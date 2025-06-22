const db = require('../config/db');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');

exports.register = async (req, res) => {
  let connection;
  try {
    const { name, email, password } = req.body;

    if (!name || !email || !password) {
      return res.status(400).json({ message: 'Faltan campos obligatorios' });
    }

    const hashed = await bcrypt.hash(password, 10);
    connection = await db.getConnection();

    const [result] = await connection.query(
      'INSERT INTO users (name, email, password) VALUES (?, ?, ?)',
      [name, email, hashed]
    );

    const userId = result.insertId;

    // Generar token
    const token = jwt.sign(
      {
        id: userId,
        email,
        role: 'user',
      },
      process.env.JWT_SECRET,
      { expiresIn: '1h' }
    );

    // Enviar token y datos mínimos
    res.status(201).json({
      token,
      user: {
        id: userId,
        name,
        email,
      }
    });

  } catch (error) {
    console.error('Error en registro:', error);
    if (error.code === 'ER_DUP_ENTRY') {
      return res.status(400).json({ message: 'El email ya está registrado' });
    }
    res.status(500).json({ message: 'Error en el servidor' });
  } finally {
    if (connection) connection.release();
  }
};



exports.login = async (req, res) => {
  let connection;
  try {
    const { email, password } = req.body;
    
    connection = await db.getConnection();
    
    const [[user]] = await connection.query(
      'SELECT * FROM users WHERE email = ?', 
      [email]
    );
    
    if (!user || !(await bcrypt.compare(password, user.password))) {
      return res.status(401).json({ message: 'Credenciales inválidas' });
    }
    
    const token = jwt.sign(
      {
        id: user.id,
        email: user.email,
        role: user.is_admin ? 'admin' : 'user',
      },
      process.env.JWT_SECRET,
      { expiresIn: '1h' }
    );
         
    
    res.json({ token, user: { id: user.id, name: user.name, email: user.email, role: user.is_admin ? 'admin' : 'user' } });
  } catch (error) {
    console.error('Error en login:', error);
    res.status(500).json({ message: 'Error en el servidor' });
  } finally {
    if (connection) connection.release();
  }
};