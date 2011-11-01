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

require URL_PREFIX.'fcms.php';

load('gallery');

init('gallery/');

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$gallery       = new PhotoGallery($currentUserId);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Photo Gallery'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
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
    initConfirmPhotoDelete(\''.T_('Are you sure you want to DELETE this Photo?').'\');
    initConfirmCommentDelete(\''.T_('Are you sure you want to DELETE this Comment?').'\');
    initConfirmCategoryDelete(\''.T_('Are you sure you want to DELETE this Category?').'\');
    initNewWindow();
});
//]]>
</script>';

// Show Header
require_once getTheme($currentUserId, $TMPL['path']).'header.php';

echo '
        <div id="gallery" class="centercontent">';

$show_latest = true;

//------------------------------------------------------------------------------
// Edit Photo
//------------------------------------------------------------------------------
if (isset($_POST['add_editphoto']))
{

    $photo_caption = cleanInput($_POST['photo_caption']);
    $category      = cleanInput($_POST['category']);
    $pid           = cleanInput($_POST['photo_id'], 'int');

    $sql = "UPDATE `fcms_gallery_photos` 
            SET `category` = '$category', 
                `caption` = '$photo_caption' 
            WHERE `id` = '$pid'";
    mysql_query($sql) or displaySQLError(
        'Edit Photo Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
    );
    
    // Has someone been tagged?
    if (isset($_POST['tagged']))
    {
        
        // Check whether previously tagged members exist
        if (isset($_POST['prev_tagged_users']))
        {
            
            // Delete all members who were previously tagged, but now are not
            $prev_users = $_POST['prev_tagged_users'];
            foreach ($prev_users as $user)
            {
                $key = array_search($user, $_POST['tagged']);
                if ($key === false)
                {
                    $sql = "DELETE FROM `fcms_gallery_photos_tags` 
                            WHERE `photo` = '$pid' 
                            AND `user` = '".cleanInput($user, 'int')."'";
                    mysql_query($sql) or displaySQLError('Delete Tagged Member Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                }
                
            }
            
            // Add only members who were not previously tagged
            foreach ($_POST['tagged'] as $user)
            {
                $key = array_search($user, $prev_users);
                if ($key === false)
                {
                    $sql = "INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`) 
                            VALUES (
                                '".cleanInput($user, 'int')."', 
                                '$pid'
                            )";
                    mysql_query($sql) or displaySQLError(
                        'Tag Members Error', __FILE__.' ['.__LINE__.']', 
                        $sql, mysql_error()
                    );
                }
                
            }
        }
        // No one was previously tagged
        else
        {
            
            // Add all tagged members, since no one was previously tagged
            foreach ($_POST['tagged'] as $user)
            {
                $sql = "INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`) 
                        VALUES (
                            '".cleanInput($user, 'int')."', 
                            '$pid'
                        )";
                mysql_query($sql) or displaySQLError('Tag Members Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            }
        }
    }
    // If no one is currently tagged, but we have previously tagged members, 
    // then we are removing all members
    elseif (isset($_POST['prev_tagged_users']))
    {
        $sql = "DELETE FROM `fcms_gallery_photos_tags` 
                WHERE `photo` = '$pid'";
        mysql_query($sql) or displaySQLError(
            'Delete All Tagged Error', __FILE__.' ['.__LINE__.']', 
            $sql, mysql_error()
        );
    }
    
    echo '
            <p class="ok-alert" id="msg">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'msg\').toggle()",4000); }
            </script>';
}

//------------------------------------------------------------------------------
// Display Edit Form
//------------------------------------------------------------------------------
if (isset($_POST['editphoto']))
{
    $show_latest = false;

    $cleanPhoto = cleanInput($_POST['photo']);
    $cleanUrl   = cleanInput($_POST['url']);
    $gallery->displayEditPhotoForm($cleanPhoto, $cleanUrl);
}

//------------------------------------------------------------------------------
// Delete photo confirmation
//------------------------------------------------------------------------------
if (isset($_POST['deletephoto']) && !isset($_POST['confirmed']))
{
    $show_latest = false;
    echo '
                <div class="info-alert clearfix">
                    <form action="index.php" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this Photo?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="photo" value="'.$_POST['photo'].'"/>
                            <input type="hidden" name="url" value="'.$_POST['url'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="index.php?'.$_POST['url'].'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';
}
//------------------------------------------------------------------------------
// Delete Photo
//------------------------------------------------------------------------------
elseif (isset($_POST['delconfirm']) || (isset($_POST['confirmed']) && !isset($_POST['editphoto'])))
{
    $show_latest = false;

    $cleanPhotoId = cleanInput($_POST['photo'], 'int');

    // Get photo info
    $sql = "SELECT `user`, `category`, `filename` 
            FROM `fcms_gallery_photos` 
            WHERE `id` = '$cleanPhotoId'";

    $result = mysql_query($sql) or displaySQLError(
        'Photo Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
    );

    $filerow = mysql_fetch_array($result);

    $photoFilename = $filerow['filename'];
    $photoUserId   = $filerow['user'];
    $photoCategory = $filerow['category'];
    
    // Remove the photo from the DB
    $sql = "DELETE FROM `fcms_gallery_photos` 
            WHERE `id` = '$cleanPhotoId'";
    mysql_query($sql) or displaySQLError(
        'Delete Photo Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
    );

    // Remove any comments for this photo
    $sql = "DELETE FROM `fcms_gallery_comments` 
            WHERE `photo` = '$cleanPhotoId'";
    mysql_query($sql) or displaySQLError(
        'Delete Comments Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
    );
    
    // Remove the Photo from the server
    unlink("../uploads/photos/member$photoUserId/".$photoFilename);
    unlink("../uploads/photos/member$photoUserId/tb_".$photoFilename);

    $sql = "SELECT `value` As 'full_size_photos'
            FROM `fcms_config`
            WHERE `name` = 'full_size_photos'
            LIMIT 1";

    $result = mysql_query($sql) or displaySQLError(
        'Full Size Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
    );

    $r = mysql_fetch_array($result);

    if ($r['full_size_photos'] == 1)
    {
        unlink("../uploads/photos/member$photoUserId/full_".$photoFilename);
    }
    $gallery->displayGalleryMenu($photoUserId);
    $gallery->showCategories(1, $photoUserId, $photoCategory);
}

//------------------------------------------------------------------------------
// Do you have access to perform actions?
//------------------------------------------------------------------------------
if (isset($_GET['action']) && (checkAccess($currentUserId) <= 3 || checkAccess($currentUserId) == 8 || checkAccess($currentUserId) == 5))
{
    // We don't want to show the gallery menu on delete confirmation screen
    // or advanced upload
    if ((!isset($_POST['delcat']) || isset($_POST['confirmedcat'])) && $_GET['action'] != 'advanced')
    {
        $gallery->displayGalleryMenu('none');
    }
    
    //-----------------------------------------------
    // Upload a photo
    //-----------------------------------------------
    if ($_GET['action'] == "upload")
    {
        // Turn on advanced uploader
        if (isset($_GET['advanced']))
        {
            $sql = "UPDATE `fcms_user_settings`
                    SET `advanced_upload` = '1'
                    WHERE `user` = '$currentUserId'";
            if (!mysql_query($sql))
            {
                displaySQLError('Advanced Uploader Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            }
        }

        $show_latest = false;

        $newPhotoId = 0;

        if (isset($_POST['addphoto']))
        {
            // Catch photos that are too large
            if ($_FILES['photo_filename']['error'] == 1)
            {
                $max = ini_get('upload_max_filesize');
                echo '
            <div class="error-alert">
                <p>'.sprintf(T_('Your photo exceeds the maximum size allowed by your PHP settings [%s].'), $max).'</p>
                <p>'.T_('Please try the \'Advanced Photo Uploader\' instead.').'</p>
            </div>
        </div><!-- #gallery .centercontent -->';
                include_once getTheme($currentUserId, $TMPL['path']).'footer.php';
                exit();
            }

            // Make sure we have a category
            if (empty($_POST['new-category']) and empty($_POST['category']))
            { 
                echo '
            <p class="error-alert">'.T_('You must choose a category first.').'</p>';

            } else {

                // Create a new category
                if (!empty($_POST['new-category']))
                {
                    $sql = "INSERT INTO `fcms_category`(`name`, `type`, `user`) 
                            VALUES (
                                '".cleanInput($_POST['new-category'])."', 
                                'gallery', 
                                '$currentUserId'
                            )";
                    mysql_query($sql) or displaySQLError(
                        'New Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
                    );
                    $cleanCategory = mysql_insert_id();
                }
                // Existing category
                else
                {
                    $cleanCategory = cleanInput($_POST['category']);
                }

                // Rotate photo
                if (isset($_POST['rotate']))
                {
                    $cleanRotate = cleanInput($_POST['rotate']);
                }
                else
                {
                    $cleanRotate = '0';
                }

                $cleanCaption = cleanInput($_POST['photo_caption']);

                $memory = isset($_POST['memory_override']) ? true : false;

                // Upload photo
                $newPhotoId = $gallery->uploadPhoto(
                    $cleanCategory, 
                    $_FILES['photo_filename'], 
                    $cleanCaption, 
                    $cleanRotate,
                    $memory
                );

                // Photo Uploaded successfully
                if ($newPhotoId !== false)
                {
                    // Tag photo
                    if (isset($_POST['tagged']))
                    {
                        $sql = "INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`)
                                VALUES ";

                        $first = true;
                        foreach ($_POST['tagged'] as $member)
                        {
                            if (!$first)
                            {
                                $sql .= ", ";
                            }
                            $sql .= "('".cleanInput($member, 'int')."', '$newPhotoId') ";

                            $first = false;
                        }
                        mysql_query($sql) or displaySQLError(
                            'Tagging Error', __FILE__.' ['.__LINE__.']', 
                            $sql, mysql_error()
                        );
                    }

                    // Email members
                    $sql = "SELECT u.`email`, s.`user` 
                            FROM `fcms_user_settings` AS s, `fcms_users` AS u 
                            WHERE `email_updates` = '1'
                            AND u.`id` = s.`user`";

                    $result = mysql_query($sql) or displaySQLError(
                        'Email Updates Error', __FILE__.' ['.__LINE__.']', 
                        $sql, mysql_error()
                    );
                    if (mysql_num_rows($result) > 0)
                    {
                        while ($r = mysql_fetch_array($result))
                        {
                            $name          = getUserDisplayName($currentUserId);
                            $to            = getUserDisplayName($r['user']);
                            $subject       = sprintf(T_('%s has added a new photo.'), $name);
                            $email         = $r['email'];
                            $url           = getDomainAndDir();
                            $email_headers = getEmailHeaders();

                            $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'index.php?uid='.$currentUserId.'&cid='.$cleanCategory.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
                            mail($email, $subject, $msg, $email_headers);
                        }
                    }
                }
            }
        }

        // Show the upload form
        if ($newPhotoId !== false)
        {
            if (usingAdvancedUploader($currentUserId))
            {
                $gallery->displayJavaUploadForm();
            }
            else
            {
                $overrideMemoryLimit = isset($_GET['memory']) ? true : false;
                $gallery->displayUploadForm($overrideMemoryLimit);
            }
        }
    }
    //-----------------------------------------------
    // Advanced Upload
    //-----------------------------------------------
    elseif ($_GET['action'] == 'advanced')
    {
        // Submit the edited photos 
        if (isset($_POST['submit_advanced_edit']))
        {
            // Loop through each photo
            for ($i=0; $i < count($_POST['id']); $i++)
            {
                $cleanCaption  = cleanInput($_POST['caption'][$i]);
                $cleanCategory = cleanInput($_POST['category'][0]); // categories are always the same
                $cleanId       = cleanInput($_POST['id'][$i]);

                // Update the caption
                $sql = "UPDATE `fcms_gallery_photos` 
                        SET `category` = '$cleanCategory', 
                            `caption`  = '$cleanCaption' 
                        WHERE `id` = '$cleanId'";
                mysql_query($sql) or displaySQLError(
                    'Edit Photo Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
                );

                // Tag members
                if (isset($_POST['tagged']))
                {
                    $sql = "INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`) 
                            VALUES ";

                    $first = true;
                    if (isset($_POST['tagged'][$i]))
                    {
                        foreach ($_POST['tagged'][$i] as $member)
                        {
                            if (!$first)
                            {
                                $sql .= ", ";
                            }
                            $sql .= "('".cleanInput($member, 'int')."', '$cleanId') ";

                            $first = false;
                        }
                        mysql_query($sql) or displaySQLError(
                            'Tagging Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
                        );
                    }
                }
            }
        }
        // Show edit form
        else
        {
            $show_latest = false;
            $gallery->displayAdvancedUploadEditForm();
            // clear the photos in the session
            unset($_SESSION['photos']);
            unset($_SESSION['mass_photos_category']);
        }
    }
    //-----------------------------------------------
    // Edit/Create Category
    //-----------------------------------------------
    elseif ($_GET['action'] == "category")
    {
        $show_latest = false;
        $show_cat    = true;

        // New category
        if (isset($_POST['newcat']))
        {
            if (empty($_POST['cat_name']))
            {
                echo '
            <p class="error-alert">'.T_('You must specify a category name.').'</p>';
            }
            else
            {
                $sql = "INSERT INTO `fcms_category`(`name`, `type`, `user`) 
                        VALUES (
                            '".cleanInput($_POST['cat_name'])."', 
                            'gallery', 
                            '$currentUserId'
                        )";
                mysql_query($sql) or displaySQLError(
                    'New Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
                );
                echo '
            <div class="ok-alert">
                <p>'.sprintf(T_('The Category %s was Created Successfully.'), "<b>".$_POST['cat_name']."</b>").'
                <p><small><a href="?action=upload">'.T_('Upload Photos').'</a></small></p>
            </div>';
            }
        }

        // Existing category
        if (isset($_POST['editcat']))
        {
            if (empty($_POST['cat_name']))
            {
                echo '
            <p class="error-alert">'.T_('Category name cannot be blank.').'</p>';
            }
            else
            {
                $sql = "UPDATE fcms_category 
                        SET `name` = '".cleanInput($_POST['cat_name'])."' 
                        WHERE `id` = '".cleanInput($_POST['cid'], 'int')."'";
                mysql_query($sql) or displaySQLError(
                    'Update Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
                );
                echo '
            <p class="ok-alert">'.sprintf(T_('The Category %s was Updated Successfully'), "<b>".$_POST['cat_name']."</b>").'</p>';
            }
        }

        // Delete category confirmation
        if (isset($_POST['delcat']) && !isset($_POST['confirmedcat']))
        {
            $show_cat = false;
            echo '
                <div class="info-alert clearfix">
                    <form action="index.php?action=category" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this category?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="cid" value="'.(int)$_POST['cid'].'"/>
                            <input style="float:left;" type="submit" id="delconfirmcat" name="delconfirmcat" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="index.php?action=category">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';
        }
        // Delete category
        elseif (isset($_POST['delconfirmcat']) || (isset($_POST['confirmedcat']) && !isset($_POST['editcat'])))
        { 
            $sql = "DELETE FROM fcms_category 
                    WHERE `id` = '".cleanInput($_POST['cid'], 'int')."'";
            mysql_query($sql) or displaySQLError(
                'Delete Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
            echo '
            <p class="ok-alert">'.T_('Category Deleted Successfully').'</p>';
        }

        // Show form
        if ($show_cat)
        {
            $gallery->displayAddCatForm();
        }
    }
}

//------------------------------------------------------------------------------
// Submit Mass Tag
//------------------------------------------------------------------------------
if (isset($_POST['submit_mass_tag']))
{
    $show_latest = false;

    $category = cleanInput($_GET['cid']);
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
        // members have been tagged in this photo
        if (isset($_POST['tagged'][$pid]))
        {
            $tagged = $_POST['tagged'][$pid];

            // anyone previously tagged?
            if (isset($_POST['prev_tagged_users'][$pid]))
            {
                $prev_tagged = $_POST['prev_tagged_users'][$pid];

                // remove users that were tagged but aren't anymore
                foreach ($prev_tagged as $uid)
                {
                    $key = array_search($uid, $tagged);
                    if ($key === false)
                    {
                        $sql = "DELETE FROM `fcms_gallery_photos_tags` 
                                WHERE `photo` = '".cleanInput($pid, 'int')."' 
                                AND `user` = '".cleanInput($uid, 'int')."'";

                        if (!mysql_query($sql))
                        {
                            displaySQLError('Delete Tag Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                            return;
                        }
                    }
                }

                // tag members that were not previously tagged
                foreach ($tagged as $uid)
                {
                    $key = array_search($uid, $prev_tagged);
                    if ($key === false)
                    {
                        $sql = "INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`) 
                                VALUES (
                                    '".cleanInput($uid, 'int')."', 
                                    '".cleanInput($pid, 'int')."'
                                )";

                        if (!mysql_query($sql))
                        {
                            displaySQLError('Tag Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                            return;
                        }
                    }
                }
            }
            // no one was previously tagged
            else
            {
                // add all tagged members, since no one was previously tagged
                foreach ($tagged as $uid)
                {
                    $sql = "INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`) 
                            VALUES (
                                '".cleanInput($uid, 'int')."', 
                                '".cleanInput($pid, 'int')."'
                            )";

                    if (!mysql_query($sql))
                    {
                        displaySQLError('Tag Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                    }
                }
            }
        }
        // no tagged members means that we are untagging everyone in this photo
        elseif (isset($_POST['prev_tagged_users'][$pid]))
        {
            $sql = "DELETE FROM `fcms_gallery_photos_tags` 
                    WHERE `photo` = '".cleanInput($pid, 'int')."'";

            if (!mysql_query($sql))
            {
                displaySQLError('Delete Tag Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            }
        }
    }

    echo '
            <p class="ok-alert" id="msg">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'msg\').toggle()",4000); }
            </script>';
}

//------------------------------------------------------------------------------
// View - Member Category
//------------------------------------------------------------------------------
if (isset($_GET['uid']) && !isset($_GET['cid']) && !isset($_GET['pid']))
{
    $show_latest = false;

    $page = isset($_GET['page']) ? $page = $_GET['page'] : $page = 1;

    $gallery->displayGalleryMenu($_GET['uid']);
    $gallery->showCategories($page, $_GET['uid']);
}
//------------------------------------------------------------------------------
// View - Category
//------------------------------------------------------------------------------
elseif (isset($_GET['cid']) && !isset($_GET['pid']))
{
    $show_latest = false;

    $page = isset($_GET['page']) ? $page = $_GET['page'] : $page = 1;

    $gallery->displayGalleryMenu($_GET['uid'], $_GET['cid']);
    $gallery->showCategories($page, $_GET['uid'], $_GET['cid']);
}
//------------------------------------------------------------------------------
// View - Photo
//------------------------------------------------------------------------------
elseif (isset($_GET['pid']))
{
    $show_latest = false;
    $show_photo  = true;

    // Add Comment
    if (isset($_POST['addcom']))
    {
        $com = ltrim($_POST['post']);
        if (!empty($com))
        {
            $sql = "INSERT INTO `fcms_gallery_comments` (
                        `photo`, `comment`, `date`, `user`
                    ) VALUES (
                        '".cleanInput($_GET['pid'], 'int')."', 
                        '".cleanInput($_POST['post'])."', 
                        NOW(), 
                        '$currentUserId'
                    )";
            mysql_query($sql) or displaySQLError(
                'Add Comment Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }
    }

    // Delete Comment confirmation
    if (isset($_POST['delcom']) && !isset($_POST['confirmedcom']))
    {
        $show_photo = false;
        echo '
                <div class="info-alert clearfix">
                    <form action="index.php?uid='.(int)$_GET['uid'].'&amp;cid='.(int)$_GET['cid'].'&amp;pid='.(int)$_GET['pid'].'" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this Comment?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.(int)$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delconfirmcom" name="delconfirmcom" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="index.php?uid='.(int)$_GET['uid'].'&amp;cid='.(int)$_GET['cid'].'&amp;pid='.(int)$_GET['pid'].'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';
    }
    // Delete Comment
    elseif (isset($_POST['delconfirmcom']) || isset($_POST['confirmedcom']))
    {
        $sql = "DELETE FROM `fcms_gallery_comments` 
                WHERE `id` = '".cleanInput($_POST['id'], 'int')."'";
        mysql_query($sql) or displaySQLError(
            'Delete Comment Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
    }

    // Vote
    if (isset($_GET['vote']))
    {
        $sql = "UPDATE `fcms_gallery_photos` 
                SET `votes` = `votes`+1, 
                    `rating` = `rating`+".cleanInput($_GET['vote'], 'int')." 
                WHERE `id` = '".cleanInput($_GET['pid'], 'int')."'";
        mysql_query($sql) or displaySQLError(
            'Vote Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
    }
    if ($show_photo)
    {
        $uid = cleanInput($_GET['uid'], 'int');
        $cid = cleanInput($_GET['cid']); // not always an #
        $pid = cleanInput($_GET['pid'], 'int');

        $gallery->showPhoto($uid, $cid, $pid);
    }
}

//------------------------------------------------------------------------------
// Mass Tag
//------------------------------------------------------------------------------
if (isset($_GET['tag']))
{
    $show_latest = false;

    $category = cleanInput($_GET['tag'], 'int');
    $user     = cleanInput($_GET['user'], 'int');

    $gallery->displayMassTagCategory($category, $user);
}

//------------------------------------------------------------------------------
// Search
//------------------------------------------------------------------------------
if (isset($_GET['search']))
{
    $show_latest = false;
    $gallery->displayGalleryMenu();
    $gallery->displaySearchForm();
}

//------------------------------------------------------------------------------
// Show Latest Categories/Comments
//------------------------------------------------------------------------------
if ($show_latest)
{
    $gallery->displayGalleryMenu();
    $gallery->displayLatestCategories();
    $gallery->showCategories(-1, 0, 'comments');
    echo '
            <p class="alignright"><a class="rss" href="../rss.php?feed=gallery">'.T_('RSS Feed').'</a></p>';
}

echo '
        </div><!-- #gallery .centercontent -->';

// Show Footer
require_once getTheme($currentUserId, $TMPL['path']).'footer.php';
