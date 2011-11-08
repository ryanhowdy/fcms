<?php
/**
 * Profile
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

load('profile');

init();

$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$profile       = new Profile($currentUserId);
$awards        = new Awards($currentUserId);

// Changing Avatar with Advanced Uploader
if (isset($_GET['advanced-avatar']))
{
    $filetypes = array(
        'image/pjpeg'   => 'jpg', 
        'image/jpeg'    => 'jpg', 
        'image/gif'     => 'gif', 
        'image/bmp'     => 'bmp', 
        'image/x-png'   => 'png', 
        'image/png'     => 'png'
    );

    $type      = $_FILES['avatar']['type'];
    $extention = $filetypes[$type];
    $id        = uniqid("");
    $name      = $id.".".$extention;

    $sql = "UPDATE `fcms_users`
            SET `avatar` = '".$name."'
            WHERE `id` = '$currentUserId'";
    if (!mysql_query($sql))
    {
        echo "FAILURE: Could not update db with new avatar.\n";
        exit();
    }

    $sql = "INSERT INTO `fcms_changelog` (`user`, `table`, `column`, `created`)
            VALUES ('$currentUserId', 'users', 'avatar', NOW())";
    if (!mysql_query($sql))
    {
        echo "FAILURE: Could not update db with changelog details.\n";
        exit();
    }

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/avatar/'.$name))
    {
        echo "success";
    }
    else
    {
        echo "FAILURE: Could not move avatar file.\n";
    }
    exit();
}

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Profile'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    initGravatar();
});
//]]>
</script>';

// Show Header
require_once getTheme($currentUserId).'header.php';

echo '
        <div id="profile" class="centercontent">';

// Invalid permissions
if (checkAccess($currentUserId) == 11)
{
    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                <a href="contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

    displayFooter();
    exit();
}

// Show awards
if (isset($_GET['award']) && isset($_GET['member']))
{
    $memberid = cleanInput($_GET['member'], 'int');
    $id       = cleanInput($_GET['award'], 'int');
    $awards->displayAward($memberid, $id);
}
// Show profile
elseif (isset($_GET['member']))
{
    $memberid = cleanInput($_GET['member'], 'int');
    $profile->displayProfile($memberid);
}
// Submit form
elseif (isset($_POST['submit']) && isset($_GET['view']))
{
    // Save Basic info
    if ($_GET['view'] == 'info')
    {
        $sql = "UPDATE `fcms_users`
                SET `fname` = '".cleanInput($_POST['fname'])."',
                    `lname` = '".cleanInput($_POST['lname'])."',
                    `sex` = '".cleanInput($_POST['sex'])."', ";

        if ($_POST['mname'])
        {
            $sql .= "`mname` = '".cleanInput($_POST['mname'])."', ";
        }
        if ($_POST['maiden'])
        {
            $sql .= "`maiden` = '".cleanInput($_POST['maiden'])."', ";
        }
        if ($_POST['bio'])
        {
            $sql .= "`bio` = '".cleanInput($_POST['bio'])."', ";
        }

        $year     = cleanInput($_POST['syear'], 'int');
        $month    = cleanInput($_POST['smonth'], 'int'); 
        $month    = str_pad($month, 2, "0", STR_PAD_LEFT);
        $day      = cleanInput($_POST['sday'], 'int');
        $day      = str_pad($day, 2, "0", STR_PAD_LEFT);

        $sql .= "`dob_year` = '$year',
                 `dob_month` = '$month',
                 `dob_day` = '$day'
                WHERE id = '$currentUserId'";

        if (!mysql_query($sql))
        {
            displaySQLError('Update User Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        echo '
            <p class="ok-alert">'.T_('Changes Updated Successfully').'</p>
            <p><a href="profile.php?view=info">'.T_('Continue').'</a></p>';

        displayFooter();
        return;
    }

    // Save profile picture
    if ($_GET['view'] == 'picture')
    {
        $sql = "UPDATE `fcms_users` SET ";

        // Avatar uploads
        if ($_POST['avatar_type'] == 'fcms')
        {
            if ($_FILES['avatar']['error'] < 1)
            {
                $img->destination  = 'uploads/avatar/';
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
                    return;
                }

                $img->resize(80, 80);

                if ($img->error > 0)
                {
                    echo '
                    <p class="error-alert">
                        '.T_('There was an error uploading your avatar.').'
                    </p>';

                    displayFooter();
                    return;
                }

                $sql .= "`avatar` = '".$img->name."'";

                if ($_POST['avatar_orig'] != 'no_avatar.jpg' && $_POST['avatar_orig'] != 'gravatar')
                {
                    unlink("uploads/avatar/".basename($_POST['avatar_orig']));
                }

            } else {
                $sql .= "`avatar` = `avatar`";
            }
        }
        // Avatar Gravatar
        else if ($_POST['avatar_type'] == 'gravatar')
        {
            $sql .= "`avatar` = 'gravatar', `gravatar` = '".cleanInput($_POST['gravatar_email'])."'";

            if ($_POST['avatar_orig'] != 'no_avatar.jpg' && $_POST['avatar_orig'] != 'gravatar')
            {
                unlink("uploads/avatar/".basename($_POST['avatar_orig']));
            }
        }
        // Avatar default
        else
        {
            $sql .= "`avatar` = 'no_avatar.jpg'";
        }

        $sql .= "WHERE `id` = '$currentUserId'";
        if (!mysql_query($sql))
        {
            displaySQLError('Update User Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            return;
        }

        echo '
            <p class="ok-alert">'.T_('Changes Updated Successfully').'</p>
            <p><a href="profile.php?view=picture">'.T_('Continue').'</a></p>';
        displayFooter();
        return;
    }
}
// Advanced Photo success
elseif (isset($_GET['view']) && $_GET['view'] == 'advanced-picture')
{
    if ($_GET['avatar_orig'] != 'no_avatar.jpg' && $_GET['avatar_orig'] != 'gravatar')
    {
        unlink("uploads/avatar/".basename($_GET['avatar_orig']));
    }

    echo '
                <p class="ok-alert">'.T_('Changes Updated Successfully').'</p>
                <p><a href="profile.php?view=picture">'.T_('Continue').'</a></p>';
    displayFooter();
    return;
}
// Save Address info
elseif (isset($_POST['editsubmit']))
{
    $sql = "UPDATE `fcms_address` 
            SET `updated`=NOW(), 
                `address`   = '".cleanInput($_POST['address'])."', 
                `city`      = '".cleanInput($_POST['city'])."', 
                `state`     = '".cleanInput($_POST['state'])."', 
                `zip`       = '".cleanInput($_POST['zip'])."', 
                `home`      = '".cleanInput($_POST['home'])."', 
                `work`      = '".cleanInput($_POST['work'])."', 
                `cell`      = '".cleanInput($_POST['cell'])."' 
            WHERE `id` = '".cleanInput($_POST['aid'], 'int')."'";
    if (!mysql_query($sql))
    {
        displaySQLError('Edit Address Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "UPDATE `fcms_users`
            SET `email`='".cleanInput($_POST['email'])."'
            WHERE `id` = '".cleanInput($_POST['uid'])."'";
    if (!mysql_query($sql))
    {
        displaySQLError('Edit Email Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        return;
    }

    echo '
                <p class="ok-alert">'.T_('Changes Updated Successfully').'</p>
                <p><a href="profile.php?view=address">'.T_('Continue').'</a></p>';
    displayFooter();
    return;
}
// View Forms
elseif (isset($_GET['view']))
{
    switch ($_GET['view'])
    {
        case 'info':
        default:
            $profile->displayEditBasicInfo();
            break;

        case 'picture':
            $profile->displayEditProfilePicture();
            break;

        case 'address':
            $profile->displayEditAddress();
            break;
    }
    displayFooter();
    return;
}
else
{
    $profile->displayEditProfile();
}

displayFooter();
return;

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter()
{
    global $currentUserId, $TMPL;

    echo '
        </div><!-- #profile .centercontent -->';

    include_once getTheme($currentUserId).'footer.php';
}
