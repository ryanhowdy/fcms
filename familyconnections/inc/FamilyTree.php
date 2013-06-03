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

        $b = isset($data['dob_year']) && !empty($data['dob_year']) ? (int)$data['dob_year'].' - ' : '';
        $d = isset($data['dod_year']) && !empty($data['dod_year']) ? (int)$data['dod_year']       : '';

        $avatarPath = getAvatarPath($data['avatar'], $data['gravatar']);

        $edit = '';
        $add  = '';
        if ($data['phpass'] == 'NONMEMBER' || $this->fcmsUser->access == 1)
        {
            $edit = '<a class="edit" href="?edit='.$data['id'].'">'.T_('Edit').'</a>';
            $add  = '<a class="add" href="?add='.$data['id'].'">'.T_('Add Family Member').'</a>';
        }

        echo '
        <div class="person '.$data['sex'].'">
            <div class="tree-thumbnail">
                <img src="'.$avatarPath.'"/>
            </div>
            <div class="tree-detail">
                <a href="?details='.$data['id'].'">
                    '.$data['fname'].'<br/>
                    '.$data['lname'].'
                </a>
                <p>'.$b.$d.'</p>
                <span class="tools">
                    <a class="view" href="?view='.$data['id'].'">'.T_('View').'</a>
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
     * displayAddRelativeForm 
     * 
     * @param int $userId 
     * 
     * @return void
     */
    function displayAddRelativeForm ($userId)
    {
        $sql = "SELECT *
                FROM `fcms_users`
                WHERE `id` = ?";
        $user = $this->fcmsDatabase->getRow($sql, $userId);
        if ($user === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $father = array();
        $mother = array();

        // Get parents
        $parents = $this->getParentsOfUsers(array($userId));
        if ($parents === false)
        {
            $this->fcmsError->displayError();
            return;
        }
        foreach ($parents as $parent)
        {
            if ($parent['sex'] == 'M')
            {
                $father[] = $parent;
            }
            else
            {
                $mother[] = $parent;
            }
        }

        $addFather  = '';
        $addMother  = '';
        $addBrother = '';
        $addSister  = '';
        if (empty($father))
        {
            $addFather = '<li><a id="father" href="#">'.T_('Father').'</a></li>';
        }
        else
        {
            $addBrother = '<li><a id="brother" href="#">'.T_('Brother').'</a></li>';
            $addSister  = '<li><a id="sister" href="#">'.T_('Sister').'</a></li>';
        }

        if (empty($mother))
        {
            $addMother = '<li><a id="mother" href="#">'.T_('Mother').'</a></li>';
        }
        elseif (strlen($addBrother) == 0)
        {
            $addBrother = '<li><a id="brother" href="#">'.T_('Brother').'</a></li>';
            $addSister = '<li><a id="sister" href="#">'.T_('Sister').'</a></li>';
        }


        $name   = $user['fname'].' '.$user['mname'].' '.$user['lname'];
        $legend = sprintf(T_pgettext('%s is the name of a person', 'Add family member for %s'), $name);

        echo '
        <form action="familytree.php?add='.$userId.'" method="post">
            <fieldset>
                <legend><span>'.$legend.'</span></legend>
                <ul id="types">
                    '.$addFather.'
                    '.$addMother.'
                    '.$addBrother.'
                    '.$addSister.'
                    <li><a id="spouse" href="#">'.T_('Spouse').'</a></li>
                    <li><a id="child" href="#">'.T_('Child').'</a></li>
                </ul>
<script>
var typeLkup = new Object();
typeLkup["father"] = "'.T_('Father').'";
typeLkup["mother"] = "'.T_('Mother').'";
typeLkup["brother"] = "'.T_('Brother').'";
typeLkup["sister"] = "'.T_('Sister').'";
typeLkup["spouse"] = "'.T_('Spouse').'";
typeLkup["child"] = "'.T_('Child').'";

var linkLkup = new Object();
linkLkup["father"] = "'.T_('Add father not listed above.').'";
linkLkup["mother"] = "'.T_('Add mother not listed above.').'";
linkLkup["brother"] = "'.T_('Add brother not listed above.').'";
linkLkup["sister"] = "'.T_('Add sister not listed above.').'";
linkLkup["spouse"] = "'.T_('Add spouse not listed above.').'";
linkLkup["child"] = "'.T_('Add child not listed above.').'";

$$("#types li a").each(function(anchor) {
    anchor.observe("click", function(e) {
        e.preventDefault();
        var id = anchor.readAttribute("id");

        var img = document.createElement("img");
        img.setAttribute("src", "ui/images/ajax-bar.gif");
        img.setAttribute("id", "ajax-loader");
        $("existing-person").insert({"before":img});

        $("existing-person-label").update(typeLkup[id]);

        $("types").hide();

        new Ajax.Request("familytree.php", {
            method: "post",
            parameters: {
                ajax : "family_member_list",
                user : "'.$userId.'",
                type : id,
            },
            onSuccess: function(transport) {
                $("existing-person").show();

                var response = transport.responseText;
                $("existing-person-select").insert({"top":response});

                var para = document.createElement("p");
                var anchor = document.createElement("a");
                anchor.setAttribute("href", "?create=" + id + "&user='.$userId.'");
                anchor.appendChild(document.createTextNode(linkLkup[id]));
                para.appendChild(anchor);
                $("existing-person-select").insert({"after":para});

                var input = document.createElement("input");
                input.setAttribute("type", "hidden");
                input.setAttribute("name", "type");
                input.setAttribute("value", id);
                $("existing-person").insert({"after":input});

                $("ajax-loader").remove();
            },
            onFailure: function(transport) {
                var div = document.createElement("p");
                div.setAttribute("class", "error-alert");

                var para = document.createElement("p");
                para.appendChild(document.createTextNode("'.T_('Having trouble getting family members, please try again later.').'"));

                div.appendChild(para);

                var rpara = document.createElement("p");
                rpara.appendChild(document.createTextNode(transport.responseText));

                div.appendChild(rpara);

                $("existing-person").insert({"before":div});
                $("ajax-loader").remove();
            }
        });
    });
});
</script>
                <div id="existing-person" class="field-row" style="display:none">
                    <div class="field-label"><label for="rel_user"><b id="existing-person-label"></b></label></div>
                    <div class="field-widget">
                        <select id="existing-person-select" name="rel_user">
                        </select><br/>
                    </div>
                </div>
                <p>
                    <input type="hidden" id="id" name="id" value="'.$userId.'"/>
                    <input class="sub1" type="submit" id="add-relative" name="submit" value="'.T_('Add').'"/> &nbsp;
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
                AND `sex` = 'M'";

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

            $fathers .= '<option value="'.$r['id'].'">'.$r['fname'].' '.$r['mname'].' '.$r['lname'].'</option>';
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
        $sql = "SELECT `id`, `fname`, `mname`, `lname`
                FROM `fcms_users` 
                WHERE `id` != ?
                AND `sex` = 'F'";

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

            $mothers .= '<option value="'.$r['id'].'">'.$r['fname'].' '.$r['mname'].' '.$r['lname'].'</option>';
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
        $sql = "SELECT `id`, `fname`, `mname`, `lname`
                FROM `fcms_users` 
                WHERE `id` != ?";

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

            $spouses .= '<option value="'.$r['id'].'">'.$r['fname'].' '.$r['mname'].' '.$r['lname'].'</option>';
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
     * @param int    $user 
     * @param string $type 
     * @param int    $newParent 
     * 
     * @return boolean
     */
    function addParent ($user, $type, $newParent)
    {
        $user      = (int)$user;
        $newParent = (int)$newParent;

        if ($type != 'father' && $type != 'mother')
        {
            $this->fcmsError->add(array(
                'type'    => 'operation',
                'message' => sprintf(T_('Invalid value [%s] for parameter %s passed to function %s.'), $type, 'type', 'addParent()'),
                'error'   => $_POST,
                'file'    => __FILE__,
                'line'    => __LINE__,
            ));
            return false;
        }

        $sex = $type == 'father' ? 'M' : 'F';

        // Get existing parents of $user
        $existingParents = $this->getParentsOfUsers(array($user));
        if ($existingParents === false)
        {
            return false;
        }

        // We should only ever have one existing parent
        $parentsCount = count($existingParents);
        if ($parentsCount > 1)
        {
            $this->fcmsError->add(array(
                'message' => T_('Both parents already exist.'),
                'details' => '<p>'.T_('Please add only biological parents. Step parents should be added as spouses, not parents.').'</p>'
            ));
            return false;
        }

        // check that we don't already have a parent of the sex
        // we are trying to add
        if ($parentsCount == 1 && $existingParents[0]['sex'] == $sex)
        {
            $message = $sex == 'M' ? T_('Father already exists.') : T_('Mother already exists.');

            $this->fcmsError->add(array(
                'message' => $message,
                'details' => '<p>'.T_('Please add only biological parents. Same-sex parents should be added as spouses, not parents.').'</p>'
            ));
            return false;
        }

        // Add the $user as a child of $newParent
        if (!$this->addChild($newParent, $user))
        {
            return false;
        }

        // $user has existing parents
        if ($parentsCount > 0)
        {
            // Add new parent as spouse of existing
            if (!$this->addSpouse($newParent, $existingParents[0]['id']))
            {
                return false;
            }

            // Make $user child of $newParent's spouse
            if (!$this->addChild($spouses[0]['id'], $user))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * addSibling 
     * 
     * @param int $userId 
     * @param int $siblingId 
     * 
     * @return boolean
     */
    function addSibling ($userId, $siblingId)
    {
        // Get parents of $userId
        $parents = $this->getParentsOfUsers(array($userId));
        if ($parents === false)
        {
            return false;
        }

        $options = array(
            'skipParentCheck'      => 1,
            'skipAddChildToSpouse' => 1
        );

        foreach ($parents as $parent)
        {
            if (!$this->addChild($parent['id'], $siblingId, $options))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * addChild 
     * 
     * Adds a child relationship to the given $userId.
     * 
     * @param int   $userId 
     * @param int   $childId 
     * @param array $options
     * 
     * Options:
     * 
     *     spouseId             - so we don't have to query the db for it
     *     userInfo             - so we don't have to query the db for it
     *     skipParentCheck      - if you do not want to make child of other parent
     *     skipAddChildToSpouse - if you do not want to make child of other parent
     * 
     * @return boolean
     */
    function addChild ($userId, $childId, $options = null)
    {
        $userId   = (int)$userId;
        $childId  = (int)$childId;

        $spouseId = isset($options['spouseId']) ? (int)$options['spouseId'] : null;
        $userInfo = isset($options['userInfo']) ? $options['userInfo']      : null;

        // Get user sex
        if (is_null($userInfo))
        {
            $sql = "SELECT *
                    FROM `fcms_users`
                    WHERE `id` = ?";

            $userInfo = $this->fcmsDatabase->getRow($sql, $userId);
            if ($userInfo === false)
            {
                return false;
            }
        }

        $parentsCount = 0;
        if (!isset($options['skipParentCheck']))
        {
            // $childId could have had a parent already, if so,
            // lets make $userId and $childId's parent spouses
            $existingParents = $this->getParentsOfUsers(array($childId));
            if ($existingParents === false)
            {
                return false;
            }

            $parentsCount = count($existingParents);
        }

        // $childId should only ever have one existing parent
        if ($parentsCount > 1)
        {
            $this->fcmsError->add(array(
                'message' => T_('Parents already exist for this child.'),
                'details' => '<p>'.T_('A child can only have two biological parents.').'</p>'
            ));
            return false;
        }

        // if no spouse was given, lets check if they have one
        if (!isset($options['skipAddChildToSpouse']) && is_null($spouseId))
        {
            $spouses = $this->getSpousesOfUsers(array($userId));
            if ($spouses === false)
            {
                return false;
            }

            // XXX - we are making an assumption here that the first spouse is the correct one
            if (count($spouses) > 0)
            {
                $spouseId = $spouses[0]['id'];
            }
        }

        // Make child of $userId
        $sql = "INSERT INTO `fcms_relationship`
                    (`user`, `relationship`, `rel_user`) 
                VALUES
                    (?, 'CHIL', ?)";

        $params = array(
            $userId, 
            $childId
        );

        // Make child of $spouse too
        if (!isset($options['skipAddChildToSpouse']) && !is_null($spouseId))
        {
            $sql .= ", (?, 'CHIL', ?)";

            $params[] = $spouseId;
            $params[] = $childId;
        }

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            return false;
        }

        // Make $existingParent and $userId spouses
        if ($parentsCount == 1)
        {
            $relationship = $userInfo['sex'] == 'M' ? 'WIFE' : 'HUSB';
            if (!$this->addSpouse($userId, $relationship, $existingParents[0]['id'], $userInfo['sex']))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * addSpouse 
     * 
     * @param int    $userId
     * @param string $relationship (HUSB, WIFE)
     * @param int    $spouseId
     * @param string $userSex      (M, F)
     * @param string $spouseSex    (M, F)
     * 
     * @return boolean
     */
    function addSpouse ($userId, $relationship, $spouseId, $userSex = null, $spouseSex = null)
    {
        $userId   = (int)$userId;
        $spouseId = (int)$spouseId;

        $params = array(
            'userId'       => $userId,
            'relationship' => $relationship,
            'spouseId'     => $spouseId,
            'userSex'      => $userSex,
            'spouseSex'    => $spouseSex,
        );

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

        // Get user and spouse info
        $sql = "SELECT *
                FROM `fcms_users`
                WHERE `id` = ?
                UNION
                SELECT *
                FROM `fcms_users`
                WHERE `id` = ?";
        $userSpouseInfo = $this->fcmsDatabase->getRows($sql, array($userId, $spouseId));
        if ($userSpouseInfo === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $userInfo   = $userSpouseInfo[0];
        $spouseInfo = $userSpouseInfo[1];

        if (is_null($userSex))
        {
            $userSex = $userInfo['sex'];
        }
        if (is_null($spouseSex))
        {
            $spouseSex = $spouseInfo['sex'];
        }

        // Get $users existing spouses
        $spouses = $this->getSpousesOfUsers(array($userId));
        if ($spouses === false)
        {
            return false;
        }

        // Figure out the spouse relationship
        if ($userSex == $spouseSex)
        {
            $spouseRelationship = $relationship;
        }
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
            $userId, $relationship, $spouseId,
            $spouseId, $spouseRelationship, $userId
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            return false;
        }

        // If this is $user's first $spouse, then set all 
        // $user's children to be $spouse's children
        if (count($spouses) < 1)
        {
            $children = $this->getChildrenOfUsers(array($userId), true);
            if ($children === false)
            {
                return false;
            }

            $options = array(
                'spouseId'             => $userId,
                'userInfo'             => $spouseInfo,
                'skipParentCheck'      => 1,
                'skipAddChildToSpouse' => 1,
            );

            foreach ($children as $childId => $v)
            {
                if (!$this->addChild($spouseId, $childId, $options))
                {
                    return false;
                }
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
                $sex    = 'M';
                $legend = sprintf(T_('Add New Father for %s'), $displayname);
                break;

            case 'mother':
                $sex    = 'F';
                $legend = sprintf(T_('Add New Mother for %s'), $displayname);
                break;

            case 'brother':
                $sex    = 'M';
                $legend = sprintf(T_('Add New Brother for %s'), $displayname);
                break;

            case 'sister':
                $sex    = 'F';
                $legend = sprintf(T_('Add New Sister for %s'), $displayname);
                break;

            case 'spouse':
                $sex    = '?';
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
                'constraints' => array(
                    'id'    => array(
                        'required' => 1,
                        'integer'  => 1
                    ),
                    'fname' => array(
                        'required' => 1
                    ),
                    'lname' => array(
                        'required' => 1
                    ),
                    'byear' => array(
                        'integer' => 1
                    ),
                    'living_deceased_options' => array(
                        'acceptance' => 1
                    )
                ),
                'messages' => array(
                    'contraints' => array(
                        'fname' => T_('Required'),
                        'lname' => T_('Required')
                    ),
                    'names' => array(
                        'fname'                   => T_('First Name'),
                        'mname'                   => T_('Middle Name'),
                        'lname'                   => T_('Last Name'),
                        'maiden'                  => T_('Maiden Name'),
                        'sex'                     => T_('Sex'),
                        'bio'                     => T_('Bio'),
                        'living_deceased_options' => T_('Living or Deceased'),
                        'bday'                    => T_('Birth Day'),
                        'bmonth'                  => T_('Birth Month'),
                        'byear'                   => T_('Birth Year'),
                        'dday'                    => T_('Deceased Day'),
                        'dmonth'                  => T_('Deceased Month'),
                        'dyear'                   => T_('Deceased Year'),
                    )
                )
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
                    )
                ),
                'messages' => array(
                    'constraints' => array(
                        'type' => T_('Type is invalid, must be one of: father, mother, spouse, brother, sister, child.'),
                    )
                )
            ),
            'create' => array(
                'constraints' => array(
                    'id' => array(
                        'required' => 1,
                        'integer'  => 1,
                    ),
                    'type' => array(
                        'required' => 1,
                        'format'   => '/(father|mother|spouse|brother|sister|child)/'
                    ),
                    'fname' => array(
                        'required' => 1,
                    ),
                    'lname' => array(
                        'required' => 1,
                    ),
                    'sex' => array(
                        'required' => 1,
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
                    )
                )
            ),
            'add_spouse' => array(
                'required' => array(
                    'userId', 'relationship', 'spouseId',
                ),
                'contraints' => array(
                    'userId' => array(
                        'integer' => 1
                    ),
                    'relationship' => array(
                        'format' => '/(HUSB|WIFE)/'
                    ),
                    'spouseId' => array(
                        'integer' => 1
                    ),
                    'userSex' => array(
                        'format' => '/(M|F)/'
                    ),
                    'spouseSex' => array(
                        'format' => '/(M|F)/'
                    ),
                )
            )
        );

        return $profile[$name];
    }
}
