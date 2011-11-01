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

require 'fcms.php';

load('datetime', 'calendar');

init();

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$calendar      = new Calendar($currentUserId);

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Calendar'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

control();
exit();


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
        displayExportSubmit();
    }
    elseif (isset($_GET['import']))
    {
        displayImportForm();
    }
    else if (isset($_POST['import']))
    {
        displayImportSubmit();
    }
    elseif (isset($_GET['invite']))
    {
        displayInvitationForm();
    }
    elseif (isset($_POST['submit-invite']))
    {
        displayInvitationSubmit();
    }
    elseif (isset($_GET['add']))
    {
        displayAddForm();
    }
    elseif (isset($_POST['add']))
    {
        displayAddSubmit();
    }
    elseif (isset($_GET['edit']))
    {
        displayEditForm();
    }
    elseif (isset($_POST['edit']))
    {
        displayEditSubmit();
    }
    elseif (isset($_GET['event']))
    {
        if (isset($_POST['attend_submit']))
        {
            displayAttendSubmit();
        }
        else
        {
            displayEvent();
        }
    }
    elseif (isset($_POST['delete']))
    {
        if (!isset($_POST['confirmed']))
        {
            displayDeleteConfirmationForm();
        }
        else
        {
            displayDeleteSubmit();
        }
    }
    elseif (isset($_GET['category']))
    {
        if (isset($_POST['delcat']))
        {
            displayDeleteCategorySubmit();
        }
        elseif ($_GET['category'] == 'add')
        {
            if (isset($_POST['addcat']))
            {
                displayAddCategorySubmit();
            }
            else
            {
                displayAddCategoryForm();
            }
        }
        elseif ($_GET['category'] == 'edit')
        {
            if (isset($_POST['editcat']))
            {
                displayEditCategorySubmit();
            }
            else
            {
                displayEditCategoryForm();
            }
        }
        else
        {
            displayCalendar();
        }
    }
    elseif (isset($_GET['view']))
    {
        displayCalendarDay();
    }
    else
    {
        displayCalendar();
    }
}

/**
 * displayExportSubmit 
 * 
 * @return void
 */
function displayExportSubmit ()
{
    global $calendar;

    $cal  = $calendar->exportCalendar();
    $date = fixDate('Y-m-d', $calendar->tzOffset);

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
    global $TMPL, $currentUserId;

    $TMPL['javascript'] = '
<script type="text/javascript" src="inc/js/livevalidation.js"></script>
<link rel="stylesheet" type="text/css" href="themes/datechooser.css"/>
<script type="text/javascript" src="inc/js/datechooser.js"></script>
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

    include_once getTheme($currentUserId).'header.php';

    echo '
        <div id="calendar-section" class="centercontent clearfix">';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    global $TMPL, $currentUserId;

    echo '
        </div><!-- /calendar-section -->';

    include_once getTheme($currentUserId).'footer.php';
}

/**
 * displayAddForm 
 * 
 * @return void
 */
function displayAddForm ()
{
    global $currentUserId, $calendar;

    displayHeader();

    if (checkAccess($currentUserId) > 5)
    {
        $calendar->displayCalendarMonth();
        displayFooter();
        return;
    }

    $date = cleanInput($_GET['add']);

    $calendar->displayAddForm($date);
    displayFooter();
}

/**
 * displayAddSubmit 
 * 
 * @return void
 */
function displayAddSubmit ()
{
    global $calendar, $currentUserId;

    $timeStart = "NULL";
    if (isset($_POST['timestart']) and !isset($_POST['all-day']))
    {
        $timeStart = "'".cleanInput($_POST['timestart'])."'";
    }

    $timeEnd = "NULL";
    if (isset($_POST['timeend']) and !isset($_POST['all-day']))
    {
        $timeEnd = "'".cleanInput($_POST['timeend'])."'";
    }

    $repeat = "NULL";
    if (isset($_POST['repeat-yearly']))
    {
        $repeat = "'yearly'";
    }

    $private = 0;
    if (isset($_POST['private']))
    {
        $private = 1;
    }

    $invite = 0;
    if (isset($_POST['invite']))
    {
        $invite = 1;
    }

    // Can't make a yearly event also an invitation
    $notify_user_changed_event = 0;
    if ($repeat == "'yearly'" && $invite == 1)
    {
        // Let's turn off the invitation, submit the event and tell the user what we did
        $invite = 0;
        $notify_user_changed_event = 1;
    }

    $sql = "INSERT INTO `fcms_calendar` (
                `date`, `time_start`, `time_end`, `date_added`, `title`, `desc`, `created_by`, 
                `category`, `repeat`, `private`, `invite`
            ) 
            VALUES (
                '".cleanInput($_POST['date'])."', 
                $timeStart, 
                $timeEnd, 
                NOW(),
                '".cleanInput($_POST['title'])."', 
                '".cleanInput($_POST['desc'])."', 
                '$currentUserId', 
                '".cleanInput($_POST['category'])."', 
                $repeat, 
                '$private', 
                '$invite'
            )";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Add Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $id = mysql_insert_id();

    // Display the invitation screen
    if ($invite == 1)
    {
        header("Location: calendar.php?invite=$id");
        return;
    }

    // Finish adding, show the event
    displayHeader();

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

    $calendar->displayEvent($id);
    displayFooter();
}

/**
 * displayEditForm 
 * 
 * @return void
 */
function displayEditForm ()
{
    global $currentUserId, $calendar;

    displayHeader();

    if (checkAccess($currentUserId) > 5)
    {
        $calendar->displayCalendarMonth();
        displayFooter();
        return;
    }

    $id = cleanInput($_GET['edit'], 'int');

    $calendar->displayEditForm($id);

    displayFooter();
}

/**
 * displayEditSubmit 
 * 
 * @return void
 */
function displayEditSubmit ()
{
    global $calendar;

    $year  = cleanInput($_POST['syear'], 'int');
    $month = cleanInput($_POST['smonth'], 'int'); 
    $month = str_pad($month, 2, "0", STR_PAD_LEFT);
    $day   = cleanInput($_POST['sday'], 'int');
    $day   = str_pad($day, 2, "0", STR_PAD_LEFT);
    $date  = "$year-$month-$day";

    $id = cleanInput($_POST['id'], 'int');

    $timeStart = "NULL";
    if (isset($_POST['timestart']) and !isset($_POST['all-day']))
    {
        $timeStart = "'".cleanInput($_POST['timestart'])."'";
    }
    $timeEnd = "NULL";
    if (isset($_POST['timeend']) and !isset($_POST['all-day']))
    {
        $timeEnd = "'".cleanInput($_POST['timeend'])."'";
    }
    $repeat = "NULL";
    if (isset($_POST['repeat-yearly']))
    {
        $repeat = "'yearly'";
    }
    $private = 0;
    if (isset($_POST['private']))
    {
        $private = 1;
    }
    $invite = 0;
    if (isset($_POST['invite']))
    {
        $invite = 1;
    }

    // Can't make a yearly event also an invitation
    $notify_user_changed_event = 0;
    if ($repeat == "'yearly'" && $invite == 1)
    {
        // Let's turn off the invitation, submit the event and tell the user what we did
        $invite = 0;
        $notify_user_changed_event = 1;
    }


    $sql = "UPDATE `fcms_calendar` 
            SET `date`      = '$date', 
                `time_start`= $timeStart, 
                `time_end`  = $timeEnd, 
                `title`     = '".cleanInput($_POST['title'])."', 
                `desc`      = '".cleanInput($_POST['desc'])."', 
                `category`  = '".cleanInput($_POST['category'])."', 
                `repeat`    = $repeat, 
                `private`   = '$private',
                `invite`    = '$invite' 
            WHERE id = '$id'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Edit Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    // Display the invitation screen
    if ($invite == 1)
    {
        header("Location: calendar.php?invite=$id");
        return;
    }

    displayHeader();

    // Did the user try to make a yearly event also an invitation?
    if ($notify_user_changed_event == 1)
    {
        echo '
                <div class="error-alert">
                    <h3>'.T_('You cannot invite guests to a repeating event.').'</h3>
                    <p>'.T_('The changes to this  event have been saved, but no invitations were sent.').'</p>
                    <p>'.T_('Please create a new non-repeating event and invite guests to that.').'</p>
                </div>';
        $calendar->displayEvent($id);
    }
    else
    {
        displayOkMessage();
        $calendar->displayCalendarMonth();
    }

    displayFooter();
}

/**
 * displayEvent 
 * 
 * @return void
 */
function displayEvent ()
{
    global $currentUserId, $calendar;

    displayHeader();

    if (checkAccess($currentUserId) > 5)
    {
        $calendar->displayCalendarMonth();
        displayFooter();
        return;
    }

    if (ctype_digit($_GET['event']))
    {
        $id = cleanInput($_GET['event'], 'int');
        $calendar->displayEvent($id);
    }
    elseif (strlen($_GET['event']) >= 8 && substr($_GET['event'], 0, 8) == 'birthday')
    {
        $id = substr($_GET['event'], 8);
        $id = cleanInput($id, 'int');
        $calendar->displayBirthdayEvent($id);
    }
    else
    {
        echo '<div class="info-alert"><h2>'.T_('I can\'t seem to find that calendar event.').'</h2>';
        echo '<p>'.T_('Please double check and try again.').'</p></div>';
    }

    displayFooter();
}

/**
 * displayImportForm 
 * 
 * @return void
 */
function displayImportForm ()
{
    global $calendar;

    displayHeader();
    $calendar->displayImportForm();
    displayFooter();
}

/**
 * displayImportSubmit 
 * 
 * @return void
 */
function displayImportSubmit ()
{
    global $calendar;

    displayHeader();

    $file_name = cleanInput($_FILES["file"]["tmp_name"]);

    $calendar->importCalendar($file_name);
    displayFooter();
}

/**
 * displayDeleteConfirmationForm 
 * 
 * @return void
 */
function displayDeleteConfirmationForm ()
{
    displayHeader();

    echo '
            <div class="info-alert clearfix">
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
    displayFooter();
}

/**
 * displayDeleteSubmit 
 * 
 * @return void
 */
function displayDeleteSubmit ()
{
    global $calendar;

    displayHeader();

    $sql = "DELETE FROM `fcms_calendar` 
            WHERE id = '".cleanInput($_POST['id'], 'int')."'";
    if (!mysql_query($sql))
    {
        displaySQLError('Delete Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayOkMessage(T_('Calendar Entry Deleted Successfully.'));
    $calendar->displayCalendarMonth();
    displayFooter();
}

/**
 * displayAddCategoryForm 
 * 
 * @return void
 */
function displayAddCategoryForm ()
{
    global $calendar;

    displayHeader();
    $calendar->displayCategoryForm();
    displayFooter();
}

/**
 * displayAddCategorySubmit 
 * 
 * @return void
 */
function displayAddCategorySubmit ()
{
    global $calendar, $currentUserId;

    displayHeader();

    $_POST['colors'] = isset($_POST['colors']) ? $_POST['colors'] : 'none';

    $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`, `color`)
            VALUES (
                '".cleanInput($_POST['name'])."', 
                'calendar',
                '".$currentUserId."', 
                NOW(),
                '".cleanInput($_POST['colors'])."'
            )";

    if (!mysql_query($sql))
    {
        displaySQLError('Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayOkMessage();
    $calendar->displayCalendarMonth();
    displayFooter();
}

/**
 * displayEditCategorySubmit 
 * 
 * @return void
 */
function displayEditCategorySubmit ()
{
    global $calendar;

    displayHeader();

    $id     = cleanInput($_POST['id'], 'int');
    $name   = cleanInput($_POST['name']);
    $colors = cleanInput($_POST['colors']);

    $sql = "UPDATE `fcms_category`
            SET
                `name`  = '$name',
                `color` = '$colors'
            WHERE `id`  = '$id'";

    if (!mysql_query($sql))
    {
        displaySQLError('Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayOkMessage();
    $calendar->displayCalendarMonth();
    displayFooter();
}

/**
 * displayEditCategoryForm 
 * 
 * @return void
 */
function displayEditCategoryForm ()
{
    global $calendar;

    displayHeader();

    $id = cleanInput($_GET['id'], 'int');

    $calendar->displayCategoryForm($id);
    displayFooter();
}

/**
 * displayDeleteCategorySubmit 
 * 
 * @return void
 */
function displayDeleteCategorySubmit ()
{
    global $calendar;

    displayHeader();

    $sql = "DELETE FROM `fcms_category` 
            WHERE `id` = '".cleanInput($_POST['id'], 'int')."'";

    if (!mysql_query($sql))
    {
        displaySQLError('Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayOkMessage();
    $calendar->displayCalendarMonth();
    displayFooter();
}

/**
 * displayCalendarDay 
 * 
 * @return void
 */
function displayCalendarDay ()
{
    global $calendar;

    displayHeader();

    $year  = cleanInput($_GET['year'], 'int');
    $month = cleanInput($_GET['month'], 'int'); 
    $month = str_pad($month, 2, "0", STR_PAD_LEFT);
    $day   = cleanInput($_GET['day'], 'int');
    $day   = str_pad($day, 2, "0", STR_PAD_LEFT);
    $date  = "$year-$month-$day";

    $calendar->displayCalendarDay($month, $year, $day);
    displayFooter();
}

/**
 * displayCalendar 
 * 
 * @return void
 */
function displayCalendar ()
{
    global $calendar;

    displayHeader();

    // Use the supplied date, if available
    if (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['day']))
    {
        $year  = cleanInput($_GET['year'], 'int');
        $month = cleanInput($_GET['month'], 'int'); 
        $month = str_pad($month, 2, "0", STR_PAD_LEFT);
        $day   = cleanInput($_GET['day'], 'int');
        $day   = str_pad($day, 2, "0", STR_PAD_LEFT);
        $date  = "$year-$month-$day";

        $calendar->displayCalendarMonth($month, $year, $day);
    }
    // use today's date
    else
    {
        $calendar->displayCalendarMonth();
    }

    displayFooter();
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
    global $currentUserId;

    displayHeader();

    $calendarId = cleanInput($id, 'int');

    if (isset($_GET['invite']))
    {
        $calendarId = cleanInput($_GET['invite'], 'int');
    }

    if ($calendarId == 0)
    {
        echo '<p class="error-alert">'.T_('Invalid ID.').'</p>';
        displayFooter();
        return;
    }

    // Get calendar invite options
    $sql = "SELECT `id`, `date`, `time_start`, `time_end`, `date_added`, 
                `title`, `desc`, `created_by`, `category`, `repeat`, `private`
            FROM `fcms_calendar` 
            WHERE `id` = '$calendarId' 
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Members Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }
    $event = mysql_fetch_array($result);

    // only creator, or admin can edit this invitation
    if ($event['created_by'] != $currentUserId && getAccessLevel($currentUserId) > 1)
    {
        echo '<p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';
        displayFooter();
        return;
    }

    // Get members
    $sql = "SELECT `id`, `email` 
            FROM `fcms_users` 
            WHERE `activated` > 0
            AND `password` != 'NONMEMBER'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Members Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }
    while ($r = mysql_fetch_array($result))
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
        if ($id == $currentUserId)
        {
            continue;
        }

        $rows .= '<tr>';
        $rows .= '<td class="chk"><input type="checkbox" id="member'.$id.'" name="member[]" value="'.cleanOutput($id).'"/></td>';
        $rows .= '<td>'.cleanOutput($members[$id]['name']).'</td>';
        $rows .= '<td>'.cleanOutput($members[$id]['email']);
        $rows .= '<input type="hidden" name="id'.$id.'" value="'.cleanOutput($members[$id]['email']).'"/></td></tr>';
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

    displayFooter();
}

/**
 * displayInvitationSubmit 
 * 
 * @return void
 */
function displayInvitationSubmit ()
{
    global $currentUserId, $calendar;

    displayHeader();

    $calendarId = cleanInput($_POST['calendar'], 'int');

    // make sure the user submitted atleast one email address
    if (!isset($_POST['all-members']) && !isset($_POST['email']) && !isset($_POST['non-member-emails']))
    {
        $error = T_('You must invite at least one guest.');
        displayInvitationForm($calendarId, $error);
        return;
    }

    // Get any invitations already sent for this event
    $invitations = getInvitations($calendarId, true);
    if ($invitations === false)
    {
        displayFooter();
        return;
    }

    if (!isset($invitations['_current_user']))
    {
        // add the current user (host) to the invite as attending
        $sql = "INSERT INTO `fcms_invitation` (`event_id`, `user`, `created`, `updated`, `attending`)
                VALUES ('$calendarId', '$currentUserId', NOW(), NOW(), 1)";
        if (!mysql_query($sql))
        {
            displaySQLError('Invitation Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }
    }

    // Get the calendar event title
    $sql = "SELECT `title` FROM `fcms_calendar` WHERE `id` = '$calendarId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Calendar Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }
    $r = mysql_fetch_array($result);

    $title = cleanOutput($r['title']);

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
                AND `id` != $currentUserId";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Members Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }
        while ($r = mysql_fetch_array($result))
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
        $fromName = getUserDisplayName($currentUserId);
        $url      = getDomainAndDir();

        // member
        if (is_array($invitee))
        {
            $user    = cleanInput($invitee['id'], 'int');
            $toEmail = rtrim($invitee['email']);
            $toEmail = cleanInput($toEmail);
            $toName  = getUserDisplayName($user);
            $email   = "NULL";
            $url    .= 'calendar.php?event='.$calendarId;
        }
        // non member
        else
        {
            $user    = 0;
            $toEmail = rtrim($invitee);
            $toEmail = cleanInput($toEmail);
            $toName  = $toEmail;
            $email   = "'$toEmail'";
            $url    .= 'invitation.php?event='.$calendarId.'&code='.$code;
        }

        // Skip email address that have already been invited
        if (isset($invitations[$toEmail]))
        {
            continue;
        }

        // add an invitation to db
        $sql = "INSERT INTO `fcms_invitation` (`event_id`, `user`, `email`, `created`, `updated`, `code`)
                VALUES ('$calendarId', '$user', $email, NOW(), NOW(), '$code')";
        if (!mysql_query($sql))
        {
            displaySQLError('Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
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
    $calendar->displayEvent($calendarId);
    displayFooter();
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
    global $currentUserId, $calendar;

    displayHeader();

    $calendarId = cleanInput($_GET['event'], 'int');
    $id         = cleanInput($_POST['id'], 'int');
    $attending  = isset($_POST['attending']) ? cleanInput($_POST['attending'], 'int') : "NULL";
    $response   = cleanInput($_POST['response']);

    $sql = "UPDATE `fcms_invitation`
            SET `response` = '$response',
                `attending` = $attending,
                `updated` = NOW()
            WHERE `id` = '$id'";
    if (!mysql_query($sql))
    {
        displaySQLError('Attending Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        exit();
    }

    $calendar->displayEvent($calendarId);
    displayFooter();
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
    global $currentUserId;

    $sql = "SELECT i.`id`, i.`user`, i.`email`, i.`attending`, i.`response`, i.`updated`,
                u.`email` AS user_email
            FROM `fcms_invitation` AS i
            LEFT JOIN `fcms_users` AS u
            ON i.`user` = u.`id`
            WHERE `event_id` = '$eventId'
            ORDER BY `updated` DESC";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Attending Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }

    $data = array();

    while ($r = mysql_fetch_assoc($result))
    {
        if ($currentUserId == $r['user'])
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
