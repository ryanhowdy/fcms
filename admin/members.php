<?php
/**
 * Members
 * 
 * PHP versions 4 and 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2010 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '../');

require URL_PREFIX.'fcms.php';

load('admin_members', 'database');

init('admin/');

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$memberObj     = new AdminMembers();

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Members'),
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
    global $currentUserId, $memberObj;

    if (checkAccess($currentUserId) > 1)
    {
        displayInvalidAccessLevel();
        return;
    }
    elseif (isset($_GET['create']))
    {
        displayHeader();
        $memberObj->displayCreateMemberForm();
        displayFooter();
    }
    elseif (isset($_POST['create']))
    {
        displayCreateSubmit();
    }
    elseif (isset($_GET['edit']))
    {
        displayHeader();
        $id = cleanInput($_GET['edit'], 'int');
        $memberObj->displayEditMemberForm($id);
        displayFooter();
    }
    elseif (isset($_GET['merge']))
    {
        displayHeader();
        $id = cleanInput($_GET['merge'], 'int');
        $memberObj->displayMergeMemberForm($id);
        displayFooter();
    }
    elseif (isset($_POST['merge-review']))
    {
        displayHeader();

        $id = cleanInput($_POST['id'], 'int');

        if ($_POST['merge-with'] < 1)
        {
            echo '
            <p class="error-alert">'.T_('You must choose a member to merge with.').'</p>';

            $memberObj->displayMergeMemberForm($id);
        }
        else
        {
            $merge = cleanInput($_POST['merge-with'], 'int');
            $memberObj->displayMergeMemberFormReview($id, $merge);
        }

        displayFooter();
    }
    elseif (isset($_POST['merge-submit']))
    {
        displayMergeSubmit();
    }
    elseif (isset($_POST['edit']))
    {
        displayEditSubmit();
    }
    elseif (isset($_POST['massupdate']))
    {
        if (isset($_POST['activateAll']))
        {
            displayActivateSubmit();
        }
        elseif (isset($_POST['inactivateAll']))
        {
            displayInactivateSubmit();
        }
        elseif (isset($_POST['deleteAll']) && !isset($_POST['confirmedall']))
        { 
            displayDeleteAllConfirmForm();
        }
        elseif (isset($_POST['delconfirmall']) || isset($_POST['confirmedall']))
        {
            displayDeleteAllSubmit();
        }
    }
    elseif (isset($_POST['delete']) && !isset($_POST['confirmed']))
    {
        displayDeleteConfirmForm();
    }
    elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed']))
    {
        displayDeleteSubmit();
    }
    elseif (isset($_POST['search']))
    {
        displayHeader();

        $first = cleanInput($_POST['fname']);
        $last  = cleanInput($_POST['lname']);
        $user  = cleanInput($_POST['uname']);

        $memberObj->displayMemberList(1, $first, $last, $user);

        displayFooter();
    }
    else
    {
        displayHeader();

        $page = isset($_GET['page']) ? cleanInput($_GET['page'], 'int') : 1;

        $memberObj->displayMemberList($page);

        displayFooter();
    }
}

/**
 * displayHeader 
 * 
 * @param string $js JavaScript to override default.
 * 
 * @return void
 */
function displayHeader ($js = '')
{
    global $currentUserId, $TMPL;

    $TMPL['javascript'] = $js;

    // Default js
    if ($js == '')
    {
        $TMPL['javascript'] = '
<script type="text/javascript" src="'.$TMPL['path'].'inc/js/livevalidation.js"></script>
<script type="text/javascript" src="'.$TMPL['path'].'inc/js/tablesort.js"></script>
<link rel="stylesheet" type="text/css" href="'.$TMPL['path'].'themes/datechooser.css"/>
<script type="text/javascript" src="'.$TMPL['path'].'inc/js/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
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
    }

    include_once getTheme($currentUserId).'header.php';

    echo '
        <div class="centercontent">';
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
        </div><!-- .centercontent -->';

    include_once getTheme($currentUserId).'footer.php';
}

/**
 * displayInvalidAccessLevel 
 * 
 * Display an error message for users who do not have admin access.
 * 
 * @return void
 */
function displayInvalidAccessLevel ()
{
    displayHeader();

    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 1 (Admin).').' 
                <a href="'.URL_PREFIX.'contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

    displayFooter();
}

/**
 * displayMergeSubmit 
 * 
 * Merges two members together.
 * 
 * @return void
 */
function displayMergeSubmit ()
{
    global $memberObj;

    displayHeader();

    $id    = cleanInput($_POST['id'], 'int');
    $merge = cleanInput($_POST['merge'], 'int');

    $year     = substr($_POST['birthday'], 0,4);
    $month    = substr($_POST['birthday'], 5,2);
    $day      = substr($_POST['birthday'], 8,2);

    // Update member
    $sql = "UPDATE `fcms_users`
            SET `fname` = '".cleanInput($_POST['fname'])."',
                `mname` = '".cleanInput($_POST['mname'])."',
                `lname` = '".cleanInput($_POST['lname'])."',
                `maiden` = '".cleanInput($_POST['maiden'])."',
                `bio` = '".cleanInput($_POST['bio'])."',
                `email` = '".cleanInput($_POST['email'])."',
                `dob_year` = '".cleanInput($year)."',
                `dob_month` = '".cleanInput($month)."',
                `dob_day` = '".cleanInput($day)."'
            WHERE `id` = '$id'";

    if (!mysql_query($sql))
    {
        displaySQLError('Merge Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    echo sprintf(T_pgettext('%s is a name of a table that gets updated.', 'Update [%s] complete.'), 'fcms_users').'<br/>';

    // Update member address
    $sql = "UPDATE `fcms_address`
            SET `address` = '".cleanInput($_POST['address'])."',
                `city` = '".cleanInput($_POST['city'])."',
                `state` = '".cleanInput($_POST['state'])."',
                `zip` = '".cleanInput($_POST['zip'])."',
                `home` = '".cleanInput($_POST['home'])."',
                `work` = '".cleanInput($_POST['work'])."',
                `cell` = '".cleanInput($_POST['cell'])."'
            WHERE `user` = '$id'";

    if (!mysql_query($sql))
    {
        displaySQLError('Merge Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    echo sprintf(T_pgettext('%s is a name of a table that gets updated.', 'Update [%s] complete.'), 'fcms_address').'<br/>';

    // Update all occurences of merge id with id
    $memberObj->mergeMember($id, $merge);

    // Delete merge id
    $sql = "DELETE FROM `fcms_users`
            WHERE `id` = '$merge'";

    if (!mysql_query($sql))
    {
        displaySQLError('Merge Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    echo sprintf(T_pgettext('%s is a name of a table that is deleted.', 'Delete [%s] complete.'), 'fcms_users').'<br/>';

    displayFooter();
}

/**
 * displayCreateSubmit 
 * 
 * @return void
 */
function displayCreateSubmit ()
{
    global $currentUserId, $memberObj;

    displayHeader();

    // Check Required Fields
    $requiredFields  = array('username', 'password', 'fname', 'lname', 'sex', 'email');
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
        $memberObj->displayCreateMemberForm(T_('Missing Required Field'));
        displayFooter();
        return;
    }

    // Check Email
    $sql = "SELECT `email` FROM `fcms_users` 
            WHERE `email` = '".cleanInput($_POST['email'])."'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Email Check Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $email_check = mysql_num_rows($result);

    if ($email_check > 0)
    {
        $memberObj->displayCreateMemberForm(
            sprintf(T_('The email address %s is already in use.  Please choose a different email.'), $_POST['email'])
        );
        displayFooter();
        return;
    }

    // birthday
    $year  = '';
    $month = '';
    $day   = '';

    if (!empty($_POST['year']))
    {
        $year = cleanInput($_POST['year'], 'int');
    }
    if (!empty($_POST['month']))
    {
        $month = cleanInput($_POST['month'], 'int');
        $month = str_pad($month, 2, "0", STR_PAD_LEFT);
    }
    if (!empty($_POST['day']))
    {
        $day = cleanInput($_POST['day'], 'int');
        $day = str_pad($day, 2, "0", STR_PAD_LEFT);
    }

    $fname    = cleanInput($_POST['fname']);
    $mname    = cleanInput($_POST['mname']);
    $lname    = cleanInput($_POST['lname']);
    $maiden   = cleanInput($_POST['maiden']);
    $sex      = cleanInput($_POST['sex']);
    $email    = cleanInput($_POST['email']);
    $username = cleanInput($_POST['username']);
    $password = cleanInput($_POST['password']);
    $md5pass  = md5($password);

    // Create new member
    $sql = "INSERT INTO `fcms_users`(
                `access`, `joindate`, `fname`, `mname`, `lname`, `maiden`, `sex`, `email`, `dob_year`, `dob_month`, `dob_day`,
                `username`, `password`, `activated`)
            VALUES (
                3, NOW(), '$fname', '$mname', '$lname', '$maiden', '$sex', '$email', '$year', '$month', '$day',
                '$username', '$md5pass', 1)";
    if (!mysql_query($sql))
    {
        displaySQLError('User Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $lastid = mysql_insert_id();

    // Create member's address
    $sql = "INSERT INTO `fcms_address`(`user`, `created_id`, `created`, `updated_id`, `updated`)
            VALUES ($lastid, '$currentUserId', NOW(), '$currentUserId', NOW())";
    if (!mysql_query($sql))
    {
        displaySQLError('Address Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    // Create member's settings
    $sql = "INSERT INTO `fcms_user_settings`(`user`) VALUES ($lastid)";
    if (!mysql_query($sql))
    {
        displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    // Email member
    if (isset($_POST['invite']))
    {
        $from     = getUserDisplayName($currentUserId, 2);
        $sitename = getSiteName();
        $subject  = sprintf(T_('Invitation to %s'), $sitename);

        $url = getDomainAndDir();
        $pos = strrpos($url, 'admin/');
        if ($pos !== false)
        {
            $url = substr($url, 0, $pos);
        }

        $message = $_POST['fname'].' '.$_POST['lname'].', 

'.sprintf(T_('You have been invited by %s to join %s.'), $from, $sitename).'

'.T_('You can login using the following information').':

'.T_('URL').': '.$url.'
'.T_('Username').': '.$username.' 
'.T_('Password').': '.$password.' 

'.T_('Thanks').',  
'.sprintf(T_('The %s Webmaster'), $sitename).'

'.T_('This is an automated response, please do not reply.');

        mail($_POST['email'], $subject, $message, getEmailHeaders());
    }

    $memberObj->displayMemberList(1);
    displayOkMessage();
    displayFooter();
}

/**
 * displayEditSubmit 
 * 
 * @return void
 */
function displayEditSubmit ()
{
    global $memberObj;

    displayHeader();

    // Check required fields
    $requiredFields  = array('fname', 'lname', 'sex', 'email');
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
        $memberObj->displayEditMemberForm(
            $_POST['id'],
            '<p class="error">'.T_('Missing Required Field').'</p>'
        );
    }

    $id = cleanInput($_POST['id'], 'int');

    $emailstart = $memberObj->getUsersEmail($id);

    // birthday
    $year  = '';
    $month = '';
    $day   = '';

    if (!empty($_POST['year']))
    {
        $year = cleanInput($_POST['year'], 'int');
    }
    if (!empty($_POST['month']))
    {
        $month = cleanInput($_POST['month'], 'int');
        $month = str_pad($month, 2, "0", STR_PAD_LEFT);
    }
    if (!empty($_POST['day']))
    {
        $day = cleanInput($_POST['day'], 'int');
        $day = str_pad($day, 2, "0", STR_PAD_LEFT);
    }

    // Update user info
    $sql = "UPDATE `fcms_users` SET 
                `fname` = '".cleanInput($_POST['fname'])."', 
                `lname` = '".cleanInput($_POST['lname'])."', 
                `sex` = '".cleanInput($_POST['sex'])."', ";

    if (isset($_POST['email']) && $_POST['email'] != $emailstart)
    {
        $email_sql = "SELECT `email` 
                      FROM `fcms_users` 
                      WHERE `email` = '".cleanInput($_POST['email'])."'";

        $result = mysql_query($email_sql);
        if (!$result)
        {
            displaySQLError('Email Check Error', __FILE__.' ['.__LINE__.']', $email_sql, mysql_error());
            displayFooter();
            return;
        }

        $email_check = mysql_num_rows($result);

        if ($email_check > 0)
        { 
            $memberObj->displayEditMemberForm(
                $_POST['id'],
                '<p class="error-alert">'.sprintf(T_('The email address %s is already in use.  Please choose a different email.'), $_POST['email']).'</p>'
            );
            exit();
        }

        $sql .= "email = '".cleanInput($_POST['email'])."', ";
    }

    if ($_POST['password'])
    {
        $sql .= "`password` = '".md5($_POST['password'])."', ";

        $sitename = getSiteName();
        $subject  = getSiteName().': '.T_('Password Change');

        $message = $_POST['fname'].' '.$_POST['lname'].', 

'.sprintf(T_('Your password at %s has been changed by the administrator.'), $sitename).'

'.sprintf(T_('Your new password is %s'), $_POST['password']);
        mail($_POST['email'], $subject, $message, getEmailHeaders());
    }

    $sql.= "`dob_year` = '$year', 
            `dob_month` = '$month',
            `dob_day` = '$day',
            `joindate` = NOW(), 
            `access` = '".cleanInput($_POST['access'], 'int')."'
            WHERE id = '".cleanInput($_POST['id'], 'int')."'";
    if (!mysql_query($sql))
    {
        displaySQLError('Edit Member Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayOkMessage();
    $memberObj->displayMemberList(1);
    displayFooter();
}

/**
 * displayActivateSubmit 
 * 
 * @return void
 */
function displayActivateSubmit ()
{
    global $memberObj;

    displayHeader();

    // Get list of new members -- members with no activity and not activated
    $sql = "SELECT `id`, `activity`, `fname`, `lname`, `email` 
            FROM `fcms_users` 
            WHERE `activity` = '0000-00-00 00:00:00'
            AND `activated` = 0";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Members Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    while ($r = mysql_fetch_array($result))
    {
        $new_members[$r['id']] = $r;
    }

    // Loop through selected members
    foreach ($_POST['massupdate'] AS $id)
    {
        // Activate the member
        $sql = "UPDATE `fcms_users` 
                SET `activated` = 1 
                WHERE `id` = '".cleanInput($id, 'int')."'";

        if (!mysql_query($sql))
        {
            displaySQLError('Activate Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        if (isset($new_members))
        { 
            // If they are a new member, then reset the joindate, and send email
            if (array_key_exists($id, $new_members))
            {
                $sql = "UPDATE `fcms_users` 
                        SET `joindate` = NOW() 
                        WHERE `id` = '".cleanInput($id, 'int')."'";

                if (!mysql_query($sql))
                {
                    displaySQLError('Activate Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                    displayFooter();
                    return;
                }

                $sitename = getSiteName();
                $subject  = getSiteName().': '.T_('Account Activated');

                $message = $new_members[$id]['fname'].' '.$new_members[$id]['lname'].', 

'.sprintf(T_('Your account at %s has been activated by the administrator.'), $sitename);

                mail($new_members[$id]['email'], $subject, $message, getEmailHeaders());
            }
        }
    }

    displayOkMessage();
    $memberObj->displayMemberList(1);
    displayFooter();
}

/**
 * displayInactivateSubmit 
 * 
 * @return void
 */
function displayInactivateSubmit ()
{
    global $memberObj;

    displayHeader();

    foreach ($_POST['massupdate'] AS $id)
    {
        $sql = "UPDATE `fcms_users` 
                SET `activated` = 0 
                WHERE `id` = '".cleanInput($id, 'int')."'";
        if (!mysql_query($sql))
        {
            displaySQLError('Inactivate Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }
    }

    displayOkMessage();
    $memberObj->displayMemberList(1);
    displayFooter();
}

/**
 * displayDeleteAllConfirmForm 
 * 
 * @return void
 */
function displayDeleteAllConfirmForm ()
{
    echo '
                <div class="info-alert clearfix">
                    <form action="members.php" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>';
    foreach ($_POST['massupdate'] AS $id)
    {
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
}

/**
 * displayDeleteAllSubmit 
 * 
 * @return void
 */
function displayDeleteAllSubmit ()
{
    global $memberObj;

    displayHeader();

    foreach ($_POST['massupdate'] AS $id)
    {
        $sql = "DELETE FROM `fcms_users` 
                WHERE `id` = '".cleanInput($id, 'int')."'";
        if (!mysql_query($sql))
        {
            displaySQLError('Delete Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }
    }

    displayOkMessage();
    $memberObj->displayMemberList(1);
    displayFooter();
}

/**
 * displayDeleteConfirmForm 
 * 
 * @return void
 */
function displayDeleteConfirmForm ()
{
    displayHeader();

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

    displayFooter();
}

/**
 * displayDeleteSubmit 
 * 
 * @return void
 */
function displayDeleteSubmit ()
{
    global $memberObj;

    displayHeader();

    $id = cleanInput($_POST['id'], 'int');

    $sql = "DELETE FROM `fcms_users` 
            WHERE `id` = '$id'";
    if (!mysql_query($sql))
    {
        displaySQLError('Delete Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    displayOkMessage();
    $memberObj->displayMemberList(1);
    displayFooter();
}
