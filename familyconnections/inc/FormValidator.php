<?php
/**
 * FormValidator
 * 
 * PHP version 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2013 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     3.3
 */
/**
 * FormValidator
 * 
 * @category  FCMS
 * @package   Family_Connections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2013 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
class FormValidator
{
    public $valid   = array();
    public $invalid = array();
    public $missing = array();

    /**
     * __construct 
     * 
     * @return void
     */
    public function __construct ()
    {
    }

    /**
     * validate 
     * 
     * Returns true on success, array of errors otherwise.
     * 
     * @param array $input 
     * @param array $profile 
     * 
     * @return boolean/array
     */
    public function validate ($input, $profile)
    {
        $this->valid   = array();
        $this->invalid = array();
        $this->missing = array();
        $errors        = array();
        $missingLkup   = array();

        // Go through just the required fields
        if (isset($profile['required']))
        {
            foreach ($profile['required'] as $fieldName)
            {
                if (!isset($input[$fieldName]) || strlen($input[$fieldName]) == 0)
                {
                    $this->missing[]         = $fieldName;
                    $missingLkup[$fieldName] = 1;
                }
            }
        }

        // Get constraints
        $constraints = isset($profile['constraints']) ? $profile['constraints'] : $profile;

        // Loop through constraint fields in profile
        foreach ($constraints as $fieldName => $options)
        {
            $bad = false;

            // Required
            if (isset($options['required']))
            {
                if (!isset($input[$fieldName]) || strlen($input[$fieldName]) == 0)
                {
                    $this->missing[] = $fieldName;
                    $bad = true;
                    continue;
                }
            }

            // Goto next field if no data was passed
            if (!isset($input[$fieldName]))
            {
                continue;
            }

            $value = $input[$fieldName];

            // Regex / Format
            if (isset($options['format']))
            {
                if (strlen($value) > 0)
                {
                    if (preg_match($options['format'], $value) === 0)
                    {
                        $this->invalid[] = $fieldName;
                        $bad = true;
                        continue;
                    }
                }
            }

            // Integers
            if (isset($options['integer']))
            {
                if (strlen($value) > 0)
                {
                    if (!is_int($value) && !ctype_digit($value))
                    {
                        $this->invalid[] = $fieldName;
                        $bad = true;
                        continue;
                    }
                }
            }

            // Length
            if (isset($options['length']))
            {
                if (strlen($value) > $options['length'])
                {
                    $this->invalid[] = $fieldName;
                    $bad = true;
                    continue;
                }
            }

            // Acceptance
            if (isset($options['acceptance']))
            {
                if (strlen($value) == 0 || $value == 'off')
                {
                    $this->invalid[] = $fieldName;
                    $bad = true;
                    continue;
                }
            }

            // If this field hasn't been set to 'bad'
            // and it wasn't a required field, then its valid
            if (!$bad && !isset($missingLkup[$fieldName]))
            {
                $this->valid[] = $fieldName;
            }
        }

        $errors = array();

        $missingCount = count($this->missing);
        $invalidCount = count($this->invalid);

        if ($missingCount > 0 || $invalidCount > 0)
        {
            $this->updateNames($profile);
        }

        if ($missingCount > 0)
        {
            foreach ($this->missing as $field)
            {
                $message = $this->getConstraintMessage($profile, $field, 'required');

                if ($message === false)
                {
                    $errors[] = sprintf(T_('%s is missing.'), $field);
                }
                else
                {
                    $errors[] = $message;
                }
            }
        }

        if ($invalidCount > 0)
        {
            foreach ($this->invalid as $field)
            {
                $message = $this->getConstraintMessage($profile, $field, 'required');

                if ($message === false)
                {
                    $errors[] = sprintf(T_('%s is invalid.'), $field);
                }
                else
                {
                    $errors[] = $message;
                }
            }
        }

        if (count($errors) > 0)
        {
            return $errors;
        }

        return true;
    }

    /**
     * getJsValidation 
     * 
     * @param array $profile 
     * 
     * @return string
     */
    public function getJsValidation ($profile)
    {
        $js  = "\n";
        $js .= '<script type="text/javascript" src="ui/js/livevalidation.js"></script>';
        $js .= '<script type="text/javascript">';

        // Get constraints
        $constraints = isset($profile['constraints']) ? $profile['constraints'] : $profile;

        foreach ($constraints as $fieldName => $options)
        {
            $js .= "\n";
            $js .= 'var f'.$fieldName.' = new LiveValidation(\''.$fieldName.'\', { onlyOnSubmit: true });'."\n";

            // Required
            if (isset($options['required']))
            {
                $message = $this->getConstraintMessage($profile, $fieldName, 'required');

                if ($message === false)
                {
                    $js .= 'f'.$fieldName.'.add(Validate.Presence);'."\n";
                }
                // Overwrite failure message
                else
                {
                    $js .= 'f'.$fieldName.'.add(Validate.Presence, { failureMessage: "'.$message.'" });'."\n";
                }
            }

            // Regex / Format
            if (isset($options['format']))
            {
                $message = $this->getConstraintMessage($profile, $fieldName, 'format');

                if ($message === false)
                {
                    $js .= 'f'.$fieldName.'.add(Validate.Format, { pattern: '.$options['format'].' });'."\n";
                }
                // Overwrite failure message
                else
                {
                    $js .= 'f'.$fieldName.'.add(Validate.Format, { pattern: '.$options['format'].', failureMessage: "'.$message.'" });'."\n";
                }
            }

            // Integers
            if (isset($options['integer']))
            {
                $message = $this->getConstraintMessage($profile, $fieldName, 'integer');

                if ($message === false)
                {
                    $js .= 'f'.$fieldName.'.add(Validate.Numericality, { onlyInteger: true });'."\n";
                }
                // Overwrite failure message
                else
                {
                    $js .= 'f'.$fieldName.'.add(Validate.Numericality, { onlyInteger: true, failureMessage: "'.$message.'" });'."\n";
                }
                
            }

            // Length
            if (isset($options['length']))
            {
                $message = $this->getConstraintMessage($profile, $fieldName, 'length');

                if ($message === false)
                {
                    $js .= 'f'.$fieldName.'.add(Validate.Length, { is: '.$options['length'].' });'."\n";
                }
                // Overwrite failure message
                else
                {
                    $js .= 'f'.$fieldName.'.add(Validate.Length, { is: '.$options['length'].', failureMessage: "'.$message.'" });'."\n";
                }
            }

            // Acceptance
            if (isset($options['acceptance']))
            {
                $message = $this->getConstraintMessage($profile, $fieldName, 'acceptance');

                if ($message === false)
                {
                    $js .= 'f'.$fieldName.'.add(Validate.Acceptance);'."\n";
                }
                // Overwrite failure message
                else
                {
                    $js .= 'f'.$fieldName.'.add(Validate.Acceptance, { failureMessage: "'.$message.'" });'."\n";
                }
            }
        }

        $js .= '</script>';

        return $js;
    }

    /**
     * getConstraintMessage 
     * 
     * Will return the constraint message for the given constraint name,
     * or false if no message is found.
     * 
     * @param array  $profile 
     * @param string  $fieldName 
     * @param string $option 
     * 
     * @return boolean/string
     */
    private function getConstraintMessage ($profile, $fieldName, $constraintName)
    {
        // Overriding the failure message?
        if (isset($profile['messages']) && isset($profile['messages']['constraints'][$fieldName]))
        {
            $constraintMessages = $profile['messages']['constraints'][$fieldName];

            // Message could be specific to a constraint or global to all
            $message = (is_array($constraintMessages) && isset($constraintMessages[$contraintName]))
                     ? $constraintMessages[$contraintName] 
                     : $constraintMessages;

            return cleanOutput($message);
        }

        return false;
    }

    /**
     * updateName 
     * 
     * Turns the names in invalid and missing array from the name of the
     * field into the message supplied to represent that field.
     * 
     * @param string $profile 
     * 
     * @return void
     */
    private function updateNames ($profile)
    {
        // Update field names with messages if available
        if (isset($profile['messages']) && isset($profile['messages']['names']))
        {
            foreach (array('missing', 'invalid') as $type)
            {
                foreach ($this->{$type} as $key => $field)
                {
                    if (isset($profile['messages']['names'][$field]))
                    {
                        $this->{$type}[$key] = $profile['messages']['names'][$field];
                    }
                }
            }
        }
    }
}
