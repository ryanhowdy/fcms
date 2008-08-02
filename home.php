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
include_once('inc/calendar_class.php');
include_once('inc/poll_class.php');
mysql_query("UPDATE `fcms_users` SET `activity`=NOW() WHERE `id`=" . $_SESSION['login_id']);
$calendar = new Calendar($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$poll = new Poll('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
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
<body id="body-home">
	<div><a name="top"></a></div>
	<div id="header"><?php echo "<h1 id=\"logo\">$cfg_sitename</h1><p>".$LANG['welcome']." <a href=\"profile.php?member=".$_SESSION['login_id']."\">"; echo getUserDisplayName($_SESSION['login_id']); echo "</a> | <a href=\"settings.php\">".$LANG['link_settings']."</a> | <a href=\"logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></p>"; ?></div>
	<?php displayTopNav(); ?>
	<div id="pagetitle"><?php echo $LANG['link_home']; ?></div>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<?php
		displaySideNav();
		if(checkAccess($_SESSION['login_id']) < 3) { 
			echo "\t<h2>".$LANG['admin']."</h2>\n\t"; 
			displayAdminNav("fix");
		}
		echo "\t<h2>".$LANG['link_calendar']."</h2>";
		$year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
		$month = isset($_GET['month']) ? str_pad($_GET['month'], 2, 0, STR_PAD_LEFT) : date('m');
		$day = isset($_GET['day']) ? str_pad($_GET['day'], 2, 0, STR_PAD_LEFT) : date('d');
		$calendar->displayCalendar($month, $year, $day);
		$calendar->displayMonthEvents($month, $year);
		$month = date("m", mktime(0,0,0,$month+1,1,2006));
		$calendar->displayMonthEvents($month, $year);
		echo "<br/><h2>".$LANG['link_admin_polls']."</h2>";
		if (isset($_POST['vote'])) {
			$poll->placeVote($_SESSION['login_id'], $_POST['option_id']);
			$poll_id = $_GET['poll_id'] ? $_GET['poll_id'] : $_POST['poll_id'];
			$poll->displayResults($poll_id);
		}
		if (isset($_GET['action'])) {
			if ($_GET['action'] == "results") {
				$poll_id = $_GET['poll_id'] ? $_GET['poll_id'] : $_POST['poll_id'];
				// Santizing user input - poll_id - only allow digits 0-9
				if (preg_match('/^\d+$/', $poll_id)) { $poll->displayResults($poll_id); }
			} elseif ($_GET['action'] == "pastpolls") {
				$poll->displayPastPolls();
			}
		} else {
			if(isset($_GET['poll_id'])) { $poll->displayPoll($_GET['poll_id']); } elseif (!isset($_POST['vote'])) { $poll->displayPoll(); }
		}
		echo "<h2>".$LANG['mem_online']."</h2>";
		displayMembersOnline(); ?>
	</div>
	<div id="content">
		<div class="centercontent">
			<?php
			$month = isset($_GET['month']) ? str_pad($_GET['month'], 2, 0, STR_PAD_LEFT) : date('m');
			$calendar->displayTodaysEvents($month, $day, $year);
			echo "<h2>".$LANG['whats_new']."</h2>\n";
			$result = mysql_query("SELECT `frontpage` FROM `fcms_users` WHERE `id` = " . $_SESSION['login_id']) or die("<h1>Frontpage Settings Error</h1>" . mysql_error());
			$r = mysql_fetch_array($result);
			mysql_free_result($result);
			if ($r['frontpage'] < 2) {
				displayWhatsNewAll($_SESSION['login_id']);
			} else {
				include_once('inc/messageboard_class.php');
				include_once('inc/addressbook_class.php');
				include_once('inc/familynews_class.php');
				include_once('inc/gallery_class.php');
				include_once('inc/prayers_class.php');
				include_once('inc/recipes_class.php');
				$mboard = new MessageBoard($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
				$book = new AddressBook($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
				$news = new FamilyNews($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
				$gallery = new PhotoGallery($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
				$prayers = new Prayers($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
				$recs = new Recipes($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
				echo "<div class=\"half\">\n";
				$today = date('Y-m-d');
				$tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
				$mboard->displayWhatsNewMessageBoard();
				$book->displayWhatsNewAddressBook();
				if (usingFamilyNews()) {
					$news->displayWhatsNewFamilyNews();
				}
				if (usingPrayers()) {
					$prayers->displayWhatsNewPrayers();
				}
				if (usingRecipes()) {
					$recs->displayWhatsNewRecipes();
				}
				echo "\t\t\t</div>\n\t\t\t<div class=\"half\">\n";
				$gallery->displayWhatsNewGallery();
				echo "\n\t\t\t\t<h3>".$LANG['comments']."</h3>\n\t\t\t\t<ul class=\"twolines\">\n";
				$sql_comments = '';
				if (usingFamilyNews()) {
					$sql_comments = "SELECT n.`user` AS 'id', n.`id` as 'id2', `comment`, nc.`date`, nc.`user`, 'NEWS' AS 'check' FROM `fcms_news_comments` AS nc, `fcms_news` AS n, `fcms_users` AS u WHERE nc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)  AND nc.`user` = u.`id` AND n.`id` = nc.`news` UNION ";
				}
				$sql_comments .= "SELECT `filename` AS 'id', 0 as 'id2', `comment`, gc.`date`, gc.`user`, `category` AS 'check' FROM `fcms_gallery_comments` AS gc, `fcms_users` AS u, `fcms_gallery_photos` AS p WHERE gc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND gc.`user` = u.`id` AND gc.`photo` = p.`id` ORDER BY `date` DESC LIMIT 5";
				$result = mysql_query($sql_comments);
				if (mysql_num_rows($result) > 0) {
					while ($com = mysql_fetch_array($result)) {
						$comment = $com['comment'];
						if (strlen($comment) > 30) { $comment = substr($comment, 0, 27) . "..."; }
						echo "\t\t\t\t\t<li";
						if(strtotime($com['date']) >= strtotime($today) && strtotime($com['date']) > $tomorrow) { echo " class=\"new\""; }
						echo "><a href=\"";
						if ($com['check'] !== 'NEWS') {
							echo "gallery/index.php?commentpid=" . $com['id'];
						} else {
							echo "familynews.php?getnews=" . $com['id'] . "&amp;newsid=" . $com['id2'];
						}
						echo "\">$comment</a><br/><span>".$com['date']." - <a href=\"profile.php?member=".htmlentities($com['user'], ENT_COMPAT, 'UTF-8')."\" class=\"u\">".getUserDisplayName($com['user'])."</a></span></li>\n";
					}
				} else {
					echo "\t\t\t\t\t<li><i>".$LANG['nothing_new_30']."</i></li>\n";
				}
				echo "\t\t\t\t</ul>\n\t\t\t</div>\n";
			}
			?>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>