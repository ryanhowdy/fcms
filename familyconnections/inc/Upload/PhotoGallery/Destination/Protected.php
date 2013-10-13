<?php

require_once INC.'Upload/PhotoGallery/Destination.php';

/**
 * Destination 
 * 
 * Saves uploads to a local directory outside of www that is
 * is defined by a path called UPLOADS in inc/config_inc.php.
 * 
 * @package Upload_PhotoGallery
 * @subpackage Destination
 * @copyright 2013 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class ProtectedDestination extends Destination
{
    public $destinationPath;

    /**
     * __construct 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase 
     * @param object $fcmsUser 
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;

        $this->destinationPath = UPLOADS.'photos/member'.$this->fcmsUser->id.'/';
    }

    /**
     * getPhotoSource 
     * 
     * @param array  $data 
     * @param string $size 
     * 
     * @return string
     */
    public function getPhotoSource ($data, $size = 'thumbnail')
    {
        // Gallery Prefix should be defined on each page
        return GALLERY_PREFIX.'photo.php?id='.(int)$data['id'].'&amp;size='.$size;
    }
}
