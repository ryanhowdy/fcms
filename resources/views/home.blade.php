@extends('layouts.main')
@section('body-id', 'home')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col border-end min-vh-100 p-5">
        <form>
            <div class="share-box position-relative">
                <img class="avatar rounded-5 position-absolute" src="{{ route('avatar', Auth()->user()->avatar) }}" title="{{ __('avatar') }}">
                <input class="form-control" type="text" placeholder="Have something to share?">
            </div>
        </form>
    </div>
    <div class="col-auto col-3 p-5">
        right sidebar
    </div>
</div>
@endsection
