<?php
/**
 * FacebookUploadPhotoGallery.
 *
 * Handles uploading photos from facebook.
 *
 * @copyright 2015 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class FacebookUploadPhotoGallery extends UploadPhotoGallery
{
    private $albumFeed;
    private $newPhotoIds;

    /**
     * upload.
     *
     * @param array $formData
     *
     * @return bool
     */
    public function upload($formData)
    {
        // Save necessary form data
        $this->setFormData($formData);

        // Validate
        if (!$this->validate())
        {
            return false;
        }

        // Create directory
        if (!$this->destination->createDirectory())
        {
            return false;
        }

        // Insert new category
        if (!$this->insertCategory())
        {
            return false;
        }

        $newPhotoFilenames = [];

        $usingFullSizePhotos = $this->usingFullSizePhotos;

        foreach ($this->albumFeed['data'] as $photo)
        {
            $id = $photo['id'];

            // just get the photos the user choose in the form
            if (!in_array($id, $this->formData['photos']))
            {
                continue;
            }

            $thumbnail = '';
            $medium = '';
            $full = '';

            // Loop over the images smallest to largest
            $images = array_reverse($photo['images']);

            foreach ($images as $img)
            {
                // thumbnail
                if (empty($thumbnail) && $img['width'] >= 150)
                {
                    $thumbnail = $img['source'];
                }
                // medium
                if (empty($medium) && $img['width'] >= 600)
                {
                    $medium = $img['source'];
                }
            }

            if (empty($thumbnail))
            {
                $thumbnail = $photo['images'][0]['source'];
            }
            if (empty($medium))
            {
                $medium = $photo['images'][0]['source'];
            }
            if ($usingFullSizePhotos)
            {
                // The first image in images is always the largest
                $full = $photo['images'][0]['source'];
            }

            $extension = $this->uploadPhoto->getFileExtension($thumbnail);

            // Save photo to db
            $params = [
                $this->newCategoryId,
                $this->fcmsUser->id,
            ];

            $sql = 'INSERT INTO `fcms_gallery_photos`
                        (`date`, `category`, `user`)
                    VALUES 
                        (NOW(), ?, ?)';

            $newPhotoId = $this->fcmsDatabase->insert($sql, $params);
            if ($newPhotoId === false)
            {
                return false;
            }

            $this->newPhotoIds[] = $newPhotoId;

            $newFilename = $newPhotoId.'.'.$extension;

            // Move files to server
            $this->destination->savePhotoFromSource($thumbnail, 'tb_'.$newFilename);
            $this->destination->savePhotoFromSource($medium, $newFilename);
            if ($this->usingFullSizePhotos)
            {
                $this->destination->savePhotoFromSource($full, 'full_'.$newFilename);
            }

            // Resize the thumbnail (facebook doesn't give us a square photo)
            $this->uploadPhoto->fileName = 'tb_'.$newFilename;
            $this->uploadPhoto->extension = $extension;

            $this->uploadPhoto->resize(150, 150, 'square');

            $newPhotoFilenames[$newPhotoId] = $newPhotoId.'.'.$extension;
        }

        // Update the filenames
        foreach ($newPhotoFilenames as $id => $filename)
        {
            $sql = 'UPDATE `fcms_gallery_photos` 
                    SET `filename` = ?
                    WHERE `id`     = ?';

            if (!$this->fcmsDatabase->update($sql, [$filename, $id]))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * validate.
     *
     * @return bool
     */
    public function validate()
    {
        if (empty($this->formData['albums']))
        {
            $this->fcmsError->add([
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('You must choose a Facebook album.').'</p>',
            ]);

            return false;
        }

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

    /**
     * setFormData.
     *
     * Saves all the data passed in from the form upload.
     *
     * @param array $formData
     *
     * @return void
     */
    public function setFormData($formData)
    {
        $this->formData = $formData;

        $albumId = $formData['albums'];

        $config = getFacebookConfigData();
        $accessToken = getUserFacebookAccessToken($this->fcmsUser->id);

        $facebook = new Facebook([
            'appId'  => $config['fb_app_id'],
            'secret' => $config['fb_secret'],
        ]);

        $facebook->setAccessToken($accessToken);

        try
        {
            $fbPhotos = $facebook->api("/$albumId/photos");
        }
        catch (FacebookApiException $e)
        {
            $this->fcmsError->add([
                'type'    => 'operation',
                'message' => T_('Could not get Facebook photos.'),
                'error'   => $e,
                'file'    => __FILE__,
                'line'    => __LINE__,
            ]);

            return false;
        }

        $this->albumFeed = $fbPhotos;
    }
}
