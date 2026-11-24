<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { 
    header("Location: /LSB/views/login/login.php"); 
    exit(); 
}
include("../../config/conexion.php");

$query = "SELECT 
            r.id_reporte,
            u.nombre AS estudiante_nombre,
            u.correo AS estudiante_correo,
            r.puntuacion_total,
            r.aciertos_totales,
            r.errores_totales,
            r.tiempo_total_seg,
            r.precision_porcentaje,
            r.senas_dominadas,
            r.fecha_generacion
          FROM reportes r
          INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
          ORDER BY r.fecha_generacion DESC";
$resultado = mysqli_query($conexion, $query);

if (!$resultado) {
    $error_mysql = mysqli_error($conexion);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HANDY - Reportes Oficiales</title>
    <link rel="stylesheet" href="/LSB/assets/css/estilo.css">
    <style>
        body {
            background-color: #1a1210;
            color: white;
            font-family: 'Segoe UI', sans-serif;
            padding: 30px 20px;
            margin: 0;
        }

        .container-reportes {
            max-width: 1100px;
            margin: 0 auto;
            background-color: #231a18;
            padding: 35px;
            border-radius: 16px;
            border: 1px solid #334155;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }

        .header-rep {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #334155;
            padding-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn-volver {
            background-color: #ff5733;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(255,87,51,0.25);
            transition: all 0.2s ease;
        }

        .btn-volver:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(255,87,51,0.4);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin-top: 15px;
        }

        th {
            background-color: #fd7e14;
            color: white;
            padding: 12px 14px;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        td {
            padding: 12px 14px;
            border-bottom: 1px solid #334155;
            font-size: 0.92rem;
        }

        tr:hover {
            background-color: #2c2220;
        }
    </style>
</head>
<body>

    <div class="container-reportes">
        
        <div class="header-rep">
            <div>
                <h1 style="margin:0; font-size:1.8rem; font-weight:800;">📋 Historial y Reportes Oficiales de Calificación</h1>
                <p style="margin:5px 0 0 0; color:#94a3b8; font-size:0.95rem;">Registro consolidado del rendimiento, aciertos y tiempos de los alumnos en HANDY.</p>
            </div>
            <a href="dashboard.php" class="btn-volver">
                ⬅️ Volver al Panel
            </a>
        </div>

        <?php if (isset($error_mysql)): ?>
            <div style="background-color: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 15px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 20px;">
                <strong>❌ Error:</strong> <?= htmlspecialchars($error_mysql) ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Alumno</th>
                    <th style="text-align:center;">Puntuación</th>
                    <th style="text-align:center;">Aciertos</th>
                    <th style="text-align:center;">Errores</th>
                    <th style="text-align:center;">Precisión</th>
                    <th style="text-align:center;">Tiempo</th>
                    <th>Fecha de Emisión</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($resultado && mysqli_num_rows($resultado) > 0): 
                    while($row = mysqli_fetch_assoc($resultado)): 
                        $segundos = intval($row['tiempo_total_seg']);
                        $minutos = floor($segundos / 60);
                        $segundos_restantes = $segundos % 60;
                        $tiempo_formateado = ($minutos > 0) ? "{$minutos}m {$segundos_restantes}s" : "{$segundos_restantes}s";
                        $total_ev = intval($row['aciertos_totales']) + intval($row['errores_totales']);
                        $prec = ($total_ev > 0) ? round((intval($row['aciertos_totales']) / $total_ev) * 100, 1) : floatval($row['precision_porcentaje']);
                ?>
                <tr>
                    <td style="color:#94a3b8; font-weight:bold;">#<?= $row['id_reporte'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($row['estudiante_nombre']) ?></strong><br>
                        <small style="color:#94a3b8;"><?= htmlspecialchars($row['estudiante_correo']) ?></small>
                    </td>
                    <td style="text-align:center; font-weight:bold; color:#ff5733; font-size:1.15rem;"><?= $row['puntuacion_total'] ?> pts</td>
                    <td style="text-align:center; font-weight:bold; color:#4ade80;"><?= $row['aciertos_totales'] ?></td>
                    <td style="text-align:center; font-weight:bold; color:#f87171;"><?= $row['errores_totales'] ?></td>
                    <td style="text-align:center; font-weight:bold; color:#22d3ee;"><?= $prec ?>%</td>
                    <td style="text-align:center; color:#cbd5e1;"><?= $tiempo_formateado ?></td>
                    <td style="color:#94a3b8; font-size:0.85rem;"><?= $row['fecha_generacion'] ?></td>
                </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                <tr>
                    <td colspan="8" style="padding:30px; text-align:center; color:#94a3b8;">
                        Aún no se han generado reportes consolidados para ningún estudiante.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

</body>
</html>
