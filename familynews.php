<?php
/**
 * Family News
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

load('familynews');

init();

$fnews = new FamilyNews($fcmsUser->id);

// Setup the Template variables;
$TMPL = array(
    'currentUserId' => $fcmsUser->id,
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Family News'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($fcmsUser->id),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    if (!$$(\'.delnews input[type="submit"]\')) { return; }
    $$(\'.delnews input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    });
    if (!$$(\'.delcom input[type="submit"]\')) { return; }
    $$(\'.delcom input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'comconfirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    });
    if ($(\'toolbar\')) {
        $(\'toolbar\').removeClassName("hideme");
    }
    if ($(\'smileys\')) {
        $(\'smileys\').removeClassName("hideme");
    }
    if ($(\'upimages\')) {
        $(\'upimages\').removeClassName("hideme");
    }
    return true;
});
//]]>
</script>';

// Show Header
require_once getTheme($fcmsUser->id).'header.php';

echo '
        <div id="familynews" class="centercontent">';

if (checkAccess($fcmsUser->id) < 6 || checkAccess($fcmsUser->id) == 9)
{
    echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="familynews.php">'.T_('Latest News').'</a></li>';

    if ($fnews->hasNews($fcmsUser->id))
    {
        echo '
                    <li><a href="?getnews='.$fcmsUser->id.'">'.T_('My News').'</a></li>';
    }

    echo '
                </ul>
            </div>
            <div id="actions_menu">
                <ul>
                    <li><a href="?addnews=yes">'.T_('Add News').'</a></li>
                </ul>
            </div>';
}

$show_last5 = true;

//-------------------------------------
// Side menu user's news listing
//-------------------------------------
if (!isset($_GET['addnews']) && !isset($_POST['editnews']))
{
    $fnews->displayNewsList();
}

//-------------------------------------
// Add news
//-------------------------------------
if (isset($_POST['submitadd']))
{
    $title = escape_string($_POST['title']);
    $news  = escape_string($_POST['post']);

    $sql = "INSERT INTO `fcms_news` (
                `title`, `news`, `user`, `created`, `updated`
            ) VALUES (
                '$title', 
                '$news', 
                '$fcmsUser->id', 
                NOW(),
                NOW()
            )";

    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        return;
    }

    displayOkMessage();

    // Email members
    $sql = "SELECT u.`email`, s.`user` 
            FROM `fcms_user_settings` AS s, `fcms_users` AS u 
            WHERE `email_updates` = '1'
            AND u.`id` = s.`user`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return;
    }

    if (mysql_num_rows($result) > 0)
    {
        while ($r = mysql_fetch_array($result))
        {
            $name          = getUserDisplayName($fcmsUser->id);
            $to            = getUserDisplayName($r['user']);
            $subject       = sprintf(T_('%s has added %s to his/her Family News'), $name, $_POST['title']);
            $email         = $r['email'];
            $url           = getDomainAndDir();
            $email_headers = getEmailHeaders();

            $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'familynews.php?getnews='.$fcmsUser->id.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
            mail($email, $subject, $msg, $email_headers);
        }
    }
}
//-------------------------------------
// Edit news
//-------------------------------------
elseif (isset($_POST['submitedit']))
{
    $show_last5 = false;

    $title = escape_string($_POST['title']);
    $news  = escape_string($_POST['post']);
    $id    = (int)$_POST['id'];
    $user  = (int)$_POST['user'];

    $sql = "UPDATE `fcms_news` 
            SET `title` = '$title', 
                `news`  = '$news' 
            WHERE `id`  = '$id'";

    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());

        $fnews->displayFamilyNews($user, $id);

        return;
    }

    displayOkMessage();

    $fnews->displayFamilyNews($user, $id);
}

//-------------------------------------
// Add news form
//-------------------------------------
if (isset($_GET['addnews']) && (checkAccess($fcmsUser->id) < 6 || checkAccess($fcmsUser->id) == 9))
{ 
    $show_last5 = false;
    $fnews->displayForm('add', $fcmsUser->id);
}
//-------------------------------------
// Edit news form
//-------------------------------------
else if (isset($_POST['editnews']))
{
    $show_last5 = false;

    $fnews->displayForm('edit', $_POST['user'], $_POST['id'], $_POST['title'], $_POST['news']);
}
//-------------------------------------
// Delete news confirmation
//-------------------------------------
else if (isset($_POST['delnews']) && !isset($_POST['confirmed']))
{
    $show_last5 = false;
    echo '
                <div class="info-alert">
                    <form action="familynews.php?getnews='.(int)$_POST['user'].'" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="user" value="'.(int)$_POST['user'].'"/>
                            <input type="hidden" name="id" value="'.(int)$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="familynews.php?getnews='.(int)$_POST['user'].'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';
}
//-------------------------------------
// Delete news
//-------------------------------------
elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed']))
{
    $show_last5 = false;

    $sql = "DELETE FROM `fcms_news` 
            WHERE id = '".(int)$_POST['id']."'";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        return;
    }

    displayOkMessage(T_('Family News Deleted Successfully'));
}

//-------------------------------------
// Show news
//-------------------------------------
if (isset($_GET['getnews']))
{
    $show_last5 = false;

    $user = (int)$_GET['getnews'];

    $page = getPage();
    $nid  = isset($_GET['newsid']) ? (int)$_GET['newsid'] : 0;

    // Add Comment
    if (isset($_POST['addcom']))
    {
        $com = ltrim($_POST['comment']);
        $com = escape_string($com);
        if (!empty($com))
        {
            $sql = "INSERT INTO `fcms_news_comments` (
                        `news`, `comment`, `date`, `user`
                    ) VALUES (
                        '$nid', '$com', NOW(), $fcmsUser->id
                    )";
            if (!mysql_query($sql))
            {
                displaySqlError($sql, mysql_error());
            }
        }
    }

    // Delete comment confirmation
    if (isset($_POST['delcom']) && !isset($_POST['comconfirmed']))
    {
        $show_last5 = false;
        echo '
                <div class="info-alert">
                    <form action="familynews.php?getnews='.(int)$_GET['getnews'].'" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.(int)$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delcomconfirm" name="delcomconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="familynews.php?getnews='.(int)$_GET['getnews'].'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    }
    // Delete news
    elseif (isset($_POST['delcomconfirm']) || isset($_POST['comconfirmed']))
    {
        $sql = "DELETE FROM `fcms_news_comments`
                WHERE `id` = '".(int)$_POST['id']."'";

        if (!mysql_query($sql))
        {
            displaySqlError($sql, mysql_error());
        }
    }

    // Show single news
    if ($nid > 0)
    {
        $fnews->displayFamilyNews($user, $nid);
    }
    // Show news for this user
    else
    {
        $fnews->displayUserFamilyNews($user, $page);
    }
}

// Show last 5 news
if ($show_last5)
{
    $fnews->displayLast5News();
}

echo '
        </div><!-- #familynews .centercontent -->';

// Show Footer
require_once getTheme($fcmsUser->id).'footer.php';
