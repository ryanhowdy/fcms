@extends('layouts.main')
@section('body-id', 'discussions')

@section('content')
<div class="p-5">
    <div class="d-flex justify-content-between pb-5 border-bottom">
        <div>
            <h2>{{ $discussion->title }}</h2>
            <span>0 replies</span>
        </div>
        <div class="d-flex justify-content-end align-items-start">
            <a href="#reply-comment" class="btn me-3 btn-success pe-5 align-start text-white">
                <i class="bi-reply-fill me-1"></i>
                {{ gettext('Reply') }}
            </a>
            <a href="javascript: history.go(-1)" class="btn me-3 btn-light">{{ gettext('Back') }}</a>
            <div class="dropdown no-caret">
                <i class="bi-three-dots-vertical dropdown-toggle" data-bs-toggle="dropdown"></i>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Edit</a></li>
                    <li><a class="dropdown-item text-danger" href="#">Delete</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="comments mx-5">
    @foreach($comments as $c)
        <div class="comment d-flex justify-content-between align-items-start py-5 border-bottom">
            <div class="d-flex flex-row">
                <div>
                    <img class="avatar rounded-5 mx-3" src="{{ getUserAvatar(Auth()->user()->toArray()) }}" title="{{ gettext('avatar') }}">
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
                    <img title="{{ gettext('Like') }}" src="{{ asset('img/emoji/color/1F44D.svg') }}"/>
                    <img title="{{ gettext('Love') }}" src="{{ asset('img/emoji/color/2764.svg') }}"/>
                    <img title="{{ gettext('Happy') }}" src="{{ asset('img/emoji/color/1F600.svg') }}"/>
                    <img title="{{ gettext('Shocked') }}" src="{{ asset('img/emoji/color/1F62E.svg') }}"/>
                    <img title="{{ gettext('Sad') }}" src="{{ asset('img/emoji/color/1F622.svg') }}"/>
                    <img title="{{ gettext('Angry') }}" src="{{ asset('img/emoji/color/1F621.svg') }}"/>
                </div>
            </div>
        </div><!-- /.comment -->
    @endforeach
    </div><!-- /.comments -->

    <form id="reply-comment" class="text-editor mt-5 mx-5" action="{{ route('discussions.comments.store', $discussion->id) }}" method="post">
        @csrf
        <input type="hidden" name="discusion_id" value="{{ $discussion->id }}"/>

        <x-text-editor/>
    </form>

<script>
$(function() {
    $('i.bi-emoji-smile').click(function () {
        $(this).parent('.buttons').next('.reactions').toggleClass('d-none');
    });
});
</script>
</div>
@endsection
