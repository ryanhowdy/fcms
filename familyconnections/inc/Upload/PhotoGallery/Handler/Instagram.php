<?php

require_once INC.'Upload/PhotoGallery/Handler.php';

/**
 * InstagramHandler 
 * 
 * @package Upload_PhotoGallery
 * @subpackage Handler
 * @copyright 2013 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class InstagramHandler extends Handler
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
     * validate 
     * 
     * @return boolean
     */
    public function validate ()
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

        $this->newCategoryId  = getUserInstagramCategory($this->fcmsUser->id);
    }

}
