<?php
include_once('database_class.php');
include_once('utils.php');
include_once('datetime.php');

/**
 * Poll 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Poll
{
    var $db;
    var $db2;
    var $tzOffset;
    var $currentUserId;

    /**
     * Poll 
     * 
     * @param   int     $currentUserId 
     *
     * @return  void
     */
    function Poll ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db  = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $this->db2 = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->tzOffset      = getTimezone($this->currentUserId);
    }

    /**
     * placeVote 
     * 
     * @param   int     $userid 
     * @param   int     $optionid 
     * @param   int     $pollid 
     * @return  void
     */
    function placeVote ($userid, $optionid, $pollid)
    {
        $userid   = cleanInput($userid, 'int');
        $optionid = cleanInput($optionid, 'int');
        $pollid   = cleanInput($pollid, 'int');

        $sql = "SELECT `user` 
                FROM `fcms_poll_votes` 
                WHERE `user` = '$userid'
                AND `poll_id` = '$pollid'";
        $this->db->query($sql) or displaySQLError(
            'User Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo "<p class=\"info-alert\">".T_('You have already voted.')."</p>\n\t\t";
        } else {
            $sql = "UPDATE `fcms_poll_options` 
                    SET `votes` = `votes`+1 
                    WHERE `id` = '$optionid'";
            $this->db->query($sql) or displaySQLError(
                '+Vote Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $sql = "INSERT INTO `fcms_poll_votes`(`user`, `option`, `poll_id`) 
                    VALUES ('$userid', '$optionid', '$pollid')";
            $this->db->query($sql) or displaySQLError(
                'Vote Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
    }

    /**
     * displayResults 
     * 
     * @param   int     $pollid 
     * @return  void
     */
    function displayResults ($pollid)
    {
        $pollid   = cleanInput($pollid, 'int');

        $sql = "SELECT result.`total`, `question`, o.`id`, `option`, `votes` 
                FROM `fcms_polls` AS p, `fcms_poll_options` AS o, (
                    SELECT SUM(`votes`) AS total, fcms_polls.id 
                    FROM `fcms_polls`, `fcms_poll_options` 
                    WHERE `fcms_poll_options`.`poll_id` = `fcms_polls`.`id` 
                    GROUP BY `id`
                ) AS result 
                WHERE p.`id` = o.`poll_id` 
                AND p.`id` = '$pollid' 
                AND result.`id` = p.`id`";
        $this->db->query($sql) or displaySQLError(
            'Result Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $i = 0;
        while ($row = $this->db->get_row()) {
            $votes = $row['votes'];
            $total = $row['total'];
            if ($total < 1) {
                $percent = 0;
            } else {
                $percent = $votes/$total;
            }
            $width = round((140 * $percent) + 10, 0);
            if ($total < 1) {
                $percent = 0;
            } else {
                $percent = round((($votes/$total) * 100), 0);
            }
            if ($i < 1) {
                echo '
            <h3>'.$row['question'].'</h3>
            <ul class="poll">';
            }
            echo '
                <li>
                    <div><b>'.$row['option'].'</b>'.$percent.'%<br/>'.$votes.' votes</div>
                    <span class="index" style="width:'.$percent.'%">'.$percent.'%</span>
                </li>';
            $i++;
        }
        echo '
            </ul>
            <p class="poll">'.T_('Total Votes').': '.$total.'</p>
            <p><b>'.T_('Who Voted for What?').'</b></p>';

        $sql = "SELECT v.`user`, u.`avatar`, o.`option`
                FROM `fcms_poll_votes` AS v, `fcms_poll_options` AS o, `fcms_users` AS u
                WHERE v.`poll_id` = '$pollid' 
                AND o.`id` = v.`option` 
                AND v.`user` = u.`id` 
                ORDER BY o.`option`";
        $this->db->query($sql) or displaySQLError(
            'Who Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            $option = '';
            while ($r = $this->db->get_row()) {
                if ($option == '') {
                    echo '
            <h4>'.$r['option'].'</h4>
            <ul class="whovoted">
                <li>
                    <img alt="avatar" src="'.getCurrentAvatar($r['user']).'"/> '.getUserDisplayName($r['user']). '
                </li>';
                } elseif ($r['option'] != $option) {
                    echo '
            </ul>
            <h4>'.$r['option'].'</h4>
            <ul class="whovoted">
                <li>
                    <img alt="avatar" src="'.getCurrentAvatar($r['user']).'"/> '.getUserDisplayName($r['user']). '
                </li>';
                } else {
                    echo '
                <li>
                    <img alt="avatar" src="'.getCurrentAvatar($r['user']).'"/> '.getUserDisplayName($r['user']). '
                </li>';
                }
                $option = $r['option'];
            }
            echo '
            </ul>
            <p><a href="?action=pastpolls">'.T_('Past Polls').'</a></p>';
        } else {
            if ($total > 0 ) {
                echo '
            <p class="info-alert">'.T_('No user data could be found.').'</p>';
            } else {
                echo '
            <p class="info-alert">'.T_('No one has voted on this poll yet.').'</p>';
            }
        }
    }

    /**
     * displayPoll 
     * 
     * @param   int     $pollid 
     * @param   boolean $showResults 
     * @return  void
     */
    function displayPoll ($pollid = 0, $showResults = true)
    {
        $poll_exists = true;

        // Get Latest Poll ID
        if ($pollid == 0) {
            $sql = "SELECT MAX(`id`) AS max FROM `fcms_polls`";
            $this->db->query($sql) or displaySQLError(
                'Max Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $r = $this->db->get_row();
            $pollid = $r['max'];
            if (!is_null($r['max'])) { 
                $pollid = $r['max'];
            } else {
                $poll_exists = false;
            }
        }

        // Get Poll Data
        if ($poll_exists) {
            $sql = "SELECT p.`id`, `question`, o.`id` AS option_id, `option` 
                    FROM `fcms_polls` AS p, `fcms_poll_options` AS o 
                    WHERE p.`id` = '" . cleanInput($pollid, 'int') . "' 
                    AND p.`id` = o.`poll_id`";
            $this->db->query($sql) or displaySQLError(
                'Poll Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            if ($this->db->count_rows() <= 0) {
                $poll_exists = false;
            }
        }

        // We are using Polls
        if ($poll_exists) {
            $sql = "SELECT * 
                    FROM `fcms_poll_votes` 
                    WHERE `poll_id` = '" . cleanInput($pollid, 'int') . "'
                    AND `user` = ".$this->currentUserId;
            $this->db2->query($sql) or displaySQLError(
                'Voted Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );

            // User has already voted
            if ($this->db2->count_rows() > 0 && $showResults) {
                $this->displayResults($pollid);

            // User hasn't voted yet
            } else {
                echo '
            <h2 class="pollmenu">'.T_('Polls').'</h2>
            <form method="post" action="home.php">';
                $i = 0;
                $id = '';
                while ($row = $this->db->get_row()) {
                    $id = $row['id'];
                    if ($i < 1) {
                        echo '
                <h3>'.$row['question'].'</h3>';
                    }
                    echo '
                <p><input type="radio" name="option_id" value="'.(int)$row['option_id'].'"/><span>'.cleanOutput($row['option']).'</span></p>';
                    $i++;
                }
                echo '
                <p>
                    <input type="hidden" name="poll_id" value="'.$id.'"/>
                    <input type="submit" value="'.T_('Vote').'" name="vote"/> &nbsp;
                </p>
                <p>
                    <a href="?action=results&amp;poll_id='.$id.'">'.T_('View Results').'</a><br/>
                    <a href="?action=pastpolls">'.T_('Past Polls').'</a>
                </p>
            </form>';
            }
        }    
    }

    /**
     * displayPastPolls 
     * 
     * @param  int  $page 
     * @return void
     */
    function displayPastPolls ($page)
    {
        $from = (($page * 15) - 15);
        echo '
            <h2>'.T_('Past Polls').'</h3>';
        $sql = "SELECT * 
                FROM `fcms_polls` 
                ORDER BY started DESC 
                LIMIT $from, 15";
        $this->db->query($sql) or displaySQLError(
            'Polls Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo '
            <table class="sortable">
                <thead>
                    <tr>
                        <th>'.T_('Question').'</th>
                        <th>'.T_('Date').'</th>
                        <th>'.T_('Total Votes').'</th>
                    </tr>
                </thead>
                <tbody>';
            while ($row = $this->db->get_row()) {
                $date = fixDate(T_('M. j, Y, g:i a'), $this->tzOffset, $row['started']);
                echo '
                    <tr>
                        <td><a href="?poll_id='.(int)$row['id'].'">'.cleanOutput($row['question']).'</a></td>
                        <td>'.$date.'</td></td>
                        <td>'.$this->getTotalVotes($row['id']).'</td>
                    </tr>';
            }
            echo '
                </tbody>
            </table>';

            // Remove the LIMIT from the $sql statement 
            // used above, so we can get the total count
            $sql = substr($sql, 0, strpos($sql, 'LIMIT'));
            $this->db->query($sql) or displaySQLError(
                'Page Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $count = $this->db->count_rows();
            $total_pages = ceil($count / 15); 
            displayPages("home.php?action=pastpolls", $page, $total_pages);
        } else {
            echo "<i>".T_('No previous polls')."</i>";
        }
    }

    /**
     * getTotalVotes 
     * 
     * @param  int  $pollid 
     * @return void
     */
    function getTotalVotes ($pollid)
    {
        $sql = "SELECT SUM(`votes`) AS total 
                FROM `fcms_polls` AS p, `fcms_poll_options` AS o
                WHERE p.`id` = '" . cleanInput($pollid, 'int') . "' 
                AND p.`id` = o.`poll_id`";
        $this->db2->query($sql) or displaySQLError(
            'Total Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db2->count_rows() > 0) {
            $r = $this->db2->get_row();
            return (int)$r['total'];
        } else {
            return 0;
        }
    }

}
