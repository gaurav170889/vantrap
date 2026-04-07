import 'dotenv/config';
console.log("DIALER V3 STARTING - DEBUG MODE");
import mysql from "mysql2/promise";
import axios from "axios";
import qs from "qs";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { v4 as uuidv4 } from 'uuid';

const LOOP_MS = 1000;
const QUEUE_FRESH_SEC = 10;
const TOKEN_REUSE_SEC = 45;
const SYSTEM_STATUS_CACHE_SEC = 3;
const PHONE_LOCK_TTL_SEC = 7200; // Keep lock during long calls; stale locks are auto-cleaned
const PHONE_LOCK_CLEANUP_MS = 30000;
const DIALER_DN = "802"; // Predictive Dialer Extension
const MINIMUM_FREE_CHANNELS_DEFAULT = Number.parseInt(
    process.env.MINIMUM_FREE_CHANNELS || process.env.POWER_DIALER_MIN_FREE_CHANNELS || "2",
    10
);
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const LOG_FILE = path.join(__dirname, "dialer.log");
const WORKER_ID = `worker-${process.pid}`;

// ------------ Helpers ------------
const sleep = (ms) => new Promise(r => setTimeout(r, ms));
const normalizeNum = (s) => String(s || "").replace(/[^\d+]/g, "");
const normalizePhoneKey = (s) => String(s || "").replace(/\D/g, "");

function log(msg, data = null) {
    const ts = new Date().toLocaleString();
    let entry = `[${ts}] ${msg}`;
    if (data) {
        try {
            entry += " | DATA: " + JSON.stringify(data);
        } catch (e) {
            entry += " [Circular/Unserializable Data]";
        }
    }
    console.log(`[${ts}] ${msg}`, data ? data : "");
    fs.appendFileSync(LOG_FILE, entry + "\n");
}

const logCallAttemptV2 = async (companyId, campaignId, leadId, callId, status, disposition, agentId, attemptNo) => {
    if (!companyId || !campaignId || !leadId) return;
    try {
        await db.execute(
            `INSERT INTO dialer_call_log 
             (company_id, campaign_id, campaignnumber_id, call_id, call_status, disposition, agent_id, started_at, attempt_no)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)`,
            [companyId, campaignId, leadId, callId, status, disposition, agentId, attemptNo]
        );

        // Update Lead Summary
        await db.execute(
            `UPDATE campaignnumbers 
             SET last_call_status=?, last_disposition=?, agent_connected=?, last_call_id=?, attempts_used = attempts_used + 1, last_call_started_at=NOW()
             WHERE id=? AND company_id=?`,
            [status, disposition, agentId, callId, leadId, companyId]
        );
    } catch (e) {
        console.error("Error in logCallAttemptV2:", e.message);
    }
};

// ------------ DB ------------
const db = mysql.createPool({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    connectionLimit: 10
});

// ------------ PBXDETAIL: token cache per company & TIMEZONE CHECK --------
async function getPbxTokenByCompany(companyId) {
    const [rows] = await db.execute(
        `SELECT id AS pbx_id, pbxurl, pbxclientid, pbxsecret, auth_token, auth_updated_at, timezone
         FROM pbxdetail WHERE company_id=? LIMIT 1`,
        [companyId]
    );
    if (!rows.length) throw new Error(`pbxdetail not found for company_id=${companyId}`);

    const pbx = rows[0];
    if (!pbx.pbxurl || !pbx.pbxclientid || !pbx.pbxsecret) throw new Error("PBX creds missing");

    let safeUrl = String(pbx.pbxurl).trim().replace(/\/$/, "");
    if (!safeUrl.match(/^https?:\/\//i)) safeUrl = `https://${safeUrl}`;

    if (pbx.auth_token && pbx.auth_updated_at) {
        const ageSec = Math.floor((new Date() - new Date(pbx.auth_updated_at)) / 1000);
        if (ageSec < TOKEN_REUSE_SEC) return { pbxurl: safeUrl, token: pbx.auth_token, timezone: pbx.timezone };
    }

    log(`[Company ${companyId}] Refreshing 3CX Token...`);
    const tokenUrl = `${safeUrl}/connect/token`;
    const body = qs.stringify({
        client_id: pbx.pbxclientid,
        client_secret: pbx.pbxsecret,
        grant_type: "client_credentials"
    });

    const resp = await axios.post(tokenUrl, body, {
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        timeout: 10000
    });

    const token = resp.data?.access_token;
    if (!token) throw new Error("No access_token from 3CX");

    await db.execute(
        `UPDATE pbxdetail SET auth_token=?, auth_updated_at=NOW() WHERE id=? AND company_id=?`,
        [token, pbx.pbx_id, companyId]
    );

    return { pbxurl: safeUrl, token, timezone: pbx.timezone };
}

// ------------ TIMEZONE HELPER: Get PBX timezone by company --------
async function getPbxTimezone(companyId) {
    try {
        const [rows] = await db.execute(
            `SELECT timezone FROM pbxdetail WHERE company_id=? LIMIT 1`,
            [companyId]
        );
        if (!rows.length) {
            log(`[Company ${companyId}] No pbxdetail record found for timezone check.`, { companyId });
            return null;
        }
        const tz = rows[0].timezone;
        if (!tz || !isPbxTimezoneValid(tz)) {
            log(`[Company ${companyId}] PBX timezone is not set or invalid: ${tz}`, { companyId, timezone: tz });
            return null;
        }
        return tz;
    } catch (e) {
        log(`[Company ${companyId}] Error fetching PBX timezone: ${e.message}`, { companyId, error: e.message });
        return null;
    }
}

// ------------ TIMEFRAME CHECK WITH TIMEZONE --------
// If timezone is set, verify that NOW() (in PBX TZ) >= next_call_at (UTC)
// If no timezone, fallback to direct MySQL NOW() comparison
function buildTimeframeFilter(timezone) {
    if (!timezone || !isPbxTimezoneValid(timezone)) {
        // No valid timezone: use direct UTC comparison
        return `next_call_at IS NULL OR next_call_at <= NOW()`;
    }
    // With timezone: Convert NOW() to PBX TZ then compare
    // CONVERT_TZ(NOW(), '+00:00', 'PBX_OFFSET') or use CAST(CONVERT_TZ(...) as DATETIME)
    return `next_call_at IS NULL OR CONVERT_TZ(NOW(), '+00:00', '${timezone}') >= next_call_at`;
}

// ------------ TIMEZONE HELPER: Check if PBX timezone is valid --------
function isPbxTimezoneValid(timezone) {
    if (!timezone || typeof timezone !== 'string') return false;
    try {
        Intl.DateTimeFormat(undefined, { timeZone: timezone });
        return true;
    } catch (err) {
        return false;
    }
}

// ------------ TIMEZONE HELPER: Get current time in PBX timezone (ISO string in that TZ) --------
function getCurrentTimeInTimezone(timezone) {
    if (!isPbxTimezoneValid(timezone)) {
        return new Date(); // Fall back to local/UTC
    }
    return new Date();
}

// ------------ DID Rotation ------------
async function getCampaignDidRotationConfig(companyId, campaignId) {
    let rows = [];
    try {
        const [qRows] = await db.execute(
            `SELECT cor.outbound_rule_id, cor.last_used_map_id, cdm.id AS map_id, cdm.sort_order, d.did
             FROM campaign_outbound_rule cor
             INNER JOIN campaign_did_map cdm
                ON cdm.company_id = cor.company_id AND cdm.campaign_id = cor.campaign_id
             INNER JOIN pbx_dids d
                ON d.id = cdm.did_id AND d.company_id = cdm.company_id
             WHERE cor.company_id = ? AND cor.campaign_id = ?
             ORDER BY cdm.sort_order ASC, cdm.id ASC`,
            [companyId, campaignId]
        );
        rows = qRows;
    } catch (e) {
        if (e && e.code === 'ER_NO_SUCH_TABLE') return null;
        throw e;
    }

    if (!rows.length) return null;

    return {
        outboundRuleId: Number(rows[0].outbound_rule_id || 0),
        lastUsedMapId: rows[0].last_used_map_id ? Number(rows[0].last_used_map_id) : null,
        didRows: rows.map(r => ({
            mapId: Number(r.map_id),
            did: String(r.did || "").trim()
        })).filter(r => r.did)
    };
}

function selectNextDidEntry(didRows, lastUsedMapId) {
    if (!Array.isArray(didRows) || didRows.length === 0) return null;
    if (!lastUsedMapId) return didRows[0];

    const idx = didRows.findIndex(r => r.mapId === Number(lastUsedMapId));
    if (idx < 0) return didRows[0];

    return didRows[(idx + 1) % didRows.length];
}

function buildOutboundRulePatchPayload(rule, nextDid) {
    const routes = Array.isArray(rule?.Routes) ? rule.Routes : [];
    const dnRanges = Array.isArray(rule?.DNRanges) ? rule.DNRanges : [];
    const groupIds = Array.isArray(rule?.GroupIds) ? rule.GroupIds : [];

    const patchedRoutes = routes.map((r, idx) => ({
        CallerID: idx === 0 ? nextDid : String(r?.CallerID || ""),
        Prepend: String(r?.Prepend || ""),
        StripDigits: Number(r?.StripDigits || 0),
        TrunkId: Number.isFinite(Number(r?.TrunkId)) ? Number(r?.TrunkId) : -1,
        TrunkName: r?.TrunkName ?? null,
        Append: String(r?.Append || "")
    }));

    return {
        Name: String(rule?.Name || ""),
        Prefix: String(rule?.Prefix || ""),
        DNRanges: dnRanges.map(d => ({
            From: String(d?.From || ""),
            To: d?.To ?? null
        })),
        NumberLengthRanges: String(rule?.NumberLengthRanges || ""),
        Routes: patchedRoutes,
        GroupIds: groupIds
    };
}

async function rotateDidForCampaignIfConfigured({ companyId, campaignId, pbxurl, token }) {
    const cfg = await getCampaignDidRotationConfig(companyId, campaignId);
    if (!cfg || !cfg.outboundRuleId || !cfg.didRows.length) return null;

    const nextEntry = selectNextDidEntry(cfg.didRows, cfg.lastUsedMapId);
    if (!nextEntry || !nextEntry.did) return null;

    const getUrl = `${pbxurl}/xapi/v1/OutboundRules(${cfg.outboundRuleId})`;
    const getResp = await axios.get(getUrl, {
        headers: { Authorization: `Bearer ${token}` },
        timeout: 15000
    });

    const payload = buildOutboundRulePatchPayload(getResp.data, nextEntry.did);

    const patchUrl = `${pbxurl}/xapi/v1/OutboundRules(${cfg.outboundRuleId})`;
    await axios.patch(patchUrl, payload, {
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json"
        },
        timeout: 15000
    });

    await db.execute(
        `UPDATE campaign_outbound_rule
         SET last_used_map_id = ?, updated_at = NOW()
         WHERE company_id = ? AND campaign_id = ?`,
        [nextEntry.mapId, companyId, campaignId]
    );

    return nextEntry.did;
}

// ------------ Queue gate ------------
async function queueAllowsDialing(companyId, queueDn) {
    const [rows] = await db.execute(
        `SELECT available_agents, updated_at FROM dialer_queue_status
         WHERE company_id=? AND queue_dn=? LIMIT 1`,
        [companyId, queueDn]
    );

    if (!rows.length) return { ok: false, reason: "no_queue_status" };

    const { available_agents, updated_at } = rows[0];
    const ageSec = Math.floor((Date.now() - new Date(updated_at).getTime()) / 1000);

    if (ageSec > QUEUE_FRESH_SEC) return { ok: false, reason: "stale_queue_status", age: ageSec, agents: available_agents };
    if (parseInt(available_agents, 10) <= 0) return { ok: false, reason: "no_free_agents", age: ageSec, agents: available_agents };

    return { ok: true, age: ageSec, agents: available_agents };
}

const systemStatusCache = new Map();

function toSafeInt(value, fallback = 0) {
    const parsed = Number.parseInt(String(value), 10);
    return Number.isFinite(parsed) ? parsed : fallback;
}

function resolvePowerDialerMinFreeChannels(campaign) {
    const campaignConfigured = toSafeInt(campaign?.concurrent_calls, NaN);
    if (Number.isFinite(campaignConfigured) && campaignConfigured >= 0) {
        return campaignConfigured;
    }
    return Math.max(0, toSafeInt(MINIMUM_FREE_CHANNELS_DEFAULT, 2));
}

async function getSystemStatusByCompany(companyId) {
    const cached = systemStatusCache.get(companyId);
    const now = Date.now();
    if (cached && (now - cached.fetchedAt) / 1000 < SYSTEM_STATUS_CACHE_SEC) {
        return cached.status;
    }

    const { pbxurl, token } = await getPbxTokenByCompany(companyId);
    const statusUrl = `${pbxurl}/xapi/v1/SystemStatus`;
    const resp = await axios.get(statusUrl, {
        headers: { Authorization: `Bearer ${token}` },
        timeout: 10000
    });

    const payload = resp?.data || {};
    const status = {
        maxSimCalls: toSafeInt(payload.MaxSimCalls, 0),
        callsActive: toSafeInt(payload.CallsActive, 0)
    };

    systemStatusCache.set(companyId, { status, fetchedAt: now });
    return status;
}

async function powerDialerAllowsDialing(companyId, campaign, dialerDn) {
    try {
        const status = await getSystemStatusByCompany(companyId);
        const minimumFreeChannels = resolvePowerDialerMinFreeChannels(campaign);
        const freeChannels = Math.max(0, status.maxSimCalls - status.callsActive);
        const ok = freeChannels > minimumFreeChannels;

        if (!ok) {
            return {
                ok: false,
                reason: "insufficient_free_channels",
                maxSimCalls: status.maxSimCalls,
                callsActive: status.callsActive,
                freeChannels,
                minimumFreeChannels,
                dialerDn
            };
        }

        return {
            ok: true,
            maxSimCalls: status.maxSimCalls,
            callsActive: status.callsActive,
            freeChannels,
            minimumFreeChannels,
            dialerDn
        };
    } catch (e) {
        return { ok: false, reason: "system_status_error", error: e.message };
    }
}



// ------------ Global phone lock ------------
async function cleanupExpiredPhoneLocks() {
    await db.execute(`DELETE FROM active_phone_locks WHERE expires_at < NOW()`);
}

async function tryAcquirePhoneLock(companyId, campaignId, leadId, phoneE164, phoneRaw, lockToken) {
    const sourcePhone = String(phoneE164 || phoneRaw || "").trim();
    const phoneKey = normalizePhoneKey(sourcePhone);
    if (!phoneKey) return { ok: false, reason: "invalid_phone" };

    const [res] = await db.execute(
        `INSERT IGNORE INTO active_phone_locks
         (company_id, phone_key, source_phone, lead_id, campaign_id, lock_token, locked_by, expires_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))`,
        [companyId, phoneKey, sourcePhone, leadId, campaignId, lockToken, WORKER_ID, PHONE_LOCK_TTL_SEC]
    );

    if (res.affectedRows > 0) return { ok: true, phoneKey };
    return { ok: false, reason: "phone_locked", phoneKey };
}

async function releasePhoneLock(lockToken) {
    if (!lockToken) return;
    await db.execute(`DELETE FROM active_phone_locks WHERE lock_token=?`, [lockToken]);
}

async function getAgentExtensionById(companyId, agentId) {
    const normalizedAgentId = Number.parseInt(String(agentId || ''), 10);
    if (!Number.isFinite(normalizedAgentId) || normalizedAgentId <= 0) {
        return null;
    }

    const [rows] = await db.execute(
        `SELECT agent_id, agent_ext
         FROM agent
         WHERE company_id=? AND agent_id=?
         LIMIT 1`,
        [companyId, normalizedAgentId]
    );

    if (!rows.length) return null;

    const agentExt = String(rows[0].agent_ext || '').trim();
    if (!agentExt) return null;

    return {
        agentId: Number(rows[0].agent_id),
        agentExt
    };
}

// ------------ Queue Logic: Pick & Lock (WITH TIMEZONE AWARENESS) --------
async function pickLead(companyId, campaignId, pbxTimezone) {
    const lockToken = uuidv4();

    // Determine time comparison clause based on timezone
    let timeframeSql = `(next_call_at IS NULL OR next_call_at <= NOW())`;
    if (pbxTimezone && isPbxTimezoneValid(pbxTimezone)) {
        // With timezone: Compare NOW() in PBX TZ >= next_call_at (UTC)
        timeframeSql = `(next_call_at IS NULL OR CONVERT_TZ(NOW(), '+00:00', '${pbxTimezone}') >= next_call_at)`;
        log(`[Camp ${campaignId}] Using timezone-aware time check (TZ=${pbxTimezone})`, { companyId, campaignId, timezone: pbxTimezone });
    } else if (pbxTimezone) {
        log(`[Camp ${campaignId}] PBX timezone invalid or not set, using UTC fallback: ${pbxTimezone}`, { companyId, campaignId, timezone: pbxTimezone });
    }

    // 1. Find candidate
    // Rules: READY/SCHEDULED, Time reached, Attempts left, Not DNC, Not Locked (or stale lock)
    const [rows] = await db.execute(
        `SELECT id, phone_e164, phone_raw, attempts_used, max_attempts 
         FROM campaignnumbers
         WHERE company_id=? AND campaignid=?
           AND state IN ('READY','SCHEDULED')
           AND is_dnc=0
           AND attempts_used < max_attempts
           AND ${timeframeSql}
           AND (locked_at IS NULL OR locked_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE))
         ORDER BY priority ASC, next_call_at ASC
         LIMIT 1`,
        [companyId, campaignId]
    );

    if (!rows.length) return null;
    const lead = rows[0];

    // 2. Try to Lock
    const [res] = await db.execute(
        `UPDATE campaignnumbers
         SET locked_at=NOW(), locked_by=?, lock_token=?, state='DIALING'
         WHERE id=? AND company_id=?
           AND state IN ('READY','SCHEDULED')
           AND (locked_at IS NULL OR locked_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE))`, // Optimistic Lock + stale lock guard
        [WORKER_ID, lockToken, lead.id, companyId]
    );

    const affected = Number(res?.affectedRows || 0);
    const changed = Number(res?.changedRows || 0);
    if (affected === 0) {
        log(`[Camp ${campaignId}] Lead ${lead.id} lock failed (lost race or stale state).`, {
            companyId,
            campaignId,
            leadId: lead.id,
            worker: WORKER_ID,
            affectedRows: affected,
            changedRows: changed
        });
        return null;
    }

    let persistedLock = null;
    try {
        const [lockRows] = await db.execute(
            `SELECT state, locked_by, lock_token, locked_at
             FROM campaignnumbers
             WHERE id=? AND company_id=?
             LIMIT 1`,
            [lead.id, companyId]
        );
        persistedLock = lockRows?.[0] || null;
    } catch (e) {
        log(`[Camp ${campaignId}] Lead ${lead.id} lock verify query failed: ${e.message}`);
    }

    log(`[Camp ${campaignId}] Lead ${lead.id} locked for dialing.`, {
        companyId,
        campaignId,
        leadId: lead.id,
        worker: WORKER_ID,
        lockToken,
        affectedRows: affected,
        changedRows: changed,
        persistedLock
    });

    return { ...lead, lockToken };
}

async function pickScheduledLead(companyId, campaignId, pbxTimezone) {
    const lockToken = uuidv4();

    // Determine time comparison clause based on timezone
    let timeframeSql = `(next_call_at IS NOT NULL AND next_call_at <= NOW())`;
    if (pbxTimezone && isPbxTimezoneValid(pbxTimezone)) {
        // With timezone: Compare NOW() in PBX TZ >= next_call_at (UTC)
        timeframeSql = `(next_call_at IS NOT NULL AND CONVERT_TZ(NOW(), '+00:00', '${pbxTimezone}') >= next_call_at)`;
        log(`[Camp ${campaignId}] Scheduled lead check with timezone (TZ=${pbxTimezone})`, { companyId, campaignId, timezone: pbxTimezone });
    } else if (pbxTimezone) {
        log(`[Camp ${campaignId}] Scheduled lead: PBX timezone invalid, using UTC fallback: ${pbxTimezone}`, { companyId, campaignId, timezone: pbxTimezone });
    }

    const [rows] = await db.execute(
        `SELECT id, phone_e164, phone_raw, attempts_used, max_attempts, agent_connected, next_call_at
         FROM campaignnumbers
         WHERE company_id=? AND campaignid=?
           AND state='SCHEDULED'
           AND is_dnc=0
           AND attempts_used < max_attempts
           AND ${timeframeSql}
           AND (locked_at IS NULL OR locked_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE))
         ORDER BY next_call_at ASC, priority ASC, id ASC
         LIMIT 1`,
        [companyId, campaignId]
    );

    if (!rows.length) return null;
    const lead = rows[0];

    const [res] = await db.execute(
        `UPDATE campaignnumbers
         SET locked_at=NOW(), locked_by=?, lock_token=?, state='DIALING'
         WHERE id=? AND company_id=?
           AND state='SCHEDULED'
           AND (locked_at IS NULL OR locked_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE))`,
        [WORKER_ID, lockToken, lead.id, companyId]
    );

    const affected = Number(res?.affectedRows || 0);
    if (affected === 0) {
        log(`[Camp ${campaignId}] Scheduled lead ${lead.id} lock failed.`, {
            companyId,
            campaignId,
            leadId: lead.id,
            worker: WORKER_ID
        });
        return null;
    }

    log(`[Camp ${campaignId}] Scheduled lead ${lead.id} locked for dialing.`, {
        companyId,
        campaignId,
        leadId: lead.id,
        worker: WORKER_ID,
        lockToken,
        nextCallAt: lead.next_call_at,
        agentConnected: lead.agent_connected || null
    });

    return { ...lead, lockToken };
}

async function verifyCampaignLockSchema() {
    try {
        const [rows] = await db.execute(
            `SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'campaignnumbers'
               AND COLUMN_NAME IN ('locked_at', 'locked_by', 'lock_token')`
        );

        const found = new Set((rows || []).map(r => r.COLUMN_NAME));
        const required = ['locked_at', 'locked_by', 'lock_token'];
        const missing = required.filter(c => !found.has(c));

        if (missing.length > 0) {
            log(`[Schema] campaignnumbers is missing lock columns.`, { missing });
        } else {
            log(`[Schema] campaignnumbers lock columns verified.`);
        }
    } catch (e) {
        log(`[Schema] Unable to verify campaignnumbers lock columns: ${e.message}`);
    }
}

async function unlockLead(companyId, leadId, newState, nextCallAt = null) {
    let sql = `UPDATE campaignnumbers SET locked_at=NULL, locked_by=NULL, lock_token=NULL, state=?`;
    const params = [newState];

    if (nextCallAt) {
        sql += `, next_call_at=?`;
        params.push(nextCallAt);
    }

    sql += ` WHERE id=? AND company_id=?`;
    params.push(leadId, companyId);

    await db.execute(sql, params);
}

// ------------ 3CX & Monitor ------------
async function makeCall({ pbxurl, token, destination, dialerDn }) {
    const dn = dialerDn || DIALER_DN;
    const url = `${pbxurl}/callcontrol/${dn}/makecall`;
    const payload = { destination, timeout: 30 };
    log(`[API Request] Making call to ${destination} via ${dn}...`, { url, payload });
    try {
        const resp = await axios.post(url, payload, {
            headers: { Authorization: `Bearer ${token}` }
        });
        log(`[API Success] Call ID: ${resp.data?.result?.callid || resp.data?.callid || "unknown"}`);
        return resp.data?.result?.callid || resp.data?.callid;
    } catch (e) {
        log(`[API Error] 3CX makecall FAILED: ${e.message}`, {
            status: e.response?.status,
            data: e.response?.data,
            url
        });
        throw e;
    }
}

async function waitThenTransfer({ pbxurl, token, callid, destination, transferDn, dialerDn }) {
    const deadline = Date.now() + 45000; // 45s Timeout
    log(`[Call ${callid}] Dialing ${destination} using Extension ${dialerDn}...`);

    while (Date.now() < deadline) {
        try {
            const url = `${pbxurl}/callcontrol/${dialerDn}/participants`;
            const resp = await axios.get(url, { headers: { Authorization: `Bearer ${token}` } });

            const list = Array.isArray(resp.data) ? resp.data : [];
            // Find participant for this call
            const p = list.find(x => String(x.callid) === String(callid) && x.status === 'Connected');

            if (p) {
                log(`[Call ${callid}] Answered! Transferring to ${transferDn}`);
                await axios.post(
                    `${pbxurl}/callcontrol/${dialerDn}/participants/${p.id}/transferto`,
                    { destination: transferDn },
                    { headers: { Authorization: `Bearer ${token}` } }
                );
                return true;
            }
        } catch (e) {
            if (e.response && e.response.status !== 404) {
                log(`[Call ${callid}] participant poll error: ${e.message}`);
            }
        }
        await sleep(1000);
    }
    return false;
}

// ------------ Global ActiveCalls Monitor ------------
let isGlobalMonitorRunning = false;

async function globalMonitorTick() {
    if (isGlobalMonitorRunning) return;
    isGlobalMonitorRunning = true;

    try {
        const [rows] = await db.execute(
            `SELECT DISTINCT company_id FROM campaignnumbers WHERE state='DISPO_PENDING'`
        );

        for (const row of rows) {
            const companyId = row.company_id;
            const creds = await getPbxTokenByCompany(companyId).catch(() => null);
            if (!creds || !creds.pbxurl || !creds.token) continue;

            const url = `${creds.pbxurl}/xapi/v1/ActiveCalls?$top=100&$skip=0&$count=true`;
            let activeCalls = [];
            try {
                const resp = await axios.get(url, { headers: { Authorization: `Bearer ${creds.token}` } });
                activeCalls = resp.data?.value || [];
            } catch (e) {
                log(`[GlobalMonitor] Error fetching active calls for company ${companyId}: ${e.message}`);
                continue;
            }

            const [leads] = await db.execute(
                `SELECT id, company_id, phone_e164, phone_raw, lock_token, last_call_id, agent_connected, last_call_started_at
                 FROM campaignnumbers WHERE state='DISPO_PENDING' AND company_id=?`,
                [companyId]
            );

            for (const lead of leads) {
                const originalCallId = lead.last_call_id;
                const leadPhone = lead.phone_e164 || lead.phone_raw;

                let myCall = activeCalls.find(c => String(c.Id) === String(originalCallId));
                if (!myCall) {
                    const targetPhone = normalizeNum(leadPhone);
                    myCall = activeCalls.find(c => {
                        const callerNum = normalizeNum(c.Caller);
                        const calleeNum = normalizeNum(c.Callee);
                        return callerNum.includes(targetPhone) || calleeNum.includes(targetPhone);
                    });
                }

                if (!myCall) {
                    // Call is gone, mark finished
                    const durationSec = lead.last_call_started_at ? Math.floor((new Date() - new Date(lead.last_call_started_at)) / 1000) : 0;
                    await db.execute(
                        `UPDATE campaignnumbers SET last_call_ended_at=NOW(), last_call_duration_sec=?, state='DISPO_REQUIRED' WHERE id=? AND company_id=?`,
                        [durationSec, lead.id, companyId]
                    );
                    await db.execute(
                        `UPDATE dialer_call_log SET ended_at=NOW(), duration_sec=? WHERE call_id=? AND company_id=?`,
                        [durationSec, originalCallId, companyId]
                    );
                    log(`[GlobalMonitor] Lead ${lead.id} call ${originalCallId} ended. Lock Released.`);
                    await releasePhoneLock(lead.lock_token).catch(() => { });

                } else {
                    // Call is active. Check Callee for Agent if not already connected
                    if (!lead.agent_connected) {
                        const calleeStr = myCall.Callee || "";
                        const callerStr = myCall.Caller || "";
                        // Usually agents are the ones answering the queue transfer 
                        // It can be formatted as "112 User"
                        const match = calleeStr.match(/^(\d+)\s/) || callerStr.match(/^(\d+)\s/);
                        if (match) {
                            const ext = match[1];
                            const [agents] = await db.execute(`SELECT agent_id FROM agent WHERE agent_ext=? AND company_id=?`, [ext, companyId]);
                            if (agents.length > 0) {
                                const agentId = agents[0].agent_id;
                                log(`[GlobalMonitor] Call ${originalCallId} CONNECTED to AGENT ${agentId} (Ext: ${ext})`);
                                await db.execute(
                                    `UPDATE campaignnumbers SET agent_connected=? WHERE id=? AND company_id=?`,
                                    [agentId, lead.id, companyId]
                                );
                                await db.execute(
                                    `UPDATE dialer_call_log SET agent_id=?, call_status='ANSWERED' WHERE call_id=? AND company_id=?`,
                                    [agentId, originalCallId, companyId]
                                );
                            }
                        }
                    }
                }
            }
        }
    } catch (e) {
        log(`[GlobalMonitor] Error: ${e.message}`);
    } finally {
        isGlobalMonitorRunning = false;
    }
}

// Start global monitor 
setInterval(globalMonitorTick, 2000);


// ------------ Spawn Call Flow (Async Background) ------------
async function spawnCallFlow(c, lead, queueDn, dialerDn) {
    let callStarted = false;
    try {
        const { pbxurl, token } = await getPbxTokenByCompany(c.company_id);
        const destination = lead.phone_e164 || lead.phone_raw;
        const transferDn = String(c.dg_reception_number || queueDn || '').trim();

        if (String(c.outbound_prefix || '').toLowerCase() === 'yes') {
            try {
                const rotatedDid = await rotateDidForCampaignIfConfigured({
                    companyId: c.company_id,
                    campaignId: c.id,
                    pbxurl,
                    token
                });
                if (rotatedDid) {
                    log(`[Camp ${c.id}] Rotated outbound CallerID to ${rotatedDid}`);
                }
            } catch (e) {
                log(`[Camp ${c.id}] DID rotation skipped due to error: ${e.message}`);
            }
        }

        if (!destination) {
            log(`[Camp ${c.id}] Lead ${lead.id} has no phone format. Marking invalid.`);
            await unlockLead(c.company_id, lead.id, 'INVALID');
            await incrementAgentCount(c.company_id, queueDn); // Refund agent 
            await releasePhoneLock(lead.lockToken).catch(() => { });
            return;
        }

        const phoneLock = await tryAcquirePhoneLock(c.company_id, c.id, lead.id, lead.phone_e164, lead.phone_raw, lead.lockToken);
        if (!phoneLock.ok) {
            if (phoneLock.reason === "invalid_phone") {
                log(`[Camp ${c.id}] Lead ${lead.id} has invalid phone format. Marking invalid.`);
                await unlockLead(c.company_id, lead.id, 'INVALID');
            } else {
                log(`[Camp ${c.id}] Lead ${lead.id} skipped; phone already locked (${phoneLock.phoneKey}).`);
                await unlockLead(c.company_id, lead.id, 'READY'); // CRUCIAL FIX: Free the lead so pickLead doesn't get stuck finding a DIALING row next tick
            }
            return;
        }

        callStarted = true;
        const callid = await makeCall({ pbxurl, token, destination, dialerDn });

        // Monitor Answer
        const connected = await waitThenTransfer({ pbxurl, token, callid, destination, transferDn, dialerDn });

        if (connected) {
            // Call transferred to queue/agent destination, but not yet ANSWERED by agent
            // Only mark ANSWERED when globalMonitorTick detects agent_connected
            await logCallAttemptV2(c.company_id, c.id, lead.id, callid, 'TRANSFERRED', null, null, lead.attempts_used + 1);

            // Do NOT increment agent count here. Waiting for agent to pick up.

            await unlockLead(c.company_id, lead.id, 'DISPO_PENDING');
            // Background globalMonitorTick will handle:
            // 1. Detecting if/when agent connects
            // 2. Updating log to ANSWERED if agent connects
            // 3. Releasing lock when call ends
        } else {
            await logCallAttemptV2(c.company_id, c.id, lead.id, callid, 'NO_ANSWER', 'SYSTEM_NO_ANSWER', null, lead.attempts_used + 1);

            const used = lead.attempts_used + 1; // It was updated in logCallAttemptV2 DB trigger

            if (used >= lead.max_attempts) {
                await unlockLead(c.company_id, lead.id, 'CLOSED');
            } else {
                // Retry delay 15 mins instead of 1 hour
                await db.execute(
                    `UPDATE campaignnumbers SET next_call_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id=? AND company_id=?`,
                    [lead.id, c.company_id]
                );
                await unlockLead(c.company_id, lead.id, 'READY');
            }
            await releasePhoneLock(lead.lockToken).catch(() => { });
        }
    } catch (e) {
        log(`[Camp ${c.id}] Spawn Call Error for lead ${lead.id}: ${e.message}`);
        await logCallAttemptV2(c.company_id, c.id, lead.id, 'failed', 'ERROR', null, null, lead.attempts_used + 1);

        await unlockLead(c.company_id, lead.id, 'READY', null); // Retry ?
        await releasePhoneLock(lead.lockToken).catch(() => { });
    }
}

async function markScheduledNoAnswer(c, lead, callId, agentId = null) {
    await logCallAttemptV2(
        c.company_id,
        c.id,
        lead.id,
        callId || 'failed',
        'NO_ANSWER',
        6,
        agentId,
        lead.attempts_used + 1
    );
    await unlockLead(c.company_id, lead.id, 'DISPO_REQUIRED');
    await releasePhoneLock(lead.lockToken).catch(() => { });
}

async function spawnScheduledCallFlow(c, lead, queueDn, dialerDn) {
    let phoneLockAcquired = false;
    try {
        const { pbxurl, token } = await getPbxTokenByCompany(c.company_id);
        const customerDestination = String(lead.phone_e164 || lead.phone_raw || '').trim();

        if (!customerDestination) {
            log(`[Camp ${c.id}] Scheduled lead ${lead.id} has no phone format. Marking invalid.`);
            await unlockLead(c.company_id, lead.id, 'INVALID');
            await releasePhoneLock(lead.lockToken).catch(() => { });
            return;
        }

        const phoneLock = await tryAcquirePhoneLock(c.company_id, c.id, lead.id, lead.phone_e164, lead.phone_raw, lead.lockToken);
        if (!phoneLock.ok) {
            if (phoneLock.reason === 'invalid_phone') {
                log(`[Camp ${c.id}] Scheduled lead ${lead.id} has invalid phone format. Marking invalid.`);
                await unlockLead(c.company_id, lead.id, 'INVALID');
            } else {
                log(`[Camp ${c.id}] Scheduled lead ${lead.id} skipped; phone already locked (${phoneLock.phoneKey}).`);
                await unlockLead(c.company_id, lead.id, 'SCHEDULED');
            }
            return;
        }
        phoneLockAcquired = true;

        let firstDestination = '';
        let answeredAgentId = null;
        let targetLabel = '';
        const assignedAgent = await getAgentExtensionById(c.company_id, lead.agent_connected);

        if (assignedAgent) {
            firstDestination = assignedAgent.agentExt;
            answeredAgentId = assignedAgent.agentId;
            targetLabel = `agent ${assignedAgent.agentExt}`;
        } else if (queueDn) {
            firstDestination = queueDn;
            targetLabel = `queue ${queueDn}`;
        } else {
            log(`[Camp ${c.id}] Scheduled lead ${lead.id} has no assigned agent and no queue fallback.`);
            await unlockLead(c.company_id, lead.id, 'SCHEDULED');
            await releasePhoneLock(lead.lockToken).catch(() => { });
            return;
        }

        if (String(c.outbound_prefix || '').toLowerCase() === 'yes') {
            try {
                const rotatedDid = await rotateDidForCampaignIfConfigured({
                    companyId: c.company_id,
                    campaignId: c.id,
                    pbxurl,
                    token
                });
                if (rotatedDid) {
                    log(`[Camp ${c.id}] Rotated outbound CallerID to ${rotatedDid} for scheduled call.`);
                }
            } catch (e) {
                log(`[Camp ${c.id}] Scheduled DID rotation skipped due to error: ${e.message}`);
            }
        }

        log(`[Camp ${c.id}] Scheduled lead ${lead.id} calling ${targetLabel} first, then customer ${customerDestination}.`);

        const callid = await makeCall({
            pbxurl,
            token,
            destination: firstDestination,
            dialerDn
        });

        const connected = await waitThenTransfer({
            pbxurl,
            token,
            callid,
            destination: firstDestination,
            transferDn: customerDestination,
            dialerDn
        });

        if (!connected) {
            await markScheduledNoAnswer(c, lead, callid, answeredAgentId);
            return;
        }

        if (answeredAgentId) {
            await db.execute(
                `UPDATE campaignnumbers
                 SET agent_connected=?
                 WHERE id=? AND company_id=?`,
                [answeredAgentId, lead.id, c.company_id]
            );
        }

        await logCallAttemptV2(
            c.company_id,
            c.id,
            lead.id,
            callid,
            answeredAgentId ? 'ANSWERED' : 'TRANSFERRED',
            null,
            answeredAgentId,
            lead.attempts_used + 1
        );

        await unlockLead(c.company_id, lead.id, 'DISPO_PENDING');
    } catch (e) {
        log(`[Camp ${c.id}] Scheduled Call Error for lead ${lead.id}: ${e.message}`);
        await unlockLead(c.company_id, lead.id, 'SCHEDULED');
        if (phoneLockAcquired) {
            await releasePhoneLock(lead.lockToken).catch(() => { });
        }
    }
}

// ------------ Main Loop ------------
let isProcessing = false;
let lastPhoneLockCleanupAt = 0;

async function tick() {
    if (isProcessing) return;
    isProcessing = true;

    try {
        if (Date.now() - lastPhoneLockCleanupAt > PHONE_LOCK_CLEANUP_MS) {
            await cleanupExpiredPhoneLocks().catch(() => { });
            lastPhoneLockCleanupAt = Date.now();
        }

        const [campaigns] = await db.execute(
            `SELECT c.id, c.company_id, c.routeto, c.dn_number, c.dg_reception_number, c.dialer_mode, c.concurrent_calls,
                    COALESCE(p.outbound_prefix, 'No') AS outbound_prefix
             FROM campaign c
             LEFT JOIN pbxdetail p ON p.company_id = c.company_id
               WHERE c.status='Running' AND c.is_deleted=0 AND c.dialer_mode IN ('Predictive Dialer','Power Dialer','Scheduled Dialer')`
        );

        for (const c of campaigns) {
            const queueDn = String(c.routeto || "").trim();
            const dialerDn = String(c.dn_number || DIALER_DN).trim(); // Use campaign DN or default
            const dialerMode = String(c.dialer_mode || "").trim();

            // Fetch PBX timezone for this company
            const pbxTimezone = await getPbxTimezone(c.company_id);

            if (dialerMode !== 'Scheduled Dialer' && !queueDn) continue;

            if (dialerMode === 'Predictive Dialer') {
                // Check Queue
                const gate = await queueAllowsDialing(c.company_id, queueDn);
                if (!gate.ok) {
                    continue;
                }

                // IMPORTANT FIX: Count how many active calls we are ALREADY ringing for this QUEUE across ALL campaigns.
                const [ringing] = await db.execute(
                    `SELECT COUNT(*) as count 
                     FROM campaignnumbers cn
                     INNER JOIN campaign cmp ON cn.campaignid = cmp.id
                     WHERE cn.company_id=? 
                       AND cn.state IN ('DIALING', 'DISPO_PENDING') 
                       AND cmp.routeto=? 
                       AND cmp.status='Running' AND cmp.is_deleted=0`,
                    [c.company_id, queueDn]
                );

                const activeCallsAllowed = parseInt(gate.agents, 10);
                const currentlyRinging = parseInt(ringing[0].count, 10);

                if (currentlyRinging >= activeCallsAllowed) {
                    // We are already dialing the max amount of numbers allowable for the queue agents.
                    log(`[Camp ${c.id}] Queue ${queueDn} Skipped - Ringing/Pending: ${currentlyRinging}, Allowed: ${activeCallsAllowed}`);
                    continue;
                }
            } else if (dialerMode === 'Power Dialer') {
                const powerGate = await powerDialerAllowsDialing(c.company_id, c, dialerDn);
                if (!powerGate.ok) {
                    if (powerGate.reason === 'insufficient_free_channels') {
                        log(
                            `[Camp ${c.id}] Power Dialer ${dialerDn} skipped - Active: ${powerGate.callsActive}, Max: ${powerGate.maxSimCalls}, Free: ${powerGate.freeChannels}, Minimum Free Channels: ${powerGate.minimumFreeChannels}`
                        );
                    } else {
                        log(`[Camp ${c.id}] Power Dialer gate error: ${powerGate.error || powerGate.reason}`);
                    }
                    continue;
                }
            } else if (dialerMode === 'Scheduled Dialer') {
                const lead = await pickScheduledLead(c.company_id, c.id, pbxTimezone);
                if (!lead) continue;

                spawnScheduledCallFlow(c, lead, queueDn, dialerDn).catch(e => {
                    log(`[Camp ${c.id}] Unhandled spawnScheduledCallFlow error: ${e.message}`);
                });
                continue;
            }

            const lead = await pickLead(c.company_id, c.id, pbxTimezone);
            if (!lead) continue;

            spawnCallFlow(c, lead, queueDn, dialerDn).catch(e => {
                log(`[Camp ${c.id}] Unhandled spawnCallFlow error: ${e.message}`);
            });
        }

    } catch (e) {
        log(`Create Tick Error: ${e.message}`);
    } finally {
        isProcessing = false;
    }
}

console.log("Dialer Service Started (Predictive + Power + Scheduled)");
verifyCampaignLockSchema().catch(() => { });
setInterval(tick, LOOP_MS);
