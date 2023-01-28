<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Calendar;
use Carbon\Carbon;
use App\Models\Event;

class CalendarController extends Controller
{
    /**
     * Show the calendar main page
     *
     * @param int $year 
     * @param int $month 
     * @param int $day 
     * @return Illuminate\View\View
     */
    public function index(int $year = null, int $month = null, int $day = null)
    {
        $date = Carbon::now();

        if (!is_null($year) && !is_null($month) && !is_null($day))
        {
            $date = Carbon::createFromDate("$year-$month-$day");
        }

        $calendar = new Calendar();

        $params = $calendar->getCalendarMonth($date);

        return view('calendar.index', $params);
    }

    /**
     * weekView 
     * 
     * @param int $year 
     * @param int $month 
     * @param int $day 
     * @return Illuminate\View\View
     */
    public function weekView(int $year = null, int $month = null, int $day = null)
    {
        $date = Carbon::now();

        if (!is_null($year) && !is_null($month) && !is_null($day))
        {
            $date = Carbon::createFromDate("$year-$month-$day");
        }

        $calendar = new Calendar();

        $params = $calendar->getCalendarWeek($date);

        return view('calendar.week', $params);
    }

    /**
     * dayView 
     * 
     * @param int $year 
     * @param int $month 
     * @param int $day 
     * @return Illuminate\View\View
     */
    public function dayView(int $year = null, int $month = null, int $day = null)
    {
        $date = Carbon::now();

        if (!is_null($year) && !is_null($month) && !is_null($day))
        {
            $date = Carbon::createFromDate("$year-$month-$day");
        }

        $calendar = new Calendar();

        $params = $calendar->getCalendarDay($date);

        return view('calendar.day', $params);
    }
}
