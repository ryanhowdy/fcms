<?php
/**
 * Private Message
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

load('datetime');

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
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Private Messages'),
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
     * @return void
     */
    function control ()
    {
        if (isset($_GET['compose']))
        {
            $this->displayComposeForm();
        }
        elseif (isset($_POST['submit']))
        {
            $this->displayComposeFormSubmit();
        }
        elseif (isset($_POST['delete']) && !isset($_POST['confirmed']))
        {
            $this->displayConfirmDelete();
        }
        elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed']))
        {
            $this->displayDeleteSubmit();
        }
        elseif (isset($_GET['pm']))
        {
            $this->displayPrivateMessage();
        }
        elseif (isset($_GET['sent']))
        {
            $this->displaySentPrivateMessage();
        }
        elseif (isset($_GET['folder']))
        {
            $this->displaySentFolder();
        }
        else
        {
            $this->displayInbox();
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
    if (!$$(\'.pm_footer input[type="submit"]\')) { return; }
    $$(\'.pm_footer input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    });
    return true;
});
//]]>
</script>';

        require_once getTheme($this->fcmsUser->id).'header.php';

        $link = T_('Inbox');

        if (isset($_SESSION['private_messages']) && $_SESSION['private_messages'] > 0)
        {
            $link = sprintf(T_('Inbox (%d)'), $_SESSION['private_messages']);
        }

        echo '
        <div id="privatemsg" class="centercontent">

            <div id="actions_menu">
                <ul><li><a href="?compose=new">'.T_('New Message').'</a></li></ul>
            </div>

            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="privatemsg.php">'.$link.'</a></li>
                    <li><a href="privatemsg.php?folder=sent">'.T_('Sent').'</a></li>
                </ul>
            </div>

            <div id="maincolumn">';
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
            </div><!--/maincolumn-->
        </div><!--/profile-->';

        include_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayComposeForm 
     * 
     * @return void
     */
    function displayComposeForm ()
    {
        $this->displayHeader();

        $id    = '';
        $title = '';

        if (isset($_GET['id']))
        {
            $id = (int)$_GET['id'];
        }

        if (isset($_GET['title']))
        {
            $title = strip_tags($_GET['title']);
            $title = 'RE: '.cleanOutput($title);
        }

        $sql = "SELECT `id`
                FROM `fcms_users` 
                WHERE `activated` > 0
                AND `password` != 'NONMEMBER'";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // TODO - optimize sql query above to get display name info instead
        foreach ($rows as $r)
        {
            $displayNameList[$r['id']] = getUserDisplayName($r['id'], 2);
        }

        asort($displayNameList);

        $user_options = buildHtmlSelectOptions($displayNameList, $id);

        echo '
            <script type="text/javascript" src="ui/js/livevalidation.js"></script>
            <form method="post" id="newpmform" action="privatemsg.php">
                <fieldset>
                    <legend><span>'.T_('New PM').'</span></legend>
                    <div>
                        <label for="title">'.T_('Subject').'</label>: 
                        <input type="text" id="title" name="title" size="50" value="'.$title.'"/>
                    </div><br/>
                    <script type="text/javascript">
                        var ftitle = new LiveValidation(\'title\', { onlyOnSubmit: true });
                        ftitle.add(Validate.Presence, { failureMessage: "" });
                    </script>
                    <div>
                        <label for="to">'.T_('To').'</label>: 
                        <select name="to">
                            '.$user_options.'
                        </select>
                    </div><br/>
                    <script type="text/javascript">var bb = new BBCode();</script>';

        displayBBCodeToolbar();

        echo '
                    <div><textarea name="post" id="post" rows="10" cols="63"></textarea></div>
                    <script type="text/javascript">bb.init(\'post\');</script>
                    <script type="text/javascript">
                        var fpost = new LiveValidation(\'post\', { onlyOnSubmit: true });
                        fpost.add(Validate.Presence, { failureMessage: "" });
                    </script>
                    <p>
                        <input class="sub1" type="submit" name="submit" value="'.T_('Send').'"/> &nbsp;
                        <a href="privatemsg.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>
            <p>&nbsp;</p>';

        $this->displayFooter();
    }

    /**
     * displayComposeFormSubmit 
     * 
     * @return void
     */
    function displayComposeFormSubmit ()
    {
        $to    = (int)$_POST['to']; 
        $title = strip_tags($_POST['title']);
        $msg   = strip_tags($_POST['post']);

        if (strlen($title) <= 0 || strlen($msg) <= 0)
        {
            header("Location: privatemsg.php");
            return;
        }

        // Insert the PM into the DB
        $sql = "INSERT INTO `fcms_privatemsg` 
                    (`to`, `from`, `date`, `title`, `msg`) 
                VALUES
                    (?, ?, NOW(), ?, ?)";

        $params = array(
            $to, 
            $this->fcmsUser->id, 
            $title, 
            $msg
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Email the PM to the user
        $sql = "SELECT `email` FROM `fcms_users` 
                WHERE `id` = ?";

        $r = $this->fcmsDatabase->getRow($sql, $to);
        if ($r === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $from     = $this->fcmsUser->displayName;
        $reply    = $this->fcmsUser->email;
        $toName   = getUserDisplayName($to);
        $sitename = getSiteName();
        $sitename = html_entity_decode($sitename);
        $subject  = sprintf(T_('A new Private Message at %s'), $sitename);
        $email    = $r['email'];
        $url      = getDomainAndDir();

        $email_headers  = 'From: '.$sitename.' <'.getContactEmail().'>'."\r\n";
        $email_headers .= 'Reply-To: '.$reply."\r\n";
        $email_headers .= 'Content-Type: text/plain; charset=UTF-8;'."\r\n";
        $email_headers .= 'MIME-Version: 1.0'."\r\n";
        $email_headers .= 'X-Mailer: PHP/'.phpversion();

        $email_msg = T_('Dear').' '.$toName.',

'.sprintf(T_('%s has sent you a new Private Message at %s'), $from, $sitename).'

'.T_('The message has been attached below.').'

'.sprintf(T_('To respond to this message either visit %s or respond to this email.'), $url.'privatemsg.php').'

----

'.T_('From').': '.$from.'
'.T_('Message Title').': '.$title.'

'.$msg.'

';

        mail($email, $subject, $email_msg, $email_headers);

        $_SESSION['success'] = 1;

        header("Location: privatemsg.php");
    }

    /**
     * displayConfirmDelete 
     * 
     * @return void
     */
    function displayConfirmDelete ()
    {
        $this->displayHeader();

        echo '
                <div class="info-alert>
                    <form action="privatemsg.php" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>';

        foreach ($_POST['del'] as $id)
        {
            echo '
                            <input type="hidden" name="del[]" value="'.(int)$id.'"/>';
        }

        echo '
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="privatemsg.php">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

        $this->displayFooter();
    }

    /**
     * displayDeleteSubmit 
     * 
     * @return void
     */
    function displayDeleteSubmit ()
    {
        foreach ($_POST['del'] as $id)
        {
            $sql = "DELETE FROM `fcms_privatemsg` 
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->delete($sql, $id))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }
        }

        $_SESSION['success'] = 1;

        header("Location: privatemsg.php");
    }

    /**
     * displayPrivateMessage 
     * 
     * @return void
     */
    function displayPrivateMessage ()
    {
        $id = (int)$_GET['pm'];

        $this->displayHeader();

        $sql = "SELECT p.`id`, `to`, `from`, `title`, `msg`, `date`, `read`, u.`avatar`, u.`gravatar`
                FROM `fcms_privatemsg` AS p
                LEFT JOIN `fcms_users` AS u ON p.`from` = u.`id`
                WHERE p.`id` = ?
                AND `to` = ?";

        $r = $this->fcmsDatabase->getRow($sql, array($id, $this->fcmsUser->id));
        if ($r === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (empty($r))
        {
            echo '
            <p class="error-alert">
                '.T_('The PM you are trying to view either doesn\'t exist or you don\'t have permission to view it.').'
            </p>';

            return;
        }

        $sql = "UPDATE `fcms_privatemsg` 
                SET `read` = '1' 
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->update($sql, $id))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $tzOffset   = getTimezone($this->fcmsUser->id);
        $date       = fixDate(T_('n/j/Y g:i a'), $tzOffset, $r['date']);
        $avatarPath = getAvatarPath($r['avatar'], $r['gravatar']);
        $from       = getUserDisplayName($r['from']);

        echo '
            <div id="pm_msg">
                <div class="user">
                    <img src="'.$avatarPath.'" alt="'.$from.'" title="'.$from.'"/>
                    <h3>'.cleanOutput($r['title']).'</h3>
                    <b>'.sprintf(T_('by %s'), $from).'</b>
                    <span>'.$date.'</span>
                </div>
                <p>
                    '.parse($r['msg']).'
                </p>
                <a href="?compose=new&amp;id='.(int)$r['from'].'&amp;title='.cleanOutput($r['title']).'">'.T_('Reply').'
            </div>';

        $this->displayFooter();
    }

    /**
     * displaySentPrivateMessage ()
     * 
     * @return void
     */
    function displaySentPrivateMessage ()
    {
        $id = (int)$_GET['sent'];

        $this->displayHeader();

        $sql = "SELECT p.`id`, `to`, `from`, `title`, `msg`, `date`, `read`, u.`avatar`, u.`gravatar`
                FROM `fcms_privatemsg` AS p
                LEFT JOIN `fcms_users` AS u ON p.`to` = u.`id`
                WHERE p.`id` = '$id' 
                AND `from` = '$this->fcmsUser->id'";

        $r = $this->fcmsDatabase->getRow($sql, array($id, $this->fcmsUser->id));
        if ($r === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (empty($r))
        {
            echo '
            <p class="error-alert">
                '.T_('The PM you are trying to view either doesn\'t exist or you don\'t have permission to view it.').'
            </p>';

            $this->displayFooter();

            return;
        }

        $tzOffset   = getTimezone($this->fcmsUser->id);
        $date       = fixDate(T_('n/j/Y g:i a'), $tzOffset, $r['date']);
        $avatarPath = getAvatarPath($r['avatar'], $r['gravatar']);
        $to         = getUserDisplayName($r['to']);

        echo '
            <div id="pm_msg">
                <div class="user">
                    <img src="'.$avatarPath.'" alt="'.$to.'" title="'.$to.'"/>
                    <h3>'.cleanOutput($r['title']).'</h3>
                    <b>'.sprintf(T_('to %s'), $to).'</b>
                    <span>'.$date.'</span>
                </div>
                <p>
                    '.parse($r['msg']).'
                </p>
            </div>';

        $this->displayFooter();
    }

    /**
     * displaySentFolder 
     * 
     * @return void
     */
    function displaySentFolder ()
    {
        $this->displayHeader();

        echo '
                <table id="pm" cellpadding="0" cellspacing="0">
                    <tr>
                        <th colspan="5" class="pm_header">'.T_('Sent Messages').'</th>
                    </tr>';

        $sql = "SELECT p.`id`, `to`, `from`, `title`, `date`, `read`, u.`avatar`, u.`gravatar`
                FROM `fcms_privatemsg` AS p
                LEFT JOIN `fcms_users` AS u ON p.`to` = u.`id`
                WHERE `from` = ?
                ORDER BY `date` DESC";

        $rows = $this->fcmsDatabase->getRows($sql, $this->fcmsUser->id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        foreach ($rows as $r)
        {
            $date       = fixDate(T_('M. j, Y, g:i a'), $this->fcmsUser->tzOffset, $r['date']);
            $avatarPath = getAvatarPath($r['avatar'], $r['gravatar']);
            $to         = getUserDisplayName($r['to']);

            echo '
                    <tr>
                        <td>
                            <div class="user">
                                <img src="'.$avatarPath.'" alt="'.$to.'" title="'.$to.'"/>
                            </div>
                            <a href="?sent='.(int)$r['id'].'">'.cleanOutput($r['title']).'</a>
                            <span>'.$date.'</span>
                        </td>
                    </tr>';
        }

        echo '
                    <tr><th colspan="5" class="pm_footer">&nbsp;</th></tr>
                </table>';

        $this->displayFooter();
    }

    /**
     * displayInbox 
     * 
     * @return void
     */
    function displayInbox ()
    {
        $this->displayHeader();

        if (isset($_SESSION['success']))
        {
            displayOkMessage();
            unset($_SESSION['success']);
        }

        $header = T_('Inbox');

        if (isset($_SESSION['private_messages']) && $_SESSION['private_messages'] > 0)
        {
            $header = sprintf(T_('Inbox (%d)'), $_SESSION['private_messages']);
        }
            
        echo '
            <form method="post" action="privatemsg.php">
                <table id="pm" cellpadding="0" cellspacing="0">
                    <tr>
                        <th colspan="3" class="pm_header">'.$header.'</th>
                    </tr>';

        $sql = "SELECT p.`id`, `to`, `from`, `title`, `date`, `read`, u.`avatar`, u.`gravatar`
                FROM `fcms_privatemsg` AS p
                LEFT JOIN `fcms_users` AS u ON p.`from` = u.`id`
                WHERE `to` = ?
                ORDER BY `date` DESC";

        $rows = $this->fcmsDatabase->getRows($sql, $this->fcmsUser->id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        foreach ($rows as $r)
        {
            $date       = fixDate(T_('M. j, Y, g:i a'), $this->fcmsUser->tzOffset, $r['date']);
            $avatarPath = getAvatarPath($r['avatar'], $r['gravatar']);
            $from       = getUserDisplayName($r['from']);
            $rowClass   = '';
            $linkClass  = 'read';

            if ($r['read'] < 1)
            {
                $rowClass  = 'new';
                $linkClass = '';
            }

            echo '
                    <tr class="'.$rowClass.'">
                        <td class="img"></td>
                        <td>
                            <div class="user">
                                <img src="'.$avatarPath.'" alt="'.$from.'" title="'.$from.'"/>
                            </div>
                            <a class="'.$linkClass.'" href="?pm='.(int)$r['id'].'">'.cleanOutput($r['title']).'</a>
                            <span>'.$date.'</span>
                        </td>
                        <td class="check"><input type="checkbox" name="del[]" value="'.(int)$r['id'].'"/></td>
                    </tr>';
        }

        echo '
                    <tr>
                        <th colspan="3" class="pm_footer">
                            <input class="sub1" type="submit" name="delete" value="'.T_('Delete Selected').'"/>
                        </th>
                    </tr>
                </table>
            </form>';

        $this->displayFooter();
    }
}
