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

require 'fcms.php';

load('datetime', 'messageboard');

init();

// Setup some globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$currentAccess = checkAccess($currentUserId);
$msgBoardObj   = new MessageBoard($currentUserId);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Message Board'),
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
    //elseif (isset($_POST['advanced-search']))
    //{
    //    displayAdvancedSearchSubmit();
    //}
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
    global $currentUserId, $TMPL;

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

    include getTheme($currentUserId).'header.php';

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
    global $currentUserId, $TMPL;

    echo '
        </div><!-- #messageboard .centercontent -->';

    include getTheme($currentUserId).'footer.php';
}

/**
 * displayThreads 
 * 
 * @return void
 */
function displayThreads ()
{
    global $msgBoardObj;

    $page = isset($_GET['page']) ? cleanInput($_GET['page'], 'int') : 1;

    displayHeader();

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

    $thread_id = cleanInput($_GET['thread'], 'int');
    $page      = isset($_GET['page']) ? cleanInput($_GET['page'], 'int') : 1;

    $msgBoardObj->showPosts($thread_id, $page);

    displayFooter();
}

/**
 * displayNewThreadSubmit 
 * 
 * @return void
 */
function displayNewThreadSubmit ()
{
    global $currentUserId, $TMPL, $msgBoardObj;

    $post    = cleanInput($_POST['post']);
    $subject = cleanInput($_POST['subject']);

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
                '$currentUserId', 
                NOW(), 
                '$currentUserId'
            )";
    if (!mysql_query($sql))
    {
        displaySQLError('Thread Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $new_thread_id = mysql_insert_id();

    // Create new post
    $sql = "INSERT INTO `fcms_board_posts`(`date`, `thread`, `user`, `post`) 
            VALUES (
                NOW(), 
                '$new_thread_id', 
                '$currentUserId', 
                '$post'
            )";
    if (!mysql_query($sql))
    {
        displaySQLError('Post Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
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
        displaySQLError('Email Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
    }

    if (mysql_num_rows($result) > 0)
    {
        while ($r = mysql_fetch_array($result))
        {
            $name = getUserDisplayName($_SESSION['login_id']);
            $to   = getUserDisplayName($r['user']);
            $pos  = strpos($subject, '#ANOUNCE#'); 

            if ($pos !== false)
            {
                $subject = substr($subject, 9, strlen($subject)-9);
            } 

            $threadSubject = $subject;
            $emailSubject  = sprintf(T_('%s started the new thread: %s'), $name, $threadSubject);
            $email         = $r['email'];
            $url           = getDomainAndDir();
            $email_headers = getEmailHeaders();

            $msg = T_('Dear').' '.$to.',

'.$emailSubject.'

'.$url.'messageboard.php?thread='.$new_thread_id.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';

            mail($email, $subject, $msg, $email_headers);
        }
    }

    // Display the new thread
    $msgBoardObj->showPosts($new_thread_id, 1);
    displayFooter();
}

/**
 * displayNewPostSubmit 
 * 
 * @return void
 */
function displayNewPostSubmit ()
{
    global $currentUserId, $TMPL, $msgBoardObj;

    displayHeader();

    $post      = cleanInput($_POST['post']);
    $thread_id = cleanInput($_POST['thread_id'], 'int');

    // Update Thread info
    $sql = "UPDATE `fcms_board_threads` 
            SET `updated` = NOW(), `updated_by` = '$currentUserId' 
            WHERE `id` = $thread_id";
    if (!mysql_query($sql))
    {
        displaySQLError('Thread Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }

    // Insert new Post
    $sql = "INSERT INTO `fcms_board_posts` (`date`, `thread`, `user`, `post`) 
            VALUES (
                NOW(), 
                '$thread_id', 
                '$currentUserId', 
                '$post'
            )";
    if (!mysql_query($sql))
    {
        displaySQLError('Post Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
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
        displaySQLError('Email Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
    }

    if (mysql_num_rows($result) > 0)
    {
        while ($r = mysql_fetch_array($result))
        {
            $name = getUserDisplayName($_SESSION['login_id']);

            $sql = "SELECT `subject` 
                    FROM `fcms_board_threads` 
                    WHERE `id` = $thread_id";
            $subject_result = mysql_query($sql);
            if (!$subject_result)
            {
                displaySQLError('Subject Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return;
            }

            $row = mysql_fetch_array($subject_result);

            $thread_subject = $row['subject'];

            $pos = strpos($thread_subject, '#ANOUNCE#'); 
            if ($pos !== false)
            {
                $thread_subject = substr($thread_subject, 9, strlen($thread_subject)-9);
            } 

            $subject = sprintf(T_('%s has replied to the thread: %s'), $name, $thread_subject);
            $email   = $r['email'];
            $to      = getUserDisplayName($r['user']);
            $url     = getDomainAndDir();

            $msg = T_('Dear').' '.$to.',
'.$subject.'

'.$url.'messageboard.php?thread='.$thread_id.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
            $email_headers = getEmailHeaders();
            mail($email, $subject, $msg, $email_headers);
        }
    }

    $msgBoardObj->showPosts($thread_id, 1);
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
    global $currentUserId, $TMPL, $currentAccess, $msgBoardObj;

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
    elseif ($_GET['reply'] > 0)
    {
        $reply = cleanInput($_GET['reply'], 'int');

        if (isset($_POST['quotepost']))
        {
            $id = cleanInput($_POST['id'], 'int');
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

    $id = cleanInput($_POST['id'], 'int');

    $sql = "SELECT `post`, `thread`
            FROM `fcms_board_posts` 
            WHERE `id` = $id
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Get Post Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
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

    $id        = cleanInput($_POST['id'], 'int');
    $thread_id = cleanInput($_POST['thread_id'], 'int');
    $post      = cleanInput($_POST['post']);

    displayHeader();

    // TODO
    // Need to find a better way to add the edited by text
    // this method could mess up if the site changes languages at some point
    $pos = strpos($post, "[size=small][i]".T_('Edited'));
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
        displaySQLError('Edit Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $msgBoardObj->showPosts($thread_id, 1);
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

    $thread = cleanInput($_POST['thread'], 'int');

    displayHeader();

    if (isset($_POST['sticky']))
    {
        $subject = "#ANOUNCE#".cleanInput($_POST['subject']);
    }
    else
    {
        $subject = cleanInput($_POST['subject']);
    }

    $sql = "UPDATE `fcms_board_threads` 
            SET `subject` = '$subject' 
            WHERE `id` = '$thread'";
    if (!mysql_query($sql))
    {
        displaySQLError('Edit Thread Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayOkMessage();

    $msgBoardObj->showPosts($thread, 1);

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
    $thread = cleanInput($_POST['thread'], 'int');
    $id     = cleanInput($_POST['id'], 'int');

    displayHeader();

    echo '
                <div class="info-alert clearfix">
                    <form action="messageboard.php?thread='.$thread.'" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.$id.'"/>
                            <input type="hidden" name="thread" value="'.$thread.'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="messageboard.php?thread='.$thread.'">'.T_('Cancel').'</a>
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

    $id     = cleanInput($_POST['id'], 'int');
    $thread = cleanInput($_POST['thread'], 'int');

    displayHeader();

    // Get id of last post in the current thread
    $sql = "SELECT MAX(`id`) AS max 
            FROM `fcms_board_posts` 
            WHERE `thread` = '$thread'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Last Post Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $r   = mysql_fetch_array($result);
    $max = $r['max'];

    // Get total post count for this thread
    $sql = "SELECT `id` 
            FROM `fcms_board_posts` 
            WHERE `thread` = '$thread'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Post Count Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $total = mysql_num_rows($result);

    // If this thread only has one post
    if ($total == 1)
    {
        // Delete the entire thread
        $sql = "DELETE FROM `fcms_board_threads` 
                WHERE `id` = '$thread'";
        if (!mysql_query($sql))
        {
            displaySQLError('Delete Thread Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        displayOkMessage();

        $msgBoardObj->showThreads('announcement');
        $msgBoardObj->showThreads('thread', 1);

        displayFooter();
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
            displaySQLError('Delete Post Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        // Get new last post in the thread
        $sql = "SELECT MAX(`id`) AS max 
                FROM `fcms_board_posts` 
                WHERE `thread` = '$thread'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Last Post Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
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
            displaySQLError('Post Info Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        $r = mysql_fetch_array($result);

        // Update the thread with last post info
        $sql = "UPDATE `fcms_board_threads` 
                SET `updated` = '".$r['date']."', `updated_by` = ".$r['user']." 
                WHERE `id` = '$thread'";
        mysql_query($sql) or displaySQLError(
            'Update Thread Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );

        displayOkMessage();

        $msgBoardObj->showPosts($thread, 1);

        displayFooter();
        return;
    }
    // We are deleting a post in the middle of the thread
    else
    {
        $sql = "DELETE FROM `fcms_board_posts`  
                WHERE `id` = '$id'";
        if (!mysql_query($sql))
        {
            displaySQLError('Delete Post Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }
   
        displayOkMessage();
 
        $msgBoardObj->showPosts($thread, 1);

        displayFooter();
        return;
    }
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

    $thread = cleanInput($_POST['thread'], 'int');

    displayHeader();

    // Did they submit a blank form?
    if (empty($_POST['admin_option']))
    {
        $msgBoardObj->showPosts($thread, 1);
        displayFooter();
        return;
    }

    // Changing Thread type
    if ($_POST['admin_option'] == 'normal' || $_POST['admin_option'] == 'announcement')
    {
        $sql = "SELECT `subject`
                FROM `fcms_board_threads`
                WHERE `id` = '$thread'
                LIMIT 1";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Thread Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        if (mysql_num_rows($result) < 1)
        {
            echo '
            <p class="error-alert">'.T_('Thread does not exist.').'</p>';

            displayFooter();
            return;
        }

        $row = mysql_fetch_array($result);

        // Normal Thread
        if ($_POST['admin_option'] == 'normal')
        {
            $subject = $msgBoardObj->fixSubject($row['subject']);
        }
        // Announcement
        else
        {
            $subject = '#ANOUNCE#'.$row['subject'];
        }


        $sql = "UPDATE `fcms_board_threads` 
                SET `subject` = '".cleanInput($subject)."' 
                WHERE `id` = '$thread'";
        if (!mysql_query($sql))
        {
            displaySQLError('Update Thread Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        displayOkMessage();

        $msgBoardObj->showPosts($thread, 1);
        displayFooter();
        return;
    } 

    // Edit Thread Subject
    if ($_POST['admin_option'] == 'subject')
    {
        $msgBoardObj->displayAdminEditSubjectForm($thread);
        return;
    }

    // Delete thread
    if ($_POST['admin_option'] == 'delete')
    {
        $sql = "DELETE FROM `fcms_board_posts` 
                WHERE `thread` = '$thread'";
        if (!mysql_query($sql))
        {
            displaySQLError('Delete Posts Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        $sql = "DELETE FROM `fcms_board_threads` 
                WHERE `id` = '$thread'";
        if (!mysql_query($sql))
        {
            displaySQLError('Thread Thread Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        displayOkMessage();

        $msgBoardObj->showThreads('announcement');
        $msgBoardObj->showThreads('thread', 1);

        displayFooter();

        return;
    }
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

    $advanced = false;

    // validate start date
    if (isset($_POST['start']))
    {
        $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $_POST['start']);
        if ($found === false || $found < 1)
        {
            $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($_POST['start']));
            displayAdvancedSearchForm($error);
            return;
        }

        $advanced = true;
        $start    = $_POST['start'];
    }

    // validate end date
    if (isset($_POST['end']))
    {
        $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $_POST['end']);
        if ($found === false || $found < 1)
        {
            $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($_POST['end']));
            displayAdvancedSearchForm($error);
            return;
        }

        $advanced = true;
        $end      = $_POST['end'];
    }

    displayHeader();

    echo '
            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a href="messageboard.php">'.T_('Message Board Home').'</a></li>
                </ul>
            </div>
            <form method="post" action="messageboard.php">
                <p id="big_search">
                    <input type="text" id="search" name="search" value="'.cleanOutput($_POST['search']).'"/>
                    <input type="submit" value="'.T_('Search').'"/><br/>
                    <a href="?search=advanced">'.T_('Advanced Search').'</a>
                </p>
            </form>';

    $search = cleanInput($_POST['search']);

    // Thread subject
    $sql = "SELECT t.`id`, t.`subject`, t.`started_by`, p.`date`, p.`post`
            FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t
            WHERE p.`thread` = t.`id`
            AND `subject` LIKE '%$search%'";
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
            AND `post` LIKE '%$search%'";
    if ($advanced)
    {
        $sql .= "
            AND p.`date` >= '$start'
            AND p.`date` <= '$end'";
    }

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
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
        $subject = $msgBoardObj->fixSubject($r['subject']);
        $subject = str_ireplace($search, '<b>'.$search.'</b>', $subject);

        $post = removeBBCode($r['post']);
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
<link rel="stylesheet" type="text/css" href="themes/datechooser.css"/>
<script type="text/javascript" src="inc/js/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    var dc1 = new DateChooser();
    dc1.setUpdateField({\'start\':\'Y-m-d\'});
    dc1.setIcon(\'themes/default/images/datepicker.jpg\', \'start\');
    var dc2 = new DateChooser();
    dc2.setUpdateField({\'end\':\'Y-m-d\'});
    dc2.setIcon(\'themes/default/images/datepicker.jpg\', \'end\');
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
            <div id="sections_menu" class="clearfix">
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

    // validate dates
    $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $_POST['start']);
    if ($found === false || $found < 1)
    {
        $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($_POST['start']));
        displayAdvancedSearchForm($error);
        return;
    }
    $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $_POST['end']);
    if ($found === false || $found < 1)
    {
        $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($_POST['end']));
        displayAdvancedSearchForm($error);
        return;
    }

    $start = $_POST['start'];
    $end   = $_POST['end'];

    displayHeader();

    echo '
            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a href="messageboard.php">'.T_('Message Board Home').'</a></li>
                </ul>
            </div>
            <form method="post" action="messageboard.php">
                <p id="big_search">
                    <input type="text" id="search" name="search" value="'.cleanOutput($_POST['advanced-search']).'"/>
                    <input type="submit" value="'.T_('Search').'"/><br/>
                    <a href="?search=advanced">'.T_('Advanced Search').'</a>
                </p>
            </form>';

    $search = cleanInput($_POST['advanced-search']);

    $sql = "SELECT t.`id`, t.`subject`, t.`started_by`, t.`updated`, p.`post`,
                'thread' AS type
            FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p
            WHERE p.`thread` = t.`id`
            AND `subject` LIKE '%$search%'
            UNION
            SELECT t.`id`, t.`subject`, t.`started_by`, p.`date` AS updated, p.`post`,
                'post' AS type
            FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p
            WHERE p.`thread` = t.`id`
            AND `post` LIKE '%$search%'
            AND `date` >= '$start'
            AND `date` <= '$end'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Search Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
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

        $subject = $msgBoardObj->fixSubject($r['subject']);
        $subject = str_ireplace($search, '<b>'.$search.'</b>', $subject);

        $post = removeBBCode($r['post']);
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
