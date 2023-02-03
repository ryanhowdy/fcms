@extends('layouts.main')
@section('body-id', 'home')
@section('main-bg', 'bg-light')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col border-end min-vh-100 p-5">
        <form class="mb-5">
            <div class="share-box position-relative">
                <img class="avatar rounded-5 position-absolute" src="{{ getUserAvatar(Auth()->user()->toArray()) }}" title="{{ __('avatar') }}">
                <input class="form-control" type="text" placeholder="Have something to share?">
            </div>
        </form>

    @for($index = 0; $index < $updates->count(); $index++)
        @php($update = $updates[$index])
        <div class="card mb-3">
            <div class="card-body">
                <div class="">
                    <img class="avatar rounded-5 float-start me-3" src="{{ getUserAvatar($update->toArray()) }}">
        @switch($update->type)
            @case('ADDRESS_ADD')
                    {{ trans(':name has added a new address.', [ 'name' => getUserDisplayName($update->toArray()) ]) }}<br/>
                @break
            @case('DISCUSSION')
                    {{ trans(':name has started a new discussion.', [ 'name' => getUserDisplayName($update->toArray()) ]) }}<br/>
                @break
            @case('PHOTOS')
                    {{ trans(':name has added some new photos.', [ 'name' => getUserDisplayName($update->toArray()) ]) }}<br/>
                @break
            @case('NEW_USER')
                    {{ trans(':name has joined the site.', [ 'name' => getUserDisplayName($update->toArray()) ]) }}<br/>
                @break
        @endswitch
                    <small class="text-muted" title="{{ $update->updated_at }}">{{ $update->updated_at->diffForHumans() }}</small>
                </div>
                <div class="border-top mt-3 pt-3 ps-5">
        @switch($update->type)
            @case('ADDRESS_ADD')
            @case('NEW_USER')
                @break
            @case('DISCUSSION')
                <a href="{{ route('discussions.show', $update->id) }}">
                    <h5 class="card-title">{{ $update->title }}</h5>
                </a>
                @break
            @case('PHOTOS')
                @php($skip = 0)
                @while($update->title == $updates[$index + $skip]->title)
                <img class="" src="{{ route('photo.thumbnail', ['id' => $updates[$index + $skip]->updated_user_id, 'file' => $updates[$index + $skip]->id]) }}">
                    @php($skip++)
                @endwhile
                @php($index += $skip -1)
                @break
            @default
                <h5 class="card-title">{{ $update->title }}</h5>
                <p>{{ $update->comments }}</p>
        @endswitch
                </div>
            </div>
        </div>
    @endfor
    </div>
    <div class="col-auto col-3 p-5">
        right sidebar
    </div>
</div>
@endsection
