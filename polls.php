<?php
/**
 * Polls
 * 
 * PHP version 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2012 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     3.1
 */
session_start();

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('Poll', 'datetime', 'comments');

init();

$poll = new Poll($fcmsError, $fcmsDatabase, $fcmsUser);
$page = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $poll);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsMessageBoard;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsPoll)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
        $this->fcmsPoll     = $fcmsPoll;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Polls'),
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        $this->control();
    }

    /**
     * control 
     * 
     * The controlling structure for this script.
     * 
     * @return void
     */
    function control ()
    {
        if (isset($_GET['action']))
        {
            if ($_GET['action'] == 'pastpolls')
            {
                $this->displayPolls();
            }
            else
            {
                $this->displayLatestPoll();
            }
        }
        elseif (isset($_GET['id']))
        {
            if (isset($_POST['addcomment']))
            {
                $this->displayAddCommentSubmit();
            }
            elseif (isset($_GET['results']))
            {
                $this->displayPoll(true);
            }
            else
            {
                $this->displayPoll();
            }
        }
        elseif (isset($_POST['vote']) && isset($_POST['option']))
        {
            $this->displayVoteSubmit();
        }
        else
        {
            $this->displayLatestPoll();
        }
    }

    /**
     * displayHeader 
     * 
     * Displays the header of the page, including the leftcolumn navigation.
     * 
     * @return void
     */
    function displayHeader ()
    {
        $TMPL = $this->fcmsTemplate;

        $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[ 
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
});
//]]>
</script>';

        include_once getTheme($this->fcmsUser->id).'header.php';

        echo '
        <div id="poll" class="centercontent">
            <div id="sections_menu">
                <ul>
                    <li><a href="polls.php">'.T_('Latest').'</a></li>
                    <li><a href="polls.php?action=pastpolls">'.T_('Past Polls').'</a></li>
                </ul>
            </div>';

        if ($this->fcmsUser->access < 2)
        {
            echo '
            <div id="actions_menu">
                <ul>
                    <li><a href="admin/polls.php">'.T_('Administrate').'</a></li>
                </ul>
            </div>';
        }
    }

    /**
     * displayFooter 
     * 
     * @return void
     */
    function displayFooter()
    {
        $TMPL = $this->fcmsTemplate;

        echo '
        </div><!--/poll-->';

        include_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayLatestPoll 
     * 
     * @return void
     */
    function displayLatestPoll ()
    {
        $this->displayHeader();

        // Get poll info
        $data = $this->fcmsPoll->getLatestPollData();
        if ($data === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        if (count($data) <= 0)
        {
            # we have no polls
            return;
        }

        $pollId = key($data);

        // Get comments
        $comments = $this->fcmsPoll->getPollCommentsData($pollId);
        if ($comments === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $commentsTotal = $comments['total'];
        unset($comments['total']);

        $pollOptions = '';
        $class       = 'sub1';
        $disabled    = '';
        $submitValue = T_('Vote');

        // Show results - user already voted
        if (isset($data['users_who_voted'][$this->fcmsUser->id]))
        {
            $submitValue = T_('Already Voted');
            $class       = 'disabled';
            $disabled    = 'disabled="disabled"';

            $pollOptions = $this->fcmsPoll->formatPollResults($data);
            if ($pollOptions === false)
            {
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }
        // Show options
        else
        {
            foreach ($data[$pollId]['options'] as $optionId => $optionData)
            {
                $pollOptions .= '
                    <p>
                        <label class="radio_label">
                            <input type="radio" name="option" value="'.$optionId.'"/>
                            '.cleanOutput($optionData['option'], 'html').'
                        </label>
                    </p>';
            }
        }

        echo '
            <h2>'.T_('Latest Poll').'</h2>
            <form class="poll" method="post" action="polls.php">
                <h3>'.cleanOutput($data[$pollId]['question'], 'html').'</h3>
                '.$pollOptions.'
                <p class="actions">
                    <a href="#comments">'.sprintf(T_('Comments (%s)'), $commentsTotal).'</a><br/>
                    <input type="hidden" id="id" name="id" value="'.$pollId.'"/>
                    <input type="submit" class="'.$class.'" '.$disabled.' id="vote" name="vote" value="'.$submitValue.'"/>
                </p>
            </form>';

        // Comments
        echo '
        <div id="comments">';

        foreach ($comments as $row)
        {
            $delete      = '';
            $date        = fixDate(T_('F j, Y g:i a'), $this->fcmsUser->tzOffset, $row['created']);
            $displayname = $row['fname'].' '.$row['lname'];
            $comment     = $row['comment'];
            $avatarPath  = getAvatarPath($row['avatar'], $row['gravatar']);

            if ($this->fcmsUser->id == $row['created'] || $this->fcmsUser->access < 2)
            {
                $delete .= '<input type="submit" name="delcom" id="delcom" '
                    . 'value="'.T_('Delete').'" class="gal_delcombtn" title="'
                    . T_('Delete this Comment') . '"/>';
            }

            echo '
            <div class="comment">
                <form class="delcom" action="polls.php?id='.$pollId.'" method="post">
                    '.$delete.'
                    <img class="avatar" alt="avatar" src="'.$avatarPath.'"/>
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>
                        '.parse($comment).'
                    </p>
                    <input type="hidden" name="id" value="'.$row['id'].'">
                </form>
            </div>';
        }

        echo '
            '.getAddCommentsForm('polls.php?id='.$pollId).'
        </div>';

        $this->displayFooter();
    }

    /**
     * displayPolls 
     * 
     * @return void
     */
    function displayPolls ()
    {
        $this->displayHeader();

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        $pollsData = $this->fcmsPoll->getPolls($page);
        if ($pollsData === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $ids = $pollsData['ids'];
        unset($pollsData['ids']);

        $votesLkup = $this->fcmsPoll->getPollsTotalVotes($ids);
        if ($votesLkup === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        echo '
            <h2>'.T_('Past Polls').'</h2>
            <table class="sortable">
                <thead>
                    <tr>
                        <th>'.T_('Question').'</th>
                        <th>'.T_('Date').'</th>
                        <th>'.T_('Votes').'</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($pollsData as $row)
        {
            $date = fixDate(T_('M. j, Y, g:i a'), $this->fcmsUser->tzOffset, $row['started']);

            echo '
                    <tr>
                        <td><a href="?id='.$row['id'].'">'.cleanOutput($row['question'], 'html').'</a></td>
                        <td>'.$date.'</td></td>
                        <td>'.$votesLkup[$row['id']].'</td>
                    </tr>';
        }

        echo '
                </tbody>
            </table>';

        $this->displayFooter();
    }

    /**
     * displayPoll 
     * 
     * @param boolean $displayResults 
     * 
     * @return void
     */
    function displayPoll ($displayResults = false)
    {
        $this->displayHeader();

        $id = (int)$_GET['id'];

        $pollData = $this->fcmsPoll->getPollData($id);
        if ($pollData === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $pollId = key($pollData);

        // Get comments
        $comments = $this->fcmsPoll->getPollCommentsData($pollId);
        if ($comments === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $commentsTotal = $comments['total'];
        unset($comments['total']);

        $pollOptions = '';
        $class       = 'sub1';
        $disabled    = '';
        $submitValue = T_('Vote');

        // Show results - user already voted
        if (isset($pollData['users_who_voted'][$this->fcmsUser->id]) || $displayResults)
        {
            $submitValue = T_('Already Voted');
            $class       = 'disabled';
            $disabled    = 'disabled="disabled"';

            $pollOptions = $this->fcmsPoll->formatPollResults($pollData);
            if ($pollOptions === false)
            {
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }
        // Show options
        else
        {
            foreach ($pollData[$pollId]['options'] as $optionId => $optionData)
            {
                $pollOptions .= '
                    <p>
                        <label class="radio_label">
                            <input type="radio" name="option" value="'.$optionId.'"/>
                            '.cleanOutput($optionData['option'], 'html').'
                        </label>
                    </p>';
            }
        }

        echo '
            <form class="poll" method="post" action="polls.php">
                <h3>'.cleanOutput($pollData[$pollId]['question'], 'html').'</h3>
                '.$pollOptions.'
                <p class="actions">
                    <a href="#comments">'.sprintf(T_('Comments (%s)'), $commentsTotal).'</a><br/>
                    <input type="hidden" id="id" name="id" value="'.$pollId.'"/>
                    <input type="submit" class="'.$class.'" '.$disabled.' id="vote" name="vote" value="'.$submitValue.'"/>
                </p>
            </form>';

        // Comments
        echo '
        <div id="comments">';

        foreach ($comments as $row)
        {
            $delete      = '';
            $date        = fixDate(T_('F j, Y g:i a'), $this->fcmsUser->tzOffset, $row['created']);
            $displayname = $row['fname'].' '.$row['lname'];
            $comment     = $row['comment'];
            $avatarPath  = getAvatarPath($row['avatar'], $row['gravatar']);

            if ($this->fcmsUser->id == $row['created'] || $this->fcmsUser->access < 2)
            {
                $delete .= '<input type="submit" name="delcom" id="delcom" '
                    . 'value="'.T_('Delete').'" class="gal_delcombtn" title="'
                    . T_('Delete this Comment') . '"/>';
            }

            echo '
            <div class="comment">
                <form class="delcom" action="polls.php?id='.$pollId.'" method="post">
                    '.$delete.'
                    <img class="avatar" alt="avatar" src="'.$avatarPath.'"/>
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>
                        '.parse($comment).'
                    </p>
                    <input type="hidden" name="id" value="'.$row['id'].'">
                </form>
            </div>';
        }

        echo '
            '.getAddCommentsForm('polls.php?id='.$pollId).'
        </div>';

        $this->displayFooter();
    }

    /**
     * displayVoteSubmit 
     * 
     * @return void
     */
    function displayVoteSubmit ()
    {
        $optionId = (int)$_POST['option'];
        $pollId   = (int)$_POST['id'];

        $result = $this->fcmsPoll->placeVote($optionId, $pollId);
        if ($result === false)
        {
            if ($this->fcmsError->hasError())
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }
            else
            {
                // TODO
                // Use session redirect to form
                $this->displayHeader();
                echo '<p class="info-alert">'.T_('You have already voted.').'</p>';
                $this->displayFooter();

                return;
            }
        }

        header("Location: polls.php?id=$pollId");
    }

    /**
     * displayAddCommentSubmit 
     * 
     * @return void
     */
    function displayAddCommentSubmit ()
    {
        $pollId   = (int)$_GET['id'];
        $comments = strip_tags($_POST['comments']);

        if (empty($comments))
        {
            header("Location: polls.php?id=$pollId");
        }

        $sql = "INSERT INTO `fcms_poll_comment`
                    (`poll_id`, `comment`, `created`, `created_id`) 
                VALUES
                    (?, ?, NOW(), ?)";

        $params = array(
            $pollId, 
            $comments, 
            $this->fcmsUser->id
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->displayHeader();

            $this->fcmsError->setMessage(T_('Could not add comment.'));
            $this->fcmsError->displayError();

            $this->displayFooter();

            return;
        }

        header("Location: polls.php?id=$pollId");
    }
}
