<?php
/**
 * Prayers
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

load('datetime');

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
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Prayer Concerns'),
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
     * @return void
     */
    function control ()
    {
        if (isset($_GET['addconcern']) && $this->fcmsUser->access <= 5)
        {
            $this->displayAddForm();
        }
        elseif (isset($_POST['submitadd']))
        {
            $this->displayAddFormSubmit();
        }
        elseif (isset($_POST['editprayer']))
        {
            $this->displayEditForm();
        }
        elseif (isset($_POST['submitedit']))
        {
            $this->displayEditFormSubmit();
        }
        elseif (isset($_POST['delprayer']) && !isset($_POST['confirmed']))
        {
            $this->displayConfirmDelete();
        }
        elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed']))
        {
            $this->displayDeleteSubmit();
        }
        else
        {
            $this->displayPrayers();
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
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    if (!$$(\'.delform input[type="submit"]\')) { return; }
    $$(\'.delform input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    });
    return true;
});
//]]>
</script>';

        require_once getTheme($this->fcmsUser->id).'header.php';

        echo '
        <div id="prayers" class="centercontent">';
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
        </div><!--/prayers-->';

        include_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayAddForm 
     * 
     * @return void
     */
    function displayAddForm ()
    {
        $this->displayHeader();

        echo '
            <script type="text/javascript" src="ui/js/livevalidation.js"></script>
            <form method="post" name="addform" action="prayers.php">
                <fieldset>
                    <legend><span>'.T_('Add a Prayer Concern').'</span></legend>
                    <div>
                        <label for="for">'.T_('Pray For').'</label>: 
                        <input type="text" name="for" id="for" size="50" tabindex="1"/>
                    </div><br/>
                    <script type="text/javascript">
                        var ffor = new LiveValidation(\'for\', { onlyOnSubmit: true });
                        ffor.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div>
                        <textarea name="desc" id="desc" rows="10" cols="63" tabindex="2"></textarea>
                    </div>
                    <script type="text/javascript">
                        var fdesc = new LiveValidation(\'desc\', { onlyOnSubmit: "" });
                        fdesc.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div>
                        <input class="sub1" type="submit" name="submitadd" value="'.T_('Add').'" tabindex="3"/> &nbsp;
                        <a href="prayers.php">'.T_('Cancel').'</a>
                    </div>
                </fieldset>
            </form>';

        $this->displayFooter();
    }

    /**
     * displayAddFormSubmit 
     * 
     * @return void
     */
    function displayAddFormSubmit ()
    {
        $for  = strip_tags($_POST['for']);
        $desc = strip_tags($_POST['desc']);

        $sql = "INSERT INTO `fcms_prayers`
                    (`for`, `desc`, `user`, `date`) 
                VALUES
                    (?, ?, ?, NOW())";

        $params = array(
            $for, 
            $desc, 
            $this->fcmsUser->id
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
            $subject       = sprintf(T_('%s added a new Prayer Concern for %s'), $name, $for);
            $url           = getDomainAndDir();
            $email_headers = getEmailHeaders();

            foreach ($rows as $r)
            {
                $to    = getUserDisplayName($r['user']);
                $email = $r['email'];
                $msg   = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'prayers.php

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
                mail($email, $subject, $msg, $email_headers);
            }
        }

        $_SESSION['success'] = 1;

        header("Location: prayers.php");
    }

    /**
     * displayEditForm 
     * 
     * @return void
     */
    function displayEditForm ()
    {
        $this->displayHeader();

        $id   = (int)$_POST['id'];
        $for  = cleanOutput($_POST['for']);
        $desc = $_POST['desc'];

        echo '
            <script type="text/javascript" src="ui/js/livevalidation.js"></script>
            <form method="post" name="editform" action="prayers.php">
                <fieldset>
                    <legend><span>'.T_('Edit Prayer Concern').'</span></legend>
                    <div>
                        <label for="for">'.T_('Pray For').'</label>: 
                        <input type="text" name="for" id="for" size="50" tabindex="1" value="'.$for.'"/>
                    </div><br/>
                    <script type="text/javascript">
                        var ffor = new LiveValidation(\'for\', { onlyOnSubmit: true });
                        ffor.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div>
                        <textarea name="desc" id="desc" rows="10" cols="63" tabindex="2">'.$desc.'</textarea>
                    </div>
                    <script type="text/javascript">
                        var fdesc = new LiveValidation(\'desc\', { onlyOnSubmit: "" });
                        fdesc.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div>
                        <input type="hidden" name="id" value="'.(int)$id.'"/>
                        <input class="sub1" type="submit" name="submitedit" value="'.T_('Edit').'" tabindex="3"/> &nbsp;
                        <a href="prayers.php">'.T_('Cancel').'</a>
                    </div>
                </fieldset>
            </form>';

        $this->displayFooter();
    }

    /**
     * displayEditFormSubmit 
     * 
     * @return void
     */
    function displayEditFormSubmit ()
    {
        $id   = (int)$_POST['id'];
        $for  = strip_tags($_POST['for']);
        $desc = strip_tags($_POST['desc']);

        $sql = "UPDATE `fcms_prayers` 
                SET `for` = ?, 
                    `desc` = ?
                WHERE `id` = ?";

        $params = array(
            $for, 
            $desc,
            $id
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->displayHeaders();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $_SESSION['success'] = 1;

        header("Location: prayers.php");
    }

    /**
     * displayConfirmDelete 
     * 
     * @return void
     */
    function displayConfirmDelete ()
    {
        $this->displayHeader();

        $id = (int)$_POST['id'];

        echo '
            <div class="info-alert">
                <form action="prayers.php" method="post">
                    <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                    <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                    <div>
                        <input type="hidden" name="id" value="'.$id.'"/>
                        <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                        <a style="float:right;" href="prayers.php">'.T_('Cancel').'</a>
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
        $id = (int)$_POST['id'];

        $sql = "DELETE FROM `fcms_prayers` 
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->delete($sql, $id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $_SESSION['delete_success'] = 1;

        header("Location: prayers.php");
    }

    /**
     * displayPrayers 
     * 
     * @return void
     */
    function displayPrayers ()
    {
        $this->displayHeader();

        if (isset($_SESSION['success']))
        {
            displayOkMessage();

            unset($_SESSION['success']);
        }

        if (isset($_SESSION['delete_success']))
        {
            displayOkMessage(T_('Prayer Concern Deleted Successfully'));

            unset($_SESSION['delete_success']);
        }

        if ($this->fcmsUser->access <= 5)
        {
            echo '
            <div id="actions_menu">
                <ul><li><a class="action" href="?addconcern=yes">'.T_('Add a Prayer Concern').'</a></li></ul>
            </div>';
        }

        $page = getPage();

        $from = (($page * 5) - 5); 

        $sql = "SELECT p.`id`, `for`, `desc`, `user`, `date` 
                FROM `fcms_prayers` AS p, `fcms_users` AS u 
                WHERE u.`id` = p.`user` 
                ORDER BY `date` DESC 
                LIMIT $from, 5";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (count($rows) <= 0)
        {
            echo '
            <div class="blank-state">
                <h2>'.T_('Nothing to see here').'</h2>
                <h3>'.T_('Currently no one has added any Prayer Concerns.').'</h3>
                <h3><a href="?addconcern=yes">'.T_('Why don\'t you add a new Prayer Concern now?').'</a></h3>
            </div>';

            $this->displayFooter();

            return;
        }

        foreach ($rows as $r)
        {
            $date        = fixDate(T_('F j, Y, g:i a'), $this->fcmsUser->tzOffset, $r['date']);
            $displayname = getUserDisplayName($r['user']);

            echo '
            <hr/>
            <h4>'.$date.'</h4>
            <div class="edit_delete">';

            // Edit
            if ($this->fcmsUser->id == $r['user'] || $this->fcmsUser->access < 2)
            {
                echo '
            <form method="post" action="prayers.php">
                <input type="hidden" name="id" value="'.(int)$r['id'].'"/>
                <input type="hidden" name="for" value="'.cleanOutput($r['for']).'"/>
                <input type="hidden" name="desc" value="'.cleanOutput($r['desc']).'"/>
                <input type="submit" name="editprayer" value="'.T_('Edit').'" class="editbtn" title="'.T_('Edit this Prayer Concern').'"/>
            </form>';
            }

            // Delete
            if ($this->fcmsUser->access < 2)
            {
                echo '
            <form class="delform" method="post" action="prayers.php">
                <input type="hidden" name="id" value="'.(int)$r['id'].'"/>
                <input type="submit" name="delprayer" value="'.T_('Delete').'" class="delbtn" title="'.T_('Delete this Prayer Concern').'"/>
            </form>';
            }

            echo '
            </div>
            <div class="for">
                <b>'.sprintf(T_('%s asks that you please pray for...'), '<a href="profile.php?member='.(int)$r['user'].'">'.$displayname.'</a>').'</b>
                <div>'.cleanOutput($r['for']).'</div>
            </div>
            <div class="because">
                <b>'.T_('Because...').'</b>
                <div>'.parse($r['desc']).'</div>
            </div>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>';
        }

        // Display Pagination
        $sql = "SELECT count(`id`) AS c 
                FROM `fcms_prayers`";

        $r = $this->fcmsDatabase->getRow($sql);
        if ($r === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $prayercount = (int)$r['c'];
        $total_pages = ceil($prayercount / 5); 

        displayPagination('prayers.php', $page, $total_pages);

        $this->displayFooter();
    }
}
