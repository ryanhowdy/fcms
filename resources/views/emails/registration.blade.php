<p>{{ sprintf(gettext('Dear %s,'), $user->displayname) }}</p>
<p>{{ sprintf(gettext('Thank you for registering at %s.'), env('APP_NAME')) }}</p>
<p>{{ gettext('In order to login and begin using the site, your administrator must activate your account.  You will get an email when this has been done.') }}</p>
<p>
    {{ gettext('Thanks,') }}<br>
    {{ sprintf(gettext('The %s Webmaster'), env('APP_NAME')) }}
</p>
<hr>
<p>{{ gettext('This is an automated response, please do not reply.') }}</p>
<hr>
<img src="{{ $message->embed('img/logo.gif') }}">
