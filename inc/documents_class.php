<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

class Documents {

    var $db;
    var $db2;
    var $tz_offset;
    var $cur_user_id;

    function Documents ($current_user_id, $type, $host, $database, $user, $pass)
    {
        $this->cur_user_id = $current_user_id;
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
                        <th class="sortfirstasc">'._('Document').'</th>
                        <th>'._('Description').'</th>
                        <th>'._('Uploaded By').'</th>
                        <th>'._('Date Added').'</th>
                    </tr>
                </thead>
                <tbody>';
            while($r = $this->db->get_row()) {
                $date = $locale->fixDate(_('m/d/Y h:ia'), $this->tz_offset, $r['date']);
                echo '
                    <tr>
                        <td>
                            <a href="?download='.$r['name'].'">'.$r['name'].'</a>';
                if (checkAccess($_SESSION['login_id']) < 3 || $_SESSION['login_id'] == $r['user']) {
                    echo '&nbsp;
                            <form method="post" action="documents.php">
                                <div>
                                    <input type="hidden" name="id" value="'.$r['id'].'"/>
                                    <input type="hidden" name="name" value="'.$r['name'].'"/>
                                    <input type="submit" name="deldoc" value="'._('Delete').'" class="delbtn" title="'._('Delete this Document').'"/>
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
                <h2>'._('Welcome to the Documents Section.').'</h2>
                <p><i>'._('Currently no one is sharing any documents.').'</i></p>
                <p><a href="?adddoc=yes">'._('Upload a document').'</a></p>
            </div>';
        }
    }

    function displayForm ()
    {
        echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <form method="post" enctype="multipart/form-data" name="addform" action="documents.php">
                <fieldset>
                    <legend><span>'._('Upload Document').'</span></legend>
                    <p>
                        <label for="doc">'._('Document').'</label>: 
                        <input type="file" name="doc" id="doc" size="30"/>
                    </p>
                    <p>
                        <label for="desc">'._('Description').'</label>: 
                        <input type="text" name="desc" id="desc" size="60"/>
                    </p>
                    <script type="text/javascript">
                        var fdesc = new LiveValidation(\'desc\', { onlyOnSubmit: true});
                        fdesc.add(Validate.Presence, {failureMessage: "'._('Required').'"});
                    </script>
                    <p>
                        <input class="sub1" type="submit" name="submitadd" value="'._('Add').'"/> &nbsp;
                        <a href="documents.php">'._('Cancel').'</a>
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
            <p class="error-alert">'.sprintf(_('Document %s exceeds the maximum file size allowed by your PHP settings.'), $filename).'</p>';
            return false;
        } else if (
            !array_key_exists($filetype, $valid_docs) ||
            !in_array($ext, $valid_docs)
        ) {
            echo '
            <p class="error-alert">
                '.sprintf(_('Document %s is not allowed.'), $filename).'<br/>
                '._('Documents must be of type (.DOC, .TXT, .XSL, .ZIP, .RTF, .PPT, .PDF).').'
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
        $today = date('Y-m-d');
        $tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
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
            <h3>'._('Documents').'</h3>
            <ul>';
            while ($r = $this->db->get_row()) {
                $name = $r['name'];
                $displayname = getUserDisplayName($r['user']);
                $date = $locale->fixDate(_('M. j, Y, g:i a'), $this->tz_offset, $r['date']);
                if (
                    strtotime($r['date']) >= strtotime($today) && 
                    strtotime($r['date']) > $tomorrow
                ) {
                    $date = _('Today');
                    $d = ' class="today"';
                } else {
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
