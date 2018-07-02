<?php
/**
 * Instagram Form.
 *
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class InstagramUploadPhotoGalleryForm extends UploadPhotoGalleryForm
{
    private $accessToken;
    private $autoUpload;

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
     * display.
     *
     * @return void
     */
    public function display()
    {
        $_SESSION['fcms_uploader_type'] = 'instagram';

        // Get auto upload setting and access token
        $sql = 'SELECT `instagram_access_token`, `instagram_auto_upload`
                FROM `fcms_user_settings`
                WHERE `user` = ?
                LIMIT 1';

        $r = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($r === false)
        {
            return false;
        }

        if (empty($r))
        {
            echo '
            <p class="error-alert">
                '.T_('Could not get Instagram user data.').'
            </p>';

            return;
        }

        $this->accessToken = $r['instagram_access_token'];
        $this->autoUpload = $r['instagram_auto_upload'] == 1 ? true : false;

        if (empty($this->accessToken))
        {
            $instagramInfo = $this->getNotConnectedInfo();
        }
        else
        {
            $instagramInfo = $this->getPhotoInfo();
        }

        // Display the form
        echo '
            <form method="post" class="photo-uploader" action="index.php?action=upload&amp;type=instagram">
                <div class="header"></div>
                <ul class="upload-types">
                    '.$this->getUploadTypesNavigation('instagram').'
                </ul>
                <div class="upload-area">
                    <div class="instagram">
                        '.$instagramInfo.'
                    </div>
                </div>
                <div class="footer">
                    <input class="sub1" type="submit" value="'.T_('Upload').'" id="instagram" name="instagram"/>
                </div>
            </form>';
    }

    /**
     * getNotConnectedInfo.
     *
     * @return string
     */
    private function getNotConnectedInfo()
    {
        return '
            <div class="info-alert">
                <h2>'.T_('Not connected to Instagram.').'</h2>
                <p>'.T_('You must connect your Family Connections account to Instagram before you can begin importing photos from Instagram.').'</p>
                <p><a href="../settings.php?view=instagram">'.T_('Connect to Instagram').'</a></p>
            </div>';
    }

    /**
     * getPhotoInfo.
     *
     * @return string
     */
    private function getPhotoInfo()
    {
        $config = getInstagramConfigData();
        $instagram = new Instagram($config['instagram_client_id'], $config['instagram_client_secret'], $this->accessToken);

        try
        {
            if (isset($_GET['show']) && $_GET['show'] == 'more')
            {
                $feed = $instagram->get('users/self/media/recent/');
            }
            else
            {
                $feed = $instagram->get('users/self/media/recent/', ['count' => 8]);
            }
        }
        catch (InstagramApiError $e)
        {
            $this->fcmsError->add([
                'type'    => 'operation',
                'message' => T_('Could not get Instagram User data.'),
                'error'   => $e->getMessage(),
                'file'    => __FILE__,
                'line'    => __LINE__,
            ]);

            return false;
        }

        $photos = '';
        $automaticSelect = '';

        if (!$this->autoUpload)
        {
            $photos .= '<h2>'.T_('Manual').'</h2>';
            $photos .= '<p>'.T_('Choose photo to add.').'</p>';
            $photos .= '<ul>';

            $i = 1;
            foreach ($feed->data as $photo)
            {
                $sourceId = $photo->id;
                $thumbnail = $photo->images->thumbnail->url;
                $medium = $photo->images->low_resolution->url;
                $full = $photo->images->standard_resolution->url;
                $caption = isset($photo->caption) ? $photo->caption->text : '';
                $caption .= ' ['.sprintf(T_('Instagram filter: %s.'), $photo->filter).']';
                $value = "$sourceId|$thumbnail|$medium|$full|$caption";

                $photos .= '<li>';
                $photos .= '<label for="instagram'.$i.'">';
                $photos .= '<img src="'.$thumbnail.'" alt="'.$caption.'"/><br/>';
                $photos .= '<input type="checkbox" id="instagram'.$i.'" name="photos[]" value="'.$value.'"/>';
                $photos .= '</label>';
                $photos .= '</li>';

                $i++;
            }

            // They probably have more
            if ($i == 8)
            {
                $photos .= '<li><a href="index.php?action=upload&amp;type=instagram&amp;show=more">'.T_('See more').'</a></li>';
            }

            $photos .= '</ul>';
        }

        return $photos.'
                        <h2>'.T_('Automatic').'</h2>
                        <label>
                            <input type="checkbox" id="automatic" name="automatic" value="1" '.($this->autoUpload ? 'checked="checked"' : '').'/>
                            '.T_('Have all photos automatically imported.').'
                        </label>';
    }
}
