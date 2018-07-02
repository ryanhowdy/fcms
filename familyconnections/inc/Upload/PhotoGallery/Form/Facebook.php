<?php
/**
 * Facebook Form.
 *
 * Prints the form for uploading photos from facebook.
 *
 * @copyright 2015 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class FacebookUploadPhotoGalleryForm extends UploadPhotoGalleryForm
{
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

        load('facebook');
    }

    /**
     * display.
     *
     * @return void
     */
    public function display()
    {
        $_SESSION['fcms_uploader_type'] = 'facebook';

        $config = getFacebookConfigData();
        $accessToken = getUserFacebookAccessToken($this->fcmsUser->id);

        $facebook = new Facebook([
            'appId'  => $config['fb_app_id'],
            'secret' => $config['fb_secret'],
        ]);

        $facebook->setAccessToken($accessToken);

        $fbUser = $facebook->getUser();
        if ($fbUser) {
            try {
                $fbProfile = $facebook->api('/me');
            } catch (FacebookApiException $e) {
                $fbUser = null;
            }
        }

        $facebookInfo = '';
        $js = '';

        if ($this->fcmsError->hasError()) {
            $this->fcmsError->displayError();

            return;
        } elseif (!$fbUser) {
            $facebookInfo = '
            <div class="info-alert">
                <h2>'.T_('Not connected to Facebook.').'</h2>
                <p>'.T_('You must connect your Family Connections account to Facebook before you can begin importing photos from Facebook.').'</p>
                <p><a href="../settings.php?view=facebook">'.T_('Connect to Facebook').'</a></p>
            </div>';
        } else {
            try {
                $fbAlbums = $facebook->api('/me/albums');

                $albumOptions = '';
                foreach ($fbAlbums['data'] as $album) {
                    $albumOptions .= '<option value="'.$album['id'].'">'.$album['name'].'</option>';
                }
            } catch (FacebookApiException $e) {
                $this->fcmsError->add([
                    'type'    => 'operation',
                    'message' => T_('Could not get Facebook albums.'),
                    'error'   => $e,
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ]);
                $this->fcmsError->displayError();

                return;
            }

            $facebookInfo = '
            <p>
                <select id="albums" name="albums">
                    '.$albumOptions.'
                </select>
            </p>
            <div id="selector">
                <a href="#" onclick="photoGallerySelectAll(event, \'facebook\');" id="select-all">'.T_('Select All').'</a>
                <a href="#" onclick="photoGallerySelectNone(event, \'facebook\');" id="select-none">'.T_('Select None').'</a>
            </div>
            <ul id="photo_list"></ul>';

            $js = 'loadPhotoGalleryPhotos("facebook", "'.T_('Could not get Facebook photos.').'");';
            $js .= 'loadPhotoGalleryPhotoEvents("facebook", "'.T_('Could not get Facebook photos.').'");';
        }

        // Display the form
        echo '
            <form method="post" class="photo-uploader" action="index.php?action=upload&amp;type=facebook">
                <div class="header">
                    <label>'.T_('Category').'</label>
                    '.$this->getCategoryInputs().'
                </div>
                <ul class="upload-types">
                    '.$this->getUploadTypesNavigation('facebook').'
                </ul>
                <div class="upload-area">
                    <div class="facebook">
                        '.$facebookInfo.'
                    </div>
                </div>
                <div class="footer">
                    <input class="sub1" type="submit" value="'.T_('Upload').'" id="submit-photos" name="facebook"/>
                </div>
            </form>
            <script type="text/javascript">
            '.$js.'
            $("#submit-photos").click(function(e) {
            '.$this->getJsUploadValidation().'
            });
            </script>';
    }
}
