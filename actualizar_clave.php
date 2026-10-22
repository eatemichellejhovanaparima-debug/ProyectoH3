<?php
include("config/conexion.php");
$nueva_clave_encriptada = password_hash("Handy2026*", PASSWORD_BCRYPT);
mysqli_query($conexion, "UPDATE usuarios SET password='$nueva_clave_encriptada'");
echo "¡Contraseñas actualizadas a Handy2026* en la base de datos!";
?>
