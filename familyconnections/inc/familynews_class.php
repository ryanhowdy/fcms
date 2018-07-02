<?php
/**
 * FamilyNews.
 *
 * @copyright   2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class FamilyNews
{
    public $fcmsError;
    public $fcmsDatabase;
    public $fcmsUser;

    /**
     * __construct.
     *
     * @param FCMS_Error $fcmsError
     * @param Database   $fcmsDatabase
     * @param User       $fcmsUser
     *
     * @return void
     */
    public function __construct(FCMS_Error $fcmsError, Database $fcmsDatabase, User $fcmsUser)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser = $fcmsUser;
    }

    /**
     * getNewsListMenu.
     *
     * Returns the template params for the family news list menu.
     *
     * @return array
     */
    public function getNewsListMenu()
    {
        $sql = 'SELECT u.`id`, `fname`, `lname`, `displayname`, `username`, MAX(`updated`) AS d 
                FROM `fcms_news` AS n, `fcms_users` AS u, `fcms_user_settings` AS s 
                WHERE u.`id` = n.`user` 
                AND u.`id` = s.`user` GROUP BY id ORDER BY d DESC';

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false) {
            return false;
        }

        $templateParams = [
            'familyNewsText' => T_('Family News'),
        ];

        if (count($rows) > 0) {
            foreach ($rows as $r) {
                $templateParams['newsMenu'][] = [
                    'id'          => (int) $r['id'],
                    'displayname' => getUserDisplayName($r['id']),
                    'date'        => fixDate(T_('M. j'), $this->fcmsUser->tzOffset, $r['d']),
                ];
            }
        }

        return $templateParams;
    }

    /**
     * displayUserFamilyNews.
     *
     * Prints the listing of family news for a given user.
     *
     * @param int $user
     * @param int $page
     *
     * @return void
     */
    public function displayUserFamilyNews($user, $page = 1)
    {
        $user = (int) $user;
        $from = (($page * 5) - 5);

        // Get family news
        $sql = "SELECT n.`id`, n.`user`, n.`title`, n.`news`, n.`updated`, n.`created`, 
                    n.`external_type`, n.`external_id`
                FROM `fcms_news` AS n, `fcms_users` AS u
                WHERE n.`user` = ? 
                    AND n.`user` = u.`id` 
                ORDER BY `updated` DESC 
                LIMIT $from, 5";

        $rows = $this->fcmsDatabase->getRows($sql, $user);
        if ($rows === false) {
            $this->fcmsError->displayError();

            return;
        }

        if (count($rows) <= 0) {
            echo '
            <div class="blank-state">
                <h2>'.T_('Nothing to see here').'</h2>
                <h3>'.T_('Currently no one has added any news').'</h3>
                <h3>'.T_('Why don\'t you be the first to add news?').'</a></h3>
                <ol>
                    <li><a href="?addnews=yes">'.T_('Add Family News').'</a></li>
                    <li><a href="settings.php?view=familynews">'.T_('Import News from existing blog').'</a></li>
                </ol>
            </div>';

            return;
        }

        foreach ($rows as $row) {
            $this->displayNews($row);
        }

        // Display Pagination
        $sql = 'SELECT COUNT(`id`) AS c 
                FROM `fcms_news` 
                WHERE `user` = ?';

        $row = $this->fcmsDatabase->getRow($sql, $user);
        if ($row === false) {
            $this->fcmsError->displayError();

            return;
        }

        $newscount = $row['c'];
        $total_pages = ceil($newscount / 5);
        displayPagination('familynews.php?getnews='.$user, $page, $total_pages);
    }

    /**
     * displayFamilyNews.
     *
     * Displays a single family news entry.
     *
     * @param int $user
     * @param int $id
     *
     * @return void
     */
    public function displayFamilyNews($user, $id)
    {
        $user = (int) $user;
        $id = (int) $id;

        $templateParams = [
            'username'        => getUserDisplayName($user),
            'commentsText'    => T_('Comments'),
            'addText'         => T_('Add'),
            'addCommentText'  => T_('Add Comment'),
            'addACommentText' => T_('Add a comment'),
            'addCommentUrl'   => '?getnews='.$user.'&amp;newsid='.$id.'#footer',
            'comments'        => [],
            'noCommentsText'  => T_('no comments'),
        ];

        $sql = 'SELECT n.`id`, n.`title`, n.`news`, n.`updated`, n.`created`,
                    n.`external_type`, n.`external_id`
                FROM `fcms_news` AS n, `fcms_users` AS u 
                WHERE n.`id` = ? 
                    AND `user` = u.`id`';

        $row = $this->fcmsDatabase->getRow($sql, $id);
        if ($row === false) {
            return;
        }

        if ($this->fcmsUser->id == $user || $this->fcmsUser->access < 2) {
            $templateParams['edit'] = [
                'user'                   => $user,
                'id'                     => (int) $row['id'],
                'title'                  => cleanOutput($row['title']),
                'news'                   => cleanOutput($row['news']),
                'editText'               => T_('Edit'),
                'editThisFamilyNewsText' => T_('Edit this Family News'),
            ];

            $templateParams['delete'] = [
                'user'                     => $user,
                'id'                       => (int) $row['id'],
                'deleteText'               => T_('Delete'),
                'deleteThisFamilyNewsText' => T_('Delete this Family News'),
            ];
        }

        $updated = fixDate(T_('F j, Y g:i a'), $this->fcmsUser->tzOffset, $row['updated']);
        $created = fixDate(T_('F j, Y g:i a'), $this->fcmsUser->tzOffset, $row['created']);

        // FCMS news
        if (empty($row['external_type']) && empty($row['external_id'])) {
            $templateParams['news'] = parse($row['news']);
        }
        // External news
        else {
            $templateParams['external'] = sprintf(T_('Originally from %s, %s.'), $row['external_type'], $created);
            $templateParams['news'] = $row['news'];

            unset($templateParams['edit']); // can't edit external
        }

        $templateParams['updated'] = $updated;
        $templateParams['created'] = $created;
        $templateParams['url'] = '?getnews='.$user.'&amp;newsid='.(int) $row['id'];
        $templateParams['commentUrl'] = '?getnews='.$user.'&amp;newsid='.(int) $row['id'].'#comments';
        $templateParams['commentCount'] = getNewsComments($row['id']);

        $templateParams['title'] = !empty($row['title']) ? cleanOutput($row['title']) : T_('untitled');

        // Comments
        $sql = 'SELECT c.id, comment, `date`, fname, lname, username, user, avatar  
                FROM fcms_news_comments AS c, fcms_users AS u 
                WHERE news = ?
                AND c.user = u.id 
                ORDER BY `date`';

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false) {
            $this->fcmsError->displayError();

            return;
        }

        foreach ($rows as $row) {
            $commentParams = [
                'avatar'   => getCurrentAvatar($row['user']),
                'username' => getUserDisplayName($row['user']),
                'date'     => fixDate(T_('F j, Y g:i a'), $this->fcmsUser->tzOffset, $row['date']),
                'comment'  => cleanOutput($row['comment']),
            ];

            if ($this->fcmsUser->id == $row['user'] || $this->fcmsUser->access < 2) {
                $commentParams['delete'] = [
                    'url'                   => '?getnews='.$user.'&amp;newsid='.$id,
                    'id'                    => (int) $row['id'],
                    'deleteText'            => T_('Delete'),
                    'deleteThisCommentText' => T_('Delete this comment'),
                ];
            }

            $templateParams['comments'][] = $commentParams;
        }

        loadTemplate('familynews', 'main', $templateParams);
    }

    /**
     * displayForm.
     *
     * @param string $type
     * @param int    $user
     * @param int    $newsid
     * @param string $title
     * @param string $news
     *
     * @return void
     */
    public function displayForm($type, $user = 0, $newsid = 0, $title = 'error', $news = 'error')
    {
        $templateParams = [
            'titleText'                 => T_('Title'),
            'titleOfYourFamilyNewsText' => T_('Title of your Family News'),
            'orText'                    => T_('or'),
            'cancelText'                => T_('Cancel'),
        ];

        if ($type == 'edit') {
            $templateParams['editText'] = T_('Edit');
            $templateParams['editNewsText'] = T_('Edit News');
            $templateParams['title'] = $title;
            $templateParams['news'] = $news;
            $templateParams['id'] = (int) $newsid;
            $templateParams['user'] = (int) $user;
        } else {
            $templateParams['addText'] = T_('Add');
            $templateParams['addNewsText'] = T_('Add News');
        }

        // get the bbcode toolbar params
        $bbcodeToolbarParams = getBBCodeToolbarTemplateParams();
        $templateParams = array_merge($templateParams, $bbcodeToolbarParams);

        loadTemplate('familynews', $type, $templateParams);
    }

    /**
     * displayLast5News.
     *
     * @return void
     */
    public function displayLast5News()
    {
        $sql = 'SELECT * 
                FROM `fcms_news` 
                ORDER BY `updated` DESC 
                LIMIT 5';

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false) {
            $this->fcmsDatabase->displayError();

            return;
        }

        $templateParams = [
            'nothingToSeeHereText'  => T_('Nothing to see here'),
            'noOneAddedNewsText'    => T_('Currently no one has added any news'),
            'beFirstAddNewsText'    => T_('Why don\'t you be the first to add news?'),
            'addFamilyNewsText'     => T_('Add Family News'),
            'importExistinBlogText' => T_('Import News from existing blog'),
            'news'                  => [],
        ];

        foreach ($rows as $row) {
            $templateParams['news'][] = $this->getNewsTemplateParams($row);
        }

        loadTemplate('familynews', 'news', $templateParams);
    }

    /**
     * hasNews.
     *
     * @param int $userid
     *
     * @return void
     */
    public function hasNews($userid)
    {
        $userid = (int) $userid;

        $sql = 'SELECT `id` 
                FROM `fcms_news` 
                WHERE `user` = ?
                LIMIT 1';

        $row = $this->fcmsDatabase->getRow($sql, $userid);
        if ($row === false) {
            $this->fcmsError->displayError();

            return;
        }
        if (count($row) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * importExternalPosts.
     *
     * Checks if any user has an external blog setup.
     * Imports posts from those blogs if they haven't been imported already.
     *
     * @return void
     */
    public function importExternalPosts()
    {
        // get date we last checked for external news
        $sql = "SELECT `value` AS 'external_news_date'
                FROM `fcms_config`
                WHERE `name` = 'external_news_date'
                LIMIT 1";

        $r = $this->fcmsDatabase->getRow($sql);
        if ($r === false) {
            $this->fcmsError->displayError();

            return;
        }

        $last_checked = strtotime($r['external_news_date']);

        // have checked in the last 8 hours?
        if (time() - $last_checked <= (8 * 3600)) {
            return;
        }

        $atomDate = 0;
        if (!empty($r['external_news_date'])) {
            // RFC 3339 format
            $atomDate = date('Y-m-d\TH:i:s\Z', strtotime($r['external_news_date']));
        }

        // Get import blog settings
        $sql = 'SELECT `user`, `blogger`, `tumblr`, `wordpress`, `posterous`
                FROM `fcms_user_settings`';

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false) {
            $this->fcmsError->displayError();

            return;
        }
        if (count($rows) <= 0) {
            return;
        }

        $external_ids = $this->getExternalPostIds();

        foreach ($rows as $r) {
            // Blogger
            if (!empty($r['blogger'])) {
                $ret = $this->importBloggerPosts($r['blogger'], $r['user'], $atomDate, $external_ids);
                if ($ret === false) {
                    die();
                }
            }

            // Tumblr
            if (!empty($r['tumblr'])) {
                $ret = $this->importTumblrPosts($r['tumblr'], $r['user'], $atomDate, $external_ids);
                if ($ret === false) {
                    die();
                }
            }

            // Wordpress
            if (!empty($r['wordpress'])) {
                $ret = $this->importWordpressPosts($r['wordpress'], $r['user'], $atomDate, $external_ids);
                if ($ret === false) {
                    die();
                }
            }

            // Posterous
            if (!empty($r['posterous'])) {
                $ret = $this->importPosterousPosts($r['posterous'], $r['user'], $atomDate, $external_ids);
                if ($ret === false) {
                    die();
                }
            }
        }

        // Update date we last checked for external ids
        $now = gmdate('Y-m-d H:i:s');
        $sql = "UPDATE `fcms_config`
                SET `value` = ?
                WHERE `name` = 'external_news_date'";

        if (!$this->fcmsDatabase->update($sql, $now)) {
            $this->fcmsError->displayError();

            return;
        }
    }

    /**
     * getNewsTemplateParams.
     *
     * Prints out the news info when looping through a list of news.
     * Used when viewing last 5 and users news.
     *
     * @param array $data
     *
     * @return array
     */
    public function getNewsTemplateParams($data)
    {
        $templateParams = [
            'displayname'   => getUserDisplayName($data['user']),
            'updated'       => fixDate(T_('F j, Y g:i a'), $this->fcmsUser->tzOffset, $data['updated']),
            'title'         => cleanOutput($data['title']),
            'url'           => '?getnews='.(int) $data['user'].'&amp;newsid='.(int) $data['id'],
            'commentsText'  => T_('Comments'),
            'commentCount'  => getNewsComments($data['id']),
        ];

        // Imported news
        if (strlen($data['external_type']) > 0) {
            $created = fixDate(T_('F j, Y g:i a'), $this->fcmsUser->tzOffset, $data['created']);

            $templateParams['external'] = sprintf(T_('Originally from %s, %s.'), $data['external_type'], $created);
            $templateParams['news'] = strip_tags($data['news']);
        }
        // Family News
        else {
            $templateParams['news'] = removeBBCode($data['news']);
            $templateParams['news'] = cleanOutput($templateParams['news']);
        }

        if (strlen($data['news']) > 300) {
            $templateParams['news'] = substr($templateParams['news'], 0, 300);
            $templateParams['news'] .= '...<br/><br/><a href="?getnews='.$data['user'].'&amp;newsid='.(int) $data['id'].'">'.T_('Read More').'</a>';
        }

        if (empty($data['title'])) {
            $templateParams['title'] = T_('untitled');
        }

        return $templateParams;
    }

    /**
     * getExternalPostIds.
     *
     * Returns an array of ids for blog posts that were imported from outside blogs.
     *
     * @return void
     */
    public function getExternalPostIds()
    {
        $external_ids = [];

        // Check existing external posts
        $sql = 'SELECT `external_id`, `external_type`
                FROM `fcms_news`
                WHERE `external_id` IS NOT NULL';

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false) {
            $this->fcmsError->displayError();

            return $external_ids;
        }

        foreach ($rows as $row) {
            $external_ids[$row['external_id']] = $row['external_type'];
        }

        return $external_ids;
    }

    /**
     * importBloggerPosts.
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
    public function importBloggerPosts($bloggerUrl, $userid, $atomDate, $externalIds)
    {
        // User entered blogger url instead of blog id, lets fix it for them
        if (!ctype_digit($bloggerUrl)) {
            $xml = $bloggerUrl;
            $bloggerUrl = '';

            $beginning = substr($xml, 0, 4);
            $ending = substr($xml, -1);

            if ($beginning !== 'http') {
                $xml = 'http://'.$xml;
            }

            if ($ending !== '/') {
                $xml = $xml.'/';
            }

            $xml = $xml.'feeds/posts/default';

            if (!$this->url_exists($xml)) {
                echo '<div class="error-alert">'.sprintf(T_('Invalid url [%s].'), $xml).'</div>';

                return false;
            }

            $feed = simplexml_load_file($xml);
            foreach ($feed->entry as $entry) {
                // I'm getting both the blog ID and post ID
                preg_match('/blog-([0-9]+).*post-([0-9]+)/', $entry->id, $match);
                $bloggerUrl = $match[1];
            }
        }

        $url = 'http://www.blogger.com/feeds/'.$bloggerUrl.'/posts/default';

        if ($atomDate > 0) {
            $url .= '?published-min='.$atomDate;
        }

        $xml = false;
        if ($this->url_exists($url)) {
            $xml = simplexml_load_file($url);
        }

        if ($xml === false) {
            echo '
                <div class="error-alert">
                    <p>'.T_('Could not import news from external source.').'</p>
                    <p><b>'.T_('Source').'</b>: blogger</p>
                    <p><b>'.T_('URL/Account').'</b>: '.$bloggerUrl.'</p>
                </div>';

            return false;
        }

        // Insert new external posts
        $sql = 'INSERT INTO `fcms_news`
                    (`title`, `news`, `user`, `created`, `updated`, `external_type`, `external_id`)
                VALUES ';

        $allParams = [];

        $importCount = 0;
        foreach ($xml->entry as $post) {
            $bid = "$post->id";

            // skip ids that already exist
            if (isset($externalIds[$bid])) {
                continue;
            }

            $sql .= "(?, ?, ?, ?, NOW(), 'blogger', ?), ";
            $params = [
                "$post->title",
                "$post->content",
                $userid,
                date('Y-m-d H:i:s', strtotime($post->published)),
                $bid,
            ];

            $allParams = array_merge($allParams, $params);

            $importCount++;
        }

        // Do we have anything to import
        if ($importCount > 0) {
            if (isset($_SESSION['external_id'])) {
                unset($_SESSION['external_id']);
            }

            // remove extra comma and space
            $sql = substr($sql, 0, -2);

            if (!$this->fcmsDatabase->insert($sql, $allParams)) {
                $this->fcmsError->displayError();

                return false;
            }
        }

        return $importCount;
    }

    /**
     * importTumblrPosts.
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
    public function importTumblrPosts($tumblrUrl, $userid, $atomDate, $externalIds)
    {
        // Tumblr api doesn't allow you to limit the search by date or id
        // it will get the last 20 every time
        $url = $tumblrUrl.'/api/read';

        if ($this->url_exists($url)) {
            $xml = simplexml_load_file($url);
        }

        if (!isset($xml)) {
            echo '
                <div class="error-alert">
                    <p>'.T_('Could not import news from external source.').'</p>
                    <p><b>'.T_('Source').'</b>: tumblr</p>
                    <p><b>'.T_('URL/Account').'</b>: '.$tumblrUrl.'</p>
                </div>';

            return false;
        }

        // Insert new external posts
        $sql = 'INSERT INTO `fcms_news` (`title`, `news`, `user`, 
                    `created`, `updated`, `external_type`, `external_id`)
                VALUES ';

        $allParams = [];

        $importCount = 0;
        foreach ($xml->posts->post as $post) {
            $id = (float) $post->attributes()->id;

            // skip ids that already exist
            if (isset($externalIds["$id"])) {
                continue;
            }

            switch ($post->attributes()->type) {
                case 'photo':
                    $news = '<img src="'.$post->{'photo-url'}[3].'"/>';
                    $title = '';
                    if (isset($post->{'photo-caption'})) {
                        $title = $post->{'photo-caption'};
                    }
                    break;

                case 'regular':
                    $title = $post->{'regular-title'};
                    $news = $post->{'regular-body'};
                    break;

                case 'quote':
                    $news = $post->{'quote-text'}.' '.$post->{'quote-source'};
                    break;
            }

            $sql .= "(?, ?, ?, ?, NOW(), 'tumblr', ?), ";

            $params = [
                $title,
                $news,
                $userid,
                date('Y-m-d H:i:s', strtotime($post->attributes()->date)),
                $id,
            ];

            $allParams = array_merge($allParams, $params);

            $importCount++;
        }

        // Do we have anything to import
        if ($importCount > 0) {
            if (isset($_SESSION['external_id'])) {
                unset($_SESSION['external_id']);
            }

            // remove extra comma and space
            $sql = substr($sql, 0, -2);

            if (!$this->fcmsDatabase->insert($sql, $allParams)) {
                $this->fcmsError->displayError();

                return false;
            }
        }

        return $importCount;
    }

    /**
     * importWordpressPosts.
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
    public function importWordpressPosts($wordpressUrl, $userId, $atomDate, $external_ids)
    {
        // Wordpress doesn't have an api to limit posts by date or id
        if ($this->url_exists($wordpressUrl)) {
            $xml = simplexml_load_file($wordpressUrl);
        }

        if (!isset($xml)) {
            echo '
                <div class="error-alert">
                    <p>'.T_('Could not import news from external source.').'</p>
                    <p><b>'.T_('Source').'</b>: wordpress</p>
                    <p><b>'.T_('URL/Account').'</b>: '.$wordpressUrl.'</p>
                </div>';

            return false;
        }

        // Insert new external posts
        $sql = 'INSERT INTO `fcms_news` (`title`, `news`, `user`, 
                    `created`, `updated`, `external_type`, `external_id`)
                VALUES ';

        $allParams = [];

        $importCount = 0;
        foreach ($xml->channel->item as $post) {
            $bid = "$post->guid";

            // skip ids that already exist
            if (isset($external_ids[$bid])) {
                continue;
            }

            $sql .= "(?, ?, ?, ?, NOW(), 'wordpress', ?), ";

            $params = [
                "$post->title",
                "$post->description",
                $userId,
                date('Y-m-d H:i:s', strtotime($post->pubDate)),
                $bid,
            ];

            $allParams = array_merge($allParams, $params);

            $importCount++;
        }

        // Do we have anything to import
        if ($importCount > 0) {
            if (isset($_SESSION['external_id'])) {
                unset($_SESSION['external_id']);
            }

            // remove extra comma and space
            $sql = substr($sql, 0, -2);

            if (!$this->fcmsDatabase->insert($sql, $allParams)) {
                $this->fcmsError->displayError();

                return false;
            }
        }

        return $importCount;
    }

    /**
     * importPosterousPosts.
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
    public function importPosterousPosts($posterousUrl, $userId, $atomDate, $external_ids)
    {
        $maxId = 0;

        // get the highest id from existing external posts, if any
        foreach ($external_ids as $id => $type) {
            if ($type != 'posterous') {
                continue;
            }

            if ($id > $maxId) {
                $maxId = $id;
            }
        }

        $url = 'http://posterous.com/api/readposts?hostname='.$posterousUrl;

        if ($maxId > 0) {
            $url .= '&amp;since_id='.$maxId;
        }

        if ($this->url_exists($url)) {
            $xml = simplexml_load_file($url);
        }

        if (!isset($xml)) {
            echo '
                <div class="error-alert">
                    <p>'.T_('Could not import news from external source.').'</p>
                    <p><b>'.T_('Source').'</b>: posterous</p>
                    <p><b>'.T_('URL/Account').'</b>: '.$posterousUrl.'</p>
                </div>';

            return false;
        }

        // Insert new external posts
        $sql = 'INSERT INTO `fcms_news` (`title`, `news`, `user`, 
                    `created`, `updated`, `external_type`, `external_id`)
                VALUES ';

        $allParams = [];

        $importCount = 0;
        foreach ($xml->post as $post) {
            $bid = "$post->id";

            // skip ids that already exist
            if (isset($external_ids[$bid])) {
                continue;
            }

            $sql .= "(?, ?, ?, ?, NOW(), 'posterous', ?), ";

            $params = [
                "$post->title",
                "$post->body",
                $userId,
                date('Y-m-d H:i:s', strtotime($post->date)),
                $bid,
            ];

            $allParams = array_merge($allParams, $params);

            $importCount++;
        }

        // Do we have anything to import
        if ($importCount > 0) {
            if (isset($_SESSION['external_id'])) {
                unset($_SESSION['external_id']);
            }

            // remove extra comma and space
            $sql = substr($sql, 0, -2);

            if (!$this->fcmsDatabase->insert($sql, $allParams)) {
                $this->fcmsError->displayError();

                return false;
            }
        }

        return $importCount;
    }

    /**
     * url_exists.
     *
     * @param string $url
     *
     * @return bool
     */
    public function url_exists($url)
    {
        $hdrs = @get_headers($url);

        return is_array($hdrs) ? preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/', $hdrs[0]) : false;
    }
}
