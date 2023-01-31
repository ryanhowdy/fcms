<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Calendar;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\EventCategory;

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

    /**
     * create 
     * 
     * @return Illuminate\View\View
     */
    public function create()
    {
        $categories = EventCategory::orderBy('name')
            ->where('id', '!=', 1)
            ->get();

        return view('calendar.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store the new event in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => ['required', 'max:50'],
            'date'       => ['required', 'date'],
            'time_start' => ['sometimes', 'nullable', 'date_format:H:i'],
            'time_end'   => ['sometimes', 'nullable', 'date_format:H:i', 'after:time_start'],
        ]);

        $date = Carbon::createFromDate($request->date);

        $event = new Event;

        $event->date              = $request->date;
        $event->title             = $request->title;
        $event->event_category_id = 1;
        $event->private           = $request->has('private');
        $event->invite            = $request->has('invite');
        $event->created_user_id   = Auth()->user()->id;
        $event->updated_user_id   = Auth()->user()->id;

        if ($request->has('start'))
        {
            $event->time_start = $request->input('start');
        }
        if ($request->has('end'))
        {
            $event->time_end = $request->input('end');
        }
        if ($request->has('category'))
        {
            $event->event_category_id = $request->has('category');
        }
        if ($request->has('description'))
        {
            $event->desc = $request->input('description');
        }
        if ($request->has('repeat_yearly'))
        {
            $event->repeat = 'yearly';
        }

        $event->save();

        return redirect()->route('calendar.month', [ $date->format('Y'), $date->format('m'), $date->format('d') ]);
    }
}
