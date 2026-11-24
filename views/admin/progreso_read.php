<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { 
    header("Location: /LSB/views/login/login.php"); 
    exit(); 
}
include("../../config/conexion.php");

// Filtro opcional por estudiante
$filtro_usuario = isset($_GET['id_usuario']) ? intval($_GET['id_usuario']) : 0;
$where_usuario = ($filtro_usuario > 0) ? " WHERE p.id_usuario = $filtro_usuario " : "";
$where_det = ($filtro_usuario > 0) ? " WHERE d.id_usuario = $filtro_usuario " : "";

// 1. Obtener lista de estudiantes para el filtro
$res_usuarios = mysqli_query($conexion, "SELECT id_usuario, nombre, correo FROM usuarios ORDER BY nombre ASC");

// 2. Consulta de resumen global
$query_resumen = "SELECT 
                    COALESCE(SUM(p.intentos), 0) AS total_intentos,
                    COALESCE(SUM(p.aciertos), 0) AS total_aciertos,
                    COALESCE(SUM(p.errores), 0) AS total_errores,
                    COUNT(DISTINCT CASE WHEN p.estado_completado = 'completado' THEN p.id_contenido END) AS senas_dominadas,
                    COUNT(DISTINCT CASE WHEN p.estado_completado != 'completado' OR p.errores > p.aciertos THEN p.id_contenido END) AS senas_refuerzo
                  FROM progreso p $where_usuario";
$res_resumen = mysqli_query($conexion, $query_resumen);
$resumen = mysqli_fetch_assoc($res_resumen);

$total_intentos = intval($resumen['total_intentos']);
$total_aciertos = intval($resumen['total_aciertos']);
$total_errores = intval($resumen['total_errores']);
$senas_dominadas = intval($resumen['senas_dominadas']);
$senas_refuerzo = intval($resumen['senas_refuerzo']);
$precision_global = ($total_intentos > 0) ? round(($total_aciertos / $total_intentos) * 100, 1) : 0;

// 3. Consulta detallada de progreso por contenido
$query_progreso = "SELECT 
                    p.id_progreso,
                    u.nombre AS alumno,
                    u.correo,
                    c.letra_abecedario AS letra,
                    c.palabra_espanol,
                    c.tipo_categoria,
                    p.intentos,
                    p.aciertos,
                    p.errores,
                    p.mejor_confianza,
                    p.estado_completado,
                    p.ultima_fecha
                  FROM progreso p
                  INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                  INNER JOIN contenidos c ON p.id_contenido = c.id_contenido
                  $where_usuario
                  ORDER BY p.ultima_fecha DESC";
$resultado_progreso = mysqli_query($conexion, $query_progreso);

// 4. Consulta de las últimas 15 detecciones de telemetría de IA
$query_detecciones = "SELECT 
                        d.id_deteccion,
                        u.nombre AS alumno,
                        c.letra_abecedario,
                        c.palabra_espanol,
                        d.letra_esperada,
                        d.letra_reconocida,
                        d.confianza,
                        d.tiempo_ms,
                        d.resultado,
                        d.fecha_hora
                      FROM detecciones_ia d
                      INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
                      INNER JOIN contenidos c ON d.id_contenido = c.id_contenido
                      $where_det
                      ORDER BY d.fecha_hora DESC
                      LIMIT 15";
$resultado_detecciones = mysqli_query($conexion, $query_detecciones);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HANDY - Panel de Seguimiento y Progreso</title>
    <link rel="stylesheet" href="/LSB/assets/css/estilo.css">
    <style>
        body {
            background-color: #1a1210;
            color: #f1f5f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px 20px;
            margin: 0;
        }

        .dashboard-container {
            max-width: 1100px;
            margin: 0 auto;
            background-color: #231a18;
            padding: 35px;
            border-radius: 16px;
            border: 1px solid #334155;
            box-shadow: 0 10px 35px rgba(0,0,0,0.5);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
            padding-bottom: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn-panel {
            background: linear-gradient(135deg, #ff5733 0%, #e64a19 100%);
            color: white;
            text-decoration: none;
            font-weight: 700;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.95rem;
            box-shadow: 0 4px 12px rgba(255, 87, 51, 0.3);
            transition: all 0.2s ease;
        }

        .btn-panel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 87, 51, 0.5);
        }

        /* TARJETAS DE MÉTRICAS */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: #2b1f1d;
            border: 1px solid #3f2e2b;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .metric-card h4 {
            margin: 0 0 6px;
            font-size: 0.85rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-card .number {
            font-size: 2.1rem;
            font-weight: 800;
            margin: 0;
            line-height: 1;
        }

        .metric-precision { color: #22d3ee; }
        .metric-aciertos { color: #22c55e; }
        .metric-errores { color: #f87171; }
        .metric-dominadas { color: #a855f7; }
        .metric-refuerzo { color: #fbbf24; }

        /* FILTRO */
        .filter-bar {
            background: #2a1e1b;
            border: 1px solid #3e2d2a;
            border-radius: 10px;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-bar select {
            background: #1e1412;
            color: #ffffff;
            border: 1px solid #4a3632;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
        }

        /* TABLAS ESTILIZADAS */
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin-top: 10px;
        }

        th {
            padding: 12px 14px;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 14px;
            font-size: 0.92rem;
            border-bottom: 1px solid #332724;
        }

        .table-progreso th {
            background: #ff5733;
            color: white;
        }

        .table-telemetria th {
            background: #0284c7;
            color: white;
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 700;
            display: inline-block;
        }

        .badge-dominada {
            background: rgba(34, 197, 94, 0.18);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.4);
        }

        .badge-refuerzo {
            background: rgba(245, 158, 11, 0.18);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.4);
        }

        .badge-acierto {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }

        .badge-fallo {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 35px 0 15px;
            font-size: 1.35rem;
            font-weight: 700;
            color: #ffffff;
            border-bottom: 1px solid #334155;
            padding-bottom: 8px;
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        
        <div class="header-top">
            <div>
                <h1 style="margin:0; font-size:1.8rem; font-weight:800; color:#ffffff;">📊 Panel de Seguimiento y Progreso Pedagógico</h1>
                <p style="margin:5px 0 0; color:#94a3b8; font-size:0.95rem;">Monitoreo de aciertos, errores, precisión de IA y estado de dominio de señas LSB.</p>
            </div>
            <a href="dashboard.php" class="btn-panel">⬅️ Volver al Panel</a>
        </div>

        <!-- BARRA DE FILTRO POR ESTUDIANTE -->
        <form method="GET" action="progreso_read.php" class="filter-bar">
            <label for="id_usuario" style="font-weight:bold; color:#cbd5e1;">Filtrar por Alumno:</label>
            <select name="id_usuario" id="id_usuario" onchange="this.form.submit()">
                <option value="0">-- Todos los Estudiantes --</option>
                <?php while($u = mysqli_fetch_assoc($res_usuarios)): ?>
                    <option value="<?= $u['id_usuario'] ?>" <?= ($filtro_usuario === intval($u['id_usuario'])) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nombre']) ?> (<?= htmlspecialchars($u['correo']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            <?php if ($filtro_usuario > 0): ?>
                <a href="progreso_read.php" style="color:#ff5733; font-size:0.9rem; text-decoration:none; font-weight:bold;">✖ Quitar Filtro</a>
            <?php endif; ?>
        </form>

        <!-- TARJETAS DE MÉTRICAS GLOBALES -->
        <div class="metrics-grid">
            <div class="metric-card">
                <h4>🎯 Precisión Global</h4>
                <p class="number metric-precision"><?= $precision_global ?>%</p>
                <small style="color:#64748b;"><?= $total_aciertos ?> de <?= $total_intentos ?> intentos</small>
            </div>

            <div class="metric-card">
                <h4>✅ Total Aciertos</h4>
                <p class="number metric-aciertos"><?= $total_aciertos ?></p>
                <small style="color:#64748b;">Señas validadas</small>
            </div>

            <div class="metric-card">
                <h4>❌ Total Errores</h4>
                <p class="number metric-errores"><?= $total_errores ?></p>
                <small style="color:#64748b;">Reintentos y fallos</small>
            </div>

            <div class="metric-card">
                <h4>⭐ Señas Dominadas</h4>
                <p class="number metric-dominadas"><?= $senas_dominadas ?></p>
                <small style="color:#64748b;">Aprobadas al 100%</small>
            </div>

            <div class="metric-card">
                <h4>⚠️ Necesitan Práctica</h4>
                <p class="number metric-refuerzo"><?= $senas_refuerzo ?></p>
                <small style="color:#64748b;">Requieren refuerzo</small>
            </div>
        </div>

        <!-- SECCIÓN 1: ESTADO DE DOMINIO POR CONTENIDO -->
        <div class="section-title">
            <span>📚 Estado de Dominio por Seña y Contenido</span>
        </div>

        <table class="table-progreso">
            <thead>
                <tr>
                    <th>Alumno</th>
                    <th style="text-align:center;">Seña</th>
                    <th style="text-align:center;">Aciertos</th>
                    <th style="text-align:center;">Errores</th>
                    <th style="text-align:center;">Intentos</th>
                    <th style="text-align:center;">Mejor Certeza IA</th>
                    <th style="text-align:center;">Diagnóstico</th>
                    <th>Última Práctica</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado_progreso && mysqli_num_rows($resultado_progreso) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($resultado_progreso)): 
                        $es_dominada = ($row['estado_completado'] === 'completado' || intval($row['aciertos']) > 0);
                        $confianza_porc = round(floatval($row['mejor_confianza']) * 100, 1);
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['alumno']) ?></strong><br>
                            <small style="color:#94a3b8;"><?= htmlspecialchars($row['correo']) ?></small>
                        </td>
                        <td style="text-align:center;">
                            <span style="font-size:1.3rem; font-weight:800; color:#ff5733;">
                                <?= htmlspecialchars($row['letra'] ?: $row['palabra_espanol']) ?>
                            </span>
                        </td>
                        <td style="text-align:center; font-weight:bold; color:#4ade80;"><?= $row['aciertos'] ?></td>
                        <td style="text-align:center; font-weight:bold; color:#f87171;"><?= $row['errores'] ?></td>
                        <td style="text-align:center; font-weight:bold;"><?= $row['intentos'] ?></td>
                        <td style="text-align:center; color:#22d3ee; font-weight:bold;"><?= $confianza_porc ?>%</td>
                        <td style="text-align:center;">
                            <?php if ($es_dominada && intval($row['errores']) <= intval($row['aciertos'])): ?>
                                <span class="badge-status badge-dominada">🌟 Dominada</span>
                            <?php else: ?>
                                <span class="badge-status badge-refuerzo">⚡ Necesita Práctica</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#94a3b8; font-size:0.85rem;"><?= $row['ultima_fecha'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:30px; color:#94a3b8;">
                            Aún no se han registrado prácticas con cámara en este filtro.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- SECCIÓN 2: TELEMETRÍA EN VIVO DE DETECCIONES IA -->
        <div class="section-title">
            <span>🤖 Telemetría de Intentos de Cámara IA en Tiempo Real</span>
        </div>

        <table class="table-telemetria">
            <thead>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Alumno</th>
                    <th style="text-align:center;">Esperada</th>
                    <th style="text-align:center;">Reconocida</th>
                    <th style="text-align:center;">Certeza IA</th>
                    <th style="text-align:center;">Tiempo Respuesta</th>
                    <th style="text-align:center;">Resultado</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado_detecciones && mysqli_num_rows($resultado_detecciones) > 0): ?>
                    <?php while($det = mysqli_fetch_assoc($resultado_detecciones)): 
                        $certeza = round(floatval($det['confianza']) * 100, 1);
                        $tiempo_seg = round(intval($det['tiempo_ms']) / 1000, 2);
                    ?>
                    <tr>
                        <td style="color:#94a3b8; font-size:0.85rem;"><?= $det['fecha_hora'] ?></td>
                        <td><strong><?= htmlspecialchars($det['alumno']) ?></strong></td>
                        <td style="text-align:center; font-weight:bold; color:#ff5733; font-size:1.1rem;"><?= htmlspecialchars($det['letra_esperada']) ?></td>
                        <td style="text-align:center; font-weight:bold; color:#22d3ee; font-size:1.1rem;"><?= htmlspecialchars($det['letra_reconocida']) ?></td>
                        <td style="text-align:center; font-weight:bold; color:<?= $certeza >= 75 ? '#4ade80' : '#fbbf24' ?>;"><?= $certeza ?>%</td>
                        <td style="text-align:center; color:#cbd5e1;"><?= $tiempo_seg ?>s</td>
                        <td style="text-align:center;">
                            <?php if ($det['resultado'] === 'acierto'): ?>
                                <span class="badge-status badge-acierto">✅ Acierto</span>
                            <?php elseif ($det['resultado'] === 'reintento'): ?>
                                <span class="badge-status badge-refuerzo">🔄 Reintento</span>
                            <?php else: ?>
                                <span class="badge-status badge-fallo">❌ Fallo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:30px; color:#94a3b8;">
                            No hay detecciones recientes en el historial de telemetría.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

</body>
</html>
