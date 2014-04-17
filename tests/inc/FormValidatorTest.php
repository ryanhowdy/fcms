<?php

require_once INC.'FormValidator.php';

class FormValidatorTest extends PHPUnit_Framework_TestCase
{
    public function testRequired()
    {
        $profile = array(
            'constraints' => array(
                'field1' => array(
                    'required' => 1,
                ),
                'field2' => array(
                    'required' => 1,
                ),
                'field3' => array(
                    'required' => 1,
                ),
                'field4' => array(
                    'required' => 1,
                ),
            ),
        );

        $data = array(
            'field1' => 'something',
            'field2' => 0,
            'field3' => '',
            'field4' => "   ",
        );

        $validator = new FormValidator();

        $errors = $validator->validate($data, $profile);

        $this->assertEquals($errors, array('field3 is missing.'));
        $this->assertEquals($validator->valid,   array('field1', 'field2', 'field4'));
        $this->assertEquals($validator->invalid, array());
        $this->assertEquals($validator->missing, array('field3'));
    }

    public function testFormat()
    {
        $profile = array(
            'constraints' => array(
                'field1' => array(
                    'format' => '/\d+/',
                ),
                'field2' => array(
                    'format' => '/\d+/',
                ),
                'field3' => array(
                    'format' => '/\w+/',
                ),
                'field4' => array(
                    'format' => '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/',
                ),
                'field5' => array(
                    'format' => '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/',
                ),
            ),
        );

        $data = array(
            'field1' => '5',
            'field2' => 10,
            'field3' => ' a\ns_df',
            'field4' => '2013-02-06',
            'field5' => '2013-2-6',
        );

        $validator = new FormValidator();

        $errors = $validator->validate($data, $profile);

        $this->assertEquals($errors, array('field5 is invalid.'));
        $this->assertEquals($validator->valid,   array('field1', 'field2', 'field3', 'field4'));
        $this->assertEquals($validator->invalid, array('field5'));
        $this->assertEquals($validator->missing, array());
    }

    public function testInteger()
    {
        $profile = array(
            'constraints' => array(
                'field1' => array(
                    'required' => 1,
                    'integer'   => 1,
                ),
                'field2' => array(
                    'integer' => 1,
                ),
                'field3' => array(
                    'integer'   => 1,
                ),
                'field4' => array(
                    'integer'   => 1,
                ),
                'field5' => array(
                    'integer'   => 1,
                ),
            ),
        );

        $data = array(
            'field1' => '5',
            'field2' => 10,
            'field3' => 'asdf',
            'field4' => '5.1',
            'field5' => 5.1,
        );

        $validator = new FormValidator();

        $errors = $validator->validate($data, $profile);

        $this->assertEquals($errors, array(
            'field3 is invalid.',
            'field4 is invalid.',
            'field5 is invalid.',
        ));
        $this->assertEquals($validator->valid,   array('field1', 'field2'));
        $this->assertEquals($validator->invalid, array('field3', 'field4', 'field5'));
        $this->assertEquals($validator->missing, array());
    }
}
