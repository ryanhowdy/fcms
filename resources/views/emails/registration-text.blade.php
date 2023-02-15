{{ __('Dear :name,', ['name' => $user->fname]) }}

{{ __('Thank you for registering at :sitename.', ['sitename' => env('APP_NAME')]) }}

{{ __('In order to login and begin using the site, your administrator must activate your account.  You will get an email when this has been done.') }}

{{ __('Thanks,') }}
{{ __('The :sitename Webmaster', ['sitename' => env('APP_NAME')]) }}

{{ __('This is an automated response, please do not reply.') }}
