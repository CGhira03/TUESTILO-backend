const db = require('../config/db');
const crypto = require('crypto');

// Obtener todos los productos con paginación y filtros
exports.getAll = async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 8;
    const offset = (page - 1) * limit;
    const search = req.query.search || '';
    const category = req.query.category || '';

    let whereClauses = [];
    let params = [];

    if (search) {
      whereClauses.push('name LIKE ?');
      params.push(`%${search}%`);
    }

    if (category) {
      whereClauses.push('category = ?');
      params.push(category);
    }

    const whereSQL = whereClauses.length ? 'WHERE ' + whereClauses.join(' AND ') : '';

    const [products] = await db.query(`
      SELECT id, name, description, price, category, sizes, image_url, code
      FROM products
      ${whereSQL}
      ORDER BY id DESC
      LIMIT ? OFFSET ?
    `, [...params, limit, offset]);

    const [[{ count }]] = await db.query(`
      SELECT COUNT(*) as count FROM products ${whereSQL}
    `, params);

    res.json({
      products,
      total: count,
      totalPages: Math.ceil(count / limit),
      currentPage: page
    });
  } catch (err) {
    console.error('Error al obtener productos:', err.message);
    res.status(500).json({ message: 'Error interno del servidor' });
  }
};

// Obtener producto por ID
exports.getOne = async (req, res) => {
  try {
    const [[product]] = await db.query('SELECT * FROM products WHERE id = ?', [req.params.id]);
    if (!product) {
      return res.status(404).json({ message: 'Producto no encontrado' });
    }
    res.json(product);
  } catch (err) {
    res.status(500).json({ message: 'Error interno del servidor' });
  }
};

// Generar código único
async function generateUniqueCode() {
  while (true) {
    const code = crypto.randomBytes(4).toString('hex').toUpperCase();
    const [rows] = await db.query('SELECT 1 FROM products WHERE code = ?', [code]);
    if (rows.length === 0) return code;
  }
}

// Crear producto
exports.create = async (req, res) => {
  const { name, description, price, category, sizes } = req.body;
  const imageUrl = req.file ? `/uploads/${req.file.filename}` : null;

  if (!name || !price || !category) {
    return res.status(400).json({ message: 'Faltan campos obligatorios' });
  }

  try {
    const code = await generateUniqueCode();
    await db.query(
      'INSERT INTO products (name, description, price, category, sizes, image_url, code) VALUES (?, ?, ?, ?, ?, ?, ?)',
      [name, description, price, category, sizes, imageUrl, code]
    );
    res.status(201).json({ message: 'Producto creado correctamente', code });
  } catch (err) {
    res.status(500).json({ message: 'Error al crear el producto' });
  }
};

// Actualizar producto
exports.update = async (req, res) => {
  const { name, description, price, category, sizes } = req.body;
  const imageUrl = req.file ? `/uploads/${req.file.filename}` : null;

  try {
    const fields = ['name', 'description', 'price', 'category', 'sizes'];
    const values = [name, description, price, category, sizes];

    if (imageUrl) {
      fields.push('image_url');
      values.push(imageUrl);
    }

    const setClause = fields.map(field => `${field} = ?`).join(', ');
    values.push(req.params.id);

    await db.query(`UPDATE products SET ${setClause} WHERE id = ?`, values);
    res.json({ message: 'Producto actualizado correctamente' });
  } catch (err) {
    res.status(500).json({ message: 'Error al actualizar el producto' });
  }
};

// Eliminar producto
exports.remove = async (req, res) => {
  const id = req.params.id;

  if (!id || isNaN(Number(id))) {
    return res.status(400).json({ message: 'ID inválido' });
  }

  try {
    const [result] = await db.query('SELECT id FROM products WHERE id = ?', [id]);
    if (result.length === 0) {
      return res.status(404).json({ message: 'Producto no encontrado' });
    }

    await db.query('DELETE FROM products WHERE id = ?', [id]);
    res.json({ message: 'Producto eliminado correctamente' });
  } catch (err) {
    res.status(500).json({ message: 'Error al eliminar el producto' });
  }
};
