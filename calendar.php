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
$calendar = new Calendar($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$pagetitle = $LANG['link_calendar'];
$datechooser = 2;
$d = "";
$admin_d = "admin/";
include_once(getTheme($_SESSION['login_id']) . 'header.php');
?>
	<div id="leftcolumn">
        <?php
        include_once(getTheme($_SESSION['login_id']) . 'sidenav.php');
        if (checkAccess($_SESSION['login_id']) < 3) {
            include_once(getTheme($_SESSION['login_id']) . 'adminnav.php');
        }
        ?>
	</div>
	<div id="content">
		<div id="messageboard" class="centercontent">
			<?php
			$showcal = true;
			if (isset($_GET['edit'])) {
				if (checkAccess($_SESSION['login_id']) <= 5) { $showcal = $calendar->displayForm('edit', $_GET['edit']); }
			} elseif (isset($_GET['add'])) {
				if (checkAccess($_SESSION['login_id']) <= 5) { $showcal = $calendar->displayForm($_GET['add']); }
			}
			if (isset($_POST['edit'])) {
				$date = $_POST['syear'] . "-" . str_pad($_POST['smonth'], 2, "0", STR_PAD_LEFT) . "-" . str_pad($_POST['sday'], 2, "0", STR_PAD_LEFT);
				if (isset($_POST['private'])) { $private = 1; } else { $private = 0; }
				$sql = "UPDATE `fcms_calendar` SET `date`='$date', `title`='".addslashes($_POST['title'])."', `desc`='".addslashes($_POST['desc'])."', `type`='".addslashes($_POST['type'])."', `private`=$private WHERE id = " . $_POST["id"];
				mysql_query($sql) or displaySQLError('Edit Calendar Error', 'calendar.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"msg\"><b>".$LANG['ok_cal_update']."</b><br/>$date - ".$_POST['type']."<br/>".$_POST['title']."<br/>".$_POST['desc']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('msg').toggle()\",3000); }</script>";
			} else if (isset($_POST['add'])) {
				$date = $_POST['syear'] . "-" . str_pad($_POST['smonth'], 2, "0", STR_PAD_LEFT) . "-" . str_pad($_POST['sday'], 2, "0", STR_PAD_LEFT);
				if (isset($_POST['private'])) { $private = 1; } else { $private = 0; }
				$sql = "INSERT INTO `fcms_calendar`(`date`, `title`, `desc`, `created_by`, `type`, `private`, `date_added`) VALUES ('$date', '".addslashes($_POST['title'])."', '".addslashes($_POST['desc'])."', " . $_SESSION['login_id'] . ", '".addslashes($_POST['type'])."', $private, NOW())";
				mysql_query($sql) or displaySQLError('Add Calendar Error', 'calendar.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"msg\"><b>".$LANG['ok_cal_add']."</b><br/>$date - ".$_POST['type']."<br/>".$_POST['title']."<br/>".$_POST['desc']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('msg').toggle()\",3000); }</script>";
			} else if (isset($_POST['delete'])) {
				$sql = "DELETE FROM `fcms_calendar` WHERE id = " . $_POST["id"];
				mysql_query($sql) or displaySQLError('Delete Calendar Error', 'calendar.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"msg\">".$LANG['ok_cal_delete']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('msg').toggle()\",3000); }</script>";
			}
			if ($showcal) {
				$year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
				$month = isset($_GET['month']) ? str_pad($_GET['month'], 2, 0, STR_PAD_LEFT) : date('m');
				$day = isset($_GET['day']) ? str_pad($_GET['day'], 2, 0, STR_PAD_LEFT) : date('d');
				$calendar->displayCalendar($month, $year, $day, 'big');
				echo "<div class=\"caltoolbar\">";
				//<div class=\"views\"><b>Calendar View:</b> [ <a class=\"monthview\" href=\"?view=month\">Month</a> | <a class=\"weekview\" href=\"?view=week\">Week</a> | <a class=\"dayview\" href=\"?view=day\">Day</a> ] </div>";
				echo "<div class=\"prints\"><a class=\"print\" href=\"#\" onclick=\"window.open('inc/calendar_print.php?year=$year&amp;month=$month&amp;day=$day','name','width=700,height=400,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no'); return false;\">".$LANG['print']."</a></div></div>\n";
			}
			?>
		</div><!-- #messageboard .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>