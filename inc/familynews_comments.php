<?php
session_start();
include_once('config_inc.php');
include_once('util_inc.php');
include_once('language.php');
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
$timezone_sql = mysql_query("SELECT timezone FROM fcms_users WHERE id = " . $_SESSION['login_id']) or die('<h1>Timezone Error (familynews_comments.class.php 24)</h1>' . mysql_error());
$ftimezone = mysql_fetch_array($timezone_sql);
$tz_offset = $ftimezone['timezone'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName() . " - " . $LANG['poweredby'] . " " . getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style type="text/css">
.right { text-align: right; }
.center { text-align: center; }
.edit_del_photo { margin: 0 auto -12px auto; width: 500px; text-align: right; }
.gal_delcombtn, .gal_addcombtn, .gal_editbtn, .gal_delbtn { border: 0; width: 16px; height: 16px; cursor: pointer; vertical-align: middle; }
.gal_delcombtn { background: url("../themes/images/default/comments_delete.gif") top left no-repeat; }
.gal_addcombtn { background: url("../themes/images/default/comments_add.gif") top left no-repeat; }
.gal_delbtn { background: url("../themes/images/default/image_delete.gif") top left no-repeat; }
.gal_editbtn { background: url("../themes/images/default/image_edit.gif") top left no-repeat; }
.comment_block { margin: 0 auto 15px auto; padding: 3px; width: 450px; border: 1px solid #c1c1c1; background-color: #f5f5f5; }
.comment_block form { margin: 0; }
.comment_block input, .comment_block span { float: right; padding-right: 5px; }
.comment_block img { display: block; float: left; margin-right: 5px; border: 2px solid #e6e6e6; }
</style>
</head>
<body>
<?php
if (isset($_GET['newsid'])) {
	$news_id = $_GET['newsid'];
	if (isset($_POST['addcom'])) {
		$com = ltrim($_POST['comment']);
		if (!empty($com)) {
			mysql_query("INSERT INTO `fcms_news_comments`(`news`, `comment`, `date`, `user`) VALUES($news_id, '" . addslashes($com) . "', NOW(), " . $_SESSION['login_id'] . ")") or die('<h1>New Comment Error (familynews_comments.class.php 86)</h1>' . mysql_error());
		}
	}
	if (isset($_POST['delcom'])) {
		mysql_query("DELETE FROM fcms_news_comments WHERE id=" . $_POST['id']) or die('<h1>Delete Error (familynews_comments.class.php 83)</h1>' . mysql_error());
	}
	echo "<h3>".$LANG['comments']."</h3><p class=\"center\"><form action=\"familynews_comments.php?newsid=$news_id\" method=\"post\">".$LANG['add_comment']."<br/><input type=\"text\" name=\"comment\" id=\"comment\" size=\"50\" title=\"".$LANG['add_comment']."\"/> <input type=\"submit\" name=\"addcom\" id=\"addcom\" value=\" \" class=\"gal_addcombtn\" /></form></p><p class=\"center\">&nbsp;</p>";
	$result = mysql_query("SELECT c.id, comment, `date`, fname, lname, displayname, username, user FROM fcms_news_comments AS c, fcms_users AS u WHERE news = $news_id AND c.user = u.id ORDER BY `date`") or die("<h1>Error (FNC004)</h1>" . mysql_error());
	if (mysql_num_rows($result) > 0) { 
		while($row = mysql_fetch_array($result)) {
			$displayname = getUserDisplayName($row['user']);
			if ($_SESSION['login_id'] == $row['user'] || checkAccess($_SESSION['login_id']) < 2) {
				echo "<div class=\"comment_block\"><form action=\"familynews_comments.php?newsid=$news_id\" method=\"post\"><input type=\"submit\" name=\"delcom\" id=\"delcom\" value=\" \" class=\"gal_delcombtn\" title=\"".$LANG['title_del_comment']."\" onclick=\"javascript:return confirm('".$LANG['js_del_comment']."'); \"/><span>".$row['date']."</span><b>$displayname</b><br/>" . htmlentities(stripslashes($row['comment']), ENT_COMPAT, 'UTF-8') . "<input type=\"hidden\" name=\"id\" value=\"".$row['id']."\"></form></div>";
			} else {
				echo "<div class=\"comment_block\"><span>".$row['date']."</span><b>$displayname</b><br/>" . htmlentities(stripslashes($row['comment']), ENT_COMPAT, 'UTF-8') . "</div>";
			}
		}
	} else { echo "<p class=\"center\">".$LANG['no_comments']."</p>"; }
} else {
	echo "<h3>".$LANG['invalid_newsid']."</h3>";
} ?>
</body>
</html>