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
     * 
     * @var array
     */
    var $errors = array();

    /**
     * Form Constructor
     * 
     * @return void
     */
    public function __construct()
    {
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

        echo '
        <div class="error-alert">';

        foreach ($this->errors as $msg)
        {
            echo '
            <p>'.$msg.'</p>';
        }

        echo '
        </div>';
    }

    /**
     * getErrors 
     * 
     * @return array
     */
    public function getErrors ()
    {
        return $this->errors;
    }

    /**
     * add 
     * 
     * @param string $message 
     * 
     * @return void
     */
    public function add ($message)
    {
        $this->errors[] = $message;
    }
}
