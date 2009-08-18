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
// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['admin_board'];
$TMPL['path'] = "../";
$TMPL['admin_path'] = "";
include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'header.php');
?>
	<div id="leftcolumn">
        <?php
        include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'sidenav.php');
        if (checkAccess($_SESSION['login_id']) < 3) {
            include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'adminnav.php');
        }
        ?>
	</div>
	<div id="content">
		<div id="messageboard" class="centercontent">
			<?php
			if (checkAccess($_SESSION['login_id']) > 1) {
				echo "<p class=\"error-alert\"><b>".$LANG['err_no_access1']."</b><br/>".$LANG['err_no_access_board2']." <a href=\"../contact.php\">".$LANG['err_no_access3']."</a> ".$LANG['err_no_access4']."</a>";				
			} else {
				if (isset($_GET['del'])) {
					$sql = "DELETE FROM `fcms_board_threads` WHERE `id`=" . $_GET['del'];
					mysql_query($sql) or displaySQLError('Delete Thread Error', 'admin/board.php [' . __LINE__ . ']', $sql, mysql_error());
					echo "<meta http-equiv='refresh' content='0;URL=board.php'>";
				} elseif (isset($_POST['edit_submit'])) {
					if (isset($_POST['sticky'])) { $subject = "#ANOUNCE#" . $_POST['subject']; } else { $subject = $_POST['subject']; }
					$sql = "UPDATE `fcms_board_threads` SET `subject` = '".addslashes($subject)."' WHERE `id` = " . $_POST['threadid'];
					mysql_query($sql) or displaySQLError('Edit Thread Error', 'admin/board.php [' . __LINE__ . ']', $sql, mysql_error());
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