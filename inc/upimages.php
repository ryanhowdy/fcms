<?php
session_start();
include_once('config_inc.php');
include_once('util_inc.php');
include_once('language.php');
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
} ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo $cfg_sitename . " - " . $LANG['poweredby'] . " " . $stgs_release; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="../<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="../themes/images/favicon.ico"/>
<style type="text/css">
<!-- 
body { width: 600px; margin: 0; padding: 20px; text-align: left; font-family: Verdana, Tahoma, Arial, sans-serif; font-size: 11px; border: none; background: #fff; }
form div, form { display: inline; }
td.n { padding: 2px; text-align: right; width: 75px; }
td.v { text-align: center; width: 30px; }
td.file { text-align: left; width: 150px; }
tr.alt { background-color: #f4f4f4; }
-->
</style>
<script type="text/javascript">
<!--
function insertUpImage(str) {
	var textarea = window.opener.document.getElementById('post');
	if (textarea) {
		if (textarea.value == "message") { textarea.value = str + ' '; } else { textarea.value += str + ' '; }
		textarea.focus();
	}
	javascript:window.close();
	return true;
}
-->
</script>
</head>
<body>
<?php
if (isset($_POST['delimg'])) {
	if (checkAccess($_SESSION['login_id']) < 2) {
		unlink("../gallery/upimages/" . $_POST['img']);
		echo "<p class=\"ok-alert\"><b>" . $_POST['img'] . "</b> ".$LANG['was_deleted']."</p>";
	} else {
		echo "<p class=\"error-alert\">".$LANG['err_del_img']."</p>";
	}
}
if (isset($_POST['upload'])) {
	if ($_FILES['upfile']['name']) { uploadImages($_FILES['upfile']['type'], $_FILES['upfile']['name'], $_FILES['upfile']['tmp_name'], "../gallery/upimages/", 600, 400); }
} else { ?>
	<h2><?php echo $LANG['up_image']; ?></h2>
	<form enctype="multipart/form-data" action="upimages.php" method="post">
		<p><input type="file" name="upfile" id="upfile" size="30" title="<?php echo $LANG['title_img_up']; ?>"/></p>
		<div><input type="submit" name="upload" id="upload" value="<?php echo $LANG['up_image']; ?>"/></div>
	</form><?php 
}
?>
<p>&nbsp;</p>
<h2><?php echo $LANG['upd_images']; ?></h2>
<table><tr><th><?php echo $LANG['upd_images']; ?></th><th><?php echo $LANG['filename']; ?></th><th></th><th></th><th></th></tr>
<?php
$img_dir = opendir("../gallery/upimages");
while ($file = readdir($img_dir)) { $images_in_dir[] = $file; }
natcasesort($images_in_dir);
reset($images_in_dir);
$i = 0;  $total_size = 0;
foreach ($images_in_dir as $file) {
	$img_name_arr = explode(".", $file);
	$img_type = end($img_name_arr);
	if ($file != ".." and $file != ".") {
		$i++;
		$this_size =  filesize("../gallery/upimages/" . $file);
		$total_size += $this_size;
		$img_info = getimagesize("../gallery/upimages/" . $file);
		$win_w = $img_info[0] + 50;
		$win_h = $img_info[1] + 50;
		if ($i % 2 != 0) { echo "\t<tr class=\"alt\">"; } else { echo "\t<tr>"; }
		echo "<td class=\"v\"><button class=\"viewbtn\" onclick=\"window.open('../gallery/upimages/$file','file','width=$win_w,height=$win_h,resizable=no,location=no,menubar=no,status=no'); return false;\"/></td><td class=\"file\">";
		echo "<a href=\"#\" onclick=\"insertUpImage('[IMG=gallery/upimages/$file]')\" title=\"".$LANG['title_ins_img']."\">$file</a></td><td>";
		if (checkAccess($_SESSION['login_id']) < 2) {
			echo "<form method=\"post\" action=\"upimages.php\"><div><input type=\"hidden\" name=\"img\" value=\"$file\"/><input type=\"submit\" name=\"delimg\" value=\" \" class=\"delbtn\" title=\"".$LANG['title_del_img']."\" onclick=\"javascript:return confirm('".$LANG['js_del_img']."');\" /></div></form>";
		}
		echo "</td><td class=\"n\">$img_info[0]x$img_info[1]</td><td class=\"n\">" . formatSize($this_size) . "</td></tr>\n";
	}
}
echo "<tr><td></td><td></td><td></td><td class=\"n\">Total Size</td><td class=\"n\">" . formatSize($total_size) . "</td></tr>";
?>
</table>
</body>
</html>