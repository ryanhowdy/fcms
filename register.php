<?php
session_start();

include_once('inc/config_inc.php');
include_once('inc/util_inc.php');

fixMagicQuotes();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo T_('lang'); ?>" lang="<?php echo T_('lang'); ?>">
<head>
<title><?php T_('Register for').' '.getSiteName(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css" />
<script type="text/javascript" src="inc/js/prototype.js"></script>
<script type="text/javascript" src="inc/js/livevalidation.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, 'load', function() {
    var u = $('username');
    u.focus();
    u.onchange = function(){
        checkAvailability();
    }
});
var url = "inc/checkAvailability.php"; 
function checkAvailability() {
    new Ajax.Request(url, {
        method: 'get',
        parameters: { username: $('username').value },
        onSuccess: process,
        onFailure: function() { 
        alert("There was an error with the connection"); 
        }
    });
}
function process(transport) {
    var response = transport.responseText;
    if (response == 'available') {
        // do nothing
    } else {
        var u = $('username');
        var s = document.createElement('span');
        u.addClassName('LV_invalid_field');
        s.addClassName('LV_validation_message LV_invalid');
        s.appendChild(document.createTextNode('That username has already been taken.'));
        u.insert({'after':s});
    }
}
//]]>
</script>
</head>
<body>
<?php
mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
mysql_select_db($cfg_mysql_db);

if (!isRegistrationOn())
{
    echo '<div id="column"><p class="error-alert">'.T_('Registration is closed.').'</p></div></body></html>';
    return;
}

if (isset($_POST['submit'])) {
    $email      = cleanInput($_POST['email']);
    $username   = cleanInput($_POST['username']);

	$result = mysql_query("SELECT `email` FROM `fcms_users` WHERE `email` = '$email'"); 
	$email_check = mysql_num_rows($result);

	$result = mysql_query("SELECT `username` FROM `fcms_users` WHERE `username` = '$username'"); 
	$username_check = mysql_num_rows($result);

	if (
        strlen($_POST['username']) < 1 ||
        strlen($_POST['password']) < 1 ||
        strlen($_POST['fname']) < 1 ||
        strlen($_POST['lname']) < 1 ||
        strlen($_POST['email']) < 1
    ) {
		displayForm('<p class="error">'.T_('You forgot to fill out a required field.').'</p>');
	} elseif ($email_check > 0) {
		displayForm(
            '<p class="error">
                '.T_('The email you have choosen is already in use.  Please choose a different email.').' 
                <a href="lostpw.php">'.T_('If you have forgotten your password please reset it').'</a></p>'
        );
	} elseif ($username_check > 0) {
		displayForm(
            '<p class="error">'.T_('Sorry, but that username is already taken.  Please choose another username.').'</p>'
        );
	} else {
		$fname      = cleanInput($_POST['fname']);
		$lname      = cleanInput($_POST['lname']);
		$password   = cleanInput($_POST['password']);
		$md5pass    = md5($password);

		$sql = "INSERT INTO `fcms_users`
                    (`access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`) 
                VALUES (
                    3, 
                    NOW(), 
                    '$fname', 
                    '$lname', 
                    '$email', 
                    '$username', 
                    '$md5pass'
                )";
		mysql_query($sql) or displaySQLError(
            'New User Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
		$lastid = mysql_insert_id();

		$sql = "INSERT INTO `fcms_user_settings`(`user`) VALUES ($lastid)";
		mysql_query($sql) or displaySQLError(
            'User Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

		$sql = "INSERT INTO `fcms_address`(`user`, `updated`) 
                VALUES ($lastid, NOW())";
		mysql_query($sql) or displaySQLError(
            'New Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sitename = getSiteName();
		$subject = $sitename.' '.T_('Membership');
        // TODO
        // Maybe we should get the timezone of the admin account
        // and use that to format this date
		$now = gmdate('F j, Y, g:i a');
		$subject2 = sprintf(T_('New User Registration at %s'), $sitename);
		$message2 = sprintf(T_('A new user has registered at %s'), $sitename).':

'.T_('Time of Registration').': '.$now.'

'.T_('Username').': '.$username.'
'.T_('Name').': '.$fname.' '.$lname;

		$sql = "SELECT `auto_activate` FROM `fcms_config`";
		$result = mysql_query($sql) or displaySQLError(
            'Activation Check Error', __FILE__.' [' . __LINE__ . ']', $sql, mysql_error()
        );
		$row = mysql_fetch_assoc($result);
		if ($row['auto_activate'] == 1) {
			$code = uniqid(''); //bug in some versions of php, needs some value here
			$sql = "UPDATE `fcms_users` 
                    SET `activate_code` = '$code' 
                    WHERE `id` = '$lastid'";
			mysql_query($sql) or displaySQLError(
                'Activation Code Error', __FILE__.' [' . __LINE__ . ']', $sql, mysql_error()
            );
			$message = T_('Please click the following link to activate your account').':

'.getDomainAndDir().'activate.php?uid='.$lastid.'&code='.$code;
			echo '
            <div id="msg">
                <h1>'.T_('Congratulations and Welcome').'</h1>
                <p>
                    '.sprintf(T_('You have been successfully registered at %s.'), $sitename).' 
                    '.sprintf(T_('Your account information has been emailed to %s.'), $email).'<br/>
                    <b>'.T_('Please remember your username and password for this site.').'</b>
                </p>
                <p>'.T_('Unfortunately you must activate your account before you can <a href="index.php">login</a> and begin using the site').'</p>
            </div>';
			mail($email, $subject, $message, getEmailHeaders());
		} elseif ($row['auto_activate'] == 0) {
			$message = T_('Dear').' '.escape_string($fname).' '.escape_string($lname).', 

'.sprintf(T_('Thank you for registering at %s'), $sitename).'

'.T_('In order to login and beging using the site, your administrator must activate your account.  You will get an email when this has been done.').'

'.T_('After your account is activated you can login using the following information').':
'.T_('Username').': '.$username.' 
'.T_('Password').': '.$password.' 

'.T_('Thanks').',  
'.sprintf(T_('The %s Webmaster'), $sitename).'

'.T_('This is an automated response, please do not reply.');
			echo '
            <div id="msg">
                <h1>'.T_('Congratulations and Welcome').'</h1>
                <p>
                    '.sprintf(T_('You have been successfully registered at %s.'), $sitename).' 
                    '.sprintf(T_('Your account information has been emailed to %s.'), $email).'<br/>
                    <b>'.T_('Please remember your username and password for this site.').'</b>
                </p>
                <p>'.T_('Unfortunately your account must be activated before you can  <a href="index.php">login</a> and begin using the site.').'</p>
            </div>';
			mail($email, $subject, $message, getEmailHeaders());
		}
		mail(getContactEmail(), $subject2, $message2, getEmailHeaders());
	}
} else { displayForm(); } ?>
</body>
</html>

<?php
function displayForm ($error = '0')
{
    $user   = isset($_POST['username']) ? cleanOutput($_POST['username'])   : '';
    $first  = isset($_POST['fname'])    ? cleanOutput($_POST['fname'])      : '';
    $last   = isset($_POST['lname'])    ? cleanOutput($_POST['lname'])      : '';
    $email  = isset($_POST['email'])    ? cleanOutput($_POST['email'])      : '';
    echo '
	<div id="column">
        <h1>'.T_('Register').'</h1>';
	if ($error !== '0') {
        echo $error;
    }
    echo '
        <form id="registerform" name="registerform" action="register.php" method="post">
            <div class="field-row">
                <div class="field-label"><label for="username"><b>'.T_('Username').'</b> <span class="req">*</span></label></div>
                <div class="field-widget clearfix">
                    <input type="text" name="username" id="username" title="'.T_('Used for logging into the site.').'" value="'.$user.'"/>
                </div>
            </div>
            <script type="text/javascript">
                var funame = new LiveValidation(\'username\', { onlyOnSubmit: true });
                funame.add(Validate.Presence, {failureMessage: "'.T_('Required: Can\' login without one.').'"});
            </script>
            <div class="field-row">
                <div class="field-label"><label for="password"><b>'.T_('Password').'</b> <span class="req">*</span></label></div>
                <div class="field-widget clearfix">
                    <input type="password" name="password" id="password" title="'.T_('Used for loggin into the site.').'"/>
                </div>
            </div>
            <script type="text/javascript">
                var fpass = new LiveValidation(\'password\', { onlyOnSubmit: true });
                fpass.add(Validate.Presence, {failureMessage: "'.T_('Required: Can\'t login without one.').'"});
            </script>
            <div class="field-row">
                <div class="field-label"><label for="fname"><b>'.T_('First Name').'</b> <span class="req">*</span></label></div>
                <div class="field-widget clearfix">
                    <input type="text" name="fname" id="fname" title="'.T_('The name your mother gave you.').'" value="'.$first.'"/>
                </div>
            </div>
            <script type="text/javascript">
                var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                ffname.add(Validate.Presence, { failureMessage: "'.T_('Required').'" });
            </script>
            <div class="field-row">
                <div class="field-label"><label for="lname"><b>'.T_('Last Name').'</b> <span class="req">*</span></label></div>
                <div class="field-widget clearfix">
                    <input type="text" name="lname" id="lname" title="'.T_('Your family name.').'" value="'.$last.'"/>
                </div>
            </div>
            <script type="text/javascript">
                var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                flname.add(Validate.Presence, {failureMessage: "'.T_('Required').'"});
            </script>
            <div class="field-row">
                <div class="field-label"><label for="email"><b>'.T_('Email Addrss').'</b> <span class="req">*</span></label></div>
                <div class="field-widget clearfix">
                    <input type="text" name="email" id="email" title="'.T_('Where can we send validation and updates?').'" value="'.$email.'"/>
                </div>
            </div>
            <script type="text/javascript">
                var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                femail.add( Validate.Presence, { failureMessage: "'.T_('Required').'" } );
                femail.add( Validate.Email, { failureMessage: "'.T_('Invalid Email').'" } );
                femail.add( Validate.Length, { minimum: 10 } );
            </script>
            <p>
                <a class="cancel" href="index.php">'.T_('Cancel').'</a> 
                <input id="submit" name="submit" type="submit"  value="'.T_('Submit').'"/>
            </p>
            <div class="clear"></div>
        </form>
	</div>';
} ?>
