<?php
/**
 * Cron
 *
 * Helper functions for the FCMS Scheduler.
 *
 * PHP versions 4 and 5
 *  
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2011 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.6
 */
if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - Initialized\n") or die(); }

/**
 * runFamilyNewsJob 
 * 
 * Checks if any user has an external blog setup.
 * Imports posts from those blogs if they haven't been imported already.
 * 
 * @return void
 */
function runFamilyNewsJob ()
{
    include_once 'familynews_class.php';

    $newsObj = new FamilyNews(1);

    // Get date we last checked for external news
    $sql = "SELECT `value` AS 'external_news_date'
            FROM `fcms_config`
            WHERE `name` = 'external_news_date'
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        die();
    }
    $r = mysql_fetch_assoc($result);

    $last_checked = strtotime($r['external_news_date']);

    // RFC 3339 format
    $atomDate = 0;
    if (!empty($r['external_news_date']))
    {
        $atomDate = date('Y-m-d\TH:i:s\Z', strtotime($r['external_news_date']));
    }

    // Get import blog settings
    $sql = "SELECT `user`, `blogger`, `tumblr`, `wordpress`, `posterous`
            FROM `fcms_user_settings`";

    $result = mysql_query($sql);
    if (!$result)
    {
        die();
    }
    if (mysql_num_rows($result) <= 0)
    {
        die();
    }

    $external_ids = $newsObj->getExternalPostIds();

    while ($r = mysql_fetch_assoc($result))
    {
        // Blogger
        if (!empty($r['blogger']))
        {
            $ret = $newsObj->importBloggerPosts($r['blogger'], $r['user'], $atomDate, $external_ids);
            if ($ret === false)
            {
                die();
            }
        }

        // Tumblr
        if (!empty($r['tumblr']))
        {
            $ret = $newsObj->importTumblrPosts($r['tumblr'], $r['user'], $atomDate, $external_ids);
            if ($ret === false)
            {
                die();
            }
        }

        // Wordpress
        if (!empty($r['wordpress']))
        {
            $ret = $newsObj->importWordpressPosts($r['wordpress'], $r['user'], $atomDate, $external_ids);
            if ($ret === false)
            {
                die();
            }
        }

        // Posterous
        if (!empty($r['posterous']))
        {
            $ret = $newsObj->importPosterousPosts($r['posterous'], $r['user'], $atomDate, $external_ids);
            if ($ret === false)
            {
                die();
            }
        }
    }

    // Update date we last imported news
    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE `fcms_config`
            SET `value` = '$now'
            WHERE `name` = 'external_news_date'";
    if (!mysql_query($sql))
    {
        die();
    }

    // Update date we last ran this job
    $sql = "UPDATE `fcms_schedule`
            SET `lastrun` = '$now'
            WHERE `type` = 'familynews'";
    if (!mysql_query($sql))
    {
        die();
    }
}

/**
 * runYouTubeJob 
 * 
 * Imports YouTube videos.
 * 
 * @return void
 */
function runYouTubeJob ()
{
    global $debug, $file;

    if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - Job Started\n") or die(); }

    require_once "constants.php";
    require_once "socialmedia.php";
    require_once "datetime.php";
    require_once "inc/gettext.inc";
    set_include_path(INC);
    require_once 'Zend/Loader.php';
    Zend_Loader::loadClass('Zend_Gdata_YouTube');
    Zend_Loader::loadClass('Zend_Gdata_AuthSub');
    Zend_Loader::loadClass('Zend_Gdata_App_Exception');

    if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - Includes included\n") or die(); }
    
    $existingIds = getExistingYouTubeIds();
    if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - Existing Ids\n") or die(); }

    // Get user's session tokens
    $sql = "SELECT u.`id`, s.`youtube_session_token`
            FROM `fcms_user_settings` AS s, `fcms_users` AS u
            WHERE s.`user` = u.`id`
            AND s.`youtube_session_token` IS NOT NULL";

    $result = mysql_query($sql);
    if (!$result)
    {
        die();
    }
    if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - Session Tokens\n") or die(); }

    $sessionTokens = array();

    while ($row = mysql_fetch_assoc($result))
    {
        $sessionTokens[$row['id']] = $row['youtube_session_token'];
    }

    $youtubeConfig  = getYouTubeConfigData();
    if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - Config Data\n") or die(); }

    // Get videos for each user
    foreach ($sessionTokens as $userId => $token)
    {
        if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - User [$userId]\n") or die(); }

        // Setup youtube api
        $httpClient     = getAuthSubHttpClient($youtubeConfig['youtube_key'], $token);
        $youTubeService = new Zend_Gdata_YouTube($httpClient);

        $feed = $youTubeService->getUserUploads('default');
        if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - User Feed\n") or die(); }

        $values = '';

        $videoCount = 0;

        foreach ($feed as $entry)
        {
            if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - User Feed>Entry\n") or die(); }
            $id = $entry->getVideoId();

            if (isset($existingIds[$id]))
            {
                if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - User Video already imported\n") or die(); }
                continue;
            }
            if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - User Feed>Entry Not existing\n") or die(); }

            $title       = htmlspecialchars($entry->getVideoTitle());
            if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - User Feed>Entry Video title\n") or die(); }
            $description = htmlspecialchars($entry->getVideoDescription());
            if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - User Feed>Entry Video desc\n") or die(); }
            $created     = formatDate('Y-m-d H:i:s', $entry->published);
            if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - User Feed>Entry Video created formatDate\n") or die(); }
            $duration    = $entry->getVideoDuration();
            if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - User Feed>Entry Video duration\n") or die(); }

            $height = '420';
            $width  = '780';
            $thumbs = $entry->getVideoThumbnails();
            if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - User Feed>Entry Video Data\n") or die(); }

            if (count($thumbs) > 0)
            {
                $height = $thumbs[0]['height'];
                $width  = $thumbs[0]['width'];
            }

            $title       = cleanInput($title);
            $description = cleanInput($description);

            $values .= "('$id', '$title', '$description', 'youtube', '$height', '$width', '$created', '$userId', NOW(), '$userId'),";

            $videoCount++;
            if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - Video found\n") or die(); }
        }
        if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - User Done - Found [$videoCount] Videos\n") or die(); }

        if ($videoCount > 0)
        {
            if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - Preparing Insert [$videoCount] Videos\n") or die(); }
            $values = substr($values, 0, -1); // remove comma

            $sql = "INSERT INTO `fcms_video` (`source_id`, `title`, `description`, `source`, `height`, `width`, `created`, `created_id`, `updated`, `updated_id`)
                    VALUES $values";
            if (!mysql_query($sql))
            {
                if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - Insert Videos failed:\n [ $sql ]\n") or die(); }
                die();
            }
            if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - Insert Videos found\n") or die(); }
        }
    }
    if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - All User's Done\n") or die(); }

    // Update date we last ran this job
    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE `fcms_schedule`
            SET `lastrun` = '$now'
            WHERE `type` = 'youtube'";
    if (!mysql_query($sql))
    {
        die();
    }
    if ($debug) { fwrite($file, "[".date('Y-m-d H:i:s')."] inc/cron.php - YouTube - Updated lastrun\n") or die(); }
}
