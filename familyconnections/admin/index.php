<?php
/**
 * Admin Dashboard
 * 
 * PHP versions 4 and 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2011 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.9
 */
session_start();

define('URL_PREFIX', '../');
define('GALLERY_PREFIX', '../gallery/');

require URL_PREFIX.'fcms.php';

init('admin/');

$page = new Page($fcmsError, $fcmsDatabase, $fcmsUser);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => cleanOutput(getSiteName()),
            'nav-link'      => getAdminNavLinks(),
            'pagetitle'     => T_('Dashboard'),
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
        if ($this->fcmsUser->access > 2)
        {
            $this->displayInvalidAccessLevel();
        }
        elseif (isset($_GET['activate']))
        {
            $this->displayActivateMemberSubmit();
        }
        else
        {
            $this->displayDashboard();
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
<script src="'.URL_PREFIX.'ui/js/prototype.js" type="text/javascript"></script>';

        include_once URL_PREFIX.'ui/admin/header.php';

        echo '
        <div id="dashboard" class="centercontent">';
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
        </div><!--/dashboard-->';

        include_once URL_PREFIX.'ui/admin/footer.php';
    }

    /**
     * displayDashboard
     * 
     * @return void
     */
    function displayDashboard ()
    {
        $this->displayHeader();

        $hasPendingItems = false;
        $update          = '';
        $members         = '';

        // Upgrade?
        $currentVersion = getCurrentVersion();
        $latestVersion  = file('http://www.familycms.com/latest/version.php');
        $latestVersion  = $latestVersion[0];
        $versionNumber  = substr($latestVersion, 19);

        if (!$this->versionUpToDate($currentVersion, $latestVersion))
        {
            $hasPendingItems = true;

            $update  = '<h4>'.T_('New Version').'</h4>';
            $update .= '<p>'.T_('A new version is available for upgrade.');
            $update .= ' <a class="btn" href="upgrade.php">'.T_('Upgrade Now').'</a></p>';
        }

        // Members waiting activation
        $membersNeedingActivation = getMembersNeedingActivation();

        if (count($membersNeedingActivation) >= 1)
        {
            $members .= '<h4>'.T_('New Members').'</h4><ul>';

            foreach ($membersNeedingActivation as $id => $member)
            {
                $hasPendingItems = true;

                $members .= '<li>'.sprintf(T_('%s is requesting access to the site.'), $member);
                $members .= ' <a class="btn" href="?activate='.$id.'">'.T_('Activate Now').'</a></li>';
            }

            $members .= '</ul>';
        }

        if (isset($_SESSION['activate_success']))
        {
            echo '
        <div class="alert-message success">
            <a class="close" href="#" onclick="$(this).up(\'div\').hide(); return false;">&times;</a>
            '.T_('Member activated successfully').'
        </div>';

            unset($_SESSION['activate_success']);
        }

        if ($hasPendingItems)
        {
            echo '
        <div id="pending" class="alert-message block-message warning">
            <h2>'.T_('Pending Items').'</h2>
            <p>'.T_('You have the following pending items that need to be taken care of.').'</p>
            <div>
                '.$update.'
                '.$members.'
            </div>
        </div>';
        }

        $links = '';

        foreach ($this->fcmsTemplate['nav-link'] AS $type => $nav)
        {
            // Remove 'admin_' from in front
            $class = substr($type, 6);

            $links .= '
            <li class="'.$class.'">
                <a href="'.URL_PREFIX.$nav['url'].'">'.$nav['text'].'</a>
            </li>';
        }

        echo '
        <ul class="dashboard-links unstyled">'.$links.'
        </ul>';

        $this->displayFooter();
    }

    /**
     * displayActivateMemberSubmit 
     * 
     * @return void
     */
    function displayActivateMemberSubmit ()
    {
        $id       = (int)$_GET['activate'];
        $sitename = getSiteName();

        // Get Member info
        $sql = "SELECT `id`, `activity`, `joindate`, `fname`, `lname`, `email` 
                FROM `fcms_users` 
                WHERE `id` = ?";

        $member = $this->fcmsDatabase->getRow($sql, $id);
        if ($member === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // User is being activated for first time (just joined)
        if ($member['joindate'] == '0000-00-00 00:00:00')
        {
            $sql = "UPDATE `fcms_users` 
                    SET `activated` = 1, 
                        `joindate` = NOW()
                    WHERE `id` = ?";
        }
        // User has already joined, just reactivating
        else
        {
            $sql = "UPDATE `fcms_users` 
                    SET `activated` = 1
                    WHERE `id` = ?";
        }

        if (!$this->fcmsDatabase->update($sql, $id))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $subject = $sitename.': '.T_('Account Activated');
        $message = $member['fname'].' '.$member['lname'].', 

'.sprintf(T_('Your account at %s has been activated by the administrator.'), $sitename);

        mail($member['email'], $subject, $message, getEmailHeaders());

        $_SESSION['activate_success'] = 1;

        header("Location: index.php");
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
        $this->displayHeader();

        echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 2 (Helper) or better.').' 
                <a href="'.URL_PREFIX.'contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

        $this->displayFooter();
    }

    /**
     * versionUpToDate 
     * 
     * @param string $current 
     * @param string $latest 
     *
     * @return void
     */
    function versionUpToDate ($current, $latest)
    {
        $current = str_pad(trim(str_replace(".", "", substr($current, 18))), 4, "0");
        $latest  = str_pad(trim(str_replace(".", "", substr($latest,  18))), 4, "0");
        
        if ($latest <= $current)
        {
            return true;
        }

        return false;
    }
}
