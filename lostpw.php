<?php
header("Cache-control: private");
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-us" lang="en-us">
<head>
<title><?php echo $cfg_sitename . " - " . $LANG['poweredby'] . " " . $stgs_release; ?></title>
<link rel="stylesheet" type="text/css" href="themes/login.css" />
</head>
<body onload="document.resetform.email.focus()">
	<?php
	if($_POST['email']) {
		$email = $_POST['email'];
		$link = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
		mysql_select_db($cfg_mysql_db, $link);
		$sql_check = mysql_query("SELECT * FROM `fcms_users` WHERE `email` = '$email'") or die("<h1>Email Error (lostpw.php 35)</h1>" . mysql_error());
		$sql_check_num = mysql_num_rows($sql_check);
		if($sql_check_num == 0) { 
			echo '<p class="error-alert">'.$LANG['err_email_not_found'].'</p>';
			displayForm();
		} else {
			$salt = "abchefghjkmnpqrstuvwxyz0123456789";
			srand((double)microtime()*1000000);
			$i = 0;
			while ($i <= 7) {
				$num = rand() % 33;
				$tmp = substr($salt, $num, 1);
				$pass = $pass . $tmp;
				$i++;
			}
			$new_pass = md5($pass);
			$sql = mysql_query("UPDATE fcms_users SET password='$new_pass' WHERE email='$email'") or die("<h1>New Password Error (lostpw.php 51)</h1>" . mysql_error());
			$subject = "$cfg_sitename ".$LANG['pw_reset']; 
			$message = $LANG['lost_pw_msg1']." $cfg_sitename ".$LANG['lost_pw_msg2']." 

".$LANG['lost_pw_msg3'].": $pass 

".$LANG['lost_pw_msg4']." 
".$LANG['lost_pw_msg5']." $cfg_sitename ".$LANG['lost_pw_msg6']."

".$LANG['lost_pw_msg7']; 

			mail($email, $subject, $message);
			echo "<p>".$LANG['pw_sent']."</p><p><a href=\"index.php\">".$LANG['continue']."</a></p>";
		}
	} else { displayForm(); } ?>
</body>
</html>
<?php
function displayForm() { 
	global $LANG; ?>
	<div id="login_box">
		<form name="resetform" method="post" action="lostpw.php">
			<h1 id="reset_header"><?php echo $LANG['reset_pw']; ?></h1>
			<p><label for="email"><?php echo $LANG['email']; ?></label> <input type="text" name="email" title="<?php echo $LANG['title_email']; ?>" size="25"/></p>
			<p><input type="submit" name="reset" value="<?php echo $LANG['reset_pw']; ?>"/></p>
			<br/>
			<div id="register"><a href="index.php"><?php echo $LANG['login']; ?></a></div>
		</form>
	</div><?php
} ?>