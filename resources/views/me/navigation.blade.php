<h5 class="text-end">{{ __('Profile') }}</h5>
<ul class="list-unstyled text-end">
    <li class="@yield('profile.general')">
        <a class="text-decoration-none" href="{{ route('my.profile') }}">{{ __('General') }}</a>
    </li>
    <li class="@yield('profile.picture')">
        <a class="text-decoration-none" href="{{ route('my.avatar') }}">{{ __('Picture') }}</a>
    </li>
    <li class="@yield('profile.address')">
        <a class="text-decoration-none" href="{{ route('my.address') }}">{{ __('Address') }}</a>
    </li>
</ul>
<h5 class="text-end mt-5">{{ __('Settings') }}</h5>
<ul class="list-unstyled text-end">
    <li class="@yield('settings.settings')">
        <a class="text-decoration-none" href="{{ route('my.settings') }}">{{ __('Settings') }}</a>
    </li>
    <li class="@yield('settings.account')">
        <a class="text-decoration-none" href="{{ route('my.account') }}">{{ __('Account') }}</a>
    </li>
</ul>
