<?php
session_start();
if (!isset($_SESSION['rol'])) { 
    header("Location: /LSB/views/login/login.php"); 
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Misiones Espaciales - HANDY</title>
    
    <!-- Tipografías espaciales modernas para niños -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800;900&family=Quicksand:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --space-violet: #8b5cf6;
            --space-purple: #a855f7;
            --space-blue: #3b82f6;
            --space-cyan: #06b6d4;
            --space-cyan-glow: #22d3ee;
            --space-pink: #ec4899;
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
            min-height: 100vh;
            background-color: #070312;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* FONDO DE VIDEO DEL CONEJO ESPACIAL */
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
            filter: brightness(0.85) contrast(1.1);
        }

        /* CAPA OSCURA CÓSMICA PARA GARANTIZAR CONTRASTE Y LECTURA */
        .space-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: radial-gradient(circle at center, rgba(16, 9, 36, 0.65) 0%, rgba(9, 4, 24, 0.86) 65%, rgba(5, 2, 16, 0.95) 100%);
            z-index: -2;
            pointer-events: none;
        }

        /* ESTRELLAS TITILANTES SUTILES */
        .starfield {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: -1;
        }

        .star {
            position: absolute;
            background-color: #ffffff;
            border-radius: 50%;
            box-shadow: 0 0 10px #22d3ee;
            animation: pulseStar 3.5s infinite ease-in-out alternate;
        }

        .star:nth-child(1) { top: 12%; left: 10%; width: 3px; height: 3px; }
        .star:nth-child(2) { top: 20%; right: 15%; width: 4px; height: 4px; animation-duration: 2.8s; }
        .star:nth-child(3) { top: 75%; left: 12%; width: 3px; height: 3px; animation-duration: 4s; }
        .star:nth-child(4) { top: 85%; right: 18%; width: 4px; height: 4px; animation-duration: 3.2s; }

        @keyframes pulseStar {
            0% { opacity: 0.25; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1.4); }
        }

        /* BOTÓN DE SONIDO FLOTANTE */
        .btn-sound-control {
            position: fixed;
            top: 22px;
            right: 22px;
            z-index: 100;
            background: rgba(18, 12, 38, 0.75);
            border: 1.5px solid rgba(168, 85, 247, 0.45);
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 50px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
        }

        .btn-sound-control:hover {
            transform: translateY(-2px) scale(1.03);
            border-color: var(--space-cyan-glow);
            box-shadow: 0 0 20px rgba(34, 211, 238, 0.4);
        }

        .btn-sound-control.sound-on {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.2);
        }

        /* CONTENEDOR PRINCIPAL DE LA NAVE / NIVELES */
        .mapa-container {
            width: 100%;
            max-width: 740px;
            background: rgba(20, 13, 42, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 45px 36px;
            border-radius: 28px;
            border: 1.5px solid rgba(168, 85, 247, 0.35);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 40px rgba(139, 92, 246, 0.25);
            text-align: center;
            position: relative;
            z-index: 10;
            animation: containerPop 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes containerPop {
            0% { opacity: 0; transform: translateY(30px) scale(0.96); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* INSIGNIA DEL ASTRONAUTA */
        .pilot-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
        }

        .pilot-avatar {
            width: 76px;
            height: 76px;
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.25) 0%, rgba(168, 85, 247, 0.35) 100%);
            border: 2px solid var(--space-cyan-glow);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.6rem;
            margin-bottom: 12px;
            box-shadow: 0 0 25px rgba(34, 211, 238, 0.4);
            animation: floatPilot 3s ease-in-out infinite alternate;
        }

        @keyframes floatPilot {
            0% { transform: translateY(0px) rotate(-3deg); }
            100% { transform: translateY(-6px) rotate(3deg); }
        }

        .titulo-mapa {
            font-family: 'Outfit', sans-serif;
            font-size: 2.4rem;
            font-weight: 900;
            margin-bottom: 6px;
            background: linear-gradient(135deg, #22d3ee 0%, #c084fc 50%, #f43f5e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 15px rgba(168, 85, 247, 0.5));
        }

        .subtitulo-mapa {
            color: #cbd5e1;
            font-size: 1.12rem;
            font-weight: 600;
            max-width: 520px;
            line-height: 1.4;
        }

        .linea-separador {
            border: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(168, 85, 247, 0.45), transparent);
            margin: 25px 0 32px;
        }

        /* TARJETAS DE NIVELES (PLANETAS DE VIDEOJUEGO) */
        .planetas-grid {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .card-planeta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(30, 20, 60, 0.65);
            border: 2px solid rgba(168, 85, 247, 0.35);
            border-radius: 20px;
            padding: 22px 28px;
            text-decoration: none;
            color: #ffffff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }

        /* PLANETA 1: CIAN / VIOLETA */
        .card-planeta-1 {
            border-color: rgba(34, 211, 238, 0.4);
        }
        .card-planeta-1:hover {
            transform: translateY(-5px) scale(1.015);
            background: rgba(36, 24, 72, 0.85);
            border-color: var(--space-cyan-glow);
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.6), 0 0 30px rgba(34, 211, 238, 0.45);
        }

        /* PLANETA 2: ROSA / AMBER */
        .card-planeta-2 {
            border-color: rgba(244, 63, 94, 0.4);
        }
        .card-planeta-2:hover {
            transform: translateY(-5px) scale(1.015);
            background: rgba(36, 24, 72, 0.85);
            border-color: var(--space-pink-glow);
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.6), 0 0 30px rgba(244, 63, 94, 0.45);
        }

        .planeta-left {
            display: flex;
            align-items: center;
            gap: 20px;
            text-align: left;
        }

        /* INSIGNIA NUMÉRICA ESTILO ORBE */
        .orbe-nivel {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 900;
            flex-shrink: 0;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease;
        }

        .card-planeta:hover .orbe-nivel {
            transform: scale(1.1) rotate(6deg);
        }

        .orbe-1 {
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 50%, #8b5cf6 100%);
            border: 2px solid #22d3ee;
            color: #ffffff;
            box-shadow: 0 0 20px rgba(34, 211, 238, 0.5);
        }

        .orbe-2 {
            background: linear-gradient(135deg, #f43f5e 0%, #ec4899 50%, #f59e0b 100%);
            border: 2px solid #fb7185;
            color: #ffffff;
            box-shadow: 0 0 20px rgba(244, 63, 94, 0.5);
        }

        .planeta-info h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            margin-bottom: 4px;
            color: #ffffff;
            letter-spacing: 0.3px;
        }

        .planeta-info p {
            color: #94a3b8;
            font-size: 0.96rem;
            font-weight: 600;
            margin: 0;
        }

        /* BOTÓN DE ACCIÓN INTERNA */
        .btn-lanzamiento {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 12px 22px;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.02rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            flex-shrink: 0;
            transition: all 0.25s ease;
        }

        .btn-lanzamiento-1 {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
        }

        .card-planeta-1:hover .btn-lanzamiento-1 {
            box-shadow: 0 6px 22px rgba(34, 197, 94, 0.7);
            transform: scale(1.05);
        }

        .btn-lanzamiento-2 {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .card-planeta-2:hover .btn-lanzamiento-2 {
            box-shadow: 0 6px 22px rgba(59, 130, 246, 0.7);
            transform: scale(1.05);
        }

        /* BOTÓN DE SALIDA */
        .btn-out {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: rgba(220, 38, 38, 0.15);
            color: #f87171;
            border: 1.5px solid rgba(239, 68, 68, 0.4);
            padding: 12px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 32px;
            transition: all 0.25s ease;
        }

        .btn-out:hover {
            background: rgba(220, 38, 38, 0.35);
            border-color: #ef4444;
            color: #ffffff;
            box-shadow: 0 0 18px rgba(239, 68, 68, 0.45);
            transform: translateY(-2px);
        }

        /* RESPONSIVE */
        @media (max-width: 600px) {
            .mapa-container {
                padding: 30px 20px;
                border-radius: 20px;
            }
            .titulo-mapa {
                font-size: 1.9rem;
            }
            .card-planeta {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                padding: 18px;
            }
            .btn-lanzamiento {
                width: 100%;
                justify-content: center;
            }
            .btn-sound-control {
                top: 12px;
                right: 12px;
                padding: 8px 14px;
                font-size: 0.85rem;
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

    <!-- CAPA OSCURA CÓSMICA Y ESTRELLAS -->
    <div class="space-overlay"></div>
    <div class="starfield">
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
    </div>

    <!-- BOTÓN DE SONIDO (MUTED POR DEFECTO PARA RESPETAR POLÍTICAS DEL NAVEGADOR) -->
    <button id="btnSound" class="btn-sound-control" onclick="toggleSonido()" type="button" aria-label="Control de Sonido">
        <span id="soundIcon">🔇</span>
        <span id="soundText">Audio</span>
    </button>

    <!-- NAVE / MAPA DE NIVELES -->
    <main class="mapa-container">
        
        <div class="pilot-header">
            <div class="pilot-avatar" title="Astronauta de HANDY">
                👨‍🚀
            </div>
            <h1 class="titulo-mapa">¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>!</h1>
            <p class="subtitulo-mapa">Elige tu planeta de entrenamiento y desbloquea el poder de la lengua de señas con la IA:</p>
        </div>

        <hr class="linea-separador">

        <div class="planetas-grid">
            
            <!-- NIVEL 1: ABECEDARIO LSB -->
            <a href="ventana_juego.php?cat=abecedario" class="card-planeta card-planeta-1" title="Iniciar Misión 1">
                <div class="planeta-left">
                    <div class="orbe-nivel orbe-1">1</div>
                    <div class="planeta-info">
                        <h3>🪐 Módulo: Abecedario LSB</h3>
                        <p>Domina las 27 letras del alfabeto boliviano practicando con tu cámara e IA.</p>
                    </div>
                </div>
                <div class="btn-lanzamiento btn-lanzamiento-1">
                    <span>▶️ INICIAR</span>
                </div>
            </a>

            <!-- NIVEL 2: PALABRAS BÁSICAS EN INGLÉS -->
            <a href="ventana_juego.php?cat=palabra_basica" class="card-planeta card-planeta-2" title="Iniciar Misión 2">
                <div class="planeta-left">
                    <div class="orbe-nivel orbe-2">2</div>
                    <div class="planeta-info">
                        <h3>🚀 Módulo: Palabras en Inglés</h3>
                        <p>Aprende vocabulario clave y expresiones en inglés mediante señas divertidas.</p>
                    </div>
                </div>
                <div class="btn-lanzamiento btn-lanzamiento-2">
                    <span>▶️ INICIAR</span>
                </div>
            </a>

        </div>

        <a href="/LSB/controllers/logout.php" class="btn-out">
            <span>🚪 Salir de la Misión</span>
        </a>

    </main>

    <!-- SCRIPT DE SONIDO INTERACTIVO -->
    <script>
        function toggleSonido() {
            const video = document.getElementById('bgVideo');
            const icon = document.getElementById('soundIcon');
            const text = document.getElementById('soundText');
            const btn = document.getElementById('btnSound');

            if (!video) return;

            if (video.muted) {
                video.muted = false;
                video.volume = 0.6;
                const playPromise = video.play();
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        icon.textContent = '🔊';
                        text.textContent = 'Audio: Activo';
                        btn.classList.add('sound-on');
                    }).catch(error => {
                        console.warn("Interacción requerida por el navegador para reproducir audio:", error);
                    });
                }
            } else {
                video.muted = true;
                icon.textContent = '🔇';
                text.textContent = 'Audio';
                btn.classList.remove('sound-on');
            }
        }
    </script>

</body>
</html>
