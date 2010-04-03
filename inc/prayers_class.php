<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

class Prayers {

    var $db;
    var $db2;
    var $tz_offset;
    var $current_user_id;

    function Prayers ($current_user_id, $type, $host, $database, $user, $pass)
    {
        $this->current_user_id = $current_user_id;
        $this->db = new database($type, $host, $database, $user, $pass);
        $this->db2 = new database($type, $host, $database, $user, $pass);
        $this->db->query("SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id") or die('<h1>Timezone Error (prayers_class.php 17)</h1>' . mysql_error());
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
    }

    function showPrayers ($page = '1')
    {
        $locale = new Locale();
        $from = (($page * 5) - 5); 
        $sql = "SELECT p.`id`, `for`, `desc`, `user`, `date` 
                FROM `fcms_prayers` AS p, `fcms_users` AS u 
                WHERE u.`id` = p.`user` 
                ORDER BY `date` DESC 
                LIMIT " . $from . ", 5";
        $this->db->query($sql) or displaySQLError(
                'Prayers Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
        if ($this->db->count_rows() > 0) {
            while($r = $this->db->get_row()) {
                $date = $locale->fixDate(_('F j, Y, g:i a'), $this->tz_offset, $r['date']);
                $displayname = getUserDisplayName($r['user']);

                // TODO
                // Get rid of the form inside h4
                echo '
            <hr/>
            <div>
                <h4>
                '.$date;
                    if ($this->current_user_id == $r['user'] || checkAccess($this->current_user_id) < 2) {
                        echo ' &nbsp;
                    <form method="post" action="prayers.php">
                        <div>
                            <input type="hidden" name="id" value="'.$r['id'].'"/>
                            <input type="hidden" name="for" value="'.htmlentities($r['for'], ENT_COMPAT, 'UTF-8').'"/>
                            <input type="hidden" name="desc" value="'.htmlentities($r['desc'], ENT_COMPAT, 'UTF-8').'"/>
                            <input type="submit" name="editprayer" value="'._('Edit').'" class="editbtn" title="'._('Edit this Prayer Concern').'"/>
                        </div>
                    </form>';
                    }
                    if (checkAccess($this->current_user_id) < 2) {
                        echo ' &nbsp;
                    <form class="delform" method="post" action="prayers.php">
                        <div>
                            <input type="hidden" name="id" value="'.$r['id'].'"/>
                            <input type="submit" name="delprayer" value="'._('Delete').'" class="delbtn" title="'._('Delete this Prayer Concern').'"/>
                        </div>
                    </form>';
                    }
                echo '
                </h4>
                <b>'.sprintf(_('%s asks that you please pray for...'), '<a href="profile.php?member='.$r['user'].'">'.$displayname.'</a>').'</b><br/>
                &nbsp;&nbsp;&nbsp;&nbsp;'.$r['for'].'<br/><br/>
                <b>'._('Because...').'</b><br/>
                &nbsp;&nbsp;&nbsp;&nbsp;
                '.parse($r['desc']).'
            </div>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p>';

            }

            // Display Pagination
            $sql = "SELECT count(`id`) AS c FROM `fcms_prayers`";
            $this->db2->query($sql) or displaySQLError(
                'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            $r = $this->db2->get_row();
            $prayercount = $r['c'];
            $total_pages = ceil($prayercount / 5); 
            displayPagination ('prayers.php', $page, $total_pages);
        } else {
            echo '
            <div class="info-alert">
                <h2>'._('Welcome to the Prayer Concerns section.').'</h2>
                <p><i>'._('Currently no one has added any Prayer Concerns.').'</i></p>
                <p><a href="?addconcern=yes">'._('Add a new Prayer Concern').'</a></p>
            </div>';
        }
    }

    function displayForm ($type, $id = '0', $for = 'error', $desc = 'error')
    {
        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>';
        if ($type == 'edit') {
            echo '
            <form method="post" name="editform" action="prayers.php">
                <fieldset>
                    <legend><span>'._('Edit Prayer Concern').'</span></legend>';
        } else {
            echo '
            <form method="post" name="addform" action="prayers.php">
                <fieldset>
                    <legend><span>'._('Add Prayer Concern').'</span></legend>';
        }
        echo '
                    <div>
                        <label for="for">'._('Pray For').'</label>: 
                        <input type="text" name="for" id="for" size="50"';
        if ($type == 'edit') { echo " value=\"".htmlentities($for, ENT_COMPAT, 'UTF-8')."\""; }
        echo '/>
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
                        <input class="sub1" type="submit" name="submitadd" value="'._('Add').'"/> &nbsp;
                        <a href="prayers.php">'._('Cancel').'</a>
                    </div>';
        } else {
            echo '
                    <div>
                        <input type="hidden" name="id" value="'.$id.'"/>
                        <input class="sub1" type="submit" name="submitedit" value="'._('Edit').'"/> &nbsp;
                        <a href="prayers.php">'._('Cancel').'</a>
                    </div>';
        }
        echo '
                </fieldset>
            </form>';
    }

    function displayWhatsNewPrayers ()
    {
        $locale = new Locale();
        $today = date('Y-m-d');
        $tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
        $this->db->query("SELECT * FROM `fcms_prayers` WHERE `date` >= DATE_SUB(CURDATE() , INTERVAL 30 DAY) ORDER BY `date` DESC LIMIT 0 , 5");
        if ($this->db->count_rows() > 0) {
            echo '
            <h3>'._('Prayer Concerns').'</h3>
            <ul>';
            while ($r = $this->db->get_row()) {
                $displayname = getUserDisplayName($r['user']);
                $for = $r['for'];
                $date = $locale->fixDate(_('M. j, Y, g:i a'), $this->tz_offset, $r['date']);
                if (
                    strtotime($r['date']) >= strtotime($today) && 
                    strtotime($r['date']) > $tomorrow
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
                    <a href="prayers.php">'.$for.'</a> - 
                    <a class="u" href="profile.php?member='.$r['user'].'">'.$displayname.'</a>
                </li>';
            }
            echo '
            </ul>';
        }
    }

} ?>
