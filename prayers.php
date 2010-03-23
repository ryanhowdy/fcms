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
include_once('inc/prayers_class.php');
$prayers = new Prayers($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['link_prayers'];
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
		<div id="prayers" class="centercontent">
			<?php
			$show = true;
			if (isset($_POST['submitadd'])) {
				$for = addslashes($_POST['for']);
				$desc = addslashes($_POST['desc']);
				$sql = "INSERT INTO `fcms_prayers`(`for`, `desc`, `user`, `date`) "
                     . "VALUES('$for', '$desc', " . $_SESSION['login_id'] . ", NOW())";
				mysql_query($sql) or displaySQLError(
                    'New Prayer Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
				echo "<p class=\"ok-alert\" id=\"add\">".$LANG['ok_pray_add']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ ";
                echo "var t=setTimeout(\"$('add').toggle()\",3000); }</script>";
                // Email members
                $sql = "SELECT u.`email`, s.`user` "
                     . "FROM `fcms_user_settings` AS s, `fcms_users` AS u "
                     . "WHERE `email_updates` = '1'"
                     . "AND u.`id` = s.`user`";
                $result = mysql_query($sql) or displaySQLError(
                    'Email Updates Error', __FILE__ . ' [' . __LINE__ . ']', 
                    $sql, mysql_error()
                );
                if (mysql_num_rows($result) > 0) {
                    while ($r = mysql_fetch_array($result)) {
                        $name = getUserDisplayName($_SESSION['login_id']);
                        $to = getUserDisplayName($r['user']);
                        $subject = "$name " . $LANG['added_concern'] . " $for.";
                        $email = $r['email'];
                        $url = getDomainAndDir();
                        $msg = $LANG['dear'] . " $to,

$name " . $LANG['added_concern'] . " $for.

{$url}prayers.php

----
" . $LANG['opt_out_updates'] . "

{$url}settings.php

";
                        mail($email, $subject, $msg, $email_headers);
                    }
                }
			} 
			if (isset($_POST['submitedit'])) {
				$for = addslashes($_POST['for']);
				$desc = addslashes($_POST['desc']);
				$sql = "UPDATE `fcms_prayers` SET `for` = '$for', `desc` = '$desc' WHERE `id` = " . $_POST['id'];
				mysql_query($sql) or displaySQLError('Edit Prayer Error', 'prayers.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"edit\">".$LANG['ok_pray_edit']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('edit').toggle()\",3000); }</script>";
			}
			if (isset($_POST['delprayer'])) {
				$sql = "DELETE FROM `fcms_prayers` WHERE `id` = " . $_POST['id'];
				mysql_query($sql) or displaySQLError('Delete Prayer Error', 'prayers.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"del\">".$LANG['ok_pray_del']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('del').toggle()\",2000); }</script>";
			}
			if (isset($_GET['addconcern']) && checkAccess($_SESSION['login_id']) <= 5) {
				$show = false;
				$prayers->displayForm('add');
			}
			if (isset($_POST['editprayer'])) {
				$show = false;
				$prayers->displayForm('edit', $_POST['id'], $_POST['for'], $_POST['desc']);
			}
			if ($show) {
				if (checkAccess($_SESSION['login_id']) <= 5) {
					echo "<div id=\"sections_menu\" class=\"clearfix\"><ul><li><a class=\"add\" href=\"?addconcern=yes\">";
                    echo $LANG['add_prayer']."</a></li></ul></div>\n";
				}
				$page = 1;
				if (isset($_GET['page'])) { $page = $_GET['page']; }
				$prayers->showPrayers($page);
			} ?>
		</div><!-- #prayers .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>
