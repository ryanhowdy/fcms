<?php

define('ROOT', dirname(dirname(__FILE__)) . '/familyconnections/');
define('TEST', dirname(dirname(__FILE__)) . '/test/');
define('INC', ROOT.'inc/');
define('UI', ROOT.'ui/');
define('THEMES', UI.'themes/');
define('GALLERY', ROOT.'gallery/');
define('ADMIN', ROOT.'admin/');
define('THIRDPARTY', INC.'thirdparty/');

require_once INC.'config_inc.php';
require_once INC.'thirdparty/gettext.inc';
require_once INC.'utils.php';
require_once INC.'constants.php';
require_once INC.'Error.php';
require_once INC.'User.php';
require_once INC.'Database.php';
