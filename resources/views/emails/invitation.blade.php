<p>{{ sprintf(_pgettext('%s is the name of a person, like Dear Bob,', 'Dear %s,'), $toName) }}</p>
<p>{{ sprintf(_pgettext('The first %s is the name of a person, the second is the title of an event', '%s has invited you to %s.'), $fromName, $eventTitle) }}</p>
<p>{{ _gettext('Please visit the link below to view the rest of this invitation.') }}</p>
<p><a href="{{ $url }}">{{ $url }}</a></p>
<hr>
<p>{{ _gettext('This is an automated response, please do not reply.') }}</p>
<hr>
<img src="{{ $message->embed('img/logo.gif') }}">
