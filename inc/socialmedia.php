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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `name`, `value`
            FROM `fcms_config`
            WHERE `name` = 'fb_app_id'
            OR `name` = 'fb_secret'";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        return false;
    }

    $data = array();

    foreach ($rows as $r)
    {
        $data[$r['name']] = $r['value'];
    }

    return $data;
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `fb_access_token`
            FROM `fcms_user_settings`
            WHERE `user` = ?
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql, $user);
    if ($r === false)
    {
        return;
    }

    if (count($r) <= 0)
    {
        return;
    }

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `name`, `value`
            FROM `fcms_config`
            WHERE `name` = 'vimeo_key'
            OR `name` = 'vimeo_secret'";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        return false;
    }

    if (count($rows) <= 0)
    {
        return false;
    }

    $data = array();
    foreach ($rows as $r)
    {
        $data[$r['name']] = $r['value'];
    }

    return $data;
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `vimeo_access_token`, `vimeo_access_token_secret`
            FROM `fcms_user_settings`
            WHERE `user` = ?
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql, $user);
    if ($r === false)
    {
        return false;
    }

    if (empty($r))
    {
        return false;
    }

    return $r;
}

/**
 * getFoursquareConfigData 
 * 
 * @return void
 */
function getFoursquareConfigData ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `name`, `value`
            FROM `fcms_config`
            WHERE `name` = 'fs_client_id'
            OR `name` = 'fs_client_secret'
            OR `name` = 'fs_callback_url'";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        return false;
    }

    if (count($rows) <= 0)
    {
        return false;
    }

    $data = array();

    foreach ($rows as $r)
    {
        $data[$r['name']] = $r['value'];
    }

    return $data;
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `fs_user_id`, `fs_access_token`
            FROM `fcms_user_settings`
            WHERE `user` = ?
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql, $user);
    if ($r === false)
    {
        return false;
    }

    if (empty($r))
    {
        return false;
    }

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `user` AS 'userid', `fs_user_id`, `fs_access_token`, `fname`, `lname`, 
                `avatar`, `gravatar`, `timezone`
            FROM `fcms_user_settings` AS s, `fcms_users` AS u
            WHERE `fs_user_id` IS NOT NULL
            AND s.`user` = u.`id`";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        return false;
    }
    if (count($rows) <= 0)
    {
        $users[0] = array();
        return $users;
    }

    $i = 0;

    foreach ($rows as $row)
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    if (isset($_SESSION['youtube_key']) && !empty($_SESSION['youtube_key']))
    {
        return array('youtube_key' => $_SESSION['youtube_key']);
    }

    $sql = "SELECT `name`, `value`
            FROM `fcms_config`
            WHERE `name` = 'youtube_key'";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false)
    {
        return;
    }

    if (empty($r))
    {
        return;
    }

    $data = array();

    $_SESSION['youtube_key'] = $r['value'];
    $data[$r['name']]        = $_SESSION['youtube_key'];

    return $data;
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `youtube_session_token`
            FROM `fcms_user_settings`
            WHERE `user` = ?
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql, $user);
    if ($r === false)
    {
        return;
    }

    if (count($r) <= 0)
    {
        return;
    }

    return $r;
}

/**
 * getYouTubeAuthSubHttpClient 
 * 
 * @param string $key   the developer key
 * @param string $token optional user's authenticated session token 
 * 
 * @return Zend_Http_Client An authenticated client.
 */
function getYouTubeAuthSubHttpClient($key, $token = '')
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

/**
 * getInstagramConfigData 
 * 
 * @return void
 */
function getInstagramConfigData ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    if (   isset($_SESSION['instagram_client_id']) 
        && isset($_SESSION['instagram_client_secret'])
        && !empty($_SESSION['instagram_client_id'])
        && !empty($_SESSION['instagram_client_secret']))
    {
        return array(
            'instagram_client_id'     => $_SESSION['instagram_client_id'],
            'instagram_client_secret' => $_SESSION['instagram_client_secret']
        );
    }

    $sql = "SELECT `name`, `value`
            FROM `fcms_config`
            WHERE `name` = 'instagram_client_id'
            OR `name` = 'instagram_client_secret'";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        return;
    }

    if (count($rows) <= 0)
    {
        return;
    }

    $data = array();

    foreach ($rows as $r)
    {
        $data[$r['name']]     = $r['value'];
        $_SESSION[$r['name']] = $r['value'];
    }

    return $data;
}

/**
 * getUserInstagramAccessToken
 * 
 * @param int $user 
 * 
 * @return void
 */
function getUserInstagramAccessToken ($user)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `instagram_access_token`
            FROM `fcms_user_settings`
            WHERE `user` = ?
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql, $user);
    if ($r === false)
    {
        return null;
    }

    if (empty($r))
    {
        return null;
    }

    if (empty($r['instagram_access_token']))
    {
        return null;
    }

    return $r['instagram_access_token'];
}

/**
 * getUserPicasaSessionToken
 * 
 * @param int $user 
 * 
 * @return void
 */
function getUserPicasaSessionToken ($user)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `picasa_session_token`
            FROM `fcms_user_settings`
            WHERE `user` = ?
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql, $user);
    if ($r === false)
    {
        return null;
    }

    if (empty($r))
    {
        return null;
    }

    return $r['picasa_session_token'];
}
