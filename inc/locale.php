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
        $this->day[0] = T_('Sunday');
        $this->day[1] = T_('Monday');
        $this->day[2] = T_('Tuesday');
        $this->day[3] = T_('Wednesday');
        $this->day[4] = T_('Thursday');
        $this->day[5] = T_('Friday');
        $this->day[6] = T_('Saturday');

        $this->day_initial[0] = T_('sunday_initial');
        $this->day_initial[1] = T_('monday_initial');
        $this->day_initial[2] = T_('tuesday_initial');
        $this->day_initial[3] = T_('wednesday_initial');
        $this->day_initial[4] = T_('thursday_initial');
        $this->day_initial[5] = T_('friday_initial');
        $this->day_initial[6] = T_('saturday_initial');

        $this->day_abbr[0] = T_('Sun');
        $this->day_abbr[1] = T_('Mon');
        $this->day_abbr[2] = T_('Tue');
        $this->day_abbr[3] = T_('Wed');
        $this->day_abbr[4] = T_('Thr');
        $this->day_abbr[5] = T_('Fri');
        $this->day_abbr[6] = T_('Sat');

        $this->month[1] = T_('January');
        $this->month[2] = T_('February');
        $this->month[3] = T_('March');
        $this->month[4] = T_('April');
        $this->month[5] = T_('May');
        $this->month[6] = T_('June');
        $this->month[7] = T_('July');
        $this->month[8] = T_('August');
        $this->month[9] = T_('September');
        $this->month[10] = T_('October');
        $this->month[11] = T_('November');
        $this->month[12] = T_('December');

        $this->month_abbr[1] = T_('january_abbreviation');
        $this->month_abbr[2] = T_('february_abbreviation');
        $this->month_abbr[3] = T_('march_abbreviation');
        $this->month_abbr[4] = T_('aprial_abbreviation');
        $this->month_abbr[5] = T_('may_abbreviation');
        $this->month_abbr[6] = T_('jun_abbreviation');
        $this->month_abbr[7] = T_('july_abbreviation');
        $this->month_abbr[8] = T_('august_abbreviation');
        $this->month_abbr[9] = T_('september_abbreviation');
        $this->month_abbr[10] = T_('october_abbreviation');
        $this->month_abbr[11] = T_('november_abbreviation');
        $this->month_abbr[12] = T_('december_abbreviation');

        $this->meridiem['am'] = T_('am');
        $this->meridiem['pm'] = T_('pm');
        $this->meridiem['AM'] = T_('AM');
        $this->meridiem['PM'] = T_('PM');

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

        // GET DST
        $sql = "SELECT `dst` FROM `fcms_user_settings` WHERE `user` = ".escape_string($_SESSION['login_id']);
        $result = mysql_query($sql) or displaySQLError(
            'DST Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);
        $dst = '';
        if ($r['dst'] > 0) {
            $dst = " +1 hours";
        }

        // Fix Timezone / DST
        $fixedDate = gmdate($dateFormat, strtotime($fixedDate." $tzOffset".$dst));

        // Translate
        $m = date('n', strtotime($fixedDate));
        $d = date('w', strtotime($fixedDate));
        $a = date('a', strtotime($fixedDate));
        $A = date('A', strtotime($fixedDate));
        $month = $this->getMonthName($m);
        $month_abbr = $this->getMonthAbbr($m);
        $day = $this->getDayName($d);
        $day_abbr = $this->getDayAbbr($d);
        $dateFormat = preg_replace( "/([^\\\])D/", "\\1" . $day_abbr, $dateFormat);
        $dateFormat = preg_replace( "/([^\\\])F/", "\\1" . $month, $dateFormat);
        $dateFormat = preg_replace( "/([^\\\])l/", "\\1" . $day, $dateFormat);
        $dateFormat = preg_replace( "/([^\\\])M/", "\\1" . $month_abbr, $dateFormat);
        return $fixedDate;
    }

}
