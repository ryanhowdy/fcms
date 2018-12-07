<?php
/**
 * Upload Photo.
 *
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class UploadPhoto
{
    private $fcmsError;
    private $destination;

    public $photo;
    public $fileName;
    public $extension;

    private $validMimeTypes = [
        'image/pjpeg'   => 1,
        'image/jpeg'    => 1,
        'image/gif'     => 1,
        'image/bmp'     => 1,
        'image/x-png'   => 1,
        'image/png'     => 1,
    ];
    private $validExtensions = [
        'jpeg'  => 1,
        'jpg'   => 1,
        'gif'   => 1,
        'bmp'   => 1,
        'png'   => 1,
    ];

    /**
     * __construct.
     *
     * @param FCMS_Error  $fcmsError
     * @param Destination $destination
     *
     * @return void
     */
    public function __construct(FCMS_Error $fcmsError, Destination $destination)
    {
        $this->fcmsError = $fcmsError;
        $this->destination = $destination;
    }

    /**
     * load.
     *
     * Takes a $_FILES object and sets the photo, filename and extension
     * variables. Then does some validation.
     *
     * @param FILES $photo
     *
     * @return UploadPhoto
     */
    public function load($photo)
    {
        $this->photo = $photo;
        $this->fileName = cleanFilename($this->photo['name']);

        $this->setExtension();
        $this->validate();

        if ($this->fcmsError->hasUserError())
        {
            return $this;
        }

        return $this;
    }

    /**
     * validate.
     *
     * @return UploadPhoto
     */
    private function validate()
    {
        // Catch photos that are too large
        if ($this->photo['error'] == 1)
        {
            $max = ini_get('upload_max_filesize');

            $this->fcmsError->add([
                'message' => T_('Upload Error'),
                'details' => '<p>'.sprintf(T_('Your photo exceeds the maximum size allowed by your PHP settings [%s].'), $max).'</p>',
            ]);

            return $this;
        }

        // Make sure we have an image
        if ($this->photo['error'] == 4)
        {
            $this->fcmsError->add([
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('You must choose a photo first.').'</p>',
            ]);

            return $this;
        }

        // Another check that we have a photo
        if ($this->photo['size'] <= 0)
        {
            $this->fcmsError->add([
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('Photo is corrupt or missing.').'</p>',
            ]);

            return $this;
        }

        // Validate mimetype/extension for real photo
        if (!isset($this->validMimeTypes[$this->photo['type']]) || !isset($this->validExtensions[$this->extension]))
        {
            $this->fcmsError->add([
                'message' => T_('Upload Error'),
                'details' => '<p>'.sprintf(T_('Photo [%s] is not a supported photo type.  Photos must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $this->fileName).'</p>',
            ]);

            return $this;
        }

        return $this;
    }

    /**
     * save.
     *
     * @param string $savedFileName
     *
     * @return UploadPhoto
     */
    public function save($savedFileName = null)
    {
        if ($this->fcmsError->hasUserError())
        {
            return $this;
        }

        $this->fileName = $savedFileName;

        if (is_null($savedFileName))
        {
            // Make file name unique
            $id = uniqid('');
            $this->fileName = $id.'.'.$this->extension;
        }

        // Copy temp photo to destination
        $this->destination->copy($this->photo['tmp_name'], $this->fileName);

        return $this;
    }

    /**
     * getFileExtension.
     *
     * @param string $file
     *
     * @return string
     */
    public function getFileExtension($file)
    {
        $ext = '';
        $arr = explode('.', $file);

        // If arr doesn't have atleast 2 elements, then the file didn't have an extension
        if (count($arr) >= 2)
        {
            $ext = end($arr);
            $ext = strtolower($ext);

            // check if we have any ?params or anything after the extension
            $pos = strpos($ext, '?');

            if ($pos !== false)
            {
                $ext = substr($ext, 0, $pos);
            }
        }

        return $ext;
    }

    /**
     * setExtension.
     *
     * @return void
     */
    private function setExtension()
    {
        $this->extension = $this->getFileExtension($this->fileName);
    }

    /**
     * resize.
     *
     * @param int    $maxWidth
     * @param int    $maxHeight
     * @param string $resizeType
     *
     * @return UploadPhoto
     */
    public function resize($maxWidth, $maxHeight, $resizeType = 'default')
    {
        if ($this->fcmsError->hasUserError())
        {
            return $this;
        }

        // Make sure we have enough memory
        if (!$this->haveEnoughMemory())
        {
            return $this;
        }

        // Make sure file was saved already
        if (!file_exists($this->destination->destinationPath.$this->fileName))
        {
            return $this;
        }

        $currentSize = $this->destination->getImageSize($this->destination->destinationPath.$this->fileName);

        // Does the image even need resized?
        if ($currentSize[0] < $maxWidth && $currentSize[1] < $maxHeight)
        {
            return $this;
        }

        // Get widths and heights for square image (cropping might occur)
        if ($resizeType == 'square')
        {
            $resizeSize = $this->getResizeSizeSquare(
                $currentSize[0],
                $currentSize[1],
                $maxWidth
            );
            $destinationWidth = $resizeSize[0];
            $destinationHeight = $resizeSize[1];
            $trueColorWidth = $resizeSize[2];
            $trueColorHeight = $resizeSize[3];
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
            $destinationWidth = $resizeSize[0];
            $destinationHeight = $resizeSize[1];
            $trueColorWidth = $resizeSize[0];
            $trueColorHeight = $resizeSize[1];
        }

        $sourceIdentifier = $this->destination->createImageIdentifier($this->fileName, $this->extension);

        $destinationIdentifier = imagecreatetruecolor($trueColorWidth, $trueColorHeight);

        // Resize image
        if (!imagecopyresampled(
            $destinationIdentifier,
            $sourceIdentifier,
            0, 0, 0, 0,
            $destinationWidth,
            $destinationHeight,
            $currentSize[0],
            $currentSize[1]
        ))
        {
            $this->fcmsError->add([
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('Could not resample photo.').'</p>',
            ]);

            return $this;
        }

        if (!$this->destination->writeImage($destinationIdentifier, $this->fileName, $this->extension))
        {
            $this->fcmsError->add([
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('Could not save resized photo.').'</p>',
            ]);

            return $this;
        }

        return $this;
    }

    /**
     * rotate.
     *
     * @param int $degrees
     *
     * @return UploadPhoto
     */
    public function rotate($degrees = 90)
    {
        if ($this->fcmsError->hasUserError())
        {
            return $this;
        }

        // Make sure file was saved already
        if (!file_exists($this->destination->destinationPath.$this->fileName))
        {
            return $this;
        }

        $identifier = $this->destination->createImageIdentifier($this->fileName, $this->extension);

        if ($identifier === false)
        {
            $this->fcmsError->add([
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('Could not create new rotated photo.').'</p>',
            ]);

            return $this;
        }

        $source = imagerotate($identifier, $degrees, 0);

        if ($source === false)
        {
            $this->fcmsError->add([
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('Could not rotate photo.').'</p>',
            ]);

            return $this;
        }

        if (!$this->destination->writeImage($source, $this->fileName, $this->extension))
        {
            $this->fcmsError->add([
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('Could not save rotated photo.').'</p>',
            ]);

            return $this;
        }

        return $this;
    }

    /**
     * getResizeSize.
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
     * @return array the new width/height
     */
    private function getResizeSize($orig_width, $orig_height, $max_width, $max_height)
    {
        // Wider than tall
        if ($orig_width > $orig_height)
        {
            // Check width
            if ($orig_width > $max_width)
            {
                $height = (int) ($max_width * $orig_height / $orig_width);

                return [$max_width, $height];
            }
            // Check height
            elseif ($orig_height > $max_height)
            {
                $width = (int) ($max_height * $orig_width / $orig_height);

                return [$width, $max_height];
            }
            // No need to resize if it's smaller than max
            else
            {
                return [$orig_width, $orig_height];
            }

        }
        // Taller than wide
        else
        {
            // Check height
            if ($orig_height > $max_height)
            {
                $width = (int) ($max_height * $orig_width / $orig_height);

                return [$width, $max_height];
            }
            // Check width
            elseif ($orig_width > $max_width)
            {
                $height = (int) ($max_width * $orig_height / $orig_width);

                return [$max_width, $height];
            }
            // No need to resize if it's smaller than max
            else
            {
                return [$orig_width, $orig_height];
            }
        }

        return [$orig_width, $orig_height];
    }

    /**
     * getResizeSizeSquare.
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
     * @return array
     */
    private function getResizeSizeSquare($width, $height, $max)
    {
        // Is either side smaller than max
        $small = ($width < $max or $height < $max) ? true : false;

        // Wider than tall
        if ($width > $height)
        {
            // Check height
            if ($height > $max)
            {
                $width = (int) ($max * $width / $height);

                return [$width, $max, $max, $max];
            }
            // Check width
            elseif ($width > $max)
            {
                if ($small)
                {
                    return [$width, $height, $max, $max];
                }
                else
                {
                    $height = (int) ($max * $height / $width);

                    return [$max, $height, $max, $max];
                }
            }
        }
        // Taller than wide
        else
        {
            // Check width
            if ($width > $max)
            {
                $height = (int) ($max * $height / $width);

                return [$max, $height, $max, $max];
            }
            // Check height
            elseif ($height > $max)
            {
                $width = (int) ($max * $width / $height);

                return [$width, $max, $max, $max];
            }
        }

        // if all else fails return orig dimensions
        return [$width, $height, $width, $height];
    }

    /**
     * haveEnoughMemory.
     *
     * Calculates whether the given image can be resized with the current available memory.
     *
     * @return bool
     */
    private function haveEnoughMemory()
    {
        $this->memoryAvailable = ini_get('memory_limit');
        $this->memoryAvailable = substr($this->memoryAvailable, 0, -1);
        $this->memoryAvailable = ($this->memoryAvailable * 1024) * 1024;

        $size = $this->destination->getImageSize($this->destination->destinationPath.$this->fileName);

        // channels and bits are not present on all images
        if (!isset($size['channels'])) {
            $size['channels'] = 3;
        }
        if (!isset($size['bits'])) {
            $size['bits'] = 8;
        }

        $this->memoryNeeded = round(($size[0] * $size[1] * $size['bits'] * $size['channels'] / 8 + pow(2, 16)) * 1.65);

        if ($this->memoryNeeded > $this->memoryAvailable)
        {
            // Try to delete from server
            $this->destination->deleteFile($this->fileName);

            $this->fcmsError->add([
                'message' => T_('Out of Memory Warning'),
                'details' => '<p>'.T_('The photo you are trying to upload is quite large and the server might run out of memory if you continue.')
                             .'<small>('.number_format($this->memoryNeeded).' / '.number_format($this->memoryAvailable).')</small></p>',
            ]);

            return false;
        }

        return true;
    }
}
