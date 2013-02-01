<?php
/**
 * Polls
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

define('URL_PREFIX', '../');
define('GALLERY_PREFIX', '../gallery/');

require URL_PREFIX.'fcms.php';

load('alerts');

init('admin/');

// Globals
$fcmsAlert = new Alerts($fcmsError, $fcmsDatabase, $fcmsUser);
$page      = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsAlert);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsAlert;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsAlert)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
        $this->fcmsAlert    = $fcmsAlert;

        $this->fcmsTemplate = array(
            'sitename'      => getSiteName(),
            'nav-link'      => getAdminNavLinks(),
            'pagetitle'     => T_('Administration: Polls'),
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
        if ($this->fcmsUser->access > 2)
        {
            $this->displayInvalidAccessLevel();
            return;
        }
        elseif (isset($_GET['alert']))
        {
            $this->displayRemoveAlertSubmit();
        }
        elseif (isset($_POST['delsubmit']))
        {
            if (!isset($_GET['confirmed']))
            {
                $this->displayConfirmDeleteForm();
            }
            else
            {
                $this->displayDeleteSubmit();
            }
        }
        // Edit
        elseif (isset($_GET['editpoll']))
        {
            $this->displayEditForm();
        }
        elseif (isset($_POST['editsubmit']))
        {
            $this->displayEditFormSubmit();
        }
        // Add
        elseif (isset($_GET['addpoll']))
        {
            $this->displayAddForm();
        }
        elseif (isset($_POST['addsubmit']))
        {
            $this->displayAddFormSubmit();
        }
        else
        {
            $this->displayPolls();
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
<script src="'.URL_PREFIX.'ui/js/prototype.js" type="text/javascript"></script>
<script src="'.URL_PREFIX.'ui/js/fcms.js" type="text/javascript"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    deleteConfirmationLinks("delpoll", "'.T_('Are you sure you want to DELETE this?').'");
});
//]]>
</script>';

        include_once URL_PREFIX.'ui/admin/header.php';

        echo '
        <div id="polls" class="centercontent">
            <p><a class="btn" href="?addpoll=yes">'.T_('Add New Poll').'</a></p>';
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
        </div><!--/centercontent-->';

        include_once URL_PREFIX.'ui/admin/footer.php';
    }

    /**
     * displayInvalidAccessLevel 
     * 
     * @return void
     */
    function displayInvalidAccessLevel ()
    {
        $this->displayHeader();

        echo '
            <p class="alert-message block-message error">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 2 (Helper) or better.').' 
                <a href="'.URL_PREFIX.'contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

        $this->displayFooter();
    }

    /**
     * displayRemoveAlertSubmit 
     * 
     * @return void
     */
    function displayRemoveAlertSubmit ()
    {
        $sql = "INSERT INTO `fcms_alerts`
                    (`alert`, `user`)
                VALUES
                    (?, ?)";

        $params = array(
            $_GET['alert'],
            $this->fcmsUser->id
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        header("Location: polls.php");
    }

    /**
     * displayPolls 
     * 
     * @return void
     */
    function displayPolls ()
    {
        $this->displayHeader();

        $this->fcmsAlert->displayPoll($this->fcmsUser->id);

        $page = getPage();
        $from = (($page * 10) - 10);

        $sql = "SELECT `id`, `question`, `started`
                FROM fcms_polls 
                ORDER BY `started` DESC 
                LIMIT $from, 10";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        echo '
            <h3>'.T_('Past Polls').'</h3>
            <table class="zebra-striped">
                <thead>
                    <tr>
                        <th>'.T_('Question').'</th>
                        <th>'.T_('Created').'</th>
                        <th>'.T_('Actions').'</th>
                    </tr>
                </thead>
                <tbody>';

        if (count($rows) > 0)
        {
            foreach ($rows as $r)
            {
                echo '
                    <tr>
                        <td>'.cleanOutput($r['question']).'</td>
                        <td>'.$r['started'].'</td>
                        <td>
                            <form action="polls.php" method="post">
                                <a class="btn" href="?editpoll='.$r['id'].'">'.T_('Edit').'</a>
                                <input type="submit" name="delsubmit" class="btn danger delpoll" value="'.T_('Delete').'" title="'.T_('Delete').'"/>
                                <input type="hidden" name="pollid" value="'.$r['id'].'"/>
                            </form>
                        </td>
                    </tr>';
            }

            // Remove the LIMIT from the $sql statement 
            // used above, so we can get the total count
            $sql = substr($sql, 0, strpos($sql, 'LIMIT'));

            $rows = $this->fcmsDatabase->getRows($sql);
            if ($rows === false)
            {
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }

            $count       = count($rows);
            $total_pages = ceil($count / 10); 

            displayPages("polls.php", $page, $total_pages);
        }
        else
        {
            echo '<tr><td colspan="3">'.T_('No Previous Polls').'</td></tr>';
        }

        echo '
                </tbody>
            </table>';

        $this->displayFooter();
    }

    /**
     * displayEditForm 
     * 
     * @return void
     */
    function displayEditForm ()
    {
        $this->displayHeader();

        $id = (int)$_GET['editpoll'];

        $sql = "SELECT `question`, o.`id`, `option` 
                FROM `fcms_polls` AS p, `fcms_poll_options` AS o 
                WHERE p.`id` = o.`poll_id` 
                AND p.`id` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        if (isset($_SESSION['success']))
        {
            echo '
        <div class="alert-message success">
            <a class="close" href="#" onclick="$(this).up(\'div\').hide(); return false;">&times;</a>
            '.T_('Changes Updated Successfully').'
        </div>';

            unset($_SESSION['success']);
        }

        echo '
            <form id="editform" name="editform" action="?page=admin_polls" method="post">
                <fieldset>
                    <legend><span>'.T_('Edit Poll').'</span></legend>';

        $i = 1;

        foreach ($rows as $row)
        {
            if ($i < 2)
            {
                echo '
                    <h3>'.cleanOutput($row['question']).'</h3>';
            }

            echo '
                    <div class="clearfix">
                        <label for="show'.$i.'">'.sprintf(T_('Option %s'), $i).'</label>
                        <div class="input">
                            <input type="text" name="show'.$i.'" id="show'.$i.'" ';
            if ($i < 3)
            {
                echo "class=\"required\"";
            }
            echo ' size="50" value="'.cleanOutput($row['option']).'"/>
                            <input type="hidden" name="option'.$i.'" value="'.$row['id'].'"/>';

            // Needs to be created by js
            if ($i >= 3)
            {
                echo '
                            <input type="button" name="deleteoption" class="btn small danger" style="width:auto;" value="'.T_('Delete').'" 
                                onclick="document.editform.show'.$i.'.value=\'\';"/>';
            }
            echo '
                        </div>
                    </div>';
            $i++;
        }

        while ($i < 11)
        {
            echo '
                    <div class="clearfix">
                        <label for="show'.$i.'">'.sprintf(T_('Option %s'), $i).'</label>
                        <div class="input">
                            <input type="text" id="show'.$i.'" name="show'.$i.'" size="50" value=""/>
                            <input type="hidden" name="option'.$i.'" value="new"/>
                        </div>
                    </div>';

            $i++;
        }

        echo '
                    <p class="actions">
                        <input class="btn primary" type="submit" name="editsubmit" id="editsubmit" value="'.T_('Edit').'"/>
                        <a class="btn secondary" href="polls.php">'.T_('Cancel').'</a>
                    </p>
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
        $sql = "SELECT MAX(id) AS c 
                FROM `fcms_polls`";

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $latestId = $row['c'];

        $i = 1;

        while ($i <= 10)
        {
            if ($_POST['show'.$i])
            {
                if ($_POST['option'.$i] == 'new')
                {
                    $sql = "INSERT INTO `fcms_poll_options`
                                (`poll_id`, `option`, `votes`) 
                            VALUES
                                (?, ?, 0)";

                    $params = array($latestId, $_POST['show'.$i]);

                    if (!$this->fcmsDatabase->insert($sql, $params))
                    {
                        $this->displayHeader();
                        $this->fcmsError->displayError();
                        $this->displayFooter();
                        return;
                    }
                }
                else
                {
                    $sql = "UPDATE `fcms_poll_options` 
                            SET `option` = ?
                            WHERE `id` = ?";

                    $params = array(
                        $_POST['show'.$i],
                        $_POST['option'.$i]
                    );

                    if (!$this->fcmsDatabase->update($sql, $params))
                    {
                        $this->displayHeader();
                        $this->fcmsError->displayError();
                        $this->displayFooter();
                        return;
                    }
                }
            }
            elseif ($_POST['option'.$i] != 'new')
            {
                $sql = "DELETE FROM `fcms_poll_options` 
                        WHERE `id` = ?";

                if (!$this->fcmsDatabase->delete($sql, $_POST['option'.$i]))
                {
                    $this->displayHeader();
                    $this->fcmsError->displayError();
                    $this->displayFooter();
                    return;
                }
            }

            $i++;
        }

        $_SESSION['success'] = 1;

        header("Location: polls.php");
    }

    /**
     * displayAddForm 
     * 
     * @return void
     */
    function displayAddForm ()
    {
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

        echo '
            <script type="text/javascript" src="'.URL_PREFIX.'ui/js/livevalidation.js"></script>
            <form id="addform" action="polls.php" method="post">
                <fieldset>
                    <legend><span>'.T_('Add New Poll').'</span></legend>
                    <div class="clearfix">
                        <label for="question">'.T_('Poll Question').'</label> 
                        <div class="input"><input type="text" name="question" id="question" class="required span8"/></div>
                    </div>
                    <script type="text/javascript">
                        var fq = new LiveValidation(\'question\', { onlyOnSubmit: true });
                        fq.add(Validate.Presence, { failureMessage: "'.T_('Required').'" });
                    </script>
                    <div class="clearfix">
                        <label for="option1">'.sprintf(T_('Option %s'), '1').'</label> 
                        <div class="input"><input type="text" name="option1" id="option1" class="required"/></div>
                    </div>
                    <script type="text/javascript">
                        var foption1 = new LiveValidation(\'option1\', { onlyOnSubmit: true });
                        foption1.add(Validate.Presence, {failureMessage: "'.T_('Without at least 2 options, it\'s not much of a poll is it?').'"});
                    </script>
                    <div class="clearfix">
                        <label for="option2">'.sprintf(T_('Option %s'), '2').'</label> 
                        <div class="input"><input type="text" name="option2" id="option2" class="required"/></div>
                    </div>
                    <script type="text/javascript">
                        var foption2 = new LiveValidation(\'option2\', { onlyOnSubmit: true });
                        foption2.add(Validate.Presence, {failureMessage: "'.T_('Without at least 2 options, it\'s not much of a poll is it?').'"});
                    </script>
                    <div class="clearfix">
                        <label for="option3">'.sprintf(T_('Option %s'), '3').'</label> 
                        <div class="input"><input type="text" name="option3" id="option3"/></div>
                    </div>
                    <div class="clearfix">
                        <label for="option4">'.sprintf(T_('Option %s'), '4').'</label> 
                        <div class="input"><input type="text" name="option4" id="option4"/></div>
                    </div>
                    <div class="clearfix">
                        <label for="option5">'.sprintf(T_('Option %s'), '5').'</label> 
                        <div class="input"><input type="text" name="option5" id="option5"/></div>
                    </div>
                    <div class="clearfix">
                        <label for="option6">'.sprintf(T_('Option %s'), '6').'</label> 
                        <div class="input"><input type="text" name="option6" id="option6"/></div>
                    </div>
                    <div class="clearfix">
                        <label for="option7">'.sprintf(T_('Option %s'), '7').'</label> 
                        <div class="input"><input type="text" name="option7" id="option7"/></div>
                    </div>
                    <div class="clearfix">
                        <label for="option8">'.sprintf(T_('Option %s'), '8').'</label> 
                        <div class="input"><input type="text" name="option8" id="option8"/></div>
                    </div>
                    <div class="clearfix">
                        <label for="option9">'.sprintf(T_('Option %s'), '9').'</label> 
                        <div class="input"><input type="text" name="option9" id="option9"/></div>
                    </div>
                    <div class="clearfix">
                        <label for="option10">'.sprintf(T_('Option %s'), '10').'</label> 
                        <div class="input"><input type="text" name="option10" id="option10"/></div>
                    </div>
                    <p class="actions">
                        <input class="btn primary" type="submit" name="addsubmit" value="'.T_('Add').'"/>
                        <a class="btn secondary" href="polls.php">'.T_('Cancel').'</a>
                    </p>
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
        $question = strip_tags($_POST['question']);

        $sql = "INSERT INTO `fcms_polls`
                    (`question`, `started`) 
                VALUES
                    (?, NOW())";

        $pollId = $this->fcmsDatabase->insert($sql, $question);
        if ($pollId === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $i = 1;

        while ($i <= 10)
        {
            if ($_POST['option'.$i])
            {
                $option = strip_tags($_POST['option'.$i]);

                $sql = "INSERT INTO `fcms_poll_options`
                            (`poll_id`, `option`, `votes`) 
                        VALUES
                            (?, ?, 0)";

                $params = array($pollId, $option);

                if (!$this->fcmsDatabase->insert($sql, $params))
                {
                    $this->displayHeader();
                    $this->fcmsError->displayError();
                    $this->displayFooter();
                    return;
                }
            }

            $i++;
        }

        $_SESSION['success'] = 1;

        header("Location: polls.php");
    }

    /**
     * displayConfirmDeleteForm 
     * 
     * @return void
     */
    function displayConfirmDeleteForm ()
    {
        $this->displayHeader();

        echo '
                <div class="alert-message block-message warning">
                    <form action="polls.php?confirmed=1" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div class="alert-actions">
                            <input type="hidden" name="pollid" value="'.(int)$_POST['pollid'].'"/>
                            <input class="btn danger" type="submit" id="delsubmit" name="delsubmit" value="'.T_('Yes, Delete').'"/>
                            <a class="btn secondary" href="polls.php">'.T_('No, Cancel').'</a>
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
        $id = (int)$_POST['pollid'];

        $sql = "DELETE FROM fcms_poll_options 
                WHERE id = ?";

        if (!$this->fcmsDatabase->delete($sql, $id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $sql = "DELETE FROM `fcms_polls` 
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->delete($sql, $id))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        header("Location: polls.php");
    }
}
