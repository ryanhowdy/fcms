@extends('layouts.main')
@section('body-id', 'calendar')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col border-end min-vh-100 p-5">

        <div class="calendar-header mb-5 d-flex justify-content-between">
            <div class="btn-group">
                <a href="{{ $dayLink }}" class="btn btn-light">{{ __('Day') }}</a>
                <a href="{{ $weekLink }}" class="btn btn-light">{{ __('Week') }}</a>
                <a href="{{ $monthLink }}" class="btn btn-light active">{{ __('Month') }}</a>
            </div>
            <div class="d-flex flex-row">
                <a href="{{ route('calendar') }}" class="btn btn-outline-secondary me-5">{{ __('Today') }}</a>
                <h2 class="mb-0 me-2">{{ $header }}</h2>
                <a class="text-secondary previous fs-4" href="{{ $previousLink }}"><i class="bi-chevron-compact-left"></i></a>
                <a class="text-secondary next fs-4" href="{{ $nextLink }}"><i class="bi-chevron-compact-right"></i></a>
            </div>
        </div>

        <div id="calendar" class="bg-light rounded-3">
            <div class="week-days row border-bottom g-0">
            @foreach ($weekDayNames as $day)
                <div class="day col p-2">{{ $day }}</div>
            @endforeach
            </div><!-- /.week-days -->
        @foreach ($calendar as $weekData)
            <div class="week row g-0">
            @foreach ($weekData as $day)
                <div class="day col p-2 border-end border-bottom {{ $day['class'] }}">
                    <a href="{{ $day['link'] }}" class="day">{{ $day['day'] }}</a>
                @foreach ($day['events'] as $e)
                    <div class="event">
                        <a class="d-block text-white rounded-1" style="background-color: {{ $e['category_color'] }}" tabindex="0"
                            data-bs-toggle="popover" data-bs-placement="top" data-bs-title="{{ $e['title'] }}" 
                            data-bs-custom-class="event-detail-popover" data-bs-content="{{ $e['desc'] }}">
                            <span class="me-2">{{ substr($e['time_start'], 0, 5) }}</span>{{ $e['title'] }}
                        </a>
                    </div>
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
