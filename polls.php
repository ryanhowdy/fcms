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

// Globals
$pollObj = new Poll($fcmsUser, $fcmsError);

$TMPL = array(
    'currentUserId' => $fcmsUser->id,
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Polls'),
    'path'          => URL_PREFIX,
    'displayname'   => $fcmsUser->displayName,
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

control();
exit();


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
            displayPolls();
        }
        else
        {
            displayLatestPoll();
        }
    }
    elseif (isset($_GET['id']))
    {
        if (isset($_POST['addcomment']))
        {
            displayAddCommentSubmit();
        }
        elseif (isset($_GET['results']))
        {
            displayPoll(true);
        }
        else
        {
            displayPoll();
        }
    }
    elseif (isset($_POST['vote']) && isset($_POST['option']))
    {
        displayVoteSubmit();
    }
    else
    {
        displayLatestPoll();
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
    global $fcmsUser, $TMPL;

    $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[ 
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
});
//]]>
</script>';

    include_once getTheme($fcmsUser->id).'header.php';

    echo '
        <div id="poll" class="centercontent">
            <div id="sections_menu">
                <ul>
                    <li><a href="polls.php">'.T_('Latest').'</a></li>
                    <li><a href="polls.php?action=pastpolls">'.T_('Past Polls').'</a></li>
                </ul>
            </div>';

    if (checkAccess($fcmsUser->id) < 2)
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
    global $fcmsUser, $TMPL;

    echo '
        </div><!--/poll-->';

    include_once getTheme($fcmsUser->id).'footer.php';
}

/**
 * displayLatestPoll 
 * 
 * @return void
 */
function displayLatestPoll ()
{
    global $fcmsUser, $fcmsError, $pollObj;

    displayHeader();

    // Get poll info
    $data = $pollObj->getLatestPollData();
    if ($data === false)
    {
        $fcmsError->displayErrors();
        displayFooter();
        return;
    }

    $pollId = key($data);

    // Get comments
    $comments = $pollObj->getPollCommentsData($pollId);
    if ($comments === false)
    {
        $fcmsError->displayErrors();
        displayFooter();
        return;
    }

    $commentsTotal = $comments['total'];
    unset($comments['total']);

    $pollOptions = '';
    $class       = 'sub1';
    $disabled    = '';
    $submitValue = T_('Vote');

    // Show results - user already voted
    if (isset($data['users_who_voted'][$fcmsUser->id]))
    {
        $submitValue = T_('Already Voted');
        $class       = 'disabled';
        $disabled    = 'disabled="disabled"';

        $pollOptions = $pollObj->formatPollResults($data);
        if ($pollOptions === false)
        {
            $fcmsError->displayErrors();
            displayFooter();
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
        $date        = fixDate(T_('F j, Y g:i a'), $fcmsUser->tzOffset, $row['created']);
        $displayname = $row['fname'].' '.$row['lname'];
        $comment     = $row['comment'];
        $avatarPath  = getAvatarPath($row['avatar'], $row['gravatar']);

        if ($fcmsUser->id == $row['created'] || checkAccess($fcmsUser->id) < 2)
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

    displayFooter();
}

/**
 * displayPolls 
 * 
 * @return void
 */
function displayPolls ()
{
    global $fcmsUser, $fcmsError, $pollObj;

    displayHeader();

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    $pollsData = $pollObj->getPolls($page);
    if ($pollsData === false)
    {
        $fcmsError->displayErrors();
        displayFooter();
        return;
    }

    $ids = $pollsData['ids'];
    unset($pollsData['ids']);

    $votesLkup = $pollObj->getPollsTotalVotes($ids);
    if ($votesLkup === false)
    {
        $fcmsError->displayErrors();
        displayFooter();
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
        $date = fixDate(T_('M. j, Y, g:i a'), $fcmsUser->tzOffset, $row['started']);

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

    displayFooter();
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
    global $fcmsUser, $fcmsError, $pollObj;

    displayHeader();

    $id = (int)$_GET['id'];

    $pollData = $pollObj->getPollData($id);
    if ($pollData === false)
    {
        $fcmsError->displayErrors();
        displayFooter();
        return;
    }

    $pollId = key($pollData);

    // Get comments
    $comments = $pollObj->getPollCommentsData($pollId);
    if ($comments === false)
    {
        $fcmsError->displayErrors();
        displayFooter();
        return;
    }

    $commentsTotal = $comments['total'];
    unset($comments['total']);

    $pollOptions = '';
    $class       = 'sub1';
    $disabled    = '';
    $submitValue = T_('Vote');

    // Show results - user already voted
    if (isset($pollData['users_who_voted'][$fcmsUser->id]) || $displayResults)
    {
        $submitValue = T_('Already Voted');
        $class       = 'disabled';
        $disabled    = 'disabled="disabled"';

        $pollOptions = $pollObj->formatPollResults($pollData);
        if ($pollOptions === false)
        {
            $fcmsError->displayErrors();
            displayFooter();
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
        $date        = fixDate(T_('F j, Y g:i a'), $fcmsUser->tzOffset, $row['created']);
        $displayname = $row['fname'].' '.$row['lname'];
        $comment     = $row['comment'];
        $avatarPath  = getAvatarPath($row['avatar'], $row['gravatar']);

        if ($fcmsUser->id == $row['created'] || checkAccess($fcmsUser->id) < 2)
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

    displayFooter();
}

/**
 * displayVoteSubmit 
 * 
 * @return void
 */
function displayVoteSubmit ()
{
    global $fcmsUser, $fcmsError, $pollObj;

    $optionId = (int)$_POST['option'];
    $pollId   = (int)$_POST['id'];

    $result = $pollObj->placeVote($optionId, $pollId);
    if ($result === false)
    {
        if ($fcmsError->hasErrors())
        {
            displayHeader();
            $fcmsError->displayErrors();
            displayFooter();
            return;
        }
        else
        {
            // TODO
            // Use session redirect to form
            echo '<p class="info-alert">'.T_('You have already voted.').'</p>';
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
    global $fcmsUser, $fcmsError;

    $pollId   = (int)$_GET['id'];
    $comments = strip_tags($_POST['comments']);
    $comments = escape_string($comments);

    if (empty($comments))
    {
        header("Location: polls.php?id=$pollId");
    }

    $sql = "INSERT INTO `fcms_poll_comment` (
                `poll_id`, `comment`, `created`, `created_id`
            ) 
            VALUES (
                '$pollId', 
                '$comments', 
                NOW(), 
                '$fcmsUser->id'
            )";

    if (!mysql_query($sql))
    {
        displayHeader();

        $msg       = T_('Could not add comment.');
        $debugInfo = $sql."\n".mysql_error();

        $fcmsError->add($msg, $debugInfo);
        $fcmsError->displayErrors();

        displayFooter();
        return;
    }

    header("Location: polls.php?id=$pollId");
}
