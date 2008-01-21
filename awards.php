<?php
session_start();
include_once('../inc/config.inc.php');
include_once('../inc/util.inc.php');
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
include_once('../inc/admin.class.php');
$admin = new Admin($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title><?php echo $cfg_sitename . " - powered by " . $stgs_release; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="../<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="../themes/images/favicon.ico"/>
</head>
<body>
	<a name="top"></a>
	<div id="header"><h1 id="logo"><?php echo $cfg_sitename; ?></h1><p>Welcome <a href="../profile.php?member=<?php echo $_SESSION['login_id']; ?>"><?php echo getUserDisplayName($_SESSION['login_id']); ?></a> | <a href="../logout.php" title="Logout">Logout</a></p></div>
	<?php displayTopNav("fix"); ?>
	<div id="pagetitle">Administration: Awards</div>
	<div id="leftcolumn">
		<h2>Navigation</h2>
		<div class="firstmenu menu">
			<ul>
				<li><a href="../calendar.php" title="Add / Update Calendar">Update Calendar</a></li>
				<li><a href="../settings.php" title="Change your personal settings">Member Settings</a></li>
				<li><a href="../profile.php" title="View member profiles">Member Profile</a></li>
				<li><a href="../profile.php" title="View member profiles">Member Profile</a></li>
				<li><a href="../contact.php" title="Contact the webmaster">Contact Webmaster</a></li>
				<li><a href="../help.php" title="Get help">Get Help</a></li>
				<li><a href="../logout.php" title="Logout">Logout</a></li>
			</ul>
		</div>
		<?php if(checkAccess($_SESSION['login_id']) < 3) { ?>
		<h2>Administration</h2>
		<div class="menu">
			<ul>
				<?php if (checkAccess($_SESSION['login_id']) < 2) { ?><li><a href="members.php">Members</a></li><?php } ?>
				<?php if (checkAccess($_SESSION['login_id']) < 2) { ?><li><a href="board.php">Message Board</a></li><?php } ?>
				<li><a href="polls.php">Polls</a></li>
				<li><a href="awards.php">Awards</a></li>
			</ul>
		</div>
		<?php } ?></div>
	<div id="content">
		<div class="centercontent">
			<?php
			if (isset($_POST['submit'])) {
				$admin->getTopThreadStarter();
				$admin->getMostViewedPhoto();
				$admin->getTopPosters();
				$admin->getTopPhotoSubmitters();
				$admin->getMostSmileys();
				echo "<p class=\"ok-aler\">The Latest Awards have been calculated successfully.</p>";
			} else {
				echo "<p>&nbsp;</p><form method=\"post\" action=\"awards.php\"><div class=\"center\"><input type=\"submit\" name=\"submit\" value=\"Get Latest Awards\"/></div></form>";
			}
			?>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<div id="footer">
		<p>
			<a href="http://www.haudenschilt.com/fcms/" class="ft">Home</a> | <a href="http://www.haudenschilt.com/forum/index.php" class="ft">Support Forum</a> | <a href="help.php" class="ft">Help</a><br />
			<a href="http://www.haudenschilt.com/fcms/"><?php echo $stgs_release; ?></a> - Copyright &copy; 2006/07 Ryan Haudenschilt.  
		</p>
	</div>
</body>
</html>