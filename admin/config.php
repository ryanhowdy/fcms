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

// Check that the user is logged in
isLoggedIn('admin/');

header("Cache-control: private");
include_once('../inc/admin_class.php');
$admin = new Admin($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['admin_config'];
$TMPL['path'] = "../";
$TMPL['admin_path'] = "";
$TMPL['javascript'] = '
<script src="../inc/livevalidation.js" type="text/javascript"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    hideConfig($(\'site_info\'));
    hideConfig($(\'defaults\'));
    hideConfig($(\'sections\'));
    hideConfig($(\'gallery\'));
});
function hideConfig(item) {
    if (item) {
        var hide = document.createElement(\'div\');
        if (hide.style.setAttribute) {
            hide.style.setAttribute("cssText", "text-align:right");
            item.style.setAttribute("cssText", "display:none");
        } else {
            hide.setAttribute("style", "text-align:right");
            item.setAttribute("style", "display:none");
        }
        var link = document.createElement(\'a\');
        link.href = "#";
        link.appendChild(document.createTextNode("'.$LANG['show_hide'].'"));
        link.onclick = function() { item.toggle(); return false; }
        hide.appendChild(link);
        item.insert({before:hide});
    }
    return;
}
//]]>
</script>';

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
				echo "<p class=\"error-alert\"><b>" . $LANG['err_no_access1'] . "</b><br/>";
                echo $LANG['err_no_access_member2'] . " <a href=\"../contact.php\">";
                echo $LANG['err_no_access3'] . "</a> " . $LANG['err_no_access4'] . "</a>";
			} else {
				$show = true;
				if (isset($_POST['submit-sitename'])) {
					if (isset($_POST['sitename'])) {
						$sql = "UPDATE `fcms_config` "
                             . "SET `sitename` = '" . addslashes($_POST['sitename']) . "'";
						mysql_query($sql) or displaySQLError(
                            'Sitename Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
					}
					if (isset($_POST['contact'])) {
						$sql = "UPDATE `fcms_config` "
                             . "SET `contact` = '" . addslashes($_POST['contact']) . "'";
						mysql_query($sql) or displaySQLError(
                            'Contact Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
					}
					if (isset($_POST['activation'])) {
						$sql = "UPDATE `fcms_config` SET `auto_activate` = " . $_POST['activation'];
						mysql_query($sql) or displaySQLError(
                            'Activation Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
					}
					if (isset($_POST['site_off'])) {
						$sql = "UPDATE `fcms_config` ";
                        if ($_POST['site_off'] == 'yes') {
                            $sql .= "SET `site_off` = '1'";
                        } else {
                            $sql .= "SET `site_off` = '0'";
                        }
						mysql_query($sql) or displaySQLError(
                            'Site Off Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
					}
					echo "<p class=\"ok-alert\" id=\"update\">" . $LANG['config_success'] . "</p>";
                    echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
				}
				if (isset($_POST['submit-defaults'])) {
					$sql = "ALTER TABLE `fcms_user_settings` "
                         . "ALTER `theme` SET DEFAULT '".$_POST['theme']."'";
					mysql_query($sql) or displaySQLError(
                        'Theme Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                        );
                    $sql = "ALTER TABLE `fcms_user_settings` ALTER `showavatar` ";
                    if (isset($_POST['showavatar'])) {
                        if ($_POST['showavatar'] == 'yes') {
                            $sql .= "SET DEFAULT '1'";
                        } else {
                            $sql .= "SET DEFAULT '0'";
                        }
                    }
					mysql_query($sql) or displaySQLError(
                        'Show Avatar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                        );
					$sql = "ALTER TABLE `fcms_user_settings` "
                         . "ALTER `displayname` SET DEFAULT '".$_POST['displayname']."'";
					mysql_query($sql) or displaySQLError(
                        'Display Name Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                        );
					$sql = "ALTER TABLE `fcms_user_settings` "
                         . "ALTER `frontpage` SET DEFAULT '".$_POST['frontpage']."'";
					mysql_query($sql) or displaySQLError(
                        'Frontpage Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                        );
					$sql = "ALTER TABLE `fcms_user_settings` "
                         . "ALTER `timezone` SET DEFAULT '".$_POST['timezone']."'";
					mysql_query($sql) or displaySQLError(
                        'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                        );
					$sql = "ALTER TABLE `fcms_user_settings` ALTER `dst` ";
                    if (isset($_POST['dst'])) {
                        if ($_POST['dst'] == 'on') {
                            $sql .= "SET DEFAULT '1'";
                        } else {
                            $sql .= "SET DEFAULT '0'";
                        }
                    }
					mysql_query($sql) or displaySQLError(
                        'DST Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                        );
					$sql = "ALTER TABLE `fcms_user_settings` "
                         . "ALTER `boardsort` SET DEFAULT '".$_POST['boardsort']."'";
					mysql_query($sql) or displaySQLError(
                        'Board Sort Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                    if (isset($_POST['changeAll'])) {
                        $avatar = isset($upfile) ? $upfile : '0x0.gif';
                        $sql = "UPDATE `fcms_user_settings` "
                             . "SET `theme` = '" . $_POST['theme'] . "', ";
                        if (isset($_POST['showavatar'])) {
                            if ($_POST['showavatar'] == 'yes') {
                                $sql .= "`showavatar` = '1', ";
                            } else {
                                $sql .= "`showavatar` = '0', ";
                            }
                        }
                        $sql .= "`displayname` = '" . $_POST['displayname'] . "', "
                              . "`frontpage` = '" . $_POST['frontpage'] . "', "
                              . "`timezone` = '" . $_POST['timezone'] . "', ";
                        if (isset($_POST['dst'])) {
                            if ($_POST['dst'] == 'on') {
                                $sql .= "`dst` = '1', ";
                            } else {
                                $sql .= "`dst` = '0', ";
                            }
                        }
                        $sql .= "`boardsort` = '" . $_POST['boardsort'] . "'";
                        mysql_query($sql) or displaySQLError(
                            'Update All Users Error',  __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                        );
                    }
					echo "<p class=\"ok-alert\" id=\"update\">" . $LANG['config_success'] . "</p>";
                    echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
				}
				if (isset($_POST['submit-sections'])) {
					if (isArrayUnique($_POST)) {
						echo "<p class=\"error-alert\" id=\"update\">" . $LANG['section_twice'] . "</p>";
                        echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
					} else {
						$pos1 = strpos($_POST['section1'], "none");
						$pos2 = strpos($_POST['section2'], "none");
						$pos3 = strpos($_POST['section3'], "none");
						$pos4 = strpos($_POST['section4'], "none");
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
							mysql_query($sql) or displaySQLError('Nav Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
							echo "<p class=\"ok-alert\" id=\"update\">" . $LANG['config_success'] . "</p>";
                            echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
						}
					}
				}
				if (isset($_POST['submit-gallery'])) {
					$sql = "UPDATE `fcms_config` SET `full_size_photos` = " . $_POST['full_size_photos'];
					mysql_query($sql) or displaySQLError('Full Size Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
					echo "<p class=\"ok-alert\" id=\"update\">" . $LANG['config_success'] . "</p>";
                    echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
				}
				if (isset($_GET['addsection'])) {
					if ($_GET['addsection'] == 'news') {
						$sql = "CREATE TABLE `fcms_news` (`id` int(11) NOT NULL auto_increment, `title` varchar(50) NOT NULL default '', `news` text NOT NULL, `user` int(11) NOT NULL default '0', `date` datetime NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY  (`id`), KEY `userindx` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
						mysql_query($sql) or displaySQLError('New News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "ALTER TABLE `fcms_news` ADD CONSTRAINT `fcms_news_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
						mysql_query($sql) or displaySQLError('Alter News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "CREATE TABLE `fcms_news_comments` (`id` int(11) NOT NULL auto_increment, `news` int(11) NOT NULL default '0', `comment` text NOT NULL, `date` timestamp NOT NULL default '0000-00-00 00:00:00', `user` int(11) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `photo_ind` (`news`), KEY `user_ind` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
						mysql_query($sql) or displaySQLError('New News Comments Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "ALTER TABLE `fcms_news_comments` ADD CONSTRAINT `fcms_news_comments_ibfk_2` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fcms_news_comments_ibfk_1` FOREIGN KEY (`news`) REFERENCES `fcms_news` (`id`) ON DELETE CASCADE";
						mysql_query($sql) or displaySQLError('Alter News Comments Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
					}
					if ($_GET['addsection'] == 'prayers') {
						$sql = "CREATE TABLE `fcms_prayers` (`id` int(11) NOT NULL auto_increment, `for` varchar(50) NOT NULL default '', `desc` text NOT NULL, `user` int(11) NOT NULL default '0', `date` datetime NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY  (`id`), KEY `userindx` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
						mysql_query($sql) or displaySQLError('New Prayers Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "ALTER TABLE `fcms_prayers` ADD CONSTRAINT `fcms_prayers_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
						mysql_query($sql) or displaySQLError('Alter Prayers Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
					}
					if ($_GET['addsection'] == 'recipes') {
						$sql = "CREATE TABLE `fcms_recipes` (`id` INT(11) NOT NULL AUTO_INCREMENT, `category` VARCHAR(50) NOT NULL, `name` VARCHAR(50) NOT NULL DEFAULT 'My Recipe', `recipe` TEXT NOT NULL, `user` INT(11) NOT NULL, `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
						mysql_query($sql) or displaySQLError('New Recipe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "ALTER TABLE `fcms_recipes` ADD CONSTRAINT `fcms_recipes_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
						mysql_query($sql) or displaySQLError('Alter Recipe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
					}
					if ($_GET['addsection'] == 'documents') {
						$sql = "CREATE TABLE `fcms_documents` (`id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(50) NOT NULL, `description` TEXT NOT NULL, `user` INT(11) NOT NULL, `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
						mysql_query($sql) or displaySQLError('New Documents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
						$sql = "ALTER TABLE `fcms_documents` ADD CONSTRAINT `fcms_documents_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
						mysql_query($sql) or displaySQLError('Alter Documents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
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
