<?php
session_start();

define('URL_PREFIX', '../');

require URL_PREFIX.'fcms.php';

// Check that the user is logged in
isLoggedIn('admin/');

header("Cache-control: private");
include_once('../inc/admin_class.php');
$admin = new Admin($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Upgrade'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

// Show Header
include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'header.php');

echo '
        <div class="centercontent">';

if (checkAccess($_SESSION['login_id']) > 1) {
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
            <p><a href="upgrade.php">'.T_('Please visit the upgrade section.').'</a></p>';
    }
}

echo '
        </div><!-- .centercontent -->';

// Show Footer
include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'footer.php');


function upgrade ($version) {
    global $cfg_mysql_db, $cfg_sitename, $cfg_contact_email, $cfg_use_news, $cfg_use_prayers;
    echo '
            <h2>'.sprintf(T_('Upgrading to %s.'), $version).'</h2>
            <p>'.T_('Upgrade is in process...').'</p>';
    /*
     * FCMS 0.9.5
     * Add the dst field to fcms_users table
     */
    echo '<p><b>(0.9.5)</b> '.T_('Adding Daylight Savings...');
    $sql = "SHOW COLUMNS FROM `fcms_users`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $user_dst_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'dst') { $user_dst_fixed = true; }
        }
    }
    if ($user_dst_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_users` ADD `dst` tinyint(1) NOT NULL default '0'";
        mysql_query($sql) or displaySQLError(
            'Upgrade DST Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    mysql_free_result($result);
    /*
     * FCMS 0.9.9
     * Add the frontpage field to fcms_users table
     */
    echo "<p><b>(0.9.9)</b> ".T_('Adding Frontpage Settings...');
    $sql = "SHOW COLUMNS FROM `fcms_users`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $user_frontpage_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'frontpage') { $user_frontpage_fixed = true; }
        }
    }
    if ($user_frontpage_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_users` ADD COLUMN `frontpage` set('1','2') NOT NULL default '1'";
        mysql_query($sql) or displaySQLError(
            'Upgrade Frontpage Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    mysql_free_result($result);
    /*
     * FCMS 1.0
     * Add the private field to fcms_calendar table
     */
    echo "<p><b>(1.0)</b> ".T_('Adding Private Calendar Entries...');
    $sql = "SHOW COLUMNS FROM `fcms_calendar`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $cal_private_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'private') { $cal_private_fixed = true; }
        }
    }
    if ($cal_private_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_calendar` ADD `private` TINYINT(1) NOT NULL DEFAULT '0'";
        mysql_query($sql) or displaySQLError(
            'Upgrade Private Field Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    mysql_free_result($result);
    /*
     * FCMS 1.0
     * Rename the message board tables
     */
    echo "<p><b>(1.0)</b> ".T_('Upgrading Message Board...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or displaySQLError(
        'Table Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $threads_posts_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_board_threads') { $threads_posts_fixed = true; }
        }
    }
    if ($threads_posts_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_threads` RENAME TO `fcms_board_threads`";
        mysql_query($sql) or displaySQLError(
            'Rename Threads Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_posts` RENAME TO `fcms_board_posts`";
        mysql_query($sql) or displaySQLError(
            'Rename Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 1.2
     * Rename the userid field in the fcms_address table
     */
    echo "<p><b>(1.2)</b> ".T_('Upgrading Address Book...');
    $sql = "SHOW COLUMNS FROM `fcms_address`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $address_user_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'user') { $address_user_fixed = true; }
        }
    }
    if ($address_user_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_address` CHANGE `userid` `user` INT(11) NOT NULL DEFAULT '0'";
        mysql_query($sql) or displaySQLError(
            'Rename userid Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 1.3
     * Add the entered_by field to the fcms_address table.
     */
    echo "<p><b>(1.3)</b> ".T_('Adding Address Book Entered By...');
    $sql = "SHOW COLUMNS FROM `fcms_address`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $address_user_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'entered_by') { $address_user_fixed = true; }
        }
    }
    if ($address_user_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_address` ADD `entered_by` INT(11) NOT NULL DEFAULT '0'";
        mysql_query($sql) or displaySQLError(
            'Upgrade entered_by Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "SELECT `id`, `user` FROM `fcms_address` ORDER BY `id`";
        $result = mysql_query($sql) or displaySQLError(
            'Address Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($row = mysql_fetch_array($results)) {
            $sql = "UPDATE `fcms_address` set `entered_by` = ".$row['user']." WHERE id = ".$row['id'];
            mysql_query($sql) or displaySQLError(
                'Upgrade Existing Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            if ($row['id'] !== $row['user']) {
                $sql = "UPDATE `fcms_address` set `user` = ".$row['id']." WHERE id = ".$row['id'];
                $result = mysql_query($sql) or displaySQLError(
                    'Upgrade Address User Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
            }
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 1.5
     * Change charset to utf8.
     */
    echo "<p><b>(1.5)</b> ".T_('Upgrading FCMS DB charset...');
    $sql = "SHOW TABLE STATUS FROM `$cfg_mysql_db` LIKE 'fcms_address'";
    $result = mysql_query($sql) or displaySQLError(
        'Table Status Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $utf8_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            $found = strpos($r['Collation'], 'utf8_');
            if ($found !== false) { $utf8_fixed = true; }
        }
    }
    if ($utf8_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        mysql_query("SET NAMES utf8") or die("<h1>Error</h1><p><b>Could not set encoding</b></p>" . mysql_error());
        mysql_query("ALTER TABLE `fcms_address` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_address` MODIFY `address` VARCHAR(50) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_address` MODIFY `city` VARCHAR(50) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_address` MODIFY `state` VARCHAR(50) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_address` MODIFY `zip` VARCHAR(10) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_address` MODIFY `home` VARCHAR(20) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_address` MODIFY `work` VARCHAR(20) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_address` MODIFY `cell` VARCHAR(20) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_board_posts` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_board_posts` MODIFY `post` TEXT CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_board_threads` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_board_threads` MODIFY `subject` VARCHAR(50) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_calendar` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_calendar` MODIFY `title` VARCHAR(50) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_calendar` MODIFY `desc` TEXT CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_gallery_comments` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_gallery_comments` MODIFY `comment` TEXT CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_gallery_photos` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_gallery_photos` MODIFY `filename` VARCHAR(25) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_gallery_photos` MODIFY `caption` TEXT CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_news` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_news` MODIFY `title` VARCHAR(50) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_news` MODIFY `news` TEXT CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_news_comments` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_news_comments` MODIFY `comment` TEXT CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_polls` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_polls` MODIFY `question` TEXT CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_poll_options` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_poll_options` MODIFY `option` TEXT CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_prayers` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_prayers` MODIFY `for` VARCHAR(50) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_prayers` MODIFY `desc` TEXT CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_users` CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change table charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_users` MODIFY `fname` VARCHAR(25) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_users` MODIFY `lname` VARCHAR(25) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_users` MODIFY `username` VARCHAR(25) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        mysql_query("ALTER TABLE `fcms_users` MODIFY `password` VARCHAR(255) CHARACTER SET utf8;") or die("</p><p style=\"color:red\">Could not change column charset</p><p style=\"color:red\">".mysql_error()."</p>");
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 1.5
     * Add Config to DB.
     */
    echo "<p><b>(1.5)</b> ".T_('Adding Configuration...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or displaySQLError(
        'Table Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $config_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_config') { $config_fixed = true; }
        }
    }
    if ($config_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "CREATE TABLE `fcms_config` (
                    `sitename` varchar(50) NOT NULL DEFAULT 'My Site', 
                    `contact` varchar(50) NOT NULL DEFAULT 'nobody@yoursite.com', 
                    `nav_top1` set('familynews','prayers','none') NOT NULL default 'familynews', 
                    `nav_top2` set('familynews','prayers','none') NOT NULL default 'prayers', 
                    `current_version` varchar(50) NOT NULL DEFAULT 'Family Connections'
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or displaySQLError(
            'Create Config Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "INSERT INTO `fcms_config` (`sitename`, `contact`, `nav_top1`, `nav_top2`, `current_version`) VALUES ('".addslashes($cfg_sitename)."', '".addslashes($cfg_contact_email)."', ";
        if($cfg_use_news == 'YES') {
            if ($cfg_use_prayers == 'YES') { $sql .= "'familynews', 'prayers', "; } else { $sql .= "'familynews', 'none', "; }
        } else { 
            if ($cfg_use_prayers == 'YES') { $sql .= "'none', 'prayers', "; } else { $sql .= "'none', 'none', "; }
        }
        $sql .= "'Family Connections 1.5')";
        mysql_query($sql) or displaySQLError(
            'Upgrade Config Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 1.6
     * Update Config.
     */
    echo "<p><b>(1.6)</b> ".T_('Upgrading Config...');
    $sql = "SHOW COLUMNS FROM `fcms_config`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $config_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'nav_side1') { $config_fixed = true; }
        }
    }
    // nav_top1 etc have been removed since 1.9
    // need to check and make sure we don't try to update these fields
    $sql = "SHOW COLUMNS FROM `fcms_config`";
    $result2 = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    // assume that we are upgrading from 1.9 or later
    $fcms19_fixed = true;
    if (mysql_num_rows($result2) > 0) {
        while($r = mysql_fetch_array($result2)) {
            // if nav_top1 exists then we are upgrading from earlier than 1.9
            if ($r['Field'] == 'nav_top1') { $fcms19_fixed = false; }
        }
    }
    if ($config_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        if ($fcms19_fixed) {
            echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
        } else {
            mysql_query("ALTER TABLE `fcms_config` CHANGE `nav_top1` `nav_top1` TINYINT(1) NOT NULL DEFAULT '1' ") or die("</p><p style=\"color:red\">".mysql_error()."</p>");
            mysql_query("ALTER TABLE `fcms_config` CHANGE `nav_top2` `nav_top2` TINYINT(1) NOT NULL DEFAULT '2' ") or die("</p><p style=\"color:red\">".mysql_error()."</p>");
            mysql_query("ALTER TABLE `fcms_config` ADD `nav_side1` TINYINT(1) NOT NULL DEFAULT '3' AFTER `nav_top2`") or die("</p><p style=\"color:red\">".mysql_error()."</p>");
            mysql_query("ALTER TABLE `fcms_config` ADD `nav_side2` TINYINT(1) NOT NULL DEFAULT '0' AFTER `nav_side1`") or die("</p><p style=\"color:red\">".mysql_error()."</p>");
            echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
        }
    }
    /*
     * FCMS 1.7
     * Add Photo Gallery Tag.
     */
    echo "<p><b>(1.7)</b> ".T_('Adding Photo Gallery Taggin...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or displaySQLError(
        'Tables Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $tag_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_gallery_photos_tags') { $tag_fixed = true; }
        }
    }
    if ($tag_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "CREATE TABLE `fcms_gallery_photos_tags` (
                    `id` int(11) NOT NULL auto_increment, 
                    `user` int(11) NOT NULL default '0', 
                    `photo` int(11) NOT NULL default '0', 
                    PRIMARY KEY  (`id`), 
                    KEY `tag_photo_ind` (`photo`), 
                    KEY `tag_user_ind` (`user`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or displaySQLError(
            'Create Tagging Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_gallery_photos_tags` 
                ADD CONSTRAINT `fcms_gallery_photos_tags_ibfk_1` 
                FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_gallery_photos_tags_ibfk_2` 
                FOREIGN KEY (`photo`) REFERENCES `fcms_gallery_photos` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or displaySQLError(
            'Upgrade Tag Constraint Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 1.7
     * Add auto_activate and full_size_photo configs.
     */
    echo "<p><b>(1.7)</b> ".T_('Adding Auto Activation and Full Sized Photos...');
    $sql = "SHOW COLUMNS FROM `fcms_config`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $config_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'auto_activate' || $r['Field'] == 'full_size_photos') { $config_fixed = true; }
        }
    }
    if ($config_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_config` ADD `auto_activate` TINYINT( 1 ) NOT NULL DEFAULT '0'";
        mysql_query($sql) or displaySQLError(
            'Upgrade Config Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_config` ADD `full_size_photos` TINYINT( 1 ) NOT NULL DEFAULT '0'";
        mysql_query($sql) or displaySQLError(
            'Upgrade Config Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 1.7.1
     * Add activattion code.
     */
    echo "<p><b>(1.7.1)</b> ".T_('Adding Activation Code...');
    $sql = "SHOW COLUMNS FROM `fcms_users`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $config_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'activate_code') { $config_fixed = true; }
        }
    }
    if ($config_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_users` ADD `activate_code` CHAR( 13 ) NULL";
        mysql_query($sql) or displaySQLError(
            'Upgrade Users Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 1.8
     * Add date added to Calendar.
     */
    echo "<p><b>(1.8)</b> ".T_('Adding date added to Calendar...');
    $sql = "SHOW COLUMNS FROM `fcms_calendar`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $cal_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'date_added') { $cal_fixed = true; }
        }
    }
    if ($cal_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_calendar` ADD `date_added` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'";
        mysql_query($sql) or displaySQLError(
            'Upgrade Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 1.8
     * Private Messages.
     */
    echo "<p><b>(1.8)</b> ".T_('Adding Private Messages...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql) or displaySQLError(
        'Table Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $pm_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_privatemsg') { $pm_fixed = true; }
        }
    }
    if ($pm_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "CREATE TABLE `fcms_privatemsg` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `to` int(11) NOT NULL, 
                    `from` int(11) NOT NULL, 
                    `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `title` VARCHAR(50) NOT NULL DEFAULT 'PM Title', 
                    `msg` TEXT, 
                    `read` TINYINT(1) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `to_ind` (`to`), 
                    KEY `from_ind` (`from`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or displaySQLError(
            'Create PM Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_privatemsg` 
                ADD CONSTRAINT `fcms_privatemsg_ibfk_1` FOREIGN KEY (`to`) 
                REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
        mysql_query($sql) or displaySQLError(
            'Constraint Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_privatemsg` 
                ADD CONSTRAINT `fcms_privatemsg_ibfk_2` FOREIGN KEY (`from`) 
                REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
        mysql_query($sql) or displaySQLError(
            'Constraint Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 1.8
     * Change sections order, add new section.
     */
    echo "<p><b>(1.8)</b> ".T_('Changing Optional Sections Order...');
    $sql = "SHOW COLUMNS FROM `fcms_config`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $config_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'nav_side3') { $config_fixed = true; }
        }
    }
    if ($config_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        if ($fcms19_fixed) {
            echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
        } else {
            $sql = "ALTER TABLE `fcms_config` CHANGE `nav_top1` `nav_top1` TINYINT(1) NOT NULL DEFAULT '3'";
            mysql_query($sql) or displaySQLError(
                'Default Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $sql = "ALTER TABLE `fcms_config` CHANGE `nav_top2` `nav_top2` TINYINT(1) NOT NULL DEFAULT '1'";
            mysql_query($sql) or displaySQLError(
                'Default Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $sql = "ALTER TABLE `fcms_config` CHANGE `nav_side1` `nav_side1` TINYINT(1) NOT NULL DEFAULT '4'";
            mysql_query($sql) or displaySQLError(
                'Default Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $sql = "ALTER TABLE `fcms_config` CHANGE `nav_side2` `nav_side2` TINYINT(1) NOT NULL DEFAULT '5'";
            mysql_query($sql) or displaySQLError(
                'DEFAULT Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $sql = "ALTER TABLE `fcms_config` ADD `nav_side3` TINYINT(1) NOT NULL DEFAULT '2' AFTER `nav_side2`";
            mysql_query($sql) or displaySQLError(
                'Add Column Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
        }
    }
    /*
     * FCMS 1.8
     * Add login_attempts.
     */
    echo "<p><b>(1.8)</b> ".T_('Adding Login Lockout...');
    $sql = "SHOW COLUMNS FROM `fcms_users`";
    $result = mysql_query($sql) or displaySQLError(
        'Column Search Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $users_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'login_attempts' || $r['Field'] == 'locked') { $users_fixed = true; }
        }
    }
    if ($users_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_users` ADD `login_attempts` TINYINT(1) NOT NULL DEFAULT '0'";
        mysql_query($sql) or displaySQLError(
            'Add Column Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_users` ADD `locked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'";
        mysql_query($sql) or displaySQLError(
            'Add Column Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 1.9
     * Update Config.
     */
    echo "<p><b>(1.9)</b> ".T_('Upgrading Optional Section Config...');
    // 2.2 Makes this update obsolete
    echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
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


    // Set the current Version
    $sql = "UPDATE `fcms_config` SET `current_version` = 'Family Connections 1.9'";
    mysql_query($sql) or displaySQLError(
        'Version Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    echo "<p><a href=\"upgrade.php\">".T_('Continue')."</a></p>";
}
?>
