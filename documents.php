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

// Check that the user is logged in
isLoggedIn();

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
// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['link_documents'];
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
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
                $doc = str_replace (" ", "_", $doc);
				$desc = addslashes($_POST['desc']);
				if ($docs->uploadDocument($_FILES['doc']['type'], $doc, $_FILES['doc']['tmp_name'], $_FILES['doc']['error'])) {
					$sql = "INSERT INTO `fcms_documents`(`name`, `description`, `user`, `date`) VALUES('$doc', '$desc', " . $_SESSION['login_id'] . ", NOW())";
					mysql_query($sql) or displaySQLError('New Document Error', 'documents.php [' . __LINE__ . ']', $sql, mysql_error());
					echo "<p class=\"ok-alert\" id=\"add\">".$LANG['ok_doc_add']."</p>";
					echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('add').toggle()\",3000); }</script>";
                    // Email members
                    $sql = "SELECT u.`email`, s.`user` "
                         . "FROM `fcms_user_settings` AS s, `fcms_users` AS u "
                         . "WHERE `email_updates` = '1'"
                         . "AND u.`id` = s.`user`";
                    $result = mysql_query($sql) or displaySQLError('Email Updates Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
                    if (mysql_num_rows($result) > 0) {
                        while ($r = mysql_fetch_array($result)) {
                            $name = getUserDisplayName($_SESSION['login_id']);
                            $to = getUserDisplayName($r['user']);
                            $subject = "$name " . $LANG['added_docs'] . " ($doc)";
                            $email = $r['email'];
                            $url = getDomainAndDir();
                            $msg = $LANG['dear'] . " $to,

$name " . $LANG['added_docs'] . " ($doc)

{$url}documents.php

----
" . $LANG['opt_out_updates'] . "

{$url}settings.php

";
                            mail($email, $subject, $msg, $email_headers);
                        }
                    }
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
					echo "<div id=\"sections_menu\" class=\"clearfix\"><ul><li><a class=\"add\" href=\"?adddoc=yes\">";
                    echo $LANG['add_document'] . "</a></li></ul></div>\n";
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
