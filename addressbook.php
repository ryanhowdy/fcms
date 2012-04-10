<?php
/**
 * AddressBook 
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

define('URL_PREFIX', '');

require 'fcms.php';

load('datetime', 'addressbook', 'database', 'alerts', 'phone', 'address');

init();

// Globals
$currentUserId = (int)$_SESSION['login_id'];
$book          = new AddressBook($currentUserId);
$alertObj      = new Alerts($currentUserId);

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Address Book'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

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
    global $book;

    if (isset($_GET['alert']))
    {
        removeAlert();
    }

    if (isset($_GET['csv']))
    {
        if ($_GET['csv'] == 'export')
        {
            displayExportSubmit();
        }
        elseif (isset($_POST['import']))
        {
            displayHeader();
            $book->importAddressCsv($_FILES['csv']);
            displayFooter();
        }
        else
        {
            displayHeader();
            $book->displayImportForm();
            displayFooter();
        }
    }
    elseif (isset($_POST['emailsubmit']))
    {
        displayMassEmailForm();
    }
    elseif (isset($_POST['sendemailsubmit']))
    {
        displayMassEmailSubmit();
    }
    elseif (isset($_GET['delete']))
    {
        if (!isset($_GET['confirmed']))
        {
            displayConfirmDeleteForm();
        }
        elseif (isset($_POST['delconfirm']) || isset($_GET['confirmed']))
        {
            displayDeleteSubmit();
        }
    }
    elseif (isset($_GET['edit']))
    {
        displayEditForm();
    }
    elseif (isset($_POST['editsubmit']))
    {
        displayEditSubmit();
    }
    elseif (isset($_GET['add']))
    {
        displayAddForm();
    }
    elseif (isset($_POST['addsubmit']))
    {
        displayAddSubmit();
    }
    elseif (isset($_GET['address']))
    {
        displayAddress();
    }
    else
    {
        displayAddressList();
    }
}

/**
 * displayExportSubmit 
 * 
 * @return void
 */
function displayExportSubmit ()
{
    global $book;

    $sql = "SELECT `lname`, `fname`, `address`, `city`, `state`, `zip`, `email`, `home`, `work`, `cell` 
            FROM `fcms_address` AS a, `fcms_users` AS u 
            WHERE a.`user` = u.`id` 
            ORDER BY `lname`, `fname`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $csv = "lname, fname, address, city, state, zip, email, home, work, cell\015\012";

    while ($row = mysql_fetch_assoc($result))
    {
        $csv .= '"'.join('","', str_replace('"', '""', $row))."\"\015\012";
    }

    $date = fixDate('Y-m-d', $book->tzOffset);

    header("Content-type: text/plain");
    header("Content-disposition: csv; filename=FCMS_Addresses_$date.csv; size=".strlen($csv));

    echo $csv;
}

/**
 * displayMassEmailForm
 * 
 * @return void
 */
function displayMassEmailForm ()
{
    global $currentUserId, $book;

    $massEmails = $_POST['massemail'];

    displayHeader();

    if (checkAccess($currentUserId) > 3)
    {
        echo '
                <p class="error-alert">
                    '.T_('You do not have permission to perform this task.  You must have an access level of 3 (Member) or higher.').'
                </p>';

        displayFooter();
        return;
    }

    if (empty($massEmails))
    {
        echo '
            <p class="error-alert">
                '.T_('You must choose at least one member to email.').' 
                <a href="help.php#address-massemail">'.T_('Get more help on sending mass emails.').'</a>
            </p>';

        displayFooter();
        return;
    }

    $book->displayMassEmailForm($massEmails);
    displayFooter();
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    global $currentUserId, $TMPL;

    $TMPL['javascript'] = '
<script type="text/javascript" src="ui/js/tablesort.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    initAddressBookClickRow();
    initCheckAll(\''.T_("Select All").'\');
    deleteConfirmationLink("del_address", "'.T_('Are you sure you want to DELETE this address?').'");
});
//]]>
</script>';

    include_once getTheme($currentUserId).'header.php';

    echo '
        <div id="addressbook" class="centercontent">';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    global $currentUserId, $TMPL;

    echo '
        </div><!-- /centercontent -->';

    include_once getTheme($currentUserId).'footer.php';
}

/**
 * displayMassEmailSubmit 
 * 
 * @return void
 */
function displayMassEmailSubmit ()
{
    global $book;

    displayHeader();

    $requiredFields = array('subject', 'email', 'name', 'msg');

    $missingRequired = false;

    foreach ($requiredFields as $field)
    {
        if (!isset($_POST[$field]))
        {
            $missingRequired = true;
        }
    }

    if ($missingRequired)
    {
        $book->displayMassEmailForm(
            $_POST['emailaddress'], 
            $_POST['email'], 
            $_POST['name'], 
            $_POST['subject'], 
            $_POST['msg'], 
            'Yes'
        );
        displayFooter();
        return;
    }

    $emailHeaders = getEmailHeaders($_POST['name'], $_POST['email']);

    foreach ($_POST['emailaddress'] as $email)
    {
        mail($email, $_POST['subject'], $_POST['msg']."\r\n-".$_POST['name'], $emailHeaders);
    }

    displayOkMessage(T_('Email has been sent.'));
    $book->displayAddressList('members');

    displayFooter();
}

/**
 * displayEditSubmit 
 * 
 * @return void
 */
function displayEditSubmit ()
{
    global $book, $currentUserId;

    displayHeader();

    $aid = (int)$_POST['aid'];
    $uid = (int)$_POST['uid'];
    $cat = $_POST['cat'];

    $address = strip_tags($_POST['address']);
    $city    = strip_tags($_POST['city']);
    $state   = strip_tags($_POST['state']);
    $zip     = strip_tags($_POST['zip']);
    $home    = strip_tags($_POST['home']);
    $work    = strip_tags($_POST['work']);
    $cell    = strip_tags($_POST['cell']);
    $email   = strip_tags($_POST['email']);

    $country = escape_string($_POST['country']);
    $address = escape_string($address);
    $city    = escape_string($city);
    $state   = escape_string($state);
    $zip     = escape_string($zip);
    $home    = escape_string($home);
    $work    = escape_string($work);
    $cell    = escape_string($cell);
    $email   = escape_string($email);

    // Get current address and email
    $sql = "SELECT a.`country`, a.`address`, a.`city`, a.`state`, a.`zip`, a.`home`, a.`work`, a.`cell`, u.`email`
            FROM `fcms_address` AS a
            LEFT JOIN `fcms_users` AS u ON a.`user` = u.`id`
            WHERE a.`id` = '$aid'
            AND a.`user` = '$uid'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $row = mysql_fetch_assoc($result);

    $changes = array();
    $columns = array(
        'country' => 'address', 
        'address' => 'address', 
        'city'    => 'address', 
        'state'   => 'address', 
        'zip'     => 'address', 
        'home'    => 'home', 
        'work'    => 'work', 
        'cell'    => 'cell', 
        'email'   => 'email'
    );

    // See what changed
    foreach ($columns as $column => $type)
    {
        // if db is null, then the column must be non empty to be considered changed
        if (is_null($row[$column]))
        {
            if (!empty($$column))
            {
                $changes[] = $type;
            }
        }
        // db doesn't match post data
        elseif ($row[$column] !== $$column)
        {
            $changes[] = $type;
        }
    }

    // We could have duplicate 'address' changes, lets only save once
    $changes = array_unique($changes);

    // Save Address
    $sql = "UPDATE `fcms_address` 
            SET `updated`    = NOW(), 
                `updated_id` = '$currentUserId',
                `country`    = '$country', 
                `address`    = '$address', 
                `city`       = '$city', 
                `state`      = '$state', 
                `zip`        = '$zip', 
                `home`       = '$home', 
                `work`       = '$work', 
                `cell`       = '$cell' 
            WHERE `id` = '$aid'";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    // Save Email
    $sql = "UPDATE `fcms_users` 
            SET `email`='$email' 
            WHERE `id` = '$uid'";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    // Update changelog
    $sql = "INSERT INTO `fcms_changelog` (`user`, `table`, `column`, `created`)
            VALUES ";

    foreach ($changes as $column)
    {
        $sql .= "('$uid', 'address', '$column', NOW()),";
    }
    $sql = substr($sql, 0, -1); // remove extra comma

    if (count($changes) > 0)
    {
        if (!mysql_query($sql))
        {
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    displayOkMessage();
    $book->displayAddress($aid, $cat);
    displayFooter();
}

/**
 * displayAddSubmit 
 * 
 * @return void
 */
function displayAddSubmit ()
{
    global $currentUserId, $book;

    displayHeader();

    $uniq    = uniqid("");

    $fname   = strip_tags($_POST['fname']);
    $lname   = strip_tags($_POST['lname']);
    $email   = strip_tags($_POST['email']);
    $country = strip_tags($_POST['country']);
    $address = strip_tags($_POST['address']);
    $city    = strip_tags($_POST['city']);
    $state   = strip_tags($_POST['state']);
    $zip     = strip_tags($_POST['zip']);
    $home    = strip_tags($_POST['home']);
    $work    = strip_tags($_POST['work']);
    $cell    = strip_tags($_POST['cell']);

    $fname   = escape_string($fname);
    $lname   = escape_string($lname);
    $email   = escape_string($email);
    $country = escape_string($country);
    $address = escape_string($address);
    $city    = escape_string($city);
    $state   = escape_string($state);
    $zip     = escape_string($zip);
    $home    = escape_string($home);
    $work    = escape_string($work);
    $cell    = escape_string($cell);

    $pw = 'NONMEMBER';

    if (isset($_POST['private']))
    {
        $pw = 'PRIVATE';
    }

    $sql = "INSERT INTO `fcms_users` (
                `access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`
            ) VALUES (
                10, 
                NOW(), 
                '$fname', 
                '$lname', 
                '$email', 
                'NONMEMBER-$uniq', 
                '$pw'
            )";

    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $id = mysql_insert_id();

    $sql = "INSERT INTO `fcms_address`(
                `user`, `created_id`, `created`, `updated_id`, `updated`, 
                `country`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell`
            ) VALUES (
                '$id', 
                '$currentUserId', 
                NOW(), 
                '$currentUserId', 
                NOW(), 
                '$country', 
                '$address', 
                '$city', 
                '$state', 
                '$zip', 
                '$home', 
                '$work', 
                '$cell'
            )";

    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    displayOkMessage();
    $book->displayAddressList('non');
    displayFooter();
}

/**
 * displayConfirmDeleteForm 
 * 
 * @return void
 */
function displayConfirmDeleteForm ()
{
    global $currentUserId, $book;

    displayHeader();

    $aid = (int)$_GET['delete'];
    $cat = cleanOutput($_GET['cat']);

    echo '
                <div class="info-alert">
                    <form action="addressbook.php?cat='.$cat.'&amp;delete='.$aid.'&amp;confirmed=1" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="addressbook.php?cat='.$cat.'&amp;address='.$aid.'">
                                '.T_('Cancel').'
                            </a>
                        </div>
                    </form>
                </div>';

    displayFooter();
}

/**
 * displayDeleteSubmit 
 * 
 * @return void
 */
function displayDeleteSubmit ()
{
    global $currentUserId, $book;

    $aid = (int)$_GET['delete'];
    $cat = $_GET['cat'];

    if (checkAccess($currentUserId) >= 2)
    {
        displayHeader();

        echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

        $book->displayAddressList($cat);
        displayFooter();
        return;
    }

    $sql = "SELECT a.`user`, u.`password`
            FROM `fcms_address` AS a, `fcms_users` AS u
            WHERE a.`id` = '$aid'
            AND a.`user` = u.`id`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $r = mysql_fetch_assoc($result);

    $user = $r['user'];
    $pass = $r['password'];

    if ($r['password'] !== 'NONMEMBER' && $r['password'] !== 'PRIVATE')
    {
        displayHeader();

        echo '
            <p class="error-alert">'.T_('You cannot delete the address of a member.').'</p>';

        $book->displayAddressList($cat);
        displayFooter();
        return;
    }

    $sql = "DELETE FROM `fcms_users` WHERE `id` = '$user'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "DELETE FROM fcms_address WHERE id = '$aid'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    displayAddressList();
    displayOkMessage(T_('Address Deleted Successfully.'));
    displayFooter();
}

/**
 * displayEditForm 
 * 
 * @return void
 */
function displayEditForm ()
{
    global $book;

    displayHeader();

    $id  = (int)$_GET['edit'];
    $cat = cleanOutput($_GET['cat']);

    $book->displayEditForm($id, 'addressbook.php?cat='.$cat.'&amp;address='.$id);
    displayFooter();
}

/**
 * displayAddForm 
 * 
 * @return void
 */
function displayAddForm ()
{
    global $currentUserId, $book;

    displayHeader();

    if (checkAccess($currentUserId) > 5)
    {
        echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

        displayFooter();
        return;
    }

    $book->displayAddForm();
    displayFooter();
}

/**
 * displayAddress 
 * 
 * @return void
 */
function displayAddress ()
{
    global $book;

    displayHeader();

    $address = (int)$_GET['address'];
    $cat     = 'all';

    if (isset($_GET['cat']))
    {
        $cat = $_GET['cat'];
    }

    $book->displayAddress($address, $cat);
    displayFooter();
}

/**
 * removeAlert 
 * 
 * @return void
 */
function removeAlert ()
{
    global $currentUserId;

    $alert = (int)$_GET['alert'];

    $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
            VALUES (
                '$alert', 
                '$currentUserId'
            )";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        exit();
    }
}

/**
 * displayAddressList 
 * 
 * @return void
 */
function displayAddressList ()
{
    global $alertObj, $book, $currentUserId;

    displayHeader();

    $cat = 'members';

    if (isset($_GET['cat']))
    {
        $cat = $_GET['cat'];
    }

    if (!$book->userHasAddress($currentUserId))
    {
        // Show Alerts
        $alertObj->displayAddress($currentUserId);
    }

    $book->displayAddressList($cat);

    displayFooter();
}
