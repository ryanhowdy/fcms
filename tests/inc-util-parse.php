#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'config_inc.php';
require_once INC.'utils.php';

diag('parse');

plan(3);

define('URL_PREFIX', '');

$in_str_entities = '<a href="#">link</a>';
$out_str_entities = '&lt;a href=&quot;#&quot;&gt;link&lt;/a&gt;';

$in_str_smilies = ':(:smile:';
$out_str_smilies = '<img src="'.URL_PREFIX.'ui/smileys/sad.gif" alt=":("/><img src="'.URL_PREFIX.'ui/smileys/smile.gif" alt=":smile:"/>';

$in_str_spaces = 'line 1
line 2

line 4



line 8';
$out_str_spaces = 'line 1<br/>line 2<br/><br/>line 4<br/><br/><br/><br/>line 8';

is($out_str_entities, parse($in_str_entities), 'htmlentities');
is($out_str_smilies, parse($in_str_smilies), 'smilies');
is($out_str_spaces, parse($in_str_spaces), 'spacing');
