<?php
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
    var $fcmsError;
    var $fcmsDatabase;
    var $fcmsUser;

    /**
     * Alerts 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase
     * @param object $fcmsUser 
     *
     * @return void
     */
    function Alerts ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
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
        $addressObj = new AddressBook($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser);

        $userid = (int)$userid;

        $sql = "SELECT `id`
                FROM `fcms_alerts` 
                WHERE `alert` = 'alert_new_user_home'
                AND `user` = ? 
                AND `hide` = 1";

        $row = $this->fcmsDatabase->getRow($sql, $userid);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            return false;
        }

        if (count($row) >= 1)
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

        $checkUploadsPath = 'uploads/avatar/no_avatar.jpg';
        if (defined('UPLOADS'))
        {
            $checkUploadsPath = 'file.php?a=no_avatar.jpg';
        }
        if ($avatar == $checkUploadsPath)
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

        $r = $this->fcmsDatabase->getRow($sql);
        if ($r === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $currentPoll = $r['max'];

        $sql = "SELECT `id`
                FROM `fcms_poll_votes`
                WHERE `user` = ?
                AND `poll_id` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, array($userid, $currentPoll));
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $poll = '<a href="polls.php">'.T_('Vote on the Poll').'</a>';
        if (count($rows) > 0)
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
        $userid = (int)$userid;

        $sql = "SELECT * 
                FROM `fcms_alerts` 
                WHERE `alert` = 'alert_poll'
                AND `user` = ? 
                AND `hide` = 1";

        $row = $this->fcmsDatabase->getRow($sql, $userid);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($row) < 1)
        {
            echo '
            <div id="alert_poll" class="alert-message block-message info">
                <a class="close" href="?alert=alert_poll">&times;</a>
                <p>
                    <b>'.T_('Welcome to the Poll Administration.').'</b>
                    '.T_('Here you can add new Poll questions or edit existing polls.').'
                </p>
                <p>'.T_('If you do not want to use Polls on your site, simply delete all existing polls and they will no longer show up on the frontpage.').'</p>
                <div class="alert-actions">
                    <a class="btn small" href="?alert=alert_poll">'.T_('Delete This Alert').'</a>
                </div>
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
        $userid = (int)$userid;

        $sql = "SELECT * 
                FROM `fcms_alerts` 
                WHERE `alert` = 'alert_address'
                AND `user` = ?
                AND `hide` = 1";

        $row = $this->fcmsDatabase->getRow($sql, $userid);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($row) < 1)
        {
            echo '
            <div id="alert_address" class="info-alert">
                <h3>'.T_('It looks like your address is incomplete.').'</h3>
                <p>'.T_('The other website members would appreciate it if you would complete your address information.  This will help them stay in touch.').'</p>
                <p><a href="?address='.$this->fcmsUser->id.'">'.T_('Complete Address Now').'</a></p>
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
        $userid = (int)$userid;

        $sql = "SELECT * 
                FROM `fcms_alerts` 
                WHERE `alert` = 'alert_scheduler'
                AND `user` = ?
                AND `hide` = 1";

        $rows = $this->fcmsDatabase->getRows($sql, $userid);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($rows) < 1)
        {
            echo '
            <div id="alert_scheduler" class="alert-message block-message info">
                <a class="close" href="?alert=alert_scheduler">&times;</a>
                <h3>'.T_('Important info about FCMS Scheduler.').'</h3>
                <p>'.T_('FCMS Scheduler is a replacement for cron.  If your host supports cron, you should disable the FCMS Scheduler and use cron instead.').'</p>
                <p>'.T_('FCMS Scheduler can NOT guarantee that the scheduled tasks will complete on the desired intervals.').'</p>
                <div class="alert-actions">
                    <a class="btn small" href="?alert=alert_scheduler">'.T_('Delete This Alert').'</a>
                </div>
            </div>';
        }
    }

}
