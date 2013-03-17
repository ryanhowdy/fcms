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
    var $fcmsError;
    var $fcmsDatabase;
    var $fcmsUser;
    var $fcmsMessagBoard;
    var $fcmsPhotoGallery;

    /**
     * Awards 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase
     * @param object $fcmsUser 
     * @param object $fcmsMessageBoard
     * @param object $fcmsPhotoGallery
     * 
     * @return void
     */
    function Awards ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsMessageBoard = null, $fcmsPhotoGallery = null)
    {
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;
        $this->fcmsMessageBoard = $fcmsMessageBoard;
        $this->fcmsPhotoGallery = $fcmsPhotoGallery;
    }

    /**
     * displayAwards 
     * 
     * @param int $userid 
     *
     * @return  void
     */
    function displayAwards ($userid)
    {
        $userid  = (int)$userid;

        $sql = "SELECT `award`
                FROM `fcms_user_awards`
                WHERE `user` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, $userid);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        if (count($rows) < 0)
        {
            echo '
                <p>'.T_('none').'</p>';

            return;
        }

        $awardInfo = $this->getAwardsInfoList();

        $awardList = array();

        foreach ($rows as $r)
        {
            $awardList[$r['award']][] = $r;
        }

        echo '
                <ul id="awards-list">';

        if (count($awardList) <= 0)
        {
            echo '
                    <li style="width:auto;">'.T_('This user has no awards yet.').'</li>';
            return;
        }

        foreach ($awardList as $type => $awards)
        {
            $count = count($awards);
            $name  = $awardInfo[$awards[0]['award']]['name'];
            $span  = '';

            if ($count > 1)
            {
                $span = '<span>&times; '.$count.'</span>';
            }

            echo '
                    <li>
                        <a href="?member='.$userid.'&amp;award='.$awards[0]['award'].'" class="'.$awards[0]['award'].'" title="'.$name.'"></a>'.$span.'
                    </li>';
        }

        echo '
                </ul>';
    }

    /**
     * displayAward 
     * 
     * Displays details about the given award type.
     * Along with who the award was awarded to and any other awards they own.
     * 
     * @param int $userid 
     * @param int $type
     * 
     * @return void
     */
    function displayAward ($userid, $type)
    {
        $userid = (int)$userid;

        $sql = "SELECT a.`id`, a.`user`, a.`award`, a.`month`, a.`date`, a.`item_id`, a.`count`, u.`fname`
                FROM `fcms_user_awards` AS a,
                    `fcms_users` AS u
                WHERE a.`user` = '$userid'
                AND a.`award` = '$type'
                AND a.`user` = u.`id`";

        $rows = $this->fcmsDatabase->getRows($sql, array($userid, $type));
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($rows) <= 0)
        {
            echo '
            <p class="error-alert">'.T_('Invalid Member/Award.').'</p>';

            return;
        }

        $awardList = array();

        foreach ($rows as $r)
        {
            $awardList[] = $r;
            $fname       = $r['fname'];
        }

        $currentAward = array(
            'id'        => $awardList[0]['id'],
            'award'     => $awardList[0]['award'],
            'month'     => $awardList[0]['month'],
            'date'      => $awardList[0]['date'],
            'item_id'   => $awardList[0]['item_id'],
            'count'     => $awardList[0]['count'],
        );

        $awardsInfo = $this->getAwardsInfoList();

        $totalTimesAwarded = count($awardList);

        $string       = T_ngettext('%s has been given this award %d time.', '%s has been given this award %d times.', $totalTimesAwarded);
        $awardedCount = sprintf($string, $fname, $totalTimesAwarded).'</h5>';

        if ($userid == $this->currentUserId)
        {
            $string       = T_ngettext('You have been given this award %d time.', 'You have been given this award %d times.', $totalTimesAwarded);
            $awardedCount = sprintf($string, $totalTimesAwarded).'</h5>';
        }

        echo '
            <div id="current-award">
                <div class="'.$currentAward['award'].'"></div>
                <h1>'.$awardsInfo[$currentAward['award']]['name'].'</h1>
                <h2>'.$awardsInfo[$currentAward['award']]['description'].'</h2>
            </div>

            <h5 class="times-awarded">'.$awardedCount.'</h5>';

        foreach ($awardList as $r)
        {
            $details = '';
            $date    = '';

            if (strlen($r['month']) == 6)
            {
                $year  = substr($r['month'], 0, 4);
                $month = substr($r['month'], 4, 2);
                $date  = date('F, Y', strtotime("$year-$month-01"));
            }

            switch ($r['award'])
            {
                case 'board':

                    $details = sprintf(T_pgettext('Ex: December, 2011 - 10 posts', '%s - %s posts'), $date, $r['count']);
                    break;

                case 'gallery':

                    $details = sprintf(T_pgettext('Ex: December, 2011 - 10 photos', '%s - %s photos'), $date, $r['count']);
                    break;

                case 'recipes':

                    $details = sprintf(T_pgettext('Ex: December, 2011 - 10 recipes', '%s - %s recipes'), $date, $r['count']);
                    break;

                case 'news':

                    $details = sprintf(T_pgettext('Ex: December, 2011 - 10 posts', '%s - %s posts'), $date, $r['count']);
                    break;

                case 'docs':

                    $details = sprintf(T_pgettext('Ex: December, 2011 - 10 documents', '%s - %s documents'), $date, $r['count']);
                    break;

                case 'icebreaker':

                    $thread  = (int)$r['item_id'];
                    $replies = sprintf(T_pgettext('Ex: 21 replies', '%d replies'), $r['count']);

                    $details = $date.' - <a href="messageboard.php?thread='.$thread.'">'.$this->fcmsMessageBoard->getThreadSubject($thread).'</a> - '.$replies;
                    break;

                case 'shutterbug':

                    $id       = (int)$r['item_id'];
                    $photo    = $this->fcmsPhotoGallery->getPhotoInfo($id);
                    $views    = sprintf(T_pgettext('Ex: 210 views', '%d views'), $r['count']);
                    $photoSrc = $this->fcmsPhotoGallery->getPhotoSource($photo);

                    $details  = $date.' - '.$views.'<br/>';
                    $details .= '<a href="gallery/index.php?uid='.$photo['user'].'&amp;cid='.$photo['category'].'&amp;pid='.$photo['id'].'">';
                    $details .= '<img src="'.$photoSrc.'"/>';
                    $details .= '</a>';
                    break;

                case 'interesting':

                    $id    = (int)$r['item_id'];
                    $views = sprintf(T_pgettext('Ex: 21 comments', '%d comments'), $r['count']);

                    $sql = "SELECT `title`
                            FROM `fcms_news`
                            WHERE `id` = '$id'";

                    $news = $this->fcmsDatabase->getRow($sql, $id);
                    if ($news === false)
                    {
                        $this->fcmsError->displayError();
                        return;
                    }

                    $title = cleanOutput($news['title']);

                    $details  = $date.' - <a href="familynews.php?getnews='.$r['user'].'&amp;newsid='.$id.'">'.$title.'</a> - '.$views;
                    break;

                case 'secretive':

                    $views = sprintf(T_pgettext('Ex: 210 private messages', '%d private messages'), $r['count']);

                    $details  = $date.' - '.$views.'<br/>';
                    break;

                case 'planner':

                    $views = sprintf(T_pgettext('Ex: 53 events', '%d events'), $r['count']);

                    $details  = $date.' - '.$views.'<br/>';
                    break;

                case 'photogenic':

                    $views = sprintf(T_pgettext('Ex: 53 photos', '%d photos'), $r['count']);

                    $details  = $date.' - '.$views.'<br/>';
                    break;
            }

            echo '
                <p>'.$details.'</p>';
        }
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
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            return false;
        }

        if ($this->db->count_rows() > 0) {
            return true;
        }

        $params = array(
            'month' => $lastMonth,
            'start' => $lastMonthStart,
            'end'   => $lastMonthEnd,
        );

        // Message Board
        $params['award'] = 'board';
        $params['table'] = 'fcms_board_posts';
        if (!$this->calculateAward($params))
        {
            return false;
        }

        // Photo Gallery
        $params['award'] = 'gallery';
        $params['table'] = 'fcms_gallery_photos';
        if (!$this->calculateAward($params))
        {
            return false;
        }

        // Family News
        $params['award'] = 'news';
        $params['table'] = 'fcms_news';
        if (!$this->calculateAward($params))
        {
            return false;
        }

        // Recipes
        $params['award'] = 'recipes';
        $params['table'] = 'fcms_recipes';
        if (!$this->calculateAward($params))
        {
            return false;
        }

        // Documents
        $params['award'] = 'documents';
        $params['table'] = 'fcms_documents';
        if (!$this->calculateAward($params))
        {
            return false;
        }

        return true;
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
            $this->fcmsError->displayError();
            return false;
        }

        if ($this->db->count_rows() > 0)
        {
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
            if (!$this->db->query($sql))
            {
                $this->fcmsError->displayError();
                return false;
            }
        }

        return true;
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
        $currentAwards = $this->getCurrentAchievementAwards();

        if ($currentAwards === false)
        {
            return false;
        }

        // Ice Breaker
        if (!$this->calculateIceBreakerAward($currentAwards)) {
            return false;
        }
        // Shutterbug
        if (!$this->calculateShutterbugAward($currentAwards)) {
            return false;
        }
        // Interesting
        if (!$this->calculateInterestingAward($currentAwards)) {
            return false;
        }
        // Secretive
        if (!$this->calculateSecretiveAward($currentAwards)) {
            return false;
        }
        // Planner
        if (!$this->calculatePlannerAward($currentAwards)) {
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
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            return false;
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
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            return false;
        }

        if ($this->db->count_rows() > 0)
        {
            while ($r = $this->db->get_row())
            {
                $month = date('Ym', strtotime($r['date']));

                // Skip already awarded awards
                if (isset($currentAwards['icebreaker_'.$r['started_by'].'_'.$month.'_'.$r['thread']]))
                {
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
                if (!$this->db2->query($sql))
                {
                    $this->fcmsError->displayError();
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
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
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
                if (!$this->db2->query($sql))
                {
                    $this->fcmsError->displayError();
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
            $this->fcmsError->displayError();
            return false;
        }

        if ($this->db->count_rows() > 0)
        {
            while ($r = $this->db->get_row())
            {
                $month = date('Ym', strtotime($r['date']));

                // Skip already awarded awards
                if (isset($currentAwards['interesting_'.$r['user'].'_'.$month.'_'.$r['news']]))
                {
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
                if (!$this->db2->query($sql))
                {
                    $this->fcmsError->displayError();
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
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            return false;
        }

        if ($this->db->count_rows() > 0)
        {
            while ($r = $this->db->get_row())
            {
                // Skip already awarded awards
                if (isset($currentAwards['secretive_'.$r['user']]))
                {
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
                if (!$this->db2->query($sql))
                {
                    $this->fcmsError->displayError();
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
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            return false;
        }

        if ($this->db->count_rows() > 0)
        {
            while ($r = $this->db->get_row())
            {
                // Skip already awarded awards
                if (isset($currentAwards['planner_'.$r['created_by']]))
                {
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
                if (!$this->db2->query($sql))
                {
                    $this->fcmsError->displayError();
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
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
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
                if (!$this->db2->query($sql))
                {
                    $this->fcmsError->displayError();
                    return false;
                }
            }
        }

        return true;
    }

}
