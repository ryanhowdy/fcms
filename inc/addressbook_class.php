<?php
include_once('database_class.php');
include_once('util_inc.php');

class AddressBook
{

    var $db;
    var $cur_user_id;
    var $tz_offset;

    function AddressBook ($current_user_id, $type, $host, $database, $user, $pass)
    {
        $this->cur_user_id = $current_user_id;
        $this->db = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id";
        $this->db->query($sql) or displaySQLError('Timezone Error', 'inc/addressbook_class.php [' . __LINE__ . ']', $sql, mysql_error());
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
    }

    /*
     *  displayAddress
     *
     *  Displays the selected address information, including the category it belongs to.
     *
     *  @param      $aid    the id of the address you want to show
     *  @param      $cat    the category that the address belongs in
     *  @return     none
     */
    function displayAddress ($aid, $cat)
    {
        // Check for valid address id
        if (!ctype_digit($aid)) {
            echo '
            <p class="error-alert">'._('Invalid Address').'</p>';
            return;
        }

        $sql = "SELECT a.`id`, a.`user`, `fname`, `lname`, `avatar`, `updated`, `address`, `city`, `state`, 
                    `zip`, `home`, `work`, `cell`, `email`, `birthday`, `password` 
                FROM `fcms_address` AS a, `fcms_users` AS u 
                WHERE a.`user` = u.`id` 
                AND a.`id` = $aid";
        $this->db->query($sql) or displaySQLError(
            'Get Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            $r = $this->db->get_row();

            // Set up vars
            // Edit / Delete links
            $edit_del = '';
            if ($this->cur_user_id == $r['user'] || checkAccess($this->cur_user_id) < 2) {
                $edit_del = '<div class="edit_del_menu">
                    <form action="addressbook.php" method="post">
                        <div>
                            <input type="hidden" name="id" value="'.$r['id'].'"/>
                            <input type="submit" id="edit" name="edit" class="editbtn" value="'._('Edit').'"/>';
                if ($r['password'] == 'NONMEMBER' || $r['password'] == 'PRIVATE') {
                    $edit_del .='
                            <input type="submit" id="del" name="del" class="delbtn" value="'._('Delete').'"/>';
                }
                $edit_del .= '
                        </div>
                    </form>
                </div>';
            }
            // Address
            $address = '';
            if (empty($r['address']) && empty($r['state'])) {
                $address = "<i>("._('none').")</i>";
            } else {
                if (!empty($r['address'])) {
                    $address .= $r['address'] . "<br/>";
                }
                if (!empty($r['city'])) {
                    $address .= $r['city'] . ", ";
                }
                $address .= $r['state'] . " " . $r['zip'];
            }
            // Email
            if (empty($r['email'])) {
                $email = "<i>("._('none').")</i>";
            } else {
                $email = $r['email'] . ' <a class="email" href="mailto:' . $r['email'] . '" title="'._('Email This Member').'">&nbsp;</a>';
            }
            // Phone Numbers
            $home = empty($r['home']) ? "<i>(" . _('none') . ")</i>" : $r['home'];
            $work = empty($r['work']) ? "<i>(" . _('none') . ")</i>" : $r['work'];
            $cell = empty($r['cell']) ? "<i>(" . _('none') . ")</i>" : $r['cell'];
            
            // Display address
            echo '
                <div id="addressbook-list" class="clearfix">';
            $this->displayToolbar();
            echo '
                    <div id="address-categories">';
            $this->displayCategories($cat);
            echo '
                    </div>
                    <div id="addresses">';
            $this->displayAddressInCategory($cat, $aid);
            echo '
                    </div>
                    <div id="address-details">
                        '.$edit_del.'
                        <p><img src="gallery/avatar/'.$r['avatar'].'"/><b>'.$r['lname'].', '.$r['fname'].'</b></p>
                        <p class="clearfix"><b class="label">'._('Address').':</b><span class="data">'.$address.'</span></p>
                        <p class="clearfix"><b class="label">'._('Email').':</b><span class="data">'.$email.'</span></p>
                        <p class="clearfix"><b class="label">'._('Home').':</b><span class="data">'.$home.'</span></p>
                        <p class="clearfix"><b class="label">'._('Work').':</b><span class="data">'.$work.'</span></p>
                        <p class="clearfix"><b class="label">'._('Mobile').':</b><span class="data">'.$cell.'</span></p>
                    </div>
                </div>';
        } else {
            echo '
            <p class="error-alert">'.sprintf(_('Could not find address (%s)'), $aid).'</p>';
        }
    }

    /*
     *  displayAddressList
     *
     *  Displays the categories and the user's in that category.
     *
     *  @param      $cat    the category
     *  @return     none
     */
    function displayAddressList ($cat = '')
    {
        $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email` 
                FROM `fcms_users` AS u, `fcms_address` as a 
                WHERE u.`id` = a.`user` 
                ORDER BY `lname`";
        echo '
            <form action="addressbook.php" id="mass_mail_form" name="mass_mail_form" method="post">
                <div id="addressbook-list" class="clearfix">';
        $this->displayToolbar();
        echo '
                    <div id="address-categories">';
        $this->displayCategories($cat);
        echo '
                    </div>
                    <div id="addresses">';
        $this->displayAddressInCategory($cat);
        echo '
                    </div>
                    <div id="address-details">
                        <p>'._('Choose an Address from the list.').'</p>
                    </div>
                </div>';
        $dis = '';
        if (checkAccess($_SESSION['login_id']) > 3) {
            $dis = 'disabled="disabled"';
        }
        echo '
                <div class="alignright"><input '.$dis.' type="submit" name="emailsubmit" value="'._('Email Selected').'"/></div>
            </form>';
    }

    function displayToolbar ()
    {
        echo '
                    <div id="address-toolbar" class="clearfix">
                        <ul id="add">
                            <li><a class="add" href="?add=yes">'._('Add Contact').'</a></li>
                            <li><a href="?address='.$this->cur_user_id.'">'._('View My Address').'</a></li>
                        </ul>
                        <ul id="import-export">
                            <li><a href="?csv=import">'._('Import').'</a></li>
                            <li><a href="?csv=export">'._('Export').'</a></li>
                        </ul>
                    </div>';
    }

    /*
     *  displayCategories
     *
     *  Displays the list of categories.
     *
     *  @param      $selected   the currently selected category
     *  @return     none
     */
    function displayCategories ($selected = '')
    {
        $all = $my = $mem = $non = '';
        if ($selected == '') {
            $all = 'class="selected"';
        }
        if ($selected == 'my') {
            $my = 'class="selected"';
        }
        if ($selected == 'members') {
            $mem = 'class="selected"';
        }
        if ($selected == 'non') {
            $non = 'class="selected"';
        }
        echo '
                        <ul>
                            <li '.$all.'><a href="addressbook.php">'._('All Addresses').'</a></li>
                            <li '.$my.'><a href="?cat=my" title="'._('Only show My personal Addresses').'">'._('My Addresses').'</a></li>
                            <li '.$mem.'><a href="?cat=members" title="'._('Only show Addresses for members of the site').'">'._('Members').'</a></li>
                            <li '.$non.'><a href="?cat=non" title="'._('Only show Addresses for non-members').'">'._('Non-Members').'</a></li>
                        </ul>';
    }

    /*
     *  displayAddressInCategory
     *
     *  Displays all the addresses in the given category.
     *
     *  @param      $category    the category, if any
     *  @param      $selected    the addresses that is selected, if any
     *  @return     none
     */
    function displayAddressInCategory ($category = '', $selected = '')
    {
        $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email` 
                FROM `fcms_users` AS u, `fcms_address` as a 
                WHERE u.`id` = a.`user` 
                AND `password` != 'PRIVATE' 
                ORDER BY `lname`";
        $cat = '';
        if ($category == 'members') {
            $cat = 'cat=members&amp;';
            $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email` 
                    FROM `fcms_users` AS u, `fcms_address` as a 
                    WHERE u.`id` = a.`user` 
                    AND `password` != 'NONMEMBER' 
                    AND `password` != 'PRIVATE' 
                    ORDER BY `lname`";
        }
        if ($category == 'non') {
            $cat = 'cat=non&amp;';
            $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email` 
                    FROM `fcms_users` AS u, `fcms_address` as a 
                    WHERE u.`id` = a.`user` 
                    AND `password` = 'NONMEMBER' 
                    ORDER BY `lname`";
        }
        if ($category == 'my') {
            $cat = 'cat=my&amp;';
            $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email` 
                    FROM `fcms_users` AS u, `fcms_address` as a 
                    WHERE u.`id` = a.`user` 
                    AND a.`entered_by` = ".$this->cur_user_id." 
                    AND `password` = 'PRIVATE' 
                    ORDER BY `lname`";
        }
        echo '
                        <div id="check-all"></div>
                        <ul>';
        $result = mysql_query($sql) or displaySQLError(
            'Get Addresses Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = mysql_fetch_array($result)) {
            $sel = '';
            if ($r['id'] == $selected) {
                $sel = 'selected';
            }
            $email = '<input disabled="disabled" type="checkbox"/>';
            if (!empty($r['email'])) {
                $email = '<input type="checkbox" name="massemail[]" value="'.htmlentities($r['email'], ENT_COMPAT, 'UTF-8').'"/>';
            }
            echo '
                            <li class="clearfix '.$sel.'"> '.$email.'<a href="?'.$cat.'address='.$r['id'].'">'.$r['lname'].', '.$r['fname'].'</a></li>';
        }
        echo '
                        </ul>';
    }

    /*
     *  displayForm
     *
     *  Displays the form for adding/editing an address.
     *
     *  @param      $type       edit | add
     *  @param      $addressid  the id of the address you want to edit
     *  @return     none
     */
    function displayForm ($type, $addressid = '0')
    {
        if ($type == 'edit') {
            $sql = "SELECT a.`id`, u.`id` AS uid, `fname`, `lname`, `email`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell` "
                 . "FROM `fcms_users` AS u, `fcms_address` AS a "
                 . "WHERE a.`id` = $addressid "
                 . "AND a.`user` = u.`id`";
            $this->db->query($sql) or displaySQLError('Get Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            $row=$this->db->get_row();
        }

        // Setup vars for output
        if ($type == 'edit') {
            $note = '';
            $legend = _('Edit Address') . " (" . stripslashes($row['fname']) . " " . stripslashes($row['lname']) . ")";
            $add = '';
            $email = htmlentities($row['email'], ENT_COMPAT, 'UTF-8');
            $address = htmlentities($row['address'], ENT_COMPAT, 'UTF-8');
            $city = htmlentities($row['city'], ENT_COMPAT, 'UTF-8');
            $state = htmlentities($row['state'], ENT_COMPAT, 'UTF-8');
            $zip = htmlentities($row['zip'], ENT_COMPAT, 'UTF-8');
            $home = htmlentities($row['home'], ENT_COMPAT, 'UTF-8');
            $work = htmlentities($row['work'], ENT_COMPAT, 'UTF-8');
            $cell = htmlentities($row['cell'], ENT_COMPAT, 'UTF-8');
        } else {
            // TODO
            // Make this a removable alert message (part of Alerts table)
            $note = '
            <p class="info-alert">
                '._('Please only add addresses for Non-members. Anyone who is a member of this website must add/update their own address.').'
            </p>';
            $legend = _('Add Address');
            $add = '<div class="field-row clearfix">
                        <div class="field-label"><label for="fname"><b>'._('First Name').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="fname" id="fname" size="25"/></div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                        ffname.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="lname"><b>'._('Last Name').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="lname" id="lname" size="25"/></div>
                    </div>
                    <script type="text/javascript">
                        var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                        flname.add(Validate.Presence, {failureMessage: ""});
                    </script>';
            $email = $address = $city = $state = $zip = $home = $work = $cell = '';
        }

        // Print the form
        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <form id="addressbook_form" action="addressbook.php" method="post">
                '.$note.'
                <fieldset>
                    <legend><span>'.$legend.'</span></legend>
                    '.$add.'
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="email"><b>'._('Email').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="email" id="email" size="50" value="'.$email.'"/></div>
                    </div>
                    <script type="text/javascript">
                        var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                        femail.add( Validate.Email, { failureMessage: "'._('That\'s not a valid email, is it?').'"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="address"><b>'._('Street Address').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="address" id="address" size="25" value="'.$address.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="city"><b>'._('City').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="city" id="city" size="50" value="'.$city.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="state"><b>'._('State').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="state" id="state" size="50" value="'.$state.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="zip"><b>'._('Zip Code').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="zip" id="zip" size="10" value="'.$zip.'"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="home"><b>'._('Home Phone').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="home" id="home" size="20" value="'.$home.'"/></div>
                    </div>
                    <script type="text/javascript">
                        var fhome = new LiveValidation(\'home\', { onlyOnSubmit: true });
                        fhome.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="work"><b>'._('Work Phone').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="work" id="work" size="20" value="'.$work.'"/></div>
                    </div>
                    <script type="text/javascript">
                        var fwork = new LiveValidation(\'work\', { onlyOnSubmit: true });
                        fwork.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="cell"><b>'._('Cell Phone').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="cell" id="cell" size="20" value="'.$cell.'"/></div>
                    </div>
                    <script type="text/javascript">
                        var fcell = new LiveValidation(\'cell\', { onlyOnSubmit: true });
                        fcell.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>';
        if ($type == 'edit') {
            echo '
                    <div>
                        <input type="hidden" name="aid" value="'.$addressid.'"/>
                        <input type="hidden" name="uid" value="'.$row['uid'].'"/>
                    </div>
                    <p>
                        <input class="sub1" type="submit" name="editsubmit" value="'._('Edit').'"/> 
                        '._('or').' 
                        <a href="addressbook.php?address='.$addressid.'">'._('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
        } else {
            echo '
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="private"><b>'._('Private').'</b></label></div>
                        <div class="field-widget"><input type="checkbox" name="private" id="private"/></div>
                    </div>
                    <p>
                        <input class="sub1" type="submit" name="addsubmit" value="'._('Add').'"/> 
                        '._('or').' 
                        <a href="addressbook.php">'._('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
        }
    }

    function displayMassEmailForm ($emails, $email = '', $name = '', $subject = '', $message = '', $show = '')
    {
        $err_email = $err_name = $err_subject = $err_msg = '';
        if (!empty($show)) {
            if (empty($email)) { $err_email = '<br/><span class="error">'._('Required').'</span>'; }
            if (empty($name)) { $err_name = '<br/><span class="error">'._('Required').'</span>'; }
            if (empty($subject)) { $err_subject = '<br/><span class="error">'._('Required').'</span>'; }
            if (empty($msg)) { $err_msg = '<br/><span class="error">'._('Required').'</span>'; }
        }
        echo '
            <p class="info-alert">
                '._('Filling out the form below will send an email to all the selected members in your addressbook. Sending an email to a large number of people can take a long time. Please be patient.').'
            </p>
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <form method="post" class="contactform" action="addressbook.php">
                <fieldset>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="email"><b>'._('Your Email').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="email" id="email" size="30"/>'.$err_email.'</div>
                    </div>
                    <script type="text/javascript">
                        var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                        femail.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="name"><b>'._('Your Name').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="name" id="name" size="30"/>'.$err_name.'</div>
                    </div>
                    <script type="text/javascript">
                        var fname = new LiveValidation(\'name\', { onlyOnSubmit: true });
                        fname.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="subject"><b>'._('Subject').'</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="subject" id="subject" size="30"/>'.$err_subject.'</div>
                    </div>
                    <script type="text/javascript">
                        var fsub = new LiveValidation(\'subject\', { onlyOnSubmit: true });
                        fsub.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="msg"><b>'._('Message').'</b></label></div>
                        <div class="field-widget"><textarea name="msg" id="msg" rows="10" cols="40"/></textarea>'.$err_msg.'</div>
                    </div>
                    <script type="text/javascript">
                        var fmsg = new LiveValidation(\'msg\', { onlyOnSubmit: true });
                        fmsg.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div>';
        foreach ($emails as $email) {
            echo '
                        <input type="hidden" name="emailaddress[]" value="'.$email.'"/>';
        }
        echo '
                    </div>
                    <p>
                        <input class="sub1" type="submit" name="sendemailsubmit" value="'._('Send Email').'"/> 
                        '._('or').'&nbsp; 
                        <a href="addressbook.php">'._('Cancel').'</a>
                    </p>
                </field>
            </form>';
    }

    /*
     *  userHasAddress
     *
     *  Checks whether or not the user has entered address info.
     *
     *  @param      $id    the user's id
     *  @return     true/false
     */
    function userHasAddress ($id)
    {
        $sql = "SELECT * FROM `fcms_address` WHERE `user` = $id";
        $this->db->query($sql) or displaySQLError(
            'Has Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() == 1) {
            $r = $this->db->get_row();
            // Must fill in at least state and one phone number to be
            // considered having address info filled out
            if (
                !empty($r['state']) && 
                ( !empty($r['home']) || !empty($r['work']) || !empty($r['cell']) )
            ) {
                return true;
            } else {
                return false;
            }
        } elseif ($this->db->count_rows() > 1) {
            die("Multiple Addresses found!");
        } else {
            return false;
        }
    }

    function displayWhatsNewAddressBook ()
    {
        $locale = new Locale();
        $today = date('Y-m-d');
        $tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
        echo '
            <h3>'._('Address Book').'</h3>
            <ul>';
        $sql = "SELECT a.id, u.id AS user, lname, fname, username, updated "
             . "FROM fcms_users AS u, fcms_address AS a "
             . "WHERE u.id = a.user "
             . "AND updated >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) "
             . "ORDER BY updated DESC "
             . "LIMIT 0, 5";
        $this->db->query($sql) or displaySQLError(
            'What\'s New Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            while($row = $this->db->get_row()) {
                $displayname = getUserDisplayName($row['user'], 2, false);
                $date = $locale->fixDate(_('M. j, Y, g:i a'), $this->tz_offset, $row['updated']);
                if (
                    strtotime($row['updated']) >= strtotime($today) && 
                    strtotime($row['updated']) > $tomorrow
                ) {
                    $full_date = _('Today');
                    $d = ' class="today"';
                } else {
                    $full_date = $date;
                    $d = '';
                }
                echo '
                <li>
                    <div'.$d.'>'.$full_date.'</div>
                    <a href="addressbook.php?address='.$row['id'].'">'.$displayname.'</a>
                </li>';
            }
        } else {
            echo '
                <li><i>'._('Nothing new Last 30 Days').'</i></li>';
        }
        echo '
            </ul>';
    }

    /*
     *  displayImportForm
     *
     *  Displays the form to allow csv imports.
     */
    function displayImportForm ()
    {
        echo '
            <h2>'._('Import').'</h2><br/>
            <form method="post" name="csv-form" action="addressbook.php?csv=import" enctype="multipart/form-data" >
                <div><input type="file" name="csv" id="csv" /></div>
                <p>
                    <label for="private">'._('Private').'</label> &nbsp;
                    <input type="checkbox" name="private" id="private"/>
                </p>
                <p>
                    <input type="submit" id="import" name="import" value="'._('Import').'"/> 
                    '._('or').' 
                    <a href="addressbook.php">'._('Cancel').'</a>
                </p>
            </form>';
    }

    /*
     *  importAddressCsv
     *
     *  Imports a CSV file into the address book
     *
     *  @param      $file   the csv file
     *  @return     nothing
     */
    function importAddressCsv ($file)
    {
        if (!in_array($file['type'], array('text/plain', 'text/x-csv', 'text/csv'))) {
            echo '
            <p class="error-alert">'.
                sprintf(_('%s (%s) is not a CSV file.'), $file['name'], $file['type']).'
            </p>';
        } else {

            // Read in the file and parse the data to an array of arrays
            $row = 0;
            $addresses = array();
            $handle = fopen($file['tmp_name'], "r");
            while (($data = fgetcsv($handle, 4096, ",")) !== FALSE) {
                if ($row == 0) {
                    // Get Column headers
                    $headers = $data;
                    $row++;
                } else {
                    $num = count($data);
                    $row++;
                    for ($i=0; $i < $num; $i++) {
                        if ($data[$i]) {
                            $addresses[$row][$headers[$i]] = $data[$i];
                        }
                    }
                }
            }

            // Loop through the multidimensional array and insert valid addresses into db
            $i = 0;
            foreach ($addresses as $address) {
                // First Name
                $fname = '';
                if (isset($address['fname'])) {
                    // FCMS
                    $fname = $address['fname'];
                } elseif (isset($address['First Name'])) {
                    // Outlook
                    $fname = $address['First Name'];
                } elseif (isset($address['Given Name'])) {
                    // Gmail
                    $fname = $address['Given Name'];
                }
                // Last Name
                $lname = '';
                if (isset($address['lname'])) {
                    // FCMS
                    $lname = $address['lname'];
                } elseif (isset($address['Last Name'])) {
                    // Outlook
                    $lname = $address['Last Name'];
                } elseif (isset($address['Family Name'])) {
                    // Gmail
                    $lname = $address['Family Name'];
                }
                // Email
                $email = '';
                if (isset($address['email'])) {
                    // FCMS
                    $email = $address['email'];
                } elseif (isset($address['E-mail Address'])) {
                    // Outlook
                    $email = $address['E-mail Address'];
                } elseif (isset($address['E-mail 1 - Value'])) {
                    // Gmail
                    $email = $address['E-mail 1 - Value'];
                }
                // Street Address
                $street = '';
                $city = '';
                $state = '';
                $zip = '';
                if (isset($address['address'])) {
                    // FCMS
                    $street = $address['address'];
                } elseif (isset($address['Home Address'])) {
                    // Outlook (all in one)
                    // Try to parse the data into individual fields
                    // This only works for US formatted addressess
                    $endStreet = strpos($address['Home Address'], "\n");
                    if ($endStreet !== false) {
                        $street = substr($address['Home Address'], 0, $endStreet-1);
                        $endCity = strpos($address['Home Address'], ",", $endStreet);
                        if ($endCity !== false) {
                            $city = substr($address['Home Address'], $endStreet+1, ($endCity - $endStreet)-1);
                            $tmpZip = substr($address['Home Address'], -5);
                            if (is_numeric($tmpZip)) {
                                $endZip = strpos($address['Home Address'], $tmpZip, $endCity);
                                if ($endZip !== false) {
                                    $state = substr($address['Home Address'], $endCity+2);
                                    $state = substr($state, 0, -6);  // 5 zip + space
                                    $zip = $tmpZip;
                                }
                            } else {
                                $state = substr($address['Home Address'], $endCity);
                            }
                        }
                    // Can't figure out which part is which
                    } else {
                        $street = $address['Home Address'];
                    }
                } elseif (isset($address['Home Street'])) {
                    // Outlook
                    $street = $address['Home Street'];
                } elseif (isset($address['Address 1 - Formatted'])) {
                    // Gmail (all in one)
                    // Try to parse the data into individual fields
                    // This only works for US formatted addressess
                    $endStreet = strpos($address['Address 1 - Formatted'], "\n");
                    if ($endStreet !== false) {
                        $street = substr($address['Address 1 - Formatted'], 0, $endStreet-1);
                        $endCity = strpos($address['Address 1 - Formatted'], ",", $endStreet);
                        if ($endCity !== false) {
                            $city = substr($address['Address 1 - Formatted'], $endStreet+1, ($endCity - $endStreet)-1);
                            $tmpZip = substr($address['Address 1 - Formatted'], -5);
                            if (is_numeric($tmpZip)) {
                                $endZip = strpos($address['Address 1 - Formatted'], $tmpZip, $endCity);
                                if ($endZip !== false) {
                                    $state = substr($address['Address 1 - Formatted'], $endCity+2);
                                    $state = substr($state, 0, -6);  // 5 zip + space
                                    $zip = $tmpZip;
                                }
                            } else {
                                $state = substr($address['Address 1 - Formatted'], $endCity);
                            }
                        }
                    // Can't figure out which part is which
                    } else {
                        $street = $address['Home Address'];
                    }
                } elseif (isset($address['Address 1 - Street'])) {
                    // Gmail
                    $street = $address['Address 1 - Street'];
                }
                // City
                if (isset($address['city'])) {
                    // FCMS
                    $city = $address['city'];
                } elseif (isset($address['Home City'])) {
                    // Outlook
                    $city = $address['Home City'];
                } elseif (isset($address['Address 1 - City'])) {
                    // Gmail
                    $city = $address['Address 1 - City'];
                }
                // State
                if (isset($address['state'])) {
                    // FCMS
                    $state = $address['state'];
                } elseif (isset($address['Home State'])) {
                    // Outlook
                    $state = $address['Home State'];
                } elseif (isset($address['Address 1 - Region'])) {
                    // Gmail
                    $state = $address['Address 1 - Region'];
                }
                // Zip
                if (isset($address['zip'])) {
                    // FCMS
                    $zip = $address['zip'];
                } elseif (isset($address['Home Postal Code'])) {
                    // Outlook
                    $zip = $address['Home Postal Code'];
                } elseif (isset($address['Address 1 - Postal Code'])) {
                    // Gmail
                    $zip = $address['Address 1 - Postal Code'];
                }
                // Phone Numbers
                $home = '';
                $work = '';
                $cell = '';
                // FCMS
                if (isset($address['home'])) {
                    $home = $address['home'];
                }
                if (isset($address['work'])) {
                    $work = $address['work'];
                }
                if (isset($address['cell'])) {
                    $cell = $address['cell'];
                }
                // Outlook
                if (isset($address['Home Phone'])) {
                    $home = $address['Home Phone'];
                }
                if (isset($address['Business Phone'])) {
                    $work = $address['Business Phone'];
                }
                if (isset($address['Mobile Phone'])) {
                    $cell = $address['Mobile Phone'];
                }
                // Gmail
                if (isset($address['Phone 1 - Type'])) {
                    switch ($address['Phone 1 - Type']) {
                        case 'Home':
                            $home = $address['Phone 1 - Type'];
                            break;
                        case 'Work':
                            $work = $address['Phone 1 - Type'];
                            break;
                        case 'Mobile':
                            $cell = $address['Phone 1 - Type'];
                            break;
                    }
                }
                if (isset($address['Phone 2 - Type'])) {
                    switch ($address['Phone 2 - Type']) {
                        case 'Home':
                            $home = $address['Phone 2 - Type'];
                            break;
                        case 'Work':
                            $work = $address['Phone 2 - Type'];
                            break;
                        case 'Mobile':
                            $cell = $address['Phone 2 - Type'];
                            break;
                    }
                }
                if (isset($address['Phone 3 - Type'])) {
                    switch ($address['Phone 3 - Type']) {
                        case 'Home':
                            $home = $address['Phone 3 - Type'];
                            break;
                        case 'Work':
                            $work = $address['Phone 3 - Type'];
                            break;
                        case 'Mobile':
                            $cell = $address['Phone 3 - Type'];
                            break;
                    }
                }

                // Create non-member
                $uniq = uniqid("");
                $pw = 'NONMEMBER';
                if (isset($_POST['private'])) {
                    $pw = 'PRIVATE';
                }
                $sql = "INSERT INTO `fcms_users` ("
                        . "`access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`"
                     . ") VALUES ("
                        . "3, "
                        . "NOW(), "
                        . "'" . addslashes($fname) . "', "
                        . "'" . addslashes($lname) . "', "
                        . "'" . addslashes($email) . "', "
                        . "'NONMEMBER-$uniq', "
                        . "'$pw')";
                mysql_query($sql) or displaySQLError(
                    'Add Non-Member Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                $id = mysql_insert_id();
                // Create address for non-member
                $sql = "INSERT INTO `fcms_address`("
                        . "`user`, `entered_by`, `updated`, `address`, `city`, `state`, "
                        . "`zip`, `home`, `work`, `cell`"
                     . ") VALUES ("
                        . "$id, "
                        . $_SESSION['login_id'] . ", "
                        . "NOW(), "
                        . "'" . addslashes($street) . "', "
                        . "'" . addslashes($city) . "', "
                        . "'" . addslashes($state) . "', "
                        . "'" . addslashes($zip) . "', "
                        . "'" . addslashes($home) . "', "
                        . "'" . addslashes($work) . "', "
                        . "'" . addslashes($cell) . "')";
                mysql_query($sql) or displaySQLError(
                    'New Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                $i++;
            }
            echo '
            <p class="ok-alert">
                '.sprintf(_ngettext('%d Address Added Successfully', '%d Addresses Added Successfully', $i), $i).'
            </p>';
        }
    }

}
?>
