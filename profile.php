<?php
session_start();
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');

// Check that the user is logged in
isLoggedIn();
$current_user_id = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");
include_once('inc/profile_class.php');
$profile = new Profile($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL['pagetitle'] = T_('Profiles');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";

$show_all = true;

// Show Header
include_once(getTheme($current_user_id) . 'header.php');

echo '
        <div id="profile" class="centercontent">
            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a href="profile.php">'.T_('Profiles').'</a></li>
                    <li><a href="privatemsg.php">'.T_('Private Messages').'</a></li>
                    <li><a href="profile.php?awards=yes">'.T_('Awards').'</a></li>
                </ul>
            </div>';
if (isset($_GET['member'])) {
    if (ctype_digit($_GET['member'])) {
        $show_all = false;
        $profile->displayProfile($_GET['member']);
    }
}
if (isset($_GET['awards'])) {
    $show_all = false;
    $profile->displayAwards();
}
if ($show_all) {
    $profile->displayAll();
}

echo '
        </div><!-- #profile .centercontent -->';

// Show Footer
include_once(getTheme($current_user_id) . 'footer.php');
