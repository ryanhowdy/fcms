<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class MessageBoard {

	var $db;
	var $db2;
	var $tz_offset;
	var $cur_user_id;

	function MessageBoard ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
		$this->db2 = new database($type, $host, $database, $user, $pass);
		$this->db->query("SELECT timezone FROM fcms_users WHERE id = $current_user_id") or die('<h1>Timezone Error (messageboard.class.php 16)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function showThreads ($type, $page = '0') {
		global $LANG;
		$from = (($page * 25) - 25);
		if ($type == 'announcement') {
			if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
				echo "<p><a href=\"messageboard.php?reply=new\">".$LANG['new_msg']."</a></p>\n\t\t\t";
			}
			echo "<table id=\"threadlist\" cellpadding=\"0\" cellspacing=\"0\">\n\t\t\t\t<thead><tr><th class=\"images\">&nbsp;</th><th class=\"subject\">".$LANG['subject']."</th><th class=\"replies\">".$LANG['replies']."</th><th class=\"views\">".$LANG['views']."</th><th class=\"updated\">".$LANG['last_updated']."</th></tr></thead>\n\t\t\t\t<tbody>\n";
			$this->db->query("SELECT fcms_board_threads.id, subject, started_by, updated, updated_by, views, user FROM fcms_board_threads, fcms_board_posts WHERE fcms_board_threads.id = fcms_board_posts.thread AND subject LIKE '#ANOUNCE#%' GROUP BY fcms_board_threads.id ORDER BY updated DESC") or die('<h1>Announcements Error (messageboard.class.php 27)</h1>' . mysql_error());
		} else {
			$this->db->query("SELECT fcms_board_threads.id, subject, started_by, updated, updated_by, views, user FROM fcms_board_threads, fcms_board_posts WHERE fcms_board_threads.id = fcms_board_posts.thread AND subject NOT LIKE '#ANOUNCE#%' GROUP BY fcms_board_threads.id ORDER BY updated DESC LIMIT " . $from . ", 29") or die('<h1>Threads Error (messageboard.class.php 35)</h1>' . mysql_error());
		}
		$alt = 0;
		while($row = $this->db->get_row()) {
			$started_by = getUserDisplayName($row['started_by']);
			$updated_by = getUserDisplayName($row['updated_by']);
			$subject = $row['subject'];
			if ($type == 'announcement') {
				$subject = substr($subject, 9, strlen($subject)-9);
				echo "\t\t\t\t\t<tr class=\"announcement\">";
			} else {
				echo "\t\t\t\t\t<tr"; if ($alt % 2 == 0) { echo ">"; } else { echo " class=\"alt\">"; }
			}
			if (gmdate('n/d/Y', strtotime($row['updated'] . $this->tz_offset)) == gmdate('n/d/Y', strtotime(date('Y-m-d H:i:s') . $this->tz_offset))) {
				echo '<td class="images"><div class="'; if ($type == 'announcement') { echo 'announcement_'; }  echo 'today">&nbsp;</div>&nbsp;</td>';
				$last_updated = "".$LANG['today_at']." " . fixDST(gmdate('h:ia', strtotime($row['updated'] . $this->tz_offset)), $this->cur_user_id, 'h:ia') . "<br/>".$LANG['by']." <a class=\"u\" href=\"profile.php?member=" . $row['updated_by'] . "\">$updated_by</a>";
			} elseif (gmdate('n/d/Y', strtotime($row['updated'] . $this->tz_offset)) == gmdate('n/d/Y', strtotime(date('n/d/Y', strtotime(date('Y-m-d H:i:s') . $this->tz_offset)) . "-24 hours"))) {
				echo '<td class="images"><div class="'; if ($type == 'announcement') { echo 'announcement_'; }  echo 'yesterday">&nbsp;</div>&nbsp;</td>';
				$last_updated = "".$LANG['yesterday_at']." " . fixDST(gmdate('h:ia', strtotime($row['updated'] . $this->tz_offset)), $this->cur_user_id, 'h:ia') . "<br/>".$LANG['by']." <a class=\"u\" href=\"profile.php?member=" . $row['updated_by'] . "\">$updated_by</a>";
			} else {
				echo '<td class="images">'; if ($type == 'announcement') { echo '<div class="announcement">&nbsp;</div>'; }  echo '&nbsp;</td>';
				$last_updated = fixDST(gmdate('m/d/Y h:ia', strtotime($row['updated'] . $this->tz_offset)), $this->cur_user_id, 'm/d/Y h:ia') . "<br/>".$LANG['by']." <a class=\"u\" href=\"profile.php?member=" . $row['updated_by'] . "\">$updated_by</a>";
			}
			echo '<td class="subject">'; if ($type == 'announcement') { echo "<small><b>".$LANG['announcement'].": </b></small>"; } echo '<a href="messageboard.php?thread=' . $row['id'] . '">';
			if($this->getNumberOfPosts($row['id']) >= 20) {
				echo "<span class=\"hot\">$subject</span>";
			} else {
				echo $subject;
			}
			echo '</a><br/>';
			if($this->getNumberOfPosts($row['id']) > 15) { 
				$num_posts = $this->getNumberOfPosts($row['id']);
				echo "<span>".$LANG['page']." ";
				$times2loop = ceil($num_posts/15);
				for($i=1;$i<=$times2loop;$i++) { echo "<a href=\"messageboard.php?thread=" . $row['id'] . "&amp;page=$i\">$i</a> "; }
				echo "</span><br/>";
			}
			echo '<span><a class="u" href="profile.php?member=' . $row['started_by'] . '">' . $started_by . '</a></span></td><td class="replies">';
			echo $this->getNumberOfPosts($row['id']) - 1;
			echo '</td><td class="views">' . $row['views'] . '</td><td class="updated">' . $last_updated . "</td></tr>\n";
			$alt++;
		}
		if ($type == 'thread') {
			echo "\t\t\t\t</tbody>\n\t\t\t</table>\n\t\t\t<div class=\"top clearfix\"><a href=\"#top\">".$LANG['back_top']."</a></div>\n";
			$this->displayPages($page);
		}
	}

	function showPosts ($thread_id, $page = '1') {
		global $LANG;
		$from = (($page * 15) - 15); 
		$this->db->query("UPDATE fcms_board_threads  SET views=(views + 1) WHERE id=$thread_id") or die('<h1>+View Error (messageboard.class.php 83)</h1>' . mysql_error());
		echo "<p><a href=\"messageboard.php\">".$LANG['msg_board_home']."</a>";
		if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
			echo " | <a href=\"messageboard.php?reply=$thread_id\">".$LANG['reply']."</a>";
		}
		echo "</p>\n";
		$this->displayPages($page, $thread_id);
		$sort = $this->getSortOrder($this->cur_user_id);
		$showavatar = $this->getShowAvatar($this->cur_user_id);
		$this->db->query("SELECT fcms_board_posts.id, thread, post, subject, fcms_board_posts.`date`, user, avatar FROM fcms_board_posts, fcms_board_threads, fcms_users WHERE thread = $thread_id AND fcms_board_threads.id = thread AND user = fcms_users.id ORDER BY fcms_board_posts.id $sort LIMIT " . $from . ", 15") or die('<h1>Posts Error (messageboard.class.php 88)</h1>' . mysql_error());
		$alt = 0;
		$first = true;
		while($row=$this->db->get_row()) {
			if ($first) {
				echo "\t\t\t<table id=\"postlist\" cellpadding=\"0\" cellspacing=\"0\">\n\t\t\t\t<tbody>\n";
				$first = false;
			}
			$subject = $row['subject'];
			if(strlen($subject) > 40) { $subject = substr($subject, 0, 37) . "..."; }
			$pos = strpos($subject, '#ANOUNCE#');
			if($pos !== false) { $subject = substr($subject, 9, strlen($subject)-9); }
			if ($alt > 0) { $subject = "RE: " . $subject; }
			$displayname = getUserDisplayName($row['user']);
			$post = $row['post'];
			$date = $row['date'];
			$date = fixDST(gmdate('n/d/Y h:ia', strtotime($date . $this->tz_offset)), $this->cur_user_id, 'n/d/Y g:ia');
			echo "\t\t\t\t\t<tr"; if ($alt % 2 == 0) { echo ">"; } else { echo " class=\"alt\">"; } echo "\n\t\t\t\t\t\t<td class=\"side\">\n";
			echo "\t\t\t\t\t\t\t<b><a href=\"profile.php?member=" . $row['user'] . "\">$displayname</a></b>";
			$points = getUserRankById($row['user']);
			if($points > 50) { echo "<div title=\"".$LANG['elder']." ($points)\" class=\"rank7\"></div>"; }
			elseif($points > 30) { echo "<div title=\"".$LANG['adult']." ($points)\" class=\"rank6\"></div>"; }
			elseif($points > 20) { echo "<div title=\"".$LANG['mature_adult']." ($points)\" class=\"rank5\"></div>"; }
			elseif($points > 10) { echo "<div title=\"".$LANG['young_adult']." ($points)\" class=\"rank4\"></div>"; }
			elseif($points > 5) { echo "<div title=\"".$LANG['teenager']." ($points)\" class=\"rank3\"></div>"; }
			elseif($points > 3) { echo "<div title=\"".$LANG['kid']." ($points)\" class=\"rank2\"></div>"; }
			elseif($points > 1) { echo "<div title=\"".$LANG['toddler']." ($points)\" class=\"rank1\"></div>"; }
			else { echo "<div title=\"".$LANG['baby']." ($points)\" class=\"rank0\"></div>"; }
			if($showavatar == 'YES') { echo "<img src=\"gallery/avatar/" . $row['avatar'] . "\" alt=\"$displayname\"/><br/><br/>"; }
			if ($this->hasAwards($row['user'])) { $this->showAwards($row['user']); }
			echo "<b>".$LANG['posts']."</b>" . $this->getUserPostCountById($row['user']) . "\n\t\t\t\t\t\t</td>\n\t\t\t\t\t\t<td class=\"post\">\n";
			echo "\t\t\t\t\t\t\t<div class=\"subject\"><b>$subject</b> - $date ";
			if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
				echo "<form method=\"post\" action=\"messageboard.php?reply=$thread_id\"><div><input type=\"hidden\" name=\"quote\" value=\"[SPAN=q]".$LANG['quoting'].": " . $displayname . "[/SPAN][QUOTE]" . htmlentities($post, ENT_COMPAT, 'UTF-8') . "[/QUOTE]\"/><input type=\"submit\" class=\"quotebtn\" value=\" \" name=\"quotepost\" title=\"".$LANG['title_quote']."\"/></div></form> &nbsp;";
			}
			if ($this->cur_user_id == $row['user'] || checkAccess($this->cur_user_id) < 3) {
				echo "<form method=\"post\" action=\"messageboard.php\"><div><input type=\"hidden\" name=\"id\" value=\"" . $row['id'] . "\"/><input type=\"submit\" name=\"editpost\" value=\" \" class=\"editbtn\" title=\"".$LANG['title_edit_post']."\"/></div></form> &nbsp;";
			}
			if (checkAccess($this->cur_user_id) < 2) {
				echo "<form method=\"post\" action=\"messageboard.php\"><div><input type=\"hidden\" name=\"id\" value=\"" . $row['id'] . "\"/><input type=\"hidden\" name=\"thread\" value=\"$thread_id\"/><input type=\"submit\" name=\"delpost\" value=\" \" class=\"delbtn\" title=\"".$LANG['title_del_post']."\" onclick=\"javascript:return confirm('".$LANG['js_del_post']."');\" /></div></form>";
			}
			echo "</div>\n\t\t\t\t\t\t\t<div class=\"msg\">";
			parse($post);
			echo "</div>\n\t\t\t\t\t\t</td>\n\t\t\t\t\t</tr>\n";
			$alt++;
		}
		if (!$first) { echo "\t\t\t\t</tbody>\n\t\t\t</table>\n"; }
		echo "<p><a href=\"messageboard.php\">".$LANG['msg_board_home']."</a>";
		if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
			echo " | <a href=\"messageboard.php?reply=$thread_id\">".$LANG['reply']."</a>";
		}
		echo "</p>\n";
		$this->displayPages($page, $thread_id);
		echo "\t\t\t<div class=\"top\"><a href=\"#top\">".$LANG['back_top']."</a></div>\n";
	}

	function getNumberOfPosts ($thread_id) {
		$this->db2->query("SELECT count(*) AS c FROM fcms_board_posts WHERE thread = $thread_id") or die('<h1># of Posts Error (messageboard.class.php 132)</h1>' . mysql_error());
		$row=$this->db2->get_row();
		return $row['c'];
	}

	function getSortOrder ($user_id) {
		$this->db2->query("SELECT boardsort FROM fcms_users WHERE id = $user_id") or die('<h1>Sort Error (messageboard.class.php 138)</h1>' . mysql_error());
		$row=$this->db2->get_row();
		return $row['boardsort'];
	}

	function getShowAvatar ($user_id) {
		$this->db2->query("SELECT showavatar FROM fcms_users WHERE id = $user_id") or die('<h1>Avatar Error (messageboard.class.php 144)</h1>' . mysql_error());
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

	// new reply edit
	function displayForm ($type, $thread_id = '0', $post_id = '0', $post = 'error') {
		global $LANG;
		echo "<script src=\"inc/prototype.js\" type=\"text/javascript\"></script>\n\t\t\t<script type=\"text/javascript\" src=\"inc/livevalidation.js\"></script>\n";
		echo "\t\t\t<script type=\"text/javascript\" src=\"inc/messageboard.inc.js\"></script>\n\t\t\t<form id=\"postform\" method=\"post\" action=\"messageboard.php\">\n\t\t\t\t";
		if($type == 'new') { 
			echo "<h2>".$LANG['new_msg']."</h2>\n";
		} elseif($type == 'reply') {
			$this->db->query("SELECT `post` FROM `fcms_board_posts` WHERE `thread` = " . $thread_id . " ORDER BY `date` DESC LIMIT 1") or die('<h1>Post Error (messageboard.class.php 211)</h1>' . mysql_error());
			$row = $this->db->get_row();
			$search = array('/\[ins\](.*?)\[\/ins\]/is', '/\[del\](.*?)\[\/del\]/is', '/\[h1\](.*?)\[\/h1\]/is', '/\[h2\](.*?)\[\/h2\]/is', '/\[h3\](.*?)\[\/h3\]/is', '/\[h4\](.*?)\[\/h4\]/is', '/\[h5\](.*?)\[\/h5\]/is', '/\[h6\](.*?)\[\/h6\]/is', 
				'/\[b\](.*?)\[\/b\]/is', '/\[i\](.*?)\[\/i\]/is', '/\[u\](.*?)\[\/u\]/is', '/\[url\=(.*?)\](.*?)\[\/url\]/is', '/\[url\](.*?)\[\/url\]/is', '/\[align\=(left|center|right)\](.*?)\[\/align\]/is','/\[img\=(.*?)\]/is', '/\[img\](.*?)\[\/img\]/is', 
				'/\[mail\=(.*?)\](.*?)\[\/mail\]/is', '/\[mail\](.*?)\[\/mail\]/is', '/\[font\=(.*?)\](.*?)\[\/font\]/is', '/\[size\=(.*?)\](.*?)\[\/size\]/is', '/\[color\=(.*?)\](.*?)\[\/color\]/is', '/\[span\](.*?)\[\/span\]/is', '/\[span\=(.*?)\](.*?)\[\/span\]/is');
			$replace = array('$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$2', '$1', '$2', '$1', '1', '$2', '$1', '$2', '$2','$2', '$1', '$2');
			$repost = preg_replace($search, $replace, $row['post']);
			echo "<p>$repost</p>";
			echo "<h2>".$LANG['reply']."</h2>\n";
		} elseif($type == 'edit') {
			echo "<h2>".$LANG['edit']."</h2>\n";
		}
		if($type == 'new') {
			echo "\t\t\t\t<div><label for=\"subject\">".$LANG['subject']."</label>: <input type=\"text\" name=\"subject\" id=\"subject\" class=\"required\" title=\"".$LANG['title_msg_subject']."\" size=\"50\"/></div>\n";
			echo "\t\t\t\t<script type=\"text/javascript\">\n\t\t\t\t\tvar fsub = new LiveValidation('subject', { validMessage: \"\", wait: 500});\n\t\t\t\t\tfsub.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\t</script>\n\t\t\t\t";
		}
		echo "\t\t\t\t<div><label for=\"showname\">".$LANG['name']."</label>: <input type=\"text\" disabled=\"disabled\" name=\"showname\" id=\"showname\" title=\"".$LANG['your_name']."\" value=\"" . getUserDisplayName($this->cur_user_id) . "\" size=\"50\" /> &nbsp;<a href=\"#\" onclick=\"window.open('inc/upimages.php','name','width=700,height=500,scrollbars=yes,resizable=no,location=no,menubar=no,status=no'); return false;\">(".$LANG['upload_image'].")</a></div>\n";
		if($type == 'new') {
			if(checkAccess($this->cur_user_id) <= 2) { echo "\t\t\t\t<p><label for=\"sticky\">".$LANG['admin_tools']."</label>: <input type=\"checkbox\" name=\"sticky\" id=\"sticky\" value=\"sticky\" />".$LANG['make_announcement']."</p>\n"; }
		}
		echo "\t\t\t\t<script type=\"text/javascript\">var bb = new BBCode();</script>\n";
		displayMBToolbar();
		if($type == 'edit') {
			$pos = strpos($post, "[size=small][i]".$LANG['edited']);
			if ($pos !== false) { $post = substr($post, 0, $pos); }
			echo "\t\t\t\t<div><textarea name=\"post\" id=\"post\" class=\"required\" rows=\"10\" cols=\"63\">$post</textarea></div>\n";
		} elseif($type == 'reply' && $post !== 'error') {
			echo "\t\t\t\t<div><textarea name=\"post\" id=\"post\" class=\"required\" rows=\"10\" cols=\"63\">$post</textarea></div>\n";
		} else {
			echo "\t\t\t\t<div><textarea name=\"post\" id=\"post\" class=\"required\" rows=\"10\" cols=\"63\"></textarea></div>\n";
			echo "\t\t\t\t<script type=\"text/javascript\">\n\t\t\t\t\tvar fpost = new LiveValidation('post', { validMessage: \"\", wait: 500});\n\t\t\t\t\tfpost.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\t</script>\n\t\t\t\t";
		}
		echo "\t\t\t\t<script type=\"text/javascript\">bb.init('post');</script>\n\t\t\t\t<p id=\"smileys\">";
		displaySmileys();
		echo "</p>\n"; //end of smileys p
		if($type == 'new') {
			echo "\t\t\t\t<div><input type=\"hidden\" name=\"name\" id=\"name\" value=\"$this->cur_user_id\"/></div>\n";
			echo "\t\t\t\t<p><input type=\"submit\" name=\"post_submit\" id=\"post_submit\" value=\"".$LANG['submit']."\"/></p>\n";
		} elseif($type == 'reply') {
			echo "\t\t\t\t<div><input type=\"hidden\" name=\"name\" id=\"name\" value=\"$this->cur_user_id\"/></div>\n\t\t\t\t<div><input type=\"hidden\" name=\"thread_id\" value=\"$thread_id\"/></div>\n";
			echo "\t\t\t\t<p><input type=\"submit\" name=\"reply_submit\" id=\"reply_submit\" value=\"".$LANG['reply']."\"/></p>\n";
		} elseif($type == 'edit') {
			echo "\t\t\t\t<div><input type=\"hidden\" name=\"id\" id=\"id\" value=\"$post_id\"/></div>\n\t\t\t\t<div><input type=\"hidden\" name=\"thread_id\" id=\"thread_id\" value=\"$thread_id\"/></div>\n";
			echo "\t\t\t\t<p><input type=\"submit\" name=\"edit_submit\" id=\"edit_submit\" value=\"".$LANG['edit']."\"/></p>\n";
		}
		echo "\t\t\t</form>\n";
	}

	function hasAwards ($user_id) {
		$this->db2->query("SELECT * FROM fcms_user_awards WHERE user = $user_id AND `count` > 0") or die('<h1>Awards? Error (messageboard.class.php 258)</h1>' . mysql_error());
		$rows=$this->db2->count_rows();
		if ($rows > 0) { return true; } else { return false; }
	}

	function showAwards ($user_id) {
		global $LANG;
		echo "<b>".$LANG['link_admin_awards']."</b>";
		$this->db2->query("SELECT * FROM fcms_user_awards WHERE user = $user_id AND `count` > 0") or die('<h1>Awards Error (messageboard.class.php 266)</h1>' . mysql_error());
		while($row=$this->db2->get_row()) {
			if ($row['type'] == 'top5poster') {
				echo "<div class=\"award boardtop5"; if ($row['value'] <= 1) { echo "gold"; } echo "\" title=\"#" . $row['value'] . " ".$LANG['poster_last_month']." (" . $row['count'] . " ".$LANG['posts_lc'].")\"></div>";
			} elseif ($row['type'] == 'top5photo') {
				echo "<div class=\"award phototop5"; if ($row['value'] <= 1) { echo "gold"; } echo "\" title=\"#" . $row['value'] . " ".$LANG['most_photos']." (" . $row['count'] . " ".$LANG['photos_lc'].")\"></div>";
			} else if ($row['type'] == 'mostsmileys') {
				echo "<div class=\"award smileys\" title=\"".$LANG['most_smileys']."\"></div>";
			} else if ($row['type'] == 'topviewedphoto') {
				echo "<div class=\"award topviewedphoto\" title=\"".$LANG['viewed_photo_last_month']." (" . $row['count'] . " ".$LANG['views_lc'].")\"></div>";
			} else {
				echo "<div class=\"award threadstarter\" title=\"".$LANG['thread_starter']." (" . $row['count'] . " ".$LANG['posts_lc'].")\"></div>";
			}
		}
	}

	function displayWhatsNewMessageBoard () {
		global $LANG;
		$today = date('Y-m-d');
		$tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		echo "\t\t\t\t<h3>Message Board</h3>\n\t\t\t\t<ul class=\"twolines\">\n";
		$this->db->query("SELECT * FROM (SELECT p.id, `date`, subject, u.id AS userid, fname, lname, displayname, username, thread FROM fcms_board_posts AS p, fcms_board_threads AS t, fcms_users AS u WHERE p.thread = t.id AND p.user = u.id AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) ORDER BY `date` DESC) AS r GROUP BY subject ORDER BY `date` DESC LIMIT 0, 5") or die('<h1>Posts Error (messageboard.class.php 287)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			while($row = $this->db->get_row()) {
				$displayname = getUserDisplayName($row['userid']);
				$subject = $row['subject'];
				$subject_full = htmlentities($row['subject'], ENT_COMPAT, 'UTF-8');
				$pos = strpos($subject, '#ANOUNCE#');
				if($pos !== false) { $subject = substr($subject, 9, strlen($subject)-9); }
				if(strlen($subject) > 23) { $subject = substr($subject, 0, 20) . "..."; }
				echo "\t\t\t\t\t<li";
				if(strtotime($row['date']) >= strtotime($today) && strtotime($row['date']) > $tomorrow) { echo " class=\"new\""; }
				echo "><a href=\"messageboard.php?thread=" . $row['thread'] . "\" title=\"$subject_full\">$subject</a> ";
				if ($this->getNumberOfPosts($row['thread']) > 15) {
					$num_posts = $this->getNumberOfPosts($row['thread']);
					echo "<span>(".$LANG['page']." ";
					$times2loop = ceil($num_posts/15);
					for($i=1;$i<=$times2loop;$i++) { echo "<a href=\"messageboard.php?thread=" . $row['thread'] . "&amp;page=$i\" title=\"".$LANG['page']." $i\">$i</a> "; }
					echo ")</span>";
				}
				echo "<br/>" . gmdate('M. j, Y, g:i a', strtotime($row['date'] . $this->tz_offset));
				echo "<br/><span><a class=\"u\" href=\"profile.php?member=" . $row['userid'] . "\">$displayname</a></span></li>\n";
			}
		} else {
			echo "\t\t\t\t\t<li><i>".$LANG['nothing_new_30']."</i></li>\n";
		}
		echo "\t\t\t\t</ul>\n";
	}

} ?>