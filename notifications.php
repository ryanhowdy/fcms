<?php
/**
 * Notifications
 *  
 * PHP versions 4 and 5
 *  
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '');

require 'fcms.php';

load('profile', 'image', 'address', 'phone');

init();

// Globals
$currentUserId = (int)$_SESSION['login_id'];

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Notifications'),
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
    if (isset($_GET['markread']))
    {
        if ($_GET['markread'] == 'all')
        {
            displayMarkAllReadSubmit();
        }
        else
        {
            displayMarkReadSubmit();
        }
    }
    elseif (isset($_GET['view']))
    {
        displayAllNotifications();
    }
    else
    {
        displayNotifications();
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
});
//]]>
</script>';

    require_once getTheme($currentUserId).'header.php';

    echo '
        <div id="notifications" class="centercontent">';
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
        </div><!-- /profile -->';

    include_once getTheme($currentUserId).'footer.php';
}

/**
 * displayNotifications 
 * 
 * @return void
 */
function displayNotifications ()
{
    global $currentUserId;

    displayHeader();

    if (isset($_SESSION['success']))
    {
        displayOKMessage();

        unset($_SESSION['success']);
    }

    $sql = "SELECT `id`, `user`, `created_id`, `notification`, `data`, `created`, `updated`
            FROM `fcms_notification`
            WHERE `user` = '$currentUserId'
            AND `read` = 0
            AND `created_id` != '$currentUserId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        if (isset($_SESSION['notifications']))
        {
            unset($_SESSION['notifications']);
        }

        echo '
            <p class="info-alert">'.T_('You do not have any notifications.').'</p>
            <p style="text-align:center">
                <small><a class="u" href="notifications.php?view=all">'.T_('View past notifications').'</a></small>
            </p>';

        return;
    }

    echo '
        <div id="actions_menu" class="clearfix">
            <ul>
                <li><a href="notifications.php?markread=all">'.T_('Mark All Read').'</a></li>
            </ul>
        </div>
        <div id="notifications-list">';

    while ($r = mysql_fetch_assoc($result))
    {
        $date   = getHumanTimeSince(strtotime($r['created']));
        $date   = ' <span class="date">'.$date.'</span>';
        $info   = '';
        $action = '<a class="read" href="?markread='.$r['id'].'">'.T_('Mark Read').'</a>';

        if ($r['notification'] == 'tagged_photo')
        {
            $displayName = getUserDisplayName($r['created_id']);

            list($uid, $cid, $pid, $filename) = explode(':', $r['data']);

            $info  = sprintf(T_('%s has added a photo of you.'), $displayName).$date;
            $info .= '<br/><a href="gallery/index.php?uid='.$uid.'&amp;cid='.$cid.'&amp;pid='.$pid.'">';
            $info .= '<img src="uploads/photos/member'.$uid.'/tb_'.basename($filename).'"/></a>';
        }

        echo '
                <p>
                    '.$action.'
                    '.$info.'
                </p>';
    }

    echo '
        </div>';

    displayFooter();
}

/**
 * displayMarkReadSubmit 
 * 
 * @return void
 */
function displayMarkReadSubmit ()
{
    $id = (int)$_GET['markread'];

    $sql = "UPDATE `fcms_notification`
            SET `read` = 1
            WHERE `id` = '$id'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    // Need to recalculate notification count
    if (isset($_SESSION['notifications']))
    {
        unset($_SESSION['notifications']);
    }

    $_SESSION['success'] = 1;

    header("Location: notifications.php");
}

/**
 * displayMarkAllReadSubmit 
 * 
 * @return void
 */
function displayMarkAllReadSubmit ()
{
    global $currentUserId;

    $sql = "UPDATE `fcms_notification`
            SET `read` = 1
            WHERE `user` = '$currentUserId'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    // Need to recalculate notification count
    if (isset($_SESSION['notifications']))
    {
        unset($_SESSION['notifications']);
    }

    $_SESSION['success'] = 1;

    header("Location: notifications.php");
}

/**
 * displayAllNotifications 
 * 
 * @return void
 */
function displayAllNotifications ()
{
    global $currentUserId;

    displayHeader();

    $sql = "SELECT `id`, `user`, `created_id`, `notification`, `data`, `created`, `updated`
            FROM `fcms_notification`
            WHERE `user` = '$currentUserId'
            AND `created_id` != '$currentUserId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        if (isset($_SESSION['notifications']))
        {
            unset($_SESSION['notifications']);
        }

        echo '
            <p class="info-alert">'.T_('You do not have any notifications.').'</p>';

        return;
    }

    echo '
        <div id="sections_menu" class="clearfix">
            <ul>
                <li><a href="notifications.php">'.T_('Unread Notifications').'</a></li>
            </ul>
        </div>
        <div id="notifications-list">';

    while ($r = mysql_fetch_assoc($result))
    {
        $date   = getHumanTimeSince(strtotime($r['created']));
        $date   = ' <span class="date">'.$date.'</span>';
        $info   = '';

        if ($r['notification'] == 'tagged_photo')
        {
            $displayName = getUserDisplayName($r['created_id']);

            list($uid, $cid, $pid, $filename) = explode(':', $r['data']);

            $info  = sprintf(T_('%s has added a photo of you.'), $displayName).$date;
            $info .= '<br/><a href="gallery/index.php?uid='.$uid.'&amp;cid='.$cid.'&amp;pid='.$pid.'">';
            $info .= '<img src="uploads/photos/member'.$uid.'/tb_'.basename($filename).'"/></a>';
        }

        echo '
                <p>
                    '.$info.'
                </p>';
    }

    echo '
        </div>';

    displayFooter();
}
