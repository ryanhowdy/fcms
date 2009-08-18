<?php
include_once('util_inc.php');
include_once('language.php');

class Members
{

	var $db;

	function Members ($database)
    {
		$this->db = $database;
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
        $this->displayForm('create', $error);
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
        global $LANG;
        
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
            $title      =   $LANG['create_member'];
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
            $title    = $LANG['edit_member'];
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
            echo "<p class=\"error\">$error</p>\n";
        }
        
        // Display the form
        echo <<<HTML
            <fieldset>
                <legend>{$title}</legend>
                <form method="post" action="members.php">
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="username"><b>{$LANG['username']}</b></label></div> 
                        <div class="field-widget">
                            <input type="text" name="username" id="username" {$disabled} class="required" title="{$LANG['title_uname']}" value="{$username}" size="25"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var funame = new LiveValidation('username', { validMessage: "", wait: 500});
                        funame.add(Validate.Presence, {failureMessage: "{$LANG['lv_sorry_req']}"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="password"><b>{$LANG['password']}</b></label></div>
                        <div class="field-widget">
                            <input type="password" name="password" id="password" class="required" title="{$LANG['title_pass']}" size="25"/>
                        </div>
                    </div>
HTML;
        // Password field is only required when creating not editing
        if ($type == 'create') {
            echo <<<HTML
                    <script type="text/javascript">
                        var fpass = new LiveValidation('password', { validMessage: "", wait: 500});
                        fpass.add(Validate.Presence, {failureMessage: "{$LANG['lv_sorry_req']}"});
                    </script>
HTML;
        }
            echo <<<HTML
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="fname"><b>{$LANG['first_name']}</b></label></div> 
                        <div class="field-widget">
                            <input type="text" name="fname" id="fname" class="required" title="{$LANG['title_fname']}" value="{$fname}" size="50"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation('fname', { validMessage: "", wait: 500});
                        ffname.add(Validate.Presence, {failureMessage: "{$LANG['lv_sorry_req']}"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="lname"><b>{$LANG['last_name']}</b></label></div> 
                        <div class="field-widget">
                            <input type="text" name="lname" id="lname" class="required" title="{$LANG['title_lname']}" value="{$lname}" size="50"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var flname = new LiveValidation('lname', { validMessage: "", wait: 500});
                        flname.add(Validate.Presence, {failureMessage: "{$LANG['lv_sorry_req']}"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="email"><b>{$LANG['email_address']}</b></label></div> 
                        <div class="field-widget">
                            <input type="text" name="email" id="email" class="required validate-email" title="{$LANG['title_email']}" value="{$email}" size="50"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var femail = new LiveValidation('email', { validMessage: "", wait: 500 });
                        femail.add( Validate.Presence, { failureMessage: "{$LANG['lv_sorry_req']}" } );
                        femail.add( Validate.Email, { failureMessage: "{$LANG['lv_bad_email']}" } );
                        femail.add( Validate.Length, { minimum: 10 } );
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="day"><b>{$LANG['birthday']}</b></label></div> 
                        <div class="field-widget">
                            <select id="day" name="day">
HTML;
                    $d = 1;
                    while ($d <= 31) {
                        echo "<option value=\"$d\"";
                        if ($day == $d) {
                            echo ' selected="selected"';
                        }
                        echo ">$d</option>";
                        $d++;
                    }
                    echo "</select>\n                        <select id=\"month\" name=\"month\">";
                    $m = 1;
                    while ($m <= 12) {
                        echo "<option value=\"$m\"";
                        if ($month == $m) {
                            echo ' selected="selected"';
                        }
                        echo ">" . $LANG[date('M', mktime(0, 0, 0, $m, 1, 2006))] . "</option>";
                        $m++;
                    }
                    echo "</select>\n                        <select id=\"year\" name=\"year\">";
                    $y = 1900;
                    while ($y - 5 <= date('Y')) {
                        echo "<option value=\"$y\"";
                        if ($year == $y) {
                            echo ' selected="selected"';
                        }
                        echo ">$y</option>";
                        $y++;
                    }
                    echo "</select>\n";
                    echo <<<HTML
                        </div>
                    </div>
HTML;
        
        // Display submit buttons
        echo "\n";
        if ($type == 'create') {
            echo <<<HTML
                    <p>
                        <input class="primary" type="submit" id="create" name="create" value="{$LANG['submit']}"/> or 
                        <a href="members.php">{$LANG['cancel']}</a>
                    </p>
                </form>
            </fieldset>
HTML;
        } elseif ($type == 'edit') {
            echo <<<HTML
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="access"><b>{$LANG['access_level']}</b></label></div> 
                        <div class="field-widget">
                            <select id="access" name="access">
HTML;
            echo "<option value=\"1\"";
            if ($access == 1) {
                echo " selected=\"selected\"";
            }
            echo ">1. " . $LANG['access_admin'] . "</option><option value=\"2\"";
            if ($access == 2) {
                echo " selected=\"selected\"";
            }
            echo ">2. " . $LANG['access_helper'] . "</option><option value=\"3\"";
            if ($access == 3) {
                echo " selected=\"selected\"";
            }
            echo ">3. " . $LANG['access_member'] . "</option>";
            echo "<option value=\"" . $access . "\"></option>";
            echo "<option value=\"" . $access . "\">" . $LANG['advanced_options']
                . "</option>";
            echo "<option value=\"" . $access . "\">"
                . "-------------------------------------</option>";
            echo "<option value=\"4\"";
            if ($access == 4) {
                echo " selected=\"selected\"";
            }
            echo ">4. " . $LANG['access_non_photo'] . "</option><option value=\"5\"";
            if ($access == 5) {
                echo " selected=\"selected\"";
            }
            echo ">5. " . $LANG['access_non_poster'] . "</option><option value=\"6\"";
            if ($access == 6) {
                echo " selected=\"selected\"";
            }
            echo ">6. " . $LANG['access_commenter'] . "</option><option value=\"7\"";
            if ($access == 7) {
                echo " selected=\"selected\"";
            }
            echo ">7. " . $LANG['access_poster'] . "</option><option value=\"8\"";
            if ($access == 8) {
                echo " selected=\"selected\"";
            }
            echo ">8. " . $LANG['access_photo'] . "</option><option value=\"9\"";
            if ($access == 9) {
                echo " selected=\"selected\"";
            }
            echo ">9. " . $LANG['access_blogger'] . "</option><option value=\"10\"";
            if ($access == 10) {
                echo " selected=\"selected\"";
            }
            echo ">10. " . $LANG['access_guest'] . "</option></select>";
            echo "\n";
            echo <<<HTML
                        </div>
                    </div>
                    <p>
                        <input type="hidden" id="id" name="id" value="{$id}"/>
                        <input class="primary" type="submit" id="edit" name="edit" value="{$LANG['edit']}"/>&nbsp;&nbsp;
                        <input class="secondary" type="submit" id="delete" name="delete" value="{$LANG['delete']}"/> or 
                        <a class="u" href="members.php">{$LANG['cancel']}</a>
                    </p>
                </form>
            </fieldset>
HTML;
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
        global $LANG;
        $valid_search = 0;
        $from = (($page * 15) - 15);
        
        // Display the add link, search box and table header
        echo <<<HTML
            <div id="sections_menu" class="clearfix">
                <ul><li><a class="add" href="?create=member">{$LANG['create_member']}</a></li></ul>
            </div>
            <hr/>
            <form method="post" action="members.php" name="search_frm" id="search_frm">
                <div>
                    <b>{$LANG['search']}</b>&nbsp;&nbsp; 
                    <label for="fname">{$LANG['first_name']}</label> 
                    <input type="text" name="fname" id="fname" value="{$fname}"/>&nbsp;&nbsp; 
                    <label for="lname">{$LANG['last_name']}</label> 
                    <input type="text" name="lname" id="lname" value="{$lname}"/>&nbsp;&nbsp; 
                    <label for="uname">{$LANG['username']}</label> 
                    <input type="text" name="uname" id="uname" value="{$uname}"/>&nbsp;&nbsp; 
                    <input type="submit" id="search" name="search" value="{$LANG['search']}"/>
                </div>
                <hr/>
            </form>
            <p>&nbsp;</p>
            <form method="post" action="members.php">
                <table class="sortable">
                    <thead>
                        <tr>
                            <th>{$LANG['id']}</th>
                            <th>{$LANG['username']}</th>
                            <th>{$LANG['lname']}</th>
                            <th>{$LANG['fname']}</th>
                            <th class="nosort"><a class="help u" title="{$LANG['title_access_help']}" href="../help.php#adm-access">{$LANG['access_level']}</a></th>
                            <th class="nosort">{$LANG['activated']}</th>
                            <th class="nosort">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
HTML;
        
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
            $sql = "SELECT * FROM fcms_users "
                 . "WHERE password != 'NONMEMBER' ";
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
                echo "\n";
                echo <<<HTML
                        <tr>
                            <td><b>{$r['id']}</b>:</td>
                            <td><a href="?edit={$r['id']}">{$r['username']}</a></td>
                            <td>{$r['lname']}</td>
                            <td>{$r['fname']}</td>
HTML;
                echo "<td>";
                echo $this->displayAccessType($r['access']);
                echo "</td>\n                            ";
                echo "<td style=\"text-align:center\">";
                if ($r['activated'] > 0) {
                    echo $LANG['yes'];
                } else {
                    echo $LANG['no'];
                }
                echo "</td>\n                            ";
                echo '<td style="text-align:center"><input type="checkbox" ';
                echo 'name="massupdate[]" value="' . $r['id'] . '"/></td>';
                echo "\n                        ";
                echo "</tr>";
            } else {
                echo "\n";
                echo <<<HTML
                        <tr>
                            <td><b>{$r['id']}</b>:</td>
                            <td><b>{$r['username']}</b></td>
                            <td>{$r['lname']}</td>
                            <td>{$r['fname']}</td>
                            <td>1. {$LANG['access_admin']}</td>
                            <td style="text-align:center">{$LANG['yes']}</td>
                            <td>&nbsp;</td>
                        </tr>
HTML;
            }
        }
        echo "\n";
        echo <<<HTML
                    </tbody>
                </table>
                <p style="text-align:right">
                    <input type="submit" name="activateAll" id="activateAll" value="{$LANG['activate_selected']}"/>&nbsp; 
                    <input type="submit" name="inactivateAll" id="inactivateAll" value="{$LANG['inactivate_selected']}"/>&nbsp; 
                    <input type="submit" name="deleteAll" id="deleteAll" value="{$LANG['del_selected']}" onclick="javascript:return confirm('{$LANG['js_sure_del_members']}');"/>
                </p>
            </form>
HTML;

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
        global $LANG;
        switch ($access_level) {
            case 1:
                echo "1. " . $LANG['access_admin'];
                break;
            case 2:
                echo "2. " . $LANG['access_helper'];
                break;
            case 3:
                echo "3. " . $LANG['access_member'];
                break;
            case 4:
                echo "4. " . $LANG['access_non_photo'];
                break;
            case 5:
                echo "5. " . $LANG['access_non_poster'];
                break;
            case 6:
                echo "6. " . $LANG['access_commenter'];
                break;
            case 7:
                echo "7. " . $LANG['access_poster'];
                break;
            case 8:
                echo "8. " . $LANG['access_photo'];
                break;
            case 9:
                echo "9. " . $LANG['access_blogger'];
                break;
            case 10:
                echo "10. " . $LANG['access_guest'];
                break;
        }
    }
}
