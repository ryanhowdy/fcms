<?php 
session_start();
include_once('inc/language.php');
unset($_SESSION['login_id']);
unset($_SESSION['login_uname']);
unset($_SESSION['login_pw']);
setcookie('fcms_login_id', '', time() - 3600, '/');
setcookie('fcms_login_uname', '', time() - 3600, '/');
setcookie('fcms_login_pw', '', time() - 3600, '/');
echo "<h3>".$LANG['logout_success']."<h3><a href=\"index.php\">".$LANG['continue']."</a>.";
echo "<meta http-equiv='refresh' content='0;URL=index.php'>";
?> 
