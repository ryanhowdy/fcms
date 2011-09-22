<?php
include_once('database_class.php');
include_once('utils.php');
include_once('datetime.php');

/**
 * Prayers 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Prayers
{
    var $db;
    var $db2;
    var $tzOffset;
    var $currentUserId;

    /**
     * Prayers 
     * 
     * @param   int     $currentUserId 
     *
     * @return  void
     */
    function Prayers ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db  = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $this->db2 = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->tzOffset      = getTimezone($this->currentUserId);
    }

    /**
     * showPrayers 
     * 
     * @param   int     $page 
     * @return  void
     */
    function showPrayers ($page = 1)
    {
        $from = (($page * 5) - 5); 
        $sql = "SELECT p.`id`, `for`, `desc`, `user`, `date` 
                FROM `fcms_prayers` AS p, `fcms_users` AS u 
                WHERE u.`id` = p.`user` 
                ORDER BY `date` DESC 
                LIMIT $from, 5";
        $this->db->query($sql) or displaySQLError(
            'Prayers Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            while($r = $this->db->get_row()) {
                $date = fixDate(T_('F j, Y, g:i a'), $this->tzOffset, $r['date']);
                $displayname = getUserDisplayName($r['user']);

                // TODO
                // Get rid of the form inside h4
                echo '
            <hr/>
            <div>
                <h4>
                '.$date;
                    if ($this->currentUserId == $r['user'] || checkAccess($this->currentUserId) < 2) {
                        echo ' &nbsp;
                    <form method="post" action="prayers.php">
                        <div>
                            <input type="hidden" name="id" value="'.(int)$r['id'].'"/>
                            <input type="hidden" name="for" value="'.cleanOutput($r['for']).'"/>
                            <input type="hidden" name="desc" value="'.cleanOutput($r['desc']).'"/>
                            <input type="submit" name="editprayer" value="'.T_('Edit').'" class="editbtn" title="'.T_('Edit this Prayer Concern').'"/>
                        </div>
                    </form>';
                    }
                    if (checkAccess($this->currentUserId) < 2) {
                        echo ' &nbsp;
                    <form class="delform" method="post" action="prayers.php">
                        <div>
                            <input type="hidden" name="id" value="'.(int)$r['id'].'"/>
                            <input type="submit" name="delprayer" value="'.T_('Delete').'" class="delbtn" title="'.T_('Delete this Prayer Concern').'"/>
                        </div>
                    </form>';
                    }
                echo '
                </h4>
                <b>'.sprintf(T_('%s asks that you please pray for...'), '<a href="profile.php?member='.(int)$r['user'].'">'.$displayname.'</a>').'</b><br/>
                &nbsp;&nbsp;&nbsp;&nbsp;'.$r['for'].'<br/><br/>
                <b>'.T_('Because...').'</b><br/>
                &nbsp;&nbsp;&nbsp;&nbsp;
                '.parse($r['desc']).'
            </div>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>
            <p>&nbsp;</p>';

            }

            // Display Pagination
            $sql = "SELECT count(`id`) AS c FROM `fcms_prayers`";
            $this->db2->query($sql) or displaySQLError(
                'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $r = $this->db2->get_row();
            $prayercount = (int)$r['c'];
            $total_pages = ceil($prayercount / 5); 
            displayPagination ('prayers.php', $page, $total_pages);
        } else {
            echo '
            <div class="info-alert">
                <h2>'.T_('Welcome to the Prayer Concerns section.').'</h2>
                <p><i>'.T_('Currently no one has added any Prayer Concerns.').'</i></p>
                <p><a href="?addconcern=yes">'.T_('Add a new Prayer Concern').'</a></p>
            </div>';
        }
    }

    /**
     * displayForm 
     * 
     * @param   string  $type 
     * @param   int     $id 
     * @param   string  $for 
     * @param   string  $desc 
     * @return  void
     */
    function displayForm ($type, $id = 0, $for = 'error', $desc = 'error')
    {
        echo '
            <script type="text/javascript" src="inc/js/livevalidation.js"></script>';
        if ($type == 'edit') {
            $for = cleanInput($for);
            echo '
            <form method="post" name="editform" action="prayers.php">
                <fieldset>
                    <legend><span>'.T_('Edit Prayer Concern').'</span></legend>';
        } else {
            $for = '';
            echo '
            <form method="post" name="addform" action="prayers.php">
                <fieldset>
                    <legend><span>'.T_('Add Prayer Concern').'</span></legend>';
        }
        echo '
                    <div>
                        <label for="for">'.T_('Pray For').'</label>: 
                        <input type="text" name="for" id="for" size="50" value="'.$for.'"/>
                    </div><br/>
                    <script type="text/javascript">
                        var ffor = new LiveValidation(\'for\', { onlyOnSubmit: true });
                        ffor.add(Validate.Presence, {failureMessage: ""});
                    </script>
                    <div>
                        <textarea name="desc" id="desc" rows="10" cols="63">';
        if ($type == 'edit') { echo $desc; }
        echo '</textarea>
                    </div>
                    <script type="text/javascript">
                        var fdesc = new LiveValidation(\'desc\', { onlyOnSubmit: "" });
                        fdesc.add(Validate.Presence, {failureMessage: ""});
                    </script>';
        if ($type == 'add') {
            echo '
                    <div>
                        <input class="sub1" type="submit" name="submitadd" value="'.T_('Add').'"/> &nbsp;
                        <a href="prayers.php">'.T_('Cancel').'</a>
                    </div>';
        } else {
            echo '
                    <div>
                        <input type="hidden" name="id" value="'.(int)$id.'"/>
                        <input class="sub1" type="submit" name="submitedit" value="'.T_('Edit').'"/> &nbsp;
                        <a href="prayers.php">'.T_('Cancel').'</a>
                    </div>';
        }
        echo '
                </fieldset>
            </form>';
    }

}
