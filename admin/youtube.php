<?php
/**
 * YouTube
 * 
 * PHP version 5
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
            'pagetitle'     => T_('Administration: YouTube'),
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

        $TMPL['javascript'] = '
<script src="'.URL_PREFIX.'ui/js/prototype.js" type="text/javascript"></script>';

        include_once URL_PREFIX.'ui/admin/header.php';

        echo '
        <div id="youtube">';
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
        </div><!-- /youtube -->';

        include_once URL_PREFIX.'ui/admin/footer.php';
    }

    /**
     * displayFormPage
     * 
     * Displays the form for configuring a youtube app.
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

        $r = getYouTubeConfigData();

        $key = isset($r['youtube_key']) ? cleanOutput($r['youtube_key']) : '';

        echo '
        <div class="alert-message block-message info">
            <h1>'.T_('YouTube Integration').'</h1>
            <p>
                '.T_('In order to integrate Family Connections with YouTube, you must get a Developer Key from Google, and provide that Key to Family Connections.').'
            </p>
        </div>';

        if (empty($key))
        {
            echo '
        <div class="row">
            <div class="span4">
                <h2>'.T_('Step 1').'</h2>
                <p>
                    '.T_('Got to Google and create a new YouTube Application.').'
                </p>
            </div>
            <div class="span12">
                <h3>
                    <a href="http://code.google.com/apis/youtube/dashboard/">'.T_('Create Youtube Application').'</a><br/>
                </h3>
            </div><!-- /span12 -->
        </div><!-- /row -->

        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>

        <div class="row">
            <div class="span4">
                <h2>'.T_('Step 2').'</h2>
                <p>
                    '.T_('Fill out the form below with the YouTube Developer Key provided by Google.').'
                </p>
            </div>
            <div class="span12">';
        }

        echo '
                <form method="post" action="youtube.php">
                    <fieldset>
                        <legend>'.T_('YouTube').'</legend>
                        <div class="clearfix">
                            <label for="key">'.T_('Developer Key').'</label>
                            <div class="input">
                                <input class="span6" type="text" name="key" id="key" value="'.$key.'"/>
                            </div>
                        </div>
                        <div class="actions">
                            <input class="btn primary" type="submit" name="submit" value="'.T_('Save').'"/>
                        </div>
                    </fieldset>
                </form>';

        if (empty($key))
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
        if (isset($_SESSION['youtube_key']))
        {
            unset($_SESSION['youtube_key']);
        }

        $key = isset($_POST['key']) ? $_POST['key'] : '';

        $sql = "UPDATE `fcms_config` 
                SET `value` = ?
                WHERE `name` = 'youtube_key'";

        if (!$this->fcmsDatabase->update($sql, $key))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $_SESSION['success'] = 1;

        header("Location: youtube.php");
    }
}
