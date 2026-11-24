<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { die("Acceso denegado."); }
include("../../config/conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];

    // Validar si el correo ya existe
    $check = mysqli_query($conexion, "SELECT id_usuario FROM usuarios WHERE correo = '$correo'");
    if (mysqli_num_rows($check) > 0) {
        $error = "❌ Este correo ya está registrado en el sistema.";
    } else {
        // Encriptar contraseña por seguridad
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        $query_insert = "INSERT INTO usuarios (nombre, correo, password, rol) VALUES ('$nombre', '$correo', '$passwordHash', '$rol')";
        if (mysqli_query($conexion, $query_insert)) {
            header("Location: /LSB/views/admin/usuarios_read.php?msg=creado");
            exit();
        } else {
            $error = "Error al insertar: " . mysqli_error($conexion);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario - HANDY</title>
</head>
<body style="background-color:#1e1412; color:white; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; font-family:sans-serif;">
    
    <div style="background-color:#2c1d1a; padding:30px; border-radius:15px; box-shadow:0 8px 20px rgba(0,0,0,0.5); width:360px;">
        <h2 style="color:#22c55e; margin-top:0; text-align:center;">➕ Agregar Usuario</h2>
        <p style="text-align:center; color:#ccc;">Registra un nuevo estudiante o profesor administrador</p>
        <hr style="border:1px solid #442f2b;"><br>

        <?php if (isset($error)): ?>
            <p style="color: #ef4444; font-weight: bold; text-align: center;"><?= $error ?></p>
        <?php endif; ?>

        <form action="" method="POST">
            <label style="display:block; margin-bottom:5px; font-weight:bold;">Nombre Completo:</label>
            <input type="text" name="nombre" style="width:100%; padding:10px; margin-bottom:15px; border-radius:5px; border:none;" placeholder="Ej. Juan Choque" required>

            <label style="display:block; margin-bottom:5px; font-weight:bold;">Correo Electrónico:</label>
            <input type="email" name="correo" style="width:100%; padding:10px; margin-bottom:15px; border-radius:5px; border:none;" placeholder="correo@ejemplo.com" required>

            <label style="display:block; margin-bottom:5px; font-weight:bold;">Contraseña de Acceso:</label>
            <input type="password" name="password" style="width:100%; padding:10px; margin-bottom:15px; border-radius:5px; border:none;" placeholder="Mínimo 8 caracteres" required>

            <label style="display:block; margin-bottom:5px; font-weight:bold;">Rol del Sistema:</label>
            <select name="rol" style="width:100%; padding:10px; margin-bottom:20px; border-radius:5px; border:none;">
                <option value="nino">Niño (Estudiante)</option>
                <option value="admin">Administrador (Docente)</option>
            </select>

            <button type="submit" style="width:100%; background-color:#22c55e; color:white; border:none; padding:12px; font-size:1rem; font-weight:bold; border-radius:5px; cursor:pointer;">Crear Usuario</button>
        </form>
        
        <p style="text-align:center; margin-top:15px;"><a href="/LSB/views/admin/usuarios_read.php" style="color:#aaa; text-decoration:none;">Cancelar y regresar</a></p>
    </div>

</body>
</html>
