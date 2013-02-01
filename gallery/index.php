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

load('gallery', 'socialmedia', 'datetime', 'image', 'picasa');

init('gallery/');

// Globals
$img     = new Image($fcmsUser->id);
$gallery = new PhotoGallery($fcmsError, $fcmsDatabase, $fcmsUser, $img);
$page    = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $gallery, $img);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsPhotoGallery;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsPhotoGallery, $fcmsImage)
    {
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;
        $this->fcmsPhotoGallery = $fcmsPhotoGallery;
        $this->fcmsImage        = $fcmsImage;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => cleanOutput(getSiteName()),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Photo Gallery'),
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        $this->control();
    }

    /**
     * control 
     * 
     * The controlling structure for this script.
     * 
     * @return void
     */
    function control ()
    {
        if (isset($_POST['ajax']))
        {
            $ajax = $_POST['ajax'];

            if ($ajax == 'picasa_photos')
            {
                $this->getAjaxPicasaPhotos();
            }
            elseif ($ajax == 'more_picasa_photos')
            {
                $this->getAjaxMorePicasaPhotos();
            }
            elseif ($ajax == 'picasa_albums')
            {
                $this->getAjaxPicasaAlbums();
            }

            return;
        }
        // Edit Photo
        if (isset($_POST['editphoto']))
        {
            $this->displayEditPhotoForm();
        }
        elseif (isset($_POST['add_editphoto']))
        {
            $this->displayEditPhotoSubmit();
        }
        // Delete Photo
        elseif (isset($_POST['deletephoto']))
        {
            if (isset($_GET['confirmed']))
            {
                $this->displayDeletePhotoSubmit();
            }
            else
            {
                $this->displayConfirmDeletePhotoForm();
            }
        }
        // Delete Category
        elseif (isset($_POST['delcat']) || isset($_GET['delcat']))
        {
            if (isset($_GET['confirmed']))
            {
                $this->displayDeleteCategorySubmit();
            }
            else
            {
                $this->displayConfirmDeleteCategoryForm();
            }
        }
        // Delete Comment
        elseif (isset($_POST['delcom']))
        {
            if (isset($_GET['confirmed']))
            {
                $this->displayDeleteCommentSubmit();
            }
            else
            {
                $this->displayConfirmDeleteCommentForm();
            }
        }
        // Mass Tagging
        elseif (isset($_GET['tag']))
        {
            $this->displayMassTagForm();
        }
        // Description
        elseif (isset($_GET['description']))
        {
            $this->displayEditDescriptionForm();
        }
        // Mass Edit Category
        elseif (isset($_GET['edit-category']))
        {
            $this->displayEditCategoryForm();
        }
        elseif (isset($_POST['save-edit-category']))
        {
            $this->displayEditCategoryFormSubmit();
        }
        // Actions
        elseif (isset($_GET['action']))
        {
            $this->checkActionPermissions();

            if ($_GET['action'] == "upload")
            {
                if (isset($_POST['addphoto']))
                {
                    $this->displayUploadFormSubmit();
                }
                elseif (isset($_POST['instagram']))
                {
                    $this->displayInstagramUploadFormSubmit();
                }
                elseif (isset($_POST['picasa']))
                {
                    $this->displayPicasaUploadFormSubmit();
                }
                else
                {
                    $this->displayUploadForm();
                }
            }
            elseif ($_GET['action'] == 'advanced')
            {
                $this->displayUploadAdvancedEditCategory();
            }
            elseif ($_GET['action'] == "category")
            {
                if (isset($_POST['editcat']))
                {
                    $this->displayEditCategoriesFormSubmit();
                }
                else
                {
                    $this->displayEditCategoriesForm();
                }
            }
        }
        elseif (isset($_POST['addcatcom']))
        {
            $this->displayAddCategoryCommentSubmit();
        }
        elseif (isset($_POST['addcom']))
        {
            $this->displayAddPhotoCommentSubmit();
        }
        elseif (isset($_GET['vote']))
        {
            $this->displayAddVoteSubmit();
        }
        elseif (isset($_GET['search']))
        {
            $this->displaySearchForm();
        }
        // User / Category / Photo - Views
        elseif (isset($_GET['uid']))
        {
            if (isset($_GET['cid']))
            {
                if (isset($_GET['pid']))
                {
                    $this->displayPhoto();
                }
                else
                {
                    $this->displayCategory();
                }
            }
            else
            {
                $this->displayUserCategory();
            }
        }
        // Frontpage
        else
        {
            $this->displayLatest();
        }
    }

    /**
     * displayHeader 
     * 
     * @return void
     */
    function displayHeader ()
    {
        $TMPL = $this->fcmsTemplate;

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

        include_once getTheme($this->fcmsUser->id, $TMPL['path']).'header.php';

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
        $TMPL = $this->fcmsTemplate;

        echo '
        </div><!-- #gallery .centercontent -->';

        include_once getTheme($this->fcmsUser->id, $TMPL['path']).'footer.php';
    }

    /**
     * displayEditPhotoForm 
     * 
     * @return void
     */
    function displayEditPhotoForm ()
    {
        $this->displayHeader();
        $this->fcmsPhotoGallery->displayEditPhotoForm($_POST['photo'], $_POST['url']);
        $this->displayFooter();
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
        $category      = strip_tags($_POST['category']);
        $cid           = $category;
        $pid           = (int)$_POST['photo_id'];

        $sql = "UPDATE `fcms_gallery_photos` 
                SET `category` = ?, 
                    `caption` = ?
                WHERE `id` = ?";

        $params = array(
            $category,
            $photo_caption,
            $pid
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $tagged = isset($_POST['tagged'])            ? $_POST['tagged']            : null;
        $prev   = isset($_POST['prev_tagged_users']) ? $_POST['prev_tagged_users'] : null;

        if (!$this->tagMembersInPhoto($pid, $tagged, $prev))
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

            $this->displayHeader();
            echo '<div class="error-alert">'.$error.'</div>';
            logError(__FILE__.' ['.__LINE__.'] - '.$error);
            $this->displayFooter();

            return false;
        }

        // Nothing to add or remove
        if ($ids === true)
        {
            return true;
        }

        if (count($ids['add']) > 0)
        {
            $sql = "INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`) 
                    VALUES ";

            $params = array();

            foreach ($ids['add'] as $userId)
            {
                $sql .= "(?, ?),";

                $params[] = $userId;
                $params[] = $photoId;
            }

            $sql = substr($sql, 0, -1); // remove trailing comma

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return false;
            }

            $this->addTaggedNotifications($photoId, $ids['add']);
        }

        if (count($ids['remove']) > 0)
        {
            // TODO - this should be changed
            $in = implode(",", $ids['remove']);

            $sql = "DELETE FROM `fcms_gallery_photos_tags` 
                    WHERE `photo` = '$photoId' 
                    AND `user` IN ($in)";
            if (!$this->fcmsDatabase->delete($sql))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
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
                WHERE `id` = ?";

        $photoInfo = $this->fcmsDatabase->getRow($sql, $photoId);
        if ($photoInfo === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }


        $sql = "INSERT INTO `fcms_notification` (`user`, `created_id`, `notification`, `data`, `read`, `created`, `updated`)
                VALUES ";

        $params = array();
        $data   = $photoInfo['user'].':'.$photoInfo['category'].':'.$photoId.':'.$photoInfo['filename'];

        foreach ($ids as $id)
        {
            $sql .= "(?, ?, 'tagged_photo', ?, 0, NOW(), NOW()),";

            $params[] = $id;
            $params[] = $photoInfo['user'];
            $params[] = $data;
        }

        if (count($ids) > 0)
        {
            $sql = substr($sql, 0, -1); // remove trailing comma

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
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
        $this->displayHeader();

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

        $this->displayFooter();
    }

    /**
     * displayDeletePhotoSubmit 
     * 
     * @return void
     */
    function displayDeletePhotoSubmit ()
    {
        $photoId     = (int)$_POST['photo'];
        $uploadsPath = getUploadsAbsolutePath();

        // Get photo info
        $sql = "SELECT `user`, `category`, `filename` 
                FROM `fcms_gallery_photos` 
                WHERE `id` = ?";

        $filerow = $this->fcmsDatabase->getRow($sql, $photoId);
        if ($filerow === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $photoFilename = $filerow['filename'];
        $photoUserId   = $filerow['user'];
        $photoCategory = $filerow['category'];
        
        // Remove the photo from the DB
        $sql = "DELETE FROM `fcms_gallery_photos` 
                WHERE `id` = ?";
        if (!$this->fcmsDatabase->delete($sql, $photoId))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // Remove any comments for this photo
        $sql = "DELETE FROM `fcms_gallery_photo_comment` 
                WHERE `photo` = ?";
        if (!$this->fcmsDatabase->delete($sql, $photoId))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $filePath  = $uploadsPath.'photos/member'.$photoUserId.'/'.basename($photoFilename);
        $thumbPath = $uploadsPath.'photos/member'.$photoUserId.'/tb_'.basename($photoFilename);
        $fullPath = $uploadsPath.'photos/member'.$photoUserId.'/full_'.basename($photoFilename);

        // Remove the Photo from the server
        if (file_exists($filePath))
        {
            unlink($filePath);
        }
        if (file_exists($thumbPath))
        {
            unlink($thumbPath);
        }
        if (file_exists($fullPath))
        {
            unlink($fullPath);
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
        $access = $this->fcmsUser->access;

        // Catch users who can't upload photos, create categories, etc.
        if (   $access == NON_POSTER_USER
            || $access == PHOTOGRAPHER_USER
            || $access == GUEST_USER
            || $access == NON_EDIT_USER
        )
        {
            $this->displayHeader();

            echo '
            <div class="error-alert">'.T_('You do NOT have access to perform this action.').'</div>';

            $this->displayFooter();

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
        $this->displayHeader();

        $this->fcmsPhotoGallery->displayGalleryMenu('none');

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
                    WHERE `user` = ?";

            if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id))
            {
                $this->fcmsError->displayError();
            }
        }

        // Special upload type?
        if (isset($_GET['type']))
        {
            if ($_GET['type'] == 'instagram')
            {
                $this->fcmsPhotoGallery->displayInstagramUploadForm();
                $this->displayFooter();
                return;
            }
            if ($_GET['type'] == 'picasa')
            {
                $this->fcmsPhotoGallery->displayPicasaUploadForm();
                $this->displayFooter();
                return;
            }
        }

        // Advanced Uploader
        if (usingAdvancedUploader($this->fcmsUser->id))
        {
            $this->fcmsPhotoGallery->displayJavaUploadForm();
        }
        // Basic Uploader
        else
        {
            $overrideMemoryLimit = isset($_GET['memory']) ? true : false;

            $this->fcmsPhotoGallery->displayUploadForm($overrideMemoryLimit);
        }

        $this->displayFooter();
    }

    /**
     * displayUploadFormSubmit 
     * 
     * @return void
     */
    function displayUploadFormSubmit ()
    {
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

            $sql = "INSERT INTO `fcms_category`(`name`, `type`, `user`) 
                    VALUES (?, 'gallery', ?)";

            $params = array(
                $newCategory,
                $this->fcmsUser->id
            );

            $categoryId = $this->fcmsDatabase->insert($sql, $params);
            if ($categoryId === false)
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }
        // Existing category
        else
        {
            $categoryId = (int)$_POST['category'];
        }

        $rotate  = isset($_POST['rotate']) ? $_POST['rotate'] : '0';
        $caption = strip_tags($_POST['photo_caption']);
        $memory  = isset($_POST['memory_override']) ? true : false;

        $this->displayHeader();

        $this->fcmsPhotoGallery->displayGalleryMenu('none');

        // Upload photo
        $newPhotoId = $this->fcmsPhotoGallery->uploadPhoto($categoryId, $_FILES['photo_filename'], $caption, $rotate, $memory);

        // Upload failed
        if ($newPhotoId == false)
        {
            $this->displayFooter();
            return;
        }

        // Tag photo
        if (isset($_POST['tagged']))
        {
            if (!$this->tagMembersInPhoto($newPhotoId, $_POST['tagged']))
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

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        if (count($rows) > 0)
        {
            $name          = getUserDisplayName($this->fcmsUser->id);
            $subject       = sprintf(T_('%s has added a new photo.'), $name);
            $url           = getDomainAndDir();
            $email_headers = getEmailHeaders();

            foreach ($rows as $r)
            {
                $to    = getUserDisplayName($r['user']);
                $email = $r['email'];

                $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'index.php?uid='.$this->fcmsUser->id.'&cid='.$category.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
                mail($email, $subject, $msg, $email_headers);
            }
        }

        $this->displayFooter();
    }

    /**
     * displayInstagramUploadFormSubmit 
     * 
     * @return void
     */
    function displayInstagramUploadFormSubmit ()
    {
        // Turn on auto upload for Instagram
        if (isset($_POST['automatic']))
        {
            $sql = "UPDATE `fcms_user_settings`
                    SET `instagram_auto_upload` = '1'
                    WHERE `user` = ?";

            if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            $this->displayHeader();
            $this->fcmsPhotoGallery->displayGalleryMenu('none');

            echo '
            <div class="info-alert">
                <p>'.T_('Your Instagram photos will be automatically imported into the site soon.').'</p>
            </div>';

            $this->displayFooter();

            return;
        }
        // Turn off auto upload for Instagram
        elseif (!isset($_POST['photos']))
        {
            $sql = "UPDATE `fcms_user_settings`
                    SET `instagram_auto_upload` = '0'
                    WHERE `user` = ?";

            if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            $this->displayHeader();
            $this->fcmsPhotoGallery->displayGalleryMenu('none');

            echo '
            <div class="info-alert">
                <p>'.T_('Your Instagram photos will no longer be automatically imported into the site soon.').'</p>
            </div>';

            $this->displayFooter();

            return;
        }


        // Upload individual photos
        if (isset($_POST['photos']))
        {
            $categoryId  = getUserInstagramCategory($this->fcmsUser->id);
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
                            (?, ?, ?, ?)";

                $params = array(
                    $sourceId,
                    $thumbnail,
                    $medium,
                    $full
                );

                $id = $this->fcmsDatabase->insert($sql, $params);

                if ($id === false)
                {
                    $this->displayHeader();
                    $this->fcmsError->displayError();
                    $this->displayFooter();
                    return;
                }

                // Insert new photo
                $sql = "INSERT INTO `fcms_gallery_photos`
                            (`date`, `external_id`, `caption`, `category`, `user`)
                        VALUES
                            (NOW(), ?, ?, ?, ?)";

                $params = array(
                    $id,
                    $caption,
                    $categoryId,
                    $this->fcmsUser->id
                );

                if (!$this->fcmsDatabase->insert($sql, $params))
                {
                    $this->displayHeader();
                    $this->fcmsError->displayError();
                    $this->displayFooter();
                    return;
                }
            }


            // Email members
            $sql = "SELECT u.`email`, s.`user` 
                    FROM `fcms_user_settings` AS s, `fcms_users` AS u 
                    WHERE `email_updates` = '1'
                    AND u.`id` = s.`user`";

            $rows = $this->fcmsDatabase->getRows($sql);
            if ($rows === false)
            {
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            if (count($rows) > 0)
            {
                foreach ($rows as $r)
                {
                    $name          = getUserDisplayName($this->fcmsUser->id);
                    $to            = getUserDisplayName($r['user']);
                    $subject       = sprintf(T_('%s has added new photos.'), $name);
                    $email         = $r['email'];
                    $url           = getDomainAndDir();
                    $email_headers = getEmailHeaders();

                    $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'index.php?uid='.$this->fcmsUser->id.'&cid='.$categoryId.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
                    mail($email, $subject, $msg, $email_headers);
                }
            }

            header('Location: index.php?uid='.$this->fcmsUser->id.'&cid='.$categoryId);
        }
    }

    /**
     * displayPicasaUploadFormSubmit 
     * 
     * @return void
     */
    function displayPicasaUploadFormSubmit ()
    {
        // Make sure we have a category
        if (empty($_POST['new-category']) && empty($_POST['category']))
        { 
            $_SESSION['error_message']  = '<p>'.T_('You must choose a category first.').'</p>';

            header('Location: index.php?action=upload&type=picasa');
            return;
        }

        // Make sure we have an album id
        if (empty($_POST['albums']))
        { 
            $_SESSION['error_message']  = '<p>'.T_('No album was selected.').'</p>';

            header('Location: index.php?action=upload&type=picasa');
            return;
        }


        // Make sure we have some photos
        if (empty($_POST['photos']))
        { 
            $_SESSION['error_message']  = '<p>'.T_('You must choose at least one photo.').'</p>';

            header('Location: index.php?action=upload&type=picasa');
            return;
        }

        // Create a new category
        if (!empty($_POST['new-category']))
        {
            $newCategory = strip_tags($_POST['new-category']);

            $sql = "INSERT INTO `fcms_category`(`name`, `type`, `user`) 
                    VALUES (?, 'gallery', ?)";

            $params = array(
                $newCategory,
                $this->fcmsUser->id
            );

            $categoryId = $this->fcmsDatabase->insert($sql, $params);
            if ($categoryId === false)
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }
        // Existing category
        else
        {
            $categoryId = (int)$_POST['category'];
        }

        $token   = getUserPicasaSessionToken($this->fcmsUser->id);
        $albumId = $_POST['albums'];
        $user    = $_POST['picasa_user'];

        $httpClient    = Zend_Gdata_AuthSub::getHttpClient($token);
        $picasaService = new Zend_Gdata_Photos($httpClient, "Google-DevelopersGuide-1.0");

        $fullSizePhotos = $this->fcmsPhotoGallery->usingFullSizePhotos();

        $thumbSizes = '150c,600';
        if ($fullSizePhotos)
        {
            $thumbSizes .= ',d';
        }

        try
        {
            $query = new Zend_Gdata_Photos_AlbumQuery();
            $query->setUser($user);
            $query->setAlbumId($albumId);
            $query->setParam('thumbsize', $thumbSizes);

            $albumFeed = $picasaService->getAlbumFeed($query);
        }
        catch (Zend_Gdata_App_Exception $e)
        {
            $this->fcmsError->add(array(
                'type'    => 'operation',
                'message' => T_('Could not get Picasa data.'),
                'error'   => $e->getMessage(),
                'file'    => __FILE__,
                'line'    => __LINE__,
             ));

            $this->fcmsError->displayError();
            return;
        }

        $newPhotoFilenames = array();

        foreach ($albumFeed as $photo)
        {
            $id = $photo->getGPhotoId()->text;

            if (!in_array($id, $_POST['photos']))
            {
                continue;
            }

            $thumbs    = $photo->getMediaGroup()->getThumbnail();
            $thumbnail = $thumbs[0]->getUrl();
            $medium    = $thumbs[1]->getUrl();
            $full      = $fullSizePhotos ? $thumbs[2]->getUrl() : '';

            // Get filename extension
            $this->fcmsImage->name = $thumbnail;
            $this->fcmsImage->getExtension();

            $extension = $this->fcmsImage->extension;

            // Save photo to db
            $params = array(
                $categoryId,
                $this->fcmsUser->id
            );

            $sql = "INSERT INTO `fcms_gallery_photos`
                        (`date`, `category`, `user`)
                    VALUES 
                        (NOW(), ?, ?)";

            $newPhotoId = $this->fcmsDatabase->insert($sql, $params);
            if ($newPhotoId === false)
            {
                $this->fcmsError->displayError();
                return;
            }

            $newFilename = $newPhotoId.'.'.$extension;

            // Move files to server
            $this->fcmsPhotoGallery->savePhotoFromSource($thumbnail, 'tb_'.$newFilename);
            $this->fcmsPhotoGallery->savePhotoFromSource($medium, $newFilename);
            if ($fullSizePhotos)
            {
                $this->fcmsPhotoGallery->savePhotoFromSource($full, 'full_'.$newFilename);
            }

            $newPhotoFilenames[$newPhotoId] = $newPhotoId.'.'.$extension;
        }

        foreach ($newPhotoFilenames as $id => $filename)
        {
            $sql = "UPDATE `fcms_gallery_photos` 
                    SET `filename` = ?
                    WHERE `id`     = ?";

            if (!$this->fcmsDatabase->update($sql, array($filename, $id)))
            {
                $this->fcmsError->displayError();
                return;
            }
        }

        header("Location: index.php?edit-category=$categoryId&user=".$this->fcmsUser->id);
    }

    /**
     * displayUploadAdvancedEditCategory
     * 
     * Handles the form submission after uploading photos using advanced uploader.
     * This just redirects to the category edit form.
     * 
     * @return void
     */
    function displayUploadAdvancedEditCategory ()
    {
        $category = (int)$_SESSION['mass_photos_category'];
        $user     = $this->fcmsUser->id;

        // Do we have a valid category?
        if (isset($_SESSION['photos']['error']))
        {
            // clear the photos in the session
            unset($_SESSION['photos']);

            $this->displayHeader();
            echo '<div class="error-alert">'.T_('You must create a new category, or select an existing category.').'</div>';
            $this->displayJavaUploadForm('');
            $this->displayFooter();
            return;
        }

        // clear the photos in the session
        unset($_SESSION['photos']);
        unset($_SESSION['mass_photos_category']);

        // redirect to the mass edit category form
        header("Location: index.php?edit-category=$category&user=$user");
    }

    /**
     * displayEditCategoriesFormSubmit 
     * 
     * @return void
     */
    function displayEditCategoriesFormSubmit ()
    {
        $categoryId     = (int)$_POST['cid'];
        $categoryName   = strip_tags($_POST['cat_name']);

        if (empty($categoryName))
        {
            $this->displayHeader();
            echo '
            <p class="error-alert">'.T_('Category name cannot be blank.').'</p>';
            $this->displayFooter();
        }

        $sql = "UPDATE fcms_category 
                SET `name` = ?
                WHERE `id` = ?";

        $params = array(
            $categoryName,
            $categoryId
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $_SESSION['message'] = sprintf(T_('The Category %s was Updated Successfully'), "<b>".$categoryName."</b>");

        header("Location: index.php?action=category");
    }

    /**
     * displayEditCategoriesForm 
     * 
     * @return void
     */
    function displayEditCategoriesForm ()
    {
        $this->displayHeader();

        if (isset($_SESSION['message']))
        {
            displayOkMessage($_SESSION['message']);

            unset($_SESSION['message']);
        }

        $this->fcmsPhotoGallery->displayGalleryMenu();
        $this->fcmsPhotoGallery->displayCategoryForm();

        $this->displayFooter();
    }

    /**
     * displayConfirmDeleteCategoryForm 
     * 
     * @return void
     */
    function displayConfirmDeleteCategoryForm ()
    {
        $this->displayHeader();

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

        $this->displayFooter();
    }

    /**
     * displayDeleteCategorySubmit 
     * 
     * @return void
     */
    function displayDeleteCategorySubmit ()
    {
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
            $this->displayHeader();
            echo '<p class="error-alert">'.T_('Missing or invalid id.').'</p>';
            $this->displayFooter();
            return;
        }

        // Get category info
        $sql = "SELECT `user`
                FROM `fcms_category`
                WHERE `id` = ?
                LIMIT 1";

        $row = $this->fcmsDatabase->getRow($sql, $cid);
        if ($row === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // Do you permission to delete?
        if ($this->fcmsUser->id != $row['user'])
        {
            $this->displayHeader();
            echo '<p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';
            $this->displayFooter();
            return;
        }

        $sql = "DELETE FROM fcms_category 
                WHERE `id` = ?
                AND `user` = ?";

        $params = array(
            $cid,
            $this->fcmsUser->id
        );

        if (!$this->fcmsDatabase->delete($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $_SESSION['message'] = T_('Category Deleted Successfully');

        header("Location: index.php?action=category");
    }

    /**
     * displayEditCategoryForm
     * 
     * @return void
     */
    function displayEditCategoryForm ()
    {
        $this->displayHeader();

        $category = (int)$_GET['edit-category'];
        $user     = (int)$_GET['user'];

        $this->fcmsPhotoGallery->displayEditCategoryForm($category, $user);

        $this->displayFooter();
    }

    /**
     * displayEditCategoryFormSubmit 
     * 
     * @return void
     */
    function displayEditCategoryFormSubmit ()
    {
        $uid = (int)$_GET['uid'];
        $cid = (int)$_GET['cid'];

        // Save description
        if (isset($_POST['description']))
        {
            $description = strip_tags($_POST['description']);

            $sql = "UPDATE `fcms_category`
                    SET `description` = ?
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->update($sql, array($description, $cid)))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        // Save captions
        if (isset($_POST['caption']))
        {
            foreach ($_POST['caption'] as $pid => $caption)
            {
                $pid     = (int)$pid;
                $caption = strip_tags($caption);

                // Update the caption
                $sql = "UPDATE `fcms_gallery_photos` 
                        SET `caption` = ?
                        WHERE `id` = ?";

                $params = array(
                    $caption,
                    $pid,
                );

                if (!$this->fcmsDatabase->update($sql, $params))
                {
                    $this->displayHeader();
                    $this->fcmsError->displayError();
                    $this->displayFooter();
                    return;
                }
            }
        }

        // Save tags
        if (isset($_POST['tagged']))
        {
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

                if (!$this->tagMembersInPhoto($pid, $tagged, $prev))
                {
                    // error handled by tagMembersInPhoto()
                    return;
                }
            }
        }

        $_SESSION['message'] = 1;

        header("Location: index.php?uid=$uid&cid=$cid");
    }

    /**
     * displayMassTagForm 
     * 
     * Prints the mass edit category function with caption and desc turned off.
     * 
     * @return void
     */
    function displayMassTagForm ()
    {
        $this->displayHeader();

        $category = (int)$_GET['tag'];
        $user     = (int)$_GET['user'];

        $this->fcmsPhotoGallery->displayEditCategoryForm(
            $category, 
            $user, 
            array(
                'description' => 1,
                'caption'     => 1
            )
        );

        $this->displayFooter();
    }

    /**
     * displayPhoto 
     * 
     * @return void
     */
    function displayPhoto ()
    {
        $this->displayHeader();

        if (isset($_SESSION['message']))
        {
            unset($_SESSION['message']);

            displayOkMessage();
        }

        $uid = (int)$_GET['uid'];
        $cid = $_GET['cid']; // not always an #
        $pid = (int)$_GET['pid'];

        $this->fcmsPhotoGallery->showPhoto($uid, $cid, $pid);

        $this->displayFooter();
    }

    /**
     * displayCategory 
     * 
     * @return void
     */
    function displayCategory ()
    {
        $this->displayHeader();

        if (isset($_SESSION['message']))
        {
            unset($_SESSION['message']);

            displayOkMessage();
        }

        $page = getPage();

        $this->fcmsPhotoGallery->displayGalleryMenu($_GET['uid'], $_GET['cid']);
        $this->fcmsPhotoGallery->showCategories($page, $_GET['uid'], $_GET['cid']);

        $this->displayFooter();
    }

    /**
     * displayUserCategory 
     * 
     * @return void
     */
    function displayUserCategory ()
    {
        $this->displayHeader();

        $page = getPage();

        $this->fcmsPhotoGallery->displayGalleryMenu($_GET['uid']);
        $this->fcmsPhotoGallery->showCategories($page, $_GET['uid']);

        $this->displayFooter();
    }

    /**
     * displayAddCategoryCommentSubmit 
     * 
     * @return void
     */
    function displayAddCategoryCommentSubmit ()
    {
        $uid       = (int)$_GET['uid'];
        $cid       = (int)$_GET['cid'];
        $com       = ltrim($_POST['comment']);
        $com       = strip_tags($com);
        $commentId = 0;

        if (!empty($com))
        {
            $sql = "INSERT INTO `fcms_gallery_category_comment`
                        (`category_id`, `comment`, `created`, `created_id`)
                    VALUES
                        (?, ?, NOW(), ?)";

            $params = array(
                $cid, 
                $com, 
                $this->fcmsUser->id
            );

            $commentId = $this->fcmsDatabase->insert($sql, $params);
            if ($commentId === false)
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        header('Location: index.php?uid='.$uid.'&cid='.$cid.'#comment'.$commentId);
    }

    /**
     * displayAddPhotoCommentSubmit 
     * 
     * @return void
     */
    function displayAddPhotoCommentSubmit ()
    {
        $uid       = (int)$_GET['uid'];
        $cid       = $_GET['cid']; // not always an #
        $pid       = (int)$_GET['pid'];
        $com       = ltrim($_POST['post']);
        $com       = strip_tags($com);
        $commentId = 0;

        if (!empty($com))
        {
            $sql = "INSERT INTO `fcms_gallery_photo_comment`
                        (`photo`, `comment`, `date`, `user`)
                    VALUES
                        (?, ?, NOW(), ?)";

            $params = array(
                $pid, 
                $com, 
                $this->fcmsUser->id
            );

            $commentId = $this->fcmsDatabase->insert($sql, $params);
            if ($commentId === false)
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

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
                SET `votes` = `votes` + 1, 
                    `rating` = `rating` + $vote 
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->update($sql, $pid))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
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
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->delete($sql, $id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
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
        $this->displayHeader();

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

        $this->displayFooter();
    }

    /**
     * displaySearchForm 
     * 
     * @return void
     */
    function displaySearchForm ()
    {
        $this->displayHeader();

        $this->fcmsPhotoGallery->displayGalleryMenu();
        $this->fcmsPhotoGallery->displaySearchForm();

        $this->displayFooter();
    }

    /**
     * displayLatest 
     * 
     * @return void
     */
    function displayLatest ()
    {
        $this->displayHeader();

        $this->fcmsPhotoGallery->displayGalleryMenu();

        $foundPhotos = $this->fcmsPhotoGallery->displayLatestCategories();

        if ($foundPhotos)
        {
            $this->fcmsPhotoGallery->showCategories(-1, '0', 'comments');

            echo '
            <p class="alignright"><a class="rss" href="../rss.php?feed=gallery">'.T_('RSS Feed').'</a></p>';
        }

        $this->displayFooter();
    }

    /**
     * displayEditDescriptionForm 
     * 
     * Prints the mass edit category function with caption and tagg turned off.
     * 
     * @return void
     */
    function displayEditDescriptionForm ()
    {
        $this->displayHeader();

        $uid = (int)$_GET['user'];
        $cid = (int)$_GET['description'];

        $this->fcmsPhotoGallery->displayEditCategoryForm(
            $cid,
            $uid,
            array(
                'caption' => 1,
                'tag'     => 1
            )
        );

        $this->displayFooter();
    }

    /**
     * getAjaxPicasaPhotos
     * 
     * Will get the first 25 photos for the given album id.  Then calls js to get next 25.
     * Or, if photos have been loaded, will return all photos from the session.
     * 
     * @return string
     */
    function getAjaxPicasaPhotos ()
    {
        $token   = $_POST['picasa_session_token'];
        $albumId = $_POST['albumId'];

        $photos = '';

        if (isset($_SESSION['picasa_album_done']) && isset($_SESSION['picasa_album_id']) && $_SESSION['picasa_album_id'] == $albumId)
        {
            $photos .= '<input type="hidden" name="picasa_user" value="'.$_SESSION['picasa_user'].'"/>';

            $i = 1;
            foreach ($_SESSION['picasa_photos'] as $id => $data)
            {
                $photos .= '<li>';
                $photos .= '<label for="picasa'.$i.'">';
                $photos .= '<img src="'.$data['thumbnail'].'" style="width:'.$data['width'].' height:'.$data['height'].'"/>';
                $photos .= '<span style="display:none"></span>';
                $photos .= '</label>';
                $photos .= '<input type="checkbox" id="picasa'.$i.'" name="photos[]" value="'.$id.'"/>';
                $photos .= '</li>';

                $i++;
            }
        }
        else
        {
            unset($_SESSION['picasa_album_done']);

            $_SESSION['picasa_album_id'] = $albumId;

            $httpClient    = Zend_Gdata_AuthSub::getHttpClient($token);
            $picasaService = new Zend_Gdata_Photos($httpClient, "Google-DevelopersGuide-1.0");

            try
            {
                $feed = $picasaService->getUserFeed("default");
            }
            catch (Zend_Gdata_App_Exception $e)
            {
                echo '
                    <p class="error-alert">
                        '.T_('Could not get Picasa data.').'
                    </p>';

                logError(__FILE__.' ['.__LINE__.'] - Could not get user picasa data. - '.$e->getMessage());
                return;
            }

            try
            {
                $query = new Zend_Gdata_Photos_AlbumQuery();
                $query->setUser($feed->getTitle());
                $query->setAlbumId($albumId);
                $query->setMaxResults(25);

                $albumFeed = $picasaService->getAlbumFeed($query);
            }
            catch (Zend_Gdata_App_Exception $e)
            {
                echo '
                    <p class="error-alert">
                        '.T_('Could not get Picasa album data.').'
                    </p>';

                logError(__FILE__.' ['.__LINE__.'] - Could not get user picasa album data. - '.$e->getMessage());
                return;
            }

            $_SESSION['picasa_photos'] = array();
            $_SESSION['picasa_user']   = $feed->getTitle()->text;

            $photos .= '<input type="hidden" name="picasa_user" value="'.$_SESSION['picasa_user'].'"/>';

            $i = 1;
            foreach ($albumFeed as $photo)
            {
                $thumb = $photo->getMediaGroup()->getThumbnail();

                $sourceId  = $photo->getGphotoId()->text;
                $thumbnail = $thumb[1]->getUrl();

                $w = $photo->getGphotoWidth()->text;
                $h = $photo->getGphotoHeight()->text;

                $width = '100%;';
                $height = 'auto;';

                if ($w > $h)
                {
                    $width = 'auto;';
                    $height = '100%;';
                }

                $_SESSION['picasa_photos'][$sourceId] = array(
                    'thumbnail' => $thumbnail,
                    'width'     => $width,
                    'height'    => $height,
                );

                $photos .= '<li>';
                $photos .= '<label for="picasa'.$i.'">';
                $photos .= '<img src="'.$thumbnail.'" style="width:'.$width.' height:'.$height.'"/>';
                $photos .= '<span style="display:none"></span>';
                $photos .= '</label>';
                $photos .= '<input type="checkbox" id="picasa'.$i.'" name="photos[]" value="'.$sourceId.'"/>';
                $photos .= '</li>';

                $i++;
            }

            if ($i - 1 >= 25)
            {
                $photos .= '<script type="text/javascript">loadMorePicasaPhotos(26, "'.$token.'", "'.T_('Could not get additional photos.').'");</script>';
            }
        }

        echo $photos;
    }

    /**
     * getAjaxMorePicasaPhotos
     * 
     * Will get the next 25 photos for the given album id, starting with given index.
     * Then calls js to get next 25.
     * 
     * @return string
     */
    function getAjaxMorePicasaPhotos ()
    {
        $token      = $_POST['picasa_session_token'];
        $albumId    = $_POST['albumId'];
        $startIndex = $_POST['start_index'];
        $photos     = '';

        $httpClient    = Zend_Gdata_AuthSub::getHttpClient($token);
        $picasaService = new Zend_Gdata_Photos($httpClient, "Google-DevelopersGuide-1.0");

        try
        {
            $feed = $picasaService->getUserFeed("default");
        }
        catch (Zend_Gdata_App_Exception $e)
        {
            echo '
                <p class="error-alert">
                    '.T_('Could not get Picasa data.').'
                </p>';

            logError(__FILE__.' ['.__LINE__.'] - Could not get user picasa data. - '.$e->getMessage());
            return;
        }

        try
        {
            $query = new Zend_Gdata_Photos_AlbumQuery();
            $query->setUser($feed->getTitle());
            $query->setAlbumId($albumId);
            $query->setStartIndex($startIndex);
            $query->setMaxResults(25);

            $albumFeed = $picasaService->getAlbumFeed($query);
        }
        catch (Zend_Gdata_App_Exception $e)
        {
            echo '
                <p class="error-alert">
                    '.T_('Could not get Picasa album data.').'
                </p>';

            logError(__FILE__.' ['.__LINE__.'] - Could not get user picasa album data. - '.$e->getMessage());
            return;
        }

        $count = 0;
        foreach ($albumFeed as $photo)
        {
            $thumb = $photo->getMediaGroup()->getThumbnail();

            $sourceId  = $photo->getGphotoId()->text;
            $thumbnail = $thumb[1]->getUrl();

            $w = $photo->getGphotoWidth()->text;
            $h = $photo->getGphotoHeight()->text;

            $width = '100%;';
            $height = 'auto;';

            if ($w > $h)
            {
                $width = 'auto;';
                $height = '100%;';
            }

            $_SESSION['picasa_photos'][$sourceId] = array(
                'thumbnail' => $thumbnail,
                'width'     => $width,
                'height'    => $height,
            );

            $photos .= '<li>';
            $photos .= '<label for="picasa'.$startIndex.'">';
            $photos .= '<img src="'.$thumbnail.'" style="width:'.$width.' height:'.$height.'"/>';
            $photos .= '<span style="display:none"></span>';
            $photos .= '</label>';
            $photos .= '<input type="checkbox" id="picasa'.$startIndex.'" name="photos[]" value="'.$sourceId.'"/>';
            $photos .= '</li>';

            $startIndex++;
            $count++;
        }

        if ($count >= 25)
        {
            $photos .= '<script type="text/javascript">loadMorePicasaPhotos('.$startIndex.', "'.$token.'", "'.T_('Could not get additional photos.').'");</script>';
        }
        else
        {
            $_SESSION['picasa_album_done'] = 1;
        }

        echo $photos;
    }

    /**
     * getAjaxPicasaAlbums
     * 
     * Will get all albums for the user.
     * 
     * @return string
     */
    function getAjaxPicasaAlbums ()
    {
        $token   = $_POST['picasa_session_token'];

        if (isset($_SESSION['picasa_albums']))
        {
            $albums = '<select id="albums" name="albums">';

            foreach ($_SESSION['picasa_albums'] as $id => $title)
            {
                $albums .= '<option value="'.$id.'">'.$title.'</option>';
            }

            $albums .= '</select>';
        }
        else
        {
            $httpClient    = Zend_Gdata_AuthSub::getHttpClient($token);
            $picasaService = new Zend_Gdata_Photos($httpClient, "Google-DevelopersGuide-1.0");

            try
            {
                $feed = $picasaService->getUserFeed("default");
            }
            catch (Zend_Gdata_App_Exception $e)
            {
                echo '
                    <p class="error-alert">
                        '.T_('Could not get Picasa data.').'
                    </p>';

                logError(__FILE__.' ['.__LINE__.'] - Could not get user picasa data. - '.$e->getMessage());
                return;
            }

            $albums = '<select id="albums" name="albums">';

            $_SESSION['picasa_albums'] = array();

            foreach ($feed as $album)
            {
                $id    = $album->getGphotoId()->text;
                $title = $album->title->text;

                $_SESSION['picasa_albums'][$id] = $title;

                $albums .= '<option value="'.$id.'">'.$title.'</option>';
            }

            $albums .= '</select>';
        }

        echo '
                <p>'.$albums.'</p>
                <div id="selector">
                    <a href="#" onclick="picasaSelectAll();" id="select-all">'.T_('Select All').'</a>
                    <a href="#" onclick="picasaSelectNone();" id="select-none">'.T_('Select None').'</a>
                </div>
                <ul id="photo_list"></ul>
                <script language="javascript">loadPicasaPhotos("'.$token.'", "'.T_('Could not get photos.').'");</script>';
    }
}
