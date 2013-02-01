<?php
// TODO
// Move this file out of inc/ and possibly rename it.

session_start();

define('URL_PREFIX', '../');
define('GALLERY_PREFIX', '../gallery/');

require URL_PREFIX.'fcms.php';

load('image');

setLanguage();
isLoggedIn('inc/');

// Globals
$img = new Image($fcmsUser->id);

control();
exit();

/**
 * control 
 * 
 * The controlling structure for this script.
 * 
 * @return void
 */
function control ()
{
    if (isset($_POST['delimg']))
    {
        displayDeleteSubmit();
    }
    elseif (isset($_POST['upload']))
    {
        displayUploadSubmit();
    }
    else
    {
        displayImages();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.T_('lang').'" lang="'.T_('lang').'">
<head>
<title>'.getSiteName().' - '.T_('powered by').' '.getCurrentVersion().'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="'.URL_PREFIX.'ui/themes/default/style.css"/>
<link rel="shortcut icon" href="'.URL_PREFIX.'ui/themes/favicon.ico"/>';
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
.delbtn, .viewbtn { padding: 0 }
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
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    echo '
</body>
</html>';
}

/**
 * displayDeleteSubmit 
 * 
 * @return void
 */
function displayDeleteSubmit ()
{
    global $fcmsUser;

    if ($fcmsUser->access >= 2)
    {
        displayHeader();
        echo '<p class="error-alert">'.T_('You do not have access to delete this image.').'</p>';
        displayFooter();
        return;
    }

    $uploadsPath = getUploadsAbsolutePath();

    unlink($uploadsPath.'upimages/'.basename($_POST['img']));

    $_SESSION['delete_ok'] = 1;

    header('Location: upimages.php');
}

/**
 * displayUploadSubmit 
 * 
 * @return void
 */
function displayUploadSubmit ()
{
    global $img;

    displayHeader();

    $uploadsPath      = getUploadsAbsolutePath();
    $img->destination = $uploadsPath.'upimages/';

    $img->upload($_FILES['upfile']);

    if ($img->error == 1)
    {
        echo '
    <p class="error-alert">
        '.sprintf(T_('Photo [%s] is not a supported photo type.  Photos must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $img->name).'
    </p>';

        displayFooter();
        return;
    }

    $img->resize(600, 400);

    if ($img->error > 0)
    {
        echo '
    <p class="error-alert">
        '.T_('There was an error uploading your image.').'
    </p>';

        displayFooter();
        return;
    }

    $path = 'uploads/upimages/';

    if (defined('UPLOADS'))
    {
        $path = 'file.php?u=';
    }

    echo '
    <p>
        <b>'.T_('Click to insert image into message.').'</b><br/>
        <a href="#" onclick="insertUpImage(\'[IMG='.$path.$img->name.']\')" 
            title="'.T_('Click to insert image into message.').'"><img src="'.URL_PREFIX.$path.$img->name.'"/></a>
    </p>';

    displayfooter();
}

/**
 * displayImages 
 * 
 * @return void
 */
function displayImages ()
{
    global $fcmsUser;

    displayHeader();

    if (isset($_SESSION['delete_ok']))
    {
        unset($_SESSION['delete_ok']);

        echo '<p class="ok-alert">'.T_('Image was Deleted Successfully').'</p>';
    }

    echo '
    <h2>'.T_('Upload Image').'</h2>
    <form enctype="multipart/form-data" action="upimages.php" method="post">
        <p><input type="file" name="upfile" id="upfile" size="30" title="'.T_('Choose the image you want to upload.').'"/></p>
        <div><input type="submit" name="upload" id="upload" value="'.T_('Upload Image').'"/></div>
    </form>
    <p>&nbsp;</p>
    <h2>'.T_('Uploaded Images').'</h2>
    <table>';

    $uploadsPath = getUploadsAbsolutePath();
    $img_dir     = opendir($uploadsPath.'upimages');

    while ($file = readdir($img_dir))
    {
        if ($file !== 'index.htm')
        {
            $images_in_dir[] = $file;
        }
    }

    natcasesort($images_in_dir);
    reset($images_in_dir);

    $i = 0;
    $total_size = 0;

    foreach ($images_in_dir as $file)
    {
        // Skip directories that start with a period
        if ($file[0] === '.')
        {
            continue;
        }

        $img_name_arr = explode(".", $file);
        $img_type     = end($img_name_arr);

        $this_size   = filesize($uploadsPath.'upimages/'.$file);
        $total_size += $this_size;
        $img_info    = getimagesize($uploadsPath.'upimages/'.$file);

        $win_w = $img_info[0] + 50;
        $win_h = $img_info[1] + 50;

        $path = 'uploads/upimages/';

        if (defined('UPLOADS'))
        {
            $path = 'file.php?u=';
        }

        $i++;

        echo '
        <tr'; if ($i % 2 != 0) { echo 'class="alt"'; } echo '>
            <td class="v">
                <button class="viewbtn" onclick="window.open(\''.URL_PREFIX.$path.basename($file).'\',\'file\',
                \'width='.$win_w.',height='.$win_h.',resizable=no,location=no,menubar=no,status=no\'); return false;"/>
            </td>
            <td class="file">
                <a href="#" onclick="insertUpImage(\'[IMG='.$path.basename($file).']\')" 
                    title="'.T_('Click to insert image into message.').'">'.$file.'</a>
            </td>
            <td>';

        if ($fcmsUser->access < 2)
        {
            echo '
                <form method="post" action="upimages.php">
                    <div>
                        <input type="hidden" name="img" value="'.cleanOutput($file).'"/>
                        <input type="submit" name="delimg" value="'.T_('Delete').'" class="delbtn" title="'.T_('Delete this Image').'" 
                            onclick="javascript:return confirm(\''.T_('Are you sure you want to DELETE this image?').'\');"/>
                    </div>
                </form>';
        }
        echo '
            </td>
            <td class="n">'.$img_info[0].'x'.$img_info[1].'</td>
            <td class="n">'.formatSize($this_size).'</td>
        </tr>';
    }

    echo '
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td class="n">'.T_('Total Size').'</td>
            <td class="n">'.formatSize($total_size).'</td>
        </tr>
    </table>';

    displayFooter();
}
