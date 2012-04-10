<?php
/**
 * CHANGE_ME - a template for creating a new page
 * 
 * PHP versions 4 and 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '');

require 'fcms.php';

load('datetime');

// Check that the user is logged in
isLoggedIn();

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('CHANGE_ME'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

control();
exit();


/**
 * control 
 * 
 * The controlling structure for this script.
 * 
 * @return void
 */
function control ()
{
    global $book;

    if (isset($_GET['add']))
    {
        displayAddForm();
    }
    elseif (isset($_POST['addsubmit']))
    {
        displayAddSubmit();
    }
    else
    {
        displayMain();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    global $currentUserId, $TMPL;

    $TMPL['javascript'] = '
<script type="text/javascript" src="ui/js/tablesort.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    deleteConfirmationLink("del_address", "'.T_('Are you sure you want to DELETE this address?').'");
});
//]]>
</script>';

    include_once getTheme($currentUserId).'header.php';

    echo '
        <div id="addressbook" class="centercontent">';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    global $currentUserId, $TMPL;

    echo '
        </div><!-- /centercontent -->';

    include_once getTheme($currentUserId).'footer.php';
}
