<?php
/**
 * Cron.
 *
 * Helper functions for the FCMS Scheduler.
 *
 * PHP versions 4 and 5
 *
 * @category  FCMS
 *
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @copyright 2011 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @link      http://www.familycms.com/wiki/
 * @since     2.6
 */

/**
 * runAwardsJob.
 *
 * @return void
 */
function runAwardsJob()
{
    global $fcmsError, $fcmsDatabase, $fcmsUser;

    include_once 'awards_class.php';

    $awards = new Awards($fcmsError, $fcmsDatabase, $fcmsUser);

    if (!$awards->calculateMonthlyAwards()) {
        logError(__FILE__.' ['.__LINE__.'] - Could not calculate monthly awards.');
        die();
    }

    if (!$awards->calculateAchievementAwards()) {
        logError(__FILE__.' ['.__LINE__.'] - Could not calculate achievement awards.');
        die();
    }

    // Update date we last ran this job
    updateLastRun(date('Y-m-d H:i:s'), 'awards');
}

/**
 * runFamilyNewsJob.
 *
 * Checks if any user has an external blog setup.
 * Imports posts from those blogs if they haven't been imported already.
 *
 * @return void
 */
function runFamilyNewsJob()
{
    include_once 'familynews_class.php';

    $fcmsError = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $newsObj = new FamilyNews($fcmsError, $fcmsDatabase, 1);

    // Get date we last checked for external news
    $sql = "SELECT `value` AS 'external_news_date'
            FROM `fcms_config`
            WHERE `name` = 'external_news_date'
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false) {
        logError(__FILE__.' ['.__LINE__.'] - Could not get external_news_date.');
        die();
    }

    $last_checked = strtotime($r['external_news_date']);

    // RFC 3339 format
    $atomDate = 0;
    if (!empty($r['external_news_date'])) {
        $atomDate = date('Y-m-d\TH:i:s\Z', strtotime($r['external_news_date']));
    }

    // Get import blog settings
    $sql = 'SELECT `user`, `blogger`, `tumblr`, `wordpress`, `posterous`
            FROM `fcms_user_settings`';

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false) {
        logError(__FILE__.' ['.__LINE__.'] - Could not get blog settings.');
        die();
    }

    if (count($rows) <= 0) {
        logError(__FILE__.' ['.__LINE__.'] - No blog settings found.');
        updateLastRun(date('Y-m-d H:i:s'), 'familynews');
        die();
    }

    $external_ids = $newsObj->getExternalPostIds();

    foreach ($rows as $r) {
        // Blogger
        if (!empty($r['blogger'])) {
            $ret = $newsObj->importBloggerPosts($r['blogger'], $r['user'], $atomDate, $external_ids);
            if ($ret === false) {
                logError(__FILE__.' ['.__LINE__.'] - No posts to import from blogger for user ['.$r['user'].'].');
                continue;
            }
        }

        // Tumblr
        if (!empty($r['tumblr'])) {
            $ret = $newsObj->importTumblrPosts($r['tumblr'], $r['user'], $atomDate, $external_ids);
            if ($ret === false) {
                logError(__FILE__.' ['.__LINE__.'] - No posts to import from tumblr for user ['.$r['user'].'].');
                continue;
            }
        }

        // Wordpress
        if (!empty($r['wordpress'])) {
            $ret = $newsObj->importWordpressPosts($r['wordpress'], $r['user'], $atomDate, $external_ids);
            if ($ret === false) {
                logError(__FILE__.' ['.__LINE__.'] - No posts to import from wordpress for user ['.$r['user'].'].');
                continue;
            }
        }

        // Posterous
        if (!empty($r['posterous'])) {
            $ret = $newsObj->importPosterousPosts($r['posterous'], $r['user'], $atomDate, $external_ids);
            if ($ret === false) {
                logError(__FILE__.' ['.__LINE__.'] - No posts to import from posterous for user ['.$r['user'].'].');
                continue;
            }
        }
    }

    // Update date we last imported news
    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE `fcms_config`
            SET `value` = '$now'
            WHERE `name` = 'external_news_date'";

    if (!$fcmsDatabase->update($sql)) {
        logError(__FILE__.' ['.__LINE__.'] - Could not update last imported news date.');
        die();
    }

    // Update date we last ran this job
    updateLastRun($now, 'familynews');
}

/**
 * runYouTubeJob.
 *
 * Imports YouTube videos.
 *
 * @return void
 */
function runYouTubeJob()
{
    require_once 'constants.php';
    require_once 'socialmedia.php';
    require_once 'datetime.php';
    require_once THIRDPARTY.'php-gettext/gettext.inc';

    set_include_path(get_include_path().PATH_SEPARATOR.THIRDPARTY.'google-api-php-client/src/');
    require_once THIRDPARTY.'google-api-php-client/src/Google/autoload.php';

    $fcmsError = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $existingIds = getExistingYouTubeIds();

    // Get all google session tokens
    $sql = 'SELECT u.`id`, s.`google_session_token`
            FROM `fcms_user_settings` AS s
            LEFT JOIN `fcms_users` AS u ON s.`user` = u.`id`
            WHERE s.`google_session_token` IS NOT NULL';

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false) {
        logError(__FILE__.' ['.__LINE__.'] - Could not get youtube tokens.');
        die();
    }

    $sessionTokens = [];
    foreach ($rows as $row) {
        $sessionTokens[$row['id']] = $row['google_session_token'];
    }

    // Get videos for each user
    foreach ($sessionTokens as $userId => $token) {
        $values = '';
        $videoCount = 0;
        $params = [];

        try {
            $googleClient = getAuthedGoogleClient($userId);

            $youtube = new Google_Service_YouTube($googleClient);

            $channelsResponse = $youtube->channels->listChannels('id,snippet,status,contentDetails,statistics', ['mine' => true]);

            foreach ($channelsResponse['items'] as $channel) {
                $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

                $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', [
                    'playlistId' => $uploadsListId,
                    'maxResults' => 50,
                ]);

                foreach ($playlistItemsResponse['items'] as $playlistItem) {
                    $id = $playlistItem['snippet']['resourceId']['videoId'];
                    $title = $playlistItem['snippet']['title'];
                    $description = $playlistItem['snippet']['description'];
                    $created = formatDate('Y-m-d H:i:s', $playlistItem['snippet']['publishedAt']);

                    if (isset($existingIds[$id])) {
                        continue;
                    }

                    $values .= "(?, ?, ?, 'youtube', ?, ?, NOW(), ?),";

                    $params[] = $id;
                    $params[] = $title;
                    $params[] = $description;
                    $params[] = $created;
                    $params[] = $userId;
                    $params[] = $userId;

                    $videoCount++;
                }
            }
        } catch (Exception $e) {
            $errors = print_r($e, true);

            logError(__FILE__.' ['.__LINE__.'] - Could not upload videos to YouTube. '.$errors);
            die();
        }

        if ($videoCount > 0) {
            $values = substr($values, 0, -1); // remove comma

            $sql = "INSERT INTO `fcms_video`
                        (`source_id`, `title`, `description`, `source`, `created`, `created_id`, `updated`, `updated_id`)
                    VALUES $values";
            if (!$fcmsDatabase->insert($sql, $params)) {
                logError(__FILE__.' ['.__LINE__.'] - Could not insert new video to db.');
                die();
            }
        }
    }

    // Update date we last ran this job
    updateLastRun(date('Y-m-d H:i:s'), 'youtube');
}

/**
 * runInstagramJob.
 *
 * @return void
 */
function runInstagramJob()
{
    require_once 'inc/config_inc.php';
    require_once 'inc/constants.php';
    require_once 'inc/socialmedia.php';
    require_once 'inc/utils.php';
    require_once THIRDPARTY.'php-gettext/gettext.inc';
    require_once THIRDPARTY.'Instagram.php';

    $fcmsError = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    // Get user's access tokens
    $sql = 'SELECT u.`id`, s.`instagram_access_token`
            FROM `fcms_user_settings` AS s, `fcms_users` AS u
            WHERE s.`user` = u.`id`
            AND s.`instagram_auto_upload` = 1
            AND s.`instagram_access_token` IS NOT NULL';

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false) {
        logError(__FILE__.' ['.__LINE__.'] - Could not get instagram access tokens.');
        die();
    }

    $accessTokens = [];

    foreach ($rows as $row) {
        $accessTokens[$row['id']] = $row['instagram_access_token'];
    }

    $config = getInstagramConfigData();
    $existingIds = getExistingInstagramIds();

    // Get pics for each user
    foreach ($accessTokens as $userId => $token) {
        $categoryId = getUserInstagramCategory($userId);
        $instagram = new Instagram($config['instagram_client_id'], $config['instagram_client_secret'], $token);

        try {
            $feed = $instagram->get('users/self/media/recent');
        } catch (InstagramApiError $e) {
            logError(__FILE__.' ['.__LINE__.'] - Could not get user instagram data. - '.$e->getMessage());
            die();
        }

        $sql = 'INSERT INTO `fcms_gallery_photos`
                    (`date`, `path`, `caption`, `category`, `user`)
                VALUES ';

        foreach ($feed->data as $photo) {
            $sourceId = $photo->id;
            $thumbnail = $photo->images->thumbnail->url;
            $medium = $photo->images->low_resolution->url;
            $full = $photo->images->standard_resolution->url;
            $caption = $photo->caption->text;
            $caption .= ' ['.sprintf(T_('Filter: %s.'), $photo->filter).']';

            // Skip existing photos
            if (isset($existingIds[$sourceId])) {
                continue;
            }

            // Save external paths
            $sql = 'INSERT INTO `fcms_gallery_external_photo`
                        (`source_id`, `thumbnail`, `medium`, `full`)
                    VALUES
                        (?, ?, ?, ?)';

            $params = [
                $sourceId,
                $thumbnail,
                $medium,
                $full,
            ];

            $id = $fcmsDatabase->insert($sql, $params);
            if ($id === false) {
                logError(__FILE__.' ['.__LINE__.'] - Could not save external photos.');
                die();
            }

            // Insert new photo
            $sql = 'INSERT INTO `fcms_gallery_photos`
                        (`date`, `external_id`, `caption`, `category`, `user`)
                    VALUES
                        (NOW(), ?, ?, ?, ?)';

            $params = [
                $id,
                $caption,
                $categoryId,
                $userId,
            ];

            if (!$fcmsDatabase->insert($sql, $params)) {
                logError(__FILE__.' ['.__LINE__.'] - Could not insert new photo.');
                die();
            }
        }
    }

    // Update date we last ran this job
    updateLastRun(date('Y-m-d H:i:s'), 'instagram');
}

/**
 * updateLastRun.
 *
 * @param date   $now
 * @param string $type
 *
 * @return void
 */
function updateLastRun($now, $type)
{
    $fcmsError = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    // Update date we last ran this job
    $sql = "UPDATE `fcms_schedule`
            SET `lastrun` = '$now'
            WHERE `type` = '$type'";

    if (!$fcmsDatabase->update($sql, [$now, $type])) {
        logError(__FILE__.' ['.__LINE__.'] - Could not update last run date for '.$type.' job.');
        die();
    }
}
