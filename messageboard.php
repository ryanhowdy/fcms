<?php
session_start();
if (get_magic_quotes_gpc()) {
    $_REQUEST = array_map('stripslashes', $_REQUEST);
    $_GET = array_map('stripslashes', $_GET);
    $_POST = array_map('stripslashes', $_POST);
    $_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/locale.php');
$locale = new Locale();

// Check that the user is logged in
isLoggedIn();
$current_user_id = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");
include_once('inc/messageboard_class.php');
$mboard = new MessageBoard($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL['pagetitle'] = T_('Message Board');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
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
include_once(getTheme($current_user_id) . 'header.php');

echo '
        <div id="messageboard" class="centercontent">';
$show_threads = true;

// Add thread
if (isset($_POST['post_submit'])) {
    $show_threads = false;
    $subject = escape_string($_POST['subject']);
    $userid = $current_user_id;
    if (empty($subject)) { $subject = "subject"; }
    if (isset($_POST['sticky'])) { $subject = "#ANOUNCE#" . $subject; }
    if (isset($_POST['username'])) { $username = $_POST['username']; }
    $post = escape_string($_POST['post']);
    $sql = "INSERT INTO `fcms_board_threads` "
            . "(`subject`, `started_by`, `updated`, `updated_by`) "
         . "VALUES ('$subject', $userid, NOW(), $userid)";
    mysql_query($sql) or displaySQLError(
        'Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $new_thread_id = mysql_insert_id();
    $sql = "INSERT INTO `fcms_board_posts`(`date`, `thread`, `user`, `post`) "
         . "VALUES (NOW(), $new_thread_id, $userid, '$post')";
    mysql_query($sql) or displaySQLError(
        'Post Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    echo "<meta http-equiv='refresh' content='0;URL=messageboard.php?thread=$new_thread_id'>";
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
            $name = getUserDisplayName($current_user_id);
            $to = getUserDisplayName($r['user']);
            $pos = strpos($subject, '#ANOUNCE#'); 
            if ($pos !== false) {
                $subject = substr($subject, 9, strlen($subject)-9);
            } 
            $thread_subject = $subject;
            $subject = sprintf(T_('%s started the new thread: %s'), $name, $thread_subject);
            $email = $r['email'];
            $url = getDomainAndDir();
            $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'messageboard.php?thread='.$new_thread_id.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
            mail($email, $subject, $msg, $email_headers);
        }
    }
}

// Add post
if (isset($_POST['reply_submit'])) {
    $post = escape_string($_POST['post']);
    $thread_id = escape_string($_POST['thread_id']);
    if (ctype_digit($thread_id)) {
        $show_threads = false;
        $sql = "UPDATE `fcms_board_threads` "
             . "SET `updated` = NOW(), `updated_by` = $current_user_id "
             . "WHERE `id` = $thread_id";
        mysql_query($sql) or displaySQLError(
            'Update Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "INSERT INTO `fcms_board_posts`(`date`, `thread`, `user`, `post`) "
             . "VALUES (NOW(), $thread_id, $current_user_id, '$post')";
        mysql_query($sql) or displaySQLError(
            'Reply Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        echo "<meta http-equiv='refresh' content='0;URL=messageboard.php?thread=$thread_id'>";
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
                $name = getUserDisplayName($current_user_id);
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
                mail($email, $subject, $msg, $email_headers);
            }
        }
    }
}

// Edit post
if (isset($_POST['edit_submit'])) {
    // TODO
    // Need to find a better way to add the edited by text
    // this method could mess up if the site changes languages at some point
    $id = escape_string($_POST['id']);
    $thread_id = $_POST['thread_id'];
    if (ctype_digit($id) && ctype_digit($thread_id)) {
        $show_threads = false;
        $post = escape_string($_POST['post']);
        $pos = strpos($post, "[size=small][i]".T_('Edited'));
        if($pos === false) {
            $post = $post . "\n\n[size=small][i]".T_('Edited')." " . $locale->fixDate('n/d/Y g:ia', $mboard->tz_offset) . "[/i][/size]";
        } else {
            $post = substr($post, 0, $pos);
            $post = $post . "[size=small][i]".T_('Edited')." " . $locale->fixDate('n/d/Y g:ia', $mboard->tz_offset) . "[/i][/size]";
        }
        $sql = "UPDATE `fcms_board_posts` SET `post` = '$post' WHERE `id` = $id";
        mysql_query($sql) or displaySQLError('Edit Post Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
        echo "<meta http-equiv='refresh' content='0;URL=messageboard.php?thread=" . $thread_id . "'>";
    }
}

// Delete post confirmation
if (isset($_POST['delpost']) && !isset($_POST['confirmed'])) {
    $thread = $_POST['thread'];
    if (ctype_digit($thread)) {
        $show_threads = false;
        echo '
                <div class="info-alert clearfix">
                    <form action="messageboard.php?thread='.$thread.'" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.$_POST['id'].'"/>
                            <input type="hidden" name="thread" value="'.$thread.'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="messageboard.php?thread='.$thread.'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';
    }

// Delete post
} elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
    $id = escape_string($_POST['id']);
    $thread = escape_string($_POST['thread']);
    if (ctype_digit($id) && ctype_digit($thread)) {
        $show_threads = false;
        $sql = "SELECT MAX(`id`) AS max FROM `fcms_board_posts` WHERE `thread` = $thread";
        $result = mysql_query($sql) or displaySQLError('Last Thread Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
        $found = mysql_fetch_array($result);
        $max = $found['max'];
        mysql_free_result($result);
        $sql = "SELECT * FROM `fcms_board_posts` WHERE `thread` = $thread";
        $result = mysql_query($sql) or displaySQLError('Post Count Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
        $total = mysql_num_rows($result);
        mysql_free_result($result);
        if ($total < 2) {
            $sql = "DELETE FROM `fcms_board_threads` WHERE `id` = $thread";
            mysql_query($sql) or displaySQLError('Delete Thread Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
            echo "<meta http-equiv='refresh' content='0;URL=messageboard.php'>";
        } elseif($id == $max) {
            $sql = "DELETE FROM `fcms_board_posts` WHERE `id` = $id";
            mysql_query($sql) or displaySQLError('Delete Post Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
            $sql = "SELECT MAX(`id`) AS max FROM `fcms_board_posts` WHERE `thread` = $thread";
            $result = mysql_query($sql) or displaySQLError('Last Post Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
            $found = mysql_fetch_array($result);
            $newmax = $found['max'];
            mysql_free_result($result);
            $sql = "SELECT `date`, `user` FROM `fcms_board_posts` WHERE `id` = $newmax";
            $result = mysql_query($sql) or displaySQLError('Last Post Info Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
            $e = mysql_fetch_array($result);
            $sql = "UPDATE `fcms_board_threads` SET `updated` = '" . $e['date'] . "', `updated_by` = " . $e['user'] . " WHERE `id` = $thread";
            mysql_query($sql) or displaySQLError('Update Thread Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
            echo "<meta http-equiv='refresh' content='0;URL=messageboard.php?thread=" . $thread . "'>";
        } else {
            $sql = "DELETE FROM fcms_board_posts WHERE id=$id";
            mysql_query($sql) or displaySQLError('Delete Post Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
            echo "<meta http-equiv='refresh' content='0;URL=messageboard.php?thread=" . $thread . "'>";
        }
    }
}
if (isset($_POST['editpost'])) {
    $id = escape_string($_POST['id']);
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
if (isset($_GET['thread'])) {
    $thread_id = $_GET['thread'];
    if (ctype_digit($thread_id)) {
        $show_threads = false;
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $mboard->showPosts($thread_id, $page);
    }
}
if (isset($_GET['reply'])) {
    if (checkAccess($current_user_id) < 8 && checkAccess($current_user_id) != 5) {
        $show_threads = false;
        if ($_GET['reply'] == 'new') {
            $mboard->displayForm('new');
        } elseif ($_GET['reply'] > 0) {
            if (isset($_POST['quotepost'])) {
                $mboard->displayForm('reply', $_GET['reply'], $_POST['id']);
            } else {
                $mboard->displayForm('reply', $_GET['reply']);
            }
        }
    }
}
if ($show_threads) {
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $mboard->showThreads('announcement');
    $mboard->showThreads('thread', $page);
}

echo '
        </div><!-- #messageboard .centercontent -->';

// Show Footer
include_once(getTheme($current_user_id) . 'footer.php');
