<?php
/**
 * Profile Destination 
 * 
 * Saves profile photos to the local uploads directory.
 *  - uploads/avatar/
 * 
 * @package Destination
 * @subpackage Profile
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class ProfileDestination extends Destination
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
        $this->relativePath = URL_PREFIX . 'uploads/avatar/';
        $this->absolutePath = ROOT       . 'uploads/avatar/';

        $this->destinationPath = $this->absolutePath;
    }
}
