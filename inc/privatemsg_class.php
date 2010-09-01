<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

class PrivateMessage {

    var $db;
    var $db2;
    var $tz_offset;
    var $current_user_id;

    function PrivateMessage ($current_user_id, $type, $host, $database, $user, $pass)
    {
        $this->current_user_id = $current_user_id;
        $this->db = new database($type, $host, $database, $user, $pass);
        $this->db2 = new database($type, $host, $database, $user, $pass);
        $this->db->query("SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id") or die('<h1>Timezone Error (profile.class.php 16)</h1>' . mysql_error());
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
    }

    function displayInbox ()
    {
        $locale = new Locale();
        echo '
            <form method="post" action="privatemsg.php">
                <table id="pm" cellpadding="0" cellspacing="0">
                    <tr><th colspan="5" class="pm_header">'.T_('Private Messages (PM)').' - '.T_('Inbox').'</th></tr>
                    <tr>
                        <th colspan="2">'.T_('Subject').'</th>
                        <th>'.T_('From').'</th>
                        <th>'.T_('Received').'</th>
                        <th>&nbsp;</th>
                    </tr>';
        $this->db->query("SELECT * FROM `fcms_privatemsg` WHERE `to` = " . $this->current_user_id);
        while ($r = $this->db->get_row()) {
            $date = $locale->fixDate(T_('M. j, Y, g:i a'), $this->tz_offset, $r['date']);
            $class = '';
            if ($r['read'] < 1) {
                $class = " class=\"new\"";
            }
            echo '
                    <tr'.$class.'>
                        <td class="img"></td>
                        <td><a href="?pm='.$r['id'].'">'.$r['title'].'</a></td>
                        <td>'.getUserDisplayName($r['from']).'</td>
                        <td>'.$date.'</td>
                        <td><input type="checkbox" name="del[]" value="'.$r['id'].'"/></td>
                    </tr>';
        }
        echo '
                    <tr>
                        <th colspan="5" class="pm_footer">
                            <input type="submit" name="delete" value="'.T_('Delete Selected').'"/>
                        </th>
                    </tr>
                </table>
            </form>';
    }

    function displaySentFolder ()
    {
        $locale = new Locale();
        echo '
                <table id="pm" cellpadding="0" cellspacing="0">
                    <tr><th colspan="5" class="pm_header">'.T_('Private Messages (PM)').' - '.T_('Sent').'</th></tr>
                    <tr>
                        <th>'.T_('To').'</th>
                        <th>'.T_('Subject').'</th>
                        <th>'.T_('Sent').'</th>
                    </tr>';
        $this->db->query("SELECT * FROM `fcms_privatemsg` WHERE `from` = " . $this->current_user_id);
        while ($r = $this->db->get_row()) {
            $date = $locale->fixDate(T_('M. j, Y, g:i a'), $this->tz_offset, $r['date']);
            echo '
                    <tr>
                        <td>'.getUserDisplayName($r['to']).'</td>
                        <td><a href="?sent='.$r['id'].'">'.$r['title'].'</a></td>
                        <td>'.$date.'</td>
                    </tr>';
        }
        echo '
                    <tr><th colspan="5" class="pm_footer">&nbsp;</th></tr>
                </table>';
    }

    function displayPM ($id)
    {
        $locale = new Locale();
        $this->db->query("SELECT * FROM `fcms_privatemsg` WHERE `id` = $id AND `to` = " . $this->current_user_id);
        if ($this->db->count_rows() > 0) { 
            $r = $this->db->get_row();
            $this->db->query("UPDATE `fcms_privatemsg` SET `read` = '1' WHERE `id` = $id");
            $date = $locale->fixDate(T_('n/j/Y g:i a'), $this->tz_offset, $r['date']);
            echo '
            <div id="pm_msg">
                <b>'.T_('Received').':</b> '.$date.'<br/>
                <b>'.T_('From').':</b> '.getUserDisplayName($r['from']).'<br/>
                <b>'.T_('Subject').':</b> '.$r['title'].'<br/>
                <p>
                    '.parse($r['msg']).'
                </p>
                <a href="?compose=new&amp;id='.$r['from'].'&amp;title='.htmlentities($r['title'], ENT_COMPAT, 'UTF-8').'">'.T_('Reply').'
            </div>';
        } else {
            echo '
            <p class="error">
                '.T_('The PM you are trying to view either doesn\'t exist or you don\'t have permission to view it.').'
            </p>';
        }
    }

    /*
     *  displaySentPM
     *
     *  Displays all messages the current user has sent, if any
     *
     *  @param      $id     the id of the sent message
     *  @return     none
     */
    function displaySentPM ($id)
    {
        $locale = new Locale();
        $this->db->query("SELECT * FROM `fcms_privatemsg` WHERE `id` = $id AND `from` = " . $this->current_user_id);
        if ($this->db->count_rows() > 0) { 
            $r = $this->db->get_row();
            $date = $locale->fixDate(T_('n/j/Y g:i a'), $this->tz_offset, $r['date']);
            echo '
            <div id="pm_msg">
                <b>'.T_('Sent').':</b> '.$date.'<br/>
                <b>'.T_('To').':</b> '.getUserDisplayName($r['to']).'<br/>
                <b>'.T_('Subject').':</b> '.$r['title'].'<br/>
                <p>
                    '.parse($r['msg']).'
                </p>
            </div>';
        } else {
            echo '
            <p class="error">
                '.T_('The PM you are trying to view either doesn\'t exist or you don\'t have permission to view it.').'
            </p>';
        }
    }

    function displayNewMessageForm ($id = '', $title = '')
    {
        $titleVal = strlen($title) > 0 ? 'RE: '.htmlentities($title, ENT_COMPAT, 'UTF-8') : '';
        $this->db->query("SELECT * FROM `fcms_users` WHERE `activated` > 0");
        while ($r = $this->db->get_row()) {
            $displayNameList[$r['id']] = getUserDisplayName($r['id']);
        }
        asort($displayNameList);
        $user_options = buildHtmlSelectOptions($displayNameList, $id);
        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <script type="text/javascript" src="inc/messageboard.inc.js"></script>
            <form method="post" id="newpmform" action="privatemsg.php">
                <fieldset>
                    <legend><span>'.T_('New PM').'</span></legend>
                    <div>
                        <label for="title">'.T_('Subject').'</label>: 
                        <input type="text" id="title" name="title" size="50" value="'.$titleVal.'"/>
                    </div><br/>
                    <script type="text/javascript">
                        var ftitle = new LiveValidation(\'title\', { onlyOnSubmit: true });
                        ftitle.add(Validate.Presence, { failureMessage: "" });
                    </script>
                    <div>
                        <label for="to">'.T_('To').'</label>: 
                        <select name="to">
                            '.$user_options.'
                        </select>
                    </div><br/>
                    <script type="text/javascript">var bb = new BBCode();</script>';
        displayMBToolbar();
        echo '
                    <div><textarea name="post" id="post" rows="10" cols="63"></textarea></div>
                    <script type="text/javascript">bb.init(\'post\');</script>
                    <script type="text/javascript">
                        var fpost = new LiveValidation(\'post\', { onlyOnSubmit: true });
                        fpost.add(Validate.Presence, { failureMessage: "" });
                    </script>
                    <p>
                        <input class="sub1" type="submit" name="submit" value="'.T_('Send').'"/> &nbsp;
                        <a href="privatemsg.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>
            <p>&nbsp;</p>';
    }

} ?>
