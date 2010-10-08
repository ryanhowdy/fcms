<?php
/**
 * Family Tree
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

require_once 'inc/config_inc.php';
require_once 'inc/util_inc.php';
require_once 'inc/familytree_class.php';

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn();
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$ftree = new FamilyTree($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Family Tree'),
    'path'          => "",
    'admin_path'    => "admin/",
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript" src="'.$TMPL['path'].'inc/livevalidation.js"></script>';

// Show Header
require_once getTheme($currentUserId) . 'header.php';

$show_tree = true;

echo '
<style>
</style>
        <div id="familytree-page" class="centercontent clearfix">';

//-------------------------------------
// Add Relationship
//-------------------------------------
if (isset($_POST['add-leaf'])) {

    $worked = false;

    $user         = cleanInput($_POST['user'], 'int');
    $relationship = cleanInput($_POST['relationship']);
    $rel_user     = cleanInput($_POST['rel_user'], 'int');

    // Spouse
    if ($relationship == 'WIFE' || $relationship == 'HUSB') {
        $worked = $ftree->addSpouse($user, $relationship, $rel_user);
    }

    // Child
    if ($relationship == 'CHIL') {
        $worked = $ftree->addChild($user, $relationship, $rel_user);
    }

    if ($worked) {
        echo '
            <p class="ok-alert" id="msg">'.T_('Family Tree Updated').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'msg\').toggle()",3000); }
            </script>';
    }
}

//-------------------------------------
// Add new user
//-------------------------------------
if (isset($_POST['add-user'])) {
    $show_tree = false;

    // Missing req field
    if (   !isset($_POST['fname'])
        or !isset($_POST['lname'])
        or !isset($_POST['sex'])
    ) {
        $url = cleanOutput($_POST['url']);
        echo '
        <p class="error-alert">' . T_('Missing Required Field') . '</p>';
        $ftree->displayCreateUserForm($url);

    // All good, create user
    } else {

        $uniq = uniqid("");

        $year   = cleanInput($_POST['year'], 'int');
        $month  = cleanInput($_POST['month'], 'int'); 
        $month  = str_pad($month, 2, "0", STR_PAD_LEFT);
        $day    = cleanInput($_POST['day'], 'int');
        $day    = str_pad($day, 2, "0", STR_PAD_LEFT);
        $birthday = "$year-$month-$day";

        $sql = "INSERT INTO `fcms_users`(
                    `access`, `joindate`, `fname`, `lname`, `sex`, `birthday`, `username`, `password`, `activated`
                ) VALUES (
                    10, 
                    NOW(), 
                    '" . cleanInput($_POST['fname']) . "', 
                    '" . cleanInput($_POST['lname']) . "', 
                    '" . cleanInput($_POST['sex']) . "', 
                    '$birthday', 
                    'NONMEMBER-$uniq', 
                    'NONMEMBER', 
                    1
                )";
        mysql_query($sql) or displaySQLError(
            'Non-Member Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $lastid = mysql_insert_id();

        $sql = "INSERT INTO `fcms_address`(`user`, `entered_by`,`updated`) 
                VALUES ('$lastid', '$currentUserId', NOW())";
        mysql_query($sql) or displaySQLError(
            'New Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        $sql = "INSERT INTO `fcms_user_settings`(`user`) VALUES ('$lastid')";
        mysql_query($sql) or displaySQLError(
            'New User Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        echo '
        <p class="ok-alert" id="update">'.T_('Changes Updated Successfully').'</p>
        <script type="text/javascript">
            window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
        </script>';

        $url = explode('&', $_POST['url']);
        $add = explode('=', $url[0]);
        $user = explode('=', $url[1]);
        if ($add[1] == 'child') {
            $ftree->displayAddChildForm($user[1]);
        } elseif ($add[1] == 'wife' || $add[1] == 'husb') {
            $ftree->displayAddSpouseForm($add[1], $user[1]);
        } else {
            $ftree->displayAddParentForm($add[1], $user[1]);
        }
    }
}

//-------------------------------------
// Display add relationship form
//-------------------------------------
if (isset($_GET['add']) and isset($_GET['user'])) {
    $show_tree = false;
    $add  = cleanInput($_GET['add']);
    $user = cleanInput($_GET['user'], 'int');
    if ($add == 'child') {
        $ftree->displayAddChildForm($user);
    } elseif ($add == 'wife' || $add == 'husb') {
        $ftree->displayAddSpouseForm($add, $user);
    } else {
        $ftree->displayAddParentForm($add, $user);
    }
}

//-------------------------------------
// Display add new user form
//-------------------------------------
if (isset($_GET['create']) and isset($_GET['type']) and isset($_GET['id'])) {
    $valid_types = array('dad', 'mom', 'child', 'wife', 'husb');
    $type = cleanInput($_GET['type']);
    $id   = cleanInput($_GET['id'], 'int');
    if (in_array($type, $valid_types)) {
        $show_tree = false;
        $ftree->displayCreateUserForm("add=$type&amp;user=$id");
    }
}

//-------------------------------------
// Remove relationships
//-------------------------------------
if (isset($_GET['remove'])) {

    // Show form
    if ($_GET['remove'] == 'user') {
        $show_tree = false;
        $id = (int)$_GET['id'];
        $ftree->displayFamilyTree($id, 'list_edit');

    // Remove from db
    } else {
        $id = (int)$_GET['remove'];
        $sql = "DELETE FROM `fcms_relationship`
                WHERE `user` = '$id'
                OR `rel_user` = '$id'";
        mysql_query($sql) or displaySQLError(
            'Remove Relationship Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    }

}

//-------------------------------------
// Show family tree
//-------------------------------------
if ($show_tree) {
    $id = $currentUserId;
    if (isset($_GET['tree'])) {
        $id = cleanInput($_GET['tree'], 'int');
    }
    $ftree->displayFamilyTree($id);
}

echo '
        </div><!-- #familytree-page .centercontent -->';

// Show Footer
require_once getTheme($currentUserId) . 'footer.php';
