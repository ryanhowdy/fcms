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

// Check that the user is logged in
isLoggedIn('admin/');

header("Cache-control: private");
include_once('../inc/admin_class.php');
$admin = new Admin($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL['pagetitle'] = _('Administration: Message Board');
$TMPL['path'] = "../";
$TMPL['admin_path'] = "";

// Show Header
include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'header.php');

echo '
        <div id="messageboard" class="centercontent">';

if (checkAccess($_SESSION['login_id']) > 2) {
    echo '
            <p class="error-alert">
                <b>'._('You do not have access to view this page.').'</b><br/>
                '._('This page requires an access level 2 (Helper) or better.').' 
                <a href="../contact.php">'._('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
} else {
    if (isset($_GET['del'])) {
        $sql = "DELETE FROM `fcms_board_threads` WHERE `id`=" . $_GET['del'];
        mysql_query($sql) or displaySQLError('Delete Thread Error', 'admin/board.php [' . __LINE__ . ']', $sql, mysql_error());
        echo "<meta http-equiv='refresh' content='0;URL=board.php'>";
    } elseif (isset($_POST['edit_submit'])) {
        if (isset($_POST['sticky'])) { $subject = "#ANOUNCE#" . $_POST['subject']; } else { $subject = $_POST['subject']; }
        $sql = "UPDATE `fcms_board_threads` SET `subject` = '".addslashes($subject)."' WHERE `id` = " . $_POST['threadid'];
        mysql_query($sql) or displaySQLError('Edit Thread Error', 'admin/board.php [' . __LINE__ . ']', $sql, mysql_error());
    }
    if (isset($_GET['edit'])) {
        $admin->displayEditThread($_GET['edit']);
    } else {
        $page = 1;
        if (isset($_GET['page'])) { $page = $_GET['page']; }
        $admin->showThreads('announcement');
        $admin->showThreads('thread', $page);
    }
}

echo '
        </div><!-- .centercontent -->';

// Show Footer
include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'footer.php');