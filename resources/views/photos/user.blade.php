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

    @include('photos.navigation', ['active' => 'people'])

    <h6 class="pt-5">{{ $user->name }}</h6>

    <div class="d-flex flex-wrap">
@foreach ($userPhotos as $userPhoto)
    @if($userPhoto->photo != null)
        <div class="me-3 mb-3">
            <a class="d-inline-block" href="{{ route('photos.users.photo.show', ['uid' => $userPhoto->user_id, 'pid' => $userPhoto->photo_id]) }}" style="height:150px; width:150px">
                <img class="object-fit-cover h-100 w-100" src="{{ route('photo.thumbnail', ['id' => $userPhoto->photo->created_user_id, 'file' => $userPhoto->photo->filename]) }}">
            </a>
        </div>
    @endif
@endforeach
    </div>

</div>
@endsection

