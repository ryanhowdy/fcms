<?php
/**
 * Members
 *  
 * PHP versions 4 and 5
 *  
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2008 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '');

require 'fcms.php';

load('members', 'database');

init();

$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$member        = new Members($currentUserId);

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
require_once getTheme($currentUserId).'header.php';

echo '
        <div id="members" class="centercontent">
            <div id="leftcolumn">
                <h3>'.T_('Order Members By:').'</h3>
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
require_once getTheme($currentUserId).'footer.php';
