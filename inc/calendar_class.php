<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class Calendar
{
    var $db;
    var $cur_user_id;

    function Calendar ($current_user_id, $type, $host, $database, $user, $pass)
    {
        $this->cur_user_id = $current_user_id;
        $this->db = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id";
        $this->db->query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
    }

    function getEventDays ($month, $year)
    {
        $days = array();
        $sql = "SELECT DAYOFMONTH(`date`) as day, `private`, `created_by` "
             . "FROM `fcms_calendar` "
             . "WHERE (`date` LIKE '$year-$month-%%' AND `type` = 'Other') "
             . "OR (`date` LIKE '%%%%-$month-%%' AND `type` != 'Other') "
             . "ORDER BY day";
        $this->db->query($sql) or displaySQLError(
            'Private Events Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        if ($this->db->count_rows() > 0) {
            while($r = $this->db->get_row()) {
                if ($r['private'] == 1) {
                    if ($r['created_by'] == $this->cur_user_id) {
                        $days[] = $r['day'];
                    }
                } else {
                    $days[] = $r['day'];
                }
            }
        }
        return $days;
    }

    /**
     * displayCalendar
     * 
     * Displays a calendar based on the month, day and year.
     * Can display big (with event details) or small, month and day views.
     * 
     * @param $month, $year, $day - calendar info
     * @param $type - small, big
     * @param $view - month, day
     */
    function displayCalendar ($month, $year, $day = -1, $type = 'small', $view = 'month')
    {
        global $LANG;
        
        $viewToolbar = '<th class="view_toolbar" colspan="2"><a class="day" '
            . "href=\"?year=$year&amp;month=$month&amp;day=$day&amp;view=day\">" . $LANG['day']
            . "</a> | <a class=\"month\" href=\"?year=$year&amp;month=$month&amp;day=$day\">"
            . $LANG['month'] . '</a></th>';
		$actionsToolbar = "<tr class=\"actions_toolbar\"><td colspan=\"7\">" . $LANG['actions']
            . ": <a class=\"print\" href=\"#\" "
            . "onclick=\"window.open('inc/calendar_print.php?year=$year&amp;month=$month"
            . "&amp;day=$day','name','width=700,height=400,scrollbars=yes,resizable=yes,"
            . "location=no,menubar=no,status=no'); return false;\">" . $LANG['print'] . "</a> | "
            . "<a href=\"?import=true\">" . $LANG['import'] . "</a> | "
            . "<a href=\"?export=true\">" . $LANG['export'] . "</a></td></tr>\n\t\t\t";
        
        echo "\n\t\t<table id=\"" . $type . "_calendar\">\n\t\t\t";
        
        // Month View
        if ($view == 'month') {
            $first = mktime(0,0,0,$month,1,$year);
            $offset = date('w', $first);
            $daysInMonth = date('t', $first);
            $monthName = date('F', $first);
            if ($type == 'big') {
                $weekDays = array($LANG['cal_sun_big'], $LANG['cal_mon_big'], $LANG['cal_tue_big'], 
                                  $LANG['cal_wed_big'], $LANG['cal_thr_big'], $LANG['cal_fri_big'], 
                                  $LANG['cal_sat_big']
                            );
            } else {
                $weekDays = array($LANG['cal_sun'], $LANG['cal_mon'], $LANG['cal_tue'], 
                                  $LANG['cal_wed'], $LANG['cal_thr'], $LANG['cal_fri'], 
                                  $LANG['cal_sat']
                            );
            }
            
            // All the events for this month
            $eventDays = $this->getEventDays($month, $year);
            
            // Timestamp of previous month
            $prevTS = strtotime("$year-$month-01 -1 month");
            // Make sure previous day is less than the total num of days in prev month
            $pDay = ($day > date('t', $prevTS)) ? date('t', $prevTS) : $day;
            
            // Timestamp of next month
            $nextTS = strtotime("$year-$month-01 +1 month");
            // Make sure next day is less than the total num of days in next month
            $nDay = ($day > date('t', $nextTS)) ? date('t', $nextTS) : $day;
            
            // Start the header row
            echo "<tr>";
            
            // Display prev/today/next links
            echo '<th';
            // Don't display the today link or views toolbar for the small calendar
            if ($type == 'big') {
                echo ' colspan="2">';
            } else {
                echo ' colspan="7">';
            }
            
            // Previous
            list($y, $m) = explode('-', date('Y-m', $prevTS));
            // if previous month is 12 then we need to subtract a year
            if ($m == 12) {
                echo "<a class=\"prev\" href=\"?year=";
                echo $year-1;
                echo "&amp;month=$m&amp;day=$pDay\">".$LANG['prev']."</a>";
            } else {
                echo "<a class=\"prev\" href=\"?year=$year&amp;month=$m&amp;day=$pDay\">"
                    . $LANG['prev'] . "</a> ";
            }
            
            // Today
            if ($type == 'big') {
                echo '<a class="today" href="?year=';
                echo date('Y');
                echo "&amp;month=";
                echo date('m');
                echo "&amp;day=";
                echo date('d');
                echo '">';
                echo $LANG['today'] . '</a> ';
            }
            
            // Next
            list($y, $m) = explode('-', date('Y-m', $nextTS));
            echo "<a class=\"next\" href=\"?year=$y&amp;month=$m&amp;day=$nDay\">"
                . $LANG['next'] . "</a>";
                
            // Display Month Name
            if ($type == 'big') {
                echo "</th><th colspan=\"3\"><h3>" . $LANG[$monthName] . " $year</h3></th>";
                
                // Display the view toolbar
                echo $viewToolbar;
            } else {
                echo "<h3>" . $LANG[$monthName] . " $year</h3></th>";
            }
            
            // Close the header row
            echo "</tr>\n\t\t\t";
            
            // Display the weekday names
            echo "<tr>";
            foreach ($weekDays as $wd) {
                echo "<td class=\"weekDays\">$wd</td>";
            }
            echo "</tr>\n\t\t\t";
            
            // Display the days in the month, fill with events
            $i = 0;
            for ($d = (1 - $offset); $d <= $daysInMonth; $d++) {
                if ($i % 7 == 0) {
                    echo "<tr>";
                }
                if ($d < 1) {
                    echo "<td class=\"nonMonthDay\"> </td>";
                } else {
                    if ($d == $day) {
                        echo "<td class=\"monthToday\">";
                    } else {
                        echo "<td class=\"monthDay\">";
                    }
                    if ($type == 'big') {
                        // add the add cal date link
                        if (checkAccess($_SESSION['login_id']) <= 5) {
                            echo "<a class=\"add\" href=\"?add=$year-$month-$d\">"
                            . $LANG['add']
                            . "</a>";
                        }
                        // display the day #
                        echo "<a href=\"?year=$year&amp;month=$month&amp;day=$d&amp;view=day\">";
                        echo "$d</a>";
                    }
                    // display the events for each day
                    if (in_array($d, $eventDays)) {
                        // for big cal we display the calendar entries
                        if ($type == 'big') {
                            $this->displayEvents($month, $d, $year);
                            
                        // for small cal we just display a link to that day (day view)
                        } else {
                            echo "<a href=\"?year=$year&amp;month=$month&amp;day=$d\">$d</a>";
                        }
                    } else {
                        // small cal just display the day #
                        if ($type !== 'big') {
                            echo $d;
                        }
                    }
                    echo "</td>";
                }
                $i++;
                // if we have 7 <td> for the current week close the <tr>
                if ($i % 7 == 0) {
                    echo "</tr>\n\t\t\t";
                }
            }
            // close any opening <tr> and insert any additional empty <td>
            if ($i % 7 != 0) {
                for ($j = 0; $j < (7 - ($i % 7)); $j++) {
                    echo "<td class=\"nonMonthDay\"> </td>";
                }
                echo "</tr>\n";
            }
            if ($type == 'big') {
                echo $actionsToolbar;
            }
            
        // Day View
        } elseif ($view == 'day') {
            // Timestamp of previous day
            $prevTS = strtotime("$year-$month-$day -1 day");
            
            // Timestamp of next day
            $nextTS = strtotime("$year-$month-$day +1 day");
            
            // Start the header row
            echo "<tr>";
            
            // Display prev/today/next links
            echo '<th colspan="2">';
            
            // Previous
            list($y, $m, $d) = explode('-', date('Y-m-d', $prevTS));
            // if previous month is 12 then we need to subtract a year
            if ($m == 12) {
                echo "<a class=\"prev\" href=\"?year=";
                echo $year-1;
                echo "&amp;month=$m&amp;day=$d&amp;view=day\">".$LANG['prev']."</a>";
            } else {
                echo "<a class=\"prev\" href=\"?year=$year&amp;month=$m&amp;day=$d&amp;view=day\">"
                    . $LANG['prev'] . "</a>";
            }
            
            // Today
            echo '<a class="today" href="?year=';
            echo date('Y');
            echo "&amp;month=";
            echo date('m');
            echo "&amp;day=";
            echo date('d');
            echo '">';
            echo $LANG['today'] . '</a> ';
            
            // Next
            list($y, $m, $d) = explode('-', date('Y-m-d', $nextTS));
            echo "<a class=\"next\" href=\"?year=$y&amp;month=$m&amp;day=$d&amp;view=day\">"
                . $LANG['next'] . "</a></th>";
                
            // Display Month Name
            echo "<th colspan=\"3\"><h3>" . date('l, ', strtotime("$year-$month-$day"));
            $monthName = date('F', strtotime("$year-$month-$day"));
            echo getLangMonthName($monthName);
            echo date(' j, Y', strtotime("$year-$month-$day"));
            echo "</h3></th>";
            
            // Display the view toolbar
            echo $viewToolbar;
            
            // Close the header row
            echo "</tr>\n\t\t\t";
            
            // Display Calendar Events for this day
            echo '<tr><td class="day_view_info" colspan="7">';
            $this->displayEvents($month, $day, $year, true);
            echo "</td></tr>\n\t\t\t";
            echo $actionsToolbar;
        }
        
        // close the table, the calendar is finished
        echo "\t\t</table>\n\t\t";
    }

    function displayMonthEvents ($month, $year)
    {
        global $LANG;
        $monthName = date("F", mktime(0,0,0,$month,1,2006));
        echo "<h3>".$LANG[$monthName].":</h3>";
        $sql = "SELECT *, "
             . "SUBSTRING(`date`, 9, 2) AS o FROM fcms_calendar "
             . "WHERE (`date` LIKE '$year-$month-%%' AND `type` = 'Other') "
             . "OR (`date` LIKE '%%%%-$month-%%' AND `type` != 'Other') "
             . "ORDER BY o";
        $this->db->query($sql) or displaySQLError(
            'Events Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        if ($this->db->count_rows() > 0) {
            while ($row = $this->db->get_row()) {
                $show = false;
                if ($row['private'] == 0) {
                    $show = true;
                } else {
                    if ($row['created_by'] == $this->cur_user_id) {
                        $show = true;
                    }
                }
                if ($show) {
                    echo "<div class=\"events\">";
                    echo date('d', strtotime($row['date']));
                    if (!empty($row['desc'])) {
                        $desc = "<dfn title=\""
                        . htmlentities($row['desc'], ENT_COMPAT, 'UTF-8')
                        . "\">".$row['title']."</dfn>";
                    } else {
                        $desc = $row['title'];
                    }
                    switch ($row['type']) {
                        case 'Birthday':
                            echo " - <span class=\"bday\">$desc</span></div>";
                            break;
                        case 'Anniversary':
                            echo " - <span class=\"anniversary\">$desc</span></div>";
                            break;
                        default:
                            echo " - <span class=\"holiday\">$desc</span></div>";
                            break;
                    }
                }
            }
            echo "\n\t\t";
        } else {
            echo "<div class=\"events\"><i>" . $LANG['no_events'] . "</i></div>\n\t\t";
        }
    }

    function displayTodaysEvents ($month, $day, $year)
    {
        global $LANG;
        $sql = "SELECT * "
             . "FROM fcms_calendar "
             . "WHERE (`date` LIKE '$year-$month-$day' AND `type` = 'Other') "
             . "OR (`date` LIKE '%%%%-$month-$day' AND `type` != 'Other')";
        $this->db->query($sql) or displaySQLError(
            'Today Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        if ($this->db->count_rows() > 0) {
            $first = true;
            while ($row = $this->db->get_row()) {
                $show = false;
                if ($row['private'] == 0) {
                    $show = true;
                } else {
                    if ($row['created_by'] == $this->cur_user_id) {
                        $show = true;
                    }
                }
                // Print the rounded box beginning part
                if ($first & $show) {
                    echo "\n\t\t\t<div>"
                    . '<b class="rounded-box"><b class="rounded-box1"><b></b></b>'
                    . '<b class="rounded-box2"><b></b></b><b class="rounded-box3"></b>'
                    . '<b class="rounded-box4"></b><b class="rounded-box5"></b></b>'
                    . "\n\t\t\t" . '<div class="rounded-boxfg">'
                    . "\n\t\t\t<h2>".$LANG['todays_events'].":</h2>";
                    $first = false;
                }
                if ($show) {
                    echo "<div class=\"events\">";
                    switch($row['type']) {
                        case 'Birthday':
                            echo " - <span class=\"bday\">".$row['title']."</span> ";
                            if (!empty($row['desc'])) {
                                echo "<br/>".$row['desc'];
                            }
                            echo "</div>";
                            break;
                        case 'Anniversary':
                            echo " - <span class=\"anniversary\">".$row['title']."</span> ";
                            if (!empty($row['desc'])) {
                                echo "<br/>".$row['desc'];
                            }
                            echo "</div>";
                            break;
                        default:
                            echo " - <span class=\"holiday\">".$row['title']."</span> ";
                            if (!empty($row['desc'])) {
                                echo $row['desc'];
                            }
                            echo "</div>";
                            break;
                    }
                }
            }
            // Print the rounded box ending part
            if (!$first) {
                echo "</div>\n\t\t\t"
                . '<b class="rounded-box"><b class="rounded-box5"></b>'
                . '<b class="rounded-box4"></b><b class="rounded-box3"></b>'
                . '<b class="rounded-box2"><b></b></b><b class="rounded-box1">'
                . '<b></b></b></b></div>';
            }
        }
    }

    function displayEvents ($month, $day, $year, $showDesc = false)
    {
        $day = str_pad($day, 2, 0, STR_PAD_LEFT);
        $sql = "SELECT * "
            . "FROM fcms_calendar "
            . "WHERE (`date` LIKE '$year-$month-$day' AND `type` = 'Other') "
            . "OR (`date` LIKE '%%%%-$month-$day' AND `type` != 'Other')";
        $this->db->query($sql) or displaySQLError(
            'Events Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo '<div class="entries"><ul>';
            while ($row = $this->db->get_row()) {
                $show = false;
                if ($row['private'] == 0) {
                    $show = true;
                } else {
                    if ($row['created_by'] == $this->cur_user_id) {
                        $show = true;
                    }
                }
                if ($show) {
                    // Show The Description
                    if ($showDesc) {
                        echo '<li class="' . $row['type'] . '">';
                        if (
                            checkAccess($this->cur_user_id) < 2 || 
                            $this->cur_user_id == $row['created_by']
                        ) {
                            echo '<a href="?edit=' . $row['id'] . '">' . $row['title'];
                            echo '<span>'. $row['desc'] . '</span></a>';
                        } else {
                            echo '<a href="entry=' . $row['id'] . '">' . $row['title'];
                            echo '<span>' . $row['desc'] . '</span></a>';
                        }
                        echo '</li>';
                    // No description
                    } else {
                        echo '<li class="' . $row['type'] . '">';
                        if (
                            checkAccess($this->cur_user_id) < 2 || 
                            $this->cur_user_id == $row['created_by']
                        ) {
                            echo '<a title="' . htmlentities($row['desc'], ENT_COMPAT, 'UTF-8');
                            echo '" href="?edit=' . $row['id'] . '">' . $row['title'] . '</a>';
                        } else {
                            echo '<a title="' . htmlentities($row['desc'], ENT_COMPAT, 'UTF-8');
                            echo '" href="?entry=' . $row['id'] . '">' . $row['title'] . '</a>';
                        }
                        echo "</li>";
                    }
                }
            }
            echo '</ul></div>';
        }
    }

    /**
     * displayForm
     * 
     * Displays the Form to add a new entry or edit an existing one.
     * Also displays the entry information, (used for viewing entry details that 
     * the user doesn't have edit access to).
     *
     * @param   $type   for add, it's the date you want to add, or 'edit' or 'show'
     * @param   $id     used for edit and show only, then entry id
     */
    function displayForm ($type, $id = '0')
    {
        global $LANG;
        $sql = "SELECT * FROM fcms_calendar WHERE id = $id";
        $this->db->query($sql) or displaySQLError(
            'Date Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $show = false;
        if ($row['private'] == 0) {
            $show = true;
        } else {
            if ($row['created_by'] == $this->cur_user_id) {
                $show = true;
            }
        }
        if ($show) {
            // edit or show
            if ($type == 'show' || $type == 'edit') {
                $year = substr($row['date'], 0,4); 
                $month = substr($row['date'], 5,2); 
                $day = substr($row['date'], 8,2);
            // add
            } else {
                $year = substr($type, 0,4);
                $month = substr($type, 5,2);
                $day = substr($type, 8,2);
            }
            if (checkAccess($this->cur_user_id) < 2 || 
                $this->cur_user_id == $row['created_by'] || 
                $type !== 'edit'
            ) {
                echo "<fieldset><legend>";
                if ($type == 'edit') {
                    echo $LANG['edit'];
                } else {
                    echo $LANG['add'];
                }
                echo " ".$LANG['cal_entry']."</legend>\n\t\t\t";
                if ($type != 'show') {
                    echo "<form id=\"frm\" method=\"post\" action=\"calendar.php\">\n\t\t\t\t";
                }
                echo "<div class=\"field-row clearfix\"><div class=\"field-label\">";
                echo "<label for=\"title\"><b>" . $LANG['title'] . "</b></label></div>";
                echo "<div class=\"field-widget\">";
                if ($type == 'show') {
                    echo $row['title'] . "</div></div>\n\t\t\t\t";
                } else {
                    echo "<span><input type=\"text\" ";
                    echo "class=\"required\" id=\"title\" name=\"title\" size=\"40\"";
                    if ($type == 'edit') {
                        echo " value=\"" . htmlentities($row['title'], ENT_COMPAT, 'UTF-8')  . "\"";
                    }
                    echo "/></span></div></div>\n\t\t\t\t";
                    echo "<script type=\"text/javascript\">\n\t\t\t\t\t";
                    echo "var ftitle = new LiveValidation('title', { validMessage: \"";
                    echo $LANG['lv_thanks'] . "\", wait: 500});\n\t\t\t\t\t";
                    echo "ftitle.add(Validate.Presence, {failureMessage: \"";
                    echo $LANG['lv_sorry_req'] . "\"});\n\t\t\t\t</script>\n\t\t\t\t";
                }
                echo "<div class=\"field-row clearfix\"><div class=\"field-label\">";
                echo "<label for=\"desc\"><b>".$LANG['desc']."</b></label></div>";
                echo "<div class=\"field-widget\">";
                if ($type == 'show') {
                    echo $row['desc'] . "</div></div>\n\t\t\t\t";
                } else {
                    echo "<span><input type=\"text\" id=\"desc\" ";
                    echo "name=\"desc\" size=\"50\"";
                    if ($type == 'edit') {
                        echo " value=\"" . htmlentities($row['desc'], ENT_COMPAT, 'UTF-8') . "\"";
                    }
                    echo "/></span></div></div>\n\t\t\t\t";
                }
                echo "<div class=\"field-row clearfix\"><div class=\"field-label\">";
                echo "<label for=\"sday\"><b>" . $LANG['sdate'] . "</b></label></div>";
                echo "<div class=\"field-widget\">";
                if ($type == 'show') {
                    echo "$year / $month / $day</div></div>\n\t\t\t\t";
                } else {
                    echo "<select id=\"sday\" name=\"sday\">";
                    $d = 1;
                    while ($d <= 31) {
                        if ($day == $d) {
                            echo "<option value=\"$d\" selected=\"selected\">$d</option>";
                        } else {
                            echo "<option value=\"$d\">$d</option>";
                        }
                        $d++;
                    }
                    echo '</select><select name="smonth">';
                    $m = 1;
                    while ($m <= 12) {
                        if ($month == $m) {
                            echo "<option value=\"$m\" selected=\"selected\">"
                                . date('M', mktime(0, 0, 0, $m, 1, 2006))
                                . "</option>";
                        } else {
                            echo "<option value=\"$m\">"
                                . date('M', mktime(0, 0, 0, $m, 1, 2006))
                                . "</option>";
                        }
                        $m++;
                    }
                    echo '</select><select name="syear">';
                    $y = 1900;
                    while ($y - 5 <= date('Y')) {
                        if ($year == $y) {
                            echo "<option value=\"$y\" selected=\"selected\">$y</option>";
                        } else {
                            echo "<option value=\"$y\">$y</option>";
                        }
                        $y++;
                    } 
                    echo "</select></div></div>\n\t\t\t\t";
                }
                echo "<div class=\"field-row clearfix\"><div class=\"field-label\">";
                echo "<label for=\"type\"><b>" . $LANG['type'] . "</b></label></div>";
                echo "<div class=\"field-widget\">";
                if ($type == 'show') {
                    echo $row['type'] . "</div></div>\n\t\t\t\t";
                } else {
                    echo "<select id=\"type\" name=\"type\"><option value=\"Anniversary\"";
                    if ($row['type'] == 'Anniversary') {
                        echo " selected=\"selected\"";
                    }
                    echo ">" . $LANG['anniversary_rpt'] . "</option><option value=\"Birthday\"";
                    if ($row['type'] == 'Birthday') {
                        echo " selected=\"selected\"";
                    }
                    echo ">" . $LANG['birthday_rpt'] . "</option><option value=\"Holiday\"";
                    if ($row['type'] == 'Holiday') {
                        echo " selected=\"selected\"";
                    }
                    echo ">" . $LANG['holiday_rpt'] . "</option><option value=\"Other\"";
                    if ($row['type'] == 'Other') {
                        echo " selected=\"selected\"";
                    }
                    echo ">" . $LANG['other_one'] . "</option></select></div></div>\n\t\t\t\t";
                }
                echo "<div class=\"field-row clearfix\"><div class=\"field-label\">";
                echo "<label for=\"private\"><b>" . $LANG['private'] . "</b></label></div>";
                echo "<div class=\"field-widget\">";
                if ($type == 'show') {
                    if ($row['private'] == 1) {
                        echo $LANG['yes'] . "</div></div>\n\t\t\t\t";
                    } else {
                        echo $LANG['no'] . "</div></div>\n\t\t\t\t";
                    }
                } else {
                    echo "<input type=\"checkbox\" name=\"private\" id=\"private\"";
                    if ($row['private'] == 1) {
                        echo " checked=\"checked\"";
                    }
                    echo "/></div></div>\n\t\t\t\t";
                }
                if ($type == 'show') {
                    echo "<p><a href=\"calendar.php?year=$year&amp;month=$month&amp;day=$day\">";
                    echo $LANG['cancel'] . "</a></p>";
                } else {
                    echo "<p><input type=\"hidden\" name=\"id\" value=\"" . $row['id'] . "\"/>";
                    echo "<input type=\"submit\" ";
                    if ($type == 'edit') {
                        echo "name=\"edit\" value=\"" . $LANG['edit'] . "\" "
                            . "title=\"" . $LANG['edit_cal'] . "\"/> ";
                    } else {
                        echo "name=\"add\" value=\"" . $LANG['add'] . "\" "
                            . "title=\"".$LANG['add_cal']."\"/> ";
                    }
                    if ($type == 'edit') {
                        echo "<input type=\"submit\" name=\"delete\" value=\""
                            . $LANG['delete'] . "\" title=\"" . $LANG['delete_cal']
                            . "\" onclick=\"javascript:return confirm('" 
                            . $LANG['js_delete_cal'] . "');\"/>";
                    }
                    echo "</p>\n\t\t\t</form>\n\t\t\t</fieldset>\n";
                }
                return false;
            } else {
                echo "<p class=\"error-alert\">" . $LANG['err_no_edit_cal'] . "</p>";
                return true;
            }
        } else {
            echo "<p class=\"error-alert\">" . $LANG['err_private_cal'] . "</p>";
            return true;
        }
    }

    /**
     * exportCalendar
     * 
     * Exports all calendar entries in .iCalendar (.ico) format
     */
    function exportCalendar ()
    {
        // http://tools.ietf.org/html/rfc2445#section-4.6
        
        $cal = "BEGIN:VCALENDAR\nPRODID:-//Family Connections//EN\nVERSION:2.0\n";
        $sql = "SELECT `date` , `date_added` , `title` , `desc` , "
                . "CONCAT( `fname` , ' ', `lname` ) AS 'organizer', `type` , `private` "
             . "FROM `fcms_calendar` AS c, `fcms_users` AS u "
             . "WHERE c.`created_by` = u.`id";
        $this->db->query($sql) or displaySQLError(
            'Calendar Entries Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        if ($this->db->count_rows() > 0) {
            while ($r = $this->db->get_row()) {
                $cal .= "BEGIN:VEVENT\n";
                $cal .= "DTSTART:" . date('Ymd', strtotime($r['date'])) . "\n";
                $cal .= "SUMMARY:" . $r['title'] . "\n";
                // If description is over 30 characters long, do iCal folding technique
                $desc = $r['desc'];
                $desc = wordwrap($desc, 30, "\n  ");
                $cal .= "DESCRIPTION:$desc\n";
                if ($r['private'] > 0) {
                    $cal .= "CLASS:PRIVATE\n";
                }
                if ($r['date_added'] != '0000-00-00 00:00:00') {
                    // datetime must be 20080609T152552Z format
                    $cal .= "CREATED:" . date('Ymd\THis\Z', strtotime($r['date_added'])) . "\n";
                }
                $cal .= "CATEGORIES:" . strtoupper($r['type']) . "\n";
                if ($r['type'] == 'Anniversary' || 
                    $r['type'] == 'Birthday' || 
                    $r['type'] == 'Holiday') {
                    $cal .= "RRULE:FREQ=YEARLY\n";
                }
                $cal .= "ORGANIZER:" . $r['organizer'] . "\n";
                $cal .= "END:VEVENT\n";
            }
        } else {
            // Calendar is empty
        }
        $cal .= "END:VCALENDAR";
        return $cal;
    }

    /**
     * importCalendar
     * 
     * Imports .iCalendar (.ico) format files into the calendar.
     *
     * @param   $file   the .ico file
     */
    function importCalendar ($file)
    {
        // Read in the file and parse the data to an array or arrays
        $row = file($file);
        $i = 0;
        $foundEvent = false;
        $events = array();
        foreach ($row as $r) {
            $pos = strpos($r, "BEGIN:VEVENT");
            if ($pos !== false) {
                $foundEvent = true;
            }
            if ($foundEvent === true) {
                $tag = strpos($r, ":");
                $name = substr($r, 0, $tag);
                $events[$i][$name] = substr($r, $tag+1);
            }
            $pos = strpos($r, "END:VEVENT");
            if ($pos !== false) {
                $foundEvent = false;
                $i++;
            }
        }
        
        // Loop through the multidimensional array and insert valid event data into db
        foreach ($events as $event) {
            $sql = "INSERT INTO `fcms_calendar` ("
                    . "`date`, `date_added`, `title`, `desc`, `created_by`, `type`, `private`"
                    . ") "
                 . "VALUES (";
            // date
            if (isset($event['DTSTART;VALUE=DATE'])) {
                $sql .= "'" . date('Y-m-d', strtotime($event['DTSTART;VALUE=DATE'])) . "', ";
            } elseif (isset($event['DTSTART'])) {
                $sql .= "'" . date('Y-m-d', strtotime($event['DTSTART'])) . "', ";
            } else {
                $sql .= "'0000-00-00', ";
            }
            // date_added
            $sql .= "'" . date('Y-m-d H:i:s', strtotime($event['CREATED'])) . "', ";
            // title
            $sql .= "'" . addslashes($event['SUMMARY']) . "', ";
            // description
            if (isset($event['DESCRIPTION'])) {
                $sql .= "'" . addslashes($event['DESCRIPTION']) . "', ";
            } else {
                $sql .= "NULL, ";
            }
            // created_by
            $sql .= "'" . $this->cur_user_id . "', ";
            // type
            if (isset($event['CATEGORIES'])) {
                if (preg_match('/(ANNIVERSARY)/i', $event['CATEGORIES'])) {
                    $sql .= "'Anniversary', ";
                } elseif (preg_match('/(BIRTHDAY)||(BDAY)||(B\-DAY)/i', $event['CATEGORIES'])) {
                    $sql .= "'Birthday', ";
                } elseif (isset($event['RRULE:FREQ=YEARLY'])) {
                    $sql .= "'Holiday', ";
                } else {
                    $sql .= "'Other', ";
                }
            } else {
                $sql .= "'Other', ";
            }
            // private
            if (isset($event['CLASS'])) {
                if ($event['CLASS'] == 'PRIVATE') {
                    $sql .= "'1'";
                } else {
                    $sql .= "'0'";
                }
            } else {
                $sql .= "'0'";
            }
            $sql .= ")";
            $this->db->query($sql) or displaySQLError(
                'Import Entries Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
        }
    }
    
    /**
     * displayImportForm
     * 
     * Displays the form used to import iCalendar compatible files.
     */
    function displayImportForm ()
    {
        global $LANG;
        echo "<h2>" . $LANG['import'] . "</h2>\n\t\t\t<br/>\n\t\t\t";
        echo "<form enctype=\"multipart/form-data\" id=\"frm\" method=\"post\" ";
        echo "action=\"calendar.php\">\n\t\t\t\t";
        echo "<p><input type=\"file\" id=\"file\" name=\"file\"/></p>\n\t\t\t\t";
        echo "<p><input type=\"submit\" name=\"import\" value=\"" . $LANG['import'] . "\"/></p>";
    }

    /**
     * displayWhatsNewCalendar
     * 
     * Returns the last 5 added calendar entries in the current month
     */
    function displayWhatsNewCalendar ()
    {
        global $LANG;
        $today = date('Y-m-d');
        $tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
        $sql = "SELECT * "
             . "FROM `fcms_calendar` "
             . "WHERE `date_added` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) "
             . "AND `private` < 1 "
             . "ORDER BY `date_added` DESC LIMIT 0, 5";
        $this->db->query($sql) or displaySQLError(
            'What\'s New Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        if ($this->db->count_rows() > 0) {
            echo "\n\t\t\t\t<h3>" . $LANG['link_calendar'] . "</h3>\n\t\t\t\t";
            echo "<ul>\n";
            while ($r = $this->db->get_row()) {
                $title = $r['title'];
				$displayname = getUserDisplayName($r['created_by']);
                $monthName = gmdate('M', strtotime($r['date_added'] . $this->tz_offset));
                $date_added = fixDST(
                    gmdate('n/j/Y g:i a', strtotime($r['date_added'] . $this->tz_offset)), 
                    $this->cur_user_id, '. j, Y, g:i a'
                );
                if (
                    strtotime($r['date_added']) >= strtotime($today) && 
                    strtotime($r['date_added']) > $tomorrow
                ) {
                    $full_date = $LANG['today'];
                    $d = ' class="today"';
                } else {
                    $full_date = getLangMonthName($monthName) . $date_added;
                    $d = '';
                }
                echo "\t\t\t\t\t<li><div$d>$full_date</div>";
                echo "<a href=\"calendar.php?year=" . date('Y', strtotime($r['date']))
                . "&amp;month=" . date('m', strtotime($r['date'])) . "&amp;day="
                . date('d', strtotime($r['date'])) . "\">$title (" 
                . date('n/j/Y', strtotime($r['date']))
                . ")</a> - <a class=\"u\" href=\"profile.php?member="
                . $r['created_by'] . "\">$displayname</a></li>\n";
            }
            echo "\t\t\t\t</ul>\n";
        }
    }

}
?>
