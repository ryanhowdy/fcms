<?php
/**
 * Facebook
 * 
 * PHP versions 4 and 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.5
 */
session_start();

define('URL_PREFIX', '../');

require '../fcms.php';

load('socialmedia');

init('admin/');

$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Facebook'),
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
    if (isset($_POST['submit']))
    {
        displayFormSubmit();
    }
    else
    {
        displayForm();
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

    include_once getTheme($currentUserId).'header.php';

    echo '
        <div id="facebook" class="centercontent clearfix">';
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

    include_once getTheme($currentUserId).'footer.php';
}

/**
 * displayForm 
 * 
 * Displays the form for configuring a facebook app.
 * 
 * @param string $displayMessage Any value will display the ok message.
 * 
 * @return void
 */
function displayForm ($displayMessage = '')
{
    global $currentUserId;

    displayHeader();

    if (!empty($displayMessage))
    {
        displayOkMessage();
    }

    $r = getFacebookConfigData();

    $id     = isset($r['fb_app_id']) ? cleanInput($r['fb_app_id']) : '';
    $secret = isset($r['fb_secret']) ? cleanInput($r['fb_secret']) : '';

    echo '
        <div class="info-alert">
            <h1>'.T_('Facebook Integration').'</h1>
            <p>
                '.T_('In order to integrate Family Connections with Facebook, you must create a new Facebook app, and configure that app in Family Connections.').'
            </p>
        </div>';

    if (empty($id) || empty($secret))
    {
        echo '
        <div style="margin: 0 100px;">
            <h2>'.T_('Step 1').'</h2>
            <p>
                <a href="http://www.facebook.com/developers/createapp.php">'.T_('Create Facebook Application').'</a><br/>
                <small>'.T_('You don\'t really have to fill out any additional information other than the application name.').'</small>
            </p>
            <h2>'.T_('Step 2').'</h2>
            <p>
                '.T_('Fill out the form below with the App Id and App Secret from your newly created Facebook Application.').'
            </p>
        </div>';
    }

    echo '
        <form method="post" action="facebook.php">
            <fieldset>
                <legend><span>'.T_('Facebook Application').'</span></legend>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="id"><b>'.T_('App ID').'</b></label></div>
                    <div class="field-widget"><input class="frm_text" type="text" name="id" id="id" size="50" value="'.$id.'"/></div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="secret"><b>'.T_('App Secret').'</b></label></div>
                    <div class="field-widget"><input class="frm_text" type="text" name="secret" id="secret" size="50" value="'.$secret.'"/></div>
                </div>
                <p><input class="sub1" type="submit" name="submit" value="'.T_('Save').'"/></p>
            </fieldset>
        </form>';

    displayFooter();
}

/**
 * displayFormSubmit 
 * 
 * @return void
 */
function displayFormSubmit ()
{
    $id     = isset($_POST['id'])     ? cleanInput($_POST['id'])     : '';
    $secret = isset($_POST['secret']) ? cleanInput($_POST['secret']) : '';

    $sql = "UPDATE `fcms_config`
            SET `value` = '$id'
            WHERE `name` = 'fb_app_id'";
    
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "UPDATE `fcms_config`
            SET `value` = '$secret'
            WHERE `name` = 'fb_secret'";
    
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayForm(1);
}
