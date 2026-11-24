<?php
session_start();
if (!isset($_SESSION['rol'])) { 
    header("Location: /LSB/views/login/login.php"); 
    exit(); 
}
include("../../config/conexion.php");

$categoria = isset($_GET['cat']) ? mysqli_real_escape_string($conexion, $_GET['cat']) : 'abecedario';
$letra_url = isset($_GET['letra']) ? mb_strtoupper(trim($_GET['letra']), 'UTF-8') : '';

// LISTA CANÓNICA DEL ABECEDARIO CON LA Ñ EN SU LUGAR EXACTO
$orden_abecedario = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','Ñ','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

$query_items = "SELECT * FROM contenidos WHERE tipo_categoria = '$categoria'";
$res_items = mysqli_query($conexion, $query_items);

$contenidos_db = [];
if ($res_items) {
    while ($row = mysqli_fetch_assoc($res_items)) {
        if ($categoria === 'abecedario') {
            $letra_key = mb_strtoupper(trim($row['letra_abecedario'] ?? ''), 'UTF-8');
            if ($letra_key !== '') {
                $contenidos_db[$letra_key] = [
                    'id_contenido' => $row['id_contenido'] ?? null,
                    'titulo' => $letra_key,
                    'traduccion' => !empty($row['palabra_espanol']) ? $row['palabra_espanol'] : "Letra " . $letra_key,
                    'imagen' => $row['ruta_visual'] ?? ''
                ];
            }
        } else {
            $contenidos_db[] = [
                'id_contenido' => $row['id_contenido'] ?? null,
                'titulo' => $row['palabra_ingles'] ?: ($row['letra_abecedario'] ?: ''),
                'traduccion' => $row['palabra_espanol'] ?: '',
                'imagen' => $row['ruta_visual'] ?? ''
            ];
        }
    }
}

$lista_contenidos = [];
if ($categoria === 'abecedario') {
    foreach ($orden_abecedario as $letra) {
        if (isset($contenidos_db[$letra])) {
            $lista_contenidos[] = $contenidos_db[$letra];
        } else {
            $lista_contenidos[] = [
                'id_contenido' => null,
                'titulo' => $letra,
                'traduccion' => "Letra " . $letra,
                'imagen' => ''
            ];
        }
    }
    foreach ($contenidos_db as $key => $item) {
        if (!in_array($key, $orden_abecedario, true)) {
            $lista_contenidos[] = $item;
        }
    }
} else {
    $lista_contenidos = $contenidos_db;
    if (empty($lista_contenidos)) {
        $lista_contenidos = [
            ['id_contenido' => null, 'titulo' => "Hello", 'traduccion' => "Hola", 'imagen' => ''],
            ['id_contenido' => null, 'titulo' => "Thank you", 'traduccion' => "Gracias", 'imagen' => ''],
            ['id_contenido' => null, 'titulo' => "Goodbye", 'traduccion' => "Adiós", 'imagen' => '']
        ];
    }
}

$indice_inicial = 0;
if ($letra_url !== '') {
    foreach ($lista_contenidos as $idx => $cont) {
        if (mb_strtoupper($cont['titulo'], 'UTF-8') === $letra_url) {
            $indice_inicial = $idx;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HANDY - Misión Espacial de Señas LSB</title>

    <!-- Tipografías espaciales -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800;900&family=Quicksand:wght@600;700&display=swap" rel="stylesheet">

    <!-- LIBRERÍAS DE INTELIGENCIA ARTIFICIAL -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@latest/dist/teachablemachine-image.min.js"></script>

    <!-- MEDIAPIPE HANDS PARA DETECCIÓN DE 21 PUNTOS Y SEGUIMIENTO -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/hands/hands.js" crossorigin="anonymous"></script>

    <!-- LIBRERÍA DE CELEBRACIÓN ESPACIAL CON CONFETI -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>

    <style>
        :root {
            --space-violet: #8b5cf6;
            --space-purple: #a855f7;
            --space-blue: #3b82f6;
            --space-cyan-glow: #22d3ee;
            --space-pink-glow: #f43f5e;
            --space-green: #22c55e;
            --space-amber: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Quicksand', 'Outfit', sans-serif;
            background-color: #070312;
            color: #ffffff;
            margin: 0;
            padding: 20px;
            text-align: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }

        /* FONDO ANIMADO DEL CONEJO ESPACIAL */
        .video-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            z-index: -3;
        }

        .space-video-bg {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            filter: brightness(0.8) contrast(1.1);
        }

        .space-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: radial-gradient(circle at center, rgba(16, 9, 36, 0.72) 0%, rgba(9, 4, 24, 0.88) 65%, rgba(5, 2, 16, 0.96) 100%);
            z-index: -2;
            pointer-events: none;
        }

        /* BOTÓN DE SONIDO */
        .btn-sound-control {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            background: rgba(18, 12, 38, 0.75);
            border: 1.5px solid rgba(168, 85, 247, 0.45);
            color: #ffffff;
            padding: 9px 16px;
            border-radius: 50px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            transition: all 0.25s ease;
        }

        .btn-sound-control:hover {
            transform: translateY(-2px);
            border-color: var(--space-cyan-glow);
            box-shadow: 0 0 15px rgba(34, 211, 238, 0.4);
        }

        .btn-sound-control.sound-on {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.2);
        }

        /* CONTENEDOR PRINCIPAL */
        #pantalla_juego {
            width: 100%;
            max-width: 1050px;
            background: rgba(22, 14, 45, 0.82);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 26px;
            border-radius: 24px;
            border: 1.5px solid rgba(168, 85, 247, 0.35);
            box-shadow: 0 20px 60px rgba(0,0,0,0.8), 0 0 35px rgba(139, 92, 246, 0.25);
            position: relative;
            z-index: 10;
        }

        .header-juego {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-volver {
            color: #22d3ee;
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: rgba(34, 211, 238, 0.1);
            border: 1px solid rgba(34, 211, 238, 0.3);
            border-radius: 10px;
            transition: all 0.25s ease;
        }

        .btn-volver:hover {
            background: rgba(34, 211, 238, 0.2);
            transform: translateX(-3px);
            color: #ffffff;
        }

        .titulo-mision {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            font-size: 1.7rem;
            font-weight: 800;
            background: linear-gradient(135deg, #22d3ee 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .badge-progreso {
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            padding: 6px 16px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(236, 72, 153, 0.4);
        }

        /* SELECTOR DE LETRAS */
        .selector-abecedario-wrapper {
            background: rgba(14, 9, 30, 0.65);
            border: 1px solid rgba(168, 85, 247, 0.25);
            border-radius: 14px;
            padding: 10px 14px;
            margin-bottom: 18px;
        }

        .selector-header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.88rem;
            color: #cbd5e1;
            font-weight: 700;
            margin-bottom: 8px;
            padding: 0 4px;
        }

        .selector-scroll {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding: 4px 2px 8px;
            scrollbar-width: thin;
            scrollbar-color: #a855f7 rgba(255,255,255,0.05);
        }

        .btn-letra {
            flex: 0 0 auto;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid rgba(168, 85, 247, 0.3);
            background: rgba(30, 20, 60, 0.7);
            color: #ffffff;
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-letra:hover {
            transform: translateY(-2px);
            background: rgba(168, 85, 247, 0.35);
            border-color: #22d3ee;
        }

        .btn-letra.activa {
            background: linear-gradient(135deg, #22d3ee 0%, #8b5cf6 100%);
            border-color: #ffffff;
            color: #070312;
            box-shadow: 0 0 12px rgba(34, 211, 238, 0.6);
            transform: scale(1.08);
        }

        /* GRID PRINCIPAL: GUÍA A LA IZQUIERDA Y CÁMARA A LA DERECHA */
        .game-grid {
            display: grid;
            grid-template-columns: 1fr 1.15fr;
            gap: 20px;
            align-items: stretch;
        }

        .panel-juego {
            background: rgba(15, 10, 32, 0.7);
            border: 1.5px solid rgba(168, 85, 247, 0.3);
            border-radius: 18px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .panel-header-sub {
            font-size: 0.95rem;
            color: #94a3b8;
            font-weight: 700;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .guia-letra-grande {
            font-family: 'Outfit', sans-serif;
            font-size: 3.4rem;
            font-weight: 900;
            margin: 0;
            line-height: 1;
            background: linear-gradient(135deg, #ffffff 0%, #22d3ee 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .guia-traduccion-txt {
            font-size: 1.05rem;
            color: #cbd5e1;
            margin: 6px 0 12px;
        }

        .contenedor-imagen-seña {
            width: 100%;
            height: 250px;
            background: rgba(7, 3, 18, 0.85);
            border: 1.5px dashed rgba(168, 85, 247, 0.4);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .contenedor-imagen-seña img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }

        .letra-fallback-display {
            font-family: 'Outfit', sans-serif;
            font-size: 7rem;
            font-weight: 900;
            color: rgba(168, 85, 247, 0.35);
        }

        /* CONTENEDOR DE CÁMARA Y CANVAS OVERLAY */
        .video-wrapper {
            position: relative;
            width: 100%;
            height: 250px;
            background: #000000;
            border-radius: 14px;
            overflow: hidden;
            border: 2px solid rgba(168, 85, 247, 0.4);
            box-shadow: 0 0 20px rgba(0,0,0,0.6);
        }

        #webcam {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1); /* Espejo amigable para niños */
        }

        #overlay_canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            transform: scaleX(-1); /* Espejo alineado con el video */
        }

        /* INSIGNIA DE ESTADO DE LA MANO */
        .hand-status-pill {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
            background: rgba(10, 6, 25, 0.75);
            border: 1px solid rgba(34, 211, 238, 0.4);
            color: #22d3ee;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            backdrop-filter: blur(8px);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* BARRA DE CONFIANZA CÓSMICA */
        .confianza-section {
            margin: 12px 0 6px;
            text-align: left;
        }

        .confianza-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.88rem;
            font-weight: 700;
            color: #cbd5e1;
            margin-bottom: 6px;
        }

        .confianza-bar-bg {
            width: 100%;
            height: 14px;
            background: rgba(30, 20, 60, 0.7);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(168, 85, 247, 0.35);
            position: relative;
        }

        .confianza-bar-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #3b82f6 0%, #22d3ee 100%);
            border-radius: 10px;
            transition: width 0.15s ease-out, background 0.25s ease;
            box-shadow: 0 0 10px rgba(34, 211, 238, 0.5);
        }

        .confianza-bar-fill.success {
            background: linear-gradient(90deg, #22c55e 0%, #4ade80 100%);
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.8);
        }

        .confianza-bar-fill.warning {
            background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.6);
        }

        /* REACCIONES DE LA MASCOTA CONEJO COSMO */
        .cosmo-card {
            background: rgba(18, 12, 38, 0.85);
            border: 1.5px solid rgba(34, 211, 238, 0.35);
            border-radius: 14px;
            padding: 10px 14px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
        }

        .cosmo-avatar {
            font-size: 2rem;
            line-height: 1;
            filter: drop-shadow(0 0 8px rgba(34, 211, 238, 0.5));
            animation: cosmoFloat 2.5s ease-in-out infinite alternate;
        }

        @keyframes cosmoFloat {
            0% { transform: translateY(0px) rotate(-3deg); }
            100% { transform: translateY(-4px) rotate(3deg); }
        }

        .cosmo-speech {
            font-size: 0.92rem;
            color: #ffffff;
            font-weight: 700;
            line-height: 1.3;
        }

        .camera-controls {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .btn-cam {
            flex: 1;
            padding: 10px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.92rem;
            font-weight: 800;
            border-radius: 10px;
            cursor: pointer;
            border: none;
            transition: all 0.25s ease;
        }

        .btn-cam-on {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        .btn-cam-on:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.5);
        }

        .btn-cam-off {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        .btn-cam-off:hover {
            background: rgba(239, 68, 68, 0.35);
        }

        .btn-siguiente-mision {
            display: none;
            width: 100%;
            margin-top: 12px;
            padding: 12px;
            background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(236, 72, 153, 0.5);
            animation: pulseBtn 1.8s infinite;
        }

        @keyframes pulseBtn {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .btn-retry {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.4);
            padding: 6px 14px;
            border-radius: 8px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-retry:hover {
            background: rgba(245, 158, 11, 0.35);
        }

        /* PANTALLA DE MISIÓN COMPLETADA */
        #pantalla_completado {
            display: none;
            background: rgba(22, 14, 45, 0.95);
            padding: 50px 30px;
            border-radius: 24px;
            border: 2px solid var(--space-cyan-glow);
            box-shadow: 0 0 50px rgba(34, 211, 238, 0.4);
            max-width: 600px;
            margin: 0 auto;
        }

        .btn-volver-mapa {
            background: linear-gradient(135deg, #22d3ee 0%, #8b5cf6 100%);
            color: #070312;
            padding: 14px 30px;
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            font-size: 1.1rem;
            border-radius: 12px;
            display: inline-block;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .game-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- FONDO DE VIDEO Y OVERLAY -->
    <div class="video-container">
        <video id="bgVideo" class="space-video-bg" autoplay loop muted playsinline preload="auto">
            <source src="/LSB/assets/videos/conejo_espacial.mp4" type="video/mp4">
            <source src="../../assets/videos/conejo_espacial.mp4" type="video/mp4">
            Tu navegador no soporta video HTML5.
        </video>
    </div>
    <div class="space-overlay"></div>

    <!-- BOTÓN DE SONIDO -->
    <button id="btnSound" class="btn-sound-control" onclick="toggleSonido()" type="button" aria-label="Control de Sonido">
        <span id="soundIcon">🔇</span>
        <span id="soundText">Audio</span>
    </button>

    <!-- VENTANA DE JUEGO -->
    <div id="pantalla_juego">
        
        <div class="header-juego">
            <a href="niveles.php" class="btn-volver">
                ⬅️ Mapa de Misiones
            </a>
            <h2 class="titulo-mision">🎯 <?= $categoria === 'abecedario' ? 'Abecedario LSB' : 'Módulo de Vocabulario' ?></h2>
            <div style="display:flex; align-items:center; gap:8px;">
                <span id="contador_progreso" class="badge-progreso">1/<?= count($lista_contenidos) ?></span>
            </div>
        </div>

        <?php if ($categoria === 'abecedario'): ?>
        <div class="selector-abecedario-wrapper">
            <div class="selector-header-title">
                <span>🛸 Selecciona cualquier letra para entrenar directamente:</span>
                <span style="color:#22d3ee; font-size:0.8rem;">27 Letras LSB</span>
            </div>
            <div class="selector-scroll" id="barra_abecedario">
                <?php foreach ($lista_contenidos as $idx => $item): ?>
                    <button type="button" 
                            class="btn-letra <?= $idx === $indice_inicial ? 'activa' : '' ?>" 
                            id="btn_letra_<?= $idx ?>" 
                            onclick="irALetra(<?= $idx ?>)"
                            title="Entrenar letra <?= htmlspecialchars($item['titulo']) ?>">
                        <?= htmlspecialchars($item['titulo']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="game-grid">
            
            <!-- PANEL IZQUIERDO: GUÍA VISUAL -->
            <div class="panel-juego">
                <div>
                    <h3 class="panel-header-sub">Haz la seña para:</h3>
                    <h1 id="guia_titulo" class="guia-letra-grande">A</h1>
                    <p class="guia-traduccion-txt">Traducción: <strong id="guia_traduccion" style="color: #22d3ee;">A</strong></p>
                </div>
                
                <div class="contenedor-imagen-seña">
                    <div id="letra_grande_fallback" class="letra-fallback-display">A</div>
                    <img id="guia_imagen" src="" alt="Guía Seña LSB">
                </div>

                <div style="margin-top:14px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.85rem; color:#94a3b8;">Observa la posición de los dedos</span>
                    <button type="button" onclick="reiniciarIntentoActual()" class="btn-retry">🔄 Reintentar</button>
                </div>
            </div>

            <!-- PANEL DERECHO: CÁMARA IA CON MEDIAPIPE Y TEACHABLE MACHINE -->
            <div class="panel-juego">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <h3 class="panel-header-sub" style="color:#22c55e;">🎥 Cámara de Detección IA</h3>
                    <span id="badge_modo" style="font-size:0.75rem; color:#a855f7; font-weight:bold;">Modo: Estabilizado + 21 Puntos</span>
                </div>
                
                <div class="video-wrapper">
                    <span id="hand_status_badge" class="hand-status-pill">🔍 Esperando cámara...</span>
                    <video id="webcam" autoplay playsinline muted></video>
                    <canvas id="overlay_canvas"></canvas>
                </div>

                <!-- BARRA DE CONFIANZA EN TIEMPO REAL -->
                <div class="confianza-section">
                    <div class="confianza-header">
                        <span>🎯 Confianza: <strong id="confianza_texto" style="color:#22d3ee;">0%</strong></span>
                        <span>Estabilidad: <strong id="estabilidad_texto" style="color:#cbd5e1;">0/4</strong></span>
                    </div>
                    <div class="confianza-bar-bg">
                        <div id="confianza_fill" class="confianza-bar-fill"></div>
                    </div>
                </div>

                <!-- TARJETA DE REACCIONES DEL CONEJO COSMO -->
                <div class="cosmo-card">
                    <div id="cosmo_avatar" class="cosmo-avatar">🐰🚀</div>
                    <div id="cosmo_mensaje" class="cosmo-speech">
                        ¡Hola! Presiona "Encender Cámara" para comenzar tu entrenamiento galáctico.
                    </div>
                </div>

                <!-- CONTROLES DE CÁMARA -->
                <div class="camera-controls">
                    <button onclick="arrancarJuegoIA()" class="btn-cam btn-cam-on">
                        🟢 Encender Cámara
                    </button>
                    <button onclick="apagarCamara()" class="btn-cam btn-cam-off">
                        🔴 Apagar Cámara
                    </button>
                </div>

                <!-- BOTÓN DE SIGUIENTE MISIÓN -->
                <button id="btn_siguiente" onclick="siguienteContenido()" class="btn-siguiente-mision">
                    ¡Misión Lograda! Siguiente ➔
                </button>
            </div>

        </div>
    </div>

    <!-- PANTALLA DE CELEBRACIÓN FINAL -->
    <div id="pantalla_completado">
        <h1 style="font-size:2.8rem; font-family:'Outfit'; color:#22d3ee; margin-bottom:10px;">🏆 ¡ESPECTACULAR!</h1>
        <h2 style="font-size:1.4rem; color:#cbd5e1; font-weight:600;">¡Has completado todas las señas con éxito galáctico!</h2>
        <div style="font-size:4rem; margin:20px 0;">🎉🚀🐰</div>
        <p style="color:#a855f7; font-weight:700;">Tu progreso ha sido registrado en tu bitácora oficial.</p>
        <a href="niveles.php" class="btn-volver-mapa">
            🚀 Volver al Mapa de Misiones
        </a>
    </div>

    <!-- AUDIO DE CELEBRACIÓN -->
    <audio id="audioVictoria" src="/LSB/assets/audio/victoria_nivel.wav" preload="auto"></audio>

    <script>
    const juegoDatos = <?= json_encode($lista_contenidos); ?>;
    let indiceActual = <?= (int)$indice_inicial ?>;

    let streamHardware = null;
    let model = null;
    let etiquetas = [];
    let iaCargada = false;
    let señaAprobada = false;
    let animacionId = null;

    // Control de tiempo para el registro de latencia
    let tiempoInicioLetra = Date.now();

    // Sistema de estabilización temporal
    const UMBRAL_CONFIANZA = 0.75; // 75% mínimo de confianza
    const FRAMES_REQUERIDOS = 4;   // 4 frames consecutivos estables
    let contadorFramesConsecutivos = 0;
    let ultimaClaseDetectada = "";

    // Integración de MediaPipe Hands
    let handsDetector = null;
    let manoDetectada = false;
    let landmarksActuales = null;

    const URL_MODELO = window.location.pathname.includes("/LSB/") ? "/LSB/modelo_ia/" : "../../modelo_ia/";
    const video = document.getElementById("webcam");
    const canvas = document.getElementById("overlay_canvas");
    const ctx = canvas ? canvas.getContext("2d") : null;

    const btnSiguiente = document.getElementById("btn_siguiente");
    const confianzaFill = document.getElementById("confianza_fill");
    const confianzaTexto = document.getElementById("confianza_texto");
    const estabilidadTexto = document.getElementById("estabilidad_texto");
    const cosmoMensaje = document.getElementById("cosmo_mensaje");
    const cosmoAvatar = document.getElementById("cosmo_avatar");
    const handBadge = document.getElementById("hand_status_badge");
    const audioVictoria = document.getElementById("audioVictoria");

    // Conexiones de los 21 puntos anatómicos de la mano para dibujar el esqueleto
    const CONEXIONES_MANO = [
        [0, 1], [1, 2], [2, 3], [3, 4],       // Pulgar
        [0, 5], [5, 6], [6, 7], [7, 8],       // Índice
        [5, 9], [9, 10], [10, 11], [11, 12],  // Medio
        [9, 13], [13, 14], [14, 15], [15, 16],// Anular
        [13, 17], [17, 18], [18, 19], [19, 20], // Meñique
        [0, 17]                               // Palma base
    ];

    function actualizarCosmo(mensaje, emote = "🐰🚀") {
        if (cosmoMensaje) cosmoMensaje.innerHTML = mensaje;
        if (cosmoAvatar) cosmoAvatar.textContent = emote;
    }

    function actualizarBarraConfianza(probabilidad, framesOk) {
        const porcentaje = Math.min(100, Math.max(0, (probabilidad * 100))).toFixed(1);
        if (confianzaFill) {
            confianzaFill.style.width = `${porcentaje}%`;
            if (probabilidad >= UMBRAL_CONFIANZA) {
                confianzaFill.className = "confianza-bar-fill success";
            } else if (probabilidad >= 0.45) {
                confianzaFill.className = "confianza-bar-fill warning";
            } else {
                confianzaFill.className = "confianza-bar-fill";
            }
        }
        if (confianzaTexto) confianzaTexto.innerText = `${porcentaje}%`;
        if (estabilidadTexto) estabilidadTexto.innerText = `${framesOk}/${FRAMES_REQUERIDOS}`;
    }

    function actualizarPantalla() {
        if (!juegoDatos || juegoDatos.length === 0) return;

        if (indiceActual >= juegoDatos.length) {
            document.getElementById("pantalla_juego").style.display = "none";
            document.getElementById("pantalla_completado").style.display = "block";
            lanzarCelebracion();
            apagarCamara();
            return;
        }

        señaAprobada = false;
        contadorFramesConsecutivos = 0;
        ultimaClaseDetectada = "";
        tiempoInicioLetra = Date.now();
        if (btnSiguiente) btnSiguiente.style.display = "none";
        actualizarBarraConfianza(0, 0);

        const item = juegoDatos[indiceActual];
        document.getElementById("guia_titulo").innerText = item.titulo;
        document.getElementById("guia_traduccion").innerText = item.traduccion;
        document.getElementById("contador_progreso").innerText = `${indiceActual + 1}/${juegoDatos.length}`;

        const imgEl = document.getElementById("guia_imagen");
        const fallbackEl = document.getElementById("letra_grande_fallback");

        if (item.imagen && item.imagen.trim() !== "") {
            imgEl.src = item.imagen;
            imgEl.style.display = "block";
            fallbackEl.style.display = "none";
        } else {
            imgEl.style.display = "none";
            fallbackEl.style.display = "block";
            fallbackEl.innerText = item.titulo.charAt(0).toUpperCase();
        }

        document.querySelectorAll('.btn-letra').forEach((btn, idx) => {
            if (idx === indiceActual) {
                btn.classList.add('activa');
                btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            } else {
                btn.classList.remove('activa');
            }
        });

        actualizarCosmo(`¡Haz la seña de la letra <strong>${item.titulo.toUpperCase()}</strong> frente a la cámara!`, "🐰✨");
    }

    function irALetra(nuevoIndice) {
        if (nuevoIndice < 0 || nuevoIndice >= juegoDatos.length) return;
        indiceActual = nuevoIndice;
        actualizarPantalla();
    }

    function reiniciarIntentoActual() {
        const item = juegoDatos[indiceActual] || {};
        const objetivo = (item.titulo || "").toUpperCase();

        // Enviar evento de reintento para que quede auditado
        guardarIntentoEnBD({
            id_contenido: item.id_contenido || 0,
            letra_esperada: objetivo,
            letra_reconocida: ultimaClaseDetectada || "REINTENTO",
            confianza: 0.0,
            tiempo_ms: Date.now() - tiempoInicioLetra,
            resultado: 'reintento'
        });

        contadorFramesConsecutivos = 0;
        señaAprobada = false;
        actualizarBarraConfianza(0, 0);
        tiempoInicioLetra = Date.now();
        actualizarCosmo(`¡Ánimo! Intenta de nuevo la seña <strong>${objetivo}</strong> fijándote en la foto guía.`, "🐰💪");
    }

    // CARGA DEL MODELO TEACHABLE MACHINE ORIGINAL DE LA USUARIA
    async function cargarModeloIA() {
        if (iaCargada && model) return;

        actualizarCosmo("Conectando tu modelo de Inteligencia Artificial (28 clases)...", "⏳🤖");

        const modelURL = URL_MODELO + "model.json";
        const metadataURL = URL_MODELO + "metadata.json";

        try {
            if (typeof tmImage !== "undefined" && tmImage.load) {
                model = await tmImage.load(modelURL, metadataURL);
                etiquetas = model.getClassLabels();
            } else if (typeof tf !== "undefined" && tf.loadLayersModel) {
                const layersModel = await tf.loadLayersModel(modelURL);
                const metaRes = await fetch(metadataURL);
                const metaData = await metaRes.json();
                etiquetas = metaData.labels || [];
                model = {
                    model: layersModel,
                    getClassLabels: () => etiquetas
                };
            } else {
                throw new Error("Librerías de TensorFlow / Teachable Machine no disponibles.");
            }

            console.log("✅ Modelo Teachable Machine cargado. 28 Clases:", etiquetas);
            iaCargada = true;
        } catch (err) {
            console.error("Error al cargar modelo IA:", err);
            actualizarCosmo("No se pudo cargar el modelo IA en /modelo_ia/.", "❌🤖");
            throw err;
        }
    }

    // INICIALIZACIÓN DE MEDIAPIPE HANDS
    function inicializarMediaPipe() {
        if (typeof Hands === "undefined") {
            console.warn("MediaPipe Hands no disponible vía CDN. Se continuará con modo directo.");
            return;
        }

        try {
            handsDetector = new Hands({
                locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/hands/${file}`
            });

            handsDetector.setOptions({
                maxNumHands: 1,
                modelComplexity: 0, // 0 = Modo Lite optimizado para alta tasa de cuadros
                minDetectionConfidence: 0.5,
                minTrackingConfidence: 0.5
            });

            handsDetector.onResults(onMediaPipeResults);
            console.log("✅ MediaPipe Hands inicializado.");
        } catch (e) {
            console.warn("Error al inicializar MediaPipe Hands:", e);
        }
    }

    function onMediaPipeResults(results) {
        if (!canvas || !ctx) return;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (results.multiHandLandmarks && results.multiHandLandmarks.length > 0) {
            manoDetectada = true;
            landmarksActuales = results.multiHandLandmarks[0];

            if (handBadge) {
                handBadge.innerHTML = "🟢 Mano Detectada (21 puntos)";
                handBadge.style.borderColor = "#22c55e";
                handBadge.style.color = "#4ade80";
            }

            // Dibujar esqueleto ciberespacial amigable
            dibujarEsqueletoMano(landmarksActuales);

        } else {
            manoDetectada = false;
            landmarksActuales = null;
            if (handBadge) {
                handBadge.innerHTML = "🟡 Buscando mano...";
                handBadge.style.borderColor = "#f59e0b";
                handBadge.style.color = "#fbbf24";
            }
        }
    }

    function dibujarEsqueletoMano(landmarks) {
        const w = canvas.width;
        const h = canvas.height;

        // 1. Dibujar huesos neón
        ctx.strokeStyle = "rgba(168, 85, 247, 0.75)";
        ctx.lineWidth = 3;
        for (const [i, j] of CONEXIONES_MANO) {
            const p1 = landmarks[i];
            const p2 = landmarks[j];
            ctx.beginPath();
            ctx.moveTo(p1.x * w, p1.y * h);
            ctx.lineTo(p2.x * w, p2.y * h);
            ctx.stroke();
        }

        // 2. Dibujar nodos articulares
        for (let i = 0; i < landmarks.length; i++) {
            const p = landmarks[i];
            ctx.fillStyle = (i === 4 || i === 8 || i === 12 || i === 16 || i === 20) ? "#22d3ee" : "#a855f7";
            ctx.beginPath();
            ctx.arc(p.x * w, p.y * h, 4.5, 0, 2 * Math.PI);
            ctx.fill();
        }
    }

    // ARRANQUE DEL HARDWARE DE CÁMARA
    async function arrancarJuegoIA() {
        if (streamHardware) return;

        actualizarCosmo("Activando cámara espacial y cargando redes neuronales...", "⏳📷");

        try {
            await cargarModeloIA();
            inicializarMediaPipe();

            streamHardware = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: "user" },
                audio: false
            });

            video.srcObject = streamHardware;
            await video.play();

            if (canvas) {
                canvas.width = video.videoWidth || 640;
                canvas.height = video.videoHeight || 480;
            }

            actualizarCosmo("¡Cámara lista! Coloca tu mano frente a la lente y haz la seña.", "🐰🚀");
            loopPrediccion();

        } catch (error) {
            console.error("Error al encender cámara:", error);
            actualizarCosmo("No se pudo conectar la cámara. Verifica los permisos de tu navegador.", "❌📷");
            apagarCamara();
        }
    }

    async function loopPrediccion() {
        if (!streamHardware) return;

        // 1. Procesar landmarks con MediaPipe si está activo
        if (handsDetector && video.readyState >= 2) {
            try {
                await handsDetector.send({ image: video });
            } catch (e) {
                // Si MediaPipe falla temporalmente, no detener la predicción principal
            }
        }

        // 2. Clasificar con tu modelo Teachable Machine
        await clasificarConModeloTM();

        if (streamHardware) {
            animacionId = requestAnimationFrame(loopPrediccion);
        }
    }

    // CLASIFICADOR ESTABILIZADO CON EL MODELO ORIGINAL
    async function clasificarConModeloTM() {
        if (!model || !video || video.readyState < 2 || señaAprobada) return;

        const item = juegoDatos[indiceActual] || {};
        const objetivo = (item.titulo || "").toUpperCase().trim();

        // Si MediaPipe está activo y no hay mano en la escena, pausar inferencia
        if (handsDetector && !manoDetectada) {
            actualizarBarraConfianza(0, 0);
            contadorFramesConsecutivos = 0;
            actualizarCosmo("¡No veo tu mano en la cámara! Levántala al centro 🐰👋", "🐰✋");
            return;
        }

        try {
            // CONSERVA EXACTAMENTE EL FORMATO ESPERADO POR TU MODELO: [96, 96, 1] Grayscale
            const tensor = tf.tidy(() => {
                let img = tf.browser.fromPixels(video);
                img = tf.image.resizeBilinear(img, [96, 96]);
                img = tf.image.rgbToGrayscale(img);
                img = img.toFloat().div(255);
                return img.expandDims(0);
            });

            const layersModel = (model && model.model) ? model.model : model;
            const predictOutput = layersModel.predict(tensor);
            const probabilidades = await predictOutput.data();

            tensor.dispose();
            predictOutput.dispose();

            const listaEtiquetas = etiquetas.length > 0 ? etiquetas : (model.getClassLabels ? model.getClassLabels() : []);

            // Encontrar la clase con mayor probabilidad
            let mejorIdx = 0;
            for (let i = 1; i < probabilidades.length; i++) {
                if (probabilidades[i] > probabilidades[mejorIdx]) {
                    mejorIdx = i;
                }
            }

            const claseDetectada = (listaEtiquetas[mejorIdx] || "---").toUpperCase().trim();
            const probabilidad = probabilidades[mejorIdx] || 0.0;
            ultimaClaseDetectada = claseDetectada;

            // SISTEMA DE ESTABILIZACIÓN TEMPORAL:
            // Requiere que la letra objetivo sea detectada con confianza >= 75% durante 4 frames consecutivos
            if (claseDetectada === objetivo && probabilidad >= UMBRAL_CONFIANZA) {
                contadorFramesConsecutivos++;
                actualizarBarraConfianza(probabilidad, contadorFramesConsecutivos);

                if (contadorFramesConsecutivos < FRAMES_REQUERIDOS) {
                    actualizarCosmo(`¡Casi listo! Mantén la seña <strong>${objetivo}</strong> quieta... (${contadorFramesConsecutivos}/${FRAMES_REQUERIDOS})`, "🐰💪");
                } else if (!señaAprobada) {
                    // ¡SEÑA APROBADA CON ÉXITO Y ESTABILIDAD COMPROBADA!
                    señaAprobada = true;
                    actualizarBarraConfianza(probabilidad, FRAMES_REQUERIDOS);
                    actualizarCosmo(`🎉 ¡EXTRAORDINARIO! Seña <strong>${objetivo}</strong> realizada con éxito (${(probabilidad * 100).toFixed(1)}%).`, "🐰🚀🎉");

                    if (btnSiguiente) btnSiguiente.style.display = "block";

                    // Celebración visual y sonora
                    lanzarCelebracion();

                    // PERSISTENCIA REAL EN MARIADB (Con el usuario de la sesión PHP)
                    const tiempoTotal = Date.now() - tiempoInicioLetra;
                    guardarIntentoEnBD({
                        id_contenido: item.id_contenido || 0,
                        letra_esperada: objetivo,
                        letra_reconocida: claseDetectada,
                        confianza: probabilidad,
                        tiempo_ms: tiempoTotal,
                        resultado: 'acierto'
                    });
                }
            } else {
                // Si la predicción titubea, reiniciar contador de estabilización
                contadorFramesConsecutivos = Math.max(0, contadorFramesConsecutivos - 1);
                actualizarBarraConfianza(probabilidad, contadorFramesConsecutivos);

                if (claseDetectada !== "FONDOS" && claseDetectada !== "---" && claseDetectada !== objetivo) {
                    actualizarCosmo(`Detecto la letra <strong>${claseDetectada}</strong>. Revisa la foto para hacer la <strong>${objetivo}</strong>.`, "🐰🧐");
                } else {
                    actualizarCosmo(`Acomoda tu mano para formar la seña <strong>${objetivo}</strong>.`, "🐰✋");
                }
            }

        } catch (err) {
            console.error("Error en inferencia:", err);
        }
    }

    // PERSISTENCIA ASÍNCRONA SEGURA EN MARIADB
    async function guardarIntentoEnBD(payload) {
        try {
            const resp = await fetch("/LSB/api/guardar_intento.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "same-origin", // Envía la cookie de sesión PHP de forma segura
                body: JSON.stringify(payload)
            });

            const data = await resp.json();
            if (data.success) {
                console.log("💾 Intento guardado en MariaDB exitosamente:", data);
            } else {
                console.warn("Respuesta al guardar progreso:", data.error);
            }
        } catch (e) {
            console.error("Error al conectar con /LSB/api/guardar_intento.php:", e);
        }
    }

    function lanzarCelebracion() {
        if (typeof confetti === "function") {
            confetti({
                particleCount: 80,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
        if (audioVictoria) {
            audioVictoria.currentTime = 0;
            audioVictoria.play().catch(() => {});
        }
    }

    function apagarCamara() {
        if (animacionId) {
            cancelAnimationFrame(animacionId);
            animacionId = null;
        }

        if (streamHardware) {
            streamHardware.getTracks().forEach(track => track.stop());
            streamHardware = null;
        }

        if (video) video.srcObject = null;
        if (ctx && canvas) ctx.clearRect(0, 0, canvas.width, canvas.height);

        actualizarBarraConfianza(0, 0);
        actualizarCosmo("Cámara apagada. Presiona 'Encender Cámara' para volver a jugar.", "⚙️🐰");
    }

    function siguienteContenido() {
        indiceActual++;
        actualizarPantalla();
    }

    function toggleSonido() {
        const v = document.getElementById('bgVideo');
        const icon = document.getElementById('soundIcon');
        const text = document.getElementById('soundText');
        const btn = document.getElementById('btnSound');

        if (!v) return;

        if (v.muted) {
            v.muted = false;
            v.volume = 0.5;
            v.play().then(() => {
                icon.textContent = '🔊';
                text.textContent = 'Audio: Activo';
                btn.classList.add('sound-on');
            }).catch(() => {});
        } else {
            v.muted = true;
            icon.textContent = '🔇';
            text.textContent = 'Audio';
            btn.classList.remove('sound-on');
        }
    }

    window.onload = function() {
        actualizarPantalla();
    };
    </script>
</body>
</html>
