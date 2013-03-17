<?php

class User
{
    public $id;
    public $tzOffset;
    public $displayName;
    public $email;

    private $error;
    private $db;
    
    public static $instance = null;

    /**
     * __construct 
     * 
     * @param object $error 
     * 
     * @return void
     */
    public function __construct ($error, $db)
    {
        if (!isset($_SESSION['login_id']))
        {
            $this->displayName = 'unknown-user';
            $this->email       = 'unknow-email';
            $this->tzOffset    = '';
            $this->access      = 10;
            return;
        }

        $this->error = $error;
        $this->db    = $db;

        $this->id = (int)$_SESSION['login_id'];

        // Get User info
        $sql = "SELECT u.`fname`, u.`lname`, u.`username`, s.`displayname`, u.`email`, s.`timezone`, u.`access`
                FROM `fcms_users` AS u
                LEFT JOIN `fcms_user_settings` AS s ON u.`id` = s.`user`
                WHERE u.`id` = ?";

        $userInfo = $this->db->getRow($sql, $this->id);
        if ($userInfo === false)
        {
            $this->error->setMessage(sprintf(T_('Could not get information for user [%s].'), $this->id));
            return;
        }

        $this->displayName = $this->getDisplayNameFromData($userInfo);
        $this->email       = $userInfo['email'];
        $this->tzOffset    = $userInfo['timezone'];
        $this->access      = $userInfo['access'];

        return;
    }

    /**
     * getInstance 
     * 
     * @return object
     */
    public static function getInstance ($error, $db)
    {
        if (!isset(self::$instance))
        {
            self::$instance = new User($error, $db);
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

        switch($data['displayname'])
        {
            case '1':
                $ret = cleanOutput($data['fname']);
                break;

            case '2':
                $ret = cleanOutput($data['fname']).' '.cleanOutput($data['lname']);
                break;

            case '3':
                $ret = cleanOutput($data['username']);
                break;

            default:
                $ret = cleanOutput($data['username']);
                break;
        }

        return $ret;
    }
}
