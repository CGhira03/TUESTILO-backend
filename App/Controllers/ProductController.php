<?php

namespace App\Controllers;

use App\Config\Database;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\UploadMiddleware;
use PDO;
use Exception;

class ProductController
{
    /* ===========================
       NORMALIZAR IMAGEN
    ============================ */
    private function withImageUrl(array $product): array
    {
        if (!empty($product['image_url']) && !str_starts_with($product['image_url'], 'http')) {
            $product['image_url'] =
                'https://tuestilo.website/backend/public' . $product['image_url'];
        }

        return $product;
    }

    /* ===========================
       LISTAR PRODUCTOS (PÚBLICO)
    ============================ */
    public function getAll()
    {
        $page  = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(1, (int) ($_GET['limit'] ?? 50));
        $offset = ($page - 1) * $limit;

        $search   = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');

        $where  = [];
        $values = [];

        if ($search !== '') {
            $where[] = "name LIKE ?";
            $values[] = "%{$search}%";
        }

        if ($category !== '') {
            $where[] = "category = ?";
            $values[] = $category;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $db = Database::connect();

            $stmt = $db->prepare("
                SELECT *
                FROM products
                $whereSql
                ORDER BY id DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($values);

            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $products = array_map([$this, 'withImageUrl'], $products);

            $countStmt = $db->prepare("SELECT COUNT(*) FROM products $whereSql");
            $countStmt->execute($values);
            $total = (int) $countStmt->fetchColumn();

            return [
                'products'    => $products,
                'total'       => $total,
                'totalPages'  => ceil($total / $limit),
                'currentPage' => $page
            ];

        } catch (Exception $e) {
            http_response_code(500);
            return [
                'message' => 'Error al obtener productos',
                'error'   => $e->getMessage()
            ];
        }
    }

    /* ===========================
       VER PRODUCTO
    ============================ */
    public function getOne($id)
    {
        try {
            $db = Database::connect();

            $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                http_response_code(404);
                return ['message' => 'Producto no encontrado'];
            }

            return $this->withImageUrl($product);

        } catch (Exception $e) {
            http_response_code(500);
            return [
                'message' => 'Error al obtener producto',
                'error' => $e->getMessage()
            ];
        }
    }

    /* ===========================
       CREAR PRODUCTO (ADMIN)
    ============================ */
    public function create()
    {
        AuthMiddleware::verifyAdmin();

        // 🔍 DEBUG CRÍTICO (borralo luego si querés)
        if (empty($_POST)) {
            http_response_code(400);
            return [
                'message' => 'POST vacío',
                'debug' => [
                    '_POST' => $_POST,
                    '_FILES' => $_FILES
                ]
            ];
        }

        if (
            empty($_POST['name']) ||
            !isset($_POST['price']) ||
            empty($_POST['category'])
        ) {
            http_response_code(400);
            return [
                'message' => 'Faltan campos obligatorios',
                'received' => $_POST
            ];
        }

        try {
            $db = Database::connect();

            // 🔑 Código único
            do {
                $code = strtoupper(bin2hex(random_bytes(4)));
                $check = $db->prepare("SELECT COUNT(*) FROM products WHERE code = ?");
                $check->execute([$code]);
            } while ($check->fetchColumn() > 0);

            // 🖼 Imagen opcional
            $imageUrl = null;
            if (!empty($_FILES['image']['name'])) {
                $imageUrl = UploadMiddleware::uploadImage('image');
            }

            $stmt = $db->prepare("
                INSERT INTO products
                (name, description, price, category, sizes, image_url, code)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                trim($_POST['name']),
                trim($_POST['description'] ?? ''),
                (float) $_POST['price'],
                trim($_POST['category']),
                trim($_POST['sizes'] ?? ''),
                $imageUrl,
                $code
            ]);

            http_response_code(201);

            return [
                'message' => 'Producto creado correctamente',
                'product' => [
                    'id' => $db->lastInsertId(),
                    'name' => $_POST['name'],
                    'code' => $code
                ]
            ];

        } catch (Exception $e) {
            http_response_code(500);
            return [
                'message' => 'Error al crear producto',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // ⚠️ SOLO DEV
            ];
        }
    }

    /* ===========================
       ACTUALIZAR PRODUCTO
    ============================ */
    public function update($id)
    {
        AuthMiddleware::verifyAdmin();

        try {
            $db = Database::connect();

            // 🔎 Verificar que el producto exista
            $check = $db->prepare("SELECT id FROM products WHERE id = ?");
            $check->execute([$id]);

            if (!$check->fetch()) {
                http_response_code(404);
                return ['message' => 'Producto no encontrado'];
            }

            // 🧩 Construir UPDATE dinámico
            $sql = "
                UPDATE products
                SET name = ?, description = ?, price = ?, category = ?, sizes = ?
            ";

            $params = [
                trim($_POST['name'] ?? ''),
                trim($_POST['description'] ?? ''),
                (float) ($_POST['price'] ?? 0),
                trim($_POST['category'] ?? ''),
                trim($_POST['sizes'] ?? '')
            ];

            // 🖼 Imagen opcional
            if (!empty($_FILES['image']['name'])) {
                $imageUrl = UploadMiddleware::uploadImage('image');
                $sql .= ", image_url = ?";
                $params[] = $imageUrl;
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // ✅ NO usamos rowCount como error
            return [
                'message' => 'Producto actualizado correctamente',
                'updated' => true
            ];

        } catch (Exception $e) {
            http_response_code(500);
            return [
                'message' => 'Error al actualizar producto',
                'error' => $e->getMessage()
            ];
        }
    }

    /* ===========================
       ELIMINAR PRODUCTO
    ============================ */
    public function remove($id)
    {
        AuthMiddleware::verifyAdmin();

        try {
            $db = Database::connect();

            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);

            if (!$stmt->rowCount()) {
                http_response_code(404);
                return ['message' => 'Producto no encontrado'];
            }

            return ['message' => 'Producto eliminado correctamente'];

        } catch (Exception $e) {
            http_response_code(500);
            return [
                'message' => 'Error al eliminar producto',
                'error' => $e->getMessage()
            ];
        }
    }
}
