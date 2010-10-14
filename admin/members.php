<?php
session_start();

include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');
include_once('../inc/admin_members_class.php');
include_once('../inc/database_class.php');

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn('admin/');
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$member = new AdminMembers($database);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Members'),
    'path'          => "../",
    'admin_path'    => "",
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript" src="'.$TMPL['path'].'inc/livevalidation.js"></script>
<script type="text/javascript" src="'.$TMPL['path'].'inc/tablesort.js"></script>
<link rel="stylesheet" type="text/css" href="'.$TMPL['path'].'themes/datechooser.css"/>
<script type="text/javascript" src="'.$TMPL['path'].'inc/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    // Datechooser
    var objDatePicker = new DateChooser();
    objDatePicker.setUpdateField({\'day\':\'j\', \'month\':\'n\', \'year\':\'Y\'});
    objDatePicker.setIcon(\''.$TMPL['path'].'themes/default/images/datepicker.jpg\', \'year\');
    // Delete Confirmation All
    if ($(\'deleteAll\')) {
        var item = $(\'deleteAll\'); 
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmedall\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    }
    // Delete Confirmation
    if ($(\'delete\')) {
        var item = $(\'delete\'); 
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    }
    return true;
});
//]]>
</script>';

// Show Header
include_once(getTheme($currentUserId, $TMPL['path']) . 'header.php');

echo '
        <div class="centercontent">';

if (checkAccess($currentUserId) < 2) {
    $show = true;

    //--------------------------------------------------------------------------
    // Display create new member form
    //--------------------------------------------------------------------------
    if (isset($_GET['create'])) {
        $show = false;
        $member->displayCreateMemberForm();

    //--------------------------------------------------------------------------
    // Diplay edit form
    //--------------------------------------------------------------------------
    } elseif (isset($_GET['edit'])) {
        $show = false;
        $id = cleanInput($_GET['edit'], 'int');
        $member->displayEditMemberForm($id);
    }

    //--------------------------------------------------------------------------
    // Merge member form
    //--------------------------------------------------------------------------
    if (isset($_GET['merge'])) {
        $show = false;
        $id = cleanInput($_GET['merge'], 'int');
        $member->displayMergeMemberForm($id);
    }

    //--------------------------------------------------------------------------
    // Merge member review
    //--------------------------------------------------------------------------
    if (isset($_POST['merge-review'])) {
        $show = false;
        $id     = cleanInput($_POST['id'], 'int');
        if ($_POST['merge-with'] < 1) {
            echo '
            <p class="error-alert">'.T_('You must choose a member to merge with.').'</p>';
            $member->displayMergeMemberForm($id);
        } else {
            $merge  = cleanInput($_POST['merge-with'], 'int');
            $member->displayMergeMemberFormReview($id, $merge);
        }
    }

    //--------------------------------------------------------------------------
    // Merge member
    //--------------------------------------------------------------------------
    if (isset($_POST['merge-submit'])) {
        $show = false;

        $id    = cleanInput($_POST['id'], 'int');
        $merge = cleanInput($_POST['merge'], 'int');

        // Update member
        $sql = "UPDATE `fcms_users`
                SET `username` = '".cleanInput($_POST['username'])."',
                    `fname` = '".cleanInput($_POST['fname'])."',
                    `lname` = '".cleanInput($_POST['lname'])."',
                    `email` = '".cleanInput($_POST['email'])."',
                    `birthday` = '".cleanInput($_POST['birthday'])."'
                WHERE `id` = '$id'";
        if (!mysql_query($sql)) {
            displaySQLError('Merge Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            die();
        }
        echo sprintf(T_('Update [%s] complete.'), 'fcms_users').'<br/>';

        // Update member address
        $sql = "UPDATE `fcms_address`
                SET `address` = '".cleanInput($_POST['address'])."',
                    `city` = '".cleanInput($_POST['city'])."',
                    `state` = '".cleanInput($_POST['state'])."',
                    `zip` = '".cleanInput($_POST['zip'])."',
                    `home` = '".cleanInput($_POST['home'])."',
                    `work` = '".cleanInput($_POST['work'])."',
                    `cell` = '".cleanInput($_POST['cell'])."'
                WHERE `id` = '$id'";
        if (!mysql_query($sql)) {
            displaySQLError('Merge Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            die();
        }
        echo sprintf(T_('Update [%s] complete.'), 'fcms_address').'<br/>';

        // Update all occurences of merge id with id
        $member->mergeMember($id, $merge);

        // Delete merge id
        $sql = "DELETE FROM `fcms_users`
                WHERE `id` = '$merge'";
        if (!mysql_query($sql)) {
            displaySQLError('Merge Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            die();
        }
        echo sprintf(T_('Delete [%s] complete.'), 'fcms_users').'<br/>';
    }

    //--------------------------------------------------------------------------
    // Create a new member
    //--------------------------------------------------------------------------
    if (isset($_POST['create'])) {
        $sql = "SELECT `email` FROM `fcms_users` 
                WHERE `email` = '" . cleanInput($_POST['email']) . "'";
        $result = mysql_query($sql) or displaySQLError(
            'Email Check Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        ); 
        $email_check = mysql_num_rows($result);
        if (
            !isset($_POST['username']) || 
            !isset($_POST['password']) || 
            !isset($_POST['fname']) || 
            !isset($_POST['lname']) || 
            !isset($_POST['email'])
        ) {
            $show = false;
            $member->displayCreateMemberForm(T_('Missing Required Field'));
        } elseif ($email_check > 0) {
            $show = false;
            $member->displayCreateMemberForm(
                sprintf(T_('The email address %s is already in use.  Please choose a different email.'), $_POST['email'])
            );
        } else {
            $fname      = cleanInput($_POST['fname']);
            $lname      = cleanInput($_POST['lname']);
            $email      = cleanInput($_POST['email']);

            $year       = cleanInput($_POST['year'], 'int');
            $month      = cleanInput($_POST['month'], 'int'); 
            $month      = str_pad($month, 2, "0", STR_PAD_LEFT);
            $day        = cleanInput($_POST['day'], 'int');
            $day        = str_pad($day, 2, "0", STR_PAD_LEFT);
            $birthday   = "$year-$month-$day";

            $username   = cleanInput($_POST['username']);
            $password   = cleanInput($_POST['password']);
            $md5pass    = md5($password);

            // Create new member
            $sql = "INSERT INTO `fcms_users`("
                    . "`access`, `joindate`, `fname`, `lname`, `email`, `birthday`, "
                    . "`username`, `password`, `activated`) "
                 . "VALUES ("
                    . "3, NOW(), '$fname', '$lname', '$email', '$birthday', "
                    . "'$username', '$md5pass', 1)";
            mysql_query($sql) or displaySQLError(
                'New User Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $lastid = mysql_insert_id();

            // Create member's address
            $sql = "INSERT INTO `fcms_address`(`user`, `entered_by`,`updated`) "
                 . "VALUES ($lastid, $lastid, NOW())";
            mysql_query($sql) or displaySQLError(
                'New Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );

            // Create member's settings
            $sql = "INSERT INTO `fcms_user_settings`(`user`) VALUES ($lastid)";
            mysql_query($sql) or displaySQLError(
                'New User Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );

            // Create calendar entry for member's bday
            $cat = getBirthdayCategory();
            $sql = "INSERT INTO `fcms_calendar`(`date`, `title`, `created_by`, `date_added`, `category`) "
                 . "VALUES ('$birthday', '$fname $lname', $currentUserId, NOW(), $cat)";
            mysql_query($sql) or displaySQLError(
                'New Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );

            echo '
            <p class="ok-alert" id="update">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
        }
    
    //--------------------------------------------------------------------------
    // Edit Member
    //--------------------------------------------------------------------------
    } elseif (isset($_POST['edit'])) {

        if (!isset($_POST['fname']) || 
            !isset($_POST['lname']) || 
            !isset($_POST['email'])) {
            $show = false;
            $member->displayEditMemberForm(
                $_POST['id'],
                '<p class="error">'.T_('Missing Required Field').'</p>'
                );
        } else {

            $id = cleanInput($_POST['id'], 'int');
            $emailstart = $member->getUsersEmail($id);

            $year       = cleanInput($_POST['year'], 'int');
            $month      = cleanInput($_POST['month'], 'int'); 
            $month      = str_pad($month, 2, "0", STR_PAD_LEFT);
            $day        = cleanInput($_POST['day'], 'int');
            $day        = str_pad($day, 2, "0", STR_PAD_LEFT);
            $birthday   = "$year-$month-$day";

            // Update user info
            $sql = "UPDATE `fcms_users` SET 
                        `fname` = '" . cleanInput($_POST['fname']) . "', 
                        `lname` = '" . cleanInput($_POST['lname']) . "', ";

            if ($_POST['email']) { 
                if ($_POST['email'] != $emailstart) {
                    $email_sql = "SELECT `email` 
                                  FROM `fcms_users` 
                                  WHERE `email` = '" . cleanInput($_POST['email']) . "'";
                    $result = mysql_query($email_sql) or displaySQLError(
                        'Email Check Error', 
                        __FILE__ . ' [' . __LINE__ . ']', 
                        $email_sql, 
                        mysql_error()
                    );
                    $email_check = mysql_num_rows($result);
                    if ($email_check > 0) { 
                        $member->displayEditMemberForm(
                            $_POST['id'],
                            '<p class="error-alert">'
                                .sprintf(T_('The email address %s is already in use.  Please choose a different email.'), $_POST['email']).
                            '</p>'
                        );
                        exit();
                    }
                    $sql .= "email = '" . cleanInput($_POST['email']) . "', ";
                }
            }
            if ($_POST['password']) {
                $sql .= "`password` = '".md5($_POST['password'])."', ";
                $sitename = getSiteName();
                $subject = getSiteName().': '.T_('Password Change');
                $message = $_POST['fname'].' '.$_POST['lname'].', 

'.sprintf(T_('Your password at %s has been changed by the administrator.'), $sitename).'

'.sprintf(T_('Your new password is %s'), $_POST['password']);
                mail($_POST['email'], $subject, $message, getEmailHeaders());
            }
            $sql .= "`birthday` = '$birthday', "
                  . "`joindate` = NOW(), "
                  . "`access` = '" . cleanInput($_POST['access'], 'int') . "' "
                  . "WHERE id = '" . cleanInput($_POST['id'], 'int') . "'";
            mysql_query($sql) or displaySQLError(
                'Edit Member Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            echo '
            <p class="ok-alert" id="update">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
        }

    //--------------------------------------------------------------------------
    // Activate Selected Members
    //--------------------------------------------------------------------------
    } elseif (isset($_POST['activateAll']) && isset($_POST['massupdate'])) { 

        // Get list of new members
        // Members with no activity and not activated
        $sql = "SELECT `id`, `activity` 
                FROM `fcms_users` 
                WHERE `activity` = '0000-00-00 00:00:00'
                AND `activated` = 0";
        $result = mysql_query($sql) or displaySQLError(
            'Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = mysql_fetch_array($result)) {
            $new_members[$r['id']] = $r['activity'];
        }
        foreach ($_POST['massupdate'] AS $id) {
            // Activate the member
            $sql = "UPDATE `fcms_users` 
                    SET `activated` = 1 
                    WHERE `id` = '" . cleanInput($id, 'int') . "'";
            mysql_query($sql) or displaySQLError(
                'Mass Activate Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            // If they are a new member, then reset the joindate
            if (isset($new_members)) { 
                if (array_key_exists($id, $new_members)) {
                    $sql = "UPDATE `fcms_users` 
                            SET `joindate` = NOW() 
                            WHERE `id` = '" . cleanInput($id, 'int') . "'";
                    mysql_query($sql) or displaySQLError(
                        'Mass Activate New Member Error',
                         __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                }
            }
        }
        echo '
            <p class="ok-alert" id="update">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';

    //--------------------------------------------------------------------------
    // Inactivate Selected Members
    //--------------------------------------------------------------------------
    } elseif (isset($_POST['inactivateAll']) && isset($_POST['massupdate'])) { 
        foreach ($_POST['massupdate'] AS $id) {
            $sql = "UPDATE `fcms_users` 
                    SET `activated` = 0 
                    WHERE `id` = '" . cleanInput($id, 'int') . "'";
            mysql_query($sql) or displaySQLError(
                'Mass Inactivate Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
        echo '
            <p class="ok-alert" id="update">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';

    //--------------------------------------------------------------------------
    // Delete confirmation for selected members
    //--------------------------------------------------------------------------
    } else if (isset($_POST['deleteAll']) && !isset($_POST['confirmedall']) && isset($_POST['massupdate'])) { 
        $show = false;
        echo '
                <div class="info-alert clearfix">
                    <form action="members.php" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>';
        foreach ($_POST['massupdate'] AS $id) {
            $id = cleanInput($id, 'int');
            echo '
                            <input type="hidden" name="massupdate[]" value="'.$id.'"/>';
        }
        echo '
                            <input style="float:left;" type="submit" id="delconfirmall" name="delconfirmall" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="members.php">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    //--------------------------------------------------------------------------
    // Delete Selected Members
    //--------------------------------------------------------------------------
    } elseif ((isset($_POST['delconfirmall']) || isset($_POST['confirmedall'])) && isset($_POST['massupdate'])) { 
        foreach ($_POST['massupdate'] AS $id) {
            $sql = "DELETE FROM `fcms_users` 
                    WHERE `id` = '" . cleanInput($id, 'int') . "'";
            mysql_query($sql) or displaySQLError(
                'Mass Delete Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
        echo '
            <p class="ok-alert" id="update">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
    
    //--------------------------------------------------------------------------
    // Delete confirmation member
    //--------------------------------------------------------------------------
    } else if (isset($_POST['delete']) && !isset($_POST['confirmed'])) {
        $show = false;
        echo '
                <div class="info-alert clearfix">
                    <form action="members.php" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.(int)$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="members.php?edit='.(int)$_POST['id'].'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    //--------------------------------------------------------------------------
    // Delete Member
    //--------------------------------------------------------------------------
    } elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
        $id = cleanInput($_POST['id'], 'int');
        $sql = "DELETE FROM `fcms_users` 
                WHERE `id` = '$id'";
        mysql_query($sql) or displaySQLError(
            'Delete User Error', 
            __FILE__ . ' [' . __LINE__ . ']', 
            $sql, 
            mysql_error()
        );
        echo '
            <p class="ok-alert" id="update">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
    }
    
    //--------------------------------------------------------------------------
    // Show Member List
    //--------------------------------------------------------------------------
    if ($show) {
        $page = 1;
        if (isset($_GET['page'])) {
            $page = cleanInput($_GET['page'], 'int');
        }
        if (isset($_POST['search'])) {
            $first = cleanInput($_POST['fname']);
            $last  = cleanInput($_POST['lname']);
            $user  = cleanInput($_POST['uname']);
            $member->displayMemberList($page, $first, $last,$user);
        } else {
            $member->displayMemberList($page);
        }
    }
} else {
    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 1 (Admin).').' 
                <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
}

echo '
        </div><!-- .centercontent -->';

// Show Footer
include_once(getTheme($currentUserId, $TMPL['path']) . 'footer.php');
