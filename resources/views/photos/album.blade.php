@extends('layouts.main')
@section('body-id', 'photos')

@section('content')
<div class="p-5">
    <div class="d-flex justify-content-between">
        <h2>{{ gettext('Photos') }}</h2>
        <div>
            <a href="{{ route('photos.create') }}" class="btn btn-success text-white">{{ gettext('Upload') }}</a>
        </div>
    </div>

    <div class="photo-nav d-flex justify-content-between pt-4 border-bottom">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('photos') }}">{{ gettext('Dashboard') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('photos.albums') }}">{{ gettext('Albums') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('photos.users') }}">{{ gettext('People') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('photos.places') }}">{{ gettext('Places') }}</a>
            </li>
        </ul>
        <div class="search-filter">
            <a class="me-3 text-muted" href="#"><i class="bi-search"></i></a>
            <div class="dropdown no-caret d-inline-block" data-bs-toggle="dropdown">
                <a class="dropdown-toggle text-muted" href="#"><i class="bi-sliders"></i></a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">{{ gettext('Top Rated') }}</a></li>
                    <li><a class="dropdown-item" href="#">{{ gettext('Most Viewed') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

@if ($album->isEmpty())
    <x-empty-state/>
@else
    <div class="album">
        <h3 class="text-primary pt-5">{{ $album[0]->name }}</h3>
        <p class="text-muted mb-1">
            {{ sprintf(gettext('Created %s'), $album[0]->created_at->format('M j, Y')]) }} - 
            {{ sprintf(ngettext('%d photo', '%d photos', 10), 10) }}
        </p>
        <p>{{ getUserDisplayName($album[0]->toArray()) }}</p>
        <div class="photos d-flex">
            <a href="{{ route('photos.show', ['aid' => $album[0]->id, 'pid' => $album[0]->photos[0]->id]) }}" class="large">
                <img class="ps-0 p-2" src="{{ route('photo.thumbnail', ['id' => $album[0]->created_user_id, 'file' => $album[0]->photos[0]->filename]) }}">
            </a>
            <div class="small">
        @foreach ($album[0]->photos as $k => $photo)
            @if ($k == 0) @continue @endif
                <a href="{{ route('photos.show', ['aid' => $album[0]->id, 'pid' => $photo->id]) }}">
                    <img class="p-2" src="{{ route('photo.thumbnail', ['id' => $album[0]->created_user_id, 'file' => $photo->filename]) }}">
                </a>
        @endforeach
            </div>
        </div><!-- /.photos -->

        <h5 class="pt-5">{{ gettext('Details') }}</h5>
        <div class="row details">
            <div class="col-6">
                <p class="text-muted">{{ gettext('Description') }}:</p>
                <p>{{ $album[0]->description }}</p>
            </div>
            <div class="col-3">
                <p class="text-muted">{{ gettext('People') }}:</p>
            </div>
            <div class="col-3">
                <p class="text-muted">{{ gettext('Places') }}:</p>
            </div>
        </div>
    </div>
@endif
</div>
@endsection
