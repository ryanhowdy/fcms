<?php
/**
 * AddressBook 
 * 
 * PHP versions 4 and 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */

/**
 * AddressBook 
 * 
 * @category  FCMS
 * @package   Family_Connections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
class AddressBook
{
    var $fcmsError;
    var $fcmsDatabase;
    var $fcmsUser;

    /**
     * AddressBook 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase
     * @param object $fcmsUser 
     * 
     * @return void
     */
    function AddressBook ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
    }

    /**
     *  displayAddress
     *
     *  Displays the address details.
     *
     *  @param int    $aid Id of the address
     *  @param string $cat Category name
     *
     *  @return void
     */
    function displayAddress ($aid, $cat)
    {
        $aid = (int)$aid;
        $cat = cleanOutput($cat);

        $sql = "SELECT a.`id`, a.`user`, `fname`, `lname`, `avatar`, `updated`, `country`, `address`, `city`, `state`, 
                    `zip`, `home`, `work`, `cell`, `email`, `password` 
                FROM `fcms_address` AS a, `fcms_users` AS u 
                WHERE a.`user` = u.`id` 
                AND a.`id` = ?";

        $r = $this->fcmsDatabase->getRow($sql, $aid);
        if ($r === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($r) <= 0)
        {
            echo '
            <p class="error-alert">'.sprintf(T_('Could not find address (%s)'), $aid).'</p>';

            return;
        }

        // Edit / Delete links
        $edit_del = '';

        if ($this->fcmsUser->id == $r['user'] || $this->fcmsUser->access < 2)
        {
            $edit_del = '<li id="edit"><a href="?cat='.$cat.'&amp;edit='.$r['id'].'">'.T_('Edit').'</a></li>';

            if ($r['password'] == 'NONMEMBER' || $r['password'] == 'PRIVATE')
            {
                $edit_del .='
                        <li id="delete"><a id="del_address" href="?cat='.$cat.'&amp;delete='.$r['id'].'">'.T_('Delete').'</a></li>';
            }
        }


        // Address
        $address    = formatAddress($r);
        $addressUrl = formatAddressUrl($address);

        if ($address == '')
        {
            $str = "<i>(".T_('none').")</i>";
        }

        $map_link = !empty($addressUrl) 
                  ? '<br/><a href="http://maps.google.com/maps?q='.$addressUrl.'"/>'.T_('Map').'</a>' 
                  : '';

        // Email
        if (empty($r['email']))
        {
            $email = "<i>(".T_('none').")</i>";
        }
        else
        {
            $email = cleanOutput($r['email']).' <a class="email" href="mailto:'.cleanOutput($r['email']).'" 
                title="'.T_('Email This Member').'">&nbsp;</a>';
        }

        // Phone Number
        $home = empty($r['home']) ? '<i>('.T_('none').')</i>' : formatPhone($r['home'], $r['country']);
        $work = empty($r['work']) ? '<i>('.T_('none').')</i>' : formatPhone($r['work'], $r['country']);
        $cell = empty($r['cell']) ? '<i>('.T_('none').')</i>' : formatPhone($r['cell'], $r['country']);

        // Display address
        echo '
            <div id="leftcolumn">';

        $this->displayCategories($cat);

        echo '
            </div>
            <div id="maincolumn">

                <div id="address-options">
                    <ul>
                        <li id="back"><a href="?cat='.$cat.'">'.T_('Back to Addresses').'</a></li>
                        <li id="email"><a href="mailto:'.cleanOutput($r['email']).'">'.T_('Email').'</a></li>
                        '.$edit_del.'
                    </ul>
                </div>
                <div id="address-details">
                    <p>
                        <img alt="avatar" src="'.getCurrentAvatar($r['user']).'"/>
                        <b class="name">'.cleanOutput($r['fname']).' '.cleanOutput($r['lname']).'</b>
                    </p>
                    <p>
                        <b class="label">'.T_('Address').':</b>
                        <span class="data">'.$address.' '.$map_link.'</span>
                    </p>
                    <p>
                        <b class="label">'.T_('Email').':</b>
                        <span class="data">'.$email.'</span>
                    </p>
                    <p>
                        <b class="label">'.T_pgettext('The dwelling where you live.', 'Home').':</b>
                        <span class="data">'.$home.'</span>
                    </p>
                    <p>
                        <b class="label">'.T_('Work').':</b>
                        <span class="data">'.$work.'</span>
                    </p>
                    <p>
                        <b class="label">'.T_('Mobile').':</b>
                        <span class="data">'.$cell.'</span>
                    </p>
                </div>

            </div>';
    }

    /**
     * displayAddressList 
     * 
     * Displays the categories and the user's in that category.
     *
     * @param string $cat Category name
     *
     * @return void
     */
    function displayAddressList ($cat = '')
    {
        echo '
            <div id="leftcolumn">';

        $this->displayCategories($cat);

        echo '
            </div>
            <div id="maincolumn">

                <form action="addressbook.php" id="check_all_form" name="check_all_form" method="post">
                <table id="address-table" cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th colspan="2">
                                <div id="check-all"></div>
                            </th>
                            <th style="text-align:right" colspan="2">
                                <a href="?add=yes">'.T_('Add New Address').'</a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="header">
                            <td class="chk"></td> 
                            <td>'.T_('Name').'</td> 
                            <td>'.T_('Address').'</td> 
                            <td>'.T_('Phone').'</td> 
                        </tr>';

        $this->displayAddressInCategory($cat);

        echo '
                    </tbody>
                </table>';

        if ($this->fcmsUser->access <= 3)
        {
            echo '
                <p class="alignright"><input class="sub1" type="submit" name="emailsubmit" value="'.T_('Email Selected').'"/></p>';
        }

        echo '
            </form>';
    }

    /**
     * displayCategories
     *
     * Displays the list of categories.
     *
     * @param string $selected The currently selected category
     *
     * @return void
     */
    function displayCategories ($selected = 'members')
    {
        $all = $my = $mem = $non = '';
        if ($selected == 'all')
        {
            $all = 'class="selected"';
        }
        if ($selected == 'my')
        {
            $my = 'class="selected"';
        }
        if ($selected == 'members')
        {
            $mem = 'class="selected"';
        }
        if ($selected == 'non')
        {
            $non = 'class="selected"';
        }
        echo '
                <b>'.T_('View').'</b>
                <ul class="address-categories">
                    <li '.$all.'><a href="?cat=all">'.T_('All').'</a></li>
                    <li '.$my.'><a href="?cat=my" title="'.T_('Only show My personal Addresses').'">'.T_('My Addresses').'</a></li>
                    <li '.$mem.'><a href="?cat=members" title="'.T_('Only show Addresses for members of the site').'">'.T_('Members').'</a></li>
                    <li '.$non.'><a href="?cat=non" title="'.T_('Only show Addresses for non-members').'">'.T_('Non-Members').'</a></li>
                </ul>
                <b>'.T_('Options').'</b>
                <ul class="address-options">
                    <li><a href="?csv=import">'.T_('Import').'</a></li>
                    <li><a href="?csv=export">'.T_('Export').'</a></li>
                </ul>';
    }

    /**
     * displayAddressInCategory
     *
     * Displays all the addresses in the given category.
     *
     * @param string $category Category name
     *
     * @return void
     */
    function displayAddressInCategory ($category = '')
    {
        $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email`,
                    `country`, `address`, `city`, `state`, `zip`
                FROM `fcms_users` AS u, `fcms_address` as a 
                WHERE u.`id` = a.`user` 
                AND (
                    `password` != 'PRIVATE' 
                    OR (
                        a.`created_id` = ".$this->fcmsUser->id." 
                        AND `password` = 'PRIVATE' 
                    )
                )
                ORDER BY `lname`";

        $cat = 'cat=all&amp;';
        if ($category == 'members')
        {
            $cat = 'cat=members&amp;';
            $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email`,
                        `country`, `address`, `city`, `state`, `zip`
                    FROM `fcms_users` AS u, `fcms_address` as a 
                    WHERE u.`id` = a.`user` 
                    AND `password` != 'NONMEMBER' 
                    AND `password` != 'PRIVATE' 
                    ORDER BY `lname`";
        }
        else if ($category == 'non')
        {
            $cat = 'cat=non&amp;';
            $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email`,
                        `country`, `address`, `city`, `state`, `zip`
                    FROM `fcms_users` AS u, `fcms_address` as a 
                    WHERE u.`id` = a.`user` 
                    AND `password` = 'NONMEMBER' 
                    ORDER BY `lname`";
        }
        else if ($category == 'my')
        {
            $cat = 'cat=my&amp;';
            $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email`,
                        `country`, `address`, `city`, `state`, `zip`
                    FROM `fcms_users` AS u, `fcms_address` as a 
                    WHERE u.`id` = a.`user` 
                    AND a.`created_id` = ".$this->fcmsUser->id." 
                    AND `password` = 'PRIVATE' 
                    ORDER BY `lname`";
        }

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        foreach ($rows as $r)
        {
            $email = '';

            if (!empty($r['email']))
            {
                $email = '<input type="checkbox" name="massemail[]" value="'.cleanOutput($r['email']).'"/>';
            }

            $address = '';
            if (!empty($r['address']))
            {
                $address .= cleanOutput($r['address']).', ';
            }

            if (!empty($r['city']))
            {
                $address .= cleanOutput($r['city']).', ';
            }

            if (!empty($r['state']))
            {
                $address .= cleanOutput($r['state']).', ';
            }

            if (!empty($r['zip']))
            {
                $address .= cleanOutput($r['zip']);
            }

            echo '
                        <tr>
                            <td class="chk">'.$email.'</td>
                            <td><a href="?'.$cat.'address='.(int)$r['id'].'">
                                '.cleanOutput($r['lname']).', '.cleanOutput($r['fname']).'</a></td>
                            <td>'.$address.'</td>
                            <td>'.formatPhone($r['home'], $r['country']).'</td>
                        </tr>';
        }
    }

    /**
     * displayEditForm
     *
     * Displays the form for editing an address.
     *
     * @param int    $addressid the id of the address you want to edit
     * @param string $cancel    the url to cancel and go back to
     * @param string $submit    the url to submit the form to
     *
     * @return void
     */
    function displayEditForm ($addressid, $cancel, $submit = 'addressbook.php')
    {
        $addressid = (int)$addressid;

        $sql = "SELECT a.`id`, u.`id` AS uid, `fname`, `lname`, `email`, `country`,
                    `address`, `city`, `state`, `zip`, `home`, `work`, `cell` 
                FROM `fcms_users` AS u, `fcms_address` AS a 
                WHERE a.`id` = '$addressid' 
                AND a.`user` = u.`id`";

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row == false)
        {
            $this->fcmsError->displayError();

            return;
        }

        if ($this->fcmsUser->access >= 2 && $this->fcmsUser->id != $row['uid'])
        {
            echo '
                    <p class="error-alert">'.T_('You do not have permission to perform this task.').'</p>';
            return;
        }

        // Setup vars for output
        $email   = cleanOutput($row['email']);
        $address = cleanOutput($row['address']);
        $city    = cleanOutput($row['city']);
        $state   = cleanOutput($row['state']);
        $zip     = cleanOutput($row['zip']);
        $home    = cleanOutput($row['home']);
        $work    = cleanOutput($row['work']);
        $cell    = cleanOutput($row['cell']);

        $country_list    = buildCountryList();
        $country_options = buildHtmlSelectOptions($country_list, $row['country']);

        // Print the form
        echo '
            <script type="text/javascript" src="ui/js/livevalidation.js"></script>
            <form id="addressbook_form" action="'.$submit.'" method="post">
                <fieldset>
                    <legend><span>'.T_('Edit Address').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="email"><b>'.T_('Email').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="email" id="email" size="50" value="'.$email.'"/></div>
                    </div>
                    <script type="text/javascript">
                        var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                        femail.add( Validate.Email, { failureMessage: "'.T_('That\'s not a valid email, is it?').'"});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="country"><b>'.T_('Country').'</b></label></div>
                        <div class="field-widget">
                            <select name="country" id="country">
                                <option></option>
                                <option value="US">'.T_('UNITED STATES').'</option>
                                <option>------</option>
                                '.$country_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="address"><b>'.T_('Street Address').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="address" id="address" size="25" value="'.$address.'"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="city"><b>'.T_('City').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="city" id="city" size="50" value="'.$city.'"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="state"><b>'.T_('State').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="state" id="state" size="50" value="'.$state.'"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="zip"><b>'.T_('Zip Code').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="zip" id="zip" size="10" value="'.$zip.'"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="home"><b>'.T_('Home Phone').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="home" id="home" size="20" value="'.$home.'"/></div>
                    </div>
                    <script type="text/javascript">
                        var fhome = new LiveValidation(\'home\', { onlyOnSubmit: true });
                        fhome.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="work"><b>'.T_('Work Phone').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="work" id="work" size="20" value="'.$work.'"/></div>
                    </div>
                    <script type="text/javascript">
                        var fwork = new LiveValidation(\'work\', { onlyOnSubmit: true });
                        fwork.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="cell"><b>'.T_('Cell Phone').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="cell" id="cell" size="20" value="'.$cell.'"/></div>
                    </div>
                    <script type="text/javascript">
                        var fcell = new LiveValidation(\'cell\', { onlyOnSubmit: true });
                        fcell.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>
                    <div>
                        <input type="hidden" name="aid" value="'.$addressid.'"/>
                        <input type="hidden" name="uid" value="'.(int)$row['uid'].'"/>
                        <input type="hidden" name="cat" value="'.(isset($_GET['cat']) ? (int)$_GET['cat'] : 0).'"/>
                    </div>
                    <p>
                        <input class="sub1" type="submit" name="editsubmit" value="'.T_('Edit').'"/>';

        if ($cancel != '')
        {
            echo '
                        '.T_('or').' 
                        <a href="'.$cancel.'">'.T_('Cancel').'</a>';
        }

        print '
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * displayAddForm
     *
     * Displays the form for adding an address.
     *
     * @return void
     */
    function displayAddForm ()
    {
        $country_list    = buildCountryList();
        $selected        = getDefaultCountry();
        $country_options = buildHtmlSelectOptions($country_list, $selected);

        // TODO
        // Make this a removable alert message (part of Alerts table)
        echo '
            <script type="text/javascript" src="ui/js/livevalidation.js"></script>
            <form id="addressbook_form" action="addressbook.php" method="post">
                <p class="info-alert">
                    '.T_('Please only add addresses for Non-members. Anyone who is a member of this website must add/update their own address.').'
                </p>
                <fieldset>
                    <legend><span>'.T_('Add Address').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="fname"><b>'.T_('First Name').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="fname" id="fname" size="25"/></div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                        ffname.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="lname"><b>'.T_('Last Name').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="lname" id="lname" size="25"/></div>
                    </div>
                    <script type="text/javascript">
                        var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                        flname.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="email"><b>'.T_('Email').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="email" id="email" size="50"/></div>
                    </div>
                    <script type="text/javascript">
                        var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                        femail.add( Validate.Email, { failureMessage: "'.T_('That\'s not a valid email, is it?').'"});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="country"><b>'.T_('Country').'</b></label></div>
                        <div class="field-widget">
                            <select name="country" id="country">
                                <option></option>
                                <option value="US">'.T_('UNITED STATES').'</option>
                                <option>------</option>
                                '.$country_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="address"><b>'.T_('Street Address').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="address" id="address" size="25"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="city"><b>'.T_('City').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="city" id="city" size="50"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="state"><b>'.T_('State').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="state" id="state" size="50"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="zip"><b>'.T_('Zip Code').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="zip" id="zip" size="10"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="home"><b>'.T_('Home Phone').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="home" id="home" size="20"/></div>
                    </div>
                    <script type="text/javascript">
                        var fhome = new LiveValidation(\'home\', { onlyOnSubmit: true });
                        fhome.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="work"><b>'.T_('Work Phone').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="work" id="work" size="20"/></div>
                    </div>
                    <script type="text/javascript">
                        var fwork = new LiveValidation(\'work\', { onlyOnSubmit: true });
                        fwork.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="cell"><b>'.T_('Cell Phone').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="cell" id="cell" size="20"/></div>
                    </div>
                    <script type="text/javascript">
                        var fcell = new LiveValidation(\'cell\', { onlyOnSubmit: true });
                        fcell.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="private"><b>'.T_('Private').'</b></label></div>
                        <div class="field-widget"><input type="checkbox" name="private" id="private"/></div>
                    </div>
                    <p>
                        <input class="sub1" type="submit" name="addsubmit" value="'.T_('Add').'"/> 
                        '.T_('or').' 
                        <a href="addressbook.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * displayMassEmailForm 
     *
     * Displays the form for sending out mass emails.
     * 
     * @param array  $emails  The email addresses you are mass mailing to
     * @param string $email   The email address you are sending from
     * @param string $name    The name you are sending from
     * @param string $subject The subject of the email
     * @param string $message The body of the email
     * @param string $show    Show errors or not
     * 
     * @return  void
     */
    function displayMassEmailForm ($emails, $email = '', $name = '', $subject = '', $message = '', $show = '')
    {
        $errors      = false;
        $err_email   = '';
        $err_name    = '';
        $err_subject = '';
        $err_msg     = '';

        // Are we allowed to show errors?
        if (!empty($show))
        {
            if (empty($email))
            {
                $errors    = true;
                $err_email = '<br/><span class="error">'.T_('Required').'</span>';
            }
            if (empty($name))
            {
                $errors   = true;
                $err_name = '<br/><span class="error">'.T_('Required').'</span>';
            }
            if (empty($subject))
            {
                $errors      = true;
                $err_subject = '<br/><span class="error">'.T_('Required').'</span>';
            }
            if (empty($message))
            {
                $errors  = true;
                $err_msg = '<br/><span class="error">'.T_('Required').'</span>';
            }
        }

        echo '
            <p class="info-alert">
                '.T_('Filling out the form below will send an email to all the selected members in your addressbook. Sending an email to a large number of people can take a long time. Please be patient.').'
            </p>';

        if ($errors)
        {
            echo '
            <p class="error-alert">'.T_('Missing Required Field').'</p>';
        }

        echo '
            <script type="text/javascript" src="ui/js/livevalidation.js"></script>
            <form method="post" class="contactform" action="addressbook.php">
                <fieldset>
                    <div class="field-row">
                        <div class="field-label"><label for="email"><b>'.T_('Your Email').'</b></label></div>
                        <div class="field-widget">
                            <input class="frm_text" value="'.cleanOutput($email).'" type="text" name="email" id="email" size="30"/>
                            '.$err_email.'
                        </div>
                    </div>
                    <script type="text/javascript">
                        var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                        femail.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="name"><b>'.T_('Your Name').'</b></label></div>
                        <div class="field-widget">
                            <input class="frm_text" value="'.cleanOutput($name).'" type="text" name="name" id="name" size="30"/>
                            '.$err_name.'
                        </div>
                    </div>
                    <script type="text/javascript">
                        var fname = new LiveValidation(\'name\', { onlyOnSubmit: true });
                        fname.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="subject"><b>'.T_('Subject').'</b></label></div>
                        <div class="field-widget">
                            <input class="frm_text" value="'.cleanOutput($subject).'" type="text" name="subject" id="subject" size="30"/>
                            '.$err_subject.'
                        </div>
                    </div>
                    <script type="text/javascript">
                        var fsub = new LiveValidation(\'subject\', { onlyOnSubmit: true });
                        fsub.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="msg"><b>'.T_('Message').'</b></label></div>
                        <div class="field-widget">
                            <textarea name="msg" id="msg" rows="10" cols="40"/>'.cleanOutput($message, 'html').'</textarea>
                            '.$err_msg.'
                        </div>
                    </div>
                    <script type="text/javascript">
                        var fmsg = new LiveValidation(\'msg\', { onlyOnSubmit: true });
                        fmsg.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div>';

        foreach ($emails as $email)
        {
            echo '
                        <input type="hidden" name="emailaddress[]" value="'.cleanOutput($email).'"/>';
        }
        echo '
                    </div>
                    <p>
                        <input class="sub1" type="submit" name="sendemailsubmit" value="'.T_('Send Email').'"/> 
                        '.T_('or').'&nbsp; 
                        <a href="addressbook.php">'.T_('Cancel').'</a>
                    </p>
                </field>
            </form>';
    }

    /**
     * userHasAddress
     *
     * Checks whether or not the user has entered address info.
     *
     * @param int $id The user's id
     *
     * @return boolean
     */
    function userHasAddress ($id)
    {
        $sql = "SELECT `state`, `home`, `work`, `cell`
                FROM `fcms_address` 
                WHERE `user` = ?";

        $r = $this->fcmsDatabase->getRow($sql, $id);
        if ($r === false)
        {
            $this->fcmsError->displayError();
            return false;
        }

        if (count($r) >= 1)
        {
            // Must fill in at least state and one phone number to be
            // considered having address info filled out
            if (!empty($r['state']) && (!empty($r['home']) || !empty($r['work']) || !empty($r['cell'])))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * displayImportForm 
     *
     * Displays the form to allow csv imports.
     * 
     * @return void
     */
    function displayImportForm ()
    {
        echo '
            <h2>'.T_('Import').'</h2><br/>
            <form method="post" name="csv-form" action="addressbook.php?csv=import" enctype="multipart/form-data" >
                <div><input type="file" name="csv" id="csv" /></div>
                <p>
                    <label for="private">'.T_('Private').'</label> &nbsp;
                    <input type="checkbox" name="private" id="private"/>
                </p>
                <p>
                    <input type="submit" id="import" name="import" value="'.T_('Import').'"/> 
                    '.T_('or').' 
                    <a href="addressbook.php">'.T_('Cancel').'</a>
                </p>
            </form>';
    }

    /**
     * importAddressCsv
     *
     * Imports a CSV file into the address book
     *
     * @param array $file The csv file
     *
     * @return void
     */
    function importAddressCsv ($file)
    {
        if (!in_array($file['type'], array('text/plain', 'text/x-csv', 'text/csv', 'application/vnd.ms-excel', 'application/octet-stream')))
        {
            echo '
            <p class="error-alert">'.sprintf(T_('%s (%s) is not a CSV file.'), $file['name'], $file['type']).'</p>';

            return;
        }

        // Read in the file and parse the data to an array of arrays
        $addresses = array();
        $handle    = fopen($file['tmp_name'], "r");

        $row = 0;
        while (($data = fgetcsv($handle, 4096, ",")) !== false)
        {
            if ($row == 0)
            {
                // Get Column headers
                $headers = $data;
                $row++;
            }
            else
            {
                $num = count($data);
                $row++;

                for ($i=0; $i < $num; $i++)
                {
                    if ($data[$i])
                    {
                        $addresses[$row][$headers[$i]] = $data[$i];
                    }
                }
            }
        }

        // Loop through the multidimensional array and insert valid addresses into db
        $i = 0;
        foreach ($addresses as $address)
        {
            // First Name
            $fname = '';
            if (isset($address['fname']))
            {
                // FCMS
                $fname = $address['fname'];
            }
            elseif (isset($address['First Name']))
            {
                // Outlook
                $fname = $address['First Name'];
            }
            elseif (isset($address['Given Name']))
            {
                // Gmail
                $fname = $address['Given Name'];
            }

            // Last Name
            $lname = '';
            if (isset($address['lname']))
            {
                // FCMS
                $lname = $address['lname'];
            }
            elseif (isset($address['Last Name']))
            {
                // Outlook
                $lname = $address['Last Name'];
            }
            elseif (isset($address['Family Name']))
            {
                // Gmail
                $lname = $address['Family Name'];
            }

            // Email
            $email = '';
            if (isset($address['email']))
            {
                // FCMS
                $email = $address['email'];
            }
            elseif (isset($address['E-mail Address']))
            {
                // Outlook
                $email = $address['E-mail Address'];
            }
            elseif (isset($address['E-mail 1 - Value']))
            {
                // Gmail
                $email = $address['E-mail 1 - Value'];
            }

            // Street Address
            $street = '';
            $city   = '';
            $state  = '';
            $zip    = '';
            if (isset($address['address']))
            {
                // FCMS
                $street = $address['address'];
            }
            elseif (isset($address['Home Address']))
            {
                // Outlook (all in one)
                // Try to parse the data into individual fields
                // This only works for US formatted addressess
                $endStreet = strpos($address['Home Address'], "\n");
                if ($endStreet !== false)
                {
                    $street  = substr($address['Home Address'], 0, $endStreet-1);
                    $endCity = strpos($address['Home Address'], ",", $endStreet);
                    if ($endCity !== false)
                    {
                        $city   = substr($address['Home Address'], $endStreet+1, ($endCity - $endStreet)-1);
                        $tmpZip = substr($address['Home Address'], -5);
                        if (is_numeric($tmpZip))
                        {
                            $endZip = strpos($address['Home Address'], $tmpZip, $endCity);
                            if ($endZip !== false)
                            {
                                $state = substr($address['Home Address'], $endCity+2);
                                $state = substr($state, 0, -6);  // 5 zip + space
                                $zip   = $tmpZip;
                            }
                        }
                        else
                        {
                            $state = substr($address['Home Address'], $endCity);
                        }
                    }
                }
                // Can't figure out which part is which
                else
                {
                    $street = $address['Home Address'];
                }
            }
            elseif (isset($address['Home Street']))
            {
                // Outlook
                $street = $address['Home Street'];
            }
            elseif (isset($address['Address 1 - Formatted']))
            {
                // Gmail (all in one)
                // Try to parse the data into individual fields
                // This only works for US formatted addressess
                $endStreet = strpos($address['Address 1 - Formatted'], "\n");
                if ($endStreet !== false)
                {
                    $street  = substr($address['Address 1 - Formatted'], 0, $endStreet-1);
                    $endCity = strpos($address['Address 1 - Formatted'], ",", $endStreet);
                    if ($endCity !== false)
                    {
                        $city   = substr($address['Address 1 - Formatted'], $endStreet+1, ($endCity - $endStreet)-1);
                        $tmpZip = substr($address['Address 1 - Formatted'], -5);
                        if (is_numeric($tmpZip))
                        {
                            $endZip = strpos($address['Address 1 - Formatted'], $tmpZip, $endCity);
                            if ($endZip !== false)
                            {
                                $state = substr($address['Address 1 - Formatted'], $endCity+2);
                                $state = substr($state, 0, -6);  // 5 zip + space
                                $zip   = $tmpZip;
                            }
                        }
                        else
                        {
                            $state = substr($address['Address 1 - Formatted'], $endCity);
                        }
                    }
                }
                // Can't figure out which part is which
                else
                {
                    $street = $address['Address 1 - Formatted'];
                }
            }
            elseif (isset($address['Address 1 - Street']))
            {
                // Gmail
                $street = $address['Address 1 - Street'];
            }

            // City
            if (isset($address['city']))
            {
                // FCMS
                $city = $address['city'];
            }
            elseif (isset($address['Home City']))
            {
                // Outlook
                $city = $address['Home City'];
            }
            elseif (isset($address['Address 1 - City']))
            {
                // Gmail
                $city = $address['Address 1 - City'];
            }

            // State
            if (isset($address['state']))
            {
                // FCMS
                $state = $address['state'];
            }
            elseif (isset($address['Home State']))
            {
                // Outlook
                $state = $address['Home State'];
            }
            elseif (isset($address['Address 1 - Region']))
            {
                // Gmail
                $state = $address['Address 1 - Region'];
            }

            // Zip
            if (isset($address['zip']))
            {
                // FCMS
                $zip = $address['zip'];
            }
            elseif (isset($address['Home Postal Code']))
            {
                // Outlook
                $zip = $address['Home Postal Code'];
            }
            elseif (isset($address['Address 1 - Postal Code']))
            {
                // Gmail
                $zip = $address['Address 1 - Postal Code'];
            }

            // Phone Numbers
            $home = '';
            $work = '';
            $cell = '';
            // FCMS
            if (isset($address['home']))
            {
                $home = $address['home'];
            }
            if (isset($address['work']))
            {
                $work = $address['work'];
            }
            if (isset($address['cell']))
            {
                $cell = $address['cell'];
            }
            // Outlook
            if (isset($address['Home Phone']))
            {
                $home = $address['Home Phone'];
            }
            if (isset($address['Business Phone']))
            {
                $work = $address['Business Phone'];
            }
            if (isset($address['Mobile Phone']))
            {
                $cell = $address['Mobile Phone'];
            }
            // Gmail
            if (isset($address['Phone 1 - Type']))
            {
                switch ($address['Phone 1 - Type'])
                {
                    case 'Home':
                        $home = $address['Phone 1 - Value'];
                        break;
                    case 'Work':
                        $work = $address['Phone 1 - Value'];
                        break;
                    case 'Mobile':
                        $cell = $address['Phone 1 - Value'];
                        break;
                }
            }
            if (isset($address['Phone 2 - Type']))
            {
                switch ($address['Phone 2 - Type'])
                {
                    case 'Home':
                        $home = $address['Phone 2 - Value'];
                        break;
                    case 'Work':
                        $work = $address['Phone 2 - Value'];
                        break;
                    case 'Mobile':
                        $cell = $address['Phone 2 - Value'];
                        break;
                }
            }
            if (isset($address['Phone 3 - Type']))
            {
                switch ($address['Phone 3 - Type'])
                {
                    case 'Home':
                        $home = $address['Phone 3 - Value'];
                        break;
                    case 'Work':
                        $work = $address['Phone 3 - Value'];
                        break;
                    case 'Mobile':
                        $cell = $address['Phone 3 - Value'];
                        break;
                }
            }

            // Create non-member
            $uniq = uniqid("");
            $pw   = 'NONMEMBER';

            if (isset($_POST['private']))
            {
                $pw = 'PRIVATE';
            }

            $sql = "INSERT INTO `fcms_users`
                        (`access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`)
                    VALUES (3, NOW(), ?, ?, ?, 'NONMEMBER-$uniq', ?)";

            $params = array(
                $fname, 
                $lname, 
                $email, 
                $pw
            );

            $id = $this->fcmsDatabase->insert($sql, $params);
            if ($id === false)
            {
                $this->fcmsError->displayError();

                return;
            }

            // Create address for non-member
            $sql = "INSERT INTO `fcms_address`
                        (`user`, `created_id`, `created`, `updated_id`, `updated`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell`)
                    VALUES
                        (?, ?, NOW(), ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";

            $params = array(
                $id, 
                $this->fcmsUser->id, 
                $this->fcmsUser->id, 
                $street, 
                $city, 
                $state, 
                $zip, 
                $home, 
                $work, 
                $cell
            );

            if ($this->fcmsDatabase->insert($sql, $params))
            {
                $this->fcmsError->displayError();

                return;
            }

            $i++;
        }
        echo '
            <p class="ok-alert">
                '.sprintf(T_ngettext('%d Address Added Successfully', '%d Addresses Added Successfully', $i), $i).'
            </p>';
    }

}
