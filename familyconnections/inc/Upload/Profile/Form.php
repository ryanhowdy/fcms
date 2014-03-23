<?php
/**
 * Basic Form
 * 
 * @package Upload
 * @subpackage UploadProfile
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class UploadProfileForm
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
        $this->setAvatarTypes();

        if (!$this->getAvatarData())
        {
            $this->fcmsError->displayError();
            return;
        }

        $avatarOptions = buildHtmlSelectOptions($this->avatarTypes, $this->data['currentAvatarType']);


        echo '
                <form id="frm" name="frm" enctype="multipart/form-data" action="profile.php?view=picture" method="post">
                    <fieldset>
                        <legend><span>'.T_('Profile Picture').'</span></legend>

                        <div class="field-row">
                            <div class="field-label">
                                <label for="avatar"><b>'.T_('Change Avatar').'</b></label>
                            </div>
                            <div class="field-widget">
                                <select name="avatar_type" id="avatar_type">
                                    '.$avatarOptions.'
                                </select><br/>
                            </div>
                        </div>

                        <div id="fcms" class="field-row">';

        $this->displayUploadArea();

        echo '
                        </div>

                        <div id="gravatar" class="field-row">
                            <div class="field-label">&nbsp;</div>
                            <div class="field-widget">
                                <b>'.T_('Gravatar Email').'</b><br/>
                                <input type="text" name="gravatar_email" size="30" value="'.cleanOutput($this->data['gravatar']).'"/><br/>
                            </div>
                        </div>

                        <div id="default" class="field-row">
                            <div class="field-label">&nbsp;</div>
                            <div class="field-widget">
                                <b>'.T_('Default').'</b><br/>
                                <img id="current-avatar" src="'.URL_PREFIX.'uploads/avatar/no_avatar.jpg" alt="'.T_('Default avatar.').'"/>
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-label">
                                <label for="avatar"><b>'.T_('Current Avatar').'</b></label>
                            </div>
                            <div class="field-widget">
                                <img id="current-avatar" src="'.getCurrentAvatar($this->fcmsUser->id).'" alt="'.T_('This is your current avatar.').'"/>
                            </div>
                        </div>

                        <p><input class="sub1" type="submit" name="submit" id="submit-avatar" value="'.T_('Submit').'"/></p>

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
                            <div class="field-label">&nbsp;</div>
                            <div class="field-widget">
                                <input type="file" name="avatar" id="avatar" size="30" title="'.T_('Upload your personal image (Avatar)').'"/>
                                <input type="hidden" name="avatar_orig" value="'.cleanOutput($this->data['avatar']).'"/><br/>
                            </div>';
    }

    /**
     * setAvatarTypes 
     * 
     * @return void
     */
    protected function setAvatarTypes ()
    {
        $this->avatarTypes  = array(
            'fcms'      => T_('Upload Avatar'),
            'gravatar'  => T_('Use Gravatar'),
            'default'   => T_('Use Default')
        );
    }

    /**
     * getAvatarData 
     * 
     * Sets the data property
     * 
     * @return boolean
     */
    protected function getAvatarData ()
    {
        $sql = "SELECT `avatar`, `gravatar`, `email`
                FROM `fcms_users`
                WHERE `id` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
            return false;;
        }

        // Default the Gravatar email to user's current email
        if (empty($row['gravatar']))
        {
            $row['gravatar'] = $row['email'];
        }

        $row['currentAvatarType'] = 'upload';

        if ($row['avatar'] == 'no_avatar.jpg')
        {
            $row['currentAvatarType'] = 'default';
        }
        else if ($row['avatar'] == 'gravatar')
        {
            $row['currentAvatarType'] = 'gravatar';
        }

        $this->data = $row;

        return true;
    }
}
