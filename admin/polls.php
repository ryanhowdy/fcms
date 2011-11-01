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

require URL_PREFIX.'fcms.php';

load('admin', 'database', 'alerts');

init('admin/');

$currentUserId = (int)escape_string($_SESSION['login_id']);
$admin         = new Admin($currentUserId);
$alert         = new Alerts($currentUserId);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Polls'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    if (!$$(\'form.frm_line input[type="submit"]\')) { return; }
    $$(\'form.frm_line input[type="submit"]\').each(function(item) {
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

// Show Header
require_once getTheme($currentUserId, $TMPL['path']).'header.php';

echo '
        <div id="polls" class="centercontent">
            <div id="actions_menu" class="clearfix">
                <ul><li><a href="?addpoll=yes">'.T_('Add New Poll').'</a></li></ul>
            </div>';

// Remove an alert
if (isset($_GET['alert']))
{
    $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
            VALUES (
                '".cleanInput($_GET['alert'])."', 
                '$currentUserId'
            )";
    mysql_query($sql) or displaySQLError(
        'Remove Alert Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
    );
}

// Show Alerts
$alert->displayPoll($currentUserId);

// Check users access
if (checkAccess($currentUserId) > 2)
{
    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 2 (Helper) or better.').' 
                <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
}
else
{
    $show = true;

    //--------------------------------------------------------------------------
    // Edit poll
    //--------------------------------------------------------------------------
    if (isset($_POST['editsubmit']))
    {
        $show = false;
        $sql = "SELECT MAX(id) AS c FROM `fcms_polls`";
        $result = mysql_query($sql) or displaySQLError(
            'Last Poll Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
        $found = mysql_fetch_array($result);
        $latest_poll_id = $found['c'];
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
                                '$latest_poll_id', 
                                '".cleanInput($_POST['show'.$i])."', 
                                0
                            )";
                    mysql_query($sql) or displaySQLError(
                        'New Option Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
                    );
                }
                else
                {
                    $sql = "UPDATE `fcms_poll_options` 
                            SET `option` = '".cleanInput($_POST['show'.$i])."' 
                            WHERE `id` = '".cleanInput($_POST['option'.$i])."'";
                    mysql_query($sql) or displaySQLError(
                        'Option Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
                    );
                }
            }
            elseif ($_POST['option'.$i] != 'new')
            {
                $sql = "DELETE FROM `fcms_poll_options` 
                        WHERE `id` = '".cleanInput($_POST['option'.$i])."'";
                mysql_query($sql) or displaySQLError(
                    'Delete Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
                );
            }
            $i++;
        }
        echo "<meta http-equiv='refresh' content='0;URL=polls.php'>";
    }

    //--------------------------------------------------------------------------
    // Add new poll
    //--------------------------------------------------------------------------
    if (isset($_POST['addsubmit']))
    {
        $show = false;

        $sql = "INSERT INTO `fcms_polls`(`question`, `started`) 
                VALUES (
                    '".cleanInput($_POST['question'])."', 
                    NOW()
                )";
        mysql_query($sql) or displaySQLError(
            'New Poll Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );

        $poll_id = mysql_insert_id();

        $i = 1;
        while ($i <= 10)
        {
            if ($_POST['option'.$i])
            {
                $sql = "INSERT INTO `fcms_poll_options`(`poll_id`, `option`, `votes`) 
                        VALUES (
                            '$poll_id', 
                            '".cleanInput($_POST['option'.$i])."', 
                            0
                        )";
                mysql_query($sql) or displaySQLError(
                    'New Option Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
                );
            }
            $i++;
        }
        echo "<meta http-equiv='refresh' content='0;URL=polls.php'>";
    }

    //--------------------------------------------------------------------------
    // Delete poll confirmation
    //--------------------------------------------------------------------------
    if (isset($_POST['delsubmit']) && !isset($_POST['confirmed']))
    {
        $show = false;
        echo '
                <div class="info-alert clearfix">
                    <form action="polls.php" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="pollid" value="'.(int)$_POST['pollid'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="polls.php">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    }
    //--------------------------------------------------------------------------
    // Delete poll
    //--------------------------------------------------------------------------
    elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed']))
    {
        $show = false;

        $poll_id = cleanInput($_POST['pollid'], 'int');

        $sql = "DELETE FROM fcms_poll_options 
                WHERE poll_id = '$poll_id'";
        mysql_query($sql) or displaySQLError(
            'Delete Option Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
        $sql = "DELETE FROM `fcms_polls` 
                WHERE `id` = '$poll_id'";
        mysql_query($sql) or displaySQLError(
            'Delete Poll Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );

        echo "<meta http-equiv='refresh' content='0;URL=polls.php'>";
    }

    //--------------------------------------------------------------------------
    // Add poll form
    //--------------------------------------------------------------------------
    if (isset($_GET['addpoll']))
    {
        $show = false;
        $admin->displayAddPollForm();
    }

    //--------------------------------------------------------------------------
    // Edit poll form
    //--------------------------------------------------------------------------
    if (isset($_GET['editpoll']))
    { 
        $show = false;

        $id = cleanInput($_GET['editpoll'], 'int');
        $admin->displayEditPollForm($id);
    }

    //--------------------------------------------------------------------------
    // Show the existing polls
    //--------------------------------------------------------------------------
    if ($show)
    {
        $page = 1;
        if (isset($_GET['page']))
        {
            $page = cleanInput($_GET['page'], 'int');
        }
        $from = (($page * 10) - 10);

        echo '
            <br/>
            <h3>'.T_('Past Polls').'</h3>';

        $sql = "SELECT * 
                FROM fcms_polls 
                ORDER BY `started` DESC 
                LIMIT $from, 10";

        $result = mysql_query($sql) or displaySQLError(
            'Poll Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
        if (mysql_num_rows($result) > 0)
        {
            while ($r = mysql_fetch_array($result))
            {
                echo '
            <div>
                <a href="?editpoll='.$r['id'].'">'.$r['question'].'</a> - '.$r['started'].' 
                <form class="frm_line" action="polls.php" method="post">
                    <div>
                        <input type="submit" name="delsubmit" class="delbtn" value="'.T_('Delete').'" title="'.T_('Delete').'"/>
                        <input type="hidden" name="pollid" value="'.$r['id'].'"/>
                    </div>
                </form>
            </div>';
            }

            // Remove the LIMIT from the $sql statement 
            // used above, so we can get the total count
            $sql = substr($sql, 0, strpos($sql, 'LIMIT'));

            $result = mysql_query($sql) or displaySQLError(
                'Page Count Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );

            $count       = mysql_num_rows($result);
            $total_pages = ceil($count / 10); 

            displayPages("polls.php", $page, $total_pages);
        }
        else
        {
            echo '<i>'.T_('No Previous Polls').'</i>';
        }
    }
}

echo '
        </div><!-- .centercontent -->';

// Show Footer
require_once getTheme($currentUserId, $TMPL['path']).'footer.php';
