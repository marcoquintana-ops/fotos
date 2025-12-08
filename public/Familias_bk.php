<?php
// public/Familias.php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/helpers/crypto.php';

// protección de acceso
if (!isset($_SESSION['admin_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: Login.php');
    exit;
}

// recibir token del evento (ofuscado)
$tkn = $_GET['tkn'] ?? '';
$evento_id = false;
if (empty($tkn) || ($evento_id = decrypt_id($tkn)) === false || !is_numeric($evento_id)) {
    // acceso inválido
    http_response_code(403);
    echo "Acceso denegado.";
    exit;
}
$evento_id = intval($evento_id);

// Obtener datos del evento por SP
$evento = null;
$stmt = $conn->prepare("CALL sp_obtener_evento(?)");
if ($stmt) {
    $stmt->bind_param("i", $evento_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        $evento = $res->fetch_assoc();
        $res->free();
    }
    if ($conn->more_results()) $conn->next_result();
    $stmt->close();
}
if (!$evento) {
    http_response_code(404);
    echo "Evento no encontrado.";
    exit;
}

$adminNombre = $_SESSION['admin_nombre'] ?? 'Administrador';
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
    .navbar { background-color: #008798 !important; }
    .btn-primary { background-color:#008798 !important; border-color:#008798 !important; }
    .btn-primary:hover { background-color:#007b88 !important; }
    .table thead { background:#008798; color:#fff; }
    .col-acciones { width:220px; text-align:center; }
    .acciones i { font-size:18px; cursor:pointer; margin:0 6px; }
    .acciones i:hover { color:#007b88; }
    .search-row { gap:10px; align-items:center; margin-bottom:16px; }
    .card { border-radius:10px; }
    .modal-header { background:#008798; color:#fff; }
    small.invalid { color:#d9534f; display:block; margin-top:4px; }
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

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h3>Gestión de Familias</h3>
            <div style="color:#374151; font-size:0.95rem;">Evento: <strong><?php echo htmlspecialchars($evento['nombre']); ?></strong></div>
        </div>

        <div>
            <?php
                // crear token para la creación con el evento
                $tkn_evento = encrypt_id($evento_id);
            ?>
            <a href="RegistrarFamilia.php?tkn=<?php echo urlencode($tkn_evento); ?>" class="btn btn-outline-success me-2">+ Nuevo Usuario (Familia)</a>
            <button class="btn btn-primary" id="btnRefresh">Refrescar</button>
        </div>
    </div>

    <!-- filtros -->
    <div class="d-flex search-row">
        <input id="textoBusq" class="form-control" placeholder="Buscar por nombre o apellido" />
        <button class="btn btn-secondary" id="btnBuscar">Buscar</button>
    </div>

    <div class="card p-3">
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

<!-- Modal Resetear contraseña (admin) -->
<div class="modal fade" id="modalReset" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="formReset" method="POST" action="../process/familias_process.php">
      <div class="modal-header">
        <h5 class="modal-title">Resetear contraseña (Familia)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="tkn_id" id="reset_tkn_id">
        <div class="mb-3">
            <label class="form-label">Nueva contraseña</label>
            <input type="password" class="form-control" name="new_password" id="reset_new_password">
            <small class="invalid" id="err_reset_new_password"></small>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Resetear</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// render table
function renderTable(data) {
    const tbody = document.getElementById('bodyFamilias');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay registros</td></tr>';
        return;
    }
    let html = '';
    data.forEach(row => {
        // crear tokens para acciones
        // note: tokens are provided by backend in list response below (if you prefer)
        const tknId = row.token_id || '';
        const editUrl = 'EditarFamilia.php?tkn=' + encodeURIComponent(row.token_id);
        const viewLienzo = 'EditorLienzo.php?usuario=' + encodeURIComponent(row.token_id); // you may change
        html += `<tr>
            <td>${row.tipo_documento.toUpperCase()} ${row.numero_documento}</td>
            <td>${escapeHtml(row.nombre_completo)}</td>
            <td>${escapeHtml(row.email)}</td>
            <td>${escapeHtml(row.nombre_familiar||'')} ${escapeHtml(row.apellidos_Familiar||'')}</td>
            <td>${row.estado}</td>
            <td class="acciones">
                <i class="bi bi-pencil-square text-warning" title="Editar" onclick='window.location="${editUrl}"'></i>
                <i class="bi bi-key-fill text-info" title="Resetear contraseña" onclick='abrirReset("${row.token_id}")'></i>
                <i class="bi bi-image text-primary" title="Ver lienzo" onclick='window.location="${viewLienzo}"'></i>
                <i class="bi bi-trash text-danger" title="Eliminar" onclick='eliminarFamilia("${row.token_id}")'></i>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function escapeHtml(s) {
    if (!s) return '';
    return (''+s).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; });
}

// cargar lista inicial (solo evento_id)
document.addEventListener('DOMContentLoaded', function(){
    const eventToken = "<?php echo urlencode($tkn_evento); ?>";
    cargarFamilias(eventToken, '');
    document.getElementById('btnBuscar').addEventListener('click', function(){
        const texto = document.getElementById('textoBusq').value.trim();
        cargarFamilias(eventToken, texto);
    });
    document.getElementById('btnRefresh').addEventListener('click', function(){
        document.getElementById('textoBusq').value = '';
        cargarFamilias(eventToken, '');
    });

    // submit modal reset
    document.getElementById('formReset').addEventListener('submit', function(e){
        e.preventDefault();
        const fd = new FormData(this);
        fetch('../process/familias_process.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(j => {
            if (j.ok) {
                var modal = bootstrap.Modal.getInstance(document.getElementById('modalReset'));
                if (modal) modal.hide();
                alert('Contraseña reseteada correctamente');
            } else {
                alert(j.message || 'No se pudo resetear');
            }
        }).catch(()=>alert('Error servidor'));
    });
});

// cargar familias desde process (AJAX) - envia token del evento
function cargarFamilias(tknEvento, texto) {
    const fd = new FormData();
    fd.append('action','list');
    fd.append('tkn_evento', tknEvento);
    fd.append('texto', texto);
    fetch('../process/familias_process.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
        // cada fila debe traer token_id (ofuscado) generado por backend
        renderTable(data);
    })
    .catch(err => {
        console.error(err);
        document.getElementById('bodyFamilias').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error cargando datos</td></tr>';
    });
}

function abrirReset(tkn_id) {
    document.getElementById('reset_tkn_id').value = tkn_id;
    document.getElementById('reset_new_password').value = '';
    document.getElementById('err_reset_new_password').textContent = '';
    new bootstrap.Modal(document.getElementById('modalReset')).show();
}

function eliminarFamilia(tkn_id) {
    if (!confirm('¿Eliminar familia? Esta acción es irreversible.')) return;
    const fd = new FormData();
    fd.append('action','delete');
    fd.append('tkn_id', tkn_id);
    fetch('../process/familias_process.php', { method:'POST', body:fd })
      .then(r=>r.json())
      .then(j=>{
          if (j.ok) {
              // refresh
              const eventToken = "<?php echo urlencode($tkn_evento); ?>";
              cargarFamilias(eventToken, document.getElementById('textoBusq').value.trim());
          } else alert(j.message || 'No se pudo eliminar');
      }).catch(()=>alert('Error servidor'));
}
</script>

</body>
</html>
