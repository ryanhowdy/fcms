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
define('GALLERY_PREFIX', '../gallery/');

require URL_PREFIX.'fcms.php';

load('gallery');

init('admin/');

// Globals
$gallery = new PhotoGallery($fcmsError, $fcmsDatabase, $fcmsUser);
$page    = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $gallery);

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
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsPhotoGallery)
    {
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;
        $this->fcmsPhotoGallery = $fcmsPhotoGallery;

        $this->fcmsTemplate = array(
            'sitename'      => getSiteName(),
            'nav-link'      => getAdminNavLinks(),
            'pagetitle'     => T_('Administration: Photo Gallery'),
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
        if ($this->fcmsUser->access > 2)
        {
            $this->displayInvalidAccessLevel();
            return;
        }
        // Delete Categories
        elseif (isset($_POST['deleteAll']) && isset($_POST['bulk_actions']))
        {
            if (isset($_GET['confirmed']))
            {
                $this->displayDeleteAllCategoriesSubmit();
            }
            else
            {
                $this->displayConfirmDeleteAllCategoriesForm();
            }
        }
        // Delete Photos
        elseif (isset($_POST['deleteAllPhotos']) && isset($_POST['bulk_actions']))
        {
            if (isset($_GET['confirmed']))
            {
                $this->displayDeleteAllPhotosSubmit();
            }
            else
            {
                $this->displayConfirmDeleteAllPhotosForm();
            }
        }
        elseif (isset($_GET['edit']))
        {
            $this->displayEditCategoryForm();
        }
        else
        {
            $this->displayLatestCategoriesForm();
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
<script src="'.URL_PREFIX.'ui/js/prototype.js" type="text/javascript"></script>
<script src="'.URL_PREFIX.'ui/js/fcms.js" type="text/javascript"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    deleteConfirmationLink("deleteAll", "'.T_('Are you sure you want to DELETE all selected categories?').'");
    deleteConfirmationLink("deleteAllPhotos", "'.T_('Are you sure you want to DELETE all selected photos?').'");
    $("check_all_form").getInputs("checkbox").each(function(item) {
        item.observe("click", function () {
            if (item.checked) {
                item.up("label").addClassName("active");
            } else {
                item.up("label").removeClassName("active");
            }
        });
    });
});
//]]>
</script>';

        include_once URL_PREFIX.'ui/admin/header.php';

        echo '
        <div class="admin-gallery">';
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
        </div><!-- /admin-gallery -->';

        include_once URL_PREFIX.'ui/admin/footer.php';
    }

    /**
     * displayInvalidAccessLevel 
     * 
     * @return void
     */
    function displayInvalidAccessLevel ()
    {
        $this->displayHeader();

        echo '
            <p class="alert-message block-message error">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 1 (Admin).').' 
                <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

        $this->displayFooter();
    }

    /**
     * displayLatestCategoriesForm 
     * 
     * @return void
     */
    function displayLatestCategoriesForm ()
    {
        $this->displayHeader();

        $page    = getPage();
        $perPage = 10;
        $from    = ($page * $perPage) - $perPage;

        $sql = "SELECT * 
                FROM (
                    SELECT p.`id`, p.`date`, p.`filename`, c.`name`, p.`user`, p.`category`,
                        e.`thumbnail`, p.`external_id`
                    FROM `fcms_gallery_photos` AS p
                    LEFT JOIN `fcms_category` AS c               ON p.`category`    = c.`id`
                    LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                    ORDER BY `date` DESC
                ) AS sub
                GROUP BY `category`
                ORDER BY `date` DESC 
                LIMIT $from, $perPage";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        if (count($rows) <= 0)
        {
            echo '
            <p>'.T_('No photos have been added yet.').'</p>';

            $this->displayFooter();
            return;
        }

        $message = '';

        if (isset($_SESSION['success']))
        {
            $message  = '<div class="alert-message success">';
            $message .= '<a class="close" href="#" onclick="$(this).up(\'div\').hide(); return false;">&times;</a>';
            $message .= T_('Changes Updated Successfully').'</div>';

            unset($_SESSION['success']);
        }

        echo '
            '.$message.'
            <form id="check_all_form" name="check_all_form" action="gallery.php" method="post">
                <ul class="unstyled clearfix">';

        foreach ($rows as $row)
        {
            $count = $this->fcmsPhotoGallery->getCategoryPhotoCount($row['category']);

            $photoSrc = $this->fcmsPhotoGallery->getPhotoSource($row);

            echo '
                    <li>
                        <label for="'.$row['category'].'">
                            <b>'.cleanOutput($row['name']).'</b><br/>
                            <i>'.sprintf(T_('%d photos'), $count).'</i><br/>
                            <img src="'.$photoSrc.'" alt="'.cleanOutput($row['name']).'"/><br/>
                            <input type="checkbox" id="'.$row['category'].'" name="bulk_actions[]" value="'.$row['category'].'"/>
                        </label>
                        <p>
                            <a href="?edit='.$row['category'].'">'.T_('Edit').'</a>
                        </p>
                    </li>';

        }

        echo '
                </ul>
                <p><input type="submit" class="btn danger" id="deleteAll" name="deleteAll" value="'.T_('Delete Selected').'"/></p>
            </form>';

        // Pagination

        // Remove the LIMIT from the $sql statement 
        // used above, so we can get the total count
        $sql = substr($sql, 0, strpos($sql, 'LIMIT'));

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $count       = count($rows);
        $total_pages = ceil($count / $perPage); 

        displayPages("gallery.php", $page, $total_pages);

        $this->displayFooter();
    }

    /**
     * displayConfirmDeleteAllCategoriesForm 
     * 
     * @return void
     */
    function displayConfirmDeleteAllCategoriesForm ()
    {
        $this->displayHeader();

        echo '
            <div class="alert-message block-message warning">
                <form action="gallery.php?confirmed=1" method="post">
                    <h2>'.T_('Are you sure you want to DELETE all selected categories?').'</h2>
                    <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                    <div class="alert-actions">';

        foreach ($_POST['bulk_actions'] AS $id)
        {
            echo '
                        <input type="hidden" name="bulk_actions[]" value="'.(int)$id.'"/>';
        }

        echo '
                        <input class="btn danger" type="submit" id="deleteAll" name="deleteAll" value="'.T_('Yes, Delete').'"/>
                        <a class="btn secondary" href="gallery.php">'.T_('No, Cancel').'</a>
                    </div>
                </form>
            </div>';

        $this->displayFooter();
    }

    /**
     * displayDeleteAllCategoriesSubmit 
     * 
     * @return void
     */
    function displayDeleteAllCategoriesSubmit ()
    {
        foreach ($_POST['bulk_actions'] AS $category)
        {
            $category = (int)$category;

            // Delete all photos in category
            $sql = "DELETE FROM `fcms_gallery_photos`
                    WHERE `category` = ?";

            if (!$this->fcmsDatabase->delete($sql, $category))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            // Delete all category comments
            $sql = "DELETE FROM `fcms_gallery_category_comment`
                    WHERE `category_id` = ?";

            if (!$this->fcmsDatabase->delete($sql, $category))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            // Delete category
            $sql = "DELETE FROM `fcms_category`
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->delete($sql, $category))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

        }

        $_SESSION['success'] = 1;

        header("Location: gallery.php");
    }

    /**
     * displayConfirmDeleteAllPhotosForm 
     * 
     * @return void
     */
    function displayConfirmDeleteAllPhotosForm ()
    {
        $this->displayHeader();

        $url = 'edit='.(int)$_GET['edit'];

        echo '
            <div class="alert-message block-message warning">
                <form action="gallery.php?'.$url.'&amp;confirmed=1" method="post">
                    <h2>'.T_('Are you sure you want to DELETE all selected photos?').'</h2>
                    <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                    <div class="alert-actions">';

        foreach ($_POST['bulk_actions'] AS $id)
        {
            echo '
                        <input type="hidden" name="bulk_actions[]" value="'.(int)$id.'"/>';
        }

        echo '
                        <input class="btn danger" type="submit" id="deleteAllPhotos" name="deleteAllPhotos" value="'.T_('Yes, Delete').'"/>
                        <a class="btn secondary" href="gallery.php?'.$url.'">'.T_('No, Cancel').'</a>
                    </div>
                </form>
            </div>';

        $this->displayFooter();
    }

    /**
     * displayDeleteAllPhotosSubmit 
     * 
     * @return void
     */
    function displayDeleteAllPhotosSubmit ()
    {
        foreach ($_POST['bulk_actions'] AS $id)
        {
            $id = (int)$id;

            $sql = "DELETE FROM `fcms_gallery_photos`
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->delete($sql, $id))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        $_SESSION['success'] = 1;

        if (isset($_GET['edit']))
        {
            header("Location: gallery.php?edit=".(int)$_GET['edit']);
            return;
        }

        header("Location: gallery.php");
    }

    /**
     * displayEditCategoryForm 
     * 
     * @return void
     */
    function displayEditCategoryForm ()
    {
        $this->displayHeader();

        $category = (int)$_GET['edit'];

        $sql = "SELECT p.`id`, p.`date`, p.`filename`, c.`name` AS category, p.`user`, p.`caption`, p.`views`,
                    p.`external_id`, e.`thumbnail`
                FROM `fcms_gallery_photos` AS p
                LEFT JOIN `fcms_category` AS c               ON p.`category`    = c.`id`
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                WHERE p.`category` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, $category);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        if (count($rows) <= 0)
        {
            echo '
            <p>'.T_('This category contains no photos.').'</p>';

            $this->displayFooter();
            return;
        }

        $message = '';

        if (isset($_SESSION['success']))
        {
            $message  = '<div class="alert-message success">';
            $message .= '<a class="close" href="#" onclick="$(this).up(\'div\').hide(); return false;">&times;</a>';
            $message .= T_('Changes Updated Successfully').'</div>';

            unset($_SESSION['success']);
        }

        echo '
            <p><a href="gallery.php">'.T_('Categories').'</a></p>
            '.$message.'
            <form id="check_all_form" name="check_all_form" action="gallery.php?edit='.$category.'" method="post">
                <ul class="unstyled clearfix">';

        foreach ($rows as $row)
        {
            $photoSrc = $this->fcmsPhotoGallery->getPhotoSource($row);

            echo '
                    <li>
                        <label for="'.$row['id'].'">
                            <img src="'.$photoSrc.'" alt="'.cleanOutput($row['caption']).'"/><br/>
                            <input type="checkbox" id="'.$row['id'].'" name="bulk_actions[]" value="'.$row['id'].'"/>
                        </label>
                    </li>';
        }

        echo '
                </ul>
                <p>
                    <input type="submit" class="btn danger" id="deleteAllPhotos" name="deleteAllPhotos" value="'.T_('Delete Selected').'"/>
                    <a class="btn secondary" href="gallery.php">'.T_('Cancel').'</a>
                </p>
            </form>';

        $this->displayFooter();
    }
}
