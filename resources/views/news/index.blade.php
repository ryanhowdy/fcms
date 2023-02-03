@extends('layouts.main')
@section('body-id', 'home')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col border-end min-vh-100 p-5 position-relative">

        <div class="position-absolute end-0 top-0 me-5 mt-5">
            <a href="{{ route('familynews.create') }}" class="btn btn-success text-white">{{ __('Add News') }}</a>
        </div>

@if ($news->isEmpty())
    <p>&nbsp;</p>
    <x-empty-state/>
@else
        <h3>{{ __('Recent') }}</h3>
        <div class="recent-news d-flex flex-wrap border-bottom pb-5">
        @foreach ($recent as $i => $n)
            <div class="d-flex my-3 position-relative">
                <div>
                    <img src="https://picsum.photos/200/200?random=1"/>
                </div>
                <div class="details pt-1 p-3">
                    <h5 class="title">{{ $n['title'] }}</h5>
                    <a href="{{ route('familynews.show', $n['id']) }}" class="summary text-decoration-none text-dark stretched-link">{{ $n['summary'] }}</a>
                    <div class="user">{{ getUserDisplayName($n) }}</div>
                    <div class="date text-muted">{{ $n['human_time'] }}</div>
                </div>
            </div>
        @endforeach
        </div><!-- /.recent-news -->

        <ul class="nav nav-pills mt-5">
            <li class="nav-item">
                <a class="nav-link active" href="#">{{ __('My News') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">{{ __('Popular') }}</a>
            </li>
        </ul>

        <div class="news d-flex flex-wrap">
        @foreach ($news as $i => $n)
            <div class="d-flex my-3 position-relative">
                <div>
                    <img src="https://picsum.photos/200/200?random=1"/>
                </div>
                <div class="details pt-1 p-3">
                    <h5 class="title">{{ $n->title }}</h5>
                    <a href="{{ route('familynews.show', $n->id) }}" class="summary text-decoration-none text-dark stretched-link">{{ $n->summary }}</a>
                    <div class="user">{{ getUserDisplayName($n->toArray()) }}</div>
                    <div class="date text-muted">{{ $n->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @endforeach
        </div><!-- /.news -->
@endif

    </div>
    <div class="col-auto col-3 p-5">
        <h6 class="mb-4">{{ __('Latest news from') }}</h6>
        <div class="vstack gap-3">
            <div class="">
                <a class="text-decoration-none text-dark" href="#">
                    <img class="avatar rounded-5 me-3" src="{{ getUserAvatar(Auth()->user()->toArray()) }}" title="{{ __('avatar') }}">
                    Ryan
                </a>
            </div>
        </div>
    </div>
</div>
<style>
.recent-news > div
{
    height: 200px;
}
.recent-news .title
{
    height: 24px;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
    width: 300px;
}
.recent-news .summary
{
    display: block;
    height: 100px;
    overflow: hidden;
    width: 300px;
}
.recent-news .date
{
    font-size: 0.8rem;
}
</style>
@endsection
