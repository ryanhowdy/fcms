<?php

define('ROOT', dirname(dirname(__FILE__)) . '/familyconnections/');
define('TEST', dirname(dirname(__FILE__)) . '/test/');
define('INC', ROOT.'inc/');
define('UI', ROOT.'ui/');
define('THEMES', UI.'themes/');
define('GALLERY', ROOT.'gallery/');
define('ADMIN', ROOT.'admin/');
define('THIRDPARTY', INC.'thirdparty/');

require_once THIRDPARTY.'php-gettext/gettext.inc';
require_once INC.'utils.php';
require_once INC.'constants.php';
require_once INC.'Error.php';
require_once INC.'User.php';
require_once INC.'Database.php';

// Some travis environments use phpunit > 6
$newClass = '\PHPUnit\Framework\TestCase';
$oldClass = '\PHPUnit_Framework_TestCase';
if (!class_exists($newClass) && class_exists($oldClass)) {
    class_alias($oldClass, $newClass);
}
