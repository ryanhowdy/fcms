<?php
/**
 * Video
 * 
 * PHP version 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2011 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.6
 */
session_start();

define('URL_PREFIX', '');

require 'fcms.php';

load('datetime', 'socialmedia', 'youtube', 'comments');

init();

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Video Gallery'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

control();
exit();


/**
 * control 
 * 
 * The controlling structure for this script.
 * 
 * @return void
 */
function control ()
{
    global $currentUserId;

    // AJAX
    if (isset($_GET['check_status']))
    {
        if (isset($_SESSION['source_id']))
        {
            $sessionToken  = getSessionToken($currentUserId);
            echo getUploadStatus($_SESSION['source_id'], $sessionToken);
            return;
        }

        echo 'n/a';
        return;
    }

    if (isset($_GET['upload']))
    {
        // YouTube
        if ($_GET['upload'] == 'youtube')
        {
            // Step 3 - YouTube Response
            if (isset($_GET['status']) && isset($_GET['id']))
            {
                displayYouTubeUploadStatusPage();
            }
            // Step 2 - Video
            elseif (isset($_POST['upload_data']))
            {
                displayYouTubeUploadFilePage();
            }
            // Step 1 - Title/Desc
            else
            {
                displayYouTubeUploadPage();
            }
        }
        // Vimeo
        else
        {
            displayVimeoUploadPage();
        }
    }
    elseif (isset($_GET['u']))
    {
        if (isset($_GET['id']))
        {
            if (isset($_POST['addcomment']))
            {
                displayCommentSubmit();
            }
            elseif (isset($_POST['remove_video']))
            {
                displayRemoveVideoSubmit();
            }
            elseif (isset($_POST['delete_video']))
            {
                displayDeleteVideoSubmit();
            }
            else
            {
                displayVideoPage();
            }
        }
        else
        {
            displayUserVideosPage();
        }
    }
    elseif (isset($_GET['members']))
    {
        displayMembersListPage();
    }
    else
    {
        displayLatestPage();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    global $currentUserId, $TMPL;

    $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    initYouTubeVideoStatus(\''.T_('This page will automatically refresh').'\');
    initHideVideoEdit(\''.T_('Edit Video').'\');
});
//]]>
</script>';

    include_once getTheme($currentUserId).'header.php';

    echo '
        <div id="video" class="centercontent clearfix">

            <div id="actions_menu" class="clearfix">
                <ul>
                    <li><a href="?upload=youtube">'.T_('Upload to YouTube').'</a></li>
                </ul>
            </div>';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    global $currentUserId, $TMPL;

    echo '
        </div><!-- /centercontent -->';

    include_once getTheme($currentUserId).'footer.php';
}

/**
 * checkUserAuthedYouTube 
 * 
 * Check to make sure the user is connected and authed at YouTube.
 * 
 * Assumed displayHeader() already sent
 * 
 * @return void
 */
function checkUserAuthedYouTube ()
{
    global $currentUserId;

    // Get session token
    $sql = "SELECT `youtube_session_token`
            FROM `fcms_user_settings`
            WHERE `user` = '$currentUserId'
            AND `youtube_session_token` IS NOT NULL
            AND `youtube_session_token` != ''";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        echo '
            <div class="info-alert">
                <h2>'.T_('Not connected to YouTube.').'</h2>
                <p>'.T_('The video gallery relies on YouTube.  You must create a YouTube account and connect it with your Family Connections account.').'</p>
                <p><a href="settings.php?view=socialmedia">'.T_('Connect to YouTube').'</a></p>
            </div>';
        displayFooter();
        die();
    }

    $row = mysql_fetch_assoc($result);

    $_SESSION['youtube_session_token'] = $row['youtube_session_token'];

    $youtubeConfig = getYouTubeConfigData();
    $httpClient    = getAuthSubHttpClient($youtubeConfig['youtube_key'], $row['youtube_session_token']);

    if ($httpClient === false)
    {
        // Error message was already displayed by getAuthSubHttpClient()
        displayFooter();
        die();
    }
}

/**
 * displayYouTubeUploadPage
 * 
 * @return void
 */
function displayYouTubeUploadPage ()
{
    displayHeader();

    checkUserAuthedYouTube();

    $youtubeConfig = getYouTubeConfigData();
    $httpClient    = getAuthSubHttpClient($youtubeConfig['youtube_key']);

    if ($httpClient === false)
    {
        // Error message was already displayed by getAuthSubHttpClient()
        displayFooter();
        die();
    }

    $youTubeService = new Zend_Gdata_YouTube($httpClient);

    $feed = $youTubeService->getUserProfile('default');
    if (!$feed instanceof Zend_Gdata_YouTube_UserProfileEntry)
    {
        print '
            <div class="error-alert">'.T_('Could not get YouTube data for user.').'</div>';
        return;
    }

    $username = $feed->getUsername();

    echo '
        <form action="video.php?upload=youtube" method="post">
            <fieldset>
                <legend><span>'.T_('Upload YouTube Video').'</span></legend>
                <div class="field-row clearfix">
                    <div class="field-label"><label><b>'.T_('YouTube Account').'</b></label></div>
                    <div class="field-widget">'.$username.'
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label><b>'.T_('Title').'</b></label></div>
                    <div class="field-widget">
                        <input type="text" name="title" size="50"/>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label><b>'.T_('Description').'</b></label></div>
                    <div class="field-widget">
                        <textarea cols="50" name="description"></textarea>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label><b>'.T_('Category').'</b></label></div>
                    <div class="field-widget">
                        <select name="category">
                            <option value="Autos">'.T_('Autos &amp; Vehicles').'</option>
                            <option value="Music">'.T_('Music').'</option>
                            <option value="Animals">'.T_('Pets &amp; Animals').'</option>
                            <option value="Sports">'.T_('Sports').'</option>
                            <option value="Travel">'.T_('Travel &amp; Events').'</option>
                            <option value="Games">'.T_('Gadgets &amp; Games').'</option>
                            <option value="Comedy">'.T_('Comedy').'</option>
                            <option value="People">'.T_('People &amp; Blogs').'</option>
                            <option value="News">'.T_('News &amp; Politics').'</option>
                            <option value="Entertainment">'.T_('Entertainment').'</option>
                            <option value="Education">'.T_('Education').'</option>
                            <option value="Howto">'.T_('Howto &amp; Style').'</option>
                            <option value="Nonprofit">'.T_('Nonprofit &amp; Activism').'</option>
                            <option value="Tech">'.T_('Science &amp; Technology').'</option>
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="private"><b>'.T_('Private').'</b></label></div>
                    <div class="field-widget">
                        <input type="checkbox" name="private" id="private" value="yes" checked="checked">
                    </div>
                </div>
                <input class="sub1" type="submit" id="upload_data" name="upload_data" value="'.T_('Next').'"/>
                 &nbsp;'.T_('or').' &nbsp;
                <a href="video.php">'.T_('Cancel').'</a>
            </fieldset>
        </form>';

    displayFooter();
}

/**
 * displayYouTubeUploadFilePage 
 * 
 * Takes the post data from the previous form, sends to youtube, creates new entry,
 * and prints the video file upload form.
 * 
 * @return void
 */
function displayYouTubeUploadFilePage ()
{
    global $currentUserId;

    displayHeader();

    $videoTitle       = isset($_POST['title'])       ? cleanInput($_POST['title'])       : '';
    $videoDescription = isset($_POST['description']) ? cleanInput($_POST['description']) : '';
    $videoCategory    = isset($_POST['category'])    ? cleanInput($_POST['category'])    : '';
    $videoPrivate     = isset($_POST['private'])     ? true                              : false;

    // Create fcms video - we update after the youtube video is created
    $sql = "INSERT INTO `fcms_video` (
                `source_id`, 
                `title`, 
                `description`, 
                `source`, 
                `created`, 
                `created_id`, 
                `updated`, 
                `updated_id`
            )
            VALUES (
                '0',
                '$videoTitle',
                '$videoDescription',
                'youtube',
                NOW(),
                '$currentUserId',
                NOW(),
                '$currentUserId'
            )";
    if (!mysql_query($sql))
    {
        displaySQLError('Video Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    // Save fcms video id
    $_SESSION['fcmsVideoId'] = mysql_insert_id();

    $sessionToken  = getSessionToken($currentUserId);
    $youtubeConfig = getYouTubeConfigData();
    $httpClient    = getAuthSubHttpClient($youtubeConfig['youtube_key'], $sessionToken);

    if ($httpClient === false)
    {
        // Error message was already displayed by getAuthSubHttpClient()
        displayFooter();
        die();
    }

    $youTubeService = new Zend_Gdata_YouTube($httpClient);
    $newVideoEntry  = new Zend_Gdata_YouTube_VideoEntry();

    $newVideoEntry->setVideoTitle($videoTitle);
    $newVideoEntry->setVideoDescription($videoDescription);
    $newVideoEntry->setVideoCategory($videoCategory);

    if ($videoPrivate)
    {
        $newVideoEntry->setVideoPrivate();
    }

    try
    {
        $tokenArray = $youTubeService->getFormUploadToken($newVideoEntry, 'http://gdata.youtube.com/action/GetUploadToken');
    }
    catch (Exception $e)
    {
        echo '
            <div class="error-alert">
                <p>'.T('Could not retrieve token for syndicated upload.').'</p>
                <p>'.$e->getMessage().'</p>
            </div>';
        displayFooter();
        return;
    }


    $tokenValue = $tokenArray['token'];
    $postUrl    = $tokenArray['url'];
    $nextUrl    = getDomainAndDir().'video.php?upload=youtube';
 
    echo '
        <form action="'.$postUrl.'?nexturl='.$nextUrl.'" method="post" enctype="multipart/form-data">
            <fieldset>
                <legend><span>'.T_('Upload YouTube Video').'</span></legend>
                <div class="field-row clearfix">
                    <div class="field-label"><label><b>'.T_('Title').'</b></label></div>
                    <div class="field-widget"><b>'.$videoTitle.'</b></div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label><b>'.T_('Video').'</b></label></div>
                    <div class="field-widget">
                        <input type="file" name="file" size="50"/>
                    </div>
                </div>
                <input name="token" type="hidden" value="'.$tokenValue.'"/>
                <input class="sub1" type="submit" id="upload_file" name="upload_file" value="'.T_('Upload').'"/>
            </fieldset>
        </form>';

    displayFooter();
}

/**
 * displayYouTubeUploadStatusPage 
 * 
 * @return void
 */
function displayYouTubeUploadStatusPage ()
{
    global $currentUserId;

    $sourceId = cleanInput($_GET['id']);
    $status   = cleanInput($_GET['status']);
    $videoId  = $_SESSION['fcmsVideoId'];

    unset($_SESSION['fcmsVideoId']);

    switch ($status)
    {
        case $status < 400:

            // Connect to YouTube and get more info about this video
            $youtubeConfig  = getYouTubeConfigData();
            $httpClient     = getAuthSubHttpClient($youtubeConfig['youtube_key']);

            if ($httpClient === false)
            {
                // Error message was already displayed by getAuthSubHttpClient()
                displayFooter();
                die();
            }

            $youTubeService = new Zend_Gdata_YouTube($httpClient);
            $videoEntry     = $youTubeService->getVideoEntry($sourceId);

            $duration = $videoEntry->getVideoDuration();
            $thumbs   = $videoEntry->getVideoThumbnails();

            $height = '420';
            $width  = '780';

            if (count($thumbs) > 0)
            {
                $height = $thumbs[0]['height'];
                $width  = $thumbs[0]['width'];
            }

            // Update fcms video
            $sql = "UPDATE `fcms_video`
                    SET `source_id` = '$sourceId',
                        `height` = '$height',
                        `width` = '$width',
                        `updated` = NOW()
                    WHERE `id` = '$videoId'";
            if (!mysql_query($sql))
            {
                displayHeader();
                displaySQLError('Video Update Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                displayFooter();
                return;
            }

            // Create fcms video
            header("Location: video.php?u=$currentUserId&id=$videoId");

            break;

        default:

            displayHeader();

            echo '
            <div class="error-alert">
                <p>'.T_('An error occurred with you video upload.').'</p>
                <p>'.getUploadStatus($videoId).'</p>
            </div>';

            displayFooter();

            break;
    }
}

/**
 * displayVimeoUploadPage
 * 
 * @return void
 */
function displayVimeoUploadPage ()
{
    displayHeader();
    echo 'Vimeo not implemented yet';
    displayFooter();
}

/**
 * displayLatestPage
 * 
 * @return void
 */
function displayLatestPage ()
{
    global $currentUserId;

    displayHeader();

    // Get Last 6 videos
    $sql = "SELECT v.`id`, v.`source_id`, v.`title`, v.`created`, v.`created_id`, u.`id` AS user_id, u.`fname`, u.`lname`
            FROM `fcms_video` AS v
            LEFT JOIN `fcms_users` AS u ON v.`created_id` = u.`id`
            WHERE `active` = 1
            ORDER BY v.`updated` DESC
            LIMIT 6";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Video Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayVideoStartCode();

    if (mysql_num_rows($result) <= 0)
    {
        // TODO move js
        echo '
        <div class="info-alert">
            <h2>'.T_('No Videos').'</h2>
            <p>'.T_('Unfortunately no videos have been added yet.').'</p>
            <div id="help"><br/>
                <p><b>'.T_('How do I add videos?').'</b></p>
                <ol>
                    <li><a href="http://www.youtube.com">'.T_('Create a YouTube account').'</a></li>
                    <li><a href="settings.php?view=socialmedia">'.T_('Connect your YouTube account with Family Connections').'</a></li>
                    <li><a href="?upload=youtube">'.T_('Upload Videos').'</a></li>
                </ol><br/>
                <p><b>'.T_('Why aren\'t my videos showing up?').'</b></p>
                <p>'.T_('Depending on your setup and the size of the video uploaded, it can take over an hour before your videos show up on the site.').'</p>
            </div>
        </div>
        <script type="text/javascript">
        if ($("help")) {
            var div = $("help");
            div.hide();
            var a = new Element("a", { href: "#" }).update("'.T_('Learn more.').'");
            a.onclick = function() { $("help").toggle(); return false; };
            div.insert({"before":a});
        }
        </script>';

        displayFooter();
        return;
    }

    echo '
        <div id="video_content">
            <h2>'.T_('Latest Videos').'<h2>
            <ul class="categories clearfix">';

    while ($row = mysql_fetch_assoc($result))
    {
        $name = cleanOutput($row['fname']).' '.cleanOutput($row['lname']);
        $date = fixDate('Y-m-d', '', $row['created'], $row['created_id']);

        echo '
                <li class="category">
                    <a href="?u='.$row['user_id'].'&amp;id='.$row['id'].'"><img src="http://i.ytimg.com/vi/'.$row['source_id'].'/default.jpg"/></a>
                    <span>
                        <strong>'.cleanOutput($row['title']).'</strong>
                        <i>'.sprintf(T_pgettext('%s is a person\'s name', 'by %s'), $name).'</i>
                        <i>'.sprintf(T_pgettext('%s is a date', 'on %s'), $date).'</i>
                    </span>
                </li>';
    }

    echo '
            </ul>';

    // Get Last 8 users
    $sql = "SELECT v.`id`, COUNT(*) AS 'count', v.`created_id` AS 'user_id', u.`fname`, u.`lname`, u.`avatar`, u.`gravatar`
            FROM `fcms_video` AS v
            LEFT JOIN `fcms_users` AS u ON v.`created_id` = u.`id`
            WHERE `active` = 1
            GROUP BY v.`created_id`
            ORDER BY v.`updated` DESC
            LIMIT 8";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Video Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    echo '
            <div><a href="?members=all">'.T_('Members').'</a></div>
            <ul id="small_video_users">';

    while ($row = mysql_fetch_assoc($result))
    {
        $name       = cleanOutput($row['fname']).' '.cleanOutput($row['lname']);
        $avatarPath = getAvatarPath($row['avatar'], $row['gravatar']);

        echo '
                <li>
                    <a href="?u='.$row['user_id'].'"><img src="'.$avatarPath.'" alt="'.$name.'"/></a>
                    <a href="?u='.$row['user_id'].'">'.$name.'</a>
                    <span>'.sprintf(T_ngettext('%d video', '%d videos', $row['count']), $row['count']).'</span>
                </li>';
    }

    echo '
                <li>
                    <a href="?members=all">'.T_('See all members.').'</a>
                </li>
            </ul>
        </div>';

    displayFooter();
}

/**
 * displayVideoPage
 * 
 * @return void
 */
function displayVideoPage ()
{
    $id = cleanInput($_GET['id']);

    $sql = "SELECT `id`, `source_id`, `height`, `width`, `created`, `created_id`
            FROM `fcms_video`
            WHERE `id` = '$id'
            AND `active` = 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displayHeader();
        displaySQLError('Video Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $video = mysql_fetch_assoc($result);

    // YouTube or Vimeo
    displayYouTubeVideoPage($video);
}

/**
 * displayYouTubeVideoPage 
 * 
 * @param array $video 
 * 
 * @return void
 */
function displayYouTubeVideoPage ($video)
{
    global $currentUserId;

    // Save video id for ajax call
    $_SESSION['source_id'] = $video['source_id'];

    displayHeader();

    // Video not found in db
    if (!is_array($video))
    {
        echo '
        <div class="info-alert">
            <h2>'.T_('Missing or Invalid Video.').'</h2>
            <p>'.T_('This video cannot be found.  Are you sure you typed in URL correctly?').'</p>
        </div>';

        displayFooter();
        return;
    }
    // Video upload failed
    elseif ($video['source_id'] == '0')
    {
        displayVideoNotFound($video, 'YouTube');
        return;
    }

    $youTubeService = new Zend_Gdata_YouTube();
    $status         = null;

    // Get video entry
    try 
    {
        $videoEntry = $youTubeService->getVideoEntry($video['source_id']);
    }
    // Video is private
    catch (Exception $e)
    {
        $response = $e->getRawResponseBody();
        $private  = stripos($response, 'Private video');
        $notFound = stripos($response, 'Video not found');

        // Video not found at YouTube
        if ($notFound !== false)
        {
            displayVideoNotFound($video, 'YouTube');
            return;
        }
        // Video isn't private
        elseif ($private === false)
        {
            echo '
            <div class="error-alert">
                <p>'.T_('Could not get video information.').'</p>
                <p>'.$e->getMessage().'</p>
            </div>';
            displayFooter();
            return;
        }
        // else Video is private

        // Couldn't get entry because it's private, so get session token of user who uploaded
        $sessionToken = getSessionToken($video['created_id']);

        $youtubeConfig = getYouTubeConfigData();
        $httpClient    = getAuthSubHttpClient($youtubeConfig['youtube_key'], $sessionToken);

        if ($httpClient === false)
        {
            // Error message was already displayed by getAuthSubHttpClient()
            displayFooter();
            return;
        }

        $youTubeService = new Zend_Gdata_YouTube($httpClient);
        $videoEntry     = $youTubeService->getVideoEntry($video['source_id']);

        $status = getUploadStatus($video['source_id'], $sessionToken);
    }

    // Video is public
    if ($status == null)
    {
        $status = getUploadStatus($video['source_id']);
    }

    $url = 'video.php?u='.$video['created_id'].'&amp;id='.$video['id'];

    // Is youtube processing finished?
    if ($status !== 'Finished')
    {
        echo '
            <div class="ok-alert">
                <p><b>'.T_('Your video was uploaded to YouTube successfully.').'</b></p>
                <p>'.T_('However it may take a few moments before you video is viewable. Please check back later.').'</p>
                <p id="js_msg"></p><br/>
                <p>'.T_('Current status: ').'<span id="current_status">'.$status.'</span></p>
                <p id="refresh"><a href="'.$url.'">'.T_('Refresh').'</a></p>
            </div>';

        displayFooter();
        return;
    }

    // Ajax is done at this point, we don't need the id anymore
    unset($_SESSION['source_id']);

    $videoUrl = 'http://www.youtube.com/e/'.$video['source_id'].'?version=3&enablejsapi=1&rel=0&wmode=transparent';

    displayVideoStartCode();

    echo '
        <div id="sections_menu" class="clearfix">
            <ul>
                <li><a href="video.php">Latest Videos</a></li>
                <li><a href="video.php?u='.$video['created_id'].'">'.getUserDisplayName($video['created_id'], 2).'</a></li>
            </ul>
        </div>';

    // Can you edit/delete this video?
    if ($video['created_id'] == $currentUserId || checkAccess($currentUserId) == 1)
    {
        echo '
        <div id="video_edit">
            <form action="'.$url.'" method="post">
                <div id="delete">
                    <input type="hidden" id="id" name="id" value="'.$video['id'].'"/>
                    <input type="hidden" id="source_id" name="source_id" value="'.$video['source_id'].'"/>
                    <input class="btn" type="submit" id="remove_video" name="remove_video" value="'.T_('Remove Video').'"/>
                    <label for="delete_youtube">'.T_('Delete from YouTube?').'</label>
                    <input type="checkbox" id="delete_youtube" name="delete_youtube"/>
                </div>
            </form>
        </div>';
    }

    echo '
        <div id="video_content">
            <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$video['width'].'" height="'.$video['height'].'">
                <param name="movie" value="'.$videoUrl.'" />
                <param name="wmode" value="transparent"></param>
                <!--[if !IE]>-->
                <object type="application/x-shockwave-flash" data="'.$videoUrl.'" wmode="transparent" width="'.$video['width'].'" height="'.$video['height'].'">
                <!--<![endif]-->
                <div class="info-alert">
                    '.T_('You need Flash player to view this video.').'<br/>
                    <a href="http://www.adobe.com/go/getflashplayer">
                        <img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="'.T_('Get Adobe Flash player').'"/>
                    </a>
                </div>
                <!--[if !IE]>-->
                </object>
                <!--<![endif]-->
            </object>
        </div>';

    echo '<p>'.T_('Views').': '.$videoEntry->getVideoViewCount().'</p>';

    $params = array(
        'currentUserId' => $currentUserId,
        'id'            => $video['id']
    );
    displayComments($url, 'video', $params);

    displayFooter();
}

/**
 * displayVideoStartCode 
 * 
 * @return void
 */
function displayVideoStartCode ()
{
    echo '
        <noscript>
            <style type="text/css">
            #video_content {display: none;}
            #noscript {padding:1em;}
            #noscript p {background-color:#ff9; padding:3em; font-size:130%; line-height:200%;}
            #noscript p span {font-size:60%;}
            </style>
            <div id="noscript">
            <p>
                '.T_('JavaScript must be enabled in order for you to use the Video Gallery. However, it seems JavaScript is either disabled or not supported by your browser.').'
                <br/><br/>
                '.T_('Please enable JavaScript by changing your browser options.').'
            </p>
            </div>
        </noscript>
        <script type="text/javascript" src="ttp://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>';
}

/**
 * getUploadStatus
 * 
 * Check the upload status of a video.  If the session token is provided 
 * it's because the video is private and we need to auth to get the
 * status of the video.
 * 
 * @param string $videoId 
 * @param string $sessionToken
 * 
 * @return string
 */
function getUploadStatus ($videoId, $sessionToken = false)
{
    $youtubeConfig  = getYouTubeConfigData();
    $youTubeService = new Zend_Gdata_YouTube();

    if ($sessionToken !== false)
    {
        $httpClient     = getAuthSubHttpClient($youtubeConfig['youtube_key'], $sessionToken);
        $youTubeService = new Zend_Gdata_YouTube($httpClient);

        if ($httpClient === false)
        {
            // Error message was already displayed by getAuthSubHttpClient()
            die();
        }
    }

    $videoEntry = $youTubeService->getVideoEntry($videoId);

    try
    {
        $control = $videoEntry->getControl();
    }
    catch (Exception $e)
    {
        return T_('Could not retrieve video status: ').$e->getMessage();
    }

    $message = 'Finished';

    if ($control instanceof Zend_Gdata_App_Extension_Control)
    {
        if (($control->getDraft() != null) && ($control->getDraft()->getText() == 'yes'))
        {
            $state = $videoEntry->getVideoState();

            if ($state instanceof Zend_Gdata_YouTube_Extension_State)
            {
                $message = $state->getName().' '.$state->getText();
            }
            else
            {
                return $message;
            }
        }
    }

    return $message;
}

/**
 * displayCommentSubmit 
 * 
 * @return void
 */
function displayCommentSubmit ()
{
    global $currentUserId;

    $userId   = cleanInput($_GET['u']);
    $videoId  = cleanInput($_GET['id']);
    $comments = ltrim($_POST['comments']);
    $comments = cleanInput($comments);

    if (!empty($comments))
    {
        $sql = "INSERT INTO `fcms_video_comment` (
                    `video_id`, `comment`, `created`, `created_id`, `updated`, `updated_id`
                )
                VALUES (
                    '$videoId', 
                    '$comments',
                    NOW(), 
                    '$currentUserId',
                    NOW(), 
                    '$currentUserId'
                )";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySQLError('Comment Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }
    }

    header("Location: video.php?u=$userId&id=$videoId#comments");
}

/**
 * getSessionToken
 * 
 * Will return the session token for the given user.
 * 
 * @param int userId
 * 
 * @return string | false
 */
function getSessionToken ($userId)
{
    $sql = "SELECT `youtube_session_token`
            FROM `fcms_user_settings`
            WHERE `user` = '".cleanInput($userId)."'
            AND `youtube_session_token` IS NOT NULL
            AND `youtube_session_token` != ''";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return false;
    }

    if (mysql_num_rows($result) <= 0)
    {
        echo '<p class="error-alert">'.T_('Could not find session token for user.').'</p>';
        return false;
    }

    $row = mysql_fetch_assoc($result);

    return $row['youtube_session_token'];
}

/**
 * displayMembersListPage 
 * 
 * @return void
 */
function displayMembersListPage ()
{
    displayHeader();

    $sql = "SELECT v.`id`, COUNT(*) AS 'count', v.`created_id` AS 'user_id', u.`fname`, u.`lname`, u.`avatar`, u.`gravatar`
            FROM `fcms_video` AS v
            LEFT JOIN `fcms_users` AS u ON v.`created_id` = u.`id`
            WHERE `active` = 1
            GROUP BY v.`created_id`
            ORDER BY v.`updated` DESC";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Video Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    echo '
        <div id="sections_menu" class="clearfix">
            <ul>
                <li><a href="video.php">Latest Videos</a></li>
            </ul>
        </div>
        <ul id="large_video_users">';

    while ($row = mysql_fetch_assoc($result))
    {
        $name       = cleanOutput($row['fname']).' '.cleanOutput($row['lname']);
        $avatarPath = getAvatarPath($row['avatar'], $row['gravatar']);

        echo '
            <li>
                <a href="?u='.$row['user_id'].'"><img src="'.$avatarPath.'" alt="'.$name.'"/></a><br/>
                <a href="?u='.$row['user_id'].'">'.$name.'</a>
                <span>'.sprintf(T_ngettext('%d video', '%d videos', $row['count']), $row['count']).'</span>
            </li>';
    }

    echo '
        </ul>
    </div>';

    displayFooter();
}

/**
 * displayUserVideosPage 
 * 
 * @return void
 */
function displayUserVideosPage ()
{
    global $currentUserId;

    displayHeader();

    $userId = cleanInput($_GET['u'], 'int');

    if (isset($_SESSION['message']))
    {
        displayMessage($_SESSION['message']);
    }

    // Get user info
    $sql = "SELECT 'id', `fname`, `lname`, `avatar`, `gravatar`
            FROM `fcms_users`
            WHERE `id` = '$userId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Member Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        echo '<div class="error-alert">'.T_('Member not found.').'</div>';
        displayFooter();
        return;
    }

    $row = mysql_fetch_assoc($result);

    $name       = cleanOutput($row['fname']).' '.cleanOutput($row['lname']);
    $avatarPath = getAvatarPath($row['avatar'], $row['gravatar']);

    echo '
        <div id="sections_menu" class="clearfix">
            <ul>
                <li><a href="video.php">Latest Videos</a></li>
                <li><a href="video.php?members=all">Members</a></li>
            </ul>
        </div>

        <div id="video_content">
            <div id="member" class="clearfix">
                <img src="'.$avatarPath.'" titl="'.$name.'"/>
                <span>'.T_('Videos For:').'</span>
                <h2>'.$name.'</h2>
            </div>
            <ul class="categories clearfix">';

    // Get videos
    $sql = "SELECT `id`, `source_id`, `title`, `active`, `created`, `created_id`
            FROM `fcms_video`
            WHERE `created_id` = '$userId'
            ORDER BY `updated` DESC";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Videos Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        echo '<div class="error-alert">'.T_('No videos found.').'</div>';
        displayFooter();
        return;
    }

    while ($row = mysql_fetch_assoc($result))
    {
        $class = '';

        if ($row['active'] == '0')
        {
            if ($row['created_id'] != $currentUserId)
            {
                continue;
            }

            $class = 'removed';
        }

        $date = fixDate('Y-m-d', '', $row['created'], $currentUserId);

        echo '
                <li class="category '.$class.'">
                    <a href="?u='.$userId.'&amp;id='.$row['id'].'"><img src="http://i.ytimg.com/vi/'.$row['source_id'].'/default.jpg"/></a>
                    <span>
                        <strong>'.cleanOutput($row['title']).'</strong>
                        <i>'.sprintf(T_pgettext('%s is a date', 'on %s'), $date).'</i>
                    </span>
                </li>';
    }

    displayFooter();
}

/**
 * displayRemoveVideoSubmit 
 * 
 * Remove video doesn't actually physically delete the video from FCMS, it 
 * just sets the video to in-active in the DB, which removes it from view.
 * 
 * We don't want to delete these entries from the db, because the cron importer
 * will just continue to import them.
 * 
 * @return void
 */
function displayRemoveVideoSubmit ()
{
    global $currentUserId;

    if (!isset($_POST['id']) || !isset($_POST['source_id']))
    {
        displayHeader();
        echo '<div class="error_alert">'.T_('Can\'t remove video.  Missing video id.').'</div>';
        displayFooter();
        return;
    }

    $userId   = cleanInput($_GET['u']);
    $id       = cleanInput($_POST['id'], 'int');
    $sourceId = cleanInput($_POST['source_id']);

    $sql = "UPDATE `fcms_video`
            SET `active` = 0,
            `updated` = NOW(),
            `updated_id` = '$currentUserId'
            WHERE `id` = '$id'";

    if (!mysql_query($sql))
    {
        displayFooter();
        displaySQLError('Remove Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    if (isset($_POST['delete_youtube']))
    {
        $sessionToken  = getSessionToken($currentUserId);
        $youtubeConfig = getYouTubeConfigData();
        $httpClient    = getAuthSubHttpClient($youtubeConfig['youtube_key'], $sessionToken);

        if ($httpClient === false)
        {
            // Error message was already displayed by getAuthSubHttpClient()
            displayFooter();
            return;
        }

        $youTubeService = new Zend_Gdata_YouTube($httpClient);
        $videoEntry     = $youTubeService->getVideoEntry($source_id);

        // Set message
        $_SESSION['message'] = 'delete_video_youtube';

        $youTubeService->delete($videoEntry);
    }

    // Set message
    if (!isset($_SESSION['message']))
    {
        $_SESSION['message'] = 'remove_video';
    }

    // Send back to user's video listing
    header("Location: video.php?u=$userId");
}

/**
 * displayDeleteVideoSubmit 
 * 
 * Will delete the video entry from the FCMS db.  This is done when the video
 * at YouTube or Vimeo has been removed.
 * 
 * @return void
 */
function displayDeleteVideoSubmit ()
{
    global $currentUserId;

    if (!isset($_POST['id']) || !isset($_POST['source_id']))
    {
        displayHeader();
        echo '<div class="error_alert">'.T_('Can\'t delete video.  Missing video id.').'</div>';
        displayFooter();
        return;
    }

    $userId   = cleanInput($_GET['u']);
    $id       = cleanInput($_POST['id'], 'int');
    $sourceId = cleanInput($_POST['source_id']);

    $sql = "DELETE FROM `fcms_video_comment`
            WHERE `video_id` = '$id'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Delete Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "DELETE FROM `fcms_video`
            WHERE `id` = '$id'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Delete Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    // Set message
    $_SESSION['message'] = 'delete_video';

    // Send back to user's video listing
    header("Location: video.php?u=$userId");
}

/**
 * displayMessage 
 * 
 * @param string  $message 
 * 
 * @return void
 */
function displayMessage ($message)
{
    unset($_SESSION['message']);

    switch ($message)
    {
        case 'remove_video':

            displayOkMessage(T_('Video removed successfully.'));
            break;

        case 'delete_video':

            displayOkMessage(T_('Video deleted successfully.'));
            break;

        case 'delete_video_youtube':

            displayOkMessage(T_('Video removed and deleted from YouTube successfully.'), '5000');
    }
}

/**
 * displayVideoNotFound 
 * 
 * @param array  $video 
 * @param string $source 
 * 
 * @return void
 */
function displayVideoNotFound ($video, $source)
{
    $userId  = cleanInput($_GET['u']);
    $videoId = cleanInput($video['id'], 'int');

    $url = 'video.php?u='.$userId.'&amp;id='.$videoId;

    echo '
            <div class="info-alert">
                <h2>'.T_('Source Video cannot be found.').'</h2><br/>
                <p>'.sprintf(T_('The video file for this video could not be found at %s.'), $source).'</p>
                <p>'.T_('Would you like to delete this video?').'</p>
                <form action="'.$url.'" method="post">
                    <input type="hidden" id="id" name="id" value="'.$video['id'].'"/>
                    <input type="hidden" id="source_id" name="source_id" value="'.$video['source_id'].'"/>
                    <input class="sub1" type="submit" id="delete_video" name="delete_video" value="'.T_('Yes').'"/>
                    &nbsp; &nbsp; '.T_('or').' &nbsp; &nbsp;
                    <a href="video.php">'.T_('No').'</a>
                </form>
            </div>';
}
