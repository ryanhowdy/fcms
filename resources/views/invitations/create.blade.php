@extends('layouts.main')
@section('body-id', 'calendar')
@section('main-bg', 'bg-light')

@section('content')
<div class="p-5">
    
    <form class="p-5 border rounded bg-white" action="{{ route('invitations.store', $event['id']) }}" method="post">
        @csrf
        <h2 class="mb-3">{{ _gettext('Invite Guests') }}</h2>
    @if ($errors->any())
        <div class="alert alert-danger">
            <h4 class="alert-heading">{{ _gettext('An error has occurred') }}</h4>
            <p>{{ _gettext('Please fill out the required fields below.') }}</p>
        </div>
    @endif
        <h5>{{ _gettext('Invite Members') }}</h5>
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" name="invite-all" id="invite-all">
                <label class="form-check-label" for="invite-all">{{ _gettext('Select All') }}</label>
            </div>
            <div class="border p-3">
            @foreach($users as $id => $name)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="{{ $id }}" name="members[]" id="member_{{ $id }}">
                    <label class="form-check-label" for="member_{{ $id }}">{{ $name }}</label>
                </div>
            @endforeach
            </div>
        </div>
        <h5>{{ _gettext('Invite Non-Members') }}</h5>
        <div class="mb-3">
            <div class="form-text">
                {{ _gettext('Enter list of emails to invite. One email per line.') }}
            </div>
            <textarea class="form-control" rows="3" id="non-members" name="non-members"></textarea>
        </div>
        <div class="pt-5">
            <button class="btn btn-primary px-5 me-3 text-white" type="submit" id="submit" name="submit">
                {{ _gettext('Save') }}
            </button>
            <button type="button" onclick="history.back()" class="btn btn-link">{{ _gettext('Cancel') }}</button>
        </div>
    </form>

</div>
<script>
$(function() {
    $('#invite-all').click(function() {
        let $checks = $(this).parent('.form-check').next('.border');

        if ($('#invite-all').is(':checked'))
        {
            $checks.hide();
        }
        else
        {
            $checks.show();
        }
    });
});
</script>
@endsection
