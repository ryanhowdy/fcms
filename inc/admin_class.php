<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class Admin {

	var $db;
	var $db2;
	var $tz_offset;
	var $lastmonth_beg;
	var $lastmonth_end;
	var $cur_user_id;

	function Admin ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->cur_user_id = $current_user_id;
		$this->lastmonth_beg = gmdate('Y-m', mktime(0, 0, 0, gmdate('m')-1, 1, gmdate('Y'))) . "-01 00:00:00";
		$this->lastmonth_end = gmdate('Y-m', mktime(0, 0, 0, gmdate('m')-1, 1, gmdate('Y'))) . "-31 24:59:59";
		$this->db = new database($type, $host, $database, $user, $pass);
		$this->db2 = new database($type, $host, $database, $user, $pass);
		$this->db->query("SELECT `timezone` FROM `fcms_users` WHERE `id` = $current_user_id") or die('<h1>Timezone Error (admin_class.php 21)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function showThreads ($type, $page = '0') {
		global $LANG;
		$from = (($page * 25) - 25);
		if ($type == 'announcement') {
			echo "<table id=\"threadlist\" cellpadding=\"0\" cellspacing=\"0\">\n\t\t\t\t<thead><tr><th class=\"images\">&nbsp;</th><th class=\"subject\">".$LANG['subject']."</th><th class=\"replies\">".$LANG['replies']."</th><th class=\"views\">".$LANG['views']."</th><th class=\"updated\">".$LANG['last_updated']."</th></tr></thead>\n\t\t\t\t<tbody>\n";
			$this->db->query("SELECT fcms_board_threads.id, subject, started_by, updated, updated_by, views, user FROM fcms_board_threads, fcms_board_posts WHERE fcms_board_threads.id = fcms_board_posts.thread AND subject LIKE '#ANOUNCE#%' GROUP BY fcms_board_threads.id ORDER BY updated DESC") or die('<h1>Announcements Error (admin.class.php 37)</h1>' . mysql_error());
		} else {
			$this->db->query("SELECT fcms_board_threads.id, subject, started_by, updated, updated_by, views, user FROM fcms_board_threads, fcms_board_posts WHERE fcms_board_threads.id = fcms_board_posts.thread AND subject NOT LIKE '#ANOUNCE#%' GROUP BY fcms_board_threads.id ORDER BY updated DESC LIMIT " . $from . ", 25") or die('<h1>Threads Error (admin.class.php 39)</h1>' . mysql_error());
		}
		$alt = 0;
		while($row=$this->db->get_row()) {
			$started_by = $this->getDisplayNameById($row['started_by']);
			$updated_by = $this->getDisplayNameById($row['updated_by']);
			$subject = $row['subject'];
			if ($type == 'announcement') {
				$subject = substr($subject, 9, strlen($subject)-9);
				echo "\t\t\t\t\t<tr class=\"announcement\">";
			} else {
				echo "\t\t\t\t\t<tr"; if ($alt % 2 == 0) { echo ">"; } else { echo " class=\"alt\">"; }
			}
			if (gmdate('n/d/Y', strtotime($row['updated'] . $this->tz_offset)) == gmdate('n/d/Y', strtotime(date('n/d/Y') . $this->tz_offset))) {
				echo '<td class="images"><div class="'; if ($type == 'announcement') { echo 'announcement_'; }  echo 'today">&nbsp;</div></td>';
				$last_updated = $LANG['today_at']." " . gmdate('h:ia', strtotime($row['updated'] . $this->tz_offset)) . "<br/>".$LANG['by']." $updated_by";
			} elseif (gmdate('n/d/Y', strtotime($row['updated'] . $this->tz_offset)) == gmdate('n/d/Y', strtotime(date('n/d/Y', strtotime(date('Y-m-d H:i:s') . $this->tz_offset)) . "-24 hours"))) {
				echo '<td class="images"><div class="'; if ($type == 'announcement') { echo 'announcement_'; }  echo 'yesterday">&nbsp;</div></td>';
				$last_updated = $LANG['yesterday_at']." " . gmdate('h:ia', strtotime($row['updated'] . $this->tz_offset)) . "<br/>".$LANG['by']." $updated_by";
			} else {
				echo '<td class="images">'; if ($type == 'announcement') { echo '<div class="announcement">&nbsp;</div>'; }  echo '</td>';
				$last_updated = gmdate('m/d/Y h:ia', strtotime($row['updated'] . $this->tz_offset)) . "<br/>".$LANG['by']." $updated_by";
			}
			echo '<td class="subject">'; if ($type == 'announcement') { echo "<small><b>".$LANG['announcement'].": </b></small>"; } 
			if($this->getNumberOfPosts($row['id']) >= 20) {
				echo "<span class=\"hot\">$subject</span>";
			} else {
				echo $subject;
			}
			echo ' <small><a class="edit_thread" href="board.php?edit=' . $row['id'] . '">edit</a> <a class="del_thread" href="board.php?del=' . $row['id'] . '" onclick="javascript:return confirm(\'Are you sure you want to DELETE this thread?\');" >delete</a></small><br/>' . $started_by . '</td><td class="replies">';
			echo $this->getNumberOfPosts($row['id']) - 1;
			echo '</td><td class="views">' . $row['views'] . '</td><td class="updated">' . $last_updated . "</td></tr>\n";
			$alt++;
		}
		if ($type == 'thread') {
			echo "\t\t\t\t</tbody>\n\t\t\t</table>\n\t\t\t<div class=\"top clearfix\"><a href=\"#top\">".$LANG['back_top']."</a></div>\n";
			$this->displayPages($page);
		}
	}

	function displayEditThread ($thread_id) {
		global $LANG;
		$this->db->query("SELECT t.`id` , `subject` , `started_by` , `post` FROM `fcms_board_threads` AS t, `fcms_board_posts` WHERE t.`id` = $thread_id LIMIT 1") or die('<h1>Thread Error (admin.class.php 78)</h1>' . mysql_error());
		$row = $this->db->get_row(); ?>
		<form method="post" action="board.php">
			<h2><?php echo $LANG['edit_thread']; ?></h2>
			<p><label for="subject"><?php echo $LANG['subject']; ?></label>: <input type="text" name="subject" id="subject" title="Message Subject" size="50" value="<?php $pos = strpos($row['subject'], '#ANOUNCE#'); if ($pos !== false) { $subject = substr($row['subject'], 9, strlen($row['subject'])-9); echo $subject; } else { echo $row['subject']; } ?>"/></p>
			<p><label for="showname"><?php echo $LANG['name']; ?></label>: <input type="text" disabled="disabled" name="showname" id="showname" title="Your Name" value="<?php echo $this->getDisplayNameById($row['started_by']); ?>" size="50"/></p>
			<div><input type="hidden" name="name" id="name" value="<?php echo $row['user']; ?>"/></div>
			<p><label for="sticky"><?php echo $LANG['admin_tools']; ?></label>: <input type="checkbox" <?php $pos = strpos($row['subject'], '#ANOUNCE#'); if ($pos !== false) { echo "checked=\"checked\" "; } ?>name="sticky" id="sticky" value="sticky"/><?php echo $LANG['make_announcement']; ?></p>
			<p><textarea disabled="disabled" name="post" id="post" rows="10" cols="63"><?php echo $row['post']; ?></textarea></p>
			<div><input type="hidden" name="threadid" id="threadid" value="<?php echo $thread_id; ?>"/></div>
			<p><input type="submit" name="edit_submit" id="edit_submit" value="<?php echo $LANG['edit_thread']; ?>"/></p>
		</form>
		<?php
	}

	function getDisplayNameById ($user_id) {
		$this->db2->query("SELECT fname, lname, displayname, username FROM fcms_users WHERE fcms_users.id = $user_id") or die('<h1>Displayname Error (admin.class.php 94)</h1>' . mysql_error());
		$row=$this->db2->get_row();
		if($row['displayname'] == 1) { return $row['fname']; }
		elseif($row['displayname'] == 2) { return $row['fname'] . $row['lname']; }
		elseif($row['displayname'] == 3) { return $row['username']; }
		else { return $row['username']; }
	}

	function getNumberOfPosts ($thread_id) {
		$this->db2->query("SELECT count(*) AS c FROM fcms_board_posts WHERE thread = $thread_id") or die('<h1># of Posts Error (admin.class.php 103)</h1>' . mysql_error());
		$row=$this->db2->get_row();
		return $row['c'];
	}

	function getSortOrder ($user_id) {
		$this->db2->query("SELECT boardsort FROM fcms_users WHERE id = $user_id") or die('<h1>Sort Error (admin.class.php 109)</h1>' . mysql_error());
		$row=$this->db2->get_row();
		return $row['boardsort'];
	}

	function getShowAvatar ($user_id) {
		$this->db2->query("SELECT showavatar FROM fcms_users WHERE id = $user_id") or die('<h1>Avatar Error (admin.class.php 115)</h1>' . mysql_error());
		$row=$this->db2->get_row();
		return $row['showavatar'];
	}

	function getUserPostCountById ($user_id) {
		$this->db2->query("SELECT * FROM fcms_board_posts") or die('<h1>Posts Error (admin.class.php 121)</h1>' . mysql_error());
		$total=$this->db2->count_rows();
		$this->db2->query("SELECT count(user) AS c FROM fcms_board_posts WHERE user = $user_id") or die('<h1>Count Error (admin.class.php 123)</h1>' . mysql_error());
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
			$this->db2->query("SELECT count(id) AS c FROM fcms_board_threads") or die('<h1>Count Error (admin.class.php 135)</h1>' . mysql_error());
			$row=$this->db2->get_row();
			$total_pages = ceil($row['c'] / 25); 
		} else {
			$this->db2->query("SELECT count(id) AS c FROM fcms_board_posts WHERE thread = $thread_id") or die('<h1>Count Error (admin.class.php 139)</h1>' . mysql_error());
			$row=$this->db2->get_row();
			$total_pages = ceil($row['c'] / 15); 
		}
		if($total_pages > 1) {
			echo "\t\t\t<div class=\"pages clearfix\"><ul>"; 
			if($page > 1) { 
				$prev = ($page - 1); 
				echo "<li><a title=\"".$LANG['title_first_page']."\" class=\"first\" href=\"board.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=1\"></a></li>"; 
				echo "<li><a title=\"".$LANG['title_prev_page']."\" class=\"previous\" href=\"board.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$prev\"></a></li>"; 
			} 
			if($total_pages > 8) {
				if($page > 2) {
					for($i = ($page-2); $i <= ($page+5); $i++) {
						if($i <= $total_pages) { echo "<li><a href=\"board.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; }
					} 
				} else {
					for($i = 1; $i <= 8; $i++) { echo "<li><a href=\"board.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; } 
				}
			} else {
				for($i = 1; $i <= $total_pages; $i++) {
					echo "<li><a href=\"board.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>";
				} 
			}
			if($page < $total_pages) { 
				$next = ($page + 1); 
				echo "<li><a title=\"".$LANG['title_next_page']."\" class=\"next\" href=\"board.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$next\"></a></li>"; 
				echo "<li><a title=\"".$LANG['title_last_page']."\" class=\"last\" href=\"board.php?"; if($thread_id != 0) { echo "thread=$thread_id&amp;"; } echo "page=$total_pages\"></a></li>"; 
			} 
			echo "</ul></div>\n"; // end of pages div
		}
	}

	function displayEditPollForm ($pollid = '0') {
		global $LANG;
		echo "<h3>".$LANG['edit_polls']."</h3>";
		if ($pollid > 0) {
			$this->db->query("SELECT `question`, o.`id`, `option` FROM `fcms_polls` AS p, `fcms_poll_options` AS o WHERE p.`id` = o.`poll_id` AND p.`id` = $pollid") or die('<h1>Poll Error (admin.class.php 175)</h1>' . mysql_error());
		} else {
			$this->db->query("SELECT max(`id`) AS c FROM `fcms_polls`") or die('<h1>Max Error (admin.class.php 177)</h1>' . mysql_error());
			$row = $this->db->get_row();
			$latest_poll_id = $row['c'];
			$this->db->query("SELECT `question`, o.`id`, `option` FROM `fcms_polls` AS p, `fcms_poll_options` AS o WHERE p.`id` = o.`poll_id` AND p.`id` = $latest_poll_id") or die('<h1>Poll Error (admin.class.php 180)</h1>' . mysql_error());
		}
		echo "<form id=\"editform\" name=\"editform\" action=\"?page=admin_polls\" method=\"post\"><fieldset><legend>".$LANG['edit_cur_poll']."</legend>";
		$i = 1;
		while ($row = $this->db->get_row()) {
			if ($i < 2) { echo "<h3>" . $row['question'] . "</h3>"; }
			echo "<div class=\"field-row\"><div class=\"field-label\"><label for=\"show$i\">".$LANG['option']." $i:</label></div> <div class=\"field-widget\"><input type=\"text\" name=\"show$i\" id=\"show$i\" ";
			if ($i < 3) { echo "class=\"required\""; } else { echo "class=\"\""; }
			echo " size=\"50\" value=\"" . htmlentities($row['option']) . "\"/><input type=\"hidden\" name=\"option$i\" class=\"\" value=\"" . $row['id'] . "\"/>";
			if ($i >= 3) { echo "<input type=\"button\" name=\"deleteoption\" class=\"delbtn\" onclick=\"document.editform.show$i.value=''; \" />"; }
			echo "</div></div>";
			$i++;
		}
		while ($i < 11) {
			echo "<div class=\"field-row\"><div class=\"field-label\"><label for=\"show$i\">".$LANG['option']." $i:</label></div> <div class=\"field-widget\"><input type=\"text\" id=\"show$i\" name=\"show$i\" class=\"\" size=\"50\" value=\"\"/><input type=\"hidden\" name=\"option$i\" class=\"\" value=\"new\"/></div></div>";
			$i++;
		}
		echo "</fieldset><p><input type=\"submit\" name=\"editsubmit\" value=\"".$LANG['edit']."\"/></p></form>";
	}

	function displayAddPollForm() { 
		global $show, $LANG;
		$show = false; ?>
		<script src="../inc/prototype.js" type="text/javascript"></script>
		<script type="text/javascript" src="../inc/livevalidation.js"></script>
		<form id="addform" action="polls.php" method="post">
		<fieldset><legend><?php echo $LANG['add_new_poll']; ?></legend>
		<div class="field-row"><div class="field-label"><label for="question"><?php echo $LANG['poll_question']; ?></label>:</div> <div class="field-widget"><input type="text" name="question" id="question" class="required" title="<?php echo $LANG['title_poll_question']; ?>" size="50"/></div></div>
		<script type="text/javascript">
			var fq = new LiveValidation('question', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
			fq.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_sorry_req']; ?>"});
		</script>
		<div class="field-row"><div class="field-label"><label for="option1"><?php echo $LANG['option']; ?> 1</label>:</div> <div class="field-widget"><input type="text" name="option1" id="option1" class="required" title="<?php echo $LANG['title_two_options']; ?>" size="40"/></div></div>
		<script type="text/javascript">
			var foption1 = new LiveValidation('option1', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
			foption1.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_2_options']; ?>"});
		</script>
		<div class="field-row"><div class="field-label"><label for="option2"><?php echo $LANG['option']; ?> 2</label>:</div> <div class="field-widget"><input type="text" name="option2" id="option2" class="required" title="<?php echo $LANG['title_two_options']; ?>" size="40"/></div></div>
		<script type="text/javascript">
			var foption2 = new LiveValidation('option2', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
			foption2.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_2_options']; ?>"});
		</script>
		<div class="field-row"><div class="field-label"><label for="option3"><?php echo $LANG['option']; ?> 3</label>:</div> <div class="field-widget"><input type="text" name="option3" id="option3" class="" title="<?php echo $LANG['title_options']; ?>" size="40"/></div></div>
		<div class="field-row"><div class="field-label"><label for="option4"><?php echo $LANG['option']; ?> 4</label>:</div> <div class="field-widget"><input type="text" name="option4" id="option4" class="" title="<?php echo $LANG['title_options']; ?>" size="40"/></div></div>
		<div class="field-row"><div class="field-label"><label for="option5"><?php echo $LANG['option']; ?> 5</label>:</div> <div class="field-widget"><input type="text" name="option5" id="option5" class="" title="<?php echo $LANG['title_options']; ?>" size="40"/></div></div>
		<div class="field-row"><div class="field-label"><label for="option6"><?php echo $LANG['option']; ?> 6</label>:</div> <div class="field-widget"><input type="text" name="option6" id="option6" class="" title="<?php echo $LANG['title_options']; ?>" size="40"/></div></div>
		<div class="field-row"><div class="field-label"><label for="option7"><?php echo $LANG['option']; ?> 7</label>:</div> <div class="field-widget"><input type="text" name="option7" id="option7" class="" title="<?php echo $LANG['title_options']; ?>" size="40"/></div></div>
		<div class="field-row"><div class="field-label"><label for="option8"><?php echo $LANG['option']; ?> 8</label>:</div> <div class="field-widget"><input type="text" name="option8" id="option8" class="" title="<?php echo $LANG['title_options']; ?>" size="40"/></div></div>
		<div class="field-row"><div class="field-label"><label for="option9"><?php echo $LANG['option']; ?> 9</label>:</div> <div class="field-widget"><input type="text" name="option9" id="option9" class="" title="<?php echo $LANG['title_options']; ?>" size="40"/></div></div>
		<div class="field-row"><div class="field-label"><label for="option10"><?php echo $LANG['option']; ?> 10</label>:</div> <div class="field-widget"><input type="text" name="option10" id="option10" class="" title="<?php echo $LANG['title_options']; ?>" size="40"/></div></div>
		</fieldset>
		<input type="submit" name="addsubmit" value="<?php echo $LANG['add']; ?>"/></form>
		<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><?php 
	}

	function getTopThreadStarter () {
		$this->db->query("SELECT *, count(`thread`) AS 'thread_count' FROM `fcms_board_posts` WHERE `date` >= '" . $this->lastmonth_beg . "' AND `date` <= '" . $this->lastmonth_end . "' GROUP BY `thread` ORDER BY 'thread_count' DESC LIMIT 1") or die('<h1>Thread Starter Error (admin.class.php 231)</h1>' . mysql_error());
		while($row=$this->db->get_row()) {
			$this->db2->query("UPDATE `fcms_user_awards` SET `user` = '" . $row['user'] . "', `value` = '" . $row['thread'] . "', `count` = '" . $row['thread_count'] . "' WHERE `type` = 'topthreadstarter'") or die('<h1>Award Error (admin.class.php 233)</h1>' . mysql_error());
		}
		if ($this->db->count_rows() > 0) { return true; } else { return false; }
	}

	function getMostViewedPhoto () {
		$this->db->query("SELECT `id`, `user`, `views` FROM `fcms_gallery_photos` WHERE date >= '" . $this->lastmonth_beg . "' AND date <= '" . $this->lastmonth_end . "' ORDER BY `views` DESC LIMIT 1") or die('<h1>View Photo Error (admin.class.php 238)</h1>' . mysql_error());
		while($row=$this->db->get_row()) {
			$this->db2->query("UPDATE `fcms_user_awards` SET `user` = '" . $row['user'] . "', `value` = '" . $row['id'] . "', `count` = '" . $row['views'] . "' WHERE `type` = 'topviewedphoto'") or die('<h1>Award Error (admin.class.php 240)</h1>' . mysql_error());
		}
	}

	function getTopPosters () {
		$this->db->query("SELECT `user`, count(`user`) AS 'post_count' FROM `fcms_board_posts` AS p WHERE `date` >= '" . $this->lastmonth_beg . "' AND `date` <= '" . $this->lastmonth_end . "' GROUP BY `user` ORDER BY 'post_count' DESC LIMIT 5") or die('<h1>Posters Error (admin.class.php 245)</h1>' . mysql_error());
		$i = 1;
		while($row=$this->db->get_row()) {
			$this->db2->query("UPDATE `fcms_user_awards` SET `user` = '" . $row['user'] . "', `value` = '$i', `count` = '" . $row['post_count'] . "' WHERE `type` = 'top5poster' AND `value` = '$i'") or die('<h1>Award Error (admin.class.php 248)</h1>' . mysql_error());
			$i++;
		}
	}

	function getTopPhotoSubmitters () {
		$this->db->query("SELECT `user`, count(*) AS c FROM `fcms_gallery_photos` WHERE `date` >= '" . $this->lastmonth_beg . "' AND `date` <= '" . $this->lastmonth_end . "' GROUP BY `user` ORDER BY c DESC LIMIT 5") or die('<h1>Photos Error (admin.class.php 254)</h1>' . mysql_error());
		$i = 1;
		while($row=$this->db->get_row()) {
			$this->db2->query("UPDATE `fcms_user_awards` SET `user` = '" . $row['user'] . "', `value` = '$i', `count` = '" . $row['c'] . "' WHERE `type` = 'top5photo' AND `value` = '$i'") or die('<h1>Award Error (admin.class.php 257)</h1>' . mysql_error());
			$i++;
		}
	}

	function getMostSmileys () {
		$most_smileys = '0';
		$most_smileys_user = '0';
		$this->db->query("SELECT `id` FROM `fcms_users` WHERE `username` != 'SITENEWS' AND `username` != 'test' AND `username` != 'reunion' ORDER BY `id`") or die('<h1>Users Error (admin.class.php 265)</h1>' . mysql_error());
		$users;
		$i = 1;
		while ($row = $this->db->get_row()) {
			$users[$i] = $row['id'];
			$i++;
		}
		foreach ($users as $user) {
			$this->db->query("SELECT count(`user`) AS 'post_count' FROM `fcms_board_posts` AS p WHERE `date` >= '" . $this->lastmonth_beg . "' AND `date` <= '" . $this->lastmonth_end . "' AND `user` = $user GROUP BY `user` ORDER BY post_count DESC") or die('<h1>Count Error (admin.class.php 273)</h1>' . mysql_error());
			$row = $this->db->get_row();
			$this_user_post_count = $row['post_count'];
			$this->db->query("SELECT count(`id`) AS 'smileys' FROM `fcms_board_posts` WHERE `date` >= '" . $this->lastmonth_beg . "' AND `date` <= '" . $this->lastmonth_end . "' AND `user` = $user AND (`post` LIKE '%:smile:%' OR `post` LIKE '%:biggrin:%' OR  `post` LIKE '%:clap:%' OR `post` LIKE '%:hrmm:%' OR `post` LIKE '%:tongue:%' OR `post` LIKE '%:wink:%' OR `post` LIKE '%:doh:%')") or die('<h1>Smileys Error (admin.class.php 276)</h1>' . mysql_error());
			$row = $this->db->get_row();
			if ($this_user_post_count > 0) {
				if ((($row['smileys'] / $this_user_post_count) * 100)  > $most_smileys && $this_user_post_count >= 5) {
					$most_smileys_user = $user;
					$most_smileys = ($row['smileys'] / $this_user_post_count) * 100;
				}
			}
		}
		if ($most_smileys_user < 1) { $most_smileys_user = 1; }
		$this->db->query("UPDATE `fcms_user_awards` SET `user` = '$most_smileys_user', `value` = '" . date('n') . "', `count` = '$most_smileys' WHERE `type` = 'mostsmileys'") or die('<h1>Awards Error (admin.class.php 285)</h1>' . mysql_error());
	}

	function getStartedMostThreads () { 
		$this->db->query("SELECT `started_by` , count(*) AS c FROM (SELECT DISTINCT t.`id` , `subject` , `started_by` FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p WHERE t.`id` = p.`thread` AND t.`started_by` = p.`user` AND p.`date` >= '2007-06-01 00:00:00' AND p.`date` <= '2007-06-31 24:59:59') AS z GROUP BY `started_by` ORDER BY c DESC LIMIT 5") or die('<h1>Thread Error (admin.class.php 289)</h1>' . mysql_error());
		$i = 1;
		while($row=$this->db->get_row()) {
			$this->db2->query("UPDATE `fcms_user_awards` SET `user` = '" . $row['started_by'] . "', `value` = '$i', `count` = '" . $row['c'] . "' WHERE `type` = 'startedmostthreads' AND `value` = '$i'") or die('<h1>Award Error (admin.class.php 292)</h1>' . mysql_error());
			$i++;
		}
	}


} ?>