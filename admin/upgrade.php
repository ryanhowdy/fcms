<?php
session_start();
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');
include_once('../inc/language.php');
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
include_once('../inc/admin_class.php');
$admin = new Admin($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo $cfg_sitename." - ".$LANG['poweredby']." ".$stgs_release; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="../<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="../themes/images/favicon.ico"/>
</head>
<body>
	<div><a name="top"></a></div>
	<div id="header"><?php echo "<h1 id=\"logo\">$cfg_sitename</h1><p>".$LANG['welcome']." <a href=\"../profile.php?member=".$_SESSION['login_id']."\">"; echo getUserDisplayName($_SESSION['login_id']); echo "</a> | <a href=\"../settings.php\">".$LANG['link_settings']."</a> | <a href=\"../logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></p>"; ?></div>
	<?php displayTopNav("fix"); ?>
	<div id="pagetitle"><?php echo $LANG['admin_upgrade']; ?></div>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<?php
		displaySideNav("fix");
		if(checkAccess($_SESSION['login_id']) < 3) { 
			echo "\t<h2>".$LANG['admin']."</h2>\n\t"; 
			displayAdminNav();
		} ?></div>
	<div id="content">
		<div class="centercontent">
			<?php
			if (isset($_POST['upgrade'])) {
				upgrade($_POST['version']);
			} else {
				echo "<h2>".$LANG['upgrade_check']."</h2><p><b>".$LANG['cur_version'].":</b> &nbsp;$stgs_release</p><p><b>".$LANG['latest_version'].":</b> &nbsp;&nbsp;&nbsp;";
				$ver = file("http://www.haudenschilt.com/fcms/latest_version.php");
				$uptodate = false;
				if (str_replace(".", "", substr($ver[0], 18)) >= str_replace(".", "",substr($stgs_release, 18))) {
					$uptodate = true;
					echo $ver[0]." <span style=\"padding-left:5px;font-size:small;font-weight:bold;color:green\">Awesome, your installation is up to date.</span>";
				} else {
					echo $ver[0]." <span style=\"padding-left:5px;font-size:small;font-weight:bold;color:red\">Bummer!, your installation is out of date.</span>";
				}
				echo "</p>\n\t\t\t<form method=\"post\" action=\"upgrade.php\"><div><input type=\"hidden\" name=\"version\" value=\"$ver[0]\"/><input type=\"submit\" name=\"upgrade\" value=\"".$LANG['link_admin_upgrade']."\"";
				if ($uptodate) { echo " onclick=\"javascript:return confirm('".$LANG['js_upgrade']."');\""; }
				echo "/></div></form>\n";
			} ?>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<div id="footer">
		<p>
			<a href="http://www.haudenschilt.com/fcms/" class="ft"><?php echo $LANG['link_home']; ?></a> | <a href="http://www.haudenschilt.com/forum/index.php" class="ft"><?php echo $LANG['link_support']; ?></a> | <a href="../help.php" class="ft"><?php echo $LANG['link_help']; ?></a><br />
			<a href="http://www.haudenschilt.com/fcms/"><?php echo $stgs_release; ?></a> - Copyright &copy; 2006/07 Ryan Haudenschilt.  
		</p>
	</div>
</body>
</html>
<?php
function upgrade ($version) {
	global $LANG, $cfg_mysql_db;
	echo "<h2>".$LANG['upgrading_to']." $version</h2><p>".$LANG['upgrade_process']."...</p>";
	/*
	 * FCMS 0.9.5
	 * Add the dst field to fcms_users table
	 */
	echo "<p>".$LANG['upgrade_dst']."...";
	$result = mysql_query("SHOW COLUMNS FROM `fcms_users`") or die("</p><p style=\"color:red\">".$LANG['not_search_fields']."</p><p style=\"color:red\">".mysql_error()."</p>");
	$user_dst_fixed = false;
	if (mysql_num_rows($result) > 0) {
		while($r = mysql_fetch_array($result)) {
			if ($r[Field] == 'dst') { $user_dst_fixed = true; }
		}
	}
	if ($user_dst_fixed) {
		echo "<span style=\"color:green\">".$LANG['no_changes']."</span></p>";
	} else {
		mysql_query("ALTER TABLE `fcms_users` ADD `dst` tinyint(1) NOT NULL default '0'") or die("</p><p style=\"color:red\">".$LANG['not_add_dst']."</p><p style=\"color:red\">".mysql_error()."</p>");
		echo "<span style=\"color:green\">".$LANG['complete']."</span></p>";
	}
	mysql_free_result($result);
	/*
	 * FCMS 0.9.9
	 * Add the frontpage field to fcms_users table
	 */
	echo "<p>Upgrading Frontpage Settings...";
	$result = mysql_query("SHOW COLUMNS FROM `fcms_users`") or die("</p><p style=\"color:red\">".$LANG['not_search_fields']."</p><p style=\"color:red\">".mysql_error()."</p>");
	$user_frontpage_fixed = false;
	if (mysql_num_rows($result) > 0) {
		while($r = mysql_fetch_array($result)) {
			if ($r[Field] == 'frontpage') { $user_frontpage_fixed = true; }
		}
	}
	if ($user_frontpage_fixed) {
		echo "<span style=\"color:green\">".$LANG['no_changes']."</span></p>";
	} else {
		mysql_query("ALTER TABLE `fcms_users` ADD COLUMN `frontpage` set('1','2') NOT NULL default '1'") or die("</p><p style=\"color:red\">".$LANG['not_add_frontpage']."</p><p style=\"color:red\">".mysql_error()."</p>");
		echo "<span style=\"color:green\">".$LANG['complete']."</span></p>";
	}
	mysql_free_result($result);
	/*
	 * FCMS 1.0
	 * Add the private field to fcms_calendar table
	 */
	echo "<p>Upgrading Calendar...";
	$result = mysql_query("SHOW COLUMNS FROM `fcms_calendar`") or die("</p><p style=\"color:red\">".$LANG['not_search_fields']."</p><p style=\"color:red\">".mysql_error()."</p>");
	$cal_private_fixed = false;
	if (mysql_num_rows($result) > 0) {
		while($r = mysql_fetch_array($result)) {
			if ($r[Field] == 'private') { $cal_private_fixed = true; }
		}
	}
	if ($cal_private_fixed) {
		echo "<span style=\"color:green\">".$LANG['no_changes']."</span></p>";
	} else {
		mysql_query("ALTER TABLE `fcms_calendar` ADD `private` TINYINT(1) NOT NULL DEFAULT '0'") or die("</p><p style=\"color:red\">".$LANG['not_add_private']."</p><p style=\"color:red\">".mysql_error()."</p>");
		echo "<span style=\"color:green\">".$LANG['complete']."</span></p>";
	}
	mysql_free_result($result);
	/*
	 * FCMS 1.0
	 * Rename the message board tables
	 */
	echo "<p>Upgrading Message Board...";
	$result = mysql_query("SHOW TABLES FROM $cfg_mysql_db") or die("</p><p style=\"color:red\">".$LANG['not_search_tables']."</p><p style=\"color:red\">".mysql_error()."</p>");
	$threads_posts_fixed = false;
	if (mysql_num_rows($result) > 0) {
		while($r = mysql_fetch_array($result)) {
			if ($r[0] == 'fcms_board_threads') { $threads_posts_fixed = true; }
		}
	}
	if ($threads_posts_fixed) {
		echo "<span style=\"color:green\">".$LANG['no_changes']."</span></p>";
	} else {
		mysql_query("ALTER TABLE `fcms_threads` RENAME TO `fcms_board_threads`") or die("</p><p style=\"color:red\">".$LANG['not_rename_threads']."</p><p style=\"color:red\">".mysql_error()."</p>");
		mysql_query("ALTER TABLE `fcms_posts` RENAME TO `fcms_board_posts`") or die("</p><p style=\"color:red\">".$LANG['not_rename_posts']."</p><p style=\"color:red\">".mysql_error()."</p>");
		echo "<span style=\"color:green\">".$LANG['complete']."</span></p>";
	}
	/*
	 * FCMS 1.2
	 * Rename the userid field in the fcms_address table
	 */
	echo "<p>Upgrading Address Book...";
	$result = mysql_query("SHOW COLUMNS FROM `fcms_address`") or die("</p><p style=\"color:red\">".$LANG['not_search_fields']."</p><p style=\"color:red\">".mysql_error()."</p>");
	$address_user_fixed = false;
	if (mysql_num_rows($result) > 0) {
		while($r = mysql_fetch_array($result)) {
			if ($r[Field] == 'user') { $address_user_fixed = true; }
		}
	}
	if ($address_user_fixed) {
		echo "<span style=\"color:green\">".$LANG['no_changes']."</span></p>";
	} else {
		mysql_query("ALTER TABLE `fcms_address` CHANGE `userid` `user` INT(11) NOT NULL DEFAULT '0' ") or die("</p><p style=\"color:red\">".$LANG['not_rename_userid']."</p><p style=\"color:red\">".mysql_error()."</p>");
		echo "<span style=\"color:green\">".$LANG['complete']."</span></p>";
	}
	/*
	 * FCMS 1.3
	 * Add the entered_by field to the fcms_address table.
	 */
	echo "<p>Upgrading Address Book...";
	$result = mysql_query("SHOW COLUMNS FROM `fcms_address`") or die("</p><p style=\"color:red\">".$LANG['not_search_fields']."</p><p style=\"color:red\">".mysql_error()."</p>");
	$address_user_fixed = false;
	if (mysql_num_rows($result) > 0) {
		while($r = mysql_fetch_array($result)) {
			if ($r[Field] == 'entered_by') { $address_user_fixed = true; }
		}
	}
	if ($address_user_fixed) {
		echo "<span style=\"color:green\">".$LANG['no_changes']."</span></p>";
	} else {
		mysql_query("ALTER TABLE `fcms_address` ADD `entered_by` INT(11) NOT NULL DEFAULT '0'") or die("</p><p style=\"color:red\">".$LANG['not_add_enteredby']."</p><p style=\"color:red\">".mysql_error()."</p>");
		$results = mysql_query("SELECT `id`, `user` FROM `fcms_address` ORDER BY `id`") or die("</p><p style=\"color:red\">Could not search existing address data.  FCMS could not be upgraded.</p><p style=\"color:red\">".mysql_error()."</p>");
		while ($row = mysql_fetch_array($results)) {
			mysql_query("UPDATE `fcms_address` set `entered_by` = ".$row['user']." WHERE id = ".$row['id']) or die("</p><p style=\"color:red\">".$LANG['not_update_enteredby']."</p><p style=\"color:red\">".mysql_error()."</p>");
			if ($row['id'] !== $row['user']) {
				mysql_query("UPDATE `fcms_address` set `user` = ".$row['id']." WHERE id = ".$row['id']) or die("</p><p style=\"color:red\">".$LANG['not_update_user']."</p><p style=\"color:red\">".mysql_error()."</p>");
			}
		}
		echo "<span style=\"color:green\">".$LANG['complete']."</span></p>";
	}
	echo "<p style=\"color:green\">Upgrade is finished.</p>";
}
?>