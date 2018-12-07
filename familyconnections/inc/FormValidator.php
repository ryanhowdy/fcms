<?php
/**
 * FormValidator.
 *
 * PHP version 5
 *
 * @category  FCMS
 *
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @copyright 2013 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @link      http://www.familycms.com/wiki/
 * @since     3.3
 */
require_once 'validation.php';

/**
 * FormValidator.
 *
 * @category  FCMS
 *
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @copyright 2013 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @link      http://www.familycms.com/wiki/
 */
class FormValidator
{
    public $valid = [];
    public $invalid = [];
    public $missing = [];

    private $messagesLkup;

    /**
     * validate.
     *
     * Returns true on success, array of errors otherwise.
     *
     * @param array $input
     * @param array $profile
     *
     * @return boolean/array
     */
    public function validate($input, $profile)
    {
        $this->valid = [];
        $this->invalid = [];
        $this->missing = [];
        $errors = [];
        $missingLkup = [];

        $this->validateProfile($profile);
        $this->setMessagesLookup($profile);

        // Go through just the required fields
        if (isset($profile['required']))
        {
            foreach ($profile['required'] as $fieldName)
            {
                if (!isset($input[$fieldName]))
                {
                    $this->missing[] = $fieldName;
                    $missingLkup[$fieldName] = 1;
                    continue;
                }

                if (is_array($input[$fieldName]))
                {
                    if (empty($input[$fieldName]))
                    {
                        $this->missing[] = $fieldName;
                        $missingLkup[$fieldName] = 1;
                    }
                }
                elseif (strlen($input[$fieldName]) == 0)
                {
                    $this->missing[] = $fieldName;
                    $missingLkup[$fieldName] = 1;
                }
            }
        }

        // Get constraints
        $constraints = $profile['constraints'];

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
                if (!$this->isValidFormat($value, $options))
                {
                    $this->invalid[] = $fieldName;
                    $bad = true;
                    continue;
                }
            }

            // Integers
            if (isset($options['integer']))
            {
                if (!$this->isValidInteger($value, $options))
                {
                    $this->invalid[] = $fieldName;
                    $bad = true;
                    continue;
                }
            }

            // Length
            if (isset($options['length']))
            {
                if (!$this->isValidLength($value, $options))
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

        // Loop through fields with constraint functions in profile
        if (isset($profile['constraint_functions']))
        {
            foreach ($profile['constraint_functions'] as $fieldName => $function)
            {
                $constraintName = $fieldName;
                $functionCall = $function;

                if (is_array($function))
                {
                    $constraintName = $function['name'];
                    $functionCall = $function['function'];
                }

                // Call the function
                if (!$functionCall($input))
                {
                    $this->invalid[] = $constraintName;
                }
            }
        }

        // Setup an array of nice error messages
        $errors = $this->getErrors($profile);

        if (count($errors) > 0)
        {
            return $errors;
        }

        return true;
    }

    /**
     * getJsValidation.
     *
     * @param array $profile
     *
     * @return string
     */
    public function getJsValidation($profile)
    {
        $js = "\n";
        $js .= '<script type="text/javascript" src="ui/js/livevalidation.js"></script>';
        $js .= '<script type="text/javascript">';

        // Get constraints
        $constraints = $profile['constraints'];

        foreach ($constraints as $fieldName => $options)
        {
            $js .= "\n";
            $js .= 'var f'.$fieldName.' = new LiveValidation(\''.$fieldName.'\', { onlyOnSubmit: true });'."\n";

            // Required
            if (isset($options['required']))
            {
                $message = $this->getConstraintMessage($profile, $fieldName, 'missing');

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
                $message = $this->getConstraintMessage($profile, $fieldName, 'invalid');

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
                $message = $this->getConstraintMessage($profile, $fieldName, 'invalid');

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
                $message = $this->getConstraintMessage($profile, $fieldName, 'invalid');

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
                $message = $this->getConstraintMessage($profile, $fieldName, 'invalid');

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
     * validateProfile.
     *
     * Validates the profile. Will die if profile is invalid.
     *
     * @param array $profile
     *
     * @return void
     */
    private function validateProfile($profile)
    {
        $constraintNames = [];

        // List of valid options
        $validOptions = [
            'required'             => 1,
            'constraints'          => [
                'acceptance' => 1,
                'array'      => 1,
                'format'     => 1,
                'integer'    => 1,
                'length'     => 1,
                'name'       => 1,
                'required'   => 1,
            ],
            'constraint_functions' => [
                'function' => 1,
                'name'     => 1,
             ],
            'messages'             => [
                'constraints'          => 1,
                'constraint_functions' => 1,
                'names'                => 1,
            ],
        ];

        // Validate the sections
        foreach ($profile as $section => $value)
        {
            if (!isset($validOptions[$section]))
            {
                die('Invalid profile: uknown key ['.$section.'].');
            }
        }

        // Validate constraints
        foreach ($profile['constraints'] as $fieldName => $options)
        {
            $constraintName = $fieldName;

            // validate the options for this field
            foreach ($options as $optionName => $optionValue)
            {
                if ($optionName == 'name')
                {
                    $constraintName = $optionValue;
                }

                if (!isset($validOptions['constraints'][$optionName]))
                {
                    die('Invalid Profile: invalid key for \'constraints\': [\''.$optionName.'\'].');
                }
            }

            // validate constraint name
            if (isset($constraintNames[$constraintName]))
            {
                die('Invalid profile: duplicate constraint name found ['.$constraintName.'].');
            }

            $constraintNames[$constraintName] = 1;
        }

        // Validate constraint functions
        if (isset($profile['constraint_functions']))
        {
            foreach ($profile['constraint_functions'] as $fieldName => $options)
            {
                $constraintName = $fieldName;

                if (is_array($options))
                {
                    if (!isset($options['name']))
                    {
                        die('Invalid Profile: $profile[\'constraint_functions\'][\''.$fieldName.'\'] found without a constraint name.');
                    }
                    if (!isset($options['function']))
                    {
                        die('Invalid Profile: $profile[\'constraint_functions\'][\''.$fieldName.'\'] found without a function.');
                    }

                    $constraintName = $options['name'];
                }

                // validate constraint name
                if (isset($constraintNames[$constraintName]))
                {
                    die('Invalid profile: duplicate constraint name found ['.$constraintName.'].');
                }

                $constraintNames[$constraintName] = 1;
            }
        }

        // Validate messages
        if (isset($profile['messages']))
        {
            foreach ($profile['messages'] as $msgType => $options)
            {
                if (!isset($validOptions['messages'][$msgType]))
                {
                    die('Invalid profile: uknown messages key ['.$msgType.']');
                }
            }
        }
    }

    /**
     * setMessageLookup.
     *
     * Creates an array for looking up messages by constraint name.
     *
     * Sets the $messagesLkup object variable.
     * Will die if profile is invalid.
     *
     * @param array $profile
     *
     * @return void
     */
    private function setMessagesLookup($profile)
    {
        $this->messagesLkup = [];

        if (!isset($profile['messages']))
        {
            return;
        }

        $messages = $profile['messages'];

        // go through each type of messages
        foreach (['constraint_functions', 'constraints'] as $type)
        {
            if (isset($messages[$type]))
            {
                // 'messages' => array(
                //     'constraints' => array(
                //         'fieldOne' => T_('Field one is wrong.'),
                //     )
                //     'constraint_functions' => array(
                //         'fieldTwo' => T_('Field two is wrong.'),
                //     )
                // )
                // 'messages' => array(
                //     'constraint' => array(
                //         'fieldOne' => T_('Field one is wrong.'),
                //     ),
                //     'constraint_functions' => array(
                //         'fieldOne' => array(
                //             'name'    => 'field_one_function',
                //             'message' => T_('Field one is wrong.'),
                //         )
                //     )
                // )
                foreach ($messages[$type] as $fieldName => $value)
                {
                    $name = $fieldName;
                    $message = $value;

                    // constraint name is given
                    if (is_array($value))
                    {
                        if (!isset($value['name']))
                        {
                            die('Invalid Profile: $profile[\'messages\'][\''.$type.'\'] found without a constraint name.');
                        }
                        if (!isset($value['message']))
                        {
                            die('Invalid Profile: $profile[\'messages\'][\''.$type.'\'] found without a constraint message.');
                        }

                        $name = $value['name'];
                        $message = $value['message'];
                    }

                    // make sure the name is unique
                    if (isset($this->messagesLkup[$name]))
                    {
                        die('Invalid Profile: duplicate constraint name found ['.$fieldName.'].');
                    }

                    $this->messagesLkup[$name] = $message;
                }
            }
        }
    }

    /**
     * getErrors.
     *
     * Returns an array of nicely formatted error messages for
     * each failed constraint.
     *
     * @param array $profile
     *
     * @return array
     */
    private function getErrors($profile)
    {
        $errors = [];

        // Missing
        if (count($this->missing))
        {
            // loop through the list of failed constraints
            foreach ($this->missing as $constraintName)
            {
                if (isset($this->messagesLkup[$constraintName]))
                {
                    $errors[] = cleanOutput($this->messagesLkup[$constraintName]);
                }
                else
                {
                    // see if we have an updated constraintName
                    $name = isset($profile['messages']['names'][$constraintName])
                          ? $profile['messages']['names'][$constraintName]
                          : $constraintName;

                    $errors[] = sprintf(T_('%s is missing.'), $name);
                }
            }
        }

        // Invalid
        if (count($this->invalid))
        {
            // loop through the list of failed constraints
            foreach ($this->invalid as $constraintName)
            {
                if (isset($this->messagesLkup[$constraintName]))
                {
                    $errors[] = cleanOutput($this->messagesLkup[$constraintName]);
                }
                else
                {
                    // see if we have an updated constraintName
                    $name = isset($profile['messages']['names'][$constraintName])
                          ? $profile['messages']['names'][$constraintName]
                          : $constraintName;

                    $errors[] = sprintf(T_('%s is invalid.'), $name);
                }
            }
        }

        return $errors;
    }

    /**
     * getConstraintMessage.
     *
     * Used with the js validation, will return any message for
     * the given constraint name.
     *
     * @param array  $profile
     * @param string $constraintName
     * @param string $type           - missing|invalid
     *
     * @return boolean/string
     */
    private function getConstraintMessage($profile, $constraintName, $type)
    {
        if (isset($this->messagesLkup[$constraintName]))
        {
            return cleanOutput($this->messagesLkup[$constraintName]);
        }
        elseif (isset($profile['messages']['names'][$constraintName]))
        {
            // see if we have an updated constraintName
            $name = $profile['messages']['names'][$constraintName];

            if ($type == 'missing')
            {
                return sprintf(T_('%s is missing.'), $name);
            }

            return sprintf(T_('%s is invalid.'), $name);
        }

        return false;
    }

    /**
     * updateName.
     *
     * Turns the names in invalid and missing array from the name of the
     * field into the message supplied to represent that field.
     *
     * @param string $profile
     *
     * @return void
     */
    private function updateNames($profile)
    {
        // Update field names with messages if available
        if (isset($profile['messages']) && isset($profile['messages']['names']))
        {
            foreach (['missing', 'invalid'] as $type)
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

    /**
     * isValidFormat.
     *
     * @param mixed $value   - form data array or string
     * @param array $options
     *
     * @return bool
     */
    public function isValidFormat($value, $options)
    {
        // array
        if (is_array($value))
        {
            if (!isset($options['array']))
            {
                return false;
            }

            foreach ($value as $v)
            {
                if (preg_match($options['format'], $v) === 0)
                {
                    return false;
                }
            }
        }
        // string
        elseif (strlen($value) > 0)
        {
            if (preg_match($options['format'], $value) === 0)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * isValidInteger.
     *
     * @param mixed $value   - form data array or string
     * @param array $options
     *
     * @return bool
     */
    public function isValidInteger($value, $options)
    {
        // array
        if (is_array($value))
        {
            if (!isset($options['array']))
            {
                return false;
            }

            foreach ($value as $v)
            {
                if (!is_int($v) && !ctype_digit($v))
                {
                    return false;
                }
            }
        }
        // string
        elseif (strlen($value) > 0)
        {
            if (!is_int($value) && !ctype_digit($value))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * isValidLength.
     *
     * @param mixed $value   - form data array or string
     * @param array $options
     *
     * @return bool
     */
    public function isValidLength($value, $options)
    {
        // array
        if (is_array($value))
        {
            if (!isset($options['array']))
            {
                return false;
            }

            foreach ($value as $v)
            {
                if (strlen($value) > $options['length'])
                {
                    return false;
                }
            }
        }
        // string
        elseif (strlen($value) > $options['length'])
        {
            return false;
        }

        return true;
    }
}
