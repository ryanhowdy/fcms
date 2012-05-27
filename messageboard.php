<?php
/**
 * Members
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

load('datetime', 'messageboard');

init();

// Setup some globals
$currentAccess = checkAccess($fcmsUser->id);
$msgBoardObj   = new MessageBoard($fcmsUser->id);

// Setup the Template variables;
$TMPL = array(
    'currentUserId' => $fcmsUser->id,
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Message Board'),
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
    global $currentAccess;

    // New Post
    if (isset($_GET['reply']))
    {
        displayNewPostForm();
    }
    elseif (isset($_POST['reply_submit']))
    {
        displayNewPostSubmit();
    }
    elseif (isset($_POST['post_submit']))
    {
        displayNewThreadSubmit();
    }
    // Edit Post
    elseif (isset($_POST['editpost']))
    {
        displayEditPostForm();
    }
    elseif (isset($_POST['edit_submit']))
    {
        displayEditPostSubmit();
    }
    // Delete
    elseif (isset($_POST['delpost']) && !isset($_POST['confirmed']))
    {
        displayConfirmDelete();
    }
    elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed']))
    {
        displayDeletePostSubmit();
    }
    // Administrate Thread
    elseif (isset($_POST['submit_admin']) && $currentAccess < 2)
    {
        displayAdministrateThreadSubmit();
    }
    // Admin Edit Subject
    elseif (isset($_POST['edit_admin_submit']))
    {
        displayAdminEditSubjectSubmit();
    }
    // Search results
    elseif (isset($_POST['search']))
    {
        displaySearchSubmit();
    }
    elseif (isset($_GET['search']))
    {
        displayAdvancedSearchForm();
    }
    elseif (isset($_GET['thread']))
    {
        displayThread();
    }
    else
    {
        displayThreads();
    }
}

/**
 * displayHeader 
 * 
 * @param string $js Javascript to overwrite the default
 * 
 * @return void
 */
function displayHeader ($js = '')
{
    global $fcmsUser, $TMPL;

    $TMPL['javascript'] = $js;

    // Default js
    if ($js == '')
    {
        $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    if (!$$(\'.delpost input[type="submit"]\')) { return; }
    $$(\'.delpost input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmed\');
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
    }

    include getTheme($fcmsUser->id).'header.php';

    echo '
        <div id="messageboard" class="centercontent">';
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
        </div><!-- #messageboard .centercontent -->';

    include getTheme($fcmsUser->id).'footer.php';
}

/**
 * displayThreads 
 * 
 * @return void
 */
function displayThreads ()
{
    global $msgBoardObj;

    $page = getPage();

    displayHeader();

    if (isset($_SESSION['success']))
    {
        displayOkMessage();

        unset($_SESSION['success']);
    }

    $msgBoardObj->showThreads('announcement');
    $msgBoardObj->showThreads('thread', $page);

    displayFooter();
}

/**
 * displayThread 
 * 
 * Displays the posts for a specific thread.
 * 
 * @return void
 */
function displayThread ()
{
    global $msgBoardObj;

    displayHeader();

    $threadId = (int)$_GET['thread'];
    $page     = getPage();

    if (isset($_SESSION['success']))
    {
        displayOkMessage();

        unset($_SESSION['success']);
    }

    $msgBoardObj->showPosts($threadId, $page);

    displayFooter();
}

/**
 * displayNewThreadSubmit 
 * 
 * @return void
 */
function displayNewThreadSubmit ()
{
    global $fcmsUser, $TMPL, $msgBoardObj;

    $rawPost    = $_POST['post'];
    $rawSubject = $_POST['subject'];
    $post       = escape_string($_POST['post']);
    $subject    = escape_string($_POST['subject']);

    displayHeader();

    if (isset($_POST['sticky']))
    {
        $subject = "#ANOUNCE#".$subject;
    }

    // Create new thread
    $sql = "INSERT INTO `fcms_board_threads` 
                (`subject`, `started_by`, `updated`, `updated_by`) 
            VALUES (
                '$subject', 
                '$fcmsUser->id', 
                NOW(), 
                '$fcmsUser->id'
            )";

    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $newThreadId = mysql_insert_id();

    // Create new post
    $sql = "INSERT INTO `fcms_board_posts`(`date`, `thread`, `user`, `post`) 
            VALUES (
                NOW(), 
                '$newThreadId', 
                '$fcmsUser->id', 
                '$post'
            )";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
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
    }

    if (mysql_num_rows($result) > 0)
    {
        while ($r = mysql_fetch_array($result))
        {
            $name = getUserDisplayName($fcmsUser->id);
            $to   = getUserDisplayName($r['user']);

            // Email is sent as plain text
            $emailHeaders  = getEmailHeaders();
            $emailSubject  = sprintf(T_('%s started the new thread %s.'), $name, $rawSubject);
            $email         = $r['email'];
            $url           = getDomainAndDir();

            $msg = T_('Dear').' '.$to.',

'.$emailSubject.'

'.$url.'messageboard.php?thread='.$newThreadId.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';

            mail($email, $rawSubject, $msg, $emailHeaders);
        }
    }

    // Display the new thread
    $msgBoardObj->showPosts($newThreadId, 1);
    displayFooter();
}

/**
 * displayNewPostSubmit 
 * 
 * @return void
 */
function displayNewPostSubmit ()
{
    global $fcmsUser, $TMPL, $msgBoardObj;

    displayHeader();

    $rawPost    = $_POST['post'];
    $post       = escape_string($rawPost);
    $threadId   = (int)$_POST['thread_id'];

    // Update Thread info
    $sql = "UPDATE `fcms_board_threads` 
            SET `updated` = NOW(), `updated_by` = '$fcmsUser->id' 
            WHERE `id` = $threadId";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        return;
    }

    // Insert new Post
    $sql = "INSERT INTO `fcms_board_posts` (`date`, `thread`, `user`, `post`) 
            VALUES (
                NOW(), 
                '$threadId', 
                '$fcmsUser->id', 
                '$post'
            )";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        return;
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
    }

    if (mysql_num_rows($result) > 0)
    {
        while ($r = mysql_fetch_array($result))
        {
            $name = getUserDisplayName($fcmsUser->id);

            $sql = "SELECT `subject` 
                    FROM `fcms_board_threads` 
                    WHERE `id` = $threadId";

            $subject_result = mysql_query($sql);
            if (!$subject_result)
            {
                displaySqlError($sql, mysql_error());
                return;
            }

            $row = mysql_fetch_array($subject_result);

            $threadSubject = $row['subject'];

            $pos = strpos($threadSubject, '#ANOUNCE#'); 
            if ($pos !== false)
            {
                $threadSubject = substr($threadSubject, 9, strlen($threadSubject)-9);
            } 

            // Emails sent as plain text
            $emailHeaders  = getEmailHeaders();
            $subject       = sprintf(T_('%s has replied to the thread: %s'), $name, $threadSubject);
            $email         = $r['email'];
            $to            = getUserDisplayName($r['user']);
            $url           = getDomainAndDir();

            $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'messageboard.php?thread='.$threadId.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
            mail($email, $subject, $msg, $emailHeaders);
        }
    }

    $msgBoardObj->showPosts($threadId, 1);
    displayFooter();
}

/**
 * displayNewPostForm 
 * 
 * Used to create new posts.  Used when creating a new thread also.
 * 
 * @return void
 */
function displayNewPostForm ()
{
    global $fcmsUser, $TMPL, $currentAccess, $msgBoardObj;

    displayHeader();

    if ($currentAccess >= 8 && $currentAccess == 5)
    {
        echo '
            <p class="error-alert">'.T_('You do not have access to view this page.').'</p>';
        displayFooter();
        return;
    }

    if ($_GET['reply'] == 'new')
    {
        $msgBoardObj->displayForm('new');
    }
    else
    {
        $reply = (int)$_GET['reply'];

        if (isset($_POST['quotepost']))
        {
            $id = (int)$_POST['id'];

            $msgBoardObj->displayForm('reply', $reply, $id);
        }
        else
        {
            $msgBoardObj->displayForm('reply', $reply);
        }
    }

    displayFooter();
}

/**
 * displayEditPostForm 
 * 
 * Displays the form for editing a post.
 * 
 * @return void
 */
function displayEditPostForm ()
{
    global $msgBoardObj;

    displayHeader();

    $id = (int)$_POST['id'];

    $sql = "SELECT `post`, `thread`
            FROM `fcms_board_posts` 
            WHERE `id` = '$id'
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $r = mysql_fetch_array($result);

    $msgBoardObj->displayForm('edit', $r['thread'], $id, $r['post']);

    displayFooter();
}

/**
 * displayEditPostSubmit 
 * 
 * TODO - see below
 * 
 * @return void
 */
function displayEditPostSubmit ()
{
    global $msgBoardObj;

    $id       = (int)$_POST['id'];
    $threadId = (int)$_POST['thread_id'];
    $post     = escape_string($_POST['post']);

    displayHeader();

    // TODO
    // Need to find a better way to add the edited by text
    // this method could mess up if the site changes languages at some point
    $pos = strpos($post, "\n\n[size=small][i]".T_('Edited'));
    if ($pos === false)
    {
        $post = $post."\n\n[size=small][i]".T_('Edited')." ".fixDate('n/d/Y g:ia', $msgBoardObj->tzOffset)."[/i][/size]";
    }
    else
    {
        $post = substr($post, 0, $pos);
        $post = $post."[size=small][i]".T_('Edited')." ".fixDate('n/d/Y g:ia', $msgBoardObj->tzOffset)."[/i][/size]";
    }

    // Update Post
    $sql = "UPDATE `fcms_board_posts` 
            SET `post` = '$post' 
            WHERE `id` = '$id'";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $msgBoardObj->showPosts($threadId, 1);
    displayFooter();
}

/**
 * displayAdminEditSubjectSubmit 
 * 
 * The submit screen for editing the subject of a thread.
 * 
 * @return void
 */
function displayAdminEditSubjectSubmit ()
{
    global $msgBoardObj;

    $threadId = (int)$_POST['thread'];

    displayHeader();

    if (isset($_POST['sticky']))
    {
        $subject = "#ANOUNCE#".$_POST->escMySQL('subject');
    }
    else
    {
        $subject = $_POST->escMySQL('subject');
    }

    $sql = "UPDATE `fcms_board_threads` 
            SET `subject` = '$subject' 
            WHERE `id` = '$threadId'";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    displayOkMessage();

    $msgBoardObj->showPosts($threadId, 1);

    displayFooter();
}

/**
 * displayConfirmDelete 
 * 
 * The delete post confirmation screen, used when user doesn't have js turned on.
 * 
 * @return void
 */
function displayConfirmDelete ()
{
    $threadId = (int)$_POST['thread'];
    $id       = (int)$_POST['id'];

    displayHeader();

    echo '
                <div class="info-alert">
                    <form action="messageboard.php?thread='.$threadId.'" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.$id.'"/>
                            <input type="hidden" name="thread" value="'.$threadId.'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="messageboard.php?thread='.$threadId.'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';
    displayFooter();
}

/**
 * displayDeletePostSubmit 
 * 
 * The submit screen for deleting a post.
 * 
 * @return void
 */
function displayDeletePostSubmit ()
{
    global $msgBoardObj;

    $id       = (int)$_POST['id'];
    $threadId = (int)$_POST['thread'];

    // Get id of last post in the current thread
    $sql = "SELECT MAX(`id`) AS max 
            FROM `fcms_board_posts` 
            WHERE `thread` = '$threadId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $r   = mysql_fetch_array($result);
    $max = $r['max'];

    // Get total post count for this thread
    $sql = "SELECT `id` 
            FROM `fcms_board_posts` 
            WHERE `thread` = '$threadId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $total = mysql_num_rows($result);

    // If this thread only has one post
    if ($total == 1)
    {
        // Delete the entire thread
        $sql = "DELETE FROM `fcms_board_threads` 
                WHERE `id` = '$threadId'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        $_SESSION['success'] = 1;

        header("Location: messageboard.php?thread=$threadId");

        return;
    }
    // If we are deleting the last post in the thread
    elseif ($id == $max)
    {
        // Delete post
        $sql = "DELETE FROM `fcms_board_posts`  
                WHERE `id` = '$id'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        // Get new last post in the thread
        $sql = "SELECT MAX(`id`) AS max 
                FROM `fcms_board_posts` 
                WHERE `thread` = '$threadId'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        $row    = mysql_fetch_array($result);
        $newmax = $row['max'];

        // Get info from new last post
        $sql = "SELECT `date`, `user` 
                FROM `fcms_board_posts` 
                WHERE `id` = '$newmax'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        $r = mysql_fetch_array($result);

        // Update the thread with last post info
        $sql = "UPDATE `fcms_board_threads` 
                SET `updated` = '".$r['date']."', `updated_by` = ".$r['user']." 
                WHERE `id` = '$threadId'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }
    // We are deleting a post in the middle of the thread
    else
    {
        $sql = "DELETE FROM `fcms_board_posts`  
                WHERE `id` = '$id'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    $_SESSION['success'] = 1;

    header("Location: messageboard.php?thread=$threadId");
}

/**
 * displayAdministrateThreadSubmit 
 * 
 * The submit screen for administering a thread.
 * 
 * @return void
 */
function displayAdministrateThreadSubmit ()
{
    global $msgBoardObj;

    $threadId    = (int)$_POST['thread'];
    $adminOption = $_POST['admin_option'];

    // Did they submit a blank form?
    if (empty($adminOption))
    {
        header("Location: messageboard.php?thread=$threadId");
        return;
    }

    // Changing Thread type
    if ($adminOption == 'normal' || $adminOption == 'announcement')
    {
        $sql = "SELECT `subject`
                FROM `fcms_board_threads`
                WHERE `id` = '$threadId'
                LIMIT 1";

        $result = mysql_query($sql);
        if (!$result)
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        if (mysql_num_rows($result) < 1)
        {
            displayHeader();
            echo '<p class="error-alert">'.T_('Thread does not exist.').'</p>';
            displayFooter();
            return;
        }

        $row = mysql_fetch_array($result);

        // Normal Thread
        if ($adminOption == 'normal')
        {
            $subject = $msgBoardObj->fixSubject($row['subject']);
        }
        // Announcement
        else
        {
            $subject = '#ANOUNCE#'.$row['subject'];
        }

        $sql = "UPDATE `fcms_board_threads` 
                SET `subject` = '$mysqlSubject' 
                WHERE `id` = '$threadId'";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        $_SESSION['success'] = 1;

        header("Location: messageboard.php?thread=$threadId");
        return;
    } 

    // Edit Thread Subject
    if ($adminOption == 'subject')
    {
        displayHeader();
        $msgBoardObj->displayAdminEditSubjectForm($threadId);
        displayFooter();
        return;
    }

    // Delete thread
    if ($adminOption == 'delete')
    {
        $sql = "DELETE FROM `fcms_board_posts` 
                WHERE `thread` = '$threadId'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        $sql = "DELETE FROM `fcms_board_threads` 
                WHERE `id` = '$threadId'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        $_SESSION['success'] = 1;
    }

    header("Location: messageboard.php");
}

/**
 * displaySearchSubmit 
 * 
 * Display the results for the search query.
 * 
 * @return void
 */
function displaySearchSubmit ()
{
    global $msgBoardObj;

    $search = $_POST['search'];

    $advanced = false;

    // validate start date
    if (isset($_POST['start']))
    {
        $start = $_POST['start'];
        $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $start);

        if ($found === false || $found < 1)
        {
            $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($start));
            displayAdvancedSearchForm($error);
            return;
        }

        $advanced = true;
    }

    // validate end date
    if (isset($_POST['end']))
    {
        $end   = $_POST['end'];
        $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $end);

        if ($found === false || $found < 1)
        {
            $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($end));
            displayAdvancedSearchForm($error);
            return;
        }

        $advanced = true;
    }

    displayHeader();

    echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="messageboard.php">'.T_('Message Board Home').'</a></li>
                </ul>
            </div>
            <form method="post" action="messageboard.php">
                <p id="big_search">
                    <input type="text" id="search" name="search" value="'.cleanOutput($search).'"/>
                    <input type="submit" value="'.T_('Search').'"/><br/>
                    <a href="?search=advanced">'.T_('Advanced Search').'</a>
                </p>
            </form>';

    $mysqlSearch = escape_string($_POST['search']);

    // Thread subject
    $sql = "SELECT t.`id`, t.`subject`, t.`started_by`, p.`date`, p.`post`
            FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t
            WHERE p.`thread` = t.`id`
            AND `subject` LIKE '%$mysqlSearch%'";
    if ($advanced)
    {
        $sql .= "
            AND p.`date` >= '$start'
            AND p.`date` <= '$end'";
    }

    // Post body
    $sql .= "
            GROUP BY p.`thread`
            UNION
            SELECT t.`id`, t.`subject`, t.`started_by`, p.`date`, p.`post`
            FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t
            WHERE p.`thread` = t.`id`
            AND `post` LIKE '%$mysqlSearch%'";
    if ($advanced)
    {
        $sql .= "
            AND p.`date` >= '$start'
            AND p.`date` <= '$end'";
    }

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        echo '
            <div class="search_result">
                <p>'.T_('Could not find anything matching your search.').'</p>
            </div>';
        
    }

    while ($r = mysql_fetch_assoc($result))
    {
        // Remove #ANNOUNCE#
        $subject = $msgBoardObj->fixSubject($r['subject']);
        // Clean html
        $subject = cleanOutput($subject, 'html');
        // Put in our html (should be the only html rendered)
        $subject = str_ireplace($search, '<b>'.$search.'</b>', $subject);

        // Remove orig bbcode
        $post = removeBBCode($r['post']);
        // Clean html
        $post = cleanOutput($post, 'html');
        // Put in our html (should be the only html rendered)
        $post = str_ireplace($search, '<b>'.$search.'</b>', $post);
        $date = fixDate('n/d/Y g:ia', $msgBoardObj->tzOffset, $r['date']);

        echo '
            <div class="search_result">
                <a href="?thread='.$r['id'].'">'.$subject.'</a>
                <p>'.$post.'</p>
                <span>'.$date.'</span>
            </div>';
    }

    displayFooter();
}

/**
 * displayAdvancedSearchForm 
 * 
 * @param string $error Any previous error for this form.
 * 
 * @return void
 */
function displayAdvancedSearchForm ($error = '')
{
    global $tzOffset;

    $js = '
<link rel="stylesheet" type="text/css" href="ui/datechooser.css"/>
<script type="text/javascript" src="ui/js/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    var dc1 = new DateChooser();
    dc1.setUpdateField({\'start\':\'Y-m-d\'});
    dc1.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'start\');
    var dc2 = new DateChooser();
    dc2.setUpdateField({\'end\':\'Y-m-d\'});
    dc2.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'end\');
});
//]]>
</script>';

    displayHeader($js);

    $end   = fixDate('Y-m-d', $tzOffset, gmdate('Y-m-d H:i:s'));
    $start = date('Y-m-d', strtotime("$end -30 day"));

    if ($error != '')
    {
        $error = '<div class="error-alert">'.$error.'</div>';
    }

    echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="messageboard.php">'.T_('Message Board Home').'</a></li>
                </ul>
            </div>
            '.$error.'
            <form method="post" action="messageboard.php">
                <fieldset>
                    <legend><span>'.T_('Advanced Search').'</span></legend>
                    <div>
                        <label for="search">'.T_('Search For').'</label><br/>
                        <input type="text" id="search" name="search"/>
                    </div><br/>
                    <div>
                        <label for="date">'.T_('Date').'</label><br/> 
                        <input type="text" id="start" name="start" value="'.$start.'" size="6" maxlength="10"/> - 
                        <input type="text" id="end" name="end" value="'.$end.'" size="6" maxlength="10"/>
                    </div><br/>
                    <p>
                        <input type="submit" class="sub1" value="'.T_('Search').'" name="advanced" id="advanced"/>
                    </p>
                </fieldset>
            </form>';

    displayFooter();
}

/**
 * displayAdvancedSearchSubmit 
 * 
 * @return void
 */
function displayAdvancedSearchSubmit ()
{
    global $msgBoardObj;

    $start  = $_POST['start'];
    $end    = $_POST['end'];
    $search = $_POST['advanced-search'];

    // validate dates
    $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $start);
    if ($found === false || $found < 1)
    {
        $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($start));
        displayAdvancedSearchForm($error);
        return;
    }
    $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $end);
    if ($found === false || $found < 1)
    {
        $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($end));
        displayAdvancedSearchForm($error);
        return;
    }

    displayHeader();

    echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="messageboard.php">'.T_('Message Board Home').'</a></li>
                </ul>
            </div>
            <form method="post" action="messageboard.php">
                <p id="big_search">
                    <input type="text" id="search" name="search" value="'.cleanOutput($search, 'html').'"/>
                    <input type="submit" value="'.T_('Search').'"/><br/>
                    <a href="?search=advanced">'.T_('Advanced Search').'</a>
                </p>
            </form>';

    $mysqlSearch = $_POST->escMySQL($search);

    $sql = "SELECT t.`id`, t.`subject`, t.`started_by`, t.`updated`, p.`post`,
                'thread' AS type
            FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p
            WHERE p.`thread` = t.`id`
            AND `subject` LIKE '%$mysqlSearch%'
            UNION
            SELECT t.`id`, t.`subject`, t.`started_by`, p.`date` AS updated, p.`post`,
                'post' AS type
            FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p
            WHERE p.`thread` = t.`id`
            AND `post` LIKE '%$mysqlSearch%'
            AND `date` >= '$start'
            AND `date` <= '$end'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        echo '
            <div class="search_result">
                <p>'.T_('Could not find anything matching your search.').'</p>
            </div>';
        
    }

    $threadsFound = array();

    while ($r = mysql_fetch_assoc($result))
    {
        // if the search is found both in the subject and post
        // skip the post, so we don't show doubles
        if ($r['type'] == 'post')
        {
            if (isset($threadsFound[$r['id']]))
            {
                continue;
            }
        }

        $threadsFound[$r['id']] = 1;

        // Remove #ANNOUNCE#
        $subject = $msgBoardObj->fixSubject($r['subject']);
        // Clean html
        $subject = cleanOutput($subject, 'html');
        // Put in our html (should be the only html rendered)
        $subject = str_ireplace($search, '<b>'.$search.'</b>', $subject);

        // Remove orig bbcode
        $post = removeBBCode($r['post']);
        // Clean html
        $post = cleanOutput($post, 'html');
        // Put in our html (should be the only html rendered)
        $post = str_ireplace($search, '<b>'.$search.'</b>', $post);

        $date = fixDate('n/d/Y g:ia', $msgBoardObj->tzOffset, $r['updated']);

        echo '
            <div class="search_result">
                <a href="?thread='.$r['id'].'">'.$subject.'</a>
                <p>'.$post.'</p>
                <span>'.$date.'</span>
            </div>';
    }

    displayFooter();
}
