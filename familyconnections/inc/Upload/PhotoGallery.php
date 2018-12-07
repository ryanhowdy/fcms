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
class UploadPhotoGallery
{
    protected $fcmsError;
    protected $fcmsDatabase;
    protected $fcmsUser;
    protected $destination;
    protected $uploadPhoto;

    protected $usingFullSizePhotos;
    protected $formData;
    protected $newCategoryId;
    protected $newPhotoId;
    protected $fileName;
    protected $extension;

    private $thumbMaxWidth = 150;
    private $thumbMaxHeight = 150;
    private $mainMaxWidth = 600;
    private $mainMaxHeight = 600;

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

        // Load the editor, and do some validation
        $this->uploadPhoto->load($this->formData['photo']);

        if ($this->fcmsError->hasUserError())
        {
            return false;
        }

        $this->fileName = $this->uploadPhoto->fileName;
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
    }

    /**
     * validate.
     *
     * Validates we have a valid category.
     *
     * @return bool
     */
    protected function validate()
    {
        // Make sure we have a category
        if (strlen($this->formData['newCategory']) <= 0)
        {
            if (empty($this->formData['category']) && !ctype_digit($this->formData['category']))
            {
                $this->fcmsError->add([
                    'message' => T_('Upload Error'),
                    'details' => '<p>'.T_('You must choose a category first.').'</p>',
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * insertCategory.
     *
     * @return bool
     */
    protected function insertCategory()
    {
        // Create a new category
        if (strlen($this->formData['newCategory']) > 0)
        {
            $sql = "INSERT INTO `fcms_category`
                        (`name`, `type`, `user`) 
                    VALUES
                        (?, 'gallery', ?)";

            $params = [
                $this->formData['newCategory'],
                $this->fcmsUser->id,
            ];

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
     * insertPhoto.
     *
     * Inserts new photo record in db, and save photo id.
     *
     * @return bool
     */
    protected function insertPhoto()
    {
        $sql = 'INSERT INTO `fcms_gallery_photos`
                    (`date`, `caption`, `category`, `user`)
                VALUES
                    (NOW(), ?, ?, ?)';

        $params = [
            $this->formData['caption'],
            $this->newCategoryId,
            $this->fcmsUser->id,
        ];

        $this->newPhotoId = $this->fcmsDatabase->insert($sql, $params);
        if ($this->newPhotoId === false)
        {
            return false;
        }

        $this->fileName = $this->newPhotoId.'.'.$this->extension;

        // Update photo record
        $sql = 'UPDATE `fcms_gallery_photos` 
                SET `filename` = ?
                WHERE `id` = ?';

        $params = [
            $this->fileName,
            $this->newPhotoId,
        ];

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            return false;
        }

        return true;
    }

    /**
     * savePhoto.
     *
     * @return bool
     */
    protected function savePhoto()
    {
        if (!$this->destination->createDirectory())
        {
            return false;
        }

        // Setup the array of photos that need uploaded
        $uploadPhotos = [
            'main'  => [
                'resize'     => true,
                'resizeType' => null,
                'prefix'     => '',
                'width'      => $this->mainMaxWidth,
                'height'     => $this->mainMaxHeight,
            ],
            'thumb' => [
                'resize'     => true,
                'resizeType' => 'square',
                'prefix'     => 'tb_',
                'width'      => $this->thumbMaxWidth,
                'height'     => $this->thumbMaxHeight,
            ],
        ];

        if ($this->usingFullSizePhotos)
        {
            $uploadPhotos['full'] = [
                'resize'     => false,
                'resizeType' => null,
                'prefix'     => 'full_',
                'width'      => null,
                'height'     => null,
            ];
        }

        // Loop through each photo that needs saved
        foreach ($uploadPhotos as $key => $value)
        {
            $resize = $uploadPhotos[$key]['resize'];
            $resizeType = $uploadPhotos[$key]['resizeType'];
            $prefix = $uploadPhotos[$key]['prefix'];
            $width = $uploadPhotos[$key]['width'];
            $height = $uploadPhotos[$key]['height'];

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
                $sql = 'DELETE FROM `fcms_gallery_photos` 
                        WHERE `id` = ?';
                $this->fcmsDatabase->delete($sql, $this->newPhotoId);

                return false;
            }
        }

        return true;
    }

    /**
     * getLastPhotoId.
     *
     * @return int
     */
    public function getLastPhotoId()
    {
        return $this->newPhotoId;
    }

    /**
     * getLastCategoryId.
     *
     * @return int
     */
    public function getLastCategoryId()
    {
        return $this->newCategoryId;
    }
}
