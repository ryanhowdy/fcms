<?php
include_once 'utils.php';
include_once 'database_class.php';

/**
 * Image
 * 
 * @package     Family Connections
 * @copyright   2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Image
{
    var $db;
    var $tzOffset;
    var $currentUserId;
    /**
     * error 
     * 
     *  1   Image type not supported or invalid
     *  2   GD doesn't support image type
     *  3   Could not write new image
     *  4   Not enough memory to resize image
     * 
     * @var string 
     */
    var $error;
    var $resizeSquare;
    var $uniqueName;
    var $name;
    var $type;
    var $extension;
    var $destination;
    var $transparentRed;
    var $transparentBlue;
    var $transparentGreen;
    var $memoryNeeded;
    var $memoryAvailable;

    /**
     * Image
     * 
     * @param int $currentUserId 
     * 
     * @return  void
     */
    function Image ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->tzOffset         = getTimezone($this->currentUserId);
        $this->currentUserId    = cleanInput($currentUserId, 'int');
        $this->error            = 0;
        $this->resizeSquare     = false;
        $this->uniqueName       = false;
        $this->name             = '';
        $this->type             = '';
        $this->extension        = '';
        $this->destination      = '';
    }

    /**
     * upload 
     * 
     * @param array $img 
     * 
     * @return boolean
     */
    function upload ($img)
    {
        if (empty($this->name))
        {
            $this->name = cleanFilename($img['name']);
        }

        $this->type = $img['type'];

        // Get extension of photo
        $this->getExtension();

        if (!$this->isValid())
        {
            $this->error = 1;
            return false;
        }

        // Unique Filename
        if ($this->uniqueName)
        {
            $id = uniqid("");
            $this->name = $id.'.'.$this->extension;
        }

        copy($img['tmp_name'], $this->destination.$this->name);

        return $this->name;
    }

    /**
     * getExtension 
     * 
     * @return void
     */
    function getExtension ()
    {
        $arr = explode('.', $this->name);

        // If arr doesn't have atleast 2 elements, then the file didn't have an extension
        if (count($arr) < 2)
        {
            $this->extension = '';
            return;
        }

        $this->extension = end($arr);
        $this->extension = strtolower($this->extension);
    }

    /**
     * isValid 
     * 
     * Checks that the image is of a valid mimetype and extension.
     * 
     * @return boolean
     */
    function isValid ()
    {
        if (empty($this->extension))
        {
            return false;
        }

        $valid_mime_types = array(
            'image/pjpeg'   => 1,
            'image/jpeg'    => 1, 
            'image/gif'     => 1, 
            'image/bmp'     => 1, 
            'image/x-png'   => 1, 
            'image/png'     => 1
        );
        $valid_extensions = array(
            'jpeg'  => 1,
            'jpg'   => 1,
            'gif'   => 1,
            'bmp'   => 1,
            'png'   => 1
        );

        if (!isset($valid_mime_types[$this->type]))
        {
            return false;
        }

        if (!isset($valid_extensions[$this->extension]))
        {
            return false;
        }

        return true;
    }

    /**
     * resize 
     * 
     * @param int $maxWidth 
     * @param int $maxHeight 
     * 
     * @return boolean
     */
    function resize ($maxWidth, $maxHeight)
    {
        $currentSize = GetImageSize($this->destination.$this->name);

        // Does the image even need resized?
        if ($currentSize[0] < $maxWidth && $currentSize[1] < $maxHeight)
        {
            return true;
        }

        // Do we have enough memory
        if (!$this->haveEnoughMemory())
        {
            return false;
        }

        // Get widths and heights for square image (cropping might occur)
        if ($this->resizeSquare)
        {
            $resizeSize = $this->getResizeSizeSquare(
                $currentSize[0], 
                $currentSize[1], 
                $maxWidth
            );
            $destinationWidth   = $resizeSize[0];
            $destinationHeight  = $resizeSize[1];
            $trueColorWidth     = $resizeSize[2];
            $trueColorHeight    = $resizeSize[3];
        }
        // Get widths and heights for proportional image
        else
        {        
            $resizeSize = $this->getResizeSize(
                $currentSize[0], 
                $currentSize[1], 
                $maxWidth, 
                $maxHeight
            );
            $destinationWidth   = $resizeSize[0];
            $destinationHeight  = $resizeSize[1];
            $trueColorWidth     = $resizeSize[0];
            $trueColorHeight    = $resizeSize[1];
        }

        $sourceIdentifier = $this->createImageIdentifier();

        $destinationIdentifier = ImageCreateTrueColor($trueColorWidth, $trueColorHeight);

        // Resize image
        ImageCopyResampled(
            $destinationIdentifier, 
            $sourceIdentifier, 
            0, 0, 0, 0, 
            $destinationWidth, 
            $destinationHeight,
            $currentSize[0], 
            $currentSize[1]
        );

        return $this->writeImage($destinationIdentifier);
    }

    /**
     * rotate 
     * 
     * @param int $degrees 
     * 
     * @return boolean
     */
    function rotate ($degrees = 90)
    {
        $identifier = $this->createImageIdentifier();

        $source = imagerotate($identifier, $degrees, 0);

        return $this->writeImage($source);
    }

    /**
     * createImageIdentifier 
     * 
     * Creates an image identifer representing and image obtained from a filename.
     * 
     * @return image identifier
     */
    function createImageIdentifier ()
    {
        switch($this->extension)
        {
            case 'jpeg':
            case 'jpg':

                $identifier = @imagecreatefromjpeg($this->destination.$this->name);

                break;

            case 'gif':

                // Handle transparent gifs
                $fp     = fopen($this->destination.$this->name, 'rb');
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

                $identifier = @imagecreatefromgif($this->destination.$this->name);

                break;

            case 'wbmp':

                $identifier = @imagecreatefrombmp($this->destination.$this->name);

                break;

            case 'png':

                $identifier = @imagecreatefrompng($this->destination.$this->name);

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
     * @param image resource $source 
     * 
     * @return boolean
     */
    function writeImage ($source)
    {
        switch($this->extension)
        {
            case 'jpeg':
            case 'jpg':

                if (!function_exists('imagejpeg'))
                {
                    $this->error = 2;
                    return false;
                }

                if (@!imagejpeg($source, $this->destination.$this->name))
                {
                    $this->error = 3;
                    return false;
                }

                break;

            case 'gif':

                if (!function_exists('imagegif'))
                {
                    $this->error = 2;
                    return false;
                }

                if (@!imagegif($source, $this->destination.$this->name))
                {
                    $this->error = 3;
                    return false;
                }

                break;

            case 'wbmp':

                if (!function_exists('imagewbmp'))
                {
                    $this->error = 2;
                    return false;
                }

                if (@!imagewbmp($source, $this->destination.$this->name))
                {
                    $this->error = 3;
                    return false;
                }

                break;

            case 'PNG':

                if (!function_exists('imagepng'))
                {
                    $this->error = 2;
                    return false;
                }

                if (@!imagepng($source, $this->destination.$this->name))
                {
                    $this->error = 3;
                    return false;
                }

                break;
        }

        return true;
    }

    /**
     * getResizeSize 
     * 
     * Given a photo's width/height, and the maximum resized width/height, it will calculate 
     * the width/height while not distorting.
     *
     * For example, a 800x600 photo with a max size of 500x500 will return 500x375
     *
     * @param int $orig_width  the original width of the photo
     * @param int $orig_height the original height of the photo
     * @param int $max_width   the maximum width for the new photo size
     * @param int $max_height  the maximum height for the new photo size
     *
     * @return  array   the new width/height
     */
    function getResizeSize ($orig_width, $orig_height, $max_width, $max_height)
    {
        // Wider than tall
        if ($orig_width > $orig_height)
        {
            // Check width
            if ($orig_width > $max_width)
            {
                $height = (int)($max_width * $orig_height / $orig_width);

                return array($max_width, $height);
            }
            // Check height
            elseif ($orig_height > $max_height)
            {
                $width = (int)($max_height * $orig_width / $orig_height);

                return array($width, $max_height);
            }
            // No need to resize if it's smaller than max
            else
            {
                return array($orig_width, $orig_height);
            }

        }
        // Taller than wide
        else
        {
            // Check height
            if ($orig_height > $max_height)
            {
                $width = (int)($max_height * $orig_width / $orig_height);

                return array($width, $max_height);
            }
            // Check width
            elseif ($orig_width > $max_width)
            {
                $height = (int)($max_width * $orig_height / $orig_width);

                return array($max_width, $height);
            }
            // No need to resize if it's smaller than max
            else
            {
                return array($orig_width, $orig_height);
            }
        }

        return array($orig_width, $orig_height);
    }

    /**
     * getResizeSizeSquare 
     * 
     * Given the photos width/height and a max, it will resize the photo to as close to
     * square as possible, allowing the smallest amount of cropping possible.
     * Photos smaller than the max will not be square and will not be resized/cropped.
     * 
     * Returns an array with the photo demensions and crop demensions:
     *      array( resize_width, resize_height, crop_width, crop_height )
     *
     * For example: given a photo of 800x600 and max size of 150
     *      will return:  array(200, 150, 150, 150)
     * 
     * For example: given a photo of 45x20 and max size of 150
     *      will return:  array(45, 20, 45, 20)
     * 
     * @param int $width 
     * @param int $height 
     * @param int $max 
     * 
     * @return  array
     */
    function getResizeSizeSquare ($width, $height, $max)
    {
        // Is either side smaller than max
        $small = ($width < $max or $height < $max) ? true : false;

        // Wider than tall
        if ($width > $height)
        {
            // Check height
            if ($height > $max)
            {
                $width = (int)($max * $width / $height);

                return array($width, $max, $max, $max);
            }
            // Check width
            elseif ($width > $max)
            {
                if ($small)
                {
                    return array($width, $height, $max, $max);
                }
                else
                {
                    $height = (int)($max * $height / $width);

                    return array($max, $height, $max, $max);
                }
            }
        }
        // Taller than wide
        else
        {
            // Check width
            if ($width > $max)
            {
                $height = (int)($max * $height / $width);

                return array($max, $height, $max, $max);
            }
            // Check height
            elseif ($height > $max)
            {
                $width = (int)($max * $width / $height);

                return array($width, $max, $max, $max);
            }
        }

        // if all else fails return orig dimensions
        return array($width, $height, $width, $height);
    }

    /**
     * haveEnoughMemory 
     * 
     * Calculates whether the given image can be resized with the current available memory.
     * 
     * @return boolean
     */
    function haveEnoughMemory ()
    {
        $this->memoryAvailable = ini_get('memory_limit');
        $this->memoryAvailable = substr($this->memoryAvailable, 0, -1);
        $this->memoryAvailable = ($this->memoryAvailable * 1024) * 1024;

        $size = GetImageSize($this->destination.$this->name);

        // channels and bits are not present on all images
        if (!isset($size['channels'])) {
            $size['channels'] = 3;
        }
        if (!isset($size['bits'])) {
            $size['bits'] = 8;
        }

        $this->memoryNeeded = Round(($size[0] * $size[1] * $size['bits'] * $size['channels'] / 8 + Pow(2, 16)) * 1.65);

        if ($this->memoryNeeded > $this->memoryAvailable)
        {
            $this->error = 4;
            return false;
        }

        return true;
    }
}
