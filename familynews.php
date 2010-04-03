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

header("Cache-control: private");
include_once('inc/familynews_class.php');
$fnews = new FamilyNews($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

// Setup the Template variables;
$TMPL['pagetitle'] = _('Family News');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    if (!$$(\'.delnews input[type="submit"]\')) { return; }
    $$(\'.delnews input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''._('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    });
    if (!$$(\'.delcom input[type="submit"]\')) { return; }
    $$(\'.delcom input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''._('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'comconfirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    });
    if ($(\'toolbar\')) {
        $(\'toolbar\').removeClassName("hideme");
    }
    if ($(\'smileys\')) {
        $(\'smileys\').removeClassName("hideme");
    }
    if ($(\'upimages\')) {
        $(\'upimages\').removeClassName("hideme");
    }
    return true;
});
//]]>
</script>';

// Show Header
include_once(getTheme($_SESSION['login_id']) . 'header.php');

echo '
        <div id="familynews" class="centercontent clearfix">';
if (checkAccess($_SESSION['login_id']) < 6 || checkAccess($_SESSION['login_id']) == 9) {
    echo '
            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a href="familynews.php">'._('Latest News').'</a></li>';
    if ($fnews->hasNews($_SESSION['login_id'])) {
        echo '
                    <li><a href="?getnews='.$_SESSION['login_id'].'">'._('My News').'</a></li>';
    }
    echo '
                </ul>
            </div>
            <div id="actions_menu" class="clearfix">
                <ul>
                    <li><a href="?addnews=yes">'._('Add News').'</a></li>
                </ul>
            </div>';
}
echo '
            <br/>';
if (!isset($_GET['addnews']) && !isset($_POST['editnews'])) {
    $fnews->displayNewsList();
}
$show_last5 = true;

// Add news
if (isset($_POST['submitadd'])) {
    $title = addslashes($_POST['title']);
    $news = addslashes($_POST['post']);
    $sql = "INSERT INTO `fcms_news`(`title`, `news`, `user`, `date`) VALUES('$title', '$news', " . $_SESSION['login_id'] . ", NOW())";
    mysql_query($sql) or displaySQLError('Add News Error', 'familynews.php [' . __LINE__ . ']', $sql, mysql_error());
    echo '
            <p class="ok-alert" id="add">'._('Family News Added Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'add\').toggle()",3000); }
            </script>';
    // Email members
    $sql = "SELECT u.`email`, s.`user` "
         . "FROM `fcms_user_settings` AS s, `fcms_users` AS u "
         . "WHERE `email_updates` = '1'"
         . "AND u.`id` = s.`user`";
    $result = mysql_query($sql) or displaySQLError('Email Updates Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
    if (mysql_num_rows($result) > 0) {
        while ($r = mysql_fetch_array($result)) {
            $name = getUserDisplayName($_SESSION['login_id']);
            $to = getUserDisplayName($r['user']);
            $subject = sprintf(_('%s has added %s to his/her Family News'),$name, $title);
            $email = $r['email'];
            $url = getDomainAndDir();
            $msg = _('Dear').' '.$to.',

'.$subject.'

'.$url.'familynews.php?getnews='.$_SESSION['login_id'].'

----
'._('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
            mail($email, $subject, $msg, $email_headers);
        }
    }

// Edit news
} elseif (isset($_POST['submitedit'])) {
    $show_last5 = false;
    $title = addslashes($_POST['title']);
    $news = addslashes($_POST['post']);
    $sql = "UPDATE `fcms_news` SET `title` = '$title', `news` = '$news' WHERE `id` = ".$_POST['id'];
    mysql_query($sql) or displaySQLError('Edit News Error', 'familynews.php [' . __LINE__ . ']', $sql, mysql_error());
    // TODO
    // Remove this page refresh and make the standard js message
    // this would include sending the GET['getnews'] and GET['newsid'] variables to the edit page
    // so they can be sent to back to that page on completion
    echo '
            <p class="ok-alert">
                '._('Changes Updated Successfully').'<br/>
                <a href="familynews.php?getnews='.$_POST['user'].'">'._('View Changes').'</a>
            </p>
            <meta http-equiv=\'refresh\' content=\'0;URL=familynews.php?getnews='.$_POST['user'].'\'>';
}

// Add news form
if (isset($_GET['addnews']) && (checkAccess($_SESSION['login_id']) < 6 || checkAccess($_SESSION['login_id']) == 9)) { 
    $show_last5 = false;
    $fnews->displayForm('add', $_SESSION['login_id']);

// Edit news form
} else if (isset($_POST['editnews'])) {
    $show_last5 = false;
    $fnews->displayForm('edit', $_POST['user'], $_POST['id'], $_POST['title'], $_POST['news']);

// Delete news confirmation
} else if (isset($_POST['delnews']) && !isset($_POST['confirmed'])) {
    $show_last5 = false;
    echo '
                <div class="info-alert clearfix">
                    <form action="familynews.php?getnews='.$_POST['user'].'" method="post">
                        <h2>'._('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'._('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="user" value="'.$_POST['user'].'"/>
                            <input type="hidden" name="id" value="'.$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'._('Yes').'"/>
                            <a style="float:right;" href="familynews.php?getnews='.$_POST['user'].'">'._('Cancel').'</a>
                        </div>
                    </form>
                </div>';

// Delete news
} elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
    $show_last5 = false;
    $sql = "DELETE FROM `fcms_news` WHERE id = ".$_POST['id'];
    mysql_query($sql) or displaySQLError('Delete News Error', 'familynews.php [' . __LINE__ . ']', $sql, mysql_error());
    echo '
            <p class="ok-alert" id="del">'._('Family News Deleted Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'del\').toggle()",3000); }
            </script>';
}

// Show news
if (isset($_GET['getnews'])) {
    $show_last5 = false;
    $page = 1; $nid = 0;
    if (isset($_GET['page'])) { 
        // Santizing user input - newspage - only allow digits 0-9
        if (preg_match('/^\d+$/', $_GET['page'])) { $page = $_GET['page']; }
    }
    if (isset($_GET['newsid'])) {
        // Santizing user input - newsid - only allow digits 0-9
        if (preg_match('/^\d+$/', $_GET['newsid'])) { $nid = $_GET['newsid']; }
    }
    if (isset($_POST['addcom'])) {
        $com = ltrim($_POST['comment']);
        if (!empty($com)) {
            $sql = "INSERT INTO `fcms_news_comments`(`news`, `comment`, `date`, `user`) VALUES($nid, '" . addslashes($com) . "', NOW(), " . $_SESSION['login_id'] . ")";
            mysql_query($sql) or displaySQLError('New Comment Error', 'familynews.php [' . __LINE__ . ']', $sql, mysql_error());
        }
    }

    // Delete comment confirmation
    if (isset($_POST['delcom']) && !isset($_POST['comconfirmed'])) {
        $show_last5 = false;
        echo '
                <div class="info-alert clearfix">
                    <form action="familynews.php?getnews='.$_GET['getnews'].'" method="post">
                        <h2>'._('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'._('This can NOT be undone').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delcomconfirm" name="delcomconfirm" value="'._('Yes').'"/>
                            <a style="float:right;" href="familynews.php?getnews='.$_GET['getnews'].'">'._('Cancel').'</a>
                        </div>
                    </form>
                </div>';

    // Delete news
    } elseif (isset($_POST['delcomconfirm']) || isset($_POST['comconfirmed'])) {
        $sql = "DELETE FROM fcms_news_comments WHERE id = " . $_POST['id'];
        mysql_query($sql) or displaySQLError('Delete Comment Error', 'familynews.php [' . __LINE__ . ']', $sql, mysql_error());
    }
    // Santizing user input - getnews - only allow digits 0-9
    if (preg_match('/^\d+$/', $_GET['getnews'])) {
        $fnews->showFamilyNews($_GET['getnews'], $nid, $page);
    }
}

// Show last 5 news
if ($show_last5) {
    $fnews->displayLast5News();
}

echo '
        </div><!-- #familynews .centercontent -->';

// Show Footer
include_once(getTheme($_SESSION['login_id']) . 'footer.php');
