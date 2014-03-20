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
class UploadPhotoGallery
{
    protected $fcmsError;
    protected $fcmsDatabase;
    protected $fcmsUser;
    protected $photoDestination;
    protected $uploadPhoto;

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

        // Load the editor, and do some validation
        $this->uploadPhoto->load($formData['photo']);

        if ($this->fcmsError->hasUserError())
        {
            return false;
        }

        $this->fileName  = $this->uploadPhoto->fileName;
        $this->extension = $this->uploadPhoto->extension;

        // Additional validation
        if (!$this->validate())
        {
            return false;
        }

        // Insert new category
        if (!$this->insertCategory())
        {
            return false;
        }

        // Insert photo into db
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
    }

    /**
     * validate 
     * 
     * Validates we have a valid category.
     * 
     * @return boolean
     */
    protected function validate ()
    {
        // Make sure we have a category
        if (strlen($this->formData['newCategory']) <= 0)
        {
            if (empty($this->formData['category']) && !ctype_digit($this->formData['category']))
            {
                $this->fcmsError->add(array(
                    'message' => T_('Upload Error'),
                    'details' => '<p>'.T_('You must choose a category first.').'</p>'
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
    protected function insertCategory ()
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
    protected function savePhoto ()
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

            $this->uploadPhoto->save($this->fileName);

            // Rotate
            if (isset($this->formData['rotate']))
            {
                if ($this->formData['rotate'] == 'left')
                {
                    $this->uploadPhoto->rotate(90);
                }
                elseif ($this->formData['rotate'] == 'right')
                {
                    $this->uploadPhoto->rotate(270);
                }
            }

            // Resize
            if ($resize)
            {
                $this->uploadPhoto->resize($width, $height, $resizeType);
            }

            // See if uploadPhoto had any errors
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
     * getLastPhotoId 
     * 
     * @return int
     */
    public function getLastPhotoId ()
    {
        return $this->newPhotoId;
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

    /**
     * getPhotoPaths 
     * 
     * @param string $fileName 
     * @param string $uid 
     * 
     * @return array
     */
    public function getPhotoPaths ($fileName, $uid)
    {
        $fileName = basename($fileName);
        $uid      = (int)$uid;

        // Link to the full sized photo if using full sized
        $sql = "SELECT `value` AS 'full_size_photos'
                FROM `fcms_config`
                WHERE `name` = 'full_size_photos'";

        $usingFullSizePhotos = false; 

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row !== false)
        {
            $usingFullSizePhotos = $row['full_size_photos'] == 1 ? true : false;
        }

        $photoPaths[0] = $this->photoDestination->absolutePath."member$uid/$fileName";
        $photoPaths[1] = $this->photoDestination->absolutePath."member$uid/$fileName";

        if ($usingFullSizePhotos)
        {
            // If you are using full sized but a photo was uploaded prior to that change, 
            // no full sized photo will be available, so don't link to it
            if (file_exists($this->photoDestination->absolutePath."member$uid/full_$fileName"))
            {
                $photo_path[1] = $this->photoDestination->absolutePath."member$uid/full_$fileName";
            }
        }

        return $photoPaths;
    }

    /**
     * getPhotoSource 
     * 
     * @param array  $data 
     * @param string $size 
     * 
     * @return string
     */
    public function getPhotoSource ($data, $size = 'thumbnail')
    {
        $prefix = '';
        if ($size == 'thumbnail')
        {
            $prefix = 'tb_';
        }
        elseif ($size == 'full')
        {
            $prefix = 'full_';
        }

        $path = $this->photoDestination->relativePath.'member'.(int)$data['user'].'/';

        $photoSrc = $path.$prefix.basename($data['filename']);

        // XXX: we may have uploaded this photo before we 
        // starting using full sized photos, so this full
        // sized photo may not exist.
        // Give them main size instead
        if ($size == 'full' && !file_exists($photoSrc))
        {
            $photoSrc = $path.basename($data['filename']);
        }

        return $photoSrc;
    }
}
