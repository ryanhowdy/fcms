<?php

class User
{
    public $id;
    public $tzOffset;
    public $displayName;
    public $email;

    private $error;
    
    public static $instance = null;

    /**
     * __construct 
     * 
     * @param FCMS_Error $error 
     * @param int        $id
     * 
     * @return void
     */
    public function __construct (FCMS_Error $error, $id = null)
    {
        if (!isset($_SESSION['fcms_id']) && is_null($id))
        {
            $this->displayName = 'unknown-user';
            $this->email       = 'unknow-email';
            $this->tzOffset    = '';
            $this->access      = 10;
            return;
        }
        else if (isset($_SESSION['fcms_id']))
        {
            $this->id = (int)$_SESSION['fcms_id'];
        }

        // Passing in an ID, will overwrite the session
        // So we can create a user object for a user other than the logged in one
        if (!is_null($id))
        {
            $this->id = (int)$id;
        }

        $this->error = $error;


        // Get User info
        $sql = "SELECT u.`fname`, u.`lname`, u.`username`, s.`displayname`, u.`email`, s.`timezone`, u.`access`
                FROM `fcms_users` AS u
                LEFT JOIN `fcms_user_settings` AS s ON u.`id` = s.`user`
                WHERE u.`id` = ?";

        $userInfo = DB::select($sql, array($this->id));
        if (empty($userInfo))
        {
            $this->error->setMessage(sprintf(T_('Could not get information for user [%s].'), $this->id));
            return;
        }

        $this->displayName = $this->getDisplayNameFromData($userInfo[0]);
        $this->email       = $userInfo[0]->email;
        $this->tzOffset    = $userInfo[0]->timezone;
        $this->access      = $userInfo[0]->access;

        return;
    }

    /**
     * getInstance 
     * 
     * @return object
     */
    public static function getInstance ($error)
    {
        if (!isset(self::$instance))
        {
            self::$instance = new User($error);
        }

        return self::$instance;
    }

    /**
     * getDisplayNameFromData 
     * 
     * @param array $data 
     * 
     * @return string
     */
    private function getDisplayNameFromData ($data)
    {
        $ret = '';

        switch($data->displayname)
        {
            case '1':
                $ret = cleanOutput($data->fname);
                break;

            case '2':
                $ret = cleanOutput($data->fname).' '.cleanOutput($data->lname);
                break;

            case '3':
                $ret = cleanOutput($data->username);
                break;

            default:
                $ret = cleanOutput($data->username);
                break;
        }

        return $ret;
    }
}
