<?php
/**
 * Protected Profile Destination 
 * 
 * Saves uploads to a local directory outside of www that is
 * is defined by a path called UPLOADS in inc/config_inc.php.
 * 
 * @package Destination
 * @subpackage Profile
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class ProtectedProfileDestination extends ProfileDestination
{
    /**
     * __construct 
     * 
     * @param FCMS_Error $fcmsError 
     * @param User       $fcmsUser 
     * 
     * @return void
     */
    public function __construct (FCMS_Error $fcmsError, User $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsUser     = $fcmsUser;
        $this->relativePath = URL_PREFIX . 'file.php';
        $this->absolutePath = UPLOADS . 'avatar/';

        $this->destinationPath = $this->absolutePath;
    }
}
