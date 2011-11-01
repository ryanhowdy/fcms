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

load('prayers');

init();

$currentUserId = cleanInput($_SESSION['login_id'], 'int');
$prayers       = new Prayers($currentUserId);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Prayer Concerns'),
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

// Show Header
require_once getTheme($currentUserId).'header.php';

echo '
        <div id="prayers" class="centercontent">';

$show = true;

//------------------------------------------------------------------------------
// Add prayer concern
//------------------------------------------------------------------------------
if (isset($_POST['submitadd']))
{
    $for  = cleanInput($_POST['for']);
    $desc = cleanInput($_POST['desc']);

    $sql = "INSERT INTO `fcms_prayers`(`for`, `desc`, `user`, `date`) 
            VALUES(
                '$for', 
                '$desc', 
                '$currentUserId', 
                NOW()
            )";
    if (!mysql_query($sql))
    {
        displaySQLError('New Prayer Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        exit();
    }

    echo '
            <p class="ok-alert" id="add">'.T_('Prayer Concern Added Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'add\').toggle()",3000); }
            </script>';

    // Email members
    $sql = "SELECT u.`email`, s.`user`
            FROM `fcms_user_settings` AS s, `fcms_users` AS u 
            WHERE `email_updates` = '1'
            AND u.`id` = s.`user`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Email Updates Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        exit();
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
}

//------------------------------------------------------------------------------
// Edit prayer concern
//------------------------------------------------------------------------------
if (isset($_POST['submitedit']))
{
    $for  = cleanInput($_POST['for']);
    $desc = cleanInput($_POST['desc']);

    $sql = "UPDATE `fcms_prayers` 
            SET `for` = '$for', `desc` = '$desc' 
            WHERE `id` = '".cleanInput($_POST['id'], 'int')."'";
    if (!mysql_query($sql))
    {
        displaySQLError('Edit Prayer Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        exit();
    }

    echo '
            <p class="ok-alert" id="edit">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'edit\').toggle()",3000); }
            </script>';
}

//------------------------------------------------------------------------------
// Delete confirmation
//------------------------------------------------------------------------------
if (isset($_POST['delprayer']) && !isset($_POST['confirmed']))
{
    $show = false;

    echo '
            <div class="info-alert clearfix">
                <form action="prayers.php" method="post">
                    <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                    <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                    <div>
                        <input type="hidden" name="id" value="'.(int)$_POST['id'].'"/>
                        <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                        <a style="float:right;" href="prayers.php">'.T_('Cancel').'</a>
                    </div>
                </form>
            </div>';

}
//------------------------------------------------------------------------------
// Delete prayer concern
//------------------------------------------------------------------------------
elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed']))
{
    $sql = "DELETE FROM `fcms_prayers` 
            WHERE `id` = '".cleanInput($_POST['id'], 'int')."'";
    if (!mysql_query($sql))
    {
        displaySQLError('Delete Prayer Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        exit();
    }

    echo '
            <p class="ok-alert" id="del">'.T_('Prayer Concern Deleted Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'del\').toggle()",2000); }
            </script>';
}

//------------------------------------------------------------------------------
// Add Form
//------------------------------------------------------------------------------
if (isset($_GET['addconcern']) && checkAccess($currentUserId) <= 5)
{
    $show = false;
    $prayers->displayForm('add');
}

//------------------------------------------------------------------------------
// Edit Form
//------------------------------------------------------------------------------
if (isset($_POST['editprayer']))
{
    $show = false;
    $id   = cleanInput($_POST['id'], 'int');
    $for  = cleanInput($_POST['for']);
    $desc = cleanInput($_POST['desc']);

    $prayers->displayForm('edit', $id, $for, $desc);
}

//------------------------------------------------------------------------------
// Show Prayers
//------------------------------------------------------------------------------
if ($show)
{
    if (checkAccess($currentUserId) <= 5)
    {
        echo '
            <div id="actions_menu" class="clearfix">
                <ul><li><a class="action" href="?addconcern=yes">'.T_('Add a Prayer Concern').'</a></li></ul>
            </div>';
    }

    $page = 1;
    if (isset($_GET['page']))
    {
        $page = cleanInput($_GET['page'], 'int');
    }

    $prayers->showPrayers($page);
}

displayFooter();

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
