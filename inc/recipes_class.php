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
                <ul><li><a class="add" href="?addrecipe=yes">'._('Add Recipe').'</a></li></ul>
            </div>';
        }

        // Get Counts
        $appetizer = $this->getRecipeCountInCategory(_('Appetizer'));
        $breakfast = $this->getRecipeCountInCategory(_('Breakfast'));
        $dessert = $this->getRecipeCountInCategory(_('Dessert'));
        $meat = $this->getRecipeCountInCategory(_('Entree (Meat)'));
        $seafood = $this->getRecipeCountInCategory(_('Entree (Seafood)'));
        $vegetable = $this->getRecipeCountInCategory(_('Entree (Vegetarian)'));
        $salad = $this->getRecipeCountInCategory(_('Salad'));
        $side = $this->getRecipeCountInCategory(_('Side Dish'));
        $soup = $this->getRecipeCountInCategory(_('Soup'));
        
        echo '
            <h2>'._('Recipe Categories').'</h2>
            <div class="cat_row clearfix">
                <div class="cat">
                    <ul><li><a href="?category=1">'._('Appetizer').'<span>'.$appetizer.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=2">'._('Breakfast').'<span>'.$breakfast.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=3">'._('Dessert').'<span>'.$dessert.'</span></a></li></ul>
                </div>
            </div>
            <div class="cat_row clearfix">
                <div class="cat">
                    <ul><li><a href="?category=4">'._('Entree (Meat)').'<span>'.$meat.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=5">'._('Entree (Seafood)').'<span>'.$seafood.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=6">'._('Entree (Vegetarian)').'<span>'.$vegetable.'</span></a></li></ul>
                </div>
            </div>
            <div class="cat_row clearfix">
                <div class="cat">
                    <ul><li><a href="?category=7">'._('Salad').'<span>'.$salad.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=8">'._('Side Dish').'<span>'.$side.'</span></a></li></ul>
                </div>
                <div class="cat">
                    <ul><li><a href="?category=9">'._('Soup').'<span>'.$soup.'</span></a></li></ul>
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
            $date = $locale->fixDate(_('F j, Y, g:i a'), $this->tz_offset, $r['date']);
            echo '
            <p>&nbsp;</p>
            <h2>'._('Latest Recipe').'</h2>
            <h4>'.$r['name'].'</h4>
            <span class="date">
                '.sprintf(_('Submitted by %s on %s.'), $displayname, $date).'
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
                    <li><a href="recipes.php">'._('Recipe Categories').'</a></li>';
        if (checkAccess($this->current_user_id) <= 5) {
            echo '
                </ul>
            </div>
            <div id="actions_menu" class="clearfix">
                <ul>
                    <li><a href="?addrecipe=yes&amp;cat='.$cat.'">'._('Add Recipe').'</a></li>';
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
                echo _('Appetizer');
                $cat_name = _('Appetizer');
                break;
            case 2:
                echo _('Breakfast');
                $cat_name = _('Breakfast');
                break;
            case 3:
                echo _('Dessert');
                $cat_name = _('Dessert');
                break;
            case 4:
                echo _('Entree (Meat)');
                $cat_name = _('Entree (Meat)');
                break;
            case 5:
                echo _('Entree (Seafood)');
                $cat_name = _('Entree (Seafood)');
                break;
            case 6:
                echo _('Entree (Vegetarian)');
                $cat_name = _('Entree (Vegetarian)');
                break;
            case 7:
                echo _('Salad');
                $cat_name = _('Salad');
                break;
            case 8:
                echo _('Side Dish');
                $cat_name = _('Side Dish');
                break;
            case 9:
                echo _('Soup');
                $cat_name = _('Soup');
                break;
            default:
                echo "<p class=\"error-alert\">" . _('The Category you are trying to view doesn\'t exist.') . "</p>";
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
                $date = $locale->fixDate(_('F j, Y, g:i a'), $this->tz_offset, $r['date']);
                if ($id > 0) {
                    $name = $r['name'];
                } else {
                    $name = '<a href="recipes.php?category='.$cat.'&amp;id='.$r['id'].'">'.$r['name'].'</a>';
                }
                echo '
            <h4>'.$name.'</h4>
            <span class="date">
                '.sprintf(_('Submitted by %s on %s.'), $displayname, $date);
                if ($this->current_user_id == $r['user'] || checkAccess($this->current_user_id) < 2) {
                    echo ' &nbsp;
                <form method="post" action="recipes.php">
                    <div>
                        <input type="hidden" name="id" value="'.$r['id'].'"/>
                        <input type="hidden" name="name" value="'.htmlentities($r['name'], ENT_COMPAT, 'UTF-8').'"/>
                        <input type="hidden" name="category" value="'.htmlentities($r['category'], ENT_COMPAT, 'UTF-8').'"/>
                        <input type="hidden" name="post" value="'.htmlentities($r['recipe'], ENT_COMPAT, 'UTF-8').'"/>
                        <input type="submit" name="editrecipe" value="'._('Edit').'" class="editbtn" title="'._('Edit this Recipe').'"/>
                    </div>
                </form>';
                }
                if (checkAccess($this->current_user_id) < 2) {
                    echo ' &nbsp;
                <form class="delrec" method="post" action="recipes.php">
                    <div>
                        <input type="hidden" name="id" value="'.$r['id'].'"/>
                        <input type="submit" name="delrecipe" value="'._('Delete').'" class="delbtn" title="'._('Delete this Recipe').'"/>
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
                <h2>'._('Welcome to the Recipe section.').'</h2>
                <p><i>'._('Currently no one has added any recipes.').'</i></p>
                <p><a href="?addrecipe=yes&amp;cat='.$cat.'">'._('Add a Recipe').'</a></p>
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
            return "0 " . _('Recipes');
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
                    <legend><span>'._('Edit Recipe').'</span></legend>';
        } else {
            echo '
            <form method="post" name="addform" action="recipes.php">
                <fieldset>
                    <legend><span>'._('Add Recipe').'</span></legend>';
        }
        echo '
                    <div>
                        <label for="name">'._('Name of Recipe').'</label>: 
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
                        <label for="category">'._('Category').'</label>: 
                        <select name="category">';
        echo "<option value=\""._('Appetizer') . "\"";
        if ($category == _('Appetizer') || $category == 1) {
            echo " selected=\"selected\"";
        }
        echo ">" . _('Appetizer') . "</option>";
        echo "<option value=\"" . _('Breakfast') . "\"\"";
        if ($category == _('Breakfast') || $category == 2) {
            echo " selected=\"selected\"";
        }
        echo ">" . _('Breakfast') . "</option>";
        echo "<option value=\"" . _('Dessert') . "\"";
        if ($category == _('Dessert') || $category == 3) {
            echo " selected=\"selected\"";
        }
        echo ">" ._('Dessert') ."</option>";
        echo "<option value=\"" . _('Entree (Meat)') . "\"";
        if ($category == _('Entree (Meat)') || $category == 4) {
            echo " selected=\"selected\"";
        }
        echo ">" . _('Entree (Meat)') . "</option>";
        echo "<option value=\"" . _('Entree (Seafood)') . "\"";
        if ($category == _('Entree (Seafood)') || $category == 5) {
            echo " selected=\"selected\"";
        }
        echo ">" . _('Entree (Seafood)') . "</option>";
        echo "<option value=\"" . _('Entree (Vegetarian)') . "\"";
        if ($category == _('Entree (Vegetarian)') || $category == 6) {
            echo " selected=\"selected\"";
        }
        echo ">"._('Entree (Vegetarian)')."</option>";
        echo "<option value=\"" . _('Salad') . "\"";
        if ($category == _('Salad') || $category == 7) {
            echo " selected=\"selected\"";
        }
        echo ">" . _('Salad') . "</option>";
        echo "<option value=\"" . _('Side Dish') . "\"";
        if ($category == _('Side Dish') || $category == 8) {
            echo " selected=\"selected\"";
        }
        echo ">" . _('Side Dish') . "</option>";
        echo "<option value=\"" . _('Soup') . "\"";
        if ($category == _('Soup') || $category == 9) {
            echo " selected=\"selected\"";
        }
        echo '>'._('Soup').'</option>
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
                        <input class="sub1" type="submit" name="submitadd" value="'._('Add').'"/> &nbsp;
                        <a href="recipes.php">'._('Cancel').'</a>
                    </p>';
        } else {
            echo '
                    <p>
                        <input type="hidden" name="id" value="'.$id.'"/>
                        <input class="sub1" type="submit" name="submitedit" value="'._('Edit').'"/> &nbsp;
                        <a href="recipes.php">'._('Cancel').'</a>
                    </p>';
        }
        echo '
                </fieldset>
            </form>';
    }

    function displayWhatsNewRecipes ()
    {
        $locale = new Locale();
        $today = date('Y-m-d');
        $tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
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
            <h3>'._('Recipes').'</h3>
            <ul>';
            while ($r = $this->db->get_row()) {
                $name = $r['name'];
                $displayname = getUserDisplayName($r['user']);
                $date = $locale->fixDate(_('M. j, Y, g:i a'), $this->tz_offset, $r['date']);
                switch ($r['category']) {
                    case _('Appetizer'):
                        $url = "recipes.php?category=1&amp;id=" . $r['id'];
                        break;
                    case _('Breakfast'):
                        $url = "recipes.php?category=2&amp;id=" . $r['id'];
                        break;
                    case _('Dessert'):
                        $url = "recipes.php?category=3&amp;id=" . $r['id'];
                        break;
                    case _('Entree (Meat)'):
                        $url = "recipes.php?category=4&amp;id=" . $r['id'];
                        break;
                    case _('Entree (Seafood)'):
                        $url = "recipes.php?category=5&amp;id=" . $r['id'];
                        break;
                    case _('Entree (Vegetarian)'):
                        $url = "recipes.php?category=6&amp;id=" . $r['id'];
                        break;
                    case _('Salad'):
                        $url = "recipes.php?category=7&amp;id=" . $r['id'];
                        break;
                    case _('Side Dish'):
                        $url = "recipes.php?category=8&amp;id=" . $r['id'];
                        break;
                    case _('Soup'):
                        $url = "recipes.php?category=9&amp;id=" . $r['id'];
                        break;
                    default:
                        $url = "recipes.php";
                        break;
                }
                if (
                    strtotime($r['date']) >= strtotime($today) && 
                    strtotime($r['date']) > $tomorrow
                ) {
                    $full_date = _('Today');
                    $d = ' class="today"';
                } else {
                    $full_date = $date;
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
