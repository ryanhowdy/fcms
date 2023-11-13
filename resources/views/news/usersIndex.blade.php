@extends('layouts.main')
@section('body-id', 'news')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col border-end min-vh-100 p-5 position-relative">

        <div class="d-flex justify-content-between">
            <h2>{{ getUserDisplayName($user->toArray()) }}</h2>
            <div class="d-flex justify-content-end align-items-start">
                <a href="javascript: history.go(-1)" class="btn me-3 btn-light">{{ _gettext('Back') }}</a>
            </div>
        </div>

    @if ($news->isEmpty())
        <p>&nbsp;</p>
        <x-empty-state/>
    @else
        <div class="news">
        @foreach ($news as $i => $n)
            <div class="d-flex my-3 position-relative">
                <div>
                    <img src="https://picsum.photos/200/200?random={{ $n->id }}"/>
                </div>
                <div class="details pt-1 p-3">
                    <div class="d-flex">
                        <h5 class="title">{{ $n->title }}</h5>
                        <div class="date text-muted ps-3">{{ $n->formattedTime }}</div>
                    </div>
                    <a href="{{ route('familynews.show', $n->id) }}" class="summary text-decoration-none text-dark stretched-link">
                        {!! cleanUserComments($n->summary, true) !!}
                    </a>
                    <div class="user">
                        <img class="avatar rounded-5 me-1" src="{{ getUserAvatar($n->toArray()) }}" title="{{ _gettext('avatar') }}">
                        {{ getUserDisplayName($n->toArray()) }}
                    </div>
                </div>
            </div>
        @endforeach
        </div><!-- /.news -->
    @endif

    </div>
    <div class="col-auto col-3 p-5">
        <h6 class="mb-4">{{ _gettext('Latest news from') }}</h6>
        <div class="vstack gap-3">
        @foreach($users as $u)
            <div class="">
                <a class="text-decoration-none text-dark" href="{{ route('familynews.users.index', $u->id) }}">
                    <img class="avatar rounded-5 me-3" src="{{ getUserAvatar($u->toArray()) }}" title="{{ _gettext('avatar') }}">
                    {{ getUserDisplayName($u->toArray()) }}
                </a>
            </div>
        @endforeach
        </div>
    </div>
</div>
@endsection
