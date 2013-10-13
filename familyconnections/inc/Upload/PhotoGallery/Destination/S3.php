<?php

require_once INC.'Upload/PhotoGallery/Destination.php';
require_once THIRDPARTY.'s3/S3.php';

/**
 * Destination 
 * 
 * Saves uploads to a local directory outside of www that is
 * is defined by a path called UPLOADS in inc/config_inc.php.
 * 
 * @package Upload_PhotoGallery
 * @subpackage Destination
 * @copyright 2013 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class S3Destination extends Destination
{
    public $destinationPath;

    private $s3;

    /**
     * __construct 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase 
     * @param object $fcmsUser 
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;

        $this->destinationPath = ROOT.'uploads/photos/s3temp/';
        // TODO
        // need to come up with a unique name for each site
        $this->bucketName      = 'fcms-s3-test';
        $this->s3              = new S3(S3_KEY, S3_SECRET_KEY);
    }

    /**
     * createDirectory 
     * 
     * Creates a new directory to save upload to, if needed.
     * 
     * @return void
     */
    public function createDirectory ()
    {
        // create a temporary directory to hold photo b4 moved to s3
        if (!file_exists($this->destinationPath))
        {
            mkdir($this->destinationPath) or trigger_error("Could not create path: ".$this->destinationPath);
        }

        $buckets = $this->s3->listBuckets();

        $found = array_search($this->bucketName, $buckets);
        if ($found === false)
        {
            if (!$this->s3->putBucket($this->bucketName))
            {
                die(T_('Could not create new AWS S3 bucket.'));
            }
        }
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
     * @return void
     */
    public function writeImage ($source, $fileName, $extension)
    {
        parent::writeImage($source, $fileName, $extension);

        if (!$this->s3->putObjectFile($this->destinationPath.$fileName, $this->bucketName, $fileName, S3::ACL_AUTHENTICATED_READ))
        {
            return false;
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
        if (!$this->s3->deleteObject($this->bucketName, $fileName))
        {
            die(T_('Could not remove photo from AWS S3.'));
        }
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
     * @param array  $data 
     * @param string $size 
     * 
     * @return string
     */
    public function getPhotoSource ($data, $size = 'thumbnail')
    {
        $filename = basename($data['filename']);

        $prefix = '';

        if ($size == 'thumbnail')
        {
            $prefix = 'tb_';
        }
        elseif ($size == 'full')
        {
            $prefix = 'full_';
        }

        $uri = $prefix.$filename;

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
