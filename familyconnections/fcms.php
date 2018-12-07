<?php
/**
 * fcms.
 *
 * PHP version 5
 *
 * @category  FCMS
 *
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @link      http://www.familycms.com/wiki/
 * @since     2.5
 */
require_once 'inc/config_inc.php';
require_once 'inc/thirdparty/php-gettext/gettext.inc';
require_once 'inc/utils.php';
require_once 'inc/constants.php';
require_once 'inc/Error.php';
require_once 'inc/User.php';
require_once 'inc/Database.php';

error_reporting(-1);
ini_set('log_errors', 0);
set_error_handler('fcmsErrorHandler');

fixMagicQuotes();

checkSiteStatus();

$fcmsError = FCMS_Error::getInstance();
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

spl_autoload_register('fcms_autoload');

/**
 * fcms_autoload.
 *
 * @param string $className
 *
 * @return void
 */
function fcms_autoload($className)
{
    $classPaths = [
        'Destination'                       => INC.'Upload/Destination.php',
        'PhotoGalleryDestination'           => INC.'Upload/Destination/PhotoGallery.php',
        'ProtectedPhotoGalleryDestination'  => INC.'Upload/Destination/PhotoGallery/Protected.php',
        'S3PhotoGalleryDestination'         => INC.'Upload/Destination/PhotoGallery/S3.php',
        'ProfileDestination'                => INC.'Upload/Destination/Profile.php',
        'ProtectedProfileDestination'       => INC.'Upload/Destination/Profile/Protected.php',
        'S3ProfileDestination'              => INC.'Upload/Destination/Profile/S3.php',
        'UploadFamilyTreeForm'              => INC.'Upload/FamilyTree/Form.php',
        'JavaUploadFamilyTreeForm'          => INC.'Upload/FamilyTree/Form/Java.php',
        'PluploadUploadFamilyTreeForm'      => INC.'Upload/FamilyTree/Form/Plupload.php',
        'UploadFamilyTree'                  => INC.'Upload/FamilyTree.php',
        'UploadPhotoGalleryForm'            => INC.'Upload/PhotoGallery/Form.php',
        'FacebookUploadPhotoGalleryForm'    => INC.'Upload/PhotoGallery/Form/Facebook.php',
        'InstagramUploadPhotoGalleryForm'   => INC.'Upload/PhotoGallery/Form/Instagram.php',
        'JavaUploadPhotoGalleryForm'        => INC.'Upload/PhotoGallery/Form/Java.php',
        'PicasaUploadPhotoGalleryForm'      => INC.'Upload/PhotoGallery/Form/Picasa.php',
        'PluploadUploadPhotoGalleryForm'    => INC.'Upload/PhotoGallery/Form/Plupload.php',
        'FacebookUploadPhotoGallery'        => INC.'Upload/PhotoGallery/Facebook.php',
        'InstagramUploadPhotoGallery'       => INC.'Upload/PhotoGallery/Instagram.php',
        'JavaUploadPhotoGallery'            => INC.'Upload/PhotoGallery/Java.php',
        'PicasaUploadPhotoGallery'          => INC.'Upload/PhotoGallery/Picasa.php',
        'PluploadUploadPhotoGallery'        => INC.'Upload/PhotoGallery/Plupload.php',
        'UploadProfileForm'                 => INC.'Upload/Profile/Form.php',
        'JavaUploadProfileForm'             => INC.'Upload/Profile/Form/Java.php',
        'PluploadUploadProfileForm'         => INC.'Upload/Profile/Form/Plupload.php',
        'UploadPhoto'                       => INC.'Upload/Photo.php',
        'UploadPhotoGallery'                => INC.'Upload/PhotoGallery.php',
        'UploadProfile'                     => INC.'Upload/Profile.php',
        'Google_Client'                     => THIRDPARTY.'google-api-php-client/src/Google/Client.php',
        'Google_Service_YouTube'            => THIRDPARTY.'google-api-php-client/src/Google/Service/YouTube.php',
        'S3'                                => THIRDPARTY.'s3/S3.php',
        'Instagram'                         => THIRDPARTY.'Instagram.php',
    ];

    if (isset($classPaths[$className]))
    {
        if (file_exists($classPaths[$className]))
        {
            require_once $classPaths[$className];
        }
    }
}

/**
 * load.
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
        elseif ($include == 'phpass')
        {
            include_once THIRDPARTY.'phpass/PasswordHash.php';
        }
        elseif ($include == 'google')
        {
            set_include_path(get_include_path().PATH_SEPARATOR.THIRDPARTY.'google-api-php-client/src/');

            require_once THIRDPARTY.'google-api-php-client/src/Google/autoload.php';
        }
        else
        {
            printr(debug_backtrace());
            trigger_error("Required include ($include) not found.", E_USER_ERROR);
        }
    }
}

/**
 * init.
 *
 * @param string $dir
 *
 * @return void
 */
function init($dir = '')
{
    setLanguage();

    isLoggedIn($dir);

    checkScheduler($dir);
}

/**
 * fixMagicQuotes.
 *
 * Strips slashes if magic quotes is turned on
 *
 * @return void
 */
function fixMagicQuotes()
{
    if (get_magic_quotes_gpc())
    {
        $_REQUEST = stripSlashesDeep($_REQUEST);
        $_GET = stripSlashesDeep($_GET);
        $_POST = stripSlashesDeep($_POST);
        $_COOKIE = stripSlashesDeep($_COOKIE);
    }
}

/**
 * stripSlashesDeep.
 *
 * Recursively strips slashes on arrays.  If not array, just stripslashes.
 *
 * @param mixed $value string or array to be stripped
 *
 * @return void
 */
function stripSlashesDeep($value)
{
    $value = is_array($value)
        ? array_map('stripSlashesDeep', $value)
        : stripslashes($value);

    return $value;
}

/**
 * setLanguage.
 *
 * Sets the language for the script.  Sets up php-gettext.
 *
 * @return void
 */
function setLanguage()
{
    $lang = 'en_US';

    if (isset($_SESSION['language']))
    {
        $lang = $_SESSION['language'];
    }
    else
    {
        $lang = getLanguage();
    }

    putenv('LC_ALL='.$lang);
    T_setlocale(LC_MESSAGES, $lang);
    T_bindtextdomain('messages', ROOT.'./language');
    T_bind_textdomain_codeset('messages', 'UTF-8');
    T_textdomain('messages');
}

/**
 * getLanguage.
 *
 * Gets the users default language.  Defaults to en_us.
 *
 * @return string
 */
function getLanguage()
{
    global $fcmsDatabase, $fcmsError;

    if (isset($_SESSION['fcms_id']))
    {
        $id = (int) $_SESSION['fcms_id'];

        $sql = 'SELECT `language` 
                FROM `fcms_user_settings` 
                WHERE `id` = ?';

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
 * fcmsErrorHandler.
 *
 * @param string $errno   PHP error number
 * @param string $errstr  description of error
 * @param string $errfile file path
 * @param string $errline line number
 *
 * @return bool
 */
function fcmsErrorHandler($errno, $errstr, $errfile, $errline)
{
    $trace = array_reverse(debug_backtrace());

    $stack = '';
    $logStack = '';

    for ($i = 0; $i < count($trace); $i++)
    {
        $function = '???';
        $file = '???';
        $line = '???';

        if (isset($trace[$i]))
        {
            $function = isset($trace[$i]['function']) ? $trace[$i]['function'] : $function;
            $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : $file;
            $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : $line;
        }

        $stack .= '#'.$i.' '.$function.' called at ['.$file.':'.$line.']<br/>';
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

    $log = $errstr."\n";
    $log .= '  FILE  - '.$errfile.' ['.$errline."]\n";
    $log .= '  PHP   - '.PHP_VERSION.' ('.PHP_OS.")\n";
    $log .= "  STACK\n".$logStack."\n";

    logError($log);

    // Don't execute PHP internal error handler
    return true;
}

/**
 * checkScheduler.
 *
 * Checks the FCMS Scheduler to see if any scheduled jobs need run.
 *
 * @param string $subdir
 *
 * @return void
 */
function checkScheduler($subdir = '')
{
    global $fcmsDatabase, $fcmsError;

    $sql = 'SELECT `id`, `type`, `repeat`, `lastrun`
            FROM `fcms_schedule`
            WHERE `status` = 1';

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
        $url = str_replace($subdir, '', $url);
    }

    foreach ($rows as $row)
    {
        $runJob = false;
        $hourAgo = strtotime('-1 hours');
        $dayAgo = strtotime('-1 days');
        $lastrun = strtotime($row['lastrun']);
        $type = cleanOutput($row['type']);

        // Job has never been run
        if (is_null($row['lastrun']))
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
            postAsync($url.'cron.php', ['job_type' => $type]);

            return;
        }
    }
}

/**
 * isLoggedIn.
 *
 * Checks whether user is logged in or not.  If user is logged in
 * it just returns, if not, it redirects to login screen.
 * returns  boolean
 */
function isLoggedIn()
{
    global $fcmsUser;

    $fcmsError = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    // User has a session
    if (isset($_SESSION['fcms_id']))
    {
        $id = (int) $_SESSION['fcms_id'];
        $token = $_SESSION['fcms_token'];
    }
    // User has a cookie
    elseif (isset($_COOKIE['fcms_cookie_id']))
    {
        $_SESSION['fcms_id'] = (int) $_COOKIE['fcms_cookie_id'];
        $_SESSION['fcms_token'] = $_COOKIE['fcms_cookie_token'];

        $id = $_SESSION['fcms_id'];
        $token = $_SESSION['fcms_token'];
    }
    // User has nothing
    else
    {
        $url = basename($_SERVER['REQUEST_URI']);
        header('Location: '.URL_PREFIX.'index.php?err=login&url='.URL_PREFIX.$url);
        exit();
    }

    // Make sure id is a number
    if (!is_numeric($id))
    {
        $url = basename($_SERVER['REQUEST_URI']);
        header('Location: '.URL_PREFIX.'index.php?err=login&url='.URL_PREFIX.$url);
        exit();
    }

    // Verify the token is good
    if (isValidLoginToken($id, $token))
    {
        $sql = "SELECT `access` AS 'val'
                FROM `fcms_users`
                WHERE `id` = ?
                UNION ALL
                SELECT `value` AS 'val'
                FROM `fcms_config`
                WHERE `name` = ?";

        $rows = $fcmsDatabase->getRows($sql, [$id, 'site_off']);
        if ($rows === false)
        {
            $error->displayError();

            return;
        }

        $site_off = $rows[0]['val'];
        $access = $rows[1]['val'];

        // Site is off and your not an admin
        if ($site_off == 1 && $access > 1)
        {
            header('Location: '.URL_PREFIX.'index.php?err=off');
            exit();
        }
        // Good login, you may proceed
        else
        {
            // Load logged in user
            $fcmsUser = new User($fcmsError, $fcmsDatabase);

            return;
        }
    }
    // The user's session/cookie credentials are bad
    else
    {
        unset($_SESSION['fcms_id']);
        unset($_SESSION['fcms_token']);

        if (isset($_COOKIE['fcms_cookie_id']))
        {
            setcookie('fcms_cookie_id', '', time() - 3600, '/');
            setcookie('fcms_cookie_token', '', time() - 3600, '/');
        }
        header('Location: '.URL_PREFIX.'index.php?err=login');
        exit();
    }
}

/**
 * checkSiteStatus.
 *
 * @return void
 */
function checkSiteStatus()
{
    // Site is on
    if (!file_exists(INC.'siteoff'))
    {
        return;
    }

    global $upgrading;

    include INC.'siteoff';

    // If the $upgrading timestamp is older than 10 minutes, don't die.
    if ((time() - $upgrading) >= 600)
    {
        return;
    }

    echo '
<html>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.T_pgettext('Language Code for this translation', 'lang').'" lang="'.T_pgettext('Language Code for this translation', 'lang').'">
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
