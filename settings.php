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
include 'inc/settings_class.php';
$settings = new Settings($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$pagetitle = $LANG['link_settings'];
$datechooser = 1;
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
		<div class="centercontent">
			<?php
			$emailstart = $settings->cur_user_email;
			if (isset($_POST['submit'])) {
				$birthday = $_POST['syear'] . "-" . str_pad($_POST['smonth'], 2, "0", STR_PAD_LEFT) . "-" . str_pad($_POST['sday'], 2, "0", STR_PAD_LEFT);
				$sql = "UPDATE `fcms_users` SET ";
				if ($_POST['fname']) { $sql .= "fname = '".addslashes($_POST['fname'])."', "; }
				if ($_POST['lname']) { $sql .= "lname = '".addslashes($_POST['lname'])."', "; }
				if ($_POST['email']) { 
					if ($_POST['email'] != $emailstart) {
						$sql = "SELECT `email` FROM `fcms_users` WHERE email='" . $_POST['email'] . "'";
						$result = mysql_query($sql) or displaySQLError('Email Check Error', 'settings.php [' . __LINE__ . ']', $sql, mysql_error());
						$email_check = mysql_num_rows($result);
						if ($email_check > 0) { 
							echo "<p class=\"error-alert\">".$LANG['err_email1']." (" . $_POST['email'] . ") ".$LANG['err_email2']."</p>";
							$settings->displayForm();
							exit();
						}
					$sql .= "email = '".addslashes($_POST['email'])."', ";
					}
				}
				if ($_POST['syear']) { $sql .= "birthday = '$birthday', "; }
				if ($_POST['boardsort']) { $sql .= "boardsort = '" . $_POST['boardsort'] . "', "; }
				if ($_POST['showavatar']) { $sql .= "showavatar = '" . $_POST['showavatar'] . "', "; }
				if ($_POST['displayname']) { $sql .= "displayname = '" . $_POST['displayname'] . "', "; }
				if ($_POST['frontpage']) { $sql .= "frontpage = '" . $_POST['frontpage'] . "', "; }
				if ($_POST['timezone']) { $sql .= "timezone = '" . $_POST['timezone'] . "', "; }
				if ($_POST['dst']) { 
					if ($_POST['dst'] == 'Yes') { $sql .= "dst = '1', "; } elseif ($_POST['dst'] == 'No') { $sql .= "dst = '0', "; }
				}
				$orig_pass = $_SESSION['login_pw'];
				if ($_POST['pass']) {
					$sql .= "password = '" . md5($_POST['pass']) . "', ";
					$_SESSION['login_pw'] = md5($_POST['pass']);
				}
				if ($_FILES['avatar']['name']) {
					$upfile = uploadImages($_FILES['avatar']['type'], $_FILES['avatar']['name'], $_FILES['avatar']['tmp_name'], "gallery/avatar/", 80, 80, 'yes');
					$sql .= "`avatar` = '$upfile', ";
					if ($_POST['avatar_orig'] != '0x0.gif') { unlink("gallery/avatar/" . $_POST['avatar_orig']); }
				}
				$sql .= "theme = '" . $_POST['theme'] . "' WHERE id = " . $_SESSION['login_id'];
				mysql_query($sql) or displaySQLError('Update Settings Error', 'settings.php [' . __LINE__ . ']', $sql, mysql_error());
				if ($orig_pass != md5($_POST['pass']) && !empty($_POST['pass']) && isset($_COOKIE['fcms_login_id'])){
					echo "<p class=\"ok-alert\">".$LANG['ok_stgs_logout1']."</p><p><a href=\"logout.php\">".$LANG['ok_stgs_logout2']."</a>.</p>";
					echo "<meta http-equiv='refresh' content='5;URL=logout.php'>";
				} else {
					echo "<p class=\"ok-alert\">".$LANG['ok_settings1']."</p><p><a href=\"settings.php\">".$LANG['ok_settings2']."</a>.</p>";
				}
			} else {
				$settings->displayForm();
			} ?>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>