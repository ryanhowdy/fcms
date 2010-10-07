<?php
session_start();

include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');
include_once('../inc/admin_class.php');
include_once('../inc/awards_class.php');

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn('admin/');
$currentUserId = (int)escape_string($_SESSION['login_id']);

$admin      = new Admin($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$database   = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$awards     = new Awards($currentUserId, $database);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Awards'),
    'path'          => "../",
    'admin_path'    => "",
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

// Show Header
include_once(getTheme($currentUserId, $TMPL['path']) . 'header.php');

echo '
        <div class="centercontent">';

// Check permissions
if (checkAccess($currentUserId) > 2) {
    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 2 (Helper) or better.').' 
                <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
    displayFooter();
    echo '
        </div><!-- .centercontent -->';
    include_once(getTheme($currentUserId, $TMPL['path']) . 'footer.php');
    die();
}

// Calculate awards
if (isset($_POST['submit'])) {
    $awards->calculateMonthlyAwards();
    if ($awards->calculateAchievementAwards()) {
        echo '
            <p class="ok-alert">'.T_('The Latest Awards have been calculated successfully.').'</p>';
    }

// Show button
} else {
    echo '
            <form method="post" action="awards.php">
                <div class="center">
                    <input type="submit" name="submit" value="'.T_('Get Latest Awards').'"/>
                </div>
            </form>';
}

echo '
        </div><!-- .centercontent -->';

// Show Footer
include_once(getTheme($currentUserId, $TMPL['path']) . 'footer.php');
