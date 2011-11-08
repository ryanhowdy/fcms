<?php
/**
 * phone - functions for formatting phone numbers
 * 
 * Formatting is done based on the ISO 3166-1 standard.
 * http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
 * 
 * PHP versions 4 and 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.7
 */

/**
 * formatPhone 
 * 
 * Will attempt to format a phone number correctly for the given country.
 * 
 * @param string $number
 * @param string $country
 * 
 * @return string
 */
function formatPhone ($number, $country = '')
{
    // Must have a country
    $country = !empty($country) ? strtoupper($country) : getDefaultCountry();

    switch ($country)
    {
        case 'US':

            $number = formatPhoneUs($number);
    }

    return $number;
}

/**
 * formatPhoneUs 
 * 
 * Format a US phone number.
 * 
 * @param string $number 
 * 
 * @return void
 */
function formatPhoneUs ($number)
{
    // Normalize - strip everything but numbers and letters
    $number = preg_replace("/[^0-9A-Za-z]/", "", $number);

    if (strlen($number) == 7)
    {
        $number = preg_replace("/([0-9a-zA-Z]{3})([0-9a-zA-Z]{4})/", "$1-$2", $number);
    }
    elseif (strlen($number) == 10)
    {
        $number = preg_replace("/([0-9a-zA-Z]{3})([0-9a-zA-Z]{3})([0-9a-zA-Z]{4})/", "($1) $2-$3", $number);
    }
    elseif (strlen($number) == 11)
    {
        $number = preg_replace("/([0-9a-zA-Z]{1})([0-9a-zA-Z]{3})([0-9a-zA-Z]{3})([0-9a-zA-Z]{4})/", "$1($2) $3-$4", $number);
    }

    return $number;
}
