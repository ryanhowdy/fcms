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
    'database', 
    'alerts',
    'socialmedia',
    'facebook'
);

init();

// Globals
$calendar = new Calendar($fcmsUser->id);
$poll     = new Poll($fcmsUser, $fcmsError);
$alertObj = new Alerts($fcmsUser->id);

$TMPL = array(
    'currentUserId' => $fcmsUser->id,
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_pgettext('The beginning or starting place.', 'Home'),
    'path'          => URL_PREFIX,
    'displayname'   => $fcmsUser->displayName,
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
    global $fcmsUser;

    // Update activity
    mysql_query("UPDATE `fcms_users` SET `activity`=NOW() WHERE `id` = '$fcmsUser->id'");

    if (isset($_POST['status_submit']))
    {
        displayStatusUpdateSubmit();
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
    global $fcmsUser, $TMPL;

    $TMPL['javascript'] = '
<script type="text/javascript">
Event.observe(window, "load", function()
{
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');

    document.onkeydown = keyHandler;
});
var position = 0;
function keyHandler(e)
{
    if (!e) { e = window.event; }

    var jDown = 74;
    var kUp   = 75;

    switch (e.keyCode)
    {
        case jDown:
            position++;
            document.location.href = "#"+position;
        break;

        case kUp:
            if (position > 1) { position--; }
            document.location.href = "#"+position;
        break;
    }
}
</script>';

    include_once getTheme($fcmsUser->id).'header.php';

    echo '
        <div id="home" class="centercontent">';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    global $fcmsUser, $TMPL;

    echo '
            </div><!--/maincolumn-->
        </div><!--/centercontent -->';

    include_once getTheme($fcmsUser->id).'footer.php';
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

    displayPoll();

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
 * displayWhatsNew 
 * 
 * @return void
 */
function displayWhatsNew ()
{
    global $fcmsUser, $poll, $alertObj, $calendar;

    displayHeader();
    displayLeftColumn();

    // Remove an alert
    if (isset($_GET['alert']))
    {
        $alert = escape_string($_GET['alert']);

        $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
                VALUES (
                    '$alert', 
                    '$fcmsUser->id'
                )";
        if (!mysql_query($sql))
        {
            displaySqlError($sql, mysql_error());
            // ok to continue
        }
    }

    // Show Alerts
    $alertShown = $alertObj->displayNewUserHome($fcmsUser->id);

    list($db_year, $db_month, $db_day) = explode('-', date('Y-m-d'));

    // Show any events happening today
    $calendar->displayTodaysEvents($db_month, $db_day, $db_year);

    // status updates
    displayStatusUpdateForm();

    // Show what's new based on user's settings
    echo '
                <h2>'.T_('What\'s New').'</h2>';

    $sql = "SELECT `frontpage` 
            FROM `fcms_user_settings` 
            WHERE `user` = '$fcmsUser->id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $r = mysql_fetch_array($result);
    mysql_free_result($result);

    // All by date
    if ($r['frontpage'] < 2)
    {
        displayWhatsNewAll($fcmsUser->id);

    }
    // Last 5 by category
    else
    {
        $whatsNewData = getWhatsNewData($fcmsUser->id, 30, true);
        $tzOffset     = getTimezone($fcmsUser->id);

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
    global $fcmsUser;

    // Facebook option is only good for first status update field, not reply
    if ($parent == 0)
    {
        $data        = getFacebookConfigData();
        $accessToken = getUserFacebookAccessToken($fcmsUser->id);
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
        $input = '<input type="hidden" id="parent" name="parent" value="'.$parent.'"/>';
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
    global $fcmsUser;

    $status = escape_string($_POST['status']);
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
    $sql = "INSERT INTO `fcms_status` (`user`, `status`, `parent`, `created`, `updated`)
            VALUES (
                '$fcmsUser->id', 
                '$status', 
                '$parent',
                NOW(), 
                NOW()
            )";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
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
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    // Post to facebook
    if (isset($_POST['update_fb']))
    {
        $data        = getFacebookConfigData();
        $accessToken = getUserFacebookAccessToken($fcmsUser->id);

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
                }
            }
        }
    }

    // Email members
    $sql = "SELECT u.`email`, s.`user` 
            FROM `fcms_user_settings` AS s, `fcms_users` AS u 
            WHERE `email_updates` = '1'
            AND u.`id` = s.`user`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayfooter();
        return;
    }

    if (mysql_num_rows($result) > 0)
    {
        $url     = getDomainAndDir();
        $headers = getEmailHeaders();
        $name    = getUserDisplayName($fcmsUser->id);

        while ($r = mysql_fetch_array($result))
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
        $subject_full = cleanOutput($subject, 'html');

        // Remove announcment
        $pos = strpos($subject, '#ANOUNCE#');
        if ($pos !== false)
        {
            $subject = substr($subject, 9, strlen($subject)-9);
        }

        $subject = shortenString($subject, 30, '...');
        $subject = cleanOutput($subject, 'html');

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
                    <a href="messageboard.php?thread='.(int)$row['id2'].'" title="'.$subject_full.'">'.$subject.'</a> ';

        if (getNumberOfPosts($row['id2']) > 15)
        {
            $num_posts  = getNumberOfPosts($row['id2']);
            $times2loop = ceil($num_posts/15);

            $return .= '('.T_('Page').' ';
            for ($i=1; $i<=$times2loop; $i++)
            {
                $return .= '<a href="messageboard.php?thread='.(int)$row['id2'].'&amp;page='.$i.'" title="'.T_('Page').' '.$i.'">'.$i.'</a> ';
            }
            $return .= ')';
        }

        $return .= '
                     - <a class="u" href="profile.php?member='.(int)$row['userid'].'">'.$displayname.'</a>
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

        $title = T_('untitled');

        if (!empty($row['title']))
        {
            $title = shortenString($row['title'], 30, '...');
            $title = cleanOutput($title, 'html');
        }

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
                    <a href="familynews.php?getnews='.(int)$row['userid'].'&amp;newsid='.(int)$row['id'].'">'.$title.'</a> - 
                    <a class="u" href="profile.php?member='.(int)$row['userid'].'">'.$displayname.'</a>
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
                    <a href="addressbook.php?address='.(int)$row['id'].'">'.$displayname.'</a>
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
        $name = shortenString($name, 30, '...');
        $name = cleanOutput($name, 'html');

        $displayname = getUserDisplayName($r['userid']);

        $url = 'recipes.php?category='.(int)$r['id2'].'&amp;id='.(int)$r['id'];

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
                    <a href="'.$url.'">'.$name.'</a> - 
                    <a class="u" href="profile.php?member='.(int)$r['userid'].'">'.$displayname.'</a>
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
        $for         = shortenString($r['title'], 30, '...');
        $for         = cleanOutput($for, 'html');
        $date        = fixDate('YmdHis', $tzOffset, $r['date']);

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
    global $fcmsUser;

    load('gallery');

    $galleryObj = new PhotoGallery($fcmsUser->id);

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
        $category      = shortenString($row['title'], 30, '...');
        $category      = cleanOutput($category, 'html');
        $full_category = cleanOutput($row['title'], 'html');
        $date          = fixDate('YmdHis', $tzOffset, $row['date']);

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
        $sql = "SELECT p.`id`, p.`user`, p.`category`, p.`filename`, p.`caption`,
                    p.`external_id`, e.`thumbnail`, e.`medium`, e.`full`
                FROM `fcms_gallery_photos` AS p
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                WHERE p.`category` = '".(int)$row['id']."' 
                AND DAYOFYEAR(p.`date`) = '".(int)$row['id3']."' 
                ORDER BY p.`date` 
                DESC LIMIT $limit";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError('Photos Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        while ($p = mysql_fetch_assoc($result))
        {
            $photoSrc = $galleryObj->getPhotoSource($p);

            $return .= '
                            <a href="gallery/index.php?uid='.$p['user'].'&amp;cid='.$p['category'].'&amp;pid='.$p['id'].'">
                                <img src="'.$photoSrc.'" 
                                    style="height:50px; width:50px;" 
                                    alt="'.cleanOutput($p['caption'], 'html').'" 
                                    title="'.cleanOutput($p['caption'], 'html').'"/>
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
        $category      = shortenString($row['title'], 30, '...');
        $category      = cleanOutput($category, 'html');
        $full_category = cleanOutput($row['title'], 'html');
        $date          = fixDate('YmdHis', $tzOffset, $row['date']);

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

    if (   !isset($whatsNewData['GALCOM']) 
        && !isset($whatsNewData['NEWSCOM']) 
        && !isset($whatsNewData['RECIPESCOM']) 
        && !isset($whatsNewData['VIDEOCOM']) 
        && !isset($whatsNewData['POLLCOM'])
    )
    {
        return $return.'<i>'.T_('Nothing new').'</i></ul>';
    }

    $count = 0;

    # Get each comment type from the $whatsNewData array, store it in a new array
    $using = array('GALCOM', 'GALCATCOM', 'VIDEOCOM', 'POLLCOM');
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

        $date     = fixDate('YmdHis', $tzOffset, $row['date']);
        $comment  = shortenString($row['title'], 30, '...');
        $comment  = cleanOutput($comment, 'html');
        $title    = shortenString($row['title'], 100, '...');
        $title    = cleanOutput($title, 'html');
        $user     = cleanOutput($row['userid']);
        $userName = getUserDisplayName($row['userid']);

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
        elseif ($row['type'] == 'GALCATCOM')
        {
            $url = 'gallery/index.php?uid='.$row['id2'].'&amp;cid='.$row['id3'];
        }
        elseif ($row['type'] == 'GALCOM')
        {
            $url = 'gallery/index.php?uid=0&amp;cid=comments&amp;pid='.$row['id'];
        }
        elseif ($row['type'] == 'POLLCOM')
        {
            $comment = '['.T_('Poll').'] '.$comment;
            $url     = 'polls.php?id='.$row['id'].'"#comments';
        }

        $return .= '
                        <li>
                            <div'.$d.'>'.$full_date.'</div>
                            <a href="'.$url.'" title="'.$title.'">'.$comment.'</a> - 
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

        $title = nl2br_nospaces($r['title']);
        $title = cleanOutput($title, 'html');

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
                WHERE `parent` = '".(int)$r['id']."' 
                ORDER BY `id`";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
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

                $status = nl2br_nospaces($s['status']);
                $status = cleanOutput($status, 'html');

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
        $title       = shortenString($r['title'], 30, '...');
        $title       = cleanOutput($title, 'html');
        $titleFull   = cleanOutput($r['title'], 'html');
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
                    <a href="calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'" 
                        title="'.$titleFull.'">'.$title.'</a> - 
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
        $document    = shortenString($r['title'], 30, '...');
        $document    = cleanOutput($document, 'html');
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
        $title       = shortenString($r['title'], 30, '...');
        $title       = cleanOutput($title, 'html');
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
            $poll  = '<a href="poll.php?id='.$r['id'].'">'.cleanOutput($r['title'], 'html').'</a>';
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

            $title = sprintf(T_('%s changed his profile picture.'), $displayname);
            if ($r['id3'] == 'F')
            {
                $title = sprintf(T_('%s changed her profile picture.'), $displayname);
            }
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

/**
 * displayPoll 
 * 
 * @return void
 */
function displayPoll ()
{
    global $fcmsUser, $fcmsError, $poll;

    $pollData = $poll->getLatestPollData();
    if ($pollData === false)
    {
        $fcmsError->displayErrors();
        displayFooter();
        return;
    }

    $pollId = key($pollData);

    $pollOptions = '';
    $input       = '';
    $results     = '';

    // Show results - user already voted
    if (isset($pollData['users_who_voted'][$fcmsUser->id]))
    {
        $submitValue = T_('Already Voted');
        $class       = 'disabled';
        $disabled    = 'disabled="disabled"';

        $pollOptions = $poll->formatPollResults($pollData);
        if ($pollOptions === false)
        {
            $fcmsError->displayErrors();
            displayFooter();
            return;
        }
    }
    // Show options
    else
    {
        foreach ($pollData[$pollId]['options'] as $optionId => $optionData)
        {
            $pollOptions .= '
                    <p>
                        <label class="radio_label">
                            <input type="radio" name="option" value="'.$optionId.'"/>
                            '.cleanOutput($optionData['option'], 'html').'
                        </label>
                    </p>';
        }

        $input   = '<input type="submit" id="vote" name="vote" value="'.T_('Vote').'"/>';
        $results = '<a href="polls.php?id='.$pollId.'&amp;results=1">'.T_('Results').'</a> | ';
    }

    echo '
            <h2 class="pollmenu">'.T_('Polls').'</h2>
            <form class="poll-small" method="post" action="polls.php">
                <h3>'.cleanOutput($pollData[$pollId]['question'], 'html').'</h3>
                '.$pollOptions.'
                <input type="hidden" id="id" name="id" value="'.$pollId.'"/>
                <p>'.$input.'</p>
                '.$results.'
                <a href="polls.php?action=pastpolls">'.T_('Past Polls').'</a>
            </form>';
}
