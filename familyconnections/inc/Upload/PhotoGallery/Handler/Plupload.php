<?php

require_once INC.'Upload/PhotoGallery/Handler.php';

/**
 * PluploadHandler
 * 
 * @package Upload_PhotoGallery
 * @subpackage Handler
 * @copyright 2013 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class PluploadHandler extends Handler
{
    /**
     * upload
     * 
     * NOTE: 2-3 calls will be made to this script, one for each type
     * (thumb, main, full). So some things only should occur when
     * when the 'main' photo is being uploaded.  For example:
     * insertCategory() and insertPhoto().
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

        // Insert new category
        if (!$this->insertCategory())
        {
            return false;
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
        if (!isset($this->validMimeTypes[ $this->formData['type'] ]) || !isset($this->validExtensions[$this->extension]))
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

        $this->fileName = cleanFilename($formData['name']);
        $this->setExtension();

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
    public function insertCategory ()
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
    public function insertPhoto ()
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
    function savePhoto ()
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
        $ret = $this->destinationType->copy($this->formData['tmp_name'], $this->fileName);
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
