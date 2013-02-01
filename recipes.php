<?php
/**
 * Recipes
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

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('recipes', 'image', 'datetime');

init();

$rec  = new Recipes($fcmsError, $fcmsDatabase, $fcmsUser);
$img  = new Image($fcmsUser->id);
$page = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $rec, $img);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsRecipe, $fcmsImage)
    {
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;
        $this->fcmsRecipe       = $fcmsRecipe;
        $this->fcmsImage        = $fcmsImage;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Recipes'),
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
        if (isset($_GET['addrecipe']))
        {
            $this->displayAddRecipeForm();
        }
        elseif (isset($_POST['submitadd']))
        {
            $this->displayAddRecipeSubmit();
        }
        elseif (isset($_POST['editrecipe']))
        {
            $this->displayEditRecipeForm();
        }
        elseif (isset($_POST['submitedit']))
        {
            $this->displayEditRecipeSubmit();
        }
        elseif (isset($_GET['thumbnail']))
        {
            $this->displayEditThumbnailForm();
        }
        elseif (isset($_POST['changethumbnail']))
        {
            $this->displayEditThumbnailSubmit();
        }
        elseif (isset($_GET['add']))
        {
            $this->displayAddCategoryForm();
        }
        elseif (isset($_POST['submit-category']))
        {
            $this->displayAddCategorySubmit();
        }
        elseif (isset($_POST['delrecipe']))
        {
            if (isset($_POST['confirmed']))
            {
                $this->displayDeleteRecipeConfirmationSubmit();
            }
            else
            {
                $this->displayDeleteRecipeConfirmationForm();
            }
        }
        elseif (isset($_GET['categoryedit']))
        {
            $this->displayEditCategoryForm();
        }
        elseif (isset($_POST['submit_cat_edit']))
        {
            if (isset($_POST['delete']))
            {
                $this->displayDeleteCategorySubmit();
            }
            else
            {
                $this->displayEditCategorySubmit();
            }
        }
        elseif (isset($_GET['category']))
        {
            if (isset($_GET['id']))
            {
                if (isset($_POST['addcom']))
                {
                    $this->displayAddCommentSubmit();
                }
                elseif (isset($_POST['delcom']))
                {
                    $this->displayDeleteCommentSubmit();
                }
                else
                {
                    $this->displayRecipe();
                }
            }
            else
            {
                $this->displayCategory();
            }
        }
        else
        {
            $this->displayLatestRecipes();
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
    initHideAddFormDetails();
    if (!$$(\'.delrec input[type="submit"]\')) { return; }
    $$(\'.delrec input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    });
    if ($(\'toolbar\')) {
        $(\'toolbar\').removeClassName("hideme");
    }
    if ($(\'smileys\')) {
        $(\'smileys\').removeClassName("hideme");
    }
    if ($(\'upimages\')) {
        $(\'upimages\').removeClassName("hideme");
    }
    return true;
});
//]]>
</script>';

        include_once getTheme($this->fcmsUser->id).'header.php';

        echo '
        <div id="recipe-page" class="centercontent">';
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
        </div><!-- #recipe-page .centercontent -->';


        include_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayAddRecipeSubmit 
     * 
     * @return void
     */
    function displayAddRecipeSubmit ()
    {
        $name        = strip_tags($_POST['name']);
        $category    = (int)$_POST['category'];
        $ingredients = strip_tags($_POST['ingredients']);
        $directions  = strip_tags($_POST['directions']);
        $thumbnail   = 'no_recipe.jpg';

        $uploadsPath = getUploadsAbsolutePath();

        // Upload Recipe Image
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['name'] && $_FILES['thumbnail']['error'] < 1)
        {
            $this->fcmsImage->destination = $uploadsPath.'upimages/';
            $this->fcmsImage->uniqueName  = true;

            $thumbnail = $this->fcmsImage->upload($_FILES['thumbnail']);

            if ($this->fcmsImage->error == 1)
            {
                $this->displayHeader();

                echo '
    <p class="error-alert">
        '.sprintf(T_('Thumbnail [%s] is not a supported type. Thumbnails must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $this->img->name).'
    </p>';
                $this->displayFooter();
                return;
            }

            $this->fcmsImage->resize(100, 100);

            if ($this->fcmsImage->error > 0)
            {
                $this->displayHeader();

                echo '
    <p class="error-alert">
        '.T_('There was an error uploading your thumbnail.').'
    </p>';
                $this->displayFooter();
                return;
            }
        }

        $sql = "INSERT INTO `fcms_recipes` 
                    (`name`, `thumbnail`, `category`, `ingredients`, `directions`, `user`, `date`) 
                VALUES
                    (?, ?, ?, ?, ?, ?, NOW())";

        $params = array(
            $name, 
            $thumbnail,
            $category,
            $ingredients, 
            $directions, 
            $this->fcmsUser->id
        );

        $rec_id = $this->fcmsDatabase->insert($sql, $params);
        if ($rec_id === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Email members
        $sql = "SELECT u.`email`, s.`user` 
                FROM `fcms_user_settings` AS s, `fcms_users` AS u 
                WHERE `email_updates` = '1'
                AND u.`id` = s.`user`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $r)
            {
                $recipeUser    = getUserDisplayName($this->fcmsUser->id);
                $to            = getUserDisplayName($r['user']);
                $subject       = sprintf(T_('%s has added the recipe: %s'), $recipeUser, $name);
                $email         = $r['email'];
                $url           = getDomainAndDir();
                $email_headers = getEmailHeaders();

                $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'recipes.php?category='.$category.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
                mail($email, $subject, $msg, $email_headers);
            }
        }

        header("Location: recipes.php?category=$category&id=$rec_id");
    }

    /**
     * displayEditRecipeSubmit 
     * 
     * @return void
     */
    function displayEditRecipeSubmit ()
    {
        $id       = (int)$_POST['id'];
        $category = (int)$_POST['category'];

        $name        = strip_tags($_POST['name']);
        $ingredients = strip_tags($_POST['ingredients']);
        $directions  = strip_tags($_POST['directions']);

        $sql = "UPDATE `fcms_recipes` 
                SET `name`          = ?, 
                    `category`      = ?, 
                    `ingredients`   = ?,
                    `directions`    = ? 
                WHERE `id`          = ?";

        $params = array(
            $name, 
            $category, 
            $ingredients,
            $directions, 
            $id
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        header("Location: recipes.php?category=$category&id=$id");
    }

    /**
     * displayAddCategorySubmit 
     * 
     * @return void
     */
    function displayAddCategorySubmit ()
    {
        $this->displayHeader();

        $name = strip_tags($_POST['name']);

        $sql = "INSERT INTO `fcms_category`
                    (`name`, `type`, `user`)
                VALUES
                    (?, 'recipe', ?)";

        $params = array(
            $name,
            $this->fcmsUser->id
        );

        $cat = $this->fcmsDatabase->insert($sql, $params);
        if ($cat === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $this->fcmsRecipe->displayAddRecipeForm($cat);
        $this->displayFooter();
    }

    /**
     * displayDeleteRecipeConfirmationForm 
     * 
     * @return void
     */
    function displayDeleteRecipeConfirmationForm ()
    {
        $this->displayHeader();

        echo '
                <div class="info-alert">
                    <form action="recipes.php" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.(int)$_POST['id'].'"/>
                            <input type="submit" name="confirmed" value="1"/>
                            <input style="float:left;" type="submit" id="delrecipe" name="delrecipe" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="recipes.php">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

        $this->displayFooter();
    }

    /**
     * displayDeleteRecipeConfirmationSubmit 
     * 
     * @return void
     */
    function displayDeleteRecipeConfirmationSubmit ()
    {
        $id = (int)$_POST['id'];

        // Get recipe info
        $sql = "SELECT `user`, `category`
                FROM `fcms_recipes`
                WHERE `id` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $id);
        if ($row === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $category = $row['category'];

        // Only creator and admin can delete
        if ($row['user'] != $this->fcmsUser->id && $this->fcmsUser->access <= 1)
        {
            $this->displayHeader();

            echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

            $this->displayFooter();
            return;
        }

        // Delete
        $sql = "DELETE FROM `fcms_recipes` 
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->delete($sql, $id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        header("Location: recipes.php?category=$category");
    }

    /**
     * displayAddRecipeForm 
     * 
     * @return void
     */
    function displayAddRecipeForm ()
    {
        $this->displayHeader();

        $cat  = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

        if ($this->fcmsUser->access > 5)
        {
            echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

            $this->displayFooter();
            return;
        }

        $this->fcmsRecipe->displayAddRecipeForm($cat);
        $this->displayFooter();
    }

    /**
     * displayLatestRecipes 
     * 
     * @return void
     */
    function displayLatestRecipes ()
    {
        $this->displayHeader();

        $page = getPage();

        $this->fcmsRecipe->showRecipes($page);
        $this->displayFooter();
    }

    /**
     * displayEditRecipeForm 
     * 
     * @return void
     */
    function displayEditRecipeForm ()
    {
        $this->displayHeader();

        $id          = (int)$_POST['id'];
        $name        = $_POST['name'];
        $thumbnail   = $_POST['thumbnail'];
        $category    = $_POST['category'];
        $ingredients = $_POST['ingredients'];
        $directions  = $_POST['directions'];

        $this->fcmsRecipe->displayEditRecipeForm($id, $name, $thumbnail, $category, $ingredients, $directions);
        $this->displayFooter();
    }

    /**
     * displayEditThumbnailForm 
     * 
     * @return void
     */
    function displayEditThumbnailForm ()
    {
        $this->displayHeader();

        $id       = (int)$_GET['thumbnail'];
        $category = (int)$_GET['category'];

        echo '
            <form method="post" enctype="multipart/form-data" action="recipes.php">
                <fieldset>
                    <legend><span>'.T_('Change Thumbnail').'</span></legend>
                    <div>
                        <label for="thumbnail">'.T_('Thumbnail').'</label>
                        <input type="file" name="thumbnail" id="thumbnail"/>
                    </div>
                    <p>
                        <input type="hidden" id="id" name="id" value="'.$id.'"/>
                        <input type="hidden" id="category" name="category" value="'.$category.'"/>
                        <input class="sub1" type="submit" name="changethumbnail" value="'.T_('Change').'"/> &nbsp;
                        <a href="recipes.php?category='.$category.'&amp;id='.$id.'">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';

        $this->displayFooter();
    }

    /**
     * displayEditThumbnailSubmit 
     * 
     * @return void
     */
    function displayEditThumbnailSubmit ()
    {
        $id        = (int)$_POST['id'];
        $category  = (int)$_POST['category'];
        $thumbnail = 'no_recipe.jpg';

        $uploadsPath = getUploadsAbsolutePath();

        // Upload Recipe Image
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['name'] && $_FILES['thumbnail']['error'] < 1)
        {
            $this->fcmsImage->destination = $uploadsPath.'upimages/';
            $this->fcmsImage->uniqueName  = true;

            $thumbnail = $this->fcmsImage->upload($_FILES['thumbnail']);

            if ($this->fcmsImage->error == 1)
            {
                $this->displayHeader();

                echo '
    <p class="error-alert">
        '.sprintf(T_('Thumbnail [%s] is not a supported type. Thumbnails must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $this->img->name).'
    </p>';
                $this->displayFooter();
                return;
            }

            $this->fcmsImage->resize(100, 100);

            if ($this->fcmsImage->error > 0)
            {
                $this->displayHeader();

                echo '
    <p class="error-alert">
        '.T_('There was an error uploading your thumbnail.').'
    </p>';
                $this->displayFooter();
                return;
            }
        }

        $sql = "UPDATE `fcms_recipes` 
                SET `thumbnail` = ?
                WHERE `id` = ?";

        $params = array($thumbnail, $id);

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        header("Location: recipes.php?category=$category&id=$id");
    }

    /**
     * displayAddCategoryForm 
     * 
     * @return void
     */
    function displayAddCategoryForm ()
    {
        $this->displayHeader();

        if ($this->fcmsUser->access > 5)
        {
            echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

            $this->displayFooter();
            return;
        }

        $this->fcmsRecipe->displayAddCategoryForm();
        $this->displayFooter();
    }

    /**
     * displayAddCommentSubmit 
     * 
     * @return void
     */
    function displayAddCommentSubmit ()
    {
        $categoryId = (int)$_GET['category'];
        $recipeId   = (int)$_POST['recipe'];
        $comment    = strip_tags($_POST['comment']);

        $sql = "INSERT INTO `fcms_recipe_comment`
                    (`recipe`, `comment`, `user`, `date`)
                VALUES
                    (?, ?, ?, NOW())";

        $params = array(
            $recipeId,
            $comment,
            $this->fcmsUser->id
        );

        $comId = $this->fcmsDatabase->insert($sql, $params);
        if ($comId === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        header("Location: recipes.php?category=$categoryId&id=$recipeId#comment$comId");
    }

    /**
     * displayDeleteCommentSubmit 
     * 
     * @return void
     */
    function displayDeleteCommentSubmit ()
    {
        $categoryId = (int)$_GET['category'];
        $recipeId   = (int)$_POST['id'];

        if ($this->fcmsUser->id != $_POST['user'] && $this->fcmsUser->access > 2)
        {
            $this->displayHeader();

            echo '
        <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

            $this->displayFooter();
            return;
        }

        $sql = "DELETE FROM `fcms_recipe_comment`
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->delete($sql, $recipeId))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        header("Location: recipes.php?category=$categoryId&id=$recipeId");
    }

    /**
     * displayEditCategoryForm 
     * 
     * @return void
     */
    function displayEditCategoryForm ()
    {
        $this->displayHeader();

        if ($this->fcmsUser->access > 2)
        {
            echo '
        <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

            $this->displayFooter();
            return;
        }

        if (isset($_SESSION['ok']))
        {
            unset($_SESSION['ok']);

            displayOkMessage();
        }

        $this->fcmsRecipe->displayEditCategoryForm();
        $this->displayFooter();
    }

    /**
     * displayEditCategorySubmit 
     * 
     * @return void
     */
    function displayEditCategorySubmit ()
    {
        if ($this->fcmsUser->access > 2)
        {
            $this->displayHeader();

            echo '
        <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

            $this->displayFooter();
            return;
        }

        $ids = $_POST['id'];

        foreach ($_POST['category'] as $key => $category)
        {
            $id       = (int)$ids[$key];
            $category = strip_tags($category);

            $sql = "UPDATE `fcms_category` 
                    SET `name` = ? 
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->update($sql, array($category, $id)))
            {
                dislayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        $_SESSION['ok'] = 1;

        header("Location: recipes.php?categoryedit=1");
    }

    /**
     * displayDeleteCategorySubmit 
     * 
     * @return void
     */
    function displayDeleteCategorySubmit ()
    {
        if ($this->fcmsUser->access > 2)
        {
            $this->displayHeader();

            echo '
        <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

            $this->displayFooter();
            return;
        }

        foreach ($_POST['delete'] as $id)
        {
            // Delete recipes
            $sql = "DELETE FROM `fcms_recipes` 
                    WHERE `category` = ?";

            if (!$this->fcmsDatabase->delete($sql, $id))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            // Delete category
            $sql = "DELETE FROM `fcms_category` 
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->delete($sql, $id))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }
        }

        $_SESSION['ok'] = 1;

        header("Location: recipes.php?categoryedit=1");
    }

    /**
     * displayCategory 
     * 
     * @return void
     */
    function displayCategory ()
    {
        $page     = getPage();
        $category = (int)$_GET['category'];

        $this->displayHeader();
        $this->fcmsRecipe->showRecipeInCategory($category, $page);
        $this->displayFooter();
    }

    /**
     * displayRecipe 
     * 
     * @return void
     */
    function displayRecipe ()
    {
        $id       = (int)$_GET['id'];
        $category = (int)$_GET['category'];

        $this->displayHeader();
        $this->fcmsRecipe->showRecipe($category, $id);
        $this->displayFooter();
    }
}
