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
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('datetime', 'socialmedia', 'google', 'comments');

init();

$page = new Page($fcmsError, $fcmsDatabase, $fcmsUser);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;

        $this->control();
    }

    /**
     * control 
     * 
     * The controlling structure for this script.
     * 
     * @return void
     */
    function control ()
    {
        // AJAX
        if (isset($_GET['check_status']))
        {
            if (isset($_SESSION['source_id']))
            {
                echo $this->getUploadStatus($_SESSION['source_id']);
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
                // Step 2 - Upload to YouTube
                if (isset($_POST['upload_data']))
                {
                    $this->displayYouTubeUploadSubmitPage();
                }
                // Step 1 - Print the upload form
                else
                {
                    $this->displayYouTubeUploadFormPage();
                }
            }
            // Vimeo
            else
            {
                $this->displayVimeoUploadPage();
            }
        }
        elseif (isset($_GET['u']))
        {
            if (isset($_GET['id']))
            {
                if (isset($_POST['addcomment']))
                {
                    $this->displayCommentSubmit();
                }
                elseif (isset($_POST['remove_video']))
                {
                    $this->displayRemoveVideoSubmit();
                }
                elseif (isset($_POST['delete_video']))
                {
                    $this->displayDeleteVideoSubmit();
                }
                else
                {
                    $this->displayVideoPage();
                }
            }
            else
            {
                $this->displayUserVideosPage();
            }
        }
        elseif (isset($_GET['members']))
        {
            $this->displayMembersListPage();
        }
        else
        {
            $this->displayLatestPage();
        }
    }

    /**
     * displayHeader 
     * 
     * @return void
     */
    function displayHeader ()
    {
        $params = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Video Gallery'),
            'pageId'        => 'video',
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        $params['javascript'] = '
<script type="text/javascript">
$(document).ready(function() {
    initChatBar(\''.T_('Chat').'\', \''.URL_PREFIX.'\');
    initYouTubeVideoStatus(\''.T_('This page will automatically refresh').'\');
    initHideVideoEdit(\''.T_('Edit Video').'\');
});
</script>';

        loadTemplate('global', 'header', $params);

        echo '
            <div id="actions_menu">
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
        $params = array(
            'path'    => URL_PREFIX,
            'version' => getCurrentVersion(),
            'year'    => date('Y')
        );

        loadTemplate('global', 'footer', $params);
    }

    /**
     * checkUserAuthedYouTube 
     * 
     * Check to make sure the user is connected and authed at YouTube.
     * 
     * Assumed $this->displayHeader() already sent
     * 
     * @return void
     */
    function checkUserAuthedYouTube ()
    {
        // Get session token
        $sql = "SELECT `google_session_token`
                FROM `fcms_user_settings`
                WHERE `user` = ?
                AND `google_session_token` IS NOT NULL
                AND `google_session_token` != ''";

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (empty($row))
        {
            // TODO
            // Check that admin has setup google first.
            echo '
            <div class="info-alert">
                <h2>'.T_('Not connected to Google.').'</h2>
                <p>'.T_('The video gallery relies on Gooble.  You must create a Google account and connect it with your Family Connections account.').'</p>
                <p><a href="settings.php?view=google">'.T_('Connect to Google').'</a></p>
            </div>';

            $this->displayFooter();
            die();
        }

        $_SESSION['google_session_token'] = $row['google_session_token'];

        $googleConfig = getGoogleConfigData();
    }

    /**
     * displayYouTubeUploadFormPage
     * 
     * Prints a form for upload videos to YouTube.
     * 
     * @return void
     */
    function displayYouTubeUploadFormPage ()
    {
        $this->displayHeader();

        $this->checkUserAuthedYouTube();

        $googleClient = getAuthedGoogleClient($this->fcmsUser->id);
        $authService  = new Google_Service_Oauth2($googleClient);

        $userInfo = $authService->userinfo->get();

        echo '
        <form enctype="multipart/form-data" action="?upload=youtube" method="post">
            <fieldset>
                <legend><span>'.T_('Upload YouTube Video').'</span></legend>
                <div class="field-row">
                    <div class="field-label"><label><b>'.T_('YouTube Account').'</b></label></div>
                    <div class="field-widget">'.$userInfo->name.' ('.$userInfo->email.')</div>
                </div>
                <div class="field-row">
                    <div class="field-label"><label><b>'.T_('Videe').'</b></label></div>
                    <div class="field-widget">
                        <input name="video" type="file"/>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label"><label><b>'.T_('Title').'</b></label></div>
                    <div class="field-widget">
                        <input type="text" name="title" size="50"/>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label"><label><b>'.T_('Description').'</b></label></div>
                    <div class="field-widget">
                        <textarea cols="50" name="description"></textarea>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label"><label for="unlisted"><b>'.T_('Unlisted').'</b></label></div>
                    <div class="field-widget">
                        <input type="checkbox" name="unlisted" id="unlisted_" value="yes" checked="checked"><br/>
                        <small>'.T_('"Unlisted" means that only people who know the link to the video can view it. The video will not appear in any of YouTube\'s public spaces, such as search results, your channel, or the Browse page, but the link can be shared with anyone.').'</small>
                    </div>
                </div>
                <input class="sub1" type="submit" id="upload_data" name="upload_data" value="'.T_('Next').'"/>
                 &nbsp;'.T_('or').' &nbsp;
                <a href="video.php">'.T_('Cancel').'</a>
            </fieldset>
        </form>';

        $this->displayFooter();
    }

    /**
     * displayYouTubeUploadSubmitPage 
     * 
     * Upload the video to youtube.
     * 
     * @return void
     */
    function displayYouTubeUploadSubmitPage ()
    {
        $videoTitle         = '';
        $videoDescription   = '';

        $videoPath = $_FILES['video']['tmp_name'];

        if (isset($_POST['title']))
        {
            $videoTitle = strip_tags($_POST['title']);
        }

        if (isset($_POST['description']))
        {
            $videoDescription = strip_tags($_POST['description']);
        }

        $videoCategory = isset($_POST['category']) ? $_POST['category'] : '';
        $videoPrivacy  = isset($_POST['unlisted']) ? 'unlisted'         : 'public';

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
                VALUES
                    ('0', ?, ?, 'youtube', NOW(), ?, NOW(), ?)";

        $params = array(
            $videoTitle,
            $videoDescription,
            $this->fcmsUser->id,
            $this->fcmsUser->id
        );

        $videoId = $this->fcmsDatabase->insert($sql, $params);
        if ($videoId === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        try
        {
            $googleClient = getAuthedGoogleClient($this->fcmsUser->id);

            $youtube = new Google_Service_YouTube($googleClient);
            $snippet = new Google_Service_YouTube_VideoSnippet();
            $status  = new Google_Service_YouTube_VideoStatus();
            $video   = new Google_Service_YouTube_Video();

            // Save the video title, desc and category
            $snippet->setTitle($videoTitle);
            $snippet->setDescription($videoDescription);
            $snippet->setCategoryId('22');

            // Save privacy (public, private or unlisted)
            $status->privacyStatus = $videoPrivacy;

            // Associate the snippet and status objects with the new video
            $video->setSnippet($snippet);
            $video->setStatus($status);

            // Specify the size of each chuck in bytes.
            // Note: higher value faster uploads, lower for better recovery
            $chunkSizeBytes = 1 * 1024 * 1024;

            // Defer - tells the client to return a request which can be called
            // with ->execute() instead of making API call immediately
            $googleClient->setDefer(true);

            $insertRequest = $youtube->videos->insert('status,snippet', $video);

            // Create a MediaFileUpload for resumable uploads
            $media = new Google_Http_MediaFileUpload(
                $googleClient,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize($_FILES['video']['size']);

            // Read the media file and upload it chunk by chunk.
            $status = false;
            $handle = fopen($videoPath, 'rb');
            while (!$status && !feof($handle)) {
                $chunk  = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }

            fclose($handle);

            $sourceId = $status['id'];

            // Update fcms video
            $sql = "UPDATE `fcms_video`
                    SET `source_id` = ?,
                        `updated` = NOW()
                    WHERE `id` = ?";

            $params = array(
                $sourceId,
                $videoId
            );

            if (!$this->fcmsDatabase->update($sql, $params))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            header("Location: video.php?u=".$this->fcmsUser->id."&id=$videoId");
        }
        catch (Exception $e)
        {
            $this->displayHeader();
            $this->fcmsError->add(array(
                'type'    => 'operation',
                'message' => 'Could not upload video to YouTube.',
                'error'   => $e,
                'file'    => __FILE__,
                'line'    => __LINE__,
            ));
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }
    }

    /**
     * displayVimeoUploadPage
     * 
     * @return void
     */
    function displayVimeoUploadPage ()
    {
        $this->displayHeader();
        echo 'Vimeo not implemented yet';
        $this->displayFooter();
    }

    /**
     * displayLatestPage
     * 
     * @return void
     */
    function displayLatestPage ()
    {
        $this->displayHeader();

        // Get Last 6 videos
        $sql = "SELECT v.`id`, v.`source_id`, v.`title`, v.`created`, v.`created_id`, u.`id` AS user_id, u.`fname`, u.`lname`
                FROM `fcms_video` AS v
                LEFT JOIN `fcms_users` AS u ON v.`created_id` = u.`id`
                WHERE `active` = 1
                ORDER BY v.`updated` DESC
                LIMIT 6";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $this->displayVideoStartCode();

        if (count($rows) <= 0)
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
                    <li><a href="settings.php?view=google">'.T_('Connect your Google account with Family Connections').'</a></li>
                    <li><a href="?upload=youtube">'.T_('Upload Videos').'</a></li>
                </ol><br/>
                <p><b>'.T_('Why aren\'t my videos showing up?').'</b></p>
                <p>'.T_('Depending on your setup and the size of the video uploaded, it can take over an hour before your videos show up on the site.').'</p>
            </div>
        </div>
        <script type="text/javascript">
        $("#help").hide();
        $("#help").before(\'<a id="learn_more" href="#">'.T_('Learn more.').'</a>\');
        $("#learn_more").click(function() {
            $("#help").toggle();
            return false;
        });
        </script>';

            $this->displayFooter();
            return;
        }

        echo '
        <div id="video_content">
            <h2>'.T_('Latest Videos').'<h2>
            <ul class="categories">';

        foreach ($rows as $row)
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

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        echo '
            <div><a href="?members=all">'.T_('Members').'</a></div>
            <ul id="small_video_users">';

        foreach ($rows as $row)
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

        $this->displayFooter();
    }

    /**
     * displayVideoPage
     * 
     * @return void
     */
    function displayVideoPage ()
    {
        $id = (int)$_GET['id'];

        $sql = "SELECT `id`, `source_id`, `title`, `description`, `created`, `created_id`
                FROM `fcms_video`
                WHERE `id` = ?";

        $video = $this->fcmsDatabase->getRow($sql, $id);
        if ($video === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // YouTube or Vimeo
        $this->displayYouTubeVideoPage($video);
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
        $this->displayHeader();

        // Video not found in db
        if (!is_array($video))
        {
            echo '
        <div class="info-alert">
            <h2>'.T_('Missing or Invalid Video.').'</h2>
            <p>'.T_('This video cannot be found.  Are you sure you typed in URL correctly?').'</p>
        </div>';

            $this->displayFooter();
            return;
        }
        // Video upload failed
        elseif ($video['source_id'] == '0')
        {
            $this->displayVideoNotFound($video, 'YouTube');
            return;
        }

        // Save video id for ajax call
        $_SESSION['source_id'] = $video['source_id'];

        $url   = 'video.php?u='.$video['created_id'].'&amp;id='.$video['id'];
        $views = T_('Unknown');

        // Get authed google client
        $googleClient = getAuthedGoogleClient($this->fcmsUser->id);

        // If this user has a google account setup, we should get a google client in return
        // so go ahead and do googly/youtuby stuff
        if ($googleClient !== false)
        {
            // Get video entry
            try
            {
                $youtube = new Google_Service_YouTube($googleClient);

                $videoEntry = $youtube->videos->listVideos('id,snippet,status,contentDetails,processingDetails,statistics', array('id'=>$video['source_id']));
            }
            catch (Exception $e)
            {
                $this->fcmsError->add(array(
                    'type'    => 'operation',
                    'message' => 'Could not search YouTube.',
                    'error'   => $e,
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ));
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            // Make sure we found the video first
            if (!(isset($videoEntry['items'][0])))
            {
                $this->displayVideoNotFound($video, 'YouTube');
                $this->displayFooter();
                return;
            }

            $status = $videoEntry['items'][0]['status']['uploadStatus'];
            $views  = $videoEntry['items'][0]['statistics']['viewCount'];

            // Let's handle all the upload statuses
            if ($status === 'deleted')
            {
                $this->displayVideoNotFound($video, 'YouTube');
                $this->displayFooter();
                return;
            }
            else if ($status === 'failed')
            {
                // TODO
                echo '<h1>FAILED</h1>';
                $this->displayFooter();
                return;
            }
            else if ($status === 'rejected')
            {
                $reason = $videoEntry['items'][0]['status']['rejectionReason'];

                echo '
                <div class="info-alert">
                    <p><b>'.T_('This video was Rejected by YouTube').'</b></p>
                    <p>'.T_('Rejection reason:').' '.$reason.'</p>
                    <p>'.T_('Would you like to delete this video?').'</p>
                    <form action="'.$url.'" method="post">
                        <input type="hidden" id="id" name="id" value="'.$video['id'].'"/>
                        <input type="hidden" id="source_id" name="source_id" value="'.$video['source_id'].'"/>
                        <input class="sub1" type="submit" id="delete_video" name="delete_video" value="'.T_('Yes').'"/>
                        &nbsp; &nbsp; '.T_('or').' &nbsp; &nbsp;
                        <a href="video.php">'.T_('No').'</a>
                    </form>
                </div>';
                $this->displayFooter();
                return;
            }
            else if ($status === 'uploaded')
            {
                $percentComplete = 0;

                $steps = array(
                    'fileDetailsAvailability',
                    'processingIssuesAvailability',
                    'tagSuggestionsAvailability',
                    'editorSuggestionsAvailability',
                    'thumbnailsAvailability'
                );

                foreach ($steps as $step)
                {
                    if ($videoEntry['items'][0]['processingDetails'][$step] === 'available')
                    {
                        $percentComplete += 20;
                    }
                }

                $message = $percentComplete;

                echo '
                <div class="ok-alert">
                    <p><b>'.T_('This video was uploaded to YouTube successfully.').'</b></p>
                    <p>'.T_('However it may take a few moments before you video is viewable. Please check back later.').'</p>
                    <p>
                        '.T_('Percentage complete:').' <span id="current_complete">'.$percentComplete.'%</span>
                    </p>
                    <p id="js_msg"></p>
                    <p id="refresh"><a href="'.$url.'">'.T_('Refresh').'</a></p>
                </div>';

                $this->displayFooter();
                return;
            }
        }

        $videoUrl = 'http://www.youtube.com/e/'.$video['source_id'].'?version=3&enablejsapi=1&rel=0&wmode=transparent';

        $this->displayVideoStartCode();

        echo '
        <div id="sections_menu">
            <ul>
                <li><a href="video.php">'.T_('Latest Videos').'</a></li>
                <li><a href="video.php?u='.$video['created_id'].'">'.getUserDisplayName($video['created_id'], 2).'</a></li>
            </ul>
        </div>';

        // Can you edit/delete this video?
        if ($video['created_id'] == $this->fcmsUser->id || $this->fcmsUser->access == 1)
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
        <div id="video_desc">
            <img src="'.getCurrentAvatar($video['created_id']).'"/>
            <h2>'.cleanOutput($video['title']).'</h2>
            <p>'.cleanOutput($video['description']).'</p>
        </div>
        <div id="video_content">
            <iframe class="youtube-player" type="text/html" width="854" height="480" 
                src="http://www.youtube.com/embed/'.$video['source_id'].'" allowfullscreen frameborder="0">
            </iframe>
        </div>';

        echo '<p>'.T_('Views').': '.cleanOutput($views).'</p>';

        $params = array(
            'id' => $video['id']
        );
        displayComments($url, 'video', $params);

        $this->displayFooter();
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
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>';
    }

    /**
     * getUploadStatus
     * 
     * Check the upload status of a video.
     * 
     * @param string $videoId 
     * 
     * @return string
     */
    function getUploadStatus ($videoId)
    {
        $message = '';

        // Get authed google client
        $googleClient = getAuthedGoogleClient($this->fcmsUser->id);

        // Get video entry
        try
        {
            $youtube = new Google_Service_YouTube($googleClient);

            $videoEntry = $youtube->videos->listVideos('status,processingDetails', array('id'=>$videoId));
        }
        catch (Exception $e)
        {
            $this->fcmsError->add(array(
                'type'    => 'operation',
                'message' => 'Could not search YouTube.',
                'error'   => $e,
                'file'    => __FILE__,
                'line'    => __LINE__,
            ));
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // Make sure we found the video first
        if (!(isset($videoEntry['items'][0])))
        {
            return T_('Could not find video');
        }

        $status = $videoEntry['items'][0]['status']['uploadStatus'];

        // Let's handle all the upload statuses
        if ($status === 'deleted')
        {
            $message = T_('Video has been deleted.');
        }
        else if ($status === 'failed')
        {
            $message = T_('Video failed to upload.');
        }
        else if ($status === 'rejected')
        {
            $message = T_('Video has been rejected.');
        }
        else if ($status === 'uploaded')
        {
            $percentComplete = 0;

            $steps = array(
                'fileDetailsAvailability',
                'processingIssuesAvailability',
                'tagSuggestionsAvailability',
                'editorSuggestionsAvailability',
                'thumbnailsAvailability'
            );

            foreach ($steps as $step)
            {
                if ($videoEntry['items'][0]['processingDetails'][$step] === 'available')
                {
                    $percentComplete += 20;
                }
            }

            $message = $percentComplete;
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
        $userId   = (int)$_GET['u'];
        $videoId  = $_GET['id'];
        $comments = strip_tags($_POST['comments']);

        if (!empty($comments))
        {
            $sql = "INSERT INTO `fcms_video_comment`
                        (`video_id`, `comment`, `created`, `created_id`, `updated`, `updated_id`) 
                    VALUES
                        (?, ?, NOW(), ?, NOW(), ?)";

            $params = array(
                $videoId, 
                $comments, 
                $this->fcmsUser->id, 
                $this->fcmsUser->id
            );

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

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
        $userId = (int)$userId;

        $sql = "SELECT `youtube_session_token`
                FROM `fcms_user_settings`
                WHERE `user` = '$userId'
                AND `youtube_session_token` IS NOT NULL
                AND `youtube_session_token` != ''";

        $row = $this->fcmsDatabase->getRow($sql, $userId);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return false;
        }

        if (empty($row))
        {
            echo '<p class="error-alert">'.T_('Could not find session token for user.').'</p>';

            return false;
        }

        return $row['youtube_session_token'];
    }

    /**
     * displayMembersListPage 
     * 
     * @return void
     */
    function displayMembersListPage ()
    {
        $this->displayHeader();

        $sql = "SELECT v.`id`, COUNT(*) AS 'count', v.`created_id` AS 'user_id', u.`fname`, u.`lname`, u.`avatar`, u.`gravatar`
                FROM `fcms_video` AS v
                LEFT JOIN `fcms_users` AS u ON v.`created_id` = u.`id`
                WHERE `active` = 1
                GROUP BY v.`created_id`
                ORDER BY v.`updated` DESC";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        echo '
        <div id="sections_menu">
            <ul>
                <li><a href="video.php">Latest Videos</a></li>
            </ul>
        </div>
        <ul id="large_video_users">';

        foreach ($rows as $row)
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

        $this->displayFooter();
    }

    /**
     * displayUserVideosPage 
     * 
     * @return void
     */
    function displayUserVideosPage ()
    {
        $this->displayHeader();

        $userId = (int)$_GET['u'];

        if (isset($_SESSION['message']))
        {
            $this->displayMessage($_SESSION['message']);
        }

        // Get user info
        $sql = "SELECT 'id', `fname`, `lname`, `avatar`, `gravatar`
                FROM `fcms_users`
                WHERE `id` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $userId);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (empty($row))
        {
            echo '<div class="error-alert">'.T_('Member not found.').'</div>';
            $this->displayFooter();

            return;
        }

        $name       = cleanOutput($row['fname']).' '.cleanOutput($row['lname']);
        $avatarPath = getAvatarPath($row['avatar'], $row['gravatar']);

        echo '
        <div id="sections_menu">
            <ul>
                <li><a href="video.php">Latest Videos</a></li>
                <li><a href="video.php?members=all">Members</a></li>
            </ul>
        </div>

        <div id="video_content">
            <div id="member">
                <img src="'.$avatarPath.'" titl="'.$name.'"/>
                <span>'.T_('Videos For:').'</span>
                <h2>'.$name.'</h2>
            </div>
            <ul class="categories">';

        // Get videos
        $sql = "SELECT `id`, `source_id`, `title`, `active`, `created`, `created_id`
                FROM `fcms_video`
                WHERE `created_id` = ?
                ORDER BY `updated` DESC";

        $rows = $this->fcmsDatabase->getRows($sql, $userId);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (count($rows) <= 0)
        {
            echo '<div class="error-alert">'.T_('No videos found.').'</div>';
            $this->displayFooter();

            return;
        }

        foreach ($rows as $row)
        {
            $class = '';

            if ($row['active'] == '0')
            {
                if ($row['created_id'] != $this->fcmsUser->id)
                {
                    continue;
                }

                $class = 'removed';
            }

            $date = fixDate('Y-m-d', '', $row['created'], $this->fcmsUser->id);

            echo '
                <li class="category '.$class.'">
                    <a href="?u='.$userId.'&amp;id='.$row['id'].'"><img src="http://i.ytimg.com/vi/'.$row['source_id'].'/default.jpg"/></a>
                    <span>
                        <strong>'.cleanOutput($row['title']).'</strong>
                        <i>'.sprintf(T_pgettext('%s is a date', 'on %s'), $date).'</i>
                    </span>
                </li>';
        }

        $this->displayFooter();
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
        if (!isset($_POST['id']) || !isset($_POST['source_id']))
        {
            $this->displayHeader();
            echo '<div class="error_alert">'.T_('Can\'t remove video.  Missing video id.').'</div>';
            $this->displayFooter();
            return;
        }

        $userId   = (int)$_GET['u'];
        $id       = (int)$_POST['id'];
        $sourceId = $_POST['source_id'];

        $sql = "UPDATE `fcms_video`
                SET `active` = 0,
                    `updated` = NOW(),
                    `updated_id` = ?
                WHERE `id` = ?";
    
        if (!$this->fcmsDatabase->update($sql, array($this->fcmsUser->id, $id)))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (isset($_POST['delete_youtube']))
        {
            try
            {
                $googleClient = getAuthedGoogleClient($this->fcmsUser->id);

                $youtube = new Google_Service_YouTube($googleClient);
                $youtube->videos->delete($sourceId);
            }
            catch (Exception $e)
            {
                $this->displayHeader();
                $this->fcmsError->add(array(
                    'type'    => 'operation',
                    'message' => 'Could not upload video to YouTube.',
                    'error'   => $e,
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ));
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
            // Set message
            $_SESSION['message'] = 'delete_video_youtube';
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
        if (!isset($_POST['id']) || !isset($_POST['source_id']))
        {
            $this->displayHeader();
            echo '<div class="error_alert">'.T_('Can\'t delete video.  Missing video id.').'</div>';
            $this->displayFooter();
            return;
        }

        $userId   = (int)$_GET['u'];
        $id       = (int)$_POST['id'];
        $sourceId = $_POST['source_id'];

        $sql = "DELETE FROM `fcms_video_comment`
                WHERE `video_id` = ?";

        if (!$this->fcmsDatabase->delete($sql, $id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $sql = "DELETE FROM `fcms_video`
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->delete($sql, $id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
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
        $userId  = (int)$_GET['u'];
        $videoId = (int)$video['id'];

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
}
