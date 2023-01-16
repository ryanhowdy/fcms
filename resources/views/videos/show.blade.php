@extends('layouts.main')
@section('body-id', 'video')

@section('content')
<div class="p-5">

    <div class="mb-3">
        <h2>{{ $video->title }}</h2>
        <p>
            {{ $video->description }}
        </p>
    </div>

    <div class="w-75">
        <video class="video-js" controls preload="auto" data-setup='{"fluid": true}'>
            <source src="{{ route('video', ['id' => $video->created_user_id, 'file' => $video->filename ]) }}" type="video/mp4">
            <p class="vjs-no-js">
                To view this video please enable JavaScript, and consider upgrading to a web browser that
                <a href="https://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
            </p>
        </video>
    </div>

    <div class="comments my-5">
        <h4>{{ __('Comments') }}</h4>
    @foreach($comments as $c)
        <div class="comment d-flex justify-content-between align-items-start py-5 border-bottom">
            <div class="d-flex flex-row">
                <div>
                    <img class="avatar rounded-5 mx-3" src="{{ route('avatar', Auth()->user()->avatar) }}" title="{{ __('avatar') }}">
                </div>
                <div>
                    <p>
                        <b class="me-3">{{ getUserDisplayName($c->toArray()) }}</b><span class="text-indigo">{{ $c->updated_at->diffForHumans() }}</span>
                    </p>
                    <div>
                        {{ $c->comments }}
                    </div>
                </div>
            </div>
            <div class="mini-toolbar position-relative">
                <div class="buttons mb-1 p-1 border rounded-1 d-flex flex-row">
                    <i class="button p-1 mx-1 rounded-5 bi-emoji-smile"></i>
                    <i class="button p-1 mx-1 rounded-5 bi-chat-quote"></i>
                    <i class="button p-1 mx-1 rounded-5 bi-pencil"></i>
                    <i class="button p-1 mx-1 rounded-5 bi-trash3"></i>
                </div>
                <div class="reactions d-none position-absolute end-0 d-flex flex-row border rounded-5">
                    <img title="{{ __('Like') }}" src="{{ asset('img/emoji/color/1F44D.svg') }}"/>
                    <img title="{{ __('Love') }}" src="{{ asset('img/emoji/color/2764.svg') }}"/>
                    <img title="{{ __('Happy') }}" src="{{ asset('img/emoji/color/1F600.svg') }}"/>
                    <img title="{{ __('Shocked') }}" src="{{ asset('img/emoji/color/1F62E.svg') }}"/>
                    <img title="{{ __('Sad') }}" src="{{ asset('img/emoji/color/1F622.svg') }}"/>
                    <img title="{{ __('Angry') }}" src="{{ asset('img/emoji/color/1F621.svg') }}"/>
                </div>
            </div>
        </div><!-- /.comment -->
    @endforeach
    </div><!-- /.comments -->

    <form id="reply-comment" class="text-editor mt-5 mx-5" action="{{ route('videos.comments.store', $video->id) }}" method="post">
        @csrf
        <input type="hidden" name="video_id" value="{{ $video->id }}"/>

        <x-text-editor/>
    </form>

</div>
@endsection
