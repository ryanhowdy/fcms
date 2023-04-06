@extends('layouts.main')
@section('body-id', 'contact')
@section('main-bg', 'bg-light')

@section('content')
<div class="p-5">
    <form class="p-5 border rounded bg-white" action="{{ route('contact') }}" method="post">
        @csrf
        <h2 class="mb-5">{{ _gettext('Contact') }}</h2>
    @if ($errors->any())
        <div class="alert alert-danger">
            <h4 class="alert-heading">{{ _gettext('An error has occurred') }}</h4>
            <p>{{ _gettext('Please fill out the required fields below.') }}</p>
        </div>
    @endif
        <div class="mb-3">
            <label for="subject" class="form-label">{{ _gettext('Subject') }}</label>
            <input type="text" class="form-control" id="subject" name="subject" value="{{ old('subject') }}">
        </div>
        <div class="mb-3">
            <label for="message" class="form-label">{{ _gettext('Message') }}</label>
            <textarea class="form-control" id="message" name="message" rows="5">{{ old('message') }}</textarea>
        </div>
        <div class="text-end">
            <button class="btn btn-secondary px-5" type="submit" id="submit" name="submit">
                {{ _gettext('Send') }}
                <i class="bi-send-fill ps-2"></i>
            </button>
        </div>
    </form>
</div>
@endsection
