#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'config_inc.php';
require_once INC.'utils.php';

diag('getTagggedMembersInPhoto');

plan(5);

connectDatabase();

$tagged1 = array(1, 2, 3);
$prev1   = array(1);
$result1 = getAddRemoveTaggedMembers($tagged1, $prev1);

$tagged2 = array(1, 2, 3);
$prev2   = array(4);
$result2 = getAddRemoveTaggedMembers($tagged2, $prev2);

$tagged3 = array(2);
$prev3   = array(1, 2, 3);
$result3 = getAddRemoveTaggedMembers($tagged3, $prev3);

$tagged4 = null;
$prev4   = array(1, 2, 3);
$result4 = getAddRemoveTaggedMembers($tagged4, $prev4);

$tagged5 = array(1, 2, 3);
$prev5   = null;
$result5 = getAddRemoveTaggedMembers($tagged5, $prev5);

is($result1, array('add' => array(2, 3),    'remove' => array()),        'Tagged and previous (adding)');
is($result2, array('add' => array(1, 2, 3), 'remove' => array(4)),       'Tagged and previous (adding & removing)');
is($result3, array('add' => array(),        'remove' => array(1, 3)),    'Tagged and previous (removing)');
is($result4, array('add' => array(),        'remove' => array(1, 2, 3)), 'Previous (removing)');
is($result5, array('add' => array(1, 2, 3), 'remove' => array()),        'Tagged (adding)');
