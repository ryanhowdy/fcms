<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class Calendar {

	var $db;
	var $cur_user_id;

	function Calendar ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
	}

	function getEventDays ($month, $year) {
		$days = array();
		$this->db->query("SELECT DAYOFMONTH(`date`) as day, `private`, `created_by` FROM `fcms_calendar` WHERE (`date` LIKE '$year-$month-%%' AND `type` = 'Other') OR (`date` LIKE '%%%%-$month-%%' AND `type` != 'Other') ORDER BY day") or die('<h1>Private Events Error (calendar.class.php 23)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			while($r = $this->db->get_row()) {
				if ($r['private'] == 1) {
					if ($r['created_by'] == $this->cur_user_id) { $days[] = $r['day']; }
				} else { $days[] = $r['day']; }
			}
		}
		return $days;
	}

	function displayCalendar ($month, $year, $day, $type = 'small') {
		global $LANG;
		echo "\n\t\t<table id=\""; if ($type == 'big') { echo "big"; } else { echo "small"; } echo "_calendar\">\n\t\t\t<tr><th colspan=\"7\">";
		$first = mktime(0,0,0,$month,1,$year);
		$offset = date('w', $first);
		$daysInMonth = date('t', $first);
		$monthName = date('F', $first);
		if ($type == 'big') {
			$weekDays = array($LANG['cal_sun_big'], $LANG['cal_mon_big'], $LANG['cal_tue_big'], $LANG['cal_wed_big'], $LANG['cal_thr_big'], $LANG['cal_fri_big'], $LANG['cal_sat_big']);
		} else { 
			$weekDays = array($LANG['cal_sun'], $LANG['cal_mon'], $LANG['cal_tue'], $LANG['cal_wed'], $LANG['cal_thr'], $LANG['cal_fri'], $LANG['cal_sat']);
		}
		$eventDays = $this->getEventDays($month, $year);
		$prevTS = strtotime("$year-$month-01 -1 month");
		$pMax = date('t', $prevTS);
		$pDay = ($day > $pMax) ? $pMax : $day;
		list($y, $m) = explode('-', date('Y-m', $prevTS));
		if ($m == 12) {
			echo "<a class=\"prev\" href=\"?year="; echo $year-1; echo "&amp;month=$m&amp;day=$pDay\">".$LANG['prev']."</a>";
		} else {
			echo "<a class=\"prev\" href=\"?year=$year&amp;month=$m&amp;day=$pDay\">".$LANG['prev']."</a>";
		}
		$nextTS = strtotime("$year-$month-01 +1 month");
		$nMax = date('t', $nextTS);
		$nDay = ($day > $nMax) ? $nMax : $day;
		list($y, $m) = explode('-', date('Y-m', $nextTS));
		echo "<a class=\"next\" href=\"?year=$y&amp;month=$m&amp;day=$nDay\">".$LANG['next']."</a><h3>".$LANG[$monthName]." $year</h3></th></tr>\n\t\t\t<tr>";
		foreach ($weekDays as $wd) { echo "<td class=\"weekDays\">$wd</td>"; }
		echo "</tr>\n\t\t\t";
		$i = 0;
		for ($d = (1 - $offset); $d <= $daysInMonth; $d++) {
			if ($i % 7 == 0) { echo "<tr>"; }
			if ($d < 1) { echo "<td class=\"nonMonthDay\"> </td>";
			} else {
				if ($d == $day) { echo "<td class=\"monthToday\">"; } else { echo "<td class=\"monthDay\">"; }
				if ($type == 'big') {
					if (checkAccess($_SESSION['login_id']) <= 5) { echo "<a class=\"add\" href=\"?add=$year-$month-$d\">".$LANG['add']."</a>"; }
					echo $d;
				}
				if (in_array($d, $eventDays)) { 
					if ($type == 'big') { $this->displayEvents($month, $d, $year); } else { echo "<a href=\"?year=$year&amp;month=$month&amp;day=$d\">$d</a>"; }
				} else {
					if ($type !== 'big') { echo $d; }
				}
				echo "</td>";
			}
			$i++;
			if ($i % 7 == 0) { echo "</tr>\n\t\t\t"; }
		}
		if ($i % 7 != 0) {
			for ($j = 0; $j < (7 - ($i % 7)); $j++) { echo "<td class=\"nonMonthDay\"> </td>"; }
			echo "</tr>\n";
		}
		echo "\t\t</table>\n\t\t";
	}

	function displayMonthEvents ($month, $year) {
		global $LANG;
		$monthName = date("F", mktime(0,0,0,$month,1,2006));
		echo "<h3>".$LANG[$monthName].":</h3>";
		$this->db->query("SELECT *, SUBSTRING(`date`, 9, 2) AS o FROM fcms_calendar WHERE (`date` LIKE '$year-$month-%%' AND `type` = 'Other') OR (`date` LIKE '%%%%-$month-%%' AND `type` != 'Other') ORDER BY o") or die('<h1>Events Error (calendar.class.php 77)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			while ($row = $this->db->get_row()) {
				$show = false;
				if ($row['private'] == 0) {
					$show = true;
				} else {
					if ($row['created_by'] == $this->cur_user_id) { $show = true; }
				}
				if ($show) {
					echo "<div class=\"events\">";
					echo date('d', strtotime($row['date']));
					if (!empty($row['desc'])) {
						$desc = "<dfn title=\"".htmlentities($row['desc'], ENT_COMPAT, 'UTF-8')."\">".$row['title']."</dfn>";
					} else {
						$desc = $row['title'];
					}
					switch ($row['type']) {
						case 'Birthday': echo " - <span class=\"bday\">$desc</span></div>"; break;
						case 'Anniversary': echo " - <span class=\"anniversary\">$desc</span></div>"; break;
						default: echo " - <span class=\"holiday\">$desc</span></div>"; break;
					}
				}
			}
			echo "\n\t\t";
		} else { echo "<div class=\"events\"><i>".$LANG['no_events']."</i></div>\n\t\t"; }
	}

	function displayTodaysEvents ($month, $day, $year) {
		global $LANG;
		$this->db->query("SELECT * FROM fcms_calendar WHERE (`date` LIKE '$year-$month-$day' AND `type` = 'Other') OR (`date` LIKE '%%%%-$month-$day' AND `type` != 'Other')") or die('<h1>Today Error (calendar.class.php 98)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			$first = true;
			while ($row = $this->db->get_row()) {
				$show = false;
				if ($row['private'] == 0) {
					$show = true;
				} else {
					if ($row['created_by'] == $this->cur_user_id) { $show = true; }
				}
				if ($first & $show) {
					echo "\n\t\t\t". '<br/><div><b class="rounded-box"><b class="rounded-box1"><b></b></b><b class="rounded-box2"><b></b></b><b class="rounded-box3"></b><b class="rounded-box4"></b><b class="rounded-box5"></b></b>';
					echo "\n\t\t\t" . '<div class="rounded-boxfg">';
					echo "\n\t\t\t<h2>".$LANG['todays_events'].":</h2>";
					$first = false;
				}
				if ($show) {
					echo "<div class=\"events\">";
					switch($row['type']) {
						case 'Birthday': echo " - <span class=\"bday\">".$row['title']."</span> "; if(!empty($row['desc'])) { echo "<br/>".$row['desc']; } echo "</div>"; break;
						case 'Anniversary': echo " - <span class=\"anniversary\">".$row['title']."</span> "; if(!empty($row['desc'])) { echo "<br/>".$row['desc']; } echo "</div>"; break;
						default: echo " - <span class=\"holiday\">".$row['title']."</span> "; if(!empty($row['desc'])) { echo $row['desc']; } echo "</div>"; break;
					}
				}
			}
			if (!$first) {
				echo "</div>\n\t\t\t";
				echo '<b class="rounded-box"><b class="rounded-box5"></b><b class="rounded-box4"></b><b class="rounded-box3"></b><b class="rounded-box2"><b></b></b><b class="rounded-box1"><b></b></b></b></div>';
			}
		}
	}

	function displayEvents ($month, $day, $year) {
		$day = str_pad($day, 2, 0, STR_PAD_LEFT);
		$this->db->query("SELECT * FROM fcms_calendar WHERE (`date` LIKE '$year-$month-$day' AND `type` = 'Other') OR (`date` LIKE '%%%%-$month-$day' AND `type` != 'Other')") or die('<h1>Events Error (calendar.class.php 119)</h1>' . mysql_error());
		while ($row = $this->db->get_row()) {
			$show = false;
			if ($row['private'] == 0) {
				$show = true;
			} else {
				if ($row['created_by'] == $this->cur_user_id) { $show = true; }
			}
			if ($show) {
				if (checkAccess($this->cur_user_id) < 2 || $this->cur_user_id == $row['created_by']) {
					echo "<p>- <a title=\"".htmlentities($row['desc'], ENT_COMPAT, 'UTF-8')."\" href=\"?edit=".$row['id']."\">".$row['title']."</a></p>";
				} else {
					echo "<p>- <span title=\"".htmlentities($row['desc'], ENT_COMPAT, 'UTF-8')."\">".$row['title']."</span></p>";
				}
			}
		}
	}

	function displayForm ($type, $id = '0') {
		global $LANG;
		$this->db->query("SELECT * FROM fcms_calendar WHERE id = $id") or die('<h1>Date Error (calendar.class.php 130)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$show = false;
		if ($row['private'] == 0) {
			$show = true;
		} else {
			if ($row['created_by'] == $this->cur_user_id) { $show = true; }
		}
		if ($show) {
			if ($type == 'edit') {
				$year = substr($row['date'], 0,4); $month = substr($row['date'], 5,2); $day = substr($row['date'], 8,2);
			} else {
				$year = substr($type, 0,4); $month = substr($type, 5,2); $day = substr($type, 8,2);
			}
			if (checkAccess($this->cur_user_id) < 2 || $this->cur_user_id == $row['created_by'] || $type !== 'edit') {
				echo "<h2>"; if ($type == 'edit') { echo $LANG['edit']; } else { echo $LANG['add']; } echo " ".$LANG['cal_entry']."</h2>\n\t\t\t";
				echo "<script src=\"inc/prototype.js\" type=\"text/javascript\"></script>\n\t\t\t<script type=\"text/javascript\" src=\"inc/livevalidation.js\"></script><br/>\n\t\t\t";
				echo "<form id=\"frm\" method=\"post\" action=\"calendar.php\">\n\t\t\t\t";
				echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"title\"><b>".$LANG['title']."</b></label>:</div><div class=\"field-widget\"><span><input type=\"text\" class=\"required\" id=\"title\" name=\"title\" size=\"40\"";
				if ($type == 'edit') { echo " value=\"" . htmlentities($row['title'], ENT_COMPAT, 'UTF-8')  . "\""; } echo "/></span></div></div>\n\t\t\t\t";
				echo "<script type=\"text/javascript\">\n\t\t\t\t\tvar ftitle = new LiveValidation('title', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\t\tftitle.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\t</script>\n\t\t\t\t";
				echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"desc\"><b>".$LANG['desc']."</b></label>:</div><div class=\"field-widget\"><span><input type=\"text\" id=\"desc\" name=\"desc\" size=\"50\"";
				if ($type == 'edit') { echo " value=\"" . htmlentities($row['desc'], ENT_COMPAT, 'UTF-8') . "\""; } echo "/></span></div></div>\n\t\t\t\t";
				echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"sday\"><b>".$LANG['sdate']."</b></label>:</div><div class=\"field-widget\"><select id=\"sday\" name=\"sday\">";
				$d = 1;
				while($d <= 31) {
					if($day == $d) { echo "<option value=\"$d\" selected=\"selected\">$d</option>"; } else { echo "<option value=\"$d\">$d</option>"; }
					$d++;
				}
				echo '</select><select name="smonth">';
				$m = 1;
				while($m <= 12) {
					if($month == $m) { echo "<option value=\"$m\" selected=\"selected\">" . date('M', mktime(0, 0, 0, $m, 1, 2006)) . "</option>"; } else { echo "<option value=\"$m\">" . date('M', mktime(0, 0, 0, $m, 1, 2006)) . "</option>"; }
					$m++;
				}
				echo '</select><select name="syear">';
				$y = 1900;
				while($y - 5 <= date('Y')) {
					if($year == $y) { echo "<option value=\"$y\" selected=\"selected\">$y</option>"; } else { echo "<option value=\"$y\">$y</option>"; }
					$y++;
				} 
				echo "</select></div></div>\n\t\t\t\t";
	//			echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"eday\"><b>End Date</b></label>:</div><div class=\"field-widget\"><select id=\"eday\" name=\"eday\">";
	//			$d = 1;
	//			while($d <= 31) {
	//				if($day == $d) { echo "<option value=\"$d\" selected=\"selected\">$d</option>"; } else { echo "<option value=\"$d\">$d</option>"; }
	//				$d++;
	//			}
	//			echo '</select><select name="emonth">';
	//			$m = 1;
	//			while($m <= 12) {
	//				if($month == $m) { echo "<option value=\"$m\" selected=\"selected\">" . date('M', mktime(0, 0, 0, $m, 1, 2006)) . "</option>"; } else { echo "<option value=\"$m\">" . date('M', mktime(0, 0, 0, $m, 1, 2006)) . "</option>"; }
	//				$m++;
	//			}
	//			echo '</select><select name="eyear">';
	//			$y = 1900;
	//			while($y - 5 <= date('Y')) {
	//				if($year == $y) { echo "<option value=\"$y\" selected=\"selected\">$y</option>"; } else { echo "<option value=\"$y\">$y</option>"; }
	//				$y++;
	//			} 
	//			echo "</select></div></div>\n\t\t\t\t";
				echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"type\"><b>".$LANG['type']."</b></label>:</div><div class=\"field-widget\"><select id=\"type\" name=\"type\">";
				echo "<option value=\"Anniversary\""; if ($row['type'] == 'Anniversary') { echo " selected=\"selected\""; } echo ">".$LANG['anniversary_rpt']."</option>";
				echo "<option value=\"Birthday\""; if ($row['type'] == 'Birthday') { echo " selected=\"selected\""; } echo ">".$LANG['birthday_rpt']."</option>";
				echo "<option value=\"Holiday\""; if ($row['type'] == 'Holiday') { echo " selected=\"selected\""; } echo ">".$LANG['holiday_rpt']."</option>";
				echo "<option value=\"Other\""; if ($row['type'] == 'Other') { echo " selected=\"selected\""; } echo ">".$LANG['other_one']."</option></select></div></div>\n\t\t\t\t";
				echo "<div class=\"field-row clearfix\"><div class=\"field-label\"><label for=\"private\"><b>".$LANG['private']."</b></label>:</div><div class=\"field-widget\"><input type=\"checkbox\" name=\"private\" id=\"private\"";
				if ($row['private'] == 1) { echo " checked=\"checked\""; }
				echo "/></div></div>\n\t\t\t\t";
				echo "<p><input type=\"hidden\" name=\"id\" value=\"" . $row['id'] . "\"/><input type=\"submit\" ";
				if ($type == 'edit') { echo "name=\"edit\" value=\"".$LANG['edit']."\" title=\"".$LANG['edit_cal']."\"/> "; } else { echo "name=\"add\" value=\"".$LANG['add']."\" title=\"".$LANG['add_cal']."\"/> "; }
				if ($type == 'edit') { echo "<input type=\"submit\" name=\"delete\" value=\"".$LANG['delete']."\" title=\"".$LANG['delete_cal']."\" onclick=\"javascript:return confirm('".$LANG['js_delete_cal']."');\"/>"; }
				echo "</p>\n\t\t\t</form>\n\t\t\t<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>\n";
				return false;
			} else {
				echo "<p class=\"error-alert\">".$LANG['err_no_edit_cal']."</p>";
				return true;
			}
		} else {
			echo "<p class=\"error-alert\">".$LANG['err_private_cal']."</p>";
			return true;
		}
	}

} ?>