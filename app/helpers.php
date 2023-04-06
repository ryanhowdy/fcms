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
