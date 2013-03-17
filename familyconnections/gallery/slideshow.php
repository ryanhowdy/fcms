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
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.T_('lang').'" lang="'.T_('lang').'">
<head>
<title>'.T_('Photo Gallery Slideshow').' - '.getSiteName().'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="author" content="Ryan Haudenschilt" />
<link rel="shortcut icon" href="../ui/favicon.ico"/>
<link rel="stylesheet" type="text/css" href="../themes/default/style.css"/>
<style type="text/css">
html { background-image: none !important; background-color: #000; }
body { background-color: #000; margin: 10px 0 0 0 !important; padding: 0 !important; width: 650px !important; text-align: center; }
</style>
<script type="text/javascript" src="../ui/js/prototype.js"></script>
<script type="text/javascript" src="../ui/js/effects.js"></script>
<script type="text/javascript">
//<![CDATA[
function slideshow(start,last,interval) {
    var frame = start;
    var nextframe = start+1;

    Effect.Appear(\'img1\',{duration:.5,from:0.0,to:1.0});
    setInterval(function() {
        Effect.Fade(\'img\'+frame,{duration:.5,from:1.0,to:0.0,afterFinish:function() {
            $(\'img\'+frame).hide();
            Effect.Appear(\'img\'+nextframe,{duration:.5,from:0.0,to:1.0});
            frame = nextframe;
            nextframe = (frame == last) ? start : nextframe+1;
        }});
    }, interval);

    return;
};
//]]>
</script>
</head>
<body>
<div class="fadein">';

if (!isset($_GET['category']))
{
    echo '
    <div class="error-alert">
        <h3>'.T_('Invalid Category ID').'</h3>
        <p>'.T_('You must supply a valid category id.').'</p>
    </div>
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
    echo '
</div>
</body>
</html>';
    die();
}

if (count($rows) > 0)
{
    $i = 0;
    foreach ($rows as $r)
    {
        $i++;

        $user     = (int)$r['user'];
        $filename = basename($r['filename']);
        $caption  = cleanOutput($r['caption']);

        $photoSrc = $fcmsGallery->getPhotoSource($r, 'medium');

        echo '
    <div id="img'.$i.'" style="display:none; color:#fff;">
        <img src="'.$photoSrc.'" alt="'.$caption.'"/><br/>
        '.$caption.'
    </div>';

    }

    echo '
    <script type="text/javascript">slideshow(1,'.$i.',5000);</script>';
}
else
{
    echo '
    <p class="info-alert">'.T_('No photos found.').'</p>';
}

echo '
</div>
</body>
</html>';
