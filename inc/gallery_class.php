<?php
include_once('util_inc.php');
include_once('locale.php');

/**
 * PhotoGallery 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class PhotoGallery {

    var $db;
    var $db2;
    var $tz_offset;
    var $currentUserId;
    var $categories_per_row;

    /**
     * PhotoGallery 
     * 
     * @param   int         $currentUserId 
     * @param   database    $database 
     * @return  void
     */
    function PhotoGallery ($currentUserId, $database)
    {
        $this->categories_per_row = 4;
        $this->currentUserId = $currentUserId;
        $this->db = $database;
        $this->db2 = $database;
        $sql = "SELECT `timezone` 
                FROM `fcms_user_settings` 
                WHERE `user` = '$currentUserId'";
        $this->db->query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
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
        $locale = new Locale();

        $sql = "SELECT * 
                FROM (
                    SELECT p.`id`, p.`date`, p.`filename`, c.`name`, p.`user`, p.`category`
                    FROM `fcms_gallery_photos` AS p, `fcms_category` AS c
                    WHERE p.`category` = c.`id`
                    ORDER BY `date` DESC
                ) AS sub
                GROUP BY `category`
                ORDER BY `date` DESC LIMIT 6";
        $this->db->query($sql) or displaySQLError(
            'Latest Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($row = $this->db->get_row()) {
            $date = $locale->fixDate(T_('M. j, Y'), $this->tz_offset, $row['date']);
            $cat_array[] = '
                        <a href="?uid='.$row['user'].'&amp;cid='.$row['category'].'&amp;pid='.$row['id'].'">
                            <img class="photo" 
                                src="photos/member'.(int)$row['user'].'/tb_'.basename($row['filename']).'" 
                                alt="'.cleanOutput($row['name']).'"/>
                        </a>
                        <span>
                            <strong>'.cleanOutput($row['name']).'</strong>
                            <i>'.$date.'</i>
                        </span>';
        }
        if (!empty($cat_array)) {
            echo '
                <ul class="categories clearfix">';
            foreach ($cat_array as $cat_link) {
                echo '
                    <li class="category">'.$cat_link.'
                    </li>';
            }
            echo '
                </ul>';
        } else {
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
        }
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
        $locale = new Locale();

        // Select all photos for the category/group you are trying to view

        //--------------------------------------------------------------------
        // Latest Comments View
        //--------------------------------------------------------------------
        if (strpos($cid, 'comments') !== false) {
            $special_breadcrumbs = '<a href="?uid=0&amp;cid=comments">'.T_('Latest Comments').'</a>';
            $urlcid = $cid;
            $cid = substr($cid, 8);
            $cid = (int)$cid;
            $sql = "SELECT DISTINCT `filename` 
                    FROM (
                        SELECT p.`filename` 
                        FROM `fcms_gallery_comments` AS c, `fcms_gallery_photos` AS p 
                        WHERE c.`photo` = p.`id` ORDER BY c.`date` DESC
                    ) as z";
        
        //--------------------------------------------------------------------
        // Top Rated View
        //--------------------------------------------------------------------
        } elseif (strpos($cid, 'toprated') !== false) {
            $special_breadcrumbs = '<a href="?uid=0&amp;cid=toprated">'.T_('Top Rated').'</a>';
            $urlcid = $cid;
            $cid = substr($cid, 8);
            $cid = (int)$cid;
            $sql = "SELECT `filename` 
                    FROM `fcms_gallery_photos` 
                    WHERE `votes` > 0 
                    ORDER BY `rating`/`votes` DESC";
        
        //--------------------------------------------------------------------
        // Most Viewed View
        //--------------------------------------------------------------------
        } elseif (strpos($cid, 'mostviewed') !== false) {
            $special_breadcrumbs = "<a href=\"?uid=$uid&amp;cid=$cid\">".T_('Most Viewed')."</a>";
            $urlcid = $cid;
            $cid = substr($cid, 10);
            $cid = (int)$cid;
            $sql = "SELECT `filename` 
                    FROM `fcms_gallery_photos` 
                    WHERE `views` > 0";
            if ($uid > 0) {
                $sql .= " AND `user` = '$uid'";
            }
            $sql .= " ORDER BY `views` DESC";
        
        //--------------------------------------------------------------------
        // Tagged Photos View -- here $cid = 'tagged' plus the tagged user's id
        //--------------------------------------------------------------------
        } elseif (strpos($cid, 'tagged') !== false) {
            $urlcid = $cid;
            $cid = substr($cid, 6);
            $cid = (int)$cid;
            $userName = getUserDisplayName($cid);
            $special_breadcrumbs = "<a href=\"?uid=0&amp;cid=$cid\">".sprintf(T_('Photos of %s'), $userName)."</a>";
            $sql = "SELECT `filename` 
                    FROM `fcms_gallery_photos` AS p, `fcms_gallery_photos_tags` AS t 
                    WHERE t.`user` = '$cid' 
                    AND t.`photo` = p.`id` 
                    ORDER BY `date`";
        
        //-----------------------------------------------------------------
        // All Photos for Member
        //-----------------------------------------------------------------
        } elseif ($cid == 'all') {
            $urlcid = $cid;
            $userName = getUserDisplayName($uid);
            $special_breadcrumbs = '<a href="?uid='.$uid.'&amp;cid=all">'.sprintf(T_('Photos uploaded by %s'), $userName).'</a>';
            $sql = "SELECT `filename` 
                    FROM `fcms_gallery_photos`
                    WHERE `user` = $uid
                    ORDER BY `id` DESC";
                
        //--------------------------------------------------------------------
        // Category of Photos
        //--------------------------------------------------------------------
        } elseif (preg_match('/^\d+$/', $cid)) {
            $urlcid = $cid;
            $sql = "SELECT `filename` 
                    FROM `fcms_gallery_photos` 
                    WHERE `category` = '$cid' 
                    ORDER BY `date`";
        }

        // Run the SQL specified above
        $this->db2->query($sql) or displaySQLError(
            'Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        // Save filenames in an array, so we can see next/prev, etc
        while ($row = $this->db2->get_row()) {
            $photo_arr[] = $row['filename'];
        }

        // Check that we have atleast one photo in the current view        
        if (isset($photo_arr)) {

            // Select Current Photo to view
            $sql = "SELECT p.`user` AS uid, `filename`, `caption`, `category` AS cid, p.`date`, 
                        `name` AS category_name, `views`, `votes`, `rating` 
                    FROM `fcms_gallery_photos` AS p, `fcms_category` AS c 
                    WHERE p.`id` = '$pid' 
                    AND p.`category` = c.`id`";
            $this->db->query($sql) or displaySQLError(
                'Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            
            // Display the photo and other info
            if ($this->db->count_rows() > 0) {
                $r = $this->db->get_row();
                $displayname = getUserDisplayName($r['uid']);
                
                // Update View count
                $sql = "UPDATE `fcms_gallery_photos` 
                        SET `views` = `views`+1 
                        WHERE `id` = '$pid'";
                $this->db->query($sql) or displaySQLError(
                    'Update View Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );

                // Next / Previous links
                $total_photos = count($photo_arr);
                $cur = array_search($r['filename'], $photo_arr);
                // strip the extension off the filename to get the pid #s (ex: 453.gif)
                if (isset($photo_arr[$cur-1])) {
                    $prev_pid = substr($photo_arr[$cur-1], 0, strpos($photo_arr[$cur-1], '.'));
                }
                if (isset($photo_arr[$cur+1])) {
                    $next_pid = substr($photo_arr[$cur+1], 0, strpos($photo_arr[$cur+1], '.'));
                }

                // Showing only 1 of 1 photos
                if (!isset($prev_pid) && !isset($next_pid)) {
                    $c = $cur + 1;
                    $photos_of = '<i>('.sprintf(T_('%d of %d'), $c, $total_photos).')</i>';
                    $prev_next = '';

                // Showing the first of multiple photos
                } elseif (!isset($prev_pid)) {
                    $c = $cur + 1;
                    $photos_of = '<i>('.sprintf(T_('%d of %d'), $c, $total_photos).')</i>';
                    $prev_next = '
                <div class="prev_next clearfix">
                    <span class="previous">&nbsp;</span>
                    <a class="next" href="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$next_pid.'">'.T_('Next').'</a>
                </div>
                <script type="text/javascript">
                function keyHandler(e) {
                    if (!e) { e = window.event; }
                    arrowRight = 39;
                    switch (e.keyCode) {
                        case arrowRight:
                        document.location.href = "index.php?uid='.$uid.'&cid='.$urlcid.'&pid='.$next_pid.'";
                        break;
                    }
                }
                document.onkeydown = keyHandler;
                </script>';

                // Showing the last of multiple photos
                } elseif (!isset($next_pid)) {
                    $c = $cur + 1;
                    $photos_of = '<i>('.sprintf(T_('%d of %d'), $c, $total_photos).')</i>';
                    $prev_next = '
                <div class="prev_next clearfix">
                    <a class="previous" href="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$prev_pid.'">'.T_('Previous').'</a>
                    <span class="next">&nbsp;</span>
                </div>
                <script type="text/javascript">
                function keyHandler(e) {
                    if (!e) { e = window.event; }
                    arrowLeft = 37;
                    switch (e.keyCode) {
                        case arrowLeft:
                        document.location.href = "index.php?uid='.$uid.'&cid='.$urlcid.'&pid='.$prev_pid.'";
                        break;
                    }
                }
                document.onkeydown = keyHandler;
                </script>';

                // Showing photo with prev and next
                } else {
                    $c = $cur + 1;
                    $photos_of = '<i>('.sprintf(T_('%d of %d'), $c, $total_photos).')</i>';
                    $prev_next = '
                <div class="prev_next clearfix">
                    <a class="previous" href="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$prev_pid.'">'.T_('Previous').'</a>
                    <a class="next" href="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$next_pid.'">'.T_('Next').'</a>
                </div>
                <script type="text/javascript">
                function keyHandler(e) {
                    if (!e) { e = window.event; }
                    arrowRight = 39;
                    arrowLeft = 37;
                    switch (e.keyCode) {
                        case arrowRight:
                        document.location.href = "index.php?uid='.$uid.'&cid='.$urlcid.'&pid='.$next_pid.'";
                        break;
                        case arrowLeft:
                        document.location.href = "index.php?uid='.$uid.'&cid='.$urlcid.'&pid='.$prev_pid.'";
                        break;
                    }
                }
                document.onkeydown = keyHandler;
                </script>';
                }

                // What type of breadcrumbs
                if (isset($special_breadcrumbs)) {
                    echo '
                <p class="breadcrumbs">'.$special_breadcrumbs.'</p>
                <small>
                    '.T_('From the Category:').' <a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'">'.cleanOutput($r['category_name']).'</a> 
                    '.T_('by').' 
                    <a class="u" href="../profile.php?member='.$r['uid'].'">'.$displayname.'</a>
                </small>';

                } else {
                    echo '
                <div class="breadcrumbs clearfix">
                    <a href="?uid=0">'.T_('Members').'</a> &gt; 
                    <a href="?uid='.$uid.'">'.$displayname.'</a> &gt; 
                    <a href="?uid='.$uid.'&amp;cid='.$cid.'">'.cleanOutput($r['category_name']).'</a>
                    '.$photos_of.'
                    '.$prev_next.'
                </div>';

                }
                
                // Setup photo path
                $photo_path = '';
                $caption = cleanOutput($r['caption']);
                // Link to the full sized photo if using full sized
                $sql = "SELECT `full_size_photos` 
                        FROM `fcms_config`";
                $this->db->query($sql) or displaySQLError(
                    'Full Size Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                $row = $this->db->get_row();
                if ($row['full_size_photos'] == 1) {
                    // If you are using full sized but a photo was uploaded prior to that change, 
                    // no full sized photo will be available, so don't link to it
                    if (file_exists("photos/member" . $r['uid'] . "/full_" . $r['filename'])) {
                        $photo_path .= "/full_";
                        $dimensions = GetImageSize("photos/member".$r['uid']."/full_".$r['filename']);
                        $size = filesize("photos/member" . $r['uid'] . "/full_" . $r['filename']);
                    } else {
                        $photo_path .= "/";
                        $dimensions = GetImageSize("photos/member" . $r['uid'] . "/" . $r['filename']);
                        $size = filesize("photos/member" . $r['uid'] . "/" . $r['filename']);
                    }
                } else {
                    $photo_path .= "/";
                    $dimensions = GetImageSize("photos/member" . $r['uid'] . "/" . $r['filename']);
                    $size = filesize("photos/member" . $r['uid'] . "/" . $r['filename']);
                }            
                // Calculate rating
                if ($r['votes'] <= 0) {
                    $rating = 0;
                    $width = 0;
                } else {
                    $rating = ($r['rating'] / $r['votes']) * 100;
                    $rating = round($rating, 0);
                    $width = $rating / 5;
                }
                // Get Tagged Members
                $tagged_mem_list = '';
                $sql = "SELECT u.`id`, u.`lname` 
                        FROM `fcms_users` AS u, `fcms_gallery_photos_tags` AS t 
                        WHERE t.`photo` = '$pid' 
                        AND t.`user` = u.`id`
                        ORDER BY u.`lname`";
                $this->db->query($sql) or displaySQLError(
                    'Tagged Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                if ($this->db->count_rows() > 0) {
                    while ($t = $this->db->get_row()) {
                        $tagged_mem_list .= getUserDisplayName($t['id']) . ", ";
                    }
                    $tagged_mem_list = substr($tagged_mem_list, 0, -2); // remove the extra ", "
                } else {
                    $tagged_mem_list .= "<i>".T_('none')."</i>";
                }
                $date_added = $locale->fixDate(T_('F j, Y g:i a'), $this->tz_offset, $r['date']);
                $size = formatSize($size);

                // Edit / Delete Photo options
                $edit_del_options = '';
                if ($this->currentUserId == $r['uid'] || checkAccess($this->currentUserId) < 2) {
                    $edit_del_options = '
            <div class="edit_del_photo">
                <form action="index.php" method="post">
                    <div>
                        <input type="hidden" name="photo" id="photo" value="'.$pid.'"/>
                        <input type="hidden" name="url" id="url" value="uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$pid.'"/>
                        <input type="submit" name="editphoto" id="editphoto" value="'.T_('Edit').'" class="editbtn"/><br/>
                        <input type="submit" name="deletephoto" id="deletephoto" value="'.T_('Delete').'" class="delbtn"/>
                    </div>
                </form>
            </div>';
                }
                
                // Display Photo -- caption, rating and other info
                echo '
            <div id="photo">
                <p>
                    <a href="photos/member'.$r['uid'].$photo_path.basename($r['filename']).'"><img class="photo" 
                        src="photos/member'.$r['uid'].'/'.basename($r['filename']).'" alt="'.$caption.'" title="'.$caption.'"/></a>
                </p>
                <div id="caption">
                    '.$caption.'
                </div>
                <ul class="star-rating small-star">
                    <li class="current-rating" style="width:'.$width.'%">'.sprintf(T_('Currently %s/5 Starts'), $r['rating']).'</li>
                    <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=1" title="'.T_('1 out of 5 Stars').'" class="one-star">1</a></li>
                    <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=2" title="'.T_('2 out of 5 Stars').'" class="two-stars">2</a></li>
                    <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=3" title="'.T_('3 out of 5 Stars').'" class="three-stars">3</a></li>
                    <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=4" title="'.T_('4 out of 5 Stars').'" class="four-stars">4</a></li>
                    <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=5" title="'.T_('5 out of 5 Stars').'" class="five-stars">5</a></li>
                </ul>
            </div>
            '.$edit_del_options.'
            <div id="photo_details">
                <table id="photo_details_sub" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>'.T_('Filename').':</td>
                        <td>'.$r['filename'].'</td>
                    </tr>
                    <tr>
                        <td>'.T_('Views').':</td>
                        <td>'.$r['views'].'</td>
                    </tr>
                    <tr>
                        <td>'.T_('Photo Size').':</td>
                        <td>'.$size.'</td>
                    </tr>
                    <tr>
                        <td>'.T_('Dimensions').':</td>
                        <td>'.$dimensions[0].' x '.$dimensions[1].'</td>
                    </tr>
                    <tr>
                        <td>'.T_('Date Added').':</td>
                        <td>'.$date_added.'</td>
                    </tr>
                    <tr>
                        <td>'.T_('Members in Photo').':</td>
                        <td>'.$tagged_mem_list.'</td>
                    </tr>
                </table>
            </div>';
                
                // Display Comments
                if (
                    checkAccess($this->currentUserId) <= 8 && 
                    checkAccess($this->currentUserId) != 7 && 
                    checkAccess($this->currentUserId) != 4
                ) {
                    echo '
            <div style="clear:both"></div>
            <p>&nbsp;</p>
            <h3>'.T_('Comments').'</h3>';
                    $sql = "SELECT c.`id`, `comment`, `date`, `fname`, `lname`, `username`, `user`, `avatar` 
                            FROM `fcms_gallery_comments` AS c, `fcms_users` AS u 
                            WHERE `photo` = '$pid' 
                            AND c.`user` = u.`id` 
                            ORDER BY `date`";
                    $this->db->query($sql) or displaySQLError(
                        'Comments Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                    if ($this->db->count_rows() > 0) { 
                        while ($row = $this->db->get_row()) {
                            // Setup some vars for each comment block
                            $del_comment = '';
                            $date = $locale->fixDate(T_('F j, Y g:i a'), $this->tz_offset, $row['date']);
                            $displayname = getUserDisplayName($row['user']);
                            $comment = $row['comment'];
                            if ($this->currentUserId == $row['user'] || 
                                checkAccess($this->currentUserId) < 2) {
                                $del_comment .= '<input type="submit" name="delcom" id="delcom" '
                                    . 'value="'.T_('Delete').'" class="gal_delcombtn" title="'
                                    . T_('Delete this Comment') . '"/>';
                            }
                            echo '
            <div class="comment_block clearfix">
                <form class="delcom" action="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$pid.'" method="post">
                    '.$del_comment.'
                    <img class="avatar" alt="avatar" src="'.getCurrentAvatar($row['user'], false).'"/>
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
            <p>&nbsp;</p>
            <div class="add_comment_block">
                <form action="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$pid.'" method="post">
                    '.T_('Add Comment').'<br/>
                    <textarea class="frm_textarea" name="post" id="post" rows="3" cols="63"></textarea>
                    <input type="submit" name="addcom" id="addcom" value="'.T_('Add Comment').'" title="'.T_('Add Comment').'" class="gal_addcombtn"/>
                </form>
            </div>
            <p>&nbsp;</p>';
                }
            
            // Specific Photo couldn't be found
            } else {
                echo '
            <p class="error-alert">'.T_('The Photo you are trying to view can not be found.').'</p>';
            }

        // No photos exist for the current view/category
        // Even though we are in photo view, bump them back to the category view
        // and let the user know that this category is now empty
        } else {
            $this->displayGalleryMenu($uid, $cid);
            echo '
            <div class="info-alert">
                <h2>'.T_('Oops!').'</h2>
                <p>'.T_('The Category you are trying to view is Empty.').'</p>
            </div>';
        }
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
        $locale = new Locale();

        // # of categories per page -- used for pagination
        $perPage = 15;
        
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
                $from = ($page * 15) - 15;
                $sql .= " LIMIT $from, 15";
                
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
                    $from = ($page * 15) - 15;
                    $sql .= " LIMIT $from, 15";
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
                $from = ($page * 15) - 15;
                $sql .= " LIMIT $from, 15";
                
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
                $from = ($page * 15) - 15;
                $sql .= " LIMIT $from, 15";
                
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
                $from = ($page * 32) - 32;
                $sql .= " LIMIT $from, 32";
                $perPage = 32;
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
                $from = ($page * 15) - 15;
                $sql .= " LIMIT $from, 15";
                
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
                $from = ($page * 15) - 15;
                $sql .= " LIMIT $from, 15";
                
            //-----------------------------------------------------------------
            // Photo Listing for Member's Sub Category (only numbers 0-9)
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
                $from = ($page * 15) - 15;
                $sql .= " LIMIT $from, 15";
                
            //-----------------------------------------------------------------
            // All Photos for Member
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
                $from = ($page * 32) - 32;
                $sql .= " LIMIT $from, 32";
                $perPage = 32;
                
            //-----------------------------------------------------------------
            // Member's Sub Categories View
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
                $from = ($page * 15) - 15;
                $sql .= " LIMIT $from, 15";
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
                    $date = $locale->fixDate(T_('M. j, Y g:i a'), $this->tz_offset, $row['heading']);
                    $cat_name = "<strong>$date</strong>";
                    $url = "?uid=0&amp;cid=comments&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=0&amp;cid=comments";
                    $alt = ' alt="' . cleanOutput($row['caption']) . '"';
                    $title = ' title="' . cleanOutput($row['caption']) . '"';
                    $comment = $row['comment'];
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
                    $urlPage = "?uid=0&amp;cid=tagged" . $row['user'];
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
                        <img class="photo" src="photos/member'.$row['uid'].'/tb_'.basename($row['filename']).'" 
                            '.$alt.' '.$title.'/>
                    </a>';
                } else {
                    $category_rows[] = '
                    <a href="index.php'.$url.'">
                        <img src="photos/member'.$row['uid'].'/tb_'.basename($row['filename']).'" 
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
            if ($type == 'PHOTOS' || $type == 'TAGGED' || $type == 'ALL') {
                echo '
            <ul class="photos clearfix">';
                $i = 0;
                foreach ($category_rows as $row) {
                    echo '
                <li class="photo">'.$row.'
                </li>';
                    $i++;
                }
                echo '
            </ul>
            <p id="slideshow"><a class="new_window" href="slideshow.php?category='.$cid.'">'.T_('View Slideshow').'</a></p>';

            // Output for Categories
            } else {
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
                            <input class="frm_text" type="text" name="new-category" size="50"/>';
        }

        $memory = '';
        if ($overrideMemoryLimit) {
           $memory = '<input type="hidden" id="memory_override" name="memory_override" value="1"/>';
        }

        // Setup the list of members for the tagging select box
        $sql = "SELECT `id` FROM `fcms_users` WHERE `activated` > 0";
        $this->db2->query($sql) or displaySQLError(
            'Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = $this->db2->get_row()) {
            $displayNameArr[$r['id']] = getUserDisplayName($r['id'], 2);
        }
        asort($displayNameArr);
        $tag_checkboxes = '';
        foreach ($displayNameArr as $key => $value) {
            $tag_checkboxes .= '<label for="' . $key . '"><input type="checkbox" id="'
                . $key . '" name="tagged[]"  value="' . $key . '"/> ' . $value . '</label>';
        }

        // Display the form
        echo '
            <fieldset>
                <legend><span>'.T_('Upload Photos').'</span></legend>
                <p class="alignright">
                    <a class="help" href="../help.php#gallery-howworks">'.T_('Help').'</a>
                </p>
                <form enctype="multipart/form-data" action="?action=upload" method="post">
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
                    <div id="rotate-options">
                        <p class="center">
                            '.T_('Rotate Left').': <input type="radio" name="rotate" value="left"/>&nbsp;&nbsp; 
                            '.T_('Rotate Right').': <input type="radio" name="rotate" value="right"/>
                        </p>
                    </div>
                    <div id="tag-options">
                        <p class="center">
                            '.T_('Members in this Photo').':
                            <div class="multi-checkbox" style="margin: 0 auto;">
                                '.$tag_checkboxes.'
                            </div>
                        </p>
                    </div>
                    '.$memory.'
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
            // only hide the select box if last cat is set
            // giving an id to select will hide it
            $category_options = '
                    <input class="frm_text" type="text" name="new-category" size="35"/>
                    <select id="existing-categories" name="category" onchange="sendCategory(this.form.category)">
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
                    <input class="frm_text" type="text" name="new-category" size="50" onchange="sendNewCategory(this.form.new-category)"/>';
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
                    '.T_('JavaScript must be enabled in order for you to use the Chat Room. However, it seems JavaScript is either disabled or not supported by your browser.').'<br/>
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
                </form>
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
                    <param name="uc_scaledInstanceNames" value="small,medium"/>
                    <param name="uc_scaledInstanceDimensions" value="150x150xcrop,600x600xfit"/>
                    <param name="uc_scaledInstanceQualityFactors" value="900"/>
                    <param name="uc_uploadFormName" value="uploadForm"/>
                    <param name="vc_lookAndFeel" value="system"/>
                    <param name="vc_uploadViewStartUploadButtonText" value="Start upload"/>
                    <param name="vc_uploadViewFilesSummaryBarVisible" value="false"/>
                    <param name="ac_fireUploaderStatusChanged" value="true"/> 
                </applet>
                <script language="javascript">
                function sendCategory(select) {
                    var uploader = document.jumpLoaderApplet.getUploader();
                    var attrSet = uploader.getAttributeSet();
                    var attr = attrSet.createStringAttribute("category", select.value);
                    attr.setSendToServer(true);
                }
                function sendNewCategory(input) {
                    var uploader = document.jumpLoaderApplet.getUploader();
                    var attrSet = uploader.getAttributeSet();
                    var attr = attrSet.createStringAttribute("new_category", input.value);
                    attr.setSendToServer(true);
                }
                function uploaderStatusChanged( uploader ) {
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

        $sql = "SELECT p.`user`, `filename`, `caption`, `name` 
                FROM `fcms_gallery_photos` AS p, `fcms_category` AS c 
                WHERE p.`id` = '$photo'
                AND p.`category` = c.`id`";
        $this->db->query($sql) or displaySQLError(
            'Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );

        if ($this->db->count_rows() > 0) {
            $row = $this->db->get_row();
            $photo_user = cleanInput($row['user'], 'int');
            $filename = basename($row['filename']);
            $caption = cleanOutput($row['caption']);
            $category = cleanOutput($row['name']);
            
            // Setup the list of categories for the select box
            $categories = $this->getUserCategories($photo_user);

            $cat_options = '';

            foreach ($categories as $id => $name) {
                $cat_options .= '<option value="' . $id . '"';
                if ($category == $name) {
                    $cat_options .= ' selected="selected"';
                }
                $cat_options .= '>' . cleanOutput($name) . '</option>';
            }
            
            // Setup the list of active members for the select box
            $sql = "SELECT * 
                    FROM `fcms_gallery_photos_tags` 
                    WHERE `photo` = '$photo'";
            $this->db2->query($sql) or displaySQLError(
                'Find Tagged Users Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            while ($r = $this->db2->get_row()) {
                $users_in_photo[$r['id']] = $r['user'];
            }
            $sql = "SELECT `id` 
                    FROM `fcms_users` 
                    WHERE `activated` > 0";
            $this->db2->query($sql) or displaySQLError(
                'Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            while ($r = $this->db2->get_row()) {
                $displayNameArr[$r['id']] = getUserDisplayName($r['id'], 2);
            }
            asort($displayNameArr);
            $tag_checkboxes = '';
            foreach ($displayNameArr as $key => $value) {
                $tag_checkboxes .= '<label for="' . $key . '"><input type="checkbox" id="'
                    . cleanOutput($key) . '" name="tagged[]"  value="' . cleanOutput($key) . '"';
                if (isset($users_in_photo)) {
                    $found = array_search($key, $users_in_photo);
                    if ($found !== false) {
                        $tag_checkboxes .= ' checked="checked"';
                    }
                }
                $tag_checkboxes .= "/> $value</label>";
            }
            
            // Send over a string of the previous members who were tagged, if applicable
            $prev_tagged = '';
            if (isset($users_in_photo)) {
                $prev_tagged .= '<div><input type="hidden" name="prev_tagged_users" value="';
                $first = true;
                foreach ($users_in_photo as $uid) {
                    if ($first) {
                        $prev_tagged .= $uid;
                    } else {
                        $prev_tagged .= ",$uid";
                    }
                    $first = false;
                }
                $prev_tagged .= '"/></div>';
            }
            
            // Display the form
            echo '
                <fieldset>
                    <legend><span>'.T_('Edit Photo').'</span></legend>
                    <img class="thumbnail" src="photos/member'.$photo_user.'/tb_'.$filename.'"/>
                    <form enctype="multipart/form-data" action="index.php?'.$url.'" method="post">
                        <div class="field-row clearfix">
                            <div class="field-label"><label><b>'.T_('Change Category').'</b></label></div>
                            <div class="field-widget">
                                <select class="frm_sel" name="category">
                                    '.$cat_options.'
                                </select>
                            </div>
                        </div>
                        <div class="field-row clearfix">
                            <div class="field-label"><label><b>'.T_('Caption').'</b></label></div>
                            <div class="field-widget">
                                <input class="frm_text" type="text" name="photo_caption" size="50" value="'.$caption.'"/>
                            </div>
                        </div>
                        <div class="field-row clearfix">
                            <div class="field-label"><label><b>'.T_('Who is in this Photo?').'</b></label></div>
                            <div class="field-widget">
                                <div class="multi-checkbox">
                                    '.$tag_checkboxes.'
                                </div>
                            </div>
                        </div>
                        '.$prev_tagged.'
                        <p>
                            <input type="hidden" name="photo_id" id="photo_id" value="'.$photo.'"/>
                            <input type="submit" name="add_editphoto" value="'.T_('Edit').'"/> 
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

        // Setup the list of active members for the select box
        $sql = "SELECT `id` 
                FROM `fcms_users` 
                WHERE `activated` > 0";
        $this->db2->query($sql) or displaySQLError(
            'Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = $this->db2->get_row()) {
            $displayNameArr[$r['id']] = getUserDisplayName($r['id'], 2);
        }
        asort($displayNameArr);

        // Display the form
        echo '
                <form action="index.php?action=advanced" method="post">
                    <fieldset>
                        <legend><span>'.T_('Edit Photos').'</span></legend>';

        $i=0;
        foreach ($_SESSION['photos'] AS $photo) {
            echo '
                        <img style="float:right" src="photos/member'.$this->currentUserId.'/tb_'.basename($photo['filename']).'"/>
                        <p>
                            '.T_('Caption').'<br/>
                            <input type="text" class="frm_text" name="caption[]" width="50"/>
                            <input type="hidden" name="id[]" value="'.(int)$photo['id'].'"/>
                            <input type="hidden" name="category[]" value="'.(int)$photo['category'].'"/>
                        </p>
                        <p>
                            '.T_('Who is in this Photo?').' 
                            <div class="multi-checkbox">';

            $tag_checkboxes = '';
            foreach ($displayNameArr as $key => $value) {
                $tag_checkboxes .= '<label for="'.$key.$i.'"><input type="checkbox" id="'.$key.$i.'" name="tagged['.$i.'][]"  value="'.$key.'"/> '.$value.'</label>';
            }
            echo '
                                '.$tag_checkboxes.'
                            </div>
                        </p>
                        <hr/>';
            $i++;
        }
        echo '
                        <br/>
                        <p>
                            <input class="sub1" type="submit" name="submit_advanced_edit" id="submit_advanced_edit" value="'.T_('Save').'"/> 
                            '.T_('or').' 
                            <a href="index.php?action=upload">'.T_('Cancel').'</a>
                        </p>
                    </fieldset>
                </form>';
    }

    /**
     * uploadPhoto 
     * 
     * Uploads a photo to the /gallery/photos/memberX/ directory where x is the user id.
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
        $known_photo_types = array(
            'image/pjpeg'   => 'jpeg', 
            'image/jpeg'    => 'jpg', 
            'image/gif'     => 'gif', 
            'image/bmp'     => 'bmp', 
            'image/x-png'   => 'png', 
            'image/png'     => 'png'
        );
        $gd_function_suffix = array(
            'image/pjpeg'   => 'JPEG', 
            'image/jpeg'    => 'JPEG', 
            'image/gif'     => 'GIF', 
            'image/bmp'     => 'BMP', 
            'image/x-png'   => 'PNG', 
            'image/png'     => 'PNG'
        );

        // Are we using full sized photos?
        $sql = "SELECT `full_size_photos` FROM `fcms_config`";
        $this->db->query($sql) or displaySQLError(
            'Full Size Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = $this->db->get_row();

        if ($photo['size'] > 0) {
                
            // Check file type
            if (!array_key_exists($photo['type'], $known_photo_types)) {
                echo '
            <p class="error-alert">
                '.sprintf(T_('Error: File %s is not a photo.  Photos must be of type (.JPG, .JPEG, .GIF, .BMP or .PNG).'), $photo['type']).'
            </p>';
                return;
            }

            // Get extension of photo
            $ext = explode('.', $photo['name']);
            $ext = end($ext);
            $ext = strtolower($ext);

            // Check file extension
            if (!in_array($ext, $known_photo_types)) {
                echo '
            <p class="error-alert">
                '.sprintf(T_('Error: File %s is not a photo.  Photos must be of type (.JPG, .JPEG, .GIF, .BMP or .PNG).'), $photo['type']).'
            </p>';
                return;
            }
                    
            // Create a new photo record in DB
            $sql = "INSERT INTO `fcms_gallery_photos`
                        (`date`, `caption`, `category`, `user`)
                    VALUES(
                        NOW(), 
                        '" . cleanInput($caption) . "', 
                        '" . cleanInput($category) . "', 
                        '" . $this->currentUserId . "'
                    )";
            $this->db->query($sql) or displaySQLError(
                'Add Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
                    
            // Update the filename and update the photo record in DB
            // We an insert above and update below so we can make sure that the filename of
            // the photo is the same as the photo id
            $new_id     = mysql_insert_id();
            $filetype   = $photo['type'];
            $filename   = $new_id . "." . $ext;
            $sql = "UPDATE `fcms_gallery_photos` 
                    SET `filename` = '" . cleanInput($filename) . "' 
                    WHERE `id` = " . cleanInput($new_id);
            $this->db->query($sql) or displaySQLError(
                'Update Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
                    
            // Create new member directory if needed
            if (!file_exists("photos/member" . $this->currentUserId)) {
                mkdir("photos/member" . $this->currentUserId);
            }
                    
            // Copy the tmp file to the member's photo dir
            copy($photo['tmp_name'], "photos/member" . $this->currentUserId . "/" . $filename);

            // If using full sized photos, make another copy of the file, because the first
            // copy is going to be resized.
            if ($r['full_size_photos'] == '1') {
                copy($photo['tmp_name'], "photos/member" . $this->currentUserId . "/full_" . $filename);
            }
                    
            // Get image sizes
            $size = GetImageSize("photos/member" . $this->currentUserId . "/" . $filename);

            // Set Thumbnail Size -- make it square
            $thumbnail = $this->getResizeSizeSquare(
                $size[0], 
                $size[1], 
                $thumb_max_width
            );
            $thumbnail_temp_width  = $thumbnail[0];
            $thumbnail_temp_height = $thumbnail[1];
            $thumbnail_width       = $thumbnail[2];
            $thumbnail_height      = $thumbnail[3];
                    
            // Set Main Photo Size -- make it proportional
            $main = $this->getResizeSize(
                $size[0], 
                $size[1], 
                $main_max_width, 
                $main_max_height
            );
            $main_width  = $main[0];
            $main_height = $main[1];


            // Do we need to resize the photos?
            $thumbNeedsResized = true;
            $mainNeedsResized = true;
            if ($size[0] < $thumb_max_width && $size[1] < $thumb_max_height) {
                $thumbNeedsResized = false;
            }
            if ($size[0] < $main_max_width && $size[1] < $main_max_height) {
                $mainNeedsResized = false;
            }

            // If were not resizing or rotating, then no need to do fancy GD stuf below, just copy over thumbnail and return
            if (   !$thumbNeedsResized
                && !$mainNeedsResized
                && $rotateoptions != 'left'
                && $rotateoptions != 'right'
            ) {
                copy($photo['tmp_name'], "photos/member" . $this->currentUserId . "/tb_" . $filename);
                echo '
            <p class="ok-alert">
                <b>'.T_('The following photo was added successfully.').'</b><br/><br/>
                &nbsp;&nbsp;&nbsp;<img src="photos/member'.$this->currentUserId.'/tb_'.$filename.'" alt="'.$caption.'"/>
            </p>';
                return $new_id;
            }

            // Check to see if we are going to run out of memory
            if (!$overrideMemoryWarning) {
                $memoryAvailable = ini_get('memory_limit');
                $memoryAvailable = substr($memoryAvailable, 0, -1);
                $memoryAvailable = ($memoryAvailable * 1024) * 1024;
                $memoryNeeded = Round(($size[0] * $size[1] * $size['bits'] * $size['channels'] / 8 + Pow(2, 16)) * 1.65);
                if ($memoryNeeded > $memoryAvailable) {
                    // Remove the photo from the DB
                    $sql = "DELETE FROM `fcms_gallery_photos` 
                            WHERE `id` = '$new_id'";
                    mysql_query($sql) or displaySQLError(
                        'Delete Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                    
                    // Remove the Photo from the server
                    unlink("photos/member" . $this->currentUserId . "/" . $filename);
                    if ($r['full_size_photos'] == 1) {
                        unlink("photos/member" . $this->currentUserId . "/full_" . $filename);
                    }
                    echo '
            <div class="info-alert">
                <h2>'.T_('Out of Memory Warning.').'</h2>
                <p>
                    '.T_('The photo you are trying to upload is quite large and the server might run out of memory if you continue.').' 
                    '.T_('It is recommended that you try to upload this photo using the "Advanced Uploader" instead.').'
                    <small>('.$memoryNeeded.' / '.$memoryAvailable.')</small>
                </p>
                <h3>'.T_('What do you want to do?').'</h3>
                <table cellpadding="0" cellspacing="0" style="width:100%">
                    <tr>
                        <td><a href="../settings.php">'.T_('Use the "Advanced Uploader"').'</a></td>
                        <td class="center">'.T_('or').'</td>
                        <td style="text-align:right">
                            <a class="u" href="?action=upload&amp;memory=confirm">'.T_('I want to continue with this photo').'</a>
                        </td>
                    </tr>
                </table>
            </div>';
                    return false;
                }
            }

            // Setup function to create new file
            $function_suffix  = $gd_function_suffix[$filetype];
            $function_to_read = "ImageCreateFrom" . $function_suffix;
            // Treat Bitmaps as Jpeg
            if ($function_suffix == 'BMP') {
                $function_to_write = "ImageJPEG";
            } else {
                $function_to_write = "Image" . $function_suffix;
            }

            // Create new file
            $source_handle = $function_to_read(
                "photos/member" . $this->currentUserId . "/" . $filename
            );

            if ($source_handle) {
                // Create a new square Thumbnail
                $thumb_destination_handle = ImageCreateTrueColor($thumbnail_width, $thumbnail_height);
                ImageCopyResampled(
                    $thumb_destination_handle, 
                    $source_handle, 
                    0, 0, 0, 0, 
                    $thumbnail_temp_width, 
                    $thumbnail_temp_height, 
                    $size[0], 
                    $size[1]
                );
                // Create a new resized copy of the Main Photo
                $main_destination_handle = ImageCreateTrueColor($main_width, $main_height);
                ImageCopyResampled(
                    $main_destination_handle, 
                    $source_handle, 
                    0, 0, 0, 0, 
                    $main_width, 
                    $main_height, 
                    $size[0], 
                    $size[1]
                );
            }

            if ($r['full_size_photos'] == '1') {
                $full_destination_handle = $function_to_read(
                    "photos/member" . $this->currentUserId . "/" . $filename
                );
            }
            $function_to_write(
                $thumb_destination_handle, 
                "photos/member" . $this->currentUserId . "/tb_" . $filename
            );
            $function_to_write(
                $main_destination_handle, 
                "photos/member" . $this->currentUserId . "/" . $filename
            );
                    
            // File Rotation
            if ($rotateoptions == 'left' || $rotateoptions =='right') {
                if ($rotateoptions == 'left') {
                    $rotate_thumb = imagerotate($thumb_destination_handle, 90, 0);
                    $rotate_main = imagerotate($main_destination_handle, 90, 0);
                    if ($r['full_size_photos'] == '1') {
                        $rotate_full = imagerotate($full_destination_handle, 90, 0);
                    }
                }
                if ($rotateoptions == 'right') {
                    $rotate_thumb = imagerotate($thumb_destination_handle, 270, 0);
                    $rotate_main = imagerotate($main_destination_handle, 270, 0);
                    if ($r['full_size_photos'] == '1') {
                        $rotate_full = imagerotate($full_destination_handle, 270, 0);
                    }
                }
                // Save the new rotated image
                switch($function_suffix) {
                    case 'JPEG':
                        imagejpeg(
                            $rotate_thumb, 
                            "photos/member" . $this->currentUserId . "/tb_" . $filename
                        );
                        imagejpeg(
                            $rotate_main, 
                            "photos/member" . $this->currentUserId . "/" . $filename
                        );
                        if ($r['full_size_photos'] == '1') {
                            imagejpeg(
                                $rotate_full, 
                                "photos/member" . $this->currentUserId . "/full_" . $filename
                            );
                        }
                        break;
                    case 'GIF':
                        imagegif(
                            $rotate_thumb, 
                            "photos/member" . $this->currentUserId . "/tb_" . $filename
                        );
                        imagegif(
                            $rotate_main, 
                            "photos/member" . $this->currentUserId . "/" . $filename
                        );
                        if ($r['full_size_photos'] == '1') {
                            imagegif(
                                $rotate_full, 
                                "photos/member" . $this->currentUserId . "/full_" . $filename
                            );
                        }
                        break;
                    case 'WBMP':
                        imagewbmp(
                            $rotate_thumb, 
                            "photos/member" . $this->currentUserId . "/tb_" . $filename
                        );
                        imagewbmp(
                            $rotate_main, 
                            "photos/member" . $this->currentUserId . "/" . $filename
                        );
                        if ($r['full_size_photos'] == '1') {
                            imagewbmp(
                                $rotate_full, 
                                "photos/member" . $this->currentUserId . "/full_" . $filename
                            );
                        }
                        break;
                    case 'PNG':
                        imagepng(
                            $rotate_thumb, 
                            "photos/member" . $this->currentUserId . "/tb_" . $filename
                        );
                        imagepng(
                            $rotate_main, 
                            "photos/member" . $this->currentUserId . "/" . $filename
                        );
                        if ($r['full_size_photos'] == '1') {
                            imagepng(
                                $rotate_full, 
                                "photos/member" . $this->currentUserId . "/full_" . $filename
                            );
                        }
                        break;
                    default:
                        imagejpg(
                            $rotate_thumb, 
                            "photos/member" . $this->currentUserId . "/tb_" . $filename
                        );
                        imagejpg(
                            $rotate_main, 
                            "photos/member" . $this->currentUserId . "/" . $filename
                        );
                        if ($r['full_size_photos'] == '1') {
                            imagejpg(
                                $rotate_full, 
                                "photos/member" . $this->currentUserId . "/full_" . $filename
                            );
                        }
                        break;
                }
            }
                    
            // Clean up the temporary files
            ImageDestroy($thumb_destination_handle);
            ImageDestroy($main_destination_handle);
            if ($r['full_size_photos'] == '1') {
                ImageDestroy($full_destination_handle);
            }
            
            // Output a success message
            echo '
            <p class="ok-alert">
                <b>'.T_('The following photo was added successfully.').'</b><br/><br/>
                &nbsp;&nbsp;&nbsp;<img src="photos/member'.$this->currentUserId.'/tb_'.$filename.'" alt="'.$caption.'"/>
            </p>';
        }

        if (isset($new_id)) {
            return $new_id;
        }
    }
    
    /**
     * getResizeSize 
     * 
     * Given a photo's width/height, and the maximum resized width/height, it will calculate 
     * the width/height while not distorting.
     *
     * For example, a 800x600 photo with a max size of 500x500 will return 500x375
     *
     * @param int $orig_width  the original width of the photo
     * @param int $orig_height the original height of the photo
     * @param int $max_width   the maximum width for the new photo size
     * @param int $max_height  the maximum height for the new photo size
     * @param int $square      force width/height to be square?
     *
     * @return  array   the new width/height
     */
    function getResizeSize ($orig_width, $orig_height, $max_width, $max_height)
    {
        // Wider than tall
        if ($orig_width > $orig_height) {
            // Check width
            if ($orig_width > $max_width) {
                $height = (int)($max_width * $orig_height / $orig_width);
                return array($max_width, $height);
            // No need to resize if it's smaller than max
            } else {
                return array($orig_width, $orig_height);
            }

        // Taller than wide
        } else {
            // Check height
            if ($orig_height > $max_height) {
                $width = (int)($max_height * $orig_width / $orig_height);
                return array($width, $max_height);
            // Check width
            } elseif ($orig_width > $max_width) {
                $height = (int)($max_width * $orig_height / $orig_width);
                return array($max_width, $height);
            // No need to resize if it's smaller than max
            } else {
                return array($orig_width, $orig_height);
            }
        }
    }

    /**
     * getResizeSizeSquare 
     * 
     * Given the photos width/height and a max, it will resize the photo to as close to
     * square as possible, allowing the smallest amount of cropping possible.
     * Photos smaller than the max will not be square and will not be resized/cropped.
     * 
     * Returns an array with the photo demensions and crop demensions:
     *      array( resize_width, resize_height, crop_width, crop_height )
     *
     * For example: given a photo of 800x600 and max size of 150
     *      will return:  array(200, 150, 150, 150)
     * 
     * For example: given a photo of 45x20 and max size of 150
     *      will return:  array(45, 20, 45, 20)
     * 
     * @param string $width 
     * @param string $height 
     * @param string $max 
     * 
     * @return  array
     */
    function getResizeSizeSquare ($width, $height, $max)
    {
        // Wider than tall
        if ($width > $height) {

            // Check height
            if ($height > $max) {
                $width = (int)($max * $width / $height);
                return array($width, $max, $max, $max);
            }

        // Taller than wide
        } else {

            // Check width
            if ($width > $max) {
                $height = (int)($max * $height / $width);
                return array($max, $height, $max, $max);
            }
        }

        // if all else fails return orig dimensions
        return array($width, $height, $width, $height);
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
     * displayWhatsNewGallery 
     * 
     * @return void
     */
    function displayWhatsNewGallery ()
    {
        $locale = new Locale();
        $today_start = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '000000';
        $today_end   = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '235959';

        echo '
            <h3>'.T_('Photo Gallery').'</h3>';
        $sql = "SELECT DISTINCT p.user, name AS category, p.category AS cid, 
                    DAYOFYEAR(p.`date`) AS d, COUNT(*) AS c 
                FROM fcms_gallery_photos AS p, fcms_users AS u, fcms_category AS c 
                WHERE p.user = u.id 
                    AND p.category = c.id 
                    AND p.`date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 
                GROUP BY user, category, d 
                ORDER BY p.`date` DESC LIMIT 0 , 5";
        $this->db->query($sql) or displaySQLError(
            'Last 5 New Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo '
            <ul>';
            while ($row = $this->db->get_row()) {
                $displayname = getUserDisplayName($row['user']);
                $category = cleanOutput($row['category']);
                $full_category = cleanOutput($category);
                if (strlen($category) > 20) {
                    $category = substr($category, 0, 17) . "...";
                }
                $sql = "SELECT p.`date` FROM fcms_gallery_photos AS p, fcms_category AS c "
                     . "WHERE p.user = '" . $row['user'] . "' "
                     . "AND c.id = p.category "
                     . "AND c.name = '" . cleanInput($full_category) . "' "
                     . "AND DAYOFYEAR(p.`date`) = " . $row['d'] . " "
                     . "ORDER BY p.`date` DESC LIMIT 1";
                $result = mysql_query($sql) or displaySQLError(
                    'Date Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                $found = mysql_fetch_array($result);
                $date = $locale->fixDate('YmdHis', $this->tz_offset, $found['date']);

                // Today
                if ($date >= $today_start && $date <= $today_end) {
                    $full_date = T_('Today');
                    $d = ' class="today"';
                } else {
                    $full_date = $locale->fixDate(T_('M. j, Y g:i a'), $this->tz_offset, $found['date']);
                    $d = '';
                }
                echo '
                    <li>
                        <div'.$d.'>'.$full_date.'</div>
                        <a href="gallery/index.php?uid='.$row['user'].'&amp;cid='.$row['cid'].'" title="'.$full_category.'">'.$category.'</a>
                        ('.sprintf(T_('%d new photos'), $row['c']).') - 
                        <a class="u" href="profile.php?member='.$row['user'].'">'.$displayname.'</a>
                    </li>';
            }
            echo '
                </ul>';
        } else {
            echo '
                <ul><li><i>'.T_('nothing new last 30 days').'</i></li></ul>';
        }
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
        $locale = new Locale();

        $perPage = 5;
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
            <form id="delete_category" action="gallery.php" method="post">
                <p><input type="submit" class="sub1" id="deleteAll" name="deleteAll" value="'.T_('Delete All').'"/></p>
                <table class="sortable" cellspacing="0" cellpadding="0">
                    <tr>
                        <th class="id">'.T_('ID').'</th>
                        <th>'.T_('Category').'</th>
                        <th>'.T_('User').'</th>
                        <th>'.T_('Photos').'</th>
                        <th>'.T_('Edit').'</th>
                        <th>'.T_('Delete').'</th>
                    </tr>
                    <tr class="check_all_none">
                        <td colspan="6">
                            '.T_('Select:').'
                            <a href="#" onclick="$(\'delete_category\').getInputs(\'checkbox\').each(function(e){ e.checked = 1 }); return false;">'.T_('All').'</a> 
                            <a href="#" onclick="$(\'delete_category\').getInputs(\'checkbox\').each(function(e){ e.checked = 0 }); return false;">'.T_('None').'</a>
                        </td>
                    </tr>';

        while ($row = $this->db->get_row()) {
            echo '
                    <tr>
                        <td>'.$row['category'].'</td>
                        <td>
                            <b>'.$row['name'].'</b><br/>
                            <img src="../gallery/photos/member'.$row['user'].'/tb_'.basename($row['filename']).'" alt="'.cleanOutput($row['name']).'"/>
                        </td>
                        <td>'.getUserDisplayName($row['user']).'</td>
                        <td>'.$this->getCategoryPhotoCount($row['category']).'</td>
                        <td><a href="?edit='.$row['category'].'">'.T_('Edit').'</a></td>
                        <td class="check"><input type="checkbox" id="cat'.$row['category'].'" name="bulk_actions[]" value="'.$row['category'].'"/></td>
                    </tr>';

        }

        echo '
                </table>
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
        $locale = new Locale();
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
            <form id="delete_category" action="gallery.php?edit='.$id.'" method="post">
                <p><input type="submit" class="sub1" id="deleteAllPhotos" name="deleteAllPhotos" value="'.T_('Delete All').'"/></p>
                <table class="sortable" cellspacing="0" cellpadding="0">
                    <tr>
                        <th class="id">'.T_('ID').'</th>
                        <th>'.T_('Photo').'</th>
                        <th>'.T_('Views').'</th>
                        <th>'.T_('Date').'</th>
                        <th>'.T_('Delete').'</th>
                    </tr>
                    <tr class="check_all_none">
                        <td colspan="6">
                            '.T_('Select:').'
                            <a href="#" onclick="$(\'delete_category\').getInputs(\'checkbox\').each(function(e){ e.checked = 1 }); return false;">'.T_('All').'</a> 
                            <a href="#" onclick="$(\'delete_category\').getInputs(\'checkbox\').each(function(e){ e.checked = 0 }); return false;">'.T_('None').'</a>
                        </td>
                    </tr>';

        while ($row = $this->db->get_row()) {
            $date = $locale->fixDate(T_('F j, Y g:i a'), $this->tz_offset, $row['date']);
            echo '
                    <tr>
                        <td>'.$row['id'].'</td>
                        <td>
                            <img src="../gallery/photos/member'.$row['user'].'/tb_'.basename($row['filename']).'" alt="'.cleanOutput($row['caption']).'"/><br/>
                            '.$row['caption'].'
                        </td>
                        <td>'.$row['views'].'</td>
                        <td>'.$date.'</td>
                        <td class="check"><input type="checkbox" id="photo'.$row['id'].'" name="bulk_actions[]" value="'.$row['id'].'"/></td>
                    </tr>';

        }

        echo '
                </table>
                <p><input type="submit" class="sub1" id="deleteAllPhotos" name="deleteAllPhotos" value="'.T_('Delete All').'"/></p>
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
}
