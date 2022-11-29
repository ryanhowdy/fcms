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
            <div class="row mb-3">
                <div class="col required">
                    <label for="fname" class="form-label">{{ __('First') }}</label>
                    <input type="text" class="form-control" id="fname" name="fname" value="{{ old('fname', $user->fname) }}">
                </div>
                <div class="col">
                    <label for="mname" class="form-label">{{ __('Middle') }}</label>
                    <input type="text" class="form-control" id="mname" name="mname" value="{{ old('mname', $user->mname) }}">
                </div>
                <div class="col required">
                    <label for="lname" class="form-label">{{ __('Last') }}</label>
                    <input type="text" class="form-control" id="lname" name="lname" value="{{ old('lname', $user->lname) }}">
                </div>
            </div>
            <div class="mb-3">
                <label for="maiden" class="form-label">{{ __('Maiden') }}</label>
                <input type="text" class="form-control" id="maiden" name="maiden" value="{{ old('maiden', $user->maiden) }}">
            </div>
            <h2 class="pt-5">{{ __('Bio') }}</h2>
            <div class="mb-3">
                <textarea class="form-control" id="bio" name="bio" rows="5">{{ old('bio', $user->bio) }}</textarea>
            </div>
            <h2 class="pt-5">{{ __('Birthday') }}</h2>
            <div class="d-flex flex-row mb-3">
                <select class="form-select w-auto" id="bday" name="bday">
                    @foreach($days as $d => $val)
                    <option value="{{ $d }}" {{ old('bday', $user->dob_day) == $d ? 'selected' : '' }}>{{ $val }}</option>
                    @endforeach
                </select>
                <select class="form-select w-auto" id="bmonth" name="bmonth">
                    @foreach($months as $m => $val)
                    <option value="{{ $m }}" {{ old('bmonth', $user->dob_month) == $m ? 'selected' : '' }}>{{ $val }}</option>
                    @endforeach
                </select>
                <select class="form-select w-auto" id="byear" name="byear">
                    @foreach($years as $y => $val)
                    <option value="{{ $y }}" {{ old('byear', $user->dob_year) == $y ? 'selected' : '' }}>{{ $val }}</option>
                    @endforeach
                </select>
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
