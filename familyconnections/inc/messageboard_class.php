<?php
/**
 * MessageBoard 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class MessageBoard
{
    var $fcmsError;
    var $fcmsDatabase;
    var $fcmsUser;

    /**
     * MessageBoard 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase
     * @param object $fcmsUser 
     * 
     * @return  void
     */
    function MessageBoard ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
    }

    /**
     * showThreads 
     * 
     * Prints the list of threads/subjects.  Also used to display announcements.
     * 
     * Must call this twice, once for announcements and once for threads.
     * 
     * @param string $type 
     * @param int    $page 
     * 
     * @return void
     */
    function showThreads ($type, $page = 0)
    {
        $page = (int)$page;
        $from = (($page * 25) - 25);

        // Announcements
        if ($type == 'announcement')
        {
            if ($this->fcmsUser->access < 8 && $this->fcmsUser->access != 5)
            {
                $this->displayMessageBoardMenu();
            }

            // Table header
            echo '
            <table id="threadlist" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th class="images">&nbsp;</th>
                        <th class="subject">'.T_('Subject').'</th>
                        <th class="info">&nbsp;</th>
                        <th class="replies">'.T_('Replies').'</th>
                        <th class="views">'.T_('Views').'</th>
                        <th class="updated">'.T_('Last Updated').'</th>
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

            $rows = $this->fcmsDatabase->getRows($sql);
            if ($rows === false)
            {
                $this->fcmsError->displayError();

                return;
            }
        }
        // Threads
        else
        {
            $sql = "SELECT t.`id`, `subject`, `started_by`, `updated`, 
                        `updated_by`, `views`, `user` 
                    FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p 
                    WHERE t.`id` = p.`thread` 
                    AND `subject` NOT LIKE '#ANOUNCE#%' 
                    GROUP BY t.`id` 
                    ORDER BY `updated` DESC 
                    LIMIT $from, 30";

            $rows = $this->fcmsDatabase->getRows($sql);
            if ($rows === false)
            {
                $this->fcmsError->displayError();

                return;
            }
        }

        $alt = 0;

        // Setup today and yesterday dates
        $today_start = fixDate('Ymd', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s')) . '000000';
        $today_end   = fixDate('Ymd', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s')) . '235959';

        $time = gmmktime(0, 0, 0, gmdate('m')  , gmdate('d')-1, gmdate('Y'));

        $yesterday_start = fixDate('Ymd', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s', $time)) . '000000';
        $yesterday_end   = fixDate('Ymd', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s', $time)) . '235959';

        // Loop through threads/annoucements
        foreach ($rows as $row)
        {
            $numberOfPosts   = $this->getNumberOfPosts($row['id']);
            $numberOfReplies = $numberOfPosts - 1;
            $started_by      = getUserDisplayName($row['started_by']);
            $updated_by      = getUserDisplayName($row['updated_by']);
            $subject         = $row['subject'];
            $subject_info    = '';
            $tr_class        = '';

            if ($type == 'announcement')
            {
                //remove #ANNOUNCE# from the subject
                $subject      = substr($subject, 9, strlen($subject)-9);
                $subject_info = "<small><b>" . T_('Announcement') . ": </b></small>";
                $tr_class     = 'announcement';
            }
            else
            {
                if ($alt % 2 !== 0) { $tr_class = 'alt'; }
            }

            $updated = fixDate('YmdHis', $this->fcmsUser->tzOffset, $row['updated']);

            // Updated Today
            if ($updated >= $today_start && $updated <= $today_end)
            {
                $img_class = 'today';
                if ($type == 'announcement')
                {
                    $img_class = 'announcement_' . $img_class;
                }
                $date = fixDate(T_('h:ia'), $this->fcmsUser->tzOffset, $row['updated']);
                $last_updated = sprintf(T_('Today at %s'), $date).'<br/>'
                    .sprintf(T_('by %s'), ' <a class="u" href="profile.php?member='.(int)$row['updated_by'].'">'.$updated_by.'</a>');
            }
            // Updated Yesterday
            elseif ($updated >= $yesterday_start && $updated <= $yesterday_end)
            {
                $img_class = 'yesterday';
                if ($type == 'announcement')
                {
                    $img_class = 'announcement_' . $img_class;
                }
                $date = fixDate(T_('h:ia'), $this->fcmsUser->tzOffset, $row['updated']);
                $last_updated = sprintf(T_('Yesterday at %s'), $date).'<br/>'
                    .sprintf(T_('by %s'), ' <a class="u" href="profile.php?member='.(int)$row['updated_by'].'">'.$updated_by.'</a>');
            }
            // Updated before yesterday
            else
            {
                $img_class = '';
                if ($type == 'announcement')
                {
                    $img_class = 'announcement';
                }
                $last_updated = fixDate(T_('m/d/Y h:ia'), $this->fcmsUser->tzOffset, $row['updated']) . '<br/>'
                    .sprintf(T_('by %s'), ' <a class="u" href="profile.php?member='.(int)$row['updated_by'].'">'.$updated_by.'</a>');
            }

            // thread has multiple pages?
            $thread_pages = '';
            if ($numberOfPosts > 15)
            {
                $num_posts    = $this->getNumberOfPosts($row['id']);
                $thread_pages = "<span>" . T_('Page') . " ";
                $times2loop   = ceil($num_posts/15);

                for ($i=1;$i<=$times2loop;$i++)
                {
                    $thread_pages .= "<a href=\"messageboard.php?thread=" . (int)$row['id'] . "&amp;page=$i\">$i</a> ";
                }
                $thread_pages .= "</span><br/>";
            }

            // Thread is hot
            if ($numberOfPosts > 20)
            {
                $info = '<div class="hot">&nbsp;</div>';
            }
            else
            {
                $info = "&nbsp;";
            }


            // Display the message board thread rows
            echo '
                    <tr class="'.$tr_class.'">
                        <td class="images"><div class="'.$img_class.'">&nbsp;</div></td>
                        <td class="subject">
                            '.$subject_info.'<a href="messageboard.php?thread='.(int)$row['id'].'">'.cleanOutput($subject, 'html').'</a><br/>
                            '.$thread_pages.'
                            <span><a class="u" href="profile.php?member='.(int)$row['started_by'].'">'.$started_by.'</a></span>
                        </td>
                        <td class="info">'.$info.'</td>
                        <td class="replies">'.$numberOfReplies.'</td>
                        <td class="views">'.(int)$row['views'].'</td>
                        <td class="updated">
                            '.$last_updated.'
                        </td>
                    </tr>';
            $alt++;
        }

        if ($type == 'thread')
        {
            echo '
                </tbody>
            </table>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>';
            $this->displayPages($page);
        }
    }

    /**
     * showPosts 
     * 
     * @param   int     $thread_id 
     * @param   int     $page 
     * @return  void
     */
    function showPosts ($thread_id, $page = 1)
    {
        $thread_id  = (int)$thread_id;
        $page       = (int)$page;

        $from = (($page * 15) - 15);

        $total = $this->getNumberOfPosts($thread_id);
        if ($total < 1) {
            echo '
            <p class="error-alert">'.T_('Thread does not exist.').'</p>';
            return;
        }

        $sql = "UPDATE `fcms_board_threads` 
                SET `views` = (`views` + 1) 
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->update($sql, $thread_id))
        {
            $this->fcmsError->displayError();

            return;
        }

        $this->displayMessageBoardMenu($thread_id);
        $this->displayPages($page, $thread_id);

        $sort = $this->getSortOrder($this->fcmsUser->id);

        $sql = "SELECT p.`id`, `thread`, `post`, `subject`, p.`date`, `user`, `avatar` 
                FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, 
                    `fcms_users` AS u 
                WHERE `thread` = ?
                AND t.`id` = `thread` 
                AND `user` = u.`id` 
                ORDER BY p.`id` $sort
                LIMIT $from, 15";

        $rows = $this->fcmsDatabase->getRows($sql, $thread_id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        $alt   = 0;
        $first = true;

        foreach ($rows as $row)
        {
            // display the table header
            if ($first)
            {
                echo '
            <table id="postlist" cellpadding="0" cellspacing="0">
                <tbody>';
                $first = false;
            }

            // Shrink long subjects
            $subject = $row['subject'];
            if (strlen($subject) > 40)
            {
                $subject = substr($subject, 0, 37) . "...";
            }

            // Remove #ANOUNCE#
            $isThreadAnnouncement = false;
            $pos = strpos($subject, '#ANOUNCE#');
            if ($pos !== false)
            {
                $isThreadAnnouncement = true;
                $subject = substr($subject, 9, strlen($subject)-9);
            }

            // Add RE: to replies
            if ($sort == 'ASC')
            {
                if ($alt > 0) { $subject = "RE: " . $subject; }
            }
            else
            {
                if ($alt !== $total - 1) { $subject = "RE: " . $subject; }
            }

            $displayname = getUserDisplayName($row['user']);
            $date        = fixDate(T_('n/d/y g:ia'), $this->fcmsUser->tzOffset, $row['date']);

            if ($alt % 2 == 0)
            {
                $tr_class = '';
            }
            else
            {
                $tr_class = 'alt';
            }

            // Participation Level
            $points = getUserParticipationPoints($row['user']);
            $level  = getUserParticipationLevel($points);

            // Avatar
            $avatar = "<img src=\"".getCurrentAvatar($row['user'])."\" alt=\"$displayname\"/><br/><br/>";

            // Post Count
            $posts_count = $this->getUserPostCountById($row['user']);

            $actions = '';
            // quote
            if ($this->fcmsUser->access < 8 && $this->fcmsUser->access != 5) {
                $actions .= '<form method="post" action="messageboard.php?reply='.$thread_id.'">
                                        <div>
                                            <input type="hidden" name="id" value="'.(int)$row['id'].'"/>
                                            <input type="submit" class="quotebtn" value="'.T_('Quote').'" name="quotepost" title="'.T_('Quote this message').'"/>
                                        </div>
                                    </form>';
            }
            // edit
            if ($this->fcmsUser->id == $row['user'] || $this->fcmsUser->access < 3) {
                $actions .= ' &nbsp;
                                    <form method="post" action="messageboard.php">
                                        <div>
                                            <input type="hidden" name="id" value="'.(int)$row['id'].'"/>
                                            <input type="submit" name="editpost" value="'.T_('Edit').'" class="editbtn" title="'.T_('Edit this message').'"/>
                                        </div>
                                    </form>';
            }
            // delete
            if ($this->fcmsUser->access < 2) {
                $actions .= ' &nbsp;
                                    <form class="delpost" method="post" action="messageboard.php">
                                        <div>
                                            <input type="hidden" name="id" value="'.(int)$row['id'].'"/>
                                            <input type="hidden" name="thread" value="'.$thread_id.'"/>
                                            <input type="submit" name="delpost" value="'.T_('Delete').'" class="delbtn" title="'.T_('Delete this message').'"/>
                                        </div>
                                    </form>';
            }

            // Display the posts rows
            echo '
                    <tr class="'.$tr_class.'">
                        <td class="side">
                            <b><a href="profile.php?member='.$row['user'].'">'.$displayname.'</a></b>
                            '.$level.'
                            '.$avatar.'
                            <b>'.T_('Posts').'</b> '.$posts_count.'
                        </td>
                        <td class="posts">
                            <div class="header">
                                <div class="subject"><b>'.cleanOutput($subject, 'html').'</b> - '.$date.'</div>
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
        $this->displayAdminMenu($thread_id, $isThreadAnnouncement);
        echo '
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>';
    }

    /**
     * getNumberOfPosts 
     * 
     * Moved to inc/utils.php
     * 
     * @param   int $thread_id 
     * @return  int
     */
    function getNumberOfPosts ($thread_id)
    {
        // This was moved to inc/utils.php
        return getNumberOfPosts($thread_id);
    }

    /**
     * getSortOrder 
     * 
     * @param   int $user_id 
     * @return  string
     */
    function getSortOrder ($user_id)
    {
        $user_id = (int)$user_id;

        $sql = "SELECT `boardsort` 
                FROM `fcms_user_settings` 
                WHERE `user` = '$user_id'";

        $row = $this->fcmsDatabase->getRow($sql, $user_id);
        if ($row == false)
        {
            return 'DESC';
        }

        $validSort = array('ASC', 'DESC');

        if (!in_array($row['boardsort'], $validSort))
        {
            return 'DESC';
        }

        return $row['boardsort'];
    }

    /**
     * getUserPostCountById 
     * 
     * @todo should be in utils
     * 
     * @param int $user_id 
     * 
     * @return  int
     */
    function getUserPostCountById ($user_id)
    {
        $user_id = (int)$user_id;

        $sql = "SELECT `id`
                FROM `fcms_board_posts`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            return 0;
        }

        $total = count($rows);

        $sql = "SELECT count(`user`) AS c 
                FROM `fcms_board_posts` 
                WHERE `user` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $user_id);
        if ($row === false)
        {
            return 0;
        }

        $count = (int)$row['c'];

        if ($total < 1)
        { 
            return "0 (0%)";
        }
        else
        { 
            return $count . " (" . round((($count/$total)*100), 1) . "%)";
        }
    }
    
    /**
     * displayPages 
     * 
     * @param int $page 
     * @param int $thread_id 
     * 
     * @return void
     */
    function displayPages ($page = 1, $thread_id = 0)
    {
        $thread_id = (int)$thread_id;

        if ($thread_id < 1)
        {
            $sql = "SELECT count(`id`) AS c 
                    FROM `fcms_board_threads`";

            $row = $this->fcmsDatabase->getRow($sql);
            if ($row === false)
            {
                $this->fcmsError->displayError();

                return;
 
           }
            $total_pages = ceil($row['c'] / 25);
            $url         = 'messageboard.php';
        }
        else
        {
            $sql = "SELECT count(`id`) AS c 
                    FROM `fcms_board_posts` 
                    WHERE `thread` = ?";

            $row = $this->fcmsDatabase->getRow($sql, $thread_id);
            if ($row === false)
            {
                $this->fcmsError->displayError();

                return;
            }

            $total_pages = ceil($row['c'] / 15);
            $url         = 'messageboard.php?thread='.$thread_id;
        }

        displayPagination ($url, $page, $total_pages);
    }

    /**
     * displayForm 
     * 
     * Displays the form for posting: new, reply and edit
     *  
     * @param   string  $type       new, reply or edit
     * @param   int     $thread_id  used for reply and edit
     * @param   int     $post_id    used for reply and edit
     * @param   string  $post       used for edit
     * @return  void
     */
    function displayForm ($type, $thread_id = 0, $post_id = 0, $post = 'error')
    {
        $thread_id = (int)$thread_id;
        $post_id   = (int)$post_id;
        $tab       = 1;

        // New
        if ($type == 'new')
        {
            $reply  = '';
            $header = T_('New Message');
            $sticky = '';
            $post   = '';

            $subject = '
                <div>
                    <label for="subject">'.T_('Subject').'</label>: 
                    <input type="text" name="subject" id="subject" size="50" tabindex="'.$tab.'"/>
                </div>
                <script type="text/javascript">
                    var fsub = new LiveValidation(\'subject\', {onlyOnSubmit: true});
                    fsub.add(Validate.Presence, {failureMessage: ""});
                </script>';
            $tab++;

            if ($this->fcmsUser->access <= 2)
            {
                $sticky = '
                <p>
                    <label for="sticky">'.T_('Admin Tools').'</label>: 
                    <input type="checkbox" name="sticky" id="sticky" value="sticky"/>'.T_('Make Announcement').'
                </p>';
            }

            $post_js = '
                <script type="text/javascript">
                    var fpost = new LiveValidation(\'post\', {onlyOnSubmit: true});
                    fpost.add(Validate.Presence, {failureMessage: ""});
                </script>';

            $hidden_submit = '
                <div><input type="hidden" name="name" id="name" value="'.$this->fcmsUser->id.'"/></div>
                <p>
                    <input class="sub1" type="submit" name="post_submit" id="post_submit" tabindex="'.($tab+1).'" value="'.T_('Submit').'"/>
                    &nbsp; <a href="messageboard.php">'.T_('Cancel').'</a>
                </p>';
        }
        // Reply
        elseif ($type == 'reply')
        {
            $header  = T_('Reply');
            $subject = '';
            $sticky  = '';
            $post_js = '';
            
            // Get last post in the thread to display above reply
            $sql = "SELECT `post`, `user` 
                    FROM `fcms_board_posts` 
                    WHERE `thread` = ?
                    ORDER BY `date` DESC 
                    LIMIT 1";

            $row = $this->fcmsDatabase->getRow($sql, $thread_id);
            if ($row === false)
            {
                $this->fcmsError->displayError();

                return;
            }

            $displayname = getUserDisplayName($row['user']);

            $reply = '
            <div class="lastpost">
                <b>'.sprintf(T_('Last post written by %s'), $displayname).'</b><br />
                <p>'.parse($row['post']).'</p>
            </div>';

            // Get the text of ther post that the user is quoting
            // We know we are quoting someone if type is reply and we have a post_id
            if ($post_id > 0)
            {
                $sql = "SELECT `post`, `user` 
                        FROM `fcms_board_posts` 
                        WHERE `id` = '$post_id'
                        LIMIT 1";

                $qrow = $this->fcmsDatabase->getRow($sql, $post_id);
                if ($qrow == false)
                {
                    $this->fcmsError->displayError();

                    return;
                }

                $post = '[SPAN=q]'.T_('Quoting').': '.getUserDisplayName($qrow['user']).'[/SPAN][QUOTE]'.cleanOutput($qrow['post']).'[/QUOTE]';
            }
            else
            {
                $post = '';
            }
            
            $hidden_submit = '
                <div><input type="hidden" name="name" id="name" value="'.$this->fcmsUser->id.'"/></div>
                <div><input type="hidden" name="thread_id" value="'.$thread_id.'"/></div>
                <p>
                    <input class="sub1" type="submit" name="reply_submit" id="reply_submit" tabindex="'.($tab+1).'" value="'.T_('Reply').'"/>
                    &nbsp; <a href="?thread='.$thread_id.'">'.T_('Cancel').'</a>
                </p>';
        }
        // Edit
        elseif ($type == 'edit')
        {
            $reply   = '';
            $header  = T_('Edit');
            $subject = '';
            $sticky  = '';
            $post_js = '';

            // Remove the previous edited by string so we can add a new one
            $pos = strpos($post, "[size=small][i]".T_('Edited'));
            if ($pos !== false)
            {
                $post = substr($post, 0, $pos);
            }
            
            $hidden_submit = '
                <div><input type="hidden" name="id" id="id" value="'.$post_id.'"/></div>
                <div><input type="hidden" name="thread_id" id="thread_id" value="'.$thread_id.'"/></div>
                <p>
                    <input class="sub1" type="submit" name="edit_submit" id="edit_submit" tabindex="'.($tab+1).'" value="'.T_('Edit').'"/>
                    &nbsp; <a href="?thread='.$thread_id.'">'.T_('Cancel').'</a>
                </p>';
        }

        // Display the form
        echo '
            <script type="text/javascript" src="ui/js/livevalidation.js"></script>
            <script type="text/javascript" src="ui/js/fcms.js"></script>
            <form id="postform" method="post" action="messageboard.php">
                <fieldset>
                    <legend><span>'.$header.'</span></legend>
                    '.$subject.'
                    <div>
                        <label for="showname">'.T_('Name').'</label>: 
                        <input type="text" disabled="disabled" name="showname" id="showname" value="'.getUserDisplayName($this->fcmsUser->id).'" size="50"/>
                    </div>
                    '.$sticky.'
                    <script type="text/javascript">var bb = new BBCode();</script>';
        echo "\n";
        displayBBCodeToolbar();
        echo '
                    <div>
                        <textarea name="post" id="post" rows="10" cols="63" tabindex="'.$tab.'">'.$post.'</textarea>
                    </div>
                    '.$post_js.'
                    <script type="text/javascript">bb.init(\'post\');</script>
                    '.$hidden_submit.'
                </fieldset>
            </form>
            '.$reply;
    }

    /**
     * hasAwards 
     * 
     * @param   int     $user_id 
     * @return  boolean
     */
    function hasAwards ($user_id)
    {
        $user_id = (int)$user_id;

        $sql = "SELECT `id` 
                FROM `fcms_user_awards` 
                WHERE `user` = '$user_id' 
                AND `count` > 0";
        if (!$this->db2->query($sql))
        {
            $this->fcmsError->displayError();
            return false;
        }

        $rows = $this->db2->count_rows();

        if ($rows > 0)
        {
            return true;
        }

        return false;
    }

    /**
     * displayMessageBoardMenu 
     * 
     * @param int $thread_id 
     * 
     * @return void
     */
    function displayMessageBoardMenu ($thread_id = 0)
    {
        $thread_id = (int)$thread_id;

        if ($thread_id == 0)
        {
            echo '
            <div id="actions_menu">
                <ul>
                    <li class="advanced_search"><a href="?search=advanced">'.T_('Advanced Search').'</a></li>
                    <li class="search">
                        <form method="post" action="messageboard.php">
                            <input type="text" id="search" name="search"/>
                            <input type="submit" value="'.T_('Search').'"/>
                        </form>
                    </li>
                    <li><a href="messageboard.php?reply=new">'.T_('New Message').'</a></li>
                </ul>
            </div>';
        }
        else
        {
            echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="messageboard.php">'.T_('Message Board Home').'</a></li>
                </ul>
            </div>';

            if ($this->fcmsUser->access < 8 && $this->fcmsUser->access != 5)
            {
                echo '
            <div id="actions_menu">
                <ul>
                    <li><a class="action" href="messageboard.php?reply='.$thread_id.'">'.T_('Reply').'</a></li>
                </ul>
            </div>';
            }
        }
    }

    /**
     * displayAdminMenu
     * 
     * @param   int     $thread
     * @param   boolean $announcement
     * @return  void
     */
    function displayAdminMenu ($thread, $announcement)
    {
        $thread = (int)$thread;

        if ($this->fcmsUser->access <= 2)
        {
            $select_options = '<option value=""></option>';

            if ($announcement)
            {
                $select_options .= '<option value="normal">'.T_('Make Normal Thread').'</option>';
            }
            else
            {
                $select_options .= '<option value="announcement">'.T_('Make Thread an Announcement').'</option>';
            }

            $select_options .= '<option value="subject">'.T_('Edit Thread Subject').'</option>';
            $select_options .= '<option value="delete">'.T_('Delete Thread').'</option>';

            echo '
            <div id="admin_menu">
                <form method="post" action="messageboard.php">
                    <b>'.T_('Administrate Thread').':</b> 
                    <select name="admin_option">
                        '.$select_options.'
                    </select>
                    <input type="hidden" name="thread" value="'.$thread.'"/>
                    <input type="submit" name="submit_admin" value="'.T_('Submit').'"/>
                </ul>
            </div>';
        }

    }

    /**
     * displayAdminEditSubjectForm
     * 
     * @param  int  $thread 
     * @return void
     */
    function displayAdminEditSubjectForm ($thread)
    {
        $thread = (int)$thread;

        $sql = "SELECT t.`id`, p.`user`, `subject`, `started_by`, `post` 
                FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p 
                WHERE t.`id` = ?
                AND p.`thread` = t.`id`
                LIMIT 1";

        $row = $this->fcmsDatabase->getRow($sql, $thread);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $pos     = strpos($row['subject'], '#ANOUNCE#');
        $subject = $row['subject'];
        $sticky  = '';

        if ($pos !== false)
        {
            $sticky = '<input type="hidden" name="sticky" id="sticky" value="1"/>';
            $subject = substr($row['subject'], 9, strlen($row['subject'])-9);
        }

        $displayname = getUserDisplayName($row['started_by']);

        echo '
            <form method="post" action="messageboard.php">
                <fieldset>
                    <legend><span>'.T_('Edit Thread').'</span></legend>
                    <div>
                        <label for="subject">'.T_('Subject').':</label>
                        <input class="frm_text" type="text" name="subject" id="subject" size="50" value="'.cleanOutput($subject, 'html').'"/>
                    </div>
                    <div>
                        <label for="showname">'.T_('Name').':</label>
                        <input type="text" disabled="disabled" name="showname" id="showname" size="50" value="'.cleanOutput($displayname).'"/>
                    </div>
                    <p><textarea disabled="disabled" name="post" id="post" rows="10" cols="63">' . cleanOutput($row['post'], 'html') . '</textarea></p>
                    <p>
                        '.$sticky.'
                        <input type="hidden" name="thread" id="thread" value="'.$thread.'"/>
                        <input class="sub1" type="submit" name="edit_admin_submit" id="edit_admin_submit" value="'.T_('Edit').'"/>
                        '.T_('or').'
                        <a href="messageboard.php?thread='.$thread.'">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * getThreadSubject
     * 
     * @param int $id 
     * 
     * @return  void
     */
    function getThreadSubject ($id)
    {
        $id = (int)$id;

        $sql = "SELECT `subject`
                FROM `fcms_board_threads`
                WHERE `id` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $id);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        return $row['subject'];
    }

    /**
     * fixSubject 
     *  
     * Removes the '#ANOUNCE#' from annoucement subjects.
     *  
     * @param string $subject 
     * 
     * @return void
     */
    function fixSubject ($subject)
    {
        $pos = strpos($subject, '#ANOUNCE#');

        if ($pos !== false) {
            $subject = substr($subject, 9, strlen($subject)-9);
        }

        return $subject;
    }
}
