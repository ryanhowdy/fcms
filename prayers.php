<?php
session_start();
if (get_magic_quotes_gpc()) {
    $_REQUEST = array_map('stripslashes', $_REQUEST);
    $_GET = array_map('stripslashes', $_GET);
    $_POST = array_map('stripslashes', $_POST);
    $_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');

// Check that the user is logged in
isLoggedIn();
$current_user_id = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");
include_once('inc/prayers_class.php');
$prayers = new Prayers($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL['pagetitle'] = T_('Prayer Concerns');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
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
include_once(getTheme($current_user_id) . 'header.php');

echo '
        <div id="prayers" class="centercontent">';
$show = true;

// Add prayer concern
if (isset($_POST['submitadd'])) {
    $for = escape_string($_POST['for']);
    $desc = escape_string($_POST['desc']);
    $sql = "INSERT INTO `fcms_prayers`(`for`, `desc`, `user`, `date`) "
         . "VALUES('$for', '$desc', $current_user_id, NOW())";
    mysql_query($sql) or displaySQLError(
        'New Prayer Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    echo '
            <p class="ok-alert" id="add">'.T_('Prayer Concern Added Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'add\').toggle()",3000); }
            </script>';
    // Email members
    $sql = "SELECT u.`email`, s.`user` "
         . "FROM `fcms_user_settings` AS s, `fcms_users` AS u "
         . "WHERE `email_updates` = '1'"
         . "AND u.`id` = s.`user`";
    $result = mysql_query($sql) or displaySQLError(
        'Email Updates Error', __FILE__ . ' [' . __LINE__ . ']', 
        $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        while ($r = mysql_fetch_array($result)) {
            $name = getUserDisplayName($current_user_id);
            $to = getUserDisplayName($r['user']);
            $subject = sprintf(T_('%s added a new Prayer Concern for %s'), $name, $for);
            $email = $r['email'];
            $url = getDomainAndDir();
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

// Edit prayer concern
if (isset($_POST['submitedit'])) {
    $for = escape_string($_POST['for']);
    $desc = escape_string($_POST['desc']);
    $sql = "UPDATE `fcms_prayers` SET `for` = '$for', `desc` = '$desc' WHERE `id` = " . escape_string($_POST['id']);
    mysql_query($sql) or displaySQLError('Edit Prayer Error', 'prayers.php [' . __LINE__ . ']', $sql, mysql_error());
    echo '
            <p class="ok-alert" id="edit">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'edit\').toggle()",3000); }
            </script>';
}

// Delete confirmation
if (isset($_POST['delprayer']) && !isset($_POST['confirmed'])) {
    $show = false;
    echo '
            <div class="info-alert clearfix">
                <form action="prayers.php" method="post">
                    <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                    <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                    <div>
                        <input type="hidden" name="id" value="'.$_POST['id'].'"/>
                        <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                        <a style="float:right;" href="prayers.php">'.T_('Cancel').'</a>
                    </div>
                </form>
            </div>';

// Delete prayer concern
} elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
    $sql = "DELETE FROM `fcms_prayers` WHERE `id` = " . escape_string($_POST['id']);
    mysql_query($sql) or displaySQLError('Delete Prayer Error', 'prayers.php [' . __LINE__ . ']', $sql, mysql_error());
    echo '
            <p class="ok-alert" id="del">'.T_('Prayer Concern Deleted Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'del\').toggle()",2000); }
            </script>';
}

// Add Form
if (isset($_GET['addconcern']) && checkAccess($current_user_id) <= 5) {
    $show = false;
    $prayers->displayForm('add');
}

// Edit Form
if (isset($_POST['editprayer'])) {
    $show = false;
    $prayers->displayForm('edit', $_POST['id'], $_POST['for'], $_POST['desc']);
}

// Show Prayers
if ($show) {
    if (checkAccess($current_user_id) <= 5) {
        echo '
            <div id="actions_menu" class="clearfix">
                <ul><li><a class="action" href="?addconcern=yes">'.T_('Add a Prayer Concern').'</a></li></ul>
            </div>';
    }
    $page = 1;
    if (isset($_GET['page'])) { $page = $_GET['page']; }
    $prayers->showPrayers($page);
}

echo '
        </div><!-- #prayers .centercontent -->';

// Show Footer
include_once(getTheme($current_user_id) . 'footer.php');
