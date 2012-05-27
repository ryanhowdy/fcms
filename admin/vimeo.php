<?php
/**
 * Facebook
 * 
 * PHP version 5
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
define('GALLERY_PREFIX', '../gallery/');

require '../fcms.php';

load('socialmedia', 'vimeo');

init('admin/');

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getAdminNavLinks(),
    'pagetitle'     => T_('Vimeo'),
    'path'          => URL_PREFIX,
    'displayname'   => $fcmsUser->displayName,
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
    global $fcmsUser, $TMPL;

    $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
});
//]]>
</script>';

    include_once getTheme($fcmsUser->id).'header.php';

    echo '
        <div id="facebook" class="centercontent">';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    global $fcmsUser, $TMPL;

    echo '
        </div><!--/centercontent-->';

    include_once getTheme($fcmsUser->id).'footer.php';
}

/**
 * displayForm 
 * 
 * Displays the form for configuring a facebook app.
 * 
 * @return void
 */
function displayForm ($displayMessage = '')
{
    global $fcmsUser;

    displayHeader();

    if (!empty($displayMessage))
    {
        displayOkMessage();
    }

    $r = getVimeoConfigData();

    $key    = isset($r['vimeo_key'])    ? cleanInput($r['vimeo_key'])    : '';
    $secret = isset($r['vimeo_secret']) ? cleanInput($r['vimeo_secret']) : '';

    echo '
        <div class="info-alert">
            <h1>'.T_('Vimeo Integration').'</h1>
            <p>
                '.T_('In order to integrate Family Connections with Vimeo, you must create a new Vimeo app, and configure that app in Family Connections.').'
            </p>
        </div>';

    if (empty($key) || empty($secret))
    {
        $callbackUrl = getDomainAndDir();
        $callbackUrl = substr($callbackUrl, 0, -6).'vimeo.php';

        echo '
        <div style="margin: 0 100px;">
            <h2>'.T_('Step 1').'</h2>
            <p>
                <a href="http://http://vimeo.com/api/applications">'.T_('Create Vimeo Application').'</a><br/>
                <small>'.sprintf(T_('Callback url should be: %s.'), $callbackUrl).'</small>
            </p>
            <h2>'.T_('Step 2').'</h2>
            <p>
                '.T_('Request Upload access for your Vimeo application.').'<br/>
                <small>'.T_('Be sure and fill out the Purpose field throughly, or your request might get denied').'</small>
            </p>
            <h2>'.T_('Step 3').'</h2>
            <p>
                '.T_('Fill out the form below with the Key and Secret from your newly created Vimeo Application.').'
            </p>
        </div>';
    }

    echo '
        <form method="post" action="vimeo.php">
            <fieldset>
                <legend><span>'.T_('Vimeo Application').'</span></legend>
                <div class="field-row">
                    <div class="field-label"><label for="id"><b>'.T_('Consumer Key').'</b></label></div>
                    <div class="field-widget"><input class="frm_text" type="text" name="key" id="key" size="50" value="'.$key.'"/></div>
                </div>
                <div class="field-row">
                    <div class="field-label"><label for="secret"><b>'.T_('Consumer Secret').'</b></label></div>
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
    $key    = isset($_POST['key'])    ? cleanInput($_POST['key'])    : '';
    $secret = isset($_POST['secret']) ? cleanInput($_POST['secret']) : '';

    $sql = "UPDATE `fcms_config` SET 
                `vimeo_key` = '$key',
                `vimeo_secret` = '$secret'";
    
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    displayForm(1);
}
