<?php
/**
 * Calendar
 * 
 * PHP versions 4 and 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('datetime', 'calendar');

init();

$calendar = new Calendar($fcmsError, $fcmsDatabase, $fcmsUser);
$page     = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $calendar);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsCalendar;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsCalendar)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
        $this->fcmsCalendar = $fcmsCalendar;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Calendar'),
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        $this->control();
    }

    /**
     * control 
     * 
     * The controlling structure for this script.
     * 
     * @return void
     */
    function control ()
    {
        if (isset($_GET['export']))
        {
            $this->displayExportSubmit();
        }
        elseif (isset($_GET['import']))
        {
            $this->displayImportForm();
        }
        else if (isset($_POST['import']))
        {
            $this->displayImportSubmit();
        }
        elseif (isset($_GET['invite']))
        {
            $this->displayInvitationForm();
        }
        elseif (isset($_POST['submit-invite']))
        {
            $this->displayInvitationSubmit();
        }
        elseif (isset($_GET['add']))
        {
            $this->displayAddForm();
        }
        elseif (isset($_POST['add']))
        {
            $this->displayAddSubmit();
        }
        elseif (isset($_GET['edit']))
        {
            $this->displayEditForm();
        }
        elseif (isset($_POST['edit']))
        {
            $this->displayEditSubmit();
        }
        elseif (isset($_GET['event']))
        {
            if (isset($_POST['attend_submit']))
            {
                $this->displayAttendSubmit();
            }
            else
            {
                $this->displayEvent();
            }
        }
        elseif (isset($_POST['delete']))
        {
            if (!isset($_POST['confirmed']))
            {
                $this->displayDeleteConfirmationForm();
            }
            else
            {
                $this->displayDeleteSubmit();
            }
        }
        elseif (isset($_GET['category']))
        {
            if (isset($_POST['delcat']))
            {
                $this->displayDeleteCategorySubmit();
            }
            elseif ($_GET['category'] == 'add')
            {
                if (isset($_POST['addcat']))
                {
                    $this->displayAddCategorySubmit();
                }
                else
                {
                    $this->displayAddCategoryForm();
                }
            }
            elseif ($_GET['category'] == 'edit')
            {
                if (isset($_POST['editcat']))
                {
                    $this->displayEditCategorySubmit();
                }
                else
                {
                    $this->displayEditCategoryForm();
                }
            }
            else
            {
                $this->displayCalendar();
            }
        }
        elseif (isset($_GET['view']))
        {
            $this->displayCalendarDay();
        }
        elseif (isset($_GET['print']))
        {
            $this->displayPrintCalendar();
        }
        else
        {
            $this->displayCalendar();
        }
    }

    /**
     * displayExportSubmit 
     * 
     * @return void
     */
    function displayExportSubmit ()
    {
        $cal  = $this->fcmsCalendar->exportCalendar();
        $date = fixDate('Y-m-d', $this->fcmsUser->tzOffset);

        header("Cache-control: private");
        header("Content-type: text/plain");
        header("Content-disposition: ics; filename=FCMS_Calendar_$date.ics; size=".strlen($cal));
        echo $cal;
    }

    /**
     * displayHeader 
     * 
     * @return void
     */
    function displayHeader ()
    {
        $TMPL = $this->fcmsTemplate;

        $TMPL['javascript'] = '
<script type="text/javascript" src="ui/js/livevalidation.js"></script>
<link rel="stylesheet" type="text/css" href="ui/datechooser.css"/>
<script type="text/javascript" src="ui/js/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    initHideAdd();
    initCalendarHighlight();
    initDisableTimes();
    initHideMoreDetails(\''.T_('Add More Details').'\');
    initCalendarClickRow();
    initAttendingEvent();
    initInviteAll();
    initInviteAttending();
    // Datpicker
    var objDatePicker = new DateChooser();
    objDatePicker.setUpdateField({\'sday\':\'j\', \'smonth\':\'n\', \'syear\':\'Y\'});
    objDatePicker.setIcon(\''.$TMPL['path'].'ui/themes/default/images/datepicker.jpg\', \'syear\');
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

        include_once getTheme($this->fcmsUser->id).'header.php';

        echo '
        <div id="calendar-section" class="centercontent">';
    }

    /**
     * displayFooter 
     * 
     * @return void
     */
    function displayFooter ()
    {
        $TMPL = $this->fcmsTemplate;

        echo '
        </div><!-- /calendar-section -->';

        include_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayAddForm 
     * 
     * @return void
     */
    function displayAddForm ()
    {
        $this->displayHeader();

        if ($this->fcmsUser->access > 5)
        {
            $this->fcmsCalendar->displayCalendarMonth();
            $this->displayFooter();
            return;
        }

        $date = strip_tags($_GET['add']);

        $this->fcmsCalendar->displayAddForm($date);
        $this->displayFooter();
    }

    /**
     * displayAddSubmit 
     * 
     * @return void
     */
    function displayAddSubmit ()
    {
        $timeStart = null;
        $timeEnd   = null;
        $repeat    = null;
        $private   = 0;
        $invite    = 0;

        if (isset($_POST['timestart']) and !isset($_POST['all-day']))
        {
            $timeStart = $_POST['timestart'];
        }
        if (isset($_POST['timeend']) and !isset($_POST['all-day']))
        {
            $timeEnd = $_POST['timeend'];
        }
        if (isset($_POST['repeat-yearly']))
        {
            $repeat = 'yearly';
        }
        if (isset($_POST['private']))
        {
            $private = 1;
        }
        if (isset($_POST['invite']))
        {
            $invite = 1;
        }

        // Can't make a yearly event also an invitation
        $notify_user_changed_event = 0;
        if ($repeat == 'yearly' && $invite == 1)
        {
            // Let's turn off the invitation, submit the event and tell the user what we did
            $invite = 0;
            $notify_user_changed_event = 1;
        }

        $sql = "INSERT INTO `fcms_calendar` (
                    `date`, `time_start`, `time_end`, `date_added`, `title`, `desc`, `created_by`, 
                    `category`, `repeat`, `private`, `invite`
                ) 
                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";

        $params = array(
            $_POST['date'], 
            $timeStart, 
            $timeEnd, 
            $_POST['title'], 
            $_POST['desc'], 
            $this->fcmsUser->id, 
            $_POST['category'], 
            $repeat, 
            $private, 
            $invite
        );

        $id = $this->fcmsDatabase->insert($sql, $params);

        if ($id === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // Display the invitation screen
        if ($invite == 1)
        {
            header("Location: calendar.php?invite=$id");
            return;
        }

        // Finish adding, show the event
        $this->displayHeader();

        // Did the user try to make a yearly event also an invitation?
        if ($notify_user_changed_event == 1)
        {
            echo '
                <div class="error-alert">
                    <h3>'.T_('You cannot invite guests to a repeating event.').'</h3>
                    <p>'.T_('Your event was created, but no invitations were sent.').'</p>
                    <p>'.T_('Please create a new non-repeating event and invite guests to that.').'</p>
                </div>';
        }
        else
        {
            displayOkMessage();
        }

        $this->fcmsCalendar->displayEvent($id);
        $this->displayFooter();
    }

    /**
     * displayEditForm 
     * 
     * @return void
     */
    function displayEditForm ()
    {
        $this->displayHeader();

        if ($this->fcmsUser->access > 5)
        {
            $this->fcmsCalendar->displayCalendarMonth();
            $this->displayFooter();
            return;
        }

        $id = (int)$_GET['edit'];

        $this->fcmsCalendar->displayEditForm($id);

        $this->displayFooter();
    }

    /**
     * displayEditSubmit 
     * 
     * @return void
     */
    function displayEditSubmit ()
    {
        $id        = (int)$_POST['id'];
        $year      = (int)$_POST['syear'];
        $month     = (int)$_POST['smonth']; 
        $month     = str_pad($month, 2, "0", STR_PAD_LEFT);
        $day       = (int)$_POST['sday'];
        $day       = str_pad($day, 2, "0", STR_PAD_LEFT);
        $date      = "$year-$month-$day";
        $title     = strip_tags($_POST['title']);
        $desc      = strip_tags($_POST['desc']);
        $category  = strip_tags($_POST['category']);
        $timeStart = null;
        $timeEnd   = null;
        $repeat    = null;
        $private   = 0;
        $invite    = 0;


        if (isset($_POST['timestart']) and !isset($_POST['all-day']))
        {
            $timeStart = $_POST['timestart'];
        }
        if (isset($_POST['timeend']) and !isset($_POST['all-day']))
        {
            $timeEnd = $_POST['timeend'];
        }
        if (isset($_POST['repeat-yearly']))
        {
            $repeat = 'yearly';
        }
        if (isset($_POST['private']))
        {
            $private = 1;
        }
        if (isset($_POST['invite']))
        {
            $invite = 1;
        }

        // Can't make a yearly event also an invitation
        $notify_user_changed_event = 0;
        if ($repeat == 'yearly' && $invite == 1)
        {
            // Let's turn off the invitation, submit the event and tell the user what we did
            $invite = 0;
            $notify_user_changed_event = 1;
        }

        $sql = "UPDATE `fcms_calendar` 
                SET `date`      = ?,
                    `time_start`= ?,
                    `time_end`  = ?,
                    `title`     = ?,
                    `desc`      = ?,
                    `category`  = ?,
                    `repeat`    = ?,
                    `private`   = ?,
                    `invite`    = ?
                WHERE id = ?";

        $params = array(
            $date, 
            $timeStart,
            $timeEnd, 
            $title, 
            $desc, 
            $category, 
            $repeat, 
            $private,
            $invite,
            $id
        );

        if ($this->fcmsDatabase->insert($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Display the invitation screen
        if ($invite == 1)
        {
            header("Location: calendar.php?invite=$id");
            return;
        }

        $this->displayHeader();

        // Did the user try to make a yearly event also an invitation?
        if ($notify_user_changed_event == 1)
        {
            echo '
                <div class="error-alert">
                    <h3>'.T_('You cannot invite guests to a repeating event.').'</h3>
                    <p>'.T_('The changes to this  event have been saved, but no invitations were sent.').'</p>
                    <p>'.T_('Please create a new non-repeating event and invite guests to that.').'</p>
                </div>';
            $this->fcmsCalendar->displayEvent($id);
        }
        else
        {
            displayOkMessage();
            $this->fcmsCalendar->displayCalendarMonth();
        }

        $this->displayFooter();
    }

    /**
     * displayEvent 
     * 
     * @return void
     */
    function displayEvent ()
    {
        $this->displayHeader();

        if ($this->fcmsUser->access > 5)
        {
            $this->fcmsCalendar->displayCalendarMonth();
            $this->displayFooter();
            return;
        }

        if (ctype_digit($_GET['event']))
        {
            $id = (int)$_GET['event'];
            $this->fcmsCalendar->displayEvent($id);
        }
        elseif (strlen($_GET['event']) >= 8 && substr($_GET['event'], 0, 8) == 'birthday')
        {
            $id = substr($_GET['event'], 8);
            $id = (int)$id;
            $this->fcmsCalendar->displayBirthdayEvent($id);
        }
        else
        {
            echo '<div class="info-alert"><h2>'.T_('I can\'t seem to find that calendar event.').'</h2>';
            echo '<p>'.T_('Please double check and try again.').'</p></div>';
        }

        $this->displayFooter();
    }

    /**
     * displayImportForm 
     * 
     * @return void
     */
    function displayImportForm ()
    {
        $this->displayHeader();
        $this->fcmsCalendar->displayImportForm();
        $this->displayFooter();
    }

    /**
     * displayImportSubmit 
     * 
     * @return void
     */
    function displayImportSubmit ()
    {
        $this->displayHeader();

        $file_name = $_FILES["file"]["tmp_name"];

        if ($this->fcmsCalendar->importCalendar($file_name))
        {
            displayOkMessage();
            $this->fcmsCalendar->displayCalendarMonth();
        }

        $this->displayFooter();
    }

    /**
     * displayDeleteConfirmationForm 
     * 
     * @return void
     */
    function displayDeleteConfirmationForm ()
    {
        $this->displayHeader();

        echo '
            <div class="info-alert">
                <form action="calendar.php" method="post">
                    <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                    <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                    <div>
                        <input type="hidden" name="id" value="'.(int)$_POST['id'].'"/>
                        <input type="hidden" name="confirmed" value="1"/>
                        <input style="float:left;" type="submit" id="delconfirm" name="delete" value="'.T_('Yes').'"/>
                        <a style="float:right;" href="calendar.php">'.T_('Cancel').'</a>
                    </div>
                </form>
            </div>';

        $this->displayFooter();
    }

    /**
     * displayDeleteSubmit 
     * 
     * @return void
     */
    function displayDeleteSubmit ()
    {
        $this->displayHeader();

        $sql = "DELETE FROM `fcms_calendar` 
                WHERE id = ?";

        if (!$this->fcmsDatabase->delete($sql, $_POST['id']))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        displayOkMessage(T_('Calendar Entry Deleted Successfully.'));
        $this->fcmsCalendar->displayCalendarMonth();
        $this->displayFooter();
    }

    /**
     * displayAddCategoryForm 
     * 
     * @return void
     */
    function displayAddCategoryForm ()
    {
        $this->displayHeader();
        $this->fcmsCalendar->displayCategoryForm();
        $this->displayFooter();
    }

    /**
     * displayAddCategorySubmit 
     * 
     * @return void
     */
    function displayAddCategorySubmit ()
    {
        $this->displayHeader();

        $name   = strip_tags($_POST['name']);
        $colors = 'none';

        if (isset($_POST['colors']))
        {
            $colors = $_POST['colors'];
        }

        $sql = "INSERT INTO `fcms_category`
                    (`name`, `type`, `user`, `date`, `color`)
                VALUES
                    (?, ?, ?, NOW(), ?)";

        $params = array(
            $name, 
            'calendar',
            $this->fcmsUser->id, 
            $colors
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        displayOkMessage();
        $this->fcmsCalendar->displayCalendarMonth();
        $this->displayFooter();
    }

    /**
     * displayEditCategorySubmit 
     * 
     * @return void
     */
    function displayEditCategorySubmit ()
    {
        $this->displayHeader();

        $id     = (int)$_POST['id'];
        $name   = strip_tags($_POST['name']);
        $colors = strip_tags($_POST['colors']);

        $sql = "UPDATE `fcms_category`
                SET
                    `name`  = ?,
                    `color` = ?
                WHERE `id`  = ?";

        $params = array(
            $name,
            $colors,
            $id
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        displayOkMessage();
        $this->fcmsCalendar->displayCalendarMonth();
        $this->displayFooter();
    }

    /**
     * displayEditCategoryForm 
     * 
     * @return void
     */
    function displayEditCategoryForm ()
    {
        $this->displayHeader();

        $id = (int)$_GET['id'];

        $this->fcmsCalendar->displayCategoryForm($id);
        $this->displayFooter();
    }

    /**
     * displayDeleteCategorySubmit 
     * 
     * @return void
     */
    function displayDeleteCategorySubmit ()
    {
        $this->displayHeader();

        $sql = "DELETE FROM `fcms_category` 
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->delete($sql, $_POST['id']))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        displayOkMessage();
        $this->fcmsCalendar->displayCalendarMonth();
        $this->displayFooter();
    }

    /**
     * displayCalendarDay 
     * 
     * @return void
     */
    function displayCalendarDay ()
    {
        $this->displayHeader();

        $year  = (int)$_GET['year'];
        $month = (int)$_GET['month']; 
        $month = str_pad($month, 2, "0", STR_PAD_LEFT);
        $day   = (int)$_GET['day'];
        $day   = str_pad($day, 2, "0", STR_PAD_LEFT);

        $this->fcmsCalendar->displayCalendarDay($month, $year, $day);
        $this->displayFooter();
    }

    /**
     * displayCalendar 
     * 
     * @return void
     */
    function displayCalendar ()
    {
        $this->displayHeader();

        // Use the supplied date, if available
        if (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['day']))
        {
            $year  = (int)$_GET['year'];
            $month = (int)$_GET['month']; 
            $month = str_pad($month, 2, "0", STR_PAD_LEFT);
            $day   = (int)$_GET['day'];
            $day   = str_pad($day, 2, "0", STR_PAD_LEFT);

            $this->fcmsCalendar->displayCalendarMonth($month, $year, $day);
        }
        // use today's date
        else
        {
            $this->fcmsCalendar->displayCalendarMonth();
        }

        $this->displayFooter();
    }

    /**
     * displayPrintCalendar 
     * 
     * @return void
     */
    function displayPrintCalendar ()
    {
        echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.T_('lang').'" lang="'.T_('lang').'">
<head>
<title>'.getSiteName().' - '.T_('powered by').' '.getCurrentVersion().'</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<style type="text/css">
#big-calendar { width: 658px; border-collapse: collapse; }
a { text-decoration: none; }
h3 { text-align: center; }
th { height: 50px; }
td { padding: 0 0 30px 2px; width: 94px; border: 1px solid #000; vertical-align: top; line-height: 10pt; overflow: hidden; }
.weekDays { padding: 3px; background-color: #ccc; text-align: center; font-weight: bold; }
.nonMonthDay { background-color: #eee; }
.add, .prev, .next, .today, .views, .actions { display: none; }
.event { padding: 5px 0 2px 0; }
</style>
</head>
<body onload="window.print();">';

        // Use the supplied date, if available
        if (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['day']))
        {
            $year  = (int)$_GET['year'];
            $month = (int)$_GET['month']; 
            $month = str_pad($month, 2, "0", STR_PAD_LEFT);
            $day   = (int)$_GET['day'];
            $day   = str_pad($day, 2, "0", STR_PAD_LEFT);

            $this->fcmsCalendar->displayCalendarMonth($month, $year, $day);
        }
        // use today's date
        else
        {
            $this->fcmsCalendar->displayCalendarMonth();
        }

        echo '
<p>&nbsp;</p>
<p>&nbsp;</p>
</body>
</html>';
    }

    /**
     * displayInvitationForm 
     * 
     * Used for both creating and editing an invitation.
     * 
     * @param int $calendarId The calendar entry id
     * @param int $errors     Any errors from previous form
     * 
     * @return void
     */
    function displayInvitationForm ($calendarId = 0, $errors = 0)
    {
        $this->displayHeader();

        $calendarId = (int)$calendarId;

        if (isset($_GET['invite']))
        {
            $calendarId = (int)$_GET['invite'];
        }

        if ($calendarId == 0)
        {
            echo '<p class="error-alert">'.T_('Invalid ID.').'</p>';
            $this->displayFooter();
            return;
        }

        // Get calendar invite options
        $sql = "SELECT `id`, `date`, `time_start`, `time_end`, `date_added`, 
                    `title`, `desc`, `created_by`, `category`, `repeat`, `private`
                FROM `fcms_calendar` 
                WHERE `id` = ?
                LIMIT 1";

        $event = $this->fcmsDatabase->getRow($sql, $calendarId);
        if ($event === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // only creator, or admin can edit this invitation
        if ($event['created_by'] != $this->fcmsUser->id && getAccessLevel($this->fcmsUser->id) > 1)
        {
            echo '<p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';
            $this->displayFooter();
            return;
        }

        // Get members
        $sql = "SELECT `id`, `email` 
                FROM `fcms_users` 
                WHERE `activated` > 0
                AND `password` != 'NONMEMBER'";

        $rs = $this->fcmsDatabase->getRows($sql);
        if ($rs === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        foreach ($rs as $r)
        {
            $members[$r['id']] = array(
                'name'  => getUserDisplayName($r['id'], 2),
                'email' => $r['email']
            );
        }
        asort($members);

        $rows = '';
        foreach ($members as $id => $arr)
        {
            if ($id == $this->fcmsUser->id)
            {
                continue;
            }

            $rows .= '<tr>';
            $rows .= '<td class="chk"><input type="checkbox" id="member'.(int)$id.'" name="member[]" value="'.(int)$id.'"/></td>';
            $rows .= '<td>'.cleanOutput($members[$id]['name']).'</td>';
            $rows .= '<td>'.cleanOutput($members[$id]['email']);
            $rows .= '<input type="hidden" name="id'.(int)$id.'" value="'.cleanOutput($members[$id]['email']).'"/></td></tr>';
        }

        // Display the form
        echo '
            <form id="invite-form" method="post" action="calendar.php?event='.$calendarId.'">
                <fieldset>
                    <legend><span>'.T_('Choose Guests').'</span></legend>
                    <h3>'.T_('Invite Members').'</h3>
                    <p>
                        <input type="checkbox" id="all-members" name="all-members" value="yes"/>
                        <label for="all-members">'.T_('Invite all Members?').'</label>
                    </p>
                    <div id="invite-members-list">
                        <table id="invite-table" cellspacing="0" cellpadding="0">
                            <thead>
                                <tr>
                                    <th class="chk"></td> 
                                    <th>'.T_('Name').'</td> 
                                    <th>'.T_('Email').'</td> 
                                </tr>
                            </thead>
                            <tbody>
                                '.$rows.'
                            </tbody>
                        </table>
                    </div>
                    <h3>'.T_('Invite Non Members').'</h3>
                    <span>'.T_('Enter list of emails to invite. One email per line.').'</span>
                    <textarea name="non-member-emails" id="non-member-emails" rows="10" cols="63"></textarea>
                    <p style="clear:both">
                        <input type="hidden" name="calendar" value="'.$calendarId.'"/>
                        <input class="sub1" type="submit" id="submit-invite" name="submit-invite" value="'.T_('Send Invitations').'"/> 
                        '.T_('or').'&nbsp;
                        <a href="calendar.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';

        $this->displayFooter();
    }

    /**
     * displayInvitationSubmit 
     * 
     * @return void
     */
    function displayInvitationSubmit ()
    {
        $this->displayHeader();

        $calendarId = (int)$_POST['calendar'];

        // make sure the user submitted atleast one email address
        if (!isset($_POST['all-members']) && !isset($_POST['email']) && !isset($_POST['non-member-emails']))
        {
            $error = T_('You must invite at least one guest.');
            displayInvitationForm($calendarId, $error);
            return;
        }

        // Get any invitations already sent for this event
        $invitations = $this->getInvitations($calendarId, true);
        if ($invitations === false)
        {
            $this->displayFooter();
            return;
        }

        if (!isset($invitations['_current_user']))
        {
            // add the current user (host) to the invite as attending
            $sql = "INSERT INTO `fcms_invitation` 
                        (`event_id`, `user`, `created`, `updated`, `attending`)
                    VALUES 
                        (?, ?, NOW(), NOW(), 1)";

            $params = array(
                $calendarId,
                $this->fcmsUser->id,
            );

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }
        }

        // Get the calendar event title
        $sql = "SELECT `title` 
                FROM `fcms_calendar` 
                WHERE `id` = ?";

        $r = $this->fcmsDatabase->getRow($sql, $calendarId);
        if ($r === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $title      = $r['title'];
        $invitees   = array();
        $nonMembers = array();
        $members    = array();

        // get emails from textarea
        if (isset($_POST['non-member-emails']))
        {
            $nonMembers = explode("\n", $_POST['non-member-emails']);
        }

        // get any members that have been invited
        if (isset($_POST['all-members']))
        {
            $sql = "SELECT `id`, `email` 
                    FROM `fcms_users` 
                    WHERE `activated` > 0
                    AND `password` != 'NONMEMBER'
                    AND `id` != ?";

            $rows = $this->fcmsDatabase->getRows($sql, $this->fcmsUser->id);
            if (!$rows === false)
            {
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            foreach ($rows as $r)
            {
                array_push($members, array('id' => $r['id'], 'email' => $r['email']));
            }
        }
        elseif (isset($_POST['member']))
        {
            foreach ($_POST['member'] as $id)
            {
                array_push($members, array('id' => $id, 'email' => $_POST["id$id"]));
            }
        }

        // merge all emails into one big list
        $invitees = array_merge($nonMembers, $members);

        // Create the invite and send the emails to each invitee
        foreach ($invitees as $invitee)
        {
            if (empty($invitee))
            {
                continue;
            }

            // create a code for this user
            $code = uniqid('');

            $user     = 0;
            $email    = '';
            $toEmail  = '';
            $toName   = '';
            $fromName = getUserDisplayName($this->fcmsUser->id);
            $url      = getDomainAndDir();

            // member
            if (is_array($invitee))
            {
                $user    = (int)$invitee['id'];
                $toEmail = rtrim($invitee['email']);
                $toName  = getUserDisplayName($user);
                $email   = null;
                $url    .= 'calendar.php?event='.$calendarId;
            }
            // non member
            else
            {
                $user    = 0;
                $toEmail = rtrim($invitee);
                $toName  = $toEmail;
                $email   = $toEmail;
                $url    .= 'invitation.php?event='.$calendarId.'&code='.$code;
            }

            // Skip email address that have already been invited
            if (isset($invitations[$toEmail]))
            {
                continue;
            }

            // add an invitation to db
            $sql = "INSERT INTO `fcms_invitation` 
                        (`event_id`, `user`, `email`, `created`, `updated`, `code`)
                    VALUES 
                        (?, ?, ?, NOW(), NOW(), ?)";

            $params = array(
                $calendarId, 
                $user, 
                $email,
                $code
            );

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            // Send email invitation
            $subject = sprintf(T_pgettext('%s is the title of an event', 'Invitation: %s'), $title);

            $msg = sprintf(T_pgettext('%s is the name of a person, like Dear Bob,', 'Dear %s,'), $toName).'

'.sprintf(T_pgettext('The first %s is the name of a person, the second is the title of an event', '%s has invited you to %s.'), $fromName, $title).'

'.T_('Please visit the link below to view the rest of this invitation.').'

'.$url.'

----
'.T_('This is an automated response, please do not reply.').'

';
            $email_headers = getEmailHeaders();
            mail($toEmail, $subject, $msg, $email_headers);
        }

        displayOkMessage();
        $this->fcmsCalendar->displayEvent($calendarId);
        $this->displayFooter();
    }

    /**
     * displayAttendSubmit 
     * 
     * When a user submits the form telling whether they will be
     * attending an event or not.
     * 
     * @return void
     */
    function displayAttendSubmit ()
    {
        $this->displayHeader();

        $calendarId = (int)$_GET['event'];
        $attending  = isset($_POST['attending']) ? (int)$_POST['attending'] : null;

        $sql = "UPDATE `fcms_invitation`
                SET `response`  = ?,
                    `attending` = ?,
                    `updated` = NOW()
                WHERE `id` = ?";

        $params = array(
            $_POST['response'],
            $attending,
            $_POST['id']
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $this->fcmsCalendar->displayEvent($calendarId);
        $this->displayFooter();
    }

    /**
     * getInvitations 
     * 
     * Returns an array of invitations that have been sent for this event.
     * Including whether or not the invitee has responded.
     * 
     * Will also add a key of _current_user if the current user is included.
     * 
     * @param int     $eventId    The calendar event id
     * @param boolean $keyByEmail Whether or not to key the array by email or 0,1,2 etc.
     * 
     * @return array
     */
    function getInvitations ($eventId, $keyByEmail = false)
    {
        $sql = "SELECT i.`id`, i.`user`, i.`email`, i.`attending`, i.`response`, i.`updated`,
                    u.`email` AS user_email
                FROM `fcms_invitation` AS i
                LEFT JOIN `fcms_users` AS u
                ON i.`user` = u.`id`
                WHERE `event_id` = '$eventId'
                ORDER BY `updated` DESC";

        $rows = $this->fcmsDatabase->getRows($sql, $eventId);
        if (!$rows === false)
        {
            $this->fcmsError->displayError();

            return false;
        }

        $data = array();

        foreach ($rows as $r)
        {
            if ($this->fcmsUser->id == $r['user'])
            {
                $data['_current_user'] = $r;
            }

            if ($keyByEmail)
            {
                $email = isset($r['email']) ? $r['email'] : $r['user_email'];

                $data[$email] = $r;
            }
            else
            {
                $data[] = $r;
            }
        }

        return $data;
    }
}
