<?php
include_once('utils.php');

/**
 * getDayName 
 * 
 * Given a number of the weekday 0-6, returns the translated word for that day.
 * 
 * @param int $d 
 * 
 * @return string
 */
function getDayName ($d)
{
    $day = getDayNames();

    return $day[$d];
}

/**
 * getDayNames 
 * 
 * Returns an array of translated weekday names.
 * 
 * @return array
 */
function getDayNames ()
{
    $day[0] = T_('Sunday');
    $day[1] = T_('Monday');
    $day[2] = T_('Tuesday');
    $day[3] = T_('Wednesday');
    $day[4] = T_('Thursday');
    $day[5] = T_('Friday');
    $day[6] = T_('Saturday');

    return $day;
}

/**
 * getDayInitial 
 * 
 * Given a number of the weekday 0-6, returns the translated initial for that day.
 * 
 * @param int $d
 * 
 * @return string
 */
function getDayInitial ($w)
{
    $day_initial = getDayInitials();

    return $day_initial[$w];
}

/**
 * getDayInitials 
 * 
 * Returns an array of translated weekday initials.
 * 
 * @return array
 */
function getDayInitials ()
{
    $day_initial[0] = T_pgettext('One letter abbreviation for Sunday.', 'S');
    $day_initial[1] = T_pgettext('One letter abbreviation for Monday.', 'M');
    $day_initial[2] = T_pgettext('One letter abbreviation for Tuesday.', 'T');
    $day_initial[3] = T_pgettext('One letter abbreviation for Wednesday.', 'W');
    $day_initial[4] = T_pgettext('One letter abbreviation for Thursday.', 'T');
    $day_initial[5] = T_pgettext('One letter abbreviation for Friday.', 'F');
    $day_initial[6] = T_pgettext('One letter abbreviation for Saturday.', 'S');

    return $day_initial;
}

/**
 * getDayAbbr 
 * 
 * Given a number of the weekday 0-6, returns the translated 3 letter abbreviation for that day.
 * 
 * @param int $d
 *
 * @return string
 */
function getDayAbbr ($d)
{
    $day_abbr[0] = T_pgettext('Three letter abbreviation for Sunday.', 'Sun');
    $day_abbr[1] = T_pgettext('Three letter abbreviation for Monday.', 'Mon');
    $day_abbr[2] = T_pgettext('Three letter abbreviation for Tuesday.', 'Tue');
    $day_abbr[3] = T_pgettext('Three letter abbreviation for Wednesday.', 'Wed');
    $day_abbr[4] = T_pgettext('Three letter abbreviation for Thursday.', 'Thr');
    $day_abbr[5] = T_pgettext('Three letter abbreviation for Friday.', 'Fri');
    $day_abbr[6] = T_pgettext('Three letter abbreviation for Saturday.', 'Sat');

    return $day_abbr[$d];
}

/**
 * getMonthName 
 * 
 * Given a number of the month 1-12, returns the translated month.
 * 
 * @param int $m 
 * 
 * @return string
 */
function getMonthName ($m)
{
    $month[1]  = T_('January');
    $month[2]  = T_('February');
    $month[3]  = T_('March');
    $month[4]  = T_('April');
    $month[5]  = T_('May');
    $month[6]  = T_('June');
    $month[7]  = T_('July');
    $month[8]  = T_('August');
    $month[9]  = T_('September');
    $month[10] = T_('October');
    $month[11] = T_('November');
    $month[12] = T_('December');

    return $month[$m];
}

/**
 * getMonthAbbr 
 * 
 * Given a number of the month 1-12, returns the translated 3 letter abbreviated month.
 * 
 * @param int $m
 * 
 * @return string
 */
function getMonthAbbr ($m)
{
    $month_abbr[1]  = T_pgettext('Three letter abbreviation for January.', 'Jan');
    $month_abbr[2]  = T_pgettext('Three letter abbreviation for February.', 'Feb');
    $month_abbr[3]  = T_pgettext('Three letter abbreviation for March.', 'Mar');
    $month_abbr[4]  = T_pgettext('Three letter abbreviation for April.', 'Apr');
    $month_abbr[5]  = T_pgettext('Three letter abbreviation for May.', 'May');
    $month_abbr[6]  = T_pgettext('Three letter abbreviation for June.', 'Jun');
    $month_abbr[7]  = T_pgettext('Three letter abbreviation for July.', 'Jul');
    $month_abbr[8]  = T_pgettext('Three letter abbreviation for August.', 'Aug');
    $month_abbr[9]  = T_pgettext('Three letter abbreviation for September.', 'Sep');
    $month_abbr[10] = T_pgettext('Three letter abbreviation for October.', 'Oct');
    $month_abbr[11] = T_pgettext('Three letter abbreviation for November.', 'Nov');
    $month_abbr[12] = T_pgettext('Three letter abbreviation for December.', 'Dec');

    return $month_abbr[$m];
}

/**
 * getMeridiem 
 * 
 * Given a meridiem (am pm AM PM), returns the translated version.
 * 
 * @param string $a 
 * 
 * @return string
 */
function getMeridiem ($a)
{
    $meridiem['am'] = T_pgettext('Lowercase Ante meridiem.', 'am');
    $meridiem['pm'] = T_pgettext('Lowercase Post meridiem.', 'pm');
    $meridiem['AM'] = T_pgettext('Uppercase Ante meridiem.', 'AM');
    $meridiem['PM'] = T_pgettext('Uppercase Post meridiem.', 'PM');

    return $meridiem[$a];
}

/**
 * fixDate
 *
 * Used to output all date/time info.  Fixes timezone, dst and translation.
 *
 * @param string $dateFormat a string of the format of the date/time, PHP date
 * @param string $tzOffset   the timezone offset from the current user
 * @param date   $date       the date to fix
 * @param int    $userid     optional, user id to get dst/tz from
 *
 * @return string the formatted and translated date
 */
function fixDate ($dateFormat, $tzOffset = '', $date = '', $userid = '')
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $fixedDate = $date;
    $dst       = '';

    if ($userid == '')
    {
        $userid = (int)$_SESSION['login_id'];
    }

    // Get DST
    $sql = "SELECT `dst` 
            FROM `fcms_user_settings` 
            WHERE `user` = ?";

    $row = $fcmsDatabase->getRow($sql, $userid);
    if ($row === false)
    {
        return $fixedDate;
    }

    if ($row['dst'] > 0)
    {
        $dst = " +1 hours";
    }

    // Fix Timezone / DST
    $fixedDate = gmdate("Y-m-d H:i:s", strtotime("$fixedDate $tzOffset$dst"));

    // Formate date
    $fixedDate = formatDate($dateFormat, $fixedDate);

    return $fixedDate;
}

/**
 * formatDate 
 * 
 * Formats a date with translation.
 *
 * @param string $dateFormat
 * @param date   $date 
 *
 * @return string
 */
function formatDate ($dateFormat, $date)
{
    // Get translatable parts of the date
    $m = date('n', strtotime($date)); # month 1-12
    $d = date('w', strtotime($date)); # weekday 0-6
    $a = date('a', strtotime($date)); # meridiem am or pm
    $A = date('A', strtotime($date)); # merideim AM or PM

    // Get translated forms
    $month      = getMonthName($m);
    $month_abbr = getMonthAbbr($m);
    $day        = getDayName($d);
    $day_abbr   = getDayAbbr($d);
    $l_meridiem = getMeridiem($a);
    $u_meridiem = getMeridiem($A);

    // Replace translatable parts of date with the translated versions
    $dateFormat = preg_replace( "/(?<!\\\)F/", addBackSlashes($month), $dateFormat);      # full month
    $dateFormat = preg_replace( "/(?<!\\\)l/", addBackSlashes($day), $dateFormat);        # full weekday
    $dateFormat = preg_replace( "/(?<!\\\)M/", addBackSlashes($month_abbr), $dateFormat); # 3 letter month
    $dateFormat = preg_replace( "/(?<!\\\)D/", addBackSlashes($day_abbr), $dateFormat);   # 3 letter weekday
    $dateFormat = preg_replace( "/(?<!\\\)a/", addBackSlashes($l_meridiem), $dateFormat); # lowercase merideim
    $dateFormat = preg_replace( "/(?<!\\\)A/", addBackSlashes($u_meridiem), $dateFormat); # uppercase merideim

    // Format date with translated data
    $fixedDate = date($dateFormat, strtotime($date));

    return $fixedDate;
}

/**
 * addBackSlashes
 * 
 * Adds backslashes before letters and before a number at the start of a string.
 * 
 * @param string $string 
 * 
 * @return string
 */
function addBackSlashes ($string)
{
    $string = preg_replace('/^([0-9])/', '\\\\\\\\\1', $string);
    $string = preg_replace('/([a-z])/i', '\\\\\1', $string);

    return $string;
}

/**
 * getHumanTimeSince 
 * 
 * Returns a nice, human readable difference between two dates.
 * ex: "5 days", "24 minutes"
 *
 * to time will be set to time() if not supplied
 * 
 * @param int $from 
 * @param int $to 
 * 
 * @return void
 */
function getHumanTimeSince ($from, $to = 0)
{
    if ($to == 0)
    {
        $to = time();
    }

    $diff = (int)abs($to - $from);

    // now
    if ($diff < 1)
    {
        $since = T_('right now');
    }
    // seconds
    elseif ($diff < 60)
    {
        $since = sprintf(T_ngettext('%s second ago', '%s seconds ago', $diff), $diff);
    }
    // minutes
    elseif ($diff <= 3600)
    {
        $mins = round($diff / 60);
        if ($mins <= 1)
        {
            $mins = 1;
        }
        $since = sprintf(T_ngettext('%s minute ago', '%s minutes ago', $mins), $mins);
    }
    // hours
    elseif (($diff <= 86400) && ($diff > 3600))
    {
        $hours = round($diff / 3600);
        if ($hours <= 1)
        {
            $hours = 1;
        }
        $since = sprintf(T_ngettext('%s hour ago', '%s hours ago', $hours), $hours);
    }
    // days
    elseif ($diff >= 86400)
    {
        $days = round($diff / 86400);
        if ($days <= 1)
        {
            $days = 1;
        }
        $since = sprintf(T_ngettext('%s day ago', '%s days ago', $days), $days);
    }

    return $since;
}

/**
 * formatBirthday 
 * 
 * Because birthdays are not required, user can have parts of a birthday.
 * This will format those partial dates in a nice way.
 * 
 * Shows the following partial dates:
 *  Y-m-d   - 2011-03-15
 *  F, Y    - March, 2011
 *  M. j    - Mar. 15
 *  Y       - 2011
 * 
 * @param string $year 
 * @param string $month 
 * @param string $day 
 * 
 * @return string
 */
function formatBirthday ($year, $month, $day)
{
    if (!empty($year))
    {
        if (!empty($month))
        {
            if (!empty($day))
            {
                return "$year-$month-$day";
            }
            else
            {
                return getMonthName($month).', '.$year;
            }
        }
        else
        {
            return $year;
        }
    }
    elseif (!empty($month))
    {
        if (!empty($day))
        {
            return getMonthAbbr($month).'. '.$day;
        }
    }

    return '';
}

/**
 * getAge 
 * 
 * @param int    $year 
 * @param int    $month 
 * @param int    $day 
 * @param string $dateToCompare defaults to today
 * 
 * @return int
 */
function getAge ($year, $month, $day, $dateToCompare = false)
{
    global $currentUserId;

    $age = 0;

    if ($dateToCompare === false)
    {
        $yearDiff  = gmdate("Y") - $year;
        $monthDiff = gmdate("m") - $month;
        $dayDiff   = gmdate("d") - $day;
    }
    else
    {
        $yearDiff  = gmdate("Y", strtotime($dateToCompare)) - $year;
        $monthDiff = gmdate("m", strtotime($dateToCompare)) - $month;
        $dayDiff   = gmdate("d", strtotime($dateToCompare)) - $day;
    }

    if ($monthDiff < 0)
    {
        $yearDiff--;
    }
    elseif ($monthDiff == 0 && $dayDiff < 0)
    {
        $yearDiff--;
    }

    $age = $yearDiff;

    return $age;
}
