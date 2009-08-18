<?php
session_start();
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	$_POST = array_map('stripslashes', $_POST);
	$_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');
if (isset($_SESSION['login_id'])) {
	if (!isLoggedIn($_SESSION['login_id'], $_SESSION['login_uname'], $_SESSION['login_pw'])) {
		displayLoginPage();
		exit();
	}
} elseif (isset($_COOKIE['fcms_login_id'])) {
	if (isLoggedIn($_COOKIE['fcms_login_id'], $_COOKIE['fcms_login_uname'], $_COOKIE['fcms_login_pw'])) {
		$_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
		$_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
		$_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
	} else {
		displayLoginPage();
		exit();
	}
} else {
	displayLoginPage();
	exit();
}
header("Cache-control: private");
$pagetitle = $LANG['contact_title'];
$d = "";
$admin_d = "admin/";
include_once(getTheme($_SESSION['login_id']) . 'header.php');
?>
	<div id="leftcolumn">
        <?php
        include_once(getTheme($_SESSION['login_id']) . 'sidenav.php');
        if (checkAccess($_SESSION['login_id']) < 3) {
            include_once(getTheme($_SESSION['login_id']) . 'adminnav.php');
        }
        ?>
	</div>
	<div id="content">
		<div id="contact" class="centercontent">
			<?php
			if (!empty($_POST['subject']) && !empty($_POST['email']) && !empty($_POST['name']) && !empty($_POST['msg'])) {
				$subject = $_POST['subject'];
				$email = $_POST['email'];
				$name = $_POST['name'];
				$msg = $_POST['msg'];
				$email_headers = 'From: ' . $name . ' <' . $email . '>' . "\r\n" . 
					'Reply-To: ' . getContactEmail() . "\r\n" . 
					'Content-Type: text/plain; charset=UTF-8;' . "\r\n" . 
					'MIME-Version: 1.0' . "\r\n" . 
					'X-Mailer: PHP/' . phpversion();
				mail(getContactEmail(), $subject, "$msg\r\n-$name", $email_headers);
				echo $LANG['msg_received']."<br/>";
				echo "<p>$msg<br/>- $name</p>";
			} else {
				echo "<br/>\n\t\t\t<form method=\"post\" class=\"contactform\" action=\"contact.php\">\n\t\t\t\t";
				echo "<p><label for=\"email\">" . $LANG['your_email'] . ":";
				if (isset($_POST['submit']) && empty($_POST['email'])) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
				echo "</label><input type=\"text\" id=\"email\" name=\"email\" size=\"30\"";
				if (isset($_POST['email'])) { echo " value=\"" . htmlentities($_POST['email'], ENT_COMPAT, 'UTF-8') . "\""; }
				echo "/></p>\n\t\t\t\t<p><label for=\"name\">" . $LANG['your_name'] . ":";
				if (isset($_POST['submit']) && empty($_POST['name'])) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
				echo "</label><input type=\"text\" id=\"name\" name=\"name\" size=\"30\"";
				if (isset($_POST['name'])) { echo " value=\"" . htmlentities($_POST['name'], ENT_COMPAT, 'UTF-8') . "\""; }
				echo "/></p>\n\t\t\t\t<p><label for=\"subject\">" . $LANG['subject'] . ":";
				if (isset($_POST['submit']) && empty($_POST['subject'])) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
				echo "</label><input type=\"text\" id=\"subject\" name=\"subject\" size=\"30\"";
				if (isset($_POST['subject'])) { echo " value=\"" . htmlentities($_POST['subject'], ENT_COMPAT, 'UTF-8') . "\""; }
				echo "/></p>\n\t\t\t\t<p><label for=\"msg\">" . $LANG['message'] . ":";
				if (isset($_POST['submit']) && empty($_POST['msg'])) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
				echo "</label><textarea name=\"msg\" rows=\"10\" cols=\"40\">";
				if (isset($_POST['msg'])) { echo $_POST['msg']; }
				echo "</textarea></p>\n\t\t\t\t";
				echo "<p><input type=\"submit\" name=\"submit\" value=\"".$LANG['submit']."\"/></p>\n\t\t\t";
				echo "</form>\n\t\t\t<p>&nbsp;</p><p>&nbsp;</p>\n";	
			} ?>
		</div><!-- #contact .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>