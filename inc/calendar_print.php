<?php
session_start();
include_once('config_inc.php');
include_once('util_inc.php');
include_once('language.php');
if (isset($_SESSION['login_id'])) {
	if (!isLoggedIn($_SESSION['login_id'], $_SESSION['login_uname'], $_SESSION['login_pw'])) {
		displayLoginPage();
		exit();
	}
} elseif (isset($_COOKIE['fcms_login_id'])) {
	if (isLoggedIn($_COOKIE['fcms_login_id'], $_COOKIE['fcms_login_uname'], $_COOKIE['fcms_login_pw'])) {
		$_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
		$_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
		$_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
	} else {
		displayLoginPage();
		exit();
	}
} else {
	displayLoginPage();
	exit();
}
header("Cache-control: private");
include_once('calendar_class.php');
$calendar = new Calendar($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName() . " - " . $LANG['poweredby'] . " " . getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<style type="text/css">
#big_calendar { width: 658px; border-collapse: collapse; }
h3 { text-align: center; }
th { height: 50px; }
td { padding: 0 0 30px 2px; width: 94px; border: 1px solid #000; vertical-align: top; line-height: 10pt; overflow: hidden; }
.weekDays { padding: 3px; background-color: #ccc; text-align: center; font-weight: bold; }
.nonMonthDay { background-color: #eee; }
.add, .prev, .next { display: none; }
</style>
</head>
<body onload="window.print();">
<?php
$year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? str_pad($_GET['month'], 2, 0, STR_PAD_LEFT) : date('m');
$day = isset($_GET['day']) ? str_pad($_GET['day'], 2, 0, STR_PAD_LEFT) : date('d');
$calendar->displayCalendar($month, $year, $day, 'big');
?>
<p>&nbsp;</p>
<p>&nbsp;</p>
</body>
</html>