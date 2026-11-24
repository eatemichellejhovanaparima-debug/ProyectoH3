<?php
header("Content-Type: application/json");
include("../../config/conexion.php"); 

// Consulta combinada para traducir los IDs a nombres reales
$query = "SELECT 
            p.id_progreso,
            u.nombre AS alumno_nombre,
            c.letra_abecedario AS contenido_letra,
            p.intentos,
            p.estado_completado,
            p.ultima_fecha
          FROM progreso p
          INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
          INNER JOIN contenidos c ON p.id_contenido = c.id_contenido
          ORDER BY p.ultima_fecha DESC";

$resultado = mysqli_query($conexion, $query);

$progresos = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    $progresos[] = $row;
}

echo json_encode($progresos, JSON_PRETTY_PRINT);
?>
