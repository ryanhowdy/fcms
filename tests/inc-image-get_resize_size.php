#!/usr/bin/php -q
<?php
require_once dirname(dirname(__FILE__)).'/test/lib/utils.php';

require_once TEST.'lib/Test-More.php';
require_once INC.'config_inc.php';
require_once INC.'image_class.php';

$imgObj = new Image(1);

diag('getResizeSize');

plan(9);

//                                                 orig      max
//                                                w    h    w    h
$size_square_both_small = $imgObj->getResizeSize(500, 500, 600, 600);
$size_square_both_big   = $imgObj->getResizeSize(500, 500, 200, 200);
$size_square_width_big  = $imgObj->getResizeSize(500, 500, 200, 600);
$size_tall_width_big    = $imgObj->getResizeSize(200, 400, 100, 500);
$size_tall_height_big   = $imgObj->getResizeSize(200, 400, 200, 200);
$size_tall_both_big     = $imgObj->getResizeSize(200, 400, 100, 100);
$size_wide_width_big    = $imgObj->getResizeSize(400, 200, 100, 500);
$size_wide_height_big   = $imgObj->getResizeSize(400, 200, 500, 100);
$size_wide_both_big     = $imgObj->getResizeSize(400, 200, 100, 100);

is($size_square_both_small, array(500, 500), 'square not resized');
is($size_square_both_big, array(200, 200), 'square both sides too big');
is($size_square_width_big, array(200, 200), 'square width too big');
is($size_tall_width_big, array(100, 200), 'tall width too big');
is($size_tall_height_big, array(100, 200), 'tall height too big');
is($size_tall_both_big, array(50, 100), 'tall both too big');
is($size_wide_width_big, array(100, 50), 'wide width too big');
is($size_wide_height_big, array(200, 100), 'wide height too big');
is($size_wide_both_big, array(100, 50), 'wide both too big');
