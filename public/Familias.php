<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/helpers/crypto.php';

// proteger acceso
if (!isset($_SESSION['admin_id']) || ($_SESSION['tipo'] ?? '') !== 'admin') {
    header("Location: Login.php");
    exit;
}

// leer token evento
$tkn = $_GET['tkn'] ?? '';
$evento_id = ($tkn) ? decrypt_id($tkn) : false;

if (!$evento_id || !is_numeric($evento_id)) {
    echo "<h3 style='margin:40px;text-align:center;color:#b91c1c;font-family:Arial;'>⚠ Ocurrió un problema.<br>El evento enviado no es válido.</h3>";
    exit;
}
$evento_id = intval($evento_id);

// obtener evento
$stmt = $conn->prepare("CALL sp_obtener_evento(?)");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$res = $stmt->get_result();
$evento = $res->fetch_assoc();
$res->free();
$stmt->close();
if ($conn->more_results()) $conn->next_result();

if (!$evento) {
    echo "<h3 style='margin:40px;text-align:center;color:#b91c1c;font-family:Arial;'>❌ No se encontró información del evento.</h3>";
    exit;
}

$adminNombre = $_SESSION['admin_nombre'] ?? 'Administrador';
$tkn_evento = encrypt_id($evento_id);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<title>Familias - VyR Producciones</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
    body { background:#f4f8f9; }
    .navbar { background:#008798 !important; }
    .btn-primary { background:#008798 !important; border-color:#008798 !important; }
    .btn-primary:hover { background:#007b88 !important; }
    .btn-outline-success { border-color:#008798 !important; color:#008798 !important; }
    .btn-outline-success:hover { background:#008798 !important; color:white !important; }

    .table thead { background:#008798; color:white; }
    .col-acciones { width:230px; text-align:center; }

    .acciones i { font-size:18px; cursor:pointer; margin:0 6px; }
    .acciones i:hover { color:#007b88; }

    .modal-header { background:#008798; color:white; }
</style>
</head>
<body>

<nav class="navbar navbar-dark px-4 py-3">
    <span class="navbar-brand mb-0 h1">VyR Producciones</span>

    <div class="text-white">
        Bienvenido, <strong><?php echo htmlspecialchars($adminNombre) ?></strong>
        &nbsp; | &nbsp;
        <a href="Logout.php" class="text-white text-decoration-none">Cerrar sesión</a>
    </div>
</nav>

<div class="container mt-4">

    <button onclick="window.location.href='Eventos.php'" class="btn btn-secondary mb-3">
        ⬅ Regresar a Eventos
    </button>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h3>Gestión de Familias</h3>
            <div style="color:#374151;font-size:0.95rem;">
                Evento: <strong><?php echo htmlspecialchars($evento['nombre']) ?></strong>
            </div>
        </div>

        <div>
            <a href="RegistrarFamilia.php?tkn=<?php echo urlencode($tkn_evento); ?>"
               class="btn btn-outline-success me-2">+ Nuevo Usuario (Familia)</a>

            <button class="btn btn-primary" id="btnRefresh">Refrescar</button>
        </div>
    </div>

    <div class="d-flex" style="gap:10px;">
        <input id="textoBusq" class="form-control" placeholder="Buscar por nombre o apellido">
        <button class="btn btn-secondary" id="btnBuscar">Buscar</button>
    </div>

    <div class="card p-3 mt-3">
        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="tblFamilias">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Nombre completo</th>
                        <th>Email</th>
                        <th>Nombre Familiar</th>
                        <th>Estado</th>
                        <th class="col-acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody id="bodyFamilias">
                    <tr><td colspan="6" class="text-center">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL RESET PASSWORD -->
<div class="modal fade" id="modalReset" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="formReset" method="POST" action="../process/familias_process.php">
      <div class="modal-header">
        <h5 class="modal-title">Resetear contraseña</h5>
        <button type="button" class="btn-close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="tkn_id" id="reset_tkn_id">

        <div class="mb-3">
            <label class="form-label">Nueva contraseña</label>
            <input type="password" class="form-control" id="reset_pass" name="new_password">
            <small class="text-danger" id="reset_err"></small>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function escapeHtml(t) {
    return (t || '').replace(/[&<>"']/g,
        m => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[m]));
}

function cargarFamilias() {
    const fd = new FormData();
    fd.append("action", "list");
    fd.append("tkn_evento", "<?php echo $tkn_evento ?>");
    fd.append("texto", document.getElementById("textoBusq").value.trim());

    fetch("../process/familias_process.php", { method: "POST", body: fd })
        .then(r => r.json())
        .then(data => renderTabla(data))
        .catch(() => {
            document.getElementById("bodyFamilias").innerHTML =
                `<tr><td colspan="6" class="text-center text-danger">Error en el servidor</td></tr>`;
        });
}

function renderTabla(list) {
    const tbody = document.getElementById("bodyFamilias");

    if (!Array.isArray(list) || list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center">Sin registros</td></tr>`;
        return;
    }

    let html = "";
    list.forEach(f => {
        html += `
        <tr>
            <td>${f.tipo_documento.toUpperCase()} ${f.numero_documento}</td>
            <td>${escapeHtml(f.nombre_completo)}</td>
            <td>${escapeHtml(f.email)}</td>
            <td>${escapeHtml(f.nombre_familiar)} ${escapeHtml(f.apellidos_Familiar)}</td>
            <td>${f.estado}</td>

            <td class="acciones">
                <i class="bi bi-pencil-square text-warning" title="Editar"
                   onclick="window.location='EditarFamilia.php?tkn=${encodeURIComponent(f.token_id)}'"></i>

                <i class="bi bi-key-fill text-info" title="Resetear contraseña"
                   onclick="abrirReset('${f.token_id}')"></i>

                <i class="bi bi-image text-primary" title="Ver lienzo"
                   onclick="window.location='EditorLienzo.php?usuario=${encodeURIComponent(f.token_id)}'"></i>

                <i class="bi bi-trash text-danger" title="Eliminar"
                   onclick="eliminarFamilia('${f.token_id}')"></i>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

function abrirReset(tkn) {
    document.getElementById("reset_tkn_id").value = tkn;
    document.getElementById("reset_pass").value = "";
    new bootstrap.Modal(document.getElementById("modalReset")).show();
}

function eliminarFamilia(id) {
    if (!confirm("¿Eliminar familia?")) return;

    const fd = new FormData();
    fd.append("action", "delete");
    fd.append("tkn_id", id);

    fetch("../process/familias_process.php", { method: "POST", body: fd })
        .then(r => r.json())
        .then(j => {
            if (j.ok) cargarFamilias();
            else alert(j.message);
        });
}

document.getElementById("btnBuscar").onclick = cargarFamilias;
document.getElementById("btnRefresh").onclick = () => {
    document.getElementById("textoBusq").value = "";
    cargarFamilias();
};

document.addEventListener("DOMContentLoaded", cargarFamilias);
</script>

</body>
</html>
