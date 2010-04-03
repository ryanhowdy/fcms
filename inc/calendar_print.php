<?php
session_start();
include_once('config_inc.php');
include_once('util_inc.php');

// Check that the user is logged in
isLoggedIn();

header("Cache-control: private");
include_once('calendar_class.php');
$calendar = new Calendar($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'._('lang').'" lang="'._('lang').'">
<head>
<title>'.getSiteName().' - '._('powered by').' '.getCurrentVersion().'</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<style type="text/css">
/* TODO
   Move to fcms-core.css */
#big_calendar { width: 658px; border-collapse: collapse; }
h3 { text-align: center; }
th { height: 50px; }
td { padding: 0 0 30px 2px; width: 94px; border: 1px solid #000; vertical-align: top; line-height: 10pt; overflow: hidden; }
.weekDays { padding: 3px; background-color: #ccc; text-align: center; font-weight: bold; }
.nonMonthDay { background-color: #eee; }
.add, .prev, .next { display: none; }
</style>
</head>
<body onload="window.print();">';

$year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? str_pad($_GET['month'], 2, 0, STR_PAD_LEFT) : date('m');
$day = isset($_GET['day']) ? str_pad($_GET['day'], 2, 0, STR_PAD_LEFT) : date('d');
$calendar->displayCalendar($month, $year, $day, 'big');

echo '
<p>&nbsp;</p>
<p>&nbsp;</p>
</body>
</html>';