<?php
include_once('util_inc.php');

class Alerts
{

    var $db;
    var $current_user_id;

    function Alerts ($id, $database)
    {
        $this->db = $database;
        $this->current_user_id = $id;
    }
    
    function displayNewAdminHome ($userid)
    {
        if (checkAccess($userid) < 2) {
            $sql = "SELECT * 
                    FROM `fcms_alerts` 
                    WHERE `alert` = 'alert_new_admin_home'
                    AND `user` = $userid 
                    AND `hide` = 1";
            $this->db->query($sql) or displaySQLError(
                'Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            if ($this->db->count_rows() < 1) { 
                $sitename = getSiteName();
                echo '
            <div id="alert_new_admin_home" class="info-alert">
                <h2>'.sprintf(T_('Welcome to %s'), $sitename).'</h2>
                <p>'.T_('It looks like you have an Admin Access Level, which allow you to:').'</p>
                <ul>
                    <li>'.T_('Edit or delete just about anything').'</li>
                    <li>'.T_('Add/edit/delete members').'</li>
                    <li>'.T_('Create/edit polls').'</li>
                    <li>'.T_('Change site settings').'</li>
                    <li>'.T_('Upgrade the site').'</li>
                    <li><a href="help.php#adm-access">'.T_('Find out more.').'</a></li>
                </ul>
                <p><b>'.T_('Getting Started...').'</b></p>
                <ul>
                    <li><a href="settings.php">'.T_('Personalize the site').'</a></li>
                    <li><a href="addressbook.php">'.T_('Share your Address/Contact information').'</a></li>
                    <li><a href="gallery/index.php">'.T_('Share Photos').'</a></li>
                    <li><a href="messageboard.php">'.T_('Start a discussion').'</a></li>
                </ul>
                <div class="close-alert"><a id="new_admin" href="?alert=alert_new_admin_home">'.T_('Delete This Alert').'</a></div>
            </div>';
            }
        }
    }

    function displayNewUserHome ($userid)
    {
        if (checkAccess($userid) > 1) {
            $sql = "SELECT * 
                    FROM `fcms_alerts` 
                    WHERE `alert` = 'alert_new_user_home'
                    AND `user` = $userid 
                    AND `hide` = 1";
            $this->db->query($sql) or displaySQLError(
                'Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            if ($this->db->count_rows() < 1) { 
                $user = getUserDisplayName($userid);
                $sitename = getSiteName();
                $access = getAccessLevel($userid);
                echo '
            <div id="alert_new_user_home" class="info-alert">
                <h2>'.sprintf(T_('Hey, %s. Thanks for joining.'), $user).'</h2>
                <p>'.sprintf(T_('It looks like you have an Access Level of %s.'), $access).'  <a href="help.php#adm-access">'.T_('Find out more.').'</a></p>
                <p><b>'.T_('Getting Started...').'</b></p>
                <ul>
                    <li><a href="settings.php">'.T_('Personalize the site').'</a></li>
                    <li><a href="addressbook.php">'.T_('Share your Address/Contact information').'</a></li>
                    <li><a href="gallery/index.php">'.T_('Share Photos').'</a></li>
                    <li><a href="messageboard.php">'.T_('Start a discussion').'</a></li>
                </ul>
                <div class="close-alert"><a id="new_user" href="?alert=alert_new_user_home">'.T_('Delete This Alert.').'</a></div>
            </div>';
            }
        }
    }

    function displayPoll ($userid)
    {
        $sql = "SELECT * 
                FROM `fcms_alerts` 
                WHERE `alert` = 'alert_poll'
                AND `user` = $userid 
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
 
    function displayAddress ($userid)
    {
        $sql = "SELECT * 
                FROM `fcms_alerts` 
                WHERE `alert` = 'alert_address'
                AND `user` = $userid 
                AND `hide` = 1";
        $this->db->query($sql) or displaySQLError(
            'Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() < 1) { 
            echo '
            <div id="alert_address" class="info-alert">
                <h3>'.T_('It looks like you haven\'t added your address information yet.').'</h3>
                <p>'.T_('The other website members would appreciate it if you would add your address information.  This will help them stay in touch.').'</p>
                <p><a href="?address='.$this->current_user_id.'">'.T_('Add Address').'</a></p>
                <div class="close-alert"><a id="new_address" href="?alert=alert_address">'.T_('Delete This Alert').'</a></div>
            </div>';
        }
    }

}
