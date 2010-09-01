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

// Check that the user is logged in
isLoggedIn();
$current_user_id = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");
include_once('inc/privatemsg_class.php');
$pm = new PrivateMessage($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL['pagetitle'] = T_('Private Messages');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    if (!$$(\'.pm_footer input[type="submit"]\')) { return; }
    $$(\'.pm_footer input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this?').'\'); };
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

// Show Header
include_once(getTheme($current_user_id) . 'header.php');

echo '
        <div id="privatemsg" class="centercontent">

            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a href="profile.php">'.T_('Profiles').'</a></li>
                    <li><a href="privatemsg.php">'.T_('Private Messages').'</a></li>
                    <li><a href="profile.php?awards=yes">'.T_('Awards').'</a></li>
                </ul>
            </div>
            <div id="actions_menu" class="clearfix">
                <ul><li><a href="?compose=new">'.T_('New Message').'</a></li></ul>
            </div>

            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="privatemsg.php">'.T_('Inbox').'</a></li>
                    <li><a href="privatemsg.php?folder=sent">'.T_('Sent').'</a></li>
                </ul>
            </div>

            <div id="maincolumn">';
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
    $title = escape_string($_POST['title']);
    $msg = escape_string($_POST['post']);
    if (strlen($title) > 0 && strlen($msg) > 0) {
        $sql = "INSERT INTO `fcms_privatemsg` 
                    (`to`, `from`, `date`, `title`, `msg`) 
                VALUES 
                    (" . escape_string($_POST['to']) . ", $current_user_id, NOW(), '$title', '$msg')";
        mysql_query($sql) or displaySQLError(
            'Send PM Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        // Email the PM to the user
        $sql = "SELECT * FROM `fcms_users` WHERE `id` = " . escape_string($_POST['to']);
        $result = mysql_query($sql) or displaySQLError(
            'Get User Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);
        $from = getUserDisplayName($current_user_id);
        $reply = getUserEmail($current_user_id);
        $to = getUserDisplayName($_POST['to']);
        $sitename = getSiteName();
        $subject = sprintf(T_('A new Private Message at %s'), $sitename);
        $email = $r['email'];
        $url = getDomainAndDir();
        $email_msg = T_('Dear').' '.$to.',

'.sprintf(T_('%s has sent you a new Private Message at %s'), $from, $sitename).'

'.T_('The message has been attached below.').'

'.sprintf(T_('To respond to this message either visit %s or respond to this email.'), $url.'privatemsg.php').'

----

'.T_('From').': '.$from.'
'.T_('Message Title').': '.$title.'

'.$msg.'

';
        $email_headers = 'From: ' . getSiteName() . ' <' . getContactEmail() . '>' . "\r\n" . 
            'Reply-To: ' . $reply . "\r\n" . 
            'Content-Type: text/plain; charset=UTF-8;' . "\r\n" . 
            'MIME-Version: 1.0' . "\r\n" . 
            'X-Mailer: PHP/' . phpversion();
        mail($email, $subject, $email_msg, $email_headers);
        echo '
            <p class="ok-alert" id="sent">'.sprintf(T_('A Private Message has been sent to %s'), $to).'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'sent\').toggle()",3000); }
            </script>';
    }

// Delete confirmation
} else if (isset($_POST['delete']) && !isset($_POST['confirmed'])) {
    $show = false;
    echo '
                <div class="info-alert clearfix">
                    <form action="privatemsg.php" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>';
    foreach ($_POST['del'] as $id) {
        echo '
                            <input type="hidden" name="del[]" value="'.$id.'"/>';
    }
    echo '
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="privatemsg.php">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

// Delete PM
} elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
    if (isset($_POST['del'])) {
        $i = 0;
        foreach ($_POST['del'] as $id) {
            $sql = "DELETE FROM `fcms_privatemsg` WHERE `id` = ".escape_string($id);
            mysql_query($sql) or displaySQLError('Delete PM Error', 'privatemsg.php [' . __LINE__ . ']', $sql, mysql_error());
            $i++;
        }
        echo '
            <p class="ok-alert" id="del">'.sprintf(_ngettext('%d Private Message Deleted Successfully', '%d Private Messages Deleted Successfully', $i), $i).'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'del\').toggle()",3000); }
            </script>';
    }
} elseif (isset($_GET['pm'])) {
    $show = false;
    $pm->displayPM($_GET['pm']);
} elseif (isset($_GET['sent'])) {
    $show = false;
    $pm->displaySentPM($_GET['sent']);
}
if ($show) {
    if (isset($_GET['folder'])) {
        $pm->displaySentFolder();
    } else {
        $pm->displayInbox();
    }
}

echo '
            </div>
        </div><!-- #profile .centercontent -->';

// Show Footer
include_once(getTheme($current_user_id) . 'footer.php');
