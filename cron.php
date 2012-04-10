<?php
/**
 * Cron
 *
 * Part of the FCMS Scheduler, which allows users without cron to run scheduled jobs.
 *
 * Sending a asynchronous POST request to this file will not slow down the user while
 * the scheduled jobs are being run.
 *
 * PHP versions 4 and 5
 *  
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2011 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.6
 */

require_once "inc/config_inc.php";

$connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
if (!$connection)
{
    logError(__FILE__.' ['.__LINE__.'] - Could not connect to db.');
    die();
}
if (!mysql_select_db($cfg_mysql_db))
{
    logError(__FILE__.' ['.__LINE__.'] - Could not select db.');
    die();
}

require_once 'inc/constants.php';
require_once 'inc/utils.php';
require_once 'inc/cron.php';

if (isset($_POST['job_type']))
{
    $jobType = $_POST['job_type'];
}
elseif (isset($argv[1]) && substr($argv[1], 0, 8) == 'job_type')
{
    $jobType = substr($argv[1], 9);
}
else
{
    logError(__FILE__.' ['.__LINE__.'] - No job type given.');
    die();
}

// Stop, if we are currently running a job
if (runningJob() or defined('RUNNING_JOB'))
{
    if (debugOn())
    {
        logError(__FILE__.' ['.__LINE__.'] - Cron Job already running.');
    }
    die();
}

define('RUNNING_JOB', true);
runJob();

switch ($jobType)
{
    case 'awards':

        runAwardsJob();
        break;

    case 'familynews':

        runFamilyNewsJob();
        break;

    case 'youtube':

        runYouTubeJob();
        break;

    case 'instagram':

        runInstagramJob();
        break;

    default:

        logError(__FILE__.' ['.__LINE__.'] - Invalid job type given ['.$jobType.'].');
        break;
}

stopJob();

die();
