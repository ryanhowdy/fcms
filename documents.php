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

load('documents', 'datetime');

init();

$docs = new Documents($fcmsError, $fcmsDatabase, $fcmsUser);
$page = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $docs);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsDocument;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsDocument)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
        $this->fcmsDocument = $fcmsDocument;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Documents'),
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
        if (isset($_GET['download']))
        {
            $this->displayDownloadDocument();
        }
        elseif (isset($_GET['adddoc']))
        {
            $this->displayAddDocumentForm();
        }
        elseif (isset($_POST['submitadd']))
        {
            $this->displayAddDocumentSubmit();
        }
        elseif (isset($_POST['deldoc']))
        {
            $this->displayDeleteDocumentSubmit();
        }
        else
        {
            $this->displayDocuments();
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
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
});
</script>';

        include_once getTheme($this->fcmsUser->id).'header.php';

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
        $TMPL = $this->fcmsTemplate;

        echo '
        </div><!--/documents-->';

        include_once getTheme($this->fcmsUser->id).'footer.php';
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
        $this->displayHeader();
        $this->fcmsDocument->displayForm();
        $this->displayFooter();
    }

    /**
     * displayAddDocumentSubmit 
     * 
     * @return void
     */
    function displayAddDocumentSubmit ()
    {
        $doc  = $_FILES['doc']['name'];
        $doc  = cleanFilename($doc);
        $desc = $_POST['desc'];
        $mime = $_FILES['doc']['type'];

        $result = $this->fcmsDocument->uploadDocument($_FILES['doc'], $doc);
        if ($result === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $sql = "INSERT INTO `fcms_documents` (
                    `name`, `description`, `mime`, `user`, `date`
                ) VALUES(
                    ?, ?, ?, ?, NOW()
                )";

        $params = array(
            $doc, $desc, $mime, $this->fcmsUser->id
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // Email members
        $sql = "SELECT u.`email`, s.`user` 
                FROM `fcms_user_settings` AS s, `fcms_users` AS u 
                WHERE `email_updates` = '1'
                AND u.`id` = s.`user`";

        $rows = $this->fcmsDatabase->getRows($sql);

        if (count($rows) > 0)
        {
            $name          = getUserDisplayName($this->fcmsUser->id);
            $url           = getDomainAndDir();
            $subject       = sprintf(T_('%s has added a new document (%s).'), $name, $doc);
            $email_headers = getEmailHeaders();

            foreach($rows as $r)
            {
                $to      = getUserDisplayName($r['user']);
                $email   = $r['email'];

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
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->delete($sql, $_POST['id']))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $uploadsPath = getUploadsAbsolutePath();

        if (!unlink($uploadsPath.'documents/'.basename($_POST['name'])))
        {
            $this->displayHeader();
            echo '<p class="error-alert">'.T_('Document could not be deleted from the server.').'</p>';
            $this->displayFooter();
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
        $this->displayHeader();

        if ($this->fcmsUser->access <= 5)
        {
            echo '
            <div id="actions_menu">
                <ul><li><a href="?adddoc=yes">'.T_('Add Document').'</a></li></ul>
            </div>';
        }

        if (isset($_SESSION['ok']))
        {
            unset($_SESSION['ok']);
            displayOkMessage();
        }

        $page = getPage();

        $this->fcmsDocument->showDocuments($page);

        $this->displayFooter();
    }
}
