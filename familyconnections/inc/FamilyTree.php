<?php

/**
 * FamilyTree 
 * 
 * XXX: The family tree creates a relationship of HUSB/WIFE for both biological
 * parents of a child and also for married couples.
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class FamilyTree
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;

    private $count = 0;

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
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;

        // Set the user's tree we are currently viewing
        if (isset($_GET['view']))
        {
            $this->currentTreeUserId = (int)$_GET['view'];
        }
        else
        {
            $this->currentTreeUserId = $this->fcmsUser->id;
        }

        $_SESSION['currentTreeUserId'] = $this->currentTreeUserId;
    }

    /**
     * getParentsOfUsers 
     * 
     * Given a list of user ids, will return all the users' parents.
     * 
     * idList option will return a single array with key as spouse id
     * 
     * @param array $ids 
     * @param boolean $idList 
     * 
     * @return mixed - array on success or false on failure
     */
    function getParentsOfUsers (array $ids, $idList = false)
    {
        if (count($ids) < 1)
        {
            return array();
        }

        $params = array();

        $where = '';
        foreach ($ids as $k => $v)
        {
            if (count($params) > 0)
            {
                $where .= "OR ";
            }

            $where .= "`rel_user` = ? ";

            if (is_array($v))
            {
                $params[] = $v['id'];
            }
            else
            {
                $params[] = $v;
            }
        }

        $sql = "SELECT u.`id`, `fname`, `mname`, `lname`, `maiden`, `dob_year`, `dob_month`, `dob_day`, `dod_year`, `dod_month`, `dod_day`, 
                    `avatar`, `gravatar`, `sex`, `relationship`, `rel_user`, `phpass`
                FROM `fcms_relationship` AS r
                LEFT JOIN `fcms_users` AS u ON r.`user` = u.`id`
                WHERE ($where)
                AND `relationship` = 'CHIL'";

        $parents = $this->fcmsDatabase->getRows($sql, $params);
        if ($parents === false)
        {
            return false;
        }

        // Return just list of parent ids
        if ($idList)
        {
            $parentIds = array();

            foreach ($parents as $parent)
            {
                $parentIds[$parent['id']] = 1;
            }

            return $parentIds;
        }

        return $parents;
    }

    /**
     * getSpousesOfUsers 
     * 
     * Given a list of user ids, will return all the users' spouses.
     * 
     * idList option will return a single array with key as spouse id
     * 
     * @param array $ids 
     * @param boolean $idList 
     * 
     * @return mixed - array on success or false on failure
     */
    function getSpousesOfUsers (array $ids, $idList = false)
    {
        if (count($ids) < 1)
        {
            return array();
        }

        $params = array();

        $where = '';
        foreach ($ids as $k => $v)
        {
            if (count($params) > 0)
            {
                $where .= "OR ";
            }

            $where .= "`rel_user` = ? ";

            if (is_array($v))
            {
                $params[] = $v['id'];
            }
            else
            {
                $params[] = $v;
            }
        }

        $sql = "SELECT u.`id`, `fname`, `mname`, `lname`, `maiden`, `dob_year`, `dob_month`, `dob_day`, `dod_year`, `dod_month`, `dod_day`, 
                    `avatar`, `gravatar`, `sex`, `relationship`, `rel_user`, `phpass`
                FROM `fcms_relationship` AS r
                LEFT JOIN `fcms_users` AS u ON r.`user` = u.`id`
                WHERE ($where)
                AND (
                    `relationship` = 'HUSB'
                    OR `relationship` = 'WIFE'
                )";

        $parents = $this->fcmsDatabase->getRows($sql, $params);
        if ($parents === false)
        {
            return false;
        }

        // Return just list of spouse ids
        if ($idList)
        {
            $spouses = array();

            foreach ($parents as $spouse)
            {
                $spouses[$spouse['id']] = 1;
            }

            return $spouses;
        }

        return $parents;
    }

    /**
     * getChildrenOfUsers 
     * 
     * Given a list of user ids, will return all the users' children.
     * 
     * idList option will return a single array with key as child id
     * 
     * @param array   $ids 
     * @param boolean $idList 
     * 
     * @return mixed - array on success or false on failure
     */
    function getChildrenOfUsers (array $ids, $idList = false)
    {
        if (count($ids) < 1)
        {
            return array();
        }

        $params = array();

        $where = '';
        foreach ($ids as $k => $v)
        {
            if (count($params) > 0)
            {
                $where .= "OR ";
            }

            $where .= "`user` = ? ";

            if (is_array($v))
            {
                $params[] = $v['id'];
            }
            else
            {
                $params[] = $v;
            }
        }

        $sql = "SELECT u.`id`, r.`id` AS 'rid', `fname`, `mname`, `lname`, `maiden`, `dob_year`, `dob_month`, `dob_day`, `dod_year`, `dod_month`, `dod_day`, 
                    `avatar`, `gravatar`, `sex`, `relationship`, `user` AS 'rel_user', `phpass`
                FROM `fcms_relationship` AS r
                LEFT JOIN `fcms_users` AS u ON r.`rel_user` = u.`id`
                WHERE ($where)
                AND `relationship` = 'CHIL'";

        $duplicateChildren = $this->fcmsDatabase->getRows($sql, $params);
        if ($duplicateChildren === false)
        {
            return false;
        }

        $children = array();

        // Return just list of children ids
        if ($idList)
        {
            foreach ($duplicateChildren as $child)
            {
                $children[$child['id']] = 1;
            }

            return $children;
        }

        // Child X could be listed twice, once for parent A
        // and once for parent b
        // To make it easier, we combine those records into one
        // with two rel_users
        foreach ($duplicateChildren as $child)
        {
            if (isset($children[$child['id']]))
            {
                $children[$child['id']]['rel_user2'] = $child['rel_user'];
            }
            else
            {
                $children[$child['id']] = $child;
            }
        }

        return $children;
    }

    /**
     * getUserTreeInfo 
     * 
     * @param int $id 
     * 
     * @return array
     */
    function getUserTreeInfo ($id)
    {
        $sql = "SELECT `id`, `fname`, `mname`, `lname`, `maiden`, `dob_year`, `dob_month`, `dob_day`, `dod_year`, `dod_month`, `dod_day`, 
                    `avatar`, `gravatar`, `sex`, `phpass`
                FROM `fcms_users` AS u
                WHERE u.`id` = ?";

        $user = $this->fcmsDatabase->getRow($sql, (int)$id);
        if ($user === false)
        {
            return array();
        }

        return $user;
    }

    /**
     * displaySpousesAndKids
     * 
     * Displays the spouse and kids of the given person, and their spouse and kids,
     * recursively, until we run out of data.
     * 
     * @param array $parent
     * @param array $data 
     * 
     * @return void
     */
    function displaySpousesAndKids ($parent, $data)
    {
        $spouses = array_shift($data);
        $kids    = array_shift($data);

        $thisSpouses = $this->getSpouses($parent, $spouses);
        $spouseCount = count($thisSpouses);

        if ($spouseCount > 1)
        {
            $htmlParentAllOpen  = '<ul class="p">';
            $htmlParentAllClose = '</ul>';
            $htmlParentOpen     = '<li>';
            $htmlParentClose    = '</li>';
        }
        elseif ($spouseCount == 1)
        {
            $htmlParentAllOpen  = '<div class="p1">';
            $htmlParentAllClose = '</div>';
            $htmlParentOpen     = '';
            $htmlParentClose    = '';
        }

        // We have spouses for this parent
        if ($spouseCount > 0)
        {
            echo "\n";
            echo $htmlParentAllOpen;

            foreach ($thisSpouses as $spouse)
            {
                echo "\n";
                echo $htmlParentOpen;
                $this->displayPerson($spouse);

                $thisKids = $this->getKids($parent, $spouse, $kids);
                $thisKids = subval_sort($thisKids, 'dob_year');
                $kidCount = count($thisKids);

                if ($kidCount > 0)
                {
                    echo "\n";
                    echo '<ul class="c">';

                    foreach ($thisKids as $kid)
                    {
                        echo "\n";
                        echo '<li>';
                        $this->displayPerson($kid);
                        $this->displaySpousesAndKids($kid, $data);
                        echo '</li>';
                    }

                    echo '</ul>';
                }

                echo $htmlParentClose;
            }

            echo $htmlParentAllClose;
        }
        // This parent has no spouse, but still have kids
        else
        {
            $thisKids = $this->getKids($parent, array(), $kids);
            $kidCount = count($thisKids);

            if ($kidCount > 0)
            {
                echo "\n";
                echo '<ul class="c">';

                foreach ($thisKids as $kid)
                {
                    echo "\n";
                    echo '<li>';
                    $this->displayPerson($kid);
                    $this->displaySpousesAndKids($kid, $data);
                    echo '</li>';
                }

                echo '</ul>';
            }
        }
    }

    /**
     * getSpouses 
     * 
     * Given a parent and a list of possible spouses,
     * will determine if any of the spouses are a spouse
     * of the given parent.
     * 
     * @param array $parent 
     * @param array $spouses 
     * 
     * @return void
     */
    function getSpouses ($parent, $spouses)
    {
        $foundSpouses = array();

        if (count($spouses) > 0)
        {
            // This parent could have multiple, lets check
            foreach ($spouses as $spouse)
            {
                if ($spouse['rel_user'] == $parent['id'])
                {
                    $foundSpouses[] = $spouse;
                }
            }
        }

        return $foundSpouses;
    }

    /**
     * getKids 
     * 
     * Given two parents and a list of possible kids,
     * will determine if any of the kids are children
     * of the two parents.
     * 
     * @param array $parent1 
     * @param array $parent2 
     * @param array $kids 
     * 
     * @return array
     */
    function getKids ($parent1, $parent2, $kids)
    {
        $foundKids = array();
        $idsFound  = array();

        if (count($kids) > 0)
        {
            // If no 2nd parent, fake it
            if (!isset($parent2['id']))
            {
                $parent2['id'] = $parent1['id'];
            }

            // Make sure kid is kid of both parents
            foreach ($kids as $kid)
            {
                // Is kids 1st parent, one of the two given parents?
                if ($kid['rel_user'] == $parent1['id'] || $kid['rel_user'] == $parent2['id'])
                {
                    // If kid has a 2nd parent, is it also one of the two given?
                    if (isset($kid['rel_user2']) && ($kid['rel_user2'] == $parent1['id'] || $kid['rel_user2'] == $parent2['id']))
                    {
                        $foundKids[] = $kid;
                        $idsFound[$kid['id']] = 1;
                    }
                    // Kid has no 2nd parent
                    elseif (!isset($kid['rel_user2']))
                    {
                        $foundKids[] = $kid;
                        $idsFound[$kid['id']] = 1;
                    }
                }
            }
        }

        // Put kids in chronological order by dob
        if (count($foundKids) > 0)
        {
            $sortedKids = subval_sort($foundKids, 'dob_year');
            $sortedKids = array_reverse($sortedKids);
        }

        return $foundKids;
    }

     /**
     * displayPerson 
     * 
     * Displays the anchor representing a single
     * person on the tree.
     * 
     * @param array $data 
     * 
     * @return void
     */
    function displayPerson ($data)
    {
        $data['sex'] = strtolower($data['sex']);


        $bd = isset($data['dob_day']) && !empty($data['dob_day']) ? (int)$data['dob_day'].'.' : '?.';
        $bm = isset($data['dob_month']) && !empty($data['dob_month']) ? (int)$data['dob_month'].'.' : '?.';
        $by = isset($data['dob_year']) && !empty($data['dob_year']) ? (int)$data['dob_year'].' - ' : '? - ';
        $b= $bd.$bm.$by;

        $dd = isset($data['dod_day']) && !empty($data['dod_day']) ? (int)$data['dod_day'].'.' : '?.';
        $dm = isset($data['dod_month']) && !empty($data['dod_month']) ? (int)$data['dod_month'].'.' : '?.';
        $dy = isset($data['dod_year']) && !empty($data['dod_year']) ? (int)$data['dod_year'] : '?';
        $d =$dd.$dm.$dy;
        $middlename = isset($data['mname']) && !empty($data['mname']) ? $data['mname'].'<br/>' : '';
        $maidenname =isset($data['maiden']) && !empty($data['maiden']) && $data['maiden'] <> $data['lname'] ? '<br/>('.$data['maiden'].')' : '';

        $avatarPath = getAvatarPath($data['avatar'], $data['gravatar']);

        $edit = '';
        $add  = '';
        $del  = '';
        if ($data['phpass'] == 'NONMEMBER' || $this->fcmsUser->access == 1)
        {
            $edit = '<a class="edit" href="?view='.$data['id'].'&amp;edit='.$data['id'].'">'.T_('Edit').'</a>';
            $add  = '<a class="add" href="#'.$data['id'].'">'.T_('Add Family Member').'</a>';
            $del  = '<a class="delete" href="?delete='.$data['id'].'">'.T_('Delete All Relationships').'</a>';
            $del .= '<script type="text/javascript">';
            $del .= '$$(\'a.delete\').each(function(item) {';
            $del .= '    item.onclick = function() {';
            $del .= '        if (confirm(\''.T_('Are you sure you want to DELETE this?').'\')) {';
            $del .= '            var url = item.href;';
            $del .= '            window.location = url + "&confirm=1";';
            $del .= '        }';
            $del .= '        return false;';
            $del .= '    };';
            $del .= '});';
            $del .= '</script>';
        }

        echo '
        <div class="person '.$data['sex'].'">
            <div class="tree-thumbnail">
                <img src="'.$avatarPath.'"/>
            </div>
            <div class="tree-detail">
                <a href="?details='.$data['id'].'">
                    '.$data['fname'].'<br/>
                    '.$middlename.'
                    '.$data['lname'].'
                    '.$maidenname.'
                </a>
                <p>'.$b.$d.'</p>
                <span class="tools">
                    <a class="view" href="?view='.$data['id'].'">'.T_('View').'</a>
                    '.$del.'
                    '.$edit.'
                    '.$add.'
                </span>
            </div>
        </div>';
    }

    /**
     * getFormattedBirthdayAndDeath 
     * 
     * Will return an array with two dates, one bday and one death day.
     * 
     * @param array $data 
     * 
     * @return array
     */
    function getFormattedBirthdayAndDeath (array $data)
    {
        $bday  = T_('Unknown');
        $death = '';

        if (!empty($data['dob_year']))
        {
            $bday  = '<b>'.T_('Born').':</b> ';
            $bday .= formatBirthday($data['dob_year'], $data['dob_month'], $data['dob_day']);

            if (empty($data['dod_year']) && !empty($data['dob_month']) && !empty($data['dob_day']))
            {
                $age   = getAge($data['dob_year'], $data['dob_month'], $data['dob_day']);
                $bday .= ' ('.sprintf(T_pgettext('Ex: age 30', 'age %d'), $age).')';
            }
        }
        if (!empty($data['dod_year']))
        {
            $death  = '<b>'.T_('Passed Away').':</b> ';
            $death .= formatBirthday($data['dod_year'], $data['dod_month'], $data['dod_day']);

            $checkDates = array(
                'dob_year'  => 'Y',
                'dob_month' => 'm',
                'dob_day'   => 'd',
                'dod_year'  => 'Y',
                'dod_month' => 'm',
                'dod_day'   => 'd'
            );
            $haveAllDates = 0;
            foreach ($checkDates as $d => $k)
            {
                if (!empty($data[$d]))
                {
                    ${$d} = $data[$d];
                    $haveAllDates++;
                }
                else
                {
                    ${$d} = gmdate($k);
                }
            }

            if (!empty($data['dod_month']) && !empty($data['dod_day']))
            {
                $dod = gmdate($data['dod_year'].'-'.$data['dod_month'].'-'.$data['dod_day']);
                $age = getAge($dob_year, $dob_month, $dob_day, $dod);
            }
            else
            {
                $dod = gmdate($data['dod_year'].'-'.gmdate('m').'-'.gmdate('d'));
                $age = getAge($dob_year, $dob_month, $dob_day, $dod);
            }

            if ($haveAllDates < 6)
            {
                $age = '~'.$age;
            }

            $death .= ' ('.sprintf(T_pgettext('Ex: at age ~81', 'at age %s'), $age).')';
        }

        return array($bday, $death);
    }

    /**
     * displayEditPersonForm 
     * 
     * @param int $userId 
     * 
     * @return void
     */
    function displayEditPersonForm ($userId)
    {
        $validator = new FormValidator();

        // Get user info
        $sql = "SELECT `id`, `fname`, `mname`, `lname`, `maiden`, `dob_year`, `dob_month`, `dob_day`, `dod_year`, `dod_month`, `dod_day`, `sex`, `bio`
                FROM `fcms_users`
                WHERE `id` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $userId);
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
        <form action="familytree.php?edit='.$userId.'" method="post">
            <fieldset class="relationship-form">
                <legend><span>'.T_('Edit').'</span></legend>
                <div style="text-align:right"><a href="?avatar='.$userId.'">'.T_('Add Picture').'</a></div>
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
                <p>
                    <label for="bio"><b>'.T_('Bio').'</b></label><br/>
                    <textarea name="bio" id="bio" cols="40" rows="5">'.$row['bio'].'</textarea>
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
                '.$validator->getJsValidation($this->getProfile('edit')).'
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
                </script>
                <p>
                    <input type="hidden" id="id" name="id" value="'.$userId.'"/>
                    <input class="sub1" type="submit" id="edit-user" name="submit" value="'.T_('Edit').'"/> &nbsp;
                    <a href="familytree.php?view='.$this->currentTreeUserId.'">'.T_('Cancel').'</a>
                </p>
            </fieldset>
        </form>';
    }

    /**
     * displayAddFatherMotherSpouseForm 
     * 
     * @param int    $userId 
     * @param string $type 
     * 
     * @return void
     */
    function displayAddFatherMotherSpouseForm ($userId, $type)
    {
        $sql = "SELECT `id`, `fname`, `mname`, `lname`
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $userId);
        if ($user === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $name = $user['fname'].' '.$user['mname'].' '.$user['lname'];

        switch ($type)
        {
            case 'father':
                $legend     = sprintf(T_pgettext('%s is a persons name', 'Add new father for %s'), $name);
                $label      = T_('Father');
                $createLink = T_('Create father not listed above');
                $members    = $this->getPossibleFatherList($userId);
                break;

            case 'mother':
                $legend     = sprintf(T_pgettext('%s is a persons name', 'Add new mother for %s'), $name);
                $label      = T_('Mother');
                $createLink = T_('Create mother not listed above');
                $members    = $this->getPossibleMotherList($userId);
                break;

            case 'spouse':
                $legend     = sprintf(T_pgettext('%s is a persons name', 'Add new spouse for %s'), $name);
                $label      = T_('Spouse');
                $createLink = T_('Create spouse not listed above');
                $members    = $this->getPossibleSpouseList($userId);
                break;

            default:
                echo '<div class="error-alert">'.T_('Invalid relationship type.').'</div>';
                return;
        }

        if ($members === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        // No existing members found, redirect them to the create new user form
        if (strlen($members) <= 0)
        {
            // can't send header because we already started printing to the page
            echo '<meta http-equiv="refresh" content="0; url=?create='.$type.'&amp;user='.$userId.'">';
            return;
        }

        // Get parents
        $parents = $this->getParentsOfUsers(array($userId));
        if ($parents === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($parents) > 0)
        {
            // Get all children of all parents
            $children = $this->getChildrenOfUsers($parents);
            if ($children === false)
            {
                $this->fcmsError->displayError();
                return;
            }
        }

        echo '
        <form action="familytree.php?add='.$userId.'" method="post">
            <fieldset>
                <legend><span>'.$legend.'</span></legend>
                <div class="field-row">
                    <div class="field-label">
                        <label for="rel_user"><b>'.$label.'</b></label>
                    </div>
                    <div class="field-widget">
                        <select name="rel_user">
                            '.$members.'
                        </select><br/>
                        <a href="?create='.$type.'&amp;user='.$userId.'">'.$createLink.'</a>
                    </div>
                </div>
                <p>
                    <input type="hidden" id="id" name="id" value="'.$userId.'"/>
                    <input type="hidden" id="type" name="type" value="'.$type.'"/>
                    <input class="sub1" type="submit" id="add-relative" name="additional-options" value="'.T_('Add').'"/> &nbsp;
                    <a href="familytree.php?view='.$this->currentTreeUserId.'">'.T_('Cancel').'</a>
                </p>
            </fieldset>
        </form>';
    }

    /**
     * displayAddChildForm 
     * 
     * @param int $userId 
     * 
     * @return void
     */
    function displayAddChildForm ($userId)
    {
        // Get user info
        $sql = "SELECT `id`, `fname`, `mname`, `lname`
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $userId);
        if ($user === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $name = $user['fname'].' '.$user['mname'].' '.$user['lname'];

        // Get possible children
        $members = $this->getPossibleChildList($userId);
        if ($members === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        // No existing members found, redirect them to the create new user form
        if (strlen($members) <= 0)
        {
            // can't send header because we already started printing to the page
            echo '<meta http-equiv="refresh" content="0; url=?create=child&amp;user='.$userId.'">';
            return;
        }

        $legend = sprintf(T_pgettext('%s is a persons name', 'Add new child for %s'), $name);

        echo '
        <form action="familytree.php?add='.$userId.'" method="post">
            <fieldset>
                <legend><span>'.$legend.'</span></legend>
                <div class="field-row">
                    <div class="field-label">
                        <label for="rel_user"><b>'.T_('Child').'</b></label>
                    </div>
                    <div class="field-widget">
                        <select name="rel_user">
                            '.$members.'
                        </select><br/>
                        <a href="?create=child&amp;user='.$userId.'">'.T_('Create child not listed above.').'</a>
                    </div>
                </div>
                <p>
                    <input type="hidden" id="id" name="id" value="'.$userId.'"/>
                    <input type="hidden" id="type" name="type" value="child"/>
                    <input class="sub1" type="submit" id="add-relative" name="additional-options" value="'.T_('Add').'"/> &nbsp;
                    <a href="familytree.php?view='.$this->currentTreeUserId.'">'.T_('Cancel').'</a>
                </p>
            </fieldset>
        </form>';
    }

    /**
     * displayAddBrotherSisterForm 
     * 
     * @param int    $userId 
     * @param string $type 
     * 
     * @return void
     */
    function displayAddBrotherSisterForm ($userId, $type)
    {
        // Get user info
        $sql = "SELECT `id`, `fname`, `mname`, `lname`
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $userId);
        if ($user === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $name = $user['fname'].' '.$user['mname'].' '.$user['lname'];

        // Get possible children
        if ($type == 'brother')
        {
            $members    = $this->getPossibleBrotherList($userId);
            $legend     = sprintf(T_pgettext('%s is a persons name', 'Add new brother for %s'), $name);
            $label      = T_('Brother');
            $createText = T_('Create brother not listed above.');
            $createLink = '?create=brother&amp;user='.$userId;
        }
        else
        {
            $members    = $this->getPossibleSisterList($userId);
            $legend     = sprintf(T_pgettext('%s is a persons name', 'Add new sister for %s'), $name);
            $label      = T_('Sister');
            $createText = T_('Create sister not listed above.');
            $createLink = '?create=sister&amp;user='.$userId;
        }

        if ($members === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        // No existing members found, redirect them to the create new user form
        if (strlen($members) <= 0)
        {
            // can't send header because we already started printing to the page
            echo '<meta http-equiv="refresh" content="0; url='.$createLink.'">';
            return;
        }

        echo '
        <form action="familytree.php?add='.$userId.'" method="post">
            <fieldset>
                <legend><span>'.$legend.'</span></legend>
                <div class="field-row">
                    <div class="field-label">
                        <label for="rel_user"><b>'.$label.'</b></label>
                    </div>
                    <div class="field-widget">
                        <select name="rel_user">
                            '.$members.'
                        </select><br/>
                        <a href="'.$createLink.'">'.$createText.'</a>
                    </div>
                </div>
                <p>
                    <input type="hidden" id="id" name="id" value="'.$userId.'"/>
                    <input type="hidden" id="type" name="type" value="'.$type.'"/>
                    <input class="sub1" type="submit" id="add-relative" name="additional-options" value="'.T_('Add').'"/> &nbsp;
                    <a href="familytree.php?view='.$this->currentTreeUserId.'">'.T_('Cancel').'</a>
                </p>
            </fieldset>
        </form>';
    }

    /**
     * getPossibleFatherList 
     * 
     * @param int $userId 
     * 
     * @return mixed - string on success false on failure
     */
    function getPossibleFatherList ($userId)
    {
        // Get list of all males
        $sql = "SELECT `id`, `fname`, `mname`, `lname`
                FROM `fcms_users` 
                WHERE `id` != ?
                AND `sex` = 'M'
                ORDER BY `lname`, `fname`";

        $rows = $this->fcmsDatabase->getRows($sql, $userId);
        if ($rows === false)
        {
            return false;
        }

        // Get parents
        $parents = $this->getParentsOfUsers(array($userId));
        if ($parents === false)
        {
            return false;
        }
        foreach ($parents as $parent)
        {
            if ($parent['sex'] == 'M')
            {
                $father = $parent;
            }
            else
            {
                $mother = $parent;
            }
        }

        $fathersRelatives = array();
        $mothersRelatives = array();

        $mothersOldestRelativeId = $userId;

        if (isset($mother['id']))
        {
            $mothersOldestRelativeId = $this->getOldestRelativeId($mother['id']);
        }


        $fathersRelatives = $this->getDescendantsAndSpousesIds(array($userId));
        $mothersRelatives = $this->getDescendantsAndSpousesIds(array($mothersOldestRelativeId));

        $mothersRelatives[$mothersOldestRelativeId] = 1;

        $fathers = '';

        foreach ($rows as $r)
        {
            // skip user's relatives on fathers side
            if (isset($fathersRelatives[$r['id']]))
                continue;

            // skip user's relatives on mothers side
            if (isset($mothersRelatives[$r['id']]))
                continue;

            $maiden = empty($r['maiden']) ? ', ' : ' ('.$r['maiden'].'), ';

            $fathers .= '<option value="'.$r['id'].'">'.$r['lname'].$maiden.' '.$r['fname'].' '.$r['mname'].'</option>';
        }

        return $fathers;
    }

    /**
     * getPossibleMotherList 
     * 
     * @param int $userId 
     * 
     * @return mixed - string on success false on failure
     */
    function getPossibleMotherList ($userId)
    {
        // Get list of all females
        $sql = "SELECT `id`, `fname`, `mname`, `lname`, `maiden`
                FROM `fcms_users` 
                WHERE `id` != ?
                AND `sex` = 'F'
                ORDER BY `lname`, `fname`";

        $rows = $this->fcmsDatabase->getRows($sql, $userId);
        if ($rows === false)
        {
            return false;
        }

        // Get parents
        $parents = $this->getParentsOfUsers(array($userId));
        if ($parents === false)
        {
            return false;
        }
        foreach ($parents as $parent)
        {
            if ($parent['sex'] == 'M')
            {
                $father = $parent;
            }
            else
            {
                $mother = $parent;
            }
        }

        $fathersRelatives = array();
        $mothersRelatives = array();

        $fathersOldestRelativeId = $userId;

        if (isset($father['id']))
        {
            $fathersOldestRelativeId = $this->getOldestRelativeId($father['id']);
        }

        $fathersRelatives = $this->getDescendantsAndSpousesIds(array($fathersOldestRelativeId));
        $mothersRelatives = $this->getDescendantsAndSpousesIds(array($userId));

        $fathersRelatives[$fathersOldestRelativeId] = 1;

        $mothers = '';

        foreach ($rows as $r)
        {
            // skip user's relatives on fathers side
            if (isset($fathersRelatives[$r['id']]))
                continue;

            // skip user's relatives on mothers side
            if (isset($mothersRelatives[$r['id']]))
                continue;

            $maiden = empty($r['maiden']) ? ', ' : ' ('.$r['maiden'].'), ';

            $mothers .= '<option value="'.$r['id'].'">'.$r['lname'].$maiden.' '.$r['fname'].' '.$r['mname'].'</option>';
        }

        return $mothers;
    }

    /**
     * getPossibleBrotherList 
     * 
     * @param int $userId 
     *
     * @return mixed - string on success false on failure
     */
    function getPossibleBrotherList ($userId)
    {
        return $this->getPossibleFatherList($userId);
    }

    /**
     * getPossibleSisterList 
     * 
     * @param int $userId 
     *
     * @return mixed - string on success false on failure
     */
    function getPossibleSisterList ($userId)
    {
        return $this->getPossibleMotherList($userId);
    }

    /**
     * getPossibleSpouseList 
     * 
     * @param int $userId 
     *
     * @return mixed - string on success false on failure
     */
    function getPossibleSpouseList ($userId)
    {
        // Get list of all members
        $sql = "SELECT `id`, `fname`, `mname`, `lname`, `maiden`
                FROM `fcms_users` 
                WHERE `id` != ?
                ORDER BY `lname`, `fname`";

        $rows = $this->fcmsDatabase->getRows($sql, $userId);
        if ($rows === false)
        {
            return false;
        }

        // Get parents
        $parents = $this->getParentsOfUsers(array($userId));
        if ($parents === false)
        {
            return false;
        }
        foreach ($parents as $parent)
        {
            if ($parent['sex'] == 'M')
            {
                $father = $parent;
            }
            else
            {
                $mother = $parent;
            }
        }

        $fathersRelatives = array();
        $mothersRelatives = array();

        if (!isset($father['id']))
        {
            $father['id'] = $userId;
        }

        if (!isset($mother['id']))
        {
            $mother['id'] = $userId;
        }
        
        $fathersOldestRelativeId = $this->getOldestRelativeId($father['id']);
        $mothersOldestRelativeId = $this->getOldestRelativeId($mother['id']);

        $fathersRelatives = $this->getDescendantsAndSpousesIds(array($fathersOldestRelativeId));
        $mothersRelatives = $this->getDescendantsAndSpousesIds(array($mothersOldestRelativeId));

        $fathersRelatives[$fathersOldestRelativeId] = 1;
        $mothersRelatives[$mothersOldestRelativeId] = 1;

        $spouses = '';

        foreach ($rows as $r)
        {
            // skip user's relatives on fathers side
            if (isset($fathersRelatives[$r['id']]))
                continue;

            // skip user's relatives on mothers side
            if (isset($mothersRelatives[$r['id']]))
                continue;

            $maiden = empty($r['maiden']) ? ', ' : ' ('.$r['maiden'].'), ';

            $spouses .= '<option value="'.$r['id'].'">'.$r['lname'].$maiden.' '.$r['fname'].' '.$r['mname'].'</option>';
        }

        return $spouses;
    }

    /**
     * getPossibleChildList 
     * 
     * @param int $userId 
     *
     * @return mixed - string on success false on failure
     */
    function getPossibleChildList ($userId)
    {
        return $this->getPossibleSpouseList($userId);
    }

    /**
     * getDescendantsAndSpouses
     * 
     * Will recursively get the data of children from an array of ids,
     * and their spouses untill no children are found.
     * 
     * @param array $ids 
     * 
     * @return mixed - array on success or false on failure
     */
    function getDescendantsAndSpouses ($ids)
    {
        $this->count++;
        if ($this->count > 50)
        {
            die('too many descendants');
        }

        $descendants = array();

        foreach ($ids as $id)
        {
            $parents[] = $id;
        }

        // 1. Get users spouses
        $spouses = $this->getSpousesOfUsers($ids);
        if ($spouses === false)
        {
            return false;
        }

        // No spouse found
        if (empty($spouses))
        {
            $descendants[] = array();
        }
        // We have a spouse
        else
        {
            // add spouse ids to parents id list
            foreach ($spouses as $spouse)
            {
                $parents[] = $spouse['id'];
            }

            // add spouses as next level in descendants
            $descendants[] = $spouses;
        }

        // 2. Get users children
        $children = $this->getChildrenOfUsers($parents);
        if ($children === false)
        {
            return false;
        }

        if (!empty($children))
        {
            // get children ids
            $childrenIds = array();
            foreach ($children as $child)
            {
                $childrenIds[] = $child['id'];
            }

            // add children as next level in descendants
            $descendants[] = $children;

            // 3. do it all over again
            $next = $this->getDescendantsAndSpouses($childrenIds);
        }

        if (isset($next) && !empty($next))
        {
            foreach ($next as $n)
            {
                $descendants[] = $n;
            }
        }

        return $descendants;
    }

    /**
     * getDescendantsAndSpousesIds
     * 
     * Will recursively get ids of children from an array of ids,
     * and their spouses untill no children are found.
     * 
     * @param array $ids 
     * 
     * @return mixed - array on success or false on failure
     */
    function getDescendantsAndSpousesIds ($ids)
    {
        $this->count++;
        if ($this->count > 50)
        {
            die('too many descendants');
        }

        foreach ($ids as $id)
        {
            $descendants[$id] = 1;
            $parents[$id]     = 1;
        }

        $spouses = $this->getSpousesOfUsers($ids, true);
        if ($spouses === false)
        {
            return false;
        }

        if (!empty($spouses))
        {
            $parents     = $parents + $spouses;
            $descendants = $descendants + $spouses;
        }

        $children = $this->getChildrenOfUsers(array_keys($parents), true);
        if ($children === false)
        {
            return false;
        }

        if (!empty($children))
        {
            $descendants = $descendants + $children;
            $children    = $this->getDescendantsAndSpousesIds(array_keys($children));
        }

        $descendants = $descendants + $children;

        return $descendants;
    }

    /**
     * getOldestRelativeId 
     * 
     * Will recursively get the father of the given user
     * until no father is found.
     * 
     * @param int $userId 
     *
     * @return mixed - int on success or false on failure
     */
    function getOldestRelativeId ($userId)
    {
        $oldest = $userId;

        $parents = $this->getParentsOfUsers(array($userId));
        if ($parents === false)
        {
            return false;
        }

        if (!empty($parents))
        {
            $count = count($parents);

            $oldest = $parents[0]['id'];
            if ($count > 1 && $parents[0]['sex'] !== 'M')
            {
                // if we can't find the father, stop and return
                return $parents[1]['id'];
            }

            $oldest = $this->getOldestRelativeId($oldest);
        }

        return $oldest;
    }

    /**
     * addParent 
     * 
     * Adds a new biologoical parent in the db for the given user.
     * 
     * XXX: This is for adding BIOLOGICAL parents.
     * Users can only add one mother and one father,
     * if they have multiple, they must be added through
     * new spouses, not parents
     *
     * @param array $newParents
     * @param int   $userId
     * 
     * @return boolean
     */
    function addParent (array $newParent1, $userId, array $newParent2 = array())
    {
        $params = array(
            'userId'     => $userId,
            'parentId1'  => (isset($newParent1['id'])  ? $newParent1['id']  : null),
            'parentSex1' => (isset($newParent1['sex']) ? $newParent1['sex'] : null),
            'parentId2'  => (isset($newParent2['id'])  ? $newParent2['id']  : null),
            'parentSex2' => (isset($newParent2['sex']) ? $newParent2['sex'] : null),
        );

        $validator = new FormValidator();

        $errors = $validator->validate($params, $this->getProfile('add_parent'));
        if ($errors !== true)
        {
            foreach ($errors as $msg)
            {
                $this->fcmsError->add(array(
                    'type'    => 'operation',
                    'message' => $msg,
                    'error'   => $params,
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ));
            }

            return false;
        }

        // Get sex if missing
        if (!isset($newParent1['sex']) || is_null($newParent1['sex']))
        {
            $sql = "SELECT `sex`
                    FROM `fcms_users`
                    WHERE `id` = ?";

            $sqlParams[] = $newParent1['id'];

            if (isset($newParent2['id']))
            {
                if (!isset($newParent2['sex']) || is_null($newParent2['sex']))
                {
                    $sql .= "
                            UNION
                            SELECT `sex`
                            FROM `fcms_users`
                            WHERE `id` = ?";

                    $sqlParams[] = $newParent2['id'];
                }
            }

            $newParentsInfo = $this->fcmsDatabase->getRows($sql, $sqlParams);
            if ($newParentsInfo === false)
            {
                return false;
            }

            $newParent1['sex'] = $newParentsInfo[0]['sex'];
            $newParent2['sex'] = isset($newParent2['id']) ? $newParentsInfo[1]['sex'] : null;
        }


        // Get existing parents of $user
        $existingParents = $this->getParentsOfUsers(array($userId));
        if ($existingParents === false)
        {
            return false;
        }

        $existingParentsCount = count($existingParents);

        // 2 existing parents
        if ($existingParentsCount > 1)
        {
            $this->fcmsError->add(array(
                'message' => T_('Both parents already exist.'),
                'details' => '<p>'.T_('Please add only biological parents. Step parents should be added as spouses, not parents.').'</p>'
            ));
            return false;
        }
        // 1 existing parent
        elseif ($existingParentsCount == 1)
        {
            // Can't add multiple parents if we already have atleast one existing
            if (isset($newParent2['id']))
            {
                $this->fcmsError->add(array(
                    'message' => T_('Parents already exist.'),
                    'details' => '<p>'.T_('Please add only biological parents. Step parents should be added as spouses, not parents.').'</p>'
                ));
                return false;
            }

            // Can't add same sex parents
            if ($newParent1['sex'] == $existingParents[0]['sex'])
            {
                $message = $newParent1['sex'] == 'M' ? T_('Father already exists.') : T_('Mother already exists.');

                $this->fcmsError->add(array(
                    'message' => $message,
                    'details' => '<p>'.T_('Please add only biological parents. Same-sex parents should be added as spouses, not parents.').'</p>'
                ));
                return false;
            }
        }

        // Add $userId as a child of $newParent1
        if (!$this->addChildren(array($userId), $newParent1))
        {
            return false;
        }

        // No existing parents
        if ($existingParentsCount < 1 && isset($newParent2['id']))
        {
            // Add $userId as a child of $newParent2
            if (!$this->addChildren(array($userId), $newParent2))
            {
                return false;
            }
        }
        // $userId has an existing parent
        elseif ($existingParentsCount == 1)
        {
            // Add new parent as spouse of existing
            if (!$this->addSpouse($newParent1, $existingParents[0]))
            {
                return false;
            }
        }
    }

    /**
     * addSibling 
     * 
     * @param int   $userId 
     * @param int   $siblingId 
     * @param array $parents
     * 
     * @return boolean
     */
    function addSibling ($userId, $siblingId, array $parents)
    {
        $params = array(
            'userId'    => $userId,
            'siblingId' => $siblingId,
            'parentId'  => $parents
        );

        $validator = new FormValidator();

        $errors = $validator->validate($params, $this->getProfile('add_sibling'));
        if ($errors !== true)
        {
            foreach ($errors as $msg)
            {
                $this->fcmsError->add(array(
                    'type'    => 'operation',
                    'message' => $msg,
                    'error'   => $params,
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ));
            }

            return false;
        }

        $sql = "INSERT INTO `fcms_relationship`
                    (`user`, `relationship`, `rel_user`) 
                VALUES ";

        $params = array();

        // Add each child to the parents
        foreach ($parents as $parentId)
        {
            $sql .= "(?, 'CHIL', ?),";

            $params[] = $parentId;
            $params[] = $siblingId;
        }

        $sql = substr($sql, 0, -1); // remove extra comma

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            return false;
        }

        return true;
    }

    /**
     * addChildren
     * 
     * Adds an array of childen to a given parent or set of parents.
     * 
     * @param array $child 
     * @param array $parent1
     * @param array $parent2
     * 
     * @return boolean
     */
    function addChildren (array $children, array $parent1, array $parent2 = array())
    {
        $params = array(
            'parentId1'  => (isset($parent1['id'])  ? $parent1['id']  : null),
            'parentSex1' => (isset($parent1['sex']) ? $parent1['sex'] : null),
            'parentId2'  => (isset($parent2['id'])  ? $parent2['id']  : null),
            'parentSex2' => (isset($parent2['sex']) ? $parent2['sex'] : null),
        );

        foreach ($children as $id)
        {
            $params['childId'][] = $id;
        }

        $validator = new FormValidator();

        $errors = $validator->validate($params, $this->getProfile('add_child'));
        if ($errors !== true)
        {
            foreach ($errors as $msg)
            {
                $this->fcmsError->add(array(
                    'type'    => 'operation',
                    'message' => $msg,
                    'error'   => $params,
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ));
            }

            return false;
        }

        // lets get existing children for the parents
        $existingChildren1 = $this->getChildrenOfUsers(array($parent1), true);
        if ($existingChildren1 === false)
        {
            return false;
        }
        if (isset($parent2['id']))
        {
            $existingChildren2 = $this->getChildrenOfUsers(array($parent2), true);
            if ($existingChildren2 === false)
            {
                return false;
            }
        }

        // start sql
        $sql = "INSERT INTO `fcms_relationship`
                    (`user`, `relationship`, `rel_user`) 
                VALUES ";

        $params = array();
        $childrenAdded = false;

        // Add each child to the parents
        foreach ($children as $childId)
        {
            // only add if this child doesn't already exist for parent1
            if (!isset($existingChildren1[$childId]))
            {
                $sql .= "(?, 'CHIL', ?),";

                $params[] = $parent1['id'];
                $params[] = $childId;

                $childrenAdded = true;
            }

            if (isset($parent2['id']))
            {
                if (!isset($existingChildren2[$childId]))
                {
                    $sql .= "(?, 'CHIL', ?),";

                    $params[] = $parent2['id'];
                    $params[] = $childId;

                    $childrenAdded = true;
                }
            }
        }

        if ($childrenAdded)
        {
            $sql = substr($sql, 0, -1); // remove extra comma

            if (!$this->fcmsDatabase->insert($sql, $params))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * addSpouse 
     * 
     * Adds a spouse to a given user.  Will also add optional children to the user and spouse.
     * 
     * @param array $user
     * @param array $spouse
     * @param array $children
     * 
     * @return boolean
     */
    function addSpouse (array $user, array $spouse, array $children = array())
    {
        $params = array(
            'userId'    => (isset($user['id'])    ? $user['id']    : null),
            'spouseId'  => (isset($spouse['id'])  ? $user['id']    : null),
            'userSex'   => (isset($user['sex'])   ? $spouse['sex'] : null),
            'spouseSex' => (isset($spouse['sex']) ? $spouse['sex'] : null),
        );

        foreach ($children as $id)
        {
            $params['childId'][] = $id;
        }

        $validator = new FormValidator();

        $errors = $validator->validate($params, $this->getProfile('add_spouse'));
        if ($errors !== true)
        {
            foreach ($errors as $msg)
            {
                $this->fcmsError->add(array(
                    'type'    => 'operation',
                    'message' => $msg,
                    'error'   => $params,
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ));
            }

            return false;
        }

        // Get sex for user and spouse
        if (!isset($user['sex']) || is_null($user['sex']) || !isset($spouse['sex']) || is_null($spouse['sex']))
        {
            $sql = "SELECT `sex`
                    FROM `fcms_users`
                    WHERE `id` = ?
                    UNION
                    SELECT `sex`
                    FROM `fcms_users`
                    WHERE `id` = ?";
            $userSpouseInfo = $this->fcmsDatabase->getRows($sql, array($user['id'], $spouse['id']));
            if ($userSpouseInfo === false)
            {
                return false;
            }

            $userInfo      = $userSpouseInfo[0];
            $spouseInfo    = isset($userSpouseInfo[1]) ? $userSpouseInfo[1] : null;
            $user['sex']   = $userInfo['sex'];
            $spouse['sex'] = $spouseInfo['sex'];
        }

        $relationship = $spouse['sex'] == 'M' ? 'HUSB' : 'WIFE';

        // Figure out the spouse relationship
        // Same sex
        if ($user['sex'] == $spouse['sex'])
        {
            $spouseRelationship = $relationship;
        }
        // Opposite
        else
        {
            $spouseRelationship = $relationship == 'WIFE' ? 'HUSB' : 'WIFE';
        }

        // Insert relationships for both users
        $sql = "INSERT INTO `fcms_relationship` (
                    `user`, `relationship`, `rel_user`
                ) 
                VALUES 
                    (?, ?, ?),
                    (?, ?, ?)";

        $params = array(
            $user['id'], $relationship, $spouse['id'],
            $spouse['id'], $spouseRelationship, $user['id']
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            return false;
        }

        // Handle any children
        if (!empty($children))
        {
            if (!$this->addChildren($children, $user, $spouse))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * displayCreateUserForm 
     * 
     * Displays the form for creating a new user to be added to the family tree
     * 
     * @param string $type 
     * @param int    $userId 
     * 
     * @return void
     */
    function displayCreateUserForm ($type, $userId)
    {
        $userId = (int)$userId;

        $displayname = getUserDisplayName($userId, 2);

        switch ($type)
        {
            case 'father':
                $sex     = 'M';
                $legend  = sprintf(T_('Add New Father for %s'), $displayname);
                $options = $this->getAddFatherMotherAdditionalOptions($userId, $type);
                break;

            case 'mother':
                $sex     = 'F';
                $legend  = sprintf(T_('Add New Mother for %s'), $displayname);
                $options = $this->getAddFatherMotherAdditionalOptions($userId, $type);
                break;

            case 'brother':
                $sex     = 'M';
                $legend  = sprintf(T_('Add New Brother for %s'), $displayname);
                $options = $this->getAddBrotherSisterAdditionalOptions($userId);
                break;

            case 'sister':
                $sex     = 'F';
                $legend  = sprintf(T_('Add New Sister for %s'), $displayname);
                $options = $this->getAddBrotherSisterAdditionalOptions($userId);
                break;

            case 'spouse':
                $sex     = '?';
                $legend  = sprintf(T_('Add New Spouse for %s'), $displayname);
                $options = $this->getAddSpouseAdditionalOptions($userId);
                break;

            case 'child':
                $sex     = '?';
                $legend  = sprintf(T_('Add New Child for %s'), $displayname);
                $options = $this->getAddChildAdditionalOptions($userId);
                break;

            default:
                echo '
            <div class="error-alert">'.T_('Invalid Display Type').'</div>';
                return;
        }

        if ($options === false)
        {
            $this->fcmsError->displayError();
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

        $validator = new FormValidator();

        echo '
        <form action="familytree.php?create=submit" method="post">
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
        if ($sex == 'F' || $sex == '?')
        {
            echo '
                <p class="maiden-name">
                    <label for="maiden"><b>'.T_('Maiden Name').'</b></label><br/>
                    <input class="frm_text" type="text" name="maiden" id="maiden" size="25"/>
                </p>';
        }

        // We don't know the sex of the child or spouse, but we do for all other relationships
        if ($sex === '?')
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
                '.$options.'
                '.$validator->getJsValidation($this->getProfile('create')).'
                <p>
                    <input type="hidden" id="id" name="id" value="'.$userId.'"/>
                    <input type="hidden" id="type" name="type" value="'.cleanOutput($type).'"/>
                    <input class="sub1" type="submit" id="submit" name="submit" value="'.T_('Add').'"/> &nbsp;
                    <a href="familytree.php?view='.$this->currentTreeUserId.'">'.T_('Cancel').'</a>
                </p>
            </fieldset>
        </form>';
    }

    /**
     * getAddFatherMotherAdditionalOptions 
     * 
     * @param int    $userId 
     * @param string $type 
     * @param int    $parentId 
     * 
     * @return mixed - string on success, false on failure
     */
    function getAddFatherMotherAdditionalOptions ($userId, $type, $parentId = null)
    {
        $options = '';

        if (empty($parentId))
        {
            return $options;
        }

        // get parentId info
        $sql = "SELECT `id`, `fname`, `lname`
                FROM `fcms_users`
                WHERE `id` = ?";
        $parentInfo = $this->fcmsDatabase->getRow($sql, $parentId);
        if ($parentInfo === false)
        {
            return false;
        }

        // get children of parentId
        $parentChildren = $this->getChildrenOfUsers(array($parentId));
        if ($parentChildren === false)
        {
            return false;
        }

        // get other parent
        $spouses = $this->getSpousesOfUsers(array($parentId));
        if ($spouses === false)
        {
            return false;
        }

        foreach ($spouses as $parent)
        {
            $options .= '<p><label><b>'.T_('Other Parent and Siblings').'</b></label><br/>';
            $options .= '<input type="checkbox" id="other-parent'.$parent['id'].'" name="other-parent" value="'.$parent['id'].'"/>';
            $options .= '<label for="other-parent'.$parent['id'].'">'.$parent['fname'].' '.$parent['lname'].'</label><br/>';

            // other parent's children
            $children = $this->getChildrenOfUsers(array($parent['id']));
            if ($children === false)
            {
                return false;
            }

            $otherParentChildren = array();

            if (count($children) > 0)
            {
                foreach ($children as $child)
                {
                    $otherParentChildren[$child['id']] = 1;

                    $options .= '<input type="hidden" id="sibling'.$child['id'].'" name="sibling['.$parent['id'].'][]" value="'.$child['id'].'"/>';
                    $options .= '<label for="sibling'.$child['id'].'" class="no-check">'.$child['fname'].' '.$child['lname'].'</label><br/>';
                }
            }

            // parents children
            if (count($parentChildren) > 0)
            {
                foreach ($parentChildren as $child)
                {
                    // skip this child if he/she is child of other parent also
                    if (isset($otherParentChildren[$child['id']]))
                    {
                        continue;
                    }

                    $options .= '<input type="hidden" id="sibling'.$child['id'].'" name="sibling['.$parentId.'][]" value="'.$child['id'].'"/>';
                    $options .= '<label for="sibling'.$child['id'].'" class="no-check">'.$child['fname'].' '.$child['lname'].'</label><br/>';
                }
            }

            $options .= '</p>';
        }

        return $options;
    }

    /**
     * getAddSpouseAdditionalOptions 
     * 
     * If kids exist for user or spouse, then we need
     * to display option to choose which children belong
     * to this couple.
     * 
     * @param int $userId 
     * @param int $spouseId
     * 
     * @return mixed - string on success, false on failure
     */
    function getAddSpouseAdditionalOptions ($userId, $spouseId = null)
    {
        $options = '';

        $children = $this->getChildrenOfUsers(array($userId));
        if ($children === false)
        {
            return false;
        }

        // if spouse exists, get their children too
        if (!empty($spouseId))
        {
            $spouseChildren = $this->getChildrenOfUsers(array($spouseId));
            if ($spouseChildren === false)
            {
                return false;
            }
            foreach ($spouseChildren as $child)
            {
                $children[] = $child;
            }
        }

        if (count($children) > 0)
        {
            $options .= '<p><label><b>'.T_('Children').'</b></label><br/>';

            foreach ($children as $child)
            {
                $options .= '<input type="checkbox" id="child'.$child['id'].'" name="child[]" value="'.$child['id'].'"/>';
                $options .= ' <label for="child'.$child['id'].'">'.$child['fname'].' '.$child['lname'].'</label><br/>';
            }

            $options .= '</p>';
        }

        return $options;
    }

    /**
     * getAddChildAdditionalOptions 
     * 
     * We need to know who the other parent is.
     * 
     * @param int $userId 
     * @param int $childId 
     * 
     * @return mixed - string on success, false on failure
     */
    function getAddChildAdditionalOptions ($userId, $childId = null)
    {
        $options = '';

        // Get spouses of user
        $spouses = $this->getSpousesOfUsers(array($userId));
        if ($spouses === false)
        {
            return false;
        }

        // if child exists already, get his parents
        $parents = array();
        if (!empty($childId))
        {
            $parents = $this->getParentsOfUsers(array($childId));
            if ($parents === false)
            {
                return false;
            }

            if (count($parents) > 1)
            {
                $this->fcmsError->add(array(
                    'type'    => 'operation',
                    'message' => T_('Cannot add child relationship. Child already has 2 parents.'),
                    'error'   => $siblingParents,
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ));
                return false;
            }

        }

        $spouseCount = count($spouses);

        if (count($parents) == 1)
        {
            $children = $this->getChildrenOfUsers(array($parents[0]['id']));
            if ($children === false)
            {
                return false;
            }

            foreach ($children as $child)
            {
                if ($child['id'] == $childId)
                {
                    continue;
                }

                $options .= '<input type="checkbox" id="child'.$child['id'].'" name="child[]" value="'.$child['id'].'"/>';
                $options .= ' <label for="child'.$child['id'].'">'.$child['fname'].' '.$child['lname'].'</label><br/>';
            }

            $options .= '</p>';

            $options .= '<p><label><b>'.T_('Other Parent').'</b></label><br/>';

            $options .= '<input type="hidden" name="other-parent" value="'.$parents[0]['id'].'"/>';
            $options .= $parents[0]['fname'].' '.$parents[0]['lname'];
            $options .= '</p>';
        }
        elseif ($spouseCount == 1)
        {
            $options .= '<p><label><b>'.T_('Other Parent').'</b></label><br/>';

            $options .= '<input type="hidden" name="other-parent" value="'.$spouses[0]['id'].'"/>';
            $options .= $spouses[0]['fname'].' '.$spouses[0]['lname'];
            $options .= '</p>';
        }
        elseif ($spouseCount > 1)
        {
            $options .= '<p><label><b>'.T_('Other Parent').'</b></label><br/>';

            foreach ($spouses as $spouse)
            {
                $options .= '<input type="radio" id="other-parent'.$spouse['id'].'" name="other-parent" value="'.$spouse['id'].'"/>';
                $options .= ' <label for="other-parent'.$spouse['id'].'">'.$spouse['fname'].' '.$spouse['lname'].'</label><br/>';
            }

            $options .= '</p>';
        }

        return $options;
    }

    /**
     * getAddBrotherSisterAdditionalOptions 
     * 
     * @param int $userId 
     * @param int $siblingId 
     * 
     * @return mixed - string on success, false on failure
     */
    function getAddBrotherSisterAdditionalOptions ($userId, $siblingId = null)
    {
        $options = '';

        $userParents = $this->getParentsOfUsers(array($userId));
        if ($userParents === false)
        {
            return false;
        }

        $siblingParents = array();

        if (!empty($siblingId))
        {
            $siblingParents = $this->getParentsOfUsers(array($siblingId));
            if ($siblingParents === false)
            {
                return false;
            }
        }

        $userParentCount    = count($userParents);
        $siblingParentCount = count($siblingParents);
        $totalParentCount   = $userParentCount + $siblingParentCount;

        // we can't have more than 2 total parents
        if ($totalParentCount > 2)
        {
            $this->fcmsError->add(array(
                'type'    => 'operation',
                'message' => T_('Cannot add sibling relationship, incompatible number of parents.'),
                'error'   => $siblingParents,
                'file'    => __FILE__,
                'line'    => __LINE__,
            ));
            return false;
        }

        /* 
        user  sibling
         2       2      error
         2       1      error
         2       0      hidden input user parents
         1       2      error
         1       1      error if parents are not opposite sex, else hidden input
         1       0      hidden input user parent
         0       2      hidden input sibling parents
         0       1      hidden input sibling parent
         0       0      error, can't add as siblings
        */
        if ($userParentCount == 2)
        {
            $options .= '<p><label><b>'.T_('Parents').'</b></label><br/>';
            foreach ($userParents as $parent)
            {
                $options .= '<input type="hidden" name="parent[]" value="'.$parent['id'].'"/>';
                $options .= $parent['fname'].' '.$parent['lname'].'<br/>';
            }
            $options .= '</p>';
        }
        elseif ($siblingParentCount == 2)
        {
            $options .= '<p><label><b>'.T_('Parents').'</b></label><br/>';
            foreach ($siblingParents as $parent)
            {
                $options .= '<input type="hidden" name="parent[]" value="'.$parent['id'].'"/>';
                $options .= $parent['fname'].' '.$parent['lname'].'<br/>';
            }
            $options .= '</p>';
        }
        elseif ($userParentCount == 1)
        {
            if ($siblingParentCount == 1)
            {
                if ($userParents[0]['sex'] == $siblingParents[0]['sex'])
                {
                    $this->fcmsError->add(array(
                        'type'    => 'operation',
                        'message' => T_('Cannot add sibling relationship, parents can not be of the same sex.'),
                        'error'   => $siblingParents,
                        'file'    => __FILE__,
                        'line'    => __LINE__,
                    ));
                    return false;
                }

                $options .= '<p><label><b>'.T_('Parents').'</b></label><br/>';
                $options .= '<input type="hidden" name="parent[]" value="'.$userParents[0]['id'].'"/>';
                $options .= '<input type="hidden" name="parent[]" value="'.$siblingParents[0]['id'].'"/>';
                $options .= $userParents[0]['fname'].' '.$userParents[0]['lname'].'<br/>';
                $options .= $siblingParents[0]['fname'].' '.$siblingParents[0]['lname'];
                $options .= '</p>';
            }
            // $siblingParentCount == 0
            else
            {
                $options .= '<p><label><b>'.T_('Parents').'</b></label><br/>';
                $options .= '<input type="hidden" name="parent[]" value="'.$userParents[0]['id'].'"/>';
                $options .= $userParents[0]['fname'].' '.$userParents[0]['lname'];
                $options .= '</p>';
            }
        }
        // $userParentCount == 0
        else
        {
            if ($siblingParentCount == 0)
            {
                $this->fcmsError->add(array(
                    'type'    => 'operation',
                    'message' => T_('Cannot add sibling relationship, no parents can be found.'),
                    'error'   => $siblingParents,
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ));
                    return false;
            }

            $options .= '<p><label><b>'.T_('Parents').'</b></label><br/>';
            $options .= '<input type="hidden" name="parent[]" value="'.$siblingParents[0]['id'].'"/>';
            $options .= $siblingParents[0]['fname'].' '.$siblingParents[0]['lname'];
            $options .= '</p>';
        }

        return $options;
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
        $sql = "SELECT `id`, `fname`, `mname`, `lname`, `maiden`
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
                <select name="view">
                    <option value="'.$this->fcmsUser->id.'">'.T_('View Family Tree for...').'</option>
                    <option value="'.$this->fcmsUser->id.'">----------</option>';

        foreach ($rows as $r)
        {
            $selected = $this->currentTreeUserId == $r['id'] ? ' selected="selected"' : '';
            $maiden   = empty($r['maiden']) ? ', ' : ' ('.$r['maiden'].'), ';

            echo '
                    <option value="'.$r['id'].'"'.$selected.'>'.cleanOutput($r['lname']).$maiden.' '.cleanOutput($r['fname']).' '.cleanOutput($r['mname']).'</option>';
        }

        echo '
                </select> 
                <input type="submit" value="'.T_('View').'"/>
            </p>
        </form>';
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
            'edit' => array(
                'required' => array(
                    'fname', 'lname'
                ),
                'constraints' => array(
                    'sex' => array(
                        'format' => '/(M|F)/'
                    ),
                    'bday' => array(
                        'integer' => 1
                    ),
                    'bmonth' => array(
                        'integer' => 1
                    ),
                    'byear' => array(
                        'integer' => 1
                    ),
                    'dday' => array(
                        'integer' => 1
                    ),
                    'dmonth' => array(
                        'integer' => 1
                    ),
                    'dyear' => array(
                        'integer' => 1
                    ),
                ),
                'messages' => array(
                    'constraints' => array(
                        'fname' => T_('Required'),
                        'lname' => T_('Required')
                    ),
                    'names' => array(
                        'fname'  => T_('First Name'),
                        'lname'  => T_('Last Name'),
                        'sex'    => T_('Sex'),
                        'bday'   => T_('Birth Day'),
                        'bmonth' => T_('Birth Month'),
                        'byear'  => T_('Birth Year'),
                        'dday'   => T_('Deceased Day'),
                        'dmonth' => T_('Deceased Month'),
                        'dyear'  => T_('Deceased Year'),
                    ),
                ),
            ),
            'add' => array(
                'constraints' => array(
                    'id' => array(
                        'required' => 1,
                        'integer'  => 1
                    ),
                    'rel_user' => array(
                        'required' => 1,
                        'integer'  => 1
                    ),
                    'type' => array(
                        'required' => 1,
                        'format'   => '/(father|mother|spouse|brother|sister|child)/'
                    ),
                ),
                'messages' => array(
                    'constraints' => array(
                        'type' => T_('Type is invalid, must be one of: father, mother, spouse, brother, sister, child.'),
                    ),
                ),
            ),
            'create' => array(
                'required' => array(
                    'id', 'type', 'fname', 'lname', 'sex'
                ),
                'constraints' => array(
                    'id' => array(
                        'integer'  => 1,
                    ),
                    'type' => array(
                        'format'   => '/(father|mother|spouse|brother|sister|child)/'
                    ),
                    'sex' => array(
                        'format'   => '/(M|F)/'
                    ),
                    'bday' => array(
                        'integer' => 1,
                    ),
                    'bmonth' => array(
                        'integer' => 1,
                    ),
                    'byear' => array(
                        'integer' => 1,
                    ),
                    'dday' => array(
                        'integer' => 1,
                    ),
                    'dmonth' => array(
                        'integer' => 1,
                    ),
                    'dyear' => array(
                        'integer' => 1,
                    ),
                    'child' => array(
                        'integer' => 1,
                        'array'   => 1
                    ),
                ),
            ),
            'add_parent' => array(
                'required' => array(
                    'userId', 'parentId1'
                ),
                'constraints' => array(
                    'userId' => array(
                        'integer' => 1,
                    ),
                    'parentId1' => array(
                        'integer'  => 1,
                    ),
                    'parentSex1' => array(
                        'format' => '/(M|F)/'
                    ),
                    'parentId2' => array(
                        'integer' => 1
                    ),
                    'parentSex2' => array(
                        'format' => '/(M|F)/'
                    ),
                ),
                'constraint_functions' => array(
                    'parentId1' => array(
                        'name'     => 'parents_opposite_sex',
                        'function' => 'addChildOppositeSexParents'
                    ),
                ),
                'messages' => array(
                    'constraint_functions' => array(
                        'parents_opposite_sex' => T_('Parents are of the same sex.  Must only add biological parents.'),
                    ),
                ),
            ),
            'add_spouse' => array(
                'required' => array(
                    'userId', 'spouseId'
                ),
                'constraints' => array(
                    'userId' => array(
                        'integer' => 1
                    ),
                    'spouseId' => array(
                        'integer' => 1
                    ),
                    'childId' => array(
                        'integer' => 1,
                        'array'   => 1
                    ),
                    'userSex' => array(
                        'format' => '/(M|F)/'
                    ),
                    'spouseSex' => array(
                        'format' => '/(M|F)/'
                    ),
                ),
            ),
            'add_child' => array(
                'required' => array(
                    'childId', 'parentId1'
                ),
                'constraints' => array(
                    'childId' => array(
                        'integer' => 1,
                        'array'   => 1
                    ),
                    'parentId1' => array(
                        'integer'  => 1,
                    ),
                    'parentSex1' => array(
                        'format' => '/(M|F)/'
                    ),
                    'parentId2' => array(
                        'integer' => 1
                    ),
                    'parentSex2' => array(
                        'format' => '/(M|F)/'
                    ),
                ),
                'constraint_functions' => array(
                    'parentId1' => array(
                        'name'     => 'parents_opposite_sex',
                        'function' => 'addChildOppositeSexParents'
                    ),
                ),
                'messages' => array(
                    'constraint_functions' => array(
                        'parents_opposite_sex' => T_('Parents are of the same sex.  Children must be added to biological parents.'),
                    ),
                ),
            ),
            'add_sibling' => array(
                'required' => array(
                    'userId', 'siblingId', 'parentId'
                ),
                'constraints' => array(
                    'userId' => array(
                        'integer' => 1,
                    ),
                    'siblingId' => array(
                        'integer' => 1,
                    ),
                    'parentId' => array(
                        'integer' => 1,
                        'array'   => 1,
                    ),
                ),
            ),
        );

        return $profile[$name];
    }
}
