@extends('layouts.main')
@section('body-id', 'profile')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col-auto col-3 p-5">
        <ul class="list-unstyled float-end">
            <li class="active">
                <a class="text-decoration-none" href="{{ route('my.profile') }}">General</a>
            </li>
            <li class="">
                <a class="text-decoration-none" href="{{ route('my.avatar') }}">Picture</a>
            </li>
            <li class="">
                <a class="text-decoration-none" href="{{ route('my.address') }}">Address</a>
            </li>
        </ul>
    </div>
    <div class="col border-start min-vh-100 p-5">
        <form class="" action="{{ route('my.profile') }}" method="post">
            @csrf
            <h2>{{ __('Name') }}</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <h4 class="alert-heading">{{ __('An error has occurred') }}</h4>
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
            </div>
        @endif
            <div class="mb-3 required">
                <label for="name">{{ __('Full Name') }}</label>
                <input type="text" class="form-control" name="name" id="name" value="{{ old('name') }}">
            </div>
            <div class="mb-3">
                <label for="displayname">{{ __('Display Name') }}</label>
                <input type="text" class="form-control" name="displayname" id="displayname" value="{{ old('displayname') }}">
                <div class="form-text">{{ __('What do you want to be called on the site?  Leave blank if it is the same as Full Name.') }}</div>
            </div>
            <h2 class="pt-5">{{ __('Bio') }}</h2>
            <div class="mb-3">
                <textarea class="form-control" id="bio" name="bio" rows="5">{{ old('bio', $user->bio) }}</textarea>
            </div>
            <h2 class="pt-5">{{ __('Birthday') }}</h2>
            <div class="mb-3 required">
                <input type="date" class="form-control" id="bday" name="bday" value="{{ old('bday') }}">
            </div>
            <div class="text-end">
                <button class="btn btn-secondary px-5" type="submit" id="submit" name="submit">
                    <i class="bi-check-square me-1"></i>
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
