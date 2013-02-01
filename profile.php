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
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('awards', 'familytree', 'profile', 'image', 'datetime', 'address', 'addressbook', 'phone', 'gallery');

init();

$awards  = new Awards($fcmsError, $fcmsDatabase, $fcmsUser);
$tree    = new FamilyTree($fcmsError, $fcmsDatabase, $fcmsUser);
$book    = new AddressBook($fcmsError, $fcmsDatabase, $fcmsUser);
$profile = new Profile($fcmsError, $fcmsDatabase, $fcmsUser, $tree, $awards, $book);
$img     = new Image($fcmsUser->id);
$gallery = new PhotoGallery($fcmsError, $fcmsDatabase, $fcmsUser);
$page    = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $profile, $awards, $img, $gallery);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsTemplate;
    private $fcmsProfile;
    private $fcmsAward;
    private $fcmsImage;
    private $fcmsPhotoGallery;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsProfile, $fcmsAward, $fcmsImage, $fcmsPhotoGallery)
    {
        $this->fcmsError         = $fcmsError;
        $this->fcmsDatabase      = $fcmsDatabase;
        $this->fcmsUser          = $fcmsUser;
        $this->fcmsProfile       = $fcmsProfile;
        $this->fcmsAward         = $fcmsAward;
        $this->fcmsImage         = $fcmsImage;
        $this->fcmsPhotoGallery  = $fcmsPhotoGallery;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Profile'),
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
        if ($this->fcmsUser->access == 11)
        {
            $this->displayInvalidPermission();
        }
        elseif (isset($_GET['advanced-avatar']))
        {
            $this->displayAdvancedAvatarUploadSubmit();
        }
        // View Profile
        elseif (isset($_GET['member']))
        {
            if (isset($_GET['view']))
            {
                if ($_GET['view'] == 'awards')
                {
                    $this->displayAwards();
                }
                elseif ($_GET['view'] == 'contributions')
                {
                    $this->displayContributions();
                }
                elseif ($_GET['view'] == 'participation')
                {
                    $this->displayParticipation();
                }
                else
                {
                    $this->displayProfile();
                }
            }
            elseif (isset($_GET['award']))
            {
                $this->displayAward();
            }
            else
            {
                $this->displayProfile();
            }
        }
        // Save Profile
        elseif (isset($_POST['submit']) && isset($_GET['view']))
        {
            if ($_GET['view'] == 'info')
            {
                $this->displayEditProfileInfoFormSubmit();
            }
            elseif ($_GET['view'] == 'picture')
            {
                $this->displayEditProfilePictureFormSubmit();
            }
            else
            {
                header("Location: profile.php");
            }
        }
        elseif (isset($_POST['editsubmit']))
        {
            $this->displayEditProfileAddressFormSubmit();
        }
        // Edit Profile
        elseif (isset($_GET['view']))
        {
            if ($_GET['view'] == 'info')
            {
                $this->displayEditProfileInfoForm();
            }
            elseif ($_GET['view'] == 'picture')
            {
                $this->displayEditProfilePictureForm();
            }
            elseif ($_GET['view'] == 'address')
            {
                $this->displayEditProfileAddressForm();
            }
            else
            {
                $this->displayEditProfileForm();
            }
        }
        else
        {
            $this->displayEditProfileForm();
        }
    }

    /**
     * displayHeader 
     * 
     * @return void
     */
    function displayHeader ($memberId = 0)
    {
        $TMPL = $this->fcmsTemplate;

        $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    initGravatar();
});
//]]>
</script>';

        require_once getTheme($this->fcmsUser->id).'header.php';

        echo '
        <div id="profile" class="centercontent">';

        if ($memberId > 0)
        {
            $sql = "SELECT `fname`, `lname`, `username`, `email`
                    FROM `fcms_users`
                    WHERE `id` = ?";

            $row = $this->fcmsDatabase->getRow($sql, $memberId);
            if ($row === false)
            {
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

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
                <div id="sections_menu">
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
        $TMPL = $this->fcmsTemplate;

        if ($memberId > 0)
        {
            echo '
            </div><!-- /maincolumn -->';
        }

        echo '
        </div><!-- /profile -->';

        include_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayAdvancedAvatarUploadSubmit 
     * 
     * @return void
     */
    function displayAdvancedAvatarUploadSubmit ()
    {
        $filetypes = array(
            'image/pjpeg'   => 'jpg', 
            'image/jpeg'    => 'jpg', 
            'image/gif'     => 'gif', 
            'image/bmp'     => 'bmp', 
            'image/x-png'   => 'png', 
            'image/png'     => 'png'
        );

        $type        = $_FILES['avatar']['type'];
        $extention   = $filetypes[$type];
        $id          = uniqid("");
        $name        = $id.".".$extention;
        $uploadsPath = getUploadsAbsolutePath();

        $sql = "UPDATE `fcms_users`
                SET `avatar` = ?
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->update($sql, array($name, $this->fcmsUser->id)))
        {
            logError(__FILE__.' ['.__LINE__.'] - Could not update db with new avatar.');
            echo "FAILURE: Could not update db with new avatar.\n";
            exit();
        }

        $sql = "INSERT INTO `fcms_changelog` 
                    (`user`, `table`, `column`, `created`)
                VALUES 
                    (?, 'users', 'avatar', NOW())";

        if (!$this->fcmsDatabase->insert($sql, array($this->fcmsUser->id)))
        {
            logError(__FILE__.' ['.__LINE__.'] - Could not update db with changelog details.');
            echo "FAILURE: Could not update db with changelog details.\n";
            exit();
        }

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadsPath.'avatar/'.$name))
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
            if (file_exists($uploadsPath.'avatar/'.basename($_GET['orig'])))
            {
                unlink($uploadsPath.'avatar/'.basename($_GET['orig']));
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
        $this->displayHeader();

        echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                <a href="contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

        $this->displayFooter();
    }

    /**
     * displayProfile 
     * 
     * @return void
     */
    function displayProfile ()
    {
        $memberId = (int)$_GET['member'];

        $this->displayHeader($memberId);

        $sql = "SELECT u.fname, u.lname, u.email, u.`bio`, u.`dob_year`, u.`dob_month`, u.`dob_day`, 
                    u.`dod_year`, u.`dod_month`, u.`dod_day`, u.avatar, u.username, u.joindate, 
                    u.`activity`, u.`sex`, a.`id` AS aid, a.`address`, a.`city`, a.`state`, a.`zip`, 
                    a.`home`, a.`cell`, a.`work`  
                FROM fcms_users AS u, fcms_address AS a 
                WHERE u.id = '$memberId' 
                AND u.id = a.user";

        $row = $this->fcmsDatabase->getRow($sql, $memberId);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

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
            <ul id="profile-data">
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

        $this->displayFooter($memberId);
    }

    /**
     * displayAwards 
     * 
     * @return void
     */
    function displayAwards ()
    {
        $memberId = (int)$_GET['member'];

        $this->displayHeader($memberId);

        $this->fcmsAward->displayAwards($memberId);

        $this->displayFooter($memberId);
    }

    /**
     * displayAward 
     * 
     * @return void
     */
    function displayAward ()
    {
        $memberId = (int)$_GET['member'];
        $type     = $_GET['award'];

        $this->displayHeader($memberId);

        $awards->displayAward($memberId, $type);

        $this->displayFooter($memberId);
    }

    /**
     * displayContributions 
     * 
     * @return void
     */
    function displayContributions ()
    {
        $memberId = (int)$_GET['member'];

        $this->displayHeader($memberId);

        $this->displayLatestMessageBoardPosts($memberId);
        $this->displayLatestPhotoGalleryPhotos($memberId);

        $this->displayFooter($memberId);
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
                AND u.`id` = ?
                ORDER BY `date` DESC 
                LIMIT 0, 5";

        $rows = $this->fcmsDatabase->getRows($sql, $memberId);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        if (count($rows) <= 0)
        {
            return;
        }

        echo '
            <h2>'.T_('Latest Posts').'</h2>';

        $tzOffset = getTimezone($memberId);

        foreach ($rows as $row)
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

        $sql = "SELECT p.`id`, p.`category`, p.`user`, p.`filename`, p.`external_id`, e.`thumbnail`
                FROM `fcms_gallery_photos` AS p
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                WHERE user = '$memberId' 
                ORDER BY `date` DESC 
                LIMIT 5";

        $rows = $this->fcmsDatabase->getRows($sql, $memberId);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        if (count($rows) <= 0)
        {
            return;
        }

        echo '
            <h2>'.T_('Latest Photos').'</h2>
            <ul class="photos">';

        foreach ($rows as $row)
        {
            $filename = basename($row['filename']);

            $photoSrc = $this->fcmsPhotoGallery->getPhotoSource($row);

            echo '
                <li class="photo">
                    <a href="gallery/index.php?uid='.$memberId.'&amp;cid='.(int)$row['category'].'&amp;pid='.(int)$row['id'].'">
                        <img class="photo" src="'.$photoSrc.'" alt=""/>
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
        $memberId = (int)$_GET['member'];

        $this->displayHeader($memberId);

        $statsData = $this->fcmsProfile->getStats($memberId);
        $points    = getUserParticipationPoints($memberId);
        $level     = getUserParticipationLevel($points);

        echo '
            <div>
                <b>'.T_('Participation Points').'</b><br/>
                <span style="float:left; padding-right: 10px;">'.$points.'</span>
                '.$level.'
            </div><br/>
            <p><b>'.T_('Stats').'</b></p>
            <div id="stats">';

        foreach ($statsData as $stats)
        {
            echo $stats;
        }

        echo '
            </div>';

        $this->displayFooter($memberId);
    }

    /**
     * displayEditProfileForm 
     * 
     * @return void
     */
    function displayEditProfileForm ()
    {
        $this->displayHeader();

        $this->fcmsProfile->displayEditProfile();

        $this->displayFooter();
    }

    /**
     * displayEditProfileInfoForm 
     * 
     * @return void
     */
    function displayEditProfileInfoForm ()
    {
        $this->displayHeader();

        if (isset($_SESSION['success']))
        {
            displayOkMessage();
            unset($_SESSION['success']);
        }

        $this->fcmsProfile->displayEditBasicInfo();

        $this->displayFooter();
    }

    /**
     * displayEditProfileInfoFormSubmit 
     * 
     * @return void
     */
    function displayEditProfileInfoFormSubmit ()
    {
        $fname = strip_tags($_POST['fname']);
        $lname = strip_tags($_POST['lname']);
        $sex   = $_POST['sex'];

        $year  = (int)$_POST['syear'];
        $month = (int)$_POST['smonth']; 
        $month = str_pad($month, 2, "0", STR_PAD_LEFT);
        $day   = (int)$_POST['sday'];
        $day   = str_pad($day, 2, "0", STR_PAD_LEFT);

        $params = array(
            $fname,
            $lname,
            $sex
        );

        $sql = "UPDATE `fcms_users`
                SET `fname` = ?,
                    `lname` = ?,
                    `sex`   = ?, ";

        if ($_POST['mname'])
        {
            $params[] = strip_tags($_POST['mname']);

            $sql .= "`mname` = ?, ";
        }
        if ($_POST['maiden'])
        {
            $params[] = strip_tags($_POST['maiden']);

            $sql .= "`maiden` = ?, ";
        }
        if ($_POST['bio'])
        {
            $params[] = strip_tags($_POST['bio']);

            $sql .= "`bio` = ?, ";
        }

        $params[] = $year;
        $params[] = $month;
        $params[] = $day;
        $params[] = $this->fcmsUser->id;

        $sql .= "`dob_year` = ?,
                 `dob_month` = ?,
                 `dob_day` = ?
                WHERE id = ?";

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

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
        $this->displayHeader();

        if (isset($_SESSION['success']))
        {
            displayOkMessage();
            unset($_SESSION['success']);
        }

        $this->fcmsProfile->displayEditProfilePicture();

        $this->displayFooter();
    }

    /**
     * displayEditProfilePictureFormSubmit 
     * 
     * @return void
     */
    function displayEditProfilePictureFormSubmit ()
    {
        $sql = "UPDATE `fcms_users` SET ";

        $uploadsPath = getUploadsAbsolutePath();

        // Avatar uploads
        if ($_POST['avatar_type'] == 'fcms')
        {
            if ($_FILES['avatar']['error'] < 1)
            {
                $img->destination  = $uploadsPath.'avatar/';
                $img->resizeSquare = true;
                $img->uniqueName   = true;

                $img->upload($_FILES['avatar']);

                if ($img->error == 1)
                {
                    $this->displayHeader();

                    echo '
                <p class="error-alert">
                    '.sprintf(T_('Photo [%s] is not a supported photo type.  Photos must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $img->name).'
                </p>';

                    $this->displayFooter();
                    return;
                }

                $img->resize(80, 80);

                if ($img->error > 0)
                {
                    $this->displayHeader();

                    echo '
                <p class="error-alert">
                    '.T_('There was an error uploading your avatar.').'
                </p>';

                    $this->displayFooter();
                    return;
                }

                $sql .= "`avatar` = '".$img->name."'";

                if ($_POST['avatar_orig'] != 'no_avatar.jpg' && $_POST['avatar_orig'] != 'gravatar')
                {
                    if (file_exists($uploadsPath.'avatar/'.basename($_POST['avatar_orig'])))
                    {
                        unlink($uploadsPath.'avatar/'.basename($_POST['avatar_orig']));
                    }
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
            $sql .= "`avatar` = 'gravatar', `gravatar` = '".$_POST['gravatar_email']."'";

            if ($_POST['avatar_orig'] != 'no_avatar.jpg' && $_POST['avatar_orig'] != 'gravatar')
            {
                if (file_exists($uploadsPath.'avatar/'.basename($_POST['avatar_orig'])))
                {
                    unlink($uploadsPath.'avatar/'.basename($_POST['avatar_orig']));
                }
            }
        }
        // Avatar default
        else
        {
            $sql .= "`avatar` = 'no_avatar.jpg'";
        }

        $sql .= "WHERE `id` = ?";
        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $sql = "INSERT INTO `fcms_changelog` (`user`, `table`, `column`, `created`)
                VALUES (?, 'users', 'avatar', NOW())";
        if (!$this->fcmsDatabase->insert($sql, $this->fcmsUser->id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
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
        $this->displayHeader();

        if (isset($_SESSION['success']))
        {
            displayOkMessage();
            unset($_SESSION['success']);
        }

        $this->fcmsProfile->displayEditAddress();

        $this->displayFooter();
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

        $sql = "UPDATE `fcms_address` 
                SET `updated` = NOW(), 
                    `country` = ?,
                    `address` = ?, 
                    `city`    = ?, 
                    `state`   = ?, 
                    `zip`     = ?, 
                    `home`    = ?, 
                    `work`    = ?, 
                    `cell`    = ? 
                WHERE `id` = ?";

        $params = array(
            $country, 
            $address, 
            $city, 
            $state, 
            $zip, 
            $home, 
            $work, 
            $cell,
            $aid
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $sql = "UPDATE `fcms_users`
                SET `email`= ?
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->update($sql, array($email, $uid)))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $_SESSION['success'] = 1;

        header("Location: profile.php?view=address");
    }
}
