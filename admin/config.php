<?php
session_start();
if (get_magic_quotes_gpc()) {
    $_REQUEST = array_map('stripslashes', $_REQUEST);
    $_GET = array_map('stripslashes', $_GET);
    $_POST = array_map('stripslashes', $_POST);
    $_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');

// Check that the user is logged in
isLoggedIn('admin/');
$current_user_id = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");
include_once('../inc/admin_class.php');
$admin = new Admin($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL['pagetitle'] = _('Administration: Configuration');
$TMPL['path'] = "../";
$TMPL['admin_path'] = "";
$TMPL['javascript'] = '
<script src="../inc/livevalidation.js" type="text/javascript"></script>';

// Show Header
include_once(getTheme($current_user_id, $TMPL['path']) . 'header.php');

echo '
        <div id="config" class="centercontent">';

if (checkAccess($current_user_id) > 1) {
    echo '
            <p class="error-alert">
                <b>'._('You do not have access to view this page.').'</b><br/>
                '._('This page requires an access level 1 (Admin).').' 
                <a href="../contact.php">'._('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
} else {
    echo '
            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="?view=info">'._('Website Information').'</a></li>
                    <li><a href="?view=defaults">'._('Defaults').'</a></li>
                    <li><a href="?view=sections">'._('Optional Sections').'</a></li>
                    <li><a href="?view=gallery">'._('Photo Gallery').'</a></li>
                </ul>
            </div>

            <div id="maincolumn">';

    $show = true;

    //-------------------------------------------------------------------------
    // Update Website Info
    //-------------------------------------------------------------------------
    if (isset($_POST['submit-sitename'])) {
        if (isset($_POST['sitename'])) {
            $sql = "UPDATE `fcms_config` "
                 . "SET `sitename` = '" . escape_string($_POST['sitename']) . "'";
            mysql_query($sql) or displaySQLError(
                'Sitename Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
        }
        if (isset($_POST['contact'])) {
            $sql = "UPDATE `fcms_config` "
                 . "SET `contact` = '" . escape_string($_POST['contact']) . "'";
            mysql_query($sql) or displaySQLError(
                'Contact Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
        }
        if (isset($_POST['activation'])) {
            $sql = "UPDATE `fcms_config` SET `auto_activate` = " . escape_string($_POST['activation']);
            mysql_query($sql) or displaySQLError(
                'Activation Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
        }
        if (isset($_POST['site_off'])) {
            $sql = "UPDATE `fcms_config` ";
            if ($_POST['site_off'] == 'yes') {
                $sql .= "SET `site_off` = '1'";
            } else {
                $sql .= "SET `site_off` = '0'";
            }
            mysql_query($sql) or displaySQLError(
                'Site Off Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
        }
        echo '
            <p class="ok-alert" id="update">'._('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
    }

    //-------------------------------------------------------------------------
    // Update Defaults
    //-------------------------------------------------------------------------
    if (isset($_POST['submit-defaults'])) {
        $sql = "ALTER TABLE `fcms_user_settings` "
             . "ALTER `theme` SET DEFAULT '".basename($_POST['theme'])."'";
        mysql_query($sql) or displaySQLError(
            'Theme Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $sql = "ALTER TABLE `fcms_user_settings` ALTER `showavatar` ";
        if (isset($_POST['showavatar'])) {
            if ($_POST['showavatar'] == 'yes') {
                $sql .= "SET DEFAULT '1'";
            } else {
                $sql .= "SET DEFAULT '0'";
            }
        }
        mysql_query($sql) or displaySQLError(
            'Show Avatar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $sql = "ALTER TABLE `fcms_user_settings` "
             . "ALTER `displayname` SET DEFAULT '".escape_string($_POST['displayname'])."'";
        mysql_query($sql) or displaySQLError(
            'Display Name Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $sql = "ALTER TABLE `fcms_user_settings` "
             . "ALTER `frontpage` SET DEFAULT '".escape_string($_POST['frontpage'])."'";
        mysql_query($sql) or displaySQLError(
            'Frontpage Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $sql = "ALTER TABLE `fcms_user_settings` "
             . "ALTER `timezone` SET DEFAULT '".escape_string($_POST['timezone'])."'";
        mysql_query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $sql = "ALTER TABLE `fcms_user_settings` ALTER `dst` ";
        if (isset($_POST['dst'])) {
            if ($_POST['dst'] == 'on') {
                $sql .= "SET DEFAULT '1'";
            } else {
                $sql .= "SET DEFAULT '0'";
            }
        }
        mysql_query($sql) or displaySQLError(
            'DST Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $sql = "ALTER TABLE `fcms_user_settings` "
             . "ALTER `boardsort` SET DEFAULT '".escape_string($_POST['boardsort'])."'";
        mysql_query($sql) or displaySQLError(
            'Board Sort Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        if (isset($_POST['changeAll'])) {
            $avatar = isset($upfile) ? $upfile : '0x0.gif';
            $sql = "UPDATE `fcms_user_settings` "
                 . "SET `theme` = '" . basename($_POST['theme']) . "', ";
            if (isset($_POST['showavatar'])) {
                if ($_POST['showavatar'] == 'yes') {
                    $sql .= "`showavatar` = '1', ";
                } else {
                    $sql .= "`showavatar` = '0', ";
                }
            }
            $sql .= "`displayname` = '" . escape_string($_POST['displayname']) . "', "
                  . "`frontpage` = '" . escape_string($_POST['frontpage']) . "', "
                  . "`timezone` = '" . escape_string($_POST['timezone']) . "', ";
            if (isset($_POST['dst'])) {
                if ($_POST['dst'] == 'on') {
                    $sql .= "`dst` = '1', ";
                } else {
                    $sql .= "`dst` = '0', ";
                }
            }
            $sql .= "`boardsort` = '" . escape_string($_POST['boardsort']) . "'";
            mysql_query($sql) or displaySQLError(
                'Update All Users Error',  __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
        echo '
            <p class="ok-alert" id="update">'._('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
    }

    //-------------------------------------------------------------------------
    // Update Optional Sections
    //-------------------------------------------------------------------------
    if (isset($_GET['add'])) {
        if (ctype_digit($_GET['add'])) {
            // Get last order
            $sql = "SELECT `order` FROM `fcms_navigation` WHERE `col` = 4 ORDER BY `order` DESC LIMIT 1";
            $result = mysql_query($sql)  or displaySQLError(
                'Remove Section Error',  __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $r = mysql_fetch_array($result);
            $order = $r['order'] + 1;
            // Add after last one
            $sql = "UPDATE `fcms_navigation` SET `order` = $order WHERE `id` = ".escape_string($_GET['add']);
            mysql_query($sql) or displaySQLError(
                'Remove Section Error',  __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            // Refresh so it shows up in nav immediately
            echo '<meta http-equiv=\'refresh\' content=\'0;URL=config.php?view=sections\'>';
        }
    }
    if (isset($_POST['remove'])) {
        // Remove section
        $sql = "UPDATE `fcms_navigation` SET `order` = 0 WHERE `id` = ".escape_string($_POST['remove']);
        mysql_query($sql) or displaySQLError(
            'Remove Section Error',  __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        // Get the current nav order
        $sql = "SELECT * FROM `fcms_navigation` WHERE `col` = 4 ORDER BY `order`";
        $result = mysql_query($sql)  or displaySQLError(
            'Remove Section Error',  __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $i = 1;
        if (mysql_num_rows($result) > 0) {
            while ($r = mysql_fetch_array($result)) {
                if ($r['order'] != 0) {
                    // Update the new order
                    $sql = "UPDATE `fcms_navigation` SET `order` = '$i' WHERE `id` = ".$r['id'];
                    mysql_query($sql) or displaySQLError(
                        'Update Order Error',  __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                    $i++;
                }
            }
        }
        // Refresh so it removes from nav immediately
        echo '<meta http-equiv=\'refresh\' content=\'0;URL=config.php?view=sections\'>';
    }
    if (isset($_POST['submit-sections'])) {
        // Validate proper order
        $orders = array();
        foreach ($_POST as $key => $value) {
            $pos = strpos($key, 'order');
            if ($pos !== false) {
                $orders[] = $value;
            }
        }
        $count1 = count($orders);
        $orders = array_unique($orders);
        $count2 = count($orders);
        if ($count1 == $count2) {
            // Update the order
            foreach ($_POST as $key => $value) {
                $pos = strpos($key, 'order');
                if ($pos !== false) {
                    $id = substr($key, 5);
                    $sql = "UPDATE `fcms_navigation` SET `order` = ".escape_string($value)." WHERE `id` = ".escape_string($id);
                    mysql_query($sql) or displaySQLError(
                        'Update Order Error',  __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                }
            }
            echo '
            <p class="ok-alert" id="update">'._('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
        } else {
            echo '
            <p class="error-alert" id="update">'._('You cannot have two sections with the same order.').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
        }
    }

    //-------------------------------------------------------------------------
    // Update Photo Gallery
    //-------------------------------------------------------------------------
    if (isset($_POST['submit-gallery'])) {
        $sql = "UPDATE `fcms_config` SET `full_size_photos` = " . escape_string($_POST['full_size_photos']);
        mysql_query($sql) or displaySQLError('Full Size Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        echo '
            <p class="ok-alert" id="update">'._('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
    }
    if (isset($_GET['addsection'])) {
        if ($_GET['addsection'] == 'news') {
            $sql = "CREATE TABLE `fcms_news` (
                        `id` int(11) NOT NULL auto_increment, 
                        `title` varchar(50) NOT NULL default '', 
                        `news` text NOT NULL, 
                        `user` int(11) NOT NULL default '0', 
                        `date` datetime NOT NULL default '0000-00-00 00:00:00', 
                        PRIMARY KEY (`id`), 
                        KEY `userindx` (`user`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or displaySQLError('New News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $sql = "ALTER TABLE `fcms_news` ADD CONSTRAINT `fcms_news_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
            mysql_query($sql) or displaySQLError('Alter News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $sql = "CREATE TABLE `fcms_news_comments` (`id` int(11) NOT NULL auto_increment, `news` int(11) NOT NULL default '0', `comment` text NOT NULL, `date` timestamp NOT NULL default '0000-00-00 00:00:00', `user` int(11) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `photo_ind` (`news`), KEY `user_ind` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or displaySQLError('New News Comments Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $sql = "ALTER TABLE `fcms_news_comments` ADD CONSTRAINT `fcms_news_comments_ibfk_2` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fcms_news_comments_ibfk_1` FOREIGN KEY (`news`) REFERENCES `fcms_news` (`id`) ON DELETE CASCADE";
            mysql_query($sql) or displaySQLError('Alter News Comments Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        }
        if ($_GET['addsection'] == 'prayers') {
            $sql = "CREATE TABLE `fcms_prayers` (
                        `id` int(11) NOT NULL auto_increment, 
                        `for` varchar(50) NOT NULL default '', 
                        `desc` text NOT NULL, 
                        `user` int(11) NOT NULL default '0', 
                        `date` datetime NOT NULL default '0000-00-00 00:00:00', 
                        PRIMARY KEY (`id`), 
                        KEY `userindx` (`user`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or displaySQLError('New Prayers Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $sql = "ALTER TABLE `fcms_prayers` ADD CONSTRAINT `fcms_prayers_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
            mysql_query($sql) or displaySQLError('Alter Prayers Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        }
        if ($_GET['addsection'] == 'recipes') {
            $sql = "CREATE TABLE `fcms_recipes` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `category` VARCHAR(50) NOT NULL, 
                        `name` VARCHAR(50) NOT NULL DEFAULT 'My Recipe', 
                        `recipe` TEXT NOT NULL, 
                        `user` INT(11) NOT NULL, 
                        `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or displaySQLError('New Recipe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $sql = "ALTER TABLE `fcms_recipes` ADD CONSTRAINT `fcms_recipes_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
            mysql_query($sql) or displaySQLError('Alter Recipe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        }
        if ($_GET['addsection'] == 'documents') {
            $sql = "CREATE TABLE `fcms_documents` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `name` VARCHAR(50) NOT NULL, 
                        `description` TEXT NOT NULL, 
                        `user` INT(11) NOT NULL, 
                        `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or displaySQLError('New Documents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $sql = "ALTER TABLE `fcms_documents` ADD CONSTRAINT `fcms_documents_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
            mysql_query($sql) or displaySQLError('Alter Documents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        }
    }
    if ($show) {
        $view = isset($_GET['view']) ? $_GET['view'] : 'info';
        $admin->displayAdminConfig($view);
    }
}

echo '
            </div>

        </div><!-- .centercontent -->';

// Show Footer
include_once(getTheme($current_user_id, $TMPL['path']) . 'footer.php');

function isArrayUnique ($array) { 
    $dup_array = $array; 
    $dup_array = array_unique($dup_array); 
    if (count($dup_array) != count($array)) { 
        return TRUE; 
    } else { 
        return FALSE; 
    } 
} ?>
