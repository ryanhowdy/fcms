
<div class="photo-nav d-flex justify-content-between pt-4 border-bottom">
    <ul class="nav">
        <li class="nav-item">
            <a @class(['nav-link', 'active' => $active == 'dashboard']) href="{{ route('photos') }}">{{ _gettext('Dashboard') }}</a>
        </li>
        <li class="nav-item">
            <a @class(['nav-link', 'active' => $active == 'albums']) href="{{ route('photos.albums') }}">{{ _gettext('Albums') }}</a>
        </li>
        <li class="nav-item">
            <a @class(['nav-link', 'active' => $active == 'people']) href="{{ route('photos.users') }}">{{ _gettext('People') }}</a>
        </li>
        <li class="nav-item">
            <a @class(['nav-link', 'active' => $active == 'places']) href="{{ route('photos.places') }}">{{ _gettext('Places') }}</a>
        </li>
    </ul>
    <div class="search-filter">
        <a class="me-3 text-muted" href="#"><i class="bi-search"></i></a>
        <div class="dropdown no-caret d-inline-block" data-bs-toggle="dropdown">
            <a class="dropdown-toggle text-muted" href="#"><i class="bi-sliders"></i></a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">{{ _gettext('Top Rated') }}</a></li>
                <li><a class="dropdown-item" href="#">{{ _gettext('Most Viewed') }}</a></li>
            </ul>
        </div>
    </div>
</div>
