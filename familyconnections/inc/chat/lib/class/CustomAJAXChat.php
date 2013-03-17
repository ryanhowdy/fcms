<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license GNU Affero General Public License
 * @link https://blueimp.net/ajax/
 */

class CustomAJAXChat extends AJAXChat
{
	// Returns an associative array containing userName, userID and userRole
	// Returns null if login is invalid
	function getValidLoginUserData()
    {
        if (!isset($_SESSION['login_id']))
        {
            die('NOT LOGGED IN');
        }

        $currentUserId = (int)$_SESSION['login_id'];
        $displayName   = $this->getUserDisplayName($currentUserId);
        $currentAccess = $this->checkAccess($currentUserId);

        if ($currentAccess == 1)
        {
            $role = AJAX_CHAT_ADMIN;
        }
        elseif ($currentAccess == 2)
        {
            $role = AJAX_CHAT_MODERATOR;
        }
        else
        {
            $role = AJAX_CHAT_USER;
        }

        $userData = array(
            'userID'    => $currentUserId,
            'userName'  => $displayName,
            'userRole'  => $role
        );

        return $userData;
  }

  // Store the channels the current user has access to
  // Make sure channel names don't contain any whitespace
  function &getChannels()
  {
        $this->_channels = array('Public' => 0);
        return $this->_channels;
  }

  // Store all existing channels
  // Make sure channel names don't contain any whitespace
  function &getAllChannels()
  {
        $this->_allChannels = array('Public' => 0);
        return $this->_allChannels;
	}

	function &getCustomUsers()
    {
		// List containing the registered chat users:
		$users = null;
		require(AJAX_CHAT_PATH.'lib/data/users.php');
		return $users;
	}
	
	function &getCustomChannels()
    {
		// List containing the custom channels:
		$channels = null;
		require(AJAX_CHAT_PATH.'lib/data/channels.php');
		return $channels;
	}

    // Initialize custom configuration settings
    function initCustomConfig()
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->setConfig('dbConnection', 'host', $cfg_mysql_host);
        $this->setConfig('dbConnection', 'user', $cfg_mysql_user);
        $this->setConfig('dbConnection', 'pass', $cfg_mysql_pass);
        $this->setConfig('dbConnection', 'name', $cfg_mysql_db);
    }

    // Initialize custom request variables:
    function initCustomRequestVars()
    {
        $this->setRequestVar('login', true);
    }

    // Never regenerate the session ID
    function regenerateSessionID()
    {
        return;
    }

    /*
     * getUserDisplayName
     *
     * Gets the user's name, displayed how they set in there settings, unless display option is set
     * which will overwrite the user's settings.
     * 
     * @param   int     $userid 
     * @param   int     $display 
     * @param   boolean $isMember 
     * @return  string
     */
    function getUserDisplayName ($userid, $display = 0, $isMember = true)
    {
        $userid = (int)$userid;

        if ($isMember) {
            $sql = "SELECT u.`fname`, u.`lname`, u.`username`, s.`displayname` "
                 . "FROM `fcms_users` AS u, `fcms_user_settings` AS s "
                 . "WHERE u.`id` = '$userid' "
                 . "AND u.`id` = s.`user`";
        } else {
            $sql = "SELECT `fname`, `lname`, `username` "
                 . "FROM `fcms_users` "
                 . "WHERE `id` = '$userid' ";
        }
        $result = mysql_query($sql) or displaySQLError(
            'Displayname Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);

        // Do we want user's settings or overriding it?
        if ($display < 1) {
            $displayname = $r['displayname'];
        } else {
            $displayname = $display;
        }
        switch($displayname) {
            case '1': return $r['fname']; break;
            case '2': return $r['fname'].' '.$r['lname']; break;
            case '3': return $r['username']; break;
            default: return $r['username']; break;
        }
    }

    /**
     * checkAccess 
     *
     * Returns the access level as a number for the given user.
     * 
     * @param   int     $userid 
     * @return  int
     */
    function checkAccess ($userid)
    {
        $userid = (int)$userid;

        $sql = "SELECT `access` 
                FROM `fcms_users` 
                WHERE `id` = '$userid'";
        $result = mysql_query($sql) or displaySQLError(
            'Access Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);
        return $r['access'];
    }
}
?>
