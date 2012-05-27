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
$alert = new Alerts($fcmsUser->id);

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getAdminNavLinks(),
    'pagetitle'     => T_('Administration: Polls'),
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
    global $fcmsUser;

    if (checkAccess($fcmsUser->id) > 2)
    {
        displayInvalidAccessLevel();
        return;
    }
    elseif (isset($_GET['alert']))
    {
        displayRemoveAlertSubmit();
    }
    elseif (isset($_POST['delsubmit']))
    {
        if (!isset($_GET['confirmed']))
        {
            displayConfirmDeleteForm();
        }
        else
        {
            displayDeleteSubmit();
        }
    }
    // Edit
    elseif (isset($_GET['editpoll']))
    {
        displayEditForm();
    }
    elseif (isset($_POST['editsubmit']))
    {
        displayEditFormSubmit();
    }
    // Add
    elseif (isset($_GET['addpoll']))
    {
        displayAddForm();
    }
    elseif (isset($_POST['addsubmit']))
    {
        displayAddFormSubmit();
    }
    else
    {
        displayPolls();
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
    global $fcmsUser, $TMPL;

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
    displayHeader();

    echo '
            <p class="alert-message block-message error">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 2 (Helper) or better.').' 
                <a href="'.URL_PREFIX.'contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

    displayFooter();
}

/**
 * displayRemoveAlertSubmit 
 * 
 * @return void
 */
function displayRemoveAlertSubmit ()
{
    global $fcmsUser;

    $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
            VALUES (
                '".escape_string($_GET['alert'])."', 
                '$fcmsUser->id'
            )";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
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
    global $fcmsUser, $alert;

    displayHeader();

    $alert->displayPoll($fcmsUser->id);

    $page = getPage();
    $from = (($page * 10) - 10);

    $sql = "SELECT `id`, `question`, `started`
            FROM fcms_polls 
            ORDER BY `started` DESC 
            LIMIT $from, 10";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
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

    if (mysql_num_rows($result) > 0)
    {
        while ($r = mysql_fetch_array($result))
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

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }

        $count       = mysql_num_rows($result);
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

    displayFooter();
}

/**
 * displayEditForm 
 * 
 * @return void
 */
function displayEditForm ()
{
    displayHeader();

    $id = (int)$_GET['editpoll'];

    $sql = "SELECT `question`, o.`id`, `option` 
            FROM `fcms_polls` AS p, `fcms_poll_options` AS o 
            WHERE p.`id` = o.`poll_id` 
            AND p.`id` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
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

    while ($row = mysql_fetch_assoc($result))
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

    displayFooter();
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

    $result = mysql_query($sql);
    if (!$result)
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $found = mysql_fetch_array($result);

    $latestId = $found['c'];

    $i = 1;

    while ($i <= 10)
    {
        if ($_POST['show'.$i])
        {
            if ($_POST['option'.$i] == 'new')
            {
                $sql = "INSERT INTO `fcms_poll_options`
                            (`poll_id`, `option`, `votes`) 
                        VALUES (
                            '$latestId', 
                            '".escape_string($_POST['show'.$i])."', 
                            0
                        )";
                if (!mysql_query($sql))
                {
                    displayHeader();
                    displaySqlError($sql, mysql_error());
                    displayFooter();
                    return;
                }
            }
            else
            {
                $sql = "UPDATE `fcms_poll_options` 
                        SET `option` = '".escape_string($_POST['show'.$i])."' 
                        WHERE `id` = '".escape_string($_POST['option'.$i])."'";
                if (!mysql_query($sql))
                {
                    displayHeader();
                    displaySqlError($sql, mysql_error());
                    displayFooter();
                    return;
                }
            }
        }
        elseif ($_POST['option'.$i] != 'new')
        {
            $sql = "DELETE FROM `fcms_poll_options` 
                    WHERE `id` = '".escape_string($_POST['option'.$i])."'";
            if (!mysql_query($sql))
            {
                displayHeader();
                displaySqlError($sql, mysql_error());
                displayFooter();
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

    displayFooter();
}

/**
 * displayAddFormSubmit 
 * 
 * @return void
 */
function displayAddFormSubmit ()
{
    $question = strip_tags($_POST['question']);
    $question = escape_string($question);

    $sql = "INSERT INTO `fcms_polls`(`question`, `started`) 
            VALUES (
                '$question', 
                NOW()
            )";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $pollId = mysql_insert_id();

    $i = 1;

    while ($i <= 10)
    {
        if ($_POST['option'.$i])
        {
            $option = strip_tags($_POST['option'.$i]);
            $option = escape_string($option);

            $sql = "INSERT INTO `fcms_poll_options`(`poll_id`, `option`, `votes`) 
                    VALUES (
                        '$pollId', 
                        '$option', 
                        0
                    )";
            if (!mysql_query($sql))
            {
                displayHeader();
                displaySqlError($sql, mysql_error());
                displayFooter();
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
    displayHeader();

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

    displayFooter();
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
            WHERE id = '$id'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "DELETE FROM `fcms_polls` 
            WHERE `id` = '$id'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    header("Location: polls.php");
}
