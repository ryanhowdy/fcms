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
    $sql = "SELECT `name`, `value`
            FROM `fcms_config`
            WHERE `name` = 'fb_app_id'
            OR `name` = 'fb_secret'";

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

    $row = array();
    while ($r = mysql_fetch_assoc($result))
    {
        $row[$r['name']] = $r['value'];
    }

    return $row;
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
    $sql = "SELECT `name`, `value`
            FROM `fcms_config`
            WHERE `name` = 'vimeo_key'
            OR `name` = 'vimeo_secret'";

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

    $row = array();
    while ($r = mysql_fetch_assoc($result))
    {
        $row[$r['name']] = $r['value'];
    }

    return $row;
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
    $sql = "SELECT `name`, `value`
            FROM `fcms_config`
            WHERE `name` = 'fs_client_id'
            OR `name` = 'fs_client_secret'
            OR `name` = 'fs_callback_url'";

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

    $row = array();
    while ($r = mysql_fetch_assoc($result))
    {
        $row[$r['name']] = $r['value'];
    }

    return $row;
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

/**
 * getFoursquareUsersData
 * 
 * Returns an array of arrays containing the users with foursquare setup.
 *
 *     Array
 *     (
 *         [0] => Array
 *             (
 *                 [user_id] => 9999
 *                 [access_token] => ABC123
 *             )
 *     
 *     ) 
 * 
 * @return array
 */
function getFoursquareUsersData ()
{
    $sql = "SELECT `user` AS 'userid', `fs_user_id`, `fs_access_token`, `fname`, `lname`, 
                `avatar`, `gravatar`, `timezone`
            FROM `fcms_user_settings` AS s, `fcms_users` AS u
            WHERE `fs_user_id` IS NOT NULL
            AND s.`user` = u.`id`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }
    if (mysql_num_rows($result) <= 0)
    {
        $users[0] = array();
        return $users;
    }

    $i = 0;
    while($row = mysql_fetch_assoc($result))
    {
        $users[$i] = array(
            'fcms_user_id' => $row['userid'],
            'user_id'      => $row['fs_user_id'],
            'access_token' => $row['fs_access_token'],
            'name'         => $row['fname'].' '.$row['lname'],
            'avatar'       => $row['avatar'],
            'gravatar'     => $row['gravatar'],
            'timezone'     => $row['timezone'],
        );
        $i++;
    }

    return $users;
}

/**
 * getYouTubeConfigData 
 * 
 * @return void
 */
function getYouTubeConfigData ()
{
    if (isset($_SESSION['youtube_key']) && !empty($_SESSION['youtube_key']))
    {
        return array('youtube_key' => cleanInput($_SESSION['youtube_key']));
    }

    $sql = "SELECT `name`, `value`
            FROM `fcms_config`
            WHERE `name` = 'youtube_key'";

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

    $row = array();

    $_SESSION['youtube_key'] = cleanInput($r['value']);

    $row[$r['name']] = $_SESSION['youtube_key'];

    return $row;
}

/**
 * getYouTubeUserData 
 * 
 * @param int $user 
 * 
 * @return void
 */
function getYouTubeUserData ($user)
{
    $sql = "SELECT `youtube_session_token`
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

/**
 * getAuthSubHttpClient 
 * 
 * @param string $key   the developer key
 * @param string $token optional user's authenticated session token 
 * 
 * @return Zend_Http_Client An authenticated client.
 */
function getAuthSubHttpClient($key, $token = '')
{
    if ($token == '')
    {
        if (isset($_SESSION['youtube_session_token']))
        {
            $token = $_SESSION['youtube_session_token'];
        }
        else
        {
            print '
                <div class="error-alert">
                    <p>'.T_('Missing or invalid YouTube session token.').'</p>
                </div>';

            return false;
        }
    }

    try
    {
        $httpClient = Zend_Gdata_AuthSub::getHttpClient($token);
    }
    catch (Zend_Gdata_App_Exception $e)
    {
        print '
            <div class="error-alert">
                <p>'.T_('Could not connect to YouTube API.  Your YouTube session token may be invalid.').'</p>
                <p><i>'.$e->getMessage().'</i></p>
            </div>';

        return false;
    }

    $httpClient->setHeaders('X-GData-Key', 'key='. $key);

    return $httpClient;
}
