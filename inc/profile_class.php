<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');
include_once('familytree_class.php');
include_once('awards_class.php');

/**
 * Profile 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Profile {

    var $db;
    var $db2;
    var $tz_offset;
    var $currentUserId;

    /**
     * Profile 
     * 
     * @param   int     $currentUserId 
     * @param   string  $type 
     * @param   string  $host 
     * @param   string  $database 
     * @param   string  $user 
     * @param   string  $pass 
     * @return  void
     */
    function Profile ($currentUserId, $type, $host, $database, $user, $pass)
    {
        $this->currentUserId = cleanInput($currentUserId, 'int');
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
        $this->tree = new FamilyTree($currentUserId, $type, $host, $database, $user, $pass);
    }

    /**
     * displayProfile 
     * 
     * @param   int     $userid 
     * @return  void
     */
    function displayProfile ($userid)
    {
        global $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass;

        $locale = new Locale();
        $database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $awards = new Awards($this->currentUserId, $database);


        // Check for valid user id
        if (!ctype_digit($userid)) {
            echo '
            <p class="error-alert">'.T_('Invalid User').'</p>';
            return;
        }

        $sql = "SELECT u.fname, u.lname, u.email, u.birthday, u.avatar, u.username, u.joindate, u.activity, 
                    u.`sex`, a.`id` AS aid, a.`address`, a.`city`, a.`state`, a.`zip`, a.`home`, a.`cell`, a.`work`  
                FROM fcms_users AS u, fcms_address AS a 
                WHERE u.id = '$userid' 
                AND u.id = a.user";
        $this->db->query($sql) or displaySQLError(
            'Profile Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();

        // Rank Info
        $points = getUserParticipationPoints($userid);
        $level  = getUserParticipationLevel($points);
        $pts = 0;

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
        if (checkAccess($this->currentUserId) != 10) {

            $postsCount     = getPostsById($userid, 'array');
            $photosCount    = getPhotosById($userid, 'array');
            $commentsCount  = getCommentsById($userid, 'array');
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

        // Sex
        $sex = ($row['sex'] == 'F') ? 'female' : 'male';

        // Print the profile info
        echo '
            <div id="side-info" class="' . $sex . '">
                <img class="avatar" src="'.getCurrentAvatar($userid).'" alt="avatar"/>
                <div class="name">
                    <h3>'.$row['fname'].' '.$row['lname'].'</h3>
                    <h4>'.$row['username'].'</h4>
                    '.$level.'
                </div>
                <p>
                    <a class="action" href="privatemsg.php?compose=new&amp;id='.$userid.'">'.T_('Send PM').'</a>
                </p>
                <b>'.T_('Address').'</b>&nbsp;&nbsp;';

        if ($this->currentUserId == $userid) {
            echo '
                <form action="addressbook.php" method="post">
                    <div>
                        <input type="hidden" value="'.$row['aid'].'" name="id">
                        <input type="hidden" value="'.$userid.'" name="user">
                        <input type="submit" title="'.T_('Edit').'" class="editbtn" value="'.T_('Edit').'" name="edit">
                    </div>
                </form>';
        }

        echo '
                <br/>'.$address.'<br/>
                <p>
                    <b>'.T_('Home Phone').'</b>: '.$home.'<br/>
                    <b>'.T_('Work Phone').'</b>: '.$work.'<br/>
                    <b>'.T_('Cell Phone').'</b>: '.$cell.'<br/>
                </p>
                <hr/>
                <h4>'.T_('Participation Points').'</h4>
                <p>
                    <b>'.T_('Points').':</b> '.$points.'<br/>
                </p>
                <hr/>
                <p><b>'.T_('Join Date').':</b><br/>'.$joinDate.'</p>
                <p><b>'.T_('Last Visit').':</b><br/>'.$activityDate.'</p>
                <hr/>
                <h4>'.T_('Relationships').'</h4>';

        // Display Family Tree Listing
        $this->tree->displayFamilyTree($userid, 'list');

        echo '
                <p><a href="familytree.php?tree=' . $userid . '">' . T_('View Family Tree') . '</a></p>
            </div>
            <div id="main-info">
                <h2>'.T_('Stats').'</h2>
                <div id="stats" class="clearfix">
                    '.$posts.'
                    '.$photos.'
                    '.$comments.'
                    '.$calendars.'
                    '.$news.'
                    '.$recipes.'
                    '.$documents.'
                    '.$prayers.'
                </div>
                <h2>'.T_('Awards').'</h2>';

        $awards->displayAwards($userid);

        echo '
                <h2>'.T_('Last 5 Posts').'</h2>';
        $this->displayLast5Posts($userid);

        echo '
                <h2>'.T_('Last 5 Photos').'</h2>';
        $this->displayLast5Photos($userid);

        echo '
            </div>
            <div style="clear:both"></div>';
    }

    /**
     * displayPointsToGo 
     *
     * Shows how many more points are needed for the next rank.
     *
     * @deprecated
     * @param       string  $pts 
     * @return      void
     */
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

    /**
     * displayLast5Posts 
     * 
     * @param   int     $userid 
     * @return  void
     */
    function displayLast5Posts ($userid)
    {
        $userid = cleanInput($userid, 'int');

        $locale = new Locale();
        $sql = "SELECT t.`id`, `subject`, `date`, `post` 
                FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, `fcms_users` AS u 
                WHERE t.`id` = p.`thread` 
                AND p.`user` = u.`id` 
                AND u.`id` = '$userid' 
                ORDER BY `date` DESC 
                LIMIT 0, 5";
        $this->db2->query($sql) or displaySQLError(
            'Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db2->count_rows() > 0) {
            while ($row = $this->db2->get_row()) {
                $date = $locale->fixDate(T_('F j, Y, g:i a'), $this->tz_offset, $row['date']);
                $post = removeBBCode($row['post']);
                $subject = stripslashes($row['subject']);
                $pos = strpos($subject, '#ANOUNCE#');
                if ($pos !== false) {
                    $subject = substr($subject, 9, strlen($subject)-9);
                }
                echo '
                <p>
                    <a href="messageboard.php?thread='.(int)$row['id'].'">'.$subject.'</a> 
                    <span class="date">'.$date.'</span><br/>
                    '.$post.'
                </p>';
            }
        } else {
            echo '
                <p>'.T_('none').'</p>';
        }
    }

    /**
     * displayLast5Photos 
     * 
     * @param   int     $userid 
     * @return  void
     */
    function displayLast5Photos ($userid)
    {
        $userid = cleanInput($userid, 'int');

        $sql = "SELECT * 
                FROM `fcms_gallery_photos` 
                WHERE user = '$userid' 
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
                    <a href="gallery/index.php?uid='.$userid.'&amp;cid='.(int)$row['category'].'&amp;pid='.(int)$row['id'].'">
                        <img class="photo" src="gallery/photos/member'.(int)$row['user'].'/tb_'.basename($row['filename']).'" alt=""/>
                    </a>
                </li>';
            }
            echo '
            </ul>';
        } else {
            echo "<p>".T_('none')."</p>";
        }
    }

}
