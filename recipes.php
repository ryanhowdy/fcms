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

load('recipes', 'image');

init();

$rec = new Recipes($fcmsUser->id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$img = new Image($fcmsUser->id);

$TMPL = array(
    'currentUserId' => $fcmsUser->id,
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Recipes'),
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
    if (isset($_GET['addrecipe']))
    {
        displayAddRecipeForm();
    }
    elseif (isset($_POST['submitadd']))
    {
        displayAddRecipeSubmit();
    }
    elseif (isset($_POST['editrecipe']))
    {
        displayEditRecipeForm();
    }
    elseif (isset($_POST['submitedit']))
    {
        displayEditRecipeSubmit();
    }
    elseif (isset($_GET['thumbnail']))
    {
        displayEditThumbnailForm();
    }
    elseif (isset($_POST['changethumbnail']))
    {
        displayEditThumbnailSubmit();
    }
    elseif (isset($_GET['add']))
    {
        displayAddCategoryForm();
    }
    elseif (isset($_POST['submit-category']))
    {
        displayAddCategorySubmit();
    }
    elseif (isset($_POST['delrecipe']))
    {
        if (isset($_POST['confirmed']))
        {
            displayDeleteRecipeConfirmationSubmit();
        }
        else
        {
            displayDeleteRecipeConfirmationForm();
        }
    }
    elseif (isset($_GET['categoryedit']))
    {
        displayEditCategoryForm();
    }
    elseif (isset($_POST['submit_cat_edit']))
    {
        if (isset($_POST['delete']))
        {
            displayDeleteCategorySubmit();
        }
        else
        {
            displayEditCategorySubmit();
        }
    }
    elseif (isset($_GET['category']))
    {
        if (isset($_GET['id']))
        {
            if (isset($_POST['addcom']))
            {
                displayAddCommentSubmit();
            }
            elseif (isset($_POST['delcom']))
            {
                displayDeleteCommentSubmit();
            }
            else
            {
                displayRecipe();
            }
        }
        else
        {
            displayCategory();
        }
    }
    else
    {
        displayLatestRecipes();
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

    include_once getTheme($fcmsUser->id).'header.php';

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
    global $fcmsUser, $TMPL;

    echo '
        </div><!-- #recipe-page .centercontent -->';


    include_once getTheme($fcmsUser->id).'footer.php';
}

/**
 * displayAddRecipeSubmit 
 * 
 * @return void
 */
function displayAddRecipeSubmit ()
{
    global $img, $fcmsUser;

    $name        = strip_tags($_POST['name']);
    $category    = (int)$_POST['category'];
    $ingredients = strip_tags($_POST['ingredients']);
    $directions  = strip_tags($_POST['directions']);

    $cleanName        = escape_string($name);
    $cleanIngredients = escape_string($ingredients);
    $cleanDirections  = escape_string($directions);
    $thumbnail        = 'no_recipe.jpg';

    $uploadsPath = getUploadsAbsolutePath();

    // Upload Recipe Image
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['name'] && $_FILES['thumbnail']['error'] < 1)
    {
        $img->destination = $uploadsPath.'upimages/';
        $img->uniqueName  = true;

        $thumbnail = $img->upload($_FILES['thumbnail']);

        if ($img->error == 1)
        {
            displayHeader();

            echo '
    <p class="error-alert">
        '.sprintf(T_('Thumbnail [%s] is not a supported type. Thumbnails must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $this->img->name).'
    </p>';
            displayFooter();
            return;
        }

        $img->resize(100, 100);

        if ($img->error > 0)
        {
            displayHeader();

            echo '
    <p class="error-alert">
        '.T_('There was an error uploading your thumbnail.').'
    </p>';
            displayFooter();
            return;
        }
    }

    $sql = "INSERT INTO `fcms_recipes` 
                (`name`, `thumbnail`, `category`, `ingredients`, `directions`, `user`, `date`) 
            VALUES(
                '$cleanName', 
                '$thumbnail',
                '$category',
                '$cleanIngredients', 
                '$cleanDirections', 
                '$fcmsUser->id', 
                NOW()
            )";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $rec_id = mysql_insert_id();

    // Email members
    $sql = "SELECT u.`email`, s.`user` 
            FROM `fcms_user_settings` AS s, `fcms_users` AS u 
            WHERE `email_updates` = '1'
            AND u.`id` = s.`user`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) > 0)
    {
        while ($r = mysql_fetch_array($result))
        {
            $recipeUser    = getUserDisplayName($fcmsUser->id);
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

    $name        = escape_string($name);
    $ingredients = escape_string($ingredients);
    $directions  = escape_string($directions);

    $sql = "UPDATE `fcms_recipes` 
            SET `name`          = '$name', 
                `category`      = '$category', 
                `ingredients`   = '$ingredients',
                `directions`    = '$directions' 
            WHERE `id` = '$id'";

    if(!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
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
    global $fcmsUser, $rec;

    displayHeader();

    $name = strip_tags($_POST['name']);
    $name = escape_string($name);

    $sql  = "INSERT INTO `fcms_category` (`name`, `type`, `user`)
            VALUES (
                '$name',
                'recipe', 
                '$fcmsUser->id'
            )";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $cat = mysql_insert_id();

    $rec->displayAddRecipeForm($cat);
    displayFooter();
}

/**
 * displayDeleteRecipeConfirmationForm 
 * 
 * @return void
 */
function displayDeleteRecipeConfirmationForm ()
{
    displayHeader();

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

    displayFooter();
}

/**
 * displayDeleteRecipeConfirmationSubmit 
 * 
 * @return void
 */
function displayDeleteRecipeConfirmationSubmit ()
{
    global $fcmsUser;

    $id = (int)$_POST['id'];

    // Get recipe info
    $sql = "SELECT `user`, `category`
            FROM `fcms_recipes`
            WHERE `id` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $row = mysql_fetch_assoc($result);

    $category = $row['category'];

    // Only creator and admin can delete
    if ($row['user'] != $fcmsUser->id && checkAccess($fcmsUser->id) <= 1)
    {
        displayHeader();

        echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

        displayFooter();
        return;
    }

    // Delete
    $sql = "DELETE FROM `fcms_recipes` 
            WHERE `id` = '$id'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
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
    global $rec, $fcmsUser;

    displayHeader();

    $cat  = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

    if (checkAccess($fcmsUser->id) > 5)
    {
        echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

        displayFooter();
        return;
    }

    $rec->displayAddRecipeForm($cat);
    displayFooter();
}

/**
 * displayLatestRecipes 
 * 
 * @return void
 */
function displayLatestRecipes ()
{
    global $rec;

    displayHeader();

    $page = getPage();
    $rec->showRecipes($page);
    displayFooter();
}

/**
 * displayEditRecipeForm 
 * 
 * @return void
 */
function displayEditRecipeForm ()
{
    global $rec;

    displayHeader();

    $id          = (int)$_POST['id'];
    $name        = $_POST['name'];
    $thumbnail   = $_POST['thumbnail'];
    $category    = $_POST['category'];
    $ingredients = $_POST['ingredients'];
    $directions  = $_POST['directions'];

    $rec->displayEditRecipeForm($id, $name, $thumbnail, $category, $ingredients, $directions);
    displayFooter();
}

/**
 * displayEditThumbnailForm 
 * 
 * @return void
 */
function displayEditThumbnailForm ()
{
    global $rec;

    displayHeader();

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

    displayFooter();
}

/**
 * displayEditThumbnailSubmit 
 * 
 * @return void
 */
function displayEditThumbnailSubmit ()
{
    global $img;

    $id        = (int)$_POST['id'];
    $category  = (int)$_POST['category'];
    $thumbnail = 'no_recipe.jpg';

    $uploadsPath = getUploadsAbsolutePath();

    // Upload Recipe Image
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['name'] && $_FILES['thumbnail']['error'] < 1)
    {
        $img->destination = $uploadsPath.'upimages/';
        $img->uniqueName  = true;

        $thumbnail = $img->upload($_FILES['thumbnail']);

        if ($img->error == 1)
        {
            displayHeader();

            echo '
    <p class="error-alert">
        '.sprintf(T_('Thumbnail [%s] is not a supported type. Thumbnails must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $this->img->name).'
    </p>';
            displayFooter();
            return;
        }

        $img->resize(100, 100);

        if ($img->error > 0)
        {
            displayHeader();

            echo '
    <p class="error-alert">
        '.T_('There was an error uploading your thumbnail.').'
    </p>';
            displayFooter();
            return;
        }
    }

    $sql = "UPDATE `fcms_recipes` 
            SET `thumbnail` = '$thumbnail'
            WHERE `id` = '$id'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
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
    global $rec, $fcmsUser;

    displayHeader();

    if (checkAccess($fcmsUser->id) > 5)
    {
        echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

        displayFooter();
        return;
    }

    $rec->displayAddCategoryForm();
    displayFooter();
}

/**
 * displayAddCommentSubmit 
 * 
 * @return void
 */
function displayAddCommentSubmit ()
{
    global $fcmsUser;

    $categoryId = (int)$_GET['category'];
    $recipeId   = (int)$_POST['recipe'];
    $comment    = strip_tags($_POST['comment']);
    $comment    = escape_string($comment);

    $sql = "INSERT INTO `fcms_recipe_comment` (`recipe`, `comment`, `user`, `date`)
            VALUES (
                '$recipeId',
                '$comment',
                '$fcmsUser->id',
                NOW()
            )";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $comId = mysql_insert_id();

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

    if ($fcmsUser->id != $_POST['user'] && checkAccess($fcmsUser->id) > 2)
    {
        displayHeader();

        echo '
        <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

        displayFooter();
        return;
    }

    $sql = "DELETE FROM `fcms_recipe_comment`
            WHERE `id` = '$recipeId'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
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
    global $fcmsUser, $rec;

    displayHeader();

    if (checkAccess($fcmsUser->id) > 2)
    {
        echo '
        <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

        displayFooter();
        return;
    }

    if (isset($_SESSION['ok']))
    {
        unset($_SESSION['ok']);

        displayOkMessage();
    }

    $rec->displayEditCategoryForm();
    displayFooter();
}

/**
 * displayEditCategorySubmit 
 * 
 * @return void
 */
function displayEditCategorySubmit ()
{
    global $fcmsUser;

    if (checkAccess($fcmsUser->id) > 2)
    {
        displayHeader();

        echo '
        <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

        displayFooter();
        return;
    }

    $ids = $_POST['id'];

    foreach ($_POST['category'] as $key => $category)
    {
        $id       = (int)$ids[$key];
        $category = strip_tags($category);
        $category = escape_string($category);

        $sql = "UPDATE `fcms_category` 
                SET `name` = '$category' 
                WHERE `id` = '$id'";
        if (!mysql_query($sql))
        {
            dislayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
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
    global $fcmsUser;

    if (checkAccess($fcmsUser->id) > 2)
    {
        displayHeader();

        echo '
        <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

        displayFooter();
        return;
    }

    foreach ($_POST['delete'] as $id)
    {
        // Delete recipes
        $sql = "DELETE FROM `fcms_recipes` 
                WHERE `category` = '".(int)$id."'";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        // Delete category
        $sql = "DELETE FROM `fcms_category` 
                WHERE `id` = '".(int)$id."'";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
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
    global $rec;

    $page     = getPage();
    $category = (int)$_GET['category'];

    displayHeader();
    $rec->showRecipeInCategory($category, $page);
    displayFooter();
}

/**
 * displayRecipe 
 * 
 * @return void
 */
function displayRecipe ()
{
    global $rec;

    $id       = (int)$_GET['id'];
    $category = (int)$_GET['category'];

    displayHeader();
    $rec->showRecipe($category, $id);
    displayFooter();
}
