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

require 'fcms.php';

load('datetime');

init();

// Globals
$currentUserId = (int)$_SESSION['login_id'];

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Prayer Concerns'),
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
 * @return void
 */
function control ()
{
    global $currentUserId;

    if (isset($_GET['addconcern']) && checkAccess($currentUserId) <= 5)
    {
        displayAddForm();
    }
    elseif (isset($_POST['submitadd']))
    {
        displayAddFormSubmit();
    }
    elseif (isset($_POST['editprayer']))
    {
        displayEditForm();
    }
    elseif (isset($_POST['submitedit']))
    {
        displayEditFormSubmit();
    }
    elseif (isset($_POST['delprayer']) && !isset($_POST['confirmed']))
    {
        displayConfirmDelete();
    }
    elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed']))
    {
        displayDeleteSubmit();
    }
    else
    {
        displayPrayers();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    global $TMPL, $currentUserId;

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

    require_once getTheme($currentUserId).'header.php';

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
    global $currentUserId, $TMPL;

    echo '
        </div><!-- #prayers .centercontent -->';

    include_once getTheme($currentUserId).'footer.php';
}

/**
 * displayAddForm 
 * 
 * @return void
 */
function displayAddForm ()
{
    displayHeader();

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

    displayFooter();
}

/**
 * displayAddFormSubmit 
 * 
 * @return void
 */
function displayAddFormSubmit ()
{
    global $currentUserId;

    $for       = strip_tags($_POST['for']);
    $cleanFor  = escape_string($for);
    $desc      = strip_tags($_POST['desc']);
    $cleanDesc = escape_string($desc);

    $sql = "INSERT INTO `fcms_prayers`(`for`, `desc`, `user`, `date`) 
            VALUES(
                '$cleanFor', 
                '$cleanDesc', 
                '$currentUserId', 
                NOW()
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
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    if (mysql_num_rows($result) > 0)
    {
        while ($r = mysql_fetch_array($result))
        {
            $name          = getUserDisplayName($currentUserId);
            $to            = getUserDisplayName($r['user']);
            $subject       = sprintf(T_('%s added a new Prayer Concern for %s'), $name, $for);
            $email         = $r['email'];
            $url           = getDomainAndDir();
            $email_headers = getEmailHeaders();

            $msg = T_('Dear').' '.$to.',

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
    displayHeader();

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

    displayFooter();
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
    $for  = escape_string($for);
    $desc = strip_tags($_POST['desc']);
    $desc = escape_string($desc);

    $sql = "UPDATE `fcms_prayers` 
            SET `for` = '$for', 
                `desc` = '$desc' 
            WHERE `id` = '$id'";
    if (!mysql_query($sql))
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        exit();
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
    displayHeader();

    $id = (int)$_POST['id'];

    echo '
            <div class="info-alert clearfix">
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

    displayFooter();
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
            WHERE `id` = '$id'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        exit();
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
    global $currentUserId;

    displayHeader();

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

    if (checkAccess($currentUserId) <= 5)
    {
        echo '
            <div id="actions_menu" class="clearfix">
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

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        exit();
    }

    if (mysql_num_rows($result) <= 0)
    {
        echo '
            <div class="blank-state">
                <h2>'.T_('Nothing to see here').'</h2>
                <h3>'.T_('Currently no one has added any Prayer Concerns.').'</h3>
                <h3><a href="?addconcern=yes">'.T_('Why don\'t you add a new Prayer Concern now?').'</a></h3>
            </div>';

        displayFooter();
        exit();
    }

    $tzOffset = getTimezone($currentUserId);

    while ($r = mysql_fetch_assoc($result))
    {
        $date        = fixDate(T_('F j, Y, g:i a'), $tzOffset, $r['date']);
        $displayname = getUserDisplayName($r['user']);

        echo '
            <hr/>
            <h4>'.$date.'</h4>
            <div class="edit_delete">';

        // Edit
        if ($currentUserId == $r['user'] || checkAccess($currentUserId) < 2)
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
        if (checkAccess($currentUserId) < 2)
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

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        exit();
    }

    $r = mysql_fetch_assoc($result);

    $prayercount = (int)$r['c'];
    $total_pages = ceil($prayercount / 5); 

    displayPagination ('prayers.php', $page, $total_pages);

    displayFooter();
}
