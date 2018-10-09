<?php
/**
 * Polls.
 *
 * PHP version 5
 *
 * @category  FCMS
 *
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @copyright 2012 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
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
     * Constructor.
     *
     * @return void
     */
    public function __construct($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsPoll)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser = $fcmsUser;
        $this->fcmsPoll = $fcmsPoll;

        $this->control();
    }

    /**
     * control.
     *
     * The controlling structure for this script.
     *
     * @return void
     */
    public function control()
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
     * displayHeader.
     *
     * Displays the header of the page, including the leftcolumn navigation.
     *
     * @return void
     */
    public function displayHeader()
    {
        $params = [
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => cleanOutput(getSiteName()),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Polls'),
            'pageId'        => 'poll',
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y'),
        ];

        displayPageHeader($params);

        $navParams = [
            'pageNavigation' => [
                'section' => [
                    [
                        'url'  => 'polls.php',
                        'text' => T_('Latest'),
                    ],
                    [
                        'url'  => 'polls.php?action=pastpolls',
                        'text' => T_('Past Polls'),
                    ],
                ],
            ],
        ];

        if ($this->fcmsUser->access < 2)
        {
            $navParams['pageNavigation']['action'] = [
                [
                    'url'  => 'admin/polls.php',
                    'text' => T_('Administrate'),
                ],
            ];
        }

        loadTemplate('global', 'page-navigation', $navParams);
    }

    /**
     * displayFooter.
     *
     * @return void
     */
    public function displayFooter()
    {
        $params = [
            'path'      => URL_PREFIX,
            'version'   => getCurrentVersion(),
            'year'      => date('Y'),
        ];

        loadTemplate('global', 'footer', $params);
    }

    /**
     * displayLatestPoll.
     *
     * @return void
     */
    public function displayLatestPoll()
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
            // we have no polls
            return;
        }

        $this->displayPollTemplate($data);
    }

    /**
     * displayPollTemplate.
     *
     * @param array  $data
     * @param string $displayResults
     *
     * @return void
     */
    public function displayPollTemplate($data, $displayResults = false)
    {
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

        $pollOptions = [];

        // Show results - user already voted
        if (isset($data['users_who_voted'][$this->fcmsUser->id]) || $displayResults)
        {
            $pollResults = $this->fcmsPoll->formatPollResults($data);
            if ($pollResults === false)
            {
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            $pollParams = [
                'pollFormClass'     => 'poll',
                'pollId'            => $pollId,
                'textPolls'         => T_('Polls'),
                'pollQuestion'      => cleanOutput($data[$pollId]['question'], 'html'),
                'textCommentsCount' => sprintf(T_('Comments (%s)'), $commentsTotal),
                'textAlreadyVoted'  => T_('Already Voted'),
                'pollResults'       => $pollResults,
            ];

            loadTemplate('poll', 'result', $pollParams);
        }
        // Show options
        else
        {
            foreach ($data[$pollId]['options'] as $optionId => $optionData)
            {
                $pollOptions[] = [
                    'id'   => (int) $optionId,
                    'text' => cleanOutput($optionData['option'], 'html'),
                ];
            }

            $pollParams = [
                'pollFormClass'   => 'poll',
                'pollId'          => $pollId,
                'textPolls'       => T_('Polls'),
                'pollQuestion'    => cleanOutput($data[$pollId]['question'], 'html'),
                'textPollVote'    => T_('Vote'),
                'textPollResults' => T_('Results'),
                'textPastPolls'   => T_('Past Polls'),
                'pollOptions'     => $pollOptions,
            ];

            loadTemplate('poll', 'view', $pollParams);
        }

        // Comments
        $commentsParams = [];

        foreach ($comments as $row)
        {
            $params = [
                'id'            => (int) $row['id'],
                'formClass'     => 'delcom',
                'formUrl'       => 'polls.php?id='.$pollId,
                'avatar'        => getAvatarPath($row['avatar'], $row['gravatar']),
                'displayname'   => $row['fname'].' '.$row['lname'],
                'date'          => fixDate(T_('F j, Y g:i a'), $this->fcmsUser->tzOffset, $row['created']),
                'comment'       => parse($row['comment']),
            ];

            if ($this->fcmsUser->id == $row['created'] || $this->fcmsUser->access < 2)
            {
                $params['textDelete'] = T_('Delete');
                $params['deleteClass'] = 'gal_delcombtn';
                $params['deleteTitle'] = T_('Delete this Comment');
            }

            $commentsParams[] = $params;
        }

        $templateParams = [
            'comments'              => $commentsParams,
            'addCommentUrl'         => 'polls.php?id='.$pollId,
            'textAddCommentLabel'   => T_('Add Comment'),
            'addCommentSubmitClass' => 'sub1',
            'addCommentSubmitValue' => T_('Comment'),
            'addCommentSubmitTitle' => T_('Add Comment'),
        ];

        loadTemplate('global', 'comments', $templateParams);

        $this->displayFooter();
    }

    /**
     * displayPolls.
     *
     * @return void
     */
    public function displayPolls()
    {
        $this->displayHeader();

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

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

        $pollParams = [];

        foreach ($pollsData as $row)
        {
            $pollParams[] = [
                'url'      => '?id='.(int) $row['id'],
                'question' => cleanOutput($row['question'], 'html'),
                'date'     => fixDate(T_('M. j, Y, g:i a'), $this->fcmsUser->tzOffset, $row['started']),
                'vote'     => $votesLkup[$row['id']],
            ];
        }

        $templateParams = [
            'textPastPolls' => T_('Past Polls'),
            'textQuestion'  => T_('Question'),
            'textDate'      => T_('Date'),
            'textVotes'     => T_('Votes'),
            'polls'         => $pollParams,
        ];

        loadTemplate('poll', 'polls', $templateParams);

        $this->displayFooter();
    }

    /**
     * displayPoll.
     *
     * @param bool $displayResults
     *
     * @return void
     */
    public function displayPoll($displayResults = false)
    {
        $this->displayHeader();

        $id = (int) $_GET['id'];

        $pollData = $this->fcmsPoll->getPollData($id);
        if ($pollData === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $this->displayPollTemplate($pollData, $displayResults);
    }

    /**
     * displayVoteSubmit.
     *
     * @return void
     */
    public function displayVoteSubmit()
    {
        $optionId = (int) $_POST['option'];
        $pollId = (int) $_POST['id'];

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
     * displayAddCommentSubmit.
     *
     * @return void
     */
    public function displayAddCommentSubmit()
    {
        $pollId = (int) $_GET['id'];
        $comments = strip_tags($_POST['comments']);

        if (empty($comments))
        {
            header("Location: polls.php?id=$pollId");
        }

        $sql = 'INSERT INTO `fcms_poll_comment`
                    (`poll_id`, `comment`, `created`, `created_id`) 
                VALUES
                    (?, ?, NOW(), ?)';

        $params = [
            $pollId,
            $comments,
            $this->fcmsUser->id,
        ];

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
