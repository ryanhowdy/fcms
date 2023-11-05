<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Calendar;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use App\Models\Invitation;

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
        $date = Carbon::now(Auth()->user()->settings->timezone);

        if (!is_null($year) && !is_null($month) && !is_null($day))
        {
            $date = Carbon::createFromDate($year, $month, $day, Auth()->user()->settings->timezone);
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
        $date = Carbon::now(Auth()->user()->settings->timezone);

        if (!is_null($year) && !is_null($month) && !is_null($day))
        {
            $date = Carbon::createFromDate($year, $month, $day, Auth()->user()->settings->timezone);
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
        $date = Carbon::now(Auth()->user()->settings->timezone);

        if (!is_null($year) && !is_null($month) && !is_null($day))
        {
            $date = Carbon::createFromDate($year, $month, $day, Auth()->user()->settings->timezone);
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
     * createDate
     * 
     * @return Illuminate\View\View
     */
    public function createDate(int $year, int $month, int $day)
    {
        $categories = EventCategory::orderBy('name')
            ->where('id', '!=', 1)
            ->get();

        $date = Carbon::createFromDate($year, $month, $day, Auth()->user()->settings->timezone);

        return view('calendar.create', [
            'date'       => $date->format('Y-m-d'),
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
            $event->time_start = $request->start;
        }
        if ($request->has('end'))
        {
            $event->time_end = $request->end;
        }
        if ($request->has('category'))
        {
            $event->event_category_id = $request->category;
        }
        if ($request->has('description'))
        {
            $event->desc = $request->description;
        }
        if ($request->has('repeat-yearly'))
        {
            $event->repeat = 'yearly';
        }

        $event->save();

        if ($event->invite)
        {
            return redirect()->route('invitations.create', [ $event->id ]);
        }

        return redirect()->route('calendar.month', [ $date->format('Y'), $date->format('m'), $date->format('d') ]);
    }

    /**
     * show
     * 
     * @param int $id 
     * @return Illuminate\Support\Facades\View
     */
    public function show(int $id)
    {
        $event = Event::findOrFail($id)->toArray();

        $cDatetime = Carbon::parse($event['date'] . ' ' . $event['time_start'], Auth()->user()->settings->timezone);

        $event['dateFormatted']      = $cDatetime->format('l, F j, Y');
        $event['timeStartFormatted'] = $cDatetime->format('g:ia');

        $invitations = Invitation::where('event_id', $id)
            ->leftJoin('users as u', 'invitations.user_id', '=', 'u.id')
            ->select('invitations.*', 'u.name', 'u.displayname')
            ->get();

        $counts = [
            'attending' => 0,
            'maybe'     => 0,
            'no'        => 0,
            'none'      => 0,
        ];
        $groupInvitations = [
            'all'       => [],
            'attending' => [],
            'maybe'     => [],
            'no'        => [],
            'none'      => [],
        ];

        foreach($invitations as $invite)
        {
            if (!is_null($invite->attending))
            {
                if ($rsvp->attending)
                {
                    $counts['attending']++;

                    $invite->status = 'Attending';

                    $groupInvitations['attending'][] = $invite->toArray();
                }
                else
                {
                    $counts['no']++;

                    $invite->status = 'No';

                    $groupInvitations['no'][] = $invite->toArray();
                }
            }
            elseif(!is_null($invite->response))
            {
                $counts['maybe']++;

                $invite->status = 'Maybe';

                $groupInvitations['maybe'][] = $invite->toArray();
            }
            else
            {
                $counts['none']++;

                $invite->status = 'None';

                $groupInvitations['none'][] = $invite->toArray();
            }

            $groupInvitations['all'][] = $invite->toArray();
        }

        $rsvp = Invitation::where('user_id', Auth()->user()->id)
            ->first();

        if ($rsvp)
        {
            $rsvp->rsvp = 'none';

            if (!is_null($rsvp->attending))
            {
                $rsvp->rsvp = $rsvp->attending ? 'attending' : 'no';
            }
            elseif(!is_null($rsvp->response))
            {
                $rsvp->rsvp = 'maybe';
            }

            $rsvp = $rsvp->toArray();
        }

        return view('calendar.show', [
            'event'       => $event,
            'invitations' => $groupInvitations,
            'counts'      => $counts,
            'rsvp'        => $rsvp,
        ]);
    }
}
