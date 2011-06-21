<?php
session_start();

define('URL_PREFIX', '../');

include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');
include_once('../inc/whereiseveryone_class.php');

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn('admin/');
$currentUserId = (int)escape_string($_SESSION['login_id']);

$whereObj = new WhereIsEveryone($currentUserId);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Where is everyone'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript">Event.observe(window, "load", function() { initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\'); });</script>';

// Show Header
include_once(getTheme($currentUserId, $TMPL['path']) . 'header.php');

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
    displayFooter();
    return;
}

// submit form
if (isset($_POST['submit']))
{
    $id     = isset($_POST['id'])     ? cleanInput($_POST['id'])     : '';
    $secret = isset($_POST['secret']) ? cleanInput($_POST['secret']) : '';
    $url    = isset($_POST['url'])    ? cleanInput($_POST['url'])    : '';

    $sql = "UPDATE `fcms_config` SET `fs_client_id` = '$id',
                `fs_client_secret` = '$secret', 
                `fs_callback_url` = '$url'";
    
    if (!mysql_query($sql))
    {
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }
}

$r = $whereObj->getFoursquareConfigData();

$id     = isset($r['fs_client_id'])     ? cleanInput($r['fs_client_id'])     : '';
$secret = isset($r['fs_client_secret']) ? cleanInput($r['fs_client_secret']) : '';
$url    = isset($r['fs_callback_url'])  ? cleanInput($r['fs_callback_url'])  : '';

echo '
            <div class="info-alert">
                <h1>'.T_('Step 1').'</h1>
                <p><a href="https://foursquare.com/oauth/register">'.T_('Register a new foursquare OAuth Consumer.').'</a></p>
                <p>'.T_('Be sure to include settings.php as part of your callback url.  For example: if your site is located at http://www.my-awesome-site.com/fcms/index.php then your callback url should be http://www.my-awesome-site.com/fcms/settings.php').'</p>
                <h1>'.T_('Step 2').'</h1>
                <p>'.T_('Fill out the form below with the information you provided in Step 1.').'</p>
            </div>
            <form id="addressbook_form" action="whereiseveryone.php" method="post">
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
return;

function displayFooter ()
{
    global $currentUserId, $TMPL;
    echo '
        </div><!-- .centercontent -->';
    include_once(getTheme($currentUserId, $TMPL['path']) . 'footer.php');
}
