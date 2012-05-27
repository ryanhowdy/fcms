<?php
/**
 * Photo Gallery
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

init('gallery/');

// Globals
$gallery = new PhotoGallery($fcmsUser->id);

$TMPL = array(
    'currentUserId' => $fcmsUser->id,
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Photo Gallery'),
    'path'          => URL_PREFIX,
    'displayname'   => $fcmsUser->displayName,
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

control();
exit();

/**
 * control 
 * 
 * The controlling structure for this script.
 * 
 * @return void
 */
function control ()
{
    // Edit Photo
    if (isset($_POST['editphoto']))
    {
        displayEditPhotoForm();
    }
    elseif (isset($_POST['add_editphoto']))
    {
        displayEditPhotoSubmit();
    }
    // Delete Photo
    elseif (isset($_POST['deletephoto']))
    {
        if (isset($_GET['confirmed']))
        {
            displayDeletePhotoSubmit();
        }
        else
        {
            displayConfirmDeletePhotoForm();
        }
    }
    // Delete Category
    elseif (isset($_POST['delcat']) || isset($_GET['delcat']))
    {
        if (isset($_GET['confirmed']))
        {
            displayDeleteCategorySubmit();
        }
        else
        {
            displayConfirmDeleteCategoryForm();
        }
    }
    // Delete Comment
    elseif (isset($_POST['delcom']))
    {
        if (isset($_GET['confirmed']))
        {
            displayDeleteCommentSubmit();
        }
        else
        {
            displayConfirmDeleteCommentForm();
        }
    }
    // Mass Tagging
    elseif (isset($_GET['tag']))
    {
        displayMassTagForm();
    }
    elseif (isset($_POST['submit_mass_tag']))
    {
        displayMassTagFormSubmit();
    }
    // Description
    elseif (isset($_GET['description']))
    {
        displayEditDescriptionForm();
    }
    elseif (isset($_POST['save-description']))
    {
        displayEditDescriptionFormSubmit();
    }
    // Actions
    elseif (isset($_GET['action']))
    {
        checkActionPermissions();

        if ($_GET['action'] == "upload")
        {
            if (isset($_POST['addphoto']))
            {
                displayUploadFormSubmit();
            }
            elseif (isset($_POST['instagram']))
            {
                displayInstagramUploadFormSubmit();
            }
            else
            {
                displayUploadForm();
            }
        }
        elseif ($_GET['action'] == 'advanced')
        {
            if (isset($_POST['submit_advanced_edit']))
            {
                displayUploadAdvancedFormSubmit();
            }
            else
            {
                displayUploadAdvancedForm();
            }
        }
        elseif ($_GET['action'] == "category")
        {
            if (isset($_POST['editcat']))
            {
                displayEditCategoryFormSubmit();
            }
            else
            {
                displayEditCategoryForm();
            }
        }
    }
    elseif (isset($_POST['addcatcom']))
    {
        displayAddCategoryCommentSubmit();
    }
    elseif (isset($_POST['addcom']))
    {
        displayAddPhotoCommentSubmit();
    }
    elseif (isset($_GET['vote']))
    {
        displayAddVoteSubmit();
    }
    elseif (isset($_GET['search']))
    {
        displaySearchForm();
    }
    // User / Category / Photo - Views
    elseif (isset($_GET['uid']))
    {
        if (isset($_GET['cid']))
        {
            if (isset($_GET['pid']))
            {
                displayPhoto();
            }
            else
            {
                displayCategory();
            }
        }
        else
        {
            displayUserCategory();
        }
    }
    // Frontpage
    else
    {
        displayLatest();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    global $fcmsUser, $TMPL;

    $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    hideUploadOptions(
        \''.T_('Rotate Photo').'\', 
        \''.T_('Use Existing Category').'\',
        \''.T_('Create New Category').'\'
    );
    hidePhotoDetails(\''.T_('More Details').'\');
    deleteConfirmationLink("deletephoto", "'.T_('Are you sure you want to DELETE this Photo?').'");
    deleteConfirmationLinks("gal_delcombtn", "'.T_('Are you sure you want to DELETE this Comment?').'");
    deleteConfirmationLinks("delcategory", "'.T_('Are you sure you want to DELETE this Category?').'");
    initNewWindow();
});
//]]>
</script>';

    include_once getTheme($fcmsUser->id, $TMPL['path']).'header.php';

    echo '
        <div id="gallery" class="centercontent">';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    global $fcmsUser, $TMPL;

    echo '
        </div><!-- #gallery .centercontent -->';

    include_once getTheme($fcmsUser->id, $TMPL['path']).'footer.php';
}

/**
 * displayEditPhotoForm 
 * 
 * @return void
 */
function displayEditPhotoForm ()
{
    global $gallery;

    displayHeader();

    $gallery->displayEditPhotoForm($_POST['photo'], $_POST['url']);

    displayFooter();
}

/**
 * displayEditPhotoSubmit 
 * 
 * @return void
 */
function displayEditPhotoSubmit ()
{
    $uid           = (int)$_GET['uid'];
    $photo_caption = strip_tags($_POST['photo_caption']);
    $photo_caption = escape_string($photo_caption);
    $category      = strip_tags($_POST['category']);
    $category      = escape_string($category);
    $cid           = $category;
    $pid           = (int)$_POST['photo_id'];

    $sql = "UPDATE `fcms_gallery_photos` 
            SET `category` = '$category', 
                `caption` = '$photo_caption' 
            WHERE `id` = '$pid'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $tagged = isset($_POST['tagged'])            ? $_POST['tagged']            : null;
    $prev   = isset($_POST['prev_tagged_users']) ? $_POST['prev_tagged_users'] : null;

    if (!tagMembersInPhoto($pid, $tagged, $prev))
    {
        // error handled by tagMembersInPhoto()
        return;
    }

    $_SESSION['message'] = 1;

    header('Location: index.php?uid='.$uid.'&cid='.$cid.'&pid='.$pid);
}

/**
 * tagMembersInPhoto 
 * 
 * Will tag a group of members in a photo. Will also remove members who were 
 * tagged, but now are not.
 * 
 * Since 2.9 - Adds a new record to the notification table.
 * 
 * @param int   $photoId           Id of photo
 * @param array $taggedMembers     Array of member id's who are being tagged
 * @param array $prevTaggedMembers Array of member id's who are being untagged
 * 
 * @return boolean
 */
function tagMembersInPhoto ($photoId, $taggedMembers = null, $prevTaggedMembers = null)
{
    $ids = getAddRemoveTaggedMembers($taggedMembers, $prevTaggedMembers);
    if ($ids === false)
    {
        $error = T_('Invalid tagged member data.');

        displayHeader();
        echo '<div class="error-alert">'.$error.'</div>';
        logError(__FILE__.' ['.__LINE__.'] - '.$error);
        displayFooter();

        return false;
    }

    // Nothing to add or remove
    if ($ids === true)
    {
        return true;
    }

    if (count($ids['add']) > 0)
    {
        $values = implode(",$photoId),(", $ids['add']);

        $sql = "INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`) 
                VALUES ($values, $photoId)";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFoote();
            return false;
        }

        addTaggedNotifications($photoId, $ids['add']);
    }

    if (count($ids['remove']) > 0)
    {
        $in = implode(",", $ids['remove']);

        $sql = "DELETE FROM `fcms_gallery_photos_tags` 
                WHERE `photo` = '$photoId' 
                AND `user` IN ($in)";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return false;
        }
    }

    return true;
}

/**
 * addTaggedNotifications 
 * 
 * @param int   $photoId 
 * @param array $ids 
 * 
 * @return void
 */
function addTaggedNotifications ($photoId, $ids)
{
    // Get photo info
    $sql = "SELECT `user`, `category`, `filename`
            FROM `fcms_gallery_photos`
            WHERE `id` = '$photoId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $photoInfo = array();
    $photoInfo = mysql_fetch_assoc($result);
    $values    = '';
    $data      = $photoInfo['user'].':'.$photoInfo['category'].':'.$photoId.':'.$photoInfo['filename'];

    foreach ($ids as $id)
    {
        $values .= "('$id', '".$photoInfo['user']."', 'tagged_photo', '$data', 0, NOW(), NOW()),";
    }

    if (strlen($values) > 0)
    {
        $values = substr($values, 0, -1); // remove trailing comma

        $sql = "INSERT INTO `fcms_notification` (`user`, `created_id`, `notification`, `data`, `read`, `created`, `updated`)
                VALUES $values";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }
}

/**
 * displayConfirmDeletePhotoForm 
 * 
 * @return void
 */
function displayConfirmDeletePhotoForm ()
{
    displayHeader();

    echo '
                <div class="info-alert">
                    <form action="index.php?confirmed=1" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this Photo?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input style="float:left;" type="submit" id="deletephoto" name="deletephoto" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="index.php?'.$_POST['url'].'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    displayFooter();
}

/**
 * displayDeletePhotoSubmit 
 * 
 * @return void
 */
function displayDeletePhotoSubmit ()
{
    global $gallery;

    $cleanPhotoId = (int)$_POST['photo'];
    $uploadsPath  = getUploadsAbsolutePath();

    // Get photo info
    $sql = "SELECT `user`, `category`, `filename` 
            FROM `fcms_gallery_photos` 
            WHERE `id` = '$cleanPhotoId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $filerow = mysql_fetch_array($result);

    $photoFilename = $filerow['filename'];
    $photoUserId   = $filerow['user'];
    $photoCategory = $filerow['category'];
    
    // Remove the photo from the DB
    $sql = "DELETE FROM `fcms_gallery_photos` 
            WHERE `id` = '$cleanPhotoId'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    // Remove any comments for this photo
    $sql = "DELETE FROM `fcms_gallery_photo_comment` 
            WHERE `photo` = '$cleanPhotoId'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }
    
    // Remove the Photo from the server
    unlink($uploadsPath.'photos/member'.$photoUserId.'/'.basename($photoFilename));
    unlink($uploadsPath.'photos/member'.$photoUserId.'/tb_'.basename($photoFilename));

    if ($gallery->usingFullSizePhotos())
    {
        unlink($uploadsPath.'photos/member'.$photoUserId.'/full_'.basename($photoFilename));
    }

    $_SESSION['message'] = 1;

    header("Location: index.php?uid=$photoUserId&cid=$photoCategory");
}

/**
 * checkActionPermissions 
 * 
 * @return void
 */
function checkActionPermissions ()
{
    global $fcmsUser;

    $access = checkAccess($fcmsUser->id);

    // Catch users who can't upload photos, create categories, etc.
    if (   $access == NON_POSTER_USER
        || $access == PHOTOGRAPHER_USER
        || $access == GUEST_USER
        || $access == NON_EDIT_USER
    )
    {
        displayHeader();

        echo '
            <div class="error-alert">'.T_('You do NOT have access to perform this action.').'</div>';

        displayFooter();

        die();
    }
}

/**
 * displayUploadForm 
 * 
 * @return void
 */
function displayUploadForm ()
{
    global $fcmsUser, $gallery;

    displayHeader();

    $gallery->displayGalleryMenu('none');

    if (isset($_SESSION['error_message']))
    {
        echo '
            <div class="error-alert">
                '.$_SESSION['error_message'].'
            </div>';

        unset($_SESSION['error_message']);
    }

    // Turn on advanced uploader
    if (isset($_GET['advanced']))
    {
        $sql = "UPDATE `fcms_user_settings`
                SET `advanced_upload` = '1'
                WHERE `user` = '$fcmsUser->id'";
        if (!mysql_query($sql))
        {
            displaySqlError($sql, mysql_error());
        }
    }

    // Special upload type?
    if (isset($_GET['type']))
    {
        if ($_GET['type'] == 'instagram')
        {
            $gallery->displayInstagramUploadForm();
            displayFooter();
            return;
        }
    }

    // Advanced Uploader
    if (usingAdvancedUploader($fcmsUser->id))
    {
        $gallery->displayJavaUploadForm();
    }
    // Basic Uploader
    else
    {
        $overrideMemoryLimit = isset($_GET['memory']) ? true : false;

        $gallery->displayUploadForm($overrideMemoryLimit);
    }

    displayFooter();
}

/**
 * displayUploadFormSubmit 
 * 
 * @return void
 */
function displayUploadFormSubmit ()
{
    global $gallery, $fcmsUser;

    // Catch photos that are too large
    if ($_FILES['photo_filename']['error'] == 1)
    {
        $max  = ini_get('upload_max_filesize');
        $link = 'index.php?action=upload&amp;advanced=1';

        $_SESSION['error_message']  = '<p>'.sprintf(T_('Your photo exceeds the maximum size allowed by your PHP settings [%s].'), $max).'</p>';
        $_SESSION['error_message'] .= '<p>'.sprintf(T_('Would you like to use the <a href="%s">Advanced Photo Uploader</a> instead?.'), $link).'</p>';

        header('Location: index.php?action=upload');
        return;
    }

    // Make sure we have a category
    if (empty($_POST['new-category']) && empty($_POST['category']))
    { 
        $_SESSION['error_message']  = '<p>'.T_('You must choose a category first.').'</p>';

        header('Location: index.php?action=upload');
        return;
    }

    // Make sure we have a photo
    if ($_FILES['photo_filename']['error'] == 4)
    {
        $_SESSION['error_message']  = '<p>'.T_('You must choose a photo first.').'</p>';

        header('Location: index.php?action=upload');
        return;
    }

    // Create a new category
    if (!empty($_POST['new-category']))
    {
        $newCategory = strip_tags($_POST['new-category']);
        $newCategory = escape_string($newCategory);

        $sql = "INSERT INTO `fcms_category`(`name`, `type`, `user`) 
                VALUES (
                    '$newCategory', 
                    'gallery', 
                    '$fcmsUser->id'
                )";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        $cleanCategory = mysql_insert_id();
    }
    // Existing category
    else
    {
        $cleanCategory = (int)$_POST['category'];
    }

    // Rotate photo
    $cleanRotate = '0';
    if (isset($_POST['rotate']))
    {
        $cleanRotate = escape_string($_POST['rotate']);
    }

    $caption      = strip_tags($_POST['photo_caption']);
    $cleanCaption = escape_string($caption);

    $memory = isset($_POST['memory_override']) ? true : false;

    displayHeader();

    $gallery->displayGalleryMenu('none');

    // Upload photo
    $newPhotoId = $gallery->uploadPhoto($cleanCategory, $_FILES['photo_filename'], $cleanCaption, $cleanRotate, $memory);

    // Upload failed
    if ($newPhotoId == false)
    {
        displayFooter();
        return;
    }

    // Tag photo
    if (isset($_POST['tagged']))
    {
        if (!tagMembersInPhoto($newPhotoId, $_POST['tagged']))
        {
            // error handled by tagMembersInPhoto()
            return;
        }
    }

    // Email members
    $sql = "SELECT u.`email`, s.`user` 
            FROM `fcms_user_settings` AS s, `fcms_users` AS u 
            WHERE `email_updates` = '1'
            AND u.`id` = s.`user`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) > 0)
    {
        while ($r = mysql_fetch_array($result))
        {
            $name          = getUserDisplayName($fcmsUser->id);
            $to            = getUserDisplayName($r['user']);
            $subject       = sprintf(T_('%s has added a new photo.'), $name);
            $email         = $r['email'];
            $url           = getDomainAndDir();
            $email_headers = getEmailHeaders();

            $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'index.php?uid='.$fcmsUser->id.'&cid='.$cleanCategory.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
            mail($email, $subject, $msg, $email_headers);
        }
    }

    displayFooter();
}

/**
 * displayInstagramUploadFormSubmit 
 * 
 * @return void
 */
function displayInstagramUploadFormSubmit ()
{
    global $gallery, $fcmsUser;

    // Turn on auto upload for Instagram
    if (isset($_POST['automatic']))
    {
        $sql = "UPDATE `fcms_user_settings`
                SET `instagram_auto_upload` = '1'
                WHERE `user` = '$fcmsUser->id'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        displayHeader();
        $gallery->displayGalleryMenu('none');

        echo '
            <div class="info-alert">
                <p>'.T_('Your Instagram photos will be automatically imported into the site soon.').'</p>
            </div>';

        displayFooter();

        return;
    }
    // Turn off auto upload for Instagram
    elseif (!isset($_POST['photos']))
    {
        $sql = "UPDATE `fcms_user_settings`
                SET `instagram_auto_upload` = '0'
                WHERE `user` = '$fcmsUser->id'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        displayHeader();
        $gallery->displayGalleryMenu('none');

        echo '
            <div class="info-alert">
                <p>'.T_('Your Instagram photos will no longer be automatically imported into the site soon.').'</p>
            </div>';

        displayFooter();

        return;
    }


    // Upload individual photos
    if (isset($_POST['photos']))
    {
        $categoryId  = getUserInstagramCategory($fcmsUser->id);
        $existingIds = getExistingInstagramIds();

        foreach ($_POST['photos'] AS $data)
        {
            list($sourceId, $thumbnail, $medium, $full, $caption) = explode('|', $data);

            // Skip existing photos
            if (isset($existingIds[$sourceId]))
            {
                continue;
            }

            // Save external paths
            $sql = "INSERT INTO `fcms_gallery_external_photo`
                        (`source_id`, `thumbnail`, `medium`, `full`)
                    VALUES
                        ('$sourceId', '$thumbnail', '$medium', '$full')";

            $result = mysql_query($sql);
            if (!$result)
            {
                displayHeader();
                displaySqlError($sql, mysql_error());
                displayFooter();
                return;
            }

            $id = mysql_insert_id();

            // Insert new photo
            $sql = "INSERT INTO `fcms_gallery_photos`
                        (`date`, `external_id`, `caption`, `category`, `user`)
                    VALUES
                        (NOW(), '$id', '$caption', '$categoryId', '$fcmsUser->id')";

            $result = mysql_query($sql);
            if (!$result)
            {
                displayHeader();
                displaySqlError($sql, mysql_error());
                displayFooter();
                return;
            }
        }


        // Email members
        $sql = "SELECT u.`email`, s.`user` 
                FROM `fcms_user_settings` AS s, `fcms_users` AS u 
                WHERE `email_updates` = '1'
                AND u.`id` = s.`user`";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        if (mysql_num_rows($result) > 0)
        {
            while ($r = mysql_fetch_array($result))
            {
                $name          = getUserDisplayName($fcmsUser->id);
                $to            = getUserDisplayName($r['user']);
                $subject       = sprintf(T_('%s has added new photos.'), $name);
                $email         = $r['email'];
                $url           = getDomainAndDir();
                $email_headers = getEmailHeaders();

                $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'index.php?uid='.$fcmsUser->id.'&cid='.$categoryId.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
                mail($email, $subject, $msg, $email_headers);
            }
        }

        header('Location: index.php?uid='.$fcmsUser->id.'&cid='.$categoryId);
    }
}

/**
 * displayUploadAdvancedForm 
 * 
 * @return void
 */
function displayUploadAdvancedForm ()
{
    global $gallery;

    displayHeader();

    $gallery->displayAdvancedUploadEditForm();

    // clear the photos in the session
    unset($_SESSION['photos']);
    unset($_SESSION['mass_photos_category']);

    displayFooter();
}

/**
 * displayUploadAdvancedFormSubmit 
 * 
 * @return void
 */
function displayUploadAdvancedFormSubmit ()
{
    global $fcmsUser;

    // Categories should all be the same
    $cleanCategory = (int)$_POST['category'][0];

    // Loop through each photo
    for ($i=0; $i < count($_POST['id']); $i++)
    {
        $caption      = strip_tags($_POST['caption'][$i]);
        $cleanCaption = escape_string($caption);
        $cleanId      = (int)$_POST['id'][$i];

        // Update the caption
        $sql = "UPDATE `fcms_gallery_photos` 
                SET `category` = '$cleanCategory', 
                    `caption`  = '$cleanCaption' 
                WHERE `id` = '$cleanId'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        // Tag members
        if (isset($_POST['tagged']))
        {
            if (isset($_POST['tagged'][$i]))
            {
                if (!tagMembersInPhoto($cleanId, $_POST['tagged'][$i]))
                {
                    // error handled by tagMembersInPhoto()
                    return;
                }
            }
        }
    }

    header("Location: index.php?uid=$fcmsUser->id&cid=$cleanCategory");
}

/**
 * displayEditCategoryFormSubmit 
 * 
 * @return void
 */
function displayEditCategoryFormSubmit ()
{
    $categoryId        = (int)$_POST['cid'];
    $categoryName      = strip_tags($_POSt['cat_name']);
    $cleanCategoryName = escape_string($categoryName);

    if (empty($categoryName))
    {
        displayHeader();
        echo '
            <p class="error-alert">'.T_('Category name cannot be blank.').'</p>';
        displayFooter();
    }

    $sql = "UPDATE fcms_category 
            SET `name` = '$cleanCategoryName' 
            WHERE `id` = '$categoryId'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $_SESSION['message'] = sprintf(T_('The Category %s was Updated Successfully'), "<b>".$categoryName."</b>");

    header("Location: index.php?action=category");
}

/**
 * displayEditCategoryForm 
 * 
 * @return void
 */
function displayEditCategoryForm ()
{
    global $gallery;

    displayHeader();

    if (isset($_SESSION['message']))
    {
        displayOkMessage($_SESSION['message']);

        unset($_SESSION['message']);
    }

    $gallery->displayGalleryMenu();
    $gallery->displayCategoryForm();

    displayFooter();
}

/**
 * displayConfirmDeleteCategoryForm 
 * 
 * @return void
 */
function displayConfirmDeleteCategoryForm ()
{
    displayHeader();

    echo '
                <div class="info-alert">
                    <form action="index.php?confirmed=1" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this category?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="cid" value="'.(int)$_POST['cid'].'"/>
                            <input style="float:left;" type="submit" id="delcat" name="delcat" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="index.php?action=category">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    displayFooter();
}

/**
 * displayDeleteCategorySubmit 
 * 
 * @return void
 */
function displayDeleteCategorySubmit ()
{
    global $fcmsUser;

    $cid = 0;

    if (isset($_GET['delcat']))
    {
        $cid = (int)$_GET['delcat'];
    }
    elseif (isset($_POST['cid']))
    {
        $cid = (int)$_POST['cid'];
    }
    else
    {
        displayHeader();
        echo '<p class="error-alert">'.T_('Missing or invalid id.').'</p>';
        displayFooter();
        return;
    }

    // Get category info
    $sql = "SELECT `user`
            FROM `fcms_category`
            WHERE `id` = '$cid'
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $row = mysql_fetch_array($result);

    // Do you permission to delete?
    if ($fcmsUser->id != $row['user'])
    {
        displayHeader();
        echo '<p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';
        displayFooter();
        return;
    }

    $sql = "DELETE FROM fcms_category 
            WHERE `id` = '$cid'
            AND `user` = '$fcmsUser->id'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $_SESSION['message'] = T_('Category Deleted Successfully');

    header("Location: index.php?action=category");
}

/**
 * displayMassTagForm 
 * 
 * @return void
 */
function displayMassTagForm ()
{
    global $gallery;

    displayHeader();

    $category = (int)$_GET['tag'];
    $user     = (int)$_GET['user'];

    $gallery->displayMassTagCategory($category, $user);

    displayFooter();
}

/**
 * displayMassTagFormSubmit 
 * 
 * @return void
 */
function displayMassTagFormSubmit ()
{
    $uid      = (int)$_GET['uid'];
    $cid      = (int)$_GET['cid'];
    $photos   = array();
    $photos1  = array();
    $photos2  = array();

    // Get all photo ids
    if (isset($_POST['tagged']))
    {
        $photos1 = array_keys($_POST['tagged']);
    }
    if (isset($_POST['prev_tagged_users']))
    {
        $photos2 = array_keys($_POST['prev_tagged_users']);
    }

    $photos = array_merge($photos1, $photos2);
    $photos = array_unique($photos);

    // Loop through each photo
    foreach ($photos as $pid)
    {
        $tagged = isset($_POST['tagged'][$pid])            ? $_POST['tagged'][$pid]            : null;
        $prev   = isset($_POST['prev_tagged_users'][$pid]) ? $_POST['prev_tagged_users'][$pid] : null;

        if (!tagMembersInPhoto($pid, $tagged, $prev))
        {
            // error handled by tagMembersInPhoto()
            return;
        }
    }

    
    $_SESSION['message'] = 1;

    header("Location: index.php?uid=$uid&cid=$cid");
}

/**
 * displayPhoto 
 * 
 * @return void
 */
function displayPhoto ()
{
    global $gallery;

    displayHeader();

    if (isset($_SESSION['message']))
    {
        unset($_SESSION['message']);

        displayOkMessage();
    }

    $uid = (int)$_GET['uid'];
    $cid = $_GET['cid']; // not always an #
    $pid = (int)$_GET['pid'];

    $gallery->showPhoto($uid, $cid, $pid);

    displayFooter();
}

/**
 * displayCategory 
 * 
 * @return void
 */
function displayCategory ()
{
    global $gallery;

    displayHeader();

    if (isset($_SESSION['message']))
    {
        unset($_SESSION['message']);

        displayOkMessage();
    }

    $page = getPage();

    $gallery->displayGalleryMenu($_GET['uid'], $_GET['cid']);
    $gallery->showCategories($page, $_GET['uid'], $_GET['cid']);

    displayFooter();
}

/**
 * displayUserCategory 
 * 
 * @return void
 */
function displayUserCategory ()
{
    global $gallery;

    displayHeader();

    $page = getPage();

    $gallery->displayGalleryMenu($_GET['uid']);
    $gallery->showCategories($page, $_GET['uid']);

    displayFooter();
}

/**
 * displayAddCategoryCommentSubmit 
 * 
 * @return void
 */
function displayAddCategoryCommentSubmit ()
{
    global $fcmsUser;

    $uid      = (int)$_GET['uid'];
    $cid      = (int)$_GET['cid'];
    $com      = ltrim($_POST['comment']);
    $com      = strip_tags($com);
    $cleanCom = escape_string($com);

    if (!empty($com))
    {
        $sql = "INSERT INTO `fcms_gallery_category_comment` (
                    `category_id`, `comment`, `created`, `created_id`
                ) VALUES (
                    '$cid', 
                    '$cleanCom', 
                    NOW(), 
                    '$fcmsUser->id'
                )";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    $commentId = mysql_insert_id();

    header('Location: index.php?uid='.$uid.'&cid='.$cid.'#comment'.$commentId);
}

/**
 * displayAddPhotoCommentSubmit 
 * 
 * @return void
 */
function displayAddPhotoCommentSubmit ()
{
    global $fcmsUser;

    $uid      = (int)$_GET['uid'];
    $cid      = $_GET['cid']; // not always an #
    $pid      = (int)$_GET['pid'];
    $com      = ltrim($_POST['post']);
    $com      = strip_tags($com);
    $cleanCom = escape_string($com);

    if (!empty($com))
    {
        $sql = "INSERT INTO `fcms_gallery_photo_comment` (
                    `photo`, `comment`, `date`, `user`
                ) VALUES (
                    '$pid', 
                    '$cleanCom', 
                    NOW(), 
                    '$fcmsUser->id'
                )";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    $commentId = mysql_insert_id();

    header('Location: index.php?uid='.$uid.'&cid='.$cid.'&pid='.$pid.'#comment'.$commentId);
}

/**
 * displayAddVoteSubmit 
 * 
 * @return void
 */
function displayAddVoteSubmit ()
{
    $uid  = (int)$_GET['uid'];
    $cid  = $_GET['cid']; // not always an #
    $pid  = (int)$_GET['pid'];
    $vote = (int)$_GET['vote'];

    $sql = "UPDATE `fcms_gallery_photos` 
            SET `votes` = `votes`+1, 
                `rating` = `rating`+$vote 
            WHERE `id` = '$pid'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    header('Location: index.php?uid='.$uid.'&cid='.$cid.'&pid='.$pid);
}

/**
 * displayDeleteCommentSubmit 
 * 
 * @return void
 */
function displayDeleteCommentSubmit ()
{
    $uid = (int)$_POST['uid'];
    $cid = $_POST['cid']; // not always an #
    $pid = (int)$_POST['pid'];
    $id  = (int)$_POST['id'];

    $sql = "DELETE FROM `fcms_gallery_photo_comment` 
            WHERE `id` = '$id'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    header('Location: index.php?uid='.$uid.'&cid='.$cid.'&pid='.$pid);
}

/**
 * displayConfirmDeleteCommentForm 
 * 
 * @return void
 */
function displayConfirmDeleteCommentForm ()
{
    $uid = (int)$_GET['uid'];
    $cid = $_GET['cid']; // not always an #
    $pid = (int)$_GET['pid'];
    $id  = (int)$_POST['id'];
    displayHeader();

    echo '
                <div class="info-alert">
                    <form action="index.php?confirmed=1" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this Comment?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="uid" value="'.$uid.'"/>
                            <input type="hidden" name="cid" value="'.cleanOutput($cid).'"/>
                            <input type="hidden" name="pid" value="'.$pid.'"/>
                            <input type="hidden" name="id" value="'.$id.'"/>
                            <input style="float:left;" type="submit" id="delcom" name="delcom" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="index.php?uid='.$uid.'&amp;cid='.cleanOutput($cid).'&amp;pid='.$pid.'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    displayFooter();
}

/**
 * displaySearchForm 
 * 
 * @return void
 */
function displaySearchForm ()
{
    global $gallery;

    displayHeader();

    $gallery->displayGalleryMenu();
    $gallery->displaySearchForm();

    displayFooter();
}

/**
 * displayLatest 
 * 
 * @return void
 */
function displayLatest ()
{
    global $gallery;

    displayHeader();

    $gallery->displayGalleryMenu();

    $foundPhotos = $gallery->displayLatestCategories();

    if ($foundPhotos)
    {
        $gallery->showCategories(-1, '0', 'comments');

        echo '
            <p class="alignright"><a class="rss" href="../rss.php?feed=gallery">'.T_('RSS Feed').'</a></p>';
    }

    displayFooter();
}

/**
 * displayEditDescriptionForm 
 * 
 * @param int $uid 
 * @param int $cid 
 * 
 * @return void
 */
function displayEditDescriptionForm ()
{
    displayHeader();

    $uid = (int)$_GET['user'];
    $cid = (int)$_GET['description'];

    $sql = "SELECT `user`, `description`
            FROM `fcms_category`
            WHERE `id` = '$cid'";
    
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        echo '
            <div class="error-alert">'.T_('Invalid ID.').'</div>';
        displayFooter();
        return;
    }

    $row = mysql_fetch_assoc($result);

    // Can the member edit description
    if ($row['user'] != $uid)
    {
        echo '
            <div class="error-alert">'.T_('You do NOT have access to perform this action.').'</div>';

        displayFooter();
        return;
    }

    echo '
            <fieldset>
                <legend><span>'.T_('Category Description').'</span></legend>
                <form action="index.php" method="post">
                    <div class="field-row">
                        <div class="field-label"></div>
                        <div class="field-widget">
                            <textarea id="description" name="description" cols="63" rows="10">'.$row['description'].'</textarea>
                        </div>
                    </div>
                    <p>
                        <input type="hidden" name="uid" id="uid" value="'.$uid.'"/>
                        <input type="hidden" name="cid" id="cid" value="'.$cid.'"/>
                        <input class="sub1" type="submit" name="save-description" value="'.T_('Edit').'"/> 
                        '.T_('or').' <a href="index.php?uid='.$uid.'&amp;cid='.$cid.'">'.T_('Cancel').'</a>
                    </p>
                </form>
            </fieldset>';

    displayFooter();
}

/**
 * displayEditDescriptionFormSubmit 
 * 
 * @return void
 */
function displayEditDescriptionFormSubmit ()
{
    $uid         = (int)$_POST['uid'];
    $cid         = (int)$_POST['cid'];
    $description = strip_tags($_POST['description']);
    $description = escape_string($description);

    $sql = "UPDATE `fcms_category`
            SET `description` = '$description'
            WHERE `id` = $cid";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $_SESSION['message'] = T_('Changes Updated Successfully');

    header('Location: index.php?uid='.$uid.'&cid='.$cid);
}
