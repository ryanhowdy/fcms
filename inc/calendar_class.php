<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

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
        $locale = new Locale();
        $viewToolbar = '
                    <th class="view_toolbar" colspan="2">
                        <a class="day" href="?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;view=day">'._('Day').'</a> | 
                        <a class="month" href="?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'">'._('Month').'</a>
                    </th>';
        $actionsToolbar = '
                <tr class="actions_toolbar">
                    <td colspan="7">
                        '._('Actions').': 
                        <a class="print" href="#" 
                            onclick="window.open(\'inc/calendar_print.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'\',
                            \'name\',\'width=700,height=400,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no\'); 
                            return false;">'._('Print').'</a> | 
                        <a href="?import=true">'._('Import').'</a> | 
                        <a href="?export=true">'._('Export').'</a>
                    </td>
                </tr>';
        echo '
            <table id="'.$type.'_calendar">';
        
        // Month View
        if ($view == 'month') {
            $first = mktime(0,0,0,$month,1,$year);
            $offset = date('w', $first);
            $daysInMonth = date('t', $first);
            $monthName = date('F', $first);
            if ($type == 'big') {
                $weekDays = $locale->getDayNames();
            } else {
                $weekDays = $locale->getDayInitials();
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
            echo '
                <tr>';
            
            // Don't display the today link or views toolbar for the small calendar
            $colspan = ' colspan="2"';
            if ($type == 'small') {
                $colspan = ' colspan="7"';
            }
            
            // Display prev/today/next links
            echo '
                    <th'.$colspan.'>';

            // Previous
            list($y, $m) = explode('-', date('Y-m', $prevTS));
            // if previous month is 12 then we need to subtract a year
            if ($m == 12) {
                echo "<a class=\"prev\" href=\"?year=";
                echo $year-1;
                echo "&amp;month=$m&amp;day=$pDay\">"._('Previous')."</a> ";
            } else {
                echo "<a class=\"prev\" href=\"?year=$year&amp;month=$m&amp;day=$pDay\">"._('Previous')."</a> ";
            }
            
            // Today
            if ($type == 'big') {
                echo '<a class="today" href="?year='.date('Y')."&amp;month=".date('m')."&amp;day=".date('d').'">'._('Today').'</a> ';
            }
            
            // Next
            list($y, $m) = explode('-', date('Y-m', $nextTS));
            echo "<a class=\"next\" href=\"?year=$y&amp;month=$m&amp;day=$nDay\">"._('Next')."</a>";
                
            // Display Month Name
            if ($type == 'big') {
                echo '</th>
                    <th colspan="3"><h3>'.$locale->fixDate('F', '', date('F',$first)).' '.$year.'</h3></th>';
                
                // Display the view toolbar
                echo $viewToolbar;
            } else {
                echo '<h3>'.$locale->fixDate('F', '', date('F',$first)).' '.$year.'</h3></th>';
            }
            
            // Close the header row
            echo '
                </tr>';
            
            // Display the weekday names
            echo '
                <tr>';
            foreach ($weekDays as $wd) {
                echo '
                    <td class="weekDays">'.$wd.'</td>';
            }
            echo '
                </tr>';
            
            // Display the days in the month, fill with events
            $i = 0;
            for ($d = (1 - $offset); $d <= $daysInMonth; $d++) {
                if ($i % 7 == 0) {
                    echo '
                <tr>';
                }
                if ($d < 1) {
                    echo '
                    <td class="nonMonthDay">&nbsp;</td>';
                } else {
                    if ($d == $day) {
                        echo '
                    <td class="monthToday">';
                    } else {
                        echo '
                    <td class="monthDay">';
                    }
                    if ($type == 'big') {
                        // add the add cal date link
                        if (checkAccess($_SESSION['login_id']) <= 5) {
                            echo '<a class="add" href="?add='.$year.'-'.$month.'-'.$d.'">'._('Add').'</a>';
                        }
                        // display the day #
                        echo '<a href="?year='.$year.'&amp;month='.$month.'&amp;day='.$d.'&amp;view=day">'.$d.'</a>';
                    }
                    // display the events for each day
                    if (in_array($d, $eventDays)) {
                        // for big cal we display the calendar entries
                        if ($type == 'big') {
                            $this->displayEvents($month, $d, $year);
                            
                        // for small cal we just display a link to that day (day view)
                        } else {
                            echo '<a href="?year='.$year.'&amp;month='.$month.'&amp;day='.$d.'">'.$d.'</a>';
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
                    echo '
                </tr>';
                }
            }
            // close any opening <tr> and insert any additional empty <td>
            if ($i % 7 != 0) {
                for ($j = 0; $j < (7 - ($i % 7)); $j++) {
                    echo '
                    <td class="nonMonthDay">&nbsp;</td>';
                }
                echo '
                </tr>';
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
            echo '
                <tr>';
            
            // Display prev/today/next links
            echo '
                    <th colspan="2">';
            
            // Previous
            list($y, $m, $d) = explode('-', date('Y-m-d', $prevTS));
            // if previous month is 12 then we need to subtract a year
            if ($m == 12) {
                echo "<a class=\"prev\" href=\"?year=";
                echo $year-1;
                echo "&amp;month=$m&amp;day=$d&amp;view=day\">"._('Previous')."</a>";
            } else {
                echo "<a class=\"prev\" href=\"?year=$year&amp;month=$m&amp;day=$d&amp;view=day\">"._('Previous')."</a>";
            }
            
            // Today
            echo '<a class="today" href="?year='.date('Y').'&amp;month='.date('m').'&amp;day='.date('d').'">'._('Today').'</a> ';
            
            // Next
            list($y, $m, $d) = explode('-', date('Y-m-d', $nextTS));
            echo "<a class=\"next\" href=\"?year=$y&amp;month=$m&amp;day=$d&amp;view=day\">"._('Next')."</a></th>";
                
            // Display Month Name
            echo '
                    <th colspan="3"><h3>'.$locale->fixDate(_('l, F j, Y'), '', "$year-$month-$day").'</h3></th>';
            
            // Display the view toolbar
            echo $viewToolbar;
            
            // Close the header row
            echo '
                </tr>';
            
            // Display Calendar Events for this day
            echo '
                <tr><td class="day_view_info" colspan="7">';
            $this->displayEvents($month, $day, $year, true);
            echo '</td></tr>';
            echo $actionsToolbar;
        }
        
        // close the table, the calendar is finished
        echo '
            </table>';
    }

    function displayMonthEvents ($month, $year)
    {
        $locale = new Locale();
        echo '
                <h3>'.$locale->fixDate('F', '', date("F", mktime(0,0,0,$month,1,2006))).':</h3>';
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
                    echo '
                <div class="events">'.date('d', strtotime($row['date']));
                    if (!empty($row['desc'])) {
                        $desc = "<dfn title=\"".htmlentities($row['desc'], ENT_COMPAT, 'UTF-8')."\">".$row['title']."</dfn>";
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
        } else {
            echo '
                <div class="events"><i>'._('No events for this month.').'</i></div>';
        }
    }

    function displayTodaysEvents ($month, $day, $year)
    {
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
                    echo '
                <div id="todaysevents">
                    <h2>'._('Today\'s Events').':</h2>'.
                    $first = false;
                }
                if ($show) {
                    echo '
                    <div class="events">';
                    switch($row['type']) {
                        case 'Birthday':
                            echo "<span class=\"bday\">".$row['title']."</span> ";
                            if (!empty($row['desc'])) {
                                echo "<br/> - ".$row['desc'];
                            }
                            echo "</div>";
                            break;
                        case 'Anniversary':
                            echo "<span class=\"anniversary\">".$row['title']."</span> ";
                            if (!empty($row['desc'])) {
                                echo "<br/> - ".$row['desc'];
                            }
                            echo "</div>";
                            break;
                        default:
                            echo "<span class=\"holiday\">".$row['title']."</span> ";
                            if (!empty($row['desc'])) {
                                echo "<br/> - ".$row['desc'];
                            }
                            echo "</div>";
                            break;
                    }
                }
            }
            // Print the rounded box ending part
            if (!$first) {
                echo '
                </div>';
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
                            echo '<a href="?entry=' . $row['id'] . '">' . $row['title'];
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
        $locale = new Locale();
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

                //-------------------------------------
                // Setup vars
                //-------------------------------------
                $formStart = '<form id="frm" method="post" action="calendar.php">';
                $date = '<select id="sday" name="sday">';
                $d = 1;
                while ($d <= 31) {
                    if ($day == $d) {
                        $date .= "<option value=\"$d\" selected=\"selected\">$d</option>";
                    } else {
                        $date .= "<option value=\"$d\">$d</option>";
                    }
                    $d++;
                }
                $date .= '</select>
                            <select name="smonth">';
                $m = 1;
                while ($m <= 12) {
                    if ($month == $m) {
                        $date .= "<option value=\"$m\" selected=\"selected\">".$locale->getMonthAbbr($m)."</option>";
                    } else {
                        $date .= "<option value=\"$m\">".$locale->getMonthAbbr($m)."</option>";
                    }
                    $m++;
                }
                $date .= '</select>
                            <select name="syear">';
                $y = 1900;
                while ($y - 5 <= date('Y')) {
                    if ($year == $y) {
                        $date .= "<option value=\"$y\" selected=\"selected\">$y</option>";
                    } else {
                        $date .= "<option value=\"$y\">$y</option>";
                    }
                    $y++;
                } 
                $date .= "</select>";
                $anniversary = ($row['type'] == 'Anniversary') ? 'selected="selected"' : '';
                $birthday = ($row['type'] == 'Birthday') ? 'selected="selected"' : '';
                $holiday = ($row['type'] == 'Holiday') ? 'selected="selected"' : '';
                $other = ($row['type'] == 'Other') ? 'selected="selected"' : '';
                $type_options = '<select id="type" name="type">
                                <option value="Anniversary" '.$anniversary.'>'._('Anniversary (repeats)').'</option>
                                <option value="Birthday" '.$birthday.'>'._('Birthday (repeats)').'</option>
                                <option value="Holiday" '.$holiday.'>'._('Holiday (repeats)').'</option>
                                <option value="Other" '.$other.'>'._('Other').'</option>
                            </select>';
                $private = '<input type="checkbox" name="private" id="private"';
                if ($row['private'] == 1) {
                    $private .= ' checked="checked"';
                }
                $private .= '/>';
                $edit_delete = '<p><input type="hidden" name="id" value="'.$row['id'].'"/>
                            <input class="sub1" type="submit" ';
                if ($type == 'edit') {
                    $edit_delete .= 'name="edit" value="'._('Edit').'"/> ';
                } else {
                    $edit_delete .= 'name="add" value="'._('Add').'"/> ';
                }
                if ($type == 'edit') {
                    $edit_delete .= '<input class="sub2" type="submit" id="delcal" name="delete" value="'._('Delete').'"/>';
                }
                $cancel = _('or').' &nbsp;<a href="calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'">'._('Cancel').'</a></p>';

                // Edit
                if ($type == 'edit') {
                    $legend = _('Edit Calendar Entry');
                    $title = '<input type="text" id="title" name="title" size="40" value="'.htmlentities($row['title'], ENT_COMPAT, 'UTF-8').'">
                            <script type="text/javascript">
                                var ftitle = new LiveValidation(\'title\', { onlyOnSubmit: true});
                                ftitle.add(Validate.Presence, {failureMessage: ""});
                            </script>';
                    $desc = '<input type="text" id="desc" name="desc" size="50" value="'.htmlentities($row['desc'], ENT_COMPAT, 'UTF-8').'">';
                // Show
                } elseif ($type == 'show') {
                    $legend = _('Calendar Entry');
                    $formStart = $formEnd = '';
                    $title = $row['title'];
                    $desc = $row['desc'];
                    $date = "$year / $month / $day";
                    $type_options = $row['type'];
                    $private = _('No');
                    if ($row['private'] == 1) {
                        $private = _('Yes');
                    }
                    $edit_delete = '';
                    $cancel = '<p><a href="calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'">'._('Cancel').'</a></p>';
                // Add
                } else {
                    $legend = _('Add Calendar Entry');
                    $title = '<input type="text" id="title" name="title" size="40">
                            <script type="text/javascript">
                                var ftitle = new LiveValidation(\'title\', { onlyOnSubmit: true});
                                ftitle.add(Validate.Presence, {failureMessage: ""});
                            </script>';
                    $desc = '<input type="text" id="desc" name="desc" size="50">';
                }

                //-------------------------------------
                // Show info
                //-------------------------------------
                echo '
            <fieldset>
                <legend><span>'.$legend.'</span></legend>
                '.$formStart.'
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="title"><b>'._('Title').'</b></label></div>
                        <div class="field-widget">
                            '.$title.'
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="desc"><b>'._('Description').'</b></label></div>
                        <div class="field-widget">
                            '.$desc.'
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="sday"><b>'._('Date').'</b></label></div>
                        <div class="field-widget">
                            '.$date.'
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="type"><b>'._('Type').'</b></label></div>
                        <div class="field-widget">
                            '.$type_options.'
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="private"><b>'._('Private?').'</b></label></div>
                        <div class="field-widget">
                            '.$private.'
                        </div>
                    </div>
                    '.$edit_delete.'
                    '.$cancel.'
                </form>
            </fieldset>';
                return false;
            } else {
                echo '<p class="error-alert">'._('You do not have permission to edit this Calendar Entry.').'</p>';
                return true;
            }
        } else {
            echo '<p class="error-alert">'._('You can not edit this event because it is private.').'</p>';
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
        echo '
            <form enctype="multipart/form-data" method="post" action="calendar.php">
                <fieldset class="add-edit big">
                    <legend>'._('Import').'</legend>
                    <p><input class="frm_file" type="file" id="file" name="file"/></p>
                    <p>
                        <input type="submit" name="import" value="'._('Import').'"/> 
                        '._('or').' &nbsp;
                        <a href="calendar.php">'._('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * displayWhatsNewCalendar
     * 
     * Returns the last 5 added calendar entries in the current month
     */
    function displayWhatsNewCalendar ()
    {
        $locale = new Locale();
        $today = date('Y-m-d');
        $tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
        $sql = "SELECT * 
                FROM `fcms_calendar` 
                WHERE `date_added` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                AND `private` < 1 
                ORDER BY `date_added` DESC LIMIT 0, 5";
        $this->db->query($sql) or displaySQLError(
            'What\'s New Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo '
            <h3>'._('Calendar').'</h3>
            <ul>';
            while ($r = $this->db->get_row()) {
                $title = $r['title'];
                $displayname = getUserDisplayName($r['created_by']);
                $date_added = $locale->fixDate(_('M. j, Y, g:i a'), $this->tz_offset, $r['date_added']);
                if (
                    strtotime($r['date_added']) >= strtotime($today) && 
                    strtotime($r['date_added']) > $tomorrow
                ) {
                    $full_date = _('Today');
                    $d = ' class="today"';
                } else {
                    $full_date = $date_added;
                    $d = '';
                }
                $year = date('Y', strtotime($r['date']));
                $month = date('m', strtotime($r['date']));
                $day = date('d', strtotime($r['date']));
                echo '
                <li>
                    <div'.$d.'>'.$full_date.'</div>
                    <a href="calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'">'.$title.' ('.date('n/j/Y', strtotime($r['date'])).')</a> - 
                    <a class="u" href="profile.php?member='.$r['created_by'].'">'.$displayname.'</a>
                </li>';
            }
            echo '
            </ul>';
        }
    }

}?>
