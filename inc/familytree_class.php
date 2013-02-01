<?php
/**
 * FamilyTree 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class FamilyTree
{
    var $fcmsError;
    var $fcmsDatabase;
    var $fcmsUser;

    /**
     * FamilyTree
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase
     * @param object $fcmsUser 
     *
     * @return void
     */
    function FamilyTree ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError       = $fcmsError;
        $this->fcmsDatabase    = $fcmsDatabase;
        $this->fcmsUser        = $fcmsUser;
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
     * 
     * @return  void
     */
    function displayFamilyTree ($id, $type = 'tree')
    {
        $id = (int)$id;

        $valid_types = array('tree', 'list', 'list_edit');
        if (!in_array($type, $valid_types))
        {
            echo '
            <div class="error-alert">'.T_('Invalid Display Type').'</div>';

            return;
        }

        // Get info for user
        $sql = "SELECT `id`, `fname`, `mname`, `lname`, `sex`, `avatar`, `dob_year`, `dob_month`, `dob_day`,
                    `dod_year`, `dod_month`, `dod_day`, `password`
                FROM `fcms_users` 
                WHERE `id` = ?
                LIMIT 1";

        $user = $this->fcmsDatabase->getRow($sql, $id);
        if ($user === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        if (count($user) <= 0)
        {
            echo '<div class="error-alert">'.T_('Could not find user.').'</div>';
            return;
        }

        $user['fname'] = cleanOutput($user['fname']);
        $user['mname'] = cleanOutput($user['mname']);
        $user['lname'] = cleanOutput($user['lname']);

        $user['nonmember'] = ($user['password'] == 'NONMEMBER' ? true : false);

        // Get spouse and kids for user
        $sql = "SELECT u.`id`, u.`fname`, u.`mname`, u.`lname`, u.`avatar`, r.`relationship`, r.`rel_user`, u.`password`
                FROM `fcms_relationship` AS r, `fcms_users` AS u 
                WHERE `user` = ? 
                AND r.`rel_user` = u.`id`";

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        $spouse = array();
        $kids   = array();

        foreach ($rows as $row)
        {
            if ($row['relationship'] == 'WIFE' or $row['relationship'] == 'HUSB')
            {
                $spouse = array(
                    'id'        => $row['id'], 
                    'fname'     => cleanOutput($row['fname']), 
                    'mname'     => cleanOutput($row['mname']), 
                    'lname'     => cleanOutput($row['lname']), 
                    'avatar'    => $row['avatar'],
                    'nonmember' => ($row['password'] == 'NONMEMBER' ? true : false)
                );
            }
            if ($row['relationship'] == 'CHIL')
            {
                $tmp = array(
                    'id'        => $row['id'], 
                    'fname'     => cleanOutput($row['fname']), 
                    'mname'     => cleanOutput($row['mname']), 
                    'lname'     => cleanOutput($row['lname']), 
                    'avatar'    => $row['avatar'],
                    'nonmember' => ($row['password'] == 'NONMEMBER' ? true : false)
                );
                array_push($kids, $tmp);
            }
        }

        // Get user's parents
        $sql = "SELECT u.`id`, u.`fname`, u.`mname`, u.`lname`, u.`maiden`, u.`avatar`, r.`relationship`, r.`rel_user`, 
                    u.`dob_year`, u.`dob_month`, u.`dob_day`, u.`dod_year`, u.`dod_month`, u.`dod_day`, u.`sex`, u.`password`
                FROM `fcms_relationship` AS r, `fcms_users` AS u 
                WHERE `rel_user` = ? 
                AND r.`user` = u.`id`
                AND r.`relationship` = 'CHIL'
                LIMIT 2";

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        $parents = array();
        $dad = '';
        $mom = '';

        if (count($rows) > 0)
        {
            foreach ($rows as $row)
            {
                $tmp = array(
                    'id'        => $row['id'], 
                    'fname'     => cleanOutput($row['fname']), 
                    'mname'     => cleanOutput($row['mname']), 
                    'lname'     => cleanOutput($row['lname']), 
                    'avatar'    => $row['avatar'],
                    'dob_year'  => $row['dob_year'],
                    'dob_month' => $row['dob_month'],
                    'dob_day'   => $row['dob_day'],
                    'dod_year'  => $row['dod_year'],
                    'dod_month' => $row['dod_month'],
                    'dod_day'   => $row['dod_day'],
                    'nonmember' => ($row['password'] == 'NONMEMBER' ? true : false)
                );

                // dad
                if (!isset($parents[0]) and $row['sex'] == 'M')
                {
                    $dad        = $row['id'];
                    $parents[0] = $tmp;
                }
                // mom
                else
                {
                    $mom        = $row['id'];
                    $parents[1] = $tmp;
                }
            }
        }

        // get parents for user's parents and wife's parents
        $dadParents = array();
        $momParents = array();

        if (!empty($dad) or !empty($mom))
        {
            $sql = "SELECT u.`id`, u.`fname`, u.`mname`, u.`lname`, u.`maiden`, u.`avatar`, r.`relationship`, r.`rel_user`, 
                        u.`dob_year`, u.`dob_month`, u.`dob_day`, u.`dod_year`, u.`dod_month`, u.`dod_day`, u.`sex`, u.`password`
                    FROM `fcms_relationship` AS r, `fcms_users` AS u ";

            $params = array();

            if ($dad > 0 and $mom > 0)
            {
                $sql     .= "WHERE (`rel_user` = ? OR `rel_user` = ?) ";
                $params[] = $dad;
                $params[] = $mom;
            }
            elseif ($dad > 0)
            {
                $sql     .= "WHERE `rel_user` = ? ";
                $params[] = $dad;
            }
            elseif ($mom > 0)
            {
                $sql     .= "WHERE `rel_user` = ? ";
                $params[] = $mom;
            }

            $sql .="AND r.`user` = u.`id`
                    AND r.`relationship` = 'CHIL'";

            $rows = $this->fcmsDatabase->getRows($sql, $params);
            if ($rows === false)
            {
                $this->fcmsError->displayError();

                return;
            }

            if (count($rows) > 0)
            {
                foreach ($rows as $row)
                {
                    $tmp = array(
                        'id'        => $row['id'], 
                        'fname'     => cleanOutput($row['fname']), 
                        'mname'     => cleanOutput($row['mname']), 
                        'lname'     => cleanOutput($row['lname']), 
                        'avatar'    => $row['avatar'],
                        'dob_year'  => $row['dob_year'],
                        'dob_month' => $row['dob_month'],
                        'dob_day'   => $row['dob_day'],
                        'dod_year'  => $row['dod_year'],
                        'dod_month' => $row['dod_month'],
                        'dod_day'   => $row['dod_day'],
                        'nonmember' => ($row['password'] == 'NONMEMBER' ? true : false)
                    );

                    // dad's parents
                    if ($row['rel_user'] == $dad)
                    {
                        // grandpa
                        if (!isset($dadParents[0]) and $row['sex'] == 'M')
                        {
                            $dadParents[0] = $tmp;
                        }
                        // grandma
                        else
                        {
                            $dadParents[1] = $tmp;
                        }
                    }
                    // mom's parents
                    else
                    {
                        // grandpa
                        if (!isset($momParents[0]) and $row['sex'] == 'M')
                        {
                            $momParents[0] = $tmp;
                        }
                        // grandma
                        else
                        {
                            $momParents[1] = $tmp;
                        }
                    }
                }
            }
        }

        // Display Tree
        if ($type == 'tree')
        {
            $this->displayMembersTreeList();

            echo '
            <div id="tree">';

            $this->displaySpouseKidsColumn($user, $spouse, $kids);
            $this->displayParentsColumn($id, $parents);
            $this->displayGrandparentsColumn($dad, $mom, $dadParents, $momParents);

            echo '
            </div>
            <div id="tree_toolbar">
                <a href="?remove=user&amp;id='.$id.'">'.T_('Remove Relationship').'</a>
            </div>';

        }
        // Display family list (used in profile)
        elseif ($type == 'list')
        {
            $this->displayFamilyTreeList($spouse, $kids, $parents, $dadParents, $momParents);
        }
        // Display edit list
        elseif ($type == 'list_edit')
        {
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
     * 
     * @return void
     */
    function displaySpouseKidsColumn ($user, $spouse, $kids)
    {
        // current user
        echo '
                <div class="column first">
                    <div class="leaf me">
                        <img src="'.getCurrentAvatar($user['id']).'" alt="'.$user['fname'].' '.$user['lname'].'"/>
                        <b>'.$user['fname'].' '.$user['lname'].'</b>
                        <span>'.$user['dob_year'].' - '.$user['dod_year'].'</span>
                        <a href="?edit='.$user['id'].'" class="edit">'.T_('Edit').'</a>
                    </div>';

        // spouse
        if (count($spouse) > 0)
        {
            $edit = '';

            // If this is your spouse, or the spouse is a nonmember, you can edit
            if ($spouse['nonmember'] or $user['id'] == $this->fcmsUser->id)
            {
                $edit = '<a href="?edit='.$spouse['id'].'" class="edit">'.T_('Edit').'</a>';
            }

            echo '
                    <div class="leaf spouse">
                        <img src="'.getCurrentAvatar($spouse['id']).'" alt="'.$spouse['fname'].' '.$spouse['lname'].'"/>
                        <a href="?tree='.$spouse['id'].'">'.$spouse['fname'].' ' .$spouse['lname'].'</a>
                        '.$edit.'
                    </div>';
        }
        else
        {
            $add = ($user['sex'] == 'M') ? 'wife' : 'husb';

            echo '
                    <a href="?add='.$add.'&amp;user='.$user['id'].'" class="leaf spouse">
                        <b>'.T_('Add Spouse').'</b>
                    </a>';
        }

        // kids
        if (count($kids) > 0)
        {
            foreach ($kids as $kid)
            {
                $edit = '';

                // If this is your kid, or the kid is a nonmember, you can edit
                if ($kid['nonmember'] or $user['id'] == $this->fcmsUser->id)
                {
                    $edit = '<a href="?edit='.$kid['id'].'" class="edit">'.T_('Edit').'</a>';
                }

                echo '
                    <div class="leaf child">
                        <img src="'.getCurrentAvatar($kid['id']).'" alt="'.$kid['fname'].' '.$kid['lname'].'"/>
                        <a href="?tree='.$kid['id'].'">'.$kid['fname'].' '.$kid['lname'].'</a>
                        '.$edit.'
                    </div>';
            }
        }

        // close column
        echo '
                    <a href="?add=child&amp;user='.$user['id'].'" class="leaf child">
                        '.T_('Add Child').'
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
        if (isset($parents[0]))
        {
            $pid       = $parents[0]['id'];
            $name      = $parents[0]['fname'].' '.$parents[0]['mname'].' '.$parents[0]['lname'];
            $bday      = $parents[0]['dob_year'];
            $dday      = $parents[0]['dod_year'];
            $nonmember = $parents[0]['nonmember'];

            $this->displayLeaf('dad', $pid, $name, $bday, $dday, $nonmember);
        }
        else
        {
            $this->displayAddLeaf('dad', $id, T_('Add Father'));
        }

        // mom
        if (isset($parents[1]))
        {
            $maiden = !empty($parents[1]['maiden']) ? '('.$parents[1]['maiden'].')' : '';

            $pid       = $parents[1]['id'];
            $name      = $parents[1]['fname'].' '.$parents[1]['mname'].' '.$parents[1]['lname'].' '.$maiden;
            $bday      = $parents[1]['dob_year'];
            $dday      = $parents[1]['dod_year'];
            $nonmember = $parents[1]['nonmember'];

            $this->displayLeaf('mom', $pid, $name, $bday, $dday, $nonmember);
        }
        else
        {
            $this->displayAddLeaf('mom', $id, T_('Add Mother'));
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
        if (isset($dadParents[0]))
        {
            $pid       = $dadParents[0]['id'];
            $name      = $dadParents[0]['fname'].' '.$dadParents[0]['mname'].' '.$dadParents[0]['lname'];
            $bday      = $dadParents[0]['dob_year'];
            $dday      = $dadParents[0]['dod_year'];
            $nonmember = $dadParents[0]['nonmember'];

            $this->displayLeaf('grandpa', $pid, $name, $bday, $dday, $nonmember);
        }
        else
        {
            // can only add a grandfather if they have a father added
            if (!empty($dad))
            {
                $this->displayAddLeaf('grandpa', $dad, T_('Add Grandfather'));
            }
            else
            {
                echo '
                    <div class="leaf grandpa unknown"></div>';
            }
        }

        // dad's mom
        if (isset($dadParents[1]))
        {
            $maiden = !empty($dadParents[1]['maiden']) ? '('.$dadParents[1]['maiden'].')' : '';

            $pid       = $dadParents[1]['id'];
            $name      = $dadParents[1]['fname'].' '.$dadParents[1]['mname'].' '.$dadParents[1]['lname'].' '.$maiden;
            $bday      = $dadParents[1]['dob_year'];
            $dday      = $dadParents[1]['dod_year'];
            $nonmember = $dadParents[1]['nonmember'];

            $this->displayLeaf('grandma', $pid, $name, $bday, $dday, $nonmember);
        }
        else
        {
            // can only add a grandmother if they have a father added
            if (!empty($dad))
            {
                $this->displayAddLeaf('grandma', $dad, T_('Add Grandmother'));
            }
            else
            {
                echo '
                    <div class="leaf grandma unknown"></div>';
            }
        }

        // mom's dad
        if (isset($momParents[0]))
        {
            $pid       = $momParents[0]['id'];
            $name      = $momParents[0]['fname'].' '.$momParents[0]['mname'].' '.$momParents[0]['lname'];
            $bday      = $momParents[0]['dob_year'];
            $dday      = $momParents[0]['dod_year'];
            $nonmember = $momParents[0]['nonmember'];

            $this->displayLeaf('grandpa', $pid, $name, $bday, $dday, $nonmember);
        }
        else
        {
            // can only add a grandfather if they have a mother added
            if (!empty($mom))
            {
                $this->displayAddLeaf('grandpa', $mom, T_('Add Grandfather'));
            }
            else
            {
                echo '
                    <div class="leaf grandpa unknown"></div>';
            }
        }

        // mom's mom
        if (isset($momParents[1]))
        {
            $maiden = !empty($momParents[1]['maiden']) ? '('.$momParents[1]['maiden'].')' : '';

            $pid       = $momParents[1]['id'];
            $name      = $momParents[1]['fname'].' '.$momParents[1]['mname'].' '.$momParents[1]['lname'].' '.$maiden;
            $bday      = $momParents[1]['dob_year'];
            $dday      = $momParents[1]['dod_year'];
            $nonmember = $momParents[1]['nonmember'];

            $this->displayLeaf('grandma', $pid, $name, $bday, $dday, $nonmember);
        }
        else
        {
            // can only add a grandmother if they have a mother added
            if (!empty($mom))
            {
                $this->displayAddLeaf('grandma', $mom, T_('Add Grandmother'));
            }
            else
            {
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
     * @param   int     $userid the child's user id
     * 
     * @return  void
     */
    function displayAddParentForm ($type, $userid)
    {
        // Can only add a mom or dad
        if ($type !== 'dad' and $type !== 'mom')
        {
            echo '<div class="error-alert">'.T_('Invalid parent type.').'</div>';
            return;
        }

        $parents     = array();
        $displayName = getUserDisplayname($userid, 2);

        if ($type == 'dad')
        {
            $sex    = 'M';
            $legend = sprintf(T_('Add Father for %s'), $displayName);
        }
        else
        {
            $sex    = 'F';
            $legend = sprintf(T_('Add Mother for %s'), $displayName);
        }


        // Get list of possible users
        $sql = "SELECT `id`, `fname`, `lname`
                FROM `fcms_users` 
                WHERE `id` != ?
                AND `id` NOT IN (
                    SELECT `user`
                    FROM `fcms_relationship`
                    WHERE `rel_user` = ?
                )
                AND `id` NOT IN (
                    SELECT `user`
                    FROM `fcms_relationship`
                    WHERE `rel_user` IN (
                        SELECT `user`
                        FROM `fcms_relationship`
                        WHERE `rel_user` = ?
                  )
                )
                ORDER BY `lname`, `fname`";

        $rows = $this->fcmsDatabase->getRows($sql, array($userid, $userid, $userid));
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        // we have at least one possible parent
        if (count($rows) >= 1)
        {
            foreach ($rows as $r)
            {
                $possibleParents[$r['id']] = $r['fname'].' '.$r['lname'];
            }

            // get children
            $children = $this->getChildren($userid);

            // get children of children (grandchildren)
            foreach ($children as $id => $name)
            {
                $grandchildren = $this->getChildren($id);
                $children += $grandchildren;

                // get great-grandchildren
                foreach ($grandchildren as $i => $n)
                {
                    $greatchildren = $this->getChildren($i);
                    $children += $greatchildren;
                }
            }

            // Remove children/grandchildren/great-grandchildren from list of possible parents
            foreach ($possibleParents as $id => $name)
            {
                if (isset($children[$id]))
                {
                    unset($possibleParents[$id]);
                }
            }
            $parents = $possibleParents;
        }

        if (count($parents) < 1)
        {
            echo '
        <fieldset>
            <legend><span>'.$legend.'</span></legend>
            <p><a class="u" href="?create=user&amp;type='.$type.'&amp;id='.$userid.'">'.T_('Add New Parent').'</a></p>
        </fieldset>';
            return;
        }

        echo '
        <form action="familytree.php" method="post">
            <fieldset>
                <legend><span>'.$legend.'</span></legend>
                <div class="field-row">
                    <div class="field-label"><label for="user"><b>'.T_('Parent').'</b></label></div>
                    <div class="field-widget">
                        <select name="user">
                            '.buildHtmlSelectOptions($parents, '-1').'
                        </select><br/>
                        <p><a class="u" href="?create=user&amp;type='.$type.'&amp;id='.$userid.'">'.T_('Add Parent Not Listed Above').'</a></p>
                    </div>
                </div>
                <p>
                    <input type="hidden" id="relationship" name="relationship" value="CHIL"/>
                    <input type="hidden" id="rel_user" name="rel_user" value="'.$userid.'"/>
                    <input class="sub1" type="submit" id="add-leaf" name="add-leaf" value="'.T_('Add').'"/> &nbsp;
                    <a href="familytree.php">'.T_('Cancel').'</a>
                </p>
            </fieldset>
        </form>';
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
        if ($type !== 'wife' and $type !== 'husb')
        {
            echo '<div class="error-alert">'.T_('Invalid spouse type.').'</div>';
            return;
        }

        // Get list of available users
        $sql = "SELECT `id`, `fname`, `lname`
                FROM `fcms_users` 
                WHERE `id` != ?
                AND `id` NOT IN (
                  SELECT `rel_user`
                  FROM `fcms_relationship`
                  WHERE `user` = ?
                  AND `relationship` = 'CHIL'
                )
                AND `id` NOT IN (
                  SELECT `user`
                  FROM `fcms_relationship`
                  WHERE `rel_user` = ?
                )
                AND `id` NOT IN (
                  SELECT `user`
                  FROM `fcms_relationship`
                  WHERE `rel_user` IN (
                    SELECT `user`
                    FROM `fcms_relationship`
                    WHERE `rel_user` = ?
                  )
                )
                ORDER BY `lname`, `fname`";

        $params = array($id, $id, $id, $id);

        $rows = $this->fcmsDatabase->getRows($sql, $params);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
        }

        if (count($rows) < 1)
        {
            echo '
        <fieldset>
            <legend><span>'.T_('Add Spouse').'</span></legend>
            <p><a class="u" href="?create=user&amp;type='.$type.'&amp;id='.$id.'">'.T_('Add New Spouse').'</a></p>
        </fieldset>';

        }
        else
        {
            foreach ($rows as $r)
            {
                $spouse[$r['id']] = $r['fname'].' '.$r['lname'];
            }
            
            echo '
        <form action="familytree.php" method="post">
            <fieldset>
                <legend><span>'.T_('Add Spouse').'</span></legend>
                <div class="field-row">
                    <div class="field-label"><label for="user"><b>'.T_('Spouse').'</b></label></div>
                    <div class="field-widget">
                        <select name="rel_user">
                            '.buildHtmlSelectOptions($spouse, '-1').'
                        </select><br/>
                        <p><a class="u" href="?create=user&amp;type='.$type.'&amp;id='.$id.'">'.T_('Add Spouse Not Listed Above').'</a></p>
                    </div>
                </div>
                <p>
                    <input type="hidden" id="relationship" name="relationship" value="'.strtoupper($type).'"/>
                    <input type="hidden" id="rel_user" name="user" value="'.$id.'"/>
                    <input class="sub1" type="submit" id="add-leaf" name="add-leaf" value="'.T_('Add').'"/> &nbsp;
                    <a href="familytree.php">'.T_('Cancel').'</a>
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
     * 
     * @return void
     */
    function displayAddChildForm ($userId)
    {
        $displayName = getUserDisplayname($userId, 2);

        // Get list of possible children
        // -- users who are not already a child of someone
        $sql = "SELECT `id`, `fname`, `lname`
                FROM `fcms_users`
                WHERE `id` != ?
                AND `id` NOT IN (
                    SELECT `rel_user` 
                    FROM `fcms_relationship` 
                    WHERE `rel_user` = ?
                )
                AND `id` NOT IN (
                  SELECT `rel_user`
                  FROM `fcms_relationship`
                  WHERE `rel_user` IN (
                    SELECT `user`
                    FROM `fcms_relationship`
                    WHERE `rel_user` = ?
                  )
                )
                ORDER BY `lname`, `fname`";

        $params = array($userId, $userId, $userId);

        $rows = $this->fcmsDatabase->getRows($sql, $params);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        if (count($rows) < 1)
        {
            echo '
        <fieldset>
            <legend><span>'.T_('Add Child').'</span></legend>
            <p><a class="u" href="?create=user&amp;type=child&amp;id='.$userId.'">'.T_('Add New Child').'</a></p>
        </fieldset>';
            return;
        }

        foreach ($rows as $r)
        {
            $children[$r['id']] = $r['fname'].' '.$r['lname'];
        }

        echo '
        <form action="familytree.php" method="post">
            <fieldset>
                <legend><span>'.sprintf(T_('Add Child for %s'), $displayName).'</span></legend>
                <div class="field-row">
                    <div class="field-label"><label for="rel_user"><b>'.T_('Child').'</b></label></div>
                    <div class="field-widget">
                        <select name="rel_user">
                            '.buildHtmlSelectOptions($children, '-1').'
                        </select><br/>
                        <p><a class="u" href="?create=user&amp;type=child&amp;id='.$userId.'">'.T_('Add Child Not Listed Above').'</a></p>
                    </div>
                </div>
                <p>
                    <input type="hidden" id="relationship" name="relationship" value="CHIL"/>
                    <input type="hidden" id="user" name="user" value="'.(int)$userId.'"/>
                    <input class="sub1" type="submit" id="add-leaf" name="add-leaf" value="'.T_('Add').'"/> &nbsp;
                    <a href="familytree.php">'.T_('Cancel').'</a>
                </p>
            </fieldset>
        </form>';
    }

    /**
     * displayCreateUserForm
     * 
     * Displays the form for creating a new user to be added to the family tree
     * 
     * @param string $type mom or dad
     * @param int    $id   id of user your adding a relationship for
     * 
     * @return void
     */
    function displayCreateUserForm ($type, $id)
    {
        $id = (int)$id;

        $displayname = getUserDisplayName($id, 2);

        switch ($type)
        {
            case 'dad':
                $sex    = 'M';
                $legend = sprintf(T_('Add New Father for %s'), $displayname);
                break;

            case 'mom':
                $sex    = 'F';
                $legend = sprintf(T_('Add New Mother for %s'), $displayname);
                break;

            case 'husb':
                $sex    = 'M';
                $legend = sprintf(T_('Add New Spouse for %s'), $displayname);
                break;

            case 'wife':
                $sex    = 'F';
                $legend = sprintf(T_('Add New Spouse for %s'), $displayname);
                break;

            case 'child':
                $sex    = '?';
                $legend = sprintf(T_('Add New Child for %s'), $displayname);
                break;

            default:
                echo '
            <div class="error-alert">'.T_('Invalid Display Type').'</div>';
                return;
        }

        $dayList = array();
        $i = 1;
        while ($i <= 31) {
            $dayList[$i] = $i;
            $i++;
        }

        $monthList = array();
        $i = 1;
        while ($i <= 12) {
            $monthList[$i] = getMonthAbbr($i);
            $i++;
        }

        echo '
        <form action="familytree.php" method="post">
            <fieldset class="relationship-form">
                <legend><span>'.$legend.'</span></legend>
                <div class="cols">
                    <div>
                        <label for="fname"><b>'.T_('First Name').'</b></label><br/>
                        <input class="frm_text" type="text" name="fname" id="fname" size="25"/>
                    </div>
                    <div>
                        <label for="mname"><b>'.T_('Middle Name').'</b></label><br/>
                        <input class="frm_text" type="text" name="mname" id="mname" size="25"/>
                    </div>
                    <div>
                        <label for="lname"><b>'.T_('Last Name').'</b></label><br/>
                        <input class="frm_text" type="text" name="lname" id="lname" size="25"/>
                    </div>
                </div>';

        // don't show maiden name unless it's needed
        if ($type == 'wife' || $type == 'mom' || $type == 'chil')
        {
            echo '
                <p class="maiden-name">
                    <label for="maiden"><b>'.T_('Maiden Name').'</b></label><br/>
                    <input class="frm_text" type="text" name="maiden" id="maiden" size="25"/>
                </p>';
        }

        // We don't know the sex of the child, but we do for all other relationships
        if ($type == 'child')
        {
            echo '
                <p>
                    <label><b>'.T_('Sex').'</b></label><br/>
                    <select id="sex" name="sex">
                        '.buildHtmlSelectOptions(array('M' => T_('Male'), 'F' => T_('Female')), '-1').'
                    </select>
                </p>';
        }
        else
        {
            echo '<div><input type="hidden" id="sex" name="sex" value="'.$sex.'"/></div>';
        }

        echo '
                <p id="living_deceased" style="display:none;">
                    <label for="living_option" class="radio_label">
                        <input type="radio" id="living_option" name="living_deceased_options" checked="checked" value="1"/>
                        '.T_('Living').'
                    </label>
                    &nbsp; &nbsp; &nbsp;
                    <label for="deceased_option" class="radio_label">
                        <input type="radio" id="deceased_option" name="living_deceased_options" value="1"/>
                        '.T_('Deceased').'
                    </label>
                </p>
                <div class="dob_dod">
                    <div class="half">
                        <label for="day"><b>'.T_('Birthday').'</b></label><br/>
                        <select id="bday" name="bday">
                            <option value="">'.T_('Day').'</option>
                            '.buildHtmlSelectOptions($dayList, "").'
                        </select>
                        <select id="bmonth" name="bmonth">
                            <option value="">'.T_('Month').'</option>
                            '.buildHtmlSelectOptions($monthList, "").'
                        </select>
                        <input class="frm_text" type="text" name="byear" id="byear" size="5" maxlength="4" placeholder="'.T_('Year').'"/>
                    </div>
                    <div id="deceased" class="half">
                        <label for="day"><b>'.T_('Date Deceased').'</b></label><br/>
                        <select id="dday" name="dday">
                            <option value="">'.T_('Day').'</option>
                            '.buildHtmlSelectOptions($dayList, "").'
                        </select>
                        <select id="dmonth" name="dmonth">
                            <option value="">'.T_('Month').'</option>
                            '.buildHtmlSelectOptions($monthList, "").'
                        </select>
                        <input class="frm_text" type="text" name="dyear" id="dyear" size="5" maxlength="4" placeholder="'.T_('Year').'"/>
                    </div>
                </div>
                <script type="text/javascript">
                    var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                    ffname.add(Validate.Presence, {failureMessage: ""});

                    var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                    flname.add(Validate.Presence, {failureMessage: ""});

                    var fbyear = new LiveValidation(\'byear\', { onlyOnSubmit: true });
                    fbyear.add(Validate.Numericality, { minimum: 1000, maximum: 9999 } );

                    var fdyear = new LiveValidation(\'dyear\', { onlyOnSubmit: true });
                    fdyear.add(Validate.Numericality, { minimum: 1000, maximum: 9999 } );
                </script>
                <p>
                    <input type="hidden" id="id" name="id" value="'.$id.'"/>
                    <input type="hidden" id="type" name="type" value="'.cleanOutput($type).'"/>
                    <input class="sub1" type="submit" id="add-user" name="add-user" value="'.T_('Add').'"/> &nbsp;
                    <a href="familytree.php">'.T_('Cancel').'</a>
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
                WHERE `id` != ?
                ORDER BY `lname`, `fname`";

        $rows = $this->fcmsDatabase->getRows($sql, $this->fcmsUser->id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        if (count($rows) < 1)
        {
            return;
        }

        echo '
        <form action="familytree.php" method="get" id="view_tree_form">
            <p>
                <select name="tree">
                    <option value="'.$this->fcmsUser->id.'">'.T_('View Family Tree for...').'</option>';

        foreach ($rows as $r)
        {
            echo '
                    <option value="'.$r['id'].'">'.cleanOutput($r['fname']).' '.cleanOutput($r['lname']).'</option>';
        }

        echo '
                </select> 
                <input type="submit" value="'.T_('View').'"/>
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
                <p><b>'.T_('Spouse').':</b> <a href="profile.php?member='.$spouse['id'].'" class="u">'.$spouse['fname'].'</a></p>';
        }

        // Kids
        if (count($kids) > 0) {
            echo '
                <p><b>'.T_('Kids').':</b> ';
            foreach ($kids as $kid) {
                echo '<a href="profile.php?member='.$kid['id'].'" class="u">' .$kid['fname'].'</a> ';
            }
                echo '</p>';
        }

        // Parents
        if (!empty($parents)) {
            $dad = '';
            if (isset($parents[0])) {
                $dad = '<a href="profile.php?member='.$parents[0]['id'].'" class="u">' .$parents[0]['fname'].'</a>';
                if (isset($parents[1])) {
                    $dad .= ' and ';
                }
            }
            $mom = '';
            if (isset($parents[1])) {
                $mom = '<a href="profile.php?member='.$parents[1]['id'].'" class="u">' .$parents[1]['fname'].'</a>';
            }
            echo '
                <p><b>'.T_('Parents').':</b> '.$dad.$mom.'</p>';
        }

        // Grandparents
        if (!empty($dadParents) or !empty($momParents)) {
            $grandparents1 = '';
            $grandparents2 = '';
            if (isset($dadParents[0])) {
                $grandparents1 = '<a href="profile.php?member='.$dadParents[0]['id'].'" class="u">' .$dadParents[0]['fname'].'</a>';
                if (isset($dadParents[1])) {
                    $grandparents1 .= ' and ';
                }
            }
            if (isset($dadParents[1])) {
                $grandparents1 .= '<a href="profile.php?member='.$dadParents[1]['id'].'" class="u">' .$dadParents[1]['fname'].'</a>';
            }
            if (isset($momParents[0]) or isset($momParents[1])) {
                $grandparents1 .= '<br/>';
            }
            if (isset($momParents[0])) {
                $grandparents2 = '<a href="profile.php?member='.$momParents[0]['id'].'" class="u">' .$momParents[0]['fname'].'</a>';
                if (isset($momParents[1])) {
                    $grandparents2 .= ' and ';
                }
            }
            if (isset($momParents[1])) {
                $grandparents2 .= '<a href="profile.php?member='.$momParents[1]['id'].'" class="u">' .$momParents[1]['fname'].'</a>';
            }
            echo '
                <p><b>'.T_('Grandparents').':</b> '.$grandparents1.$grandparents2.'</p>';
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
        if ($this->fcmsUser->access > 2 && $id != $this->fcmsUser->id)
        {
            echo '
                <div class="error-alert">'.T_('You do NOT have access to perform this action.').'</div>';
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

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        $delete = array();

        foreach ($rows as $r)
        {
            if ($r['relationship'] == 'WIFE' || $r['relationship'] == 'HUSB')
            {
                $delete[$r['rel_user']]['spouse'] = $r['user'];
            }
            else
            {
                $delete[$r['user']]['kids'][] = $r['rel_user'];
                $delete[$r['rel_user']]['parents'][] = $r['user'];
            }
        }

        echo '
                <table class="sortable">
                    <thead>
                        <tr>
                            <th>'.T_('Name').'</th>
                            <th>'.T_('Relationship').'</th>
                            <th>'.T_('Remove').'</th>
                        </tr>
                    </thead>
                    <tbody>';
        // Spouse
        if (count($spouse) > 0 ) {
            $del = '<a href="?remove='.$spouse['id'].'">'.T_('Remove').'</a>';
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
                $del = '<a href="?remove='.$kid['id'].'">'.T_('Remove').'</a>';
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
                $del = '<a href="?remove='.$parents[0]['id'].'">'.T_('Remove').'</a>';
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
                $del = '<a href="?remove='.$parents[1]['id'].'">'.T_('Remove').'</a>';
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
                $del = '<a href="?remove='.$dadParents[0]['id'].'">'.T_('Remove').'</a>';
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
                $del = '<a href="?remove='.$dadParents[1]['id'].'">'.T_('Remove').'</a>';
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
                $del = '<a href="?remove='.$momParents[0]['id'].'">'.T_('Remove').'</a>';
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
                $del = '<a href="?remove='.$momParents[1]['id'].'">'.T_('Remove').'</a>';
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
                </table>
                <div id="tree_toolbar">
                    <a href="familytree.php">'.T_('Cancel').'</a>
                </div>';
    }

    /**
     * displayEditForm 
     * 
     * @param string  $userid 
     * 
     * @return void
     */
    function displayEditForm ($userid)
    {
        // Get user info
        $sql = "SELECT `id`, `fname`, `mname`, `lname`, `maiden`, `dob_year`, `dob_month`, `dob_day`, `dod_year`, `dod_month`, `dod_day`, `sex`
                FROM `fcms_users`
                WHERE `id` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $userid);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        $dayList   = array();
        $monthList = array();

        $i = 1;
        while ($i <= 31)
        {
            $dayList[$i] = $i;
            $i++;
        }

        $i = 1;
        while ($i <= 12)
        {
            $monthList[$i] = getMonthAbbr($i);
            $i++;
        }

        // Living or Deceased?
        $living   = 'checked="checked"';
        $deceased = '';

        if (!empty($row['dod_year']) || !empty($row['dod_month']) || !empty($row['dod_day']))
        {
            $living   = '';
            $deceased = 'checked="checked"';
        }

        echo '
        <form action="familytree.php" method="post">
            <fieldset class="relationship-form">
                <legend><span>'.T_('Edit').'</span></legend>
                <div style="text-align:right"><a href="?avatar='.$userid.'">'.T_('Add Picture').'</a></div>
                <div class="cols">
                    <div>
                        <label for="fname"><b>'.T_('First Name').'</b></label><br/>
                        <input class="frm_text" type="text" name="fname" id="fname" size="25" value="'.cleanOutput($row['fname']).'"/>
                    </div>
                    <div>
                        <label for="mname"><b>'.T_('Middle Name').'</b></label><br/>
                        <input class="frm_text" type="text" name="mname" id="mname" size="25" value="'.cleanOutput($row['mname']).'"/>
                    </div>
                    <div>
                        <label for="lname"><b>'.T_('Last Name').'</b></label><br/>
                        <input class="frm_text" type="text" name="lname" id="lname" size="25" value="'.cleanOutput($row['lname']).'"/>
                    </div>
                </div>
                <p id="maiden-name" class="maiden-name">
                    <label for="maiden"><b>'.T_('Maiden Name').'</b></label><br/>
                    <input class="frm_text" type="text" name="maiden" id="maiden" size="25" value="'.cleanOutput($row['maiden']).'"/>
                </p>
                <p>
                    <label><b>'.T_('Sex').'</b></label><br/>
                    <select id="sex" name="sex">
                        '.buildHtmlSelectOptions(array('M' => T_('Male'), 'F' => T_('Female')), $row['sex']).'
                    </select>
                </p>
                <p id="living_deceased" style="display:none;">
                    <label for="living_option" class="radio_label">
                        <input type="radio" id="living_option" name="living_deceased_options" '.$living.' value="1"/>
                        '.T_('Living').'
                    </label>
                    &nbsp; &nbsp; &nbsp;
                    <label for="deceased_option" class="radio_label">
                        <input type="radio" id="deceased_option" name="living_deceased_options" '.$deceased.' value="1"/>
                        '.T_('Deceased').'
                    </label>
                </p>
                <div class="dob_dod">
                    <div class="half">
                        <label for="day"><b>'.T_('Birthday').'</b></label><br/>
                        <select id="bday" name="bday">
                            <option value="">'.T_('Day').'</option>
                            '.buildHtmlSelectOptions($dayList, $row['dob_day']).'
                        </select>
                        <select id="bmonth" name="bmonth">
                            <option value="">'.T_('Month').'</option>
                            '.buildHtmlSelectOptions($monthList, $row['dob_month']).'
                        </select>
                        <input class="frm_text" type="text" name="byear" id="byear" size="5" maxlength="4" placeholder="'.T_('Year').'" value="'.$row['dob_year'].'"/>
                    </div>
                    <div id="deceased" class="half">
                        <label for="day"><b>'.T_('Date Deceased').'</b></label><br/>
                        <select id="dday" name="dday">
                            <option value="">'.T_('Day').'</option>
                            '.buildHtmlSelectOptions($dayList, $row['dod_day']).'
                        </select>
                        <select id="dmonth" name="dmonth">
                            <option value="">'.T_('Month').'</option>
                            '.buildHtmlSelectOptions($monthList, $row['dod_month']).'
                        </select>
                        <input class="frm_text" type="text" name="dyear" id="dyear" size="5" maxlength="4" placeholder="'.T_('Year').'" value="'.$row['dod_year'].'"/>
                    </div>
                </div>
                <script type="text/javascript">
                    if ($F("sex") == "M") {
                        $("maiden-name").hide();
                    } else {
                        $("maiden-name").show();
                    }

                    $("sex").observe("change", function() {
                        if ($F("sex") == "M") {
                            $("maiden-name").hide();
                        } else {
                            $("maiden-name").show();
                        }
                    });

                    var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                    ffname.add(Validate.Presence, {failureMessage: ""});

                    var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                    flname.add(Validate.Presence, {failureMessage: ""});

                    var fbyear = new LiveValidation(\'byear\', { onlyOnSubmit: true });
                    fbyear.add(Validate.Numericality, { minimum: 1000, maximum: 9999 } );

                    var fdyear = new LiveValidation(\'dyear\', { onlyOnSubmit: true });
                    fdyear.add(Validate.Numericality, { minimum: 1000, maximum: 9999 } );
                </script>
                <p>
                    <input type="hidden" id="id" name="id" value="'.$userid.'"/>
                    <input class="sub1" type="submit" id="edit-user" name="edit-user" value="'.T_('Edit').'"/> &nbsp;
                    <a href="familytree.php">'.T_('Cancel').'</a>
                </p>
            </fieldset>
        </form>';
    }

    /**
     * displayEditAvatarForm 
     * 
     * @param int $userid 
     * 
     * @return void
     */
    function displayEditAvatarForm ($userid)
    {
        // Get user info
        $sql = "SELECT `id`, `fname`, `lname`, `maiden`, `avatar`, `gravatar`
                FROM `fcms_users`
                WHERE `id` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $userid);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        $form   = '';
        $input  = '';
        $js     = '';
        $submit = 'submit';

        if (usingAdvancedUploader($this->fcmsUser->id))
        {
            $form  = '<form id="frm" name="frm" method="post">';

            $input = '<applet name="jumpLoaderApplet"
                    code="jmaster.jumploader.app.JumpLoaderApplet.class"
                    archive="inc/thirdparty/jumploader_z.jar"
                    width="200"
                    height="260"
                    mayscript>
                    <param name="uc_sendImageMetadata" value="true"/>
                    <param name="uc_maxFiles" value="1"/>
                    <param name="uc_uploadUrl" value="familytree.php?advanced_avatar='.$userid.'&orig='.$row['avatar'].'"/>
                    <param name="vc_useThumbs" value="true"/>
                    <param name="uc_uploadScaledImagesNoZip" value="true"/>
                    <param name="uc_uploadScaledImages" value="true"/>
                    <param name="uc_scaledInstanceNames" value="avatar"/>
                    <param name="uc_scaledInstanceDimensions" value="80x80xcrop"/>
                    <param name="uc_scaledInstanceQualityFactors" value="900"/>
                    <param name="uc_uploadFormName" value="frm"/>
                    <param name="vc_lookAndFeel" value="system"/>
                    <param name="vc_uploadViewStartActionVisible" value="false"/>
                    <param name="vc_uploadViewStopActionVisible" value="false"/>
                    <param name="vc_uploadViewPasteActionVisible" value="false"/>
                    <param name="vc_uploadViewRetryActionVisible" value="false"/>
                    <param name="vc_uploadViewFilesSummaryBarVisible" value="false"/>
                    <param name="vc_uiDefaults" value="Panel.background=#eff0f4; List.background=#eff0f4;"/> 
                    <param name="ac_fireUploaderStatusChanged" value="true"/> 
                </applet>
                <br/>';

            $js = '<script language="javascript">
                Event.observe("submitUpload","click",function(){
                    var uploader = document.jumpLoaderApplet.getUploader();
                    uploader.startUpload();
                });
                function uploaderStatusChanged(uploader) {
                    if (uploader.isReady() && uploader.getFileCountByStatus(3) == 0) { 
                        window.location.href = "familytree.php";
                    }
                }
                </script>';

            $submit = 'button';
        }
        else
        {
            $form   = '<form id="frm" name="frm" enctype="multipart/form-data" action="?avatar='.$userid.'" method="post">';
            $input  = '<input type="file" name="avatar" id="avatar" size="30" title="'.T_('Upload your personal image (Avatar)').'"/>';
        }


        echo '
                '.$form.'
                    <fieldset>
                        <legend><span>'.T_('Picture').'</span></legend>
                        <div class="field-row">
                            <div class="field-label"><b>'.T_('Current Picture').'</b></div>
                            <div class="field-widget">
                                <img src="'.getCurrentAvatar($userid).'"/>
                            </div>
                        </div>
                        <div class="field-row">
                            <div class="field-label"><b>'.T_('Choose new Picture').'</b></div>
                            <div class="field-widget">
                                '.$input.'
                            </div>
                        </div>
                        <p>
                            <input type="hidden" name="avatar_orig" value="'.cleanOutput($row['avatar']).'"/>
                            <input class="sub1" type="'.$submit.'" name="submitUpload" id="submitUpload" value="'.T_('Submit').'"/>
                            &nbsp; <a href="familytree.php">'.T_('Cancel').'</a>
                        </p>
                    </fieldset>
                </form>
                '.$js.'
            </div>';
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
        $user         = (int)$user;
        $rel_user     = (int)$rel_user;

        $opposite_relationship = ($relationship == 'WIFE') ? 'HUSB' : 'WIFE';

        // Insert relationships for both users
        $sql = "INSERT INTO `fcms_relationship` (
                    `user`, `relationship`, `rel_user`
                ) 
                VALUES 
                    (?, ?, ?),
                    (?, ?, ?)";

        $params = array(
            $user, $relationship, $rel_user,
            $rel_user, $opposite_relationship, $user
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->fcmsError->displayError();

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
        $user       = (int)$user;
        $rel_user   = (int)$rel_user;

        // Insert child relationship
        $sql = "INSERT INTO `fcms_relationship`
                    (`user`, `relationship`, `rel_user`) 
                VALUES
                    (?, ?, ?)";

        $params = array(
            $user, 
            $relationship, 
            $rel_user
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->fcmsError->displayError();

            return false;
        }

        // Get wife/husb of user
        $sql = "SELECT r.`rel_user`, u.`sex`
                FROM `fcms_relationship` AS r, `fcms_users` AS u
                WHERE r.`user` = ?
                AND r.`relationship` IN ('WIFE', 'HUSB')
                AND r.`rel_user` = u.`id`";

        $rows = $this->fcmsDatabase->getRows($sql, $user);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return false;
        }

        // Make a child relationship with the users husb/wife
        if (count($rows) == 1)
        {
            $row = $rows[0];

            $sql = "INSERT INTO `fcms_relationship`
                        (`user`, `relationship`, `rel_user`) 
                    VALUES
                        (?, ?, ?)";

            $params = array(
                $row['rel_user'],
                $relationship,
                $rel_user
            );

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                $this->fcmsError->displayError();

                return false;
            }
        }

        // Get other parent of rel_user
        $sql = "SELECT r.`user`, u.`sex`
                FROM `fcms_relationship` AS r, `fcms_users` AS u
                WHERE `user` = (
                    SELECT `user`
                    FROM `fcms_relationship`
                    WHERE `rel_user` = ?
                    AND `user` != ?
                    AND `relationship` = 'CHIL'
                )
                AND r.`user` = u.`id`";

        $rows = $this->fcmsDatabase->getRows($sql, array($rel_user, $user));
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return false;
        }

        // If user has another parent already, make them HUSB/WIFE
        if (count($rows) == 1)
        {
            $row = $rows[0];

            $spouse_relationship = ($row['sex'] == 'M') ? 'WIFE' : 'HUSB';

            $this->addSpouse($row['user'], $spouse_relationship, $user);
        }

    }

    /**
     * displayLeaf 
     * 
     * @param string  $type 
     * @param int     $user 
     * @param string  $name 
     * @param int     $id 
     * @param int     $byear
     * @param int     $dyear
     * @param boolean $nonmember
     * 
     * @return void
     */
    function displayLeaf ($type, $id, $name, $byear, $dyear, $nonmember)
    {
        $linkName = '';
        $details  = '';
        $edit     = '';

        if ($id > 0)
        {
            $title    = sprintf(T_('View tree for %s.'), $name);
            $linkName = '<a href="?tree='.$id.'" title="'.$title.'">'.$name.'</a>';

            if (!empty($byear))
            {
                $byear .= ' - ';
            }

            $details  = '<span>'.$byear.$dyear.'</span>';

            if ($nonmember or $id == $this->fcmsUser->id)
            {
                $edit = '<a href="?edit='.$id.'" class="edit">'.T_('Edit').'</a>';
            }
        }

        echo '
                    <div class="leaf '.$type.'">
                        <img src="'.getCurrentAvatar($id).'" alt="'.$name.'"/>
                        '.$linkName.'
                        '.$details.'
                        '.$edit.'
                    </div>';
    }

    /**
     * displayAddLeaf 
     * 
     * @param string  $type 
     * @param string  $user 
     * @param string  $name 
     * 
     * @return void
     */
    function displayAddLeaf ($type, $user, $name)
    {
        $addType = $type;

        if ($type == 'grandpa')
        {
            $addType = 'dad';
        }

        if ($type == 'grandma')
        {
            $addType = 'mom';
        }

        echo '
                    <a href="?add='.$addType.'&amp;user='.$user.'" class="leaf '.$type.' unknown">
                        '.$name.'
                    </a>';
    }

    /**
     * getChildren 
     * 
     * Returns an array of children's names keyed by id, for the given user's id.
     * 
     * @param int    $id 
     * 
     * @return void
     */
    function getChildren ($id)
    {
        $children = array();

        $sql = "SELECT `rel_user` AS id, `fname`, `lname`
                FROM `fcms_relationship` AS r, `fcms_users` AS u
                WHERE r.`user` = ?
                AND `rel_user` = u.`id`
                ORDER BY `lname`, `fname`";

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();

            return false;
        }

        foreach ($rows as $r)
        {
            $children[$r['id']] = $r['fname'].' '.$r['lname'];
        }

        return $children;
    }

}
