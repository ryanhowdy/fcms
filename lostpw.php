<?php
session_start();
header("Cache-control: private");
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-us" lang="en-us">
<head>
<title><?php echo getSiteName()." - "._('powered by')." ".getCurrentVersion(); ?></title>
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css" />
</head>
<body onload="document.resetform.email.focus()">
	<?php
	if(isset($_POST['email'])) {
		$email = $_POST['email'];
		$link = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
		mysql_select_db($cfg_mysql_db, $link);
		$sql = "SELECT * FROM `fcms_users` WHERE `email` = '$email'";
		$sql_check = mysql_query($sql) or displaySQLError('Email Error', 'lostpw.php [' . __LINE__ . ']', $sql, mysql_error());
		$sql_check_num = mysql_num_rows($sql_check);
		if($sql_check_num == 0) { 
			echo '
    <div class="err-msg">
        <p>'._('Your email address could not be found.  Please make sure you have entered it correctly.').'</p>
    </div>';
			displayForm();
		} else {
			$salt = "abchefghjkmnpqrstuvwxyz0123456789";
			srand((double)microtime()*1000000);
			$i = 0;
			$pass = 0;
			while ($i <= 7) {
				$num = rand() % 33;
				$tmp = substr($salt, $num, 1);
				$pass = $pass . $tmp;
				$i++;
			}
			$new_pass = md5($pass);
			$sql = "UPDATE `fcms_users` SET `password` = '$new_pass' WHERE `email` = '$email'";
			mysql_query($sql) or displaySQLError('Update Password Error', 'lostpw.php [' . __LINE__ . ']', $sql, mysql_error());
			$subject = getSiteName()." "._('Password Reset');
            $sitename = getSiteName();
			$message = sprintf(_('Your password at %s has been reset.'), $sitename)." 

"._('New Password').": $pass 

"._('Thanks')." 
".sprintf(_('The %s Webmaster'), $sitename)."

"._('This is an automated message, please do not reply.');

			mail($email, $subject, $message, $email_headers);
			echo '
    <div class="err-msg">
        <p>'._('Your password has been reset, please check your email.').'</p>
        <p><a href="index.php">'._('Continue').'</a></p>
    </div>';
		}
	} else {
        displayForm();
    } ?>
</body>
</html>
<?php
function displayForm() {
    echo '
    <div id="login_box">
        <p>&nbsp;</p>
        <h1 id="reset_header">'._('Reset Password').'</h1>
        <p>&nbsp;</p>
        <form name="resetform" method="post" action="lostpw.php">
            <p>
                <label for="email">'._('Email').'</label> 
                <input type="text" name="email" id="email" title="'._('The email address associated with your account.').'" size="25"/>
            </p>
            <p>
                <a class="cancel" href="index.php">'._('Cancel').'</a> 
                <input id="reset" name="reset" type="submit" value="'._('Reset Password').'"/>
            </p>
            <div class="clear"></div>
            <br/>
        </form>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
	</div>';
} ?>