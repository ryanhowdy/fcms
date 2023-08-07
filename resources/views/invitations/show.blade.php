@extends('layouts.header')

<div class="container">
    <div class="p-5">
        <img class="mb-5" src="{{ asset('img/logo.gif') }}">

        @include('calendar.invitation-header')

        @include('calendar.invitation-form')

        @include('calendar.invitation-list')

    </div>
</div>
</body>
</html>
