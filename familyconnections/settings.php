<?php
/**
 * Settings.
 *
 * PHP version 5
 *
 * @category  FCMS
 *
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load(
    'settings',
    'foursquare',
    'facebook',
    'socialmedia',
    'instagram',
    'familynews',
    'phpass',
    'google'
);

init();

// Globals
$settings = new Settings($fcmsError, $fcmsDatabase, $fcmsUser);
$page = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $settings);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsSettings;
    private $fcmsTemplate;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsSettings)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser = $fcmsUser;
        $this->fcmsSettings = $fcmsSettings;

        $this->control();
    }

    /**
     * control.
     *
     * The controlling structure for this script.
     *
     * @return void
     */
    public function control()
    {
        if ($this->fcmsUser->access == 11) {
            $this->displayInvalidAccessLevel();

            return;
        }
        // Saving changes
        elseif (isset($_POST['submit'])) {
            if ($_GET['view'] == 'account') {
                $this->displayEditAccountSubmit();
            } elseif ($_GET['view'] == 'settings') {
                $this->displayEditSettingsSubmit();
            } elseif ($_GET['view'] == 'notifications') {
                $this->displayEditNotificationsSubmit();
            } elseif ($_GET['view'] == 'photogallery') {
                $this->displayEditPhotoGallerySubmit();
            } elseif ($_GET['view'] == 'familynews') {
                $this->displayEditFamilyNewsSubmit();
            } elseif ($_GET['view'] == 'messageboard') {
                $this->displayEditMessageBoardSubmit();
            }
        }
        // Theme
        elseif (isset($_GET['use']) && $_GET['view'] == 'theme') {
            $this->displayEditThemeSubmit();
        } elseif (isset($_GET['delete']) && $_GET['view'] == 'theme' && !isset($_GET['confirmed'])) {
            $this->displayDeleteThemeConfirmation();
        } elseif (isset($_POST['delconfirm']) || (isset($_GET['delete']) && isset($_GET['confirmed']))) {
            $this->displayDeleteThemeSubmit();
        }
        // Import
        elseif (isset($_GET['import']) && isset($_GET['view'])) {
            $this->displayImportBlogPosts();
        }
        // Edit
        elseif (isset($_GET['view'])) {
            if ($_GET['view'] == 'account') {
                $this->displayEditAccount();
            } elseif ($_GET['view'] == 'theme') {
                $this->displayEditTheme();
            } elseif ($_GET['view'] == 'settings') {
                $this->displayEditSettings();
            } elseif ($_GET['view'] == 'notifications') {
                $this->displayEditNotifications();
            } elseif ($_GET['view'] == 'photogallery') {
                $this->displayEditPhotoGallery();
            } elseif ($_GET['view'] == 'familynews') {
                $this->displayEditFamilyNews();
            } elseif ($_GET['view'] == 'messageboard') {
                $this->displayEditMessageBoard();
            }
            // Facebook
            elseif ($_GET['view'] == 'facebook') {
                if (isset($_GET['code']) && isset($_GET['state'])) {
                    $this->displayEditFacebookSubmit();
                } else {
                    $this->displayEditFacebook();
                }
            }
            // Foursquare
            elseif ($_GET['view'] == 'foursquare') {
                if (isset($_GET['code'])) {
                    $this->displayFoursquareSubmit();
                } else {
                    $this->displayEditFoursquare();
                }
            }
            // Instagram
            elseif ($_GET['view'] == 'instagram') {
                if (isset($_GET['code'])) {
                    $this->displayEditInstagramSubmit();
                } else {
                    $this->displayEditInstagram();
                }
            }
            // Google
            elseif ($_GET['view'] == 'google') {
                if (isset($_GET['oauth2callback'])) {
                    $this->displayEditGoogleSubmit();
                } else {
                    $this->displayEditGoogle();
                }
            } else {
                $this->displayEditAccount();
            }
        }
        // Revoke app access
        elseif (isset($_GET['revoke'])) {
            if ($_GET['revoke'] == 'facebook') {
                $this->displayRevokeFacebookAccess();
            } elseif ($_GET['revoke'] == 'foursquare') {
                $this->displayRevokeFoursquareAccess();
            } elseif ($_GET['revoke'] == 'instagram') {
                $this->displayRevokeInstagramAccess();
            } elseif ($_GET['revoke'] == 'google') {
                $this->displayRevokeGoogleAccess();
            }
        } else {
            $this->displayEditAccount();
        }
    }

    /**
     * displayHeader.
     *
     * Displays the header of the page, including the leftcolumn navigation.
     *
     * @param array $options
     *
     * @return void
     */
    public function displayHeader($options = null)
    {
        $params = [
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => cleanOutput(getSiteName()),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Settings'),
            'pageId'        => 'settings',
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y'),
        ];

        displayPageHeader($params, $options);

        echo '
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
                    <li><a href="?view=photogallery">'.T_('Photo Gallery').'</a></li>
                    <li><a href="?view=familynews">'.T_('Family News').'</a></li>
                    <li><a href="?view=messageboard">'.T_('Message Board').'</a></li>
                </ul>';

        $facebookConfig = getFacebookConfigData();
        $foursquareConfig = getFoursquareConfigData();
        $instagramConfig = getInstagramConfigData();
        $googleConfig = getGoogleConfigData();

        $facebookLink = '';
        $foursquareLink = '';
        $instagramLink = '';
        $googleLink = '';

        if (!empty($facebookConfig['fb_app_id']) && !empty($facebookConfig['fb_secret'])) {
            $facebookLink = '<li><a href="?view=facebook">Facebook</a></li>';
        }

        if (!empty($foursquareConfig['fs_client_id']) && !empty($foursquareConfig['fs_client_secret'])) {
            $foursquareLink = '<li><a href="?view=foursquare">Foursquare</a></li>';
        }

        if (!empty($instagramConfig['instagram_client_id']) && !empty($instagramConfig['instagram_client_secret'])) {
            $instagramLink = '<li><a href="?view=instagram">Instagram</a></li>';
        }

        if (!empty($googleConfig['google_client_id']) && !empty($googleConfig['google_client_secret'])) {
            $googleLink = '<li><a href="?view=google">Google</a></li>';
        }

        $links = "$facebookLink$foursquareLink$instagramLink$googleLink";

        if (!empty($links)) {
            echo '
                <h3>'.T_('Social Media').'</h3>
                <ul class="menu">
                    '.$facebookLink.'
                    '.$foursquareLink.'
                    '.$instagramLink.'
                    '.$googleLink.'
                </ul>';
        }

        echo '
            </div>

            <div id="maincolumn">';
    }

    /**
     * displayFooter.
     *
     * @return void
     */
    public function displayFooter()
    {
        $params = [
            'path'    => URL_PREFIX,
            'version' => getCurrentVersion(),
            'year'    => date('Y'),
        ];

        echo '
            </div><!--/#maincolumn-->
            <div style="clear:both"></div>';

        loadTemplate('global', 'footer', $params);
    }

    /**
     * displayEditAccount.
     *
     * @return void
     */
    public function displayEditAccount()
    {
        $this->displayHeader();
        $this->fcmsSettings->displayAccountInformation();
        $this->displayFooter();
    }

    /**
     * displayEditAccountSubmit.
     *
     * @return void
     */
    public function displayEditAccountSubmit()
    {
        $email = strip_tags($_POST['email']);
        $emailStart = $this->fcmsUser->email;

        // Check email
        if ($_POST['email'] != $emailStart) {
            $sql = 'SELECT `email`
                    FROM `fcms_users` 
                    WHERE email = ?';

            $row = $this->fcmsDatabase->getRow($sql, $email);
            if ($row === false) {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            if (!empty($row)) {
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

        $sql = 'UPDATE `fcms_users` SET ';
        $params = [];

        if (isset($_POST['pass']) && !empty($_POST['pass'])) {
            $sql .= 'phpass = ?, ';

            $hasher = new PasswordHash(8, false);
            $params[] = $hasher->HashPassword($_POST['pass']);

            $orig_pass = 1;
        }

        $sql .= '`email` = ?
                WHERE id = ?';

        $params[] = $email;
        $params[] = $this->fcmsUser->id;

        if (!$this->fcmsDatabase->update($sql, $params)) {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (isset($orig_pass)) {
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
     * displayEditTheme.
     *
     * @return void
     */
    public function displayEditTheme()
    {
        $this->displayHeader(
            [
                'jsOnload' => 'deleteConfirmationLinks("del_theme", "'.T_('Are you sure you want to DELETE this theme?').'");',
            ]
        );
        $this->fcmsSettings->displayTheme();
        $this->displayFooter();
    }

    /**
     * displayEditThemeSubmit.
     *
     * Changes the theme.
     *
     * @return void
     */
    public function displayEditThemeSubmit()
    {
        $theme = basename($_GET['use']);

        $sql = 'UPDATE `fcms_user_settings`
                SET `theme` = ?
                WHERE `user` = ?';

        $params = [$theme, $this->fcmsUser->id];

        if (!$this->fcmsDatabase->update($sql, $params)) {
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
     * displayDeleteThemeSubmit.
     *
     * @return void
     */
    public function displayDeleteThemeSubmit()
    {
        $this->displayHeader();

        $theme = basename($_GET['delete']);

        if (!file_exists(THEMES.$theme)) {
            echo '
                <p class="error-alert">'.sprintf(T_('Theme [%s] not found.'), $theme).'</p>';
            $this->fcmsSettings->displayTheme();
            $this->displayFooter();

            return;
        }

        if (!is_dir(THEMES.$theme)) {
            echo '
                <p class="error-alert">'.sprintf(T_('[%s] is not a directory.'), $theme).'</p>';
            $this->fcmsSettings->displayTheme();
            $this->displayFooter();

            return;
        }

        if (!deleteDirectory(THEMES.$theme)) {
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
     * displayEditSettings.
     *
     * @return void
     */
    public function displayEditSettings()
    {
        $this->displayHeader();

        if (isset($_SESSION['success'])) {
            displayOkMessage();
            unset($_SESSION['success']);
        }

        $this->fcmsSettings->displaySettings();
        $this->displayFooter();
    }

    /**
     * displayEditSettingsSubmit.
     *
     * @return void
     */
    public function displayEditSettingsSubmit()
    {
        $sql = 'UPDATE `fcms_user_settings` SET ';

        $params = [];

        if ($_POST['language']) {
            $sql .= '`language` = ?, ';
            $params[] = $_POST['language'];
        }
        if ($_POST['timezone']) {
            $sql .= '`timezone` = ?, ';
            $params[] = $_POST['timezone'];
        }
        if ($_POST['dst']) {
            $sql .= '`dst` = ?, ';
            $params[] = $_POST['dst'] == 'on' ? 1 : 0;
        }
        if ($_POST['displayname']) {
            $sql .= '`displayname` = ?, ';
            $params[] = $_POST['displayname'];
        }
        if ($_POST['frontpage']) {
            $sql .= '`frontpage` = ?, ';
            $params[] = $_POST['frontpage'];
        }

        $sql = substr($sql, 0, -2); // remove the extra comma space at the end
        $sql .= ' WHERE `user` = ?';

        $params[] = $this->fcmsUser->id;

        if (strlen($sql) > 50) {
            if (!$this->fcmsDatabase->update($sql, $params)) {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }
        }

        $_SESSION['success'] = 1;

        header('Location: settings.php?view=settings');
    }

    /**
     * displayEditNotifications.
     *
     * @return void
     */
    public function displayEditNotifications()
    {
        $this->displayHeader();
        $this->fcmsSettings->displayNotifications();
        $this->displayFooter();
    }

    /**
     * displayEditNotificationsSubmit.
     *
     * @return void
     */
    public function displayEditNotificationsSubmit()
    {
        $this->displayHeader();

        $params = [];

        if ($_POST['email_updates']) {
            $params[] = $_POST['email_updates'] == 'yes' ? 1 : 0;
            $params[] = $this->fcmsUser->id;

            $sql = 'UPDATE `fcms_user_settings`
                    SET `email_updates` = ?
                    WHERE `user` = ?';

            if (!$this->fcmsDatabase->update($sql, $params)) {
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
     * displayEditPhotoGallery.
     *
     * @return void
     */
    public function displayEditPhotoGallery()
    {
        $this->displayHeader(['jsOnload' => 'initAdvancedTagging();']);
        $this->fcmsSettings->displayPhotoGallerySettings();
        $this->displayFooter();
    }

    /**
     * displayEditPhotoGallerySubmit.
     *
     * @return void
     */
    public function displayEditPhotoGallerySubmit()
    {
        $this->displayHeader();

        $sql = 'UPDATE `fcms_user_settings` 
                SET `uploader` = ?,
                    `advanced_tagging` = ?
                WHERE `user` = ?';

        $advancedTagging = $_POST['advanced_tagging'] == 'yes' ? 1 : 0;

        $params = [
            $_POST['uploader'],
            $advancedTagging,
            $this->fcmsUser->id,
        ];

        if (!$this->fcmsDatabase->update($sql, $params)) {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        displayOkMessage();

        // We may need to reset the fcms_uploader_type
        $uploaderTypesThatNeedUpdated = [
            'plupload' => 1,
            'java'     => 1,
            'basic'    => 1,
        ];
        if (isset($_SESSION['fcms_uploader_type']) && isset($uploaderTypesThatNeedUpdated[$_SESSION['fcms_uploader_type']])) {
            unset($_SESSION['fcms_uploader_type']);
        }

        $this->fcmsSettings->displayPhotoGallerySettings();
        $this->displayFooter();
    }

    /**
     * displayEditFamilyNews.
     *
     * @return void
     */
    public function displayEditFamilyNews()
    {
        $this->displayHeader();
        $this->fcmsSettings->displayFamilyNews();
        $this->displayFooter();
    }

    /**
     * displayEditFamilyNewsSubmit.
     *
     * @return void
     */
    public function displayEditFamilyNewsSubmit()
    {
        $this->displayHeader();

        $params = [];

        $params[] = isset($_POST['blogger']) ? $_POST['blogger'] : '';
        $params[] = isset($_POST['tumblr']) ? $_POST['tumblr'] : '';
        $params[] = isset($_POST['wordpress']) ? $_POST['wordpress'] : '';
        $params[] = isset($_POST['posterous']) ? $_POST['posterous'] : '';
        $params[] = $this->fcmsUser->id;

        $sql = 'UPDATE `fcms_user_settings`
                SET `blogger` = ?,
                    `tumblr` = ?,
                    `wordpress` = ?,
                    `posterous` = ?
                WHERE `user` = ?';

        if (!$this->fcmsDatabase->update($sql, $params)) {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        displayOkMessage();

        $this->fcmsSettings->displayFamilyNews();
        $this->displayFooter();
    }

    /**
     * displayEditMessageBoard.
     *
     * @return void
     */
    public function displayEditMessageBoard()
    {
        $this->displayHeader();
        $this->fcmsSettings->displayMessageBoard();
        $this->displayFooter();
    }

    /**
     * displayEditMessageBoardSubmit.
     *
     * @return void
     */
    public function displayEditMessageBoardSubmit()
    {
        $this->displayHeader();

        if (isset($_POST['boardsort'])) {
            $params = [
                $_POST['boardsort'],
                $this->fcmsUser->id,
            ];

            $sql = 'UPDATE `fcms_user_settings`
                    SET `boardsort` = ?
                    WHERE `user` = ?';

            if (!$this->fcmsDatabase->update($sql, $params)) {
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
     * displayImportBlogPosts.
     *
     * @return void
     */
    public function displayImportBlogPosts()
    {
        $this->displayHeader();

        // setup familynew obj
        $newsObj = new FamilyNews($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser);

        // get external ids
        $external_ids = $newsObj->getExternalPostIds();

        // Get import blog settings
        $sql = 'SELECT `user`, `blogger`, `tumblr`, `wordpress`, `posterous`
                FROM `fcms_user_settings`
                WHERE `user` = ?';

        $r = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($r === false) {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (empty($r)) {
            echo '<div class="error-alert">'.T_('Nothing to import.').'</div>';
            $this->fcmsSettings->displayFamilyNews();
            $this->displayFooter();

            return;
        }

        $count = 0;

        switch ($_GET['import']) {
            case 'blogger':
                $count = $newsObj->importBloggerPosts($r['blogger'], $this->fcmsUser->id, '', $external_ids);
                if ($count === false) {
                    $this->fcmsSettings->displayFamilyNews();
                    $this->displayFooter();

                    return;
                }
                break;

            case 'tumblr':
                $count = $newsObj->importTumblrPosts($r['tumblr'], $this->fcmsUser->id, '', $external_ids);
                if ($count === false) {
                    $this->fcmsSettings->displayFamilyNews();
                    $this->displayFooter();

                    return;
                }
                break;

            case 'wordpress':
                $count = $newsObj->importWordpressPosts($r['wordpress'], $this->fcmsUser->id, '', $external_ids);
                if ($count === false) {
                    $this->fcmsSettings->displayFamilyNews();
                    $this->displayFooter();

                    return;
                }
                break;

            case 'posterous':
                $count = $newsObj->importPosterousPosts($r['posterous'], $this->fcmsUser->id, '', $external_ids);
                if ($count === false) {
                    $this->fcmsSettings->displayFamilyNews();
                    $this->displayFooter();

                    return;
                }
                break;
        }

        displayOkMessage(sprintf(T_ngettext('%d post has been imported.', '%d posts have been imported.', $count), $count));
        $this->fcmsSettings->displayFamilyNews();
        $this->displayFooter();
    }

    /**
     * displayDeleteThemeConfirmation.
     *
     * The confirmation screen that is shown when trying to delete a theme with js turned off.
     *
     * @return void
     */
    public function displayDeleteThemeConfirmation()
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
     * displayInvalidAccessLevel.
     *
     * @return void
     */
    public function displayInvalidAccessLevel()
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
     * displayEditFacebook.
     *
     * @return void
     */
    public function displayEditFacebook()
    {
        $this->displayHeader();

        $config = getFacebookConfigData();
        $accessToken = getUserFacebookAccessToken($this->fcmsUser->id);

        if (!empty($config['fb_app_id']) && !empty($config['fb_secret'])) {
            // Setup url for callbacks
            $callbackUrl = getDomainAndDir();
            $callbackUrl .= 'settings.php?view=facebook';

            $facebook = new Facebook([
                'appId'  => $config['fb_app_id'],
                'secret' => $config['fb_secret'],
            ]);

            // Check if the user is logged in and authed
            $fbUser = $facebook->getUser();
            $fbProfile = '';

            if ($fbUser) {
                try {
                    $fbProfile = $facebook->api('/me');
                } catch (FacebookApiException $e) {
                    $fbUser = null;
                }
            }

            if ($fbUser) {
                $user = '<a href="'.$fbProfile['link'].'">'.$fbProfile['email'].'</a>';
                $status = sprintf(T_('Currently connected as: %s'), $user);
                $status .= '<br/><br/><img src="https://graph.facebook.com/'.$fbUser.'/picture" alt="Facebook">';
                $link = '<a class="disconnect" href="?revoke=facebook">'.T_('Disconnect').'</a>';
            } else {
                $params = [
                    'scope'        => 'user_about_me,user_photos,user_birthday,user_location,email,publish_actions',
                    'redirect_uri' => $callbackUrl,
                ];

                $status = T_('Not Connected');
                $link = '<a href="'.$facebook->getLoginUrl($params).'">'.T_('Connect').'</a>';
            }
        }

        echo '
        <div class="social-media-connect">
            <img class="icon" src="ui/img/facebook.png" alt="Facebook"/>
            <h2>Facebook</h2>
            <p>'.T_('Connecting with Facebook will allow you to:').'</p>
            <ul>
                <li>'.T_('Login to this site using your Facebook credentials.').'</li>
                <li>'.T_('Share status updates from this site to Facebook.').'</li>
            </ul>
            <div class="status">'.$status.'</div>
            <div class="action">'.$link.'</div>
        </div>';

        $this->displayFooter();
    }

    /**
     * displayEditFacebookSubmit.
     *
     * @return void
     */
    public function displayEditFacebookSubmit()
    {
        $data = getFacebookConfigData();

        if (!empty($data['fb_app_id']) && !empty($data['fb_secret'])) {
            $facebook = new Facebook([
              'appId'  => $data['fb_app_id'],
              'secret' => $data['fb_secret'],
            ]);

            $fbUserId = $facebook->getUser();
            if ($fbUserId) {
                try {
                    $fbProfile = $facebook->api('/me');
                } catch (FacebookApiException $e) {
                    $fbUserId = null;
                }
            }

            $facebook->setExtendedAccessToken();
            $accessToken = $facebook->getAccessToken();

            $sql = 'UPDATE `fcms_user_settings`
                    SET `fb_access_token` = ?,
                        `fb_user_id` = ?
                    WHERE `user` = ?';

            $params = [
                $accessToken,
                $fbUserId,
                $this->fcmsUser->id,
            ];

            if (!$this->fcmsDatabase->update($sql, $params)) {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }
        }

        // Facebook isn't configured
        else {
            $this->displayHeader();

            echo '
            <div class="info-alert">
                <h2>'.T_('Facebook isn\'t Configured Yet.').'</h2>
                <p>'.T_('Unfortunately, your website administrator has not set up Facebook yet.').'</p>
            </div>';

            $this->displayFooter();

            return;
        }

        header('Location: settings.php?view=facebook');
    }

    /**
     * displayRevokeFacebookAccess.
     *
     * @return void
     */
    public function displayRevokeFacebookAccess()
    {
        $sql = 'UPDATE `fcms_user_settings`
                SET `fb_access_token` = NULL
                WHERE `user` = ?';

        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id)) {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // remove any facebook session vars
        foreach ($_SESSION as $key => $val) {
            if (substr($key, 0, 3) == 'fb_') {
                unset($_SESSION[$key]);
            }
        }

        header('Location: settings.php?view=facebook');
    }

    /**
     * displayEditFoursquare.
     *
     * @return void
     */
    public function displayEditFoursquare()
    {
        $this->displayHeader();

        $config = getFoursquareConfigData();
        $user = getFoursquareUserData($this->fcmsUser->id);

        // Setup url for callbacks
        $callbackUrl = getDomainAndDir();
        $callbackUrl .= 'settings.php?view=foursquare';

        $fsObj = new EpiFoursquare($config['fs_client_id'], $config['fs_client_secret']);

        if (!empty($user['fs_user_id']) && !empty($user['fs_access_token'])) {
            $fsObjAuth = new EpiFoursquare(
                            $config['fs_client_id'],
                            $config['fs_client_secret'],
                            $user['fs_access_token']
            );

            $self = $fsObjAuth->get('/users/self');

            $user = '<a href="http://foursquare.com/user/'.$self->response->user->id.'">'.$self->response->user->contact->email.'</a>';
            $status = sprintf(T_('Currently connected as: %s'), $user);
            $status .= '<br/><br/><img src="'.$self->response->user->photo->prefix.'80x80'.$self->response->user->photo->suffix.'"/>';
            $link = '<a class="disconnect" href="?revoke=foursquare">'.T_('Disconnect').'</a>';
        } else {
            $status = '<span class="not_connected">'.T_('Not Connected').'</span>';
            $link = '<a href="'.$fsObj->getAuthorizeUrl($callbackUrl).'">'.T_('Connect').'</a>';
        }

        echo '
        <div class="social-media-connect">
            <img class="icon" src="ui/img/foursquare.png" alt="Foursquare"/>
            <h2>Foursquare</h2>
            <p>'.T_('Connecting with Foursquare will allow you to:').'</p>
            <ul>
                <li>'.T_('Share your Foursquare check-ins with this site.').'</li>
            </ul>
            <div class="status">'.$status.'</div>
            <div class="action">'.$link.'</div>
        </div>';

        $this->displayFooter();
    }

    /**
     * displayFoursquareSubmit.
     *
     * The submit screen for saving foursquare data.
     *
     * @return void
     */
    public function displayFoursquareSubmit()
    {
        $r = getFoursquareConfigData();

        $id = cleanOutput($r['fs_client_id']);
        $secret = cleanOutput($r['fs_client_secret']);
        $url = cleanOutput($r['fs_callback_url']);

        $fsObj = new EpiFoursquare($id, $secret);
        $token = $fsObj->getAccessToken($_GET['code'], $url);

        $fsObjAuth = new EpiFoursquare($id, $secret, $token->access_token);
        $self = $fsObjAuth->get('/users/self');

        $sql = 'UPDATE `fcms_user_settings`
                SET `fs_user_id` = ?,
                    `fs_access_token` = ?
                WHERE `user` = ?';

        $params = [
            $self->response->user->id,
            $token->access_token,
            $this->fcmsUser->id,
        ];

        if (!$this->fcmsDatabase->update($sql, $params)) {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        header('Location: settings.php?view=foursquare');
    }

    /**
     * displayRevokeFoursquareAccess.
     *
     * @return void
     */
    public function displayRevokeFoursquareAccess()
    {
        $sql = 'UPDATE `fcms_user_settings`
                SET `fs_user_id` = NULL, `fs_access_token` = NULL
                WHERE `user` = ?';

        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id)) {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        header('Location: settings.php?view=foursquare');
    }

    /**
     * displayEditInstagram.
     *
     * @return void
     */
    public function displayEditInstagram()
    {
        $this->displayHeader();

        $config = getInstagramConfigData();

        $callbackUrl = getDomainAndDir();
        $callbackUrl .= 'settings.php?view=instagram';

        $accessToken = getUserInstagramAccessToken($this->fcmsUser->id);
        $instagram = new Instagram($config['instagram_client_id'], $config['instagram_client_secret'], $accessToken);

        if (!$accessToken) {
            $url = $instagram->authorizeUrl($callbackUrl, ['basic', 'comments', 'likes', 'relationships']);

            $status = T_('Not Connected');
            $link = '<a href="'.$url.'">'.T_('Connect').'</a>';
        } else {
            try {
                $feed = $instagram->get('users/self');
            } catch (InstagramApiError $e) {
                die($e->getMessage());
            }

            $status = sprintf(T_('Currently connected as: %s'), $feed->data->username);
            $status .= '<br/><br/><img src="'.$feed->data->profile_picture.'"/>';
            $link = '<a class="disconnect" href="?revoke=instagram">'.T_('Disconnect').'</a>';
        }

        echo '
        <div class="social-media-connect">
            <img class="icon" src="ui/img/instagram.png" alt="Instagram"/>
            <h2>Instagram</h2>
            <p>'.T_('Connecting with Instagram will allow you to:').'</p>
            <ul>
                <li>'.T_('Share your Instagram photos with this site.').'</li>
            </ul>
            <div class="status">'.$status.'</div>
            <div class="action">'.$link.'</div>
        </div>';

        $this->displayFooter();
    }

    /**
     * displayEditInstagramSubmit.
     *
     * @return void
     */
    public function displayEditInstagramSubmit()
    {
        $config = getInstagramConfigData();

        if (!empty($config['instagram_client_id']) && !empty($config['instagram_client_secret'])) {
            $callbackUrl = getDomainAndDir();
            $callbackUrl .= 'settings.php?view=instagram';

            $instagram = new Instagram($config['instagram_client_id'], $config['instagram_client_secret'], null);

            if (isset($_GET['error']) || isset($_GET['error_reason']) || isset($_GET['error_description'])) {
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

            $sql = 'UPDATE `fcms_user_settings`
                    SET `instagram_access_token` = ?
                    WHERE `user` = ?';

            $params = [
                $accessToken,
                $this->fcmsUser->id,
            ];

            if (!$this->fcmsDatabase->update($sql, $params)) {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }
        }
        // Instagram isn't configured
        else {
            $this->displayHeader();

            echo '
            <div class="info-alert">
                <h2>'.T_('Instagram isn\'t Configured Yet.').'</h2>
                <p>'.T_('Unfortunately, your website administrator has not set up Instagram yet.').'</p>
            </div>';

            $this->displayFooter();

            return;
        }

        header('Location: settings.php?view=instagram');
    }

    /**
     * displayRevokeInstagramAccess.
     *
     * @return void
     */
    public function displayRevokeInstagramAccess()
    {
        $sql = 'UPDATE `fcms_user_settings`
                SET `instagram_access_token` = NULL
                WHERE `user` = ?';

        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id)) {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        header('Location: settings.php?view=instagram');
    }

    /**
     * displayEditGoogle.
     *
     * @return void
     */
    public function displayEditGoogle()
    {
        $this->displayHeader();

        $config = getGoogleConfigData();
        $user = getGoogleUserData($this->fcmsUser->id);

        // If google hasn't been configured, warn the user
        if (empty($config['google_client_id']) || empty($config['google_client_secret'])) {
            echo '
            <div class="info-alert">
                <h2>'.T_('Google isn\'t Configured Yet.').'</h2>
                <p>'.T_('Unfortunately, your website administrator has not set up Google yet.').'</p>
                <p>'.T_('You will not be able to upload photos from Picasa or upload videos from YouTube, until this is complete.').'</p>
            </div>';

            $this->displayFooter();

            return;
        }

        // Setup url for callbacks
        $callbackUrl = getDomainAndDir();
        $callbackUrl .= 'settings.php?view=google&oauth2callback';

        $_SESSION['callback_url'] = $callbackUrl;

        $googleClient = new Google_Client();
        $googleClient->setClientId($config['google_client_id']);
        $googleClient->setClientSecret($config['google_client_secret']);
        $googleClient->setAccessType('offline');
        $googleClient->setScopes([
            'https://www.googleapis.com/auth/youtube.force-ssl',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://picasaweb.google.com/data/',
        ]);
        $googleClient->setRedirectUri($callbackUrl);

        // We still have a token saved
        if (isset($_SESSION['googleSessionToken'])) {
            try {
                $googleClient->setAccessToken($_SESSION['googleSessionToken']);
                // Make sure our access token is still good
                if ($googleClient->isAccessTokenExpired()) {
                    $googleClient->refreshToken($user['google_session_token']);
                }
            } catch (Exception $e) {
                $failure = 1;
            }
        }
        // We need to use our refresh token from the db to get an access token
        elseif (!empty($user['google_session_token'])) {
            try {
                $googleClient->refreshToken($user['google_session_token']);

                $_SESSION['googleSessionToken'] = $googleClient->getAccessToken();
            } catch (Exception $e) {
                $failure = 1;
            }
        }

        if (!isset($failure) && isset($_SESSION['googleSessionToken'])) {
            try {
                $youtube = new Google_Service_YouTube($googleClient);
                $channel = $youtube->channels->listChannels('id', [
                    'mine' => 'true',
                ]);
            } catch (Exception $e) {
                echo '<div class="error-alert">ERROR: '.$e->getMessage().'</div>';
                $this->displayFooter();

                return;
            }

            $oAuth = new Google_Service_Oauth2($googleClient);

            $userInfo = $oAuth->userinfo->get();

            $user = '<a href="http://www.youtube.com/channel/'.$channel->items[0]['id'].'">'.$userInfo->email.'</a>';
            $status = sprintf(T_('Currently connected as: %s'), $user);
            $link = '<a class="disconnect" href="?revoke=google">'.T_('Disconnect').'</a>';
        } else {
            $state = mt_rand();
            $googleClient->setState($state);

            $_SESSION['state'] = $state;

            $url = $googleClient->createAuthUrl();

            $status = T_('Not Connected');
            $link = '<a href="'.$url.'">'.T_('Connect').'</a>';
        }

        echo '
        <div class="social-media-connect">
            <img class="icon" src="ui/img/google.png" alt="Google"/>
            <h2>Google</h2>
            <p>'.T_('Connecting with Google will allow you to:').'</p>
            <ul>
                <li>'.T_('Share your Picasa photos with this site.').'</li>
                <li>'.T_('Share your YouTube videos with this site.').'</li>
            </ul>
            <div class="status">'.$status.'</div>
            <div class="action">'.$link.'</div>
        </div>';

        $this->displayFooter();
    }

    /**
     * displayEditGoogleSubmit.
     *
     * @return void
     */
    public function displayEditGoogleSubmit()
    {
        $config = getGoogleConfigData();

        if (strval($_SESSION['state']) !== strval($_GET['state'])) {
            $this->displayHeader();
            echo '
            <div class="error-alert">The session state did not match.</div>';
            $this->displayFooter();

            return;
        }

        if (!isset($_GET['code'])) {
            $this->displayHeader();
            echo '
            <div class="error-alert">
                <p>Something went wrong.</p>
                <p><a href="settings.php?view=google">Go back to Google Settings.</a></p>
            </div>';
            $this->displayFooter();

            return;
        }

        $googleClient = new Google_Client();
        $googleClient->setClientId($config['google_client_id']);
        $googleClient->setClientSecret($config['google_client_secret']);
        $googleClient->setAccessType('offline');
        $googleClient->setScopes([
            'https://www.googleapis.com/auth/youtube.force-ssl',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://picasaweb.google.com/data/',
        ]);
        $googleClient->setRedirectUri($_SESSION['callback_url']);

        // auth by turning code into token
        $googleClient->authenticate($_GET['code']);

        $_SESSION['googleSessionToken'] = $googleClient->getAccessToken();

        // Save the token
        $googleClient->setAccessToken($_SESSION['googleSessionToken']);

        $json = json_decode($_SESSION['googleSessionToken']);

        $sql = 'UPDATE `fcms_user_settings`
                SET `google_session_token` = ?
                WHERE `user` = ?';

        $params = [
            $json->refresh_token,
            $this->fcmsUser->id,
        ];

        if (!$this->fcmsDatabase->update($sql, $params)) {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        header('Location: settings.php?view=google');
    }

    /**
     * displayRevokeGoogleAccess.
     *
     * @return void
     */
    public function displayRevokeGoogleAccess()
    {
        $config = getGoogleConfigData();

        if (isset($_SESSION['googleSessionToken'])) {
            $googleClient = new Google_Client();
            $googleClient->setClientId($config['google_client_id']);
            $googleClient->setClientSecret($config['google_client_secret']);
            $googleClient->setAccessType('offline');
            $googleClient->setScopes([
                'https://www.googleapis.com/auth/youtube.force-ssl',
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile',
                'https://picasaweb.google.com/data/',
            ]);
            $googleClient->setRedirectUri($_SESSION['callback_url']);

            $googleClient->setAccessToken($_SESSION['googleSessionToken']);
            $googleClient->revokeToken();

            unset($_SESSION['googleSessionToken']);
        }

        $sql = 'UPDATE `fcms_user_settings`
                SET `google_session_token` = NULL
                WHERE `user` = ?';

        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id)) {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        header('Location: settings.php?view=google');
    }
}
