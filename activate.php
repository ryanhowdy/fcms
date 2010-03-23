<?php
header("Cache-control: private");
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');
$link = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
mysql_select_db($cfg_mysql_db, $link);?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName() . " - " . $LANG['poweredby'] . " " . getCurrentVersion(); ?></title>
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css" />
</head>
<body>
	<?php
	if (isset($_GET['uid'])) {
		// Santizing user input - uid - only allow digits 0-9
		if (preg_match('/^\d+$/', $_GET['uid'])) {
			echo "<body><div id=\"login_box\"><h1 id=\"reset_header\">" . $LANG['account_activation'] . "</h1>";
			$sql = "SELECT `activate_code` FROM `fcms_users` WHERE `id` = " . $_GET['uid'];
			$result = mysql_query($sql) or displaySQLError('Check Code Error', 'activate.php [' . __LINE__ . ']', $sql, mysql_error());
			$row = mysql_fetch_array($result);
			if (isset($_GET['code'])) {
				if ($row['activate_code'] == $_GET['code']) {
					$sql = "UPDATE `fcms_users` 
                            SET `activated` = 1, `joindate` = NOW() 
                            WHERE `id` = " . $_GET['uid'];
					mysql_query($sql) or displaySQLError('Activation Error', 'activate.php [' . __LINE__ . ']', $sql, mysql_error());
					echo "<p><b>" . $LANG['activate_yes1'] . "</b></p><p>" . $LANG['activate_yes2'] . " <a href=\"index.php\">" . $LANG['activate_yes3'] . "</a> " . $LANG['activate_yes4'] . "</p>";
					echo "<meta http-equiv='refresh' content='3;URL=index.php'>";
				} else {
					echo "<p><b>" . $LANG['activate_no1'] . "</b></p><p>" . $LANG['activate_no2'] . "</p>";
				}
			} else {
				echo "<p><b>" . $LANG['activate_no1'] . "</b></p><p>" . $LANG['activate_no2'] . "</p>";
			}
			echo  "<br/></div>";
		} else {
			echo $LANG['access_denied1'];
		}
	} else {
		echo $LANG['access_denied1'];
	} ?>
</body>
</html>