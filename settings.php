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
include 'inc/settings_class.php';
$settings = new Settings($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName() . " - " . $LANG['poweredby'] . " " . getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="stylesheet" type="text/css" href="themes/datechooser.css" />
<link rel="shortcut icon" href="themes/images/favicon.ico"/>
<script type="text/javascript" src="inc/datechooser.js"></script>
<script type="text/javascript">
<!-- //
	window.onload = WindowLoad;
	function WindowLoad()
	{
		var objDatePicker = new DateChooser();
		objDatePicker.setUpdateField({'day':'j', 'month':'n', 'year':'Y'});
		objDatePicker.setIcon('themes/images/default/datepicker.jpg', 'year');
		return true;
	}
// -->
</script>
</head>
<body id="body-settings">
	<div><a name="top"></a></div>
	<div id="header"><?php echo "<h1 id=\"logo\">$cfg_sitename</h1><p>".$LANG['welcome']." <a href=\"profile.php?member=".$_SESSION['login_id']."\">"; echo getUserDisplayName($_SESSION['login_id']); echo "</a> | <a href=\"settings.php\">".$LANG['link_settings']."</a> | <a href=\"logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></p>"; ?></div>
	<?php displayTopNav(); ?>
	<div id="pagetitle"><?php echo $LANG['link_settings']; ?></div>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<?php
		displaySideNav();
		if(checkAccess($_SESSION['login_id']) < 3) { 
			echo "\t<h2>".$LANG['admin']."</h2>\n\t"; 
			displayAdminNav("fix");
		} ?></div>
	<div id="content">
		<div class="centercontent">
			<?php
			$emailstart = $settings->cur_user_email;
			if (isset($_POST['submit'])) {
				$birthday = $_POST['year'] . "-" . str_pad($_POST['month'], 2, "0", STR_PAD_LEFT) . "-" . str_pad($_POST['day'], 2, "0", STR_PAD_LEFT);
				$sql = "UPDATE `fcms_users` SET ";
				if ($_POST['fname']) { $sql .= "fname = '".addslashes($_POST['fname'])."', "; }
				if ($_POST['lname']) { $sql .= "lname = '".addslashes($_POST['lname'])."', "; }
				if ($_POST['email']) { 
					if ($_POST['email'] != $emailstart) {
						$result = mysql_query("SELECT email FROM fcms_users WHERE email='" . $_POST['email'] . "'") or die("<h1>Get Email Error (settings.php 79)</h1>" . mysql_error());
						$email_check = mysql_num_rows($result);
						if ($email_check > 0) { 
							echo "<p class=\"error-alert\">".$LANG['err_email1']." (" . $_POST['email'] . ") ".$LANG['err_email2']."</p>";
							$settings->displayForm();
							exit();
						}
					$sql .= "email = '".addslashes($_POST['email'])."', ";
					}
				}
				if ($_POST['year']) { $sql .= "birthday = '$birthday', "; }
				if ($_POST['boardsort']) { $sql .= "boardsort = '" . $_POST['boardsort'] . "', "; }
				if ($_POST['showavatar']) { $sql .= "showavatar = '" . $_POST['showavatar'] . "', "; }
				if ($_POST['displayname']) { $sql .= "displayname = '" . $_POST['displayname'] . "', "; }
				if ($_POST['frontpage']) { $sql .= "frontpage = '" . $_POST['frontpage'] . "', "; }
				if ($_POST['timezone']) { $sql .= "timezone = '" . $_POST['timezone'] . "', "; }
				if ($_POST['dst']) { 
					if ($_POST['dst'] == 'Yes') { $sql .= "dst = '1', "; } elseif ($_POST['dst'] == 'No') { $sql .= "dst = '0', "; }
				}
				$orig_pass = $_SESSION['login_pw'];
				if ($_POST['pass']) {
					$sql .= "password = '" . md5($_POST['pass']) . "', ";
					$_SESSION['login_pw'] = md5($_POST['pass']);
				}
				if ($_FILES['avatar']['name']) {
					$upfile = uploadImages($_FILES['avatar']['type'], $_FILES['avatar']['name'], $_FILES['avatar']['tmp_name'], "gallery/avatar/", 80, 80, 'yes');
					$sql .= "`avatar` = '$upfile', ";
					if ($_POST['avatar_orig'] != '0x0.gif') { unlink("gallery/avatar/" . $_POST['avatar_orig']); }
				}
				$sql .= "theme = '" . $_POST['theme'] . "' WHERE id = " . $_SESSION['login_id'];
				mysql_query($sql) or die("<h1>Update Settings Error (settings.php 102)</h1>" . mysql_error());
				if ($orig_pass != md5($_POST['pass']) && !empty($_POST['pass']) && isset($_COOKIE['fcms_login_id'])){
					echo "<p class=\"ok-alert\">".$LANG['ok_stgs_logout1']."</p><p><a href=\"logout.php\">".$LANG['ok_stgs_logout2']."</a>.</p>";
					echo "<meta http-equiv='refresh' content='5;URL=logout.php'>";
				} else {
					echo "<p class=\"ok-alert\">".$LANG['ok_settings1']."</p><p><a href=\"settings.php\">".$LANG['ok_settings2']."</a>.</p>";
				}
			} else {
				$settings->displayForm();
			} ?>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>