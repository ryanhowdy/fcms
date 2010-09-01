<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

class Recipes
{

    var $db;
    var $db2;
    var $tz_offset;
    var $current_user_id;

    function Recipes ($current_user_id, $type, $host, $database, $user, $pass)
    {
        $this->current_user_id = $current_user_id;
        $this->db = new database($type, $host, $database, $user, $pass);
        $this->db2 = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id";
        $this->db->query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
    }

    function showRecipes ()
    {
        $locale = new Locale();
        if (checkAccess($this->current_user_id) <= 5) {
            echo '
            <div id="actions_menu" class="clearfix">
                <ul><li><a class="add" href="?addrecipe=yes">'.T_('Add Recipe').'</a></li></ul>
            </div>';
        }

        // Get Counts
        $appetizer = $this->getRecipeCountInCategory(T_('Appetizer'));
        $breakfast = $this->getRecipeCountInCategory(T_('Breakfast'));
        $dessert = $this->getRecipeCountInCategory(T_('Dessert'));
        $meat = $this->getRecipeCountInCategory(T_('Entree (Meat)'));
        $seafood = $this->getRecipeCountInCategory(T_('Entree (Seafood)'));
        $vegetable = $this->getRecipeCountInCategory(T_('Entree (Vegetarian)'));
        $salad = $this->getRecipeCountInCategory(T_('Salad'));
        $side = $this->getRecipeCountInCategory(T_('Side Dish'));
        $soup = $this->getRecipeCountInCategory(T_('Soup'));
        
        echo '
            <h2>'.T_('Recipe Categories').'</h2>
            <div class="cat_row clearfix">
                <div class="cat">
                    <ul><li><a href="?category=1">'.T_('Appetizer').'<span>'.$appetizer.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=2">'.T_('Breakfast').'<span>'.$breakfast.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=3">'.T_('Dessert').'<span>'.$dessert.'</span></a></li></ul>
                </div>
            </div>
            <div class="cat_row clearfix">
                <div class="cat">
                    <ul><li><a href="?category=4">'.T_('Entree (Meat)').'<span>'.$meat.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=5">'.T_('Entree (Seafood)').'<span>'.$seafood.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=6">'.T_('Entree (Vegetarian)').'<span>'.$vegetable.'</span></a></li></ul>
                </div>
            </div>
            <div class="cat_row clearfix">
                <div class="cat">
                    <ul><li><a href="?category=7">'.T_('Salad').'<span>'.$salad.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=8">'.T_('Side Dish').'<span>'.$side.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=9">'.T_('Soup').'<span>'.$soup.'</span></a></li></ul>
                </div>
            </div>';
        $sql = "SELECT * FROM `fcms_recipes` ORDER BY `date` DESC LIMIT 1";
        $this->db->query($sql) or displaySQLError(
            'Get Last Recipe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        if ($this->db->count_rows() > 0) {
            $r = $this->db->get_row();
            $displayname = getUserDisplayName($r['user']);
            $displayname = '<a href="profile.php?member='.$r['user'].'">'.$displayname.'</a>';
            $date = $locale->fixDate(T_('F j, Y, g:i a'), $this->tz_offset, $r['date']);
            echo '
            <p>&nbsp;</p>
            <h2>'.T_('Latest Recipe').'</h2>
            <h4>'.$r['name'].'</h4>
            <span class="date">
                '.sprintf(T_('Submitted by %s on %s.'), $displayname, $date).'
            </span>
            <p>
                '.parse($r['recipe']).'
            </p>
            <p>&nbsp;</p>';
        }
    }

    function showRecipeInCategory ($cat, $page = '1', $id = '0')
    {
        $locale = new Locale();
        $from = (($page * 5) - 5);
        echo '
            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a href="recipes.php">'.T_('Recipe Categories').'</a></li>';
        if (checkAccess($this->current_user_id) <= 5) {
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
        if ($id > 0) {
            echo '
                <h2><a href="recipes.php?category='.$cat.'">';
        } else {
            echo '
                <h2>';
        }
        switch ($cat) {
            case 1:
                echo T_('Appetizer');
                $cat_name = T_('Appetizer');
                break;
            case 2:
                echo T_('Breakfast');
                $cat_name = T_('Breakfast');
                break;
            case 3:
                echo T_('Dessert');
                $cat_name = T_('Dessert');
                break;
            case 4:
                echo T_('Entree (Meat)');
                $cat_name = T_('Entree (Meat)');
                break;
            case 5:
                echo T_('Entree (Seafood)');
                $cat_name = T_('Entree (Seafood)');
                break;
            case 6:
                echo T_('Entree (Vegetarian)');
                $cat_name = T_('Entree (Vegetarian)');
                break;
            case 7:
                echo T_('Salad');
                $cat_name = T_('Salad');
                break;
            case 8:
                echo T_('Side Dish');
                $cat_name = T_('Side Dish');
                break;
            case 9:
                echo T_('Soup');
                $cat_name = T_('Soup');
                break;
            default:
                echo "<p class=\"error-alert\">" . T_('The Category you are trying to view doesn\'t exist.') . "</p>";
                $cat_name = '';
                break;
        }
        if ($id > 0) {
            echo "</a></h2><br/>";
        } else {
            echo "</h2><br/>";
        }
        $sql = "SELECT * FROM `fcms_recipes` WHERE `category` LIKE '$cat_name' ";
        if ($id > 0) {
            $sql .= "AND `id` = $id";
        } else {
            $sql .= "ORDER BY `date` DESC LIMIT " . $from . ", 5";
        }
        $this->db->query($sql) or displaySQLError(
            'Get Category Recipes Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        if ($this->db->count_rows() > 0) {
            while($r = $this->db->get_row()) {
                $displayname = getUserDisplayName($r['user']);
                $displayname = '<a href="profile.php?member='.$r['user'].'">'.$displayname.'</a>';
                $date = $locale->fixDate(T_('F j, Y, g:i a'), $this->tz_offset, $r['date']);
                if ($id > 0) {
                    $name = $r['name'];
                } else {
                    $name = '<a href="recipes.php?category='.$cat.'&amp;id='.$r['id'].'">'.$r['name'].'</a>';
                }
                echo '
            <h4>'.$name.'</h4>
            <span class="date">
                '.sprintf(T_('Submitted by %s on %s.'), $displayname, $date);
                if ($this->current_user_id == $r['user'] || checkAccess($this->current_user_id) < 2) {
                    echo ' &nbsp;
                <form method="post" action="recipes.php">
                    <div>
                        <input type="hidden" name="id" value="'.$r['id'].'"/>
                        <input type="hidden" name="name" value="'.htmlentities($r['name'], ENT_COMPAT, 'UTF-8').'"/>
                        <input type="hidden" name="category" value="'.htmlentities($r['category'], ENT_COMPAT, 'UTF-8').'"/>
                        <input type="hidden" name="post" value="'.htmlentities($r['recipe'], ENT_COMPAT, 'UTF-8').'"/>
                        <input type="submit" name="editrecipe" value="'.T_('Edit').'" class="editbtn" title="'.T_('Edit this Recipe').'"/>
                    </div>
                </form>';
                }
                if (checkAccess($this->current_user_id) < 2) {
                    echo ' &nbsp;
                <form class="delrec" method="post" action="recipes.php">
                    <div>
                        <input type="hidden" name="id" value="'.$r['id'].'"/>
                        <input type="submit" name="delrecipe" value="'.T_('Delete').'" class="delbtn" title="'.T_('Delete this Recipe').'"/>
                    </div>
                </form>';
                }
                echo '
            </span>
            <p>
                '.parse($r['recipe']).'
            </p>
            <p>&nbsp;</p>';
            }

            // display pagination
            if ($id <= 0) {
                $sql = "SELECT count(`id`) AS c "
                     . "FROM `fcms_recipes` "
                     . "WHERE `category` LIKE '$cat_name'";
                $this->db2->query($sql) or displaySQLError(
                    'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                while ($r = $this->db2->get_row()) {
                    $recipecount = $r['c'];
                }
                $total_pages = ceil($recipecount / 5);
                displayPagination('recipes.php?category='.$cat, $page, $total_pages);
            }
        } else {
            echo '
            <div class="info-alert">
                <h2>'.T_('Welcome to the Recipe section.').'</h2>
                <p><i>'.T_('Currently no one has added any recipes.').'</i></p>
                <p><a href="?addrecipe=yes&amp;cat='.$cat.'">'.T_('Add a Recipe').'</a></p>
            </div>';
        }
    }

    /**
     * getRecipeCountInCategory
     * 
     * Returns the recipe count for the desired category
     *
     * @param   $cat - category id
     */
    function getRecipeCountInCategory ($cat)
    {
        $sql = "SELECT count(*) FROM `fcms_recipes` WHERE `category` = '$cat'";
        $this->db->query($sql) or displaySQLError(
            'Recipe Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        if ($this->db->count_rows() > 0) {
            $r = $this->db->get_row();
            return sprintf(_ngettext('%s Recipe', '%s Recipes', $r[0]), $r[0]);
        } else {
            return "0 " . T_('Recipes');
        }
    }

    function displayForm ($type, $id = '0', $name = 'error', $category = 'error', $recipe = 'error')
    {
        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <script type="text/javascript" src="inc/messageboard.inc.js"></script>';
        if ($type == 'edit') {
            echo '
            <form method="post" name="editform" action="recipes.php">
                <fieldset>
                    <legend><span>'.T_('Edit Recipe').'</span></legend>';
        } else {
            echo '
            <form method="post" name="addform" action="recipes.php">
                <fieldset>
                    <legend><span>'.T_('Add Recipe').'</span></legend>';
        }
        echo '
                    <div>
                        <label for="name">'.T_('Name of Recipe').'</label>: 
                        <input type="text" name="name" id="name"';
        if ($type == 'edit') {
            echo " value=\"".htmlentities($name, ENT_COMPAT, 'UTF-8')."\"";
        }
        echo ' size="50"/>
                    </div><br/>
                    <script type="text/javascript">
                        var fname = new LiveValidation(\'name\', { onlyOnSubmit: true });
                        fname.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div>
                        <label for="category">'.T_('Category').'</label>: 
                        <select name="category">';
        echo "<option value=\"".T_('Appetizer') . "\"";
        if ($category == T_('Appetizer') || $category == 1) {
            echo " selected=\"selected\"";
        }
        echo ">" . T_('Appetizer') . "</option>";
        echo "<option value=\"" . T_('Breakfast') . "\"\"";
        if ($category == T_('Breakfast') || $category == 2) {
            echo " selected=\"selected\"";
        }
        echo ">" . T_('Breakfast') . "</option>";
        echo "<option value=\"" . T_('Dessert') . "\"";
        if ($category == T_('Dessert') || $category == 3) {
            echo " selected=\"selected\"";
        }
        echo ">" .T_('Dessert') ."</option>";
        echo "<option value=\"" . T_('Entree (Meat)') . "\"";
        if ($category == T_('Entree (Meat)') || $category == 4) {
            echo " selected=\"selected\"";
        }
        echo ">" . T_('Entree (Meat)') . "</option>";
        echo "<option value=\"" . T_('Entree (Seafood)') . "\"";
        if ($category == T_('Entree (Seafood)') || $category == 5) {
            echo " selected=\"selected\"";
        }
        echo ">" . T_('Entree (Seafood)') . "</option>";
        echo "<option value=\"" . T_('Entree (Vegetarian)') . "\"";
        if ($category == T_('Entree (Vegetarian)') || $category == 6) {
            echo " selected=\"selected\"";
        }
        echo ">".T_('Entree (Vegetarian)')."</option>";
        echo "<option value=\"" . T_('Salad') . "\"";
        if ($category == T_('Salad') || $category == 7) {
            echo " selected=\"selected\"";
        }
        echo ">" . T_('Salad') . "</option>";
        echo "<option value=\"" . T_('Side Dish') . "\"";
        if ($category == T_('Side Dish') || $category == 8) {
            echo " selected=\"selected\"";
        }
        echo ">" . T_('Side Dish') . "</option>";
        echo "<option value=\"" . T_('Soup') . "\"";
        if ($category == T_('Soup') || $category == 9) {
            echo " selected=\"selected\"";
        }
        echo '>'.T_('Soup').'</option>
                        </select>
                    </div><br/>
                    <script type="text/javascript">var bb = new BBCode();</script>';
        displayMBToolbar();
        echo '
                    <div><textarea name="post" id="post" rows="10" cols="63">';
        if ($type == 'edit') {
            echo $recipe;
        }
        echo '</textarea></div>
                    <script type="text/javascript">bb.init(\'post\');</script>
                    <script type="text/javascript">
                        var frecipe = new LiveValidation(\'post\', { onlyOnSubmit: true });
                        frecipe.add(Validate.Presence, {failureMessage: ""});
                    </script>';
        if ($type == 'add') {
            echo '
                    <p>
                        <input class="sub1" type="submit" name="submitadd" value="'.T_('Add').'"/> &nbsp;
                        <a href="recipes.php">'.T_('Cancel').'</a>
                    </p>';
        } else {
            echo '
                    <p>
                        <input type="hidden" name="id" value="'.$id.'"/>
                        <input class="sub1" type="submit" name="submitedit" value="'.T_('Edit').'"/> &nbsp;
                        <a href="recipes.php">'.T_('Cancel').'</a>
                    </p>';
        }
        echo '
                </fieldset>
            </form>';
    }

    function displayWhatsNewRecipes ()
    {
        $locale = new Locale();
        $today_start = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '000000';
        $today_end = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '235959';

        $sql = "SELECT * "
             . "FROM `fcms_recipes` "
             . "WHERE `date` >= DATE_SUB(CURDATE() , INTERVAL 30 DAY) "
             . "ORDER BY `date` DESC "
             . "LIMIT 0 , 5";
        $this->db->query($sql) or displaySQLError(
            "What's New Error", __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        if ($this->db->count_rows() > 0) {
            echo '
            <h3>'.T_('Recipes').'</h3>
            <ul>';
            while ($r = $this->db->get_row()) {
                $name = $r['name'];
                $displayname = getUserDisplayName($r['user']);
                switch ($r['category']) {
                    case T_('Appetizer'):
                        $url = "recipes.php?category=1&amp;id=" . $r['id'];
                        break;
                    case T_('Breakfast'):
                        $url = "recipes.php?category=2&amp;id=" . $r['id'];
                        break;
                    case T_('Dessert'):
                        $url = "recipes.php?category=3&amp;id=" . $r['id'];
                        break;
                    case T_('Entree (Meat)'):
                        $url = "recipes.php?category=4&amp;id=" . $r['id'];
                        break;
                    case T_('Entree (Seafood)'):
                        $url = "recipes.php?category=5&amp;id=" . $r['id'];
                        break;
                    case T_('Entree (Vegetarian)'):
                        $url = "recipes.php?category=6&amp;id=" . $r['id'];
                        break;
                    case T_('Salad'):
                        $url = "recipes.php?category=7&amp;id=" . $r['id'];
                        break;
                    case T_('Side Dish'):
                        $url = "recipes.php?category=8&amp;id=" . $r['id'];
                        break;
                    case T_('Soup'):
                        $url = "recipes.php?category=9&amp;id=" . $r['id'];
                        break;
                    default:
                        $url = "recipes.php";
                        break;
                }
                $date = $locale->fixDate('YmdHis', $this->tz_offset, $r['date']);
                if ($date >= $today_start && $date <= $today_end) {
                    $full_date = T_('Today');
                    $d = ' class="today"';
                } else {
                    $full_date = $locale->fixDate(T_('M. j, Y, g:i a'), $this->tz_offset, $r['date']);
                    $d = '';
                }
                echo '
                <li>
                    <div'.$d.'>'.$full_date.'</div>
                    <a href="'.$url.'">'.$name.'</a> - 
                    <a class="u" href="profile.php?member='.$r['user'].'">'.$displayname.'</a>
                </li>';
            }
            echo '
            </ul>';
        }
    }

} ?>
