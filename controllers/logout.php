<?php
session_start();
session_unset();
session_destroy();
header("Location: /LSB/views/login/login.php");
exit();
?>
