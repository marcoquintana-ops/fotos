<?php
session_start();

// No permitir volver con botón atrás
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/helpers/crypto.php"; // AES-256-CBC

// Validar sesión y rol
if (!isset($_SESSION["admin_id"]) || ($_SESSION["tipo"] ?? '') !== 'admin') {
    header("Location: Login.php");
    exit;
}

$adminNombre = $_SESSION["admin_nombre"] ?? "Administrador";

// ================================
// CARGAR EVENTOS
// ================================
$eventos = [];
$res = $conn->query("CALL sp_eventos_listar()");
if ($res) {
    while ($fila = $res->fetch_assoc()) {
        $eventos[] = $fila;
    }
    $res->free();
    if ($conn->more_results()) $conn->next_result();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eventos - VyR Producciones</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { background:#f4f8f9; }
        .navbar { background:#008798 !important; }
        .btn-primary { background:#008798 !important; border-color:#008798 !important; }
        .btn-primary:hover { background:#007b88 !important; }

        .table thead { background:#008798; color:#fff; }

        .col-fecha { width:110px; }
        .col-estado { width:100px; }
        .col-acciones { width:230px; text-align:center; }

        .acciones i {
            font-size:20px;
            cursor:pointer;
            margin:0 6px;
        }
        .acciones i:hover { color:#007b88; }

        .modal-header {
            background:#008798;
            color:#fff;
        }
        .modal-header .btn-close {
            filter: invert(1);
        }
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

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Gestión de Eventos</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNew">+ Nuevo evento</button>
    </div>

    <?php if (isset($_GET["ok"])): ?>
        <div class="alert alert-success">
            <?php
                if ($_GET["ok"] == 1) echo "Evento registrado correctamente.";
                if ($_GET["ok"] == 2) echo "Evento actualizado exitosamente.";
                if ($_GET["ok"] == 3) echo "Evento eliminado.";
            ?>
        </div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Nombre del Evento</th>
                <th class="col-fecha">Inicio</th>
                <th class="col-fecha">Fin</th>
                <th>Lugar</th>
                <th class="col-estado">Estado</th>
                <th class="col-acciones">Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($eventos)): ?>
                <tr>
                    <td colspan="6" class="text-center">No hay eventos registrados</td>
                </tr>
            <?php else: ?>
                <?php foreach ($eventos as $ev): ?>
                    <?php $tkn = encrypt_id($ev["id"]); ?>
                    <tr>
                        <td><?= htmlspecialchars($ev["nombre"]); ?></td>
                        <td><?= $ev["fecha_inicio"]; ?></td>
                        <td><?= $ev["fecha_fin"]; ?></td>
                        <td><?= htmlspecialchars($ev["lugar"]); ?></td>
                        <td><?= $ev["estado"]; ?></td>

                        <td class="acciones">

                            <!-- EDITAR -->
                            <i class="bi bi-pencil-square text-warning"
                               onclick='editar(<?= json_encode($ev); ?>)'
                               title="Editar"></i>

                            <!-- EXPORTAR -->
                            <i class="bi bi-image text-info"
                               onclick="exportarLienzos('<?= $tkn ?>')"
                               title="Exportar lienzos"></i>

                            <!-- FAMILIAS -->
                            <i class="bi bi-people-fill text-primary"
                               onclick="verFamilias('<?= $tkn ?>')"
                               title="Ver familias"></i>

                            <!-- ELIMINAR -->
                            <i class="bi bi-trash text-danger"
                               onclick="eliminarEvt('<?= $tkn ?>')"
                               title="Eliminar"></i>
                        </td>

                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</div>

<!-- MODAL NUEVO EVENTO -->
<div class="modal fade" id="modalNew" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="formNew" method="POST" action="../process/eventos_process.php" novalidate>

      <div class="modal-header">
        <h5 class="modal-title">Nuevo Evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <input type="hidden" name="action" value="create">
        <input type="hidden" name="tkn" value="">
        <input type="hidden" name="estado" id="hidden_estado_new">

        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input class="form-control" id="new_nombre" name="nombre">
            <div class="invalid-feedback" id="err_new_nombre"></div>
        </div>

        <div class="row">
            <div class="col">
                <label class="form-label">Fecha inicio</label>
                <input type="date" class="form-control" id="new_fecha_inicio" name="fecha_inicio">
                <div class="invalid-feedback" id="err_new_fecha_inicio"></div>
            </div>

            <div class="col">
                <label class="form-label">Fecha fin</label>
                <input type="date" class="form-control" id="new_fecha_fin" name="fecha_fin">
                <div class="invalid-feedback" id="err_new_fecha_fin"></div>
            </div>
        </div>

        <div class="mb-3 mt-3">
            <label class="form-label">Lugar</label>
            <input class="form-control" id="new_lugar" name="lugar">
            <div class="invalid-feedback" id="err_new_lugar"></div>
        </div>

        <div class="mb-3">
            <label class="form-label">Estado (automático)</label>
            <select class="form-select" id="new_estado" disabled>
                <option value="">-- Calculado automáticamente --</option>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>

    </form>
  </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="formEdit" method="POST" action="../process/eventos_process.php">

      <div class="modal-header">
        <h5 class="modal-title">Editar Evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="edit_id">
        <input type="hidden" name="estado" id="hidden_estado_edit">

        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input class="form-control" id="edit_nombre" name="nombre">
            <div class="invalid-feedback" id="err_edit_nombre"></div>
        </div>

        <div class="row">
            <div class="col">
                <label class="form-label">Fecha inicio</label>
                <input type="date" class="form-control" id="edit_fecha_inicio" name="fecha_inicio">
                <div class="invalid-feedback" id="err_edit_fecha_inicio"></div>
            </div>

            <div class="col">
                <label class="form-label">Fecha fin</label>
                <input type="date" class="form-control" id="edit_fecha_fin" name="fecha_fin">
                <div class="invalid-feedback" id="err_edit_fecha_fin"></div>
            </div>
        </div>

        <div class="mb-3 mt-3">
            <label class="form-label">Lugar</label>
            <input class="form-control" id="edit_lugar" name="lugar">
            <div class="invalid-feedback" id="err_edit_lugar"></div>
        </div>

        <div class="mb-3">
            <label class="form-label">Estado (automático)</label>
            <select class="form-select" id="edit_estado" disabled>
                <option value="">-- Calculado automáticamente --</option>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Actualizar</button>
      </div>

    </form>
  </div>
</div>

<!-- MODAL ELIMINAR -->
<form method="POST" action="../process/eventos_process.php">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" id="delete_tkn" name="tkn">

    <div class="modal fade" id="modalDelete" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Eliminar Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                ¿Está seguro de eliminar este evento?
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-danger">Eliminar</button>
            </div>

        </div>
      </div>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// EDITAR
function editar(ev) {
    document.getElementById("edit_id").value = ev.id;
    document.getElementById("edit_nombre").value = ev.nombre;
    document.getElementById("edit_fecha_inicio").value = ev.fecha_inicio;
    document.getElementById("edit_fecha_fin").value = ev.fecha_fin;
    document.getElementById("edit_lugar").value = ev.lugar;

    document.getElementById("edit_estado").value = ev.estado;
    document.getElementById("hidden_estado_edit").value = ev.estado;

    new bootstrap.Modal(document.getElementById("modalEdit")).show();
}

// ELIMINAR
function eliminarEvt(tkn) {
    document.getElementById("delete_tkn").value = tkn;
    new bootstrap.Modal(document.getElementById("modalDelete")).show();
}

// EXPORTAR
function exportarLienzos(tkn) {
    window.location.href = "ExportarLienzos.php?tkn=" + encodeURIComponent(tkn);
}

// VER FAMILIAS
function verFamilias(tkn) {
    window.location.href = "Familias.php?tkn=" + encodeURIComponent(tkn);
}

// ESTADO AUTOMÁTICO
document.addEventListener("DOMContentLoaded", function () {

    const newFin = document.getElementById("new_fecha_fin");
    const newEstado = document.getElementById("new_estado");
    const hidden_new = document.getElementById("hidden_estado_new");

    newFin.addEventListener("change", function () {
        let hoy = new Date(); hoy.setHours(0,0,0,0);
        let f = new Date(newFin.value);

        if (f < hoy) newEstado.value = "inactivo";
        else newEstado.value = "activo";

        hidden_new.value = newEstado.value;
    });

    const editFin = document.getElementById("edit_fecha_fin");
    const editEstado = document.getElementById("edit_estado");
    const hidden_edit = document.getElementById("hidden_estado_edit");

    editFin.addEventListener("change", function () {
        let hoy = new Date(); hoy.setHours(0,0,0,0);
        let f = new Date(editFin.value);

        if (f < hoy) editEstado.value = "inactivo";
        else editEstado.value = "activo";

        hidden_edit.value = editEstado.value;
    });

});
</script>

</body>
</html>
