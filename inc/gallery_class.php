<?php
include_once('util_inc.php');
include_once('language.php');

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
    }

    function displayGalleryMenu ($uid = '', $cid = '')
    {
        global $LANG;
        $home = $member = $rated = $viewed = '';
        if ($uid == '') {
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
        echo <<<HTML
            <div id="sections_menu" class="clearfix">
                <ul class="gal_menu clearfix">
                    <li><a class="{$home}" href="index.php">{$LANG['gallery_home']}</a></li>
                    <li><a class="{$member}" href="?uid=0">{$LANG['member_gal']}</a></li>
                    <li><a class="{$rated}" href="?uid={$uid}&amp;cid=toprated">{$LANG['top_rated']}</a></li>
                    <li><a class="{$viewed}" href="?uid={$uid}&amp;cid=mostviewed">{$LANG['most_viewed']}</a></li>
                </ul>

HTML;
        if (checkAccess($this->cur_user_id) <= 3 || checkAccess($this->cur_user_id) == 8 || checkAccess($this->cur_user_id) == 5) {
            echo <<<HTML
                <ul class="gal_sub_menu clearfix">
                    <li><b>{$LANG['actions']}: </b></li>
                    <li><a class="upload" href="?action=upload">{$LANG['upload_photos']}</a></li>
                    <li><a class="create_edit" href="?action=category">{$LANG['create_edit_cat']}</a></li>
                </ul>

HTML;
        }
        echo "</div>\n";
    }

    function displaySideMenu ()
    {
        global $LANG;
        echo <<<HTML
            <div class="gal_sidemenu">
                <b>{$LANG['link_gallery']}</b><br/>
                <b>{$LANG['viewing_options']}</b><br/><br/>
                <a href="?uid={$this->cur_user_id}">{$LANG['view_my_photos']}</a><br/>
                <a href="?uid=0&amp;cid={$this->cur_user_id}">{$LANG['view_photos_me']}</a><br/>
                <form action="index.php" method="get">
                    {$LANG['view_photos_of']}<br/>
                    <input type="hidden" name="uid" value="0"/>
                    <select name="cid">
HTML;
        $sql = "SELECT `id` FROM `fcms_users` WHERE `activated` > 0 AND `id` != " . $this->cur_user_id;
        $this->db->query($sql) or displaySQLError(
            'Members Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) { 
            while ($row = $this->db->get_row()) {
                $displayNameArr[$row['id']] = getUserDisplayName($row['id']);
            }
            asort($displayNameArr);
            foreach ($displayNameArr as $key => $value) {
                echo "<option value=\"$key\">$value</option>";
            }
        }
        echo "\n";
        echo <<<HTML
                    </select>
                    <input type="submit" value="{$LANG['view']}"/>
                </form>
            </div>

HTML;
    }

    function displayLatestCategories()
    {
        global $LANG;
        $sql = "SELECT p.`id`, p.`date`, p.`filename`, c.`name`, p.`user`, p.`category` "
             . "FROM `fcms_gallery_photos` AS p, `fcms_gallery_category` AS c "
             . "WHERE p.`category` = c.`id` "
             . "GROUP BY `category` "
             . "ORDER BY `date` DESC LIMIT 4";
        $this->db->query($sql) or displaySQLError(
            'Latest Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($row = $this->db->get_row()) {
            $monthName = date('M', strtotime($row['date']));
            $date = date('. j, Y', strtotime($row['date']));
            $cat_array[] = "<div class=\"cat_name\">" . $row['name'] . "</div>"
                . "<a href=\"?uid=" . $row['user'] . "&amp;cid=" . $row['category']
                . "&amp;pid=" . $row['id'] . "\"><img class=\"photo\" src=\"photos/member"
                . $row['user'] . "/tb_" . $row['filename'] . "\" alt=\"\"/></a>"
                . "<div class=\"cat_info\">" . getLangMonthName($monthName) . "$date</div>";
        }
        if (!empty($cat_array)) {
            echo <<<HTML
            <h3>{$LANG['latest_cat']}</h3>
            <div class="gal_row clearfix">
HTML;
            foreach ($cat_array as $cat_link) {
                echo "<div class=\"cat\">$cat_link</div>";
            }
            echo "</div>\n";
        } else {
            echo <<<HTML
            <div class="info-alert">
                <h2>{$LANG['info_gal_empty1']}</h2>
                <p><i>{$LANG['info_gal_empty2']}</i></p>
                <p>
                    <b>{$LANG['info_gal_empty3']}</b><br/>
                    {$LANG['info_gal_empty4']} 
                    <a href="?action=category">{$LANG['create_edit_cat']}</a> 
                    {$LANG['info_gal_empty5']} 
                    <a href="?action=upload">{$LANG['upload_photos']}</a>.
                </p>
            </div>

HTML;
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
     * 
     *  @param      $uid    the user's id or 0 if displaying view for all users
     *  @param      $cid    the category id, 'tagged#', 'comments', 'toprated' or 'mostviewed'
     *  @param      $pid    the photo id
     *  @return     nothing
     */
    function showPhoto ($uid, $cid, $pid)
    {
        global $LANG;
        
        // Select all photos for the category/group you are trying to view
        // Latest Comments View
        if (strpos($cid, 'comments') !== false) {
            $special_breadcrumbs = "<a href=\"?uid=0&amp;cid=comments\">" 
                . $LANG['latest_comments'] . "</a>";
            $urlcid = $cid;
            $cid = substr($cid, 8);
            // TODO
            // Rewrite this so it doesn't use subqueries
            $sql = "SELECT DISTINCT `filename` "
                 . "FROM ("
                    . "SELECT p.`filename` "
                    . "FROM `fcms_gallery_comments` AS c, `fcms_gallery_photos` AS p "
                    . "WHERE c.`photo` = p.`id` ORDER BY c.`date` DESC"
                 . ") as z";
        
        // Top Rated View
        } elseif (strpos($cid, 'toprated') !== false) {
            $special_breadcrumbs = "<a href=\"?uid=0&amp;cid=toprated\">" 
                . $LANG['top_rated'] . "</a>";
            $urlcid = $cid;
            $cid = substr($cid, 8);
            $sql = "SELECT `filename` FROM `fcms_gallery_photos` "
                 . "WHERE `votes` > 0 ORDER BY `rating`/`votes` DESC";
        
        // Most Viewed View
        } elseif (strpos($cid, 'mostviewed') !== false) {
            $special_breadcrumbs = "<a href=\"?uid=$uid&amp;cid=$cid\">" 
                . $LANG['most_viewed'] . "</a>";
            $urlcid = $cid;
            $cid = substr($cid, 10);
            $sql = "SELECT `filename` FROM `fcms_gallery_photos` WHERE `views` > 0";
            if ($uid > 0) {
                $sql .= " AND `user` = $uid";
            }
            $sql .= " ORDER BY `views` DESC";
        
        // Tagged Photos View -- here $cid = 'tagged' plus the tagged user's id
        } elseif (strpos($cid, 'tagged') !== false) {
            $urlcid = $cid;
            $cid = substr($cid, 6);
            $special_breadcrumbs = "<a href=\"?uid=0&amp;cid=$cid\">" . $LANG['photos_of'] 
                . " " . getUserDisplayName($cid) . "</a>";
            $sql = "SELECT `filename` "
                 . "FROM `fcms_gallery_photos` AS p, `fcms_gallery_photos_tags` AS t "
                 . "WHERE t.`user` = $cid "
                 . "AND t.`photo` = p.`id` "
                 . "ORDER BY `date`";
        
        // Category of Photos
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
                    echo <<<HTML
                <p class="breadcrumbs">{$special_breadcrumbs}</p>
                <small>
                    {$LANG['from_the_cat']}: <a href="?uid={$r['uid']}&amp;cid={$r['cid']}">{$r['category_name']}</a> 
                    {$LANG['by']} 
                    <a class="u" href="../profile.php?member={$r['uid']}">{$displayname}</a>
                </small>
HTML;
                } else {
                    echo <<<HTML
                <p class="breadcrumbs">
                    <a href="?uid=0">{$LANG['member_gal']}</a> &gt; 
                    <a href="?uid={$uid}">{$displayname}</a> &gt; 
                    <a href="?uid={$uid}&amp;cid={$cid}">{$r['category_name']}</a>
                </p>
HTML;
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
                    echo <<<HTML
                <div id="photo_nav clearfix">
                    <div class="info">{$LANG['photo']} {$c} {$LANG['of']} {$total_photos}</div>
                </div>

HTML;
                // Showing the first of multiple photos
                } elseif (!isset($prev_pid)) {
                    $c = $cur + 1;
                    echo <<<HTML
                <div id="photo_nav" class="clearfix">
                    <div class="info">{$LANG['photo']} {$c} {$LANG['of']} {$total_photos}</div>
                    <div class="prev_next clearfix">
                        <span class="previous">&nbsp;</span>
                        <a class="next" href="?uid={$uid}&amp;cid={$urlcid}&amp;pid={$next_pid}">{$LANG['next']} &gt;&gt;</a>
                    </div>
                </div>
                <script type="text/javascript">
                function keyHandler(e) {
                    if (!e) { e = window.event; }
                    arrowRight = 39;
                    switch (e.keyCode) {
                        case arrowRight:
                        document.location.href = "index.php?uid={$uid}&cid={$urlcid}&pid={$next_pid}";
                        break;
                    }
                }
                document.onkeydown = keyHandler;
                </script>

HTML;
                // Showing the last of multiple photos
                } elseif (!isset($next_pid)) {
                    $c = $cur + 1;
                    echo <<<HTML
                <div id="photo_nav" class="clearfix">
                    <div class="info">{$LANG['photo']} {$c} {$LANG['of']} {$total_photos}</div>
                    <div class="prev_next clearfix">
                        <a class="previous" href="?uid={$uid}&amp;cid={$urlcid}&amp;pid={$prev_pid}">&lt;&lt; {$LANG['prev']}</a>
                        <span class="next">&nbsp;</span>
                    </div>
                </div>
                <script type="text/javascript">
                function keyHandler(e) {
                    if (!e) { e = window.event; }
                    arrowLeft = 37;
                    switch (e.keyCode) {
                        case arrowLeft:
                        document.location.href = "index.php?uid={$uid}&cid={$urlcid}&pid={$prev_pid}";
                        break;
                    }
                }
                document.onkeydown = keyHandler;
                </script>

HTML;
                // Showing photo with prev and next
                } else {
                    $c = $cur + 1;
                    echo <<<HTML
                <div id="photo_nav" class="clearfix">
                    <div class="info">{$LANG['photo']} {$c} {$LANG['of']} {$total_photos}</div>
                    <div class="prev_next clearfix">
                        <a class="previous" href="?uid={$uid}&amp;cid={$urlcid}&amp;pid={$prev_pid}">&lt;&lt; {$LANG['prev']}</a>
                        <a class="next" href="?uid={$uid}&amp;cid={$urlcid}&amp;pid={$next_pid}">{$LANG['next']} &gt;&gt;</a>
                    </div>
                </div>
                <script type="text/javascript">
                function keyHandler(e) {
                    if (!e) { e = window.event; }
                    arrowRight = 39;
                    arrowLeft = 37;
                    switch (e.keyCode) {
                        case arrowRight:
                        document.location.href = "index.php?uid={$uid}&cid={$urlcid}&pid={$next_pid}";
                        break;
                        case arrowLeft:
                        document.location.href = "index.php?uid={$uid}&cid={$urlcid}&pid={$prev_pid}";
                        break;
                    }
                }
                document.onkeydown = keyHandler;
                </script>

HTML;
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
                } else {
                    $tagged_mem_list .= "<i>" . $LANG['none'] . "</i>";
                }
                $tagged_mem_list = substr($tagged_mem_list, 0, -2); // remove the extra ", "
                $date_added = fixDST(
                    gmdate('F j, Y g:i a', strtotime($r['date'] . $this->tz_offset)), 
                    $this->cur_user_id, '. d, Y (h:i a)'
                );
                $monthName = fixDST(
                    gmdate('F j, Y g:i a', strtotime($r['date'] . $this->tz_offset)), 
                    $this->cur_user_id, 'M'
                );
                $size = formatSize($size);
                // Edit / Delete Photo options
                $edit_del_options = '';
                if ($this->cur_user_id == $r['uid'] || checkAccess($this->cur_user_id) < 2) {
                    $edit_del_options = <<<HTML
                <div class="edit_del_photo">
                    <form action="index.php" method="post">
                        <div>
                            <input type="hidden" name="photo" id="photo" value="{$pid}"/>
                            <input type="hidden" name="url" id="url" value="uid={$uid}&amp;cid={$urlcid}&amp;pid={$pid}"/>
                            <input type="submit" name="editphoto" id="editphoto" value="{$LANG['edit']}" class="editbtn"/>&nbsp;&nbsp;
                            <input type="submit" name="deletephoto" id="deletephoto" value="{$LANG['delete']}" class="delbtn" onclick="javascript:return confirm('{$LANG['js_del_photo']}');"/>
                        </div>
                    </form>
                </div>
HTML;
                }
                
                // Display Photo -- caption, rating and other info
                echo <<<HTML
            <p class="center">
                <a href="photos/member{$r['uid']}{$photo_path}{$r['filename']}"><img class="photo" src="photos/member{$r['uid']}/{$r['filename']}" alt="{$caption}" title="{$caption}"/></a>
            </p>
            <div class="caption">
                {$caption}
            </div>
            <div class="photo_details">
                {$edit_del_options}
                <p><b>{$LANG['filename']}:</b> &nbsp;{$r['filename']}</p>
                <div style="float:left"><b>{$LANG['rating']}:</b> &nbsp;</div>
                <div style="float:left">
                    <ul class="star-rating small-star">
                        <li class="current-rating" style="width:{$width}%">Currently {$r['rating']}/5 Stars.</li>
                        <li><a href="?uid={$r['uid']}&amp;cid={$r['cid']}&amp;pid={$pid}&amp;vote=1" title="{$LANG['title_stars1']}" class="one-star">1</a></li>
                        <li><a href="?uid={$r['uid']}&amp;cid={$r['cid']}&amp;pid={$pid}&amp;vote=2" title="{$LANG['title_stars2']}" class="two-stars">2</a></li>
                        <li><a href="?uid={$r['uid']}&amp;cid={$r['cid']}&amp;pid={$pid}&amp;vote=3" title="{$LANG['title_stars3']}" class="three-stars">3</a></li>
                        <li><a href="?uid={$r['uid']}&amp;cid={$r['cid']}&amp;pid={$pid}&amp;vote=4" title="{$LANG['title_stars4']}" class="four-stars">4</a></li>
                        <li><a href="?uid={$r['uid']}&amp;cid={$r['cid']}&amp;pid={$pid}&amp;vote=5" title="{$LANG['title_stars5']}" class="five-stars">5</a></li>
                    </ul>
                </div>
                <p style="clear:left"><b>{$LANG['views']}:</b> &nbsp;{$r['views']}</p>
                <p><b>{$LANG['photo_size']}:</b> &nbsp;{$size}</p>
                <p><b>{$LANG['photo_dimensions']}:</b> &nbsp;{$dimensions[0]} x {$dimensions[1]}</p>
                <p><b>{$LANG['date_photo_added']}:</b> &nbsp;{$monthName}{$date_added}</p>
                <p><b>{$LANG['members_in_photo']}:</b> &nbsp;{$tagged_mem_list}</p>
            </div>
HTML;
                
                // Display Comments
                if (
                    checkAccess($_SESSION['login_id']) <= 8 && 
                    checkAccess($_SESSION['login_id']) != 7 && 
                    checkAccess($_SESSION['login_id']) != 4
                ) {
                    echo <<<HTML
            <p>&nbsp;</p>
            <h3>{$LANG['comments']}</h3>
            <p class="center">
                <form action="?uid={$uid}&amp;cid={$urlcid}&amp;pid={$pid}" method="post">
                    {$LANG['add_comment']}<br/>
                    <input class="frm_text" type="text" name="comment" id="comment" size="50" title="{$LANG['add_comment']}"/> 
                    <input type="submit" name="addcom" id="addcom" value="" class="gal_addcombtn"/>
                </form>
            </p>
            <p>&nbsp;</p>
HTML;
                    $sql = "SELECT c.`id`, `comment`, `date`, `fname`, `lname`, `username`, `user` "
                         . "FROM `fcms_gallery_comments` AS c, `fcms_users` AS u "
                         . "WHERE `photo` = '$pid' "
                         . "AND c.`user` = u.`id` "
                         . "ORDER BY `date`";
                    $this->db->query($sql) or displaySQLError(
                        'Comments Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                    if ($this->db->count_rows() > 0) { 
                        while ($row = $this->db->get_row()) {
                            // Setup some vars for each comment block
                            $del_comment = '';
                            $date = fixDST(
                                gmdate('Y-m-d h:i:s', strtotime($row['date'] . $this->tz_offset)), 
                                $this->cur_user_id, 'M. d, Y (h:i a)'
                            );
                            $displayname = getUserDisplayName($row['user']);
                            $comment = htmlentities($row['comment'], ENT_COMPAT, 'UTF-8');
                            if ($this->cur_user_id == $row['user'] || 
                                checkAccess($this->cur_user_id) < 2) {
                                $del_comment .= '<input type="submit" name="delcom" id="delcom" '
                                    . 'value="" class="gal_delcombtn" title="'
                                    . $LANG['title_del_comment'] . '" onclick="javascript:return '
                                    . 'confirm(\'' . $LANG['js_del_comment'] . '\');"/>';
                            }
                            echo <<<HTML
            <div class="comment_block">
                <form action="?page=photo&amp;uid={$uid}&amp;cid={$urlcid}&amp;pid={$pid}" method="post">
                    {$del_comment}
                    <span>{$date}</span>
                    <b>{$displayname}</b><br/>
                    {$comment}
                    <input type="hidden" name="id" value="{$row['id']}">
                </form>
            </div>

HTML;
                        }
                    } else {
                        echo "<p class=\"center\">".$LANG['no_comments']."</p>";
                    }
                }
            
            // Specific Photo couldn't be found
            } else {
                echo "<p class=\"error-alert\">" . $LANG['err_photo_not_found'] . "</p>";
            }
        // No photos exist for the current view/category
        // Even though we are in photo view, bump them back to the category view
        // and let the user know that this category is now empty
        } else {
            $this->displayGalleryMenu($uid, $cid);
            echo "<div class=\"info-alert\"><h2>" . $LANG['info_cat_empty1'] . "</h2>";
            echo "<p>" . $LANG['info_cat_empty2'] . "</p></div>\n";
        }
    }

    /*
     *  showCategories
     *
     *  Displays a list of photos in the desired category or view.
     *
     *  @param      $from   the page # you want for the specified category
     *  @param      $uid    the user's id or 0 if displaying view for all users
     *  @param      $cid    optional, category id
     *  @return     nothing
     */
    function showCategories ($from, $uid, $cid = 'none')
    {
        global $LANG;
        
        // No user id specified
        if ($uid == 0) {
            
            // Member Gallery View
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
                
            // All Latest Comments View
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
                
            // Overall Top Rated View
            } elseif ($cid == 'toprated') {
                $sql = "SELECT 'RATED' AS type, `user` AS uid, `filename`, `category`, "
                        . "`caption`, `id` AS pid, `rating`/`votes` AS 'r' "
                     . "FROM `fcms_gallery_photos` "
                     . "WHERE `votes` > 0 "
                     . "ORDER BY r DESC";
                
            // Overall Most Viewed View
            } elseif ($cid == 'mostviewed') {
                $sql = "SELECT 'VIEWED' AS type, `user` AS uid, `filename`, `caption`, "
                        . "`id` AS pid, `views` "
                     . "FROM `fcms_gallery_photos` "
                     . "WHERE `views` > 0 "
                     . "ORDER BY VIEWS DESC";
                
            // Tagged Photos View (only number 0-9)
            } elseif (preg_match('/^\d+$/', $cid)) {
                $sql = "SELECT 'TAGGED' AS type, t.`user`, p.`id` AS pid, p.`filename`, "
                        . "p.`caption`, p.`user` AS uid "
                     . "FROM `fcms_gallery_photos` AS p, `fcms_gallery_photos_tags` AS t "
                     . "WHERE t.`user` = $cid "
                     . "AND t.`photo` = p.`id`";
            }
            
        // Valid user id specified
        } elseif (preg_match('/^\d+$/', $uid)) {
            
            // Member's Top Rated View
            if ($cid == 'toprated') {
                $sql = "SELECT 'RATED' AS type, `user` AS uid, `filename`, `category`, "
                        . "`caption`, `id` AS pid, `rating`/`votes` AS 'r' "
                     . "FROM `fcms_gallery_photos` "
                     . "WHERE `votes` > 0 AND `user` = $uid ";
                
            // Member's Most Viewed View
            } elseif ($cid == 'mostviewed') {
                $sql = "SELECT 'VIEWED' AS type, `user` AS uid, `filename`, `caption`, "
                        . "`id` AS pid, `views` "
                     . "FROM `fcms_gallery_photos` "
                     . "WHERE `views` > 0 "
                     . "AND `user` = $uid";
                
            // Photo Listing for Member's Sub Category (only numbers 0-9)
            } elseif (preg_match('/^\d+$/', $cid)) {
                $sql = "SELECT 'PHOTOS' AS type, u.`id` AS uid, `category` AS cid, "
                        . "p.`id` AS pid, `caption`, c.`name` AS category, `filename` "
                     . "FROM `fcms_gallery_category` AS c, `fcms_gallery_photos` AS p, "
                        . "`fcms_users` AS u "
                     . "WHERE p.`user` = u.`id` "
                     . "AND `category` = c.`id` "
                     . "AND `category` = $cid";
                
            // Member's Sub Categories View
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
            }
        // Catch all invalid $uid's
        } else {
            echo "<div class=\"info-alert\"><h2>" . $LANG['no_category1'] . "</h2>";
            echo "<p>" . $LANG['no_category2'] . "</p></div>";
        }
        
        // Starting with what page?  -- used with Latest Comments, Top Rated and Most Viewed
        if ($from >= 0) {
            $sql .= " LIMIT $from, 16";
        } else {
            $sql .= " LIMIT 8";
        }
        $this->db->query($sql) or displaySQLError(
            'Categories Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            $first = true;
            while ($row = $this->db->get_row()) {
                $cat_name = "";
                $url = "";
                $alt = "";
                $title = "";
                $cat_info = "";
                if ($row['type'] == 'MEMBER') {
                    if ($first) {
                        echo "<h3>" . $LANG['member_gal'] . "</h3>";
                    }
                    $displayname = getUserDisplayName($row['uid']);
                    $cat_name = "<div class=\"cat_name\">$displayname</div>";
                    $url = "?uid=" . $row['uid'];
                    $alt = ' alt="' . $LANG['alt_view_cat_for'] . ' ' 
                        . htmlentities($displayname, ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . $LANG['alt_view_cat_for'] . ' ' 
                        . htmlentities($displayname, ENT_COMPAT, 'UTF-8') . '"';
                    $cat_info = "<div class=\"cat_info\">" . $LANG['photos'] . " (" . $row['c'] 
                        . ")</div>";
                } elseif ($row['type'] == 'COMMENTS') {
                    if ($first) {
                        if ($from >= 0) {
                            echo "<h3>" . $LANG['latest_comments'] . "</h3>\n";
                        } else {
                            echo "<h3 style=\"float:left\">" . $LANG['latest_comments'] . "</h3>";
                            echo "<a style=\"float:left; margin-left:5px;\" ";
                            echo "href=\"?uid=0&amp;cid=comments\">(".$LANG['view_all'].")</a>\n";
                        }
                    }
                    $monthName = fixDST(
                        gmdate('F j, Y g:i a', strtotime($row['heading'] . $this->tz_offset)), 
                        $this->cur_user_id, 'M'
                    );
                    $date = fixDST(
                        gmdate('F j, Y g:i a', strtotime($row['heading'] . $this->tz_offset)), 
                        $this->cur_user_id, '. j, Y'
                    );
                    $date = getLangMonthName($monthName) . $date;
                    $cat_name = "<div class=\"cat_name\">$date</div>";
                    $url = "?uid=0&amp;cid=comments&amp;pid=" . $row['pid'];
                    $alt = ' alt="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $comment = $row['comment'];
                    if (strlen($comment) > 25) {
                        $comment = substr($comment, 0, 22) . "...";
                    }
                    $cat_info = "<div class=\"cat_info\"><b>" . getUserDisplayName($row['user']) 
                        . ":</b> $comment</div>";
                } elseif ($row['type'] == 'RATED') {
                    if ($first) {
                        echo "\t\t\t<h3>" . $LANG['top_rated'];
                        if ($uid > 0) {
                            echo " (" . getUserDisplayName($uid) . ")";
                        }
                        echo "</h3>\n";
                    }
                    $width = ($row['r'] / 5) * 100;
                    $url = "?uid=0&amp;cid=toprated" . $row['category'] . "&amp;pid=" . $row['pid'];
                    $alt = ' alt="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $cat_info = "<div class=\"cat_info\"><ul class=\"star-rating small-star\">"
                        . "<li class=\"current-rating\" style=\"width:$width%\">Currently " 
                        . $row['r'] . "/5 Stars.</li><li><a href=\"$url&amp;vote=1\" title=\"" 
                        . $LANG['title_stars1'] . "\" class=\"one-star\">1</a></li><li>"
                        . "<a href=\"$url&amp;vote=2\" title=\"" . $LANG['title_stars2']
                        . "\" class=\"two-stars\">2</a></li><li><a href=\"$url&amp;vote=3\" "
                        . "title=\"" . $LANG['title_stars3'] . "\" class=\"three-stars\">3</a>"
                        . "</li><li><a href=\"$url&amp;vote=4\" title=\"" . $LANG['title_stars4']
                        . "\" class=\"four-stars\">4</a></li><li><a href=\"$url&amp;vote=5\" "
                        . "title=\"" . $LANG['title_stars5'] . "\" class=\"five-stars\">5</a>"
                        . "</li></ul></div>";
                } elseif ($row['type'] == 'VIEWED') {
                    if ($first) {
                        echo "\t\t\t<h3>" . $LANG['most_viewed'];
                        if ($uid > 0) {
                            echo " (" . getUserDisplayName($uid) . ")";
                        }
                        echo "</h3>\n";
                    }
                    $url = "?uid=$uid&amp;cid=mostviewed&amp;pid=" . $row['pid'];
                    $alt = ' alt="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $cat_info = "<div class=\"cat_info\"><b>".$LANG['views'].": </b>"
                        . $row['views'] . "</div>";
                } elseif ($row['type'] == 'TAGGED') {
                    if ($first) {
                        echo "\t\t\t<h3>" . $LANG['photos_of'] . " ";
                        echo getUserDisplayName($row['user']) . "</h3>\n";
                    }
                    $url = "?uid=0&amp;cid=" . $row['user'] . "&amp;pid=" . $row['pid'];
                    $alt = ' alt="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                } elseif ($row['type'] == 'PHOTOS') {
                    if ($first) {
                        echo "\t\t\t<p class=\"breadcrumbs\"><a href=\"?uid=0\">";
                        echo $LANG['member_gal'] . "</a> &gt; <a href=\"?uid=$uid\">";
                        echo getUserDisplayName($row['uid']) . "</a> &gt; ";
                        echo $row['category'] . "</p>\n";
                    }
                    $url = "?uid=" . $row['uid'] . "&amp;cid=" . $row['cid'] 
                        . "&amp;pid=" . $row['pid'];
                    $alt = ' alt="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . '"';
                } elseif ($row['type'] == 'CATEGORIES') {
                    if ($first) {
                        echo "\t\t\t<p class=\"breadcrumbs\"><a href=\"?uid=0\">";
                        echo $LANG['member_gal']."</a> &gt; " . getUserDisplayName($row['uid']);
                        echo "</p>\n";
                    }
                    $cat_name = "<div class=\"cat_name\">" . $row['category'] . "</div>";
                    $url = "?uid=" . $row['uid'] . "&amp;cid=" . $row['cid'];
                    $alt = ' alt="' . $LANG['alt_view_photos_in'] . ' '
                        . htmlentities($row['category'], ENT_COMPAT, 'UTF-8') . '"';
                    $title = ' title="' . $LANG['alt_view_photos_in'] . ' '
                        . htmlentities($row['category'], ENT_COMPAT, 'UTF-8') . '"';
                    $cat_info = "<div class=\"cat_info\">" . $LANG['photos'] . " ("
                        . $row['c'] . ")</div>";
                }
                $category_rows[] = "$cat_name<a href=\"index.php$url\"><img "
                    . "class=\"photo\" src=\"photos/member" . $row['uid'] . "/tb_"
                    . $row['filename'] . "\" $alt $title/></a>$cat_info";
                $first = false;
            }
            echo "\t\t\t<div class=\"gal_row clearfix\">";
            $i = 0;
            foreach ($category_rows as $row) {
                if ($i == $this->categories_per_row) {    
                    $i = 1;
                    echo "</div>\n\t\t\t<div class=\"gal_row clearfix\">";
                } else {
                    $i++;
                }
                echo "<div class=\"cat\">$row</div>";
            }
            echo "</div>\n";
            
            // Display pages if needed
            if ($from >= 0) {
                $page = ($from / 16) + 1;
                
                // Remove the LIMIT from the $sql statement 
                // used above, so we can get the total count
                $sql = substr($sql, 0, strpos($sql, 'LIMIT'));
                
                // Setup the pages URL link
                if (preg_match('/^\d+$/', $cid)) {
                    // Remove the pid (link to a specific photo) from the $url to be used 
                    // in pages url
                    $pos = strpos($url, '&amp;pid=');
                    if ($pos !== false) {
                        $url = substr($url, 0, $pos);
                    }
                } elseif ($uid == 0) {
                    // Reset the uid to 0 on the url
                    $url = "?uid=0";
                    // If toprated or most views, cid will need added to url
                    if ($cid != 'none') {
                        $url .= "&amp;cid=$cid";
                    }
                } else {
                    // Remove the cid (link to a specific category) from the $url to be used 
                    // in pages url
                    $pos = strpos($url, '&amp;cid=');
                    if ($pos !== false) {
                        $url = substr($url, 0, $pos);
                    }
                }
                
                $this->db->query($sql) or displaySQLError(
                    'Page Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                $count = $this->db->count_rows();
                $total_pages = ceil($count / 16); 
                displayPages("index.php$url", $page, $total_pages);
            }
            
        // If the sql statement returns no results and we're not trying to show 
        // the latest 8 comments on the index
        } elseif ($uid !== 0 && $cid !== 'comments') {
            echo "\t\t\t<div class=\"info-alert\"><h2>" . $LANG['info_cat_empty1'] . "</h2>";
            echo "<p>" . $LANG['info_cat_empty2'] . "</p></div>\n";
        }
    }

    /*
     *  displayUploadForm
     *
     *  Displays the form for uploading photos to the photo gallery.
     *
     *  @param      $num        the number of photos to be uploaded
     *  @param      $last_cat   the last category photos where uploaded to
     *  @return     nothing
     */
    function displayUploadForm ($num, $last_cat)
    {
        global $LANG;
        
        $cat_options = '';
        $photo_uploads = '';
        
        // TODO -- move this to a function, this is used more than once
        // Setup the list of categories for the select box
        $sql = "SELECT `id`, `name` FROM `fcms_gallery_category` "
             . "WHERE `user` = " . $this->cur_user_id;
        $this->db->query($sql) or displaySQLError(
            'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            while($row = $this->db->get_row()) {
                $cat_options .= "<option";
                if ($last_cat == $row['id']) {
                    $cat_options .= ' selected="selected"';
                }
                $cat_options .= ' value="' . $row['id'] . '">' . $row['name'] . '</option>';
            }
            
            // Display the photo upload browse buttons and options if applicable
            $i = 1;
            while ($i <= $num) {
                $photo_label = '';
                $spacing = '';
                
                // Uploading multiple photos
                if ($num > 1) {
                    $photo_label .= $LANG['photo'] . " ($i)";
                    // Addd some spacing between upload boxes
                    $spacing .= "<p>&nbsp;</p>";
                
                // Uploading just one photo (default)
                } else {
                    $photo_label .= $LANG['photo'];
                    
                    // Setup the list of members for the tagging select box
                    $sql = "SELECT `id` FROM `fcms_users` WHERE `activated` > 0";
                    $this->db2->query($sql) or displaySQLError(
                        'Members Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                    );
                    while ($r = $this->db2->get_row()) {
                        $displayNameArr[$r['id']] = getUserDisplayName($r['id']);
                    }
                    asort($displayNameArr);
                    $tag_checkboxes = '';
                    foreach ($displayNameArr as $key => $value) {
                        $tag_checkboxes .= '<label for="' . $key . '"><input type="checkbox" id="'
                            . $key . '" name="tagged[]"  value="' . $key . '"/> ' . $value . '</label>';
                    }
                    // Show the rotate and tagging options
                    $rotate_options = <<<HTML
                <div id="rotate-options">
                    <p class="center">
                        {$LANG['rotate_left']}: <input type="radio" name="rotate[]" value="left"/>&nbsp;&nbsp; 
                        {$LANG['rotate_right']}: <input type="radio" name="rotate[]" value="right"/>
                    </p>
                </div>

HTML;
                    $tag_options = <<<HTML
                <div id="tag-options">
                    <p class="center">
                        {$LANG['who_in_photo']}:
                        <div class="multi-checkbox" style="margin: 0 auto;">
                            {$tag_checkboxes}
                        </div>
                    </p>
                </div>
HTML;
                }
                
                // Display the photo browse and caption
                $photo_uploads .= <<<HTML
                    <div class="field-row clearfix">
                        <div class="field-label"><label><b>{$photo_label}</b></label></div>
                        <div class="field-widget">
                            <input name="photo_filename[]" type="file" size="50"/>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label><b>{$LANG['caption']}</b></label></div>
                        <div class="field-widget">
                            <input class="frm_text" type="text" name="photo_caption[]" size="50"/>
                        </div>
                    </div>
{$rotate_options}
{$tag_options}
                    {$spacing}

HTML;
                $i++;
            }

            // Display the form
            echo <<<HTML
            <fieldset>
                <legend>{$LANG['upload_photos']}</legend>
                <p class="alignright">
                    <a class="help" href="../help.php#gallery-howworks">{$LANG['link_help']}</a>
                </p>
                <form enctype="multipart/form-data" action="?action=upload" method="post">
                    <div class="field-row clearfix">
                        <div class="field-label"><label><b>{$LANG['select_cat']}</b></label></div>
                        <div class="field-widget">
                            <select name="category">
                                {$cat_options}
                            </select>
                        </div>
                    </div>
{$photo_uploads}
                    <input type="submit" id="addphoto" name="addphoto" value="{$LANG['submit']}"/>
                </form>
                <p>&nbsp;</p>

HTML;
        
        // User doesn't have a category to upload photos into
        } else {
            echo '<p class="info-alert">' . $LANG['err_cat_first'] . '</p>';
            $this->displayAddCatForm();
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
        global $LANG;
        echo "<h4>" . $LANG['edit_photo'] . "</h4>\n";
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
                $displayNameArr[$r['id']] = getUserDisplayName($r['id']);
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
            echo <<<HTML
                <form enctype="multipart/form-data" action="index.php?{$url}" method="post">
                    {$LANG['change_cat']}
                    <select name="category">
                        {$cat_options}
                    </select>
                    <p><img src="photos/member{$photo_user}/{$filename}"/></p>
                    <p>{$LANG['caption']}: <input class="frm_text" type="text" name="photo_caption" size="50" value="{$caption_html}"/></p>
                    <p>
                        {$LANG['who_in_photo']}: 
                        <div class="multi-checkbox">
                            {$tag_checkboxes}
                        </div>
                    </p>
                    {$prev_tagged}
                    <p>
                        <input type="hidden" name="photo_id" id="photo_id" value="{$photo}"/>
                        <input type="submit" name="add_editphoto" value="{$LANG['submit_changes']}"/> 
                        {$LANG['or']} <a href="index.php?{$url}">{$LANG['cancel']}</a>
                    </p>
                </form>

HTML;
        } else {
            echo '<p class="error-alert">' . $LANG['err_edit_photo'] . '</p>' . "\n";
        }
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
        global $LANG;
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
                    echo "<p class=\"error-alert\">" . $LANG['err_not_file1'] . " ";
                    echo ($i+1) . " ".$LANG['err_not_file2']."</p><br />";
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
                    if (count($photos_uploaded['name']) > 1) {
                        if ($i <= 0) {
                            echo '<p class="ok-alert"><b>' . $LANG['ok_photos_success'].'</b></p>';
                        }
                        echo "<img src=\"photos/member" . $this->cur_user_id;
                        echo "/tb_$filename\" alt=\"" . $photos_caption[$i] . "\"/>&nbsp;&nbsp;";
                    } else {
                        echo "<p class=\"ok-alert\"><b>" . $LANG['ok_photos_success'] . "</b>";
                        echo "<br/><img src=\"photos/member" . $this->cur_user_id;
                        echo "/tb_$filename\" alt=\"" . $photos_caption[$i] . "\"/></p>";
                    }
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
        global $LANG;
        $cat_list = '';
        
        // Setup the list of categories for edit/delete
        $sql = "SELECT * FROM fcms_gallery_category WHERE user=" . $this->cur_user_id;
        $this->db->query($sql) or displaySQLError(
            'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            while ($row = $this->db->get_row()) {
                $name = htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
                $cat_list .= <<<HTML
                <li>
                    <form class="frm_line" action="index.php?action=category" method="post">
                        <input class="frm_text" type="text" name="cat_name" id="cat_name" size="60" value="{$name}"/>
                        <input type="hidden" name="cid" id="cid" value="{$row['id']}"/> &nbsp;
                        <input type="submit" name="editcat" class="editbtn" value=""/> &nbsp;
                        <input type="submit" name="delcat" class="delbtn" value="" onclick="javascript:return confirm('{$LANG['js_del_cat']}');"/>
                    </form>
                </li>
HTML;
            }
        } else {
            $cat_list .= "<li><i>" . $LANG['no_cats'] . "</i></li>";
        }
        
        // Display the form
        echo <<<HTML
            <h3>{$LANG['create_cat']}</h3>
            <form action="index.php?action=category" method="post">
                <p>
                    {$LANG['new_cat_name']}: 
                    <input class="frm_text" type="text" name="cat_name" id="cat_name"/>
                    <input type="submit" name="newcat" id="newcat" value="{$LANG['add_cat']}"/>
                </p>
            </form>
            <p>&nbsp;</p>
            <h3>{$LANG['edit_cat']}</h3>
            <ul class="gallery_cat">
{$cat_list}
            </ul>
            <p>&nbsp;</p>

HTML;
    }

    function displayWhatsNewGallery ()
    {
        global $LANG;
        $today = date('Y-m-d');
        $tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
        echo "          <h3>" . $LANG['link_gallery'] . "</h3>\n";
        $sql = "SELECT DISTINCT p.user, name AS category, p.category AS cid, "
                . "DAYOFYEAR(`date`) AS d, COUNT(*) AS c "
             . "FROM fcms_gallery_photos AS p, fcms_users AS u, fcms_gallery_category AS c "
             . "WHERE p.user = u.id "
             . "AND p.category = c.id "
             . "AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) "
             . "GROUP BY user, category, d "
             . "ORDER BY `date` DESC LIMIT 0 , 5";
        $this->db->query($sql) or displaySQLError(
            'Last 5 New Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo "              <ul>\n";
            while($row = $this->db->get_row()) {
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
                $this->db2->query($sql) or displaySQLError(
                    'Date Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
                $found = $this->db2->get_row();
                $monthName = gmdate('M', strtotime($found['date'] . $this->tz_offset));
                $monthName = getLangMonthName($monthName);
                $date = gmdate('. j, Y, g:i a', strtotime($found['date'] . $this->tz_offset));
                if (
                    strtotime($found['date']) >= strtotime($today) && 
                    strtotime($found['date']) < strtotime($tomorrow)
                ) {
                    $full_date = $LANG['today'];
                    $d = ' class="today"';
                } else {
                    $full_date = "$monthName$date";
                    $d = '';
                }
                echo <<<HTML
                    <li>
                        <div{$d}>{$full_date}</div>
                        <a href="gallery/index.php?uid={$row['user']}&amp;cid={$row['cid']}" title="{$full_category}">$category</a>
                        ({$row['c']} {$LANG['new_photos']}) - 
                        <a class="u" href="profile.php?member={$row['user']}">{$displayname}</a>
                    </li>

HTML;
            }
            echo "              </ul>\n";
        } else {
            echo "<ul><li><i>" . $LANG['nothing_new_30'] . "</i></li></ul>\n";
        }
    }

} ?>
