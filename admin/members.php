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
	<div id="pagetitle">Administration: Members</div>
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
		<div class="centercontent">
			<?php
			if (checkAccess($_SESSION['login_id']) < 2) {
				if (isset($_POST['edit'])) {
					$id = $_POST['id'];
					$access = (int)$_POST['access'];
					if (!empty($access) && is_numeric($access) && $access <= 3 && $access > 0) {
						if($_POST['activated'] > 0) {
							mysql_query("UPDATE fcms_users SET activated = 1 WHERE id = $id") or die('<h1>Activate Error (admin/members.php 71)</h1>' . mysql_error());
						} else {
							mysql_query("UPDATE fcms_users SET activated = 0 WHERE id = $id") or die('<h1>Activate Error (admin/members.php 73)</h1>' . mysql_error());
						}
						mysql_query("UPDATE fcms_users SET access = $access WHERE id = $id") or die('<h1>Access Error (admin/members.php 75)</h1>' . mysql_error());
						echo "<p class=\"ok-alert\">Update Successful.</p>";
					}
					echo "<meta http-equiv='refresh' content='0;URL=members.php'>";
				}
				if (isset($_POST['del'])) {
					$id = $_POST['id'];
					mysql_query("DELETE FROM fcms_users WHERE id = $id") or die('<h1>Delete Error (admin/members.php 82)</h1>' . mysql_error());
					echo "<meta http-equiv='refresh' content='0;URL=members.php'>";
				} ?>
				<p class="info-alert">You must click the edit button for each member for changes to take effect when changing Access Levels or Activating members.</p>
				<table width="100%"><thead><tr><th>ID</th><th>Username (Full Name)</th><th width="20%"><a class="help u" title="Get Help using Access Levels" href="../help.php#adm-access">Access Level</a></th><th>Activated?</th><th>Edit/Delete</th></tr></thead><tbody>
				<?php 
				$result = mysql_query("SELECT * FROM fcms_users WHERE password != 'NONMEMBER'") or die('<h1>Get Members Error (admin/members.php 87)</h1>' . mysql_error());
				while($r=mysql_fetch_array($result)) {
					$id = $r['id'];
					$username = $r['username'];
					$fname = $r['fname'];
					$lname = $r['lname'];
					$access = $r['access'];
					$activated = $r['activated'];
					echo "<tr><form method=\"post\" action=\"members.php\"><td><b>$id</b>:</td><td> $username &nbsp;( $fname $lname )</td>"; 
					echo "<td><select id=\"access\" name=\"access\"><option value=\"1\""; if ($access == 1) { echo " selected=\"selected\""; }
					echo ">Admin</option><option value=\"2\""; if ($access == 2) { echo " selected=\"selected\""; }
					echo ">Helper</option><option value=\"3\""; if ($access == 3) { echo " selected=\"selected\""; }
					echo ">Member</option></select></td><td style=\"text-align:center\"><input name=\"activated\" id=\"activated\" type=\"checkbox\"";  if($activated > 0) { echo " checked=\"checked\""; }  echo " value=\"1\"/><td> &nbsp;<input type=\"submit\" name=\"edit\" id=\"edit\" value=\" \" class=\"editbtn\" title=\"Edit User Details\"/> / ";
					echo "<input type=\"submit\" name=\"del\" id=\"del\" value=\" \" class=\"delbtn\" title=\"Delete User\" onclick=\"javascript:return confirm('Are you sure?  This IS NOT recommended!&#92;n&#92;nALL message board posts, photos, news, and comments will be deleted as well.&#92;nPlease read the help documentation for more info.'); \"/><input type=\"hidden\" name=\"id\" value=\"$id\"/></td></form></tr>";
				}
			} else {
				echo "<p class=\"error-alert\"><b>You do not have access to view this page.</b><br/>This page requires an access level of 1.  Please <a href=\"../contact.php\">contact</a> your website's administrator if you feel you should have access to this page.</a>";
			} ?>
			</tbody></table>
			<p>&nbsp;</p>
			<p>&nbsp;</p>
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