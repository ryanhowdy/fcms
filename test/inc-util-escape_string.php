#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'config_inc.php';
require_once INC.'utils.php';

diag('escape_string');

plan(3);

connectDatabase();

$single_quotes_in  = "This isn't cool";
$single_quotes_out = escape_string($single_quotes_in);

is($single_quotes_out, "This isn\'t cool", 'single quotes');


$double_quotes_in  = 'Bob "Babyface" Smith';
$double_quotes_out = escape_string($double_quotes_in);

is($double_quotes_out, 'Bob \"Babyface\" Smith', 'double quotes');


$sql_injection_in  = "SELECT * FROM users WHERE user='aidan' AND password='' OR ''=''";
$sql_injection_out = escape_string($sql_injection_in);

is($sql_injection_out, "SELECT * FROM users WHERE user=\'aidan\' AND password=\'\' OR \'\'=\'\'", 'sql injection quotes');
