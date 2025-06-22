const db = require('../config/db');

exports.getProfile = async (req, res) => {
  const userId = req.user.id;
  try {
    const [rows] = await db.query(
      'SELECT id, name, email, address, phone, is_admin FROM users WHERE id = ?',
      [userId]
    );

    if (rows.length === 0) return res.status(404).json({ message: 'Usuario no encontrado' });

    const user = rows[0];

    res.json({
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        address: user.address,
        phone: user.phone,
        role: user.is_admin ? 'admin' : 'user'  
      }
    });
  } catch (error) {
    res.status(500).json({ message: 'Error al obtener el perfil', error });
  }
};


exports.updateProfile = async (req, res) => {
  const userId = req.user.id;
  const { name, email, address, phone } = req.body; 

  try {
    await db.query(
      'UPDATE users SET name = ?, email = ?, address = ?, phone = ? WHERE id = ?',
      [name, email, address, phone, userId]
    );
    res.json({ message: 'Perfil actualizado correctamente' });
  } catch (error) {
    res.status(500).json({ message: 'Error al actualizar el perfil', error });
  }
};


exports.getCart = async (req, res) => {
  const userId = req.user.id;

  try {
    const [cartItems] = await db.query(
      `SELECT c.id, p.name, p.price, c.quantity 
       FROM cart_items c 
       JOIN products p ON c.product_id = p.id 
       WHERE c.user_id = ?`,
      [userId]
    );
    res.json(cartItems);
  } catch (error) {
    res.status(500).json({ message: 'Error al obtener el carrito', error });
  }
};

exports.addCart = async (req, res) => {
  const userId = req.user.id;
  const { productId, quantity } = req.body;

  try {
    const [existing] = await db.query(
      'SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?',
      [userId, productId]
    );

    if (existing.length > 0) {
      await db.query(
        'UPDATE cart_items SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?',
        [quantity, userId, productId]
      );
      return res.json({ message: 'Carrito actualizado' });
    }

    await db.query(
      'INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)',
      [userId, productId, quantity]
    );

    res.status(201).json({ message: 'Producto añadido al carrito' });
  } catch (error) {
    res.status(500).json({ message: 'Error al añadir al carrito', error });
  }
};
// Obtener datos básicos del usuario autenticado
exports.getMe = async (req, res) => {
  try {
    const [rows] = await db.query(
      'SELECT id, name, email, address, phone, is_admin FROM users WHERE id = ?',
      [req.user.id]
    );

    if (rows.length === 0) {
      return res.status(404).json({ message: 'Usuario no encontrado' });
    }

    const user = rows[0];

    res.json({
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        address: user.address,
        phone: user.phone,
        role: user.is_admin ? 'admin' : 'user'
      }
    });
  } catch (error) {
    console.error('Error en /users/me:', error);
    res.status(500).json({ message: 'Error al obtener los datos del usuario' });
  }
};

