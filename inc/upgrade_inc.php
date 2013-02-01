<?php
/**
 * Upgrade
 * 
 * PHP version 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2012 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */

/**
 * Upgrade 
 * 
 * @category  FCMS
 * @package   Family_Connections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2012 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
class Upgrade
{
    public $fcmsError;
    public $fcmsDatabase;

    /**
     * __construct 
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;

        $this->fcmsDatabase->setFetchType(PDO::FETCH_BOTH);
    }

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
                SET `value` = ?
                WHERE `name` = 'current_version'";

        if (!$this->fcmsDatabase->update($sql, $version))
        {
            $this->fcmsError->setMessage('Could not update version');
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

        if (!$this->copy_files(INC."upgrade/$container", ROOT))
        {
            // error alrady displayed by copy_files
            return false;
        }

        // Everything copied over fine, delete the upgrade directory
        $this->deleteDirectory(INC."upgrade");

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
            if (!mkdir($to))
            {
                echo '<div class="error-alert">'.sprintf(T_('Destination not found and could not be created: %s.'), $to).'</div>';
                return false;
            }
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
                if (!$this->copy_files($from.$file, $to.$file))
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
                $this->deleteDirectory($file);
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
        if (!$this->upgrade250())
        {
            return false;
        }
        if (!$this->upgrade260())
        {
            return false;
        }
        if (!$this->upgrade270())
        {
            return false;
        }
        if (!$this->upgrade280())
        {
            return false;
        }
        if (!$this->upgrade290())
        {
            return false;
        }
        if (!$this->upgrade300())
        {
            return false;
        }
        if (!$this->upgrade310())
        {
            return false;
        }
        if (!$this->upgrade320())
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

        $errorMessage = sprintf(T_('Could not upgrade database to version %s.'), '2.5');

        // Status updates
        $status_fixed = false;

        $sql = "SHOW TABLES FROM `$cfg_mysql_db`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r[0] == 'fcms_status')
            {
                $status_fixed = true;
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

            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $sql = "ALTER TABLE `fcms_config` ADD `fb_app_id` VARCHAR(50) NULL";
            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $sql = "ALTER TABLE `fcms_config` ADD `fb_secret` VARCHAR(50) NULL";
            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $sql = "ALTER TABLE `fcms_user_settings` ADD `fb_access_token` VARCHAR(255) NULL";
            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $adminOrder = getNextAdminNavigationOrder();

            $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                    VALUES ('admin_facebook', 6, ?, 0)";
            if (!$this->fcmsDatabase->insert($sql, $adminOrder))
            {
                $this->fcmsError->setMessage($errorMessage);
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

        $errorMessage = sprintf(T_('Could not upgrade database to version %s.'), '2.6');

        // Family News created/updated
        $fnews_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_news`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'created')
            {
                $fnews_fixed = true;
            }
        }

        if (!$fnews_fixed)
        {
            $sql = "ALTER TABLE `fcms_news`
                    CHANGE `date` `updated` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                    ADD COLUMN `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `user`";
            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // Change log
        $change_fixed = false;

        $sql = "SHOW TABLES FROM `$cfg_mysql_db`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r[0] == 'fcms_changelog') { $change_fixed = true; }
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

            if (!$this->fcmsDatabase->create($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // Addressbook
        $book_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_address`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'created')
            {
                $book_fixed = true;
            }
        }

        if (!$book_fixed)
        {
            $sql = "ALTER TABLE `fcms_address`
                    CHANGE `entered_by` `created_id` INT(11) NOT NULL DEFAULT '0',
                    ADD COLUMN `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `created_id`,
                    ADD COLUMN `updated_id` INT(11) NOT NULL DEFAULT '0' AFTER `updated`";

            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $sql = "UPDATE `fcms_address`
                    SET `updated_id` = `created_id`
                    WHERE `updated_id` = 0";

            if (!$this->fcmsDatabase->update($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $sql = "UPDATE `fcms_address`
                    SET `created` = `updated`
                    WHERE `created` = '0000-00-00 00:00:00'";

            if (!$this->fcmsDatabase->update($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // YouTube (key) - Old config style
        $youtube_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_config`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'youtube_key')
            {
                $youtube_fixed = true;
            }
        }

        // YouTube (key) - New config style
        // We do this check, because the upgrade script may have already done added the youtube key,
        // and then converted it to the new style.
        // We want to check for that before we add the old style config.
        if (!$youtube_fixed)
        {
            $sql = "SELECT `name` FROM `fcms_config` WHERE `name` = 'youtube_key'";

            $row = $this->fcmsDatabase->getRow($sql);
            if ($row === false)
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            if (!empty($row))
            {
                $youtube_fixed = true;
            }
        }

        if (!$youtube_fixed)
        {
            $sql = "ALTER TABLE `fcms_config`
                    ADD COLUMN `youtube_key` VARCHAR(255) NULL";

            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $adminOrder = getNextAdminNavigationOrder();

            $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                    VALUES ('admin_youtube', 6, ?, 1)";

            if (!$this->fcmsDatabase->insert($sql, $adminOrder))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // YouTube (token)
        $youtube_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_user_settings`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'youtube_session_token')
            {
                $youtube_fixed = true;
            }
        }

        if (!$youtube_fixed)
        {
            $sql = "ALTER TABLE `fcms_user_settings`
                    ADD COLUMN `youtube_session_token` VARCHAR(255) NULL";

            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // Config
        $cfg_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_config`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'name')
            {
                $cfg_fixed = true;
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

            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            // Get current config data
            $sql = "SELECT *
                    FROM `fcms_config`";

            $config = $this->fcmsDatabase->getRows($sql);
            if ($config === false)
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            // Insert current config into new table
            $sql = "INSERT INTO `fcms_config2`
                        (`name`, `value`)
                    VALUES
                        ('sitename', ?),
                        ('contact', ?),
                        ('current_version', ?),
                        ('auto_activate', ?),
                        ('registration', ?),
                        ('full_size_photos', ?),
                        ('site_off', ?),
                        ('log_errors', ?),
                        ('fs_client_id', ?),
                        ('fs_client_secret', ?),
                        ('fs_callback_url', ?),
                        ('external_news_date', ?),
                        ('fb_app_id', ?),
                        ('fb_secret', ?),
                        ('youtube_key', ?)";

            $params = array(
                $config['sitename'],
                $config['contact'],
                $config['current_version'],
                $config['auto_activate'],
                $config['registration'], 
                $config['full_size_photos'],
                $config['site_off'],
                $config['log_errors'],
                $config['fs_client_id'],
                $config['fs_client_secret'], 
                $config['fs_callback_url'],
                $config['external_news_date'],
                $config['fb_app_id'],
                $config['fb_secret'],
                $config['youtube_key']
            );

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            // Delete current config
            $sql = "DROP TABLE `fcms_config`";

            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            // Rename new table to current
            $sql = "RENAME TABLE `fcms_config2` TO `fcms_config`";

            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // Schedule
        $schedule_fixed = false;

        $sql = "SHOW TABLES FROM `$cfg_mysql_db`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r[0] == 'fcms_schedule') { $schedule_fixed = true; }
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
            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $sql = "INSERT INTO `fcms_schedule` (`type`, `repeat`)
                    VALUES 
                        ('familynews', 'hourly'),
                        ('youtube', 'hourly')";
            if (!$this->fcmsDatabase->insert($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $sql = "INSERT INTO `fcms_config` (`name`, `value`)
                    VALUES ('running_job', '0')";
            if (!$this->fcmsDatabase->insert($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $adminOrder = getNextAdminNavigationOrder();

            $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                    VALUES ('admin_scheduler', 6, ?, 1)";

            if (!$this->fcmsDatabase->insert($sql, $adminOrder))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // Video Gallery
        $video_fixed = false;

        $sql = "SHOW TABLES FROM `$cfg_mysql_db`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r[0] == 'fcms_video') { $video_fixed = true; }
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

            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $order = getNextShareNavigationOrder();

            $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                    VALUES ('videogallery', 4, ?, 1)";

            if (!$this->fcmsDatabase->insert($sql, $order))
            {
                $this->fcmsError->setMessage($errorMessage);
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

            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
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

        $errorMessage = sprintf(T_('Could not upgrade database to version %s.'), '2.7');

        // Country
        $country_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_address`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'country')
            {
                $country_fixed = true;
            }
        }

        if (!$country_fixed)
        {
            $sql = "ALTER TABLE `fcms_address`
                    ADD COLUMN `country` CHAR(2) DEFAULT NULL AFTER `user`";
            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $sql = "INSERT INTO `fcms_config` (`name`, `value`)
                    VALUES ('country', 'US')";
            if (!$this->fcmsDatabase->insert($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // Birthday and date of death
        $dob_dod_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_users`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'dob_year')
            {
                $dob_dod_fixed = true;
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

            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $sql = "SELECT `id`, `birthday`, `death`
                    FROM `fcms_users`";

            $rows = $this->fcmsDatabase->getRows($sql);
            if ($rows === false)
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            if (count($rows))
            {
                $this->fcmsError->add(array(
                    'type'    => 'operation',
                    'message' => T_('No user birthday\'s found.'),
                    'line'    => __LINE__,
                    'file'    => __FILE__,
                ));
                return false;
            }

            foreach ($rows as $r)
            {
                $id = $r['id'];

                $bYear  = substr($r['birthday'], 0, 4);
                $bMonth = substr($r['birthday'], 5, 2);
                $bDay   = substr($r['birthday'], 8, 2);

                $dYear  = substr($r['death'], 0, 4);
                $dMonth = substr($r['death'], 5, 2);
                $dDay   = substr($r['death'], 8, 2);

                $sql = "UPDATE `fcms_users`
                        SET `dob_year`  = ?,
                            `dob_month` = ?,
                            `dob_day`   = ?,
                            `dod_year`  = ?,
                            `dod_month` = ?,
                            `dod_day`   = ?
                        WHERE `id`      = ?";

                $params = array(
                    $bYear,
                    $bMonth,
                    $bDay,
                    $dYear,
                    $dMonth,
                    $dDay,
                    $id
                );

                if (!$this->fcmsDatabase->update($sql, $params))
                {
                    $this->fcmsError->setMessage($errorMessage);
                    return false;
                }
            }

            $sql = "ALTER TABLE `fcms_users`
                    DROP COLUMN `birthday`,
                    DROP COLUMN `death`";

            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        return true;
    }

    /**
     * upgrade280
     * 
     * Upgrade database to version 2.8.
     * 
     * @return boolean
     */
    function upgrade280 ()
    {
        global $cfg_mysql_db;

        $errorMessage = sprintf(T_('Could not upgrade database to version %s.'), '2.8');

        // category description
        $desc_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_category`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'description')
            {
                $desc_fixed = true;
            }
        }

        if (!$desc_fixed)
        {
            $sql = "ALTER TABLE `fcms_category`
                    ADD COLUMN `description` VARCHAR(255) NULL";

            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // debug
        $debug_fixed = false;

        $sql = "SELECT `name` FROM `fcms_config` WHERE `name` = 'debug'";

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }
        if (!empty($row))
        {
            $debug_fixed = true;
        }

        if (!$debug_fixed)
        {
            $sql = "INSERT INTO `fcms_config` (`name`, `value`)
                    VALUES ('debug', '0')";

            if (!$this->fcmsDatabase->insert($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        return true;
    }

    /**
     * upgrade290
     * 
     * Upgrade database to version 2.9.
     * 
     * @return boolean
     */
    function upgrade290 ()
    {
        global $cfg_mysql_db;

        $errorMessage = sprintf(T_('Could not upgrade database to version %s.'), '2.9');

        // turn on foursquare admin
        $foursquare_fixed = false;

        $sql = "SELECT `link`, `order`
                FROM `fcms_navigation` 
                WHERE `link` = 'admin_whereiseveryone' 
                OR `link` = 'admin_foursquare'
                AND `order` > 0
                LIMIT 1";

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }
        if (!empty($row))
        {
            $foursquare_fixed = true;
        }

        if (!$foursquare_fixed)
        {
            $adminOrder = getNextAdminNavigationOrder();

            $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                    VALUES ('admin_foursquare', 6, ?, 0)";

            if (!$this->fcmsDatabase->insert($sql, $adminOrder))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // awards scheduler
        $awards_fixed = false;

        $sql = "SELECT `type`
                FROM `fcms_schedule` 
                WHERE `type` = 'awards' 
                LIMIT 1";

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        if (!empty($row))
        {
            $awards_fixed = true;
        }

        if (!$awards_fixed)
        {
            $sql = "INSERT INTO `fcms_schedule` (`type`, `repeat`)
                    VALUES ('awards', 'daily')";

            if (!$this->fcmsDatabase->insert($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            // delete db entry
            $sql = "DELETE FROM `fcms_navigation`
                    WHERE `link` = 'admin_awards'";

            if (!$this->fcmsDatabase->delete($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            // delete file
            if (file_exists('awards.php'))
            {
                unlink('awards.php');
            }

        }

        // Start of week
        $start_fixed = false;

        $sql = "SELECT `name`
                FROM `fcms_config`
                WHERE `name` = 'start_week'";

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        if (!empty($row))
        {
            $start_fixed = true;
        }

        if (!$start_fixed)
        {
            $sql = "INSERT INTO `fcms_config` (`name`, `value`)
                    VALUES ('start_week', '0')";

            if (!$this->fcmsDatabase->insert($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // Notifications
        $notification_fixed = false;

        $sql = "SHOW TABLES FROM `$cfg_mysql_db`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r[0] == 'fcms_notification') { $notification_fixed = true; }
        }

        if (!$notification_fixed)
        {
            $sql = "CREATE TABLE `fcms_notification` (
                        `id` INT(25) NOT NULL AUTO_INCREMENT,
                        `user` INT(25) NOT NULL DEFAULT '0',
                        `created_id` INT(25) NOT NULL DEFAULT '0',
                        `notification` VARCHAR(50) NOT NULL,
                        `data` VARCHAR(50) NOT NULL,
                        `read` TINYINT(1) NOT NULL DEFAULT '0',
                        `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                        `updated` DATETIME DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        CONSTRAINT FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }

            $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                    VALUES ('notification', 2, 4, 1)";

            if (!$this->fcmsDatabase->insert($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        return true;
    }

    /**
     * upgrade300
     * 
     * Upgrade database to version 3.0.
     * 
     * @return boolean
     */
    function upgrade300 ()
    {
        global $cfg_mysql_db;

        $errorMessage = sprintf(T_('Could not upgrade database to version %s.'), '3.0');

        // instagram client id
        $instagram_client_id_fixed = false;

        $sql = "SELECT `name` 
                FROM `fcms_config` 
                WHERE `name` = 'instagram_client_id'";

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        if (!empty($row))
        {
            $instagram_client_id_fixed = true;
        }

        if (!$instagram_client_id_fixed)
        {
            $sql = "INSERT INTO `fcms_config` (`name`, `value`)
                    VALUES ('instagram_client_id', NULL)";

            if (!$this->fcmsDatabase->insert($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // instagram client secret
        $instagram_client_secret_fixed = false;

        $sql = "SELECT `name` 
                FROM `fcms_config` 
                WHERE `name` = 'instagram_client_secret'";

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        if (!empty($row))
        {
            $instagram_client_secret_fixed = true;
        }

        if (!$instagram_client_secret_fixed)
        {
            $sql = "INSERT INTO `fcms_config` (`name`, `value`)
                    VALUES ('instagram_client_secret', NULL)";

            if (!$this->fcmsDatabase->insert($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // instagram admin nav
        $instagram_admin_nav_fixed = false;

        $sql = "SELECT `link`
                FROM `fcms_navigation`
                WHERE `link` = 'admin_instagram'";

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        if (!empty($row))
        {
            $instagram_admin_nav_fixed = true;
        }

        if (!$instagram_admin_nav_fixed)
        {
            $adminOrder = getNextAdminNavigationOrder();

            $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                    VALUES ('admin_instagram', 6, ?, 1)";

            if (!$this->fcmsDatabase->insert($sql, $adminOrder))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // instagram user access code
        $instagram_user_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_user_settings`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'instagram_access_token')
            {
                $instagram_user_fixed = true;
            }
        }

        if (!$instagram_user_fixed)
        {
            $sql = "ALTER TABLE `fcms_user_settings` 
                    ADD `instagram_access_token` VARCHAR(255) NULL";

            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // external
        $external_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_gallery_photos`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'external_id')
            {
                $external_fixed = true;
            }
        }

        if (!$external_fixed)
        {
            $sql = "ALTER TABLE `fcms_gallery_photos` 
                    ADD `external_id` INT(11) DEFAULT NULL AFTER `filename`";

            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // external photos
        $external_photos_fixed = false;

        $sql = "SHOW TABLES FROM `$cfg_mysql_db`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r[0] == 'fcms_gallery_external_photo')
            {
                $external_photos_fixed = true;
            }
        }

        if (!$external_photos_fixed)
        {
            $sql = "CREATE TABLE `fcms_gallery_external_photo` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `source_id` VARCHAR(255) NOT NULL,
                        `thumbnail` VARCHAR(255) NOT NULL, 
                        `medium` VARCHAR(255) NOT NULL, 
                        `full` VARCHAR(255) NOT NULL, 
                        PRIMARY KEY (`id`)
                    ) 
                    ENGINE=InnoDB DEFAULT CHARSET=utf8";

            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // Rename gallery photo comment
        $photo_com_fixed = false;

        $sql = "SHOW TABLES FROM `$cfg_mysql_db`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r[0] == 'fcms_gallery_photo_comment')
            {
                $photo_com_fixed = true;
            }
        }

        if (!$photo_com_fixed)
        {
            $sql = "RENAME TABLE `fcms_gallery_comments` TO `fcms_gallery_photo_comment`";

            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // Gallery category comments
        $cat_com_fixed = false;

        $sql = "SHOW TABLES FROM `$cfg_mysql_db`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r[0] == 'fcms_gallery_category_comment')
            {
                $cat_com_fixed = true;
            }
        }

        if (!$cat_com_fixed)
        {
            $sql = "CREATE TABLE `fcms_gallery_category_comment` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `category_id` INT(11) NOT NULL, 
                        `comment` TEXT NOT NULL, 
                        `created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                        `created_id` INT(11) NOT NULL, 
                        PRIMARY KEY (`id`)
                    ) 
                    ENGINE=InnoDB DEFAULT CHARSET=utf8";

            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // instagram automatic
        $instagram_auto_upload_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_user_settings`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'instagram_auto_upload')
            {
                $instagram_auto_upload_fixed = true;
            }
        }

        if (!$instagram_auto_upload_fixed)
        {
            $sql = "ALTER TABLE `fcms_user_settings` 
                    ADD `instagram_auto_upload` TINYINT(1) DEFAULT 0";

            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // instagram scheduler
        $instagram_sched_fixed = false;

        $sql = "SELECT `type`
                FROM `fcms_schedule` 
                WHERE `type` = 'instagram' 
                LIMIT 1";

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        if (!empty($row))
        {
            $instagram_sched_fixed = true;
        }

        if (!$instagram_sched_fixed)
        {
            $sql = "INSERT INTO `fcms_schedule` (`type`, `repeat`)
                    VALUES ('instagram', 'hourly')";

            if (!$this->fcmsDatabase->insert($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        return true;
    }

    /**
     * upgrade310
     * 
     * Upgrade database to version 3.1.
     * 
     * @return boolean
     */
    function upgrade310 ()
    {
        global $cfg_mysql_db;

        $errorMessage = sprintf(T_('Could not upgrade database to version %s.'), '3.1');

        // Poll comments
        $poll_com_fixed = false;

        $sql = "SHOW TABLES FROM `$cfg_mysql_db`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r[0] == 'fcms_poll_comment')
            {
                $poll_com_fixed = true;
            }
        }

        if (!$poll_com_fixed)
        {
            $sql = "CREATE TABLE `fcms_poll_comment` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `poll_id` INT(11) NOT NULL, 
                        `comment` TEXT NOT NULL, 
                        `created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                        `created_id` INT(11) NOT NULL, 
                        PRIMARY KEY (`id`)
                    ) 
                    ENGINE=InnoDB DEFAULT CHARSET=utf8";

            if (!$this->fcmsDatabase->execute($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        return true;
    }

    /**
     * upgrade320
     * 
     * Upgrade database to version 3.2.
     * 
     * @return boolean
     */
    function upgrade320 ()
    {
        $errorMessage = sprintf(T_('Could not upgrade database to version %s.'), '3.2');

        // joindate
        $joindate_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_users`
                WHERE `Field` = 'joindate'";

        $r = $this->fcmsDatabase->getRow($sql);
        if ($r === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        if ($r['Type'] == 'datetime')
        {
            $joindate_fixed = true;
        }

        if (!$joindate_fixed)
        {
            $sql = "ALTER TABLE `fcms_users`
                    CHANGE `joindate` `joindate` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'";

            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        // picasa
        $picasa_fixed = false;

        $sql = "SHOW COLUMNS FROM `fcms_user_settings`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage($errorMessage);
            return false;
        }

        foreach ($rows as $r)
        {
            if ($r['Field'] == 'picasa_session_token')
            {
                $picasa_fixed = true;
            }
        }

        if (!$picasa_fixed)
        {
            $sql = "ALTER TABLE `fcms_user_settings`
                    ADD COLUMN `picasa_session_token` VARCHAR(255) NULL";

            if (!$this->fcmsDatabase->alter($sql))
            {
                $this->fcmsError->setMessage($errorMessage);
                return false;
            }
        }

        return true;
    }
}
