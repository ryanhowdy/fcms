<?php
session_start();
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	// a bug found with an array in $_POST
	if (!isset($_POST['del'])) {
		$_POST = array_map('stripslashes', $_POST);
	}
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
include_once('inc/privatemsg_class.php');
$pm = new PrivateMessage($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$pagetitle = $LANG['link_privatemsg'];
$d = "";
$admin_d = "admin/";
include_once(getTheme($_SESSION['login_id']) . 'header.php');
?>
	<div id="leftcolumn">
        <?php
        include_once(getTheme($_SESSION['login_id']) . 'sidenav.php');
        if (checkAccess($_SESSION['login_id']) < 3) {
            include_once(getTheme($_SESSION['login_id']) . 'adminnav.php');
        }
        ?>
	</div>
	<div id="content">
		<div id="profile" class="centercontent">
			<p><a href="profile.php"><?php echo $LANG['profiles']; ?></a> | <a href="privatemsg.php"><?php echo $LANG['privatemsgs']; ?></a> | <a href="profile.php?awards=yes"><?php echo $LANG['link_admin_awards']; ?></a></p>
			<p><a href="privatemsg.php"><?php echo $LANG['inbox']; ?></a> | <a href="?compose=new"><?php echo $LANG['new_pmsg']; ?></a></p>
			<?php
			$show = true;
			if (isset($_GET['compose'])) {
				$show = false;
				if (isset($_GET['id']) && !isset($_GET['title'])) {
					$pm->displayNewMessageForm($_GET['id']);
				} elseif (isset($_GET['id']) && isset($_GET['title'])) {
					$pm->displayNewMessageForm($_GET['id'], $_GET['title']);
				} else {
					$pm->displayNewMessageForm();
				}
			} elseif (isset($_POST['submit'])) {
				$title = addslashes($_POST['title']);
				$msg = addslashes($_POST['post']);
				$sql = "INSERT INTO `fcms_privatemsg`(`to`, `from`, `date`, `title`, `msg`) "
					. "VALUES(" . $_POST['to'] . ", " . $_SESSION['login_id'] . ", NOW(), '$title', '$msg')";
				mysql_query($sql) or displaySQLError('Send PM Error', 'privatemsg.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"sent\">" . $LANG['pm_sent_to'] . " " . getUserDisplayName($_POST['to']) . "</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('sent').toggle()\",3000); }</script>";
			} elseif (isset($_POST['delete'])) {
				if (isset($_POST['del'])) {
					foreach ($_POST['del'] as $id) {
						$sql = "DELETE FROM `fcms_privatemsg` WHERE `id` = $id";
						mysql_query($sql) or displaySQLError('Delete PM Error', 'privatemsg.php [' . __LINE__ . ']', $sql, mysql_error());
					}
					echo "<p class=\"ok-alert\" id=\"del\">" . $LANG['pm_deleted'] . "</p>";
					echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('del').toggle()\",3000); }</script>";
				}
			} elseif (isset($_GET['pm'])) {
				$show = false;
				$pm->displayPM($_GET['pm']);
			}
			if ($show) {
				$pm->displayInbox();
			} ?>
		</div><!-- #profile .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>