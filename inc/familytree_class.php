<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

/**
 * FamilyTree 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class FamilyTree {

    var $db;
    var $db2;
    var $tz_offset;
    var $currentUserId;

    /**
     * FamilyTree 
     * 
     * @param   int     $currentUserId 
     * @param   string  $type 
     * @param   string  $host 
     * @param   string  $database 
     * @param   string  $user 
     * @param   string  $pass 
     * @return  void
     */
    function FamilyTree ($currentUserId, $type, $host, $database, $user, $pass)
    {
        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->db = new database($type, $host, $database, $user, $pass);
        $this->db2 = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` 
                FROM `fcms_user_settings` 
                WHERE `user` = '$currentUserId'";
        $this->db->query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];

    }

    /**
     * displayFamilyTree 
     * 
     * Displays the family tree for the given user.
     * In either a tree format or list (used in profile).
     * 
     * both parents will have chil relationship with children
     * only relationships are:  CHIL, WIFE, HUSB
     * 
     * @param   int     $id     the id of the user's family tree
     * @param   string  $type   tree or list or list_edit
     * @return  void
     */
    function displayFamilyTree ($id, $type = 'tree')
    {
        $id = cleanInput($id, 'int');

        $valid_types = array('tree', 'list', 'list_edit');
        if (!in_array($type, $valid_types)) {
            echo '
            <div class="error-alert">' . T_('Invalid Display Type') . '</div>';
        }

        // Get info for user
        $sql = "SELECT `id`, `fname`, `lname`, `sex`, `avatar`, `birthday`
                FROM `fcms_users` 
                WHERE `id` = '$id'
                LIMIT 1";
        $result = $this->db->query($sql) or displaySQLError(
            'User Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() < 1) {
            echo '<div class="error-alert">' . T_('Could not find user.') . '</div>';
            return;
        }
        $user = $this->db->get_row();

        // Get spouse and kids for user
        $sql = "SELECT u.`id`, u.`fname`, u.`lname`, u.`avatar`, r.`relationship`, r.`rel_user` 
                FROM `fcms_relationship` AS r, `fcms_users` AS u 
                WHERE `user` = '$id' 
                AND r.`rel_user` = u.`id`";
        $result = mysql_query($sql) or displaySQLError(
            'Spouse Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $spouse = array();
        $kids = array();
        while ($row = mysql_fetch_assoc($result)) {
            if ($row['relationship'] == 'WIFE' or $row['relationship'] == 'HUSB') {
                $spouse = array(
                    'id'        => $row['id'], 
                    'fname'     => $row['fname'], 
                    'lname'     => $row['lname'], 
                    'avatar'    => $row['avatar']
                );
            }
            if ($row['relationship'] == 'CHIL') {
                $tmp = array(
                    'id'        => $row['id'], 
                    'fname'     => $row['fname'], 
                    'lname'     => $row['lname'], 
                    'avatar'    => $row['avatar']
                );
                array_push($kids, $tmp);
            }
        }

        // Get user's parents
        $sql = "SELECT u.`id`, u.`fname`, u.`lname`, u.`avatar`, r.`relationship`, r.`rel_user`, u.`birthday`, u.`sex` 
                FROM `fcms_relationship` AS r, `fcms_users` AS u 
                WHERE `rel_user` = '$id' 
                AND r.`user` = u.`id`
                AND r.`relationship` = 'CHIL'
                LIMIT 2";
        $result = mysql_query($sql) or displaySQLError(
            'Parents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $parents = array();
        $dad = '';
        $mom = '';
        if ($this->db->count_rows() > 0) {
            while($row = mysql_fetch_assoc($result)) {
                // dad
                if ($row['sex'] == 'M') {
                    $dad = $row['id'];
                    $tmp = array(
                        'id'        => $row['id'], 
                        'fname'     => $row['fname'], 
                        'lname'     => $row['lname'], 
                        'avatar'    => $row['avatar'],
                        'birthday'  => $row['birthday']
                    );
                    $parents[0] = $tmp;
                // mom
                } else {
                    $mom = $row['id'];
                    $tmp = array(
                        'id'        => $row['id'], 
                        'fname'     => $row['fname'], 
                        'lname'     => $row['lname'], 
                        'avatar'    => $row['avatar'],
                        'birthday'  => $row['birthday']
                    );
                    $parents[1] = $tmp;
                }
            }
        }

        // get parents for user's parents and wife's parents
        $dadParents = array();
        $momParents = array();
        if (!empty($dad) or !empty($mom)) {
            $sql = "SELECT u.`id`, u.`fname`, u.`lname`, u.`avatar`, r.`relationship`, r.`rel_user`, u.`birthday`, u.`sex` 
                    FROM `fcms_relationship` AS r, `fcms_users` AS u ";
            if ($dad > 0 and $mom > 0) {
                $sql .= "WHERE (`rel_user` = $dad OR `rel_user` = $mom) ";
            } elseif ($dad > 0) {
                $sql .= "WHERE `rel_user` = $dad ";
            } elseif ($mom > 0) {
                $sql .= "WHERE `rel_user` = $mom ";
            }
            $sql .="AND r.`user` = u.`id`
                    AND r.`relationship` = 'CHIL'";

            $result = mysql_query($sql) or displaySQLError(
                'Grandparents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            if ($this->db->count_rows() > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    // dad's parents
                    if ($row['rel_user'] == $dad) {
                        // grandpa
                        if ($row['sex'] == 'M') {
                            $tmp = array(
                                'id'        => $row['id'], 
                                'fname'     => $row['fname'], 
                                'lname'     => $row['lname'], 
                                'avatar'    => $row['avatar'],
                                'birthday'  => $row['birthday']
                            );
                            $dadParents[0] = $tmp;
                        // grandma
                        } else {
                            $tmp = array(
                                'id'        => $row['id'], 
                                'fname'     => $row['fname'], 
                                'lname'     => $row['lname'], 
                                'avatar'    => $row['avatar'],
                                'birthday'  => $row['birthday']
                            );
                            $dadParents[1] = $tmp;
                        }
                    // mom's parents
                    } else  {
                        // grandpa
                        if ($row['sex'] == 'M') {
                            $tmp = array(
                                'id'        => $row['id'], 
                                'fname'     => $row['fname'], 
                                'lname'     => $row['lname'], 
                                'avatar'    => $row['avatar'],
                                'birthday'  => $row['birthday']
                            );
                            $momParents[0] = $tmp;
                        // grandma
                        } else {
                            $tmp = array(
                                'id'        => $row['id'], 
                                'fname'     => $row['fname'], 
                                'lname'     => $row['lname'], 
                                'avatar'    => $row['avatar'],
                                'birthday'  => $row['birthday']
                            );
                            $momParents[1] = $tmp;
                        }
                    }
                }
            }
        }

        // Display Tree
        if ($type == 'tree') {
            $this->displayMembersTreeList();
            echo '
            <div id="tree">';
            $this->displaySpouseKidsColumn($user, $spouse, $kids);
            $this->displayParentsColumn($id, $parents);
            $this->displayGrandparentsColumn($dad, $mom, $dadParents, $momParents);
            echo '
            </div>
            <div id="tree_toolbar">
                <a href="?remove=user&amp;id=' . $id . '">' . T_('Remove Relationship') . '</a>
            </div>';

        } elseif ($type == 'list') {
            $this->displayFamilyTreeList($spouse, $kids, $parents, $dadParents, $momParents);
        } elseif ($type == 'list_edit') {
            $this->displayFamilyTreeEditList($id, $spouse, $kids, $parents, $dadParents, $momParents);
        }
    }

    /**
     * displaySpouseKidsColumn
     * 
     * Used by displayFamilyTree, displays the first column
     * in the tree, containing the user, the spouse and any kids
     * 
     * @param  array $user
     * @param  array $spouse
     * @param  array $kids
     * @return void
     */
    function displaySpouseKidsColumn ($user, $spouse, $kids)
    {
        // current user
        echo '
                <div class="column first">
                    <div class="leaf me">
                        <b>' . $user['fname'] . ' ' . $user['lname'] . '</b>
                        <span>' . date('Y', strtotime($user['birthday'])) . ' - </span>
                    </div>';

        // spouse
        if (count($spouse) > 0) {
            echo '
                    <a href="?tree=' . $spouse['id'] . '" class="leaf spouse">
                        <b>' . $spouse['fname'] . ' ' .$spouse['lname'] . '</b>
                    </a>';
        } else {
            $add = ($user['sex'] == 'M') ? 'wife' : 'husb';
            echo '
                    <a href="?add='.$add.'&amp;user=' . $user['id'] . '" class="leaf spouse">
                        <b>' . T_('Add Spouse') . '</b>
                    </a>';
        }

        // kids
        if (count($kids) > 0) {
            foreach ($kids as $kid) {
                echo '
                    <a href="?tree=' . $kid['id'] . '" class="leaf child">
                        <b>' . $kid['fname'] . ' ' . $kid['lname'] . '</b>
                    </a>';
            }
        }

        // close column
        echo '
                    <a href="?add=child&amp;user=' . $user['id'] . '" class="leaf child">
                        ' . T_('Add Child') . '
                    </a>
                </div>';
    }

    /**
     * displayParentsColumn
     * 
     * Used by displayFamilyTree, displays the second column
     * in the tree, containing the user's parents
     * 
     * @param   int     $id the id of the user
     * @param   array   $parents
     * @return  void
     */
    function displayParentsColumn ($id, $parents)
    {
        echo '
                <div class="column second">';

        // dad
        if (isset($parents[0])) {
            echo '
                    <a href="?tree=' . $parents[0]['id'] . '" class="leaf dad">
                        <b>' . $parents[0]['fname'] . ' ' . $parents[0]['lname'] . '</b>
                        <span> ' . date('Y', strtotime($parents[0]['birthday'])) . ' - </span>
                    </a>';
        } else {
            echo '
                    <a href="?add=dad&amp;user=' . $id . '" class="leaf dad unknown">
                        ' . T_('Add Father') . '
                    </a>';
        }

        // mom
        if (isset($parents[1])) {
            echo '
                    <a href="?tree=' . $parents[1]['id'] . '" class="leaf mom">
                        <b>' . $parents[1]['fname'] . ' ' . $parents[1]['lname'] . '</b>
                        <span> ' . date('Y', strtotime($parents[1]['birthday'])) . ' - </span>
                    </a>';
        } else {
            echo '
                    <a href="?add=mom&amp;user=' . $id . '" class="leaf mom unknown">
                        ' . T_('Add Mother') . '
                    </a>';
        }

        echo '
                </div>';
    }

    /**
     * displayGrandparentsColumn
     * 
     * Used by displayFamilyTree, displays the last column
     * in the tree, containing the user's grandparents
     * 
     * @param   int     $dad        the id of the user's dad
     * @param   int     $mom        the id of the user's mom
     * @param   array   $dadParents
     * @param   array   $momParents
     * @return  void
     */
    function displayGrandparentsColumn ($dad, $mom, $dadParents, $momParents)
    {
        echo '
                <div class="column third">';

        // dad's dad
        if (isset($dadParents[0])) {
            echo '
                    <a href="?tree=' . $dadParents[0]['id'] . '" class="leaf grandpa">
                        <b>' . $dadParents[0]['fname'] . ' ' . $dadParents[0]['lname'] . '</b>
                        <span> ' . date('Y', strtotime($dadParents[0]['birthday'])) . ' - </span>
                    </a>';
        } else {
            // can only add a grandfather if they have a father added
            if (!empty($dad)) {
                echo '
                    <a href="?add=dad&amp;user=' . $dad . '" class="leaf grandpa unknown">
                        ' . T_('Add Grandfather') . '
                    </a>';
            } else {
                echo '
                    <div class="leaf grandpa unknown"></div>';
            }
        }

        // dad's mom
        if (isset($dadParents[1])) {
            echo '
                    <a href="?tree=' . $dadParents[1]['id'] . '" class="leaf grandma">
                        <b>' . $dadParents[1]['fname'] . ' ' . $dadParents[1]['lname'] . '</b>
                        <span> ' . date('Y', strtotime($dadParents[1]['birthday'])) . ' - </span>
                    </a>';
        } else {
            // can only add a grandmother if they have a father added
            if (!empty($dad)) {
                echo '
                    <a href="?add=mom&amp;user=' . $dad . '" class="leaf grandma unknown">
                        ' . T_('Add Grandmother') . '
                    </a>';
            } else {
                echo '
                    <div class="leaf grandma unknown"></div>';
            }
        }

        // mom's dad
        if (isset($momParents[0])) {
            echo '
                    <a href="?tree=' . $momParents[0]['id'] . '" class="leaf grandpa">
                        <b>' . $momParents[0]['fname'] . ' ' . $momParents[0]['lname'] . '</b>
                        <span> ' . date('Y', strtotime($momParents[0]['birthday'])) . ' - </span>
                    </a>';
        } else {
            // can only add a grandfather if they have a mother added
            if (!empty($mom)) {
                echo '
                    <a href="?add=dad&amp;user=' . $mom . '" class="leaf grandpa unknown">
                        ' . T_('Add Grandfather') . '
                    </a>';
            } else {
                echo '
                    <div class="leaf grandpa unknown"></div>';
            }
        }

        // mom's mom
        if (isset($momParents[1])) {
            echo '
                    <a href="?tree=' . $momParents[1]['id'] . '" class="leaf grandma">
                        <b>' . $momParents[1]['fname'] . ' ' . $momParents[1]['lname'] . '</b>
                        <span> ' . date('Y', strtotime($momParents[1]['birthday'])) . ' - </span>
                    </a>';
        } else {
            // can only add a grandmother if they have a mother added
            if (!empty($mom)) {
                echo '
                    <a href="?add=mom&amp;user=' . $mom . '" class="leaf grandma unknown">
                        ' . T_('Add Grandmother') . '
                    </a>';
            } else {
                echo '
                    <div class="leaf grandma unknown"></div>';
            }
        }

        echo '
                </div>';
    }

    /**
     * displayAddParentForm
     * 
     * Displays the form for adding a parent to the family tree
     * 
     * @param   string  $type   mom or dad
     * @param   int     $id     the child's user id
     * @return  void
     */
    function displayAddParentForm ($type, $id)
    {
        // Can only add a mom or dad
        if ($type !== 'dad' and $type !== 'mom') {
            echo '<div class="error-alert">' . T_('Invalid parent type.') . '</div>';
            return;
        }

        $sex = ($type == 'dad') ? 'M' : 'F';

        // Get list of available users
        $sql = "SELECT `id`, `fname`, `lname`
                FROM `fcms_users` 
                WHERE `sex` = '$sex'  
                AND `id` != $id  
                ORDER BY `lname`, `fname`";
        $result = mysql_query($sql) or displaySQLError(
            'Users Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() < 1) {
            echo '
        <p><a class="u" href="?create=user&amp;type=' . $type . '&amp;id=' . $id . '">' . T_('Add New Parent') . '</a></p>';

        } else {
            while($r = mysql_fetch_assoc($result)) {
                $parents[$r['id']] = $r['fname'] . ' ' . $r['lname'];
            }
            
            echo '
        <form action="familytree.php" method="post">
            <fieldset>
                <legend><span>'.T_('Add Parent').'</span></legend>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="user"><b>'.T_('Parent').'</b></label></div>
                    <div class="field-widget">
                        <select name="user">
                            '.buildHtmlSelectOptions($parents, '-1').'
                        </select><br/>
                        <p><a class="u" href="?create=user&amp;type=' . $type . '&amp;id=' . $id . '">' . T_('Add Parent Not Listed Above') . '</a></p>
                    </div>
                </div>
                <p>
                    <input type="hidden" id="relationship" name="relationship" value="CHIL"/>
                    <input type="hidden" id="rel_user" name="rel_user" value="'.$id.'"/>
                    <input type="submit" id="add-leaf" name="add-leaf" value="'.T_('Add').'"/>
                </p>
            </fieldset>
        </form>';
        }
    }

    /**
     * displayAddSpouseForm
     * 
     * Displays the form for adding a spouse to the family tree
     * 
     * @param   string  $type   wife or husb
     * @param   int     $id
     * @return  void
     */
    function displayAddSpouseForm ($type, $id)
    {
        // Can only add a wife or husb
        if ($type !== 'wife' and $type !== 'husb') {
            echo '<div class="error-alert">' . T_('Invalid spouse type.') . '</div>';
            return;
        }

        $sex = ($type == 'husb') ? 'M' : 'F';

        // Get list of available users
        $sql = "SELECT `id`, `fname`, `lname`
                FROM `fcms_users` 
                WHERE `sex` = '$sex'  
                AND `id` != $id  
                ORDER BY `lname`, `fname`";
        $result = mysql_query($sql) or displaySQLError(
            'Users Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() < 1) {
            echo '
        <p><a class="u" href="?create=user&amp;type=' . $type . '&amp;id=' . $id . '">' . T_('Add New Spouse') . '</a></p>';

        } else {
            while($r = mysql_fetch_assoc($result)) {
                $spouse[$r['id']] = $r['fname'] . ' ' . $r['lname'];
            }
            
            echo '
        <form action="familytree.php" method="post">
            <fieldset>
                <legend><span>'.T_('Add Spouse').'</span></legend>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="user"><b>'.T_('Spouse').'</b></label></div>
                    <div class="field-widget">
                        <select name="rel_user">
                            '.buildHtmlSelectOptions($spouse, '-1').'
                        </select><br/>
                        <p><a class="u" href="?create=user&amp;type=' . $type . '&amp;id=' . $id . '">' . T_('Add Spouse Not Listed Above') . '</a></p>
                    </div>
                </div>
                <p>
                    <input type="hidden" id="relationship" name="relationship" value="'.strtoupper($type).'"/>
                    <input type="hidden" id="rel_user" name="user" value="'.$id.'"/>
                    <input type="submit" id="add-leaf" name="add-leaf" value="'.T_('Add').'"/>
                </p>
            </fieldset>
        </form>';
        }
    }

    /**
     * displayAddChildForm
     * 
     * Displays the form for adding a child to the family tree
     * 
     * @param  int  $id the id of the person your adding a child for
     * @return void
     */
    function displayAddChildForm ($id)
    {
        // Get list of available users
        $sql = "SELECT `id`, `fname`, `lname`
                FROM `fcms_users`
                WHERE `id` != '$id'
                ORDER BY `lname`, `fname`";
        $result = mysql_query($sql) or displaySQLError(
            'Users Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() < 1) {
            echo '
        <p><a class="u" href="?create=user&amp;type=child&amp;id=' . $id . '">' . T_('Add New Child') . '</a></p>';

        } else {
            while($r = mysql_fetch_assoc($result)) {
                $children[$r['id']] = $r['fname'] . ' ' . $r['lname'];
            }
            
            echo '
        <form action="familytree.php" method="post">
            <fieldset>
                <legend><span>'.T_('Add Child').'</span></legend>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="rel_user"><b>'.T_('Child').'</b></label></div>
                    <div class="field-widget">
                        <select name="rel_user">
                            '.buildHtmlSelectOptions($children, '-1').'
                        </select><br/>
                        <p><a class="u" href="?create=user&amp;type=child&amp;id=' . $id . '">' . T_('Add Child Not Listed Above') . '</a></p>
                    </div>
                </div>
                <p>
                    <input type="hidden" id="relationship" name="relationship" value="CHIL"/>
                    <input type="hidden" id="user" name="user" value="'.(int)$id.'"/>
                    <input type="submit" id="add-leaf" name="add-leaf" value="'.T_('Add').'"/>
                </p>
            </fieldset>
        </form>';
        }
    }

    /**
     * displayCreateUserForm
     * 
     * Displays the form for creating a new user to be added to the family tree
     * 
     * @param string $url the url to go back to after creating this user
     * @return void
     */
    function displayCreateUserForm ($url)
    {
        $locale = new Locale();
        $year   = $locale->fixDate('Y', $this->tz_offset, gmdate('Y-m-d H:i:s'));
        $month  = $locale->fixDate('m', $this->tz_offset, gmdate('Y-m-d H:i:s'));
        $day    = $locale->fixDate('j', $this->tz_offset, gmdate('Y-m-d H:i:s'));

        echo '
        <form action="familytree.php" method="post">
            <fieldset>
                <legend><span>' . T_('Add New Member') . '</span></legend>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="fname"><b>' . T_('First Name') . '</b></label></div>
                    <div class="field-widget"><input class="frm_text" type="text" name="fname" id="fname" size="25"/></div>
                </div>
                <script type="text/javascript">
                    var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                    ffname.add(Validate.Presence, {failureMessage: ""});
                </script>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="lname"><b>' . T_('Last Name') . '</b></label></div>
                    <div class="field-widget"><input class="frm_text" type="text" name="lname" id="lname" size="25"/></div>
                </div>
                <script type="text/javascript">
                    var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                    flname.add(Validate.Presence, {failureMessage: ""});
                </script>
                <div class="field-row clearfix">
                    <div class="field-label"><label><b>' . T_('Sex') . '</b></label></div>
                    <div class="field-widget">
                        <input type="radio" name="sex" id="sex_m" value="M"/>
                        <label class="radio_label" for="sex_m"> ' . T_('Male') . '</label><br>
                        <input type="radio" name="sex" id="sex_f" value="F"/>
                        <label class="radio_label" for="sex_f"> ' . T_('Female') . '</label>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="day"><b>' . T_('Birthday') . '</b></label></div> 
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
        while ($y <= date('Y')) {
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
                </div>
                <p>
                    <input type="hidden" id="url" name="url" value="' . cleanOutput($url) . '"/>
                    <input type="submit" id="add-user" name="add-user" value="' . T_('Add') . '"/>
                </p>
            </fieldset>
        </form>';
    }

    /**
     * displayMembersTreeList
     * 
     * Displays the list of members for viewing their family tree
     * 
     * @return void
     */
    function displayMembersTreeList ()
    {
        // Get list of available users
        $sql = "SELECT `id`, `fname`, `lname`
                FROM `fcms_users`
                WHERE `id` != '" . $this->currentUserId . "'
                ORDER BY `lname`, `fname`";
        $result = mysql_query($sql) or displaySQLError(
            'Users Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() < 1) {
            return;
        }

        echo '
        <form action="familytree.php" method="get" id="view_tree_form">
            <p>
                <select name="tree">
                    <option value="' . $this->currentUserId . '">' . T_('View Family Tree for...') . '</option>';

        while($r = mysql_fetch_assoc($result)) {
            echo '
                    <option value="' . $r['id'] . '">' . $r['fname'] . ' ' . $r['lname'] . '</option>';
        }

        echo '
                </select> 
                <input type="submit" value="' . T_('View') . '"/>
            </p>
        </form>';
    }

    /**
     * displayFamilyTreeList
     * 
     * Displays the Family Tree in a list form.  Used mostly for the
     * profile page.
     * 
     * @param  array $spouse
     * @param  array $kids
     * @param  array $parents
     * @param  array $dadParents
     * @param  array $momParents
     * @return void
     */
    function displayFamilyTreeList ($spouse, $kids, $parents, $dadParents, $momParents)
    {
        echo '
            <div id="relationship_list">';

        // Spouse
        if (count($spouse) > 0 ) {
            echo '
                <p><b>' . T_('Spouse') . ':</b> <a href="profile.php?member=' . $spouse['id'] . '" class="u">' . $spouse['fname'] . '</a></p>';
        }

        // Kids
        if (count($kids) > 0) {
            echo '
                <p><b>' . T_('Kids') . ':</b> ';
            foreach ($kids as $kid) {
                echo '<a href="profile.php?member=' . $kid['id'] . '" class="u">' .$kid['fname'] . '</a> ';
            }
                echo '</p>';
        }

        // Parents
        if (!empty($parents)) {
            $dad = '';
            if (isset($parents[0])) {
                $dad = '<a href="profile.php?member=' . $parents[0]['id'] . '" class="u">' .$parents[0]['fname'] . '</a>';
                if (isset($parents[1])) {
                    $dad .= ' and ';
                }
            }
            $mom = '';
            if (isset($parents[1])) {
                $mom = '<a href="profile.php?member=' . $parents[1]['id'] . '" class="u">' .$parents[1]['fname'] . '</a>';
            }
            echo '
                <p><b>' . T_('Parents') . ':</b> ' . $dad . $mom . '</p>';
        }

        // Grandparents
        if (!empty($dadParents) or !empty($momParents)) {
            $grandparents1 = '';
            $grandparents2 = '';
            if (isset($dadParents[0])) {
                $grandparents1 = '<a href="profile.php?member=' . $dadParents[0]['id'] . '" class="u">' .$dadParents[0]['fname'] . '</a>';
                if (isset($dadParents[1])) {
                    $grandparents1 .= ' and ';
                }
            }
            if (isset($dadParents[1])) {
                $grandparents1 .= '<a href="profile.php?member=' . $dadParents[1]['id'] . '" class="u">' .$dadParents[1]['fname'] . '</a>';
            }
            if (isset($momParents[0]) or isset($momParents[1])) {
                $grandparents1 .= '<br/>';
            }
            if (isset($momParents[0])) {
                $grandparents2 = '<a href="profile.php?member=' . $momParents[0]['id'] . '" class="u">' .$momParents[0]['fname'] . '</a>';
                if (isset($momParents[1])) {
                    $grandparents2 .= ' and ';
                }
            }
            if (isset($momParents[1])) {
                $grandparents2 .= '<a href="profile.php?member=' . $momParents[1]['id'] . '" class="u">' .$momParents[1]['fname'] . '</a>';
            }
            echo '
                <p><b>' . T_('Grandparents') . ':</b> ' . $grandparents1 . $grandparents2 . '</p>';
        }

        echo '
            </div>';
    }

    /**
     * displayFamilyTreeEditList
     * 
     * Displays the Family Tree in an editable list form.  Used mostly for
     * removing members from the current tree.
     * 
     * @param   int     $id         the id of the user's who tree we are editing
     * @param   array   $spouse
     * @param   array   $kids
     * @param   array   $parents
     * @param   array   $dadParents
     * @param   array   $momParents
     * @return  void
     */
    function displayFamilyTreeEditList ($id, $spouse, $kids, $parents, $dadParents, $momParents)
    {
        // Must have admin rights or edit your own tree
        if (checkAccess($this->currentUserId) > 2 && $id != $this->currentUserId) {
            echo '
                <div class="error-alert">' . T_('You do NOT have access to perform this action.') . '</div>';
            return;
        }

        // Build a list of ids
        $ids = array();
        if (count($spouse) > 0) {
            $ids[] = $spouse['id'];
        }
        if (count($kids) > 0) {
            foreach ($kids as $kid) {
                $ids[] = $kid['id'];
            }
        }
        if (count($parents) > 0) {
            foreach ($parents as $parent) {
                $ids[] = $parent['id'];
            }
        }
        if (count($dadParents) > 0) {
            foreach ($dadParents as $parent) {
                $ids[] = $parent['id'];
            }
        }
        if (count($momParents) > 0) {
            foreach ($momParents as $parent) {
                $ids[] = $parent['id'];
            }
        }

        // Get list of relationships that can be deleted
        $sql = "SELECT `id`, `user`, `relationship`, `rel_user`
                FROM `fcms_relationship`
                WHERE `user` IN ('".implode("','", $ids)."')
                OR `rel_user` IN ('".implode("','", $ids)."')";
        if (!$this->db->query($sql)) {
            displaySQLError('Relationship Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return;
        }
        $delete = array();
        while ($r = $this->db->get_row()) {
            if ($r['relationship'] == 'WIFE' || $r['relationship'] == 'HUSB') {
                $delete[$r['rel_user']]['spouse'] = $r['user'];
            } else {
                $delete[$r['user']]['kids'][] = $r['rel_user'];
                $delete[$r['rel_user']]['parents'][] = $r['user'];
            }
        }

        echo '
                <table class="sortable">
                    <thead>
                        <tr>
                            <th>' . T_('Name') . '</th>
                            <th>' . T_('Relationship') . '</th>
                            <th>' . T_('Remove') . '</th>
                        </tr>
                    </thead>
                    <tbody>';
        // Spouse
        if (count($spouse) > 0 ) {
            $del = '<a href="?remove='.$spouse['id'].'">' . T_('Remove') . '</a>';
            // Can't delete user if they have both parents and children
            if (   isset($delete[$spouse['id']]['kids'])
                && isset($delete[$spouse['id']]['parents'])
            ) {
                $del = '';
            }

            echo '
                        <tr>
                            <td>'.$spouse['fname'].'</td>
                            <td>'.T_('Spouse').'</td>
                            <td>'.$del.'</td>
                        </tr>';
        }

        // Kids
        if (count($kids) > 0) {
            foreach ($kids as $kid) {
                $del = '<a href="?remove='.$kid['id'].'">' . T_('Remove') . '</a>';
                // Can't delete user if they have both parents and children
                if (   isset($delete[$kid['id']]['kids'])
                    && isset($delete[$kid['id']]['parents'])
                ) {
                    $del = '';
                }

                echo '
                        <tr>
                            <td>'.$kid['fname'].'</td>
                            <td>'.T_('Child').'</td>
                            <td>'.$del.'</a></td>
                        </tr>';
            }
        }

        // Parents
        if (!empty($parents)) {
            if (isset($parents[0])) {
                $del = '<a href="?remove='.$parents[0]['id'].'">' . T_('Remove') . '</a>';
                // Can't delete user if they have both parents and children
                if (   isset($delete[$parents[0]['id']]['kids'])
                    && isset($delete[$parents[0]['id']]['parents'])
                ) {
                    $del = '';
                }

                echo '
                        <tr>
                            <td>'.$parents[0]['fname'].'</td>
                            <td>'.T_('Father').'</td>
                            <td>'.$del.'</td>
                        </tr>';
            }
            if (isset($parents[1])) {
                $del = '<a href="?remove='.$parents[1]['id'].'">' . T_('Remove') . '</a>';
                // Can't delete user if they have both parents and children
                if (   isset($delete[$parents[1]['id']]['kids'])
                    && isset($delete[$parents[1]['id']]['parents'])
                ) {
                    $del = '';
                }

                echo '
                        <tr>
                            <td>'.$parents[1]['fname'].'</td>
                            <td>'.T_('Mother').'</td>
                            <td>'.$del.'</td>
                        </tr>';
            }
        }

        // Grandparents
        if (!empty($dadParents) or !empty($momParents)) {
            if (isset($dadParents[0])) {
                $del = '<a href="?remove='.$dadParents[0]['id'].'">' . T_('Remove') . '</a>';
                // Can't delete user if they have both parents and children
                if (   isset($delete[$dadParents[0]['id']]['kids'])
                    && isset($delete[$dadParents[0]['id']]['parents'])
                ) {
                    $del = '';
                }

                echo '
                        <tr>
                            <td>'.$dadParents[0]['fname'].'</td>
                            <td>'.T_('Grandfather').'</td>
                            <td>'.$del.'</td>
                        </tr>';
            }
            if (isset($dadParents[1])) {
                $del = '<a href="?remove='.$dadParents[1]['id'].'">' . T_('Remove') . '</a>';
                // Can't delete user if they have both parents and children
                if (   isset($delete[$dadParents[1]['id']]['kids'])
                    && isset($delete[$dadParents[1]['id']]['parents'])
                ) {
                    $del = '';
                }

                echo '
                        <tr>
                            <td>'.$dadParents[1]['fname'].'</td>
                            <td>'.T_('Grandmother').'</td>
                            <td>'.$del.'</td>
                        </tr>';
            }
            if (isset($momParents[0])) {
                $del = '<a href="?remove='.$momParents[0]['id'].'">' . T_('Remove') . '</a>';
                // Can't delete user if they have both parents and children
                if (   isset($delete[$momParents[0]['id']]['kids'])
                    && isset($delete[$momParents[0]['id']]['parents'])
                ) {
                    $del = '';
                }

                echo '
                        <tr>
                            <td>'.$momParents[0]['fname'].'</td>
                            <td>'.T_('Grandfather').'</td>
                            <td>'.$del.'</td>
                        </tr>';
            }
            if (isset($momParents[1])) {
                $del = '<a href="?remove='.$momParents[1]['id'].'">' . T_('Remove') . '</a>';
                // Can't delete user if they have both parents and children
                if (   isset($delete[$momParents[1]['id']]['kids'])
                    && isset($delete[$momParents[1]['id']]['parents'])
                ) {
                    $del = '';
                }

                echo '
                        <tr>
                            <td>'.$momParents[1]['fname'].'</td>
                            <td>'.T_('Grandmother').'</td>
                            <td>'.$del.'</td>
                        </tr>';
            }
        }

        echo '
                    </tbody>
                </table>';
    }

    /**
     * addSpouse 
     * 
     * @param int    $user 
     * @param string $relationship 
     * @param int    $rel_user 
     * 
     * @return boolean
     */
    function addSpouse ($user, $relationship, $rel_user)
    {
        $user         = cleanInput($user, 'int');
        $relationship = cleanInput($relationship);
        $rel_user     = cleanInput($rel_user, 'int');

        $opposite_relationship = ($relationship == 'WIFE') ? 'HUSB' : 'WIFE';

        // Insert relationships for both users
        $sql = "INSERT INTO `fcms_relationship` (
                    `user`, `relationship`, `rel_user`
                ) 
                VALUES 
                    ('$user', '$relationship', '$rel_user'),
                    ('$rel_user', '$opposite_relationship', '$user')";
        if (!mysql_query($sql)) {
            displaySQLError('Relationship Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }
    }

    /**
     * addChild
     * 
     * Adds a child relationship for the given user.
     * 
     * Also checks both user and rel_user to see if this relationship creates others.
     * 
     * user
     *
     *      If user already has a HUSB/WIFE, then create a CHIL relationship with rel_user.
     *
     * rel_user
     *
     *      If rel_user already has a parent, then create a HUSB/WIFE relationship for user.
     * 
     * @param int    $user 
     * @param string $relationship 
     * @param int    $rel_user 
     * 
     * @return boolean
     */
    function addChild ($user, $relationship, $rel_user)
    {
        $user         = cleanInput($user, 'int');
        $relationship = cleanInput($relationship);
        $rel_user     = cleanInput($rel_user, 'int');

        // Insert child relationship
        $sql = "INSERT INTO `fcms_relationship` (
                    `user`, `relationship`, `rel_user`
                ) 
                VALUES (
                    '$user', '$relationship', '$rel_user'
                )";
        if (!$this->db->query($sql)) {
            displaySQLError('Relationship Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }

        // Get wife/husb of user
        $sql = "SELECT r.`rel_user`, u.`sex`
                FROM `fcms_relationship` AS r, `fcms_users` AS u
                WHERE r.`user` = '$user'
                AND r.`relationship` IN ('WIFE', 'HUSB')
                AND r.`rel_user` = u.`id`";
        if (!$this->db->query($sql)) {
            displaySQLError('Relationship Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }
        // Make a child relationship with the users husb/wife
        if ($this->db->count_rows() == 1) {
            $row = $this->db->get_row();
            $sql = "INSERT INTO `fcms_relationship` (
                        `user`, `relationship`, `rel_user`
                    ) 
                    VALUES (
                        '".$row['rel_user']."', '$relationship', '$rel_user'
                    )";
            if (!$this->db->query($sql)) {
                displaySQLError('Relationship Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
                return false;
            }
        }

        // Get other parent of rel_user
        $sql = "SELECT r.`user`, u.`sex`
                FROM `fcms_relationship` AS r, `fcms_users` AS u
                WHERE `user` = (
                    SELECT `user`
                    FROM `fcms_relationship`
                    WHERE `rel_user` = '$rel_user'
                    AND `user` != '$user'
                    AND `relationship` = 'CHIL'
                )
                AND r.`user` = u.`id`";
        if (!$this->db->query($sql)) {
            displaySQLError('Relationship Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }
        // If user has another parent already, make them HUSB/WIFE
        if ($this->db->count_rows() == 1) {
            $row = $this->db->get_row();
            $spouse_relationship = ($row['sex'] == 'M') ? 'WIFE' : 'HUSB';
            $this->addSpouse($row['user'], $spouse_relationship, $user);
        }

    }

}
