<?php
/**
 * Contact.
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
     * Constructor.
     *
     * @return void
     */
    public function __construct($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser = $fcmsUser;

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
        if (!empty($_POST['subject']) && !empty($_POST['email']) && !empty($_POST['name']) && !empty($_POST['msg'])) {
            $this->displayContactFormSubmit();
        } else {
            $this->displayContactForm();
        }
    }

    /**
     * displayHeader.
     *
     * @return void
     */
    public function displayHeader()
    {
        $params = [
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Contact'),
            'pageId'        => 'contact',
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
        ];

        displayPageHeader($params);
    }

    /**
     * displayFooter.
     *
     * @return void
     */
    public function displayFooter()
    {
        $params = [
            'path'      => URL_PREFIX,
            'version'   => getCurrentVersion(),
            'year'      => date('Y'),
        ];

        loadTemplate('global', 'footer', $params);
    }

    public function displayContactFormSubmit()
    {
        $subject = $_POST['subject'];
        $email = $_POST['email'];
        $name = $_POST['name'];
        $msg = $_POST['msg'];
        $email_headers = getEmailHeaders($name, $email);

        if (!mail(getContactEmail(), $subject, "$msg\r\n-$name", $email_headers)) {
            $this->displayHeader();
            $this->displayFooter();
        }

        $_SESSION['ok'] = '<p>'.cleanOutput($msg).'<br/>- '.cleanOutput($name).'</p>';

        header('Location: contact.php');
    }

    public function displayContactForm()
    {
        $this->displayHeader();

        $templateParams = [
            'emailText'   => T_('Your Email'),
            'nameText'    => T_('Your Name'),
            'subjectText' => T_('Subject'),
            'messageText' => T_('Message'),
            'submitText'  => T_('Submit'),
        ];

        $templateParams['email'] = isset($_POST['email']) ? cleanOutput($_POST['email']) : '';
        $templateParams['name'] = isset($_POST['name']) ? cleanOutput($_POST['name']) : '';
        $templateParams['subject'] = isset($_POST['subject']) ? cleanOutput($_POST['subject']) : '';
        $templateParams['message'] = isset($_POST['msg']) ? cleanOutput($_POST['msg'], 'html') : '';

        if (isset($_SESSION['ok'])) {
            $okMessage = '<p>'.T_('The following message has been sent to the Administrator:').'</p>'.$_SESSION['ok'];

            displayOkMessage($okMessage);

            unset($_SESSION['ok']);
        }

        loadTemplate('contact', 'main', $templateParams);
        $this->displayFooter();
    }
}
