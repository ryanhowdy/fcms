<?php
/**
 * UploadProfile
 * 
 * Handles uploads from the basic profile uploader.
 * 
 * @package Upload
 * @subpackage Profile
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class UploadProfile
{
    protected $fcmsError;
    protected $fcmsDatabase;
    protected $fcmsUser;

    /**
     * __construct 
     * 
     * @param FCMS_Error        $fcmsError 
     * @param Database          $fcmsDatabase 
     * @param User              $fcmsUser 
     * @param PhotoDestination  $photoDestination 
     * @param UploadPhoto       $uploadPhoto 
     * 
     * @return void
     */
    public function __construct (FCMS_Error $fcmsError, Database $fcmsDatabase, User $fcmsUser, PhotoDestination $photoDestination, UploadPhoto $uploadPhoto = null)
    {
        $this->fcmsError           = $fcmsError;
        $this->fcmsDatabase        = $fcmsDatabase;
        $this->fcmsUser            = $fcmsUser;
        $this->photoDestination    = $photoDestination;
        $this->uploadPhoto         = $uploadPhoto;
    }

    /**
     * upload 
     * 
     * @return boolean
     */
    public function upload ($formData)
    {
        $this->setFormData($formData);

        if ($this->formData['avatar_type'] == 'fcms')
        {
            // Load the photo, and do some validation
            $this->uploadPhoto->load($this->formData['avatar']);

            if ($this->fcmsError->hasUserError())
            {
                return false;
            }

            // Save file and resize
            if (!$this->saveAvatar())
            {
                return false;
            }
        }

        $this->removeOldAvatar();

        // Update db
        if (!$this->updateAvatar())
        {
            return false;
        }


        return true;
    }

    /**
     * setFormData 
     * 
     * Saves all the data passed in from the form upload.
     * 
     * @param array $formData
     * 
     * @return void
     */
    protected function setFormData ($formData)
    {
        $this->formData = $formData;
    }

    /**
     * saveAvatar 
     * 
     * Resizes the photo and saves it to the right destination.
     * 
     * @return boolean
     */
    protected function saveAvatar ()
    {
        $this->uploadPhoto->save()
                          ->resize(80, 80, 'square');

        // See if uploadPhoto had any errors
        if ($this->fcmsError->hasUserError())
        {
            return false;
        }

        return true;
    }

    /**
     * removeOldAvatar 
     * 
     * Will delete the previous avatar from the destination path.
     * 
     * @return void
     */
    protected function removeOldAvatar ()
    {
        if ($this->formData['avatar_orig'] != 'no_avatar.jpg' && $this->formData['avatar_orig'] != 'gravatar')
        {
            if (file_exists($this->photoDestination->destinationPath.basename($this->formData['avatar_orig'])))
            {
                unlink($this->photoDestination->destinationPath.basename($this->formData['avatar_orig']));
            }
        }
    }

    /**
     * updateAvatar
     * 
     * Updates the avatar in the db.
     * 
     * @return boolean
     */
    protected function updateAvatar ()
    {
        // update changelog
        $sql = "INSERT INTO `fcms_changelog`
                    (`user`, `table`, `column`, `created`)
                VALUES
                    (?, 'users', 'avatar', NOW())";
        if (!$this->fcmsDatabase->insert($sql, $this->fcmsUser->id))
        {
            return false;
        }

        // insert profile
        if ($this->formData['avatar_type'] == 'fcms')
        {
            return $this->updateUploadedAvatar();
        }
        else if ($this->formData['avatar_type'] == 'gravatar')
        {
            return $this->updateGravatar();
        }

        return $this->updateDefaultAvatar();
    }

    /**
     * updateUploadedAvatar 
     * 
     * Sets the avatar to the uploaded avatar filename.
     * 
     * @return boolean
     */
    private function updateUploadedAvatar ()
    {
        $sql = "UPDATE `fcms_users`
                SET `avatar` = ?
                WHERE `id` = ?";

        $params = array(
            $this->uploadPhoto->fileName,
            $this->fcmsUser->id,
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            return false;
        }

        return true;
    }

    /**
     * updateGravatar 
     * 
     * Sets the avatar for the current user to gravatar.
     * 
     * @return boolean
     */
    private function updateGravatar ()
    {
        $sql = "UPDATE `fcms_users`
                SET `avatar` = 'gravatar', 
                    `gravatar` = ?
                WHERE `id` = ?";

        $params = array(
            $this->formData['gravatar_email'],
            $this->fcmsUser->id,
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            return false;
        }

        return true;
    }

    /**
     * updateDefaultAvatar
     * 
     * Sets the avatar for the current user to the default.
     * 
     * @return boolean
     */
    private function updateDefaultAvatar ()
    {
        $sql = "UPDATE `fcms_users`
                SET `avatar` = 'no_avatar.jpg'
                WHERE `id` = ?";
        if (!$this->fcmsDatabase->update($sql, $this->fcmsUser->id))
        {
            return false;
        }

        return true;
    }
}
