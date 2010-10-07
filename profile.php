<?php
session_start();

include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/profile_class.php');

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn();
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$profile  = new Profile($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$awards   = new Awards($currentUserId, $database);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Profile'),
    'path'          => "",
    'admin_path'    => "admin/",
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

$show = true;

// Show Header
include_once(getTheme($currentUserId) . 'header.php');

echo '
        <div id="profile" class="centercontent">';

$memberid = isset($_GET['member']) ? cleanInput($_GET['member'], 'int') : $currentUserId;

// Show awards
if (isset($_GET['award'])) {
    $show = false;
    $id = cleanInput($_GET['award'], 'int');
    $awards->displayAward($memberid, $id);
}

// Show profile
if ($show) {
    $profile->displayProfile($memberid);
}

echo '
        </div><!-- #profile .centercontent -->';

// Show Footer
include_once(getTheme($currentUserId) . 'footer.php');
