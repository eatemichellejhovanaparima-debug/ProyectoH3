<?php
header("Content-Type: application/json");
include("../../config/conexion.php");

// Genera la encriptación segura requerida por password_verify()
$password_encriptada = password_hash("Admin123*", PASSWORD_DEFAULT);

// Actualiza al Profesor Administrador (id_usuario 7)
$query = "UPDATE usuarios SET password = '$password_encriptada' WHERE id_usuario = 7";
$resultado = mysqli_query($conexion, $query);

if ($resultado) {
    echo json_encode([
        "status" => "success", 
        "message" => "¡Contraseña de Administrador encriptada y corregida con éxito!"
    ]);
} else {
    echo json_encode([
        "status" => "error", 
        "message" => mysqli_error($conexion)
    ]);
}
?>
