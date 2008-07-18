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
<title><?php echo getSiteName()." - ".$LANG['poweredby']." ".getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="../<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="../themes/images/favicon.ico"/>
</head>
<body>
	<div><a name="top"></a></div>
	<div id="header"><?php echo "<h1 id=\"logo\">".getSiteName()."</h1><p>".$LANG['welcome']." <a href=\"../profile.php?member=".$_SESSION['login_id']."\">"; echo getUserDisplayName($_SESSION['login_id']); echo "</a> | <a href=\"../settings.php\">".$LANG['link_settings']."</a> | <a href=\"../logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></p>"; ?></div>
	<?php displayTopNav("fix"); ?>
	<div id="pagetitle"><?php echo $LANG['admin_board']; ?></div>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<?php
		displaySideNav("fix");
		if(checkAccess($_SESSION['login_id']) < 3) { 
			echo "\t<h2>".$LANG['admin']."</h2>\n\t"; 
			displayAdminNav();
		} ?></div>
	<div id="content">
		<div id="messageboard" class="centercontent">
			<?php
			if (checkAccess($_SESSION['login_id']) > 1) {
				echo "<p class=\"error-alert\"><b>".$LANG['err_no_access1']."</b><br/>".$LANG['err_no_access_board2']." <a href=\"../contact.php\">".$LANG['err_no_access3']."</a> ".$LANG['err_no_access4']."</a>";				
			} else {
				if (isset($_GET['del'])) {
					mysql_query("DELETE FROM `fcms_board_threads` WHERE `id`=" . $_GET['del']) or die('<h1>Delete Error (admin/board.php 69)</h1>' . mysql_error());
					echo "<meta http-equiv='refresh' content='0;URL=board.php'>";
				} elseif (isset($_POST['edit_submit'])) {
					if($_POST['sticky']) { $subject = "#ANOUNCE#" . $_POST['subject']; } else { $subject = $_POST['subject']; }
					mysql_query("UPDATE `fcms_board_threads` SET `subject` = '".addslashes($subject)."' WHERE `id` = " . $_POST['threadid']) or die('<h1>Edit Error (admin/board.php 75)</h1>' . mysql_error());
				}
				if (isset($_GET['edit'])) {
					$admin->displayEditThread($_GET['edit']);
				} else {
					$page = 1;
					if (isset($_GET['page'])) { $page = $_GET['page']; }
					$admin->showThreads('announcement');
					$admin->showThreads('thread', $page);
				}
			} ?>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<?php displayFooter("fix"); ?>
</body>
</html>