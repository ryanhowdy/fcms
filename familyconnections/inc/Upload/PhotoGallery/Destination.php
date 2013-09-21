<?php

/**
 * Destination 
 * 
 * Saves uploads to the local uploads/photos/memberXX/* directory.
 * 
 * @package Upload_PhotoGallery
 * @subpackage Destination
 * @copyright 2013 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class Destination
{
    public $destinationPath;

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

        $this->destinationPath = ROOT.'uploads/photos/member'.$this->fcmsUser->id.'/';
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
        if (!file_exists($this->destinationPath))
        {
            mkdir($this->destinationPath) or trigger_error("Could not create path: ".$this->destinationPath);
        }
    }

    /**
     * copy 
     * 
     * @param string $photo
     * @param string $fileName 
     * 
     * @return void
     */
    public function copy ($photo, $fileName)
    {
        copy($photo, $this->destinationPath.$fileName);
    }

    /**
     * getImageSize 
     * 
     * @param string $fileName 
     * 
     * @return void
     */
    public function getImageSize ($fileName)
    {
        return GetImageSize($this->destinationPath.$fileName);
    }

    /**
     * deleteFile 
     * 
     * @param string $fileName 
     * 
     * @return void
     */
    public function deleteFile ($fileName)
    {
        if (file_exists($this->destinationPath.$fileName))
        {
            unlink($this->destinationPath.$fileName);
        }
    }

    /**
     * createImageIdentifier 
     * 
     * Creates an image identifer representing and image obtained from a filename.
     * 
     * @return image identifier
     */
    public function createImageIdentifier ($fileName, $extension)
    {
        switch ($extension)
        {
            case 'jpeg':
            case 'jpg':

                $identifier = @imagecreatefromjpeg($this->destinationPath.$fileName);

                break;

            case 'gif':

                // Handle transparent gifs
                $fp     = fopen($this->destinationPath.$fileName, 'rb');
                $result = fread($fp, 13);

                $color_flag = ord(substr($result, 10, 1)) >> 7;
                $background = ord(substr($result, 11));

                if ($color_flag)
                {
                    $size = ($background + 1) * 3;
                    $result = fread($fp, $size);

                    $this->transparent_red      = ord(substr($result, $background * 3,     1));
                    $this->transparent_green    = ord(substr($result, $background * 3 + 1, 1));
                    $this->transparent_blue     = ord(substr($result, $background * 3 + 2, 1));
                }

                fclose($fp);

                $identifier = @imagecreatefromgif($this->destinationPath.$fileName);

                break;

            case 'wbmp':
            case 'bmp':

                $identifier = @imagecreatefrombmp($this->destinationPath.$fileName);

                break;

            case 'png':

                $identifier = @imagecreatefrompng($this->destinationPath.$fileName);

                break;
        }

        return $identifier;
    }

    /**
     * writeImage 
     * 
     * Takes an image resource (from imagerotate or imagecreatefrom*) 
     * and creates a new image.
     * 
     * @param $source image resource
     * 
     * @return boolean
     */
    public function writeImage ($source, $fileName, $extension)
    {
        switch($extension)
        {
            case 'jpeg':
            case 'jpg':

                if (!function_exists('imagejpeg'))
                {
                    $this->fcmsError->add(array(
                        'message' => T_('Upload Error'),
                        'details' => '<p>'.T_('GD Library is either not installed or does not support this file type.').'</p>'
                    ));
                    
                    return false;
                }

                if (@!imagejpeg($source, $this->destinationPath.$fileName))
                {
                    $this->fcmsError->add(array(
                        'message' => T_('Upload Error'),
                        'details' => '<p>'.T_('Could not write file, check folder permissions.').'</p>'
                    ));

                    return false;
                }

                break;

            case 'gif':

                if (!function_exists('imagegif'))
                {
                    $this->fcmsError->add(array(
                        'message' => T_('Upload Error'),
                        'details' => '<p>'.T_('GD Library is either not installed or does not support this file type.').'</p>'
                    ));

                    return false;
                }

                if (@!imagegif($source, $this->destinationPath.$fileName))
                {
                    $this->fcmsError->add(array(
                        'message' => T_('Upload Error'),
                        'details' => '<p>'.T_('Could not write file, check folder permissions.').'</p>'
                    ));

                    return false;
                }

                break;

            case 'bmp':

                if (!function_exists('imagewbmp'))
                {
                    $this->fcmsError->add(array(
                        'message' => T_('Upload Error'),
                        'details' => '<p>'.T_('GD Library is either not installed or does not support this file type.').'</p>'
                    ));

                    return false;
                }

                if (@!imagewbmp($source, $this->destinationPath.$fileName))
                {
                    $this->fcmsError->add(array(
                        'message' => T_('Upload Error'),
                        'details' => '<p>'.T_('Could not write file, check folder permissions.').'</p>'
                    ));

                    return false;
                }

                break;

            case 'png':

                if (!function_exists('imagepng'))
                {
                    $this->fcmsError->add(array(
                        'message' => T_('Upload Error'),
                        'details' => '<p>'.T_('GD Library is either not installed or does not support this file type.').'</p>'
                    ));

                    return false;
                }

                if (@!imagepng($source, $this->destinationPath.$fileName))
                {
                    $this->fcmsError->add(array(
                        'message' => T_('Upload Error'),
                        'details' => '<p>'.T_('Could not write file, check folder permissions.').'</p>'
                    ));

                    return false;
                }

                break;
        }

        return true;
    }

}
