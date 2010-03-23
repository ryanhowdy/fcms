<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

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

	function displayToolbar ()
    {
		global $LANG;
		if (checkAccess($_SESSION['login_id']) <= 5) {
            // TODO
            // Remove the inline js
			echo <<<HTML
            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a class="add_address" href="?add=yes">{$LANG['add_address']}</a></li>
                    <li><a class="edit_address" href="?address={$_SESSION['login_id']}">{$LANG['my_address']}</a></li>
                    <li><a class="import" href="?csv=import">{$LANG['import']}</a></li>
                    <li><a class="export" href="?csv=export" onclick="javascript:return confirm('{$LANG['js_sure_export']}');">{$LANG['export']}</a></li>
                </ul>
            </div>

HTML;
		}
		echo '<div id="addresstoolbar" class="clearfix"><ul>';
		$sql = "SELECT `lname` 
                FROM `fcms_users` AS u, `fcms_address` as a 
                WHERE u.`id` = a.`user` 
                ORDER BY `lname`";
		$this->db->query($sql) or displaySQLError(
            'Address Letter Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
		$prev_letter = -1;
        $letters = array();
		while($r = $this->db->get_row()) {
			$letter = strtoupper(substr($r['lname'], 0, 1));
			if ($letter != $prev_letter) { $letters[] = $letter;  }
			$prev_letter = $letter;
		}
        foreach (range('A', 'Z') as $letter) {
            if (in_array($letter, $letters)) {
                echo '<li><a href="?letter=' . $letter . '">' . $letter . '</a></li>';
            } else {
                echo '<li><span>' . $letter . '</span></li>';
            }
        }
		echo "</ul></div>\n";
		echo '<p><a class="u" href="addressbook.php">' . $LANG['show_all'] . '</a></p>';
	}

	function displayAddress ($aid)
    {
		global $LANG;
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
                            <input type="submit" id="edit" name="edit" class="editbtn" value="'.$LANG['edit'].'"/>';
                if ($r['password'] == 'NONMEMBER') {
                    $edit_del .='
                            <input type="submit" id="del" name="del" class="delbtn" value="'.$LANG['delete'].'"/>';
                }
                $edit_del .= '
                        </div>
                    </form>
                </div>';
            }
            // Address
            $address = '';
            if (empty($r['address']) && empty($r['state'])) {
                $address = "<i>(".$LANG['none'].")</i>";
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
                $email = "<i>(" . $LANG['none'] . ")</i>";
            } else {
                // TODO
                // Add 'Email This Member' to language file
                $email = $r['email'] . ' <a class="email" href="mailto:' . $r['email'] . '" title="Email This Member">&nbsp;</a>';
            }
            // Phone Numbers
            $home = empty($r['home']) ? "<i>(" . $LANG['none'] . ")</i>" : $r['home'];
            $work = empty($r['work']) ? "<i>(" . $LANG['none'] . ")</i>" : $r['work'];
            $cell = empty($r['cell']) ? "<i>(" . $LANG['none'] . ")</i>" : $r['cell'];
            
            // Display address
            echo <<<HTML

            <div id="address">
                {$edit_del}
                <p><img src="gallery/avatar/{$r['avatar']}"/><b>{$r['lname']}, {$r['fname']}</b></p>
                <p class="clearfix"><b class="label">{$LANG['address']}</b><span class="data">{$address}</span></p>
                <p class="clearfix"><b class="label">{$LANG['address_email']}</b><span class="data">{$email}</span></p>
                <p class="clearfix"><b class="label">{$LANG['address_home']}</b><span class="data">{$home}</span></p>
                <p class="clearfix"><b class="label">{$LANG['address_work']}</b><span class="data">{$work}</span></p>
                <p class="clearfix"><b class="label">{$LANG['address_mobile']}</b><span class="data">{$cell}</span></p>
            </div>

HTML;
		} else {
            echo '<p class="error-alert">Could not find address ($aid).</p>';
        }
	}

    function displayAddressList ($letter = '', $page = 1)
    {
        global $LANG;
		$from = (($page * 25) - 25);
        if ($letter !== '') {
            $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email` 
                    FROM `fcms_users` AS u, `fcms_address` as a WHERE u.`id` = a.`user` 
                    AND `lname` LIKE '" . escape_string($_GET['letter']) . "%' 
                    ORDER BY `lname`";
        } else {
            $sql = "SELECT a.`id`, `user`, `fname`, `lname`, `updated`, `home`, `email` 
                    FROM `fcms_users` AS u, `fcms_address` as a 
                    WHERE u.`id` = a.`user` 
                    ORDER BY `lname`
                    LIMIT $from, 25";
        }
        echo '
            <form action="addressbook.php" id="mass_mail_form" name="mass_mail_form" method="post">
                <table class="sortable">
                    <thead>
                        <tr>
                            <th class="sortfirstasc">'.$LANG['name'].'</th>
                            <th>'.$LANG['phone_num'].'</th>
                            <th>'.$LANG['email'].'</th>
                            <th class="nosort"><a class="helpimg" href="help.php#address-massemail"></a></th>
                        </tr>
                    </thead>
                    <tbody>';
        $result = mysql_query($sql) or displaySQLError(
            'Get Addresses Error', 'addressbook.php [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = mysql_fetch_array($result)) {
            $email = '';
            if (!empty($r['email'])) {
                $email = '<input type="checkbox" name="massemail[]" value="'.htmlentities($r['email'], ENT_COMPAT, 'UTF-8').'"/>';
            }
            echo '
                        <tr>
                            <td><a href="?address='.$r['id'].'">'.$r['lname'].', '.$r['fname'].'</a></td>
                            <td>'.$r['home'].'</td>
                            <td><a href="mailto:'.htmlentities($r['email'], ENT_COMPAT, 'UTF-8').'">'.$r['email'].'</a></td>
                            <td>'.$email.'</td>
                        </tr>';
        }
        $dis = '';
        if (checkAccess($_SESSION['login_id']) > 3) {
            $dis = 'disabled="disabled"';
        }
        echo '
                    </tbody>
                </table>
                <div class="alignright"><input '.$dis.' type="submit" name="emailsubmit" value="'.$LANG['email'].'"/></div>
                <p>&nbsp;</p>
            </form>';

        // Display Pages
        if ($letter == '') {
            // Remove the LIMIT from the $sql statement 
            // used above, so we can get the total count
            $sql = substr($sql, 0, strpos($sql, 'LIMIT'));
            $result = mysql_query($sql) or displaySQLError(
                'Page Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
        $count = mysql_num_rows($result);
        $total_pages = ceil($count / 25); 
        displayPages("addressbook.php", $page, $total_pages);
    }

	function displayForm ($type, $addressid = '0')
    {
		global $LANG;
		if($type == 'edit') {
			$sql = "SELECT a.`id`, u.`id` AS uid, `fname`, `lname`, `email`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell` "
                 . "FROM `fcms_users` AS u, `fcms_address` AS a "
                 . "WHERE a.`id` = $addressid "
                 . "AND a.`user` = u.`id`";
			$this->db->query($sql) or displaySQLError('Get Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
			$row=$this->db->get_row();
		}

        // Setup vars for output
		if($type == 'edit') {
            $note = '';
			$legend = $LANG['edit_address'] . " (" . stripslashes($row['fname']) . " " . stripslashes($row['lname']) . ")";
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
            $note = '<p class="info-alert">' . $LANG['info_add_address'] . '</p>';
            $legend = $LANG['add_address'];
			$add = <<<HTML
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="fname"><b>{$LANG['first_name']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="fname" id="fname" title="{$LANG['title_fname']}" size="25"/></div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation('fname', { onlyOnSubmit: true });
                        ffname.add(Validate.Presence, {failureMessage: ""});
                    </script>
			        <div class="field-row clearfix">
                        <div class="field-label"><label for="lname"><b>{$LANG['last_name']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="lname" id="lname" title="{$LANG['title_lname']}" size="25"/></div>
                    </div>
                    <script type="text/javascript">
                        var flname = new LiveValidation('lname', { onlyOnSubmit: true });
                        flname.add(Validate.Presence, {failureMessage: ""});
                    </script>

HTML;
            $email = $address = $city = $state = $zip = $home = $work = $cell = '';
		}

        // Print the form
		echo <<<HTML
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <form id="addressbook_form" action="addressbook.php" method="post">
                {$note}
                <fieldset>
                    <legend>{$legend}</legend>
{$add}
		            <div class="field-row clearfix">
                        <div class="field-label"><label for="email"><b>{$LANG['email']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="email" id="email" title="{$LANG['title_email']}" size="50" value="{$email}"/></div>
                    </div>
		            <script type="text/javascript">
                        var femail = new LiveValidation('email', { onlyOnSubmit: true });
                        femail.add( Validate.Email, { failureMessage: "{$LANG['lv_bad_email']}"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="address"><b>{$LANG['street']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="address" id="address" title="{$LANG['title_street']}" size="25" value="{$address}"/></div>
                    </div>
		            <div class="field-row clearfix">
                        <div class="field-label"><label for="city"><b>{$LANG['city_town']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="city" id="city" title="{$LANG['title_city_town']}" size="50" value="{$city}"/></div>
		            </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="state"><b>{$LANG['state_prov']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="state" id="state" title="{$LANG['title_state_prov']}" size="50" value="{$state}"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="zip"><b>{$LANG['zip_pos']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="zip" id="zip" title="{$LANG['title_zip_pos']}" size="10" value="{$zip}"/></div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="home"><b>{$LANG['home_phone']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="home" id="home" title="{$LANG['title_phone']}" size="20" value="{$home}"/></div>
                    </div>
                    <script type="text/javascript">
                        var fhome = new LiveValidation('home', { onlyOnSubmit: true });
                        fhome.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="work"><b>{$LANG['work_phone']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="work" id="work" title="{$LANG['title_phone']}" size="20" value="{$work}"/></div>
                    </div>
                    <script type="text/javascript">
                        var fwork = new LiveValidation('work', { onlyOnSubmit: true });
                        fwork.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="cell"><b>{$LANG['mobile_phone']}</b></label></div>
                        <div class="field-widget"><input class="frm_text" type="text" name="cell" id="cell" title="{$LANG['title_phone']}" size="20" value="{$cell}"/></div>
                    </div>
                    <script type="text/javascript">
                        var fcell = new LiveValidation('cell', { onlyOnSubmit: true });
                        fcell.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
                    </script>
                </fieldset>
HTML;
		if($type == 'edit') {
			echo '
                <div>
                    <input type="hidden" name="aid" value="'.$addressid.'"/>
                    <input type="hidden" name="uid" value="'.$row['uid'].'"/>
                </div>
                <p>
                    <input type="submit" name="editsubmit" value="'.$LANG['edit_address'].'"/> 
                    '.$LANG['or'].' 
                    <a href="addressbook.php?address='.$addressid.'">'.$LANG['cancel'].'</a>
                </p>
            </form>';
		} else {
			echo '
                <p>
                    <input type="submit" name="addsubmit" value="'.$LANG['add_address'].'"/> 
                    '.$LANG['or'].' 
                    <a href="addressbook.php">'.$LANG['cancel'].'</a>
                </p>
            </form>';
		}
	}

	function displayMassEmailForm ($emails, $email = '', $name = '', $subject = '', $message = '', $show = '') {
		global $LANG;
		echo "<p class=\"info-alert\">".$LANG['info_massemail']."</p>\n\t\t\t";
		echo "<form method=\"post\" class=\"contactform\" action=\"addressbook.php\">\n\t\t\t\t";
		echo "<p><label for=\"email\">".$LANG['your_email'].":";
		if (!empty($show) && empty($email)) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
		echo "</label><input class=\"frm_text\" type=\"text\" id=\"email\" name=\"email\" size=\"30\"/></p>\n\t\t\t\t";
		echo "<p><label for=\"name\">".$LANG['your_name'].":";
		if (!empty($show) && empty($name)) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
		echo "</label><input class=\"frm_text\" type=\"text\" id=\"name\" name=\"name\" size=\"30\"/></p>\n\t\t\t\t";
		echo "<p><label for=\"subject\">".$LANG['subject'].":";
		if (!empty($show) && empty($subject)) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
		echo "</label><input class=\"frm_text\" type=\"text\" id=\"subject\" name=\"subject\" size=\"30\"/></p>\n\t\t\t\t";
		echo "<p><label for=\"subject\">".$LANG['message'].":";
		if (!empty($show) && empty($msg)) { echo " <span class=\"error\">" . $LANG['required'] . "</span>"; }
		echo "</label><textarea name=\"msg\" rows=\"10\" cols=\"40\"></textarea></p>\n\t\t\t\t";
		foreach ($emails as $email) {
			echo "<input type=\"hidden\" name=\"emailaddress[]\" value=\"$email\"/>\n\t\t\t";
		}
		echo "<p><input type=\"submit\" name=\"sendemailsubmit\" value=\"".$LANG['send_mass_email']."\"/>";
		echo " " . $LANG['or'] . " <a href=\"addressbook.php\">" . $LANG['cancel'] . "</a></p>\n\t\t\t</form><p>&nbsp;</p><p>&nbsp;</p>";
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
		global $LANG;
		$today = date('Y-m-d');
		$tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		echo "\t\t\t\t<h3>".$LANG['link_address']."</h3>\n\t\t\t\t<ul>\n";
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
				$monthName = gmdate('M', strtotime($row['updated'] . $this->tz_offset));
				$date = gmdate('. j, Y, g:i a', strtotime($row['updated'] . $this->tz_offset));
				if (
                    strtotime($row['updated']) >= strtotime($today) && 
                    strtotime($row['updated']) > $tomorrow
                ) {
                    $full_date = $LANG['today'];
                    $d = ' class="today"';
                } else {
                    $full_date = getLangMonthName($monthName) . $date;
                    $d = '';
                }
                echo "\t\t\t\t\t<li><div$d>$full_date</div>";
				echo "<a href=\"addressbook.php?address=" . $row['id'] . "\">$displayname</a>";
                echo "</li>\n";			
			}
		} else {
			echo "\t\t\t\t\t<li><i>".$LANG['nothing_new_30']."</i></li>\n";
		}
		echo "\t\t\t\t</ul>\n";
	}

    /*
     *  displayImportForm
     *
     *  Displays the form to allow csv imports.
     */
    function displayImportForm ()
    {
        global $LANG;
        echo '
            <h2>'.$LANG['import'].'</h2><br/>
            <form method="post" name="csv-form" action="addressbook.php?csv=import" enctype="multipart/form-data" >
                <div><input type="file" name="csv" id="csv" /></div>
                <p>
                    <input type="submit" id="import" name="import" value="'.$LANG['import'].'"/> or 
                    <a href="addressbook.php">'.$LANG['cancel'].'</a>
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
        global $LANG;
        if (!in_array($file['type'], array('text/plain', 'text/x-csv'))) {
            echo '
            <p class="error-alert">'.$file['name'].' ('.$file['type'].') is not a CSV file.</p>';
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
                if (isset($address['First Name'])) {
                    // Outlook
                    $fname = $address['First Name'];
                } elseif (isset($address['Given Name'])) {
                    // Gmail
                    $fname = $address['Given Name'];
                }
                // Last Name
                $lname = '';
                if (isset($address['Last Name'])) {
                    // Outlook
                    $lname = $address['Last Name'];
                } elseif (isset($address['Family Name'])) {
                    // Gmail
                    $lname = $address['Family Name'];
                }
                // Email
                $email = '';
                if (isset($address['E-mail Address'])) {
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
                if (isset($address['Home Address'])) {
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
                if (isset($address['Home City'])) {
                    // Outlook
                    $city = $address['Home City'];
                } elseif (isset($address['Address 1 - City'])) {
                    // Gmail
                    $city = $address['Address 1 - City'];
                }
                // State
                if (isset($address['Home State'])) {
                    // Outlook
                    $state = $address['Home State'];
                } elseif (isset($address['Address 1 - Region'])) {
                    // Gmail
                    $state = $address['Address 1 - Region'];
                }
                // Zip
                if (isset($address['Home Postal Code'])) {
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
                $sql = "INSERT INTO `fcms_users` ("
                        . "`access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`"
                     . ") VALUES ("
                        . "3, "
                        . "NOW(), "
                        . "'" . addslashes($fname) . "', "
                        . "'" . addslashes($lname) . "', "
                        . "'" . addslashes($email) . "', "
                        . "'NONMEMBER-$uniq', "
                        . "'NONMEMBER')";
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
            echo '<p class="ok-alert">('.$i.') addresses successfully added.</p>';
        }
    }

}
?>
