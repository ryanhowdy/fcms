<?php
/*
Family Connections - a family oriented CMS -- http://www.familycms.com/

Copyright (C) 2007-09 Ryan Haudenschilt

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

session_start();

// Site has NOT been installed yet
if (!file_exists('inc/config_inc.php')) {

    // Setup php-gettext
    include_once('inc/gettext.inc');
    T_setlocale(LC_MESSAGES, 'en_US');
    T_bindtextdomain('messages', './language');
    T_bind_textdomain_codeset('messages', 'UTF-8');
    T_textdomain('messages');

    displayHeader(false);
    echo '
    <div id="oops">
        <h1>'.T_('Oops!').'</h1>
        <p>
            '.T_('This site hasn\'t been installed yet.').' 
            <a href="install.php">'.T_('You must finish the installation before using the site.').'</a>
        </p>
    </div>
</body>
</html>';

// Site is installed... proceed
} else {

    include_once('inc/config_inc.php');
    include_once('inc/util_inc.php');

    if (isset($_POST['user'])) {
        $user = cleanInput($_POST['user']);
    }
    if (isset($_POST['pass'])) {
        $pass = cleanInput($_POST['pass']);
    }
    $rem = isset($_POST['rem']) ? 1 : 0;

    mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
    mysql_select_db($cfg_mysql_db);

    // Change language
    if (isset($_GET['lang'])) {
        $_SESSION['language'] = cleanInput($_GET['lang']);
        T_setlocale(LC_MESSAGES, $_SESSION['language']);
        header("Location: index.php");
    }

    $redirect = isset($_POST['url']) ? $_POST['url'] : 'home.php';

    // Redirected from anther page
    if (isset($_GET['err'])) {
        // Tried to access a page before logging in
        if ($_GET['err'] == 'login') {
            displayHeader();
            echo '
    <div class="err-msg">
        <h2>'.T_('Access Denied').'</h2>
        <p>'.T_('You must be logged in to view that page.').'</p>
    </div>';
            displayLogin();
            exit();
        } elseif ($_GET['err'] == 'off') {
            displayHeader();
            echo '
    <div class="err-msg">
        <h2>'.T_('Hold On a Second!').'</h2>
        <p>'.T_('The site has been closed by an administrator.').'</p>
        <p>'.T_('Please come back later.').'</p>
    </div>';
            displayLogin();
            exit();
        }
    }

    // No session/cookie data
    if (!isset($_SESSION['login_id']) && !isset($_COOKIE['fcms_login_id'])) {

        // Form wasn't submitted so display the form
        if ((!isset($user)) || (!isset($pass))) {
            displayHeader();
            displayLogin();
            exit();
        }

        // Form was submitted so check to see if we can login the user
        $pass = md5($pass);
        $sql = "SELECT `id`, `username`, `password`, `activated`, `locked` 
                FROM `fcms_users` 
                WHERE `username` = '$user' 
                AND `password` = '$pass'";
        $result = mysql_query($sql) or displaySQLError(
            'Login Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $login_check = mysql_num_rows($result);
        $row = mysql_fetch_array($result);

        // User's username and password match the db
        if ($login_check > 0) {

            // User is active
            if ($row['activated'] > 0) {

                // Setup Cookie/Session
                if ($rem >= 1) {
                    setcookie('fcms_login_id', $row['id'], time() + (30*(24*3600)), '/');  // 30 days
                    setcookie('fcms_login_uname', $row['username'], time() + (30*(24*3600)), '/');  // 30 days
                    setcookie('fcms_login_pw', $row['password'], time() + (30*(24*3600)), '/');  // 30 days
                } else {
                    $_SESSION['login_id'] = $row['id'];
                    $_SESSION['login_uname'] = $row['username'];
                    $_SESSION['login_pw'] = $row['password'];
                }

                // Update activity
                $sql = "UPDATE `fcms_users` 
                        SET `activity` = NOW() 
                        WHERE `id` = " . $row['id'];
                mysql_query($sql) or displaySQLError(
                    'Activity Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );

                // Reset invalid login attempts
                $sql = "UPDATE `fcms_users` 
                        SET `login_attempts` = '0' 
                        WHERE `id` = " . $row['id'];
                mysql_query($sql) or displaySQLError(
                    'Login Attempt Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );

                // Redirect to desired page
                header("Location: $redirect");

            // User has been locked out for failed attempts
            } elseif ($row['activated'] < 0) {

                // User's lockout has ended
                if (gmdate('YmdHis') > gmdate('YmdHis', strtotime($row['locked']))) {

                    // Set user as active
                    $sql = "UPDATE `fcms_users` 
                            SET `activated` = '1' 
                            WHERE `id` = " . $row['id'];
                    mysql_query($sql) or displaySQLError(
                        'Activated Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );

                    // Setup Cookie/Session
                    if ($rem >= 1) {
                        setcookie('fcms_login_id', $row['id'], time() + (30*(24*3600)), '/');  // 30 days
                        setcookie('fcms_login_uname', $row['username'], time() + (30*(24*3600)), '/');  // 30 days
                        setcookie('fcms_login_pw', $row['password'], time() + (30*(24*3600)), '/');  // 30 days
                    } else {
                        $_SESSION['login_id'] = $row['id'];
                        $_SESSION['login_uname'] = $row['username'];
                        $_SESSION['login_pw'] = $row['password'];
                    }

                    // Update activity
                    $sql = "UPDATE `fcms_users` 
                            SET `activity` = NOW() 
                            WHERE `id` = " . $row['id'];
                    mysql_query($sql) or displaySQLError(
                        'Activity Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );

                    // Reset invalid login attempts
                    $sql = "UPDATE `fcms_users` 
                            SET `login_attempts` = '0' 
                            WHERE `id` = " . $row['id'];
                    mysql_query($sql) or displaySQLError(
                        'Login Attempt Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );

                    // Redirect to desired page
                    header("Location: $redirect");

                // User is still locked out
                } else {
                    displayHeader();
                    echo '
    <div class="err-msg">
        <h2>'.T_('Hold On a Second!').'</h2>
        <p>'.T_('You have exceeded the number of allowed login attempts.').'</p>
        <p>'.T_('Your account has been locked for 1 hour.').'</p>
    </div>';
                    displayLogin();
                }

            // User is not active
            } else {
                displayHeader();
                echo '
    <div class="err-msg">
        <h2>'.T_('Not So Fast').'</h2>
        <p>'.T_('Your account isn\'t active yet.  Your website administrator must activate your account before you can login and begin using the website.').'</p>
    </div>';
                displayLogin();
            }

        // Username and password do not match the db
        } else {
            $sql = "SELECT `id`, `login_attempts` 
                    FROM `fcms_users` 
                    WHERE `username` = '$user'";
            $result = mysql_query($sql) or displaySQLError(
                'Valid Username Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $valid_username = mysql_num_rows($result);

            // User has a valid username, but they entered the wrong pw
            if ($valid_username > 0) {
                $r = mysql_fetch_array($result);

                // User has exceeded the max login attempts, lock the account
                if ($r['login_attempts'] > 4) {

                    // Lock users account
                    $sql = "UPDATE `fcms_users` 
                            SET `activated` = '-1', `locked` = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                            WHERE `id` = " . $r['id'];
                    mysql_query($sql) or displaySQLError(
                        'Login Limit Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );

                    displayHeader();
                    echo '
    <div class="err-msg">
        <h2>'.T_('Hold On a Second!').'</h2>
        <p>'.T_('You have exceeded the number of allowed login attempts.').'</p>
        <p>'.T_('Your account has been locked for 1 hour.').'</p>
    </div>';
                    displayLogin();
                    exit(0);
                }

                // Increase login attempts
                $sql = "UPDATE `fcms_users` 
                        SET `login_attempts` = `login_attempts`+1 
                        WHERE `id` = " . $r['id'];
                mysql_query($sql) or displaySQLError(
                    'Login Attempt Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
            }

            displayHeader();
            echo '
    <div class="err-msg">
        <h2>'.T_('Oops!').'</h2/>
        <p>'.T_('That login information wasn\'t quite right.').'</p>
        <p>'.T_('Be sure and check that you typed your username/password correctly.').'</p>
    </div>';
            displayLogin();
        }

    // User has a valid session/cookie
    } else {
        if (isset($_COOKIE['fcms_login_id'])) {
            $_SESSION['login_id'] = cleanInput($_COOKIE['fcms_login_id'], 'int');
            $_SESSION['login_uname'] = cleanInput($_COOKIE['fcms_login_uname']);
            $_SESSION['login_pw'] = cleanInput($_COOKIE['fcms_login_pw']);
        }

        // Update activity
        $sql = "UPDATE `fcms_users` 
                SET `activity` = NOW() 
                WHERE `id` = '" . cleanInput($_SESSION['login_id'], 'int') . "'";
        mysql_query($sql) or displaySQLError(
            'Activity Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        // Reset invalid login attempts
        $sql = "UPDATE `fcms_users` 
                SET `login_attempts` = '0' 
                WHERE `id` = '" . cleanInput($_SESSION['login_id'], 'int') . "'";
        mysql_query($sql) or displaySQLError(
            'Login Attempt Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        // Redirect to desired page
        header("Location: $redirect");
    }
}

/**
 * displayHeader 
 * 
 * @param   boolean $login 
 * @return  void
 */
function displayHeader($login = true) {
    if ($login) {
        $sitename = getSiteName().' - '.T_('powered by').' '.getCurrentVersion();
        $js = ' onload="document.getElementById(\'user\').focus()"';
    } else {
        // Don't translate
        $sitename = 'Family Connections';
        $js = '';
    }
    echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.T_('lang').'" lang="'.T_('lang').'">
<head>
<title>'.$sitename.'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="author" content="Ryan Haudenschilt"/>
<link rel="shortcut icon" href="themes/favicon.ico"/>
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css"/>
</head>
<body'.$js.'>';
}

/**
 * displayLogin 
 * 
 * @return void
 */
function displayLogin() {
    $sitename = getSiteName();
    if (isset($_GET['url'])) {
        $hidden = '<input type="hidden" name="url" id="url" value="' . cleanOutput($_GET['url']) . '"/>';
    } else {
        $hidden = '';
    }

    // Get available languages
    $lang_dir = "language/";
    $lang_options = '';
    if (is_dir($lang_dir))
    {
        if ($dh = opendir($lang_dir))
        {
            while (($file = readdir($dh)) !== false)
            {
                // Skip directories that start with a period
                if ($file[0] === '.') {
                    continue;
                }

                // Skip directories that don't include a messages.mo file
                if (!file_exists($lang_dir . $file . '/LC_MESSAGES/messages.mo')) {
                    continue;
                }

                $arr[$file] = getLangName($file);
            }
            closedir($dh);
            asort($arr);
            foreach($arr as $key => $val) {
                $lang_options .= '<option value="'.$key.'"';
                if (isset($_SESSION['language'])) {
                    if ($_SESSION['language'] == $key) {
                        $lang_options .= ' selected="selected"';
                    }
                }
                $lang_options .= '>'.$val.'</option>';
            }
        }
    }


    echo '
    <div id="login_box">
        <h1 id="login_header">'.T_('Login to').' '.$sitename.'</h1>
        <form action="index.php" method="post">
            <div style="float:right">
                <select style="background-color:#e9f3fb; border:none;" onchange="window.location.href=\'?lang=\'+this.options[this.selectedIndex].value;">
                    <option>Language:</option>
                    '.$lang_options.'
                </select>
            </div>
            <p><label for="user">'.T_('Username').':</label><input type="text" name="user" id="user"/></p>
            <p><label for="pass">'.T_('Password').':</label><input type="password" name="pass" id="pass"/></p>
            <p>
                <label class="rem" for="rem">'.T_('Remember Me').'</label>
                <input class="rem" name="rem" id="rem" type="checkbox" value="1"/>
                '.$hidden.'
                <input type="submit" name="submit" id="submit" value="'.T_('Login').'"/>
            </p>
            <div class="clear"></div>
        </form>
        <p class="center">
            <a href="lostpw.php">'.T_('Forgot Password?').'</a> | <a href="register.php">'.T_('Register').'</a>
        </p>
    </div>
</body>
</html>';
}
?>
