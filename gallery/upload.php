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

require URL_PREFIX.'fcms.php';

$currentUserId = cleanInput($_SESSION['login_id'], 'int');

if ($currentUserId < 1)
{
    echo "Current User Authorization Failure";
    die();
}

$file_param_name[] = 'small';
$file_param_name[] = 'medium';
$file_param_name[] = 'full';

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
            // Create category
            $sql = "INSERT INTO `fcms_category`(`name`, `type`, `user`) 
                    VALUES (
                        '".cleanInput($_POST['new-category'])."', 
                        'gallery', 
                        '$currentUserId'
                    )";
            mysql_query($sql) or displaySQLError(
                'New Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
            $_POST['category'] = mysql_insert_id();
            $_SESSION['mass_photos_category'] = $_POST['category'];
        }
    }
}

// Create a new photo record in DB
$sql = "INSERT INTO `fcms_gallery_photos` (`date`, `category`, `user`) 
        VALUES(
            NOW(), 
            '".cleanInput($_POST['category'])."', 
            '$currentUserId'
        )";
if (!mysql_query($sql))
{
    echo "Insert New Photo Failure";
    die();
}

// Update the filename and update the photo record in DB
// We insert above and update below so we can make sure that the filename of
// the photo is the same as the photo id
$new_id    = mysql_insert_id();
$filetype  = $_FILES['medium']['type'];
$extention = $known_photo_types[$filetype];
$filename  = "$new_id.$extention";

$sql = "UPDATE `fcms_gallery_photos` 
        SET `filename` = '".cleanInput($filename)."' 
        WHERE `id` = '".cleanInput($new_id)."'";
if (!mysql_query($sql))
{
    echo "Update Photo Failure";
    die();
}

// Loop through each photo (small, medium, full?)
foreach ($file_param_name AS $file)
{
    // Create new member directory if needed
    if (!file_exists("../uploads/photos/member$currentUserId"))
    {
        mkdir("../uploads/photos/member$currentUserId");
    }

    if ($file == 'small')
    {
        $dest_path = "../uploads/photos/member$currentUserId/tb_$filename";
    }
    elseif ($file == 'full' && isset($_POST['full-sized-photos']))
    {
        $dest_path = "../uploads/photos/member$currentUserId/full_$filename";
    }
    elseif ($file == 'full' && isset($_POST['full-sized-photos']))
    {
        $dest_path = "photos/member$currentUserId/full_$filename";
    }
    else
    {
        $dest_path = "../uploads/photos/member$currentUserId/$filename";
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
                'category' => $_POST['category']
            );
        }
        echo "success";
    }
    else
    {
        echo "Move File Failure";
    }
}
