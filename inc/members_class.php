<?php
include_once('util_inc.php');
include_once('locale.php');

class Members
{

    var $db;

    function Members ($database)
    {
        $this->db = $database;
        bindtextdomain('messages', '.././language');
    }
    
    function getUsersEmail ($id)
    {
        $sql = "SELECT `email` FROM `fcms_users` WHERE `id` = $id";
        $this->db->query($sql) or displaySQLError(
            'Email Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        $r = $this->db->get_row();
        return $r['email'];
    }
    
    function displayCreateMemberForm ($error = '')
    {
        $this->displayForm('create', 0, $error);
    }
    function displayEditMemberForm ($id, $error = '')
    {
        $this->displayForm('edit', $id, $error);
    }

    /**
     * displayForm
     *
     * Displays the form for editing or creating new members.
     *
     * @param   $type       create || edit
     * @param   $member     the id for the member to be edited
     * @param   $errors     used for invalid email or required fields
     *
     */
    function displayForm ($type, $member = 0, $error = '')
    {
        $locale = new Locale();

        // Only get info from db if a valid id is used
        if ($member > 0) {
            $sql = "SELECT `id`, `username`, `fname`, `lname`, `email`, `birthday`, `access` "
                 . "FROM `fcms_users` "
                 . "WHERE `id` = $member ";
            $this->db->query($sql) or displaySQLError(
                'Member Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
            $r = $this->db->get_row();
        }
        
        // Setup create member variables
        if ($type == 'create') {
            $title      =   T_('Create New Member');
            $disabled   =   '';
            $username   =   isset($_POST['username'])   ?   $_POST['username']  :   '';
            $fname      =   isset($_POST['fname'])      ?   $_POST['fname']     :   '';
            $lname      =   isset($_POST['lname'])      ?   $_POST['lname']     :   '';
            $email      =   isset($_POST['email'])      ?   $_POST['email']     :   '';
            $year       =   isset($_POST['year'])       ?   $_POST['year']      :   date('Y');
            $month      =   isset($_POST['month'])      ?   $_POST['month']     :   date('m');
            $day        =   isset($_POST['day'])        ?   $_POST['day']       :   date('d');
        
        // Setup edit member variables
        } elseif ($type == 'edit') {
            $title    = T_('Edit Member');
            $disabled = 'disabled="disabled"';
            $id       = isset($r['id'])       ?  $r['id']                     :  $_POST['id'];
            $username = isset($r['username']) ?  $r['username']               :  $_POST['username'];
            $fname    = isset($r['fname'])    ?  $r['fname']                  :  $_POST['fname'];
            $lname    = isset($r['lname'])    ?  $r['lname']                  :  $_POST['lname'];
            $email    = isset($r['email'])    ?  $r['email']                  :  $_POST['email'];
            $year     = isset($r['birthday']) ?  substr($r['birthday'], 0, 4) :  $_POST['year'];
            $month    = isset($r['birthday']) ?  substr($r['birthday'], 5, 2) :  $_POST['month'];
            $day      = isset($r['birthday']) ?  substr($r['birthday'], 8, 2) :  $_POST['day'];
            $access   = isset($r['access'])   ?  $r['access']                 :  $_POST['access'];
        }
        
        // Display applicable errors
        if ($error != '') {
            echo '
            <p class="error-alert">'.$error.'</p>';
        }
        
        // Display the form
        echo '
            <fieldset>
                <legend><span>'.$title.'</span></legend>
                <form method="post" action="members.php">
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="username"><b>'.T_('Username').'</b></label></div> 
                        <div class="field-widget">
                            <input type="text" name="username" id="username" '.$disabled.' value="'.$username.'" size="25"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var funame = new LiveValidation(\'username\', { onlyOnSubmit: true });
                        funame.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="password"><b>'.T_('Password').'</b></label></div>
                        <div class="field-widget">
                            <input type="password" name="password" id="password" size="25"/>
                        </div>
                    </div>';

        // Password field is only required when creating not editing
        if ($type == 'create') {
            echo '
                    <script type="text/javascript">
                        var fpass = new LiveValidation(\'password\', { onlyOnSubmit: true });
                        fpass.add(Validate.Presence, {failureMessage: ""});
                    </script>';
        }
        echo '
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="fname"><b>'.T_('First Name').'</b></label></div> 
                        <div class="field-widget">
                            <input type="text" name="fname" id="fname" value="'.$fname.'" size="50"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                        ffname.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="lname"><b>'.T_('Last Name').'</b></label></div> 
                        <div class="field-widget">
                            <input type="text" name="lname" id="lname" value="'.$lname.'" size="50"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                        flname.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="email"><b>'.T_('Email').'</b></label></div> 
                        <div class="field-widget">
                            <input type="text" name="email" id="email" value="'.$email.'" size="50"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                        femail.add( Validate.Presence, { failureMessage: "'.T_('Sorry, but this information is Required.').'" } );
                        femail.add( Validate.Email, { failureMessage: "'.T_('That\'s not a valid email address is it?').'" } );
                        femail.add( Validate.Length, { minimum: 10 } );
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="day"><b>'.T_('Birthday').'</b></label></div> 
                        <div class="field-widget">
                            <select id="day" name="day">';
        $d = 1;
        while ($d <= 31) {
            echo "<option value=\"$d\"";
            if ($day == $d) {
                echo ' selected="selected"';
            }
            echo ">$d</option>";
            $d++;
        }
        echo '
                            </select>
                            <select id="month" name="month">';
        $m = 1;
        while ($m <= 12) {
            echo "<option value=\"$m\"";
            if ($month == $m) {
                echo ' selected="selected"';
            }
            echo ">" . $locale->getMonthAbbr($m) . "</option>";
            $m++;
        }
        echo '
                            </select>
                            <select id="year" name="year">';
        $y = 1900;
        while ($y - 5 <= date('Y')) {
            echo "<option value=\"$y\"";
            if ($year == $y) {
                echo ' selected="selected"';
            }
            echo ">$y</option>";
            $y++;
        }
        echo '
                            </select>
                        </div>
                    </div>';
        
        // Display submit buttons
        if ($type == 'create') {
            echo '
                    <p>
                        <input class="sub1" type="submit" id="create" name="create" value="'.T_('Create').'"/> '.T_('or').' &nbsp;
                        <a href="members.php">'.T_('Cancel').'</a>
                    </p>
                </form>
            </fieldset>';
        } elseif ($type == 'edit') {
            echo '
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="access"><b>'.T_('Access Level').'</b></label></div> 
                        <div class="field-widget">
                            <select id="access" name="access">
                                <option value="1"';
            if ($access == 1) {
                echo " selected=\"selected\"";
            }
            echo ">1. " . T_('Admin') . "</option><option value=\"2\"";
            if ($access == 2) {
                echo " selected=\"selected\"";
            }
            echo ">2. " . T_('Helper') . "</option><option value=\"3\"";
            if ($access == 3) {
                echo " selected=\"selected\"";
            }
            echo ">3. " . T_('Member') . "</option>";
            echo "<option value=\"" . $access . "\"></option>";
            echo "<option value=\"" . $access . "\">" . T_('Advanced Options')
                . "</option>";
            echo "<option value=\"" . $access . "\">"
                . "-------------------------------------</option>";
            echo "<option value=\"4\"";
            if ($access == 4) {
                echo " selected=\"selected\"";
            }
            echo ">4. " . T_('Non-Photographer') . "</option><option value=\"5\"";
            if ($access == 5) {
                echo " selected=\"selected\"";
            }
            echo ">5. " . T_('Non-Poster') . "</option><option value=\"6\"";
            if ($access == 6) {
                echo " selected=\"selected\"";
            }
            echo ">6. " . T_('Commenter') . "</option><option value=\"7\"";
            if ($access == 7) {
                echo " selected=\"selected\"";
            }
            echo ">7. " . T_('Poster') . "</option><option value=\"8\"";
            if ($access == 8) {
                echo " selected=\"selected\"";
            }
            echo ">8. " . T_('Photographer') . "</option><option value=\"9\"";
            if ($access == 9) {
                echo " selected=\"selected\"";
            }
            echo ">9. " . T_('Blogger') . "</option><option value=\"10\"";
            if ($access == 10) {
                echo " selected=\"selected\"";
            }
            echo '>10. '.T_('Guest').'</option>
                            </select>
                        </div>
                    </div>
                    <p>
                        <input type="hidden" id="id" name="id" value="'.$id.'"/>
                        <input class="sub1" type="submit" id="edit" name="edit" value="'.T_('Edit').'"/>&nbsp;&nbsp;
                        <input class="sub2" type="submit" id="delete" name="delete" value="'.T_('Delete').'"/> '.T_('or').' &nbsp;
                        <a class="u" href="members.php">'.T_('Cancel').'</a>
                    </p>
                </form>
            </fieldset>';
        }
    }
    
    /**
     * displayMemberList
     *
     * Displays the list of members, by default list all or list based on search results.
     *
     * @param   $page   which page to display
     * @param   $fname  search parameter for first name
     * @param   $lname  search parameter for last name
     * @param   $uname  search parameter for username
     *
     */
    function displayMemberList ($page, $fname = '', $lname = '', $uname = '')
    {
        $valid_search = 0;
        $from = (($page * 15) - 15);
        
        // Display the add link, search box and table header
        echo '
            <div id="actions_menu" class="clearfix">
                <ul><li><a class="add" href="?create=member">'.T_('Create Member').'</a></li></ul>
            </div>
            <hr/>
            <form method="post" action="members.php" name="search_frm" id="search_frm">
                <div>
                    <b>'.T_('Search').'</b>&nbsp;&nbsp; 
                    <label for="fname">'.T_('First Name').'</label> 
                    <input type="text" name="fname" id="fname" value="'.$fname.'"/>&nbsp;&nbsp; 
                    <label for="lname">'.T_('Last Name').'</label> 
                    <input type="text" name="lname" id="lname" value="'.$lname.'"/>&nbsp;&nbsp; 
                    <label for="uname">'.T_('Username').'</label> 
                    <input type="text" name="uname" id="uname" value="'.$uname.'"/>&nbsp;&nbsp; 
                    <input type="submit" id="search" name="search" value="'.T_('Search').'"/>
                </div>
                <hr/>
            </form>
            <p>&nbsp;</p>
            <form method="post" action="members.php">
                <table class="sortable">
                    <thead>
                        <tr>
                            <th>'.T_('ID').'</th>
                            <th>'.T_('Username').'</th>
                            <th>'.T_('Last Name').'</th>
                            <th>'.T_('First Name').'</th>
                            <th class="nosort"><a class="help u" title="'.T_('Get Help using Access Levels').'" href="../help.php#adm-access">'.T_('Access Level').'</a></th>
                            <th class="nosort">'.T_('Active?').'</th>
                            <th class="nosort">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // prevent sql injections - only allow letters, numbers, a space and the % sign
        if (strlen($fname) > 0) {
            if (!preg_match('/^[A-Za-z0-9%\s]+$/', $fname)) {
                $valid_search++;
            }
        }
        if (strlen($lname) > 0) {
            if (!preg_match('/^[A-Za-z0-9%\s]+$/', $lname)) {
                $valid_search++;
            }
        }
        if (strlen($uname) > 0) {
            if (!preg_match('/^[A-Za-z0-9%\s]+$/', $uname)) {
                $valid_search++;
            }
        }
        
        // Search - one or valid search parameters
        if ($valid_search < 1) {
            $sql = "SELECT * FROM `fcms_users` 
                    WHERE `password` != 'NONMEMBER' 
                    AND `password` != 'PRIVATE' ";
            if (strlen($fname) > 0) {
                $sql .= "AND `fname` LIKE '$fname' ";
            }
            if (strlen($lname) > 0) {
                $sql .= "AND `lname` LIKE '$lname' ";
            }
            if (strlen($uname) > 0) {
                $sql .= "AND `username` LIKE '$uname' ";
            }
            $sql .= "ORDER BY `id` LIMIT $from, 15";
        
        // Display All - one of more blank or invalid search parameters
        } else {
            $sql = "SELECT * FROM fcms_users "
                 . "WHERE password != 'NONMEMBER' "
                 . "ORDER BY `id` "
                 . "LIMIT $from, 15";
        }
        $result = mysql_query($sql) or displaySQLError(
            'Member Info Error', 
            __FILE__ . ' [' . __LINE__ . ']', 
            $sql, 
            mysql_error()
            );
        
        // Display the member list
        while($r = mysql_fetch_array($result)) {
            if ($r['id'] > 1) {
                echo '
                        <tr>
                            <td><b>'.$r['id'].'</b>:</td>
                            <td><a href="?edit='.$r['id'].'">'.$r['username'].'</a></td>
                            <td>'.$r['lname'].'</td>
                            <td>'.$r['fname'].'</td>
                            <td>';
                echo $this->displayAccessType($r['access']);
                echo '</td>
                            <td style="text-align:center">';
                if ($r['activated'] > 0) {
                    echo T_('Yes');
                } else {
                    echo T_('No');
                }
                echo '</td>
                            <td style="text-align:center"><input type="checkbox" name="massupdate[]" value="'.$r['id'].'"/></td>
                        </tr>';
            } else {
                echo '
                        <tr>
                            <td><b>'.$r['id'].'</b>:</td>
                            <td><b>'.$r['username'].'</b></td>
                            <td>'.$r['lname'].'</td>
                            <td>'.$r['fname'].'</td>
                            <td>1. '.T_('Admin').'</td>
                            <td style="text-align:center">'.T_('Yes').'</td>
                            <td>&nbsp;</td>
                        </tr>';
            }
        }
        echo '
                    </tbody>
                </table>
                <p style="text-align:right">
                    <input type="submit" name="activateAll" id="activateAll" value="'.T_('Activate Selected').'"/>&nbsp; 
                    <input type="submit" name="inactivateAll" id="inactivateAll" value="'.T_('Inactivate Selected').'"/>&nbsp; 
                    <input type="submit" name="deleteAll" id="deleteAll" value="'.T_('Delete Selected').'"/>
                </p>
            </form>';

        // Remove the LIMIT from the $sql statement 
        // used above, so we can get the total count
        $sql = substr($sql, 0, strpos($sql, 'LIMIT'));
        $this->db->query($sql) or displaySQLError(
            'Page Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $count = $this->db->count_rows();
        $total_pages = ceil($count / 15); 
        displayPages("members.php", $page, $total_pages);
    }
    
    /**
     * displayAccessType
     *
     * Displays the access type based on access level code
     *
     * @param   $access_level   1-10
     *
     */
    function displayAccessType ($access_level) {
        switch ($access_level) {
            case 1:
                echo "1. ".T_('Admin');
                break;
            case 2:
                echo "2. ".T_('Helper');
                break;
            case 3:
                echo "3. ".T_('Member');
                break;
            case 4:
                echo "4. ".T_('Non-Photographer');
                break;
            case 5:
                echo "5. ".T_('Non-Poster');
                break;
            case 6:
                echo "6. ".T_('Commenter');
                break;
            case 7:
                echo "7. ".T_('Poster');
                break;
            case 8:
                echo "8. ".T_('Photographer');
                break;
            case 9:
                echo "9. ".T_('Blogger');
                break;
            case 10:
                echo "10. ".T_('Guest');
                break;
        }
    }
}
