@extends('layouts.main')
@section('body-id', 'home')
@section('main-bg', 'bg-light')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col border-end min-vh-100 p-5">
        <form class="mb-5">
            <div class="share-box position-relative">
                <img class="avatar rounded-5 position-absolute" src="{{ getUserAvatar(Auth()->user()->toArray()) }}" title="{{ _gettext('avatar') }}">
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
                    {{ sprintf(_gettext('%s has added a new address.'), getUserDisplayName($update->toArray())) }}<br/>
                @break
            @case('DISCUSSION')
                    {{ sprintf(_gettext('%s has started a new discussion.'), getUserDisplayName($update->toArray())) }}<br/>
                @break
            @case('PHOTOS')
                    {{ sprintf(_gettext('%s has added some new photos.'), getUserDisplayName($update->toArray())) }}<br/>
                @break
            @case('NEW_USER')
                    {{ sprintf(_gettext('%s has joined the site.'), getUserDisplayName($update->toArray())) }}<br/>
                @break
        @endswitch
        @switch($update->type)
            @case('NEW_USER')
                    <small class="text-muted" title="{{ $update->created_at->timezone(Auth()->user()->settings->timezone)->isoFormat('lll') }}">
                        {{ $update->created_at->diffForHumans() }}
                    </small>
                @break
            @default
                    <small class="text-muted" title="{{ $update->updated_at->timezone(Auth()->user()->settings->timezone)->isoFormat('lll') }}">
                        {{ $update->updated_at->diffForHumans() }}
                    </small>
                @break
        @endswitch
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
        <div class="card mb-3">
            <div class="card-header">
                {{ _gettext('Latest Poll') }}
            </div>
            <div class="card-body">
                <h5 class="card-title">{{ $poll['question'] }}</h5>
            @if (isset($poll['current_user_voted']))
                @foreach ($poll['options'] as $option)
                    @php($percent = round(($option['total_votes'] / $poll['total_votes'] * 100), 0))
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <div class="fw-bold">{{ $option['option'] }}</div>
                        <div class="small text-muted">{{ $option['total_votes'] }}</div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $percent }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                @endforeach
            @else
                <form action="{{ route('poll.vote') }}" method="post">
                    @csrf
                    <div class="mb-3">
                    @foreach ($poll['options'] as $option)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="option" id="option-{{ $option['id'] }}" value="{{ $option['id'] }}">
                            <label class="form-check-label" for="option-{{ $option['id'] }}">{{ $option['option'] }}</label>
                        </div>
                    @endforeach
                    </div>
                    <button type="submit" class="btn btn-sm btn-info">{{ _gettext('Vote') }}</button>
                </form>
            @endif
            </div><!-- /.card-body -->
        </div><!-- /.card -->
    </div>
</div>
@endsection
