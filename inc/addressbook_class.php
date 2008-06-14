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
		$this->db->query("SELECT timezone FROM fcms_users WHERE id = $current_user_id") or die('<h1>Timezone Error (addressbook.class.php 15)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function displayToolbar () {
		global $LANG;
		if (checkAccess($_SESSION['login_id']) <= 5) {
			echo "<p><a class=\"add_address\" href=\"?add=yes\">".$LANG['add_address']."</a> | <small><a href=\"?csv=export\" onclick=\"javascript:return confirm('".$LANG['js_sure_export']."');\">".$LANG['export']."</a></small></p>\n\t\t\t";
		}
		echo "<p class=\"center addresstoolbar\"><a class=\"u\" href=\"addressbook.php\">".$LANG['show_all']."</a> ";
		$this->db->query("SELECT `lname` FROM `fcms_users` AS u, `fcms_address` as a WHERE u.`id` = a.`user` AND `username` != 'SITENEWS' AND `password` != 'SITENEWS' ORDER BY `lname`") or die("<h1>Address Letter Error (addressbook.class.php 21)</h1>" . mysql_error());
		$prev_letter = -1;
		while($r = $this->db->get_row()) {
			$letter = strtoupper(substr($r['lname'], 0, 1));
			if ($letter != $prev_letter) { echo "<a href=\"?letter=$letter\">$letter</a> "; }
			$prev_letter = $letter;
		}
		echo "</p>";
	}

	function displayAddress ($aid) {
		global $LANG;
		$this->db->query("SELECT a.`id`, a.`user`, `fname`, `lname`, `avatar`, `updated`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell`, `email`, `birthday`, `password` FROM `fcms_address` AS a, `fcms_users` AS u WHERE a.`user` = u.`id` AND a.`id` = " . $aid) or die('<h1>Get Addresses Error (addressbook.class.php 29)</h1>' . mysql_error());
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

	function displayForm ($type, $addressid = '0') {
		global $LANG;
		if($type == 'edit') {
			$this->db->query("SELECT a.`id`, u.`id` AS uid, `fname`, `lname`, `email`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell` FROM `fcms_users` AS u, `fcms_address` AS a WHERE a.`id` = $addressid AND a.`user` = u.`id`") or die('<h1>Address Error (addressbook.class.php 77)</h1>' . mysql_error());
			$row=$this->db->get_row();
		}
		echo "<script src=\"inc/prototype.js\" type=\"text/javascript\"></script>\n\t\t\t<script type=\"text/javascript\" src=\"inc/livevalidation.js\"></script>\n";
		echo "\t\t\t<form id=\"addressbook_form\" action=\"addressbook.php\" method=\"post\">\n";
		if($type == 'edit') {
			echo "\t\t\t<fieldset><legend>".$LANG['edit_address']." (".stripslashes($row['fname'])." ".stripslashes($row['lname']).")</legend>\n";
		} else { 
			echo "\t\t\t<p class=\"info-alert\">".$LANG['info_add_address']."</p>\n\t\t\t<fieldset><legend>".$LANG['add_address']."</legend>\n";
		}
		if($type == 'add') {
			echo "\t\t\t<div class=\"field-row\"><div class=\"field-label\"><label for=\"fname\">".$LANG['first_name']."</label>:</div> <div class=\"field-widget\"><input type=\"text\" name=\"fname\" id=\"fname\" class=\"required\" title=\"".$LANG['title_fname']."\" size=\"25\"/></div></div>\n";
			echo "\t\t\t<script type=\"text/javascript\">\n\t\t\t\tvar ffname = new LiveValidation('fname', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\tffname.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t</script>\n";
			echo "\t\t\t<div class=\"field-row\"><div class=\"field-label\"><label for=\"lname\">".$LANG['last_name']."</label>:</div> <div class=\"field-widget\"><input type=\"text\" name=\"lname\" id=\"lname\" class=\"required\" title=\"".$LANG['title_lname']."\" size=\"25\"/></div></div>\n";
			echo "\t\t\t<script type=\"text/javascript\">\n\t\t\t\tvar flname = new LiveValidation('lname', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\tflname.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t</script>\n";
		}
		echo "\t\t\t<div class=\"field-row\"><div class=\"field-label\"><label for=\"email\">".$LANG['email']."</label>:</div> <div class=\"field-widget\"><input type=\"text\" name=\"email\" id=\"email\" class=\"validate-email\" title=\"".$LANG['title_email']."\" size=\"50\" value=\"".htmlentities(stripslashes($row['email']))."\"/></div></div>\n";
		echo "\t\t\t<script type=\"text/javascript\">\n\t\t\t\tvar femail = new LiveValidation('email', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\tfemail.add( Validate.Email, { failureMessage: \"".$LANG['lv_bad_email']."\" } );\n\t\t\t</script>\n";
		echo "\t\t\t<div class=\"field-row\"><div class=\"field-label\"><label for=\"address\">".$LANG['street']."</label>:</div> <div class=\"field-widget\"><input type=\"text\" name=\"address\" id=\"address\" title=\"".$LANG['title_street']."\" size=\"25\" value=\"".htmlentities(stripslashes($row['address']))."\"/></div></div>\n";
		echo "\t\t\t<div class=\"field-row\"><div class=\"field-label\"><label for=\"city\">".$LANG['city_town']."</label>:</div> <div class=\"field-widget\"><input type=\"text\" name=\"city\" id=\"city\" title=\"".$LANG['title_city_town']."\" size=\"50\" value=\"".htmlentities(stripslashes($row['city']))."\"/></div></div>\n";
		echo "\t\t\t<div class=\"field-row\"><div class=\"field-label\"><label for=\"state\">".$LANG['state_prov']."</label>:</div> <div class=\"field-widget\"><input type=\"text\" name=\"state\" id=\"state\" class=\"\" title=\"".$LANG['title_state_prov']."\" size=\"50\" value=\"".htmlentities(stripslashes($row['state']))."\"/></div></div>\n";
		echo "\t\t\t<div class=\"field-row\"><div class=\"field-label\"><label for=\"zip\">".$LANG['zip_pos']."</label>:</div> <div class=\"field-widget\"><input type=\"text\" name=\"zip\" id=\"zip\" class=\"\" title=\"".$LANG['title_zip_pos']."\" size=\"10\" value=\"".htmlentities(stripslashes($row['zip']))."\"/></div></div>\n";
		echo "\t\t\t<div class=\"field-row\"><div class=\"field-label\"><label for=\"home\">".$LANG['home_phone']."</label>:</div> <div class=\"field-widget\"><input type=\"text\" name=\"home\" id=\"home\" class=\"validate-phone\" title=\"".$LANG['title_phone']."\" size=\"20\" value=\"".htmlentities(stripslashes($row['home']))."\"/></div></div>\n";
		echo "\t\t\t<div class=\"field-row\"><div class=\"field-label\"><label for=\"work\">".$LANG['work_phone']."</label>:</div> <div class=\"field-widget\"><input type=\"text\" name=\"work\" id=\"work\" class=\"validate-phone\" title=\"".$LANG['title_phone']."\" size=\"20\" value=\"".htmlentities(stripslashes($row['work']))."\"/></div></div>\n";
		echo "\t\t\t<div class=\"field-row\"><div class=\"field-label\"><label for=\"cell\">".$LANG['mobile_phone']."</label>:</div> <div class=\"field-widget\"><input type=\"text\" name=\"cell\" id=\"cell\" class=\"validate-phone\" title=\"".$LANG['title_phone']."\" size=\"20\" value=\"".htmlentities(stripslashes($row['cell']))."\"/></div></div>\n";
		echo "\t\t\t</fieldset>\n";
		if($type == 'edit') {
			echo "\t\t\t<div><input type=\"hidden\" name=\"aid\" value=\"$addressid\"/></div>\n";
			echo "\t\t\t<div><input type=\"hidden\" name=\"uid\" value=\"".$row['uid']."\"/></div>\n";
			echo "\t\t\t<p><input type=\"submit\" name=\"editsubmit\" value=\"".$LANG['edit_address']."\"/></form></p>\n";
		} else {
			echo "\t\t\t<p><input type=\"submit\" name=\"addsubmit\" value=\"".$LANG['add_address']."\"/></form></p>\n";
		}
		echo "\t\t\t<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>\n";
	}

	function displayWhatsNewAddressBook () {
		global $LANG;
		$today = date('Y-m-d');
		$tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		echo "\t\t\t\t<h3>".$LANG['link_address']."</h3>\n\t\t\t\t<ul>\n";
		$this->db->query("SELECT a.id, u.id AS user, lname, fname, displayname, username, updated FROM fcms_users AS u, fcms_address AS a WHERE u.id = a.user AND updated >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) ORDER BY updated DESC LIMIT 0, 5") or die('<h1>New Error (addressbook.class.php 123)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			while($row = $this->db->get_row()) {
				$displayname = getUserDisplayName($row['user']);
				$monthName = gmdate('M', strtotime($row['updated'] . $this->tz_offset));
				$date = gmdate('. j, Y, g:i a', strtotime($row['updated'] . $this->tz_offset));
				echo "\t\t\t\t\t<li";
				if(strtotime($row['updated']) >= strtotime($today) && strtotime($row['updated']) > $tomorrow) { echo " class=\"new\""; }
				echo "><a href=\"addressbook.php?address=".$row['id']."\">$displayname</a> - <span>".$LANG[$monthName]."$date</span></li>\n";			
			}
		} else {
			echo "\t\t\t\t\t<li><i>".$LANG['nothing_new_30']."</i></li>\n";
		}
		echo "\t\t\t\t</ul>\n";
	}
}
?>