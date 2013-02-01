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
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('profile', 'image', 'address', 'phone', 'gallery');

init();

$gallery = new PhotoGallery($fcmsError, $fcmsDatabase, $fcmsUser);
$page    = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $gallery);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsMessageBoard;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsPhotoGallery)
    {
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;
        $this->fcmsPhotoGallery = $fcmsPhotoGallery;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Notifications'),
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

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
        if (isset($_GET['markread']))
        {
            if ($_GET['markread'] == 'all')
            {
                $this->displayMarkAllReadSubmit();
            }
            else
            {
                $this->displayMarkReadSubmit();
            }
        }
        elseif (isset($_GET['view']))
        {
            $this->displayAllNotifications();
        }
        else
        {
            $this->displayNotifications();
        }
    }

    /**
     * displayHeader 
     * 
     * @return void
     */
    function displayHeader ()
    {
        $TMPL = $this->fcmsTemplate;

        $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
});
//]]>
</script>';

        require_once getTheme($this->fcmsUser->id).'header.php';

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
        $TMPL = $this->fcmsTemplate;

        echo '
        </div><!--/notifications-->';

        include_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayNotifications 
     * 
     * @return void
     */
    function displayNotifications ()
    {
        $this->displayHeader();

        if (isset($_SESSION['success']))
        {
            displayOKMessage();

            unset($_SESSION['success']);
        }

        $sql = "SELECT `id`, `user`, `created_id`, `notification`, `data`, `created`, `updated`
                FROM `fcms_notification`
                WHERE `user` = ?
                AND `read` = 0
                AND `created_id` != ?";

        $params = array(
            $this->fcmsUser->id,
            $this->fcmsUser->id
        );

        $rows = $this->fcmsDatabase->getRows($sql, $params);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (count($rows) <= 0)
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
        <div id="actions_menu">
            <ul>
                <li><a href="notifications.php?markread=all">'.T_('Mark All Read').'</a></li>
            </ul>
        </div>
        <div id="notifications-list">';

        foreach ($rows as $r)
        {
            $date   = getHumanTimeSince(strtotime($r['created']));
            $date   = ' <span class="date">'.$date.'</span>';
            $info   = '';
            $action = '<a class="read" href="?markread='.$r['id'].'">'.T_('Mark Read').'</a>';

            if ($r['notification'] == 'tagged_photo')
            {
                $displayName = getUserDisplayName($r['created_id']);

                list($uid, $cid, $pid, $filename) = explode(':', $r['data']);

                $data = array(
                    'id'          => $pid,
                    'external_id' => null,
                    'filename'    => $filename,
                    'user'        => $uid,
                );
                $photoSrc = $this->fcmsPhotoGallery->getPhotoSource($data);

                $info  = sprintf(T_('%s has added a photo of you.'), $displayName).$date;
                $info .= '<br/><a href="gallery/index.php?uid='.$uid.'&amp;cid='.$cid.'&amp;pid='.$pid.'">';
                $info .= '<img src="'.$photoSrc.'"/></a>';
            }

            echo '
                <p>
                    '.$action.'
                    '.$info.'
                </p>';
        }

        echo '
        </div>';

        $this->displayFooter();
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
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->update($sql, $id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

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
        global $fcmsUser;

        $sql = "UPDATE `fcms_notification`
                SET `read` = 1
                WHERE `user` = ?";

        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

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
        global $fcmsUser;

        $this->displayHeader();

        $sql = "SELECT `id`, `user`, `created_id`, `notification`, `data`, `created`, `updated`
                FROM `fcms_notification`
                WHERE `user` = ?
                AND `created_id` != ?";

        $params = array(
            $this->fcmsUser->id,
            $this->fcmsUser->id
        );

        $rows = $this->fcmsDatabase->getRows($sql, $params);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (count($rows) <= 0)
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
        <div id="sections_menu">
            <ul>
                <li><a href="notifications.php">'.T_('Unread Notifications').'</a></li>
            </ul>
        </div>
        <div id="notifications-list">';

        foreach ($rows as $r)
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

        $this->displayFooter();
    }
}
