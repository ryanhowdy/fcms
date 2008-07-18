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
include_once('inc/familynews_class.php');
$fnews = new FamilyNews($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName() . " - " . $LANG['poweredby'] . " " . getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="themes/images/favicon.ico"/>
</head>
<body id="body-familynews">
	<div><a name="top"></a></div>
	<div id="header"><?php echo "<h1 id=\"logo\">$cfg_sitename</h1><p>".$LANG['welcome']." <a href=\"profile.php?member=".$_SESSION['login_id']."\">"; echo getUserDisplayName($_SESSION['login_id']); echo "</a> | <a href=\"settings.php\">".$LANG['link_settings']."</a> | <a href=\"logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></p>"; ?></div>
	<?php displayTopNav(); ?>
	<div id="pagetitle"><?php echo $LANG['link_news']; ?></div>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<div class="firstmenu menu">
			<ul><?php
				$result = mysql_query("SELECT u.id, fname, lname, displayname, username, MAX(`date`) AS d FROM fcms_news AS n, fcms_users AS u WHERE u.id = n.user AND username != 'SITENEWS' AND `password` != 'SITENEWS' GROUP BY id ORDER BY d DESC") or die("<h1>News Users Error (familynews.php 45)</h1>" . mysql_error());
				while($r=mysql_fetch_array($result)) {
					$date = fixDST(gmdate('M. j', strtotime($r['d'] . $fnews->tz_offset)), $_SESSION['login_id'], 'M. j');
					$displayname = getUserDisplayName($r['id']);
					echo "\n\t\t\t\t<li><a href=\"familynews.php?getnews=".$r['id']."\">$displayname  <small>[$date]</small></a></li>";
				}
				mysql_free_result($result); 
				if (checkAccess($_SESSION['login_id']) < 6 || checkAccess($_SESSION['login_id']) == 9) {
					echo "\n\t\t\t\t<li><a href=\"familynews.php?addnews=yes\">".$LANG['add_family_news']."</a></li>\n";
				} else {
					echo "\n\t\t\t\t<li><a href=\"familynews.php\">".$LANG['link_news']."</a></li>\n";
				} ?>
			</ul>
		</div>
	</div>
	<div id="content">
		<div id="familynews" class="centercontent">
			<?php 
			if (checkAccess($_SESSION['login_id']) < 6 || checkAccess($_SESSION['login_id']) == 9) {
				echo "\t\t\t<p class=\"center\">";
				if ($fnews->hasNews($_SESSION['login_id'])) { echo "<a href=\"?getnews=".$_SESSION['login_id']."\">".$LANG['my_news']."</a> | "; }
				echo "<a href=\"?addnews=yes\">".$LANG['add_news']."</a></p>\n";
			}
			$show_last5 = true;
			if(isset($_POST['submitadd'])) {
				$show_last5 = false;
				$title = addslashes($_POST['title']);
				$news = addslashes($_POST['post']);
				mysql_query("INSERT INTO `fcms_news`(`title`, `news`, `user`, `date`) VALUES('$title', '$news', " . $_SESSION['login_id'] . ", NOW())") or die("<h1>Add News Error (familynews.php 69)</h1>" . mysql_error());
				echo "<p class=\"ok-alert\">".$LANG['ok_news_add']."<br/><a href=\"familynews.php?getnews=".$_SESSION['login_id']."\">".$LANG['refresh_page']."</a>.</p>";
				echo "<meta http-equiv='refresh' content='0;URL=familynews.php?getnews=" . $_SESSION['login_id'] . "'>";
			} elseif (isset($_POST['submitedit'])) {
				$show_last5 = false;
				$title = addslashes($_POST['title']);
				$news = addslashes($_POST['post']);
				mysql_query("UPDATE `fcms_news` SET `title` = '$title', `news` = '$news' WHERE `id` = ".$_POST['id']) or die("<h1>Edit News Error (familynews.php 70)</h1>" . mysql_error());
				echo "<p class=\"ok-alert\">".$LANG['ok-news-edit']."<br/><a href=\"familynews.php?getnews=".$_POST['user']."\">".$LANG['refresh_page']."</a>.</p>";
				echo "<meta http-equiv='refresh' content='0;URL=familynews.php?getnews=".$_POST['user']."'>";
			}
			if (isset($_GET['getnews'])) {
				$show_last5 = false;
				$page = 1;
				$nid = 0;
				if (isset($_GET['newspage'])) { $page = $_GET['newspage']; }
				if (isset($_GET['newsid'])) { $nid = $_GET['newsid']; }
				if (isset($_POST['addcom'])) {
					$com = ltrim($_POST['comment']);
					if (!empty($com)) {
						mysql_query("INSERT INTO `fcms_news_comments`(`news`, `comment`, `date`, `user`) VALUES($nid, '" . addslashes($com) . "', NOW(), " . $_SESSION['login_id'] . ")") or die('<h1>New Comment Error (familynews.php 114)</h1>' . mysql_error());
					}
				}
				if (isset($_POST['delcom'])) {
					mysql_query("DELETE FROM fcms_news_comments WHERE id=" . $_POST['id']) or die('<h1>Delete Error (familynews.php 118)</h1>' . mysql_error());
				}
				$fnews->showFamilyNews($_GET['getnews'], $nid, $page);
			}
			if (isset($_GET['addnews']) && (checkAccess($_SESSION['login_id']) < 6 || checkAccess($_SESSION['login_id']) == 9)) { 
				$show_last5 = false;
				$fnews->displayForm('add', $_SESSION['login_id']);
			} elseif (isset($_POST['editnews'])) {
				$show_last5 = false;
				$fnews->displayForm('edit', $_POST['user'], $_POST['id'], $_POST['title'], $_POST['news']);
			} elseif (isset($_POST['delnews'])) {
				$show_last5 = false;
				mysql_query("DELETE FROM `fcms_news` WHERE id = ".$_POST['id']) or die("<h1>Delete News Error (familynews.php 92)</h1>" . mysql_error());
				echo "<p class=\"ok-alert\">".$LANG['ok-news-delete']."<br/><a href=\"familynews.php?getnews=" . $_POST['user'] . "\">".$LANG['refresh_page']."</a>.</p>";
				echo "<meta http-equiv='refresh' content='0;URL=familynews.php?getnews=" . $_POST['user'] . "'>";
			}
			if ($show_last5) {
				$fnews->displayLast5News();
			}
			?></div><!-- #familynews .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>