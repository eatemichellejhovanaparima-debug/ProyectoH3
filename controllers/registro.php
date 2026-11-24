<?php
include("../config/conexion.php");

$nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
$usuario = mysqli_real_escape_string($conexion, $_POST['usuario']);
$password = $_POST['password'];

$checkEmail = mysqli_query($conexion, "SELECT id_usuario FROM usuarios WHERE correo = '$usuario'");
if(mysqli_num_rows($checkEmail) > 0) {
    die("❌ El correo ya se encuentra registrado.");
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT);
$query = "INSERT INTO usuarios (nombre, correo, password, rol) VALUES ('$nombre', '$usuario', '$passwordHash', 'nino')";

if (mysqli_query($conexion, $query)) {
    header("Location: /LSB/views/login/login.php");
    exit();
} else {
    echo "Error al registrar el usuario: " . mysqli_error($conexion);
}
?>
