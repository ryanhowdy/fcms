<?php
/**
 * Family News
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

load('familynews', 'datetime');

init();

$fnews = new FamilyNews($fcmsError, $fcmsDatabase, $fcmsUser);
$page  = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $fnews);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsFamilyNews;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsFamilyNews)
    {
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;
        $this->fcmsFamilyNews   = $fcmsFamilyNews;

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
        if (isset($_GET['addnews']))
        {
            $this->displayAddNewsForm();
        }
        elseif (isset($_POST['submitadd']))
        {
            $this->displayAddNewsSubmit();
        }
        elseif (isset($_POST['editnews']))
        {
            $this->displayEditNewsForm();
        }
        elseif (isset($_POST['submitedit']))
        {
            $this->displayEditNewsSubmit();
        }
        elseif (isset($_POST['delnews']) && !isset($_POST['confirmed']))
        {
            $this->displayDeleteConfirmation();
        }
        elseif (isset($_POST['delnews']) && isset($_POST['confirmed']))
        {
            $this->displayDeleteSubmit();
        }
        elseif (isset($_POST['delcom']) && !isset($_POST['confirmed']))
        {
            $this->displayDeleteCommentConfirmation();
        }
        elseif (isset($_POST['delcom']) && isset($_POST['confirmed']))
        {
            $this->displayDeleteCommentSubmit();
        }
        elseif (isset($_POST['addcom']) && isset($_GET['getnews']))
        {
            $this->displayAddCommentSubmit();
        }
        elseif (isset($_GET['getnews']))
        {
            if (isset($_GET['newsid']))
            {
                $this->displayNews();
            }
            else
            {
                $this->displayUserNews();
            }
        }
        else
        {
            $this->displayLast5();
        }
    }

    /**
     * displayHeader 
     * 
     * @return void
     */
    function displayHeader ($options = null)
    {
        $params = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Family News'),
            'pageId'        => 'familynews',
            'path'          => URL_PREFIX,
            'displayname'   => getUserDisplayName($this->fcmsUser->id),
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        displayPageHeader($params, $options);

        $templateParams = array();

        if ($this->fcmsUser->access < 6 || $this->fcmsUser->access == 9)
        {
            $templateParams['latestNewsText'] = T_('Latest News');
            $templateParams['addNewsText']    = T_('Add News');

            if ($this->fcmsFamilyNews->hasNews($this->fcmsUser->id))
            {
                $templateParams['myUserId']   = $this->fcmsUser->id;
                $templateParams['myNewsText'] = T_('My News');
            }

        }

        if (!isset($_GET['addnews']) && !isset($_POST['editnews']))
        {
            $menuParams = $this->fcmsFamilyNews->getNewsListMenu();
            if ($menuParams === false)
            {
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            $templateParams = array_merge($templateParams, $menuParams);
        }

        if (!empty($templateParams))
        {
            loadTemplate('familynews', 'menu', $templateParams);
        }
    }

    /**
     * displayFooter 
     * 
     * @return void
     */
    function displayFooter()
    {
        $params = array(
            'path'    => URL_PREFIX,
            'version' => getCurrentVersion(),
            'year'    => date('Y')
        );

        loadTemplate('global', 'footer', $params);
    }

    /**
     * displayAddNewsSubmit 
     * 
     * @return void
     */
    function displayAddNewsSubmit ()
    {
        $sql = "INSERT INTO `fcms_news`
                    (`title`, `news`, `user`, `created`, `updated`)
                VALUES
                    (?, ?, ? ,NOW(), NOW())";

        $params = array($_POST['title'], $_POST['post'], $this->fcmsUser->id);

        $newNewsId = $this->fcmsDatabase->insert($sql, $params);
        if ($newNewsId === false)
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
        if ($rows === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (count($rows) > 0)
        {
            $name          = getUserDisplayName($this->fcmsUser->id);
            $url           = getDomainAndDir();
            $email_headers = getEmailHeaders();

            foreach ($rows as $r)
            {
                $to      = getUserDisplayName($r['user']);
                $subject = sprintf(T_('%s has added %s to his/her Family News'), $name, $_POST['title']);
                $email   = $r['email'];

                $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'familynews.php?getnews='.$this->fcmsUser->id.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
                mail($email, $subject, $msg, $email_headers);
            }
        }

        $user = (int)$this->fcmsUser->id;

        header("Location: familynews.php?getnews=$user&newsid=$newNewsId");
    }

    /**
     * displayEditNewsSubmit 
     * 
     * @return void
     */
    function displayEditNewsSubmit ()
    {
        $this->displayHeader();

        $id   = $_POST['id'];
        $user = $_POST['user'];

        $sql = "UPDATE `fcms_news` 
                SET `title` = ?, 
                    `news`  = ? 
                WHERE `id`  = ?";

        $params = array($_POST['title'], $_POST['post'], $_POST['id']);

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->fcmsError->displayError();
            $this->fcmsFamilyNews->displayFamilyNews($user, $id);
            $this->displayFooter();

            return;
        }

        displayOkMessage();

        $this->fcmsFamilyNews->displayFamilyNews($user, $id);
        $this->displayFooter();
    }

    /**
     * displayAddNewsForm 
     * 
     * @return void
     */
    function displayAddNewsForm ()
    {
        $this->displayHeader();

        if ($this->fcmsUser->access > 6 && $this->fcmsUser->access != 9)
        {
            echo '<p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';
            $this->displayFooter();

            return;
        }

        $this->fcmsFamilyNews->displayForm('add', $this->fcmsUser->id);
        $this->displayFooter();
    }

    /**
     * displayEditNewsForm 
     * 
     * @return void
     */
    function displayEditNewsForm ()
    {
        $this->displayHeader();
        $this->fcmsFamilyNews->displayForm('edit', $_POST['user'], $_POST['id'], $_POST['title'], $_POST['news']);
        $this->displayFooter();
    }

    /**
     * displayDeleteConfirmation 
     * 
     * @return void
     */
    function displayDeleteConfirmation ()
    {
        $this->displayHeader();

        echo '
                <div class="info-alert">
                    <form action="familynews.php?getnews='.(int)$_POST['user'].'" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="user" value="'.(int)$_POST['user'].'"/>
                            <input type="hidden" name="id" value="'.(int)$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="familynews.php?getnews='.(int)$_POST['user'].'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

        $this->displayFooter();
    }

    /**
     * displayDeleteSubmit 
     * 
     * @return void
     */
    function displayDeleteSubmit ()
    {

        $sql = "DELETE FROM `fcms_news` 
                WHERE id = ?";

        if (!$this->fcmsDatabase->delete($sql, $_POST['id']))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (isset($_GET['getnews']))
        {
            header("Location: familynews.php?getnews=".$_GET['getnews']);
        }
        else
        {
            header("Location: familynews.php");
        }
    }

    /**
     * displayDeleteCommentConfirmation 
     * 
     * @return void
     */
    function displayDeleteCommentConfirmation ()
    {
        $this->displayHeader();

        echo '
                <div class="info-alert">
                    <form action="familynews.php?getnews='.(int)$_GET['getnews'].'" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.(int)$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="familynews.php?getnews='.(int)$_GET['getnews'].'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

        $this->displayFooter();
    }

    /**
     * displayDeleteCommentSubmit 
     * 
     * @return void
     */
    function displayDeleteCommentSubmit ()
    {
        $sql = "DELETE FROM `fcms_news_comments`
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->delete($sql, $_POST['id']))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        header("Location: familynews.php?getnews=".(int)$_GET['getnews']);
    }

    /**
     * displayAddCommentSubmit 
     * 
     * @return void
     */
    function displayAddCommentSubmit ()
    {
        $com = ltrim($_POST['comment']);

        if (!empty($com))
        {
            $sql = "INSERT INTO `fcms_news_comments`
                        (`news`, `comment`, `date`, `user`)
                    VALUES
                        (?, ?, NOW(), ?)";

            $params = array(
                $_GET['newsid'],
                $com,
                $this->fcmsUser->id
            );

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }
        }

        header("Location: familynews.php?getnews=".(int)$_GET['getnews']."&newsid=".(int)$_GET['newsid']);
    }

    /**
     * displayNews 
     * 
     * @return void
     */
    function displayNews ()
    {
        $this->displayHeader(
            array(
                'jsOnload' => '
    $(\'.delnews input[type="submit"]\').click(function(e) {
        return confirmDeleteLink(this, "'.T_('Are you sure you want to DELETE this?').'", e);
    });
    $(\'.delcom input[type="submit"]\').click(function(e) {
        return confirmDeleteLink(this, "'.T_('Are you sure you want to DELETE this?').'", e);
    });'
            )
        );

        $user = (int)$_GET['getnews'];
        $nid  = (int)$_GET['newsid'];

        $this->fcmsFamilyNews->displayFamilyNews($user, $nid);

        $this->displayFooter(); 
    }

    /**
     * displayUserNews 
     * 
     * @return void
     */
    function displayUserNews ()
    {
        $this->displayHeader();

        $user = (int)$_GET['getnews'];
        $page = getPage();

        $this->fcmsFamilyNews->displayUserFamilyNews($user, $page);

        $this->displayFooter(); 
    }

    /**
     * displayLast5 
     * 
     * @return void
     */
    function displayLast5 ()
    {
        $this->displayHeader();
        $this->fcmsFamilyNews->displayLast5News();
        $this->displayFooter();
    }
}
