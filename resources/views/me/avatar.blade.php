@extends('layouts.main')
@section('body-id', 'profile')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col-auto col-3 p-5">
        <ul class="list-unstyled float-end">
            <li class="">
                <a class="text-decoration-none" href="{{ route('my.profile') }}">General</a>
            </li>
            <li class="active">
                <a class="text-decoration-none" href="{{ route('my.avatar') }}">Picture</a>
            </li>
            <li class="">
                <a class="text-decoration-none" href="{{ route('my.address') }}">Address</a>
            </li>
        </ul>
    </div>
    <div class="col border-start min-vh-100 p-5">
        <form class="" action="{{ route('my.avatar') }}" method="post" enctype="multipart/form-data">
            @csrf
            <h2>{{ __('Avatar') }}</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <h4 class="alert-heading">{{ __('An error has occurred') }}</h4>
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
            </div>
        @endif
            <div class="mb-3 avatar-preview text-center border">
                <div class="text-center py-5">
                    <img class="" src="{{ route('avatar', Auth()->user()->avatar) }}" title="{{ __('avatar') }}">
                </div>
                <div class="d-flex align-items-start flex-column p-3">
                    <input type="file" class="d-none" id="photo-picker" name="avatar" accept="image/*">
                    <a href="#" class="uploader"><i class="bi-plus"></i>{{ __('Upload New Picture') }}</a>
                    <a href="#" class=""><i class="bi-plus"></i>{{ __('Use Gravatar') }}</a>
                </div>
            </div>
            <div class="">
                <button class="btn btn-secondary px-5" type="submit" id="submit" name="submit">
                    <i class="bi-check-square me-1"></i>
                    {{ __('Save') }}
                </button>
                <a href="#" class="btn btn-link">{{ __('Remove Picture') }}</a>
            </div>
        </form>
    </div>
</div>
<style>
.avatar-preview
{
    width: 300px;
}
</style>
<script>
$(function() {
    // Open the file picker
    $('.uploader').click(function(e) {
        e.preventDefault();
        $('#photo-picker').trigger('click');
    });
});
</script>
@endsection
