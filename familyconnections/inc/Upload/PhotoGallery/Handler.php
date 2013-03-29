<?php

class Handler
{
    private $tmpPhoto;
    private $fileName;
    private $extension;
    private $mimeType;

    private $usingFullSizePhotos;
    private $rotate;

    private $thumbMaxWidth  = 150;
    private $thumbMaxHeight = 150;
    private $mainMaxWidth   = 600;
    private $mainMaxHeight  = 600;

    private $validMimeTypes = array(
        'image/pjpeg'   => 1,
        'image/jpeg'    => 1, 
        'image/gif'     => 1, 
        'image/bmp'     => 1, 
        'image/x-png'   => 1, 
        'image/png'     => 1
    );
    private $validExtensions = array(
        'jpeg'  => 1,
        'jpg'   => 1,
        'gif'   => 1,
        'bmp'   => 1,
        'png'   => 1
    );

    /**
     * __construct 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase 
     * @param object $fcmsUser 
     * @param object $destinationType 
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $destinationType)
    {
        $this->fcmsError       = $fcmsError;
        $this->fcmsDatabase    = $fcmsDatabase;
        $this->fcmsUser        = $fcmsUser;
        $this->destinationType = $destinationType;

        $this->usingFullSizePhotos = $this->getUsingFullSizePhotos();
    }

    /**
     * validate 
     * 
     * @param array $formData 
     * 
     * @return boolean
     */
    public function validate ($formData)
    {
        // Catch photos that are too large
        if ($formData['photo']['error'] == 1)
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
        if ($formData['photo']['error'] == 4)
        {
            $this->fcmsError->add(array(
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('You must choose a photo first.').'</p>'
            ));

            return false;
        }

        // Another check that we have a photo
        if ($formData['photo']['size'] <= 0)
        {
            $this->fcmsError->add(array(
                'message' => T_('Upload Error'),
                'details' => '<p>'.T_('Photo is corrupt or missing.').'</p>'
            ));

            return false;
        }

        // Validate mimetype/extension for real photo
        if (!isset($this->validMimeTypes[$this->mimeType]) || !isset($this->validExtensions[$this->extension]))
        {
            $this->fcmsError->add(array(
                'message' => T_('Upload Error'),
                'details' => '<p>'.sprintf(T_('Photo [%s] is not a supported photo type.  Photos must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $this->fileName).'</p>'
            ));

            return false;
        }

        // Make sure we have a valid rotate type
        if (!is_null($this->rotate))
        {
            if ($this->rotate !== 'left' || $this->rotate !== 'right')
            {
                $this->fcmsError->add(array(
                    'message' => T_('Upload Error'),
                    'details' => '<p>'.T_('Rotate option is invalid, must choose "left" or "right".').'</p>'
                ));

                return false;
            }
        }

        return true;
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
        $this->destinationType->createDirectory();
    }

    /**
     * upload
     * 
     * @param int $newPhotoId 
     * 
     * @return boolean
     */
    public function upload ($newPhotoId)
    {
        // Setup the array of photos that need uploaded
        $uploadPhotos = array(
            'main'  => array(
                'resize'    => true,
                'square'    => false,
                'prefix'    => '',
                'width'     => $this->mainMaxWidth,
                'height'    => $this->mainMaxHeight
            ),
            'thumb' => array(
                'resize'    => true,
                'square'    => true,
                'prefix'    => 'tb_',
                'width'     => $this->thumbMaxWidth,
                'height'    => $this->thumbMaxHeight
            ),
        );

        if ($this->usingFullSizePhotos)
        {
            $uploadPhotos['full'] = array(
                'resize'    => false,
                'square'    => false,
                'prefix'    => 'full_',
                'width'     => 0,
                'height'    => 0
            );
        }

        // Loop through each photo that needs saved
        foreach ($uploadPhotos as $key => $value)
        {
            $resize = $uploadPhotos[$key]['resize'];
            $square = $uploadPhotos[$key]['square'];
            $prefix = $uploadPhotos[$key]['prefix'];
            $width  = $uploadPhotos[$key]['width'];
            $height = $uploadPhotos[$key]['height'];

            // Reset the filename for each photo
            $this->fileName = $prefix.$newPhotoId.'.'.$this->extension;

            // Copy temp photo to destination
            $this->destinationType->copy($this->tmpPhoto, $this->fileName);

            // Do we have enough memory
            if (!$this->haveEnoughMemory($newPhotoId))
            {
                return false;
            }

            // Rotate
            if ($this->rotate == 'left')
            {
                $this->rotate(90);
            }
            elseif ($this->rotate == 'right')
            {
                $this->rotate(270);
            }

            // Resize
            if ($resize && !$this->resize($width, $height, $square))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * setFormData 
     * 
     * Saves all the data passed in from the form upload.
     * 
     * @param array $formData
     * 
     * @return void
     */
    public function setFormData ($formData)
    {
        // Save photo data from $_FILES
        $this->tmpPhoto = $formData['photo']['tmp_name'];
        $this->fileName = cleanFilename($formData['photo']['name']);
        $this->mimeType = $formData['photo']['type'];

        $this->setExtension();

        // Set optional form params
        $this->rotate = $formData['rotate'];
//        $this->photo       = isset($formData['photo'])       ? $formData['photo']                   : null;
//        $this->newCategory = isset($formData['newCategory']) ? strip_tags($formData['newCategory']) : null; 
//        $this->category    = isset($formData['category'])    ? $formData['category']                : null; 
//        $this->caption     = isset($formData['caption'])     ? strip_tags($formData['caption'])     : null; 
    }

    /**
     * setExtension 
     * 
     * @return void
     */
    private function setExtension ()
    {
        $arr = explode('.', $this->fileName);

        $this->extension = '';

        // If arr doesn't have atleast 2 elements, then the file didn't have an extension
        if (count($arr) >= 2)
        {
            $this->extension = end($arr);
            $this->extension = strtolower($this->extension);
        }
    }

    /**
     * getExtension 
     * 
     * @return void
     */
    public function getExtension ()
    {
        return $this->extension;
    }

    /**
     * setFileName 
     * 
     * @param string $fileName 
     * 
     * @return void
     */
    public function setFileName ($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * getUsingFullSizePhotos 
     * 
     * @return boolean
     */
    private function getUsingFullSizePhotos ()
    {
        $sql = "SELECT `value` AS 'full_size_photos'
                FROM `fcms_config`
                WHERE `name` = 'full_size_photos'";

        $r = $this->fcmsDatabase->getRow($sql);
        if (empty($r))
        {
            return false;
        }

        if ($r['full_size_photos'] == 1)
        {
            return true;
        }

        return false;
    }

    /**
     * resize 
     * 
     * @param int $maxWidth 
     * @param int $maxHeight 
     * 
     * @return boolean
     */
    private function resize ($maxWidth, $maxHeight, $square = false)
    {
        $currentSize = $this->destinationType->getImageSize($this->fileName);

        // Does the image even need resized?
        if ($currentSize[0] < $maxWidth && $currentSize[1] < $maxHeight)
        {
            return true;
        }

        // Get widths and heights for square image (cropping might occur)
        if ($square)
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

        $sourceIdentifier = $this->destinationType->createImageIdentifier($this->fileName, $this->extension);

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

        return $this->destinationType->writeImage($destinationIdentifier, $this->fileName, $this->extension);
    }

    /**
     * rotate 
     * 
     * @param int $degrees 
     * 
     * @return boolean
     */
    private function rotate ($degrees = 90)
    {
        $identifier = $this->destinationType->createImageIdentifier($this->fileName, $this->extension);

        $source = imagerotate($identifier, $degrees, 0);

        return $this->destinationType->writeImage($source, $this->fileName, $this->extension);
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
    private function getResizeSize ($orig_width, $orig_height, $max_width, $max_height)
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
    private function getResizeSizeSquare ($width, $height, $max)
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
     * @param int $newPhotoId 
     * 
     * @return boolean
     */
    private function haveEnoughMemory ($newPhotoId)
    {
        $this->memoryAvailable = ini_get('memory_limit');
        $this->memoryAvailable = substr($this->memoryAvailable, 0, -1);
        $this->memoryAvailable = ($this->memoryAvailable * 1024) * 1024;

        $size = $this->destinationType->getImageSize($this->fileName);

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
            // Try to delete from db
            $sql = "DELETE FROM `fcms_gallery_photos` 
                    WHERE `id` = ?";
            $this->fcmsDatabase->delete($sql, $newPhotoId);
            
            // Try to delete from server
            $this->destinationType->deleteFile($this->fileName);

            $this->fcmsError->add(array(
                'message' => T_('Out of Memory Warning'),
                'details' => '<p>'.T_('The photo you are trying to upload is quite large and the server might run out of memory if you continue.')
                             .T_('It is recommended that you try to upload this photo using the "Advanced Uploader" instead.')
                             .'<small>('.number_format($this->memoryNeeded).' / '.number_format($this->memoryAvailable).')</small></p>'
                             .'<h3>'.T_('What do you want to do?').'</h3>'
                             .'<p><a href="?action=upload&amp;advanced=on">'.T_('Use the "Advanced Uploader"').'</a>&nbsp; '
                             .T_('or').' <a class="u" href="index.php">'.T_('Cancel').'</a></p>'
            ));

            return false;
        }

        return true;
    }
}
