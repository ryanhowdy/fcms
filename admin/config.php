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
$pagetitle = $LANG['admin_config'];
$d = "../";
$admin_d = "";
$livevalidation = true;
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
				echo "<p class=\"error-alert\"><b>".$LANG['err_no_access1']."</b><br/>".$LANG['err_no_access_member2']." <a href=\"../contact.php\">".$LANG['err_no_access3']."</a> ".$LANG['err_no_access4']."</a>";
			} else {
				$show = true;
				if (isset($_POST['submit-sitename'])) {
					if (isset($_POST['sitename'])) {
						$sql = "UPDATE `fcms_config` SET `sitename` = '" . addslashes($_POST['sitename']) . "'";
						mysql_query($sql) or displaySQLError('Sitename Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
					}
					if (isset($_POST['contact'])) {
						$sql = "UPDATE `fcms_config` SET `contact` = '" . addslashes($_POST['contact']) . "'";
						mysql_query($sql) or displaySQLError('Contact Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
					}
					if (isset($_POST['activation'])) {
						$sql = "UPDATE `fcms_config` SET `auto_activate` = " . $_POST['activation'];
						mysql_query($sql) or displaySQLError('Activation Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
					}
					echo "<p class=\"ok-alert\" id=\"update\">" . $LANG['config_success'] . "</p>";
					echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
				}
				if (isset($_POST['submit-sections'])) {
					if (isArrayUnique($_POST)) {
						echo "<p class=\"error-alert\" id=\"update\">" . $LANG['section_twice'] . "</p>";
						echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
					} else {
						$pos1 = strrpos($_POST['section1'], "none");
						$pos2 = strrpos($_POST['section2'], "none");
						$pos3 = strrpos($_POST['section3'], "none");
						$pos4 = strrpos($_POST['section4'], "none");
						$everythingIsOK = true;
						// if the first section is set to none, then all of the other sections must be none as well
						if ($pos1 !== false) {
							if ($pos2 === false || $pos3 === false || $pos4 === false) {
								$everythingIsOK = false;
								echo "<p class=\"error-alert\" id=\"update\">".$LANG['opt_before_none']."</p>";
								echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
							}
						// if the second section is set to none, then all of the other sections must be none as well
						} elseif ($pos2 !== false) {
							if ($pos3 === false || $pos4 === false) {
								$everythingIsOK = false;
								echo "<p class=\"error-alert\" id=\"update\">".$LANG['opt_before_none']."</p>";
								echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
							}
						// if the third section is set to none, then all of the other sections must be none as well
						} elseif ($pos3 !== false) {
							if ($pos4 === false) {
								$everythingIsOK = false;
								echo "<p class=\"error-alert\" id=\"update\">".$LANG['opt_before_none']."</p>";
								echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
							}
						}
						if ($everythingIsOK) {
							$sql = "UPDATE `fcms_config` SET `section1` = '".$_POST['section1']."', `section2` = '".$_POST['section2']."', `section3` = '".$_POST['section3']."', `section4` = '".$_POST['section4']."'";
							mysql_query($sql) or displaySQLError('Nav Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
							echo "<p class=\"ok-alert\" id=\"update\">" . $LANG['config_success'] . "</p>";
							echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
						}
					}
				}
				if (isset($_POST['submit-gallery'])) {
					$sql = "UPDATE `fcms_config` SET `full_size_photos` = " . $_POST['full_size_photos'];
					mysql_query($sql) or displaySQLError('Full Size Photos Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
					echo "<p class=\"ok-alert\" id=\"update\">" . $LANG['config_success'] . "</p>";
					echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
				}
				if (isset($_GET['addsection'])) {
					if ($_GET['addsection'] == 'news') {
						$sql = "CREATE TABLE `fcms_news` (`id` int(11) NOT NULL auto_increment, `title` varchar(50) NOT NULL default '', `news` text NOT NULL, `user` int(11) NOT NULL default '0', `date` datetime NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY  (`id`), KEY `userindx` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
						mysql_query($sql) or displaySQLError('New News Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "ALTER TABLE `fcms_news` ADD CONSTRAINT `fcms_news_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
						mysql_query($sql) or displaySQLError('Alter News Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "CREATE TABLE `fcms_news_comments` (`id` int(11) NOT NULL auto_increment, `news` int(11) NOT NULL default '0', `comment` text NOT NULL, `date` timestamp NOT NULL default '0000-00-00 00:00:00', `user` int(11) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `photo_ind` (`news`), KEY `user_ind` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
						mysql_query($sql) or displaySQLError('New News Comments Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "ALTER TABLE `fcms_news_comments` ADD CONSTRAINT `fcms_news_comments_ibfk_2` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fcms_news_comments_ibfk_1` FOREIGN KEY (`news`) REFERENCES `fcms_news` (`id`) ON DELETE CASCADE";
						mysql_query($sql) or displaySQLError('Alter News Comments Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
					}
					if ($_GET['addsection'] == 'prayers') {
						$sql = "CREATE TABLE `fcms_prayers` (`id` int(11) NOT NULL auto_increment, `for` varchar(50) NOT NULL default '', `desc` text NOT NULL, `user` int(11) NOT NULL default '0', `date` datetime NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY  (`id`), KEY `userindx` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
						mysql_query($sql) or displaySQLError('New Prayers Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "ALTER TABLE `fcms_prayers` ADD CONSTRAINT `fcms_prayers_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
						mysql_query($sql) or displaySQLError('Alter Prayers Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
					}
					if ($_GET['addsection'] == 'recipes') {
						$sql = "CREATE TABLE `fcms_recipes` (`id` INT(11) NOT NULL AUTO_INCREMENT, `category` VARCHAR(50) NOT NULL, `name` VARCHAR(50) NOT NULL DEFAULT 'My Recipe', `recipe` TEXT NOT NULL, `user` INT(11) NOT NULL, `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
						mysql_query($sql) or displaySQLError('New Recipe Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "ALTER TABLE `fcms_recipes` ADD CONSTRAINT `fcms_recipes_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
						mysql_query($sql) or displaySQLError('Alter Recipe Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
					}
					if ($_GET['addsection'] == 'documents') {
						$sql = "CREATE TABLE `fcms_documents` (`id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(50) NOT NULL, `description` TEXT NOT NULL, `user` INT(11) NOT NULL, `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
						mysql_query($sql) or displaySQLError('New Documents Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "ALTER TABLE `fcms_documents` ADD CONSTRAINT `fcms_documents_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
						mysql_query($sql) or displaySQLError('Alter Documents Error', 'admin/config.php [' . __LINE__ . ']', $sql, mysql_error());
					}
				}
				if ($show) {
					$admin->displayAdminConfig();
				}
			}
			?><p>&nbsp;</p>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<?php displayFooter("fix"); ?>
</body>
</html>
<?php
function isArrayUnique ($array) { 
	$dup_array = $array; 
	$dup_array = array_unique($dup_array); 
	if (count($dup_array) != count($array)) { 
		return TRUE; 
	} else { 
		return FALSE; 
	} 
} ?>