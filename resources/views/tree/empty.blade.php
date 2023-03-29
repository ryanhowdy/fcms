@extends('layouts.main')
@section('body-id', 'familytree')
@section('main-bg', 'bg-light')

@section('content')
<div class="p-5">
    
    <form class="p-5 border rounded bg-white" action="{{ route('familytree.store') }}" method="post">
        @csrf
        <input type="hidden" name="user_id" value="{{ $user['id'] }}">

        <h2 class="mb-3">{{ __('Start Family Tree') }}</h2>

        <div class="alert alert-info">
            <h3>{{ __('You do not have a Family Tree record yet.') }}</h3>
            <p>{{ __('Please verify the information below and make any changes or additions as needed.') }}</p>
            <p>{{ __('Notice that your Member record and Family Tree record are different.') }}</p>
        </div>

    @if ($errors->any())
        <div class="alert alert-danger w-auto">
            <h4 class="alert-heading">{{ __('An error has occurred') }}</h4>
            <p>{{ __('Please fill out the required fields below.') }}</p>
        </div>
    @endif

        <div class="d-flex mb-3">
            <div class="me-3">
                <label for="given_name" class="form-label fw-bold">{{ __('First and optional Middle Name') }}</label>
                <input type="text" class="form-control w-auto" id="given_name" name="given_name" value="{{ old('given_name', $user['given_name']) }}">
            </div>
            <div class="me-3">
                <label for="surname" class="form-label fw-bold">{{ __('Surname') }}</label>
                <input type="text" class="form-control w-auto" id="surname" name="surname" value="{{ old('surname', $user['surname']) }}">
            </div>
            <div class="me-3">
                <label for="name_suffix" class="form-label fw-bold">{{ __('Suffix') }}</label>
                <input type="text" class="form-control w-50" id="name_suffix" name="name_suffix" value="{{ old('name_suffix') }}">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">{{ __('Sex') }}</label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sex" id="sex_unknown" value="U">
                    <label class="form-check-label" for="sex_unknown">{{ __('Unknown') }}</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sex" id="sex_male" value="M">
                    <label class="form-check-label" for="sex_male">{{ __('Male') }}</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sex" id="sex_female" value="F">
                    <label class="form-check-label" for="sex_female">{{ __('Female') }}</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="sex" id="sex_other" value="O">
                    <label class="form-check-label" for="sex_other">{{ __('Not Listed or Prefer not to share') }}</label>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label for="dob" class="form-label fw-bold">{{ __('Date of Birth') }}</label>
            <input type="date" class="form-control w-auto" id="dob" name="dob" value="{{ old('dob', $user['dob']) }}">
        </div>
        <button type="submit" class="btn btn-success">{{ __('Save') }}</button>
    </form>

</div>
@endsection
