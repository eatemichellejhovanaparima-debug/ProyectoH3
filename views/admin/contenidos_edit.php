<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { header("Location: /LSB/views/login/login.php"); exit(); }
include("../../config/conexion.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cont_query = mysqli_query($conexion, "SELECT * FROM contenidos WHERE id_contenido = $id");
$cont = mysqli_fetch_assoc($cont_query);

if (!$cont) {
    die("❌ Contenido didáctico no encontrado.");
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $tipo = $_POST['tipo_categoria'];
    $letra = !empty($_POST['letra_abecedario']) ? "'".mysqli_real_escape_string($conexion, $_POST['letra_abecedario'])."'" : "NULL";
    $ingles = !empty($_POST['palabra_ingles']) ? "'".mysqli_real_escape_string($conexion, $_POST['palabra_ingles'])."'" : "NULL";
    $espanol = mysqli_real_escape_string($conexion, $_POST['palabra_espanol']);
    $ruta_final = $cont['ruta_visual'];

    if (!empty($_POST['link_multimedia'])) {
        $ruta_final = mysqli_real_escape_string($conexion, $_POST['link_multimedia']);
    } 
    elseif (!empty($_FILES["multimedia"]["name"])) {
        $target_dir = "../../assets/multimedia/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_name = time() . "_" . basename($_FILES["multimedia"]["name"]);
        if (move_uploaded_file($_FILES["multimedia"]["tmp_name"], $target_dir . $file_name)) {
            if (strpos($cont['ruta_visual'], 'http') === false) {
                $foto_antigua = $_SERVER['DOCUMENT_ROOT'] . $cont['ruta_visual'];
                if (file_exists($foto_antigua) && is_file($foto_antigua)) {
                    unlink($foto_antigua);
                }
            }
            $ruta_final = "/LSB/assets/multimedia/" . $file_name;
        }
    }

    $query_update = "UPDATE contenidos SET tipo_categoria='$tipo', letra_abecedario=$letra, palabra_ingles=$ingles, palabra_espanol='$espanol', ruta_visual='$ruta_final' WHERE id_contenido=$id";
    
    if(mysqli_query($conexion, $query_update)){
        header("Location: /LSB/views/admin/contenidos_read.php?msg=editado");
        exit();
    } else {
        echo "Error al actualizar: " . mysqli_error($conexion);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Contenido - HANDY</title>
    <link rel="stylesheet" href="/LSB/assets/css/estilo.css">
</head>
<body style="background-color:#1e1412; color:#f1f5f9; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px;">
    
    <div style="background-color:#231a18; padding:40px; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,0.5); width:100%; max-width:450px; border: 1px solid #334155; box-sizing: border-box;">
        <h2 style="color:#ff5733; margin-top:0; text-align:center; font-size:1.8rem; font-weight:600;">✏️ Editar Contenido</h2>
        <p style="text-align:center; color:#94a3b8; font-size:0.95rem; margin-bottom:25px;">Modifica las señas usando archivos locales o links directos de internet</p>
        <hr style="border:0; border-top:1px solid #334155; margin-bottom:20px;">

        <form action="" method="POST" enctype="multipart/form-data">
            
            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.9rem; color:#cbd5e1;">Categoría del Módulo:</label>
            <select name="tipo_categoria" style="width:100%; padding:12px; margin-bottom:20px; border-radius:8px; border:1px solid #475569; background-color:#1e1412; color:white; box-sizing:border-box; font-size:0.95rem;">
                <option value="abecedario" <?= $cont['tipo_categoria'] == 'abecedario' ? 'selected' : '' ?>>Abecedario (Letras)</option>
                <option value="palabra_basica" <?= $cont['tipo_categoria'] == 'palabra_basica' ? 'selected' : '' ?>>Palabras Básicas (Vocabulario)</option>
            </select>

            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.9rem; color:#cbd5e1;">Letra (Solo si aplica al Abecedario):</label>
            <input type="text" name="letra_abecedario" value="<?= htmlspecialchars($cont['letra_abecedario'] ?? '') ?>" maxlength="1" style="width:100%; padding:12px; margin-bottom:20px; border-radius:8px; border:1px solid #475569; background-color:#1e1412; color:white; box-sizing:border-box;" placeholder="Ej. A">

            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.9rem; color:#cbd5e1;">Palabra en Inglés (Vocabulario):</label>
            <input type="text" name="palabra_ingles" value="<?= htmlspecialchars($cont['palabra_ingles'] ?? '') ?>" style="width:100%; padding:12px; margin-bottom:20px; border-radius:8px; border:1px solid #475569; background-color:#1e1412; color:white; box-sizing:border-box;" placeholder="Ej. Hello">

            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.9rem; color:#cbd5e1;">Palabra en Español (Traducción):</label>
            <input type="text" name="palabra_espanol" value="<?= htmlspecialchars($cont['palabra_espanol']) ?>" style="width:100%; padding:12px; margin-bottom:20px; border-radius:8px; border:1px solid #475569; background-color:#1e1412; color:white; box-sizing:border-box;" placeholder="Ej. Hola" required>

            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.9rem; color:#ff5733;">Opción A: Pegar Link de Imagen/GIF de Internet:</label>
            <input type="url" name="link_multimedia" value="<?= (strpos($cont['ruta_visual'], 'http') !== false) ? htmlspecialchars($cont['ruta_visual']) : '' ?>" style="width:100%; padding:12px; margin-bottom:20px; border-radius:8px; border:1px solid #ff5733; background-color:#1e1412; color:white; box-sizing:border-box;" placeholder="https://ejemplo.com">

            <label style="display:block; margin-bottom:5px; font-weight:600; font-size:0.9rem; color:#cbd5e1;">Opción B: Subir Archivo Físico Local:</label>
            <p style="margin: 0 0 10px 0; font-size: 0.8rem; color: #94a3b8;">(Deja ambas opciones vacías si deseas conservar la seña actual)</p>
            <input type="file" name="multimedia" accept="image/*" style="width:100%; padding:10px; margin-bottom:25px; border-radius:8px; border:1px dashed #475569; background-color:#1e1412; color:#cbd5e1; box-sizing:border-box;">

            <button type="submit" style="width:100%; background-color:#22c55e; color:white; border:none; padding:14px; font-size:1rem; font-weight:bold; border-radius:8px; cursor:pointer; box-shadow:0 4px 12px rgba(34,197,94,0.25);">
                Actualizar Contenido
            </button>
        </form>
        
        <p style="text-align:center; margin-top:20px; font-size:0.95rem;"><a href="/LSB/views/admin/contenidos_read.php" style="color:#94a3b8; text-decoration:none; font-weight:500;">Cancelar y regresar</a></p>
    </div>

</body>
</html>
