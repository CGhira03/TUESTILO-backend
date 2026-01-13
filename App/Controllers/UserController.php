<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class UserController
{
    public function getMe($user)
    {
        try {
            $db = Database::connect();

            $stmt = $db->prepare("
                SELECT id, name, email, address, phone, is_admin
                FROM users WHERE id = ?
            ");
            $stmt->execute([$user->id]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                http_response_code(404);
                return ['message' => 'Usuario no encontrado'];
            }

            $row['role'] = $row['is_admin'] ? 'admin' : 'user';

            return ['user' => $row];

        } catch (Exception $e) {
            http_response_code(500);
            return ['message' => 'Error al obtener perfil'];
        }
    }

    public function updateProfile($user)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        try {
            $db = Database::connect();

            $stmt = $db->prepare("
                UPDATE users
                SET name=?, email=?, address=?, phone=?
                WHERE id=?
            ");

            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['address'],
                $data['phone'],
                $user->id
            ]);

            return ['message' => 'Perfil actualizado'];

        } catch (Exception $e) {
            http_response_code(500);
            return ['message' => 'Error al actualizar perfil'];
        }
    }
}
