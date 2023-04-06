@extends('layouts.main')
@section('body-id', 'calendar')
@section('main-bg', 'bg-light')

@section('content')
<div class="p-5">
    
    <form class="p-5 border rounded bg-white" action="{{ route('calendar.create') }}" method="post">
        @csrf
        <h2 class="mb-3">{{ _gettext('Create New Event') }}</h2>
    @if ($errors->any())
        <div class="alert alert-danger">
            <h4 class="alert-heading">{{ _gettext('An error has occurred') }}</h4>
            <p>{{ _gettext('Please fill out the required fields below.') }}</p>
        </div>
    @endif
        <div class="mb-3 required">
            <label for="title" class="form-label">{{ _gettext('Title') }}</label>
            <input type="text" class="w-auto form-control" id="title" name="title" value="{{ old('title') }}">
        </div>
        <div class="mb-3 required">
            <label for="date" class="form-label">{{ _gettext('Date') }}</label>
            <div class="d-flex">
                <div class="row">
                    <div class="col-6">
                        <input type="date" class="form-control" id="date" name="date" value="{{ old('date') }}">
                    </div>
                    <div class="col-3">
                        <input type="time" class="form-control" id="start" name="start" value="{{ old('start') }}">
                    </div>
                    <div class="col-3">
                        <input type="time" class="form-control" id="end" name="end" value="{{ old('end') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="more pt-3 d-none">

            <div class="mb-3 d-flex">
                <span class="bi-tag text-muted fs-3 me-3"></span>
                <div>
                    <select class="form-select" id="category" name="category">
                        <option value="1"></option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3 d-flex">
                <span class="bi-repeat text-muted fs-3 me-3"></span>
                <div class="">
                    <input class="btn-check" type="checkbox" value="1" id="repeat-yearly" name="repeat-yearly">
                    <label class="btn btn-outline-purple" for="repeat-yearly">{{ _gettext('Repeat Every Year') }}</label>
                </div>
            </div>
            <div class="mb-3 d-flex">
                <span class="bi-lock text-muted fs-3 me-3"></span>
                <div class="">
                    <input class="btn-check" type="checkbox" value="1" id="private" name="private">
                    <label class="btn btn-outline-purple" for="private">{{ _gettext('Private') }}</label>
                </div>
            </div>
            <div class="mb-3 d-flex">
                <span class="bi-people text-muted fs-3 me-3"></span>
                <div class="">
                    <input class="btn-check" type="checkbox" value="1" id="invite" name="invite">
                    <label class="btn btn-outline-purple" for="invite">{{ _gettext('Invite Guests') }}</label>
                </div>
            </div>

        </div><!-- /.more -->

        <div class="pt-5">
            <button class="btn btn-primary px-5 me-3 text-white" type="submit" id="submit" name="submit">
                {{ _gettext('Save') }}
            </button>
            <a href="#" class="btn btn-secondary">{{ _gettext('More Options') }}</a>
        </div>
    </form>

</div>
<script>
$(function() {
    $('.btn-secondary').click(function(e) {
        e.preventDefault();
        $('.more').toggleClass('d-none');
    });
});
</script>
@endsection
