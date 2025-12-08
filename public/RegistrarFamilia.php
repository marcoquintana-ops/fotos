<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/helpers/crypto.php';

// Solo admin
if (!isset($_SESSION['admin_id']) || ($_SESSION['tipo'] ?? '') !== 'admin') {
    header('Location: Login.php');
    exit;
}

// token de evento
$tkn = $_GET['tkn'] ?? '';
$evento_id = $tkn ? decrypt_id($tkn) : false;
if (!$evento_id || !is_numeric($evento_id)) {
    echo "<h3 style='margin:40px;text-align:center;color:#b91c1c;font-family:Arial;'>
            ⚠ El identificador del evento no es válido.
          </h3>";
    exit;
}
$evento_id = (int)$evento_id;

// obtener info del evento
$stmt = $conn->prepare("CALL sp_obtener_evento(?)");
if ($stmt) {
    $stmt->bind_param("i", $evento_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $evento = $res->fetch_assoc();
    $res->free();
    $stmt->close();
    if ($conn->more_results()) $conn->next_result();
}
if (empty($evento)) {
    echo "<h3 style='margin:40px;text-align:center;color:#b91c1c;font-family:Arial;'>
            ❌ No se encontró el evento.
          </h3>";
    exit;
}

$adminNombre = $_SESSION['admin_nombre'] ?? 'Administrador';
$tkn_evento = encrypt_id($evento_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar Familia - VyR Producciones</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body { background:#f4f8f9; }
    .navbar { background:#008798 !important; }
    .btn-primary { background:#008798 !important; border-color:#008798 !important; }
    .btn-primary:hover { background:#007b88 !important; }
    .btn-outline-secondary:hover { background:#e5e7eb !important; }

    .card {
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        border: 1px solid #e5e7eb;
    }
    .form-label { font-weight: 500; }
    .invalid-feedback { display:block; font-size:0.85rem; }
</style>
</head>
<body>

<nav class="navbar navbar-dark px-4 py-3">
    <span class="navbar-brand mb-0 h1">VyR Producciones</span>
    <div class="text-white">
        Bienvenido, <strong><?php echo htmlspecialchars($adminNombre); ?></strong>
        &nbsp; | &nbsp;
        <a href="Logout.php" class="text-white text-decoration-none">Cerrar sesión</a>
    </div>
</nav>

<div class="container mt-4 mb-4">

    <button class="btn btn-outline-secondary mb-3" onclick="window.location.href='Familias.php?tkn=<?php echo urlencode($tkn_evento); ?>'">
        ⬅ Volver a Familias
    </button>

    <div class="card p-4">
        <h4 class="mb-3">Registrar nuevo usuario (Familia)</h4>
        <p class="text-muted mb-4">
            Evento: <strong><?php echo htmlspecialchars($evento['nombre']); ?></strong>
        </p>

        <form id="frmFamilia" method="POST" action="../process/familias_process.php" novalidate>
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="tkn_evento" value="<?php echo htmlspecialchars($tkn_evento); ?>">

            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label">Tipo Documento</label>
                    <select name="tipo_documento" id="tipo_documento" class="form-select">
                        <option value="">Seleccione...</option>
                        <option value="dni">DNI</option>
                        <option value="ce">CE</option>
                    </select>
                    <div class="invalid-feedback" id="err_tipo_documento"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Número Documento</label>
                    <input type="text" name="numero_documento" id="numero_documento" class="form-control">
                    <div class="invalid-feedback" id="err_numero_documento"></div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label">Nombres</label>
                    <input type="text" name="nombres" id="nombres" class="form-control">
                    <div class="invalid-feedback" id="err_nombres"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Apellidos</label>
                    <input type="text" name="apellidos" id="apellidos" class="form-control">
                    <div class="invalid-feedback" id="err_apellidos"></div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" id="email" class="form-control">
                <div class="invalid-feedback" id="err_email"></div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label">Nombre Familiar</label>
                    <input type="text" name="nombre_familiar" id="nombre_familiar" class="form-control">
                    <div class="invalid-feedback" id="err_nombre_familiar"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Apellidos Familiar</label>
                    <input type="text" name="apellidos_familiar" id="apellidos_familiar" class="form-control">
                    <div class="invalid-feedback" id="err_apellidos_familiar"></div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Estado</label>
                <select name="estado" id="estado" class="form-select">
                    <option value="activo" selected>Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
                <div class="invalid-feedback" id="err_estado"></div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="button" class="btn btn-outline-secondary me-2"
                        onclick="window.location.href='Familias.php?tkn=<?php echo urlencode($tkn_evento); ?>'">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>

        </form>
    </div>
</div>

<script>
const form = document.getElementById('frmFamilia');

function setError(id, msg) {
    const el = document.getElementById(id);
    el.textContent = msg;
}
function clearErrors() {
    ['tipo_documento','numero_documento','nombres','apellidos','email',
     'nombre_familiar','apellidos_familiar','estado'].forEach(c => {
        const inp = document.getElementById(c);
        if (inp) inp.classList.remove('is-invalid');
    });
    ['err_tipo_documento','err_numero_documento','err_nombres','err_apellidos','err_email',
     'err_nombre_familiar','err_apellidos_familiar','err_estado'].forEach(id => {
        document.getElementById(id).textContent = '';
    });
}

form.addEventListener('submit', function(e) {
    clearErrors();
    let ok = true;

    function markInvalid(fieldId, errId, msg) {
        const f = document.getElementById(fieldId);
        f.classList.add('is-invalid');
        setError(errId, msg);
        ok = false;
    }

    if (document.getElementById('tipo_documento').value === '') {
        markInvalid('tipo_documento', 'err_tipo_documento', 'Seleccione tipo de documento.');
    }
    if (document.getElementById('numero_documento').value.trim() === '') {
        markInvalid('numero_documento', 'err_numero_documento', 'Ingrese número de documento.');
    }
    if (document.getElementById('nombres').value.trim() === '') {
        markInvalid('nombres', 'err_nombres', 'Ingrese nombres.');
    }
    if (document.getElementById('apellidos').value.trim() === '') {
        markInvalid('apellidos', 'err_apellidos', 'Ingrese apellidos.');
    }
    const email = document.getElementById('email');
    if (email.value.trim() === '') {
        markInvalid('email', 'err_email', 'Ingrese correo.');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
        markInvalid('email', 'err_email', 'Correo no válido.');
    }
    if (document.getElementById('nombre_familiar').value.trim() === '') {
        markInvalid('nombre_familiar', 'err_nombre_familiar', 'Ingrese nombre del familiar.');
    }
    if (document.getElementById('apellidos_familiar').value.trim() === '') {
        markInvalid('apellidos_familiar', 'err_apellidos_familiar', 'Ingrese apellidos del familiar.');
    }

    if (!ok) e.preventDefault();
});
</script>

</body>
</html>
