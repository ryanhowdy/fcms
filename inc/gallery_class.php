<?php
include_once('util_inc.php');
include_once('locale.php');

class PhotoGallery {

    var $db;
    var $db2;
    var $tz_offset;
    var $cur_user_id;
    var $categories_per_row;

    function PhotoGallery ($current_user_id, $database)
    {
        $this->categories_per_row = 4;
        $this->cur_user_id = $current_user_id;
        $this->db = $database;
        $this->db2 = $database;
        $sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id";
        $this->db->query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
        bindtextdomain('messages', '.././language');
    }

    function displayGalleryMenu ($uid = '', $cid = '')
    {
        $home = $member = $rated = $viewed = $my = $search = '';
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
        if ($uid == $this->cur_user_id && $cid == '') {
            $my = ' selected';
        }
        echo '
            <div id="sections_menu" class="clearfix">
                <ul>
                    <li><a class="'.$home.'" href="index.php">'._('Photo Gallery Home').'</a></li>
                    <li><a class="'.$member.'" href="?uid=0">'._('Member Gallery').'</a></li>
                    <li><a class="'.$rated.'" href="?uid='.$uid.'&amp;cid=toprated">'._('Top Rated').'</a></li>
                    <li><a class="'.$viewed.'" href="?uid='.$uid.'&amp;cid=mostviewed">'._('Most Viewed').'</a></li>
                    <li><a class="'.$my.'" href="?uid='.$this->cur_user_id.'">'._('My Photos').'</a></li>
                    <li><a class="'.$search.'" href="?search=form">'._('Search').'</a></li>
                </ul>
            </div>
            <div id="actions_menu" class="clearfix">
                <ul>
                    <li><a class="upload" href="?action=upload">'._('Upload Photos').'</a></li>
                    <li><a class="manage_categories" href="?action=category">'._('Manage Categories').'</a></li>
                </ul>
            </div>';
    }

    /*
     *  displaySearchForm
     *
     *  Displays the form to search the photo gallery.
     */
    function displaySearchForm ()
    {
        $displayNameArr = array();
        $sql = "SELECT `id` FROM `fcms_users` WHERE `activated` > 0";
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
                    <legend><span>'._('Search').'</span></legend>
                    <form action="index.php" method="get">
                        <div class="field-row clearfix">
                            <div class="field-label"><b>'._('Photo Uploaded By').'</b></div>
                            <div class="field-widget">
                                <select name="uid">
                                    <option value="0"></option>';
        foreach ($displayNameArr as $key => $value) {
            echo '
                                    <option value="'.$key.'">'.$value.'</option>';
        }
        echo '
                                </select>
                            </div>
                        </div>
                        <div class="field-row clearfix">
                            <div class="field-label"><b>'._('Members In Photo').'</b></div>
                            <div class="field-widget">
                                <select name="cid">
                                    <option value="all"></option>';
        foreach ($displayNameArr as $key => $value) {
            echo '
                                    <option value="'.$key.'">'.$value.'</option>';
        }
        echo '
                                </select>
                            </div>
                        </div>
                        <p><input class="sub1" type="submit" value="'._('Search').'"/></p>
                    </form>
                </fieldset>';
    }

    /*
     *  displayLatestCategories
     *
     *  Displays the last 4 categories that most recently had new photos added.
     */
    function displayLatestCategories()
    {
        $locale = new Locale();
        $sql = "SELECT * 
                FROM (
                    SELECT p.`id`, p.`date`, p.`filename`, c.`name`, p.`user`, p.`category`
                    FROM `fcms_gallery_photos` AS p, `fcms_gallery_category` AS c
                    WHERE p.`category` = c.`id`
                    ORDER BY `date` DESC
                ) AS sub
                GROUP BY `category`
                ORDER BY `date` DESC LIMIT 6";
        $this->db->query($sql) or displaySQLError(
            'Latest Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($row = $this->db->get_row()) {
            $date = $locale->fixDate(_('M. j, Y'), $this->tz_offset, $row['date']);
            $cat_array[] = '
                        <a class="category-link" href="?uid='.$row['user'].'&amp;cid='.$row['category'].'&amp;pid='.$row['id'].'">
                            <img class="photo" src="photos/member'.$row['user'].'/tb_'.$row['filename'].'" alt=""/>
                            <span>
                                <strong>'.$row['name'].'</strong>
                                <i>'.$date.'</i>
                            </span>
                        </a>';
        }
        if (!empty($cat_array)) {
            echo '
                <h3>'._('Latest Categories').'</h3>
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
                    <h2>'._('Welcome to the Photo Gallery').'</h2>
                    <p><i>'._('Currently no one has added any photos').'</i></p>
                    <p><b>'._('How do I add photos?').'</b></p>
                    <ol>
                        <li><a href="?action=category">'._('Create a Category').'</a></li>
                        <li><a href="?action=upload">'._('Upload Photos').'</a></li>
                    </ol>
                </div>';
        }
    }
    
    /*
     *  showPhoto
     *
     *  Displays the current photo, info, comments, next/prev butons etc.
     *
     *  The following views use this function:
     *      Latest Comments - uid=0         cid=comments
     *      Top Rated       - uid=0         cid=toprated
     *      Most  Viewed    - uid=userid    cid=mostviewed
     *      Tagged Users    - uid=0         cid=tagged# (where # is the id of the tagged user)
     *      All for User    - uid=userid    cid=all
     * 
     *  @param      $uid    the user's id or 0
     *  @param      $cid    the category id, 'tagged#', 'comments', 'toprated', 'mostviewed' or 'all'
     *  @param      $pid    the photo id
     *  @return     nothing
     */
    function showPhoto ($uid, $cid, $pid)
    {
        $locale = new Locale();

        // Select all photos for the category/group you are trying to view

        //--------------------------------------------------------------------
        // Latest Comments View
        //--------------------------------------------------------------------
        if (strpos($cid, 'comments') !== false) {
            $special_breadcrumbs = '<a href="?uid=0&amp;cid=comments">'._('Latest Comments').'</a>';
            $urlcid = $cid;
            $cid = substr($cid, 8);
            $sql = "SELECT DISTINCT `filename` "
                 . "FROM ("
                    . "SELECT p.`filename` "
                    . "FROM `fcms_gallery_comments` AS c, `fcms_gallery_photos` AS p "
                    . "WHERE c.`photo` = p.`id` ORDER BY c.`date` DESC"
                 . ") as z";
        
        //--------------------------------------------------------------------
        // Top Rated View
        //--------------------------------------------------------------------
        } elseif (strpos($cid, 'toprated') !== false) {
            $special_breadcrumbs = '<a href="?uid=0&amp;cid=toprated">'._('Top Rated').'</a>';
            $urlcid = $cid;
            $cid = substr($cid, 8);
            $sql = "SELECT `filename` FROM `fcms_gallery_photos` "
                 . "WHERE `votes` > 0 ORDER BY `rating`/`votes` DESC";
        
        //--------------------------------------------------------------------
        // Most Viewed View
        //--------------------------------------------------------------------
        } elseif (strpos($cid, 'mostviewed') !== false) {
            $special_breadcrumbs = "<a href=\"?uid=$uid&amp;cid=$cid\">"._('Most Viewed')."</a>";
            $urlcid = $cid;
            $cid = substr($cid, 10);
            $sql = "SELECT `filename` FROM `fcms_gallery_photos` WHERE `views` > 0";
            if ($uid > 0) {
                $sql .= " AND `user` = $uid";
            }
            $sql .= " ORDER BY `views` DESC";
        
        //--------------------------------------------------------------------
        // Tagged Photos View -- here $cid = 'tagged' plus the tagged user's id
        //--------------------------------------------------------------------
        } elseif (strpos($cid, 'tagged') !== false) {
            $urlcid = $cid;
            $cid = substr($cid, 6);
            $userName = getUserDisplayName($cid);
            $special_breadcrumbs = "<a href=\"?uid=0&amp;cid=$cid\">".sprintf(_('Photos of %s'), $userName)."</a>";
            $sql = "SELECT `filename` "
                 . "FROM `fcms_gallery_photos` AS p, `fcms_gallery_photos_tags` AS t "
                 . "WHERE t.`user` = $cid "
                 . "AND t.`photo` = p.`id` "
                 . "ORDER BY `date`";
        
        //-----------------------------------------------------------------
        // All Photos for Member
        //-----------------------------------------------------------------
        } elseif ($cid == 'all') {
            $urlcid = $cid;
            $userName = getUserDisplayName($uid);
            $special_breadcrumbs = '<a href="?uid='.$uid.'&amp;cid=all">'.sprintf(_('Photos uploaded by %s'), $userName).'</a>';
            $sql = "SELECT `filename` 
                    FROM `fcms_gallery_photos`
                    WHERE `user` = $uid
                    ORDER BY `id` DESC";
                
        //--------------------------------------------------------------------
        // Category of Photos
        //--------------------------------------------------------------------
        } elseif (preg_match('/^\d+$/', $cid)) {
            $urlcid = $cid;
            $sql = "SELECT `filename` FROM `fcms_gallery_photos` "
                 . "WHERE `category` = $cid ORDER BY `date`";
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
            $sql = "SELECT p.`user` AS uid, `filename`, `caption`, `category` AS cid, p.`date`, "
                    . "`name` AS category_name, `views`, `votes`, `rating` "
                 . "FROM `fcms_gallery_photos` AS p, `fcms_gallery_category` AS c "
                 . "WHERE p.`id` = $pid "
                 . "AND p.`category` = c.`id`";
            $this->db->query($sql) or displaySQLError(
                'Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            
            // Display the photo and other info
            if ($this->db->count_rows() > 0) {
                $r = $this->db->get_row();
                $displayname = getUserDisplayName($r['uid']);
                
                // Update View count
                $sql = "UPDATE `fcms_gallery_photos` SET `views` = `views`+1 WHERE `id` = $pid";
                $this->db->query($sql) or displaySQLError(
                    'Update View Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                
                // What type of breadcrumbs
                if (isset($special_breadcrumbs)) {
                    echo '
                <p class="breadcrumbs">'.$special_breadcrumbs.'</p>
                <small>
                    '._('From the Category:').' <a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'">'.$r['category_name'].'</a> 
                    '._('by').' 
                    <a class="u" href="../profile.php?member='.$r['uid'].'">'.$displayname.'</a>
                </small>';

                } else {
                    echo '
                <p class="breadcrumbs">
                    <a href="?uid=0">'._('Member Gallery').'</a> &gt; 
                    <a href="?uid='.$uid.'">'.$displayname.'</a> &gt; 
                    <a href="?uid='.$uid.'&amp;cid='.$cid.'">'.$r['category_name'].'</a>
                </p>';

                }
                
                // Display Next / Previous links
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
                    echo '
                <div id="photo_nav clearfix">
                    <div class="info">'.sprintf(_('Photo %d of %d'), $c, $total_photos).'</div>
                </div>';

                // Showing the first of multiple photos
                } elseif (!isset($prev_pid)) {
                    $c = $cur + 1;
                    echo '
                <div id="photo_nav" class="clearfix">
                    <div class="info">'.sprintf(_('Photo %d of %d'), $c, $total_photos).'</div>
                    <div class="prev_next clearfix">
                        <span class="previous">&nbsp;</span>
                        <a class="next" href="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$next_pid.'">'._('Next').' &gt;&gt;</a>
                    </div>
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
                    echo '
                <div id="photo_nav" class="clearfix">
                    <div class="info">'.sprintf(_('Photo %d of %d'), $c, $total_photos).'</div>
                    <div class="prev_next clearfix">
                        <a class="previous" href="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$prev_pid.'">&lt;&lt; '._('Previous').'</a>
                        <span class="next">&nbsp;</span>
                    </div>
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
                    echo '
                <div id="photo_nav" class="clearfix">
                    <div class="info">'.sprintf(_('Photo %d of %d'), $c, $total_photos).'</div>
                    <div class="prev_next clearfix">
                        <a class="previous" href="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$prev_pid.'">&lt;&lt; '._('Previous').'</a>
                        <a class="next" href="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$next_pid.'">'._('Next').' &gt;&gt;</a>
                    </div>
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
                
                // Setup photo path
                $photo_path = '';
                $caption = htmlentities($r['caption'], ENT_COMPAT, 'UTF-8');
                // Link to the full sized photo if using full sized
                $sql = "SELECT `full_size_photos` FROM `fcms_config`";
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
                $sql = "SELECT u.`id`, u.`lname` "
                     . "FROM `fcms_users` AS u, `fcms_gallery_photos_tags` AS t "
                     . "WHERE t.`photo` = $pid "
                     . "AND t.`user` = u.`id`"
                     . "ORDER BY u.`lname`";
                $this->db->query($sql) or displaySQLError(
                    'Tagged Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                if ($this->db->count_rows() > 0) {
                    while ($t = $this->db->get_row()) {
                        $tagged_mem_list .= getUserDisplayName($t['id']) . ", ";
                    }
                    $tagged_mem_list = substr($tagged_mem_list, 0, -2); // remove the extra ", "
                } else {
                    $tagged_mem_list .= "<i>"._('none')."</i>";
                }
                $date_added = $locale->fixDate(_('F j, Y g:i a'), $this->tz_offset, $r['date']);
                $size = formatSize($size);
                // Edit / Delete Photo options
                $edit_del_options = '';
                if ($this->cur_user_id == $r['uid'] || checkAccess($this->cur_user_id) < 2) {
                    $edit_del_options = '
                <div class="edit_del_photo">
                    <strong>'._('Edit Photo').'</strong> &nbsp;
                    <form action="index.php" method="post">
                        <div>
                            <input type="hidden" name="photo" id="photo" value="'.$pid.'"/>
                            <input type="hidden" name="url" id="url" value="uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$pid.'"/>
                            <input type="submit" name="editphoto" id="editphoto" value="'._('Edit').'" class="editbtn"/>&nbsp;&nbsp;
                            <input type="submit" name="deletephoto" id="deletephoto" value="'._('Delete').'" class="delbtn"/>
                        </div>
                    </form>
                </div>';
                }
                
                // Display Photo -- caption, rating and other info
                echo '
            <div id="photo">
                <p>
                    <a href="photos/member'.$r['uid'].$photo_path.$r['filename'].'"><img class="photo" 
                        src="photos/member'.$r['uid'].'/'.$r['filename'].'" alt="'.$caption.'" title="'.$caption.'"/></a>
                </p>
                <div id="caption">
                    <b>'._('Caption').':</b> '.$caption.'
                </div>
            </div>
            <div id="photo_details">
                '.$edit_del_options.'
                <div style="float:left"><strong>'._('Rating').':</strong> &nbsp;</div>
                <div style="float:left">
                    <ul class="star-rating small-star">
                        <li class="current-rating" style="width:'.$width.'%">'.sprintf(_('Currently %s/5 Starts'), $r['rating']).'</li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=1" title="'._('1 out of 5 Stars').'" class="one-star">1</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=2" title="'._('2 out of 5 Stars').'" class="two-stars">2</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=3" title="'._('3 out of 5 Stars').'" class="three-stars">3</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=4" title="'._('4 out of 5 Stars').'" class="four-stars">4</a></li>
                        <li><a href="?uid='.$r['uid'].'&amp;cid='.$r['cid'].'&amp;pid='.$pid.'&amp;vote=5" title="'._('5 out of 5 Stars').'" class="five-stars">5</a></li>
                    </ul>
                </div>
                <div style="clear:both"><br/></div>
                <div id="photo_details_sub">
                    <p><b>'._('Filename').':</b><br/>'.$r['filename'].'</p>
                    <p><b>'._('Views').':</b><br/>'.$r['views'].'</p>
                    <p><b>'._('Photo Size').':</b><br/>'.$size.'</p>
                    <p><b>'._('Dimensions').':</b><br/>'.$dimensions[0].' x '.$dimensions[1].'</p>
                    <p><b>'._('Date Added').':</b><br/>'.$date_added.'</p>
                    <p><b>'._('Members in Photo').':</b><br/>'.$tagged_mem_list.'</p>
                </div>
            </div>';
                
                // Display Comments
                if (
                    checkAccess($_SESSION['login_id']) <= 8 && 
                    checkAccess($_SESSION['login_id']) != 7 && 
                    checkAccess($_SESSION['login_id']) != 4
                ) {
                    echo '
            <div style="clear:both"></div>
            <p>&nbsp;</p>
            <h3>'._('Comments').'</h3>';
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
                            $date = $locale->fixDate(_('F j, Y g:i a'), $this->tz_offset, $row['date']);
                            $displayname = getUserDisplayName($row['user']);
                            $comment = $row['comment'];
                            if ($this->cur_user_id == $row['user'] || 
                                checkAccess($this->cur_user_id) < 2) {
                                $del_comment .= '<input type="submit" name="delcom" id="delcom" '
                                    . 'value="'._('Delete').'" class="gal_delcombtn" title="'
                                    . _('Delete this Comment') . '"/>';
                            }
                            echo '
            <div class="comment_block clearfix">
                <form class="delcom" action="?uid='.$uid.'&amp;cid='.$urlcid.'&amp;pid='.$pid.'" method="post">
                    '.$del_comment.'
                    <img class="avatar" src="avatar/'.$row['avatar'].'">
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
                    '._('Add Comment').'<br/>
                    <textarea class="frm_textarea" name="post" id="post" rows="3" cols="63"></textarea>
                    <input type="submit" name="addcom" id="addcom" value="'._('Add Comment').'" title="'._('Add Comment').'" class="gal_addcombtn"/>
                </form>
            </div>
            <p>&nbsp;</p>';
                }
            
            // Specific Photo couldn't be found
            } else {
                echo '
            <p class="error-alert">'._('The Photo you are trying to view can not be found.').'</p>';
            }

        // No photos exist for the current view/category
        // Even though we are in photo view, bump them back to the category view
        // and let the user know that this category is now empty
        } else {
            $this->displayGalleryMenu($uid, $cid);
            echo '
            <div class="info-alert">
                <h2>'._('Oops!').'</h2>
                <p>'._('The Category you are trying to view is Empty.').'</p>
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
                $sql = "SELECT 'MEMBER' AS type, u.`id` AS uid, f.`filename`, COUNT(p.`id`) as c "
                     . "FROM `fcms_gallery_category` AS cat "
                     . "LEFT JOIN `fcms_gallery_photos` AS p "
                     . "ON p.`category` = cat.`id`, `fcms_users` AS u, ("
                        . "SELECT * "
                        . "FROM `fcms_gallery_photos` "
                        . "ORDER BY `date` DESC"
                     . ") AS f "
                     . "WHERE f.`id` = p.`id` "
                     . "AND u.`id` = p.`user` "
                     . "GROUP BY p.`user`";

                // Pagination
                $from = ($page * 15) - 15;
                $sql .= " LIMIT $from, 15";
                
            //-----------------------------------------------------------------
            // Latest Comments View
            //-----------------------------------------------------------------
            } elseif ($cid == 'comments') {
                $sql = "SELECT 'COMMENTS' AS type, p.`user` AS uid, p.`category` AS cid, "
                        . "c.`date` AS heading, p.`id` AS pid, p.`filename`, c.`comment`, "
                        . "p.`caption`, c.`user` "
                     . "FROM `fcms_gallery_comments` AS c, `fcms_gallery_photos` AS p, "
                        . "`fcms_gallery_category` AS cat, `fcms_users` AS u "
                     . "WHERE c.`photo` = p.`id` "
                     . "AND p.`category` = cat.`id` "
                     . "AND c.`user` = u.`id` "
                     . "ORDER BY c.`date` DESC";

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
                $sql = "SELECT 'RATED' AS type, `user` AS uid, `filename`, `category`, "
                        . "`caption`, `id` AS pid, `rating`/`votes` AS 'r' "
                     . "FROM `fcms_gallery_photos` "
                     . "WHERE `votes` > 0 "
                     . "ORDER BY r DESC";

                // Pagination
                $from = ($page * 15) - 15;
                $sql .= " LIMIT $from, 15";
                
            //-----------------------------------------------------------------
            // Overall Most Viewed View
            //-----------------------------------------------------------------
            } elseif ($cid == 'mostviewed') {
                $sql = "SELECT 'VIEWED' AS type, `user` AS uid, `filename`, `caption`, "
                        . "`id` AS pid, `views` "
                     . "FROM `fcms_gallery_photos` "
                     . "WHERE `views` > 0 "
                     . "ORDER BY VIEWS DESC";

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
                     . "WHERE t.`user` = $cid "
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
                     . "WHERE `votes` > 0 AND `user` = $uid ";

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
                        AND `user` = $uid
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
                     . "FROM `fcms_gallery_category` AS c, `fcms_gallery_photos` AS p, "
                        . "`fcms_users` AS u "
                     . "WHERE p.`user` = u.`id` "
                     . "AND `category` = c.`id` "
                     . "AND `category` = $cid";

                // Pagination
                $from = ($page * 15) - 15;
                $sql .= " LIMIT $from, 15";
                
            //-----------------------------------------------------------------
            // All Photos for Member
            //-----------------------------------------------------------------
            } elseif ($cid == 'all') {
                $sql = "SELECT 'ALL' AS type, u.`id` AS uid, `category` AS cid, 
                            p.`id` AS pid, `caption`, c.`name` AS category, `filename` 
                        FROM `fcms_gallery_category` AS c, `fcms_gallery_photos` AS p, 
                            `fcms_users` AS u 
                        WHERE p.`user` = $uid 
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
                     . "FROM `fcms_gallery_category` AS cat "
                     . "LEFT JOIN `fcms_gallery_photos` AS p "
                     . "ON p.`category` = cat.`id`, `fcms_users` AS u, ("
                        . "SELECT * "
                        . "FROM `fcms_gallery_photos` "
                        . "ORDER BY `date` DESC"
                     . ") AS f "
                     . "WHERE f.`id` = p.`id` "
                     . "AND u.`id` = p.`user` "
                     . "AND p.`user`=$uid "
                     . "GROUP BY cat.`id` DESC";

                // Pagination
                $from = ($page * 15) - 15;
                $sql .= " LIMIT $from, 15";
            }
        // Catch all invalid $uid's
        } else {
            echo '
            <div class="info-alert">
                <h2>'._('Uh Oh!').'</h2>
                <p>'._('The category you are trying to view doesn\'t exist.').'</p>
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
            <p class="breadcrumbs">'._('Member Gallery').'</p>';
                    }
                    $displayname = getUserDisplayName($row['uid']);
                    $htmlDisplayname = htmlentities($displayname, ENT_COMPAT, 'UTF-8');
                    $cat_name = "<strong>$displayname</strong>";
                    $url = "?uid=" . $row['uid'];
                    $urlPage = "?uid=0";
                    $alt = ' alt="'.sprintf(_('View Categories for %s'), $htmlDisplayname).'"';
                    $title = ' title="'.sprintf(_('View Categories for %s'), $htmlDisplayname).'"';
                    $cat_info = "<i>" . _('Photos') . " (" . $row['c'] . ")</i>";

                //-------------------------------------------------------------
                // Comments
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'COMMENTS') {
                    if ($first) {
                        if ($page >= 0) {
                            echo '
            <p class="breadcrumbs">'._('Latest Comments').'</p>';
                        } else {
                            echo '
            <h3>'._('Latest Comments').'</h3>
            <a href="?uid=0&amp;cid=comments">('._('View All').')</a><br/>';
                        }
                    }
                    $date = $locale->fixDate(_('M. j, Y g:i a'), $this->tz_offset, $row['heading']);
                    $cat_name = "<strong>$date</strong>";
                    $url = "?uid=0&amp;cid=comments&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=0&amp;cid=comments";
                    $alt = ' alt="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $comment = $row['comment'];
                    $cat_info = "<i><b>" . getUserDisplayName($row['user']) . ":</b> $comment</i>";

                //-------------------------------------------------------------
                // Top Rated
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'RATED') {
                    if ($first) {
                        echo '
            <p class="breadcrumbs">'._('Top Rated');
                        if ($uid > 0) {
                            echo " (" . getUserDisplayName($uid) . ")";
                        }
                        echo "</p>";
                    }
                    $width = ($row['r'] / 5) * 100;
                    $url = "?uid=0&amp;cid=toprated" . $row['category'] . "&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=0&amp;cid=toprated" . $row['category'];
                    $alt = ' alt="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $cat_info = "<i><ul class=\"star-rating small-star\">"
                        . "<li class=\"current-rating\" style=\"width:$width%\">"
                        . sprintf(_('Currently %d/5 Stars.'), $row['r'])
                        . "</li><li><a href=\"$url&amp;vote=1\" title=\"" 
                        . _('1 out of 5 Stars') . "\" class=\"one-star\">1</a></li><li>"
                        . "<a href=\"$url&amp;vote=2\" title=\"" . _('2 out of 5 Stars')
                        . "\" class=\"two-stars\">2</a></li><li><a href=\"$url&amp;vote=3\" "
                        . "title=\"" . _('3 out of 5 Stars') . "\" class=\"three-stars\">3</a>"
                        . "</li><li><a href=\"$url&amp;vote=4\" title=\"" . _('4 out of 5 Stars')
                        . "\" class=\"four-stars\">4</a></li><li><a href=\"$url&amp;vote=5\" "
                        . "title=\"" . _('5 out of 5 Stars') . "\" class=\"five-stars\">5</a>"
                        . "</li></ul></i>";

                //-------------------------------------------------------------
                // Most Viewed
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'VIEWED') {
                    if ($first) {
                        echo '
            <p class="breadcrumbs">'._('Most Viewed');
                        if ($uid > 0) {
                            echo " (" . getUserDisplayName($uid) . ")";
                        }
                        echo "</p>";
                    }
                    $url = "?uid=$uid&amp;cid=mostviewed&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=$uid&amp;cid=mostviewed";
                    $alt = ' alt="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $cat_info = "<i><b>"._('Views').": </b>" . $row['views'] . "</i>";

                //-------------------------------------------------------------
                // Tagged
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'TAGGED') {
                    if ($first) {
                        $userName = getUserDisplayName($row['user']);
                        echo '
            <p class="breadcrumbs">'.sprintf(_('Photos of %s'), $userName).'</p>';
                    }
                    $url = "?uid=0&amp;cid=tagged" . $row['user'] . "&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=0&amp;cid=tagged" . $row['user'];
                    $alt = ' alt="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';

                //-------------------------------------------------------------
                // ALL
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'ALL') {
                    if ($first) {
                        $userName = getUserDisplayName($row['uid']);
                        echo '
            <p class="breadcrumbs">'.sprintf(_('Photos uploaded by %s'), $userName).'</p>';
                    }
                    $url = "?uid=" . $row['uid'] . "&amp;cid=all&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=" . $row['uid'] . "&amp;cid=all";
                    $alt = ' alt="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';

                //-------------------------------------------------------------
                // Photos
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'PHOTOS') {
                    if ($first) {
                        echo '
            <p class="breadcrumbs">
                <a href="?uid=0">'._('Member Gallery').'</a> &gt; 
                <a href="?uid='.$uid.'">'.getUserDisplayName($row['uid']).'</a> &gt; 
                '.$row['category'].'
            </p>';
                    }
                    $url = "?uid=" . $row['uid'] . "&amp;cid=" . $row['cid'] . "&amp;pid=" . $row['pid'];
                    $urlPage = "?uid=" . $row['uid'] . "&amp;cid=" . $row['cid'];
                    $alt = ' alt="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';

                //-------------------------------------------------------------
                // Categories
                //-------------------------------------------------------------
                } elseif ($row['type'] == 'CATEGORIES') {
                    if ($first) {
                        echo '
            <p class="breadcrumbs">
                <a href="?uid=0">'._('Member Gallery').'</a> &gt; '.getUserDisplayName($row['uid']).'
            </p>';
                    }
                    $htmlCat = htmlentities($row['category'], ENT_COMPAT, 'UTF-8');
                    $cat_name = "<strong>" . $row['category'] . "</strong>";
                    $url = "?uid=" . $row['uid'] . "&amp;cid=" . $row['cid'];
                    $urlPage = "?uid=" . $row['uid'];
                    $alt = ' alt="'.sprintf(_('View Photos in %s'), $htmlCat).'"';
                    $title = ' title="'.sprintf(_('View Photos in %s'), $htmlCat).'"';
                    $cat_info = "<i>" . _('Photos') . " (" . $row['c'] . ")</i>";
                }

                if ($type == 'PHOTOS' || $type == 'TAGGED' || $type == 'ALL') {
                    $category_rows[] = '
                    <a href="index.php'.$url.'">
                        <img class="photo" src="photos/member'.$row['uid'].'/tb_'.$row['filename'].'" '.$alt.' '.$title.'/>
                    </a>';
                } else {
                    $category_rows[] = '
                    <a class="category-link" href="index.php'.$url.'">
                        <img src="photos/member'.$row['uid'].'/tb_'.$row['filename'].'" '.$alt.' '.$title.'/>
                        <span>
                            '.$cat_name.'
                            '.$cat_info.'
                        </span>
                    </a>';
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
            </ul>';

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
                <h2>'._('Oops!').'</h2>
                <p>'._('The Category you are trying to view is Empty.').'</p>
            </div>';
        }
    }

    /*
     *  displayUploadForm
     *
     *  Displays the form for uploading photos to the photo gallery.
     *
     *  @param      $last_cat   the last category photos where uploaded to
     *  @return     nothing
     */
    function displayUploadForm ($last_cat)
    {
        // TODO -- move this to a function, this is used more than once
        // Setup the list of categories for the select box
        $sql = "SELECT `id`, `name` FROM `fcms_gallery_category` 
                WHERE `user` = " . $this->cur_user_id . "
                ORDER BY `id` DESC";
        $this->db->query($sql) or displaySQLError(
            'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            $cat_options = '';
            while($row = $this->db->get_row()) {
                $cat_options .= "<option";
                if ($last_cat == $row['id']) {
                    $cat_options .= ' selected="selected"';
                }
                $cat_options .= ' value="' . $row['id'] . '">' . $row['name'] . '</option>';
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
                <legend><span>'._('Upload Photos').'</span></legend>
                <p class="alignright">
                    <a class="help" href="../help.php#gallery-howworks">'._('Help').'</a>
                </p>
                <form enctype="multipart/form-data" action="?action=upload" method="post">
                    <div class="field-row clearfix">
                        <div class="field-label"><label><b>'._('Category').'</b></label></div>
                        <div class="field-widget">
                            <select name="category">
                                '.$cat_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label><b>'._('Photo').'</b></label></div>
                        <div class="field-widget">
                            <input name="photo_filename[]" type="file" size="50"/>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label><b>'._('Caption').'</b></label></div>
                        <div class="field-widget">
                            <input class="frm_text" type="text" name="photo_caption[]" size="50"/>
                        </div>
                    </div>
                    <div id="rotate-options">
                        <p class="center">
                            '._('Rotate Left').': <input type="radio" name="rotate[]" value="left"/>&nbsp;&nbsp; 
                            '._('Rotate Right').': <input type="radio" name="rotate[]" value="right"/>
                        </p>
                    </div>
                    <div id="tag-options">
                        <p class="center">
                            '._('Members in this Photo').':
                            <div class="multi-checkbox" style="margin: 0 auto;">
                                '.$tag_checkboxes.'
                            </div>
                        </p>
                    </div>
                    <input class="sub1" type="submit" id="addphoto" name="addphoto" value="'._('Submit').'"/>
                </form>';

        // User doesn't have a category to upload photos into
        } else {
            echo '
            <p class="info-alert">'._('You must create a Category first.').'</p>';
            $this->displayAddCatForm();
        }
    }

    /*
     *  displayJavaUploadForm
     *
     *  Displays the form for uploading photos using the JumpLoader
     *  java applet.
     *
     *  @param      $last_cat   the last category photos where uploaded to
     *  @return     nothing
     */
    function displayJavaUploadForm ($last_cat)
    {
        $sql = "SELECT `id`, `name` FROM `fcms_gallery_category` 
                WHERE `user` = " . $this->cur_user_id . "
                ORDER BY `id` DESC";
        $result = mysql_query($sql) or displaySQLError(
            'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $cat_options = '';
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                $cat_options .= "<option";
                if ($last_cat == $row['id']) {
                    $cat_options .= ' selected="selected"';
                }
                $cat_options .= ' value="' . $row['id'] . '">' . $row['name'] . '</option>';
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
                        '._('JavaScript must be enabled in order for you to use the Chat Room. However, it seems JavaScript is either disabled or not supported by your browser.').'<br/>
                        <span>
                            '._('Either enable JavaScript by changing your browser options.').'<br/>
                            '._('or').'<br/>
                            '._('Enable the Basic Upload option by changing Your Settings.').'
                        </span>
                    </p>
                    </div>
                </noscript>
                <form method="post" name="uploadForm">
                    <div class="field-row clearfix">
                        <div class="field-label"><label><b>'._('Category').'</b></label></div>
                        <div class="field-widget">
                            <select id="category" name="category" onchange="sendCategory(this.form.category)">
                                '.$cat_options.'
                            </select>
                        </div>
                    </div>
                </form>
                <applet name="jumpLoaderApplet"
                    code="jmaster.jumploader.app.JumpLoaderApplet.class"
                    archive="../inc/jumploader_z.jar"
                    width="540"
                    height="500"
                    mayscript>
                    <param name="uc_sendImageMetadata" value="true"/>
                    <param name="uc_uploadUrl" value="upload.php"/>
                    <param name="vc_useThumbs" value="true"/>
                    <param name="uc_uploadScaledImagesNoZip" value="true"/>
                    <param name="uc_uploadScaledImages" value="true"/>
                    <param name="uc_scaledInstanceNames" value="small,medium"/>
                    <param name="uc_scaledInstanceDimensions" value="100x100,500x1000"/>
                    <param name="uc_scaledInstanceQualityFactors" value="900"/>
                    <param name="uc_uploadFormName" value="uploadForm"/>
                    <param name="vc_lookAndFeel" value="system"/>
                    <param name="vc_uploadViewStartUploadButtonText" value="Start upload"/>
                    <param name="vc_uploadViewFilesSummaryBarVisible" value="false"/>
                    <param name="ac_fireUploaderStatusChanged" value="true"/> 
                </applet>
                <script language="javascript">
                sendCategory($("category"));
                function sendCategory(select) {
                    var uploader = document.jumpLoaderApplet.getUploader();
                    var attrSet = uploader.getAttributeSet();
                    var attr = attrSet.createStringAttribute("category", select.value);
                    attr.setSendToServer(true);
                }
                function uploaderStatusChanged( uploader ) {
                    if (uploader.isReady() && uploader.getFileCountByStatus(3) == 0) { 
                        window.location.href = "index.php?action=advanced";
                    }
                }
                </script>';
        }
    }

    /*
     *  displayEditPhotoForm
     *
     *  Displays a form for editing a photo.
     *
     *  @param      $photo      the photo id of the photo you want to edit
     *  @param      $url        optional -- the url to go back to after form is submitted
     *  @return     nothing
     */
    function displayEditPhotoForm ($photo, $url = '')
    {
        $sql = "SELECT p.`user`, `filename`, `caption`, `name` "
             . "FROM `fcms_gallery_photos` AS p, `fcms_gallery_category` AS c "
             . "WHERE p.`id` = $photo "
             . "AND p.`category` = c.`id`";
        $this->db->query($sql) or displaySQLError(
            'Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            $row = $this->db->get_row();
            $photo_user = $row['user'];
            $filename = $row['filename'];
            $caption = $row['caption'];
            $caption_html = htmlentities($caption, ENT_COMPAT, 'UTF-8');
            $category = $row['name'];
            
            // Setup the list of categories for the select box
            $sql = "SELECT `id`, `name` FROM `fcms_gallery_category` WHERE `user` = $photo_user";
            $this->db2->query($sql) or displaySQLError(
                'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $cat_options = '';
            while($row = $this->db2->get_row()) {
                $cat_options .= '<option value="' . $row[0] . '"';
                if ($category == $row[1]) {
                    $cat_options .= ' selected="selected"';
                }
                $cat_options .= '>' . $row[1] . '</option>';
            }
            
            // Setup the list of active members for the select box
            $sql = "SELECT * FROM `fcms_gallery_photos_tags` WHERE `photo` = $photo";
            $this->db2->query($sql) or displaySQLError(
                'Find Tagged Users Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            while ($r = $this->db2->get_row()) {
                $users_in_photo[$r['id']] = $r['user'];
            }
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
                    . $key . '" name="tagged[]"  value="' . $key . '"';
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
                    <legend><span>'._('Edit Photo').'</span></legend>
                    <img class="thumbnail" src="photos/member'.$photo_user.'/tb_'.$filename.'"/>
                    <form enctype="multipart/form-data" action="index.php?'.$url.'" method="post">
                        <div class="field-row clearfix">
                            <div class="field-label"><label><b>'._('Change Category').'</b></label></div>
                            <div class="field-widget">
                                <select class="frm_sel" name="category">
                                    '.$cat_options.'
                                </select>
                            </div>
                        </div>
                        <div class="field-row clearfix">
                            <div class="field-label"><label><b>'._('Caption').'</b></label></div>
                            <div class="field-widget">
                                <input class="frm_text" type="text" name="photo_caption" size="50" value="'.$caption_html.'"/>
                            </div>
                        </div>
                        <div class="field-row clearfix">
                            <div class="field-label"><label><b>'._('Who is in this Photo?').'</b></label></div>
                            <div class="field-widget">
                                <div class="multi-checkbox">
                                    '.$tag_checkboxes.'
                                </div>
                            </div>
                        </div>
                        '.$prev_tagged.'
                        <p>
                            <input type="hidden" name="photo_id" id="photo_id" value="'.$photo.'"/>
                            <input type="submit" name="add_editphoto" value="'._('Edit').'"/> 
                            '._('or').' <a href="index.php?'.$url.'">'._('Cancel').'</a>
                        </p>
                    </form>
                </fieldset>';
        } else {
            echo '
            <p class="error-alert">'._('Could not edit Photo.  Photo ID does not exist.').'</p>';
        }
    }

    /*
     *  displayAdvancedUploadEditForm
     *
     *  Displays a form for editing photos that were uploaded using the advanced uploader.
     *
     *  @return     nothing
     */
    function displayAdvancedUploadEditForm ()
    {
        // Setup the list of active members for the select box
        $sql = "SELECT `id` FROM `fcms_users` WHERE `activated` > 0";
        $this->db2->query($sql) or displaySQLError(
            'Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = $this->db2->get_row()) {
            $displayNameArr[$r['id']] = getUserDisplayName($r['id'], 2);
        }
        asort($displayNameArr);

        // Display the form
        echo '
                <fieldset>
                    <legend>'._('Edit Photos').'</legend>
                    <form action="index.php?action=advanced" method="post">';

        $i=0;
        foreach ($_SESSION['photos'] AS $photo) {
            echo '
                        <img style="float:right" src="photos/member'.$_SESSION['login_id'].'/tb_'.$photo['filename'].'"/>
                        <p>
                            '._('Caption').'<br/>
                            <input type="text" class="frm_text" name="caption[]" width="50"/>
                            <input type="hidden" name="id[]" value="'.$photo['id'].'"/>
                            <input type="hidden" name="category[]" value="'.$photo['category'].'"/>
                        </p>
                        <p>
                            '._('Who is in this Photo?').' 
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
                            <input class="sub1" type="submit" name="submit_advanced_edit" id="submit_advanced_edit" value="'._('Save').'"/> 
                            '._('or').' 
                            <a href="index.php?action=upload">'._('Cancel').'</a>
                        </p>
                    </form>
                </fieldset>';
    }

    /*
     *  uploadPhoto
     *
     *  Uploads a photo to the /gallery/photos/memberX/ directory where x is the user id.
     *  Adds photo info to the db, creates a thumbnail, resizes middle sized photo 
     *  and rotates the photo if desired.
     *
     *  @param      $category           array of categories ids
     *  @param      $photos_uploaded    array of temp photos to be uploaded
     *  @param      $photos_caption     array of captions
     *  @param      $rotateoptions      array of rotate options
     *  @param      $stripcap           boolean
     *  @param      $thumb_max_width    defaults to 100px
     *  @param      $thumb_max_height   defaults to 100px
     *  @param      $main_max_width     defaults to 500px
     *  @param      $main_max_height    defaults to 1000px -- doesn't effect the layout
     *  @return     the photo id
     */
    function uploadPhoto (
        $category, $photos_uploaded, $photos_caption, $rotateoptions, $stripcap, 
        $thumb_max_width = 100, $thumb_max_height = 100, $main_max_width = 500, 
        $main_max_height = 10000
    )
    {
        $known_photo_types = array(
            'image/pjpeg'   => 'jpg', 
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
        
        // Loop through the array of photos and upload each one
        $i = 0;
        while ($i < count($photos_uploaded['name'])) {
            if ($photos_uploaded['size'][$i] > 0) {
                
                // Catch non supported file types
                if (!array_key_exists($photos_uploaded['type'][$i], $known_photo_types)) {
                    echo '
            <p class="error-alert">'._('File is not a valid Photo, must be of type (.JPG, .JPEG, .GIF, .BMP or .PNG).').'</p><br />';
                } else {
                    
                    // Do we need to strip slashes on captions?
                    if ($stripcap == 'true') {
                        $photos_caption[$i] = stripslashes($photos_caption[$i]);
                    }
                    
                    // Create a new photo record in DB
                    $sql = "INSERT INTO `fcms_gallery_photos`"
                            . "(`date`, `caption`, `category`, `user`)"
                         . "VALUES("
                            . "NOW(), "
                            . "'" . addslashes($photos_caption[$i]) . "', "
                            . "'" . addslashes($category) . "', "
                            . $this->cur_user_id
                         . ")";
                    $this->db->query($sql) or displaySQLError(
                        'Add Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                    
                    // Update the filename and update the photo record in DB
                    // We an insert above and update below so we can make sure that the filename of
                    // the photo is the same as the photo id
                    $new_id = mysql_insert_id();
                    $filetype = $photos_uploaded['type'][$i];
                    $extention = $known_photo_types[$filetype];
                    $filename = $new_id . "." . $extention;
                    $sql = "UPDATE `fcms_gallery_photos` "
                         . "SET `filename`='" . addslashes($filename) . "' "
                         . "WHERE id = " . addslashes($new_id);
                    $this->db->query($sql) or displaySQLError(
                        'Update Photo Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                    
                    // Create new member directory if needed
                    if (!file_exists("photos/member" . $this->cur_user_id)) {
                        mkdir("photos/member" . $this->cur_user_id);
                    }
                    
                    // Copy the tmp file to the member's photo dir
                    copy(
                        $photos_uploaded['tmp_name'][$i], 
                        "photos/member" . $this->cur_user_id . "/" . $filename
                    );
                    // If using full sized photos, make another copy of the file, because the first
                    // copy is going to be resized.
                    if ($r['full_size_photos'] == '1') {
                        copy(
                            $photos_uploaded['tmp_name'][$i], 
                            "photos/member" . $this->cur_user_id . "/full_" . $filename
                        );
                    }
                    
                    // Get image sizes
                    $size = GetImageSize("photos/member" . $this->cur_user_id . "/" . $filename);
                    
                    // Set Thumbnail Size -- make it proportional
                    $thumbnail = $this->getResizeSize(
                        $size[0], 
                        $size[1], 
                        $thumb_max_width, 
                        $thumb_max_height
                    );
                    $thumbnail_width = $thumbnail[0];
                    $thumbnail_height = $thumbnail[1];
                    
                    // Set Main Photo Size -- make it proportional
                    $main = $this->getResizeSize(
                        $size[0], 
                        $size[1], 
                        $main_max_width, 
                        $main_max_height
                    );
                    $main_width = $main[0];
                    $main_height = $main[1];
                    
                    // Read in the photo info for resizing
                    $function_suffix = $gd_function_suffix[$filetype];
                    $function_to_read = "ImageCreateFrom" . $function_suffix;
                    // Treat Bitmaps as Jpeg
                    if ($function_suffix == 'BMP') {
                        $function_to_write = "ImageJPEG";
                    } else {
                        $function_to_write = "Image" . $function_suffix;
                    }
                    $source_handle = $function_to_read(
                        "photos/member" . $this->cur_user_id . "/" . $filename
                    );
                    
                    if($source_handle) {
                        // Create a new resized copy of the Thumbnail
                        $thumb_destination_handle = ImageCreateTrueColor(
                            $thumbnail_width, $thumbnail_height
                        );
                        ImageCopyResampled(
                            $thumb_destination_handle, 
                            $source_handle, 
                            0, 0, 0, 0, 
                            $thumbnail_width, 
                            $thumbnail_height, 
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
                            "photos/member" . $this->cur_user_id . "/" . $filename
                        );
                    }
                    $function_to_write(
                        $thumb_destination_handle, 
                        "photos/member" . $this->cur_user_id . "/tb_" . $filename
                    );
                    $function_to_write(
                        $main_destination_handle, 
                        "photos/member" . $this->cur_user_id . "/" . $filename
                    );
                    
                    // File Rotation
                    if ($rotateoptions[$i] == 'left' || $rotateoptions[$i] =='right') {
                        if ($rotateoptions[$i] == 'left') {
                            $rotate_thumb = imagerotate($thumb_destination_handle, 90, 0);
                            $rotate_main = imagerotate($main_destination_handle, 90, 0);
                            if ($r['full_size_photos'] == '1') {
                                $rotate_full = imagerotate($full_destination_handle, 90, 0);
                            }
                        }
                        if ($rotateoptions[$i] == 'right') {
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
                                    "photos/member" . $this->cur_user_id . "/tb_" . $filename
                                );
                                imagejpeg(
                                    $rotate_main, 
                                    "photos/member" . $this->cur_user_id . "/" . $filename
                                );
                                if ($r['full_size_photos'] == '1') {
                                    imagejpeg(
                                        $rotate_full, 
                                        "photos/member" . $this->cur_user_id . "/full_" . $filename
                                    );
                                }
                                break;
                            case 'GIF':
                                imagegif(
                                    $rotate_thumb, 
                                    "photos/member" . $this->cur_user_id . "/tb_" . $filename
                                );
                                imagegif(
                                    $rotate_main, 
                                    "photos/member" . $this->cur_user_id . "/" . $filename
                                );
                                if ($r['full_size_photos'] == '1') {
                                    imagegif(
                                        $rotate_full, 
                                        "photos/member" . $this->cur_user_id . "/full_" . $filename
                                    );
                                }
                                break;
                            case 'WBMP':
                                imagewbmp(
                                    $rotate_thumb, 
                                    "photos/member" . $this->cur_user_id . "/tb_" . $filename
                                );
                                imagewbmp(
                                    $rotate_main, 
                                    "photos/member" . $this->cur_user_id . "/" . $filename
                                );
                                if ($r['full_size_photos'] == '1') {
                                    imagewbmp(
                                        $rotate_full, 
                                        "photos/member" . $this->cur_user_id . "/full_" . $filename
                                    );
                                }
                                break;
                            case 'PNG':
                                imagepng(
                                    $rotate_thumb, 
                                    "photos/member" . $this->cur_user_id . "/tb_" . $filename
                                );
                                imagepng(
                                    $rotate_main, 
                                    "photos/member" . $this->cur_user_id . "/" . $filename
                                );
                                if ($r['full_size_photos'] == '1') {
                                    imagepng(
                                        $rotate_full, 
                                        "photos/member" . $this->cur_user_id . "/full_" . $filename
                                    );
                                }
                                break;
                            default:
                                imagejpg(
                                    $rotate_thumb, 
                                    "photos/member" . $this->cur_user_id . "/tb_" . $filename
                                );
                                imagejpg(
                                    $rotate_main, 
                                    "photos/member" . $this->cur_user_id . "/" . $filename
                                );
                                if ($r['full_size_photos'] == '1') {
                                    imagejpg(
                                        $rotate_full, 
                                        "photos/member" . $this->cur_user_id . "/full_" . $filename
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
                <b>'._('The following photo was added successfully.').'</b><br/><br/>
                &nbsp;&nbsp;&nbsp;<img src="photos/member'.$this->cur_user_id.'/tb_'.$filename.'" alt="'.$photos_caption[$i].'"/>
            </p>';
                }
            }
            $i++;
        }
        if (isset($new_id)) {
            return $new_id;
        }
    }
    
    /*
     *  getResizeSize
     *
     *  Given a photo's width/height, and the maximum resized width/height, it will calculate 
     *  the width/height while not distorting.
     *
     *  For example, a 800x600 photo with a max size of 500x500 will return 500x375
     *
     *  @param      $orig_width     the original width of the photo
     *  @param      $orig_height    the original height of the photo
     *  @param      $max_width      the maximum width for the new photo size
     *  @param      $max_height     the maximum height for the new photo size
     *  @return     an array of the new width/height
     */
    function getResizeSize ($orig_width, $orig_height, $max_width, $max_height)
    {
        if ($orig_width > $orig_height) {
            // Check width
            if ($orig_width > $max_width) {
                $height = (int)($max_width * $orig_height / $orig_width);
                return array($max_width, $height);
            // No need to resize if it's smaller than max
            } else {
                return array($orig_width, $orig_height);
            }
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

    function displayAddCatForm ()
    {
        $cat_list = '';
        
        // Setup the list of categories for edit/delete
        $sql = "SELECT * FROM fcms_gallery_category WHERE user=" . $this->cur_user_id;
        $this->db->query($sql) or displaySQLError(
            'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            while ($row = $this->db->get_row()) {
                $name = htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
                $cat_list .= '<li>
                    <form class="frm_line" action="index.php?action=category" method="post">
                        <input class="frm_text" type="text" name="cat_name" id="cat_name" size="60" value="'.$name.'"/>
                        <input type="hidden" name="cid" id="cid" value="'.$row['id'].'"/> &nbsp;
                        <input type="submit" name="editcat" class="editbtn" value="'._('Edit').'" title="'._('Edit Category').'"/> &nbsp;
                        <input type="submit" name="delcat" class="delbtn" value="'._('Delete').'" title="'._('Delete Category').'"/>
                    </form>
                </li>';
            }
        } else {
            $cat_list .= "<li><i>"._('No Categories created yet.')."</i></li>";
        }
        
        // Display the form
        echo '
            <fieldset>
                <legend><span>'._('Create Category').'</span></legend>
                <form action="index.php?action=category" method="post">
                    <div class="field-row clearfix">
                        <div class="field-label"><label><b>'._('Category Name').'</b></label></div>
                        <div class="field-widget">
                            <input class="frm_text" type="text" size="60" name="cat_name" id="cat_name"/>
                        </div>
                    </div>
                    <p>
                        <input class="sub1" type="submit" name="newcat" id="newcat" value="'._('Add').'"/>
                    </p>
                </form>
            </fieldset>
            <fieldset>
                <legend><span>'._('Edit Category').'</span></legend>
                <ul class="gallery_cat">
                    '.$cat_list.'
                </ul>
            </fieldset>';
    }

    function displayWhatsNewGallery ()
    {
        $locale = new Locale();
        $today = date('Y-m-d');
        $tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
        echo '
            <h3>'._('Photo Gallery').'</h3>';
        $sql = "SELECT DISTINCT p.user, name AS category, p.category AS cid, 
                    DAYOFYEAR(`date`) AS d, COUNT(*) AS c 
                FROM fcms_gallery_photos AS p, fcms_users AS u, fcms_gallery_category AS c 
                WHERE p.user = u.id 
                    AND p.category = c.id 
                    AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 
                GROUP BY user, category, d 
                ORDER BY `date` DESC LIMIT 0 , 5";
        $this->db->query($sql) or displaySQLError(
            'Last 5 New Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo '
            <ul>';
            while ($row = $this->db->get_row()) {
                $displayname = getUserDisplayName($row['user']);
                $category = $row['category'];
                $full_category = htmlentities($category, ENT_COMPAT, 'UTF-8');
                if (strlen($category) > 20) {
                    $category = substr($category, 0, 17) . "...";
                }
                $sql = "SELECT `date` FROM fcms_gallery_photos AS p, fcms_gallery_category AS c "
                     . "WHERE p.user = " . $row['user'] . " "
                     . "AND c.id = p.category "
                     . "AND c.name = '" . addslashes($full_category) . "' "
                     . "AND DAYOFYEAR(p.`date`) = " . $row['d'] . " "
                     . "ORDER BY `date` DESC LIMIT 1";
                $result = mysql_query($sql) or displaySQLError(
                    'Date Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                $found = mysql_fetch_array($result);
                $date = $locale->fixDate(_('M. j, Y g:i a'), $this->tz_offset, $found['date']);
                if (
                    strtotime($found['date']) >= strtotime($today) && 
                    strtotime($found['date']) < strtotime($tomorrow)
                ) {
                    $full_date = _('Today');
                    $d = ' class="today"';
                } else {
                    $full_date = $date;
                    $d = '';
                }
                echo '
                    <li>
                        <div'.$d.'>'.$full_date.'</div>
                        <a href="gallery/index.php?uid='.$row['user'].'&amp;cid='.$row['cid'].'" title="'.$full_category.'">'.$category.'</a>
                        ('.sprintf(_('%d new photos'), $row['c']).') - 
                        <a class="u" href="profile.php?member='.$row['user'].'">'.$displayname.'</a>
                    </li>';
            }
            echo '
                </ul>';
        } else {
            echo '
                <ul><li><i>'._('nothing new last 30 days').'</i></li></ul>';
        }
    }

} ?>
