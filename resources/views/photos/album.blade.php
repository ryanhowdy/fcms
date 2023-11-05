@extends('layouts.main')
@section('body-id', 'photos')

@section('content')
<div class="p-5">
    <div class="d-flex justify-content-between">
        <h2>{{ _gettext('Photos') }}</h2>
        <div>
            <a href="{{ route('photos.create') }}" class="btn btn-success text-white">{{ _gettext('Upload') }}</a>
        </div>
    </div>

    @include('photos.navigation', ['active' => 'albums'])

@if (empty($album))
    <x-empty-state/>
@else
    <div class="album row">
        <div class="col-xl-9 col-12">
            <h3 class="text-primary pt-5">{{ $album->album_name }}</h3>
            <p class="text-muted mb-1">
                {{ sprintf(_gettext('Created %s'), $album->created_at->format('M j, Y')) }} - 
                {{ sprintf(_ngettext('%d photo', '%d photos', count($album->photos)), count($album->photos)) }}
            </p>
            <p>{{ getUserDisplayName($album->toArray()) }}</p>
            <div class="photos d-flex">
                <a href="{{ route('photos.show', ['aid' => $album->id, 'pid' => $album->photos[0]->id]) }}" class="large">
                    <img class="ps-0 p-2" src="{{ route('photo.thumbnail', ['id' => $album->photos[0]->created_user_id, 'file' => $album->photos[0]->filename]) }}">
                </a>
                <div class="d-flex flex-wrap">
            @foreach ($album->photos as $k => $photo)
                @if ($k == 0) @continue @endif
                    <a class="d-inline-block p-2" href="{{ route('photos.show', ['aid' => $album->id, 'pid' => $photo->id]) }}" style="height:100px; width:100px">
                        <img class="object-fit-cover h-100 w-100" src="{{ route('photo.thumbnail', ['id' => $photo->created_user_id, 'file' => $photo->filename]) }}">
                    </a>
            @endforeach
                </div>
            </div><!-- /.photos -->

            <h5 class="pt-5">{{ _gettext('Details') }}</h5>
            <div class="row details">
                <div class="col-6">
                    <p class="text-muted">{{ _gettext('Description') }}:</p>
                    <p>{{ $album->description }}</p>
                </div>
                <div class="col-3">
                    <p class="text-muted">{{ _gettext('People') }}:</p>
                @isset($album->users)
                    <div class="d-flex">
                    @foreach($album->users as $u)
                        <div>
                            <img class="avatar rounded-5 me-3" src="{{ getUserAvatar($u->toArray()) }}" title="{{ getUserDisplayName($u->toArray()) }}">
                        </div>
                    @endforeach
                    </div>
                @endisset
                </div>
                <div class="col-3">
                    <p class="text-muted">{{ _gettext('Places') }}:</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-12">
            <h5 class="text-primary fw-normal pt-5">{{ _gettext('Comments') }}</h3>
    @isset($album->comments)
            <div class="comments">
        @foreach($album->comments as $c)
                <div class="comment py-4">
                    <div class="d-flex flex-row">
                        <div>
                            <img class="avatar rounded-5 mx-3" src="{{ getUserAvatar($c->toArray()) }}" title="{{ _gettext('avatar') }}">
                        </div>
                        <div>
                            <div class="mb-2">
                                <b class="me-3">{{ getUserDisplayName($c->toArray()) }}</b><span class="text-primary">{{ $c->updated_at->diffForHumans() }}</span>
                            </div>
                            <div class="">
                                {!! cleanUserComments($c->comments) !!}
                            </div>
                        </div>
                    </div>
                </div><!-- /.comment -->
        @endforeach
            </div>
    @endisset
        </div>
    </div>
@endif
</div>
@endsection
