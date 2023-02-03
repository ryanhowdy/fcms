@extends('layouts.main')
@section('body-id', 'news')

@section('content')
<div class="p-5">
    <div class="d-flex justify-content-between">
        <h2>{{ $news->title }}</h2>
        <div class="d-flex justify-content-end align-items-start">
            <a href="javascript: history.go(-1)" class="btn me-3 btn-light">{{ __('Back') }}</a>
            <div class="dropdown no-caret">
                <i class="bi-three-dots-vertical dropdown-toggle" data-bs-toggle="dropdown"></i>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Edit</a></li>
                    <li><a class="dropdown-item text-danger" href="#">Delete</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="pt-5 w-75 lh-lg">
        {!! $news->news !!}
    </div>

    <div class="comments mx-5">
    @foreach($comments as $c)
        <div class="comment d-flex justify-content-between align-items-start py-5 border-bottom">
            <div class="d-flex flex-row">
                <div>
                    <img class="avatar rounded-5 mx-3" src="{{ getUserAvatar(Auth()->user()->toArray()) }}" title="{{ __('avatar') }}">
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
        </div><!-- /.comment -->
    @endforeach
    </div><!-- /.comments -->

    <form id="reply-comment" class="text-editor mt-5 mx-5" action="{{ route('familynews.comments.store', $news->id) }}" method="post">
        @csrf
        <input type="hidden" name="news_id" value="{{ $news->id }}"/>

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
