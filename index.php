<?php
/*
Family Connections - a family oriented CMS -- http://www.familycms.com/

Copyright (C) 2007-08 Ryan Haudenschilt

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
include_once('inc/language.php');
if (!file_exists('inc/config_inc.php')) {
	displayHeader(false);
	echo "<div id=\"oops\"><h1>".$LANG['need_to_install1']."</h1><p>".$LANG['need_to_install2']." <a href=\"install.php\">".$LANG['need_to_install3']."</a> ".$LANG['need_to_install4']."</p></div></body></html>";
} else {
	include_once('inc/config_inc.php');
	include_once('inc/util_inc.php');
	if (isset($_POST['user'])) { $user = $_POST['user']; }
	if (isset($_POST['pass'])) { $pass = $_POST['pass']; }
	if (isset($_POST['rem'])) { $rem = $_POST['rem']; } else { $rem = 0; }
	mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
	mysql_select_db($cfg_mysql_db);
	if (!isset($_SESSION['login_id']) && !isset($_COOKIE['fcms_login_id']))  {
		if ((!isset($user)) || (!isset($pass))) {
			displayHeader();
			displayLogin();
			exit();
		}
		$user = escape_string($user);
		$pass = escape_string($pass);
		$pass = md5($pass);
		$sql = "SELECT * FROM `fcms_users` WHERE `username` = '$user' AND `password` = '$pass'";
		$result = mysql_query($sql) or displaySQLError('Login Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
		$login_check = mysql_num_rows($result);
		if($login_check > 0) {
			$sql = "SELECT `activated` FROM `fcms_users` WHERE `username` = '$user' AND `password` = '$pass'";
			$a = mysql_query($sql) or displaySQLError('Activated Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
			$answer = mysql_fetch_array($a);
			$account_is_active = $answer['activated'];
			if($account_is_active > 0) {
				while($row = mysql_fetch_array($result)) { 
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
					echo "<h3>".$LANG['login_success']."<h3><a href=\"home.php\">".$LANG['continue']."</a>.";
					echo "<meta http-equiv='refresh' content='0;URL=home.php'>";
				}
			} else {
				displayHeader();
				echo '<div class="err-msg"><h2>'.$LANG['err_active1'].'</h2><p>'.$LANG['err_active2'].'</p></div>';
				displayLogin();
			}
		} else { 
			displayHeader();
			echo '<div class="err-msg"><h2>'.$LANG['err_invalid1'].'</h2/><p>'.$LANG['err_invalid2'].'</p><p>'.$LANG['err_invalid3'].'</p></div>';
			displayLogin();
		}
	} else {
		if (isset($_COOKIE['fcms_login_id'])) {
			$_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
			$_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
			$_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
		}
		$sql = "UPDATE `fcms_users` SET `activity` = NOW() WHERE `id` = " . $_SESSION['login_id'];
		mysql_query($sql) or displaySQLError('Activity2 Error', 'index.php [' . __LINE__ . ']', $sql, mysql_error());
		echo "<h3>".$LANG['already_login']."</h3><a href=\"home.php\">".$LANG['continue']."</a>.";
		echo "<meta http-equiv='refresh' content='0;URL=home.php'>";
	}
}

function displayHeader($login = true) {
	global $LANG;
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
	echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$LANG['lang'].'" lang="'.$LANG['lang'].'"><head><title>';
	if ($login) { echo getSiteName() . ' - powered by ' . getCurrentVersion(); } else { echo  'Family Connections'; }
	echo '</title><link rel="stylesheet" type="text/css" href="themes/login.css" /></head><body';
	if ($login) { echo ' onload="document.getElementById(\'user\').focus()"'; }
	echo '>';
}
function displayLogin() {
	global $LANG;
	echo '<div id="login_box"><form action="index.php" method="post"><h1 id="login_header">'.$LANG['login_to'].' ' . getSiteName() . '</h1>'
		. '<p><label for="user">'.$LANG['username'].':</label><input type="text" name="user" id="user"/></p>'
		. '<p><label for="pass">'.$LANG['password'].':</label><input type="password" name="pass" id="pass"/><span><a href="lostpw.php">'.$LANG['forgot_pass'].'</a></span></p>'
		. '<p><label class="rem" for="rem">'.$LANG['remember_me'].'</label><input name="rem" id="rem" type="checkbox" value="1" /><input type="submit" name="submit" id="submit" value="'.$LANG['login'].'" /></p>'
		. '<div id="register">'.$LANG['no_account'].' <a href="register.php">'.$LANG['register_here'].'</a>.</div></form></div></body></html>';
} ?>