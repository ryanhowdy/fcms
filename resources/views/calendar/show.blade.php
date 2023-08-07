@extends('layouts.main')
@section('body-id', 'calendar')

@section('content')
<div class="p-5">

@if(count($invitations['all']))
    @include('calendar.invitation-header')
@else
    <div>
        <h3 class="fw-normal mb-2">{{ $event['title'] }}</h3>
        <div class="text-muted pb-2">
            <i class="bi-calendar px-2"></i>
            {{ $event['dateFormatted'] }}
        </div>
        <div class="text-muted pb-2">
            <i class="bi-clock px-2"></i>
            {{ $event['timeStartFormatted'] }}
        </div>
    @if(!is_null($event['desc']))
        <div class="pb-3">
            {{ $event['desc'] }}
        </div>
    @endif
    </div>
@endif

@if($rsvp)
    @include('calendar.invitation-form')
@endif

@if(count($invitations['all']))
    @include('calendar.invitation-list')
@endif

</div>
@endsection
