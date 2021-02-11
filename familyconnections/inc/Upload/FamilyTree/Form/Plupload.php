<?php
/**
 * Plupload Family Tree profile Form.
 *
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class PluploadUploadFamilyTreeForm extends UploadFamilyTreeForm
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
            <link rel="stylesheet" href="ui/js/jqueryui/jquery-ui.min.css">
            <link rel="stylesheet" href="ui/js/jqueryui/jquery-ui.theme.min.css">
            <link rel="stylesheet" href="inc/thirdparty/plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css">
            <script type="text/javascript" src="ui/js/jqueryui/jquery-ui.min.js"></script>
            <script type="text/javascript" src="inc/thirdparty/plupload/js/plupload.full.min.js"></script>
            <script type="text/javascript" src="inc/thirdparty/plupload/js/jquery.ui.plupload/jquery.ui.plupload.min.js"></script>
<script>
$(document).ready(function() {
    $("#uploader").plupload({

        runtimes        : "html5,flash,silverlight,html4",
        url             : "familytree.php?advanced_avatar='.$id.'",
        max_file_size   : "100mb",
        multi_selection : false,

        buttons: {
            "start" : false,
        },
 
        multipart_params: {
            "plupload" : "1",
        },
 
        filters : [
            {title : "Image files", extensions : "jpg,jpeg,gif,png"}
        ],

        resize : {
            width: 80,
            height: 80,
            quality: 90,
            crop: true
        },
 
        // Sort files
        sortable: true,
 
        // Views to activate
        views: {
            list   : false,
            thumbs : true,
            active : "thumbs"
        },
 
        // Flash settings
        flash_swf_url : "../inc/thirdparty/plupload/js/Moxie.swf",
     
        // Silverlight settings
        silverlight_xap_url : "../inc/thirdparty/plupload/js/Moxie.xap",

        init : {
            FilesAdded: function(up, files) {
                while (up.files.length > 1) {
                    up.removeFile(up.files[0]);
                }
            },

            FileUploaded: function(up, file, info) {
                window.location.href = "familytree.php";
            }
        }
    });

    $("#frm").submit(function(event) {
        event.preventDefault();

        var uploader = $("#uploader").plupload("getUploader");
        uploader.settings.multipart_params = {
            "avatar_orig" : $("#avatar_orig").val()
        };

        $("#uploader").plupload("start");
    });
});
</script>
                            <div class="field-label">&nbsp;</div>
                            <div id="plupload_container" class="field-widget">
                                <div id="uploader"></div>
                                <input type="hidden" id="avatar_orig" name="avatar_orig" value="'.cleanOutput($this->data['avatar']).'"/><br/>
                            </div>';
    }
}
