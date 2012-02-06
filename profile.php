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

load('profile', 'image', 'address', 'phone');

init();

// Globals
$currentUserId = (int)$_SESSION['login_id'];
$profile       = new Profile($currentUserId);
$awards        = new Awards($currentUserId);
$img           = new Image($currentUserId);

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Profile'),
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
    global $currentUserId;

    if (checkAccess($currentUserId) == 11)
    {
        displayInvalidPermission();
    }
    elseif (isset($_GET['advanced-avatar']))
    {
        displayAdvancedAvatarUploadSubmit();
    }
    // View Profile
    elseif (isset($_GET['member']))
    {
        if (isset($_GET['view']))
        {
            if ($_GET['view'] == 'awards')
            {
                displayAwards();
            }
            elseif ($_GET['view'] == 'contributions')
            {
                displayContributions();
            }
            elseif ($_GET['view'] == 'participation')
            {
                displayParticipation();
            }
            else
            {
                displayProfile();
            }
        }
        elseif (isset($_GET['award']))
        {
            displayAward();
        }
        else
        {
            displayProfile();
        }
    }
    // Save Profile
    elseif (isset($_POST['submit']) && isset($_GET['view']))
    {
        if ($_GET['view'] == 'info')
        {
            displayEditProfileInfoFormSubmit();
        }
        elseif ($_GET['view'] == 'picture')
        {
            displayEditProfilePictureFormSubmit();
        }
        else
        {
            header("Location: profile.php");
        }
    }
    elseif (isset($_POST['editsubmit']))
    {
        displayEditProfileAddressFormSubmit();
    }
    // Edit Profile
    elseif (isset($_GET['view']))
    {
        if ($_GET['view'] == 'info')
        {
            displayEditProfileInfoForm();
        }
        elseif ($_GET['view'] == 'picture')
        {
            displayEditProfilePictureForm();
        }
        elseif ($_GET['view'] == 'address')
        {
            displayEditProfileAddressForm();
        }
        else
        {
            displayEditProfileForm();
        }
    }
    else
    {
        displayEditProfileForm();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ($memberId = 0)
{
    global $currentUserId, $TMPL;

    $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    initGravatar();
});
//]]>
</script>';

    require_once getTheme($currentUserId).'header.php';

    echo '
        <div id="profile" class="centercontent">';

    if ($memberId > 0)
    {
        $sql = "SELECT `fname`, `lname`, `username`, `email`
                FROM `fcms_users`
                WHERE `id` = '$memberId'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        $row = mysql_fetch_assoc($result);

        echo '
            <div id="leftcolumn">
                <div id="avatar">
                    <img class="avatar" src="'.getCurrentAvatar($memberId).'" alt="avatar"/>
                </div>
                <div id="contact-buttons">
                    <ul>
                        <li><a class="action" href="privatemsg.php?compose=new&amp;id='.$memberId.'">'.T_('Send PM').'</a></li>
                        <li><a class="action" href="mailto:'.$row['email'].'">'.T_('Send Email').'</a></li>
                    </ul>
                </div>
            </div><!-- /leftcolumn -->

            <div id="maincolumn">
                <div class="name">
                    <h1>'.cleanOutput($row['fname']).' '.cleanOutput($row['lname']).'</h1>
                    <h2>'.cleanOutput($row['username']).'</h2>
                </div>
                <div id="sections_menu" class="clearfix">
                    <ul>
                        <li><a href="?member='.$memberId.'">'.T_('Profile').'</a></li>
                        <li><a href="?member='.$memberId.'&amp;view=awards">'.T_('Awards').'</a></li>
                        <li><a href="?member='.$memberId.'&amp;view=contributions">'.T_('Contributions').'</a></li>
                        <li><a href="?member='.$memberId.'&amp;view=participation">'.T_('Participation').'</a></li>
                    </ul>
                </div>';
    }
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ($memberId = 0)
{
    global $currentUserId, $TMPL;

    if ($memberId > 0)
    {
        echo '
            </div><!-- /maincolumn -->';
    }

    echo '
        </div><!-- /profile -->';

    include_once getTheme($currentUserId).'footer.php';
}

/**
 * displayAdvancedAvatarUploadSubmit 
 * 
 * @return void
 */
function displayAdvancedAvatarUploadSubmit ()
{
    global $currentUserId;

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
        logError(__FILE__.' ['.__LINE__.'] - Could not update db with new avatar.');
        echo "FAILURE: Could not update db with new avatar.\n";
        exit();
    }

    $sql = "INSERT INTO `fcms_changelog` (`user`, `table`, `column`, `created`)
            VALUES ('$currentUserId', 'users', 'avatar', NOW())";
    if (!mysql_query($sql))
    {
        logError(__FILE__.' ['.__LINE__.'] - Could not update db with changelog details.');
        echo "FAILURE: Could not update db with changelog details.\n";
        exit();
    }

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/avatar/'.$name))
    {
        echo "success";
    }
    else
    {
        logError(__FILE__.' ['.__LINE__.'] - Could not move avatar file.');
        echo "FAILURE: Could not move avatar file.\n";
        exit();
    }

    if ($_GET['orig'] != 'no_avatar.jpg' && $_GET['orig'] != 'gravatar')
    {
        if (file_exists("uploads/avatar/".basename($_GET['orig'])))
        {
            unlink("uploads/avatar/".basename($_GET['orig']));
        }
    }

    $_SESSION['success'] = 1;

    exit();
}

/**
 * displayInvalidPermission 
 * 
 * @return void
 */
function displayInvalidPermission ()
{
    displayHeader();

    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                <a href="contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

    displayFooter();
}

/**
 * displayProfile 
 * 
 * @return void
 */
function displayProfile ()
{
    $memberId = (int)$_GET['member'];

    displayHeader($memberId);

    $sql = "SELECT u.fname, u.lname, u.email, u.`bio`, u.`dob_year`, u.`dob_month`, u.`dob_day`, 
                u.`dod_year`, u.`dod_month`, u.`dod_day`, u.avatar, u.username, u.joindate, 
                u.`activity`, u.`sex`, a.`id` AS aid, a.`address`, a.`city`, a.`state`, a.`zip`, 
                a.`home`, a.`cell`, a.`work`  
            FROM fcms_users AS u, fcms_address AS a 
            WHERE u.id = '$memberId' 
            AND u.id = a.user";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $row = mysql_fetch_assoc($result);

    $tzOffset     = getTimezone($memberId);
    $joinDate     = fixDate(T_('F j, Y'), $tzOffset, $row['joindate']);
    $address      = formatAddress($row);
    $contact      = '';
    $activityDate = T_('Never visited');

    // Phone
    if (!empty($row['home']))
    {
        $contact .= '<p><span>'.T_pgettext('The beginning or starting place.', 'Home').'</span> '.formatPhone($row['home']).'</p>';
    }
    if (!empty($row['work']))
    {
        $contact .= '<p><span>'.T_('Work').'</span> '.formatPhone($row['work']).'</p>';
    }
    if (!empty($row['cell']))
    {
        $contact .= '<p><span>'.T_('Cell').'</span> '.formatPhone($row['cell']).'</p>';
    }

    // Activity
    if ($row['activity'] != '0000-00-00 00:00:00')
    {
        $activityDate = fixDate(T_('F j, Y g:i a'), $tzOffset, $row['activity']);
    }

    echo '
            <ul id="profile-data" class="clearfix">
                <li>
                    <b>'.T_('Bio').'</b>
                    <div>'.cleanOutput($row['bio']).'</div>
                </li>
                <li>
                    <b>'.T_('Address').'</b>
                    <div>'.$address.'</div>
                </li>
                <li>
                    <b>'.T_('Contact').'</b>
                    <div>'.$contact.'</div>
                </li>
                <li>
                    <b>'.T_('Join Date').'</b>
                    <div>'.$joinDate.'</div>
                </li>
                <li>
                    <b>'.T_('Last Visit').'</b>
                    <div>'.$activityDate.'</div>
                </li>
            </ul>';

    displayFooter($memberId);
}

/**
 * displayAwards 
 * 
 * @return void
 */
function displayAwards ()
{
    global $awards;

    $memberId = (int)$_GET['member'];

    displayHeader($memberId);

    $awards->displayAwards($memberId);

    displayFooter($memberId);
}

/**
 * displayAward 
 * 
 * @return void
 */
function displayAward ()
{
    global $awards;

    $memberId = (int)$_GET['member'];
    $type     = $_GET['award'];

    displayHeader($memberId);

    $awards->displayAward($memberId, $type);

    displayFooter($memberId);
}

/**
 * displayContributions 
 * 
 * @return void
 */
function displayContributions ()
{
    $memberId = (int)$_GET['member'];

    displayHeader($memberId);

    displayLatestMessageBoardPosts($memberId);
    displayLatestPhotoGalleryPhotos($memberId);

    displayFooter($memberId);
}

/**
 * displayLatestMessageBoardPosts 
 * 
 * @param int $memberId 
 * 
 * @return void
 */
function displayLatestMessageBoardPosts ($memberId)
{
    $memberId = (int)$memberId;

    $sql = "SELECT t.`id`, `subject`, `date`, `post` 
            FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, `fcms_users` AS u 
            WHERE t.`id` = p.`thread` 
            AND p.`user` = u.`id` 
            AND u.`id` = '$memberId' 
            ORDER BY `date` DESC 
            LIMIT 0, 5";

    $result = mysql_query ($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return;
    }

    echo '
            <h2>'.T_('Latest Posts').'</h2>';

    $tzOffset = getTimezone($memberId);

    while ($row = mysql_fetch_assoc($result))
    {
        $date    = fixDate(T_('F j, Y, g:i a'), $tzOffset, $row['date']);
        $subject = $row['subject'];
        $post    = removeBBCode($row['post']);
        $post    = cleanOutput($post);
        $pos     = strpos($subject, '#ANOUNCE#');

        if ($pos !== false)
        {
            $subject = substr($subject, 9, strlen($subject)-9);
        }

        $subject = cleanOutput($subject);

        echo '
                <p>
                    <a href="messageboard.php?thread='.$row['id'].'">'.$subject.'</a> 
                    <span class="date">'.$date.'</span><br/>
                    '.$post.'
                </p>';
    }
}

/**
 * displayLatestPhotoGalleryPhotos 
 * 
 * @param int $memberId 
 * 
 * @return  void
 */
function displayLatestPhotoGalleryPhotos ($memberId)
{
    $memberId = (int)$memberId;

    $sql = "SELECT `id`, `category`, `user`, `filename` 
            FROM `fcms_gallery_photos` 
            WHERE user = '$memberId' 
            ORDER BY `date` DESC 
            LIMIT 5";

    $result = mysql_query ($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return;
    }

    echo '
            <h2>'.T_('Latest Photos').'</h2>
            <ul class="photos clearfix">';

    while ($row = mysql_fetch_assoc($result))
    {
        echo '
                <li class="photo">
                    <a href="gallery/index.php?uid='.$memberId.'&amp;cid='.(int)$row['category'].'&amp;pid='.(int)$row['id'].'">
                        <img class="photo" src="uploads/photos/member'.(int)$row['user'].'/tb_'.basename($row['filename']).'" alt=""/>
                    </a>
                </li>';
    }

        echo '
            </ul>';
}

/**
 * displayParticipation 
 * 
 * @return void
 */
function displayParticipation ()
{
    global $profile;

    $memberId = (int)$_GET['member'];

    displayHeader($memberId);

    $statsData = $profile->getStats($memberId);
    $points    = getUserParticipationPoints($memberId);
    $level     = getUserParticipationLevel($points);

    echo '
            <div class="clearfix">
                <b>'.T_('Participation Points').'</b><br/>
                <span style="float:left; padding-right: 10px;">'.$points.'</span>
                '.$level.'
            </div><br/>
            <p><b>'.T_('Stats').'</b></p>
            <div id="stats" class="clearfix">';

    foreach ($statsData as $stats)
    {
        echo $stats;
    }

    echo '
            </div>';

    displayFooter($memberId);
}

/**
 * displayEditProfileForm 
 * 
 * @return void
 */
function displayEditProfileForm ()
{
    global $profile;

    displayHeader();

    $profile->displayEditProfile();

    displayFooter();
}

/**
 * displayEditProfileInfoForm 
 * 
 * @return void
 */
function displayEditProfileInfoForm ()
{
    global $profile;

    displayHeader();

    if (isset($_SESSION['success']))
    {
        displayOkMessage();
        unset($_SESSION['success']);
    }

    $profile->displayEditBasicInfo();

    displayFooter();
}

/**
 * displayEditProfileInfoFormSubmit 
 * 
 * @return void
 */
function displayEditProfileInfoFormSubmit ()
{
    global $currentUserId;

    $fname = strip_tags($_POST['fname']);
    $lname = strip_tags($_POST['lname']);

    $fname = escape_string($fname);
    $lname = escape_string($lname);
    $sex   = escape_string($_POST['sex']);

    $year  = (int)$_POST['syear'];
    $month = (int)$_POST['smonth']; 
    $month = str_pad($month, 2, "0", STR_PAD_LEFT);
    $day   = (int)$_POST['sday'];
    $day   = str_pad($day, 2, "0", STR_PAD_LEFT);

    $sql = "UPDATE `fcms_users`
            SET `fname` = '$fname',
                `lname` = '$lname',
                `sex`   = '$sex', ";

    if ($_POST['mname'])
    {
        $mname = strip_tags($_POST['mname']);
        $mname = escape_string($mname);

        $sql .= "`mname` = '$mname', ";
    }
    if ($_POST['maiden'])
    {
        $maiden = strip_tags($_POST['maiden']);
        $maiden = escape_string($maiden);

        $sql .= "`maiden` = '$maiden', ";
    }
    if ($_POST['bio'])
    {
        $bio = strip_tags($_POST['bio']);
        $bio = escape_string($bio);

        $sql .= "`bio` = '$bio', ";
    }

    $sql .= "`dob_year` = '$year',
             `dob_month` = '$month',
             `dob_day` = '$day'
            WHERE id = '$currentUserId'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $_SESSION['success'] = 1;

    header("Location: profile.php?view=info");
}

/**
 * displayEditProfilePictureForm 
 * 
 * @return void
 */
function displayEditProfilePictureForm ()
{
    global $profile;

    displayHeader();

    if (isset($_SESSION['success']))
    {
        displayOkMessage();
        unset($_SESSION['success']);
    }

    $profile->displayEditProfilePicture();

    displayFooter();
}

/**
 * displayEditProfilePictureFormSubmit 
 * 
 * @return void
 */
function displayEditProfilePictureFormSubmit ()
{
    global $currentUserId, $img;

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
                displayHeader();

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
                displayHeader();

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

        }
        else
        {
            $sql .= "`avatar` = `avatar`";
        }
    }
    // Avatar Gravatar
    else if ($_POST['avatar_type'] == 'gravatar')
    {
        $sql .= "`avatar` = 'gravatar', `gravatar` = '".escape_string($_POST['gravatar_email'])."'";

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
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "INSERT INTO `fcms_changelog` (`user`, `table`, `column`, `created`)
            VALUES ('$currentUserId', 'users', 'avatar', NOW())";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $_SESSION['success'] = 1;

    header("Location: profile.php?view=picture");
}

/**
 * displayEditProfileAddressForm 
 * 
 * @return void
 */
function displayEditProfileAddressForm ()
{
    global $profile;

    displayHeader();

    if (isset($_SESSION['success']))
    {
        displayOkMessage();
        unset($_SESSION['success']);
    }

    $profile->displayEditAddress();

    displayFooter();
}

/**
 * displayEditProfileAddressFormSubmit 
 * 
 * @return void
 */
function displayEditProfileAddressFormSubmit ()
{
    $uid     = (int)$_POST['uid'];
    $aid     = (int)$_POST['aid'];

    $email   = strip_tags($_POST['email']);
    $country = strip_tags($_POST['country']);
    $address = strip_tags($_POST['address']);
    $city    = strip_tags($_POST['city']);
    $state   = strip_tags($_POST['state']);
    $zip     = strip_tags($_POST['zip']);
    $home    = strip_tags($_POST['home']);
    $work    = strip_tags($_POST['work']);
    $cell    = strip_tags($_POST['cell']);

    $email   = escape_string($email);
    $country = escape_string($country);
    $address = escape_string($address);
    $city    = escape_string($city);
    $state   = escape_string($state);
    $zip     = escape_string($zip);
    $home    = escape_string($home);
    $work    = escape_string($work);
    $cell    = escape_string($cell);

    $sql = "UPDATE `fcms_address` 
            SET `updated`=NOW(), 
                `country`   = '$country', 
                `address`   = '$address', 
                `city`      = '$city', 
                `state`     = '$state', 
                `zip`       = '$zip', 
                `home`      = '$home', 
                `work`      = '$work', 
                `cell`      = '$cell' 
            WHERE `id` = '$aid'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "UPDATE `fcms_users`
            SET `email`= '$email'
            WHERE `id` = '$uid'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $_SESSION['success'] = 1;

    header("Location: profile.php?view=address");
}
