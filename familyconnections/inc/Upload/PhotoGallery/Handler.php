<?php

require_once INC.'PhotoEditor.php';

/**
 * Handler 
 * 
 * @package Family Connections
 * @copyright 2013 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class Handler
{
    protected $fcmsError;
    protected $fcmsDatabase;
    protected $fcmsUser;
    protected $destinationType;
    protected $usingFullSizePhotos;
    protected $formData;
    protected $newCategoryId;
    protected $newPhotoId;
    protected $fileName;
    protected $extension;

    private $thumbMaxWidth  = 150;
    private $thumbMaxHeight = 150;
    private $mainMaxWidth   = 600;
    private $mainMaxHeight  = 600;

    protected $validMimeTypes = array(
        'image/pjpeg'   => 1,
        'image/jpeg'    => 1, 
        'image/gif'     => 1, 
        'image/bmp'     => 1, 
        'image/x-png'   => 1, 
        'image/png'     => 1
    );
    protected $validExtensions = array(
        'jpeg'  => 1,
        'jpg'   => 1,
        'gif'   => 1,
        'bmp'   => 1,
        'png'   => 1
    );

    /**
     * __construct 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase 
     * @param object $fcmsUser 
     * @param object $destinationType 
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $destinationType)
    {
        $this->fcmsError       = $fcmsError;
        $this->fcmsDatabase    = $fcmsDatabase;
        $this->fcmsUser        = $fcmsUser;
        $this->destinationType = $destinationType;

        $this->photoEditor = new PhotoEditor($fcmsError, $fcmsDatabase, $fcmsUser, $destinationType);

        $this->usingFullSizePhotos = usingFullSizePhotos();
    }

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

        // Load the editor, and do some validation
        $this->photoEditor->load($formData['photo']);

        if ($this->fcmsError->hasUserError())
        {
            return false;
        }

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
        // Make sure we got a category

        if (!isset($this->formData['newCategory']))
        {
            if (!isset($this->formData['category']))
            {
                $this->fcmsError->add(array(
                    'message' => T_('Upload Error'),
                    'details' => '<p>'.T_('You must choose an existing category, or create a new category.').'</p>'
                ));

                return false;
            }
        }

        return true;
    }

    /**
     * insertCategory 
     * 
     * @return void
     */
    public function insertCategory ()
    {
        // Create a new category
        if (strlen($this->formData['newCategory']) > 0)
        {
            $sql = "INSERT INTO `fcms_category`
                        (`name`, `type`, `user`) 
                    VALUES
                        (?, 'gallery', ?)";

            $params = array(
                $this->formData['newCategory'],
                $this->fcmsUser->id
            );

            $this->newCategoryId = $this->fcmsDatabase->insert($sql, $params);
            if ($this->newCategoryId === false)
            {
                return false;
            }
        }
        // Set the supplied category id
        else
        {
            $this->newCategoryId = $this->formData['category'];
        }

        return true;
    }

    /**
     * insertPhoto
     * 
     * Inserts new photo record in db, and save photo id.
     * 
     * @return boolean
     */
    protected function insertPhoto ()
    {
        $sql = "INSERT INTO `fcms_gallery_photos`
                    (`date`, `caption`, `category`, `user`)
                VALUES
                    (NOW(), ?, ?, ?)";

        $params = array(
            $this->formData['caption'],
            $this->newCategoryId,
            $this->fcmsUser->id
        );

        $this->newPhotoId = $this->fcmsDatabase->insert($sql, $params);
        if ($this->newPhotoId === false)
        {
            return false;
        }

        $this->fileName = $this->newPhotoId.'.'.$this->extension;

        // Update photo record
        $sql = "UPDATE `fcms_gallery_photos` 
                SET `filename` = ?
                WHERE `id` = ?";

        $params = array(
            $this->fileName,
            $this->newPhotoId
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            return false;
        }

        return true;
    }

    /**
     * savePhoto 
     * 
     * @return boolean
     */
    function savePhoto ()
    {
        // Setup the array of photos that need uploaded
        $uploadPhotos = array(
            'main'  => array(
                'resize'     => true,
                'resizeType' => null,
                'prefix'     => '',
                'width'      => $this->mainMaxWidth,
                'height'     => $this->mainMaxHeight
            ),
            'thumb' => array(
                'resize'     => true,
                'resizeType' => 'square',
                'prefix'     => 'tb_',
                'width'      => $this->thumbMaxWidth,
                'height'     => $this->thumbMaxHeight
            ),
        );

        if ($this->usingFullSizePhotos)
        {
            $uploadPhotos['full'] = array(
                'resize'     => false,
                'resizeType' => null,
                'prefix'     => 'full_',
                'width'      => null,
                'height'     => null
            );
        }

        // Loop through each photo that needs saved
        foreach ($uploadPhotos as $key => $value)
        {
            $resize     = $uploadPhotos[$key]['resize'];
            $resizeType = $uploadPhotos[$key]['resizeType'];
            $prefix     = $uploadPhotos[$key]['prefix'];
            $width      = $uploadPhotos[$key]['width'];
            $height     = $uploadPhotos[$key]['height'];

            // Reset the filename for each photo
            $this->fileName = $prefix.$this->newPhotoId.'.'.$this->extension;

            $this->photoEditor->save($this->fileName);

            // Rotate
            if ($this->formData['rotate'] == 'left')
            {
                $this->photoEditor->rotate(90);
            }
            elseif ($this->formData['rotate'] == 'right')
            {
                $this->photoEditor->rotate(270);
            }

            // Resize
            if ($resize)
            {
                $this->photoEditor->resize($width, $height, $resizeType);
            }

            // See if photoEditor had any errors
            if ($this->fcmsError->hasUserError())
            {
                // Try to delete from db
                $sql = "DELETE FROM `fcms_gallery_photos` 
                        WHERE `id` = ?";
                $this->fcmsDatabase->delete($sql, $this->newPhotoId);

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
    public function setFormData ($formData)
    {
        $this->formData = $formData;

        $this->fileName = cleanFilename($formData['photo']['name']);
        $this->setExtension();
    }

    /**
     * getFileExtension 
     * 
     * @param string $file 
     * 
     * @return string
     */
    public function getFileExtension ($file)
    {
        $ext = '';
        $arr = explode('.', $file);

        // If arr doesn't have atleast 2 elements, then the file didn't have an extension
        if (count($arr) >= 2)
        {
            $ext = end($arr);
            $ext = strtolower($ext);
        }

        return $ext;
    }

    /**
     * setExtension 
     * 
     * @return void
     */
    public function setExtension ()
    {
        $this->extension = $this->getFileExtension($this->fileName);
    }

    /**
     * getLastPhotoId 
     * 
     * @return int
     */
    public function getLastPhotoId ()
    {
        return $this->newPhotoId;
    }

    /**
     * getLastPhotoIds
     * 
     * @return array
     */
    public function getLastPhotoIds ()
    {
        return array( $this->newPhotoId );
    }

    /**
     * getLastCategoryId 
     * 
     * @return int
     */
    public function getLastCategoryId ()
    {
        return $this->newCategoryId;
    }

}
