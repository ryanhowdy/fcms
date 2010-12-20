<?php
session_start();
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');

// Check that the user is logged in
isLoggedIn('admin/');
$currentUserId = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");
include_once('../inc/admin_class.php');
$admin = new Admin($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Upgrade'),
    'path'          => "../",
    'admin_path'    => "",
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

// Show Header
include_once(getTheme($currentUserId, $TMPL['path']) . 'header.php');

echo '
        <div class="centercontent">';

if (checkAccess($currentUserId) > 1) {
    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 1 (Admin).').' 
                <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
} else {
    if (isset($_POST['upgrade'])) {
        upgrade($_POST['version']);
    } else {
        echo '
            <h2>'.T_('Upgrade Check').'</h2>
            <p><b>'.T_('Current Version').':</b> &nbsp;'.getCurrentVersion().'</p>
            <p><b>'.T_('Latest Version').':</b> &nbsp;&nbsp;&nbsp;';
        $ver = file("http://www.haudenschilt.com/fcms/latest_version.php");
        $uptodate = false; 

        if (
            trim(str_pad(str_replace(".", "", substr($ver[0], 18)), 4, "0")) <= 
            trim(str_pad(str_replace(".", "", substr(getCurrentVersion(), 18)), 4, "0"))
        ) {
            $uptodate = true;
            // TODO
            // Remove inline CSS
            echo $ver[0].' <span style="padding-left:5px;font-size:small;font-weight:bold;color:green">';
            echo T_('Awesome, your installation is up to date.').'</span>';
        } else {
            // TODO
            // Remove inline CSS
            echo $ver[0].' <span style="padding-left:5px;font-size:small;font-weight:bold;color:red">';
            echo T_('Bummer!, your installation is out of date.').'  <a href="http://www.familycms.com/">'
                .T_('Download latest version.').'</a></span>';
        }
        echo '</p>
            <form method="post" action="upgrade.php">
                <div>
                    <input type="hidden" name="version" value="'.$ver[0].'"/>
                    <input type="submit" name="upgrade" value="'.T_('Upgrade').'"';
        if ($uptodate) {
            echo ' onclick="javascript:return confirm(\''.T_('Your installation is already up to date!').'\n'.T_('Are you sure you want to proceed?').'\');"';
        }
        echo '/>
                </div>
            </form>';
    }
}

echo '
        </div><!-- .centercontent -->';

// Show Footer
include_once(getTheme($currentUserId, $TMPL['path']) . 'footer.php');


function upgrade ($version) {
    global $cfg_mysql_db, $cfg_sitename, $cfg_contact_email, $cfg_use_news, $cfg_use_prayers;
    echo '
            <h2>'.sprintf(T_('Upgrading to %s.'), $version).'</h2>
            <p>'.T_('Upgrade is in process...').'</p>';
    /*
     * FCMS 2.0
     * User Settings.
     */
    echo "<p><b>(2.0)</b> ".T_('Adding User Settings...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or displaySQLError(
        'Table Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $user_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_user_settings') { $user_fixed = true; }
        }
    }
    if ($user_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "CREATE TABLE `fcms_user_settings` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `user` INT(11) NOT NULL, 
                    `theme` varchar(25) NOT NULL default 'default', 
                    `boardsort` SET('ASC', 'DESC') NOT NULL DEFAULT 'ASC', 
                    `showavatar` TINYINT(1) NOT NULL DEFAULT '1', 
                    `displayname` SET('1','2','3') NOT NULL DEFAULT '1', 
                    `frontpage` set('1','2') NOT NULL default '1', 
                    `timezone` set('-12 hours', '-11 hours', '-10 hours', '-9 hours', '-8 hours', '-7 hours', '-6 hours', '-5 hours', '-4 hours', '-3 hours -30 minutes', '-3 hours', '-2 hours', '-1 hours', '-0 hours', '+1 hours', '+2 hours', '+3 hours', '+3 hours +30 minutes', '+4 hours', '+4 hours +30 minutes', '+5 hours', '+5 hours +30 minutes', '+6 hours', '+7 hours', '+8 hours', '+9 hours', '+9 hours +30 minutes', '+10 hours', '+11 hours', '+12 hours') NOT NULL DEFAULT '-5 hours', 
                    `dst` TINYINT(1) NOT NULL DEFAULT '0', 
                    `email_updates` TINYINT(1) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `user_ind` (`user`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or displaySQLError(
            'Create Table Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_user_settings` 
                ADD CONSTRAINT `fcms_user_stgs_ibfk_1` FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
        mysql_query($sql) or displaySQLError(
            'Constraint Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $result = mysql_query("SELECT * FROM `fcms_users`") or die(mysql_error());
        while ($r = mysql_fetch_array($result)) {
            if ($r['showavatar'] == 'YES') {
                $showavatar = '1';
            } else {
                $showavatar = '0';
            }
            mysql_query("INSERT INTO `fcms_user_settings` (`user`, `theme`, `boardsort`, `showavatar`, `displayname`, `frontpage`, `timezone`, `dst`) "
                . "VALUES ("
                . "'" . $r['id'] . "', "
                . "'" . $r['theme'] . "', "
                . "'" . $r['boardsort'] . "', "
                . "'$showavatar', "
                . "'" . $r['displayname'] . "', "
                . "'" . $r['frontpage'] . "', "
                . "'" . $r['timezone'] . "', "
                . "'" . $r['dst'] . "')") or die("<b>Transfering User Settings</b><br/>" . mysql_error());
        }
        // Supressing errors here because not dropping these fields doesn't effect site
        @mysql_query("ALTER TABLE `fcms_users` DROP `boardsort`");
        @mysql_query("ALTER TABLE `fcms_users` DROP `showavatar`");
        @mysql_query("ALTER TABLE `fcms_users` DROP `displayname`");
        @mysql_query("ALTER TABLE `fcms_users` DROP `timezone`");
        @mysql_query("ALTER TABLE `fcms_users` DROP `dst`");
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.0
     * Update Avatar.
     */
    echo "<p><b>(2.0)</b> ".T_('Upgrading Avatar...');
    $sql = "ALTER TABLE `fcms_users` ALTER `avatar` SET DEFAULT 'no_avatar.jpg'";
    mysql_query($sql) or displaySQLError(
        'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    /*
     * FCMS 2.0.1
     * Chat Room.
     */
    echo "<p><b>(2.0.1)</b> ".T_('Adding Chat...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or displaySQLError(
        'Table Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $chat_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_chat_users') { $chat_fixed = true; }
        }
    }
    if ($chat_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "CREATE TABLE `fcms_chat_users` (
                    `user_name` VARCHAR(64) NOT NULL
                ) ENGINE=INNODB DEFAULT CHARSET=utf8";
        mysql_query($sql) or displaySQLError(
            'Create Table Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "CREATE TABLE `fcms_chat_messages` (
                    `message_id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `chat_id` INT(11) NOT NULL DEFAULT '0', 
                    `user_id` INT(11) NOT NULL DEFAULT '0', 
                    `user_name` VARCHAR(64) DEFAULT NULL, 
                    `message` TEXT, 
                    `post_time` DATETIME DEFAULT NULL, 
                    PRIMARY KEY  (`message_id`)
                ) ENGINE=INNODB DEFAULT CHARSET=utf8";
        mysql_query($sql) or displaySQLError(
            'Create Table Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.1
     * Site Off
     */
    echo "<p><b>(2.1)</b> ".T_('Adding Ability to Turn Site Off...');
    $sql = "SHOW COLUMNS FROM `fcms_config`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $siteoff_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'site_off') { $siteoff_fixed = true; }
        }
    }
    if ($siteoff_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_config` ADD `site_off` TINYINT(1) NOT NULL DEFAULT '0'";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.1
     * Alerts
     */
    echo "<p><b>(2.1)</b> ".T_('Adding Alerts...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or displaySQLError(
        'Table Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $alert_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_alerts') { $alert_fixed = true; }
        }
    }
    if ($alert_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "CREATE TABLE `fcms_alerts` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT, 
                    `alert` VARCHAR(50) NOT NULL DEFAULT '0', 
                    `user` INT(25) NOT NULL DEFAULT '0', 
                    `hide` TINYINT(1) NOT NULL DEFAULT '1',
                    PRIMARY KEY (`id`),
                    KEY `alert_ind` (`alert`),
                    KEY `user_ind` (`user`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or displaySQLError(
            'Create Table Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.1
     * Polls
     */
    echo "<p><b>(2.1)</b> ".T_('Adding Historical Poll Data...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or displaySQLError(
        'Tables Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $poll_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_poll_votes') { $poll_fixed = true; }
        }
    }
    if ($poll_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "RENAME TABLE `fcms_poll_users` TO `fcms_poll_votes`";
        mysql_query($sql) or displaySQLError(
            'Rename Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_poll_votes` ADD COLUMN (`poll_id` INT(11) NOT NULL DEFAULT '0')";
        mysql_query($sql) or displaySQLError(
            'Add Column Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.1
     * Update Chat Room
     */
    echo "<p><b>(2.1)</b> ".T_('Adding Chat Time...');
    $sql = "SHOW COLUMNS FROM `fcms_chat_users`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $chat_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'time') { $chat_fixed = true; }
        }
    }
    if ($chat_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_chat_users` ADD `time` DATETIME NOT NULL";
        mysql_query($sql) or displaySQLError(
            'Add Column Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.2
     * Advanced Uploader
     */
    echo "<p><b>(2.2)</b> ".T_('Adding Advanced Uploader...');
    $sql = "SHOW COLUMNS FROM `fcms_user_settings`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $upload_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'advanced_upload') { $upload_fixed = true; }
        }
    }
    if ($upload_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_user_settings` ADD `advanced_upload` TINYINT(1) NOT NULL DEFAULT '0'";
        mysql_query($sql) or displaySQLError(
            'Add Column Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.2
     * Navigation
     */
    echo "<p><b>(2.2)</b> ".T_('Adding Navigation...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or displaySQLError(
        'Table Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $nav_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_navigation') { $nav_fixed = true; }
        }
    }
    if ($nav_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "CREATE TABLE `fcms_navigation` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT, 
                    `link` VARCHAR(30) NOT NULL, 
                    `col` TINYINT(1) NOT NULL,
                    `order` TINYINT(2) NOT NULL, 
                    `req` TINYINT(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or displaySQLError(
            'Create Table Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                VALUES ('profile', 2, 1, 1),
                    ('settings', 2, 2, 1),
                    ('pm', 2, 3, 1),
                    ('messageboard', 3, 1, 1),
                    ('chat', 3, 2, 1),
                    ('photogallery', 4, 1, 1),
                    ('addressbook', 4, 2, 1),
                    ('calendar', 4, 3, 1),
                    ('familynews', 4, 4, 0),
                    ('recipes', 4, 5, 0),
                    ('documents', 4, 6, 0),
                    ('prayers', 4, 7, 0)";
        mysql_query($sql) or displaySQLError(
            'INSERT Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.2
     * User Language
     */
    echo "<p><b>(2.2)</b> ".T_('Adding User Language...');
    $sql = "SHOW COLUMNS FROM `fcms_user_settings`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $lang_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'language') { $lang_fixed = true; }
        }
    }
    if ($lang_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_user_settings` ADD `language` VARCHAR(6) NOT NULL DEFAULT 'en_US'";
        mysql_query($sql) or displaySQLError(
            'Add Column Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Category Table.
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Category Table...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or die("$sql<br/>".mysql_error());
    $cat_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_category') { $cat_fixed = true; }
        }
    }
    if ($cat_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "CREATE TABLE `fcms_category` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(50) NOT NULL,
                    `type` VARCHAR(20) NOT NULL,
                    `user` INT(11) NOT NULL,
                    `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `color` VARCHAR(20) NULL,
                    PRIMARY KEY (`id`), 
                    KEY `user_ind` (`user`)
                )
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        // Drop FK
        $sql = "ALTER TABLE `$cfg_mysql_db`.`fcms_gallery_photos` DROP FOREIGN KEY `fcms_gallery_photos_ibfk_2`";
        @mysql_query($sql);
        $sql = "SELECT p.`id` AS pid, p.`category` AS cid, c.`name`, c.`user` 
                FROM `fcms_gallery_category` AS c, `fcms_gallery_photos` AS p 
                WHERE p.`category` = c.`id`";
        $result = mysql_query($sql) or die("$sql<br/>".mysql_error());
        $last_category = '';
        $cat_id = 0;
        while ($r = mysql_fetch_array($result)) {
            if ($r['name'] != $last_category) {
                // transfer category to new table
                $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`) "
                    . "VALUES ("
                    . "'" . addslashes($r['name']) . "', "
                    . "'gallery', "
                    . "'" . $r['user'] . "', "
                    . "NOW())";
                mysql_query($sql) or die("$sql<br/>".mysql_error());
                $cat_id = mysql_insert_id();
                $last_category = $r['name'];
            }
            // update photo to point to new category table id
            $sql = "UPDATE `fcms_gallery_photos` SET `category` = '$cat_id' WHERE `id` = '".$r['pid']."'";
            mysql_query($sql) or die("$sql<br/>".mysql_error());
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Calendar Repeating.
     */
    echo "<p><b>(2.3)</b> ".T_('Upgrading Calendar (Repeating/Categories)...');
    $sql = "SHOW COLUMNS FROM `fcms_calendar`";
    $result = mysql_query($sql) or die("$sql<br/>".mysql_error());
    $cal_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'repeat') { $cal_fixed = true; }
        }
    }
    if ($cal_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        // Add repeat field
        $sql = "ALTER TABLE `fcms_calendar` ADD `repeat` VARCHAR(20) NULL";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        // Add category field
        $sql = "ALTER TABLE `fcms_calendar` ADD `category` INT(11) NOT NULL DEFAULT '0'";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        // Create Anniversary, Birthday, and Holiday categories
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`, `color`)
                VALUES  ('', 'calendar', 1, NOW(), 'none')";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $none = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`, `color`)
                VALUES  ('".T_('Anniversary')."', 'calendar', 1, NOW(), 'green')";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $ann = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`, `color`)
                VALUES ('".T_('Birthday')."', 'calendar', 1, NOW(), 'red')";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $bday = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`, `color`)
                VALUES ('".T_('Holiday')."', 'calendar', 1, NOW(), 'violet')";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $hol = mysql_insert_id();
        $sql = "SELECT * FROM `fcms_calendar`";
        $result = mysql_query($sql) or die("$sql<br/>".mysql_error());
        while ($r = mysql_fetch_array($result)) {
            // transfer data into new format
            $repeat = '';
            $repeat = ($r['type'] != 'Other') ? '`repeat` = \'yearly\', ' : $repeat;
            $cat = $none;
            $cat = ($r['type'] == 'Birthday') ? $bday : $cat;
            $cat = ($r['type'] == 'Anniversary') ? $ann : $cat;
            $cat = ($r['type'] == 'Holiday') ? $hol : $cat;
            $sql = "UPDATE `fcms_calendar`
                    SET 
                        $repeat
                        `category` = $cat 
                    WHERE `id` = ".$r['id'];
            mysql_query($sql) or die("$sql<br/>".mysql_error());
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Family Tree
     */
    echo "<p><b>(2.3)</b> Adding Family Tree...";
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or die("$sql<br/>".mysql_error());
    $tree_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_relationship') { $tree_fixed = true; }
        }
    }
    if ($tree_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "CREATE TABLE `fcms_relationship` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT,
                    `user` INT(25) NOT NULL,
                    `relationship` VARCHAR(4) NOT NULL,
                    `rel_user` INT(25) NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `user_ind` (`user`),
                    KEY `rel_user` (`rel_user`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                VALUES ('tree', 4, 8, 0)";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Sex
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Sex...');
    $sql = "SHOW COLUMNS FROM `fcms_users`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $sex_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'sex') { $sex_fixed = true; }
        }
    }
    if ($sex_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_users` ADD `sex` CHAR(1) NOT NULL DEFAULT 'M'";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Error Logging
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Error Logging...');
    $sql = "SHOW COLUMNS FROM `fcms_config`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $errors_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'log_errors') { $errors_fixed = true; }
        }
    }
    if ($errors_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_config` ADD `log_errors` TINYINT(1) NOT NULL DEFAULT '1'";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Recipe Categories
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Recipe Categories...');
    $sql = "SHOW COLUMNS FROM `fcms_recipes`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $recipe_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'category') {
                if (strtolower($r['Type']) == 'int(11)') { $recipe_fixed = true; }
            }
        }
    }
    if ($recipe_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {

        // Add new categories
        $categories = array();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Appetizer', 'recipe', 1, NOW())";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $categories['Appetizer'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Breakfast', 'recipe', 1, NOW())";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $categories['Breakfast'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Dessert', 'recipe', 1, NOW())";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $categories['Dessert'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Entree (Meat)', 'recipe', 1, NOW())";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $categories['Entree (Meat)'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Entree (Seafood)', 'recipe', 1, NOW())";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $categories['Entree (Seafood)'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Entree (Vegetarian)', 'recipe', 1, NOW())";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $categories['Entree (Vegetarian)'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Salad', 'recipe', 1, NOW())";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $categories['Salad'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Side Dish', 'recipe', 1, NOW())";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $categories['Side Dish'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Soup', 'recipe', 1, NOW())";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        $categories['Soup'] = mysql_insert_id();

        // Update existing recipe categories to new categories
        $sql = "SELECT `id`, `category` FROM `fcms_recipes`";
        $result = mysql_query($sql) or die("$sql<br/>".mysql_error());
        while ($r = mysql_fetch_array($result)) {
            $sql = "UPDATE `fcms_recipes`
                    SET `category` = ".$categories[$r['category']]." 
                    WHERE `id` = ".$r['id'];
            mysql_query($sql) or die("$sql<br/>".mysql_error());
        }

        // Modify category column
        $sql = "ALTER TABLE `fcms_recipes` MODIFY `category` INT(11) NOT NULL";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Calendar Time
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Calendar Time...');
    $sql = "SHOW COLUMNS FROM `fcms_calendar`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $time_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'time_start') { $time_fixed = true; }
        }
    }
    if ($time_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_calendar` ADD `time_start` TIME NULL";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_calendar` ADD `time_end` TIME NULL";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Recipe ingredients/directions
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Recipe Info...');
    $sql = "SHOW COLUMNS FROM `fcms_recipes`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $recipe_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'ingredients') { $recipe_fixed = true; }
        }
    }
    if ($recipe_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_recipes` ADD `thumbnail` VARCHAR(255) NOT NULL DEFAULT 'no_recipe.jpg' AFTER `name`";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_recipes` ADD `ingredients` TEXT NOT NULL AFTER `thumbnail`";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_recipes` CHANGE `recipe` `directions` TEXT NOT NULL";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Recipe comments
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Recipe Comments...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or die("$sql<br/>".mysql_error());
    $recipe_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_recipe_comment') { $recipe_fixed = true; }
        }
    }
    if ($recipe_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "CREATE TABLE `fcms_recipe_comment` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT,
                    `recipe` INT(25) NOT NULL,
                    `comment` TEXT NOT NULL,
                    `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `user` INT(25) NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `recipe` (`recipe`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die("$sql<br/>".mysql_error());
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Gravatar email
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Gravatar...');
    $sql = "SHOW COLUMNS FROM `fcms_users`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $gravatar_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'gravatar') { $gravatar_fixed = true; }
        }
    }
    if ($gravatar_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_users` ADD `gravatar` VARCHAR(255) NULL AFTER `avatar`";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Awards
     */
    echo "<p><b>(2.3)</b> ".T_('Updating Awards...');
    $sql = "SHOW COLUMNS FROM `fcms_user_awards`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $awards_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'award') { $awards_fixed = true; }
        }
    }
    if ($awards_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "TRUNCATE TABLE `fcms_user_awards`";
        mysql_query($sql) or displaySQLError(
            'Truncate Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_user_awards` CHANGE `type` `award` VARCHAR(100) NOT NULL";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_user_awards` CHANGE `value` `item_id` INT(11) NULL";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_user_awards` ADD `month` INT(6) NOT NULL AFTER `award`";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_user_awards` ADD `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `month`";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Documents mimetype
     */
    echo "<p><b>(2.3)</b> ".T_('Adding mime types...');
    $sql = "SHOW COLUMNS FROM `fcms_documents`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $awards_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'mime') { $awards_fixed = true; }
        }
    }
    if ($awards_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_documents` ADD `mime` VARCHAR(50) NOT NULL DEFAULT 'application/download' AFTER `description`";
        mysql_query($sql) or displaySQLError(
            'Alter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }

    // Set the current Version
    $sql = "UPDATE `fcms_config` SET `current_version` = 'Family Connections 2.3.2'";
    mysql_query($sql) or die("$sql<br/>".mysql_error());
    echo "<p style=\"color:green\"><b>".T_('Upgrade is finished.')."</b></p>";
}
?>
