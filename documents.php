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
include_once('inc/documents_class.php');
$docs = new Documents($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
header("Cache-control: private");
if (isset($_GET['download'])) {
	$show = false;
	$filename = "gallery/documents/" . basename($_GET['download']);
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Type: application/download");
	header("Content-Disposition: attachment; filename=".basename($filename).";");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".filesize($filename));
	@readfile($filename);
	exit(0);
}
$pagetitle = $LANG['link_documents'];
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
		<div id="documents" class="centercontent">
			<?php
			$show = true;
			if (isset($_POST['submitadd'])) {
				$doc = $_FILES['doc']['name'];
				$desc = addslashes($_POST['desc']);
				if ($docs->uploadDocument($_FILES['doc']['type'], $_FILES['doc']['name'], $_FILES['doc']['tmp_name'])) {
					$sql = "INSERT INTO `fcms_documents`(`name`, `description`, `user`, `date`) VALUES('$doc', '$desc', " . $_SESSION['login_id'] . ", NOW())";
					mysql_query($sql) or displaySQLError('New Document Error', 'documents.php [' . __LINE__ . ']', $sql, mysql_error());
					echo "<p class=\"ok-alert\" id=\"add\">".$LANG['ok_doc_add']."</p>";
					echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('add').toggle()\",3000); }</script>";
				}
			} 
			if (isset($_POST['deldoc'])) {
				$sql = "DELETE FROM `fcms_documents` WHERE `id` = " . $_POST['id'];
				mysql_query($sql) or displaySQLError('Delete Document Error', 'documents.php [' . __LINE__ . ']', $sql, mysql_error());
				unlink("gallery/documents/" . $_POST['name']);
				echo "<p class=\"ok-alert\" id=\"del\">".$LANG['ok_doc_del']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('del').toggle()\",2000); }</script>";
			}
			if (isset($_GET['adddoc']) && checkAccess($_SESSION['login_id']) <= 5) {
				$show = false;
				$docs->displayForm();
			}
			if ($show) {
				if (checkAccess($_SESSION['login_id']) <= 5) {
					echo "<div class=\"clearfix\"><a class=\"link_block add\" href=\"?adddoc=yes\">" . $LANG['add_document'] . "</a></div>\n";
				}
				$page = 1;
				if (isset($_GET['page'])) { $page = $_GET['page']; }
				$docs->showDocuments($page);
			} ?>
		</div><!-- #documents .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>