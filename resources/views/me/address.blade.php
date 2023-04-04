@extends('layouts.main')
@section('body-id', 'profile')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col-auto col-3 p-5">
        @section('profile.address', 'active')
        @include('me.navigation')
        </ul>
    </div>
    <div class="col border-start min-vh-100 p-5">
        <form class="" action="{{ route('my.address') }}" method="post">
            @csrf
            <h2>{{ __('Address') }}</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <h4 class="alert-heading">{{ __('An error has occurred') }}</h4>
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
            </div>
        @endif
            <div class="mb-3">
                <label for="country" class="form-label">{{ __('Country') }}</label>
                <select class="form-select" id="country" name="country">
                    <option></option>
                    @foreach($countries as $iso => $name)
                    <option value="{{ $iso }}" {{ old('country', $address->country) == $iso ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">{{ __('Street Address') }}</label>
                <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $address->address) }}">
            </div>
            <div class="mb-3">
                <label for="city" class="form-label">{{ __('City') }}</label>
                <input type="text" class="form-control" id="city" name="city" value="{{ old('cit', $address->city) }}">
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label for="state" class="form-label">{{ __('State') }}</label>
                    <input type="text" class="form-control" id="state" name="state" value="{{ old('state', $address->state) }}">
                </div>
                <div class="col">
                    <label for="zip" class="form-label">{{ __('Zip Code') }}</label>
                    <input type="text" class="form-control" id="zip" name="zip" value="{{ old('state', $address->zip) }}">
                </div>
            </div>
            <h2 class="pt-5">{{ __('Contacts') }}</h2>
            <div class="mb-3">
                <label for="cell" class="form-label">{{ __('Cell') }}</label>
                <input type="tel" class="form-control" id="cell" name="cell" value="{{ old('cell', $address->cell) }}">
            </div>
            <div class="mb-3">
                <label for="home" class="form-label">{{ __('Home') }}</label>
                <input type="tel" class="form-control" id="home" name="home" value="{{ old('home', $address->home) }}">
            </div>
            <div class="mb-3">
                <label for="work" class="form-label">{{ __('Work') }}</label>
                <input type="tel" class="form-control" id="work" name="work" value="{{ old('work', $address->work) }}">
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
