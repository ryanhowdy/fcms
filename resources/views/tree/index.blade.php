@extends('layouts.main')
@section('body-id', 'familytree')

@section('content')
<div class="p-5 tree">

@if ($errors->any())
    <div class="alert alert-danger">
        <h4 class="alert-heading">{{ __('An error has occurred') }}</h4>
    @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
@php dump($errors) @endphp
    @endforeach
    </div>
@endif

    <ul class="list-unstyled">
    @foreach ($tree as $t)
        @include('tree.tree', ['person' => $t])
    @endforeach
    </ul>

</div>

<div class="modal" tabindex="-1">
    <form id="modal-form" action="{{ route('familytree.store') }}" method="post">
        @csrf
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Relationship') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col required">
                            <label for="fname" class="form-label">{{ __('First') }}</label>
                            <input type="text" class="form-control" id="fname" name="fname" value="{{ old('fname') }}">
                        </div>
                        <div class="col">
                            <label for="mname" class="form-label">{{ __('Middle') }}</label>
                            <input type="text" class="form-control" id="mname" name="mname" value="{{ old('mname') }}">
                        </div>
                        <div class="col required">
                            <label for="lname" class="form-label">{{ __('Last') }}</label>
                            <input type="text" class="form-control" id="lname" name="lname" value="{{ old('lname') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="maiden" class="form-label">{{ __('Maiden') }}</label>
                        <input type="text" class="form-control" id="maiden" name="maiden" value="{{ old('maiden') }}">
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="dob" class="form-label">{{ __('Date of Birth') }}</label>
                            <input type="date" class="form-control" id="dob" name="dob" value="{{ old('dob') }}">
                        </div>
                        <div class="col">
                            <label for="dod" class="form-label">{{ __('Date of Death') }}</label>
                            <input type="date" class="form-control" id="dod" name="dod" value="{{ old('dod') }}">
                        </div>
                    </div>
                </div><!-- /.modal-body -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Save') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </form>
</div><!-- /.modal -->

<script>
$(function() {
    $('ul.dropdown-menu li a').click(function(e) {
        e.preventDefault();

        let $link  = $(this);
        let userId = $link.data('user');
        let type   = $link.data('type');

        $('.modal').modal('show');

        let userInput = document.createElement('input');

        userInput.setAttribute('type', 'hidden');
        userInput.setAttribute('name', 'user_id');
        userInput.value = userId;

        $('#modal-form').append(userInput);

        let relInput = document.createElement('input');

        relInput.setAttribute('type', 'hidden');
        relInput.setAttribute('name', 'relationship');
        relInput.value = type;

        $('#modal-form').append(relInput);
    });
});
</script>

@endsection
