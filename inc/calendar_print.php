<?php
session_start();

include_once('config_inc.php');
include_once('utils.php');
include_once('calendar_class.php');

// Check that the user is logged in
isLoggedIn();

T_bindtextdomain('messages', '.././language');

$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$calendar = new Calendar($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.T_('lang').'" lang="'.T_('lang').'">
<head>
<title>'.getSiteName().' - '.T_('powered by').' '.getCurrentVersion().'</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<style type="text/css">';

// TODO
// Move to fcms-core.css
echo '
#big-calendar { width: 658px; border-collapse: collapse; }
a { text-decoration: none; }
h3 { text-align: center; }
th { height: 50px; }
td { padding: 0 0 30px 2px; width: 94px; border: 1px solid #000; vertical-align: top; line-height: 10pt; overflow: hidden; }
.weekDays { padding: 3px; background-color: #ccc; text-align: center; font-weight: bold; }
.nonMonthDay { background-color: #eee; }
.add, .prev, .next, .today, .views, .actions { display: none; }
.event { padding: 5px 0 2px 0; }
</style>
</head>
<body onload="window.print();">';

// Use the supplied date, if available
if (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['day'])) {
    $year   = cleanInput($_GET['year'], 'int');
    $month  = cleanInput($_GET['month'], 'int'); 
    $month  = str_pad($month, 2, "0", STR_PAD_LEFT);
    $day    = cleanInput($_GET['day'], 'int');
    $day    = str_pad($day, 2, "0", STR_PAD_LEFT);
// get today's date
} else {
    $year  = fixDate('Y', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
    $month = fixDate('m', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
    $day   = fixDate('d', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
}
$calendar->displayCalendarMonth($month, $year, $day);

echo '
<p>&nbsp;</p>
<p>&nbsp;</p>
</body>
</html>';
