<?php

/**
 * getFacebookConfigData 
 * 
 * Will return an array of the facebook app id and app secret.
 * 
 * @return array
 */
function getFacebookConfigData ()
{
    $sql = "SELECT `fb_app_id`, `fb_secret`
            FROM `fcms_config`
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return false;
    }

    return mysql_fetch_assoc($result);
}

/**
 * getUserFacebookAccessToken 
 * 
 * Returns the user's saved access token from the db.  Or null if they don't have one.
 * 
 * @param int $user 
 * 
 * @return string
 */
function getUserFacebookAccessToken ($user)
{
    $sql = "SELECT `fb_access_token`
            FROM `fcms_user_settings`
            WHERE `user` = '$user'
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return;
    }

    $r = mysql_fetch_assoc($result);

    return $r['fb_access_token'];
}

/**
 * getVimeoConfigData 
 * 
 * Will return an array of the vimeo consumer key and secret.
 * 
 * @return array
 */
function getVimeoConfigData ()
{
    $sql = "SELECT `vimeo_key`, `vimeo_secret`
            FROM `fcms_config`
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return false;
    }

    return mysql_fetch_assoc($result);
}

/**
 * getVimeoUserData 
 * 
 * @param int $user
 * 
 * @return void
 */
function getVimeoUserData ($user)
{
    $sql = "SELECT `vimeo_access_token`, `vimeo_access_token_secret`
            FROM `fcms_user_settings`
            WHERE `user` = '$user'
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return;
    }

    $r = mysql_fetch_assoc($result);

    return $r;
}

/**
 * getFoursquareConfigData 
 * 
 * @return void
 */
function getFoursquareConfigData ()
{
    $sql = "SELECT `fs_client_id`, `fs_client_secret`, `fs_callback_url`
            FROM `fcms_config`
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return;
    }

    $r = mysql_fetch_assoc($result);

    return $r;
}

/**
 * getFoursquareUserData 
 * 
 * @param int $user 
 * 
 * @return void
 */
function getFoursquareUserData ($user)
{
    $sql = "SELECT `fs_user_id`, `fs_access_token`
            FROM `fcms_user_settings`
            WHERE `user` = '$user'
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('User Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return;
    }

    $r = mysql_fetch_assoc($result);

    return $r;
}
