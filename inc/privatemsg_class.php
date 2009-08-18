<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class PrivateMessage {

	var $db;
	var $db2;
	var $tz_offset;
	var $cur_user_id;

	function PrivateMessage ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
		$this->db2 = new database($type, $host, $database, $user, $pass);
		$this->db->query("SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id") or die('<h1>Timezone Error (profile.class.php 16)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function displayInbox () {
		global $LANG;
		echo "<form method=\"post\" action=\"privatemsg.php\">\n<table id=\"pm\" cellpadding=\"0\" cellspacing=\"0\">\n";
		echo "<tr><th colspan=\"5\" class=\"pm_header\">" . $LANG['privatemsgs'] . " - " . $LANG['inbox'] . "</th></tr>\n";
		echo "<tr><th colspan=\"2\">" . $LANG['pm_title'] . "</th><th>" . $LANG['pm_from'] . "</th><th>" . $LANG['date'] . "</th><th></th></tr>\n";
		$this->db->query("SELECT * FROM `fcms_privatemsg` WHERE `to` = " . $this->cur_user_id);
		while ($r = $this->db->get_row()) {
			$monthName = gmdate('M', strtotime($r['date'] . $this->tz_offset));
			$date = fixDST(gmdate('n/j/Y g:i a', strtotime($r['date'] . $this->tz_offset)), $this->cur_user_id, '. j, Y, g:i a');
			echo "<tr";
			if ($r['read'] < 1) {
				echo " class=\"new\"";
			}
			echo "><td class=\"img\"></td><td><a href=\"?pm=" . $r['id'] . "\">" . $r['title'] . "</a></td><td>" . getUserDisplayName($r['from']) . "</td><td>";
			echo getLangMonthName($monthName) . "$date";
			echo "</td><td><input type=\"checkbox\" name=\"del[]\" value=\"" . $r['id'] . "\"/></td></tr>\n";
		}
		echo "<tr><th colspan=\"5\" class=\"pm_footer\"><input type=\"submit\" name=\"delete\" value=\"".$LANG['delete']."\" onclick=\"javascript:return confirm('".$LANG['js_pm_delete']."'); \"/> " . $LANG['selected_msgs'] . "</th></tr>\n";
		echo "</table>\n</table>\n";
	}

	function displayPM ($id) {
		global $LANG;
		$this->db->query("SELECT * FROM `fcms_privatemsg` WHERE `id` = $id AND `to` = " . $this->cur_user_id);
		if ($this->db->count_rows() > 0) { 
			$r = $this->db->get_row();
			$this->db->query("UPDATE `fcms_privatemsg` SET `read` = '1' WHERE `id` = $id");
			$date = fixDST(gmdate('n/j/Y g:i a', strtotime($r['date'] . $this->tz_offset)), $this->cur_user_id, 'n/j/Y g:i a');
			echo "<div id=\"pm_msg\">\n\t\t\t\t";
			echo "<b>" . $LANG['pm_sent'] . ":</b> $date<br/>\n\t\t\t\t<b>" . $LANG['pm_from'] . ":</b> " . getUserDisplayName($r['from']) . "<br/>\n\t\t\t\t";
			echo "<b>" . $LANG['pm_title'] . ":</b> " . $r['title'] . "<br/>\n\t\t\t\t";
			echo "<p>";
			parse($r['msg']);
			echo "</p>\n\t\t\t<a href=\"?compose=new&amp;id=" . $r['from'] . "&amp;title=" . htmlentities($r['title'], ENT_COMPAT, 'UTF-8') . "\">" . $LANG['reply'] . "</div>\n";
		} else {
			echo "<p class=\"error\">" . $LANG['no_pm_access'] . "</p><p>&nbsp;</p>\n";
		}
	}

	function displayNewMessageForm ($id = '', $title = '') {
		global $LANG;
		echo "<script type=\"text/javascript\" src=\"inc/livevalidation.js\"></script>\n";
		echo "\t\t\t<script type=\"text/javascript\" src=\"inc/messageboard.inc.js\"></script>\n";
		echo "\t\t\t<form method=\"post\" id=\"newpmform\" action=\"privatemsg.php\">\n";
		echo "\t\t\t\t<h3>".$LANG['new_pmsg']."</h3>\n";
		echo "\t\t\t\t<div><label for=\"title\">" . $LANG['title'] . "</label>: <input type=\"text\" name=\"title\" class=\"required\" size=\"50\"";
		if (strlen($title) > 0) { echo " value=\"RE: " . htmlentities($title, ENT_COMPAT, 'UTF-8') . "\""; }
		echo "/></div><br/>\n";
		echo "\t\t\t\t<script type=\"text/javascript\">\n\t\t\t\t\tvar ftitle = new LiveValidation('title', { validMessage: \"\", wait: 500});\n\t\t\t\t\tftitle.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\t</script>\n";
		echo "\t\t\t\t<div><label for=\"to\">" . $LANG['pm_to'] . "</label>: <select name=\"to\"/>";
		$this->db->query("SELECT * FROM `fcms_users` WHERE `activated` > 0");
		while ($r = $this->db->get_row()) {
			$displayNameArr[$r['id']] = getUserDisplayName($r['id']);
		}
		asort($displayNameArr);
		foreach ($displayNameArr as $key => $value) {
			echo "<option value=\"$key\"";
			if ($id == $key) {
				echo " selected=\"selected\"";
			}
			echo ">$value</option>";
		}
		echo "</select></div><br/>\n";
		echo "\t\t\t\t<script type=\"text/javascript\">var bb = new BBCode();</script>\n";
		displayMBToolbar();
		echo "\t\t\t\t<div><textarea name=\"post\" id=\"post\" rows=\"10\" cols=\"63\" class=\"required\"></textarea></div>\n";
		echo "\t\t\t\t<script type=\"text/javascript\">bb.init('post');</script>\n";
		echo "\t\t\t\t<div><input type=\"submit\" name=\"submit\" value=\"".$LANG['send']."\"/></div>\n\t\t\t</form>\n\t\t\t<p>&nbsp;</p>\n";
	}

} ?>