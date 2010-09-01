<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

class Documents {

    var $db;
    var $db2;
    var $tz_offset;
    var $current_user_id;

    function Documents ($current_user_id, $type, $host, $database, $user, $pass)
    {
        $this->current_user_id = $current_user_id;
        $this->db = new database($type, $host, $database, $user, $pass);
        $this->db2 = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id";
        $this->db->query($sql) or displaySQLError(
            'Timezone Error', 'inc/documents_class.php [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];
    }

    function showDocuments ($page = '1')
    {
        $locale = new Locale();
        $from = (($page * 25) - 25); 
        $sql = "SELECT `id`, `name`, `description`, `user`, `date` 
                FROM `fcms_documents` AS d 
                ORDER BY `date` DESC 
                LIMIT " . $from . ", 25";
        $this->db->query($sql) or displaySQLError(
            'Get Documents Error', 'inc/documents_class.php [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo '
            <script type="text/javascript" src="inc/tablesort.js"></script>
            <table id="docs" class="sortable">
                <thead>
                    <tr>
                        <th class="sortfirstasc">'.T_('Document').'</th>
                        <th>'.T_('Description').'</th>
                        <th>'.T_('Uploaded By').'</th>
                        <th>'.T_('Date Added').'</th>
                    </tr>
                </thead>
                <tbody>';
            while($r = $this->db->get_row()) {
                $date = $locale->fixDate(T_('m/d/Y h:ia'), $this->tz_offset, $r['date']);
                echo '
                    <tr>
                        <td>
                            <a href="?download='.$r['name'].'">'.$r['name'].'</a>';
                if (checkAccess($this->current_user_id) < 3 || $this->current_user_id == $r['user']) {
                    echo '&nbsp;
                            <form method="post" action="documents.php">
                                <div>
                                    <input type="hidden" name="id" value="'.$r['id'].'"/>
                                    <input type="hidden" name="name" value="'.$r['name'].'"/>
                                    <input type="submit" name="deldoc" value="'.T_('Delete').'" class="delbtn" title="'.T_('Delete this Document').'"/>
                                </div>
                            </form>';
                }
                echo '
                        </td>
                        <td>'.$r['description'].'</td>
                        <td>'.getUserDisplayName($r['user']).'</td>
                        <td>'.$date.'</td>
                    </tr>';
            }
            echo '
                </tbody>
            </table>';

            // Pages
            $sql = "SELECT count(`id`) AS c FROM `fcms_documents`";
            $this->db2->query($sql) or displaySQLError(
                'Count Documents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            while ($r = $this->db2->get_row()) { $docscount = $r['c']; }
            $total_pages = ceil($docscount / 25); 
            displayPages('documents.php', $page, $total_pages);
        } else {
            echo '
            <div class="info-alert">
                <h2>'.T_('Welcome to the Documents Section.').'</h2>
                <p><i>'.T_('Currently no one is sharing any documents.').'</i></p>
                <p><a href="?adddoc=yes">'.T_('Upload a document').'</a></p>
            </div>';
        }
    }

    function displayForm ()
    {
        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <form method="post" enctype="multipart/form-data" name="addform" action="documents.php">
                <fieldset>
                    <legend><span>'.T_('Upload Document').'</span></legend>
                    <p>
                        <label for="doc">'.T_('Document').'</label>: 
                        <input type="file" name="doc" id="doc" size="30"/>
                    </p>
                    <p>
                        <label for="desc">'.T_('Description').'</label>: 
                        <input type="text" name="desc" id="desc" size="60"/>
                    </p>
                    <script type="text/javascript">
                        var fdesc = new LiveValidation(\'desc\', { onlyOnSubmit: true});
                        fdesc.add(Validate.Presence, {failureMessage: "'.T_('Required').'"});
                    </script>
                    <p>
                        <input class="sub1" type="submit" name="submitadd" value="'.T_('Add').'"/> &nbsp;
                        <a href="documents.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    function uploadDocument ($file, $filename)
    {
        $valid_docs = array(
            'application/msword'            => 'doc', 
            'text/plain'                    => 'txt', 
            'application/excel'             => 'xsl', 
            'application/vnd.ms-excel'      => 'xsl', 
            'application/x-msexcel'         => 'xsl', 
            'application/x-compressed'      => 'zip', 
            'application/x-zip-compressed'  => 'zip', 
            'application/zip'               => 'zip', 
            'multipart/x-zip'               => 'zip', 
            'application/rtf'               => 'rtf', 
            'application/x-rtf'             => 'rtf', 
            'text/richtext'                 => 'rtf', 
            'application/mspowerpoint'      => 'ppt', 
            'application/powerpoint'        => 'ppt', 
            'application/vnd.ms-powerpoint' => 'ppt', 
            'application/x-mspowerpoint'    => 'ppt', 
            'application/x-excel'           => 'xsl', 
            'application/pdf'               => 'pdf'
        );
        $filetmpname = $file['tmp_name'];
        $filetype = $file['type'];
        $error = $file['error'];
        $ext = explode(".", strtolower($filename));
        $ext = end($ext);
        if ($error == 1) {
            echo '
            <p class="error-alert">'.sprintf(T_('Document %s exceeds the maximum file size allowed by your PHP settings.'), $filename).'</p>';
            return false;
        } else if (
            !array_key_exists($filetype, $valid_docs) ||
            !in_array($ext, $valid_docs)
        ) {
            echo '
            <p class="error-alert">
                '.sprintf(T_('Document %s is not allowed.'), $filename).'<br/>
                '.T_('Documents must be of type (.DOC, .TXT, .XSL, .ZIP, .RTF, .PPT, .PDF).').'
            </p>';
            return false;
        } else {
            copy($filetmpname, "gallery/documents/$filename");
            return true;
        }
    }

    function displayWhatsNewDocuments ()
    {
        $locale = new Locale();
        $today_start = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '000000';
        $today_end = $locale->fixDate('Ymd', $this->tz_offset, gmdate('Y-m-d H:i:s')) . '235959';

        $sql = "SELECT * 
                FROM `fcms_documents` 
                WHERE `date` >= DATE_SUB(CURDATE() , INTERVAL 30 DAY) 
                ORDER BY `date` DESC 
                LIMIT 0 , 5";
        $this->db->query($sql) or displaySQLError(
            'What\'s New Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo '
            <h3>'.T_('Documents').'</h3>
            <ul>';
            while ($r = $this->db->get_row()) {
                $name = $r['name'];
                $displayname = getUserDisplayName($r['user']);
                $date = $locale->fixDate('YmdHis', $this->tz_offset, $r['date']);
                if ($date >= $today_start && $date <= $today_end) {
                    $date = T_('Today');
                    $d = ' class="today"';
                } else {
                    $date = $locale->fixDate(T_('M. j, Y, g:i a'), $this->tz_offset, $r['date']);
                    $d = '';
                }
                echo '
                <li>
                    <div'.$d.'>'.$date.'</div>
                    <a href="documents.php">'.$name.'</a> - 
                    <a class="u" href="profile.php?member='.$r['user'].'">'.$displayname.'</a>
                </li>';
            }
            echo '
            </ul>';
        }
    }

} ?>
