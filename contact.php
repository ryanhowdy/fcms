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
// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['contact_title'];
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
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
                $email = $name = $subject = $msg = '';
				if (isset($_POST['email'])) { $email = htmlentities($_POST['email'], ENT_COMPAT, 'UTF-8'); }
				if (isset($_POST['name'])) { $name = htmlentities($_POST['name'], ENT_COMPAT, 'UTF-8'); }
				if (isset($_POST['subject'])) { $subject = htmlentities($_POST['subject'], ENT_COMPAT, 'UTF-8'); }
				if (isset($_POST['msg'])) { $msg = $_POST['msg']; }
				echo <<<HTML
            <fieldset>
                <form method="post" class="contactform" action="contact.php">
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="email"><b>{$LANG['your_email']}</b></label></div>
				        <div class="field-widget"><input type="text" id="email" name="email" size="30" value="{$email}"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="name"><b>{$LANG['your_name']}</b></label></div>
				        <div class="field-widget"><input type="text" id="name" name="name" size="30" value="{$name}"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="subject"><b>{$LANG['subject']}</b></label></div>
                        <div class="field-widget"><input type="text" id="subject" name="subject" size="30" value="{$subject}"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="msg"><b>{$LANG['message']}</b></label></div>
                        <div class="field-widget"><textarea name="msg" rows="10" cols="40">{$msg}</textarea></div>
                    </div>
                    <p><input type="submit" name="submit" value="{$LANG['submit']}"/></p>
                </form>
            </fieldset>
            <p>&nbsp;</p><p>&nbsp;</p>

HTML;
			} ?>
		</div><!-- #contact .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>
