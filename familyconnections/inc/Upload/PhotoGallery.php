<?php
/**
 * Upload_PhotoGallery 
 * 
 * @package Family Connections
 * @copyright 2013 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class Upload_PhotoGallery
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;

    private $formType;
    private $handlerType;
    private $destinationType;

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
        // TODO 
        //
        //// Save to Amazon S3
        //elseif (defined('S3'))
        //{
        //    require_once INC.'Upload/PhotoGallery/Destination/S3.php';
        //    $this->destinationType = new S3Destination($fcmsError, $fcmsDatabase, $fcmsUser);
        //}
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
     * Calls the appropriate Handler Object's upload function.
     * 
     * @return boolean
     */
    public function upload ($formData)
    {
        if (!$this->handlerType->upload($formData))
        {
            return false;
        }

        return true;
    }

    /**
     * validate 
     * 
     * Validates we have a valid category (all photo gallery uploads should have this)
     * and then calls the appropriate Handler Object's validate function.
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
        return $this->handlerType->getLastPhotoId();
    }

    /**
     * getLastPhotoIds
     * 
     * @return array
     */
    public function getLastPhotoIds ()
    {
        return $this->handlerType->getLastPhotoIds();
    }

    /**
     * getLastCategoryId 
     * 
     * @return int
     */
    public function getLastCategoryId ()
    {
        return $this->handlerType->getLastCategoryId();
    }
}
