<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class Prayers {

	var $db;
	var $db2;
	var $tz_offset;
	var $cur_user_id;

	function Prayers ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
		$this->db2 = new database($type, $host, $database, $user, $pass);
		$this->db->query("SELECT `timezone` FROM `fcms_users` WHERE `id` = $current_user_id") or die('<h1>Timezone Error (prayers_class.php 17)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function showPrayers ($page = '1') {
		global $LANG;
		$from = (($page * 5) - 5); 
		$this->db->query("SELECT p.`id`, `for`, `desc`, `user`, `date` FROM `fcms_prayers` AS p, `fcms_users` AS u WHERE u.`id` = p.`user` ORDER BY `date` DESC LIMIT " . $from . ", 5") or die("<h1>Get Prayers Error (prayers_class.php 21)</h1>" . mysql_error());
		if ($this->db->count_rows() > 0) {
			while($r = $this->db->get_row()) {
				$monthName = gmdate('F', strtotime($r['date'] . $this->tz_offset));
				$date = fixDST(gmdate('F j, Y g:i a', strtotime($r['date'] . $this->tz_offset)), $this->cur_user_id, 'j, Y g:i a');
				$displayname = getUserDisplayName($r['user']);
				echo "\t\t\t<hr/><div><h4>".$LANG[$monthName]." $date";
					if ($this->cur_user_id == $r['user'] || checkAccess($this->cur_user_id) < 2) {
						echo " &nbsp;<form class=\"frm_inline\" method=\"post\" action=\"prayers.php\"><div><input type=\"hidden\" name=\"id\" value=\"".$r['id']."\"/><input type=\"hidden\" name=\"for\" value=\"".htmlentities($r['for'], ENT_COMPAT, 'UTF-8')."\"/><input type=\"hidden\" name=\"desc\" value=\"".htmlentities($r['desc'], ENT_COMPAT, 'UTF-8')."\"/><input type=\"submit\" name=\"editprayer\" value=\" \" class=\"editbtn\" title=\"".$LANG['title_edit_prayer']."\"/></div></form>";
					}
					if (checkAccess($_SESSION['login_id']) < 2) {
						echo " &nbsp;<form class=\"frm_inline\" method=\"post\" action=\"prayers.php\"><div><input type=\"hidden\" name=\"id\" value=\"".$r['id']."\"/><input type=\"submit\" name=\"delprayer\" value=\" \" class=\"delbtn\" title=\"Delete this Prayer Concern.\" onclick=\"javascript:return confirm('Are you sure you want to DELETE this Prayer Concern?');\"/></div></form>";
					}
				echo "</h4><b><a href=\"profile.php?member=" . $r['id'] . "\">$displayname</a> ".$LANG['asks_pray']."...</b><br/>&nbsp;&nbsp;&nbsp;&nbsp;".$r['for']."<br/><br/><b>".$LANG['because']."...</b><br/>&nbsp;&nbsp;&nbsp;&nbsp;";
				parse($r['desc']);
				echo "</div><p>&nbsp;</p>\n\t\t\t<div class=\"top\"><a href=\"#top\">".$LANG['back_top']."</a></div><p>&nbsp;</p>\n";
			}
			$this->db2->query("SELECT count(`id`) AS c FROM `fcms_prayers`")  or die('<h1>Count Error (prayers_class.php 41)</h1>' . mysql_error());
			while ($r = $this->db2->get_row()) { $prayercount = $r['c']; }
			$total_pages = ceil($prayercount / 5); 
			if ($total_pages > 1) {
				echo "<div class=\"pages clearfix\"><ul>"; 
				if ($page > 1) { 
					$prev = ($page - 1); 
					echo "<li><a title=\"".$LANG['title_first_page']."\" class=\"first\" href=\"prayers.php?page=1\"></a></li>"; 
					echo "<li><a title=\"".$LANG['title_prev_page']."\" class=\"previous\" href=\"prayers.php?page=$prev\"></a></li>"; 
				} 
				if ($total_pages > 8) {
					if($page > 2) {
						for($i = ($page-2); $i <= ($page+5); $i++) {
							if($i <= $total_pages) { echo "<li><a href=\"prayers.php?page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; }
						} 
					} else {
						for($i = 1; $i <= 8; $i++) { echo "<li><a href=\"prayers.php?page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; } 
					}
				} else {
					for($i = 1; $i <= $total_pages; $i++) {
						echo "<li><a href=\"prayers.php?page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>";
					} 
				}
				if ($page < $total_pages) { 
					$next = ($page + 1); 
					echo "<li><a title=\"".$LANG['title_next_page']."\" class=\"next\" href=\"prayers.php?page=$next\"></a></li>"; 
					echo "<li><a title=\"".$LANG['title_last_page']."\" class=\"last\" href=\"prayers.php?page=$total_pages\"></a></li>"; 
				} 
				echo "</ul></div>";
			}
		} else {
			echo "<div class=\"info-alert\"><h2>".$LANG['info_prayers1']."</h2><p><i>".$LANG['info_prayers2']."</i></p><p><b>".$LANG['info_prayers3']."</b><br/>".$LANG['info_prayers4']." <a href=\"?addconcern=yes\">".$LANG['add_prayer']."</a> ".$LANG['info_prayers5']."</p></div>";
		}
		echo "\t\t\t<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>\n";
	}

	function displayForm ($type, $id = '0', $for = 'error', $desc = 'error') {
		global $LANG;
		echo "<script src=\"inc/prototype.js\" type=\"text/javascript\"></script>\n\t\t\t<script type=\"text/javascript\" src=\"inc/livevalidation.js\"></script>\n\t\t\t";
		if ($type == 'edit') {
			echo "<form method=\"post\" name=\"editform\" action=\"prayers.php\">\n\t\t\t\t<br/><h3>".$LANG['edit_prayer']."</h3>\n\t\t\t\t";			
		} else {
			echo "<form method=\"post\" name=\"addform\" action=\"prayers.php\">\n\t\t\t\t<h3>".$LANG['add_prayer']."</h3>\n\t\t\t\t";
		}
		echo "<div><label for=\"for\">".$LANG['pray_for']."</label>: <input type=\"text\" name=\"for\" id=\"for\" class=\"required\" title=\"".$LANG['title_pray_for']."\" size=\"50\"";
		if ($type == 'edit') { echo " value=\"$for\""; }
		echo "/></div><br/>\n\t\t\t\t";
		echo "\t\t\t\t<script type=\"text/javascript\">\n\t\t\t\t\tvar ffor = new LiveValidation('for', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\t\tffor.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\t</script>\n\t\t\t\t";
		echo "<div><textarea name=\"desc\" id=\"desc\" class=\"required\" rows=\"10\" cols=\"63\" title=\"".$LANG['title_desc_pray']."\">";
		if ($type == 'edit') { echo $desc; }
		echo "</textarea></div>\n\t\t\t\t";
		echo "\t\t\t\t<script type=\"text/javascript\">\n\t\t\t\t\tvar fdesc = new LiveValidation('desc', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\t\tfdesc.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\t</script>\n\t\t\t\t";
		if ($type == 'add') {
			echo "<div><input type=\"submit\" name=\"submitadd\" value=\"".$LANG['submit']."\"/></div>";
		} else {
			echo "<div><input type=\"hidden\" name=\"id\" value=\"$id\"/><input type=\"submit\" name=\"submitedit\" value=\"".$LANG['edit']."\"/></div>";
		}
		echo "</form>\n\t\t\t<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>";
	}

	function displayWhatsNewPrayers () {
		global $LANG;
		$today = date('Y-m-d');
		$tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		$this->db->query("SELECT `for` , `date` FROM `fcms_prayers` WHERE `date` >= DATE_SUB(CURDATE() , INTERVAL 30 DAY) ORDER BY `date` DESC LIMIT 0 , 5");
		if ($this->db->count_rows() > 0) {
			echo "\n\t\t\t\t<h3>".$LANG['link_prayer']."</h3>\n\t\t\t\t<ul>\n";
			while ($r = $this->db->get_row()) {
				$for = $r['for'];
				$monthName = gmdate('M', strtotime($r['date'] . $this->tz_offset));
				$date = gmdate('. j, Y, g:i a', strtotime($r['date'] . $this->tz_offset));
				echo "\t\t\t\t\t<li";
				if(strtotime($r['date']) >= strtotime($today) && strtotime($r['date']) > $tomorrow) { echo " class=\"new\""; }
				echo "><a href=\"prayers.php\">$for</a> - <span>".$LANG[$monthName]."$date</span></li>\n";			
			}
			echo "\t\t\t\t</ul>\n";
		}
	}

} ?>