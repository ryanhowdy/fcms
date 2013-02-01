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

load('familytree', 'image', 'datetime');

init();

$ftree = new FamilyTree($fcmsError, $fcmsDatabase, $fcmsUser);
$img   = new Image($fcmsUser->id);
$page  = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $ftree, $img);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsFamilyTree;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsFamilyTree, $fcmsImage)
    {
        $this->fcmsError         = $fcmsError;
        $this->fcmsDatabase      = $fcmsDatabase;
        $this->fcmsUser          = $fcmsUser;
        $this->fcmsFamilyTree    = $fcmsFamilyTree;
        $this->fcmsImage         = $fcmsImage;

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

        // Set the user's tree we are currently viewing
        if (isset($_GET['tree']))
        {
            $this->currentTreeUserId = (int)$_GET['tree'];
        }
        elseif (isset($_SESSION['currentTreeUserId']))
        {
            $this->currentTreeUserId = $_SESSION['currentTreeUserId'];
        }
        else
        {
            $this->currentTreeUserId = $this->fcmsUser->id;
        }

        $_SESSION['currentTreeUserId'] = $this->currentTreeUserId;

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
        if (isset($_GET['advanced_avatar']))
        {
            $this->uploadAdvancedAvatar();
        }
        elseif (isset($_POST['add-leaf']))
        {
            $this->displayAddRelationshipSubmit();
        }
        elseif (isset($_GET['add']) && isset($_GET['user']))
        {
            $this->displayAddRelationshipForm();
        }
        elseif (isset($_POST['add-user']))
        {
            $this->displayAddNewUserSubmit();
        }
        elseif (isset($_GET['create']) && isset($_GET['type']) && isset($_GET['id']))
        {
            $this->displayAddNewUserForm();
        }
        elseif (isset($_GET['edit']))
        {
            $this->displayEditUserForm();
        }
        elseif (isset($_POST['edit-user']))
        {
            $this->displayEditUserSubmit();
        }
        elseif (isset($_GET['avatar']))
        {
            if (isset($_POST['submitUpload']))
            {
                $this->displayUploadAvatarSubmit();
            }
            else
            {
                $this->displayUploadAvatarForm();
            }
        }
        elseif (isset($_GET['remove']))
        {
            if ($_GET['remove'] == 'user')
            {
                $this->displayRemoveFamilyTreeForm();
            }
            else
            {
                $this->displayRemoveFamilyTreeSubmit();
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
     * uploadAdvancedAvatar 
     * 
     * @return void
     */
    function uploadAdvancedAvatar ()
    {
        $userid = (int)$_GET['advanced_avatar'];

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
                SET `avatar` = '".$name."'
                WHERE `id` = '$userid'";

        if (!$this->fcmsDatabase->update($sql, array($name, $userid)))
        {
            echo "FAILURE: Could not update db with new avatar.\n";
            exit();
        }

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadsPath.'avatar/'.$name))
        {
            echo "success";
        }
        else
        {
            $this->fcmsError->add(array(
                'type'    => 'operation',
                'message' => T_('Could not move avatar file.'),
                'line'    => __LINE__,
                'file'    => __FILE__,
            ));

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

        exit();
    }

    /**
     * displayAddRelationshipSubmit 
     * 
     * TODO - fix error handling
     * 
     * @return void
     */
    function displayAddRelationshipSubmit ()
    {
        $user         = (int)$_POST['user'];
        $relationship = $_POST['relationship'];
        $rel_user     = (int)$_POST['rel_user'];

        // Spouse
        if ($relationship == 'WIFE' || $relationship == 'HUSB')
        {
            $worked = $this->fcmsFamilyTree->addSpouse($user, $relationship, $rel_user);
        }
     
        // Child
        if ($relationship == 'CHIL')
        {
            $worked = $this->fcmsFamilyTree->addChild($user, $relationship, $rel_user);
        }

        if ($worked === false)
        {
            // error has already been displayed
            // TODO - it shouldn't
            return;
        }

        $_SESSION['ok'] = 1;

        header("Location: familytree.php");
    }

    /**
     * displayAddNewUserSubmit 
     * 
     * TODO - error handling
     * 
     * @return void
     */
    function displayAddNewUserSubmit ()
    {
        $type = $_POST['type'];
        $id   = (int)$_POST['id'];

        // Missing req field
        if (!isset($_POST['fname']) or !isset($_POST['lname']) or !isset($_POST['sex']))
        {
            $this->displayHeader();

            echo '<p class="error-alert">'.T_('Missing Required Field').'</p>';

            $this->fcmsFamilyTree->displayCreateUserForm($type, $id);
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
        $maiden = $_POST['maiden'];

        // Insert new user
        $sql = "INSERT INTO `fcms_users`
                (
                    `access`, `joindate`, `fname`, `mname`, `lname`, `maiden`, `sex`, `dob_year`, `dob_month`, `dob_day`,
                    `dod_year`, `dod_month`, `dod_day`, `username`, `password`, `activated`
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
        if ($type == 'wife' || $type == 'husb')
        {
            $rel    = strtoupper($type);
            $worked = $this->fcmsFamilyTree->addSpouse($lastId, $rel, $id);
        }
        if ($type == 'mom' || $type == 'dad')
        {
            $worked = $this->fcmsFamilyTree->addChild($lastId, 'CHIL', $id);
        }
        if ($type == 'child')
        {
            $worked = $this->fcmsFamilyTree->addChild($id, 'CHIL', $lastId);
        }

        if ($worked === false)
        {
            // error has already been displayed
            // TODO - it shouldn't
            return;
        }

        $_SESSION['ok'] = 1;

        header("Location: familytree.php");
    }

    /**
     * displayEditUserSubmit 
     * 
     * @return void
     */
    function displayEditUserSubmit ()
    {
        // birthday
        list($bYear, $bMonth, $bDay) = formatYMD($_POST['byear'], $_POST['bmonth'], $_POST['bday']);

        // death
        list($dYear, $dMonth, $dDay) = formatYMD($_POST['dyear'], $_POST['dmonth'], $_POST['dday']);

        $fname  = strip_tags($_POST['fname']);
        $mname  = strip_tags($_POST['mname']);
        $lname  = strip_tags($_POST['lname']);

        $sql = "UPDATE `fcms_users`
                SET `fname`     = ?,
                    `mname`     = ?, 
                    `lname`     = ?, 
                    `maiden`    = ?,
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
     * displayAddRelationshipForm 
     * 
     * @return void
     */
    function displayAddRelationshipForm ()
    {
        $this->displayHeader();

        $add  = $_GET['add'];
        $user = (int)$_GET['user'];

        // Children
        if ($add == 'child')
        {
            $this->fcmsFamilyTree->displayAddChildForm($user);
        }
        // Spouse
        elseif ($add == 'wife' || $add == 'husb')
        {
            $this->fcmsFamilyTree->displayAddSpouseForm($add, $user);
        }
        // Parents
        else
        {
            $this->fcmsFamilyTree->displayAddParentForm($add, $user);
        }

        $this->displayFooter();
    }

    /**
     * displayAddNewUserForm 
     * 
     * @return void
     */
    function displayAddNewUserForm ()
    {
        $valid_types = array('dad', 'mom', 'child', 'wife', 'husb');

        $type = $_GET['type'];
        $id   = (int)$_GET['id'];

        if (!in_array($type, $valid_types))
        {
            header("Location: familytree.php");
            return;
        }

        $this->displayHeader();
        $this->fcmsFamilyTree->displayCreateUserForm($type, $id);
        $this->displayFooter();
    }

    /**
     * displayEditUserForm 
     * 
     * @return void
     */
    function displayEditUserForm ()
    {
        $this->displayHeader();

        $id = (int)$_GET['edit'];

        $this->fcmsFamilyTree->displayEditForm($id);
        $this->displayFooter();
    }

    /**
     * displayUploadAvatarForm 
     * 
     * @return void
     */
    function displayUploadAvatarForm ()
    {
        $this->displayHeader();

        $id = (int)$_GET['avatar'];

        $this->fcmsFamilyTree->displayEditAvatarForm($id);
        $this->displayFooter();
    }

    /**
     * displayUploadAvatarSubmit 
     * 
     * TODO
     * 
     * @return void
     */
    function displayUploadAvatarSubmit ()
    {
        $uploadsPath = getUploadsAbsolutePath();

        $this->fcmsImage->destination  = $uploadsPath.'avatar/';
        $this->fcmsImage->resizeSquare = true;
        $this->fcmsImage->uniqueName   = true;

        $this->fcmsImage->upload($_FILES['avatar']);

        if ($this->fcmsImage->error == 1)
        {
            $this->displayHeader();
            echo '
            <p class="error-alert">
                '.sprintf(T_('Photo [%s] is not a supported photo type.  Photos must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $this->fcmsImage->name).'
            </p>';

            $this->displayFooter();

            return;
        }

        $this->fcmsImage->resize(80, 80);

        if ($this->fcmsImage->error > 0)
        {
            $this->displayHeader();
            echo '
            <p class="error-alert">
                '.T_('There was an error uploading your avatar.').'
            </p>';

            $this->displayFooter();

            return;
        }

        $sql = "UPDATE `fcms_users`
                SET `avatar` = ?
                WHERE `id`   = ?";

        $params = array(
            $this->fcmsImage->name,
            $_GET['avatar']
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if ($_POST['avatar_orig'] != 'no_avatar.jpg' && $_POST['avatar_orig'] != 'gravatar')
        {
            unlink($uploadsPath.'avatar/'.basename($_POST['avatar_orig']));
        }

        $_SESSION['ok'] = 1;

        header("Location: familytree.php");
    }

    /**
     * displayRemoveFamilyTreeForm 
     * 
     * @return void
     */
    function displayRemoveFamilyTreeForm ()
    {
        $this->displayHeader();

        $id = (int)$_GET['id'];

        $this->fcmsFamilyTree->displayFamilyTree($id, 'list_edit');
        $this->displayFooter();
    }

    /**
     * displayRemoveFamilyTreeSubmit 
     * 
     * @return void
     */
    function displayRemoveFamilyTreeSubmit ()
    {
        $id = (int)$_GET['remove'];

        $sql = "DELETE FROM `fcms_relationship`
                WHERE `user` = '$id'
                OR `rel_user` = '$id'";

        if (!$this->fcmsDatabase->delete($sql, array($id, $id)))
        {
            $this->fcmsError->displayError();

            return;
        }

        $_SESSION['ok'] = 1;

        header("Location: familytree.php");
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

        $this->fcmsFamilyTree->displayFamilyTree($this->currentTreeUserId);
        $this->displayFooter();
    }
}
