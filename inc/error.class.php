<?php
/**
 * Family Connections Error
 * 
 * PHP 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2012 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.9
 */

/**
 * FCMS_Error
 * 
 * @package   Family Connections
 * @copyright 2012 Haudenschilt LLC
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license   http://www.gnu.org/licenses/gpl-2.0.html
 */
class FCMS_Error
{
    /**
     * Stores the list of errors.
     */
    private $errors = array();

    /**
     * Stores the list of errors, with additional debug info.
     */
    private $debug = array();

    /**
     * Is debug on.
     */
    private $debugOn = false;

    /**
     * Form Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        $this->debugOn = debugOn();
    }

    /**
     * hasErrors 
     * 
     * Checks whether any errors have occured.
     * 
     * @return boolean
     */
    public function hasErrors ()
    {
        if (count($this->errors) > 0)
        {
            return true;
        }

        return false;
    }

    /**
     * getErrors 
     * 
     * @return array
     */
    public function getErrors ()
    {
        if ($this->debugOn)
        {
            return array(
                'errors' => $this->errors,
                'debug'  => $this->debug
            );
        }

        return $this->errors;
    }

    /**
     * displayErrors 
     * 
     * Prints out all errors.
     * 
     * @return void
     */
    public function displayErrors ()
    {
        if (!$this->hasErrors())
        {
            return;
        }

        echo '<div class="error-alert">';

        for ($i=0; $i < count($this->errors); $i++)
        {
            echo '<p><b>'.$this->errors[$i].'</b>';

            if ($this->debugOn)
            {
                echo '<br/>'.$this->debug[$i];
            }

            echo '</p>';
        }

        echo '</div>';
    }

    /**
     * add 
     * 
     * Logs the error and keeps track of the error info.
     * 
     * Params:
     *
     *   message   - is a nice message to display to the user.
     *
     *   debugInfo - any error info that would be useful for debugging.
     *               could be an array or string
     *
     * 
     * @param string $message 
     * @param mixed  $debugInfo
     * 
     * @return void
     */
    public function add ($message, $debugInfo = null)
    {
        $backtrace = debug_backtrace(false);
        $last      = $backtrace[1];
        $file      = $last['file'];
        $line      = $last['line'];
        $debugInfo = "$file [$line]";
        $log       = "$file [$line]";

        // Save error
        $this->errors[] = $message;

        // Get debug/log info
        if (is_null($debugInfo))
        {
            $debugInfo .= " - $message";
            $log       .= $message;
        }
        else
        {
            if (is_array($debugInfo) || is_object($debugInfo))
            {
                $debugInfo .= ' - '.print_r($debugInfo, true);
                $log       .= $message."\n".$debugInfo;
            }
            else
            {
                $log = $message.' - '.$debugInfo;
            }
        }

        // Save debug info
        $this->debug[] = $debugInfo;

        // Log error
        logError("$log");
    }
}
