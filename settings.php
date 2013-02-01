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

load('settings', 'foursquare', 'facebook', 'socialmedia', 'youtube', 'instagram', 'familynews', 'picasa');

init();

// Globals
$settings   = new Settings($fcmsError, $fcmsDatabase, $fcmsUser);
$page       = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $settings);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsSettings;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsSettings)
    {
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;
        $this->fcmsSettings     = $fcmsSettings;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => cleanOutput(getSiteName()),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Settings'),
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        $this->control();
    }

    /**
     * control 
     * 
     * The controlling structure for this script.
     * 
     * @return void
     */
    function control ()
    {
        if ($this->fcmsUser->access == 11)
        {
            $this->displayInvalidAccessLevel();
            return;
        }
        // Saving changes
        elseif (isset($_POST['submit']))
        {
            if ($_GET['view'] == 'account')
            {
                $this->displayEditAccountSubmit();
            }
            elseif ($_GET['view'] == 'settings')
            {
                $this->displayEditSettingsSubmit();
            }
            elseif ($_GET['view'] == 'notifications')
            {
                $this->displayEditNotificationsSubmit();
            }
            elseif ($_GET['view'] == 'familynews')
            {
                $this->displayEditFamilyNewsSubmit();
            }
            elseif ($_GET['view'] == 'messageboard')
            {
                $this->displayEditMessageBoardSubmit();
            }
        }
        // Theme
        elseif (isset($_GET['use']) && $_GET['view'] == 'theme')
        {
            $this->displayEditThemeSubmit();
        }
        elseif (isset($_GET['delete']) && $_GET['view'] == 'theme' && !isset($_GET['confirmed']))
        {
            $this->displayDeleteThemeConfirmation();
        }
        elseif (isset($_POST['delconfirm']) || (isset($_GET['delete']) && isset($_GET['confirmed'])))
        {
            $this->displayDeleteThemeSubmit();
        }
        // Import
        elseif (isset($_GET['import']) && isset($_GET['view']))
        {
            $this->displayImportBlogPosts();
        }
        // Edit
        elseif (isset($_GET['view']))
        {
            if ($_GET['view'] == 'account')
            {
                $this->displayEditAccount();
            }
            elseif ($_GET['view'] == 'theme')
            {
                $this->displayEditTheme();
            }
            elseif ($_GET['view'] == 'settings')
            {
                $this->displayEditSettings();
            }
            elseif ($_GET['view'] == 'notifications')
            {
                $this->displayEditNotifications();
            }
            elseif ($_GET['view'] == 'familynews')
            {
                $this->displayEditFamilyNews();
            }
            elseif ($_GET['view'] == 'messageboard')
            {
                $this->displayEditMessageBoard();
            }
            // Facebook
            elseif ($_GET['view'] == 'facebook')
            {
                if (isset($_GET['code']) && isset($_GET['state']))
                {
                    $this->displayEditFacebookSubmit();
                }
                else
                {
                    $this->displayEditFacebook();
                }
            }
            // Foursquare
            elseif ($_GET['view'] == 'foursquare')
            {
                if (isset($_GET['code']))
                {
                    $this->displayFoursquareSubmit();
                }
                else
                {
                    $this->displayEditFoursquare();
                }
            }
            // Instagram
            elseif ($_GET['view'] == 'instagram')
            {
                if (isset($_GET['code']))
                {
                    $this->displayEditInstagramSubmit();
                }
                else
                {
                    $this->displayEditInstagram();
                }
            }
            // YouTube
            elseif ($_GET['view'] == 'youtube')
            {
                if (isset($_GET['token']))
                {
                    $this->displayEditYouTubeSubmit();
                }
                else
                {
                    $this->displayEditYouTube();
                }
            }
            // Picasa
            elseif ($_GET['view'] == 'picasa')
            {
                if (isset($_GET['token']))
                {
                    $this->displayEditPicasaSubmit();
                }
                else
                {
                    $this->displayEditPicasa();
                }
            }
            else
            {
                $this->displayEditAccount();
            }
        }
        // Revoke app access
        elseif (isset($_GET['revoke']))
        {
            if ($_GET['revoke'] == 'facebook')
            {
                $this->displayRevokeFacebookAccess();
            }
            elseif ($_GET['revoke'] == 'foursquare')
            {
                $this->displayRevokeFoursquareAccess();
            }
            elseif ($_GET['revoke'] == 'instagram')
            {
                $this->displayRevokeInstagramAccess();
            }
            elseif ($_GET['revoke'] == 'youtube')
            {
                $this->displayRevokeYouTubeAccess();
            }
            elseif ($_GET['revoke'] == 'picasa')
            {
                $this->displayRevokePicasaAccess();
            }
        }
        else
        {
            $this->displayEditAccount();
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
        $TMPL = $this->fcmsTemplate;

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

        include_once getTheme($this->fcmsUser->id).'header.php';

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

        $picasaLink = '<li><a href="?view=picasa">Picasa</a></li>';

        $links = "$facebookLink$foursquareLink$instagramLink$youtubeLink$picasaLink";

        if (!empty($links))
        {
            echo '
                <h3>'.T_('Social Media').'</h3>
                <ul class="menu">
                    '.$facebookLink.'
                    '.$foursquareLink.'
                    '.$instagramLink.'
                    '.$youtubeLink.'
                    '.$picasaLink.'
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
        $TMPL = $this->fcmsTemplate;

        echo '
            </div>
            <div style="clear:both"></div>
        </div><!-- #settings .centercontent -->';

        include_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayEditAccount 
     * 
     * @return void
     */
    function displayEditAccount ()
    {
        $this->displayHeader();
        $this->fcmsSettings->displayAccountInformation();
        $this->displayFooter();

        return;
    }

    /**
     * displayEditAccountSubmit 
     * 
     * @return void
     */
    function displayEditAccountSubmit ()
    {
        $email      = strip_tags($_POST['email']);
        $emailStart = $this->fcmsUser->email;

        // Check email
        if ($_POST['email'] != $emailStart)
        {
            $sql = "SELECT `email`
                    FROM `fcms_users` 
                    WHERE email = ?";

            $row = $this->fcmsDatabase->getRow($sql, $email);
            if ($row === false)
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            if (!empty($row))
            {
                $this->displayHeader();

                echo '
            <p class="error-alert">
                '.sprintf(T_('The email address %s is already in use.  Please choose a different email.'), $email).'
            </p>';

                $this->fcmsSettings->displayAccountInformation();
                $this->displayFooter();

                return;
            }
        }

        $sql    = "UPDATE `fcms_users` SET ";
        $params = array();

        if (isset($_POST['pass']) && !empty($_POST['pass']))
        {
            $sql      .= "password = ?, ";
            $params[]  = md5($_POST['pass']);

            $orig_pass            = $_SESSION['login_pw'];
            $_SESSION['login_pw'] = md5($_POST['pass']);
        }

        $sql .= "`email` = ?
                WHERE id = ?";

        $params[] = $email;
        $params[] = $this->fcmsUser->id;

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

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

        $this->displayHeader();
        displayOkMessage();
        $this->fcmsSettings->displayAccountInformation();
        $this->displayFooter();
    }

    /**
     * displayEditTheme 
     * 
     * @return void
     */
    function displayEditTheme ()
    {
        $js = '
<script type="text/javascript">
Event.observe(window, \'load\', function() {
    deleteConfirmationLinks("del_theme", "'.T_('Are you sure you want to DELETE this theme?').'");
});
</script>';

        $this->displayHeader($js);
        $this->fcmsSettings->displayTheme();
        $this->displayFooter();

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
        $theme = basename($_GET['use']);

        $sql = "UPDATE `fcms_user_settings`
                SET `theme` = ?
                WHERE `user` = ?";

        $params = array($theme, $this->fcmsUser->id);

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $this->displayHeader();
        displayOkMessage();

        $this->fcmsSettings->displayTheme();

        $this->displayFooter();
    }

    /**
     * displayDeleteThemeSubmit 
     * 
     * @return void
     */
    function displayDeleteThemeSubmit ()
    {
        $this->displayHeader();

        $theme = basename($_GET['delete']);

        if (!file_exists(THEMES.$theme))
        {
            echo '
                <p class="error-alert">'.sprintf(T_('Theme [%s] not found.'), $theme).'</p>';
            $this->fcmsSettings->displayTheme();
            $this->displayFooter();
            return;
        }

        if (!is_dir(THEMES.$theme))
        {
            echo '
                <p class="error-alert">'.sprintf(T_('[%s] is not a directory.'), $theme).'</p>';
            $this->fcmsSettings->displayTheme();
            $this->displayFooter();
            return;
        }

        if (!deleteDirectory(THEMES.$theme))
        {
            echo '
                <p class="error-alert">'.sprintf(T_('Could not delete theme [%s].'), $theme).'</p>';
            $this->fcmsSettings->displayTheme();
            $this->displayFooter();
            return;
        }

        displayOkMessage();
        $this->fcmsSettings->displayTheme();
        $this->displayFooter();
    }

    /**
     * displayEditSettings 
     * 
     * @return void
     */
    function displayEditSettings ()
    {
        $this->displayHeader();
        $this->fcmsSettings->displaySettings();
        $this->displayFooter();

        return;
    }

    /**
     * displayEditSettingsSubmit 
     * 
     * @return void
     */
    function displayEditSettingsSubmit ()
    {
        $this->displayHeader();

        $sql = "UPDATE `fcms_user_settings` SET ";

        $params = array();

        if ($_POST['advanced_upload'])
        {
            $sql     .= "`advanced_upload` = ?, ";
            $params[] = $_POST['advanced_upload'] == 'yes' ? 1 : 0;
        }
        if ($_POST['advanced_tagging'])
        {
            $sql     .= "`advanced_tagging` = ?, ";
            $params[] = $_POST['advanced_tagging'] == 'yes' ? 1 : 0;
        }
        if ($_POST['language'])
        {
            $sql     .= "`language` = ?, ";
            $params[] = $_POST['language'];
        }
        if ($_POST['timezone'])
        {
            $sql     .= "`timezone` = ?, ";
            $params[] = $_POST['timezone'];
        }
        if ($_POST['dst'])
        {
            $sql     .= "`dst` = ?, ";
            $params[] = $_POST['dst'] == 'on' ? 1 : 0;
        }
        if ($_POST['displayname'])
        {
            $sql     .= "`displayname` = ?, ";
            $params[] = $_POST['displayname'];
        }
        if ($_POST['frontpage'])
        {
            $sql     .= "`frontpage` = ?, ";
            $params[] = $_POST['frontpage'];
        }

        $sql  = substr($sql, 0, -2); // remove the extra comma space at the end
        $sql .= " WHERE `user` = ?";

        $params[] = $this->fcmsUser->id;

        if (strlen($sql) > 50)
        {
            if (!$this->fcmsDatabase->update($sql, $params))
            {
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            displayOkMessage();
        }

        $this->fcmsSettings->displaySettings();
        $this->displayFooter();
    }

    /**
     * displayEditNotifications 
     * 
     * @return void
     */
    function displayEditNotifications ()
    {
        $this->displayHeader();
        $this->fcmsSettings->displayNotifications();
        $this->displayFooter();

        return;
    }

    /**
     * displayEditNotificationsSubmit 
     * 
     * @return void
     */
    function displayEditNotificationsSubmit ()
    {
        $this->displayHeader();

        $params = array();

        if ($_POST['email_updates'])
        {
            $params[] = $_POST['email_updates'] == 'yes' ? 1 : 0;
            $params[] = $this->fcmsUser->id;

            $sql = "UPDATE `fcms_user_settings`
                    SET `email_updates` = ?
                    WHERE `user` = ?";

            if (!$this->fcmsDatabase->update($sql, $params))
            {
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            displayOkMessage();
        }

        $this->fcmsSettings->displayNotifications();
        $this->displayFooter();
    }

    /**
     * displayEditFamilyNews 
     * 
     * @return void
     */
    function displayEditFamilyNews ()
    {
        $this->displayHeader();
        $this->fcmsSettings->displayFamilyNews();
        $this->displayFooter();

        return;
    }

    /**
     * displayEditFamilyNewsSubmit 
     * 
     * @return void
     */
    function displayEditFamilyNewsSubmit ()
    {
        $this->displayHeader();

        $params = array();

        $params[] = isset($_POST['blogger'])   ? $_POST['blogger']   : '';
        $params[] = isset($_POST['tumblr'])    ? $_POST['tumblr']    : '';
        $params[] = isset($_POST['wordpress']) ? $_POST['wordpress'] : '';
        $params[] = isset($_POST['posterous']) ? $_POST['posterous'] : '';
        $params[] = $this->fcmsUser->id;

        $sql = "UPDATE `fcms_user_settings`
                SET `blogger` = ?,
                    `tumblr` = ?,
                    `wordpress` = ?,
                    `posterous` = ?
                WHERE `user` = ?";

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        displayOkMessage();

        $this->fcmsSettings->displayFamilyNews();
        $this->displayFooter();
    }

    /**
     * displayEditMessageBoard 
     * 
     * @return void
     */
    function displayEditMessageBoard ()
    {
        $this->displayHeader();
        $this->fcmsSettings->displayMessageBoard();
        $this->displayFooter();

        return;
    }

    /**
     * displayEditMessageBoardSubmit
     * 
     * @return void
     */
    function displayEditMessageBoardSubmit ()
    {
        $this->displayHeader();


        if (isset($_POST['boardsort']))
        {
            $params = array(
                $_POST['boardsort'],
                $this->fcmsUser->id
            );

            $sql = "UPDATE `fcms_user_settings`
                    SET `boardsort` = ?
                    WHERE `user` = ?";

            if (!$this->fcmsDatabase->update($sql, $params))
            {
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            displayOkMessage();
        }

        $this->fcmsSettings->displayMessageBoard();
        $this->displayFooter();
    }

    /**
     * displayImportBlogPosts 
     * 
     * @return void
     */
    function displayImportBlogPosts ()
    {
        $this->displayHeader();

        // setup familynew obj
        $newsObj = new FamilyNews($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser);

        // get external ids
        $external_ids = $newsObj->getExternalPostIds();

        // Get import blog settings
        $sql = "SELECT `user`, `blogger`, `tumblr`, `wordpress`, `posterous`
                FROM `fcms_user_settings`
                WHERE `user` = ?";

        $r = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($r === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (empty($r))
        {
            echo '<div class="error-alert">'.T_('Nothing to import.').'</div>';
            $this->fcmsSettings->displayFamilyNews();
            $this->displayFooter();

            return;
        }

        $count = 0;

        switch ($_GET['import'])
        {
            case 'blogger':
                $count = $newsObj->importBloggerPosts($r['blogger'], $this->fcmsUser->id, '', $external_ids);
                if ($count === false)
                {
                    $this->fcmsSettings->displayFamilyNews();
                    $this->displayFooter();
                    return;
                }
                break;

            case 'tumblr':
                $count = $newsObj->importTumblrPosts($r['tumblr'], $this->fcmsUser->id, '', $external_ids);
                if ($count === false)
                {
                    $this->fcmsSettings->displayFamilyNews();
                    $this->displayFooter();
                    return;
                }
                break;

            case 'wordpress':
                $count = $newsObj->importWordpressPosts($r['wordpress'], $this->fcmsUser->id, '', $external_ids);
                if ($count === false)
                {
                    $this->fcmsSettings->displayFamilyNews();
                    $this->displayFooter();
                    return;
                }
                break;

            case 'posterous':
                $count = $newsObj->importPosterousPosts($r['posterous'], $this->fcmsUser->id, '', $external_ids);
                if ($count === false)
                {
                    $this->fcmsSettings->displayFamilyNews();
                    $this->displayFooter();
                    return;
                }
                break;
        }

        displayOkMessage(sprintf(T_ngettext('%d post has been imported.', '%d posts have been imported.', $count), $count));
        $this->fcmsSettings->displayFamilyNews();
        $this->displayFooter();

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

        $this->displayHeader();

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

        $this->displayFooter();
    }

    /**
     * displayInvalidAccessLevel 
     * 
     * @return void
     */
    function displayInvalidAccessLevel ()
    {
        $this->displayHeader();

        echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                <a href="contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

        $this->displayFooter();
    }

    /**
     * displayEditFacebook 
     * 
     * @return void
     */
    function displayEditFacebook ()
    {
        $this->displayHeader();

        $config      = getFacebookConfigData();
        $accessToken = getUserFacebookAccessToken($this->fcmsUser->id);

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

        $this->displayFooter();
    }

    /**
     * displayEditFacebookSubmit 
     * 
     * @return void
     */
    function displayEditFacebookSubmit ()
    {
        $data = getFacebookConfigData();

        if (!empty($data['fb_app_id']) && !empty($data['fb_secret']))
        {
            $facebook = new Facebook(array(
              'appId'  => $data['fb_app_id'],
              'secret' => $data['fb_secret'],
            ));

            $accessToken = $facebook->getAccessToken();

            $sql = "UPDATE `fcms_user_settings`
                    SET `fb_access_token` = ?
                    WHERE `user` = ?";

            $params = array(
                $accessToken,
                $this->fcmsUser->id
            );

            if (!$this->fcmsDatabase->update($sql, $params))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        // Facebook isn't configured
        else
        {
            $this->displayHeader();

            echo '
            <div class="info-alert">
                <h2>'.T_('Facebook isn\'t Configured Yet.').'</h2>
                <p>'.T_('Unfortunately, your website administrator has not set up Facebook yet.').'</p>
            </div>';

            $this->displayFooter();
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
        $sql = "UPDATE `fcms_user_settings`
                SET `fb_access_token` = NULL
                WHERE `user` = ?";

        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
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
        $this->displayHeader();

        $config = getFoursquareConfigData();
        $user   = getFoursquareUserData($this->fcmsUser->id);

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

        $this->displayFooter();
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
        $r = getFoursquareConfigData();

        $id     = cleanOutput($r['fs_client_id']);
        $secret = cleanOutput($r['fs_client_secret']);
        $url    = cleanOutput($r['fs_callback_url']);

        $fsObj = new EpiFoursquare($id, $secret);
        $token = $fsObj->getAccessToken($_GET['code'], $url);

        $fsObjAuth = new EpiFoursquare($id, $secret, $token->access_token);
        $self      = $fsObjAuth->get('/users/self');

        $sql = "UPDATE `fcms_user_settings`
                SET `fs_user_id` = ?,
                    `fs_access_token` = ?
                WHERE `user` = ?";

        $params = array(
            $self->response->user->id,
            $token->access_token,
            $this->fcmsUser->id
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
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
        $sql = "UPDATE `fcms_user_settings`
                SET `fs_user_id` = NULL, `fs_access_token` = NULL
                WHERE `user` = ?";

        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
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
        $this->displayHeader();

        $config = getInstagramConfigData();

        $callbackUrl  = getDomainAndDir();
        $callbackUrl .= 'settings.php?view=instagram';

        $accessToken = getUserInstagramAccessToken($this->fcmsUser->id);
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

        $this->displayFooter();
    }

    /**
     * displayEditInstagramSubmit 
     * 
     * @return void
     */
    function displayEditInstagramSubmit ()
    {
        $config = getInstagramConfigData();

        if (!empty($config['instagram_client_id']) && !empty($config['instagram_client_secret']))
        {
            $callbackUrl  = getDomainAndDir();
            $callbackUrl .= 'settings.php?view=instagram';

            $instagram = new Instagram($config['instagram_client_id'], $config['instagram_client_secret'], null);

            if (isset($_GET['error']) || isset($_GET['error_reason']) || isset($_GET['error_description']))
            {
                $this->displayHeader();

                echo '
                <div class="error-alert">
                    <p>'.$_GET['error'].'</p>
                    <p>'.$_GET['error_reason'].'</p>
                    <p>'.$_GET['error_description'].'</p>
                </div>';

                $this->displayFooter();

                return;
            }

            $response = $instagram->getAccessToken($_GET['code'], $callbackUrl);

            $accessToken = $response->access_token;

            $sql = "UPDATE `fcms_user_settings`
                    SET `instagram_access_token` = ?
                    WHERE `user` = ?";

            $params = array(
                $accessToken,
                $this->fcmsUser->id
            );

            if (!$this->fcmsDatabase->update($sql, $params))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }
        // Instagram isn't configured
        else
        {
            $this->displayHeader();

            echo '
            <div class="info-alert">
                <h2>'.T_('Instagram isn\'t Configured Yet.').'</h2>
                <p>'.T_('Unfortunately, your website administrator has not set up Instagram yet.').'</p>
            </div>';

            $this->displayFooter();
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
        $sql = "UPDATE `fcms_user_settings`
                SET `instagram_access_token` = NULL
                WHERE `user` = ?";

        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
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
        $this->displayHeader();

        $config = getYouTubeConfigData();
        $user   = getYouTubeUserData($this->fcmsUser->id);

        // Setup url for callbacks
        $callbackUrl  = getDomainAndDir();
        $callbackUrl .= 'settings.php?view=youtube';

        if (!empty($config['youtube_key']))
        {
            if (!empty($user['youtube_session_token']))
            {
                $httpClient = getYouTubeAuthSubHttpClient($config['youtube_key'], $user['youtube_session_token']);

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

        $this->displayFooter();
    }

    /**
     * displayEditYouTubeSubmit
     * 
     * @return void
     */
    function displayEditYouTubeSubmit ()
    {
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
                $this->displayHeader();
                echo '
            <div class="error-alert">ERROR - Token upgrade for ['.$singleUseToken.'] failed: '.$e->getMessage();
                $this->displayFooter();
                return;
            }

            $_SESSION['sessionToken'] = $sessionToken;

            $sql = "UPDATE `fcms_user_settings`
                    SET `youtube_session_token` = ?
                    WHERE `user` = ?";

            $params = array(
                $sessionToken,
                $this->fcmsUser->id
            );

            if (!$this->fcmsDatabase->update($sql, $params))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        // YouTube isn't configured
        else
        {
            $this->displayHeader();

            echo '
            <div class="info-alert">
                <h2>'.T_('YouTube isn\'t Configured Yet.').'</h2>
                <p>'.T_('Unfortunately, your website administrator has not set up YouTube yet.').'</p>
            </div>';

            $this->displayFooter();
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
        if (isset($_SESSION['sessionToken']))
        {
            unset($_SESSION['sessionToken']);
        }

        $sql = "UPDATE `fcms_user_settings`
                SET `youtube_session_token` = NULL
                WHERE `user` = ?";

        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        header("Location: settings.php?view=youtube");
    }

    /**
     * displayEditPicasa
     * 
     * @return void
     */
    function displayEditPicasa ()
    {
        $this->displayHeader();

        $token = getUserPicasaSessionToken($this->fcmsUser->id);

        // Setup url for callbacks
        $callbackUrl  = getDomainAndDir();
        $callbackUrl .= 'settings.php?view=picasa';

        if (!is_null($token))
        {
            $httpClient = Zend_Gdata_AuthSub::getHttpClient($token);

            $picasaService = new Zend_Gdata_Photos($httpClient, "Google-DevelopersGuide-1.0");

            try
            {
                $feed = $picasaService->getUserFeed("default");
            }
            catch (Zend_Gdata_App_Exception $e)
            {
                print '<div class="error-alert">'.T_('Could not get Picasa session token.').'</div>';
                return;
            }

            $username = $feed->getTitle();

            $user    = '<a href="http://picasaweb.google.com/'.$username.'">'.$username.'</a>';
            $status  = sprintf(T_('Currently connected as: %s'), $user);
            $link    = '<a class="disconnect" href="?revoke=picasa">'.T_('Disconnect').'</a>';
        }
        else
        {
            $url = Zend_Gdata_AuthSub::getAuthSubTokenUri($callbackUrl, 'https://picasaweb.google.com/data', false, true);

            $status = T_('Not Connected');
            $link   = '<a href="'.$url.'">'.T_('Connect').'</a>';
        }

        echo '
        <div class="social-media-connect">
            <img class="icon" src="ui/images/picasa.png" alt="Picasa"/>
            <h2>Picasa Web</h2>
            <p>'.T_('Picasa Web allows users to share photos with friends and family.').'</p>
            <div class="status">'.$status.'</div>
            <div class="action">'.$link.'</div>
        </div>';

        $this->displayFooter();
    }

    /**
     * displayEditPicasaSubmit
     * 
     * @return void
     */
    function displayEditPicasaSubmit ()
    {
        $singleUseToken = $_GET['token'];

        // Exchange single use token for a session token
        try
        {
            $sessionToken = Zend_Gdata_AuthSub::getAuthSubSessionToken($singleUseToken);
        }
        catch (Zend_Gdata_App_Exception $e)
        {
            $this->displayHeader();
            echo '<div class="error-alert">ERROR - Token upgrade for ['.$singleUseToken.'] failed: '.$e->getMessage();
            $this->displayFooter();
            return;
        }

        $sql = "UPDATE `fcms_user_settings`
                SET `picasa_session_token` = ?
                WHERE `user` = ?";

        $params = array(
            $sessionToken,
            $this->fcmsUser->id
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        header("Location: settings.php?view=picasa");
    }

    /**
     * displayRevokePicasaAccess 
     * 
     * @return void
     */
    function displayRevokePicasaAccess ()
    {
        if (isset($_SESSION['sessionToken']))
        {
            unset($_SESSION['sessionToken']);
        }

        $sql = "UPDATE `fcms_user_settings`
                SET `picasa_session_token` = NULL
                WHERE `user` = ?";

        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        header("Location: settings.php?view=picasa");
    }
}
