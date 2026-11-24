<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { die("Acceso denegado."); }
include("../../config/conexion.php");

// --- LÓGICA DE ELIMINACIÓN ---
if (isset($_GET['delete_id'])) {
    $id_a_eliminar = intval($_GET['delete_id']);
    
    if ($id_a_eliminar === intval($_SESSION['id'])) {
        echo "<script>alert('❌ No puedes eliminar tu propia cuenta de administrador.'); window.location='/LSB/views/admin/usuarios_read.php';</script>";
        exit();
    }
    
    $query_delete = "DELETE FROM usuarios WHERE id_usuario = $id_a_eliminar";
    if (mysqli_query($conexion, $query_delete)) {
        header("Location: /LSB/views/admin/usuarios_read.php?msg=eliminado");
        exit();
    } else {
        echo "Error al eliminar: " . mysqli_error($conexion);
    }
}

// CONSULTA DE LISTADO
$query = "SELECT * FROM usuarios";
$resultado = mysqli_query($conexion, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios - HANDY</title>
    <link rel="stylesheet" href="/LSB/assets/css/estilo.css">
</head>
<body style="display:block; background-color:#1e1412; color:#f1f5f9; padding:40px; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    
    <div style="max-width: 1100px; margin: 0 auto;">
        
        <h2 style="color:#ffffff; font-size: 2rem; margin-bottom: 5px; font-weight: 600;">👥 Listado y Control de Usuarios</h2>
        <p style="color: #94a3b8; margin-top: 0; margin-bottom: 25px;">Módulo administrativo para la gestión de cuentas en el sistema HANDY</p>

        <!-- CONTAINER DE ALERTAS ESTILIZADO CON DISEÑO MODERNO PASTEL -->
        <?php if (isset($_GET['msg'])): ?>
            <div style="margin-bottom: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); font-size: 0.95rem; font-weight: 500;">
                <?php if ($_GET['msg'] === 'creado'): ?>
                    <div style="background-color: rgba(22, 163, 74, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.4); padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.2rem;">🎉</span> ¡Excelente! El usuario ha sido creado correctamente en el juego.
                    </div>
                <?php elseif ($_GET['msg'] === 'editado'): ?>
                    <div style="background-color: rgba(37, 99, 235, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4); padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.2rem;">📝</span> Los datos del usuario han sido actualizados con éxito.
                    </div>
                <?php elseif ($_GET['msg'] === 'eliminado'): ?>
                    <div style="background-color: rgba(220, 38, 38, 0.15); color: #f87171; border: 1px solid rgba(239, 44, 44, 0.4); padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.2rem;">🗑️</span> El usuario ha sido eliminado correctamente del sistema.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN DE BOTONES DE NAVEGACIÓN -->
        <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <a href="/LSB/views/admin/dashboard.php" style="color:#94a3b8; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 5px; transition: color 0.2s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#94a3b8'">
                ⬅️ Volver al Panel
            </a>
            <!-- BOTÓN AGREGAR NUEVO ESTILIZADO -->
            <a href="/LSB/views/admin/usuarios_create.php" style="background-color: #22c55e; color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 0.95rem; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3); transition: background 0.2s;" onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                ➕ Agregar Nuevo Usuario
            </a>
        </div>
        
        <!-- TABLA CON ESTILO PREMIUM OSCURO -->
        <div style="overflow-hidden; border-radius: 14px; border: 1px solid #334155; box-shadow: 0 10px 25px rgba(0,0,0,0.4);">
            <table style="width:100%; border-collapse:collapse; background:#231a18; text-align:left; font-size: 0.95rem;">
                <thead>
                    <tr style="background:#ff5733; color:white;">
                        <th style="padding:16px 20px; font-weight: 600;">ID</th>
                        <th style="padding:16px 20px; font-weight: 600;">Nombre</th>
                        <th style="padding:16px 20px; font-weight: 600;">Correo</th>
                        <th style="padding:16px 20px; font-weight: 600;">Rol</th>
                        <th style="padding:16px 20px; font-weight: 600; text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($resultado) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($resultado)): ?>
                        <tr style="border-bottom:1px solid #334155; transition: background 0.2s;" onmouseover="this.style.background='#2c1f1d'" onmouseout="this.style.background='transparent'">
                            <td style="padding:16px 20px; color: #cbd5e1;"><?= $row['id_usuario'] ?></td>
                            <td style="padding:16px 20px; font-weight: 500; color: #ffffff;"><?= htmlspecialchars($row['nombre']) ?></td>
                            <td style="padding:16px 20px; color: #cbd5e1;"><?= htmlspecialchars($row['correo']) ?></td>
                            <td style="padding:16px 20px;">
                                <span style="background-color: <?= $row['rol'] === 'admin' ? 'rgba(245, 158, 11, 0.2)' : 'rgba(59, 130, 246, 0.2)' ?>; color: <?= $row['rol'] === 'admin' ? '#fbbf24' : '#60a5fa' ?>; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                    <?= $row['rol'] ?>
                                </span>
                            </td>
                            <td style="padding:16px 20px; text-align: center; font-weight: bold;">
                                <a href="/LSB/views/admin/usuarios_edit.php?id=<?= $row['id_usuario'] ?>" style="color:#60a5fa; text-decoration:none; margin-right: 12px; transition: color 0.2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='#60a5fa'">📝 Editar</a>
                                <span style="color: #475569;">|</span>
                                <a href="/LSB/views/admin/usuarios_read.php?delete_id=<?= $row['id_usuario'] ?>" style="color:#f87171; text-decoration:none; margin-left: 12px; transition: color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#f87171'" onclick="return confirm('¿Seguro que deseas eliminar este usuario?')">❌ Eliminar</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8;">No hay usuarios registrados en el sistema.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>

</body>
</html>
