#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'config_inc.php';
require_once INC.'utils.php';

diag("getTheme");

plan(2);

connectDatabase();

$theme_no_id   = getTheme();
$theme_bad_id  = getTheme(0);

is($theme_no_id, ROOT.'themes/default/', 'no userid');
is($theme_bad_id, ROOT.'themes/default/', 'bad userid');
