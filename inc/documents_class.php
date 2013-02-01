<?php
/**
 * Documents 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Documents
{
    var $fcmsError;
    var $fcmsDatabase;
    var $fcmsUser;

    /**
     * Documents 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase
     * @param object $fcmsUser 
     *
     * @return void
     */
    function Documents ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError       = $fcmsError;
        $this->fcmsDatabase    = $fcmsDatabase;
        $this->fcmsUser        = $fcmsUser;
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
                LIMIT $from, 25";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (count($rows) > 0)
        {
            echo '
            <script type="text/javascript" src="ui/js/tablesort.js"></script>
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

            foreach ($rows as $r)
            {
                $date = fixDate(T_('m/d/Y h:ia'), $this->fcmsUser->tzOffset, $r['date']);

                echo '
                    <tr>
                        <td>
                            <a href="?download='.cleanOutput($r['name']).'">'.cleanOutput($r['name']).'</a>';

                if ($this->fcmsUser->access < 3 || $this->fcmsUser->id == $r['user'])
                {
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
            $sql = "SELECT count(`id`) AS c 
                    FROM `fcms_documents`";

            $row = $this->fcmsDatabase->getRow($sql);
            if ($row === false)
            {
                $this->fcmsError->displayError();
                return;
            }

            $docscount   = isset($row['c']) ? $row['c'] : 0;
            $total_pages = ceil($docscount / 25); 

            displayPages('documents.php', $page, $total_pages);
        }
        // No docs to show
        else
        {
            echo '
            <div class="blank-state">
                <h2>'.T_('Nothing to see here').'</h2>
                <h3>'.T_('Currently no one has shared any documents.').'</h3>
                <h3><a href="?adddoc=yes">'.T_('Why don\'t you share a document now?').'</a></h3>
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
            'application/octet-stream'                                                  => 'doc',
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
        $filetype    = $file['type'];
        $error       = $file['error'];

        $ext = explode(".", strtolower($file['name']));
        $ext = end($ext);

        // Check max file size
        if ($error == 1)
        {
            $this->fcmsError->add(array(
                'message' => T_('Document too large.'),
                'details' => '<p>'.sprintf(T_('Document %s exceeds the maximum file size allowed by your PHP settings.'), $filename).'</p>',
            ));

            return false;
        }

        // Check allowable file type
        if (!array_key_exists($filetype, $valid_docs) || !in_array($ext, $valid_docs))
        {
            $this->fcmsError->add(array(
                'message' => T_('Invalid Document'),
                'details' => '<p>'.$filename.' &nbsp;<small><i>('.$filetype.')</i></small></p><p>'.T_('Documents must be of type (.doc, .txt, .xsl, .zip, .rtf, .ppt, .pdf).').'</p>',
            ));

            return false;
        }

        $filename = basename($filename); // just the filename, no paths

        $uploadsPath = getUploadsAbsolutePath();

        // Check if a file with that name exists already
        if (file_exists($uploadsPath.'documents/'.$filename))
        {
            $this->fcmsError->add(array(
                'message' => sprintf(T_('Document %s already exists!  Please change the filename and try again.'), $filename),
                'file'    => __FILE__,
                'line'    => __LINE__,
            ));

            return false;
        }

        // Upload the file
        copy($filetmpname, $uploadsPath.'documents/'.$filename);
        return true;
    }
}
