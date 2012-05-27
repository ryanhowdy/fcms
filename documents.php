<?php
/**
 * Documents
 * 
 * PHP versions 4 and 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2009 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     1.8
 */
session_start();

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('documents');

init();

// Globals
$docs = new Documents($fcmsUser->id);

$TMPL = array(
    'currentUserId' => $fcmsUser->id,
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Documents'),
    'path'          => URL_PREFIX,
    'displayname'   => $fcmsUser->displayName,
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
    if (isset($_GET['download']))
    {
        displayDownloadDocument();
    }
    elseif (isset($_GET['adddoc']))
    {
        displayAddDocumentForm();
    }
    elseif (isset($_POST['submitadd']))
    {
        displayAddDocumentSubmit();
    }
    elseif (isset($_POST['deldoc']))
    {
        displayDeleteDocumentSubmit();
    }
    else
    {
        displayDocuments();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    global $fcmsUser, $TMPL;

    $TMPL['javascript'] = '
<script type="text/javascript">
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
});
</script>';

    include_once getTheme($fcmsUser->id).'header.php';

    echo '
        <div id="documents" class="centercontent">';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter()
{
    global $fcmsUser, $TMPL;

    echo '
        </div><!--/documents-->';

    include_once getTheme($fcmsUser->id).'footer.php';
}

/**
 * displayDownloadDocument 
 * 
 * Download a document.
 * 
 * @return void
 */
function displayDownloadDocument ()
{
    $uploadsPath = getUploadsAbsolutePath();

    $filename = $uploadsPath.'documents/'.basename($_GET['download']);
    $mimetype = isset($_GET['mime']) ? $_GET['mime'] : 'application/download';

    header("Cache-control: private");
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: ".$mimetype);
    header("Content-Disposition: attachment; filename=".basename($filename).";");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize($filename));
    @readfile($filename);
    exit(0);
}

/**
 * displayAddDocumentForm 
 * 
 * @return void
 */
function displayAddDocumentForm ()
{
    global $docs;

    displayHeader();
    $docs->displayForm();
    displayFooter();
}

/**
 * displayAddDocumentSubmit 
 * 
 * @return void
 */
function displayAddDocumentSubmit ()
{
    global $docs, $fcmsUser;

    $doc  = $_FILES['doc']['name'];
    $doc  = cleanFilename($doc);
    $desc = escape_string($_POST['desc']);
    $mime = escape_string($_FILES['doc']['type']);

    $result = $docs->uploadDocument($_FILES['doc'], $doc);
    if ($result === false)
    {
        displayHeader();
        displayFooter();
        return;
    }

    $sql = "INSERT INTO `fcms_documents` (
                `name`, `description`, `mime`, `user`, `date`
            ) VALUES(
                '$doc', '$desc', '$mime', '$fcmsUser->id', NOW()
            )";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    // Email members
    $sql = "SELECT u.`email`, s.`user` 
            FROM `fcms_user_settings` AS s, `fcms_users` AS u 
            WHERE `email_updates` = '1'
            AND u.`id` = s.`user`";

    $result = mysql_query($sql);

    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) > 0)
    {
        while ($r = mysql_fetch_array($result))
        {
            $name          = getUserDisplayName($fcmsUser->id);
            $to            = getUserDisplayName($r['user']);
            $subject       = sprintf(T_('%s has added a new document (%s).'), $name, $doc);
            $email         = $r['email'];
            $url           = getDomainAndDir();
            $email_headers = getEmailHeaders();

            $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'documents.php


----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';


            mail($email, $subject, $msg, $email_headers);
        }
    }

    $_SESSION['ok'] = 1;

    header("Location: documents.php");
}

/**
 * displayDeleteDocumentSubmit 
 * 
 * @return void
 */
function displayDeleteDocumentSubmit ()
{
    $sql = "DELETE FROM `fcms_documents` 
            WHERE `id` = '".(int)$_POST['id']."'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $uploadsPath = getUploadsAbsolutePath();

    if (!unlink($uploadsPath.'documents/'.basename($_POST['name'])))
    {
        displayHeader();
        echo '<p class="error-alert">'.T_('Document could not be deleted from the server.').'</p>';
        displayFooter();
        return;
    }

    header("Location: documents.php");
}

/**
 * displayDocuments 
 * 
 * @return void
 */
function displayDocuments ()
{
    global $fcmsUser, $docs;

    displayHeader();

    if (checkAccess($fcmsUser->id) <= 5)
    {
        echo '
            <div id="actions_menu">
                <ul><li><a href="?adddoc=yes">'.T_('Add Document').'</a></li></ul>
            </div>';
    }

    if (isset($_SESSION['ok']))
    {
        displayOkMessage();
    }

    $page = getPage();

    $docs->showDocuments($page);

    displayFooter();
}
