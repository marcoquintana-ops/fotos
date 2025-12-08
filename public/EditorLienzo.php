<?php
// public/EditorLienzo.php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Solo usuarios de tipo familia
if (!isset($_SESSION['familia_id']) || ($_SESSION['tipo'] ?? '') !== 'familia') {
    header('Location: Login.php');
    exit;
}

$familiaId = (int)$_SESSION['familia_id'];
$nombreFamiliar = $_SESSION['nombre_familiar'] ?? 'Familia';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editor de lienzo - VyR Producciones</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --vyr-green: #008798;
            --vyr-green-dark: #006f79;
            --bg-page: #e9f3f4;
            --bg-lienzo: #4b5563;      /* gris medio oscuro */
            --bg-lienzo-inner: #ffffff; /* blanco principal del lienzo */
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-page);
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
        }

        .navbar-vyr {
            background-color: var(--vyr-green) !important;
            color: #fff;
        }

        .navbar-vyr .navbar-brand {
            font-weight: bold;
        }

        .navbar-vyr a {
            color: #ffffff;
            text-decoration: none;
        }
        .navbar-vyr a:hover {
            text-decoration: underline;
        }

        .btn-vyr {
            background-color: var(--vyr-green);
            border-color: var(--vyr-green);
            color: #fff;
        }
        .btn-vyr:hover {
            background-color: var(--vyr-green-dark);
            border-color: var(--vyr-green-dark);
            color: #fff;
        }

        .editor-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 12px;
            gap: 10px;
        }

        .plantillas-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            padding: 6px 10px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            white-space: nowrap;
        }

        .plantilla-thumb {
            position: relative;
            width: 120px;
            height: 45px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            flex: 0 0 auto;
        }

        .plantilla-thumb.selected {
            border-color: var(--vyr-green);
            box-shadow: 0 0 0 2px rgba(0,135,152,0.2);
        }

        .plantilla-thumb-inner-margin {
            position: absolute;
            inset: 4px;
            background: #f9fafb;
        }

        .plantilla-thumb-slot {
            position: absolute;
            background: #d1d5db;
            border: 2px solid #ffffff;
            box-sizing: border-box;
        }

        .lienzo-wrapper {
            flex: 1;
            min-height: 0;
            background: #d1d5db;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            padding: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        #lienzoCanvas {
            display: block;
            max-width: 100%;
            max-height: 100%;
        }

        /* Contenedor de controles por slot sobre el canvas */
        #slotControlsContainer {
            position: absolute;
            inset: 10px;
            pointer-events: none; /* se habilita por botón */
        }

        .slot-controls {
            position: absolute;
            display: flex;
            flex-direction: row;
            gap: 4px;
            background: rgba(0,0,0,0.35);
            border-radius: 6px;
            padding: 2px 4px;
            align-items: center;
            pointer-events: auto;
        }

        .slot-btn {
            border: none;
            background: transparent;
            color: #ffffff;
            font-size: 14px;
            width: 20px;
            height: 20px;
            padding: 0;
            cursor: pointer;
        }
        .slot-btn:hover {
            color: #facc15;
        }

        .slot-btn-folder::before {
            content: "\1F4C2"; /* carpeta */
        }

        .slot-btn-plus::before {
            content: "+";
        }

        .slot-btn-minus::before {
            content: "−";
        }

        .info-bar {
            font-size: 0.9rem;
            color: #4b5563;
        }

        @media (max-width: 768px) {
            .plantilla-thumb {
                width: 100px;
                height: 40px;
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-vyr px-3 py-2 d-flex justify-content-between align-items-center">
    <div>
        <span class="navbar-brand mb-0 h1">Editor de lienzo</span>
    </div>
    <div class="text-end">
        <span class="me-3">Familia: <strong><?php echo htmlspecialchars($nombreFamiliar); ?></strong></span>
        <a href="Logout.php" class="btn btn-sm btn-light">Cerrar sesión</a>
    </div>
</nav>

<div class="editor-container">

    <div class="d-flex justify-content-between align-items-center mb-1">
        <div class="info-bar">
            Tamaño físico del lienzo: <strong>25.4 cm × 60.5 cm</strong> &nbsp;|&nbsp;
            Líneas de separación ≈ <strong>2 mm</strong> &nbsp;|&nbsp;
            Línea de doblez vertical solo visible en editor.
        </div>
        <button id="btnGuardarLienzo" class="btn btn-vyr btn-sm">
            Guardar lienzo
        </button>
    </div>

    <!-- BARRA DE PLANTILLAS -->
    <div id="plantillasBar" class="plantillas-bar">
        <!-- Se llena desde app.js -->
    </div>

    <!-- ÁREA DE LIENZO -->
    <div class="lienzo-wrapper">
        <canvas id="lienzoCanvas"></canvas>
        <div id="slotControlsContainer"></div>
    </div>

</div>

<!-- input oculto para cargar imágenes -->
<input type="file" id="fileInputHidden" accept="image/*" style="display:none" />

<!-- Configuración para JS -->
<script>
    window.VYR_LIENZO = {
        familiaId: <?php echo (int)$familiaId; ?>,
        nombreFamiliar: <?php echo json_encode($nombreFamiliar, JSON_UNESCAPED_UNICODE); ?>
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>

</body>
</html>
