<?php
/**
 * Photo Gallery.
 *
 * PHP versions 4 and 5
 *
 * @category  FCMS
 *
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '../');
define('GALLERY_PREFIX', '');

require URL_PREFIX.'fcms.php';

load(
    'gallery',
    'socialmedia',
    'datetime',
    'image',
    'google',
    'facebook'
);

init('gallery/');

// Globals
$img = new Image($fcmsUser->id);
$gallery = new PhotoGallery($fcmsError, $fcmsDatabase, $fcmsUser, $img);
$page = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $gallery, $img);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsPhotoGallery;
    private $fcmsTemplate;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsPhotoGallery, $fcmsImage)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser = $fcmsUser;
        $this->fcmsPhotoGallery = $fcmsPhotoGallery;
        $this->fcmsImage = $fcmsImage;

        $this->control();
    }

    /**
     * control.
     *
     * The controlling structure for this script.
     *
     * @return void
     */
    public function control()
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
            elseif (isset($_POST['type']) && $_POST['type'] == 'facebook')
            {
                $this->getAjaxFacebookPhotos();
            }
            else
            {
                die('Uknown AJAX Request');
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
            if (isset($_POST['confirmed']) || isset($_GET['confirmed']))
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

            if ($_GET['action'] == 'upload')
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
                elseif (isset($_POST['facebook']))
                {
                    $this->displayFacebookUploadFormSubmit();
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
            elseif ($_GET['action'] == 'category')
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
        elseif (isset($_POST['plupload']))
        {
            $this->displayPluploadFormSubmit();
        }
        elseif (isset($_POST['javaUpload']))
        {
            $this->displayJavaUploadFormSubmit();
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
     * displayHeader.
     *
     * @return void
     */
    public function displayHeader($options = null)
    {
        $params = [
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => cleanOutput(getSiteName()),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Photo Gallery'),
            'pageId'        => 'gallery',
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y'),
        ];

        if ($options === null)
        {
            $options = [
                'jsOnload' => 'deleteConfirmationLink("deletephoto", "'.T_('Are you sure you want to DELETE this Photo?').'");'
                            .'deleteConfirmationLinks("gal_delcombtn", "'.T_('Are you sure you want to DELETE this Comment?').'");'
                            .'deleteConfirmationLinks("delcategory", "'.T_('Are you sure you want to DELETE this Category?').'");'
                            .'initNewWindow();',
            ];
        }

        displayPageHeader($params, $options);
    }

    /**
     * displayFooter.
     *
     * @return void
     */
    public function displayFooter()
    {
        $params = [
            'path'    => URL_PREFIX,
            'version' => getCurrentVersion(),
            'year'    => date('Y'),
        ];

        loadTemplate('global', 'footer', $params);
    }

    /**
     * displayEditPhotoForm.
     *
     * @return void
     */
    public function displayEditPhotoForm()
    {
        $this->displayHeader(
            ['modules' => ['autocomplete']]
        );
        $this->fcmsPhotoGallery->displayEditPhotoForm($_POST['photo'], $_POST['url']);
        $this->displayFooter();
    }

    /**
     * displayEditPhotoSubmit.
     *
     * @return void
     */
    public function displayEditPhotoSubmit()
    {
        $uid = (int) $_GET['uid'];
        $photo_caption = strip_tags($_POST['photo_caption']);
        $category = strip_tags($_POST['category']);
        $cid = $category;
        $pid = (int) $_POST['photo_id'];

        $sql = 'UPDATE `fcms_gallery_photos` 
                SET `category` = ?, 
                    `caption` = ?
                WHERE `id` = ?';

        $params = [
            $category,
            $photo_caption,
            $pid,
        ];

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $tagged = isset($_POST['tagged']) ? $_POST['tagged'] : null;
        $prev = isset($_POST['prev_tagged_users']) ? $_POST['prev_tagged_users'] : null;

        if (!$this->tagMembersInPhoto($pid, $tagged, $prev))
        {
            // error handled by tagMembersInPhoto()
            return;
        }

        // Rotating Image?
        if (isset($_POST['rotate']))
        {
            $sql = 'SELECT `id`, `user`, `filename`, `external_id`
                    FROM `fcms_gallery_photos`
                    WHERE `id` = ?';

            $row = $this->fcmsDatabase->getRow($sql, $pid);
            if ($row === false)
            {
                $this->fcmsError->displayError();

                return;
            }

            // TODO - Instagram hack -- needs to go away
            // We should never keep photos externally like this
            // We should download the photos locally like we do with Picasa
            if ($row['filename'] == 'noimage.gif' && $row['external_id'] != null)
            {
                $_SESSION['message'] = T_('The photo cannot be editted.  Please go to Instagram to edit this photo.');
                header('Location: index.php?uid='.$uid.'&cid='.$cid.'&pid='.$pid);
            }

            // Setup the array of photos that need uploaded
            $photoPrefixes = [
                'main'      => '',
                'thumbnail' => 'tb_',
            ];
            if ($this->fcmsPhotoGallery->usingFullSizePhotos())
            {
                $photoPrefixes['full'] = 'full_';
            }

            $rotate = 270;
            if ($_POST['rotate'] == 'left')
            {
                $rotate = 90;
            }

            // Loop through each photo that needs uploaded
            foreach ($photoPrefixes as $key => $prefix)
            {
                $photoDestinationType = getDestinationType().'PhotoGalleryDestination';
                $photoDestination = new $photoDestinationType($this->fcmsError, $this->fcmsUser);

                $photoDestination->rotate($prefix.$row['filename'], $rotate);

                $_SESSION['updatedPhotos'][$pid] = 1;
            }
        }

        $_SESSION['message'] = 1;

        header('Location: index.php?uid='.$uid.'&cid='.$cid.'&pid='.$pid);
    }

    /**
     * tagMembersInPhoto.
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
     * @return bool
     */
    public function tagMembersInPhoto($photoId, $taggedMembers = null, $prevTaggedMembers = null)
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
            $sql = 'INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`) 
                    VALUES ';

            $params = [];

            foreach ($ids['add'] as $userId)
            {
                $sql .= '(?, ?),';

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
            $in = implode(',', $ids['remove']);

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
     * addTaggedNotifications.
     *
     * @param int   $photoId
     * @param array $ids
     *
     * @return void
     */
    public function addTaggedNotifications($photoId, $ids)
    {
        // Get photo info
        $sql = 'SELECT `user`, `category`, `filename`
                FROM `fcms_gallery_photos`
                WHERE `id` = ?';

        $photoInfo = $this->fcmsDatabase->getRow($sql, $photoId);
        if ($photoInfo === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $sql = 'INSERT INTO `fcms_notification` (`user`, `created_id`, `notification`, `data`, `read`, `created`, `updated`)
                VALUES ';

        $params = [];
        $data = $photoInfo['user'].':'.$photoInfo['category'].':'.$photoId.':'.$photoInfo['filename'];

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
     * displayConfirmDeletePhotoForm.
     *
     * @return void
     */
    public function displayConfirmDeletePhotoForm()
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
     * displayDeletePhotoSubmit.
     *
     * @return void
     */
    public function displayDeletePhotoSubmit()
    {
        $photoId = (int) $_POST['photo'];

        // Get photo info
        $sql = 'SELECT `user`, `category`, `filename`, `external_id`
                FROM `fcms_gallery_photos` 
                WHERE `id` = ?';

        $filerow = $this->fcmsDatabase->getRow($sql, $photoId);
        if ($filerow === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $photoUserId = $filerow['user'];
        $photoCategory = $filerow['category'];

        $worked = $this->fcmsPhotoGallery->deletePhotos([$photoId]);
        if (!$worked)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $_SESSION['message'] = 1;

        header("Location: index.php?uid=$photoUserId&cid=$photoCategory");
    }

    /**
     * checkActionPermissions.
     *
     * @return void
     */
    public function checkActionPermissions()
    {
        $access = $this->fcmsUser->access;

        // Catch users who can't upload photos, create categories, etc.
        if ($access == NON_POSTER_USER
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
     * displayUploadForm.
     *
     * @return void
     */
    public function displayUploadForm()
    {
        $this->displayHeader(
            [
                'modules'  => ['autocomplete'],
                'jsOnload' => 'hideUploadOptions(\''.T_('Rotate Photo').'\', \''.T_('Use Existing Category').'\', \''.T_('Create New Category').'\');',
            ]
        );

        $this->fcmsPhotoGallery->displayGalleryMenu('none');

        // Display any errors from last upload
        if ($this->fcmsError->hasAnyError())
        {
            $this->fcmsError->displayError();
        }

        // Figure out what type of photo gallery uploader we are using, and create new object
        $photoGalleryType = getPhotoGallery();
        $photoGalleryType .= 'Form';
        $photoGalleryForm = new $photoGalleryType($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser);

        $photoGalleryForm->display();

        $this->displayFooter();

    }

    /**
     * displayUploadFormSubmit.
     *
     * @return void
     */
    public function displayUploadFormSubmit()
    {
        // Figure out where we are currently saving photos, and create new destination object
        $photoDestinationType = getDestinationType().'PhotoGalleryDestination';
        $photoDestination = new $photoDestinationType($this->fcmsError, $this->fcmsUser);

        $uploadPhoto = new UploadPhoto($this->fcmsError, $photoDestination);

        // Figure out what type of photo gallery uploader we are using, and create new object
        $photoGalleryType = getPhotoGallery();
        $photoGalleryUploader = new $photoGalleryType($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser, $photoDestination, $uploadPhoto);

        $formData = [
            'photo'       => $_FILES['photo_filename'],
            'newCategory' => $_POST['new-category'],
            'caption'     => $_POST['photo_caption'],
        ];
        $formData['category'] = isset($_POST['category']) ? $_POST['category'] : null;
        $formData['rotate'] = isset($_POST['rotate']) ? $_POST['rotate'] : null;

        // Upload the photo
        if (!$photoGalleryUploader->upload($formData))
        {
            header('Location: index.php?action=upload');

            return;
        }

        $photoId = $photoGalleryUploader->getLastPhotoId();
        $categoryId = $photoGalleryUploader->getLastCategoryId();

        // Tag photo
        if (isset($_POST['tagged']))
        {
            if (!$this->tagMembersInPhoto($photoId, $_POST['tagged']))
            {
                // error handled by tagMembersInPhoto()
                return;
            }
        }

        // Email members
        $this->fcmsPhotoGallery->emailMembersNewPhotos($categoryId);

        // Redirect to new photo
        header('Location: index.php?uid='.$this->fcmsUser->id.'&cid='.$categoryId.'&pid='.$photoId);

    }

    /**
     * displayPluploadFormSubmit.
     *
     * @return void
     */
    public function displayPluploadFormSubmit()
    {
        // Figure out where we are currently saving photos, and create new destination object
        $photoDestinationType = getDestinationType().'PhotoGalleryDestination';
        $photoDestination = new $photoDestinationType($this->fcmsError, $this->fcmsUser);

        $uploadPhoto = new UploadPhoto($this->fcmsError, $photoDestination);

        // Figure out what type of photo gallery uploader we are using, and create new object
        $photoGalleryType = getPhotoGallery();
        $photoGalleryUploader = new $photoGalleryType($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser, $photoDestination, $uploadPhoto);

        $type = key($_FILES);
        $file = array_shift($_FILES);

        $file['name'] = $_POST['name'];

        $formData = [
            'photo_type'  => $type,
            'photo'       => $file,
            'newCategory' => $_POST['new-category'],
        ];

        $formData['category'] = isset($_POST['category']) ? $_POST['category'] : null;

        // Upload the photo
        if (!$photoGalleryUploader->upload($formData))
        {
            $error = $photoGalleryUploader->fcmsUser->getError();
            $message = $error['message'].' - '.$error['details'];

            die('{"jsonrpc" : "2.0", "error" : {"code": 500, "message": "'.$message.'"}, "id" : "id"}');
        }
    }

    /**
     * displayJavaUploadFormSubmit.
     *
     * @return void
     */
    public function displayJavaUploadFormSubmit()
    {
        // Figure out where we are currently saving photos, and create new destination object
        $photoDestinationType = getDestinationType().'PhotoGalleryDestination';
        $photoDestination = new $photoDestinationType($this->fcmsError, $this->fcmsUser);

        $uploadPhoto = new UploadPhoto($this->fcmsError, $photoDestination);

        // Figure out what type of photo gallery uploader we are using, and create new object
        $photoGalleryType = getPhotoGallery();
        $photoGalleryUploader = new $photoGalleryType($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser, $photoDestination, $uploadPhoto);

        $formData = [
            'thumb'       => $_FILES['thumb'],
            'main'        => $_FILES['main'],
            'newCategory' => $_POST['new-category'],
        ];

        $formData['full'] = isset($_FILES['full']) ? $_FILES['full'] : null;
        $formData['category'] = isset($_POST['category']) ? $_POST['category'] : null;

        if (!$photoGalleryUploader->upload($formData))
        {
            echo 'Upload Failure';

            return;
        }

        echo 'Success';
    }

    /**
     * displayInstagramUploadFormSubmit.
     *
     * @return void
     */
    public function displayInstagramUploadFormSubmit()
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

        // We currently don't need a photo destination for Instagram
        $photoDestination = new Destination($this->fcmsError, $this->fcmsUser);

        // Figure out what type of photo gallery uploader we are using, and create new object
        $photoGalleryType = getPhotoGallery();
        $photoGalleryUploader = new $photoGalleryType($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser, $photoDestination);

        $formData = [
            'photos' => $_POST['photos'],
        ];

        if (!$photoGalleryUploader->upload($formData))
        {
            header('Location: index.php?action=upload&type=instagram');

            return;
        }

        $categoryId = $photoGalleryUploader->getLastCategoryId();

        header('Location: index.php?uid='.$this->fcmsUser->id."&cid=$categoryId");
    }

    /**
     * displayPicasaUploadFormSubmit.
     *
     * @return void
     */
    public function displayPicasaUploadFormSubmit()
    {
        // Figure out where we are currently saving photos, and create new destination object
        $photoDestinationType = getDestinationType().'PhotoGalleryDestination';
        $photoDestination = new $photoDestinationType($this->fcmsError, $this->fcmsUser);

        $uploadPhoto = new UploadPhoto($this->fcmsError, $photoDestination);

        // Figure out what type of photo gallery uploader we are using, and create new object
        $photoGalleryType = getPhotoGallery();
        $photoGalleryUploader = new $photoGalleryType($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser, $photoDestination, $uploadPhoto);

        $formData = [
            'photos'      => $_POST['photos'],
            'albums'      => $_POST['albums'],
            'picasa_user' => $_POST['picasa_user'],
            'newCategory' => $_POST['new-category'],
        ];

        $formData['category'] = isset($_POST['category']) ? $_POST['category'] : null;

        if (!$photoGalleryUploader->upload($formData))
        {
            header('Location: index.php?action=upload&type=picasa');

            return;
        }

        $categoryId = $photoGalleryUploader->getLastCategoryId();

        header("Location: index.php?edit-category=$categoryId&user=".$this->fcmsUser->id);
    }

    /**
     * displayFacebookUploadFormSubmit.
     *
     * @return void
     */
    public function displayFacebookUploadFormSubmit()
    {
        load('facebook');

        // Figure out where we are currently saving photos, and create new destination object
        $photoDestinationType = getDestinationType().'PhotoGalleryDestination';
        $photoDestination = new $photoDestinationType($this->fcmsError, $this->fcmsUser);

        $uploadPhoto = new UploadPhoto($this->fcmsError, $photoDestination);

        // Figure out what type of photo gallery uploader we are using, and create new object
        $photoGalleryType = getPhotoGallery();
        $photoGalleryUploader = new $photoGalleryType($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser, $photoDestination, $uploadPhoto);

        $formData = [
            'photos'      => $_POST['photos'],
            'albums'      => $_POST['albums'],
            'newCategory' => $_POST['new-category'],
        ];

        $formData['category'] = isset($_POST['category']) ? $_POST['category'] : null;

        if (!$photoGalleryUploader->upload($formData))
        {
            header('Location: index.php?action=upload&type=facebook');

            return;
        }

        $categoryId = $photoGalleryUploader->getLastCategoryId();

        header("Location: index.php?edit-category=$categoryId&user=".$this->fcmsUser->id);
    }

    /**
     * displayUploadAdvancedEditCategory.
     *
     * Handles the form submission after uploading photos using advanced uploader.
     * This just redirects to the category edit form.
     *
     * @return void
     */
    public function displayUploadAdvancedEditCategory()
    {
        $category = (int) $_SESSION['mass_photos_category'];
        $user = $this->fcmsUser->id;

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
     * displayEditCategoriesFormSubmit.
     *
     * @return void
     */
    public function displayEditCategoriesFormSubmit()
    {
        $categoryId = (int) $_POST['cid'];
        $categoryName = strip_tags($_POST['cat_name']);

        if (empty($categoryName))
        {
            $this->displayHeader();
            echo '
            <p class="error-alert">'.T_('Category name cannot be blank.').'</p>';
            $this->displayFooter();
        }

        $sql = 'UPDATE fcms_category 
                SET `name` = ?
                WHERE `id` = ?';

        $params = [
            $categoryName,
            $categoryId,
        ];

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $_SESSION['message'] = sprintf(T_('The Category %s was Updated Successfully'), '<b>'.$categoryName.'</b>');

        header('Location: index.php?action=category');
    }

    /**
     * displayEditCategoriesForm.
     *
     * @return void
     */
    public function displayEditCategoriesForm()
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
     * displayConfirmDeleteCategoryForm.
     *
     * @return void
     */
    public function displayConfirmDeleteCategoryForm()
    {
        $this->displayHeader();

        echo '
                <div class="info-alert">
                    <form action="index.php?confirmed=1" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this category?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="cid" value="'.(int) $_POST['cid'].'"/>
                            <input style="float:left;" type="submit" id="delcat" name="delcat" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="index.php?action=category">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

        $this->displayFooter();
    }

    /**
     * displayDeleteCategorySubmit.
     *
     * @return void
     */
    public function displayDeleteCategorySubmit()
    {
        $cid = 0;

        if (isset($_GET['delcat']))
        {
            $cid = (int) $_GET['delcat'];
        }
        elseif (isset($_POST['cid']))
        {
            $cid = (int) $_POST['cid'];
        }
        else
        {
            $this->displayHeader();
            echo '<p class="error-alert">'.T_('Missing or invalid id.').'</p>';
            $this->displayFooter();

            return;
        }

        // Get category info
        $sql = 'SELECT `user`
                FROM `fcms_category`
                WHERE `id` = ?
                LIMIT 1';

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

        $sql = 'DELETE FROM fcms_category 
                WHERE `id` = ?
                AND `user` = ?';

        $params = [
            $cid,
            $this->fcmsUser->id,
        ];

        if (!$this->fcmsDatabase->delete($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $_SESSION['message'] = T_('Category Deleted Successfully');

        header('Location: index.php?action=category');
    }

    /**
     * displayEditCategoryForm.
     *
     * @return void
     */
    public function displayEditCategoryForm()
    {
        $this->displayHeader(
            ['modules' => ['autocomplete']]
        );

        $category = (int) $_GET['edit-category'];
        $user = (int) $_GET['user'];

        $this->fcmsPhotoGallery->displayEditCategoryForm($category, $user);

        $this->displayFooter();
    }

    /**
     * displayEditCategoryFormSubmit.
     *
     * @return void
     */
    public function displayEditCategoryFormSubmit()
    {
        $uid = (int) $_GET['uid'];
        $cid = (int) $_GET['cid'];

        // Save description
        if (isset($_POST['description']))
        {
            $description = strip_tags($_POST['description']);

            $sql = 'UPDATE `fcms_category`
                    SET `description` = ?
                    WHERE `id` = ?';

            if (!$this->fcmsDatabase->update($sql, [$description, $cid]))
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
                $pid = (int) $pid;
                $caption = strip_tags($caption);

                // Update the caption
                $sql = 'UPDATE `fcms_gallery_photos` 
                        SET `caption` = ?
                        WHERE `id` = ?';

                $params = [
                    $caption,
                    $pid,
                ];

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
            $photos = [];
            $photos1 = [];
            $photos2 = [];

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
                $tagged = isset($_POST['tagged'][$pid]) ? $_POST['tagged'][$pid] : null;
                $prev = isset($_POST['prev_tagged_users'][$pid]) ? $_POST['prev_tagged_users'][$pid] : null;

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
     * displayMassTagForm.
     *
     * Prints the mass edit category function with caption and desc turned off.
     *
     * @return void
     */
    public function displayMassTagForm()
    {
        $this->displayHeader(
            ['modules' => ['autocomplete']]
        );

        $category = (int) $_GET['tag'];
        $user = (int) $_GET['user'];

        $this->fcmsPhotoGallery->displayEditCategoryForm(
            $category,
            $user,
            [
                'description' => 1,
                'caption'     => 1,
            ]
        );

        $this->displayFooter();
    }

    /**
     * displayPhoto.
     *
     * @return void
     */
    public function displayPhoto()
    {
        $this->displayHeader();

        if (isset($_SESSION['message']))
        {
            unset($_SESSION['message']);

            displayOkMessage();
        }

        $uid = (int) $_GET['uid'];
        $cid = $_GET['cid']; // not always an #
        $pid = (int) $_GET['pid'];

        $this->fcmsPhotoGallery->showPhoto($uid, $cid, $pid);

        $this->displayFooter();
    }

    /**
     * displayCategory.
     *
     * @return void
     */
    public function displayCategory()
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
     * displayUserCategory.
     *
     * @return void
     */
    public function displayUserCategory()
    {
        $this->displayHeader();

        $page = getPage();

        $this->fcmsPhotoGallery->displayGalleryMenu($_GET['uid']);
        $this->fcmsPhotoGallery->showCategories($page, $_GET['uid']);

        $this->displayFooter();
    }

    /**
     * displayAddCategoryCommentSubmit.
     *
     * @return void
     */
    public function displayAddCategoryCommentSubmit()
    {
        $uid = (int) $_GET['uid'];
        $cid = (int) $_GET['cid'];
        $com = ltrim($_POST['comment']);
        $com = strip_tags($com);
        $commentId = 0;

        if (!empty($com))
        {
            $sql = 'INSERT INTO `fcms_gallery_category_comment`
                        (`category_id`, `comment`, `created`, `created_id`)
                    VALUES
                        (?, ?, NOW(), ?)';

            $params = [
                $cid,
                $com,
                $this->fcmsUser->id,
            ];

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
     * displayAddPhotoCommentSubmit.
     *
     * @return void
     */
    public function displayAddPhotoCommentSubmit()
    {
        $uid = (int) $_GET['uid'];
        $cid = $_GET['cid']; // not always an #
        $pid = (int) $_GET['pid'];
        $com = ltrim($_POST['post']);
        $com = strip_tags($com);
        $commentId = 0;

        if (!empty($com))
        {
            $sql = 'INSERT INTO `fcms_gallery_photo_comment`
                        (`photo`, `comment`, `date`, `user`)
                    VALUES
                        (?, ?, NOW(), ?)';

            $params = [
                $pid,
                $com,
                $this->fcmsUser->id,
            ];

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
     * displayAddVoteSubmit.
     *
     * @return void
     */
    public function displayAddVoteSubmit()
    {
        $uid = (int) $_GET['uid'];
        $cid = $_GET['cid']; // not always an #
        $pid = (int) $_GET['pid'];
        $vote = (int) $_GET['vote'];

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
     * displayDeleteCommentSubmit.
     *
     * @return void
     */
    public function displayDeleteCommentSubmit()
    {
        $uid = (int) $_POST['uid'];
        $cid = $_POST['cid']; // not always an #
        $pid = (int) $_POST['pid'];
        $id = (int) $_POST['id'];

        $sql = 'DELETE FROM `fcms_gallery_photo_comment` 
                WHERE `id` = ?';

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
     * displayConfirmDeleteCommentForm.
     *
     * @return void
     */
    public function displayConfirmDeleteCommentForm()
    {
        $uid = (int) $_GET['uid'];
        $cid = $_GET['cid']; // not always an #
        $pid = (int) $_GET['pid'];
        $id = (int) $_POST['id'];
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
     * displaySearchForm.
     *
     * @return void
     */
    public function displaySearchForm()
    {
        $this->displayHeader();

        $this->fcmsPhotoGallery->displayGalleryMenu();
        $this->fcmsPhotoGallery->displaySearchForm();

        $this->displayFooter();
    }

    /**
     * displayLatest.
     *
     * @return void
     */
    public function displayLatest()
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
     * displayEditDescriptionForm.
     *
     * Prints the mass edit category function with caption and tagg turned off.
     *
     * @return void
     */
    public function displayEditDescriptionForm()
    {
        $this->displayHeader();

        $uid = (int) $_GET['user'];
        $cid = (int) $_GET['description'];

        $this->fcmsPhotoGallery->displayEditCategoryForm(
            $cid,
            $uid,
            [
                'caption' => 1,
                'tag'     => 1,
            ]
        );

        $this->displayFooter();
    }

    /**
     * getAjaxPicasaPhotos.
     *
     * Will get the first 25 photos for the given album id.  Then calls js to get next 25.
     * Or, if photos have been loaded, will return all photos from the session.
     *
     * @return string
     */
    public function getAjaxPicasaPhotos()
    {
        $token = $_POST['picasa_session_token'];
        $albumId = $_POST['albumId'];

        $photos = '';

        $googleClient = getAuthedGoogleClient($this->fcmsUser->id);

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

            // Get the album data
            $curl = curl_init();

            curl_setopt_array(
                $curl,
                [
                    CURLOPT_CUSTOMREQUEST  => 'GET',
                    CURLOPT_URL            => 'https://picasaweb.google.com/data/feed/api/user/default/albumid/'.$albumId.'?max-results=25',
                    CURLOPT_HTTPHEADER     => ['GData-Version: 2', 'Authorization: Bearer '.$token],
                    CURLOPT_RETURNTRANSFER => 1,
                ]
            );

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            if ($httpCode !== 200)
            {
                echo '
                    <p class="error-alert">
                        '.T_('Could not get Picasa album data.').'
                    </p>';

                logError(__FILE__.' ['.__LINE__.'] - Could not get Picasa album data. - '.$response);

                return;
            }

            $xml = new SimpleXMLElement($response);

            $_SESSION['picasa_photos'] = [];
            $_SESSION['picasa_user'] = (string) $xml->title;

            $i = 1;
            foreach ($xml->entry as $photo)
            {
                $group = $photo->children('media', true)->group;

                // skip videos
                foreach ($group->content as $content)
                {
                    if ($content->attributes()->medium == 'video')
                    {
                        continue 2;
                    }
                }

                $sourceId = (int) $photo->children('gphoto', true)->id;

                $w = (int) $photo->children('gphoto', true)->width;
                $h = (int) $photo->children('gphoto', true)->height;

                $width = '100%;';
                $height = 'auto;';

                if ($w > $h)
                {
                    $width = 'auto;';
                    $height = '100%;';
                }

                $thumbnail = (string) $group->thumbnail[1]->attributes()->url;

                $_SESSION['picasa_photos'][$sourceId] = [
                    'thumbnail' => $thumbnail,
                    'width'     => $width,
                    'height'    => $height,
                ];

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

        if ($i <= 1 && empty($photos))
        {
            $photos = '<p class="info-alert">'.T_('No photos were found in this album').'</p>';
        }

        $photos .= '<input type="hidden" name="picasa_user" value="'.$_SESSION['picasa_user'].'"/>';

        echo $photos;
    }

    /**
     * getAjaxMorePicasaPhotos.
     *
     * Will get the next 25 photos for the given album id, starting with given index.
     * Then calls js to get next 25.
     *
     * @return string
     */
    public function getAjaxMorePicasaPhotos()
    {
        $token = $_POST['picasa_session_token'];
        $albumId = $_POST['albumId'];
        $startIndex = $_POST['start_index'];
        $photos = '';

        // Get the album data
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            [
                CURLOPT_CUSTOMREQUEST  => 'GET',
                CURLOPT_URL            => 'https://picasaweb.google.com/data/feed/api/user/default/albumid/'.$albumId.'?start-index='.$startIndex.'&max-results=25',
                CURLOPT_HTTPHEADER     => ['GData-Version: 2', 'Authorization: Bearer '.$token],
                CURLOPT_RETURNTRANSFER => 1,
            ]
        );

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode !== 200)
        {
            echo '
                <p class="error-alert">
                    '.T_('Could not get Picasa album data.').'
                </p>';

            logError(__FILE__.' ['.__LINE__.'] - Could not get Picasa album data. - '.$response);

            return;
        }

        $xml = new SimpleXMLElement($response);

        $count = 0;
        foreach ($xml->entry as $photo)
        {
            $group = $photo->children('media', true)->group;

            // Skip videos
            foreach ($group->content as $content)
            {
                if ($content->attributes()->medium == 'video')
                {
                    continue 2;
                }
            }

            $sourceId = (int) $photo->children('gphoto', true)->id;

            $thumbnail = (string) $group->thumbnail[1]->attributes()->url;

            $w = (int) $photo->children('gphoto', true)->width;
            $h = (int) $photo->children('gphoto', true)->height;

            $width = '100%;';
            $height = 'auto;';

            if ($w > $h)
            {
                $width = 'auto;';
                $height = '100%;';
            }

            $_SESSION['picasa_photos'][$sourceId] = [
                'thumbnail' => $thumbnail,
                'width'     => $width,
                'height'    => $height,
            ];

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
     * getAjaxPicasaAlbums.
     *
     * Will get all albums for the user.
     *
     * @return string
     */
    public function getAjaxPicasaAlbums()
    {
        $token = $_POST['picasa_session_token'];

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
            $curl = curl_init();

            curl_setopt_array(
                $curl,
                [
                    CURLOPT_CUSTOMREQUEST  => 'GET',
                    CURLOPT_URL            => 'https://picasaweb.google.com/data/feed/api/user/default',
                    CURLOPT_HTTPHEADER     => ['GData-Version: 2', 'Authorization: Bearer '.$token],
                    CURLOPT_RETURNTRANSFER => 1,
                ]
            );

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            if ($httpCode !== 200)
            {
                echo '
                    <p class="error-alert">
                        '.T_('Could not get Picasa data.').'
                    </p>';

                logError(__FILE__.' ['.__LINE__.'] - Could not get user picasa data. - '.$response);

                return;
            }

            $xml = new SimpleXMLElement($response);

            $albums = '<select id="albums" name="albums">';

            $_SESSION['picasa_albums'] = [];

            foreach ($xml->entry as $album)
            {
                $id = (int) $album->children('gphoto', true)->id;
                $title = (string) $album->title;

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
                <script language="javascript">loadPicasaPhotoEvents("'.$token.'", "'.T_('Could not get photos.').'");</script>
                <ul id="photo_list">
                    <script language="javascript">loadPicasaPhotos("'.$token.'", "'.T_('Could not get photos.').'");</script>
                </ul>';
    }

    /**
     * getAjaxFacebookPhotos.
     *
     * Will print a list of photos from facebook.
     *
     * @return null
     */
    public function getAjaxFacebookPhotos()
    {
        $config = getFacebookConfigData();
        $accessToken = getUserFacebookAccessToken($this->fcmsUser->id);

        $facebook = new Facebook([
            'appId'  => $config['fb_app_id'],
            'secret' => $config['fb_secret'],
        ]);

        $facebook->setAccessToken($accessToken);

        $albumId = (int) $_POST['albumId'];
        $photos = '';
        $i = 1;

        $_SESSION['facebook_photos'] = [];

        try
        {
            $fbPhotos = $facebook->api("/$albumId/photos");

            foreach ($fbPhotos['data'] as $photo)
            {
                $w = $photo['width'];
                $h = $photo['height'];

                $width = '100%;';
                $height = 'auto;';

                if ($w > $h)
                {
                    $width = 'auto;';
                    $height = '100%;';
                }

                $sourceId = $photo['id'];
                $thumbnail = $photo['picture'];

                $_SESSION['facebook_photos'][$sourceId] = [
                    'thumbnail' => $thumbnail,
                    'width'     => $width,
                    'height'    => $height,
                ];

                $photos .= '<li>';
                $photos .= '<label for="facebook'.$i.'">';
                $photos .= '<img src="'.$thumbnail.'" style="width:'.$width.' height:'.$height.'"/>';
                $photos .= '<span style="display:none"></span>';
                $photos .= '</label>';
                $photos .= '<input type="checkbox" id="facebook'.$i.'" name="photos[]" value="'.$sourceId.'"/>';
                $photos .= '</li>';

                $i++;
            }
        }
        catch (FacebookApiException $e)
        {
            echo '<p class="error-alert">'.T_('Could not get Facebook photos.').'</p>';

            $this->fcmsError->add([
                'type'    => 'operation',
                'message' => T_('Could not get Facebook photos.'),
                'error'   => $e,
                'file'    => __FILE__,
                'line'    => __LINE__,
            ]);

            return;
        }

        if ($i <= 1 && empty($photos))
        {
            $photos = '<p class="info-alert">'.T_('No photos were found in this album').'</p>';
        }

        echo $photos;
    }
}
