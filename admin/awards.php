<?php
session_start();
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
$pagetitle = $LANG['admin_awards'];
$d = "../";
$admin_d = "";
include_once(getTheme($_SESSION['login_id'], $d) . 'header.php');
?>
	<div id="leftcolumn">
        <?php
        include_once(getTheme($_SESSION['login_id'], $d) . 'sidenav.php');
        if (checkAccess($_SESSION['login_id']) < 3) {
            include_once(getTheme($_SESSION['login_id'], $d) . 'adminnav.php');
        }
        ?>
	</div>
	<div id="content">
		<div class="centercontent">
			<?php
			if (checkAccess($_SESSION['login_id']) > 2) {
				echo "<p class=\"error-alert\"><b>".$LANG['err_no_access1']."</b><br/>".$LANG['err_no_access_awards2']." <a href=\"../contact.php\">".$LANG['err_no_access3']."</a> ".$LANG['err_no_access4']."</a>";
			} else {
				if (isset($_POST['submit'])) {
					$worked = $admin->getTopThreadStarter();
					$admin->getMostViewedPhoto();
					$admin->getTopPosters();
					$admin->getTopPhotoSubmitters();
					$admin->getMostSmileys();
					if ($worked) { 
						echo "<p class=\"ok-alert\">".$LANG['ok_awards']."</p>";
					} else {
						echo "<p class=\"info-alert\">".$LANG['no_awards']."</p>";
					}
				} else {
					echo "<p>&nbsp;</p><form method=\"post\" action=\"awards.php\"><div class=\"center\"><input type=\"submit\" name=\"submit\" value=\"".$LANG['get_awards']."\"/></div></form>";
				}
			} ?>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<?php displayFooter("fix"); ?>
</body>
</html>