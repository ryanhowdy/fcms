<?php
/**
 * Family Connections Error.
 *
 * There are two types of errors in FCMS.
 *
 *   operation - A logic, run-time, or compliation error
 *   user      - A user or validation error.
 *
 * Operation Errors consist of:
 *
 *   message       - a user friendly message that is displayed to the user
 *   error         - the error details, usually mysql_error or php errstr
 *   line          - the line number the error occurred on
 *   file          - the file the error occurred on
 *   stack         - the stack trace
 *   php_version   - php version
 *   mysql_version - mysql version
 *   os            - operating system
 *   sql           - the sql statement (if available)
 *
 * User Errors consist of:
 *
 *   message - a user friendly message that is displayed to the user
 *   details - an optional area for more user friendly info
 *
 * PHP 5
 *
 * @category  FCMS
 *
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @copyright 2012 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @link      http://www.familycms.com/wiki/
 * @since     2.9
 */

/**
 * FCMS_Error.
 *
 * @copyright 2012 Haudenschilt LLC
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license   http://www.gnu.org/licenses/gpl-2.0.html
 */
class FCMS_Error
{
    private $type;
    private $message;
    private $details;
    private $error;
    private $line;
    private $file;
    private $stack;
    private $sql;

    public static $instance = null;

    /**
     * Constructor.
     *
     * @return void
     */
    private function __construct()
    {
        $this->type = isset($_SESSION['user_error']) ? 'user' : null;
        $this->message = isset($_SESSION['user_error']) ? $_SESSION['user_error']['message'] : null;
        $this->details = isset($_SESSION['user_error']) ? $_SESSION['user_error']['details'] : null;
        $this->error = null;
        $this->line = null;
        $this->file = null;
        $this->stack = null;
        $this->sql = null;
    }

    /**
     * getInstance.
     *
     * @return object
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new FCMS_Error();
        }

        return self::$instance;
    }

    /**
     * hasError.
     *
     * Checks whether non user error has occured.
     *
     * @return bool
     */
    public function hasError()
    {
        if (!is_null($this->message) && $this->type != 'user') {
            return true;
        }

        return false;
    }

    /**
     * hasAnyError.
     *
     * Checks whether any error has occured.
     *
     * @return bool
     */
    public function hasAnyError()
    {
        if (!is_null($this->message)) {
            return true;
        }

        return false;
    }

    /**
     * hasUserError.
     *
     * Checks whether any user error has occured.
     *
     * @return bool
     */
    public function hasUserError()
    {
        if (!is_null($this->message) && $this->type == 'user') {
            return true;
        }

        return false;
    }

    /**
     * getError.
     *
     * @return array
     */
    public function getError()
    {
        return [
            'message'       => $this->message,
            'details'       => $this->details,
            'error'         => $this->error,
            'line'          => $this->line,
            'file'          => $this->file,
            'stack'         => $this->stack,
            'php_version'   => PHP_VERSION,
            'os'            => PHP_OS,
            'sql'           => $this->sql,
        ];
    }

    /**
     * displayError.
     *
     * Prints out the error.
     *
     * @return void
     */
    public function displayError()
    {
        if (!$this->hasAnyError()) {
            return;
        }

        if ($this->type == 'user') {
            if (isset($_SESSION['user_error'])) {
                unset($_SESSION['user_error']);
            }

            $this->displayUserError();

            return;
        }

        echo '<div class="error-alert"><p><b>'.$this->message.'</b></p>';

        if (debugOn()) {
            echo '<p>'.$this->error.'</p>';
            echo '<p><b>File</b>: '.$this->file.'</p>';
            echo '<p><b>Line</b>: '.$this->line.'</p>';

            if (!is_null($this->sql)) {
                echo '<p><b>SQL</b>:<br/>'.$this->sql.'</p>';
            }

            echo '<p><b>Stack</b>:<br/><small>'.$this->stack.'</small></p>';
            echo '<p><b>PHP</b>: '.PHP_VERSION.' ('.PHP_OS.')</p>';
        }

        echo '</div>';
    }

    /**
     * displayUserError.
     *
     * Prints out the error.
     *
     * @return void
     */
    public function displayUserError()
    {
        echo '<div class="error-alert"><h2>'.$this->message.'</h2>'.$this->details.'</div>';
    }

    /**
     * setMessage.
     *
     * @param string $msg
     *
     * @return void
     */
    public function setMessage($msg)
    {
        $this->message = $msg;
    }

    /**
     * setError.
     *
     * @param mixed $error
     *
     * @return void
     */
    public function setError($error)
    {
        if (is_array($error) || is_object($error)) {
            $error = print_r($error, true);
        }

        $this->error = $error;
    }

    /**
     * add.
     *
     * Logs the error and keeps track of the error info.
     *
     * Params:
     *
     *   type          - operation | user
     *   message       - a user friendly message that is always displayed to the user
     *   details       - an optional area for more user friendly info
     *   error         - the error details, usually mysql_error or php errstr
     *   line          - the line number the error occurred on
     *   file          - the file the error occurred on
     *   sql           - the sql statement
     *
     * @param array $params
     *
     * @return void
     */
    public function add($args)
    {
        // Get params
        $type = isset($args['type']) ? $args['type'] : 'user';
        $message = isset($args['message']) ? $args['message'] : 'Unknown Error';
        $details = isset($args['details']) ? $args['details'] : null;
        $error = isset($args['error']) ? $args['error'] : null;
        $file = isset($args['file']) ? $args['file'] : null;
        $line = isset($args['line']) ? $args['line'] : null;
        $sql = isset($args['sql']) ? $args['sql'] : null;

        $stack = '';
        $logStack = '';

        // Get stack trace
        $trace = array_reverse(debug_backtrace());

        for ($i = 0; $i < count($trace); $i++) {
            $stack .= '#'.$i.' '.$trace[$i]['function'].' called at ['.$trace[$i]['file'].':'.$trace[$i]['line'].']<br/>';
            $logStack .= '    #'.$i.' '.$trace[$i]['function'].' called at ['.$trace[$i]['file'].':'.$trace[$i]['line']."]\n";
        }

        $this->setError($error);

        $this->type = $type;
        $this->message = $message;
        $this->details = $details;
        $this->line = $line;
        $this->file = $file;
        $this->stack = $stack;
        $this->sql = $sql;

        // Log Operation errors
        if ($this->type == 'operation') {
            $log = $this->message.' - ';
            $log .= $this->error."\n";
            $log .= '  FILE  - '.$this->file.' ['.$this->line."]\n";
            $log .= '  PHP   - '.PHP_VERSION.' ('.PHP_OS.")\n";

            if (!is_null($this->sql)) {
                //$log .= '  MySQL - '.mysql_get_server_info()."\n";
                $log .= '  SQL   - '.$this->sql."\n";
            }

            $log .= "  STACK\n".$logStack."\n";

            logError($log);
        }
        // Save User errors in session
        else {
            $_SESSION['user_error'] = [
                'message' => $this->message,
                'details' => $this->details,
            ];
        }
    }
}
