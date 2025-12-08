<?php
// process/lienzo_process.php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Solo familias autenticadas pueden usar este endpoint
if (!isset($_SESSION['familia_id']) || ($_SESSION['tipo'] ?? '') !== 'familia') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// helper para limpiar resultados múltiples de SP
function clear_results($conn) {
    while ($conn->more_results() && $conn->next_result()) {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // CARGAR LIENZO POR FAMILIA
    $familia_id = (int)($_GET['usuario_familia_id'] ?? 0);

    // Debe coincidir con la sesión
    if ($familia_id <= 0 || $familia_id !== (int)($_SESSION['familia_id'])) {
        echo json_encode(['ok' => false, 'message' => 'Parámetro inválido']);
        exit;
    }

    $stmt = $conn->prepare("CALL sp_lienzo_obtener_por_familia(?)");
    if (!$stmt) {
        echo json_encode(['ok' => false, 'message' => 'Error preparando SP']);
        exit;
    }
    $stmt->bind_param("i", $familia_id);
    if (!$stmt->execute()) {
        $stmt->close();
        clear_results($conn);
        echo json_encode(['ok' => false, 'message' => 'Error ejecutando SP']);
        exit;
    }

    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if ($res) $res->free();
    $stmt->close();
    clear_results($conn);

    if ($row) {
        echo json_encode([
            'ok' => true,
            'lienzo' => [
                'id'           => (int)$row['id'],
                'plantilla_id' => (int)$row['plantilla_id'],
                'nombre'       => $row['nombre'],
                'datos_lienzo' => json_decode($row['datos_lienzo'], true),
                'estado'       => $row['estado']
            ]
        ]);
    } else {
        echo json_encode(['ok' => true, 'lienzo' => null]);
    }
    exit;
}

if ($method === 'POST') {
    // GUARDAR LIENZO (INSERT / UPDATE)
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        echo json_encode(['ok' => false, 'message' => 'JSON inválido']);
        exit;
    }

    $familia_id = (int)($data['usuario_familia_id'] ?? 0);
    if ($familia_id <= 0 || $familia_id !== (int)($_SESSION['familia_id'])) {
        echo json_encode(['ok' => false, 'message' => 'Familia inválida']);
        exit;
    }

    $plantilla_id = (int)($data['plantilla_id'] ?? ($data['datos_lienzo']['plantilla_id'] ?? 0));
    $nombre       = trim($data['nombre'] ?? 'Lienzo principal');
    $datos_lienzo = $data['datos_lienzo'] ?? null;

    if ($plantilla_id <= 0 || !$datos_lienzo) {
        echo json_encode(['ok' => false, 'message' => 'Datos incompletos.']);
        exit;
    }

    $jsonLienzo = json_encode($datos_lienzo, JSON_UNESCAPED_UNICODE);
    if ($jsonLienzo === false) {
        echo json_encode(['ok' => false, 'message' => 'Error al codificar JSON del lienzo.']);
        exit;
    }

    $estado = 'borrador';

    $stmt = $conn->prepare("CALL sp_lienzo_guardar(?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['ok' => false, 'message' => 'Error preparando SP']);
        exit;
    }
    $stmt->bind_param(
        "iisss",
        $familia_id,
        $plantilla_id,
        $nombre,
        $jsonLienzo,
        $estado
    );
    if (!$stmt->execute()) {
        $msg = $stmt->error;
        $stmt->close();
        clear_results($conn);
        echo json_encode(['ok' => false, 'message' => 'Error al guardar: ' . $msg]);
        exit;
    }
    $stmt->close();
    clear_results($conn);

    echo json_encode(['ok' => true]);
    exit;
}

// Si llega aquí: método no soportado
echo json_encode(['ok' => false, 'message' => 'Método no soportado']);
