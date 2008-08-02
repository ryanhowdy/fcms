<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class Settings {

	var $db;
	var $cur_user_id;
	var $cur_user_email;

	function Settings ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
		$this->db->query("SELECT email FROM `fcms_users` WHERE id = " . $this->cur_user_id) or die('<h1>Timezone Error (settings.class.php 14)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$this->cur_user_email = $row['email'];
	}

	function displayForm () {
		global $LANG;
		$this->db->query("SELECT * FROM `fcms_users` WHERE id = " . $this->cur_user_id) or die('<h1>User Error (settings.class.php 22)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$year = substr($row['birthday'], 0,4); $month = substr($row['birthday'], 5,2); $day = substr($row['birthday'], 8,2);
		echo "<script src=\"inc/prototype.js\" type=\"text/javascript\"></script><script type=\"text/javascript\" src=\"inc/livevalidation.js\"></script>\n";
		echo "<form id=\"frm\" enctype=\"multipart/form-data\" action=\"settings.php\" method=\"post\"><p class=\"info-alert\">".$LANG['info_settings_blank']."</p>\n";
		echo "<h2 class=\"bg\">".$LANG['settings']."</h2><div class=\"field-row clearfix\"><div class=\"field-label\"><b>".$LANG['access_level']."</b>:</div><div class=\"field-widget\"><p class=\"info\">";
		switch($row['access']) {
			case 1: echo "<b>".$LANG['level1_1']."</b>: ".$LANG['level1_2']."<br/>".$LANG['level1_3']; break;
			case 2: echo "<b>".$LANG['level2_1']."</b>: ".$LANG['level2_2']."<br/>".$LANG['level2_3']; break;
			case 3: echo "<b>".$LANG['level3_1']."</b>: ".$LANG['level3_2']."<br/>".$LANG['level3_3']; break;
			case 4: echo "<b>".$LANG['level4_1']."</b>: ".$LANG['level4_2']."<br/>".$LANG['level4_3']; break;
			case 5: echo "<b>".$LANG['level5_1']."</b>: ".$LANG['level5_2']."<br/>".$LANG['level5_3']; break;
			case 6: echo "<b>".$LANG['level6_1']."</b>: ".$LANG['level6_2']."<br/>".$LANG['level6_3']; break;
			case 7: echo "<b>".$LANG['level7_1']."</b>: ".$LANG['level7_2']."<br/>".$LANG['level7_3']; break;
			case 8: echo "<b>".$LANG['level8_1']."</b>: ".$LANG['level8_2']."<br/>".$LANG['level8_3']; break;
			case 9: echo "<b>".$LANG['level9_1']."</b>: ".$LANG['level9_2']."<br/>".$LANG['level9_3']; break;
			case 10: echo "<b>".$LANG['level10_1']."</b>: ".$LANG['level10_2']."<br/>".$LANG['level10_3']; break;
			default: echo "<b>".$LANG['level3_1']."</b>: ".$LANG['level3_2']."<br/>".$LANG['level3_3']; break;
		}
		echo "</p></div></div>\n<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"theme\"><b>".$LANG['theme']."</b></label>:</div><div class=\"field-widget\"><select name=\"theme\" id=\"theme\">";
		$dir = "themes/";
		if (is_dir($dir))	{
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if (filetype($dir . $file) !== "dir" && $file !== "datechooser.css" && $file !== "login.css") {
						$arr[] = $file;
					}
				}
				closedir($dh);
				sort($arr);
				foreach($arr as $file) {
					echo "<option value=\"$file\""; if($row['theme'] == $file) { echo " selected=\"selected\""; } echo ">$file</option>";
				}
			}
		}
		echo "</select><br/><small>(".$LANG['theme_desc'].")</small></div></div>\n";
		echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"avatar\"><b>".$LANG['avatar']."</b></label>:<br/><img src=\"gallery/avatar/" . $row['avatar'] . "\" alt=\"avatar\"/></div><div class=\"field-widget\"><span><input type=\"file\" name=\"avatar\" id=\"avatar\" size=\"30\" title=\"".$LANG['title_avatar']."\"/></span>";
		echo "<input type=\"hidden\" name=\"avatar_orig\" value=\"" . $row['avatar'] . "\"/></div></div>\n";
		echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"displayname\"><b>".$LANG['display_name']."</b></label>:</div><div class=\"field-widget\"><select name=\"displayname\" id=\"displayname\" title=\"".$LANG['title_display']."\">";
		echo "<option value=\"1\"";
		if($row['displayname'] == '1') { echo " selected=\"selected\""; } echo ">".$LANG['first_name']."</option><option value=\"2\"";
		if($row['displayname'] == '2') { echo " selected=\"selected\""; } echo ">".$LANG['first_last_name']."</option><option value=\"3\"";
		if($row['displayname'] == '3') { echo " selected=\"selected\""; } echo ">".$LANG['username']."</option></select></div></div>\n";
		echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"frontpage\"><b>".$LANG['frontpage']."</b></label>:</div><div class=\"field-widget\"><select name=\"frontpage\" id=\"frontpage\" title=\"".$LANG['title_frontpage']."\">";
		echo "<option value=\"1\"";
		if($row['frontpage'] == '1') { echo " selected=\"selected\""; } echo ">".$LANG['all_by_date']."</option><option value=\"2\"";
		if($row['frontpage'] == '2') { echo " selected=\"selected\""; } echo ">".$LANG['last_5_sections']."</option></select></div></div>\n";
		echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"timezone\"><b>".$LANG['timezone']."</b></label>:</div><div class=\"field-widget\"><select name=\"timezone\" id=\"timezone\" title=\"".$LANG['title_timezone']."\">";
		echo "<option value=\"-12 hours\""; if($row['timezone'] == '-12 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_12']."</option>";
		echo "<option value=\"-11 hours\""; if($row['timezone'] == '-11 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_11']."</option>";
		echo "<option value=\"-10 hours\""; if($row['timezone'] == '-10 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_10']."</option>";
		echo "<option value=\"-9 hours\""; if($row['timezone'] == '-9 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_9']."</option>";
		echo "<option value=\"-8 hours\""; if($row['timezone'] == '-8 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_8']."</option>";
		echo "<option value=\"-7 hours\""; if($row['timezone'] == '-7 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_7']."</option>";
		echo "<option value=\"-6 hours\""; if($row['timezone'] == '-6 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_6']."</option>";
		echo "<option value=\"-5 hours\""; if($row['timezone'] == '-5 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_5']."</option>";
		echo "<option value=\"-4 hours\""; if($row['timezone'] == '-4 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_4']."</option>";
		echo "<option value=\"-3 hours -30 minutes\""; if($row['timezone'] == '-3 hours -30 minutes') { echo " selected=\"selected\""; } echo ">".$LANG['tz_33']."</option>";
		echo "<option value=\"-3 hours\""; if($row['timezone'] == '-3 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_3']."</option>";
		echo "<option value=\"-2 hours\""; if($row['timezone'] == '-2 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_2']."</option>";
		echo "<option value=\"-1 hours\""; if($row['timezone'] == '-1 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz_1']."</option>";
		echo "<option value=\"-0 hours\""; if($row['timezone'] == '-0 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz0']."</option>";
		echo "<option value=\"+1 hours\""; if($row['timezone'] == '+1 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz1']."</option>";
		echo "<option value=\"+2 hours\""; if($row['timezone'] == '+2 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz2']."</option>";
		echo "<option value=\"+3 hours\""; if($row['timezone'] == '+3 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz3']."</option>";
		echo "<option value=\"+3 hours +30 minutes\""; if($row['timezone'] == '+3 hours +30 minutes') { echo " selected=\"selected\""; } echo ">".$LANG['tz33']."</option>";
		echo "<option value=\"+4 hours\""; if($row['timezone'] == '+4 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz4']."</option>";
		echo "<option value=\"+4 hours +30 minutes\""; if($row['timezone'] == '+4 hours +30 minutes') { echo " selected=\"selected\""; } echo ">".$LANG['tz43']."</option>";
		echo "<option value=\"+5 hours\""; if($row['timezone'] == '+5 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz5']."</option>";
		echo "<option value=\"+5 hours +30 minutes\""; if($row['timezone'] == '+5 hours +30 minutes') { echo " selected=\"selected\""; } echo ">".$LANG['tz53']."</option>";
		echo "<option value=\"+6 hours\""; if($row['timezone'] == '+6 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz6']."</option>";
		echo "<option value=\"+7 hours\""; if($row['timezone'] == '+7 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz7']."</option>";
		echo "<option value=\"+8 hours\""; if($row['timezone'] == '+8 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz8']."</option>";
		echo "<option value=\"+9 hours\""; if($row['timezone'] == '+9 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz9']."</option>";
		echo "<option value=\"+9 hours +30 minutes\""; if($row['timezone'] == '+9 hours +30 minutes') { echo " selected=\"selected\""; } echo ">".$LANG['tz93']."</option>";
		echo "<option value=\"+10 hours\""; if($row['timezone'] == '+10 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz10']."</option>";
		echo "<option value=\"+11 hours\""; if($row['timezone'] == '+11 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz11']."</option>";
		echo "<option value=\"+12 hours\""; if($row['timezone'] == '+12 hours') { echo " selected=\"selected\""; } echo ">".$LANG['tz12']."</option></select></div></div>\n";
		echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"dst\"><b>".$LANG['daylight']."</b></label>:</div><div class=\"field-widget\"><select name=\"dst\" id=\"dst\" title=\"".$LANG['title_daylight']."\">";
		echo "<option value=\"Yes\""; if($row['dst'] == '1') { echo " selected=\"selected\""; } echo ">".$LANG['yes']."</option>";
		echo "<option value=\"No\""; if($row['dst'] == '0') { echo " selected=\"selected\""; } echo ">".$LANG['no']."</option></select><br/><small>(".$LANG['daylight_desc'].")</small></div></div>\n";
		echo "<h2 class=\"bg\">".$LANG['link_board']."</h2>\n<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"boardsort\"><b>".$LANG['sort_msgs']."</b></label>:</div><div class=\"field-widget\"><select name=\"boardsort\" id=\"boardsort\" title=\"".$LANG['title_sort_msgs']."\">";
		if($row['boardsort'] == 'ASC') {
			echo "<option value=\"ASC\" selected=\"selected\">".$LANG['msgs_bottom']."</option><option value=\"DESC\">".$LANG['msgs_top']."</option></select></div></div>\n";
		} else {
			echo "<option value=\"ASC\">".$LANG['msgs_bottom']."</option><option value=\"DESC\" selected=\"selected\">".$LANG['msgs_top']."</option></select></div></div>\n";
		}
		echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"showavatar\"><b>".$LANG['show_avatars']."</b></label>:</div><div class=\"field-widget\">";
		if($row['showavatar'] == 'YES') {
			echo "<input type=\"radio\" name=\"showavatar\" id=\"showavatar_yes\" value=\"YES\" checked=\"checked\"/> ".$LANG['yes']."<br/><input type=\"radio\" name=\"showavatar\" id=\"showavatar_no\" value=\"NO\" class=\"validate-one-required\"/> ".$LANG['no']."</div></div>\n";
		} else {
			echo "<input type=\"radio\" name=\"showavatar\" id=\"showavatar_yes\" value=\"YES\"/> ".$LANG['yes']."<br/><input type=\"radio\" name=\"showavatar\" id=\"showavatar_no\" value=\"NO\"  checked=\"checked\" class=\"validate-one-required\"/> ".$LANG['no']."</div></div>\n";
		}
		echo "<h2 class=\"bg\">".$LANG['personal_info']."</h2><div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"fname\"><b>".$LANG['first_name']."</b></label>:</div><div class=\"field-widget\"><span><input type=\"text\" name=\"fname\" size=\"50\" id=\"fname\" class=\"required\" value=\"" . $row['fname'] . "\" title=\"".$LANG['title_fname']."\"/></span></div></div>\n";
		echo "\t\t\t\t<script type=\"text/javascript\">\n\t\t\t\t\tvar ffname = new LiveValidation('fname', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\t\tffname.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\t</script>\n\t\t\t\t";
		echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"lname\"><b>".$LANG['last_name']."</b></label>:</div><div class=\"field-widget\"><span><input type=\"text\" name=\"lname\" size=\"50\" id=\"lname\" class=\"required\" value=\"" . $row['lname'] . "\" title=\"".$LANG['title_lname']."\"/></span></div></div>\n";
		echo "\t\t\t\t<script type=\"text/javascript\">\n\t\t\t\t\tvar flname = new LiveValidation('lname', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\t\tflname.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\t</script>\n\t\t\t\t";
		echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"email\"><b>".$LANG['email']."</b></label>:</div><div class=\"field-widget\"><span><input type=\"text\" name=\"email\" size=\"50\" id=\"email\" class=\"validate-email required\" value=\"" . $row['email'] . "\" title=\"".$LANG['title_email']."\"/></span></div></div>\n";
		echo "\t\t\t\t<script type=\"text/javascript\">\n\t\t\t\t\tvar femail = new LiveValidation('email', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\t\tfemail.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\tfemail.add( Validate.Email, { failureMessage: \"".$LANG['lv_bad_email']."\" } );\n\t\t\t\tfemail.add( Validate.Length, { minimum: 10 } );\n\t\t\t\t</script>\n\t\t\t\t";
		echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"day\"><b>".$LANG['birthday']."</b></label>:</div><div class=\"field-widget\"><select name=\"day\">";
		$d = 1;
		while($d <= 31) {
			if($day == $d) { echo "<option value=\"$d\" selected=\"selected\">$d</option>"; }
			else { echo "<option value=\"$d\">$d</option>"; }
			$d++;
		}
		echo '</select><select name="month">';
		$m = 1;
		while($m <= 12) {
			$monthName = date('M', mktime(0, 0, 0, $m, 1, 2006));
			if($month == $m) { echo "<option value=\"$m\" selected=\"selected\">".$LANG[$monthName]."</option>"; }
			else { echo "<option value=\"$m\">".$LANG[$monthName]."</option>"; }
			$m++;
		}
		echo '</select><select name="year">';
		$y = 1900;
		while($y - 5 <= date('Y')) {
			if($year == $y) { echo "<option value=\"$y\" selected=\"selected\">$y</option>"; }
			else { echo "<option value=\"$y\">$y</option>"; }
			$y++;
		} 
		echo "</select></div></div>\n";
		echo "<h2 class=\"bg\">".$LANG['login_info']."</h2><div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"uname\"><b>".$LANG['username']."</b></label>:</div><div class=\"field-widget\"><span><input disabled=\"disabled\" type=\"text\" name=\"uname\" size=\"50\" id=\"uname\" value=\"" . $row['username'] . "\"/></span></div></div>\n";
		echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"pass\"><b>".$LANG['password']."</b></label>:</div><div class=\"field-widget\"><span><input type=\"password\" name=\"pass\" size=\"50\" id=\"pass\"/></span><br/><small>(".$LANG['password_desc'].")</small></div></div>\n";
		echo "<p><input type=\"submit\" name=\"submit\" value=\"".$LANG['submit']."\"/></p></form>\n";
	}

} ?>