<?php
session_start();
$mensaje_error = $_SESSION['reset_error'] ?? "";
$mensaje_ok    = $_SESSION['reset_ok'] ?? "";
unset($_SESSION['reset_error'], $_SESSION['reset_ok']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Restablecer Contraseña</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --corporate-green: #008798;
        --corporate-green-dark: #006b75;
        --gray-bg: #F1F5F9;
        --white: #ffffff;
        --gray-200: #E5E7EB;
        --gray-600: #4B5563;
        --danger: #EF4444;
        --success: #10B981;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
    }

    body {
        background: var(--gray-bg);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
        min-height: 100vh;
    }

    .container {
        background: var(--white);
        width: 100%;
        max-width: 420px;
        padding: 32px;
        border-radius: 10px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }

    .title {
        text-align: center;
        font-size: 1.6rem;
        font-weight: 700;
        margin-bottom: 6px;
        color: var(--corporate-green);
    }

    .subtitle {
        text-align: center;
        font-size: 0.95rem;
        color: var(--gray-600);
        margin-bottom: 20px;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 0.9rem;
        margin-bottom: 16px;
        display: block;
    }

    .alert-error {
        background: #FEE2E2;
        border: 1px solid #FCA5A5;
        color: #B91C1C;
    }

    .alert-success {
        background: #D1FAE5;
        border: 1px solid #6EE7B7;
        color: #065F46;
    }

    .form-group {
        margin-bottom: 18px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        color: var(--gray-600);
        font-size: 0.9rem;
        font-weight: 500;
    }

    .input {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        font-size: 0.95rem;
    }

    .input:focus {
        outline: none;
        border-color: var(--corporate-green);
        box-shadow: 0 0 0 2px rgba(0,135,152,0.2);
    }

    .btn {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        font-size: 1rem;
        cursor: pointer;
        margin-top: 8px;
        border: none;
        font-weight: 600;
        transition: 0.25s;
    }

    .btn-primary {
        background: var(--corporate-green);
        color: var(--white);
    }

    .btn-primary:hover {
        background: var(--corporate-green-dark);
    }

    .btn-secondary {
        background: #E2E8F0;
        color: #1E293B;
    }

    .btn-secondary:hover {
        background: #CBD5E1;
    }

    .link-login {
        margin-top: 14px;
        text-align: center;
    }

    .link-login a {
        color: var(--corporate-green);
        text-decoration: none;
        font-weight: 500;
    }

    .link-login a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<div class="container">

    <h2 class="title">Restablecer Contraseña</h2>
    <p class="subtitle">Ingrese su correo y defina una nueva contraseña.</p>

    <!-- ALERTAS -->
    <?php if ($mensaje_error): ?>
        <div class="alert alert-error"><?= $mensaje_error ?></div>
    <?php endif; ?>

    <?php if ($mensaje_ok): ?>
        <div class="alert alert-success"><?= $mensaje_ok ?></div>
    <?php endif; ?>

    <form action="../process/reset_password_process.php" method="POST">

        <div class="form-group">
            <label>Correo Electrónico</label>
            <input type="email" name="email" class="input" placeholder="correo@ejemplo.com" required>
        </div>

        <div class="form-group">
            <label>Nueva Contraseña</label>
            <input type="password" name="password" class="input" placeholder="Nueva contraseña" required>
        </div>

        <div class="form-group">
            <label>Confirmar Nueva Contraseña</label>
            <input type="password" name="confirm_password" class="input" placeholder="Confirmar contraseña" required>
        </div>

        <button type="submit" class="btn btn-primary">Restablecer Contraseña</button>

        <button type="button" onclick="window.location.href='Login.php'" class="btn btn-secondary">
            Regresar al Login
        </button>

    </form>

    <div class="link-login">
        <a href="Login.php">¿Recordaste tu contraseña? Inicia sesión aquí</a>
    </div>

</div>

</body>
</html>
