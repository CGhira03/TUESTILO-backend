<?php
namespace App\Controllers;

use App\Config\Database;
use Firebase\JWT\JWT;
use PDO;
use Exception;


class AuthController
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../Config/config.php';
    }

    public function register()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$name || !$email || !$password) {
            http_response_code(400);
            return ['message' => 'Faltan campos obligatorios'];
        }

        try {
            $db = Database::connect();

            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount()) {
                http_response_code(400);
                return ['message' => 'El email ya está registrado'];
            }

            $hashed = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare("
                INSERT INTO users (name, email, password)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$name, $email, $hashed]);

            $userId = $db->lastInsertId();

            $token = JWT::encode([
                'id' => $userId,
                'email' => $email,
                'exp' => time() + 3600
            ], $this->config['jwt_secret'], 'HS256');


            http_response_code(201);
            return [
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email
                ]
            ];

        } catch (Exception $e) {
            http_response_code(500);
            return [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }

    }

    public function login()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            http_response_code(400);
            return ['message' => 'Email y contraseña requeridos'];
        }

        try {
            $db = Database::connect();

            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                http_response_code(401);
                return ['message' => 'Credenciales inválidas'];
            }

            $role = (!empty($user['is_admin']) && $user['is_admin'] == 1)
                ? 'admin'
                : 'user';

            $token = JWT::encode([
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $role,
                'exp' => time() + 3600
            ], $this->config['jwt_secret'], 'HS256');

            return [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $role
                ]
            ];

        } catch (Exception $e) {
            http_response_code(500);
            return [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
}
