<?php
/**
 * Photo Gallery - photo viewer
 * 
 * PHP versions 4 and 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2012 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     3.0
 */
if (!isset($_GET['id']))
{
    logError(__FILE__.' ['.__LINE__.'] No photo id given.');
    return;
}

session_start();

define('URL_PREFIX', '../');
define('GALLERY_PREFIX', '');

require URL_PREFIX.'fcms.php';

load('gallery');

// Globals
$galleryObj = new PhotoGallery($fcmsUser->id);

$id   = (int)$_GET['id'];
$size = 'thumbnail';

if (isset($_GET['size']))
{
    if ($_GET['size'] == 'medium')
    {
        $size = 'medium';
    }
    elseif ($_GET['size'] == 'full')
    {
        $size = 'full';
    }
}

// Get photo data
// Session
if (isset($_SESSION['photo-path-data'][$id]))
{
    $photo = $_SESSION['photo-path-data'][$id];
}
// Database
else
{
    $sql = "SELECT p.`id`, p.`user`, p.`filename`, p.`external_id`, e.`thumbnail`, e.`medium`, e.`full`
            FROM `fcms_gallery_photos` AS p
            LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
            WHERE p.`id` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        logError(__FILE__.' ['.__LINE__.'] Could not get photo from db.');
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        logError(__FILE__.' ['.__LINE__.'] No photo found in db for id ['.$id.'].');
        return;
    }

    $photo = mysql_fetch_assoc($result);
}

// Get photo path
// External
if ($photo['filename'] == 'noimage.gif' && $photo['external_id'] != null)
{
    $path = $photo[$size]; 

    if (!fopen($path, 'r'))
    {
        logError(__FILE__.' ['.__LINE__.'] No photo found remotely ['.$path.'] for photo id ['.$id.'].');
        header('HTTP/1.0 404 Not Found');
        return;
    }
}
// Local
else
{
    $prefix = '';

    if ($size == 'thumbnail')
    {
        $prefix = 'tb_';
    }
    elseif ($size == 'full' && $galleryObj->usingFullSizePhotos())
    {
        $prefix = 'full_';
    }

    $filename = basename($photo['filename']);
    $userId   = (int)$photo['user'];
    $path     = getUploadsAbsolutePath().'photos/member'.$userId.'/'.$prefix.$filename;

    // Make sure photo file exists
    if (!file_exists($path) || !is_file($path))
    {
        logError(__FILE__.' ['.__LINE__.'] No photo found in directory ['.$path.'] for photo id ['.$id.'].');
        header('HTTP/1.0 404 Not Found');
        return;
    }
}

$info = getimagesize($path);

header("Cache-control: public, no-cache;");
header("Content-type: ".$info['mime']); 

readfile($path); 
exit();
