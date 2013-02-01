<?php
/**
 * Facebook
 * 
 * PHP versions 4 and 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.5
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
            'pagetitle'     => T_('Facebook'),
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
            $this->displayFormSubmit();
        }
        else
        {
            $this->displayForm();
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
        <div id="facebook">';
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
        </div><!-- /facebook -->';

        include_once URL_PREFIX.'ui/admin/footer.php';
    }

    /**
     * displayForm 
     * 
     * Displays the form for configuring a facebook app.
     * 
     * @return void
     */
    function displayForm ()
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

        $r = getFacebookConfigData();

        $id     = isset($r['fb_app_id']) ? $r['fb_app_id'] : '';
        $secret = isset($r['fb_secret']) ? $r['fb_secret'] : '';

        echo '
        <div class="alert-message block-message info">
            <h1>'.T_('Facebook Integration').'</h1>
            <p>
                '.T_('In order to integrate Family Connections with Facebook, you must create a new Facebook app, and configure that app in Family Connections.').'
            </p>
        </div>';

        if (empty($id) || empty($secret))
        {
            echo '
        <div class="row">
            <div class="span4">
                <h2>'.T_('Step 1').'</h2>
                <p>
                    '.T_('Got to Facebook and create a new Application.').'
                </p>
            </div>
            <div class="span12">
                <h3><a href="http://www.facebook.com/developers/createapp.php">'.T_('Create Facebook Application').'</a></h3>
                <p>
                    '.T_('You don\'t really have to fill out any additional information other than the application name.').'
                </p>
            </div>
        </div><!-- /row -->

        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>

        <div class="row">
            <div class="span4">
                <h2>'.T_('Step 2').'</h2>
                <p>
                    '.T_('Fill out the form with the App Id and App Secret from your newly created Facebook Application.').'
                </p>
            </div>
            <div class="span12">';
        }

        echo '
            <form method="post" action="facebook.php">
                <fieldset>
                    <legend>'.T_('Facebook Application').'</legend>
                    <div class="clearfix">
                        <label for="id">'.T_('App ID').'</label>
                        <div class="input">
                            <input class="frm_text" type="text" name="id" id="id" size="50" value="'.cleanOutput($id).'"/>
                        </div>
                    </div>
                    <div class="clearfix">
                        <label for="secret">'.T_('App Secret').'</label>
                        <div class="input">
                            <input class="frm_text" type="text" name="secret" id="secret" size="50" value="'.cleanOutput($secret).'"/>
                        </div>
                    </div>
                    <div class="actions">
                        <input class="btn primary" type="submit" name="submit" value="'.T_('Save').'"/>
                    </div>
                </fieldset>
            </form>';

        if (empty($id) || empty($secret))
        {
            echo '
            </div><!-- /span12 -->
        </div><!-- /row -->';
        }

        $this->displayFooter();
    }

    /**
     * displayFormSubmit 
     * 
     * @return void
     */
    function displayFormSubmit ()
    {
        $id     = isset($_POST['id'])     ? $_POST['id']     : '';
        $secret = isset($_POST['secret']) ? $_POST['secret'] : '';

        $sql = "UPDATE `fcms_config`
                SET `value` = ?
                WHERE `name` = 'fb_app_id'";

        if (!$this->fcmsDatabase->update($sql, $id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $sql = "UPDATE `fcms_config`
                SET `value` = ?
                WHERE `name` = 'fb_secret'";
        
        if (!$this->fcmsDatabase->update($sql, $secret))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $_SESSION['success'] = 1;

        header("Location: facebook.php");
    }
}
