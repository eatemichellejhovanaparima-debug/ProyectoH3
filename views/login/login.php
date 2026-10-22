<?php
session_start();
// Si ya hay una sesión activa, redirigir según el rol para evitar doble login
if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'admin') {
        header("Location: /LSB/views/admin/dashboard.php");
    } else {
        header("Location: /LSB/views/juego/niveles.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HANDY - Misión Espacial de Aprendizaje</title>
    
    <!-- Tipografías modernas y amigables para niños -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800;900&family=Quicksand:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- GOOGLE RECAPTCHA (Carga prioritaria) -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <style>
        :root {
            --space-violet: #8b5cf6;
            --space-purple: #a855f7;
            --space-blue: #3b82f6;
            --space-cyan: #06b6d4;
            --space-cyan-glow: #22d3ee;
            --space-pink: #ec4899;
            --space-pink-glow: #f43f5e;
            --dark-glass: rgba(18, 12, 38, 0.72);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Quicksand', 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            background-color: #070312;
            color: #ffffff;
            position: relative;
            padding: 20px;
        }

        /* VIDEO DE FONDO DEL CONEJO ESPACIAL */
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
            filter: brightness(0.92) contrast(1.05);
        }

        /* CAPA DEGRADADA OSCURA PARA MAXIMIZAR LEGIBILIDAD */
        .space-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: radial-gradient(circle at center, rgba(16, 9, 36, 0.6) 0%, rgba(10, 5, 26, 0.82) 65%, rgba(6, 2, 18, 0.94) 100%);
            z-index: -2;
            pointer-events: none;
        }

        /* ESTRELLAS ANIMADAS SUTILES SIN SATURAR */
        .starfield {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }

        .star {
            position: absolute;
            background-color: #ffffff;
            border-radius: 50%;
            box-shadow: 0 0 8px #22d3ee, 0 0 14px #a855f7;
            animation: twinkle 3s infinite ease-in-out alternate;
        }

        .star:nth-child(1) { top: 15%; left: 12%; width: 3px; height: 3px; animation-duration: 2.5s; }
        .star:nth-child(2) { top: 25%; right: 18%; width: 4px; height: 4px; animation-duration: 3.8s; }
        .star:nth-child(3) { top: 70%; left: 8%; width: 3px; height: 3px; animation-duration: 3.2s; }
        .star:nth-child(4) { top: 80%; right: 14%; width: 4px; height: 4px; animation-duration: 2.8s; }
        .star:nth-child(5) { top: 40%; left: 22%; width: 2px; height: 2px; animation-duration: 4.2s; }
        .star:nth-child(6) { top: 60%; right: 28%; width: 3px; height: 3px; animation-duration: 3.5s; }

        @keyframes twinkle {
            0% { opacity: 0.2; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.3); }
            100% { opacity: 0.3; transform: scale(0.9); }
        }

        /* BOTÓN DE CONTROL DE SONIDO */
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
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.4), 0 0 15px rgba(139, 92, 246, 0.25);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-sound-control:hover {
            transform: translateY(-2px) scale(1.03);
            border-color: var(--space-cyan-glow);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.5), 0 0 20px rgba(34, 211, 238, 0.4);
        }

        .btn-sound-control.sound-on {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.2);
            box-shadow: 0 4px 18px rgba(34, 197, 94, 0.35);
        }

        /* TARJETA MODERNA DE LOGIN (GLASSMORPHISM ESPACIAL) */
        .cosmic-card {
            background: var(--dark-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1.5px solid rgba(168, 85, 247, 0.35);
            border-radius: 26px;
            padding: 42px 34px;
            width: 100%;
            max-width: 430px;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.75), 0 0 40px rgba(139, 92, 246, 0.25);
            position: relative;
            z-index: 10;
            animation: cosmicEntrance 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cosmicEntrance {
            0% {
                opacity: 0;
                transform: translateY(28px) scale(0.96);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* BADGE TEMÁTICO DE CONEJO / ASTRONAUTA */
        .mascot-badge {
            width: 72px;
            height: 72px;
            margin: 0 auto 12px;
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.2) 0%, rgba(168, 85, 247, 0.3) 100%);
            border: 2px solid var(--space-cyan-glow);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4rem;
            box-shadow: 0 0 25px rgba(34, 211, 238, 0.4), inset 0 0 15px rgba(168, 85, 247, 0.3);
            animation: floatMascot 3.2s ease-in-out infinite alternate;
        }

        @keyframes floatMascot {
            0% { transform: translateY(0px) rotate(-3deg); }
            100% { transform: translateY(-7px) rotate(4deg); }
        }

        /* TÍTULO HANDY CON ANIMACIÓN Y GRADIENTE ESPACIAL */
        .cosmic-title {
            font-family: 'Outfit', sans-serif;
            font-size: 3.2rem;
            font-weight: 900;
            letter-spacing: 2px;
            margin-bottom: 4px;
            background: linear-gradient(135deg, #22d3ee 0%, #c084fc 45%, #f43f5e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 22px rgba(168, 85, 247, 0.6));
            animation: pulseGlow 4s ease-in-out infinite alternate;
        }

        @keyframes pulseGlow {
            0% { filter: drop-shadow(0 0 14px rgba(34, 211, 238, 0.5)); }
            100% { filter: drop-shadow(0 0 28px rgba(236, 72, 153, 0.7)); }
        }

        .cosmic-subtitle {
            font-size: 1.05rem;
            color: #cbd5e1;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .cosmic-subtitle span.highlight {
            color: var(--space-cyan-glow);
            font-weight: 700;
        }

        /* CAMPOS DEL FORMULARIO */
        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 0.88rem;
            font-weight: 700;
            color: #cbd5e1;
            margin-bottom: 6px;
            margin-left: 4px;
            font-family: 'Outfit', sans-serif;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            font-size: 1.2rem;
            pointer-events: none;
            opacity: 0.85;
        }

        .cosmic-input {
            width: 100%;
            padding: 14px 16px 14px 46px;
            background: rgba(255, 255, 255, 0.08);
            border: 1.5px solid rgba(168, 85, 247, 0.35);
            border-radius: 14px;
            color: #ffffff;
            font-family: 'Quicksand', sans-serif;
            font-size: 1.02rem;
            font-weight: 600;
            outline: none;
            transition: all 0.25s ease;
        }

        .cosmic-input::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }

        .cosmic-input:focus {
            background: rgba(255, 255, 255, 0.14);
            border-color: var(--space-cyan-glow);
            box-shadow: 0 0 20px rgba(34, 211, 238, 0.4), inset 0 0 10px rgba(34, 211, 238, 0.1);
            transform: translateY(-1px);
        }

        /* GOOGLE RECAPTCHA */
        .recaptcha-box {
            display: flex;
            justify-content: center;
            margin: 18px 0 20px;
            transform: scale(0.96);
            transform-origin: center;
        }

        /* BOTÓN DE INGRESAR (COSMIC BUTTON) */
        .btn-enter-mission {
            width: 100%;
            background: linear-gradient(135deg, #8b5cf6 0%, #d946ef 50%, #f43f5e 100%);
            color: #ffffff;
            border: none;
            padding: 15px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.18rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            border-radius: 14px;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(217, 70, 239, 0.45), 0 0 15px rgba(139, 92, 246, 0.35);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-enter-mission:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 32px rgba(217, 70, 239, 0.65), 0 0 25px rgba(34, 211, 238, 0.5);
            background: linear-gradient(135deg, #7c3aed 0%, #ec4899 50%, #f43f5e 100%);
        }

        .btn-enter-mission:active {
            transform: translateY(0) scale(0.99);
        }

        /* DIVISOR */
        .cosmic-divider {
            margin: 22px 0 18px;
            position: relative;
            text-align: center;
        }

        .cosmic-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(168, 85, 247, 0.4), transparent);
        }

        .cosmic-divider span {
            position: relative;
            background: #18102e;
            padding: 2px 14px;
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 10px;
            border: 1px solid rgba(168, 85, 247, 0.2);
        }

        /* BOTÓN CREAR CUENTA */
        .btn-register-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px;
            background: rgba(255, 255, 255, 0.06);
            border: 1.5px solid rgba(34, 211, 238, 0.4);
            color: #22d3ee;
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
            font-size: 1.02rem;
            font-weight: 700;
            border-radius: 14px;
            transition: all 0.25s ease;
        }

        .btn-register-link:hover {
            background: rgba(34, 211, 238, 0.15);
            border-color: #22d3ee;
            color: #ffffff;
            box-shadow: 0 0 20px rgba(34, 211, 238, 0.35);
            transform: translateY(-2px);
        }

        /* RESPONSIVE */
        @media (max-width: 480px) {
            .cosmic-card {
                padding: 30px 22px;
                border-radius: 20px;
            }
            .cosmic-title {
                font-size: 2.6rem;
            }
            .btn-sound-control {
                top: 14px;
                right: 14px;
                padding: 8px 14px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

    <!-- VIDEO DEL CONEJO ESPACIAL DE FONDO -->
    <div class="video-container">
        <video id="bgVideo" class="space-video-bg" autoplay loop muted playsinline preload="auto">
            <source src="/LSB/assets/videos/conejo_espacial.mp4" type="video/mp4">
            <source src="../../assets/videos/conejo_espacial.mp4" type="video/mp4">
            Tu navegador no soporta video HTML5.
        </video>
    </div>

    <!-- CAPA OSCURA Y ESTRELLAS -->
    <div class="space-overlay"></div>
    <div class="starfield">
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
    </div>

    <!-- BOTÓN PARA ACTIVAR / DESACTIVAR SONIDO (RESPETA POLÍTICAS DE AUTOPLAY) -->
    <button id="btnSound" class="btn-sound-control" onclick="toggleSonido()" type="button" aria-label="Control de Sonido">
        <span id="soundIcon">🔇</span>
        <span id="soundText">Audio</span>
    </button>

    <!-- TARJETA CÓSMICA DE INICIO DE SESIÓN -->
    <main class="cosmic-card">
        
        <!-- MASCOTA ESPACIAL -->
        <div class="mascot-badge" title="¡Bienvenido a HANDY!">
            🐰
        </div>

        <!-- TÍTULO ANIMADO -->
        <h1 class="cosmic-title">HANDY</h1>
        <p class="cosmic-subtitle">
            🚀 Misión: <span class="highlight">Inglés con Señas LSB</span>
        </p>

        <!-- FORMULARIO DE ACCESO (Mantiene el controlador y validaciones exactas) -->
        <form action="/LSB/controllers/login.php" method="POST" autocomplete="on">

            <!-- CAMPO: CORREO -->
            <div class="form-group">
                <label class="form-label" for="input-correo">Correo del Astronauta</label>
                <div class="input-wrapper">
                    <span class="input-icon">📧</span>
                    <input
                        id="input-correo"
                        class="cosmic-input"
                        type="email"
                        name="usuario"
                        placeholder="tu_correo@ejemplo.com"
                        required
                        autocomplete="email"
                    >
                </div>
            </div>

            <!-- CAMPO: CONTRASEÑA -->
            <div class="form-group">
                <label class="form-label" for="input-pass">Clave Secreta</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔐</span>
                    <input
                        id="input-pass"
                        class="cosmic-input"
                        type="password"
                        name="password"
                        placeholder="Tu contraseña espacial"
                        required
                        autocomplete="current-password"
                    >
                </div>
            </div>

            <!-- RECAPTCHA (Tus llaves originales intactas) -->
            <div class="recaptcha-box">
                <div class="g-recaptcha" data-sitekey="6LcOlOUsAAAAAEVbXi48HyCABjEVIXS-XJ_xrAyV"></div>
            </div>

            <!-- BOTÓN INGRESAR -->
            <button type="submit" class="btn-enter-mission">
                <span>🚀 Iniciar Aventura</span>
            </button>

        </form>

        <div class="cosmic-divider">
            <span>¿NUEVO VIAJERO?</span>
        </div>

        <!-- BOTÓN CREAR CUENTA -->
        <a href="/LSB/views/login/registro.php" class="btn-register-link">
            <span>✨ Crear Nueva Cuenta</span>
        </a>

    </main>

    <!-- SCRIPT DE CONTROL DE AUDIO INTERACTIVO -->
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
