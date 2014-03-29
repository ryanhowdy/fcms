<?php
/**
 * Amazon S3 Profile Destination 
 * 
 * Saves profile photos to amazon s3.
 * 
 * Amazon key and secret key must be defined in the
 * inc/config_inc.php file.
 * 
 * @package Destination
 * @subpackage Profile
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class S3ProfileDestination extends ProfileDestination
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
logError("S3.php - construct()");
        $this->fcmsError       = $fcmsError;
        $this->fcmsUser        = $fcmsUser;
        $this->absolutePath    = ROOT.'uploads/avatar/s3tmp/';
        $this->destinationPath = $this->absolutePath;
        $this->bucketName      = 'fcms-s3-profile';

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
logError("S3.php - createDirectory()");
        // create a temporary directory to hold photo b4 moved to s3
        if (!file_exists($this->destinationPath))
        {
logError("file does not exist [".$this->destinationPath."]");
            if (!@mkdir($this->destinationPath))
            {
logError("could not mkdir [".$this->destinationPath."]");
                $this->fcmsError->add(array(
                    'message' => T_('Upload Destination Error'),
                    'details' => '<p>'.T_('Could not create new temporary directory.').'</p>'
                ));

                return false;
            }
logError("mkdir all good [".$this->destinationPath."]");
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
logError("S3.php - writeImage()");
        // save tmp file
        parent::writeImage($source, $fileName, $extension);

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
        $sql = "SELECT `value` AS 'full_size_photos'
                FROM `fcms_config`
                WHERE `name` = 'full_size_photos'";

        $usingFullSizePhotos = false; 

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row !== false)
        {
            $usingFullSizePhotos = $row['full_size_photos'] == 1 ? true : false;
        }

        $mediumPath = $this->s3->getAuthenticatedURL($this->bucketName, $fileName, 3600);

        $photoPaths = array($mediumPath, $mediumPath);

        if ($usingFullSizePhotos)
        {
            $photoPaths[1] = $this->s3->getAuthenticatedURL($this->bucketName, 'full_'.$fileName, 3600);
        }

        return $photoPaths;
    }

    /**
     * getPhotoSource 
     * 
     * @param string $fileName
     * 
     * @return string
     */
    public function getPhotoSource ($fileName)
    {
        $uri = basename($fileName);

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
