<?php
/**
 * Family Tree
 * 
 * PHP versions 4 and 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2010 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.3
 */
session_start();

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('familytree', 'image');

init();

$ftree = new FamilyTree($fcmsUser->id);
$img   = new Image($fcmsUser->id);

// Setup the Template variables;
$TMPL = array(
    'currentUserId' => $fcmsUser->id,
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Family Tree'),
    'path'          => URL_PREFIX,
    'displayname'   => $fcmsUser->displayName,
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript" src="ui/js/livevalidation.js"></script>
<link rel="stylesheet" type="text/css" href="ui/datechooser.css"/>
<script type="text/javascript" src="ui/js/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    var bday = new DateChooser();
    bday.setUpdateField({\'bday\':\'j\', \'bmonth\':\'n\', \'byear\':\'Y\'});
    bday.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'byear\');
    var dday = new DateChooser();
    dday.setUpdateField({\'dday\':\'j\', \'dmonth\':\'n\', \'dyear\':\'Y\'});
    dday.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'dyear\');
    initLivingDeceased();
});
//]]>
</script>';

// Changing Avatar with Advanced Uploader
if (isset($_GET['advanced_avatar']))
{
    $userid = (int)$_GET['advanced_avatar'];

    $filetypes = array(
        'image/pjpeg'   => 'jpg', 
        'image/jpeg'    => 'jpg', 
        'image/gif'     => 'gif', 
        'image/bmp'     => 'bmp', 
        'image/x-png'   => 'png', 
        'image/png'     => 'png'
    );

    $type        = $_FILES['avatar']['type'];
    $extention   = $filetypes[$type];
    $id          = uniqid("");
    $name        = $id.".".$extention;
    $uploadsPath = getUploadsAbsolutePath();

    $sql = "UPDATE `fcms_users`
            SET `avatar` = '".$name."'
            WHERE `id` = '$userid'";
    if (!mysql_query($sql))
    {
        echo "FAILURE: Could not update db with new avatar.\n";
        exit();
    }

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadsPath.'avatar/'.$name))
    {
        echo "success";
    }
    else
    {
        logError(__FILE__.' ['.__LINE__.'] Could not move avatar file.');
        echo "FAILURE: Could not move avatar file.\n";
    }

    if ($_GET['orig'] != 'no_avatar.jpg' && $_GET['orig'] != 'gravatar')
    {
        if (file_exists($uploadsPath.'avatar/'.basename($_GET['orig'])))
        {
            unlink($uploadsPath.'avatar/'.basename($_GET['orig']));
        }
    }

    exit();
}
// Show Header
require_once getTheme($fcmsUser->id).'header.php';

$show_tree = true;

// Set the user's tree we are currently viewing
if (isset($_GET['tree']))
{
    $_SESSION['view_tree_user'] = (int)$_GET['tree'];
}
elseif (!isset($_SESSION['view_tree_user']))
{
    $_SESSION['view_tree_user'] = $fcmsUser->id;
}

echo '
        <div id="familytree-page" class="centercontent">';

//-------------------------------------
// Add Relationship
//-------------------------------------
if (isset($_POST['add-leaf']))
{
    $user         = (int)$_POST['user'];
    $relationship = $_POST['relationship'];
    $rel_user     = (int)$_POST['rel_user'];

    // Spouse
    if ($relationship == 'WIFE' || $relationship == 'HUSB')
    {
        $worked = $ftree->addSpouse($user, $relationship, $rel_user);
    }
 
    // Child
    if ($relationship == 'CHIL')
    {
        $worked = $ftree->addChild($user, $relationship, $rel_user);
    }

    if ($worked === false)
    {
        // error has already been displayed
        return;
    }

    displayOkMessage();
}

//-------------------------------------
// Add new user
//-------------------------------------
if (isset($_POST['add-user']))
{
    $type = $_POST['type'];
    $id   = (int)$_POST['id'];

    // Missing req field
    if (!isset($_POST['fname']) or !isset($_POST['lname']) or !isset($_POST['sex']))
    {
        echo '
        <p class="error-alert">'.T_('Missing Required Field').'</p>';

        $ftree->displayCreateUserForm($type, $id);
        return;
    }

    $uniq = uniqid("");

    // birthday
    $bYear  = '';
    $bMonth = '';
    $bDay   = '';

    if (!empty($_POST['byear']))
    {
        $bYear = (int)$_POST['byear'];
    }
    if (!empty($_POST['bmonth']))
    {
        $bMonth = (int)$_POST['bmonth'];
        $bMonth = str_pad($bMonth, 2, "0", STR_PAD_LEFT);
    }
    if (!empty($_POST['bday']))
    {
        $bDay = (int)$_POST['bday'];
        $bDay = str_pad($bDay, 2, "0", STR_PAD_LEFT);
    }

    // death
    $dYear  = '';
    $dMonth = '';
    $dDay   = '';

    if (!empty($_POST['dyear']))
    {
        $dYear = (int)$_POST['dyear'];
    }
    if (!empty($_POST['dmonth']))
    {
        $dMonth = (int)$_POST['dmonth'];
        $dMonth = str_pad($dMonth, 2, "0", STR_PAD_LEFT);
    }
    if (!empty($_POST['dday']))
    {
        $dDay = (int)$_POST['dday'];
        $dDay = str_pad($dDay, 2, "0", STR_PAD_LEFT);
    }

    $fname = strip_tags($_POST['fname']);
    $mname = strip_tags($_POST['mname']);
    $lname = strip_tags($_POST['lname']);

    $fname = escape_string($fname);
    $mname = escape_string($mname);
    $lname = escape_string($lname);
    $sex   = escape_string($_POST['sex']);

    $maiden = isset($_POST['maiden']) ? "'".escape_string($_POST['maiden'])."'" : 'NULL';

    // Insert new user
    $sql = "INSERT INTO `fcms_users`(
                `access`, `joindate`, `fname`, `mname`, `lname`, `maiden`, `sex`, `dob_year`, `dob_month`, `dob_day`,
                `dod_year`, `dod_month`, `dod_day`, `username`, `password`, `activated`
            ) VALUES (
                10, 
                NOW(), 
                '$fname', 
                '$mname', 
                '$lname', 
                $maiden,
                '$sex', 
                '$bYear', 
                '$bMonth',
                '$bDay', 
                '$dYear', 
                '$dMonth',
                '$dDay', 
                'NONMEMBER-$uniq', 
                'NONMEMBER', 
                1
            )";

    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        return;
    }

    $lastid = mysql_insert_id();

    // Create empty address
    $sql = "INSERT INTO `fcms_address`(`user`, `created_id`, `created`, `updated_id`, `updated`) 
            VALUES ('$lastid', '$fcmsUser->id', NOW(), '$fcmsUser->id', NOW())";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        // ok to continue
    }

    // Create empty settings
    $sql = "INSERT INTO `fcms_user_settings`(`user`) VALUES ('$lastid')";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        // ok to continue
    }

    // Add this new user as a relationship
    if ($type == 'wife' || $type == 'husb')
    {
        $rel    = strtoupper($type);
        $worked = $ftree->addSpouse($lastid, $rel, $id);
    }
    if ($type == 'mom' || $type == 'dad')
    {
        $worked = $ftree->addChild($lastid, 'CHIL', $id);
    }
    if ($type == 'child')
    {
        $worked = $ftree->addChild($id, 'CHIL', $lastid);
    }

    if ($worked === false)
    {
        // error has already been displayed
        return;
    }

    displayOkMessage();
}

//-------------------------------------
// Edit user
//-------------------------------------
if (isset($_POST['edit-user']))
{
    // birthday
    $bYear  = '';
    $bMonth = '';
    $bDay   = '';

    if (!empty($_POST['byear']))
    {
        $bYear = (int)$_POST['byear'];
    }
    if (!empty($_POST['month']))
    {
        $bMonth = (int)$_POST['bmonth'];
        $bMonth = str_pad($bMonth, 2, "0", STR_PAD_LEFT);
    }
    if (!empty($_POST['bday']))
    {
        $bDay = (int)$_POST['bday'];
        $bDay = str_pad($bDay, 2, "0", STR_PAD_LEFT);
    }

    // death
    $dYear  = '';
    $dMonth = '';
    $dDay   = '';

    if (!empty($_POST['dyear']))
    {
        $dYear = (int)$_POST['dyear'];
    }
    if (!empty($_POST['dmonth']))
    {
        $dMonth = (int)$_POST['dmonth'];
        $dMonth = str_pad($dMonth, 2, "0", STR_PAD_LEFT);
    }
    if (!empty($_POST['dday']))
    {
        $dDay = (int)$_POST['dday'];
        $dDay = str_pad($dDay, 2, "0", STR_PAD_LEFT);
    }

    $fname = strip_tags($_POST['fname']);
    $mname = strip_tags($_POST['mname']);
    $lname = strip_tags($_POST['lname']);

    $fname = escape_string($fname);
    $mname = escape_string($mname);
    $lname = escape_string($lname);
    $sex   = escape_string($_POST['sex']);

    $maiden = isset($_POST['maiden']) 
            ? "`maiden` = '".(int)$_POST['maiden']."',"
            : '';

    $sql = "UPDATE `fcms_users`
            SET `fname`     = '$fname',
                `mname`     = '$mname', 
                `lname`     = '$lname', 
                $maiden
                `dob_year`  = '$bYear', 
                `dob_month` = '$bMonth',
                `dob_day`   = '$bDay',
                `dod_year`  = '$dYear', 
                `dod_month` = '$dMonth',
                `dod_day`   = '$dDay',
                `sex`       = '$sex'
            WHERE `id` = '".(int)$_POST['id']."'";

    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        return;
    }

    displayOkMessage();
}

//-------------------------------------
// Display add relationship form
//-------------------------------------
if (isset($_GET['add']) and isset($_GET['user']))
{
    $show_tree = false;

    $add  = $_GET['add'];
    $user = (int)$_GET['user'];

    if ($add == 'child')
    {
        $ftree->displayAddChildForm($user);
    }
    elseif ($add == 'wife' || $add == 'husb')
    {
        $ftree->displayAddSpouseForm($add, $user);
    }
    else
    {
        $ftree->displayAddParentForm($add, $user);
    }
}

//-------------------------------------
// Display add new user form
//-------------------------------------
if (isset($_GET['create']) and isset($_GET['type']) and isset($_GET['id']))
{
    $valid_types = array('dad', 'mom', 'child', 'wife', 'husb');

    $type = $_GET['type'];
    $id   = (int)$_GET['id'];

    if (in_array($type, $valid_types))
    {
        $show_tree = false;
        $ftree->displayCreateUserForm($type, $id);
    }
}

//-------------------------------------
// Display edit user form
//-------------------------------------
if (isset($_GET['edit']))
{
    $show_tree = false;

    $id = (int)$_GET['edit'];
    $ftree->displayEditForm($id);
}

//-------------------------------------
// Avatar non-advanced
//-------------------------------------
if (isset($_GET['avatar']))
{
    //-------------------------------------
    // Submit
    //-------------------------------------
    if (isset($_POST['submitUpload']))
    {
        $uploadsPath       = getUploadsAbsolutePath();
        $img->destination  = $uploadsPath.'avatar/';
        $img->resizeSquare = true;
        $img->uniqueName   = true;

        $img->upload($_FILES['avatar']);

        if ($img->error == 1)
        {
            echo '
            <p class="error-alert">
                '.sprintf(T_('Photo [%s] is not a supported photo type.  Photos must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $img->name).'
            </p>';

            displayFooter();
            die();
        }

        $img->resize(80, 80);

        if ($img->error > 0)
        {
            echo '
            <p class="error-alert">
                '.T_('There was an error uploading your avatar.').'
            </p>';

            displayFooter();
            die();
        }

        $sql = "UPDATE `fcms_users`
                SET `avatar` = '".$img->name."'
                WHERE `id` = '".(int)$_GET['avatar']."'";
        if (!mysql_query($sql))
        {
            displaySqlError($sql, mysql_error());
            displayFooter();
            die();
        }

        if ($_POST['avatar_orig'] != 'no_avatar.jpg' && $_POST['avatar_orig'] != 'gravatar')
        {
            unlink($uploadsPath.'avatar/'.basename($_POST['avatar_orig']));
        }
    }
    //-------------------------------------
    // Display form
    //-------------------------------------
    else
    {
        $show_tree = false;

        $id = (int)$_GET['avatar'];
        $ftree->displayEditAvatarForm($id);
    }
}

//-------------------------------------
// Remove relationships
//-------------------------------------
if (isset($_GET['remove']))
{
    // Show form
    if ($_GET['remove'] == 'user')
    {
        $show_tree = false;

        $id = (int)$_GET['id'];
        $ftree->displayFamilyTree($id, 'list_edit');
    }
    // Remove from db
    else
    {
        $id = (int)$_GET['remove'];

        $sql = "DELETE FROM `fcms_relationship`
                WHERE `user` = '$id'
                OR `rel_user` = '$id'";
        if (!mysql_query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        displayOkMessage();
    }
}

//-------------------------------------
// Show family tree
//-------------------------------------
if ($show_tree)
{
    $ftree->displayFamilyTree($_SESSION['view_tree_user']);
}

echo '
        </div><!-- #familytree-page .centercontent -->';

// Show Footer
require_once getTheme($fcmsUser->id).'footer.php';
