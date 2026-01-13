<?php

namespace App\Middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    private static function getJwtSecret(): string
    {
        $config = require __DIR__ . '/../Config/config.php';

        if (empty($config['jwt_secret'])) {
            http_response_code(500);
            echo json_encode([
                'message' => 'JWT secret no configurado'
            ]);
            exit;
        }

        return $config['jwt_secret'];
    }

    /**
     * Verifica token JWT
     */
    public static function verifyToken()
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        $headers = array_change_key_case($headers, CASE_LOWER);

        if (empty($headers['authorization'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Token no proporcionado']);
            exit;
        }

        if (!preg_match('/^Bearer\s+(\S+)$/', $headers['authorization'], $matches)) {
            http_response_code(401);
            echo json_encode(['message' => 'Formato de token inválido']);
            exit;
        }

        try {
            return JWT::decode(
                $matches[1],
                new Key(self::getJwtSecret(), 'HS256')
            );

        } catch (\Throwable $e) {
            http_response_code(401);
            echo json_encode([
                'message' => 'Token inválido o expirado',
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Verifica rol ADMIN
     */
    public static function verifyAdmin()
    {
        $user = self::verifyToken();

        if (empty($user->role) || $user->role !== 'admin') {
            http_response_code(403);
            echo json_encode([
                'message' => 'Acceso denegado: requiere rol admin'
            ]);
            exit;
        }

        return $user;
    }
}
