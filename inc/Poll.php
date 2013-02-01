<?php
/**
 * Poll 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Poll
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;

    /**
     * __construct 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase
     * @param object $fcmsUser 
     *
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
    }

    /**
     * getLatestPollData 
     * 
     * Returns an array of poll data. See getPollData() for example.
     * 
     * @return array
     */
    function getLatestPollData ()
    {
        $data = array();

        // Get Latest poll
        $sql = "SELECT MAX(`id`) AS max 
                FROM `fcms_polls`";

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $this->fcmsError->setMessage(T_('Could not get latest poll information.'));

            return false;
        }

        $pollId = $row['max'];

        // No polls exist
        if (is_null($pollId))
        {
            return;
        }

        return $this->getPollData($pollId);
    }

    /**
     * getPollData 
     * 
     * Example array:
     * Where the int keys are ids for that element.
     * 
     * Array
     * (
     *     [1] => Array
     *         (
     *             [question] => Family Connections software is...
     *             [total_votes] => 1
     *             [options] => Array
     *                 (
     *                     [1] => Array
     *                         (
     *                             [option] => Easy to use!
     *                             [votes] => Array
     *                                 (
     *                                     [total] => 0
     *                                     [users] => Array
     *                                         (
     *                                         )
     *                                 )
     *                         )
     *                     [2] => Array
     *                         (
     *                             [option] => Visually appealing!
     *                             [votes] => Array
     *                                 (
     *                                     [total] => 0
     *                                     [users] => Array
     *                                         (
     *                                         )
     *                                 )
     *                         )
     *                     [3] => Array
     *                         (
     *                             [option] => Just what our family needed!
     *                             [votes] => Array
     *                                 (
     *                                     [total] => 1
     *                                     [users] => Array
     *                                         (
     *                                             [1] => 1
     *                                         )
     *                                 )
     *                         )
     *                 )
     *         )
     *     [users_who_voted] => Array
     *         (
     *             [1] => 1
     *         )
     * )
     * 
     * @param id $id 
     * 
     * @return array
     */
    function getPollData ($id)
    {
        // Get poll questions
        $sql = "SELECT p.`id`, `question`, o.`id` AS option_id, `option` 
                FROM `fcms_polls` AS p
                LEFT JOIN `fcms_poll_options` AS o ON p.`id` = o.`poll_id`
                WHERE p.`id` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false)
        {
            $this->fcmsError->setMessage(T_('Could not get poll information.'));

            return false;
        }

        foreach ($rows as $r)
        {
            $data[$r['id']]['question']    = $r['question'];
            $data[$r['id']]['total_votes'] = 0;

            $data[$r['id']]['options'][$r['option_id']] = array(
                'option' => $r['option'],
                'votes'  => array(
                    'total' => 0,
                    'users' => array(),
                ),
            );
        }

        // Get votes
        $sql = "SELECT `id`, `user`, `option`, `poll_id`
                FROM `fcms_poll_votes` 
                WHERE `poll_id` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false)
        {
            $this->fcmsError->setMessage(T_('Could not get poll information.'));

            return false;
        }

        foreach ($rows as $r)
        {
            $data[$r['poll_id']]['total_votes']++;
            $data[$r['poll_id']]['options'][$r['option']]['votes']['total']++;

            $data[$r['poll_id']]['options'][$r['option']]['votes']['users'][$r['user']] = 1;
            $data['users_who_voted'][$r['user']] = 1;
        }

        return $data;
    }

    /**
     * formatPollResults 
     * 
     * Given an array of poll options data (from getPollData), will 
     * return a string of html, showing the results.
     * 
     * @param array $pollOptionsData 
     * 
     * @return void
     */
    function formatPollResults ($pollData)
    {
        $pollId     = key($pollData);
        $totalVotes = $pollData[$pollId]['total_votes'];
        $fcmsUsersLkup  = array();

        if (isset($pollData['users_who_voted']))
        {
            $fcmsUsersLkup = $this->getUsersAvatarName($pollData['users_who_voted']);
            if ($fcmsUsersLkup === false)
            {
                // Errors already set
                return false;
            }
        }

        $pollOptions = '
                <ul class="poll-results">';

        $i = 1;
        foreach ($pollData[$pollId]['options'] as $optionId => $optionData)
        {
            $votes   = $optionData['votes']['total'];
            $fcmsUsers   = null;
            $percent = 0;
            $width   = 0;

            if ($totalVotes > 0)
            {
                $percent = $votes/$totalVotes;
                $width   = round((140 * $percent) + 10, 0);
                $percent = round((($votes/$totalVotes) * 100), 0);
            }

            foreach ($optionData['votes']['users'] as $fcmsUserId => $val)
            {
                $fcmsUsers .= '<li><div onmouseover="showTooltip(this)" onmouseout="hideTooltip(this)">';
                $fcmsUsers .= '<img src="'.$fcmsUsersLkup[$fcmsUserId]['avatar'].'"/></div><div class="tooltip" style="display:none;">';
                $fcmsUsers .= '<h5>'.$fcmsUsersLkup[$fcmsUserId]['name'].'</h5></div></li>';
            }
            if (is_null($fcmsUsers))
            {
                 $fcmsUsers = '<li>'.T_('None').'</li>';
            }

            $pollOptions .= '
                    <li>
                        <b>'.cleanOutput($optionData['option'], 'html').'</b>
                        <span>'.sprintf(T_('%s votes'), $votes).'</span>
                        <a href="#" onclick="$(\'who'.$i.'\').toggle(); return false;" class="progress" title="'.T_('Click to see who voted for this.').'">
                            <div class="bar" style="width:'.$percent.'%"></div>
                        </a>
                        <div id="who'.$i.'" class="who-voted" style="display:none">
                            <ul class="avatar-member-list-small">
                                '.$fcmsUsers.'
                            </ul>
                        </div>
                    </li>';
            $i++;
        }

        $pollOptions .= '
                </ul>';

        return $pollOptions;
    }

    /**
     * getPolls 
     * 
     * @param int $page 
     * 
     * @return array
     */
    function getPolls ($page)
    {
        $polls = array();

        $from = (($page * 25) - 25);

        $sql = "SELECT `id`, `question`, `started`
                FROM `fcms_polls` 
                ORDER BY started DESC 
                LIMIT $from, 25";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage(T_('Could not get poll information.'));

            return false;
        }

        foreach ($rows as $r)
        {
            $polls[] = $r;

            $polls['ids'][] = $r['id'];
        }

        return $polls;
    }

    /**
     * placeVote 
     * 
     * @param int $optionId 
     * @param int $pollId 
     * 
     * @return boolean
     */
    function placeVote ($optionId, $pollId)
    {
        $optionId = (int)$optionId;
        $pollId   = (int)$pollId;

        $sql = "SELECT `user` 
                FROM `fcms_poll_votes` 
                WHERE `user` = ?
                AND `poll_id` = ?";

        $params = array(
            $this->fcmsUser->id,
            $pollId
        );

        $row = $this->fcmsDatabase->getRow($sql, $params);
        if ($row === false)
        {
            $this->fcmsError->setMessage(T_('Could not get poll information.'));

            return false;
        }

        // query will only return results if voted previously
        if (!empty($row))
        {
            return false;
        }

        $sql = "UPDATE `fcms_poll_options` 
                SET `votes` = `votes`+1 
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->update($sql, $optionId))
        {
            $this->fcmsError->setMessage(T_('Could not update poll.'));

            return false;
        }

        $sql = "INSERT INTO `fcms_poll_votes`
                    (`user`, `option`, `poll_id`) 
                VALUES 
                    (?, ?, ?)";

        $params = array(
            $this->fcmsUser->id,
            $optionId,
            $pollId
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->fcmsError->setMessage(T_('Could not add vote.'));

            return false;
        }

        return true;
    }

    /**
     * getPollsTotalVotes 
     * 
     * Will return the vote counts for the given list of polls.
     * 
     * @param string  $ids 
     * 
     * @return void
     */
    function getPollsTotalVotes ($ids)
    {
        $data = array();

        $ids = implode(',', $ids);

        $sql = "SELECT p.`id`, SUM(`votes`) AS total 
                FROM `fcms_polls` AS p
                LEFT JOIN `fcms_poll_options` AS o ON p.`id` = o.`poll_id`
                WHERE p.`id` IN ($ids)
                GROUP BY `id`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage(T_('Could not get polls votes.'));

            return false;
        }

        foreach ($rows as $r)
        {
            $data[$r['id']] = $r['total'];
        }

        return $data;
    }

    /**
     * getPollCommentsData
     * 
     * @param int $id 
     * 
     * @return array
     */
    function getPollCommentsData ($id)
    {
        $comments = array('total' => 0);

        $sql = "SELECT c.`id`, c.`comment`, c.`created`, c.`created_id`, u.`fname`, u.`lname`, u.`avatar`, u.`gravatar`
                FROM `fcms_poll_comment` AS c
                LEFT JOIN `fcms_users` AS u ON c.`created_id` = u.`id`
                WHERE `poll_id` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false)
        {
            $this->fcmsError->setMessage(T_('Could not get poll comments.'));

            return false;
        }

        foreach ($rows as $r)
        {
            $comments[] = $r;

            $comments['total']++;
        }

        return $comments;
    }

    /**
     * getUsersAvatarName
     * 
     * Gets the avatar and name for the given members.
     * 
     * @param array $users 
     * 
     * @return array
     */
    function getUsersAvatarName ($users)
    {
        $avatars = array();

        $ids = implode(',', array_keys($users));

        $sql = "SELECT `id`, `avatar`, `gravatar`, `fname`, `lname`
                FROM `fcms_users`
                WHERE `id` IN ($ids)";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->setMessage(T_('Could not get member information.'));

            return false;
        }

        foreach ($rows as $r)
        {
            $avatars[$r['id']]['avatar'] = getAvatarPath($r['avatar'], $r['gravatar']);
            $avatars[$r['id']]['name']   = $r['fname'].' '.$r['lname'];
        }

        return $avatars;
    }
}
