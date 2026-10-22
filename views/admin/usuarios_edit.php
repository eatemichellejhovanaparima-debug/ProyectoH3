<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { die("Acceso denegado."); }
include("../../config/conexion.php");

// Capturar ID seguro
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener datos actuales del usuario
$query_user = "SELECT * FROM usuarios WHERE id_usuario = $id";
$resultado_user = mysqli_query($conexion, $query_user);
$user = mysqli_fetch_assoc($resultado_user);

if (!$user) {
    die("❌ Usuario no encontrado en el sistema.");
}

// Procesar el formulario de actualización al presionar Guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
    $rol = $_POST['rol'];
    $nueva_password = $_POST['password'];

    // Si escribió una nueva contraseña, actualizarla
    if (!empty($nueva_password)) {
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

        $query_update = "UPDATE usuarios 
                         SET nombre='$nombre', correo='$correo', rol='$rol', password='$password_hash' 
                         WHERE id_usuario=$id";
    } else {
        // Si dejó la contraseña vacía, conservar la actual
        $query_update = "UPDATE usuarios 
                         SET nombre='$nombre', correo='$correo', rol='$rol' 
                         WHERE id_usuario=$id";
    }

    if (mysqli_query($conexion, $query_update)) {
        header("Location: /LSB/views/admin/usuarios_read.php?msg=editado");
        exit();
    } else {
        echo "Error al actualizar: " . mysqli_error($conexion);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario - HANDY</title>
    <link rel="stylesheet" href="/LSB/assets/css/estilo.css">
</head>
<body style="background-color:#1e1412; color:#f1f5f9; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    
    <!-- TARJETA DE EDICIÓN CON DISEÑO PREMIUM -->
    <div style="background-color:#231a18; padding:40px; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,0.5); width:380px; border: 1px solid #334155;">
        <h2 style="color:#ff5733; margin-top:0; text-align:center; font-size:1.8rem; font-weight:600;">✏️ Editar Usuario</h2>
        <p style="text-align:center; color:#94a3b8; font-size:0.95rem; margin-bottom:25px;">Modifica los campos del estudiante o administrador</p>
        <hr style="border:0; border-top:1px solid #334155; margin-bottom:20px;">

        <form action="" method="POST">
            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.9rem; color:#cbd5e1;">Nombre Completo:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" style="width:100%; padding:12px; margin-bottom:20px; border-radius:8px; border:1px solid #475569; background-color:#1e1412; color:white; box-sizing:border-box; font-size:0.95rem; transition:border-color 0.2s;" onfocus="this.style.borderColor='#ff5733'; this.style.outline='none';" onblur="this.style.borderColor='#475569';" required>

            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.9rem; color:#cbd5e1;">Correo Electrónico:</label>
            <input type="email" name="correo" value="<?= htmlspecialchars($user['correo']) ?>" style="width:100%; padding:12px; margin-bottom:20px; border-radius:8px; border:1px solid #475569; background-color:#1e1412; color:white; box-sizing:border-box; font-size:0.95rem; transition:border-color 0.2s;" onfocus="this.style.borderColor='#ff5733'; this.style.outline='none';" onblur="this.style.borderColor='#475569';" required>

            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.9rem; color:#cbd5e1;">
    Nueva Contraseña:
</label>

<input 
    type="password" 
    name="password" 
    placeholder="Dejar vacío para conservar la actual"
    style="width:100%; padding:12px; margin-bottom:20px; border-radius:8px; border:1px solid #475569; background-color:#1e1412; color:white; box-sizing:border-box; font-size:0.95rem; transition:border-color 0.2s;"
    onfocus="this.style.borderColor='#ff5733'; this.style.outline='none';"
    onblur="this.style.borderColor='#475569';"
>

            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.9rem; color:#cbd5e1;">Rol del Sistema:</label>
            <select name="rol" style="width:100%; padding:12px; margin-bottom:25px; border-radius:8px; border:1px solid #475569; background-color:#1e1412; color:white; box-sizing:border-box; font-size:0.95rem; transition:border-color 0.2s;" onfocus="this.style.borderColor='#ff5733'; this.style.outline='none';" onblur="this.style.borderColor='#475569';">
                <option value="nino" <?= $user['rol'] === 'nino' ? 'selected' : '' ?>>Niño</option>
                <option value="admin" <?= $user['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>

            <button type="submit" style="width:100%; background-color:#ff5733; color:white; border:none; padding:14px; font-size:1rem; font-weight:bold; border-radius:8px; cursor:pointer; box-shadow:0 4px 12px rgba(255,87,51,0.25); transition:background 0.2s;" onmouseover="this.style.background='#e04d2b'" onmouseout="this.style.background='#ff5733'">Guardar Cambios</button>
        </form>
        
        <p style="text-align:center; margin-top:20px; font-size:0.95rem;"><a href="/LSB/views/admin/usuarios_read.php" style="color:#94a3b8; text-decoration:none; font-weight:500; transition:color 0.2s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#94a3b8'">Cancelar y regresar</a></p>
    </div>

</body>
</html>
