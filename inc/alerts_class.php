<?php
include_once('utils.php');
include_once('database_class.php');

/**
 * Alerts 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Alerts
{
    var $db;
    var $currentUserId;

    /**
     * Alerts 
     * 
     * @param string $id 
     *
     * @return void
     */
    function Alerts ($id)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($id, 'int');
    }
    
    /**
     * displayNewUserHome 
     * 
     * @param  int  $userid 
     * 
     * @return boolean
     */
    function displayNewUserHome ($userid)
    {
        include_once 'addressbook_class.php';
        $addressObj = new AddressBook($userid);

        $userid = cleanInput($userid, 'int');

        $sql = "SELECT `id`
                FROM `fcms_alerts` 
                WHERE `alert` = 'alert_new_user_home'
                AND `user` = '$userid' 
                AND `hide` = 1";
        if (!$this->db->query($sql))
        {
            displaySQLError('Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }

        if ($this->db->count_rows() >= 1)
        {
            return false;
        }

        $sitename = getSiteName();
        $sitename = cleanOutput($sitename);
        $complete = 0;

        // social media
        $social = '<a href="settings.php?view=socialmedia">'.T_('Connect social media sites').'</a>';
        if (userConnectedSocialMedia($userid))
        {
            $social = '<span>'.T_('Connect social media sites').'</span>';
            $complete++;
        }

        // add profile pic
        $profilePicture = '<span>'.T_('Add a Profile Picture').'</span>';
        $complete++;
        $avatar = getCurrentAvatar($userid);
        if ($avatar == 'uploads/avatar/no_avatar.jpg')
        {
            $profilePicture = '<a href="profile.php?view=picture">'.T_('Add a Profile Picture').'</a>';
            $complete--;
        }

        // update contact info
        $address = '<a href="addressbook.php?cat=all&edit='.$userid.'">'.T_('Add your Address/Contact information').'</a>';
        if ($addressObj->userhasAddress($userid))
        {
            $address = '<span>'.T_('Add your Address/Contact information').'</span>';
            $complete++;
        }

        // vote
        $sql = "SELECT MAX(`id`) AS 'max'
                FROM `fcms_polls`";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Current Poll Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $r = mysql_fetch_array($result);
        $currentPoll = $r['max'];

        $sql = "SELECT `id`
                FROM `fcms_poll_votes`
                WHERE `user` = '$userid'
                AND `poll_id` = '$currentPoll'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Vote Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $rows = mysql_num_rows($result);

        $poll = '<a href="?poll_id='.$currentPoll.'">'.T_('Vote on the Poll').'</a>';
        if ($rows > 0)
        {
            $poll = '<span>'.T_('Vote on the Poll').'</span>';
            $complete++;
        }

        $percent = ($complete / 4) * 100;

        echo '
        <div id="alert_new_user_home" class="info-alert">
            <h2>'.T_('It looks like you\'re new here.').'</h2>
            <p>'.T_('Complete the following list to get the most out of the site:').'</p>
            <small>'.sprintf(T_('Your profile is %s complete.'), $percent).'</small>
            <div id="progress"><div style="width: '.$percent.'%"></div></div>
            <ol class="todo">
                <li>'.$social.'</a></li>
                <li>'.$profilePicture.'</a></li>
                <li>'.$address.'</li>
                <li>'.$poll.'</li>
            </ol>
            <p style="text-align:right"><a id="new_admin" href="?alert=alert_new_user_home">'.T_('Skip This and get right to the app.').'</a></p>
        </div>';

        return true;
    }

    /**
     * displayPoll 
     * 
     * @param  int  $userid 
     * @return void
     */
    function displayPoll ($userid)
    {
        $userid = cleanInput($userid, 'int');

        $sql = "SELECT * 
                FROM `fcms_alerts` 
                WHERE `alert` = 'alert_poll'
                AND `user` = '$userid' 
                AND `hide` = 1";
        $this->db->query($sql) or displaySQLError(
            'Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() < 1) { 
            echo '
            <div id="alert_poll" class="info-alert">
                <h2>'.T_('Welcome to the Poll Section').'</h2>
                <p>'.T_('Here you can add new Poll questions or edit existing polls.').'</p>
                <p>'.T_('If you do not want to use Polls on your site, simply delete all existing polls and they will no longer show up on the frontpage.').'</p>
                <div class="close-alert"><a id="new_poll" href="?alert=alert_poll">'.T_('Delete This Alert').'</a></div>
            </div>';
        }
    }
 
    /**
     * displayAddress 
     * 
     * @param  int  $userid 
     * @return void
     */
    function displayAddress ($userid)
    {
        $userid = cleanInput($userid, 'int');

        $sql = "SELECT * 
                FROM `fcms_alerts` 
                WHERE `alert` = 'alert_address'
                AND `user` = '$userid' 
                AND `hide` = 1";
        $this->db->query($sql) or displaySQLError(
            'Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() < 1) { 
            echo '
            <div id="alert_address" class="info-alert">
                <h3>'.T_('It looks like your address is incomplete.').'</h3>
                <p>'.T_('The other website members would appreciate it if you would complete your address information.  This will help them stay in touch.').'</p>
                <p><a href="?address='.$this->currentUserId.'">'.T_('Complete Address Now').'</a></p>
                <div class="close-alert"><a id="new_address" href="?alert=alert_address">'.T_('Delete This Alert').'</a></div>
            </div>';
        }
    }

    /**
     * displayScheduler
     * 
     * @param  int  $userid 
     * @return void
     */
    function displayScheduler ($userid)
    {
        $userid = cleanInput($userid, 'int');

        $sql = "SELECT * 
                FROM `fcms_alerts` 
                WHERE `alert` = 'alert_scheduler'
                AND `user` = '$userid' 
                AND `hide` = 1";
        if (!$this->db->query($sql))
        {
            displaySQLError('Alert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() < 1)
        {
            echo '
            <div id="alert_scheduler" class="info-alert">
                <h3>'.T_('Important info about FCMS Scheduler.').'</h3>
                <p>'.T_('FCMS Scheduler is a replacement for cron.  If your host supports cron, you should disable the FCMS Scheduler and use cron instead.').'</p>
                <p>'.T_('FCMS Scheduler can NOT guarantee that the scheduled tasks will complete on the desired intervals.').'</p>
                <div class="close-alert"><a id="del_scheduler_alert" href="?alert=alert_scheduler">'.T_('Delete This Alert').'</a></div>
            </div>';
        }
    }

}
