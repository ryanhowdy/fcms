<?php
/**
 * disableSite 
 * 
 * Creates a 'siteoff' file in the inc directory.
 * If this file exists users will be unable to view site for a period of time.
 * 
 * @return void
 */
function disableSite ()
{
    $str = '<?php $upgrading = '.time().'; ?>';
    $file = @fopen(INC.'siteoff', 'w');
    if ($file === false)
    {
        return false;
    }

    $write = @fwrite($file, $str);
    if ($write === false)
    {
        return false;
    }

    fclose($file);

    return true;
}

/**
 * enableSite 
 * 
 * Removes 'siteoff' file from inc directory.
 * 
 * @return boolean
 */
function enableSite ()
{
    if (file_exists(INC.'siteoff'))
    {
        if (!unlink(INC.'siteoff'))
        {
            return false;
        }
    }

    return true;
}

/**
 * updateCurrentVersion 
 * 
 * @param string  $version 
 * 
 * @return boolean
 */
function updateCurrentVersion ($version)
{
    $sql = "UPDATE `fcms_config` 
            SET `value` = '$version'
            WHERE `name` = 'current_version'";
    if (!mysql_query($sql))
    {
        displaySQLError('Version Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }

    return true;
}

/**
 * downloadLatestVersion 
 * 
 * @return void
 */
function downloadLatestVersion ()
{
    // Have we downloaded the latest file already?
    if (file_exists(INC.'latest.zip'))
    {
        $modified = filemtime(INC.'latest.zip');

        // Skip the download if the file has been downloaded already today
        if (date('Ymd') == date('Ymd', $modified))
        {
            return;
        }
    }

    $ch = curl_init(LATEST_FILE_URL);
    $fh = fopen(INC.'latest.zip', 'w');
    curl_setopt($ch, CURLOPT_FILE, $fh);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * unzipFile 
 * 
 * @return boolean
 */
function unzipFile ()
{
    $zipFile = INC.'latest.zip';

    $za = new ZipArchive(); 

    if (!$za->open($zipFile))
    {
        echo '<div class="error-alert">'.T_('Could not open zip file.').'</div>';
        return false;
    }

    if (!$za->extractTo(INC.'upgrade'))
    {
        echo '<div class="error-alert">'.T_('Could extract zip file.').'</div>';
        return false;
    }

    $za->close();

    return true;
}

/**
 * install 
 * 
 * Copies newly downloaded files from temp.
 * 
 * @return boolean
 */
function install ()
{
    $dir = INC.'upgrade/';

    if (!is_dir($dir))
    {
        echo '<div class="error-alert">'.T_('Could not find upgrade directory.').'</div>';
        return false;
    }

    $dh = @opendir($dir);
    if ($dh === false)
    {
        echo '<div class="error-alert">'.T_('Could not open upgrade directory.').'</div>';
        return false;
    }

    // Get container file (FCMS 2.X)
    while (($container = readdir($dh)) !== false)
    {
        // Skip directories that start with a period
        if ($container[0] === '.')
        {
            continue;
        }

        if (!is_dir($dir.$container))
        {
            echo '<div class="error-alert">'.T_('Could not find new upgrade files.').'</div>';
            return false;
        }

        break;
    }

    if (!copy_files(INC."upgrade/$container", ROOT))
    {
        // error alrady displayed by copy_files
        return false;
    }

    // Everything copied over fine, delete the upgrade directory
    deleteDirectory(INC."upgrade");

    return true;
}

/**
 * copy_files 
 * 
 * @param string $from 
 * @param string $to 
 * 
 * @return void
 */
function copy_files ($from, $to)
{
    // Add trailing slashes to directories
    if (substr($from, -1) !== '/')
    {
        $from .= '/';
    }
    if (substr($to, -1) !== '/')
    {
        $to .= '/';
    }

    if (!is_dir($from))
    {
        echo '<div class="error-alert">'.sprintf(T_('Could not find origin: %s.'), $from).'</div>';
        return false;
    }

    if (!is_dir($to))
    {
        echo '<div class="error-alert">'.sprintf(T_('Could not find destination: %s.'), $to).'</div>';
        return false;
    }

    $dh = @opendir($from);
    if ($dh === false)
    {
        echo '<div class="error-alert">'.sprintf(T_('Could not open directory: %s.'), $from).'</div>';
        return false;
    }

    while (($file = readdir($dh)) !== false)
    {
        // Skip directories that start with a period
        if ($file[0] === '.')
        {
            continue;
        }

        // Directory
        if (filetype($from.$file) == "dir")
        {
            if (!copy_files($from.$file, $to.$file))
            {
                // error alrady displayed by copy_files
                return false;
            }

        }
        // File
        else
        {
            if (!copy($from.$file, $to.$file))
            {
                echo '<div class="error-alert">'.sprintf(T_('Could not copy file: %s.'), $from.$file).'</div>';
                return false;
            }
        }
    }

    return true;
}

/**
 * deleteDirectory 
 * 
 * Recursively deletes a directory and anything in it.
 * 
 * @param string $dir 
 * 
 * @return void
 */
function deleteDirectory ($dir)
{
    $files = scandir($dir);

    if ($files === false)
    {
        return false;
    }

    // remove . and .. if they exist
    if ($files[0] == '.') { array_shift($files); }
    if ($files[0] == '..') { array_shift($files); }
   
    foreach ($files as $file)
    {
        $file = $dir . '/' . $file;

        if (is_dir($file))
        {
            deleteDirectory($file);
        }
        else
        {
            unlink($file);
        }
    }

    if (is_dir($dir))
    {
        rmdir($dir);
    } 

    return true;
}

/**
 * upgrade 
 * 
 * Upgrade database from 2.5 to current version.
 * 
 * @return boolean
 */
function upgrade ()
{
    if (!upgrade250())
    {
        return false;
    }

    if (!upgrade260())
    {
        return false;
    }

    if (!upgrade270())
    {
        return false;
    }

    return true;
}

/**
 * upgrade250 
 * 
 * Upgrade database to version 2.5.
 * 
 * @return boolean
 */
function upgrade250 ()
{
    global $cfg_mysql_db;

    // Status updates
    $status_fixed = false;

    $sql    = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r[0] == 'fcms_status') { $status_fixed = true; }
        }
    }

    if (!$status_fixed)
    {
        $sql = "CREATE TABLE `fcms_status` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT,
                    `user` INT(25) NOT NULL DEFAULT '0',
                    `status` TEXT DEFAULT NULL,
                    `parent` INT(25) NOT NULL DEFAULT '0',
                    `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                    `updated` DATETIME DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    CONSTRAINT FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $sql = "ALTER TABLE `fcms_config` ADD `fb_app_id` VARCHAR(50) NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $sql = "ALTER TABLE `fcms_config` ADD `fb_secret` VARCHAR(50) NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $sql = "ALTER TABLE `fcms_user_settings` ADD `fb_access_token` VARCHAR(255) NULL";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }
        $adminOrder = getNextAdminNavigationOrder();
        $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                VALUES ('admin_facebook', 6, $adminOrder, 0)";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }
    }

    return true;
}

/**
 * upgrade260 
 * 
 * Upgrade database to version 2.6.
 * 
 * @return boolean
 */
function upgrade260 ()
{
    global $cfg_mysql_db;

    // Family News created/updated
    $fnews_fixed = false;

    $sql = "SHOW COLUMNS FROM `fcms_news`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r['Field'] == 'created')
            {
                $fnews_fixed = true;
            }
        }
    }

    if (!$fnews_fixed)
    {
        $sql = "ALTER TABLE `fcms_news`
                CHANGE `date` `updated` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                ADD COLUMN `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `user`";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }
    }

    // Change log
    $change_fixed = false;

    $sql    = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r[0] == 'fcms_changelog') { $change_fixed = true; }
        }
    }

    if (!$change_fixed)
    {
        $sql = "CREATE TABLE `fcms_changelog` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT,
                    `user` INT(25) NOT NULL DEFAULT '0',
                    `table` VARCHAR(50) NOT NULL,
                    `column` VARCHAR(50) NOT NULL,
                    `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                    PRIMARY KEY (`id`),
                    CONSTRAINT FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }
    }

    // Addressbook
    $book_fixed = false;

    $sql = "SHOW COLUMNS FROM `fcms_address`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r['Field'] == 'created')
            {
                $book_fixed = true;
            }
        }
    }

    if (!$book_fixed)
    {
        $sql = "ALTER TABLE `fcms_address`
                CHANGE `entered_by` `created_id` INT(11) NOT NULL DEFAULT '0',
                ADD COLUMN `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `created_id`,
                ADD COLUMN `updated_id` INT(11) NOT NULL DEFAULT '0' AFTER `updated`";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $sql = "UPDATE `fcms_address`
                SET `updated_id` = `created_id`
                WHERE `updated_id` = 0";
        if (!mysql_query($sql))
		{
			displaySQLError('Updated Id Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $sql = "UPDATE `fcms_address`
                SET `created` = `updated`
                WHERE `created` = '0000-00-00 00:00:00'";
        if (!mysql_query($sql))
		{
			displaySQLError('Created Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }
    }

    // YouTube
    $youtube_fixed = false;

    $sql = "SHOW COLUMNS FROM `fcms_config`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r['Field'] == 'youtube_key')
            {
                $youtube_fixed = true;
            }
        }
    }

    $sql = "SELECT `value` FROM `fcms_config` WHERE `name` = 'youtube_key'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        $youtube_fixed = true;
    }

    if (!$youtube_fixed)
    {
        $sql = "ALTER TABLE `fcms_config`
                ADD COLUMN `youtube_key` VARCHAR(255) NULL";
        if (!mysql_query($sql))
		{
			displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $sql = "ALTER TABLE `fcms_user_settings`
                ADD COLUMN `youtube_session_token` VARCHAR(255) NULL";
        if (!mysql_query($sql))
		{
			displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $adminOrder = getNextAdminNavigationOrder();
        $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                VALUES ('admin_youtube', 6, $adminOrder, 1)";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }
    }

    // Config
    $cfg_fixed = false;

    $sql = "SHOW COLUMNS FROM `fcms_config`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r['Field'] == 'name')
            {
                $cfg_fixed = true;
            }
        }
    }

    if (!$cfg_fixed)
    {
        // Create new config
        $sql = "CREATE TABLE `fcms_config2` (
                    `name` VARCHAR(50) NOT NULL,
                    `value` VARCHAR(255) NULL
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die($sql . '<br/>' . mysql_error());

        // Get current config data
        $sql = "SELECT *
                FROM `fcms_config`";

        $result = mysql_query($sql) or die($sql . '<br/>' . mysql_error());
        $config = mysql_fetch_assoc($result);

        // Insert current config into new table
        $sql = "INSERT INTO `fcms_config2` (`name`, `value`)
                VALUES
                    ('sitename', '".cleanInput($config['sitename'])."'),
                    ('contact', '".cleanInput($config['contact'])."'),
                    ('current_version', '".cleanInput($config['current_version'])."'),
                    ('auto_activate', '".cleanInput($config['auto_activate'])."'),
                    ('registration', '".cleanInput($config['registration'])."'), 
                    ('full_size_photos', '".cleanInput($config['full_size_photos'])."'),
                    ('site_off', '".cleanInput($config['site_off'])."'),
                    ('log_errors', '".cleanInput($config['log_errors'])."'),
                    ('fs_client_id', '".cleanInput($config['fs_client_id'])."'),
                    ('fs_client_secret', '".cleanInput($config['fs_client_secret'])."'), 
                    ('fs_callback_url', '".cleanInput($config['fs_callback_url'])."'),
                    ('external_news_date', '".cleanInput($config['external_news_date'])."'),
                    ('fb_app_id', '".cleanInput($config['fb_app_id'])."'),
                    ('fb_secret', '".cleanInput($config['fb_secret'])."'),
                    ('youtube_key', '".cleanInput($config['youtube_key'])."')";
        mysql_query($sql) or die($sql . '<br/>' . mysql_error());

        // Delete current config
        $sql = "DROP TABLE `fcms_config`";
        mysql_query($sql) or die($sql . '<br/>' . mysql_error());

        // Rename new table to current
        $sql = "RENAME TABLE `fcms_config2` TO `fcms_config`";
        mysql_query($sql) or die($sql . '<br/>' . mysql_error());
    }

    // Schedule
    $schedule_fixed = false;

    $sql    = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r[0] == 'fcms_schedule') { $schedule_fixed = true; }
        }
    }

    if (!$schedule_fixed)
    {
        $sql = "CREATE TABLE `fcms_schedule` (
                    `id`        INT(25) NOT NULL AUTO_INCREMENT,
                    `type`      VARCHAR(50) NOT NULL DEFAULT 'familynews',
                    `repeat`    VARCHAR(50) NOT NULL DEFAULT 'hourly',
                    `lastrun`   DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                    `status`    TINYINT(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $sql = "INSERT INTO `fcms_schedule` (`type`, `repeat`)
                VALUES 
                    ('familynews', 'hourly'),
                    ('youtube', 'hourly')";
        if (!mysql_query($sql))
        {
            displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $sql = "INSERT INTO `fcms_config` (`name`, `value`)
                VALUES ('running_job', '0')";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $adminOrder = getNextAdminNavigationOrder();
        $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                VALUES ('admin_scheduler', 6, $adminOrder, 1)";
        if (!mysql_query($sql))
        {
            displaySQLError('Alter Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }
    }

    // Video Gallery
    $video_fixed = false;

    $sql    = "SHOW TABLES FROM `$cfg_mysql_db`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r[0] == 'fcms_video') { $video_fixed = true; }
        }
    }

    if (!$video_fixed)
    {
        $sql = "CREATE TABLE `fcms_video` (
                    `id`                INT(25) NOT NULL AUTO_INCREMENT,
                    `source_id`         VARCHAR(255) NOT NULL,
                    `title`             VARCHAR(255) NOT NULL DEFAULT 'untitled',
                    `description`       VARCHAR(255) NULL,
                    `duration`          INT(25) NULL,
                    `source`            VARCHAR(50) NULL,
                    `height`            INT(4) NOT NULL DEFAULT '420',
                    `width`             INT(4) NOT NULL DEFAULT '780',
                    `active`            TINYINT(1) NOT NULL DEFAULT '1',
                    `created`           DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                    `created_id`        INT(25) NOT NULL,
                    `updated`           DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                    `updated_id`        INT(25) NOT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $order = getNextShareNavigationOrder();
        $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                VALUES ('videogallery', 4, $order, 1)";
        if (!mysql_query($sql))
        {
            displaySQLError('INSERT Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $sql = "CREATE TABLE `fcms_video_comment` (
                    `id`            INT(25) NOT NULL AUTO_INCREMENT,
                    `video_id`      INT(25) NOT NULL,
                    `comment`       TEXT NOT NULL,
                    `created`       DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                    `created_id`    INT(25) NOT NULL,
                    `updated`       DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                    `updated_id`    INT(25) NOT NULL,
                    PRIMARY KEY (`id`),
                    CONSTRAINT FOREIGN KEY (`video_id`) REFERENCES `fcms_video` (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }
    }

    return true;
}

/**
 * upgrade270
 * 
 * Upgrade database to version 2.7.
 * 
 * @return boolean
 */
function upgrade270 ()
{
    global $cfg_mysql_db;

    // Country
    $country_fixed = false;

    $sql = "SHOW COLUMNS FROM `fcms_address`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r['Field'] == 'country')
            {
                $country_fixed = true;
            }
        }
    }

    if (!$country_fixed)
    {
        $sql = "ALTER TABLE `fcms_address`
                ADD COLUMN `country` CHAR(2) DEFAULT NULL AFTER `user`";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $sql = "INSERT INTO `fcms_config` (`name`, `value`)
                VALUES ('country', 'US')";
        if (!mysql_query($sql))
		{
			displaySQLError('Insert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }
    }

    // Birthday and date of death
    $dob_dod_fixed = false;

    $sql = "SHOW COLUMNS FROM `fcms_users`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Table Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        while($r = mysql_fetch_array($result))
        {
            if ($r['Field'] == 'dob_year')
            {
                $dob_dod_fixed = true;
            }
        }
    }

    if (!$dob_dod_fixed)
    {
        $sql = "ALTER TABLE `fcms_users`
                ADD COLUMN `dob_year` CHAR(4) AFTER `birthday`,
                ADD COLUMN `dob_month` CHAR(2) AFTER `dob_year`,
                ADD COLUMN `dob_day` CHAR(2) AFTER `dob_month`,
                ADD COLUMN `dod_year` CHAR(4) AFTER `dob_day`,
                ADD COLUMN `dod_month` CHAR(2) AFTER `dod_year`,
                ADD COLUMN `dod_day` CHAR(2) AFTER `dod_month`";
        if (!mysql_query($sql))
		{
			displaySQLError('Create Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        $sql = "SELECT `id`, `birthday`, `death`
                FROM `fcms_users`";

        $result = mysql_query($sql);
        if (!$result)
		{
			displaySQLError('Select Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }

        if (mysql_num_rows($result) <= 0)
        {
            echo '<p>'.T_('No user birthday\'s found.').'</p>';
            return false;
        }

        while ($r = mysql_fetch_assoc($result))
        {
            $id = $r['id'];

            $bYear  = substr($r['birthday'], 0, 4);
            $bMonth = substr($r['birthday'], 5, 2);
            $bDay   = substr($r['birthday'], 8, 2);

            $dYear  = substr($r['death'], 0, 4);
            $dMonth = substr($r['death'], 5, 2);
            $dDay   = substr($r['death'], 8, 2);

            $sql = "UPDATE `fcms_users`
                    SET `dob_year`  = '$bYear',
                        `dob_month` = '$bMonth',
                        `dob_day`   = '$bDay',
                        `dod_year`  = '$dYear',
                        `dod_month` = '$dMonth',
                        `dod_day`   = '$dDay'
                    WHERE `id` = '$id'";
            if (!mysql_query($sql))
            {
                displaySQLError('User DOB/DOD Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return false;
            }
        }

        $sql = "ALTER TABLE `fcms_users`
                DROP COLUMN `birthday`,
                DROP COLUMN `death`";
        if (!mysql_query($sql))
		{
			displaySQLError('Drop Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }
    }

    return true;
}
