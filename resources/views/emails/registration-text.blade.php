{{ sprintf(gettext('Dear %s,'), $user->displayname) }}

{{ sprintf(gettext('Thank you for registering at %s.'), env('APP_NAME')) }}

{{ gettext('In order to login and begin using the site, your administrator must activate your account.  You will get an email when this has been done.') }}

{{ gettext('Thanks,') }}
{{ sprintf(gettext('The %s Webmaster'), env('APP_NAME')) }}

{{ gettext('This is an automated response, please do not reply.') }}
