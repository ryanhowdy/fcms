<?php
include_once('database_class.php');
include_once('utils.php');
include_once('datetime.php');

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
    var $tzOffset;

    /**
     * Calendar 
     * 
     * @param  int      $currentUserId 
     *
     * @return void
     */
    function Calendar ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->tzOffset      = getTimezone($this->currentUserId);

    }

    /**
     * getEventDays 
     * 
     * Gets a list (array) of days that have events (fcms_calendar) for a given month/year.
     *
     * Will also include birthday's from the fcms_users table. [since 2.5]
     * 
     * @param   int     $month 
     * @param   int     $year 
     * 
     * @return  array   $days
     */
    function getEventDays ($month, $year)
    {
        $month = cleanInput($month, 'int');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = cleanInput($year, 'int');

        $days = array();

        // Get days from calendar events
        $sql = "SELECT DAYOFMONTH(`date`) as day, `private`, `created_by` 
                FROM `fcms_calendar` 
                WHERE (`date` LIKE '$year-$month-%%') 
                OR (`date` LIKE '%%%%-$month-%%' AND `repeat` = 'yearly') 
                ORDER BY day";

        if (!$this->db->query($sql))
        {
            displaySQLError('Events Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return $days;
        }

        if ($this->db->count_rows() > 0)
        {
            while($r = $this->db->get_row())
            {
                if ($r['private'] == 1)
                {
                    // only the user who created the private event can see it
                    if ($r['created_by'] == $this->currentUserId)
                    {
                        $days[] = $r['day'];
                    }
                }
                else
                {
                    $days[] = $r['day'];
                }
            }
        }

        // Get days from user's birthdays
        $sql = "SELECT `dob_day` 
                FROM `fcms_users` 
                WHERE `dob_month` = '$month' 
                ORDER BY `dob_day`";

        if (!$this->db->query($sql))
        {
            displaySQLError('Events Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return $days;
        }

        if ($this->db->count_rows() > 0)
        {
            while($r = $this->db->get_row())
            {
                $days[] = $r['dob_day'];
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
     * @param int $month 
     * @param int $year 
     * @param int $day 
     * 
     * @return  void
     */
    function displayCalendarMonth ($month = 0, $year = 0, $day = 0)
    {
        if ($month == 0)
        {
            $year  = fixDate('Y', $this->tzOffset, gmdate('Y-m-d H:i:s'));
            $month = fixDate('m', $this->tzOffset, gmdate('Y-m-d H:i:s'));
            $day   = fixDate('d', $this->tzOffset, gmdate('Y-m-d H:i:s'));
        }

        $month = cleanInput($month, 'int');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = cleanInput($year, 'int');
        $day   = cleanInput($day, 'int');
        $day   = str_pad($day, 2, 0, STR_PAD_LEFT);

        $weekDays   = getDayNames();
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
        $tYear  = fixDate('Y', $this->tzOffset, gmdate('Y-m-d H:i:s'));
        $tMonth = fixDate('m', $this->tzOffset, gmdate('Y-m-d H:i:s'));
        $tDay   = fixDate('d', $this->tzOffset, gmdate('Y-m-d H:i:s'));

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
                    <th colspan="3"><h3>'.formatDate('F Y', "$year-$month-$day").'</h3></th>
                    <th class="views" colspan="2">
                        <a class="day" href="?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;view=day">'.T_('Day').'</a> | 
                        <a class="month" href="?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'">'.T_('Month').'</a>
                    </th>
                </tr>
                <tr>';

        // Weekday names
        foreach ($weekDays as $wd)
        {
            echo '
                    <td class="weekDays">' . $wd . '</td>';
        }

        echo '
                </tr>';
            
        $i = 0;

        // Display the days in the month, fill with events
        for ($d = (1 - $offset); $d <= $daysInMonth; $d++)
        {
            // start new row
            if ($i % 7 == 0)
            {
                echo '
                <tr>';
            }

            // display cell for date outside of this month
            if ($d < 1)
            {
                echo '
                    <td class="nonMonthDay">&nbsp;</td>';
            }
            // display cell for a day in this month
            else
            {
                // today
                if ($d == $day)
                {
                    echo '
                    <td class="monthToday">';
                }
                // every day other than today
                else
                {
                    echo '
                    <td class="monthDay">';
                }

                // add the add cal date link
                if (checkAccess($this->currentUserId) <= 5)
                {
                    echo '<a class="add" href="?add='.$year.'-'.$month.'-'.$d.'">'.T_('Add').'</a>';
                }

                // display the day #
                echo '<a href="?year='.$year.'&amp;month='.$month.'&amp;day='.$d.'&amp;view=day">'.$d.'</a>';

                // display the events for each day
                if (in_array($d, $eventDays))
                {
                    $this->displayEvents($month, $d, $year);
                }

                echo "</td>";
            }

            $i++;
            // if we have 7 <td> for the current week close the <tr>
            if ($i % 7 == 0)
            {
                echo '
                </tr>';
            }
        }

        // close any opening <tr> and insert any additional empty <td>
        if ($i % 7 != 0)
        {
            for ($j = 0; $j < (7 - ($i % 7)); $j++)
            {
                echo '
                    <td class="nonMonthDay">&nbsp;</td>';
            }
            echo '
                </tr>';
        }

        // Display the bottom menu of calendar
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

        $categories = $this->getCategories();

        // Previous day links
        $prevTS = strtotime("$year-$month-$day -1 day");
        list($pYear, $pMonth, $pDay) = explode('-', date('Y-m-d', $prevTS));

        // Today links
        $tYear   = fixDate('Y', $this->tzOffset, gmdate('Y-m-d H:i:s'));
        $tMonth  = fixDate('m', $this->tzOffset, gmdate('Y-m-d H:i:s'));
        $tDay    = fixDate('d', $this->tzOffset, gmdate('Y-m-d H:i:s'));
        $isToday = false;
        $header  = formatDate(T_('l, F j, Y'), "$year-$month-$day");

        if ("$year$month$day" === "$tYear$tMonth$tDay")
        {
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

        $allDayEvents = array();
        $timeEvents = array();

        // Get Events
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

        if (!$this->db->query($sql))
        {
            displaySQLError('Events Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            while ($row = $this->db->get_row())
            {
                if (empty($row['time_start']))
                {
                    $allDayEvents[] = $row;
                }
                else
                {
                    list($hour, $min, $sec) = explode(':', $row['time_start']);

                    // multiple events for this hour?
                    if (array_key_exists($hour, $timeEvents))
                    {
                        $singleEvent       = $timeEvents[$hour];
                        $timeEvents[$hour] = array($singleEvent);

                        array_push($timeEvents[$hour], $row);
                    }
                    // nope
                    else
                    {
                        $timeEvents[$hour] = $row;
                    }
                }
            }
        }

        // Get Birthday Category info
        $birthdayCategory = 1;
        $birthdayColor    = 'none';

        // Get birthday category and color
        $sql = "SELECT `id`, `color` 
                FROM `fcms_category` 
                WHERE `name` = 'Birthday'
                LIMIT 1";

        if (!$this->db->query($sql))
        {
            displaySQLError('Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            $r = $this->db->get_row();

            $birthdayCategory = $r['id'];
            $birthdayColor    = $r['color'];
        }

        // Get Birthdays
        $sql = "SELECT `id`, `fname`, `lname`, `dob_year`, `dob_month`, `dob_day`
                FROM `fcms_users` 
                WHERE `dob_month` = '$month'";

        if (!$this->db->query($sql))
        {
            displaySQLError('Events Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            while ($row = $this->db->get_row())
            {
                $age = getAge($row['dob_year'], $row['dob_month'], $row['dob_day'], "$year-$month-$day");

                $row['id']    = 'birthday'.$row['id'];
                $row['color'] = $birthdayColor;
                $row['title'] = $row['fname'].' '.$row['lname'];
                $row['desc']  = sprintf(T_('%s turns %s today.'), $row['fname'], $age);

                $allDayEvents[] = $row;
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
                            '<a class="' . $event['color'] . '" href="?event=' . $event['id'] . '">' . 
                                cleanOutput($event['title']) . 
                                '<span>' . cleanOutput($event['desc']) . '</span>' .
                            '</a>' .
                        '</div>';
        }

        echo '
                    </td>
                </tr>';


        // Time Specific Events
        $times   = $this->getTimesList();
        $curTime = fixDate('Hi', $this->tzOffset, date('Y-m-d H:i:s')) . "00";
        
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

        $weekDays   = getDayInitials();
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
        $tYear  = fixDate('Y', $this->tzOffset, gmdate('Y-m-d H:i:s'));
        $tMonth = fixDate('m', $this->tzOffset, gmdate('Y-m-d H:i:s'));
        $tDay   = fixDate('d', $this->tzOffset, gmdate('Y-m-d H:i:s'));

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
                        <h3><a href="calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'">'
                            .formatDate('F Y', "$year-$month-$day").
                        '</a></h3>
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
     * @param int $month 
     * @param int $year 
     *
     * @return void
     */
    function displayMonthEvents ($month, $year)
    {
        $month = cleanInput($month, 'int');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = cleanInput($year, 'int');

        $gm_next   = gmdate('Y-m-d H:i:s', gmmktime(gmdate('h'), gmdate('i'), gmdate('s'), $month+1, 1, $year));
        $nextMonth = fixDate('m', $this->tzOffset, $gm_next);

        $today      = fixDate('Ymd', $this->tzOffset, gmdate('Y-m-d H:i:s'));
        $today_year = fixDate('Y',   $this->tzOffset, gmdate('Y-m-d H:i:s'));

        $sql = "SELECT `id`, DATE_FORMAT(`date`, '%m%d') as day, `title`, `desc`, 
                    `date`, `private`, `created_by`, `repeat`
                FROM fcms_calendar 
                WHERE (`date` LIKE '$year-$month-%%') 
                OR (`date` LIKE '$year-$nextMonth-%%') 
                OR (`date` LIKE '%%%%-$month-%%' AND `repeat` = 'yearly') 
                OR (`date` LIKE '%%%%-$nextMonth-%%' AND `repeat` = 'yearly') 
                ORDER BY day";
        if (!$this->db->query($sql))
        {
            displaySQLError('Events Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        $events = array();

        if ($this->db->count_rows() > 0)
        {
            while ($row = $this->db->get_row())
            {
                $events[] = $row;
            }
        }

        // Get birthdays
        $sql = "SELECT `id`, `fname`, `lname`, `dob_year`, `dob_month`, `dob_day` 
                FROM `fcms_users` 
                WHERE `dob_month` = '$month'";

        if (!$this->db->query($sql))
        {
            displaySQLError('Birthdays Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            while($r = $this->db->get_row())
            {
                if (empty($r['dob_year']) || empty($r['dob_month']) || empty($r['dob_day']))
                {
                    continue;
                }

                $age = getAge($r['dob_year'], $r['dob_month'], $r['dob_day'], "$year-$month-".$r['dob_day']);

                $r['id']         = 'birthday'.$r['id'];
                $r['day']        = $r['dob_day'];
                $r['date']       = $r['dob_year'].'-'.$r['dob_month'].'-'.$r['dob_day'];
                $r['title']      = $r['fname'].' '.$r['lname'];
                $r['desc']       = sprintf(T_('%s turns %s today.'), $r['fname'], $age);
                $r['private']    = 0;
                $r['repeat']     = 'yearly';
                $r['created_by'] = $r['id'];

                $events[] = $r;
            }
        }

        // show the next 5
        $count = 0;

        // fix order
        $events = subval_sort($events, 'day');

        foreach ($events as $row)
        {
            if ($count > 5)
            {
                break;
            }

            $show = false;

            list($event_year, $event_month, $event_day) = explode("-", $row['date']);

            // Fix repeating event year
            if ($row['repeat'] == 'yearly')
            {
                $event_year = $today_year;
            }

            // Skip events that have already happened
            if ($event_year.$event_month.$event_day < $today)
            {
                continue;
            }

            if ($row['private'] == 0)
            {
                $show = true;
            }
            else
            {
                if ($row['created_by'] == $this->currentUserId)
                {
                    $show = true;
                }
            }

            if ($show)
            {
                $count++;

                $title = !empty($row['desc']) ? $row['desc'] : $row['title'];
                $title = cleanOutput($title);

                echo '
                <div class="events">
                    <a title="'.$title.'" href="calendar.php?event='.$row['id'].'">'.cleanOutput($row['title']).'</a><br/>
                    '.formatDate(T_('M. d'), $row['date']).'
                </div>';
            }
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

        // Get events
        $sql = "SELECT `title`, `desc`, `private`, `created_by`
                FROM fcms_calendar 
                WHERE (`date` LIKE '$year-$month-$day') 
                OR (`date` LIKE '%%%%-$month-$day' AND `repeat` = 'yearly')";
        if (!$this->db->query($sql))
        {
            displaySQLError('Today Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        $events = array();

        if ($this->db->count_rows() > 0)
        {
            while ($row = $this->db->get_row())
            {
                $events[] = $row;
            }
        }

        // Get birthdays
        $sql = "SELECT `id`, `fname`, `lname`, `dob_year`, `dob_month`, `dob_day` 
                FROM `fcms_users` 
                WHERE `dob_month` = '$month'
                AND `dob_day` = '$day'";

        if (!$this->db->query($sql))
        {
            displaySQLError('Birthdays Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            while($r = $this->db->get_row())
            {
                if (empty($r['dob_year']) || empty($r['dob_month']) || empty($r['dob_day']))
                {
                    continue;
                }

                $age = getAge($r['dob_year'], $r['dob_month'], $r['dob_day'], "$year-$month-$day");

                $r['title']      = $r['fname'].' '.$r['lname'];
                $r['desc']       = sprintf(T_('%s turns %s today.'), $r['fname'], $age);
                $r['private']    = 0;
                $r['created_by'] = $r['id'];

                $events[] = $r;
            }
        }

        if (count($events) > 0)
        {
            $first = true;
            foreach ($events as $row)
            {
                $show = false;
                if ($row['private'] == 0)
                {
                    $show = true;
                }
                else
                {
                    if ($row['created_by'] == $this->currentUserId)
                    {
                        $show = true;
                    }
                }

                // Start the todaysevents box
                if ($first & $show)
                {
                    echo '
                <div id="todaysevents">
                    <h2>'.T_('Today\'s Events').':</h2>'.
                    $first = false;
                }

                // Display each event/calendar entry
                if ($show)
                {
                    echo '
                    <div class="events">
                        <b>'.cleanOutput($row['title']).'</b>';

                    if (!empty($row['desc']))
                    {
                        echo '<span>'.cleanOutput($row['desc']).'</span>';
                    }

                    echo '
                    </div>';
                }
            }

            // close #todaysevents (if it was started)
            if (!$first)
            {
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
     * @param int     $month 
     * @param int     $day 
     * @param int     $year 
     * @param boolean $showDesc 
     * 
     * @return  void
     */
    function displayEvents ($month, $day, $year, $showDesc = false)
    {
        $month = cleanInput($month, 'int');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = cleanInput($year, 'int');
        $day   = cleanInput($day, 'int');
        $day   = str_pad($day, 2, 0, STR_PAD_LEFT);

        // Get events from fcms_calendar
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

        if (!$this->db->query($sql))
        {
            displaySQLError('Events Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        $events = array();

        if ($this->db->count_rows() > 0)
        {
            while ($row = $this->db->get_row())
            {
                $events[] = $row;
            }
        }


        $birthdayCategory = 1;
        $birthdayColor    = 'none';

        // Get birthday category and color
        $sql = "SELECT `id`, `color` 
                FROM `fcms_category` 
                WHERE `name` = 'Birthday'
                LIMIT 1";

        if (!$this->db->query($sql))
        {
            displaySQLError('Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            $r = $this->db->get_row();

            $birthdayCategory = $r['id'];
            $birthdayColor    = $r['color'];
        }

        // Get birthdays
        $sql = "SELECT `id`, `fname`, `lname`, `dob_year`, `dob_month`, `dob_day`
                FROM `fcms_users` 
                WHERE `dob_month` = '$month'
                AND `dob_day` = '$day'";

        if (!$this->db->query($sql))
        {
            displaySQLError('Birthdays Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            while($r = $this->db->get_row())
            {
                if (empty($r['dob_year']) || empty($r['dob_month']) || empty($r['dob_day']))
                {
                    continue;
                }

                $age = getAge($r['dob_year'], $r['dob_month'], $r['dob_day'], "$year-$month-$day");

                $r['private']    = 0;
                $r['id']         = 'birthday'.$r['id'];
                $r['time_start'] = 0;
                $r['color']      = $birthdayColor;
                $r['title']      = $r['fname'].' '.$r['lname'];
                $r['desc']       = sprintf(T_('%s turns %s today.'), $r['fname'], $age);

                $events[] = $r;
            }
        }

        if (count($events) > 0)
        {
            $times = $this->getTimesList(false);

            foreach ($events as $event)
            {
                $show = false;

                // always display non-private events
                if ($event['private'] == 0)
                {
                    $show = true;
                }
                // show private events to the user who created it
                elseif ($event['created_by'] == $this->currentUserId)
                {
                    $show = true;
                }

                if ($show)
                {
                    // Show The Description
                    if ($showDesc)
                    {
                        echo '<div class="event">' . 
                                '<a class="' . $event['color'] . '" href="?event=' . $event['id'] . '">' . 
                                    cleanOutput($event['title']) . 
                                    '<span>'. cleanOutput($event['desc']) . '</span>' .
                                '</a>' .
                            '</div>';
                    }
                    // No description
                    else
                    {
                        // event title/description
                        if (empty($event['desc']))
                        {
                            $title = cleanOutput($event['title']);

                            $tooltipDetails = '<h5>'.$title.'</h5>';
                        }
                        else
                        {
                            $cleanTitle = cleanOutput($event['title']);
                            $cleanDesc  = cleanOutput($event['desc']);

                            $title = $cleanTitle.' : '.$cleanDesc;

                            $tooltipDetails = '<h5 class="highlight">'.$cleanTitle.'</h5><h5>'.$cleanDesc.'</h5>';
                        }

                        // event time
                        $start = '';
                        $end   = '';
                        if (isset($times[$event['time_start']]))
                        {
                            $start = $times[$event['time_start']];

                            $tooltipDetails .= '<span>'.$start;

                            if (isset($times[$event['time_end']]))
                            {
                                if ($event['time_start'] != $event['time_end'])
                                {
                                    $end = ' - ' . $times[$event['time_end']];

                                    $tooltipDetails .= $end;
                                }
                            }
                            $tooltipDetails .= '</span>';
                        }

                        echo '<div class="event">' .
                                '<a class="'.$event['color'].' tooltip" title="'.$start.$end.' '.$title.'" href="?event='.$event['id'].'" onmouseover="showTooltip(this)" onmouseout="hideTooltip(this)">' .
                                    '<i>' . $start . '</i> '.$event['title'] .
                                '</a>' .
                                '<div class="tooltip" style="display:none">'.$tooltipDetails.'</div>' .
                            '</div>';
                    }
                }
            } // foreach
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
        $dateTitle = formatDate(T_('M. d, Y'), $addDate);

        // Split date
        list($year, $month, $day) = explode('-', $addDate);
        for ($i = 1; $i <= 31; $i++) {
            $days[$i] = $i;
        }
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = getMonthAbbr($i);
        }
        for ($i = 1900; $i <= date('Y')+5; $i++) {
            $years[$i] = $i;
        }

        // Setup time fields
        $defaultTimeStart = fixDate('H:i', $this->tzOffset, date('Y-m-d H:i:s'));
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
            'Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
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
                                <div class="field-widget">
                                    <input type="checkbox" name="repeat-yearly" id="repeat-yearly"/>
                                    <label for="repeat-yearly"><b>'.T_('Repeat (Yearly)').'</b></label>
                                </div>
                            </div>
                            <div class="field-row clearfix">
                                <div class="field-widget">
                                    <input type="checkbox" name="private" id="private"/>
                                    <label for="private"><b>'.T_('Private?').'</b></label>
                                </div>
                            </div>
                            <div class="field-row clearfix">
                                <div class="field-widget">
                                    <input type="checkbox" name="invite" id="invite"/>
                                    <label for="invite"><b>'.T_('Invite Guests?').'</b></label>
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

        $sql = "SELECT `id`, `date`, `time_start`, `time_end`, `date_added`, 
                    `title`, `desc`, `created_by`, `category`, `repeat`, `private`, `invite`
                FROM `fcms_calendar` 
                WHERE `id` = '$id' 
                LIMIT 1";

        if (!$this->db->query($sql))
        {
            displaySQLError('Date Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        $row = $this->db->get_row();

        // Make sure then can edit this event
        if (checkAccess($this->currentUserId) > 1 and $row['created_by'] != $this->currentUserId)
        {
            echo '
            <div class="error-alert">' . T_('You do not have permission to perform this task.')  . '</div>';
            return;
        }

        list($year, $month, $day) = explode('-', $row['date']);
        for ($i = 1; $i <= 31; $i++) {
            $days[$i] = $i;
        }
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = getMonthAbbr($i);
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
            'Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
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
        $inviteChk  = ($row['invite'] == 1)         ? 'checked="checked"' : '';

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
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="invite"><b>'.T_('Invite Guests?').'</b></label></div>
                        <div class="field-widget">
                            <input type="checkbox" name="invite" id="invite" ' . $inviteChk . '/>
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
     * Displays the event details.
     *
     * @param int $id
     *
     * @return  void
     */
    function displayEvent ($id)
    {
        $id = cleanInput($id, 'int');

        $sql = "SELECT c.`id`, c.`date`, c.`time_start`, c.`time_end`, c.`date_added`, c.`title`, 
                    c.`desc`, c.`created_by`, cat.`name` AS category, c.`repeat`, c.`private`,
                    c.`invite`
                FROM `fcms_calendar` AS c, `fcms_category` AS cat 
                WHERE c.`id` = '$id' 
                    AND c.`category` = cat.`id` 
                LIMIT 1";

        if (!$this->db->query($sql))
        {
            displaySQLError('Event Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '<div class="info-alert"><h2>'.T_('I can\'t seem to find that calendar event.').'</h2>';
            echo '<p>'.T_('Please double check and try again.').'</p></div>';
            return;
        }

        $row = $this->db->get_row();

        $times = $this->getTimesList();
        $date  = formatDate(T_('F j, Y'), $row['date']);
        $title = cleanOutput($row['title']);

        $time = '';
        $cat  = '';
        $desc = '';

        list($year, $month, $day) = explode('-', $row['date']);

        if ($row['repeat'] == 'yearly')
        {
            $date = formatDate(T_('F j'), $row['date']);
            $date = sprintf(T_('Every year on %s'), $date);
        }

        // handle time
        if (isset($times[$row['time_start']]))
        {
            // one moment in time
            if ($row['time_start'] == $row['time_end'])
            {
                $time = '<br/>' . sprintf(T_('beginning at %s'), $times[$row['time_start']]);
            }
            // start and end
            else
            {
                $time = '<br/>' . sprintf(T_('between %s and %s'), $times[$row['time_start']], $times[$row['time_end']]);
            }
        }

        if (!empty($row['category']))
        {
            $cat = ' <h2>' . $row['category'] . '</h2>';
        }

        if (!empty($row['desc']))
        {
            $desc = '<br/>' . cleanOutput($row['desc']);
        }

        // host/created by
        $hostOrCreatedTitle = T_('Created By');
        if ($row['invite'] == 1)
        {
            $hostOrCreatedTitle = T_('Host');
        }

        $edit = '';
        if (checkAccess($this->currentUserId) == 1 || $row['created_by'] == $this->currentUserId)
        {
            $edit = '<span><a href="?edit='.$id.'" class="edit_event">'.T_('Edit').'</a></span>';
        }

        // Display the form
        echo '
            <p id="back">
                <a href="calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'">'.T_('Back to Calendar').'</a>
            </p>
            <div id="event_details">
                '.$edit.'
                <h1>'.$title.'</h1>
                '.$cat.'
                <p id="desc">'.$desc.'</p>
                <div id="when">
                    <h3>'.T_('When').'</h3>
                    <p><b>'.$date.'</b> '.$time.'</p>
                    <h3>'.$hostOrCreatedTitle.'</h3>
                    <p>'.getUserDisplayName($row['created_by']).'</p>
                </div>
            </div>';

        // Show invitation stuff
        if ($row['invite'] == 1)
        {
            $this->displayInvitationDetails($id);
        }
    }

    /**
     * displayBirthdayEvent
     * 
     * Displays the event details for a birthday, which is treated like an event,
     * but isn't really.  Birthday comes from the fcms_user table, and not the fcms_calendar table.
     *
     * @param int $id
     *
     * @return void
     */
    function displayBirthdayEvent ($id)
    {
        $id = cleanInput($id, 'int');

        $sql = "SELECT `id`, `fname`, `lname`, `dob_year`, `dob_month`, `dob_day`
                FROM `fcms_users`
                WHERE `id` = '$id'";

        if (!$this->db->query($sql))
        {
            displaySQLError('Event Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '<div class="info-alert"><h2>'.T_('I can\'t seem to find that calendar event.').'</h2>';
            echo '<p>'.T_('Please double check and try again.').'</p></div>';
            return;
        }

        $row = $this->db->get_row();

        $year  = $row['dob_year'];
        $month = $row['dob_month'];
        $day   = $row['dob_day'];

        $date = formatDate(T_('F j'), "$year-$month-$day");
        $date = sprintf(T_('Every year on %s, since %s.'), $date, $year);

        // Figure out age
        $age = getAge($year, $month, $day);

        $edit = '';

        // If this bday is the current user's, edit sends them to their profile
        if ($id == $this->currentUserId)
        {
            $edit = '<span><a href="profile.php?view=info" class="edit_event">'.T_('Edit').'</a></span>';
        }
        // If current user is admin, edit sends them to the admin member's page
        elseif (checkAccess($this->currentUserId) == 1)
        {
            $edit = '<span><a href="admin/members.php?edit='.$id.'" class="edit_event">'.T_('Edit').'</a></span>';
        }

        // Display the form
        echo '
            <p id="back">
                <a href="calendar.php?year='.date('Y').'&amp;month='.$month.'&amp;day='.$day.'">'.T_('Back to Calendar').'</a>
            </p>
            <div id="event_details">
                '.$edit.'
                <h1>'.$row['fname'].' '.$row['lname'].'</h1>
                <p id="desc">'.sprintf(T_('%s turns %s today.'), $row['fname'], $age).'</p>
                <div id="when">
                    <h3>'.T_('When').'</h3>
                    <p><b>'.$date.'</b></p>
                </div>
            </div>';
    }

    /**
     * displayInvitationDetails 
     * 
     * @param int $id 
     * 
     * @return void
     */
    function displayInvitationDetails ($id)
    {
        // Get info on who's coming
        $sql = "SELECT `id`, `user`, `email`, `attending`, `response`, `updated`
                FROM `fcms_invitation`
                WHERE `event_id` = '$id'
                ORDER BY `updated` DESC";
        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Attending Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            displayFooter();
            exit();
        }

        $yesCount        = 0;
        $noCount         = 0;
        $maybeCount      = 0;
        $undecidedCount  = 0;
        $comingYes       = '';
        $comingNo        = '';
        $comingMaybe     = '';
        $comingUndecided = '';
        $responses       = array();
        $usersLkup       = array();

        while ($r = mysql_fetch_array($result))
        {
            $usersLkup[$r['user']] = array(
                'attending' => $r['attending'],
                'id'        => $r['id']
            );

            $img = '';

            $displayname = cleanOutput($r['email']);
            if ($r['user'] != 0)
            {
                $displayname = getUserDisplayName($r['user'], 2);
            }

            if ($r['attending'] === NULL)
            {
                $undecidedCount++;
                $comingUndecided .= "<p>$displayname</p>";
            }
            elseif ($r['attending'] == 0)
            {
                $noCount++;
                $img = '<img class="avatar" src="themes/images/attend_no.png" alt="'.T_('No').'"/>';
                $comingNo .= "<p>$displayname</p>";
            }
            elseif ($r['attending'] == 1)
            {
                $yesCount++;
                $img = '<img class="avatar" src="themes/images/attend_yes.png" alt="'.T_('Yes').'"/>';
                $comingYes .= "<p>$displayname</p>";
            }
            elseif ($r['attending'] > 1)
            {
                $maybeCount++;
                $img = '<img class="avatar" src="themes/images/attend_maybe.png" alt="'.T_('Maybe').'"/>';
                $comingMaybe .= "<p>$displayname</p>";
            }

            $responses[] = array(
                'user'        => $r['user'],
                'updated'     => $r['updated'],
                'displayname' => $displayname,
                'response'    => $r['response'],
                'attending'   => $r['attending'],
                'img'         => $img
            );
        }

        if (isset($usersLkup[$this->currentUserId]) && $usersLkup[$this->currentUserId]['attending'] === NULL)
        {
            echo '
            <form action="calendar.php?event='.$id.'" method="post">
                <h1 id="attending_header">'.T_('Are you attending?').'</h1>
                <ul id="attending" class="clearfix">
                    <li>
                        <label for="yes">
                            <img src="themes/images/attend_yes.png"/><br/>
                            <b>'.T_('Yes').'</b>
                        </label>
                        <input type="radio" id="yes" name="attending" value="1"/>
                    </li>
                    <li>
                        <label for="maybe">
                            <img src="themes/images/attend_maybe.png"/><br/>
                            <b>'.T_('Maybe').'</b>
                        </label>
                        <input type="radio" id="maybe" name="attending" value="2"/>
                    </li>
                    <li>
                        <label for="no">
                            <img src="themes/images/attend_no.png"/><br/>
                            <b>'.T_('No').'</b>
                        </label>
                        <input type="radio" id="no" name="attending" value="0"/>
                    </li>
                    <li class="submit">
                        <textarea id="response" name="response" cols="50" rows="10"></textarea>
                        <input type="hidden" id="id" name="id" value="'.$usersLkup[$this->currentUserId]['id'].'"/>
                        <input type="submit" id="attend_submit" name="attend_submit" value="'.T_('Submit').'"/>
                    </li>
                </ul>
            </form>';
        }

        echo '
            <div id="leftcolumn">
                <div id="whos_coming">
                    <h3>'.T_('Who\'s Coming').'</h3>
                    <h3 class="coming"><span class="ok"></span>'.T_('Yes').' <i>'.$yesCount.'</i></h3>
                    <div class="coming_details">'.$comingYes.'</div>
                    <h3 class="coming"><span class="maybe"></span>'.T_('Maybe').' <i>'.$maybeCount.'</i></h3>
                    <div class="coming_details">'.$comingMaybe.'</div>
                    <h3 class="coming"><span class="no"></span>'.T_('No').' <i>'.$noCount.'</i></h3>
                    <div class="coming_details">'.$comingNo.'</div>
                    <h3 class="coming">'.T_('Undecided').' <i>'.$undecidedCount.'</i></h3>
                    <div class="coming_details">'.$comingUndecided.'</div>
                </div>
            </div>

            <div id="maincolumn">';

        foreach ($responses as $response)
        {
            if (isset($response['attending']))
            {
                $updated = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $response['updated']);

                echo '
                <div class="comment_block clearfix">
                    '.$response['img'].'
                    <b>'.$response['displayname'].'</b> <i>'.$updated.'</i>
                    <p>
                        '.cleanOutput($response['response']).'
                    </p>
                </div>';
            }
        }

        echo '
            </div>';
    }

    /**
     * displayCategoryForm 
     * 
     * Displays the form to add or edit a category.
     * If no id is given, we are adding a new category.
     * 
     * @param int $id 
     * 
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
        $url    = '?category=add';
        $title  = T_('Add New Category');

        // Edit
        if ($id > 0)
        {
            $sql = "SELECT `name`, `color` 
                    FROM `fcms_category` 
                    WHERE `id` = '$id' 
                    LIMIT 1";
            $this->db->query($sql) or displaySQLError(
                'Category Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
            $row = $this->db->get_row();

            $title = T_('Edit Category');
            $url   = '?category=edit&amp;id='.$id;
            $name  = $row['name'];

            ${$row['color']} = 'checked="checked"';
        }

        echo '
            <form method="post" action="'.$url.'">
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
            'Calendar Entries Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
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
                'Import Entries Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
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
            'Categories Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
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
            'Categories Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
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
                            '<a class="' . $event['color'] . '" href="?event=' . $event['id'] . '">' . 
                                '<i>' . $t[$event['time_start']] . ' - ' . $t[$event['time_end']] . '</i>' .
                                $event['title'] . 
                                '<span>' . $event['desc'] . '</span>' .
                            '</a>' .
                        '</div>';
                }
            } else {
                echo '
                        <div class="event">' .
                            '<a class="' . $timeEvents[$hour]['color'] . '" href="?event=' . $timeEvents[$hour]['id'] . '">' . 
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
