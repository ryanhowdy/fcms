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

// Check that the user is logged in
isLoggedIn();

header("Cache-control: private");
include 'inc/settings_class.php';
$settings = new Settings($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
// Setup the Template variables;
$TMPL['pagetitle'] = _('Settings');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = <<<HTML
<link rel="stylesheet" type="text/css" href="themes/datechooser.css"/>
<script type="text/javascript" src="inc/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[ 
window.onload = WindowLoad;
function WindowLoad() {
    var objDatePicker = new DateChooser();
    objDatePicker.setUpdateField({'sday':'j', 'smonth':'n', 'syear':'Y'});
    objDatePicker.setIcon('themes/default/images/datepicker.jpg', 'syear'); 
    return true;
}
//]]>
</script>
HTML;

// Show Header
include_once(getTheme($_SESSION['login_id']) . 'header.php');

echo '
        <div id="settings" class="centercontent">

            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="?view=settings">'._('Settings').'</a></li>
                    <li><a href="?view=board">'._('Message Board').'</a></li>
                    <li><a href="?view=personal">'._('Personal Info').'</a></li>
                    <li><a href="?view=password">'._('Password').'</a></li>
                </ul>
            </div>

            <div id="maincolumn">';

$emailstart = $settings->cur_user_email;
if (isset($_POST['submit'])) {

    //-----------------------------------------------
    // Update `fcms_users`
    //-----------------------------------------------
    $sql = "UPDATE `fcms_users` SET ";
    // Settings
    if (isset($_POST['settings'])) {
        if ($_FILES['avatar']['error'] < 1) {
            $upfile = uploadImages(
                $_FILES['avatar']['type'], $_FILES['avatar']['name'], 
                $_FILES['avatar']['tmp_name'], "gallery/avatar/", 80, 80, 'yes'
            );
            $sql .= "`avatar` = '$upfile', ";
            if ($_POST['avatar_orig'] != 'no_avatar.jpg') {
                unlink("gallery/avatar/" . $_POST['avatar_orig']);
            }
        }
    }
    // Personal Info
    if (isset($_POST['personal'])) {
        if ($_POST['fname']) { $sql .= "fname = '" .addslashes($_POST['fname']) . "', "; }
        if ($_POST['lname']) { $sql .= "lname = '".addslashes($_POST['lname'])."', "; }
        if ($_POST['email']) { 
            if ($_POST['email'] != $emailstart) {
                $sql2 = "SELECT `email` FROM `fcms_users` "
                      . "WHERE email='" . $_POST['email'] . "'";
                $result = mysql_query($sql2) or displaySQLError(
                    'Email Check Error', ___FILE___ . ' [' . __LINE__ . ']', 
                    $sql, mysql_error()
                );
                $email_check = mysql_num_rows($result);
                if ($email_check > 0) { 
                    echo '
            <p class="error-alert">
                '.sprintf(_('The email address %s is already in use.  Please choose a different email.'), $_POST['email']).'
            </p>';
                    $settings->displayForm();
                    exit();
                }
            $sql .= "email = '".addslashes($_POST['email'])."', ";
            }
        }
        $birthday = $_POST['syear']."-".str_pad($_POST['smonth'], 2, "0", STR_PAD_LEFT)."-".str_pad($_POST['sday'], 2, "0", STR_PAD_LEFT);
        $sql .= "birthday = '$birthday', ";
    }
    // Password
    if (isset($_POST['password'])) {
        $orig_pass = $_SESSION['login_pw'];
        if (!empty($_POST['pass'])) {
            $sql .= "password = '" . md5($_POST['pass']) . "', ";
            $_SESSION['login_pw'] = md5($_POST['pass']);
        }
    }
    // Only update user if there's somethign to update
    if (!empty($_POST['pass']) || isset($_POST['syear']) || isset($upfile)) {
        $sql = substr($sql, 0, -2); // remove the extra comma space at the end
        $sql .= "WHERE id = " . $_SESSION['login_id'];
        mysql_query($sql) or displaySQLError(
            'Update User Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    }

    //-----------------------------------------------
    // Update `fcms_user_settings`
    //-----------------------------------------------
    $sql = "UPDATE `fcms_user_settings` SET ";
    // Settings
    if (isset($_POST['settings'])) {
        $sql .= "theme = '" . $_POST['theme'] . "', ";
        if ($_POST['displayname']) { $sql .= "displayname = '" . $_POST['displayname'] . "', "; }
        if ($_POST['frontpage']) { $sql .= "frontpage = '" . $_POST['frontpage'] . "', "; }
        if ($_POST['email_updates']) {
            if ($_POST['email_updates'] == 'yes') {
                $sql .= "email_updates = '1', ";
            } else {
                $sql .= "email_updates = '0', ";
            }
        }
        if ($_POST['advanced_upload']) {
            if ($_POST['advanced_upload'] == 'yes') {
                $sql .= "advanced_upload = '1', ";
            } else {
                $sql .= "advanced_upload = '0', ";
            }
        }
        if ($_POST['language']) {
            $sql .= "language = '" . $_POST['language'] . "', ";
            $_SESSION['language'] = $_POST['language'];
            T_setlocale(LC_MESSAGES, $_SESSION['language']);
        }
        if ($_POST['timezone']) { $sql .= "timezone = '" . $_POST['timezone'] . "', "; }
        if ($_POST['dst']) {
            if ($_POST['dst'] == 'on') {
                $sql .= "dst = '1', ";
            } else {
                $sql .= "dst = '0', ";
            }
        }
    }
    // Message Board
    if (isset($_POST['board'])) {
        if ($_POST['boardsort']) { $sql .= "boardsort = '" . $_POST['boardsort'] . "', "; }
        if ($_POST['showavatar']) {
            if ($_POST['showavatar'] == 'yes') {
                $sql .= "showavatar = '1', ";
            } else {
                $sql .= "showavatar = '0', ";
            }
        }
    }
    // Only update user if there's somethign to update
    if (isset($_POST['settings']) || isset($_POST['board'])) {
        $sql = substr($sql, 0, -2); // remove the extra comma space at the end
        $sql .= "WHERE `user` = " . $_SESSION['login_id'];
        mysql_query($sql) or displaySQLError(
            'Update Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    }
    if (isset($orig_pass)) {
        echo '
            <div class="ok-alert">
                <p>'._('Your Settings were Updated Successfully.').'</p>
                <p><a href="logout.php">'._('You must now logout and login again to complete your changes.').'</a></p>
            </div>
            <meta http-equiv=\'refresh\' content=\'5;URL=logout.php\'>';
    } else {
        echo '
            <p class="ok-alert">'._('Changes Updated Successfully').'</p>
            <p><a href="settings.php">'._('Continue').'</a></p>';
    }
} else {
    $option = isset($_GET['view']) ? $_GET['view'] : 'settings';
    $settings->displayForm($option);
}

echo '
            </div>
        </div><!-- #settings .centercontent -->';

// Show Footer
include_once(getTheme($_SESSION['login_id']) . 'footer.php');
