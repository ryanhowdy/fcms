<?php
include_once('util_inc.php');

class Alerts
{

    var $db;

    function Alerts ($database)
    {
        $this->db = $database;
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
                <h2>'.sprintf(_('Welcome to %s'), $sitename).'</h2>
                <p>'._('It looks like you have an Admin Access Level, which allow you to:').'</p>
                <ul>
                    <li>'._('Edit or delete just about anything').'</li>
                    <li>'._('Add/edit/delete members').'</li>
                    <li>'._('Create/edit polls').'</li>
                    <li>'._('Change site settings').'</li>
                    <li>'._('Upgrade the site').'</li>
                    <li><a href="help.php#adm-access">'._('Find out more.').'</a></li>
                </ul>
                <p><b>'._('Getting Started...').'</b></p>
                <ul>
                    <li><a href="settings.php">'._('Personalize the site').'</a></li>
                    <li><a href="addressbook.php">'._('Share your Address/Contact information').'</a></li>
                    <li><a href="gallery/index.php">'._('Share Photos').'</a></li>
                    <li><a href="messageboard.php">'._('Start a discussion').'</a></li>
                </ul>
                <div class="close-alert"><a id="new_admin" href="?alert=alert_new_admin_home">'._('Delete This Alert').'</a></div>
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
                <h2>'.sprintf(_('Hey, %s. Thanks for joining.'), $user).'</h2>
                <p>'.sprintf(_('It looks like you have an Access Level of %s.'), $access).'  <a href="help.php#adm-access">'._('Find out more.').'</a></p>
                <p><b>'._('Getting Started...').'</b></p>
                <ul>
                    <li><a href="settings.php">'._('Personalize the site').'</a></li>
                    <li><a href="addressbook.php">'._('Share your Address/Contact information').'</a></li>
                    <li><a href="gallery/index.php">'._('Share Photos').'</a></li>
                    <li><a href="messageboard.php">'._('Start a discussion').'</a></li>
                </ul>
                <div class="close-alert"><a id="new_user" href="?alert=alert_new_user_home">'._('Delete This Alert.').'</a></div>
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
                <h2>'._('Welcome to the Poll Section').'</h2>
                <p>'._('Here you can add new Poll questions or edit existing polls.').'</p>
                <p>'._('If you do not want to use Polls on your site, simply delete all existing polls and they will no longer show up on the frontpage.').'</p>
                <div class="close-alert"><a id="new_poll" href="?alert=alert_poll">'._('Delete This Alert').'</a></div>
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
                <h3>'._('It looks like you haven\'t added your address information yet.').'</h3>
                <p>'._('The other website members would appreciate it if you would add your address information.  This will help them stay in touch.').'</p>
                <p><a href="?address='.$this->current_user_id.'">'._('Add Address').'</a></p>
                <div class="close-alert"><a id="new_address" href="?alert=alert_address">'._('Delete This Alert').'</a></div>
            </div>';
        }
    }

}
