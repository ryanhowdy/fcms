<?php
/**
 * address - functions for formatting addresses
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
 * formatAddress 
 * 
 * Will attempt to format an address based on the given country.
 * If not enough address info is provided will return empty string.
 *
 * Address param can contain any of the following:
 * 
 *     country, address, city, state, zip
 * 
 * @param array $address 
 * 
 * @return string
 */
function formatAddress ($address)
{
    $str = '';

    // Must have a country
    $country = isset($address['country']) ? $address['country'] : getDefaultCountry();

    switch ($country)
    {
        case 'US':
            $str = formatAddressUs($address);
            break;
    }

    return $str;
}

/**
 * formatAddressUrl 
 * 
 * Turns a formatted address into a url for google maps.
 * 
 * @param string $address 
 * 
 * @return string
 */
function formatAddressUrl ($address)
{
    $url = $address;

    // Space
    $url = preg_replace("/\s/", "%20", $url);

    // <br/>
    $url = preg_replace("/<br\/>/", ",%20", $url);

    return $url;
}

/**
 * formatAddressUs 
 * 
 * Valid Address
 *   1. Country
 *   2. State
 *   3. City
 *   4. City, State
 *   5. Address, City
 *   6. Address, City, State
 *   7. Address, City, State, Zip
 *   8. Address, City, State, Zip Country
 *
 * @param string  $address 
 *
 * @return void
 */
function formatAddressUs ($address)
{
    $str = '';

    // 5 - 8
    if (!empty($address['address']))
    {
        $str .= cleanOutput($address['address']).'<br/>';

        if (!empty($address['city']))
        {
            $str .= cleanOutput($address['city']);

            if (!empty($address['state']))
            {
                $str .= ', '.cleanOutput($address['state']);

                if (!empty($address['zip']))
                {
                    $str .= ' '.cleanOutput($address['zip']);

                    if (!empty($address['country']))
                    {
                        // Convert country code to name
                        $countries = buildCountryList();
                        $country   = cleanOutput($address['country']);
                        $country   = $countries[$country];
                        $country   = ucwords(strtolower($country));

                        $str .= '<br/>'.$country;
                    }
                }
            }
        }
    }
    // 3 or 4
    elseif (!empty($address['city']))
    {
        $str .= cleanOutput($address['city']);

        if (!empty($address['state']))
        {
            $str .= ', '.cleanOutput($address['state']);
        }
    }
    // 2
    elseif (!empty($address['state']))
    {
        $str .= cleanOutput($address['state']);
    }
    // 1
    elseif (!empty($address['country']))
    {
        $str .= cleanOutput($address['state']);
    }

    return $str;
}
