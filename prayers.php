<?php
session_start();
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	$_POST = array_map('stripslashes', $_POST);
	$_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');
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
include_once('inc/prayers_class.php');
$prayers = new Prayers($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName() . " - " . $LANG['poweredby'] . " " . getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="themes/images/favicon.ico"/>
<script src="inc/prototype.js" type="text/javascript"></script>
</head>
<body id="body-prayers">
	<div><a name="top"></a></div>
	<div id="header"><?php echo "<h1 id=\"logo\">$cfg_sitename</h1><p>".$LANG['welcome']." <a href=\"profile.php?member=".$_SESSION['login_id']."\">"; echo getUserDisplayName($_SESSION['login_id']); echo "</a> | <a href=\"settings.php\">".$LANG['link_settings']."</a> | <a href=\"logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></p>"; ?></div>
	<?php displayTopNav(); ?>
	<div id="pagetitle"><?php echo $LANG['link_prayer']; ?></div>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<?php
		displaySideNav();
		if(checkAccess($_SESSION['login_id']) < 3) { 
			echo "\t<h2>".$LANG['admin']."</h2>\n\t"; 
			displayAdminNav("fix");
		} ?></div>
	<div id="content">
		<div id="prayers" class="centercontent">
			<?php
			$show = true;
			if (isset($_POST['submitadd'])) {
				$for = addslashes($_POST['for']);
				$desc = addslashes($_POST['desc']);
				mysql_query("INSERT INTO `fcms_prayers`(`for`, `desc`, `user`, `date`) VALUES('$for', '$desc', " . $_SESSION['login_id'] . ", NOW())") or die("<h1>New Prayer Error (prayers.php 69)</h1>" . mysql_error());
				echo "<p class=\"ok-alert\" id=\"add\">".$LANG['ok_pray_add']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('add').toggle()\",3000); }</script>";
			} 
			if (isset($_POST['submitedit'])) {
				$for = addslashes($_POST['for']);
				$desc = addslashes($_POST['desc']);
				mysql_query("UPDATE `fcms_prayers` SET `for` = '$for', `desc` = '$desc' WHERE `id` = " . $_POST['id']) or die("<h1>Edit Prayer Error (prayers.php 69)</h1>" . mysql_error());
				echo "<p class=\"ok-alert\" id=\"edit\">".$LANG['ok_pray_edit']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('edit').toggle()\",3000); }</script>";
			}
			if (isset($_POST['delprayer'])) {
				mysql_query("DELETE FROM `fcms_prayers` WHERE id = " . $_POST['id']) or die("<h1>Delete Prayers Error (prayers.php 83)</h1>" . mysql_error());
				echo "<p class=\"ok-alert\" id=\"del\">".$LANG['ok_pray_del']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('del').toggle()\",2000); }</script>";
			}
			if (isset($_GET['addconcern']) && checkAccess($_SESSION['login_id']) <= 5) {
				$show = false;
				$prayers->displayForm('add');
			}
			if (isset($_POST['editprayer'])) {
				$show = false;
				$prayers->displayForm('edit', $_POST['id'], $_POST['for'], $_POST['desc']);
			}
			if ($show) {
				if (checkAccess($_SESSION['login_id']) <= 5) {
					echo "<div style=\"text-align: right\"><a href=\"?addconcern=yes\">".$LANG['add_prayer']."</a></div>\n";
				}
				$page = 1;
				if (isset($_GET['page'])) { $page = $_GET['page']; }
				$prayers->showPrayers($page);
			} ?>
		</div><!-- #prayers .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>