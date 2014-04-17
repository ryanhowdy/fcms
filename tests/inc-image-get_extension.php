#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'config_inc.php';
require_once INC.'image_class.php';

$imgObj = new Image(1);

diag('getExtension');

plan(5);

$imgObj->name = 'normal.gif';
$imgObj->getExtension();

is($imgObj->extension, 'gif', 'normal');


$imgObj->name = 'not.so.normal.jpeg';
$imgObj->getExtension();

is($imgObj->extension, 'jpeg', 'dot in filename');


$imgObj->name = 'bad filename.jo';
$imgObj->getExtension();

is($imgObj->extension, 'jo', 'bad extension');


$imgObj->name = 'missing_extension.';
$imgObj->getExtension();

is($imgObj->extension, '', 'missing extension');


$imgObj->name = 'no_extension';
$imgObj->getExtension();

is($imgObj->extension, '', 'no extension');
