import axios from "axios";
import mysql from "mysql2/promise";
import qs from "qs";

const db = await mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: 'root',
    database: 'addon3cx'
});

async function run() {
    try {
        const [pbxRows] = await db.execute("SELECT pbxurl, pbxclientid, pbxsecret FROM pbxdetail WHERE company_id = 3");
        const pbx = pbxRows[0];
        let pbxurl = pbx.pbxurl.trim().replace(/\/$/, "");
        if (!pbxurl.match(/^https?:\/\//i)) pbxurl = `https://${pbxurl}`;

        const tokenUrl = `${pbxurl}/connect/token`;
        const body = qs.stringify({
            client_id: pbx.pbxclientid,
            client_secret: pbx.pbxsecret,
            grant_type: "client_credentials"
        });

        const tokenResp = await axios.post(tokenUrl, body);
        const token = tokenResp.data.access_token;

        console.log("SystemStatus Check:");
        const statusUrl = `${pbxurl}/xapi/v1/SystemStatus`;
        const resp = await axios.get(statusUrl, {
            headers: { Authorization: `Bearer ${token}` }
        });
        console.log("MaxSimCalls:", resp.data.MaxSimCalls);
        console.log("CallsActive:", resp.data.CallsActive);

    } catch (e) {
        console.error("Error:", e.message);
    } finally {
        await db.end();
    }
}

run();
