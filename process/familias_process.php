<?php
// process/familias_process.php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/helpers/crypto.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['ok'=>false,'message'=>'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

function json_err($msg) { echo json_encode(['ok'=>false,'message'=>$msg]); exit; }
function json_ok($data=[]) { echo json_encode(array_merge(['ok'=>true], (array)$data)); exit; }

if ($action === 'list') {
    $tkn_evento = $_POST['tkn_evento'] ?? '';
    $texto = trim($_POST['texto'] ?? '');

    $evento_id = decrypt_id($tkn_evento);
    if ($evento_id === false || !is_numeric($evento_id)) json_err('Token de evento inválido');

    $evento_id = intval($evento_id);

    $stmt = $conn->prepare("CALL sp_listar_familias(?, ?)");
    if (!$stmt) json_err('SP sp_listar_familias error: '.$conn->error);

    $stmt->bind_param("is", $evento_id, $texto);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        // agregar token ofuscado por cada fila
        $r['token_id'] = encrypt_id($r['id']);
        $rows[] = $r;
    }
    $stmt->close();
    if ($conn->more_results()) $conn->next_result();

    echo json_encode($rows);
    exit;
}

/* CREATE */
if ($action === 'create') {
    $tkn_evento = $_POST['tkn_evento'] ?? '';
    $evento_id = decrypt_id($tkn_evento);
    if ($evento_id === false || !is_numeric($evento_id)) json_err('Token de evento inválido');

    $tipo_documento = $_POST['tipo_documento'] ?? '';
    $numero_documento = $_POST['numero_documento'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $nombre_familiar = $_POST['nombre_familiar'] ?? '';
    $apellidos_Familiar = $_POST['apellidos_Familiar'] ?? '';

    $errors = [];
    if (!in_array($tipo_documento, ['dni','ce'])) $errors['tipo_documento'] = 'Tipo inválido';
    if (trim($numero_documento) === '') $errors['numero_documento'] = 'Número requerido';
    if (trim($nombres) === '') $errors['nombres'] = 'Nombres requeridos';
    if (trim($apellidos) === '') $errors['apellidos'] = 'Apellidos requeridos';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email inválido';
    if (trim($nombre_familiar) === '') $errors['nombre_familiar'] = 'Nombre familiar requerido';

    if (!empty($errors)) json_ok(['ok'=>false,'errors'=>$errors]);

    $hash = null;
    if (trim($password) !== '') $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("CALL sp_familia_insertar(?,?,?,?,?,?,?,?,?)");
    if (!$stmt) json_err('SP insert error: '.$conn->error);
    $stmt->bind_param("ssssssiss",
        $tipo_documento,
        $numero_documento,
        $nombres,
        $apellidos,
        $email,
        $hash,
        $evento_id,
        $nombre_familiar,
        $apellidos_Familiar
    );
    $ex = $stmt->execute();
    $stmt->close();
    if ($conn->more_results()) $conn->next_result();
    if (!$ex) json_err('No se pudo insertar');
    json_ok();
}

/* UPDATE */
if ($action === 'update') {
    $tkn_id = $_POST['tkn_id'] ?? '';
    $id = decrypt_id($tkn_id);
    if ($id === false || !is_numeric($id)) json_err('Token id inválido');
    $id = intval($id);

    $tipo_documento = $_POST['tipo_documento'] ?? '';
    $numero_documento = $_POST['numero_documento'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $evento_id = intval($_POST['evento_id'] ?? 0);
    $nombre_familiar = $_POST['nombre_familiar'] ?? '';
    $apellidos_Familiar = $_POST['apellidos_Familiar'] ?? '';
    $estado = $_POST['estado'] ?? 'activo';

    $errors = [];
    if (!in_array($tipo_documento, ['dni','ce'])) $errors['tipo_documento'] = 'Tipo inválido';
    if (trim($numero_documento) === '') $errors['numero_documento'] = 'Número requerido';
    if (trim($nombres) === '') $errors['nombres'] = 'Nombres requeridos';
    if (trim($apellidos) === '') $errors['apellidos'] = 'Apellidos requeridos';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email inválido';
    if ($evento_id <= 0) $errors['evento_id'] = 'Seleccione evento';
    if (trim($nombre_familiar) === '') $errors['nombre_familiar'] = 'Nombre familiar requerido';

    if (!empty($errors)) json_ok(['ok'=>false,'errors'=>$errors]);

    $hash = null;
    if (trim($password) !== '') $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("CALL sp_familia_actualizar(?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) json_err('SP update error: '.$conn->error);
    $p_pass_param = $hash ?? '';
    $stmt->bind_param("issssssisss",
        $id,
        $tipo_documento,
        $numero_documento,
        $nombres,
        $apellidos,
        $email,
        $p_pass_param,
        $evento_id,
        $nombre_familiar,
        $apellidos_Familiar,
        $estado
    );
    $ex = $stmt->execute();
    $stmt->close();
    if ($conn->more_results()) $conn->next_result();
    if (!$ex) json_err('No se pudo actualizar');
    json_ok();
}

/* DELETE */
if ($action === 'delete') {
    $tkn_id = $_POST['tkn_id'] ?? '';
    $id = decrypt_id($tkn_id);
    if ($id === false || !is_numeric($id)) json_err('Token id inválido');
    $id = intval($id);

    $stmt = $conn->prepare("CALL sp_familia_eliminar(?)");
    if (!$stmt) json_err('SP delete error');
    $stmt->bind_param("i", $id);
    $ex = $stmt->execute();
    $stmt->close();
    if ($conn->more_results()) $conn->next_result();
    if (!$ex) json_err('No se pudo eliminar');
    json_ok();
}

/* RESET PASSWORD (admin resets family password) */
if ($action === 'reset_password') {
    $tkn_id = $_POST['tkn_id'] ?? '';
    $id = decrypt_id($tkn_id);
    if ($id === false || !is_numeric($id)) json_err('Token id inválido');
    $id = intval($id);

    $newpass = $_POST['new_password'] ?? '';
    if (trim($newpass) === '') json_err('Contraseña inválida');

    // Obtener email por SP
    $stmt = $conn->prepare("CALL sp_obtener_familia(?)");
    if (!$stmt) json_err('SP obtener familia error');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $fam = $res->fetch_assoc();
    $stmt->close();
    if ($conn->more_results()) $conn->next_result();

    if (!$fam) json_err('Familia no encontrada');
    if ($fam['estado'] !== 'activo') json_ok(['ok'=>false,'message'=>'Usuario inactivo']);

    $hash = password_hash($newpass, PASSWORD_BCRYPT);

    $stmt2 = $conn->prepare("CALL sp_resetear_password_familia(?, ?)");
    if (!$stmt2) json_err('SP resetear error');
    $stmt2->bind_param("ss", $fam['email'], $hash);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $r2 = $res2->fetch_assoc();
    $stmt2->close();
    if ($conn->more_results()) $conn->next_result();

    if ($r2 && isset($r2['actualizado']) && intval($r2['actualizado']) > 0) {
        json_ok();
    } else {
        json_ok(['ok'=>false,'message'=>'No se realizó la actualización']);
    }
}

json_err('Acción no reconocida');
