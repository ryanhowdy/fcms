<?php
include_once('database_class.php');
include_once('utils.php');
include_once('datetime.php');

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
    var $db;
    var $db2;
    var $tzOffset;
    var $currentUserId;

    /**
     * Recipes 
     * 
     * @param   int     $currentUserId 
     *
     * @return  void
     */
    function Recipes ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db  = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $this->db2 = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->tzOffset      = getTimezone($this->currentUserId);
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
        $page = cleanInput($page, 'int');
        $from = (($page * 5) - 5);

        if (checkAccess($this->currentUserId) <= 5) {
            echo '
            <div id="actions_menu" class="clearfix">
                <ul><li><a class="add" href="?addrecipe=yes">'.T_('Add Recipe').'</a></li></ul>
            </div>';
        }

        // Show Category Side Menu
        $hasCategories = $this->showCategoryMenu();

        if (!$hasCategories) {
            echo '
            <div class="info-alert">
                <h2>'.T_('Welcome to the Recipes Section').'</h2>
                <p><i>'.T_('Currently no one has added any recipes').'</i></p>
                <ol>
                    <li><a href="?add=category">'.T_('Create a Category').'</a></li>
                    <li><a href="?addrecipe=yes">'.T_('Add a Recipe').'</a></li>
                </ol>
            </div>';
            return;
        }

        echo '
            <div id="maincolumn">';

        // Display last 5 added recipes
        $sql = "SELECT `id`, `name`, `category`
                FROM `fcms_recipes` 
                ORDER BY `date` DESC 
                LIMIT $from, 5";
        $this->db->query($sql) or displaySQLError(
            'Get Last Recipe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {

            echo '
                <h2>'.T_('Latest Recipes').'</h2>
                <ul id="recipe-list">';

            while ($r = $this->db->get_row()) {
                echo '
                    <li>
                        <a href="?category=' . (int)$r['category'] . '&amp;id=' . (int)$r['id'] . '">
                            <span>' . T_('Click to view recipe') . '</span>
                            <b>' . $r['name'] . '</b>
                        </a>
                    </li>';
            }
        }

        // Close maincolumn and recipe-list
        echo '
                </ul>
            </div>';

        // Display Pagination
        $sql = "SELECT count(`id`) AS c FROM `fcms_recipes`";
        $this->db->query($sql) or displaySQLError(
            'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = $this->db->get_row();
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
        $cat  = cleanInput($cat, 'int');
        $page = cleanInput($page, 'int');
        $from = (($page * 5) - 5);

        // Display Menu
        echo '
            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a href="recipes.php">'.T_('Recipe Categories').'</a></li>';
        if (checkAccess($this->currentUserId) <= 5) {
            echo '
                </ul>
            </div>
            <div id="actions_menu" class="clearfix">
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
        $sql = "SELECT r.`id`, r.`name`, r.`category`, 
                    c.`name` AS category_name, r.`user`, r.`date`
                FROM `fcms_recipes` AS r, `fcms_category` AS c
                WHERE `category` = '$cat'
                AND r.`category` = c.`id` 
                ORDER BY `date` DESC 
                LIMIT $from, 5";
        $this->db->query($sql) or displaySQLError(
            'Recipes Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        $categoryName = '';

        // Display Recipes
        if ($this->db->count_rows() > 0) {

            $displayed_category = false;
            while($r = $this->db->get_row()) {

                // Category
                if (!$displayed_category) {
                    $displayed_category = true;
                    $categoryName = $r['category_name'];
                    echo '
            <h2>' . $r['category_name'] . '</h2>
            <ul id="recipe-list">';
                }

                echo '
                <li>
                    <a href="?category=' . $cat . '&amp;id=' . (int)$r['id'] . '">
                        <span>' . T_('Click to view recipe') . '</span>
                        <b>' . cleanOutput($r['name']) . '</b>
                    </a>
                </li>';
            }

            echo '
            </ul>';

            // Display Pagination
            $sql = "SELECT count(`id`) AS c 
                    FROM `fcms_recipes` 
                    WHERE `category` = '$cat'";
            $this->db2->query($sql) or displaySQLError(
                'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $r = $this->db2->get_row();
            $recipecount = $r['c'];
            $total_pages = ceil($recipecount / 5);
            displayPagination('recipes.php?category='.$cat, $page, $total_pages);

        // No recipes for this category
        } else {
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
        $cat = cleanInput($cat, 'int');
        $id  = cleanInput($id, 'int');

        // Display Menu
        echo '
            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a href="recipes.php">'.T_('Recipe Categories').'</a></li>';
        if (checkAccess($this->currentUserId) <= 5) {
            echo '
                </ul>
            </div>
            <div id="actions_menu" class="clearfix">
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
        $this->db->query($sql) or displaySQLError(
            'Recipe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        // Invalid id/category
        if ($this->db->count_rows() < 1) {
            echo '
            <div class="error-alert">' . T_('Recipe does not exist.') . '</div>';
            return;
        }

        $r = $this->db->get_row();

        $displayname = getUserDisplayName($r['user']);
        $displayname = '<a href="profile.php?member='.$r['user'].'">'.$displayname.'</a>';
        $date = fixDate(T_('F j, Y, g:i a'), $this->tzOffset, $r['date']);

        $cleanName          = cleanOutput($r['name']);
        $cleanCategory      = cleanOutput($r['category'], 'int');
        $cleanThumb         = basename($r['thumbnail']);
        $cleanIngredients   = cleanOutput($r['ingredients']);
        $cleanDirections    = cleanOutput($r['directions']);

        // Display Recipe
        echo '
            <div id="maincolumn">
                <div class="recipe-thumbnail"><img src="uploads/upimages/'.$cleanThumb.'"/></div>
                <h4 class="recipe-name">' . $cleanName . '</h4>
                <span class="date">
                    '.sprintf(T_('Submitted by %s on %s.'), $displayname, $date);
        if ($this->currentUserId == $r['user'] || checkAccess($this->currentUserId) < 2) {
            echo ' &nbsp;
                    <form method="post" action="recipes.php">
                        <div>
                            <input type="hidden" name="id" value="'.(int)$r['id'].'"/>
                            <input type="hidden" name="name" value="'.$cleanName.'"/>
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
                <div class="clearfix">
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

        echo '
            <script type="text/javascript" src="inc/js/livevalidation.js"></script>
            <form method="post" id="addform" name="addform" enctype="multipart/form-data" action="recipes.php">
                <fieldset>
                    <legend><span>'.T_('Add Recipe').'</span></legend>
                    <div class="clearfix">
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
                    <div class="clearfix">
                        <label for="thumbnail">'.T_('Thumbnail').'</label>
                        <input type="file" name="thumbnail" id="thumbnail"/>
                    </div>
                    <div class="clearfix">
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
                    <div class="clearfix">
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
                    <div class="clearfix">
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
     * @param   int     $id 
     * @param   string  $name 
     * @param   string  $category 
     * @param   string  $ingredients 
     * @param   string  $directions
     * @return  void
     */
    function displayEditRecipeForm ($id, $name, $category, $ingredients, $directions)
    {
        $categories = $this->getCategoryList();

        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <form method="post" id="editform" name="editform" action="recipes.php">
                <fieldset>
                    <legend><span>'.T_('Edit Recipe').'</span></legend>
                    <div>
                        <label for="name">'.T_('Name').'</label>
                        <input type="text" name="name" id="name" value="'.$name.'" size="50"/>
                        <script type="text/javascript">
                            var fname = new LiveValidation(\'name\', { onlyOnSubmit: true });
                            fname.add(Validate.Presence, {failureMessage: ""});
                        </script>
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
                        <input type="hidden" name="id" value="'.$id.'"/>
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
        $this->db2->query($sql) or displaySQLError(
            'Categories Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = $this->db2->get_row()) {
            $categories[$r['id']] = $r['name'];
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
        $this->db->query($sql) or displaysqlerror(
            'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        if ($this->db->count_rows() <= 0) {
            return false;
        }

        $categories = array();
        $counts     = array();

        while ($r = $this->db->get_row()) {
            if ($r['type'] == 'cat') {
                $categories[$r['id']] = cleanOutput($r['name']);
            } else {
                $counts[$r['id']] = $r['name'];
            }
        }

        echo '
            <div id="leftcolumn">
                <h3>' . T_('Recipe Categories') . '</h3>
                <ul class="menu">';


        foreach ($categories as $id => $name) {
            echo '
                    <li><a href="?category=' . (int)$id . '">' . $name . '<span>(' . (int)$counts[$id] . ')</span></a></li>';
        }

        echo '
                </ul>';

        if (checkAccess($this->currentUserId) <= 2)
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
        $id       = cleanInput($id, 'int');
        $category = cleanInput($category, 'int');

        $sql = "SELECT rc.`id`, rc.`recipe`, rc.`comment`, rc.`date`, rc.`user`, u.`avatar` 
                FROM `fcms_recipe_comment` AS rc, `fcms_users` AS u 
                WHERE `recipe` = '$id' 
                AND rc.`user` = u.`id` 
                ORDER BY `date`";
        $this->db->query($sql) or displaySQLError(
            'Comments Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        // Display current comments
        if ($this->db->count_rows() >= 0) {
            while ($r = $this->db->get_row()) {

                $del_comment = '';
                $date = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $r['date']);
                $displayname = getUserDisplayName($r['user']);
                $comment = $r['comment'];
                if ($this->currentUserId == $r['user'] || checkAccess($this->currentUserId) < 2) {
                    $del_comment .= '<input type="submit" name="delcom" id="delcom" '
                        . 'value="'.T_('Delete').'" class="gal_delcombtn" title="'
                        . T_('Delete this Comment') . '"/>';
                }

                echo '
            <div class="comment_block clearfix">
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
            <div id="sections_menu" class="clearfix">
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
