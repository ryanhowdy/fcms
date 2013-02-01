<?php
include_once 'thirdparty/gettext.inc';

/**
 * dropTables 
 * 
 * @return void
 */
function dropTables ()
{
    mysql_query("DROP TABLE IF EXISTS `fcms_config`")                   or die("fcms_config<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_notification`")             or die("fcms_notification<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_video_comment`")            or die("fcms_video_comment<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_video`")                    or die("fcms_video<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_changelog`")                or die("fcms_changelog<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_schedule`")                 or die("fcms_schedule<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_status`")                   or die("fcms_status<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_navigation`")               or die("fcms_navigation<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_chat_online`")              or die("fcms_chat_oneline<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_chat_messages`")            or die("fcms_chat_messages<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_address`")                  or die("fcms_address<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_alerts`")                   or die("fcms_alerts<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_privatemsg`")               or die("fcms_privatemsg<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_documents`")                or die("fcms_documents<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_calendar`")                 or die("fcms_calendar<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_invitation`")               or die("fcms_invitation<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_gallery_photo_comment`")    or die("fcms_gallery_photo_comment<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_gallery_category_comment`") or die("fcms_gallery_category_comment<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_gallery_photos_tags`")      or die("fcms_gallery_photos_tags<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_gallery_external_photo`")   or die("fcms_gallery_external_photo<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_gallery_photos`")           or die("fcms_gallery_photos<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_news_comments`")            or die("fcms_news_comments<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_news`")                     or die("fcms_news<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_poll_comment`")             or die("fcms_poll_comment<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_poll_votes`")               or die("fcms_poll_votes<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_poll_options`")             or die("fcms_poll_options<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_polls`")                    or die("fcms_polls<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_prayers`")                  or die("fcms_prayers<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_board_posts`")              or die("fcms_board_posts<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_board_threads`")            or die("fcms_board_threads<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_recipes`")                  or die("fcms_recipes<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_recipe_comment`")           or die("fcms_recipe_comment<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_category`")                 or die("fcms_category<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_user_awards`")              or die("fcms_user_awards<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_user_settings`")            or die("fcms_user_settings<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_relationship`")             or die("fcms_relationship<br/>" . mysql_error());
    mysql_query("DROP TABLE IF EXISTS `fcms_users`")                    or die("fcms_users<br/>" . mysql_error());

    mysql_query("SET NAMES utf8") or die("Encoding<br/>" . mysql_error());
}

/**
 * installConfig 
 * 
 * @param string  $sitename 
 * @param string  $contact 
 * @param string  $version 
 * 
 * @return void
 */
function installConfig ($sitename, $contact, $version)
{
    $sql = "CREATE TABLE `fcms_config` (
                `name` VARCHAR(50) NOT NULL,
                `value` VARCHAR(255) NULL
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    $sql = "INSERT INTO `fcms_config` (`name`, `value`)
            VALUES
                ('sitename', '$sitename'),
                ('contact', '$contact'),
                ('current_version', '$version'),
                ('auto_activate', '0'),
                ('registration', '1'), 
                ('full_size_photos', '0'),
                ('site_off', '0'),
                ('log_errors', '0'),
                ('fs_client_id', NULL),
                ('fs_client_secret', NULL), 
                ('fs_callback_url', NULL),
                ('external_news_date', NULL),
                ('fb_app_id', NULL),
                ('fb_secret', NULL),
                ('youtube_key', NULL),
                ('running_job', '0'),
                ('start_week', '0'),
                ('debug', '0'),
                ('country', 'US'),
                ('instagram_client_id', NULL),
                ('instagram_client_secret', NULL)";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());
}

/**
 * installNavigation 
 * 
 * @param string  $sections 
 *
 * @return void
 */
function installNavigation ($sections)
{
    $sql = "CREATE TABLE `fcms_navigation` (
                `id` INT(25) NOT NULL AUTO_INCREMENT,
                `link` VARCHAR(30) NOT NULL,
                `col` TINYINT(1) NOT NULL,
                `order` TINYINT(2) NOT NULL,
                `req` TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
            VALUES ('home', 1, 1, 1),
                ('profile', 2, 1, 1),
                ('settings', 2, 2, 1),
                ('pm', 2, 3, 1),
                ('notification', 2, 4, 1),
                ('messageboard', 3, 1, 1),
                ('photogallery', 4, 1, 1),
                ('videogallery', 4, 2, 1),
                ('addressbook', 4, 3, 1),
                ('calendar', 4, 4, 1),
                ('members', 5, 1, 1),
                ('contact', 5, 2, 1),
                ('help', 5, 3, 1),
                ('admin_upgrade', 6, 1, 1),
                ('admin_configuration', 6, 2, 1),
                ('admin_members', 6, 3, 1),
                ('admin_photogallery', 6, 4, 1),
                ('admin_polls', 6, 5, 1),
                ('admin_scheduler', 6, 10, 1),
                ('admin_facebook', 6, 6, 1),
                ('admin_youtube', 6, 7, 1),
                ('admin_foursquare', 6, 8, 1),
                ('admin_instagram', 6, 9, 1)";
    mysql_query($sql) or die("$sql<br/>".mysql_error());

    $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
            VALUES ";

    foreach ($sections as $table => $section)
    {
        $sql .= "('$table', $section[0], $section[1], $section[2]), ";
    }

    $sql = substr($sql, 0, -2); // Remove the comma at the end

    mysql_query($sql) or die($sql . "<br/><br/>" . mysql_error());
}

/**
 * installUsers 
 * 
 * Installs the users, user_settings and address tables.
 * 
 * @param string $fname 
 * @param string $lname 
 * @param string $email 
 * @param string $dobYear
 * @param string $dobMonth
 * @param string $dobDay
 * @param string $username 
 * @param string $password 
 * 
 * @return void
 */
function installUsers ($fname, $lname, $email, $dobYear, $dobMonth, $dobDay, $username, $password)
{
    $sql = "CREATE TABLE `fcms_users` (
                `id` INT(25) NOT NULL AUTO_INCREMENT, 
                `access` TINYINT(1) NOT NULL DEFAULT '3', 
                `activity` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `joindate` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `fname` VARCHAR(25) NOT NULL DEFAULT 'fname', 
                `mname` VARCHAR(25) NULL,
                `lname` VARCHAR(25) NOT NULL DEFAULT 'lname', 
                `maiden` VARCHAR(25) NULL,
                `sex` CHAR(1) NOT NULL DEFAULT 'M',
                `email` VARCHAR(50) NOT NULL DEFAULT 'me@mail.com', 
                `dob_year` CHAR(4),
                `dob_month` CHAR(2),
                `dob_day` CHAR(2),
                `dod_year` CHAR(4),
                `dod_month` CHAR(2),
                `dod_day` CHAR(2),
                `username` VARCHAR(25) NOT NULL DEFAULT '0', 
                `password` VARCHAR(255) NOT NULL DEFAULT '0', 
                `avatar` VARCHAR(25) NOT NULL DEFAULT 'no_avatar.jpg', 
                `gravatar` VARCHAR(255) NULL, 
                `bio` VARCHAR(200) NULL,
                `activate_code` CHAR(13) NULL, 
                `activated` TINYINT(1) NOT NULL DEFAULT '0', 
                `login_attempts` TINYINT(1) NOT NULL DEFAULT '0', 
                `locked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                PRIMARY KEY (`id`), 
                UNIQUE KEY `username` (`username`)
            )
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // insert users
    $sql = "INSERT INTO `fcms_users` (
                `id`, `access`, `joindate`, `fname`, `lname`, `email`, `dob_year`, `dob_month`, `dob_day`, `username`, `password`, `activated`
            ) VALUES (
                1, 
                1, 
                NOW(), 
                '$fname', 
                '$lname', 
                '$email', 
                '$dobYear', 
                '$dobMonth', 
                '$dobDay', 
                '$username', 
                '$password', 
                1
            )";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create user_settings
    $sql = "CREATE TABLE `fcms_user_settings` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `user` INT(11) NOT NULL, 
                `theme` VARCHAR(25) NOT NULL DEFAULT 'default', 
                `boardsort` SET('ASC', 'DESC') NOT NULL DEFAULT 'ASC', 
                `displayname` SET('1','2','3') NOT NULL DEFAULT '1', 
                `frontpage` SET('1','2') NOT NULL DEFAULT '1', 
                `timezone` set('-12 hours', '-11 hours', '-10 hours', '-9 hours', '-8 hours', '-7 hours', '-6 hours', '-5 hours', '-4 hours', '-3 hours -30 minutes', '-3 hours', '-2 hours', '-1 hours', '-0 hours', '+1 hours', '+2 hours', '+3 hours', '+3 hours +30 minutes', '+4 hours', '+4 hours +30 minutes', '+5 hours', '+5 hours +30 minutes', '+6 hours', '+7 hours', '+8 hours', '+9 hours', '+9 hours +30 minutes', '+10 hours', '+11 hours', '+12 hours') NOT NULL DEFAULT '-5 hours', 
                `dst` TINYINT(1) NOT NULL DEFAULT '0', 
                `email_updates` TINYINT(1) NOT NULL DEFAULT '0', 
                `advanced_upload` TINYINT(1) NOT NULL DEFAULT '1',
                `advanced_tagging` TINYINT(1) NOT NULL DEFAULT '1',
                `language` VARCHAR(6) NOT NULL DEFAULT 'en_US',
                `fs_user_id` INT(11) NULL,
                `fs_access_token` CHAR(50) NULL,
                `blogger` VARCHAR(255) NULL,
                `tumblr` VARCHAR(255) NULL,
                `wordpress` VARCHAR(255) NULL,
                `posterous` VARCHAR(255) NULL,
                `fb_access_token` VARCHAR(255) NULL,
                `youtube_session_token` VARCHAR(255) NULL,
                `instagram_access_token` VARCHAR(255) NULL,
                `instagram_auto_upload` TINYINT(1) DEFAULT 0,
                `picasa_session_token` VARCHAR(255) NULL,
                PRIMARY KEY (`id`), 
                KEY `user_ind` (`user`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter user_settings
    $sql = "ALTER TABLE `fcms_user_settings` 
            ADD CONSTRAINT `fcms_user_stgs_ibfk_1` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // insert user_settings
    $sql = "INSERT INTO `fcms_user_settings` (`id`, `user`) 
            VALUES (NULL, 1)";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create address
    $sql = "CREATE TABLE `fcms_address` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `user` INT(11) NOT NULL DEFAULT '0', 
                `country` CHAR(2) DEFAULT NULL, 
                `address` VARCHAR(50) DEFAULT NULL, 
                `city` VARCHAR(50) DEFAULT NULL, 
                `state` VARCHAR(50) DEFAULT NULL, 
                `zip` VARCHAR(10) DEFAULT NULL, 
                `home` VARCHAR(20) DEFAULT NULL, 
                `work` VARCHAR(20) DEFAULT NULL, 
                `cell` VARCHAR(20) DEFAULT NULL, 
                `created_id` INT(11) NOT NULL DEFAULT '0', 
                `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `updated_id` INT(11) NOT NULL DEFAULT '0', 
                `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                PRIMARY KEY (`id`), 
                KEY `user_ind` (`user`), 
                KEY `create_ind` (`created_id`),
                KEY `update_ind` (`updated_id`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter address
    $sql = "ALTER TABLE `fcms_address` 
            ADD CONSTRAINT `fcms_address_ibfk_1` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // insert address
    $sql = "INSERT INTO `fcms_address` (`id`, `user`, `created_id`, `created`, `updated_id`, `updated`) 
            VALUES (NULL, 1, 1, NOW(), 1, NOW())";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());
}

/**
 * installCategory 
 * 
 * @return void
 */
function installCategory ()
{
    // create fcms_category
    $sql = "CREATE TABLE `fcms_category` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `type` VARCHAR(20) NOT NULL,
                `user` INT(11) NOT NULL,
                `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `color` VARCHAR(20) NULL,
                `description` VARCHAR(255) NULL,
                PRIMARY KEY (`id`),
                KEY `user_ind` (`user`)
            )
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // insert fcms_category
    $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`, `date`, `color`)
            VALUES  
                ('', 'calendar', 1, NOW(), 'none'), 
                ('".T_('Anniversary')."', 'calendar', 1, NOW(), 'green'),
                ('".T_('Birthday')."', 'calendar', 1, NOW(), 'red'),
                ('".T_('Holiday')."', 'calendar', 1, NOW(), 'indigo')";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

}

/**
 * installCalendar 
 * 
 * @return void
 */
function installCalendar ()
{
    // create calendar
    $sql = "CREATE TABLE `fcms_calendar` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `date` DATE NOT NULL DEFAULT '0000-00-00', 
                `time_start` TIME NULL, 
                `time_end` TIME NULL, 
                `date_added` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `title` VARCHAR(50) NOT NULL DEFAULT 'MyDate', 
                `desc` TEXT, 
                `created_by` INT(11) NOT NULL DEFAULT '0', 
                `category` INT(11) NOT NULL DEFAULT '0', 
                `repeat` VARCHAR(20) NULL, 
                `private` TINYINT(1) NOT NULL DEFAULT '0', 
                `invite` TINYINT(1) NOT NULL DEFAULT '0', 
                PRIMARY KEY  (`id`), 
                KEY `by_ind` (`created_by`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter calendar
    $sql = "ALTER TABLE `fcms_calendar` 
            ADD CONSTRAINT `fcms_calendar_ibfk_1` 
            FOREIGN KEY (`created_by`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());
    $sql = "INSERT INTO `fcms_calendar` 
                (`id`, `date`, `date_added`, `title`, `created_by`, `category`, `repeat`) 
            VALUES 
                (NULL, '2007-12-25', '2007-12-25 01:00:00', \"".T_('Christmas')."\", 1, 4, 'yearly'), 
                (NULL, '2007-02-14', '2007-02-14 01:00:00', \"".T_('Valentine\'s Day')."\", 1, 4, 'yearly'), 
                (NULL, '2007-01-01', '2007-01-01 01:00:00', \"".T_('New Year\'s Day')."\", 1, 4, 'yearly'), 
                (NULL, '2007-07-04', '2007-07-04 01:00:00', \"".T_('Independence Day')."\", 1, 4, 'yearly'), 
                (NULL, '2007-02-02', '2007-02-02 01:00:00', \"".T_('Groundhog Day')."\", 1, 4, 'yearly'), 
                (NULL, '2007-03-17', '2007-03-17 01:00:00', \"".T_('St. Patrick\'s Day')."\", 1, 4, 'yearly'), 
                (NULL, '2007-04-01', '2007-04-01 01:00:00', \"".T_('April Fools Day')."\", 1, 4, 'yearly'), 
                (NULL, '2007-10-31', '2007-10-31 01:00:00', \"".T_('Halloween')."\", 1, 4, 'yearly')";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());
}

/**
 * installTables 
 * 
 * @return void
 */
function installTables ()
{
    // create video
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
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

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
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create schedule
    $sql = "CREATE TABLE `fcms_schedule` (
                `id`        INT(25) NOT NULL AUTO_INCREMENT,
                `type`      VARCHAR(50) NOT NULL DEFAULT 'familynews',
                `repeat`    VARCHAR(50) NOT NULL DEFAULT 'hourly',
                `lastrun`   DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                `status`    TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // populate schedule
    $sql = "INSERT INTO `fcms_schedule` (`type`, `repeat`)
            VALUES 
                ('awards', 'daily'),
                ('familynews', 'hourly'),
                ('youtube', 'hourly'),
                ('instagram', 'hourly')";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create changelog
    $sql = "CREATE TABLE `fcms_changelog` (
                `id` INT(25) NOT NULL AUTO_INCREMENT,
                `user` INT(25) NOT NULL DEFAULT '0',
                `table` VARCHAR(50) NOT NULL,
                `column` VARCHAR(50) NOT NULL,
                `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY (`id`),
                CONSTRAINT FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create invitation
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
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create gallery_photos
    $sql = "CREATE TABLE `fcms_gallery_photos` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `filename` VARCHAR(25) NOT NULL DEFAULT 'noimage.gif', 
                `external_id` INT(11) DEFAULT NULL, 
                `caption` TEXT, 
                `category` INT(11) NOT NULL DEFAULT '0', 
                `user` INT(11) NOT NULL DEFAULT '0', 
                `views` SMALLINT(6) NOT NULL DEFAULT '0', 
                `votes` SMALLINT(6) NOT NULL DEFAULT '0', 
                `rating` FLOAT NOT NULL DEFAULT '0', 
                PRIMARY KEY (`id`), 
                KEY `cat_ind` (`category`), 
                KEY `user_ind` (`user`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter gallery_photos
    $sql = "ALTER TABLE `fcms_gallery_photos` 
            ADD CONSTRAINT `fcms_gallery_photos_ibfk_1` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE, 
            ADD CONSTRAINT `fcms_gallery_photos_ibfk_2` 
            FOREIGN KEY (`category`) 
            REFERENCES `fcms_category` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create gallery_photo_comment
    $sql = "CREATE TABLE `fcms_gallery_photo_comment` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `photo` INT(11) NOT NULL DEFAULT '0', 
                `comment` TEXT NOT NULL, 
                `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `user` INT(11) NOT NULL DEFAULT '0', 
                PRIMARY KEY (`id`), 
                KEY `photo_ind` (`photo`), 
                KEY `user_ind` (`user`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter gallery_photo_comment
    $sql = "ALTER TABLE `fcms_gallery_photo_comment` 
            ADD CONSTRAINT `fcms_gallery_photo_comment_ibfk_1` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE, 
            ADD CONSTRAINT `fcms_gallery_photo_comment_ibfk_2` 
            FOREIGN KEY (`photo`) 
            REFERENCES `fcms_gallery_photos` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create gallery_category_comment
    $sql = "CREATE TABLE `fcms_gallery_category_comment` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `category_id` INT(11) NOT NULL, 
                `comment` TEXT NOT NULL, 
                `created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `created_id` INT(11) NOT NULL, 
                PRIMARY KEY (`id`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create gallery_photos_tags
    $sql = "CREATE TABLE `fcms_gallery_photos_tags` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `user` INT(11) NOT NULL DEFAULT '0', 
                `photo` INT(11) NOT NULL DEFAULT '0', 
                PRIMARY KEY (`id`), 
                KEY `tag_photo_ind` (`photo`), 
                KEY `tag_user_ind` (`user`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter gallery_photos_tags
    $sql = "ALTER TABLE `fcms_gallery_photos_tags` 
            ADD CONSTRAINT `fcms_gallery_photos_tags_ibfk_1` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE, 
            ADD CONSTRAINT `fcms_gallery_photos_tags_ibfk_2` 
            FOREIGN KEY (`photo`) 
            REFERENCES `fcms_gallery_photos` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create gallery_external_photo
    $sql = "CREATE TABLE `fcms_gallery_external_photo` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `source_id` VARCHAR(255) NOT NULL,
                `thumbnail` VARCHAR(255) NOT NULL, 
                `medium` VARCHAR(255) NOT NULL, 
                `full` VARCHAR(255) NOT NULL, 
                PRIMARY KEY (`id`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create news
    $sql = "CREATE TABLE `fcms_news` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `title` VARCHAR(50) NOT NULL DEFAULT '', 
                `news` TEXT NOT NULL, 
                `user` INT(11) NOT NULL DEFAULT '0', 
                `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                `updated` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                `external_type` VARCHAR(20) NULL,
                `external_id` VARCHAR(255) NULL,
                PRIMARY KEY (`id`), 
                KEY `userindx` (`user`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter news
    $sql = "ALTER TABLE `fcms_news` 
            ADD CONSTRAINT `fcms_news_ibfk_1` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create news_comments
    $sql = "CREATE TABLE `fcms_news_comments` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `news` INT(11) NOT NULL DEFAULT '0', 
                `comment` TEXT NOT NULL, 
                `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `user` INT(11) NOT NULL DEFAULT '0', 
                PRIMARY KEY (`id`), 
                KEY `photo_ind` (`news`), 
                KEY `user_ind` (`user`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter news_comments
    $sql = "ALTER TABLE `fcms_news_comments` 
            ADD CONSTRAINT `fcms_news_comments_ibfk_2` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE, 
            ADD CONSTRAINT `fcms_news_comments_ibfk_1` 
            FOREIGN KEY (`news`) 
            REFERENCES `fcms_news` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create polls
    $sql = "CREATE TABLE `fcms_polls` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `question` TEXT NOT NULL, 
                `started` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                PRIMARY KEY  (`id`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // insert poll
    $sql = "INSERT INTO `fcms_polls` (`id`, `question`, `started`) 
            VALUES (NULL, '".T_('Family Connections software is...')."', NOW())";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create poll_options
    $sql = "CREATE TABLE `fcms_poll_options` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `poll_id` INT(11) NOT NULL DEFAULT '0', 
                `option` TEXT NOT NULL, 
                `votes` INT(11) NOT NULL DEFAULT '0', 
                PRIMARY KEY (`id`), 
                KEY `pollid_ind` (`poll_id`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter poll_options
    $sql = "ALTER TABLE `fcms_poll_options` 
            ADD CONSTRAINT `fcms_poll_options_ibfk_1` 
            FOREIGN KEY (`poll_id`) 
            REFERENCES `fcms_polls` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // insert poll_options
    $sql = "INSERT INTO `fcms_poll_options` (`id`, `poll_id`, `option`, `votes`) 
            VALUES 
                (NULL, 1, '".T_('Easy to use!')."', 0), 
                (NULL, 1, '".T_('Visually appealing!')."', 0), 
                (NULL, 1, '".T_('Just what our family needed!')."', 0)";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create poll_votes
    $sql = "CREATE TABLE `fcms_poll_votes` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `user` INT(11) NOT NULL DEFAULT '0', 
                `option` INT(11) NOT NULL DEFAULT '0', 
                `poll_id` INT(11) NOT NULL DEFAULT '0', 
                PRIMARY KEY (`id`), 
                KEY `user_ind` (`user`), 
                KEY `option_ind` (`option`), 
                KEY `poll_id_ind` (`poll_id`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter poll_votes
    $sql = "ALTER TABLE `fcms_poll_votes` 
            ADD CONSTRAINT `fcms_poll_votes_ibfk_1` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE, 
            ADD CONSTRAINT `fcms_poll_votes_ibfk_2` 
            FOREIGN KEY (`option`) 
            REFERENCES `fcms_poll_options` (`id`) 
            ON DELETE CASCADE,  
            ADD CONSTRAINT `fcms_poll_votes_ibfk_3` 
            FOREIGN KEY (`poll_id`) 
            REFERENCES `fcms_polls` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create poll_comment
    $sql = "CREATE TABLE `fcms_poll_comment` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `poll_id` INT(11) NOT NULL, 
                `comment` TEXT NOT NULL, 
                `created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `created_id` INT(11) NOT NULL, 
                PRIMARY KEY (`id`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create prayers
    $sql = "CREATE TABLE `fcms_prayers` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `for` VARCHAR(50) NOT NULL DEFAULT '', 
                `desc` TEXT NOT NULL, 
                `user` INT(11) NOT NULL DEFAULT '0', 
                `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                PRIMARY KEY (`id`), 
                KEY `userindx` (`user`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter prayers
    $sql = "ALTER TABLE `fcms_prayers` 
            ADD CONSTRAINT `fcms_prayers_ibfk_1` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create board_threads
    $sql = "CREATE TABLE `fcms_board_threads` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `subject` VARCHAR(50) NOT NULL DEFAULT 'Subject', 
                `started_by` INT(11) NOT NULL DEFAULT '0', 
                `updated` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `updated_by` INT(11) NOT NULL DEFAULT '0', 
                `views` SMALLINT(6) NOT NULL DEFAULT '0', 
                PRIMARY KEY (`id`), 
                KEY `start_ind` (`started_by`), 
                KEY `up_ind` (`updated_by`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter board_threads
    $sql = "ALTER TABLE `fcms_board_threads` 
            ADD CONSTRAINT `fcms_threads_ibfk_1` 
            FOREIGN KEY (`started_by`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE, 
            ADD CONSTRAINT `fcms_threads_ibfk_2` 
            FOREIGN KEY (`updated_by`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // insert board_threads
    $sql = "INSERT INTO `fcms_board_threads` (`id`, `subject`, `started_by`, `updated`, `updated_by`, `views`) 
            VALUES (1, '".T_('Welcome')."', 1, NOW(), 1, 0)";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create board_posts
    $sql = "CREATE TABLE `fcms_board_posts` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `thread` INT(11) NOT NULL DEFAULT '0', 
                `user` INT(11) NOT NULL DEFAULT '0', 
                `post` TEXT NOT NULL, 
                PRIMARY KEY (`id`), 
                KEY `thread_ind` (`thread`), 
                KEY `user_ind` (`user`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // altert board_posts
    $sql = "ALTER TABLE `fcms_board_posts` 
            ADD CONSTRAINT `fcms_posts_ibfk_1` 
            FOREIGN KEY (`thread`) 
            REFERENCES `fcms_board_threads` (`id`) 
            ON DELETE CASCADE, 
            ADD CONSTRAINT `fcms_posts_ibfk_2` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // insert board_posts
    $sql = "INSERT INTO `fcms_board_posts` (`id`, `date`, `thread`, `user`, `post`) 
            VALUES (NULL, NOW(), 1, 1, '".sprintf(T_('Welcome to the %s Message Board.'), 'Family Connections')."')";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create recipes
    $sql = "CREATE TABLE `fcms_recipes` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `name` VARCHAR(50) NOT NULL DEFAULT 'My Recipe', 
                `thumbnail` VARCHAR(255) NOT NULL DEFAULT 'no_recipe.jpg', 
                `category` INT(11) NOT NULL, 
                `ingredients` TEXT NOT NULL,
                `directions` TEXT NOT NULL, 
                `user` INT(11) NOT NULL, 
                `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                PRIMARY KEY (`id`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter recipes
    $sql = "ALTER TABLE `fcms_recipes` 
            ADD CONSTRAINT `fcms_recipes_ibfk_1` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // recipe comments
    $sql = "CREATE TABLE `fcms_recipe_comment` (
                `id` INT(25) NOT NULL AUTO_INCREMENT,
                `recipe` INT(25) NOT NULL,
                `comment` TEXT NOT NULL,
                `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `user` INT(25) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `recipe` (`recipe`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create privatemsg
    $sql = "CREATE TABLE `fcms_privatemsg` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `to` INT(11) NOT NULL, 
                `from` INT(11) NOT NULL, 
                `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `title` VARCHAR(50) NOT NULL DEFAULT 'PM Title', 
                `msg` TEXT, 
                `read` TINYINT(1) NOT NULL DEFAULT '0', 
                PRIMARY KEY (`id`), 
                KEY `to_ind` (`to`), 
                KEY `from_ind` (`from`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter privatemsg
    $sql = "ALTER TABLE `fcms_privatemsg` 
            ADD CONSTRAINT `fcms_privatemsg_ibfk_1` 
            FOREIGN KEY (`to`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE, 
            ADD CONSTRAINT `fcms_privatemsg_ibfk_2` 
            FOREIGN KEY (`from`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create documents
    $sql = "CREATE TABLE `fcms_documents` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `name` VARCHAR(50) NOT NULL, 
                `description` TEXT NOT NULL, 
                `mime` VARCHAR(50) NOT NULL DEFAULT 'application/download',
                `user` INT(11) NOT NULL, 
                `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                PRIMARY KEY (`id`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter documents
    $sql = "ALTER TABLE `fcms_documents` 
            ADD CONSTRAINT `fcms_documents_ibfk_1` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create family tree
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

    // creat fcms_chat_online
    $sql = "CREATE TABLE fcms_chat_online (
                userID INT(11) NOT NULL,
                userName VARCHAR(64) NOT NULL,
                userRole INT(1) NOT NULL,
                channel INT(11) NOT NULL,
                dateTime DATETIME NOT NULL,
                ip VARBINARY(16) NOT NULL
            ) DEFAULT CHARSET=utf8 COLLATE=utf8_bin";
    mysql_query($sql) or die("$sql<br/>".mysql_error());

    // creat fcms_chat_messages
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
    mysql_query($sql) or die("$sql<br/>".mysql_error());

    // create user_awards
    $sql = "CREATE TABLE `fcms_user_awards` (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `user` INT(11) NOT NULL DEFAULT '0', 
                `award` VARCHAR(100) NOT NULL, 
                `month` INT(6) NOT NULL, 
                `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                `item_id` INT(11) NULL, 
                `count` SMALLINT(4) NOT NULL default '0', 
                PRIMARY KEY (`id`), 
                KEY `user` (`user`)
            ) 
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // alter user_awards
    $sql = "ALTER TABLE `fcms_user_awards` 
            ADD CONSTRAINT `fcms_user_awards_ibfk_1` 
            FOREIGN KEY (`user`) 
            REFERENCES `fcms_users` (`id`) 
            ON DELETE CASCADE";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create fcms_alerts
    $sql = "CREATE TABLE `fcms_alerts` (
                `id` INT(25) NOT NULL AUTO_INCREMENT, 
                `alert` VARCHAR(50) NOT NULL DEFAULT '0', 
                `user` INT(25) NOT NULL DEFAULT '0', 
                `hide` TINYINT(1) NOT NULL DEFAULT '1',
                PRIMARY KEY (`id`),
                KEY `alert_ind` (`alert`),
                KEY `user_ind` (`user`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create fcms_status
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
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());

    // create fcms_notification
    $sql = "CREATE TABLE `fcms_notification` (
                `id` INT(25) NOT NULL AUTO_INCREMENT,
                `user` INT(25) NOT NULL DEFAULT '0',
                `created_id` INT(25) NOT NULL DEFAULT '0',
                `notification` VARCHAR(50) DEFAULT NULL,
                `data` VARCHAR(50) NOT NULL,
                `read` TINYINT(1) NOT NULL DEFAULT '0',
                `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                `updated` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    mysql_query($sql) or die($sql . '<br/>' . mysql_error());
}
