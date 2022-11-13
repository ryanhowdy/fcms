<?php

namespace App;

use Carbon\Carbon;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;

class Calendar
{
    private $startOfWeek;
    private $endOfWeek;
    private $daysOfWeekLkup;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->startOfWeek = env('FCMS_WEEK_START');
        $this->endOfWeek   = env('FCMS_WEEK_END');

        $this->daysOfWeekLkup = [
            0 => __('Sunday'),
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
        ];
    }

    /**
     * Get the calendar params for a month
     *
     * @param  Carbon\Carbon $date
     * @return array
     */
    public function getCalendarMonth(Carbon $date)
    {
        $params = [
            'header'       => $date->format('M Y'),
            'weekDayNames' => [],
            'calendar'     => [],
        ];

        $nextDate = $date->copy()->addMonth();
        $prevDate = $date->copy()->subMonth();

        $params['previousLink'] = route('calendar.month', [ $prevDate->format('Y'), $prevDate->format('m'), $prevDate->format('d') ]);
        $params['nextLink']     = route('calendar.month', [ $nextDate->format('Y'), $nextDate->format('m'), $nextDate->format('d') ]);

        // Get the week day names
        for ($w = 0; $w <= 6; $w++)
        {
            $params['weekDayNames'][] = $this->daysOfWeekLkup[($w + $this->startOfWeek) % 7];
        }

        $startOfCalendar = $date->copy()->firstOfMonth()->startOfWeek($this->startOfWeek);
        $endOfCalendar   = $date->copy()->lastOfMonth()->endOfWeek($this->endOfWeek);

        // Get events
        $events = $this->getEvents($date);

        // https://jonathanbriehl.com/posts/build-a-simple-calendar-with-carbon-and-laravel

        $curDate = $startOfCalendar->copy();

        $d = 0;
        $w = 0;
        while ($curDate <= $endOfCalendar)
        {
            // start new week
            if ($d % 7 == 0)
            {
                $w++;
            }

            $classes = '';
            if ($curDate->format('n') < $date->format('n'))
            {
                $classes .= 'previous ';
            }
            else if ($curDate->format('n') > $date->format('n'))
            {
                $classes .= 'next ';
            }


            if ($date->format('Ymd') === $curDate->format('Ymd'))
            {
                $classes .= 'today ';
            }

            $dayData = [
                'fullDate' => $curDate->format('Y-m-d'),
                'day'      => $curDate->format('j'),
                'class'    => $classes,
                'events'   => [],
            ];

            if (isset($events[ $curDate->format('Y-m-d') ]))
            {
                $dayData['events'] = $events[ $curDate->format('Y-m-d') ];
            }

            $params['calendar'][$w][] = $dayData;

            $curDate->addDay();
            $d++;
        }

        return $params;
    }

    /**
     * Get the calendar events for the given date
     *
     * @param  Carbon\Carbon $date
     * @return array
     */
    public function getEvents(Carbon $date)
    {
        $formattedEvents = [];

        $startOfCalendar = $date->copy()->firstOfMonth()->startOfWeek($this->startOfWeek);
        $endOfCalendar   = $date->copy()->lastOfMonth()->endOfWeek($this->endOfWeek);

        $month = $date->format('m');

        // Get all events for this month
        $events = Event::where('date', '>=', $startOfCalendar->format('Y-m-d'))
            ->where('date', '<=', $endOfCalendar->format('Y-m-d'))
            ->orWhere(function($query) use($month) {
                $query->where('date', 'like', "%%%%-$month-%%")
                      ->where('repeat', 'yearly');
            })
            ->leftJoin('event_categories as c', 'events.event_category_id', '=', 'c.id')
            ->select('events.*', 'c.name as category_name', 'c.color as category_color')
            ->get();

        foreach ($events as $e)
        {
            $dateKey = $date->format('Y').'-';
            $dateKey .= substr($e['date'], 5);

            $formattedEvents[$dateKey][] = $e;
        }

        $birthdayCategory = EventCategory::find(3)->toArray();

        // Get users birthdays for this month
        $birthdays = User::where('dob_month', $date->format('n'))
            ->get();

        foreach ($birthdays as $b)
        {
            $bMonth = $this->fixMonth($b->dob_month);
            $bDay   = $this->fixDay($b->dob_day);

            $carbonBirthday = Carbon::createFromDate($b->dob_year.'-'.$b->dob_month.'-'.$b->dob_day);

            $age = $date->diff($carbonBirthday)->format('%y');

            $dateKey = $date->format('Y').'-'.$bMonth.'-'.$bDay;

            $formattedEvents[$dateKey][] = [
                'date'              => $b->dob_year.'-'.$bMonth.'-'.$bDay,
                'time_start'        => null,
                'time_end'          => null,
                'title'             => $b->fname.' '.$b->lname,
                'desc'              => __('Turns :age years old today.', [ 'age' => $age ]),
                'event_category_id' => 3,
                'category_name'     => $birthdayCategory['name'],
                'category_color'    => $birthdayCategory['color'],
                'repeat'            => 'yearly',
                'private'           => false,
                'invite'            => false,
                'created_user_id'   => $b->id,
                'updated_user_id'   => $b->id,
            ];
        }

        return $formattedEvents;
    }

    /**
     * Make sure the month number is 2 digits
     *
     * @param string $month
     * @return string
     */
    private function fixMonth($month)
    {
        return str_pad($month, 2, 0, STR_PAD_LEFT);
    }

    /**
     * Make sure the day number is 2 digits
     *
     * @param string $day
     * @return string
     */
    private function fixDay($day)
    {
        return str_pad($day, 2, 0, STR_PAD_LEFT);
    }
}
