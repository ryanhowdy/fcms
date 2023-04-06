@extends('layouts.main')
@section('body-id', 'documents')
@section('main-bg', 'bg-light')

@section('content')
<div class="p-5">

    <form class="p-5 border rounded bg-white" action="{{ route('documents.create') }}" enctype="multipart/form-data" method="post">
        @csrf
        <h2 class="mb-5">{{ gettext('Upload Document') }}</h2>
    @if ($errors->any())
        <div class="alert alert-danger">
            <h4 class="alert-heading">{{ gettext('An error has occurred') }}</h4>
            <p>{{ gettext('Please fill out the required fields below.') }}</p>
<?php echo '<pre>'; print_r($errors); echo '</pre>'; ?>
        </div>
    @endif
        <div class="document-uploader alert alert-info text-center w-50">
            <p class="mb-1">
                <a class="fs-5 text-decoration-none d-block" href="#">
                    <i class="bi-cloud-arrow-up fs-1 d-block"></i>
                    {{ gettext('Select a document to upload') }}
                </a>
            </p>
            <p class="fs-6"><small>{{ gettext('or drag and drop it here') }}</small></a>
            <input type="file" class="d-none" id="document-picker" name="document">
        </div>
        <div class="mb-3 required">
            <label for="name" class="form-label">{{ gettext('Name') }}</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">{{ gettext('Description') }}</label>
            <textarea class="form-control" id="description" name="description" value="{{ old('description') }}"></textarea>
        </div>
        <div class="text-end">
            <button class="btn btn-secondary px-5" type="submit" id="submit" name="submit">
                {{ gettext('Upload') }}
            </button>
        </div>
    </form>

</div>
<script>
$(function() {
    // Open the file picker
    $('.document-uploader a').click(function(e) {
        e.preventDefault();
        $('#document-picker').trigger('click');
    });
});
</script>

@endsection
