<?php
/**
 * Java Photo Gallery
 * 
 * @package Upload
 * @subpackage PhotoGallery
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class JavaUploadPhotoGallery extends UploadPhotoGallery
{
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

        $this->formData['photo'] = $formData['main'];

        // Save an empty caption for now, user will edit it later
        $this->formData['caption'] = '';
    }

    /**
     * insertCategory 
     * 
     * @return boolean
     */
    protected function insertCategory ()
    {
        // If mass_photos_category is set, we already created the category
        // just set the newCategoryId and move on
        if (isset($_SESSION['mass_photos_category']))
        {
            $this->newCategoryId = $_SESSION['mass_photos_category'];
        }
        else
        {
            // Insert new category
            if (!parent::insertCategory())
            {
                return false;
            }

            // Set var so we don't create a category with each file in the upload
            $_SESSION['mass_photos_category'] = $this->newCategoryId;
        }

        return true;
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
            $this->photoDestination->copy($this->formData[$type]['tmp_name'], $this->fileName);

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
