<?php
/**
 * PhotoGallery
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
require_once 'utils.php';
require_once 'image_class.php';
require_once 'datetime.php';
require_once 'socialmedia.php';

/**
 * PhotoGallery 
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
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
     * @param int $currentUserId Id of authed user
     * 
     * @return void
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
     * @param string $uid Member id
     * @param string $cid Category id
     * 
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

        if (isset($_GET['search']))
        {
            $search = ' selected';
        }
        elseif ($uid == '')
        {
            $uid  = '0';
            $home = ' selected';
        }
        elseif ($uid == '0' && $cid == '')
        {
            $member = ' selected';
        }

        if ($cid == 'toprated')
        {
            $rated = ' selected';
        }
        elseif ($cid == 'mostviewed')
        {
            $viewed = ' selected';
        }

        if ($uid == $this->currentUserId && $cid == '')
        {
            $my = ' selected';
        }

        echo '
            <div id="gallery_menu">
                <div id="sections_menu">
                    <ul>
                        <li><a class="'.$home.'" href="index.php">'.T_('Latest').'</a></li>
                        <li><a class="'.$member.'" href="?uid=0">'.T_('Members').'</a></li>
                        <li><a class="'.$rated.'" href="?uid='.$uid.'&amp;cid=toprated">'.T_('Top Rated').'</a></li>
                        <li><a class="'.$viewed.'" href="?uid='.$uid.'&amp;cid=mostviewed">'.T_('Most Viewed').'</a></li>
                        <li><a class="'.$my.'" href="?uid='.$this->currentUserId.'">'.T_('My Photos').'</a></li>
                        <li><a class="'.$search.'" href="?search=form">'.T_('Search').'</a></li>
                    </ul>
                </div>';

        $access = checkAccess($this->currentUserId);

        if ($access <= 3 or $access == NON_POSTER_USER or $access == PHOTOGRAPHER_USER)
        {
            echo '
                <div id="actions_menu">
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
        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            while ($row = $this->db->get_row())
            {
                $displayNameArr[$row['id']] = getUserDisplayName($row['id'], 2);
            }

            asort($displayNameArr);
        }

        echo '
                <fieldset>
                    <legend><span>'.T_('Search').'</span></legend>
                    <form action="index.php" method="get">
                        <div class="field-row">
                            <div class="field-label"><b>'.T_('Photo Uploaded By').'</b></div>
                            <div class="field-widget">
                                <select name="uid">
                                    <option value="0"></option>';

        foreach ($displayNameArr as $key => $value)
        {
            echo '
                                    <option value="'.(int)$key.'">'.cleanOutput($value).'</option>';
        }

        echo '
                                </select>
                            </div>
                        </div>
                        <div class="field-row">
                            <div class="field-label"><b>'.T_('Members In Photo').'</b></div>
                            <div class="field-widget">
                                <select name="cid">
                                    <option value="all"></option>';

        foreach ($displayNameArr as $key => $value)
        {
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
                    SELECT p.`id`, p.`date`, p.`filename`, c.`name`, p.`user`, p.`category`,
                        p.`external_id`, e.`thumbnail`, u.`fname`, u.`lname`
                    FROM `fcms_gallery_photos` AS p
                    LEFT JOIN `fcms_category` AS c               ON p.`category`    = c.`id`
                    LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                    LEFT JOIN `fcms_users` AS u                  ON p.`user`        = u.`id`
                    ORDER BY `date` DESC
                ) AS sub
                GROUP BY `category`
                ORDER BY `date` DESC LIMIT 5";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '
                <div class="blank-state">
                    <h2>'.T_('Nothing to see here').'</h2>
                    <h3>'.T_('Currently no one has added any photos').'</h3>
                    <h3><a href="?action=upload">'.T_('Why don\'t you upload some photos now?').'</a></h3>
                </div>';
            return false;
        }

        echo '
                <div class="categories">';

        while ($row = $this->db->get_row())
        {
            $date = fixDate(T_('M. j, Y'), $this->tzOffset, $row['date']);

            if ($row['filename'] == 'noimage.gif' && $row['external_id'] != null)
            {
                $photoSrc = $row['thumbnail'];
            }
            else
            {
                $photoSrc = '../uploads/photos/member'.(int)$row['user'].'/tb_'.basename($row['filename']);
            }

            echo '
                    <div class="category">
                        <a href="?uid='.$row['user'].'&amp;cid='.$row['category'].'">
                            <div class="photo-title">
                                <img class="photo" src="'.$photoSrc.'" alt="'.cleanOutput($row['name']).'"/>
                                <div class="overlay">
                                    <p>'.cleanOutput($row['name']).'</p>
                                </div>
                            </div><!--/photo-title-->
                        </a>
                        <div class="footer">
                            <b>'.cleanOutput($row['fname']).' '.cleanOutput($row['lname']).'</b>
                            <i>'.$date.'</i>
                        </div>
                    </div><!--/category-->';
        }

        echo '
                </div>';

        return true;
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
     * @param string $uid the user's id or 0
     * @param string $cid the category id, 'tagged#', 'comments', 'toprated', 'mostviewed' or 'all'
     * @param string $pid the photo id 
     *
     * @return  void
     */
    function showPhoto ($uid, $cid, $pid)
    {
        $uid = (int)$uid;
        $pid = (int)$pid;

        list($breadcrumbs, $cid, $urlcid, $sql) = $this->getShowPhotoParams($uid, $cid);

        if (!$this->db2->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        // Save filenames in an array, so we can see next/prev, etc
        while ($row = $this->db2->get_row())
        {
            $photo_arr[]     = $row['filename'];
            $photoIdLookup[] = $row['id'];
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
                    `name` AS category_name, `views`, `votes`, `rating`,
                    p.`external_id`, e.`thumbnail`, e.`medium`, e.`full`
                FROM `fcms_gallery_photos` AS p
                LEFT JOIN `fcms_category` AS c ON p.`category` = c.`id`
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                WHERE p.`id` = '$pid' ";

        if (!$this->db2->query($sql))
        {
            displaySqlError($sql, mysql_error());
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
            displaySqlError($sql, mysql_error());
        }

        // Get photo comments
        $comments = $this->getPhotoComments($pid);

        $total   = count($photo_arr);
        $current = array_search($pid, $photoIdLookup);

        $prev = $this->getPrevId($photo_arr, $photoIdLookup, $current);
        $next = $this->getNextId($photo_arr, $photoIdLookup, $current);

        $photos_of = '<i>('.sprintf(T_('%d of %d'), $current+1, $total).')</i>';

        $prev_next = '';

        if ($total > 1)
        {
            $prev_next .= '
                <div class="prev_next">
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
        if ($r['filename'] == 'noimage.gif' && $r['external_id'] != null)
        {
            $photo_path_middle = $r['medium'];
            $photo_path_full   = $r['full'];
            $size              = T_('Unknown');
        }
        else
        {
            $photo_path        = $this->getPhotoPath($r['filename'], $r['uid']);
            $photo_path_middle = $photo_path[0];
            $photo_path_full   = $photo_path[1];
            $size              = filesize($photo_path_full);
            $size              = formatSize($size);
        }

        $caption    = cleanOutput($r['caption']);
        $dimensions = GetImageSize($photo_path_full);
        $date_added = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $r['date']);

        // Calculate rating
        if ($r['votes'] <= 0)
        {
            $rating = 0;
            $width  = 0;
        }
        else
        {
            $rating = ($r['rating'] / $r['votes']) * 100;
            $rating = round($rating, 0);
            $width  = $rating / 5;
        }

        // Get Tagged Members
        $sql = "SELECT u.`id`, u.`fname`, u.`lname` 
                FROM `fcms_users` AS u, `fcms_gallery_photos_tags` AS t 
                WHERE t.`photo` = '$pid' 
                AND t.`user` = u.`id`
                ORDER BY u.`lname`";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        $tagged_mem_list = '<li>'.T_('none').'</li>';

        if ($this->db->count_rows() > 0)
        {
            $tagged_mem_list = '';

            while ($t = $this->db->get_row())
            {
                $taggedName = cleanOutput($t['fname']).' '.cleanOutput($t['lname']);

                $tagged_mem_list .= '<li><a href="?uid=0&cid='.$t['id'].'" ';
                $tagged_mem_list .= 'title="'.sprintf(T_('Click to view more photos of %s.'), $taggedName).'">'.$taggedName.'</a></li>';
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
            <div class="breadcrumbs">
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
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=1" title="'.sprintf(T_('%s out of 5 Stars', '1'), '1').'" class="one-star">1</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=2" title="'.sprintf(T_('%s out of 5 Stars', '2'), '2').'" class="two-stars">2</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=3" title="'.sprintf(T_('%s out of 5 Stars', '3'), '3').'" class="three-stars">3</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=4" title="'.sprintf(T_('%s out of 5 Stars', '4'), '4').'" class="four-stars">4</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=5" title="'.sprintf(T_('%s out of 5 Stars', '5'), '5').'" class="five-stars">5</a></li>
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
                        $del_comment .= '<input type="submit" name="delcom" value="'.T_('Delete').'" class="gal_delcombtn" title="'.T_('Delete this Comment').'"/>';
                    }

                    echo '
            <div id="comment'.$row['id'].'" class="comment_block">
                <form action="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$pid.'" method="post">
                    '.$del_comment.'
                    <img class="avatar" alt="avatar" src="'.getCurrentAvatar($row['user'], true).'"/>
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>
                        '.parse($comment, '../').'
                    </p>
                    <input type="hidden" name="uid" value="'.$uid.'"/>
                    <input type="hidden" name="cid" value="'.$cid.'"/>
                    <input type="hidden" name="pid" value="'.$pid.'"/>
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
     * @param int $user     member id
     * @param int $category category id
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

            $sql = "SELECT DISTINCT `id`, `filename` 
                    FROM (
                        SELECT p.`id`, p.`filename` 
                        FROM `fcms_gallery_photo_comment` AS c, `fcms_gallery_photos` AS p 
                        WHERE c.`photo` = p.`id` ORDER BY c.`date` DESC
                    ) as z";

            $breadcrumbs = '<a href="?uid=0&amp;cid=comments">'.T_('Latest Comments').'</a>';
        }
        // Top Rated View
        elseif ($category == 'toprated')
        {
            $urlcid = $category;
            $cid    = $urlcid;

            $sql = "SELECT `id`, `filename` 
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

            $sql = "SELECT `id`, `filename` 
                    FROM `fcms_gallery_photos` 
                    WHERE `views` > 0";
            if ($user > 0)
            {
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

            $sql = "SELECT p.`id`, p.`filename` 
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

            $sql = "SELECT `id`, `filename` 
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

            $sql = "SELECT `id`, `filename` 
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
     * @param string $filename Filename path
     * @param int    $uid      Member id
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
            displaySqlError($sql, mysql_error());
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

    /**
     * showCategories
     *
     * Displays a list of photos in the desired category or view.
     *
     * The following views use this function:
     *     Member Gallery  - uid=0             cid=
     *     Latest Comments - uid=0 or userid   cid='comments'
     *     Top Rated       - uid=0 or userid   cid='toprated'
     *     Most  Viewed    - uid=0 or userid   cid='mostviewed'
     *     Tagged Users    - uid=0             cid=userid
     *     Category        - uid=userid        cid=#
     *     All for User    - uid=userid        cid='all'
     *
     * @param int   $page the page # you want for the specified category
     * @param int   $uid  the user's id or 0 if displaying view for all users
     * @param mixed $cid  optional, category id, 'comments', 'toprated', 'mostviewed', or 'all'
     *
     * @return void
     */
    function showCategories ($page, $uid, $cid = null)
    {
        if (!is_numeric($uid))
        {
            echo '
            <div class="info-alert">
                <h2>'.T_('Uh Oh!').'</h2>
                <p>'.T_('The category you are trying to view doesn\'t exist.').'</p>
            </div>';

            return;
        }

        // Top Rated
        if ($cid == 'toprated')
        {
            $this->displayTopRatedCategory($page, $uid);
        }
        // Most Viewed
        elseif ($cid == 'mostviewed')
        {
            $this->displayMostViewedCategory($page, $uid);
        }
        // Latest Comments
        elseif ($cid == 'comments')
        {
            $this->displayLatestCommentsCategory($page);
        }
        elseif ($uid == 0)
        {
            // Photos of / Tagged
            if (ctype_digit($cid))
            {
                $this->displayPhotosOf($page, $cid);
            }
            // Member Listing
            else
            {
                $this->displayMemberListCategory($page);
            }
        }
        else
        {
            // Category
            if (ctype_digit($cid))
            {
                $this->displayCategory($page, $uid, $cid);
            }
            // Photos upload by user
            elseif ($cid == 'all')
            {
                $this->displayPhotosUploadedBy($page, $uid);
            }
            // Categories by member
            else
            {
                $this->displayMemberCategory($page, $uid);
            }
        }
    }

    /**
     * displayLatestCommentsCategory 
     * 
     * Displays the latest comments.
     * 
     * @param int $page 
     * 
     * @return void
     */
    function displayLatestCommentsCategory ($page)
    {
        $perPage = 18;
        $from    = ($page * $perPage) - $perPage;

        $sql = "SELECT p.`user` AS uid, p.`category` AS cid, c.`date` AS heading, p.`id` AS pid, 
                    p.`filename`, c.`comment`, p.`caption`, c.`user`, p.`external_id`,
                    e.`thumbnail`
                FROM `fcms_gallery_photo_comment` AS c
                LEFT JOIN `fcms_gallery_photos` AS p         ON c.`photo`       = p.`id`
                LEFT JOIN `fcms_category` AS cat             ON p.`category`    = cat.`id`
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                LEFT JOIN `fcms_users` AS u                  ON p.`user`        = u.`id`
                ORDER BY c.`date` DESC";

        if ($page >= 0)
        {
            $sql .= " LIMIT $from, $perPage";
        }
        else
        {
            // Front page Latest Comments
            $sql .= " LIMIT 5";
        }

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            if ($page >= 0)
            {
                echo '
            <div class="info-alert">
                <h2>'.T_('Oops!').'</h2>
                <p>'.T_('The Category you are trying to view is Empty.').'</p>
            </div>';
            }
            return;
        }

        if ($page >= 0)
        {
            echo '
            <p class="breadcrumbs">'.T_('Latest Comments').'</p>
            <ul class="categories">';
        }
        else
        {
            echo '
            <h3>'.T_('Latest Comments').'</h3>
            <a href="?uid=0&amp;cid=comments">('.T_('View All').')</a><br/>
            <ul class="categories">';
        }

        while ($row = $this->db->get_row())
        {
            $date        = fixDate(T_('M. j, Y g:i a'), $this->tzOffset, $row['heading']);
            $displayname = getUserDisplayName($row['user']);
            $filename    = basename($row['filename']);
            $caption     = cleanOutput($row['caption']);
            $comment     = cleanOutput($row['comment']);
            $pid         = (int)$row['pid'];
            $uid         = (int)$row['uid'];

            if ($filename == 'noimage.gif' && $row['external_id'] != null)
            {
                $photoSrc = $row['thumbnail'];
            }
            else
            {
                $photoSrc = '../uploads/photos/member'.$uid.'/tb_'.$filename;
            }

            echo '
                <li class="category">
                    <a href="index.php?uid=0&amp;cid=comments&amp;pid='.$pid.'">
                        <img src="'.$photoSrc.'" alt="'.$caption.'" title="'.$caption.'"/>
                    </a>
                    <span>
                        <strong>'.$date.'</strong>
                        <i><b>'.$displayname.':</b> '.$comment.'</i>
                    </span>
                </li>';
        }

        echo '
            </ul>';

        if ($page >= 0)
        {
            $url = '?uid=0&amp;cid=comments';

            $this->displayCategoryPagination($sql, $page, $perPage, $url);
        }
    }

    /**
     * displayMemberListCategory 
     * 
     * Displays the listing of Members who have created categories with photos in them.
     * 
     * @param int $page 
     * 
     * @return void
     */
    function displayMemberListCategory ($page)
    {
        $perPage = 18;
        $from    = ($page * $perPage) - $perPage;

        $sql = "SELECT u.`id` AS uid, f.`filename`, COUNT(p.`id`) as c,
                    e.`thumbnail`, p.`external_id`
                FROM `fcms_category` AS cat
                LEFT JOIN `fcms_gallery_photos` AS p         ON p.`category`    = cat.`id`
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                LEFT JOIN `fcms_users` AS u                  ON p.`user`        = u.`id`,
                (
                    SELECT * 
                    FROM `fcms_gallery_photos` 
                    ORDER BY `date` DESC
                ) AS f
                WHERE f.`id` = p.`id` 
                GROUP BY p.`user`
                ORDER BY cat.`date` DESC
                LIMIT $from, $perPage";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '
            <div class="info-alert">
                <h2>'.T_('Oops!').'</h2>
                <p>'.T_('The Category you are trying to view is Empty.').'</p>
            </div>';
        }

        echo '
            <p class="breadcrumbs">'.T_('Members').'</p>
            <ul class="categories">';

        while ($row = $this->db->get_row())
        {
            $displayname = getUserDisplayName($row['uid']);
            $displayname = cleanOutput($displayname);
            $id          = $row['uid'];
            $filename    = basename($row['filename']);
            $count       = cleanOutput($row['c']);
            $alt         = 'alt="'.sprintf(T_('View Categories for %s'), $displayname).'"';
            $title       = 'title="'.sprintf(T_('View Categories for %s'), $displayname).'"';
            $url         = '?uid='.$row['uid'];

            if ($filename == 'noimage.gif' && $row['external_id'] != null)
            {
                $photoSrc = $row['thumbnail'];
            }
            else
            {
                $photoSrc = '../uploads/photos/member'.$id.'/tb_'.$filename;
            }

            echo '
                <li class="category">
                    <a href="index.php'.$url.'">
                        <img src="'.$photoSrc.'" '.$alt.' '.$title.'/>
                    </a>
                    <span>
                        <strong>'.$displayname.'</strong>
                        <i>'.T_('Photos').' ('.$count.')</i>
                    </span>
                </li>';
        }

        echo '
            </ul>';

        $url = '?uid=0';

        $this->displayCategoryPagination($sql, $page, $perPage, $url);
    }

    /**
     * displayMemberCategory 
     * 
     * Displays all categories created by the given member.
     * 
     * @param int $page 
     * @param int $uid 
     * 
     * @return void
     */
    function displayMemberCategory ($page, $uid)
    {
        $perPage = 18;
        $from    = ($page * $perPage) - $perPage;

        $sql = "SELECT u.`id` AS uid, cat.`name` AS category, cat.`id` AS cid, f.`filename`, COUNT(p.`id`) AS c,
                    e.`thumbnail`, p.`external_id`
                FROM `fcms_category` AS cat
                LEFT JOIN `fcms_gallery_photos` AS p         ON p.`category`    = cat.`id`
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                LEFT JOIN `fcms_users` AS u                  ON p.`user`        = u.`id`,
                (
                    SELECT *
                    FROM `fcms_gallery_photos`
                    ORDER BY `date` DESC
                ) AS f
                WHERE f.`id` = p.`id`
                AND p.`user` = '$uid'
                GROUP BY cat.`id` DESC
                LIMIT $from, $perPage";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '
            <div class="info-alert">
                <h2>'.T_('Oops!').'</h2>
                <p>'.T_('The Category you are trying to view is Empty.').'</p>
            </div>';
        }

        echo '
            <p class="breadcrumbs">
                <a href="?uid=0">'.T_('Members').'</a> &gt; '.getUserDisplayName($uid).'
            </p>
            <ul class="categories">';

        while ($row = $this->db->get_row())
        {
            $category = cleanOutput($row['category']);
            $cid      = (int)$row['cid'];
            $filename = basename($row['filename']);
            $alt      = 'alt="'.sprintf(T_('View Photos in %s'), $category).'"';
            $title    = 'title="'.sprintf(T_('View Photos in %s'), $category).'"';
            $count    = cleanOutput($row['c']);

            if ($filename == 'noimage.gif' && $row['external_id'] != null)
            {
                $photoSrc = $row['thumbnail'];
            }
            else
            {
                $photoSrc = '../uploads/photos/member'.$uid.'/tb_'.$filename;
            }

            echo '
                <li class="category">
                    <a href="index.php?uid='.$uid.'&amp;cid='.$cid.'">
                        <img src="'.$photoSrc.'" '.$alt.' '.$title.'/>
                    </a>
                    <span>
                        <strong>'.$category.'</strong>
                        <i>'.T_('Photos').' ('.$count.')</i>
                    </span>
                </li>';
        }

        echo '
            </ul>';

        $url = '?uid='.$uid;

        $this->displayCategoryPagination($sql, $page, $perPage, $url);
    }

    /**
     * displayCategory 
     * 
     * @param int $page 
     * @param int $uid 
     * @param int $cid 
     * 
     * @return void
     */
    function displayCategory ($page, $uid, $cid)
    {
        $perPage = 40;
        $from    = ($page * $perPage) - $perPage;

        $sql = "SELECT u.`id` AS uid, `category` AS cid, p.`id` AS pid, `caption`, c.`name` AS category, 
                    `filename`, c.`description`, p.`external_id`, e.`thumbnail`
                FROM `fcms_category` AS c
                LEFT JOIN `fcms_gallery_photos` AS p         ON p.`category`    = c.`id`
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                LEFT JOIN `fcms_users` AS u                  ON p.`user`        = u.`id`
                WHERE p.`category` = '$cid'
                LIMIT $from, $perPage";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '
            <div class="info-alert">
                <h2>'.T_('Oops!').'</h2>
                <p>'.T_('The Category you are trying to view is Empty.').'</p>
            </div>';
        }

        $photos   = '';
        $admin    = '';
        $descInfo = '';
        $access   = checkAccess($this->currentUserId);

        while ($row = $this->db->get_row())
        {
            $category    = cleanOutput($row['category']);
            $filename    = basename($row['filename']);
            $description = cleanOutput($row['description']);
            $caption     = cleanOutput($row['caption']);
            $pid         = (int)$row['pid'];
            $alt         = 'alt="'.$caption.'"';
            $title       = 'title="'.$caption.'"';

            if ($filename == 'noimage.gif' && $row['external_id'] != null)
            {
                $photoSrc = $row['thumbnail'];
            }
            else
            {
                $photoSrc = '../uploads/photos/member'.$uid.'/tb_'.$filename;
            }

            $photos .= '
                    <li class="photo">
                        <a href="index.php?uid='.$uid.'&amp;cid='.$cid.'&amp;pid='.$pid.'">
                            <img class="photo" src="'.$photoSrc.'" '.$alt.' '.$title.'/>
                        </a>
                    </li>';
        }

        // Administrate link
        if ($access < 2)
        {
            $admin = '<li class="administrate"><a href="../admin/gallery.php?edit='.$cid.'">'.T_('Administrate').'</a></li>';
        }

        // Description
        if (empty($description))
        {
            if ($uid == $this->currentUserId)
            {
                $descInfo = '<a href="?description='.$cid.'&amp;user='.$uid.'">'.T_('Add Description').'</a>';
            }
            else
            {
                $descInfo = '<i>'.T_('No description').'</i>';
            }
        }
        else
        {
            $descInfo .= $description;

            if ($uid == $this->currentUserId)
            {
                $descInfo .= '<br/><a href="?description='.$cid.'&amp;user='.$uid.'">'.T_('Edit Description').'</a>';
            }
        }

        // Members in category
        $membersInCategory = $this->getMembersInCategory($uid, $cid);

        $url = '?uid='.$uid.'&amp;cid='.$cid;

        echo '
            <p class="breadcrumbs">
                <a href="?uid=0">'.T_('Members').'</a> &gt; 
                <a href="?uid='.$uid.'">'.getUserDisplayName($uid).'</a> &gt; 
                '.$category.'
            </p>
            <div id="maincolumn">
                <ul id="photos">'.$photos.'
                </ul>';

        // Display Comments
        if (   checkAccess($this->currentUserId) <= 8
            && checkAccess($this->currentUserId) != 7
            && checkAccess($this->currentUserId) != 4
        )
        {
            $comments = $this->getCategoryComments($cid);

            echo '
                <h3 id="comments">'.T_('Comments').'</h3>';

            if (count($comments) > 0)
            { 
                foreach ($comments as $row)
                {
                    // Setup some vars for each comment block
                    $del_comment = '';
                    $date        = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $row['created']);
                    $displayname = $row['fname'].' '.$row['lname'];
                    $comment     = $row['comment'];
                    $avatarPath  = getAvatarPath($row['avatar'], $row['gravatar'], '../');

                    if ($this->currentUserId == $row['created_id'] || checkAccess($this->currentUserId) < 2)
                    {
                        $del_comment .= '<input type="submit" name="delcom" value="'.T_('Delete').'" class="gal_delcombtn" title="'.T_('Delete this Comment').'"/>';
                    }

                    echo '
                <div id="comment'.$row['id'].'" class="comment_block">
                    <form action="?uid='.$uid.'&amp;cid='.$cid.'" method="post">
                        '.$del_comment.'
                        <img class="avatar" alt="avatar" src="'.$avatarPath.'"/>
                        <b>'.$displayname.'</b>
                        <span>'.$date.'</span>
                        <p>
                            '.parse($comment, '../').'
                        </p>
                        <input type="hidden" name="uid" value="'.$uid.'"/>
                        <input type="hidden" name="cid" value="'.$cid.'"/>
                        <input type="hidden" name="id" value="'.$row['id'].'">
                    </form>
                </div>';
                }
            }

            echo '
                <div class="add_comment_block">
                    <form action="?uid='.$uid.'&amp;cid='.$cid.'" method="post">
                        '.T_('Add Comment').'<br/>
                        <textarea class="frm_textarea" name="comment" id="comment" rows="3" cols="63"></textarea><br/>
                        <input type="submit" name="addcatcom" id="addcatcom" value="'.T_('Add Comment').'" title="'.T_('Add Comment').'"/>
                    </form>
                </div>';
        }

        $this->displayCategoryPagination($sql, $page, $perPage, $url);

        echo '
            </div>
            <div id="leftcolumn">
                <ul id="category-actions">
                    <li class="slideshow"><a class="new_window" href="slideshow.php?category='.$cid.'">'.T_('View Slideshow').'</a></li>
                    '.$admin.'
                </ul>
                <p><b>'.T_('Description').'</b></p>
                <p>'.$descInfo.'</p>
                <p><b>'.T_('Members In Category').'</b></p>
                <p>'.$membersInCategory.'</p>
            </div>';
    }

    /**
     * displayTopRatedCategory 
     * 
     * @param int $page 
     * @param int $uid 
     * 
     * @return void
     */
    function displayTopRatedCategory ($page, $uid)
    {
        $perPage = 18;
        $from    = ($page * $perPage) - $perPage;
        $where   = '';

        if ($uid > 0)
        {
            $where = " AND `user` = '$uid' ";
        }

        $sql = "SELECT p.`user` AS uid, p.`filename`, p.`category`, p.`caption`, 
                    p.`id` AS pid, p.`rating`/p.`votes` AS 'r', p.`external_id`,
                    e.`thumbnail`
                FROM `fcms_gallery_photos` AS p
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                WHERE `votes` > 0 
                $where
                ORDER BY r DESC
                LIMIT $from, $perPage";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '
            <div class="info-alert">
                <h2>'.T_('Oops!').'</h2>
                <p>'.T_('The Category you are trying to view is Empty.').'</p>
            </div>';
        }

        $topRatedUser = '';
        if ($uid > 0)
        {
            $topRatedUser = ' ('.getUserDisplayName($uid).')';
        }

        echo '
            <p class="breadcrumbs">'.T_('Top Rated').$topRatedUser.'</p>
            <ul class="categories">';

        while ($row = $this->db->get_row())
        {
            $filename = basename($row['filename']);
            $user     = (int)$row['uid'];
            $cid      = (int)$row['category'];
            $pid      = (int)$row['pid'];
            $url      = 'index.php?uid='.$uid.'&amp;cid=toprated'.$cid.'&amp;pid='.$pid;
            $width    = ($row['r'] / 5) * 100;
            $caption  = cleanOutput($row['caption']);

            if ($filename == 'noimage.gif' && $row['external_id'] != null)
            {
                $photoSrc = $row['thumbnail'];
            }
            else
            {
                $photoSrc = '../uploads/photos/member'.$user.'/tb_'.$filename;
            }

            echo '
                <li class="category">
                    <a href="'.$url.'">
                        <img src="'.$photoSrc.'" alt="'.$caption.'" title="'.$caption.'"/>
                    </a>
                    <span>
                        <i>
                            <ul class="star-rating small-star">
                                <li class="current-rating" style="width:'.$width.'%">'.sprintf(T_('Currently %d/5 Stars.'), $row['r']).'</li>
                                <li><a href="'.$url.'&amp;vote=1" title="'.T_('1 out of 5 Stars').'" class="one-star">1</a></li>
                                <li><a href="'.$url.'&amp;vote=2" title="'.T_('2 out of 5 Stars').'" class="two-stars">2</a></li>
                                <li><a href="'.$url.'&amp;vote=3" title="'.T_('3 out of 5 Stars').'" class="three-stars">3</a></li>
                                <li><a href="'.$url.'&amp;vote=4" title="'.T_('4 out of 5 Stars').'" class="four-stars">4</a></li>
                                <li><a href="'.$url.'&amp;vote=5" title="'.T_('5 out of 5 Stars').'" class="five-stars">5</a></li>
                            </ul>
                        </i>
                    </span>
                </li>';
        }

        echo '
            </ul>';

        $url = '?uid='.$uid.'&amp;cid=toprated';

        $this->displayCategoryPagination($sql, $page, $perPage, $url);
    }

    /**
     * displayMostViewedCategory 
     * 
     * @param int $page 
     * @param int $uid 
     * 
     * @return void
     */
    function displayMostViewedCategory ($page, $uid)
    {
        $perPage = 18;
        $from    = ($page * $perPage) - $perPage;
        $where   = '';

        if ($uid > 0)
        {
            $where = " AND `user` = '$uid' ";
        }

        $sql = "SELECT p.`user` AS uid, p.`filename`, p.`caption`, p.`id` AS pid, 
                    p.`views`, p.`external_id`, e.`thumbnail`
                FROM `fcms_gallery_photos` AS p
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                WHERE `views` > 0 
                $where
                ORDER BY VIEWS DESC
                LIMIT $from, $perPage";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '
            <div class="info-alert">
                <h2>'.T_('Oops!').'</h2>
                <p>'.T_('The Category you are trying to view is Empty.').'</p>
            </div>';
        }

        $mostViewedUser = '';
        if ($uid > 0)
        {
            $mostViewedUser = ' ('.getUserDisplayName($uid).')';
        }

        echo '
            <p class="breadcrumbs">'.T_('Most Viewed').$mostViewedUser.'</p>
            <ul class="categories">';

        while ($row = $this->db->get_row())
        {
            $filename = basename($row['filename']);
            $user     = (int)$row['uid'];
            $pid      = (int)$row['pid'];
            $caption  = cleanOutput($row['caption']);
            $views    = (int)$row['views'];

            if ($filename == 'noimage.gif' && $row['external_id'] != null)
            {
                $photoSrc = $row['thumbnail'];
            }
            else
            {
                $photoSrc = '../uploads/photos/member'.$user.'/tb_'.$filename;
            }

            echo '
                <li class="category">
                    <a href="?uid='.$user.'&amp;cid=mostviewed&amp;pid='.$pid.'">
                        <img src="'.$photoSrc.'" alt="'.$caption.'" title="'.$caption.'"/>
                    </a>
                    <span>
                        <i><b>'.T_('Views').': </b>'.$views.'</i>
                    </span>
                </li>';
        }

        echo '
            </ul>';

        $url = '?uid='.$uid.'&amp;cid=mostviewed';

        $this->displayCategoryPagination($sql, $page, $perPage, $url);
    }

    /**
     * displayPhotosOf 
     * 
     * @param int $page 
     * @param int $userId
     * 
     * @return void
     */
    function displayPhotosOf ($page, $userId)
    {
        $perPage = 30;
        $from    = ($page * $perPage) - $perPage;

        $sql = "SELECT t.`user`, p.`id` AS pid, p.`filename`, p.`caption`, p.`user` AS uid,
                    p.`external_id`, e.`thumbnail`
                FROM `fcms_gallery_photos` AS p
                LEFT JOIN `fcms_gallery_photos_tags` AS t    ON t.`photo`       = p.`id`
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                WHERE t.`user` = '$userId'
                LIMIT $from, $perPage";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '
            <div class="info-alert">
                <h2>'.T_('Oops!').'</h2>
                <p>'.T_('The Category you are trying to view is Empty.').'</p>
            </div>';
        }

        $userName = getUserDisplayName($userId);

        echo '
            <p class="breadcrumbs">'.sprintf(T_('Photos of %s'), $userName).'</p>
            <ul class="photos">';

        while ($row = $this->db->get_row())
        {
            $filename = basename($row['filename']);
            $uid      = (int)$row['uid'];
            $pid      = (int)$row['pid'];
            $urlPage  = '?uid=0&amp;cid='.$userId;
            $caption  = cleanOutput($row['caption']);

            if ($filename == 'noimage.gif' && $row['external_id'] != null)
            {
                $photoSrc = $row['thumbnail'];
            }
            else
            {
                $photoSrc = '../uploads/photos/member'.$uid.'/tb_'.$filename;
            }

            echo '
                <li class="photo">
                    <a href="index.php?uid=0&amp;cid=tagged'.$userId.'&amp;pid='.$pid.'">
                        <img class="photo" src="'.$photoSrc.'" alt="'.$caption.'" title="'.$caption.'"/>
                    </a>
                </li>';
        }

        echo '
            </ul>';

        $url = '?uid=0&amp;cid='.$userId;

        $this->displayCategoryPagination($sql, $page, $perPage, $url);
    }

    /**
     * displayPhotosUploadedBy 
     * 
     * @param int $page 
     * @param int $userId 
     * 
     * @return void
     */
    function displayPhotosUploadedBy ($page, $userId)
    {
        $perPage = 30;
        $from    = ($page * $perPage) - $perPage;

        $sql = "SELECT u.`id` AS uid, p.`category` AS cid, p.`id` AS pid, p.`caption`, 
                    c.`name` AS category, p.`filename`, p.`external_id`, e.`thumbnail`
                FROM `fcms_category` AS c
                LEFT JOIN `fcms_gallery_photos` AS p         ON p.`category`    = c.`id`
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                LEFT JOIN `fcms_users` AS u                  ON p.`user`        = u.`id`
                WHERE p.`user` = '$userId' 
                ORDER BY p.`id`
                LIMIT $from, $perPage";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() <= 0)
        {
            echo '
            <div class="info-alert">
                <h2>'.T_('Oops!').'</h2>
                <p>'.T_('The Category you are trying to view is Empty.').'</p>
            </div>';

            return;
        }

        $userName = getUserDisplayName($userId);

        echo '
            <p class="breadcrumbs">'.sprintf(T_('Photos uploaded by %s'), $userName).'</p>
            <ul class="photos">';

        while ($row = $this->db->get_row())
        {
            $filename = basename($row['filename']);
            $pid      = (int)$row['pid'];
            $urlPage  = '?uid='.$userId.'&amp;cid=all';
            $caption  = cleanOutput($row['caption']);

            if ($filename == 'noimage.gif' && $row['external_id'] != null)
            {
                $photoSrc = $row['thumbnail'];
            }
            else
            {
                $photoSrc = '../uploads/photos/member'.$userId.'/tb_'.$filename;
            }

            echo '
                <li class="photo">
                    <a href="index.php?uid='.$userId.'&amp;cid=all&amp;pid='.$pid.'">
                        <img class="photo" src="'.$photoSrc.'" alt="'.$caption.'" title="'.$caption.'"/>
                    </a>
                </li>';
        }

        echo '
            </ul>';

        $url = '?uid='.$userId.'&amp;cid=all';

        $this->displayCategoryPagination($sql, $page, $perPage, $url);
    }

    /**
     * displayCategoryPagination 
     * 
     * @param string $sql 
     * @param int    $page 
     * @param int    $perPage 
     * @param string $url 
     * 
     * @return void
     */
    function displayCategoryPagination ($sql, $page, $perPage, $url)
    {
        // Remove the LIMIT from the $sql statement 
        $findLimit = strpos($sql, 'LIMIT');
        if ($findLimit !== false)
        {
            $sql = substr($sql, 0, strpos($sql, 'LIMIT'));
        }

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        $count = $this->db->count_rows();
        $total = ceil($count / $perPage); 

        displayPages("index.php$url", $page, $total);
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
        $categories = $this->getCategoryInputs();

        // Setup the photo tagging options (autocomplete or checkbox)
        $advanced_tagging       = usingAdvancedTagging($this->currentUserId);
        $members                = array();
        $autocomplete_selected  = '';
        $tagging_options        = '';
        $users_list             = '';
        $users_lkup             = '';

        // Setup the list of active members for possible tags
        $sql = "SELECT `id` 
                FROM `fcms_users` 
                WHERE `activated` > 0";
        if (!$this->db2->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }
        while ($r = $this->db2->get_row())
        {
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
                            <div class="multi-checkbox">
                                '.$tag_checkboxes.'
                            </div>';
        }

        // Display the form
        echo '
            <script type="text/javascript" src="../ui/js/scriptaculous.js"></script>
            <form id="autocomplete_form" enctype="multipart/form-data" action="?action=upload" method="post" class="photo-uploader">
                <div class="header">
                    <label>'.T_('Category').'</label>
                    '.$categories.'
                </div>
                <ul class="upload-types">
                    '.$this->getUploadTypesNavigation('upload').'
                </ul>
                <div class="upload-area">
                    <div class="basic">
                        <p style="float:right">
                            <a class="help" href="../help.php?topic=photo#gallery-howworks">'.T_('Help').'</a>
                        </p>
                        <p>
                            <label><b>'.T_('Photo').'</b></label><br/>
                            <input name="photo_filename" type="file" size="50"/>
                        </p>
                        <p>
                            <label><b>'.T_('Caption').'</b></label><br/>
                            <input class="frm_text" type="text" name="photo_caption" size="50"/>
                        </p>
                        <div id="tag-options">
                            <label><b>'.T_('Who is in this Photo?').'</b></label><br/>
                            '.$tagging_options.'
                        </div>
                        <p>
                            <label><b>'.T_('Rotate').'</b></label><br/>
                            <input type="radio" id="left" name="rotate" value="left"/>
                            <label for="left" class="radio_label">'.T_('Left').'</label>&nbsp;&nbsp; 
                            <input type="radio" id="right" name="rotate" value="right"/>
                            <label for="right" class="radio_label">'.T_('Right').'</label>
                        </p>
                    </div><!--/basic-->
                </div>
                <div class="footer">
                    <input class="sub1" type="submit" id="addphoto" name="addphoto" value="'.T_('Submit').'"/>
                </div>
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
        $category_options = $this->getCategoryInputs();

        // Setup some applet params
        $scaledInstanceNames      = '<param name="uc_scaledInstanceNames" value="small,medium"/>';
        $scaledInstanceDimensions = '<param name="uc_scaledInstanceDimensions" value="150x150xcrop,600x600xfit"/>';
        $fullSizedPhotos          = '';

        if ($this->usingFullSizePhotos())
        {
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
                applet, .photo-uploader {display: none;}
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

            <form method="post" name="uploadForm" class="photo-uploader">
                <div class="header">
                    <label>'.T_('Category').'</label>
                    '.$category_options.'
                </div>
                <ul class="upload-types">
                    '.$this->getUploadTypesNavigation('upload').'
                </ul>
                <div class="upload-area">
                    <applet name="jumpLoaderApplet"
                        code="jmaster.jumploader.app.JumpLoaderApplet.class"
                        archive="../inc/thirdparty/jumploader_z.jar"
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
                </div>
                <div class="footer">
                    <input class="sub1" type="button" value="'.T_('Upload').'" id="start-upload" name="start-upload"/>
                </div>
            </form>
            <script language="javascript">
            Event.observe("start-upload","click",function(){

                if ($F("new-category").empty() && $F("existing-categories") <= 0) {
                    alert("'.T_('Please specify a category first.').'");
                    return;
                }

                var uploader = document.jumpLoaderApplet.getUploader();
                var attrSet  = uploader.getAttributeSet();

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
            </script>';
    }

    /**
     * displayInstagramUploadForm 
     *
     * Displays the form for uploading photos from Instagram.
     * 
     * @return void
     */
    function displayInstagramUploadForm ()
    {
        if (isset($_SESSION['error']))
        {
            unset($_SESSION['error']);

            echo '
            <p class="error-alert">
                '.T_('You must choose at least one photo, or choose to automatically import all.').'
            </p>';
        }

        require_once INC.'socialmedia.php';
        require_once THIRDPARTY.'Instagram.php';

        // Get auto upload setting and access token
        $sql = "SELECT `instagram_access_token`, `instagram_auto_upload`
                FROM `fcms_user_settings`
                WHERE `user` = '".$this->currentUserId."'
                LIMIT 1";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() < 0)
        {
            echo '
            <p class="error-alert">
                '.T_('Could not get user data.').'
            </p>';
            return;
        }

        $r = $this->db->get_row();

        $instagramInfo = '';

        if (empty($r['instagram_access_token']))
        {
            $instagramInfo = '
            <div class="info-alert">
                <h2>'.T_('Not connected to Instagram.').'</h2>
                <p>'.T_('You must connect your Family Connections account to Instagram before you can begin importing photos from Instagram.').'</p>
                <p><a href="../settings.php?view=instagram">'.T_('Connect to Instagram').'</a></p>
            </div>';
        }
        else
        {
            $config     = getInstagramConfigData();
            $token      = $r['instagram_access_token'];
            $automatic  = $r['instagram_auto_upload'] == 1 ? true : false;
            $instagram  = new Instagram($config['instagram_client_id'], $config['instagram_client_secret'], $token);

            try
            {
                if (isset($_GET['show']) && $_GET['show'] == 'more')
                {
                    $feed = $instagram->get('users/self/media/recent/');
                }
                else
                {
                    $feed = $instagram->get('users/self/media/recent/', array('count' => 8));
                }
            }
            catch (InstagramApiError $e)
            {
                echo '
                <p class="error-alert">
                    '.T_('Could not get Instagram data.').'
                </p>';

                logError(__FILE__.' ['.__LINE__.'] - Could not get user instagram data. - '.$e->getMessage());
                return;
            }

            $photos          = '';
            $automaticSelect = '';

            if (!$automatic)
            {
                $photos .= '<h2>'.T_('Manual').'</h2>';
                $photos .= '<p>'.T_('Choose photo to add.').'</p>';
                $photos .= '<ul>';

                $i = 1;
                foreach ($feed->data as $photo)
                {
                    $sourceId  = $photo->id;
                    $thumbnail = $photo->images->thumbnail->url;
                    $medium    = $photo->images->low_resolution->url;
                    $full      = $photo->images->standard_resolution->url;
                    $caption   = sprintf(T_('Imported from Instagram.  Filter: %s.'), $photo->filter);
                    $value     = "$sourceId|$thumbnail|$medium|$full|$caption";

                    $photos .= '<li>';
                    $photos .= '<label for="instagram'.$i.'">';
                    $photos .= '<img src="'.$thumbnail.'" alt="'.$caption.'"/><br/>';
                    $photos .= '<input type="checkbox" id="instagram'.$i.'" name="photos[]" value="'.$value.'"/>';
                    $photos .= '</label>';
                    $photos .= '</li>';

                    $i++;
                }

                // They probably have more
                if ($i == 8)
                {
                    $photos .= '<li><a href="index.php?action=upload&amp;type=instagram&amp;show=more">'.T_('See more').'</a></li>';
                }

                $photos .= '</ul>';
            }

            $instagramInfo = $photos.'
                        <h2>'.T_('Automatic').'</h2>
                        <label>
                            <input type="checkbox" id="automatic" name="automatic" value="1" '.($automatic ? 'checked="checked"' : '').'/>
                            '.T_('Have all photos automatically imported.').'
                        </label>';
        }

        echo '
            <form method="post" class="photo-uploader" action="index.php?action=upload&amp;type=instagram">
                <div class="header">
                    <label>'.T_('Category').'</label>
                    '.$this->getCategoryInputs().'
                </div>
                <ul class="upload-types">
                    <li><a href="?action=upload">'.T_('Upload').'</a></li>
                    <li class="current"><a href="?action=upload&amp;type=instagram">Instagram</a></li>
                </ul>
                <div class="upload-area">
                    <div class="instagram">
                        '.$instagramInfo.'
                    </div>
                </div>
                <div class="footer">
                    <input class="sub1" type="submit" value="'.T_('Upload').'" id="instagram" name="instagram"/>
                </div>
            </form>';
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
        $photo = (int)$photo;

        $sql = "SELECT p.`user`, p.`filename`, p.`caption`, c.`name`, c.`id` AS category_id, p.`external_id`, e.`thumbnail`
                FROM `fcms_gallery_photos` AS p
                LEFT JOIN `fcms_category` AS c               ON p.`category`    = c.`id`
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                WHERE p.`id` = '$photo'";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        if ($this->db->count_rows() > 0)
        {
            $row        = $this->db->get_row();
            $photo_user = (int)$row['user'];
            $filename   = basename($row['filename']);
            $caption    = cleanOutput($row['caption']);
            $cat_name   = cleanOutput($row['name']);
            $cat_id     = cleanOutput($row['category_id']);

            if ($filename == 'noimage.gif' && $row['external_id'] != null)
            {
                $photoSrc = $row['thumbnail'];
            }
            else
            {
                $photoSrc = '../uploads/photos/member'.$photo_user.'/tb_'.$filename;
            }

            $categories  = $this->getUserCategories($photo_user);
            $cat_options = buildHtmlSelectOptions($categories, $cat_id);

            $advanced_tagging = usingAdvancedTagging($photo_user);

            $prev_tagged = array();
            $members     = array();

            $autocomplete_selected = '';
            $prev_tagged_options   = '';

            // Setup the photo tagging options (autocomplete or checkbox)
            $tagging_options = '';
            $users_list      = '';
            $users_lkup      = '';

            // Setup the list of users already tagged
            $sql = "SELECT `id`, `user` 
                    FROM `fcms_gallery_photos_tags` 
                    WHERE `photo` = '$photo'";
            if (!$this->db2->query($sql))
            {
                displaySqlError($sql, mysql_error());
                return;
            }
            while ($r = $this->db2->get_row())
            {
                $prev_tagged[$r['user']] = 1;
            }

            // Setup the list of active members for possible tags
            $sql = "SELECT `id` 
                    FROM `fcms_users` 
                    WHERE `activated` > 0";
            if (!$this->db2->query($sql))
            {
                displaySqlError($sql, mysql_error());
                return;
            }
            while ($r = $this->db2->get_row())
            {
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

                    $tag_checkboxes .= '<label for="'.$key.'">';
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
                    <script type="text/javascript" src="../ui/js/scriptaculous.js"></script>
                    <legend><span>'.T_('Edit Photo').'</span></legend>
                    <img class="thumbnail" src="'.$photoSrc.'"/>
                    <form id="autocomplete_form" enctype="multipart/form-data" action="index.php?'.$url.'" method="post">
                        <div class="field-row">
                            <div class="field-label"><label><b>'.T_('Change Category').'</b></label></div>
                            <div class="field-widget">
                                <select class="frm_sel" name="category" tabindex="1">
                                    '.$cat_options.'
                                </select>
                            </div>
                        </div>
                        <div class="field-row">
                            <div class="field-label"><label><b>'.T_('Caption').'</b></label></div>
                            <div class="field-widget">
                                <input class="frm_text" type="text" name="photo_caption" size="50" tabindex="2" value="'.$caption.'"/>
                            </div>
                        </div>
                        <div class="field-row">
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
        if (isset($_SESSION['photos']['error']))
        {
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
        if (!$this->db2->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }
        while ($r = $this->db2->get_row())
        {
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
                <script type="text/javascript" src="../ui/js/scriptaculous.js"></script>
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
        if (!file_exists(ROOT.'uploads/photos/member'.$this->currentUserId))
        {
            mkdir(ROOT.'uploads/photos/member'.$this->currentUserId);
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
            displaySqlError($sql, mysql_error());
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
                    displaySqlError($sql, mysql_error());
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
     * displayCategoryForm 
     *
     * Displays the form for editing/deleting categories 
     *
     * @return void
     */
    function displayCategoryForm ()
    {
        $cat_list = '';
        
        // Setup the list of categories for edit/delete
        $sql = "SELECT * FROM `fcms_category` 
                WHERE `user` = '".$this->currentUserId."'
                AND `type` = 'gallery'";
        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        $categories = '';

        if ($this->db->count_rows() > 0)
        {
            while ($row = $this->db->get_row())
            {
                $id    = cleanOutput($row['id']);
                $name  = cleanOutput($row['name']);
                $count = $this->getCategoryPhotoCount($row['id']);

                $categories .= '
                    <tr>
                        <td>
                            <form class="frm_line" action="index.php?action=category" method="post">
                                <input type="hidden" name="cid" id="cid" value="'.$id.'"/>
                                <input class="frm_text" type="text" name="cat_name" id="cat_name" size="60" value="'.$name.'"/>
                                <input type="submit" name="editcat" class="editbtn" value="'.T_('Edit').'" title="'.T_('Edit Category').'"/>
                            </form>
                        </td>
                        <td>'.$count.'</td>
                        <td>
                            <a href="?delcat='.$id.'" class="delcategory" title="'.T_('Delete Category').'">'.T_('Delete').'</a>
                        </td>
                    </tr>';
            }
        }
        else
        {
            $categories .= '<tr><td colspan="3"><i>'.T_('No Categories created yet.').'</i></td></tr>';
        }
        
        // Display the form
        echo '
            <h2>'.T_('Manage Categories').'</h2>
            <table id="manage-categories" class="sortable">
                <thead>
                    <tr>
                        <th>'.T_('Category').'</th>
                        <th>'.T_('Photos').'</th>
                        <th>'.T_('Delete').'</th>
                    </tr>
                </thead>
                <tbody>'.$categories.'
                </tbody>
            </table>';
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
        if ($userid == 0)
        {
            $userid = $this->currentUserId;
        }

        $sql = "SELECT `id`, `name` FROM `fcms_category` 
                WHERE `user` = '".(int)$userid."'
                AND `type` = 'gallery'
                ORDER BY `id` DESC";
        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return;
        }

        $categories = array();

        while($row = $this->db->get_row())
        {
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
     * getCategoryPhotoCount 
     * 
     * @param int $id 
     * 
     * @return  void
     */
    function getCategoryPhotoCount ($id)
    {
        $id = (int)$id;

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
        $id = (int)$id;

        $sql = "SELECT *
                FROM `fcms_gallery_photos`
                WHERE `id` = '$id'
                LIMIT 1";
        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
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
            displaySqlError($sql, mysql_error());
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
                if (!mysql_query($sql))
                {
                    displaySqlError($sql, mysql_error());
                    // continue
                }
                
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
            displaySqlError($sql, mysql_error());
            return;
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
        $sql = "SELECT u.`id` AS uid, p.`category` AS cid, p.`id` AS pid, p.`caption`, 
                    c.`name` AS category, p.`filename`, p.`external_id`, 
                    e.`thumbnail`
                FROM `fcms_category` AS c
                LEFT JOIN `fcms_gallery_photos` AS p         ON p.`category`    = c.`id`
                LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                LEFT JOIN `fcms_users` AS u                  ON p.`user`        = u.`id`
                WHERE p.`user` = '$user' 
                AND `category` = '$category'
                AND p.`user` = u.`id`
                AND `category` = c.`id`
                ORDER BY p.`id`";
        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
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
                <script type="text/javascript" src="../ui/js/scriptaculous.js"></script>
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
                displaySqlError($sql, mysql_error());
                return;
            }

            while ($r = $this->db2->get_row())
            {
                $prev_tagged[$r['user']] = 1;
            }

            // handle previously tagged members
            if (count($prev_tagged) > 0)
            {
                foreach ($prev_tagged as $id => $name)
                {
                    $prev_tagged_options .= '<input type="hidden" name="prev_tagged_users['.$row['pid'].'][]" value="'.$id.'"/>';
                    if ($advanced_tagging)
                    {
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

            // Instagram photo or regular?
            if ($row['filename'] == 'noimage.gif' && $row['external_id'] != null)
            {
                $photoSrc = $row['thumbnail'];
            }
            else
            {
                $photoSrc = '../uploads/photos/member'.$user.'/tb_'.basename($row['filename']);
            }

            echo '
                        <img style="float:right" src="'.$photoSrc.'"/>
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
                FROM `fcms_gallery_photo_comment` AS c, `fcms_users` AS u 
                WHERE `photo` = '$pid' 
                AND c.`user` = u.`id` 
                ORDER BY `date`";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
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

    /**
     * getCategoryComments 
     * 
     * @param int $cid 
     * 
     * @return array
     */
    function getCategoryComments ($cid)
    {
        $comments = array();

        $sql = "SELECT c.`id`, c.`comment`, c.`created`, u.`fname`, u.`lname`, u.`username`, c.`created_id`, u.`avatar`, u.`gravatar`
                FROM `fcms_gallery_category_comment` AS c
                LEFT JOIN `fcms_users` AS u ON c.`created_id` = u.`id`
                WHERE `category_id` = '$cid' 
                ORDER BY `created`";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
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

    /**
     * getMembersInCategory 
     * 
     * @param int $cid 
     * 
     * @return string
     */
    function getMembersInCategory ($uid, $cid)
    {
        $retVal = '';

        $sql = "SELECT u.`id`, u.`fname`, u.`lname`, u.`avatar`, u.`gravatar`
                FROM `fcms_gallery_photos_tags` AS t
                LEFT JOIN `fcms_gallery_photos` AS p ON t.`photo` = p.`id`
                LEFT JOIN `fcms_category` AS c ON p.`category` = c.`id`
                LEFT JOIN `fcms_users` AS u ON t.`user` = u.`id`
                WHERE p.`category` = '$cid'
                AND p.`user` = '$uid'
                GROUP BY u.`id`";

        if (!$this->db->query($sql))
        {
            displaySqlError($sql, mysql_error());
            return $retVal;
        }

        if ($this->db->count_rows() <= 0)
        {
            if ($uid == $this->currentUserId || checkAccess($this->currentUserId) < 2)
            {
                $retVal .= '<a href="?tag='.$cid.'&amp;user='.$uid.'">'.T_('Tag Members In Photos').'</a>';
            }
            return $retVal;
        }

        $retVal .= '
            <ul class="avatar-member-list-small">';

        while ($row = $this->db->get_row())
        {
            $id          = (int)$row['id'];
            $displayname = cleanOutput($row['fname']).' '.cleanOutput($row['lname']);
            $avatarPath  = getAvatarPath($row['avatar'], $row['gravatar'], '../');

            $retVal .= '
                <li>
                    <a href="index.php?uid=0&amp;cid='.$id.'" class="tooltip" title="" onmouseover="showTooltip(this)" onmouseout="hideTooltip(this)">
                        <img class="avatar" alt="avatar" src="'.$avatarPath.'"/>
                    </a>
                    <div class="tooltip" style="display:none;">
                        <h5>'.$displayname.'</h5>
                        <span>'.sprintf(T_('Click to view more photos of %s.'), $displayname).'</span>
                    </div>
                </li>';
            
        }

        $retVal .= '
            </ul>';

        if ($uid == $this->currentUserId || checkAccess($this->currentUserId) < 2)
        {
            $retVal .= '<a href="?tag='.$cid.'&amp;user='.$uid.'">'.T_('Tag Members In Photos').'</a>';
        }

        return $retVal;
    }

    /**
     * getPrevId 
     * 
     * @param array $photos 
     * @param array $photoIdLookup 
     * @param int   $current
     * 
     * @return int
     */
    function getPrevId ($photos, $photoIdLookup, $current)
    {
        $total   = count($photos);
        $prev    = 0;

        // Is there a previous photo?
        if (isset($photos[$current-1]))
        {
            // External image
            if ($photos[$current-1]  == 'noimage.gif')
            {
                $prev = $photoIdLookup[$current-1];
            }
            // Real image
            else
            {
                // strip the extension off the filename to get the pid #s (ex: 453.gif)
                $prev = substr($photos[$current-1], 0, strpos($photos[$current-1], '.'));
            }
        }
        // No, then go to last photo
        else
        {
            // External image
            if (end($photos)  == 'noimage.gif')
            {
                $prev = end($photoIdLookup);
            }
            // Real image
            else
            {
                $prev = substr(end($photos), 0, strpos(end($photos), '.'));
            }
        }

        return $prev;
    }

    /**
     * getNextId 
     * 
     * @param array $photos 
     * @param array $photoIdLookup 
     * @param int   $current
     * 
     * @return int
     */
    function getNextId ($photos, $photoIdLookup, $current)
    {
        $total   = count($photos);
        $next    = 0;

        // Is there a next photo?
        if (isset($photos[$current+1]))
        {
            // External image
            if ($photos[$current+1]  == 'noimage.gif')
            {
                $next = $photoIdLookup[$current+1];
            }
            // Real image
            else
            {
                // strip the extension off the filename to get the pid #s (ex: 453.gif)
                $next = substr($photos[$current+1], 0, strpos($photos[$current+1], '.'));
            }
        }
        // No, then go to first photo
        else
        {
            // External image
            if ($photos[0]  == 'noimage.gif')
            {
                $next = $photoIdLookup[0];
            }
            // Real image
            else
            {
                $next = substr($photos[0], 0, strpos($photos[0], '.'));
            }
        }

        return $next;
    }

    /**
     * getCategoryInputs 
     * 
     * @return string
     */
    function getCategoryInputs ()
    {
        $categories = $this->getUserCategories();

        // We have existing categories
        if (count($categories) > 0)
        {
            return '
                    <input class="frm_text" type="text" id="new-category" name="new-category" size="35""/>
                    <select id="existing-categories" name="category">
                        <option value="0">&nbsp;</option>
                        '.buildHtmlSelectOptions($categories, '').'
                    </select>';
        }
        // No Categories (force creation of new one)
        else
        {
            return '
                    <input class="frm_text" type="text" id="new-category" name="new-category" size="50""/>';
        }
    }

    /**
     * getUploadTypesNavigation 
     * 
     * @param string $currentType 
     * 
     * @return void
     */
    function getUploadTypesNavigation ($currentType)
    {
        $nav = '';

        $types = array('upload', 'instagram');
        foreach ($types as $type)
        {
            $url   = '';
            $class = $currentType == $type ? 'current' : '';
            $text  = '';

            if ($type == 'upload')
            {
                $url   = '?action=upload';
                $text  = T_('Upload');
            }
            elseif ($type == 'instagram')
            {
                $config  = getInstagramConfigData();
                if (empty($config['instagram_client_id']) || empty($config['instagram_client_secret']))
                {
                    continue;
                }

                $url   = '?action=upload&amp;type=instagram';
                $text  = 'Instagram';
            }
            else
            {
                die('Invalid upload type.');
            }

            $nav .= '
                    <li class="'.$class.'"><a href="'.$url.'">'.$text.'</a></li>';
        }

        return $nav;
    }
}
