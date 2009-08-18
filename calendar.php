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
if (isset($_GET['export'])) {
	$show = false;
	if ($_GET['export'] == 'true') {
		$cal = $calendar->exportCalendar();
		header("Content-type: text/plain");
		header("Content-disposition: ics; filename=FCMS_Calendar_".date("Y-m-d").".ics; size=".strlen($cal));
		echo $cal;
		exit();
	}
}

// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['link_calendar'];
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = <<<HTML
<script type="text/javascript" src="inc/livevalidation.js"></script>
<link rel="stylesheet" type="text/css" href="themes/datechooser.css"/>
<script type="text/javascript" src="inc/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
events.add(window, 'load', initDateChooser);
function initDateChooser() {
    var objDatePicker = new DateChooser();
    objDatePicker.setUpdateField({'sday':'j', 'smonth':'n', 'syear':'Y'});
    objDatePicker.setIcon('{$TMPL['path']}themes/default/images/datepicker.jpg', 'year');
    return true;
}
//]]>
</script>
HTML;

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
				if (checkAccess($_SESSION['login_id']) <= 5) {
                    $showcal = $calendar->displayForm('edit', $_GET['edit']);
                }
			} elseif (isset($_GET['add'])) {
				if (checkAccess($_SESSION['login_id']) <= 5) {
                    $showcal = $calendar->displayForm($_GET['add']);
                }
			} elseif (isset($_GET['entry'])) {
                $showcal = $calendar->displayForm('show', $_GET['entry']);
            } elseif (isset($_GET['import'])) {
                $showcal = false;
                $calendar->displayImportForm();
            }
                
            // Edit Calendar Entry
			if (isset($_POST['edit'])) {
				$date = $_POST['syear'] . "-" . str_pad($_POST['smonth'], 2, "0", STR_PAD_LEFT)
                    . "-" . str_pad($_POST['sday'], 2, "0", STR_PAD_LEFT);
				if (isset($_POST['private'])) {
                    $private = 1;
                } else {
                    $private = 0;
                }
				$sql = "UPDATE `fcms_calendar` "
                     . "SET `date` = '$date', "
                        . "`title`='".addslashes($_POST['title'])."', "
                        . "`desc`='".addslashes($_POST['desc'])."', "
                        . "`type`='".addslashes($_POST['type'])."', "
                        . "`private`=$private "
                     . "WHERE id = " . $_POST["id"];
				mysql_query($sql) or displaySQLError(
                    'Edit Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
				echo "<p class=\"ok-alert\" id=\"msg\"><b>" . $LANG['ok_cal_update'] . "</b><br/>";
                echo "$date - " . $_POST['type'] . "<br/>" . $_POST['title'] . "<br/>";
                echo $_POST['desc'] . "</p>";
				echo "<script type=\"text/javascript\">"
                    . "window.onload=function(){ var t=setTimeout(\"$('msg').toggle()\",3000); }"
                    . "</script>";
                
            // Add new Calendar Entry
			} else if (isset($_POST['add'])) {
				$date = $_POST['syear'] . "-" . str_pad($_POST['smonth'], 2, "0", STR_PAD_LEFT)
                    . "-" . str_pad($_POST['sday'], 2, "0", STR_PAD_LEFT);
				if (isset($_POST['private'])) {
                    $private = 1;
                } else {
                    $private = 0;
                }
				$sql = "INSERT INTO `fcms_calendar` "
                     . "(`date`, `title`, `desc`, `created_by`, `type`, `private`, `date_added`) "
                     . "VALUES ('$date', '" . addslashes($_POST['title']) . "', "
                        . "'" . addslashes($_POST['desc']) . "', " . $_SESSION['login_id'] . ", "
                        . "'".addslashes($_POST['type'])."', $private, NOW())";
				mysql_query($sql) or displaySQLError(
                    'Add Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
				echo "<p class=\"ok-alert\" id=\"msg\"><b>" . $LANG['ok_cal_add'] . "</b><br/>";
                echo "$date - " . $_POST['type'] . "<br/>" . $_POST['title'] . "<br/>";
                echo $_POST['desc']."</p>";
				echo "<script type=\"text/javascript\">"
                    . "window.onload=function(){ var t=setTimeout(\"$('msg').toggle()\",3000); }"
                    . "</script>";
                
            // Delete Calendar Entry
			} else if (isset($_POST['delete'])) {
				$sql = "DELETE FROM `fcms_calendar` WHERE id = " . $_POST["id"];
				mysql_query($sql) or displaySQLError(
                    'Delete Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
				echo "<p class=\"ok-alert\" id=\"msg\">" . $LANG['ok_cal_delete'] . "</p>";
				echo "<script type=\"text/javascript\">"
                    . "window.onload=function(){ var t=setTimeout(\"$('msg').toggle()\",3000); }"
                    . "</script>";
                
            // Import Calendar Entries
			} else if (isset($_POST['import'])) {
                $calendar->importCalendar($_FILES["file"]["tmp_name"]);
			}
			if ($showcal) {
				$year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
				$month = isset($_GET['month']) ? str_pad($_GET['month'], 2, 0, STR_PAD_LEFT) : date('m');
				$day = isset($_GET['day']) ? str_pad($_GET['day'], 2, 0, STR_PAD_LEFT) : date('d');
				$view = isset($_GET['view']) ? $_GET['view'] : 'month';
				$calendar->displayCalendar($month, $year, $day, 'big', $view);
			}
			?>
		</div><!-- #messageboard .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>
