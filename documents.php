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

include_once('inc/documents_class.php');
$docs = new Documents($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
header("Cache-control: private");
if (isset($_GET['download'])) {
    $show = false;
    $filename = "gallery/documents/" . basename($_GET['download']);
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: application/force-download");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment; filename=".basename($filename).";");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize($filename));
    @readfile($filename);
    exit(0);
}
// Setup the Template variables;
$TMPL['pagetitle'] = _('Documents');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";

// Show Header
include_once(getTheme($_SESSION['login_id']) . 'header.php');

echo '
        <div id="documents" class="centercontent">';
$show = true;
if (isset($_POST['submitadd'])) {
    $doc = $_FILES['doc']['name'];
    $doc = str_replace (" ", "_", $doc);
    $desc = addslashes($_POST['desc']);
    if ($docs->uploadDocument($_FILES['doc'], $doc)) {
        $sql = "INSERT INTO `fcms_documents`(`name`, `description`, `user`, `date`) VALUES('$doc', '$desc', " . $_SESSION['login_id'] . ", NOW())";
        mysql_query($sql) or displaySQLError('New Document Error', 'documents.php [' . __LINE__ . ']', $sql, mysql_error());
        echo '
            <p class="ok-alert" id="add">'._('Document Added Successfully').'</p>
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
                $subject = sprintf(_('%s has added a new document (%s).'), $name, $doc);
                $email = $r['email'];
                $url = getDomainAndDir();
                $msg = _('Dear').' '.$to.',

'.$subject.'

'.$url.'documents.php


----
'._('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
                mail($email, $subject, $msg, $email_headers);
            }
        }
    }
} 
if (isset($_POST['deldoc'])) {
    $sql = "DELETE FROM `fcms_documents` WHERE `id` = " . $_POST['id'];
    mysql_query($sql) or displaySQLError('Delete Document Error', 'documents.php [' . __LINE__ . ']', $sql, mysql_error());
    unlink("gallery/documents/" . $_POST['name']);
    echo '
            <p class="ok-alert" id="del">'._('Document Deleted Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'del\').toggle()",2000); }
            </script>';
}
if (isset($_GET['adddoc']) && checkAccess($_SESSION['login_id']) <= 5) {
    $show = false;
    $docs->displayForm();
}
if ($show) {
    if (checkAccess($_SESSION['login_id']) <= 5) {
        echo '
            <div id="actions_menu" class="clearfix">
                <ul><li><a href="?adddoc=yes">'._('Add Document').'</a></li></ul>
            </div>';
    }
    $page = 1;
    if (isset($_GET['page'])) { $page = $_GET['page']; }
    $docs->showDocuments($page);
}
echo '
        </div><!-- #documents .centercontent -->';

// Show Footer
include_once(getTheme($_SESSION['login_id']) . 'footer.php');
