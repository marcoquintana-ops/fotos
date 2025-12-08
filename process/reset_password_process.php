<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    die("NO HAY CONEXIÓN A LA BASE DE DATOS");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../public/RestablecerContrasena.php");
    exit;
}

// Datos del form
$email = trim($_POST['email'] ?? '');
$pass1 = $_POST['password'] ?? '';
$pass2 = $_POST['confirm_password'] ?? '';

if ($email === '' || $pass1 === '' || $pass2 === '') {
    $_SESSION['reset_error'] = "Debe completar todos los campos.";
    header("Location: ../public/RestablecerContrasena.php");
    exit;
}

if ($pass1 !== $pass2) {
    $_SESSION['reset_error'] = "Las contraseñas no coinciden.";
    header("Location: ../public/RestablecerContrasena.php");
    exit;
}

// ======================================================
//         LLAMAR SP PARA BUSCAR USUARIO POR EMAIL
// ======================================================
$stmt = $conn->prepare("CALL sp_buscar_usuario_por_email(?)");
$stmt->bind_param("s", $email);

if (!$stmt->execute()) {
    $_SESSION['reset_error'] = "Error del servidor.";
    header("Location: ../public/RestablecerContrasena.php");
    exit;
}

$admin = $stmt->get_result()->fetch_assoc(); // primer resultset
$stmt->next_result();
$fam = $stmt->get_result()->fetch_assoc(); // segundo resultset
$stmt->close();

// ======================================================
//     VALIDAR SI EXISTE EN ADMIN O FAMILIA
// ======================================================
$tipo = null;

if ($admin && $admin['id']) {
    $tipo = 'admin';
    $estado = $admin['estado'];
} elseif ($fam && $fam['id']) {
    $tipo = 'familia';
    $estado = $fam['estado'];
} else {
    $_SESSION['reset_error'] = "El correo no está registrado. Debe crear una nueva cuenta.";
    header("Location: ../public/RestablecerContrasena.php");
    exit;
}

// ======================================================
//     VALIDAR ESTADO ACTIVO
// ======================================================
if ($estado !== 'activo') {
    $_SESSION['reset_error'] = "El usuario está inactivo. No puede restablecer contraseña.";
    header("Location: ../public/RestablecerContrasena.php");
    exit;
}

// ======================================================
//     GENERAR HASH
// ======================================================
$hash = password_hash($pass1, PASSWORD_BCRYPT);

// ======================================================
//     LLAMAR SP PARA ACTUALIZAR CONTRASEÑA
// ======================================================
$stmt2 = $conn->prepare("CALL sp_reset_password(?,?,?)");
$stmt2->bind_param("sss", $email, $hash, $tipo);
$stmt2->execute();
$stmt2->close();

// ======================================================
//          OK FINAL
// ======================================================
$_SESSION['reset_ok'] = "¡Contraseña actualizada correctamente!";
header("Location: ../public/RestablecerContrasena.php");
exit;
?>
