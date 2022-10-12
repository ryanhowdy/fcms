<?php
/**
 * UploadFamilyTree
 * 
 * Handles uploads from the basic family tree profile uploader.
 * 
 * @package Upload
 * @subpackage FamilyTree
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class UploadFamilyTree
{
    protected $fcmsError;
    protected $fcmsDatabase;
    protected $fcmsUser;

    /**
     * __construct 
     * 
     * @param FCMS_Error  $fcmsError 
     * @param Database    $fcmsDatabase 
     * @param User        $fcmsUser 
     * @param Destination $destination 
     * @param UploadPhoto $uploadPhoto 
     * 
     * @return void
     */
    public function __construct (FCMS_Error $fcmsError, Database $fcmsDatabase, User $fcmsUser, Destination $destination, UploadPhoto $uploadPhoto = null)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
        $this->destination  = $destination;
        $this->uploadPhoto  = $uploadPhoto;
    }

    /**
     * upload 
     * 
     * @return boolean
     */
    public function upload ($formData)
    {
        $this->setFormData($formData);

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
        if (!$this->destination->createDirectory())
        {
            return false;
        }

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
            if (file_exists($this->destination->destinationPath.basename($this->formData['avatar_orig'])))
            {
                unlink($this->destination->destinationPath.basename($this->formData['avatar_orig']));
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
        $sql = "UPDATE `fcms_users`
                SET `avatar` = ?
                WHERE `id` = ?";

        $params = array(
            $this->uploadPhoto->fileName,
            $this->formData['userid'],
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            return false;
        }

        return true;
    }
}
