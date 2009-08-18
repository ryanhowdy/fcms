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
include_once('inc/language.php');
if (isset($_SESSION['login_id'])) {
	if (!isLoggedIn($_SESSION['login_id'], $_SESSION['login_uname'], $_SESSION['login_pw'])) {
		displayLoginPage();
		exit();
	}
} elseif (isset($_COOKIE['fcms_login_id'])) {
	if (isLoggedIn($_COOKIE['fcms_login_id'], $_COOKIE['fcms_login_uname'], $_COOKIE['fcms_login_pw'])) {
		$_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
		$_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
		$_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
	} else {
		displayLoginPage();
		exit();
	}
} else {
	displayLoginPage();
	exit();
}
header("Cache-control: private");
include_once('inc/messageboard_class.php');
$mboard = new MessageBoard($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['link_board'];
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
include_once(getTheme($_SESSION['login_id']) . 'header.php');
?>
	<div id="leftcolumn">
        <?php
        include_once(getTheme($_SESSION['login_id']) . 'sidenav.php');
        if (checkAccess($_SESSION['login_id']) < 3) {
            include_once(getTheme($_SESSION['login_id']) . 'adminnav.php');
        }
        ?>
	</div>
	<div id="content">
		<div id="messageboard" class="centercontent">
			<?php
			$show_threads = true;
			if (isset($_POST['post_submit'])) {
				$show_threads = false;
				$subject = addslashes($_POST['subject']);
                $userid = $_SESSION['login_id'];
				if (empty($subject)) { $subject = "subject"; }
				if (isset($_POST['sticky'])) { $subject = "#ANOUNCE#" . $subject; }
				if (isset($_POST['username'])) { $username = $_POST['username']; }
				$post = addslashes($_POST['post']);
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
                        $name = getUserDisplayName($_SESSION['login_id']);
                        $thread_subject = $subject;
                        $subject = "$name " . $LANG['started_thread'] . " $thread_subject";
                        $email = $r['email'];
                        $name = getUserDisplayName($r['user']);
                        $url = getDomainAndDir();
                        $msg = $LANG['dear'] . " $name,
$name " . $LANG['started_thread'] . " $thread_subject

{$url}messageboard.php?thread=$new_thread_id

----
" . $LANG['opt_out_updates'] . "

{$url}settings.php

";
                        mail($email, $subject, $msg, $email_headers);
                    }
                }
			}
			if (isset($_POST['reply_submit'])) {
				$show_threads = false;
				$post = addslashes($_POST['post']);
				$thread_id = $_POST['thread_id'];
				$sql = "UPDATE `fcms_board_threads` "
                     . "SET `updated` = NOW(), `updated_by` = " . $_SESSION['login_id'] . " "
                     . "WHERE `id` = $thread_id";
				mysql_query($sql) or displaySQLError(
                    'Update Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
				$sql = "INSERT INTO `fcms_board_posts`(`date`, `thread`, `user`, `post`) "
                     . "VALUES (NOW(), $thread_id, " . $_SESSION['login_id'] . ", '$post')";
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
                        $name = getUserDisplayName($_SESSION['login_id']);
                        $sql = "SELECT `subject` FROM `fcms_board_threads` WHERE `id` = $thread_id";
                        $subject = mysql_query($sql) or displaySQLError(
                            'Subject Error', __FILE__ . ' [' . __LINE__ . ']', 
                            $sql, mysql_error()
                        );
                        $row = mysql_fetch_array($subject);
                        $thread_subject = $row['subject'];
                        $subject = "$name " . $LANG['replied_to'] . " $thread_subject";
                        $email = $r['email'];
                        $name = getUserDisplayName($r['user']);
                        $url = getDomainAndDir();
                        $msg = $LANG['dear'] . " $name,
$name " . $LANG['replied_to'] . " $thread_subject

{$url}messageboard.php?thread=$thread_id

----
" . $LANG['opt_out_updates'] . "

{$url}settings.php

";
                        mail($email, $subject, $msg, $email_headers);
                    }
                }
			}
			if (isset($_POST['edit_submit'])) {
				$show_threads = false;
				$id = $_POST['id'];
				$thread_id = $_POST['thread_id'];
				$post = addslashes($_POST['post']);
				$pos = strpos($post, "[size=small][i]".$LANG['edited']);
				if($pos === false) {
					$post = $post . "\n\n[size=small][i]".$LANG['edited']." " . fixDST(gmdate('n/d/Y g:ia', strtotime($mboard->tz_offset)), $_SESSION['login_id'], 'n/d/Y g:ia') . "[/i][/size]";
				} else {
					$post = substr($post, 0, $pos);
					$post = $post . "[size=small][i]".$LANG['edited']." " . fixDST(gmdate('n/d/Y g:ia', strtotime($mboard->tz_offset)), $_SESSION['login_id'], 'n/d/Y g:ia') . "[/i][/size]";
				}
				$sql = "UPDATE `fcms_board_posts` SET `post` = '$post' WHERE `id` = $id";
				mysql_query($sql) or displaySQLError('Edit Post Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<meta http-equiv='refresh' content='0;URL=messageboard.php?thread=" . $thread_id . "'>";
			}
			if (isset($_POST['delpost'])) {
				$show_threads = false;
				$id = $_POST['id'];
				$thread = $_POST['thread'];
				$sql = "SELECT MAX(`id`) AS max FROM `fcms_board_posts` WHERE `thread` = $thread";
				$result = mysql_query($sql) or displaySQLError('Last Thread Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
				$found = mysql_fetch_array($result);
				$max = $found['max'];
				mysql_free_result($result);
				$sql = "SELECT * FROM `fcms_board_posts` WHERE `thread` = $thread";
				$result = mysql_query($sql) or displaySQLError('Post Count Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
				$total = mysql_num_rows($result);
				mysql_free_result($result);
				if($total < 2) {
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
			if (isset($_POST['editpost'])) {
				$show_threads = false;
				$id = $_POST['id'];
				$sql = "SELECT * FROM `fcms_board_posts` WHERE `id` = $id";
				$result = mysql_query($sql) or displaySQLError('Get Post Error', 'messageboard.php [' . __LINE__ . ']', $sql, mysql_error());
				while($r=mysql_fetch_array($result)) {
					$post = $r['post'];
					$thread_id = $r['thread'];
				}
				$mboard->displayForm('edit', $thread_id, $id, $post);
			}
			if (isset($_GET['thread'])) {
				$show_threads = false;
				$page = 1;
				if (isset($_GET['page'])) { $page = $_GET['page']; }
				$thread_id = $_GET['thread'];
				$mboard->showPosts($thread_id, $page);
			}
			if (isset($_GET['reply'])) {
				if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
					$show_threads = false;
					if ($_GET['reply'] == 'new') {
						$mboard->displayForm('new');
					} elseif ($_GET['reply'] > 0) {
						if (isset($_POST['quote'])) { $mboard->displayForm('reply', $_GET['reply'], 0, $_POST['quote']); } else { $mboard->displayForm('reply', $_GET['reply']); }
					}
				}
			}
			if ($show_threads) {
				$page = 1;
				if (isset($_GET['page'])) { $page = $_GET['page']; }
				$mboard->showThreads('announcement');
				$mboard->showThreads('thread', $page);
			} ?>
		</div><!-- #messageboard .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>