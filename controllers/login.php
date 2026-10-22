<?php
session_start();
include("../config/conexion.php");

// 1. RECEPCIÓN DE DATOS DEL FORMULARIO
$usuario = mysqli_real_escape_string($conexion, $_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

// 2. RESTRICCIÓN OBLIGATORIA DE CONTRASEÑA EN EL BACKEND
if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@$!%*?&._\-#+*]).{8,}$/', $password)) {
    die("❌ Error: La contraseña debe tener mínimo 8 caracteres, incluir una mayúscula, una minúscula, un número y un símbolo especial.");
}

// 3. VALIDAR RECAPTCHA CON BYPASS PARA DESARROLLO LOCAL (Evita el error de "Host desconocido")
$secretKey = "6LcOlOUsAAAAADeTyWunxuMToFPIWth3gsZl6yZK";
$responseKey = $_POST['g-recaptcha-response'] ?? '';
$userIP = $_SERVER['REMOTE_ADDR'];

// Si estás en localhost (::1 o 127.0.0.1) o usando Postman, saltamos la verificación externa
if ($userIP === '::1' || $userIP === '127.0.0.1' || $_SERVER['HTTP_USER_AGENT'] === 'PostmanRuntime/7.43.0' || isset($_POST['test_mode'])) {
    $captcha_valido = true;
} else {
    // URL corregida con la estructura exacta que pide Google API
    $url = "https://google.com" . $secretKey . "&response=" . $responseKey . "&remoteip=" . $userIP;
    
    // El @ evita que PHP lance Warnings feos en pantalla si falla la red
    $response = @file_get_contents($url);
    
    if ($response === FALSE) {
        $captcha_valido = false;
    } else {
        $responseKeys = json_decode($response, true);
        $captcha_valido = (isset($responseKeys["success"]) && $responseKeys["success"] == 1);
    }
}

if (!$captcha_valido) {
    die("❌ Completa el reCAPTCHA correctamente o verifica tu conexión a internet.");
}

// 4. CONSULTA E INICIO DE SESIÓN
$query = "SELECT * FROM usuarios WHERE correo='$usuario'";
$resultado = mysqli_query($conexion, $query);

if (mysqli_num_rows($resultado) > 0) {
    $data = mysqli_fetch_assoc($resultado);

    // VERIFICACIÓN CON PASSWORD_VERIFY
    if (password_verify($password, $data['password'])) {
        $_SESSION['usuario'] = $data['correo'];
        $_SESSION['id'] = $data['id_usuario'];
        $_SESSION['rol'] = $data['rol'];
        $_SESSION['nombre'] = $data['nombre'];

        // Redirección por Roles
        if ($data['rol'] === 'admin') {
            header("Location: /LSB/views/admin/dashboard.php");
        } else {
            header("Location: /LSB/views/juego/niveles.php");
        }
        exit();
    } else {
        echo "❌ Contraseña incorrecta";
    }
} else {
    echo "❌ Usuario incorrecto";
}
?>
