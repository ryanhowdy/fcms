<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

class FamilyNews {

    var $db;
    var $db2;
    var $tz_offset;
    var $cur_user_id;

    function FamilyNews ($current_user_id, $type, $host, $database, $user, $pass)
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
                <h2>'._('Family News').'</h2>
                <ul>';

            while ($r = $this->db->get_row()) {
                $date = $locale->fixDate(_('M. j'), $this->tz_offset, $r['d']);
                $displayname = getUserDisplayName($r['id']);
                echo '
                    <li><a href="familynews.php?getnews='.$r['id'].'">'.$displayname.'</a> &nbsp;<small>'.$date.'</small></li>';
            }
            echo '
                </ul>
            </div>';
        }
    }

    function showFamilyNews ($usersnews, $id = '0', $page = '1')
    {
        $locale = new Locale();
        $from = (($page * 5) - 5); 
        if ($id <= 0) {
            $sql = "SELECT n.`id`, `title`, `news`, `date` 
                    FROM `fcms_news` AS n, `fcms_users` AS u 
                    WHERE `user` = $usersnews 
                        AND `user` = u.`id` 
                    ORDER BY `date` DESC 
                    LIMIT " . $from . ", 5";
        } else {
            $sql = "SELECT n.`id`, `title`, `news`, `date` 
                    FROM `fcms_news` AS n, `fcms_users` AS u 
                    WHERE n.`id` = $id 
                        AND `user` = u.`id`";
        }
        $this->db->query($sql) or displaySQLError(
            'News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($row = $this->db->get_row()) {
            $date = $locale->fixDate(_('F j, Y g:i a'), $this->tz_offset, $row['date']);
            $displayname = getUserDisplayName($usersnews);
            echo '
            <div class="news-post">
                <h2>
                    <a href="?getnews='.$usersnews.'&amp;newsid='.$row['id'].'">'.$row['title'].'</a>
                </h2>
                <span class="date">
                    '.$date.'</b> - '.$displayname;
            if ($_SESSION['login_id'] == $usersnews || checkAccess($_SESSION['login_id']) < 2) {
                echo ' &nbsp;
                    <form method="post" action="familynews.php">
                        <div>
                            <input type="hidden" name="user" value="'.$usersnews.'"/>
                            <input type="hidden" name="id" value="'.$row['id'].'"/>
                            <input type="hidden" name="title" value="'.htmlentities($row['title'], ENT_COMPAT, 'UTF-8').'"/>
                            <input type="hidden" name="news" value="'.htmlentities($row['news'], ENT_COMPAT, 'UTF-8').'"/>
                            <input type="submit" name="editnews" value="'._('Edit').'" class="editbtn" title="'._('Edit this Family News').'"/>
                        </div>
                    </form>';
            }
            if (checkAccess($_SESSION['login_id']) < 2) {
                echo ' &nbsp;
                    <form class="delnews" method="post" action="familynews.php?getnews='.$usersnews.'">
                        <div>
                            <input type="hidden" name="user" value="'.$usersnews.'"/>
                            <input type="hidden" name="id" value="'.$row['id'].'"/>
                            <input type="submit" name="delnews" value="'._('Delete').'" class="delbtn" title="'._('Delete this Family News').'"/>
                        </div>
                    </form>';
            }
            echo '
                </span>
                <p>';
            if ($id <= 0) {
                echo parse(rtrim(substr($row['news'], 0, 300)));
                if (strlen($row['news']) > 300) {
                    echo '...<br/><br/>
                <a href="?getnews='.$usersnews.'&amp;newsid='.$row['id'].'">'._('Read More').'</a>';
                }
                echo '
                </p>
                <p class="news-comments">
                    <a href="#" onclick="window.open(\'inc/familynews_comments.php?newsid='.$row['id'].'\', 
                        \'_Comments\', \'height=400,width=550,resizable=yes,scrollbars=yes\');return false;">'._('Comments').'</a> - 
                    '.getNewsComments($row['id']).'
                </p>
            </div>';
            } else {
                echo parse($row['news']).'
            </div>
            <h3>'._('Comments').'</h3>
            <p class="center">
                <form action="?getnews='.$usersnews.'&amp;newsid='.$id.'" method="post">
                    '._('Add Comment').'<br/>
                    <input type="text" name="comment" id="comment" size="50" title="'._('Add a comment').'"/> 
                    <input type="submit" name="addcom" id="addcom" value="'._('Add').'" class="gal_addcombtn"/>
                </form>
            </p>
            <p class="center">&nbsp;</p>';
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
                        if ($this->cur_user_id == $row['user'] || checkAccess($this->cur_user_id) < 2) {
                            echo '
            <div class="comment_block clearfix">
                <form class="delcom" action="?getnews='.$usersnews.'&amp;newsid='.$id.'" method="post">
                    <input type="submit" name="delcom" id="delcom" value="'._('Delete').'" class="gal_delcombtn" title="'._('Delete this comment').'"/>
                    <img src="gallery/avatar/'.$row['avatar'].'">
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>';
                            echo htmlentities(stripslashes($row['comment']), ENT_COMPAT, 'UTF-8');
                            echo '</p>
                    <input type="hidden" name="id" value="'.$row['id'].'">
                </form>
            </div>';
                        } else {
                            echo '
            <div class="comment_block clearfix">
                    <img src="avatar/'.$row['avatar'].'">
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>';
                            echo htmlentities(stripslashes($row['comment']), ENT_COMPAT, 'UTF-8');
                            echo '</p>
                </form>
            </div>';
                        }
                    }
                } else {
                    echo '
            <p class="center">'._('no comments').'</p>';
                }
            }
        }

        // Display Pagination
        if ($id <= 0) {
            $sql = "SELECT count(id) AS c FROM fcms_news WHERE user = $usersnews";
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

    function displayForm ($type, $user = '0', $newsid = '0', $title='error', $news = 'error')
    {
        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <script type="text/javascript" src="inc/messageboard.inc.js"></script>';
        if ($type == 'edit') {
            echo '
            <form method="post" id="editform" action="familynews.php">
                <fieldset>
                    <legend><span>'._('Edit News').'</span></legend>';
        } else {
            echo '
            <form method="post" id="addform" action="familynews.php">
                <fieldset>
                    <legend><span>'._('Add News').'</span></legend>';
        }
        echo '
                    <p>
                        <label for="title">'._('Title').'</label>:
                        <input type="text" name="title" id="title" title="'._('Title of your Family News').'"';
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
            echo ">$news</textarea></div>";
        }
        echo '
                    <script type="text/javascript">bb.init(\'post\');</script>
                    <p>';
        if ($type == 'add') {
            echo '
                        <input class="sub1" type="submit" name="submitadd" value="'._('Add').'"/>';
        } else {
            echo '
                        <input type="hidden" name="id" value="'.$newsid.'"/>
                        <input type="hidden" name="user" value="'.$user.'"/>
                        <input class="sub1" type="submit" name="submitedit" value="'._('Edit').'"/>';
        }
        echo '
                         &nbsp;'._('or').' &nbsp;
                        <a href="familynews.php">'._('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    function displayLast5News ()
    {
        $locale = new Locale();
        $sql = "SELECT * FROM `fcms_news` ORDER BY `date` DESC LIMIT 5";
        $this->db->query($sql) or displaySQLError(
            'Last 5 News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            while ($row = $this->db->get_row()) {
                $date = $locale->fixDate(_('F j, Y g:i a'), $this->tz_offset, $row['date']);
                $displayname = getUserDisplayName($row['user']);
                echo '
                    <div class="news-post">
                        <h2>
                            <a href="?getnews='.$row['user'].'&amp;newsid='.$row['id'].'">'.$row['title'].'</a>
                        </h2>
                        <span class="date">'.$date.' - '.$displayname.'</span>
                        <p>
                            '.parse(rtrim(substr($row['news'], 0, 300)));
                if (strlen($row['news']) > 300) {
                    echo '...<br/><br/>
                            <a href="?getnews='.$row['user'].'&amp;newsid='.$row['id'].'">'._('Read More').'</a>';
                }
                echo '
                        </p>
                        <p class="news-comments">
                            <a href="#" onclick="window.open(\'inc/familynews_comments.php?newsid='.$row['id'].'\', 
                                \'_Comments\', \'height=400,width=550,resizable=yes,scrollbars=yes\'); return false;">
                                '._('Comments').'
                            </a> - '.getNewsComments($row['id']).'
                        </p>
                    </div>';
            }
        } else {
            echo '
            <div class="info-alert">
                <h2>'._('Welcome to the Family News Section.').'</h2>
                <p><i>'._('Currently no one has added any news.').'</i></p>
                <p><a href="?addnews=yes">'._('Add Family News').'</a></p>
            </div>';
        }
    }

    function hasNews ($userid)
    {
        $sql = "SELECT * FROM `fcms_news` WHERE `user` = $userid LIMIT 1";
        $this->db->query($sql) or displaySQLError(
            'Has News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    function displayWhatsNewFamilyNews ()
    {
        $locale = new Locale();
        $today = date('Y-m-d');
        $tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
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
            <h3>'._('Family News').'</h3>
            <ul>';
            while ($row = $this->db->get_row()) {
                $displayname = getUserDisplayName($row['userid']);
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
                    <a href="familynews.php?getnews='.$row['userid'].'&amp;newsid='.$row['id'].'">'.$row['title'].'</a> - 
                    <a class="u" href="profile.php?member='.$row['userid'].'">'.$displayname.'</a>
                </li>';
            }
            echo '
            </ul>';
        }
    }

} ?>
