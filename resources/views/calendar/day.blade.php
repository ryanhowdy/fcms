@extends('layouts.main')
@section('body-id', 'calendar')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col border-end min-vh-100 p-5">

        <div class="calendar-header mb-5 d-flex justify-content-between">
            <div class="btn-group">
                <a href="{{ route('calendar.day') }}" class="btn btn-light active">{{ __('Day') }}</a>
                <a href="{{ route('calendar.week') }}" class="btn btn-light">{{ __('Week') }}</a>
                <a href="{{ route('calendar.month') }}" class="btn btn-light">{{ __('Month') }}</a>
            </div>
            <div class="d-flex flex-row">
                <a href="{{ route('calendar') }}" class="btn btn-outline-secondary me-5">{{ __('Today') }}</a>
                <h2 class="mb-0 me-2">{{ $header }}</h2>
                <a class="text-secondary previous fs-4" href="{{ $previousLink }}"><i class="bi-chevron-compact-left"></i></a>
                <a class="text-secondary next fs-4" href="{{ $nextLink }}"><i class="bi-chevron-compact-right"></i></a>
            </div>
        </div>

        <div id="calendar" class="week-view bg-light rounded-3">
            <div class="week-days row border-bottom g-0">
                <div class="time"></div>
            @foreach ($weekDays as $wk)
                <div class="day col p-2"><div>{{ $wk['name'] }}</div>{{ $wk['day'] }}</div>
            @endforeach
            </div>
        @php($key = key($calendar))
        @foreach ($calendar[$key] as $hour => $calData)
            <div class="week row g-0">
                <div class="time p-1 border-end border-bottom">{{ $hour }}:00</div>
            @foreach ($calendar as $date => $dataEvents)
                <div class="day col p-2 border-end border-bottom">
                @foreach ($calendar[$date][$hour] as $dataEvents)
                    @foreach ($dataEvents as $e)
                    <div class="event">
                        <a class="d-block text-white mb-2 rounded-1" style="background-color: {{ $e['category_color'] }}" tabindex="0" href="#"
                            data-bs-toggle="popover" data-bs-placement="top" data-bs-title="{{ $e['title'] }}" 
                            data-bs-custom-class="event-detail-popover" data-bs-content="{{ $e['desc'] }}">
                            <i>{{ $e['time_start'] }}</i>{{ $e['title'] }}
                        </a>
                    </div><!-- /.event -->
                    @endforeach
                @endforeach
                </div><!-- /.day -->
            @endforeach
            </div><!-- /.week -->
        @endforeach
        </div><!-- /#calendar -->

    </div>
    <div class="col-auto col-3 p-5">
        @include('calendar.sidebar')
    </div>
</div>
<script>
$(function() {
    $('[data-bs-toggle="popover"]').popover({ trigger: 'focus' });
});
</script>
@endsection
