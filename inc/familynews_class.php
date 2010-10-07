<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

/**
 * FamilyNews 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class FamilyNews {

    var $db;
    var $db2;
    var $tz_offset;
    var $currentUserId;

    /**
     * FamilyNews 
     * 
     * @param  int      $currentUserId 
     * @param  string   $type 
     * @param  string   $host 
     * @param  string   $database 
     * @param  string   $user 
     * @param  string   $pass 
     * @return void
     */
    function FamilyNews ($currentUserId, $type, $host, $database, $user, $pass)
    {
        $this->currentUserId = $currentUserId;
        $this->db = new database($type, $host, $database, $user, $pass);
        $this->db2 = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` 
                FROM `fcms_user_settings` 
                WHERE `user` = '$currentUserId'";
        $this->db->query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
    }

    /**
     * displayNewsList 
     *
     * Displays a navigation list of users who have news.
     * 
     * @return void
     */
    function displayNewsList ()
    {
        $locale = new Locale();
        $sql = "SELECT u.`id`, `fname`, `lname`, `displayname`, `username`, MAX(`date`) AS d 
                FROM `fcms_news` AS n, `fcms_users` AS u, `fcms_user_settings` AS s 
                WHERE u.`id` = n.`user` 
                AND u.`id` = s.`user` GROUP BY id ORDER BY d DESC";
        $this->db->query($sql) or displaySQLError(
            'News List Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo '
            <div id="news-list">
                <h2>'.T_('Family News').'</h2>
                <ul>';

            while ($r = $this->db->get_row()) {
                $date = $locale->fixDate(T_('M. j'), $this->tz_offset, $r['d']);
                $displayname = getUserDisplayName($r['id']);
                echo '
                    <li><a href="familynews.php?getnews='.(int)$r['id'].'">'.$displayname.'</a> &nbsp;<small>'.$date.'</small></li>';
            }
            echo '
                </ul>
            </div>';
        }
    }

    /**
     * showFamilyNews 
     * 
     * @param   int     $usersnews 
     * @param   int     $id 
     * @param   int     $page 
     * @return  void
     */
    function showFamilyNews ($usersnews, $id = 0, $page = 1)
    {
        $usersnews = cleanInput($usersnews, 'int');
        $id        = cleanInput($id, 'int');

        $locale = new Locale();
        $from = (($page * 5) - 5); 
        if ($id <= 0) {
            $sql = "SELECT n.`id`, `title`, `news`, `date` 
                    FROM `fcms_news` AS n, `fcms_users` AS u 
                    WHERE `user` = '$usersnews' 
                        AND `user` = u.`id` 
                    ORDER BY `date` DESC 
                    LIMIT " . $from . ", 5";
        } else {
            $sql = "SELECT n.`id`, `title`, `news`, `date` 
                    FROM `fcms_news` AS n, `fcms_users` AS u 
                    WHERE n.`id` = '$id' 
                        AND `user` = u.`id`";
        }
        $this->db->query($sql) or displaySQLError(
            'News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        while ($row = $this->db->get_row()) {

            $date = $locale->fixDate(T_('F j, Y g:i a'), $this->tz_offset, $row['date']);
            $displayname = getUserDisplayName($usersnews);

            echo '
            <div class="news-post">
                <h2>
                    <a href="?getnews='.$usersnews.'&amp;newsid='.(int)$row['id'].'">'.cleanOutput($row['title']).'</a>
                </h2>
                <span class="date">
                    '.$date.'</b> - '.$displayname;

            if ($this->currentUserId == $usersnews || checkAccess($this->currentUserId) < 2) {
                echo ' &nbsp;
                    <form method="post" action="familynews.php">
                        <div>
                            <input type="hidden" name="user" value="'.$usersnews.'"/>
                            <input type="hidden" name="id" value="'.(int)$row['id'].'"/>
                            <input type="hidden" name="title" value="'.cleanOutput($row['title']).'"/>
                            <input type="hidden" name="news" value="'.cleanOutput($row['news']).'"/>
                            <input type="submit" name="editnews" value="'.T_('Edit').'" class="editbtn" title="'.T_('Edit this Family News').'"/>
                        </div>
                    </form>';
            }
            if (checkAccess($this->currentUserId) < 2) {
                echo ' &nbsp;
                    <form class="delnews" method="post" action="familynews.php?getnews='.$usersnews.'">
                        <div>
                            <input type="hidden" name="user" value="'.$usersnews.'"/>
                            <input type="hidden" name="id" value="'.(int)$row['id'].'"/>
                            <input type="submit" name="delnews" value="'.T_('Delete').'" class="delbtn" title="'.T_('Delete this Family News').'"/>
                        </div>
                    </form>';
            }

            echo '
                </span>';

            // Showing list of news for a user
            if ($id <= 0)
            {
                $news = removeBBCode($row['news']);

                if (strlen($row['news']) > 300) {
                    $news = substr($news, 0, 300);
                    $news .= '...<br/><br/>
                <a href="?getnews='.$usersnews.'&amp;newsid='.(int)$row['id'].'">'.T_('Read More').'</a>';
                }

                echo '
                <p>
                    '.$news.'
                </p>
                <p class="news-comments">
                    <a href="?getnews='.$usersnews.'&amp;newsid='.(int)$row['id'].'#comments">'.T_('Comments').'</a> - 
                    '.getNewsComments($row['id']).'
                </p>
            </div>';

            // Showing single news entry for user
            } else {
                echo parse($row['news']).'
            </div>
            <h3 id="comments">'.T_('Comments').'</h3>
            <p class="center">
                <form action="?getnews='.$usersnews.'&amp;newsid='.$id.'" method="post">
                    '.T_('Add Comment').'<br/>
                    <input type="text" name="comment" id="comment" size="50" title="'.T_('Add a comment').'"/> 
                    <input type="submit" name="addcom" id="addcom" value="'.T_('Add').'" class="gal_addcombtn"/>
                </form>
            </p>
            <p>&nbsp;</p>';

                $sql = "SELECT c.id, comment, `date`, fname, lname, username, user, avatar  
                        FROM fcms_news_comments AS c, fcms_users AS u 
                        WHERE news = $id 
                        AND c.user = u.id 
                        ORDER BY `date`";
                $this->db2->query($sql) or displaySQLError(
                    'News Comments Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                if ($this->db->count_rows() > 0) { 
                    while($row = $this->db2->get_row()) {
                        $displayname = getUserDisplayName($row['user']);
                        if ($this->currentUserId == $row['user'] || checkAccess($this->currentUserId) < 2) {
                            echo '
            <div class="comment_block clearfix">
                <form class="delcom" action="?getnews='.$usersnews.'&amp;newsid='.$id.'" method="post">
                    <input type="submit" name="delcom" id="delcom" value="'.T_('Delete').'" class="gal_delcombtn" title="'.T_('Delete this comment').'"/>
                    <img class="avatar" alt="avatar" src="'.getCurrentAvatar($row['user']).'"/>
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>'.cleanOutput($row['comment']).'</p>
                    <input type="hidden" name="id" value="'.(int)$row['id'].'">
                </form>
            </div>';
                        } else {
                            echo '
            <div class="comment_block clearfix">
                    <img class="avatar" src="avatar/'.basename($row['avatar']).'">
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>'.cleanOutput($row['comment']).'</p>
                </form>
            </div>';
                        }
                    }
                } else {
                    echo '
            <p class="center">'.T_('no comments').'</p>';
                }
            }
        }

        // Display Pagination
        if ($id <= 0) {
            $sql = "SELECT count(id) AS c FROM fcms_news WHERE user = '$usersnews'";
            $this->db2->query($sql) or displaySQLError(
                'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            while ($r=$this->db2->get_row()) {
                $newscount = $r['c'];
            }
            $total_pages = ceil($newscount / 5);
            displayPagination('familynews.php?getnews='.$usersnews, $page, $total_pages);
        }
    }

    /**
     * displayForm 
     * 
     * @param   string  $type 
     * @param   int     $user 
     * @param   int     $newsid 
     * @param   string  $title 
     * @param   string  $news 
     * @return  void
     */
    function displayForm ($type, $user = 0, $newsid = 0, $title='error', $news = 'error')
    {
        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <script type="text/javascript" src="inc/messageboard.inc.js"></script>';
        if ($type == 'edit') {
            echo '
            <form method="post" id="editform" action="familynews.php">
                <fieldset>
                    <legend><span>'.T_('Edit News').'</span></legend>';
        } else {
            echo '
            <form method="post" id="addform" action="familynews.php">
                <fieldset>
                    <legend><span>'.T_('Add News').'</span></legend>';
        }
        echo '
                    <p>
                        <label for="title">'.T_('Title').'</label>:
                        <input type="text" name="title" id="title" title="'.T_('Title of your Family News').'"';
        if ($type == 'edit') {
            echo ' value="'.$title.'"';
        }
        echo ' size="50"/>
                    </p>
                    <script type="text/javascript">
                        var ftitle = new LiveValidation(\'title\', { onlyOnSubmit:true });
                        ftitle.add(Validate.Presence, { failureMessage: "" });
                    </script>
                    <script type="text/javascript">var bb = new BBCode();</script>';
        displayMBToolbar();
        echo '
                    <div><textarea name="post" id="post" rows="10" cols="63"';
        if ($type == 'add') {
            echo "></textarea></div>";
        } else {
            echo ">".$news."</textarea></div>";
        }
        echo '
                    <script type="text/javascript">bb.init(\'post\');</script>
                    <p>';
        if ($type == 'add') {
            echo '
                        <input class="sub1" type="submit" name="submitadd" value="'.T_('Add').'"/>';
        } else {
            echo '
                        <input type="hidden" name="id" value="'.(int)$newsid.'"/>
                        <input type="hidden" name="user" value="'.(int)$user.'"/>
                        <input class="sub1" type="submit" name="submitedit" value="'.T_('Edit').'"/>';
        }
        echo '
                         &nbsp;'.T_('or').' &nbsp;
                        <a href="familynews.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * displayLast5News 
     * 
     * @return void
     */
    function displayLast5News ()
    {
        $locale = new Locale();
        $sql = "SELECT * 
                FROM `fcms_news` 
                ORDER BY `date` DESC 
                LIMIT 5";
        $this->db->query($sql) or displaySQLError(
            'Last 5 News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0)
        {
            while ($row = $this->db->get_row())
            {
                $date = $locale->fixDate(T_('F j, Y g:i a'), $this->tz_offset, $row['date']);
                $displayname = getUserDisplayName($row['user']);

                $news = removeBBCode($row['news']);
                if (strlen($news) > 300) {
                    $news = substr($news, 0, 300);
                    $news .= '...<br/><br/>
                            <a href="?getnews='.$row['user'].'&amp;newsid='.$row['id'].'">'.T_('Read More').'</a>';
                }

                echo '
                    <div class="news-post">
                        <h2>
                            <a href="?getnews='.(int)$row['user'].'&amp;newsid='.(int)$row['id'].'">'.cleanOutput($row['title']).'</a>
                        </h2>
                        <span class="date">'.$date.' - '.$displayname.'</span>
                        <p>
                            '.$news.'
                        </p>
                        <p class="news-comments">
                            <a href="?getnews='.$row['user'].'&amp;newsid='.$row['id'].'#comments">'.T_('Comments').'</a> - '
                            . getNewsComments($row['id']) . '
                        </p>
                    </div>';
            }
        } else {
            echo '
            <div class="info-alert">
                <h2>'.T_('Welcome to the Family News Section.').'</h2>
                <p><i>'.T_('Currently no one has added any news.').'</i></p>
                <p><a href="?addnews=yes">'.T_('Add Family News').'</a></p>
            </div>';
        }
    }

    /**
     * hasNews 
     * 
     * @param  int  $userid 
     * @return void
     */
    function hasNews ($userid)
    {
        $userid = cleanInput($userid, 'int');
        $sql = "SELECT `id` 
                FROM `fcms_news` 
                WHERE `user` = '$userid' 
                LIMIT 1";
        $this->db->query($sql) or displaySQLError(
            'Has News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * displayWhatsNewFamilyNews 
     * 
     * @return void
     */
    function displayWhatsNewFamilyNews ()
    {
        $locale = new Locale();
        $today_start = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '000000';
        $today_end = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '235959';

        $sql = "SELECT n.`id`, n.`title`, u.`id` AS userid, n.`date` 
                FROM `fcms_users` AS u, `fcms_news` AS n 
                WHERE u.`id` = n.`user` 
                    AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 
                ORDER BY `date` DESC 
                LIMIT 0, 5";
        $this->db->query($sql) or displaySQLError(
            'What\'s New Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo '
            <h3>'.T_('Family News').'</h3>
            <ul>';
            while ($row = $this->db->get_row()) {
                $displayname = getUserDisplayName($row['userid']);
                $date = $locale->fixDate('YmdHis', $this->tz_offset, $row['date']);
                if ($date >= $today_start && $date <= $today_end) {
                    $full_date = T_('Today');
                    $d = ' class="today"';
                } else {
                    $full_date = $locale->fixDate(T_('M. j, Y g:i a'), $this->tz_offset, $row['date']);
                    $d = '';
                }
                echo '
                <li>
                    <div'.$d.'>'.$full_date.'</div>
                    <a href="familynews.php?getnews='.(int)$row['userid'].'&amp;newsid='.(int)$row['id'].'">'.cleanOutput($row['title']).'</a> - 
                    <a class="u" href="profile.php?member='.(int)$row['userid'].'">'.$displayname.'</a>
                </li>';
            }
            echo '
            </ul>';
        }
    }

} ?>
