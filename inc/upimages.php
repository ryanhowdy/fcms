<?php
session_start();
include_once('config_inc.php');
include_once('util_inc.php');
include_once('locale.php');

// Check that the user is logged in
isLoggedIn();
$current_user_id = (int)escape_string($_SESSION['login_id']);

echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'._('lang').'" lang="'._('lang').'">
<head>
<title>'.getSiteName().' - '._('powered by').' '.getCurrentVersion().'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="'.getTheme($current_user_id, "../").'style.css"/>
<link rel="shortcut icon" href="../themes/favicon.ico"/>';
// TODO
// Move css to fcms-core
echo '
<style type="text/css">
html { background: #fff; }
body { width: 600px; margin: 0; padding: 20px; text-align: left; font-family: Verdana, Tahoma, Arial, sans-serif; font-size: 11px; border: none; background: #fff; }
form div, form { display: inline; }
td.n { padding: 2px; text-align: right; width: 75px; }
td.v { text-align: center; width: 30px; }
td.file { padding: 0 5px;  text-align: left; width: 150px; }
tr.alt { background-color: #f4f4f4; }
</style>
<script type="text/javascript">
//<![CDATA[
function insertUpImage(str) {
    var textarea = window.opener.document.getElementById(\'post\');
    if (textarea) {
        if (textarea.value == "message") { textarea.value = str + " "; } else { textarea.value += str + " "; }
        textarea.focus();
    }
    javascript:window.close();
    return true;
}
//]]>
</script>
</head>
<body>';
if (isset($_POST['delimg'])) {
    if (checkAccess($current_user_id) < 2) {
        unlink("../gallery/upimages/" . $_POST['img']);
        echo "<p class=\"ok-alert\">".sprintf(_('%s was Deleted Successfully'), $_POST['img'])."</p>";
    } else {
        echo "<p class=\"error-alert\">"._('You do not have access to delete this image.')."</p>";
    }
}
if (isset($_POST['upload'])) {
    if ($_FILES['upfile']['name']) { uploadImages($_FILES['upfile']['type'], $_FILES['upfile']['name'], $_FILES['upfile']['tmp_name'], "../gallery/upimages/", 600, 400); }
} else {
    echo '
    <h2>'._('Upload Image').'</h2>
    <form enctype="multipart/form-data" action="upimages.php" method="post">
        <p><input type="file" name="upfile" id="upfile" size="30" title="'._('Choose the image you want to upload.').'"/></p>
        <div><input type="submit" name="upload" id="upload" value="'._('Upload Image').'"/></div>
    </form>';
}

echo '
    <p>&nbsp;</p>
    <h2>'._('Uploaded Images').'</h2>
    <table>';

$img_dir = opendir("../gallery/upimages");
while ($file = readdir($img_dir)) {
    if ($file !== 'index.htm') {
        $images_in_dir[] = $file;
    }
}
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
        if ($i % 2 != 0) {
            echo '
        <tr class="alt">';
        } else {
            echo '
        <tr>';
        }
        echo '
            <td class="v"><button class="viewbtn" onclick="window.open(\'../gallery/upimages/'.$file.'\',\'file\',
                \'width='.$win_w.',height='.$win_h.',resizable=no,location=no,menubar=no,status=no\'); return false;"/></td>
            <td class="file"><a href="#" onclick="insertUpImage(\'[IMG=gallery/upimages/'.$file.']\')" title="'._('Insert Image into Message').'">'.$file.'</a></td>
            <td>';
        if (checkAccess($current_user_id) < 2) {
            echo '
                <form method="post" action="upimages.php">
                    <div>
                        <input type="hidden" name="img" value="'.$file.'"/>
                        <input type="submit" name="delimg" value="'._('Delete').'" class="delbtn" title="'._('Delete this Image').'" 
                            onclick="javascript:return confirm(\''._('Are you sure you want to DELETE this image?').'\');"/>
                    </div>
                </form>';
        }
        echo '
            </td>
            <td class="n">'.$img_info[0].'x'.$img_info[1].'</td>
            <td class="n">'.formatSize($this_size).'</td>
        </tr>';
    }
}
echo '
        <tr><td></td><td></td><td></td><td class="n">'._('Total Size').'</td><td class="n">'.formatSize($total_size).'</td></tr>
    </table>
</body>
</html>';