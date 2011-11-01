<?php
/**
 * Photo Gallery
 * 
 * PHP versions 4 and 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2010 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '../');

require URL_PREFIX.'fcms.php';

load('gallery', 'database');

init('admin/');

$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$gallery       = new PhotoGallery($currentUserId);

// Setup the Template variables
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Photo Gallery'),
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
    // Delete Confirmation All
    if ($(\'deleteAll\')) {
        var item = $(\'deleteAll\'); 
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE all selected categories?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmedall\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    }
    $$(".tag_photo input").each(function(el) {
        el.observe("click", clickMassTagMember);

    });
});
//]]>
</script>';

// Show Header
require_once getTheme($currentUserId, $TMPL['path']).'header.php';

echo '
        <div class="centercontent">';

//--------------------------------------------------------------------------
// Check Access
//--------------------------------------------------------------------------
if (checkAccess($currentUserId) > 1)
{
    echo '
            <div class="error-alert">
                <h3>'.T_('You do not have access to view this page.').'</h3>
                <p>'.T_('This page requires an access level 1 (Admin).').'</p>
                <p>
                    <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
                </p>
            </div>
        </div><!-- .centercontent -->';

    include_once getTheme($currentUserId, $TMPL['path']).'footer.php';
    exit();
}

$show = true;

//--------------------------------------------------------------------------
// Confirm Delete All Categories
//--------------------------------------------------------------------------
if (isset($_POST['deleteAll']) && !isset($_POST['confirmedall']) && isset($_POST['bulk_actions']))
{
    $show = false;
    echo '
            <div class="info-alert clearfix">
                <form action="gallery.php" method="post">
                    <h2>'.T_('Are you sure you want to DELETE all selected categories?').'</h2>
                    <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                    <div>';

    foreach ($_POST['bulk_actions'] AS $id)
    {
        echo '
                        <input type="hidden" name="bulk_actions[]" value="'.$id.'"/>';
    }

    echo '
                        <input type="hidden" id="confirmedall" name="confirmedall" value="'.T_('Yes').'"/>
                        <input style="float:left;" type="submit" id="deleteAll" name="deleteAll" value="'.T_('Yes').'"/>
                        <a style="float:right;" href="gallery.php">'.T_('Cancel').'</a>
                    </div>
                </form>
            </div>';
}

//--------------------------------------------------------------------------
// Delete All Categories
//--------------------------------------------------------------------------
if (isset($_POST['deleteAll']) && isset($_POST['confirmedall']) && isset($_POST['bulk_actions']))
{
    foreach ($_POST['bulk_actions'] AS $category)
    {
        $category = cleanInput($category, 'int');

        $sql = "DELETE FROM `fcms_gallery_photos`
                WHERE `category` = '$category'";
        if (!mysql_query($sql))
        {
            displaySQLError('Delete Photos Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            exit();
        }
        $sql = "DELETE FROM `fcms_category`
                WHERE `id` = '$category'";
        if (!mysql_query($sql))
        {
            displaySQLError('Delete Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            exit();
        }
    }
    echo '
            <p class="ok-alert">'.T_('Categories Deleted').'</p>';
}

//--------------------------------------------------------------------------
// Confirm Delete All Photos
//--------------------------------------------------------------------------
if (isset($_POST['deleteAllPhotos']) && !isset($_POST['confirmedall']) && isset($_POST['bulk_actions']))
{
    $show = false;
    echo '
            <div class="info-alert clearfix">
                <form action="gallery.php" method="post">
                    <h2>'.T_('Are you sure you want to DELETE all selected photos?').'</h2>
                    <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                    <div>';

    foreach ($_POST['bulk_actions'] AS $id)
    {
        echo '
                        <input type="hidden" name="bulk_actions[]" value="'.$id.'"/>';
    }

    echo '
                        <input type="hidden" id="confirmedall" name="confirmedall" value="'.T_('Yes').'"/>
                        <input style="float:left;" type="submit" id="deleteAllPhotos" name="deleteAllPhotos" value="'.T_('Yes').'"/>
                        <a style="float:right;" href="gallery.php">'.T_('Cancel').'</a>
                    </div>
                </form>
            </div>';
}

//--------------------------------------------------------------------------
// Delete All Photos
//--------------------------------------------------------------------------
if (isset($_POST['deleteAllPhotos']) && isset($_POST['confirmedall']) && isset($_POST['bulk_actions']))
{
    foreach ($_POST['bulk_actions'] AS $id)
    {
        $id = cleanInput($id, 'int');

        $sql = "DELETE FROM `fcms_gallery_photos`
                WHERE `id` = '$id'";
        if (!mysql_query($sql))
        {
            displaySQLError('Delete Photo Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            exit();
        }
    }
    echo '
            <p class="ok-alert">'.T_('Photos Deleted').'</p>';
}

//--------------------------------------------------------------------------
// Show
//--------------------------------------------------------------------------
if ($show)
{
    // Show Photos
    if (isset($_GET['edit']))
    {
        $category = cleanInput($_GET['edit'], 'int');
        $gallery->displayAdminDeletePhotos($category);
    }
    // Show Categories
    else
    {
        $page = (isset($_GET['page'])) ? $_GET['page'] : 1;
        $gallery->displayAdminDeleteCategories($page);
    }
}

echo '
        </div><!-- .centercontent -->';

// Show Footer
require_once getTheme($currentUserId, $TMPL['path']).'footer.php';
