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
     * Will return the users name, based on how they configured it to display
     *
     * @param Array $user
     * @return String
     */
    function getUserDisplayName(array $user)
    {
        $fname = isset($user['fname']) ? $user['fname'] : '';
        $lname = isset($user['lname']) ? $user['lname'] : '';

        $display = "$fname $lname";

        if (!isset($user['displayname']))
        {
            return trim($display);
        }

        switch ($user['displayname'])
        {
            // First
            case 1:
                $display = $fname;
                break;

            // First Last
            case 2:
                $display = "$fname $lname";
                break;

            // Last
            case 3:
                $display = "$lname";
                break;

            // Last, First
            case 4:
                $display = "$lname, $fname";
                break;
        }

        return trim($display);
    }
}
