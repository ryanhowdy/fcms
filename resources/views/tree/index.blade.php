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
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-new-tab" data-bs-toggle="tab" data-bs-target="#nav-new" type="button" role="tab" aria-controls="nav-new" aria-selected="true">
                                <i class="bi-person-plus pe-1"></i>
                                {{ __('Create New Person') }}
                            </button>
                            <button class="nav-link" id="nav-existing-tab" data-bs-toggle="tab" data-bs-target="#nav-existing" type="button" role="tab" aria-controls="nav-existing" aria-selected="false">
                                <i class="bi-search pe-1"></i>
                                {{ __('Choose Existing Person') }}
                            </button>
                        </div>
                    </nav>
                    <div class="tab-content pt-4" id="nav-tabContent">
                        <div class="tab-pane show active" id="nav-new" role="tabpanel" aria-labelledby="nav-new" tabindex="0">
                            <div class="row mb-3">
                                <div class="col-5">
                                    <label for="given_name" class="form-label fw-bold">{{ __('First and optional Middle Name') }}</label>
                                    <input type="text" class="form-control" id="given_name" name="given_name" value="{{ old('given_name') }}">
                                </div>
                                <div id="rel_surname" class="col-5">
                                    <label for="surname" class="form-label fw-bold">{{ __('Surname') }}</label>
                                    <input type="text" class="form-control" id="surname" name="surname" value="{{ old('surname') }}">
                                </div>
                                <div id="rel_maiden" class="col-5 d-none">
                                    <label for="maiden" class="form-label fw-bold">{{ __('Maiden Name') }}</label>
                                    <input type="text" class="form-control" id="maiden" name="maiden" value="{{ old('maiden') }}">
                                </div>
                                <div class="col-2">
                                    <label for="name_suffix" class="form-label fw-bold">{{ __('Suffix') }}</label>
                                    <input type="text" class="form-control" id="name_suffix" name="name_suffix" value="{{ old('name_suffix') }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('Sex') }}</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="sex" id="sex_unknown" value="U" checked>
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
                                <label class="form-label fw-bold">{{ __('Status') }}</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status" id="living" value="living" checked>
                                        <label class="form-check-label" for="living">{{ __('Living') }}</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status" id="deceased" value="deceased">
                                        <label class="form-check-label" for="deceased">{{ __('Deceased') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="dob" class="form-label fw-bold">{{ __('Date of Birth') }}</label>
                                    <input type="date" class="form-control" id="dob" name="dob" value="{{ old('dob') }}">
                                </div>
                                <div id="deceased_date" class="col d-none">
                                    <label for="dod" class="form-label fw-bold">{{ __('Date of Death') }}</label>
                                    <input type="date" class="form-control" id="dod" name="dod" value="{{ old('dod') }}">
                                </div>
                            </div>
                            <div id="child-extra" class="mb-3 d-none">
                                <label class="form-label">{{ __('Parents') }}</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="" id="" value="">
                                    <label class="form-check-label" for=""></label>
                                </div>
                            </div>
                        </div><!-- /#nav-new -->
                        <div class="tab-pane" id="nav-existing" role="tabpanel" aria-labelledby="nav-existing" tabindex="0">
                        @if (count($users))
                            <div class="mb-3">
                                <label for="existing_user" class="form-label fw-bold">{{ __('Existing Member?') }}</label>
                                <select class="form-select" id="existing_user" name="existing_user">
                                    <option></option>
                                    <option value="CREATE_NEW">{{ __('Create New') }}</option>
                                    <optgroup label="{{ __('Existing Members') }}">
                                        @foreach($users as $id => $name)
                                        <option value="{{ $id }}" {{ old('existing_user') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                            </div>
                        @endif
                        </div><!-- /#nav-existing -->
                    </div><!-- .tab-content -->
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

        let $link        = $(this);
        let familyId     = $link.data('family');
        let individualId = $link.data('individual');
        let type         = $link.data('type');
        let header       = $link.data('header');

        // cleanup previous modal data
        $('.modal input[name="family_id"]').remove();
        $('.modal input[name="individual_id"]').remove();
        $('.modal input[name="relationship"]').remove();

        // show the modal
        $('.modal').modal('show');

        // create a hidden input for the family id
        let familyInput = document.createElement('input');

        familyInput.setAttribute('type', 'hidden');
        familyInput.setAttribute('name', 'family_id');
        familyInput.value = familyId;

        $('#modal-form').append(familyInput);

        // create a hidden input for the individual id
        let individualInput = document.createElement('input');

        individualInput.setAttribute('type', 'hidden');
        individualInput.setAttribute('name', 'individual_id');
        individualInput.value = individualId;

        $('#modal-form').append(individualInput);

        // create a hidden input for the relationship type
        let relInput = document.createElement('input');

        relInput.setAttribute('type', 'hidden');
        relInput.setAttribute('name', 'relationship');
        relInput.value = type;

        $('#modal-form').append(relInput);

        // update the header
        $('.modal-header > h5').text(header);

        // Show/hide parts of the form based on relationship type
        if (type == 'spouse')
        {
            $('#rel_surname').addClass('d-none');
            $('#surname').val($link.data('surname'));
            $('#rel_maiden').removeClass('d-none');
        }
        else if (type == 'child')
        {
            $('#child-extra').removeClass('d-none');
        }
    });

    // Toggle date deceased 
    $('input[name="status"]').change(function() {
        console.log('changed the status');
        if ($('#deceased').is(':checked'))
        {
            $('#deceased_date').removeClass('d-none');
        }
        else
        {
            $('#deceased_date').addClass('d-none');
        }
    });
});
</script>

@endsection
