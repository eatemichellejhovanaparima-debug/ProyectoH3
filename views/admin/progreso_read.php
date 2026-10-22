<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { 
    header("Location: /LSB/views/login/login.php"); 
    exit(); 
}
include("../../config/conexion.php");

$query = "SELECT 
            p.id_progreso,
            u.nombre AS alumno,
            u.correo,
            c.letra_abecedario AS letra,
            p.intentos,
            p.estado_completado,
            p.ultima_fecha
          FROM progreso p
          INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
          INNER JOIN contenidos c ON p.id_contenido = c.id_contenido
          ORDER BY p.ultima_fecha DESC";
$resultado = mysqli_query($conexion, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HANDY - Monitoreo de Progreso</title>
</head>
<body style="background-color:#1e1412; color:white; font-family:sans-serif; padding:30px;">

    <div style="max-width: 1000px; margin: 0 auto; background-color:#231a18; padding:30px; border-radius:12px; border: 1px solid #334155;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2>📊 Seguimiento de Avance en Señas LSB</h2>
            <a href="dashboard.php" style="color:#ff5733; text-decoration:none; font-weight:bold;">⬅️ Volver al Panel</a>
        </div>

        <table style="width:100%; border-collapse: collapse; text-align:left; margin-top:15px;">
            <thead>
                <tr style="background-color:#ff5733; color:white;">
                    <th style="padding:12px;">Niño</th>
                    <th style="padding:12px;">Letra Evaluada</th>
                    <th style="padding:12px; text-align:center;">Intentos</th>
                    <th style="padding:12px; text-align:center;">Estado</th>
                    <th style="padding:12px;">Última Fecha</th>
                </tr>       
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($resultado)): ?>
                <tr style="border-bottom: 1px solid #334155;">
                    <td style="padding:12px;"><?= $row['alumno'] ?> <br><small style="color:#94a3b8;"><?= $row['correo'] ?></small></td>
                    <td style="padding:12px; font-weight:bold; font-size:1.2rem; color:#ff5733;"><?= $row['letra'] ?></td>
                    <td style="padding:12px; text-align:center;"><?= $row['intentos'] ?></td>
                    <td style="padding:12px; text-align:center;">
                        <span style="padding:4px 8px; border-radius:4px; font-weight:bold; font-size:0.85rem; background-color: <?= $row['estado_completado'] ? 'rgba(34,197,94,0.15)' : 'rgba(245,158,11,0.15)' ?>; color: <?= $row['estado_completado'] ? '#22c55e' : '#fbbf24' ?>;">
                            <?= $row['estado_completado'] ? 'Completado' : 'En Curso' ?>
                        </span>
                    </td>
                    <td style="padding:12px; color:#94a3b8;"><?= $row['ultima_fecha'] ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($resultado) == 0): ?>
                <tr>
                    <td colspan="5" style="padding:30px; text-align:center; color:#94a3b8;">Aún ningún alumno ha realizado intentos en el juego.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
