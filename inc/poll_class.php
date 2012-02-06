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

        $this->currentUserId = (int)$currentUserId;
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
        $userid   = (int)$userid;
        $optionid = (int)$optionid;
        $pollid   = (int)$pollid;

        $sql = "SELECT `user` 
                FROM `fcms_poll_votes` 
                WHERE `user` = '$userid'
                AND `poll_id` = '$pollid'";
        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            echo '<p class="info-alert">'.T_('You have already voted.').'</p>';
        }
        else
        {
            $sql = "UPDATE `fcms_poll_options` 
                    SET `votes` = `votes`+1 
                    WHERE `id` = '$optionid'";
            if (!$this->db->query($sql))
            {
                displaySqlError($sql, mysql_error());
                return;
            }

            $sql = "INSERT INTO `fcms_poll_votes`(`user`, `option`, `poll_id`) 
                    VALUES ('$userid', '$optionid', '$pollid')";
            if (!$this->db->query($sql))
            {
                displaySqlError($sql, mysql_error());
                return;
            }
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
        $pollid = (int)$pollid;

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
        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        $i = 0;
        while ($row = $this->db->get_row())
        {
            $votes = $row['votes'];
            $total = $row['total'];

            if ($total < 1)
            {
                $percent = 0;
            }
            else
            {
                $percent = $votes/$total;
            }

            $width = round((140 * $percent) + 10, 0);

            if ($total < 1)
            {
                $percent = 0;
            }
            else
            {
                $percent = round((($votes/$total) * 100), 0);
            }

            if ($i < 1)
            {
                echo '
            <h3>'.cleanOutput($row['question'], 'html').'</h3>
            <ul class="poll">';
            }

            echo '
                <li>
                    <div><b>'.cleanOutput($row['option'], 'html').'</b>'.$percent.'%<br/>'.$votes.' votes</div>
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
        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            $option = '';

            while ($r = $this->db->get_row())
            {
                if ($option == '')
                {
                    echo '
            <h4>'.cleanOutput($r['option'], 'html').'</h4>
            <ul class="whovoted">
                <li>
                    <img alt="avatar" src="'.getCurrentAvatar($r['user']).'"/> '.getUserDisplayName($r['user']). '
                </li>';
                }
                elseif ($r['option'] != $option)
                {
                    echo '
            </ul>
            <h4>'.cleanOutput($r['option'], 'html').'</h4>
            <ul class="whovoted">
                <li>
                    <img alt="avatar" src="'.getCurrentAvatar($r['user']).'"/> '.getUserDisplayName($r['user']). '
                </li>';
                }
                else
                {
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
        }
        else
        {
            if ($total > 0 )
            {
                echo '
            <p class="info-alert">'.T_('No user data could be found.').'</p>';
            }
            else
            {
                echo '
            <p class="info-alert">'.T_('No one has voted on this poll yet.').'</p>';
            }
        }
    }

    /**
     * displayPoll 
     * 
     * @param   int     $pollId 
     * @param   boolean $showResults 
     * @return  void
     */
    function displayPoll ($pollId = 0, $showResults = true)
    {
        $poll_exists = true;

        // Get Latest Poll ID
        if ($pollId == 0)
        {
            $sql = "SELECT MAX(`id`) AS max FROM `fcms_polls`";
            if (!$this->db->query($sql))
            {
                displaySqlError($sql, mysql_error());
                return;
            }

            $r = $this->db->get_row();
            $pollId = $r['max'];

            if (!is_null($r['max']))
            { 
                $pollId = $r['max'];
            }
            else
            {
                $poll_exists = false;
            }
        }

        // Get Poll Data
        if ($poll_exists)
        {
            $pollId = (int)$pollId;

            $sql = "SELECT p.`id`, `question`, o.`id` AS option_id, `option` 
                    FROM `fcms_polls` AS p, `fcms_poll_options` AS o 
                    WHERE p.`id` = '$pollId' 
                    AND p.`id` = o.`poll_id`";
            if (!$this->db->query($sql))
            {
                displaySqlError($sql, mysql_error());
                $poll_exists = false;
            }
            if ($this->db->count_rows() <= 0)
            {
                $poll_exists = false;
            }
        }

        // We are using Polls
        if ($poll_exists)
        {
            $pollId = (int)$pollId;

            $sql = "SELECT * 
                    FROM `fcms_poll_votes` 
                    WHERE `poll_id` = '$pollId'
                    AND `user` = ".$this->currentUserId;
            if (!$this->db2->query($sql))
            {
                displaySqlError($sql, mysql_error());
                return;
            }

            // User has already voted
            if ($this->db2->count_rows() > 0 && $showResults)
            {
                $this->displayResults($pollId);
            }
            // User hasn't voted yet
            else
            {
                echo '
            <h2 class="pollmenu">'.T_('Polls').'</h2>
            <form method="post" action="home.php">';

                $i  = 0;
                $id = '';

                while ($row = $this->db->get_row())
                {
                    $id = $row['id'];

                    if ($i < 1)
                    {
                        echo '
                <h3>'.cleanOutput($row['question'], 'html').'</h3>';
                    }

                    echo '
                <p><label class="radio_label"><input type="radio" name="option_id" value="'.(int)$row['option_id'].'"/>'.cleanOutput($row['option'], 'html').'</label></p>';
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
        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
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

            while ($row = $this->db->get_row())
            {
                $date = fixDate(T_('M. j, Y, g:i a'), $this->tzOffset, $row['started']);

                echo '
                    <tr>
                        <td><a href="?poll_id='.(int)$row['id'].'">'.cleanOutput($row['question'], 'html').'</a></td>
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
            if (!$this->db->query($sql))
            {
                displaySqlError($sql, mysql_error());
                return;
            }
            $count = $this->db->count_rows();
            $total_pages = ceil($count / 15); 
            displayPages("home.php?action=pastpolls", $page, $total_pages);
        }
        else
        {
            echo "<i>".T_('No previous polls')."</i>";
        }
    }

    /**
     * getTotalVotes 
     * 
     * @param  int  $pollId 
     * @return void
     */
    function getTotalVotes ($pollId)
    {
        $pollId = (int)$pollId;

        $sql = "SELECT SUM(`votes`) AS total 
                FROM `fcms_polls` AS p, `fcms_poll_options` AS o
                WHERE p.`id` = '$pollId' 
                AND p.`id` = o.`poll_id`";
        if (!$this->db2->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db2->count_rows() > 0)
        {
            $r = $this->db2->get_row();
            return (int)$r['total'];
        }
        else
        {
            return 0;
        }
    }

}
