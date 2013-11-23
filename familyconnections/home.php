<?php
/**
 * Home
 * 
 * PHP version 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
if (!isset($_SESSION))
{
    session_start();
}

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load(
    'Upload_PhotoGallery',
    'datetime', 
    'calendar', 
    'Poll', 
    'alerts',
    'socialmedia',
    'facebook',
    'gallery'
);

init();

$calendar = new Calendar($fcmsError, $fcmsDatabase, $fcmsUser);
$poll     = new Poll($fcmsError, $fcmsDatabase, $fcmsUser);
$alert    = new Alerts($fcmsError, $fcmsDatabase, $fcmsUser);
$gallery  = new PhotoGallery($fcmsError, $fcmsDatabase, $fcmsUser);
$page     = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $calendar, $poll, $alert, $gallery);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsCalendar;
    private $fcmsPoll;
    private $fcmsAlert;
    private $fcmsPhotoGallery;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsCalendar, $fcmsPoll, $fcmsAlert, $fcmsPhotoGallery)
    {
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;
        $this->fcmsCalendar     = $fcmsCalendar;
        $this->fcmsPoll         = $fcmsPoll;
        $this->fcmsAlert        = $fcmsAlert;
        $this->fcmsPhotoGallery = $fcmsPhotoGallery;

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
        // Update activity
        $sql = "UPDATE `fcms_users`
                SET `activity` = NOW()
                WHERE `id` = ?";

        $this->fcmsDatabase->update($sql, $this->fcmsUser->id);

        if (isset($_POST['status_submit']))
        {
            $this->displayStatusUpdateSubmit();
        }
        else
        {
            $this->displayWhatsNew();
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
            'sitename'      => cleanOutput(getSiteName()),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_pgettext('The beginning or starting place.', 'Home'),
            'pageId'        => 'home',
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        $options = array(
            'js'       => '<script type="text/javascript">var position = 0;</script>',
            'jsOnload' => 'document.onkeydown = nextPrevNews;',
        );

        displayPageHeader($params, $options);
    }

    /**
     * displayFooter 
     * 
     * @return void
     */
    function displayFooter ()
    {
        $params = array(
            'path'          => URL_PREFIX,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        loadTemplate('home', 'footer');
        loadTemplate('global', 'footer', $params);
    }

    /**
     * displayLeftColumn 
     * 
     * @return void
     */
    function displayLeftColumn ()
    {
        $year  = fixDate('Y', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        $month = fixDate('m', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        $day   = fixDate('d', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));

        $templateParams = array(
            'textCalendar'      => T_('Calendar'),
            'textUpcoming'      => T_('Upcoming'),
            'textMembersOnline' => T_('Members Online'),
        );

        // Get calendar
        $calendarParams = $this->fcmsCalendar->getSmallCalendar($month, $year, $day);
        if ($calendarParams === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        // Get Events
        $eventParams = $this->fcmsCalendar->getMonthEvents($month, $year);
        if ($eventParams === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        // Get Poll
        $pollParams = $this->getLatestPollParams();
        if ($pollParams === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        // Get Members Online
        $onlineParams = $this->getMembersOnline();
        if ($onlineParams === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $templateParams['events'] = $eventParams;

        $templateParams = array_merge($templateParams, $calendarParams);
        $templateParams = array_merge($templateParams, $pollParams);
        $templateParams = array_merge($templateParams, $onlineParams);

        // Load the sidebar
        loadTemplate('home', 'sidebar', $templateParams);
    }

    /**
     * displayWhatsNew 
     * 
     * @return void
     */
    function displayWhatsNew ()
    {
        $this->displayHeader();
        $this->displayLeftColumn();

        // Remove an alert
        if (isset($_GET['alert']))
        {
            $alert = $_GET['alert'];

            $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
                    VALUES (?, ?)";

            $params = array($alert, $this->fcmsUser->id);

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        // Show Alerts
        $alertShown = $this->fcmsAlert->displayNewUserHome($this->fcmsUser->id);

        list($db_year, $db_month, $db_day) = explode('-', date('Y-m-d'));

        // Template params
        $templateParams = array(
            'textSharePlaceholder'  => T_('Share'),
            'textShareTitle'        => T_('Share something with everyone'),
            'textSubmit'            => T_('Submit'),
        );

        // Are we using facebook
        $data = getFacebookConfigData();
        $user = null;
        if (!empty($data['fb_app_id']) && !empty($data['fb_secret']))
        {
            $facebook = new Facebook(array(
              'appId'  => $data['fb_app_id'],
              'secret' => $data['fb_secret'],
            ));

            // Check if the user is logged in and authed
            $user = $facebook->getUser();
            if ($user)
            {
                try
                {
                    $user_profile = $facebook->api('/me');
                }
                catch (FacebookApiException $e)
                {
                    $user = null;
                }
            }
        }
        if ($user)
        {
            $templateParams['textUpdateFacebook'] = T_('Update Facebook?');
        }

        $todaysEventsParams = $this->fcmsCalendar->getTodaysEventsTemplateParams($db_month, $db_day, $db_year);
        $templateParams = array_merge($templateParams, $todaysEventsParams);

        // Load the main template
        loadTemplate('home', 'main', $templateParams);

        $this->displayWhatsNewAll();
        $this->displayFooter();
    }

    /**
     * displayStatusUpdateSubmit 
     * 
     * @return void
     */
    function displayStatusUpdateSubmit ()
    {
        $status = $_POST['status'];
        $parent = 0;

        // Submited blank form?
        if (empty($_POST['status']))
        {
            header("Location: home.php");
            return;
        }

        if (isset($_POST['parent']))
        {
            $parent = (int)$_POST['parent'];
        }

        // Insert new status
        $sql = "INSERT INTO `fcms_status`
                    (`user`, `status`, `parent`, `created`, `updated`)
                VALUES
                    (?, ?, ?, NOW(), NOW())";

        $params = array(
            $this->fcmsUser->id,
            $status,
            $parent,
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // If replying, update the orig status updated date, so it bumps up to the top of the list
        if ($parent > 0)
        {
            $sql = "UPDATE `fcms_status`
                    SET `updated` = NOW()
                    WHERE `id` = ?
                    LIMIT 1;";

            if (!$this->fcmsDatabase->update($sql, $parent))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        // Post to facebook
        if (isset($_POST['update_fb']))
        {
            $data = getFacebookConfigData();

            // Send status to facebook
            if (!empty($data['fb_app_id']) && !empty($data['fb_secret']))
            {
                $facebook = new Facebook(array(
                    'appId'  => $data['fb_app_id'],
                    'secret' => $data['fb_secret'],
                ));

                // Check if the user is logged in and authed
                $user = $facebook->getUser();
                if ($user)
                {
                    try
                    {
                        $statusUpdate = $facebook->api('/me/feed', 'post', array('message'=> $_POST['status'], 'cb' => ''));
                    }
                    catch (FacebookApiException $e)
                    {
                        printr($e);
                    }
                }
            }
        }

        // Email members
        $sql = "SELECT u.`email`, s.`user` 
                FROM `fcms_user_settings` AS s, `fcms_users` AS u 
                WHERE `email_updates` = '1'
                AND u.`id` = s.`user`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        if (count($rows) > 0)
        {
            $url     = getDomainAndDir();
            $headers = getEmailHeaders();
            $name    = getUserDisplayName($this->fcmsUser->id);

            foreach ($rows as $r)
            {
                $to      = getUserDisplayName($r['user']);
                $subject = sprintf(T_('%s added a new status update.'), $name);
                $email   = $r['email'];

                $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'home.php

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';

                mail($email, $subject, $msg, $headers);
            }
        }

        header("Location: home.php");
    }

    /**
     * getLatestPollParams
     * 
     * @return mixed - array on success, false on failure
     */
    function getLatestPollParams ()
    {
        $pollData = $this->fcmsPoll->getLatestPollData();
        if ($pollData === false)
        {
            return false;
        }

        if (count($pollData) <= 0)
        {
            # we have no polls
            return array();
        }

        $pollId      = key($pollData);
        $pollOptions = array();

        // Show results - user already voted
        if (isset($pollData['users_who_voted'][$this->fcmsUser->id]))
        {
            $submitValue = T_('Already Voted');
            $class       = 'disabled';
            $disabled    = 'disabled="disabled"';

            $pollResults = $this->fcmsPoll->formatPollResults($pollData);
            if ($pollResults === false)
            {
                return false;
            }

            return array(
                'pollId'          => $pollId,
                'textPolls'       => T_('Polls'),
                'pollQuestion'    => cleanOutput($pollData[$pollId]['question'], 'html'),
                'textPastPolls'   => T_('Past Polls'),
                'pollResults'     => $pollResults,
            );
        }
        // Show options
        else
        {
            foreach ($pollData[$pollId]['options'] as $optionId => $optionData)
            {
                $pollOptions[] = array(
                    'id'   => (int)$optionId,
                    'text' => cleanOutput($optionData['option'], 'html'),
                );
            }
        }

        return array(
            'pollId'          => $pollId,
            'textPolls'       => T_('Polls'),
            'pollQuestion'    => cleanOutput($pollData[$pollId]['question'], 'html'),
            'textPollVote'    => T_('Vote'),
            'textPollResults' => T_('Results'),
            'textPastPolls'   => T_('Past Polls'),
            'pollOptions'     => $pollOptions,
        );
    }

    /**
     * displayWhatsNewAll 
     * 
     * Displays the following types of new data from the site:
     *
     *  ADDRESSADD      Add address of non-member
     *  ADDRESSEDIT     Edit own address
     *  AVATAR          Change avatar
     *  BOARD           Message board post
     *  CALENDAR        Add date to calendar
     *  DOCS            Added document
     *  GALCATCOM       Commented on category of photos
     *  GALCOM          Commented on photo
     *  GALLERY         Added photo
     *  JOINED          Joined the site (became active)
     *  NEWS            Added family news
     *  NEWSCOM         Commented on family news
     *  POLL            Added poll
     *  POLLCOM         Commented on poll
     *  PRAYERS         Added prayer concern
     *  RECIPES         Added recipe
     *  RECIPECOM       Commented on recipe
     *  STATUS          Added status update
     *  VIDEO           Added video
     *  VIDEOCOM        Commented on video
     *  WHEREISEVERYONE Checked in on foursquare
     *
     * @return void
     */
    function displayWhatsNewAll ()
    {
        $lastday = '0-0';

        $today_start = fixDate('Ymd', $this->fcmsUser->tzOffset, date('Y-m-d H:i:s')) . '000000';
        $today_end   = fixDate('Ymd', $this->fcmsUser->tzOffset, date('Y-m-d H:i:s')) . '235959';

        $time            = mktime(0, 0, 0, date('m')  , date('d')-1, date('Y'));
        $yesterday_start = fixDate('Ymd', $this->fcmsUser->tzOffset, date('Y-m-d H:i:s', $time)) . '000000';
        $yesterday_end   = fixDate('Ymd', $this->fcmsUser->tzOffset, date('Y-m-d H:i:s', $time)) . '235959';

        // Get data
        $whatsNewData = getWhatsNewData(30);
        if ($whatsNewData === false)
        {
            return;
        }

        $cachedUserData = array();

        $position = 1;
        $older    = true;

        $newParams = array(
            'textWhatsNew'  => T_('What\'s New'),
            'textRssFeed'   => T_('RSS Feed'),
            'new'           => array(),
        );

        // Loop through data
        foreach ($whatsNewData as $r)
        {
            $updated     = fixDate('Ymd',    $this->fcmsUser->tzOffset, $r['date']);
            $updatedFull = fixDate('YmdHis', $this->fcmsUser->tzOffset, $r['date']);

            // Date Header
            if ($updated != $lastday)
            {
                // Today
                if ($updatedFull >= $today_start && $updatedFull <= $today_end)
                {
                    $newParams['new'][] = array('textDateHeading' => T_('Today'));
                }
                // Yesterday
                if ($updatedFull >= $yesterday_start && $updatedFull <= $yesterday_end)
                {
                    $newParams['new'][] = array('textDateHeading' => T_('Yesterday'));
                }
                // Older
                if ($updatedFull < $yesterday_start && $older)
                {
                    $newParams['new'][] = array('textDateHeading' => T_('Older'));
                    $older       = false;
                }
            }

            $rtime = strtotime($r['date']);

            $displayname = '';
            $avatar      = '';

            // Use cached data
            if (isset($cachedUserData[$r['userid']]))
            {
                $displayname = $cachedUserData[$r['userid']]['displayname'];
                $avatar      = $cachedUserData[$r['userid']]['avatar'];
            }
            // Get new data
            else
            {
                $displayname = getUserDisplayName($r['userid']);
                $avatar      = getCurrentAvatar($r['userid']);

                // Save this for later
                $cachedUserData[$r['userid']]['avatar']      = $avatar;
                $cachedUserData[$r['userid']]['displayname'] = $displayname;
            }

            if ($r['type'] == 'ADDRESSADD')
            {
                $displayname = getUserDisplayName($r['id2']);
                $for         = '<a href="addressbook.php?address='.$r['id'].'">'.getUserDisplayName($r['userid'], 2, false).'</a>';

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newaddress',
                    'avatar'        => getCurrentAvatar($r['id2']),
                    'displayname'   => $displayname,
                    'userId'        => $r['id2'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Added address information for %s.'), $for),
                );
            }
            elseif ($r['type'] == 'ADDRESSEDIT')
            {
                if ($r['title'] == 'address')
                {
                    $titleType = T_('address');
                }
                elseif ($r['title'] == 'email')
                {
                    $titleType = T_('email address');
                }
                elseif ($r['title'] == 'home')
                {
                    $titleType = T_('home phone number');
                }
                elseif ($r['title'] == 'work')
                {
                    $titleType = T_('work phone number');
                }
                elseif ($r['title'] == 'cell')
                {
                    $titleType = T_('cell phone number');
                }
                // this shouldn't happen
                else
                {
                    $titleType = T_('address');
                }

                $address = '<a href="addressbook.php?address='.$r['id'].'">'.$titleType.'</a>';

                if ($r['id2'] != $r['userid'])
                {
                    $user = getUserDisplayName($r['userid']);
                    $text = sprintf(T_pgettext('Example: "Updated the <address/phone/email> for <name>."', 'Updated the %s for %s.'), $address, $user);
                }
                else
                {
                    if ($r['id3'] == 'F')
                    {
                        $text = sprintf(T_pgettext('Example: "Updated her <address/phone/email>."', 'Updated her %s.'), $address);
                    }
                    else
                    {
                        $text = sprintf(T_pgettext('Example: "Updated his <address/phone/email>."', 'Updated his %s.'), $address);
                    }
                }

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newaddress',
                    'avatar'        => getCurrentAvatar($r['id2']),
                    'displayname'   => getUserDisplayName($r['id2']),
                    'userId'        => $r['id2'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => $text,
                );
            }
            elseif ($r['type'] == 'AVATAR')
            {
                $text = T_('Changed his profile picture.');

                if ($r['id3'] == 'F')
                {
                    $text = T_('Changed her profile picture.');
                }

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newavatar',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => $text,
                );
            }
            elseif ($r['type'] == 'BOARD')
            {
                $sql = "SELECT MIN(`id`) AS 'id' 
                        FROM `fcms_board_posts` 
                        WHERE `thread` = ?";

                $minpost = $this->fcmsDatabase->getRow($sql, $r['id2']);
                if ($minpost === false)
                {
                    $this->fcmsError->displayError();
                    return;
                }

                $subject  = $r['title'];

                $pos = strpos($subject, '#ANOUNCE#');
                if ($pos !== false)
                {
                    $subject = substr($subject, 9, strlen($subject)-9);
                }

                $title   = cleanOutput($subject);
                $subject = cleanOutput($subject);
                $subject = '<a href="messageboard.php?thread='.$r['id2'].'" title="'.$title.'">'.$subject.'</a>';

                if ($r['id'] == $minpost['id'])
                {
                    $class = 'newthread';
                    $text = sprintf(T_('Started the new thread %s.'), $subject);
                }
                else
                {
                    $class = 'newpost';
                    $text = sprintf(T_('Replied to %s.'), $subject);
                }

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => $class,
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => $text,
                );
            }
            elseif ($r['type'] == 'CALENDAR')
            {
                $date_date = date('F j, Y', strtotime($r['id2']));
                $for       = '<a href="calendar.php?event='.$r['id'].'">'.cleanOutput($r['title']).'</a>';

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newcal',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => $for.' - '.$date_date,
                );
            }
            elseif ($r['type'] == 'DOCS')
            {
                $doc = '<a href="documents.php">'.cleanOutput($r['title']).'</a>';

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newdocument',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Added a new Document (%s).'), $doc),
                );
            }
            elseif ($r['type'] == 'GALCATCOM')
            {
                $category = '<a href="gallery/index.php?uid='.$r['id2'].'&amp;cid='.$r['id3'].'">'.cleanOutput($r['title']).'</a>';

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newcom',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Commented on %s.'), $category),
                );
            }
            elseif ($r['type'] == 'GALCOM')
            {
                $data = array(
                    'id'          => $r['id'],
                    'user'        => $r['id2'],
                    'filename'    => $r['id3'],
                    'external_id' => null,
                    'thumbnail'   => null
                );

                if ($r['id3'] == 'noimage.gif')
                {
                    $sql = "SELECT p.`id`, p.`filename`, p.`external_id`, e.`thumbnail`
                            FROM `fcms_gallery_photos` AS p
                            LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                            WHERE p.`id` = '".(int)$r['id']."'";

                    $p = $this->fcmsDatabase->getRow($sql, $r['id']);
                    if ($p === false)
                    {
                        $this->fcmsError->displayError();
                        return;
                    }

                    $data['external_id'] = $p['external_id'];
                    $data['thumbnail']   = $p['thumbnail'];
                }

                $photoSrc = $galleryObj->getPhotoSource($data);

                $text = T_('Commented on the following photo:').'<br/><a href="gallery/index.php?uid=0&amp;cid=comments&amp;pid='.$r['id'].'"><img src="'.$photoSrc.'"/></a>';

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newcom',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => $text,
                );
            }
            elseif ($r['type'] == 'GALLERY')
            {
                $cat    = '<a href="gallery/index.php?uid='.$r['userid'].'&amp;cid='.$r['id'].'">'.cleanOutput($r['title']).'</a>';
                $photos = '';

                $limit = 4;
                if ($r['id2'] < $limit)
                {
                    $limit = $r['id2'];
                }
                $sql = "SELECT p.`id`, p.`user`, p.`category`, p.`filename`, p.`caption`,
                            p.`external_id`, e.`thumbnail`, e.`medium`, e.`full`
                        FROM `fcms_gallery_photos` AS p
                        LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                        WHERE p.`category` = '".(int)$r['id']."' 
                        AND DAYOFYEAR(p.`date`) = '".(int)$r['id3']."' 
                        ORDER BY p.`date` 
                        DESC LIMIT $limit";

                $photo_rows = $this->fcmsDatabase->getRows($sql, array($r['id'], $r['id3']));
                if ($photo_rows === false)
                {
                    $this->fcmsError->displayError();
                    return;
                }

                foreach ($photo_rows as $p)
                {
                    $photoSrc = $galleryObj->getPhotoSource($p);

                    $photos .= '
                            <a href="gallery/index.php?uid='.$r['userid'].'&amp;cid='.$r['id'].'&amp;pid='.$p['id'].'">
                                <img src="'.$photoSrc.'" alt="'.cleanOutput($p['caption']).'"/>
                            </a> &nbsp;';
                }

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newphoto',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Added %d new photos to the %s category.'), $r['id2'], $cat).'<br/>'.$photos,
                );
            }
            elseif ($r['type'] == 'JOINED')
            {
                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newmember',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => T_('Joined the website.'),
                );
            }
            elseif ($r['type'] == 'NEWS')
            {
                $title = !empty($r['title']) ? cleanOutput($r['title']) : T_('untitled');
                $news  = '<a href="familynews.php?getnews='.$r['userid'].'&amp;newsid='.$r['id'].'">'.$title.'</a>'; 

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newnews',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Added %s to his/her Family News.'), $news),
                );
            }
            elseif ($r['type'] == 'NEWSCOM')
            {
                $news = '<a href="familynews.php?getnews='.$r['userid'].'&amp;newsid='.$r['id'].'">'.cleanOutput($r['title']).'</a>';

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newcom',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Commented on Family News %s.'), $news),
                );
            }
            elseif ($r['type'] == 'POLL')
            {
                $poll = '<a href="polls.php?id='.$r['id'].'">'.cleanOutput($r['title']).'</a>';

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newpoll',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Added a new poll %s.'), $poll),
                );
            }
            elseif ($r['type'] == 'POLLCOM')
            {
                $poll = '<a href="polls.php?id='.$r['id'].'"#comments>'.cleanOutput($r['title']).'</a>';

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'pollcom',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Commented on Poll %s.'), $poll),
                );
            }
            elseif ($r['type'] == 'PRAYERS')
            {
                $for = '<a href="prayers.php">'.cleanOutput($r['title']).'</a>';

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newprayer',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Added a Prayer Concern for %s.'), $for),
                );
            }
            elseif ($r['type'] == 'RECIPES')
            {
                $rec = '<a href="recipes.php?category='.$r['id2'].'&amp;id='.$r['id'].'">'.cleanOutput($r['title']).'</a>';

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newrecipe',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Added the %s recipe.'), $rec),
                );
            }
            elseif ($r['type'] == 'RECIPECOM')
            {
                $rec = '<a href="recipes.php?category='.$r['id2'].'&amp;id='.$r['id'].'">'.cleanOutput($r['title']).'</a>';

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newcom',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Commented on Recipe %s.'), $rec),
                );
            }
            elseif ($r['type'] == 'STATUS')
            {
                $title = cleanOutput($r['title']);
                $title = nl2br_nospaces($title);

                // Get any replies to this status update
                $sql = "SELECT `id`, `user`, `status`, `parent`, `updated`, `created` 
                        FROM `fcms_status` 
                        WHERE `parent` = ?
                        ORDER BY `id`";

                $rows = $this->fcmsDatabase->getRows($sql, $r['id']);
                if ($rows === false)
                {
                    $this->fcmsError->displayError();
                    return;
                }

                $children = array();

                if (count($rows) > 0)
                {
                    foreach ($rows as $s)
                    {
                        $status = cleanOutput($s['status']);
                        $status = nl2br_nospaces($status);

                        $children[] = array(
                            'class'         => 'newstatus',
                            'avatar'        => getCurrentAvatar($s['user']),
                            'displayname'   => getUserDisplayName($s['user']),
                            'userId'        => $s['user'],
                            'timeSince'     => getHumanTimeSince(strtotime($s['created'])),
                            'textInfo'      => $status
                        );
                    }
                }

                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newcom',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => (int)$r['userid'],
                    'timeSince'     => getHumanTimeSince(strtotime($r['id3'])),
                    'textInfo'      => $title,
                    'children'      => $children,
                    'textReply'     => T_('Reply'),
                    'replyParentId' => (int)$r['id'],
                );
            }
            elseif ($r['type'] == 'VIDEO')
            {
                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newvideo',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => T_('Added a new Video.').'<br/><a href="video.php?u='.$r['userid'].'&amp;id='.$r['id'].'"><img src="http://i.ytimg.com/vi/'.$r['id2'].'/default.jpg"/></a>',
                );
            }
            elseif ($r['type'] == 'VIDEOCOM')
            {
                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newvideo',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => T_('Commented on the following Video:').'<br/><a href="video.php?u='.$r['userid'].'&amp;id='.$r['id'].'#comments"><img src="http://i.ytimg.com/vi/'.$r['id2'].'/default.jpg"/></a>',
                );
            }
            elseif ($r['type'] == 'WHEREISEVERYONE')
            {
                $newParams['new'][] = array(
                    'position'      => $position,
                    'class'         => 'newvideo',
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => $r['userid'],
                    'timeSince'     => getHumanTimeSince($rtime),
                    'textInfo'      => sprintf(T_('Visited %s.'), $r['title']).'<br/><br/>'.(!empty($r['id2']) ? '<blockquote>'.cleanOutput($r['id2']).'</blockquote>' : ''),
                );
            }

            $position++;

            $lastday = $updated;
        }

        loadTemplate('home', 'new', $newParams);
    }

    /**
     * getMembersOnline 
     * 
     * @return mixed - array on success, false on failure
     */
    function getMembersOnline ()
    {
        $membersOnline = array(
            'textLastSeen'  => T_('Last Seen'),
            'membersOnline' => array(),
        );

        $last24hours = time() - (60 * 60 * 24);

        $sql = "SELECT * 
                FROM fcms_users 
                WHERE UNIX_TIMESTAMP(`activity`) >= ?
                ORDER BY `activity` DESC";

        $rows = $this->fcmsDatabase->getRows($sql, $last24hours);    
        if ($rows === false)
        {
            $this->fcmsError->setMessage('Could not get members online.');
            return false;
        }

        foreach ($rows as $r)
        {
            $membersOnline['membersOnline'][] = array(
                'id'          => (int)$r['id'],
                'avatar'      => getCurrentAvatar($r['id']),
                'displayname' => getUserDisplayName($r['id']),
                'since'       => getHumanTimeSince(strtotime($r['activity'])),
            );
        }

        return $membersOnline;
    }

}
