#!/usr/bin/php -q
<?php
require_once 'lib/Test-More.php';
require_once '../inc/config_inc.php';
require_once '../inc/image_class.php';

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
