<?php
header("Cache-control: private");
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
$link = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
mysql_select_db($cfg_mysql_db, $link);?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo T_('lang'); ?>" lang="<?php echo T_('lang'); ?>">
<head>
<title><?php echo getSiteName() . " - " . T_('powered by') . " " . getCurrentVersion(); ?></title>
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css" />
</head>
<body>
<?php
if (isset($_GET['uid'])) {

    // Check for valid user id
    if (ctype_digit($_GET['uid'])) {
        echo '
    <div id="login_box">
        <h1 id="reset_header">'.T_('Account Activation').'</h1>';
        $sql = "SELECT `activate_code` FROM `fcms_users` WHERE `id` = " . $_GET['uid'];
        $result = mysql_query($sql) or displaySQLError('Check Code Error', 'activate.php [' . __LINE__ . ']', $sql, mysql_error());
        $row = mysql_fetch_array($result);

        // User supplied an activation code
        if (isset($_GET['code'])) {

            // Code is valid
            if ($row['activate_code'] == $_GET['code']) {
                $sql = "UPDATE `fcms_users` 
                        SET `activated` = 1, `joindate` = NOW() 
                        WHERE `id` = " . $_GET['uid'];
                mysql_query($sql) or displaySQLError('Activation Error', 'activate.php [' . __LINE__ . ']', $sql, mysql_error());
                echo '
        <p><b>'.T_('Alright!').'</b></p>
        <p>'.T_('Your account is now active.').'</p>
        <p><a href="index.php">'.T_('You can now login and begin using the site.').'</a></p>
        <meta http-equiv=\'refresh\' content=\'5;URL=index.php\'>';

            // Code is invalid
            } else {
                echo '
        <p><b>'.T_('Invalid Activation Code!').'</b></p>
        <p>'.T_('Your account could NOT be activated').'</p>';
            }

        // No code
        } else {
            echo '
        <p><b>'.T_('Invalid Activation Code!').'</b></p>
        <p>'.T_('Your account could NOT be activated').'</p>';
        }
        echo  '
        <br/>
    </div>';

    // Invalid user id
    } else {
        echo T_('Access Denied');
    }
} else {
    echo T_('Access Denied');
} ?>
</body>
</html>
