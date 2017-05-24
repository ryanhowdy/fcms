<?php
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
    var $fcmsError;
    var $fcmsDatabase;
    var $fcmsUser;
    var $weekStartOffset;

    /**
     * __construct 
     * 
     * @param FCMS_Error $fcmsError 
     * @param Database   $fcmsDatabase
     * @param User       $fcmsUser 
     * 
     * @return void
     */
    public function __construct (FCMS_Error $fcmsError, Database $fcmsDatabase, User $fcmsUser)
    {
        $this->fcmsError       = $fcmsError;
        $this->fcmsDatabase    = $fcmsDatabase;
        $this->fcmsUser        = $fcmsUser;
        $this->weekStartOffset = getCalendarWeekStart();
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
        $month = (int)$month;
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = (int)$year;

        $days = array();

        // Get days from calendar events
        $sql = "SELECT DAYOFMONTH(`date`) as day, `private`, `created_by` 
                FROM `fcms_calendar` 
                WHERE (`date` LIKE '$year-$month-%%') 
                OR (`date` LIKE '%%%%-$month-%%' AND `repeat` = 'yearly') 
                ORDER BY day";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return $days;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $r)
            {
                if ($r['private'] == 1)
                {
                    // only the user who created the private event can see it
                    if ($r['created_by'] == $this->fcmsUser->id)
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

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return $days;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $r)
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
        $templateParams = array(
            'previousText'     => T_('Previous'),
            'todayText'        => T_('Today'),
            'nextText'         => T_('Next'),
            'dayText'          => T_('Day'),
            'monthText'        => T_('Month'),
            'categoriesText'   => T_('Categories'),
            'addCategoryText'  => T_('Add Category'),
            'actionsText'      => T_('Actions'),
            'editCategoryText' => T_('Edit Category'),
            'printText'        => T_('Print'),
            'importText'       => T_('Import'),
            'exportText'       => T_('Export'),
            'categories'       => $this->getCategories(),
        );

        if ($month == 0)
        {
            $year  = fixDate('Y', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
            $month = fixDate('m', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
            $day   = fixDate('d', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        }

        $year  = (int)$year;
        $month = (int)$month;
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $day   = (int)$day;
        $day   = str_pad($day, 2, 0, STR_PAD_LEFT);

        $weekDays   = getDayNames();
        $eventDays  = $this->getEventDays($month, $year);
        
        // First day of the month starts on which day?
        $first = mktime(0,0,0,$month,1,$year);
        $offset = date('w', $first);

        // Fix offset - if day of week changed
        if ($this->weekStartOffset > 0)
        {
            $offset = $offset + (7 - $this->weekStartOffset);
            if ($offset >= 7)
            {
                $offset = $offset - 7;
            }
        }

        $daysInMonth = date('t', $first);
        
        // Previous month links
        $prevTS = strtotime("$year-$month-01 -1 month");
        // Make sure previous day is less than the total num of days in prev month
        $pDay = ($day > date('t', $prevTS)) ? date('t', $prevTS) : $day;
        list($pYear, $pMonth) = explode('-', date('Y-m', $prevTS));

        // Today links
        $tYear  = fixDate('Y', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        $tMonth = fixDate('m', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        $tDay   = fixDate('d', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));

        // Next month links
        $nextTS = strtotime("$year-$month-01 +1 month");
        // Make sure next day is less than the total num of days in next month
        $nDay = ($day > date('t', $nextTS)) ? date('t', $nextTS) : $day;
        list($nYear, $nMonth) = explode('-', date('Y-m', $nextTS));

        $templateParams['prevUrl']  = '?year='.$pYear.'&amp;month='.$pMonth.'&amp;day='.$pDay;
        $templateParams['todayUrl'] = '?year='.$tYear.'&amp;month='.$tMonth.'&amp;day='.$tDay;
        $templateParams['nextUrl']  = '?year='.$nYear.'&amp;month='.$nMonth.'&amp;day='.$nDay;

        $templateParams['dayViewUrl']   = '?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;view=day';
        $templateParams['monthViewUrl'] = '?year='.$year.'&amp;month='.$month.'&amp;day='.$day;
        $templateParams['printUrl']     = 'calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;print=1';

        $templateParams['monthYear'] = formatDate('F Y', "$year-$month-$day");

        // Weekday names
        for ($w = 0; $w <= 6; $w++)
        {
            $templateParams['weekDays'][] = $weekDays[($w+$this->weekStartOffset)%7];
        }

        $templateParams['weeks'] = array();

        $templateWeek = array();

        $i = 0;

        // Add weeks array filled with days to the template
        for ($d = (1 - $offset); $d <= $daysInMonth; $d++)
        {
            // start new week
            if ($i % 7 == 0)
            {
                $templateWeek = array();
            }

            // add a day to the week, outside of the month
            if ($d < 1)
            {
                $templateWeek[] = array('class' => 'nonMonthDay');
            }
            // add a day to the week
            else
            {
                $templateDay = array(
                    'dayUrl' => '?year='.$year.'&amp;month='.$month.'&amp;day='.$d.'&amp;view=day',
                    'day'    => $d,
                );

                // today
                if ($d == $day)
                {
                    $templateDay['class'] = 'monthToday';
                }
                // every day other than today
                else
                {
                    $templateDay['class'] = 'monthDay';
                }

                // add the add cal date link
                if ($this->fcmsUser->access <= 5)
                {
                    $templateDay['addUrl']  = '?add='.$year.'-'.$month.'-'.$d;
                    $templateDay['addText'] = T_('Add');
                }

                // display the events for each day
                if (in_array($d, $eventDays))
                {
                    $templateDay['events'] = $this->getEvents($month, $d, $year);
                }

                $templateWeek[] = $templateDay;
            }

            $i++;
            // if we have 7 <td> for the current week close the <tr>
            if ($i % 7 == 0)
            {
                $templateParams['weeks'][] = $templateWeek;
            }
        }

        if ($i % 7 != 0)
        {
            for ($j = 0; $j < (7 - ($i % 7)); $j++)
            {
                $templateWeek[] = array('class' => 'nonMonthDay');
            }
            $templateParams['weeks'][] = $templateWeek;
        }

        loadTemplate('calendar', 'month', $templateParams);
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
        $templateParams = array(
            'previousText'     => T_('Previous'),
            'todayText'        => T_('Today'),
            'nextText'         => T_('Next'),
            'dayText'          => T_('Day'),
            'monthText'        => T_('Month'),
            'categoriesText'   => T_('Categories'),
            'addCategoryText'  => T_('Add Category'),
            'actionsText'      => T_('Actions'),
            'editCategoryText' => T_('Edit Category'),
            'printText'        => T_('Print'),
            'importText'       => T_('Import'),
            'exportText'       => T_('Export'),
            'categories'       => $this->getCategories(),
        );

        $year  = (int)$year;
        $month = (int)$month;
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $day   = (int)$day;
        $day   = str_pad($day, 2, 0, STR_PAD_LEFT);

        // Previous day links
        $prevTS = strtotime("$year-$month-$day -1 day");
        list($pYear, $pMonth, $pDay) = explode('-', date('Y-m-d', $prevTS));

        // Today links
        $tYear   = fixDate('Y', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        $tMonth  = fixDate('m', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        $tDay    = fixDate('d', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        $isToday = false;

        $templateParams['header'] = formatDate(T_('l, F j, Y'), "$year-$month-$day");

        if ("$year$month$day" === "$tYear$tMonth$tDay")
        {
            $isToday = true;
            $templateParams['header'] = T_('Today');
        }

        // Next day links
        $nextTS = strtotime("$year-$month-$day +1 day");
        list($nYear, $nMonth, $nDay) = explode('-', date('Y-m-d', $nextTS));

        $templateParams['prevUrl']  = '?year='.$pYear.'&amp;month='.$pMonth.'&amp;day='.$pDay.'&amp;view=day';
        $templateParams['todayUrl'] = '?year='.$tYear.'&amp;month='.$tMonth.'&amp;day='.$tDay.'&amp;view=day';
        $templateParams['nextUrl']  = '?year='.$nYear.'&amp;month='.$nMonth.'&amp;day='.$nDay.'&amp;view=day';

        $templateParams['dayViewUrl']   = '?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;view=day';
        $templateParams['monthViewUrl'] = '?year='.$year.'&amp;month='.$month.'&amp;day='.$day;
        $templateParams['printUrl']     = 'calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;print=1';

        $templateParams['allDayEvents'] = array();
        $templateParams['times']        = array();

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

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $row)
            {
                if (empty($row['time_start']))
                {
                    $templateParams['allDayEvents'][] = array(
                        'class' => cleanOutput($row['color']),
                        'url'   => '?event='.(int)$row['id'],
                        'title' => cleanOutput($row['title'], 'html'),
                        'desc'  => cleanOutput($row['desc'], 'html'),
                    );
                }
                else
                {
                    list($hour, $min, $sec) = explode(':', $row['time_start']);

                    if (isset($timeEvents[$hour]))
                    {
                        $timeEvents[$hour][] = array(
                            'class' => cleanOutput($row['color']),
                            'url'   => '?event='.(int)$row['id'],
                            'start' => cleanOutput($row['time_start']),
                            'end'   => cleanOutput($row['time_end']),
                            'title' => cleanOutput($row['title'], 'html'),
                            'desc'  => cleanOutput($row['desc'], 'html'),
                        );
                    }
                    else
                    {
                        $timeEvents[$hour] = array(array(
                            'class' => cleanOutput($row['color']),
                            'url'   => '?event='.(int)$row['id'],
                            'start' => cleanOutput($row['time_start']),
                            'end'   => cleanOutput($row['time_end']),
                            'title' => cleanOutput($row['title'], 'html'),
                            'desc'  => cleanOutput($row['desc'], 'html'),
                        ));
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

        $r = $this->fcmsDatabase->getRow($sql);
        if ($r === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($r) > 0)
        {
            $birthdayCategory = $r['id'];
            $birthdayColor    = $r['color'];
        }

        // Get Birthdays
        $sql = "SELECT `id`, `fname`, `lname`, `dob_year`, `dob_month`, `dob_day`,
                    `dod_year`, `dod_month`, `dod_day`
                FROM `fcms_users` 
                WHERE `dob_month` = ?
                AND `dob_day` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, array($month, $day));
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $row)
            {
                if (!empty($row['dod_year']) || !empty($row['dod_month']) || !empty($row['dod_day']))
                {
                    continue;
                }

                $age = getAge($row['dob_year'], $row['dob_month'], $row['dob_day'], "$year-$month-$day");

                $row['id']    = 'birthday'.$row['id'];
                $row['color'] = $birthdayColor;
                $row['title'] = $row['fname'].' '.$row['lname'];
                $row['desc']  = sprintf(T_('%s turns %s today.'), $row['fname'], $age);

                $allDayEvents[] = $row;
            }
        }

        // Time Specific Events
        $times   = $this->getTimesList();
        $curTime = fixDate('Hi', $this->fcmsUser->tzOffset, date('Y-m-d H:i:s'))."00";
        
        foreach($times AS $key => $val)
        {
            list($hour, $min, $sec) = explode(':', $key);

            $timeParams = array(
                'class'  => '',
                'time'   => $val,
                'events' => isset($timeEvents[$hour]) ? $timeEvents[$hour] : array(),
            );

            // Only show hours
            if ($min == 30)
            {
                continue;
            }

            // Show time past, for today only
            if ($isToday)
            {
                if ($curTime > $hour.$min.$sec)
                {
                    $timeParams['class'] = 'past';
                }
            }

            // Shrink hours 12:00am (0) through 7:00am (7)
            if ($hour < 8)
            {
                if ($hour == 0)
                {
                    $timeParams['time'] = T_('12:00 am - 7:00 am');
                }
                $templateParams['times'][] = $timeParams;
            }
            // Regular times greater than 7:00am
            else
            {
                $templateParams['times'][] = $timeParams;
            }
        }

        loadTemplate('calendar', 'day', $templateParams);
    }

    /**
     * getSmallCalendar
     * 
     * Gets the data for the small calendar based on the month, day and year.
     *
     * NOTE: Dates are assumed already fixed for timezone and dst.
     * 
     * @param int $month 
     * @param int $year 
     * @param int $day 
     * 
     * @return array
     */
    function getSmallCalendar ($month, $year, $day)
    {
        $month = (int)$month;
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = (int)$year;
        $day   = (int)$day;
        $day   = str_pad($day, 2, 0, STR_PAD_LEFT);

        $weekDays   = getDayInitials();
        $categories = $this->getCategories();
        $eventDays  = $this->getEventDays($month, $year);
        
        // First day of the month starts on which day?
        $first = mktime(0,0,0,$month,1,$year);
        $offset = date('w', $first);

        // Fix offset - if day of week changed
        if ($this->weekStartOffset > 0)
        {
            $offset = $offset + (7 - $this->weekStartOffset);
            if ($offset >= 7)
            {
                $offset = $offset - 7;
            }
        }

        $daysInMonth = date('t', $first);
        
        // Previous month links
        $prevTS = strtotime("$year-$month-01 -1 month");
        // Make sure previous day is less than the total num of days in prev month
        $pDay = ($day > date('t', $prevTS)) ? date('t', $prevTS) : $day;
        list($pYear, $pMonth) = explode('-', date('Y-m', $prevTS));

        // Today links
        $tYear  = fixDate('Y', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        $tMonth = fixDate('m', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        $tDay   = fixDate('d', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));

        // Next month links
        $nextTS = strtotime("$year-$month-01 +1 month");
        // Make sure next day is less than the total num of days in next month
        $nDay = ($day > date('t', $nextTS)) ? date('t', $nextTS) : $day;
        list($nYear, $nMonth) = explode('-', date('Y-m', $nextTS));

        $formatDate = formatDate('F Y', "$year-$month-$day");

        $calendarData = array(
            'thisMonthUrl' => 'calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day,
            'thisMonth'    => $formatDate,
        );

        // Weekday names
        $weekDayData = array();
        for ($w = 0; $w <= 6; $w++)
        {
            $weekDayData[] = $weekDays[($w+$this->weekStartOffset)%7];
        }
        $calendarData['weekDays'] = $weekDayData;


        // Days in the month, fill with events
        $monthData = array();
        $i         = 0;

        for ($d = (1 - $offset); $d <= $daysInMonth; $d++)
        {
            if ($i % 7 == 0)
            {
                // start new week
                $weekData = array();
            }

            if ($d < 1)
            {
                $weekData[] = array(
                    'class' => 'nonMonthDay',
                    'data'  => '&nbsp;',
                );
            }
            else
            {
                $class = 'monthDay';
                if ($d == $day)
                {
                    $class = 'monthToday';
                }

                $data = $d;
                if (in_array($d, $eventDays))
                {
                    $data = '<a href="calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$d.'&amp;view=day">'.$d.'</a>';
                }

                $weekData[] = array(
                    'class' => $class,
                    'data'  => $data,
                );
            }

            $i++;

            // if we have 7 <td> for the current week close the <tr>
            if ($i % 7 == 0)
            {
                $monthData[] = $weekData;
            }
        }

        if ($i % 7 != 0)
        {
            // finish any incomplete weeks/rows
            for ($j = 0; $j < (7 - ($i % 7)); $j++)
            {
                $weekData[] = array(
                    'class' => 'nonMonthDay',
                    'data'  => '&nbsp;',
                );
            }

            $monthData[] = $weekData;
        }

        $calendarData['days'] = $monthData;

        return $calendarData;
    }

    /**
     * getMonthEvents 
     * 
     * Gets a listing of events for a given month.
     * Used on the homepage with the small calendar view.
     *
     * @param int $month 
     * @param int $year 
     *
     * @return array
     */
    function getMonthEvents ($month, $year)
    {
        $month = (int)$month;
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = (int)$year;

        $gm_next   = gmdate('Y-m-d H:i:s', gmmktime(gmdate('h'), gmdate('i'), gmdate('s'), $month+1, 1, $year));
        $nextMonth = fixDate('m', $this->fcmsUser->tzOffset, $gm_next);

        $today      = fixDate('Ymd', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        $today_year = fixDate('Y',   $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));

        $sql = "SELECT `id`, DATE_FORMAT(`date`, '%m%d') as day, `title`, `desc`, 
                    `date`, `private`, `created_by`, `repeat`
                FROM fcms_calendar 
                WHERE (`date` LIKE ?) 
                OR (`date` LIKE ?) 
                OR (`date` LIKE ? AND `repeat` = 'yearly') 
                OR (`date` LIKE ? AND `repeat` = 'yearly') 
                ORDER BY day";

        $params = array(
            "$year-$month-%%",
            "$year-$nextMonth-%%",
            "%%%%-$month-%%",
            "%%%%-$nextMonth-%%",
        );

        $rows = $this->fcmsDatabase->getRows($sql, $params);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $events = array();

        if (count($rows) > 0)
        {
            foreach ($rows as $row)
            {
                $events[] = $row;
            }
        }

        // Get birthdays
        $sql = "SELECT `id`, `fname`, `lname`, `dob_year`, `dob_month`, `dob_day`, 
                    `dod_year`, `dod_month`, `dod_day` 
                FROM `fcms_users` 
                WHERE `dob_month` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, $month);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $r)
            {
                if (empty($r['dob_month']) || empty($r['dob_day']))
                {
                    continue;
                }

                if (!empty($r['dod_year']) || !empty($r['dod_month']) || !empty($r['dod_day']))
                {
                    continue;
                }

                $age = getAge($r['dob_year'], $r['dob_month'], $r['dob_day'], "$year-$month-".$r['dob_day']);

                $r['created_by'] = $r['id'];
                $r['id']         = 'birthday'.$r['id'];
                $r['day']        = $r['dob_month'].$r['dob_day'];
                $r['date']       = $r['dob_year'].'-'.$r['dob_month'].'-'.$r['dob_day'];
                $r['title']      = $r['fname'].' '.$r['lname'];
                $r['desc']       = sprintf(T_('%s turns %s today.'), $r['fname'], $age);
                $r['private']    = 0;
                $r['repeat']     = 'yearly';

                $events[] = $r;
            }
        }

        if (count($events) <= 0)
        {
            return array();
        }

        // show the next 5
        $count = 0;

        // fix order
        $events = subval_sort($events, 'day');

        $eventData = array();

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
                if ($row['created_by'] == $this->fcmsUser->id)
                {
                    $show = true;
                }
            }

            if ($show)
            {
                $count++;

                $title = cleanOutput($row['title']);
                $desc  = !empty($row['desc']) ? $row['desc'] : $row['title'];
                $desc  = cleanOutput($desc);

                $eventData[] = array(
                    'id'    => startsWith($row['id'], 'birthday') ? $row['id'] : (int)$row['id'],
                    'title' => $title,
                    'desc'  => $desc,
                    'date'  => formatDate(T_('M. d'), $row['date']),
                );
            }
        }

        return $eventData;
    }

    /**
     * getTodaysEventsTemplateParams
     *
     * Display the events happening today.  Used on the homepage.
     * 
     * @param   int     $month 
     * @param   int     $day 
     * @param   int     $year 
     * @return  void
     */
    function getTodaysEventsTemplateParams ($month, $day, $year)
    {
        $month = (int)$month;
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = (int)$year;
        $day   = (int)$day;
        $day   = str_pad($day, 2, 0, STR_PAD_LEFT);

        // Get events
        $sql = "SELECT `title`, `desc`, `private`, `created_by`
                FROM fcms_calendar 
                WHERE `date` = ? 
                OR (
                    `date` LIKE ? 
                    AND `repeat` = 'yearly'
                )";

        $params = array(
            "$year-$month-$day",
            "%%%%-$month-$day"
        );

        $rows = $this->fcmsDatabase->getRows($sql, $params);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $events = array();

        if (count($rows) > 0)
        {
            foreach ($rows as $row)
            {
                $events[] = $row;
            }
        }

        // Get birthdays
        $sql = "SELECT `id`, `fname`, `lname`, `dob_year`, `dob_month`, `dob_day`,
                    `dod_year`, `dod_month`, `dod_day` 
                FROM `fcms_users` 
                WHERE `dob_month` = ?
                AND `dob_day` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, array($month, $day));
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $r)
            {
                if (empty($r['dob_month']) || empty($r['dob_day']))
                {
                    continue;
                }

                if (!empty($r['dod_year']) || !empty($r['dod_month']) || !empty($r['dod_day']))
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

        $templateParams = array();

        if (count($events) > 0)
        {
            $templateParams['textTodaysEvents'] = T_('Today\'s Events');
            $templateParams['events']           = array();

            foreach ($events as $row)
            {
                $show = false;
                if ($row['private'] == 0)
                {
                    $show = true;
                }
                else
                {
                    if ($row['created_by'] == $this->fcmsUser->id)
                    {
                        $show = true;
                    }
                }

                // Display each event/calendar entry
                if ($show)
                {
                    $eventParams = array(
                        'title' => cleanOutput($row['title']),
                    );
                    if (!empty($row['desc']))
                    {
                        $eventParams['desc'] = cleanOutput($row['desc']);
                    }
                    $templateParams['events'][] = $eventParams;
                }
            }
        }

        return $templateParams;
    }

    /**
     * getEvents 
     *
     * Display the events for a given day.
     * 
     * @param int $month 
     * @param int $day 
     * @param int $year 
     * 
     * @return  void
     */
    function getEvents ($month, $day, $year)
    {
        $month = (int)$month;
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $year  = (int)$year;
        $day   = (int)$day;
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

        $events = $this->fcmsDatabase->getRows($sql);
        if ($events === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $birthdayCategory = 1;
        $birthdayColor    = 'none';

        // Get birthday category and color
        $sql = "SELECT `id`, `color` 
                FROM `fcms_category` 
                WHERE `name` = 'Birthday'
                LIMIT 1";

        $r = $this->fcmsDatabase->getRow($sql);
        if ($r === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($r) > 0)
        {
            $birthdayCategory = $r['id'];
            $birthdayColor    = $r['color'];
        }

        // Get birthdays
        $sql = "SELECT `id`, `fname`, `lname`, `dob_year`, `dob_month`, `dob_day`, 
                    `dod_year`, `dod_month`, `dod_day`
                FROM `fcms_users` 
                WHERE `dob_month` = ?
                AND `dob_day` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, array($month, $day));
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $r)
            {
                if (empty($r['dob_month']) || empty($r['dob_day']))
                {
                    continue;
                }

                if (!empty($r['dod_year']) || !empty($r['dod_month']) || !empty($r['dod_day']))
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

            for ($i = 0; $i < count($events); $i++)
            {
                $show = false;

                // always display non-private events
                if ($events[$i]['private'] == 0)
                {
                    $show = true;
                }
                // show private events to the user who created it
                elseif ($events[$i]['created_by'] == $this->fcmsUser->id)
                {
                    $show = true;
                }

                if (!$show)
                {
                    continue;
                }

                $events[$i]['title'] = cleanOutput($events[$i]['title']);

                // event title/description
                if (empty($events[$i]['desc']))
                {

                    $events[$i]['desc']    = '<h5>'.$events[$i]['title'].'</h5>';
                    $events[$i]['details'] = '<h5>'.$events[$i]['title'].'</h5>';
                }
                else
                {
                    $cleanDesc = cleanOutput($events[$i]['desc']);

                    $title = $events[$i]['title'].' : '.$cleanDesc;

                    $tooltipDesc = $cleanDesc;
                    if (strlen($tooltipDesc) > 150)
                    {
                        $tooltipDesc = substr($tooltipDesc, 0, 147)."...";
                    }

                    $events[$i]['details'] = '<h5 class="highlight">'.$events[$i]['title'].'</h5><h5>'.$tooltipDesc.'</h5>';
                }

                // event time
                $start = '';
                $end   = '';
                if (isset($times[$events[$i]['time_start']]))
                {
                    $start = $times[$events[$i]['time_start']];

                    $events[$i]['details'] .= '<span>'.$start;

                    if (isset($times[$events[$i]['time_end']]))
                    {
                        if ($events[$i]['time_start'] != $events[$i]['time_end'])
                        {
                            $end = ' - '.$times[$events[$i]['time_end']];

                            $events[$i]['details'] .= $end;
                        }
                    }
                    $events[$i]['details'] .= '</span>';
                }

                $events[$i]['start'] = $start;
                $events[$i]['end']   = $end;

                if ($events[$i]['id'][0] == 'b')
                {
                    $id = 'birthday'.(int)substr($events[$i]['id'], 8);
                }
                else
                {
                    $id = (int)$events[$i]['id'];
                }

                $events[$i]['url']   = '?event='.$id;
                $events[$i]['class'] = cleanOutput($events[$i]['color']);

            } // foreach
        }

        return $events;
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
        if ($this->fcmsUser->access > 3)
        {
            loadTemplate('calendar', 'add', array('error' => T_('You do not have permission to perform this task.')));
            return;
        }

        // Validate date YYYY-MM-DD or YYYY-M-D
        if (!preg_match('/[0-9]{4}-[0-9]|[0-9]{2}-[0-9]|[0-9]{2}/', $addDate))
        {
            loadTemplate('calendar', 'add', array('error' => T_('Invalid Date.')));
            return;
        }

        $templateParams = array(
            'eventText'        => T_('Event'),
            'date'             => formatDate(T_('M. d, Y'), $addDate),
            'descriptionText'  => T_('Description'),
            'timeText'         => T_('Time'),
            'throughText'      => T_('through'),
            'allDayText'       => T_('All Day'),
            'categoryText'     => T_('Category'),
            'repeatYearlyText' => T_('Repeat (Yearly)'),
            'privateText'      => T_('Private?'),
            'inviteGuestsText' => T_('Invite Guests?'),
            'addText'          => T_('Add'),
            'addDate'          => $addDate,
            'orText'           => T_('or'),
            'cancelText'       => T_('Cancel'),
        );

        // Split date
        list($year, $month, $day) = explode('-', $addDate);
        for ($i = 1; $i <= 31; $i++)
        {
            $days[$i] = $i;
        }
        for ($i = 1; $i <= 12; $i++)
        {
            $months[$i] = getMonthAbbr($i);
        }
        for ($i = 1900; $i <= date('Y')+5; $i++)
        {
            $years[$i] = $i;
        }

        $templateParams['cancelUrl'] = 'calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day;

        // Setup time fields
        $defaultTimeStart = fixDate('H:i', $this->fcmsUser->tzOffset, date('Y-m-d H:i:s'));
        list($hour, $min) = explode(':', $defaultTimeStart);
        if ($min > 30)
        {
            $defaultTimeStart   = ($hour + 1).":00:00";
            $defaultTimeEnd     = ($hour + 1).":30:00";
        }
        else
        {
            $defaultTimeStart   = "$hour:30:00";
            $defaultTimeEnd     = ($hour + 1).":00:00";
        }
        $times = $this->getTimesList();

        foreach ($times as $key => $val)
        {
            $templateParams['startTimes'][] = array(
                'value'    => $key,
                'selected' => ($defaultTimeStart == $key ? 'selected="selected"' : ''),
                'text'     => $val,
            );
            $templateParams['endTimes'][]   = array(
                'value'    => $key,
                'selected' => ($defaultTimeEnd == $key ? 'selected="selected"' : ''),
                'text'     => $val,
            );
        }

        // Setup category field
        $sql = "SELECT * 
                FROM `fcms_category` 
                WHERE `type` = 'calendar'";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        foreach ($rows as $r)
        {
            $templateParams['categories'][] = array(
                'value' => $r['id'],
                'text'  => $r['name'],
            );
        }

        loadTemplate('calendar', 'add', $templateParams);
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
        $id = (int)$id;

        $sql = "SELECT `id`, `date`, `time_start`, `time_end`, `date_added`, 
                    `title`, `desc`, `created_by`, `category`, `repeat`, `private`, `invite`
                FROM `fcms_calendar` 
                WHERE `id` = ?
                LIMIT 1";

        $calendar = $this->fcmsDatabase->getRow($sql, $id);
        if ($calendar === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        // Make sure then can edit this event
        if ($this->fcmsUser->access > 1 and $calendar['created_by'] != $this->fcmsUser->id)
        {
            loadTemplate('calendar', 'add', array('error' => T_('You do not have permission to perform this task.')));
            return;
        }

        $templateParams = array(
            'id'               => (int)$calendar['id'],
            'editEventText'    => T_('Edit Event'),
            'eventText'        => T_('Event'),
            'title'            => cleanOutput($calendar['title']),
            'descriptionText'  => T_('Description'),
            'description'      => cleanOutput($calendar['desc']),
            'dateText'         => T_('Date'),
            'timeText'         => T_('Time'),
            'throughText'      => T_('through'),
            'allDayText'       => T_('All Day'),
            'categoryText'     => T_('Category'),
            'repeatYearlyText' => T_('Repeat (Yearly)'),
            'privateText'      => T_('Private?'),
            'inviteGuestsText' => T_('Invite Guests?'),
            'editText'         => T_('Edit'),
            'deleteText'       => T_('Delete'),
            'cancelText'       => T_('Cancel'),
            'orText'           => T_('or'),
            'repeatChecked'    => ($calendar['repeat'] == 'yearly' ? 'checked="checked"' : ''),
            'privateChecked'   => ($calendar['private'] == 1       ? 'checked="checked"' : ''),
            'inviteChecked'    => ($calendar['invite'] == 1        ? 'checked="checked"' : ''),
        );

        list($year, $month, $day) = explode('-', $calendar['date']);
        for ($i = 1; $i <= 31; $i++) {
            $templateParams['days'][] = array(
                'value'    => $i,
                'selected' => ($day == $i ? 'selected="selected"' : ''),
                'text'     => $i,
            );
        }
        for ($i = 1; $i <= 12; $i++) {
            $templateParams['months'][] = array(
                'value'    => $i,
                'selected' => ($month == $i ? 'selected="selected"' : ''),
                'text'     => getMonthAbbr($i),
            );
        }
        $templateParams['year'] = $year;

        $templateParams['cancelUrl'] = 'calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day;

        $times = $this->getTimesList();

        foreach ($times as $key => $val)
        {
            $templateParams['startTimes'][] = array(
                'value'    => $key,
                'selected' => ($calendar['time_start'] == $key ? 'selected="selected"' : ''),
                'text'     => $val,
            );
            $templateParams['endTimes'][]   = array(
                'value'    => $key,
                'selected' => ($calendar['time_end'] == $key ? 'selected="selected"' : ''),
                'text'     => $val,
            );
        }

        $templateParams['allDayChecked'] = empty($calendar['time_start']) ? 'checked="checked"' : '';

        // Setup category field
        $sql = "SELECT *
                FROM `fcms_category` 
                WHERE `type` = 'calendar'";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        foreach ($rows as $r)
        {
            $templateParams['categories'][] = array(
                'value'    => (int)$r['id'],
                'selected' => ($calendar['category'] == $r['id'] ? 'selected="selected"' : ''),
                'text'     => cleanOutput($r['name']),
            );
        }

        loadTemplate('calendar', 'edit', $templateParams);
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
    function displayEvent ($id, $templateParams = array())
    {
echo '<h1 style="background-color: red">displayEvent</h1>';
        $id = (int)$id;

        $templateParams['editUrl'] = '?edit='.$id;

        $sql = "SELECT c.`id`, c.`date`, c.`time_start`, c.`time_end`, c.`date_added`, c.`title`, 
                    c.`desc`, c.`created_by`, cat.`name` AS category, c.`repeat`, c.`private`,
                    c.`invite`
                FROM `fcms_calendar` AS c, `fcms_category` AS cat 
                WHERE c.`id` = ?
                AND c.`category` = cat.`id` 
                LIMIT 1";

        $row = $this->fcmsDatabase->getRow($sql, $id);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($row) <= 0)
        {
            loadTemplate('calendar', 'event', array(
                'error' => array(
                        'header' => T_('I can\'t seem to find that calendar event.'),
                        'errors' => array(
                            T_('Please double check and try again.'),
                        ),
                    ),
                )
            );
            return;
        }

        $times = $this->getTimesList();
        $date  = formatDate(T_('F j, Y'), $row['date']);

        $time = '';
        $desc = '';

        list($year, $month, $day) = explode('-', $row['date']);

        $templateParams['title']       = cleanOutput($row['title']);
        $templateParams['backUrl']     = 'calendar.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day;
        $templateParams['category']    = cleanOutput($row['category']);
        $templateParams['description'] = cleanOutput($row['desc']);

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
                $time = sprintf(T_('beginning at %s'), $times[$row['time_start']]);
            }
            // start and end
            else
            {
                $time = sprintf(T_('between %s and %s'), $times[$row['time_start']], $times[$row['time_end']]);
            }
        }

        $templateParams['date'] = $date;
        $templateParams['time'] = $time;

        // host/created by
        $templateParams['hostOrCreatedTitle'] = T_('Created By');
        if ($row['invite'] == 1)
        {
            $templateParams['hostOrCreatedTitle'] = T_('Host');
        }

        $templateParams['createdBy'] = getUserDisplayName($row['created_by']);

        if ($this->fcmsUser->access == 1 || $row['created_by'] == $this->fcmsUser->id)
        {
            $templateParams['edit'] = 1;
        }

        loadTemplate('calendar', 'event', $templateParams);

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
        $id = (int)$id;

        $sql = "SELECT `id`, `fname`, `lname`, `dob_year`, `dob_month`, `dob_day`
                FROM `fcms_users`
                WHERE `id` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $id);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $templateParams = array();

        if (empty($row))
        {
            loadTemplate('calendar', 'event', array(
                'error' => array(
                        'header' => T_('I can\'t seem to find that calendar event.'),
                        'errors' => array(
                            T_('Please double check and try again.'),
                        ),
                    ),
                )
            );
            return;
        }

        $year  = $row['dob_year'];
        $month = $row['dob_month'];
        $day   = $row['dob_day'];

        $date = formatDate(T_('F j'), "$year-$month-$day");
        $date = sprintf(T_('Every year on %s, since %s.'), $date, !empty($year) ? $year : '?');

        // Figure out age
        $age = getAge($year, $month, $day, date('Y')."-$month-$day");

        $edit = '';

        // If this bday is the current user's, edit sends them to their profile
        if ($id == $this->fcmsUser->id)
        {
            $templateParams['edit']    = 1;
            $templateParams['editUrl'] = 'profile.php?view=info';
        }
        // If current user is admin, edit sends them to the admin member's page
        elseif ($this->fcmsUser->access == 1)
        {
            $templateParams['edit']    = 1;
            $templateParams['editUrl'] = 'admin/members.php?edit='.$id;
        }

        $templateParams['title']       = cleanOutput($row['fname']).' '.cleanOutput($row['lname']);
        $templateParams['backUrl']     = 'calendar.php?year='.date('Y').'&amp;month='.$month.'&amp;day='.$day;
        $templateParams['description'] = sprintf(T_('%s turns %s today.'), cleanOutput($row['fname']), $age);
        $templateParams['date']        = $date;

        loadTemplate('calendar', 'event', $templateParams);
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
                WHERE `event_id` = ?
                ORDER BY `updated` DESC";

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            displayFooter();
            exit();
        }

        $templateParams = array(
            'eventId'   => $id,
            'whosComing' => array(
                'yes'       => array(
                    'count' => 0,
                    'users' => array(),
                ),
                'no'        => array(
                    'count' => 0,
                    'users' => array(),
                ),
                'maybe'     => array(
                    'count' => 0,
                    'users' => array(),
                ),
                'undecided' => array(
                    'count' => 0,
                    'users' => array(),
                ),
            ),
            'responses' => array(),
        );

        $usersLkup = array();

        foreach ($rows as $r)
        {
            $usersLkup[$r['user']] = array(
                'attending' => $r['attending'],
                'id'        => $r['id']
            );

            $response = array(
                'updated' => fixDate(T_('F j, Y g:i a'), $this->fcmsUser->tzOffset, $r['updated']),
                'text'    => cleanOutput($r['response']),
            );

            $displayname = cleanOutput($r['email']);
            if ($r['user'] != 0)
            {
                $displayname = getUserDisplayName($r['user'], 2);
            }
            $response['name'] = $displayname;

            if ($r['attending'] === null)
            {
                $templateParams['whosComing']['undecided']['count']++;
                $templateParams['whosComing']['undecided']['users'][] = $displayname;
            }
            elseif ($r['attending'] == 0)
            {
                $templateParams['whosComing']['no']['count']++;
                $templateParams['whosComing']['no']['users'] = $displayname;

                $response['responseType'] = 'no';
                $response['responseText'] = T_('No');
            }
            elseif ($r['attending'] == 1)
            {
                $templateParams['whosComing']['yes']['count']++;
                $templateParams['whosComing']['yes']['users'] = $displayname;

                $response['responseType'] = 'yes';
                $response['responseText'] = T_('Yes');
            }
            elseif ($r['attending'] > 1)
            {
                $templateParams['whosComing']['maybe']['count']++;
                $templateParams['whosComing']['maybe']['users'] = $displayname;

                $response['responseType'] = 'maybe';
                $response['responseText'] = T_('Maybe');
            }

            if ($r['attending'] > 0)
            {
                $templateParams['responses'][] = $response;
            }
        }

        if (isset($usersLkup[$this->fcmsUser->id]) && $usersLkup[$this->fcmsUser->id]['attending'] === null)
        {
            $templateParams['showAttendingForm']     = 1;
            $templateParams['currentUserResponseId'] = $usersLkup[$this->fcmsUser->id]['id'];
        }

        loadTemplate('calendar', 'event-invitation', $templateParams);
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
        $id = (int)$id;

        $templateParams = array(
            'url'    => '?category=add',
            'title'  => T_('Add New Category'),
            'name'   => '',
        );

        // Edit
        if ($id > 0)
        {
            $sql = "SELECT `name`, `color` 
                    FROM `fcms_category` 
                    WHERE `id` = ?
                    LIMIT 1";

            $row = $this->fcmsDatabase->getRow($sql, $id);
            if ($row === false)
            {
                $this->fcmsError->displayError();
                return;
            }

            $templateParams['edit']  = true;
            $templateParams['id']    = $id;
            $templateParams['title'] = T_('Edit Category');
            $templateParams['url']   = '?category=edit&amp;id='.$id;
            $templateParams['name']  = cleanOutput($row['name']);

            $templateParams[$row['color']] = 'checked="checked"';
        }

        loadTemplate('calendar', 'category', $templateParams);
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

        $cal = "BEGIN:VCALENDAR\r\nPRODID:-//Family Connections//EN\r\nVERSION:2.0\r\n";

        $sql = "SELECT `date`, `date_added`, `title`, `desc`, `repeat`, c.`category`, 
                    CONCAT(`fname`, ' ', `lname`) AS 'organizer', `private`
                FROM `fcms_calendar` AS c, `fcms_users` AS u 
                WHERE c.`created_by` = u.`id";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $r)
            {
                $cal .= "BEGIN:VEVENT\r\n";
                // datetime must be 20080609T152552Z format
                $cal .= "DTSTART:".date('Ymd\This\Z', strtotime($r['date']))."\r\n";
                $cal .= "SUMMARY:".$r['title']."\r\n";
                // If description is over 30 characters long, do iCal folding technique
                $desc = $r['desc'];
                $desc = wordwrap($desc, 30, "\r\n  ");
                $cal .= "DESCRIPTION:$desc\r\n";
                if ($r['private'] > 0) {
                    $cal .= "CLASS:PRIVATE\r\n";
                }
                if ($r['date_added'] != '0000-00-00 00:00:00') {
                    // datetime must be 20080609T152552Z format
                    $cal .= "CREATED:".date('Ymd\THis\Z', strtotime($r['date_added']))."\r\n";
                }
                $category = isset($categories[$r['category']]) ? strtoupper($categories[$r['category']]) : '';
                $cal .= "CATEGORIES:$category\r\n";
                if ($r['repeat'] == 'yearly') {
                    $cal .= "RRULE:FREQ=YEARLY\r\n";
                }
                $cal .= "ORGANIZER:CN=".$r['organizer']."\r\n";
                $cal .= "END:VEVENT\r\n";
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
     * @param $file
     *
     * @return boolean
     */
    function importCalendar ($file)
    {
        // Read in the file and parse the data to an array or arrays
        $row = file($file);

        $foundEvent = false;
        $events     = array();

        $i = 0;
        foreach ($row as $r)
        {
            // Find Beginning
            $pos = strpos($r, "BEGIN:VEVENT");
            if ($pos !== false)
            {
                $foundEvent = true;
            }

            if ($foundEvent === true)
            {
                $tag = strpos($r, ":");
                if ($tag === false)
                {
                    // Found badly formatted line in ICS file
                    continue;
                }

                $name = substr($r, 0, $tag);

                $events[$i][$name] = substr($r, $tag+1);
            }

            // Find End
            $pos = strpos($r, "END:VEVENT");
            if ($pos !== false)
            {
                $foundEvent = false;
                $i++;
            }
        }

        
        // Loop through the multidimensional array and insert valid event data into db
        foreach ($events as $event)
        {
            $sql = "INSERT INTO `fcms_calendar`
                        (`date`, `date_added`, `title`, `desc`, `created_by`, `category`, `private`) 
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?)";

            $params = array();

            // date
            if (isset($event['DTSTART;VALUE=DATE']))
            {
                $params[] = date('Y-m-d', strtotime($event['DTSTART;VALUE=DATE']));
            }
            elseif (isset($event['DTSTART']))
            {
                $params[] = date('Y-m-d', strtotime($event['DTSTART']));
            }
            else
            {
                $params[] = '0000-00-00';
            }

            // date_added
            if (isset($event['CREATED']))
            {
                $params[] = date('Y-m-d H:i:s', strtotime($event['CREATED']));
            }
            else
            {
                $params[] = date('Y-m-d H:i:s');
            }

            // title
            $params[] = $event['SUMMARY'];

            // description
            if (isset($event['DESCRIPTION']))
            {
                $params[] = strip_tags($event['DESCRIPTION']);
            }
            else
            {
                $params[] = null;
            }

            // created_by
            $params[] = $this->fcmsUser->id;

            // category
            if (isset($event['CATEGORIES']))
            {
                $params[] = getCalendarCategory(trim($event['CATEGORIES']));
            }
            else
            {
                $params[] = 1;
            }

            // private
            if (isset($event['CLASS']))
            {
                if (trim($event['CLASS']) == 'PRIVATE')
                {
                    $params[] = 1;
                }
                else
                {
                    $params[] = 0;
                }
            }
            else
            {
                $params[] = 0;
            }

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                $this->fcmsError->displayError();
                return false;
            }
        }

        return true;
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
        loadTemplate('calendar', 'import');
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
        $cats = array();

        $sql = "SELECT `id`, `name` 
                FROM `fcms_category` 
                WHERE `type` = 'calendar'
                AND `name` != ''";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return $cats;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $r)
            {
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
        $categories = array();

        $sql = "SELECT * 
                FROM `fcms_category` 
                WHERE `type` = 'calendar'
                AND `name` != ''";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return $ret;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $r)
            {
                $categories[] = array(
                    'class' => cleanOutput($r['color']),
                    'url'   => '?category=edit&amp;id='.(int)$r['id'],
                    'name'  => cleanOutput($r['name'], 'html'),
                );
            }
        }

        return $categories;
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
        if ($whitespace)
        {
            return array(
                '00:00:00' => T_('12:00 am'),
                '00:30:00' => T_('12:30 am'),
                '01:00:00' => T_('1:00 am'),
                '01:30:00' => T_('1:30 am'),
                '02:00:00' => T_('2:00 am'),
                '02:30:00' => T_('2:30 am'),
                '03:00:00' => T_('3:00 am'),
                '03:30:00' => T_('3:30 am'),
                '04:00:00' => T_('4:00 am'),
                '04:30:00' => T_('4:30 am'),
                '05:00:00' => T_('5:00 am'),
                '05:30:00' => T_('5:30 am'),
                '06:00:00' => T_('6:00 am'),
                '06:30:00' => T_('6:30 am'),
                '07:00:00' => T_('7:00 am'),
                '07:30:00' => T_('7:30 am'),
                '08:00:00' => T_('8:00 am'),
                '08:30:00' => T_('8:30 am'),
                '09:00:00' => T_('9:00 am'),
                '09:30:00' => T_('9:30 am'),
                '10:00:00' => T_('10:00 am'),
                '10:30:00' => T_('10:30 am'),
                '11:00:00' => T_('11:00 am'),
                '11:30:00' => T_('11:30 am'),
                '12:00:00' => T_('12:00 pm'),
                '12:30:00' => T_('12:30 pm'),
                '13:00:00' => T_('1:00 pm'),
                '13:30:00' => T_('1:30 pm'),
                '14:00:00' => T_('2:00 pm'),
                '14:30:00' => T_('2:30 pm'),
                '15:00:00' => T_('3:00 pm'),
                '15:30:00' => T_('3:30 pm'),
                '16:00:00' => T_('4:00 pm'),
                '16:30:00' => T_('4:30 pm'),
                '17:00:00' => T_('5:00 pm'),
                '17:30:00' => T_('5:30 pm'),
                '18:00:00' => T_('6:00 pm'),
                '18:30:00' => T_('6:30 pm'),
                '19:00:00' => T_('7:00 pm'),
                '19:30:00' => T_('7:30 pm'),
                '20:00:00' => T_('8:00 pm'),
                '20:30:00' => T_('8:30 pm'),
                '21:00:00' => T_('9:00 pm'),
                '21:30:00' => T_('9:30 pm'),
                '22:00:00' => T_('10:00 pm'),
                '22:30:00' => T_('10:30 pm'),
                '23:00:00' => T_('11:00 pm'),
                '23:30:00' => T_('11:30 pm'),
            );
        }

        // remove whitespace
        return array(
            '00:00:00' => T_('12:00am'),
            '00:30:00' => T_('12:30am'),
            '01:00:00' => T_('1:00am'),
            '01:30:00' => T_('1:30am'),
            '02:00:00' => T_('2:00am'),
            '02:30:00' => T_('2:30am'),
            '03:00:00' => T_('3:00am'),
            '03:30:00' => T_('3:30am'),
            '04:00:00' => T_('4:00am'),
            '04:30:00' => T_('4:30am'),
            '05:00:00' => T_('5:00am'),
            '05:30:00' => T_('5:30am'),
            '06:00:00' => T_('6:00am'),
            '06:30:00' => T_('6:30am'),
            '07:00:00' => T_('7:00am'),
            '07:30:00' => T_('7:30am'),
            '08:00:00' => T_('8:00am'),
            '08:30:00' => T_('8:30am'),
            '09:00:00' => T_('9:00am'),
            '09:30:00' => T_('9:30am'),
            '10:00:00' => T_('10:00am'),
            '10:30:00' => T_('10:30am'),
            '11:00:00' => T_('11:00am'),
            '11:30:00' => T_('11:30am'),
            '12:00:00' => T_('12:00pm'),
            '12:30:00' => T_('12:30pm'),
            '13:00:00' => T_('1:00pm'),
            '13:30:00' => T_('1:30pm'),
            '14:00:00' => T_('2:00pm'),
            '14:30:00' => T_('2:30pm'),
            '15:00:00' => T_('3:00pm'),
            '15:30:00' => T_('3:30pm'),
            '16:00:00' => T_('4:00pm'),
            '16:30:00' => T_('4:30pm'),
            '17:00:00' => T_('5:00pm'),
            '17:30:00' => T_('5:30pm'),
            '18:00:00' => T_('6:00pm'),
            '18:30:00' => T_('6:30pm'),
            '19:00:00' => T_('7:00pm'),
            '19:30:00' => T_('7:30pm'),
            '20:00:00' => T_('8:00pm'),
            '20:30:00' => T_('8:30pm'),
            '21:00:00' => T_('9:00pm'),
            '21:30:00' => T_('9:30pm'),
            '22:00:00' => T_('10:00pm'),
            '22:30:00' => T_('10:30pm'),
            '23:00:00' => T_('11:00pm'),
            '23:30:00' => T_('11:30pm'),
        );
    }
}
