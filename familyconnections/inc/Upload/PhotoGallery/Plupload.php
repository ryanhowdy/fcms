<?php
/**
 * Plupload Upload PhotoGallery 
 * 
 * Handles uploading of photos from the Plupload uploader.
 * 
 * NOTE: 2-3 calls will be made to this script, one for each type
 * (thumb, main, full). So some things only should occur when
 * when the 'main' photo is being uploaded.  For example:
 * insertCategory() and insertPhoto().
 * 
 * @package Upload
 * @subpackage Photo
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class PluploadUploadPhotoGallery extends UploadPhotoGallery
{
    /**
     * __construct 
     * 
     * @param FCMS_Error  $fcmsError 
     * @param Database    $fcmsDatabase 
     * @param User        $fcmsUser 
     * @param Destination $destination 
     * @param UploadPhoto $uploadPhoto 
     * 
     * @return void
     */
    public function __construct (FCMS_Error $fcmsError, Database $fcmsDatabase, User $fcmsUser, Destination $destination, UploadPhoto $uploadPhoto = null)
    {
        $this->fcmsError           = $fcmsError;
        $this->fcmsDatabase        = $fcmsDatabase;
        $this->fcmsUser            = $fcmsUser;
        $this->destination         = $destination;
        $this->uploadPhoto         = $uploadPhoto;
        $this->usingFullSizePhotos = usingFullSizePhotos();
    }

    /**
     * setFormData 
     * 
     * Saves all the data passed in from the form upload.
     * 
     * @param array $formData
     * 
     * @return void
     */
    protected function setFormData ($formData)
    {
        $this->formData = $formData;

        // When we send multiple photos
        if (isset($_SESSION['photos'][0]['category']))
        {
            // We need to set the category to equal the last
            // category created/used
            $this->formData['category']    = $_SESSION['photos'][0]['category'];
            $this->formData['newCategory'] = '';
        }

        // Save an empty caption for now, user will edit it later
        $this->formData['caption'] = '';
    }

    /**
     * insertCategory 
     * 
     * Overwrite the base insertCategory function, so we can prevent
     * multiple categories being created with each photo type.
     * 
     * @return void
     */
    protected function insertCategory ()
    {
        // if we are uploading multiple photos at once,
        // then when only insert category the first time
        // if mass_photos_category is set, then that means
        // we already inserted the category, so just move on
        if (isset($_SESSION['mass_photos_category']))
        {
            $this->newCategoryId = $_SESSION['mass_photos_category'];
            return true;;
        }

        // Don't insert category for thumb or full
        if ($this->formData['photo_type'] !== 'main')
        {
            $this->newCategoryId = $_SESSION['photos'][0]['category'];
            return true;
        }

        // Only for main
        $ret = parent::insertCategory();
        if (!$ret)
        {
            return false;
        }

        // Set var so we don't create a category with each file in the upload
        $_SESSION['mass_photos_category'] = $this->newCategoryId;
        
        return true;
    }

    /**
     * insertPhoto 
     * 
     * Overwrite the base insertPhoto function, so we can prevent
     * multiple photo ids from being created
     * 
     * @return void
     */
    protected function insertPhoto ()
    {
        // Don't insert photo for thumb or full
        if ($this->formData['photo_type'] !== 'main')
        {
            // The id should be the same as the last 'main' photo
            // that was uploaded
            $lastMainPhoto    = end($_SESSION['photos']);
            $this->newPhotoId = $lastMainPhoto['id'];

            return true;
        }

        // Only for main
        return parent::insertPhoto();
    }

    /**
     * savePhoto 
     * 
     * @return boolean
     */
    protected function savePhoto ()
    {
        $photoTypes = array(
            'main'  => '',
            'thumb' => 'tb_',
            'full'  => 'full_',
        );

        $prefix = $photoTypes[ $this->formData['photo_type'] ];

        // Reset the filename for each photo
        $this->fileName = $prefix.$this->newPhotoId.'.'.$this->extension;

        // Copy temp photo to destination
        $ret = $this->destination->copy($this->formData['photo']['tmp_name'], $this->fileName);
        if ($ret === false)
        {
            return false;
        }

        // Set up the session vars to send to edit page
        // only on main, so we don't get both full size and thumbnail
        if ($this->formData['photo_type'] == 'main')
        {
            $_SESSION['photos'][] = array(
                'id'       => $this->newPhotoId,
                'filename' => $this->fileName,
                'category' => $this->newCategoryId
            );
        }

        return true;
    }
}
