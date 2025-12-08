<?php
// process/register_process.php
session_start();
require_once dirname(__DIR__)."/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/RegistrarUsuario.php");
    exit;
}

// ===============================
// CAPTURAR CAMPOS
// ===============================
$tipo_documento      = $_POST["tipoDocumento"] ?? "";
$numero_documento    = $_POST["numDocumento"] ?? "";
$nombres             = $_POST["nombre"] ?? "";
$apellidos           = $_POST["apellidos"] ?? "";
$email               = $_POST["correo"] ?? "";
$password            = $_POST["password"] ?? "";
$password2           = $_POST["password2"] ?? "";
$evento_id           = $_POST["evento"] ?? "";
$nombre_familiar     = $_POST["nombreFamiliar"] ?? "";
$apellidos_familiar  = $_POST["apellidosFamiliar"] ?? "";

// ===============================
// VALIDACIÓN BACK-END
// ===============================
if (
    $tipo_documento === "" ||
    $numero_documento === "" ||
    $nombres === "" ||
    $apellidos === "" ||
    $email === "" ||
    $password === "" ||
    $password2 === "" ||
    $evento_id === "" ||
    $nombre_familiar === "" ||
    $apellidos_familiar === ""
) {
    header("Location: ../public/RegistrarUsuario.php?error=1");
    exit;
}

// Contraseñas coinciden
if ($password !== $password2) {
    header("Location: ../public/RegistrarUsuario.php?error=2");
    exit;
}

$passHash = password_hash($password, PASSWORD_BCRYPT);

// ===============================
// LLAMAR AL SP
// ===============================
$stmt = $conn->prepare("CALL sp_familia_insertar(?,?,?,?,?,?,?,?,?)");

if (!$stmt) {
    error_log("SP ERROR: ".$conn->error);
    header("Location: ../public/RegistrarUsuario.php?error=3");
    exit;
}

$stmt->bind_param(
    "ssssssiss",
    $tipo_documento,
    $numero_documento,
    $nombres,
    $apellidos,
    $email,
    $passHash,
    $evento_id,
    $nombre_familiar,
    $apellidos_familiar
);

$stmt->execute();
$stmt->close();

$conn->next_result();

header("Location: ../public/RegistrarUsuario.php?ok=1");
exit;
