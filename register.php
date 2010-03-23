<?php
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	$_POST = array_map('stripslashes', $_POST);
	$_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo $LANG['reg_for']." ".getSiteName(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css" />
<script type="text/javascript" src="inc/prototype.js"></script>
<script type="text/javascript" src="inc/livevalidation.js"></script>
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
if (isset($_POST['submit'])) {
	$result = mysql_query("SELECT email FROM fcms_users WHERE email='" . $_POST['email'] . "'"); 
	$email_check = mysql_num_rows($result);
	$result = mysql_query("SELECT username FROM fcms_users WHERE username='" . $_POST['username'] . "'"); 
	$username_check = mysql_num_rows($result);
	if (
        strlen($_POST['username']) < 1 ||
        strlen($_POST['password']) < 1 ||
        strlen($_POST['fname']) < 1 ||
        strlen($_POST['lname']) < 1 ||
        strlen($_POST['email']) < 1
    ) {
		displayForm('<p class="error">' . $LANG['err_required'] . '</p>');
	} elseif ($email_check > 0) {
		displayForm(
            '<p class="error">' . $LANG['err_email_use1'] . ' <a href="lostpw.php">'
            . $LANG['err_email_use2'] . '</a> ' . $LANG['err_email_use3'] . '</p>'
        );
	} elseif ($username_check > 0) {
		displayForm(
            '<p class="error">Sorry, but that username is already taken.  Please choose another username.</p>'
        );
	} else {
		$fname = escape_string($_POST['fname']);
		$lname = escape_string($_POST['lname']);
		$email = escape_string($_POST['email']);
		$username = escape_string($_POST['username']);
		$password = escape_string($_POST['password']);
		$md5pass = md5($password);
		$sql = "INSERT INTO `fcms_users`(`access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`) "
             . "VALUES (3, NOW(), '$fname', '$lname', '$email', '$username', '$md5pass')";
		mysql_query($sql) or displaySQLError('New User Error', 'register.php [' . __LINE__ . ']', $sql, mysql_error());
		$lastid = mysql_insert_id();
		$sql = "INSERT INTO `fcms_user_settings`(`user`) VALUES ($lastid)";
		mysql_query($sql) or displaySQLError('User Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
		$sql = "INSERT INTO `fcms_address`(`user`, `updated`) VALUES ($lastid, NOW())";
		mysql_query($sql) or displaySQLError('New Address Error', 'register.php [' . __LINE__ . ']', $sql, mysql_error());
		$subject = getSiteName()." ".$LANG['mail_reg1'];
		$now = date('F j, Y, g:i a');
		$subject2 = $LANG['mail_reg_adm1']." ".getSiteName();
		$message2 = $LANG['mail_reg_adm2']." ".getSiteName().":
	
".$LANG['mail_reg_adm3'].": $now

".$LANG['username'].": ".stripslashes($username)."
".$LANG['name'].": ".stripslashes($fname)." ".stripslashes($lname);
		$sql = "SELECT `auto_activate` FROM `fcms_config`";
		$result = mysql_query($sql) or displaySQLError('Activation Check Error', 'register.php [' . __LINE__ . ']', $sql, mysql_error());
		$row = mysql_fetch_assoc($result);
		if ($row['auto_activate'] == 1) {
			//bug in some versions of php, needs some value here
			$code = uniqid('');
			$sql = "UPDATE `fcms_users` SET `activate_code` = '$code' WHERE `id` = $lastid";
			mysql_query($sql) or displaySQLError('Activation Code Error', 'register.php [' . __LINE__ . ']', $sql, mysql_error());
			$message = $LANG['link_to_activate'] . ":

" . getDomainAndDir() . "activate.php?uid=$lastid&code=$code";
			echo '<div id="msg"><h1>'.$LANG['reg_success'].'</h1><p>'.$LANG['reg_msg1'].' ' . getSiteName() . '. '.$LANG['reg_msg2'].' ' . $email . '. <br/><b>'.$LANG['reg_msg3'].'</b></p>'
				. '<p>'.$LANG['reg_msg4_2'].' <a href="index.php">'.$LANG['reg_msg5'].'</a>.</div>';
			mail($email, $subject, $message, $email_headers);
		} elseif ($row['auto_activate'] == 0) {
			$message = $LANG['mail_reg2']." ".stripslashes($fname)." ".stripslashes($lname).", 

".$LANG['mail_reg3']." ".getSiteName()."

".$LANG['mail_reg4']." ".getSiteName().", ".$LANG['mail_reg5']."

".$LANG['mail_reg6']."
".$LANG['username'].": ".stripslashes($username)." 
".$LANG['password'].": $password

".$LANG['mail_reg7']." 
".$LANG['mail_reg8']." ".getSiteName()." ".$LANG['mail_reg9']."

".$LANG['mail_reg10'];
			echo '<div id="msg"><h1>'.$LANG['reg_success'].'</h1><p>'.$LANG['reg_msg1'].' ' . getSiteName() . '. '.$LANG['reg_msg2'].' ' . $email . '. <br/><b>'.$LANG['reg_msg3'].'</b></p>'
				. '<p>'.$LANG['reg_msg4'].' <a href="index.php">'.$LANG['reg_msg5'].'</a>.</div>';
			mail($email, $subject, $message, $email_headers);
		}
		mail(getContactEmail(), $subject2, $message2, $email_headers);
	}
} else { displayForm(); } ?>
</body>
</html>

<?php
function displayForm ($error = '0') {
	global $LANG;
    $user = isset($_POST['username']) ? $_POST['username'] : '';
    $first = isset($_POST['fname']) ? $_POST['fname'] : '';
    $last = isset($_POST['lname']) ? $_POST['lname'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    ?>
	<div id="column">
	<h1><?php echo $LANG['reg']; ?></h1>
	<?php if ($error !== '0') { echo $error; } ?>
	<form id="registerform" name="registerform" action="register.php" method="post">
		<div class="field-row">
            <div class="field-label"><label for="username"><b><?php echo $LANG['username']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget clearfix">
                <input type="text" name="username" id="username" title="<?php echo $LANG['title_uname']; ?>" value="<?php echo $user; ?>"/>
            </div>
        </div>
		<script type="text/javascript">
			var funame = new LiveValidation('username', { onlyOnSubmit: true });
			funame.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_sorry_req']; ?>"});
		</script>
		<div class="field-row">
            <div class="field-label"><label for="password"><b><?php echo $LANG['password']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget clearfix">
                <input type="password" name="password" id="password" title="<?php echo $LANG['title_pass']; ?>"/>
            </div>
        </div>
		<script type="text/javascript">
			var fpass = new LiveValidation('password', { onlyOnSubmit: true });
			fpass.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_bad_pass']; ?>"});
		</script>
		<div class="field-row">
            <div class="field-label"><label for="fname"><b><?php echo $LANG['first_name']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget clearfix">
                <input type="text" name="fname" id="fname" title="<?php echo $LANG['title_fname']; ?>" value="<?php echo $first; ?>"/>
            </div>
        </div>
		<script type="text/javascript">
			var ffname = new LiveValidation('fname', { onlyOnSubmit: true });
			ffname.add(Validate.Presence, { failureMessage: "<?php echo $LANG['lv_sorry_req']; ?>" });
		</script>
		<div class="field-row">
            <div class="field-label"><label for="lname"><b><?php echo $LANG['last_name']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget clearfix">
                <input type="text" name="lname" id="lname" title="<?php echo $LANG['title_lname']; ?>" value="<?php echo $last; ?>"/>
            </div>
        </div>
		<script type="text/javascript">
			var flname = new LiveValidation('lname', { onlyOnSubmit: true });
			flname.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_sorry_req']; ?>"});
		</script>
		<div class="field-row">
            <div class="field-label"><label for="email"><b><?php echo $LANG['email_address']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget clearfix">
                <input type="text" name="email" id="email" title="<?php echo $LANG['title_email']; ?>" value="<?php echo $email; ?>"/>
            </div>
        </div>
		<script type="text/javascript">
			var femail = new LiveValidation('email', { onlyOnSubmit: true });
			femail.add( Validate.Presence, { failureMessage: "<?php echo $LANG['lv_sorry_req']; ?>" } );
			femail.add( Validate.Email, { failureMessage: "<?php echo $LANG['lv_bad_email']; ?>" } );
			femail.add( Validate.Length, { minimum: 10 } );
		</script>
		<p><a class="cancel" href="index.php">Cancel</a> <input id="submit" name="submit" type="submit"  value="<?php echo $LANG['submit']; ?>"/></p>
        <div class="clear"></div>
    </form>
	</div><?php
} ?>