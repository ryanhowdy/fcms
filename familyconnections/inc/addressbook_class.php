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
                    `zip`, `home`, `work`, `cell`, `email`, `phpass` 
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

        $templateParams = array(
            'addressText'     => T_('Address'),
            'emailText'       => T_('Email'),
            'homeText'        => T_pgettext('The dwelling where you live.', 'Home'),
            'workText'        => T_('Work'),
            'mobileText'      => T_('Mobile'),
            'emailMemberText' => T_('Email This Member'),
            'avatar'          => getCurrentAvatar($r['user']),
            'name'            => cleanOutput($r['fname']).' '.cleanOutput($r['lname']),
            'addressOptions' => array(
                array(
                    'liId' => 'back',
                    'aId'  => '',
                    'url'  => '?cat='.$cat,
                    'text' => T_('Back to Addresses'),
                ),
                array(
                    'liId' => 'email',
                    'aId'  => '',
                    'url'  => 'mailto:'.cleanOutput($r['email']),
                    'text' => T_('Email'),
                ),
            ),
        );

        // Edit / Delete links
        if ($this->fcmsUser->id == $r['user'] || $this->fcmsUser->access < 2)
        {
            $templateParams['addressOptions'][] = array(
                'liId' => 'edit',
                'aId'  => '',
                'url'  => '?cat='.$cat.'&amp;edit='.(int)$r['id'],
                'text' => T_('Edit'),
            );

            if ($r['phpass'] == 'NONMEMBER' || $r['phpass'] == 'PRIVATE')
            {
                $templateParams['addressOptions'][] = array(
                    'liId' => 'delete',
                    'aId'  => 'del_address',
                    'url'  => '?cat='.$cat.'&amp;delete='.(int)$r['id'],
                    'text' => T_('Delete'),
                );
            }
        }

        // Address
        $templateParams['address']    = formatAddress($r);
        $templateParams['addressUrl'] = formatAddressUrl($templateParams['address']);

        if ($templateParams['address'] == '')
        {
            $templateParams['address'] = "<i>(".T_('none').")</i>";
        }

        // Email
        $templateParams['email'] = empty($r['email']) ? "<i>(".T_('none').")</i>" : cleanOutput($r['email']); 

        // Phone Number
        $templateParams['home']   = empty($r['home']) ? '<i>('.T_('none').')</i>' : formatPhone($r['home'], $r['country']);
        $templateParams['work']   = empty($r['work']) ? '<i>('.T_('none').')</i>' : formatPhone($r['work'], $r['country']);
        $templateParams['mobile'] = empty($r['cell']) ? '<i>('.T_('none').')</i>' : formatPhone($r['cell'], $r['country']);

        $categories = $this->getCategoryParams($cat);

        $templateParams = array_merge($templateParams, $categories);

        loadTemplate('addressbook', 'address', $templateParams);
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
        $templateParams = array(
            'addNewAddressText' => T_('Add New Address'),
            'nameText'          => T_('Name'),
            'addressText'       => T_('Address'),
            'phoneText'         => T_('Phone'),
        );

        $categories = $this->getCategoryParams($cat);
        $addresses  = $this->getAddressInCategoryParams($cat);

        $templateParams = array_merge($templateParams, $categories);
        $templateParams = array_merge($templateParams, $addresses);

        if ($this->fcmsUser->access <= 3)
        {
            $templateParams['allowedToEmail']    = 1;
            $templateParams['emailSelectedText'] = T_('Email Selected');
        }

        loadTemplate('addressbook', 'main', $templateParams);
    }

    /**
     * getCategoryParams
     *
     * Returns an array of template parameters for showing the list of categories.
     *
     * @param string $selected The currently selected category
     *
     * @return array
     */
    function getCategoryParams ($selected = 'members')
    {
        $rv  = array();
        $all = '';
        $my  = '';
        $mem = '';
        $non = '';

        if ($selected == 'all')
        {
            $all = 'selected';
        }
        if ($selected == 'my')
        {
            $my = 'selected';
        }
        if ($selected == 'members')
        {
            $mem = 'selected';
        }
        if ($selected == 'non')
        {
            $non = 'selected';
        }

        $rv['viewText']   = T_('View');
        $rv['optionText'] = T_('Options');

        $rv['categories'] = array(
            array(
                'liClass' => $all,
                'url'     => '?cat=all',
                'title'   => '',
                'text'    => T_('All'),
            ),
            array(
                'liClass' => $my,
                'url'     => '?cat=my',
                'title'   => T_('Only show My personal Addresses'),
                'text'    => T_('My Addresses'),
            ),
            array(
                'liClass' => $mem,
                'url'     => '?cat=members',
                'title'   => T_('Only show Addresses for members of the site'),
                'text'    => T_('Members'),
            ),
            array(
                'liClass' => $non,
                'url'     => '?cat=non',
                'title'   => T_('Only show Addresses for non-members'),
                'text'    => T_('Non-Members'),
            ),
        );

        $rv['options'] = array(
            array(
                'url'  => '?csv=import',
                'text' => T_('Import'),
            ),
            array(
                'url'  => '?csv=export',
                'text' => T_('Export'),
            ),
        );

        return $rv;
    }

    /**
     * getAddressInCategoryParams
     *
     * Returns an array of template params for all the addresses in the given category.
     *
     * @param string $category Category name
     *
     * @return array
     */
    function getAddressInCategoryParams ($category = '')
    {
        $rv = array();

        // All addresses
        $cat = 'cat=all&amp;';
        $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email`,
                    `country`, `address`, `city`, `state`, `zip`
                FROM `fcms_users` AS u, `fcms_address` as a
                WHERE u.`id` = a.`user`
                AND ((
                        `phpass` IS NOT NULL
                        AND (
                            `phpass` != 'PRIVATE'
                            OR (a.`created_id` = ? AND `phpass` = 'PRIVATE')
                        )
                    )
                    OR (
                        `phpass` IS NULL
                        AND (
                            `password` != 'PRIVATE'
                            OR (a.`created_id` = ? AND `password` = 'PRIVATE')
                        )
                    )
                )
                ORDER BY `lname`";

        $params = array(
            $this->fcmsUser->id,
            $this->fcmsUser->id
        );

        // Member addresses
        if ($category == 'members')
        {
            $cat = 'cat=members&amp;';
            $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email`,
                        `country`, `address`, `city`, `state`, `zip`
                    FROM `fcms_users` AS u, `fcms_address` as a
                    WHERE u.`id` = a.`user`
                    AND ((
                            `phpass` IS NOT NULL
                            AND `phpass` != 'NONMEMBER'
                            AND `phpass` != 'PRIVATE'
                        )
                        OR (
                            `phpass` IS NULL
                            AND `password` != 'NONMEMBER'
                            AND `password` != 'PRIVATE'
                        )
                    )
                    ORDER BY `lname`";

            $params = array();
        }
        // Non-member addresses
        else if ($category == 'non')
        {
            $cat = 'cat=non&amp;';
            $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email`,
                        `country`, `address`, `city`, `state`, `zip`
                    FROM `fcms_users` AS u, `fcms_address` as a 
                    WHERE u.`id` = a.`user` 
                    AND (`phpass` = 'NONMEMBER' OR `password` = 'NONMEMBER')
                    ORDER BY `lname`";

            $params = array();
        }
        // My (private) addresses
        else if ($category == 'my')
        {
            $cat = 'cat=my&amp;';
            $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email`,
                        `country`, `address`, `city`, `state`, `zip`
                    FROM `fcms_users` AS u, `fcms_address` as a 
                    WHERE u.`id` = a.`user` 
                    AND a.`created_id` = ?
                    AND (`phpass` = 'PRIVATE' OR `password` = 'PRIVATE')
                    ORDER BY `lname`";

            $params = array($this->fcmsUser->id);
        }

        if (count($params) > 0)
        {
            $rows = $this->fcmsDatabase->getRows($sql, $params);
        }
        else
        {
            $rows = $this->fcmsDatabase->getRows($sql);
        }

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

            $rv['addresses'][] = array(
                'checkbox'   => $email,
                'addressUrl' => '?'.$cat.'address='.(int)$r['id'],
                'name'       => cleanOutput($r['lname']).', '.cleanOutput($r['fname']),
                'address'    => $address,
                'phone'      => formatPhone($r['home'], $r['country']),
            );
        }

        return $rv;
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
        $validator       = new FormValidator();

        // TODO
        // Make this a removable alert message (part of Alerts table)
        echo '
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
                    <div class="field-row">
                        <div class="field-label"><label for="lname"><b>'.T_('Last Name').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="lname" id="lname" size="25"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="email"><b>'.T_('Email').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="email" id="email" size="50"/></div>
                    </div>
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
                    <div class="field-row">
                        <div class="field-label"><label for="work"><b>'.T_('Work Phone').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="work" id="work" size="20"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="cell"><b>'.T_('Cell Phone').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="cell" id="cell" size="20"/></div>
                    </div>
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
                '.$validator->getJsValidation($this->getProfile('add')).'
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
                        (`access`, `joindate`, `fname`, `lname`, `email`, `username`, `phpass`)
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

    /**
     * getProfile 
     * 
     * @param string $name 
     * 
     * @return array
     */
    function getProfile ($name)
    {
        $profile = array(
            'add' => array(
                'constraints' => array(
                    'fname' => array(
                        'required' => 1,
                    ),
                    'lname' => array(
                        'required' => 1,
                    ),
                    'email' => array(
                        'format' => '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/',
                    ),
                    'country' => array(
                        'required' => 1,
                        'format'   => '/^[A-Za-z]{2,3}$/',
                    ),
                    'home' => array(
                        'format' => '/^[0-9\.\-\x\s\+\(\)]+$/',
                    ),
                    'work' => array(
                        'format' => '/^[0-9\.\-\x\s\+\(\)]+$/',
                    ),
                    'cell' => array(
                        'format' => '/^[0-9\.\-\x\s\+\(\)]+$/',
                    )
                ),
                'messages' => array(
                    'constraints' => array(
                        'fname' => T_('Required'),
                        'lname' => T_('Required'),
                    ),
                    'names' => array(
                        'fname'   => T_('First Name'),
                        'lname'   => T_('Last Name'),
                        'email'   => T_('Email Address'),
                        'country' => T_('Country'),
                        'address' => T_('Street Address'),
                        'city'    => T_('City'),
                        'state'   => T_('State'),
                        'zip'     => T_('Zip Code'),
                        'home'    => T_('Home Phone Number'),
                        'work'    => T_('Work Phone Number'),
                        'cell'    => T_('Cellular Phone Number')
                    )
                )
            ),
            'delete' => array(
                'constraints' => array(
                    'delete' => array(
                        'required' => 1,
                        'integer'  => 1,
                    ),
                    'cat'   => array(
                        'required' => 1,
                        'format'   => '/(all|my|members|non)/',
                    )
                )
            )
        );

        return $profile[$name];
    }
}
