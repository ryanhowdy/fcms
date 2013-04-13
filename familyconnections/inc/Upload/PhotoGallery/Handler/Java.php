<?php

require_once INC.'Upload/PhotoGallery/Handler.php';

/**
 * JavaHandler 
 * 
 * @package Upload_PhotoGallery
 * @subpackage Handler
 * @copyright 2013 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class JavaHandler extends Handler
{
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

        // If mass_photos_category is set, we already created the category
        // just set the newCategoryId and move on
        if (isset($_SESSION['mass_photos_category']))
        {
            $this->newCategoryId = $_SESSION['mass_photos_category'];
        }
        else
        {
            // Insert new category
            if (!$this->insertCategory())
            {
                return false;
            }

            // Set var so we don't create a category with each file in the upload
            $_SESSION['mass_photos_category'] = $this->newCategoryId;
        }

        // Create new directory
        $this->destinationType->createDirectory();

        // Save photo to db
        if (!$this->insertPhoto())
        {
            return false;
        }

        // Save file, rotate and resize
        if (!$this->savePhoto())
        {
            return false;
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
        // Validate mimetype/extension for real photo
        if (!isset($this->validMimeTypes[ $this->formData['main']['type'] ]) || !isset($this->validExtensions[$this->extension]))
        {
            $this->fcmsError->add(array(
                'message' => T_('Upload Error'),
                'details' => '<p>'.sprintf(T_('Photo [%s] is not a supported photo type.  Photos must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $this->fileName).'</p>'
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

        $this->fileName = cleanFilename($formData['main']['name']);
        $this->setExtension();

        // Save an empty caption for now, user will edit it later
        $this->formData['caption'] = '';
    }

    /**
     * savePhoto 
     * 
     * @return boolean
     */
    function savePhoto ()
    {
        $photoTypes = array(
            'main'  => '',
            'thumb' => 'tb_'
        );

        if ($this->usingFullSizePhotos)
        {
            $photoTypes['full'] = 'full_';
        }

        foreach ($photoTypes as $type => $prefix)
        {
            // Reset the filename for each photo
            $this->fileName = $prefix.$this->newPhotoId.'.'.$this->extension;

            // Copy temp photo to destination
            $this->destinationType->copy($this->formData[$type]['tmp_name'], $this->fileName);

            // Set up the session vars to send to edit page
            // only on main, so we don't get both full size and thumbnail
            if ($type == 'main')
            {
                $_SESSION['photos'][] = array(
                    'id'       => $this->newPhotoId,
                    'filename' => $this->fileName,
                    'category' => $this->newCategoryId
                );
            }
        }

        return true;
    }

}
