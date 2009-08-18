<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class Admin {

    var $db;
    var $db2;
    var $db3;
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
        $this->db3 = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id";
        $this->db->query($sql) or displaySQLError('Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
    }

    function showThreads ($type, $page = '0') {
        global $LANG;
        $from = (($page * 25) - 25);
        if ($type == 'announcement') {
            echo "<table id=\"threadlist\" cellpadding=\"0\" cellspacing=\"0\">\n\t\t\t\t<thead><tr><th class=\"images\">&nbsp;</th><th class=\"subject\">".$LANG['subject']."</th><th class=\"replies\">".$LANG['replies']."</th><th class=\"views\">".$LANG['views']."</th><th class=\"updated\">".$LANG['last_updated']."</th></tr></thead>\n\t\t\t\t<tbody>\n";
            $sql = "SELECT fcms_board_threads.id, subject, started_by, updated, updated_by, views, user FROM fcms_board_threads, fcms_board_posts WHERE fcms_board_threads.id = fcms_board_posts.thread AND subject LIKE '#ANOUNCE#%' GROUP BY fcms_board_threads.id ORDER BY updated DESC";
            $this->db->query($sql) or displaySQLError('Announcements Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        } else {
            $sql = "SELECT fcms_board_threads.id, subject, started_by, updated, updated_by, views, user FROM fcms_board_threads, fcms_board_posts WHERE fcms_board_threads.id = fcms_board_posts.thread AND subject NOT LIKE '#ANOUNCE#%' GROUP BY fcms_board_threads.id ORDER BY updated DESC LIMIT " . $from . ", 25";
            $this->db->query($sql) or displaySQLError('Threads Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        }
        $alt = 0;
        while($row=$this->db->get_row()) {
            $started_by = getUserDisplayName($row['started_by']);
            $updated_by = getUserDisplayName($row['updated_by']);
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
        $sql = "SELECT t.`id`, p.`user`, `subject`, `started_by`, `post` FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p WHERE t.`id` = $thread_id LIMIT 1";
        $this->db->query($sql) or displaySQLError('Edit Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $row = $this->db->get_row(); ?>
        <form method="post" action="board.php">
            <h2><?php echo $LANG['edit_thread']; ?></h2>
            <p><label for="subject"><?php echo $LANG['subject']; ?></label>: <input type="text" name="subject" id="subject" title="Message Subject" size="50" value="<?php $pos = strpos($row['subject'], '#ANOUNCE#'); if ($pos !== false) { $subject = substr($row['subject'], 9, strlen($row['subject'])-9); echo $subject; } else { echo $row['subject']; } ?>"/></p>
            <p><label for="showname"><?php echo $LANG['name']; ?></label>: <input type="text" disabled="disabled" name="showname" id="showname" title="Your Name" value="<?php echo getUserDisplayName($row['started_by']); ?>" size="50"/></p>
            <div><input type="hidden" name="name" id="name" value="<?php echo $row['user']; ?>"/></div>
            <p><label for="sticky"><?php echo $LANG['admin_tools']; ?></label>: <input type="checkbox" <?php $pos = strpos($row['subject'], '#ANOUNCE#'); if ($pos !== false) { echo "checked=\"checked\" "; } ?>name="sticky" id="sticky" value="sticky"/><?php echo $LANG['make_announcement']; ?></p>
            <p><textarea disabled="disabled" name="post" id="post" rows="10" cols="63"><?php echo $row['post']; ?></textarea></p>
            <div><input type="hidden" name="threadid" id="threadid" value="<?php echo $thread_id; ?>"/></div>
            <p><input type="submit" name="edit_submit" id="edit_submit" value="<?php echo $LANG['edit_thread']; ?>"/></p>
        </form>
        <?php
    }

    function getNumberOfPosts ($thread_id) {
        $sql = "SELECT COUNT(*) AS c FROM `fcms_board_posts` WHERE `thread` = $thread_id";
        $this->db2->query($sql) or displaySQLError('# of Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $row=$this->db2->get_row();
        return $row['c'];
    }

    function getSortOrder ($user_id) {
        $sql = "SELECT `boardsort` FROM `fcms_users` WHERE `id` = $user_id";
        $this->db2->query($sql) or displaySQLError('Sort Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $row=$this->db2->get_row();
        return $row['boardsort'];
    }

    function getShowAvatar ($user_id) {
        $sql = "SELECT `showavatar` FROM `fcms_users` WHERE `id` = $user_id";
        $this->db2->query($sql) or displaySQLError('Avatar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $row=$this->db2->get_row();
        return $row['showavatar'];
    }

    function getUserPostCountById ($user_id) {
        $sql = "SELECT * FROM `fcms_board_posts`";
        $this->db2->query($sql) or displaySQLError('Post Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $total=$this->db2->count_rows();
        $sql = "SELECT COUNT(user) AS c FROM `fcms_board_posts` WHERE `user` = $user_id";
        $this->db2->query($sql) or displaySQLError('Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
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
            $sql = "SELECT COUNT(id) AS c FROM `fcms_board_threads`";
            $this->db2->query($sql) or displaySQLError('Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $row=$this->db2->get_row();
            $total_pages = ceil($row['c'] / 25); 
        } else {
            $sql = "SELECT COUNT(id) AS c FROM `fcms_board_posts` WHERE `thread` = $thread_id";
            $this->db2->query($sql) or displaySQLError('Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
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
        $poll_exists = true;
        if ($pollid > 0) {
            $sql = "SELECT `question`, o.`id`, `option` FROM `fcms_polls` AS p, `fcms_poll_options` AS o WHERE p.`id` = o.`poll_id` AND p.`id` = $pollid";
            $this->db->query($sql) or displaySQLError('Poll Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            if ($this->db->count_rows() <= 0) { $poll_exists = false; }
        } else {
            $sql = "SELECT MAX(`id`) AS c FROM `fcms_polls`";
            $this->db->query($sql) or displaySQLError('Max Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $row = $this->db->get_row();
            $latest_poll_id = $row['c'];
            if (is_null($row['c'])) {
                $poll_exists = false;
                $this->displayAddPollForm();
            } else {
                $sql = "SELECT `question`, o.`id`, `option` FROM `fcms_polls` AS p, `fcms_poll_options` AS o WHERE p.`id` = o.`poll_id` AND p.`id` = $latest_poll_id";
                $this->db->query($sql) or displaySQLError('Poll Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            }
        }
        if ($poll_exists) {
            echo "<br/><h3>".$LANG['edit_polls']."</h3>";
            echo "<form id=\"editform\" name=\"editform\" action=\"?page=admin_polls\" method=\"post\"><fieldset><legend>".$LANG['edit_cur_poll']."</legend>";
            $i = 1;
            while ($row = $this->db->get_row()) {
                if ($i < 2) { echo "<h3>" . $row['question'] . "</h3>"; }
                echo "<div class=\"field-row\"><div class=\"field-label\"><label for=\"show$i\">".$LANG['option']." $i:</label></div> <div class=\"field-widget\"><input type=\"text\" name=\"show$i\" id=\"show$i\" ";
                if ($i < 3) { echo "class=\"required\""; } else { echo "class=\"\""; }
                echo " size=\"50\" value=\"" . htmlentities($row['option'], ENT_COMPAT, 'UTF-8') . "\"/><input type=\"hidden\" name=\"option$i\" class=\"\" value=\"" . $row['id'] . "\"/>";
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
    }

    function displayAddPollForm() { 
        global $show, $LANG;
        $show = false; ?>
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
        $sql = "SELECT *, count(`thread`) AS 'thread_count' FROM `fcms_board_posts` WHERE `date` >= '" . $this->lastmonth_beg . "' AND `date` <= '" . $this->lastmonth_end . "' GROUP BY `thread` ORDER BY 'thread_count' DESC LIMIT 1";
        $this->db->query($sql) or displaySQLError('Thread Starter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        while($row=$this->db->get_row()) {
            $sql = "UPDATE `fcms_user_awards` SET `user` = '" . $row['user'] . "', `value` = '" . $row['thread'] . "', `count` = '" . $row['thread_count'] . "' WHERE `type` = 'topthreadstarter'";
            $this->db2->query($sql) or displaySQLError('Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        }
        if ($this->db->count_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    function getMostViewedPhoto () {
        $sql = "SELECT `id`, `user`, `views` FROM `fcms_gallery_photos` WHERE date >= '" . $this->lastmonth_beg . "' AND date <= '" . $this->lastmonth_end . "' ORDER BY `views` DESC LIMIT 1";
        $this->db->query($sql) or displaySQLError('Viewed Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        while($row=$this->db->get_row()) {
            $sql = "UPDATE `fcms_user_awards` SET `user` = '" . $row['user'] . "', `value` = '" . $row['id'] . "', `count` = '" . $row['views'] . "' WHERE `type` = 'topviewedphoto'";
            $this->db2->query($sql) or displaySQLError('Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        }
    }

    function getTopPosters () {
        $sql = "SELECT `user`, count(`user`) AS 'post_count' FROM `fcms_board_posts` AS p WHERE `date` >= '" . $this->lastmonth_beg . "' AND `date` <= '" . $this->lastmonth_end . "' GROUP BY `user` ORDER BY 'post_count' DESC LIMIT 5";
        $this->db->query($sql) or displaySQLError('Top Posters Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $i = 1;
        while($row=$this->db->get_row()) {
            $sql = "UPDATE `fcms_user_awards` SET `user` = '" . $row['user'] . "', `value` = '$i', `count` = '" . $row['post_count'] . "' WHERE `type` = 'top5poster' AND `value` = '$i'";
            $this->db2->query($sql) or displaySQLError('Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $i++;
        }
    }

    function getTopPhotoSubmitters () {
        $sql = "SELECT `user`, count(*) AS c FROM `fcms_gallery_photos` WHERE `date` >= '" . $this->lastmonth_beg . "' AND `date` <= '" . $this->lastmonth_end . "' GROUP BY `user` ORDER BY c DESC LIMIT 5";
        $this->db->query($sql) or displaySQLError('Submitters Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $i = 1;
        while($row=$this->db->get_row()) {
            $sql = "UPDATE `fcms_user_awards` SET `user` = '" . $row['user'] . "', `value` = '$i', `count` = '" . $row['c'] . "' WHERE `type` = 'top5photo' AND `value` = '$i'";
            $this->db2->query($sql) or displaySQLError('Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $i++;
        }
    }

    function getMostSmileys () {
        $most_smileys = '0';
        $most_smileys_user = '0';
        $sql = "SELECT `id` FROM `fcms_users` WHERE `username` != 'SITENEWS' AND `username` != 'test' AND `username` != 'reunion' ORDER BY `id`";
        $this->db->query($sql) or displaySQLError('Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $users;
        $i = 1;
        while ($row = $this->db->get_row()) {
            $users[$i] = $row['id'];
            $i++;
        }
        foreach ($users as $user) {
            $sql = "SELECT count(`user`) AS 'post_count' FROM `fcms_board_posts` AS p WHERE `date` >= '" . $this->lastmonth_beg . "' AND `date` <= '" . $this->lastmonth_end . "' AND `user` = $user GROUP BY `user` ORDER BY post_count DESC";
            $this->db->query($sql) or displaySQLError('Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $row = $this->db->get_row();
            $this_user_post_count = $row['post_count'];
            $sql = "SELECT count(`id`) AS 'smileys' FROM `fcms_board_posts` WHERE `date` >= '" . $this->lastmonth_beg . "' AND `date` <= '" . $this->lastmonth_end . "' AND `user` = $user AND (`post` LIKE '%:smile:%' OR `post` LIKE '%:biggrin:%' OR  `post` LIKE '%:clap:%' OR `post` LIKE '%:hrmm:%' OR `post` LIKE '%:tongue:%' OR `post` LIKE '%:wink:%' OR `post` LIKE '%:doh:%')";
            $this->db->query($sql) or displaySQLError('Smileys Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $row = $this->db->get_row();
            if ($this_user_post_count > 0) {
                if ((($row['smileys'] / $this_user_post_count) * 100)  > $most_smileys && $this_user_post_count >= 5) {
                    $most_smileys_user = $user;
                    $most_smileys = ($row['smileys'] / $this_user_post_count) * 100;
                }
            }
        }
        if ($most_smileys_user < 1) { $most_smileys_user = 1; }
        $sql = "UPDATE `fcms_user_awards` SET `user` = '$most_smileys_user', `value` = '" . date('n') . "', `count` = '$most_smileys' WHERE `type` = 'mostsmileys'";
        $this->db->query($sql) or displaySQLError('Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
    }

    function getStartedMostThreads () {
        $sql = "SELECT `started_by` , count(*) AS c FROM (SELECT DISTINCT t.`id` , `subject` , `started_by` FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p WHERE t.`id` = p.`thread` AND t.`started_by` = p.`user` AND p.`date` >= '2007-06-01 00:00:00' AND p.`date` <= '2007-06-31 24:59:59') AS z GROUP BY `started_by` ORDER BY c DESC LIMIT 5";
        $this->db->query($sql) or displaySQLError('Most Threads Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $i = 1;
        while($row=$this->db->get_row()) {
            $sql = "UPDATE `fcms_user_awards` SET `user` = '" . $row['started_by'] . "', `value` = '$i', `count` = '" . $row['c'] . "' WHERE `type` = 'startedmostthreads' AND `value` = '$i'";
            $this->db2->query($sql) or displaySQLError('Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $i++;
        }
    }
    
    /**
     * displayAdminConfig
     *
     * Displays the forms for changing/configuring the sitename,
     * email, auto activation, user defaults and sections.
     */
    function displayAdminConfig ()
    {
        global $LANG, $cfg_mysql_db;
        
        // General Config
        $sql = "SELECT * FROM `fcms_config`";
        $this->db->query($sql) or displaySQLError(
            'Site Info Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $row = $this->db->get_row();
        
        $activate_list = array(
            "0" => $LANG['admin_activation'],
            "1" => $LANG['auto_activation']
        );
        $activate_options = buildHtmlSelectOptions($activate_list, $row['auto_activate']);
        
        echo <<<HTML
        <form action="config.php" method="post">
        <fieldset class="general_cfg">
            <legend>{$LANG['site_info']}</legend>
            <div style="text-align:right"><a href="#" onclick="$('site_info').toggle(); return false">{$LANG['show_hide']}</a></div>
            <div id="site_info" style="display:none">
                <div class="field-row clearfix">
                    <div class="field-label"><label for="sitename"><b>{$LANG['site_name']}</b></label></div>
                    <div class="field-widget">
                        <input type="text" name="sitename" size="50" value="{$row['sitename']}"/>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="contact"><b>{$LANG['contact']}</b></label></div>
                    <div class="field-widget">
                        <input type="text" id="contact" name="contact" size="50" value="{$row['contact']}"/>
                    </div>
                </div>
                <script type="text/javascript">
                    var email = new LiveValidation('contact', {validMessage: "", wait: 500});
                    email.add(Validate.Email, {failureMessage: "{$LANG['lv_bad_email']}"});
                    email.add(Validate.Length, {minimum: 10});
                </script>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="activation"><b>{$LANG['account_activation']}</b></label></div>
                    <div class="field-widget">
                        <select name="activation">
                            {$activate_options}
                        </select>
                    </div>
                </div>
                <p><input type="submit" id="submit-sitename" name="submit-sitename" value="{$LANG['save']}"/></p>
            </div>
        </fieldset>
        </form>
HTML;
        
        // Defaults Config
        $sql = "DESCRIBE `fcms_user_settings`";
        $this->db3->query($sql) or displaySQLError(
            'Describe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($drow = $this->db3->get_row()) {
            if ($drow['Field'] == 'theme') {
                $default_theme = $drow['Default'];
            }
            if ($drow['Field'] == 'showavatar') {
                $default_showavatar = $drow['Default'];
            }
            if ($drow['Field'] == 'displayname') {
                $default_displayname = $drow['Default'];
            }
            if ($drow['Field'] == 'frontpage') {
                $default_frontpage = $drow['Default'];
            }
            if ($drow['Field'] == 'timezone') {
                $default_tz = $drow['Default'];
            }
            if ($drow['Field'] == 'dst') {
                $default_dst = $drow['Default'];
            }
            if ($drow['Field'] == 'boardsort') {
                $default_boardsort = $drow['Default'];
            }
        }
        // Themes
        $dir = "../themes/";
        $theme_options = '';
        if (is_dir($dir))    {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (filetype($dir . $file) === "dir" && 
                        $file !== "." && 
                        $file !== ".." && 
                        $file !== "smileys"
                    ) {
                        $arr[] = $file;
                    }
                }
                closedir($dh);
                sort($arr);
                foreach($arr as $file) {
                    $theme_options .= "<option value=\"$file\"";
                    if ($default_theme == $file) {
                        $theme_options .= " selected=\"selected\"";
                    }
                    $theme_options .= ">$file</option>";
                }
            }
        }
        // Show Avatars
        $avatars_options = '<input type="radio" name="showavatar" id="showavatar_yes" '
            . 'value="yes"';
        if ($default_showavatar == 1) { $avatars_options .= ' checked="checked"'; }
        $avatars_options .= '><label class="radio_label" for="showavatar_yes"> '
            . $LANG['yes'] . '</label><br><input type="radio" name="showavatar" '
            . 'id="showavatar_no" value="no"';
        if ($default_showavatar == 0) { $avatars_options .= ' checked="checked"'; }
        $avatars_options .= '><label class="radio_label" for="showavatar_no"> '
            . $LANG['no'] . '</label>';
        // Display Name
        $displayname_list = array(
            "1" => $LANG['first_name'],
            "2" => $LANG['first_last_name'],
            "3" => $LANG['username']
        );
        $displayname_options = buildHtmlSelectOptions($displayname_list, $default_displayname);
        // Frontpage
        $frontpage_list = array(
            "1" => $LANG['all_by_date'],
            "2" => $LANG['last_5_sections']
        );
        $frontpage_options = buildHtmlSelectOptions($frontpage_list, $default_frontpage);
        // Timezone
        $tz_list = array(
            "-12 hours" => $LANG['tz_12'],
            "-11 hours" => $LANG['tz_11'],
            "-10 hours" => $LANG['tz_10'],
            "-9 hours" => $LANG['tz_9'],
            "-8 hours" => $LANG['tz_8'],
            "-7 hours" => $LANG['tz_7'],
            "-6 hours" => $LANG['tz_6'],
            "-5 hours" => $LANG['tz_5'],
            "-4 hours" => $LANG['tz_4'],
            "-3 hours -30 minutes" => $LANG['tz_33'],
            "-3 hours" => $LANG['tz_3'],
            "-2 hours" => $LANG['tz_2'],
            "-1 hours" => $LANG['tz_1'],
            "+0 hours" => $LANG['tz0'],
            "+1 hours" => $LANG['tz1'],
            "+2 hours" => $LANG['tz2'],
            "+3 hours" => $LANG['tz3'],
            "+3 hours 30 minutes" => $LANG['tz33'],
            "+4 hours" => $LANG['tz4'],
            "+4 hours 30 minutes" => $LANG['tz43'],
            "+5 hours" => $LANG['tz5'],
            "+5 hours 30 minutes" => $LANG['tz53'],
            "+6 hours" => $LANG['tz6'],
            "+7 hours" => $LANG['tz7'],
            "+8 hours" => $LANG['tz8'],
            "+9 hours" => $LANG['tz9'],
            "+9 hours 30 minutes" => $LANG['tz93'],
            "+10 hours" => $LANG['tz10'],
            "+11 hours" => $LANG['tz11'],
            "+12 hours" => $LANG['tz12']
        );
        $tz_options = buildHtmlSelectOptions($tz_list, $default_tz);
        // DST
        $dst_options = '<input type="radio" name="dst" id="dst_on" '
            . 'value="on"';
        if ($default_dst == 1) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_on"> ' . $LANG['on'] . '</label><br>'
            . '<input type="radio" name="dst" id="dst_off" value="off"';
        if ($default_dst == 0) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_off"> ' . $LANG['off'] . '</label>';
        // Board Sort
        $boardsort_list = array(
            "ASC" => $LANG['msgs_bottom'],
            "DESC" => $LANG['msgs_top']
        );
        $boardsort_options = buildHtmlSelectOptions($boardsort_list, $default_boardsort);
        
        echo <<<HTML
        <form enctype="multipart/form-data" action="config.php" method="post">
        <fieldset class="default_cfg">
            <legend>{$LANG['defaults']}</legend>
            <div style="text-align:right"><a href="#" onclick="$('defaults').toggle(); return false">{$LANG['show_hide']}</a></div>
            <div id="defaults" style="display:none">
                <div class="field-row clearfix">
                    <div class="field-label"><label for="theme"><b>{$LANG['theme']}</b></label></div>
                    <div class="field-widget">
                        <select name="theme" id="theme">
                            {$theme_options}
                        </select>
                    </select>
                </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="showavatar"><b>{$LANG['show_avatars']}</b></label></div>
                    <div class="field-widget">
                        {$avatars_options}
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="displayname"><b>{$LANG['display_name']}</b></label></div>
                    <div class="field-widget">
                        <select name="displayname" id="displayname" title="{$LANG['title_display']}">
                            {$displayname_options}
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="frontpage"><b>{$LANG['frontpage']}</b></label></div>
                    <div class="field-widget">
                        <select name="frontpage" id="frontpage" title="{$LANG['title_frontpage']}">
                            {$frontpage_options}
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="timezone"><b>{$LANG['timezone']}</b></label></div>
                    <div class="field-widget">
                        <select name="timezone" id="timezone" title="{$LANG['title_timezone']}">
                            {$tz_options}
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="dst"><b>{$LANG['daylight']}</b></label></div>
                    <div class="field-widget">
                        {$dst_options}
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="boardsort"><b>{$LANG['sort_msgs']}</b></label></div>
                    <div class="field-widget">
                        <select name="boardsort" id="boardsort" title="{$LANG['title_sort_msgs']}">
                            {$boardsort_options}
                        </select>
                    </div>
                </div>
                <p>
                    <input type="submit" id="submit-defaults" name="submit-defaults" value="{$LANG['save']}"/> &nbsp;
                    <input type="checkbox" name="changeAll" id="changeAll"/> 
                    <label for="changeAll">{$LANG['update_cur_users']}</label>
                </p>
            </div>
        </fieldset>
        </form>
HTML;
        
        // Sections Config
        $sql = "SHOW TABLES FROM `$cfg_mysql_db`";
        $this->db2->query($sql) or displaySQLError(
            'Show Tables Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $recipes_exists = false;
        $news_exists = false;
        $prayers_exists = false;
        $documents_exists = false;
        if ($this->db2->count_rows() > 0) {
            while($r = $this->db2->get_row()) {
                if ($r[0] == 'fcms_recipes') { $recipes_exists = true; }
                if ($r[0] == 'fcms_news') { $news_exists = true; }
                if ($r[0] == 'fcms_prayers') { $prayers_exists = true; }
                if ($r[0] == 'fcms_documents') { $documents_exists = true; }
            }
        }
        
        if (!$news_exists) {
            $news = "<a class=\"add\" href=\"?addsection=news\">" . $LANG['add'] . "</a>";
        } else {
            $news = "<small>" . $LANG['already_added'] . "</small>";
        }
        if (!$prayers_exists) {
            $prayers = "<a class=\"add\" href=\"?addsection=prayers\">" . $LANG['add'] . "</a>";
        } else {
            $prayers = "<small>" . $LANG['already_added'] . "</small>";
        }
        if (!$recipes_exists) {
            $recipes = "<a class=\"add\" href=\"?addsection=recipes\">" . $LANG['add'] . "</a>";
        } else {
            $recipes = "<small>" . $LANG['already_added'] . "</small>";
        }
        if (!$documents_exists) {
            $documents = "<a class=\"add\" href=\"?addsection=documents\">" . $LANG['add'] . "</a>";
        } else {
            $documents = "<small>" . $LANG['already_added'] . "</small>";
        }
        
        echo <<<HTML
        <form action="config.php" method="post">
        <fieldset class="sections_cfg">
            <legend>{$LANG['sections']}</legend>
            <div style="text-align:right"><a href="#" onclick="$('sections').toggle(); return false">{$LANG['show_hide']}</a></div>
            <div id="sections" style="display:none">
                <div style="width: 90%; text-align: right;">
                    <a class="help" href="../help.php#adm-sections-add">{$LANG['link_help']}</a>
                </div>
                <h3>{$LANG['opt_sections']}</h3>
                <div class="cfg-sections clearfix">
                    <span class="newnews">{$LANG['link_familynews']}</span>
                    {$news}
                </div>
                <div class="cfg-sections clearfix">
                    <span class="newprayer">{$LANG['link_prayers']}</span>
                    {$prayers}
                </div>
                <div class="cfg-sections clearfix">
                    <span class="newrecipe">{$LANG['link_recipes']}</span>
                    {$recipes}
                </div>
                <div class="cfg-sections clearfix">
                    <span class="newdocument">{$LANG['link_documents']}</span>
                    {$documents}
                </div>
                <p>&nbsp;</p>
                <div style="width: 90%; text-align:right;">
                    <a class="help" href="../help.php#adm-sections-nav">{$LANG['link_help']}</a>
                </div>
                <h3>{$LANG['navigation']}</h3>
HTML;
        $i = 0;
        $this->displaySectionDropdown('section1', $row['section1']);
        $this->displaySectionDropdown('section2', $row['section2']);
        $this->displaySectionDropdown('section3', $row['section3']);
        $this->displaySectionDropdown('section4', $row['section4']);
        echo '<p><input type="submit" id="submit-sections" name="submit-sections" value="' . $LANG['save'] . '"/></p>';
        echo '</div></fieldset></form>';
        
        // Photo Gallery Config
        // TODO: move to it's own admin section
        $full_size_list = array(
            "0" => $LANG['full_size_off'],
            "1" => $LANG['full_size_on']
        );
        $full_size_options = buildHtmlSelectOptions($full_size_list, $row['full_size_photos']);
        
        echo <<<HTML
        <form action="config.php" method="post">
        <fieldset class="gallery_cfg">
            <legend>{$LANG['link_gallery']}</legend>
            <div style="text-align:right"><a href="#" onclick="$('gallery').toggle(); return false">{$LANG['show_hide']}</a></div>
            <div id="gallery" style="display:none">
                <p class="info-alert">{$LANG['full_size_photo_info']}</p>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="full_size_photos"><b>{$LANG['full_size_photos']}</b></label></div>
                    <div class="field-widget">
                        <select name="full_size_photos">
                            {$full_size_options}
                        </select>
                    </div>
                </div>
                <p><input type="submit" id="submit-gallery" name="submit-gallery" value="{$LANG['save']}"/></p>
            </div>
        </fieldset>
        </form>
HTML;
    }

    function displaySectionDropdown ($which_nav, $which_selected) { 
        global $LANG; ?>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="<?php echo $which_nav; ?>"><b><?php echo $LANG[$which_nav]; ?></b></label></div>
                    <div class="field-widget">
                        <select name="<?php echo $which_nav; ?>">
                            <?php 
                            if (tableExists('fcms_news')) {
                                echo '<option value="familynews"';
                                if ($which_selected == 'familynews') {
                                    echo ' selected="selected"';
                                }
                                echo '>' . $LANG['link_familynews'] . '</option>';
                            }
                            if (tableExists('fcms_recipes')) {
                                echo '<option value="recipes"';
                                if ($which_selected == 'recipes') {
                                    echo ' selected="selected"';
                                }
                                echo '>' . $LANG['link_recipes'] . '</option>';
                            }
                            if (tableExists('fcms_documents')) {
                                echo '<option value="documents"';
                                if ($which_selected == 'documents') {
                                    echo ' selected="selected"';
                                }
                                echo '>' . $LANG['link_documents'] . '</option>';
                            }
                            if (tableExists('fcms_prayers')) {
                                echo '<option value="prayers"';
                                if ($which_selected == 'prayers') {
                                    echo ' selected="selected"';
                                }
                                echo '>' . $LANG['link_prayers'] . '</option>';
                            }
                            $i = substr($which_nav, 7);
                            echo '<option value="none'.$i.'"';
                            $pos = strpos($which_selected, "none");
                            if ($pos !== false) {
                                echo ' selected="selected"';
                            }
                            echo '>' . $LANG['none'] . '</option>';
                            ?>
                        </select>
                    </div>
                </div>
    <?php
    }

} ?>
