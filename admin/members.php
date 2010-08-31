<?php
session_start();
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');

// Check that the user is logged in
isLoggedIn('admin/');

include_once('../inc/members_class.php');
include_once('../inc/database_class.php');
$database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$member = new Members($database);
header("Cache-control: private");

// Setup the Template variables;
$TMPL['pagetitle'] = _('Administration: Members');
$TMPL['path'] = "../";
$TMPL['admin_path'] = "";
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
        item.onclick = function() { return confirm(\''._('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmedall\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    }
    // Delete Confirmation
    if ($(\'delete\')) {
        var item = $(\'delete\'); 
        item.onclick = function() { return confirm(\''._('Are you sure you want to DELETE this?').'\'); };
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
include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'header.php');

echo '
        <div class="centercontent">';

if (checkAccess($_SESSION['login_id']) < 2) {
    $show = true;
    
    // Display create new member form
    if (isset($_GET['create'])) {
        $show = false;
        $member->displayCreateMemberForm();
    
    // Diplay edit form
    } elseif (isset($_GET['edit'])) {
        $show = false;
        // Sanitize input, only numbers
        if (preg_match('/^\d+$/', $_GET['edit'])) {
            $member->displayEditMemberForm($_GET['edit']);
        } else {
            echo '
            <p class="error">'._('Invalid Member ID.').'</p>';
        }
    }
    
    // Create a new member
    if (isset($_POST['create'])) {
        $sql = "SELECT `email` FROM `fcms_users` "
             . "WHERE `email` = '" . $_POST['email'] . "'";
        $result = mysql_query($sql) or displaySQLError(
            'Email Check Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            ); 
        $email_check = mysql_num_rows($result);
        if (!isset($_POST['username']) || 
            !isset($_POST['password']) || 
            !isset($_POST['fname']) || 
            !isset($_POST['lname']) || 
            !isset($_POST['email'])) {
            $show = false;
            $member->displayCreateMemberForm(_('Missing Required Field'));
        } elseif ($email_check > 0) {
            $show = false;
            $member->displayCreateMemberForm(
                sprintf(_('The email address %s is already in use.  Please choose a different email.'), $_POST['email'])
            );
        } else {
            $fname = escape_string($_POST['fname']);
            $lname = escape_string($_POST['lname']);
            $email = escape_string($_POST['email']);
            $birthday = escape_string($_POST['year']) . "-"
                . str_pad(escape_string($_POST['month']), 2, "0", STR_PAD_LEFT) . "-" 
                . str_pad(escape_string($_POST['day']), 2, "0", STR_PAD_LEFT);
            $username = escape_string($_POST['username']);
            $password = escape_string($_POST['password']);
            $md5pass = md5($password);
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
            $sql = "INSERT INTO `fcms_address`(`user`, `entered_by`,`updated`) "
                 . "VALUES ($lastid, $lastid, NOW())";
            mysql_query($sql) or displaySQLError(
                'New Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
            $sql = "INSERT INTO `fcms_user_settings`(`user`) VALUES ($lastid)";
            mysql_query($sql) or displaySQLError(
                'New User Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
            $sql = "INSERT INTO `fcms_calendar`(`date`, `title`, `created_by`, `type`) "
                 . "VALUES ('$birthday', '$fname $lname', {$_SESSION['login_id']}, 'Birthday')";
            mysql_query($sql) or displaySQLError(
                'New Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
            echo '
            <p class="ok-alert" id="update">'._('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
        }
    
    // Edit Member
    } elseif (isset($_POST['edit'])) {
        if (!isset($_POST['fname']) || 
            !isset($_POST['lname']) || 
            !isset($_POST['email'])) {
            $show = false;
            $member->displayEditMemberForm(
                $_POST['id'],
                '<p class="error">'._('Missing Required Field').'</p>'
                );
        } else {
            // Update user info
            $emailstart = $member->getUsersEmail($_POST['id']);
            $birthday = $_POST['year'] . "-" 
                . str_pad($_POST['month'], 2, "0", STR_PAD_LEFT) . "-" 
                . str_pad($_POST['day'], 2, "0", STR_PAD_LEFT);
            $sql = "UPDATE `fcms_users` SET "
                . "`fname` = '" . escape_string($_POST['fname']) . "', "
                . "`lname` = '" . escape_string($_POST['lname']) . "', ";
            if ($_POST['email']) { 
                if ($_POST['email'] != $emailstart) {
                    $email_sql = "SELECT `email` FROM `fcms_users` "
                         . "WHERE `email` = '" . $_POST['email'] . "'";
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
                                .sprintf(_('The email address %s is already in use.  Please choose a different email.'), $_POST['email']).
                            '</p>'
                        );
                        exit();
                    }
                    $sql .= "email = '" . escape_string($_POST['email']) . "', ";
                }
            }
            if ($_POST['password']) {
                $sql .= "`password` = '".md5($_POST['password'])."', ";
                $sitename = getSiteName();
                $subject = getSiteName().': '._('Password Change');
                $message = $_POST['fname'].' '.$_POST['lname'].', 

'.sprintf(_('Your password at %s has been changed by the administrator.'), $sitname).'

'.sprintf(_('Your new password is %s'), $_POST['password']);
                mail($_POST['email'], $subject, $message, $email_headers);
            }
            $sql .= "`birthday` = '$birthday', "
                  . "`joindate` = NOW(), "
                  . "`access` = " . $_POST['access'] . " "
                  . "WHERE id = " . $_POST['id'];
            mysql_query($sql) or displaySQLError(
                'Edit Member Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
            echo '
            <p class="ok-alert" id="update">'._('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
        }

    //-----------------------------------------------
    // Activate Selected Members
    //-----------------------------------------------
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
            $sql = "UPDATE `fcms_users` SET `activated` = 1 WHERE `id` = $id";
            mysql_query($sql) or displaySQLError(
                'Mass Activate Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            // If they are a new member, then reset the joindate
            if (isset($new_members)) { 
                if (array_key_exists($id, $new_members)) {
                    $sql = "UPDATE `fcms_users` SET `joindate` = NOW() WHERE `id` = $id";
                    mysql_query($sql) or displaySQLError(
                        'Mass Activate New Member Error',
                         __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                }
            }
        }
        echo '
            <p class="ok-alert" id="update">'._('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';

    //----------------------------------------------- 
    // Inactivate Selected Members
    //-----------------------------------------------
    } elseif (isset($_POST['inactivateAll']) && isset($_POST['massupdate'])) { 
        foreach ($_POST['massupdate'] AS $id) {
            $sql = "UPDATE `fcms_users` SET `activated` = 0 WHERE `id` = $id";
            mysql_query($sql) or displaySQLError(
                'Mass Inactivate Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
        }
        echo '
            <p class="ok-alert" id="update">'._('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';

    //-----------------------------------------------
    // Delete confirmation for selected members
    //-----------------------------------------------
    } else if (isset($_POST['deleteAll']) && !isset($_POST['confirmedall']) && isset($_POST['massupdate'])) { 
        $show = false;
        echo '
                <div class="info-alert clearfix">
                    <form action="members.php" method="post">
                        <h2>'._('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'._('This can NOT be undone.').'</i></b></p>
                        <div>';
        foreach ($_POST['massupdate'] AS $id) {
            echo '
                            <input type="hidden" name="massupdate[]" value="'.$id.'"/>';
        }
        echo '
                            <input style="float:left;" type="submit" id="delconfirmall" name="delconfirmall" value="'._('Yes').'"/>
                            <a style="float:right;" href="members.php">'._('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    //-----------------------------------------------
    // Delete Selected Members
    //-----------------------------------------------
    } elseif ((isset($_POST['delconfirmall']) || isset($_POST['confirmedall'])) && isset($_POST['massupdate'])) { 
        foreach ($_POST['massupdate'] AS $id) {
            $sql = "DELETE FROM `fcms_users` WHERE `id` = $id";
            mysql_query($sql) or displaySQLError(
                'Mass Delete Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
        }
        echo '
            <p class="ok-alert" id="update">'._('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
    
    //-----------------------------------------------
    // Delete confirmation member
    //-----------------------------------------------
    } else if (isset($_POST['delete']) && !isset($_POST['confirmed'])) {
        $show = false;
        echo '
                <div class="info-alert clearfix">
                    <form action="members.php" method="post">
                        <h2>'._('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'._('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'._('Yes').'"/>
                            <a style="float:right;" href="members.php?edit='.$_POST['id'].'">'._('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    //-----------------------------------------------
    // Delete Member
    //-----------------------------------------------
    } elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
        $id = $_POST['id'];
        $sql = "DELETE FROM fcms_users WHERE id = $id";
        mysql_query($sql) or displaySQLError(
            'Delete User Error', 
            __FILE__ . ' [' . __LINE__ . ']', 
            $sql, 
            mysql_error()
        );
        echo '
            <p class="ok-alert" id="update">'._('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
    }
    
    //-----------------------------------------------
    // Show Member List
    //-----------------------------------------------
    if ($show) {
        $page = 1;
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        if (isset($_POST['search'])) {
            $member->displayMemberList(
                $page, 
                $_POST['fname'], 
                $_POST['lname'], 
                $_POST['uname']
                );
        } else {
            $member->displayMemberList($page);
        }
    }
} else {
    echo '
            <p class="error-alert">
                <b>'._('You do not have access to view this page.').'</b><br/>
                '._('This page requires an access level 1 (Admin).').' 
                <a href="../contact.php">'._('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
}

echo '
        </div><!-- .centercontent -->';

// Show Footer
include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'footer.php');
