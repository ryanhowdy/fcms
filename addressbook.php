<?php
session_start();
if (get_magic_quotes_gpc()) {
    $_REQUEST = array_map('stripslashes', $_REQUEST);
    $_GET = array_map('stripslashes', $_GET);
    // a bug found with an array in $_POST
    if (!isset($_POST['emailsubmit']) && !isset($_POST['sendemailsubmit'])) {
        $_POST = array_map('stripslashes', $_POST);
    }
    $_COOKIE = array_map('stripslashes', $_COOKIE);
}

include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/alerts_class.php');

// Check that the user is logged in
isLoggedIn();
$current_user_id = (int)escape_string($_SESSION['login_id']);

include_once('inc/addressbook_class.php');
$book = new AddressBook($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$alert = new Alerts($database);
header("Cache-control: private");
if (isset($_GET['csv'])) {
    $show = false;
    if ($_GET['csv'] == 'export') {
        $csv = "lname, fname, address, city, state, zip, email, home, work, cell\015\012";
        $sql = "SELECT `lname`, `fname`, `address`, `city`, `state`, `zip`, `email`, `home`, `work`, `cell` 
                FROM `fcms_address` AS a, `fcms_users` AS u 
                WHERE a.`user` = u.`id` 
                ORDER BY `lname`, `fname`";
        $result = mysql_query($sql);
        while ($row = mysql_fetch_assoc($result)) {
            $csv .= '"'.join('","', str_replace('"', '""', $row))."\"\015\012";
        }
        header("Content-type: text/plain");
        header("Content-disposition: csv; filename=FCMS_Addresses_".date("Y-m-d").".csv; size=".strlen($csv));
        echo $csv;
        exit();
    }
}

// Setup the Template variables;
$TMPL['pagetitle'] = _('Address Book');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = '
<script type="text/javascript" src="inc/tablesort.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    if ($(\'del\')) {
        var item = $(\'del\');
        item.onclick = function() { return confirm(\''._('Are you sure you want to DELETE this address?').'\'); };
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

// Show header
include_once(getTheme($current_user_id) . 'header.php');

echo '
        <div id="addressbook" class="centercontent clearfix">';
$show = true;
if (isset($_GET['csv'])) {
    if ($_GET['csv'] == 'import' && !isset($_POST['import'])) {
        $show = false;
        $book->displayImportForm();
    } elseif ($_GET['csv'] == 'import' && isset($_POST['import'])) {
        $book->importAddressCsv($_FILES['csv']);
    }
}

if (isset($_POST['emailsubmit'])) {
    if (checkAccess($current_user_id) > 3) {
        echo '
                <p class="error-alert">
                    '._('You do not have permission to perform this task.  You must have an access level of 3 (Member) or higher.').'
                </p>';
    } else {
        if (empty($_POST['massemail'])) {
            echo '
                <p class="error-alert">
                    '._('You must choose at least one member to email.').' 
                    <a href="help.php#address-massemail">'._('Get more help on sending mass emails.').'</a>
                </p>';
        } else {
            $book->displayMassEmailForm($_POST['massemail']);
            $show = false;
        }
    }
}

//-----------------------------------------------
// Send Mass Email
//-----------------------------------------------
if (isset($_POST['sendemailsubmit']) && !empty($_POST['subject']) && !empty($_POST['email']) && !empty($_POST['name']) && !empty($_POST['msg'])) {
    $subject = stripslashes($_POST['subject']);
    $email = stripslashes($_POST['email']);
    $name = stripslashes($_POST['name']);
    $msg = stripslashes($_POST['msg']);
    foreach ($_POST['emailaddress'] as $email) {
        mail($email, $subject, "$msg\r\n-$name", $email_headers);
    }
    echo '
            <p class="ok-alert" id="msg">
                '._('The following message has been sent:').'<br/>
                '.$msg.'
            </p>
            <script type="text/javascript">window.onload=function(){ var t=setTimeout("$(\'msg\').toggle()",4000); }</script>';

//-----------------------------------------------
// Show Mass Email Form
//-----------------------------------------------
} elseif (isset($_POST['sendemailsubmit'])) {
    $book->displayMassEmailForm(
        $_POST['emailaddress'], $_POST['email'], $_POST['name'], $_POST['subject'], $_POST['msg'], 'Yes'
    );
    $show = false;
}

//-----------------------------------------------
// Edit Address Info
//-----------------------------------------------
if (isset($_POST['editsubmit'])) {
    // Address
    $sql = "UPDATE `fcms_address` 
            SET `updated`=NOW(), 
                `address`='".escape_string($_POST['address'])."', 
                `city`='".escape_string($_POST['city'])."', 
                `state`='".escape_string($_POST['state'])."', 
                `zip`='".escape_string($_POST['zip'])."', 
                `home`='".escape_string($_POST['home'])."', 
                `work`='".escape_string($_POST['work'])."', 
                `cell`='".escape_string($_POST['cell'])."' 
            WHERE `id`=".$_POST['aid'];
    mysql_query($sql) or displaySQLError(
        'Edit Address Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error()
    );
    // User's email
    $sql = "UPDATE `fcms_users` 
            SET `email`='".escape_string($_POST['email'])."' 
            WHERE `id` = ".escape_string($_POST['uid']);
    mysql_query($sql) or displaySQLError(
        'Edit Email Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error()
    );
}

//-----------------------------------------------
// Add new Address
//-----------------------------------------------
if (isset($_POST['addsubmit'])) {
    $uniq = uniqid("");
    $pw = 'NONMEMBER';
    if (isset($_POST['private'])) {
        $pw = 'PRIVATE';
    }
    $sql = "INSERT INTO `fcms_users` ("
            . "`access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`"
         . ") VALUES ("
            . "10, "
            . "NOW(), "
            . "'" . escape_string($_POST['fname']) . "', "
            . "'" . escape_string($_POST['lname']) . "', "
            . "'" . escape_string($_POST['email']) . "', "
            . "'NONMEMBER-$uniq', "
            . "'$pw')";
    mysql_query($sql) or displaySQLError(
        'Add Non-Member Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $id = mysql_insert_id();
    $sql = "INSERT INTO `fcms_address`("
            . "`user`, `entered_by`, `updated`, `address`, `city`, `state`, "
            . "`zip`, `home`, `work`, `cell`"
         . ") VALUES ("
            . "$id, "
            . $current_user_id . ", "
            . "NOW(), "
            . "'" . escape_string($_POST['address']) . "', "
            . "'" . escape_string($_POST['city']) . "', "
            . "'" . escape_string($_POST['state']) . "', "
            . "'" . escape_string($_POST['zip']) . "', "
            . "'" . escape_string($_POST['home']) . "', "
            . "'" . escape_string($_POST['work']) . "', "
            . "'" . escape_string($_POST['cell']) . "')";
    mysql_query($sql) or displaySQLError(
        'New Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
}

//-----------------------------------------------
// Confirmation screen for delete
//-----------------------------------------------
if (isset($_POST['del']) && !isset($_POST['confirmed'])) {
    $show = false;
    echo '
                <div class="info-alert clearfix">
                    <form action="addressbook.php" method="post">
                        <h2>'._('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'._('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'._('Yes').'"/>
                            <a style="float:right;" href="addressbook.php?address='.$_POST['id'].'">'._('Cancel').'</a>
                        </div>
                    </form>
                </div>';

//-----------------------------------------------
// Delete Address
//-----------------------------------------------
} elseif (isset($_POST['delconfirm']) || (isset($_POST['confirmed']) && isset($_POST['del']))) {
    if (checkAccess($current_user_id) < 2) {
        $aid = escape_string($_POST['id']);
        $sql = "DELETE FROM fcms_users WHERE id = $aid";
        mysql_query($sql) or displaySQLError('Non-member Deletion Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error());
        $sql = "DELETE FROM fcms_address WHERE id = $aid";
        mysql_query($sql) or displaySQLError('Delete Address Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error());
    } else {
        echo '
            <p class="error-alert">'._('You do not have permission to perform this action.').'</p>';
    }
}

//-----------------------------------------------
// Show form for editing an address
//-----------------------------------------------
if (isset($_POST['edit'])) {
    if (checkAccess($current_user_id) < 2 || $current_user_id == $_POST['id']) {
        $book->displayForm('edit', $_POST['id']);
    } else {
        echo '
            <p class="error-alert">'._('You do not have permission to perform this action.').'</p>';
    }
    $show = false;
}

//-----------------------------------------------
// Show form for adding an address
//-----------------------------------------------
if (isset($_GET['add'])) {
    if (checkAccess($current_user_id) <= 5) {
        $book->displayForm('add');
        $show = false;
    }
}

//-----------------------------------------------
// Display an Address
//-----------------------------------------------
if (isset($_GET['address'])) {
    $cat = isset($_GET['cat']) ? $_GET['cat'] : '';
    $book->displayAddress($_GET['address'], $cat);
    $show = false;
}

//-----------------------------------------------
// Show list of addresses
//-----------------------------------------------
if ($show) {
    // Remove an alert
    if (isset($_GET['alert'])) {
        $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
                VALUES (
                    '".escape_string($_GET['alert'])."', 
                    ".$current_user_id."
                )";
        mysql_query($sql) or displaySQLError(
            'Remove Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    }
    if (!$book->userHasAddress($current_user_id)) {
        // Show Alerts
        $alert->displayAddress($current_user_id);
    }
    $cat = isset($_GET['cat']) ? $_GET['cat'] : '';
    $book->displayAddressList($cat);
}

echo '
        </div><!-- #centercontent -->';

// Show Footer
include_once(getTheme($current_user_id) . 'footer.php'); ?>
