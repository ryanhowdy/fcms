<?php
session_start();
include_once('../inc/config_inc.php');
include_once('../inc/util_inc.php');
include_once('../inc/language.php');
if (isset($_SESSION['login_id'])) {
    if (!isLoggedIn($_SESSION['login_id'], $_SESSION['login_uname'], $_SESSION['login_pw'])) {
        displayLoginPage();
        exit();
    }
} elseif (isset($_COOKIE['fcms_login_id'])) {
    if (isLoggedIn($_COOKIE['fcms_login_id'], $_COOKIE['fcms_login_uname'], $_COOKIE['fcms_login_pw'])) {
        $_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
        $_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
        $_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
    } else {
        displayLoginPage();
        exit();
    }
} else {
    displayLoginPage();
    exit();
}
include_once('../inc/members_class.php');
include_once('../inc/database_class.php');
$database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$member = new Members($database);
header("Cache-control: private");
// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['admin_members'];
$TMPL['path'] = "../";
$TMPL['admin_path'] = "";
$TMPL['javascript'] = <<<HTML
<script type="text/javascript" src="{$TMPL['path']}inc/livevalidation.js"></script>
<script type="text/javascript" src="{$TMPL['path']}inc/tablesort.js"></script>
<link rel="stylesheet" type="text/css" href="{$TMPL['path']}themes/datechooser.css" />
<script type="text/javascript" src="{$TMPL['path']}inc/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[ 
events.add(window, 'load', initDateChooser);
function initDateChooser() {
    var objDatePicker = new DateChooser();
    objDatePicker.setUpdateField({'day':'j', 'month':'n', 'year':'Y'});
    objDatePicker.setIcon('{$TMPL['path']}themes/default/images/datepicker.jpg', 'year');
    var del = $('delete');
    if (del) { del.setAttribute('onclick', "return confirm('
HTML;
$TMPL['javascript'] .= $LANG['js_del_confirm'];
$TMPL['javascript'] .= <<<HTML
')"); }
    return true;
}
//]]>
</script>
HTML;
include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'header.php');
?>
    <div id="leftcolumn">
        <?php
        include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'sidenav.php');
        if (checkAccess($_SESSION['login_id']) < 3) {
            include_once(getTheme($_SESSION['login_id'], $TMPL['path']) . 'adminnav.php');
        }
        ?>
    </div>
    <div id="content">
        <div class="centercontent">
            <?php
            if (checkAccess($_SESSION['login_id']) < 2) {
                $show = true;
                
                // Display create new member form
                if (isset($_GET['create'])) {
                    $show = false;
                    $member->displayCreateMemberForm();
                
                // Diplay edit form
                } elseif (isset($_GET['edit'])) {
                    $show = false;
                    // Sanitize input, only numbers
                    if (preg_match('/^\d+$/', $_GET['edit'])) {
                        $member->displayEditMemberForm($_GET['edit']);
                    } else {
                        echo '<p class="error">' . $LANG['invalid_mem_id'] . '</p>';
                    }
                }
                
                // Create a new member
                if (isset($_POST['create'])) {
                    $sql = "SELECT `email` FROM `fcms_users` "
                         . "WHERE `email` = '" . $_POST['email'] . "'";
                    $result = mysql_query($sql) or displaySQLError(
                        'Email Check Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                        ); 
                    $email_check = mysql_num_rows($result);
                    if (!isset($_POST['username']) || 
                        !isset($_POST['password']) || 
                        !isset($_POST['fname']) || 
                        !isset($_POST['lname']) || 
                        !isset($_POST['email'])) {
                        $show = false;
                        $member->displayCreateMemberForm($LANG['err_required']);
                    } elseif ($email_check > 0) {
                        $show = false;
                        $member->displayCreateMemberForm(
                            $LANG['err_email_use1'] . ' '
                            . '<a href="../lostpw.php">' . $LANG['err_email_use2'] . '</a> '
                            . $LANG['err_email_use3']
                            );
                    } else {
                        $fname = escape_string($_POST['fname']);
                        $lname = escape_string($_POST['lname']);
                        $email = escape_string($_POST['email']);
                        $birthday = escape_string($_POST['year']) . "-"
                            . str_pad(escape_string($_POST['month']), 2, "0", STR_PAD_LEFT) . "-" 
                            . str_pad(escape_string($_POST['day']), 2, "0", STR_PAD_LEFT);
                        $username = escape_string($_POST['username']);
                        $password = escape_string($_POST['password']);
                        $md5pass = md5($password);
                        $sql = "INSERT INTO `fcms_users`("
                                . "`access`, `joindate`, `fname`, `lname`, `email`, `birthday`, "
                                . "`username`, `password`, `activated`) "
                             . "VALUES ("
                                . "3, NOW(), '$fname', '$lname', '$email', '$birthday', "
                                . "'$username', '$md5pass', 1)";
                        mysql_query($sql) or displaySQLError(
                            'New User Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
                        $lastid = mysql_insert_id();
                        $sql = "INSERT INTO `fcms_address`(`user`, `entered_by`,`updated`) "
                             . "VALUES ($lastid, " . $_SESSION['login_id'] . ", NOW())";
                        mysql_query($sql) or displaySQLError(
                            'New Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
                        $sql = "INSERT INTO `fcms_user_settings`(`user`) VALUES ($lastid)";
                        mysql_query($sql) or displaySQLError(
                            'New User Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
                        $sql = "INSERT INTO `fcms_calendar`(`date`, `title`, `created_by`, `type`) "
                             . "VALUES ('$birthday', '$fname $lname', {$_SESSION['login_id']}, 'Birthday')";
                        mysql_query($sql) or displaySQLError(
                            'New Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
                        echo "<p class=\"ok-alert\" id=\"update\">".$LANG['update_success']."</p>";
                        echo "<script type=\"text/javascript\">window.onload=function(){ ";
                        echo "var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
                    }
                
                // Edit Member
                } elseif (isset($_POST['edit'])) {
                    if (!isset($_POST['fname']) || 
                        !isset($_POST['lname']) || 
                        !isset($_POST['email'])) {
                        $show = false;
                        $member->displayEditMemberForm(
                            $_POST['id'],
                            "<p class=\"error\">" . $LANG['err_required'] . "</p>"
                            );
                    } else {
                        // Update user info
                        $emailstart = $member->getUsersEmail($_POST['id']);
                        $birthday = $_POST['year'] . "-" 
                            . str_pad($_POST['month'], 2, "0", STR_PAD_LEFT) . "-" 
                            . str_pad($_POST['day'], 2, "0", STR_PAD_LEFT);
                        $sql = "UPDATE `fcms_users` SET "
                            . "`fname` = '" . escape_string($_POST['fname']) . "', "
                            . "`lname` = '" . escape_string($_POST['lname']) . "', ";
                        if ($_POST['email']) { 
                            if ($_POST['email'] != $emailstart) {
                                $email_sql = "SELECT `email` FROM `fcms_users` "
                                     . "WHERE `email` = '" . $_POST['email'] . "'";
                                $result = mysql_query($email_sql) or displaySQLError(
                                    'Email Check Error', 
                                    __FILE__ . ' [' . __LINE__ . ']', 
                                    $email_sql, 
                                    mysql_error()
                                    );
                                $email_check = mysql_num_rows($result);
                                if ($email_check > 0) { 
                                    $member->displayEditMemberForm(
                                        $_POST['id'],
                                        "<p class=\"error-alert\">" . $LANG['err_email1'] . " ("
                                            . $_POST['email'] . ") ".$LANG['err_email2']."</p>"
                                        );
                                    exit();
                                }
                                $sql .= "email = '" . escape_string($_POST['email']) . "', ";
                            }
                        }
                        if ($_POST['password']) {
                            $sql .= "`password` = '" . md5($_POST['password']) . "', ";
                            $subject = getSiteName() . ': ' . $LANG['pw_change_email1'];
                            $message = $_POST['fname'] . " " . $_POST['lname'] . ", 

" . $LANG['pw_change_email2'] . " " . getSiteName() . " " . $LANG['pw_change_email3'] . "

" . $LANG['pw_change_email4'] . ": " . $_POST['password'];
                            mail($_POST['email'], $subject, $message, $email_headers);
                        }
                        $sql .= "`birthday` = '$birthday', "
                              . "`access` = " . $_POST['access'] . " "
                              . "WHERE id = " . $_POST['id'];
                        mysql_query($sql) or displaySQLError(
                            'Edit Member Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
                        echo "<p class=\"ok-alert\" id=\"update\">".$LANG['update_success']."</p>";
                        echo "<script type=\"text/javascript\">window.onload=function(){ ";
                        echo "var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
                    }
                
                // Activate Selected Members
                } elseif (isset($_POST['activateAll'])) {
                    foreach ($_POST['massupdate'] AS $id) {
                        $sql = "UPDATE `fcms_users` SET `activated` = 1 WHERE `id` = $id";
                        mysql_query($sql) or displaySQLError(
                            'Mass Activate Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
                    }
                    echo "<p class=\"ok-alert\" id=\"update\">".$LANG['update_success']."</p>";
                    echo "<script type=\"text/javascript\">window.onload=function(){ ";
                    echo "var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
                
                // Inactivate Selected Members
                } elseif (isset($_POST['inactivateAll'])) {
                    foreach ($_POST['massupdate'] AS $id) {
                        $sql = "UPDATE `fcms_users` SET `activated` = 0 WHERE `id` = $id";
                        mysql_query($sql) or displaySQLError(
                            'Mass Inactivate Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
                    }
                    echo "<p class=\"ok-alert\" id=\"update\">".$LANG['update_success']."</p>";
                    echo "<script type=\"text/javascript\">window.onload=function(){ ";
                    echo "var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
                
                // Delete Selected Members
                } elseif (isset($_POST['deleteAll'])) {
                    foreach ($_POST['massupdate'] AS $id) {
                        $sql = "DELETE FROM `fcms_users` WHERE `id` = $id";
                        mysql_query($sql) or displaySQLError(
                            'Mass Delete Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                            );
                    }
                    echo "<p class=\"ok-alert\" id=\"update\">".$LANG['update_success']."</p>";
                    echo "<script type=\"text/javascript\">window.onload=function(){ ";
                    echo "var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
                
                // Delete Member
                } elseif (isset($_POST['delete'])) {
                    $id = $_POST['id'];
                    $sql = "DELETE FROM fcms_users WHERE id = $id";
                    mysql_query($sql) or displaySQLError(
                        'Delete User Error', 
                        __FILE__ . ' [' . __LINE__ . ']', 
                        $sql, 
                        mysql_error()
                        );
                    echo "<p class=\"ok-alert\" id=\"update\">".$LANG['update_success']."</p>";
                    echo "<script type=\"text/javascript\">window.onload=function(){ ";
                    echo "var t=setTimeout(\"$('update').toggle()\",3000); }</script>";
                }
                
                // Show Member List
                if ($show) {
                    $page = 1;
                    if (isset($_GET['page'])) {
                        $page = $_GET['page'];
                    }
                    if (isset($_POST['search'])) {
                        $member->displayMemberList(
                            $page, 
                            $_POST['fname'], 
                            $_POST['lname'], 
                            $_POST['uname']
                            );
                    } else {
                        $member->displayMemberList($page);
                    }
                }
            } else {
                echo "<p class=\"error-alert\"><b>" . $LANG['err_no_access1'] . "</b><br/>";
                echo $LANG['err_no_access_member2'] . " <a href=\"../contact.php\">";
                echo $LANG['err_no_access3'] . "</a> " . $LANG['err_no_access4'] . "</a></p>";
            }
            ?>
            <p>&nbsp;</p>
            <p>&nbsp;</p>
        </div><!-- .centercontent -->
    </div><!-- #content -->
    <?php displayFooter("fix"); ?>
</body>
</html>
