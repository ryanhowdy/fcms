<?php
session_start();
$stripcap = 'true';
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	// a bug found with an array in $_POST
	if (!isset($_POST['addphoto'])) {
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
include_once('../inc/gallery_class.php');
$gallery = new PhotoGallery($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName()." - ".$LANG['poweredby']." ".getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="../<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="../themes/images/favicon.ico"/>
</head>
<body id="body-gallery">
	<a name="top"></a>
	<div id="header"><?php echo "<h1 id=\"logo\">".getSiteName()."</h1><p>".$LANG['welcome']." <a href=\"../profile.php?member=".$_SESSION['login_id']."\">"; echo getUserDisplayName($_SESSION['login_id']); echo "</a> | <a href=\"../settings.php\">".$LANG['link_settings']."</a> | <a href=\"../logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></p>"; ?></div>
	<?php displayTopNav("fix"); ?>
	<div id="pagetitle"><?php echo $LANG['link_gallery']; ?></div>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<?php
		displaySideNav("fix");
		if(checkAccess($_SESSION['login_id']) < 3) { 
			echo "\t<h2>".$LANG['admin']."</h2>\n\t"; 
			displayAdminNav("fixgal");
		} ?></div>
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
							$gallery->uploadPhoto($last_cat, $_FILES['photo_filename'], $_POST['photo_caption'], $rotate, $stripcap);
						}
					}
					if (isset($_GET['photos'])) { $gallery->displayUploadForm($_GET['photos'], $last_cat); } else { $gallery->displayUploadForm(1, $last_cat); }
				} elseif ($_GET['action'] == "category") {
					$show_latest = false;
					if (isset($_POST['newcat'])) {
						$show_latest = false;
						if(empty($_POST['cat_name'])) {
							echo "<p class=\"error-alert\">".$LANG['err_cat_name1']." <a href=\"?page=photo&amp;category=edit\">".$LANG['err_cat_name2']."</a> ".$LANG['err_cat_name3']."</p>";
						} else {
							mysql_query("INSERT INTO `fcms_gallery_category`(`name`, `user`) VALUES('" . addslashes($_POST['cat_name']) . "', " . $_SESSION['login_id'] . ")") or die('<h1>Category Error (gallery/index.php 83)</h1>' . mysql_error());
							echo "<p class=\"ok-alert\">".$LANG['ok_cat_add1']." <b>" . stripslashes($_POST['cat_name']) . "</b> ".$LANG['ok_cat_add2']." <a href=\"?action=upload\">".$LANG['ok_cat_add3']."</a> ".$LANG['ok_cat_add4']."</p>";
						}
					}
					if (isset($_POST['editcat'])) {
						if(empty($_POST['cat_name'])) { echo "<p class=\"error-alert\">".$LANG['err_cat_blank']."</p>"; } else {
							mysql_query("UPDATE fcms_gallery_category SET name = '" . addslashes($_POST['cat_name']) . "' WHERE id = " . $_POST['cid']) or die('<h1>Category Error (gallery/index.php 89)</h1>' . mysql_error());
							echo "<p class=\"ok-alert\">".$LANG['ok_cat_edit1']." " . stripslashes($_POST['cat_name']) . " ".$LANG['ok_cat_edit2']."</p>";
							$show_latest = false;
						}
					}
					if (isset($_POST['delcat'])) {
						mysql_query("DELETE FROM fcms_gallery_category WHERE id = " . $_POST['cid']) or die('<h1>Category Error (gallery/index.php 95)</h1>' . mysql_error());
						echo "<p class=\"ok-alert\">".$LANG['ok_cat_del']."</p>";
						$show_latest = false;
					}
					$gallery->displayAddCatForm();
				}
			}
			if (isset($_GET['uid']) && !isset($_GET['cid']) && !isset($_GET['pid'])) {
				$show_latest = false;
				$gallery->displayGalleryMenu($_GET['uid']);
				$gallery->showCategories($_GET['uid']);
			} elseif (isset($_GET['cid']) && !isset($_GET['pid'])) {
				$show_latest = false;
				$gallery->displayGalleryMenu($_GET['uid']);
				$gallery->showCategories($_GET['uid'], $_GET['cid']);
			} elseif (isset($_GET['pid'])) {
				$show_latest = false;
				if (isset($_POST['addcom'])) { mysql_query("INSERT INTO `fcms_gallery_comments`(`photo`, `comment`, `date`, `user`) VALUES(" . $_GET['pid'] . ", '" . addslashes($_POST['comment']) . "', NOW(), " . $_SESSION['login_id'] . ")") or die('<h1>Comment Error (gallery/index.php 110)</h1>' . mysql_error()); }
				if (isset($_POST['delcom'])) { mysql_query("DELETE FROM `fcms_gallery_comments` WHERE id=" . $_POST['id']) or die('<h1>Comment Error (gallery/index.php 111)</h1>' . mysql_error()); }
				if (isset($_GET['vote'])) { mysql_query("UPDATE `fcms_gallery_photos` SET `votes` = `votes`+1, `rating` = `rating`+" . $_GET['vote'] . " WHERE `id` = " . $_GET['pid']) or die('<h1>Vote Error (gallery/index.php 112)</h1>' . mysql_error()); }
				$gallery->showPhoto($_GET['uid'], $_GET['cid'], $_GET['pid']);		
			}
			if (isset($_GET['commentpid']) || isset($_GET['topratedpid']) || isset($_GET['viewspid'])) {
				$show_latest = false;
				$gallery->displayGalleryMenu();
				if (isset($_GET['commentpid'])) { $photo = $_GET['commentpid']; } elseif (isset($_GET['topratedpid'])) { $photo = $_GET['topratedpid']; } elseif (isset($_GET['viewspid'])) { $photo = $_GET['viewspid']; }
				$pid = substr($photo, 0, strpos($photo, '.'));
				if (isset($_POST['addcom'])) { mysql_query("INSERT INTO `fcms_gallery_comments`(`photo`, `comment`, `date`, `user`) VALUES($pid, '" . addslashes($_POST['comment']) . "', NOW(), " . $_SESSION['login_id'] . ")") or die('<h1>Comment Error (gallery/index.php 119)</h1>' . mysql_error()); }
				if (isset($_POST['delcom'])) { mysql_query("DELETE FROM `fcms_gallery_comments` WHERE id=" . $_POST['id']) or die('<h1>Comment Error (gallery/index.php 120)</h1>' . mysql_error()); }
				if (isset($_GET['vote'])) { mysql_query("UPDATE `fcms_gallery_photos` SET `votes` = `votes`+1, `rating` = `rating`+" . $_GET['vote'] . " WHERE `id` = $pid") or die('<h1>Vote Error (gallery/index.php 121)</h1>' . mysql_error()); }
				if (isset($_GET['commentpid'])) { $gallery->showAllPhoto($photo); } elseif (isset($_GET['topratedpid'])) { $gallery->showAllPhoto($photo, "toprated"); } elseif (isset($_GET['viewspid'])) { $gallery->showAllPhoto($photo, "views"); }
			}
			if(isset($_POST['add_editphoto'])) {
				mysql_query("UPDATE `fcms_gallery_photos` SET category='" . addslashes($_POST['category']) . "', caption='" . addslashes($_POST['photo_caption']) . "' WHERE id=" . $_POST['photo_id']) or die('<h1>Edit Photo Error (gallery/index.php 125)</h1>' . mysql_error());
				echo "<p class=\"ok-alert\">".$LANG['ok_photo_info']."</p>";
			}
			if(isset($_POST['editphoto'])) {
				$show_latest = false;
				$gallery->displayEditPhotoForm($_POST['photo']);
			}
			if (isset($_POST['deletephoto'])) {
				$photo = $_POST['photo'];
				$result = mysql_query("SELECT user, category, filename FROM fcms_gallery_photos WHERE id= $photo") or die('<h1>Photo Error (gallery/index.php 134)</h1>' . mysql_error());
				$filerow = mysql_fetch_array($result);
				$file_photo = $filerow['filename'];
				$photo_user_id = $filerow['user'];
				$photo_cat_id = $filerow['category'];
				mysql_query("DELETE FROM fcms_gallery_photos WHERE id=" . $photo) or die('<h1>Delete Photo Error (gallery/index.php 139)</h1>' . mysql_error());
				mysql_query("DELETE FROM fcms_gallery_comments WHERE photo=" . $photo) or die('<h1>Delete Comment Error (gallery/index.php 140)</h1>' . mysql_error());
				unlink("photos/member$photo_user_id/" . $file_photo) or die("<h1>Error</h1><p><b>Photo could not be deleted from server.</b></p>");
				unlink("photos/member$photo_user_id/tb_" . $file_photo) or die("<h1>Error</h1><p><b>Thumbnail could not be deleted from server.</b></p>");
				mysql_free_result($result);
				echo "<meta http-equiv='refresh' content='0;URL=?uid=" . $photo_user_id . "&amp;cid=" . $photo_cat_id . "'>";
			}
			if (isset($_GET['view'])) {
				$gallery->displayGalleryMenu($_GET['u']);
				if ($_GET['view'] == "comments") {
					if (isset($_GET['page'])) { $gallery->displayLatestTopMost("comments", $_GET['u'], (($_GET['page'] * 16) - 16)); } else { $gallery->displayLatestTopMost("comments", $_GET['u'], 0); }
				} elseif ($_GET['view'] == "member") {
					$gallery->showCategories();
				} elseif ($_GET['view'] == "toprated") {
					if (isset($_GET['page'])) { $gallery->displayLatestTopMost("toprated", $_GET['u'], (($_GET['page'] * 16) - 16)); } else { $gallery->displayLatestTopMost("toprated", $_GET['u'], 0); }
				} elseif ($_GET['view'] == "views") {
					if (isset($_GET['page'])) { $gallery->displayLatestTopMost("views", $_GET['u'], (($_GET['page'] * 16) - 16)); } else { $gallery->displayLatestTopMost("views", $_GET['u'], 0); }
				}
			} elseif ($show_latest) {
				$gallery->displayGalleryMenu();
				$gallery->displayLatestCategories();
				$gallery->displayLatestTopMost();
			} ?>
		</div><!-- #gallery .centercontent -->
	</div><!-- #content -->
	<?php displayFooter("fix"); ?>
</body>
</html>