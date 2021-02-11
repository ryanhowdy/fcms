<?php
/**
 * Documents.
 *
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Documents
{
    public $fcmsError;
    public $fcmsDatabase;
    public $fcmsUser;

    /**
     * __construct.
     *
     * @param FCMS_Error $fcmsError
     * @param Database   $fcmsDatabase
     * @param User       $fcmsUser
     *
     * @return void
     */
    public function __construct(FCMS_Error $fcmsError, Database $fcmsDatabase, User $fcmsUser)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser = $fcmsUser;
    }

    /**
     * showDocuments.
     *
     * @param int $page
     *
     * @return void
     */
    public function showDocuments($page = 1)
    {
        $templateParams = [
            'documentText'         => T_('Document'),
            'descriptionText'      => T_('Description'),
            'uploadedByText'       => T_('Uploaded By'),
            'dateAddedText'        => T_('Date Added'),
            'blankStateHeaderText' => T_('Nothing to see here'),
            'blankStateText'       => T_('Currently no one has shared any documents.'),
            'blankStateLinkText'   => T_('Why don\'t you share a document now?'),
        ];

        if ($this->fcmsUser->access <= 5)
        {
            $templateParams['pageNavigation'] = [
                'action' => [
                    [
                        'url'  => '?adddoc=yes',
                        'text' => T_('Add Document'),
                    ],
                ],
            ];
        }

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

        foreach ($rows as $r)
        {
            $date = fixDate(T_('m/d/Y h:ia'), $this->fcmsUser->tzOffset, $r['date']);

            $documentRow = [
                'name'        => cleanOutput($r['name']),
                'description' => cleanOutput($r['description']),
                'user'        => getUserDisplayName($r['user']),
                'date'        => $date,
            ];

            if ($this->fcmsUser->access < 3 || $this->fcmsUser->id == $r['user'])
            {
                $documentRow['delete'] = '&nbsp;
                            <form method="post" action="documents.php">
                                <div>
                                    <input type="hidden" name="id" value="'.(int) $r['id'].'"/>
                                    <input type="hidden" name="name" value="'.cleanOutput($r['name']).'"/>
                                    <input type="submit" name="deldoc" value="'.T_('Delete').'" class="delbtn" title="'.T_('Delete this Document').'"/>
                                </div>
                            </form>';
            }

            $templateParams['documents'][] = $documentRow;
        }

        // Pages
        $sql = 'SELECT count(`id`) AS c 
                FROM `fcms_documents`';

        $row = $this->fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        $docscount = isset($row['c']) ? $row['c'] : 0;
        $total_pages = ceil($docscount / 25);

        loadTemplate('document', 'main', $templateParams);

        displayPages('documents.php', $page, $total_pages);
    }

    /**
     * displayForm.
     *
     * Display the form for uploading a document
     *
     * @return void
     */
    public function displayForm()
    {
        $templateParams = [
            'uploadDocumentText'     => T_('Upload Document'),
            'documentText'           => T_('Document'),
            'descriptionText'        => T_('Description'),
            'descriptionFailureText' => T_('Required'),
            'addText'                => T_('Add'),
            'cancelText'             => T_('Cancel'),
        ];

        loadTemplate('document', 'add', $templateParams);
    }

    /**
     * uploadDocument.
     *
     * @param file   $file
     * @param string $filename
     *
     * @return void
     */
    public function uploadDocument($file, $filename)
    {
        $valid_docs = [
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
            'multipart/x-zip'                                                           => 'zip',
        ];
        $filetmpname = $file['tmp_name'];
        $filetype = $file['type'];
        $error = $file['error'];

        $ext = explode('.', strtolower($file['name']));
        $ext = end($ext);

        // Check max file size
        if ($error == 1)
        {
            $this->fcmsError->add([
                'message' => T_('Document too large.'),
                'details' => '<p>'.sprintf(T_('Document %s exceeds the maximum file size allowed by your PHP settings.'), $filename).'</p>',
            ]);

            return false;
        }

        // Check allowable file type
        if (!array_key_exists($filetype, $valid_docs) || !in_array($ext, $valid_docs))
        {
            $this->fcmsError->add([
                'message' => T_('Invalid Document'),
                'details' => '<p>'.$filename.' &nbsp;<small><i>('.$filetype.')</i></small></p><p>'.T_('Documents must be of type (.doc, .txt, .xsl, .zip, .rtf, .ppt, .pdf).').'</p>',
            ]);

            return false;
        }

        $filename = basename($filename); // just the filename, no paths

        $uploadsPath = getUploadsAbsolutePath();

        // Check if a file with that name exists already
        if (file_exists($uploadsPath.'documents/'.$filename))
        {
            $this->fcmsError->add([
                'message' => sprintf(T_('Document %s already exists!  Please change the filename and try again.'), $filename),
                'file'    => __FILE__,
                'line'    => __LINE__,
            ]);

            return false;
        }

        // Upload the file
        copy($filetmpname, $uploadsPath.'documents/'.$filename);

        return true;
    }
}
