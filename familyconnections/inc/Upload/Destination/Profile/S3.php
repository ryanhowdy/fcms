<?php
/**
 * Amazon S3 Profile Destination.
 *
 * Saves profile photos to amazon s3.
 *
 * Amazon key and secret key must be defined in the
 * inc/config_inc.php file.
 *
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class S3ProfileDestination extends ProfileDestination
{
    private $s3;
    private $bucketName;

    /**
     * __construct.
     *
     * @param FCMS_Error $fcmsError
     * @param User       $fcmsUser
     *
     * @return void
     */
    public function __construct(FCMS_Error $fcmsError, User $fcmsUser)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsUser = $fcmsUser;
        $this->absolutePath = ROOT.'uploads/avatar/s3tmp/';
        $this->destinationPath = $this->absolutePath;
        $this->bucketName = $_SERVER['SERVER_NAME'].'-fcms-s3-profile';

        $this->s3 = new S3(S3_KEY, S3_SECRET_KEY);
    }

    /**
     * createDirectory.
     *
     * Creates a new directory to save upload to, if needed.
     *
     * @return bool
     */
    public function createDirectory()
    {
        // create a temporary directory to hold photo b4 moved to s3
        if (!file_exists($this->destinationPath)) {
            if (!@mkdir($this->destinationPath)) {
                $this->fcmsError->add([
                    'message' => T_('Upload Destination Error'),
                    'details' => '<p>'.T_('Could not create new temporary directory.').'</p>',
                ]);

                return false;
            }
        }

        $buckets = $this->s3->listBuckets();

        $found = array_search($this->bucketName, $buckets);
        if ($found === false) {
            if (!$this->s3->putBucket($this->bucketName)) {
                $this->fcmsError->add([
                    'message' => T_('Upload Destination Error'),
                    'details' => '<p>'.T_('Could not create new AWS S3 bucket.').'</p>',
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * writeImage.
     *
     * Calls parent writeImage, then moves the file to S3.
     *
     * @param image resource $source
     * @param string         $fileName
     * @param string         $extension
     *
     * @return bool
     */
    public function writeImage($source, $fileName, $extension)
    {
        // save tmp file
        parent::writeImage($source, $fileName, $extension);

        // save photo to s3
        if (!$this->s3->putObjectFile($this->destinationPath.$fileName, $this->bucketName, $fileName, S3::ACL_AUTHENTICATED_READ)) {
            return false;
        }

        // clean up tmp file
        if (file_exists($this->destinationPath.$fileName)) {
            unlink($this->destinationPath.$fileName);
        }

        return true;
    }

    /**
     * deleteFile.
     *
     * Removes a photo from the s3 bucket.
     *
     * @param string $fileName
     *
     * @return void
     */
    public function deleteFile($fileName)
    {
        $this->s3->deleteObject($this->bucketName, $fileName);
    }

    /**
     * getPhotoSource.
     *
     * @param string $fileName
     *
     * @return string
     */
    public function getPhotoSource($fileName)
    {
        $uri = basename($fileName);

        return $this->s3->getAuthenticatedURL($this->bucketName, $uri, 3600);
    }
}
