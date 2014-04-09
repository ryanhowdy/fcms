<?php
/**
 * UploadPhotoGallery 
 * 
 * Handles printing the form, and submitting of the form for the 'Basic'
 * standard photo gallery upload.
 * 
 * @package Upload
 * @subpackage Photo
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class PicasaUploadPhotoGallery extends UploadPhotoGallery
{
    private $albumFeed;
    private $newPhotoIds;

    /**
     * upload
     * 
     * @param array $formData 
     * 
     * @return boolean
     */
    public function upload ($formData)
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

        $newPhotoFilenames = array();

        foreach ($this->albumFeed as $photo)
        {
            $id = $photo->getGPhotoId()->text;

            if (!in_array($id, $this->formData['photos']))
            {
                continue;
            }

            $thumbs    = $photo->getMediaGroup()->getThumbnail();
            $thumbnail = $thumbs[0]->getUrl();
            $medium    = $thumbs[1]->getUrl();
            $full      = $this->usingFullSizePhotos ? $thumbs[2]->getUrl() : '';

            $extension = $this->uploadPhoto->getFileExtension($thumbnail);

            // Save photo to db
            $params = array(
                $this->newCategoryId,
                $this->fcmsUser->id
            );

            $sql = "INSERT INTO `fcms_gallery_photos`
                        (`date`, `category`, `user`)
                    VALUES 
                        (NOW(), ?, ?)";

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
            $sql = "UPDATE `fcms_gallery_photos` 
                    SET `filename` = ?
                    WHERE `id`     = ?";

            if (!$this->fcmsDatabase->update($sql, array($filename, $id)))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * validate 
     * 
     * @return boolean
     */
    public function validate ()
    {
        if (empty($this->formData['albums']))
        {
            $this->fcmsError->add(array(
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('You must choose a Picasa Web Album selected.').'</p>'
            ));

            return false;
        }

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

    /**
     * setFormData 
     * 
     * Saves all the data passed in from the form upload.
     * 
     * @param array $formData
     * 
     * @return void
     */
    public function setFormData ($formData)
    {
        $this->formData = $formData;

        $token   = getUserPicasaSessionToken($this->fcmsUser->id);
        $albumId = $formData['albums'];
        $user    = $formData['picasa_user'];

        $httpClient    = Zend_Gdata_AuthSub::getHttpClient($token);
        $picasaService = new Zend_Gdata_Photos($httpClient, "Google-DevelopersGuide-1.0");

        $thumbSizes = '150c,600';
        if ($this->usingFullSizePhotos)
        {
            $thumbSizes .= ',d';
        }

        try
        {
            $query = new Zend_Gdata_Photos_AlbumQuery();
            $query->setUser($user);
            $query->setAlbumId($albumId);
            $query->setParam('thumbsize', $thumbSizes);

            $albumFeed = $picasaService->getAlbumFeed($query);
        }
        catch (Zend_Gdata_App_Exception $e)
        {
            $this->fcmsError->add(array(
                'type'    => 'operation',
                'message' => T_('Could not get Picasa data.'),
                'error'   => $e->getMessage(),
                'file'    => __FILE__,
                'line'    => __LINE__,
             ));

            return false;
        }

        $this->albumFeed = $albumFeed;
    }

}
