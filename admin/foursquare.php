<?php
/**
 * Foursquare
 * 
 * PHP versions 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2011 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.6
 */
session_start();

define('URL_PREFIX', '../');

require URL_PREFIX.'fcms.php';

load('socialmedia');

init('admin/');

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Foursquare'),
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
    checkPermissions();

    if (isset($_POST['submit']))
    {
        displayFormSubmitPage();
    }
    else
    {
        displayFormPage();
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
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
});
//]]>
</script>';

    include_once getTheme($currentUserId, $TMPL['path']).'header.php';

    echo '
        <div id="foursquare" class="centercontent clearfix">';
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
        </div><!--/centercontent-->';

    include_once getTheme($currentUserId, $TMPL['path']).'footer.php';
}

/**
 * checkPermissions 
 * 
 * @return void
 */
function checkPermissions ()
{
    global $currentUserId;

    if (checkAccess($currentUserId) > 2)
    {
        echo '
                <p class="error-alert">
                    <b>'.T_('You do not have access to view this page.').'</b><br/>
                    '.T_('This page requires an access level 2 (Helper) or better.').' 
                    <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
                </p>';
        displayFooter();
        die();
    }
}

/**
 * displayFormSubmitPage 
 * 
 * @return void
 */
function displayFormSubmitPage ()
{
    $id     = isset($_POST['id'])     ? cleanInput($_POST['id'])     : '';
    $secret = isset($_POST['secret']) ? cleanInput($_POST['secret']) : '';
    $url    = isset($_POST['url'])    ? cleanInput($_POST['url'])    : '';

    $sql = "DELETE FROM `fcms_config`
            WHERE `name` = 'fs_client_id'
            OR `name` = 'fs_client_secret'
            OR `name` = 'fs_callback_url'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "INSERT INTO `fcms_config` (`name`, `value`)
            VALUES
                ('fs_client_id', '$id'),
                ('fs_client_secret', '$secret'), 
                ('fs_callback_url', '$url')";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayFormPage(1);
}

/**
 * displayFormPage 
 * 
 * @param string $displayMessage 
 * 
 * @return void
 */
function displayFormPage ($displayMessage = '')
{
    displayHeader();

    if (!empty($displayMessage))
    {
        displayOkMessage();
    }

    $r = getFoursquareConfigData();

    $id     = isset($r['fs_client_id'])     ? cleanInput($r['fs_client_id'])     : '';
    $secret = isset($r['fs_client_secret']) ? cleanInput($r['fs_client_secret']) : '';
    $url    = isset($r['fs_callback_url'])  ? cleanInput($r['fs_callback_url'])  : '';

    if (empty($id) || empty($secret) || empty($url))
    {
        echo '
        <div style="margin: 0 100px;">
            <h2>'.T_('Step 1').'</h2>
            <p>
                <a href="https://foursquare.com/oauth/register">'.T_('Register a new foursquare OAuth Consumer.').'</a>
                <small>'.T_('Be sure to include settings.php as part of your callback url.  For example: if your site is located at http://www.my-awesome-site.com/fcms/index.php then your callback url should be http://www.my-awesome-site.com/fcms/settings.php').'</small>
            </p>
            <h2>'.T_('Step 2').'</h2>
            <p>'.T_('Fill out the form below with the information you provided in Step 1.').'</p>
        </div>';
    }

    echo '
            <form action="foursquare.php" method="post">
                <fieldset>
                    <legend><span>'.T_('Foursquare Confirguration').'</span></legend>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="id"><b>'.T_('Client ID').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="id" id="id" size="50" value="'.$id.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="secret"><b>'.T_('Client Secret').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="secret" id="secret" size="50" value="'.$secret.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="url"><b>'.T_('Callback URL').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="url" id="url" size="50" value="'.$url.'"/></div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" value="'.T_('Save').'"/></p>
                </fieldset>
            </form>';

    displayFooter();
}
