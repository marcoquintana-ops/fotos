<?php
session_start();

// No permitir volver después de logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Si ya está logeado como admin → entrar directo
if (isset($_SESSION["admin_id"]) && ($_SESSION["tipo"] ?? '') === "admin") {
    header("Location: Eventos.php");
    exit;
}

// Si es familia → redirigir a su editor
if (isset($_SESSION["familia_id"]) && ($_SESSION["tipo"] ?? '') === "familia") {
    header("Location: EditorLienzo.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>VyR Producciones - Iniciar Sesión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #e9f3f4;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            border: 2px solid #00879833;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .titulo {
            color: #008798;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
        }

        .btn-primary {
            background-color: #008798 !important;
            border-color: #008798 !important;
        }
        .btn-primary:hover {
            background-color: #006f79 !important;
        }

        .link-op {
            color: #008798;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .link-op:hover {
            text-decoration: underline;
        }

        .invalid-feedback { display:none; }

    </style>
</head>

<body>

<div class="login-card">

    <h3 class="titulo">VyR Producciones</h3>

    <form id="formLogin" action="../process/login_process.php" method="POST" novalidate>

        <div class="mb-3">
            <label class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" name="correo" id="correo" required>
            <div class="invalid-feedback" id="correoError">Ingrese un correo válido.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" class="form-control" name="password" id="password" required>
            <div class="invalid-feedback" id="passError">La contraseña es obligatoria.</div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Ingresar</button>

        <div class="text-center mt-3">
            <a href="RestablecerContrasena.php" class="link-op">¿Olvidó su contraseña?</a><br>
            <a href="RegistrarUsuario.php" class="link-op">Registrar nuevo usuario</a>
        </div>

    </form>
</div>

<!-- MODAL -->
<div class="modal fade" id="modalError" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Error</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Mensaje aquí…</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('formLogin').addEventListener('submit', function (e) {
    let ok = true;

    const correo = document.getElementById('correo');
    const pass = document.getElementById('password');

    if (correo.value.trim() === '') {
        document.getElementById('correoError').style.display = 'block';
        correo.classList.add('is-invalid');
        ok = false;
    } else {
        document.getElementById('correoError').style.display = 'none';
        correo.classList.remove('is-invalid');
    }

    if (pass.value.trim() === '') {
        document.getElementById('passError').style.display = 'block';
        pass.classList.add('is-invalid');
        ok = false;
    } else {
        document.getElementById('passError').style.display = 'none';
        pass.classList.remove('is-invalid');
    }

    if (!ok) e.preventDefault();
});

const params = new URLSearchParams(window.location.search);

if (params.has('error')) {
    const modal = new bootstrap.Modal(document.getElementById('modalError'));
    document.querySelector('#modalError .modal-title').textContent = 'Credenciales incorrectas';
    document.querySelector('#modalError .modal-body p').textContent =
        'Los datos ingresados son incorrectos. Verifique su correo y contraseña.';
    modal.show();
    history.replaceState(null, '', window.location.pathname);
}

if (params.has('inactive')) {
    const modal = new bootstrap.Modal(document.getElementById('modalError'));
    document.querySelector('#modalError .modal-title').textContent = 'Cuenta inactiva';
    document.querySelector('#modalError .modal-body p').textContent =
        'Su usuario se encuentra inactivo. Debe registrar otra cuenta o contactar con soporte.';
    modal.show();
    history.replaceState(null, '', window.location.pathname);
}
</script>

</body>
</html>
