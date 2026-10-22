<?php
header("Content-Type: application/json");

// RUTA CORREGIDA A DOS NIVELES DESDE /views/juego/
$ruta_conexion = "../../config/conexion.php";

if (!file_exists($ruta_conexion)) {
    echo json_encode(["error" => "No se encuentra el archivo de conexion en la ruta: " . realpath($ruta_conexion)]);
    exit();
}

include($ruta_conexion);

if (!isset($conexion) || !$conexion) {
    echo json_encode(["error" => "La variable de conexion a la base de datos no esta definida o fallo."]);
    exit();
}

$query = "SELECT id_usuario, nombre, correo, password, rol, fecha_registro FROM usuarios";
$resultado = mysqli_query($conexion, $query);

if (!$resultado) {
    echo json_encode(["error" => "Error en la consulta SQL: " . mysqli_error($conexion)]);
    exit();
}

$usuarios = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    $usuarios[] = $row;
}

echo json_encode($usuarios, JSON_PRETTY_PRINT);
?>
