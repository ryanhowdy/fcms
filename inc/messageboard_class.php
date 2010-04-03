<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class MessageBoard
{

	var $db;
	var $db2;
	var $tz_offset;
	var $cur_user_id;

	function MessageBoard ($current_user_id, $type, $host, $database, $user, $pass)
    {
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
		$this->db2 = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id";
		$this->db->query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function showThreads ($type, $page = '0') {
		global $LANG;
		$from = (($page * 25) - 25);
		if ($type == 'announcement') {
			if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
				$this->displayMessageBoardMenu();
			}
			echo <<<HTML
            <table id="threadlist" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th class="images">&nbsp;</th>
                        <th class="subject">{$LANG['subject']}</th>
                        <th class="info">&nbsp;</th>
                        <th class="replies">{$LANG['replies']}</th>
                        <th class="views">{$LANG['views']}</th>
                        <th class="updated">{$LANG['last_updated']}</th>
                    </tr>
                </thead>
                <tbody>

HTML;
            $sql = "SELECT t.`id`, `subject`, `started_by`, 
                        `updated`, `updated_by`, `views`, `user` 
                    FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p 
                    WHERE t.`id` = p.`thread` 
                    AND `subject` LIKE '#ANOUNCE#%' 
                    GROUP BY t.`id` 
                    ORDER BY `updated` DESC";
            $this->db->query($sql) or displaySQLError(
                'Announcements Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
		} else {
            $sql = "SELECT t.`id`, `subject`, `started_by`, `updated`, 
                        `updated_by`, `views`, `user` 
                    FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p 
                    WHERE t.`id` = p.`thread` 
                    AND `subject` NOT LIKE '#ANOUNCE#%' 
                    GROUP BY t.`id` 
                    ORDER BY `updated` DESC 
                    LIMIT $from, 30";
            $this->db->query($sql) or displaySQLError(
                'Threads Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
		}
		$alt = 0;
		while ($row = $this->db->get_row()) {

            // Setup some vars
			$started_by = getUserDisplayName($row['started_by']);
			$updated_by = getUserDisplayName($row['updated_by']);
			$subject = $row['subject'];
            $subject_info = '';
            $tr_class = '';
			if ($type == 'announcement') {
                //remove #ANNOUNCE# from the subject
				$subject = substr($subject, 9, strlen($subject)-9);
                $subject_info = "<small><b>" . $LANG['announcement'] . ": </b></small>";
                $tr_class = 'announcement';
			} else {
                if ($alt % 2 !== 0) { $tr_class = 'alt'; }
			}
            // thread was updated today
			if (
                gmdate('n/d/Y', strtotime($row['updated'] . $this->tz_offset)) == 
                gmdate('n/d/Y', strtotime(date('Y-m-d H:i:s') . $this->tz_offset))
            ) {
                $img_class = 'today';
                if ($type == 'announcement') {
                    $img_class = 'announcement_' . $img_class;
                }
				$last_updated = $LANG['today_at'] . " "
                    . fixDST(gmdate('h:ia', strtotime($row['updated'] . $this->tz_offset)), $this->cur_user_id, 'h:ia')
                    . "<br/>" . $LANG['by'] . " <a class=\"u\" href=\"profile.php?member="
                    . $row['updated_by'] . "\">$updated_by</a>";
            // thread was updated yesterday
			} elseif (
                gmdate('n/d/Y', strtotime($row['updated'] . $this->tz_offset)) == 
                gmdate('n/d/Y', strtotime(date('n/d/Y', strtotime(date('Y-m-d H:i:s') . $this->tz_offset)) . "-24 hours"))
            ) {
                $img_class = 'yesterday';
                if ($type == 'announcement') {
                    $img_class = 'announcement_' . $img_class;
                }
				$last_updated = $LANG['yesterday_at'] . " "
                    . fixDST(gmdate('h:ia', strtotime($row['updated'] . $this->tz_offset)), $this->cur_user_id, 'h:ia')
                    . "<br/>" . $LANG['by'] . " <a class=\"u\" href=\"profile.php?member="
                    . $row['updated_by'] . "\">$updated_by</a>";
			} else {
                $img_class = '';
                if ($type == 'announcement') {
                    $img_class = 'announcement';
                }
				$last_updated = fixDST(
                    gmdate('m/d/Y h:ia', strtotime($row['updated'] . $this->tz_offset)), 
                    $this->cur_user_id, 'm/d/Y h:ia'
                );
                $last_updated .= "<br/>" . $LANG['by'] . " <a class=\"u\" href=\"profile.php?member="
                    . $row['updated_by'] . "\">$updated_by</a>";
            }
            // thread has multiple pages?
            $thread_pages = '';
			if ($this->getNumberOfPosts($row['id']) > 15) { 
				$num_posts = $this->getNumberOfPosts($row['id']);
				$thread_pages = "<span>" . $LANG['page'] . " ";
				$times2loop = ceil($num_posts/15);
				for ($i=1;$i<=$times2loop;$i++) {
                    $thread_pages .= "<a href=\"messageboard.php?thread=" . $row['id'] . "&amp;page=$i\">$i</a> ";
                }
				$thread_pages .= "</span><br/>";
            }
            if ($this->getNumberOfPosts($row['id']) > 20) {
                $info = '<div class="hot">&nbsp;</div>';
            } else {
                $info = "&nbsp;";
            }
            $num_replies = $this->getNumberOfPosts($row['id']) - 1;

            // Display the message board posts rows
            echo <<<HTML
                    <tr class="{$tr_class}">
                        <td class="images"><div class="{$img_class}">&nbsp;</div></td>
                        <td class="subject">
                            {$subject_info}<a href="messageboard.php?thread={$row['id']}">{$subject}</a><br/>
                            {$thread_pages}
                            <span><a class="u" href="profile.php?member={$row['started_by']}">{$started_by}</a></span>
                        </td>
                        <td class="info">{$info}</td>
                        <td class="replies">$num_replies</td>
                        <td class="views">{$row['views']}</td>
                        <td class="updated">
                            {$last_updated}
                        </td>
                    </tr>

HTML;
			$alt++;
		}
		if ($type == 'thread') {
			echo <<<HTML
                </tbody>
            </table>
            <div class="top clearfix"><a href="#top">{$LANG['back_top']}</a></div>

HTML;
			$this->displayPages($page);
		}
	}

	function showPosts ($thread_id, $page = '1')
    {
		global $LANG;
		$from = (($page * 15) - 15);
        $sql = "UPDATE fcms_board_threads SET views=(views + 1) WHERE id=$thread_id";
		$this->db->query($sql) or displaySQLError(
            '+ View Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
		$this->displayMessageBoardMenu($thread_id);
		$this->displayPages($page, $thread_id);
		$sort = $this->getSortOrder($this->cur_user_id);
		$showavatar = $this->getShowAvatar($this->cur_user_id);
        $sql = "SELECT p.`id`, `thread`, `post`, `subject`, p.`date`, `user`, `avatar` 
                FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, 
                    `fcms_users` AS u 
                WHERE `thread` = $thread_id 
                AND t.`id` = `thread` 
                AND `user` = u.`id` 
                ORDER BY p.`id` $sort 
                LIMIT $from, 15";
		$this->db->query($sql) or displaySQLError(
            'Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
		$alt = 0;
		$first = true;
        $total = $this->getNumberOfPosts($thread_id);
		while ($row = $this->db->get_row()) {
            // display the table header
			if ($first) {
				echo '            <table id="postlist" cellpadding="0" cellspacing="0">' . "\n";
                echo '                <tbody>' . "\n";
				$first = false;
			}

            // Setup some vars
			$subject = $row['subject'];
			if (strlen($subject) > 40) {
                $subject = substr($subject, 0, 37) . "...";
            }
            // Remove #ANOUNCE#
			$pos = strpos($subject, '#ANOUNCE#');
			if ($pos !== false) {
                $subject = substr($subject, 9, strlen($subject)-9);
            }
            if ($sort == 'ASC') {
                if ($alt > 0) { $subject = "RE: " . $subject; }
            } else {
                if ($alt !== $total - 1) { $subject = "RE: " . $subject; }
            }
			$displayname = getUserDisplayName($row['user']);
			$date = $row['date'];
			$date = fixDST(gmdate('n/d/Y h:ia', strtotime($date . $this->tz_offset)), $this->cur_user_id, 'n/d/Y g:ia');
            if ($alt % 2 == 0) {
                $tr_class = '';
            } else {
                $tr_class = 'alt';
            }
            // rank
			$points = getUserRankById($row['user']);
            $rank = '';
			if ($points > 50) {
                $rank = "<div title=\"".$LANG['elder']." ($points)\" class=\"rank7\"></div>";
            } elseif ($points > 30) {
                $rank = "<div title=\"".$LANG['adult']." ($points)\" class=\"rank6\"></div>";
            } elseif ($points > 20) {
                $rank = "<div title=\"".$LANG['mature_adult']." ($points)\" class=\"rank5\"></div>";
            } elseif ($points > 10) {
                $rank = "<div title=\"".$LANG['young_adult']." ($points)\" class=\"rank4\"></div>";
            } elseif ($points > 5) {
                $rank = "<div title=\"".$LANG['teenager']." ($points)\" class=\"rank3\"></div>";
            } elseif ($points > 3) {
                $rank = "<div title=\"".$LANG['kid']." ($points)\" class=\"rank2\"></div>";
            } elseif ($points > 1) {
                $rank = "<div title=\"".$LANG['toddler']." ($points)\" class=\"rank1\"></div>";
            } else {
                $rank = "<div title=\"".$LANG['baby']." ($points)\" class=\"rank0\"></div>";
            }
            $avatar = '';
            if ($showavatar > 0) {
                $avatar = "<img src=\"gallery/avatar/" . $row['avatar'] . "\" alt=\"$displayname\"/><br/><br/>";
            }
            $awards = '';
            if ($this->hasAwards($row['user'])) {
                $awards = $this->getAwards($row['user']);
            }
            $posts_count = $this->getUserPostCountById($row['user']);
            $actions = '';
            // quote
			if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
				$actions .= '<form method="post" action="messageboard.php?reply='.$thread_id.'">
                                        <div>
                                            <input type="hidden" name="id" value="'.$row['id'].'"/>
                                            <input type="submit" class="quotebtn" value="'.$LANG['quote'].'" name="quotepost" title="'.$LANG['title_quote'].'"/>
                                        </div>
                                    </form>';
			}
            // edit
			if ($this->cur_user_id == $row['user'] || checkAccess($this->cur_user_id) < 3) {
				$actions .= ' &nbsp;
                                    <form method="post" action="messageboard.php">
                                        <div>
                                            <input type="hidden" name="id" value="'.$row['id'].'"/>
                                            <input type="submit" name="editpost" value="'.$LANG['edit'].'" class="editbtn" title="'.$LANG['title_edit_post'].'"/>
                                        </div>
                                    </form>';
			}
            // delete
			if (checkAccess($this->cur_user_id) < 2) {
				$actions .= ' &nbsp;
                                    <form class="delpost" method="post" action="messageboard.php">
                                        <div>
                                            <input type="hidden" name="id" value="'.$row['id'].'"/>
                                            <input type="hidden" name="thread" value="'.$thread_id.'"/>
                                            <input type="submit" name="delpost" value="'.$LANG['delete'].'" class="delbtn" title="'.$LANG['title_del_post'].'"/>
                                        </div>
                                    </form>';
			}

            // Display the posts rows
            echo <<<HTML
                    <tr class="{$tr_class}">
                        <td class="side">
                            <b><a href="profile.php?member={$row['user']}">$displayname</a></b>
                            {$rank}
                            {$avatar}
                            {$awards}
                            <b>{$LANG['posts']}</b> {$posts_count}
                        </td>
                        <td class="posts">
                            <div class="header clearfix">
                                <div class="subject"><b>{$subject}</b> - $date</div>
                                <div class="actions">
                                    {$actions}
                                </div>
                            </div>
                            <div class="msg">

HTML;
			parse($row['post']);
			echo '
                            </div>
                        </td>
                    </tr>';
			$alt++;
		}
		if (!$first) {
            echo '
                </tbody>
            </table>';
        }
        $this->displayMessageBoardMenu($thread_id);
		$this->displayPages($page, $thread_id);
		echo "            <div class=\"top\"><a href=\"#top\">".$LANG['back_top']."</a></div>\n";
	}

	function getNumberOfPosts ($thread_id) {
		$this->db2->query("SELECT count(*) AS c FROM fcms_board_posts WHERE thread = $thread_id") or die('<h1># of Posts Error (messageboard.class.php 132)</h1>' . mysql_error());
		$row=$this->db2->get_row();
		return $row['c'];
	}

	function getSortOrder ($user_id)
    {
        $sql = "SELECT `boardsort` FROM `fcms_user_settings` WHERE `user` = $user_id";
		$this->db2->query($sql) or displaySQLError(
            'Sort Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
		$row=$this->db2->get_row();
		return $row['boardsort'];
	}

	function getShowAvatar ($user_id)
    {
        $sql = "SELECT `showavatar` FROM `fcms_user_settings` WHERE `user` = $user_id";
		$this->db2->query($sql) or displaySQLError(
            'Avatar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
		$row=$this->db2->get_row();
		return $row['showavatar'];
    }

	function getUserPostCountById ($user_id) {
		$this->db2->query("SELECT * FROM fcms_board_posts") or die('<h1>Posts Error (messageboard.class.php 150)</h1>' . mysql_error());
		$total=$this->db2->count_rows();
		$this->db2->query("SELECT count(user) AS c FROM fcms_board_posts WHERE user = $user_id") or die('<h1>Count Error (messageboard.class.php 152)</h1>' . mysql_error());
		$row=$this->db2->get_row();
		$count=$row['c'];
		if($total < 1) { 
			return "0 (0%)";
		} else { 
			return $count . " (" . round((($count/$total)*100), 1) . "%)";
		}
	}
    
    // TODO
    // remove this and use standard pages function
	function displayPages ($page = '1', $thread_id = '0') {
		global $LANG;
		if($thread_id < 1) {
			$this->db2->query("SELECT count(id) AS c FROM fcms_board_threads") or die('<h1>Count Error (messageboard.class.php 165)</h1>' . mysql_error());
			$row=$this->db2->get_row();
			$total_pages = ceil($row['c'] / 25); 
		} else {
			$this->db2->query("SELECT count(id) AS c FROM fcms_board_posts WHERE thread = $thread_id") or die('<h1>Count Error (messageboard.class.php 169)</h1>' . mysql_error());
			$row=$this->db2->get_row();
			$total_pages = ceil($row['c'] / 15); 
		}
		if($total_pages > 1) {
			echo "\t\t\t<div class=\"pages clearfix\"><ul>"; 
			if($page > 1) { 
				$prev = ($page - 1); 
				echo "<li><a title=\"".$LANG['title_first_page']."\" class=\"first\" href=\"messageboard.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=1\"></a></li>"; 
				echo "<li><a title=\"".$LANG['title_prev_page']."\" class=\"previous\" href=\"messageboard.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$prev\"></a></li>"; 
			} 
			if($total_pages > 8) {
				if($page > 2) {
					for($i = ($page-2); $i <= ($page+5); $i++) {
						if($i <= $total_pages) { echo "<li><a href=\"messageboard.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; }
					} 
				} else {
					for($i = 1; $i <= 8; $i++) { echo "<li><a href=\"messageboard.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; } 
				}
			} else {
				for($i = 1; $i <= $total_pages; $i++) {
					echo "<li><a href=\"messageboard.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>";
				} 
			}
			if($page < $total_pages) { 
				$next = ($page + 1); 
				echo "<li><a title=\"".$LANG['title_next_page']."\" class=\"next\" href=\"messageboard.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$next\"></a></li>"; 
				echo "<li><a title=\"".$LANG['title_last_page']."\" class=\"last\" href=\"messageboard.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$total_pages\"></a></li>"; 
			} 
			echo "</ul></div>\n"; // end of pages div
		}
	}

    /*
     *  displayForm
     *
     *  Displays the form for posting: new, reply and edit
     *  
     *  @param      $type       new, reply or edit
     *  @param      $thread_id  used for reply and edit
     *  @param      $post_id    used for reply and edit
     *  @param      $post       used for edit
     *  @return     none
     */
	function displayForm ($type, $thread_id = '0', $post_id = '0', $post = 'error') {
		global $LANG;

        // New
        if ($type == 'new') {
            $reply = '';
            $header = $LANG['new_msg'];
            $subject = '
                <div>
                    <label for="subject">'.$LANG['subject'].'</label>: 
                    <input type="text" name="subject" id="subject" title="'.$LANG['title_msg_subject'].'" size="50"/>
                </div>
                <script type="text/javascript">
                    var fsub = new LiveValidation(\'subject\', {onlyOnSubmit: true});
                    fsub.add(Validate.Presence, {failureMessage: ""});
                </script>';
            $sticky = '';
			if (checkAccess($this->cur_user_id) <= 2) {
                $sticky = '
                <p>
                    <label for="sticky">'.$LANG['admin_tools'].'</label>: 
                    <input type="checkbox" name="sticky" id="sticky" value="sticky"/>'.$LANG['make_announcement'].'
                </p>';
            }
            $post = '';
            $post_js = '
                <script type="text/javascript">
                    var fpost = new LiveValidation(\'post\', {onlyOnSubmit: true});
                    fpost.add(Validate.Presence, {failureMessage: ""});
                </script>';
            $hidden_submit = '
                <div><input type="hidden" name="name" id="name" value="'.$this->cur_user_id.'"/></div>
                <p><input type="submit" name="post_submit" id="post_submit" value="'.$LANG['submit'].'"/></p>';

        // Reply
        } elseif ($type == 'reply') {
            $header = $LANG['reply'];
            $subject = '';
            $sticky = '';
            $post_js = '';
            
            // Get last post in the thread to display above reply
            $sql = "SELECT `post` 
                    FROM `fcms_board_posts` 
                    WHERE `thread` = " . $thread_id . " 
                    ORDER BY `date` DESC 
                    LIMIT 1";
			$this->db->query($sql) or displaySQLError(
                'Get Reply Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
			$row = $this->db->get_row();
			$search = array('/\[ins\](.*?)\[\/ins\]/is', '/\[del\](.*?)\[\/del\]/is', 
                '/\[h1\](.*?)\[\/h1\]/is', '/\[h2\](.*?)\[\/h2\]/is', '/\[h3\](.*?)\[\/h3\]/is', 
                '/\[h4\](.*?)\[\/h4\]/is', '/\[h5\](.*?)\[\/h5\]/is', '/\[h6\](.*?)\[\/h6\]/is', 
				'/\[b\](.*?)\[\/b\]/is', '/\[i\](.*?)\[\/i\]/is', '/\[u\](.*?)\[\/u\]/is', 
                '/\[url\=(.*?)\](.*?)\[\/url\]/is', '/\[url\](.*?)\[\/url\]/is', 
                '/\[align\=(left|center|right)\](.*?)\[\/align\]/is','/\[img\=(.*?)\]/is', 
                '/\[img\](.*?)\[\/img\]/is', '/\[mail\=(.*?)\](.*?)\[\/mail\]/is', 
                '/\[mail\](.*?)\[\/mail\]/is', '/\[font\=(.*?)\](.*?)\[\/font\]/is', 
                '/\[size\=(.*?)\](.*?)\[\/size\]/is', '/\[color\=(.*?)\](.*?)\[\/color\]/is', 
                '/\[span\](.*?)\[\/span\]/is', '/\[span\=(.*?)\](.*?)\[\/span\]/is'
            );
			$replace = array('$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', 
                '$2', '$1', '$2', '$1', '1', '$2', '$1', '$2', '$2','$2', '$1', '$2'
            );
			$reply = "<p>".preg_replace($search, $replace, $row['post'])."</p>";

            // Get the text of ther post that the user is quoting
            // We know we are quoting someone if type is reply and we have a post_id
            if ($post_id > 0) {
                $sql = "SELECT `post`, `user` 
                        FROM `fcms_board_posts` 
                        WHERE `id` = " . $post_id . " 
                        LIMIT 1";
                $this->db->query($sql) or displaySQLError(
                    'Get Quote Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                $qrow = $this->db->get_row();
                $post = '[SPAN=q]'.$LANG['quoting'].': '.getUserDisplayName($qrow['user']).'[/SPAN][QUOTE]'
                        .htmlentities($qrow['post'], ENT_COMPAT, 'UTF-8').'[/QUOTE]';
            } else {
                $post = '';
            }
			
            $hidden_submit = '
                <div><input type="hidden" name="name" id="name" value="'.$this->cur_user_id.'"/></div>
                <div><input type="hidden" name="thread_id" value="'.$thread_id.'"/></div>
                <p><input type="submit" name="reply_submit" id="reply_submit" value="'.$LANG['reply'].'"/></p>';

        // Edit
        } elseif ($type == 'edit') {
            $reply = '';
            $header = $LANG['edit'];
            $subject = '';
            $sticky = '';
            $post_js = '';

            // Remove the previous edited by string so we can add a new one
            $pos = strpos($post, "[size=small][i]".$LANG['edited']);
            if ($pos !== false) {
                $post = substr($post, 0, $pos);
            }
			
            $hidden_submit = '
                <div><input type="hidden" name="id" id="id" value="'.$post_id.'"/></div>
                <div><input type="hidden" name="thread_id" id="thread_id" value="'.$thread_id.'"/></div>
                <p><input type="submit" name="edit_submit" id="edit_submit" value="'.$LANG['edit'].'"/></p>';
        }

        // Display the form
		echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <script type="text/javascript" src="inc/fcms.js"></script>
            <form id="postform" method="post" action="messageboard.php">
                '.$reply.'
                <h2>'.$header.'</h2>
                '.$subject.'
		        <div>
                    <label for="showname">'.$LANG['name'].'</label>: 
                    <input type="text" disabled="disabled" name="showname" id="showname" title="'.$LANG['your_name'].'" value="'.getUserDisplayName($this->cur_user_id).'" size="50"/> &nbsp;
                    <a id="upimages" class="hideme" href="#" onclick="window.open(\'inc/upimages.php\',\'name\',\'width=700,height=500,scrollbars=yes,resizable=no,location=no,menubar=no,status=no\'); return false;">('.$LANG['upload_image'].')</a>
                </div>
                '.$sticky.'
                <script type="text/javascript">var bb = new BBCode();</script>';
        echo "\n";
        displayMBToolbar();
        echo '
                <div>
                    <textarea name="post" id="post" rows="10" cols="63">'.$post.'</textarea>
                </div>
                '.$post_js.'
                <script type="text/javascript">bb.init(\'post\');</script>
                <p id="smileys" class="hideme">';
        echo "\n";
        displaySmileys();
		echo '
                </p>
                '.$hidden_submit.'
            </form>';
	}

	function hasAwards ($user_id) {
		$this->db2->query("SELECT * FROM fcms_user_awards WHERE user = $user_id AND `count` > 0") or die('<h1>Awards? Error (messageboard.class.php 258)</h1>' . mysql_error());
		$rows=$this->db2->count_rows();
		if ($rows > 0) { return true; } else { return false; }
	}

    function getAwards ($user_id)
    {
        global $LANG;
        $str = "<b>".$LANG['link_admin_awards']."</b>";
        $sql = "SELECT * FROM fcms_user_awards WHERE user = $user_id AND `count` > 0";
        $this->db2->query($sql) or displaySQLError(
            'Awards Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while($row=$this->db2->get_row()) {
            if ($row['type'] == 'top5poster') {
                $str .= "<div class=\"award boardtop5";
                if ($row['value'] <= 1) { $str .= "gold"; }
                $str .= "\" title=\"#" . $row['value'] . " " . $LANG['poster_last_month'];
                $str .= " (" . $row['count'] . " ".$LANG['posts_lc'].")\"></div>";
            } elseif ($row['type'] == 'top5photo') {
                $str .= "<div class=\"award phototop5";
                if ($row['value'] <= 1) { $str .= "gold"; }
                $str .= "\" title=\"#" . $row['value'] . " " . $LANG['most_photos'];
                $str .= " (" . $row['count'] . " ".$LANG['photos_lc'].")\"></div>";
            } else if ($row['type'] == 'mostsmileys') {
                $str .= "<div class=\"award smileys\" title=\"" . $LANG['most_smileys'] . "\"></div>";
            } else if ($row['type'] == 'topviewedphoto') {
                $str .= "<div class=\"award topviewedphoto\" title=\"";
                $str .= $LANG['viewed_photo_last_month'] . " (" . $row['count'] . " ";
                $str .= $LANG['views_lc'] . ")\"></div>";
            } else {
                $str .= "<div class=\"award threadstarter\" title=\"".$LANG['thread_starter'];
                $str .= " (" . $row['count'] . " " . $LANG['posts_lc'] . ")\"></div>";
            }
        }
        return $str;
    }

    function displayMessageBoardMenu ($thread_id = '') {
        global $LANG;
        if ($thread_id == '') {
            echo '<div id="sections_menu" class="clearfix">' . "\n";
            echo '<ul><li><a class="add_thread" href="messageboard.php?reply=new">'
                . $LANG['new_msg'] . "</a></li></ul>\n";
            echo "</div>\n";
        } else {
            echo '<div id="sections_menu" class="clearfix">' . "\n<ul>\n";
            echo '<li><a class="home" href="messageboard.php">'
                . $LANG['msg_board_home'] . "</a></li>\n";
            if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
                echo '<li><a class="add_post" href="messageboard.php?reply=' . $thread_id . '">'
                    . $LANG['reply'] . "</a></li>\n";
            }
            echo "</ul>\n</div>\n";
		}

    }

	function displayWhatsNewMessageBoard ()
    {
		global $LANG;
		$today = date('Y-m-d');
		$tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		echo "\t\t\t\t<h3>" . $LANG['link_board'] . "</h3>\n\t\t\t\t<ul>\n";
        $sql = "SELECT * "
             . "FROM ("
                . "SELECT p.id, `date`, subject, u.id AS userid, fname, lname, username, thread "
                . "FROM fcms_board_posts AS p, fcms_board_threads AS t, fcms_users AS u "
                . "WHERE p.thread = t.id "
                . "AND p.user = u.id "
                . "AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) "
                . "ORDER BY `date` DESC"
             . ") AS r "
             . "GROUP BY subject "
             . "ORDER BY `date` DESC "
             . "LIMIT 0, 5";
		$this->db->query($sql) or die('<h1>Posts Error (messageboard.class.php 287)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			while($row = $this->db->get_row()) {
				$displayname = getUserDisplayName($row['userid']);
				$subject = $row['subject'];
				$subject_full = htmlentities($row['subject'], ENT_COMPAT, 'UTF-8');
				$pos = strpos($subject, '#ANOUNCE#');
				if($pos !== false) { $subject = substr($subject, 9, strlen($subject)-9); }
				if(strlen($subject) > 23) { $subject = substr($subject, 0, 20) . "..."; }
                $monthName = gmdate('M', strtotime($row['date'] . $this->tz_offset));
                $date = fixDST(
                    gmdate('n/j/Y g:i a', strtotime($row['date'] . $this->tz_offset)), 
                    $this->cur_user_id, '. j, Y, g:i a'
                );
				if (
                    strtotime($row['date']) >= strtotime($today) && 
                    strtotime($row['date']) > $tomorrow
                ) { 
                    $full_date = $LANG['today'];
                    $d = ' class="today"';
                } else {
                    $full_date = getLangMonthName($monthName) . $date;
                    $d = '';
                }
				echo "\t\t\t\t\t<li><div$d>$full_date</div>";
				echo "<a href=\"messageboard.php?thread=" . $row['thread'] . "\" ";
                echo "title=\"$subject_full\">$subject</a> ";
				if ($this->getNumberOfPosts($row['thread']) > 15) {
					$num_posts = $this->getNumberOfPosts($row['thread']);
					echo "(".$LANG['page']." ";
					$times2loop = ceil($num_posts/15);
					for ($i=1; $i<=$times2loop; $i++) {
                        echo "<a href=\"messageboard.php?thread=" . $row['thread'];
                        echo "&amp;page=$i\" title=\"".$LANG['page']." $i\">$i</a> ";
                    }
					echo ")";
				}
				echo " - <a class=\"u\" href=\"profile.php?member=" . $row['userid'];
                echo "\">$displayname</a></li>\n";
			}
		} else {
			echo "\t\t\t\t\t<li><i>".$LANG['nothing_new_30']."</i></li>\n";
		}
		echo "\t\t\t\t</ul>\n";
	}

} ?>
