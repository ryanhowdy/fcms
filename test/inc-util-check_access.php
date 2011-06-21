#!/usr/bin/php -q
<?php
require_once 'lib/Test-More.php';
require_once '../inc/config_inc.php';
require_once '../inc/util_inc.php';

diag('checkAccess');

plan(2);

$bad_id = checkAccess(0);
$good_id = checkAccess(1);

ok(ctype_digit($bad_id), 'bad id');
ok(ctype_digit($good_id), 'good id');
