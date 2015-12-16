<?php
/**
 * Family Connections - www.familycms.com
 * 
 * PHP versions 4 and 5
 * 
 * Copyright (C) 2007 Ryan Haudenschilt
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */

session_start();

// Site has NOT been installed yet
if (!file_exists('inc/config_inc.php'))
{
    displayNoConfig();
    return;
}

require 'fcms.php';

load('facebook', 'socialmedia', 'phpass');

setLanguage();

control();
return;

/**
 * control 
 * 
 * @return void
 */
function control ()
{
    if (isset($_GET['lang']))
    {
        displayChangeLanguage();
    }
    elseif (isset($_SESSION['fcms_id']) || isset($_COOKIE['fcms_cookie_id']))
    {
        displayAlreadyLoggedIn();
    }
    elseif (isset($_POST['submit']))
    {
        displayLoginSubmit();
    }
    else
    {
        displayLoginForm();
    }
}

/**
 * displayNoConfig 
 * 
 * @return void
 */
function displayNoConfig ()
{
    include_once 'inc/constants.php';
    include_once 'inc/thirdparty/gettext.inc';

    // Setup php-gettext
    T_setlocale(LC_MESSAGES, 'en_US');
    T_bindtextdomain('messages', './language');
    T_bind_textdomain_codeset('messages', 'UTF-8');
    T_textdomain('messages');

    displayHeader(false);

    $TMPL = array(
        'message' => array(
            'type'     => 'err-msg',
            'title'    => T_('Oops!'),
            'messages' => array(
                T_('This site hasn\'t been installed yet.')
                    .' <a href="install.php">'.T_('You must finish the installation before using the site.').'</a>',
            ),
        ),
        'noForm' => 1,
    );

    require_once('ui/login/main.php');
}

/**
 * displayChangeLanguage 
 * 
 * Changes the language and redirects the page to the login form.
 * 
 * @return void
 */
function displayChangeLanguage ()
{
    $_SESSION['language'] = $_GET['lang'];

    T_setlocale(LC_MESSAGES, $_SESSION['language']);

    header("Location: index.php");
}

/**
 * displayLoginSubmit 
 * 
 * @return void
 */
function displayLoginSubmit ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    $user     = $_POST['user'];
    $pass     = $_POST['pass'];
    $redirect = 'home.php';
    $rem      = 0;

    if (isset($_POST['rem']))
    {
        $rem = 1;
    }

    $sql = "SELECT `id`, `username`, `password`, `phpass`, `activated`, `locked` 
            FROM `fcms_users` 
            WHERE `username` = ?";

    $row = $fcmsDatabase->getRow($sql, $user);
    if ($row === false)
    {
        $fcmsError->displayError();
        return;
    }

    // Can't find username
    if (count($row) <= 0)
    {
        handleBadLogin($user);
        return;
    }

    // New password style
    if ($row['password'] == '0')
    {
        $hasher = new PasswordHash(8, FALSE);

        // Does the pw supplied match the db?
        if (!$hasher->CheckPassword($pass, $row['phpass']))
        {
            handleBadLogin($user);
            return;
        }
    }
    // Old password style
    else
    {
        if (md5($pass) !== $row['password'])
        {
            handleBadLogin($user);
            return;
        }

        // Lets update the user's old pw to the new style
        if (!upgradeNewPassword($row['id'], $pass))
        {
            displayHeader();
            echo '<div class="err-msg">';
            $fcmsError->displayError();
            echo '</div>';
            displayLogin();
            return;
        }
    }

    // User is active
    if ($row['activated'] > 0)
    {
        // Login the user
        if (!loginUser($row['id'], $rem))
        {
            displayHeader();
            echo '<div class="err-msg">';
            $fcmsError->displayError();
            echo '</div>';
            displayLogin();
            return;
        }

        // Redirect to desired page
        header("Location: $redirect");
    }
    // User has been locked out for failed attempts
    elseif ($row['activated'] < 0)
    {
        // User's lockout has ended
        if (gmdate('YmdHis') > gmdate('YmdHis', strtotime($row['locked'])))
        {
            // Set user as active
            $sql = "UPDATE `fcms_users` 
                    SET `activated` = '1' 
                    WHERE `id` = ?";

            if (!$fcmsDatabase->update($sql, $row['id']))
            {
                displayHeader();
                echo '<div class="err-msg">';
                $fcmsError->displayError();
                echo '</div>';
                displayLogin();
                return;
            }

            // Login the user
            if (!loginUser($row['id'], $rem))
            {
                displayHeader();
                echo '<div class="err-msg">';
                $fcmsError->displayError();
                echo '</div>';
                displayLogin();
                return;
            }

            // Redirect to desired page
            header("Location: $redirect");

        }
        // User is still locked out
        else
        {
            displayLockedOut();
        }

    }
    // User is not active
    else
    {
        displayNotActive();
    }
}

/**
 * displayAlreadyLoggedIn 
 * 
 * @return void
 */
function displayAlreadyLoggedIn ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    if (isset($_COOKIE['fcms_cookie_id']))
    {
        $_SESSION['fcms_id']    = (int)$_COOKIE['fcms_cookie_id'];
        $_SESSION['fcms_token'] = $_COOKIE['fcms_cookie_token'];
    }

    // Redirect to desired page
    header("Location: home.php");
}

/**
 * displayHeader 
 * 
 * @param boolean $login Are we displaying the login screen?
 * 
 * @return  void
 */
function displayHeader($login = true)
{
    $TMPL = array(
        'sitename' => 'Family Connections',
        'body'     => '',
    );

    if ($login)
    {
        $TMPL['sitename'] = getSiteName().' - '.T_('powered by').' '.getCurrentVersion();
        $TMPL['body']     = ' onload="document.getElementById(\'user\').focus()"';
    }

    require_once('ui/login/header.php');
}

/**
 * displayLoginForm 
 * 
 * @return void
 */
function displayLoginForm ()
{
    $msg = handleFacebookLogin();

    displayHeader();
    displayLogin($msg);
}

/**
 * displayLogin 
 * 
 * @return void
 */
function displayLogin($msg = null)
{
    $TMPL = array(
        'sitename'           => getSiteName(),
        'languageOptions'    => array(),
        'usernameText'       => T_('Username'),
        'passwordText'       => T_('Password'),
        'rememberMeText'     => T_('Remember Me'),
        'loginText'          => T_('Login'),
        'forgotPasswordText' => T_('Forgot Password?'),
        'currentVersion'     => getCurrentVersion(),
    );

    // Display any errors, that were redirected here
    if (isset($_GET['err']))
    {
        if ($_GET['err'] == 'login')
        {
            $TMPL['message'] = array(
                'type'     => 'err-msg',
                'title'    => T_('Access Denied'),
                'messages' => array(
                    T_('You must be logged in to view that page.'),
                ),
            );
        }
        elseif ($_GET['err'] == 'off')
        {
            $TMPL['noForm']  = 1;
            $TMPL['message'] = array(
                'type'     => 'err-msg',
                'title'    => T_('Hold On a Second!'),
                'messages' => array(
                    T_('The site has been closed by an administrator.'),
                    T_('Please come back later.'),
                ),
            );
        }
    }

    if (isset($_GET['url']))
    {
        $TMPL['redirectUrl'] = '<input type="hidden" name="url" id="url" value="'.cleanOutput($_GET['url']).'"/>';
    }

    // Display any errors that were passed in
    if (!is_null($msg))
    {
        $TMPL['message'] = $msg;
    }

    // Get available languages
    $lang_dir     = "language/";
    $lang_options = '';

    if (is_dir($lang_dir))
    {
        if ($dh = opendir($lang_dir))
        {
            while (($file = readdir($dh)) !== false)
            {
                // Skip directories that start with a period
                if ($file[0] === '.')
                {
                    continue;
                }

                // Skip files (messages.pot)
                if (!is_dir("$lang_dir$file"))
                {
                    continue;
                }

                // Skip directories that don't include a messages.mo file
                if (!file_exists($lang_dir.$file.'/LC_MESSAGES/messages.mo'))
                {
                    continue;
                }

                $selected = '';
                if (isset($_SESSION['language']) && $_SESSION['language'] == $file)
                {
                    $selected = 'selected="selected"';
                }

                $TMPL['languageOptions'][] = array(
                    'value'    => $file,
                    'language' => getLangName($file),
                    'selected' => $selected,
                );
            }

            $TMPL['languageOptions'] = subval_sort($TMPL['languageOptions'], 'language');

            closedir($dh);
        }
    }

    if (isRegistrationOn())
    {
        $TMPL['registerText'] = T_('Register');
    }

    $fbData = getFacebookConfigData();
    $params = array('scope' => 'user_about_me,user_birthday,user_location,email,publish_actions');

    // Print the facebook register button
    if (!empty($fbData['fb_app_id']) && !empty($fbData['fb_secret']))
    {
        $facebook = new Facebook(array(
            'appId'  => $fbData['fb_app_id'],
            'secret' => $fbData['fb_secret'],
        ));

        $TMPL['facebookLogin'] = array(
            'url'  => $facebook->getLoginUrl($params),
            'text' => T_('Login using Facebook'),
        );
    }

    require_once('ui/login/main.php');
}

/**
 * handleBadLogin 
 * 
 * @param string $user The username login being attempted on
 * 
 * @return void
 */
function handleBadLogin ($user)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    $sql = "SELECT `id`, `login_attempts` 
            FROM `fcms_users` 
            WHERE `username` = ?";

    $row = $fcmsDatabase->getRow($sql, $user);    
    if ($row === false)
    {
        $fcmsError->displayError();

        return;
    }

    // valid username, wrong password
    if (!empty($row))
    {
        // user exceeded max login attempts
        if ($row['login_attempts'] > 4)
        {
            // Lock users account
            $sql = "UPDATE `fcms_users` 
                    SET `activated` = '-1', `locked` = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                    WHERE `id` = ?";

            if (!$fcmsDatabase->update($sql, $row['id']))
            {
                $fcmsError->displayError();

                return;
            }

            displayLockedOut();
            return;
        }

        // Increase login attempts
        $sql = "UPDATE `fcms_users` 
                SET `login_attempts` = `login_attempts`+1 
                WHERE `id` = ?";

        if (!$fcmsDatabase->update($sql, $row['id']))
        {
            $fcmsError->displayError();

            return;
        }
    }

    displayHeader();

    $msg = array(
        'type'     => 'err-msg',
        'title'    => T_('Oops!'),
        'messages' => array(
            T_('That login information wasn\'t quite right.'),
            T_('Be sure and check that you typed your username/password correctly.'),
        ),
    );

    displayLogin($msg);
}

/**
 * handleFacebookLogin 
 * 
 * @return void
 */
function handleFacebookLogin ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    $msg = null;

    $fbData = getFacebookConfigData();

    if (empty($fbData['fb_app_id']) || empty($fbData['fb_secret']))
    {
        return;
    }

    $facebook = new Facebook(array(
        'appId'  => $fbData['fb_app_id'],
        'secret' => $fbData['fb_secret'],
    ));

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

    // User isn't logged in, or authed
    if (!$fbUser)
    {
        return $msg;
    }

    $sql = "SELECT u.`id`, u.`username`, u.`phpass`, u.`activated`, u.`locked`
            FROM `fcms_users` AS u, `fcms_user_settings` AS s
            WHERE s.`user` = u.`id`
            AND (
                u.`username` = ?
                OR s.`fb_user_id` = ?
            )";

    $params = array(
        $fbProfile['email'],
        $fbUser
    );

    $row = $fcmsDatabase->getRow($sql, $params);
    if ($row === false)
    {
        $fcmsError->displayError();
        return $msg;
    }

    if (empty($row))
    {
        $msg = array(
            'type'     => 'err-msg',
            'title'    => T_('Oops!'),
            'messages' => array(
                T_('Your account hasn\'t been connected to Facebook yet.  You need to connect your existing account with Facebook or register a new account using Facebook.'),
            ),
        );

        return $msg;
    }

    // Check account is active
    if ($row['activated'] == 0)
    {
        displayNotActive();
        die(); // we don't want to return to displaying the login, we already did 
    }

    // We made it past all the checks, then the user can be logged in
    if (!loginUser($row['id'], 0))
    {
        $fcmsError->displayError();
        return $msg;
    }

    header("Location: home.php");
}

/**
 * displayNotActive 
 * 
 * @return void
 */
function displayNotActive ()
{
    displayHeader();

    $msg = array(
        'type'     => 'err-msg',
        'title'    => T_('Not So Fast'),
        'messages' => array(
            T_('Your account isn\'t active yet.  Your website administrator must activate your account before you can login and begin using the website.'),
        ),
    );

    displayLogin($msg);
}

/**
 * displayLockedOut 
 * 
 * @return void
 */
function displayLockedOut ()
{
    displayHeader();

    $msg = array(
        'type'     => 'err-msg',
        'title'    => T_('Hold On a Second!'),
        'messages' => array(
            T_('You have exceeded the number of allowed login attempts.'),
            T_('Your account has been locked for 1 hour.'),
        ),
    );

    displayLogin($msg);
}

/**
 * upgradeNewPassword 
 * 
 * Saves the password in the new format, deletes old pw.
 * 
 * @param int    $userId
 * @param string $password 
 * 
 * @return boolean
 */
function upgradeNewPassword($userId, $password)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    // Hash the pw
    $hasher         = new PasswordHash(8, FALSE);
    $hashedPassword = $hasher->HashPassword($password);

    $sql = "UPDATE `fcms_users`
            SET `password` = '0',
                `phpass` = ?
            WHERE `id` = ?";

    $params = array($hashedPassword, $userId);

    if (!$fcmsDatabase->update($sql, $params))
    {
        return false;
    }

    return true;
}
