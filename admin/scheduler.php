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
define('GALLERY_PREFIX', '../gallery/');

require URL_PREFIX.'fcms.php';

load('datetime', 'alerts');

init('admin/');

// Globals
$alert = new Alerts($fcmsUser->id);

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getAdminNavLinks(),
    'pagetitle'     => T_('Administration: Scheduler'),
    'path'          => URL_PREFIX,
    'displayname'   => $fcmsUser->displayName,
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
    global $fcmsUser, $TMPL;

    $TMPL['javascript'] = '
<script src="'.URL_PREFIX.'ui/js/prototype.js" type="text/javascript"></script>';

    include_once URL_PREFIX.'ui/admin/header.php';

    echo '
        <div id="scheduler">';
}

/**
 * displayFooter
 *
 * @return void
 */
function displayFooter ()
{
    global $fcmsUser, $TMPL;

    echo '
        </div><!-- /scheduler -->';

    include_once URL_PREFIX.'ui/admin/footer.php';
}

/**
 * checkPermissions
 *
 * @return void
 */
function checkPermissions ()
{
    global $fcmsUser;

    if (checkAccess($fcmsUser->id) > 2)
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
    global $fcmsUser, $alert;

    displayHeader();

    if (isset($_SESSION['schedule_edit']))
    {
        displayOkMessage();
        unset($_SESSION['schedule_edit']);
    }

    $alert->displayScheduler($fcmsUser->id);

    // Check job running status
    $sql = "SELECT `value` AS 'running_job'
            FROM `fcms_config`
            WHERE `name` = 'running_job'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $row = mysql_fetch_assoc($result);

    if ($row['running_job'] > 0)
    {
        echo '
            <div class="alert-message block-message warning">
                <h2>'.T_('A scheduled job is currently running.').'</h2>
                <p>'.T_('Most jobs take less than an hour to complete, if the last successfull run for a scheduled job below is over an hour ago, their may be a problem with the current job.').'</p>
                <p>'.T_('To debug this job:').'</p>
                <ol>
                    <li>'.T_('Turn on debugging.').'</li>
                    <li>'.T_('Reset the running job flag.').'</p>
                </ol>
                <p>
                    <a class="btn small" href="?running_job=off">'.T_('Set running job flag to off.').'</a>
                    '.T_('(only if you know what you are doing)').'
                </p>
            </div>';
    }

    // Get schedules
    $sql = "SELECT `id`, `type`, `repeat`, `lastrun`, `status`
            FROM `fcms_schedule`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
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

    $setupCron = '';

    while ($row = mysql_fetch_assoc($result))
    {
        $id      = cleanOutput($row['id']);
        $type    = cleanOutput($row['type']);
        $lastrun = cleanOutput($row['lastrun']);

        $statusOptions = buildHtmlSelectOptions($onOff, $row['status']);
        $repeatOptions = buildHtmlSelectOptions($frequency, $row['repeat']);

        $status = '<span class="label important">'.T_('Off').'</span>';
        if ($row['status'] == 1)
        {
            $status = '<span class="label success">'.T_('On').'</span>';
        }

        if ($lastrun == '0000-00-00 00:00:00')
        {
            $lastrun = '<i>'.T_('never').'</i>';
        }
        else
        {
            $tzOffset = getTimezone($fcmsUser->id);
            $lastrun  = fixDate('Y-m-d h:i:s', $tzOffset, $lastrun);
        }

        $cronFreq = $row['repeat'] == 'daily' ? '0 0 * * *' : '0 * * * *';

        $setupCron .= '<pre class="cron-example">'.$cronFreq.' php -q '.ROOT.'cron.php job_type='.$type.'</pre>';

        echo '
                    <tr>
                        <td>
                            '.$id.'
                            <input type="hidden" name="id[]" id="id" value="'.$id.'">
                        </td>
                        <td>'.$type.'</td>
                        <td>
                            <select name="repeat[]" id="schedule_status" class="span4">
                                '.$repeatOptions.'
                            </select>
                        </td>
                        <td>'.$lastrun.'</td>
                        <td>
                            <select name="status[]" id="schedule_status" class="span2">
                                '.$statusOptions.'
                            </select>
                        </td>
                        <td>'.$status.'</td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>
            <div class="actions">
                <input class="btn primary" type="submit" name="save" id="save" value="'.T_('Save Changes').'"/>
            </div>
        </form>

        <p>&nbsp;</p>

        <h2>'.T_('Set up Cron').'</h2>
        <p>'.T_('Set up a new crontab for each of the following commands.').'</p>
        '.$setupCron;

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
        displaySqlError($sql, mysql_error());
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

        $id     = (int)$_POST['id'][$i];
        $repeat = escape_string($_POST['repeat'][$i]);
        $status = escape_string($_POST['status'][$i]);

        $sql = "UPDATE `fcms_schedule`
                SET `repeat` = '$repeat',
                    `status` = '$status'
                WHERE `id` = '$id'; ";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
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
    global $fcmsUser;

    if ($_GET['alert'] !== 'alert_scheduler')
    {
        return;
    }

    $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
            VALUES (
                '".escape_string($_GET['alert'])."', 
                '$fcmsUser->id'
            )";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
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
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: scheduler.php");
}
