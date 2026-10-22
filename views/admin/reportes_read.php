<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { 
    header("Location: /LSB/views/login/login.php"); 
    exit(); 
}
include("../../config/conexion.php");

// Consulta combinada exacta usando tus columnas reales de la base de datos
$query = "SELECT 
            r.id_reporte,
            u.nombre AS estudiante_nombre,
            u.correo AS estudiante_correo,
            r.puntuacion_total,
            r.tiempo_total_seg,
            r.fecha_generacion
          FROM reportes r
          INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
          ORDER BY r.fecha_generacion DESC";
$resultado = mysqli_query($conexion, $query);

// Capturador automático por si hay algún error de tipeo en la base de datos
if (!$resultado) {
    $error_mysql = mysqli_error($conexion);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HANDY - Reportes Oficiales</title>
</head>
<body style="background-color:#1e1412; color:white; font-family:'Segoe UI', sans-serif; padding:30px; margin:0;">

    <div style="max-width: 1050px; margin: 0 auto; background-color:#231a18; padding:35px; border-radius:12px; border: 1px solid #334155; box-shadow: 0 10px 25px rgba(0,0,0,0.4); box-sizing: border-box;">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; border-bottom: 1px solid #334155; padding-bottom:15px;">
            <div>
                <h2 style="margin:0; font-size:1.8rem; font-weight:600;">📋 Historial y Reportes Oficiales de Calificación</h2>
                <p style="margin:5px 0 0 0; color:#94a3b8; font-size:0.95rem;">Registro consolidado del rendimiento y tiempos de los alumnos en la plataforma HANDY.</p>
            </div>
            <a href="dashboard.php" style="background-color:#ff5733; color:white; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9rem; box-shadow:0 4px 10px rgba(255,87,51,0.25);">
                ⬅️ Volver al Panel
            </a>
        </div>

        <!-- Alerta visual si llega a fallar la consulta -->
        <?php if (isset($error_mysql)): ?>
            <div style="background-color: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 15px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 20px; font-family: monospace;">
                <strong>❌ Error Crítico de Sincronización:</strong> <?= $error_mysql ?>
            </div>
        <?php endif; ?>

        <table style="width:100%; border-collapse: collapse; text-align:left;">
            <thead>
                <tr style="background-color:#fd7e14; color:white;">
                    <th style="padding:14px; font-weight:600;">ID Reporte</th>
                    <th style="padding:14px; font-weight:600;">Niño</th>
                    <th style="padding:14px; font-weight:600; text-align:center;">Puntuación Total</th>
                    <th style="padding:14px; font-weight:600; text-align:center;">Tiempo Empleado</th>
                    <th style="padding:14px; font-weight:600;">Fecha de Emisión</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($resultado && mysqli_num_rows($resultado) > 0): 
                    while($row = mysqli_fetch_assoc($resultado)): 
                        // Convertir segundos a un formato cómodo de leer (Minutos y Segundos)
                        $segundos = intval($row['tiempo_total_seg']);
                        $minutos = floor($segundos / 60);
                        $segundos_restantes = $segundos % 60;
                        $tiempo_formateado = ($minutos > 0) ? "{$minutos}m {$segundos_restantes}s" : "{$segundos_restantes} segundos";
                ?>
                <tr style="border-bottom: 1px solid #334155; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#2c2220'" onmouseout="this.style.backgroundColor='transparent'">
                    <td style="padding:14px; color:#94a3b8; font-weight:bold;">#<?= $row['id_reporte'] ?></td>
                    <td style="padding:14px;">
                        <span style="font-weight:600; color:#fff;"><?= htmlspecialchars($row['estudiante_nombre']) ?></span><br>
                        <small style="color:#94a3b8;"><?= htmlspecialchars($row['estudiante_correo']) ?></small>
                    </td>
                    <td style="padding:14px; text-align:center; font-weight:700; color:#22c55e; font-size:1.1rem;">
                        <?= $row['puntuacion_total'] ?> pts
                    </td>
                    <td style="padding:14px; text-align:center; color:#cbd5e1; font-weight:500;">
                        ⏱️ <?= $tiempo_formateado ?>
                    </td>
                    <td style="padding:14px; color:#94a3b8; font-size:0.9rem;"><?= $row['fecha_generacion'] ?></td>
                </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                <tr>
                    <td colspan="5" style="padding:45px; text-align:center; color:#94a3b8; font-size:1.05rem;">
                        📭 La tabla 'reportes' se encuentra vacía actualmente. Los puntajes oficiales se consolidarán cuando los alumnos terminen una sesión completa de evaluación en el juego.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
