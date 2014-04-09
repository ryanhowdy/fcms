<?php
/**
 * Java Form
 * 
 * @package Upload
 * @subpackage UploadPhotoGallery
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class JavaUploadPhotoGalleryForm extends UploadPhotoGalleryForm
{
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
     * @return boolean
     */
    public function display ()
    {
        $_SESSION['fcms_uploader_type'] = 'java';

        // Setup some applet params
        $scaledInstanceNames      = '<param name="uc_scaledInstanceNames" value="thumb,main"/>';
        $scaledInstanceDimensions = '<param name="uc_scaledInstanceDimensions" value="150x150xcrop,600x600xfit"/>';
        $fullSizedPhotos          = '';

        if (usingFullSizePhotos())
        {
            $scaledInstanceNames      = '<param name="uc_scaledInstanceNames" value="thumb,main,full"/>';
            $scaledInstanceDimensions = '<param name="uc_scaledInstanceDimensions" value="150x150xcrop,600x600xfit,1400x1400xfit"/>';
            $fullSizedPhotos          = '
                function sendFullSizedPhotos() {
                    var uploader = document.jumpLoaderApplet.getUploader();
                    var attrSet = uploader.getAttributeSet();
                    var attr = attrSet.createStringAttribute("full-sized-photos", "1");
                    attr.setSendToServer(true);
                }
                sendFullSizedPhotos();';
        }

        echo '
            <noscript>
                <style type="text/css">
                applet, .photo-uploader {display: none;}
                #noscript {padding:1em;}
                #noscript p {background-color:#ff9; padding:3em; font-size:130%; line-height:200%;}
                #noscript p span {font-size:60%;}
                </style>
                <div id="noscript">
                <p>
                    '.T_('JavaScript must be enabled in order for you to use the Advanced Uploader. However, it seems JavaScript is either disabled or not supported by your browser.').'<br/>
                    <span>
                        '.T_('Either enable JavaScript by changing your browser options.').'<br/>
                        '.T_('or').'<br/>
                        '.T_('Enable the Basic Upload option by changing Your Settings.').'
                    </span>
                </p>
                </div>
            </noscript>

            <div id="loading">'.T_('Loading Advanced Uploader...').'</div>
            <form method="post" id="uploadForm" name="uploadForm" class="photo-uploader" style="visibility:hidden">
                <div class="header">
                    <label>'.T_('Category').'</label>
                    '.$this->getCategoryInputs().'
                </div>
                <ul class="upload-types">
                    '.$this->getUploadTypesNavigation('upload').'
                </ul>
                <div class="upload-area">
                    <applet id="jumpLoaderApplet" name="jumpLoaderApplet"
                        code="jmaster.jumploader.app.JumpLoaderApplet.class"
                        archive="../inc/thirdparty/jumploader_z.jar"
                        width="758"
                        height="300"
                        mayscript>
                        <param name="uc_sendImageMetadata" value="true"/>
                        <param name="uc_uploadUrl" value="index.php"/>
                        <param name="vc_useThumbs" value="true"/>
                        <param name="uc_uploadScaledImagesNoZip" value="true"/>
                        <param name="uc_uploadScaledImages" value="true"/>
                        '.$scaledInstanceNames.'
                        '.$scaledInstanceDimensions.'
                        <param name="uc_scaledInstanceQualityFactors" value="900"/>
                        <param name="uc_uploadFormName" value="uploadForm"/>
                        <param name="vc_lookAndFeel" value="system"/>
                        <param name="vc_uploadViewStartActionVisible" value="false"/>
                        <param name="vc_uploadViewStopActionVisible" value="false"/>
                        <param name="vc_uploadViewPasteActionVisible" value="false"/>
                        <param name="vc_uploadViewRetryActionVisible" value="false"/>
                        <param name="vc_uploadViewFilesSummaryBarVisible" value="false"/>
                        <param name="vc_uiDefaults" value="Panel.background=#eff0f4; List.background=#eff0f4;"/> 
                        <param name="ac_fireAppletInitialized" value="true"/>
                        <param name="ac_fireUploaderStatusChanged" value="true"/> 
                        <param name="ac_fireUploaderFileStatusChanged" value="true"/>
                    </applet>
                </div>
                <div class="footer">
                    <input class="sub1" type="button" value="'.T_('Upload').'" id="start-upload" name="start-upload"/>
                </div>
            </form>
            <script type="text/javascript">
            document.onkeydown = keyHandler;
            function keyHandler(e)
            {
                if (!e) { e = window.event; }
                if (e.keyCode == 27) {
                    $("uploadForm").setStyle({visibility:"visible"});
                    $("loading").hide();
                }
            }
            Event.observe("start-upload","click",function(e){

                '.$this->getJsUploadValidation().'

                var uploader = document.jumpLoaderApplet.getUploader();
                var attrSet  = uploader.getAttributeSet();

                var newValue = $F("new-category");
                var newAttr  = attrSet.createStringAttribute("new-category", newValue);
                newAttr.setSendToServer(true);

                var attribute = attrSet.createStringAttribute("javaUpload", 1);
                attribute.setSendToServer(true);

                if ($("existing-categories")) {
                    var value = $F("existing-categories");
                    var attr  = attrSet.createStringAttribute("category", value);
                    attr.setSendToServer(true);
                }

                uploader.startUpload();
            });'.$fullSizedPhotos.'
            function uploaderStatusChanged(uploader) {
                if (uploader.getStatus() == 0) {
                    window.location.href = "index.php?action=advanced";
                }
            }
            function appletInitialized(applet) {
                $("uploadForm").setStyle({visibility:"visible"});
                $("loading").hide();
            }
            </script>';
    }
}
