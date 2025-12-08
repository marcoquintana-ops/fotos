<?php
// config/db.php - archivo obligatorio que define $conn
// Ajusta credenciales si es necesario.

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';        // si tienes contraseña, ponla aquí
$DB_NAME = 'vyr_producciones';
$DB_CHAR = 'utf8mb4';

// Crear la conexión mysqli y forzar set_charset
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_errno) {
    // esto aparecerá en logs y detiene ejecución para evitar continuar sin DB
    error_log("DB connect error ({$conn->connect_errno}): " . $conn->connect_error);
    die("ERROR: No se pudo conectar a la base de datos. Revisa config/db.php");
}

if (! $conn->set_charset($DB_CHAR) ) {
    error_log("Warning: no se pudo setear charset {$DB_CHAR}: " . $conn->error);
}
