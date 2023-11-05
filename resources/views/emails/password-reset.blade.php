<img src="{{ $message->embed('img/logo.gif') }}">
<p>{{ sprintf(_gettext('Dear %s,'), $user->displayname) }}</p>
<p>{{ sprintf(_gettext('A request has been made to change your password at %s.'), env('APP_NAME')) }}</p>
<p><a href="{{ route('password.reset', $token) }}">{{ _gettext('Reset Password') }}</a></p>
<p>
    {{ _gettext('Thanks,') }}<br>
    {{ sprintf(_gettext('The %s Webmaster'), env('APP_NAME')) }}
</p>
<hr>
<p>{{ _gettext('This is an automated response, please do not reply.') }}</p>
<hr>
