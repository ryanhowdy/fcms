<p>{{ __('Dear :name,', ['name' => $user->displayname]) }}</p>
<p>{{ __('Thank you for registering at :sitename.', ['sitename' => env('APP_NAME')]) }}</p>
<p>{{ __('In order to login and begin using the site, your administrator must activate your account.  You will get an email when this has been done.') }}</p>
<p>
    {{ __('Thanks,') }}<br>
    {{ __('The :sitename Webmaster', ['sitename' => env('APP_NAME')]) }}
</p>
<hr>
<p>{{ __('This is an automated response, please do not reply.') }}</p>
<hr>
<img src="{{ $message->embed('img/logo.gif') }}">
