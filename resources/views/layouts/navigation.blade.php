<nav class="navbar navbar-expand-sm border">
    <div class="container-fluid">
        <a class="navbar-brand p-3" href="/">
            <img src="{{ asset('img/logo.gif') }}" alt="Family Connections" height="45">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav-content" aria-controls="#nav-content" aria-expanded="false" aria-label="Toggle Navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav-content">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item mx-2 d-none d-sm-inline">
                    <form class="" role="search">
                        <input class="form-control me-2 rounded-5" type="search" placeholder="Search" aria-label="Search">
                    </form>
                </li>
                <li class="nav-item dropdown no-caret mx-2">
                    <a class="nav-link dropdown-toggle fs-4" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img class="avatar rounded-5" src="{{ getUserAvatar(Auth()->user()->toArray()) }}" title="{{ gettext('avatar') }}">
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="d-flex">
                            <a class="px-3 py-1 avatar-wrapper position-relative" href="{{ route('my.avatar') }}">
                                <img class="avatar rounded-5" src="{{ getUserAvatar(Auth()->user()->toArray()) }}" title="{{ gettext('avatar') }}">
                                <i class="bi-pencil position-absolute"></i>
                            </a>
                            <div>
                                <p class="fw-bold pe-3 m-0">
                                    {{ Auth()->user()->displayname }}
                                </p>
                                <p class="pe-3">
                                    {{ Auth()->user()->email }}
                                </p>
                            </div>
                        </div>
                        <a class="dropdown-item" href="{{ route('my.profile') }}">{{ gettext('Profile') }}</a>
                        <a class="dropdown-item" href="{{ route('my.messages') }}">{{ gettext('Messages') }}</a>
                        <a class="dropdown-item" href="{{ route('my.notifications') }}">{{ gettext('Notifications') }}</a>
                        <a class="dropdown-item" href="{{ route('my.settings') }}">{{ gettext('Settings') }}</a>
                        <hr class="dropdown-divider">
                        <a class="dropdown-item" href="{{ route('auth.logout') }}">{{ gettext('Logout') }}</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
