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

            if ($ajax == 'family_member_list')
            {
                $this->getAjaxFamilyMemberList();
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
            if (isset($_POST['submit']))
            {
                $this->displayAddRelativeFormSubmit();
            }
            else
            {
                $this->displayAddRelativeForm();
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
     * @return void
     */
    function displayHeader ()
    {
        $TMPL = $this->fcmsTemplate;

        $TMPL['javascript'] = '
<script type="text/javascript" src="ui/js/livevalidation.js"></script>
<link rel="stylesheet" type="text/css" href="ui/datechooser.css"/>
<script type="text/javascript" src="ui/js/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    var bday = new DateChooser();
    bday.setUpdateField({\'bday\':\'j\', \'bmonth\':\'n\', \'byear\':\'Y\'});
    bday.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'byear\');
    var dday = new DateChooser();
    dday.setUpdateField({\'dday\':\'j\', \'dmonth\':\'n\', \'dyear\':\'Y\'});
    dday.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'dyear\');
    initLivingDeceased();
});
//]]>
</script>';

        require_once getTheme($this->fcmsUser->id).'header.php';

        echo '
        <div id="familytree-page" class="centercontent">';

    }

    /**
     * displayFooter 
     * 
     * @return void
     */
    function displayFooter()
    {
        $TMPL = $this->fcmsTemplate;

        echo '
        </div><!--/familytree-page-->';

        require_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayEditPersonForm 
     * 
     * @return void
     */
    function displayEditPersonForm ()
    {
        $this->displayHeader();

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

        $this->fcmsFamilyTree->displayAddRelativeForm($id);
        $this->displayFooter();
    }

    /**
     * displayAddRelativeFormSubmit 
     * 
     * @return void
     */
    function displayAddRelativeFormSubmit ()
    {
        $id        = (int)$_GET['add'];
        $relUserId = (int)$_POST['rel_user'];

        $validator = new FormValidator();

        $errors = $validator->validate($_POST, $this->fcmsFamilyTree->getProfile('add'));
        if ($errors !== true)
        {
            $this->displayHeader();
            displayErrors($errors);
            $this->displayFooter();
            return;
        }

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
            $worked = $this->fcmsFamilyTree->addParent($id, $_POST['type'], $relUserId);
        }
        elseif ($_POST['type'] == 'brother' || $_POST['type'] == 'sister')
        {
            $worked = $this->fcmsFamilyTree->addSibling($id, $relUserId);
        }
        elseif ($_POST['type'] == 'spouse')
        {
            $relationship = $relUser['sex'] == 'M' ? 'HUSB' : 'WIFE';
            $worked       = $this->fcmsFamilyTree->addSpouse($id, $relationship, $relUserId, $user['sex'], $relUser['sex']);
        }
        elseif ($_POST['type'] == 'child')
        {
            $worked = $this->fcmsFamilyTree->addChild($id, $relUserId);
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
        $this->displayHeader();

        if (isset($_SESSION['ok']))
        {
            unset($_SESSION['ok']);
            displayOkMessage();
        }

        $oldestId = $this->fcmsFamilyTree->getOldestRelativeId($this->fcmsFamilyTree->currentTreeUserId);

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
     * getAjaxFamilyMemberList 
     * 
     * @return void
     */
    function getAjaxFamilyMemberList ()
    {
        if (!isset($_POST['type']))
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('getAjaxFamilyMemberList() called without [type].'),
                'error'   => $_POST,
                'file'    => __FILE__,
                'line'    => __LINE__,
            ));
            header("HTTP/1.0 500 Internal Server Error");
            return;
        }

        if (!isset($_POST['user']))
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('getAjaxFamilyMemberList() called without [user].'),
                'error'   => $_POST,
                'file'    => __FILE__,
                'line'    => __LINE__,
            ));
            header("HTTP/1.0 500 Internal Server Error");
            return;
        }

        $userId = (int)$_POST['user'];

        $members = '';

        switch ($_POST['type'])
        {
            case 'father':
                $members = $this->fcmsFamilyTree->getPossibleFatherList($userId);
                break;

            case 'mother':
                $members = $this->fcmsFamilyTree->getPossibleMotherList($userId);
                break;

            case 'brother':
                $members = $this->fcmsFamilyTree->getPossibleBrotherList($userId);
                break;

            case 'sister':
                $members = $this->fcmsFamilyTree->getPossibleSisterList($userId);
                break;

            case 'spouse':
                $members = $this->fcmsFamilyTree->getPossibleSpouseList($userId);
                break;

            case 'child':
                $members = $this->fcmsFamilyTree->getPossibleChildList($userId);
                break;

            default:
                $this->error->add(array(
                    'type'    => 'operation',
                    'message' => T_('getAjaxFamilyMemberList() called with incorrect [type].'),
                    'error'   => $_POST,
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ));
                header("HTTP/1.0 500 Internal Server Error");
                return;
                break;
        }

        echo $members;
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

        $this->displayHeader();
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
                $worked = $this->fcmsFamilyTree->addParent($id, $type, $lastId);
                break;

            case 'brother':
            case 'sister':
                $worked = $this->fcmsFamilyTree->addSibling($id, $lastId);
                break;

            case 'spouse':
                $relationship = $sex == 'M' ? 'HUSB' : 'WIFE';
                $worked       = $this->fcmsFamilyTree->addSpouse($id, $relationship, $lastId);

                break;

            case 'child':
                $worked = $this->fcmsFamilyTree->addChild($id, $lastId);
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


}
