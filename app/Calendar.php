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
            0 => _gettext('Sunday'),
            1 => _gettext('Monday'),
            2 => _gettext('Tuesday'),
            3 => _gettext('Wednesday'),
            4 => _gettext('Thursday'),
            5 => _gettext('Friday'),
            6 => _gettext('Saturday'),
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
            'dayLink'      => route('calendar.day', [ $date->format('Y'), $date->format('m'), $date->format('d') ]),
            'weekLink'     => route('calendar.week', [ $date->format('Y'), $date->format('m'), $date->format('d') ]),
            'monthLink'    => route('calendar.month', [ $date->format('Y'), $date->format('m'), $date->format('d') ]),
            'weekDayNames' => [],
            'calendar'     => [],
            'categories'   => EventCategory::where('id', '!=', 1)->get()->keyBy('id')->toArray(),
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
                'fullDate'   => $curDate->format('Y-m-d'),
                'day'        => $curDate->format('j'),
                'class'      => $classes,
                'createLink' => route('calendar.createDate', [ $curDate->format('Y'), $curDate->format('m'), $curDate->format('d') ]),
                'link'       => route('calendar.day', [ $curDate->format('Y'), $curDate->format('m'), $curDate->format('d') ]),
                'events'     => [],
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
     * Get the calendar params for a week
     *
     * @param  Carbon\Carbon $date
     * @return array
     */
    public function getCalendarWeek(Carbon $date)
    {
        $params = [
            'header'       => $date->format('M Y'),
            'dayLink'      => route('calendar.day', [ $date->format('Y'), $date->format('m'), $date->format('d') ]),
            'weekLink'     => route('calendar.week', [ $date->format('Y'), $date->format('m'), $date->format('d') ]),
            'monthLink'    => route('calendar.month', [ $date->format('Y'), $date->format('m'), $date->format('d') ]),
            'weekDayNames' => [],
            'calendar'     => [],
            'categories'   => EventCategory::where('id', '!=', 1)->get()->keyBy('id')->toArray(),
        ];

        $nextDate = $date->copy()->addWeek();
        $prevDate = $date->copy()->subWeek();

        $params['previousLink'] = route('calendar.week', [ $prevDate->format('Y'), $prevDate->format('m'), $prevDate->format('d') ]);
        $params['nextLink']     = route('calendar.week', [ $nextDate->format('Y'), $nextDate->format('m'), $nextDate->format('d') ]);

        // Get the week day names
        for ($w = 0; $w <= 6; $w++)
        {
            $params['weekDays'][] = [
                'name' => $this->daysOfWeekLkup[($w + $this->startOfWeek) % 7],
            ];
        }

        $startOfCalendar = $date->copy()->startOfWeek($this->startOfWeek);
        $endOfCalendar   = $date->copy()->endOfWeek($this->endOfWeek);

        $curDate = $startOfCalendar->copy();

        $i = 0;
        while ($curDate <= $endOfCalendar)
        {
            $day = $curDate->format('Y-m-d');

            $params['weekDays'][$i]['day']  = $curDate->format('j');
            $params['weekDays'][$i]['link'] = route('calendar.day', [ $curDate->format('Y'), $curDate->format('m'), $curDate->format('d') ]);

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
                'fullDate' => $day,
                'day'      => $curDate->format('j'),
                'class'    => $classes,
                'events'   => [],
            ];

            for ($h = 0; $h <= 24; $h++)
            {
                $hour = $this->fixHour($h);

                $params['calendar'][$day][$hour] = ['events' => [],];
            }

            $curDate->addDay();
            $i++;
        }

        // Get events
        $events = $this->getEvents($date);

        foreach ($events as $day => $dayEvents)
        {
            foreach ($dayEvents as $event)
            {
                $hour = '00';

                if (!is_null($event['time_start']))
                {
                    $hour = substr($event['time_start'], 0, 2);
                }

                if (isset($params['calendar'][$day]))
                {
                    $params['calendar'][$day][$hour]['events'][] = $event;
                }
            }
        }

        return $params;
    }

    /**
     * Get the calendar params for a day
     *
     * @param  Carbon\Carbon $date
     * @return array
     */
    public function getCalendarDay(Carbon $date)
    {
        $params = [
            'header'       => $date->format('M Y'),
            'dayLink'      => route('calendar.day', [ $date->format('Y'), $date->format('m'), $date->format('d') ]),
            'weekLink'     => route('calendar.week', [ $date->format('Y'), $date->format('m'), $date->format('d') ]),
            'monthLink'    => route('calendar.month', [ $date->format('Y'), $date->format('m'), $date->format('d') ]),
            'weekDayNames' => [],
            'calendar'     => [],
            'categories'   => EventCategory::where('id', '!=', 1)->get()->keyBy('id')->toArray(),
        ];

        $nextDate = $date->copy()->addDay();
        $prevDate = $date->copy()->subDay();

        $params['previousLink'] = route('calendar.day', [ $prevDate->format('Y'), $prevDate->format('m'), $prevDate->format('d') ]);
        $params['nextLink']     = route('calendar.day', [ $nextDate->format('Y'), $nextDate->format('m'), $nextDate->format('d') ]);

        $curDate = $date->copy();

        // Get the week day names
        $params['weekDays'][] = [
            'name' => $this->daysOfWeekLkup[$curDate->format('w')],
            'day'  => $curDate->format('j'),
            'link' => route('calendar.day', [ $curDate->format('Y'), $curDate->format('m'), $curDate->format('d') ]),
        ];

        $day = $curDate->format('Y-m-d');

        $dayData = [
            'fullDate' => $day,
            'day'      => $curDate->format('j'),
            'class'    => 'today',
            'events'   => [],
        ];

        for ($h = 0; $h <= 24; $h++)
        {
            $hour = $this->fixHour($h);

            $params['calendar'][$day][$hour] = ['events' => [],];
        }

        // Get events
        $events = $this->getEvents($date);

        foreach ($events as $day => $dayEvents)
        {
            foreach ($dayEvents as $event)
            {
                $hour = '00';

                if (!is_null($event['time_start']))
                {
                    $hour = substr($event['time_start'], 0, 2);
                }

                if (isset($params['calendar'][$day]))
                {
                    $params['calendar'][$day][$hour]['events'][] = $event;
                }
            }
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
        $birthdays = User::where('birthday', 'like', '%%%%-'.$date->format('m').'-%%')
            ->get();

        foreach ($birthdays as $b)
        {
            if ($endOfCalendar->lt($b->birthday))
            {
                continue;
            }

            $bMonth = $b->birthday->format('m');
            $bDay   = $b->birthday->format('d');

            $age = $endOfCalendar->diff($b->birthday)->format('%y');

            $desc = sprintf(_ngettext('Turns %d year old today.', 'Turns %d years old today.', $age), $age);
            if ($age == 0)
            {
                $desc = _gettext('Was born today.');
            }

            $dateKey = $endOfCalendar->format('Y').'-'.$bMonth.'-'.$bDay;

            $formattedEvents[$dateKey][] = [
                'id'                => 0,
                'isBirthday'        => true,
                'date'              => $b->birthday->format('Y-m-d'),
                'time_start'        => null,
                'time_end'          => null,
                'title'             => getUserDisplayName($b->toArray()),
                'desc'              => $desc,
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

    /**
     * Make sure the hour number is 2 digits
     *
     * @param string $hour
     * @return string
     */
    private function fixHour($hour)
    {
        return str_pad($hour, 2, 0, STR_PAD_LEFT);
    }
}
