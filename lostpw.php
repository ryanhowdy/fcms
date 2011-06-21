<?php
session_start();

include_once('inc/config_inc.php');
include_once('inc/util_inc.php');

fixMagicQuotes();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo T_('lang'); ?>" lang="<?php echo T_('lang'); ?>">
<head>
<title><?php echo getSiteName()." - ".T_('powered by')." ".getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="author" content="Ryan Haudenschilt" />
<link rel="shortcut icon" href="themes/favicon.ico"/>
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css" />
</head>
<body onload="document.resetform.email.focus()"><?php

// Resset PW
if (isset($_POST['email'])) {

    $email = cleanInput($_POST['email']);
    $link = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
    mysql_select_db($cfg_mysql_db, $link);
    $sql = "SELECT `id` 
            FROM `fcms_users` 
            WHERE `email` = '$email'";
    $sql_check = mysql_query($sql) or displaySQLError('Email Error', 'lostpw.php [' . __LINE__ . ']', $sql, mysql_error());
    $sql_check_num = mysql_num_rows($sql_check);

    // Invalid email
    if ($sql_check_num == 0) { 
        echo '
    <div class="err-msg">
        <p>'.T_('Your email address could not be found.  Please make sure you have entered it correctly.').'</p>
    </div>';
        displayForm();

    // Found email
    } else {

        // Create new PW
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

        // Set new PW
        $sql = "UPDATE `fcms_users` 
                SET `password` = '$new_pass' 
                WHERE `email` = '$email'";
        mysql_query($sql) or displaySQLError(
            'Update Password Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        // Send email
        $subject = getSiteName()." ".T_('Password Reset');
        $sitename = getSiteName();
        $message = sprintf(T_('Your password at %s has been reset.'), $sitename)." 

".T_('New Password').": $pass 

".T_('Thanks')." 
".sprintf(T_('The %s Webmaster'), $sitename)."

".T_('This is an automated message, please do not reply.');

        $email_headers = getEmailHeaders();
        mail($email, $subject, $message, $email_headers);
        echo '
    <div class="err-msg">
        <p>'.T_('Your password has been reset, please check your email.').'</p>
        <p><a href="index.php">'.T_('Continue').'</a></p>
    </div>';
    }

// Show form for resetting pw
} else {
    displayForm();
} ?>
</body>
</html>
<?php

/**
 * displayForm 
 * 
 * @return void
 */
function displayForm()
{
    echo '
    <div id="login_box">
        <p>&nbsp;</p>
        <h1 id="reset_header">'.T_('Reset Password').'</h1>
        <p>&nbsp;</p>
        <form name="resetform" method="post" action="lostpw.php">
            <p>
                <label for="email">'.T_('Email').'</label> 
                <input type="text" name="email" id="email" title="'.T_('The email address associated with your account.').'" size="25"/>
            </p>
            <p>
                <a class="cancel" href="index.php">'.T_('Cancel').'</a> 
                <input id="reset" name="reset" type="submit" value="'.T_('Reset Password').'"/>
            </p>
            <div class="clear"></div>
            <br/>
        </form>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
	</div>';
} ?>
