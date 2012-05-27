<?php
/**
 * Settings
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
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('settings', 'foursquare', 'facebook', 'socialmedia', 'youtube', 'instagram');

init();

// Globals
$settingsObj = new Settings($fcmsUser->id);

$TMPL = array(
    'currentUserId' => $fcmsUser->id,
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Settings'),
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
    global $fcmsUser;

    if (checkAccess($fcmsUser->id) == 11)
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
        elseif ($_GET['view'] == 'facebook')
        {
            if (isset($_GET['code']) && isset($_GET['state']))
            {
                displayEditFacebookSubmit();
            }
            else
            {
                displayEditFacebook();
            }
        }
        // Foursquare
        elseif ($_GET['view'] == 'foursquare')
        {
            if (isset($_GET['code']))
            {
                displayFoursquareSubmit();
            }
            else
            {
                displayEditFoursquare();
            }
        }
        // Instagram
        elseif ($_GET['view'] == 'instagram')
        {
            if (isset($_GET['code']))
            {
                displayEditInstagramSubmit();
            }
            else
            {
                displayEditInstagram();
            }
        }
        // YouTube
        elseif ($_GET['view'] == 'youtube')
        {
            if (isset($_GET['token']))
            {
                displayEditYouTubeSubmit();
            }
            else
            {
                displayEditYouTube();
            }
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
        elseif ($_GET['revoke'] == 'instagram')
        {
            displayRevokeInstagramAccess();
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
    global $fcmsUser, $TMPL;

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

    include_once getTheme($fcmsUser->id).'header.php';

    echo '
        <div id="settings" class="centercontent">

            <div id="leftcolumn">
                <h3>'.T_('General Settings').'</h3>
                <ul class="menu">
                    <li><a href="?view=account">'.T_('Account').'</a></li>
                    <li><a href="?view=theme">'.T_('Theme').'</a></li>
                    <li><a href="?view=settings">'.T_('Settings').'</a></li>
                    <li><a href="?view=notifications">'.T_('Notifications').'</a></li>
                </ul>
                <h3>'.T_('Plugin Settings').'</h3>
                <ul class="menu">
                    <li><a href="?view=familynews">'.T_('Family News').'</a></li>
                    <li><a href="?view=messageboard">'.T_('Message Board').'</a></li>
                </ul>';

    $facebookConfig   = getFacebookConfigData();
    $foursquareConfig = getFoursquareConfigData();
    $instagramConfig  = getInstagramConfigData();
    $youtubeConfig    = getYouTubeConfigData();

    $facebookLink   = '';
    $foursquareLink = '';
    $instagramLink  = '';
    $youtubeLink    = '';

    if (!empty($facebookConfig['fb_app_id']) && !empty($facebookConfig['fb_secret']))
    {
        $facebookLink = '<li><a href="?view=facebook">Facebook</a></li>';
    }

    if (!empty($foursquareConfig['fs_client_id']) && !empty($foursquareConfig['fs_client_secret']))
    {
        $foursquareLink = '<li><a href="?view=foursquare">Foursquare</a></li>';
    }

    if (!empty($instagramConfig['instagram_client_id']) && !empty($instagramConfig['instagram_client_secret']))
    {
        $instagramLink = '<li><a href="?view=instagram">Instagram</a></li>';
    }

    if (!empty($youtubeConfig['youtube_key']))
    {
        $youtubeLink = '<li><a href="?view=youtube">YouTube</a></li>';
    }

    $links = "$facebookLink$foursquareLink$instagramLink$youtubeLink";

    if (!empty($links))
    {
        echo '
                <h3>'.T_('Social Media').'</h3>
                <ul class="menu">
                    '.$facebookLink.'
                    '.$foursquareLink.'
                    '.$instagramLink.'
                    '.$youtubeLink.'
                </ul>';
    }

    echo '
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
    global $fcmsUser, $TMPL;

    echo '
            </div>
            <div style="clear:both"></div>
        </div><!-- #settings .centercontent -->';

    include_once getTheme($fcmsUser->id).'footer.php';
}

/**
 * displayEditAccount 
 * 
 * @return void
 */
function displayEditAccount ()
{
    global $fcmsUser, $settingsObj;

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
    global $fcmsUser, $settingsObj;

    $email      = strip_tags($_POST['email']);
    $cleanEmail = escape_string($email);
    $emailstart = $settingsObj->currentUserEmail;

    // Check email
    if ($_POST['email'] != $emailstart)
    {
        $sql2 = "SELECT `email` FROM `fcms_users` 
                 WHERE email='$cleanEmail'";

        $result = mysql_query($sql2);
        if (!$result)
        {
            displayHeader();
            displaySqlError($sql2, mysql_error());
            displayFooter();
            return;
        }

        $email_check = mysql_num_rows($result);

        if ($email_check > 0)
        {
            displayHeader();
            echo '
            <p class="error-alert">
                '.sprintf(T_('The email address %s is already in use.  Please choose a different email.'), $email).'
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

    $sql .= "`email` = '$cleanEmail'
            WHERE id = '$fcmsUser->id'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
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
    global $fcmsUser, $settingsObj;

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
    global $fcmsUser, $settingsObj;

    $theme = basename($_GET['use']);
    $theme = escape_string($theme);

    $sql = "UPDATE `fcms_user_settings`
            SET `theme` = '$theme'
            WHERE `user` = '$fcmsUser->id'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
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
    global $fcmsUser, $settingsObj;

    displayHeader();

    $theme = basename($_GET['delete']);

    if (!file_exists(THEMES.$theme))
    {
        echo '
                <p class="error-alert">'.sprintf(T_('Theme [%s] not found.'), $theme).'</p>';
        $settingsObj->displayTheme();
        displayFooter();
        return;
    }

    if (!is_dir(THEMES.$theme))
    {
        echo '
                <p class="error-alert">'.sprintf(T_('[%s] is not a directory.'), $theme).'</p>';
        $settingsObj->displayTheme();
        displayFooter();
        return;
    }

    if (!deleteDirectory(THEMES.$theme))
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
    global $fcmsUser, $settingsObj;

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
    global $fcmsUser, $settingsObj;

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
        $sql .= "`language` = '".escape_string($_POST['language'])."', ";
    }
    if ($_POST['timezone'])
    {
        $sql .= "`timezone` = '".escape_string($_POST['timezone'])."', ";
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
        $sql .= "`displayname` = '".(int)$_POST['displayname']."', ";
    }
    if ($_POST['frontpage'])
    {
        $sql .= "`frontpage` = '".escape_string($_POST['frontpage'])."', ";
    }

    $sql  = substr($sql, 0, -2); // remove the extra comma space at the end
    $sql .= " WHERE `user` = '$fcmsUser->id'";

    if (strlen($sql) > 50)
    {
        if (!mysql_query($sql))
        {
            displaySqlError($sql, mysql_error());
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
    global $fcmsUser, $settingsObj;

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
    global $fcmsUser, $settingsObj;

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
                WHERE `user` = '$fcmsUser->id'";

        if (!mysql_query($sql))
        {
            displaySqlError($sql, mysql_error());
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
    global $fcmsUser, $settingsObj;

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
    global $fcmsUser, $settingsObj;

    displayHeader();

    $blogger   = isset($_POST['blogger'])   ? escape_string($_POST['blogger'])   : '';
    $tumblr    = isset($_POST['tumblr'])    ? escape_string($_POST['tumblr'])    : '';
    $wordpress = isset($_POST['wordpress']) ? escape_string($_POST['wordpress']) : '';
    $posterous = isset($_POST['posterous']) ? escape_string($_POST['posterous']) : '';

    $sql = "UPDATE `fcms_user_settings`
            SET `blogger` = '$blogger',
            `tumblr` = '$tumblr',
            `wordpress` = '$wordpress',
            `posterous` = '$posterous'
            WHERE `user` = '$fcmsUser->id'";

    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
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
    global $fcmsUser, $settingsObj;

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
    global $fcmsUser, $settingsObj;

    displayHeader();

    if (isset($_POST['boardsort']))
    {
        $boardsort  = escape_string($_POST['boardsort']);

        $sql = "UPDATE `fcms_user_settings`
                SET `boardsort` = '$boardsort'
                WHERE `user` = '$fcmsUser->id'";

        if (!mysql_query($sql))
        {
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        displayOkMessage();
    }

    $settingsObj->displayMessageBoard();
    displayFooter();
}

/**
 * displayImportBlogPosts 
 * 
 * @return void
 */
function displayImportBlogPosts ()
{
    global $fcmsUser, $settingsObj;

    displayHeader();

    // setup familynew obj
    include_once 'inc/familynews_class.php';
    $newsObj = new FamilyNews($fcmsUser->id);

    // get external ids
    $external_ids = $newsObj->getExternalPostIds();

    // Get import blog settings
    $sql = "SELECT `user`, `blogger`, `tumblr`, `wordpress`, `posterous`
            FROM `fcms_user_settings`
            WHERE `user` = '$fcmsUser->id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
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
            $count = $newsObj->importBloggerPosts($r['blogger'], $fcmsUser->id, '', $external_ids);
            if ($count === false)
            {
                $settingsObj->displayFamilyNews();
                displayFooter();
                return;
            }
            break;

        case 'tumblr':
            $count = $newsObj->importTumblrPosts($r['tumblr'], $fcmsUser->id, '', $external_ids);
            if ($count === false)
            {
                $settingsObj->displayFamilyNews();
                displayFooter();
                return;
            }
            break;

        case 'wordpress':
            $count = $newsObj->importWordpressPosts($r['wordpress'], $fcmsUser->id, '', $external_ids);
            if ($count === false)
            {
                $settingsObj->displayFamilyNews();
                displayFooter();
                return;
            }
            break;

        case 'posterous':
            $count = $newsObj->importPosterousPosts($r['posterous'], $fcmsUser->id, '', $external_ids);
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
    $theme = cleanOutput($theme);

    displayHeader();

    echo '
                <div class="info-alert">
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
 * displayEditFacebook 
 * 
 * @return void
 */
function displayEditFacebook ()
{
    global $fcmsUser;

    displayHeader();

    $config      = getFacebookConfigData();
    $accessToken = getUserFacebookAccessToken($fcmsUser->id);

    if (!empty($config['fb_app_id']) && !empty($config['fb_secret']))
    {
        // Setup url for callbacks
        $callbackUrl  = getDomainAndDir();
        $callbackUrl .= 'settings.php?view=facebook';

        $facebook = new Facebook(array(
            'appId'  => $config['fb_app_id'],
            'secret' => $config['fb_secret'],
        ));

        $facebook->setAccessToken($accessToken);

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
            $user    = '<a href="'.$fbProfile['link'].'">'.$fbProfile['email'].'</a>';
            $status  = sprintf(T_('Currently connected as: %s'), $user);
            $status .= '<br/><br/><img src="https://graph.facebook.com/'.$fbUser.'/picture" alt="Facebook">';
            $link    = '<a class="disconnect" href="?revoke=facebook">'.T_('Disconnect').'</a>';
        }
        else
        {
            $params = array('scope' => 'user_about_me,user_birthday,user_location,email,publish_stream,offline_access');

            $status = T_('Not Connected');
            $link   = '<a href="'.$facebook->getLoginUrl($params).'">'.T_('Connect').'</a>';
        }
    }

    echo '
        <div class="social-media-connect">
            <img class="icon" src="ui/images/facebook.png" alt="Facebook"/>
            <h2>Facebook</h2>
            <p>'.T_('Facebook helps you connect and share with the people in your life.').'</p>
            <div class="status">'.$status.'</div>
            <div class="action">'.$link.'</div>
        </div>';

    displayFooter();
}

/**
 * displayEditFacebookSubmit 
 * 
 * @return void
 */
function displayEditFacebookSubmit ()
{
    global $fcmsUser, $settingsObj;

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
                WHERE `user` = '$fcmsUser->id'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
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

    header("Location: settings.php?view=facebook");
}

/**
 * displayRevokeFacebookAccess 
 * 
 * @return void
 */
function displayRevokeFacebookAccess ()
{
    global $fcmsUser;

    $sql = "UPDATE `fcms_user_settings`
            SET `fb_access_token` = NULL
            WHERE `user` = '$fcmsUser->id'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
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

    header("Location: settings.php?view=facebook");
}

/**
 * displayEditFoursquare 
 * 
 * @return void
 */
function displayEditFoursquare ()
{
    global $fcmsUser;

    displayHeader();

    $config = getFoursquareConfigData();
    $user   = getFoursquareUserData($fcmsUser->id);

    // Setup url for callbacks
    $callbackUrl  = getDomainAndDir();
    $callbackUrl .= 'settings.php?view=foursquare';

    $fsObj = new EpiFoursquare($config['fs_client_id'], $config['fs_client_secret']);

    if (!empty($user['fs_user_id']) && !empty($user['fs_access_token']))
    {
        $fsObjAuth = new EpiFoursquare(
                        $config['fs_client_id'], 
                        $config['fs_client_secret'], 
                        $user['fs_access_token']
        );

        $self = $fsObjAuth->get('/users/self');

        $user    = '<a href="http://foursquare.com/user/'.$self->response->user->id.'">'.$self->response->user->contact->email.'</a>';
        $status  = sprintf(T_('Currently connected as: %s'), $user);
        $status .= '<br/><br/><img src="'.$self->response->user->photo.'"/>';
        $link    = '<a class="disconnect" href="?revoke=foursquare">'.T_('Disconnect').'</a>';
    }
    else
    {
        $status = '<span class="not_connected">'.T_('Not Connected').'</span>';
        $link   = '<a href="'.$fsObj->getAuthorizeUrl($callbackUrl).'">'.T_('Connect').'</a>';
    }

    echo '
        <div class="social-media-connect">
            <img class="icon" src="ui/images/foursquare.png" alt="Foursquare"/>
            <h2>Foursquare</h2>
            <p>'.T_('A location-based social networking website for your phone.').'</p>
            <div class="status">'.$status.'</div>
            <div class="action">'.$link.'</div>
        </div>';

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
    global $fcmsUser, $settingsObj;

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
            WHERE `user` = '$fcmsUser->id'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: settings.php?view=foursquare");
}

/**
 * displayRevokeFoursquareAccess 
 * 
 * @return void
 */
function displayRevokeFoursquareAccess ()
{
    global $fcmsUser;

    $sql = "UPDATE `fcms_user_settings`
            SET `fs_user_id` = NULL, `fs_access_token` = NULL
            WHERE `user` = '$fcmsUser->id'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: settings.php?view=foursquare");
}


/**
 * displayEditInstagram 
 * 
 * @return void
 */
function displayEditInstagram ()
{
    global $fcmsUser;

    displayHeader();

    $config = getInstagramConfigData();

    $callbackUrl  = getDomainAndDir();
    $callbackUrl .= 'settings.php?view=instagram';

    $accessToken = getUserInstagramAccessToken($fcmsUser->id);
    $instagram   = new Instagram($config['instagram_client_id'], $config['instagram_client_secret'], $accessToken);

    if (!$accessToken)
    {
        $url = $instagram->authorizeUrl($callbackUrl, array('basic', 'comments', 'likes', 'relationships'));

        $status = T_('Not Connected');
        $link   = '<a href="'.$url.'">'.T_('Connect').'</a>';
    }
    else
    {
        try
        {
            $feed = $instagram->get('users/self');
        }
        catch (InstagramApiError $e)
        {
            die($e->getMessage());
        }

        $status = sprintf(T_('Currently connected as: %s'), $feed->data->username);
        $status .= '<br/><br/><img src="'.$feed->data->profile_picture.'"/>';
        $link   = '<a class="disconnect" href="?revoke=instagram">'.T_('Disconnect').'</a>';
    }

    echo '
        <div class="social-media-connect">
            <img class="icon" src="ui/images/instagram.png" alt="Instagram"/>
            <h2>Instagram</h2>
            <p>'.T_('Instagram is a photo sharing app for your phone.').'</p>
            <div class="status">'.$status.'</div>
            <div class="action">'.$link.'</div>
        </div>';

    displayFooter();
}

/**
 * displayEditInstagramSubmit 
 * 
 * @return void
 */
function displayEditInstagramSubmit ()
{
    global $fcmsUser;

    $config = getInstagramConfigData();

    if (!empty($config['instagram_client_id']) && !empty($config['instagram_client_secret']))
    {
        $callbackUrl  = getDomainAndDir();
        $callbackUrl .= 'settings.php?view=instagram';

        $instagram = new Instagram($config['instagram_client_id'], $config['instagram_client_secret'], null);

        if (isset($_GET['error']) || isset($_GET['error_reason']) || isset($_GET['error_description']))
        {
            displayHeader();

            echo '
                <div class="error-alert">
                    <p>'.$_GET['error'].'</p>
                    <p>'.$_GET['error_reason'].'</p>
                    <p>'.$_GET['error_description'].'</p>
                </div>';

            displayFooter();

            return;
        }

        $response = $instagram->getAccessToken($_GET['code'], $callbackUrl);

        $accessToken = $response->access_token;

        $sql = "UPDATE `fcms_user_settings`
                SET `instagram_access_token` = '$accessToken'
                WHERE `user` = '$fcmsUser->id'";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }
    // Instagram isn't configured
    else
    {
        displayHeader();

        echo '
            <div class="info-alert">
                <h2>'.T_('Instagram isn\'t Configured Yet.').'</h2>
                <p>'.T_('Unfortunately, your website administrator has not set up Instagram yet.').'</p>
            </div>';

        displayFooter();
        return;
    }

    header("Location: settings.php?view=instagram");
}

/**
 * displayRevokeInstagramAccess 
 * 
 * @return void
 */
function displayRevokeInstagramAccess ()
{
    global $fcmsUser;

    $sql = "UPDATE `fcms_user_settings`
            SET `instagram_access_token` = NULL
            WHERE `user` = '$fcmsUser->id'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: settings.php?view=instagram");
}

/**
 * displayEditYouTube 
 * 
 * @return void
 */
function displayEditYouTube ()
{
    global $fcmsUser;

    displayHeader();

    $config = getYouTubeConfigData();
    $user   = getYouTubeUserData($fcmsUser->id);

    // Setup url for callbacks
    $callbackUrl  = getDomainAndDir();
    $callbackUrl .= 'settings.php?view=youtube';

    if (!empty($config['youtube_key']))
    {
        if (!empty($user['youtube_session_token']))
        {
            $httpClient = getAuthSubHttpClient($config['youtube_key'], $user['youtube_session_token']);

            $youTubeService = new Zend_Gdata_YouTube($httpClient);

            $feed = $youTubeService->getUserProfile('default');
            if (!$feed instanceof Zend_Gdata_YouTube_UserProfileEntry)
            {
                print '
            <div class="error-alert">'.T_('Could not get YouTube data for user.').'</div>';
                return;
            }

            $username = $feed->getUsername();

            $user    = '<a href="http://www.youtube.com/user/'.$username.'">'.$username.'</a>';
            $status  = sprintf(T_('Currently connected as: %s'), $user);
            $link    = '<a class="disconnect" href="?revoke=youtube">'.T_('Disconnect').'</a>';
        }
        else
        {
            $url = Zend_Gdata_AuthSub::getAuthSubTokenUri($callbackUrl, 'http://gdata.youtube.com', false, true);

            $status = T_('Not Connected');
            $link   = '<a href="'.$url.'">'.T_('Connect').'</a>';
        }
    }

    echo '
        <div class="social-media-connect">
            <img class="icon" src="ui/images/youtube.png" alt="YouTube"/>
            <h2>YouTube</h2>
            <p>'.T_('YouTube allows users to discover, watch and share videos.').'</p>
            <div class="status">'.$status.'</div>
            <div class="action">'.$link.'</div>
        </div>';

    displayFooter();
}

/**
 * displayEditYouTubeSubmit
 * 
 * @return void
 */
function displayEditYouTubeSubmit ()
{
    global $fcmsUser, $settingsObj;

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
                WHERE `user` = '$fcmsUser->id'";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
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

    header("Location: settings.php?view=youtube");
}

/**
 * displayRevokeYouTubeAccess 
 * 
 * @return void
 */
function displayRevokeYouTubeAccess ()
{
    global $fcmsUser;

    if (isset($_SESSION['sessionToken']))
    {
        unset($_SESSION['sessionToken']);
    }

    $sql = "UPDATE `fcms_user_settings`
            SET `youtube_session_token` = NULL
            WHERE `user` = '$fcmsUser->id'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: settings.php?view=youtube");
}

