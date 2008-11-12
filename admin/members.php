<?php
session_start();
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');
include_once('../inc/language.php');
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName()." - ".$LANG['poweredby']." ".getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="../<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="themes/images/favicon.ico"/>
<script src="../inc/prototype.js" type="text/javascript"></script>
<script src="../inc/livevalidation.js" type="text/javascript"></script>
</head>
<body>
	<div><a name="top"></a></div>
	<div id="header"><?php echo "<h1 id=\"logo\">".getSiteName()."</h1><p>".$LANG['welcome']." <a href=\"../profile.php?member=".$_SESSION['login_id']."\">"; echo getUserDisplayName($_SESSION['login_id']); echo "</a> | <a href=\"../settings.php\">".$LANG['link_settings']."</a> | <a href=\"../logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></p>"; ?></div>
	<?php displayTopNav("fix"); ?>
	<div id="pagetitle"><?php echo $LANG['admin_members']; ?></div>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<?php
		displaySideNav("fix");
		if(checkAccess($_SESSION['login_id']) < 3) { 
			echo "\t<h2>".$LANG['admin']."</h2>\n\t"; 
			displayAdminNav();
		} ?></div>
	<div id="content">
		<div class="centercontent">
			<?php
			if (checkAccess($_SESSION['login_id']) < 2) {
				if (isset($_POST['edit'])) {
					$id = $_POST['id'];
					$access = (int)$_POST['access'];
					if (!empty($access) && is_numeric($access) && $access <= 10 && $access > 0) {
						if (isset($_POST['activated'])) {
							$sql = "UPDATE fcms_users SET activated = 1 WHERE id = $id";
							mysql_query($sql) or displaySQLError('Activate Error', 'admin/members.php [' . __LINE__ . ']', $sql, mysql_error());
							$sql = "SELECT `email` FROM `fcms_users` WHERE id = $id";
							$result = mysql_query($sql) or displaySQLError('Get Email Error', 'admin/members.php [' . __LINE__ . ']', $sql, mysql_error());
							$r = mysql_fetch_array($result);
							$msg = $LANG['mail_msg_activate1']." ".getSiteName()." ".$LANG['mail_msg_activate2'];
							mail($r['email'], $LANG['mail_sub_activate'] . getSiteName(), $msg, $email_headers);
						} else {
							$sql = "UPDATE `fcms_users` SET `activated` = 0 WHERE id = $id";
							mysql_query($sql) or displaySQLError('Activate Error', 'admin/members.php [' . __LINE__ . ']', $sql, mysql_error());
						}
						$sql = "UPDATE fcms_users SET access = $access WHERE id = $id";
						mysql_query($sql) or displaySQLError('Access Error', 'admin/members.php [' . __LINE__ . ']', $sql, mysql_error());
						echo "<p class=\"ok-alert\" id=\"update\">".$LANG['update_success']."</p>";
						echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
					}
				}
				if (isset($_POST['del'])) {
					$id = $_POST['id'];
					$sql = "DELETE FROM fcms_users WHERE id = $id";
					mysql_query($sql) or displaySQLError('Delete User Error', 'admin/members.php [' . __LINE__ . ']', $sql, mysql_error());
					echo "<meta http-equiv='refresh' content='0;URL=members.php'>";
				} ?>
				<p class="info-alert"><?php echo $LANG['info_edit_members']; ?></p>
				<table width="100%"><thead><tr><th><?php echo $LANG['id']; ?></th><th><?php echo $LANG['uname_flname']; ?></th><th width="20%"><a class="help u" title="<?php echo $LANG['title_access_help']; ?>" href="../help.php#adm-access"><?php echo $LANG['access_level']; ?></a></th><th><?php echo $LANG['activated']; ?></th><th><?php echo $LANG['edit_delete']; ?></th></tr></thead><tbody>
				<?php 
				$sql = "SELECT * FROM fcms_users WHERE password != 'NONMEMBER' ORDER BY `id`";
				$result = mysql_query($sql) or displaySQLError('Member Info Error', 'admin/members.php [' . __LINE__ . ']', $sql, mysql_error());
				while($r=mysql_fetch_array($result)) {
					if ($r['id'] > 1) {
						echo "<tr><form method=\"post\" action=\"members.php\"><td><b>".$r['id']."</b>:</td><td> <b>".$r['username']."</b> &nbsp;(".$r['fname']." ".$r['lname'].")</td>"; 
						echo "<td><select id=\"access\" name=\"access\"><option value=\"1\""; if ($r['access'] == 1) { echo " selected=\"selected\""; }
						echo ">1. ".$LANG['access_admin']."</option><option value=\"2\""; if ($r['access'] == 2) { echo " selected=\"selected\""; }
						echo ">2. ".$LANG['access_helper']."</option><option value=\"3\""; if ($r['access'] == 3) { echo " selected=\"selected\""; }
						echo ">3. ".$LANG['access_member']."</option><option value=\"".$r['access']."\"></option><option value=\"".$r['access']."\">".$LANG['advanced_options']."</option>";
						echo "<option value=\"".$r['access']."\">-------------------------------------</option><option value=\"4\""; if ($r['access'] == 4) { echo " selected=\"selected\""; }
						echo ">4. ".$LANG['access_non_photo']."</option><option value=\"5\""; if ($r['access'] == 5) { echo " selected=\"selected\""; }
						echo ">5. ".$LANG['access_non_poster']."</option><option value=\"6\""; if ($r['access'] == 6) { echo " selected=\"selected\""; }
						echo ">6. ".$LANG['access_commenter']."</option><option value=\"7\""; if ($r['access'] == 7) { echo " selected=\"selected\""; }
						echo ">7. ".$LANG['access_poster']."</option><option value=\"8\""; if ($r['access'] == 8) { echo " selected=\"selected\""; }
						echo ">8. ".$LANG['access_photo']."</option><option value=\"9\""; if ($r['access'] == 9) { echo " selected=\"selected\""; }
						echo ">9. ".$LANG['access_blogger']."</option><option value=\"10\""; if ($r['access'] == 10) { echo " selected=\"selected\""; }
						echo ">10. ".$LANG['access_guest']."</option></select></td>";
						echo "<td style=\"text-align:center\"><input name=\"activated\" id=\"activated\" type=\"checkbox\"";  if($r['activated'] > 0) { echo " checked=\"checked\""; }  echo " value=\"1\"/><td> &nbsp;<input type=\"submit\" name=\"edit\" id=\"edit\" value=\" \" class=\"editbtn\" title=\"".$LANG['title_edit_member']."\"/> / ";
						echo "<input type=\"submit\" name=\"del\" id=\"del\" value=\" \" class=\"delbtn\" title=\"".$LANG['title_delete_member']."\" onclick=\"javascript:return confirm('".$LANG['js_delete_member']."'); \"/><input type=\"hidden\" name=\"id\" value=\"".$r['id']."\"/></td></form></tr>";
					} else {
						echo "<tr><form><td><b>".$r['id']."</b>:</td><td> <b>".$r['username']."</b> &nbsp;(".$r['fname']." ".$r['lname'].")</td><td><input disabled=\"disabled\" type=\"text\" value=\"1. ".$LANG['access_admin']."\"/></td><td style=\"text-align:center\"><input disabled=\"disabled\" name=\"activated\" id=\"activated\" type=\"checkbox\" checked=\"checked\"/></td><td>&nbsp;</td></form></tr>";
					}
				}
			} else {
				echo "<p class=\"error-alert\"><b>".$LANG['err_no_access1']."</b><br/>".$LANG['err_no_access_member2']." <a href=\"../contact.php\">".$LANG['err_no_access3']."</a> ".$LANG['err_no_access4']."</a>";
			} ?>
			</tbody></table>
			<p>&nbsp;</p>
			<p>&nbsp;</p>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<?php displayFooter("fix"); ?>
</body>
</html>