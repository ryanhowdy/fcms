<?php
/**
 * Foursquare
 * 
 * PHP versions 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2011 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.6
 */
session_start();

define('URL_PREFIX', '../');
define('GALLERY_PREFIX', '../gallery/');

require URL_PREFIX.'fcms.php';

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
            'pagetitle'     => T_('Administration: Foursquare'),
            'path'          => URL_PREFIX,
            'displayname'   => $fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        $this->checkPermissions();

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

        $TMPL['javascript'] = '
<script src="'.URL_PREFIX.'ui/js/prototype.js" type="text/javascript"></script>';

        include_once URL_PREFIX.'ui/admin/header.php';

        echo '
        <div id="foursquare">';
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
        </div><!-- /foursquare -->';

        include_once URL_PREFIX.'ui/admin/footer.php';
    }

    /**
     * checkPermissions 
     * 
     * @return void
     */
    function checkPermissions ()
    {
        if ($this->fcmsUser->access > 2)
        {
            $this->displayHeader();

            echo '
                <p class="error-alert">
                    <b>'.T_('You do not have access to view this page.').'</b><br/>
                    '.T_('This page requires an access level 2 (Helper) or better.').' 
                    <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
                </p>';

            $this->displayFooter();
            die();
        }
    }

    /**
     * displayFormSubmitPage 
     * 
     * @return void
     */
    function displayFormSubmitPage ()
    {
        $id     = isset($_POST['id'])     ? $_POST['id']     : '';
        $secret = isset($_POST['secret']) ? $_POST['secret'] : '';
        $url    = isset($_POST['url'])    ? $_POST['url']    : '';

        $sql = "DELETE FROM `fcms_config`
                WHERE `name` = 'fs_client_id'
                OR `name` = 'fs_client_secret'
                OR `name` = 'fs_callback_url'";

        if (!$this->fcmsDatabase->delete($sql))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $sql = "INSERT INTO `fcms_config` (`name`, `value`)
                VALUES
                    ('fs_client_id', ?),
                    ('fs_client_secret', ?), 
                    ('fs_callback_url', ?)";

        $params = array(
            $id,
            $secret, 
            $url
        );

        $result = $this->fcmsDatabase->insert($sql, $params);
        if ($result === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $_SESSION['success'] = 1;

        header("Location: foursquare.php");
    }

    /**
     * displayFormPage 
     * 
     * @param string $displayMessage 
     * 
     * @return void
     */
    function displayFormPage ($displayMessage = '')
    {
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

        $r = getFoursquareConfigData();

        $id     = isset($r['fs_client_id'])     ? cleanOutput($r['fs_client_id'])     : '';
        $secret = isset($r['fs_client_secret']) ? cleanOutput($r['fs_client_secret']) : '';
        $url    = isset($r['fs_callback_url'])  ? cleanOutput($r['fs_callback_url'])  : '';

        if (empty($id) || empty($secret) || empty($url))
        {
            echo '
        <div class="row">
            <div class="span4">
                <h2>'.T_('Step 1').'</h2>
                <p>'.T_('Go to Foursquare and register a new app.').'</p>
            </div>
            <div class="span12">
                <h3><a href="https://foursquare.com/developers/register">'.T_('Register a new foursquare app.').'</a></h3>
                <p>
                    '.T_('Be sure to include settings.php as part of your callback url.  For example: if your site is located at http://www.my-awesome-site.com/fcms/index.php then your callback url should be http://www.my-awesome-site.com/fcms/settings.php').'
                </p>
            </div><!-- /span12 -->
        </div><!-- /row -->

        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>

        <div class="row">
            <div class="span4">
                <h2>'.T_('Step 2').'</h2>
                <p>'.T_('Fill out the form below with the information you provided in Step 1.').'</p>
            </div>
            <div class="span12">';
        }

        echo '
                <form action="foursquare.php" method="post">
                    <fieldset>
                        <legend>'.T_('Foursquare Confirguration').'</legend>
                        <div class="clearfix">
                            <label for="id">'.T_('Client ID').'</label>
                            <div class="input">
                                <input type="text" name="id" id="id" size="50" value="'.$id.'"/>
                            </div>
                        </div>
                        <div class="clearfix">
                            <label for="secret">'.T_('Client Secret').'</label>
                            <div class="input">
                                <input type="text" name="secret" id="secret" size="50" value="'.$secret.'"/>
                            </div>
                        </div>
                        <div class="clearfix">
                            <label for="url">'.T_('Callback URL').'</label>
                            <div class="input">
                                <input class="frm_text" type="text" name="url" id="url" size="50" value="'.$url.'"/>
                            </div>
                        </div>
                        <div class="actions">
                            <input class="btn primary" type="submit" name="submit" value="'.T_('Save').'"/>
                        </div>
                    </fieldset>
                </form>';

        if (empty($id) || empty($secret) || empty($url))
        {
            echo '
            </div><!-- /span12 -->
        </div><!-- /row -->';
        }

        $this->displayFooter();
    }
}
