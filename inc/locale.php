<?php
include_once('util_inc.php');

class Locale
{
    var $day;
    var $day_initial;
    var $day_abbr;
    var $month;
    var $month_abbr;
    var $meridiem;

    function Locale ()
    {
        $this->day[0] = _('Sunday');
        $this->day[1] = _('Monday');
        $this->day[2] = _('Tuesday');
        $this->day[3] = _('Wednesday');
        $this->day[4] = _('Thursday');
        $this->day[5] = _('Friday');
        $this->day[6] = _('Saturday');

        $this->day_initial[0] = _('sunday_initial');
        $this->day_initial[1] = _('monday_initial');
        $this->day_initial[2] = _('tuesday_initial');
        $this->day_initial[3] = _('wednesday_initial');
        $this->day_initial[4] = _('thursday_initial');
        $this->day_initial[5] = _('friday_initial');
        $this->day_initial[6] = _('saturday_initial');

        $this->day_abbr[0] = _('Sun');
        $this->day_abbr[1] = _('Mon');
        $this->day_abbr[2] = _('Tue');
        $this->day_abbr[3] = _('Wed');
        $this->day_abbr[4] = _('Thr');
        $this->day_abbr[5] = _('Fri');
        $this->day_abbr[6] = _('Sat');

        $this->month[1] = _('January');
        $this->month[2] = _('February');
        $this->month[3] = _('March');
        $this->month[4] = _('April');
        $this->month[5] = _('May');
        $this->month[6] = _('June');
        $this->month[7] = _('July');
        $this->month[8] = _('August');
        $this->month[9] = _('September');
        $this->month[10] = _('October');
        $this->month[11] = _('November');
        $this->month[12] = _('December');

        $this->month_abbr[1] = _('january_abbreviation');
        $this->month_abbr[2] = _('february_abbreviation');
        $this->month_abbr[3] = _('march_abbreviation');
        $this->month_abbr[4] = _('aprial_abbreviation');
        $this->month_abbr[5] = _('may_abbreviation');
        $this->month_abbr[6] = _('jun_abbreviation');
        $this->month_abbr[7] = _('july_abbreviation');
        $this->month_abbr[8] = _('august_abbreviation');
        $this->month_abbr[9] = _('september_abbreviation');
        $this->month_abbr[10] = _('october_abbreviation');
        $this->month_abbr[11] = _('november_abbreviation');
        $this->month_abbr[12] = _('december_abbreviation');

        $this->meridiem['am'] = _('am');
        $this->meridiem['pm'] = _('pm');
        $this->meridiem['AM'] = _('AM');
        $this->meridiem['PM'] = _('PM');

    }
    
    function getDayName ($w)
    {
        return $this->day[$w];
    }

    function getDayNames ()
    {
        return $this->day;
    }
    
    function getDayInitial ($w)
    {
        return $this->day_initial[$w];
    }
    
    function getDayInitials ()
    {
        return $this->day_initial;
    }
    
    function getDayAbbr ($w)
    {
        return $this->day_abbr[$w];
    }
    
    function getMonthName ($n)
    {
        return $this->month[$n];
    }
    
    function getMonthAbbr ($n)
    {
        return $this->month_abbr[$n];
    }

    /*
     *  fixDate
     *
     *  Used to output all date/time info.  Fixes timezone, dst and translation.
     *
     *  @param      $dateFormat     a string of the format of the date/time, PHP date
     *  @param      $tzOffset       the timezone offset from the current user
     *  @param      $date           the date to fix
     *  @return     a fixed date as a string
     */
    function fixDate ($dateFormat, $tzOffset = '', $date = false)
    {
        $fixedDate = $date;
        if ($date === false) {
            $fixedDate = gmdate($dateFormat);
        }

        // Fix Timezone
        $fixedDate = gmdate($dateFormat, strtotime($date.$tzOffset));

        // Fix DST
        $sql = "SELECT `dst` FROM `fcms_user_settings` WHERE `user` = ".$_SESSION['login_id'];
        $result = mysql_query($sql) or displaySQLError(
            'DST Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);
        if ($r['dst'] > 0) {
            $fixedDate = date($dateFormat, strtotime($fixedDate . " +1 hours"));
        }

        // Translate
        $m = date('n', strtotime($fixedDate));
        $d = date('w', strtotime($fixedDate));
        $a = date('a', strtotime($fixedDate));
        $A = date('A', strtotime($fixedDate));
        $month = $this->getMonthName($m);
        $month_abbr = $this->getMonthAbbr($m);
        $day = $this->getDayName($d);
        $day_abbr = $this->getDayAbbr($d);
        $meridiem = _($a);
        $meridiem_capital = _($A);
        $dateFormat = preg_replace( "/([^\\\])D/", "\\1" . $day_abbr, $dateFormat);
        $dateFormat = preg_replace( "/([^\\\])F/", "\\1" . $month, $dateFormat);
        $dateFormat = preg_replace( "/([^\\\])l/", "\\1" . $day, $dateFormat);
        $dateFormat = preg_replace( "/([^\\\])M/", "\\1" . $month_abbr, $dateFormat);
        $dateFormat = preg_replace( "/([^\\\])a/", "\\1" . $meridiem, $dateFormat);
        $dateFormat = preg_replace( "/([^\\\])A/", "\\1" . $meridiem_capital, $dateFormat);
        return $fixedDate;
    }

}
