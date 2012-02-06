<?php

// Absolute Paths
define('ROOT', dirname(dirname(__FILE__)) . '/');
define('INC', dirname(__FILE__) . '/');
define('UI', ROOT.'/ui/');
define('THEMES', UI.'/themes/');
define('GALLERY', ROOT.'/gallery/');
define('ADMIN', ROOT.'/admin/');
define('THIRDPARTY', INC.'/thirdparty/');

// User Types
define('ADMIN_USER', 1);
define('HELPER_USER', 2);
define('MEMBER_USER', 3);
define('NON_PHOTOGRAPHER_USER', 4);
define('NON_POSTER_USER', 5);
define('COMMENTER_USER', 6);
define('POSTER_USER', 7);
define('PHOTOGRAPHER_USER', 8);
define('BLOGGER_USER', 9);
define('GUEST_USER', 10);
define('NON_EDIT_USER', 11);
