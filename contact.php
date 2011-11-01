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

require 'fcms.php';

init();

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Contact'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript">Event.observe(window, "load", function() { initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\'); });</script>';

// Show Header
require_once getTheme($currentUserId).'header.php';

echo '
        <div id="contact" class="centercontent">';

// Send mail
if (!empty($_POST['subject']) && !empty($_POST['email']) && !empty($_POST['name']) && !empty($_POST['msg']))
{
    $subject       = cleanOutput($_POST['subject']);
    $email         = cleanOutput($_POST['email']);
    $name          = cleanOutput($_POST['name']);
    $msg           = cleanOutput($_POST['msg'], 'html');
    $email_headers = getEmailHeaders($name, $email);

    mail(getContactEmail(), $subject, "$msg\r\n-$name", $email_headers);

    echo '
            <p>'.T_('The following message has been sent to the Administrator:').'</p>
            <p>'.$msg.'<br/>- '.$name.'</p>';

}
// Show form
else
{
    $email   = isset($_POST['email'])   ? cleanOutput($_POST['email'])       : '';
    $name    = isset($_POST['name'])    ? cleanOutput($_POST['name'])        : '';
    $subject = isset($_POST['subject']) ? cleanOutput($_POST['subject'])     : '';
    $msg     = isset($_POST['msg'])     ? cleanOutput($_POST['msg'], 'html') : '';

    echo '
            <fieldset>
                <form method="post" class="contactform" action="contact.php">
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="email"><b>'.T_('Your Email').'</b></label></div>
                        <div class="field-widget"><input type="text" id="email" name="email" size="30" value="'.$email.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="name"><b>'.T_('Your Name').'</b></label></div>
                        <div class="field-widget"><input type="text" id="name" name="name" size="30" value="'.$name.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="subject"><b>'.T_('Subject').'</b></label></div>
                        <div class="field-widget"><input type="text" id="subject" name="subject" size="30" value="'.$subject.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="msg"><b>'.T_('Message').'</b></label></div>
                        <div class="field-widget"><textarea name="msg" rows="10" cols="40">'.$msg.'</textarea></div>
                    </div>
                    <p><input type="submit" name="submit" value="'.T_('Submit').'"/></p>
                </form>
            </fieldset>';
}

echo '
        </div><!-- #contact .centercontent -->';

// Show Footer
require_once getTheme($currentUserId).'footer.php';
