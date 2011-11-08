#!/usr/bin/php -q
<?php
require_once 'lib/Test-More.php';
require_once '../inc/config_inc.php';
require_once '../inc/utils.php';

diag('cleanFilename');

plan(2);

$spaces_in  = 'New Microsoft Office Word Document.docx';
$spaces_out = cleanFilename($spaces_in);

is($spaces_out, 'New_Microsoft_Office_Word_Document.docx', 'spaces');


$special_chr_in  = 'test@#$%^&*()- 2314.zip';
$special_chr_out = cleanFilename($special_chr_in);

is($special_chr_out, 'test-_2314.zip', 'special characters');
