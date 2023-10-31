@extends('layouts.main')
@section('body-id', 'photos')

@section('content')
<div class="p-5">
    <div class="d-flex justify-content-between">
        <h2>{{ _gettext('Photos') }}</h2>
        <div>
            <a href="{{ route('photos.create') }}" class="btn btn-success text-white">{{ _gettext('Upload') }}</a>
        </div>
    </div>

    @include('photos.navigation', ['active' => 'people'])

    <h6 class="pt-5">{{ _gettext('Latest') }}</h6>

@if ($users->isEmpty())
    <x-empty-state/>
@else
    <div class="d-flex flex-wrap">
    @foreach ($users as $user)
        <div class="border p-3 me-3 mb-3" style="width:300px">
            <a href="{{ route('photos.users.show', $user->id) }}" class="d-block text-dark text-decoration-none">
                <img class="avatar rounded-5 me-3" src="{{ getUserAvatar($user->toArray()) }}" title="{{ getUserDisplayName($user->toArray()) }}">
                <span>{{ $user->name }}</span>
            </a>
        </div>
    @endforeach
    </div>
@endif

</div>
@endsection

