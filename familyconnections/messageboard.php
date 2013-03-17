<?php
/**
 * Members
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
session_start();

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('datetime', 'messageboard');

init();

$mBoard = new MessageBoard($fcmsError, $fcmsDatabase, $fcmsUser);
$page   = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $mBoard);

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
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsMessageBoard)
    {
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;
        $this->fcmsMessageBoard = $fcmsMessageBoard;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Message Board'),
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
        // New Post
        if (isset($_GET['reply']))
        {
            $this->displayNewPostForm();
        }
        elseif (isset($_POST['reply_submit']))
        {
            $this->displayNewPostSubmit();
        }
        elseif (isset($_POST['post_submit']))
        {
            $this->displayNewThreadSubmit();
        }
        // Edit Post
        elseif (isset($_POST['editpost']))
        {
            $this->displayEditPostForm();
        }
        elseif (isset($_POST['edit_submit']))
        {
            $this->displayEditPostSubmit();
        }
        // Delete
        elseif (isset($_POST['delpost']) && !isset($_POST['confirmed']))
        {
            $this->displayConfirmDelete();
        }
        elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed']))
        {
            $this->displayDeletePostSubmit();
        }
        // Administrate Thread
        elseif (isset($_POST['submit_admin']) && $this->fcmsUser->access < 2)
        {
            $this->displayAdministrateThreadSubmit();
        }
        // Admin Edit Subject
        elseif (isset($_POST['edit_admin_submit']))
        {
            $this->displayAdminEditSubjectSubmit();
        }
        // Search results
        elseif (isset($_POST['search']))
        {
            if (isset($_POST['advanced']))
            {
                $this->displayAdvancedSearchSubmit();
            }
            else
            {
                $this->displaySearchSubmit();
            }
        }
        elseif (isset($_GET['search']))
        {
            $this->displayAdvancedSearchForm();
        }
        elseif (isset($_GET['thread']))
        {
            $this->displayThread();
        }
        else
        {
            $this->displayThreads();
        }
    }

    /**
     * displayHeader 
     * 
     * @param string $js Javascript to overwrite the default
     * 
     * @return void
     */
    function displayHeader ($js = '')
    {
        $TMPL = $this->fcmsTemplate;

        $TMPL['javascript'] = $js;

        // Default js
        if ($js == '')
        {
            $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
    if (!$$(\'.delpost input[type="submit"]\')) { return; }
    $$(\'.delpost input[type="submit"]\').each(function(item) {
        item.onclick = function() { return confirm(\''.T_('Are you sure you want to DELETE this?').'\'); };
        var hid = document.createElement(\'input\');
        hid.setAttribute(\'type\', \'hidden\');
        hid.setAttribute(\'name\', \'confirmed\');
        hid.setAttribute(\'value\', \'true\');
        item.insert({\'after\':hid});
    });
    if ($(\'toolbar\')) {
        $(\'toolbar\').removeClassName("hideme");
    }
    if ($(\'smileys\')) {
        $(\'smileys\').removeClassName("hideme");
    }
    if ($(\'upimages\')) {
        $(\'upimages\').removeClassName("hideme");
    }
    return true;
});
//]]>
</script>';
        }

        include getTheme($this->fcmsUser->id).'header.php';

        echo '
        <div id="messageboard" class="centercontent">';
    }

    /**
     * displayFooter 
     * 
     * @return void
     */
    function displayFooter ()
    {
        $TMPL = $this->fcmsTemplate;

        echo '
        </div><!-- #messageboard .centercontent -->';

        include getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayThreads 
     * 
     * @return void
     */
    function displayThreads ()
    {
        $page = getPage();

        $this->displayHeader();

        if (isset($_SESSION['success']))
        {
            displayOkMessage();

            unset($_SESSION['success']);
        }

        $this->fcmsMessageBoard->showThreads('announcement');
        $this->fcmsMessageBoard->showThreads('thread', $page);

        $this->displayFooter();
    }

    /**
     * displayThread 
     * 
     * Displays the posts for a specific thread.
     * 
     * @return void
     */
    function displayThread ()
    {
        $this->displayHeader();

        $threadId = (int)$_GET['thread'];
        $page     = getPage();

        if (isset($_SESSION['success']))
        {
            displayOkMessage();

            unset($_SESSION['success']);
        }

        $this->fcmsMessageBoard->showPosts($threadId, $page);

        $this->displayFooter();
    }

    /**
     * displayNewThreadSubmit 
     * 
     * @return void
     */
    function displayNewThreadSubmit ()
    {
        $post       = $_POST['post'];
        $subject    = $_POST['subject'];

        if (isset($_POST['sticky']))
        {
            $subject = "#ANOUNCE#".$subject;
        }

        // Create new thread
        $sql = "INSERT INTO `fcms_board_threads` 
                    (`subject`, `started_by`, `updated`, `updated_by`) 
                VALUES
                    (?, ?, NOW(), ?)";

        $params = array(
            $subject,
            $this->fcmsUser->id,
            $this->fcmsUser->id
        );

        $newThreadId = $this->fcmsDatabase->insert($sql, $params);
        if ($newThreadId === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Create new post
        $sql = "INSERT INTO `fcms_board_posts`
                    (`date`, `thread`, `user`, `post`) 
                VALUES 
                    (NOW(), ?, ?, ?)";

        $params = array(
            $newThreadId,
            $this->fcmsUser->id,
            $post
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Email members
        $sql = "SELECT u.`email`, s.`user` 
                FROM `fcms_user_settings` AS s, `fcms_users` AS u 
                WHERE `email_updates` = '1'
                AND u.`id` = s.`user`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (count($rows) > 0)
        {
            foreach ($rows as $r)
            {
                $name = getUserDisplayName($this->fcmsUser->id);
                $to   = getUserDisplayName($r['user']);

                // Email is sent as plain text
                $emailHeaders  = getEmailHeaders();
                $emailSubject  = sprintf(T_('%s started the new thread %s.'), $name, $subject);
                $email         = $r['email'];
                $url           = getDomainAndDir();

                $msg = T_('Dear').' '.$to.',

'.$emailSubject.'

'.$url.'messageboard.php?thread='.$newThreadId.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';

                mail($email, $subject, $msg, $emailHeaders);
            }
        }

        // Display the new thread
        header("Location: messageboard.php?thread=$newThreadId");
    }

    /**
     * displayNewPostSubmit 
     * 
     * @return void
     */
    function displayNewPostSubmit ()
    {
        $post     = $_POST['post'];
        $threadId = (int)$_POST['thread_id'];

        // Update Thread info
        $sql = "UPDATE `fcms_board_threads` 
                SET `updated` = NOW(), 
                    `updated_by` = ?
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->update($sql, array($this->fcmsUser->id, $threadId)))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Insert new Post
        $sql = "INSERT INTO `fcms_board_posts`
                    (`date`, `thread`, `user`, `post`)
                VALUES
                    (NOW(), ?, ?, ?)";

        $params = array(
            $threadId, 
            $this->fcmsUser->id, 
            $post
        );

        if (!$this->fcmsDatabase->insert($sql, $params))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        // Email members
        $sql = "SELECT u.`email`, s.`user` 
                FROM `fcms_user_settings` AS s, `fcms_users` AS u 
                WHERE `email_updates` = '1'
                AND u.`id` = s.`user`";

        $rows = $this->fcmsDatabase->getRows($sql);

        if (count($rows) > 0)
        {
            $name = getUserDisplayName($this->fcmsUser->id);

            $sql = "SELECT `subject` 
                    FROM `fcms_board_threads` 
                    WHERE `id` = ?";

            $threadInfo = $this->fcmsDatabase->getRow($sql, $threadId);
            if ($threadInfo === false)
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            $threadSubject = $threadInfo['subject'];

            $pos = strpos($threadSubject, '#ANOUNCE#'); 
            if ($pos !== false)
            {
                $threadSubject = substr($threadSubject, 9, strlen($threadSubject)-9);
            } 

            $emailHeaders  = getEmailHeaders();
            $subject       = sprintf(T_('%s has replied to the thread: %s'), $name, $threadSubject);
            $url           = getDomainAndDir();

            foreach ($rows as $r)
            {
                $email  = $r['email'];
                $to     = getUserDisplayName($r['user']);

                $msg = T_('Dear').' '.$to.',

'.$subject.'

'.$url.'messageboard.php?thread='.$threadId.'

----
'.T_('To stop receiving these notifications, visit the following url and change your \'Email Update\' setting to No:').'

'.$url.'settings.php

';
                mail($email, $subject, $msg, $emailHeaders);
            }
        }

        header("Location: messageboard.php?thread=$threadId");
    }

    /**
     * displayNewPostForm 
     * 
     * Used to create new posts.  Used when creating a new thread also.
     * 
     * @return void
     */
    function displayNewPostForm ()
    {
        $this->displayHeader();

        if ($this->fcmsUser->access >= 8 && $this->fcmsUser->access == 5)
        {
            echo '
            <p class="error-alert">'.T_('You do not have access to view this page.').'</p>';
            $this->displayFooter();
            return;
        }

        if ($_GET['reply'] == 'new')
        {
            $this->fcmsMessageBoard->displayForm('new');
        }
        else
        {
            $reply = (int)$_GET['reply'];

            if (isset($_POST['quotepost']))
            {
                $id = (int)$_POST['id'];

                $this->fcmsMessageBoard->displayForm('reply', $reply, $id);
            }
            else
            {
                $this->fcmsMessageBoard->displayForm('reply', $reply);
            }
        }

        $this->displayFooter();
    }

    /**
     * displayEditPostForm 
     * 
     * Displays the form for editing a post.
     * 
     * @return void
     */
    function displayEditPostForm ()
    {
        $this->displayHeader();

        $id = (int)$_POST['id'];

        $sql = "SELECT `post`, `thread`
                FROM `fcms_board_posts` 
                WHERE `id` = ?
                LIMIT 1";

        $row = $this->fcmsDatabase->getRow($sql, $id);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $this->fcmsMessageBoard->displayForm('edit', $row['thread'], $id, $row['post']);

        $this->displayFooter();
    }

    /**
     * displayEditPostSubmit 
     * 
     * TODO - see below
     * 
     * @return void
     */
    function displayEditPostSubmit ()
    {
        $id       = (int)$_POST['id'];
        $threadId = (int)$_POST['thread_id'];
        $post     = $_POST['post'];

        // TODO
        // Need to find a better way to add the edited by text
        // this method could mess up if the site changes languages at some point
        $pos = strpos($post, "\n\n[size=small][i]".T_('Edited'));
        if ($pos === false)
        {
            $post = $post."\n\n[size=small][i]".T_('Edited')." ".fixDate('n/d/Y g:ia', $this->fcmsUser->tzOffset)."[/i][/size]";
        }
        else
        {
            $post = substr($post, 0, $pos);
            $post = $post."[size=small][i]".T_('Edited')." ".fixDate('n/d/Y g:ia', $this->fcmsUser->tzOffset)."[/i][/size]";
        }

        // Update Post
        $sql = "UPDATE `fcms_board_posts` 
                SET `post` = ?
                WHERE `id` = ?";
        if (!$this->fcmsDatabase->update($sql, array($post, $id)))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        header("Location: messageboard.php?thread=$threadId");
    }

    /**
     * displayAdminEditSubjectSubmit 
     * 
     * The submit screen for editing the subject of a thread.
     * 
     * @return void
     */
    function displayAdminEditSubjectSubmit ()
    {
        $threadId = (int)$_POST['thread'];

        if (isset($_POST['sticky']))
        {
            $subject = "#ANOUNCE#".$_POST['subject'];
        }
        else
        {
            $subject = $_POST['subject'];
        }

        $sql = "UPDATE `fcms_board_threads` 
                SET `subject` = ?
                WHERE `id` = ?";

        if (!$this->fcmsDatabase->update($sql, array($subject, $threadId)))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        header("Location: messageboard.php?thread=$threadId");
    }

    /**
     * displayConfirmDelete 
     * 
     * The delete post confirmation screen, used when user doesn't have js turned on.
     * 
     * @return void
     */
    function displayConfirmDelete ()
    {
        $threadId = (int)$_POST['thread'];
        $id       = (int)$_POST['id'];

        $this->displayHeader();

        echo '
                <div class="info-alert">
                    <form action="messageboard.php?thread='.$threadId.'" method="post">
                        <h2>'.T_('Are you sure you want to DELETE this?').'</h2>
                        <p><b><i>'.T_('This can NOT be undone.').'</i></b></p>
                        <div>
                            <input type="hidden" name="id" value="'.$id.'"/>
                            <input type="hidden" name="thread" value="'.$threadId.'"/>
                            <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.T_('Yes').'"/>
                            <a style="float:right;" href="messageboard.php?thread='.$threadId.'">'.T_('Cancel').'</a>
                        </div>
                    </form>
                </div>';
        $this->displayFooter();
    }

    /**
     * displayDeletePostSubmit 
     * 
     * The submit screen for deleting a post.
     * 
     * @return void
     */
    function displayDeletePostSubmit ()
    {
        $id       = (int)$_POST['id'];
        $threadId = (int)$_POST['thread'];

        // Get id of last post in the current thread
        $sql = "SELECT MAX(`id`) AS max 
                FROM `fcms_board_posts` 
                WHERE `thread` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $threadId);
        if ($row === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $max = $row['max'];

        // Get total post count for this thread
        $sql = "SELECT `id` 
                FROM `fcms_board_posts` 
                WHERE `thread` = ?";

        $rows = $this->fcmsDatabase->getRows($sql, $threadId);
        if ($rows === false)
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $total = count($rows);

        // If this thread only has one post
        if ($total == 1)
        {
            // Delete the entire thread
            $sql = "DELETE FROM `fcms_board_threads` 
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->delete($sql, $threadId))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            $_SESSION['success'] = 1;

            header("Location: messageboard.php");

            return;
        }
        // If we are deleting the last post in the thread
        elseif ($id == $max)
        {
            // Delete post
            $sql = "DELETE FROM `fcms_board_posts`  
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->delete($sql, $id))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            // Get new last post in the thread
            $sql = "SELECT MAX(`id`) AS max 
                    FROM `fcms_board_posts` 
                    WHERE `thread` = ?";

            $row = $this->fcmsDatabase->getRow($sql, $threadId);
            if ($row === false)
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            $newmax = $row['max'];

            // Get info from new last post
            $sql = "SELECT `date`, `user` 
                    FROM `fcms_board_posts` 
                    WHERE `id` = ?";

            $r = $this->fcmsDatabase->getRow($sql, $newmax);
            if ($r === false)
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            // Update the thread with last post info
            $sql = "UPDATE `fcms_board_threads` 
                    SET `updated` = ?,
                        `updated_by` = ?
                    WHERE `id` = ?";

            $params = array($r['date'], $r['user'], $threadId);

            if (!$this->fcmsDatabase->update($sql, $params))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }
        }
        // We are deleting a post in the middle of the thread
        else
        {
            $sql = "DELETE FROM `fcms_board_posts`  
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->delete($sql, $id))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }
        }

        $_SESSION['success'] = 1;

        header("Location: messageboard.php?thread=$threadId");
    }

    /**
     * displayAdministrateThreadSubmit 
     * 
     * The submit screen for administering a thread.
     * 
     * @return void
     */
    function displayAdministrateThreadSubmit ()
    {
        $threadId    = (int)$_POST['thread'];
        $adminOption = $_POST['admin_option'];

        // Did they submit a blank form?
        if (empty($adminOption))
        {
            header("Location: messageboard.php?thread=$threadId");
            return;
        }

        // Changing Thread type
        if ($adminOption == 'normal' || $adminOption == 'announcement')
        {
            $sql = "SELECT `subject`
                    FROM `fcms_board_threads`
                    WHERE `id` = ?";

            $row = $this->fcmsDatabase->getRow($sql, $threadId);
            if ($row === false)
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            if (count($row) < 1)
            {
                $this->displayHeader();
                echo '<p class="error-alert">'.T_('Thread does not exist.').'</p>';
                $this->displayFooter();

                return;
            }

            // Normal Thread
            if ($adminOption == 'normal')
            {
                $subject = $this->fcmsMessageBoard->fixSubject($row['subject']);
            }
            // Announcement
            else
            {
                $subject = '#ANOUNCE#'.$row['subject'];
            }

            $sql = "UPDATE `fcms_board_threads` 
                    SET `subject` = ? 
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->update($sql, array($subject, $threadId)))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            $_SESSION['success'] = 1;

            header("Location: messageboard.php?thread=$threadId");
            return;
        } 

        // Edit Thread Subject
        if ($adminOption == 'subject')
        {
            $this->displayHeader();
            $this->fcmsMessageBoard->displayAdminEditSubjectForm($threadId);
            $this->displayFooter();
            return;
        }

        // Delete thread
        if ($adminOption == 'delete')
        {
            $sql = "DELETE FROM `fcms_board_posts` 
                    WHERE `thread` = ?";

            if (!$this->fcmsDatabase->delete($sql, $threadId))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            $sql = "DELETE FROM `fcms_board_threads` 
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->delete($sql, $threadId))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();

                return;
            }

            $_SESSION['success'] = 1;
        }

        header("Location: messageboard.php");
    }

    /**
     * displaySearchSubmit 
     * 
     * Display the results for the search query.
     * 
     * @return void
     */
    function displaySearchSubmit ()
    {
        $search = trim($_POST['search']);

        $advanced = false;

        // validate start date
        if (isset($_POST['start']))
        {
            $start = $_POST['start'];
            $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $start);

            if ($found === false || $found < 1)
            {
                $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($start));
                $this->displayAdvancedSearchForm($error);

                return;
            }

            $advanced = true;
        }

        // validate end date
        if (isset($_POST['end']))
        {
            $end   = $_POST['end'];
            $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $end);

            if ($found === false || $found < 1)
            {
                $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($end));
                $this->displayAdvancedSearchForm($error);

                return;
            }

            $advanced = true;
        }

        $this->displayHeader();

        echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="messageboard.php">'.T_('Message Board Home').'</a></li>
                </ul>
            </div>
            <form method="post" action="messageboard.php">
                <p id="big_search">
                    <input type="text" id="search" name="search" value="'.cleanOutput($search).'"/>
                    <input type="submit" value="'.T_('Search').'"/><br/>
                    <a href="?search=advanced">'.T_('Advanced Search').'</a>
                </p>
            </form>';

        // Thread subject
        $sql = "SELECT t.`id`, t.`subject`, t.`started_by`, p.`date`, p.`post`
                FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t
                WHERE p.`thread` = t.`id`
                AND `subject` LIKE ?";

        $params = array("%$search%");

        if ($advanced)
        {
            $sql .= "
                AND p.`date` >= ?
                AND p.`date` <= ?";

            $params[] = $start;
            $params[] = $end;
        }

        // Post body
        $sql .= "
                GROUP BY p.`thread`
                UNION
                SELECT t.`id`, t.`subject`, t.`started_by`, p.`date`, p.`post`
                FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t
                WHERE p.`thread` = t.`id`
                AND `post` LIKE ?";

        $params[] = "%$search%";

        if ($advanced)
        {
            $sql .= "
                AND p.`date` >= ?
                AND p.`date` <= ?";

            $params[] = $start;
            $params[] = $end;
        }

        $rows = $this->fcmsDatabase->getRows($sql, $params);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (count($rows) <= 0)
        {
            echo '
            <div class="search_result">
                <p>'.T_('Could not find anything matching your search.').'</p>
            </div>';
        
        }

        foreach ($rows as $r)
        {
            // Remove #ANNOUNCE#
            $subject = $this->fcmsMessageBoard->fixSubject($r['subject']);
            // Clean html
            $subject = cleanOutput($subject, 'html');
            // Put in our html (should be the only html rendered)
            $subject = highlight($search, $subject);

            // Remove orig bbcode
            $post = removeBBCode($r['post']);
            // Clean html
            $post = cleanOutput($post, 'html');
            // Put in our html (should be the only html rendered)
            $post = highlight($search, $post);
            $date = fixDate('n/d/Y g:ia', $this->fcmsUser->tzOffset, $r['date']);

            echo '
                <div class="search_result">
                    <a href="?thread='.$r['id'].'">'.$subject.'</a>
                    <p>'.$post.'</p>
                    <span>'.$date.'</span>
                </div>';
        }

        $this->displayFooter();
    }

    /**
     * displayAdvancedSearchForm 
     * 
     * @param string $error Any previous error for this form.
     * 
     * @return void
     */
    function displayAdvancedSearchForm ($error = '', $search = '', $start = null, $end = null, $footer = true)
    {
        $js = '
<link rel="stylesheet" type="text/css" href="ui/datechooser.css"/>
<script type="text/javascript" src="ui/js/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    var dc1 = new DateChooser();
    dc1.setUpdateField({\'start\':\'Y-m-d\'});
    dc1.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'start\');
    var dc2 = new DateChooser();
    dc2.setUpdateField({\'end\':\'Y-m-d\'});
    dc2.setIcon(\'ui/themes/default/images/datepicker.jpg\', \'end\');
});
//]]>
</script>';

        $this->displayHeader($js);

        if (empty($end))
        {
            $end   = fixDate('Y-m-d', $this->fcmsUser->tzOffset, gmdate('Y-m-d H:i:s'));
        }

        if (empty($start))
        {
            $start = date('Y-m-d', strtotime("$end -30 day"));
        }

        if ($error != '')
        {
            $error = '<div class="error-alert">'.$error.'</div>';
        }

        echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="messageboard.php">'.T_('Message Board Home').'</a></li>
                </ul>
            </div>
            '.$error.'
            <form method="post" action="messageboard.php">
                <fieldset>
                    <legend><span>'.T_('Advanced Search').'</span></legend>
                    <div>
                        <label for="search">'.T_('Search For').'</label><br/>
                        <input type="text" id="search" name="search" value="'.$search.'"/>
                    </div><br/>
                    <div>
                        <label for="date">'.T_('Date').'</label><br/> 
                        <input type="text" id="start" name="start" value="'.$start.'" size="6" maxlength="10"/> - 
                        <input type="text" id="end" name="end" value="'.$end.'" size="6" maxlength="10"/>
                    </div><br/>
                    <p>
                        <input type="submit" class="sub1" value="'.T_('Search').'" name="advanced" id="advanced"/>
                    </p>
                </fieldset>
            </form>';

        if ($footer)
        {
            $this->displayFooter();
        }
    }

    /**
     * displayAdvancedSearchSubmit 
     * 
     * @return void
     */
    function displayAdvancedSearchSubmit ()
    {
        $start  = $_POST['start'];
        $end    = $_POST['end'];
        $search = $_POST['search'];

        // validate dates
        $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $start);
        if ($found === false || $found < 1)
        {
            $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($start));
            $this->displayAdvancedSearchForm($error);
            return;
        }
        $found = preg_match('/^\d{4}-(1[012]|0?\d)-(3[01]|[012]?\d)$/', $end);
        if ($found === false || $found < 1)
        {
            $error = sprintf(T_('Invalid Date [%s]'), cleanOutput($end));
            $this->displayAdvancedSearchForm($error);
            return;
        }

        // header is displayed by displayAdvancedSearchForm()
        $this->displayAdvancedSearchForm('', $search, $start, $end, false);

        $sql = "SELECT t.`id`, t.`subject`, t.`started_by`, t.`updated`, p.`post`,
                    'thread' AS type
                FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p
                WHERE p.`thread` = t.`id`
                AND `subject` LIKE ?
                UNION
                SELECT t.`id`, t.`subject`, t.`started_by`, p.`date` AS updated, p.`post`,
                    'post' AS type
                FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p
                WHERE p.`thread` = t.`id`
                AND `post` LIKE ?
                AND `date` >= ?
                AND `date` <= ?";

        $params = array(
            "%$search%",
            "%$search%",
            "$start 00:00:00",
            "$end 24:59:59"
        );

        $rows = $this->fcmsDatabase->getRows($sql, $params);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (count($rows) <= 0)
        {
            echo '
            <div class="search_result">
                <p>'.T_('Could not find anything matching your search.').'</p>
            </div>';
        
        }

        $threadsFound = array();

        foreach ($rows as $r)
        {
            // if the search is found both in the subject and post
            // skip the post, so we don't show doubles
            if ($r['type'] == 'post')
            {
                if (isset($threadsFound[$r['id']]))
                {
                    continue;
                }
            }

            $threadsFound[$r['id']] = 1;

            // Remove #ANNOUNCE#
            $subject = $this->fcmsMessageBoard->fixSubject($r['subject']);
            // Clean html
            $subject = cleanOutput($subject, 'html');
            // Put in our html (should be the only html rendered)
            $subject = highlight($search, $subject);

            // Remove orig bbcode
            $post = removeBBCode($r['post']);
            // Clean html
            $post = cleanOutput($post, 'html');
            // Put in our html (should be the only html rendered)
            $post = highlight($search, $post);

            $date = fixDate('n/d/Y g:ia', $this->fcmsUser->tzOffset, $r['updated']);

            echo '
            <div class="search_result">
                <a href="?thread='.$r['id'].'">'.$subject.'</a>
                <p>'.$post.'</p>
                <span>'.$date.'</span>
            </div>';
        }

        $this->displayFooter();
    }
}
