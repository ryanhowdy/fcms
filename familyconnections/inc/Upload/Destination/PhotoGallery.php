<?php
/**
 * PhotoGallery Destination 
 * 
 * Saves photos to the local uploads directory.
 *  - uploads/photos/memberX/
 * 
 * @package Destination
 * @subpackage PhotoGallery
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class PhotoGalleryDestination extends Destination
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
        $this->relativePath = URL_PREFIX . 'uploads/photos/';
        $this->absolutePath = ROOT       . 'uploads/photos/';

        $this->destinationPath = $this->absolutePath.'member'.(int)$this->fcmsUser->id.'/';
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
        $prefix = '';
        if ($size == 'thumbnail')
        {
            $prefix = 'tb_';
        }
        elseif ($size == 'full')
        {
            $prefix = 'full_';
        }

        $path = $this->relativePath.'member'.(int)$data['user'].'/';

        $photoSrc = $path.$prefix.basename($data['filename']);

        // XXX: we may have uploaded this photo before we 
        // starting using full sized photos, so this full
        // sized photo may not exist.
        // Give them main size instead
        if ($size == 'full' && !file_exists($photoSrc))
        {
            $photoSrc = $path.basename($data['filename']);
        }

        return $photoSrc;
    }
}
