const jwt = require('jsonwebtoken');

const verifyToken = (req, res, next) => {
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({ message: 'Token no proporcionado' });
  }

  const token = authHeader.split(' ')[1];

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    console.log('Token decodificado:', decoded); // para depurar
    req.user = decoded; 
    next();
  } catch (error) {
    console.error('Token inválido:', error.message);
    return res.status(403).json({ message: 'Token inválido o expirado' });
  }
};

const verifyAdmin = (req, res, next) => {
  verifyToken(req, res, () => {
    if (req.user && req.user.role === 'admin') {
      next();
    } else {
      res.status(403).json({ message: 'Acceso denegado: se requiere rol admin' });
    }
  });
};

module.exports = {
  verifyToken,
  verifyAdmin
};
