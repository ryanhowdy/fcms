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

define('URL_PREFIX', '../');

require URL_PREFIX.'fcms.php';

load('admin', 'upgrade');

// Check that the user is logged in
isLoggedIn('admin/');

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$admin         = new Admin($currentUserId);

define('LATEST_VERSION_URL', 'http://www.familycms.com/latest/version.php');
define('LATEST_FILE_URL',    'http://www.familycms.com/latest/latest.zip');

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Upgrade'),
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
 * @return void
 */
function control ()
{
    checkPermission();

    if (isset($_POST['automatic-upgrade']))
    {
        displayAutomaticUpgradeSubmit();
    }
    elseif (isset($_POST['upgrade']))
    {
        displayUpgradeSubmit();
    }
    else
    {
        displayUpgradeForm();
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

    include_once getTheme($currentUserId, $TMPL['path']).'header.php';

    echo '
        <div class="centercontent">';
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
        </div><!-- .centercontent -->';

    include_once getTheme($currentUserId, $TMPL['path']).'footer.php';

}

/**
 * checkPermission 
 * 
 * @return void
 */
function checkPermission ()
{
    global $currentUserId;

    if (checkAccess($currentUserId) > 1)
    {
        displayHeader();

        echo '
                <p class="error-alert">
                    <b>'.T_('You do not have access to view this page.').'</b><br/>
                    '.T_('This page requires an access level 1 (Admin).').' 
                    <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
                </p>';

        displayFooter();
        die();
    }
}

/**
 * displayUpgradeForm 
 * 
 * @return void
 */
function displayUpgradeForm ()
{
    displayHeader();

    $currentVersion = getCurrentVersion();
    $latestVersion  = file(LATEST_VERSION_URL);
    $latestVersion  = $latestVersion[0];

    echo '
        <div style="margin: 10px 50px">';

    if (versionUpToDate($currentVersion, $latestVersion))
    {
        echo '
            <h2>'.T_('Awesome, you have the latest version of Family Connections.').'</h2>
            <p>'.sprintf(T_('You do not need to upgrade at this time, you are currently running %s, which is the most up to date version. If you need to, you can re-install the current version.'), $currentVersion).'</p>';

        if (class_exists('ZipArchive'))
        {
            echo '
            <form method="post" action="upgrade.php">
                <div>
                    <input class="sub1" type="submit" name="automatic-upgrade" value="'.T_('Re-install').'"/>
                </div>
            </form>';
        }
        else
        {
            echo '
            <form method="post" action="upgrade.php">
                <div>
                    <input class="sub1" type="submit" name="upgrade" value="'.T_('Upgrade Database').'"/>
                </div>
            </form>';
        }
    }
    else
    {
        echo '
            <h2>'.T_('Bummer!, you need to Update.').'</h2>
            <p>'.sprintf(T_('Your version (%s) is out of date.  Please update to the latest version using the Automatic Upgrade, or by downloading and manually upgrading.'), $currentVersion).'</p>
            <p><b>'.T_('Remember to back up your site first.').'</b></p>';

        if (class_exists('ZipArchive'))
        {
            echo '
            <form method="post" action="upgrade.php">
                <div>
                    <input class="sub1" type="submit" name="automatic-upgrade" value="'.T_('Automatic Upgrade').'"/>
                    &nbsp;&nbsp;
                    <a href="http://www.familycms.com/download.php">'.T_('Download').'</a>
                </div>
            </form>';
        }
        else
        {
            echo '
            <form method="post" action="upgrade.php">
                <div>
                    <input class="sub1" type="submit" name="upgrade" value="'.T_('Upgrade Database').'"/>
                    &nbsp;&nbsp;
                    <a href="http://www.familycms.com/download.php">'.T_('Download').'</a>
                </div>
            </form>';
        }
    }


    echo '
        </div>';

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
 * displayAutomaticUpgradeSubmit 
 * 
 * @return void
 */
function displayAutomaticUpgradeSubmit ()
{
    displayHeader();

    $latestVersion  = file(LATEST_VERSION_URL);
    $latestVersion  = $latestVersion[0];

    echo '
            <h2>'.sprintf(T_('Upgrading to %s'), $latestVersion).'</h2>
            <ol>';

    // Turn off site
    echo '<li>'.T_('Turning off site.').'</li>';
    if (!disableSite())
    {
        displayFooter();
        return;
    }

    // Download
    echo '<li>'.T_('Downloading latest version.').'</li>';
    downloadLatestVersion();

    // Unzip
    echo '<li>'.T_('Unpacking files.').'</li>';
    if (!unzipFile())
    {
        displayFooter();
        return;
    }

    // Install
    echo '<li>'.T_('Installing latest version.').'</li>';
    if (!install())
    {
        displayFooter();
        return;
    }

    // Upgrade DB
    echo '<li>'.T_('Upgrading database.').'</li>';
    if (!upgrade())
    {
        displayFooter();
        return;
    }
    if (!updateCurrentVersion($latestVersion))
    {
        displayFooter();
        return;
    }

    // Turn on site
    echo '<li>'.T_('Turning site back on.').'</li>';
    if (!enableSite())
    {
        displayFooter();
        return;
    }

    echo '<li><b>'.T_('Upgrade Complete').'</b></li>';
    echo '</ol>';

    displayFooter();
}

/**
 * displayUpgradeSubmit 
 * 
 * @return void
 */
function displayUpgradeSubmit ()
{
    displayHeader();

    $latestVersion  = file(LATEST_VERSION_URL);
    $latestVersion  = $latestVersion[0];

    echo '
            <h2>'.sprintf(T_('Upgrading to %s'), $latestVersion).'</h2>
            <ol>';

    // Turn off site
    echo '<li>'.T_('Turning off site.').'</li>';
    if (!disableSite())
    {
        displayFooter();
        return;
    }

    // Upgrade DB
    echo '<li>'.T_('Upgrading database.').'</li>';
    if (!upgrade())
    {
        displayFooter();
        return;
    }
    if (!updateCurrentVersion($latestVersion))
    {
        displayFooter();
        return;
    }

    // Turn on site
    echo '<li>'.T_('Turning site back on.').'</li>';
    if (!enableSite())
    {
        displayFooter();
        return;
    }

    echo '<li><b>'.T_('Upgrade Complete').'</b></li>';
    echo '</ol>';

    displayFooter();
}

/**
 * disableSite 
 * 
 * @return void
 */
function disableSite ()
{
    $sql = "UPDATE `fcms_config`
            SET `site_off` = '1'";
    if (!mysql_query($sql))
    {
        displaySQLError('Site Off Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }

    return true;
}

/**
 * enableSite 
 * 
 * @return boolean
 */
function enableSite ()
{
    $sql = "UPDATE `fcms_config`
            SET `site_off` = '0'";
    if (!mysql_query($sql))
    {
        displaySQLError('Site On Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }

    return true;
}

/**
 * updateCurrentVersion 
 * 
 * @param string  $version 
 * 
 * @return boolean
 */
function updateCurrentVersion ($version)
{
    $sql = "UPDATE `fcms_config` 
            SET `current_version` = '$version'";
    if (!mysql_query($sql))
    {
        displaySQLError('Version Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }

    return true;
}

/**
 * downloadLatestVersion 
 * 
 * @return void
 */
function downloadLatestVersion ()
{
    // Have we downloaded the latest file already?
    if (file_exists(INC.'latest.zip'))
    {
        $modified = filemtime(INC.'latest.zip');

        // Skip the download if the file has been downloaded already today
        if (date('Ymd') == date('Ymd', $modified))
        {
            return;
        }
    }

    $ch = curl_init(LATEST_FILE_URL);
    $fh = fopen(INC.'latest.zip', 'w');
    curl_setopt($ch, CURLOPT_FILE, $fh);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * unzipFile 
 * 
 * @return boolean
 */
function unzipFile ()
{
    $zipFile = INC.'latest.zip';

    $za = new ZipArchive(); 

    if (!$za->open($zipFile))
    {
        echo '<div class="error-alert">'.T_('Could not open zip file.').'</div>';
        return false;
    }

    // Get the container dir (FCMS X.X)
    $firstIndex = $za->statIndex(0);
    $nameIndex  = $firstIndex['name'];

    $pos = strpos($nameIndex, '/');
    if ($pos === false)
    {
        echo '<div class="error-alert">'.T_('Corrupt zip file.').'</div>';
        return false;
    }

    $container = substr($nameIndex, 0, $pos);

    for ($i = 0; $i < $za->numFiles; $i++)
    {
        $filename = $za->getNameIndex($i);
        $fileinfo = pathinfo($filename);

        // Remove the FCMS X.X from the destination path
        $file = substr($filename, strlen($container));

        // handle the directories in the zip file
        if (substr($filename, -1) == '/')
        {
            // Skip the container FCMS X.X directory
            if ($filename == $container)
            {
                continue;
            }

            // See if this dir exists on our server, if not, create it
            if (!is_dir(ROOT."$file"))
            {
                mkdir(ROOT."$file");
            }

            continue;
        }

        copy("zip://$zipFile#$filename", ROOT."$file");
    }
    $za->close();

    return true;
}
