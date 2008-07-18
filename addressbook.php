<?php
session_start();
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	// a bug found with an array in $_POST
	if (!isset($_POST['emailsubmit']) && !isset($_POST['sendemailsubmit'])) {
		$_POST = array_map('stripslashes', $_POST);
	}
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
include_once('inc/addressbook_class.php');
$book = new AddressBook($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
header("Cache-control: private");
if (isset($_GET['csv'])) {
	$show = false;
	if ($_GET['csv'] == 'export') {
		$csv = "address, city, state, zip, home, work, cell\015\012";
		$result = mysql_query("SELECT `address`, `city`, `state`, `zip`, `home`, `work`, `cell` FROM `fcms_address`");
		while ($row = mysql_fetch_assoc($result)) {
			$csv .= '"'.join('","', str_replace('"', '""', $row))."\"\015\012";
		}
		header("Content-type: text/plain");
		header("Content-disposition: csv; filename=FCMS_Addresses_".date("Y-m-d").".csv; size=".strlen($csv));
		echo $csv;
		exit();
	}
} ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName() . " - " . $LANG['poweredby'] . " " . getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="themes/images/favicon.ico"/>
<script src="inc/prototype.js" type="text/javascript"></script>
</head>
<body id="body-addressbook">
	<div><a name="top"></a></div>
	<div id="header"><?php echo "<h1 id=\"logo\">".getSiteName()."</h1><p>".$LANG['welcome']." <a href=\"profile.php?member=".$_SESSION['login_id']."\">"; echo getUserDisplayName($_SESSION['login_id']); echo "</a> | <a href=\"settings.php\">".$LANG['link_settings']."</a> | <a href=\"logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></p>"; ?></div>
	<?php displayTopNav(); ?>
	<div id="pagetitle"><?php echo $LANG['link_address']; ?></div>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<?php
		displaySideNav();
		if(checkAccess($_SESSION['login_id']) < 3) { 
			echo "\t<h2>".$LANG['admin']."</h2>\n\t"; 
			displayAdminNav("fix");
		} ?></div>
	<div id="content">
		<div id="profile" class="centercontent">
			<?php 
			$show = true;
			if (isset($_POST['emailsubmit'])) {
				if (checkAccess($_SESSION['login_id']) > 3) {
					echo "<p class=\"error-alert\">".$LANG['err_access_3plus']."</p>";
				} else {
					if (empty($_POST['massemail'])) {
						echo "<p class=\"error-alert\">".$LANG['err_massemail_1']."  <a href=\"help.php#address-massemail\">".$LANG['err_massemail_2']."</a> ".$LANG['err_massemail_3']."</p>";
					} else {
						echo "<p class=\"info-alert\">".$LANG['info_massemail']."</p>\n\t\t\t<form method=\"post\" action=\"addressbook.php\">\n\t\t\t\t<p><label for=\"email\">".$LANG['your_email'].": </label><input type=\"text\" id=\"email\" name=\"email\" size=\"60\"/></p>\n\t\t\t\t";
						echo "<p><label for=\"name\">".$LANG['your_name'].": </label><input type=\"text\" id=\"name\" name=\"name\" size=\"60\"/></p>\n\t\t\t\t<p><label for=\"subject\">".$LANG['subject'].": </label><input type=\"text\" id=\"subject\" name=\"subject\" size=\"60\"/></p>\n\t\t\t\t<p><textarea name=\"msg\" rows=\"10\" cols=\"65\"></textarea></p>\n\t\t\t\t";
						foreach ($_POST['massemail'] as $email) {
							echo "<input type=\"hidden\" name=\"emailaddress[]\" value=\"$email\"/>\n\t\t\t";
						}
						echo "<p><input type=\"submit\" name=\"sendemailsubmit\" value=\"".$LANG['send_mass_email']."\"/></p>\n\t\t\t</form><p>&nbsp;</p><p>&nbsp;</p>";
						$show = false;
					}
				}
			}
			if (isset($_POST['sendemailsubmit'])) {
				$subject = $_POST['subject'];
				$email = $_POST['email'];
				$name = $_POST['name'];
				$msg = $_POST['msg'];
				$msg = $msg . "\r\n-" . $name;
				foreach ($_POST['emailaddress'] as $email) {
					mail($email, "$subject", "$email", "$msg", $email_headers);
				}
				echo "<p class=\"ok-alert\" id=\"msg\">".$LANG['msg_sent']."<br/>";
				echo "$msg</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('msg').toggle()\",4000); }</script>";
			}
			if (isset($_POST['editsubmit'])) {
				mysql_query("UPDATE `fcms_address` SET updated=NOW(), address='".addslashes($_POST['address'])."', city='".addslashes($_POST['city'])."', state='".addslashes($_POST['state'])."', zip='".addslashes($_POST['zip'])."', home='".addslashes($_POST['home'])."', `work`='".addslashes($_POST['work'])."', cell='".addslashes($_POST['cell'])."' WHERE id=".$_POST['aid']) or die('<h1>Edit Address Error (addressbook.php 67)</h1>' . mysql_error());
				mysql_query("UPDATE `fcms_users` SET `email`='".addslashes($_POST['email'])."' WHERE id=".$_POST['uid']) or die('<h1>Edit Address Error (addressbook.php 68)</h1>' . mysql_error());
			}
			if (isset($_POST['addsubmit'])) {
				$uniq = uniqid("");
				mysql_query("INSERT INTO `fcms_users`(`access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`) VALUES (3, NOW(), '".addslashes($_POST['fname'])."', '".addslashes($_POST['lname'])."', '".addslashes($_POST['email'])."', 'NONMEMBER-$uniq', 'NONMEMBER')") or die("<h1>Non-member Creation Error (addressbook.php 81)</h1>" . mysql_error());
				$id = mysql_insert_id();
				mysql_query("INSERT INTO `fcms_address`(`user`, `entered_by`, `updated`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell`) VALUES ($id, ".$_SESSION['login_id'].", NOW(), '".addslashes($_POST['address'])."', '".addslashes($_POST['city'])."', '".addslashes($_POST['state'])."', '".addslashes($_POST['zip'])."', '".addslashes($_POST['home'])."', '".addslashes($_POST['work'])."', '".addslashes($_POST['cell'])."')") or die("<h1>New Address Error (addressbook.php 83)</h1>" . mysql_error());
			}
			if (isset($_GET['del'])) {
				if ($_SESSION['login_id'] == $_GET['u'] || checkAccess($_SESSION['login_id']) < 2) {
					$aid = $_GET['del'];
					mysql_query("DELETE FROM fcms_users WHERE id = $aid") or die("<h1>Non-member Deletion Error (addressbook.php 87)</h1>" . mysql_error());
					mysql_query("DELETE FROM fcms_address WHERE id = $aid") or die("<h1>Delete Address Error (addressbook.php 88)</h1>" . mysql_error());
				} else {
					echo "<p class=\"error-alert\">".$LANG['err_permission']."</p>";
				}
			}
			if (isset($_GET['edit'])) {
				if ($_SESSION['login_id'] == $_GET['u'] || checkAccess($_SESSION['login_id']) < 2) {
					$book->displayForm('edit', $_GET['edit']);
				} else {
					echo "<p class=\"error-alert\">".$LANG['err_permission']."</p>";
				}
				$show = false;
			}
			if (isset($_GET['add'])) {
				if (checkAccess($_SESSION['login_id']) <= 5) {
					$book->displayForm('add');
					$show = false;
				}
			}
			if (isset($_GET['address'])) {
				$book->displayToolbar();
				$book->displayAddress($_GET['address']);
				$show = false;
			}
			if ($show) {
				$book->displayToolbar();
				if (isset($_GET['letter'])) {
					$sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email` FROM `fcms_users` AS u, `fcms_address` as a WHERE u.`id` = a.`user` AND `username` != 'SITENEWS' AND `password` != 'SITENEWS' AND `lname` LIKE '" . $_GET['letter'] . "%' ORDER BY `lname`";
				} else {
					$sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email` FROM `fcms_users` AS u, `fcms_address` as a WHERE u.`id` = a.`user` AND `username` != 'SITENEWS' AND `password` != 'SITENEWS' ORDER BY `lname`";
				}
				echo "\n\t\t\t<script src=\"inc/prototype.js\" type=\"text/javascript\"></script>\n\t\t\t<script type=\"text/javascript\" src=\"inc/tablesort.js\"></script>\n\t\t\t";
				echo "<form action=\"addressbook.php\" method=\"post\">\n\t\t\t";
				echo "<table class=\"sortable\">\n\t\t\t\t<thead><tr><th class=\"sortfirstasc\">".$LANG['name']."</th><th>".$LANG['phone_num']."</th><th>".$LANG['email']."</th><th class=\"nosort\"><a class=\"helpimg\" href=\"help.php#address-massemail\"></a></th></tr></thead>\n\t\t\t\t<tbody>\n";
				$result = mysql_query($sql) or die("<h1>Addresses Error (addressbook.php 130)</h1>" . mysql_error());
				while($r = mysql_fetch_array($result)) {
					echo "\t\t\t\t\t<tr><td><a href=\"?address=".$r['id']."\">".$r['lname'].", ".$r['fname']."</a></td><td>".$r['home']."</td><td><a href=\"mailto:".htmlentities($r['email'], ENT_COMPAT, 'UTF-8')."\">".$r['email']."</a></td><td>";
					if (!empty($r['email'])) { echo "<input type=\"checkbox\" name=\"massemail[]\" value=\"".htmlentities($r['email'], ENT_COMPAT, 'UTF-8')."\"/>"; }
					echo "</td></tr>\n";
				}
				echo "\n\t\t\t\t</tbody>\n\t\t\t</table>\t\t\t\t\t<div class=\"alignright\"><input ";
				if (checkAccess($_SESSION['login_id']) > 3) { echo "disabled=\"disabled\" "; }
				echo "type=\"submit\" name=\"emailsubmit\" value=\"".$LANG['email']."\"/></div><p>&nbsp;</p>\n\t\t\t</form>\n";
			} echo "\n"; ?>
		</div><!-- #centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>