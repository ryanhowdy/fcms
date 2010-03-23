<?php
session_start();
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');

// Check that the user is logged in
isLoggedIn();

header("Cache-control: private");
include_once('inc/profile_class.php');
$profile = new Profile($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['link_profiles'];
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
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
			<?php
			if (isset($_GET['member'])) {
				$profile->displayProfile($_GET['member']);
			} elseif (isset($_GET['awards'])) {
				$profile->displayAwards();
			} else {
				$profile->displayAll();
			} ?>
		</div><!-- #profile .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>