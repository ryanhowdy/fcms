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

require 'fcms.php';

load('documents');

init();

$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$docs          = new Documents($currentUserId);

// Download Document
if (isset($_GET['download']))
{
    $show     = false;
    $filename = "uploads/documents/".basename($_GET['download']);
    $mimetype = isset($_GET['mime']) ? cleanInput($_GET['mime']) : 'application/download';

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

// Setup the Template variables;
$TMPL = array(
    'sitename'    => getSiteName(),
    'nav-link'    => getNavLinks(),
    'pagetitle'   => T_('Documents'),
    'path'        => URL_PREFIX,
    'displayname' => getUserDisplayName($currentUserId),
    'version'     => getCurrentVersion(),
    'year'        => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript">Event.observe(window, "load", function() { initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\'); });</script>';

// Show Header
require_once getTheme($currentUserId).'header.php';

echo '
        <div id="documents" class="centercontent">';

$show = true;

// Add new document
if (isset($_POST['submitadd']))
{
    $doc  = $_FILES['doc']['name'];
    $doc  = cleanFilename($doc);
    $desc = cleanInput($_POST['desc']);
    $mime = cleanInput($_FILES['doc']['type']);

    if ($docs->uploadDocument($_FILES['doc'], $doc))
    {
        $sql = "INSERT INTO `fcms_documents` (
                    `name`, `description`, `mime`, `user`, `date`
                ) VALUES(
                    '$doc', '$desc', '$mime', '$currentUserId', NOW()
                )";

        if (!mysql_query($sql))
        {
            displaySQLError('Document Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        displayOkMessage();

        // Email members
        $sql = "SELECT u.`email`, s.`user` 
                FROM `fcms_user_settings` AS s, `fcms_users` AS u 
                WHERE `email_updates` = '1'
                AND u.`id` = s.`user`";

        $result = mysql_query($sql);

        if (!$result)
        {
            displaySQLError('Email Updates Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if (mysql_num_rows($result) > 0)
        {
            while ($r = mysql_fetch_array($result))
            {
                $name          = getUserDisplayName($currentUserId);
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
    }
} 

// Delete document
if (isset($_POST['deldoc']))
{
    $sql = "DELETE FROM `fcms_documents` 
            WHERE `id` = ".cleanInput($_POST['id'], 'int');

    if (!mysql_query($sql))
    {
        displaySQLError('Delete Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }

    if (!unlink("uploads/documents/".basename($_POST['name'])))
    {
        echo '<p class="error-alert">'.T_('Document could not be deleted from the server.').'</p>';
        return;
    }

    displayOkMessage(T_('Document Deleted Successfully'));
}

// Show add document form
if (isset($_GET['adddoc']) && checkAccess($currentUserId) <= 5)
{
    $show = false;
    $docs->displayForm();
}

// Show list of documents
if ($show)
{
    if (checkAccess($currentUserId) <= 5)
    {
        echo '
            <div id="actions_menu" class="clearfix">
                <ul><li><a href="?adddoc=yes">'.T_('Add Document').'</a></li></ul>
            </div>';
    }

    $page = isset($_GET['page']) ? cleanInput($_GET['page'], 'int') : 1;

    $docs->showDocuments($page);
}
echo '
        </div><!-- #documents .centercontent -->';

// Show Footer
require_once getTheme($currentUserId).'footer.php';
