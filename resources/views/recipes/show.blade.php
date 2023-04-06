@extends('layouts.main')
@section('body-id', 'recipes')

@section('content')
<div class="p-5">
    <div class="row">
        <div class="col-8 mx-auto">

            <div class="header row mb-5">
                <div class="col-6">
                    <h2>{{ $recipe->name }}</h2>
                </div>
                <div class="col-6">
                    <img class="recipe-thumbnail" src="{{ asset('img/'.$recipe->thumbnail) }}" alt="{{ $recipe->name }}"/>
                </div>
            </div><!-- .header -->

            <div class="d-flex justify-content-between">
                <div class="directions pe-5">
                    <h5>{{ gettext('Directions') }}</h5>
                    {!! $recipe->directions !!}
                </div>
                <div class="ingredients bg-info p-4 rounded">
                    <h5>{{ gettext('Ingredients') }}</h5>
                    <ul>
                    @foreach (explode("\r\n", $recipe->ingredients) as $key => $i)
                        @if (!empty($i))
                        <li>{{ $i }}</li>
                        @endif
                    @endforeach
                    </ul>
                </div>
            </div>

        </div><!-- /.col-8.mx-auto -->
    </div>
</div>

<style>
.recipe-thumbnail
{
    width: 250px;
}
.ingredients
{
    min-width: 350px;
}
.directions
</style>
@endsection
