@extends('layouts.main')
@section('body-id', 'photos')

@section('content')
<div class="p-5">
    <div class="d-flex justify-content-between">
        <h2>{{ __('Photos') }}</h2>
        <div>
            <a href="{{ route('photos.create') }}" class="btn btn-success text-white">{{ __('Upload') }}</a>
        </div>
    </div>

    <div class="photo-nav d-flex justify-content-between pt-4 border-bottom">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('photos') }}">{{ __('Dashboard') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('photos.albums') }}">{{ __('Albums') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('photos.users') }}">{{ __('People') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('photos.places') }}">{{ __('Places') }}</a>
            </li>
        </ul>
        <div class="search-filter">
            <a class="me-3 text-muted" href="#"><i class="bi-search"></i></a>
            <div class="dropdown no-caret d-inline-block" data-bs-toggle="dropdown">
                <a class="dropdown-toggle text-muted" href="#"><i class="bi-sliders"></i></a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">{{ __('Top Rated') }}</a></li>
                    <li><a class="dropdown-item" href="#">{{ __('Most Viewed') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <h6 class="pt-5">{{ __('Latest') }}</h6>

@if ($albums->isEmpty())
    <x-empty-state/>
@else
    <div class="albums d-flex flex-wrap">
    @foreach ($albums as $album)
        <div class="album me-3 mb-3">
            <a href="{{ route('photos.albums.show', $album->id) }}" class="d-flex">
            @isset($album->photos[0])
                <div class="large">
                    <img class="" src="{{ route('photo.thumbnail', ['id' => $album->created_user_id, 'file' => $album->photos[0]->filename]) }}">
                </div>
                <div class="small d-flex flex-column">
                @isset($album->photos[1])
                    <img class="" src="{{ route('photo.thumbnail', ['id' => $album->created_user_id, 'file' => $album->photos[1]->filename]) }}">
                @endisset
                @isset($album->photos[2])
                    <img class="" src="{{ route('photo.thumbnail', ['id' => $album->created_user_id, 'file' => $album->photos[2]->filename]) }}">
                @endisset
                </div>
            @endisset
            </a>
            <div class="description p-1">
                <b>{{ $album->name }}</b>
                <p>{{ $album->description }}</p>
            </div>
        </div><!-- /.album -->
    @endforeach
    </div><!-- /.albums -->
@endif

<!--
    <div class="albums d-flex flex-wrap">
        <div class="album me-3 mb-3">
            <div class="d-flex">
                <div class="large">
                    <img src="https://picsum.photos/200?random1"/>
                </div>
                <div class="small d-flex flex-column">
                    <img src="https://picsum.photos/100?random2"/>
                    <img src="https://picsum.photos/100?random3"/>
                </div>
            </div>
            <div class="description p-1">
                <b>Landscapes</b>
                <p>A short description about landscapes</p>
            </div>
        </div>
    </div>
-->

    <h6 class="pt-5">{{ __('On This Day') }}</h6>
</div>
@endsection
