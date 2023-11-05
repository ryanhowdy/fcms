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

    @include('photos.navigation', ['active' => 'dashboard'])

    <h6 class="pt-5">{{ _gettext('Latest') }}</h6>

@if ($albums->isEmpty())
    <x-empty-state/>
@else
    <div class="albums d-flex flex-wrap">
    @foreach ($albums as $album)
        <div class="album me-3 mb-3">
            <a href="{{ route('photos.albums.show', $album->id) }}" class="d-flex">
            @isset($album->photos[0])
                <div class="large">
                    <img class="" src="{{ route('photo.thumbnail', ['id' => $album->photos[0]->created_user_id, 'file' => $album->photos[0]->filename]) }}">
                </div>
                <div class="small d-flex flex-column">
                @isset($album->photos[1])
                    <img class="" src="{{ route('photo.thumbnail', ['id' => $album->photos[1]->created_user_id, 'file' => $album->photos[1]->filename]) }}">
                @endisset
                @isset($album->photos[2])
                    <img class="" src="{{ route('photo.thumbnail', ['id' => $album->photos[2]->created_user_id, 'file' => $album->photos[2]->filename]) }}">
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

    <h6 class="pt-5">{{ _gettext('On This Day') }}</h6>
</div>
@endsection
