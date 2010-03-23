<?php
session_start();
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');
include_once('../inc/language.php');

// Check that the user is logged in
isLoggedIn('admin/');

header("Cache-control: private");
include_once('../inc/admin_class.php');
$admin = new Admin($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['admin_awards'];
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