<?php

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

    return true;
}

/**
 * install 
 * 
 * Make any directory changes needed from 2.5 to current version.
 * 
 * @return boolean
 */
function install ()
{
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
    global $cfg_mysql_db, $cfg_sitename, $cfg_contact_email, $cfg_use_news, $cfg_use_prayers;

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
