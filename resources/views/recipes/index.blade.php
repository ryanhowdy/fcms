@extends('layouts.main')
@section('body-id', 'recipes')

@section('content')
<div class="p-5">
    <div class="d-flex justify-content-between">
        <h2>{{ gettext('Recipes') }}</h2>
        <div>
            <a href="{{ route('recipes.create') }}" class="btn btn-success text-white">{{ gettext('Add Recipe') }}</a>
        </div>
    </div>

@if ($recipes->isEmpty())
    <x-empty-state/>
@else
    <div class="d-flex flex-wrap">
@foreach ($recipes as $r)
        <div class="card me-4 mb-4">
            <img src="{{ asset('img/'.$r->thumbnail) }}" alt="{{ $r->name }}"/>
            <div class="card-body">
                <h5 class="card-title">
                    <a href="{{ route('recipes.show', $r->id) }}" class="stretched-link">{{ $r->name }}</a>
                </h5>
                <p class="card-text">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                </p>
            </div><!-- /.card-body -->
        </div><!-- /.card -->
@endforeach
    </div><!-- .flex-wrap -->
@endif

</div>

<style>
#recipes .card
{
    width: 250px;
}
</style>
@endsection
