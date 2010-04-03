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

// Check that the user is logged in
isLoggedIn();
$current_user_id = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");
// Setup the Template variables;
$TMPL['pagetitle'] = _('Contact');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";

// Show Header
include_once(getTheme($current_user_id) . 'header.php');

echo '
        <div id="contact" class="centercontent">';
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
    echo '
            <p>'._('The following message has been sent to the Administrator:').'</p>
            <p>'.$msg.'<br/>- '.$name.'</p>';
} else {
    $email = $name = $subject = $msg = '';
    if (isset($_POST['email'])) { $email = htmlentities($_POST['email'], ENT_COMPAT, 'UTF-8'); }
    if (isset($_POST['name'])) { $name = htmlentities($_POST['name'], ENT_COMPAT, 'UTF-8'); }
    if (isset($_POST['subject'])) { $subject = htmlentities($_POST['subject'], ENT_COMPAT, 'UTF-8'); }
    if (isset($_POST['msg'])) { $msg = $_POST['msg']; }
    echo '
            <fieldset>
                <form method="post" class="contactform" action="contact.php">
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="email"><b>'._('Your Email').'</b></label></div>
                        <div class="field-widget"><input type="text" id="email" name="email" size="30" value="'.$email.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="name"><b>'._('Your Name').'</b></label></div>
                        <div class="field-widget"><input type="text" id="name" name="name" size="30" value="'.$name.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="subject"><b>'._('Subject').'</b></label></div>
                        <div class="field-widget"><input type="text" id="subject" name="subject" size="30" value="'.$subject.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="msg"><b>'._('Message').'</b></label></div>
                        <div class="field-widget"><textarea name="msg" rows="10" cols="40">'.$msg.'</textarea></div>
                    </div>
                    <p><input type="submit" name="submit" value="'._('Submit').'"/></p>
                </form>
            </fieldset>';
}
echo '
        </div><!-- #contact .centercontent -->';

// Show Footer
include_once(getTheme($current_user_id) . 'footer.php'); ?>
