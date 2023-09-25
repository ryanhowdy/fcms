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

    <div class="photo-nav d-flex justify-content-between pt-4 border-bottom">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('photos') }}">{{ _gettext('Dashboard') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('photos.albums') }}">{{ _gettext('Albums') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('photos.users') }}">{{ _gettext('People') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('photos.places') }}">{{ _gettext('Places') }}</a>
            </li>
        </ul>
        <div class="search-filter">
            <a class="me-3 text-muted" href="#"><i class="bi-search"></i></a>
            <div class="dropdown no-caret d-inline-block" data-bs-toggle="dropdown">
                <a class="dropdown-toggle text-muted" href="#"><i class="bi-sliders"></i></a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">{{ _gettext('Top Rated') }}</a></li>
                    <li><a class="dropdown-item" href="#">{{ _gettext('Most Viewed') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

@if (empty($album))
    <x-empty-state/>
@else
    <div class="album">
        <h3 class="text-primary pt-5">{{ $album->album_name }}</h3>
        <p class="text-muted mb-1">
            {{ sprintf(_gettext('Created %s'), $album->created_at->format('M j, Y')) }} - 
            {{ sprintf(_ngettext('%d photo', '%d photos', count($album->photos)), count($album->photos)) }}
        </p>
        <p>{{ getUserDisplayName($album->toArray()) }}</p>
        <div class="photos d-flex">
            <a href="{{ route('photos.show', ['aid' => $album->id, 'pid' => $album->photos[0]->id]) }}" class="large">
                <img class="ps-0 p-2" src="{{ route('photo.thumbnail', ['id' => $album->photos[0]->created_user_id, 'file' => $album->photos[0]->filename]) }}">
            </a>
            <div class="small">
        @foreach ($album->photos as $k => $photo)
            @if ($k == 0) @continue @endif
                <a href="{{ route('photos.show', ['aid' => $album->id, 'pid' => $photo->id]) }}">
                    <img class="p-2" src="{{ route('photo.thumbnail', ['id' => $photo->created_user_id, 'file' => $photo->filename]) }}">
                </a>
        @endforeach
            </div>
        </div><!-- /.photos -->

        <h5 class="pt-5">{{ _gettext('Details') }}</h5>
        <div class="row details">
            <div class="col-6">
                <p class="text-muted">{{ _gettext('Description') }}:</p>
                <p>{{ $album->description }}</p>
            </div>
            <div class="col-3">
                <p class="text-muted">{{ _gettext('People') }}:</p>
            </div>
            <div class="col-3">
                <p class="text-muted">{{ _gettext('Places') }}:</p>
            </div>
        </div>
    </div>
@endif
</div>
@endsection
