<?php 
session_start();
unset($_SESSION['fcms_id']);
unset($_SESSION['fcms_token']);
session_destroy(); // destroy fb data
setcookie('fcms_cookie_id', '', time() - 3600, '/');
setcookie('fcms_cookie_token', '', time() - 3600, '/');
header("Location: index.php");
?>
