const mysql = require('mysql2/promise');

const pool = mysql.createPool({
  host: process.env.DB_HOST,
  port: parseInt(process.env.DB_PORT),
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

async function verificarConexion() {
  try {
    const conexion = await pool.getConnection();
    console.log('✅ Conexión exitosa a MySQL');
    conexion.release();
  } catch (err) {
    console.error('❌ Error al conectar a MySQL:', err.message);
  }
}

verificarConexion();

module.exports = pool;
