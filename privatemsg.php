<?php
session_start();
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	// a bug found with an array in $_POST
	if (!isset($_POST['del'])) {
		$_POST = array_map('stripslashes', $_POST);
	}
	$_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');

// Check that the user is logged in
isLoggedIn();

header("Cache-control: private");
include_once('inc/privatemsg_class.php');
$pm = new PrivateMessage($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['link_privatemsg'];
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    if (!$$(\'.pm_footer input[type="submit"]\')) { return; }
    $$(\'.pm_footer input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''.$LANG['js_pm_delete'].'\'); };
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
		<div id="profile" class="centercontent">
			<p>
                <a href="profile.php"><?php echo $LANG['profiles']; ?></a> | 
                <a href="privatemsg.php"><?php echo $LANG['privatemsgs']; ?></a> | 
                <a href="profile.php?awards=yes"><?php echo $LANG['link_admin_awards']; ?></a>
            </p>
			<p>
                <a href="privatemsg.php"><?php echo $LANG['inbox']; ?></a> | 
                <a href="?compose=new"><?php echo $LANG['new_pmsg']; ?></a>
            </p>
			<?php
			$show = true;
			if (isset($_GET['compose'])) {
				$show = false;
				if (isset($_GET['id']) && !isset($_GET['title'])) {
					$pm->displayNewMessageForm($_GET['id']);
				} elseif (isset($_GET['id']) && isset($_GET['title'])) {
					$pm->displayNewMessageForm($_GET['id'], $_GET['title']);
				} else {
					$pm->displayNewMessageForm();
				}
			} elseif (isset($_POST['submit'])) {
                // Insert the PM into the DB
				$title = addslashes($_POST['title']);
				$msg = addslashes($_POST['post']);
                if (strlen($title) > 0 && strlen($msg) > 0) {
                    $sql = "INSERT INTO `fcms_privatemsg` 
                                (`to`, `from`, `date`, `title`, `msg`) 
                            VALUES 
                                (" . $_POST['to'] . ", " . $_SESSION['login_id'] . ", NOW(), '$title', '$msg')";
                    mysql_query($sql) or displaySQLError(
                        'Send PM Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                    // Email the PM to the user
                    $sql = "SELECT * FROM `fcms_users` WHERE `id` = " . $_POST['to'];
                    $result = mysql_query($sql) or displaySQLError(
                        'Get User Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                    $r = mysql_fetch_array($result);
                    $from = getUserDisplayName($_SESSION['login_id']);
                    $reply = getUserEmail($_SESSION['login_id']);
                    $to = getUserDisplayName($_POST['to']);
                    $subject = "A new Private Message at ". getSiteName();
                    $email = $r['email'];
                    $url = getDomainAndDir();
                    $email_msg = $LANG['dear'] . " $to,

$from has sent you a new private message at " . getSiteName() . ".

The message has been attached below.

To respond to this message either visit {$url}privatemsg.php or respond to this email.

----

From: $from
Message Title: $title

$msg
";
                    $email_headers = 'From: ' . getSiteName() . ' <' . getContactEmail() . '>' . "\r\n" . 
                        'Reply-To: ' . $reply . "\r\n" . 
                        'Content-Type: text/plain; charset=UTF-8;' . "\r\n" . 
                        'MIME-Version: 1.0' . "\r\n" . 
                        'X-Mailer: PHP/' . phpversion();
                    mail($email, $subject, $email_msg, $email_headers);
                    echo "<p class=\"ok-alert\" id=\"sent\">" . $LANG['pm_sent_to'] . " " . getUserDisplayName($_POST['to']) . "</p>";
                    echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('sent').toggle()\",3000); }</script>";
                }

            // Delete confirmation
            } else if (isset($_POST['delete']) && !isset($_POST['confirmed'])) {
                $show = false;
                echo '
                <div class="info-alert clearfix">
                    <form action="privatemsg.php" method="post">
                        <h2>'.$LANG['js_pm_delete'].'</h2>
                        <p><b><i>'.$LANG['cannot_be_undone'].'</i></b></p>
                        <div>';
                foreach ($_POST['del'] as $id) {
                    echo '
                            <input type="hidden" name="del[]" value="'.$id.'"/>';
                }
                echo '
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.$LANG['yes'].'"/>
                            <a style="float:right;" href="privatemsg.php">'.$LANG['cancel'].'</a>
                        </div>
                    </form>
                </div>';

            // Delete PM
            } elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
				if (isset($_POST['del'])) {
					foreach ($_POST['del'] as $id) {
						$sql = "DELETE FROM `fcms_privatemsg` WHERE `id` = $id";
						mysql_query($sql) or displaySQLError('Delete PM Error', 'privatemsg.php [' . __LINE__ . ']', $sql, mysql_error());
					}
					echo "<p class=\"ok-alert\" id=\"del\">" . $LANG['pm_deleted'] . "</p>";
					echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('del').toggle()\",3000); }</script>";
				}
			} elseif (isset($_GET['pm'])) {
				$show = false;
				$pm->displayPM($_GET['pm']);
			}
			if ($show) {
				$pm->displayInbox();
			} ?>
		</div><!-- #profile .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>