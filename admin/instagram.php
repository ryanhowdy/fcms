<?php
/**
 * Instagram
 * 
 * PHP version 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2012 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     3.0
 */
session_start();

define('URL_PREFIX', '../');

require '../fcms.php';

load('socialmedia');

init('admin/');

// Globals
$currentUserId = (int)$_SESSION['login_id'];

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getAdminNavLinks(),
    'pagetitle'     => T_('Administration: Instagram'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

control();
exit();


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
        displayFormSubmitPage();
    }
    else
    {
        displayFormPage();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    global $currentUserId, $TMPL;

    $TMPL['javascript'] = '
<script src="'.URL_PREFIX.'ui/js/prototype.js" type="text/javascript"></script>';

    include_once URL_PREFIX.'ui/admin/header.php';

    echo '
        <div id="instagram">';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    global $currentUserId, $TMPL;

    echo '
        </div><!--/instagram-->';

    include_once URL_PREFIX.'ui/admin/footer.php';
}

/**
 * displayFormPage
 * 
 * Displays the form for configuring a instagram app.
 * 
 * @return void
 */
function displayFormPage ()
{
    global $currentUserId;

    displayHeader();

    if (isset($_SESSION['success']))
    {
        echo '
        <div class="alert-message success">
            <a class="close" href="#" onclick="$(this).up(\'div\').hide(); return false;">&times;</a>
            '.T_('Changes Updated Successfully').'
        </div>';

        unset($_SESSION['success']);
    }

    $r = getInstagramConfigData();

    $client_id     = isset($r['instagram_client_id'])     ? cleanOutput($r['instagram_client_id'])     : '';
    $client_secret = isset($r['instagram_client_secret']) ? cleanOutput($r['instagram_client_secret']) : '';

    echo '
        <div class="alert-message block-message info">
            <h1>'.T_('Instagram Integration').'</h1>
            <p>
                '.T_('In order to integrate Family Connections with Instagram, you must create a new Instagram client as Instagram.com, and configure that client in Family Connections.').'
            </p>
        </div>';

    if (empty($client_id) || empty($client_secret))
    {
        echo '
        <div class="row">
            <div class="span4">
                <h2>'.T_('Step 1').'</h2>
                <p>
                    '.T_('Got to Instagram and register a new Instagram client.').'
                </p>
            </div>
            <div class="span12">
                <h3>
                    <a href="http://instagram.com/developer/manage/">'.T_('Register new Instagram Client').'</a><br/>
                </h3>
                <p>
                    '.T_('Make sure you add <code>settings.php?view=instagram</code> to your Callback URL.').'
                </p>
            </div><!-- /span12 -->
        </div><!-- /row -->

        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>

        <div class="row">
            <div class="span4">
                <h2>'.T_('Step 2').'</h2>
                <p>
                    '.T_('Fill out the form below with the Instagram Client Id and Client Secret.').'
                </p>
            </div>
            <div class="span12">';
    }

    echo '
                <form method="post" action="instagram.php">
                    <fieldset>
                        <legend>Instagram</legend>
                        <div class="clearfix">
                            <label for="id">'.T_('Client ID').'</label>
                            <div class="input">
                                <input class="span6" type="text" name="id" id="id" value="'.$client_id.'"/>
                            </div>
                        </div>
                        <div class="clearfix">
                            <label for="secret">'.T_('Client Secret').'</label>
                            <div class="input">
                                <input class="span6" type="text" name="secret" id="secret" value="'.$client_secret.'"/>
                            </div>
                        </div>
                        <div class="actions">
                            <input class="btn primary" type="submit" name="submit" value="'.T_('Save').'"/>
                        </div>
                    </fieldset>
                </form>';

    if (empty($client_id) || empty($client_secret))
    {
        echo '
            </div><!-- /span12 -->
        </div><!-- /row -->';
    }

    displayFooter();
}

/**
 * displayFormSubmitPage
 * 
 * @return void
 */
function displayFormSubmitPage ()
{
    if (isset($_SESSION['instagram_client_id']))
    {
        unset($_SESSION['instagram_client_id']);
    }

    if (isset($_SESSION['instagram_client_secret']))
    {
        unset($_SESSION['instagram_client_secret']);
    }

    $id     = isset($_POST['id'])     ? escape_string($_POST['id'])     : '';
    $secret = isset($_POST['secret']) ? escape_string($_POST['secret']) : '';

    $sql = "UPDATE `fcms_config` 
            SET `value` = '$id'
            WHERE `name` = 'instagram_client_id'";
    
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "UPDATE `fcms_config` 
            SET `value` = '$secret'
            WHERE `name` = 'instagram_client_secret'";
    
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $_SESSION['success'] = 1;

    header("Location: instagram.php");
}
