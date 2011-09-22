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

setLanguage();

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
        else
        {
            printr(debug_backtrace());
            trigger_error("Required include ($include) not found.", E_USER_ERROR);
        }
    }
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

