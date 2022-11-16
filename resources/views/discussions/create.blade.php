@extends('layouts.main')
@section('body-id', 'discussions')
@section('main-bg', 'bg-light')

@section('content')
<div class="p-5">

    <form class="p-5 border rounded bg-white" action="{{ route('discussions.create') }}" method="post">
        @csrf
        <h2 class="mb-5">Create New Discussion</h2>
    @if ($errors->any())
        <div class="alert alert-danger">
            <h4 class="alert-heading">{{ __('An error has occurred') }}</h4>
            <p>{{ __('Please fill out the required fields below.') }}</p>
        </div>
    @endif
        <div class="mb-3 required">
            <label for="title" class="form-label">{{ __('Title') }}</label>
            <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}">
        </div>
        <x-text-editor/>
    </form>

</div>
@endsection
