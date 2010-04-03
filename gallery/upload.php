<?php
session_start();
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');

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

// Create a new photo record in DB
$sql = "INSERT INTO `fcms_gallery_photos` (`date`, `category`, `user`) "
     . "VALUES(NOW(), ".$_POST['category'].", ".$_SESSION['login_id'].")";
mysql_query($sql) or displaySQLError(
    'Add Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
);

// Update the filename and update the photo record in DB
// We an insert above and update below so we can make sure that the filename of
// the photo is the same as the photo id
$new_id = mysql_insert_id();
$filetype = $_FILES['medium']['type'];
$extention = $known_photo_types[$filetype];
$filename = $new_id . "." . $extention;
$sql = "UPDATE `fcms_gallery_photos` "
     . "SET `filename`='" . addslashes($filename) . "' "
     . "WHERE id = " . addslashes($new_id);
mysql_query($sql) or displaySQLError(
    'Update Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
);

foreach ($file_param_name AS $file) {
    // Create new member directory if needed
    if (!file_exists("photos/member" . $_SESSION['login_id'])) {
        mkdir("photos/member" . $_SESSION['login_id']);
    }

    if ($file == 'small') {
        $dest_path = "photos/member".$_SESSION['login_id']."/tb_".$filename;
    } else {
        $dest_path = "photos/member".$_SESSION['login_id']."/".$filename;
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
    ?>
    <html>
    <body>
        <h1>GET content</h1>
        <pre><?print_r( $_GET );?></pre>
        <h1>POST content</h1>
        <pre><?print_r( $_POST );?></pre>
        <h1>FILES content</h1>
        <pre><?print_r( $_FILES );?></pre>
    </body>
<?php
}
?>
