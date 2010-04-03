<?php
session_start();
if (get_magic_quotes_gpc()) {
    $_REQUEST = array_map('stripslashes', $_REQUEST);
    $_GET = array_map('stripslashes', $_GET);
    $_POST = array_map('stripslashes', $_POST);
    $_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');

// Check that the user is logged in
isLoggedIn('admin/');
$current_user_id = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");
include_once('../inc/admin_class.php');
include_once('../inc/database_class.php');
include_once('../inc/alerts_class.php');
$admin = new Admin($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$alert = new Alerts($database);

// Setup the Template variables;
$TMPL['pagetitle'] = _('Administration: Polls');
$TMPL['path'] = "../";
$TMPL['admin_path'] = "";
$TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    if (!$$(\'form.frm_line input[type="submit"]\')) { return; }
    $$(\'form.frm_line input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''._('Are you sure you want to DELETE this?').'\'); };
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
include_once(getTheme($current_user_id, $TMPL['path']) . 'header.php');

echo '
        <div id="polls" class="centercontent">
            <div id="actions_menu" class="clearfix">
                <ul><li><a href="?addpoll=yes">'._('Add New Poll').'</a></li></ul>
            </div>';

// Remove an alert
if (isset($_GET['alert'])) {
    $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
            VALUES (
                '".escape_string($_GET['alert'])."', 
                $current_user_id
            )";
    mysql_query($sql) or displaySQLError(
        'Remove Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
}

// Show Alerts
$alert->displayPoll($current_user_id);

// Check users access
if (checkAccess($current_user_id) > 2) {
    echo '
            <p class="error-alert">
                <b>'._('You do not have access to view this page.').'</b><br/>
                '._('This page requires an access level 2 (Helper) or better.').' 
                <a href="../contact.php">'._('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
} else {
    $show = true;

    // Edit poll
    if (isset($_POST['editsubmit'])) {
        $show = false;
        $sql = "SELECT MAX(id) AS c FROM `fcms_polls`";
        $result = mysql_query($sql) or displaySQLError(
            'Last Poll Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $found = mysql_fetch_array($result);
        $latest_poll_id = $found['c'];
        $i = 1;
        while ($i <= 10) {
            if ($_POST['show' . $i]) {
                if ($_POST['option' . $i] == 'new') {
                    $sql = "INSERT INTO `fcms_poll_options`
                                (`poll_id`, `option`, `votes`) 
                            VALUES 
                                ($latest_poll_id, '" . addslashes($_POST['show' . $i]) . "', 0)";
                    mysql_query($sql) or displaySQLError(
                        'New Option Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                } else {
                    $sql = "UPDATE `fcms_poll_options` 
                            SET `option` = '" . addslashes($_POST['show' . $i]) . "' 
                            WHERE `id` = " . $_POST['option' . $i];
                    mysql_query($sql) or displaySQLError(
                        'Option Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                }
            } elseif ($_POST['option' . $i] != 'new') {
                $sql = "DELETE FROM `fcms_poll_options` 
                        WHERE `id` = " . $_POST['option' . $i];
                mysql_query($sql) or displaySQLError(
                    'Delete Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
            }
            $i++;
        }
        echo "<meta http-equiv='refresh' content='0;URL=polls.php'>";
    }

    // Add new poll
    if (isset($_POST['addsubmit'])) {
        $show = false;
        $i = 1;
        $sql = "INSERT INTO `fcms_polls`(`question`, `started`) VALUES ('" . addslashes($_POST['question']) . "', NOW())";
        mysql_query($sql) or displaySQLError('New Poll Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
        $poll_id = mysql_insert_id();
        while ($i <= 10) {
            if ($_POST['option' . $i]) {
                $sql = "INSERT INTO `fcms_poll_options`(`poll_id`, `option`, `votes`) VALUES ($poll_id, '" . addslashes($_POST['option' . $i]) . "', 0)";
                mysql_query($sql) or displaySQLError('New Option Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
            }
            $i++;
        }
        echo "<meta http-equiv='refresh' content='0;URL=polls.php'>";
    }

    // Delete poll confirmation
    if (isset($_POST['delsubmit']) && !isset($_POST['confirmed'])) {
        $show = false;
        echo '
                <div class="info-alert clearfix">
                    <form action="polls.php" method="post">
                        <h2>'._('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'._('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="pollid" value="'.$_POST['pollid'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'._('Yes').'"/>
                            <a style="float:right;" href="polls.php">'._('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    // Delete poll
    } elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
        $show = false;
        $poll_id = $_POST['pollid'];
        $sql = "DELETE FROM fcms_poll_options WHERE poll_id = $poll_id";
        mysql_query($sql) or displaySQLError('Delete Option Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
        $sql = "DELETE FROM fcms_polls WHERE id = $poll_id";
        mysql_query($sql) or displaySQLError('Delete Poll Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
        echo "<meta http-equiv='refresh' content='0;URL=polls.php'>";
    }

    // Add poll form
    if (isset($_GET['addpoll'])) {
        $show = false;
        $admin->displayAddPollForm();
    }
    if (isset($_GET['editpoll'])) { 
        $show = false;
        $admin->displayEditPollForm($_GET['editpoll']);
    }

    // Show the existing polls
    if ($show) {
        $page = 1;
        if (isset($_GET['page'])) { $page = $_GET['page']; }
        $from = (($page * 10) - 10);

        echo '
            <br/>
            <h3>'._('Past Polls').'</h3>';

        $sql = "SELECT * FROM fcms_polls ORDER BY `started` DESC LIMIT $from, 10";
        $result = mysql_query($sql) or displaySQLError('Poll Error', 'admin/polls.php [' . __LINE__ . ']', $sql, mysql_error());
        if (mysql_num_rows($result) > 0) {
            while($r = mysql_fetch_array($result)) {
                echo '
            <div>
                <a href="?editpoll='.$r['id'].'">'.$r['question'].'</a> - '.$r['started'].' 
                <form class="frm_line" action="polls.php" method="post">
                    <div>
                        <input type="submit" name="delsubmit" class="delbtn" value="'._('Delete').'" title="'._('Delete').'"/>
                        <input type="hidden" name="pollid" value="'.$r['id'].'"/>
                    </div>
                </form>
            </div>';
            }

            // Remove the LIMIT from the $sql statement 
            // used above, so we can get the total count
            $sql = substr($sql, 0, strpos($sql, 'LIMIT'));
            $result = mysql_query($sql) or displaySQLError(
                'Page Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $count = mysql_num_rows($result);
            $total_pages = ceil($count / 10); 
            displayPages("polls.php", $page, $total_pages);
        } else {
            echo '<i>'._('No Previous Polls').'</i>';
        }
    }
}

echo '
        </div><!-- .centercontent -->';

// Show Footer
include_once(getTheme($current_user_id, $TMPL['path']) . 'footer.php');
