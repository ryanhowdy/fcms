<?php
session_start();
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');

// Check that the user is logged in
isLoggedIn('admin/');

header("Cache-control: private");
include_once('../inc/admin_class.php');
$admin = new Admin($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL['pagetitle'] = _('Administration: Awards');
$TMPL['path'] = "../";
$TMPL['admin_path'] = "";

// Show Header
include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'header.php');

echo '
        <div class="centercontent">';

if (checkAccess($_SESSION['login_id']) > 2) {
    echo '
            <p class="error-alert">
                <b>'._('You do not have access to view this page.').'</b><br/>
                '._('This page requires an access level 2 (Helper) or better.').' 
                <a href="../contact.php">'._('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
} else {
    if (isset($_POST['submit'])) {
        $worked = $admin->getTopThreadStarter();
        $admin->getMostViewedPhoto();
        $admin->getTopPosters();
        $admin->getTopPhotoSubmitters();
        $admin->getMostSmileys();
        if ($worked) { 
            echo '
            <p class="ok-alert">'._('The Latest Awards have been calculated successfully.').'</p>';
        } else {
            echo '
            <p class="info-alert">'._('Awards could not be calculated, please try again after using the site for at least a month.').'</p>';
        }
    } else {
        echo '
            <form method="post" action="awards.php">
                <div class="center">
                    <input type="submit" name="submit" value="'._('Get Latest Awards').'"/>
                </div>
            </form>';
    }
}

echo '
        </div><!-- .centercontent -->';

// Show Footer
include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'footer.php');