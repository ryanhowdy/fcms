<?php
/**
 * fcms 
 * 
 * PHP version 5
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
require_once 'inc/thirdparty/gettext.inc';
require_once 'inc/utils.php';
require_once 'inc/constants.php';
require_once 'inc/Error.php';
require_once 'inc/User.php';
require_once 'inc/Database.php';

error_reporting(-1);
ini_set('log_errors', 0);
set_error_handler("fcmsErrorHandler");

fixMagicQuotes();

checkSiteStatus();

$fcmsError    = FCMS_Error::getInstance();
$fcmsDatabase = Database::getInstance($fcmsError);
if ($fcmsError->hasError())
{
    $fcmsError->displayError();
    return;
}

$fcmsUser = new User($fcmsError, $fcmsDatabase);
if ($fcmsError->hasError())
{
    $fcmsError->displayError();
    return;
}

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
            include_once THIRDPARTY.'foursquare/EpiCurl.php';
            include_once THIRDPARTY.'foursquare/EpiFoursquare.php';
        }
        elseif ($include == 'facebook')
        {
            include_once THIRDPARTY.'facebook/src/facebook.php';
        }
        elseif ($include == 'instagram')
        {
            include_once THIRDPARTY.'Instagram.php';
        }
        elseif ($include == 'vimeo')
        {
            include_once THIRDPARTY.'vimeo/vimeo.php';
        }
        elseif ($include == 'youtube')
        {
            set_include_path(THIRDPARTY);

            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass('Zend_Gdata_YouTube');
            Zend_Loader::loadClass('Zend_Gdata_AuthSub');
            Zend_Loader::loadClass('Zend_Gdata_App_Exception');
        }
        elseif ($include == 'picasa')
        {
            set_include_path(THIRDPARTY);

            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass('Zend_Gdata_Photos');
            Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
            Zend_Loader::loadClass('Zend_Gdata_AuthSub');
            Zend_Loader::loadClass('Zend_Gdata_Photos_AlbumQuery');
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
 * getLanguage 
 * 
 * Gets the users default language.  Defaults to en_us.
 * 
 * @return  string
 */
function getLanguage ()
{
    global $fcmsDatabase, $fcmsError;

    if (isset($_SESSION['login_id']))
    {
        $id = (int)$_SESSION['login_id'];

        $sql = "SELECT `language` 
                FROM `fcms_user_settings` 
                WHERE `id` = ?";

        $row = $fcmsDatabase->getRow($sql, $id);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($row) > 0)
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
    $trace = array_reverse(debug_backtrace());

    $stack    = '';
    $logStack = '';

    for ($i = 0; $i < count($trace); $i++)
    {
        $function = '???';
        $file     = '???';
        $line     = '???';

        if (isset($trace[$i]))
        {
            $function = isset($trace[$i]['function']) ? $trace[$i]['function'] : $function;
            $file     = isset($trace[$i]['file'])     ? $trace[$i]['file']     : $file;
            $line     = isset($trace[$i]['line'])     ? $trace[$i]['line']     : $line;
        }

        $stack    .= '#'.$i.' '.$function.' called at ['.$file.':'.$line.']<br/>';
        $logStack .= '    #'.$i.' '.$function.' called at ['.$file.':'.$line."]\n";
    }

    switch ($errno)
    {
        case E_USER_ERROR:
            echo '
            <div class="error-alert">
                <p><b>Fatal Error</b></p>
                <p><b>File</b>: '.$errfile.'</p>
                <p><b>Line</b>: '.$errline.'</p>
                <p><b>Stack</b>:<br/><small>'.$stack.'</small></p>
                <p><b>PHP</b>: '.PHP_VERSION.' ('.PHP_OS.')</p>
            </div>';

            exit(1);
            break;

        case E_USER_WARNING:
            $errno = 'PHP Warning';
            break;

        case E_USER_NOTICE:
            $errno = 'PHP Notice';
            break;

        default:
            $errno = 'PHP Error';
            break;
    }

    echo '<div class="error-alert"><p><b>'.$errno.'</b></p><p><b>'.$errstr.'</b></p>';

    if (debugOn())
    {
        echo '<p><b>File</b>: '.$errfile.'</p>';
        echo '<p><b>Line</b>: '.$errline.'</p>';
        echo '<p><b>Stack</b>:<br/><small>'.$stack.'</small></p>';
        echo '<p><b>PHP</b>: '.PHP_VERSION.' ('.PHP_OS.')</p>';
    }

    echo '</div>';

    $log  = $errstr."\n";
    $log .= '  FILE  - '.$errfile.' ['.$errline."]\n";
    $log .= '  PHP   - '.PHP_VERSION.' ('.PHP_OS.")\n";
    $log .= "  STACK\n".$logStack."\n";

    logError($log);

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
    global $fcmsDatabase, $fcmsError;

    $sql = "SELECT `id`, `type`, `repeat`, `lastrun`
            FROM `fcms_schedule`
            WHERE `status` = 1";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        $fcmsError->displayError();
        return;
    }

    if (count($rows) <= 0)
    {
        return;
    }

    $url = getDomainAndDir();

    // Remove subdirectory from end (admin/ or gallery/)
    if (!empty($subdir))
    {
        $url = str_replace($subdir, "", $url);
    }

    foreach ($rows as $row)
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
function isLoggedIn ()
{
    global $fcmsDatabase, $fcmsError;

    // User has a session
    if (isset($_SESSION['login_id']))
    {
        $id   = (int)$_SESSION['login_id'];
        $user = $_SESSION['login_uname'];
        $pass = $_SESSION['login_pw'];
    }
    // User has a cookie
    elseif (isset($_COOKIE['fcms_login_id']))
    {
        $_SESSION['login_id']    = (int)$_COOKIE['fcms_login_id'];
        $_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
        $_SESSION['login_pw']    = $_COOKIE['fcms_login_pw'];

        $id   = $_SESSION['login_id'];
        $user = $_SESSION['login_uname'];
        $pass = $_SESSION['login_pw'];
    }
    // User has nothing
    else
    {
        $url = basename($_SERVER["REQUEST_URI"]);
        header('Location: '.URL_PREFIX.'index.php?err=login&url='.URL_PREFIX.$url);
        exit();
    }

    // Make sure id is a number
    if (!is_numeric($id))
    {
        $url = basename($_SERVER["REQUEST_URI"]);
        header('Location: '.URL_PREFIX.'index.php?err=login&url='.URL_PREFIX.$url);
        exit();
    }

    // User's session/cookie credentials are good
    if (checkLoginInfo($id, $user, $pass))
    {
        $sql = "SELECT `access` AS 'val'
                FROM `fcms_users`
                WHERE `id` = ?
                UNION
                SELECT `value` AS 'val'
                FROM `fcms_config`
                WHERE `name` = ?";

        $rows = $fcmsDatabase->getRows($sql, array($id, 'site_off'));
        if ($rows === false)
        {
            $error->displayError();
            return;
        }

        $site_off = $rows[0]['val'];
        $access   = $rows[1]['val'];

        // Site is off and your not an admin
        if ($site_off == 1 && $access > 1)
        {
            header('Location: '.URL_PREFIX.'index.php?err=off');
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
        header('Location: '.URL_PREFIX.'index.php?err=login');
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
