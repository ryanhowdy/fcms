<?php
/**
 * AddressBook 
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

require_once 'inc/config_inc.php';
require_once 'inc/util_inc.php';
require_once 'inc/alerts_class.php';
require_once 'inc/database_class.php';
require_once 'inc/locale.php';
require_once 'inc/addressbook_class.php';

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn();
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$locale = new Locale();
$database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$book = new AddressBook($currentUserId, $database);
$alert = new Alerts($currentUserId, $database);

header("Cache-control: private");
if (isset($_GET['csv'])) {
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
        $date = $locale->fixDate('Y-m-d', $book->tzOffset);
        header("Content-type: text/plain");
        header("Content-disposition: csv; filename=FCMS_Addresses_$date.csv; size=".strlen($csv));
        echo $csv;
        exit();
    }
}

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Address Book'),
    'path'          => "",
    'admin_path'    => "admin/",
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript" src="inc/tablesort.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    if ($(\'del\')) {
        var item = $(\'del\');
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this address?').'\'); };
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
require_once getTheme($currentUserId) . 'header.php';

echo '
        <div id="addressbook" class="centercontent clearfix">';

$show = true;

//-----------------------------------------------
// CSV
//-----------------------------------------------
if (isset($_GET['csv'])) {
    if ($_GET['csv'] == 'import' && !isset($_POST['import'])) {
        $show = false;
        $book->displayImportForm();
    } elseif ($_GET['csv'] == 'import' && isset($_POST['import'])) {
        $book->importAddressCsv($_FILES['csv']);
    }
}

//-----------------------------------------------
// Mass email Form
//-----------------------------------------------
if (isset($_POST['emailsubmit'])) {
    if (checkAccess($currentUserId) > 3) {
        echo '
                <p class="error-alert">
                    '.T_('You do not have permission to perform this task.  You must have an access level of 3 (Member) or higher.').'
                </p>';
    } else {
        if (empty($_POST['massemail'])) {
            echo '
                <p class="error-alert">
                    '.T_('You must choose at least one member to email.').' 
                    <a href="help.php#address-massemail">'.T_('Get more help on sending mass emails.').'</a>
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
if (   isset($_POST['sendemailsubmit'])
    && !empty($_POST['subject'])
    && !empty($_POST['email'])
    && !empty($_POST['name'])
    && !empty($_POST['msg'])
) {
    $subject = cleanOutput($_POST['subject']);
    $fromEmail = cleanOutput($_POST['email']);
    $name = cleanOutput($_POST['name']);
    $msg = cleanOutput($_POST['msg'], 'html');
    $emailHeaders = getEmailHeaders($name, $fromEmail);
    foreach ($_POST['emailaddress'] as $email) {
        $email = cleanInput($email);
        mail($email, $subject, "$msg\r\n-$name", $emailHeaders);
    }
    echo '
            <p class="ok-alert" id="msg">
                ' . T_('The following message has been sent:') . '<br/>
                ' . $msg . '
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
                `address`   = '" . cleanInput($_POST['address']) . "', 
                `city`      = '" . cleanInput($_POST['city']) . "', 
                `state`     = '" . cleanInput($_POST['state']) . "', 
                `zip`       = '" . cleanInput($_POST['zip']) . "', 
                `home`      = '" . cleanInput($_POST['home']) . "', 
                `work`      = '" . cleanInput($_POST['work']) . "', 
                `cell`      = '" . cleanInput($_POST['cell']) . "' 
            WHERE `id` = '" . cleanInput($_POST['aid'], 'int') . "'";
    mysql_query($sql) or displaySQLError(
        'Edit Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    // User's email
    $sql = "UPDATE `fcms_users` 
            SET `email`='" . cleanInput($_POST['email'])."' 
            WHERE `id` = " . cleanInput($_POST['uid'], 'int');
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
    $sql = "INSERT INTO `fcms_users` (
                `access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`
            ) VALUES (
                10, 
                NOW(), 
                '" . cleanInput($_POST['fname']) . "', 
                '" . cleanInput($_POST['lname']) . "', 
                '" . cleanInput($_POST['email']) . "', 
                'NONMEMBER-$uniq', 
                '$pw')";
    mysql_query($sql) or displaySQLError(
        'Add Non-Member Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $id = mysql_insert_id();
    $sql = "INSERT INTO `fcms_address`(
                `user`, `entered_by`, `updated`, `address`, `city`, `state`, 
                `zip`, `home`, `work`, `cell`
            ) VALUES (
                '$id', 
                '$currentUserId', 
                NOW(), 
                '" . cleanInput($_POST['address']) . "', 
                '" . cleanInput($_POST['city']) . "', 
                '" . cleanInput($_POST['state']) . "', 
                '" . cleanInput($_POST['zip']) . "', 
                '" . cleanInput($_POST['home']) . "', 
                '" . cleanInput($_POST['work']) . "', 
                '" . cleanInput($_POST['cell']) . "'
            )";
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
                        <h2>' . T_('Are you sure you want to DELETE this?') . '</h2>
                        <p><b><i>' . T_('This can NOT be undone.') . '</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="' . (int)$_POST['id'] . '"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="' . T_('Yes') . '"/>
                            <a style="float:right;" href="addressbook.php?address=' . (int)$_POST['id'] . '">'
                                . T_('Cancel') .
                            '</a>
                        </div>
                    </form>
                </div>';

//-----------------------------------------------
// Delete Address
//-----------------------------------------------
} elseif (isset($_POST['delconfirm']) || (isset($_POST['confirmed']) && isset($_POST['del']))) {
    if (checkAccess($currentUserId) < 2) {
        $aid = cleanInput($_POST['id'], 'int');
        $sql = "DELETE FROM fcms_users WHERE id = '$aid'";
        mysql_query($sql) or displaySQLError(
            'Non-member Deletion Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $sql = "DELETE FROM fcms_address WHERE id = '$aid'";
        mysql_query($sql) or displaySQLError(
            'Delete Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    } else {
        echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';
    }
}

//-----------------------------------------------
// Show form for editing an address
//-----------------------------------------------
if (isset($_POST['edit'])) {
    $aid = cleanInput($_POST['id'], 'int');
    $uid = cleanInput($_POST['user'], 'int');
    if (checkAccess($currentUserId) < 2 || $currentUserId == $uid) {
        $book->displayForm('edit', $aid);
    } else {
        echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';
    }
    $show = false;
}

//-----------------------------------------------
// Show form for adding an address
//-----------------------------------------------
if (isset($_GET['add'])) {
    if (checkAccess($currentUserId) <= 5) {
        $book->displayForm('add');
        $show = false;
    }
}

//-----------------------------------------------
// Display an Address
//-----------------------------------------------
if (isset($_GET['address'])) {
    $address = cleanInput($_GET['address'], 'int');
    $cat = isset($_GET['cat']) ? cleanInput($_GET['cat']) : '';
    $book->displayAddress($address, $cat);
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
                    '" . cleanInput($_GET['alert']) . "', 
                    '" . $currentUserId . "'
                )";
        mysql_query($sql) or displaySQLError(
            'Remove Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    }
    if (!$book->userHasAddress($currentUserId)) {
        // Show Alerts
        $alert->displayAddress($currentUserId);
    }
    $cat = isset($_GET['cat']) ? cleanInput($_GET['cat']) : '';
    $book->displayAddressList($cat);
}

echo '
        </div><!-- #centercontent -->';

// Show Footer
require_once getTheme($currentUserId) . 'footer.php';
/**
 *  
 */
