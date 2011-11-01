<?php
/**
 * Scheduler
 *
 * PHP version 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @copyright 2011 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.6
 */
session_start();

define('URL_PREFIX', '../');

require URL_PREFIX.'fcms.php';

load('datetime', 'alerts');

init('admin/');

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$alert         = new Alerts($currentUserId);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Scheduler'),
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
    checkPermissions();

    if (isset($_GET['restore']))
    {
        displayRestoreSchedulesPage();
    }
    elseif (isset($_POST['save']))
    {
        displayEditScheduleSubmitPage();
    }
    elseif (isset($_GET['running_job']))
    {
        displayTurnOffRunningJobPage();
    }
    elseif (isset($_GET['alert']))
    {
        removeAlert();
    }
    else
    {
        displaySchedulerPage();
    }
}

/**
 * displayHeader
 *
 * @return void
 */
function displayHeader ()
{
    global $currentUserId, $TMPL;

    $TMPL['javascript'] = '
<script type="text/javascript">Event.observe(window, "load", function() { initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\'); });</script>';

    include_once getTheme($currentUserId, $TMPL['path']).'header.php';

    echo '
        <div id="scheduler" class="centercontent clearfix">';
}

/**
 * displayFooter
 *
 * @return void
 */
function displayFooter ()
{
    global $currentUserId, $TMPL;

    echo '
        </div><!-- /centercontent -->';

    include_once getTheme($currentUserId, $TMPL['path']).'footer.php';
}

/**
 * checkPermissions
 *
 * @return void
 */
function checkPermissions ()
{
    global $currentUserId;

    if (checkAccess($currentUserId) > 2)
    {
        displayHeader();

        echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 2 (Helper) or better.').'
                <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

        displayFooter();
        exit();
    }
}

/**
 * displaySchedulerPage
 *
 * @return void
 */
function displaySchedulerPage ()
{
    global $currentUserId, $alert;

    displayHeader();

    if (isset($_SESSION['schedule_edit']))
    {
        displayOkMessage();
        unset($_SESSION['schedule_edit']);
    }

    $alert->displayScheduler($currentUserId);

    // Check job running status
    $sql = "SELECT `value` AS 'running_job'
            FROM `fcms_config`
            WHERE `name` = 'running_job'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $row = mysql_fetch_assoc($result);

    if ($row['running_job'] > 0)
    {
        echo '
            <div class="info-alert">
                <h2>'.T_('A scheduled job is currently running.').'</h2>
                <p>'.T_('Most jobs take less than an hour to complete, if the last successfull run for a scheduled job below is over an hour ago, their may be a problem with the current job.').'</p>
                <div id="help-debug">
                    <p>'.T_('To debug this job:').'</p>
                    <ol>
                        <li>'.T_('Edit the cron.php file and set the debug option to true.').'</li>
                        <li>'.T_('Reset the running job flag.').'</p>
                    </ol>
                    <p style="text-align: right;">
                        <a href="?running_job=off">'.T_('Set running job flag to off.').'</a><br/>
                        <small>'.T_('(only if you know what you are doing)').'</small>
                    </p>
                </div>
            </div>
            <script type="text/javascript">
            if ($("help-debug")) {
                var div = $("help-debug");
                div.hide();
                var a = new Element("a", { href: "#" }).update("'.T_('Learn more.').'");
                a.onclick = function() { $("help-debug").toggle(); return false; };
                div.insert({"before":a});
            }
            </script>';
    }

    // Get schedules
    $sql = "SELECT `id`, `type`, `repeat`, `lastrun`, `status`
            FROM `fcms_schedule`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    echo '
        <form id="scheduler-frm" action="scheduler.php" method="post">
            <table class="sortable">
                <thead>
                    <tr>
                        <th>'.T_('ID').'</th>
                        <th>'.T_('Type').'</th>
                        <th>'.T_('Frequency').'</th>
                        <th>'.T_('Last Run').'</th>
                        <th>'.T_('Status').'</th>
                        <th class="nosort"></th>
                    </tr>
                </thead>
                <tbody>';

    // This shouldn't happen
    if (mysql_num_rows($result) <= 0)
    {
        echo '
                    <tr>
                        <td colspan="6" style="text-align:center">
                            <p>'.T_('No schedules found.').' <a href="?restore=schedules">'.T_('Restore missing schedules.').'</a></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>';

        displayFooter();
        return;
    }

    $onOff = array(
        '1' => T_('On'),
        '0' => T_('Off')
    );
    $frequency = array(
        'hourly' => T_('Hourly'),
        'daily'  => T_('Daily')
    );

    while ($row = mysql_fetch_assoc($result))
    {
        $id      = cleanOutput($row['id']);
        $type    = cleanOutput($row['type']);
        $lastrun = cleanOutput($row['lastrun']);

        $statusOptions = buildHtmlSelectOptions($onOff, $row['status']);
        $repeatOptions = buildHtmlSelectOptions($frequency, $row['repeat']);

        $status = '<b class="current-status-off">&nbsp;</b>';
        if ($row['status'] == 1)
        {
            $status = '<b class="current-status-on">&nbsp;</b>';
        }

        if ($lastrun == '0000-00-00 00:00:00')
        {
            $lastrun = '<i>'.T_('never').'</i>';
        }
        else
        {
            $tzOffset = getTimezone($currentUserId);
            $lastrun  = fixDate('Y-m-d h:i:s', $tzOffset, $lastrun);
        }

        echo '
                    <tr>
                        <td>
                            '.$id.'
                            <input type="hidden" name="id[]" id="id" value="'.$id.'">
                        </td>
                        <td>'.$type.'</td>
                        <td>
                            <select name="repeat[]" id="schedule_status">
                                '.$repeatOptions.'
                            </select>
                        </td>
                        <td>'.$lastrun.'</td>
                        <td>
                            <select name="status[]" id="schedule_status">
                                '.$statusOptions.'
                            </select>
                        </td>
                        <td>'.$status.'</td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>
            <p style="text-align:right">
                <input class="sub1" type="submit" name="save" id="save" value="'.T_('Save Changes').'"/>&nbsp; 
            </p>
        </form>';

    displayFooter();
}

/**
 * displayRestoreSchedulesPage 
 * 
 * @return void
 */
function displayRestoreSchedulesPage ()
{
    $sql = "INSERT INTO `fcms_schedule` (`type`, `repeat`)
            VALUES 
                ('familynews', 'hourly'),
                ('youtube', 'hourly')";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: scheduler.php");
}

/**
 * displayEditScheduleSubmitPage 
 * 
 * @return void
 */
function displayEditScheduleSubmitPage ()
{
    if (!isset($_POST['id']) or !is_array($_POST['id']))
    {
        header("Location: scheduler.php");
    }

    for ($i = 0; $i < count($_POST['id']); $i++)
    {
        if (!isset($_POST['id'][$i]) or !isset($_POST['status'][$i]) or !isset($_POST['repeat'][$i]))
        {
            continue;
        }

        $id     = cleanInput($_POST['id'][$i]);
        $repeat = cleanInput($_POST['repeat'][$i]);
        $status = cleanInput($_POST['status'][$i]);

        $sql = "UPDATE `fcms_schedule`
                SET `repeat` = '$repeat',
                    `status` = '$status'
                WHERE `id` = '$id'; ";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }
    }

    $_SESSION['schedule_edit'] = 1;
    header("Location: scheduler.php");
}

/**
 * removeAlert 
 * 
 * @return void
 */
function removeAlert ()
{
    global $currentUserId;

    if ($_GET['alert'] !== 'alert_scheduler')
    {
        return;
    }

    $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
            VALUES (
                '".cleanInput($_GET['alert'])."', 
                '$currentUserId'
            )";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Alert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: scheduler.php");
}

/**
 * displayTurnOffRunningJobPage 
 * 
 * @return void
 */
function displayTurnOffRunningJobPage ()
{
    $sql = "UPDATE `fcms_config`
            SET `value` = '0'
            WHERE `name` = 'running_job'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Running Job Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: scheduler.php");
}
