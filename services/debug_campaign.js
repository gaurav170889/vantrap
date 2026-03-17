import mysql from "mysql2/promise";

const db = await mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: 'root',
    database: 'addon3cx'
});

async function run() {
    try {
        const [campaigns] = await db.execute("SELECT * FROM campaign WHERE id = 3");
        console.log(JSON.stringify(campaigns[0], null, 2));
    } catch (e) {
        console.error(e.message);
    } finally {
        await db.end();
    }
}

run();
