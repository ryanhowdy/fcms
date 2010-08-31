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
include_once('inc/locale.php');
$locale = new Locale();

// Check that the user is logged in
isLoggedIn();
$current_user_id = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");
include_once('inc/calendar_class.php');
$calendar = new Calendar($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
if (isset($_GET['export'])) {
    $show = false;
    if ($_GET['export'] == 'true') {
        $cal = $calendar->exportCalendar();
        $date = $locale->fixDate('Y-m-d', $calendar-tz_offset);
        header("Content-type: text/plain");
        header("Content-disposition: ics; filename=FCMS_Calendar_$date.ics; size=".strlen($cal));
        echo $cal;
        exit();
    }
}

// Setup the Template variables;
$TMPL['pagetitle'] = T_('Calendar');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = '
<script type="text/javascript" src="inc/livevalidation.js"></script>
<link rel="stylesheet" type="text/css" href="themes/datechooser.css"/>
<script type="text/javascript" src="inc/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initHideAdd();
    initCalendarHighlight();
    // Datpicker
    var objDatePicker = new DateChooser();
    objDatePicker.setUpdateField({\'sday\':\'j\', \'smonth\':\'n\', \'syear\':\'Y\'});
    objDatePicker.setIcon(\''.$TMPL['path'].'themes/default/images/datepicker.jpg\', \'year\');
    // Delete Confirmation
    if ($(\'delcal\')) {
        var item = $(\'delcal\');
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    }
    return true;
});
//]]>
</script>';

// Show header
include_once(getTheme($current_user_id) . 'header.php');

echo '
        <div id="calendar" class="centercontent clearfix">';
$showcal = true;
if (isset($_GET['edit'])) {
    if (checkAccess($current_user_id) <= 5) {
        $showcal = $calendar->displayForm('edit', $_GET['edit']);
    }
} elseif (isset($_GET['add'])) {
    if (checkAccess($current_user_id) <= 5) {
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
            . "`title`='".escape_string($_POST['title'])."', "
            . "`desc`='".escape_string($_POST['desc'])."', "
            . "`type`='".escape_string($_POST['type'])."', "
            . "`private`=$private "
         . "WHERE id = " . $_POST["id"];
    mysql_query($sql) or displaySQLError(
        'Edit Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    echo '
            <p class="ok-alert" id="msg">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'msg\').toggle()",3000); }
            </script>';
    
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
         . "VALUES ('$date', '" . escape_string($_POST['title']) . "', "
            . "'" . escape_string($_POST['desc']) . "', $current_user_id, "
            . "'".escape_string($_POST['type'])."', $private, NOW())";
    mysql_query($sql) or displaySQLError(
        'Add Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    echo '
            <p class="ok-alert" id="msg">'.T_('New Calendar Entry Added Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'msg\').toggle()",3000); }
            </script>';

// Confirm Delete Calendar Entry                
} else if (isset($_POST['delete']) && !isset($_POST['confirmed'])) {
    $showcal = false;
    echo '
            <div class="info-alert clearfix">
                <form action="calendar.php" method="post">
                    <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                    <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                    <div>
                        <input type="hidden" name="id" value="'.$_POST['id'].'"/>
                        <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                        <a style="float:right;" href="calendar.php">'.T_('Cancel').'</a>
                    </div>
                </form>
            </div>';

// Delete Calendar Entry
} else if (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
    $sql = "DELETE FROM `fcms_calendar` WHERE id = " . escape_string($_POST["id"]);
    mysql_query($sql) or displaySQLError(
        'Delete Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    echo '
        <p class="ok-alert" id="msg">'.T_('Calendar Entry Deleted Successfully').'</p>
        <script type="text/javascript">
            window.onload=function(){ var t=setTimeout("$(\'msg\').toggle()",3000); }
        </script>';
    
// Import Calendar Entries
} else if (isset($_POST['import'])) {
    $calendar->importCalendar($_FILES["file"]["tmp_name"]);
}
if ($showcal) {
    // Use the supplied date, if available
    if (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['day'])) {
        $year  = (int)$_GET['year'];
        $month = (int)$_GET['month'];
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $day = (int)$_GET['day'];
        $day = str_pad($day, 2, 0, STR_PAD_LEFT);
    // get today's date
    } else {
        $year = $locale->fixDate('Y', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
        $month = $locale->fixDate('m', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
        $day = $locale->fixDate('d', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
    }
    $view = isset($_GET['view']) ? $_GET['view'] : 'month';
    $calendar->displayCalendar($month, $year, $day, 'big', $view);
}

echo '
        </div><!-- #calendar .centercontent -->';

// Show Footer
include_once(getTheme($current_user_id) . 'footer.php'); ?>
