<?php
include_once('util_inc.php');
include_once('locale.php');

/**
 * Members 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Members
{

    var $currentUserId;
    var $db;
    var $tzOffset;

    /**
     * Members 
     * 
     * @param int    $id 
     * 
     * @return  void
     */
    function Members ($id)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($id, 'int');
        $this->tzOffset      = getTimezone($this->currentUserId);
    }

    /**
     * displayAll 
     *
     * Displays a list of all users.
     * 
     * @return  void
     */
    function displayAll ($order)
    {
        $locale = new FCMS_Locale();

        $validOrderTypes = array(
            'alphabetical'  => 'ORDER BY u.`fname`',
            'age'           => 'ORDER BY u.`birthday`',
            'participation' => '',
            'activity'      => 'ORDER BY u.`activity` DESC',
            'joined'        => 'ORDER BY u.`joindate` DESC',
        );
        if (!array_key_exists($order, $validOrderTypes)) {
            echo '
            <div class="error-alert">'.T_('Invalid Order.').'</div>';
            return;
        }

        $sql = "SELECT 
                    u.`id`, u.`activity`, u.`joindate`, u.`fname`, u.`lname`, u.`sex`, 
                    u.`birthday`, u.`username`, u.`avatar`, u.`gravatar`
                FROM `fcms_users` AS u
                WHERE u.`password` != 'NONMEMBER'
                AND u.`password` != 'PRIVATE'
                ".$validOrderTypes[$order];
        $this->db->query($sql) or displaySQLError(
            'Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        echo '
            <ul id="memberlist">';

        while ($row = $this->db->get_row()) {
            $row['points'] = getUserParticipationPoints($row['id']);
            $memberData[] = $row;
        }

        // Sort by participation
        if ($order == 'participation') {
            foreach($memberData as $k=>$v) {
                $b[$k] = strtolower($v['points']);
            }
            asort($b);
            foreach($b as $key=>$val) {
                $c[] = $memberData[$key];
            }
            $memberData = array_reverse($c);
        }

        foreach ($memberData AS $row) {

            $display = '';

            // Alphabetical
            if ($order == 'alphabetical') {
                $display = '('.$row['username'].')';

            // Age
            } elseif ($order == 'age') {
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
                $display = $year_diff.' '.T_('years old');

            // Participation
            } elseif ($order == 'participation') {
                $display = $row['points'];

            // Last Seen
            } elseif ($order == 'activity') {
                $display = '';
                if ($row['activity'] != '0000-00-00 00:00:00') {
                    $display = $locale->fixDate(T_('M. j, Y'), $this->tzOffset, $row['activity']);
                }

            // Joined
            } elseif ($order == 'joined') {
                $display = $locale->fixDate(T_('M. j, Y'), $this->tzOffset, $row['joindate']);
            }

            // Display members
            echo '
                <li>
                    <a class="avatar" href="profile.php?member='.(int)$row['id'].'">
                        <img alt="avatar" src="'.getCurrentAvatar($row['id']).'"/>
                    </a><br/>
                    <a href="profile.php?member='.(int)$row['id'].'">'.$row['fname'].' '.$row['lname'].'</a><br/>
                    '.$display.'
                </li>';
        }
        echo '
            </ul>';
    }

}
