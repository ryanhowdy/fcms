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

require 'fcms.php';

load(
    'datetime', 
    'calendar', 
    'poll', 
    'database', 
    'alerts',
    'socialmedia',
    'facebook'
);

init();

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$calendar      = new Calendar($currentUserId);
$poll          = new Poll($currentUserId);
$alert         = new Alerts($currentUserId);

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_pgettext('The beginning or starting place.', 'Home'),
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

    // Update activity
    mysql_query("UPDATE `fcms_users` SET `activity`=NOW() WHERE `id` = '$currentUserId'");

    // previous poll
    if (isset($_GET['poll_id']) && !isset($_GET['action']) && !isset($_POST['vote']))
    {
        displayPoll();
    }
    // vote
    elseif (isset($_POST['vote']) and isset($_POST['option_id']))
    {
        displayVoteSubmit();
    }
    elseif (isset($_POST['status_submit']))
    {
        displayStatusUpdateSubmit();
    }
    elseif (isset($_GET['action']))
    {
        if ($_GET['action'] == "results")
        {
            displayPollResults();
        }
        else
        {
            displayPastPolls();
        }
    }
    else
    {
        displayWhatsNew();
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
Event.observe(window, "load", function() { initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\'); });
</script>';

    include_once getTheme($currentUserId).'header.php';

    echo '
        <div id="home" class="centercontent clearfix">';
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
            </div><!--/maincolumn-->
        </div><!--/centercontent -->';

    include_once getTheme($currentUserId).'footer.php';
}

/**
 * displayLeftColumn 
 * 
 * @return void
 */
function displayLeftColumn ()
{
    global $calendar, $poll;

    $year  = fixDate('Y', $calendar->tzOffset, gmdate('Y-m-d H:i:s'));
    $month = fixDate('m', $calendar->tzOffset, gmdate('Y-m-d H:i:s'));
    $day   = fixDate('d', $calendar->tzOffset, gmdate('Y-m-d H:i:s'));

    echo '
            <div id="leftcolumn">
                <h2 class="calmenu">'.T_('Calendar').'</h2>';

    $calendar->displaySmallCalendar($month, $year, $day);

    echo '
                <h3>'.T_('Upcoming').'</h3>';

    $calendar->displayMonthEvents($month, $year);

    if (canDisplaySmallPoll())
    {
        $poll->displayPoll('0', false);
    }

    echo '
                <h2 class="membermenu">'.T_('Members Online').'</h2>
                <div class="membermenu">';

    displayMembersOnline();

    echo '
                </div>
            </div>
            <div id="maincolumn">';

}

/**
 * canDisplaySmallPoll 
 *
 * 1. not voting                       - no POST[vote] or no POST[option_id]
 * 2. not looking at prev poll results - no GET[poll_id]
 * 3. not looking at prev poll list    - no GET[action]
 * 
 * @return void
 */
function canDisplaySmallPoll ()
{
    // Not if we are voting
    if (isset($_POST['vote']))
    {
        return false;
    }
    // Not if we are viewing a past poll
    elseif (isset($_GET['poll_Id']))
    {
        return false;
    }
    // Not if we are viewing past polls
    if (isset($_GET['action']))
    {
        return false;
    }

    return true;
}

/**
 * displayPoll 
 * 
 * @return void
 */
function displayPoll ()
{
    global $poll;

    displayHeader();
    displayLeftColumn();

    $id = cleanInput($_GET['poll_id'], 'int');
    $poll->displayPoll($id);

    displayFooter();
}

/**
 * displayVoteSubmit 
 * 
 * @return void
 */
function displayVoteSubmit ()
{
    global $currentUserId, $poll;

    displayHeader();
    displayLeftColumn();

    $option = cleanInput($_POST['option_id'], 'int');

    if (isset($_GET['poll_id']))
    {
        $id = cleanInput($_GET['poll_id'], 'int');
    }
    else
    {
        $id = cleanInput($_POST['poll_id'], 'int');
    }

    $poll->placeVote($currentUserId, $option, $id);
    $poll->displayResults($id);

    displayFooter();
}

/**
 * displayPollResults 
 * 
 * @return void
 */
function displayPollResults ()
{
    global $poll;

    displayHeader();
    displayLeftColumn();

    if (isset($_GET['poll_id']))
    {
        $id = cleanInput($_GET['poll_id'], 'int');
    }
    else
    {
        $id = cleanInput($_POST['poll_id'], 'int');
    }

    $poll->displayResults($id);

    displayFooter();
}

/**
 * displayPastPolls 
 * 
 * @return void
 */
function displayPastPolls ()
{
    global $poll;

    displayHeader();
    displayLeftColumn();

    $page = 1;
    if (isset($_GET['page']))
    {
        $page = cleanInput($_GET['page'], 'int');
    }

    $poll->displayPastPolls($page);

    displayFooter();
}

/**
 * displayWhatsNew 
 * 
 * @return void
 */
function displayWhatsNew ()
{
    global $currentUserId, $poll, $alert, $calendar;

    displayHeader();
    displayLeftColumn();

    // Remove an alert
    if (isset($_GET['alert']))
    {
        $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
                VALUES (
                    '".cleanInput($_GET['alert'])."', 
                    '$currentUserId'
                )";
        mysql_query($sql) or displaySQLError(
            'Remove Alert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
    }

    // Show Alerts
    $alertShown = $alert->displayNewUserHome($currentUserId);

    // Show any events happening today
        // Note: no need to fix dates for locale
        //       the db stores dates in server tz
    list($db_year, $db_month, $db_day) = explode('-', date('Y-m-d'));
    $calendar->displayTodaysEvents($db_month, $db_day, $db_year);

    // status updates
    displayStatusUpdateForm();

    // Show what's new based on user's settings
    echo '
                <h2>'.T_('What\'s New').'</h2>';
    $sql = "SELECT `frontpage` 
            FROM `fcms_user_settings` 
            WHERE `user` = '$currentUserId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Frontpage Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $r = mysql_fetch_array($result);
    mysql_free_result($result);

    // All by date
    if ($r['frontpage'] < 2)
    {
        displayWhatsNewAll($currentUserId);

    }
    // Last 5 by category
    else
    {
        $whatsNewData = getWhatsNewData($currentUserId, 30, true);
        $tzOffset     = getTimezone($currentUserId);

        $messageboard    = formatWhatsNewMessageBoard($whatsNewData, $tzOffset);
        $familynews      = formatWhatsNewFamilyNews($whatsNewData, $tzOffset);
        $addressbook     = formatWhatsNewAddressBook($whatsNewData, $tzOffset);
        $recipes         = formatWhatsNewRecipes($whatsNewData, $tzOffset);
        $prayers         = formatWhatsNewPrayers($whatsNewData, $tzOffset);
        $photogallery    = formatWhatsNewPhotoGallery($whatsNewData, $tzOffset);
        $videogallery    = formatWhatsNewVideoGallery($whatsNewData, $tzOffset);
        $comments        = formatWhatsNewComments($whatsNewData, $tzOffset);
        $statusupdates   = formatWhatsNewStatusUpdates($whatsNewData, $tzOffset);
        $calendar        = formatWhatsNewCalendar($whatsNewData, $tzOffset);
        $documents       = formatWhatsNewDocuments($whatsNewData, $tzOffset);
        $whereiseveryone = formatWhatsNewWhereIsEveryone($whatsNewData, $tzOffset);
        $misc            = formatWhatsNewMisc($whatsNewData, $tzOffset);

        // Set the order of the sections
        $sections = array(
            'photogallery',
            'misc',
            'videogallery',
            'statusupdates',
            'messageboard',
            'familynews',
            'comments',
            'whereiseveryone',
            'addressbook',
            'recipes',
            'calendar',
            'prayers',
            'documents'
        );

        // Remove unused sections
        foreach ($sections as $key => $section)
        {
            if (empty(${$section}))
            {
                unset($sections[$key]);
            }
        }

        // Display the left half
        echo '
                <div class="half">';

        for ($i=0; $i < count($sections); $i++)
        {
            if ($i % 2 == 0)
                echo ${$sections[$i]};

            $i++;
        }

        // Display the right half
        echo '
                </div>
                <div class="half">';

        for ($i=1; $i < count($sections); $i++)
        {
            if ($i % 2 !== 0)
                echo ${$sections[$i]};

            $i++;
        }

        echo '</div><div style="clear:both"></div>';
    }

    echo '
                <p class="alignright">
                    <a class="rss" href="rss.php?feed=all">'.T_('RSS Feed').'</a>
                </p>';

    displayFooter();
}

/**
 * displayStatusUpdateForm 
 * 
 * @param int $parent the id of the parent status update, or 0 if none
 * 
 * @return void
 */
function displayStatusUpdateForm ($parent = 0)
{
    global $currentUserId;

    // Facebook option is only good for first status update field, not reply
    if ($parent == 0)
    {
        $data        = getFacebookConfigData();
        $accessToken = getUserFacebookAccessToken($currentUserId);
        $user        = null;

        if (!empty($data['fb_app_id']) && !empty($data['fb_secret']))
        {
            $facebook = new Facebook(array(
              'appId'  => $data['fb_app_id'],
              'secret' => $data['fb_secret'],
            ));

            $facebook = $facebook->setAccessToken($accessToken);

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

        $fb = '';
        if ($user)
        {
            $fb  = '<input type="checkbox" id="update_fb" name="update_fb"/>';
            $fb .= '<label for="update_fb">'.T_('Update Facebook?').'</label>';
        }
    }

    $id    = 'status_update';
    $value = T_('Submit');
    $ph    = T_('Share');
    $title = T_('Share something with everyone.');
    $input = '';

    if (ctype_digit($parent) && $parent > 0)
    {
        $id    = 'status_reply';
        $fb    = '';
        $value = T_('Reply');
        $ph    = T_('Reply');
        $title = T_('Reply');
        $input = '<input type="hidden" id="parent" name="parent" value="'.cleanOutput($parent).'"/>';
    }

    echo '
        <div id="'.$id.'">
            <form method="post" action="home.php">
                <textarea id="status" name="status" placeholder="'.$ph.'" title="'.$title.'"></textarea>
                <small>'.$fb.'</small>'.$input.'
                <input type="submit" id="status_submit" name="status_submit" value="'.$value.'"/>
            </form>
        </div>';
}

/**
 * displayStatusUpdateSubmit 
 * 
 * @return void
 */
function displayStatusUpdateSubmit ()
{
    global $currentUserId;

    if (empty($_POST['status']))
    {
        header("Location: home.php");
        return;
    }

    $status = cleanInput($_POST['status']);
    $parent = isset($_POST['parent']) ? cleanInput($_POST['parent'], 'int') : 0;

    // Insert new status
    $sql = "INSERT INTO `fcms_status` (`user`, `status`, `parent`, `created`, `updated`)
            VALUES (
                '$currentUserId', 
                '$status', 
                '$parent',
                NOW(), 
                NOW()
            )";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySQLError('Status Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    // If replying, update the orig status updated date, so it bumps up to the top of the list
    if ($parent > 0)
    {
        $sql = "UPDATE `fcms_status`
                SET `updated` = NOW()
                WHERE `id` = '$parent'
                LIMIT 1;";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySQLError('Status Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }
    }

    // Post to facebook
    if (isset($_POST['update_fb']))
    {
        $data        = getFacebookConfigData();
        $accessToken = getUserFacebookAccessToken($currentUserId);

        // Send status to facebook
        if (!empty($data['fb_app_id']) && !empty($data['fb_secret']))
        {
            $facebook = new Facebook(array(
                'appId'  => $data['fb_app_id'],
                'secret' => $data['fb_secret'],
            ));

            $facebook->setAccessToken($accessToken);

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
                    $user = null;
                }
            }
        }
    }

    header("Location: home.php");
}

/**
 * formatWhatsNewMessageBoard 
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 * 
 * @return string
 */
function formatWhatsNewMessageBoard ($whatsNewData, $tzOffset)
{
    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Message Board').'</h3>
            <ul>';

    if (!isset($whatsNewData['BOARD']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    // $whatsNewData holds all replies, we only want to show the threads that have
    // been updated, so we keep track of threads as we display them
    $displayedThreads = array();

    foreach ($whatsNewData['BOARD'] as $row)
    {
        // Skip, if we displayed this thread already
        if (isset($displayedThreads[$row['title']]))
        {
            continue;
        }
        $displayedThreads[$row['title']] = 1;

        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $displayname  = getUserDisplayName($row['userid']);
        $subject      = $row['title'];
        $subject_full = cleanOutput($subject);

        // Remove announcment
        $pos = strpos($subject, '#ANOUNCE#');
        if ($pos !== false)
        {
            $subject = substr($subject, 9, strlen($subject)-9);
        }

        // Chop Long subjects
        if (strlen($subject) > 23)
        {
            $subject = substr($subject, 0, 20) . "...";
        }

        $date = fixDate('YmdHis', $tzOffset, $row['date']);

        if ($date >= $today_start && $date <= $today_end)
        {
            $full_date = T_('Today');
            $d = ' class="today"';
        }
        else
        {
            $full_date = fixDate(T_('M. j, Y g:i a'), $tzOffset, $row['date']);
            $d = '';
        }

        $return .= '
                <li>
                    <div'.$d.'>'.$full_date.'</div>
                    <a href="messageboard.php?thread='.cleanInput($row['id2'], 'int').'" title="'.$subject_full.'">'.$subject.'</a> ';

        if (getNumberOfPosts($row['id2']) > 15)
        {
            $num_posts  = getNumberOfPosts($row['id2']);
            $times2loop = ceil($num_posts/15);

            $return .= '('.T_('Page').' ';
            for ($i=1; $i<=$times2loop; $i++)
            {
                $return .= '<a href="messageboard.php?thread='.cleanInput($row['id2'], 'int').'&amp;page='.$i.'" title="'.T_('Page').' '.$i.'">'.$i.'</a> ';
            }
            $return .= ')';
        }

        $return .= '
                     - <a class="u" href="profile.php?member='.cleanInput($row['userid'], 'int').'">'.$displayname.'</a>
                </li>';
    }

    return $return.'
            </ul>';

}

/**
 * formatWhatsNewFamilyNews 
 * 
 * Returns empty string if Family News section is turned off.
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 * 
 * @return string
 */
function formatWhatsNewFamilyNews ($whatsNewData, $tzOffset)
{
    if (!usingFamilyNews())
    {
        return '';
    }

    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Family News').'</h3>
            <ul>';

    if (!isset($whatsNewData['NEWS']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    foreach ($whatsNewData['NEWS'] as $row)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $displayname = getUserDisplayName($row['userid']);
        $date        = fixDate('YmdHis', $tzOffset, $row['date']);
        $title       = !empty($row['title']) ? cleanOutput($row['title']) : T_('untitled');

        if ($date >= $today_start && $date <= $today_end)
        {
            $full_date = T_('Today');
            $d = ' class="today"';
        }
        else
        {
            $full_date = fixDate(T_('M. j, Y g:i a'), $tzOffset, $row['date']);
            $d = '';
        }

        $return .= '
                <li>
                    <div'.$d.'>'.$full_date.'</div>
                    <a href="familynews.php?getnews='.cleanInput($row['userid'], 'int').'&amp;newsid='.cleanInput($row['id'], 'int').'">'.$title.'</a> - 
                    <a class="u" href="profile.php?member='.cleanInput($row['userid'], 'int').'">'.$displayname.'</a>
                </li>';
    }

    $return .= '
            </ul>';

    return $return;
}

/**
 * formatWhatsNewAddressBook 
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 * 
 * @return string
 */
function formatWhatsNewAddressBook ($whatsNewData, $tzOffset)
{
    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Address Book').'</h3>
            <ul>';

    if (!isset($whatsNewData['ADDRESSEDIT']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    foreach ($whatsNewData['ADDRESSEDIT'] as $row)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $displayname = getUserDisplayName($row['userid'], 2, false);
        $date        = fixDate('YmdHis', $tzOffset, $row['date']);

        if ($date >= $today_start && $date <= $today_end)
        {
            $full_date = T_('Today');
            $d = ' class="today"';
        }
        else
        {
            $full_date = fixDate(T_('M. j, Y, g:i a'), $tzOffset, $row['date']);
            $d = '';
        }

        $return .= '
                <li>
                    <div'.$d.'>'.$full_date.'</div>
                    <a href="addressbook.php?address='.cleanInput($row['id'], 'int').'">'.$displayname.'</a>
                </li>';
    }

    $return .= '
            </ul>';

    return $return;
}

/**
 * formatWhatsNewRecipes 
 * 
 * Returns empty string if Recipes section is turned off.
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 * 
 * @return string
 */
function formatWhatsNewRecipes ($whatsNewData, $tzOffset)
{
    if (!usingRecipes())
    {
        return '';
    }

    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Recipes').'</h3>
            <ul>';

    if (!isset($whatsNewData['RECIPES']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    foreach ($whatsNewData['RECIPES'] as $r)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $name = $r['title'];
        $displayname = getUserDisplayName($r['userid']);

        $url = 'recipes.php?category='.cleanInput($r['id2'], 'int').'&amp;id='.cleanInput($r['id'], 'int');

        $date = fixDate('YmdHis', $tzOffset, $r['date']);

        if ($date >= $today_start && $date <= $today_end)
        {
            $full_date = T_('Today');
            $d = ' class="today"';
        }
        else
        {
            $full_date = fixDate(T_('M. j, Y, g:i a'), $tzOffset, $r['date']);
            $d = '';
        }

        $return .= '
                <li>
                    <div'.$d.'>'.$full_date.'</div>
                    <a href="'.$url.'">'.cleanOutput($name).'</a> - 
                    <a class="u" href="profile.php?member='.cleanInput($r['userid'], 'int').'">'.$displayname.'</a>
                </li>';
    }

    $return .= '
            </ul>';

    return $return;
}

/**
 * formatWhatsNewPrayers 
 * 
 * Returns empty string if Prayers section is turned off.
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 * 
 * @return string
 */
function formatWhatsNewPrayers ($whatsNewData, $tzOffset)
{
    if (!usingPrayers())
    {
        return '';
    }

    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Prayer Concerns').'</h3>
            <ul>';

    if (!isset($whatsNewData['PRAYERS']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    foreach ($whatsNewData['PRAYERS'] as $r)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $displayname = getUserDisplayName($r['userid']);
        $for = $r['title'];
        $date = fixDate('YmdHis', $tzOffset, $r['date']);
        if ($date >= $today_start && $date <= $today_end) {
            $full_date = T_('Today');
            $d = ' class="today"';
        } else {
            $full_date = fixDate(T_('M. j, Y, g:i a'), $tzOffset, $r['date']);
            $d = '';
        }

        $return .= '
                <li>
                    <div'.$d.'>'.$full_date.'</div>
                    <a href="prayers.php">'.$for.'</a> - 
                    <a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>
                </li>';
    }

    $return .= '
            </ul>';

    return $return;
}

/**
 * formatWhatsNewPhotoGallery 
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 * 
 * @return string
 */
function formatWhatsNewPhotoGallery ($whatsNewData, $tzOffset)
{
    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Photo Gallery').'</h3>
            <ul>';

    if (!isset($whatsNewData['GALLERY']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    foreach ($whatsNewData['GALLERY'] as $row)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $displayname   = getUserDisplayName($row['userid']);
        $category      = cleanOutput($row['title']);
        $full_category = cleanOutput($category);
        $date          = fixDate('YmdHis', $tzOffset, $row['date']);
        if (strlen($category) > 20)
        {
            $category = substr($category, 0, 17) . "...";
        }

        // Today
        if ($date >= $today_start && $date <= $today_end)
        {
            $full_date = T_('Today');
            $d = ' class="today"';
        }
        else
        {
            $full_date = fixDate(T_('M. j, Y g:i a'), $tzOffset, $row['date']);
            $d = '';
        }

        $return .= '
                    <li>
                        <div'.$d.'>'.$full_date.'</div>
                        <p>
                            <a href="gallery/index.php?uid='.$row['userid'].'&amp;cid='.$row['id'].'" title="'.$full_category.'">'.$category.'</a> - 
                            <a class="u" href="profile.php?member='.$row['userid'].'">'.$displayname.'</a>
                        </p>';

        $limit = 4;
        if ($row['id2'] < $limit)
        {
            $limit = $row['id2'];
        }
        $sql = "SELECT `id`, `user`, `category`, `filename`, `caption`
                FROM `fcms_gallery_photos` 
                WHERE `category` = '".cleanInput($row['id'], 'int')."' 
                AND DAYOFYEAR(`date`) = '".cleanInput($row['id3'])."' 
                ORDER BY `date` 
                DESC LIMIT $limit";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaysqlerror('Photos Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        while ($p = mysql_fetch_assoc($result))
        {
            $return .= '
                            <a href="gallery/index.php?uid='.$p['user'].'&amp;cid='.$p['category'].'&amp;pid='.$p['id'].'">
                                <img src="uploads/photos/member'.$p['user'].'/tb_'.basename($p['filename']).'" 
                                    style="height:50px; width:50px;" 
                                    alt="'.cleanOutput($p['caption']).'"/>
                            </a> &nbsp;';
        }

        $return .= '
                    </li>';
    }

    $return .= '
            </ul>';

    return $return;
}

/**
 * formatWhatsNewVideoGallery 
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 * 
 * @return string
 */
function formatWhatsNewVideoGallery ($whatsNewData, $tzOffset)
{
    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Video Gallery').'</h3>
            <ul>';

    if (!isset($whatsNewData['VIDEO']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    foreach ($whatsNewData['VIDEO'] as $row)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $displayname   = getUserDisplayName($row['userid']);
        $category      = cleanOutput($row['title']);
        $full_category = cleanOutput($category);
        $date          = fixDate('YmdHis', $tzOffset, $row['date']);
        if (strlen($category) > 20)
        {
            $category = substr($category, 0, 17) . "...";
        }

        // Today
        if ($date >= $today_start && $date <= $today_end)
        {
            $full_date = T_('Today');
            $d = ' class="today"';
        }
        else
        {
            $full_date = fixDate(T_('M. j, Y g:i a'), $tzOffset, $row['date']);
            $d = '';
        }

        $return .= '
                    <li>
                        <div'.$d.'>'.$full_date.'</div>
                        <p>
                            <a href="video.php?u='.$row['userid'].'&amp;id='.$row['id'].'" title="'.$full_category.'">'.$category.'</a> - 
                            <a class="u" href="profile.php?member='.$row['userid'].'">'.$displayname.'</a><br/>
                            <a href="video.php?u='.$row['userid'].'&amp;id='.$row['id'].'"><img src="http://i.ytimg.com/vi/'.$row['id2'].'/default.jpg"/></a>
                        </p>
                    </li>';
    }

    $return .= '
            </ul>';

    return $return;
}

/**
 * formatWhatsNewComments 
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 * 
 * @return string
 */
function formatWhatsNewComments ($whatsNewData, $tzOffset)
{
    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Comments').'</h3>
            <ul>';

    if (!isset($whatsNewData['GALCOM']) && !isset($whatsNewData['NEWSCOM']) && !isset($whatsNewData['RECIPESCOM']) && !isset($whatsNewData['VIDEOCOM']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    # Get each comment type from the $whatsNewData array, store it in a new array
    $using = array('GALCOM', 'VIDEOCOM');
    if (usingFamilyNews())
    {
        array_push($using, 'NEWSCOM');
    }
    if (usingRecipes())
    {
        array_push($using, 'RECIPECOM');
    }

    $commentsData = array();

    foreach ($using as $type)
    {
        if (!isset($whatsNewData[$type]))
        {
            continue;
        }
        foreach ($whatsNewData[$type] as $data)
        {
            $commentsData[] = $data;
        }
    }

    // Need to resort the commentsData
    $commentsData = subval_sort($commentsData, 'date');
    $commentsData = array_reverse($commentsData);

    foreach ($commentsData as $row)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $date = fixDate('YmdHis', $tzOffset, $row['date']);

        $comment = $row['title'];
        if (strlen($comment) > 30)
        {
            $comment = substr($comment, 0, 27)."...";
        }

        if ($date >= $today_start && $date <= $today_end)
        {
            $full_date = T_('Today');
            $d = ' class="today"';
        }
        else
        {
            $full_date = fixDate(T_('M. j, Y, g:i a'), $tzOffset, $row['date']);
            $d = '';
        }

        if ($row['type'] == 'NEWSCOM')
        {
            $url = 'familynews.php?getnews='.$row['userid'].'&amp;newsid='.$row['id'];
        }
        elseif ($row['type'] == 'RECIPECOM')
        {
            $url = 'recipes.php?category='.$row['id2'].'&amp;id='.$row['id'];
        }
        elseif ($row['type'] == 'VIDEOCOM')
        {
            $url = 'video.php?u='.$row['userid'].'&amp;id='.$row['id'].'#comments';
        }
        else
        {
            $url = 'gallery/index.php?uid=0&amp;cid=comments&amp;pid='.$row['id'];
        }

        $user     = cleanOutput($row['userid']);
        $userName = getUserDisplayName($row['userid']);

        $return .= '
                        <li>
                            <div'.$d.'>'.$full_date.'</div>
                            <a href="'.$url.'">'.$comment.'</a> - 
                            <a href="profile.php?member='.$user.'" class="u">'.$userName.'</a>
                        </li>';
    }

    $return .= '
            </ul>';

    return $return;
}

/**
 * formatWhatsNewStatusUpdates 
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 *
 * @return string
 */
function formatWhatsNewStatusUpdates ($whatsNewData, $tzOffset)
{
    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Status Updates').'</h3>
            <ul>';

    if (!isset($whatsNewData['STATUS']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    foreach ($whatsNewData['STATUS'] as $r)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $displayname = getUserDisplayName($r['userid']);
        $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';

        $title = cleanOutput($r['title']);
        $title = nl2br_nospaces($title);

        $return .= '
                <li style="line-height: 120%;">
                    <div>
                        <p>
                            '.$displayname.': 
                            '.$title.'<br/>
                            <small><i>'.getHumanTimeSince(strtotime($r['id3'])).'</i></small>
                        </p>
                    </div>';

        // Get any replies to this status update
        $sql = "SELECT `id`, `user`, `status`, `parent`, `updated`, `created` 
                FROM `fcms_status` 
                WHERE `parent` = '".cleanInput($r['id'], 'int')."' 
                ORDER BY `id`";
        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Status Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        $return .= '
                    <div class="status_replies">';

        if (mysql_num_rows($result) > 0)
        {
            while ($s = mysql_fetch_array($result))
            {
                $name = getUserDisplayName($s['user']);
                $name = '<a class="u" href="profile.php?member='.$s['user'].'">'.$name.'</a>';

                $status = cleanOutput($s['status']);
                $status = nl2br_nospaces($status);

                $return .= '<div><p>'.$name.': '.$status.'<br/><small><i>'.getHumanTimeSince(strtotime($s['created'])).'</i></small></p></div>';
            }
        }

        //displayStatusUpdateForm($r['id']);
        $return .= '
                    </div>
                </li>';
    }

    $return .= '
            </ul>';

    return $return;
}

/**
 * formatWhatsNewCalendar 
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 *
 * @return string
 */
function formatWhatsNewCalendar ($whatsNewData, $tzOffset)
{
    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Calendar').'</h3>
            <ul>';

    if (!isset($whatsNewData['CALENDAR']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    foreach ($whatsNewData['CALENDAR'] as $r)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $displayname = getUserDisplayName($r['userid']);
        $title       = $r['title'];
        $date        = fixDate('YmdHis', $tzOffset, $r['date']);

        if ($date >= $today_start && $date <= $today_end)
        {
            $full_date = T_('Today');
            $d         = ' class="today"';
        }
        else
        {
            $full_date = fixDate(T_('M. j, Y, g:i a'), $tzOffset, $r['date']);
            $d         = '';
        }

        list($year, $month, $day) = explode('-', date('Y-m-d', strtotime($r['date'])));

        $return .= '
                <li>
                    <div'.$d.'>'.$full_date.'</div>
                    <a href="calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'">'.$title.' ('.date('n/j/Y', strtotime($r['id2'])).')</a> - 
                    <a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>
                </li>';
    }

    $return .= '
            </ul>';

    return $return;
}

/**
 * formatWhatsNewDocuments 
 * 
 * Returns empty string if Documents section is turned off.
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 *
 * @return string
 */
function formatWhatsNewDocuments ($whatsNewData, $tzOffset)
{
    if (!usingDocuments())
    {
        return '';
    }

    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Documents').'</h3>
            <ul>';

    if (!isset($whatsNewData['DOCS']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    foreach ($whatsNewData['DOCS'] as $r)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $displayname = getUserDisplayName($r['userid']);
        $document    = cleanOutput($r['title']);
        $date        = fixDate('YmdHis', $tzOffset, $r['date']);

        if ($date >= $today_start && $date <= $today_end)
        {
            $date = T_('Today');
            $d = ' class="today"';
        }
        else
        {
            $date = fixDate(T_('M. j, Y, g:i a'), $tzOffset, $r['date']);
            $d = '';
        }

        $return .= '
                <li>
                    <div'.$d.'>'.$date.'</div>
                    <a href="documents.php">'.$document.'</a> - 
                    <a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>
                </li>';
    }

    $return .= '
            </ul>';

    return $return;
}

/**
 * formatWhatsNewWhereIsEveryone
 * 
 * Returns empty string if Where Is Everyone section is turned off.
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 *
 * @return string
 */
function formatWhatsNewWhereIsEveryone ($whatsNewData, $tzOffset)
{
    if (!usingWhereIsEveryone())
    {
        return '';
    }

    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Where Is Everyone').'</h3>
            <ul>';

    if (!isset($whatsNewData['WHEREISEVERYONE']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    foreach ($whatsNewData['WHEREISEVERYONE'] as $r)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $displayname = getUserDisplayName($r['userid']);
        $title       = cleanOutput($r['title']);
        $date        = fixDate('YmdHis', $tzOffset, $r['date']);

        if ($date >= $today_start && $date <= $today_end)
        {
            $date = T_('Today');
            $d = ' class="today"';
        }
        else
        {
            $date = fixDate(T_('M. j, Y, g:i a'), $tzOffset, $r['date']);
            $d = '';
        }

        $displayname = getUserDisplayName($r['userid']);

        $return .= '
                <li>
                    <div'.$d.'>'.$date.'</div>
                    <a href="whereiseveryone.php">'.sprintf(T_('%s visited %s.'), $displayname, $title).'</a> - 
                    <a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>
                </li>';
    }

    $return .= '
            </ul>';

    return $return;
}

/**
 * formatWhatsNewMisc 
 * 
 * @param array  $whatsNewData 
 * @param string $tzOffset 
 * 
 * @return string
 */
function formatWhatsNewMisc ($whatsNewData, $tzOffset)
{
    $today_start = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

    $return = '
            <h3>'.T_('Misc.').'</h3>
            <ul>';

    if (!isset($whatsNewData['JOINED']) && !isset($whatsNewData['ADDRESSADD']) && !isset($whatsNewData['POLL']) && !isset($whatsNewData['AVATAR']))
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    # Get each comment type from the $whatsNewData array, store it in a new array
    $miscData = array();

    foreach (array('JOINED', 'ADDRESSADD', 'POLL', 'AVATAR') as $type)
    {
        if (!isset($whatsNewData[$type]))
        {
            continue;
        }
        foreach ($whatsNewData[$type] as $data)
        {
            $miscData[] = $data;
        }
    }

    // Need to resort the miscData
    $miscData = subval_sort($miscData, 'date');
    $miscData = array_reverse($miscData);

    foreach ($miscData as $r)
    {
        // Quit, if we displayed 5 already
        if ($count > 5)
        {
            break;
        }
        $count++;

        $date = fixDate('YmdHis', $tzOffset, $r['date']);

        if ($date >= $today_start && $date <= $today_end)
        {
            $full_date = T_('Today');
            $d = ' class="today"';
        }
        else
        {
            $full_date = fixDate(T_('M. j, Y, g:i a'), $tzOffset, $r['date']);
            $d = '';
        }

        if ($r['type'] == 'POLL')
        {
            $poll  = '<a href="home.php?poll_id='.$r['id'].'">'.cleanOutput($r['title']).'</a>';
            $title = sprintf(T_('A new Poll (%s) has been added.'), $poll);
        }
        elseif ($r['type'] == 'ADDRESSADD')
        {
            $displayname = '<a class="u" href="profile.php?member='.$r['id2'].'">'.getUserDisplayName($r['id2']).'</a>';
            $for         = '<a href="addressbook.php?address='.$r['id'].'">'.getUserDisplayName($r['userid'], 2, false).'</a>';
            $title       = sprintf(T_('%s has added address information for %s.'), $displayname, $for);
        }
        elseif ($r['type'] == 'AVATAR')
        {
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.getUserDisplayName($r['userid']).'</a>';
            $title       = sprintf(T_('%s changed his profile picture.'), $displayname);
        }
        // JOINED
        else
        {
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.getUserDisplayName($r['userid']).'</a>';
            $title       = sprintf(T_('%s has joined the website.'), $displayname);
        }

        $return .= '
                        <li>
                            <div'.$d.'>'.$full_date.'</div>
                            '.$title.'
                        </li>';
    }

    $return .= '
            </ul>';

    return $return;
}
