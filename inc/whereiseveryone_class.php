<?php
require_once 'util_inc.php';
require_once 'database_class.php';

/**
 * WhereIsEveryone 
 * 
 * @package   Family_Connections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2010 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html
 */
class WhereIsEveryone
{
    var $db;
    var $currentUserId;
    var $tzOffset;

    /**
     * WhereIsEveryone 
     * 
     * @param int $currentUserId 
     * 
     * @return void
     */
    function WhereIsEveryone ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->tzOffset      = getTimezone($this->currentUserId);
    }

    /**
     * getActiveUsers 
     * 
     * Returns an array of arrays containing the users with foursquare setup.
     *
     *     Array
     *     (
     *         [0] => Array
     *             (
     *                 [user_id] => 9999
     *                 [access_token] => ABC123
     *             )
     *     
     *     ) 
     * 
     * @return array
     */
    function getActiveUsers ()
    {
        $sql = "SELECT `user` AS 'userid', `fs_user_id`, `fs_access_token`, `fname`, `lname`, 
                    `avatar`, `gravatar`, `timezone`
                FROM `fcms_user_settings` AS s, `fcms_users` AS u
                WHERE `fs_user_id` IS NOT NULL
                AND s.`user` = u.`id`";
        if (!$this->db->query($sql))
        {
            displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return false;
        }
        if ($this->db->count_rows() <= 0)
        {
            $users[0] = array();
            return $users;
        }

        $i = 0;
        while($row = $this->db->get_row())
        {
            $users[$i] = array(
                'fcms_user_id' => $row['userid'],
                'user_id'      => $row['fs_user_id'],
                'access_token' => $row['fs_access_token'],
                'name'         => $row['fname'].' '.$row['lname'],
                'avatar'       => $row['avatar'],
                'gravatar'     => $row['gravatar'],
                'timezone'     => $row['timezone'],
            );
            $i++;
        }

        return $users;
    }

    /**
     * getFoursquareConfigData 
     * 
     * Will return an array of the foursquare client id, client secret and callback uri
     * 
     * @return void
     */
    function getFoursquareConfigData ()
    {
        $sql = "SELECT `fs_client_id`, `fs_client_secret`, `fs_callback_url`
                FROM `fcms_config`
                LIMIT 1";
        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        if (mysql_num_rows($result) <= 0)
        {
            echo '
                    <p class="error-alert">'.T_('No configuration data found.').'</p>';
            return;
        }
        return mysql_fetch_assoc($result);
    }
}
