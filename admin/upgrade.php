<?php
session_start();

define('URL_PREFIX', '../');

include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');

// Check that the user is logged in
isLoggedIn('admin/');
$currentUserId = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");
include_once('../inc/admin_class.php');

$admin = new Admin($currentUserId);

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
include_once(getTheme($currentUserId, $TMPL['path']) . 'header.php');

// Latest Version
$latest_version = '2.4';

echo '
        <div class="centercontent">';

if (checkAccess($currentUserId) > 1)
{
    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 1 (Admin).').' 
                <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
}
else
{
    // Upgrade
    if (isset($_POST['upgrade']))
    {
        upgrade();
    }
    // Version Check
    else
    {
        $uptodate           = false; 
        $current_version    = getCurrentVersion();
        $ver                = file("http://www.haudenschilt.com/fcms/latest_version.php");
        $latest_release     = $ver[0];

        echo '
            <h2>'.T_('Version Check').'</h2>';

        if (
            trim(str_pad(str_replace(".", "", substr($latest_release, 18)), 4, "0")) <= 
            trim(str_pad(str_replace(".", "", substr($current_version, 18)), 4, "0"))
        )
        {
            $uptodate = true;
            echo '
            <div class="ok-alert">
                '.T_('Awesome, your installation is up to date.').'
            </div>';
        }
        else
        {
            echo '
            <div class="error-alert">
                '.T_('Bummer!, your installation is out of date.').'  
                <a href="http://www.familycms.com/">
                    '.T_('Download latest version.').'
                </a>
            </div>';
        }

        echo '
            <p><b>'.T_('Your Version').':</b> &nbsp;&nbsp;&nbsp;'.$current_version.'</p>
            <p><b>'.T_('Latest Version').':</b> &nbsp;'.$latest_release.'</p>
            <form method="post" action="upgrade.php">
                <div>
                    <input class="sub1" type="submit" name="upgrade" value="'.T_('Upgrade').'"';
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


function upgrade ()
{
    global $cfg_mysql_db, $cfg_sitename, $cfg_contact_email, $cfg_use_news, $cfg_use_prayers, $latest_version;

    echo '
            <h2>'.sprintf(T_('Upgrading to %s'), $latest_version).'</h2>
            <p>'.T_('Upgrade is in process...').'</p>';
    /*
     * FCMS 2.0
     * User Settings.
     */
    echo "<p><b>(2.0)</b> ".T_('Adding User Settings...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Create Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_user_settings` 
                ADD CONSTRAINT `fcms_user_stgs_ibfk_1` FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
        if (!mysql_query($sql))
        {
            displaySQLError('Constraint Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $result = mysql_query("SELECT * FROM `fcms_users`");
        if (!$result)
        {
            displaySQLError('Users Error', __FILE__.' ['.__LINE__.']', '', mysql_error());
            return;
        }
        while ($r = mysql_fetch_array($result)) {
            if ($r['showavatar'] == 'YES') {
                $showavatar = '1';
            } else {
                $showavatar = '0';
            }
            $sql = "INSERT INTO `fcms_user_settings` (`user`, `theme`, `boardsort`, `showavatar`, `displayname`, `frontpage`, `timezone`, `dst`)
                    VALUES (
                    '" . $r['id'] . "', 
                    '" . $r['theme'] . "', 
                    '" . $r['boardsort'] . "', 
                    '$showavatar', 
                    '" . $r['displayname'] . "', 
                    '" . $r['frontpage'] . "',
                    '" . $r['timezone'] . "', 
                    '" . $r['dst'] . "')";
            if (!mysql_query($sql))
            {
                displaySQLError('Transferring User Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return;
            }
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
    if (!mysql_query($sql))
    {
        displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    /*
     * FCMS 2.1
     * Site Off
     */
    echo "<p><b>(2.1)</b> ".T_('Adding Ability to Turn Site Off...');
    $sql = "SHOW COLUMNS FROM `fcms_config`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.1
     * Alerts
     */
    echo "<p><b>(2.1)</b> ".T_('Adding Alerts...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Create Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.1
     * Polls
     */
    echo "<p><b>(2.1)</b> ".T_('Adding Historical Poll Data...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Rename Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_poll_votes` ADD COLUMN (`poll_id` INT(11) NOT NULL DEFAULT '0')";
        if (!mysql_query($sql))
        {
            displaySQLError('Add Column Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.2
     * Advanced Uploader
     */
    echo "<p><b>(2.2)</b> ".T_('Adding Advanced Uploader...');
    $sql = "SHOW COLUMNS FROM `fcms_user_settings`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Add Column Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.2
     * Navigation
     */
    echo "<p><b>(2.2)</b> ".T_('Adding Navigation...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Create Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
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
        if (!mysql_query($sql))
        {
            displaySQLError('INSERT Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.2
     * User Language
     */
    echo "<p><b>(2.2)</b> ".T_('Adding User Language...');
    $sql = "SHOW COLUMNS FROM `fcms_user_settings`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Add Column Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Category Table.
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Category Table...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Create Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        // Drop FK
        $sql = "ALTER TABLE `$cfg_mysql_db`.`fcms_gallery_photos` DROP FOREIGN KEY `fcms_gallery_photos_ibfk_2`";
        @mysql_query($sql);
        $sql = "SELECT p.`id` AS pid, p.`category` AS cid, c.`name`, c.`user` 
                FROM `fcms_gallery_category` AS c, `fcms_gallery_photos` AS p 
                WHERE p.`category` = c.`id`";
        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
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
                if (!mysql_query($sql))
                {
                    displaySQLError('Transfer Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                    return;
                }
                $cat_id = mysql_insert_id();
                $last_category = $r['name'];
            }
            // update photo to point to new category table id
            $sql = "UPDATE `fcms_gallery_photos` SET `category` = '$cat_id' WHERE `id` = '".$r['pid']."'";
            if (!mysql_query($sql))
            {
                displaySQLError('Create Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return;
            }
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Calendar Repeating.
     */
    echo "<p><b>(2.3)</b> ".T_('Upgrading Calendar (Repeating/Categories)...');
    $sql = "SHOW COLUMNS FROM `fcms_calendar`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        // Add category field
        $sql = "ALTER TABLE `fcms_calendar` ADD `category` INT(11) NOT NULL DEFAULT '0'";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        // Create Anniversary, Birthday, and Holiday categories
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`, `color`)
                VALUES  ('', 'calendar', 1, NOW(), 'none')";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $none = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`, `color`)
                VALUES  ('".T_('Anniversary')."', 'calendar', 1, NOW(), 'green')";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $ann = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`, `color`)
                VALUES ('".T_('Birthday')."', 'calendar', 1, NOW(), 'red')";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $bday = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`, `color`)
                VALUES ('".T_('Holiday')."', 'calendar', 1, NOW(), 'violet')";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $hol = mysql_insert_id();
        $sql = "SELECT * FROM `fcms_calendar`";
        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Calenda Entries Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
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
            if (!mysql_query($sql))
            {
                displaySQLError('Update Calendar Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return;
            }
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Family Tree
     */
    echo "<p><b>(2.3)</b> Adding Family Tree...";
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Search Tables Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Create Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                VALUES ('tree', 4, 8, 0)";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Sex
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Sex...');
    $sql = "SHOW COLUMNS FROM `fcms_users`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Error Logging
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Error Logging...');
    $sql = "SHOW COLUMNS FROM `fcms_config`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Recipe Categories
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Recipe Categories...');
    $sql = "SHOW COLUMNS FROM `fcms_recipes`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $categories['Appetizer'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Breakfast', 'recipe', 1, NOW())";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $categories['Breakfast'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Dessert', 'recipe', 1, NOW())";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $categories['Dessert'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Entree (Meat)', 'recipe', 1, NOW())";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $categories['Entree (Meat)'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Entree (Seafood)', 'recipe', 1, NOW())";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $categories['Entree (Seafood)'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Entree (Vegetarian)', 'recipe', 1, NOW())";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $categories['Entree (Vegetarian)'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Salad', 'recipe', 1, NOW())";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $categories['Salad'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Side Dish', 'recipe', 1, NOW())";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $categories['Side Dish'] = mysql_insert_id();
        $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`)
                VALUES  ('Soup', 'recipe', 1, NOW())";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $categories['Soup'] = mysql_insert_id();

        // Update existing recipe categories to new categories
        $sql = "SELECT `id`, `category` FROM `fcms_recipes`";
        $result = mysql_query($sql);
        if (!mysql_query($sql))
        {
            displaySQLError('Recipe Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        while ($r = mysql_fetch_array($result)) {
            $sql = "UPDATE `fcms_recipes`
                    SET `category` = ".$categories[$r['category']]." 
                    WHERE `id` = ".$r['id'];
            if (!mysql_query($sql))
            {
                displaySQLError('Update Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return;
            }
        }

        // Modify category column
        $sql = "ALTER TABLE `fcms_recipes` MODIFY `category` INT(11) NOT NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Calendar Time
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Calendar Time...');
    $sql = "SHOW COLUMNS FROM `fcms_calendar`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_calendar` ADD `time_end` TIME NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Recipe ingredients/directions
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Recipe Info...');
    $sql = "SHOW COLUMNS FROM `fcms_recipes`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_recipes` ADD `ingredients` TEXT NOT NULL AFTER `thumbnail`";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_recipes` CHANGE `recipe` `directions` TEXT NOT NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Recipe comments
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Recipe Comments...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Create Table Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Gravatar email
     */
    echo "<p><b>(2.3)</b> ".T_('Adding Gravatar...');
    $sql = "SHOW COLUMNS FROM `fcms_users`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Awards
     */
    echo "<p><b>(2.3)</b> ".T_('Updating Awards...');
    $sql = "SHOW COLUMNS FROM `fcms_user_awards`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Truncate Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_user_awards` CHANGE `type` `award` VARCHAR(100) NOT NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_user_awards` CHANGE `value` `item_id` INT(11) NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_user_awards` ADD `month` INT(6) NOT NULL AFTER `award`";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_user_awards` ADD `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `month`";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.3
     * Documents mimetype
     */
    echo "<p><b>(2.3)</b> ".T_('Adding mime types...');
    $sql = "SHOW COLUMNS FROM `fcms_documents`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
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
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.4
     * Advanced Tagging
     */
    echo "<p><b>(2.4)</b> ".T_('Adding advanced tagging...');
    $sql = "SHOW COLUMNS FROM `fcms_user_settings`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    $tagging_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'advanced_tagging') { $tagging_fixed = true; }
        }
    }
    if ($tagging_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_user_settings` ADD `advanced_tagging` TINYINT(1) NOT NULL DEFAULT '1' AFTER `advanced_upload`";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.4
     * Middle, maiden name and bio
     */
    echo "<p><b>(2.4)</b> ".T_('Middle, maiden name and bio...');
    $sql = "SHOW COLUMNS FROM `fcms_users`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    $tagging_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'mname') { $tagging_fixed = true; }
        }
    }
    if ($tagging_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_users` ADD `mname` VARCHAR(25) NULL AFTER `fname`";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_users` ADD `maiden` VARCHAR(25) NULL AFTER `lname`";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_users` ADD `bio` VARCHAR(200) NULL AFTER `gravatar`";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_users` ADD `death` DATE NULL AFTER `birthday`";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.4
     * Seperate content and member data
     */
    echo "<p><b>(2.4)</b> ".T_('Moving member data...');

    $data_fixed = false;

    if (file_exists('../uploads/photos/'))
    {
        $data_fixed = true;
    }

    if ($data_fixed)
    {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    }
    else
    {
        mkdir('../uploads/');
        mkdir('../uploads/avatar/');
        mkdir('../uploads/documents/');
        mkdir('../uploads/photos/');
        mkdir('../uploads/upimages/');

        $directories = array(
            'avatar'    => '../gallery/avatar/', 
            'documents' => '../gallery/documents/', 
            'photos'    => '../gallery/photos/', 
            'upimages'  => '../gallery/upimages/'
        );

        $count = array(
            'avatar'    => 0, 
            'documents' => 0, 
            'photos'    => 0, 
            'upimages'  => 0
        );

        foreach ($directories as $key => $dir)
        {
            if ($dh = opendir($dir))
            {
                while (($file = readdir($dh)) !== false)
                {
                    // Skip directories that start with a period
                    if ($file[0] === '.')
                    {
                        continue;
                    }

                    // Directories
                    if (filetype($dir.$file) == "dir")
                    {
                        mkdir("../uploads/$key/$file");

                        if ($sdh = opendir($dir.$file))
                        {
                            while (($f = readdir($sdh)) !== false)
                            {
                                // Skip directories starting with period
                                if ($f[0] === '.')
                                {
                                    continue;
                                }

                                // Skip directories
                                if (filetype("$dir$file/$f") == "dir")
                                {
                                    echo '<div class="error-alert">'.sprintf(T_('Too many directory levels [%s].'), "$dir$file/$f").'</div>';
                                }

                                if (!copy("$dir$file/$f", "../uploads/$key/$file/$f"))
                                {
                                    echo '<div class="error-alert">';
                                    echo sprintf(T_('Could not transfer file [%s] to location [%s].'), "$dir$file/$f", "../uploads/$key/$file/$f");
                                    echo '</div>';
                                    return;
                                }
                                $count[$key]++;
                            }
                            closedir($sdh);
                        }
                    }
                    // File
                    else
                    {
                        if (!copy($dir.$file, "../uploads/$key/$file"))
                        {
                            echo '<div class="error-alert">';
                            echo sprintf(T_('Could not transfer file [%s] to location [%s].'), "$dir$file", "../uploads/$key/$file");
                            echo '</div>';
                            return;
                        }
                        $count[$key]++;
                    }
                }
                closedir($dh);
            }
        }

        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";

        echo '
            <div class="info-alert">
                <p>'.T_('Your member data (photos, documents, avatars and uploaded images) has been moved.').'</p>
                <p>'.T_('You should verify that these files were moved successfully.').'</p>
                <p>'.T_('After verifying everything is ok, you can delete the following directories:').'</p>
                <ul>
                    <li>gallery/avatar/</li> 
                    <li>gallery/documents/</li>
                    <li>gallery/photos/</li>
                    <li>gallery/upimages/</li>
                </ul>
            </div>';
    }
    /*
     * FCMS 2.4
     * Where Is Everyone
     */
    echo "<p><b>(2.4)</b> ".T_('Where is everyone...');
    $sql = "SHOW COLUMNS FROM `fcms_config`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    $whereiseveryone_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'fs_client_id') { $whereiseveryone_fixed = true; }
        }
    }
    if ($whereiseveryone_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_config` ADD `fs_client_id` CHAR(50) NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_config` ADD `fs_client_secret` CHAR(50) NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_config` ADD `fs_callback_url` VARCHAR(255) NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_user_settings` ADD `fs_user_id` INT(11) NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_user_settings` ADD `fs_access_token` CHAR(50) NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                VALUES ('whereiseveryone', 4, 9, 0),
                ('admin_whereiseveryone', 6, 7, 0)";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.4
     * Import blogging platforms
     */
    echo "<p><b>(2.4)</b> ".T_('Import blogging data...');
    $sql = "SHOW COLUMNS FROM `fcms_user_settings`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Column Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    $blog_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'blogger') { $blog_fixed = true; }
        }
    }
    if ($blog_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_user_settings` ADD `blogger` VARCHAR(255) NULL";
        if (!mysql_query($sql))
		{
			displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_user_settings` ADD `tumblr` VARCHAR(255) NULL";
        if (!mysql_query($sql))
		{
			displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_user_settings` ADD `wordpress` VARCHAR(255) NULL";
        if (!mysql_query($sql))
		{
			displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_user_settings` ADD `posterous` VARCHAR(255) NULL";
        if (!mysql_query($sql))
		{
			displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_news` ADD `external_type` VARCHAR(20) NULL";
        if (!mysql_query($sql))
		{
			displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_news` ADD `external_id` VARCHAR(255) NULL";
        if (!mysql_query($sql))
		{
			displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "ALTER TABLE `fcms_config` ADD `external_news_date` DATETIME NULL";
        if (!mysql_query($sql))
		{
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.4
     * AJAX Chat
     */
    echo "<p><b>(2.4)</b> ".T_('AJAX Chat...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    $chat_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r[0] == 'fcms_chat_online') { $chat_fixed = true; }
        }
    }
    if ($chat_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "DROP TABLE IF EXISTS fcms_chat_online";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "CREATE TABLE fcms_chat_online (
                    userID INT(11) NOT NULL,
                    userName VARCHAR(64) NOT NULL,
                    userRole INT(1) NOT NULL,
                    channel INT(11) NOT NULL,
                    dateTime DATETIME NOT NULL,
                    ip VARBINARY(16) NOT NULL
                ) DEFAULT CHARSET=utf8 COLLATE=utf8_bin";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "DROP TABLE IF EXISTS fcms_chat_messages";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $sql = "CREATE TABLE fcms_chat_messages (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    userID INT(11) NOT NULL,
                    userName VARCHAR(64) NOT NULL,
                    userRole INT(1) NOT NULL,
                    channel INT(11) NOT NULL,
                    dateTime DATETIME NOT NULL,
                    ip VARBINARY(16) NOT NULL,
                    text TEXT,
                    PRIMARY KEY (id)
                ) DEFAULT CHARSET=utf8 COLLATE=utf8_bin";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
    }
    /*
     * FCMS 2.4
     * Add missing navigation
     */
    echo "<p><b>(2.4)</b> ".T_('Update Navigation...');
    $sql = "SELECT * FROM `fcms_navigation` WHERE `link` = 'home'";
    $result = mysql_query($sql);
    if (!$result)
    {
	    displaySQLError('Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    $nav_fixed = false;
    if (mysql_num_rows($result) > 0) {
        $nav_fixed = true;
    }
    if ($nav_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                VALUES ('home', 1, 1, 1),
                    ('members', 5, 1, 1),
                    ('contact', 5, 2, 1),
                    ('help', 5, 3, 1),
                    ('admin_upgrade', 6, 1, 1),
                    ('admin_configuration', 6, 2, 1),
                    ('admin_members', 6, 3, 1),
                    ('admin_photogallery', 6, 4, 1),
                    ('admin_polls', 6, 5, 1),
                    ('admin_awards', 6, 6, 1)";
        if (!mysql_query($sql))
		{
			displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.4
     * Move navigation
     */
    echo "<p><b>(2.4)</b> ".T_('Move Navigation...');
    $sql = "UPDATE `fcms_navigation` SET `col` = 3 WHERE `link` = 'familynews'";
    if (!mysql_query($sql))
    {
        displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    $sql = "UPDATE `fcms_navigation` SET `col` = 3 WHERE `link` = 'prayers'";
    if (!mysql_query($sql))
    {
        displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    $sql = "DELETE FROM `fcms_navigation` WHERE `link` = 'chat'";
    if (!mysql_query($sql))
    {
        displaySQLError('Delete Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    /*
     * FCMS 2.4
     * Add turn off registration
     */
    echo "<p><b>(2.4)</b> ".T_('Add ability to Turn off registration...');
    $sql = "SHOW COLUMNS FROM `fcms_config`";
    $result = mysql_query($sql);
    if (!$result)
    {
	    displaySQLError('Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    $register_fixed = false;
    if (mysql_num_rows($result) > 0) {
        while($r = mysql_fetch_array($result)) {
            if ($r['Field'] == 'registration') { $register_fixed = true; }
        }
    }
    if ($register_fixed) {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    } else {
        $sql = "ALTER TABLE `fcms_config` ADD `registration` TINYINT(1) NOT NULL DEFAULT '1'";
        if (!mysql_query($sql))
		{
			displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.4
     * Add invitations
     */
    echo "<p><b>(2.4)</b> ".T_('Add calendar invitations...');
    $sql = "SHOW COLUMNS FROM `fcms_calendar`";
    $result = mysql_query($sql);
    if (!$result)
    {
	    displaySQLError('Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    $invite_fixed = false;
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r['Field'] == 'invite') { $invite_fixed = true; }
        }
    }
    if ($invite_fixed)
    {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    }
    else
    {
        $sql = "ALTER TABLE `fcms_calendar` ADD `invite` TINYINT(1) NOT NULL DEFAULT '0'";
        if (!mysql_query($sql))
		{
			displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }
    /*
     * FCMS 2.4
     * Add invitations
     */
    echo "<p><b>(2.4)</b> ".T_('Add invitations...');
    $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    $invite_fixed = false;
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r[0] == 'fcms_invitation') { $invite_fixed = true; }
        }
    }
    if ($invite_fixed)
    {
        echo "<span style=\"color:green\">".T_('No changes needed')."</span></p>";
    }
    else
    {
        $sql = "CREATE TABLE `fcms_invitation` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT,
                    `event_id` INT(25) NOT NULL DEFAULT '0',
                    `user` INT(25) NOT NULL DEFAULT '0',
                    `email` VARCHAR(50) NULL, 
                    `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                    `updated` DATETIME DEFAULT NULL,
                    `attending` TINYINT(1) DEFAULT NULL,
                    `code` CHAR(13) DEFAULT NULL,
                    `response` TEXT DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `event_id` (`event_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        echo "<span style=\"color:green\"><b>".T_('Complete')."</b></span></p>";
    }


    // Set the current Version
    $sql = "UPDATE `fcms_config` SET `current_version` = 'Family Connections $latest_version'";
    if (!mysql_query($sql))
    {
        displaySQLError('Version Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    echo "<p style=\"color:green\"><b>".T_('Upgrade is finished.')."</b></p>";
}
