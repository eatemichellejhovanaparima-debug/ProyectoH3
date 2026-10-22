<?php
session_start();
if (!isset($_SESSION['rol'])) { 
    header("Location: /LSB/views/login/login.php"); 
    exit(); 
}
include("../../config/conexion.php");

$categoria = isset($_GET['cat']) ? mysqli_real_escape_string($conexion, $_GET['cat']) : 'abecedario';
$letra_url = isset($_GET['letra']) ? mb_strtoupper(trim($_GET['letra']), 'UTF-8') : '';

// LISTA CANÓNICA DEL ABECEDARIO ESPAÑOL CON LA Ñ EN SU LUGAR EXACTO ENTRE N Y O
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
    // 1. Recorrer de forma canónica estricta para garantizar: A, B, C, ..., N, Ñ, O, ..., Z
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
    // 2. Preservar cualquier otro contenido que pudiera estar en la base de datos
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

// Determinar el índice inicial si se solicitó una letra específica por URL
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
    <title>HANDY - Misión de Señas</title>

    <!-- Tipografías espaciales -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800;900&family=Quicksand:wght@600;700&display=swap" rel="stylesheet">

    <!-- LIBRERÍAS DE INTELIGENCIA ARTIFICIAL -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@latest/dist/teachablemachine-image.min.js"></script>

    <style>
        :root {
            --space-violet: #8b5cf6;
            --space-purple: #a855f7;
            --space-blue: #3b82f6;
            --space-cyan-glow: #22d3ee;
            --space-pink-glow: #f43f5e;
            --space-green: #22c55e;
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

        /* FONDO ANIMADO DE CONEJO ESPACIAL */
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

        /* CONTENEDOR DE LA VENTANA DE JUEGO */
        #pantalla_juego {
            width: 100%;
            max-width: 980px;
            background: rgba(22, 14, 45, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 24px;
            border: 1.5px solid rgba(168, 85, 247, 0.35);
            box-shadow: 0 20px 60px rgba(0,0,0,0.8), 0 0 35px rgba(139, 92, 246, 0.25);
            box-sizing: border-box;
            position: relative;
            z-index: 10;
        }

        .header-juego {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-volver {
            color: #22d3ee;
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.05rem;
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
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #22d3ee 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .badge-progreso {
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
            padding: 7px 18px;
            border-radius: 20px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(236, 72, 153, 0.35);
        }

        /* SELECTOR HORIZONTAL DE LETRAS DEL ABECEDARIO */
        .selector-abecedario-wrapper {
            margin-bottom: 22px;
            background: rgba(14, 8, 30, 0.65);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 12px 14px;
        }

        .selector-header-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 8px;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .selector-scroll {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding-bottom: 6px;
            scrollbar-width: thin;
            scrollbar-color: rgba(168, 85, 247, 0.5) rgba(255, 255, 255, 0.05);
        }

        .selector-scroll::-webkit-scrollbar {
            height: 6px;
        }

        .selector-scroll::-webkit-scrollbar-thumb {
            background: rgba(168, 85, 247, 0.5);
            border-radius: 6px;
        }

        .btn-letra {
            background: rgba(255, 255, 255, 0.07);
            border: 1.5px solid rgba(168, 85, 247, 0.35);
            color: #ffffff;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 800;
            cursor: pointer;
            flex-shrink: 0;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-letra:hover {
            background: rgba(34, 211, 238, 0.25);
            border-color: var(--space-cyan-glow);
            transform: translateY(-2px);
            color: #22d3ee;
        }

        .btn-letra.activa {
            background: linear-gradient(135deg, #06b6d4 0%, #a855f7 100%);
            border-color: #22d3ee;
            color: #ffffff;
            box-shadow: 0 0 14px rgba(34, 211, 238, 0.55);
            transform: scale(1.1);
        }

        /* COLUMNAS DE JUEGO (GUÍA Y CÁMARA) */
        .game-grid {
            display: flex;
            gap: 24px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .panel-juego {
            flex: 1;
            min-width: 320px;
            background: rgba(18, 11, 38, 0.7);
            padding: 24px;
            border-radius: 18px;
            border: 1.5px solid rgba(168, 85, 247, 0.3);
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .panel-header-sub {
            color: #22d3ee;
            font-family: 'Outfit', sans-serif;
            margin-top: 0;
            font-size: 1.05rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .guia-letra-grande {
            font-family: 'Outfit', sans-serif;
            font-size: 4.2rem;
            margin: 6px 0;
            color: #fff;
            font-weight: 900;
            text-shadow: 0 0 20px rgba(34, 211, 238, 0.4);
        }

        .guia-traduccion-txt {
            color: #94a3b8;
            font-size: 1.1rem;
            margin-bottom: 18px;
            font-weight: 600;
        }

        .contenedor-imagen-seña {
            width: 210px;
            height: 210px;
            background: rgba(10, 5, 22, 0.8);
            border-radius: 18px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.5);
            border: 2px solid rgba(168, 85, 247, 0.4);
            overflow: hidden;
            margin: 0 auto;
        }

        .contenedor-imagen-seña img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: none;
        }

        .letra-fallback-display {
            font-family: 'Outfit', sans-serif;
            font-size: 5.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #22d3ee 0%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* CÁMARA Y VIDEO */
        .video-wrapper {
            position: relative;
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            background: #000;
            box-shadow: 0 8px 25px rgba(0,0,0,0.6);
            aspect-ratio: 4/3;
            border: 1.5px solid rgba(168, 85, 247, 0.4);
        }

        .video-wrapper video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
            display: block;
            background: #000;
        }

        .camera-controls {
            display: flex;
            gap: 10px;
            margin-top: 14px;
            justify-content: center;
        }

        .btn-cam {
            padding: 12px 20px;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 0.95rem;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .btn-cam-on {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
        }

        .btn-cam-on:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.55);
        }

        .btn-cam-off {
            background: rgba(239, 68, 68, 0.2);
            border: 1.5px solid rgba(239, 68, 68, 0.4);
            color: #f87171;
        }

        .btn-cam-off:hover {
            background: rgba(239, 68, 68, 0.35);
            color: white;
        }

        /* ALERTAS DE ESTADO IA */
        #resultado_ia {
            margin-top: 16px;
            padding: 14px;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            background-color: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.35);
            font-size: 0.98rem;
            box-sizing: border-box;
            line-height: 1.4;
        }

        .btn-siguiente-mision {
            margin-top: 15px;
            width: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #10b981 100%);
            color: white;
            border: none;
            padding: 15px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 900;
            letter-spacing: 0.5px;
            border-radius: 14px;
            cursor: pointer;
            display: none;
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.45);
            animation: pulseBtn 1.8s infinite ease-in-out;
        }

        @keyframes pulseBtn {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        /* PANTALLA DE MISIÓN COMPLETADA */
        #pantalla_completado {
            display: none;
            width: 100%;
            max-width: 580px;
            background: rgba(22, 14, 45, 0.85);
            backdrop-filter: blur(20px);
            padding: 45px 36px;
            border-radius: 26px;
            border: 2px solid var(--space-cyan-glow);
            box-shadow: 0 20px 60px rgba(0,0,0,0.8), 0 0 40px rgba(34, 211, 238, 0.3);
            box-sizing: border-box;
            position: relative;
            z-index: 10;
        }

        #pantalla_completado h1 {
            color: #22c55e;
            font-family: 'Outfit', sans-serif;
            font-size: 3.4rem;
            margin-bottom: 10px;
        }

        #pantalla_completado h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            color: white;
            margin-bottom: 25px;
        }

        .btn-volver-mapa {
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
            padding: 16px 34px;
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.15rem;
            border-radius: 14px;
            display: inline-block;
            box-shadow: 0 8px 25px rgba(236, 72, 153, 0.4);
            transition: all 0.25s ease;
        }

        .btn-volver-mapa:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 12px 30px rgba(236, 72, 153, 0.6);
        }

        @media (max-width: 600px) {
            #pantalla_juego {
                padding: 20px 15px;
            }
            .titulo-mision {
                font-size: 1.4rem;
            }
            .panel-juego {
                min-width: 100%;
                padding: 18px;
            }
        }
    </style>
</head>
<body>

    <!-- FONDO DE VIDEO DEL CONEJO ESPACIAL -->
    <div class="video-container">
        <video id="bgVideo" class="space-video-bg" autoplay loop muted playsinline preload="auto">
            <source src="/LSB/assets/videos/conejo_espacial.mp4" type="video/mp4">
            <source src="../../assets/videos/conejo_espacial.mp4" type="video/mp4">
            Tu navegador no soporta video HTML5.
        </video>
    </div>

    <!-- CAPA OSCURA CÓSMICA -->
    <div class="space-overlay"></div>

    <!-- BOTÓN DE SONIDO -->
    <button id="btnSound" class="btn-sound-control" onclick="toggleSonido()" type="button" aria-label="Control de Sonido">
        <span id="soundIcon">🔇</span>
        <span id="soundText">Audio</span>
    </button>

    <!-- VENTANA DEL JUEGO -->
    <div id="pantalla_juego">
        
        <div class="header-juego">
            <a href="niveles.php" class="btn-volver">
                ⬅️ Mapa de Misiones
            </a>
            <h2 class="titulo-mision">🎯 <?= $categoria === 'abecedario' ? 'Abecedario LSB' : 'Módulo de Vocabulario' ?></h2>
            <span id="contador_progreso" class="badge-progreso">1/<?= count($lista_contenidos) ?></span>
        </div>

        <?php if ($categoria === 'abecedario'): ?>
        <!-- SELECTOR INTERACTIVO Y ORDENADO DE LETRAS (A - Z INCLUYENDO LA Ñ) -->
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
            
            <!-- GUÍA VISUAL -->
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

                <div style="margin-top:15px; font-size:0.88rem; color:#94a3b8;">
                    <?= $categoria === 'abecedario' ? 'Realiza la seña frente a la cámara web' : 'Practica el vocabulario en inglés' ?>
                </div>
            </div>

            <!-- CÁMARA ACTIVA -->
            <div class="panel-juego">
                <h3 class="panel-header-sub" style="color:#22c55e;">🎥 Cámara de Detección IA</h3>
                
                <div class="video-wrapper">
                    <video id="webcam" autoplay playsinline muted></video>
                </div>

                <div class="camera-controls">
                    <button onclick="arrancarJuegoIA()" class="btn-cam btn-cam-on">
                        🟢 Encender Cámara
                    </button>
                    <button onclick="apagarCamara()" class="btn-cam btn-cam-off">
                        🔴 Apagar Cámara
                    </button>
                </div>

                <!-- ALERTAS DINÁMICAS -->
                <div id="resultado_ia">
                    ⚙️ Presiona "Encender Cámara" para conectar tu webcam y el modelo de IA...
                </div>

                <button id="btn_siguiente" onclick="siguienteContenido()" class="btn-siguiente-mision">
                    ¡Misión Lograda! Siguiente ➔
                </button>
            </div>

        </div>
    </div>

    <!-- PANTALLA DE MISIÓN COMPLETADA -->
    <div id="pantalla_completado">
        <h1>🏆 ¡Excelente!</h1>
        <h2>¡Has completado todas las señas con éxito!</h2>
        <a href="niveles.php" class="btn-volver-mapa">
            🚀 Volver al Mapa de Misiones
        </a>
    </div>

    <script>
    const juegoDatos = <?= json_encode($lista_contenidos); ?>;

    let indiceActual = <?= (int)$indice_inicial ?>;
    let streamHardware = null;
    let model = null;
    let etiquetas = [];
    let iaCargada = false;
    let señaAprobada = false;
    let animacionId = null;

    // Ruta base al modelo IA (soporta tanto /LSB/ como rutas relativas)
    const URL_MODELO = window.location.pathname.includes("/LSB/")
        ? "/LSB/modelo_ia/"
        : "../../modelo_ia/";

    const video = document.getElementById("webcam");
    const resultadoDiv = document.getElementById("resultado_ia");
    const btnSiguiente = document.getElementById("btn_siguiente");

    function mostrarEstado(mensaje, tipo = "info") {
        if (!resultadoDiv) return;
        resultadoDiv.innerHTML = mensaje;
        if (tipo === "error") {
            resultadoDiv.style.backgroundColor = "rgba(239, 68, 68, 0.2)";
            resultadoDiv.style.color = "#f87171";
            resultadoDiv.style.border = "1px solid rgba(239, 68, 68, 0.4)";
        } else if (tipo === "success") {
            resultadoDiv.style.backgroundColor = "rgba(34, 197, 94, 0.25)";
            resultadoDiv.style.color = "#4ade80";
            resultadoDiv.style.border = "1px solid rgba(34, 197, 94, 0.6)";
        } else if (tipo === "warning") {
            resultadoDiv.style.backgroundColor = "rgba(245, 158, 11, 0.18)";
            resultadoDiv.style.color = "#fbbf24";
            resultadoDiv.style.border = "1px solid rgba(245, 158, 11, 0.35)";
        } else {
            resultadoDiv.style.backgroundColor = "rgba(59, 130, 246, 0.18)";
            resultadoDiv.style.color = "#60a5fa";
            resultadoDiv.style.border = "1px solid rgba(59, 130, 246, 0.35)";
        }
    }

    function actualizarPantalla() {
        if (!juegoDatos || juegoDatos.length === 0) {
            mostrarEstado("⚠️ No hay contenidos disponibles en esta categoría.", "warning");
            return;
        }

        if (indiceActual >= juegoDatos.length) {
            document.getElementById("pantalla_juego").style.display = "none";
            document.getElementById("pantalla_completado").style.display = "block";
            apagarCamara();
            return;
        }

        señaAprobada = false;
        if (btnSiguiente) btnSiguiente.style.display = "none";

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

        // Actualizar selector interactivo de letras
        document.querySelectorAll('.btn-letra').forEach((btn, idx) => {
            if (idx === indiceActual) {
                btn.classList.add('activa');
                btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            } else {
                btn.classList.remove('activa');
            }
        });

        if (streamHardware) {
            mostrarEstado(`🎯 Objetivo: <strong style="color:white; font-size:1.1rem;">${item.titulo.toUpperCase()}</strong>. Haz la seña frente a la cámara.`, "warning");
        }
    }

    // FUNCIÓN PARA SELECCIONAR UNA LETRA DIRECTAMENTE
    function irALetra(nuevoIndice) {
        if (nuevoIndice < 0 || nuevoIndice >= juegoDatos.length) return;
        indiceActual = nuevoIndice;
        actualizarPantalla();
    }

    async function cargarModeloIA() {
        if (iaCargada && model) return;

        mostrarEstado("⏳ Conectando modelo de Inteligencia Artificial...", "info");

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
                throw new Error("No se pudo cargar la librería de TensorFlow / Teachable Machine.");
            }

            console.log("Modelo IA cargado exitosamente. Clases:", etiquetas);
            iaCargada = true;
        } catch (err) {
            console.error("Error al cargar el modelo IA:", err);
            throw new Error("No se pudo cargar el modelo IA desde '" + modelURL + "'. Verifica que los archivos existan.");
        }
    }

    async function arrancarJuegoIA() {
        if (streamHardware) {
            mostrarEstado("ℹ️ La cámara ya se encuentra encendida.", "info");
            return;
        }

        mostrarEstado("⏳ Conectando cámara y cargando modelo IA...", "info");

        try {
            // 1. Cargar modelo IA
            await cargarModeloIA();

            // 2. Acceso al hardware de la cámara
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error("Tu navegador no soporta o bloquea el acceso a la cámara. Recuerda usar 'localhost' o HTTPS.");
            }

            streamHardware = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: "user"
                },
                audio: false
            });

            video.srcObject = streamHardware;

            // Esperar a que el video comience a reproducirse y cargue sus dimensiones
            await video.play();

            mostrarEstado("🚀 Cámara e IA listas. Haz la seña frente a la cámara.", "warning");

            // 3. Iniciar loop de clasificación
            loopPrediccion();

        } catch (error) {
            console.error("Error en arrancarJuegoIA:", error);
            let mensajeError = error.message || error;
            if (error.name === "NotAllowedError" || error.name === "PermissionDeniedError") {
                mensajeError = "Permiso de cámara denegado. Permite el acceso a la webcam en tu navegador.";
            } else if (error.name === "NotFoundError" || error.name === "DevicesNotFoundError") {
                mensajeError = "No se detectó ninguna cámara conectada en tu equipo.";
            } else if (error.name === "NotReadableError" || error.name === "TrackStartError") {
                mensajeError = "La cámara está ocupada por otra aplicación o no responde.";
            }
            mostrarEstado("❌ " + mensajeError, "error");
            apagarCamara();
        }
    }

    async function loopPrediccion() {
        if (!streamHardware) return;

        await clasificar();

        if (streamHardware) {
            animacionId = requestAnimationFrame(loopPrediccion);
        }
    }

    async function clasificar() {
        if (!model) return;
        if (!video || video.readyState < 2 || video.videoWidth === 0 || video.videoHeight === 0) {
            return;
        }

        try {
            const tensor = tf.tidy(() => {
                let img = tf.browser.fromPixels(video);
                img = tf.image.resizeBilinear(img, [96, 96]);
                img = tf.image.rgbToGrayscale(img);
                img = img.toFloat().div(255);
                return img.expandDims(0);
            });

            const layersModel = (model && model.model) ? model.model : model;
            const resultado = layersModel.predict(tensor);
            const probabilidades = await resultado.data();

            tensor.dispose();
            resultado.dispose();

            const listaEtiquetas = etiquetas.length > 0 ? etiquetas : (model.getClassLabels ? model.getClassLabels() : []);

            let mejorIndice = 0;
            for (let i = 1; i < probabilidades.length; i++) {
                if (probabilidades[i] > probabilidades[mejorIndice]) {
                    mejorIndice = i;
                }
            }

            const claseDetectada = listaEtiquetas[mejorIndice] || "---";
            const probabilidad = probabilidades[mejorIndice] || 0;
            const porcentaje = (probabilidad * 100).toFixed(1);

            const objetivo = (juegoDatos[indiceActual] && juegoDatos[indiceActual].titulo)
                ? juegoDatos[indiceActual].titulo.trim().toUpperCase()
                : "";

            if (!señaAprobada) {
                mostrarEstado(`
                    <div style="display:flex; justify-content:space-around; align-items:center;">
                        <div>🎯 Objetivo: <strong style="color:white; font-size:1.15rem;">${objetivo}</strong></div>
                        <div>🤖 Detectado: <strong style="color:${claseDetectada.toUpperCase() === objetivo ? '#4ade80' : '#fbbf24'}; font-size:1.15rem;">${claseDetectada}</strong> (${porcentaje}%)</div>
                    </div>
                `, "warning");
            }

            if (
                claseDetectada.toUpperCase() === objetivo &&
                probabilidad >= 0.80 &&
                !señaAprobada
            ) {
                señaAprobada = true;
                mostrarEstado(`🎉 ¡Excelente! Seña <strong>${objetivo}</strong> realizada con éxito (${porcentaje}%).`, "success");
                if (btnSiguiente) btnSiguiente.style.display = "block";
            }

        } catch (err) {
            console.error("Error en clasificar:", err);
        }
    }

    function apagarCamara() {
        if (animacionId) {
            cancelAnimationFrame(animacionId);
            animacionId = null;
        }

        if (streamHardware) {
            streamHardware.getTracks().forEach(track => {
                track.stop();
            });
            streamHardware = null;
        }

        if (video) {
            video.srcObject = null;
        }

        mostrarEstado("⚙️ Cámara apagada. Presiona \"Encender Cámara\" para volver a iniciar.", "warning");
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
            const playPromise = v.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    icon.textContent = '🔊';
                    text.textContent = 'Audio: Activo';
                    btn.classList.add('sound-on');
                }).catch(err => console.warn("Interacción requerida:", err));
            }
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
