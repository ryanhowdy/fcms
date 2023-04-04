@extends('layouts.main')
@section('body-id', 'profile')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col-auto col-3 p-5">
        @section('settings.settings', 'active')
        @include('me.navigation')
    </div>
    <div class="col border-start min-vh-100 p-5">
        <form class="" action="{{ route('my.profile') }}" method="post">
            @csrf
        @if ($errors->any())
            <div class="alert alert-danger">
                <h4 class="alert-heading">{{ __('An error has occurred') }}</h4>
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
            </div>
        @endif
            <h2>{{ __('Language') }}</h2>
            <div class="mb-3 required">
                <label for="language">{{ __('Language') }}</label>
                <select id="language" name="language" class="form-select">
                    <option value="en_US">{{ __('English') }}</option>
                </select>
            </div>
            <div class="mb-3 required">
                <label for="timezone">{{ __('Timezone') }}</label>
                <select id="timezone" name="timezone" class="form-select">
                @foreach ($timezones as $timezone)
                    <option value="{{ $timezone }}" @selected(old('timezone', $user->settings->timezone) == $timezone)>
                        {{ $timezone }}
                        - ({{ \Carbon\Carbon::now($timezone)->format('g:ia') }})
                    </option>
                @endforeach
                </select>
            </div>
            <div class="pt-3">
                <button class="btn btn-success text-white px-5" type="submit" id="submit" name="submit">
                    <i class="bi-check-square me-1"></i>
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
