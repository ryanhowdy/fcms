<?php
/**
 * Photo Gallery Protected Destination.
 *
 * Saves uploads to a local directory outside of www that is
 * is defined by a path called UPLOADS in inc/config_inc.php.
 *
 * @copyright 2013 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class ProtectedPhotoGalleryDestination extends PhotoGalleryDestination
{
    /**
     * __construct.
     *
     * @param FCMS_Error $fcmsError
     * @param User       $fcmsUser
     *
     * @return void
     */
    public function __construct(FCMS_Error $fcmsError, User $fcmsUser)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsUser = $fcmsUser;
        $this->relativePath = GALLERY_PREFIX.'photo.php';
        $this->absolutePath = UPLOADS.'photos/';

        $this->destinationPath = $this->absolutePath.'member'.(int) $this->fcmsUser->id.'/';
    }

    /**
     * getPhotoSource.
     *
     * @param array  $data
     * @param string $size
     *
     * @return string
     */
    public function getPhotoSource($data, $size = 'thumbnail')
    {
        $photoSrc = $this->relativePath.'?id='.(int) $data['id'].'&amp;size='.$size;

        $absolutePathToFullPhoto = $this->destinationPath.'full_'.basename($data['filename']);

        // XXX: we may have uploaded this photo before we
        // starting using full sized photos, so this full
        // sized photo may not exist.
        // Give them main size instead
        if ($size == 'full' && !file_exists($absolutePathToFullPhoto)) {
            $photoSrc = $this->relativePath.'?id='.(int) $data['id'].'&amp;size=medium';
        }

        return $photoSrc;
    }
}
