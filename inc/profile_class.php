<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

class Profile {

    var $db;
    var $db2;
    var $tz_offset;
    var $current_user_id;

    function Profile ($current_user_id, $type, $host, $database, $user, $pass)
    {
        $this->current_user_id = $current_user_id;
        $this->db = new database($type, $host, $database, $user, $pass);
        $this->db2 = new database($type, $host, $database, $user, $pass);
        $this->db->query("SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id") or die('<h1>Timezone Error (profile.class.php 16)</h1>' . mysql_error());
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
    }

    function displayProfile ($userid)
    {
        $locale = new Locale();

        // Check for valid user id
        if (!ctype_digit($userid)) {
            echo '
            <p class="error-alert">'.T_('Invalid User').'</p>';
            return;
        }

        $sql = "SELECT u.fname, u.lname, u.email, u.birthday, u.avatar, u.username, u.joindate, u.activity, 
                    a.`address`, a.`city`, a.`state`, a.`zip`, a.`home`, a.`cell`, a.`work`  
                FROM fcms_users AS u, fcms_address AS a 
                WHERE u.id = $userid 
                AND u.id = a.user";
        $this->db->query($sql) or displaySQLError(
            'Profile Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();

        // Rank Info
        $points = round(getUserRankById($userid), 2);
        $pts = 0;
        if ($points > 50) { 
            $rank_img = "<div title=\"".T_('Elder')." ($points)\" class=\"rank7\"></div>";
            $rank = T_('Elder');
            $next_rank = "<i>none</i>";
        } elseif ($points > 30) {
            $rank_img = "<div title=\"".T_('Adult')." ($points)\" class=\"rank6\"></div>";
            $rank = T_('Adult');
            $next_rank = T_('Elder');
            $pts = 50;
        } elseif ($points > 20) {
            $rank_img = "<div title=\"".T_('Mature Adult')." ($points)\" class=\"rank5\"></div>";
            $rank = T_('Mature Adult');
            $next_rank = T_('Adult');
            $pts = 30;
        } elseif ($points > 10) {
            $rank_img = "<div title=\"".T_('Young Adult')." ($points)\" class=\"rank4\"></div>";
            $rank = T_('Young Adult');
            $next_rank = T_('Mature Adult');
            $pts = 20;
        } elseif ($points > 5) {
            $rank_img = "<div title=\"".T_('Teenager')." ($points)\" class=\"rank3\"></div>";
            $rank = T_('Teenager');
            $next_rank = T_('Young Adult');
            $pts = 10;
        } elseif ($points > 3) {
            $rank_img = "<div title=\"".T_('Kid')." ($points)\" class=\"rank2\"></div>";
            $rank = T_('Kid');
            $next_rank = T_('Teenager');
            $pts = 5;
        } elseif ($points > 1) {
            $rank_img = "<div title=\"".T_('Toddler')." ($points)\" class=\"rank1\"></div>";
            $rank = T_('Toddler');
            $next_rank = T_('Kid');
            $pts = 3;
        } else {
            $rank_img = "<div title=\"".T_('Baby')." ($points)\" class=\"rank0\"></div>";
            $rank = T_('Baby');
            $next_rank = T_('Toddler');
            $pts = 1;
        }

        // Dates Info
        $joinDate = $locale->fixDate(T_('F j, Y'), $this->tz_offset, $row['joindate']);
        $activityDate = $locale->fixDate(T_('F j, Y g:i a'), $this->tz_offset, $row['activity']);

        // Stats Info
        $posts = '';
        $photos = '';
        $comments = '';
        $calendars = '';
        $news = '';
        $recipes = '';
        $documents = '';
        $prayers = '';
        // If user is not a guest
        if (checkAccess($this->current_user_id) != 10) {
            $postsCount = getPostsById($userid, 'array');
            $photosCount = getPhotosById($userid, 'array');
            $commentsCount = getCommentsById($userid, 'array');
            $calendarsCount = getCalendarEntriesById($userid, 'array');
            $posts = '<div class="stat c1">
                        <span title="'.$postsCount['percent'].' of total">'.$postsCount['count'].'</span>
                        <b>'.T_('Posts').'</b>
                    </div>';
            $photos = '<div class="stat c2">
                        <span title="'.$photosCount['percent'].' of total">'.$photosCount['count'].'</span>
                        <b>'.T_('Photos').'</b>
                    </div>';
            $comments = '<div class="stat c3">
                        <span title="'.$commentsCount['percent'].' of total">'.$commentsCount['count'].'</span>
                        <b>'.T_('Comments').'</b>
                    </div>';
            $calendars = '<div class="stat c4">
                        <span title="'.$calendarsCount['percent'].' of total">'.$calendarsCount['count'].'</span>
                        <b>'.T_('Dates').'</b>
                    </div>';
            $i = 5;
            if (usingFamilyNews()) {
                $newsCount = getFamilyNewsById($userid, 'array');
                $news = '<div class="stat c'.$i.'">
                        <span title="'.$newsCount['percent'].' of total">'.$newsCount['count'].'</span>
                        <b>'.T_('Family News').'</b>
                    </div>';
                $i++;
            }
            if (usingRecipes()) {
                $recipesCount = getRecipesById($userid, 'array');
                $recipes = '<div class="stat c'.$i.'">
                        <span title="'.$recipesCount['percent'].' of total">'.$recipesCount['count'].'</span>
                        <b>'.T_('Recipes').'</b>
                    </div>';
                $i++;
            }
            if (usingDocuments()) {
                $documentsCount = getDocumentsById($userid, 'array');
                $documents = '<div class="stat c'.$i.'">
                        <span title="'.$documentsCount['percent'].' of total">'.$documentsCount['count'].'</span>
                        <b>'.T_('Documents').'</b>
                    </div>';
                $i++;
            }
            if (usingPrayers()) {
                $prayersCount = getPrayersById($userid, 'array');
                $prayers = '<div class="stat c'.$i.'">
                        <span title="'.$prayersCount['percent'].' of total">'.$prayersCount['count'].'</span>
                        <b>'.T_('Prayer Concerns').'</b>
                    </div>';
                $i++;
            }
        }
        $ptsToGo = $pts - round($points, 2);
        if ($ptsToGo > 0) {
            $ptsToGo = "<small>(".sprintf(_ngettext('%s point to go', '%s points to go', $ptsToGo), $ptsToGo).")</small>";
        } else { 
            $ptsToGo = "";
        }

        // Address
        $address = '';
        if (empty($row['address']) && empty($row['state'])) {
            $address = "<i>(".T_('none').")</i>";
        } else {
            if (!empty($row['address'])) {
                $address .= $row['address'] . "<br/>";
            }
            if (!empty($row['city'])) {
                $address .= $row['city'] . ", ";
            }
            $address .= $row['state'] . " " . $row['zip'];
        }
        // Phone Numbers
        $home = empty($row['home']) ? "<i>(" . T_('none') . ")</i>" : $row['home'];
        $work = empty($row['work']) ? "<i>(" . T_('none') . ")</i>" : $row['work'];
        $cell = empty($row['cell']) ? "<i>(" . T_('none') . ")</i>" : $row['cell'];

        // Print the profile info
        echo '
            <div id="side-info">
                <img class="avatar" src="gallery/avatar/'.$row['avatar'].'" alt="avatar"/>
                <div class="name">
                    <h3>'.$row['fname'].' '.$row['lname'].'</h3>
                    <h4>'.$row['username'].'</h4>
                    '.$rank_img.'
                </div>
                <p>
                    <a class="action" href="privatemsg.php?compose=new&amp;id='.$userid.'">'.T_('Send PM').'</a>
                </p>
                <p>
                    <b>'.T_('Address').'</b><br/>
                    '.$address.'
                </p>
                <p>
                    <b>'.T_('Home Phone').'</b>: '.$home.'<br/>
                    <b>'.T_('Work Phone').'</b>: '.$work.'<br/>
                    <b>'.T_('Cell Phone').'</b>: '.$cell.'<br/>
                </p>
                <hr/>
                <h4>'.T_('Rank').'</h4>
                <p>
                    <b>'.T_('Points').':</b> '.$points.'<br/>
                    <b>'.T_('Rank').':</b> '.$rank.'<br/>
                    <b>'.T_('Next Rank').':</b> '.$next_rank.' '.$ptsToGo.'
                </p>
                <hr/>
                <p><b>'.T_('Join Date').':</b><br/>'.$joinDate.'</p>
                <p><b>'.T_('Last Visit').':</b><br/>'.$activityDate.'</p>
            </div>
            <div id="main-info">
                <div id="stats" class="clearfix">
                    '.$posts.'
                    '.$photos.'
                    '.$comments.'
                    '.$calendars.'
                    '.$news.'
                    '.$recipes.'
                    '.$documents.'
                    '.$prayers.'
                </div>';
        if (checkAccess($this->current_user_id) < 8 && checkAccess($this->current_user_id) != 5) {
            echo '
                <h2>'.T_('Last 5 Posts').'</h2>';
            $this->displayLast5Posts($userid);
        }
        if (checkAccess($this->current_user_id) <= 3 || checkAccess($this->current_user_id) == 8) {
            echo '
                <h2>'.T_('Last 5 Photos').'</h2>';
            $this->displayLast5Photos($userid);
        }
        echo '
            </div>
            <div style="clear:both"></div>';
    }

    function displayAll ()
    {
        echo '
            <script type="text/javascript" src="inc/tablesort.js"></script>
            <table class="sortable">
                <thead>
                    <tr>
                        <th>'.T_('Username').'</th>
                        <th>'.T_('First Name').'</th>
                        <th>'.T_('Last Name').'</th>
                        <th>'.T_('Posts').'</th>
                        <th>'.T_('Photos').'</th>
                        <th class="sortfirstdesc">'.T_('Rank').'</th>
                        <th>'.T_('Age').'</th>
                    </tr>
                </thead>
                <tbody>';
        $sql = "SELECT u.`id`, u.`fname`, u.`lname`, u.`birthday`, u.`avatar`, u.`username`, u.`activity`, a.`state` 
                FROM `fcms_users` AS u, `fcms_address` AS a 
                WHERE u.`id` = a.`user` 
                AND u.`password` != 'NONMEMBER'
                AND u.`password` != 'PRIVATE'";
        $this->db->query($sql) or displaySQLError(
            'Users Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($row = $this->db->get_row()) {

            // Calculate Age
            $birthday = $row['birthday'];
            list($year,$month,$day) = explode("-",$birthday);
            $year_diff  = gmdate("Y") - $year;
            $month_diff = gmdate("m") - $month;
            $day_diff   = gmdate("d") - $day;
            if ($month_diff < 0) {
                $year_diff--;
            } elseif (($month_diff==0) && ($day_diff < 0)) {
                $year_diff--;
            }
            $birthday = $year_diff;

            // Points
            $points = getUserRankById($row['id']);
            $points = round($points, 2);
            if ($points > 50) {
                $uname_display = "<div title=\"".T_('Elder')." ($points)\" class=\"rank7\"></div>";
            } elseif ($points > 30) {
                $uname_display = "<div title=\"".T_('Adult')." ($points)\" class=\"rank6\"></div>";
            } elseif ($points > 20) {
                $uname_display = "<div title=\"".T_('Mature Adult')." ($points)\" class=\"rank5\"></div>";
            } elseif ($points > 10) {
                $uname_display = "<div title=\"".T_('Young Adult')." ($points)\" class=\"rank4\"></div>";
            } elseif ($points > 5) {
                $uname_display = "<div title=\"".T_('Teenager')." ($points)\" class=\"rank3\"></div>";
            } elseif($points > 3) {
                $uname_display = "<div title=\"".T_('Kid')." ($points)\" class=\"rank2\"></div>";
            } elseif ($points > 1) {
                $uname_display = "<div title=\"".T_('Toddler')." ($points)\" class=\"rank1\"></div>";
            } else {
                $uname_display = "<div title=\"".T_('Baby')." ($points)\" class=\"rank0\"></div>";
            }

            // Display user info row
            echo '
                    <tr>
                        <td><a href="profile.php?member='.$row['id'].'">'.$row['username'].'</a><br/>'.$uname_display.'</td>
                        <td>'.$row['fname'].'</td>
                        <td>'.$row['lname'].'</td>
                        <td>'.getPostsById($row['id']).'</td>
                        <td>'.getPhotosById($row['id']).'</td>
                        <td>'.$points.'</td>
                        <td>'.$birthday.'</td>
                    </tr>';
        }
        echo '
                </tbody>
            </table>';
    }

    function displayPointsToGo ($pts)
    {
        // Removed in 2.2
        $posts = ceil($pts / (1 / 75));
        $photos = ceil($pts / (1 / 25));
        $comments = ceil($pts / (1 / 20));
        $calendar = ceil($pts / (1 / 5));
        $news = ceil($pts / (1 / 10));
        echo '
                <div><small><i>&nbsp;&nbsp;&nbsp; '.$posts.' new posts &nbsp;&nbsp;- or -</i></small></div>
                <div><small><i>&nbsp;&nbsp;&nbsp; '.$photos.' new photos &nbsp;&nbsp;- or -</i></small></div>
                <div><small><i>&nbsp;&nbsp;&nbsp; '.$comments.' new comments &nbsp;&nbsp;- or -</i></small></div>
                <div><small><i>&nbsp;&nbsp;&nbsp; '.$calendar.' new calendar entries &nbsp;&nbsp;- or -</i></small></div>
                <div><small><i>&nbsp;&nbsp;&nbsp; '.$comments.' new family news entries &nbsp;&nbsp;- or -</i></small></div>';
    }

    function displayLast5Posts ($userid)
    {
        $locale = new Locale();
        $sql = "SELECT t.`id`, `subject`, `date`, `post` 
                FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, `fcms_users` AS u 
                WHERE t.`id` = p.`thread` 
                AND p.`user` = u.`id` 
                AND u.`id` = $userid 
                ORDER BY `date` DESC 
                LIMIT 0, 5";
        $this->db2->query($sql) or displaySQLError(
            'Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db2->count_rows() > 0) {
            while ($row = $this->db2->get_row()) {
                $date = $locale->fixDate(T_('F j, Y, g:i a'), $this->tz_offset, $row['date']);
                $search = array('/\[ins\](.*?)\[\/ins\]/is', '/\[del\](.*?)\[\/del\]/is', '/\[h1\](.*?)\[\/h1\]/is', '/\[h2\](.*?)\[\/h2\]/is', '/\[h3\](.*?)\[\/h3\]/is', '/\[h4\](.*?)\[\/h4\]/is', '/\[h5\](.*?)\[\/h5\]/is', '/\[h6\](.*?)\[\/h6\]/is', 
                    '/\[b\](.*?)\[\/b\]/is', '/\[i\](.*?)\[\/i\]/is', '/\[u\](.*?)\[\/u\]/is', '/\[url\=(.*?)\](.*?)\[\/url\]/is', '/\[url\](.*?)\[\/url\]/is', '/\[align\=(left|center|right)\](.*?)\[\/align\]/is','/\[img\=(.*?)\]/is', '/\[img\](.*?)\[\/img\]/is', 
                    '/\[mail\=(.*?)\](.*?)\[\/mail\]/is', '/\[mail\](.*?)\[\/mail\]/is', '/\[font\=(.*?)\](.*?)\[\/font\]/is', '/\[size\=(.*?)\](.*?)\[\/size\]/is', '/\[color\=(.*?)\](.*?)\[\/color\]/is', '/\[span\](.*?)\[\/span\]/is', '/\[span\=(.*?)\](.*?)\[\/span\]/is');
                $replace = array('$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$2', '$1', '$2', '$1', '1', '$2', '$1', '$2', '$2','$2', '$1', '$2');
                $post = preg_replace($search, $replace, stripslashes($row['post']));
                $post = htmlentities($post, ENT_COMPAT, 'UTF-8');
                $subject = stripslashes($row['subject']);
                $pos = strpos($subject, '#ANOUNCE#');
                if ($pos !== false) {
                    $subject = substr($subject, 9, strlen($subject)-9);
                }
                echo '
                <p>
                    <a href="messageboard.php?thread='.$row['id'].'">'.$subject.'</a> 
                    <span class="date">'.$date.'</span><br/>
                    '.$post.'
                </p>';
            }
        } else {
            echo '
                <p>'.T_('none').'</p>';
        }
    }

    function displayLast5Photos ($userid)
    {
        $sql = "SELECT * 
                FROM `fcms_gallery_photos` 
                WHERE user = $userid 
                ORDER BY `date` DESC 
                LIMIT 5";
        $this->db2->query($sql) or displaySQLError(
            'Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db2->count_rows() > 0) {
            echo '
            <ul class="photos clearfix">';
            while ($row = $this->db2->get_row()) {
                echo '
                <li class="photo">
                    <a href="gallery/index.php?uid='.$userid.'&amp;cid='.$row['category'].'&amp;pid='.$row['id'].'">
                        <img class="photo" src="gallery/photos/member'.$row['user'].'/tb_'.$row['filename'].'" alt=""/>
                    </a>
                </li>';
            }
            echo '
            </ul>';
        } else {
            echo "<p>".T_('none')."</p>";
        }
    }

    function displayAwards ()
    {
        $this->db->query("SELECT `count` FROM `fcms_user_awards` WHERE `id` = 1") or die('<h1>Awards Error (profile.class.php 130)</h1>' . mysql_error());
        $check = $this->db->get_row();
        if ($check['count'] > 0) {
            $this->db->query("SELECT * FROM `fcms_user_awards` WHERE `type` = 'top5poster'") or die('<h1>Awards Error (profile.class.php 133)</h1>' . mysql_error());
            echo '
            <h2>'.T_('Message Board').'</h2>
            <h3><dfn title="'.T_('for having the most posts').'"></dfn>'.T_('Most Talkative Award').'</h3>
            <ol>';
            while ($row = $this->db->get_row()) {
                if ($row['count'] > 0) {
                    echo '
                <li><a class="u" href="profile.php?member='.$row['user'].'">'.getUserDisplayName($row['user']).'</a> - '.$row['count'].' '.T_('posts').'</li>';
                }
            }
            echo '
            </ol>';
            $this->db->query("SELECT * FROM `fcms_user_awards` WHERE `type` = 'topthreadstarter'") or die('<h1>Awards Error (profile.class.php 139)</h1>' . mysql_error());
            $row = $this->db->get_row();
            $this->db2->query("SELECT `subject` FROM `fcms_board_threads` WHERE `id` = " . $row['value']) or die('<h1>Subject Error (profile.class.php 141)</h1>' . mysql_error());
            $row2 = $this->db2->get_row();
            $pos = strpos($row2['subject'], '#ANOUNCE#');
            if ($pos !== false) { $subject = substr($row2['subject'], 9, strlen($row2['subject'])-9); } else { $subject = $row2['subject']; }
            echo '
            <p>&nbsp;</p>
            <h3><dfn title="'.T_('for starting the most active thread').'">'.T_('Conversation Starter Award').'</dfn></h3>
            <p>
                <a class="u" href="profile.php?member='.$row['user'].'">'.getUserDisplayName($row['user']).'</a> - 
                <a href="messageboard.php?thread='.$row['value'].'">'.$subject.'</a>, '.$row['count'].' '.T_('replies').'
            </p>';
            $this->db->query("SELECT * FROM `fcms_user_awards` WHERE `type` = 'mostsmileys'") or die('<h1>Awards Error (profile.class.php 147)</h1>' . mysql_error());
            $row = $this->db->get_row();
            echo '
            <p>&nbsp;</p>
            <h3><dfn title="'.T_('for using the most smileys').'">'.T_('Smiley Award').'</dfn></h3>
            <p><a class="u" href="profile.php?member='.$row['user'].'">'.getUserDisplayName($row['user']).'</a></p>';
        } else {
            echo '
            <p class="info-alert">'.T_('No Message Board Awards have been awarded yet.').'</p>';
        }
        $this->db->query("SELECT `count` FROM `fcms_user_awards` WHERE `id` = 8") or die('<h1>Awards Error (profile.class.php 153)</h1>' . mysql_error());
        $check = $this->db->get_row();
        if ($check['count'] > 0) {
            $this->db->query("SELECT * FROM `fcms_user_awards` WHERE `type` = 'top5photo'") or die('<h1>Awards Error (profile.class.php 156)</h1>' . mysql_error());
            echo '
            <h2>'.T_('Photo Gallery').'</h2>
            <h3><dfn title="'.T_('for submitting the most photos').'">'.T_('Photographer Award').'</dfn></h3>
            <ol>';
            while ($row = $this->db->get_row()) {
                if ($row['count'] > 0) {
                    echo '
                <li><a class="u" href="profile.php?member='.$row['user'].'">'.getUserDisplayName($row['user']).'</a> - '.$row['count'].' '.T_('photos').'</li>';
                }
            }
            echo '
            </ol>
            <p>&nbsp;</p>
            <h3><dfn title="'.T_('for uploading the most viewed photo').'">'.T_('Most Photogenic Award').'</dfn></h3>';
            $this->db->query("SELECT * FROM `fcms_user_awards` WHERE `type` = 'topviewedphoto'") or die('<h1>Awards Error (profile.class.php 162)</h1>' . mysql_error());
            $row = $this->db->get_row();
            $this->db2->query("SELECT `user`, `filename`, `category` FROM `fcms_gallery_photos` WHERE `id` = " . $row['value']) or die('<h1>Filename Error (profile.class.php 164)</h1>' . mysql_error());
            $row2 = $this->db2->get_row();
            echo '
            <p>
                <a class="u" href="profile.php?member='.$row['user'].'">'.getUserDisplayName($row['user']).'</a><br/>
                <a href="gallery/index.php?uid='.$row['user'].'&cid='.$row2['category'].'&pid='.$row['value'].'">
                    <img src="gallery/photos/member'.$row2['user'].'/tb_'.$row2['filename'].'"/>
                </a>
            </p>
            <p>&nbsp;</p>';
        } else {
            echo '
            <p class="info-alert">'.T_('No Photo Gallery Awards have been awarded yet.').'</p>';
        }
    }

} ?>
