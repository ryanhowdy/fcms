#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'config_inc.php';
require_once INC.'utils.php';

diag("getEmailHeaders");

plan(4);

$header = getEmailHeaders('Bob', 'bob@mail.com');
$headers = explode("\r\n", $header);

is($headers[0], 'From: Bob <bob@mail.com>', 'from');
is($headers[1], 'Reply-To: bob@mail.com', 'reply-to');
is($headers[2], 'Content-Type: text/plain; charset=UTF-8;', 'content charset');
is($headers[3], 'MIME-Version: 1.0', 'mime');
