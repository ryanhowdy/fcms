<?php
require_once 'utils.php';
require_once 'messageboard_class.php';
require_once 'gallery_class.php';

/**
 * Awards 
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2010 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
class Awards
{

    var $db;
    var $db2;
    var $currentUserId;

    /**
     * Awards 
     * 
     * @param int $id
     * 
     * @return void
     */
    function Awards ($id)
    {
        global $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass;

        $this->db  = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $this->db2 = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($id, 'int');
    }

    /**
     * displayAwards 
     * 
     * @param int $userid 
     *
     * @return  void
     */
    function displayAwards ($userid, $exclude = 0)
    {
        $userid  = cleanInput($userid, 'int');
        $exlcude = cleanInput($exclude, 'int');

        $sql = "SELECT `id`, `user`, `award`, `month`, `date`, `item_id`, `count`
                FROM `fcms_user_awards`
                WHERE `user` = '$userid'";
        $this->db->query($sql) or displaySQLError(
            'Awards Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        if ($this->db->count_rows() < 0) {
            echo '
                <p>'.T_('none').'</p>';
            return;
        }

        echo '
                <ul id="awards-list" class="clearfix">';

        $awardInfo = $this->getAwardsInfoList();

        while ($r = $this->db->get_row()) {

            if ($r['id'] == $exclude) {
                continue;
            }

            $date = '';
            if (strlen($r['month']) == 6) {
                $year  = substr($r['month'], 0, 4);
                $month = substr($r['month'], 4, 2);
                $date  = date('F Y', strtotime("$year-$month-01"));
            }

            echo '
                    <li>
                        <a href="?member='.$userid.'&amp;award='.$r['id'].'" class="'.$r['award'].'" title="'.$awardInfo[$r['award']]['name'].': '.$date.'"></a>
                    </li>';
        }

        echo '
                </ul>';
    }

    /**
     * displayAward 
     * 
     * Displays details about the given award 
     * along with who the award was awarded to and any other awards they own.
     * 
     * @param int $userid 
     * @param int $id 
     * 
     * @return void
     */
    function displayAward ($userid, $id)
    {
        global $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass;

        $mb       = new MessageBoard($this->currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $g        = new PhotoGallery($this->currentUserId, $database);

        $userid = cleanInput($userid, 'int');
        $id     = cleanInput($id, 'int');

        $sql = "SELECT `id`, `user`, `award`, `month`, `date`, `item_id`, `count`
                FROM `fcms_user_awards`
                WHERE `user` = '$userid'
                AND `id` = '$id'";
        if (!$this->db->query($sql)) {
            displaySQLError('Awards Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0) {
            echo '
            <p class="error-alert">'.T_('Invalid Member/Award.').'</p>';
            return;
        }

        $r = $this->db->get_row();
        $currentAward = array(
            'id'        => $r['id'],
            'award'     => $r['award'],
            'month'     => $r['month'],
            'date'      => $r['date'],
            'item_id'   => $r['item_id'],
            'count'     => $r['count'],
        );

        $awardsInfo = $this->getAwardsInfoList();

        $details = '';
        if ($currentAward['award'] == 'icebreaker') {
            $thread = $currentAward['item_id'];
            $details = '<h3><a href="messageboard.php?thread='.$thread.'">'.$mb->getThreadSubject($thread).'</a></h3>';
        }
        if ($currentAward['award'] == 'shutterbug') {
            $id = $currentAward['item_id'];
            $photo = $g->getPhotoInfo($id);
            $details = '<h3>
                    <a href="gallery/index.php?uid='.$photo['user'].'&amp;cid='.$photo['category'].'&amp;pid='.$photo['id'].'">
                        <img src="gallery/photos/member'.$photo['user'].'/tb_'.basename($photo['filename']).'"/>
                    </a>
                </h3>';
        }

        echo '
            <div id="current-award">
                <div class="'.$currentAward['award'].'"></div>
                <h1>'.$awardsInfo[$currentAward['award']]['name'].'</h1>
                <h2>'.$awardsInfo[$currentAward['award']]['description'].'</h2>
                '.$details.'
                <h4>'.sprintf(T_('Awarded on %s.'), $currentAward['date']).'</h4>';

        $this->displayAwards($userid, $id);

        echo '
            </div>';

    }

    /**
     * getAwardsInfoList 
     *
     * Returns a list of awards with translated names and other info
     * 
     * @return array
     */
    function getAwardsInfoList ()
    {
        return array(
            'board'         => array(
                'name'          => T_('Message Board'),
                'description'   => T_('Adding the most Message Board posts for the month.'),
            ),
            'gallery'       => array(
                'name'          => T_('Photo Gallery'),
                'description'   => T_('Uploading the most photos for the month.'),
            ),
            'recipes'       => array(
                'name'          => T_('Recipes'),
                'description'   => T_('Adding the most recipes for the month.'),
            ),
            'news'          => array(
                'name'          => T_('Family News'),
                'description'   => T_('Adding the most Family News for the month.'),
            ),
            'docs'          => array(
                'name'          => T_('Documents'),
                'description'   => T_('Sharing the most documents for the month.'),
            ),
            'icebreaker'    => array(
                'name'          => T_('Ice Breaker'),
                'description'   => T_('Starting a Message Board post with over 20 replies.'),
            ),
            'shutterbug'    => array(
                'name'          => T_('Shutterbug'),
                'description'   => T_('Uploading a photo with over 100 views.'),
            ),
            'interesting'   => array(
                'name'          => T_('Interesting'),
                'description'   => T_('Adding Family News with over 20 comments.'),
            ),
            'secretive'     => array(
                'name'          => T_('Secretive'),
                'description'   => T_('Sending over 100 Private Messages (PM).'),
            ),
            'planner'       => array(
                'name'          => T_('Planner'),
                'description'   => T_('Adding over 50 events to the calendar.'),
            ),
            'photogenic'    => array(
                'name'          => T_('Photogenic'),
                'description'   => T_('Being tagged in over 50 photos.'),
            ),
        );
    }

    /**
     * calculateMonthlyAwards 
     * 
     * Awards are calculated each month, with 1 winner for each of the following section:
     *      Message Board
     *      Photo Gallery
     *      Family News
     *      Recipes
     *      Documents
     * 
     * @return  void
     */
    function calculateMonthlyAwards ()
    {
        $lastMonth      = date('Ym', mktime(0, 0, 0, date('m')-1, 1, date('Y')));
        $lastMonthStart = date('Y-m', mktime(0, 0, 0, date('m')-1, 1, date('Y'))) . "-01 00:00:00";
        $lastMonthEnd   = date('Y-m', mktime(0, 0, 0, date('m')-1, 1, date('Y'))) . "-31 23:59:59";

        // Have last months awards been calculated already?
        $sql = "SELECT `id`, `month`
                FROM `fcms_user_awards`
                WHERE `month` = '$lastMonth'";
        $this->db->query($sql) or displaySQLError(
            'Check Awards Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            return;
        }

        $params = array(
            'month' => $lastMonth,
            'start' => $lastMonthStart,
            'end'   => $lastMonthEnd,
        );

        // Message Board
        $params['award'] = 'board';
        $params['table'] = 'fcms_board_posts';
        $this->calculateAward($params);

        // Photo Gallery
        $params['award'] = 'gallery';
        $params['table'] = 'fcms_gallery_photos';
        $this->calculateAward($params);

        // Family News
        $params['award'] = 'news';
        $params['table'] = 'fcms_news';
        $this->calculateAward($params);

        // Recipes
        $params['award'] = 'recipes';
        $params['table'] = 'fcms_recipes';
        $this->calculateAward($params);

        // Documents
        $params['award'] = 'documents';
        $params['table'] = 'fcms_documents';
        $this->calculateAward($params);
    }

    /**
     * calculateAward 
     * 
     * Takes an array with the following params:
     *
     *      award - name of the award (gallery)
     *      table - name of table (fcms_gallery_photos)
     *      month - last month (YYYYMM)
     *      start - datetime of beginning of last month
     *      end   - datetime of end of last month
     *
     * @param array $params
     *
     * @return void
     */
    function calculateAward ($params)
    {
        $date = 'date';

        if ($params['table'] == 'fcms_news')
        {
            $date = 'created';
        }

        $sql = "SELECT `user`, COUNT(`id`) AS c
                FROM `".$params['table']."` 
                WHERE `$date` >= '".$params['start']."' 
                  AND `$date` <= '".$params['end']."' 
                GROUP BY `user` 
                ORDER BY c DESC 
                LIMIT 1";
        if (!$this->db->query($sql))
        {
            displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            die();
        }

        if ($this->db->count_rows() > 0) {
            $r = $this->db->get_row();
            $sql = "INSERT INTO `fcms_user_awards`
                        (`user`, `award`, `month`, `date`, `count`)
                    VALUES (
                        '".$r['user']."',
                        '".$params['award']."',
                        '".$params['month']."',
                        NOW(),
                        '".$r['c']."'
                    )";
            $this->db->query($sql) or displaySQLError(
                'Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
    }

    /**
     * calculateAchievementAwards 
     * 
     * Calculate the awards given out based on an achievement 
     * as opposed to given out every month.
     *
     *  Photo Gallery:
     *      Shutterbug   uploading photo with over 100 views
     *      Photogenic   tagged in over 50 photos
     *
     *  Message Board:
     *      Ice Breaker  starting thread with over 20 replies
     *      Boring       starting over 20 threads with no replies
     *
     *  Family News:
     *      Interesting  submiting family news with over 20 comments
     *
     *  Calendar:
     *      Planner      adding over 50 events to the calendar
     *
     *  Misc:
     *      Secretive    sending over 100 private messages
     *
     * @return boolean
     */
    function calculateAchievementAwards ()
    {
        $errors = 0;

        $currentAwards = $this->getCurrentAchievementAwards();

        // Ice Breaker
        if (!$this->calculateIceBreakerAward($currentAwards)) {
            $errors++;
        }
        // Shutterbug
        if (!$this->calculateShutterbugAward($currentAwards)) {
            $errors++;
        }
        // Interesting
        if (!$this->calculateInterestingAward($currentAwards)) {
            $errors++;
        }
        // Secretive
        if (!$this->calculateSecretiveAward($currentAwards)) {
            $errors++;
        }
        // Planner
        if (!$this->calculatePlannerAward($currentAwards)) {
            $errors++;
        }

        if ($errors > 0) {
            return false;
        }

        return true;
    }

    /**
     * getCurrentAchievementAwards 
     *
     * Used by the calculate achievement awards functions so we don't award
     * the same award to the same person more than once.
     * 
     * @return array
     */
    function getCurrentAchievementAwards ()
    {
        $array = array();

        $sql = "SELECT `user`, `award`, `month`, `item_id`
                FROM `fcms_user_awards`
                WHERE `award` IN ('icebreaker', 'shutterbug', 'interesting', 'secretive', 'planner', 'boring', 'photogenic')";
        if (!$this->db->query($sql)) {
            displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return $array;
        }

        while ($r = $this->db->get_row()) {

            // Secretive or Planner
            if ($r['award'] == 'secretive' || $r['award'] == 'planner' || $r['award'] == 'photogenic') {
                $array[ $r['award'].'_'.$r['user'] ] = 1;
                continue;
            }

            $array[ $r['award'].'_'.$r['user'].'_'.$r['month'].'_'.$r['item_id'] ] = 1;
        }

        return $array;
    }

    /**
     * calculateIceBreakerAward 
     * 
     * Awarded when a user has started a thread with over 20 replies
     * 
     * @param array $currentAwards
     * 
     * @return boolean
     */
    function calculateIceBreakerAward ($currentAwards)
    {
        // We check for 21 because the thread is counted as a reply
        $sql = "SELECT t.`started_by`, p.`date`, p.`thread`, COUNT(p.`id`) AS ct
                FROM `fcms_board_posts` AS p
                JOIN `fcms_board_threads` AS t ON p.`thread` = t.`id`
                GROUP BY `thread`
                HAVING ct >= 21";
        if (!$this->db->query($sql)) {
            displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }

        if ($this->db->count_rows() > 0) {
            while ($r = $this->db->get_row()) {

                $month = date('Ym', strtotime($r['date']));

                // Skip already awarded awards
                if (isset($currentAwards['icebreaker_'.$r['started_by'].'_'.$month.'_'.$r['thread']])) {
                    continue;
                }

                $sql = "INSERT INTO `fcms_user_awards`
                            (`user`, `award`, `month`, `date`, `item_id`, `count`)
                        VALUES (
                            '".$r['started_by']."',
                            'icebreaker',
                            '$month',
                            NOW(),
                            '".$r['thread']."',
                            '".$r['ct']."'
                        )";
                if (!$this->db2->query($sql)) {
                    displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * calculateShutterbugAward 
     * 
     * Awarded when a user has uploaded a photo with over 100 views
     * 
     * @param array $currentAwards
     * 
     * @return boolean
     */
    function calculateShutterbugAward ($currentAwards)
    {
        $sql = "SELECT `id`, `user`, `date`, `views`
                FROM `fcms_gallery_photos`
                WHERE `views` >= 100";
        if (!$this->db->query($sql)) {
            displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }

        if ($this->db->count_rows() > 0) {
            while ($r = $this->db->get_row()) {

                $month = date('Ym', strtotime($r['date']));

                // Skip already awarded awards
                if (isset($currentAwards['shutterbug_'.$r['user'].'_'.$month.'_'.$r['id']])) {
                    continue;
                }

                $sql = "INSERT INTO `fcms_user_awards`
                            (`user`, `award`, `month`, `date`, `item_id`, `count`)
                        VALUES (
                            '".$r['user']."',
                            'shutterbug',
                            '$month',
                            NOW(),
                            '".$r['id']."',
                            '".$r['views']."'
                        )";
                if (!$this->db2->query($sql)) {
                    displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * calculateInterestingAward 
     * 
     * Awarded when a user submits family news with over 20 comments
     * 
     * @param array $currentAwards
     * 
     * @return boolean
     */
    function calculateInterestingAward ($currentAwards)
    {
        $sql = "SELECT n.`user`, n.`created`, c.`news`, COUNT(c.`id`) AS ct
                FROM `fcms_news_comments` AS c
                JOIN `fcms_news` AS n ON c.`news` = n.`id`
                GROUP BY c.`news`
                HAVING ct >= 20";
        if (!$this->db->query($sql))
        {
            displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }

        if ($this->db->count_rows() > 0) {
            while ($r = $this->db->get_row()) {

                $month = date('Ym', strtotime($r['date']));

                // Skip already awarded awards
                if (isset($currentAwards['interesting_'.$r['user'].'_'.$month.'_'.$r['news']])) {
                    continue;
                }

                $sql = "INSERT INTO `fcms_user_awards`
                            (`user`, `award`, `month`, `date`, `item_id`, `count`)
                        VALUES (
                            '".$r['user']."',
                            'interesting',
                            '$month',
                            NOW(),
                            '".$r['news']."',
                            '".$r['ct']."'
                        )";
                if (!$this->db2->query($sql)) {
                    displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * calculateSecretiveAward 
     * 
     * Awarded when a user sends over 100 private messages
     * 
     * @param array $currentAwards
     * 
     * @return boolean
     */
    function calculateSecretiveAward ($currentAwards)
    {
        $sql = "SELECT `from`, `date`, COUNT(`id`) AS ct
                FROM `fcms_privatemsg`
                GROUP BY `from`
                HAVING ct >= 100";
        if (!$this->db->query($sql)) {
            displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }

        if ($this->db->count_rows() > 0) {
            while ($r = $this->db->get_row()) {

                // Skip already awarded awards
                if (isset($currentAwards['secretive_'.$r['user']])) {
                    continue;
                }

                $month = date('Ym', strtotime($r['date']));
                $sql = "INSERT INTO `fcms_user_awards`
                            (`user`, `award`, `month`, `date`, `count`)
                        VALUES (
                            '".$r['from']."',
                            'secretive',
                            '$month',
                            NOW(),
                            '".$r['ct']."'
                        )";
                if (!$this->db2->query($sql)) {
                    displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * calculatePlannerAward 
     * 
     * Awarded when a user adds over 50 events to the calendar
     * 
     * @param array $currentAwards
     * 
     * @return boolean
     */
    function calculatePlannerAward ($currentAwards)
    {
        $sql = "SELECT `created_by`, `date_added`, COUNT(`id`) AS ct
                FROM `fcms_calendar`
                GROUP BY `created_by`
                HAVING ct >= 50";
        if (!$this->db->query($sql)) {
            displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }

        if ($this->db->count_rows() > 0) {
            while ($r = $this->db->get_row()) {

                // Skip already awarded awards
                if (isset($currentAwards['planner_'.$r['created_by']])) {
                    continue;
                }

                $month = date('Ym', strtotime($r['date_added']));
                $sql = "INSERT INTO `fcms_user_awards`
                            (`user`, `award`, `month`, `date`, `count`)
                        VALUES (
                            '".$r['created_by']."',
                            'planner',
                            '$month',
                            NOW(),
                            '".$r['ct']."'
                        )";
                if (!$this->db2->query($sql)) {
                    displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * calculatePhotogenicAward 
     * 
     * Awarded when a user is tagged in over 50 photos
     * 
     * @param array $currentAwards
     * 
     * @return boolean
     */
    function calculatePhotogenicAward ($currentAwards)
    {
        $sql = "SELECT `user`, COUNT(`id`) AS ct
                FROM `fcms_gallery_photos_tags`
                GROUP BY `user`
                HAVING ct >= 50";
        if (!$this->db->query($sql)) {
            displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }

        if ($this->db->count_rows() > 0) {
            while ($r = $this->db->get_row()) {

                // Skip already awarded awards
                if (isset($currentAwards['photogenic_'.$r['user']])) {
                    continue;
                }

                $sql = "INSERT INTO `fcms_user_awards`
                            (`user`, `award`, `month`, `date`, `count`)
                        VALUES (
                            '".$r['user']."',
                            'photogenic',
                            '0',
                            NOW(),
                            '".$r['ct']."'
                        )";
                if (!$this->db2->query($sql)) {
                    displaySQLError('Award Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
                    return false;
                }
            }
        }

        return true;
    }

}
