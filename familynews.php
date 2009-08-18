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
$pagetitle = $LANG['link_familynews'];
$d = "";
$admin_d = "admin/";
include_once(getTheme($_SESSION['login_id']) . 'header.php');
?>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<div class="firstmenu menu">
			<ul><?php
				$sql = "SELECT u.id, fname, lname, displayname, username, MAX(`date`) AS d FROM fcms_news AS n, fcms_users AS u WHERE u.id = n.user AND username != 'SITENEWS' AND `password` != 'SITENEWS' GROUP BY id ORDER BY d DESC";
				$result = mysql_query($sql) or displaySQLError('Get News Users Error', 'familynews.php [' . __LINE__ . ']', $sql, mysql_error());
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
				echo "\t\t\t<div class=\"clearfix\">";
				if ($fnews->hasNews($_SESSION['login_id'])) { echo "<a class=\"link_block news\" href=\"?getnews=".$_SESSION['login_id']."\">".$LANG['my_news']."</a> "; }
				echo "<a class=\"link_block add\" href=\"?addnews=yes\">".$LANG['add_news']."</a></div>\n";
			}
			$show_last5 = true;
			if(isset($_POST['submitadd'])) {
				$title = addslashes($_POST['title']);
				$news = addslashes($_POST['post']);
				$sql = "INSERT INTO `fcms_news`(`title`, `news`, `user`, `date`) VALUES('$title', '$news', " . $_SESSION['login_id'] . ", NOW())";
				mysql_query($sql) or displaySQLError('Add News Error', 'familynews.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"add\">".$LANG['ok_news_add']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('add').toggle()\",3000); }</script>";
			} elseif (isset($_POST['submitedit'])) {
				$show_last5 = false;
				$title = addslashes($_POST['title']);
				$news = addslashes($_POST['post']);
				$sql = "UPDATE `fcms_news` SET `title` = '$title', `news` = '$news' WHERE `id` = ".$_POST['id'];
				mysql_query($sql) or displaySQLError('Edit News Error', 'familynews.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\">".$LANG['ok_news_edit']."<br/><a href=\"familynews.php?getnews=".$_POST['user']."\">".$LANG['refresh_page']."</a>.</p>";
				echo "<meta http-equiv='refresh' content='0;URL=familynews.php?getnews=".$_POST['user']."'>";
			}
			if (isset($_GET['getnews'])) {
				$show_last5 = false;
				$page = 1; $nid = 0;
				if (isset($_GET['newspage'])) { 
					// Santizing user input - newspage - only allow digits 0-9
					if (preg_match('/^\d+$/', $_GET['newspage'])) { $page = $_GET['newspage']; }
				}
				if (isset($_GET['newsid'])) {
					// Santizing user input - newsid - only allow digits 0-9
					if (preg_match('/^\d+$/', $_GET['newsid'])) { $nid = $_GET['newsid']; }
				}
				if (isset($_POST['addcom'])) {
					$com = ltrim($_POST['comment']);
					if (!empty($com)) {
						$sql = "INSERT INTO `fcms_news_comments`(`news`, `comment`, `date`, `user`) VALUES($nid, '" . addslashes($com) . "', NOW(), " . $_SESSION['login_id'] . ")";
						mysql_query($sql) or displaySQLError('New Comment Error', 'familynews.php [' . __LINE__ . ']', $sql, mysql_error());
					}
				}
				if (isset($_POST['delcom'])) {
					$sql = "DELETE FROM fcms_news_comments WHERE id = " . $_POST['id'];
					mysql_query($sql) or displaySQLError('Delete Comment Error', 'familynews.php [' . __LINE__ . ']', $sql, mysql_error());
				}
				// Santizing user input - getnews - only allow digits 0-9
				if (preg_match('/^\d+$/', $_GET['getnews'])) {
					$fnews->showFamilyNews($_GET['getnews'], $nid, $page);
				}
			}
			if (isset($_GET['addnews']) && (checkAccess($_SESSION['login_id']) < 6 || checkAccess($_SESSION['login_id']) == 9)) { 
				$show_last5 = false;
				$fnews->displayForm('add', $_SESSION['login_id']);
			} elseif (isset($_POST['editnews'])) {
				$show_last5 = false;
				$fnews->displayForm('edit', $_POST['user'], $_POST['id'], $_POST['title'], $_POST['news']);
			} elseif (isset($_POST['delnews'])) {
				$show_last5 = false;
				$sql = "DELETE FROM `fcms_news` WHERE id = ".$_POST['id'];
				mysql_query($sql) or displaySQLError('Delete News Error', 'familynews.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\">".$LANG['ok_news_delete']."<br/><a href=\"familynews.php?getnews=" . $_POST['user'] . "\">".$LANG['refresh_page']."</a>.</p>";
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