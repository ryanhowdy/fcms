<?php
/**
 * Calendar
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

require_once 'inc/config_inc.php';
require_once 'inc/util_inc.php';
require_once 'inc/locale.php';
require_once 'inc/calendar_class.php';

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn();
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$locale = new Locale();
$calendar = new Calendar($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Export .ics
header("Cache-control: private");
if (isset($_GET['export'])) {
    $show = false;
    if ($_GET['export'] == 'true') {
        $cal = $calendar->exportCalendar();
        $date = $locale->fixDate('Y-m-d', $calendar->tz_offset);
        header("Content-type: text/plain");
        header("Content-disposition: ics; filename=FCMS_Calendar_$date.ics; size=".strlen($cal));
        echo $cal;
        exit();
    }
}

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Calendar'),
    'path'          => "",
    'admin_path'    => "admin/",
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript" src="inc/livevalidation.js"></script>
<link rel="stylesheet" type="text/css" href="themes/datechooser.css"/>
<script type="text/javascript" src="inc/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initHideAdd();
    initCalendarHighlight();
    initDisableTimes();
    initHideMoreDetails(\'' . T_('Add More Details') . '\');
    // Datpicker
    var objDatePicker = new DateChooser();
    objDatePicker.setUpdateField({\'sday\':\'j\', \'smonth\':\'n\', \'syear\':\'Y\'});
    objDatePicker.setIcon(\''.$TMPL['path'].'themes/default/images/datepicker.jpg\', \'syear\');
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
require_once getTheme($currentUserId) . 'header.php';

echo '
        <div id="calendar-section" class="centercontent clearfix">';

$showcal = true;

// Edit form
if (isset($_GET['edit'])) {
    if (checkAccess($currentUserId) <= 5) {
        $showcal = false;
        $id = cleanInput($_GET['edit'], 'int');
        $calendar->displayEditForm($id);
    }

// Add form
} elseif (isset($_GET['add'])) {
    if (checkAccess($currentUserId) <= 5) {
        $showcal = false;
        $date = cleanInput($_GET['add']);
        $calendar->displayAddForm($date);
    }

// View event
} elseif (isset($_GET['event'])) {
    $showcal = false;
    $id = cleanInput($_GET['event'], 'int');
    $calendar->displayEvent($_GET['event']);

// Import form
} elseif (isset($_GET['import'])) {
    $showcal = false;
    $calendar->displayImportForm();
}
    
// Edit event
if (isset($_POST['edit'])) {
    $year   = cleanInput($_POST['syear'], 'int');
    $month  = cleanInput($_POST['smonth'], 'int'); 
    $month  = str_pad($month, 2, "0", STR_PAD_LEFT);
    $day    = cleanInput($_POST['sday'], 'int');
    $day    = str_pad($day, 2, "0", STR_PAD_LEFT);
    $date   = "$year-$month-$day";

    $timeStart = "NULL";
    if (isset($_POST['timestart']) and !isset($_POST['all-day'])) {
        $timeStart = "'" . cleanInput($_POST['timestart']) . "'";
    }
    $timeEnd = "NULL";
    if (isset($_POST['timeend']) and !isset($_POST['all-day'])) {
        $timeEnd = "'" . cleanInput($_POST['timeend']) . "'";
    }
    $repeat = "NULL";
    if (isset($_POST['repeat-yearly'])) {
        $repeat = "'yearly'";
    }
    $private = 0;
    if (isset($_POST['private'])) {
        $private = 1;
    }

    $sql = "UPDATE `fcms_calendar` 
            SET `date`      = '$date', 
                `time_start`= $timeStart, 
                `time_end`  = $timeEnd, 
                `title`     = '" . cleanInput($_POST['title']) . "', 
                `desc`      = '" . cleanInput($_POST['desc']) . "', 
                `category`  = '" . cleanInput($_POST['category']) . "', 
                `repeat`    = $repeat, 
                `private`   = '$private' 
            WHERE id = '" . cleanInput($_POST['id'], 'int') . "'";
    mysql_query($sql) or displaySQLError(
        'Edit Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    echo '
            <p class="ok-alert" id="msg">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'msg\').toggle()",3000); }
            </script>';
    
// Add event
} else if (isset($_POST['add'])) {

    $timeStart = "NULL";
    if (isset($_POST['timestart']) and !isset($_POST['all-day'])) {
        $timeStart = "'" . cleanInput($_POST['timestart']) . "'";
    }
    $timeEnd = "NULL";
    if (isset($_POST['timeend']) and !isset($_POST['all-day'])) {
        $timeEnd = "'" . cleanInput($_POST['timeend']) . "'";
    }
    $repeat = "NULL";
    if (isset($_POST['repeat-yearly'])) {
        $repeat = "'yearly'";
    }
    $private = 0;
    if (isset($_POST['private'])) {
        $private = 1;
    }
    $sql = "INSERT INTO `fcms_calendar` (
                `date`, `time_start`, `time_end`, `title`, `desc`, `created_by`, 
                `category`, `repeat`, `private`, `date_added`
            ) 
            VALUES (
                '" . cleanInput($_POST['date']) . "', 
                $timeStart, 
                $timeEnd, 
                '" . cleanInput($_POST['title']) . "', 
                '" . cleanInput($_POST['desc']) . "', 
                '" . $currentUserId . "', 
                '" . cleanInput($_POST['category']) . "', 
                $repeat, 
                '$private', 
                NOW()
            )";
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
                        <input type="hidden" name="id" value="' . (int)$_POST['id'] . '"/>
                        <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                        <a style="float:right;" href="calendar.php">'.T_('Cancel').'</a>
                    </div>
                </form>
            </div>';

// Delete Calendar Entry
} else if (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
    $sql = "DELETE FROM `fcms_calendar` 
            WHERE id = '" . cleanInput($_POST['id'], 'int') . "'";
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
    $file_name = cleanInput($_FILES["file"]["tmp_name"]);
    $calendar->importCalendar($file_name);
}

//---------------------------
// Category
//---------------------------
if (isset($_GET['category'])) {

    // Add Category
    if ($_GET['category'] == 'add') {

        // Submit
        if (isset($_POST['addcat'])) {

            $_POST['colors'] = isset($_POST['colors']) ? $_POST['colors'] : 'none';

            $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`, `color`)
                    VALUES (
                        '" . cleanInput($_POST['name']) . "', 
                        'calendar',
                        '" . $currentUserId . "', 
                        NOW(),
                        '" . cleanInput($_POST['colors']) . "'
                    )";
            mysql_query($sql) or displaySQLError(
                'New Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            echo '
            <p class="ok-alert" id="msg">'.T_('Category Added Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'msg\').toggle()",3000); }
            </script>';

        // Show form
        } else {
            if (!isset($_GET['id'])) {
                $showcal = false;
                $calendar->displayCategoryForm();
            }
        }
    }

    // Edit Category
    if (isset($_GET['id'])) {

        // Submit Edit
        if (isset($_POST['editcat'])) {
            $sql = "UPDATE `fcms_category`
                    SET
                        `name`  = '" . cleanInput($_POST['name']) . "',
                        `color` = '" . cleanInput($_POST['colors']) . "'
                    WHERE `id`  = '" . cleanInput($_POST['id'], 'int') . "'";
            mysql_query($sql) or displaySQLError(
                'Edit Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            echo '
            <p class="ok-alert" id="msg">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'msg\').toggle()",3000); }
            </script>';

        // Submit Delete
        } elseif (isset($_POST['delcat'])) {
            $sql = "DELETE FROM `fcms_category` 
                    WHERE `id` = '" . cleanInput($_POST['id'], 'int') . "'";
            mysql_query($sql) or displaySQLError(
                'Delete Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            echo '
            <p class="ok-alert" id="msg">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'msg\').toggle()",3000); }
            </script>';

        // Show Form
        } else {
            $showcal = false;
            $id = cleanInput($_GET['id'], 'int');
            $calendar->displayCategoryForm($id);
        }
    }

}

if ($showcal) {
    // Use the supplied date, if available
    if (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['day'])) {
        $year   = cleanInput($_GET['year'], 'int');
        $month  = cleanInput($_GET['month'], 'int'); 
        $month  = str_pad($month, 2, "0", STR_PAD_LEFT);
        $day    = cleanInput($_GET['day'], 'int');
        $day    = str_pad($day, 2, "0", STR_PAD_LEFT);
        $date   = "$year-$month-$day";
    // get today's date
    } else {
        $year = $locale->fixDate('Y', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
        $month = $locale->fixDate('m', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
        $day = $locale->fixDate('d', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
    }

    if (isset($_GET['view'])) {
        $calendar->displayCalendarDay($month, $year, $day);
    } else {
        $calendar->displayCalendarMonth($month, $year, $day);
    }
}

echo '
        </div><!-- #calendar .centercontent -->';

// Show Footer
require_once getTheme($currentUserId) . 'footer.php'; ?>
