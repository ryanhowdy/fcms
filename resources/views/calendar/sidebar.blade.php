<div class="mb-5">
    <a href="{{ route('calendar.create') }}" class="btn btn-success px-5 text-white">
        <i class="bi-calendar2-plus me-1"></i>
        {{ gettext('Create Event') }}
    </a>
</div>

<h4>{{ gettext('Categories') }}</h4>
<ul class="category-list list-unstyled">
@foreach($categories as $id => $category)
    <li class="lh-1 mb-3 d-flex">
        <div class="color me-2 rounded" style="background-color:{{ $category['color'] }}!important;"></div>
        <div>{{ $category['name'] }}</div>
    </li>
@endforeach
</ul>
<style>
ul.category-list li > .color
{
    width: 16px;
    height: 16px;
}
</style>
