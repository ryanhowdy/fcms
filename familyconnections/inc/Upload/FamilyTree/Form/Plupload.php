<?php
/**
 * Plupload Family Tree profile Form
 * 
 * @package Upload
 * @subpackage UploadFamilyTree
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class PluploadUploadFamilyTreeForm extends UploadFamilyTreeForm
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
     * displayUploadArea 
     * 
     * @return void
     */
    protected function displayUploadArea ()
    {
        $id = (int)$_GET['avatar'];

        echo '
            <script type="text/javascript" src="ui/js/scriptaculous.js"></script>
            <script type="text/javascript" src="inc/thirdparty/plupload/js/plupload.full.min.js"></script>
<script>
Event.observe(window, "load", function() {
    var uploader = new plupload.Uploader({
        runtimes            : "gears,html5,flash,silverlight,browserplus",
        browse_button       : "choose_photos",
        container           : "plupload_container",
        url                 : "familytree.php?advanced_avatar='.$id.'",
        flash_swf_url       : "inc/thirdparty/plupload/js/plupload.flash.swf",
        silverlight_xap_url : "inc/thirdparty/plupload/js/plupload.silverlight.xap",
        multi_selection     : false,
        filters             : [
            {title : "Image files", extensions : "jpg,jpeg,gif,png"},
        ],
        resize              : { width: 80, height: 80, quality: 90, crop: true }
    });

    $("submitUpload").observe("click", function(e) {
        e.preventDefault();

        uploader.settings.multipart_params = {
            "avatar_orig" : $F("avatar_orig"),
        };

        uploader.start();
    });

    uploader.init();
 
    uploader.bind("FilesAdded", function(up, files) {
        if (uploader.files.length > 1) {
            uploader.files.each(function(file) {
                uploader.removeFile(file);
                $("file").firstDescendant().remove();
                throw $break;
            });
        }

        files.each(function(file) {
            var li = document.createElement("li");
            $("file").appendChild(li);

            $(li).insert({
                bottom:   "<div id=\"" + file.id + "\">" + file.name + " (" + plupload.formatSize(file.size) + ")<span></span></div>"
            });
        });
 
        up.refresh(); // Reposition Flash/Silverlight
    });

    uploader.bind("UploadProgress", function(up, file) {
        $(file.id).firstDescendant().insert({
            bottom : file.percent
        });
    });
 
    uploader.bind("Error", function(up, err) {
        $("file").insert({
            after : "<div style=\"color:red\">Error: " + err.code + ", Message: " + err.message + (err.file ? ", File: " + err.file.name : "") + "</div>"
        });
 
        up.refresh(); // Reposition Flash/Silverlight
    });
 
    uploader.bind("FileUploaded", function(up, file) {
        window.location.href = "familytree.php";
    });
});
</script>
                            <div class="field-label">&nbsp;</div>
                            <div id="plupload_container" class="field-widget">
                                <a id="choose_photos" class="sub1" href="#">'.T_('Choose Avatar').'</a>
                                <ul id="file"></ul>
                                <input type="hidden" id="avatar_orig" name="avatar_orig" value="'.cleanOutput($this->data['avatar']).'"/><br/>
                            </div>';
    }
}
