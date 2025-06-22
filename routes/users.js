const express = require('express');
const router = express.Router();
const { verifyToken } = require('../middlewares/authMiddleware');
const userController = require('../controllers/userController'); 

// Actualizar perfil del usuario
router.put('/me', verifyToken, userController.updateProfile);

// Obtener información del usuario autenticado (incluyendo is_admin)
router.get('/me', verifyToken, userController.getMe);

// Actualizar perfil
router.put('/me', verifyToken, userController.updateProfile);


module.exports = router;
