<?php
/**
 * YouTube
 * 
 * PHP version 5
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

require '../fcms.php';

load('socialmedia');

init('admin/');

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: YouTube'),
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

    include_once getTheme($currentUserId).'header.php';

    echo '
        <div id="youtube" class="centercontent clearfix">';
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
 * displayFormPage
 * 
 * Displays the form for configuring a youtube app.
 * 
 * @param string $displayMessage Any value will display the ok message.
 * 
 * @return void
 */
function displayFormPage ($displayMessage = '')
{
    global $currentUserId;

    displayHeader();

    if (!empty($displayMessage))
    {
        displayOkMessage();
    }

    $r = getYouTubeConfigData();

    $key = isset($r['youtube_key']) ? cleanInput($r['youtube_key']) : '';

    echo '
        <div class="info-alert">
            <h1>'.T_('YouTube Integration').'</h1>
            <p>
                '.T_('In order to integrate Family Connections with YouTube, you must get a Developer Key from Google, and provide that Key to Family Connections.').'
            </p>
        </div>';

    if (empty($key))
    {
        echo '
        <div style="margin: 0 100px;">
            <h2>'.T_('Step 1').'</h2>
            <p>
                <a href="http://code.google.com/apis/youtube/dashboard/">'.T_('Create Youtube Application').'</a><br/>
            </p>
            <h2>'.T_('Step 2').'</h2>
            <p>
                '.T_('Fill out the form below with the YouTube Developer Key provided by Google.').'
            </p>
        </div>';
    }

    echo '
        <form method="post" action="youtube.php">
            <fieldset>
                <legend><span>'.T_('YouTube').'</span></legend>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="key"><b>'.T_('Developer Key').'</b></label></div>
                    <div class="field-widget"><input class="frm_text" type="text" name="key" id="key" size="50" value="'.$key.'"/></div>
                </div>
                <p><input class="sub1" type="submit" name="submit" value="'.T_('Save').'"/></p>
            </fieldset>
        </form>';

    displayFooter();
}

/**
 * displayFormSubmitPage
 * 
 * @return void
 */
function displayFormSubmitPage ()
{
    $key = isset($_POST['key']) ? cleanInput($_POST['key']) : '';

    $sql = "UPDATE `fcms_config` 
            SET `value` = '$key'
            WHERE `name` = 'youtube_key'";
    
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayFormPage(1);
}
