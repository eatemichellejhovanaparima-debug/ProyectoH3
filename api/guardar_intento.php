<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// 1. Verificación estricta de seguridad: Usuario autenticado en sesión PHP
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No autorizado. Debes iniciar sesión en HANDY para registrar tu progreso.'
    ]);
    exit();
}

$id_usuario = intval($_SESSION['id']);

require_once(__DIR__ . '/../config/conexion.php');

if (!$conexion) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión a la base de datos.'
    ]);
    exit();
}

// 2. Lectura y validación de datos JSON recibidos
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Formato JSON inválido.'
    ]);
    exit();
}

$id_contenido      = isset($data['id_contenido']) ? intval($data['id_contenido']) : 0;
$letra_esperada    = isset($data['letra_esperada']) ? mb_strtoupper(trim($data['letra_esperada']), 'UTF-8') : '';
$letra_reconocida  = isset($data['letra_reconocida']) ? mb_strtoupper(trim($data['letra_reconocida']), 'UTF-8') : '';
$confianza         = isset($data['confianza']) ? floatval($data['confianza']) : 0.0;
$tiempo_ms         = isset($data['tiempo_ms']) ? intval($data['tiempo_ms']) : 0;
$resultado         = isset($data['resultado']) && in_array($data['resultado'], ['acierto', 'fallo', 'reintento'], true) 
                        ? $data['resultado'] 
                        : 'fallo';

// Si no se proporcionó id_contenido directo, buscarlo en la tabla 'contenidos'
if ($id_contenido <= 0 && !empty($letra_esperada)) {
    $stmt_find = mysqli_prepare($conexion, "SELECT id_contenido FROM contenidos WHERE letra_abecedario = ? OR UPPER(palabra_ingles) = ? OR UPPER(palabra_espanol) = ? LIMIT 1");
    if ($stmt_find) {
        mysqli_stmt_bind_param($stmt_find, "sss", $letra_esperada, $letra_esperada, $letra_esperada);
        mysqli_stmt_execute($stmt_find);
        $res_find = mysqli_stmt_get_result($stmt_find);
        if ($row_find = mysqli_fetch_assoc($res_find)) {
            $id_contenido = intval($row_find['id_contenido']);
        }
        mysqli_stmt_close($stmt_find);
    }
}

// Si la letra no existía previamente en 'contenidos', registrarla automáticamente para trazabilidad
if ($id_contenido <= 0 && !empty($letra_esperada)) {
    $stmt_insert_cont = mysqli_prepare($conexion, "INSERT INTO contenidos (tipo_categoria, letra_abecedario, palabra_espanol, ruta_visual) VALUES ('abecedario', ?, ?, '')");
    if ($stmt_insert_cont) {
        $desc = "Letra " . $letra_esperada;
        mysqli_stmt_bind_param($stmt_insert_cont, "ss", $letra_esperada, $desc);
        if (mysqli_stmt_execute($stmt_insert_cont)) {
            $id_contenido = mysqli_insert_id($conexion);
        }
        mysqli_stmt_close($stmt_insert_cont);
    }
}

if ($id_contenido <= 0) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo vincular la seña con un contenido válido.'
    ]);
    exit();
}

// 3. Ejecución transaccional: Inserción en detecciones_ia y actualización en progreso
mysqli_begin_transaction($conexion);

try {
    // A) Registro en telemetría de IA
    $stmt_det = mysqli_prepare($conexion, "INSERT INTO detecciones_ia (id_usuario, id_contenido, letra_esperada, letra_reconocida, confianza, tiempo_ms, resultado, fecha_hora) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt_det) {
        throw new Exception("Error al preparar telemetría: " . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_det, "iissdis", $id_usuario, $id_contenido, $letra_esperada, $letra_reconocida, $confianza, $tiempo_ms, $resultado);
    if (!mysqli_stmt_execute($stmt_det)) {
        throw new Exception("Error al guardar telemetría: " . mysqli_stmt_error($stmt_det));
    }
    $id_deteccion = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt_det);

    // B) Actualización atómica en la tabla 'progreso'
    $es_acierto = ($resultado === 'acierto') ? 1 : 0;
    $es_error   = ($resultado === 'fallo') ? 1 : 0;
    $estado     = ($resultado === 'acierto') ? 'completado' : 'pendiente';

    $stmt_prog = mysqli_prepare($conexion, "INSERT INTO progreso (id_usuario, id_contenido, intentos, aciertos, errores, mejor_confianza, estado_completado, ultima_fecha) 
        VALUES (?, ?, 1, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            intentos = intentos + 1,
            aciertos = aciertos + VALUES(aciertos),
            errores = errores + VALUES(errores),
            mejor_confianza = GREATEST(mejor_confianza, VALUES(mejor_confianza)),
            estado_completado = IF(VALUES(estado_completado) = 'completado', 'completado', estado_completado),
            ultima_fecha = NOW()");
    
    if (!$stmt_prog) {
        throw new Exception("Error al preparar progreso: " . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_prog, "iiiids", $id_usuario, $id_contenido, $es_acierto, $es_error, $confianza, $estado);
    if (!mysqli_stmt_execute($stmt_prog)) {
        throw new Exception("Error al actualizar progreso: " . mysqli_stmt_error($stmt_prog));
    }
    mysqli_stmt_close($stmt_prog);

    // C) Actualizar o registrar en tabla 'reportes'
    $stmt_rep = mysqli_prepare($conexion, "SELECT id_reporte FROM reportes WHERE id_usuario = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_rep, "i", $id_usuario);
    mysqli_stmt_execute($stmt_rep);
    $res_rep = mysqli_stmt_get_result($stmt_rep);
    
    $puntos_ganados = ($resultado === 'acierto') ? 20 : 0;
    $tiempo_seg = intval(ceil($tiempo_ms / 1000));

    if ($row_rep = mysqli_fetch_assoc($res_rep)) {
        $id_rep = intval($row_rep['id_reporte']);
        $stmt_up_rep = mysqli_prepare($conexion, "UPDATE reportes SET 
            puntuacion_total = puntuacion_total + ?, 
            aciertos_totales = aciertos_totales + ?, 
            errores_totales = errores_totales + ?, 
            tiempo_total_seg = tiempo_total_seg + ?, 
            fecha_generacion = NOW() 
            WHERE id_reporte = ?");
        mysqli_stmt_bind_param($stmt_up_rep, "iiiii", $puntos_ganados, $es_acierto, $es_error, $tiempo_seg, $id_rep);
        mysqli_stmt_execute($stmt_up_rep);
        mysqli_stmt_close($stmt_up_rep);
    } else {
        $stmt_in_rep = mysqli_prepare($conexion, "INSERT INTO reportes (id_usuario, puntuacion_total, aciertos_totales, errores_totales, tiempo_total_seg, precision_porcentaje, senas_dominadas, fecha_generacion) 
            VALUES (?, ?, ?, ?, ?, 100.0, 1, NOW())");
        mysqli_stmt_bind_param($stmt_in_rep, "iiiii", $id_usuario, $puntos_ganados, $es_acierto, $es_error, $tiempo_seg);
        mysqli_stmt_execute($stmt_in_rep);
        mysqli_stmt_close($stmt_in_rep);
    }
    mysqli_stmt_close($stmt_rep);

    mysqli_commit($conexion);

    // Obtener estadísticas actualizadas del usuario para retroalimentación
    $stmt_stats = mysqli_prepare($conexion, "SELECT 
        SUM(intentos) as total_intentos, 
        SUM(aciertos) as total_aciertos, 
        SUM(errores) as total_errores,
        COUNT(CASE WHEN estado_completado = 'completado' THEN 1 END) as senas_dominadas
        FROM progreso WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt_stats, "i", $id_usuario);
    mysqli_stmt_execute($stmt_stats);
    $res_stats = mysqli_stmt_get_result($stmt_stats);
    $stats = mysqli_fetch_assoc($res_stats);
    mysqli_stmt_close($stmt_stats);

    echo json_encode([
        'success' => true,
        'id_deteccion' => $id_deteccion,
        'resultado' => $resultado,
        'confianza' => $confianza,
        'stats' => [
            'total_intentos' => intval($stats['total_intentos'] ?? 0),
            'total_aciertos' => intval($stats['total_aciertos'] ?? 0),
            'total_errores' => intval($stats['total_errores'] ?? 0),
            'senas_dominadas' => intval($stats['senas_dominadas'] ?? 0)
        ]
    ]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

