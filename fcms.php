<?php
/**
 * fcms 
 * 
 * PHP versions 4 and 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.5
 */

require_once 'inc/config_inc.php';
require_once 'inc/gettext.inc';
require_once 'inc/utils.php';
require_once 'inc/constants.php';

set_error_handler("fcmsErrorHandler");

fixMagicQuotes();

connectDatabase();

checkSiteStatus();

/**
 * load 
 * 
 * Will include the necessary classes/inc files.
 * 
 * @return void
 */
function load()
{
    $includes = func_get_args();

    foreach ($includes as $include)
    {
        if (file_exists(INC.$include.'_class.php'))
        {
            include_once INC.$include.'_class.php';
        }
        elseif (file_exists(INC.$include.'.php'))
        {
            include_once INC.$include.'.php';
        }
        elseif (file_exists(INC.$include.'_inc.php'))
        {
            include_once INC.$include.'_inc.php';
        }
        elseif ($include == 'foursquare')
        {
            include_once INC.'foursquare/EpiCurl.php';
            include_once INC.'foursquare/EpiFoursquare.php';
        }
        elseif ($include == 'facebook')
        {
            include_once INC.'facebook/src/facebook.php';
        }
        elseif ($include == 'vimeo')
        {
            include_once INC.'vimeo/vimeo.php';
        }
        elseif ($include == 'youtube')
        {
            set_include_path(INC);

            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass('Zend_Gdata_YouTube');
            Zend_Loader::loadClass('Zend_Gdata_AuthSub');
            Zend_Loader::loadClass('Zend_Gdata_App_Exception');
        }
        else
        {
            printr(debug_backtrace());
            trigger_error("Required include ($include) not found.", E_USER_ERROR);
        }
    }
}

/**
 * init 
 * 
 * @param string $dir 
 * 
 * @return void
 */
function init ($dir = '')
{
    setLanguage();

    isLoggedIn($dir);

    checkScheduler($dir);
}

/**
 * fixMagicQuotes 
 *
 * Strips slashes if magic quotes is turned on
 * 
 * @return void
 */
function fixMagicQuotes ()
{
    if (get_magic_quotes_gpc())
    {
        $_REQUEST = stripSlashesDeep($_REQUEST);
        $_GET     = stripSlashesDeep($_GET);
        $_POST    = stripSlashesDeep($_POST);
        $_COOKIE  = stripSlashesDeep($_COOKIE);
    }
}

/**
 * stripSlashesDeep 
 *
 * Recursively strips slashes on arrays.  If not array, just stripslashes.
 * 
 * @param mixed $value string or array to be stripped
 * 
 * @return  void
 */
function stripSlashesDeep ($value)
{
    $value = is_array($value) 
        ? array_map('stripSlashesDeep', $value)
        : stripslashes($value);
    return $value;
}

/**
 * setLanguage 
 * 
 * Sets the language for the script.  Sets up php-gettext.
 * 
 * @return void
 */
function setLanguage ()
{
    if (isset($_SESSION['language']))
    {
        T_setlocale(LC_MESSAGES, $_SESSION['language']);
    }
    else
    {
        $lang = getLanguage();
        T_setlocale(LC_MESSAGES, $lang);
    }
    T_bindtextdomain('messages', './language');
    T_bind_textdomain_codeset('messages', 'UTF-8');
    T_textdomain('messages');
}

/**
 * connectDatabase 
 * 
 * Connects to the mysql db.
 * 
 * @return void
 */
function connectDatabase ()
{
    global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

    $connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
    mysql_select_db($cfg_mysql_db);

    if (!mysql_query("SET NAMES 'utf8'"))
    {
        displaySQLError('UTF8 Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
    }
}

/**
 * getLanguage 
 * 
 * Gets the users default language.  Defaults to en_us.
 * 
 * @return  string
 */
function getLanguage ()
{
    if (isset($_SESSION['login_id']))
    {
        $user = cleanInput($_SESSION['login_id'], 'int');

        $sql = "SELECT `language` 
                FROM `fcms_user_settings` 
                WHERE `id` = '$user'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Language Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        $row = mysql_fetch_array($result);

        if (mysql_num_rows($result) > 0)
        {
            return $row['language'];
        }
    }

    return 'en_US';
}

/**
 * fcmsErrorHandler 
 * 
 * @param string $errno   PHP error number
 * @param string $errstr  description of error
 * @param string $errfile file path
 * @param string $errline line number
 *
 * @return boolean
 */
function fcmsErrorHandler($errno, $errstr, $errfile, $errline)
{
    $pos = strpos($errstr, "It is not safe to rely on the system's timezone settings");
    if ($pos !== false)
    {
        return true;
    }

    switch ($errno)
    {
        case E_USER_ERROR:
            echo "<div class=\"error-alert\"><big><b>Fatal Error</b></big><br/><small><b>$errstr</b></small><br/>";
            echo  "<small><b>Where:</b> on line $errline in $errfile</small><br/>";
            echo  "<small><b>Environment:</b> PHP ".PHP_VERSION." (".PHP_OS.")</small></div>";
            exit(1);
            break;

        case E_USER_WARNING:
            echo "<div class=\"error-alert\"><big><b>Warning</b></big><br/><small><b>$errstr</b></small><br/>";
            echo  "<small><b>Where:</b> on line $errline in $errfile</small><br/>";
            echo  "<small><b>Environment:</b> PHP ".PHP_VERSION." (".PHP_OS.")</small></div>";
            break;

        case E_USER_NOTICE:
            echo "<div class=\"error-alert\"><big><b>Notice</b></big><br/><small><b>$errstr</b></small><br/>";
            echo "<small><b>Where:</b> on line $errline in $errfile</small><br/>";
            echo  "<small><b>Environment:</b> PHP ".PHP_VERSION." (".PHP_OS.")</small></div>";
            break;

        default:
            echo "<div class=\"error-alert\"><big><b>Error</b></big><br/><small><b>$errstr</b></small><br/>";
            echo "<small><b>Where:</b> on line $errline in $errfile</small><br/>";
            echo "<small><b>Environment:</b> PHP ".PHP_VERSION." (".PHP_OS.")</small></div>";
            break;
    }

    // Don't execute PHP internal error handler
    return true;
}

/**
 * checkScheduler 
 * 
 * Checks the FCMS Scheduler to see if any scheduled jobs need run.
 * 
 * @param string $subdir 
 * 
 * @return void
 */
function checkScheduler ($subdir = '')
{
    $sql = "SELECT `id`, `type`, `repeat`, `lastrun`
            FROM `fcms_schedule`
            WHERE `status` = 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Schedule Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return;
    }

    $url = getDomainAndDir();

    // Remove subdirectory from end (admi/ or gallery/)
    if (!empty($subdir))
    {
        $url = str_replace($subdir, "", $url);
    }

    while ($row = mysql_fetch_assoc($result))
    {
        $runJob  = false;
        $hourAgo = strtotime('-1 hours');
        $dayAgo  = strtotime('-1 days');
        $lastrun = strtotime($row['lastrun']);
        $type    = cleanOutput($row['type']);

        // Job has never been run
        if ($row['lastrun'] == '0000-00-00 00:00:00')
        {
            $runJob = true;
        }
        // Hourly jobs
        elseif ($row['repeat'] == 'hourly')
        {
            if ($lastrun < $hourAgo)
            {
                $runJob = true;
            }
        }
        // Daily jobs
        elseif ($row['repeat'] == 'daily')
        {
            if ($lastrun < $dayAgo)
            {
                $runJob = true;
            }
        }

        // Attempt to run scheduled job
        if ($runJob)
        {
            postAsync($url.'cron.php', array('job_type' => $type));
            return;
        }
    }
}

/**
 * isLoggedIn
 * 
 * Checks whether user is logged in or not.  If user is logged in 
 * it just returns, if not, it redirects to login screen.
 * returns  boolean
 */
function isLoggedIn ($directory = '')
{
    $up = '';

    // We have are looking for a sub directory, then index is up a level
    if ($directory != '')
    {
        $up = '../';
    }

    // User has a session
    if (isset($_SESSION['login_id']))
    {
        $id = $_SESSION['login_id'];
        $user = $_SESSION['login_uname'];
        $pass = $_SESSION['login_pw'];
    }
    // User has a cookie
    elseif (isset($_COOKIE['fcms_login_id']))
    {
        $_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
        $_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
        $_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
        $id = $_SESSION['login_id'];
        $user = $_SESSION['login_uname'];
        $pass = $_SESSION['login_pw'];
    }
    // User has nothing
    else
    {
        $url = basename($_SERVER["REQUEST_URI"]);
        header("Location: {$up}index.php?err=login&url=$directory$url");
        exit();
    }

    // Make sure id is a digit
    if (!ctype_digit($id))
    {
        $url = basename($_SERVER["REQUEST_URI"]);
        header("Location: {$up}index.php?err=login&url=$directory$url");
        exit();
    }

    // User's session/cookie credentials are good
    if (checkLoginInfo($id, $user, $pass))
    {
        $sql = "SELECT `access`
                FROM `fcms_users`
                WHERE `id` = '".escape_string($id)."'
                LIMIT 1
                UNION
                SELECT `value`
                FROM `fcms_config`
                WHERE `name` = 'site_off'
                LIMIT 1";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Site Status Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            die();
        }

        $r1 = mysql_fetch_assoc($result);
        $r2 = mysql_fetch_assoc($result);

        // Site is off and your not an admin
        if ($r2['site_off'] == 1 && $r1['access'] > 1)
        {
            header("Location: {$up}index.php?err=off");
            exit();
        }
        // Good login, you may proceed
        else
        {
            return;
        }
    }
    // The user's session/cookie credentials are bad
    else
    {
        unset($_SESSION['login_id']);
        unset($_SESSION['login_uname']);
        unset($_SESSION['login_pw']);

        if (isset($_COOKIE['fcms_login_id']))
        {
            setcookie('fcms_login_id', '', time() - 3600, '/');
            setcookie('fcms_login_uname', '', time() - 3600, '/');
            setcookie('fcms_login_pw', '', time() - 3600, '/');
        }
        header("Location: {$up}index.php?err=login");
        exit();
    }
}

/**
 * checkSiteStatus 
 * 
 * @return void
 */
function checkSiteStatus ()
{
    // Site is on
    if (!file_exists(INC.'siteoff'))
    {
        return;
    }

    global $upgrading;

    include INC.'siteoff';

    // If the $upgrading timestamp is older than 10 minutes, don't die.
    if ((time() - $upgrading) >= 600 )
    {
        return;
    }

    echo '
<html>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.T_('lang').'" lang="'.T_('lang').'">
<head>
<title>'.T_('Site is currently turned off...').'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="author" content="Ryan Haudenschilt" />
</head>
<body>
<h1>'.T_('Hold On a Second!').'</h1>
<p>'.T_('The site has been closed by an administrator.').'</p>
<p>'.T_('Please come back later.').'</p>
</body>
</html>';

    die();
}
