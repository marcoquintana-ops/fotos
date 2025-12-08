<?php
session_start();
require_once dirname(__DIR__)."/config/db.php";

// ==============================
// CARGAR EVENTOS ACTIVOS
// ==============================
$eventos = [];
$sql = "SELECT id, nombre FROM eventos WHERE estado='activo' ORDER BY nombre";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) $eventos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Usuario - VyR Producciones</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{ background:#eef3f4; }
        .card{
            width:650px;
            border:1px solid #008798;
        }
        .btn-primary{
            background:#008798 !important;
            border-color:#008798 !important;
        }
        .btn-primary:hover{
            background:#007b88 !important;
        }
        .invalid-feedback { display:block; }
        .link-green{ color:#008798; text-decoration:none; }
        .link-green:hover{ text-decoration:underline; }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="card p-4 shadow">

        <h3 class="text-center mb-3">Registrar Nuevo Usuario</h3>

        <?php if(isset($_GET["ok"])): ?>
            <div class="alert alert-success">Usuario registrado correctamente.</div>
        <?php endif; ?>

        <form id="formReg" method="POST" action="../process/register_process.php" novalidate>

            <!-- FILA 1 -->
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Tipo Documento</label>
                    <select class="form-select" id="tipoDocumento" name="tipoDocumento">
                        <option value="">Seleccione</option>
                        <option value="dni">DNI</option>
                        <option value="ce">C.E.</option>
                    </select>
                    <div class="invalid-feedback" id="err_tipoDocumento"></div>
                </div>

                <div class="col-md-6 mb-2">
                    <label class="form-label">Número Documento</label>
                    <input class="form-control" id="numDocumento" name="numDocumento">
                    <div class="invalid-feedback" id="err_numDocumento"></div>
                </div>
            </div>

            <!-- FILA 2 -->
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Nombres</label>
                    <input class="form-control" id="nombre" name="nombre">
                    <div class="invalid-feedback" id="err_nombre"></div>
                </div>

                <div class="col-md-6 mb-2">
                    <label class="form-label">Apellidos</label>
                    <input class="form-control" id="apellidos" name="apellidos">
                    <div class="invalid-feedback" id="err_apellidos"></div>
                </div>
            </div>

            <!-- FILA 3 -->
            <div class="mb-2">
                <label class="form-label">Correo</label>
                <input class="form-control" id="correo" name="correo">
                <div class="invalid-feedback" id="err_correo"></div>
            </div>

            <!-- FILA 4 -->
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password">
                    <div class="invalid-feedback" id="err_password"></div>
                </div>

                <div class="col-md-6 mb-2">
                    <label class="form-label">Validar Contraseña</label>
                    <input type="password" class="form-control" id="password2" name="password2">
                    <div class="invalid-feedback" id="err_password2"></div>
                </div>
            </div>

            <!-- FILA 5 -->
            <div class="mb-2">
                <label class="form-label">Evento</label>
                <select class="form-select" id="evento" name="evento">
                    <option value="">Seleccione</option>
                    <?php foreach($eventos as $ev): ?>
                        <option value="<?= $ev["id"]; ?>"><?= htmlspecialchars($ev["nombre"]); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback" id="err_evento"></div>
            </div>

            <!-- FILA 6 -->
            <div class="mb-2">
                <label class="form-label">Nombre Familiar</label>
                <input class="form-control" id="nombreFamiliar" name="nombreFamiliar">
                <div class="invalid-feedback" id="err_nombreFamiliar"></div>
            </div>

            <!-- FILA 7 -->
            <div class="mb-3">
                <label class="form-label">Apellidos Familiar</label>
                <input class="form-control" id="apellidosFamiliar" name="apellidosFamiliar">
                <div class="invalid-feedback" id="err_apellidosFamiliar"></div>
            </div>

            <button class="btn btn-primary w-100">Registrar</button>

            <div class="text-center mt-2">
                <a class="link-green" href="Login.php">Volver a iniciar sesión</a>
            </div>

        </form>
    </div>
</div>

<script>
document.getElementById("formReg").addEventListener("submit", function(e){
    let ok = true;

    function error(id,msg){
        const el = document.getElementById(id);
        el.classList.add("is-invalid");
        document.getElementById("err_"+id).textContent = msg;
        ok = false;
    }

    function limpiar(id){
        const el = document.getElementById(id);
        el.classList.remove("is-invalid");
        document.getElementById("err_"+id).textContent = "";
    }

    const campos = [
        "tipoDocumento","numDocumento","nombre","apellidos",
        "correo","password","password2","evento",
        "nombreFamiliar","apellidosFamiliar"
    ];
    campos.forEach(c => limpiar(c));

    function check(id,msg){
        if(document.getElementById(id).value.trim() === ""){
            error(id,msg);
        }
    }

    check("tipoDocumento","Seleccione un tipo.");
    check("numDocumento","Ingrese número.");
    check("nombre","Ingrese nombres.");
    check("apellidos","Ingrese apellidos.");
    check("correo","Ingrese correo.");
    check("password","Ingrese contraseña.");
    check("password2","Confirme contraseña.");
    check("evento","Seleccione evento.");
    check("nombreFamiliar","Ingrese nombre familiar.");
    check("apellidosFamiliar","Ingrese apellidos familiar.");

    if(document.getElementById("password").value !== document.getElementById("password2").value){
        error("password2","Las contraseñas no coinciden.");
    }

    if(!ok) e.preventDefault();
});
</script>

</body>
</html>
