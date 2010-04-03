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

if (!file_exists('inc/config_inc.php')) {

    // Setup php-gettext
    include_once('inc/gettext.inc');
    T_setlocale(LC_MESSAGES, 'en_US');
    bindtextdomain('messages', './language');
    if (function_exists('bind_textdomain_codeset')) {
      bind_textdomain_codeset('messages', 'UTF-8');
    }
    textdomain('messages');

    displayHeader(false);
    echo '
    <div id="oops">
        <h1>'._('Oops!').'</h1>
        <p>
            '._('This site hasn\'t been installed yet.').' 
            <a href="install.php">'._('You must finish the installation before using the site.').'</a>
        </p>
    </div>
</body>
</html>';
} else {
    include_once('inc/config_inc.php');
    include_once('inc/util_inc.php');
    if (isset($_POST['user'])) { $user = $_POST['user']; }
    if (isset($_POST['pass'])) { $pass = $_POST['pass']; }
    if (isset($_POST['rem'])) { $rem = $_POST['rem']; } else { $rem = 0; }
    mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
    mysql_select_db($cfg_mysql_db);

    // Change language
    if (isset($_GET['lang'])) {
        $_SESSION['language'] = $_GET['lang'];
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
        <h2>'._('Access Denied').'</h2>
        <p>'._('You must be logged in to view that page.').'</p>
    </div>';
            displayLogin();
            exit();
        } elseif ($_GET['err'] == 'off') {
            displayHeader();
            echo '
    <div class="err-msg">
        <h2>'._('Hold On a Second!').'</h2>
        <p>'._('The site has been closed by an administrator.').'</p>
        <p>'._('Please come back later.').'</p>
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
        $user = escape_string($user);
        $pass = escape_string($pass);
        $pass = md5($pass);
        $sql = "SELECT * FROM `fcms_users` WHERE `username` = '$user' AND `password` = '$pass'";
        $result = mysql_query($sql) or displaySQLError('Login Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
        $login_check = mysql_num_rows($result);
        $row = mysql_fetch_array($result);

        // User's username and password match the db
        if ($login_check > 0) {

            // User is active
            if ($row['activated'] > 0) {
                if ($rem >= 1) {
                    setcookie('fcms_login_id', $row['id'], time() + (30*(24*3600)), '/');  // 30 days
                    setcookie('fcms_login_uname', $row['username'], time() + (30*(24*3600)), '/');  // 30 days
                    setcookie('fcms_login_pw', $row['password'], time() + (30*(24*3600)), '/');  // 30 days
                } else {
                    $_SESSION['login_id'] = $row['id'];
                    $_SESSION['login_uname'] = $row['username'];
                    $_SESSION['login_pw'] = $row['password'];
                }
                $sql = "UPDATE `fcms_users` SET `activity` = NOW() WHERE `id` = " . $row['id'];
                mysql_query($sql) or displaySQLError('Activity Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
                $sql = "UPDATE `fcms_users` SET `login_attempts` = '0' WHERE `id` = " . $row['id'];
                mysql_query($sql) or displaySQLError('Login Attempt Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
                header("Location: $redirect");

            // User has been locked out for failed attempts
            } elseif ($row['activated'] < 0) {

                // User's lockout has ended
                if (gmdate('YmdHis') > gmdate('YmdHis', strtotime($row['locked']))) {
                    $sql = "UPDATE `fcms_users` SET `activated` = '1' WHERE `id` = " . $row['id'];
                    mysql_query($sql) or displaySQLError('Activated Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
                    if ($rem >= 1) {
                        setcookie('fcms_login_id', $row['id'], time() + (30*(24*3600)), '/');  // 30 days
                        setcookie('fcms_login_uname', $row['username'], time() + (30*(24*3600)), '/');  // 30 days
                        setcookie('fcms_login_pw', $row['password'], time() + (30*(24*3600)), '/');  // 30 days
                    } else {
                        $_SESSION['login_id'] = $row['id'];
                        $_SESSION['login_uname'] = $row['username'];
                        $_SESSION['login_pw'] = $row['password'];
                    }
                    $sql = "UPDATE `fcms_users` SET `activity` = NOW() WHERE `id` = " . $row['id'];
                    mysql_query($sql) or displaySQLError('Activity Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
                    $sql = "UPDATE `fcms_users` SET `login_attempts` = '0' WHERE `id` = " . $row['id'];
                    mysql_query($sql) or displaySQLError('Login Attempt Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
                    header("Location: $redirect");

                // User is still locked out
                } else {
                    displayHeader();
                    echo '
    <div class="err-msg">
        <h2>'._('Hold On a Second!').'</h2>
        <p>'._('You have exceeded the number of allowed login attempts.').'</p>
        <p>'._('Your account has been locked for 1 hour.').'</p>
    </div>';
                    displayLogin();
                }

            // User is not active
            } else {
                displayHeader();
                echo '
    <div class="err-msg">
        <h2>'._('Not So Fast').'</h2>
        <p>'._('Your account isn\'t active yet.  Your website administrator must activate your account before you can login and begin using the website.').'</p>
    </div>';
                displayLogin();
            }

        // Username and password do not match the db
        } else {
            $sql = "SELECT * FROM `fcms_users` WHERE `username` = '$user'";
            $result = mysql_query($sql) or displaySQLError('Valid Username Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
            $valid_username = mysql_num_rows($result);

            // User has a valid username, but they entered the wrong pw
            if ($valid_username > 0) {
                $r = mysql_fetch_array($result);

                // User has exceeded the max login attempts, lock the account
                if ($r['login_attempts'] > 4) {
                    $sql = "UPDATE `fcms_users` SET `activated` = '-1', `locked` = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE `id` = " . $r['id'];
                    mysql_query($sql) or displaySQLError('Login Limit Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
                    displayHeader();
                    echo '
    <div class="err-msg">
        <h2>'._('Hold On a Second!').'</h2>
        <p>'._('You have exceeded the number of allowed login attempts.').'</p>
        <p>'._('Your account has been locked for 1 hour.').'</p>
    </div>';
                    displayLogin();
                    exit(0);
                }
                $sql = "UPDATE `fcms_users` SET `login_attempts` = `login_attempts`+1 WHERE `id` = " . $r['id'];
                mysql_query($sql) or displaySQLError('Login Attempt Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
            }
            displayHeader();
            echo '
    <div class="err-msg">
        <h2>'._('Oops!').'</h2/>
        <p>'._('That login information wasn\'t quite right.').'</p>
        <p>'._('Be sure and check that you typed your username/password correctly.').'</p>
    </div>';
            displayLogin();
        }

    // User has a valid session/cookie
    } else {
        if (isset($_COOKIE['fcms_login_id'])) {
            $_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
            $_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
            $_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
        }
        $sql = "UPDATE `fcms_users` SET `activity` = NOW() WHERE `id` = " . escape_string($_SESSION['login_id']);
        mysql_query($sql) or displaySQLError('Activity Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
        $sql = "UPDATE `fcms_users` SET `login_attempts` = '0' WHERE `id` = " . escape_string($_SESSION['login_id']);
        mysql_query($sql) or displaySQLError('Login Attempt Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
        // We are always going to redirect to home.php if they have a valid session/cookie
        header("Location: $redirect");
    }
}

function displayHeader($login = true) {
    if ($login) {
        $sitename = getSiteName().' - '._('powered by').' '.getCurrentVersion();
        $js = ' onload="document.getElementById(\'user\').focus()"';
    } else {
        // Don't translate
        $sitename = 'Family Connections';
        $js = '';
    }
    echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'._('lang').'" lang="'._('lang').'">
<head>
<title>'.$sitename.'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="author" content="Ryan Haudenschilt"/>
<link rel="shortcut icon" href="themes/favicon.ico"/>
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css"/>
</head>
<body'.$js.'>';
}

function displayLogin() {
    $sitename = getSiteName();
    if (isset($_GET['url'])) {
        $hidden = '<input type="hidden" name="url" id="url" value="' . $_GET['url'] . '"/>';
    } else {
        $hidden = '';
    }

    // Get available languages
    $dir = "language/";
    $lang_options = '';
    if (is_dir($dir))    {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if (filetype($dir . $file) === "dir" && 
                    $file !== "." && 
                    $file !== ".." 
                ) {
                    $arr[$file] = getLangName($file);
                }
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
        <h1 id="login_header">'._('Login to').' '.$sitename.'</h1>
        <form action="index.php" method="post">
            <div style="float:right">
                <select style="background-color:#e9f3fb; border:none;" onchange="window.location.href=\'?lang=\'+this.options[this.selectedIndex].value;">
                    <option>Language:</option>
                    '.$lang_options.'
                </select>
            </div>
            <p><label for="user">'._('Username').':</label><input type="text" name="user" id="user"/></p>
            <p><label for="pass">'._('Password').':</label><input type="password" name="pass" id="pass"/></p>
            <p>
                <label class="rem" for="rem">'._('Remember Me').'</label>
                <input class="rem" name="rem" id="rem" type="checkbox" value="1"/>
                '.$hidden.'
                <input type="submit" name="submit" id="submit" value="'._('Login').'"/>
            </p>
            <div class="clear"></div>
        </form>
        <p class="center">
            <a href="lostpw.php">'._('Forgot Password?').'</a> | <a href="register.php">'._('Register').'</a>
        </p>
    </div>
</body>
</html>';
}
?>