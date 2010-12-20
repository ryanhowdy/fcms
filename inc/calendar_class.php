<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

/**
 * Calendar 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Calendar
{
    var $db;
    var $currentUserId;

    /**
     * Calendar 
     * 
     * @param  int      $currentUserId 
     * @param  string   $type 
     * @param  string   $host 
     * @param  string   $database 
     * @param  string   $user 
     * @param  string   $pass 
     * @return void
     */
    function Calendar ($currentUserId, $type, $host, $database, $user, $pass)
    {
        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->db = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` 
                FROM `fcms_user_settings` 
                WHERE `user` = '$currentUserId'";
        $this->db->query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
    }

    /**
     * getEventDays 
     * 
     * Gets a list (array) of days that have events for a given month/year
     * 
     * @param   int     $month 
     * @param   int     $year 
     * @return  array   $days
     */
    function getEventDays ($month, $year)
    {
        $month = cleanInput($month, 'int');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = cleanInput($year, 'int');

        $days = array();
        $sql = "SELECT DAYOFMONTH(`date`) as day, `private`, `created_by` 
                FROM `fcms_calendar` 
                WHERE (`date` LIKE '$year-$month-%%') 
                OR (`date` LIKE '%%%%-$month-%%' AND `repeat` = 'yearly') 
                ORDER BY day";
        $this->db->query($sql) or displaySQLError(
            'Events Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            while($r = $this->db->get_row()) {
                if ($r['private'] == 1) {
                    // only the user who created the private event can see it
                    if ($r['created_by'] == $this->currentUserId) {
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
     * displayCalendarMonth 
     * 
     * Displays a month view of the calendar based on the month, day and year.
     *
     * NOTE: Dates are assumed already fixed for timezone and dst.
     * 
     * @param   int     $month 
     * @param   int     $year 
     * @param   int     $day 
     * @return  void
     */
    function displayCalendarMonth ($month, $year, $day)
    {
        $month = cleanInput($month, 'int');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = cleanInput($year, 'int');
        $day   = cleanInput($day, 'int');
        $day   = str_pad($day, 2, 0, STR_PAD_LEFT);

        $locale = new Locale();

        $weekDays   = $locale->getDayNames();
        $categories = $this->getCategories();
        $eventDays  = $this->getEventDays($month, $year);
        
        // First day of the month starts on?
        $first = mktime(0,0,0,$month,1,$year);
        $offset = date('w', $first);

        $daysInMonth = date('t', $first);
        
        // Previous month links
        $prevTS = strtotime("$year-$month-01 -1 month");
        // Make sure previous day is less than the total num of days in prev month
        $pDay = ($day > date('t', $prevTS)) ? date('t', $prevTS) : $day;
        list($pYear, $pMonth) = explode('-', date('Y-m', $prevTS));

        // Today links
        $tYear  = $locale->fixDate('Y', $this->tz_offset, gmdate('Y-m-d H:i:s'));
        $tMonth = $locale->fixDate('m', $this->tz_offset, gmdate('Y-m-d H:i:s'));
        $tDay   = $locale->fixDate('d', $this->tz_offset, gmdate('Y-m-d H:i:s'));

        // Next month links
        $nextTS = strtotime("$year-$month-01 +1 month");
        // Make sure next day is less than the total num of days in next month
        $nDay = ($day > date('t', $nextTS)) ? date('t', $nextTS) : $day;
        list($nYear, $nMonth) = explode('-', date('Y-m', $nextTS));

        // Display the month
        echo '
            <table id="big-calendar" cellpadding="0" cellspacing="0">
                <tr>
                    <th colspan="2">
                        <a class="prev" href="?year=' . $pYear . '&amp;month=' . $pMonth . '&amp;day=' . $pDay . '">'
                            . T_('Previous') .
                        '</a> 
                        <a class="today" href="?year=' . $tYear . '&amp;month=' . $tMonth . '&amp;day=' . $tDay . '">'
                            . T_('Today') . 
                        '</a> 
                        <a class="next" href="?year=' . $nYear . '&amp;month=' . $nMonth . '&amp;day=' . $nDay . '">'
                            . T_('Next') .
                        '</a>
                    </th>
                    <th colspan="3"><h3>' . $locale->formatDate('F Y', "$year-$month-$day") . '</h3></th>
                    <th class="views" colspan="2">
                        <a class="day" href="?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;view=day">'.T_('Day').'</a> | 
                        <a class="month" href="?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'">'.T_('Month').'</a>
                    </th>
                </tr>
                <tr>';

        // Weekday names
        foreach ($weekDays as $wd) {
            echo '
                    <td class="weekDays">' . $wd . '</td>';
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
                // add the add cal date link
                if (checkAccess($this->currentUserId) <= 5) {
                    echo '<a class="add" href="?add='.$year.'-'.$month.'-'.$d.'">'.T_('Add').'</a>';
                }
                // display the day #
                echo '<a href="?year='.$year.'&amp;month='.$month.'&amp;day='.$d.'&amp;view=day">'.$d.'</a>';
                // display the events for each day
                if (in_array($d, $eventDays)) {
                    $this->displayEvents($month, $d, $year);
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
        echo '
                <tr class="actions">
                    <td style="text-align:left;" colspan="3">
                        <b>'.T_('Categories').'</b><br/>
                        <ul id="category_menu">'.$categories.'
                            <li><a href="?category=add">'.T_('Add Category').'</a></li>
                        </ul>
                    </td>
                    <td colspan="4">
                        '.T_('Actions').': 
                        <a class="print" href="#" 
                            onclick="window.open(\'inc/calendar_print.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'\',
                            \'name\',\'width=700,height=400,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no\'); 
                            return false;">'.T_('Print').'</a> | 
                        <a href="?import=true">'.T_('Import').'</a> | 
                        <a href="?export=true">'.T_('Export').'</a>
                    </td>
                </tr>
            </table>';
    }

    /**
     * displayCalendarDay 
     *
     * Displays the day view of the calendar.
     *
     * NOTE: Dates are assumed already fixed for timezone and dst.
     * 
     * @param   int     $month 
     * @param   int     $year 
     * @param   int     $day 
     * @return  void
     */
    function displayCalendarDay ($month, $year, $day)
    {
        $month = cleanInput($month, 'int');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = cleanInput($year, 'int');
        $day   = cleanInput($day, 'int');
        $day   = str_pad($day, 2, 0, STR_PAD_LEFT);

        $locale = new Locale();

        $categories = $this->getCategories();

        // Previous day links
        $prevTS = strtotime("$year-$month-$day -1 day");
        list($pYear, $pMonth, $pDay) = explode('-', date('Y-m-d', $prevTS));

        // Today links
        $tYear = $locale->fixDate('Y', $this->tz_offset, gmdate('Y-m-d H:i:s'));
        $tMonth = $locale->fixDate('m', $this->tz_offset, gmdate('Y-m-d H:i:s'));
        $tDay = $locale->fixDate('d', $this->tz_offset, gmdate('Y-m-d H:i:s'));
        $isToday = false;
        $header = $locale->formatDate(T_('l, F j, Y'), "$year-$month-$day");
        if ("$year$month$day" === "$tYear$tMonth$tDay") {
            $isToday = true;
            $header = T_('Today');
        }

        // Next day links
        $nextTS = strtotime("$year-$month-$day +1 day");
        list($nYear, $nMonth, $nDay) = explode('-', date('Y-m-d', $nextTS));

        // Display day 
        echo '
            <table id="day-calendar">
                <tr>
                    <th class="header" colspan="2">
                        <div class="navigation">
                            <a class="prev" href="?year=' . $pYear . '&amp;month=' . $pMonth . '&amp;day=' . $pDay . '&amp;view=day">'
                                . T_('Previous') .
                            '</a> 
                            <a class="today" href="?year=' . $tYear . '&amp;month=' . $tMonth . '&amp;day=' . $tDay . '&amp;view=day">'
                                . T_('Today') . 
                            '</a> 
                            <a class="next" href="?year=' . $nYear . '&amp;month=' . $nMonth . '&amp;day=' . $nDay . '&amp;view=day">'
                                . T_('Next') .
                            '</a>
                        </div>
                        <h3>' . $header . '</h3>
                        <div class="views">
                            <a class="day" href="?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;view=day">'.T_('Day').'</a> | 
                            <a class="month" href="?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'">'.T_('Month').'</a>
                        </div>
                    </th>
                </tr>';

        // Get Events
        $allDayEvents = array();
        $timeEvents = array();
        $sql = "SELECT c.`id`, c.`date`, c.`time_start`, c.`time_end`, c.`date_added`, 
                    c.`title`, c.`desc`, c.`created_by`, c.`private`, c.`repeat`, 
                    ca.`name` AS 'category', ca.`color` 
                FROM `fcms_calendar` AS c, `fcms_category` AS ca  
                WHERE 
                    (
                        (c.`date` LIKE '$year-$month-$day') 
                        OR (c.`date` LIKE '%%%%-$month-$day' AND c.`repeat` = 'yearly')
                    )
                    AND c.`category` = ca.`id`
                ORDER BY c.`time_start`";
        $this->db->query($sql) or displaySQLError(
            'Events Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            while ($row = $this->db->get_row()) {
                if (empty($row['time_start'])) {
                    $allDayEvents[] = $row;
                } else {

                    list($hour, $min, $sec) = explode(':', $row['time_start']);

                    // multiple events for this hour?
                    if (array_key_exists($hour, $timeEvents)) {
                        $singleEvent = $timeEvents[$hour];
                        $timeEvents[$hour] = array($singleEvent);
                        array_push($timeEvents[$hour], $row);

                    // nope
                    } else {
                        $timeEvents[$hour] = $row;
                    }
                }
            }
        }

        // All Day Events
        echo '
                <tr>
                    <td class="all-day"></td>
                    <td class="time-event-data">';

        foreach($allDayEvents AS $event) {
            echo '
                        <div class="event">' .
                            '<a class="' . $event['color'] . '" href="?edit=' . $event['id'] . '">' . 
                                cleanOutput($event['title']) . 
                                '<span>' . cleanOutput($event['desc']) . '</span>' .
                            '</a>' .
                        '</div>';
        }

        echo '
                    </td>
                </tr>';


        // Time Specific Events
        $times = $this->getTimesList();
        $curTime = $locale->fixDate('Hi', $this->tz_offset, date('Y-m-d H:i:s')) . "00";
        
        foreach($times AS $key => $val) {

            list($hour, $min, $sec) = explode(':', $key);

            // Only show hours
            if ($min == 30) { continue; }

            $class = '';
            // Show time past, for today only
            if ($isToday) {
                if ($curTime > $hour.$min.$sec) {
                    $class = 'past';
                }
            }

            // Shrink hours 12:00am (0) through 7:00am (7)
            if ($hour < 8) {
                if ($hour == 0) {
                    echo '
                <tr>
                    <td class="time ' . $class . '">' . T_('12:00 am - 7:00 am') . '</td>
                    <td class="time-event-data">';
                }

                $this->displayTimeEvents($timeEvents, $hour);

                if ($hour == 7) {
                    echo '
                    </td>
                </tr>';
                }

            // Regular times greater than 7:00am
            } else {
                echo '
                <tr>
                    <td class="time ' . $class . '">' . $val . '</td>
                    <td class="time-event-data">';

                $this->displayTimeEvents($timeEvents, $hour);

                echo '
                    </td>
                </tr>';
            }
        }

        echo '
                <tr class="actions">
                    <td style="text-align:left;">
                        <b>'.T_('Categories').'</b><br/>
                        <ul id="category_menu">'.$categories.'
                            <li><a href="?category=add">'.T_('Add Category').'</a></li>
                        </ul>
                    </td>
                    <td>
                        '.T_('Actions').': 
                        <a class="print" href="#" 
                            onclick="window.open(\'inc/calendar_print.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'\',
                            \'name\',\'width=700,height=400,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no\'); 
                            return false;">'.T_('Print').'</a> | 
                        <a href="?import=true">'.T_('Import').'</a> | 
                        <a href="?export=true">'.T_('Export').'</a>
                    </td>
                </tr>
            </table>';
    }

    /**
     * displaySmallCalendar 
     * 
     * Displays a small calendar based on the month, day and year.
     *
     * NOTE: Dates are assumed already fixed for timezone and dst.
     * 
     * @param   int     $month 
     * @param   int     $year 
     * @param   int     $day 
     * @return  void
     */
    function displaySmallCalendar ($month, $year, $day)
    {
        $month = cleanInput($month, 'int');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = cleanInput($year, 'int');
        $day   = cleanInput($day, 'int');
        $day   = str_pad($day, 2, 0, STR_PAD_LEFT);

        $locale = new Locale();

        $weekDays = $locale->getDayInitials();
        $categories = $this->getCategories();
        $eventDays = $this->getEventDays($month, $year);
        
        // First day of the month starts on?
        $first = mktime(0,0,0,$month,1,$year);
        $offset = date('w', $first);

        $daysInMonth = date('t', $first);
        
        // Previous month links
        $prevTS = strtotime("$year-$month-01 -1 month");
        // Make sure previous day is less than the total num of days in prev month
        $pDay = ($day > date('t', $prevTS)) ? date('t', $prevTS) : $day;
        list($pYear, $pMonth) = explode('-', date('Y-m', $prevTS));

        // Today links
        $tYear = $locale->fixDate('Y', $this->tz_offset, gmdate('Y-m-d H:i:s'));
        $tMonth = $locale->fixDate('m', $this->tz_offset, gmdate('Y-m-d H:i:s'));
        $tDay = $locale->fixDate('d', $this->tz_offset, gmdate('Y-m-d H:i:s'));

        // Next month links
        $nextTS = strtotime("$year-$month-01 +1 month");
        // Make sure next day is less than the total num of days in next month
        $nDay = ($day > date('t', $nextTS)) ? date('t', $nextTS) : $day;
        list($nYear, $nMonth) = explode('-', date('Y-m', $nextTS));

        // Display the month
        echo '
            <table id="small-calendar">
                <tr>
                    <th colspan="7">
                        <a class="prev" href="?year=' . $pYear . '&amp;month=' . $pMonth . '&amp;day=' . $pDay . '">'
                            . T_('Previous') .
                        '</a> 
                        <a class="next" href="?year=' . $nYear . '&amp;month=' . $nMonth . '&amp;day=' . $nDay . '">'
                            . T_('Next') .
                        '</a>
                        <h3>' . $locale->formatDate('F Y', "$year-$month-$day") . '</h3>
                    </th>
                </tr>
                <tr>';

        // Weekday names
        foreach ($weekDays as $wd) {
            echo '
                    <td class="weekDays">' . $wd . '</td>';
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
                // display the events for each day
                if (in_array($d, $eventDays)) {
                    echo '<a href="calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$d.'">'.$d.'</a>';
                } else {
                    echo $d;
                }
                // display the day #
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
        echo '
            </table>';
    }

    /**
     * displayMonthEvents 
     * 
     * Displays a listing of events for a given month.
     * Used on the homepage with the small calendar view.
     *
     * @param   int     $month 
     * @param   int     $year 
     * @return  void
     */
    function displayMonthEvents ($month, $year)
    {
        $month = cleanInput($month, 'int');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = cleanInput($year, 'int');

        echo '
                <h3>'.date('F', mktime(0,0,0,$month,1,$year)).':</h3>';

        $sql = "SELECT DAYOFMONTH(`date`) as day, `title`, `desc`, 
                    `date`, `private`, `created_by`
                FROM fcms_calendar 
                WHERE (`date` LIKE '$year-$month-%%') 
                OR (`date` LIKE '%%%%-$month-%%' AND `repeat` = 'yearly') 
                ORDER BY day";
        $this->db->query($sql) or displaySQLError(
            'Events Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            while ($row = $this->db->get_row()) {
                $show = false;
                if ($row['private'] == 0) {
                    $show = true;
                } else {
                    if ($row['created_by'] == $this->currentUserId) {
                        $show = true;
                    }
                }
                if ($show) {
                    $title = !empty($row['desc']) ? $row['desc'] : $row['title'];
                    $title = cleanOutput($title);
                    echo '
                <div class="events">
                    '.date('d', strtotime($row['date'])).' - '
                    . '<dfn title="'.$title.'">'.cleanOutput($row['title']).'</dfn>
                </div>';
                }
            }
        } else {
            echo '
                <div class="events"><i>'.T_('No events for this month.').'</i></div>';
        }
    }

    /**
     * displayTodaysEvents 
     *
     * Display the events happening today.  Used on the homepage.
     * 
     * @param   int     $month 
     * @param   int     $day 
     * @param   int     $year 
     * @return  void
     */
    function displayTodaysEvents ($month, $day, $year)
    {
        $month = cleanInput($month, 'int');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = cleanInput($year, 'int');
        $day   = cleanInput($day, 'int');
        $day   = str_pad($day, 2, 0, STR_PAD_LEFT);

        $sql = "SELECT * 
                FROM fcms_calendar 
                WHERE (`date` LIKE '$year-$month-$day') 
                OR (`date` LIKE '%%%%-$month-$day' AND `repeat` = 'yearly')";
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
                    if ($row['created_by'] == $this->currentUserId) {
                        $show = true;
                    }
                }

                // Start the todaysevents box
                if ($first & $show) {
                    echo '
                <div id="todaysevents">
                    <h2>'.T_('Today\'s Events').':</h2>'.
                    $first = false;
                }

                // Display each event/calendar entry
                if ($show) {
                    echo '
                    <div class="events">
                        <b>' . cleanOutput($row['title']) . '</b>';
                    if (!empty($row['desc'])) {
                        echo '<span>' . cleanOutput($row['desc']) . '</span>';
                    }
                    echo '
                    </div>';
                }
            }

            // close #todaysevents (if it was started)
            if (!$first) {
                echo '
                </div>';
            }
        }
    }

    /**
     * displayEvents 
     *
     * Display the events for a given day.
     * 
     * @param   int     $month 
     * @param   int     $day 
     * @param   int     $year 
     * @param   boolean $showDesc 
     * @return  void
     */
    function displayEvents ($month, $day, $year, $showDesc = false)
    {
        $month = cleanInput($month, 'int');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = cleanInput($year, 'int');
        $day   = cleanInput($day, 'int');
        $day   = str_pad($day, 2, 0, STR_PAD_LEFT);

        $sql = "SELECT c.`id`, c.`date`, c.`time_start`, c.`time_end`, c.`date_added`, 
                    c.`title`, c.`desc`, c.`created_by`, 
                    c.`private`, c.`repeat`, ca.`name` AS 'category', ca.`color` 
                FROM `fcms_calendar` AS c, `fcms_category` AS ca  
                WHERE 
                    (
                        (c.`date` LIKE '$year-$month-$day') 
                        OR (c.`date` LIKE '%%%%-$month-$day' AND c.`repeat` = 'yearly')
                    )
                    AND c.`category` = ca.`id`
                ORDER BY c.`time_start`";
        $this->db->query($sql) or displaySQLError(
            'Events Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        $times = $this->getTimesList(false);

        if ($this->db->count_rows() > 0) {
            while ($row = $this->db->get_row()) {
                $show = false;
                if ($row['private'] == 0) {
                    $show = true;
                } else {
                    if ($row['created_by'] == $this->currentUserId) {
                        $show = true;
                    }
                }
                if ($show) {

                    $url = 'event';
                    if (
                        checkAccess($this->currentUserId) < 2 || 
                        $this->currentUserId == $row['created_by']
                    ) {
                        $url = 'edit';
                    }

                    // Show The Description
                    if ($showDesc) {
                        echo '<div class="event">' . 
                                '<a class="' . $row['color'] . '" href="?edit=' . $row['id'] . '">' . 
                                    cleanOutput($row['title']) . 
                                    '<span>'. cleanOutput($row['desc']) . '</span>' .
                                '</a>' .
                            '</div>';

                    // No description
                    } else {

                        $title = !empty($row['desc']) ? $row['title'].' : '.$row['desc'] : $row['title'];
                        $htmlTitle = cleanOutput($title);

                        // Add time to event
                        $start = '';
                        if (isset($times[$row['time_start']])) {
                            $start = $times[$row['time_start']];
                        }
                        $end = '';
                        if (isset($times[$row['time_end']])) {
                            if ($row['time_start'] != $row['time_end']) {
                                $end = ' - ' . $times[$row['time_end']];
                            }
                        }

                        echo '<div class="event">' .
                                '<a class="' . $row['color'] . '" ' . 
                                    'title="' . $start .  $end . ' ' . $title . '" ' . 
                                    'href="?' . $url . '=' . $row['id'] . '">' .
                                    '<i>' . $start . '</i> ' .
                                    $row['title'] . 
                                '</a>' .
                            '</div>';
                    }
                }
            }
        }
    }

    /**
     * displayAddForm
     * 
     * Displays the Form to add a new event.
     *
     * @param   date    $addDate
     * @return  void
     */
    function displayAddForm ($addDate)
    {
        $locale = new Locale();

        // Check Access
        if (checkAccess($this->currentUserId) > 3) {
            echo '
            <div class="error-alert">' . T_('You do not have permission to perform this task.') . '</div>';
        }

        // Validate date YYYY-MM-DD or YYYY-M-D
        if (!preg_match('/[0-9]{4}-[0-9]|[0-9]{2}-[0-9]|[0-9]{2}/', $addDate)) {
            echo '
            <div class="error-alert">' . T_('Invalid Date') . '</div>';
        }

        // Format date
        $dateTitle = $locale->formatDate(T_('M. d, Y'), $addDate);

        // Split date
        list($year, $month, $day) = explode('-', $addDate);
        for ($i = 1; $i <= 31; $i++) {
            $days[$i] = $i;
        }
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = $locale->getMonthAbbr($i);
        }
        for ($i = 1900; $i <= date('Y')+5; $i++) {
            $years[$i] = $i;
        }

        // Setup time fields
        $defaultTimeStart = $locale->fixDate('H:i', $this->tz_offset, date('Y-m-d H:i:s'));
        list($hour, $min) = explode(':', $defaultTimeStart);
        if ($min > 30) {
            $defaultTimeStart   = ($hour + 1) . ":00:00";
            $defaultTimeEnd     = ($hour + 1) . ":30:00";
        } else {
            $defaultTimeStart   = "$hour:30:00";
            $defaultTimeEnd     = ($hour + 1) . ":00:00";
        }
        $times = $this->getTimesList();

        // Setup category field
        $sql = "SELECT * FROM `fcms_category` WHERE `type` = 'calendar'";
        $this->db->query($sql) or displaySQLError(
            'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $choose = '';
        while($r = $this->db->get_row()) {
            if ($r['name'] == '') {
                $choose = '<option value="'.$r['id'].'"></option>';
            } else {
                $categories[$r['id']] = $r['name'];
            }
        }

        // Display the form
        echo '
            <form id="frm" method="post" action="calendar.php">
                <fieldset>
                    <legend><span>' . $dateTitle . '</span></legend>

                    <div id="main-cal-info">
                        <div class="field-row clearfix">
                            <div class="field-label"><label for="title"><b>'.T_('Event').'</b></label></div>
                            <div class="field-widget">
                                <input type="text" id="title" name="title" size="40">
                                <script type="text/javascript">
                                    var ftitle = new LiveValidation(\'title\', { onlyOnSubmit: true});
                                    ftitle.add(Validate.Presence, {failureMessage: ""});
                                </script>
                            </div>
                        </div>
                        <div class="field-row clearfix">
                            <div class="field-label"><label for="desc"><b>'.T_('Description').'</b></label></div>
                            <div class="field-widget">
                                <input type="text" id="desc" name="desc" size="50">
                            </div>
                        </div>
                        <div id="time" class="field-row clearfix">
                            <div class="field-label"><label for="sday"><b>'.T_('Time').'</b></label></div>
                            <div class="field-widget">
                                <select id="timestart" name="timestart">
                                    '.buildHtmlSelectOptions($times, $defaultTimeStart).'
                                </select> &nbsp;
                                ' . T_('through') . ' &nbsp;
                                <select id="timeend" name="timeend">
                                    '.buildHtmlSelectOptions($times, $defaultTimeEnd).'
                                </select> &nbsp;
                                <input id="all-day" name="all-day" type="checkbox" 
                                    onclick="toggleDisable($(\'timestart\'), $(\'timeend\'))"/>
                                <label for="all-day">' . T_('All Day') . '</label> 
                            </div>
                        </div>
                    </div>

                    <div id="more-cal-info">
                        <div id="cal-details">
                            <div class="field-row clearfix">
                                <div class="field-label"><label for="category"><b>'.T_('Category').'</b></label></div>
                                <div class="field-widget">
                                    <select id="category" name="category">
                                        '.$choose.'
                                        '.buildHtmlSelectOptions($categories, 0).'
                                    </select>
                                </div>
                            </div>
                            <div class="field-row clearfix">
                                <div class="field-label"><label for="repeat-yearly"><b>'.T_('Repeat (Yearly)').'</b></label></div>
                                <div class="field-widget">
                                    <input type="checkbox" name="repeat-yearly" id="repeat-yearly"/>
                                </div>
                            </div>
                            <div class="field-row clearfix">
                                <div class="field-label"><label for="private"><b>'.T_('Private?').'</b></label></div>
                                <div class="field-widget">
                                    <input type="checkbox" name="private" id="private"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p>
                        <input type="hidden" id="date" name="date" value="' . $addDate . '"/> 
                        <input class="sub1" type="submit" name="add" value="'.T_('Add').'"/> 
                        ' . T_('or') . '&nbsp;
                        <a href="calendar.php?year=' . $year . '&amp;month=' . $month . '&amp;day=' . $day . '">' . T_('Cancel') . '</a>
                    </p>
                </form>
            </fieldset>';
    }

    /**
     * displayEditForm
     * 
     * Displays the form to edit an existing calendar event.
     *
     * @param   int     $id
     * @return  void
     */
    function displayEditForm ($id)
    {
        $id = cleanInput($id, 'int');

        $locale = new Locale();

        $sql = "SELECT `id`, `date`, `time_start`, `time_end`, `date_added`, 
                    `title`, `desc`, `created_by`, `category`, `repeat`, `private`
                FROM `fcms_calendar` 
                WHERE `id` = '$id' 
                LIMIT 1";
        $this->db->query($sql) or displaySQLError(
            'Date Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();

        // Make sure then can edit this event
        if (checkAccess($this->currentUserId) > 1 and $row['created_by'] != $this->currentUserId) {
            echo '
            <div class="error-alert">' . T_('You do not have permission to perform this task.')  . '</div>';
            return;
        }

        list($year, $month, $day) = explode('-', $row['date']);
        for ($i = 1; $i <= 31; $i++) {
            $days[$i] = $i;
        }
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = $locale->getMonthAbbr($i);
        }
        for ($i = 1900; $i <= date('Y')+5; $i++) {
            $years[$i] = $i;
        }

        $times = $this->getTimesList();

        $title  = cleanOutput($row['title']);
        $desc   = cleanOutput($row['desc']);

        $allDayChk = empty($row['time_start']) ? 'checked="checked"' : '';

        // Setup category field
        $sql = "SELECT * FROM `fcms_category` WHERE `type` = 'calendar'";
        $this->db->query($sql) or displaySQLError(
            'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $choose = '';
        while($r = $this->db->get_row()) {
            if ($r['name'] == '') {
                $choose = '<option value="'.$r['id'].'"></option>';
            } else {
                $categories[$r['id']] = $r['name'];
            }
        }

        $repeatChk  = ($row['repeat'] == 'yearly')  ? 'checked="checked"' : '';
        $privateChk = ($row['private'] == 1)        ? 'checked="checked"' : '';

        // Display the form
        echo '
            <form id="frm" method="post" action="calendar.php">
                <fieldset>
                    <legend><span>' . T_('Edit Event') . '</span></legend>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="title"><b>'.T_('Event').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" id="title" name="title" size="40" value="' . $title . '"/>
                            <script type="text/javascript">
                                var ftitle = new LiveValidation(\'title\', { onlyOnSubmit: true});
                                ftitle.add(Validate.Presence, {failureMessage: ""});
                            </script>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="desc"><b>'.T_('Description').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" id="desc" name="desc" size="50" value="' . $desc . '"/>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="sday"><b>'.T_('Date').'</b></label></div>
                        <div class="field-widget">
                            <select id="sday" name="sday">
                                '.buildHtmlSelectOptions($days, $day).'
                            </select>
                            <select id="smonth" name="smonth">
                                '.buildHtmlSelectOptions($months, $month).'
                            </select>
                            <select id="syear" name="syear">
                                '.buildHtmlSelectOptions($years, $year).'
                            </select>
                        </div>
                    </div>
                    <div id="time" class="field-row clearfix">
                        <div class="field-label"><label for="sday"><b>'.T_('Time').'</b></label></div>
                        <div class="field-widget">
                            <select id="timestart" name="timestart">
                                <option></option>
                                '.buildHtmlSelectOptions($times, $row['time_start']).'
                            </select> &nbsp;
                            ' . T_('through') . ' &nbsp;
                            <select id="timeend" name="timeend">
                                <option></option>
                                '.buildHtmlSelectOptions($times, $row['time_end']).'
                            </select> &nbsp;
                            <input id="all-day" named="all-day" type="checkbox" 
                                onclick="toggleDisable($(\'timestart\'), $(\'timeend\'))" ' . $allDayChk . '/>
                            <label for="all-day">' . T_('All Day') . '</label> 
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="category"><b>'.T_('Category').'</b></label></div>
                        <div class="field-widget">
                            <select id="category" name="category">
                                '.$choose.'
                                '.buildHtmlSelectOptions($categories, $row['category']).'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="repeat-yearly"><b>'.T_('Repeat (Yearly)').'</b></label></div>
                        <div class="field-widget">
                            <input type="checkbox" name="repeat-yearly" id="repeat-yearly" ' . $repeatChk . '/>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="private"><b>'.T_('Private?').'</b></label></div>
                        <div class="field-widget">
                            <input type="checkbox" name="private" id="private" ' . $privateChk . '/>
                        </div>
                    </div>

                    <p>
                        <input type="hidden" name="id" value="' . $id . '"/>
                        <input class="sub1" type="submit" name="edit" value="' . T_('Edit') . '"/> 
                        <input class="sub2" type="submit" id="delcal" name="delete" value="' . T_('Delete') . '"/>
                        ' . T_('or') . '&nbsp;
                        <a href="calendar.php?year=' . $year . '&amp;month=' . $month . '&amp;day=' . $day . '">' . T_('Cancel') . '</a>
                    </p>
                </form>
            </fieldset>';
    }

    /**
     * displayEvent
     * 
     * Displays the event for users who can't edit event.
     *
     * @param   int     $id
     * @return  void
     */
    function displayEvent ($id)
    {
        $id = cleanInput($id, 'int');

        $locale = new Locale();

        $sql = "SELECT c.`id`, c.`date`, c.`time_start`, c.`time_end`, c.`date_added`, 
                    c.`title`, c.`desc`, c.`created_by`, cat.`name` AS category, c.`repeat`, c.`private`
                FROM `fcms_calendar` AS c, `fcms_category` AS cat 
                WHERE c.`id` = '$id' 
                    AND c.`category` = cat.`id` 
                LIMIT 1";
        $this->db->query($sql) or displaySQLError(
            'Date Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();

        $times = $this->getTimesList();
        list($year, $month, $day) = explode('-', $row['date']);

        $date = $locale->formatDate(T_('F j, Y'), $row['date']);
        if ($row['repeat'] == 'yearly') {
            $date = sprintf(T_('Every year on %s'), $date);
        }

        $time = '';
        if (isset($times[$row['time_start']])) {
            if ($row['time_start'] == $row['time_end']) {
                $time = '<br/>' . sprintf(T_('beginning at %s'), $times[$row['time_start']]);
            } else {
                $time = '<br/>' . sprintf(T_('between %s and %s'), $times[$row['time_start']], $times[$row['time_end']]);
            }
        }

        $title = cleanOutput($row['title']);

        $cat = '';
        if (!empty($row['category'])) {
            $cat = ' <i>(' . $row['category'] . ')</i>';
        }

        $desc = '';
        if (!empty($row['desc'])) {
            $desc = '<br/>' . cleanOutput($row['desc']);
        }

        // Display the form
        echo '
            <form id="frm" method="post" action="calendar.php">
                <fieldset>
                    <legend><span>' . T_('Event') . '</span></legend>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="title"><b>' . T_('What') . '</b></label></div>
                        <div class="field-widget">
                            <b>' . $title . '</b>' . $cat . $desc . '
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="sday"><b>'.T_('When').'</b></label></div>
                        <div class="field-widget">
                            <b>' . $date . '</b> ' . $time . '
                        </div>
                    </div>
                    <p style="text-align: right">
                        <a href="calendar.php?year=' . $year . '&amp;month=' . $month . '&amp;day=' . $day . '">' 
                            . T_('Back to Calendar') . 
                        '</a>
                    </p>
                </form>
            </fieldset>';
    }

    /**
     * displayCategoryForm 
     * 
     * Displays the Form to add a new category.
     * 
     * @param int $id 
     * @return void
     */
    function displayCategoryForm ($id = 0)
    {
        $id = cleanInput($id, 'int');

        $name   = '';
        $none   = '';
        $red    = '';
        $orange = '';
        $yellow = '';
        $green  = '';
        $blue   = '';
        $indigo = '';
        $violet = '';
        $url    = '';

        $title = T_('Add New Category');

        if ($id > 0) {
            $sql = "SELECT `name`, `color` 
                    FROM `fcms_category` 
                    WHERE `id` = '$id' 
                    LIMIT 1";
            $this->db->query($sql) or displaySQLError(
                'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $row = $this->db->get_row();
            $title = T_('Edit Category');
            $url = '&amp;id='.$id;
            $name = $row['name'];
            ${$row['color']} = 'checked="checked"';
        }
        echo '
            <form method="post" action="calendar.php?category=add'.$url.'">
                <fieldset>
                    <legend><span>'.$title.'</span></legend>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="name"><b>'.T_('Name').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" id="name" name="name" size="40" value="'.$name.'">
                            <script type="text/javascript">
                                var fname = new LiveValidation(\'name\', { onlyOnSubmit: true});
                                fname.add(Validate.Presence, {failureMessage: ""});
                            </script>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="color"><b>'.T_('Color').'</b></label></div>
                        <div class="field-widget">
                            <label for="none" class="colors none"><input type="radio" '.$none.' name="colors" id="none" value="none"/>'.T_('None').'</label>
                            <label for="red" class="colors red"><input type="radio" '.$red.' name="colors" id="red" value="red"/>'.T_('Red').'</label>
                            <label for="orange" class="colors orange"><input type="radio" '.$orange.' name="colors" id="orange" value="orange"/>'.T_('Orange').'</label>
                            <label for="yellow" class="colors yellow"><input type="radio" '.$yellow.' name="colors" id="yellow" value="yellow"/>'.T_('Yellow').'</label><br/>
                            <label for="green" class="colors green"><input type="radio" '.$green.' name="colors" id="green" value="green"/>'.T_('Green').'</label>
                            <label for="blue" class="colors blue"><input type="radio" '.$blue.' name="colors" id="blue" value="blue"/>'.T_('Blue').'</label>
                            <label for="indigo" class="colors indigo"><input type="radio" '.$indigo.' name="colors" id="indigo" value="indigo"/>'.T_('Indigo').'</label>
                            <label for="violet" class="colors violet"><input type="radio" '.$violet.' name="colors" id="violet" value="violet"/>'.T_('Violet').'</label>
                        </div>
                    </div>';
        if ($id > 0) {
            echo '
                    <p>
                        <input type="hidden" id="id" name="id" value="'.$id.'"/> 
                        <input class="sub1" type="submit" id="editcat" name="editcat" value="'.T_('Edit').'"/> 
                        <input class="sub2" type="submit" id="delcat" name="delcat" value="'.T_('Delete').'"/>
                        '.T_('or').' &nbsp;
                        <a href="calendar.php">'.T_('Cancel').'</a>
                    </p>';
        } else {
            echo '
                    <p>
                        <input class="sub1" type="submit" id="addcat" name="addcat" value="'.T_('Add').'"/>
                        '.T_('or').' &nbsp;
                        <a href="calendar.php">'.T_('Cancel').'</a>
                    </p>';
        }
        echo '
                </fieldset>
            </form>';
    }

    /**
     * exportCalendar
     * 
     * Exports all calendar entries in .iCalendar (.ico) format
     * http://tools.ietf.org/html/rfc2445#section-4.6
     * 
     * @return void
     */
    function exportCalendar ()
    {
        // Get List of all categories
        $categories = $this->getCategoryList();

        $cal = "BEGIN:VCALENDAR\nPRODID:-//Family Connections//EN\nVERSION:2.0\n";
        $sql = "SELECT `date`, `date_added`, `title`, `desc`, `repeat`, c.`category`, 
                    CONCAT(`fname`, ' ', `lname`) AS 'organizer', `private`
                FROM `fcms_calendar` AS c, `fcms_users` AS u 
                WHERE c.`created_by` = u.`id";
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
                $category = isset($categories[$r['category']]) ? strtoupper($categories[$r['category']]) : '';
                $cal .= "CATEGORIES:$category\n";
                if ($r['repeat'] == 'yearly') {
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
     * @param   $file
     * @return  void
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
                    . "`date`, `date_added`, `title`, `desc`, `created_by`, `category`, `private`"
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
            if (isset($event['CREATED'])) {
                $sql .= "'" . date('Y-m-d H:i:s', strtotime($event['CREATED'])) . "', ";
            } else {
                $sql .= "NOW(), ";
            }
            // title
            $sql .= "'" . addslashes($event['SUMMARY']) . "', ";
            // description
            if (isset($event['DESCRIPTION'])) {
                $sql .= "'" . addslashes($event['DESCRIPTION']) . "', ";
            } else {
                $sql .= "NULL, ";
            }
            // created_by
            $sql .= "'" . $this->currentUserId . "', ";
            // category
            if (isset($event['CATEGORIES'])) {
                $sql .= "'" . getCalendarCategory(trim($event['CATEGORIES'])) . "', ";
            } else {
                $sql .= "1, ";
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
     * 
     * @return void
     */
    function displayImportForm ()
    {
        echo '
            <form enctype="multipart/form-data" method="post" action="calendar.php">
                <fieldset class="add-edit big">
                    <legend><span>'.T_('Import').'</span></legend>
                    <p><input class="frm_file" type="file" id="file" name="file"/></p>
                    <p>
                        <input type="submit" name="import" value="'.T_('Import').'"/> 
                        '.T_('or').' &nbsp;
                        <a href="calendar.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * displayWhatsNewCalendar
     * 
     * Returns the last 5 added calendar entries in the current month
     * 
     * @return void
     */
    function displayWhatsNewCalendar ()
    {
        $locale = new Locale();

        $today_start = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '000000';
        $today_end   = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '235959';

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
            <h3>'.T_('Calendar').'</h3>
            <ul>';

            while ($r = $this->db->get_row()) {
                $title = $r['title'];
                $displayname = getUserDisplayName($r['created_by']);
                $date = $locale->fixDate('YmdHis', $this->tz_offset, $r['date_added']);
                if ($date >= $today_start && $date <= $today_end) {
                    $full_date = T_('Today');
                    $d = ' class="today"';
                } else {
                    $full_date = $locale->fixDate(T_('M. j, Y, g:i a'), $this->tz_offset, $r['date_added']);
                    $d = '';
                }
                list($year, $month, $day) = explode('-', date('Y-m-d', strtotime($r['date'])));
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

    /**
     * getCategoryList
     * 
     * Returns the current list of categories
     *
     * @return array
     */
    function getCategoryList ()
    {
        $sql = "SELECT `id`, `name` 
                FROM `fcms_category` 
                WHERE `type` = 'calendar'
                AND `name` != ''";
        $this->db->query($sql) or displaySQLError(
            'Categories Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $cats = array();
        if ($this->db->count_rows() > 0) {
            while ($r = $this->db->get_row()) {
                $cats[$r['id']] = $r['name'];
            }
        }
        return $cats;
    }

    /**
     * getCategories
     * 
     * Returns the current list of categories formatted as list items
     *
     * @return  string
     */
    function getCategories ()
    {
        $sql = "SELECT * 
                FROM `fcms_category` 
                WHERE `type` = 'calendar'
                AND `name` != ''";
        $this->db->query($sql) or displaySQLError(
            'Categories Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $ret = '';
        if ($this->db->count_rows() > 0) {
            while ($r = $this->db->get_row()) {
                $ret .= '
                            <li class="cat '.$r['color'].'">
                                <a title="'.T_('Edit Category').'" href="?category=edit&amp;id='.$r['id'].'">'.cleanOutput($r['name']).'</a>
                            </li>';
            }
        }
        return $ret;
    }

    /**
     * displayTimeEvents 
     * 
     * Given an array of events, and an hour.  Displays all events for that hour.
     *
     * @param array $timeEvents 
     * @param string $hour 
     * @return void
     */
    function displayTimeEvents ($timeEvents, $hour)
    {
        if (!is_array($timeEvents)) {
            return;
        }

        $t = $this->getTimesList();

        if (isset($timeEvents[$hour])) {
            if (is_array($timeEvents[$hour][0])) {
                foreach($timeEvents[$hour] AS $event) {
                    echo '
                        <div class="event">' .
                            '<a class="' . $event['color'] . '" href="?edit=' . $event['id'] . '">' . 
                                '<i>' . $t[$event['time_start']] . ' - ' . $t[$event['time_end']] . '</i>' .
                                $event['title'] . 
                                '<span>' . $event['desc'] . '</span>' .
                            '</a>' .
                        '</div>';
                }
            } else {
                echo '
                        <div class="event">' .
                            '<a class="' . $timeEvents[$hour]['color'] . '" href="?edit=' . $timeEvents[$hour]['id'] . '">' . 
                                '<i>' . $t[$timeEvents[$hour]['time_start']] . '</i>' .
                                $timeEvents[$hour]['title'] . 
                                '<span>' . $timeEvents[$hour]['desc'] . '</span>' .
                            '</a>' .
                        '</div>';
            }
        }

    }

    /**
     * getTimesList 
     * 
     * Returns an array of times, used for start end time for calendar events.
     *
     * @param   boolean $whitespace 
     * @return  array
     */
    function getTimesList ($whitespace = true)
    {
        if ($whitespace) {
            return array(
                '00:00:00' => '12:00 am',
                '00:30:00' => '12:30 am',
                '01:00:00' => '1:00 am',
                '01:30:00' => '1:30 am',
                '02:00:00' => '2:00 am',
                '02:30:00' => '2:30 am',
                '03:00:00' => '3:00 am',
                '03:30:00' => '3:30 am',
                '04:00:00' => '4:00 am',
                '04:30:00' => '4:30 am',
                '05:00:00' => '5:00 am',
                '05:30:00' => '5:30 am',
                '06:00:00' => '6:00 am',
                '06:30:00' => '6:30 am',
                '07:00:00' => '7:00 am',
                '07:30:00' => '7:30 am',
                '08:00:00' => '8:00 am',
                '08:30:00' => '8:30 am',
                '09:00:00' => '9:00 am',
                '09:30:00' => '9:30 am',
                '10:00:00' => '10:00 am',
                '10:30:00' => '10:30 am',
                '11:00:00' => '11:00 am',
                '11:30:00' => '11:30 am',
                '12:00:00' => '12:00 pm',
                '12:30:00' => '12:30 pm',
                '13:00:00' => '1:00 pm',
                '13:30:00' => '1:30 pm',
                '14:00:00' => '2:00 pm',
                '14:30:00' => '2:30 pm',
                '15:00:00' => '3:00 pm',
                '15:30:00' => '3:30 pm',
                '16:00:00' => '4:00 pm',
                '16:30:00' => '4:30 pm',
                '17:00:00' => '5:00 pm',
                '17:30:00' => '5:30 pm',
                '18:00:00' => '6:00 pm',
                '18:30:00' => '6:30 pm',
                '19:00:00' => '7:00 pm',
                '19:30:00' => '7:30 pm',
                '20:00:00' => '8:00 pm',
                '20:30:00' => '8:30 pm',
                '21:00:00' => '9:00 pm',
                '21:30:00' => '9:30 pm',
                '22:00:00' => '10:00 pm',
                '22:30:00' => '10:30 pm',
                '23:00:00' => '11:00 pm',
                '23:30:00' => '11:30 pm',
            );
        }

        // remove whitespace
        return array(
            '00:00:00' => '12:00am',
            '00:30:00' => '12:30am',
            '01:00:00' => '1:00am',
            '01:30:00' => '1:30am',
            '02:00:00' => '2:00am',
            '02:30:00' => '2:30am',
            '03:00:00' => '3:00am',
            '03:30:00' => '3:30am',
            '04:00:00' => '4:00am',
            '04:30:00' => '4:30am',
            '05:00:00' => '5:00am',
            '05:30:00' => '5:30am',
            '06:00:00' => '6:00am',
            '06:30:00' => '6:30am',
            '07:00:00' => '7:00am',
            '07:30:00' => '7:30am',
            '08:00:00' => '8:00am',
            '08:30:00' => '8:30am',
            '09:00:00' => '9:00am',
            '09:30:00' => '9:30am',
            '10:00:00' => '10:00am',
            '10:30:00' => '10:30am',
            '11:00:00' => '11:00am',
            '11:30:00' => '11:30am',
            '12:00:00' => '12:00pm',
            '12:30:00' => '12:30pm',
            '13:00:00' => '1:00pm',
            '13:30:00' => '1:30pm',
            '14:00:00' => '2:00pm',
            '14:30:00' => '2:30pm',
            '15:00:00' => '3:00pm',
            '15:30:00' => '3:30pm',
            '16:00:00' => '4:00pm',
            '16:30:00' => '4:30pm',
            '17:00:00' => '5:00pm',
            '17:30:00' => '5:30pm',
            '18:00:00' => '6:00pm',
            '18:30:00' => '6:30pm',
            '19:00:00' => '7:00pm',
            '19:30:00' => '7:30pm',
            '20:00:00' => '8:00pm',
            '20:30:00' => '8:30pm',
            '21:00:00' => '9:00pm',
            '21:30:00' => '9:30pm',
            '22:00:00' => '10:00pm',
            '22:30:00' => '10:30pm',
            '23:00:00' => '11:00pm',
            '23:30:00' => '11:30pm',
        );
    }

}?>
