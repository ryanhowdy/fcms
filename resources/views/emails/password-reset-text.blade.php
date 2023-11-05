{{ sprintf(_gettext('Dear %s,'), $user->displayname) }}</p>

{{ sprintf(_gettext('A request has been made to change your password at %s.'), env('APP_NAME')) }}</p>

{{ route('password.reset', $token) }}

{{ _gettext('Thanks,') }}<br>
{{ sprintf(_gettext('The %s Webmaster'), env('APP_NAME')) }}

{{ _gettext('This is an automated response, please do not reply.') }}</p>
