<?php
/**
 * Uploads File viewer
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
if (!isset($_GET['a']) && !isset($_GET['u']))
{
    logError(__FILE__.' ['.__LINE__.'] No parameters given.');
    return;
}

session_start();

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require URL_PREFIX.'fcms.php';

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
    if (isset($_GET['a']))
    {
        printAvatar();
    }
    elseif (isset($_GET['u']))
    {
        printUpImage();
    }
}

/**
 * printAvatar 
 * 
 * @return void
 */
function printAvatar ()
{
    $filename = $_GET['a'];
    $filename = basename($filename);
    $path     = getUploadsAbsolutePath().'avatar/'.$filename;

    // Make sure photo file exists
    if (!file_exists($path) || !is_file($path))
    {
        logError(__FILE__.' ['.__LINE__.'] No avatar found in directory ['.$path.'] for avatar ['.$filename.'].');
        header('HTTP/1.0 404 Not Found');
        return;
    }

    $info = getimagesize($path);

    header("Cache-control: public, no-cache;");
    header("Content-type: ".$info['mime']); 

    readfile($path); 
    exit();
}

/**
 * printUpImage 
 * 
 * @return void
 */
function printUpImage ()
{
    $filename = $_GET['u'];
    $filename = basename($filename);
    $path     = getUploadsAbsolutePath().'upimages/'.$filename;

    // Make sure photo file exists
    if (!file_exists($path) || !is_file($path))
    {
        logError(__FILE__.' ['.__LINE__.'] No image ['.$filename.'] found in directory ['.$path.'].');
        header('HTTP/1.0 404 Not Found');
        return;
    }

    $info = getimagesize($path);

    header("Cache-control: public, no-cache;");
    header("Content-type: ".$info['mime']); 

    readfile($path); 
    exit();
}
