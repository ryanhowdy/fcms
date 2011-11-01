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

require 'fcms.php';

load('database', 'privatemsg');

init();

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$pm            = new PrivateMessage($currentUserId);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Private Messages'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
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

// Show Header
require_once getTheme($currentUserId).'header.php';

echo '
        <div id="privatemsg" class="centercontent">

            <div id="actions_menu" class="clearfix">
                <ul><li><a href="?compose=new">'.T_('New Message').'</a></li></ul>
            </div>

            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="privatemsg.php">'.T_('Inbox').'</a></li>
                    <li><a href="privatemsg.php?folder=sent">'.T_('Sent').'</a></li>
                </ul>
            </div>

            <div id="maincolumn">';

$show = true;

//------------------------------------------------------------------------------
// Display form to create a new PM
//------------------------------------------------------------------------------
if (isset($_GET['compose']))
{
    $show = false;

    // PM with no title
    if (isset($_GET['id']) && !isset($_GET['title']))
    {
        $id = cleanInput($_GET['id'], 'int');
        $pm->displayNewMessageForm($id);

    }
    // PM with title
    elseif (isset($_GET['id']) && isset($_GET['title']))
    {
        $id    = cleanInput($_GET['id'], 'int');
        $title = cleanInput($_GET['title']);

        $pm->displayNewMessageForm($id, $title);

    }
    else
    {
        $pm->displayNewMessageForm();
    }

}
//------------------------------------------------------------------------------
// Send PM
//------------------------------------------------------------------------------
elseif (isset($_POST['submit']))
{
    $title = cleanInput($_POST['title']);
    $msg   = cleanInput($_POST['post']);

    if (strlen($title) > 0 && strlen($msg) > 0)
    {
        // Insert the PM into the DB
        $sql = "INSERT INTO `fcms_privatemsg` 
                    (`to`, `from`, `date`, `title`, `msg`) 
                VALUES (
                    '".cleanInput($_POST['to'])."', 
                    '$currentUserId', 
                    NOW(), 
                    '$title', 
                    '$msg'
                )";
        if (!mysql_query($sql))
        {
            displaySQLError('Send PM Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            exit();
        }

        // Email the PM to the user
        $sql = "SELECT * FROM `fcms_users` 
                WHERE `id` = '".cleanInput($_POST['to'])."'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Get User Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            exit();
        }

        $r = mysql_fetch_array($result);

        $from     = getUserDisplayName($currentUserId);
        $reply    = getUserEmail($currentUserId);
        $to       = getUserDisplayName($_POST['to']);
        $sitename = getSiteName();
        $subject  = sprintf(T_('A new Private Message at %s'), $sitename);
        $email    = $r['email'];
        $url      = getDomainAndDir();

        $email_headers  = 'From: '.getSiteName().' <'.getContactEmail().'>'."\r\n";
        $email_headers .= 'Reply-To: '.$reply."\r\n";
        $email_headers .= 'Content-Type: text/plain; charset=UTF-8;'."\r\n";
        $email_headers .= 'MIME-Version: 1.0'."\r\n";
        $email_headers .= 'X-Mailer: PHP/'.phpversion();

        $email_msg = T_('Dear').' '.$to.',

'.sprintf(T_('%s has sent you a new Private Message at %s'), $from, $sitename).'

'.T_('The message has been attached below.').'

'.sprintf(T_('To respond to this message either visit %s or respond to this email.'), $url.'privatemsg.php').'

----

'.T_('From').': '.$from.'
'.T_('Message Title').': '.$title.'

'.$msg.'

';

        mail($email, $subject, $email_msg, $email_headers);

        echo '
            <p class="ok-alert" id="sent">'.sprintf(T_('A Private Message has been sent to %s'), $to).'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'sent\').toggle()",3000); }
            </script>';
    }

}
//------------------------------------------------------------------------------
// Delete confirmation
//------------------------------------------------------------------------------
else if (isset($_POST['delete']) && !isset($_POST['confirmed']))
{
    $show = false;
    echo '
                <div class="info-alert clearfix">
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

}
//------------------------------------------------------------------------------
// Delete PM
//------------------------------------------------------------------------------
elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed']))
{
    if (isset($_POST['del']))
    {
        $i = 0;
        foreach ($_POST['del'] as $id)
        {
            $sql = "DELETE FROM `fcms_privatemsg` WHERE `id` = ".escape_string($id);
            if (!mysql_query($sql))
            {
                displaySQLError('Delete PM Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                displayFooter();
                exit();
            }
            $i++;
        }
        echo '
            <p class="ok-alert" id="del">'.sprintf(T_ngettext('%d Private Message Deleted Successfully', '%d Private Messages Deleted Successfully', $i), $i).'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'del\').toggle()",3000); }
            </script>';
    }
}
elseif (isset($_GET['pm']))
{
    $show = false;

    $privateMsg = cleanInput($_GET['pm']);
    $pm->displayPM($privateMsg);
}
elseif (isset($_GET['sent']))
{
    $show = false;
    $sent = cleanInput($_GET['sent']);
    $pm->displaySentPM($sent);
}

if ($show)
{
    if (isset($_GET['folder']))
    {
        $pm->displaySentFolder();
    }
    else
    {
        $pm->displayInbox();
    }
}

displayFooter();


/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    global $currentUserId, $TMPL;

    echo '
            </div>
        </div><!-- #profile .centercontent -->';

    include_once getTheme($currentUserId).'footer.php';
}
