<?php
/**
 * UploadPhotoGallery.
 *
 * Handles printing the form, and submitting of the form for the 'Basic'
 * standard photo gallery upload.
 *
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class PicasaUploadPhotoGallery extends UploadPhotoGallery
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

        foreach ($this->albumFeed->entry as $photo)
        {
            $id = (int) $photo->children('gphoto', true)->id;

            // just get the photos the user choose in the form
            if (!in_array($id, $this->formData['photos']))
            {
                continue;
            }

            // thumbnails
            $group = $photo->children('media', true)->group;

            $thumbnail = (string) $group->thumbnail[0]->attributes()->url;
            $medium = (string) $group->thumbnail[1]->attributes()->url;

            if ($this->usingFullSizePhotos)
            {
                $full = (string) $group->thumbnail[2]->attributes()->url;
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

            $newPhotoFilenames[$newPhotoId] = $newPhotoId.'.'.$extension;
        }

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
                'details' => '<p>'.T_('You must choose a Picasa Web Album selected.').'</p>',
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
        $user = $formData['picasa_user'];

        $googleClient = getAuthedGoogleClient($this->fcmsUser->id);

        $json = json_decode($_SESSION['googleSessionToken']);
        $token = $json->access_token;

        $curl = curl_init();

        $thumbSizes = '150c,600';
        if ($this->usingFullSizePhotos)
        {
            $thumbSizes .= ',d';
        }

        $url = 'https://picasaweb.google.com/data/feed/api/user/default/albumid/'.$albumId.'?thumbsize='.$thumbSizes;

        curl_setopt_array(
            $curl,
            [
                CURLOPT_CUSTOMREQUEST  => 'GET',
                CURLOPT_URL            => $url,
                CURLOPT_HTTPHEADER     => ['GData-Version: 2', 'Authorization: Bearer '.$token],
                CURLOPT_RETURNTRANSFER => 1,
            ]
        );

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode !== 200)
        {
            $this->fcmsError->add([
                'type'    => 'operation',
                'message' => T_('Could not get Picasa data.'),
                'error'   => $response,
                'file'    => __FILE__,
                'line'    => __LINE__,
             ]);

            return false;
        }

        $xml = new SimpleXMLElement($response);

        $this->albumFeed = $xml;
    }
}
