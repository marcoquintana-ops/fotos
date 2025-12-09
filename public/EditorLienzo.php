<?php
// public/EditorLienzo.php
session_start();

// solo FAMILIAS
if (!isset($_SESSION['familia_id']) || ($_SESSION['tipo'] ?? '') !== 'familia') {
    header('Location: Login.php');
    exit;
}

$familiaId      = (int)$_SESSION['familia_id'];
$nombreFamiliar = $_SESSION['nombre_familiar'] ?? 'Familia';

// evitar volver con atrás después de logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
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
            --brand: #008798;
            --brand-dark: #007582;
            --canvas-bg: #444;   /* fondo gris oscuro del lienzo */
            --canvas-inner: #ffffff;
            --slot-border: #ffffff;
            --fold-line: #bfbfbf;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: #e9f3f4;
            color: #222;
        }

        /* BARRA SUPERIOR (VERDE) */
        .topbar {
            background: var(--brand);
            color: #fff;
            padding: 0.6rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topbar-left {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .topbar-brand {
            font-size: 1.35rem; /* similar a navbar-brand de Eventos */
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .topbar-title-inline {
            font-size: 1rem;
            font-weight: 600;
            opacity: 0.95;
        }

        .topbar-subtitle-inline {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .topbar-right {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .btn-guardar {
            background: #ffffff;
            color: var(--brand);
            border: none;
            font-weight: 600;
            padding: 0.45rem 1.1rem;
            border-radius: 999px;
            font-size: 0.9rem;
        }

        .btn-guardar:hover {
            background: #f4f4f4;
        }

        .btn-logout {
            border-radius: 999px;
            padding: 0.45rem 1.1rem;
            font-size: 0.85rem;
            border: 1px solid #ffffff;
            color: #ffffff;
            background: transparent;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: rgba(255,255,255,0.16);
            color: #ffffff;
        }

        /* CONTENEDOR PRINCIPAL */
        .editor-wrapper {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 56px); /* altura total menos barra verde aprox */
        }

        /* BARRA DE PLANTILLAS ARRIBA */
        .templates-bar {
            background: #ffffff;
            padding: 0.5rem 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            overflow-x: auto;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .templates-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #555;
            margin-right: 0.5rem;
            flex: 0 0 auto;
        }

        .template-thumb {
            flex: 0 0 auto;
            width: 90px;
            height: 38px;
            border-radius: 6px;
            background: #f0f0f0;
            border: 2px solid transparent;
            padding: 3px;
            display: flex;
            align-items: stretch;
            justify-content: stretch;
            cursor: pointer;
            position: relative;
        }

        .template-thumb-inner {
            width: 100%;
            height: 100%;
            background: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .template-thumb.selected {
            border-color: var(--brand);
            box-shadow: 0 0 0 1px rgba(0,135,152,0.35);
        }

        /* Miniaturas — recuadros internos */
        .thumb-slot {
            position: absolute;
            background: #7F7F7F !important;  /* nuevo color */
            border: 2px solid #ffffff;       /* líneas blancas */
        }


        .thumb-margin {
            position: absolute;
            inset: 8%;
            border: 2px solid #ffffff !important;
            background: transparent;
        }


        /* ZONA LIENZO */
        .canvas-wrapper {
            flex: 1 1 auto;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: stretch;
            overflow: hidden; /* sin scroll */
        }

        .canvas-outer {
            background: var(--canvas-bg);
            border-radius: 0;
            padding: 4px;
            width: 100%;
            height: 100%;
            max-width: none;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Mantener relación 60.5 x 25.4 (aprox 605:254), ocupando el alto */
        .canvas-inner {
            position: relative;
            background: var(--canvas-inner);
            height: 100%;
            aspect-ratio: 605 / 254;
            width: auto;
            overflow: hidden;
        }

        /* Línea de doblez (VERTICAL SIEMPRE) */
        .fold-line {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 0;
            border-left: 2px dashed var(--fold-line); /* aprox 2mm */
            pointer-events: none;
            z-index: 5;
        }

        /* SLOTS DE FOTOS EN EL LIENZO */
        .photo-slot {
            position: absolute;
            border: 6px solid var(--slot-border); /* ~2 mm aprox */
            box-sizing: border-box;
            overflow: hidden;
            background: #7F7F7F; /* gris medio para cada recuadro (antes #e5e5e5) */
        }

        .photo-slot-inner {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .slot-image {
            position: absolute;
            top: 50%;
            left: 50%;
            transform-origin: center center;
            transform: translate(-50%, -50%) scale(1);
            max-width: none; /* usamos escala, no limitamos ancho */
        }

        .slot-controls {
            position: absolute;
            bottom: 4px;
            right: 4px;
            display: flex;
            gap: 4px;
            background: rgba(0,0,0,0.35);
            border-radius: 999px;
            padding: 2px 4px;
        }

        .slot-btn {
            border: none;
            background: transparent;
            color: #fff;
            font-size: 0.75rem;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .slot-btn:hover {
            background: rgba(255,255,255,0.15);
            border-radius: 999px;
        }

        input[type="file"].hidden-input {
            display: none;
        }

        /* Tooltip sencillo */
        .slot-btn[data-title] {
            position: relative;
        }
        .slot-btn[data-title]:hover::after {
            content: attr(attr-title);
            content: attr(data-title);
            position: absolute;
            bottom: 130%;
            right: 50%;
            transform: translateX(50%);
            background: rgba(0,0,0,0.75);
            color: #fff;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 4px;
            white-space: nowrap;
            pointer-events: none;
        }

        /* Mensajes flotantes */
        .toast-fixed {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 9999;
            min-width: 260px;
        }

        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.35rem;
            }
            .topbar-right {
                width: 100%;
                justify-content: flex-end;
            }
            .topbar-left {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <span class="topbar-brand">VyR Producciones</span>
        <span class="topbar-title-inline">Editor de lienzo</span>
        <span class="topbar-subtitle-inline">
            | Familia: <strong><?php echo htmlspecialchars($nombreFamiliar); ?></strong>
        </span>
    </div>
    <div class="topbar-right">
        <button id="btnGuardarLienzo" class="btn-guardar">Guardar</button>
        <a class="btn-logout" href="Logout.php">Cerrar sesión</a>
    </div>
</div>

<div class="editor-wrapper">

    <!-- BARRA DE PLANTILLAS -->
    <div class="templates-bar" id="templatesBar">
        <div class="templates-title">Plantillas</div>
        <!-- miniaturas se construyen desde app.js -->
    </div>

    <!-- ZONA DEL LIENZO -->
    <div class="canvas-wrapper">
        <div class="canvas-outer">
            <div class="canvas-inner" id="canvasInner">
                <div class="fold-line"></div>
                <!-- aquí JS dibuja los slots de fotos -->
            </div>
        </div>
    </div>

</div>

<!-- Toast de mensajes -->
<div class="toast-fixed">
    <div id="toastMsg" class="alert alert-secondary d-none mb-0"></div>
</div>

<script>
    window.LIENZO_CONFIG = {
        familiaId: <?php echo $familiaId; ?>,
        nombreFamiliar: <?php echo json_encode($nombreFamiliar, JSON_UNESCAPED_UNICODE); ?>
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
<script src="js/lienzo_feedback.js"></script>
</body>
</html>
