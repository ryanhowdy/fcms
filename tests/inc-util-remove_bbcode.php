#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'config_inc.php';
require_once INC.'utils.php';

diag('removeBBCode');

plan(3);

$matching_mixed_missing_in  = '[b]bold[/b] [b]still bold[/B] [B]also bold';
$matching_mixed_missing_out = removeBBCode($matching_mixed_missing_in);

is($matching_mixed_missing_out, 'bold still bold [B]also bold', 'matching, mixed, missing');


$intermixed_in  = '[b]bold [ins]inserted[/ins][/b]';
$intermixed_out = removeBBCode($intermixed_in);

is($intermixed_out, 'bold inserted', 'intermixed');


$bad_intermixed_in  = '[b]bold [ins][/b]inserted[/ins]';
$bad_intermixed_out = removeBBCode($bad_intermixed_in);

is($bad_intermixed_out, 'bold inserted', 'bad intermixed');
