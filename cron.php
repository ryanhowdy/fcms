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

$debug = false; // Set to true when debugging issues

if ($debug)
{
    $file = fopen('inc/debug.txt', 'a+') or die();
    fwrite($file, "[".date('Y-m-d H:i:s')."] cron.php - Initialized\n") or die();
}

if (!isset($_POST['job_type']))
{
    if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] cron.php - No type given\n") or die(); }
    die();
}

require_once "inc/config_inc.php";

$connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass) or die();
mysql_select_db($cfg_mysql_db) or die();

require_once "inc/utils.php";
require_once "inc/cron.php";

// Stop, if we are currently running a job
if (runningJob() or defined('RUNNING_JOB'))
{
    if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] cron.php - Job already running\n") or die(); }
    die();
}

define('RUNNING_JOB', true);
runJob();
if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] cron.php - Job Started\n") or die(); }

switch ($_POST['job_type'])
{
    case 'familynews':

        if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] cron.php - Family News Import\n") or die(); }
        runFamilyNewsJob();
        break;

    case 'youtube':

        if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] cron.php - YouTube Videos Import\n") or die(); }
        runYouTubeJob();
        break;

    default:

        if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] cron.php - No job type found [".$_POST['job_type']."]\n") or die(); }
        break;
}

stopJob();

if ($debug)
{
    fwrite($file, "[".date('Y-m-d H:i:s')."] cron.php - Job Ended\n") or die();
    fclose($file);
}
die();
