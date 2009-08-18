<?php
session_start();
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	$_POST = array_map('stripslashes', $_POST);
	$_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');
if (isset($_SESSION['login_id'])) {
	if (!isLoggedIn($_SESSION['login_id'], $_SESSION['login_uname'], $_SESSION['login_pw'])) {
		displayLoginPage();
		exit();
	}
} elseif (isset($_COOKIE['fcms_login_id'])) {
	if (isLoggedIn($_COOKIE['fcms_login_id'], $_COOKIE['fcms_login_uname'], $_COOKIE['fcms_login_pw'])) {
		$_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
		$_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
		$_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
	} else {
		displayLoginPage();
		exit();
	}
} else {
	displayLoginPage();
	exit();
}
header("Cache-control: private");
include_once('inc/recipes_class.php');
$rec = new Recipes($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$pagetitle = $LANG['link_recipes'];
$d = "";
$admin_d = "admin/";
include_once(getTheme($_SESSION['login_id']) . 'header.php');
?>
	<div id="leftcolumn">
        <?php
        include_once(getTheme($_SESSION['login_id']) . 'sidenav.php');
        if (checkAccess($_SESSION['login_id']) < 3) {
            include_once(getTheme($_SESSION['login_id']) . 'adminnav.php');
        }
        ?>
	</div>
	<div id="content">
		<div id="recipe" class="centercontent">
			<?php
			$show = true;
			if (isset($_POST['submitadd'])) {
				$name = addslashes($_POST['name']);
				$recipe = addslashes($_POST['post']);
				$sql = "INSERT INTO `fcms_recipes`(`name`, `category`, `recipe`, `user`, `date`) VALUES('$name', '".$_POST['category']."', '$recipe', " . $_SESSION['login_id'] . ", NOW())";
				mysql_query($sql) or displaySQLError('New Recipe Error', 'recipes.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"add\">".$LANG['ok_recipe_add']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('add').toggle()\",3000); }</script>";
			} 
			if (isset($_POST['submitedit'])) {
				$name = addslashes($_POST['name']);
				$recipe = addslashes($_POST['post']);
				$sql = "UPDATE `fcms_recipes` SET `name` = '$name', `category` = '".$_POST['category']."', `recipe` = '$recipe' WHERE `id` = " . $_POST['id'];
				mysql_query($sql) or displaySQLError('Edit Recipe Error', 'recipes.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"edit\">".$LANG['ok_recipe_edit']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('edit').toggle()\",3000); }</script>";
			}
			if (isset($_POST['delrecipe'])) {
				$sql = "DELETE FROM `fcms_recipes` WHERE `id` = " . $_POST['id'];
				mysql_query($sql) or displaySQLError('Delete Recipe Error', 'recipes.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"del\">".$LANG['ok_recipe_del']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('del').toggle()\",2000); }</script>";
			}
			if (isset($_GET['addrecipe']) && checkAccess($_SESSION['login_id']) <= 5) {
				$show = false;
				$rec->displayForm('add');
			}
			if (isset($_POST['editrecipe'])) {
				$show = false;
				$rec->displayForm('edit', $_POST['id'], $_POST['name'], $_POST['category'], $_POST['post']);
			}
			if (isset($_GET['category'])) {
				// Santizing user input - category - only allow digits 0-9
				if (preg_match('/^\d+$/', $_GET['category'])) {
					$show = false;
					echo "<div class=\"clearfix\"><a class=\"link_block home\" href=\"recipes.php\">".$LANG['recipe_cats']."</a>";
					if (checkAccess($_SESSION['login_id']) <= 5) {
						echo "<a class=\"link_block add\" href=\"?addrecipe=yes\">".$LANG['add_recipe']."</a>";
					}
					echo "</div>\n";
					$page = 1; $id = 0;
					if (isset($_GET['page'])) { if (preg_match('/^\d+$/', $_GET['page'])) { $page = escape_string($_GET['page']); } }
					if (isset($_GET['id'])) { if (preg_match('/^\d+$/', $_GET['id'])) { $id = escape_string($_GET['id']); } }
					$rec->showRecipeInCategory(escape_string($_GET['category']), $page, $id);
				}
			}
			if ($show) {
				if (checkAccess($_SESSION['login_id']) <= 5) {
					echo "<div class=\"clearfix\"><a class=\"link_block add\" href=\"?addrecipe=yes\">".$LANG['add_recipe']."</a></div>\n";
				}
				$rec->showRecipes();
			} ?>
			</div><!-- #recipe .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>