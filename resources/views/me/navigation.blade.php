<h5 class="text-end">{{ _gettext('Profile') }}</h5>
<ul class="list-unstyled text-end">
    <li class="@yield('profile.general')">
        <a class="text-decoration-none" href="{{ route('my.profile') }}">{{ _gettext('General') }}</a>
    </li>
    <li class="@yield('profile.picture')">
        <a class="text-decoration-none" href="{{ route('my.avatar') }}">{{ _gettext('Picture') }}</a>
    </li>
    <li class="@yield('profile.address')">
        <a class="text-decoration-none" href="{{ route('my.address') }}">{{ _gettext('Address') }}</a>
    </li>
</ul>
<h5 class="text-end mt-5">{{ _gettext('Settings') }}</h5>
<ul class="list-unstyled text-end">
    <li class="@yield('settings.settings')">
        <a class="text-decoration-none" href="{{ route('my.settings') }}">{{ _gettext('Settings') }}</a>
    </li>
    <li class="@yield('settings.account')">
        <a class="text-decoration-none" href="{{ route('my.account') }}">{{ _gettext('Account') }}</a>
    </li>
</ul>
