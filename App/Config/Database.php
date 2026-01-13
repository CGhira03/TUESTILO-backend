<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {

    public static function connect() {

        $config = require __DIR__ . '/config.php';

        $db = $config['db'];

        $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4";

        try {
            return new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                "error" => "Error al conectar a MySQL",
                "details" => $e->getMessage()
            ]));
        }
    }
}
