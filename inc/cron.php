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

/**
 * runAwardsJob 
 * 
 * @return void
 */
function runAwardsJob ()
{
    include_once 'awards_class.php';

    $awards = new Awards(1);

    if (!$awards->calculateMonthlyAwards())
    {
        logError(__FILE__.' ['.__LINE__.'] - Could not calculate monthly awards.');
        die();
    }

    if (!$awards->calculateAchievementAwards())
    {
        logError(__FILE__.' ['.__LINE__.'] - Could not calculate achievement awards.');
        die();
    }

    // Update date we last ran this job
    updateLastRun(date('Y-m-d H:i:s'), 'awards');
}

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

    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    // Get date we last checked for external news
    $sql = "SELECT `value` AS 'external_news_date'
            FROM `fcms_config`
            WHERE `name` = 'external_news_date'
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false)
    {
        logError(__FILE__.' ['.__LINE__.'] - Could not get external_news_date.');
        die();
    }

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

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        logError(__FILE__.' ['.__LINE__.'] - Could not get blog settings.');
        die();
    }

    if (count($rows) <= 0)
    {
        if (debugOn())
        {
            logError(__FILE__.' ['.__LINE__.'] - No blog settings found.');
        }

        updateLastRun(date('Y-m-d H:i:s'), 'familynews');
        die();
    }

    $external_ids = $newsObj->getExternalPostIds();

    foreach ($rows as $r)
    {
        // Blogger
        if (!empty($r['blogger']))
        {
            $ret = $newsObj->importBloggerPosts($r['blogger'], $r['user'], $atomDate, $external_ids);
            if ($ret === false)
            {
                if (debugOn())
                {
                    logError(__FILE__.' ['.__LINE__.'] - No posts to import from blogger for user ['.$r['user'].'].');
                }
                continue;
            }
        }

        // Tumblr
        if (!empty($r['tumblr']))
        {
            $ret = $newsObj->importTumblrPosts($r['tumblr'], $r['user'], $atomDate, $external_ids);
            if ($ret === false)
            {
                if (debugOn())
                {
                    logError(__FILE__.' ['.__LINE__.'] - No posts to import from tumblr for user ['.$r['user'].'].');
                }
                continue;
            }
        }

        // Wordpress
        if (!empty($r['wordpress']))
        {
            $ret = $newsObj->importWordpressPosts($r['wordpress'], $r['user'], $atomDate, $external_ids);
            if ($ret === false)
            {
                if (debugOn())
                {
                    logError(__FILE__.' ['.__LINE__.'] - No posts to import from wordpress for user ['.$r['user'].'].');
                }
                continue;
            }
        }

        // Posterous
        if (!empty($r['posterous']))
        {
            $ret = $newsObj->importPosterousPosts($r['posterous'], $r['user'], $atomDate, $external_ids);
            if ($ret === false)
            {
                if (debugOn())
                {
                    logError(__FILE__.' ['.__LINE__.'] - No posts to import from posterous for user ['.$r['user'].'].');
                }
                continue;
            }
        }
    }

    // Update date we last imported news
    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE `fcms_config`
            SET `value` = '$now'
            WHERE `name` = 'external_news_date'";

    if (!$fcmsDatabase->update($sql))
    {
        logError(__FILE__.' ['.__LINE__.'] - Could not update last imported news date.');
        die();
    }

    // Update date we last ran this job
    updateLastRun($now, 'familynews');
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
    global $file;

    require_once 'constants.php';
    require_once 'socialmedia.php';
    require_once 'datetime.php';
    require_once THIRDPARTY.'gettext.inc';
    set_include_path(THIRDPARTY);
    require_once 'Zend/Loader.php';
    Zend_Loader::loadClass('Zend_Gdata_YouTube');
    Zend_Loader::loadClass('Zend_Gdata_AuthSub');
    Zend_Loader::loadClass('Zend_Gdata_App_Exception');

    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $existingIds = getExistingYouTubeIds();

    // Get user's session tokens
    $sql = "SELECT u.`id`, s.`youtube_session_token`
            FROM `fcms_user_settings` AS s, `fcms_users` AS u
            WHERE s.`user` = u.`id`
            AND s.`youtube_session_token` IS NOT NULL";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        logError(__FILE__.' ['.__LINE__.'] - Could not get youtube tokens.');
        die();
    }

    $sessionTokens = array();

    foreach ($rows as $row)
    {
        $sessionTokens[$row['id']] = $row['youtube_session_token'];
    }

    $youtubeConfig  = getYouTubeConfigData();

    // Get videos for each user
    foreach ($sessionTokens as $userId => $token)
    {
        // Setup youtube api
        $httpClient     = getYouTubeAuthSubHttpClient($youtubeConfig['youtube_key'], $token);
        $youTubeService = new Zend_Gdata_YouTube($httpClient);

        $feed = $youTubeService->getUserUploads('default');

        $values     = '';
        $videoCount = 0;
        $params     = array();

        foreach ($feed as $entry)
        {
            $id = $entry->getVideoId();

            if (isset($existingIds[$id]))
            {
                if (debugOn())
                {
                    logError(__FILE__.' ['.__LINE__.'] - Video ['.$id.'] for user ['.$userId.'] already imported.');
                }
                continue;
            }

            $title       = htmlspecialchars($entry->getVideoTitle());
            $description = htmlspecialchars($entry->getVideoDescription());
            $created     = formatDate('Y-m-d H:i:s', $entry->published);
            $duration    = $entry->getVideoDuration();

            $height = '420';
            $width  = '780';
            $thumbs = $entry->getVideoThumbnails();

            if (count($thumbs) > 0)
            {
                $height = $thumbs[0]['height'];
                $width  = $thumbs[0]['width'];
            }

            $values .= "(?, ?, ?, 'youtube', ?, ?, ?, ?, NOW(), ?),";

            $params[] = $id;
            $params[] = $title;
            $params[] = $description;
            $params[] = $height;
            $params[] = $width;
            $params[] = $created;
            $params[] = $userId;
            $params[] = $userId;

            $videoCount++;
        }

        if ($videoCount > 0)
        {
            $values = substr($values, 0, -1); // remove comma

            $sql = "INSERT INTO `fcms_video`
                        (`source_id`, `title`, `description`, `source`, `height`, `width`, `created`, `created_id`, `updated`, `updated_id`)
                    VALUES $values";

            if (!$fcmsDatabase->insert($sql, $params))
            {
                logError(__FILE__.' ['.__LINE__.'] - Could not insert new video to db.');
                die();
            }
        }
    }

    // Update date we last ran this job
    updateLastRun(date('Y-m-d H:i:s'), 'youtube');
}

/**
 * runInstagramJob 
 * 
 * @return void
 */
function runInstagramJob ()
{
    require_once 'inc/config_inc.php';
    require_once 'inc/constants.php';
    require_once 'inc/socialmedia.php';
    require_once 'inc/utils.php';
    require_once THIRDPARTY.'gettext.inc';
    require_once THIRDPARTY.'Instagram.php';

    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    // Get user's access tokens
    $sql = "SELECT u.`id`, s.`instagram_access_token`
            FROM `fcms_user_settings` AS s, `fcms_users` AS u
            WHERE s.`user` = u.`id`
            AND s.`instagram_auto_upload` = 1
            AND s.`instagram_access_token` IS NOT NULL";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        logError(__FILE__.' ['.__LINE__.'] - Could not get instagram access tokens.');
        die();
    }

    $accessTokens = array();

    foreach ($rows as $row)
    {
        $accessTokens[$row['id']] = $row['instagram_access_token'];
    }

    $config      = getInstagramConfigData();
    $existingIds = getExistingInstagramIds();

    // Get pics for each user
    foreach ($accessTokens as $userId => $token)
    {
        $categoryId = getUserInstagramCategory($userId);
        $instagram  = new Instagram($config['instagram_client_id'], $config['instagram_client_secret'], $token);

        try
        {
            $feed = $instagram->get('users/self/media/recent');
        }
        catch (InstagramApiError $e)
        {
            logError(__FILE__.' ['.__LINE__.'] - Could not get user instagram data. - '.$e->getMessage());
            die();
        }

        $sql = "INSERT INTO `fcms_gallery_photos`
                    (`date`, `path`, `caption`, `category`, `user`)
                VALUES ";

        foreach ($feed->data as $photo)
        {
            $sourceId  = $photo->id;
            $thumbnail = $photo->images->thumbnail->url;
            $medium    = $photo->images->low_resolution->url;
            $full      = $photo->images->standard_resolution->url;
            $caption   = sprintf(T_('Imported from Instagram.  Filter: %s.'), $photo->filter);

            // Skip existing photos
            if (isset($existingIds[$sourceId]))
            {
                continue;
            }

            // Save external paths
            $sql = "INSERT INTO `fcms_gallery_external_photo`
                        (`source_id`, `thumbnail`, `medium`, `full`)
                    VALUES
                        (?, ?, ?, ?)";

            $params = array(
                $sourceId,
                $thumbnail,
                $medium,
                $full
            );

            $id = $fcmsDatabase->insert($sql, $params);
            if ($id === false)
            {
                logError(__FILE__.' ['.__LINE__.'] - Could not save external photos.');
                die();
            }

            // Insert new photo
            $sql = "INSERT INTO `fcms_gallery_photos`
                        (`date`, `external_id`, `caption`, `category`, `user`)
                    VALUES
                        (NOW(), ?, ?, ?, ?)";

            $params = array(
                $id,
                $caption,
                $categoryId,
                $userId
            );

            if (!$fcmsDatabase->insert($sql, $params))
            {
                logError(__FILE__.' ['.__LINE__.'] - Could not insert new photo.');
                die();
            }
        }
    }

    // Update date we last ran this job
    updateLastRun(date('Y-m-d H:i:s'), 'instagram');
}

/**
 * updateLastRun 
 * 
 * @param date   $now 
 * @param string $type 
 * 
 * @return void
 */
function updateLastRun ($now, $type)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    // Update date we last ran this job
    $sql = "UPDATE `fcms_schedule`
            SET `lastrun` = '$now'
            WHERE `type` = '$type'";

    if (!$fcmsDatabase->update($sql, array($now, $type)))
    {
        logError(__FILE__.' ['.__LINE__.'] - Could not update last run date for '.$type.' job.');
        die();
    }
}
