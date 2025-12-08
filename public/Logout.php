<?php
// public/Logout.php
session_start();

// 1) ELIMINAR TODAS LAS VARIABLES
$_SESSION = [];

// 2) INVALIDAR COOKIE DE SESIÓN SI EXISTE
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3) DESTRUIR SESIÓN
session_destroy();

// 4) PREVENIR BOTÓN ATRÁS
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 5) REDIRECCIONAR AL LOGIN
header("Location: Login.php");
exit;
