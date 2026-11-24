<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { 
    header("Location: /LSB/views/login/login.php"); 
    exit(); 
}
include("../../config/conexion.php");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $tipo = $_POST['tipo_categoria'];
    $letra = !empty($_POST['letra_abecedario']) ? "'".$_POST['letra_abecedario']."'" : "NULL";
    $ingles = !empty($_POST['palabra_ingles']) ? "'".$_POST['palabra_ingles']."'" : "NULL";
    $espanol = $_POST['palabra_espanol'];
    $link_externo = !empty($_POST['enlace_externo']) ? $_POST['enlace_externo'] : '';

    $ruta_db = '';

    if (!empty($_FILES["multimedia"]["name"])) {
        $target_dir = "../../assets/multimedia/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_name = time() . "_" . basename($_FILES["multimedia"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if(move_uploaded_file($_FILES["multimedia"]["tmp_name"], $target_file)){
            $ruta_db = "/LSB/assets/multimedia/" . $file_name;
        }
    } 
    else if (!empty($link_externo)) {
        $ruta_db = $link_externo;
    }

    if (!empty($ruta_db)) {
        mysqli_query($conexion, "INSERT INTO contenidos (tipo_categoria, letra_abecedario, palabra_ingles, palabra_espanol, ruta_visual) VALUES ('$tipo', $letra, $ingles, '$espanol', '$ruta_db')");
        header("Location: contenidos_read.php");
        exit();
    } else {
        echo "<script>alert('⚠️ Error: Debes seleccionar un archivo local o ingresar un enlace de internet.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Contenido</title>
    <!-- RUTA RELATIVA REPARADA PARA CARGAR EL DISEÑO CORRECTAMENTE -->
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>
<body style="background-color:#1e1412; color:white; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; padding:40px; display: flex; justify-content: center; align-items: center; min-height: 100vh; box-sizing: border-box;">

    <div style="width: 100%; max-width: 500px; background-color:#231a18; padding:35px; border-radius:16px; border: 1px solid #334155; box-shadow: 0 10px 30px rgba(0,0,0,0.5); box-sizing: border-box;">
        <h2 style="margin-top:0; color:#ff5733; font-size:1.6rem; text-align:center; margin-bottom:25px;">✨ Agregar Nuevo Contenido Didáctico</h2>
        
        <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 15px; text-align: left;">
            
            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#94a3b8;">Categoría:</label>
                <select name="tipo_categoria" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #475569; background:#1e1412; color:white;">
                    <option value="abecedario">Abecedario</option>
                    <option value="palabra_basica">Palabras Básicas</option>
                </select>
            </div>

            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#94a3b8;">Letra (opcional):</label>
                <input type="text" name="letra_abecedario" maxlength="1" style="width:100%; padding:10px; border-radius:6px; border:1px solid #475569; background:#1e1412; color:white; box-sizing: border-box;">
            </div>

            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#94a3b8;">Palabra en Inglés (opcional):</label>
                <input type="text" name="palabra_ingles" style="width:100%; padding:10px; border-radius:6px; border:1px solid #475569; background:#1e1412; color:white; box-sizing: border-box;">
            </div>

            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#94a3b8;">Palabra en Español:</label>
                <input type="text" name="palabra_espanol" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #475569; background:#1e1412; color:white; box-sizing: border-box;">
            </div>

            <div style="background: #1a110f; padding: 15px; border-radius: 8px; border: 1px dashed #ff5733;">
                <p style="margin-top:0; font-weight:bold; color:#ff5733; margin-bottom:10px; font-size:0.95rem;">📸 Opción A: Subir Archivo Local</p>
                <input type="file" name="multimedia" accept="image/*" style="color:#94a3b8;">
                
                <hr style="border:0; border-top:1px solid #334155; margin:15px 0;">
                
                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#22c55e; font-size:0.95rem;">🔗 Opción B: O pegar un Link de Internet</label>
                <input type="url" name="enlace_externo" placeholder="https://ejemplo.com" style="width:100%; padding:10px; border-radius:6px; border:1px solid #475569; background:#1e1412; color:white; box-sizing: border-box;">
            </div>

            <button type="submit" style="background-color:#22c55e; color:white; border:none; padding:12px; font-weight:bold; border-radius:6px; cursor:pointer; font-size:1rem; margin-top:10px;">Registrar Seña</button>
        </form>
        
        <div style="text-align: center; margin-top: 15px;">
            <a href="contenidos_read.php" style="color:#94a3b8; text-decoration:none; font-size:0.95rem;">❌ Cancelar y Volver</a>
        </div>
    </div>

</body>
</html>
