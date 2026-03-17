import mysql from "mysql2/promise";

const db = await mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: 'root',
    database: 'addon3cx'
});

async function run() {
    try {
        const [pbx] = await db.execute("SELECT auth_updated_at FROM pbxdetail WHERE company_id = 3");
        if (pbx.length === 0) { console.log("PBX not found"); return; }
        
        const dbDate = pbx[0].auth_updated_at;
        const now = new Date();
        
        console.log("Database auth_updated_at:", dbDate);
        console.log("Current JS Date (UTC):", now.toISOString());
        console.log("Current JS Date (Local):", now.toString());
        
        if (dbDate) {
            const ageSec = Math.floor((now - new Date(dbDate)) / 1000);
            console.log("Calculated Age (sec):", ageSec);
            console.log("Will reuse token (age < 45):", ageSec < 45);
        }

    } catch (e) {
        console.error("Error:", e.message);
    } finally {
        await db.end();
    }
}

run();
