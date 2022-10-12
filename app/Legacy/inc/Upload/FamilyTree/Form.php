<?php
/**
 * Basic Form
 * 
 * @package Upload
 * @subpackage UploadFamilyTree
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class UploadFamilyTreeForm
{
    protected $avatarTypes;
    protected $data;

    /**
     * __construct 
     * 
     * @param FCMS_Error $fcmsError 
     * @param Database   $fcmsDatabase 
     * @param User       $fcmsUser 
     * 
     * @return void
     */
    public function __construct (FCMS_Error $fcmsError, Database $fcmsDatabase, User $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
   }

    /**
     * display 
     * 
     * @return void
     */
    public function display ()
    {
        $id = (int)$_GET['avatar'];

        // Get user info
        $sql = "SELECT `id`, `fname`, `lname`, `maiden`, `avatar`, `gravatar`
                FROM `fcms_users`
                WHERE `id` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $id);
        if ($row === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $name = cleanOutput($row['fname']).' '.cleanOutput($row['lname']);

        echo '
                <form id="frm" name="frm" enctype="multipart/form-data" action="?avatar='.$id.'" method="post">
                    <fieldset>
                        <legend><span>'.sprintf(T_pgettext('%s is a persons full name', 'Picture for %s'), $name).'</span></legend>
                        <div class="field-row">
                            <div class="field-label"><b>'.T_('Current Picture').'</b></div>
                            <div class="field-widget">
                                <img src="'.getCurrentAvatar($id).'"/>
                            </div>
                        </div>';

        $this->displayUploadArea();

        echo '
                        <p>
                            <input type="hidden" name="avatar_orig" value="'.cleanOutput($row['avatar']).'"/>
                            <input class="sub1" type="submit" name="submitUpload" id="submitUpload" value="'.T_('Submit').'"/>
                            &nbsp; <a href="familytree.php">'.T_('Cancel').'</a>
                        </p>
                    </fieldset>
                </form>';
    }

    /**
     * displayUploadArea 
     * 
     * @return void
     */
    protected function displayUploadArea ()
    {
        echo '
                        <div class="field-row">
                            <div class="field-label"><b>'.T_('Choose new Picture').'</b></div>
                            <div class="field-widget">
                                <input type="file" name="avatar" id="avatar" size="30" title="'.T_('Upload your personal image (Avatar)').'"/>
                            </div>
                        </div>';
    }
}
