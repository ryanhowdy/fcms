<?php
include_once('database_class.php');
include_once('utils.php');
include_once('datetime.php');

/**
 * FamilyNews 
 * 
 * @package     Family Connections
 * @copyright   2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class FamilyNews
{
    var $db;
    var $db2;
    var $tzOffset;
    var $currentUserId;

    /**
     * FamilyNews 
     * 
     * @param int $currentUserId 
     *
     * @return void
     */
    function FamilyNews ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db  = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $this->db2 = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->tzOffset      = getTimezone($this->currentUserId);
    }

    /**
     * displayNewsList 
     *
     * Displays a navigation list of users who have news.
     * 
     * @return void
     */
    function displayNewsList ()
    {
        $sql = "SELECT u.`id`, `fname`, `lname`, `displayname`, `username`, MAX(`updated`) AS d 
                FROM `fcms_news` AS n, `fcms_users` AS u, `fcms_user_settings` AS s 
                WHERE u.`id` = n.`user` 
                AND u.`id` = s.`user` GROUP BY id ORDER BY d DESC";

        if (!$this->db->query($sql))
        {
            displaySQLError('News List Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            echo '
            <div id="news-list">
                <h2>'.T_('Family News').'</h2>
                <ul>';

            while ($r = $this->db->get_row())
            {
                $date = fixDate(T_('M. j'), $this->tzOffset, $r['d']);
                $displayname = getUserDisplayName($r['id']);
                echo '
                    <li><a href="familynews.php?getnews='.(int)$r['id'].'">'.$displayname.'</a> &nbsp;<small>'.$date.'</small></li>';
            }
            echo '
                </ul>
            </div>';
        }
    }

    /**
     * displayUserFamilyNews 
     * 
     * Prints the listing of family news for a given user.
     * 
     * @param int $user
     * @param int $page 
     * 
     * @return  void
     */
    function displayUserFamilyNews ($user, $page = 1)
    {
        $user = cleanInput($user, 'int');
        $from = (($page * 5) - 5); 

        // Get family news
        $sql = "SELECT n.`id`, n.`user`, n.`title`, n.`news`, n.`updated`, n.`created`, 
                    n.`external_type`, n.`external_id`
                FROM `fcms_news` AS n, `fcms_users` AS u
                WHERE n.`user` = '$user' 
                    AND n.`user` = u.`id` 
                ORDER BY `updated` DESC 
                LIMIT " . $from . ", 5";

        if (!$this->db->query($sql))
        {
            displaySQLError('News Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        while ($row = $this->db->get_row())
        {
            $this->displayNews($row);
        }

        // Display Pagination
        $sql = "SELECT COUNT(`id`) AS c 
                FROM `fcms_news` 
                WHERE `user` = '$user'";

        if (!$this->db2->query($sql))
        {
            displaySQLError('Count Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        $r = $this->db2->get_row();
        $newscount = $r['c'];
        $total_pages = ceil($newscount / 5);
        displayPagination('familynews.php?getnews='.$user, $page, $total_pages);
    }

    /**
     * displayFamilyNews 
     * 
     * Displays a single family news entry.
     * 
     * @param int $user
     * @param int $id 
     * 
     * @return  void
     */
    function displayFamilyNews ($user, $id)
    {
        $user = cleanInput($user, 'int');
        $id   = cleanInput($id, 'int');

        $sql = "SELECT n.`id`, n.`title`, n.`news`, n.`updated`, n.`created`,
                    n.`external_type`, n.`external_id`
                FROM `fcms_news` AS n, `fcms_users` AS u 
                WHERE n.`id` = '$id' 
                    AND `user` = u.`id`";

        if (!$this->db->query($sql))
        {
            displaySQLError('News Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        $row = $this->db->get_row();

        $updated = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $row['updated']);
        $created = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $row['created']);

        $displayname = getUserDisplayName($user);

        $edit = '';
        $del  = '';

        if ($this->currentUserId == $user || checkAccess($this->currentUserId) < 2)
        {
            $edit = ' &nbsp;
                <form method="post" action="familynews.php">
                    <div>
                        <input type="hidden" name="user" value="'.$user.'"/>
                        <input type="hidden" name="id" value="'.cleanInput($row['id'], 'int').'"/>
                        <input type="hidden" name="title" value="'.cleanOutput($row['title']).'"/>
                        <input type="hidden" name="news" value="'.cleanOutput($row['news']).'"/>
                        <input type="submit" name="editnews" value="'.T_('Edit').'" class="editbtn" title="'.T_('Edit this Family News').'"/>
                    </div>
                </form>';

            $del = ' &nbsp;
                <form class="delnews" method="post" action="familynews.php?getnews='.$user.'">
                    <div>
                        <input type="hidden" name="user" value="'.$user.'"/>
                        <input type="hidden" name="id" value="'.cleanInput($row['id'], 'int').'"/>
                        <input type="submit" name="delnews" value="'.T_('Delete').'" class="delbtn" title="'.T_('Delete this Family News').'"/>
                    </div>
                </form>';
        }

        $newsSource = '';

        // FCMS news
        if (empty($row['external_type']) && empty($row['external_id']))
        {
            $news = parse($row['news']);
        }
        // External news
        else
        {
            $newsSource = '
                    <span style="background-color:#eee; color:#999; font-size:13px;">
                        '.sprintf(T_('Originally from %s, %s.'), $row['external_type'], $created).'
                    </span><br/>';
            $news = $row['news'];
            $edit = ''; // can't edit external
        }

        $title = !empty($row['title']) ? cleanOutput($row['title']) : T_('untitled');

        echo '
            <div class="news-post">
                <h2>
                    <a href="?getnews='.$user.'&amp;newsid='.(int)$row['id'].'">'.$title.'</a>
                </h2>
                <span class="date">
                    '.$updated.' - '.$displayname.$edit.$del.'
                </span>
                <p>
                    '.$newsSource.$news.'
                </p>
                <p class="news-comments">
                    <a href="?getnews='.$user.'&amp;newsid='.cleanInput($row['id'], 'int').'#comments">'.T_('Comments').'</a> - 
                    '.getNewsComments($row['id']).'
                </p>
            </div>
            <h3 id="comments">'.T_('Comments').'</h3>
            <p class="center">
                <form action="?getnews='.$user.'&amp;newsid='.$id.'#footer" method="post">
                    '.T_('Add Comment').'<br/>
                    <input type="text" name="comment" id="comment" size="50" title="'.T_('Add a comment').'"/> 
                    <input type="submit" name="addcom" id="addcom" value="'.T_('Add').'" class="gal_addcombtn"/>
                </form>
            </p>
            <p>&nbsp;</p>';

        // Comments
        $sql = "SELECT c.id, comment, `date`, fname, lname, username, user, avatar  
                FROM fcms_news_comments AS c, fcms_users AS u 
                WHERE news = $id 
                AND c.user = u.id 
                ORDER BY `date`";

        if (!$this->db2->query($sql))
        {
            displaySQLError('Comments Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        { 
            echo '
            <p class="center">'.T_('no comments').'</p>';
            return;
        }

        while($row = $this->db2->get_row())
        {
            $displayname = getUserDisplayName($row['user']);
            $date        = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $row['date']);

            if ($this->currentUserId == $row['user'] || checkAccess($this->currentUserId) < 2)
            {
                echo '
            <div class="comment_block clearfix">
                <form class="delcom" action="?getnews='.$user.'&amp;newsid='.$id.'" method="post">
                    <input type="submit" name="delcom" id="delcom" value="'.T_('Delete').'" class="gal_delcombtn" title="'.T_('Delete this comment').'"/>
                    <img class="avatar" alt="avatar" src="'.getCurrentAvatar($row['user']).'"/>
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>'.cleanOutput($row['comment']).'</p>
                    <input type="hidden" name="id" value="'.(int)$row['id'].'">
                </form>
            </div>';
            }
            else
            {
                echo '
            <div class="comment_block clearfix">
                    <img class="avatar" src="avatar/'.basename($row['avatar']).'">
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>'.cleanOutput($row['comment']).'</p>
                </form>
            </div>';
            }
        }
    }

    /**
     * displayForm 
     * 
     * @param   string  $type 
     * @param   int     $user 
     * @param   int     $newsid 
     * @param   string  $title 
     * @param   string  $news 
     * 
     * @return  void
     */
    function displayForm ($type, $user = 0, $newsid = 0, $title='error', $news = 'error')
    {
        echo '
            <script type="text/javascript" src="inc/js/livevalidation.js"></script>';
        if ($type == 'edit') {
            echo '
            <form method="post" id="editform" action="familynews.php">
                <fieldset>
                    <legend><span>'.T_('Edit News').'</span></legend>';
        } else {
            echo '
            <form method="post" id="addform" action="familynews.php">
                <fieldset>
                    <legend><span>'.T_('Add News').'</span></legend>';
        }
        echo '
                    <p>
                        <label for="title">'.T_('Title').'</label>:
                        <input type="text" name="title" id="title" title="'.T_('Title of your Family News').'"';
        if ($type == 'edit') {
            echo ' value="'.$title.'"';
        }
        echo ' size="50"/>
                    </p>
                    <script type="text/javascript">
                        var ftitle = new LiveValidation(\'title\', { onlyOnSubmit:true });
                        ftitle.add(Validate.Presence, { failureMessage: "" });
                    </script>
                    <script type="text/javascript">var bb = new BBCode();</script>';
        displayBBCodeToolbar();
        echo '
                    <div><textarea name="post" id="post" rows="10" cols="63"';
        if ($type == 'add') {
            echo "></textarea></div>";
        } else {
            echo ">".$news."</textarea></div>";
        }
        echo '
                    <script type="text/javascript">bb.init(\'post\');</script>
                    <p>';
        if ($type == 'add') {
            echo '
                        <input class="sub1" type="submit" name="submitadd" value="'.T_('Add').'"/>';
        } else {
            echo '
                        <input type="hidden" name="id" value="'.(int)$newsid.'"/>
                        <input type="hidden" name="user" value="'.(int)$user.'"/>
                        <input class="sub1" type="submit" name="submitedit" value="'.T_('Edit').'"/>';
        }
        echo '
                         &nbsp;'.T_('or').' &nbsp;
                        <a href="familynews.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * displayLast5News 
     * 
     * @return void
     */
    function displayLast5News ()
    {
        $sql = "SELECT * 
                FROM `fcms_news` 
                ORDER BY `updated` DESC 
                LIMIT 5";

        if (!$this->db->query($sql))
        {
            displaySQLError('News Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '
            <div class="info-alert">
                <h2>'.T_('Welcome to the Family News Section.').'</h2>
                <p><i>'.T_('Currently no one has added any news.').'</i></p>
                <p><a href="?addnews=yes">'.T_('Add Family News').'</a></p>
                <p><a href="settings.php?view=familynews">'.T_('Import News from existing blog').'</a></p>
            </div>';

            return;
        }

        while ($row = $this->db->get_row())
        {
            $this->displayNews($row);
        }
    }

    /**
     * hasNews 
     * 
     * @param  int  $userid 
     * @return void
     */
    function hasNews ($userid)
    {
        $userid = cleanInput($userid, 'int');
        $sql = "SELECT `id` 
                FROM `fcms_news` 
                WHERE `user` = '$userid' 
                LIMIT 1";
        $this->db->query($sql) or displaySQLError(
            'Has News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * importExternalPosts 
     * 
     * Checks if any user has an external blog setup.
     * Imports posts from those blogs if they haven't been imported already.
     * 
     * @return void
     */
    function importExternalPosts ()
    {
        // get date we last checked for external news
        $sql = "SELECT `value` AS 'external_news_date'
                FROM `fcms_config`
                WHERE `name` = 'external_news_date'
                LIMIT 1";
        if (!$this->db->query($sql))
        {
            displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $r = $this->db->get_row();

        $last_checked = strtotime($r['external_news_date']);

        // have checked in the last 8 hours?
        if (time() - $last_checked <= (8*3600))
        {
            return;
        }

        $atomDate = 0;
        if (!empty($r['external_news_date']))
        {
            // RFC 3339 format
            $atomDate = date('Y-m-d\TH:i:s\Z', strtotime($r['external_news_date']));
        }

        // Get import blog settings
        $sql = "SELECT `user`, `blogger`, `tumblr`, `wordpress`, `posterous`
                FROM `fcms_user_settings`";
        if (!$this->db->query($sql))
        {
            displaySQLError('Settings Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        if ($this->db->count_rows() <= 0)
        {
            return;
        }

        $external_ids = $this->getExternalPostIds();

        while ($r = $this->db->get_row())
        {
            // Blogger
            if (!empty($r['blogger']))
            {
                $ret = $this->importBloggerPosts($r['blogger'], $r['user'], $atomDate, $external_ids);
                if ($ret === false)
                {
                    die();
                }
            }

            // Tumblr
            if (!empty($r['tumblr']))
            {
                $ret = $this->importTumblrPosts($r['tumblr'], $r['user'], $atomDate, $external_ids);
                if ($ret === false)
                {
                    die();
                }
            }

            // Wordpress
            if (!empty($r['wordpress']))
            {
                $ret = $this->importWordpressPosts($r['wordpress'], $r['user'], $atomDate, $external_ids);
                if ($ret === false)
                {
                    die();
                }
            }

            // Posterous
            if (!empty($r['posterous']))
            {
                $ret = $this->importPosterousPosts($r['posterous'], $r['user'], $atomDate, $external_ids);
                if ($ret === false)
                {
                    die();
                }
            }
        }

        // Update date we last checked for external ids
        $now = gmdate('Y-m-d H:i:s');
        $sql = "UPDATE `fcms_config`
                SET `value` = '$now'
                WHERE `name` = 'external_news_date'";
        if (!$this->db->query($sql))
        {
            displaySQLError('Config Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        }
    }

    /**
     * displayNews 
     * 
     * Prints out the news info when looping through a list of news.
     * Used when viewing last 5 and users news.
     * 
     * @param array $data 
     * 
     * @return void
     */
    function displayNews ($data)
    {
        $displayname = getUserDisplayName($data['user']);

        $updated = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $data['updated']);
        $created = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $data['created']);

        $newsSource = '';

        // Imported news
        if (strlen($data['external_type']) > 0)
        {
            $newsSource = '
                    <span style="background-color:#eee; color:#999; font-size:13px;">
                        '.sprintf(T_('Originally from %s, %s.'), $data['external_type'], $created).'
                    </span><br/>';
            $news       = strip_tags($data['news']);
        }
        // Family News
        else
        {
            $news = removeBBCode($data['news']);
        }

        if (strlen($data['news']) > 300)
        {
            $news = substr($news, 0, 300);
            $news .= '...<br/><br/><a href="?getnews='.$data['user'].'&amp;newsid='
                        .cleanInput($data['id'], 'int').'">'.T_('Read More').'</a>';
        }

        if (empty($data['title']))
        {
            $data['title'] = T_('untitled');
        }

        echo '
            <div class="news-post">
                <h2>
                    <a href="?getnews='.$data['user'].'&amp;newsid='.cleanInput($data['id'], 'int').'">'
                        .cleanOutput($data['title']).'</a>
                </h2>
                <span class="date">'.$updated.' - '.$displayname.'</span>
                <p>'.$newsSource.$news.'</p>
                <p class="news-comments">
                    <a href="?getnews='.$data['user'].'&amp;newsid='.cleanInput($data['id'], 'int').'#comments">'
                        .T_('Comments').'</a> - '.getNewsComments($data['id']).'
                </p>
            </div>';
    }

    /**
     * getExternalPostIds 
     * 
     * Returns an array of ids for blog posts that were imported from outside blogs.
     * 
     * @return void
     */
    function getExternalPostIds ()
    {
        $external_ids = array();

        // Check existing external posts
        $sql = "SELECT `external_id`, `external_type`
                FROM `fcms_news`
                WHERE `external_id` IS NOT NULL";

        if (!$this->db2->query($sql))
        {
            displaySQLError('Import Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());

            return $external_ids;
        }

        while ($row = $this->db2->get_row())
        {
            $external_ids[$row['external_id']] = $row['external_type'];
        }

        return $external_ids;
    }

    /**
     * importBloggerPosts 
     * 
     * Will return the number of posts imported or false if failures.
     * 
     * @param string $bloggerUrl 
     * @param int    $userid 
     * @param string $atomDate 
     * @param array  $externalIds 
     * 
     * @return int or boolean false
     */
    function importBloggerPosts ($bloggerUrl, $userid, $atomDate, $externalIds)
    {
        // User entered blogger url instead of blog id, lets fix it for them
        if (!ctype_digit($bloggerUrl))
        {
            $xml        = $bloggerUrl;
            $bloggerUrl = '';

            $beginning = substr($xml, 0, 4);
            $ending    = substr($xml, -1);

            if ($beginning !== 'http')
            {
                $xml = 'http://'.$xml;
            }

            if ($ending !== '/')
            {
                $xml = $xml.'/';
            }

            $xml = $xml.'feeds/posts/default';

            if (!$this->url_exists($xml))
            {
                echo '<div class="error-alert">'.sprintf(T_('Invalid url [%s].'), $xml).'</div>';
                return false;
            }

            $feed = simplexml_load_file($xml);
            foreach ($feed->entry as $entry)
            {
                // I'm getting both the blog ID and post ID
                preg_match('/blog-([0-9]+).*post-([0-9]+)/', $entry->id, $match);
                $bloggerUrl = $match[1];
            }
        }

        $url = 'http://www.blogger.com/feeds/'.$bloggerUrl.'/posts/default';

        if ($atomDate > 0)
        {
            $url .= '?published-min='.$atomDate;
        }

        $xml = false;
        if ($this->url_exists($url))
        {
            $xml = simplexml_load_file($url);
        }

        if ($xml === false)
        {
            echo '
                <div class="error-alert">
                    <p>'.T_('Could not import news from external source.').'</p>
                    <p><b>'.T_('Source').'</b>: blogger</p>
                    <p><b>'.T_('URL/Account').'</b>: '.$bloggerUrl.'</p>
                </div>';

            return false;
        }

        // Insert new external posts
        $sql = "INSERT INTO `fcms_news` (`title`, `news`, `user`, 
                    `created`, `updated`, `external_type`, `external_id`)
                VALUES ";

        $importCount = 0;
        foreach ($xml->entry as $post)
        {
            $bid = cleanInput("$post->id");

            // skip ids that already exist
            if (isset($externalIds[$bid]))
            {
                continue;
            }

            $title = cleanInput("$post->title");
            $news  = cleanInput("$post->content");
            $user  = cleanInput($userid, 'int');
            $date  = date('Y-m-d H:i:s', strtotime($post->published));

            $sql .= "('$title', '$news', '$user', '$date', NOW(), 'blogger', '$bid'), ";

            $importCount++;
        }

        // Do we have anything to import
        if ($importCount > 0)
        {
            if (isset($_SESSION['external_id']))
            {
                unset($_SESSION['external_id']);
            }

            // remove extra comma and space
            $sql = substr($sql, 0, -2);

            if (!$this->db2->query($sql))
            {
                displaySQLError('Import Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return false;
            }
        }

        return $importCount;
    }

    /**
     * importTumblrPosts 
     * 
     * Will return the number of posts imported or false if failures.
     * 
     * @param string $tumblrUrl 
     * @param int    $userid
     * @param string $atomDate 
     * @param array  $externalIds 
     * 
     * @return int or boolean false
     */
    function importTumblrPosts ($tumblrUrl, $userid, $atomDate, $externalIds)
    {
        // Tumblr api doesn't allow you to limit the search by date or id
        // it will get the last 20 every time
        $url = $tumblrUrl.'/api/read';

        if ($this->url_exists($url))
        {
            $xml = simplexml_load_file($url);
        }

        if (!isset($xml))
        {
            echo '
                <div class="error-alert">
                    <p>'.T_('Could not import news from external source.').'</p>
                    <p><b>'.T_('Source').'</b>: tumblr</p>
                    <p><b>'.T_('URL/Account').'</b>: '.$tumblrUrl.'</p>
                </div>';

            return false;
        }

        // Insert new external posts
        $sql = "INSERT INTO `fcms_news` (`title`, `news`, `user`, 
                    `date`, `external_type`, `external_id`)
                VALUES ";

        $importCount = 0;
        foreach($xml->posts->post as $post)
        {
            $id   = (float)$post->attributes()->id;
            $date = date('Y-m-d H:i:s', strtotime($post->attributes()->date));
            $user = $userid;

            // skip ids that already exist
            if (isset($externalIds["$id"]))
            {
                continue;
            }

            switch ($post->attributes()->type)
            {
                case 'photo':
                    $news  = '<img src="'.$post->{'photo-url'}[3].'"/>';
                    $title = '';
                    if (isset($post->{'photo-caption'}))
                    {
                        $title = cleanInput($post->{'photo-caption'});
                    }
                    break;

                case 'regular':
                    $title = $post->{'regular-title'};
                    $news  = $post->{'regular-body'};
                    break;

                case 'quote':
                    $news = $post->{'quote-text'}.' '.$post->{'quote-source'};
                    break;
            }

            $sql .= "('$title', '$news', '$user', '$date', 'tumblr', '$id'), ";

            $importCount++;
        }

        // Do we have anything to import
        if ($importCount > 0)
        {
            if (isset($_SESSION['external_id']))
            {
                unset($_SESSION['external_id']);
            }

            // remove extra comma and space
            $sql = substr($sql, 0, -2);

            if (!$this->db2->query($sql))
            {
                displaySQLError('Import Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return false;
            }
        }

        return $importCount;
    }

    /**
     * importWordpressPosts 
     * 
     * Will return the number of posts imported or false if failures.
     * 
     * @param string $wordpressUrl 
     * @param int    $userId 
     * @param string $atomDate 
     * @param string $external_ids 
     * 
     * @return int or boolean false
     */
    function importWordpressPosts ($wordpressUrl, $userId, $atomDate, $external_ids)
    {
        // Wordpress doesn't have an api to limit posts by date or id
        if ($this->url_exists($wordpressUrl))
        {
            $xml = simplexml_load_file($wordpressUrl);
        }

        if (!isset($xml))
        {
            echo '
                <div class="error-alert">
                    <p>'.T_('Could not import news from external source.').'</p>
                    <p><b>'.T_('Source').'</b>: wordpress</p>
                    <p><b>'.T_('URL/Account').'</b>: '.$wordpressUrl.'</p>
                </div>';

            return false;
        }

        // Insert new external posts
        $sql = "INSERT INTO `fcms_news` (`title`, `news`, `user`, 
                    `date`, `external_type`, `external_id`)
                VALUES ";

        $importCount = 0;
        foreach($xml->channel->item as $post)
        {
            $bid = cleanInput("$post->guid");

            // skip ids that already exist
            if (isset($external_ids[$bid]))
            {
                continue;
            }

            $title = cleanInput("$post->title");
            $news  = cleanInput("$post->description");
            $user  = $userId;
            $date  = date('Y-m-d H:i:s', strtotime($post->pubDate));

            $sql .= "('$title', '$news', '$user', '$date', 'wordpress', '$bid'), ";

            $importCount++;
        }

        // Do we have anything to import
        if ($importCount > 0)
        {
            if (isset($_SESSION['external_id']))
            {
                unset($_SESSION['external_id']);
            }

            // remove extra comma and space
            $sql = substr($sql, 0, -2);

            if (!$this->db2->query($sql))
            {
                displaySQLError('Import Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return false;
            }
        }

        return $importCount;
    }

    /**
     * importPosterousPosts 
     * 
     * Will return the number of posts imported or false if failures.
     * 
     * @param string $posterousUrl 
     * @param int    $userId 
     * @param string $atomDate 
     * @param string $external_ids 
     * 
     * @return int or boolean false
     */
    function importPosterousPosts ($posterousUrl, $userId, $atomDate, $external_ids)
    {
        $maxId = 0;

        # get the highest id from existing external posts, if any
        foreach ($external_ids as $id => $type)
        {
            if ($type != 'posterous')
            {
                continue;
            }

            if ($id > $maxId)
            {
                $maxId = $id;
            }                    
        }

        $url = 'http://posterous.com/api/readposts?hostname='.$posterousUrl;

        if ($maxId > 0)
        {
            $url .= '&amp;since_id='.$maxId;
        }

        if ($this->url_exists($url))
        {
            $xml = simplexml_load_file($url);
        }

        if (!isset($xml))
        {
            echo '
                <div class="error-alert">
                    <p>'.T_('Could not import news from external source.').'</p>
                    <p><b>'.T_('Source').'</b>: posterous</p>
                    <p><b>'.T_('URL/Account').'</b>: '.$posterousUrl.'</p>
                </div>';

            return false;
        }

        // Insert new external posts
        $sql = "INSERT INTO `fcms_news` (`title`, `news`, `user`, 
                    `date`, `external_type`, `external_id`)
                VALUES ";

        $importCount = 0;
        foreach($xml->post as $post)
        {
            $bid = cleanInput($post->id, 'int');

            // skip ids that already exist
            if (isset($external_ids[$bid]))
            {
                continue;
            }

            $title = cleanInput("$post->title");
            $news  = cleanInput("$post->body");
            $user  = $userId;
            $date  = date('Y-m-d H:i:s', strtotime($post->date));

            $sql .= "('$title', '$news', '$user', '$date', 'posterous', '$bid'), ";

            $importCount++;
        }

        // Do we have anything to import
        if ($importCount > 0)
        {
            if (isset($_SESSION['external_id']))
            {
                unset($_SESSION['external_id']);
            }

            // remove extra comma and space
            $sql = substr($sql, 0, -2);

            if (!$this->db2->query($sql))
            {
                displaySQLError('Import Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return false;
            }
        }

        return $importCount;
    }

    /**
     * url_exists 
     * 
     * @param string $url 
     *
     * @return boolean
     */
    function url_exists($url)
    { 
        $hdrs = @get_headers($url); 
        return is_array($hdrs) ? preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/',$hdrs[0]) : false; 
    } 
}
