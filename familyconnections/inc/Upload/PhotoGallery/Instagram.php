<?php
/**
 * Instagram Upload PhotoGallery 
 * 
 * Handles uploading of photos from the Instagram uploader.
 * 
 * @package Upload
 * @subpackage Photo
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class InstagramUploadPhotoGallery extends UploadPhotoGallery
{
    /**
     * __construct 
     * 
     * @param FCMS_Error        $fcmsError 
     * @param Database          $fcmsDatabase 
     * @param User              $fcmsUser 
     * @param PhotoDestination  $photoDestination 
     * @param UploadPhoto       $uploadPhoto 
     * 
     * @return void
     */
    public function __construct (FCMS_Error $fcmsError, Database $fcmsDatabase, User $fcmsUser, PhotoDestination $photoDestination, UploadPhoto $uploadPhoto = null)
    {
        $this->fcmsError           = $fcmsError;
        $this->fcmsDatabase        = $fcmsDatabase;
        $this->fcmsUser            = $fcmsUser;
        $this->photoDestination    = $photoDestination;
        $this->uploadPhoto         = $uploadPhoto;
        $this->usingFullSizePhotos = usingFullSizePhotos();
    }

    /**
     * upload
     * 
     * @return boolean
     */
    public function upload ($formData)
    {
        $this->setFormData($formData);

        // Additional validation
        if (!$this->validate())
        {
            return false;
        }

        $existingIds = getExistingInstagramIds();

        foreach ($this->formData['photos'] AS $data)
        {
            list($sourceId, $thumbnail, $medium, $full, $caption) = explode('|', $data);

            // Skip existing photos
            if (isset($existingIds[$sourceId]))
            {
                continue;
            }

            // Save external paths
            $sql = "INSERT INTO `fcms_gallery_external_photo`
                        (`source_id`, `thumbnail`, `medium`, `full`)
                    VALUES
                        (?, ?, ?, ?)";

            $params = array(
                $sourceId,
                $thumbnail,
                $medium,
                $full
            );

            $id = $this->fcmsDatabase->insert($sql, $params);
            if ($id === false)
            {
                return false;
            }

            $this->newPhotoIds[] = $id;

            // Insert new photo
            $sql = "INSERT INTO `fcms_gallery_photos`
                        (`date`, `external_id`, `caption`, `category`, `user`)
                    VALUES
                        (NOW(), ?, ?, ?, ?)";

            $params = array(
                $id,
                $caption,
                $this->newCategoryId,
                $this->fcmsUser->id
            );

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                return false;
            }
        }

        return true;
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

        $this->newCategoryId  = getUserInstagramCategory($this->fcmsUser->id);
    }

    /**
     * validate 
     * 
     * @return boolean
     */
    protected function validate ()
    {
        if (empty($this->formData['photos']))
        {
            $this->fcmsError->add(array(
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('You must choose at least one photo.').'</p>'
            ));

            return false;
        }

        return true;
    }

}
