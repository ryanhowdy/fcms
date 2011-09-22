<?php
include_once('utils.php');
include_once('datetime.php');

/**
 * PrivateMessage 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class PrivateMessage {

    var $db;
    var $db2;
    var $tzOffset;
    var $currentUserId;

    /**
     * PrivateMessage 
     * 
     * @param   int         $currentUserId 
     *
     * @return  void
     */
    function PrivateMessage ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db  = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $this->db2 = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->tzOffset      = getTimezone($this->currentUserId);
    }

    /**
     * displayInbox 
     * 
     * @return  void
     */
    function displayInbox ()
    {
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
        $sql = "SELECT * 
                FROM `fcms_privatemsg` 
                WHERE `to` = '" . $this->currentUserId . "'";
        $this->db->query($sql) or displaySQLError(
            'Private Msg Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = $this->db->get_row()) {
            $date = fixDate(T_('M. j, Y, g:i a'), $this->tzOffset, $r['date']);
            $class = '';
            if ($r['read'] < 1) {
                $class = " class=\"new\"";
            }
            echo '
                    <tr'.$class.'>
                        <td class="img"></td>
                        <td><a href="?pm='.(int)$r['id'].'">'.cleanOutput($r['title']).'</a></td>
                        <td>'.getUserDisplayName($r['from']).'</td>
                        <td>'.$date.'</td>
                        <td><input type="checkbox" name="del[]" value="'.(int)$r['id'].'"/></td>
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

    /**
     * displaySentFolder 
     * 
     * @return  void
     */
    function displaySentFolder ()
    {
        echo '
                <table id="pm" cellpadding="0" cellspacing="0">
                    <tr><th colspan="5" class="pm_header">'.T_('Private Messages (PM)').' - '.T_('Sent').'</th></tr>
                    <tr>
                        <th>'.T_('To').'</th>
                        <th>'.T_('Subject').'</th>
                        <th>'.T_('Sent').'</th>
                    </tr>';
        $sql = "SELECT * 
                FROM `fcms_privatemsg` 
                WHERE `from` = '" . $this->currentUserId . "'";
        $this->db->query($sql) or displaySQLError(
            'Private Msg Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = $this->db->get_row()) {
            $date = fixDate(T_('M. j, Y, g:i a'), $this->tzOffset, $r['date']);
            echo '
                    <tr>
                        <td>'.getUserDisplayName($r['to']).'</td>
                        <td><a href="?sent='.(int)$r['id'].'">'.cleanOutput($r['title']).'</a></td>
                        <td>'.$date.'</td>
                    </tr>';
        }
        echo '
                    <tr><th colspan="5" class="pm_footer">&nbsp;</th></tr>
                </table>';
    }

    /**
     * displayPM 
     * 
     * @param   int     $id 
     * @return  void
     */
    function displayPM ($id)
    {
        $id = cleanInput($id, 'int');

        $sql = "SELECT * 
                FROM `fcms_privatemsg` 
                WHERE `id` = '$id' 
                AND `to` = '" . $this->currentUserId . "'";
        $this->db->query($sql) or displaySQLError(
            'Private Msg Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) { 
            $r = $this->db->get_row();
            $sql = "UPDATE `fcms_privatemsg` 
                    SET `read` = '1' 
                    WHERE `id` = '$id'";
            $this->db->query($sql) or displaySQLError(
                'PM Read Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $date = fixDate(T_('n/j/Y g:i a'), $this->tzOffset, $r['date']);
            echo '
            <div id="pm_msg">
                <b>'.T_('Received').':</b> '.$date.'<br/>
                <b>'.T_('From').':</b> '.getUserDisplayName($r['from']).'<br/>
                <b>'.T_('Subject').':</b> '.cleanOutput($r['title']).'<br/>
                <p>
                    '.parse($r['msg']).'
                </p>
                <a href="?compose=new&amp;id='.(int)$r['from'].'&amp;title='.cleanOutput($r['title']).'">'.T_('Reply').'
            </div>';
        } else {
            echo '
            <p class="error">
                '.T_('The PM you are trying to view either doesn\'t exist or you don\'t have permission to view it.').'
            </p>';
        }
    }

    /**
     * displaySentPM 
     * 
     * Displays all messages the current user has sent, if any
     *
     * @param   string  $id 
     * @return  void
     */
    function displaySentPM ($id)
    {
        $sql = "SELECT * 
                FROM `fcms_privatemsg` 
                WHERE `id` = '$id' 
                AND `from` = '" . $this->currentUserId . "'";
        $this->db->query($sql) or displaySQLError(
            'Private Msg Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) { 
            $r = $this->db->get_row();
            $date = fixDate(T_('n/j/Y g:i a'), $this->tzOffset, $r['date']);
            echo '
            <div id="pm_msg">
                <b>'.T_('Sent').':</b> '.$date.'<br/>
                <b>'.T_('To').':</b> '.getUserDisplayName($r['to']).'<br/>
                <b>'.T_('Subject').':</b> '.cleanOutput($r['title']).'<br/>
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

    /**
     * displayNewMessageForm 
     * 
     * @param   mixed   $id 
     * @param   mixed   $title 
     * @return  void
     */
    function displayNewMessageForm ($id = '', $title = '')
    {
        $titleVal = strlen($title) > 0 ? 'RE: '.cleanOutput($title) : '';

        $sql = "SELECT `id`
                FROM `fcms_users` 
                WHERE `activated` > 0
                AND `password` != 'NONMEMBER'";
        $this->db->query($sql) or displaySQLError(
            'Active User Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        while ($r = $this->db->get_row()) {
            $displayNameList[$r['id']] = getUserDisplayName($r['id'], 2);
        }

        asort($displayNameList);

        $user_options = buildHtmlSelectOptions($displayNameList, $id);

        echo '
            <script type="text/javascript" src="inc/js/livevalidation.js"></script>
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
        displayBBCodeToolbar();
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

}
