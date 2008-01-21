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
	<div id="pagetitle">Administration: Message Board</div>
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
		<div id="messageboard" class="centercontent">
			<?php
			if (isset($_GET['del'])) {
				if (checkAccess($_SESSION['login_id']) < 2) {
					mysql_query("DELETE FROM `fcms_threads` WHERE `id`=" . $_GET['del']) or die('<h1>Delete Error (admin/board.php 70)</h1>' . mysql_error());
					echo "<meta http-equiv='refresh' content='0;URL=board.php'>";
				}
			} elseif (isset($_POST['edit_submit'])) {
				if (checkAccess($_SESSION['login_id']) < 2) {
					if($_POST['sticky']) { $subject = "#ANOUNCE#" . $_POST['subject']; } else { $subject = $_POST['subject']; }
					mysql_query("UPDATE `fcms_threads` SET `subject` = '$subject' WHERE `id` = " . $_POST['threadid']) or die('<h1>Edit Error (admin/board.php 76)</h1>' . mysql_error());
				}
			}
			if (isset($_GET['edit'])) {
				if (checkAccess($_SESSION['login_id']) < 2) {
					$admin->displayEditThread($_GET['edit']);
				}
			} else {
				$page = 1;
				if (isset($_GET['page'])) { $page = $_GET['page']; }
				$admin->showThreads('announcement');
				$admin->showThreads('thread', $page);
			} ?>
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