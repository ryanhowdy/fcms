{{ sprintf(_gettext('Dear %s,'), $user->displayname) }}

{{ sprintf(_gettext('Thank you for registering at %s.'), env('APP_NAME')) }}

{{ _gettext('In order to login and begin using the site, your administrator must activate your account.  You will get an email when this has been done.') }}

{{ _gettext('Thanks,') }}
{{ sprintf(_gettext('The %s Webmaster'), env('APP_NAME')) }}

{{ _gettext('This is an automated response, please do not reply.') }}
