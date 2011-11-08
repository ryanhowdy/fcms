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

require 'fcms.php';

load('familytree');

init();

$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$ftree         = new FamilyTree($currentUserId);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Family Tree'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript" src="inc/js/livevalidation.js"></script>
<link rel="stylesheet" type="text/css" href="themes/datechooser.css"/>
<script type="text/javascript" src="inc/js/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    var bday = new DateChooser();
    bday.setUpdateField({\'bday\':\'j\', \'bmonth\':\'n\', \'byear\':\'Y\'});
    bday.setIcon(\'themes/default/images/datepicker.jpg\', \'byear\');
    var dday = new DateChooser();
    dday.setUpdateField({\'dday\':\'j\', \'dmonth\':\'n\', \'dyear\':\'Y\'});
    dday.setIcon(\'themes/default/images/datepicker.jpg\', \'dyear\');
    initLivingDeceased();
});
//]]>
</script>';

// Show Header
require_once getTheme($currentUserId).'header.php';

$show_tree = true;

// Set the user's tree we are currently viewing
if (isset($_GET['tree']))
{
    $_SESSION['view_tree_user'] = cleanInput($_GET['tree'], 'int');
}
elseif (!isset($_SESSION['view_tree_user']))
{
    $_SESSION['view_tree_user'] = $currentUserId;
}

echo '
        <div id="familytree-page" class="centercontent clearfix">';

//-------------------------------------
// Add Relationship
//-------------------------------------
if (isset($_POST['add-leaf']))
{
    $user         = cleanInput($_POST['user'], 'int');
    $relationship = cleanInput($_POST['relationship']);
    $rel_user     = cleanInput($_POST['rel_user'], 'int');

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
    $type = cleanOutput($_POST['type']);
    $id   = cleanOutput($_POST['id']);

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
        $bYear = cleanInput($_POST['byear'], 'int');
    }
    if (!empty($_POST['month']))
    {
        $bMonth = cleanInput($_POST['bmonth'], 'int');
        $bMonth = str_pad($bMonth, 2, "0", STR_PAD_LEFT);
    }
    if (!empty($_POST['bday']))
    {
        $bDay = cleanInput($_POST['bday'], 'int');
        $bDay = str_pad($bDay, 2, "0", STR_PAD_LEFT);
    }

    // death
    $dYear  = '';
    $dMonth = '';
    $dDay   = '';

    if (!empty($_POST['dyear']))
    {
        $dYear = cleanInput($_POST['dyear'], 'int');
    }
    if (!empty($_POST['month']))
    {
        $dMonth = cleanInput($_POST['dmonth'], 'int');
        $dMonth = str_pad($dMonth, 2, "0", STR_PAD_LEFT);
    }
    if (!empty($_POST['dday']))
    {
        $dDay = cleanInput($_POST['dday'], 'int');
        $dDay = str_pad($dDay, 2, "0", STR_PAD_LEFT);
    }

    $maiden = isset($_POST['maiden']) ? "'".cleanInput($_POST['maiden'])."'" : 'NULL';

    // Insert new user
    $sql = "INSERT INTO `fcms_users`(
                `access`, `joindate`, `fname`, `mname`, `lname`, `maiden`, `sex`, `dob_year`, `dob_month`, `dob_day`,
                `dod_year`, `dod_month`, `dod_day`, `death`, `username`, `password`, `activated`
            ) VALUES (
                10, 
                NOW(), 
                '".cleanInput($_POST['fname'])."', 
                '".cleanInput($_POST['mname'])."', 
                '".cleanInput($_POST['lname'])."', 
                $maiden,
                '".cleanInput($_POST['sex'])."', 
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
        displaySQLError('Non-Member Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }

    $lastid = mysql_insert_id();

    // Create empty address
    $sql = "INSERT INTO `fcms_address`(`user`, `created_id`, `created`, `updated_id`, `updated`) 
            VALUES ('$lastid', '$currentUserId', NOW(), '$currentUserId', NOW())";
    if (!mysql_query($sql))
    {
        displaySQLError('Address Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        // ok to continue
    }

    // Create empty settings
    $sql = "INSERT INTO `fcms_user_settings`(`user`) VALUES ('$lastid')";
    if (!mysql_query($sql))
    {
        displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
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
        $bYear = cleanInput($_POST['byear'], 'int');
    }
    if (!empty($_POST['month']))
    {
        $bMonth = cleanInput($_POST['bmonth'], 'int');
        $bMonth = str_pad($bMonth, 2, "0", STR_PAD_LEFT);
    }
    if (!empty($_POST['bday']))
    {
        $bDay = cleanInput($_POST['bday'], 'int');
        $bDay = str_pad($bDay, 2, "0", STR_PAD_LEFT);
    }

    // death
    $dYear  = '';
    $dMonth = '';
    $dDay   = '';

    if (!empty($_POST['dyear']))
    {
        $dYear = cleanInput($_POST['dyear'], 'int');
    }
    if (!empty($_POST['month']))
    {
        $dMonth = cleanInput($_POST['dmonth'], 'int');
        $dMonth = str_pad($dMonth, 2, "0", STR_PAD_LEFT);
    }
    if (!empty($_POST['dday']))
    {
        $dDay = cleanInput($_POST['dday'], 'int');
        $dDay = str_pad($dDay, 2, "0", STR_PAD_LEFT);
    }

    $maiden = isset($_POST['maiden']) ? "`maiden`   = '".cleanInput($_POST['maiden'])."'," : '';

    $sql = "UPDATE `fcms_users`
            SET `fname`     = '".cleanInput($_POST['fname'])."',
                `mname`     = '".cleanInput($_POST['mname'])."', 
                `lname`     = '".cleanInput($_POST['lname'])."', 
                $maiden
                `dob_year`  = '$bYear', 
                `dob_month` = '$bMonth',
                `dob_day`   = '$bDay',
                `dod_year`  = '$dYear', 
                `dod_month` = '$dMonth',
                `dod_day`   = '$dDay',
                `sex`       = '".cleanInput($_POST['sex'])."'
            WHERE `id` = '".cleanInput($_POST['id'])."'";

    if (!mysql_query($sql))
    {
        displaySQLError('Non-Member Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
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

    $add  = cleanInput($_GET['add']);
    $user = cleanInput($_GET['user'], 'int');

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

    $type = cleanInput($_GET['type']);
    $id   = cleanInput($_GET['id'], 'int');

    if (in_array($type, $valid_types))
    {
        $show_tree = false;
        $ftree->displayCreateUserForm($type, $id);
    }
}

//-------------------------------------
// Display add new user form
//-------------------------------------
if (isset($_GET['edit']))
{
    $show_tree = false;

    $id = cleanInput($_GET['edit'], 'int');
    $ftree->displayEditForm($id);
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

        $id = cleanInput($_GET['id'], 'int');
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
            displaySQLError('Remove Relationship Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
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
require_once getTheme($currentUserId).'footer.php';
