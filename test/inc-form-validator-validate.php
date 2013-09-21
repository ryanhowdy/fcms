#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'FormValidator.php';

diag('validate');

$validator = new FormValidator();


// Required
$profileRequired = array(
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
);

$dataRequired = array(
    'field1' => 'something',
    'field2' => 0,
    'field3' => '',
    'field4' => "   ",
);

$errors = $validator->validate($dataRequired, $profileRequired);

ok(!$errors, 'required - form did not validate');
is($validator->valid,   array('field1', 'field2', 'field4'), 'required - valid');
is($validator->invalid, array(), 'required - invalid');
is($validator->missing, array('field3'), 'required - missing');


// Format
$profileFormat = array(
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
);

$dataFormat = array(
    'field1' => '5',
    'field2' => 10,
    'field3' => ' a\ns_df',
    'field4' => '2013-02-06',
    'field5' => '2013-2-6',
);

$errors = $validator->validate($dataFormat, $profileFormat);

ok(!$errors, 'format - form did not validate');
is($validator->valid,   array('field1', 'field2', 'field3', 'field4'), 'format - valid');
is($validator->invalid, array('field5'), 'format - invalid');
is($validator->missing, array(), 'format - missing');


// Integer
$profileInteger = array(
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
);

$dataInteger = array(
    'field1' => '5',
    'field2' => 10,
    'field3' => 'asdf',
    'field4' => '5.1',
    'field5' => 5.1,
);

$errors = $validator->validate($dataInteger, $profileInteger);

ok(!$errors, 'integer - form did not validate');
is($validator->valid,   array('field1', 'field2'), 'integer - valid');
is($validator->invalid, array('field3', 'field4', 'field5'), 'integer - invalid');
is($validator->missing, array(), 'integer - missing');
