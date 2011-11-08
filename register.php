<?php
/**
 * Register
 *  
 * PHP versions 4 and 5
 *  
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

require 'fcms.php';

load('facebook', 'socialmedia');

control();
exit();

/**
 * control 
 * 
 * The controlling structure for this page.
 * 
 * @return void
 */
function control ()
{
    if (!isRegistrationOn())
    {
        displayClosed();
    }
    elseif (isset($_GET['ajax'])) {
        checkUsername();
    }
    elseif (isset($_GET['facebook'])) {
        handleFacebookRegister();
    }
    elseif (isset($_POST['submit']))
    {
        displaySubmit();
    }
    else
    {
        displayForm();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    print '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.T_('lang').'" lang="'.T_('lang').'">
<head>
<title>'.sprintf(T_pgettext('%s is the name of the website', 'Register for %s.'), getSiteName()).'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css" />
<script type="text/javascript" src="inc/js/prototype.js"></script>
<script type="text/javascript" src="inc/js/livevalidation.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, "load", function() {
    var u = $("username");
    u.focus();
    u.onchange = function(){
        checkAvailability();
    }
});
var url = "register.php";
function checkAvailability() {
    new Ajax.Request(url, {
        method: "get",
        parameters: { ajax: 1, username: $("username").value },
        onSuccess: process,
        onFailure: function() { alert("'.T_('There was an error with the connection.').'"); }
    });
}
function process(transport) {
    var response = transport.responseText;
    var u = $("username");
    var s = document.createElement("span");

    if (response == "available") {
        s.addClassName("available");
        s.appendChild(document.createTextNode("'.T_('Available').'"));
        u.insert({"after":s});
    } else if (response == "unavailable") {
        u.addClassName("LV_invalid_field");
        s.addClassName("LV_validation_message LV_invalid");
        s.appendChild(document.createTextNode("'.T_('That username has already been taken.').'"));
        u.insert({"after":s});
    } else {
        alert("'.T_('Could not check availability of username.').'");
    }
}
//]]>
</script>
</head>
<body>';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    echo '
</body>
</html>';
}

/**
 * displayClosed 
 * 
 * @return void
 */
function displayClosed ()
{
    displayHeader();

    echo '
    <div id="column"><p class="error-alert">'.T_('Registration is closed.').'</p></div>';

    displayFooter();
    die();
}

/**
 * displaySubmit 
 * 
 * @param string $params The params that have been submitted to the form.
 * 
 * @return void
 */
function displaySubmit ($params = '')
{
    displayHeader();

    if ($params == '')
    {
        $formData = $_POST;
    }
    else
    {
        $formData = $params;
    }

    // Make sure they filled out all required fields
    $required_fields = array('username', 'password', 'fname', 'lname', 'email');
    foreach ($required_fields as $f)
    {
        if (strlen($formData[$f]) < 1)
        {
            displayHtmlForm('<p class="error">'.T_('You forgot to fill out a required field.').'</p>');
            displayFooter();
            return;
        }
    }

    $email    = cleanInput($formData['email']);
    $username = cleanInput($formData['username']);
    $fname    = cleanInput($formData['fname']);
    $lname    = cleanInput($formData['lname']);
    $password = cleanInput($formData['password']);

    if ($params == '')
    {
        $password = md5($password);
    }

    // Is email available?
    $result      = mysql_query("SELECT `email` FROM `fcms_users` WHERE `email` = '$email'"); 
    $email_check = mysql_num_rows($result);

    if ($email_check > 0)
    {
        displayHtmlForm(
            '<p class="error">'.T_('The email you have choosen is already in use.  Please choose a different email.').' <a href="lostpw.php">'.T_('If you have forgotten your password please reset it').'</a></p>'
        );
        displayFooter();
        return;
    }

    // Is username availabel?
    $result         = mysql_query("SELECT `username` FROM `fcms_users` WHERE `username` = '$username'"); 
    $username_check = mysql_num_rows($result);

    if ($username_check > 0)
    {
        displayHtmlForm(
            '<p class="error">'.T_('Sorry, but that username is already taken.  Please choose another username.').'</p>'
        );
        displayFooter();
        return;
    }

    $sex = isset($formData['sex']) ? $formData['sex'] : 'M';

    // Create new user
    $sql = "INSERT INTO `fcms_users`
                (`access`, `joindate`, `fname`, `lname`, `sex`, `email`, `username`, `password`) 
            VALUES (
                3, 
                NOW(), 
                '$fname', 
                '$lname', 
                '$sex', 
                '$email', 
                '$username', 
                '$password'
            )";
    if (!mysql_query($sql))
    {
        displaySQLError('New User Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $lastid = mysql_insert_id();

    $fbAccessToken = isset($formData['accessToken']) ? $formData['accessToken'] : '';

    // Create user's settings
    $sql = "INSERT INTO `fcms_user_settings`(`user`, `fb_access_token`)
            VALUES ($lastid, '$fbAccessToken')";
    if (!mysql_query($sql))
    {
        displaySQLError('New Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    // Create user's address
    $sql = "INSERT INTO `fcms_address`(`user`, `updated`) 
            VALUES ($lastid, NOW())";
    if (!mysql_query($sql))
    {
        displaySQLError('New Address Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    // Setup some stuff for sending email
    $sitename = getSiteName();
    $now      = gmdate('F j, Y, g:i a'); // TODO: use admin's tz?
    $subject  = $sitename.' '.T_('Membership');
    $message  = '';

    // Which activation method?
    $sql = "SELECT `value` AS 'auto_activate'
            FROM `fcms_config`
            WHERE `name` = 'auto_activate'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Activation Check Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $row = mysql_fetch_assoc($result);

    // Auto activation
    if ($row['auto_activate'] == 1)
    {
        handleAutoActivation($email, $subject, $lastid, $sitename);
    }
    elseif ($row['auto_activate'] == 0)
    {
        $message = T_('Dear').' '.escape_string($fname).' '.escape_string($lname).', 

'.sprintf(T_('Thank you for registering at %s'), $sitename).'

'.T_('In order to login and begin using the site, your administrator must activate your account.  You will get an email when this has been done.').'

'.T_('After your account is activated you can login using the following information').':
'.T_('Username').': '.$username.' 

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

    // Email the admin
    $admin_subject = sprintf(T_('New User Registration at %s'), $sitename);
    $admin_message = sprintf(T_('A new user has registered at %s'), $sitename).':

'.T_('Time of Registration').': '.$now.'

'.T_('Username').': '.$username.'
'.T_('Name').': '.$fname.' '.$lname;

    mail(getContactEmail(), $admin_subject, $admin_message, getEmailHeaders());
}

/**
 * displayForm 
 * 
 * @return void
 */
function displayForm ()
{
    displayHeader();
    displayHtmlForm();
    displayFooter();
}

/**
 * displayHtmlForm 
 * 
 * @param string $error Any errors from the previous form
 * 
 * @return void
 */
function displayHtmlForm ($error = '0')
{
    $user  = isset($_POST['username']) ? cleanOutput($_POST['username'])   : '';
    $first = isset($_POST['fname'])    ? cleanOutput($_POST['fname'])      : '';
    $last  = isset($_POST['lname'])    ? cleanOutput($_POST['lname'])      : '';
    $email = isset($_POST['email'])    ? cleanOutput($_POST['email'])      : '';

    $fbData = getFacebookConfigData();

    $fbUser   = null;
    $facebook = null;

    echo '
    <div id="column">
        <h1>'.T_('Register').'</h1>';

    if ($error !== '0')
    {
        echo $error;
    }

    // Print the facebook register button
    if (!empty($fbData['fb_app_id']) && !empty($fbData['fb_secret']))
    {
        $facebook = new Facebook(array(
            'appId'  => $fbData['fb_app_id'],
            'secret' => $fbData['fb_secret'],
        ));

        // Check if the user is logged in and authed
        $fbUser = $facebook->getUser();
        if ($fbUser)
        {
            try
            {
                $fbProfile = $facebook->api('/me');
            }
            catch (FacebookApiException $e)
            {
                $fbUser = null;
            }
        }
    }

    if ($fbUser && !isset($_GET['normal']))
    {
        echo '
        <p style="text-align:center; padding: 20px 0">
            <a class="fbbutton" href="?facebook=1">'.T_('Register with Facebook').'</a><br/><br/><br/>
            <small><a style="text-decoration:none" href="register.php?normal=1">'.T_('Cancel').'</a></small>
        </p>';
    }

    if (!$fbUser && $facebook)
    {
        $params = array('scope' => 'user_about_me,user_birthday,user_location,email,publish_stream,offline_access');

        echo '
        <p style="text-align:right">
            <a class="fbbutton" href="'.$facebook->getLoginUrl($params).'">'.T_('Connect with Facebook').'</a>
        </p>';
    }

    if (!$fbUser || isset($_GET['normal']))
    {

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
    }
}

/**
 * handleAutoActivation 
 * 
 * @param string $email    email address to send email to
 * @param string $subject  subject of email
 * @param int    $id       id of user being activated
 * @param string $sitename sitename
 * 
 * @return void
 */
function handleAutoActivation ($email, $subject, $id, $sitename)
{
    $code = uniqid(''); //bug in some versions of php, needs some value here

    $sql = "UPDATE `fcms_users` 
            SET `activate_code` = '$code' 
            WHERE `id` = '$id'";

    if (!mysql_query($sql))
    {
        displaySQLError('Activation Code Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        die();
    }

    $message = T_('Please click the following link to activate your account').':

'.getDomainAndDir().'activate.php?uid='.$id.'&code='.$code;

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
}

/**
 * checkUsername 
 * 
 * @return void
 */
function checkUsername ()
{
    $result = mysql_query("SELECT `username` FROM `fcms_users` WHERE `username` = '".cleanInput($_GET['username'])."'"); 

    $username_check = mysql_num_rows($result);

    if ($username_check > 0)
    {
        echo 'unavailable';
    }
    else
    {
        echo 'available';
    }
}

/**
 * displayFacebookRegister 
 * 
 * @return void
 */
function handleFacebookRegister ()
{
    $fbData    = getFacebookConfigData();
    $fbProfile = '';

    if (empty($fbData['fb_app_id']) && empty($fbData['fb_secret']))
    {
        displayHeader();
        displayHtmlForm(T_('Facebook isn\'t Configured Yet.'));
        displayFooter();
        return;
    }

    $facebook = new Facebook(array(
        'appId'  => $fbData['fb_app_id'],
        'secret' => $fbData['fb_secret'],
    ));

    // Check if the user is logged in and authed
    $fbUser = $facebook->getUser();
    if ($fbUser)
    {
        try
        {
            $fbProfile = $facebook->api('/me');
        }
        catch (FacebookApiException $e)
        {
            $fbUser = null;
        }
    }

    // the user's auth went away or logged out of fb, send them back to register form
    if (!$fbUser)
    {
        displayForm();
        return;
    }

    // Register new user
    $accessToken = $facebook->getAccessToken();

    $params = array(
        'fname'       => $fbProfile['first_name'],
        'lname'       => $fbProfile['last_name'],
        'email'       => $fbProfile['email'],
        'sex'         => $fbProfile['gender'] == 'male' ? 'M' : 'F',
        'username'    => $fbProfile['email'],
        'password'    => 'FACEBOOK',
        'accessToken' => $accessToken
    );

    displaySubmit($params);
}
