<?php

class User
{
    public $id;
    public $tzOffset;
    public $displayName;

    private $error;
    
    /**
     * __construct 
     * 
     * @param object $error 
     * 
     * @return void
     */
    public function __construct ($error)
    {
        if (!isset($_SESSION['login_id']))
        {
            return;
        }

        $this->id    = (int)$_SESSION['login_id'];
        $this->error = $error;

        // Get User info
        $sql = "SELECT u.`fname`, u.`lname`, u.`username`, s.`displayname` 
                FROM `fcms_users` AS u, `fcms_user_settings` AS s 
                WHERE u.`id` = '$this->id' 
                AND u.`id` = s.`user`";

        $result = mysql_query($sql);
        if (!$result)
        {
            $msg       = sprintf(T_('Could not get information for user [%s].'), $this->id);
            $debugInfo = $sql."\n".mysql_error();

            $this->error->add($msg,  $debugInfo);
            return;
        }

        $data = mysql_fetch_assoc($result);

        $this->displayName = $this->getDisplayNameFromData($data);

        return;
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
