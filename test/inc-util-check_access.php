#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'config_inc.php';
require_once INC.'utils.php';

diag('checkAccess');

plan(2);

connectDatabase();

$bad_id = checkAccess(0);
$good_id = checkAccess(1);

ok(ctype_digit($bad_id), 'bad id');
ok(ctype_digit($good_id), 'good id');
