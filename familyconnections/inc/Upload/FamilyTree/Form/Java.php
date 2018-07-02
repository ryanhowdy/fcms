<?php
/**
 * Java Family Tree Form.
 *
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class JavaUploadFamilyTreeForm extends UploadFamilyTreeForm
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
     * displayUploadArea.
     *
     * @return void
     */
    protected function displayUploadArea()
    {
        $id = (int) $_GET['avatar'];

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
                            <div class="field-label">&nbsp;</div>
                            <div class="field-widget">
                                <applet id="jumpLoaderApplet" name="jumpLoaderApplet"
                                    code="jmaster.jumploader.app.JumpLoaderApplet.class"
                                    archive="inc/thirdparty/jumploader_z.jar"
                                    width="300"
                                    height="300"
                                    mayscript>
                                    <param name="uc_sendImageMetadata" value="true"/>
                                    <param name="uc_uploadUrl" value="familytree.php?advanced_avatar='.$id.'"/>
                                    <param name="vc_useThumbs" value="true"/>
                                    <param name="uc_uploadScaledImagesNoZip" value="true"/>
                                    <param name="uc_uploadScaledImages" value="true"/>
                                    <param name="uc_scaledInstanceNames" value="avatar"/>
                                    <param name="uc_scaledInstanceDimensions" value="80x80xcrop"/>
                                    <param name="uc_scaledInstanceQualityFactors" value="900"/>
                                    <param name="uc_uploadFormName" value="uploadForm"/>
                                    <param name="uc_maxFiles" value="1"/>
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
                                <input type="hidden" id="avatar_orig" name="avatar_orig" value="'.cleanOutput($this->data['avatar']).'"/><br/>
                                <script type="text/javascript">
                                $(document).ready(function() {
                                    $("#frm").submit(function(event) {
                                        event.preventDefault();

                                        var uploader = document.jumpLoaderApplet.getUploader();
                                        var attrSet  = uploader.getAttributeSet();

                                        var origAttr = attrSet.createStringAttribute("avatar_orig", $("#avatar_orig").val());
                                        origAttr.setSendToServer(true);

                                        uploader.startUpload();
                                    });
                                });
                                function uploaderStatusChanged(uploader) {
                                    if (uploader.getStatus() == 0) {
                                        window.location.href = "familytree.php";
                                    }
                                }
                                </script>
                            </div>';
    }
}
