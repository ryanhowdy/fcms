<?php
session_start();

include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include 'inc/settings_class.php';

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn();
$currentUserId = (int)escape_string($_SESSION['login_id']);

$settings = new Settings($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Settings'),
    'path'          => "",
    'admin_path'    => "admin/",
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = <<<HTML
<link rel="stylesheet" type="text/css" href="themes/datechooser.css"/>
<script type="text/javascript" src="inc/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[ 
window.onload = WindowLoad;
function WindowLoad() {
    initGravatar();
    var objDatePicker = new DateChooser();
    objDatePicker.setUpdateField({'sday':'j', 'smonth':'n', 'syear':'Y'});
    objDatePicker.setIcon('themes/default/images/datepicker.jpg', 'syear'); 
    return true;
}
//]]>
</script>
HTML;

// Show Header
include_once(getTheme($currentUserId) . 'header.php');

echo '
        <div id="settings" class="centercontent">

            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="?view=settings">'.T_('Settings').'</a></li>
                    <li><a href="?view=board">'.T_('Message Board').'</a></li>
                    <li><a href="?view=personal">'.T_('Personal Info').'</a></li>
                    <li><a href="?view=password">'.T_('Password').'</a></li>
                </ul>
            </div>

            <div id="maincolumn">';

$emailstart = $settings->currentUserEmail;

if (isset($_POST['submit'])) {

    //-----------------------------------------------
    // Update `fcms_users`
    //-----------------------------------------------
    $sql = "UPDATE `fcms_users` SET ";
    // Settings
    if (isset($_POST['settings'])) {
        // Avatar uploads
        if ($_POST['avatar_type'] == 'fcms') {
            if ($_FILES['avatar']['error'] < 1) {
                $upfile = uploadImages(
                    $_FILES['avatar']['type'], $_FILES['avatar']['name'], 
                    $_FILES['avatar']['tmp_name'], "gallery/avatar/", 80, 80, true, false, true
                );
                $sql .= "`avatar` = '$upfile', ";
                if ($_POST['avatar_orig'] != 'no_avatar.jpg' && $_POST['avatar_orig'] != 'gravatar') {
                    unlink("gallery/avatar/" . basename($_POST['avatar_orig']));
                }
            } else {
                $sql .= "`avatar` = `avatar`, ";
            }
        // Avatar Gravatar
        } else if ($_POST['avatar_type'] == 'gravatar') {
            $sql .= "`avatar` = 'gravatar', `gravatar` = '".cleanInput($_POST['gravatar_email'])."', ";
            if ($_POST['avatar_orig'] != 'no_avatar.jpg' && $_POST['avatar_orig'] != 'gravatar') {
                unlink("gallery/avatar/" . basename($_POST['avatar_orig']));
            }
        // Avatar default
        } else {
            $sql .= "`avatar` = 'no_avatar.jpg', ";
        }
    }
    // Personal Info
    if (isset($_POST['personal'])) {
        if ($_POST['fname']) {
            $sql .= "`fname` = '" . cleanInput($_POST['fname']) . "', ";
        }
        if ($_POST['lname']) {
            $sql .= "`lname` = '" . cleanInput($_POST['lname']) . "', ";
        }
        if ($_POST['email']) { 
            if ($_POST['email'] != $emailstart) {
                $sql2 = "SELECT `email` FROM `fcms_users` 
                         WHERE email='" . cleanInput($_POST['email']) . "'";
                $result = mysql_query($sql2) or displaySQLError(
                    'Email Check Error', ___FILE___ . ' [' . __LINE__ . ']', 
                    $sql, mysql_error()
                );
                $email_check = mysql_num_rows($result);
                if ($email_check > 0) { 
                    echo '
            <p class="error-alert">
                '.sprintf(T_('The email address %s is already in use.  Please choose a different email.'), $_POST['email']).'
            </p>';
                    $settings->displayForm();
                    exit();
                }
                $sql .= "`email` = '" . cleanInput($_POST['email']) . "', ";
            }
        }
        if ($_POST['sex']) {
            $sql .= "`sex` = '" . cleanInput($_POST['sex']) . "', ";
        }
        $year   = cleanInput($_POST['syear'], 'int');
        $month  = cleanInput($_POST['smonth'], 'int'); 
        $month  = str_pad($month, 2, "0", STR_PAD_LEFT);
        $day    = cleanInput($_POST['sday'], 'int');
        $day    = str_pad($day, 2, "0", STR_PAD_LEFT);
        $birthday = "$year-$month-$day";
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
    if (!empty($_POST['pass']) || isset($_POST['syear']) || isset($_POST['theme'])) {
        $sql = substr($sql, 0, -2); // remove the extra comma space at the end
        $sql .= "WHERE id = '$currentUserId'";
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
        if ($_POST['theme']) {
            $sql .= "`theme` = '" . basename($_POST['theme']) . "', ";
        }
        if ($_POST['displayname']) {
            $sql .= "`displayname` = '" . cleanInput($_POST['displayname']) . "', ";
        }
        if ($_POST['frontpage']) {
            $sql .= "`frontpage` = '" . cleanInput($_POST['frontpage']) . "', ";
        }
        if ($_POST['email_updates']) {
            if ($_POST['email_updates'] == 'yes') {
                $sql .= "`email_updates` = '1', ";
            } else {
                $sql .= "`email_updates` = '0', ";
            }
        }
        if ($_POST['advanced_upload']) {
            if ($_POST['advanced_upload'] == 'yes') {
                $sql .= "`advanced_upload` = '1', ";
            } else {
                $sql .= "`advanced_upload` = '0', ";
            }
        }
        if ($_POST['language']) {
            $lang = cleanInput($_POST['language']);
            $sql .= "`language` = '" . $lang . "', ";
            $_SESSION['language'] = $lang;
            T_setlocale(LC_MESSAGES, $lang);
        }
        if ($_POST['timezone']) {
            $sql .= "`timezone` = '" . cleanInput($_POST['timezone']) . "', ";
        }
        if ($_POST['dst']) {
            if ($_POST['dst'] == 'on') {
                $sql .= "`dst` = '1', ";
            } else {
                $sql .= "`dst` = '0', ";
            }
        }
    }
    // Message Board
    if (isset($_POST['board'])) {
        if ($_POST['boardsort']) {
            $sql .= "`boardsort` = '" . cleanInput($_POST['boardsort']) . "', ";
        }
        if ($_POST['showavatar']) {
            if ($_POST['showavatar'] == 'yes') {
                $sql .= "`showavatar` = '1', ";
            } else {
                $sql .= "`showavatar` = '0', ";
            }
        }
    }
    // Only update user if there's something to update
    if (isset($_POST['settings']) || isset($_POST['board'])) {
        $sql = substr($sql, 0, -2); // remove the extra comma space at the end
        $sql .= " WHERE `user` = '$currentUserId'";
        mysql_query($sql) or displaySQLError(
            'Update Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    }
    if (isset($orig_pass)) {
        echo '
            <div class="ok-alert">
                <p>'.T_('Your Settings were Updated Successfully.').'</p>
                <p><a href="logout.php">'.T_('You must now logout and login again to complete your changes.').'</a></p>
            </div>
            <meta http-equiv=\'refresh\' content=\'5;URL=logout.php\'>';
    } else {
        echo '
            <p class="ok-alert">'.T_('Changes Updated Successfully').'</p>
            <p><a href="settings.php">'.T_('Continue').'</a></p>';
    }
} else {
    $option = isset($_GET['view']) ? cleanInput($_GET['view']) : 'settings';
    $settings->displayForm($option);
}

echo '
            </div>
        </div><!-- #settings .centercontent -->';

// Show Footer
include_once(getTheme($currentUserId) . 'footer.php');
