@extends('layouts.main')
@section('body-id', 'recipes')

@section('content')
<div class="p-5">

    <form class="p-5 border rounded bg-white" action="{{ route('recipes.categories.create') }}" method="post">
        @csrf
        <h2 class="mb-5">{{ gettext('Add Category') }}</h2>
    @if ($errors->any())
        <div class="alert alert-danger">
            <h4 class="alert-heading">{{ gettext('An error has occurred') }}</h4>
            <p>{{ gettext('Please fill out the required fields below.') }}</p>
        </div>
    @endif
        <div class="mb-3 required">
            <label for="name" class="form-label">{{ gettext('Name') }}</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}">
        </div>
        <div class="mb-5 required">
            <label for="description" class="form-label">{{ gettext('Description') }}</label>
            <textarea class="form-control" id="description" name="description" rows="5"></textarea>
        </div>
        <div class="">
            <button class="btn btn-success px-5" type="submit" id="submit" name="submit">
                <i class="bi-check-square me-1"></i>
                {{ gettext('Add') }}
            </button>
        </div>
    </form>

</div>
<style>
.form-check-label > img
{
    width: 75px;
}
.add-ingredient:hover
{
    cursor: pointer;
    text-decoration: underline;
}
</style>
<script>
$(function() {
    $('.add-ingredient').click(function() {
        let $add        = $(this);
        let $ingredient = $add.prev('input').clone();

        $add.before($ingredient);
    });
});
</script>
@endsection
