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

// Check that the user is logged in
isLoggedIn();

header("Cache-control: private");
include_once('inc/prayers_class.php');
$prayers = new Prayers($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['link_prayers'];
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    if (!$$(\'.delform input[type="submit"]\')) { return; }
    $$(\'.delform input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''.$LANG['js_del_prayer'].'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    });
    return true;
});
//]]>
</script>';

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

            // Add prayer concern
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

            // Edit prayer concern
			if (isset($_POST['submitedit'])) {
				$for = addslashes($_POST['for']);
				$desc = addslashes($_POST['desc']);
				$sql = "UPDATE `fcms_prayers` SET `for` = '$for', `desc` = '$desc' WHERE `id` = " . $_POST['id'];
				mysql_query($sql) or displaySQLError('Edit Prayer Error', 'prayers.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"edit\">".$LANG['ok_pray_edit']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('edit').toggle()\",3000); }</script>";
			}

            // Delete confirmation
            if (isset($_POST['delprayer']) && !isset($_POST['confirmed'])) {
                $show = false;
                echo '
            <div class="info-alert clearfix">
                <form action="prayers.php" method="post">
                    <h2>'.$LANG['js_del_prayer'].'</h2>
                    <p><b><i>'.$LANG['cannot_be_undone'].'</i></b></p>
                    <div>
                        <input type="hidden" name="id" value="'.$_POST['id'].'"/>
                        <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.$LANG['yes'].'"/>
                        <a style="float:right;" href="prayers.php">'.$LANG['cancel'].'</a>
                    </div>
                </form>
            </div>';

            // Delete prayer concern
            } elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
				$sql = "DELETE FROM `fcms_prayers` WHERE `id` = " . $_POST['id'];
				mysql_query($sql) or displaySQLError('Delete Prayer Error', 'prayers.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"del\">".$LANG['ok_pray_del']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('del').toggle()\",2000); }</script>";
			}

            // Add Form
			if (isset($_GET['addconcern']) && checkAccess($_SESSION['login_id']) <= 5) {
				$show = false;
				$prayers->displayForm('add');
			}

            // Edit Form
			if (isset($_POST['editprayer'])) {
				$show = false;
				$prayers->displayForm('edit', $_POST['id'], $_POST['for'], $_POST['desc']);
			}

            // Show Prayers
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
