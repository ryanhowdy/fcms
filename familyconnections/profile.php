<?php
/**
 * Profile.
 *
 * PHP versions 4 and 5
 *
 * @category  FCMS
 *
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('awards', 'FamilyTree', 'profile', 'image', 'datetime', 'address', 'addressbook', 'phone', 'gallery');

init();

$tree = new FamilyTree($fcmsError, $fcmsDatabase, $fcmsUser);
$book = new AddressBook($fcmsError, $fcmsDatabase, $fcmsUser);
$mBoard = new MessageBoard($fcmsError, $fcmsDatabase, $fcmsUser);
$gallery = new PhotoGallery($fcmsError, $fcmsDatabase, $fcmsUser);
$awards = new Awards($fcmsError, $fcmsDatabase, $fcmsUser, $mBoard, $gallery);
$profile = new Profile($fcmsError, $fcmsDatabase, $fcmsUser, $tree, $awards, $book);
$img = new Image($fcmsUser->id);
$page = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $profile, $awards, $img, $gallery);

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
     * Constructor.
     *
     * @return void
     */
    public function __construct($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsProfile, $fcmsAward, $fcmsImage, $fcmsPhotoGallery)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser = $fcmsUser;
        $this->fcmsProfile = $fcmsProfile;
        $this->fcmsAward = $fcmsAward;
        $this->fcmsImage = $fcmsImage;
        $this->fcmsPhotoGallery = $fcmsPhotoGallery;

        $this->control();
    }

    /**
     * control.
     *
     * The controlling structure for this script.
     *
     * @return void
     */
    public function control()
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
                header('Location: profile.php');
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
     * displayHeader.
     *
     * @return void
     */
    public function displayHeader($memberId = 0)
    {
        $params = [
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Profile'),
            'pageId'        => 'profile',
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y'),
        ];

        $params['javascript'] = '
<link rel="stylesheet" type="text/css" href="ui/css/datechooser.css"/>
<script type="text/javascript" src="ui/js/datechooser.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    initChatBar(\''.T_('Chat').'\', \''.URL_PREFIX.'\');
    initGravatar();
    // Datepicker
    var objDatePicker = new DateChooser();
    objDatePicker.setUpdateField({\'sday\':\'j\', \'smonth\':\'n\', \'syear\':\'Y\'});
    objDatePicker.setIcon(\''.URL_PREFIX.'ui/themes/default/img/datepicker.jpg\', \'syear\');
});
</script>';

        loadTemplate('global', 'header', $params);

        if ($memberId > 0)
        {
            $sql = 'SELECT `fname`, `lname`, `username`, `email`
                    FROM `fcms_users`
                    WHERE `id` = ?';

            $row = $this->fcmsDatabase->getRow($sql, $memberId);
            if ($row === false)
            {
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            if ($memberId == $this->fcmsUser->id)
            {
                echo '
            <div id="actions_menu">
                <ul>
                    <li><a href="profile.php">'.T_('Edit Profile').'</a></li>
                </ul>
            </div>';
            }

            echo '
            <div id="leftcolumn">
                <h3>'.T_('Sections').'</h3>
                <ul class="menu">
                    <li><a href="?member='.$memberId.'">'.T_('Profile').'</a></li>
                    <li><a href="?member='.$memberId.'&amp;view=awards">'.T_('Awards').'</a></li>
                    <li><a href="?member='.$memberId.'&amp;view=contributions">'.T_('Contributions').'</a></li>
                    <li><a href="?member='.$memberId.'&amp;view=participation">'.T_('Participation').'</a></li>
                </ul>
                <h3>'.T_('Quick Links').'</h3>
                <ul class="menu">
                    <li><a href="familynews.php?getnews='.$memberId.'">'.T_('Family News').'</a></li>
                    <li><a href="familytree.php?view='.$memberId.'">'.T_('Family Tree').'</a></li>
                    <li><a href="gallery/index.php?uid='.$memberId.'">'.T_('Photos').'</a></li>
                    <li><a href="gallery/index.php?uid=0&cid='.$memberId.'">'.sprintf(T_pgettext('%s is the name of a person. Photos of Bill. etc.', 'Photos Of %s'), $row['fname']).'</a></li>
                    <li><a href="video.php?u='.$memberId.'">'.T_('Videos').'</a></li>
                    <li><a href="addressbook.php?cat=all&address='.$memberId.'">'.T_('Address').'</a></li>
                </ul>
            </div><!-- /leftcolumn -->

            <div id="maincolumn">';
        }
    }

    /**
     * displayFooter.
     *
     * @return void
     */
    public function displayFooter($memberId = 0)
    {
        if ($memberId > 0)
        {
            echo '
            </div><!-- /maincolumn -->';
        }

        $params = [
            'path'    => URL_PREFIX,
            'version' => getCurrentVersion(),
            'year'    => date('Y'),
        ];

        loadTemplate('global', 'footer', $params);
    }

    /**
     * displayAdvancedAvatarUploadSubmit.
     *
     * @return void
     */
    public function displayAdvancedAvatarUploadSubmit()
    {
        // Figure out where we are currently saving photos
        $photoDestinationType = getDestinationType().'ProfileDestination';

        $photoDestination = new $photoDestinationType($this->fcmsError, $this->fcmsUser);
        $uploadPhoto = new UploadPhoto($this->fcmsError, $photoDestination);
        $profileUploader = new UploadProfile($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser, $photoDestination, $uploadPhoto);

        $formData = $_POST;

        if (isset($_FILES['file']))
        {
            $_FILES['file']['name'] = $_POST['name'];
            $formData['avatar'] = $_FILES['file'];
        }
        elseif (isset($_FILES['avatar']))
        {
            $formData['avatar'] = $_FILES['avatar'];
        }

        if (!$profileUploader->upload($formData))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $_SESSION['success'] = 1;
    }

    /**
     * displayInvalidPermission.
     *
     * @return void
     */
    public function displayInvalidPermission()
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
     * displayProfile.
     *
     * @return void
     */
    public function displayProfile()
    {
        $memberId = (int) $_GET['member'];

        $this->displayHeader($memberId);

        // handle unknown user
        if ($memberId == 0)
        {
            echo '
            <p class="error-alert">
                <b>'.T_('Unknown member.').'</b><br/>
            </p>';

            $this->displayFooter();

            return;
        }

        $sql = 'SELECT u.fname, u.lname, u.email, u.`bio`, u.`dob_year`, u.`dob_month`, u.`dob_day`, 
                    u.`dod_year`, u.`dod_month`, u.`dod_day`, u.avatar, u.username, u.joindate, 
                    u.`activity`, u.`sex`, a.`id` AS aid, a.`address`, a.`city`, a.`state`, a.`zip`, 
                    a.`home`, a.`cell`, a.`work`  
                FROM fcms_users AS u, fcms_address AS a 
                WHERE u.id = ?
                AND u.id = a.user';

        $row = $this->fcmsDatabase->getRow($sql, $memberId);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $tzOffset = getTimezone($memberId);
        $joinDate = fixDate(T_('F j, Y'), $tzOffset, $row['joindate']);
        $address = formatAddress($row);
        $contact = '';
        $activityDate = T_('Never visited');

        $points = getUserParticipationPoints($memberId);
        $level = getUserParticipationLevel($points);

        // Contacts - Email
        if (!empty($row['cell']))
        {
            $contact .= '<p><span>'.T_('Email').'</span> '.$row['email'].'</p>';
        }
        // Contacts - Phone
        if (!empty($row['cell']))
        {
            $contact .= '<p><span>'.T_('Cell').'</span> '.formatPhone($row['cell']).'</p>';
        }
        if (!empty($row['home']))
        {
            $contact .= '<p><span>'.T_pgettext('The beginning or starting place.', 'Home').'</span> '.formatPhone($row['home']).'</p>';
        }
        if (!empty($row['work']))
        {
            $contact .= '<p><span>'.T_('Work').'</span> '.formatPhone($row['work']).'</p>';
        }

        // Call
        $hasPhone = false;
        $call = '';
        $tel = '';
        if (!empty($row['cell']))
        {
            $tel = $row['cell'];
            $hasPhone = true;
        }
        elseif (!empty($row['home']))
        {
            $tel = $row['home'];
            $hasPhone = true;
        }
        elseif (!empty($row['work']))
        {
            $tel = $row['work'];
            $hasPhone = true;
        }

        if ($hasPhone)
        {
            $call = '<li><a class="call" href="tel:'.$tel.'">'.sprintf(T_pgettext('%s is the name of a person. Call Bob. etc.', 'Call %s'), $row['fname']).'</a></li>';
        }

        // Activity
        if ($row['activity'] != '0000-00-00 00:00:00')
        {
            $activityDate = fixDate(T_('F j, Y g:i a'), $tzOffset, $row['activity']);
        }

        $bday = formatDate('F j, Y', $row['dob_year'].'-'.$row['dob_month'].'-'.$row['dob_day']);
        $age = getAge($row['dob_year'], $row['dob_month'], $row['dob_day']);

        $gender = $row['sex'] == 'M' ? T_('Male') : T_('Female');

        echo '
                <div id="avatar">
                    <h1><img class="avatar" src="'.getCurrentAvatar($memberId).'" alt="avatar"/></h1>
                    '.$level.'
                </div>
                <div class="name-contacts">
                    <h1>'.cleanOutput($row['fname']).' '.cleanOutput($row['lname']).'</h1>
                    <h2>'.cleanOutput($row['username']).'</h2>
                    <ul>
                        '.$call.'
                        <li><a class="email" href="mailto:'.$row['email'].'">'.T_('Send Email').'</a></li>
                        <li><a class="pm" href="privatemsg.php?compose=new&amp;id='.$memberId.'">'.T_('Send Private Message').'</a></li>
                    </ul>
                </div>
                <ul>
                    <li>
                        <ul>
                            <li>
                                <b>'.T_('Birthday').'</b>
                                <div>'.$bday.' ('.sprintf(T_('%s years old'), $age).')</div>
                            </li>
                            <li>
                                <b>'.T_('Gender').'</b>
                                <div>'.$gender.'</div>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <ul>
                            <li>
                                <b>'.T_('Location').'</b>
                                <div>'.$address.'</div>
                            </li>
                            <li>
                                <b>'.T_('Contact').'</b>
                                <div>'.$contact.'</div>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <b>'.T_('Bio').'</b>
                        <div>'.cleanOutput($row['bio']).'</div>
                    </li>
                    <li>
                        <ul>
                            <li>
                                <b>'.T_('Join Date').'</b>
                                <div>'.$joinDate.'</div>
                            </li>
                            <li>
                                <b>'.T_('Last Visit').'</b>
                                <div>'.$activityDate.'</div>
                            </li>
                        </ul>
                    </li>
                </ul>';

        $this->displayFooter($memberId);
    }

    /**
     * displayAwards.
     *
     * @return void
     */
    public function displayAwards()
    {
        $memberId = (int) $_GET['member'];

        $this->displayHeader($memberId);

        echo '<h2>'.T_('Awards').'</h2>';

        $this->fcmsAward->displayAwards($memberId);

        $this->displayFooter($memberId);
    }

    /**
     * displayAward.
     *
     * @return void
     */
    public function displayAward()
    {
        $memberId = (int) $_GET['member'];
        $type = $_GET['award'];

        $this->displayHeader($memberId);

        $this->fcmsAward->displayAward($memberId, $type);

        $this->displayFooter($memberId);
    }

    /**
     * displayContributions.
     *
     * @return void
     */
    public function displayContributions()
    {
        $memberId = (int) $_GET['member'];

        $this->displayHeader($memberId);

        $this->displayLatestMessageBoardPosts($memberId);
        $this->displayLatestPhotoGalleryPhotos($memberId);

        $this->displayFooter($memberId);
    }

    /**
     * displayLatestMessageBoardPosts.
     *
     * @param int $memberId
     *
     * @return void
     */
    public function displayLatestMessageBoardPosts($memberId)
    {
        $memberId = (int) $memberId;

        $sql = 'SELECT t.`id`, `subject`, `date`, `post` 
                FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, `fcms_users` AS u 
                WHERE t.`id` = p.`thread` 
                AND p.`user` = u.`id` 
                AND u.`id` = ?
                ORDER BY `date` DESC 
                LIMIT 0, 5';

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
            $date = fixDate(T_('F j, Y, g:i a'), $tzOffset, $row['date']);
            $subject = $row['subject'];
            $post = removeBBCode($row['post']);
            $post = cleanOutput($post);
            $pos = strpos($subject, '#ANOUNCE#');

            if ($pos !== false)
            {
                $subject = substr($subject, 9, strlen($subject) - 9);
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
     * displayLatestPhotoGalleryPhotos.
     *
     * @param int $memberId
     *
     * @return void
     */
    public function displayLatestPhotoGalleryPhotos($memberId)
    {
        $memberId = (int) $memberId;

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
                    <a href="gallery/index.php?uid='.$memberId.'&amp;cid='.(int) $row['category'].'&amp;pid='.(int) $row['id'].'">
                        <img class="photo" src="'.$photoSrc.'" alt=""/>
                    </a>
                </li>';
        }

            echo '
            </ul>';
    }

    /**
     * displayParticipation.
     *
     * @return void
     */
    public function displayParticipation()
    {
        $memberId = (int) $_GET['member'];

        $this->displayHeader($memberId);

        $statsData = $this->fcmsProfile->getStats($memberId);
        $points = getUserParticipationPoints($memberId);
        $level = getUserParticipationLevel($points);

        echo '
            <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/easy-pie-chart/2.1.4/jquery.easypiechart.min.js"></script>
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
            </div>
            <script type="text/javascript">
                $(function() {
                    $(".stat").easyPieChart({
                        animate     : false,
                        scaleColor  : false,
                        barColor    : "#99CEF0",
                        lineWidth   : 6,
                        size        : 150
                    });
                });
            </script>';

        $this->displayFooter($memberId);
    }

    /**
     * displayEditProfileForm.
     *
     * @return void
     */
    public function displayEditProfileForm()
    {
        $this->displayHeader();

        $this->fcmsProfile->displayEditProfile();

        $this->displayFooter();
    }

    /**
     * displayEditProfileInfoForm.
     *
     * @return void
     */
    public function displayEditProfileInfoForm()
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
     * displayEditProfileInfoFormSubmit.
     *
     * @return void
     */
    public function displayEditProfileInfoFormSubmit()
    {
        $fname = strip_tags($_POST['fname']);
        $lname = strip_tags($_POST['lname']);
        $sex = $_POST['sex'];

        $year = (int) $_POST['syear'];
        $month = (int) $_POST['smonth'];
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = (int) $_POST['sday'];
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);

        $params = [
            $fname,
            $lname,
            $sex,
        ];

        $sql = 'UPDATE `fcms_users`
                SET `fname` = ?,
                    `lname` = ?,
                    `sex`   = ?, ';

        if ($_POST['mname'])
        {
            $params[] = strip_tags($_POST['mname']);

            $sql .= '`mname` = ?, ';
        }
        if ($_POST['maiden'])
        {
            $params[] = strip_tags($_POST['maiden']);

            $sql .= '`maiden` = ?, ';
        }
        if ($_POST['bio'])
        {
            $params[] = strip_tags($_POST['bio']);

            $sql .= '`bio` = ?, ';
        }

        $params[] = $year;
        $params[] = $month;
        $params[] = $day;
        $params[] = $this->fcmsUser->id;

        $sql .= '`dob_year` = ?,
                 `dob_month` = ?,
                 `dob_day` = ?
                WHERE id = ?';

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $_SESSION['success'] = 1;

        header('Location: profile.php?view=info');
    }

    /**
     * displayEditProfilePictureForm.
     *
     * @return void
     */
    public function displayEditProfilePictureForm()
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
     * displayEditProfilePictureFormSubmit.
     *
     * @return void
     */
    public function displayEditProfilePictureFormSubmit()
    {
        // Figure out where we are currently saving photos, and create new destination object
        $photoDestinationType = getDestinationType().'ProfileDestination';

        $photoDestination = new $photoDestinationType($this->fcmsError, $this->fcmsUser);
        $uploadPhoto = new UploadPhoto($this->fcmsError, $photoDestination);
        $profileUploader = new UploadProfile($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser, $photoDestination, $uploadPhoto);

        $formData = $_POST;

        if (isset($_FILES['avatar']))
        {
            $formData['avatar'] = $_FILES['avatar'];
        }

        if (!$profileUploader->upload($formData))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $_SESSION['success'] = 1;

        header('Location: profile.php?view=picture');
    }

    /**
     * displayEditProfileAddressForm.
     *
     * @return void
     */
    public function displayEditProfileAddressForm()
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
     * displayEditProfileAddressFormSubmit.
     *
     * @return void
     */
    public function displayEditProfileAddressFormSubmit()
    {
        $uid = (int) $_POST['uid'];
        $aid = (int) $_POST['aid'];

        $email = strip_tags($_POST['email']);
        $country = strip_tags($_POST['country']);
        $address = strip_tags($_POST['address']);
        $city = strip_tags($_POST['city']);
        $state = strip_tags($_POST['state']);
        $zip = strip_tags($_POST['zip']);
        $home = strip_tags($_POST['home']);
        $work = strip_tags($_POST['work']);
        $cell = strip_tags($_POST['cell']);

        $sql = 'UPDATE `fcms_address` 
                SET `updated` = NOW(), 
                    `country` = ?,
                    `address` = ?, 
                    `city`    = ?, 
                    `state`   = ?, 
                    `zip`     = ?, 
                    `home`    = ?, 
                    `work`    = ?, 
                    `cell`    = ? 
                WHERE `id` = ?';

        $params = [
            $country,
            $address,
            $city,
            $state,
            $zip,
            $home,
            $work,
            $cell,
            $aid,
        ];

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $sql = 'UPDATE `fcms_users`
                SET `email`= ?
                WHERE `id` = ?';

        if (!$this->fcmsDatabase->update($sql, [$email, $uid]))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $_SESSION['success'] = 1;

        header('Location: profile.php?view=address');
    }
}
