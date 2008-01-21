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
<link rel="shortcut icon" href="themes/images/favicon.ico"/>
</head>
<body>
	<a name="top"></a>
	<div id="header"><h1 id="logo"><?php echo $cfg_sitename; ?></h1><p>Welcome <a href="../profile.php?member=<?php echo $_SESSION['login_id']; ?>"><?php echo getUserDisplayName($_SESSION['login_id']); ?></a> | <a href="../logout.php" title="Logout">Logout</a></p></div>
	<?php displayTopNav("fix"); ?>
	<div id="pagetitle">Administration: Polls</div>
	<div id="leftcolumn">
		<h2>Navigation</h2>
		<div class="firstmenu menu">
			<ul>
				<li><a href="../calendar.php" title="Add / Update Calendar">Update Calendar</a></li>
				<li><a href="../settings.php" title="Change your personal settings">Member Settings</a></li>
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
		<div id="vote" class="centercontent">
			<p style="text-align: right;"><a href="?addpoll=yes">Add New Poll</a></p>
			<?php
			if (checkAccess($_SESSION['login_id']) > 2) {
				echo "<p class=\"error-alert\"><b>You do not have access to view this page.</b><br/>This page requires an access level of 2.  Please <a href=\"../contact.php\">contact</a> your website's administrator if you feel you should have access to this page.</a>";
			} else {
				$show = true;
				if (isset($_POST['editsubmit'])) {
					$show = false;
					$result = mysql_query("SELECT max(id) AS c FROM fcms_polls") or die('<h1>Last Poll Error (admin/polls.php 74)</h1>' . mysql_error());
					$found = mysql_fetch_array($result);
					$latest_poll_id = $found['c'];
					$i = 1;
					while ($i <= 10) {
						if ($_POST['show' . $i]) {
							if ($_POST['option' . $i] == 'new') {
								mysql_query("INSERT INTO `fcms_poll_options`(`poll_id`, `option`, `votes`) VALUES ($latest_poll_id, '" . addslashes($_POST['show' . $i]) . "', 0)") or die('<h1>New Option Error (admin/polls.php 81)</h1>' . mysql_error());
							} else {
								mysql_query("UPDATE fcms_poll_options SET `option` = '" . addslashes($_POST['show' . $i]) . "' WHERE id = " . $_POST['option' . $i]) or die('<h1>Update Error (admin/polls.php 83)</h1>' . mysql_error());
							}
						} elseif ($_POST['option' . $i] != 'new') {
							mysql_query("DELETE FROM fcms_poll_options WHERE id = " . $_POST['option' . $i]) or die('<h1>Delete Error (admin/polls.php 86)</h1>' . mysql_error());
						}
						$i++;
					}
					echo "<meta http-equiv='refresh' content='0;URL=polls.php'>";
				}
				if (isset($_POST['addsubmit'])) {
					$show = false;
					$i = 1;
					mysql_query("INSERT INTO `fcms_polls`(`question`, `started`) VALUES ('" . $_POST['question'] . "', NOW())") or die('<h1>New Poll Error (admin/polls.php 95)</h1>' . mysql_error());
					$poll_id = mysql_insert_id();
					while ($i <= 10) {
						if ($_POST['option' . $i]) { mysql_query("INSERT INTO `fcms_poll_options`(`poll_id`, `option`, `votes`) VALUES ($poll_id, '" . $_POST['option' . $i] . "', 0)") or die('<h1>New Option Error (admin/polls.php 98)</h1>' . mysql_error()); }
						$i++;
					}
					mysql_query("TRUNCATE TABLE fcms_poll_users") or die('<h1>Truncate Error (admin/polls.php 101)</h1>' . mysql_error());
					echo "<meta http-equiv='refresh' content='0;URL=polls.php'>";
				}
				if (isset($_POST['delsubmit'])) {
					$show = false;
					$poll_id = $_POST['pollid'];
					mysql_query("DELETE FROM fcms_poll_options WHERE poll_id = $poll_id") or die('<h1>Delete Option Error (admin/polls.php 107)</h1>' . mysql_error());
					mysql_query("DELETE FROM fcms_polls WHERE id = $poll_id") or die('<h1>Delete Poll Error (admin/polls.php 108)</h1>' . mysql_error());
					echo "<meta http-equiv='refresh' content='0;URL=polls.php'>";
				}
				if (isset($_GET['addpoll'])) {
					$show = false;
					$admin->displayAddPollForm();
				}
				if ($show) {
					if (isset($_GET['editpoll'])) { 
						$admin->displayEditPollForm($_GET['editpoll']);
					} else {
						$admin->displayEditPollForm();
					}
					echo "<b>Past Polls</b><br/>";
					$result = mysql_query("SELECT * FROM fcms_polls ORDER BY `started` DESC") or die('<h1>Get Poll Error (admin/polls.php 122)</h1>' . mysql_error());
					while($r = mysql_fetch_array($result)) {
						echo "<a href=\"?page=admin_polls&amp;editpoll=" . $r['id'] . "\">" . $r['question'] . "</a> - " . $r['started'];
						echo " <form class=\"formline\" action=\"polls.php\" method=\"post\"><div><input type=\"submit\" name=\"delsubmit\" class=\"delbtn\" value=\" \" onclick=\"javascript:return confirm('Are you sure you want to DELETE this poll?'); \"/><input type=\"hidden\" name=\"pollid\" value=\"" . $r['id'] . "\"/></div></form><br/>";
					}
				}
			}
			?><p>&nbsp;</p>
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