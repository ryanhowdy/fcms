@extends('layouts.main')
@section('body-id', 'calendar')

@section('content')
<div class="p-5">

    @include('calendar.invitation-header')

@if($rsvp)
    @include('calendar.invitation-form')
@endif

    @include('calendar.invitation-list')

</div>
@endsection
