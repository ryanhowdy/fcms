<?php

/*
 * Helpers
 *
 * Some usefule global helper/utility functions
 */

if (!function_exists('getUserDisplayName'))
{
    /*
     * getUserDisplayName
     *
     * Will return the users full name or display name if given
     *
     * @param Array $user
     * @return String
     */
    function getUserDisplayName(array $user)
    {
        $name = $user['name'];

        if (isset($user['displayname']))
        {
            if (!is_null($user['displayname']))
            {
                $name = $user['displayname'];
            }
        }

        return trim($name);
    }
}

if (!function_exists('getUserAvatar'))
{
    /*
     * getUserAvatar
     *
     * Will return the route/url to the users avatar
     *
     * @param Array $user
     * @return String
     */
    function getUserAvatar(array $user)
    {
        if (!isset($user['avatar']))
        {
            return route('avatar', 'no_avatar.jpg');
        }

        if ($user['avatar'] == 'gravatar')
        {
            if (isset($user['email']))
            {
                return 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($user['email'])));
            }
            
            return route('avatar', 'no_avatar.jpg');
        }

        return route('avatar', $user['avatar']);
    }
}

if (!function_exists('getIndividualPicture'))
{
    /*
     * getindividualPicture
     *
     * Will return the asset for the family tree individual's picture.
     *
     * If the individual is also a user, it will use their configured avatar, else
     * it will use the standard male/female.
     *
     * @param array $individual 
     * @return String
     */
    function getIndividualPicture(array $individual)
    {
        if (isset($individual['user_id']) && !empty($individual['user_id']))
        {
            return getUserAvatar($individual);
        }

        if (isset($individual['sex']) && $individual['sex'] == 'F')
        {
            return asset('img/tree-female.png');
        }

        return asset('img/tree-male.png');
    }
}

if (!function_exists('getListOfAvailableLanguages'))
{
    /**
     * getListOfAvailableLanguages 
     * 
     * @return array
     */
    function getListOfAvailableLanguages($skipMissingMo = true)
    {
        $languages = ['en_US' => getLanguageName('en')];

        $dir = base_path().'/lang/';

        if (is_dir($dir))
        {
            if ($dh = opendir($dir))
            {
                while (($file = readdir($dh)) !== false)
                {
                    // Skip directories that start with a period
                    if ($file[0] === '.')
                    {
                        continue;
                    }

                    // Skip files (messages.pot)
                    if (!is_dir("$dir$file"))
                    {
                        continue;
                    }

                    // Skip directories that don't include a messages.mo file
                    if ($skipMissingMo && !file_exists($dir.$file.'/LC_MESSAGES/messages.mo'))
                    {
                        continue;
                    }

                    $languages[$file] = getLanguageName($file);
                }

                closedir($dh);
            }
        }

        return $languages;
    }
}

if (!function_exists('getLanguageName'))
{
    /**
     * getLanguageName 
     * 
     * Given a locale usually in the ll_CC format where 
     * ll is an ISO 639 2-letter language code and
     * CC is an ISO 3166 2-letter country code
     * Will return a string of the language name and country name if applicable
     * 
     * @param string $locale 
     * @return string
     */
    function getLanguageName(string $locale)
    {
        switch($locale)
        {
            case 'ar':
                return _gettext('Arabic');
                break;
            case 'da_DK':
                return _gettext('Danish (Denmark)');
                break;
            case 'de_DE':
                return _gettext('German (Germany)');
                break;
            case 'es_ES':
                return _gettext('Spanish (Spain)');
                break;
            case 'en':
                return _gettext('English');
                break;
            case 'fr_FR':
                return _gettext('French (France)');
                break;
            case 'it_IT':
                return _gettext('Italian (Italy)');
                break;
            case 'nl_NL':
                return _gettext('Dutch (Netherlands)');
                break;
            case 'pt_BR':
                return _gettext('Portuguese (Brazil)');
                break;
            case 'ru_RU':
                return _gettext('Russian (Russia)');
                break;
            case 'sk_SK':
                return _gettext('Slovak (Slovakia)');
                break;
            case 'tr_TR':
                return _gettext('Turkish (Turkey)');
                break;
            case 'xx_XX':
                return _gettext('XX (Test)');
                break;
            default:
                return $locale;
                break;
        }
    }
}

if (!function_exists('displayUserComments'))
{
    /**
     * displayUserComments
     * 
     * Will run all user comments through htmlspecialchars to prevent xss attacks.
     *
     * Also handles legacy data if needed.
     * 
     * @param string $locale 
     * @return string
     */
    function displayUserComments(string $dirty)
    {
        $clean = htmlspecialchars($dirty, ENT_QUOTES, 'UTF-8');

        // legacy code used bbcode, emojis, etc, lets convert that to html 
        if (config('fcms.legacy'))
        {
            // Convert smileys
            $clean = parseLegacySmilies($clean);

            // Ammar BBcode size is different than what fcms 3.8.0 used
            // so convert it to the correct style
            $clean = str_replace('[size=small]', '[size=80]', $clean);

            // Convert bbcode
            $clean = \Ammar\BBCode\Facades\BBCode::parseCaseInsensitive($clean);
        }

        return $clean;
    }
}

if (!function_exists('parseLegacySmilies'))
{
    function parseLegacySmilies(string $string)
    {
        $smileys = [
            ':smile:', ':none:', ':)', '=)', 
            ':wink:', ';)', 
            ':tongue:', 
            ':biggrin:', 
            ':sad:', ':(', 
            ':sick:', 
            ':cry:', 
            ':shocked:', 
            ':cool:', 
            ':sleep:', 'zzz', 
            ':angry:', ':mad:', 
            ':embarrassed:', ':shy:', 
            ':rolleyes:', 
            ':nervous:', 
            ':doh:', 
            ':love:', 
            ':please:', ':1please:', 
            ':hrmm:', 
            ':quiet:', 
            ':clap:', 
            ':twitch:', 
            ':blah:', 
            ':bored:', 
            ':crazy:', 
            ':excited:', 
            ':noidea:', 
            ':disappointed:', 
            ':banghead:', 
            ':dance:', 
            ':laughat:', 
            ':ninja:', 
            ':pirate:', 
            ':thumbup:', 
            ':thumbdown:', 
            ':twocents:',
        ];
        $images = [
            'smile.gif', 'smile.gif', 'smile.gif', 'smile.gif', 
            'wink.gif', 'wink.gif', 
            'tongue.gif', 
            'biggrin.gif', 
            'sad.gif', 'sad.gif', 
            'sick.gif', 
            'cry.gif', 
            'shocked.gif', 
            'cool.gif', 
            'sleep.gif', 'sleep.gif', 
            'angry.gif', 'angry.gif', 
            'embarrassed.gif', 'embarrassed.gif', 
            'rolleyes.gif', 
            'nervous.gif', 
            'doh.gif', 
            'love.gif', 
            'please.gif', 'please.gif', 
            'hrmm.gif', 
            'quiet.gif', 
            'clap.gif', 
            'twitch.gif', 
            'blah.gif', 
            'bored.gif', 
            'crazy.gif', 
            'excited.gif', 
            'noidea.gif', 
            'disappointed.gif', 
            'banghead.gif', 
            'dance.gif', 
            'laughat.gif', 
            'ninja.gif', 
            'pirate.gif', 
            'thumbup.gif', 
            'thumbdown.gif', 
            'twocents.gif'
        ];

        foreach ($smileys as $i => $smiley)
        {
            $img = '<img src="' . asset('img/smileys/' . $images[$i]) . '" alt="' . $smiley . '" class="smiley">';

            $string = str_replace($smiley, $img, $string);
        }

        return $string;
    }
}
