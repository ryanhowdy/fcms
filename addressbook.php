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
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('datetime', 'addressbook', 'alerts', 'phone', 'address');

init();

$book  = new AddressBook($fcmsError, $fcmsDatabase, $fcmsUser);
$alert = new Alerts($fcmsError, $fcmsDatabase, $fcmsUser);
$page  = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $book, $alert);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsBook;
    private $fcmsAlert;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsBook, $fcmsAlert)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
        $this->fcmsBook     = $fcmsBook;
        $this->fcmsAlert    = $fcmsAlert;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Address Book'),
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        $this->control();
    }

    /**
     * control 
     * 
     * The controlling structure for this script.
     * 
     * @return void
     */
    function control ()
    {
        if (isset($_GET['alert']))
        {
            $this->removeAlert();
        }

        if (isset($_GET['csv']))
        {
            if ($_GET['csv'] == 'export')
            {
                $this->displayExportSubmit();
            }
            elseif (isset($_POST['import']))
            {
                $this->displayHeader();
                $this->fcmsBook->importAddressCsv($_FILES['csv']);
                $this->displayFooter();
            }
            else
            {
                $this->displayHeader();
                $this->fcmsBook->displayImportForm();
                $this->displayFooter();
            }
        }
        elseif (isset($_POST['emailsubmit']))
        {
            $this->displayMassEmailForm();
        }
        elseif (isset($_POST['sendemailsubmit']))
        {
            $this->displayMassEmailSubmit();
        }
        elseif (isset($_GET['delete']))
        {
            if (!isset($_GET['confirmed']))
            {
                $this->displayConfirmDeleteForm();
            }
            elseif (isset($_POST['delconfirm']) || isset($_GET['confirmed']))
            {
                $this->displayDeleteSubmit();
            }
        }
        elseif (isset($_GET['edit']))
        {
            $this->displayEditForm();
        }
        elseif (isset($_POST['editsubmit']))
        {
            $this->displayEditSubmit();
        }
        elseif (isset($_GET['add']))
        {
            $this->displayAddForm();
        }
        elseif (isset($_POST['addsubmit']))
        {
            $this->displayAddSubmit();
        }
        elseif (isset($_GET['address']))
        {
            $this->displayAddress();
        }
        else
        {
            $this->displayAddressList();
        }
    }

    /**
     * displayHeader 
     * 
     * @return void
     */
    function displayHeader ()
    {
        $TMPL = $this->fcmsTemplate;

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

        include_once getTheme($this->fcmsUser->id).'header.php';

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
        $TMPL = $this->fcmsTemplate;

        echo '
        </div><!-- /centercontent -->';

        include_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayExportSubmit 
     * 
     * @return void
     */
    function displayExportSubmit ()
    {
        $sql = "SELECT `lname`, `fname`, `address`, `city`, `state`, `zip`, `email`, `home`, `work`, `cell` 
                FROM `fcms_address` AS a, `fcms_users` AS u 
                WHERE a.`user` = u.`id` 
                ORDER BY `lname`, `fname`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $csv = "lname, fname, address, city, state, zip, email, home, work, cell\015\012";

        foreach ($rows as $row)
        {
            $csv .= '"'.join('","', str_replace('"', '""', $row))."\"\015\012";
        }

        $date = fixDate('Y-m-d', $this->fcmsUser->tzOffset);

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
        $massEmails = $_POST['massemail'];

        $this->displayHeader();

        if ($this->fcmsUser->access > 3)
        {
            echo '
                <p class="error-alert">
                    '.T_('You do not have permission to perform this task.  You must have an access level of 3 (Member) or higher.').'
                </p>';

            $this->displayFooter();
            return;
        }

        if (empty($massEmails))
        {
            echo '
            <p class="error-alert">
                '.T_('You must choose at least one member to email.').' 
                <a href="help.php#address-massemail">'.T_('Get more help on sending mass emails.').'</a>
            </p>';

            $this->displayFooter();
            return;
        }

        $this->fcmsBook->displayMassEmailForm($massEmails);
        $this->displayFooter();
    }

    /**
     * displayMassEmailSubmit 
     * 
     * @return void
     */
    function displayMassEmailSubmit ()
    {
        $this->displayHeader();

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
            $this->fcmsBook->displayMassEmailForm(
                $_POST['emailaddress'], 
                $_POST['email'], 
                $_POST['name'], 
                $_POST['subject'], 
                $_POST['msg'], 
                'Yes'
            );
            $this->displayFooter();

            return;
        }

        $emailHeaders = getEmailHeaders($_POST['name'], $_POST['email']);

        foreach ($_POST['emailaddress'] as $email)
        {
            mail($email, $_POST['subject'], $_POST['msg']."\r\n-".$_POST['name'], $emailHeaders);
        }

        displayOkMessage(T_('Email has been sent.'));
        $this->fcmsBook->displayAddressList('members');

        $this->displayFooter();
    }

    /**
     * displayEditSubmit 
     * 
     * @return void
     */
    function displayEditSubmit ()
    {
        $this->displayHeader();

        $aid = (int)$_POST['aid'];
        $uid = (int)$_POST['uid'];
        $cat = $_POST['cat'];

        $country = strip_tags($_POST['country']);
        $address = strip_tags($_POST['address']);
        $city    = strip_tags($_POST['city']);
        $state   = strip_tags($_POST['state']);
        $zip     = strip_tags($_POST['zip']);
        $home    = strip_tags($_POST['home']);
        $work    = strip_tags($_POST['work']);
        $cell    = strip_tags($_POST['cell']);
        $email   = strip_tags($_POST['email']);

        // Get current address and email
        $sql = "SELECT a.`country`, a.`address`, a.`city`, a.`state`, a.`zip`, a.`home`, a.`work`, a.`cell`, u.`email`
                FROM `fcms_address` AS a
                LEFT JOIN `fcms_users` AS u ON a.`user` = u.`id`
                WHERE a.`id` = ? 
                AND a.`user` = ?";

        $row = $this->fcmsDatabase->getRow($sql, array($aid, $uid));
        if ($row === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

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
                    `updated_id` = ?,
                    `country`    = ?,
                    `address`    = ?,
                    `city`       = ?,
                    `state`      = ?,
                    `zip`        = ?,
                    `home`       = ?,
                    `work`       = ?,
                    `cell`       = ?
                WHERE `id` = ?";

        $params = array($this->fcmsUser->id, $country, $address, $city, $state, $zip, $home, $work, $cell, $aid);

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Save Email
        $sql = "UPDATE `fcms_users` 
                SET `email` = ?
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->update($sql, array($email, $uid)))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Update changelog
        $sql = "INSERT INTO `fcms_changelog` (`user`, `table`, `column`, `created`)
                VALUES ";

        $params = array();

        foreach ($changes as $column)
        {
            $sql .= "(?, 'address', ?, NOW()),";

            array_push($params, $uid, $column);
        }
        $sql = substr($sql, 0, -1); // remove extra comma

        if (count($changes) > 0)
        {
            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }
        }

        displayOkMessage();
        $this->fcmsBook->displayAddress($aid, $cat);
        $this->displayFooter();
    }

    /**
     * displayAddSubmit 
     * 
     * @return void
     */
    function displayAddSubmit ()
    {
        $this->displayHeader();

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

        $pw = 'NONMEMBER';

        if (isset($_POST['private']))
        {
            $pw = 'PRIVATE';
        }

        $sql = "INSERT INTO `fcms_users` (
                    `access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`
                ) VALUES (
                    ?, '0000-00-00 00:00:00', ?, ?, ?, ?, ?
                )";

        $id = $this->fcmsDatabase->insert($sql, array('10', $fname, $lname, $email, 'NONMEMBER-'.$uniq, $pw));

        if ($id === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $sql = "INSERT INTO `fcms_address`(
                    `user`, `created_id`, `created`, `updated_id`, `updated`, 
                    `country`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell`
                ) VALUES (
                    ?, ?, NOW(), ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?
                )";

        $params = array(
            $id, 
            $this->fcmsUser->id, 
            $this->fcmsUser->id, 
            $country, 
            $address, 
            $city, 
            $state, 
            $zip, 
            $home, 
            $work, 
            $cell
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        displayOkMessage();
        $this->fcmsBook->displayAddressList('non');
        $this->displayFooter();
    }

    /**
     * displayConfirmDeleteForm 
     * 
     * @return void
     */
    function displayConfirmDeleteForm ()
    {
        $this->displayHeader();

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

        $this->displayFooter();
    }

    /**
     * displayDeleteSubmit 
     * 
     * @return void
     */
    function displayDeleteSubmit ()
    {
        $aid = (int)$_GET['delete'];
        $cat = $_GET['cat'];

        if ($this->fcmsUser->access >= 2)
        {
            $this->displayHeader();

            echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

            $this->fcmsBook->displayAddressList($cat);
            $this->displayFooter();
            return;
        }

        $sql = "SELECT a.`user`, u.`password`
                FROM `fcms_address` AS a, `fcms_users` AS u
                WHERE a.`id` = ?
                AND a.`user` = u.`id`";

        $r = $this->fcmsDatabase->getRow($sql, $aid);
        if ($r === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $user = $r['user'];
        $pass = $r['password'];

        if ($r['password'] !== 'NONMEMBER' && $r['password'] !== 'PRIVATE')
        {
            $this->displayHeader();

            echo '
            <p class="error-alert">'.T_('You cannot delete the address of a member.').'</p>';

            $this->fcmsBook->displayAddressList($cat);
            $this->displayFooter();

            return;
        }

        $sql = "DELETE FROM `fcms_users` 
                WHERE `id` = ?";
        if (!$this->fcmsDatabase->delete($sql, $user))
        {
            $this->displayHeader();
            $this->fcmsDatabase->displayError();
            $this->displayFooter();

            return;
        }

        $sql = "DELETE FROM fcms_address 
                WHERE id = ?";
        if (!$this->fcmsDatabase->delete($sql, $aid))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $this->displayAddressList();
        displayOkMessage(T_('Address Deleted Successfully.'));
        $this->displayFooter();
    }

    /**
     * displayEditForm 
     * 
     * @return void
     */
    function displayEditForm ()
    {
        $this->displayHeader();

        $id  = (int)$_GET['edit'];
        $cat = cleanOutput($_GET['cat']);

        $this->fcmsBook->displayEditForm($id, 'addressbook.php?cat='.$cat.'&amp;address='.$id);
        $this->displayFooter();
    }

    /**
     * displayAddForm 
     * 
     * @return void
     */
    function displayAddForm ()
    {
        $this->displayHeader();

        if ($this->fcmsUser->access > 5)
        {
            echo '
            <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';

            $this->displayFooter();
            return;
        }

        $this->fcmsBook->displayAddForm();
        $this->displayFooter();
    }

    /**
     * displayAddress 
     * 
     * @return void
     */
    function displayAddress ()
    {
        $this->displayHeader();

        $address = (int)$_GET['address'];
        $cat     = 'all';

        if (isset($_GET['cat']))
        {
            $cat = $_GET['cat'];
        }

        $this->fcmsBook->displayAddress($address, $cat);
        $this->displayFooter();
    }

    /**
     * removeAlert 
     * 
     * @return void
     */
    function removeAlert ()
    {
        $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
                VALUES (?, ?)";

        if (!$this->fcmsDatabase->insert($sql, array($_GET['alert'], $this->fcmsUser->id)))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
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
        $this->displayHeader();

        $cat = 'members';

        if (isset($_GET['cat']))
        {
            $cat = $_GET['cat'];
        }

        if (!$this->fcmsBook->userHasAddress($this->fcmsUser->id))
        {
            // Show Alerts
            $this->fcmsAlert->displayAddress($this->fcmsUser->id);
        }

        $this->fcmsBook->displayAddressList($cat);

        $this->displayFooter();
    }
}
