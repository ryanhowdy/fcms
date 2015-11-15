<?php
/**
 * Google
 * 
 * PHP version 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2011 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     3.5.1
 */
session_start();

define('URL_PREFIX', '../');
define('GALLERY_PREFIX', '../gallery/');

require '../fcms.php';

load('socialmedia');

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
            'sitename'      => cleanOutput(getSiteName()),
            'nav-link'      => getAdminNavLinks(),
            'pagetitle'     => T_('Administration: Google'),
            'path'          => URL_PREFIX,
            'displayname'   => $fcmsUser->displayName,
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
        if (isset($_POST['submit']))
        {
            $this->displayFormSubmitPage();
        }
        else
        {
            $this->displayFormPage();
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

        include_once URL_PREFIX.'ui/admin/header.php';

        echo '
        <div id="google">';
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
        </div><!-- /google -->';

        include_once URL_PREFIX.'ui/admin/footer.php';
    }

    /**
     * displayFormPage
     * 
     * Displays the form for configuring a google api.
     * 
     * @return void
     */
    function displayFormPage ()
    {
        global $fcmsUser;

        $this->displayHeader();

        if (isset($_SESSION['success']))
        {
            echo '
        <div class="alert-message success">
            <a class="close" href="#" onclick="$(this).up(\'div\').hide(); return false;">&times;</a>
            '.T_('Changes Updated Successfully').'
        </div>';

            unset($_SESSION['success']);
        }

        $r = getGoogleConfigData();

        $clientId     = isset($r['google_client_id'])     ? cleanOutput($r['google_client_id'])     : '';
        $clientSecret = isset($r['google_client_secret']) ? cleanOutput($r['google_client_secret']) : '';

        echo '
        <div class="alert-message block-message info">
            <h1>'.T_('Google Integration').'</h1>
            <p>
                '.T_('In order to integrate Family Connections with Google, you must create a Google API Project.').'
            </p>
        </div>';

        if (empty($clientId) || empty($clientSecret))
        {
            echo '
        <div class="row">
            <div class="span4">
                <h2>'.T_('Step 1').'</h2>
                <p>
                    '.T_('Go to Google and create a new API Project.').'
                </p>
            </div>
            <div class="span12">
                <ol>
                    <li>
                        '.sprintf(T_('Open the %s.'), '<a href="http://console.developers.google.com/">'.T_('Google Developers Console').'</a>').'
                    </li>
                    <li>'.T_('Create a new Project.').'</li>
                    <li>'.T_('Click APIs & auth.').'</li>
                    <li>'.T_('Select Consent screen.').'</li>
                    <li>'.T_('Fill out the Product name and any other optional information and Save.').'
                    <li>'.T_('Select Credentials.').'</li>
                    <li>'.T_('Click Create new Client ID.').'</li>
                    <li>'.T_('Select Web application for the application type.').'</li>
                    <li>'.T_('Fill out the Authorized redirct URIs, they need to end with "settings.php?view=google&oauth2callback".').'</li>
                    <li>'.T_('Click Create Client ID.').'</li>
                </ol>
            </div><!-- /span12 -->
        </div><!-- /row -->

        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>

        <div class="row">
            <div class="span4">
                <h2>'.T_('Step 2').'</h2>
                <p>
                    '.T_('Fill out the form below with the Client ID for web application detials you created in Step 1 above.').'
                </p>
            </div>
            <div class="span12">';
        }

        echo '
                <form method="post" action="google.php">
                    <fieldset>
                        <legend>'.T_('Google').'</legend>
                        <div class="clearfix">
                            <label for="client_id">'.T_('Client ID').'</label>
                            <div class="input">
                                <input class="span6" type="text" name="client_id" id="client_id" value="'.$clientId.'"/>
                            </div>
                        </div>
                        <div class="clearfix">
                            <label for="client_secret">'.T_('Client secret').'</label>
                            <div class="input">
                                <input class="span6" type="text" name="client_secret" id="client_secret" value="'.$clientSecret.'"/>
                            </div>
                        </div>
                        <div class="actions">
                            <input class="btn primary" type="submit" name="submit" value="'.T_('Save').'"/>
                        </div>
                    </fieldset>
                </form>';

        if (empty($clientId) || empty($clientSecret))
        {
            echo '
            </div><!-- /span12 -->
        </div><!-- /row -->';
        }

        $this->displayFooter();
    }

    /**
     * displayFormSubmitPage
     * 
     * @return void
     */
    function displayFormSubmitPage ()
    {
        if (isset($_SESSION['google_client_id']))
        {
            unset($_SESSION['google_client_id']);
        }
        if (isset($_SESSION['google_client_secret']))
        {
            unset($_SESSION['google_client_secret']);
        }

        $clientId     = isset($_POST['client_id'])     ? $_POST['client_id']     : '';
        $clientSecret = isset($_POST['client_secret']) ? $_POST['client_secret'] : '';

        $sql = "UPDATE `fcms_config` 
                SET `value` = ?
                WHERE `name` = 'google_client_id'";

        if (!$this->fcmsDatabase->update($sql, $clientId))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $sql = "UPDATE `fcms_config` 
                SET `value` = ?
                WHERE `name` = 'google_client_secret'";

        if (!$this->fcmsDatabase->update($sql, $clientSecret))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $_SESSION['success'] = 1;

        header("Location: google.php");
    }
}
