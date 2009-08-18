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
include_once('inc/addressbook_class.php');
$book = new AddressBook($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
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
$pagetitle = $LANG['link_address'];
$d = "";
$admin_d = "admin/";
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
		<div id="profile" class="centercontent">
			<?php 
			$show = true;
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
				$sql = "INSERT INTO `fcms_users`(`access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`) VALUES (3, NOW(), '".addslashes($_POST['fname'])."', '".addslashes($_POST['lname'])."', '".addslashes($_POST['email'])."', 'NONMEMBER-$uniq', 'NONMEMBER')";
				mysql_query($sql) or displaySQLError('Non-member Creation Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error());
				$id = mysql_insert_id();
				$sql = "INSERT INTO `fcms_address`(`user`, `entered_by`, `updated`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell`) VALUES ($id, ".$_SESSION['login_id'].", NOW(), '".addslashes($_POST['address'])."', '".addslashes($_POST['city'])."', '".addslashes($_POST['state'])."', '".addslashes($_POST['zip'])."', '".addslashes($_POST['home'])."', '".addslashes($_POST['work'])."', '".addslashes($_POST['cell'])."')";
				mysql_query($sql) or displaySQLError('New Address Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error());
			}
			if (isset($_GET['del'])) {
				if ($_SESSION['login_id'] == $_GET['u'] || checkAccess($_SESSION['login_id']) < 2) {
					$aid = $_GET['del'];
					$sql = "DELETE FROM fcms_users WHERE id = $aid";
					mysql_query($sql) or displaySQLError('Non-member Deletion Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error());
					$sql = "DELETE FROM fcms_address WHERE id = $aid";
					mysql_query($sql) or displaySQLError('Delete Address Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error());
				} else {
					echo "<p class=\"error-alert\">".$LANG['err_permission']."</p>";
				}
			}
			if (isset($_GET['edit'])) {
				if ($_SESSION['login_id'] == $_GET['u'] || checkAccess($_SESSION['login_id']) < 2) {
					$book->displayForm('edit', $_GET['edit']);
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
					$book->displayAddress($_GET['address']);
					$show = false;
				}
			}
			if ($show) {
				$book->displayToolbar();
				if (isset($_GET['letter'])) {
					$sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email` FROM `fcms_users` AS u, `fcms_address` as a WHERE u.`id` = a.`user` AND `username` != 'SITENEWS' AND `password` != 'SITENEWS' AND `lname` LIKE '" . escape_string($_GET['letter']) . "%' ORDER BY `lname`";
				} else {
					$sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email` FROM `fcms_users` AS u, `fcms_address` as a WHERE u.`id` = a.`user` AND `username` != 'SITENEWS' AND `password` != 'SITENEWS' ORDER BY `lname`";
				}
				echo "\n\t\t\t<script src=\"inc/prototype.js\" type=\"text/javascript\"></script>\n\t\t\t<script type=\"text/javascript\" src=\"inc/tablesort.js\"></script>\n\t\t\t";
				echo "<form action=\"addressbook.php\" method=\"post\">\n\t\t\t";
				echo "<table class=\"sortable\">\n\t\t\t\t<thead><tr><th class=\"sortfirstasc\">".$LANG['name']."</th><th>".$LANG['phone_num']."</th><th>".$LANG['email']."</th><th class=\"nosort\"><a class=\"helpimg\" href=\"help.php#address-massemail\"></a></th></tr></thead>\n\t\t\t\t<tbody>\n";
				$result = mysql_query($sql) or displaySQLError('Get Addresses Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error());
				while($r = mysql_fetch_array($result)) {
					echo "\t\t\t\t\t<tr><td><a href=\"?address=".$r['id']."\">".$r['lname'].", ".$r['fname']."</a></td><td>".$r['home']."</td><td><a href=\"mailto:".htmlentities($r['email'], ENT_COMPAT, 'UTF-8')."\">".$r['email']."</a></td><td>";
					if (!empty($r['email'])) { echo "<input type=\"checkbox\" name=\"massemail[]\" value=\"".htmlentities($r['email'], ENT_COMPAT, 'UTF-8')."\"/>"; }
					echo "</td></tr>\n";
				}
				echo "\n\t\t\t\t</tbody>\n\t\t\t</table>\t\t\t\t\t<div class=\"alignright\"><input ";
				if (checkAccess($_SESSION['login_id']) > 3) { echo "disabled=\"disabled\" "; }
				echo "type=\"submit\" name=\"emailsubmit\" value=\"".$LANG['email']."\"/></div><p>&nbsp;</p>\n\t\t\t</form>\n";
			} echo "\n"; ?>
		</div><!-- #centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>