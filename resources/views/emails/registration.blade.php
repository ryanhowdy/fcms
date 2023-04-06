<p>{{ sprintf(_gettext('Dear %s,'), $user->displayname) }}</p>
<p>{{ sprintf(_gettext('Thank you for registering at %s.'), env('APP_NAME')) }}</p>
<p>{{ _gettext('In order to login and begin using the site, your administrator must activate your account.  You will get an email when this has been done.') }}</p>
<p>
    {{ _gettext('Thanks,') }}<br>
    {{ sprintf(_gettext('The %s Webmaster'), env('APP_NAME')) }}
</p>
<hr>
<p>{{ _gettext('This is an automated response, please do not reply.') }}</p>
<hr>
<img src="{{ $message->embed('img/logo.gif') }}">
