<?php
/**
 * AWS S3 Photo Gallery Destination 
 * 
 * Saves profile photos to amazon s3.
 * 
 * Amazon key and secret key must be defined in the
 * inc/config_inc.php file.
 * 
 * Example:
 * 
 *  define('S3',            strtotime('2014-03-25'));
 *  define('S3_KEY',        'AMAZON_KEY_GOES_HERE');
 *  define('S3_SECRET_KEY', 'AMAZON_SECRET_GOES_HERE');
 * 
 * @package Destination
 * @subpackage PhotoGallery
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class S3PhotoGalleryDestination extends PhotoGalleryDestination
{
    private $s3;
    private $bucketName;

    /**
     * __construct 
     * 
     * @param FCMS_Error $fcmsError 
     * @param User       $fcmsUser 
     * 
     * @return void
     */
    public function __construct (FCMS_Error $fcmsError, User $fcmsUser)
    {
        $this->fcmsError       = $fcmsError;
        $this->fcmsUser        = $fcmsUser;
        $this->absolutePath    = ROOT.'uploads/photos/s3tmp/';
        $this->destinationPath = $this->absolutePath;
        $this->bucketName      = $_SERVER["SERVER_NAME"].'-fcms-s3-photogallery';

        $this->s3 = new S3(S3_KEY, S3_SECRET_KEY);
    }

    /**
     * createDirectory 
     * 
     * Creates a new directory to save upload to, if needed.
     * 
     * @return boolean
     */
    public function createDirectory ()
    {
        // create a temporary directory to hold photo b4 moved to s3
        if (!file_exists($this->destinationPath))
        {
            if (!@mkdir($this->destinationPath))
            {
                $this->fcmsError->add(array(
                    'message' => T_('Upload Destination Error'),
                    'details' => '<p>'.T_('Could not create new temporary directory.').'</p>'
                ));

                return false;
            }
        }

        $buckets = $this->s3->listBuckets();

        $found = array_search($this->bucketName, $buckets);
        if ($found === false)
        {
            if (!$this->s3->putBucket($this->bucketName))
            {
                $this->fcmsError->add(array(
                    'message' => T_('Upload Destination Error'),
                    'details' => '<p>'.T_('Could not create new AWS S3 bucket.').'</p>'
                ));

                return false;
            }
        }

        return true;
    }

    /**
     * writeImage 
     * 
     * Calls parent writeImage, then moves the file to S3.
     * 
     * @param image resource $source 
     * @param string         $fileName 
     * @param string         $extension 
     * 
     * @return boolean
     */
    public function writeImage ($source, $fileName, $extension)
    {
        // save tmp file
        parent::writeImage($source, $fileName, $extension);

        // save photo to s3
        if (!$this->movePhotoFromTempToS3($fileName))
        {
            return false;
        }

        return true;
    }

    /**
     * copy
     * 
     * Calls parent copy, then moves the file to S3.
     * 
     * @param string $photo
     * @param string $fileName 
     * 
     * @return void
     */
    public function copy ($photo, $fileName)
    {
        // save tmp file
        parent::copy($photo, $fileName);

        // save photo to s3
        if (!$this->movePhotoFromTempToS3($fileName))
        {
            return false;
        }

        return true;
    }

    /**
     * savePhotoFromSource 
     * 
     * @param string $source
     * @param string $filename
     * 
     * @return void
     */
    public function savePhotoFromSource ($source, $filename)
    {
        parent::savePhotoFromSource($source, $filename);

        // save photo to s3
        if (!$this->movePhotoFromTempToS3($filename))
        {
            return false;
        }

        return true;
    }

    /**
     * movePhotoFromTempToS3 
     * 
     * @param string $fileName 
     * 
     * @return boolean
     */
    private function movePhotoFromTempToS3 ($fileName)
    {
        // save photo to s3
        if (!$this->s3->putObjectFile($this->destinationPath.$fileName, $this->bucketName, $fileName, S3::ACL_AUTHENTICATED_READ))
        {
            return false;
        }

        // clean up tmp file
        if (file_exists($this->destinationPath.$fileName))
        {
            unlink($this->destinationPath.$fileName);
        }

        return true;
    }

    /**
     * deleteFile 
     * 
     * Removes a photo from the s3 bucket.
     * 
     * @param string $fileName 
     * 
     * @return void
     */
    public function deleteFile ($fileName)
    {
        $this->s3->deleteObject($this->bucketName, $fileName);
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

        $mediumPath = $this->s3->getAuthenticatedURL($this->bucketName, $fileName, 3600);

        $photoPaths = array($mediumPath, $mediumPath);

        if (usingFullSizePhotos())
        {
            $photoPaths[1] = $this->s3->getAuthenticatedURL($this->bucketName, 'full_'.$fileName, 3600);
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

        $uri = $prefix.basename($data['filename']);

        return $this->s3->getAuthenticatedURL($this->bucketName, $uri, 3600);
    }

    /**
     * getPhotoFileSize 
     * 
     * @param string $file 
     * 
     * @return string
     */
    public function getPhotoFileSize ($file)
    {
        return T_('Unknown');
    }
}
