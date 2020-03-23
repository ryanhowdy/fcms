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
                'pollFormClass'   => 'poll-small',
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
            'pollFormClass'   => 'poll-small',
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
        $page    = getPage();
        $perPage = 30;
        $from    = ($page * $perPage) - $perPage;

        // Get data
        $whatsNewData = getWhatsNewData($perPage, $from);
        if ($whatsNewData === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $template = array(
            'textWhatsNew'         => T_('What\'s New'),
            'textRssFeed'          => T_('RSS Feed'),
            'new'                  => array(),
            'textBlankHeader'      => T_('Nothing New'),
            'textBlankDescription' => T_('Bummer, nothing new has happened in the last 30 days.'),
            'page'                 => ($page+1),
            'txtMore'              => T_('Show More'),
        );

        $position       = 1;
        $cachedUserData = array();
        $totalData      = 0;

        // Loop through data
        foreach ($whatsNewData as $groupType => $data)
        {
            $totalData++;

            $parent   = array_shift($data);
            $data     = array_reverse($data);

            // handle children (replies, etc)
            $children = array();
            foreach ($data as $d)
            {
                $totalData++;

                // Use cached data
                if (isset($cachedUserData[$d['userid']]))
                {
                    $displayname = $cachedUserData[ $d['userid'] ]['displayname'];
                    $avatar      = $cachedUserData[ $d['userid'] ]['avatar'];
                }
                // Get new data
                else
                {
                    $displayname = getUserDisplayName($d['userid']);
                    $avatar      = getCurrentAvatar($d['userid']);

                    // Save this for later
                    $cachedUserData[ $d['userid'] ]['avatar']      = $avatar;
                    $cachedUserData[ $d['userid'] ]['displayname'] = $displayname;
                }

                $timeSince = $this->getWhatsNewDataTimeSince($d);
                $textInfo  = $this->getWhatsNewDataTextInfo($d);
                $object    = $this->getWhatsNewDataObject($d);

                $children[] = array(
                    'class'         => 'new'.strtolower($d['type']),
                    'avatar'        => $avatar,
                    'displayname'   => $displayname,
                    'userId'        => (int)$d['userid'],
                    'timeSince'     => $timeSince,
                    'textInfo'      => $textInfo,
                    'details'       => $object['details'],
                );
            }

            $timeSince = $this->getWhatsNewDataTimeSince($parent);
            $textInfo  = $this->getWhatsNewDataTextInfo($parent);
            $object    = $this->getWhatsNewDataObject($parent);

            // Use cached data
            if (isset($cachedUserData[$parent['userid']]))
            {
                $displayname = $cachedUserData[ $parent['userid'] ]['displayname'];
                $avatar      = $cachedUserData[ $parent['userid'] ]['avatar'];
            }
            // Get new data
            else
            {
                $displayname = T_('System');
                if (!startsWith($groupType, 'poll'))
                {
                    // polls don't have user ids
                    $displayname = getUserDisplayName($parent['userid']);
                }

                $avatar = getCurrentAvatar($parent['userid']);

                // Save this for later
                $cachedUserData[ $parent['userid'] ]['avatar']      = $avatar;
                $cachedUserData[ $parent['userid'] ]['displayname'] = $displayname;
            }

            $params = array(
                'position'      => $position,
                'class'         => 'new'.strtolower($parent['type']),
                'avatar'        => $avatar,
                'displayname'   => $displayname,
                'userId'        => (int)$parent['userid'],
                'timeSince'     => $timeSince,
                'textInfo'      => $textInfo,
                'title'         => $object['title'],
                'details'       => $object['details'],
                'children'      => $children,
            );

            if (startsWith($groupType, 'status'))
            {
                $params['textReply']     = T_('Reply');
                $params['replyParentId'] = (int)$parent['id'];
            }

            $template['new'][] = $params;

            $position++;
        }

        if ($totalData < $perPage)
        {
            unset($template['page']);
            unset($template['txtMore']);
        }

        loadTemplate('home', 'new', $template);
    }

    /**
     * getWhatsNewDataTimeSince 
     * 
     * @param array $data 
     * 
     * @return string
     */
    function getWhatsNewDataTimeSince ($data)
    {
        $time = '';

        switch ($data['type'])
        {
            case 'STATUS':
                $time = getHumanTimeSince(strtotime($data['id3']));
                break;

            default:
                $time = getHumanTimeSince(strtotime($data['date']));
                break;
        }

        return $time;
    }

    /**
     * getWhatsNewDataTextInfo 
     * 
     * @param array $data 
     * 
     * @return string
     */
    function getWhatsNewDataTextInfo ($data)
    {
        $text = '';

        switch ($data['type'])
        {
            case 'ADDRESSADD':
                $displayname = getUserDisplayName($data['id2']);
                $for         = '<a href="addressbook.php?address='.(int)$data['id'].'">'.getUserDisplayName($data['userid'], 2, false).'</a>';
                $text        = sprintf(T_('Added address information for %s.'), $for);
                break;

            case 'ADDRESSEDIT':
                $text = $this->getWhatsNewDataAddressEditTextInfo($data);
                break;

            case 'AVATAR':
                $text = $data['id3'] == 'M' ? T_('Changed his profile picture.')
                                            : T_('Changed her profile picture.');
                break;

            case 'CALENDAR':
                $text = T_('Added a new event.');
                break;

            case 'DOCS':
                $text = T_('Added a new document.');
                break;

            case 'BOARD':
            case 'GALCATCOM':
            case 'NEWSCOM':
            case 'POLLCOM':
            case 'RECIPECOM':
            case 'VIDEOCOM':
                $text = cleanOutput($data['details']);
                break;

            case 'GALCOM':
                $text = T_('Commented on the following photo:');
                break;

            case 'GALLERY':
                $text = sprintf(T_('Added %d new photos.'), (int)$data['id2']);
                break;

            case 'JOINED':
                $text = T_('Joined the website.');
                break;

            case 'NEWS':
                $text = T_('Added new Family News.');
                break;

            case 'POLL':
                $text = T_('Added a new poll.');
                break;

            case 'PRAYERS':
                $text = T_('Added a new prayer concern.');
                break;

            case 'RECIPES':
                $text = T_('Added a new recipe.');
                break;

            case 'THREAD':
                $text = T_('Started a new thread.');
                break;

            case 'VIDEO':
                $text = T_('Added a new video.');
                break;

            default:
                $text = cleanOutput($data['title']);
                $text = nl2br_nospaces($text);
                break;
        }

        return $text;
    }

    /**
     * getWhatsNewDataAddressEditTextInfo 
     * 
     * @param array $data 
     * @todo  this should probably just say 'updated address'
     *        then the object info should show the details below
     * 
     * @return string
     */
    function getWhatsNewDataAddressEditTextInfo ($data)
    {
        $titleType = T_('address');

        if ($data['title'] == 'email')
        {
            $titleType = T_('email address');
        }
        elseif ($data['title'] == 'home')
        {
            $titleType = T_('home phone number');
        }
        elseif ($data['title'] == 'work')
        {
            $titleType = T_('work phone number');
        }
        elseif ($data['title'] == 'cell')
        {
            $titleType = T_('cell phone number');
        }

        $address = '<a href="addressbook.php?address='.(int)$data['id'].'">'.$titleType.'</a>';

        if ($data['id2'] != $data['userid'])
        {
            $user = getUserDisplayName($data['userid']);
            $text = sprintf(T_pgettext('Example: "Updated the <address/phone/email> for <name>."', 'Updated the %s for %s.'), $address, $user);
        }
        else
        {
            if ($data['id3'] == 'F')
            {
                $text = sprintf(T_pgettext('Example: "Updated her <address/phone/email>."', 'Updated her %s.'), $address);
            }
            else
            {
                $text = sprintf(T_pgettext('Example: "Updated his <address/phone/email>."', 'Updated his %s.'), $address);
            }
        }

        return $text;
    }

    /**
     * getWhatsNewDataObject
     * 
     * @param array $data 
     * 
     * @return array
     */
    function getWhatsNewDataObject ($data)
    {
        $title   = '';
        $details = '';

        switch ($data['type'])
        {
            case 'CALENDAR':
                $title   = '<a href="calendar.php?event='.$data['id'].'">'.cleanOutput($data['title']).'</a>';
                $details = date('F j, Y', strtotime($data['id2']));
                break;

            case 'DOCS':
                $title   = '<a href="documents.php">'.cleanOutput($data['title']).'</a>';
                $details = cleanOutput($data['details']);
                break;

            case 'GALCOM':
                foreach ($data['photos'] as $p)
                {
                    $photoSrc = $this->fcmsPhotoGallery->getPhotoSource($p);
                    $title    = '<a href="gallery/index.php?uid=0&amp;cid=comments&amp;pid='.(int)$data['id'].'"><img src="'.$photoSrc.'"/></a>';
                }
                $details = cleanOutput($data['title']);
                break;

            case 'GALLERY':
                $title   = '<a href="gallery/index.php?uid='.(int)$data['userid'].'&amp;cid='.$data['id'].'">'.cleanOutput($data['title']).'</a>';
                $details = '';

                foreach ($data['photos'] as $p)
                {
                    $photoSrc = $this->fcmsPhotoGallery->getPhotoSource($p);

                    $details .= '
                            <a href="gallery/index.php?uid='.(int)$data['userid'].'&amp;cid='.$data['id'].'&amp;pid='.(int)$p['id'].'">
                                <img src="'.$photoSrc.'" alt="'.cleanOutput($p['caption']).'"/>
                            </a> &nbsp;';
                }
                break;

            case 'NEWS':
                $name  = !empty($data['title']) ? cleanOutput($data['title']) : T_('untitled');
                $title = '<a href="familynews.php?getnews='.$data['userid'].'&amp;newsid='.$data['id'].'">'.$name.'</a>'; 

                $details = removeBBCode($data['details']);
                $details = cleanOutput($details);
                if (strlen($details) > 300)
                {
                    $details = substr($details, 0, 300);
                    $details .= '...<br/><br/><a href="familynews.php?getnews='.$data['userid'].'&amp;newsid='.(int)$data['id'].'">'.T_('Read More').'</a>';
                }

                break;

            case 'POLL':
                $title = '<a href="polls.php?id='.(int)$data['id'].'">'.cleanOutput($data['title']).'</a>';
                break;

            case 'PRAYERS':
                $title   = '<a href="prayers.php">'.cleanOutput($data['title']).'</a>';
                $details = cleanOutput($data['details']);
                break;

            case 'RECIPES':
                $title   = '<a href="recipes.php?category='.$data['id2'].'&amp;id='.$data['id'].'">'.cleanOutput($data['title']).'</a>';
                break;

            case 'THREAD':
                $subject  = $data['title'];
                $pos      = strpos($subject, '#ANOUNCE#');
                if ($pos !== false)
                {
                    $subject = substr($subject, 9, strlen($subject)-9);
                }

                $subject = cleanOutput($subject);
                $title   = '<a href="messageboard.php?thread='.(int)$data['id2'].'" title="'.$subject.'">'.$subject.'</a>';

                $details = removeBBCode($data['details']);
                $details = cleanOutput($details);
                if (strlen($details) > 300)
                {
                    $details = substr($details, 0, 300);
                    $details .= '...<br/><br/><a href="messageboard.php?thread='.(int)$data['id2'].'">'.T_('Read More').'</a>';
                }

                break;

            case 'VIDEO':
                $title = '<a href="video.php?u='.(int)$data['userid'].'&amp;id='.(int)$data['id'].'"><img src="http://i.ytimg.com/vi/'.$data['id2'].'/default.jpg"/></a>';
                break;

            default:
                $title   = '';
                $details = '';
                break;
        }

        return array(
            'title'   => $title,
            'details' => $details,
        );

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
