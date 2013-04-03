<?php

require_once INC.'Upload/PhotoGallery/Form.php';

/**
 * PicasaFormUpload 
 * 
 * @package Upload_PhotoGallery
 * @subpackage Form
 * @copyright 2013 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class PicasaFormUpload extends FormUpload
{
    /**
     * __construct 
     * 
     * @param string  $fcmsError 
     * @param string  $fcmsDatabase 
     * @param string  $fcmsUser 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser)
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
        $_SESSION['fcms_uploader_type'] = 'picasa';

        // Get session token
        $sql = "SELECT `picasa_session_token`
                FROM `fcms_user_settings`
                WHERE `user` = ?
                LIMIT 1";

        $r = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($r === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        if (empty($r))
        {
            echo '
            <p class="error-alert">
                '.T_('Could not get user data.').'
            </p>';
            return;
        }

        $picasaInfo = '';
        $token      = '';
        $js         = '';

        if (empty($r['picasa_session_token']))
        {
            $picasaInfo = '
            <div class="info-alert">
                <h2>'.T_('Not connected to Picasa.').'</h2>
                <p>'.T_('You must connect your Family Connections account to Picasa before you can begin importing photos from Picasa.').'</p>
                <p><a href="../settings.php?view=picasa">'.T_('Connect to Picasa').'</a></p>
            </div>';
        }
        else
        {
            $token      = $r['picasa_session_token'];
            $picasaInfo = '<p></p>';
            $js         = 'loadPicasaAlbums("'.$token.'", "'.T_('Could not get albums.').'");';
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
            Event.observe("submit-photos","click",function(e){
            '.$this->getJsUploadValidation().'
            });
            </script>';
    }
}
