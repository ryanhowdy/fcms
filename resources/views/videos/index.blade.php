@extends('layouts.main')
@section('body-id', 'video')

@section('content')
<div class="p-5">

    <div class="d-flex justify-content-between">
        <h2>{{ gettext('Videos') }}</h2>
        <div>
            <a href="{{ route('videos.create') }}" class="btn btn-success text-white">{{ gettext('Upload') }}</a>
        </div>
    </div>

@if ($videos->isEmpty())
    <x-empty-state/>
@else
    <div class="videos d-flex flex-wrap">
    @foreach ($videos as $video)
        <div class="video me-3 mb-3">
            <div class="description p-1">
                <a href="{{ route('videos.show', $video->id) }}" class="d-flex">
                    {{ $video->title }}
                </a>
                <p>{{ $video->description }}</p>
            </div>
        </div><!-- /.video -->
    @endforeach
    </div><!-- /.videos -->
@endif

</div>
@endsection
