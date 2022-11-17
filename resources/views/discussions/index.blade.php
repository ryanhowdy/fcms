@extends('layouts.main')
@section('body-id', 'discussions')

@section('content')
<div class="p-5">
    <div class="d-flex justify-content-between">
        <h2>{{ __('Discussions') }}</h2>
        <div>
            <a href="{{ route('discussions.create') }}" class="btn btn-success text-white">{{ __('New Discussion') }}</a>
        </div>
    </div>

@if ($discussions->isEmpty())
    <x-empty-state/>
@else
    <div class="discussions m-5 ms-0">
    @foreach($discussions as $d)
        <div class="discussion d-flex justify-content-between pb-4 border-bottom">
            <div class="d-flex flex-row p-3">
                <div>
                    <img class="avatar rounded-5 mx-3" src="{{ route('avatar', Auth()->user()->avatar) }}" title="{{ __('avatar') }}">
                </div>
                <div>
                    <a href="{{ route('discussions.show', $d->id) }}" class="h3 py-1 d-block text-black text-decoration-none">{{ $d->title }}</a>
                    <b class="text-primary me-3">{{ getUserDisplayName($d->toArray()) }}</b><span class="text-muted">{{ $d->updated_at->diffForHumans() }}</span>
                    <div class="details d-flex flex-row mt-4">
                        <div class="d-inline-block border p-2 me-5">
                            <i class="bi-chat-square me-2"></i>
                            0 replies
                        </div>
                        <div class="d-inline-block border p-2">
                            <i class="bi-eye me-2"></i>
                            {{ trans_choice('{0} :count views|{1} :count view|[2,*] :count views', $d->views, ['count' => $d->views]) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-3">
                <i class="bi-bookmark-star-fill fs-4 text-primary"></i>
            </div>
        </div><!-- /.discussion -->
    @endforeach
    </div><!-- /.discussions -->
    {{ $discussions->links() }}
@endif
</div>
@endsection
