<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - HANDY</title>
    
    <!-- Tipografías espaciales -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800;900&family=Quicksand:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --space-violet: #8b5cf6;
            --space-purple: #a855f7;
            --space-cyan-glow: #22d3ee;
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
            background-color: #070312;
            color: #ffffff;
            position: relative;
            padding: 20px;
            overflow-x: hidden;
        }

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

        .cosmic-card {
            background: var(--dark-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1.5px solid rgba(168, 85, 247, 0.35);
            border-radius: 26px;
            padding: 40px 32px;
            width: 100%;
            max-width: 440px;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.75), 0 0 40px rgba(139, 92, 246, 0.25);
            position: relative;
            z-index: 10;
        }

        .mascot-badge {
            width: 66px;
            height: 66px;
            margin: 0 auto 10px;
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.2) 0%, rgba(168, 85, 247, 0.3) 100%);
            border: 2px solid var(--space-cyan-glow);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            box-shadow: 0 0 25px rgba(34, 211, 238, 0.4);
        }

        .cosmic-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.4rem;
            font-weight: 800;
            margin-bottom: 6px;
            background: linear-gradient(135deg, #22d3ee 0%, #c084fc 50%, #f43f5e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .cosmic-subtitle {
            font-size: 0.98rem;
            color: #cbd5e1;
            font-weight: 600;
            margin-bottom: 22px;
        }

        .form-group {
            margin-bottom: 14px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 0.86rem;
            font-weight: 700;
            color: #cbd5e1;
            margin-bottom: 5px;
            font-family: 'Outfit', sans-serif;
        }

        .cosmic-input {
            width: 100%;
            padding: 13px 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 1.5px solid rgba(168, 85, 247, 0.35);
            border-radius: 12px;
            color: #ffffff;
            font-family: 'Quicksand', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            outline: none;
            transition: all 0.25s ease;
        }

        .cosmic-input:focus {
            background: rgba(255, 255, 255, 0.14);
            border-color: var(--space-cyan-glow);
            box-shadow: 0 0 16px rgba(34, 211, 238, 0.4);
        }

        .btn-enter-mission {
            width: 100%;
            background: linear-gradient(135deg, #8b5cf6 0%, #d946ef 50%, #f43f5e 100%);
            color: #ffffff;
            border: none;
            padding: 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 800;
            border-radius: 14px;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(217, 70, 239, 0.45);
            transition: all 0.25s ease;
            margin-top: 10px;
        }

        .btn-enter-mission:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(217, 70, 239, 0.65);
        }

        .btn-back-link {
            display: inline-block;
            margin-top: 18px;
            color: #22d3ee;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .btn-back-link:hover {
            color: #ffffff;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="video-container">
        <video class="space-video-bg" autoplay loop muted playsinline preload="auto">
            <source src="/LSB/assets/videos/conejo_espacial.mp4" type="video/mp4">
            <source src="../../assets/videos/conejo_espacial.mp4" type="video/mp4">
        </video>
    </div>

    <div class="space-overlay"></div>

    <main class="cosmic-card">
        <div class="mascot-badge">🚀</div>
        <h1 class="cosmic-title">Nuevo Viajero</h1>
        <p class="cosmic-subtitle">Regístrate para comenzar tus misiones en HANDY</p>

        <form action="/LSB/controllers/registro.php" method="POST">
            <div class="form-group">
                <label class="form-label">Nombre del Astronauta</label>
                <input class="cosmic-input" type="text" name="nombre" placeholder="Tu nombre completo" required>
            </div>
            <div class="form-group">
                <label class="form-label">Correo Electrónico</label>
                <input class="cosmic-input" type="email" name="usuario" placeholder="correo@ejemplo.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña Segura</label>
                <input class="cosmic-input" type="password" name="password" placeholder="Mínimo 8 caracteres, mayúscula y número" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número" required>
            </div>
            <button type="submit" class="btn-enter-mission">Crear Cuenta Espacial 🌟</button>
        </form>

        <a href="/LSB/views/login/login.php" class="btn-back-link">⬅️ ¿Ya tienes cuenta? Volver al Login</a>
    </main>

</body>
</html>
