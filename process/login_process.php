<?php
// process/login_process.php
// Login para administradores y familias.
// Usa SPs sp_login_admin y sp_login_familia
// Maneja intentos y bloqueo mediante SPs (sp_incrementar_intentos, sp_reset_intentos, sp_bloquear_admin, sp_bloquear_familia)
// Requiere /config/db.php

session_start();

require_once dirname(__DIR__) . '/config/db.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    die("NO HAY CONEXIÓN A LA BASE DE DATOS");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/Login.php');
    exit;
}

$correo = strtolower(trim($_POST['correo'] ?? ''));
$passwordIngresado = $_POST['password'] ?? '';

if ($correo === '' || $passwordIngresado === '') {
    header('Location: ../public/Login.php?error=1');
    exit;
}

// Helpers
function ejecutar_sp_result($conn, $sp, $param) {
    $stmt = $conn->prepare("CALL {$sp}(?)");
    if (!$stmt) {
        error_log("Prepare falló: " . $conn->error . " -- SP: ".$sp);
        return false;
    }
    $stmt->bind_param("s", $param);
    if (!$stmt->execute()) {
        error_log("Execute falló: " . $stmt->error . " -- SP: ".$sp);
        $stmt->close();
        if ($conn->more_results()) $conn->next_result();
        return false;
    }
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if ($conn->more_results()) $conn->next_result();
    return $row;
}

// validar password (bcrypt vs plano)
function validar_password($input, $stored) {
    if ($stored === null) return false;
    if ((strpos($stored, '$2y$') === 0) || (strpos($stored, '$2a$') === 0)) {
        return password_verify($input, $stored);
    }
    // fallback: igualdad simple (no recomendado pero soportado)
    return hash_equals($stored, $input);
}

// ---------------------
// 1) Intentar ADMIN
// ---------------------
$admin = ejecutar_sp_result($conn, 'sp_login_admin', $correo);

if ($admin) {
    // verificar estado
    if (isset($admin['estado']) && $admin['estado'] !== 'activo') {
        // usuario inactivo
        header('Location: ../public/Login.php?inactive=1');
        exit;
    }

    $pass_field = null;
    // intentar encontrar campo password (nombres distintos)
    foreach (['password','pass','passwd','contrasena'] as $c) {
        if (isset($admin[$c])) { $pass_field = $c; break; }
    }
    // default field
    if ($pass_field === null && isset($admin['password'])) $pass_field = 'password';

    if ($pass_field && validar_password($passwordIngresado, $admin[$pass_field])) {
        // login exitoso admin
        session_regenerate_id(true);
        $_SESSION['tipo'] = 'admin';
        $_SESSION['admin_id'] = $admin['id'] ?? null;
        $_SESSION['admin_nombre'] = $admin['nombre'] ?? ($admin['name'] ?? '');

        // resetear intentos via SP (si existe sp_reset_intentos)
        $stmt = $conn->prepare("CALL sp_reset_intentos(?, 1)");
        if ($stmt) { $stmt->bind_param("s", $correo); $stmt->execute(); $stmt->close(); if ($conn->more_results()) $conn->next_result(); }

        header('Location: ../public/Eventos.php');
        exit;
    }
    // si pasa por aquí la contraseña fue incorrecta -> incrementar intentos más abajo
}

// ---------------------
// 2) Intentar FAMILIA
// ---------------------
$fam = ejecutar_sp_result($conn, 'sp_login_familia', $correo);

if ($fam) {
    if (isset($fam['estado']) && $fam['estado'] !== 'activo') {
        header('Location: ../public/Login.php?inactive=1');
        exit;
    }

    $pass_field = null;
    foreach (['password','pass','passwd','contrasena'] as $c) {
        if (isset($fam[$c])) { $pass_field = $c; break; }
    }
    if ($pass_field === null && isset($fam['password'])) $pass_field = 'password';

    if ($pass_field && validar_password($passwordIngresado, $fam[$pass_field])) {
        // login exitoso familia
        session_regenerate_id(true);
        $_SESSION['tipo'] = 'familia';
        $_SESSION['familia_id'] = $fam['id'] ?? null;
        // elegir nombre_familiar o nombres
        $_SESSION['nombre_familiar'] = $fam['nombre_familiar'] ?? ($fam['nombres'] ?? '');

        // resetear intentos familia
        $stmt = $conn->prepare("CALL sp_reset_intentos(?, 0)");
        if ($stmt) { $stmt->bind_param("s", $correo); $stmt->execute(); $stmt->close(); if ($conn->more_results()) $conn->next_result(); }

        header('Location: ../public/EditorLienzo.php');
        exit;
    }
    // else -> incorrect password, incrementar intentos más abajo
}

// ---------------------
// Si llegamos aquí: credenciales inválidas
// Incrementar intentos en ambas tablas (SPs)
// ---------------------
$stmt = $conn->prepare("CALL sp_incrementar_intentos(?, 1)");
if ($stmt) { $stmt->bind_param("s", $correo); $stmt->execute(); $stmt->close(); if ($conn->more_results()) $conn->next_result(); }

$stmt = $conn->prepare("CALL sp_incrementar_intentos(?, 0)");
if ($stmt) { $stmt->bind_param("s", $correo); $stmt->execute(); $stmt->close(); if ($conn->more_results()) $conn->next_result(); }

// Comprobar intentos y bloquear si corresponde (leer registros de nuevo)
$admin2 = ejecutar_sp_result($conn, 'sp_login_admin', $correo);
if ($admin2 && isset($admin2['intentos']) && (int)$admin2['intentos'] >= 5) {
    $stmt = $conn->prepare("CALL sp_bloquear_admin(?)");
    if ($stmt) { $stmt->bind_param("s", $correo); $stmt->execute(); $stmt->close(); if ($conn->more_results()) $conn->next_result(); }
}

$fam2 = ejecutar_sp_result($conn, 'sp_login_familia', $correo);
if ($fam2 && isset($fam2['intentos']) && (int)$fam2['intentos'] >= 5) {
    $stmt = $conn->prepare("CALL sp_bloquear_familia(?)");
    if ($stmt) { $stmt->bind_param("s", $correo); $stmt->execute(); $stmt->close(); if ($conn->more_results()) $conn->next_result(); }
}

// Redirigir con error genérico
header('Location: ../public/Login.php?error=1');
exit;
