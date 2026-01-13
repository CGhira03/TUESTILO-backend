<?php
namespace App\Middlewares;

class UploadMiddleware
{
    public static function uploadImage($inputName)
    {
        $uploadDir = __DIR__ . '/../../public/uploads/';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!isset($_FILES[$inputName])) {
            return null; // no se subió imagen
        }

        $file = $_FILES[$inputName];

        // Validar errores generales
        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new \Exception("Error al subir archivo");
        }

        // Validar tipo
        $allowed = ["image/jpeg", "image/jpg", "image/png", "image/gif"];

        if (!in_array($file["type"], $allowed)) {
            throw new \Exception("Solo se permiten imágenes (jpeg, jpg, png, gif)");
        }

        // Validar tamaño (5MB)
        if ($file["size"] > 5 * 1024 * 1024) {
            throw new \Exception("El archivo supera el límite de 5MB");
        }

        // Nombre único
        $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
        $newName = time() . "-" . rand(100000, 999999) . "." . $ext;

        $destination = $uploadDir . $newName;

        if (!move_uploaded_file($file["tmp_name"], $destination)) {
            throw new \Exception("Error al guardar archivo");
        }

        return "/uploads/" . $newName;
    }
}
