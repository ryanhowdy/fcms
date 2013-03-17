<?php
/**
 * Upload
 * 
 * PHP versions 4 and 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '../');
define('GALLERY_PREFIX', '');

require URL_PREFIX.'fcms.php';

load('gallery');

// Gallery
$fcmsPhotoGallery = new PhotoGallery($fcmsError, $fcmsDatabase, $fcmsUser);

if ($fcmsUser->id < 1)
{
    echo "Current User Authorization Failure";
    logError(__FILE__.' ['.__LINE__.'] - Unauthorized user attempted upload.');
    die();
}

$file_param_name[] = 'small';
$file_param_name[] = 'medium';

if ($fcmsPhotoGallery->usingFullSizePhotos())
{
    $file_param_name[] = 'full';
}

$known_photo_types = array(
    'image/pjpeg'   => 'jpg', 
    'image/jpeg'    => 'jpg', 
    'image/gif'     => 'gif', 
    'image/bmp'     => 'bmp', 
    'image/x-png'   => 'png', 
    'image/png'     => 'png'
);

// New Category or existing?
if (empty($_POST['category']))
{
    if (empty($_POST['new-category']))
    {
        // Send error to the edit page
        $_SESSION['photos']['error'] = 'error';
        echo "Category Failure";
        logError(__FILE__.' ['.__LINE__.'] - No category given.');
        die();
    }
    else
    {
        // If we are uploading multiple photos to a new category, only create 1 category
        if (isset($_SESSION['mass_photos_category']))
        {
            $_POST['category'] = $_SESSION['mass_photos_category'];
        }
        else
        {
            $newCategory = strip_tags($_POST['new-category']);

            // Create category
            $sql = "INSERT INTO `fcms_category`(`name`, `type`, `user`) 
                    VALUES (?, 'gallery', ?)";

            $params = array(
                $newCategory, 
                $fcmsUser->id
            );

            $categoryId = $fcmsDatabase->insert($sql, $params);
            if ($categoryId === false)
            {
                echo "Create Category Failure";
                die();
            }

            $_POST['category']                = $categoryId;
            $_SESSION['mass_photos_category'] = $_POST['category'];
        }
    }
}

// Create a new photo record in DB
$sql = "INSERT INTO `fcms_gallery_photos` (`date`, `category`, `user`) 
        VALUES(NOW(), ?, ?)";

$params = array(
    $_POST['category'],
    $fcmsUser->id
);

$new_id = $fcmsDatabase->insert($sql, $params);
if ($new_id === false)
{
    echo "Insert New Photo Failure";
    die();
}

// Update the filename and update the photo record in DB
// We insert above and update below so we can make sure that the filename of
// the photo is the same as the photo id
$filetype    = $_FILES['medium']['type'];
$extention   = $known_photo_types[$filetype];
$filename    = $new_id.'.'.$extention;
$uploadsPath = getUploadsAbsolutePath();

$sql = "UPDATE `fcms_gallery_photos` 
        SET `filename` = ?
        WHERE `id` = ?";

$params = array(
    $filename,
    $new_id
);

if (!$fcmsDatabase->update($sql, $params))
{
    echo "Update Photo Failure";
    die();
}

// Loop through each photo (small, medium, full?)
foreach ($file_param_name AS $file)
{
    // Create new member directory if needed
    if (!file_exists($uploadsPath.'photos/member'.$fcmsUser->id))
    {
        mkdir($uploadsPath.'photos/member'.$fcmsUser->id);
    }

    if ($file == 'small')
    {
        $dest_path = $uploadsPath.'photos/member'.$fcmsUser->id.'/tb_'.$filename;
    }
    elseif ($file == 'full' && isset($_POST['full-sized-photos']))
    {
        $dest_path = $uploadsPath.'photos/member'.$fcmsUser->id.'/full_'.$filename;
    }
    else
    {
        $dest_path = $uploadsPath.'photos/member'.$fcmsUser->id.'/'.$filename;
    }

    if (move_uploaded_file($_FILES[$file]['tmp_name'], $dest_path))
    {
        // Set up the session vars to send to edit page
        // only on medium, so we don't get both full size and thumbnail
        if ($file == 'medium')
        {
            $_SESSION['photos'][] = array(
                'id'       => $new_id,
                'filename' => $filename,
                'category' => (int)$_POST['category']
            );
        }

        echo "success";
    }
    else
    {
        echo "Move File Failure";
        logError(__FILE__.' ['.__LINE__.'] - Move file failure.');
    }
}
