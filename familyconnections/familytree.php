<?php
/**
 * Family Tree
 * 
 * PHP versions 4 and 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2010 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.3
 */
session_start();

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('FamilyTree', 'FormValidator', 'image', 'datetime');

init();

$tree = new FamilyTree($fcmsError, $fcmsDatabase, $fcmsUser);
$img  = new Image($fcmsUser->id);
$page = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $tree, $img);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsFamilyTree;
    private $fcmsImage;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsFamilyTree, $fcmsImage)
    {
        $this->fcmsError      = $fcmsError;
        $this->fcmsDatabase   = $fcmsDatabase;
        $this->fcmsUser       = $fcmsUser;
        $this->fcmsFamilyTree = $fcmsFamilyTree;
        $this->fcmsImage      = $fcmsImage;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Family Tree'),
            'path'          => URL_PREFIX,
            'displayname'   => getUserDisplayName($this->fcmsUser->id),
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
        if (isset($_POST['ajax']))
        {
            $ajax = $_POST['ajax'];

            if ($ajax == 'add_relative_menu')
            {
                $this->getAjaxAddRelativeMenu();
                return;
            }

            header("HTTP/1.0 500 Internal Server Error");
        }
        elseif (isset($_GET['details']))
        {
            $this->displayDetails();
        }
        elseif (isset($_GET['add']))
        {
            if (isset($_POST['additional-options']))
            {
                $this->displayAddRelativeFormAdditionalOptions();
            }
            elseif (isset($_POST['submit']))
            {
                $this->displayAddRelativeFormSubmit();
            }
            else
            {
                $this->displayAddRelativeForm();
            }
        }
        elseif (isset($_GET['delete']))
        {
            if (isset($_GET['confirm']))
            {
                $this->displayDeletePersonFormSubmit();
            }
            else
            {
                $this->displayDeletePersonForm();
            }
        }
        elseif (isset($_GET['edit']))
        {
            if (isset($_POST['submit']))
            {
                $this->displayEditPersonFormSubmit();
            }
            else
            {
                $this->displayEditPersonForm();
            }
        }
        elseif (isset($_GET['create']))
        {
            if (isset($_POST['submit']))
            {
                $this->displayCreateUserFormSubmit();
            }
            else
            {
                $this->displayCreateUserForm();
            }
        }
        else
        {
            $this->displayFamilyTree();
        }
    }

    /**
     * displayHeader 
     * 
     * @param array $options 
     * 
     * @return void
     */
    function displayHeader ($options = null)
    {
        displayPageHeader('familytree-page', $this->fcmsTemplate, $options);
    }

    /**
     * displayFooter 
     * 
     * @return void
     */
    function displayFooter()
    {
        displayPageFooter($this->fcmsTemplate);
    }

    /**
     * displayEditPersonForm 
     * 
     * @return void
     */
    function displayEditPersonForm ()
    {
        $js = '
    var bday = new DateChooser();
    bday.setUpdateField({\'bday\':\'j\', \'bmonth\':\'n\', \'byear\':\'Y\'});
    bday.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'byear\');
    var dday = new DateChooser();
    dday.setUpdateField({\'dday\':\'j\', \'dmonth\':\'n\', \'dyear\':\'Y\'});
    dday.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'dyear\');

    initLivingDeceased();';

        $this->displayHeader(
            array(
                'modules'  => array('livevalidation', 'datechooser'),
                'jsOnload' => $js
            )
        );

        $id = (int)$_GET['edit'];

        $sql = "SELECT *
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $id);
        if ($user === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // If this is a real user, and you are not an admin, you can't edit it
        if ($user['phpass'] != 'NONMEMBER' && $this->fcmsUser->access != 1)
        {
            echo '<div class="error-alert">'.T_('You do not have permission to perform this task.').'</div>';
            $this->displayFooter();
            return;
        }

        $this->fcmsFamilyTree->displayEditPersonForm($id);

        $this->displayFooter();
    }

    /**
     * displayEditPersonFormSubmit 
     * 
     * @return void
     */
    function displayEditPersonFormSubmit ()
    {
        $id = (int)$_GET['edit'];

        $sql = "SELECT *
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $id);
        if ($user === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        if ($user['phpass'] != 'NONMEMBER' && $this->fcmsUser->access != 1)
        {
            $this->displayHeader();
            echo '<div class="error-alert">'.T_('You do not have permission to perform this task.').'</div>';
            $this->displayFooter();
            return;
        }

        $validator = new FormValidator();

        $errors = $validator->validate($_POST, $this->fcmsFamilyTree->getProfile('edit'));
        if ($errors !== true)
        {
            $this->displayHeader();
            displayErrors($errors);
            $this->displayFooter();
            return;
        }

        // birthday
        list($bYear, $bMonth, $bDay) = formatYMD($_POST['byear'], $_POST['bmonth'], $_POST['bday']);

        // death
        list($dYear, $dMonth, $dDay) = formatYMD($_POST['dyear'], $_POST['dmonth'], $_POST['dday']);

        $fname  = strip_tags($_POST['fname']);
        $mname  = strip_tags($_POST['mname']);
        $lname  = strip_tags($_POST['lname']);
        $bio    = strip_tags($_POST['bio']);

        $sql = "UPDATE `fcms_users`
                SET `fname`     = ?,
                    `mname`     = ?, 
                    `lname`     = ?, 
                    `maiden`    = ?,
                    `bio`       = ?,
                    `dob_year`  = ?, 
                    `dob_month` = ?,
                    `dob_day`   = ?,
                    `dod_year`  = ?, 
                    `dod_month` = ?,
                    `dod_day`   = ?,
                    `sex`       = ?
                WHERE `id`      = ?";

        $params = array(
            $fname,
            $mname, 
            $lname, 
            $_POST['maiden'],
            $bio,
            $bYear, 
            $bMonth,
            $bDay,
            $dYear, 
            $dMonth,
            $dDay,
            $_POST['sex'],
            $_POST['id']
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $_SESSION['ok'] = 1;

        header("Location: familytree.php");
    }

    /**
     * displayDetails
     * 
     * @return void
     */
    function displayDetails ()
    {
        $this->displayHeader();

        $id = (int)$_GET['details'];

        $sql = "SELECT *
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $id);
        if ($user === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $canEdit = false;
        if ($user['phpass'] == 'NONMEMBER' || $this->fcmsUser->access == 1)
        {
            $canEdit  = true;
            $editLink = '<li><a href="?edit='.$id.'">'.T_('Edit This Person').'</a></li>';
        }

        $avatarPath = getAvatarPath($user['avatar'], $user['gravatar']);

        $status = $user['phpass'] == 'NONMEMBER' ? T_('Non-member')  : T_('Member');

        // Bio
        if (strlen($user['bio']) > 0)
        {
            $bio = cleanOutput($user['bio']);
        }
        elseif ($canEdit)
        {
            $bio = '<a href="?edit='.$id.'">'.T_('Share some information about this person.').'</a>';
        }
        else
        {
            $bio = T_('This user does not have a bio.');
        }

        // Get Parents
        $parents = $this->fcmsFamilyTree->getParentsOfUsers(array($user['id']));
        if ($parents === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $father = array();
        $mother = array();
        foreach ($parents as $parent)
        {
            if ($parent['sex'] == 'M')
            {
                $father[] = $parent;
            }
            else
            {
                $mother[] = $parent;
            }
        }

        // Get spouse
        $spouse = $this->fcmsFamilyTree->getSpousesOfUsers(array($user['id']));
        if ($spouse === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // Get children
        $children = $this->fcmsFamilyTree->getChildrenOfUsers($spouse);
        if ($children === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        list($bday, $death) = $this->fcmsFamilyTree->getFormattedBirthdayAndDeath($user);

        echo '
        <div id="sections_menu">
            <ul>
                <li><a href="?view='.$id.'">'.T_('View Family Tree').'</a></li>
            </ul>
        </div>
        <div id="actions_menu">
            <ul>
                '.$editLink.'
                <li><a href="?add='.$id.'">'.T_('Add Family Member').'</a></li>
            </ul>
        </div>
        <div class="person-details">
            <img class="avatar" src="'.$avatarPath.'"/>
            <h1>'.$user['fname'].' '.$user['lname'].'</h1>
            <p class="member_status">'.$status.'</p>
        </div>
        <p>
            '.$bday.'<br/>
            '.$death.'
        </p>
        <h3>'.T_('Bio').'</h3>
        <p>'.$bio.'</p>
        <h3>'.T_('Immediate Family').'</h3>
        <ul id="immediate-family">';

        // Print parents, spouses, and children
        $types = array(
            'father' => array(
                'M' => T_('Father'),
            ),
            'mother' => array(
                'F' => T_('Mother'),
            ),
            'spouse' => array(
                'M' => T_('Spouse'),
                'F' => T_('Spouse'),
            ),
            'children' => array(
                'M' => T_('Son'),
                'F' => T_('Daughter'),
            )
        );

        foreach ($types as $type => $i18n)
        {
            foreach (${$type} as $relative)
            {
                if (!empty($relative))
                {
                    $relAvatarPath = getAvatarPath($relative['avatar'], $relative['gravatar']);

                    $maiden = strlen($relative['maiden']) > 0 ? '('.$relative['maiden'].')' : '';

                    echo '
        <li>
            <img class="small-avatar" src="'.$relAvatarPath.'"/>
            <p>
                <a href="?details='.$relative['id'].'">
                    '.$relative['fname'].' '.$relative['mname'].' '.$relative['lname'].' '.$maiden.'
                </a>
                <i>'.$i18n[$relative['sex']].'</i>
            </p>
        </li>';
                }
            }
        }

        $this->displayFooter();
    }

    /**
     * displayAddRelativeForm 
     * 
     * @return void
     */
    function displayAddRelativeForm ()
    {
        $this->displayHeader();

        $id = (int)$_GET['add'];

        $sql = "SELECT *
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $id);
        if ($user === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // If this is a real user, and you are not an admin, you can't edit it
        if ($user['phpass'] != 'NONMEMBER' && $this->fcmsUser->access != 1)
        {
            echo '<div class="error-alert">'.T_('You do not have permission to perform this task.').'</div>';
            $this->displayFooter();
            return;
        }

        switch ($_GET['type'])
        {
            case 'father':
            case 'mother':
            case 'spouse':
                $this->fcmsFamilyTree->displayAddFatherMotherSpouseForm($id, $_GET['type']);
                break;

            case 'brother':
            case 'sister':
                $this->fcmsFamilyTree->displayAddBrotherSisterForm($id, $_GET['type']);
                break;

            case 'child':
                $this->fcmsFamilyTree->displayAddChildForm($id);
                break;

            default:
                echo '<div class="error-alert">'.T_('You have supplied an invalid family member type.').'</div>';
        }

        $this->displayFooter();
    }

    /**
     * displayAddRelativeFormAdditionalOptions 
     * 
     * @return void
     */
    function displayAddRelativeFormAdditionalOptions ()
    {
        $this->displayHeader();

        $validator = new FormValidator();

        $errors = $validator->validate($_POST, $this->fcmsFamilyTree->getProfile('add'));
        if ($errors !== true)
        {
            displayErrors($errors);
            $this->displayFooter();
            return;
        }

        $userId    = $_POST['id'];
        $relUserId = $_POST['rel_user'];
        $type      = $_POST['type'];

        // Get user and relUser info
        $sql = "SELECT `id`, `fname`, `mname`, `lname`
                FROM `fcms_users`
                WHERE `id` = ?
                UNION
                SELECT `id`, `fname`, `mname`, `lname`
                FROM `fcms_users`
                WHERE `id` = ?";
        $users = $this->fcmsDatabase->getRows($sql, array($userId, $relUserId));
        if ($users === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $user    = $users[0];
        $relUser = $users[1];

        $userName    = $user['fname'].' '.$user['mname'].' '.$user['lname'];
        $relUserName = $relUser['fname'].' '.$relUser['mname'].' '.$relUser['lname'];

        switch ($type)
        {
            case 'father':
                $legend  = sprintf(T_pgettext('%s is a persons name', 'Add new father for %s'), $userName);
                $label   = T_('Father');
                $options = $this->fcmsFamilyTree->getAddFatherMotherAdditionalOptions($userId, $_POST['type'], $relUserId);
                break;

            case 'mother':
                $legend  = sprintf(T_pgettext('%s is a persons name', 'Add new mother for %s'), $userName);
                $label   = T_('Mother');
                $options = $this->fcmsFamilyTree->getAddFatherMotherAdditionalOptions($userId, $_POST['type'], $relUserId);
                break;

            case 'spouse':
                $legend  = sprintf(T_pgettext('%s is a persons name', 'Add new spouse for %s'), $userName);
                $label   = T_('Spouse');
                $options = $this->fcmsFamilyTree->getAddSpouseAdditionalOptions($userId, $relUserId);
                break;

            case 'child':
                $legend  = sprintf(T_pgettext('%s is a persons name', 'Add new child for %s'), $userName);
                $label   = T_('Child');
                $options = $this->fcmsFamilyTree->getAddChildAdditionalOptions($userId, $relUserId);
                break;

            case 'brother':
                $legend  = sprintf(T_pgettext('%s is a persons name', 'Add new brother for %s'), $userName);
                $label   = T_('Brother');
                $options = $this->fcmsFamilyTree->getAddBrotherSisterAdditionalOptions($userId, $relUserId);
                break;

            case 'brother':
                $legend  = sprintf(T_pgettext('%s is a persons name', 'Add new sister for %s'), $userName);
                $label   = T_('Sister');
                $options = $this->fcmsFamilyTree->getAddBrotherSisterAdditionalOptions($userId, $relUserId);
                break;
        }

        if ($options === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        echo '
        <form action="familytree.php?add='.$userId.'" method="post">
            <fieldset>
                <legend><span>'.$legend.'</span></legend>
                <div class="field-row">
                    <div class="field-label">
                        <label for="rel_user"><b>'.$label.'</b></label>
                    </div>
                    <div class="field-widget">'.$relUserName.'</div>
                </div>
                <div class="field-row">
                    <div class="field-label"><label for="rel_user"><b></b></label></div>
                    <div class="field-widget">
                        '.$options.'
                    </div>
                <p>
                    <input type="hidden" id="id" name="id" value="'.$userId.'"/>
                    <input type="hidden" id="type" name="type" value="'.$type.'"/>
                    <input type="hidden" id="rel_user" name="rel_user" value="'.$relUserId.'"/>
                    <input class="sub1" type="submit" id="add-relative" name="submit" value="'.T_('Add').'"/> &nbsp;
                    <a href="familytree.php?view='.$this->fcmsFamilyTree->currentTreeUserId.'">'.T_('Cancel').'</a>
                </p>
            </fieldset>
        </form>';

        $this->displayFooter();
    }

    /**
     * displayAddRelativeFormSubmit 
     * 
     * @return void
     */
    function displayAddRelativeFormSubmit ()
    {
        $validator = new FormValidator();

        $errors = $validator->validate($_POST, $this->fcmsFamilyTree->getProfile('add'));
        if ($errors !== true)
        {
            $this->displayHeader();
            displayErrors($errors);
            $this->displayFooter();
            return;
        }

        $id        = $_POST['id'];
        $relUserId = $_POST['rel_user'];

        $sql = "SELECT *
                FROM `fcms_users`
                WHERE `id` = ?
                UNION
                SELECT *
                FROM `fcms_users`
                WHERE `id` = ?";
        $users = $this->fcmsDatabase->getRows($sql, array($id, $relUserId));
        if ($users === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $user    = $users[0];
        $relUser = $users[1];

        if ($user['phpass'] != 'NONMEMBER' && $this->fcmsUser->access != 1)
        {
            $this->displayHeader();
            echo '<div class="error-alert">'.T_('You do not have permission to perform this task.').'</div>';
            $this->displayFooter();
            return;
        }

        if ($_POST['type'] == 'father' || $_POST['type'] == 'mother')
        {
            $parent1 = array('id' => $relUserId);
            $parent2 = array();
            if ($_POST['other-parent'])
            {
                $parent2['id'] = $_POST['other-parent'];
            }

            $worked = $this->fcmsFamilyTree->addParent($parent1, $id, $parent2);
        }
        elseif ($_POST['type'] == 'brother' || $_POST['type'] == 'sister')
        {
            $worked = $this->fcmsFamilyTree->addSibling($id, $relUserId, $_POST['parent']);
        }
        elseif ($_POST['type'] == 'spouse')
        {
            $children = isset($_POST['child']) ? $_POST['child'] : array();
            $worked   = $this->fcmsFamilyTree->addSpouse($user, $relUser, $children);
        }
        elseif ($_POST['type'] == 'child')
        {
            $child = array($relUserId);
            if (isset($_POST['child']))
            {
                foreach ($_POST['child'] as $childId)
                {
                    $child[] = $childId;
                }
            }

            $parent2 = array();
            if (isset($_POST['other-parent']))
            {
                $parent2['id'] = $_POST['other-parent'];
            }
            $worked = $this->fcmsFamilyTree->addChildren($child, $user, $parent2);
        }

        if ($worked === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $_SESSION['ok'] = 1;

        header("Location: familytree.php?view=".$this->fcmsFamilyTree->currentTreeUserId);
    }

    /**
     * displayFamilyTree 
     * 
     * @return void
     */
    function displayFamilyTree ()
    {
        $this->displayHeader(
            array('jsOnload' => 'initAddRelative();')
        );

        if (isset($_SESSION['ok']))
        {
            unset($_SESSION['ok']);
            displayOkMessage();
        }

        $this->fcmsFamilyTree->displayMembersTreeList();

        $oldestId = $this->fcmsFamilyTree->getOldestRelativeId($this->fcmsFamilyTree->currentTreeUserId);
        if ($oldestId === false)
        {
            $this->fcmsDatabase->displayError();
            $thid->displayFooter();
            return;
        }

        // Get oldest relative user info
        $sql = "SELECT `id`, `fname`, `mname`, `lname`, `maiden`, `dob_year`, `dob_month`, `dob_day`, `dod_year`, `dod_month`, `dod_day`, 
                    `avatar`, `gravatar`, `sex`, `phpass`
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $oldestId);
        if ($user === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        if (empty($user))
        {
            echo '<div class="error-alert">'.T_('Missing or invalid id.').'</div>';
            $this->displayFooter();
            return;
        }

        $descendants = $this->fcmsFamilyTree->getDescendantsAndSpouses(array($oldestId));

        echo '
        <div class="tree">
            <ul>
                <li>';

        $this->fcmsFamilyTree->displayPerson($user);
        $this->fcmsFamilyTree->displaySpousesAndKids($user, $descendants);

        echo '
                </li>
            </ul>
        </div>';

        $this->displayFooter();
    }

    /**
     * displayCreateUserForm 
     * 
     * @return void
     */
    function displayCreateUserForm ()
    {
        if (!isset($_GET['user']) || empty($_GET['user']))
        {
            header("Location: familytree.php");
        }

        $user = (int)$_GET['user'];

        $js = '
    var bday = new DateChooser();
    bday.setUpdateField({\'bday\':\'j\', \'bmonth\':\'n\', \'byear\':\'Y\'});
    bday.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'byear\');
    var dday = new DateChooser();
    dday.setUpdateField({\'dday\':\'j\', \'dmonth\':\'n\', \'dyear\':\'Y\'});
    dday.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'dyear\');

    initLivingDeceased();';

        $this->displayHeader(
            array(
                'modules'  => array('livevalidation', 'datechooser'),
                'jsOnload' => $js
            )
        );
        $this->fcmsFamilyTree->displayCreateUserForm($_GET['create'], $user);
        $this->displayFooter();
    }

    /**
     * displayCreateUserFormSubmit 
     * 
     * @return void
     */
    function displayCreateUserFormSubmit ()
    {
        $type = $_POST['type'];
        $id   = (int)$_POST['id'];

        $validator = new FormValidator();

        $errors = $validator->validate($_POST, $this->fcmsFamilyTree->getProfile('create'));
        if ($errors !== true)
        {
            $this->displayHeader();
            displayErrors($errors);
            $this->displayFooter();
            return;
        }

        $uniq = uniqid("");

        // birthday
        list($bYear, $bMonth, $bDay) = formatYMD($_POST['byear'], $_POST['bmonth'], $_POST['bday']);

        // death
        list($dYear, $dMonth, $dDay) = formatYMD($_POST['dyear'], $_POST['dmonth'], $_POST['dday']);

        $fname  = strip_tags($_POST['fname']);
        $mname  = strip_tags($_POST['mname']);
        $lname  = strip_tags($_POST['lname']);
        $sex    = $_POST['sex'];
        $maiden = isset($_POST['maiden']) ? strip_tags($_POST['maiden']) : '';

        // Insert new user
        $sql = "INSERT INTO `fcms_users`
                (
                    `access`, `joindate`, `fname`, `mname`, `lname`, `maiden`, `sex`, `dob_year`, `dob_month`, `dob_day`,
                    `dod_year`, `dod_month`, `dod_day`, `username`, `phpass`, `activated`
                )
                VALUES (10, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";

        $params = array(
            $fname,
            $mname, 
            $lname, 
            $maiden,
            $sex, 
            $bYear, 
            $bMonth,
            $bDay, 
            $dYear, 
            $dMonth,
            $dDay, 
            'NONMEMBER-'.$uniq, 
            'NONMEMBER'
        );

        $lastId = $this->fcmsDatabase->insert($sql, $params);
        if ($lastId === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Create empty address
        $sql = "INSERT INTO `fcms_address`
                    (`user`, `created_id`, `created`, `updated_id`, `updated`) 
                VALUES 
                    (?, ?, NOW(), ?, NOW())";

        $params = array($lastId, $this->fcmsUser->id, $this->fcmsUser->id);

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Create empty settings
        $sql = "INSERT INTO `fcms_user_settings` (`user`) 
                VALUES (?)";
        if (!$this->fcmsDatabase->insert($sql, array($lastId)))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Add this new user as a relationship
        switch ($type)
        {
            case 'father':
            case 'mother':
                $worked = $this->fcmsFamilyTree->addParent(array('id' => $lastId), $id);
                break;

            case 'brother':
            case 'sister':
                $worked  = $this->fcmsFamilyTree->addSibling($id, $lastId, $_POST['parent']);
                break;

            case 'spouse':
                $children = isset($_POST['child']) ? $_POST['child'] : array();
                $worked   = $this->fcmsFamilyTree->addSpouse(array('id' => $id), array('id' => $lastId), $children);

                break;

            case 'child':
                $parent2 = array();
                if (isset($_POST['other-parent']))
                {
                    $parent2['id'] = $_POST['other-parent'];
                }
                $worked = $this->fcmsFamilyTree->addChildren(array($lastId), array('id' => $id), $parent2);
                break;
        }

        if ($worked === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $_SESSION['ok'] = 1;

        header("Location: familytree.php?view=".$this->fcmsFamilyTree->currentTreeUserId);
    }

    /**
     * getAjaxAddRelativeMenu 
     * 
     * @return void
     */
    function getAjaxAddRelativeMenu ()
    {
        $userId = (int)$_POST['id'];

        $sql = "SELECT `id`, `fname`, `mname`, `lname`
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $userId);
        if ($user === false)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => 'getAjaxAddRelativeMenu() - could not get user info.',
                'error'   => $_POST,
                'file'    => __FILE__,
                'line'    => __LINE__,
            ));
            header("HTTP/1.0 500 Internal Server Error");
            return;
        }

        $father = array();
        $mother = array();

        // Get parents
        $parents = $this->fcmsFamilyTree->getParentsOfUsers(array($userId));
        if ($parents === false)
        {
            $this->fcmsError->displayError();
            return;
        }
        foreach ($parents as $parent)
        {
            if ($parent['sex'] == 'M')
            {
                $father[] = $parent;
            }
            else
            {
                $mother[] = $parent;
            }
        }

        $addFather  = '';
        $addMother  = '';
        $addBrother = '';
        $addSister  = '';
        if (empty($father))
        {
            $addFather = '<li><a id="father" href="?view='.$userId.'&add='.$userId.'&type=father">'.T_('Father').'</a></li>';
        }
        else
        {
            $addBrother = '<li><a id="brother" href="?view='.$userId.'&add='.$userId.'&type=brother">'.T_('Brother').'</a></li>';
            $addSister  = '<li><a id="sister" href="?view='.$userId.'&add='.$userId.'&type=sister">'.T_('Sister').'</a></li>';
        }

        if (empty($mother))
        {
            $addMother = '<li><a id="mother" href="?view='.$userId.'&add='.$userId.'&type=mother">'.T_('Mother').'</a></li>';
        }
        elseif (strlen($addBrother) == 0)
        {
            $addBrother = '<li><a id="brother" href="?view='.$userId.'&add='.$userId.'&type=brother">'.T_('Brother').'</a></li>';
            $addSister = '<li><a id="sister" href="?view='.$userId.'&add='.$userId.'&type=sister">'.T_('Sister').'</a></li>';
        }

        $name   = $user['fname'].' '.$user['mname'].' '.$user['lname'];
        $legend = sprintf(T_pgettext('%s is the name of a person', 'Add family member for %s'), $name);

        echo '<ul id="add_relative_menu">';
        echo '<li class="close">'.T_pgettext('x as in the symbol to close or exit out of something', 'X').'</li>';
        echo '<li class="header">'.$legend.'</li>';
        echo $addFather;
        echo $addMother;
        echo $addBrother;
        echo $addSister;
        echo '<li><a id="spouse" href="?view='.$userId.'&add='.$userId.'&type=spouse">'.T_('Spouse').'</a></li>';
        echo '<li><a id="child" href="?view='.$userId.'&add='.$userId.'&type=child">'.T_('Child').'</a></li>';
        echo '</ul>';
        echo '<script type="text/javascript">';
        echo '$$("#add_relative_menu .close").each(function(el) { el.observe("click", function() { $("add_relative_menu").remove(); }); });';
        echo '</script>';
    }

    /**
     * displayDeletePersonForm 
     * 
     * @return void
     */
    function displayDeletePersonForm ()
    {
        $this->displayHeader();

        $id = $_GET['delete'];

        $sql = "SELECT `phpass`
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $id);
        if ($user === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // If this is a real user, and you are not an admin, you can't edit it
        if ($user['phpass'] != 'NONMEMBER' && $this->fcmsUser->access != 1)
        {
            echo '<div class="error-alert">'.T_('You do not have permission to perform this task.').'</div>';
            $this->displayFooter();
            return;
        }

        echo '
                <div class="info-alert">
                    <form action="?delete='.$id.'&amp;confirm=1" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="?view='.$id.'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

        $this->displayFooter();
    }

    /**
     * displayDeletePersonFormSubmit 
     * 
     * @return void
     */
    function displayDeletePersonFormSubmit ()
    {
        $id = $_GET['delete'];

        $sql = "SELECT `phpass`
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $id);
        if ($user === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // If this is a real user, and you are not an admin, you can't edit it
        if ($user['phpass'] != 'NONMEMBER' && $this->fcmsUser->access != 1)
        {
            $this->displayHeader();
            echo '<div class="error-alert">'.T_('You do not have permission to perform this task.').'</div>';
            $this->displayFooter();
            return;
        }

        $sql = "DELETE FROM `fcms_relationship`
                WHERE `user` = ?
                OR `rel_user` = ?";
        if (!$this->fcmsDatabase->delete($sql, array($id, $id)))
        {
            $this->displayHeader();
            $this->fcmsDatabase->displayError();
            $this->displayFooter();

            return;
        }

        header("Location: familytree.php");
    }
}
