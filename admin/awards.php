<?php
/**
 * Awards
 * 
 * PHP versions 4 and 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2010 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '../');

require URL_PREFIX.'fcms.php';

load('admin', 'awards');

init('admin/');

$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$admin         = new Admin($currentUserId);
$awards        = new Awards($currentUserId);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Awards'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript">Event.observe(window, "load", function() { initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\'); });</script>';

// Show Header
require_once getTheme($currentUserId, $TMPL['path']).'header.php';

echo '
        <div class="centercontent">';

// Check permissions
if (checkAccess($currentUserId) > 2)
{
    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 2 (Helper) or better.').' 
                <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
    echo '
        </div><!-- .centercontent -->';
    include_once getTheme($currentUserId, $TMPL['path']).'footer.php';
    die();
}

// Calculate awards
if (isset($_POST['submit']))
{
    $awards->calculateMonthlyAwards();
    if ($awards->calculateAchievementAwards())
    {
        echo '
            <p class="ok-alert">'.T_('The Latest Awards have been calculated successfully.').'</p>';
    }
}
// Show button
else
{
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
require_once getTheme($currentUserId, $TMPL['path']).'footer.php';
