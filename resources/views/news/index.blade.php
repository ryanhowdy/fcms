@extends('layouts.main')
@section('body-id', 'news')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col border-end min-vh-100 p-5 position-relative">

        <div class="position-absolute end-0 top-0 me-5 mt-5">
            <a href="{{ route('familynews.create') }}" class="btn btn-success text-white">{{ _gettext('Add News') }}</a>
        </div>

    @if ($news->isEmpty())
        <p>&nbsp;</p>
        <x-empty-state/>
    @else
        <h3>{{ _gettext('Recent') }}</h3>
        <div class="recent-news d-flex flex-wrap border-bottom pb-5">
        @foreach ($recent as $i => $n)
            <div class="d-flex my-3 position-relative">
                <div>
                    <img src="https://picsum.photos/200/200?random={{ $n->id }}"/>
                </div>
                <div class="details pt-1 p-3">
                    <h5 class="title">{{ $n->title }}</h5>
                    <a href="{{ route('familynews.show', $n->id) }}" class="summary text-decoration-none text-dark stretched-link">
                        {!! cleanUserComments($n->summary, true) !!}
                    </a>
                    <div class="user">
                        <img class="avatar rounded-5 me-1" src="{{ getUserAvatar($n->toArray()) }}" title="{{ _gettext('avatar') }}">
                        {{ getUserDisplayName($n->toArray()) }}
                    </div>
                    <div class="date text-muted">{{ $n->human_time }}</div>
                </div>
            </div>
        @endforeach
        </div><!-- /.recent-news -->

        <ul class="nav nav-pills mt-5">
            <li class="nav-item">
                <a class="nav-link active" href="#">{{ _gettext('My News') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">{{ _gettext('Popular') }}</a>
            </li>
        </ul>

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
