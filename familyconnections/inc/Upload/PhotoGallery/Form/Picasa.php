<?php
/**
 * Picasa Form.
 *
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class PicasaUploadPhotoGalleryForm extends UploadPhotoGalleryForm
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
    }

    /**
     * display.
     *
     * @return void
     */
    public function display()
    {
        $_SESSION['fcms_uploader_type'] = 'picasa';

        $googleClient = getAuthedGoogleClient($this->fcmsUser->id);

        $picasaInfo = '';
        $token = '';
        $js = '';

        if ($this->fcmsError->hasError()) {
            $this->fcmsError->displayError();

            return;
        } elseif ($googleClient === false) {
            $picasaInfo = '
            <div class="info-alert">
                <h2>'.T_('Not connected to Google.').'</h2>
                <p>'.T_('You must connect your Family Connections account to Google before you can begin importing photos from Google.').'</p>
                <p><a href="../settings.php?view=google">'.T_('Connect to Google').'</a></p>
            </div>';
        } else {
            $json = json_decode($_SESSION['googleSessionToken']);

            $token = $json->access_token;
            $picasaInfo = '<p></p>';
            $js = 'loadPicasaAlbums("'.$token.'", "'.T_('Could not get albums.').'");';
        }

        // Display the form
        echo '
            <form method="post" class="photo-uploader" action="index.php?action=upload&amp;type=picasa">
                <div class="header">
                    <label>'.T_('Category').'</label>
                    '.$this->getCategoryInputs().'
                </div>
                <ul class="upload-types">
                    '.$this->getUploadTypesNavigation('picasa').'
                </ul>
                <div class="upload-area">
                    <div class="picasa">
                        '.$picasaInfo.'
                    </div>
                </div>
                <div class="footer">
                    <input class="sub1" type="submit" value="'.T_('Upload').'" id="submit-photos" name="picasa"/>
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
