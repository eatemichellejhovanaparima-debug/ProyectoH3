<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { 
    header("Location: /LSB/views/login/login.php"); 
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - HANDY</title>
    <link rel="stylesheet" href="/LSB/assets/css/estilo.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; background-color:#1e1412; color:#f1f5f9; min-height:100vh; margin:0; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    
    <!-- TU CONTENEDOR ORIGINAL EN COLOR OSCURO -->
    <div style="background-color:#231a18; padding:40px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.5); width:100%; max-width:950px; border: 1px solid #334155; box-sizing: border-box;">
        
        <!-- ENCABEZADO -->
        <h1 style="color:#ffffff; font-size: 2rem; margin-bottom: 8px; font-weight: 600; text-align: left;">
            ⚙️ Panel de Control Principal - HANDY
        </h1>
        <p style="color: #cbd5e1; margin-top: 0; margin-bottom: 25px; font-size: 1rem; text-align: left;">
            Bienvenido: <strong style="color: #ff5733; font-weight: 600;"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Administrador') ?></strong>
        </p>
        
        <hr style="border:0; border-top:1px solid #334155; margin-bottom: 25px;">
        
        <!-- ALINEACIÓN HORIZONTAL DE LOS 5 BOTONES EN FILA PERFECTA -->
        <div style="display: flex; gap: 15px; justify-content: flex-start; flex-wrap: wrap;">
            
            <!-- BOTÓN 1: GESTIONAR usuariOS -->
            <a href="/LSB/views/admin/usuarios_read.php" 
               style="background-color: #007bff; color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.95rem; box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2); transition: background 0.2s;" 
               onmouseover="this.style.background='#0069d9'" 
               onmouseout="this.style.background='#007bff'">
                👥 Gestionar Usuarios
            </a>
            
            <!-- BOTÓN 2: GESTIONAR CONTENIDOS -->
            <a href="/LSB/views/admin/contenidos_read.php" 
               style="background-color: #28a745; color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.95rem; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2); transition: background 0.2s;" 
               onmouseover="this.style.background='#218838'" 
               onmouseout="this.style.background='#28a745'">
                📚 Gestionar Contenidos LSB
            </a>

            <!-- BOTÓN 3: VER PROGRESO ALUMNOS -->
            <a href="/LSB/views/admin/progreso_read.php" 
               style="background-color: #1e3a8a; color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.95rem; box-shadow: 0 4px 10px rgba(30, 58, 138, 0.2); transition: background 0.2s;" 
               onmouseover="this.style.background='#172554'" 
               onmouseout="this.style.background='#1e3a8a'">
                📊 Ver Progreso Usuarios
            </a>

            <!-- BOTÓN 4: REPORTES (CORREGIDO SOLO TEXTO "REPORTES") -->
            <a href="/LSB/views/admin/reportes_read.php" 
               style="background-color: #fd7e14; color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.95rem; box-shadow: 0 4px 10px rgba(253, 126, 20, 0.2); transition: background 0.2s;" 
               onmouseover="this.style.background='#d9410d'" 
               onmouseout="this.style.background='#fd7e14'">
                📋 Reportes
            </a>
            
            <!-- BOTÓN 5: CERRAR SESIÓN -->
            <a href="/LSB/controllers/logout.php" 
               style="background-color: #dc3545; color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.95rem; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2); transition: background 0.2s;" 
               onmouseover="this.style.background='#c82333'" 
               onmouseout="this.style.background='#dc3545'">
                🚪 Cerrar Sesión
            </a>
            
        </div>
        
    </div>

</body>
</html>
