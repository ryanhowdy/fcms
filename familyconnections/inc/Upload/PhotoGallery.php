<?php
require_once INC.'Upload.php';

class Upload_PhotoGallery implements Upload
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;

    private $formType;
    private $handlerType;
    private $destinationType;

    private $formData;
    private $lastPhotoId;
    private $lastCategoryId;

    /**
     * __construct 
     * 
     * Creates form, handler, and destination objects.
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase 
     * @param object $fcmsUser 
     * @param string $type 
     * @param string $destination
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $type = 'basic')
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;

        // Save outside the root (Protected)
        if (defined('UPLOADS'))
        {
            require_once INC.'Upload/PhotoGallery/Destination/Protected.php';
            $this->destinationType = new ProtectedDestination($fcmsError, $fcmsDatabase, $fcmsUser);
        }
        // Save to Amazon S3
        elseif (defined('S3'))
        {
            require_once INC.'Upload/PhotoGallery/Destination/S3.php';
            $this->destinationType = new S3Destination($fcmsError, $fcmsDatabase, $fcmsUser);
        }
        // Save in uploads/photos/*
        else
        {
            require_once INC.'Upload/PhotoGallery/Destination.php';
            $this->destinationType = new Destination($fcmsError, $fcmsDatabase, $fcmsUser);
        }

        switch ($type)
        {
            case 'java':
                require_once INC.'Upload/PhotoGallery/Form/Java.php';
                require_once INC.'Upload/PhotoGallery/Handler/Java.php';

                $this->formType    = new JavaFormUpload($fcmsError, $fcmsDatabase, $fcmsUser);
                $this->handlerType = new JavaHandler($fcmsError, $fcmsDatabase, $fcmsUser, $this->destinationType);
                break;

            case 'instagram':
                require_once INC.'Upload/PhotoGallery/Form/Instagram.php';
                require_once INC.'Upload/PhotoGallery/Handler/Instagram.php';

                $this->formType    = new InstagramFormUpload($fcmsError, $fcmsDatabase, $fcmsUser);
                $this->handlerType = new InstagramHandler($fcmsError, $fcmsDatabase, $fcmsUser, $this->destinationType);
                break;

            case 'picasa':
                require_once INC.'Upload/PhotoGallery/Form/Picasa.php';
                require_once INC.'Upload/PhotoGallery/Handler/Picasa.php';

                $this->formType    = new PicasaFormUpload($fcmsError, $fcmsDatabase, $fcmsUser);
                $this->handlerType = new PicasaHandler($fcmsError, $fcmsDatabase, $fcmsUser, $this->destinationType);
                break;

            case 'basic':
            default:
                require_once INC.'Upload/PhotoGallery/Form.php';
                require_once INC.'Upload/PhotoGallery/Handler.php';

                $this->formType    = new FormUpload($fcmsError, $fcmsDatabase, $fcmsUser);
                $this->handlerType = new Handler($fcmsError, $fcmsDatabase, $fcmsUser, $this->destinationType);
                break;
        }
    }

    /**
     * displayForm 
     * 
     * Calls the appropriate Form Object's display form function.
     * 
     * @return void
     */
    public function displayForm ()
    {
        $this->formType->display();
        return;
    }

    /**
     * upload 
     * 
     * Saves data in the db about the photo (category, photo, etc.).
     * Then uses appropriate FileHandler Object to save photo.
     * 
     * If returns false, either $_SESSION['error_message'] or 
     * $fcmsError will have an error message set.
     * 
     * @return boolean
     */
    public function upload ($formData)
    {
        $this->formData = $formData;

        // Save form data in handler
        $this->handlerType->setFormData($formData);

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
        $this->handlerType->createDirectory();

        // Save photo to db and set $this->newPhotoId
        if (!$this->insertPhoto())
        {
            return false;
        }

        // Update filename to match newPhotoId
        if (!$this->updateFilename())
        {
            return false;
        }

        // Upload the photo
        if (!$this->handlerType->upload($this->lastPhotoId))
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
    private function validate ()
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

        // Do file handler specific validation
        return $this->handlerType->validate($this->formData);
    }

    /**
     * getLastPhotoId 
     * 
     * @return int
     */
    public function getLastPhotoId ()
    {
        return $this->lastPhotoId;
    }

    /**
     * getLastCategoryId 
     * 
     * @return int
     */
    public function getLastCategoryId ()
    {
        return $this->lastCategoryId;
    }

    /**
     * insertCategory 
     * 
     * @return void
     */
    private function insertCategory ()
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

            $this->lastCategoryId = $this->fcmsDatabase->insert($sql, $params);
            if ($this->lastCategoryId === false)
            {
                return false;
            }
        }
        // Set the supplied category id
        else
        {
            $this->lastCategoryId = $this->formData['category'];
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
    private function insertPhoto ()
    {
        $sql = "INSERT INTO `fcms_gallery_photos`
                    (`date`, `caption`, `category`, `user`)
                VALUES
                    (NOW(), ?, ?, ?)";

        $params = array(
            $this->formData['caption'],
            $this->lastCategoryId,
            $this->fcmsUser->id
        );

        $this->lastPhotoId = $this->fcmsDatabase->insert($sql, $params);
        if ($this->lastPhotoId === false)
        {
            return false;
        }

        return true;
    }

    /**
     * updateFilename
     * 
     * @return boolean
     */
    private function updateFilename ()
    {
        $ext = $this->handlerType->getExtension();

        $fileName = $this->lastPhotoId.'.'.$ext;

        // Update photo record
        $sql = "UPDATE `fcms_gallery_photos` 
                SET `filename` = ?
                WHERE `id` = ?";

        $params = array(
            $fileName,
            $this->lastPhotoId
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            return false;
        }

        $this->handlerType->setFileName($fileName);

        return true;
    }
}
