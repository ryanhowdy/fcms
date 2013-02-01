<?php
/**
 * Contact
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

init();

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
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Contact'),
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
        if (!empty($_POST['subject']) && !empty($_POST['email']) && !empty($_POST['name']) && !empty($_POST['msg']))
        {
            $this->displayContactFormSubmit();
        }
        else
        {
            $this->displayContactForm();
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
<script type="text/javascript">
Event.observe(window, "load", function()
{
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
});
</script>';

        include_once getTheme($this->fcmsUser->id).'header.php';

        echo '
        <div id="contact" class="centercontent">';
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
        </div><!--/contact-->';

        include_once getTheme($this->fcmsUser->id).'footer.php';
    }


    function displayContactFormSubmit ()
    {
        $subject       = $_POST['subject'];
        $email         = $_POST['email'];
        $name          = $_POST['name'];
        $msg           = $_POST['msg'];
        $email_headers = getEmailHeaders($name, $email);

        if (!mail(getContactEmail(), $subject, "$msg\r\n-$name", $email_headers))
        {
            $this->displayHeader();
            $this->displayFooter();
        }

        $_SESSION['ok'] = '<p>'.cleanOutput($msg).'<br/>- '.cleanOutput($name).'</p>';

        header("Location: contact.php");
    }

    function displayContactForm ()
    {
        $this->displayHeader();

        $email   = isset($_POST['email'])   ? cleanOutput($_POST['email'])       : '';
        $name    = isset($_POST['name'])    ? cleanOutput($_POST['name'])        : '';
        $subject = isset($_POST['subject']) ? cleanOutput($_POST['subject'])     : '';
        $msg     = isset($_POST['msg'])     ? cleanOutput($_POST['msg'], 'html') : '';

        if (isset($_SESSION['ok']))
        {
            $okMessage = '<p>'.T_('The following message has been sent to the Administrator:').'</p>'.$_SESSION['ok'];

            displayOkMessage($okMessage);

            unset($_SESSION['ok']);
        }

        echo '
            <fieldset>
                <form method="post" class="contactform" action="contact.php">
                    <div class="field-row">
                        <div class="field-label"><label for="email"><b>'.T_('Your Email').'</b></label></div>
                        <div class="field-widget"><input type="text" id="email" name="email" size="30" value="'.$email.'"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="name"><b>'.T_('Your Name').'</b></label></div>
                        <div class="field-widget"><input type="text" id="name" name="name" size="30" value="'.$name.'"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="subject"><b>'.T_('Subject').'</b></label></div>
                        <div class="field-widget"><input type="text" id="subject" name="subject" size="30" value="'.$subject.'"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="msg"><b>'.T_('Message').'</b></label></div>
                        <div class="field-widget"><textarea name="msg" rows="10" cols="40">'.$msg.'</textarea></div>
                    </div>
                    <p><input type="submit" name="submit" value="'.T_('Submit').'"/></p>
                </form>
            </fieldset>';

        $this->displayFooter();
    }
}
