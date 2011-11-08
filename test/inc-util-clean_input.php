#!/usr/bin/php -q
<?php
require_once 'lib/Test-More.php';
require_once 'lib/utils.php';
require_once '../inc/config_inc.php';
require_once '../inc/utils.php';

diag('cleanInput');

plan(2);

connectDatabase();

$int_in  = "1";
$int_out = cleanInput($int_in, 'int');

ok(ctype_digit($int_out), 'int');


$sql_injection_int_in  = "1;DROP TABLE `users`;";
$sql_injection_int_out = escape_string($sql_injection_int_in);

is($sql_injection_int_out, (int)"1;DROP TABLE `users`;", 'sql injection');
