<?php
// process/eventos_process.php
// Gestiona: create, update, delete de eventos.
// Usa tokens ofuscados opcionally (tkn) y siempre SPs.
// Requiere session admin.

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/helpers/crypto.php';

// Validar conexión
if (!isset($conn) || !$conn instanceof mysqli) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'No hay conexión a la base de datos']);
    exit;
}

// Validar sesión + rol admin
if (!isset($_SESSION['admin_id']) || ($_SESSION['tipo'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok'=>false,'message'=>'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

// helpers
function json_ok($data = []) { echo json_encode(array_merge(['ok'=>true], (array)$data)); exit; }
function json_err($msg) { echo json_encode(['ok'=>false,'message'=>$msg]); exit; }

// ---- CREATE EVENTO ----
if ($action === 'create') {
    $nombre = trim($_POST['nombre'] ?? '');
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin = trim($_POST['fecha_fin'] ?? '');
    $lugar = trim($_POST['lugar'] ?? '');
    $estado = trim($_POST['estado'] ?? '');

    // server-side validation (mensajes por cada campo)
    $errors = [];
    if ($nombre === '') $errors['nombre'] = 'Nombre requerido';
    if ($fecha_inicio === '') $errors['fecha_inicio'] = 'Fecha inicio requerida';
    if ($fecha_fin === '') $errors['fecha_fin'] = 'Fecha fin requerida';
    if ($lugar === '') $errors['lugar'] = 'Lugar requerido';

    if (!empty($errors)) {
        json_ok(['ok'=>false,'errors'=>$errors]);
    }

    // Llamar SP (asumimos sp_evento_crear IN: p_nombre, p_fecha_inicio, p_fecha_fin, p_lugar)
    $stmt = $conn->prepare("CALL sp_evento_crear(?,?,?,?)");
    if (!$stmt) json_err('Error servidor (prepare): '.$conn->error);

    $stmt->bind_param("ssss", $nombre, $fecha_inicio, $fecha_fin, $lugar);
    $ok = $stmt->execute();
    $stmt->close();
    // limpiar posibles resultados
    if ($conn->more_results()) $conn->next_result();

    if (!$ok) json_err('No se pudo crear evento');
    // retorno OK
    json_ok(['message'=>'Evento creado']);
}

// ---- UPDATE EVENTO ----
// Nota: admitimos recibir 'tkn' (token) o 'id' numérico (compatibilidad)
if ($action === 'update') {
    $tkn = $_POST['tkn'] ?? '';
    $id = null;
    if (!empty($tkn)) {
        $dec = decrypt_id($tkn);
        if ($dec === false || !is_numeric($dec)) json_err('Token inválido');
        $id = intval($dec);
    } else {
        // fallback: id numérico (legacy)
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) json_err('ID inválido');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin = trim($_POST['fecha_fin'] ?? '');
    $lugar = trim($_POST['lugar'] ?? '');
    $estado = trim($_POST['estado'] ?? '');

    $errors = [];
    if ($nombre === '') $errors['nombre'] = 'Nombre requerido';
    if ($fecha_inicio === '') $errors['fecha_inicio'] = 'Fecha inicio requerida';
    if ($fecha_fin === '') $errors['fecha_fin'] = 'Fecha fin requerida';
    if ($lugar === '') $errors['lugar'] = 'Lugar requerido';
    if (!in_array($estado, ['activo','finalizado','inactivo',''])) {
        // permitir '' por compatibilidad; el SP puede decidir
        $errors['estado'] = 'Estado inválido';
    }

    if (!empty($errors)) json_ok(['ok'=>false,'errors'=>$errors]);

    // Llamar SP sp_evento_actualizar(IN p_id INT, IN p_nombre, IN p_fecha_inicio, IN p_fecha_fin, IN p_lugar, IN p_estado)
    // Ajustamos bind según el SP; si tu SP no espera estado, modifica la llamada en tu BD.
    $stmt = $conn->prepare("CALL sp_evento_actualizar(?,?,?,?,?,?)");
    if (!$stmt) json_err('Error servidor (prepare): '.$conn->error);

    $stmt->bind_param("isssss", $id, $nombre, $fecha_inicio, $fecha_fin, $lugar, $estado);
    $ok = $stmt->execute();
    $stmt->close();
    if ($conn->more_results()) $conn->next_result();

    if (!$ok) json_err('No se pudo actualizar evento');
    json_ok(['message'=>'Evento actualizado']);
}

// ---- DELETE EVENTO ----
// Recibe token 'tkn' preferentemente
if ($action === 'delete') {
    $tkn = $_POST['tkn'] ?? '';
    if (empty($tkn)) json_err('Token requerido');

    $dec = decrypt_id($tkn);
    if ($dec === false || !is_numeric($dec)) json_err('Token inválido');

    $id = intval($dec);
    if ($id <= 0) json_err('ID inválido');

    // Llamar SP sp_evento_eliminar(IN p_id)
    $stmt = $conn->prepare("CALL sp_evento_eliminar(?)");
    if (!$stmt) json_err('Error servidor (prepare): '.$conn->error);

    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    if ($conn->more_results()) $conn->next_result();

    if (!$ok) json_err('No se pudo eliminar evento');
    json_ok(['message'=>'Evento eliminado']);
}

// Si no reconocemos action
json_err('Acción no reconocida');
