<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class AddressBook {

	var $db;
	var $cur_user_id;
	var $tz_offset;

	function AddressBook ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
		$sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id";
		$this->db->query($sql) or displaySQLError('Timezone Error', 'inc/addressbook_class.php [' . __LINE__ . ']', $sql, mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function displayToolbar () {
		global $LANG;
		if (checkAccess($_SESSION['login_id']) <= 5) {
			echo <<<HTML
            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a class="add_address" href="?add=yes">{$LANG['add_address']}</a></li>
                    <li><a class="export" href="?csv=export" onclick="javascript:return confirm('{$LANG['js_sure_export']}');">{$LANG['export']}</a></li>
                </ul>
            </div>

HTML;
		}
		echo '<div id="addresstoolbar" class="clearfix"><ul>';
		$sql = "SELECT `lname` FROM `fcms_users` AS u, `fcms_address` as a WHERE u.`id` = a.`user` AND `username` != 'SITENEWS' AND `password` != 'SITENEWS' ORDER BY `lname`";
		$this->db->query($sql) or displaySQLError('Address Letter Error', 'inc/addressbook_class.php [' . __LINE__ . ']', $sql, mysql_error());
		$prev_letter = -1;
        $letters = array();
		while($r = $this->db->get_row()) {
			$letter = strtoupper(substr($r['lname'], 0, 1));
			if ($letter != $prev_letter) { $letters[] = $letter;  }
			$prev_letter = $letter;
		}
        foreach (range('A', 'Z') as $letter) {
            if (in_array($letter, $letters)) {
                echo '<li><a href="?letter=' . $letter . '">' . $letter . '</a></li>';
            } else {
                echo '<li><span>' . $letter . '</span></li>';
            }
        }
		echo "</ul></div>\n";
		echo '<p><a class="u" href="addressbook.php">' . $LANG['show_all'] . '</a></p>';
	}

	function displayAddress ($aid) {
		global $LANG;
		$sql = "SELECT a.`id`, a.`user`, `fname`, `lname`, `avatar`, `updated`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell`, `email`, `birthday`, `password` FROM `fcms_address` AS a, `fcms_users` AS u WHERE a.`user` = u.`id` AND a.`id` = $aid";
		$this->db->query($sql) or displaySQLError('Get Address Error', 'inc/addressbook_class.php [' . __LINE__ . ']', $sql, mysql_error());
		if ($this->db->count_rows() > 0) {
			while($r = $this->db->get_row()) {
				echo "\t\t\t<div class=\"address\">\n\t\t\t\t<p><img src=\"gallery/avatar/" . $r['avatar'] . "\"/><b>" . $r['lname'] . ", " . $r['fname'] . "</b></p>\n";
				if ($this->cur_user_id == $r['user'] || checkAccess($this->cur_user_id) < 2) {
					echo "\t\t\t\t<p class=\"alignright\">";
					if ($r['password'] == 'NONMEMBER') { echo "<a class=\"del_address\" href=\"?del=" . $r['id'] . "&amp;u=".$r['user']."\" onclick=\"javascript:return confirm('".$LANG['js_sure_del']."');\">".$LANG['delete']."</a>&nbsp;&nbsp;"; }
					echo "<a class=\"edit_address\" href=\"?edit=" . $r['id'] . "&amp;u=".$r['user']."\">".$LANG['edit']."</a></p>\n";
				}
				echo "\t\t\t\t<p class=\"clearfix\"><b class=\"label\">".$LANG['address']."</b><span class=\"data\">";
				if (empty($r['address']) or $r['address'] == 'NULL') {
					echo "<i>(".$LANG['none'].")</i>";
				} else {
					echo $r['address'] . "<br/>" . $r['city'] . ", " . $r['state'] . " " . $r['zip'];
				}
				echo "</span></p>\n\t\t\t\t<p class=\"clearfix\"><b class=\"label\">".$LANG['address_email']."</b><span class=\"data\">";
				if (empty($r['email']) or $r['email'] == 'NULL') {
					echo "<i>(".$LANG['none'].")</i>";
				} else {
					echo $r['email'] . " <a class=\"email\" href=\"mailto:" . $r['email'] . "\" title=\"Email This Member\">&nbsp;</a>";
				}
				echo "</span></p>\n\t\t\t\t<p class=\"clearfix\"><b class=\"label\">".$LANG['address_home']."</b><span class=\"data\">";
				if (empty($r['home']) or $r['home'] == 'NULL') {
					echo "<i>(".$LANG['none'].")</i>";
				} else {
					echo $r['home'];
				}
				echo "</span></p>\n\t\t\t\t<p class=\"clearfix\"><b class=\"label\">".$LANG['address_work']."</b><span class=\"data\">";
				if (empty($r['work']) or $r['work'] == 'NULL') {
					echo "<i>(".$LANG['none'].")</i>";
				} else {
					echo $r['work'];
				}
				echo "</span></p>\n\t\t\t\t<p class=\"clearfix\"><b class=\"label\">".$LANG['address_mobile']."</b><span class=\"data\">";
				if (empty($r['cell']) or $r['cell'] == 'NULL') {
					echo "<i>(".$LANG['none'].")</i>";
				} else {
					echo $r['cell'];
				}
				echo "</span></p>\n\t\t\t</div>\n";
			}
		}
	}

	function displayForm ($type, $addressid = '0')
    {
		global $LANG;
		if($type == 'edit') {
			$sql = "SELECT a.`id`, u.`id` AS uid, `fname`, `lname`, `email`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell` "
                 . "FROM `fcms_users` AS u, `fcms_address` AS a "
                 . "WHERE a.`id` = $addressid "
                 . "AND a.`user` = u.`id`";
			$this->db->query($sql) or displaySQLError('Get Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
			$row=$this->db->get_row();
		}

        // Setup vars for output
		if($type == 'edit') {
            $note = '';
			$legend = $LANG['edit_address'] . " (" . stripslashes($row['fname']) . " " . stripslashes($row['lname']) . ")";
            $add = '';
            $email = htmlentities($row['email'], ENT_COMPAT, 'UTF-8');
            $address = htmlentities($row['address'], ENT_COMPAT, 'UTF-8');
            $city = htmlentities($row['city'], ENT_COMPAT, 'UTF-8');
            $state = htmlentities($row['state'], ENT_COMPAT, 'UTF-8');
            $zip = htmlentities($row['zip'], ENT_COMPAT, 'UTF-8');
            $home = htmlentities($row['home'], ENT_COMPAT, 'UTF-8');
            $work = htmlentities($row['work'], ENT_COMPAT, 'UTF-8');
            $cell = htmlentities($row['cell'], ENT_COMPAT, 'UTF-8');
		} else {
            $note = '<p class="info-alert">' . $LANG['info_add_address'] . '</p>';
            $legend = $LANG['add_address'];
			$add = <<<HTML
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="fname"><b>{$LANG['first_name']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="fname" id="fname" title="{$LANG['title_fname']}" size="25"/></div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation('fname', {validMessage: ""});
                        ffname.add(Validate.Presence, {failureMessage: ""});
                    </script>
			        <div class="field-row clearfix">
                        <div class="field-label"><label for="lname"><b>{$LANG['last_name']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="lname" id="lname" title="{$LANG['title_lname']}" size="25"/></div>
                    </div>
                    <script type="text/javascript">
                        var flname = new LiveValidation('lname', { validMessage: ""});
                        flname.add(Validate.Presence, {failureMessage: ""});
                    </script>

HTML;
            $email = $address = $city = $state = $zip = $home = $work = $cell = '';
		}

        // Print the form
		echo <<<HTML
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <form id="addressbook_form" action="addressbook.php" method="post">
                {$note}
                <fieldset>
                    <legend>{$legend}</legend>
{$add}
		            <div class="field-row clearfix">
                        <div class="field-label"><label for="email"><b>{$LANG['email']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="email" id="email" title="{$LANG['title_email']}" size="50" value="{$email}"/></div>
                    </div>
		            <script type="text/javascript">
                        var femail = new LiveValidation('email', { validMessage: "", wait: 500});
                        femail.add( Validate.Email, { failureMessage: "{$LANG['lv_bad_email']}"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="address"><b>{$LANG['street']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="address" id="address" title="{$LANG['title_street']}" size="25" value="{$address}"/></div>
                    </div>
		            <div class="field-row clearfix">
                        <div class="field-label"><label for="city"><b>{$LANG['city_town']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="city" id="city" title="{$LANG['title_city_town']}" size="50" value="{$city}"/></div>
		            </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="state"><b>{$LANG['state_prov']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="state" id="state" title="{$LANG['title_state_prov']}" size="50" value="{$state}"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="zip"><b>{$LANG['zip_pos']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="zip" id="zip" title="{$LANG['title_zip_pos']}" size="10" value="{$zip}"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="home"><b>{$LANG['home_phone']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="home" id="home" title="{$LANG['title_phone']}" size="20" value="{$home}"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="work"><b>{$LANG['work_phone']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="work" id="work" title="{$LANG['title_phone']}" size="20" value="{$work}"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="cell"><b>{$LANG['mobile_phone']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="cell" id="cell" title="{$LANG['title_phone']}" size="20" value="{$cell}"/></div>
                    </div>
                </fieldset>

HTML;
		if($type == 'edit') {
			echo "\t\t\t<div><input type=\"hidden\" name=\"aid\" value=\"$addressid\"/></div>\n";
			echo "\t\t\t<div><input type=\"hidden\" name=\"uid\" value=\"".$row['uid']."\"/></div>\n";
			echo "\t\t\t<p><input type=\"submit\" name=\"editsubmit\" value=\"".$LANG['edit_address']."\"/> " . $LANG['or'] . " <a href=\"addressbook.php\">" . $LANG['cancel'] . "</a></p></form>\n";
		} else {
			echo "\t\t\t<p><input type=\"submit\" name=\"addsubmit\" value=\"".$LANG['add_address']."\"/> " . $LANG['or'] . " <a href=\"addressbook.php\">" . $LANG['cancel'] . "</a></p></form>\n";
		}
	}

	function displayMassEmailForm ($emails, $email = '', $name = '', $subject = '', $message = '', $show = '') {
		global $LANG;
		echo "<p class=\"info-alert\">".$LANG['info_massemail']."</p>\n\t\t\t";
		echo "<form method=\"post\" class=\"contactform\" action=\"addressbook.php\">\n\t\t\t\t";
		echo "<p><label for=\"email\">".$LANG['your_email'].":";
		if (!empty($show) && empty($email)) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
		echo "</label><input class=\"frm_text\" type=\"text\" id=\"email\" name=\"email\" size=\"30\"/></p>\n\t\t\t\t";
		echo "<p><label for=\"name\">".$LANG['your_name'].":";
		if (!empty($show) && empty($name)) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
		echo "</label><input class=\"frm_text\" type=\"text\" id=\"name\" name=\"name\" size=\"30\"/></p>\n\t\t\t\t";
		echo "<p><label for=\"subject\">".$LANG['subject'].":";
		if (!empty($show) && empty($subject)) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
		echo "</label><input class=\"frm_text\" type=\"text\" id=\"subject\" name=\"subject\" size=\"30\"/></p>\n\t\t\t\t";
		echo "<p><label for=\"subject\">".$LANG['message'].":";
		if (!empty($show) && empty($msg)) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
		echo "</label><textarea name=\"msg\" rows=\"10\" cols=\"40\"></textarea></p>\n\t\t\t\t";
		foreach ($emails as $email) {
			echo "<input type=\"hidden\" name=\"emailaddress[]\" value=\"$email\"/>\n\t\t\t";
		}
		echo "<p><input type=\"submit\" name=\"sendemailsubmit\" value=\"".$LANG['send_mass_email']."\"/>";
		echo " " . $LANG['or'] . " <a href=\"addressbook.php\">" . $LANG['cancel'] . "</a></p>\n\t\t\t</form><p>&nbsp;</p><p>&nbsp;</p>";
	}

	function displayWhatsNewAddressBook ()
    {
		global $LANG;
		$today = date('Y-m-d');
		$tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		echo "\t\t\t\t<h3>".$LANG['link_address']."</h3>\n\t\t\t\t<ul>\n";
		$sql = "SELECT a.id, u.id AS user, lname, fname, username, updated "
             . "FROM fcms_users AS u, fcms_address AS a "
             . "WHERE u.id = a.user "
             . "AND updated >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) "
             . "ORDER BY updated DESC "
             . "LIMIT 0, 5";
		$this->db->query($sql) or displaySQLError(
            'What\'s New Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
		if ($this->db->count_rows() > 0) {
			while($row = $this->db->get_row()) {
				$displayname = getUserDisplayName($row['user'], 2, false);
				$monthName = gmdate('M', strtotime($row['updated'] . $this->tz_offset));
				$date = gmdate('. j, Y, g:i a', strtotime($row['updated'] . $this->tz_offset));
				if (
                    strtotime($row['updated']) >= strtotime($today) && 
                    strtotime($row['updated']) > $tomorrow
                ) {
                    $full_date = $LANG['today'];
                    $d = ' class="today"';
                } else {
                    $full_date = getLangMonthName($monthName) . $date;
                    $d = '';
                }
                echo "\t\t\t\t\t<li><div$d>$full_date</div>";
				echo "<a href=\"addressbook.php?address=" . $row['id'] . "\">$displayname</a>";
                echo "</li>\n";			
			}
		} else {
			echo "\t\t\t\t\t<li><i>".$LANG['nothing_new_30']."</i></li>\n";
		}
		echo "\t\t\t\t</ul>\n";
	}

}
?>
