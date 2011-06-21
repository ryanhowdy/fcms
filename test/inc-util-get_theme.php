#!/usr/bin/php -q
<?php
require_once 'lib/Test-More.php';
require_once '../inc/config_inc.php';
require_once '../inc/util_inc.php';

diag("getTheme");

plan(2);

$theme_no_id   = getTheme();
$theme_bad_id  = getTheme(0);

is($theme_no_id, ROOT.'themes/default/', 'no userid');
is($theme_bad_id, ROOT.'themes/default/', 'bad userid');
