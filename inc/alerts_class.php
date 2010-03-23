<?php
include_once('util_inc.php');
include_once('language.php');

class Alerts
{

	var $db;

	function Alerts ($database)
    {
		$this->db = $database;
	}
    
    function displayNewAdminHome ($userid)
    {
        global $LANG;
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
                echo '
            <div id="alert_new_admin_home" class="info-alert">
                <h2>Welcome to '.getSiteName().'</h2>
                <p>It looks like you have <a href="help.php#adm-access">Admin rights</a>, which allow you to:</p>
                <ul>
                    <li>Edit or delete just about anything</li>
                    <li>Add/edit/delete members</li>
                    <li>Create/edit polls</li>
                    <li>Turn off the site</li>
                    <li>Upgrade the site</li>
                </ul>
                <p><b>Some things you should know about '.getSiteName().' are:</b></p>
                <ul>
                    <li>Go to <a href="settings.php">My Settings</a> to personalize the site.</li>
                    <li>Finish your profile by adding your <a href="addressbook.php">address</a> information.</li>
                </ul>
                <div class="close-alert"><a id="new_admin" href="?alert=alert_new_admin_home">Delete This Alert</a></div>
            </div>';
            }
        }
    }

    function displayNewUserHome ($userid)
    {
        global $LANG;
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
                echo '
            <div id="alert_new_user_home" class="info-alert">
                <h2>Hey, '.getUserDisplayName($userid).'. Thanks for joining.</h2>
                <p>Some things you should know about '.getSiteName().' are:</p>
                <ul>
                    <li>Go to <a href="settings.php">My Settings</a> to personalize the site.</li>
                    <li>Finish your profile by adding your <a href="addressbook.php">address</a> information.</li>
                </ul>
                <div class="close-alert"><a id="new_user" href="?alert=alert_new_user_home">Delete This Alert</a></div>
            </div>';
            }
        }
    }

    function displayPoll ($userid)
    {
        global $LANG;
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
                <h2>'.$LANG['poll_welcome_msg1'].'</h2>
                <p>'.$LANG['poll_welcome_msg2'].'</p>
                <p>'.$LANG['poll_welcome_msg3'].'</p>
                <div class="close-alert"><a id="new_poll" href="?alert=alert_poll">Delete This Alert</a></div>
            </div>';
        }
    }
 
    function displayAddress ($userid)
    {
        global $LANG;
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
                <h3>'.$LANG['no_add_address1'].'</h3>
                <p>'.$LANG['no_add_address2'].'</p>
                <p><b>'.$LANG['no_add_address3a'].'</b> '.$LANG['no_add_address3b'].' <a href="?address='.$_SESSION['login_id'].'">'.$LANG['my_address'].'</a> '.$LANG['no_add_address3c'].'</p>
                <div class="close-alert"><a id="new_address" href="?alert=alert_address">Delete This Alert</a></div>
            </div>';
        }
    }

}
