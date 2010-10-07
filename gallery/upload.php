<?php
session_start();

include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');

fixMagicQuotes();

$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$file_param_name[] = 'small';
$file_param_name[] = 'medium';
$known_photo_types = array(
    'image/pjpeg'   => 'jpg', 
    'image/jpeg'    => 'jpg', 
    'image/gif'     => 'gif', 
    'image/bmp'     => 'bmp', 
    'image/x-png'   => 'png', 
    'image/png'     => 'png'
);

// New Category or existing?
if (empty($_POST['category'])) {

    if (empty($_POST['new-category'])) {
        // Send error to the edit page
        $_SESSION['photos']['error'] = 'error';
        echo "failure";
        die();

    } else {

        // If we are uploading multiple photos to a new category, only create 1 category
        if (isset($_SESSION['mass_photos_category'])) {
            $_POST['category'] = $_SESSION['mass_photos_category'];
        } else {
            // Create category
            $sql = "INSERT INTO `fcms_category`(`name`, `type`, `user`) 
                    VALUES (
                        '" . cleanInput($_POST['new-category']) . "', 
                        'gallery', 
                        '$currentUserId'
                    )";
            mysql_query($sql) or displaySQLError(
                'New Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
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
            '" . cleanInput($_POST['category']) . "', 
            '$currentUserId'
        )";
mysql_query($sql) or displaySQLError(
    'Add Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
);

// Update the filename and update the photo record in DB
// We insert above and update below so we can make sure that the filename of
// the photo is the same as the photo id
$new_id     = mysql_insert_id();
$filetype   = $_FILES['medium']['type'];
$extention  = $known_photo_types[$filetype];
$filename   = $new_id . "." . $extention;

$sql = "UPDATE `fcms_gallery_photos` 
        SET `filename` = '" . cleanInput($filename) . "' 
        WHERE `id` = '" . cleanInput($new_id) . "'";
mysql_query($sql) or displaySQLError(
    'Update Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
);

// Loop through each photo (medium, small)
foreach ($file_param_name AS $file) {

    // Create new member directory if needed
    if (!file_exists("photos/member$currentUserId")) {
        mkdir("photos/member$currentUserId");
    }

    if ($file == 'small') {
        $dest_path = "photos/member$currentUserId/tb_$filename";
    } else {
        $dest_path = "photos/member$currentUserId/$filename";
    }

    if (move_uploaded_file($_FILES[$file]['tmp_name'], $dest_path)) {
        // Set up the session vars to send to edit page
        // only on medium, so we don't get both full size and thumbnail
        if ($file == 'medium') {
            $_SESSION['photos'][] = array(
                'id' => $new_id,
                'filename' => $filename,
                'category' => $_POST['category']
            );
        }
        echo "success";
    } else{
        echo "failure";
    }
}
?>
