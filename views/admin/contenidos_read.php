<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { header("Location: /LSB/views/login/login.php"); exit(); }
include("../../config/conexion.php");

// --- LÓGICA DE ELIMINACIÓN DIRECTA ---
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    
    // Obtener la ruta del archivo físico para borrarlo del servidor y no dejar basura
    $res_foto = mysqli_query($conexion, "SELECT ruta_visual FROM contenidos WHERE id_contenido = $del_id");
    if ($row_foto = mysqli_fetch_assoc($res_foto)) {
        $archivo_fisico = $_SERVER['DOCUMENT_ROOT'] . $row_foto['ruta_visual'];
        if (file_exists($archivo_fisico) && is_file($archivo_fisico)) {
            unlink($archivo_fisico);
        }
    }
    
    mysqli_query($conexion, "DELETE FROM contenidos WHERE id_contenido = $del_id");
    header("Location: /LSB/views/admin/contenidos_read.php?msg=eliminado");
    exit();
}

// CONSULTA PARA LISTAR VOCABULARIO ORDENADO PEDAGÓGICAMENTE
$resultado = mysqli_query($conexion, "SELECT * FROM contenidos ORDER BY 
    CASE WHEN tipo_categoria = 'abecedario' THEN 1 ELSE 2 END ASC,
    CASE 
        WHEN tipo_categoria = 'abecedario' AND letra_abecedario IS NOT NULL THEN 
            FIELD(UPPER(letra_abecedario), 'A','B','C','D','E','F','G','H','I','J','K','L','M','N','Ñ','O','P','Q','R','S','T','U','V','W','X','Y','Z')
        ELSE id_contenido
    END ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Contenidos - HANDY</title>
    <link rel="stylesheet" href="/LSB/assets/css/estilo.css">
</head>
<body style="display:block; background-color:#1e1412; color:#f1f5f9; padding:40px; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    
    <div style="max-width: 1100px; margin: 0 auto;">
        
        <!-- ENCABEZADO PREMIUM -->
        <h2 style="color:#ffffff; font-size: 2rem; margin-bottom: 5px; font-weight: 600;">📚 Gestión del Vocabulario y Señas LSB</h2>
        <p style="text-align: left; color:#94a3b8; margin-top: 0; margin-bottom: 25px;">Estructura pedagógica del abecedario boliviano y la traducción técnica al inglés</p>

        <!-- CONTAINER DE ALERTAS CON TRANSPARENCIA ELEGANTE -->
        <?php if (isset($_GET['msg'])): ?>
            <div style="margin-bottom: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); font-size: 0.95rem; font-weight: 500;">
                <?php if ($_GET['msg'] === 'creado'): ?>
                    <div style="background-color: rgba(22, 163, 74, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.4); padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                        <span>🎉</span> ¡Excelente! La nueva seña didáctica fue añadida correctamente al juego.
                    </div>
                <?php elseif ($_GET['msg'] === 'editado'): ?>
                    <div style="background-color: rgba(37, 99, 235, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4); padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                        <span>📝</span> El contenido pedagógico ha sido modificado y actualizado con éxito.
                    </div>
                <?php elseif ($_GET['msg'] === 'eliminado'): ?>
                    <div style="background-color: rgba(220, 38, 38, 0.15); color: #f87171; border: 1px solid rgba(239, 44, 44, 0.4); padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                        <span>🗑️</span> La seña y sus traducciones se han eliminado del sistema.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ENLACES Y ACCIONES SUPERIORES EN LÍNEA -->
        <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <a href="/LSB/views/admin/dashboard.php" style="color:#94a3b8; font-weight: 600; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#94a3b8'">
                ⬅️ Volver al Panel
            </a>
            <a href="/LSB/views/admin/contenidos_create.php" style="background-color: #22c55e; color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 0.95rem; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3); transition: background 0.2s;" onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                ➕ Agregar Seña Nueva
            </a>
        </div>
        
        <!-- ESTRUCTURA DE LA TABLA OSCURA -->
        <div style="overflow: hidden; border-radius: 14px; border: 1px solid #334155; box-shadow: 0 10px 25px rgba(0,0,0,0.4);">
            <table style="width:100%; border-collapse:collapse; background:#231a18; text-align:left; font-size: 0.95rem;">
                <thead>
                    <tr style="background:#ff5733; color:white;">
                        <th style="padding:16px 20px; font-weight: 600;">ID</th>
                        <th style="padding:16px 20px; font-weight: 600;">Categoría del Módulo</th>
                        <th style="padding:16px 20px; font-weight: 600; text-align: center;">Letra</th>
                        <th style="padding:16px 20px; font-weight: 600;">Inglés (Vocabulario)</th>
                        <th style="padding:16px 20px; font-weight: 600;">Español (Apoyo)</th>
                        <th style="padding:16px 20px; font-weight: 600; text-align: center;">Previsualización Seña LSB</th>
                        <th style="padding:16px 20px; font-weight: 600; text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($resultado) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($resultado)): ?>
                        <tr style="border-bottom:1px solid #334155; transition: background 0.2s;" onmouseover="this.style.background='#2c1f1d'" onmouseout="this.style.background='transparent'">
                            <td style="padding:16px 20px; color: #cbd5e1;"><?= $row['id_contenido'] ?></td>
                            <td style="padding:16px 20px;">
                                <span style="background-color: <?= $row['tipo_categoria'] === 'abecedario' ? 'rgba(168, 85, 247, 0.2)' : 'rgba(14, 165, 233, 0.2)' ?>; color: <?= $row['tipo_categoria'] === 'abecedario' ? '#c084fc' : '#38bdf8' ?>; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                    <?= $row['tipo_categoria'] ?>
                                </span>
                            </td>
                            <td style="padding:16px 20px; text-align: center; font-weight: bold; color: #fff;">
                                <?= $row['letra_abecedario'] ? $row['letra_abecedario'] : '<span style="color:#475569;">-</span>' ?>
                            </td>
                            <td style="padding:16px 20px; font-weight: 600; color: #ff5733;"><?= $row['palabra_ingles'] ? htmlspecialchars($row['palabra_ingles']) : '<span style="color:#475569; font-weight:normal;">-</span>' ?></td>
                            <td style="padding:16px 20px; color: #ffffff;"><?= htmlspecialchars($row['palabra_espanol']) ?></td>
                            
                            <!-- CONTENEDOR MULTIMEDIA CORREGIDO (Evita la imagen rota) -->
                            <td style="padding:12px 20px; text-align: center;">
                                <?php if (!empty($row['ruta_visual'])): ?>
                                    <img src="<?= htmlspecialchars($row['ruta_visual']) ?>" 
                                         alt="Seña LSB" 
                                         style="width: 55px; height: 55px; border-radius: 8px; border: 2px solid #475569; object-fit: cover; background-color: #fff;"
                                         onerror="this.onerror=null; this.src='https://placehold.co';">
                                <?php else: ?>
                                    <span style="color:#475569;">Sin recurso</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- BOTONES DE ACCIÓN ASOCIADOS -->
                            <td style="padding:16px 20px; text-align: center; font-weight: bold;">
                                <a href="/LSB/views/admin/contenidos_edit.php?id=<?= $row['id_contenido'] ?>" style="color:#ffc107; text-decoration:none; margin-right: 12px; transition: color 0.2s;" onmouseover="this.style.color='#ffb300'" onmouseout="this.style.color='#ffc107'">Editar</a>
                                <span style="color: #475569;">|</span>
                                <a href="/LSB/views/admin/contenidos_read.php?delete_id=<?= $row['id_contenido'] ?>" style="color:#f87171; text-decoration:none; margin-left: 12px; transition: color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#f87171'" onclick="return confirm('¿Seguro que deseas eliminar esta seña didáctica?')">Eliminar</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="padding: 40px; text-align: center; color: #94a3b8;">No existen materiales didácticos o señas registradas en la base de datos.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>

</body>
</html>
