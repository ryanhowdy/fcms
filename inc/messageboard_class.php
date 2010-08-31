<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

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

    function showThreads ($type, $page = '0')
    {
        $locale = new Locale();
        $from = (($page * 25) - 25);
        if ($type == 'announcement') {
            if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
                $this->displayMessageBoardMenu();
            }
            echo '
            <table id="threadlist" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th class="images">&nbsp;</th>
                        <th class="subject">'._('Subject').'</th>
                        <th class="info">&nbsp;</th>
                        <th class="replies">'._('Replies').'</th>
                        <th class="views">'._('Views').'</th>
                        <th class="updated">'._('Last Updated').'</th>
                    </tr>
                </thead>
                <tbody>';

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
                $subject_info = "<small><b>" . _('Announcement') . ": </b></small>";
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
                $date = $locale->fixDate(_('h:ia'), $this->tz_offset, $row['updated']);
                $last_updated = sprintf(_('Today at %s'), $date).'<br/>'
                    .sprintf(_('by %s'), ' <a class="u" href="profile.php?member='.$row['updated_by'].'">'.$updated_by.'</a>');
            // thread was updated yesterday
            } elseif (
                gmdate('n/d/Y', strtotime($row['updated'] . $this->tz_offset)) == 
                gmdate('n/d/Y', strtotime(date('n/d/Y', strtotime(date('Y-m-d H:i:s') . $this->tz_offset)) . "-24 hours"))
            ) {
                $img_class = 'yesterday';
                if ($type == 'announcement') {
                    $img_class = 'announcement_' . $img_class;
                }
                $date = $locale->fixDate(_('h:ia'), $this->tz_offset, $row['updated']);
                $last_updated = sprintf(_('Yesterday at %s'), $date).'<br/>'
                    .sprintf(_('by %s'), ' <a class="u" href="profile.php?member='.$row['updated_by'].'">'.$updated_by.'</a>');
            } else {
                $img_class = '';
                if ($type == 'announcement') {
                    $img_class = 'announcement';
                }
                $last_updated = $locale->fixDate(_('m/d/Y h:ia'), $this->tz_offset, $row['updated'])
                    .sprintf(_('by %s'), ' <a class="u" href="profile.php?member='.$row['updated_by'].'">'.$updated_by.'</a>');
            }
            // thread has multiple pages?
            $thread_pages = '';
            if ($this->getNumberOfPosts($row['id']) > 15) { 
                $num_posts = $this->getNumberOfPosts($row['id']);
                $thread_pages = "<span>" . _('Page') . " ";
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
            echo '
                    <tr class="'.$tr_class.'">
                        <td class="images"><div class="'.$img_class.'">&nbsp;</div></td>
                        <td class="subject">
                            '.$subject_info.'<a href="messageboard.php?thread='.$row['id'].'">'.$subject.'</a><br/>
                            '.$thread_pages.'
                            <span><a class="u" href="profile.php?member='.$row['started_by'].'">'.$started_by.'</a></span>
                        </td>
                        <td class="info">'.$info.'</td>
                        <td class="replies">'.$num_replies.'</td>
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

    function showPosts ($thread_id, $page = '1')
    {
        $locale = new Locale();
        $from = (($page * 15) - 15);
        if (!ctype_digit($thread_id)) {
            echo '
            <p class="error-alert">'._('Invalid Thread').'</p>';
            return;
        }
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
            $date = $locale->fixDate(_('n/d/y g:ia'), $this->tz_offset, $row['date']);
            if ($alt % 2 == 0) {
                $tr_class = '';
            } else {
                $tr_class = 'alt';
            }
            // rank
            $points = getUserRankById($row['user']);
            $rank = '';
            if ($points > 50) {
                $rank = "<div title=\""._('Elder')." ($points)\" class=\"rank7\"></div>";
            } elseif ($points > 30) {
                $rank = "<div title=\""._('Adult')." ($points)\" class=\"rank6\"></div>";
            } elseif ($points > 20) {
                $rank = "<div title=\""._('Matuer Adult')." ($points)\" class=\"rank5\"></div>";
            } elseif ($points > 10) {
                $rank = "<div title=\""._('Young Adult')." ($points)\" class=\"rank4\"></div>";
            } elseif ($points > 5) {
                $rank = "<div title=\""._('Teenager')." ($points)\" class=\"rank3\"></div>";
            } elseif ($points > 3) {
                $rank = "<div title=\""._('Kid')." ($points)\" class=\"rank2\"></div>";
            } elseif ($points > 1) {
                $rank = "<div title=\""._('Toddler')." ($points)\" class=\"rank1\"></div>";
            } else {
                $rank = "<div title=\""._('Baby')." ($points)\" class=\"rank0\"></div>";
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
                                            <input type="submit" class="quotebtn" value="'._('Quote').'" name="quotepost" title="'._('Quote this message').'"/>
                                        </div>
                                    </form>';
            }
            // edit
            if ($this->cur_user_id == $row['user'] || checkAccess($this->cur_user_id) < 3) {
                $actions .= ' &nbsp;
                                    <form method="post" action="messageboard.php">
                                        <div>
                                            <input type="hidden" name="id" value="'.$row['id'].'"/>
                                            <input type="submit" name="editpost" value="'._('Edit').'" class="editbtn" title="'._('Edit this message').'"/>
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
                                            <input type="submit" name="delpost" value="'._('Delete').'" class="delbtn" title="'._('Delete this message').'"/>
                                        </div>
                                    </form>';
            }

            // Display the posts rows
            echo '
                    <tr class="'.$tr_class.'">
                        <td class="side">
                            <b><a href="profile.php?member='.$row['user'].'">'.$displayname.'</a></b>
                            '.$rank.'
                            '.$avatar.'
                            '.$awards.'
                            <b>'._('Posts').'</b> '.$posts_count.'
                        </td>
                        <td class="posts">
                            <div class="header clearfix">
                                <div class="subject"><b>'.$subject.'</b> - '.$date.'</div>
                                <div class="actions">
                                    '.$actions.'
                                </div>
                            </div>
                            <div class="msg">
                                '.parse($row['post']).'
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
        echo '
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>';
    }

    function getNumberOfPosts ($thread_id)
    {
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

    function getUserPostCountById ($user_id)
    {
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
    
    function displayPages ($page = '1', $thread_id = '0')
    {
        if ($thread_id < 1) {
            $sql = "SELECT count(id) AS c FROM fcms_board_threads";
            $this->db2->query($sql) or displaySQLError(
                'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $row = $this->db2->get_row();
            $total_pages = ceil($row['c'] / 25);
            $url = 'messageboard.php';
        } else {
            $sql = "SELECT count(id) AS c FROM fcms_board_posts WHERE thread = $thread_id";
            $this->db2->query($sql) or displaySQLError(
                'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $row = $this->db2->get_row();
            $total_pages = ceil($row['c'] / 15);
            $url = 'messageboard.php?thread='.$thread_id;
        }
        displayPagination ($url, $page, $total_pages);
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
    function displayForm ($type, $thread_id = '0', $post_id = '0', $post = 'error')
    {

        // New
        if ($type == 'new') {
            $reply = '';
            $header = _('New Message');
            $subject = '
                <div>
                    <label for="subject">'._('Subject').'</label>: 
                    <input type="text" name="subject" id="subject" size="50"/>
                </div>
                <script type="text/javascript">
                    var fsub = new LiveValidation(\'subject\', {onlyOnSubmit: true});
                    fsub.add(Validate.Presence, {failureMessage: ""});
                </script>';
            $sticky = '';
            if (checkAccess($this->cur_user_id) <= 2) {
                $sticky = '
                <p>
                    <label for="sticky">'._('Admin Tools').'</label>: 
                    <input type="checkbox" name="sticky" id="sticky" value="sticky"/>'._('Make Announcement').'
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
                <p>
                    <input class="sub1" type="submit" name="post_submit" id="post_submit" value="'._('Submit').'"/>
                    &nbsp; <a href="messageboard.php">'._('Cancel').'</a>
                </p>';

        // Reply
        } elseif ($type == 'reply') {
            $header = _('Reply');
            $subject = '';
            $sticky = '';
            $post_js = '';
            
            // Get last post in the thread to display above reply
            $sql = "SELECT `post`, `user` 
                    FROM `fcms_board_posts` 
                    WHERE `thread` = " . $thread_id . " 
                    ORDER BY `date` DESC 
                    LIMIT 1";
            $this->db->query($sql) or displaySQLError(
                'Get Reply Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $row = $this->db->get_row();
            $displayname = getUserDisplayName($row['user']);
            $reply = '
            <div class="lastpost">
                <b>'.sprintf(_('Last post written by %s'), $displayname).'</b><br />
                <p>'.parse($row['post']).'</p>
            </div>';
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
                $post = '[SPAN=q]'._('Quoting').': '.getUserDisplayName($qrow['user']).'[/SPAN][QUOTE]'
                        .htmlentities($qrow['post'], ENT_COMPAT, 'UTF-8').'[/QUOTE]';
            } else {
                $post = '';
            }
            
            $hidden_submit = '
                <div><input type="hidden" name="name" id="name" value="'.$this->cur_user_id.'"/></div>
                <div><input type="hidden" name="thread_id" value="'.$thread_id.'"/></div>
                <p>
                    <input class="sub1" type="submit" name="reply_submit" id="reply_submit" value="'._('Reply').'"/>
                    &nbsp; <a href="?thread='.$thread_id.'">'._('Cancel').'</a>
                </p>';

        // Edit
        } elseif ($type == 'edit') {
            $reply = '';
            $header = _('Edit');
            $subject = '';
            $sticky = '';
            $post_js = '';

            // Remove the previous edited by string so we can add a new one
            $pos = strpos($post, "[size=small][i]"._('Edited'));
            if ($pos !== false) {
                $post = substr($post, 0, $pos);
            }
            
            $hidden_submit = '
                <div><input type="hidden" name="id" id="id" value="'.$post_id.'"/></div>
                <div><input type="hidden" name="thread_id" id="thread_id" value="'.$thread_id.'"/></div>
                <p>
                    <input class="sub1" type="submit" name="edit_submit" id="edit_submit" value="'._('Edit').'"/>
                    &nbsp; <a href="?thread='.$thread_id.'">'._('Cancel').'</a>
                </p>';
        }

        // Display the form
        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <script type="text/javascript" src="inc/fcms.js"></script>
            <form id="postform" method="post" action="messageboard.php">
                <fieldset>
                    <legend><span>'.$header.'</span></legend>
                    '.$subject.'
                    <div>
                        <label for="showname">'._('Name').'</label>: 
                        <input type="text" disabled="disabled" name="showname" id="showname" value="'.getUserDisplayName($this->cur_user_id).'" size="50"/>
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
                    '.$hidden_submit.'
                </fieldset>
            </form>
            '.$reply;
    }

    function hasAwards ($user_id)
    {
        $this->db2->query("SELECT * FROM fcms_user_awards WHERE user = $user_id AND `count` > 0") or die('<h1>Awards? Error (messageboard.class.php 258)</h1>' . mysql_error());
        $rows=$this->db2->count_rows();
        if ($rows > 0) { return true; } else { return false; }
    }

    function getAwards ($user_id)
    {
        $str = "<b>"._('Awards')."</b>";
        $sql = "SELECT * FROM fcms_user_awards WHERE user = $user_id AND `count` > 0";
        $this->db2->query($sql) or displaySQLError(
            'Awards Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while($row=$this->db2->get_row()) {
            if ($row['type'] == 'top5poster') {
                $str .= "<div class=\"award boardtop5";
                if ($row['value'] <= 1) { $str .= "gold"; }
                $str .= "\" title=\"".sprintf(_('#%s Poster last month'), $row['value']);
                $str .= " (" . $row['count'] . " "._('posts').")\"></div>";
            } elseif ($row['type'] == 'top5photo') {
                $str .= "<div class=\"award phototop5";
                if ($row['value'] <= 1) { $str .= "gold"; }
                $str .= "\" title=\"".sprintf(_('#%s Photographer last month'), $row['value']);
                $str .= " (" . $row['count'] . " "._('posts').")\"></div>";
            } else if ($row['type'] == 'mostsmileys') {
                $str .= "<div class=\"award smileys\" title=\"" . _('Used Most Smileys last month') . "\"></div>";
            } else if ($row['type'] == 'topviewedphoto') {
                $str .= "<div class=\"award topviewedphoto\" title=\"";
                $str .= _('Uploaded Most Viewed Photo last month') . " (" . $row['count'] . " ";
                $str .= _('views') . ")\"></div>";
            } else {
                $str .= "<div class=\"award threadstarter\" title=\""._('Top Thread Starter last month');
                $str .= " (" . $row['count'] . " " . _('posts') . ")\"></div>";
            }
        }
        return $str;
    }

    function displayMessageBoardMenu ($thread_id = '')
    {
        if ($thread_id == '') {
            echo '
            <div id="actions_menu" class="clearfix">
                <ul>
                    <li><a href="messageboard.php?reply=new">'._('New Message').'</a></li>
                </ul>
            </div>';
        } else {
            echo '
            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a href="messageboard.php">'._('Message Board Home').'</a></li>
                </ul>
            </div>';

            if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
                echo '
            <div id="actions_menu" class="clearfix">
                <ul>
                    <li><a class="action" href="messageboard.php?reply=' . $thread_id . '">'._('Reply').'</a></li>
                </ul>
            </div>';
            }
        }

    }

    function displayWhatsNewMessageBoard ()
    {
        $locale = new Locale();
        $today = date('Y-m-d');
        $tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
        echo '
            <h3>'._('Message Board').'</h3>
            <ul>';
        $sql = "SELECT * 
                FROM (
                    SELECT p.id, `date`, subject, u.id AS userid, fname, lname, username, thread 
                    FROM fcms_board_posts AS p, fcms_board_threads AS t, fcms_users AS u 
                    WHERE p.thread = t.id 
                    AND p.user = u.id 
                    AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 
                    ORDER BY `date` DESC 
                ) AS r 
                GROUP BY subject 
                ORDER BY `date` DESC 
                LIMIT 0, 5";
        $this->db->query($sql) or die('<h1>Posts Error (messageboard.class.php 287)</h1>' . mysql_error());
        if ($this->db->count_rows() > 0) {
            while ($row = $this->db->get_row()) {
                $displayname = getUserDisplayName($row['userid']);
                $subject = $row['subject'];
                $subject_full = htmlentities($row['subject'], ENT_COMPAT, 'UTF-8');
                $pos = strpos($subject, '#ANOUNCE#');
                if ($pos !== false) { $subject = substr($subject, 9, strlen($subject)-9); }
                if (strlen($subject) > 23) { $subject = substr($subject, 0, 20) . "..."; }
                $date = $locale->fixDate(_('M. j, Y g:i a'), $this->tz_offset, $row['date']);
                if (
                    strtotime($row['date']) >= strtotime($today) && 
                    strtotime($row['date']) > $tomorrow
                ) { 
                    $full_date = _('Today');
                    $d = ' class="today"';
                } else {
                    $full_date = $date;
                    $d = '';
                }
                echo '
                <li>
                    <div'.$d.'>'.$full_date.'</div>
                    <a href="messageboard.php?thread='.$row['thread'].'" title="'.$subject_full.'">'.$subject.'</a> ';

                if ($this->getNumberOfPosts($row['thread']) > 15) {
                    $num_posts = $this->getNumberOfPosts($row['thread']);
                    echo '
                    ('._('Page').' ';
                    $times2loop = ceil($num_posts/15);
                    for ($i=1; $i<=$times2loop; $i++) {
                        echo '<a href="messageboard.php?thread='.$row['thread'].'&amp;page='.$i.'" title="'._('Page').' '.$i.'">'.$i.'</a> ';
                    }
                    echo ")";
                }
                echo '
                     - <a class="u" href="profile.php?member='.$row['userid'].'">'.$displayname.'</a>
                </li>';
            }
        } else {
            echo '
                <li><i>'._('nothing new last 30 days').'</i></li>';
        }
        echo '
            </ul>';
    }

} ?>
