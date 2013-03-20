<?php
require_once INC.'Upload.php';

class Upload_PhotoGallery implements Upload
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;

    private $photo;
    private $newPhotoId;

    private $formType;
    private $handlerType;
    private $destinationType;

    // Form Data
    private $newCategory;
    private $category;
    private $caption;

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
    public function upload ($photo, $formData)
    {
        $this->photo       = $photo;
        $this->newCategory = isset($formData['newCategory']) ? strip_tags($formData['newCategory']) : null; 
        $this->category    = isset($formData['category'])    ? $formData['category']                : null; 
        $this->caption     = isset($formData['caption'])     ? strip_tags($formData['caption'])     : null; 

        // Save photo data in handler
        $this->handlerType->setPhotoData($photo);

        // Validate
        if (!$this->validate($formData))
        {
            return false;
        }

        // Set rotate options in handler
        $this->handlerType->setRotate($formData['rotate']);

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
        if (!$this->handlerType->upload($this->newPhotoId))
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
        if (strlen($this->newCategory) <= 0)
        {
            if (empty($this->category) && !ctype_digit($this->category))
            {
                $this->fcmsError->add(array(
                    'message' => T_('Upload Error'),
                    'details' => '<p>'.T_('You must choose a category first.').'</p>'
                ));

                return false;
            }
        }

        // Catch photos that are too large
        if ($this->photo['error'] == 1)
        {
            $max  = ini_get('upload_max_filesize');
            $link = 'index.php?action=upload&amp;advanced=1';

            $this->fcmsError->add(array(
                'message' => T_('Upload Error'),
                'details' => '<p>'.sprintf(T_('Your photo exceeds the maximum size allowed by your PHP settings [%s].'), $max).'</p>'
                            .'<p>'.sprintf(T_('Would you like to use the <a href="%s">Advanced Photo Uploader</a> instead?.'), $link).'</p>'
            ));

            return false;
        }

        // Make sure we have a photo
        if ($this->photo['error'] == 4)
        {
            $this->fcmsError->add(array(
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('You must choose a photo first.').'</p>'
            ));

            return false;
        }

        // Another check that we have a photo
        if ($this->photo['size'] <= 0)
        {
            $this->fcmsError->add(array(
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('Photo is corrupt or missing.').'</p>'
            ));

            return false;
        }

        // Do file handler specific validation
        return $this->handlerType->validate();
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
     * insertCategory 
     * 
     * @return void
     */
    private function insertCategory ()
    {
        // Create a new category
        if (strlen($this->newCategory) > 0)
        {
            $sql = "INSERT INTO `fcms_category`
                        (`name`, `type`, `user`) 
                    VALUES
                        (?, 'gallery', ?)";

            $params = array(
                $this->newCategory,
                $this->fcmsUser->id
            );

            $this->category = $this->fcmsDatabase->insert($sql, $params);
            if ($this->category === false)
            {
                return false;
            }
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
            $this->caption,
            $this->category,
            $this->fcmsUser->id
        );

        $this->newPhotoId = $this->fcmsDatabase->insert($sql, $params);
        if ($this->newPhotoId === false)
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

        $fileName = $this->newPhotoId.'.'.$ext;

        // Update photo record
        $sql = "UPDATE `fcms_gallery_photos` 
                SET `filename` = ?
                WHERE `id` = ?";

        $params = array(
            $fileName,
            $this->newPhotoId
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            return false;
        }

        $this->handlerType->setFileName($fileName);

        return true;
    }
}
