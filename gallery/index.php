<?php
session_start();
$stripcap = 'true';
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	// a bug found with an array in $_POST
	if (!isset($_POST['addphoto']) && !isset($_POST['add_editphoto'])) {
		$stripcap = 'false';
		$_POST = array_map('stripslashes', $_POST);
	}
	$_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');
include_once('../inc/language.php');
if (isset($_SESSION['login_id'])) {
	if (!isLoggedIn($_SESSION['login_id'], $_SESSION['login_uname'], $_SESSION['login_pw'])) {
		displayLoginPage("fix");
		exit();
	}
} elseif (isset($_COOKIE['fcms_login_id'])) {
	if (isLoggedIn($_COOKIE['fcms_login_id'], $_COOKIE['fcms_login_uname'], $_COOKIE['fcms_login_pw'])) {
		$_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
		$_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
		$_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
	} else {
		displayLoginPage("fix");
		exit();
	}
} else {
	displayLoginPage("fix");
	exit();
}
header("Cache-control: private");
include_once('../inc/gallery_class.php');
$gallery = new PhotoGallery($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$pagetitle = $LANG['link_gallery'];
$d = "../";
$admin_d = "../admin/";
include_once(getTheme($_SESSION['login_id'], $d) . 'header.php');
?>
	<div id="leftcolumn">
        <?php
        include_once(getTheme($_SESSION['login_id'], $d) . 'sidenav.php');
        if (checkAccess($_SESSION['login_id']) < 3) {
            include_once(getTheme($_SESSION['login_id'], $d) . 'adminnav.php');
        }
        ?>
	</div>
	<div id="content">
		<div id="gallery" class="centercontent">
			<?php
			$show_latest = true;
			if (isset($_GET['action']) && (checkAccess($_SESSION['login_id']) <= 3 || checkAccess($_SESSION['login_id']) == 8 || checkAccess($_SESSION['login_id']) == 5)) {
				$gallery->displayGalleryMenu();
				if ($_GET['action'] == "upload") {
					$show_latest = false;
					$last_cat = 0;
					if (isset($_POST['addphoto'])) {
						if (empty($_POST['category'])) { 
							echo "<p class=\"error-alert\">".$LANG['err_cat_first']."</p>";
						} else { 
							$last_cat = $_POST['category'];
							if (isset($_POST['rotate'])) { $rotate = $_POST['rotate']; } else { $rotate = '0'; }
							$photo_id = $gallery->uploadPhoto($last_cat, $_FILES['photo_filename'], $_POST['photo_caption'], $rotate, $stripcap);
							if (isset($_POST['tagged'])) {
								$sql = "INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`) VALUES ";
								$first = true;
								foreach ($_POST['tagged'] as $member) {
									if (!$first) { $sql .= ", "; }
									$sql .= "($member, $photo_id) ";
									$first = false;
								}
								mysql_query($sql) or displaySQLError('Tagging Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
							}
						}
					}
					if (isset($_GET['photos'])) {
						$gallery->displayUploadForm($_GET['photos'], $last_cat);
					} else {
						$gallery->displayUploadForm(1, $last_cat);
					}
				} elseif ($_GET['action'] == "category") {
					$show_latest = false;
					if (isset($_POST['newcat'])) {
						$show_latest = false;
						if(empty($_POST['cat_name'])) {
							echo "<p class=\"error-alert\">".$LANG['err_cat_name1']." <a href=\"?page=photo&amp;category=edit\">".$LANG['err_cat_name2']."</a> ".$LANG['err_cat_name3']."</p>";
						} else {
							$sql = "INSERT INTO `fcms_gallery_category`(`name`, `user`) VALUES('" . addslashes($_POST['cat_name']) . "', " . $_SESSION['login_id'] . ")";
							mysql_query($sql) or displaySQLError('New Category Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
							echo "<p class=\"ok-alert\">".$LANG['ok_cat_add1']." <b>" . stripslashes($_POST['cat_name']) . "</b> ".$LANG['ok_cat_add2']." <a href=\"?action=upload\">".$LANG['ok_cat_add3']."</a> ".$LANG['ok_cat_add4']."</p>";
						}
					}
					if (isset($_POST['editcat'])) {
						if(empty($_POST['cat_name'])) {
							echo "<p class=\"error-alert\">".$LANG['err_cat_blank']."</p>";
						} else {
							$sql = "UPDATE fcms_gallery_category SET name = '" . addslashes($_POST['cat_name']) . "' WHERE id = " . $_POST['cid'];
							mysql_query($sql) or displaySQLError('Update Category Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
							echo "<p class=\"ok-alert\">".$LANG['ok_cat_edit1']." " . stripslashes($_POST['cat_name']) . " ".$LANG['ok_cat_edit2']."</p>";
							$show_latest = false;
						}
					}
					if (isset($_POST['delcat'])) {
						$sql = "DELETE FROM fcms_gallery_category WHERE id = " . $_POST['cid'];
						mysql_query($sql) or displaySQLError('Delete Category Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
						echo "<p class=\"ok-alert\">".$LANG['ok_cat_del']."</p>";
						$show_latest = false;
					}
					$gallery->displayAddCatForm();
				}
			}
			if (isset($_GET['uid']) && !isset($_GET['cid']) && !isset($_GET['pid'])) {
				$show_latest = false;
				if (isset($_GET['page'])) { $page = ($_GET['page'] * 16) - 16; } else { $page = 0; }
				$gallery->displayGalleryMenu($_GET['uid']);
				$gallery->showCategories($page, $_GET['uid']);
			} elseif (isset($_GET['cid']) && !isset($_GET['pid'])) {
				$show_latest = false;
				if (isset($_GET['page'])) { $page = ($_GET['page'] * 16) - 16; } else { $page = 0; }
				$gallery->displayGalleryMenu($_GET['uid']);
				$gallery->showCategories($page, $_GET['uid'], $_GET['cid']);
			} elseif (isset($_GET['pid'])) {
				$show_latest = false;
				if (isset($_POST['addcom'])) {
					$sql = "INSERT INTO `fcms_gallery_comments`(`photo`, `comment`, `date`, `user`) VALUES(" . $_GET['pid'] . ", '" . addslashes($_POST['comment']) . "', NOW(), " . $_SESSION['login_id'] . ")";
					mysql_query($sql) or displaySQLError('Add Comment Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
				}
				if (isset($_POST['delcom'])) {
					$sql = "DELETE FROM `fcms_gallery_comments` WHERE id=" . $_POST['id'];
					mysql_query($sql) or displaySQLError('Delete Comment Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
				}
				if (isset($_GET['vote'])) {
					$sql = "UPDATE `fcms_gallery_photos` SET `votes` = `votes`+1, `rating` = `rating`+" . $_GET['vote'] . " WHERE `id` = " . $_GET['pid'];
					mysql_query($sql) or displaySQLError('Vote Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
				}
				$gallery->showPhoto($_GET['uid'], $_GET['cid'], $_GET['pid']);		
			}
			if(isset($_POST['add_editphoto'])) {
				$photo_caption = stripslashes($_POST['photo_caption']);
				$sql = "UPDATE `fcms_gallery_photos` SET category='" . addslashes($_POST['category']) . "', caption='" . addslashes($photo_caption) . "' WHERE id=" . $_POST['photo_id'];
				mysql_query($sql) or displaySQLError('Edit Photo Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
				
				// Has someone been tagged?
				if (isset($_POST['tagged'])) {
					
					// Check whether previously tagged members exist
					if (isset($_POST['prev_tagged_users'])) {
						
						// Delete all members who were previously tagged, but now are not
						$prev_users = explode(",", $_POST['prev_tagged_users']);
						foreach ($prev_users as $user) {
							$key = array_search($user, $_POST['tagged']);
							if ($key === false) {
								$sql = "DELETE FROM `fcms_gallery_photos_tags` WHERE `photo` = " . $_POST['photo_id'] . " AND `user` = $user";
								mysql_query($sql) or displaySQLError('Delete Tagged Member Error', 'gallery/index.php', $sql, mysql_error());
							}
							
						}
						
						// Add only members who were not previously tagged
						foreach ($_POST['tagged'] as $user) {
							$key = array_search($user, $prev_users);
							if ($key === false) {
								$sql = "INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`) VALUES ($user, " . $_POST['photo_id'] . ")";
								mysql_query($sql) or displaySQLError('Tag Members Error', 'gallery/index.php', $sql, mysql_error());
							}
							
						}
						
					} else {
						
						// Add all tagged members, since no one was previously tagged
						foreach ($_POST['tagged'] as $user) {
							$sql = "INSERT INTO `fcms_gallery_photos_tags` (`user`, `photo`) VALUES ($user, " . $_POST['photo_id'] . ")";
							mysql_query($sql) or displaySQLError('Tag Members Error', 'gallery/index.php', $sql, mysql_error());
						}
					}
				}
				
				echo "<p class=\"ok-alert\">".$LANG['ok_photo_info']."</p>";
			}
			if(isset($_POST['editphoto'])) {
				$show_latest = false;
				$gallery->displayEditPhotoForm($_POST['photo']);
			}
			if (isset($_POST['deletephoto'])) {
				$show_latest = false;
				$sql = "SELECT `user`, `category`, `filename` FROM `fcms_gallery_photos` WHERE `id` = " . $_POST['photo'];
				$result = mysql_query($sql) or displaySQLError('Photo Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
				$filerow = mysql_fetch_array($result);
				$file_photo = $filerow['filename'];
				$photo_user_id = $filerow['user'];
				$photo_cat_id = $filerow['category'];
				$sql = "DELETE FROM `fcms_gallery_photos` WHERE `id` = " . $_POST['photo'];
				mysql_query($sql) or displaySQLError('Delete Photo Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
				$sql = "DELETE FROM `fcms_gallery_comments` WHERE `photo` = " . $_POST['photo'];
				mysql_query($sql) or displaySQLError('Delete Comments Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
				unlink("photos/member$photo_user_id/" . $file_photo);
				unlink("photos/member$photo_user_id/tb_" . $file_photo);
				mysql_free_result($result);
				$sql = "SELECT `full_size_photos` FROM `fcms_config`";
				$result = mysql_query($sql) or displaySQLError('Full Size Error', 'gallery/index.php [' . __LINE__ . ']', $sql, mysql_error());
				$r = mysql_fetch_array($result);
				if ($r['full_size_photos'] == 1) {
					unlink("photos/member$photo_user_id/full_" . $file_photo);
				}
				$gallery->displayGalleryMenu($photo_user_id);
				$gallery->showCategories(0, $photo_user_id, $photo_cat_id);
				//echo "<meta http-equiv='refresh' content='0;URL=?uid=" .  . "&amp;cid=" . $photo_cat_id . "'>";
			}
			if ($show_latest) {
				$gallery->displayGalleryMenu();
				$gallery->displayLatestCategories();
				$gallery->showCategories(-1, 0, 'comments');
				echo "<p class=\"alignright\"><a class=\"rss\" href=\"../rss.php?feed=gallery\">" . $LANG['rss_feed'] . "</a></p>";
			} ?>
		</div><!-- #gallery .centercontent -->
	</div><!-- #content -->
	<?php displayFooter("fix"); ?>
</body>
</html>