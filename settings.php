<?php
/**
 * AddressBook 
 * 
 * PHP version 5
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

load('settings', 'foursquare', 'facebook', 'socialmedia', 'youtube');

init();

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$settingsObj   = new Settings($currentUserId);

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Settings'),
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
    global $currentUserId;

    if (checkAccess($currentUserId) == 11)
    {
        displayInvalidAccessLevel();
        return;
    }
    // Saving changes
    elseif (isset($_POST['submit']))
    {
        if ($_GET['view'] == 'account')
        {
            displayEditAccountSubmit();
        }
        elseif ($_GET['view'] == 'settings')
        {
            displayEditSettingsSubmit();
        }
        elseif ($_GET['view'] == 'notifications')
        {
            displayEditNotificationsSubmit();
        }
        elseif ($_GET['view'] == 'familynews')
        {
            displayEditFamilyNewsSubmit();
        }
        elseif ($_GET['view'] == 'messageboard')
        {
            displayEditMessageBoardSubmit();
        }
    }
    // Theme
    elseif (isset($_GET['use']) && $_GET['view'] == 'theme')
    {
        displayEditThemeSubmit();
    }
    elseif (isset($_GET['delete']) && $_GET['view'] == 'theme' && !isset($_GET['confirmed']))
    {
        displayDeleteThemeConfirmation();
    }
    elseif (isset($_POST['delconfirm']) || (isset($_GET['delete']) && isset($_GET['confirmed'])))
    {
        displayDeleteThemeSubmit();
    }
    // Import
    elseif (isset($_GET['import']) && isset($_GET['view']))
    {
        displayImportBlogPosts();
    }
    // Edit
    elseif (isset($_GET['view']))
    {
        if ($_GET['view'] == 'account')
        {
            displayEditAccount();
        }
        elseif ($_GET['view'] == 'theme')
        {
            displayEditTheme();
        }
        elseif ($_GET['view'] == 'settings')
        {
            displayEditSettings();
        }
        elseif ($_GET['view'] == 'notifications')
        {
            displayEditNotifications();
        }
        elseif ($_GET['view'] == 'familynews')
        {
            displayEditFamilyNews();
        }
        elseif ($_GET['view'] == 'messageboard')
        {
            displayEditMessageBoard();
        }
        // Facebook
        elseif ($_GET['view'] == 'socialmedia' && isset($_GET['code']) && isset($_GET['state']))
        {
            displayEditFacebookSubmit();
        }
        // Foursquare
        elseif ($_GET['view'] == 'socialmedia' && isset($_GET['code']))
        {
            displayFoursquareSubmit();
        }
        // YouTube
        elseif ($_GET['view'] == 'socialmedia' && isset($_GET['token']))
        {
            displayEditYouTubeSubmit();
        }
        elseif ($_GET['view'] == 'socialmedia')
        {
            displayEditSocialMedia();
        }
        else
        {
            displayEditAccount();
        }
    }
    // Revoke app access
    elseif (isset($_GET['revoke']))
    {
        if ($_GET['revoke'] == 'facebook')
        {
            displayRevokeFacebookAccess();
        }
        elseif ($_GET['revoke'] == 'foursquare')
        {
            displayRevokeFoursquareAccess();
        }
        elseif ($_GET['revoke'] == 'youtube')
        {
            displayRevokeYouTubeAccess();
        }
    }
    else
    {
        displayEditAccount();
    }
}

/**
 * displayHeader 
 * 
 * Displays the header of the page, including the leftcolumn navigation.
 * 
 * @param string $js Allows you to overwrite the javascript that is included in the header.
 * 
 * @return void
 */
function displayHeader ($js = '')
{
    global $currentUserId, $TMPL;

    $TMPL['javascript'] = $js;

    // Default js
    if ($js == '')
    {
        $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[ 
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    initAdvancedTagging();
});
//]]>
</script>';
    }

    include_once getTheme($currentUserId).'header.php';

    echo '
        <div id="settings" class="centercontent">

            <div id="leftcolumn">
                <h3>'.T_('General Settings').'</h3>
                <ul class="menu">
                    <li><a href="?view=account">'.T_('Account').'</a></li>
                    <li><a href="?view=theme">'.T_('Theme').'</a></li>
                    <li><a href="?view=settings">'.T_('Settings').'</a></li>
                    <li><a href="?view=notifications">'.T_('Notifications').'</a></li>
                    <li><a href="?view=socialmedia">'.T_('Social Media').'</a></li>
                </ul>
                <h3>'.T_('Section Settings').'</h3>
                <ul class="menu">
                    <li><a href="?view=familynews">'.T_('Family News').'</a></li>
                    <li><a href="?view=messageboard">'.T_('Message Board').'</a></li>
                </ul>
            </div>

            <div id="maincolumn">';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter()
{
    global $currentUserId, $TMPL;

    echo '
            </div>
            <div style="clear:both"></div>
        </div><!-- #settings .centercontent -->';

    include_once getTheme($currentUserId).'footer.php';
}

/**
 * displayEditAccount 
 * 
 * @return void
 */
function displayEditAccount ()
{
    global $currentUserId, $settingsObj;

    displayHeader();
    $settingsObj->displayAccountInformation();
    displayFooter();

    return;
}

/**
 * displayEditAccountSubmit 
 * 
 * @return void
 */
function displayEditAccountSubmit ()
{
    global $currentUserId, $settingsObj;

    $emailstart = $settingsObj->currentUserEmail;

    // Check email
    if ($_POST['email'] != $emailstart)
    {
        $sql2 = "SELECT `email` FROM `fcms_users` 
                 WHERE email='".cleanInput($_POST['email'])."'";

        $result = mysql_query($sql2);
        if (!$result)
        {
            displayHeader();
            displaySQLError('Email Error', ___FILE___.' ['.__LINE__.']', $sql2, mysql_error());
            displayFooter();
            return;
        }

        $email_check = mysql_num_rows($result);

        if ($email_check > 0)
        {
            displayHeader();
            echo '
            <p class="error-alert">
                '.sprintf(T_('The email address %s is already in use.  Please choose a different email.'), $_POST['email']).'
            </p>';

            $settingsObj->displayAccountInformation();
            displayFooter();
            return;
        }
    }

    $sql = "UPDATE `fcms_users` SET ";

    if (isset($_POST['pass']))
    {
        $orig_pass = $_SESSION['login_pw'];
        if (!empty($_POST['pass']))
        {
            $sql .= "password = '".md5($_POST['pass'])."', ";
            $_SESSION['login_pw'] = md5($_POST['pass']);
        }
    }

    $sql .= "`email` = '".cleanInput($_POST['email'])."'
            WHERE id = '$currentUserId'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Update User Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    if (isset($orig_pass))
    {
        echo '
<html>
<head>
<title>'.T_('Password Change').'</title>
<style>
html { font: 12px/18px Verdana, Arial, sans-serif; background-color: #fff; color: #333; text-align: center; }
body { width: 500px; margin: 50px auto; }
div { padding: 30px; background-color: #cff0cc; -moz-border-radius: 10px; -webkit-border-radius: 10px; border-radius: 10px; }
h1 { font: bold 20px/28px Verdana, Arial, sans-serif; }
a { font: bold 14px/20px Verdana, Arial, sans-serif; background-color: #aae4a5; color: #333; text-decoration: none; padding: 5px 15px; -moz-border-radius: 10px; -webkit-border-radius: 10px; border-radius: 10px; }
a:hover { background-color: #6cd163; }
</style>
</head>
<body>
    <div class="ok-alert">
        <h1>'.T_('To complete your changes, you must logout and log back in again.').'</h1><br/>
        <a href="logout.php">'.T_('Logout').'</a><br/>
        <p>'.T_('You will be automatically logged out in 10 seconds.').'</a></p>
    </div>
    <meta http-equiv=\'refresh\' content=\'10;URL=logout.php\'>
</body>
</html>';

        return;
    }

    displayHeader();
    displayOkMessage();
    $settingsObj->displayAccountInformation();
    displayFooter();
}

/**
 * displayEditTheme 
 * 
 * @return void
 */
function displayEditTheme ()
{
    global $currentUserId, $settingsObj;

    $js = '
<script type="text/javascript">
Event.observe(window, \'load\', function() {
    deleteConfirmationLinks("del_theme", "'.T_('Are you sure you want to DELETE this theme?').'");
});
</script>';

    displayHeader($js);
    $settingsObj->displayTheme();
    displayFooter();

    return;
}

/**
 * displayEditThemeSubmit 
 * 
 * Changes the theme.
 * 
 * @return void
 */
function displayEditThemeSubmit ()
{
    global $currentUserId, $settingsObj;

    $theme = basename($_GET['use']);

    $sql = "UPDATE `fcms_user_settings`
            SET `theme` = '$theme'
            WHERE `user` = '$currentUserId'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Update Theme Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayHeader();
    displayOkMessage();

    $settingsObj->displayTheme();

    displayFooter();
}

/**
 * displayDeleteThemeSubmit 
 * 
 * @return void
 */
function displayDeleteThemeSubmit ()
{
    global $currentUserId, $settingsObj;

    displayHeader();

    $theme = basename($_GET['delete']);

    if (!file_exists("themes/$theme"))
    {
        echo '
                <p class="error-alert">'.sprintf(T_('Theme [%s] not found.'), $theme).'</p>';
        $settingsObj->displayTheme();
        displayFooter();
        return;
    }

    if (!is_dir("themes/$theme"))
    {
        echo '
                <p class="error-alert">'.sprintf(T_('[%s] is not a directory.'), $theme).'</p>';
        $settingsObj->displayTheme();
        displayFooter();
        return;
    }

    if (!deleteDirectory("themes/$theme"))
    {
        echo '
                <p class="error-alert">'.sprintf(T_('Could not delete theme [%s].'), $theme).'</p>';
        $settingsObj->displayTheme();
        displayFooter();
        return;
    }

    displayOkMessage();
    $settingsObj->displayTheme();
    displayFooter();
}

/**
 * displayEditSettings 
 * 
 * @return void
 */
function displayEditSettings ()
{
    global $currentUserId, $settingsObj;

    displayHeader();
    $settingsObj->displaySettings();
    displayFooter();

    return;
}

/**
 * displayEditSettingsSubmit 
 * 
 * @return void
 */
function displayEditSettingsSubmit ()
{
    global $currentUserId, $settingsObj;

    displayHeader();

    $sql = "UPDATE `fcms_user_settings` SET ";

    if ($_POST['advanced_upload'])
    {
        if ($_POST['advanced_upload'] == 'yes')
        {
            $sql .= "`advanced_upload` = '1', ";
        }
        else
        {
            $sql .= "`advanced_upload` = '0', ";
        }
    }
    if ($_POST['advanced_tagging'])
    {
        if ($_POST['advanced_tagging'] == 'yes')
        {
            $sql .= "`advanced_tagging` = '1', ";
        }
        else
        {
            $sql .= "`advanced_tagging` = '0', ";
        }
    }
    if ($_POST['language'])
    {
        $sql .= "`language` = '".cleanInput($_POST['language'])."', ";
    }
    if ($_POST['timezone'])
    {
        $sql .= "`timezone` = '".cleanInput($_POST['timezone'])."', ";
    }
    if ($_POST['dst'])
    {
        if ($_POST['dst'] == 'on')
        {
            $sql .= "`dst` = '1', ";
        }
        else
        {
            $sql .= "`dst` = '0', ";
        }
    }
    if ($_POST['displayname'])
    {
        $sql .= "`displayname` = '".cleanInput($_POST['displayname'], 'int')."', ";
    }
    if ($_POST['frontpage'])
    {
        $sql .= "`frontpage` = '".cleanInput($_POST['frontpage'])."', ";
    }

    $sql  = substr($sql, 0, -2); // remove the extra comma space at the end
    $sql .= " WHERE `user` = '$currentUserId'";

    if (strlen($sql) > 50)
    {
        if (!mysql_query($sql))
        {
            displaySQLError('Update User Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        displayOkMessage();
    }

    $settingsObj->displaySettings();
    displayFooter();
}

/**
 * displayEditNotifications 
 * 
 * @return void
 */
function displayEditNotifications ()
{
    global $currentUserId, $settingsObj;

    displayHeader();
    $settingsObj->displayNotifications();
    displayFooter();

    return;
}

/**
 * displayEditNotificationsSubmit 
 * 
 * @return void
 */
function displayEditNotificationsSubmit ()
{
    global $currentUserId, $settingsObj;

    displayHeader();

    if ($_POST['email_updates'])
    {
        if ($_POST['email_updates'] == 'yes')
        {
            $email_updates = '1';
        }
        else
        {
            $email_updates = '0';
        }
        $sql = "UPDATE `fcms_user_settings`
                SET `email_updates` = '$email_updates'
                WHERE `user` = '$currentUserId'";

        if (!mysql_query($sql))
        {
            displaySQLError('Update User Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        displayOkMessage();
    }

    $settingsObj->displayNotifications();
    displayFooter();
}

/**
 * displayEditFamilyNews 
 * 
 * @return void
 */
function displayEditFamilyNews ()
{
    global $currentUserId, $settingsObj;

    displayHeader();
    $settingsObj->displayFamilyNews();
    displayFooter();

    return;
}

/**
 * displayEditFamilyNewsSubmit 
 * 
 * @return void
 */
function displayEditFamilyNewsSubmit ()
{
    global $currentUserId, $settingsObj;

    displayHeader();

    $blogger   = isset($_POST['blogger'])   ? cleanInput($_POST['blogger'])   : '';
    $tumblr    = isset($_POST['tumblr'])    ? cleanInput($_POST['tumblr'])    : '';
    $wordpress = isset($_POST['wordpress']) ? cleanInput($_POST['wordpress']) : '';
    $posterous = isset($_POST['posterous']) ? cleanInput($_POST['posterous']) : '';

    $sql = "UPDATE `fcms_user_settings`
            SET `blogger` = '$blogger',
            `tumblr` = '$tumblr',
            `wordpress` = '$wordpress',
            `posterous` = '$posterous'
            WHERE `user` = '$currentUserId'";

    if (!mysql_query($sql))
    {
        displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayOkMessage();

    $settingsObj->displayFamilyNews();
    displayFooter();
}

/**
 * displayEditMessageBoard 
 * 
 * @return void
 */
function displayEditMessageBoard ()
{
    global $currentUserId, $settingsObj;

    displayHeader();
    $settingsObj->displayMessageBoard();
    displayFooter();

    return;
}

/**
 * displayEditMessageBoardSubmit
 * 
 * @return void
 */
function displayEditMessageBoardSubmit ()
{
    global $currentUserId, $settingsObj;

    displayHeader();

    if (isset($_POST['boardsort']) && isset($_POST['showavatar']))
    {
        $showavatar = ($_POST['showavatar'] == 'yes') ? 1 : 0;
        $boardsort  = cleanInput($_POST['boardsort']);

        $sql = "UPDATE `fcms_user_settings`
                SET `boardsort` = '$boardsort',
                    `showavatar` = '$showavatar'
                WHERE `user` = '$currentUserId'";

        if (!mysql_query($sql))
        {
            displaySQLError('Update MB Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        displayOkMessage();
    }

    $settingsObj->displayMessageBoard();
    displayFooter();
}

/**
 * displayFoursquareSubmit 
 * 
 * The submit screen for saving foursquare data.
 * 
 * @return void
 */
function displayFoursquareSubmit ()
{
    global $currentUserId, $settingsObj;

    $r = getFoursquareConfigData();

    $id     = cleanOutput($r['fs_client_id']);
    $secret = cleanOutput($r['fs_client_secret']);
    $url    = cleanOutput($r['fs_callback_url']);

    $fsObj = new EpiFoursquare($id, $secret);
    $token = $fsObj->getAccessToken($_GET['code'], $url);

    $fsObjAuth = new EpiFoursquare($id, $secret, $token->access_token);
    $self      = $fsObjAuth->get('/users/self');

    $sql = "UPDATE `fcms_user_settings`
            SET `fs_user_id` = '".$self->response->user->id."',
                `fs_access_token` = '".$token->access_token."'
            WHERE `user` = '$currentUserId'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: settings.php?view=socialmedia");
}

/**
 * displayRevokeFoursquareAccess 
 * 
 * @return void
 */
function displayRevokeFoursquareAccess ()
{
    global $currentUserId;

    $sql = "UPDATE `fcms_user_settings`
            SET `fs_user_id` = NULL, `fs_access_token` = NULL
            WHERE `user` = '$currentUserId'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: settings.php?view=socialmedia");
}

/**
 * displayImportBlogPosts 
 * 
 * @return void
 */
function displayImportBlogPosts ()
{
    global $currentUserId, $settingsObj;

    displayHeader();

    // setup familynew obj
    include_once 'inc/familynews_class.php';
    $newsObj = new FamilyNews($currentUserId);

    // get external ids
    $external_ids = $newsObj->getExternalPostIds();

    // Get import blog settings
    $sql = "SELECT `user`, `blogger`, `tumblr`, `wordpress`, `posterous`
            FROM `fcms_user_settings`
            WHERE `user` = '$currentUserId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }
    if (mysql_num_rows($result) <= 0)
    {
        echo '<div class="error-alert">'.T_('Nothing to import.').'</div>';
        $settingsObj->displayFamilyNews();
        displayFooter();
        return;
    }

    $r = mysql_fetch_assoc($result);

    $count = 0;

    switch ($_GET['import'])
    {
        case 'blogger':
            $count = $newsObj->importBloggerPosts($r['blogger'], $currentUserId, '', $external_ids);
            if ($count === false)
            {
                $settingsObj->displayFamilyNews();
                displayFooter();
                return;
            }
            break;

        case 'tumblr':
            $count = $newsObj->importTumblrPosts($r['tumblr'], $currentUserId, '', $external_ids);
            if ($count === false)
            {
                $settingsObj->displayFamilyNews();
                displayFooter();
                return;
            }
            break;

        case 'wordpress':
            $count = $newsObj->importWordpressPosts($r['wordpress'], $currentUserId, '', $external_ids);
            if ($count === false)
            {
                $settingsObj->displayFamilyNews();
                displayFooter();
                return;
            }
            break;

        case 'posterous':
            $count = $newsObj->importPosterousPosts($r['posterous'], $currentUserId, '', $external_ids);
            if ($count === false)
            {
                $settingsObj->displayFamilyNews();
                displayFooter();
                return;
            }
            break;
    }

    displayOkMessage(sprintf(T_ngettext('%d post has been imported.', '%d posts have been imported.', $count), $count));
    $settingsObj->displayFamilyNews();
    displayFooter();

    return;
}

/**
 * displayDeleteThemeConfirmation 
 * 
 * The confirmation screen that is shown when trying to delete a theme with js turned off.
 * 
 * @return void
 */
function displayDeleteThemeConfirmation ()
{
    $theme = basename($_GET['delete']);

    displayHeader();

    echo '
                <div class="info-alert clearfix">
                    <form action="?view=theme&amp;delete='.$theme.'&amp;confirmed=1" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="?view=theme">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    displayFooter();
}

/**
 * displayInvalidAccessLevel 
 * 
 * @return void
 */
function displayInvalidAccessLevel ()
{
    displayHeader();

    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                <a href="contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

    displayFooter();
}

/**
 * displayEditFacebookSubmit 
 * 
 * @return void
 */
function displayEditFacebookSubmit ()
{
    global $currentUserId, $settingsObj;

    $data = getFacebookConfigData();

    if (!empty($data['fb_app_id']) && !empty($data['fb_secret']))
    {
        $facebook = new Facebook(array(
          'appId'  => $data['fb_app_id'],
          'secret' => $data['fb_secret'],
        ));

        $accessToken = $facebook->getAccessToken();

        $sql = "UPDATE `fcms_user_settings`
                SET `fb_access_token` = '$accessToken'
                WHERE `user` = '$currentUserId'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }
    }

    // Facebook isn't configured
    else
    {
        displayHeader();

        echo '
            <div class="info-alert">
                <h2>'.T_('Facebook isn\'t Configured Yet.').'</h2>
                <p>'.T_('Unfortunately, your website administrator has not set up Facebook yet.').'</p>
            </div>';

        displayFooter();
    }

    header("Location: settings.php?view=socialmedia");
}

/**
 * displayRevokeFacebookAccess 
 * 
 * @return void
 */
function displayRevokeFacebookAccess ()
{
    global $currentUserId;

    $sql = "UPDATE `fcms_user_settings`
            SET `fb_access_token` = NULL
            WHERE `user` = '$currentUserId'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    // remove any facebook session vars
    foreach ($_SESSION as $key => $val)
    {
        if (substr($key, 0, 3) == 'fb_')
        {
            unset($_SESSION[$key]);
        }
    }

    header("Location: settings.php?view=socialmedia");
}

/**
 * displayEditYouTubeSubmit
 * 
 * @return void
 */
function displayEditYouTubeSubmit ()
{
    global $currentUserId, $settingsObj;

    $data = getYouTubeConfigData();

    $singleUseToken = $_GET['token'];

    if (!empty($data['youtube_key']))
    {
        // Exchange single use token for a session token
        try
        {
            $sessionToken = Zend_Gdata_AuthSub::getAuthSubSessionToken($singleUseToken);
        }
        catch (Zend_Gdata_App_Exception $e)
        {
            displayHeader();
            echo '
            <div class="error-alert">ERROR - Token upgrade for ['.$singleUseToken.'] failed: '.$e->getMessage();
            displayFooter();
            return;
        }

        $_SESSION['sessionToken'] = $sessionToken;

        $sql = "UPDATE `fcms_user_settings`
                SET `youtube_session_token` = '$sessionToken'
                WHERE `user` = '$currentUserId'";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }
    }

    // YouTube isn't configured
    else
    {
        displayHeader();

        echo '
            <div class="info-alert">
                <h2>'.T_('YouTube isn\'t Configured Yet.').'</h2>
                <p>'.T_('Unfortunately, your website administrator has not set up YouTube yet.').'</p>
            </div>';

        displayFooter();
        return;
    }

    header("Location: settings.php?view=socialmedia");
}

/**
 * displayRevokeYouTubeAccess 
 * 
 * @return void
 */
function displayRevokeYouTubeAccess ()
{
    global $currentUserId;

    if (isset($_SESSION['sessionToken']))
    {
        unset($_SESSION['sessionToken']);
    }

    $sql = "UPDATE `fcms_user_settings`
            SET `youtube_session_token` = NULL
            WHERE `user` = '$currentUserId'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: settings.php?view=socialmedia");
}

/**
 * displayEditSocialMedia 
 * 
 * @return void
 */
function displayEditSocialMedia ()
{
    global $currentUserId;

    displayHeader();

    // Get Data
    $facebookConfig      = getFacebookConfigData();
    $facebookAccessToken = getUserFacebookAccessToken($currentUserId);
    $foursquareConfig    = getFoursquareConfigData();
    $foursquareUser      = getFoursquareUserData($currentUserId);
    $youtubeConfig       = getYouTubeConfigData();
    $youtubeUser         = getYouTubeUserData($currentUserId);

    // Setup url for callbacks
    $callbackUrl  = getDomainAndDir();
    $callbackUrl .= 'settings.php?view=socialmedia';

    // Facebook
    //------------------------------------------------------------------------------------
    $facebookRow    = '';
    $facebookStatus = '';
    $facebookLink   = '';

    if (!empty($facebookConfig['fb_app_id']) && !empty($facebookConfig['fb_secret']))
    {
        $facebook = new Facebook(array(
            'appId'  => $facebookConfig['fb_app_id'],
            'secret' => $facebookConfig['fb_secret'],
        ));

        $facebook->setAccessToken($facebookAccessToken);

        // Check if the user is logged in and authed
        $fbUser    = $facebook->getUser();
        $fbProfile = '';
        if ($fbUser)
        {
            try
            {
                $fbProfile = $facebook->api('/me');
            }
            catch (FacebookApiException $e)
            {
                $fbUser = null;
            }
        }

        if ($fbUser)
        {
            $facebookStatus = '<a href="'.$fbProfile['link'].'">'.$fbProfile['email'].'</span>';
            $facebookLink   = '<a class="disconnect" href="?revoke=facebook">'.T_('Disconnect').'</a>';
        }
        else
        {
            $params = array('scope' => 'user_about_me,user_birthday,user_location,email,publish_stream,offline_access');

            $facebookStatus = '<span class="not_connected">'.T_('Not Connected').'</span>';
            $facebookLink   = '<a href="'.$facebook->getLoginUrl($params).'">'.T_('Connect').'</a>';
        }

        $facebookRow = '
                <tr>
                    <td><img src="themes/images/facebook_24.png" alt="'.T_('Facebook').'"/></td>
                    <td>'.T_('Facebook').'</td>
                    <td>'.$facebookStatus.'</td>
                    <td>'.$facebookLink.'</td>
                </tr>';
    }

    // Foursquare
    //------------------------------------------------------------------------------------
    $foursquareRow    = '';
    $foursquareStatus = '';
    $foursquareLink   = '';

    if (!empty($foursquareConfig['fs_client_id']) && !empty($foursquareConfig['fs_client_secret']))
    {
        $fsObj = new EpiFoursquare($foursquareConfig['fs_client_id'], $foursquareConfig['fs_client_secret']);

        if (!empty($foursquareUser['fs_user_id']) && !empty($foursquareUser['fs_access_token']))
        {
            $fsObjAuth = new EpiFoursquare($foursquareConfig['fs_client_id'], $foursquareConfig['fs_client_secret'], $foursquareUser['fs_access_token']);
            $self      = $fsObjAuth->get('/users/self');

            $foursquareStatus = '<a href="http://foursquare.com/user/'.$self->response->user->id.'">'.$self->response->user->contact->email.'</a>';
            $foursquareLink   = '<a class="disconnect" href="?revoke=foursquare">'.T_('Disconnect').'</a>';
        }
        else
        {
            $foursquareStatus = '<span class="not_connected">'.T_('Not Connected').'</span>';
            $foursquareLink   = '<a href="'.$fsObj->getAuthorizeUrl($callbackUrl).'">'.T_('Connect').'</a>';
        }

        $foursquareRow = '
                <tr>
                    <td><img src="themes/images/foursquare_24.png" alt="'.T_('Foursquare').'"/></td>
                    <td>'.T_('Foursquare').'</td>
                    <td>'.$foursquareStatus.'</td>
                    <td>'.$foursquareLink.'</td>
                </tr>';
    }

    // YouTube
    //------------------------------------------------------------------------------------
    $youtubeRow        = '';
    $youtubeRowPrivate = '';
    $youtubeStatus     = '';
    $youtubeLink       = '';

    if (!empty($youtubeConfig['youtube_key']))
    {
        if (!empty($youtubeUser['youtube_session_token']))
        {
            $httpClient = getAuthSubHttpClient($youtubeConfig['youtube_key'], $youtubeUser['youtube_session_token']);

            $youTubeService = new Zend_Gdata_YouTube($httpClient);

            $feed = $youTubeService->getUserProfile('default');
            if (!$feed instanceof Zend_Gdata_YouTube_UserProfileEntry)
            {
                print '
            <div class="error-alert">'.T_('Could not get YouTube data for user.').'</div>';
                return;
            }

            $username = $feed->getUsername();

            $youtubeStatus = '<a href="http://www.youtube.com/user/'.$username.'">'.$username.'</a>';
            $youtubeLink   = '<a class="disconnect" href="?revoke=youtube">'.T_('Disconnect').'</a>';
        }
        else
        {
            $url = Zend_Gdata_AuthSub::getAuthSubTokenUri($callbackUrl, 'http://gdata.youtube.com', false, true);

            $youtubeStatus = '<span class="not_connected">'.T_('Not Connected').'</span>';
            $youtubeLink   = '<a href="'.$url.'">'.T_('Connect').'</a>';
        }

        $youtubeRow = '
                <tr>
                    <td><img src="themes/images/youtube_24.png" alt="'.T_('YouTube').'"/></td>
                    <td>'.T_('YouTube').'</td>
                    <td>'.$youtubeStatus.'</td>
                    <td>'.$youtubeLink.'</td>
                </tr>';
    }

    // Blank state
    if ($facebookRow == '' && $foursquareRow == '' && $youtubeRow == '')
    {
        echo '
        <div class="info-alert">
            <h2>'.T_('Bummer!').'</h2>
            <p>'.T_('Your site is not configured to use any Social Media sites yet.').'</p>
            <p>'.T_('Your site administrator must configure these before you can connect to them.').'</p>
        </div>';
        displayFooter();
        return;
    }

    // Error code was given
    if (isset($_GET['error']))
    {
        echo '
        <div class="error-alert">
            <h2>'.T_('Error connecting site.').'</h2>
            <p>'.T_('Site returned error code:').'</p>
            <p style="text-align:center"><i>'.$_GET['error'].'</i></p>
        </div>';
    }

    echo '
        <table id="socialmedia-connect">
            <tbody>'.$facebookRow.$foursquareRow.$youtubeRow.'
            </tbody>
        </table><br/>';


    // Add a row that allows user to decide if they want to share private videos
    if ($youtubeStatus !== '' && $youtubeLink !== '')
    {
        echo '
        <div class="info-alert">
            <p><b>'.T_('YouTube Private Videos').'</b></p>
            <p>
                '.T_('By connecting your account to YouTube, you are allowing all members of this site to view your private videos from YouTube.').'
            </p>
            <p><a href="help.php#video-youtube-private">'.T_('Learn more.').'</a></p>
        </div>';
    }

    displayFooter();
}

