<?php
/**
 * Recipes 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Recipes
{
    var $fcmsError;
    var $fcmsDatabase;
    var $fcmsUser;

    /**
     * Recipes
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase
     * @param object $fcmsUser 
     * 
     * @return void
     */
    function Recipes ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
    }

    /**
     * showRecipes 
     * 
     * Displays a list of the current categories with recipe counts, and 
     * the last added recipe.
     *
     * @param   int     $page 
     * @return  void
     */
    function showRecipes ($page = 1)
    {
        $page = (int)$page;
        $from = (($page * 5) - 5);

        if ($this->fcmsUser->access <= 5)
        {
            echo '
            <div id="actions_menu">
                <ul><li><a class="add" href="?addrecipe=yes">'.T_('Add Recipe').'</a></li></ul>
            </div>';
        }

        // Show Category Side Menu
        $hasCategories = $this->showCategoryMenu();

        if (!$hasCategories) {
            echo '
            <div class="blank-state">
                <h2>'.T_('Nothing to see here').'</h2>
                <h3>'.T_('Currently no one has added any recipes').'</h3>
                <h3><a href="?addrecipe=yes">'.T_('Why don\'t you share a recipe now?').'</a></h3>
            </div>';
            return;
        }

        echo '
            <div id="maincolumn">';

        // Display last 5 added recipes
        $sql = "SELECT `id`, `name`, `category`, `thumbnail`, `date`
                FROM `fcms_recipes` 
                ORDER BY `date` DESC 
                LIMIT $from, 5";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        if (count($rows) <= 0)
        {
            echo '
            <div class="info-alert">
                <p><i>'.T_('Currently no one has added any recipes.').'</i></p>
                <p><a href="?addrecipe=yes">'.T_('Add a Recipe').'</a></p>
            </div>';

            return;
        }

        $path = 'uploads/upimages/';

        if (defined('UPLOADS'))
        {
            $path = 'file.php?u=';
        }

        echo '
                <h2>'.T_('Latest Recipes').'</h2>
                <ul id="recipe-list">';

        foreach ($rows as $r)
        {
            $since = getHumanTimeSince(strtotime($r['date']));

            echo '
                    <li>
                        <a href="?category=' . (int)$r['category'] . '&amp;id=' . (int)$r['id'] . '">
                            <span>' . T_('Click to view recipe') . '</span>
                            <img src="'.URL_PREFIX.$path.basename($r['thumbnail']).'"/>
                            <b>'.cleanOutput($r['name']).'</b>
                            <i>'.$since.'</i>
                        </a>
                    </li>';
        }

        // Close maincolumn and recipe-list
        echo '
                </ul>
            </div>';

        // Display Pagination
        $sql = "SELECT count(`id`) AS c 
                FROM `fcms_recipes`";

        $r = $this->fcmsDatabase->getRow($sql);
        if ($r === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        $recipecount = $r['c'];
        $total_pages = ceil($recipecount / 5);

        displayPagination('recipes.php', $page, $total_pages);
    }

    /**
     * showRecipeInCategory 
     *
     * Displays up to 5 recipes for the given category and page.
     * 
     * @param   int     $cat 
     * @param   int     $page 
     * @return  void
     */
    function showRecipeInCategory ($cat, $page = 1)
    {
        $cat  = (int)$cat;
        $page = (int)$page;
        $from = (($page * 5) - 5);

        // Display Menu
        echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="recipes.php">'.T_('Recipe Categories').'</a></li>';

        if ($this->fcmsUser->access <= 5)
        {
            echo '
                </ul>
            </div>
            <div id="actions_menu">
                <ul>
                    <li><a href="?addrecipe=yes&amp;cat='.$cat.'">'.T_('Add Recipe').'</a></li>';
        }

        echo '
                </ul>
            </div>';

        // Show Category Side Menu
        $this->showCategoryMenu();

        echo '
            <div id="maincolumn">';


        // Get Recipes for this category
        $sql = "SELECT r.`id`, r.`name`, r.`category`, r.`thumbnail`, c.`name` AS category_name, r.`user`, r.`date`
                FROM `fcms_recipes` AS r, `fcms_category` AS c
                WHERE `category` = ?
                AND r.`category` = c.`id` 
                ORDER BY `date` DESC 
                LIMIT $from, 5";

        $rows = $this->fcmsDatabase->getRows($sql, $cat);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        $categoryName = '';

        // Display Recipes
        if (count($rows) > 0)
        {
            $displayed_category = false;

            $path = 'uploads/upimages/';

            if (defined('UPLOADS'))
            {
                $path = 'file.php?u=';
            }

            foreach ($rows as $r)
            {
                // Category
                if (!$displayed_category)
                {
                    $displayed_category = true;
                    $categoryName       = cleanOutput($r['category_name']);

                    echo '
            <h2>'.$categoryName.'</h2>
            <ul id="recipe-list">';
                }

                $since = getHumanTimeSince(strtotime($r['date']));

                echo '
                <li>
                    <a href="?category='.$cat.'&amp;id='.(int)$r['id'].'">
                        <span>'.T_('Click to view recipe').'</span>
                        <img src="'.URL_PREFIX.$path.basename($r['thumbnail']).'"/>
                        <b>'.cleanOutput($r['name']).'</b>
                        <i>'.$since.'</i>
                    </a>
                </li>';
            }

            echo '
            </ul>';

            // Display Pagination
            $sql = "SELECT count(`id`) AS c 
                    FROM `fcms_recipes` 
                    WHERE `category` = ?";

            $r = $this->fcmsDatabase->getRow($sql, $cat);
            if ($r === false)
            {
                $this->fcmsError->displayError();

                return;
            }

            $recipecount = $r['c'];
            $total_pages = ceil($recipecount / 5);

            displayPagination('recipes.php?category='.$cat, $page, $total_pages);

        // No recipes for this category
        }
        else
        {
            echo '
            <div class="info-alert">
                <h2>'.$categoryName.'</h2>
                <p><i>'.T_('Currently no one has added any recipes to this category.').'</i></p>
                <p><a href="?addrecipe=yes&amp;cat='.$cat.'">'.T_('Add a Recipe').'</a></p>
            </div>';
        }

        echo '
            </div>';
    }

    /**
     * showRecipe
     *
     * Display a single recipe.  Display options for editing/deleting. 
     * 
     * @param   int     $cat 
     * @param   int     $id 
     * @return  void
     */
    function showRecipe ($cat, $id)
    {
        $cat = (int)$cat;
        $id  = (int)$id;

        // Display Menu
        echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="recipes.php">'.T_('Recipe Categories').'</a></li>';
        if ($this->fcmsUser->access <= 5) {
            echo '
                </ul>
            </div>
            <div id="actions_menu">
                <ul>
                    <li><a href="?addrecipe=yes&amp;cat='.$cat.'">'.T_('Add Recipe').'</a></li>';
        }
        echo '
                </ul>
            </div>';

        // Show Category Side Menu
        $this->showCategoryMenu();

        // Get Recipes for this category
        $sql = "SELECT r.`id`, r.`name`, r.`category`, 
                    r.`ingredients`, r.`directions`, r.`thumbnail`, 
                    c.`name` AS category_name, r.`user`, r.`date`
                FROM `fcms_recipes` AS r, `fcms_category` AS c
                WHERE r.`id` = '$id' 
                AND r.`category` = '$cat'
                AND r.`category` = c.`id`
                LIMIT 1";

        $r = $this->fcmsDatabase->getRow($sql, array($id, $cat));
        if ($r === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        // Invalid id/category
        if (empty($r)) {
            echo '
            <div class="error-alert">' . T_('Recipe does not exist.') . '</div>';

            return;
        }

        $displayname = getUserDisplayName($r['user']);
        $displayname = '<a href="profile.php?member='.$r['user'].'">'.$displayname.'</a>';
        $date        = fixDate(T_('F j, Y, g:i a'), $this->fcmsUser->tzOffset, $r['date']);

        $cleanName        = cleanOutput($r['name']);
        $cleanCategory    = (int)$r['category'];
        $cleanThumb       = basename($r['thumbnail']);
        $cleanIngredients = cleanOutput($r['ingredients']);
        $cleanDirections  = cleanOutput($r['directions']);

        $path = 'uploads/upimages/';

        if (defined('UPLOADS'))
        {
            $path = 'file.php?u=';
        }

        // Display Recipe
        echo '
            <div id="maincolumn">
                <div class="recipe-thumbnail"><img src="'.$path.$cleanThumb.'"/></div>
                <h4 class="recipe-name">'.$cleanName.'</h4>
                <span class="date">
                    '.sprintf(T_('Submitted by %s on %s.'), $displayname, $date);

        if ($this->fcmsUser->id == $r['user'] || $this->fcmsUser->access < 2)
        {
            echo ' &nbsp;
                    <form method="post" action="recipes.php">
                        <div>
                            <input type="hidden" name="id" value="'.(int)$r['id'].'"/>
                            <input type="hidden" name="name" value="'.$cleanName.'"/>
                            <input type="hidden" name="thumbnail" value="'.$cleanThumb.'"/>
                            <input type="hidden" name="category" value="'.$cleanCategory.'"/>
                            <input type="hidden" name="ingredients" value="'.$cleanIngredients.'"/>
                            <input type="hidden" name="directions" value="'.$cleanDirections.'"/>
                            <input type="submit" name="editrecipe" value="'.T_('Edit').'" class="editbtn" title="'.T_('Edit this Recipe').'"/>
                        </div>
                    </form> &nbsp;
                    <form class="delrec" method="post" action="recipes.php">
                        <div>
                            <input type="hidden" name="id" value="'.(int)$r['id'].'"/>
                            <input type="submit" name="delrecipe" value="'.T_('Delete').'" class="delbtn" title="'.T_('Delete this Recipe').'"/>
                        </div>
                    </form>';
        }
        echo '
                </span>
                <div class="recipe-container">
                    <div class="recipe-directions">
                        <b>'.T_('Directions').'</b>
                        '.nl2br_nospaces($cleanDirections).'
                    </div>
                    <div class="recipe-ingredients">
                        <b>'.T_('Ingredients').'</b>
                        '.nl2br_nospaces($cleanIngredients).'
                    </div>
                </div>';

        $this->showComments($id, $cat);

        echo '
            </div>';
    }

    /**
     * displayAddRecipeForm 
     * 
     * Displays the form for adding a recipe.
     *
     * @param   int     $category 
     * @return  void
     */
    function displayAddRecipeForm ($category = 0)
    {
        $categories = $this->getCategoryList();

        if (count($categories) <= 0)
        {
            echo '
            <p class="info-alert">'.T_('You need to create a category before you add a recipe.').'</p>';

            $this->displayAddCategoryForm();
            return;
        }

        echo '
            <script type="text/javascript" src="ui/js/livevalidation.js"></script>
            <form method="post" id="addform" name="addform" enctype="multipart/form-data" action="recipes.php">
                <fieldset>
                    <legend><span>'.T_('Add Recipe').'</span></legend>
                    <div>
                        <label for="name">'.T_('Name').'</label>
                        <input type="text" name="name" id="name"/>
                        <div id="name-info" class="info">
                            '.T_('The name of your recipe.').'
                        </div>
                        <script type="text/javascript">
                            var fname = new LiveValidation(\'name\', { onlyOnSubmit: true });
                            fname.add(Validate.Presence, {failureMessage: ""});
                        </script>
                    </div>
                    <div>
                        <label for="thumbnail">'.T_('Thumbnail').'</label>
                        <input type="file" name="thumbnail" id="thumbnail"/>
                    </div>
                    <div>
                        <label for="category">'.T_('Category').'</label>
                        <select name="category" id="category">
                            <option></option>
                            ' . buildHtmlSelectOptions($categories, $category) . '
                        </select>&nbsp;
                        <a href="?add=category">' . T_('New Category') . '</a>
                        <script type="text/javascript">
                            var fcategory = new LiveValidation(\'category\', { onlyOnSubmit: true });
                            fcategory.add(Validate.Presence, {failureMessage: ""});
                        </script>
                    </div>
                    <div>
                        <label for="ingredients">'.T_('Ingredients').'</label>
                        <textarea name="ingredients" id="ingredients"></textarea>
                        <div id="ingredients-info" class="info">
                            '.T_('Put each ingredient on a seperate line.').'
                        </div>
                        <script type="text/javascript">
                            var fingredients = new LiveValidation(\'ingredients\', { onlyOnSubmit: true });
                            fingredients.add(Validate.Presence, {failureMessage: ""});
                        </script>
                    </div>
                    <div>
                        <label for="directions">'.T_('Directions').'</label>
                        <textarea name="directions" id="directions"></textarea>
                        <script type="text/javascript">
                            var fdirections = new LiveValidation(\'directions\', { onlyOnSubmit: true });
                            fdirections.add(Validate.Presence, {failureMessage: ""});
                        </script>
                    </div>
                    <p>
                        <input class="sub1" type="submit" name="submitadd" value="'.T_('Add').'"/> &nbsp;
                        <a href="recipes.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * displayEditRecipeForm 
     * 
     * Displays the form for editing a recipe.
     *
     * @param int    $id 
     * @param string $name 
     * @param string $thumbnail
     * @param string $category 
     * @param string $ingredients 
     * @param string $directions
     *
     * @return  void
     */
    function displayEditRecipeForm ($id, $name, $thumbnail, $category, $ingredients, $directions)
    {
        $categories = $this->getCategoryList();

        $path = 'uploads/upimages/';

        if (defined('UPLOADS'))
        {
            $path = 'file.php?u=';
        }

        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <form method="post" id="editform" name="editform" action="recipes.php">
                <fieldset>
                    <legend><span>'.T_('Edit Recipe').'</span></legend>
                    <div>
                        <label for="name">'.T_('Name').'</label>
                        <input type="text" name="name" id="name" value="'.cleanOutput($name).'" size="50"/>
                        <script type="text/javascript">
                            var fname = new LiveValidation(\'name\', { onlyOnSubmit: true });
                            fname.add(Validate.Presence, {failureMessage: ""});
                        </script>
                    </div>
                    <div>
                        <label for="thumbnail">'.T_('Thumbnail').'</label>
                        <img src="'.$path.$thumbnail.'"/>
                        <a href="recipes.php?category='.$category.'&amp;thumbnail='.$id.'">'.T_('Change').'</a>
                    </div>
                    <div>
                        <label for="category">'.T_('Category').'</label>
                        <select name="category">
                            ' . buildHtmlSelectOptions($categories, $category) . '
                        </select>
                    </div>
                    <div>
                        <label for="ingredients">'.T_('Ingredients').'</label>
                        <textarea name="ingredients" id="ingredients">'.$ingredients.'</textarea>
                        <script type="text/javascript">
                            var fingredients = new LiveValidation(\'ingredients\', { onlyOnSubmit: true });
                            fingredients.add(Validate.Presence, {failureMessage: ""});
                        </script>
                    </div>
                    <div>
                        <label for="directions">'.T_('Directions').'</label>
                        <textarea name="directions" id="directions">'.$directions.'</textarea>
                        <script type="text/javascript">
                            var fdirections = new LiveValidation(\'directions\', { onlyOnSubmit: true });
                            fdirections.add(Validate.Presence, {failureMessage: ""});
                        </script>
                    </div>
                    <p>
                        <input type="hidden" name="id" value="'.(int)$id.'"/>
                        <input class="sub1" type="submit" name="submitedit" value="' . T_('Edit') . '"/> &nbsp;
                        <a href="recipes.php">' . T_('Cancel') . '</a>
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * displayAddCategoryForm 
     * 
     * @return void
     */
    function displayAddCategoryForm ()
    {
        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <form method="post" name="editform" action="recipes.php">
                <fieldset>
                    <legend><span>'.T_('New Category').'</span></legend>
                    <div>
                        <label for="name">'.T_('Name').'</label>: 
                        <input type="text" name="name" id="name" size="50"/>
                    </div><br/>
                    <script type="text/javascript">
                        var fname = new LiveValidation(\'name\', { onlyOnSubmit: true });
                        fname.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <p>
                        <input class="sub1" type="submit" name="submit-category" value="' . T_('Create') . '"/> &nbsp;
                        <a href="recipes.php?addrecipe=yes">' . T_('Cancel') . '</a>
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * getCategoryList
     *
     * Returns an array of the current Recipe Categories. 
     * 
     * @return array
     */
    function getCategoryList ()
    {
        $categories = array();

        // Get Recipes for this category
        $sql = "SELECT `id`, `name` 
                FROM `fcms_category` 
                WHERE `type` = 'recipe' 
                ORDER BY `name`"; 

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return $categories;
        }

        foreach ($rows as $r)
        {
            $categories[$r['id']] = cleanOutput($r['name']);
        }

        return $categories;
    }

    /**
     * showCategoryMenu 
     * 
     * Displays the left side category menu. Returns true if categories exist.
     *
     * @return  boolean
     */
    function showCategoryMenu ()
    {
        $sql = "SELECT 'cat' AS type, `id`, `name`
                FROM `fcms_category`
                WHERE `type` = 'recipe'
                UNION
                SELECT 'count' AS 'type', c.`id`, count(r.`id`)
                FROM `fcms_category` AS c 
                LEFT JOIN `fcms_recipes` AS r ON c.`id` = r.`category`
                WHERE c.`type` = 'recipe'
                GROUP by c.`id`
                ORDER BY `name`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return false;
        }

        if (count($rows) <= 0)
        {
            return false;
        }

        $categories = array();
        $counts     = array();

        foreach ($rows as $r)
        {
            if ($r['type'] == 'cat')
            {
                $categories[$r['id']] = cleanOutput($r['name']);
            }
            else
            {
                $counts[$r['id']] = cleanOutput($r['name']);
            }
        }

        echo '
            <div id="leftcolumn">
                <h3>' . T_('Recipe Categories') . '</h3>
                <ul class="menu">';


        foreach ($categories as $id => $name)
        {
            echo '
                    <li><a href="?category=' . (int)$id . '">' . $name . '<span>(' . (int)$counts[$id] . ')</span></a></li>';
        }

        echo '
                </ul>';

        if ($this->fcmsUser->access <= 2)
        {
            echo '<br/>
                <h3>' . T_('Admin Options') . '</h3>
                <ul class="menu">
                    <li><a href="?categoryedit=1">'.T_('Edit Categories').'</a></li>
                </ul>';
        }

        echo '
            </div>';

        return true;
    }

    /**
     * showComments 
     * 
     * Show the comments for the given recipe.
     * 
     * @param   int     $id 
     * @param   int     $category 
     * @return  void
     */
    function showComments ($id, $category)
    {
        $id       = (int)$id;
        $category = (int)$category;

        $sql = "SELECT rc.`id`, rc.`recipe`, rc.`comment`, rc.`date`, rc.`user`, u.`avatar` 
                FROM `fcms_recipe_comment` AS rc, `fcms_users` AS u 
                WHERE `recipe` = '$id' 
                AND rc.`user` = u.`id` 
                ORDER BY `date`";

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        // Display current comments
        if (count($rows) >= 0)
        {
            foreach ($rows as $r)
            {
                $del_comment = '';
                $date = fixDate(T_('F j, Y g:i a'), $this->fcmsUser->tzOffset, $r['date']);
                $displayname = getUserDisplayName($r['user']);
                $comment = $r['comment'];
                if ($this->fcmsUser->id == $r['user'] || $this->fcmsUser->access < 2) {
                    $del_comment .= '<input type="submit" name="delcom" id="delcom" '
                        . 'value="'.T_('Delete').'" class="gal_delcombtn" title="'
                        . T_('Delete this Comment') . '"/>';
                }

                echo '
            <div id="comment'.$id.'" class="comment_block">
                <form class="delcom" action="?category='.$category.'&amp;id='.$id.'" method="post">
                    '.$del_comment.'
                    <img class="avatar" alt="avatar" src="'.getCurrentAvatar($r['user']).'"/>
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>
                        '.parse($comment).'
                    </p>
                    <input type="hidden" name="id" value="'.$r['id'].'">
                    <input type="hidden" name="user" value="'.$r['user'].'">
                </form>
            </div>';
            }
        }

        // Display add comment form
        echo '
            <p>&nbsp;</p>
            <div class="add_comment_block">
                <form action="?category='.$category.'&amp;id='.$id.'" method="post">
                    '.T_('Add Comment').'<br/>
                    <textarea class="frm_textarea" name="comment" id="comment" rows="3" cols="63"></textarea>
                    <input type="hidden" name="recipe" value="'.$id.'">
                    <input type="submit" name="addcom" id="addcom" value="'.T_('Add Comment').'" title="'.T_('Add Comment').'" class="gal_addcombtn"/>
                </form>
            </div>
            <p>&nbsp;</p>';
    }

    /**
     * displayEditCategoryForm 
     * 
     * @return void
     */
    function displayEditCategoryForm ()
    {
        $categories = $this->getCategoryList();

        echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="recipes.php">'.T_('Recipe Categories').'</a></li>
                </ul>
            </div>
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <form method="post" id="editcategories" name="editcategories" action="recipes.php">
                <table class="sortable">
                    <thead>
                        <tr>
                            <th>'.T_('ID').'</th>
                            <th>'.T_('Category').'</th>
                            <th>'.T_('Delete').'</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($categories as $id => $category)
        {
            echo '
                        <tr>
                            <td>'.$id.'</td>
                            <td>
                                <input type="text" name="category[]" id="category_'.$id.'" value="'.$category.'" size="50"/>
                                <input type="hidden" name="id[]" id="id_'.$id.'" value="'.$id.'"/>
                                <script type="text/javascript">
                                    var fcat'.$id.' = new LiveValidation(\'category_'.$id.'\', { onlyOnSubmit: true });
                                    fcat'.$id.'.add(Validate.Presence, {failureMessage: ""});
                                </script>
                            </td>
                            <td>
                                <input type="checkbox" name="delete[]" id="delete[]" value="'.$id.'"/>
                            </td>
                        </tr>';
        }

        echo '
                    </tbody>
                </table>
                <p>
                    <input class="sub1" type="submit" id="submit_cat_edit" name="submit_cat_edit" value="' . T_('Save Changes') . '"/> &nbsp;
                    <a href="recipes.php">' . T_('Cancel') . '</a>
                </p>
            </form>';
    }

}
