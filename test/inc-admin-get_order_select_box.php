#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'config_inc.php';
require_once INC.'gettext.inc';
require_once INC.'admin_class.php';

$adminObj = new Admin(1);

diag('getOrderSelectBox');

plan(1);

$sel_got = $adminObj->getOrderSelectBox('c', 1, 3, 3, 1);
$sel_expected = '<select id="c-order_1" name="c-order_1"><option value="1">1</option><option value="2">2</option><option value="3" selected="selected">3</option></select>';

is($sel_got, $sel_expected, '3 options, 3 selected');
