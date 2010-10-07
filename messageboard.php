<?php
session_start();

include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/locale.php');
include_once('inc/messageboard_class.php');

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn();
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$locale = new Locale();
$mboard = new MessageBoard($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Message Board'),
    'path'          => "",
    'admin_path'    => "admin/",
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
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

// Show Header
include_once(getTheme($_SESSION['login_id']) . 'header.php');

echo '
        <div id="messageboard" class="centercontent">';

$show_threads = true;

//------------------------------------------------------------------------------
// Add thread
//------------------------------------------------------------------------------
if (isset($_POST['post_submit'])) {

    $show_threads = false;

    $subject = cleanInput($_POST['subject']);
    if (isset($_POST['sticky'])) {
        $subject = "#ANOUNCE#" . $subject;
    }
    $post = cleanInput($_POST['post']);

    // Create new thread
    $sql = "INSERT INTO `fcms_board_threads` 
                (`subject`, `started_by`, `updated`, `updated_by`) 
            VALUES (
                '$subject', 
                '$currentUserId', 
                NOW(), 
                '$currentUserId'
            )";
    mysql_query($sql) or displaySQLError(
        'Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $new_thread_id = mysql_insert_id();

    // Create new post
    $sql = "INSERT INTO `fcms_board_posts`(`date`, `thread`, `user`, `post`) 
            VALUES (
                NOW(), 
                '$new_thread_id', 
                '$currentUserId', 
                '$post'
            )";
    mysql_query($sql) or displaySQLError(
        'Post Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );

    // Email members
    $sql = "SELECT u.`email`, s.`user` 
            FROM `fcms_user_settings` AS s, `fcms_users` AS u 
            WHERE `email_updates` = '1'
            AND u.`id` = s.`user`";
    $result = mysql_query($sql) or displaySQLError(
        'Email Updates Error', __FILE__ . ' [' . __LINE__ . ']', 
        $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        while ($r = mysql_fetch_array($result)) {
            $name = getUserDisplayName($_SESSION['login_id']);
            $to = getUserDisplayName($r['user']);
            $pos = strpos($subject, '#ANOUNCE#'); 
            if ($pos !== false) {
                $subject = substr($subject, 9, strlen($subject)-9);
            } 
            $threadSubject = $subject;
            $emailSubject = sprintf(T_('%s started the new thread: %s'), $name, $threadSubject);
            $email = $r['email'];
            $url = getDomainAndDir();
            $msg = T_('Dear').' '.$to.',

'.$emailSubject.'

'.$url.'messageboard.php?thread='.$new_thread_id.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
            $email_headers = getEmailHeaders();
            mail($email, $subject, $msg, $email_headers);
        }
    }

    // Display the new thread
    $mboard->showPosts($new_thread_id, 1);
}

//------------------------------------------------------------------------------
// Add Post/Reply
//------------------------------------------------------------------------------
if (isset($_POST['reply_submit'])) {

    $show_threads = false;

    $post = cleanInput($_POST['post']);
    $thread_id = cleanInput($_POST['thread_id'], 'int');

    // Update Thread info
    $sql = "UPDATE `fcms_board_threads` 
            SET `updated` = NOW(), `updated_by` = '$currentUserId' 
            WHERE `id` = $thread_id";
    mysql_query($sql) or displaySQLError(
        'Update Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );

    // Insert new Post
    $sql = "INSERT INTO `fcms_board_posts` (`date`, `thread`, `user`, `post`) 
            VALUES (
                NOW(), 
                '$thread_id', 
                '$currentUserId', 
                '$post'
            )";
    mysql_query($sql) or displaySQLError(
        'Reply Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );

    // Email members
    $sql = "SELECT u.`email`, s.`user` "
         . "FROM `fcms_user_settings` AS s, `fcms_users` AS u "
         . "WHERE `email_updates` = '1'"
         . "AND u.`id` = s.`user`";
    $result = mysql_query($sql) or displaySQLError(
        'Email Updates Error', __FILE__ . ' [' . __LINE__ . ']', 
        $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        while ($r = mysql_fetch_array($result)) {
            $name = getUserDisplayName($_SESSION['login_id']);
            $sql = "SELECT `subject` FROM `fcms_board_threads` WHERE `id` = $thread_id";
            $subject = mysql_query($sql) or displaySQLError(
                'Subject Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $row = mysql_fetch_array($subject);
            $thread_subject = $row['subject'];
            $pos = strpos($thread_subject, '#ANOUNCE#'); 
            if ($pos !== false) { 
                $thread_subject = substr($thread_subject, 9, strlen($thread_subject)-9);
            } 
            $subject = sprintf(T_('%s has replied to the thread: %s'), $name, $thread_subject);
            $email = $r['email'];
            $to = getUserDisplayName($r['user']);
            $url = getDomainAndDir();
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

    // Display the thread
    $mboard->showPosts($thread_id, 1);
}

//------------------------------------------------------------------------------
// Edit post
//------------------------------------------------------------------------------
if (isset($_POST['edit_submit'])) {

    $show_threads = false;

    $id         = cleanInput($_POST['id'], 'int');
    $thread_id  = cleanInput($_POST['thread_id'], 'int');
    $post       = cleanInput($_POST['post']);

    // TODO
    // Need to find a better way to add the edited by text
    // this method could mess up if the site changes languages at some point
    $pos = strpos($post, "[size=small][i]".T_('Edited'));
    if($pos === false) {
        $post = $post . "\n\n[size=small][i]".T_('Edited')." " . $locale->fixDate('n/d/Y g:ia', $mboard->tz_offset) . "[/i][/size]";
    } else {
        $post = substr($post, 0, $pos);
        $post = $post . "[size=small][i]".T_('Edited')." " . $locale->fixDate('n/d/Y g:ia', $mboard->tz_offset) . "[/i][/size]";
    }

    // Update Post
    $sql = "UPDATE `fcms_board_posts` 
            SET `post` = '$post' 
            WHERE `id` = '$id'";
    mysql_query($sql) or displaySQLError(
        'Edit Post Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );

    // Display the thread
    $mboard->showPosts($thread_id, 1);
}

//------------------------------------------------------------------------------
// Admin Edit Subject
//------------------------------------------------------------------------------
if (isset($_POST['edit_admin_submit'])) {

    $show_threads = false;

    $thread     = cleanInput($_POST['thread'], 'int');
    if (isset($_POST['sticky'])) {
        $subject = "#ANOUNCE#" . cleanInput($_POST['subject']);
    } else {
        $subject = cleanInput($_POST['subject']);
    }

    $sql = "UPDATE `fcms_board_threads` 
            SET `subject` = '$subject' 
            WHERE `id` = '$thread'";
    mysql_query($sql) or displaySQLError(
        'Edit Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );

    // Display the thread
    $mboard->showPosts($thread, 1);
}

//------------------------------------------------------------------------------
// Delete post confirmation
//------------------------------------------------------------------------------
if (isset($_POST['delpost']) && !isset($_POST['confirmed'])) {

    $show_threads = false;

    $thread = cleanInput($_POST['thread'], 'int');
    $id     = cleanInput($_POST['id'], 'int');

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

//------------------------------------------------------------------------------
// Delete post
//------------------------------------------------------------------------------
} elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {

    $show_threads = false;

    $id     = cleanInput($_POST['id'], 'int');
    $thread = cleanInput($_POST['thread'], 'int');

    // Get id of last post in the current thread
    $sql = "SELECT MAX(`id`) AS max 
            FROM `fcms_board_posts` 
            WHERE `thread` = '$thread'";
    $result = mysql_query($sql) or displaySQLError(
        'Last Post Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $max = $found['max'];
    mysql_free_result($result);

    // Get total post count for this thread
    $sql = "SELECT `id` 
            FROM `fcms_board_posts` 
            WHERE `thread` = '$thread'";
    $result = mysql_query($sql) or displaySQLError(
        'Post Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $total = mysql_num_rows($result);
    mysql_free_result($result);

    // If this thread only has one post
    if ($total < 2) {

        // Delete the entire thread
        $sql = "DELETE FROM `fcms_board_threads` 
                WHERE `id` = '$thread'";
        mysql_query($sql) or displaySQLError(
            'Delete Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        // No thread to show, default back to main thread list view
        $show_threads = true;

    // If we are deleting the last post in the thread
    } elseif ($id == $max) {

        // Delete post
        $sql = "DELETE FROM `fcms_board_posts`  
                WHERE `id` = '$id'";
        mysql_query($sql) or displaySQLError(
            'Delete Post Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        // Get new last post in the thread
        $sql = "SELECT MAX(`id`) AS max 
                FROM `fcms_board_posts` 
                WHERE `thread` = '$thread'";
        $result = mysql_query($sql) or displaySQLError(
            'Last Post Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $found = mysql_fetch_array($result);
        $newmax = $found['max'];
        mysql_free_result($result);

        // Get info from new last post
        $sql = "SELECT `date`, `user` 
                FROM `fcms_board_posts` 
                WHERE `id` = '$newmax'";
        $result = mysql_query($sql) or displaySQLError(
            'Last Post Info Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);

        // Update the thread with last post info
        $sql = "UPDATE `fcms_board_threads` 
                SET `updated` = '" . $r['date'] . "', `updated_by` = " . $r['user'] . " 
                WHERE `id` = '$thread'";
        mysql_query($sql) or displaySQLError(
            'Update Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        // Display the thread
        $mboard->showPosts($thread, 1);

    // If we are deleting a post in the middle of the thread
    } else {
        $sql = "DELETE FROM `fcms_board_posts`  
                WHERE `id` = '$id'";
        mysql_query($sql) or displaySQLError(
            'Delete Post Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    
        // Display the thread
        $mboard->showPosts($thread, 1);
    }
}

//------------------------------------------------------------------------------
// Display Edit Post Form
//------------------------------------------------------------------------------
if (isset($_POST['editpost'])) {
    $id = $_POST['id'];
    if (ctype_digit($id)) {
        $show_threads = false;
        $sql = "SELECT * FROM `fcms_board_posts` WHERE `id` = $id";
        $result = mysql_query($sql) or displaySQLError('Get Post Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
        while($r=mysql_fetch_array($result)) {
            $post = $r['post'];
            $thread_id = $r['thread'];
        }
        $mboard->displayForm('edit', $thread_id, $id, $post);
    }
}

//------------------------------------------------------------------------------
// Display Thread
//------------------------------------------------------------------------------
if (isset($_GET['thread'])) {

    $show_threads = false;
    $thread_id = cleanInput($_GET['thread'], 'int');
    $page = isset($_GET['page']) ? cleanInput($_GET['page'], 'int') : 1;
    $mboard->showPosts($thread_id, $page);
}

//------------------------------------------------------------------------------
// Administrate Thread
//------------------------------------------------------------------------------
if (isset($_POST['submit_admin']) && checkAccess($currentUserId) < 2) {

    $thread = cleanInput($_POST['thread'], 'int');

    // Make Normal Thread
    if ($_POST['admin_option'] == 'normal') {
        $sql = "SELECT `subject`
                FROM `fcms_board_threads`
                WHERE `id` = '$thread'
                LIMIT 1";
        $result = mysql_query($sql) or displaySQLError(
            'Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $subject = $row['subject'];
            $pos = strpos($subject, '#ANOUNCE#');
            if ($pos !== false) {
                $subject = substr($subject, 9, strlen($subject)-9);
            }
            $sql = "UPDATE `fcms_board_threads` 
                    SET `subject` = '".cleanInput($subject)."' 
                    WHERE `id` = '$thread'";
            mysql_query($sql) or displaySQLError(
                'Update Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
    } 

    // Make Thread Announcement
    if ($_POST['admin_option'] == 'announcement') {
        $sql = "SELECT `subject`
                FROM `fcms_board_threads`
                WHERE `id` = '$thread'
                LIMIT 1";
        $result = mysql_query($sql) or displaySQLError(
            'Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $subject = '#ANOUNCE#'.$row['subject'];
            $sql = "UPDATE `fcms_board_threads` 
                    SET `subject` = '".cleanInput($subject)."' 
                    WHERE `id` = '$thread'";
            mysql_query($sql) or displaySQLError(
                'Update Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
    } 

    // Edit Thread Subject
    if ($_POST['admin_option'] == 'subject') {
        $show_threads = false;
        $mboard->displayAdminEditSubjectForm($thread);
    }
}

//------------------------------------------------------------------------------
// Display reply form
//------------------------------------------------------------------------------
if (isset($_GET['reply'])) {
    if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
        $show_threads = false;
        if ($_GET['reply'] == 'new') {
            $mboard->displayForm('new');
        } elseif ($_GET['reply'] > 0) {
            $reply = cleanInput($_GET['reply'], 'int');
            if (isset($_POST['quotepost'])) {
                $id = cleanInput($_POST['id'], 'int');
                $mboard->displayForm('reply', $reply, $id);
            } else {
                $mboard->displayForm('reply', $reply);
            }
        }
    }
}

//------------------------------------------------------------------------------
// Show list of threads
//------------------------------------------------------------------------------
if ($show_threads) {
    $page = isset($_GET['page']) ? cleanInput($_GET['page'], 'int') : 1;
    $mboard->showThreads('announcement');
    $mboard->showThreads('thread', $page);
}

echo '
        </div><!-- #messageboard .centercontent -->';

// Show Footer
include_once(getTheme($currentUserId) . 'footer.php');
