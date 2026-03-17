import mysql from "mysql2/promise";

const db = await mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: 'root',
    database: 'addon3cx'
});

async function run() {
    try {
        const [leads] = await db.execute("SELECT company_id, campaignid FROM campaignnumbers WHERE id = 5");
        if (leads.length === 0) { console.log("Lead not found"); return; }
        
        const { company_id, campaignid } = leads[0];
        console.log("Company ID:", company_id);
        console.log("Campaign ID:", campaignid);

        const [pbx] = await db.execute("SELECT pbxurl, pbxclientid, pbxsecret, auth_token FROM pbxdetail WHERE company_id = ?", [company_id]);
        if (pbx.length === 0) { console.log("PBX not found"); return; }

        const p = pbx[0];
        console.log("PBX URL:", p.pbxurl);
        console.log("PBX Client ID:", p.pbxclientid);
        console.log("PBX Secret (first 4):", p.pbxsecret ? p.pbxsecret.substring(0, 4) + "..." : "null");
        console.log("Auth Token (first 10):", p.auth_token ? p.auth_token.substring(0, 10) + "..." : "null");

    } catch (e) {
        console.error("Error:", e.message);
    } finally {
        await db.end();
    }
}

run();
