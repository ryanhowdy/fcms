#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'config_inc.php';
require_once INC.'image_class.php';

$imgObj = new Image(1);

diag('isValid');

plan(4);

connectDatabase();

$imgObj->name = 'normal.gif';
$imgObj->type = 'image/gif';
$imgObj->getExtension();

ok($imgObj->isValid(), 'normal');


$imgObj->name = 'not.so.normal.jpeg';
$imgObj->type = 'image/jpeg';
$imgObj->getExtension();

ok($imgObj->isValid(), 'dot in filename');


$imgObj->name = 'bad filename.jo';
$imgObj->type = 'text/plain';
$imgObj->getExtension();

ok(!$imgObj->isValid(), 'bad extension');


$imgObj->name = 'no_extension.';
$imgObj->type = 'image/jpeg';
$imgObj->getExtension();

ok(!$imgObj->isValid(), 'no extension');
