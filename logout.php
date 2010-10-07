<?php 
session_start();
unset($_SESSION['login_id']);
unset($_SESSION['login_uname']);
unset($_SESSION['login_pw']);
setcookie('fcms_login_id', '', time() - 3600, '/');
setcookie('fcms_login_uname', '', time() - 3600, '/');
setcookie('fcms_login_pw', '', time() - 3600, '/');
header("Location: index.php");
?>
