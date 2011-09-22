<?php
include_once('database_class.php');
include_once('utils.php');
include_once('datetime.php');

/**
 * Documents 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Documents {

    var $db;
    var $db2;
    var $tzOffset;
    var $currentUserId;

    /**
     * Documents 
     * 
     * @param  int      $currentUserId 
     *
     * @return void
     */
    function Documents ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db  = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $this->db2 = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->tzOffset      = getTimezone($this->currentUserId);
    }

    /**
     * showDocuments 
     * 
     * @param  int  $page 
     * @return void
     */
    function showDocuments ($page = 1)
    {
        $from = (($page * 25) - 25); 
        $sql = "SELECT `id`, `name`, `description`, `user`, `date` 
                FROM `fcms_documents` AS d 
                ORDER BY `date` DESC 
                LIMIT " . $from . ", 25";
        $this->db->query($sql) or displaySQLError(
            'Get Documents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo '
            <script type="text/javascript" src="inc/js/tablesort.js"></script>
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

            while ($r = $this->db->get_row()) {
                $date = fixDate(T_('m/d/Y h:ia'), $this->tzOffset, $r['date']);
                echo '
                    <tr>
                        <td>
                            <a href="?download='.cleanOutput($r['name']).'">'.cleanOutput($r['name']).'</a>';

                if (checkAccess($this->currentUserId) < 3 || $this->currentUserId == $r['user']) {
                    echo '&nbsp;
                            <form method="post" action="documents.php">
                                <div>
                                    <input type="hidden" name="id" value="'.(int)$r['id'].'"/>
                                    <input type="hidden" name="name" value="'.cleanOutput($r['name']).'"/>
                                    <input type="submit" name="deldoc" value="'.T_('Delete').'" class="delbtn" title="'.T_('Delete this Document').'"/>
                                </div>
                            </form>';
                }

                echo '
                        </td>
                        <td>'.cleanOutput($r['description']).'</td>
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

        // No docs to show
        } else {
            echo '
            <div class="info-alert">
                <h2>'.T_('Welcome to the Documents Section.').'</h2>
                <p><i>'.T_('Currently no one is sharing any documents.').'</i></p>
                <p><a href="?adddoc=yes">'.T_('Upload a document').'</a></p>
            </div>';
        }
    }

    /**
     * displayForm 
     *
     * Display the form for uploading a document
     * 
     * @return void
     */
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

    /**
     * uploadDocument 
     * 
     * @param  file   $file 
     * @param  string $filename 
     * @return void
     */
    function uploadDocument ($file, $filename)
    {
        $valid_docs = array(
            'application/msword'                                                        => 'doc',
            'application/msword'                                                        => 'dot',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.template'   => 'dotx',

            'application/excel'                                                         => 'xls', 
            'application/x-excel'                                                       => 'xls', 
            'application/x-msexcel'                                                     => 'xls', 
            'application/vnd.ms-excel'                                                  => 'xls',
            'application/vnd.ms-excel'                                                  => 'xlt',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.template'      => 'xltx',

            'application/mspowerpoint'                                                  => 'ppt', 
            'application/powerpoint'                                                    => 'ppt', 
            'application/x-mspowerpoint'                                                => 'ppt', 
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.ms-powerpoint'                                             => 'pot',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/vnd.openxmlformats-officedocument.presentationml.template'     => 'potx',

            'application/msaccess'                                                      => 'accdb',

            'application/vnd.oasis.opendocument.presentation'                           => 'odp',
            'application/vnd.oasis.opendocument.spreadsheet'                            => 'ods',
            'application/vnd.oasis.opendocument.text'                                   => 'odt',

            'text/plain'                                                                => 'txt', 
            'text/css'                                                                  => 'css', 

            'application/rtf'                                                           => 'rtf', 
            'application/x-rtf'                                                         => 'rtf', 
            'text/richtext'                                                             => 'rtf', 

            'application/pdf'                                                           => 'pdf',

            'application/x-compressed'                                                  => 'zip', 
            'application/x-zip-compressed'                                              => 'zip', 
            'application/x-zip'                                                         => 'zip', 
            'application/zip'                                                           => 'zip', 
            'multipart/x-zip'                                                           => 'zip'
        );
        $filetmpname = $file['tmp_name'];
        $filetype = $file['type'];
        $error = $file['error'];
        $ext = explode(".", strtolower($file['name']));
        $ext = end($ext);

        // Check max file size
        if ($error == 1) {
            echo '
            <p class="error-alert">
                '.sprintf(T_('Document %s exceeds the maximum file size allowed by your PHP settings.'), $filename).'
            </p>';
            return false;
        }

        // Check allowable file type
        if (
            !array_key_exists($filetype, $valid_docs) ||
            !in_array($ext, $valid_docs)
        ) {
            echo '
            <div class="error-alert">
                <h2>'.T_('Invalid Document').'</h2>
                '.$filename.' &nbsp;<small><i>('.$filetype.')</i></small><br/><br/>
                '.T_('Documents must be of type (.doc, .txt, .xsl, .zip, .rtf, .ppt, .pdf).').'
            </div>';
            return false;
        }

        $filename = basename($filename); // just the filename, no paths

        // Check if a file with that name exists already
        if (file_exists("uploads/documents/$filename")) {
            echo '
            <p class="error-alert">
                '.sprintf(T_('Document %s already exists!  Please change the filename and try again.'), $filename).'
            </p>';
            return false;
        }

        // Upload the file
        copy($filetmpname, "uploads/documents/$filename");
        return true;
    }

}
