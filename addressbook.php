<?php
session_start();
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	// a bug found with an array in $_POST
	if (!isset($_POST['emailsubmit']) && !isset($_POST['sendemailsubmit'])) {
		$_POST = array_map('stripslashes', $_POST);
	}
	$_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');
include_once('inc/alerts_class.php');

// Check that the user is logged in
isLoggedIn();

include_once('inc/addressbook_class.php');
$book = new AddressBook($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$alert = new Alerts($database);
header("Cache-control: private");
if (isset($_GET['csv'])) {
	$show = false;
	if ($_GET['csv'] == 'export') {
		$csv = "lname, fname, address, city, state, zip, home, work, cell\015\012";
		$result = mysql_query("SELECT `lname`, `fname`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell` FROM `fcms_address` AS a, `fcms_users` AS u WHERE a.`user` = u.`id` ORDER BY `lname`, `fname`");
		while ($row = mysql_fetch_assoc($result)) {
			$csv .= '"'.join('","', str_replace('"', '""', $row))."\"\015\012";
		}
		header("Content-type: text/plain");
		header("Content-disposition: csv; filename=FCMS_Addresses_".date("Y-m-d").".csv; size=".strlen($csv));
		echo $csv;
		exit();
	}
}

// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['link_address'];
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = '
<script type="text/javascript" src="inc/tablesort.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    if ($(\'del\')) {
        var item = $(\'del\');
        item.onclick = function() { return confirm(\''.$LANG['js_sure_del'].'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    }
    return true;
});
//]]>
</script>';
include_once(getTheme($_SESSION['login_id']) . 'header.php');
?>
    <div id="leftcolumn">
        <?php
        include_once(getTheme($_SESSION['login_id']) . 'sidenav.php');
        if (checkAccess($_SESSION['login_id']) < 3) {
            include_once(getTheme($_SESSION['login_id']) . 'adminnav.php');
        }
        ?>
    </div>
	<div id="content">
		<div id="addressbook" class="centercontent">
			<?php 
			$show = true;
            if (isset($_GET['csv'])) {
                if ($_GET['csv'] == 'import' && !isset($_POST['import'])) {
                    $show = false;
                    $book->displayImportForm();
                } elseif ($_GET['csv'] == 'import' && isset($_POST['import'])) {
                    $book->importAddressCsv($_FILES['csv']);
                }
            }

			if (isset($_POST['emailsubmit'])) {
				if (checkAccess($_SESSION['login_id']) > 3) {
					echo "<p class=\"error-alert\">".$LANG['err_access_3plus']."</p>";
				} else {
					if (empty($_POST['massemail'])) {
						echo "<p class=\"error-alert\">".$LANG['err_massemail_1']."  <a href=\"help.php#address-massemail\">".$LANG['err_massemail_2']."</a> ".$LANG['err_massemail_3']."</p>";
					} else {
						$book->displayMassEmailForm($_POST['massemail']);
						$show = false;
					}
				}
			}
			if (isset($_POST['sendemailsubmit']) && !empty($_POST['subject']) && !empty($_POST['email']) && !empty($_POST['name']) && !empty($_POST['msg'])) {
				$subject = stripslashes($_POST['subject']);
				$email = stripslashes($_POST['email']);
				$name = stripslashes($_POST['name']);
				$msg = stripslashes($_POST['msg']);
				foreach ($_POST['emailaddress'] as $email) {
					mail($email, $subject, "$msg\r\n-$name", $email_headers);
				}
				echo "<p class=\"ok-alert\" id=\"msg\">".$LANG['msg_sent']."<br/>";
				echo "$msg</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('msg').toggle()\",4000); }</script>";
			} elseif (isset($_POST['sendemailsubmit'])) {
				$book->displayMassEmailForm($_POST['emailaddress'], $_POST['email'], $_POST['name'], $_POST['subject'], $_POST['msg'], 'Yes');
				$show = false;
			}
			if (isset($_POST['editsubmit'])) {
				$sql = "UPDATE `fcms_address` SET updated=NOW(), address='".addslashes($_POST['address'])."', city='".addslashes($_POST['city'])."', state='".addslashes($_POST['state'])."', zip='".addslashes($_POST['zip'])."', home='".addslashes($_POST['home'])."', `work`='".addslashes($_POST['work'])."', cell='".addslashes($_POST['cell'])."' WHERE id=".$_POST['aid'];
				mysql_query($sql) or displaySQLError('Edit Address Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error());
				$sql = "UPDATE `fcms_users` SET `email`='".addslashes($_POST['email'])."' WHERE id=".$_POST['uid'];
				mysql_query($sql) or displaySQLError('Edit Email Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error());
			}
			if (isset($_POST['addsubmit'])) {
				$uniq = uniqid("");
				$sql = "INSERT INTO `fcms_users` ("
                        . "`access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`"
                     . ") VALUES ("
                        . "3, "
                        . "NOW(), "
                        . "'" . addslashes($_POST['fname']) . "', "
                        . "'" . addslashes($_POST['lname']) . "', "
                        . "'" . addslashes($_POST['email']) . "', "
                        . "'NONMEMBER-$uniq', "
                        . "'NONMEMBER')";
				mysql_query($sql) or displaySQLError(
                    'Add Non-Member Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
				$id = mysql_insert_id();
				$sql = "INSERT INTO `fcms_address`("
                        . "`user`, `entered_by`, `updated`, `address`, `city`, `state`, "
                        . "`zip`, `home`, `work`, `cell`"
                     . ") VALUES ("
                        . "$id, "
                        . $_SESSION['login_id'] . ", "
                        . "NOW(), "
                        . "'" . addslashes($_POST['address']) . "', "
                        . "'" . addslashes($_POST['city']) . "', "
                        . "'" . addslashes($_POST['state']) . "', "
                        . "'" . addslashes($_POST['zip']) . "', "
                        . "'" . addslashes($_POST['home']) . "', "
                        . "'" . addslashes($_POST['work']) . "', "
                        . "'" . addslashes($_POST['cell']) . "')";
				mysql_query($sql) or displaySQLError(
                    'New Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
			}

            // Delete address confirmation
            if (isset($_POST['del']) && !isset($_POST['confirmed'])) {
                $show = false;
                echo '
                <div class="info-alert clearfix">
                    <form action="addressbook.php" method="post">
                        <h2>'.$LANG['js_del_confirm'].'</h2>
                        <p><b><i>'.$LANG['cannot_be_undone'].'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.$_POST['id'].'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.$LANG['yes'].'"/>
                            <a style="float:right;" href="addressbook.php?address='.$_POST['id'].'">'.$LANG['cancel'].'</a>
                        </div>
                    </form>
                </div>';

            // Delete Addresss
            } elseif (isset($_POST['delconfirm']) || (isset($_POST['confirmed']) && isset($_POST['del']))) {
				if (checkAccess($_SESSION['login_id']) < 2) {
					$aid = $_POST['id'];
					$sql = "DELETE FROM fcms_users WHERE id = $aid";
					mysql_query($sql) or displaySQLError('Non-member Deletion Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error());
					$sql = "DELETE FROM fcms_address WHERE id = $aid";
					mysql_query($sql) or displaySQLError('Delete Address Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error());
				} else {
					echo "<p class=\"error-alert\">".$LANG['err_permission']."</p>";
				}
			}

            // Edit Address
			if (isset($_POST['edit'])) {
				if (checkAccess($_SESSION['login_id']) < 2 || $_SESSION['login_id'] == $_POST['id']) {
					$book->displayForm('edit', $_POST['id']);
				} else {
					echo "<p class=\"error-alert\">".$LANG['err_permission']."</p>";
				}
				$show = false;
			}
			if (isset($_GET['add'])) {
				if (checkAccess($_SESSION['login_id']) <= 5) {
					$book->displayForm('add');
					$show = false;
				}
			}
			if (isset($_GET['address'])) {
				// Santizing user input - address - only allow digits 0-9
				if (preg_match('/^\d+$/', $_GET['address'])) {
					$book->displayToolbar();
                    $page = isset($_GET['page']) ? $_GET['page'] : 1;
					$book->displayAddress($_GET['address'], $page);
					$show = false;
				}
			}
			if ($show) {
                $page = isset($_GET['page']) ? $_GET['page'] : 1;
                // Remove an alert
                if (isset($_GET['alert'])) {
                    $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
                            VALUES (
                                '".escape_string($_GET['alert'])."', 
                                ".$_SESSION['login_id']."
                            )";
                    mysql_query($sql) or displaySQLError(
                        'Remove Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                }
                if (!$book->userHasAddress($_SESSION['login_id'])) {
                    // Show Alerts
                    $alert->displayAddress($_SESSION['login_id'], $page);
                }
				$book->displayToolbar();
				if (isset($_GET['letter'])) {
                    $book->displayAddressList($_GET['letter'], $page);
				} else {
                    $book->displayAddressList('', $page);
				}
			} ?>

        </div><!-- #centercontent -->
    </div><!-- #content -->
    <?php displayFooter(); ?>
</body>
</html>
