@extends('layouts.main')
@section('body-id', 'profile')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col-auto col-3 p-5">
        @section('profile.picture', 'active')
        @include('me.navigation')
    </div>
    <div class="col border-start min-vh-100 p-5">
        <form class="" action="{{ route('my.avatar') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="avatar-other" name="avatar-other">
            <h2>{{ _gettext('Current Picture') }}</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <h4 class="alert-heading">{{ _gettext('An error has occurred') }}</h4>
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
            </div>
        @endif
            <div class="avatar-preview border mb-5 d-flex justify-content-center align-items-center">
                <div class="p-2">
                    <img class="" src="{{ getUserAvatar(Auth()->user()->toArray()) }}" title="{{ _gettext('avatar') }}">
                </div>
            </div>
            <h2>{{ _gettext('Change') }}</h2>
            <div class="avatars d-flex">
                <div class="me-3 mb-3 border text-center">
                    <input type="file" class="d-none" id="photo-picker" name="avatar" accept="image/*">
                    <a href="#" class="uploader d-block pt-2"><i class="bi-upload"></i><br>{{ _gettext('Upload') }}</a>
                </div>
                <div class="me-3 mb-3 border text-center">
                    <a href="#" data-id="default" title="{{ _gettext('Use Default Picture') }}"><img src="{{ route('avatar', 'no_avatar.jpg') }}"></a>
                </div>
                <div class="me-3 mb-3 border text-center">
                    <a href="#" data-id="gravatar" title="{{ _gettext('Use Gravatar') }}"><img src="https://www.gravatar.com/avatar/{{ md5(strtolower(trim(Auth()->user()->email))) }}"></a>
                </div>
            </div>
            <div class="avatars d-flex mb-5">
                <div class="me-3 mb-3 border text-center">
                    <a href="#" data-id="1"><img class="pb-1" src="{{ route('avatar', 'avataaars1.png') }}"></a>
                </div>
                <div class="me-3 mb-3 border text-center">
                    <a href="#" data-id="2"><img class="pb-1" src="{{ route('avatar', 'avataaars2.png') }}"></a>
                </div>
                <div class="me-3 mb-3 border text-center">
                    <a href="#" data-id="3"><img class="pb-1" src="{{ route('avatar', 'avataaars3.png') }}"></a>
                </div>
                <div class="me-3 mb-3 border text-center">
                    <a href="#" data-id="4"><img class="pb-1" src="{{ route('avatar', 'avataaars4.png') }}"></a>
                </div>
                <div class="me-3 mb-3 border text-center">
                    <a href="#" data-id="5"><img class="pb-1" src="{{ route('avatar', 'avataaars5.png') }}"></a>
                </div>
            </div>
            <div class="">
                <button class="btn btn-success text-white px-5" type="submit" id="submit" name="submit">
                    <i class="bi-check-square me-1"></i>
                    {{ _gettext('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
<style>
.avatars div
{
    height: 82px;
    width: 82px;
}
.avatars a img
{
    max-height: 80px;
}
.avatars div.selected
{
    border-color: #28a745!important;
    box-shadow: 0 0 0 0.4rem rgba(40, 167, 69, 0.25);
}
.avatar-preview
{
    width: 250px;
    height: 250px;
}
.avatar-preview img
{
    max-width: 230px;
}
</style>
<script>
$(function() {
    // Open the file picker
    $('.uploader').click(function(e) {
        e.preventDefault();
        $('#photo-picker').trigger('click');
    });

    // select avatar
    $('.avatars a:not(.uploader)').click(function(e) {
        e.preventDefault();
        $('.avatars div.selected').removeClass('selected');
        $(this).parent('div').addClass('selected');

        $('#avatar-other').val($(this).data('id'));
    });
});
</script>
@endsection
