<?php
include_once('config_inc.php');
include_once('database_class.php');
include_once('util_inc.php');

$username = cleanInput($_GET['username']);
$result = mysql_query("SELECT `username` FROM `fcms_users` WHERE `username` = '$username'"); 
$username_check = mysql_num_rows($result);

if ($username_check > 0) {
    echo 'unavailable';
} else {
    echo 'available';
}
?>
