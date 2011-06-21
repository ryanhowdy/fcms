<?php
session_start();

define('URL_PREFIX', '');

include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/database_class.php');
include_once('inc/members_class.php');

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn();
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$member = new Members($currentUserId);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Members'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript">Event.observe(window, "load", function() { initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\'); });</script>';

$show_all = true;

// Show Header
include_once(getTheme($currentUserId) . 'header.php');

echo '
        <div id="members" class="centercontent">
            <div id="leftcolumn">
                <h3>' . T_('Order Members By:') . '</h3>
                <ul class="menu">
                    <li><a href="?order=alphabetical">'.T_('Alphabetical').'</a></li>
                    <li><a href="?order=age">'.T_('Age').'</a></li>
                    <li><a href="?order=participation">'.T_('Participation').'</a></li>
                    <li><a href="?order=activity">'.T_('Last Seen').'</a></li>
                    <li><a href="?order=joined">'.T_('Joined').'</a></li>
                </ul>
            </div>
            <div id="maincolumn">';

$order = isset($_GET['order']) ? $_GET['order'] : 'alphabetical';
$member->displayAll($order);

echo '
            </div><!-- #maincolumn -->
        </div><!-- #members  -->';

// Show Footer
include_once(getTheme($currentUserId) . 'footer.php');
