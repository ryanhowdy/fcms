<?php
/**
 * Slideshow
 * 
 * PHP versions 4 and 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '../');
define('GALLERY_PREFIX', '');

require URL_PREFIX.'fcms.php';

load('gallery');

init('gallery/');

// Globals
$fcmsGallery = new PhotoGallery($fcmsError, $fcmsDatabase, $fcmsUser);

echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.T_pgettext('Language Code for this translation', 'lang').'" lang="'.T_pgettext('Language Code for this translation', 'lang').'">
<head>
<title>'.T_('Photo Gallery Slideshow').' - '.getSiteName().'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="author" content="Ryan Haudenschilt" />
<link rel="shortcut icon" href="../ui/favicon.ico"/>
<style type="text/css">
html { background-color: #000; }
body { overflow: hidden; text-align: center; }
ul { margin: 0 auto; padding: 0; }
ul > li { list-style-type: none; }
</style>
<script type="text/javascript" src="../ui/js/jquery.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    $("#slideshow > li").hide();
    setInterval(function() {
        $("#slideshow > li:first")
            // Hide the first photo
            .fadeOut(1000)
            // Get next photo and fade in
            .next().fadeIn(1000)
            // Put this photo at the end of the list
            .end().appendTo("#slideshow")
    }, 6000);
});
</script>
</head>
<body>';

if (!isset($_GET['category']))
{
    echo '
    <div class="error-alert">
        <h3>'.T_('Invalid Category ID').'</h3>
        <p>'.T_('You must supply a valid category id.').'</p>
    </div>
</body>
</html>';
    exit();
}

$cid = (int)$_GET['category'];

$sql = "SELECT p.`id`, p.`caption`, p.`filename`, p.`user`, p.`external_id`, e.`medium`
        FROM `fcms_gallery_photos` AS p
        LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
        WHERE `category` = ?";

$rows = $fcmsDatabase->getRows($sql, $cid);
if ($rows === false)
{
    $fcmsError->displayError();
    echo '</body></html>';
    die();
}

if (count($rows) > 0)
{
    echo '
    <ul id="slideshow">';

    $i = 0;
    foreach ($rows as $r)
    {
        $i++;

        $user     = (int)$r['user'];
        $filename = basename($r['filename']);
        $caption  = cleanOutput($r['caption']);

        $photoSrc = $fcmsGallery->getPhotoSource($r, 'medium');

        echo '
        <li>
            <img src="'.$photoSrc.'" alt="'.$caption.'" title="'.$caption.'"/>
        </li>';

    }
    echo '
    </ul>';
}
else
{
    echo '
    <p class="info-alert">'.T_('No photos found.').'</p>';
}

echo '
</body>
</html>';
