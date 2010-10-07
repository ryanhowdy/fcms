<?php
session_start();

include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/recipes_class.php');

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn();
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$rec = new Recipes($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Recipes'),
    'path'          => "",
    'admin_path'    => "admin/",
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
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

// Show Header
include_once(getTheme($currentUserId) . 'header.php');

echo '
        <div id="recipe-page" class="centercontent">';

$show = true;
//------------------------------------------------------------------------------
// Add recipe
//------------------------------------------------------------------------------
if (isset($_POST['submitadd'])) {

    $name           = cleanInput($_POST['name']);
    $category       = cleanInput($_POST['category'], 'int');
    $ingredients    = cleanInput($_POST['ingredients']);
    $directions     = cleanInput($_POST['directions']);
    $thumbnail      = 'no_recipe.jpg';

    // Upload Recipe Image
    if ($_FILES['thumbnail']['name'] && $_FILES['thumbnail']['error'] < 1) {
        $thumbnail = uploadImages(
            $_FILES['thumbnail']['type'], $_FILES['thumbnail']['name'], 
            $_FILES['thumbnail']['tmp_name'], "gallery/upimages/", 100, 100, true, false
        );
    }
    $sql = "INSERT INTO `fcms_recipes` 
                (`name`, `thumbnail`, `category`, `ingredients`, `directions`, `user`, `date`) 
            VALUES(
                '$name', 
                '$thumbnail',
                '$category',
                '$ingredients', 
                '$directions', 
                $currentUserId, 
                NOW()
            )";
    mysql_query($sql) or displaySQLError(
        'New Recipe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $rec_id = mysql_insert_id();
    echo '
            <p class="ok-alert" id="add">'.T_('Recipe Added Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'add\').toggle()",3000); }
            </script>';

    // Email members
    $sql = "SELECT u.`email`, s.`user` 
            FROM `fcms_user_settings` AS s, `fcms_users` AS u 
            WHERE `email_updates` = '1'
            AND u.`id` = s.`user`";
    $result = mysql_query($sql) or displaySQLError(
        'Email Updates Error', __FILE__ . ' [' . __LINE__ . ']', 
        $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        while ($r = mysql_fetch_array($result)) {
            $recipeName = $name;
            $recipeUser = getUserDisplayName($currentUserId);
            $to = getUserDisplayName($r['user']);
            $subject = sprintf(T_('%s has added the recipe: %s'), $recipeUser, $recipeName);
            $email = $r['email'];
            $url = getDomainAndDir();
            $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'recipes.php?category='.$category.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
            $email_headers = getEmailHeaders();
            mail($email, $subject, $msg, $email_headers);
        }
    }
}

//------------------------------------------------------------------------------
// Edit recipe
//------------------------------------------------------------------------------
if (isset($_POST['submitedit'])) {
    $sql = "UPDATE `fcms_recipes` 
            SET `name`          = '" . cleanInput($_POST['name']) . "', 
                `category`      = '" . cleanInput($_POST['category']) . "', 
                `ingredients`   = '" . cleanInput($_POST['ingredients']) . "',
                `directions`    = '" . cleanInput($_POST['directions']) . "' 
            WHERE `id` = '" . cleanInput($_POST['id'], 'int') . "'";
    mysql_query($sql) or displaySQLError(
        'Edit Recipe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    echo '
            <p class="ok-alert" id="edit">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'edit\').toggle()",3000); }
            </script>';
}

//------------------------------------------------------------------------------
// Add category
//------------------------------------------------------------------------------
if (isset($_POST['submit-category'])) {
    $show = false;
    $sql = "INSERT INTO `fcms_category` (`name`, `type`, `user`)
            VALUES (
                '" . cleanInput($_POST['name']) . "',
                'recipe', 
                '$currentUserId'
            )";
    mysql_query($sql) or displaySQLError(
        'New Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $cat = mysql_insert_id();
    $rec->displayAddRecipeForm($cat);
}

//------------------------------------------------------------------------------
// Delete confirmation
//------------------------------------------------------------------------------
if (isset($_POST['delrecipe']) && !isset($_POST['confirmed'])) {
    $show = false;
    echo '
                <div class="info-alert clearfix">
                    <form action="recipes.php" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.(int)$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="recipes.php">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

//------------------------------------------------------------------------------
// Delete recipe
//------------------------------------------------------------------------------
} elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
    $sql = "DELETE FROM `fcms_recipes` 
            WHERE `id` = '" . cleanInput($_POST['id'], 'int') . "'";
    mysql_query($sql) or displaySQLError(
        'Delete Recipe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    echo '
            <p class="ok-alert" id="del">'.T_('Recipe Deleted Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'del\').toggle()",2000); }
            </script>';
}

//------------------------------------------------------------------------------
// Add recipe form
//------------------------------------------------------------------------------
if (isset($_GET['addrecipe']) && checkAccess($currentUserId) <= 5) {
    $show = false;
    $cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
    $rec->displayAddRecipeForm($cat);
}

//------------------------------------------------------------------------------
// Edit recipe form
//------------------------------------------------------------------------------
if (isset($_POST['editrecipe'])) {
    $show = false;
    $id             = cleanOutput($_POST['id']);
    $name           = cleanOutput($_POST['name']);
    $category       = cleanOutput($_POST['category']);
    $ingredients    = cleanOutput($_POST['ingredients']);
    $directions     = cleanOutput($_POST['directions']);
    $rec->displayEditRecipeForm($id, $name, $category, $ingredients, $directions);
}

//------------------------------------------------------------------------------
// Add category form
//------------------------------------------------------------------------------
if (isset($_GET['add']) and checkAccess($currentUserId) <= 5) {
    $show = false;
    $rec->displayAddCategoryForm();
}

//------------------------------------------------------------------------------
// Add comment
//------------------------------------------------------------------------------
if (isset($_POST['addcom'])) {
    $sql = "INSERT INTO `fcms_recipe_comment` (`recipe`, `comment`, `user`, `date`)
            VALUES (
                '" . cleanInput($_POST['recipe'], 'int') . "',
                '" . cleanInput($_POST['comment']) . "',
                '$currentUserId',
                NOW()
            )";
    mysql_query($sql) or displaySQLError(
        'Add Comment Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
}

//------------------------------------------------------------------------------
// Delete comment
//------------------------------------------------------------------------------
if (isset($_POST['delcom'])) {
    if ($currentUserId == $_POST['user'] || checkAccess($currentUserId) < 2) {
        $sql = "DELETE FROM `fcms_recipe_comment`
                WHERE `id` = '" . cleanInput($_POST['id'], 'int') . "'";
        mysql_query($sql) or displaySQLError(
            'Delete Comment Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

    } else {
        echo '
        <p class="error-alert">'.T_('You do not have permission to delete this comment.').'</p>';
    }
}

//------------------------------------------------------------------------------
// Show recipes in specific Category
//------------------------------------------------------------------------------
if (isset($_GET['category'])) {

    $show = false;
    $id = 0;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $category = cleanInput($_GET['category'], 'int');

    // Show recipe
    if (isset($_GET['id'])) {
        $id = cleanInput($_GET['id'], 'int');
        $rec->showRecipe($category, $id);

    // Show list of recipes
    } else {
        $rec->showRecipeInCategory($category, $page);
    }

}

//------------------------------------------------------------------------------
// Display Last 5 recipes
//------------------------------------------------------------------------------
if ($show) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rec->showRecipes($page);
}

echo '
        </div><!-- #recipe-page .centercontent -->';


// Show Footer
include_once(getTheme($currentUserId) . 'footer.php');
