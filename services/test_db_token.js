import axios from "axios";
import mysql from "mysql2/promise";

const db = await mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: 'root',
    database: 'addon3cx'
});

async function run() {
    try {
        const [pbxRows] = await db.execute("SELECT pbxurl, auth_token FROM pbxdetail WHERE company_id = 3");
        const pbx = pbxRows[0];
        let pbxurl = pbx.pbxurl.trim().replace(/\/$/, "");
        if (!pbxurl.match(/^https?:\/\//i)) pbxurl = `https://${pbxurl}`;
        const token = pbx.auth_token;

        console.log("Testing makecall with EXISTING token from DB...");
        const makecallUrl = `${pbxurl}/callcontrol/807/makecall`;
        
        try {
            const resp = await axios.post(makecallUrl, { destination: "528541370", timeout: 30 }, {
                headers: { Authorization: `Bearer ${token}` },
                timeout: 10000
            });
            console.log("makecall response:", resp.status);
        } catch (e) {
            console.log("makecall FAILED:", e.response ? e.response.status : e.message);
            if (e.response && e.response.data) {
                console.log("Error details:", JSON.stringify(e.response.data));
            }
        }

    } catch (e) {
        console.error("Error:", e.message);
    } finally {
        await db.end();
    }
}

run();
