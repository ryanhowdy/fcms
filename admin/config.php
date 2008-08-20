<?php
session_start();
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	$_POST = array_map('stripslashes', $_POST);
	$_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');
include_once('../inc/language.php');
if (isset($_SESSION['login_id'])) {
	if (!isLoggedIn($_SESSION['login_id'], $_SESSION['login_uname'], $_SESSION['login_pw'])) {
		displayLoginPage("fix");
		exit();
	}
} elseif (isset($_COOKIE['fcms_login_id'])) {
	if (isLoggedIn($_COOKIE['fcms_login_id'], $_COOKIE['fcms_login_uname'], $_COOKIE['fcms_login_pw'])) {
		$_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
		$_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
		$_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
	} else {
		displayLoginPage("fix");
		exit();
	}
} else {
	displayLoginPage("fix");
	exit();
}
header("Cache-control: private");
include_once('../inc/admin_class.php');
$admin = new Admin($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName()." - ".$LANG['poweredby']." ".getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="../<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="themes/images/favicon.ico"/>
<script src="../inc/prototype.js" type="text/javascript"></script>
</head>
<body>
	<div><a name="top"></a></div>
	<div id="header"><?php echo "<h1 id=\"logo\">".getSiteName()."</h1><p>".$LANG['welcome']." <a href=\"../profile.php?member=".$_SESSION['login_id']."\">"; echo getUserDisplayName($_SESSION['login_id']); echo "</a> | <a href=\"../settings.php\">".$LANG['link_settings']."</a> | <a href=\"../logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></p>"; ?></div>
	<?php displayTopNav("fix"); ?>
	<div id="pagetitle"><?php echo $LANG['admin_config']; ?></div>
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
			if (checkAccess($_SESSION['login_id']) > 2) {
				echo "<p class=\"error-alert\"><b>".$LANG['err_no_access1']."</b><br/>".$LANG['err_no_access_member2']." <a href=\"../contact.php\">".$LANG['err_no_access3']."</a> ".$LANG['err_no_access4']."</a>";
			} else {
				$show = true;
				if (isset($_POST['submit-sections'])) {
					if ($_POST['nav_top1'] == $_POST['nav_top2'] || $_POST['nav_top1'] == $_POST['nav_side1'] || $_POST['nav_top1'] == $_POST['nav_side2'] || $_POST['nav_side1'] == $_POST['nav_side2'] || $_POST['nav_side2'] == $_POST['nav_top2'] || $_POST['nav_side1'] == $_POST['nav_top2']) {
						echo "<p class=\"error-alert\" id=\"update\">You cannot have the same section twice.</p>";
						echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
					} else {
						mysql_query("UPDATE `fcms_config` SET `nav_top1` = ".$_POST['nav_top1'].", `nav_top2` = ".$_POST['nav_top2'].", `nav_side1` = ".$_POST['nav_side1'].", `nav_side2` = ".$_POST['nav_side2']) or die("<h1>Update Nav Error (config.php 69)</h1>" . mysql_error());
						echo "<p class=\"ok-alert\" id=\"update\">Your configurations were updated successfully.</p>";
						echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
					}
				}
				if (isset($_GET['addsection'])) {
					if ($_GET['addsection'] == 'news') {
						mysql_query("CREATE TABLE `fcms_news` (`id` int(11) NOT NULL auto_increment, `title` varchar(50) NOT NULL default '', `news` text NOT NULL, `user` int(11) NOT NULL default '0', `date` datetime NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY  (`id`), KEY `userindx` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
						mysql_query("ALTER TABLE `fcms_news` ADD CONSTRAINT `fcms_news_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE") or die(mysql_error());
						mysql_query("CREATE TABLE `fcms_news_comments` (`id` int(11) NOT NULL auto_increment, `news` int(11) NOT NULL default '0', `comment` text NOT NULL, `date` timestamp NOT NULL default '0000-00-00 00:00:00', `user` int(11) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `photo_ind` (`news`), KEY `user_ind` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
						mysql_query("ALTER TABLE `fcms_news_comments` ADD CONSTRAINT `fcms_news_comments_ibfk_2` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fcms_news_comments_ibfk_1` FOREIGN KEY (`news`) REFERENCES `fcms_news` (`id`) ON DELETE CASCADE") or die(mysql_error());
					}
					if ($_GET['addsection'] == 'prayers') {
						mysql_query("CREATE TABLE `fcms_prayers` (`id` int(11) NOT NULL auto_increment, `for` varchar(50) NOT NULL default '', `desc` text NOT NULL, `user` int(11) NOT NULL default '0', `date` datetime NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY  (`id`), KEY `userindx` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
						mysql_query("ALTER TABLE `fcms_prayers` ADD CONSTRAINT `fcms_prayers_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE") or die(mysql_error());
					}
					if ($_GET['addsection'] == 'recipes') {
						mysql_query("CREATE TABLE `fcms_recipes` (`id` INT(11) NOT NULL AUTO_INCREMENT, `category` VARCHAR(50) NOT NULL, `name` VARCHAR(50) NOT NULL DEFAULT 'My Recipe', `recipe` TEXT NOT NULL, `user` INT(11) NOT NULL, `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die("<h1>Create Recipe Error (config.php 87)</h1>".mysql_error());
						mysql_query("ALTER TABLE `fcms_recipes` ADD CONSTRAINT `fcms_recipes_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE") or die("<h1>Alter Recipe Error (config.php 88)</h1>".mysql_error());
					}
				}
				if ($show) {
					$admin->displayAdminConfig();
				}
			}
			?><p>&nbsp;</p>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<?php displayFooter("fix"); ?>
</body>
</html>