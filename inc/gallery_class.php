<?php
include_once 'utils.php';
include_once 'image_class.php';
include_once 'datetime.php';

/**
 * PhotoGallery 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class PhotoGallery
{
    var $db;
    var $db2;
    var $tzOffset;
    var $currentUserId;
    var $img;

    /**
     * PhotoGallery 
     * 
     * @param   int         $currentUserId 
     * 
     * @return  void
     */
    function PhotoGallery ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db  = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $this->db2 = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = $currentUserId;
        $this->img           = new Image($this->currentUserId);
        $this->tzOffset      = getTimezone($this->currentUserId);

        T_bindtextdomain('messages', '.././language');
    }

    /**
     * displayGalleryMenu 
     * 
     * @param   string $uid 
     * @param   string $cid 
     * @return  void
     */
    function displayGalleryMenu ($uid = '', $cid = '')
    {
        $home   = '';
        $member = '';
        $rated  = '';
        $viewed = '';
        $my     = '';
        $search = '';

        if (isset($_GET['search'])) {
            $search = ' selected';
        } elseif ($uid == '' ) {
            $uid = '0';
            $home = ' selected';
        } elseif ($uid == '0' && $cid == '') {
            $member = ' selected';
        }

        if ($cid == 'toprated') {
            $rated = ' selected';
        } elseif ($cid == 'mostviewed') {
            $viewed = ' selected';
        }

        if ($uid == $this->currentUserId && $cid == '') {
            $my = ' selected';
        }

        echo '
            <div id="gallery_menu" class="clearfix">
                <div id="sections_menu" class="clearfix">
                    <ul>
                        <li><a class="'.$home.'" href="index.php">'.T_('Latest').'</a></li>
                        <li><a class="'.$member.'" href="?uid=0">'.T_('Members').'</a></li>
                        <li><a class="'.$rated.'" href="?uid='.$uid.'&amp;cid=toprated">'.T_('Top Rated').'</a></li>
                        <li><a class="'.$viewed.'" href="?uid='.$uid.'&amp;cid=mostviewed">'.T_('Most Viewed').'</a></li>
                        <li><a class="'.$my.'" href="?uid='.$this->currentUserId.'">'.T_('My Photos').'</a></li>
                        <li><a class="'.$search.'" href="?search=form">'.T_('Search').'</a></li>
                    </ul>
                </div>';
        if (
            checkAccess($this->currentUserId) <= 3 or 
            checkAccess($this->currentUserId) == 5 or 
            checkAccess($this->currentUserId) == 8
        ) {
            echo '
                <div id="actions_menu" class="clearfix">
                    <ul>
                        <li><a class="upload" href="?action=upload">'.T_('Upload Photos').'</a></li>
                        <li><a class="manage_categories" href="?action=category">'.T_('Manage Categories').'</a></li>
                    </ul>
                </div>
            </div>';
        }
    }

    /**
     * displaySearchForm 
     * 
     * Displays the form to search the photo gallery.
     * 
     * @return void
     */
    function displaySearchForm ()
    {
        $displayNameArr = array();

        $sql = "SELECT `id` 
                FROM `fcms_users` 
                WHERE `activated` > 0";
        $this->db->query($sql) or displaySQLError(
            'Members Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) { 
            while ($row = $this->db->get_row()) {
                $displayNameArr[$row['id']] = getUserDisplayName($row['id'], 2);
            }
            asort($displayNameArr);
        }
        echo '
                <fieldset>
                    <legend><span>'.T_('Search').'</span></legend>
                    <form action="index.php" method="get">
                        <div class="field-row clearfix">
                            <div class="field-label"><b>'.T_('Photo Uploaded By').'</b></div>
                            <div class="field-widget">
                                <select name="uid">
                                    <option value="0"></option>';
        foreach ($displayNameArr as $key => $value) {
            echo '
                                    <option value="'.(int)$key.'">'.cleanOutput($value).'</option>';
        }
        echo '
                                </select>
                            </div>
                        </div>
                        <div class="field-row clearfix">
                            <div class="field-label"><b>'.T_('Members In Photo').'</b></div>
                            <div class="field-widget">
                                <select name="cid">
                                    <option value="all"></option>';
        foreach ($displayNameArr as $key => $value) {
            echo '
                                    <option value="'.(int)$key.'">'.cleanOutput($value).'</option>';
        }
        echo '
                                </select>
                            </div>
                        </div>
                        <p><input class="sub1" type="submit" value="'.T_('Search').'"/></p>
                    </form>
                </fieldset>';
    }

    /**
     * displayLatestCategories 
     * 
     * Displays the last 6 categories that most recently had new photos added.
     * 
     * @return void
     */
    function displayLatestCategories()
    {
        $sql = "SELECT * 
                FROM (
                    SELECT p.`id`, p.`date`, p.`filename`, c.`name`, p.`user`, p.`category`
                    FROM `fcms_gallery_photos` AS p, `fcms_category` AS c
                    WHERE p.`category` = c.`id`
                    ORDER BY `date` DESC
                ) AS sub
                GROUP BY `category`
                ORDER BY `date` DESC LIMIT 6";

        if (!$this->db->query($sql))
        {
            displaySQLError('Latest Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '
                <div class="info-alert">
                    <h2>'.T_('Welcome to the Photo Gallery').'</h2>
                    <p><i>'.T_('Currently no one has added any photos').'</i></p>
                    <p><b>'.T_('How do I add photos?').'</b></p>
                    <ol>
                        <li><a href="?action=category">'.T_('Create a Category').'</a></li>
                        <li><a href="?action=upload">'.T_('Upload Photos').'</a></li>
                    </ol>
                </div>';
            return;
        }

        echo '
                <ul class="categories clearfix">';

        while ($row = $this->db->get_row())
        {
            $date = fixDate(T_('M. j, Y'), $this->tzOffset, $row['date']);

            echo '
                    <li class="category">
                        <a href="?uid='.$row['user'].'&amp;cid='.$row['category'].'">
                            <img class="photo" 
                                src="../uploads/photos/member'.(int)$row['user'].'/tb_'.basename($row['filename']).'" 
                                alt="'.cleanOutput($row['name']).'"/>
                        </a>
                        <span>
                            <strong>'.cleanOutput($row['name']).'</strong>
                            <i>'.$date.'</i>
                        </span>
                    </li>';
        }

        echo '
                </ul>';
    }
    
    /**
     * showPhoto 
     * 
     * Displays the current photo, info, comments, next/prev butons etc.
     *
     * The following views use this function:
     *     Latest Comments - uid=0         cid=comments
     *     Top Rated       - uid=0         cid=toprated
     *     Most  Viewed    - uid=userid    cid=mostviewed
     *     Tagged Users    - uid=0         cid=tagged# (where # is the id of the tagged user)
     *     All for User    - uid=userid    cid=all
     *
     * @param   string  $uid    the user's id or 0
     * @param   string  $cid    the category id, 'tagged#', 'comments', 'toprated', 'mostviewed' or 'all'
     * @param   string  $pid    the photo id 
     * @return  void
     */
    function showPhoto ($uid, $cid, $pid)
    {
        $uid = (int)$uid;
        $pid = (int)$pid;

        list($breadcrumbs, $cid, $urlcid, $sql) = $this->getShowPhotoParams($uid, $cid);

        if (!$this->db2->query($sql))
        {
            displaySQLError('Photos Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        // Save filenames in an array, so we can see next/prev, etc
        while ($row = $this->db2->get_row())
        {
            $photo_arr[] = $row['filename'];
        }

        // No photos exist for the current view/category
        // Even though we are in photo view, bump them back to the category view
        // and let the user know that this category is now empty
        if ($this->db2->count_rows() <= 0)
        {
            $this->displayGalleryMenu($uid, $cid);

            echo '
            <div class="info-alert">
                <h2>'.T_('Oops!').'</h2>
                <p>'.T_('The Category you are trying to view is Empty.').'</p>
            </div>';

            return;
        }

        // Select Current Photo to view
        $sql = "SELECT p.`user` AS uid, `filename`, `caption`, `category` AS cid, p.`date`, 
                    `name` AS category_name, `views`, `votes`, `rating` 
                FROM `fcms_gallery_photos` AS p, `fcms_category` AS c 
                WHERE p.`id` = '$pid' 
                AND p.`category` = c.`id`";

        if (!$this->db2->query($sql))
        {
            displaySQLError('Photo Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db2->count_rows() <= 0)
        {
            echo '
            <p class="error-alert">'.T_('The Photo you are trying to view can not be found.').'</p>';
            return;
        }
            
        // Save info about current photo
        $r = $this->db2->get_row();
        $displayname = getUserDisplayName($r['uid']);
                
        // Update View count
        $sql = "UPDATE `fcms_gallery_photos` 
                SET `views` = `views`+1 
                WHERE `id` = '$pid'";
        if (!$this->db->query($sql))
        {
            // Just show error and continue
            displaySQLError('Update View Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        }

        // Get photo comments
        $comments = $this->getPhotoComments($pid);

        $total   = count($photo_arr);
        $current = array_search($r['filename'], $photo_arr);
        $prev    = 0;
        $next    = 0;

        // Previous pid
        if (isset($photo_arr[$current-1]))
        {
            // strip the extension off the filename to get the pid #s (ex: 453.gif)
            $prev = substr($photo_arr[$current-1], 0, strpos($photo_arr[$current-1], '.'));
        }
        else
        {
            $prev = substr(end($photo_arr), 0, strpos(end($photo_arr), '.'));
        }

        // Next pid
        if (isset($photo_arr[$current+1]))
        {
            $next = substr($photo_arr[$current+1], 0, strpos($photo_arr[$current+1], '.'));
        }
        else
        {
            $next = substr($photo_arr[0], 0, strpos($photo_arr[0], '.'));
        }

        $photos_of = '<i>('.sprintf(T_('%d of %d'), $current+1, $total).')</i>';

        $prev_next = '';

        if ($total > 1)
        {
            $prev_next .= '
                <div class="prev_next clearfix">
                    <a class="previous" href="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$prev.'">'.T_('Previous').'</a>
                    <a class="next" href="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$next.'">'.T_('Next').'</a>
                </div>
                <script type="text/javascript">
                function keyHandler(e) {
                    if (!e) { e = window.event; }
                    arrowRight = 39;
                    arrowLeft = 37;
                    switch (e.keyCode) {
                        case arrowRight:
                        document.location.href = "index.php?uid='.$uid.'&cid='.$urlcid.'&pid='.$next.'";
                        break;
                        case arrowLeft:
                        document.location.href = "index.php?uid='.$uid.'&cid='.$urlcid.'&pid='.$prev.'";
                        break;
                    }
                }
                document.onkeydown = keyHandler;
                </script>';
        }

        // special view detail
        $special = '
                <div id="special">
                    '.T_('From the Category:').' <a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'">'.cleanOutput($r['category_name']).'</a> 
                    '.T_('by').' 
                    <a class="u" href="../profile.php?member='.$r['uid'].'">'.$displayname.'</a>
                </div>';

        // if breadcrumbs haven't been defined, give the default
        if ($breadcrumbs == '')
        {
            $breadcrumbs = '
                <a href="?uid=0">'.T_('Members').'</a> &gt; 
                <a href="?uid='.$uid.'">'.$displayname.'</a> &gt; 
                <a href="?uid='.$uid.'&amp;cid='.$cid.'">'.cleanOutput($r['category_name']).'</a>
                '.$photos_of;

            $special = '';
        }

        // setup some vars to hold photo details
        $photo_path        = $this->getPhotoPath($r['filename'], $r['uid']);
        $photo_path_middle = $photo_path[0];
        $photo_path_full   = $photo_path[1];
        $caption           = cleanOutput($r['caption']);
        $dimensions        = GetImageSize($photo_path_full);
        $size              = filesize($photo_path_full);
        $size              = formatSize($size);
        $date_added        = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $r['date']);

        // Calculate rating
        if ($r['votes'] <= 0)
        {
            $rating = 0;
            $width = 0;
        }
        else
        {
            $rating = ($r['rating'] / $r['votes']) * 100;
            $rating = round($rating, 0);
            $width = $rating / 5;
        }

        // Get Tagged Members
        $sql = "SELECT u.`id`, u.`lname` 
                FROM `fcms_users` AS u, `fcms_gallery_photos_tags` AS t 
                WHERE t.`photo` = '$pid' 
                AND t.`user` = u.`id`
                ORDER BY u.`lname`";

        if (!$this->db->query($sql))
        {
            displaySQLError('Tagged Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return;
        }

        $tagged_mem_list = '<li>'.T_('none').'</li>';

        if ($this->db->count_rows() > 0)
        {
            $tagged_mem_list = '';

            while ($t = $this->db->get_row())
            {
                 $tagged_mem_list .= '<li><a href="?uid=0&cid='.$t['id'].'" title="Show more photos of '.getUserDisplayName($t['id'],2).'">'.getUserDisplayName($t['id']).'</a></li>';
            }
        }

        // Edit / Delete Photo options
        $edit_del_options = '';
        if ($this->currentUserId == $r['uid'] || checkAccess($this->currentUserId) < 2)
        {
            $edit_del_options = '
                            <li>
                                <input type="submit" name="editphoto" id="editphoto" value="'.T_('Edit').'" class="editbtn"/>
                            </li>
                            <li>
                                <input type="submit" name="deletephoto" id="deletephoto" value="'.T_('Delete').'" class="delbtn"/>
                            </li>';
        }
        
        // Display
        echo '
            <div class="breadcrumbs clearfix">
                '.$breadcrumbs.'
                '.$prev_next.'
            </div>
            <div id="photo">
                <a href="'.$photo_path_full.'"><img class="photo" src="'.$photo_path_middle.'" alt="'.$caption.'" title="'.$caption.'"/></a>
            </div>

            <div id="photo_details">

                <div id="caption">
                    '.$caption.'
                    <ul class="star-rating small-star">
                        <li class="current-rating" style="width:'.$width.'%">'.sprintf(T_('Currently %s/5 Starts'), $r['rating']).'</li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=1" title="'.T_('1 out of 5 Stars').'" class="one-star">1</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=2" title="'.T_('2 out of 5 Stars').'" class="two-stars">2</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=3" title="'.T_('3 out of 5 Stars').'" class="three-stars">3</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=4" title="'.T_('4 out of 5 Stars').'" class="four-stars">4</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=5" title="'.T_('5 out of 5 Stars').'" class="five-stars">5</a></li>
                    </ul>
                </div>

                <div id="photo_stats">
                    <form action="index.php" method="post">
                        <ul>
                            <li class="photo_views">'.$r['views'].'</li>
                            <li class="photo_comments">'.count($comments).'</li> 
                            '.$edit_del_options.'
                        </ul>
                        <div>
                            <input type="hidden" name="photo" id="photo" value="'.$pid.'"/>
                            <input type="hidden" name="url" id="url" value="uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$pid.'"/>
                        </div>
                    </form>
                </div>

                <div id="members_in_photo">
                    <b>'.T_('Members in Photo').'</b>
                    <ul>
                        '.$tagged_mem_list.'
                    </ul>
                </div>

                '.$special.'

                <div id="more_details">
                    <div id="photo_details_sub">
                        <p><b>'.T_('Filename').':</b> '.$r['filename'].'</p>
                        <p><b>'.T_('Photo Size').':</b> '.$size.'</p>
                        <p><b>'.T_('Dimensions').':</b> '.$dimensions[0].' x '.$dimensions[1].'</p>
                        <p><b>'.T_('Date Added').':</b> '.$date_added.'</p>
                    </div>
                </div>

            </div>';
                
        // Display Comments
        if (   checkAccess($this->currentUserId) <= 8
            && checkAccess($this->currentUserId) != 7
            && checkAccess($this->currentUserId) != 4
        )
        {

            echo '
            <h3 id="comments">'.T_('Comments').'</h3>';

            if (count($comments) > 0)
            { 
                foreach ($comments as $row)
                {
                    // Setup some vars for each comment block
                    $del_comment = '';
                    $date        = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $row['date']);
                    $displayname = getUserDisplayName($row['user']);
                    $comment     = $row['comment'];

                    if ($this->currentUserId == $row['user'] || checkAccess($this->currentUserId) < 2)
                    {
                        $del_comment .= '<input type="submit" name="delcom" id="delcom" '
                            . 'value="'.T_('Delete').'" class="gal_delcombtn" title="'
                            . T_('Delete this Comment') . '"/>';
                    }

                    echo '
            <div class="comment_block clearfix">
                <form class="delcom" action="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$pid.'" method="post">
                    '.$del_comment.'
                    <img class="avatar" alt="avatar" src="'.getCurrentAvatar($row['user'], true).'"/>
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>
                        '.parse($comment, '../').'
                    </p>
                    <input type="hidden" name="id" value="'.$row['id'].'">
                </form>
            </div>';
                }
            }

            echo '
            <div class="add_comment_block">
                <form action="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$pid.'" method="post">
                    '.T_('Add Comment').'<br/>
                    <textarea class="frm_textarea" name="post" id="post" rows="3" cols="63"></textarea>
                    <input type="submit" name="addcom" id="addcom" value="'.T_('Add Comment').'" title="'.T_('Add Comment').'" class="gal_addcombtn"/>
                </form>
            </div>';
        }
    }

    /**
     * getShowPhotoParams 
     * 
     * Returns an array of params used for showing a photo.
     *
     * return array(
     *       0 => $breadcrumbs,
     *       1 => $cid,
     *       2 => $urlcid,
     *       3 => $sql
     * );
     *
     * @param string $user 
     * @param string $category 
     * 
     * @return array
     */
    function getShowPhotoParams ($user, $category)
    {
        // Latest Comments View
        if ($category == 'comments')
        {
            $urlcid = $category;
            $cid    = $urlcid;

            $sql = "SELECT DISTINCT `filename` 
                    FROM (
                        SELECT p.`filename` 
                        FROM `fcms_gallery_comments` AS c, `fcms_gallery_photos` AS p 
                        WHERE c.`photo` = p.`id` ORDER BY c.`date` DESC
                    ) as z";

            $breadcrumbs = '<a href="?uid=0&amp;cid=comments">'.T_('Latest Comments').'</a>';
        }
        // Top Rated View
        elseif ($category == 'toprated')
        {
            $urlcid = $category;
            $cid    = $urlcid;

            $sql = "SELECT `filename` 
                    FROM `fcms_gallery_photos` 
                    WHERE `votes` > 0 
                    ORDER BY `rating`/`votes` DESC";

            $breadcrumbs = '<a href="?uid=0&amp;cid=toprated">'.T_('Top Rated').'</a>';
        }
        // Most Viewed View
        elseif ($category == 'mostviewed')
        {
            $urlcid = $category;
            $cid    = $urlcid;

            $sql = "SELECT `filename` 
                    FROM `fcms_gallery_photos` 
                    WHERE `views` > 0";
            if ($user > 0) {
                $sql .= " AND `user` = '$user'";
            }
            $sql .= " ORDER BY `views` DESC";

            $breadcrumbs = "<a href=\"?uid=$user&amp;cid=$cid\">".T_('Most Viewed')."</a>";
        }
        // Tagged Photos View
        elseif (strpos($category, 'tagged') !== false)
        {
            $urlcid = $category;

            // $category = 'tagged#' we only want the number
            $cid = substr($category, 6);
            $cid = (int)$cid;

            $sql = "SELECT `filename` 
                    FROM `fcms_gallery_photos` AS p, `fcms_gallery_photos_tags` AS t 
                    WHERE t.`user` = '$cid' 
                    AND t.`photo` = p.`id` 
                    ORDER BY `date`";

            $userName    = getUserDisplayName($cid);
            $breadcrumbs = "<a href=\"?uid=0&amp;cid=$cid\">".sprintf(T_('Photos of %s'), $userName)."</a>";
        }
        // All Photos for Member
        elseif ($category == 'all')
        {
            $urlcid = $category;
            $cid    = $urlcid;

            $sql = "SELECT `filename` 
                    FROM `fcms_gallery_photos`
                    WHERE `user` = $user
                    ORDER BY `id` DESC";

            $userName    = getUserDisplayName($user);
            $breadcrumbs = '<a href="?uid='.$user.'&amp;cid=all">'.sprintf(T_('Photos uploaded by %s'), $userName).'</a>';
        }
        // Category of Photos
        else
        {
            $urlcid = (int)$category;
            $cid    = $urlcid;

            $sql = "SELECT `filename` 
                    FROM `fcms_gallery_photos` 
                    WHERE `category` = '$cid' 
                    ORDER BY `date`";

            $breadcrumbs = '';
        }

        return array(
            0 => $breadcrumbs,
            1 => $cid,
            2 => $urlcid,
            3 => $sql
        );
    }

    /**
     * getPhotoPath 
     * 
     * Returns an array of photo paths.
     * The first path is the path to the middle sized photo.
     * The second path is the path to the full sized photo or the middle again if no full sized exists.
     * 
     * @param string $filename 
     * @param int    $uid 
     * 
     * @return array
     */
    function getPhotoPath ($filename, $uid)
    {
        $filename = basename($filename);
        $uid      = (int)$uid;

        // Link to the full sized photo if using full sized
        $sql = "SELECT `value` AS 'full_size_photos'
                FROM `fcms_config`
                WHERE `name` = 'full_size_photos'";

        $full_size_photos = false; 

        if (!$this->db->query($sql))
        {
            // If we can't figure out full sized, we will default to no and continue on
            displaySQLError('Full Size Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        }
        else
        {
            $row = $this->db->get_row();
            $full_size_photos = $row['full_size_photos'] == 1 ? true : false;
        }

        $photo_path[0] = "../uploads/photos/member$uid/$filename";
        $photo_path[1] = "../uploads/photos/member$uid/$filename";

        if ($full_size_photos)
        {
            // If you are using full sized but a photo was uploaded prior to that change, 
            // no full sized photo will be available, so don't link to it
            if (file_exists("../uploads/photos/member$uid/full_$filename"))
            {
                $photo_path[1] = "../uploads/photos/member$uid/full_$filename";
            }
        }

        return $photo_path;
    }

    /*
     *  showCategories
     *
     *  Displays a list of photos in the desired category or view.
     *
     *  The following views use this function:
     *      Member Gallery  - uid=0             cid=
     *      Latest Comments - uid=0 or userid   cid='comments'
     *      Top Rated       - uid=0 or userid   cid='toprated'
     *      Most  Viewed    - uid=0 or userid   cid='mostviewed'
     *      Tagged Users    - uid=0             cid=userid
     *      Category        - uid=userid        cid=#
     *      All for User    - uid=userid        cid='all'
     *
     *  @param      $page   the page # you want for the specified category
     *  @param      $uid    the user's id or 0 if displaying view for all users
     *  @param      $cid    optional, category id, 'comments', 'toprated', 'mostviewed', or 'all'
     *  @return     nothing
     */
    function showCategories ($page, $uid, $cid = 'none')
    {
        // # of categories per page -- used for pagination
        $perPage = 18;
        
        // No user id specified
        if ($uid == 0) {
            
            //-----------------------------------------------------------------
            // Member Gallery View
            //-----------------------------------------------------------------
            if ($cid == 'none') {
                $sql = "SELECT 'MEMBER' AS type, u.`id` AS uid, f.`filename`, COUNT(p.`id`) as c 
                        FROM `fcms_category` AS cat 
                        LEFT JOIN `fcms_gallery_photos` AS p 
                        ON p.`category` = cat.`id`, `fcms_users` AS u, (
                            SELECT * 
                            FROM `fcms_gallery_photos` 
                            ORDER BY `date` DESC
                        ) AS f 
                        WHERE f.`id` = p.`id` 
                        AND u.`id` = p.`user` 
                        GROUP BY p.`user`";

                // Pagination
                $from = ($page * $perPage) - $perPage;
                $sql .= " LIMIT $from, $perPage";
                
            //-----------------------------------------------------------------
            // Latest Comments View
            //-----------------------------------------------------------------
            } elseif ($cid == 'comments') {
                $sql = "SELECT 'COMMENTS' AS type, p.`user` AS uid, p.`category` AS cid, 
                            c.`date` AS heading, p.`id` AS pid, p.`filename`, c.`comment`, 
                            p.`caption`, c.`user` 
                        FROM `fcms_gallery_comments` AS c, `fcms_gallery_photos` AS p, 
                            `fcms_category` AS cat, `fcms_users` AS u 
                        WHERE c.`photo` = p.`id` 
                        AND p.`category` = cat.`id` 
                        AND c.`user` = u.`id` 
                        ORDER BY c.`date` DESC";

                // Pagination
                if ($page >= 1) {
                    // ALL Latest Comments
                    $from = ($page * $perPage) - $perPage;
                    $sql .= " LIMIT $from, $perPage";
                } else {
                    // Front page Latest Comments
                    $sql .= " LIMIT 6";
                }
                
            //-----------------------------------------------------------------
            // Overall Top Rated View
            //-----------------------------------------------------------------
            } elseif ($cid == 'toprated') {
                $sql = "SELECT 'RATED' AS type, `user` AS uid, `filename`, `category`, 
                            `caption`, `id` AS pid, `rating`/`votes` AS 'r' 
                        FROM `fcms_gallery_photos` 
                        WHERE `votes` > 0 
                        ORDER BY r DESC";

                // Pagination
                $from = ($page * $perPage) - $perPage;
                $sql .= " LIMIT $from, $perPage";
                
            //-----------------------------------------------------------------
            // Overall Most Viewed View
            //-----------------------------------------------------------------
            } elseif ($cid == 'mostviewed') {
                $sql = "SELECT 'VIEWED' AS type, `user` AS uid, `filename`, `caption`, 
                            `id` AS pid, `views` 
                        FROM `fcms_gallery_photos` 
                        WHERE `views` > 0 
                        ORDER BY VIEWS DESC";

                // Pagination
                $from = ($page * $perPage) - $perPage;
                $sql .= " LIMIT $from, $perPage";
                
            //-----------------------------------------------------------------
            // Tagged Photos View (only number 0-9)
            //-----------------------------------------------------------------
            } elseif (preg_match('/^\d+$/', $cid)) {
                $sql = "SELECT 'TAGGED' AS type, t.`user`, p.`id` AS pid, p.`filename`, "
                        . "p.`caption`, p.`user` AS uid "
                     . "FROM `fcms_gallery_photos` AS p, `fcms_gallery_photos_tags` AS t "
                     . "WHERE t.`user` = '$cid' "
                     . "AND t.`photo` = p.`id`";

                // Pagination
                $perPage = 30;
                $from = ($page * $perPage) - $perPage;
                $sql .= " LIMIT $from, $perPage";
            }
            
        // Valid user id specified
        } elseif (preg_match('/^\d+$/', $uid)) {
            
            //-----------------------------------------------------------------
            // Member's Top Rated View
            //-----------------------------------------------------------------
            if ($cid == 'toprated') {
                $sql = "SELECT 'RATED' AS type, `user` AS uid, `filename`, `category`, "
                        . "`caption`, `id` AS pid, `rating`/`votes` AS 'r' "
                     . "FROM `fcms_gallery_photos` "
                     . "WHERE `votes` > 0 AND `user` = '$uid' ";

                // Pagination
                $from = ($page * $perPage) - $perPage;
                $sql .= " LIMIT $from, $perPage";
                
            //-----------------------------------------------------------------
            // Member's Most Viewed View
            //-----------------------------------------------------------------
            } elseif ($cid == 'mostviewed') {
                $sql = "SELECT 'VIEWED' AS type, `user` AS uid, `filename`, `caption`, 
                            `id` AS pid, `views` 
                        FROM `fcms_gallery_photos` 
                        WHERE `views` > 0 
                        AND `user` = '$uid'
                        ORDER BY `views` DESC";

                // Pagination
                $from = ($page * $perPage) - $perPage;
                $sql .= " LIMIT $from, $perPage";
                
            //-----------------------------------------------------------------
            // Category View
            //-----------------------------------------------------------------
            } elseif (preg_match('/^\d+$/', $cid)) {
                $sql = "SELECT 'PHOTOS' AS type, u.`id` AS uid, `category` AS cid, "
                        . "p.`id` AS pid, `caption`, c.`name` AS category, `filename` "
                     . "FROM `fcms_category` AS c, `fcms_gallery_photos` AS p, "
                        . "`fcms_users` AS u "
                     . "WHERE p.`user` = u.`id` "
                     . "AND `category` = c.`id` "
                     . "AND `category` = '$cid'";

                // Pagination
                $perPage = 30;
                $from = ($page * $perPage) - $perPage;
                $sql .= " LIMIT $from, $perPage";
                
            //-----------------------------------------------------------------
            // All Photos for Member (Search)
            //-----------------------------------------------------------------
            } elseif ($cid == 'all') {
                $sql = "SELECT 'ALL' AS type, u.`id` AS uid, `category` AS cid, 
                            p.`id` AS pid, `caption`, c.`name` AS category, `filename` 
                        FROM `fcms_category` AS c, `fcms_gallery_photos` AS p, 
                            `fcms_users` AS u 
                        WHERE p.`user` = '$uid' 
                        AND p.`user` = u.`id`
                        AND `category` = c.`id`
                        ORDER BY p.`id`";

                // Pagination
                $perPage = 30;
                $from = ($page * $perPage) - $perPage;
                $sql .= " LIMIT $from, $perPage";
                
            //-----------------------------------------------------------------
            // Member's Categories View
            //-----------------------------------------------------------------
            // invalid $cid's will default to member's sub cat listing
            } else {
                $sql = "SELECT 'CATEGORIES' AS type, u.`id` AS uid, cat.`name` AS category, "
                        . "cat.`id` AS cid, f.`filename`, COUNT(p.`id`) AS c "
                     . "FROM `fcms_category` AS cat "
                     . "LEFT JOIN `fcms_gallery_photos` AS p "
                     . "ON p.`category` = cat.`id`, `fcms_users` AS u, ("
                        . "SELECT * "
                        . "FROM `fcms_gallery_photos` "
                        . "ORDER BY `date` DESC"
                     . ") AS f "
                     . "WHERE f.`id` = p.`id` "
                     . "AND u.`id` = p.`user` "
                     . "AND p.`user` = '$uid' "
                     . "GROUP BY cat.`id` DESC";

                // Pagination
                $from = ($page * $perPage) - $perPage;
                $sql .= " LIMIT $from, $perPage";
            }
        // Catch all invalid $uid's
        } else {
            echo '
            <div class="info-alert">
                <h2>'.T_('Uh Oh!').'</h2>
                <p>'.T_('The category you are trying to view doesn\'t exist.').'</p>
            </div>';
        }
        
        $this->db->query($sql) or displaySQLError(
            'Categories Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $type = '';

        if ($this->db->count_rows() > 0) {
            $first = true;
            while ($row = $this->db->get_row()) {
                $cat_name = "";
                $url = "";
                $alt = "";
                $title = "";
                $cat_info = "";
                $type = $row['type'];

                //-------------------------------------------------------------
                // Member Gallery
                //-------------------------------------------------------------
                if ($row['type'] == 'MEMBER') {
                    if ($first) {
                        echo '
            <p class="breadcrumbs">'.T_('Members').'</p>';
                    }
                    $displayname = getUserDisplayName($row['uid']);
                    $cat_name = "<strong>$displayname</strong>";
                    $url = "?uid=" . $row['uid'];
                    $urlPage = "?uid=0";
                    $alt = ' alt="'.sprintf(T_('View Categories for %s'), cleanOutput($displayname)).'"';
                    $title = ' title="'.sprintf(T_('View Categories for %s'), cleanOutput($displayname)).'"';
                    $cat_info = "<i>" . T_('Photos') . " (" . $row['c'] . ")</i>";

                //-------------------------------------------------------------
                // Comments
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'COMMENTS') {
                    if ($first) {
                        if ($page >= 0) {
                            echo '
            <p class="breadcrumbs">'.T_('Latest Comments').'</p>';
                        } else {
                            echo '
            <h3>'.T_('Latest Comments').'</h3>
            <a href="?uid=0&amp;cid=comments">('.T_('View All').')</a><br/>';
                        }
                    }
                    $date     = fixDate(T_('M. j, Y g:i a'), $this->tzOffset, $row['heading']);
                    $cat_name = "<strong>$date</strong>";
                    $url      = "?uid=0&amp;cid=comments&amp;pid=" . $row['pid'];
                    $urlPage  = "?uid=0&amp;cid=comments";
                    $alt      = ' alt="' . cleanOutput($row['caption']) . '"';
                    $title    = ' title="' . cleanOutput($row['caption']) . '"';
                    $comment  = $row['comment'];
                    $cat_info = "<i><b>" . getUserDisplayName($row['user']) . ":</b> $comment</i>";

                //-------------------------------------------------------------
                // Top Rated
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'RATED') {
                    if ($first) {
                        echo '
            <p class="breadcrumbs">'.T_('Top Rated');
                        if ($uid > 0) {
                            echo " (" . getUserDisplayName($uid) . ")";
                        }
                        echo "</p>";
                    }
                    $width = ($row['r'] / 5) * 100;
                    $url = "?uid=0&amp;cid=toprated" . $row['category'] . "&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=0&amp;cid=toprated" . $row['category'];
                    $alt = ' alt="' . cleanOutput($row['caption']) . '"';
                    $title = ' title="' . cleanOutput($row['caption']) . '"';
                    $cat_info = "<i><ul class=\"star-rating small-star\">"
                        . "<li class=\"current-rating\" style=\"width:$width%\">"
                        . sprintf(T_('Currently %d/5 Stars.'), $row['r'])
                        . "</li><li><a href=\"$url&amp;vote=1\" title=\"" 
                        . T_('1 out of 5 Stars') . "\" class=\"one-star\">1</a></li><li>"
                        . "<a href=\"$url&amp;vote=2\" title=\"" . T_('2 out of 5 Stars')
                        . "\" class=\"two-stars\">2</a></li><li><a href=\"$url&amp;vote=3\" "
                        . "title=\"" . T_('3 out of 5 Stars') . "\" class=\"three-stars\">3</a>"
                        . "</li><li><a href=\"$url&amp;vote=4\" title=\"" . T_('4 out of 5 Stars')
                        . "\" class=\"four-stars\">4</a></li><li><a href=\"$url&amp;vote=5\" "
                        . "title=\"" . T_('5 out of 5 Stars') . "\" class=\"five-stars\">5</a>"
                        . "</li></ul></i>";

                //-------------------------------------------------------------
                // Most Viewed
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'VIEWED') {
                    if ($first) {
                        echo '
            <p class="breadcrumbs">'.T_('Most Viewed');
                        if ($uid > 0) {
                            echo " (" . getUserDisplayName($uid) . ")";
                        }
                        echo "</p>";
                    }
                    $url = "?uid=$uid&amp;cid=mostviewed&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=$uid&amp;cid=mostviewed";
                    $alt = ' alt="' . cleanOutput($row['caption']) . '"';
                    $title = ' title="' . cleanOutput($row['caption']) . '"';
                    $cat_info = "<i><b>".T_('Views').": </b>" . $row['views'] . "</i>";

                //-------------------------------------------------------------
                // Tagged
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'TAGGED') {
                    if ($first) {
                        $userName = getUserDisplayName($row['user']);
                        echo '
            <p class="breadcrumbs">'.sprintf(T_('Photos of %s'), $userName).'</p>';
                    }
                    $url = "?uid=0&amp;cid=tagged" . $row['user'] . "&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=0&amp;cid=" . $row['user'];
                    $alt = ' alt="' . cleanOutput($row['caption']) . '"';
                    $title = ' title="' . cleanOutput($row['caption']) . '"';

                //-------------------------------------------------------------
                // ALL
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'ALL') {
                    if ($first) {
                        $userName = getUserDisplayName($row['uid']);
                        echo '
            <p class="breadcrumbs">'.sprintf(T_('Photos uploaded by %s'), $userName).'</p>';
                    }
                    $url = "?uid=" . $row['uid'] . "&amp;cid=all&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=" . $row['uid'] . "&amp;cid=all";
                    $alt = ' alt="' . cleanOutput($row['caption']) . '"';
                    $title = ' title="' . cleanOutput($row['caption']) . '"';

                //-------------------------------------------------------------
                // Photos
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'PHOTOS') {
                    if ($first) {
                        echo '
            <p class="breadcrumbs">
                <a href="?uid=0">'.T_('Members').'</a> &gt; 
                <a href="?uid='.$uid.'">'.getUserDisplayName($row['uid']).'</a> &gt; 
                '.$row['category'].'
            </p>';
                    }
                    $url = "?uid=" . $row['uid'] . "&amp;cid=" . $row['cid'] . "&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=" . $row['uid'] . "&amp;cid=" . $row['cid'];
                    $alt = ' alt="' . cleanOutput($row['caption']) . '"';
                    $title = ' title="' . cleanOutput($row['caption']) . '"';

                //-------------------------------------------------------------
                // Categories
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'CATEGORIES') {
                    if ($first) {
                        echo '
            <p class="breadcrumbs">
                <a href="?uid=0">'.T_('Members').'</a> &gt; '.getUserDisplayName($row['uid']).'
            </p>';
                    }
                    $cat_name = "<strong>" . $row['category'] . "</strong>";
                    $url = "?uid=" . $row['uid'] . "&amp;cid=" . $row['cid'];
                    $urlPage = "?uid=" . $row['uid'];
                    $alt = ' alt="'.sprintf(T_('View Photos in %s'), cleanOutput($row['category'])).'"';
                    $title = ' title="'.sprintf(T_('View Photos in %s'), cleanOutput($row['category'])).'"';
                    $cat_info = "<i>" . T_('Photos') . " (" . $row['c'] . ")</i>";
                }

                if ($type == 'PHOTOS' || $type == 'TAGGED' || $type == 'ALL') {
                    $category_rows[] = '
                    <a href="index.php'.$url.'">
                        <img class="photo" src="../uploads/photos/member'.$row['uid'].'/tb_'.basename($row['filename']).'" 
                            '.$alt.' '.$title.'/>
                    </a>';
                } else {
                    $category_rows[] = '
                    <a href="index.php'.$url.'">
                        <img src="../uploads/photos/member'.$row['uid'].'/tb_'.basename($row['filename']).'" 
                            '.$alt.' '.$title.'/>
                    </a>
                    <span>
                        '.$cat_name.'
                        '.$cat_info.'
                    </span>';
                }
                $first = false;
            }

            // Output for Photos
            if ($type == 'PHOTOS' || $type == 'TAGGED' || $type == 'ALL')
            {
                $tag   = '';
                $admin = '';
                if ($type == 'PHOTOS')
                {
                    if ($uid == $this->currentUserId || checkAccess($this->currentUserId) < 2)
                    {
                        $tag = '<li><a href="?tag='.$cid.'&amp;user='.$uid.'">'.T_('Tag Members In Photos').'</a></li>';
                    }

                    if (checkAccess($this->currentUserId) < 2)
                    {
                        $admin = '<li><a href="../admin/gallery.php?edit='.$cid.'">'.T_('Administrate').'</a></li>';
                    }

                    echo '
            <ul id="category_actions">
                '.$admin.'
                '.$tag.'
                <li><a class="new_window" href="slideshow.php?category='.$cid.'">'.T_('View Slideshow').'</a></li>
            </ul>';

                }

                echo '
            <ul class="photos clearfix">';

                $i = 0;
                foreach ($category_rows as $row)
                {
                    echo '
                <li class="photo">'.$row.'
                </li>';
                    $i++;
                }

                echo '
            </ul>';

            }
            // Output for Categories
            else
            {
                echo '
            <ul class="categories clearfix">';
                $i = 0;
                foreach ($category_rows as $row) {
                    echo '
                <li class="category">'.$row.'
                </li>';
                    $i++;
                }
                echo '
            </ul>';
            }
            
            // Display Pagination (unless it's front page latest comments
            if ($page > 0) {
                // Remove the LIMIT from the $sql statement 
                // used above, so we can get the total count
                $sql = substr($sql, 0, strpos($sql, 'LIMIT'));
                $this->db->query($sql) or displaySQLError(
                    'Page Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                $count = $this->db->count_rows();
                $total_pages = ceil($count / $perPage); 
                displayPages("index.php$urlPage", $page, $total_pages);
            }
            
        // If the sql statement returns no results and we're not trying to show 
        // the latest comments on the front page
        } elseif ($uid !== 0 && $cid !== 'comments') {
            echo '
            <div class="info-alert">
                <h2>'.T_('Oops!').'</h2>
                <p>'.T_('The Category you are trying to view is Empty.').'</p>
            </div>';
        }
    }

    /**
     * displayUploadForm 
     *
     * Displays the form for uploading photos to the photo gallery.
     * 
     * @param boolean $overrideMemoryLimit 
     * 
     * @return void
     */
    function displayUploadForm ($overrideMemoryLimit = false)
    {
        $categories = $this->getUserCategories();

        // We have existing categories
        if (count($categories) > 0) {
            $category_options = '
                            <input class="frm_text" type="text" id="new-category" name="new-category" size="35"/>
                            <select id="existing-categories" name="category">
                                <option value="0">&nbsp;</option>
                                '.buildHtmlSelectOptions($categories, '').'
                            </select>';

        // No Categories (force creation of new one)
        } else {
            $category_options = '
                            <input class="frm_text" type="text" name="new-category" size="50"/>';
        }

        $advanced_tagging = usingAdvancedTagging($this->currentUserId);

        $members = array();

        $autocomplete_selected  = '';

        // Setup the photo tagging options (autocomplete or checkbox)
        $tagging_options    = '';
        $users_list         = '';
        $users_lkup         = '';

        // Setup the list of active members for possible tags
        $sql = "SELECT `id` 
                FROM `fcms_users` 
                WHERE `activated` > 0";
        $this->db2->query($sql) or displaySQLError(
            'Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = $this->db2->get_row()) {
            $members[$r['id']] = getUserDisplayName($r['id'], 2);
        }
        asort($members);

        // Advanced (autocomplete)
        if ($advanced_tagging)
        {
            foreach ($members as $key => $value)
            {
                $users_list .= '"'.$key.': '.cleanOutput($value).'", ';
                $users_lkup .= 'users_lkup["'.$key.'"] = "'.cleanOutput($value).'"; ';
            }

            $users_list = substr($users_list, 0, -2); // remove the extra comma space at the end

            $tagging_options = '
                                <input type="text" id="autocomplete_input" class="frm_text autocomplete_input" 
                                    autocomplete="off" size="50" tabindex="3"/>
                                <div id="autocomplete_instructions" class="autocomplete_instructions">
                                    '.T_('Type name of person...').'
                                </div>
                                <ul id="autocomplete_selected" class="autocomplete_selected"></ul>
                                <div id="autocomplete_search" class="autocomplete_search" style="display:none"></div>
                                <script type="text/javascript">
                                //<![CDATA[
                                Event.observe(window, "load", function() {
                                    var users_list = [ '.$users_list.' ];
                                    var users_lkup = new Array();
                                    '.$users_lkup.'
                                    new Autocompleter.Local(
                                        "autocomplete_input", "autocomplete_search", users_list, {
                                            fullSearch: true,
                                            partialChars: 1,
                                            updateElement: newUpdateElement
                                        }
                                    );
                                });
                                //]]>
                                </script>';
        }
        // Basic (checkbox)
        else
        {
            $tag_checkboxes = '';
            foreach ($members as $key => $value)
            {
                $tag_checkboxes .= '<label for="'.$key.'">';
                $tag_checkboxes .= '<input type="checkbox" id="'.$key.'" name="tagged[]" 
                    value="'.cleanOutput($key).'"/> '.$value.'</label>';
            }
            $tagging_options = '
                            <div class="multi-checkbox" style="margin: 0 auto;">
                                '.$tag_checkboxes.'
                            </div>';
        }

        // Display the form
        echo '
            <fieldset>
                <legend><span>'.T_('Upload Photos').'</span></legend>
                <p class="alignright">
                    <a class="help" href="../help.php#gallery-howworks">'.T_('Help').'</a>
                </p>
                <script type="text/javascript" src="../inc/js/scriptaculous.js"></script>
                <form id="autocomplete_form" enctype="multipart/form-data" action="?action=upload" method="post">
                    <div class="field-row clearfix">
                        <div class="field-label"><label><b>'.T_('Category').'</b></label></div>
                        <div class="field-widget">
                            '.$category_options.'
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label><b>'.T_('Photo').'</b></label></div>
                        <div class="field-widget">
                            <input name="photo_filename" type="file" size="50"/>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label><b>'.T_('Caption').'</b></label></div>
                        <div class="field-widget">
                            <input class="frm_text" type="text" name="photo_caption" size="50"/>
                        </div>
                    </div>
                    <div id="atag-options" class="field-row clearfix">
                        <div class="field-label"><label><b>'.T_('Who is in this Photo?').'</b></label></div>
                        <div class="field-widget">
                            '.$tagging_options.'
                        </div>
                    </div>
                    <div id="rotate-options">
                        <div class="field-label"><label><b>'.T_('Rotate').'</b></label></div>
                        <div class="field-widget">
                            <input type="radio" id="left" name="rotate" value="left"/>
                            <label for="left" class="radio_label">'.T_('Left').'</label>&nbsp;&nbsp; 
                            <input type="radio" id="right" name="rotate" value="right"/>
                            <label for="right" class="radio_label">'.T_('Right').'</label>
                        </div>
                    </div>
                    <input class="sub1" type="submit" id="addphoto" name="addphoto" value="'.T_('Submit').'"/>
                </form>';

    }

    /**
     * displayJavaUploadForm 
     *
     * Displays the form for uploading photos using the JumpLoader
     * java applet.
     * 
     * @return void
     */
    function displayJavaUploadForm ()
    {
        $categories = $this->getUserCategories();

        // We have existing categories
        if (count($categories) > 0) {
            $category_options = '
                    <input class="frm_text" type="text" id="new-category" name="new-category" size="35""/>
                    <select id="existing-categories" name="category">
                        <option value="0">&nbsp;</option>';
            foreach ($categories as $id => $name) {
                $category_options .= '
                        <option value="' . $id . '">' . cleanOutput($name) . '</option>';
            }
            $category_options .= '
                    </select>';

        // No Categories (force creation of new one)
        } else {
            $category_options = '
                    <input class="frm_text" type="text" id="new-category" name="new-category" size="50""/>';
        }

        // Are we using full sized photos?
        $sql = "SELECT `value` AS 'full_size_photos'
                FROM `fcms_config`
                WHERE `name` = 'full_size_photos'";
        $this->db->query($sql) or displaySQLError(
            'Full Size Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = $this->db->get_row();

        $scaledInstanceNames      = '<param name="uc_scaledInstanceNames" value="small,medium"/>';
        $scaledInstanceDimensions = '<param name="uc_scaledInstanceDimensions" value="150x150xcrop,600x600xfit"/>';
        $fullSizedPhotos          = '';

        if ($r['full_size_photos'] == 1) {
            $scaledInstanceNames      = '<param name="uc_scaledInstanceNames" value="small,medium,full"/>';
            $scaledInstanceDimensions = '<param name="uc_scaledInstanceDimensions" value="150x150xcrop,600x600xfit,1400x1400xfit"/>';
            $fullSizedPhotos          = '
                function sendFullSizedPhotos() {
                    var uploader = document.jumpLoaderApplet.getUploader();
                    var attrSet = uploader.getAttributeSet();
                    var attr = attrSet.createStringAttribute("full-sized-photos", "1");
                    attr.setSendToServer(true);
                }
                sendFullSizedPhotos();';
        }

        echo '
            <noscript>
                <style type="text/css">
                applet, .field-row {display: none;}
                #noscript {padding:1em;}
                #noscript p {background-color:#ff9; padding:3em; font-size:130%; line-height:200%;}
                #noscript p span {font-size:60%;}
                </style>
                <div id="noscript">
                <p>
                    '.T_('JavaScript must be enabled in order for you to use the Advanced Uploader. However, it seems JavaScript is either disabled or not supported by your browser.').'<br/>
                    <span>
                        '.T_('Either enable JavaScript by changing your browser options.').'<br/>
                        '.T_('or').'<br/>
                        '.T_('Enable the Basic Upload option by changing Your Settings.').'
                    </span>
                </p>
                </div>
            </noscript>
            <fieldset>
                <legend><span>'.T_('Upload Photos').'</span></legend>
                <form method="post" name="uploadForm">
                    <label><b>'.T_('Category').'</b></label>
                    '.$category_options.'
                    <br/>
                    <br/>
                    <applet name="jumpLoaderApplet"
                        code="jmaster.jumploader.app.JumpLoaderApplet.class"
                        archive="../inc/jumploader_z.jar"
                        width="540"
                        height="300"
                        mayscript>
                        <param name="uc_sendImageMetadata" value="true"/>
                        <param name="uc_uploadUrl" value="upload.php"/>
                        <param name="vc_useThumbs" value="true"/>
                        <param name="uc_uploadScaledImagesNoZip" value="true"/>
                        <param name="uc_uploadScaledImages" value="true"/>
                        '.$scaledInstanceNames.'
                        '.$scaledInstanceDimensions.'
                        <param name="uc_scaledInstanceQualityFactors" value="900"/>
                        <param name="uc_uploadFormName" value="uploadForm"/>
                        <param name="vc_lookAndFeel" value="system"/>
                        <param name="vc_uploadViewStartActionVisible" value="false"/>
                        <param name="vc_uploadViewStopActionVisible" value="false"/>
                        <param name="vc_uploadViewPasteActionVisible" value="false"/>
                        <param name="vc_uploadViewRetryActionVisible" value="false"/>
                        <param name="vc_uploadViewFilesSummaryBarVisible" value="false"/>
                        <param name="vc_uiDefaults" value="Panel.background=#eff0f4; List.background=#eff0f4;"/> 
                        <param name="ac_fireUploaderStatusChanged" value="true"/> 
                    </applet>
                    <br/>
                    <br/>
                    <input class="sub1" type="button" value="'.T_('Upload').'" id="start-upload" name="start-upload"/>
                </form>
                <script language="javascript">
                Event.observe("start-upload","click",function(){
                    var uploader = document.jumpLoaderApplet.getUploader();
                    var attrSet = uploader.getAttributeSet();

                    var newValue = $F("new-category");
                    var newAttr  = attrSet.createStringAttribute("new-category", newValue);
                    newAttr.setSendToServer(true);

                    if ($("existing-categories")) {
                        var value = $F("existing-categories");
                        var attr  = attrSet.createStringAttribute("category", value);
                        attr.setSendToServer(true);
                    }

                    uploader.startUpload();
                });'.$fullSizedPhotos.'
                function uploaderStatusChanged(uploader) {
                    if (uploader.isReady() && uploader.getFileCountByStatus(3) == 0) { 
                        window.location.href = "index.php?action=advanced";
                    }
                }
                </script>
            </fieldset>';
    }

    /**
     * displayEditPhotoForm 
     *
     * Displays a form for editing a photo.
     * 
     * @param   int     $photo  the photo id of the photo you want to edit
     * @param   string  $url    optional -- the url to go back to after form is submitted
     * @return  void
     */
    function displayEditPhotoForm ($photo, $url = '')
    {
        $photo = cleanInput($photo, 'int');

        $sql = "SELECT p.`user`, `filename`, `caption`, `name`, c.`id` AS category_id
                FROM `fcms_gallery_photos` AS p, `fcms_category` AS c 
                WHERE p.`id` = '$photo'
                AND p.`category` = c.`id`";
        $this->db->query($sql) or displaySQLError(
            'Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        if ($this->db->count_rows() > 0)
        {
            $row        = $this->db->get_row();

            $photo_user = cleanInput($row['user'], 'int');
            $filename   = basename($row['filename']);
            $caption    = cleanOutput($row['caption']);
            $cat_name   = cleanOutput($row['name']);
            $cat_id     = cleanOutput($row['category_id']);

            $categories     = $this->getUserCategories($photo_user);
            $cat_options    = buildHtmlSelectOptions($categories, $cat_id);

            $advanced_tagging = usingAdvancedTagging($photo_user);

            $prev_tagged    = array();
            $members        = array();

            $autocomplete_selected  = '';
            $prev_tagged_options    = '';

            // Setup the photo tagging options (autocomplete or checkbox)
            $tagging_options    = '';
            $users_list         = '';
            $users_lkup         = '';

            // Setup the list of users already tagged
            $sql = "SELECT `id`, `user` 
                    FROM `fcms_gallery_photos_tags` 
                    WHERE `photo` = '$photo'";
            $this->db2->query($sql) or displaySQLError(
                'Find Tagged Users Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            while ($r = $this->db2->get_row()) {
                $prev_tagged[$r['user']] = 1;
            }

            // Setup the list of active members for possible tags
            $sql = "SELECT `id` 
                    FROM `fcms_users` 
                    WHERE `activated` > 0";
            $this->db2->query($sql) or displaySQLError(
                'Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            while ($r = $this->db2->get_row()) {
                $members[$r['id']] = getUserDisplayName($r['id'], 2);
            }
            asort($members);

            // handle previously tagged members
            if (count($prev_tagged) > 0)
            {
                foreach ($prev_tagged as $id => $name)
                {
                    $prev_tagged_options .= '<input type="hidden" name="prev_tagged_users[]" value="'.$id.'"/>';
                    if ($advanced_tagging) {
                        $prev_tagged_options .= '<input type="hidden" name="tagged[]" class="tagged" value="'.$id.'"/>';
                    }
                    $autocomplete_selected .= '<li>'.$members[$id].'<a href="#" alt="'.$id.'" onclick="removeTagged()">x</a></li>';
                }
            }
            

            // Advanced (autocomplete)
            if ($advanced_tagging)
            {
                foreach ($members as $key => $value)
                {
                    $users_list .= '"'.$key.': '.cleanOutput($value).'", ';
                    $users_lkup .= 'users_lkup["'.$key.'"] = "'.cleanOutput($value).'"; ';
                }

                $users_list = substr($users_list, 0, -2); // remove the extra comma space at the end

                $tagging_options = '
                                <input type="text" id="autocomplete_input" class="frm_text autocomplete_input" 
                                    autocomplete="off" size="50" tabindex="3"/>
                                <div id="autocomplete_instructions" class="autocomplete_instructions">
                                    '.T_('Type name of person...').'
                                </div>
                                <ul id="autocomplete_selected" class="autocomplete_selected"></ul>
                                <div id="autocomplete_search" class="autocomplete_search" style="display:none"></div>
                                <script type="text/javascript">
                                //<![CDATA[
                                Event.observe(window, "load", function() {
                                    var users_list = [ '.$users_list.' ];
                                    var users_lkup = new Array();
                                    '.$users_lkup.'
                                    new Autocompleter.Local(
                                        "autocomplete_input", "autocomplete_search", users_list, {
                                            fullSearch: true,
                                            partialChars: 1,
                                            updateElement: newUpdateElement
                                        }
                                    );
                                    initPreviouslyTagged(users_lkup);
                                });
                                //]]>
                                </script>';
            }
            // Basic (checkbox)
            else
            {
                $tag_checkboxes = '';
                foreach ($members as $key => $value)
                {
                    $check = isset($prev_tagged[$key]) ? 'checked="checked"' : '';

                    $tag_checkboxes .= '<label for="' . $key . '">';
                    $tag_checkboxes .= '<input type="checkbox" id="'.cleanOutput($key).'" 
                        name="tagged[]"  value="'.cleanOutput($key).'" '.$check.'/> '.$value;
                    $tag_checkboxes .= '</label>';
                }
                $tagging_options = '
                                <div class="multi-checkbox">
                                    '.$tag_checkboxes.'
                                </div>';
            }
            
            // Display the form
            echo '
                <fieldset>
                    <script type="text/javascript" src="../inc/js/scriptaculous.js"></script>
                    <legend><span>'.T_('Edit Photo').'</span></legend>
                    <img class="thumbnail" src="../uploads/photos/member'.$photo_user.'/tb_'.$filename.'"/>
                    <form id="autocomplete_form" enctype="multipart/form-data" action="index.php?'.$url.'" method="post">
                        <div class="field-row clearfix">
                            <div class="field-label"><label><b>'.T_('Change Category').'</b></label></div>
                            <div class="field-widget">
                                <select class="frm_sel" name="category" tabindex="1">
                                    '.$cat_options.'
                                </select>
                            </div>
                        </div>
                        <div class="field-row clearfix">
                            <div class="field-label"><label><b>'.T_('Caption').'</b></label></div>
                            <div class="field-widget">
                                <input class="frm_text" type="text" name="photo_caption" size="50" tabindex="2" value="'.$caption.'"/>
                            </div>
                        </div>
                        <div class="field-row clearfix">
                            <div class="field-label"><label><b>'.T_('Who is in this Photo?').'</b></label></div>
                            <div class="field-widget">
                                '.$tagging_options.'
                            </div>
                        </div>
                        <p>
                            '.$prev_tagged_options.'
                            <input type="hidden" name="photo_id" id="photo_id" value="'.$photo.'"/>
                            <input class="sub1" type="submit" name="add_editphoto" value="'.T_('Edit').'"/> 
                            '.T_('or').' <a href="index.php?'.$url.'">'.T_('Cancel').'</a>
                        </p>
                    </form>
                </fieldset>';
        } else {
            echo '
            <p class="error-alert">'.T_('Could not edit Photo.  Photo ID does not exist.').'</p>';
        }
    }

    /**
     * displayAdvancedUploadEditForm 
     * 
     * Displays a form for editing photos that were uploaded using the advanced uploader.
     * 
     * @return void
     */
    function displayAdvancedUploadEditForm ()
    {
        // Do we have a valid category?
        if (isset($_SESSION['photos']['error'])) {
            // clear the photos in the session
            unset($_SESSION['photos']);
            echo '<div class="error-alert">'.T_('You must create a new category, or select an existing category.').'</div>';
            $this->displayJavaUploadForm('');
            return;
        }

        $advanced_tagging = usingAdvancedTagging($this->currentUserId);

        $members = array();

        $autocomplete_selected  = '';

        // Setup the photo tagging options (autocomplete or checkbox)
        $tagging_options    = '';
        $users_list         = '';
        $users_lkup         = '';
        $js                 = '';
        $js_list            = '';
        $js_autocompleter   = '';

        // Setup the list of active members for possible tags
        $sql = "SELECT `id` 
                FROM `fcms_users` 
                WHERE `activated` > 0";
        $this->db2->query($sql) or displaySQLError(
            'Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = $this->db2->get_row()) {
            $members[$r['id']] = getUserDisplayName($r['id'], 2);
        }
        asort($members);

        // Advanced (autocomplete)
        if ($advanced_tagging)
        {
            foreach ($members as $key => $value)
            {
                $users_list .= '"'.$key.': '.cleanOutput($value).'", ';
                $users_lkup .= 'users_lkup["'.$key.'"] = "'.cleanOutput($value).'"; ';
            }

            $users_list = substr($users_list, 0, -2); // remove the extra comma space at the end

            $js_list = '
                <script type="text/javascript">
                //<![CDATA[
                Event.observe(window, "load", function() {
                    var users_list = [ '.$users_list.' ];
                    var users_lkup = new Array();
                    '.$users_lkup;
        }

        // Display the form
        echo '
                <script type="text/javascript" src="../inc/js/scriptaculous.js"></script>
                <form id="autocomplete_form" action="index.php?action=advanced" method="post">
                    <fieldset>
                        <legend><span>'.T_('Edit Photos').'</span></legend>';

        // Loop over each photo
        $i=0;
        foreach ($_SESSION['photos'] AS $photo)
        {
            // Advanced (autocomplete)
            if ($advanced_tagging)
            {
                $tagging_options = '
                            <input type="text" id="autocomplete_input_'.$i.'" class="frm_text autocomplete_input" 
                                autocomplete="off" size="50" tabindex="3"/>
                            <div id="autocomplete_instructions_'.$i.'" class="autocomplete_instructions">
                                '.T_('Type name of person...').'
                            </div>
                            <ul id="autocomplete_selected_'.$i.'" class="autocomplete_selected"></ul>
                            <div id="autocomplete_search_'.$i.'" class="autocomplete_search" style="display:none"></div>';

                $js_autocompleter .= '
                    new Autocompleter.Local(
                        "autocomplete_input_'.$i.'", "autocomplete_search_'.$i.'", users_list, {
                            fullSearch: true,
                            partialChars: 1,
                            updateElement: newMultiUpdateElement
                        }
                    );';
            }
            // Basic (checkbox)
            else
            {
                $tag_checkboxes = '';
                foreach ($members as $key => $value)
                {
                    $tag_checkboxes .= '<label for="'.$key.$i.'">';
                    $tag_checkboxes .= '<input type="checkbox" id="'.$key.$i.'" name="tagged['.$i.'][]" 
                        value="'.cleanOutput($key).'"/> '.$value.'</label>';
                }
                $tagging_options = '
                            <div class="multi-checkbox">
                                '.$tag_checkboxes.'
                            </div>';
            }

            echo '
                        <img style="float:right" src="../uploads/photos/member'.$this->currentUserId.'/tb_'.basename($photo['filename']).'"/>
                        <p>
                            '.T_('Caption').'<br/>
                            <input type="text" class="frm_text" name="caption[]" width="50"/>
                            <input type="hidden" name="id[]" value="'.(int)$photo['id'].'"/>
                            <input type="hidden" name="category[]" value="'.(int)$photo['category'].'"/>
                        </p>
                        <div>
                            '.T_('Who is in this Photo?').'<br/>
                            '.$tagging_options.'
                        </div><br/>
                        <hr/>';
            $i++;
        }

        if ($advanced_tagging)
        {
            $js = $js_list.$js_autocompleter.'
                });
                //]]>
                </script>';
        }

        echo '
                        <br/>
                        <p>
                            <input class="sub1" type="submit" name="submit_advanced_edit" id="submit_advanced_edit" value="'.T_('Save').'"/> 
                            '.T_('or').' 
                            <a href="index.php?action=upload">'.T_('Cancel').'</a>
                        </p>
                    </fieldset>
                </form>
                '.$js;
    }

    /**
     * uploadPhoto 
     * 
     * Uploads a photo to the /uploads/photos/memberX/ directory where x is the user id.
     * Adds photo info to the db, creates a thumbnail, resizes middle sized photo 
     * and rotates the photo if desired.
     *
     * @param   int     $category
     * @param   array   $photo                  array of uploaded photo data
     * @param   string  $caption
     * @param   string  $rotateoptions
     * @param   boolean $overrideMemoryWarning
     * @param   int     $thumb_max_width        defaults to 150px
     * @param   int     $thumb_max_height       defaults to 150px
     * @param   int     $main_max_width         defaults to 600px
     * @param   int     $main_max_height        defaults to 600px
     *
     * @return  int
     */
    function uploadPhoto (
        $category, 
        $photo, 
        $caption, 
        $rotateoptions, 
        $overrideMemoryWarning = false,
        $thumb_max_width = 150, 
        $thumb_max_height = 150, 
        $main_max_width = 600, 
        $main_max_height = 600
    )
    {
        // Valid photo?
        if ($photo['size'] <= 0)
        {
            echo '
            <p class="error-alert">'.T_('Photo is corrupt or missing.').'</p>';

            return false;
        }

        // Create new directory if needed
        if (!file_exists('../uploads/photos/member'.$this->currentUserId))
        {
            mkdir('../uploads/photos/member'.$this->currentUserId);
        }

        // Insert new photo record
        $sql = "INSERT INTO `fcms_gallery_photos`
                    (`date`, `caption`, `category`, `user`)
                VALUES(
                    NOW(), 
                    '$caption', 
                    '$category', 
                    '".$this->currentUserId."'
                )";
        if (!$this->db->query($sql))
        {
            displaySQLError('Add Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }

        $id = mysql_insert_id();

        // Temporarily set name so we can get extension, then change name below
        $this->img->name = $photo['name'];
        $this->img->getExtension();

        // Setup the array of photos that need uploaded
        $upload_photos = array(
            'main'  => array(
                'resize'    => true,
                'prefix'    => '',
                'width'     => $main_max_width,
                'height'    => $main_max_height
            ),
            'thumb' => array(
                'resize'    => true,
                'prefix'    => 'tb_',
                'width'     => $thumb_max_width,
                'height'    => $thumb_max_height
            ),
        );

        if ($this->usingFullSizePhotos())
        {
            $upload_photos['full'] = array(
                'resize'    => false,
                'prefix'    => 'full_',
                'width'     => 0,
                'height'    => 0
            );
        }

        // Loop through each photo that needs uploaded
        foreach ($upload_photos as $key => $value)
        {
            $resize = $upload_photos[$key]['resize'];
            $prefix = $upload_photos[$key]['prefix'];
            $width  = $upload_photos[$key]['width'];
            $height = $upload_photos[$key]['height'];

            // Setup image upload settings
            $this->img->name          = $prefix.$id.'.'.$this->img->extension;
            $this->img->destination   = '../uploads/photos/member'.$this->currentUserId.'/';
            $this->img->resizeSquare  = $key == 'thumb' ? true : false;

            if ($key == 'main')
            {
                // Update photo record
                $sql = "UPDATE `fcms_gallery_photos` 
                        SET `filename` = '".$this->img->name."' 
                        WHERE `id` = $id";
                if (!$this->db->query($sql))
                {
                    displaySQLError('Update Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
                    return false;
                }
            }

            // Upload photo
            $this->img->upload($photo);

            if ($this->img->error == 1)
            {
                echo '
                <p class="error-alert">
                    '.sprintf(T_('Photo [%s] is not a supported photo type.  Photos must be of type (.jpg, .jpeg, .gif, .bmp or .png).'), $this->img->name).'
                </p>';

                return false;
            }

            // Rotate
            if ($rotateoptions == 'left')
            {
                $this->img->rotate(90);
            }
            elseif ($rotateoptions == 'right')
            {
                $this->img->rotate(270);
            }

            // Resize
            if ($resize)
            {
                $this->img->resize($width, $height);
            }

            // Errors?
            if ($this->img->error > 0)
            {
                $this->handleImageErrors($id);
                return false;
            }
        }

        echo '
            <p class="ok-alert">
                <b>'.T_('The following photo was added successfully.').'</b><br/><br/>
                <img src="../uploads/photos/member'.$this->currentUserId.'/'.$this->img->name.'" alt="'.cleanOutput($caption).'"/>
            </p>';

        return $id;
    }

    /**
     * displayAddCatForm 
     *
     * Displays the form for adding categories 
     *
     * @return void
     */
    function displayAddCatForm ()
    {
        $cat_list = '';
        
        // Setup the list of categories for edit/delete
        $sql = "SELECT * FROM `fcms_category` 
                WHERE `user` = '".$this->currentUserId."'
                AND `type` = 'gallery'";
        $this->db->query($sql) or displaySQLError(
            'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            while ($row = $this->db->get_row()) {
                $name = cleanOutput($row['name']);
                $cat_list .= '<li>
                    <form class="frm_line" action="index.php?action=category" method="post">
                        <input class="frm_text" type="text" name="cat_name" id="cat_name" size="60" value="'.$name.'"/>
                        <input type="hidden" name="cid" id="cid" value="'.(int)$row['id'].'"/> &nbsp;
                        <input type="submit" name="editcat" class="editbtn" value="'.T_('Edit').'" title="'.T_('Edit Category').'"/> &nbsp;
                        <input type="submit" name="delcat" class="delbtn" value="'.T_('Delete').'" title="'.T_('Delete Category').'"/>
                    </form>
                </li>';
            }
        } else {
            $cat_list .= "<li><i>".T_('No Categories created yet.')."</i></li>";
        }
        
        // Display the form
        echo '
            <fieldset>
                <legend><span>'.T_('Edit Category').'</span></legend>
                <ul class="gallery_cat">
                    '.$cat_list.'
                </ul>
            </fieldset>';
    }

    /**
     * getUserCategories 
     * 
     * Returns an array of the categories for the given user.
     *
     * @param   int     $userid 
     * @return  array
     */
    function getUserCategories ($userid = 0)
    {
        if ($userid == 0) {
            $userid = $this->currentUserId;
        }

        $sql = "SELECT `id`, `name` FROM `fcms_category` 
                WHERE `user` = '" . cleanInput($userid, 'int') . "'
                AND `type` = 'gallery'
                ORDER BY `id` DESC";
        $this->db->query($sql) or displaySQLError(
            'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        $categories = array();

        while($row = $this->db->get_row()) {
            $categories[$row['id']] = $row['name'];
        }

        return $categories;
    }

    /**
     * displayAdminQuickDelete 
     * 
     * Displays a form to delete entire category by id.
     *
     * @return  void
     */
    function displayAdminQuickDelete ()
    {
        echo '
            <form id="quick_delete" action="gallery.php" method="post">
                <h2>'.T_('Quick Delete').'</h2>
                <p>
                    <label for="category">'.T_('Category').'</label>
                    <input type="text" id="category" name="category" maxlength="11"/>
                    <input type="submit" class="sub1" name="submit" value="'.T_('Delete All').'"/>
                </p>
            </form>';
    }

    /**
     * displayAdminDeleteCategories
     * 
     * Displays the Admin options to edit/delete categories
     * 
     * @param int $page 
     *
     * @return  void
     */
    function displayAdminDeleteCategories ($page)
    {
        $perPage = 10;
        $from = ($page * $perPage) - $perPage;

        $sql = "SELECT * 
                FROM (
                    SELECT p.`id`, p.`date`, p.`filename`, c.`name`, p.`user`, p.`category`
                    FROM `fcms_gallery_photos` AS p, `fcms_category` AS c
                    WHERE p.`category` = c.`id`
                    ORDER BY `date` DESC
                ) AS sub
                GROUP BY `category`
                ORDER BY `date` DESC 
                LIMIT $from, $perPage";
        if (!$this->db->query($sql)) {
            displaySQLError('Latest Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0) {
            echo '
            <p class="info-alert">'.T_('No photos have been added yet.').'</p>';
            return;
        }

        echo '
            <form id="check_all_form" name="check_all_form" action="gallery.php" method="post">
                <ul class="photos clearfix">';

        while ($row = $this->db->get_row())
        {
            $count = $this->getCategoryPhotoCount($row['category']);
            echo '
                    <li class="tag_photo">
                        <p>
                            <b>'.$row['name'].'</b><br/>
                            <i>'.sprintf(T_('%d photos'), $count).'</i>
                        </p>
                        <label for="'.$row['category'].'">
                            <span></span>
                            <img src="../uploads/photos/member'.$row['user'].'/tb_'.basename($row['filename']).'" 
                                alt="'.cleanOutput($row['name']).'"/>
                        </label>
                        <input type="checkbox" id="'.$row['category'].'" name="bulk_actions[]" value="'.$row['category'].'"/>
                        <p>
                            <a href="?edit='.$row['category'].'">'.T_('Edit').'</a>
                        </p>
                    </li>';

        }

        echo '
                </ul>
                <p><input type="submit" class="sub1" id="deleteAll" name="deleteAll" value="'.T_('Delete All').'"/></p>
            </form>';

        // Pagination

        // Remove the LIMIT from the $sql statement 
        // used above, so we can get the total count
        $sql = substr($sql, 0, strpos($sql, 'LIMIT'));
        $this->db->query($sql) or displaySQLError(
            'Page Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $count = $this->db->count_rows();
        $total_pages = ceil($count / $perPage); 
        displayPages("gallery.php", $page, $total_pages);
    }

    /**
     * displayAdminDeletePhotos
     * 
     * Displays the Admin options to edit/delete photos from a specific category
     * 
     * @param int $id 
     *
     * @return  void
     */
    function displayAdminDeletePhotos ($id)
    {
        $id = cleanInput($id, 'int');

        $sql = "SELECT p.`id`, p.`date`, p.`filename`, c.`name` AS category, p.`user`, p.`caption`, p.`views`
                FROM `fcms_gallery_photos` AS p, `fcms_category` AS c
                WHERE p.`category` = '$id'
                AND p.`category` = c.`id`";
        if (!$this->db->query($sql)) {
            displaySQLError('Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0) {
            echo '
            <p class="info-alert">'.T_('This category contains no photos.').'</p>';
            return;
        }

        $this->displayAdminMenu();

        echo '
            <form id="check_all_form" name="check_all_form" action="gallery.php?edit='.$id.'" method="post">
                <ul class="photos clearfix">';

        while ($row = $this->db->get_row())
        {
            echo '
                    <li class="tag_photo">
                        <label for="'.$row['id'].'">
                            <span></span>
                            <img src="../uploads/photos/member'.$row['user'].'/tb_'.basename($row['filename']).'" 
                                alt="'.cleanOutput($row['caption']).'"/>
                        </label>
                        <input type="checkbox" id="'.$row['id'].'" name="bulk_actions[]" value="'.$row['id'].'"/>
                    </li>';
        }

        echo '
                </ul>
                <p style="text-align: right">
                    <input type="submit" class="sub1" id="deleteAllPhotos" 
                        name="deleteAllPhotos" value="'.T_('Delete Selected').'"/>
                </p>
            </form>';
    }

    /**
     * displayAdminMenu 
     * 
     * @return  void
     */
    function displayAdminMenu ()
    {
        echo '
            <div class="clearfix" id="sections_menu">
                <ul>
                    <li><a href="gallery.php">'.T_('Categories').'</a></li>
                </ul>
            </div>';
    }

    /**
     * getCategoryPhotoCount 
     * 
     * @param int $id 
     * 
     * @return  void
     */
    function getCategoryPhotoCount ($id)
    {
        $id = cleanInput($id, 'int');

        $sql = "SELECT COUNT(`id`) AS count
                FROM `fcms_gallery_photos`
                WHERE `category` = '$id'
                LIMIT 1";
        if (!$result = mysql_query($sql)) {
            return 0;
        }
        $r = mysql_fetch_array($result);
        return $r['count'];
    }

    /**
     * getPhotoInfo
     * 
     * @param int $id 
     * 
     * @return  void
     */
    function getPhotoInfo ($id)
    {
        $id = cleanInput($id, 'int');

        $sql = "SELECT *
                FROM `fcms_gallery_photos`
                WHERE `id` = '$id'
                LIMIT 1";
        if (!$this->db->query($sql)) {
            displaySQLError('Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return;
        }
        return $this->db->get_row();
    }

    /**
     * usingFullSizePhotos 
     * 
     * @return void
     */
    function usingFullSizePhotos ()
    {
        $sql = "SELECT `value` AS 'full_size_photos'
                FROM `fcms_config`
                WHERE `name` = 'full_size_photos'";
        if (!$this->db->query($sql))
        {
            displaySQLError('Full Size Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return false;
        }

        $r = $this->db->get_row();

        if ($r['full_size_photos'] == 1)
        {
            return true;
        }

        return false;
    }

    /**
     * handleImageErrors 
     * 
     *  1   Image type not supported or invalid
     *  2   GD doesn't support image type
     *  3   Could not write new image
     *  4   Not enough memory to resize image
     * 
     * @param int $id 
     * 
     * @return void
     */
    function handleImageErrors ($id)
    {
        switch ($this->img->error)
        {
            case 2:

                echo '
            <div class="error-alert">
                '.T_('GD Library is either not installed or does not support this file type.').'
            </div>';

                break;

            case 3:

                echo '
            <div class="error-alert">
                '.T_('Could not write file, check folder permissions.').'
            </div>';

                break;

            case 4:

                // Remove the photo from the DB
                $sql = "DELETE FROM `fcms_gallery_photos` 
                        WHERE `id` = '$id'";
                mysql_query($sql) or displaySQLError(
                    'Delete Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                
                // Remove the Photo from the server
                unlink("photos/member".$this->currentUserId."/".$this->img->name);

                echo '
            <div class="info-alert">
                <h2>'.T_('Out of Memory Warning.').'</h2>
                <p>
                    '.T_('The photo you are trying to upload is quite large and the server might run out of memory if you continue.').' 
                    '.T_('It is recommended that you try to upload this photo using the "Advanced Uploader" instead.').'
                    <small>('.number_format($this->img->memoryNeeded).' / '.number_format($this->img->memoryAvailable).')</small>
                </p>
                <h3>'.T_('What do you want to do?').'</h3>
                <p>
                    <a href="?action=upload&amp;advanced=on">'.T_('Use the "Advanced Uploader"').'</a>&nbsp; 
                    '.T_('or').'
                    <a class="u" href="index.php">'.T_('Cancel').'</a>
                </p>
            </div>';
                break;

            default:

                echo '
            <div class="error-alert">'.T_('An unknown error has occured.').'</div>';

                break;
        }
    }

    /**
     * displayMassTagCategory 
     * 
     * Displays the form for tagging users in multiple categories.
     * 
     * @param int $category 
     * @param int $user 
     * 
     * @return void
     */
    function displayMassTagCategory ($category, $user)
    {
        $advanced_tagging = usingAdvancedTagging($this->currentUserId);
        $members          = array();
        $tagging_options  = '';
        $users_list       = '';
        $users_lkup       = '';
        $js               = '';
        $js_list          = '';
        $js_autocompleter = '';

        // Setup the list of active members for possible tags
        $sql = "SELECT `id` 
                FROM `fcms_users` 
                WHERE `activated` > 0";

        if (!$this->db->query($sql))
        {
            displaySQLError('Members Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        }

        if ($this->db->count_rows() < 0)
        {
            echo '<p class="error">'.T_('No members found.').'</p>';
            return;
        }

        $members = array();

        while ($r = $this->db->get_row())
        {
            $members[$r['id']] = getUserDisplayName($r['id'], 2);
        }

        asort($members);

        // Get photos in category
        $sql = "SELECT u.`id` AS uid, `category` AS cid, p.`id` AS pid, `caption`, c.`name` AS category, `filename` 
                FROM `fcms_category` AS c, `fcms_gallery_photos` AS p, `fcms_users` AS u 
                WHERE p.`user` = '$user' 
                AND `category` = '$category'
                AND p.`user` = u.`id`
                AND `category` = c.`id`
                ORDER BY p.`id`";
        if (!$this->db->query($sql))
        {
            displaySQLError('Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() < 0)
        {
            echo '<p class="error">'.T_('No photos found in this category.').'</p>';
            return;
        }

        // Advanced (autocomplete)
        if ($advanced_tagging)
        {
            foreach ($members as $key => $value)
            {
                $users_list .= '"'.$key.': '.cleanOutput($value).'", ';
                $users_lkup .= 'users_lkup["'.$key.'"] = "'.cleanOutput($value).'"; ';
            }

            $users_list = substr($users_list, 0, -2); // remove the extra comma space at the end

            $js_list = '
                <script type="text/javascript">
                //<![CDATA[
                Event.observe(window, "load", function() {
                    var users_list = [ '.$users_list.' ];
                    var users_lkup = new Array();
                    '.$users_lkup;
        }

        // Display the form
        echo '
                <script type="text/javascript" src="../inc/js/scriptaculous.js"></script>
                <form id="autocomplete_form" action="index.php?uid='.$user.'&amp;cid='.$category.'" method="post">
                    <fieldset>
                        <legend><span>'.T_('Tag Members In Photos').'</span></legend>';

        // Loop over each photo
        $i = 1;
        while ($row = $this->db->get_row())
        {
            $prev_tagged         = array();
            $prev_tagged_options = '';

            // Setup the list of users already tagged
            $sql = "SELECT `id`, `user` 
                    FROM `fcms_gallery_photos_tags` 
                    WHERE `photo` = '".$row['pid']."'";

            if (!$this->db2->query($sql))
            {
                displaySQLError('Tagged Users Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            }

            while ($r = $this->db2->get_row()) {
                $prev_tagged[$r['user']] = 1;
            }

            // handle previously tagged members
            if (count($prev_tagged) > 0)
            {
                foreach ($prev_tagged as $id => $name)
                {
                    $prev_tagged_options .= '<input type="hidden" name="prev_tagged_users['.$row['pid'].'][]" value="'.$id.'"/>';
                    if ($advanced_tagging) {
                        $prev_tagged_options .= '<input type="hidden" id="tagged_'.$row['pid'].'" name="tagged['.$row['pid'].'][]" class="tagged" value="'.$id.'"/>';
                    }
                }
            }

            // Advanced (autocomplete)
            if ($advanced_tagging)
            {
                $tagging_options = '
                            <input type="text" id="autocomplete_input_'.$row['pid'].'" class="frm_text autocomplete_input" 
                                autocomplete="off" size="50" tabindex="'.$i.'"/>
                            <div id="autocomplete_instructions_'.$row['pid'].'" class="autocomplete_instructions">
                                '.T_('Type name of person...').'
                            </div>
                            <ul id="autocomplete_selected_'.$row['pid'].'" class="autocomplete_selected"></ul>
                            <div id="autocomplete_search_'.$row['pid'].'" class="autocomplete_search" style="display:none"></div>';

                $js_autocompleter .= '
                    new Autocompleter.Local(
                        "autocomplete_input_'.$row['pid'].'", "autocomplete_search_'.$row['pid'].'", users_list, {
                            fullSearch: true,
                            partialChars: 1,
                            updateElement: newMultiUpdateElement
                        }
                    );
                    initMultiPreviouslyTagged('.$row['pid'].', users_lkup);';
            }
            // Basic (checkbox)
            else
            {
                $tag_checkboxes = '';
                foreach ($members as $key => $value)
                {
                    $check = isset($prev_tagged[$key]) ? 'checked="checked"' : '';

                    $tag_checkboxes .= '<label for="'.$key.$i.'">';
                    $tag_checkboxes .= '<input type="checkbox" id="'.$key.$i.'" name="tagged['.$row['pid'].'][]" 
                        value="'.cleanOutput($key).'" '.$check.'/> '.$value.'</label>';
                }
                $tagging_options = '
                            <div class="multi-checkbox">
                                '.$tag_checkboxes.'
                            </div>';
            }

            echo '
                        <img style="float:right" src="../uploads/photos/member'.$user.'/tb_'.basename($row['filename']).'"/>
                        <p>
                            '.T_('Caption').'<br/>
                            '.cleanOutput($row['caption']).'
                        </p>
                        <div>
                            '.T_('Who is in this Photo?').'<br/>
                            '.$tagging_options.'
                            '.$prev_tagged_options.'
                        </div><br/>
                        <hr/>';
            $i++;
        }

        if ($advanced_tagging)
        {
            $js = $js_list.$js_autocompleter.'
                });
                //]]>
                </script>';
        }

        echo '
                        <br/>
                        <p>
                            <input class="sub1" type="submit" name="submit_mass_tag" id="submit_mass_tag" value="'.T_('Save').'"/> 
                            '.T_('or').' 
                            <a href="index.php?uid='.$user.'&amp;cid='.$category.'">'.T_('Cancel').'</a>
                        </p>
                    </fieldset>
                </form>
                '.$js;
    }

    /**
     * getPhotoComments 
     * 
     * @param int $pid 
     * 
     * @return void
     */
    function getPhotoComments ($pid)
    {
        $comments = array();

        $sql = "SELECT c.`id`, `comment`, `date`, `fname`, `lname`, `username`, `user`, `avatar` 
                FROM `fcms_gallery_comments` AS c, `fcms_users` AS u 
                WHERE `photo` = '$pid' 
                AND c.`user` = u.`id` 
                ORDER BY `date`";

        if (!$this->db->query($sql))
        {
            displaySQLError('Comments Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        { 
            while ($row = $this->db->get_row())
            {
                $comments[] = $row;
            }
        }

        return $comments;
    }
}
