<?php
include_once('database_class.php');
include_once('util_inc.php');

class Admin {

    var $db;
    var $db2;
    var $db3;
    var $tz_offset;
    var $lastmonth_beg;
    var $lastmonth_end;
    var $current_user_id;

    function Admin ($current_user_id, $type, $host, $database, $user, $pass)
    {
        $this->current_user_id = $current_user_id;
        $this->lastmonth_beg = gmdate('Y-m', mktime(0, 0, 0, gmdate('m')-1, 1, gmdate('Y'))) . "-01 00:00:00";
        $this->lastmonth_end = gmdate('Y-m', mktime(0, 0, 0, gmdate('m')-1, 1, gmdate('Y'))) . "-31 24:59:59";
        $this->db = new database($type, $host, $database, $user, $pass);
        $this->db2 = new database($type, $host, $database, $user, $pass);
        $this->db3 = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id";
        $this->db->query($sql) or displaySQLError('Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
        bindtextdomain('messages', '.././language');
    }

    function showThreads ($type, $page = '0')
    {
        $from = (($page * 25) - 25);
        if ($type == 'announcement') {
            echo '
            <table id="threadlist" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th class="images">&nbsp;</th>
                        <th class="subject">'._('Subject').'</th>
                        <th class="replies">'._('Replies').'</th>
                        <th class="views">'._('Views').'</th>
                        <th class="updated">'._('Last Updated').'</th>
                    </tr>
                </thead>
                <tbody>';
            $sql = "SELECT t.`id`, `subject`, `started_by`, `updated`, `updated_by`, `views`, `user` 
                    FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p 
                    WHERE t.`id` = p.`thread` 
                    AND `subject` LIKE '#ANOUNCE#%' 
                    GROUP BY t.`id` 
                    ORDER BY `updated` DESC";
            $this->db->query($sql) or displaySQLError(
                'Announcements Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        } else {
            $sql = "SELECT t.`id`, `subject`, `started_by`, `updated`, `updated_by`, `views`, `user` 
                    FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p 
                    WHERE t.`id` = p.`thread` 
                    AND `subject` NOT LIKE '#ANOUNCE#%' 
                    GROUP BY t.`id` 
                    ORDER BY `updated` DESC LIMIT " . $from . ", 25";
            $this->db->query($sql) or displaySQLError(
                'Threads Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
        $alt = 0;
        while ($row=$this->db->get_row()) {
            $started_by = getUserDisplayName($row['started_by']);
            $updated_by = getUserDisplayName($row['updated_by']);
            $subject = $row['subject'];
            if ($type == 'announcement') {
                $subject = substr($subject, 9, strlen($subject)-9);
                $subject = '<small><b>'._('Announcement').': </b></small>'.$subject;
                $tr_class = 'announcement';
            } else {
                if ($alt % 2 == 0) {
                    $tr_class = '';
                } else {
                    $tr_class = 'alt';
                }
            }

            // Updated Today
            if (
                gmdate('n/d/Y', strtotime($row['updated'] . $this->tz_offset)) == 
                gmdate('n/d/Y', strtotime(date('n/d/Y') . $this->tz_offset))
            ) {
                if ($type == 'announcement') {
                    $up_class = 'announcement_today';
                } else {
                    $up_class = 'today';
                }
                $last_updated = sprintf(_('Today at %s'), gmdate('h:ia', strtotime($row['updated'] . $this->tz_offset)))
                    . "<br/>" . sprintf(_('by %s'), $updated_by);

            // Updated Yesterday
            } elseif (
                gmdate('n/d/Y', strtotime($row['updated'] . $this->tz_offset)) == 
                gmdate('n/d/Y', strtotime(date('n/d/Y', strtotime(date('Y-m-d H:i:s') . $this->tz_offset)) . "-24 hours"))
            ) {
                if ($type == 'announcement') {
                    $up_class = 'announcement_yesterday';
                } else {
                    $up_class = 'yesterday';
                }
                $last_updated = sprintf(_('Yesterday at %s'), gmdate('h:ia', strtotime($row['updated'] . $this->tz_offset)))
                    . "<br/>" . sprintf(_('by %s'), $updated_by);

            // Updated older than yesterday
            } else {
                if ($type == 'announcement') {
                    $up_class = 'announcement';
                } else {
                    $up_class = '';
                }
                $last_updated = gmdate('m/d/Y h:ia', strtotime($row['updated'] . $this->tz_offset))
                    . "<br/>" . sprintf(_('by %s'), $updated_by);
            }
            $replies = $this->getNumberOfPosts($row['id']) - 1;
            
            // Display Row
            echo '
                    <tr class="'.$tr_class.'">
                        <td class="images"><div class="'.$up_class.'"&nbsp;</div></td>
                        <td class="subject">
                            '.$subject.' 
                            <small>
                                <a class="edit_thread" href="board.php?edit='.$row['id'].'">'._('Edit').'</a> 
                                <a class="del_thread" href="board.php?del='.$row['id'].'">'._('Delete').'</a>
                            </small><br/>
                            '.$started_by.'
                        </td>
                        <td class="replies">'.$replies.'</td>
                        <td class="views">'.$row['views'].'</td>
                        <td class="updated">
                            '.$last_updated.'
                        </td>
                    </tr>';
            $alt++;
        }
        if ($type == 'thread') {
            echo '
                </tbody>
            </table>
            <div class="top clearfix"><a href="#top">'._('Back to Top').'</a></div>';
            $this->displayPages($page);
        }
    }

    function displayEditThread ($thread_id)
    {
        $sql = "SELECT t.`id`, p.`user`, `subject`, `started_by`, `post` FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p WHERE t.`id` = $thread_id LIMIT 1";
        $this->db->query($sql) or displaySQLError('Edit Thread Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $row = $this->db->get_row();
        $pos = strpos($row['subject'], '#ANOUNCE#');
        $subject = $row['subject'];
        if ($pos !== false) {
            $subject = substr($row['subject'], 9, strlen($row['subject'])-9);
        }
        $displayname = getUserDisplayName($row['started_by']);
        $pos = strpos($row['subject'], '#ANOUNCE#');
        $chk = '';
        if ($pos !== false) {
            $chk = 'checked="checked"';
        }
        echo '
            <form method="post" action="board.php">
                <fieldset>
                    <legend><span>'._('Edit Thread').'</span></legend>
                    <p>
                        <label for="subject">'._('Subject').':</label>
                        <input class="frm_text" type="text" name="subject" id="subject" size="50" value="'.$subject.'"/>
                    </p>
                    <p>
                        <label for="showname">'._('Name').':</label>
                        <input type="text" disabled="disabled" name="showname" id="showname" size="50" value="'.$displayname.'"/>
                    </p>
                    <div><input type="hidden" name="name" id="name" value="'.$row['user'].'"/></div>
                    <p>
                        '._('Admin Tools').':&nbsp;&nbsp;
                        <input type="checkbox" '.$chk.'  name="sticky" id="sticky" value="sticky"/>
                        <label for="sticky">'._('Make Announcement').'</label>
                    </p>
                    <p><textarea disabled="disabled" name="post" id="post" rows="10" cols="63">'.$row['post'].'</textarea></p>
                    <div><input type="hidden" name="threadid" id="threadid" value="'.$thread_id.'"/></div>
                    <p>
                        <input class="sub1" type="submit" name="edit_submit" id="edit_submit" value="'._('Edit').'"/> 
                        '._('or').' 
                        <a href="board.php">'._('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    function getNumberOfPosts ($thread_id)
    {
        $sql = "SELECT COUNT(*) AS c FROM `fcms_board_posts` WHERE `thread` = $thread_id";
        $this->db2->query($sql) or displaySQLError('# of Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $row=$this->db2->get_row();
        return $row['c'];
    }

    function getSortOrder ($user_id)
    {
        $sql = "SELECT `boardsort` FROM `fcms_users` WHERE `id` = $user_id";
        $this->db2->query($sql) or displaySQLError('Sort Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $row=$this->db2->get_row();
        return $row['boardsort'];
    }

    function getShowAvatar ($user_id)
    {
        $sql = "SELECT `showavatar` FROM `fcms_users` WHERE `id` = $user_id";
        $this->db2->query($sql) or displaySQLError('Avatar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
        $row=$this->db2->get_row();
        return $row['showavatar'];
    }

    function getUserPostCountById ($user_id)
    {
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

    function displayPages ($page = '1', $thread_id = '0')
    {
        if ($thread_id < 1) {
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
        if ($total_pages > 1) {
            echo '
            <div class="pages clearfix">
                <ul>';
            $url = '';
            if ($thread_id != 0) {
                $url = 'thread='.$thread_id.'&amp;';
            }
            if ($page > 1) { 
                $prev = ($page - 1); 
                echo '
                    <li><a title="'._('First Page').'" class="first" href="board.php?'.$url.'page=1"></a></li>
                    <li><a title="'._('Previous Page').'" class="previous" href="board.php?'.$url.'page='.$prev.'"></a></li>'; 
            } 
            if ($total_pages > 8) {
                if ($page > 2) {
                    for ($i = ($page-2); $i <= ($page+5); $i++) {
                        $class = ($page == $i) ? 'class="current"' : '';
                        if ($i <= $total_pages) {
                            echo '
                    <li><a href="board.php?'.$url.'page='.$i.'" '.$class.'>'.$i.'</a></li>';
                        }
                    } 
                } else {
                    for ($i = 1; $i <= 8; $i++) {
                        $class = ($page == $i) ? 'class="current"' : '';
                        echo '
                    <li><a href="board.php?'.$url.'page='.$i.'" '.$class.'>'.$i.'</a></li>';
                    } 
                }
            } else {
                for ($i = 1; $i <= $total_pages; $i++) {
                    $class = ($page == $i) ? 'class="current"' : '';
                    echo '
                    <li><a href="board.php?'.$url.'page='.$i.'" '.$class.'>'.$i.'</a></li>';
                } 
            }
            if ($page < $total_pages) { 
                $next = ($page + 1); 
                echo '
                    <li><a title="'._('Next Page').'" class="next" href="board.php?'.$url.'page='.$next.'"></a></li>
                    <li><a title="'._('Last Page').'" class="last" href="board.php?'.$url.'page='.$total_pages.'"></a></li>';
            } 
            echo '
                </ul>
            </div>';
        }
    }

    function displayEditPollForm ($pollid = '0')
    {
        $poll_exists = true;
        if ($pollid > 0) {
            $sql = "SELECT `question`, o.`id`, `option` 
                    FROM `fcms_polls` AS p, `fcms_poll_options` AS o 
                    WHERE p.`id` = o.`poll_id` 
                    AND p.`id` = $pollid";
            $this->db->query($sql) or displaySQLError(
                'Poll Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            if ($this->db->count_rows() <= 0) {
                $poll_exists = false;
            }
        } else {

            // Get last poll info
            $sql = "SELECT MAX(`id`) AS c FROM `fcms_polls`";
            $this->db->query($sql) or displaySQLError(
                'Max Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $row = $this->db->get_row();
            $latest_poll_id = $row['c'];
            if (is_null($row['c'])) {
                $poll_exists = false;
                $this->displayAddPollForm();
            } else {
                $sql = "SELECT `question`, o.`id`, `option` 
                        FROM `fcms_polls` AS p, `fcms_poll_options` AS o 
                        WHERE p.`id` = o.`poll_id` 
                        AND p.`id` = $latest_poll_id";
                $this->db->query($sql) or displaySQLError(
                    'Poll Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
            }
        }

        // Display the current poll
        if ($poll_exists) {
            echo '
            <form id="editform" name="editform" action="?page=admin_polls" method="post">
                <fieldset>
                    <legend><span>'._('Edit Poll').'</span></legend>';
            $i = 1;
            while ($row = $this->db->get_row()) {
                if ($i < 2) {
                    echo '
                    <h3>'.$row['question'].'</h3>';
                }
                echo '
                    <div class="field-row">
                        <div class="field-label"><label for="show'.$i.'"><b>'.sprintf(_('Option %s'), $i).':</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="show'.$i.'" id="show'.$i.'" ';
                if ($i < 3) {
                    echo "class=\"required\"";
                }
                echo ' size="50" value="'.htmlentities($row['option'], ENT_COMPAT, 'UTF-8').'"/>
                            <input type="hidden" name="option'.$i.'" value="'.$row['id'].'"/>';
                if ($i >= 3) {
                    echo '
                            <input type="button" name="deleteoption" class="delbtn" value="'._('Delete').'" title="'._('Delete').'" onclick="document.editform.show'.$i.'.value=\'\';"/>';
                }
                echo '
                        </div>
                    </div>';
                $i++;
            }
            while ($i < 11) {
                echo '
                    <div class="field-row">
                        <div class="field-label"><label for="show'.$i.'"><b>'.sprintf(_('Option %s'), $i).':</b></label></div>
                        <div class="field-widget">
                            <input type="text" id="show'.$i.'" name="show'.$i.'" size="50" value=""/>
                            <input type="hidden" name="option'.$i.'" value="new"/>
                        </div>
                    </div>';
                $i++;
            }
            echo '
                    <p>
                        <input class="sub1" type="submit" name="editsubmit" id="editsubmit" value="'._('Edit').'"/> &nbsp;
                        <a href="polls.php">'._('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
        }
    }

    function displayAddPollForm()
    {
        global $show;
        $show = false;
        echo '
            <script type="text/javascript" src="../inc/livevalidation.js"></script>
            <form id="addform" action="polls.php" method="post">
                <fieldset>
                    <legend><span>'._('Add New Poll').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="question"><b>'._('Poll Question').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="question" id="question" class="required" size="50"/></div>
                    </div>
                    <script type="text/javascript">
                        var fq = new LiveValidation(\'question\', { onlyOnSubmit: true });
                        fq.add(Validate.Presence, { failureMessage: "'._('Required').'" });
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="option1"><b>'.sprintf(_('Option %s'), '1').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option1" id="option1" class="required" size="40"/></div>
                    </div>
                    <script type="text/javascript">
                        var foption1 = new LiveValidation(\'option1\', { onlyOnSubmit: true });
                        foption1.add(Validate.Presence, {failureMessage: "'._('Without at least 2 options, it\'s not much of a poll is it?').'"});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="option2"><b>'.sprintf(_('Option %s'), '2').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option2" id="option2" class="required" size="40"/></div>
                    </div>
                    <script type="text/javascript">
                        var foption2 = new LiveValidation(\'option2\', { onlyOnSubmit: true });
                        foption2.add(Validate.Presence, {failureMessage: "'._('Without at least 2 options, it\'s not much of a poll is it?').'"});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="option3"><b>'.sprintf(_('Option %s'), '3').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option3" id="option3" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option4"><b>'.sprintf(_('Option %s'), '4').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option4" id="option4" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option5"><b>'.sprintf(_('Option %s'), '5').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option5" id="option5" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option6"><b>'.sprintf(_('Option %s'), '6').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option6" id="option6" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option7"><b>'.sprintf(_('Option %s'), '7').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option7" id="option7" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option8"><b>'.sprintf(_('Option %s'), '8').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option8" id="option8" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option9"><b>'.sprintf(_('Option %s'), '9').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option9" id="option9" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option10"><b>'.sprintf(_('Option %s'), '10').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option10" id="option10" size="40"/></div>
                    </div>
                    <p>
                        <input class="sub1" type="submit" name="addsubmit" value="'._('Add').'"/> &nbsp;
                        <a href="polls.php">'._('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    function getTopThreadStarter ()
    {
        $sql = "SELECT *, count(`thread`) AS 'thread_count' 
                FROM `fcms_board_posts` 
                WHERE `date` >= '" . $this->lastmonth_beg . "' 
                AND `date` <= '" . $this->lastmonth_end . "' 
                GROUP BY `thread` 
                ORDER BY 'thread_count' DESC LIMIT 1";
        $this->db->query($sql) or displaySQLError(
            'Thread Starter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($row=$this->db->get_row()) {
            $sql = "UPDATE `fcms_user_awards` 
                    SET `user` = '" . $row['user'] . "', 
                        `value` = '" . $row['thread'] . "', 
                        `count` = '" . $row['thread_count'] . "' 
                    WHERE `type` = 'topthreadstarter'";
            $this->db2->query($sql) or displaySQLError(
                'Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
        if ($this->db->count_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    function getMostViewedPhoto ()
    {
        $sql = "SELECT `id`, `user`, `views` 
                FROM `fcms_gallery_photos` 
                WHERE date >= '" . $this->lastmonth_beg . "' 
                    AND date <= '" . $this->lastmonth_end . "' 
                ORDER BY `views` DESC LIMIT 1";
        $this->db->query($sql) or displaySQLError(
            'Viewed Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($row=$this->db->get_row()) {
            $sql = "UPDATE `fcms_user_awards` 
                    SET `user` = '" . $row['user'] . "', 
                        `value` = '" . $row['id'] . "', 
                        `count` = '" . $row['views'] . "' 
                    WHERE `type` = 'topviewedphoto'";
            $this->db2->query($sql) or displaySQLError(
                'Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
    }

    function getTopPosters ()
    {
        $sql = "SELECT `user`, count(`user`) AS 'post_count' 
                FROM `fcms_board_posts` AS p 
                WHERE `date` >= '" . $this->lastmonth_beg . "' 
                    AND `date` <= '" . $this->lastmonth_end . "' 
                GROUP BY `user` 
                ORDER BY 'post_count' DESC 
                LIMIT 5";
        $this->db->query($sql) or displaySQLError(
            'Top Posters Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $i = 1;
        while ($row=$this->db->get_row()) {
            $sql = "UPDATE `fcms_user_awards` 
                    SET `user` = '" . $row['user'] . "', 
                        `value` = '$i', 
                        `count` = '" . $row['post_count'] . "' 
                    WHERE `type` = 'top5poster' 
                        AND `value` = '$i'";
            $this->db2->query($sql) or displaySQLError(
                'Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $i++;
        }
    }

    function getTopPhotoSubmitters ()
    {
        $sql = "SELECT `user`, count(*) AS c 
                FROM `fcms_gallery_photos` 
                WHERE `date` >= '" . $this->lastmonth_beg . "' 
                    AND `date` <= '" . $this->lastmonth_end . "' 
                GROUP BY `user` 
                ORDER BY c DESC 
                LIMIT 5";
        $this->db->query($sql) or displaySQLError(
            'Submitters Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $i = 1;
        while ($row=$this->db->get_row()) {
            $sql = "UPDATE `fcms_user_awards` 
                    SET `user` = '" . $row['user'] . "', 
                        `value` = '$i', 
                        `count` = '" . $row['c'] . "' 
                    WHERE `type` = 'top5photo' 
                        AND `value` = '$i'";
            $this->db2->query($sql) or displaySQLError(
                'Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $i++;
        }
    }

    function getMostSmileys ()
    {
        $most_smileys = '0';
        $most_smileys_user = '0';
        $sql = "SELECT `id` 
                FROM `fcms_users` 
                WHERE `username` != 'SITENEWS' 
                    AND `username` != 'test' 
                    AND `username` != 'reunion' 
                ORDER BY `id`";
        $this->db->query($sql) or displaySQLError(
            'Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $users;
        $i = 1;
        while ($row = $this->db->get_row()) {
            $users[$i] = $row['id'];
            $i++;
        }
        foreach ($users as $user) {
            $sql = "SELECT count(`user`) AS 'post_count' 
                    FROM `fcms_board_posts` AS p 
                    WHERE `date` >= '" . $this->lastmonth_beg . "' 
                        AND `date` <= '" . $this->lastmonth_end . "' 
                        AND `user` = $user 
                    GROUP BY `user` 
                    ORDER BY post_count DESC";
            $this->db->query($sql) or displaySQLError(
                'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $row = $this->db->get_row();
            $this_user_post_count = $row['post_count'];
            $sql = "SELECT count(`id`) AS 'smileys' 
                    FROM `fcms_board_posts` 
                    WHERE `date` >= '" . $this->lastmonth_beg . "' 
                        AND `date` <= '" . $this->lastmonth_end . "' 
                        AND `user` = $user 
                        AND (
                            `post` LIKE '%:smile:%' 
                            OR `post` LIKE '%:biggrin:%' 
                            OR  `post` LIKE '%:clap:%' 
                            OR `post` LIKE '%:hrmm:%' 
                            OR `post` LIKE '%:tongue:%' 
                            OR `post` LIKE '%:wink:%' 
                            OR `post` LIKE '%:doh:%'
                        )";
            $this->db->query($sql) or displaySQLError(
                'Smileys Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $row = $this->db->get_row();
            if ($this_user_post_count > 0) {
                if ((($row['smileys'] / $this_user_post_count) * 100) > $most_smileys && $this_user_post_count >= 5) {
                    $most_smileys_user = $user;
                    $most_smileys = ($row['smileys'] / $this_user_post_count) * 100;
                }
            }
        }
        if ($most_smileys_user < 1) { $most_smileys_user = 1; }
        $sql = "UPDATE `fcms_user_awards` 
                SET `user` = '$most_smileys_user', 
                    `value` = '" . date('n') . "', 
                    `count` = '$most_smileys' 
                WHERE `type` = 'mostsmileys'";
        $this->db->query($sql) or displaySQLError(
            'Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    }

    function getStartedMostThreads ()
    {
        $sql = "SELECT `started_by` , count(*) AS c 
                FROM (
                    SELECT DISTINCT t.`id` , `subject` , `started_by` 
                    FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p 
                    WHERE t.`id` = p.`thread` 
                        AND t.`started_by` = p.`user` 
                        AND p.`date` >= '2007-06-01 00:00:00' 
                        AND p.`date` <= '2007-06-31 24:59:59'
                ) AS z 
                GROUP BY `started_by` 
                ORDER BY c DESC 
                LIMIT 5";
        $this->db->query($sql) or displaySQLError(
            'Most Threads Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $i = 1;
        while ($row=$this->db->get_row()) {
            $sql = "UPDATE `fcms_user_awards` 
                    SET `user` = '" . $row['started_by'] . "', 
                        `value` = '$i', 
                        `count` = '" . $row['c'] . "' 
                    WHERE `type` = 'startedmostthreads' 
                        AND `value` = '$i'";
            $this->db2->query($sql) or displaySQLError(
                'Update Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $i++;
        }
    }
    
    /**
     * displayAdminConfig
     *
     * Displays the forms for changing/configuring the sitename,
     * email, auto activation, user defaults and sections.
     * 
     * @param   $view   which admin config section to view/edit
     * @return  n/a
     */
    function displayAdminConfig ($view)
    {
        switch($view) {
            case 'info':
                $this->displayAdminConfigInfo();
                break;
            case 'defaults':
                $this->displayAdminConfigDefaults();
                break;
            case 'sections':
                $this->displayAdminConfigSections();
                break;
            case 'gallery':
                $this->displayAdminConfigGallery();
                break;
        }
    }

    function displayAdminConfigInfo ()
    {
        // General Config
        $sql = "SELECT * FROM `fcms_config`";
        $this->db->query($sql) or displaySQLError(
            'Site Info Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $row = $this->db->get_row();
        
        // Activate Options
        $activate_list = array (
            "0" => _('Admin Activation'),
            "1" => _('Auto Activation')
        );
        $activate_options = buildHtmlSelectOptions($activate_list, $row['auto_activate']);
        // Site Off Options
        $site_off_options = '<input type="radio" name="site_off" id="site_off_yes" '
            . 'value="yes"';
        if ($row['site_off'] == 1) { $site_off_options .= ' checked="checked"'; }
        $site_off_options .= '><label class="radio_label" for="site_off_yes"> '
            . _('Yes') . '</label><br><input type="radio" name="site_off" '
            . 'id="site_off_no" value="no"';
        if ($row['site_off'] == 0) { $site_off_options .= ' checked="checked"'; }
        $site_off_options .= '><label class="radio_label" for="site_off_no"> '
            . _('No') . '</label>';
        
        echo '
        <form action="config.php" method="post">
        <fieldset class="general_cfg">
            <legend><span>'._('Website Information').'</span></legend>
            <div id="site_info">
                <div class="field-row clearfix">
                    <div class="field-label"><label for="sitename"><b>'._('Site Name').'</b></label></div>
                    <div class="field-widget">
                        <input type="text" name="sitename" size="50" value="'.$row['sitename'].'"/>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="contact"><b>'._('Contact Email').'</b></label></div>
                    <div class="field-widget">
                        <input type="text" id="contact" name="contact" size="50" value="'.$row['contact'].'"/>
                    </div>
                </div>
                <script type="text/javascript">
                    var email = new LiveValidation(\'contact\', {onlyOnSubmit: true});
                    email.add(Validate.Email, {failureMessage: "'._('That\'s not a valid email address is it?').'"});
                    email.add(Validate.Length, {minimum: 10});
                </script>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="activation"><b>'._('Account Activation').'</b></label></div>
                    <div class="field-widget">
                        <select name="activation">
                            '.$activate_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="site_off"><b>'._('Turn Off Site?').'</b></label></div>
                    <div class="field-widget">
                        '.$site_off_options.'
                    </div>
                </div>
                <p><input type="submit" id="submit-sitename" name="submit-sitename" value="'._('Save').'"/></p>
            </div>
        </fieldset>
        </form>';
    }

    function displayAdminConfigDefaults ()
    {
 
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
            . _('Yes') . '</label><br><input type="radio" name="showavatar" '
            . 'id="showavatar_no" value="no"';
        if ($default_showavatar == 0) { $avatars_options .= ' checked="checked"'; }
        $avatars_options .= '><label class="radio_label" for="showavatar_no"> '
            . _('No') . '</label>';
        // Display Name
        $displayname_list = array(
            "1" => _('First Name'),
            "2" => _('First & Last Name'),
            "3" => _('Username')
        );
        $displayname_options = buildHtmlSelectOptions($displayname_list, $default_displayname);
        // Frontpage
        $frontpage_list = array(
            "1" => _('All (by date)'),
            "2" => _('Last 5 (by section)')
        );
        $frontpage_options = buildHtmlSelectOptions($frontpage_list, $default_frontpage);
        // Timezone
        $tz_list = array(
            "-12 hours" => _('(GMT -12:00) Eniwetok, Kwajalein'),
            "-11 hours" => _('(GMT -11:00) Midway Island, Samoa'),
            "-10 hours" => _('(GMT -10:00) Hawaii'),
            "-9 hours" => _('(GMT -9:00) Alaska'),
            "-8 hours" => _('(GMT -8:00) Pacific Time (US & Canada)'),
            "-7 hours" => _('(GMT -7:00) Mountain Time (US & Canada)'),
            "-6 hours" => _('(GMT -6:00) Central Time (US & Canada), Mexico City'),
            "-5 hours" => _('(GMT -5:00) Eastern Time (US & Canada), Bogota, Lima'),
            "-4 hours" => _('(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz'),
            "-3 hours -30 minutes" => _('(GMT -3:30) Newfoundland'),
            "-3 hours" => _('(GMT -3:00) Brazil, Buenos Aires, Georgetown'),
            "-2 hours" => _('(GMT -2:00) Mid-Atlantic'),
            "-1 hours" => _('(GMT -1:00) Azores, Cape Verde Islands'),
            "-0 hours" => _('(GMT) Western Europe Time, London, Lisbon, Casablanca'),
            "+1 hours" => _('(GMT +1:00) Brussels, Copenhagen, Madrid, Paris'),
            "+2 hours" => _('(GMT +2:00) Kaliningrad, South Africa'),
            "+3 hours" => _('(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburgh'),
            "+3 hours 30 minutes" => _('(GMT +3:30) Tehran'),
            "+4 hours" => _('(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi'),
            "+4 hours 30 minutes" => _('(GMT +4:30) Kabul'),
            "+5 hours" => _('(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent'),
            "+5 hours 30 minutes" => _('(GMT +5:30) Bombay, Calcutta, Madras, New Delhi'),
            "+6 hours" => _('(GMT +6:00) Almaty, Dhaka, Colombo'),
            "+7 hours" => _('(GMT +7:00) Bangkok, Hanoi, Jakarta'),
            "+8 hours" => _('(GMT +8:00) Beijing, Perth, Singapore, Hong Kong'),
            "+9 hours" => _('(GMT +9:00) Tokyo, Seoul, Osaka, Spporo, Yakutsk'),
            "+9 hours 30 minutes" => _('(GMT +9:30) Adeliaide, Darwin'),
            "+10 hours" => _('(GMT +10:00) Eastern Australia, Guam, Vladivostok'),
            "+11 hours" => _('(GMT +11:00) Magadan, Solomon Islands, New Caledonia'),
            "+12 hours" => _('(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka')
        );
        $tz_options = buildHtmlSelectOptions($tz_list, $default_tz);
        // DST
        $dst_options = '<input type="radio" name="dst" id="dst_on" '
            . 'value="on"';
        if ($default_dst == 1) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_on"> ' . _('On') . '</label><br>'
            . '<input type="radio" name="dst" id="dst_off" value="off"';
        if ($default_dst == 0) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_off"> ' . _('Off') . '</label>';
        // Board Sort
        $boardsort_list = array(
            "ASC" => _('New Messages at Bottom'),
            "DESC" => _('New Messages at Top')
        );
        $boardsort_options = buildHtmlSelectOptions($boardsort_list, $default_boardsort);
        
        echo '
        <form enctype="multipart/form-data" action="config.php" method="post">
        <fieldset class="default_cfg">
            <legend><span>'._('Defaults').'</span></legend>
            <div id="defaults">
                <div class="field-row clearfix">
                    <div class="field-label"><label for="theme"><b>'._('Theme').'</b></label></div>
                    <div class="field-widget">
                        <select name="theme" id="theme">
                            '.$theme_options.'
                        </select>
                    </select>
                </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="showavatar"><b>'._('Show Avatars').'</b></label></div>
                    <div class="field-widget">
                        '.$avatars_options.'
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="displayname"><b>'._('Display Name').'</b></label></div>
                    <div class="field-widget">
                        <select name="displayname" id="displayname" title="'._('How do you want your name to display?').'">
                            '.$displayname_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="frontpage"><b>'._('Front Page').'</b></label></div>
                    <div class="field-widget">
                        <select name="frontpage" id="frontpage" title="'._('How do you want the latest information to display on the homepage?').'">
                            '.$frontpage_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="timezone"><b>'._('Time Zone').'</b></label></div>
                    <div class="field-widget">
                        <select name="timezone" id="timezone" title="'._('What time zone do you live in?').'">
                            '.$tz_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="dst"><b>'._('Daylight Savings Time').'</b></label></div>
                    <div class="field-widget">
                        '.$dst_options.'
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="boardsort"><b>'._('Sort Messages').'</b></label></div>
                    <div class="field-widget">
                        <select name="boardsort" id="boardsort" title="'._('How do you want messages to display on the Message Board?').'">
                            '.$boardsort_options.'
                        </select>
                    </div>
                </div>
                <p>
                    <input type="submit" id="submit-defaults" name="submit-defaults" value="'._('Save').'"/> &nbsp;
                    <input type="checkbox" name="changeAll" id="changeAll"/> 
                    <label for="changeAll">'._('Update existing users?').'</label>
                </p>
            </div>
        </fieldset>
        </form>';
    }

    function getOrderSelectBox ($c, $total, $selected, $start = 1)
    {
        $order_options = '<select id="order'.$c.'" name="order'.$c.'">';
        for ($i = $start; $i <= $total; $i++) {
            $order_options .= '
                                    <option value="'.$i.'"';
            if ($i == $selected) {
                $order_options .= ' selected="selected"';
            }
            $order_options .= '>'.$i.'</option>';
        }
        $order_options .= '
                                </select>';
        return $order_options;
    }

    function displayAdminConfigSections ()
    {
        // Get Navigation Data
        $nav = array();
        $unused = array();
        $sql = "SELECT * FROM `fcms_navigation` WHERE `col` = 4 ORDER BY `order`";
        $this->db2->query($sql) or displaySQLError(
            'Navigation Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = $this->db2->get_row()) {
            array_push($nav, $r);
            if ($r['order'] == 0) {
                array_push($unused, $r);
            }
        }

        echo '
        <form action="config.php?view=sections" method="post">
            <fieldset>
                <legend><span>'._('Navigation').'</span></legend>';
        if (count($unused) > 0) {
            echo '
                <p><b>'._('Add Optional Sections').'</b></p>
                <p>';
            foreach ($unused AS $r) {
                echo getSectionName($r['link']).' &nbsp;<a class="add" href="?view=sections&amp;add='.$r['id'].'">'._('Add').'</a><br/>';
            }
            echo '
                </p>';
        }
        echo '
                <table class="order-nav">
                    <thead>
                        <tr><th>'._('Section').'</th><th>'._('Order').'</th><th class="remove">'._('Remove').'</th></tr>
                    </thead>
                    <tbody>';

        foreach ($nav AS $r) {
            // order = 0 means it's unused
            if ($r['order'] > 0) {
                $del = '<i>'._('required').'</i>';
                if ($r['req'] < 1 && usingSection($r['link'])) {
                    $del = '&nbsp;<input class="delbtn" type="submit" name="remove" value="'.$r['id'].'"/>';
                }
                echo '
                        <tr>
                            <td>'.getSectionName($r['link']).'</td>
                            <td>
                                '.$this->getOrderSelectBox($r['id'], 7, $r['order']).'
                            </td>
                            <td class="remove">'.$del.'</td>
                        </tr>';
            }
        }
        echo '
                    </tbody>
                </table>
                <p><input type="submit" id="submit-sections" name="submit-sections" value="' . _('Save') . '"/></p>
            </fieldset>
        </form>';
    }

    function displayAdminConfigGallery ()
    {
        $sql = "SELECT * FROM `fcms_config`";
        $this->db->query($sql) or displaySQLError(
            'Site Info Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $row = $this->db->get_row();
        
        $full_size_list = array(
            "0" => _('Off (2 photos)'),
            "1" => _('On (3 photos)')
        );
        $full_size_options = buildHtmlSelectOptions($full_size_list, $row['full_size_photos']);
        
        echo '
        <form action="config.php" method="post">
        <fieldset class="gallery_cfg">
            <legend><span>'._('Photo Gallery').'</span></legend>
            <div id="gallery">
                <p class="info-alert">
                    '._('By default, Full Sized Photos is turned off to save on storage space and bandwidth.  Turning this feature on can eat up significant space and bandwith.').'
                </p>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="full_size_photos"><b>'._('Fulle Size Photos').'</b></label></div>
                    <div class="field-widget">
                        <select name="full_size_photos">
                            '.$full_size_options.'
                        </select>
                    </div>
                </div>
                <p><input type="submit" id="submit-gallery" name="submit-gallery" value="'._('Save').'"/></p>
            </div>
        </fieldset>
        </form>';
    }

    function displaySectionDropdown ($which_nav, $which_selected, $num)
    { 
        echo '
                <div class="field-row clearfix">
                    <div class="field-label"><label for="'.$which_nav.'"><b>'._('Section').' '.$num.'</b></label></div>
                    <div class="field-widget">
                        <select name="'.$which_nav.'">';
        if (tableExists('fcms_news')) {
            echo '<option value="familynews"';
            if ($which_selected == 'familynews') {
                echo ' selected="selected"';
            }
            echo '>' . _('Family News') . '</option>';
        }
        if (tableExists('fcms_recipes')) {
            echo '<option value="recipes"';
            if ($which_selected == 'recipes') {
                echo ' selected="selected"';
            }
            echo '>' . _('Recipes') . '</option>';
        }
        if (tableExists('fcms_documents')) {
            echo '<option value="documents"';
            if ($which_selected == 'documents') {
                echo ' selected="selected"';
            }
            echo '>' . _('Documents') . '</option>';
        }
        if (tableExists('fcms_prayers')) {
            echo '<option value="prayers"';
            if ($which_selected == 'prayers') {
                echo ' selected="selected"';
            }
            echo '>' . _('Prayer Concerns') . '</option>';
        }
        $i = substr($which_nav, 7);
        echo '<option value="none'.$i.'"';
        $pos = strpos($which_selected, "none");
        if ($pos !== false) {
            echo ' selected="selected"';
        }
        echo '>' . _('none') . '</option>
                        </select>
                    </div>
                </div>';
    }

} ?>
