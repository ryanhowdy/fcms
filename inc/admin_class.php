<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

/**
 * Admin 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Admin {

    var $db;
    var $db2;
    var $db3;
    var $tz_offset;
    var $lastmonth_beg;
    var $lastmonth_end;
    var $currentUserId;

    /**
     * Admin 
     * 
     * @param   int     $currentUserId 
     * @param   string  $type 
     * @param   string  $host 
     * @param   string  $database 
     * @param   string  $user 
     * @param   string  $pass 
     * @return  void
     */
    function Admin ($currentUserId, $type, $host, $database, $user, $pass)
    {
        $this->currentUserId = $currentUserId;
        $this->db = new database($type, $host, $database, $user, $pass);
        $this->db2 = new database($type, $host, $database, $user, $pass);
        $this->db3 = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` 
                FROM `fcms_user_settings` 
                WHERE `user` = '" . cleanInput($currentUserId, 'int') . "'";
        $this->db->query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
        $this->lastmonth_beg = date('Y-m', mktime(0, 0, 0, date('m')-1, 1, date('Y'))) . "-01 00:00:00";
        $this->lastmonth_end = date('Y-m', mktime(0, 0, 0, date('m')-1, 1, date('Y'))) . "-31 23:59:59";
        T_bindtextdomain('messages', '.././language');
    }

    /**
     * showThreads 
     * 
     * @param   string  $type 
     * @param   int     $page 
     * @return  void
     */
    function showThreads ($type, $page = 0)
    {
        $locale = new Locale();

        $from = (($page * 25) - 25);
        if ($type == 'announcement') {
            echo '
            <table id="threadlist" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th class="images">&nbsp;</th>
                        <th class="subject">'.T_('Subject').'</th>
                        <th class="replies">'.T_('Replies').'</th>
                        <th class="views">'.T_('Views').'</th>
                        <th class="updated">'.T_('Last Updated').'</th>
                    </tr>
                </thead>
                <tbody>';
            $sql = "SELECT t.`id`, `subject`, `started_by`, `updated`, `updated_by`, `views`, `user` 
                    FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p 
                    WHERE t.`id` = p.`thread` 
                    AND `subject` LIKE '#ANOUNCE#%' 
                    GROUP BY t.`id` 
                    ORDER BY `updated` DESC";
            $this->db->query($sql) or displaySQLError(
                'Announcements Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        } else {
            $sql = "SELECT t.`id`, `subject`, `started_by`, `updated`, `updated_by`, `views`, `user` 
                    FROM `fcms_board_threads` AS t, `fcms_board_posts` AS p 
                    WHERE t.`id` = p.`thread` 
                    AND `subject` NOT LIKE '#ANOUNCE#%' 
                    GROUP BY t.`id` 
                    ORDER BY `updated` DESC 
                    LIMIT " . $from . ", 25";
            $this->db->query($sql) or displaySQLError(
                'Threads Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        }
        $alt = 0;
        while ($row = $this->db->get_row()) {
            $started_by = getUserDisplayName($row['started_by']);
            $updated_by = getUserDisplayName($row['updated_by']);
            $subject = $row['subject'];
            if ($type == 'announcement') {
                $subject = substr($subject, 9, strlen($subject)-9);
                $subject = '<small><b>'.T_('Announcement').': </b></small>'.$subject;
                $tr_class = 'announcement';
            } else {
                if ($alt % 2 == 0) {
                    $tr_class = '';
                } else {
                    $tr_class = 'alt';
                }
            }

            $today_start = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '000000';
            $today_end = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '235959';

            $time = gmmktime(0, 0, 0, gmdate('m')  , gmdate('d')-1, gmdate('Y'));
            $yesterday_start = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s', $time)) . '000000';
            $yesterday_end = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s', $time)) . '235959';

            $updated = $locale->fixDate('YmdHis', $this->tz_offset, strtotime($row['updated']));

            // Updated Today
            if ($updated >= $today_start && $updated <= $today_end) {
                if ($type == 'announcement') {
                    $up_class = 'announcement_today';
                } else {
                    $up_class = 'today';
                }
                $date = $locale->fixDate('h:ia', $this->tz_offset, strtotime($row['updated']));
                $last_updated = sprintf(T_('Today at %s'), $date)
                    . "<br/>" . sprintf(T_('by %s'), $updated_by);

            // Updated Yesterday
            } elseif ($updated >= $yesterday_start && $updated <= $yesterday_end) {
                if ($type == 'announcement') {
                    $up_class = 'announcement_yesterday';
                } else {
                    $up_class = 'yesterday';
                }
                $date = $locale->fixDate('h:ia', $this->tz_offset, strtotime($row['updated']));
                $last_updated = sprintf(T_('Yesterday at %s'), $date)
                    . "<br/>" . sprintf(T_('by %s'), $updated_by);

            // Updated older than yesterday
            } else {
                if ($type == 'announcement') {
                    $up_class = 'announcement';
                } else {
                    $up_class = '';
                }
                $date = $locale->fixDate(T_('m/d/Y h:ia'), $this->tz_offset, strtotime($row['updated']));
                $last_updated = $date . "<br/>" . sprintf(T_('by %s'), $updated_by);
            }
            $replies = $this->getNumberOfPosts($row['id']) - 1;
            
            // Display Row
            echo '
                    <tr class="'.$tr_class.'">
                        <td class="images"><div class="'.$up_class.'"&nbsp;</div></td>
                        <td class="subject">
                            '.$subject.' 
                            <small>
                                <a class="edit_thread" href="board.php?edit='.(int)$row['id'].'">'.T_('Edit').'</a> 
                                <a class="del_thread" href="board.php?del='.(int)$row['id'].'">'.T_('Delete').'</a>
                            </small><br/>
                            '.$started_by.'
                        </td>
                        <td class="replies">'.$replies.'</td>
                        <td class="views">'.$row['views'].'</td>
                        <td class="updated">
                            '.$last_updated.'
                        </td>
                    </tr>';
            $alt++;
        }
        if ($type == 'thread') {
            echo '
                </tbody>
            </table>
            <div class="top clearfix"><a href="#top">'.T_('Back to Top').'</a></div>';
            $this->displayPages($page);
        }
    }

    /**
     * getNumberOfPosts 
     * 
     * @param  int $thread_id 
     * @return void
     */
    function getNumberOfPosts ($thread_id)
    {
        $sql = "SELECT COUNT(*) AS c 
                FROM `fcms_board_posts` 
                WHERE `thread` = '" . cleanInput($thread_id, 'int') . "'";
        $this->db2->query($sql) or displaySQLError(
            '# of Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row=$this->db2->get_row();
        return $row['c'];
    }

    /**
     * getSortOrder 
     * 
     * @param  int $user_id 
     * @return void
     */
    function getSortOrder ($user_id)
    {
        $sql = "SELECT `boardsort` 
                FROM `fcms_users` 
                WHERE `id` = '" . cleanInput($user_id, 'int') . "'";
        $this->db2->query($sql) or displaySQLError(
            'Sort Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row=$this->db2->get_row();
        return $row['boardsort'];
    }

    /**
     * getShowAvatar 
     * 
     * @param  int $user_id 
     * @return void
     */
    function getShowAvatar ($user_id)
    {
        $sql = "SELECT `showavatar` 
                FROM `fcms_users` 
                WHERE `id` = '" . cleanInput($user_id, 'int') . "'";
        $this->db2->query($sql) or displaySQLError(
            'Avatar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row=$this->db2->get_row();
        return $row['showavatar'];
    }

    /**
     * getUserPostCountById 
     * 
     * @param  int      $user_id 
     * @return string
     */
    function getUserPostCountById ($user_id)
    {
        $sql = "SELECT `id`
                FROM `fcms_board_posts`";
        $this->db2->query($sql) or displaySQLError(
            'Post Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $total=$this->db2->count_rows();

        $sql = "SELECT COUNT(`user`) AS c 
                FROM `fcms_board_posts` 
                WHERE `user` = '" . cleanInput($user_id, 'int') . "'";
        $this->db2->query($sql) or displaySQLError(
            'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row=$this->db2->get_row();
        $count=$row['c'];

        if($total < 1) { 
            return "0 (0%)";
        } else { 
            return $count . " (" . round((($count/$total)*100), 1) . "%)";
        }
    }

    /**
     * displayPages 
     *
     * @todo    this needs removed in favor of the global function in inc/util
     * @param   int $page 
     * @param   int $thread_id 
     * @return  void
     */
    function displayPages ($page = 1, $thread_id = 0)
    {
        $thread_id = cleanInput($thread_id, 'int');

        if ($thread_id < 1) {
            $sql = "SELECT COUNT(`id`) AS c 
                    FROM `fcms_board_threads`";
            $this->db2->query($sql) or displaySQLError(
                'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $row=$this->db2->get_row();
            $total_pages = ceil($row['c'] / 25); 
        } else {
            $sql = "SELECT COUNT(`id`) AS c 
                    FROM `fcms_board_posts` 
                    WHERE `thread` = '$thread_id'";
            $this->db2->query($sql) or displaySQLError(
                'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $row=$this->db2->get_row();
            $total_pages = ceil($row['c'] / 15); 
        }
        if ($total_pages > 1) {
            echo '
            <div class="pages clearfix">
                <ul>';
            $url = '';
            if ($thread_id != 0) {
                $url = 'thread='.$thread_id.'&amp;';
            }
            if ($page > 1) { 
                $prev = ($page - 1); 
                echo '
                    <li><a title="'.T_('First Page').'" class="first" href="board.php?'.$url.'page=1"></a></li>
                    <li><a title="'.T_('Previous Page').'" class="previous" href="board.php?'.$url.'page='.$prev.'"></a></li>'; 
            } 
            if ($total_pages > 8) {
                if ($page > 2) {
                    for ($i = ($page-2); $i <= ($page+5); $i++) {
                        $class = ($page == $i) ? 'class="current"' : '';
                        if ($i <= $total_pages) {
                            echo '
                    <li><a href="board.php?'.$url.'page='.$i.'" '.$class.'>'.$i.'</a></li>';
                        }
                    } 
                } else {
                    for ($i = 1; $i <= 8; $i++) {
                        $class = ($page == $i) ? 'class="current"' : '';
                        echo '
                    <li><a href="board.php?'.$url.'page='.$i.'" '.$class.'>'.$i.'</a></li>';
                    } 
                }
            } else {
                for ($i = 1; $i <= $total_pages; $i++) {
                    $class = ($page == $i) ? 'class="current"' : '';
                    echo '
                    <li><a href="board.php?'.$url.'page='.$i.'" '.$class.'>'.$i.'</a></li>';
                } 
            }
            if ($page < $total_pages) { 
                $next = ($page + 1); 
                echo '
                    <li><a title="'.T_('Next Page').'" class="next" href="board.php?'.$url.'page='.$next.'"></a></li>
                    <li><a title="'.T_('Last Page').'" class="last" href="board.php?'.$url.'page='.$total_pages.'"></a></li>';
            } 
            echo '
                </ul>
            </div>';
        }
    }

    /**
     * displayEditPollForm 
     * 
     * @param  int $pollid 
     * @return void
     */
    function displayEditPollForm ($pollid = 0)
    {
        $poll_exists = true;

        if ($pollid > 0) {
            $sql = "SELECT `question`, o.`id`, `option` 
                    FROM `fcms_polls` AS p, `fcms_poll_options` AS o 
                    WHERE p.`id` = o.`poll_id` 
                    AND p.`id` = '" . cleanInput($pollid, 'int') . "'";
            $this->db->query($sql) or displaySQLError(
                'Poll Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            if ($this->db->count_rows() <= 0) {
                $poll_exists = false;
            }
        } else {

            // Get last poll info
            $sql = "SELECT MAX(`id`) AS c FROM `fcms_polls`";
            $this->db->query($sql) or displaySQLError(
                'Max Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $row = $this->db->get_row();
            $latest_poll_id = $row['c'];
            if (is_null($row['c'])) {
                $poll_exists = false;
                $this->displayAddPollForm();
            } else {
                $sql = "SELECT `question`, o.`id`, `option` 
                        FROM `fcms_polls` AS p, `fcms_poll_options` AS o 
                        WHERE p.`id` = o.`poll_id` 
                        AND p.`id` = $latest_poll_id";
                $this->db->query($sql) or displaySQLError(
                    'Poll Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
            }
        }

        // Display the current poll
        if ($poll_exists) {
            echo '
            <form id="editform" name="editform" action="?page=admin_polls" method="post">
                <fieldset>
                    <legend><span>'.T_('Edit Poll').'</span></legend>';
            $i = 1;
            while ($row = $this->db->get_row()) {
                if ($i < 2) {
                    echo '
                    <h3>'.cleanOutput($row['question']).'</h3>';
                }
                echo '
                    <div class="field-row">
                        <div class="field-label"><label for="show'.$i.'"><b>'.sprintf(T_('Option %s'), $i).':</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="show'.$i.'" id="show'.$i.'" ';
                if ($i < 3) {
                    echo "class=\"required\"";
                }
                echo ' size="50" value="'.cleanOutput($row['option']).'"/>
                            <input type="hidden" name="option'.$i.'" value="'.$row['id'].'"/>';
                if ($i >= 3) {
                    echo '
                            <input type="button" name="deleteoption" class="delbtn" value="'.T_('Delete').'" 
                                title="'.T_('Delete').'" onclick="document.editform.show'.$i.'.value=\'\';"/>';
                }
                echo '
                        </div>
                    </div>';
                $i++;
            }
            while ($i < 11) {
                echo '
                    <div class="field-row">
                        <div class="field-label"><label for="show'.$i.'"><b>'.sprintf(T_('Option %s'), $i).':</b></label></div>
                        <div class="field-widget">
                            <input type="text" id="show'.$i.'" name="show'.$i.'" size="50" value=""/>
                            <input type="hidden" name="option'.$i.'" value="new"/>
                        </div>
                    </div>';
                $i++;
            }
            echo '
                    <p>
                        <input class="sub1" type="submit" name="editsubmit" id="editsubmit" value="'.T_('Edit').'"/> &nbsp;
                        <a href="polls.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
        }
    }

    /**
     * displayAddPollForm 
     * 
     * @return void
     */
    function displayAddPollForm ()
    {
        echo '
            <script type="text/javascript" src="../inc/livevalidation.js"></script>
            <form id="addform" action="polls.php" method="post">
                <fieldset>
                    <legend><span>'.T_('Add New Poll').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="question"><b>'.T_('Poll Question').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="question" id="question" class="required" size="50"/></div>
                    </div>
                    <script type="text/javascript">
                        var fq = new LiveValidation(\'question\', { onlyOnSubmit: true });
                        fq.add(Validate.Presence, { failureMessage: "'.T_('Required').'" });
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="option1"><b>'.sprintf(T_('Option %s'), '1').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option1" id="option1" class="required" size="40"/></div>
                    </div>
                    <script type="text/javascript">
                        var foption1 = new LiveValidation(\'option1\', { onlyOnSubmit: true });
                        foption1.add(Validate.Presence, {failureMessage: "'.T_('Without at least 2 options, it\'s not much of a poll is it?').'"});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="option2"><b>'.sprintf(T_('Option %s'), '2').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option2" id="option2" class="required" size="40"/></div>
                    </div>
                    <script type="text/javascript">
                        var foption2 = new LiveValidation(\'option2\', { onlyOnSubmit: true });
                        foption2.add(Validate.Presence, {failureMessage: "'.T_('Without at least 2 options, it\'s not much of a poll is it?').'"});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="option3"><b>'.sprintf(T_('Option %s'), '3').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option3" id="option3" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option4"><b>'.sprintf(T_('Option %s'), '4').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option4" id="option4" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option5"><b>'.sprintf(T_('Option %s'), '5').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option5" id="option5" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option6"><b>'.sprintf(T_('Option %s'), '6').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option6" id="option6" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option7"><b>'.sprintf(T_('Option %s'), '7').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option7" id="option7" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option8"><b>'.sprintf(T_('Option %s'), '8').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option8" id="option8" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option9"><b>'.sprintf(T_('Option %s'), '9').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option9" id="option9" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option10"><b>'.sprintf(T_('Option %s'), '10').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option10" id="option10" size="40"/></div>
                    </div>
                    <p>
                        <input class="sub1" type="submit" name="addsubmit" value="'.T_('Add').'"/> &nbsp;
                        <a href="polls.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * displayAdminConfig
     *
     * Displays the forms for changing/configuring the sitename,
     * email, auto activation, user defaults and sections.
     * 
     * @param   $view   which admin config section to view/edit
     * @return  void
     */
    function displayAdminConfig ($view)
    {
        switch($view) {
            case 'general':
                $this->displayAdminConfigInfo();
                break;
            case 'defaults':
                $this->displayAdminConfigDefaults();
                break;
            case 'sections':
                $this->displayAdminConfigSections();
                break;
            case 'gallery':
                $this->displayAdminConfigGallery();
                break;
        }
    }

    /**
     * displayAdminConfigInfo 
     * 
     * @return void
     */
    function displayAdminConfigInfo ()
    {
        // General Config
        $sql = "SELECT * FROM `fcms_config`";
        $this->db->query($sql) or displaySQLError(
            'Site Info Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        
        // Activate Options
        $activate_list = array (
            "0" => T_('Admin Activation'),
            "1" => T_('Auto Activation')
        );
        $activate_options = buildHtmlSelectOptions($activate_list, $row['auto_activate']);

        // Site Off Options
        $site_off_options = '<input type="radio" name="site_off" id="site_off_yes" '
            . 'value="yes"';
        if ($row['site_off'] == 1) { $site_off_options .= ' checked="checked"'; }
        $site_off_options .= '><label class="radio_label" for="site_off_yes"> '
            . T_('Yes') . '</label><br><input type="radio" name="site_off" '
            . 'id="site_off_no" value="no"';
        if ($row['site_off'] == 0) { $site_off_options .= ' checked="checked"'; }
        $site_off_options .= '><label class="radio_label" for="site_off_no"> '
            . T_('No') . '</label>';

        // Errors
        $error_list = array(
            '1' => T_('Log Errors'),
            '0' => T_('Display Errors')
        );
        $error_options = buildHtmlSelectOptions($error_list, $row['log_errors']);
        
        echo '
        <form action="config.php" method="post">
        <fieldset class="general_cfg">
            <legend><span>'.T_('Website Information').'</span></legend>
            <div id="site_info">
                <div class="field-row clearfix">
                    <div class="field-label"><label for="sitename"><b>'.T_('Site Name').'</b></label></div>
                    <div class="field-widget">
                        <input type="text" name="sitename" size="50" value="'.cleanOutput($row['sitename']).'"/>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="contact"><b>'.T_('Contact Email').'</b></label></div>
                    <div class="field-widget">
                        <input type="text" id="contact" name="contact" size="50" value="'.cleanOutput($row['contact']).'"/>
                    </div>
                </div>
                <script type="text/javascript">
                    var email = new LiveValidation(\'contact\', {onlyOnSubmit: true});
                    email.add(Validate.Email, {failureMessage: "'.T_('That\'s not a valid email address is it?').'"});
                    email.add(Validate.Length, {minimum: 10});
                </script>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="activation"><b>'.T_('Account Activation').'</b></label></div>
                    <div class="field-widget">
                        <select name="activation">
                            '.$activate_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="site_off"><b>'.T_('Turn Off Site?').'</b></label></div>
                    <div class="field-widget">
                        '.$site_off_options.'
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="log_errors"><b>'.T_('Errors?').'</b></label></div>
                    <div class="field-widget">
                        <select name="log_errors">
                            '.$error_options.'
                        </select>
                    </div>
                </div>
                <p><input type="submit" id="submit-sitename" name="submit-sitename" value="'.T_('Save').'"/></p>
            </div>
        </fieldset>
        </form>';
    }

    /**
     * displayAdminConfigDefaults 
     * 
     * @return void
     */
    function displayAdminConfigDefaults ()
    {
 
        // Defaults Config
        $sql = "DESCRIBE `fcms_user_settings`";
        $this->db3->query($sql) or displaySQLError(
            'Describe Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($drow = $this->db3->get_row()) {
            if ($drow['Field'] == 'theme') {
                $default_theme = $drow['Default'];
            }
            if ($drow['Field'] == 'showavatar') {
                $default_showavatar = $drow['Default'];
            }
            if ($drow['Field'] == 'displayname') {
                $default_displayname = $drow['Default'];
            }
            if ($drow['Field'] == 'frontpage') {
                $default_frontpage = $drow['Default'];
            }
            if ($drow['Field'] == 'timezone') {
                $default_tz = $drow['Default'];
            }
            if ($drow['Field'] == 'dst') {
                $default_dst = $drow['Default'];
            }
            if ($drow['Field'] == 'boardsort') {
                $default_boardsort = $drow['Default'];
            }
        }
        // Themes
        $dir = "../themes/";
        $theme_options = '';
        if (is_dir($dir))    {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (filetype($dir . $file) === "dir" && 
                        $file !== "." && 
                        $file !== ".." && 
                        $file !== "smileys"
                    ) {
                        $arr[] = $file;
                    }
                }
                closedir($dh);
                sort($arr);
                foreach($arr as $file) {
                    $theme_options .= "<option value=\"$file\"";
                    if ($default_theme == $file) {
                        $theme_options .= " selected=\"selected\"";
                    }
                    $theme_options .= ">$file</option>";
                }
            }
        }
        // Show Avatars
        $avatars_options = '<input type="radio" name="showavatar" id="showavatar_yes" '
            . 'value="yes"';
        if ($default_showavatar == 1) { $avatars_options .= ' checked="checked"'; }
        $avatars_options .= '><label class="radio_label" for="showavatar_yes"> '
            . T_('Yes') . '</label><br><input type="radio" name="showavatar" '
            . 'id="showavatar_no" value="no"';
        if ($default_showavatar == 0) { $avatars_options .= ' checked="checked"'; }
        $avatars_options .= '><label class="radio_label" for="showavatar_no"> '
            . T_('No') . '</label>';
        // Display Name
        $displayname_list = array(
            "1" => T_('First Name'),
            "2" => T_('First & Last Name'),
            "3" => T_('Username')
        );
        $displayname_options = buildHtmlSelectOptions($displayname_list, $default_displayname);
        // Frontpage
        $frontpage_list = array(
            "1" => T_('All (by date)'),
            "2" => T_('Last 5 (by section)')
        );
        $frontpage_options = buildHtmlSelectOptions($frontpage_list, $default_frontpage);
        // Timezone
        $tz_list = array(
            "-12 hours" => T_('(GMT -12:00) Eniwetok, Kwajalein'),
            "-11 hours" => T_('(GMT -11:00) Midway Island, Samoa'),
            "-10 hours" => T_('(GMT -10:00) Hawaii'),
            "-9 hours" => T_('(GMT -9:00) Alaska'),
            "-8 hours" => T_('(GMT -8:00) Pacific Time (US & Canada)'),
            "-7 hours" => T_('(GMT -7:00) Mountain Time (US & Canada)'),
            "-6 hours" => T_('(GMT -6:00) Central Time (US & Canada), Mexico City'),
            "-5 hours" => T_('(GMT -5:00) Eastern Time (US & Canada), Bogota, Lima'),
            "-4 hours" => T_('(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz'),
            "-3 hours -30 minutes" => T_('(GMT -3:30) Newfoundland'),
            "-3 hours" => T_('(GMT -3:00) Brazil, Buenos Aires, Georgetown'),
            "-2 hours" => T_('(GMT -2:00) Mid-Atlantic'),
            "-1 hours" => T_('(GMT -1:00) Azores, Cape Verde Islands'),
            "-0 hours" => T_('(GMT) Western Europe Time, London, Lisbon, Casablanca'),
            "+1 hours" => T_('(GMT +1:00) Brussels, Copenhagen, Madrid, Paris'),
            "+2 hours" => T_('(GMT +2:00) Kaliningrad, South Africa'),
            "+3 hours" => T_('(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburgh'),
            "+3 hours 30 minutes" => T_('(GMT +3:30) Tehran'),
            "+4 hours" => T_('(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi'),
            "+4 hours 30 minutes" => T_('(GMT +4:30) Kabul'),
            "+5 hours" => T_('(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent'),
            "+5 hours 30 minutes" => T_('(GMT +5:30) Bombay, Calcutta, Madras, New Delhi'),
            "+6 hours" => T_('(GMT +6:00) Almaty, Dhaka, Colombo'),
            "+7 hours" => T_('(GMT +7:00) Bangkok, Hanoi, Jakarta'),
            "+8 hours" => T_('(GMT +8:00) Beijing, Perth, Singapore, Hong Kong'),
            "+9 hours" => T_('(GMT +9:00) Tokyo, Seoul, Osaka, Spporo, Yakutsk'),
            "+9 hours 30 minutes" => T_('(GMT +9:30) Adeliaide, Darwin'),
            "+10 hours" => T_('(GMT +10:00) Eastern Australia, Guam, Vladivostok'),
            "+11 hours" => T_('(GMT +11:00) Magadan, Solomon Islands, New Caledonia'),
            "+12 hours" => T_('(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka')
        );
        $tz_options = buildHtmlSelectOptions($tz_list, $default_tz);
        // DST
        $dst_options = '<input type="radio" name="dst" id="dst_on" '
            . 'value="on"';
        if ($default_dst == 1) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_on"> ' . T_('On') . '</label><br>'
            . '<input type="radio" name="dst" id="dst_off" value="off"';
        if ($default_dst == 0) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_off"> ' . T_('Off') . '</label>';
        // Board Sort
        $boardsort_list = array(
            "ASC" => T_('New Messages at Bottom'),
            "DESC" => T_('New Messages at Top')
        );
        $boardsort_options = buildHtmlSelectOptions($boardsort_list, $default_boardsort);
        
        echo '
        <form enctype="multipart/form-data" action="config.php" method="post">
        <fieldset class="default_cfg">
            <legend><span>'.T_('Defaults').'</span></legend>
            <div id="defaults">
                <div class="field-row clearfix">
                    <div class="field-label"><label for="theme"><b>'.T_('Theme').'</b></label></div>
                    <div class="field-widget">
                        <select name="theme" id="theme">
                            '.$theme_options.'
                        </select>
                    </select>
                </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="showavatar"><b>'.T_('Show Avatars').'</b></label></div>
                    <div class="field-widget">
                        '.$avatars_options.'
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="displayname"><b>'.T_('Display Name').'</b></label></div>
                    <div class="field-widget">
                        <select name="displayname" id="displayname" title="'.T_('How do you want your name to display?').'">
                            '.$displayname_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="frontpage"><b>'.T_('Front Page').'</b></label></div>
                    <div class="field-widget">
                        <select name="frontpage" id="frontpage" title="'.T_('How do you want the latest information to display on the homepage?').'">
                            '.$frontpage_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="timezone"><b>'.T_('Time Zone').'</b></label></div>
                    <div class="field-widget">
                        <select name="timezone" id="timezone" title="'.T_('What time zone do you live in?').'">
                            '.$tz_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="dst"><b>'.T_('Daylight Savings Time').'</b></label></div>
                    <div class="field-widget">
                        '.$dst_options.'
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="boardsort"><b>'.T_('Sort Messages').'</b></label></div>
                    <div class="field-widget">
                        <select name="boardsort" id="boardsort" title="'.T_('How do you want messages to display on the Message Board?').'">
                            '.$boardsort_options.'
                        </select>
                    </div>
                </div>
                <p>
                    <input type="submit" id="submit-defaults" name="submit-defaults" value="'.T_('Save').'"/> &nbsp;
                    <input type="checkbox" name="changeAll" id="changeAll"/> 
                    <label for="changeAll">'.T_('Update existing users?').'</label>
                </p>
            </div>
        </fieldset>
        </form>';
    }

    /**
     * getOrderSelectBox 
     * 
     * @param  int  $c 
     * @param  int  $total 
     * @param  int  $selected 
     * @param  int  $start 
     * @return void
     */
    function getOrderSelectBox ($c, $total, $selected, $start = 1)
    {
        $order_options = '<select id="order'.$c.'" name="order'.$c.'">';
        for ($i = $start; $i <= $total; $i++) {
            $order_options .= '
                                    <option value="'.$i.'"';
            if ($i == $selected) {
                $order_options .= ' selected="selected"';
            }
            $order_options .= '>'.$i.'</option>';
        }
        $order_options .= '
                                </select>';
        return $order_options;
    }

    /**
     * displayAdminConfigSections 
     * 
     * @return void
     */
    function displayAdminConfigSections ()
    {
        // Get Navigation Data
        $nav = array();
        $unused = array();
        $sql = "SELECT * FROM `fcms_navigation` WHERE `col` = 4 ORDER BY `order`";
        $this->db2->query($sql) or displaySQLError(
            'Navigation Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = $this->db2->get_row()) {
            array_push($nav, $r);
            if ($r['order'] == 0) {
                array_push($unused, $r);
            }
        }

        echo '
        <form action="config.php?view=sections" method="post">
            <fieldset>
                <legend><span>'.T_('Navigation').'</span></legend>';
        if (count($unused) > 0) {
            echo '
                <p><b>'.T_('Add Optional Sections').'</b></p>
                <p>';
            foreach ($unused AS $r) {
                echo getSectionName($r['link']).' &nbsp;<a class="add" href="?view=sections&amp;add='.$r['id'].'">'.T_('Add').'</a><br/>';
            }
            echo '
                </p>';
        }
        echo '
                <table class="order-nav">
                    <thead>
                        <tr><th>'.T_('Section').'</th><th>'.T_('Order').'</th><th class="remove">'.T_('Remove').'</th></tr>
                    </thead>
                    <tbody>';

        foreach ($nav AS $r) {
            // order = 0 means it's unused
            if ($r['order'] > 0) {
                $del = '<i>'.T_('required').'</i>';
                if ($r['req'] < 1 && usingSection($r['link'])) {
                    $del = '&nbsp;<input class="delbtn" type="submit" name="remove" value="'.$r['id'].'"/>';
                }
                echo '
                        <tr>
                            <td>'.getSectionName($r['link']).'</td>
                            <td>
                                '.$this->getOrderSelectBox($r['id'], 8, $r['order']).'
                            </td>
                            <td class="remove">'.$del.'</td>
                        </tr>';
            }
        }
        echo '
                    </tbody>
                </table>
                <p><input type="submit" id="submit-sections" name="submit-sections" value="' . T_('Save') . '"/></p>
            </fieldset>
        </form>';
    }

    /**
     * displayAdminConfigGallery 
     * 
     * @return void
     */
    function displayAdminConfigGallery ()
    {
        $sql = "SELECT * FROM `fcms_config`";
        $this->db->query($sql) or displaySQLError(
            'Site Info Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        
        $full_size_list = array(
            "0" => T_('Off (2 photos)'),
            "1" => T_('On (3 photos)')
        );
        $full_size_options = buildHtmlSelectOptions($full_size_list, $row['full_size_photos']);
        
        echo '
        <form action="config.php" method="post">
        <fieldset class="gallery_cfg">
            <legend><span>'.T_('Photo Gallery').'</span></legend>
            <div id="gallery">
                <p class="info-alert">
                    '.T_('By default, Full Sized Photos is turned off to save on storage space and bandwidth.  Turning this feature on can eat up significant space and bandwith.').'
                </p>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="full_size_photos"><b>'.T_('Full Size Photos').'</b></label></div>
                    <div class="field-widget">
                        <select name="full_size_photos">
                            '.$full_size_options.'
                        </select>
                    </div>
                </div>
                <p><input type="submit" id="submit-gallery" name="submit-gallery" value="'.T_('Save').'"/></p>
            </div>
        </fieldset>
        </form>';
    }

    /**
     * displaySectionDropdown 
     * 
     * @param string $which_nav 
     * @param string $which_selected 
     * @param string $num 
     * @return void
     */
    function displaySectionDropdown ($which_nav, $which_selected, $num)
    { 
        echo '
                <div class="field-row clearfix">
                    <div class="field-label"><label for="'.$which_nav.'"><b>'.T_('Section').' '.$num.'</b></label></div>
                    <div class="field-widget">
                        <select name="'.$which_nav.'">';
        if (tableExists('fcms_news')) {
            echo '<option value="familynews"';
            if ($which_selected == 'familynews') {
                echo ' selected="selected"';
            }
            echo '>' . T_('Family News') . '</option>';
        }
        if (tableExists('fcms_recipes')) {
            echo '<option value="recipes"';
            if ($which_selected == 'recipes') {
                echo ' selected="selected"';
            }
            echo '>' . T_('Recipes') . '</option>';
        }
        if (tableExists('fcms_documents')) {
            echo '<option value="documents"';
            if ($which_selected == 'documents') {
                echo ' selected="selected"';
            }
            echo '>' . T_('Documents') . '</option>';
        }
        if (tableExists('fcms_prayers')) {
            echo '<option value="prayers"';
            if ($which_selected == 'prayers') {
                echo ' selected="selected"';
            }
            echo '>' . T_('Prayer Concerns') . '</option>';
        }
        $i = substr($which_nav, 7);
        echo '<option value="none'.$i.'"';
        $pos = strpos($which_selected, "none");
        if ($pos !== false) {
            echo ' selected="selected"';
        }
        echo '>' . T_('none') . '</option>
                        </select>
                    </div>
                </div>';
    }

} ?>
