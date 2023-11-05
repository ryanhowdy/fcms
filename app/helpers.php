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

if (!function_exists('cleanUserComments'))
{
    /**
     * cleanUserComments
     * 
     * Will run all user comments through htmlspecialchars to prevent xss attacks.
     *
     * Also handles legacy data if needed.
     * 
     * @param string $locale 
     * @return string
     */
    function cleanUserComments(string $dirty, $remove = false)
    {
        $clean = htmlspecialchars($dirty, ENT_QUOTES, 'UTF-8');

        // handle legacy data
        if (config('fcms.legacy'))
        {
            // Are we parsing bbcode/smileys or removing them
            if ($remove)
            {
                // Remove bbcode
                $clean = stripBBCode($clean);
            }
            else
            {
                // Convert smileys
                $clean = parseLegacySmilies($clean);

                // Ammar BBcode size is different than what fcms 3.8.0 used
                // so convert it to the correct style
                $clean = str_replace('[size=small]', '[size=80]', $clean);

                // Convert bbcode
                $clean = parseBBCode($clean);
            }
        }

        $clean = \Illuminate\Mail\Markdown::parse($clean);

        return $clean;
    }
}

if (!function_exists('parseBBCode'))
{
    /**
     * parseBBCode 
     * 
     * Converts bbcode to html in a given string.
     *
     * @param string $source 
     * @return string
     */
    function parseBBCode(string $source)
    {
        $bbcodes = config('bbcodes');

        foreach ($bbcodes as $name => $config)
        {
            $source = preg_replace($config['pattern'], $config['replace'], $source);
        }

        return $source;
    }
}

if (!function_exists('stripBBCode'))
{
    /**
     * parseBBCode 
     * 
     * Removes bbcode from a given string.
     *
     * @param string $source 
     * @return string
     */
    function stripBBCode(string $source)
    {
        $bbcodes = config('bbcodes');

        foreach ($bbcodes as $name => $config)
        {
            $source = preg_replace($config['pattern'], $config['content'], $source);
        }

        return $source;
    }
}

if (!function_exists('parseLegacySmilies'))
{
    /**
     * parseLegacySmilies 
     * 
     * @param string $source 
     * @return string
     */
    function parseLegacySmilies(string $source)
    {
        $smileys = config('smileys');

        foreach ($smileys as $name => $config)
        {
            $search  = [ $config['search'] ];
            $replace = [ $config['replace'] ];

            foreach ($search as $i => $s)
            {
                $img = '<img src="' . asset('img/smileys/' . $replace[$i]) . '" alt="' . $search[$i] . '" class="smiley">';

                $source = str_replace($search[$i], $img, $source);
            }
        }

        return $source;
    }
}
