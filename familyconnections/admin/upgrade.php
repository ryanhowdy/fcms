<?php
/**
 * Upgrade
 * 
 * PHP 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

error_reporting(-1);
ini_set('log_errors', 0);

define('ROOT', dirname(dirname(__FILE__)) . '/');
define('INC', ROOT.'inc/');
define('THIRDPARTY', INC.'/thirdparty/');
define('LATEST_VERSION_URL', 'http://www.familycms.com/latest/version.php');
define('LATEST_FILE_URL',    'http://www.familycms.com/latest/latest.zip');

require_once INC.'config_inc.php';
require_once INC.'thirdparty/gettext.inc';
require_once INC.'utils.php';
require_once INC.'upgrade_inc.php';
require_once INC.'Error.php';
require_once INC.'Database.php';

checkLoginAndPermission();

control();
exit();

/**
 * control 
 * 
 * @return void
 */
function control ()
{
    // Automatic Upgrade
    if (isset($_GET['auto']))
    {
        $step = (int)$_GET['auto'];

        // Turn Off Site
        if ($step == 1)
        {
            displayStepOne();
        }
        // Download
        elseif ($step == 2)
        {
            displayStepTwo();
        }
        // Unzip
        elseif ($step == 3)
        {
            displayStepThree();
        }
        // Install
        elseif ($step == 4)
        {
            displayStepFour();
        }
        // Upgrade Database
        elseif ($step == 5)
        {
            displayStepFive();
        }
        // Update Version
        elseif ($step == 6)
        {
            displayStepSix();
        }
        // Turn On Site
        elseif ($step == 7)
        {
            displayStepSeven();
        }
    }
    // Manual Upgrade
    elseif (isset($_GET['manual']))
    {
        displayManualUpgrade();
    }
    // Manual Upgrade Database
    elseif (isset($_GET['upgrade']))
    {
        displayUpgradeDatabase();
    }
    // Manual Upgrade Site On/Off
    elseif (isset($_GET['site']))
    {
        displayUpgradeSiteStatus($_GET['site']);
    }
    else
    {
        displayStart();
    }
}


/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    echo '
<html>
<head>
<title>'.T_('Automatic Upgrade').'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="author" content="Ryan Haudenschilt">
<link rel="shortcut icon" href="../themes/favicon.ico">
<style>
html { text-align: center; }
body { width: 755px; text-align: left; padding: 20px; font: 16px/24px arial, verdana, sans-serif; margin: 0 auto; }
img { margin: 0 0 20px 0; }
.primary {
    cursor: pointer;
    display: inline-block;
    color: #fff;
    text-decoration: none;
    background-color: #222;
    background-color: #222;
    background-repeat: repeat-x;
    background-image: -khtml-gradient(linear, left top, left bottom, from(#333333), to(#222222));
    background-image: -moz-linear-gradient(top, #333333, #222222);
    background-image: -ms-linear-gradient(top, #333333, #222222);
    background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #333333), color-stop(100%, #222222));
    background-image: -webkit-linear-gradient(top, #333333, #222222);
    background-image: -o-linear-gradient(top, #333333, #222222);
    background-image: linear-gradient(top, #333333, #222222);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#333333\', endColorstr=\'#222222\', GradientType=0);
    -webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25), inset 0 -1px 0 rgba(0, 0, 0, 0.1);
    -moz-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25), inset 0 -1px 0 rgba(0, 0, 0, 0.1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25), inset 0 -1px 0 rgba(0, 0, 0, 0.1);
    padding: 5px 20px;
    -webkit-border-radius: 4px;
    -moz-border-radius: 4px;
    border-radius: 4px;
}
ul { list-style-type: none; margin: 0; padding: 0; }
li h2 { color: #000; margin-left: -20px; }
li { color: #ccc; font-weight: bold; padding: 3px 0 3px 20px; }
li.current { color: #000; background: transparent url(../ui/images/current.gif) left center no-repeat; }
li.complete { color: green; background: transparent url(../ui/images/complete.png) left center no-repeat; }
.manual { border-bottom: 1px solid #ddd; }
.manual li { font-weight: normal; color: #000; font: 14px/18px arial, verdana, sans-serif; border: 1px solid #ddd; border-bottom: none; padding: 3px 3px 3px 30px; }
.manual li.complete { background-position: 8px 17px; }
.manual li div { float: right; color: #ddd; font: bold 18px/24px arial, verdana, sans-serif; }
</style>
</head>
<body>
<a href="../home.php"><img src="../ui/images/logo.gif"/></a>';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    echo '
</body>
</html>';
}

/**
 * checkLoginAndPermission 
 * 
 * @return void
 */
function checkLoginAndPermission ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    if (isset($_COOKIE['fcms_login_id']))
    {
        $_SESSION['login_id']    = $_COOKIE['fcms_login_id'];
        $_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
        $_SESSION['login_pw']    = $_COOKIE['fcms_login_pw'];
    }
    elseif (!isset($_SESSION['login_id']))
    {
        displayHeader();
        echo '<h1>'.T_('You must be logged in to view this page.').'</h1>';
        displayFooter();
        die();
    }

    $id       = $_SESSION['login_id'];
    $username = $_SESSION['login_uname'];
    $password = $_SESSION['login_pw'];

    if (!ctype_digit($id))
    {
        displayHeader();
        echo '<h1>'.T_('Invalid login id.').'</h1>';
        displayFooter();
        die();
    }

    $sql = "SELECT `username`, `password`, `access`
            FROM `fcms_users` 
            WHERE `id` = ?
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql, $id);
    if ($r === false)
    {
        displayHeader();
        echo '<h1>'.T_('Invalid request.').'</h1>';
        $fcmsError->displayError();
        displayFooter();
        die();
    }

    if (empty($r))
    {
        displayHeader();
        echo '<h1>'.T_('User not found.').'</h1>';
        displayFooter();
        die();
    }

    if ($r['username'] !== $username || $r['password'] !== $password)
    {
        displayHeader();
        echo '<h1>'.T_('Invalid login credentials.').'</h1>';
        displayFooter();
        die();
    }

    if ($r['access'] > 1)
    {
        displayHeader();
        echo '<h1>'.T_('You do not have access to view this page.').'</h1>';
        displayFooter();
        die();
    }
}

/**
 * displaySteps 
 * 
 * @param int $step 
 * 
 * @return void
 */
function displaySteps ($step)
{
    $latestVersion = $_SESSION['latestVersion'];

    $steps = array(
        1 => T_('Turning off site.'),
        2 => T_('Downloading latest version.'),
        3 => T_('Unpacking files.'),
        4 => T_('Installing files.'),
        5 => T_('Upgrading database.'),
        6 => T_('Upgrading version number.'),
        7 => T_('Turning site back on.')
    );

    echo '<ul><li><h2>'.sprintf(T_('Upgrading to %s'), $latestVersion).'</h2></li>';

    for ($i = 1; $i <= 7; $i++)
    {
        $class = '';

        if ($i < $step)
        {
            $class = 'complete';
        }
        elseif ($i == $step)
        {
            $class = 'current';
        }

        echo '<li class="'.$class.'">'.$steps[$i].'</li>';
    }

    echo '</ul>';
}

/**
 * displayStart
 * 
 * @return void
 */
function displayStart ()
{
    displayHeader();

    $currentVersion = getCurrentVersion();
    $latestVersion  = file(LATEST_VERSION_URL);
    $latestVersion  = $latestVersion[0];
    $versionNumber  = substr($latestVersion, 19);

    $_SESSION['latestVersion'] = $latestVersion;

    echo '<p><a style="color: #999;" href="../home.php">&laquo; '.T_('Back to Site').'</a></p>';

    if (versionUpToDate($currentVersion, $latestVersion))
    {
        echo '
            <h2>'.T_('Awesome, you have the latest version of Family Connections.').'</h2>
            <p>'.sprintf(T_('You do not need to upgrade at this time, you are currently running %s, which is the most up to date version. If you need to, you can re-install the current version.'), $currentVersion).'</p>';

        if (class_exists('ZipArchive') && function_exists('curl_init'))
        {
            echo '
            <a class="primary" href="upgrade.php?auto=1">'.sprintf(T_('Re-install %s'), $versionNumber).'</a>
            <div style="text-align:right; font-size:small">
                <a href="upgrade.php?manual=1">'.T_('Manual Upgrade').'</a>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <a href="http://www.familycms.com/download.php">'.T_('Download').'</a>
            </div>';
        }
        else
        {
            echo '
            <a class="primary" href="upgrade.php?manual=1">'.T_('Manual Upgrade').'</a>
            <div style="text-align:right; font-size:small">
                <a href="http://www.familycms.com/download.php">'.T_('Download').'</a>
            </div>';
        }
    }
    else
    {
        echo '
            <h2>'.T_('Bummer!, you need to Update.').'</h2>
            <p>'.sprintf(T_('Your version (%s) is out of date.  Please update to the latest version using the Automatic Upgrade, or by downloading and manually upgrading.'), $currentVersion).'</p>
            <p><b>'.T_('Remember to back up your site first.').'</b></p>';

        if (class_exists('ZipArchive') && function_exists('curl_init'))
        {
            echo '
            <a class="primary" href="upgrade.php?auto=1">'.T_('Automatic Upgrade').'</a>
            <div style="text-align:right; font-size:small">
                <a href="upgrade.php?manual=1">'.T_('Manual Upgrade').'</a>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <a href="http://www.familycms.com/download.php">'.T_('Download').'</a>
            </div>';
        }
        else
        {
            echo '
            <a class="primary" href="upgrade.php?manual=1">'.T_('Manual Upgrade').'</a>
            <div style="text-align:right; font-size:small">
                <a href="http://www.familycms.com/download.php">'.T_('Download').'</a>
            </div>';
        }
    }

    displayFooter();
}

/**
 * versionUpToDate 
 * 
 * @param string $current 
 * @param string $latest 
 *
 * @return void
 */
function versionUpToDate ($current, $latest)
{
    $current = str_pad(trim(str_replace(".", "", substr($current, 18))), 4, "0");
    $latest  = str_pad(trim(str_replace(".", "", substr($latest,  18))), 4, "0");
    
    if ($latest <= $current)
    {
        return true;
    }

    return false;
}

/**
 * displayStepOne
 * 
 * @return void
 */
function displayStepOne ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUpgrade  = new Upgrade($fcmsError, $fcmsDatabase);

    displayHeader();
    displaySteps(1);

    if ($fcmsUpgrade->disableSite())
    {
        echo '<meta http-equiv="refresh" content="0; url=upgrade.php?auto=2">';
    }
}

/**
 * displayStepTwo 
 * 
 * @return void
 */
function displayStepTwo ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUpgrade  = new Upgrade($fcmsError, $fcmsDatabase);

    displayHeader();
    displaySteps(2);

    $fcmsUpgrade->downloadLatestVersion();

    echo '<meta http-equiv="refresh" content="0; url=upgrade.php?auto=3">';
}

/**
 * displayStepThree 
 * 
 * @return void
 */
function displayStepThree ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUpgrade  = new Upgrade($fcmsError, $fcmsDatabase);

    displayHeader();
    displaySteps(3);

    // Unzip
    if ($fcmsUpgrade->unzipFile())
    {
        echo '<meta http-equiv="refresh" content="0; url=upgrade.php?auto=4">';
    }

}

/**
 * displayStepFour 
 * 
 * @return void
 */
function displayStepFour ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUpgrade  = new Upgrade($fcmsError, $fcmsDatabase);

    displayHeader();
    displaySteps(4);

    // Install
    if ($fcmsUpgrade->install())
    {
        echo '<meta http-equiv="refresh" content="0; url=upgrade.php?auto=5">';
    }
}

/**
 * displayStepFive 
 * 
 * @return void
 */
function displayStepFive ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUpgrade  = new Upgrade($fcmsError, $fcmsDatabase);

    displayHeader();
    displaySteps(5);

    if ($fcmsUpgrade->upgrade())
    {
        echo '<meta http-equiv="refresh" content="0; url=upgrade.php?auto=6">';
    }
}

/**
 * displayStepSix 
 * 
 * @return void
 */
function displayStepSix ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUpgrade  = new Upgrade($fcmsError, $fcmsDatabase);

    displayHeader();
    displaySteps(6);

    $latestVersion = $_SESSION['latestVersion'];

    // Upgrade Current Version
    if ($fcmsUpgrade->updateCurrentVersion($latestVersion))
    {
        echo '<meta http-equiv="refresh" content="0; url=upgrade.php?auto=7">';
    }
}

/**
 * displayStepSeven 
 * 
 * @return void
 */
function displayStepSeven ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUpgrade  = new Upgrade($fcmsError, $fcmsDatabase);

    displayHeader();
    displaySteps(7);

    // Turn on site
    if ($fcmsUpgrade->enableSite())
    {
        unset($_SESSION['latestVersion']);

        // Go back to orig screen, 
        // it will be updated with message saying we're up to date
        echo '<meta http-equiv="refresh" content="0; url=upgrade.php">';
    }
}

/**
 * displayManualUpgrade 
 * 
 * @return void
 */
function displayManualUpgrade ()
{
    displayHeader();

    $statusSiteOff  = '';
    $statusDatabase = '';

    if (file_exists(INC.'siteoff'))
    {
        global $upgrading;

        include INC.'siteoff';

        // If the $upgrading timestamp is less than 10 minutes old, then site is off
        if ((time() - $upgrading) < 600)
        {
            $statusSiteOff = 'complete';
        }
    }

    $currentVersion = getCurrentVersion();
    $latestVersion  = file(LATEST_VERSION_URL);
    $latestVersion  = $latestVersion[0];

    $_SESSION['latestVersion'] = $latestVersion;

    if (versionUpToDate($currentVersion, $latestVersion))
    {
        $statusDatabase = 'complete';
    }

    echo '
        <h2>'.T_('Manual Upgrade').'</h2>
        <p><a style="color: #999;" href="upgrade.php">&laquo; '.T_('Back to Automatic Upgrade').'</a></p>
        <p>'.sprintf(T_('Please follow the steps below to manually upgrade to %s.'), $latestVersion).'</p>
        <ul class="manual">
            <li class="'.$statusSiteOff.'">
                <div>'.T_('Step 1').'</div>
                <p><a href="?site=off">'.T_('Turn Off Site').'</a></p>
                <p>'.T_('Turning the site off during an upgrade keeps users from trying to login and potentially causing problems.').'</p>
            </li>
            <li>
                <div>'.T_('Step 2').'</div>
                <p>'.T_('Manual Upgrade Files').'</p>
                <p>'.T_('Since your webserver does not support the Automatic Upgrade, you must download the latest Family Connection files and upload them to your site manually.').'</p>
                <p>'.T_('You must make sure to complete this step before continuing.').'</p>
            </li>
            <li class="'.$statusDatabase.'">
                <div>'.T_('Step 3').'</div>
                <p><a href="?upgrade=database">'.T_('Upgrade Database').'</a></p>
                <p>'.T_('Make any databases changes needed for this upgrade.').'</p>
            </li>
            <li>
                <div>'.T_('Step 4').'</div>
                <p><a href="?site=on">'.T_('Finish').'</a></p>
                <p>'.T_('Turn the site back on and go to the homepage.').'</p>
            </li>
        </ul>';

    displayFooter();
}

/**
 * displayUpgradeSiteStatus 
 * 
 * @param string $status 
 * 
 * @return void
 */
function displayUpgradeSiteStatus ($status)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUpgrade  = new Upgrade($fcmsError, $fcmsDatabase);

    // Turn On
    if ($status == 'on')
    {
        if (!$fcmsUpgrade->enableSite())
        {
            displayHeader();
            echo '<p>'.T_('Could not turn on site').'</p>';
            displayFooter();

            return;
        }

        header('Location: ../home.php');

        return;
    }
    // Turn Off
    else
    {
        if (!$fcmsUpgrade->disableSite())
        {
            displayHeader();
            echo '<p>'.T_('Could not turn off site').'</p>';
            displayFooter();

            return;
        }
    }

    header('Location: upgrade.php?manual=1');
}

/**
 * displayUpgradeDatabase
 * 
 * @return void
 */
function displayUpgradeDatabase ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUpgrade  = new Upgrade($fcmsError, $fcmsDatabase);

    $latestVersion = $_SESSION['latestVersion'];

    if (!$fcmsUpgrade->upgrade())
    {
        // Jacked html, but should work
        displayHeader();
        $fcmsError->displayError();
        displayFooter();

        return;
    }

    if (!$fcmsUpgrade->updateCurrentVersion($latestVersion))
    {
        // Jacked html, but should work
        displayHeader();
        $fcmsError->displayError();
        displayFooter();

        return;
    }

    header('Location: upgrade.php?manual=1');
}
