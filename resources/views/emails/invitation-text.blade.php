{{ sprintf(_pgettext('%s is the name of a person, like Dear Bob,', 'Dear %s,'), $toName) }}

{{ sprintf(_pgettext('The first %s is the name of a person, the second is the title of an event', '%s has invited you to %s.'), $fromName, $eventTitle) }}


{{ _gettext('Please visit the link below to view the rest of this invitation.') }}

{{ $url }}

----
{{ _gettext('This is an automated response, please do not reply.') }}
