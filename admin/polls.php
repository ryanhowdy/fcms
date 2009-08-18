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
$TMPL['pagetitle'] = $LANG['admin_polls'];
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
		<div id="polls" class="centercontent">
			<div id="sections_menu" class="clearfix">
                <ul><li><a class="add" href="?addpoll=yes"><?php echo $LANG['add_new_poll']; ?></a></li></ul>
            </div>
			<?php
			if (checkAccess($_SESSION['login_id']) > 2) {
				echo "<p class=\"error-alert\"><b>".$LANG['err_no_access1']."</b><br/>".$LANG['err_no_access_polls2']." <a href=\"../contact.php\">".$LANG['err_no_access3']."</a> ".$LANG['err_no_access4']."</a>";
			} else {
				$show = true;
				if (isset($_POST['editsubmit'])) {
					$show = false;
					$sql = "SELECT MAX(id) AS c FROM `fcms_polls`";
					$result = mysql_query($sql) or displaySQLError('Last Poll Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
					$found = mysql_fetch_array($result);
					$latest_poll_id = $found['c'];
					$i = 1;
					while ($i <= 10) {
						if ($_POST['show' . $i]) {
							if ($_POST['option' . $i] == 'new') {
								$sql = "INSERT INTO `fcms_poll_options`(`poll_id`, `option`, `votes`) VALUES ($latest_poll_id, '" . addslashes($_POST['show' . $i]) . "', 0)";
								mysql_query($sql) or displaySQLError('New Option Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
							} else {
								$sql = "UPDATE `fcms_poll_options` SET `option` = '" . addslashes($_POST['show' . $i]) . "' WHERE `id` = " . $_POST['option' . $i];
								mysql_query($sql) or displaySQLError('Option Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
							}
						} elseif ($_POST['option' . $i] != 'new') {
							$sql = "DELETE FROM `fcms_poll_options` WHERE `id` = " . $_POST['option' . $i];
							mysql_query($sql) or displaySQLError('Delete Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
						}
						$i++;
					}
					echo "<meta http-equiv='refresh' content='0;URL=polls.php'>";
				}
				if (isset($_POST['addsubmit'])) {
					$show = false;
					$i = 1;
					$sql = "INSERT INTO `fcms_polls`(`question`, `started`) VALUES ('" . addslashes($_POST['question']) . "', NOW())";
					mysql_query($sql) or displaySQLError('New Poll Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
					$poll_id = mysql_insert_id();
					while ($i <= 10) {
						if ($_POST['option' . $i]) {
							$sql = "INSERT INTO `fcms_poll_options`(`poll_id`, `option`, `votes`) VALUES ($poll_id, '" . addslashes($_POST['option' . $i]) . "', 0)";
							mysql_query($sql) or displaySQLError('New Option Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
						}
						$i++;
					}
					$sql = "TRUNCATE TABLE fcms_poll_users";
					mysql_query($sql) or displaySQLError('Truncate Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
					echo "<meta http-equiv='refresh' content='0;URL=polls.php'>";
				}
				if (isset($_POST['delsubmit'])) {
					$show = false;
					$poll_id = $_POST['pollid'];
					$sql = "DELETE FROM fcms_poll_options WHERE poll_id = $poll_id";
					mysql_query($sql) or displaySQLError('Delete Option Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
					$sql = "DELETE FROM fcms_polls WHERE id = $poll_id";
					mysql_query($sql) or displaySQLError('Delete Poll Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
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
					echo "<b>".$LANG['past_polls']."</b><br/>";
					$sql = "SELECT * FROM fcms_polls ORDER BY `started` DESC";
					$result = mysql_query($sql) or displaySQLError('Poll Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
					if (mysql_num_rows($result) > 0) {
						while($r = mysql_fetch_array($result)) {
							echo "<a href=\"?page=admin_polls&amp;editpoll=" . $r['id'] . "\">" . $r['question'] . "</a> - " . $r['started'];
							echo " <form class=\"frm_line\" action=\"polls.php\" method=\"post\"><div><input type=\"submit\" name=\"delsubmit\" class=\"delbtn\" value=\" \" onclick=\"javascript:return confirm('".$LANG['js_delete_poll']."'); \"/><input type=\"hidden\" name=\"pollid\" value=\"" . $r['id'] . "\"/></div></form><br/>";
						}
					} else {
						echo "<i>" . $LANG['no_prev_polls'] . "</i>";
					}
				}
			}
			?><p>&nbsp;</p>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<?php displayFooter("fix"); ?>
</body>
</html>
