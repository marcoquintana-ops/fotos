<?php
// process/lienzo_process.php
session_start();

/**
 * Queremos que NUNCA salga HTML en la respuesta,
 * solo JSON (incluyendo errores fatales).
 */

// --- Configuración de errores ---
error_reporting(E_ALL);

// NO mostrar errores como HTML
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Para saber si ya respondimos en JSON
$GLOBALS['__JSON_RESPONSE_SENT__'] = false;

// Forzar siempre JSON
header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------------------
// Manejador de errores NO fatales (warnings, notices, etc.)
// ---------------------------------------------------------
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return;
    }

    if ($GLOBALS['__JSON_RESPONSE_SENT__'] === true) {
        return;
    }

    $GLOBALS['__JSON_RESPONSE_SENT__'] = true;

    // Limpiar cualquier salida previa
    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => "PHP error ($errno): $errstr en $errfile:$errline"
    ]);
    exit;
});

// ---------------------------------------------------------
// Manejador de apagado para errores FATALES (E_ERROR, etc.)
// ---------------------------------------------------------
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {

        if ($GLOBALS['__JSON_RESPONSE_SENT__'] === true) {
            return;
        }

        $GLOBALS['__JSON_RESPONSE_SENT__'] = true;

        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'ok'      => false,
            'message' => "PHP fatal error ({$error['type']}): {$error['message']} en {$error['file']}:{$error['line']}"
        ]);
    }
});

// Iniciar buffer de salida por si algún include imprime algo
ob_start();

require_once dirname(__DIR__) . '/config/db.php';

// Validar conexión
if (!isset($conn) || !$conn instanceof mysqli) {
    $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
    echo json_encode([
        'ok'      => false,
        'message' => 'No hay conexión con la base de datos.'
    ]);
    exit;
}

// Solo familias autenticadas pueden usar este endpoint
if (!isset($_SESSION['familia_id']) || ($_SESSION['tipo'] ?? '') !== 'familia') {
    $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
    echo json_encode([
        'ok'      => false,
        'message' => 'No autorizado. Debe iniciar sesión como familia.'
    ]);
    exit;
}

// Helper para limpiar resultados múltiples de SP
function clear_results(mysqli $conn): void {
    while ($conn->more_results() && $conn->next_result()) {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {

    // =========================================================
    // GET: Cargar lienzo de la familia logueada
    // =========================================================
    if ($method === 'GET') {

        $familia_id = (int) $_SESSION['familia_id'];

        $stmt = $conn->prepare("CALL sp_lienzo_obtener_por_familia(?)");
        if (!$stmt) {
            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'      => false,
                'message' => 'Error preparando SP (obtener lienzo): ' . $conn->error
            ]);
            exit;
        }

        $stmt->bind_param("i", $familia_id);

        if (!$stmt->execute()) {
            $msg = $stmt->error;
            $stmt->close();
            clear_results($conn);

            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'      => false,
                'message' => 'Error ejecutando SP (obtener lienzo): ' . $msg
            ]);
            exit;
        }

        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        if ($res) {
            $res->free();
        }
        $stmt->close();
        clear_results($conn);

        if ($row) {
            $datos = null;
            if (!empty($row['datos_lienzo'])) {
                $datos = json_decode($row['datos_lienzo'], true);
            }

            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'     => true,
                'lienzo' => [
                    'id'                  => (int) $row['id'],
                    'plantilla_id'        => (int) $row['plantilla_id'],
                    'nombre'              => $row['nombre'],
                    'datos_lienzo'        => $datos,
                    'estado'              => $row['estado'],
                    'fecha_creacion'      => $row['fecha_creacion'],
                    'fecha_actualizacion' => $row['fecha_actualizacion']
                ]
            ]);
        } else {
            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'     => true,
                'lienzo' => null
            ]);
        }

        exit;
    }

    // =========================================================
    // POST: Guardar lienzo
    // =========================================================
    if ($method === 'POST') {

        $raw = file_get_contents('php://input');

        if ($raw === '' || $raw === false) {
            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'      => false,
                'message' => 'No se recibieron datos en la petición.'
            ]);
            exit;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'      => false,
                'message' => 'JSON inválido en el cuerpo de la petición.'
            ]);
            exit;
        }

        // Validar familia contra la sesión
        $familia_id_body = (int) ($data['usuario_familia_id'] ?? 0);
        $familia_id_ses  = (int) $_SESSION['familia_id'];

        if ($familia_id_body <= 0 || $familia_id_body !== $familia_id_ses) {
            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'      => false,
                'message' => 'Familia inválida o no coincide con la sesión.'
            ]);
            exit;
        }

        $plantilla_id = (int) ($data['plantilla_id'] ?? ($data['datos_lienzo']['plantilla_id'] ?? 0));
        $nombre       = trim($data['nombre'] ?? 'Lienzo principal');
        $datos_lienzo = $data['datos_lienzo'] ?? null;

        if ($plantilla_id <= 0 || !$datos_lienzo) {
            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'      => false,
                'message' => 'Datos incompletos. Falta plantilla o estructura del lienzo.'
            ]);
            exit;
        }

        if (empty($datos_lienzo)) {
            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'      => false,
                'message' => 'No hay información de lienzo para guardar.'
            ]);
            exit;
        }

        $jsonLienzo = json_encode($datos_lienzo, JSON_UNESCAPED_UNICODE);
        if ($jsonLienzo === false) {
            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'      => false,
                'message' => 'No se pudo codificar el lienzo a JSON.'
            ]);
            exit;
        }

        $estado = 'borrador';

        $stmt = $conn->prepare("CALL sp_lienzo_guardar(?, ?, ?, ?, ?)");
        if (!$stmt) {
            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'      => false,
                'message' => 'Error preparando SP (guardar lienzo): ' . $conn->error
            ]);
            exit;
        }

        $stmt->bind_param(
            "iisss",
            $familia_id_ses,
            $plantilla_id,
            $nombre,
            $jsonLienzo,
            $estado
        );

        if (!$stmt->execute()) {
            $msg = $stmt->error;
            $stmt->close();
            clear_results($conn);

            $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
            echo json_encode([
                'ok'      => false,
                'message' => 'Error al guardar en BD: ' . $msg
            ]);
            exit;
        }

        $stmt->close();
        clear_results($conn);

        $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
        echo json_encode([
            'ok'      => true,
            'message' => 'Lienzo guardado correctamente.'
        ]);
        exit;
    }

    // =========================================================
    // MÉTODO NO SOPORTADO
    // =========================================================
    $GLOBALS['__JSON_RESPONSE_SENT__'] = true;
    echo json_encode([
        'ok'      => false,
        'message' => 'Método HTTP no soportado.'
    ]);
    exit;

} catch (Throwable $e) {
    if ($GLOBALS['__JSON_RESPONSE_SENT__'] === true) {
        exit;
    }

    $GLOBALS['__JSON_RESPONSE_SENT__'] = true;

    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => 'Excepción interna: ' . $e->getMessage()
    ]);
    exit;
}
