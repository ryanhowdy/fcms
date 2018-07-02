<?php
/**
 * Instagram Upload PhotoGallery.
 *
 * Handles uploading of photos from the Instagram uploader.
 *
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class InstagramUploadPhotoGallery extends UploadPhotoGallery
{
    /**
     * __construct.
     *
     * @param FCMS_Error  $fcmsError
     * @param Database    $fcmsDatabase
     * @param User        $fcmsUser
     * @param Destination $destination
     * @param UploadPhoto $uploadPhoto
     *
     * @return void
     */
    public function __construct(FCMS_Error $fcmsError, Database $fcmsDatabase, User $fcmsUser, Destination $destination, UploadPhoto $uploadPhoto = null)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser = $fcmsUser;
        $this->destination = $destination;
        $this->uploadPhoto = $uploadPhoto;
        $this->usingFullSizePhotos = usingFullSizePhotos();
    }

    /**
     * upload.
     *
     * @return bool
     */
    public function upload($formData)
    {
        $this->setFormData($formData);

        // Additional validation
        if (!$this->validate())
        {
            return false;
        }

        $existingIds = getExistingInstagramIds();

        foreach ($this->formData['photos'] as $data)
        {
            list($sourceId, $thumbnail, $medium, $full, $caption) = explode('|', $data);

            // Skip existing photos
            if (isset($existingIds[$sourceId]))
            {
                continue;
            }

            // Save external paths
            $sql = 'INSERT INTO `fcms_gallery_external_photo`
                        (`source_id`, `thumbnail`, `medium`, `full`)
                    VALUES
                        (?, ?, ?, ?)';

            $params = [
                $sourceId,
                $thumbnail,
                $medium,
                $full,
            ];

            $id = $this->fcmsDatabase->insert($sql, $params);
            if ($id === false)
            {
                return false;
            }

            $this->newPhotoIds[] = $id;

            // Insert new photo
            $sql = 'INSERT INTO `fcms_gallery_photos`
                        (`date`, `external_id`, `caption`, `category`, `user`)
                    VALUES
                        (NOW(), ?, ?, ?, ?)';

            $params = [
                $id,
                $caption,
                $this->newCategoryId,
                $this->fcmsUser->id,
            ];

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * setFormData.
     *
     * Saves all the data passed in from the form upload.
     *
     * @param array $formData
     *
     * @return void
     */
    protected function setFormData($formData)
    {
        $this->formData = $formData;

        $this->newCategoryId = getUserInstagramCategory($this->fcmsUser->id);
    }

    /**
     * validate.
     *
     * @return bool
     */
    protected function validate()
    {
        if (empty($this->formData['photos']))
        {
            $this->fcmsError->add([
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('You must choose at least one photo.').'</p>',
            ]);

            return false;
        }

        return true;
    }
}
