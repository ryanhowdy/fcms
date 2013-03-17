<?php
/**
 * AdminMembers
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
require_once 'utils.php';
require_once 'datetime.php';

/**
 * AdminMembers 
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
class AdminMembers
{
    var $fcmsError;
    var $fcmsDatabase;
    var $fcmsUser;

    /**
     * AdminMembers 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase
     * @param object $fcmsUser 
     * 
     * @return  void
     */
    function AdminMembers ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;

        T_bindtextdomain('messages', '.././language');
    }
    
    /**
     * getUsersEmail 
     * 
     * @param int $id The id of the user
     * 
     * @return void
     */
    function getUsersEmail ($id)
    {
        $sql = "SELECT `email`
                FROM `fcms_users`
                WHERE `id` = ?";

        $r = $this->fcmsDatabase->getRow($sql, $id);
        if ($r === false)
        {
            return '';
        }

        return $r['email'];
    }
    
    /**
     * displayCreateMemberForm 
     * 
     * @param string $error Any errors from previous form
     * 
     * @return void
     */
    function displayCreateMemberForm ($error = '')
    {
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $fname    = isset($_POST['fname'])    ? $_POST['fname']    : '';
        $mname    = isset($_POST['mname'])    ? $_POST['mname']    : '';
        $lname    = isset($_POST['lname'])    ? $_POST['lname']    : '';
        $maiden   = isset($_POST['maiden'])   ? $_POST['maiden']   : '';
        $sex      = isset($_POST['sex'])      ? $_POST['sex']      : '';
        $email    = isset($_POST['email'])    ? $_POST['email']    : '';
        $year     = isset($_POST['year'])     ? $_POST['year']     : date('Y');
        $month    = isset($_POST['month'])    ? $_POST['month']    : date('m');
        $day      = isset($_POST['day'])      ? $_POST['day']      : date('d');

        for ($i = 1; $i <= 31; $i++)
        {
            $days[$i] = $i;
        }
        for ($i = 1; $i <= 12; $i++)
        {
            $months[$i] = getMonthName($i);
        }

        // Display applicable errors
        if ($error != '')
        {
            echo '
            <p class="alert-message error">'.$error.'</p>';
        }
        
        // Display the form
        echo '
            <form method="post" action="members.php">
                <fieldset>
                    <legend>'.T_('Create New Member').'</legend>
                    <div class="clearfix">
                        <label for="username">'.T_('Username').'</label> 
                        <div class="input">
                            <input type="text" name="username" id="username" value="'.cleanOutput($username).'" size="25"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var funame = new LiveValidation(\'username\', { onlyOnSubmit: true });
                        funame.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="clearfix">
                        <label for="password">'.T_('Password').'</label>
                        <div class="input">
                            <input type="password" name="password" id="password" size="25"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var fpass = new LiveValidation(\'password\', { onlyOnSubmit: true });
                        fpass.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="clearfix">
                        <label for="fname">'.T_('Name').'</label> 
                        <div class="input">
                            <input type="text" name="fname" id="fname" value="'.cleanOutput($fname).'" placeholder="'.T_('First').'" title="'.T_('First').'" size="20"/>
                            <input type="text" name="mname" id="mname" value="'.cleanOutput($mname).'" placeholder="'.T_('Middle').'" title="'.T_('Middle').'" size="10"/>
                            <input type="text" name="lname" id="lname" value="'.cleanOutput($lname).'" placeholder="'.T_('Last').'" title="'.T_('Last').'" size="20"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                        ffname.add(Validate.Presence, {failureMessage: ""});
                        var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                        flname.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="clearfix">
                        <label for="maiden">'.T_('Maiden Name').'</label> 
                        <div class="input">
                            <input type="text" name="maiden" id="maiden" value="'.cleanOutput($maiden).'" size="50"/>
                        </div>
                    </div>
                    <div class="clearfix">
                        <label for="sex">'.T_('Gender').'</label> 
                        <div class="input">
                            <select name="sex" id="sex">
                                <option value="M">'.T_('Male').'</option>
                                <option value="F">'.T_('Female').'</option>
                            </select>
                        </div>
                    </div>
                    <div class="clearfix">
                        <label for="email">'.T_('Email').'</label> 
                        <div class="input">
                            <input type="text" name="email" id="email" value="'.cleanOutput($email).'" size="50"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                        femail.add( Validate.Presence, { failureMessage: "'.T_('Sorry, but this information is Required.').'" } );
                        femail.add( Validate.Email, { failureMessage: "'.T_('That\'s not a valid email address is it?').'" } );
                        femail.add( Validate.Length, { minimum: 10 } );
                    </script>
                    <div class="clearfix">
                        <label for="day">'.T_('Birthday').'</label> 
                        <div class="input">
                            <select id="day" name="day" class="span2">
                                '.buildHtmlSelectOptions($days, $day).'
                            </select>
                            <select id="month" name="month" class="span3">
                                '.buildHtmlSelectOptions($months, $month).'
                            </select>
                            <input type="text" name="year" id="year" value="'.cleanOutput($year).'"/>
                        </div>
                    </div>
                    <div class="clearfix">
                        <div class="input">
                            <ul class="inputs-list">
                                <li>
                                    <label>
                                        <input type="checkbox" id="invite" name="invite" value="1"/>
                                        <span>'.T_('Send Invitation Email').'</span>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="actions">
                        <input class="btn primary" type="submit" id="create" name="create" value="'.T_('Create').'"/>
                        <a class="btn" href="members.php">'.T_('Cancel').'</a>
                    </div>
                </fieldset>
            </form>';
    }

    /**
     * displayEditMemberForm 
     * 
     * @param int    $id    The id of the user
     * @param string $error Any errors from previous form
     * 
     * @return  void
     */
    function displayEditMemberForm ($id, $error = '')
    {
        $member = (int)$id;

        $sql = "SELECT `id`, `username`, `fname`, `mname`, `lname`, `maiden`, `sex`, 
                    `email`, `dob_year`, `dob_month`, `dob_day`, `dod_year`, `dod_month`,
                    `dod_day`, `access` 
                FROM `fcms_users` 
                WHERE `id` = ?";

        $r = $this->fcmsDatabase->getRow($sql, $member);
        if ($r === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (empty($r))
        {
            echo '
            <p class="error-alert">'.T_('Could not find member.').'</p>';

            return;
        }
        
        // Display applicable errors
        if ($error != '')
        {
            echo '
            <p class="error-alert">'.$error.'</p>';
        }

        $id       = isset($_POST['id'])       ? $_POST['id']       : $r['id'];
        $username = isset($_POST['username']) ? $_POST['username'] : $r['username'];
        $fname    = isset($_POST['fname'])    ? $_POST['fname']    : $r['fname'];
        $mname    = isset($_POST['mname'])    ? $_POST['mname']    : $r['mname'];
        $lname    = isset($_POST['lname'])    ? $_POST['lname']    : $r['lname'];
        $maiden   = isset($_POST['maiden'])   ? $_POST['maiden']   : $r['maiden'];
        $sex      = isset($_POST['sex'])      ? $_POST['sex']      : $r['sex'];
        $email    = isset($_POST['email'])    ? $_POST['email']    : $r['email'];
        $bYear    = isset($_POST['byear'])    ? $_POST['byear']    : $r['dob_year'];
        $bMonth   = isset($_POST['bmonth'])   ? $_POST['bmonth']   : $r['dob_month'];
        $bDay     = isset($_POST['bday'])     ? $_POST['bday']     : $r['dob_day'];
        $dYear    = isset($_POST['dyear'])    ? $_POST['dyear']    : $r['dod_year'];
        $dMonth   = isset($_POST['dmonth'])   ? $_POST['dmonth']   : $r['dod_month'];
        $dDay     = isset($_POST['dday'])     ? $_POST['dday']     : $r['dod_day'];
        $access   = isset($_POST['access'])   ? $_POST['access']   : $r['access'];

        for ($i = 1; $i <= 31; $i++)
        {
            $days[$i] = $i;
        }
        for ($i = 1; $i <= 12; $i++)
        {
            $months[$i] = getMonthName($i);
        }

        // Display the form
        echo '
            <form method="post" action="members.php">
                <fieldset>
                    <legend>'.T_('Edit Member').'</legend>
                    <a class="btn merge" href="?merge='.$member.'">'.T_('Merge').'</a>
                    <div class="clearfix">
                        <label for="username">'.T_('Username').'</label>
                        <div class="input">
                            <input type="text" name="username" id="username" disabled="disabled" value="'.cleanOutput($username).'" size="25"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var funame = new LiveValidation(\'username\', { onlyOnSubmit: true });
                        funame.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="clearfix">
                        <label for="password">'.T_('Password').'</label>
                        <div class="input">
                            <input type="password" name="password" id="password" size="25"/>
                        </div>
                    </div>
                    <div class="clearfix">
                        <label for="fname">'.T_('First Name').'</label> 
                        <div class="input">
                            <input type="text" name="fname" id="fname" value="'.cleanOutput($fname).'" size="50"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                        ffname.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div class="clearfix">
                        <label for="mname">'.T_('Middle Name').'</label> 
                        <div class="input">
                            <input type="text" name="mname" id="mname" value="'.cleanOutput($mname).'" size="50"/>
                        </div>
                    </div>
                    <div class="clearfix">
                        <label for="lname">'.T_('Last Name').'</label> 
                        <div class="input">
                            <input type="text" name="lname" id="lname" value="'.cleanOutput($lname).'" size="50"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                        flname.add(Validate.Presence, {failureMessage: ""});
                    </script>';

        if ($sex == 'F')
        {
            echo '
                    <div class="clearfix">
                        <label for="maiden">'.T_('Maiden Name').'</label> 
                        <div class="input">
                            <input type="text" name="maiden" id="maiden" value="'.cleanOutput($maiden).'" size="50"/>
                        </div>
                    </div>';
        }

        $maleSelected   = '';
        $femaleSelected = '';
        if ($sex == 'M')
        {
            $maleSelected = 'selected="selected"';
        }
        else
        {
            $femaleSelected = 'selected="selected"';
        }

        $accessSelected1  = '';
        $accessSelected2  = '';
        $accessSelected3  = '';
        $accessSelected4  = '';
        $accessSelected5  = '';
        $accessSelected6  = '';
        $accessSelected7  = '';
        $accessSelected8  = '';
        $accessSelected9  = '';
        $accessSelected10 = '';
        $accessSelected11 = '';
        if ($access == 1)
        {
            $accessSelected1 = 'selected="selected"';
        }
        if ($access == 2)
        {
            $accessSelected2 = 'selected="selected"';
        }
        if ($access == 3)
        {
            $accessSelected3 = 'selected="selected"';
        }
        if ($access == 4)
        {
            $accessSelected4 = 'selected="selected"';
        }
        if ($access == 5)
        {
            $accessSelected5 = 'selected="selected"';
        }
        if ($access == 6)
        {
            $accessSelected6 = 'selected="selected"';
        }
        if ($access == 7)
        {
            $accessSelected7 = 'selected="selected"';
        }
        if ($access == 8)
        {
            $accessSelected8 = 'selected="selected"';
        }
        if ($access == 9)
        {
            $accessSelected9 = 'selected="selected"';
        }
        if ($access == 10)
        {
            $accessSelected10 = 'selected="selected"';
        }
        if ($access == 11)
        {
            $accessSelected11 = 'selected="selected"';
        }

        echo '
                    <div class="clearfix">
                        <label for="sex">'.T_('Gender').'</label> 
                        <div class="input">
                            <select name="sex" id="sex">
                                <option value="M" '.$maleSelected.'>'.T_('Male').'</option>
                                <option value="F" '.$femaleSelected.'>'.T_('Female').'</option>
                            </select>
                        </div>
                    </div>
                    <div class="clearfix">
                        <label for="email">'.T_('Email').'</label> 
                        <div class="input">
                            <input type="text" name="email" id="email" value="'.cleanOutput($email).'" size="50"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                        femail.add( Validate.Presence, { failureMessage: "'.T_('Sorry, but this information is Required.').'" } );
                        femail.add( Validate.Email, { failureMessage: "'.T_('That\'s not a valid email address is it?').'" } );
                        femail.add( Validate.Length, { minimum: 10 } );
                    </script>
                    <div class="clearfix">
                        <label for="bday">'.T_('Birthday').'</label> 
                        <div class="input">
                            <select id="bday" name="bday" class="span2">
                                <option value="">'.T_('Day').'</option>
                                '.buildHtmlSelectOptions($days, $bDay).'
                            </select>
                            <select id="bmonth" name="bmonth" class="span3">
                                <option value="">'.T_('Month').'</option>
                                '.buildHtmlSelectOptions($months, $bMonth).'
                            </select>
                            <input type="text" name="byear" id="byear" size="5" maxlength="4" placeholder="'.T_('Year').'" value="'.$bYear.'"/>
                        </div>
                    </div>
                    <div class="clearfix">
                        <label for="dday">'.T_('Deceased').'</label> 
                        <div class="input">
                            <select id="dday" name="dday" class="span2">
                                <option value="">'.T_('Day').'</option>
                                '.buildHtmlSelectOptions($days, $dDay).'
                            </select>
                            <select id="dmonth" name="dmonth" class="span3">
                                <option value="">'.T_('Month').'</option>
                                '.buildHtmlSelectOptions($months, $dMonth).'
                            </select>
                            <input type="text" name="dyear" id="dyear" size="5" maxlength="4" placeholder="'.T_('Year').'" value="'.$dYear.'"/>
                        </div>
                    </div>
                    <div class="clearfix">
                        <label for="access">'.T_('Access Level').'</label> 
                        <div class="input">
                            <select id="access" name="access">
                                <option value="1" '.$accessSelected1.'>1. '.T_('Admin').'</option>
                                <option value="2" '.$accessSelected2.'>2. '.T_('Helper').'</option>
                                <option value="3" '.$accessSelected3.'>3. '.T_('Member').'</option>
                                <option value="'.(int)$access.'"></option>
                                <option value="'.(int)$access.'">'.T_('Advanced Options').'</option>
                                <option value="'.(int)$access.'">-------------------------------------</option>
                                <option value="4" '.$accessSelected4.'>4. '.T_('Non-Photographer').'</option>
                                <option value="5" '.$accessSelected5.'>5. '.T_('Non-Poster').'</option>
                                <option value="6" '.$accessSelected6.'>6. '.T_('Commenter').'</option>
                                <option value="7" '.$accessSelected7.'>7. '.T_('Poster').'</option>
                                <option value="8" '.$accessSelected8.'>8. '.T_('Photographer').'</option>
                                <option value="9" '.$accessSelected9.'>9. '.T_('Blogger').'</option>
                                <option value="10" '.$accessSelected10.'>10. '.T_('Guest').'</option>
                                <option value="11" '.$accessSelected11.'>11. '.T_('Non-Editable Member').'</option>
                            </select>
                        </div>
                    </div>
                    <div class="actions">
                        <input type="hidden" id="id" name="id" value="'.(int)$id.'"/>
                        <input class="btn primary" type="submit" id="edit" name="edit" value="'.T_('Save Changes').'"/>
                        <input class="btn danger" type="submit" id="delete" name="delete" value="'.T_('Delete').'"/>
                        <a class="btn secondary" href="members.php">'.T_('Cancel').'</a>
                    </div>
                </fieldset>
            </form>';
    }

    /**
     * displayMergeMemberForm 
     * 
     * @param int $id The id of current member
     * 
     * @return void
     */
    function displayMergeMemberForm ($id)
    {
        $id = (int)$id;

        // Get current member info
        $sql = "SELECT u.`id`, u.`username`, u.`fname`, u.`mname`, u.`lname`, u.`maiden`, u.`email`, 
                    u.`dob_year`, u.`dob_month`, u.`dob_day`,
                    a.`address`, a.`city`, a.`state`, a.`zip`, a.`home`, a.`work`, a.`cell`, u.`bio`
                FROM `fcms_users` AS u, `fcms_address` AS a
                WHERE u.`id` = ?
                AND u.`id` = a.`user`";

        $r = $this->fcmsDatabase->getRow($sql, $id);
        if ($r === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        // Get member list
        $sql = "SELECT `id`, `username`, `password`,`fname`, `lname`
                FROM `fcms_users` 
                WHERE `id` != ?";

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $members = array();

        foreach ($rows as $row)
        {
            if ($row['password'] == 'NONMEMBER')
            {
                $members[$row['id']] = $row['lname'].', '.$row['fname'].' ('.T_('Non-member').')';
                continue;
            }

            $members[$row['id']] = $row['lname'].', '.$row['fname'].' ('.$row['username'].')';
        }

        asort($members);

        // Display the form
        echo '
            <form method="post" id="merge-form" action="members.php">
                <fieldset>
                    <legend><span>'.T_('Merge Member').'</span></legend>
                    <div class="row">
                        <div class="span8">
                            <div class="clearfix">
                                <label>'.T_('ID').'</label>
                                <div class="input">'.$r['id'].'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Username').'</label>
                                <div class="input">'.cleanOutput($r['username']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Name').'</label>
                                <div class="input">'.cleanOutput($r['fname']).' '.cleanOutput($r['mname']).' '.cleanOutput($r['lname']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Maiden Name').'</label>
                                <div class="input">'.cleanOutput($r['maiden']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Email').'</label>
                                <div class="input">'.cleanOutput($r['email']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Birthday').'</label>
                                <div class="input">'.formatBirthday($r['dob_year'], $r['dob_month'], $r['dob_day']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Address').'</label>
                                <div class="input">'.cleanOutput($r['address']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('City').'</label>
                                <div class="input">'.cleanOutput($r['city']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('State').'</label>
                                <div class="input">'.cleanOutput($r['state']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Zip').'</label>
                                <div class="input">'.cleanOutput($r['zip']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Home Phone').'</label>
                                <div class="input">'.cleanOutput($r['home']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Work Phone').'</label>
                                <div class="input">'.cleanOutput($r['work']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Cell Phone').'</label>
                                <div class="input">'.cleanOutput($r['cell']).'</div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Bio').'</label>
                                <div class="input">'.cleanOutput($r['bio']).'</div>
                            </div>
                        </div><!-- span8 -->
                        <div class="span8">
                            <p>
                                <b>'.T_('Member to merge with').'</b><br/>
                                <select id="merge-with" name="merge-with">
                                    <option value="0"></option>
                                    '.buildHtmlSelectOptions($members, -1).'
                                </select>
                            </p>
                        </div><!-- /span8 -->
                    </div><!-- /row -->
                    <div class="actions">
                        <input type="hidden" id="id" name="id" value="'.(int)$id.'"/>
                        <input class="btn primary" type="submit" id="merge-review" name="merge-review" value="'.T_('Next').'"/>
                        <a class="btn" href="members.php">'.T_('Cancel').'</a>
                    </div>
                </fieldset>
            </form>';
    }

    /**
     * displayMergeMemberFormReview
     * 
     * @param int $id    The id of the current member
     * @param int $merge The id of the member you are merging with
     * 
     * @return void
     */
    function displayMergeMemberFormReview ($id, $merge)
    {
        $id    = (int)$id;
        $merge = (int)$merge;

        $sql = "SELECT u.`id`, u.`username`, u.`fname`, u.`mname`, u.`lname`, u.`maiden`, u.`email`, 
                    u.`dob_year`, u.`dob_month`, u.`dob_day`, 
                    a.`address`, a.`city`, a.`state`, a.`zip`, a.`home`, a.`work`, a.`cell`, u.`bio`
                FROM `fcms_users` AS u, `fcms_address` AS a
                WHERE u.`id` IN ('$id', '$merge') 
                AND u.`id` = a.`user`";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            return;
        }
        while ($r = $this->db->get_row())
        {
            $members[$r['id']] = $r;
        }

        $year1  = empty($members[$id]['dob_year'])     ? '0000' : $members[$id]['dob_year'];
        $month1 = empty($members[$id]['dob_month'])    ? '00'   : $members[$id]['dob_month'];
        $day1   = empty($members[$id]['dob_day'])      ? '00'   : $members[$id]['dob_day'];
        $year2  = empty($members[$merge]['dob_year'])  ? '0000' : $members[$merge]['dob_year'];
        $month2 = empty($members[$merge]['dob_month']) ? '00'   : $members[$merge]['dob_month'];
        $day2   = empty($members[$merge]['dob_day'])   ? '00'   : $members[$merge]['dob_day'];

        $birthday1 = $year1.'-'.$month1.'-'.$day1;
        $birthday2 = $year2.'-'.$month2.'-'.$day2;

        $formatBirthday1 = formatBirthday($members[$id]['dob_year'], $members[$id]['dob_month'], $members[$id]['dob_day']);
        $formatBirthday2 = formatBirthday($members[$merge]['dob_year'], $members[$merge]['dob_month'], $members[$merge]['dob_day']);

        // Display form
        echo '
            <form method="post" id="merge-form" action="members.php">
                <fieldset>
                    <legend><span>'.T_('Merge Member').'</span></legend>
                    <div class="alert-message block-message info">
                        <p>'.T_('Choose which information you would like to use from the two members below.').'</p>
                        <p>'.sprintf(T_('Please note that user [%s] and all information not selected will be deleted.'), $merge).'</p>
                    </div>
                    <div class="row">
                        <div class="span8">
                            <div class="clearfix">
                                <label>'.T_('ID').'</label>
                                <div class="input">
                                    <ul class="inputs-list"><li><label>'.$members[$id]['id'].'</label></li></ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Username').'</label>
                                <div class="input">
                                    <ul class="inputs-list"><li><label>'.$members[$id]['username'].'</label></li></ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('First Name').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="f1" name="fname" value="'.$members[$id]['fname'].'"/>
                                                <span>'.$members[$id]['fname'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Middle Name').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="m1" name="mname" value="'.$members[$id]['mname'].'"/>
                                                <span>'.$members[$id]['mname'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Last Name').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="l1" name="lname" value="'.$members[$id]['lname'].'"/>
                                                <span>'.$members[$id]['lname'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Maiden Name').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="ma1" name="maiden" value="'.$members[$id]['maiden'].'"/>
                                                <span>'.$members[$id]['maiden'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Email').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="e1" name="email" value="'.$members[$id]['email'].'"/>
                                                <span>'.$members[$id]['email'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Birthday').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="b1" name="birthday" value="'.$birthday1.'"/>
                                                <span>'.$formatBirthday1.'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Address').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="a1" name="address" value="'.$members[$id]['address'].'"/>
                                                <span>'.$members[$id]['address'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('City').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="c1" name="city" value="'.$members[$id]['city'].'"/>
                                                <span>'.$members[$id]['city'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('State').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="s1" name="state" value="'.$members[$id]['state'].'"/>
                                                <span>'.$members[$id]['state'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Zip').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="z1" name="zip" value="'.$members[$id]['zip'].'"/>
                                                <span>'.$members[$id]['zip'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Home Phone').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="h1" name="home" value="'.$members[$id]['home'].'"/>
                                                <span>'.$members[$id]['home'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Work Phone').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="w1" name="work" value="'.$members[$id]['work'].'"/>
                                                <span>'.$members[$id]['work'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Cell Phone').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="ce1" name="cell" value="'.$members[$id]['cell'].'"/>
                                                <span>'.$members[$id]['cell'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Bio').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" checked="checked" id="bi1" name="bio" value="'.$members[$id]['bio'].'"/>
                                                <span>'.$members[$id]['bio'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div><!-- /span8 -->
                        <div class="span8">
                            <div class="clearfix">
                                <label>'.T_('ID').'</label>
                                <div class="input">
                                    <ul class="inputs-list"><li><label>'.$members[$merge]['id'].'</label></li></ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Username').'</label>
                                <div class="input">
                                    <ul class="inputs-list"><li><label>'.$members[$merge]['username'].'</label></li></ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('First Name').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="f2" name="fname" value="'.$members[$merge]['fname'].'"/>
                                                <span>'.$members[$merge]['fname'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Middle Name').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="m2" name="mname" value="'.$members[$merge]['mname'].'"/>
                                                <span>'.$members[$merge]['mname'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Last Name').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="l2" name="lname" value="'.$members[$merge]['lname'].'"/>
                                                <span>'.$members[$merge]['lname'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Maiden Name').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="ma2" name="maiden" value="'.$members[$merge]['maiden'].'"/>
                                                <span>'.$members[$merge]['maiden'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Email').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="e2" name="email" value="'.$members[$merge]['email'].'"/>
                                                <span>'.$members[$merge]['email'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Birthday').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="b2" name="birthday" value="'.$birthday2.'"/>
                                                <span>'.$formatBirthday2.'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Address').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="a2" name="address" value="'.$members[$merge]['address'].'"/>
                                                <span>'.$members[$merge]['address'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('City').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="c2" name="city" value="'.$members[$merge]['city'].'"/>
                                                <span>'.$members[$merge]['city'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('State').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="s2" name="state" value="'.$members[$merge]['state'].'"/>
                                                <span>'.$members[$merge]['state'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Zip').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="z2" name="zip" value="'.$members[$merge]['zip'].'"/>
                                                <span>'.$members[$merge]['zip'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Home Phone').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="h2" name="home" value="'.$members[$merge]['home'].'"/>
                                                <span>'.$members[$merge]['home'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Work Phone').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="w2" name="work" value="'.$members[$merge]['work'].'"/>
                                                <span>'.$members[$merge]['work'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Cell Phone').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="ce2" name="cell" value="'.$members[$merge]['cell'].'"/>
                                                <span>'.$members[$merge]['cell'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="clearfix">
                                <label>'.T_('Bio').'</label>
                                <div class="input">
                                    <ul class="inputs-list">
                                        <li>
                                            <label>
                                                <input type="radio" id="bi2" name="bio" value="'.$members[$merge]['bio'].'"/>
                                                <span>'.$members[$merge]['bio'].'</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div><!-- /span8 -->
                    </div><!-- /row -->
                    <div class="actions">
                        <input type="hidden" id="id" name="id" value="'.$id.'"/>
                        <input type="hidden" id="merge" name="merge" value="'.$merge.'"/>
                        <input class="btn primary" type="submit" id="merge-submit" name="merge-submit" value="'.T_('Merge').'"/>
                        <a class="btn" href="members.php">'.T_('Cancel').'</a>
                    </div>
                </fieldset>
            </form>';
    }

    /**
     * displayMemberList 
     * 
     * Displays the list of members, by default list all or list based on search results.
     *
     * @param int    $page  which page to display
     * @param string $fname search parameter for first name
     * @param string $lname search parameter for last name
     * @param string $uname search parameter for username
     *
     * @return  void
     */
    function displayMemberList ($page, $fname = '', $lname = '', $uname = '')
    {
        $valid_search = 0;
        $perPage      = 30;

        $from = (($page * $perPage) - $perPage);

        $view = 'members';

        $allActive     = '';
        $membersActive = '';
        $nonActive     = '';

        if (isset($_GET['view']))
        {
            if ($_GET['view'] == 'all')
            {
                $view      = 'all';
                $allActive = 'active';
            }
            elseif ($_GET['view'] == 'non')
            {
                $view      = 'non';
                $nonActive = 'active';
            }
            else
            {
                $membersActive = 'active';
            }
        }
        else
        {
            $membersActive = 'active';
        }
        
        // Display the add link, search box and table header
        echo '
            <ul class="tabs">
                <li class="'.$allActive.'"><a href="?view=all">'.T_('All').'</a></li>
                <li class="'.$membersActive.'"><a href="?view=members">'.T_('Members').'</a></li>
                <li class="'.$nonActive.'"><a href="?view=non">'.T_('Non-Members').'</a></li>
                <li class="action"><a href="?create=member">'.T_('Create Member').'</a></li>
            </ul>
            <form method="post" action="members.php" name="search_frm" id="search_frm">
                <div>
                    <input type="text" name="fname" id="fname" placeholder="'.T_('First Name').'" title="'.T_('First Name').'" value="'.cleanOutput($fname).'"/>
                    <input type="text" name="lname" id="lname" placeholder="'.T_('Last Name').'" title="'.T_('Last Name').'" value="'.cleanOutput($lname).'"/>
                    <input type="text" name="uname" id="uname" placeholder="'.T_('Username').'" title="'.T_('Username').'" value="'.cleanOutput($uname).'"/>
                    <input type="submit" id="search" name="search" value="'.T_('Search').'"/>
                </div>
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
                            <th class="nosort">
                                <a class="help u" title="'.T_('Get Help using Access Levels').'" href="../help.php#adm-access">'.T_('Access Level').'</a>
                            </th>
                            <th class="nosort">'.T_('Member?').'</th>
                            <th class="nosort">'.T_('Active?').'</th>
                            <th class="nosort">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // prevent sql injections - only allow letters, numbers, a space and the % sign
        if (strlen($fname) > 0)
        {
            if (!preg_match('/^[A-Za-z0-9%\s]+$/', $fname))
            {
                $valid_search++;
            }
        }
        if (strlen($lname) > 0)
        {
            if (!preg_match('/^[A-Za-z0-9%\s]+$/', $lname))
            {
                $valid_search++;
            }
        }
        if (strlen($uname) > 0)
        {
            if (!preg_match('/^[A-Za-z0-9%\s]+$/', $uname))
            {
                $valid_search++;
            }
        }

        $params = array();

        $sql = "SELECT *
                FROM `fcms_users` ";

        if ($view == 'members')
        {
            $sql .= "WHERE `password` != 'NONMEMBER'
                     AND `password` != 'PRIVATE' ";
        }
        elseif ($view == 'non')
        {
            $sql .= "WHERE `password` = 'NONMEMBER' ";
        }
        
        // Search - one or valid search parameters
        if ($valid_search < 1)
        {
            if (strlen($fname) > 0)
            {
                $sql     .= "AND `fname` LIKE ? ";
                $params[] = $fname;
            }
            if (strlen($lname) > 0)
            {
                $sql     .= "AND `lname` LIKE ? ";
                $params[] = $lname;
            }
            if (strlen($uname) > 0)
            {
                $sql     .= "AND `username` LIKE ? ";
                $params[] = $uname;
            }

            $sql .= "ORDER BY `id` LIMIT $from, $perPage";

            $rows = $this->fcmsDatabase->getRows($sql, $params);
        }
        // Display All - one of more blank or invalid search parameters
        else
        {
            $sql .= "ORDER BY `id`
                     LIMIT $from, $perPage";

            $rows = $this->fcmsDatabase->getRows($sql);
        }

        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }
        
        // Display the member list
        foreach ($rows as $r)
        {
            $member = ($r['password'] == 'NONMEMBER') ? T_('No') : T_('Yes');
            $active = ($r['activated'] <= 0)          ? T_('No') : T_('Yes');

            if ($r['id'] > 1)
            {
                echo '
                        <tr>
                            <td><b>'.(int)$r['id'].'</b>:</td>
                            <td><a href="?edit='.(int)$r['id'].'">'.cleanOutput($r['username']).'</a></td>
                            <td>'.cleanOutput($r['lname']).'</td>
                            <td>'.cleanOutput($r['fname']).'</td>
                            <td>'; echo $this->displayAccessType($r['access']); echo '</td>
                            <td style="text-align:center">'.$member.'</td>
                            <td style="text-align:center">'.$active.'</td>
                            <td style="text-align:center"><input type="checkbox" name="massupdate[]" value="'.(int)$r['id'].'"/></td>
                        </tr>';
            } else {
                echo '
                        <tr>
                            <td><b>'.(int)$r['id'].'</b>:</td>
                            <td><b>'.cleanOutput($r['username']).'</b></td>
                            <td>'.cleanOutput($r['lname']).'</td>
                            <td>'.cleanOutput($r['fname']).'</td>
                            <td>1. '.T_('Admin').'</td>
                            <td style="text-align:center">'.T_('Yes').'</td>
                            <td style="text-align:center">'.T_('Yes').'</td>
                            <td>&nbsp;</td>
                        </tr>';
            }
        }
        echo '
                    </tbody>
                </table>
                <p style="text-align:right">
                    <input type="submit" class="btn primary" name="activateAll" id="activateAll" value="'.T_('Activate Selected').'"/>&nbsp; 
                    <input type="submit" class="btn" name="inactivateAll" id="inactivateAll" value="'.T_('Inactivate Selected').'"/>&nbsp; 
                    <input type="submit" class="btn danger" name="deleteAll" id="deleteAll" value="'.T_('Delete Selected').'"/>
                </p>
            </form>';

        // Remove the LIMIT from the $sql statement 
        // used above, so we can get the total count
        $sql = substr($sql, 0, strpos($sql, 'LIMIT'));

        if ($valid_search < 1)
        {
            $mrows = $this->fcmsDatabase->getRows($sql, $params);
        }
        else
        {
            $mrows = $this->fcmsDatabase->getRows($sql);
        }

        if ($mrows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $count       = count($mrows);
        $total_pages = ceil($count / $perPage); 

        displayPages("members.php?view=$view", $page, $total_pages);
    }
    
    /**
     * displayAccessType 
     * 
     * Displays the access type based on access level code
     *
     * @param int $access_level The level of access
     *
     * @return  void
     */
    function displayAccessType ($access_level)
    {
        switch ($access_level)
        {
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
            case 11:
                echo "11. ".T_('Non-editable Member');
                break;
            default:
                echo "10. ".T_('Guest');
                break;
        }
    }

    /**
     * mergeMember 
     * 
     * @param int $id    The id of the current member
     * @param int $merge The id of the user you are merging with
     * 
     * @return void
     */
    function mergeMember ($id, $merge)
    {
        $id    = (int)$_POST['id'];
        $merge = (int)$_POST['merge'];

        // fcms_address

        // fcms_alerts
        $sql = "DELETE FROM `fcms_alerts`
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_alerts').'<br/>';

        // fcms_board_posts
        $sql = "UPDATE `fcms_board_posts`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_board_posts').'<br/>';

        // fcms_board_thread
        $sql = "UPDATE `fcms_board_threads`
                SET `started_by` = '$id'
                WHERE `started_by` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        $sql = "UPDATE `fcms_board_threads`
                SET `updated_by` = '$id'
                WHERE `updated_by` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_board_threads').'<br/>';

        // fcms_calendar
        $sql = "UPDATE `fcms_calendar`
                SET `created_by` = '$id'
                WHERE `created_by` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_calendar').'<br/>';

        // fcms_category
        $sql = "UPDATE `fcms_category`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_category').'<br/>';

        // fcms_chat_messages

        // fcms_chat_users

        // fcms_config

        // fcms_documents
        $sql = "UPDATE `fcms_documents`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_documents').'<br/>';

        // fcms_gallery_photo_comments
        $sql = "UPDATE `fcms_gallery_photo_comment`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_gallery_photo_comment').'<br/>';

        // fcms_gallery_photos
        $sql = "UPDATE `fcms_gallery_photos`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_gallery_photos').'<br/>';

        // fcms_gallery_photos_tags
        $sql = "UPDATE `fcms_gallery_photos_tags`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_gallery_photos_tags').'<br/>';

        // fcms_navigation

        // fcms_news
        $sql = "UPDATE `fcms_news`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_news').'<br/>';

        // fcms_news_comments
        $sql = "UPDATE `fcms_news_comments`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_news_comments').'<br/>';

        // fcms_polls

        // fcms_poll_options

        // fcms_poll_votes
        $sql = "UPDATE `fcms_poll_votes`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_poll_votes').'<br/>';

        // fcms_prayers
        $sql = "UPDATE `fcms_prayers`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_prayers').'<br/>';

        // fcms_privatemsg
        $sql = "UPDATE `fcms_privatemsg`
                SET `to` = '$id'
                WHERE `to` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        $sql = "UPDATE `fcms_privatemsg`
                SET `from` = '$id'
                WHERE `from` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_privatemsg').'<br/>';

        // fcms_recipes
        $sql = "UPDATE `fcms_recipes`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_recipes').'<br/>';

        // fcms_recipe_comment
        $sql = "UPDATE `fcms_recipe_comment`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_recipe_comment').'<br/>';

        // fcms_relationship
        $sql = "UPDATE `fcms_relationship`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        $sql = "UPDATE `fcms_relationship`
                SET `rel_user` = '$id'
                WHERE `rel_user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_relationship').'<br/>';

        // fcms_users

        // fcms_user_awards
        $sql = "UPDATE `fcms_user_awards`
                SET `user` = '$id'
                WHERE `user` = '$merge'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();
            die();
        }
        echo sprintf(T_('Merge [%s] complete.'), 'fcms_user_awards').'<br/>';

        // fcms_user_settings
    }
}
