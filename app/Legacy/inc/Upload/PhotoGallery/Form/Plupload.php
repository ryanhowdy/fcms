<?php
/**
 * Plupload Form
 * 
 * @package Upload
 * @subpackage UploadPhotoGallery
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class PluploadUploadPhotoGalleryForm extends UploadPhotoGalleryForm
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
     * @return void
     */
    public function display ()
    {
        $_SESSION['fcms_uploader_type'] = 'plupload';

        if (isset($_SESSION['photos']))
        {
            unset($_SESSION['photos']);
        }

        $fullFileUploaded   = '';
        $filesPerPhotoCount = 2;

        if (usingFullSizePhotos())
        {
            $filesPerPhotoCount = 3;

            $fullFileUploaded = 'else if (!("full" in file)) {
            file.full    = true;
            file.thumb   = false;
            file.loaded  = 0;
            file.percent = 0;
            file.status  = plupload.QUEUED;

            up.trigger("QueuedChanged");
            up.refresh();
        }';

        }

        // Display the form
        echo '
            <link rel="stylesheet" href="../ui/js/jqueryui/jquery-ui.min.css">
            <link rel="stylesheet" href="../ui/js/jqueryui/jquery-ui.theme.min.css">
            <link rel="stylesheet" href="../inc/thirdparty/plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css">
            <script type="text/javascript" src="../ui/js/jqueryui/jquery-ui.min.js"></script>
            <script type="text/javascript" src="../inc/thirdparty/plupload/js/plupload.full.min.js"></script>
            <script type="text/javascript" src="../inc/thirdparty/plupload/js/jquery.ui.plupload/jquery.ui.plupload.min.js"></script>
            <script>
            var filesPerPhotoCount = '.$filesPerPhotoCount.';
            $(document).ready(function() {
                $("#uploader").plupload({

                    runtimes      : "html5,flash,silverlight,html4",
                    url           : "index.php",
                    max_file_size : "100mb",

                    buttons: {
                        "start" : false,
                    },
             
                    multipart_params: {
                        "plupload" : "1",
                    },
             
                    filters : [
                        {title : "Image files", extensions : "jpg,jpeg,gif,png"}
                    ],
             
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

                    preinit : {
                        Init: function(up, info) {
                            up.real_total_files    = 0;
                            up.real_files_uploaded = 0;
                        },
                    },

                    init : {
                        FilesAdded: function(up, files) {
                            var total = files.length;
                            var i     = 1;

                            plupload.each(files, function(file) {
                                i++;
                                up.real_total_files += filesPerPhotoCount;
                            });
                     
                            up.refresh(); // Reposition Flash/Silverlight
                        },

                        BeforeUpload: function(up, file) {
                            if ("thumb" in file) {
                                up.settings.resize         = { width: 150, height: 150, quality: 80, crop: true };
                                up.settings.resize.enabled = true;
                                up.settings.resize.width   = 150;
                                up.settings.resize.height  = 150;
                                up.settings.resize.quality = 90;
                                up.settings.resize.crop    = true;
                                up.settings.file_data_name = "thumb";

                                if ("full" in file) {
                                    up.settings.resize.enabled = false;
                                    up.settings.file_data_name = "full";
                                }
                            }
                            else {
                                up.settings.resize.enabled = true;
                                up.settings.resize.width   = 600;
                                up.settings.resize.height  = 600;
                                up.settings.resize.crop    = true;
                                up.settings.file_data_name = "main";
                            }
                        },

                        Error: function(up, err) {
                            $("#autocomplete_form").before(
                                "<div class=\"error-alert\">" + err.message + "</div>"
                            );
                     
                            up.refresh(); // Reposition Flash/Silverlight
                        },
             
                        FileUploaded: function(up, file, info) {
                            var response = JSON.parse(info.response);
                            if (response !== null && response.error) {
                                file.status = plupload.FAILED;
                                $("#uploader").plupload("notify", "error", response.error.message);
                            }
                            if (!("thumb" in file)) {
                                file.thumb   = true;
                                file.loaded  = 0;
                                file.percent = 0;
                                file.status  = plupload.QUEUED;

                                up.trigger("QueuedChanged");
                                up.refresh();
                            }
                            '.$fullFileUploaded.'

                            up.real_files_uploaded++;

                            if (up.real_total_files == up.real_files_uploaded) {
                                window.location.href = "index.php?action=advanced";
                            }
                        }
                    }
                });
            });
            </script>
            <form id="autocomplete_form" enctype="multipart/form-data" action="?action=upload" method="post" class="photo-uploader">
                <div class="header">
                    <label>'.T_('Category').'</label>
                    '.$this->getCategoryInputs().'
                </div>
                <ul class="upload-types">
                    '.$this->getUploadTypesNavigation('upload').'
                </ul>
                <div class="upload-area">
                    <div class="plupload">
                        <p style="float:right">
                            <a class="help" href="../help.php?topic=photo#gallery-howworks">'.T_('Help').'</a>
                        </p>
                        <div id="uploader">
                        </div>
                    </div><!--/plupload-->
                </div>
                <div class="footer">
                    <input class="sub1" type="submit" id="submit-photos" name="addphoto" value="'.T_('Submit').'"/>
                </div>
            </form>
            <script type="text/javascript">
            $("#submit-photos").click(function(e) {
            '.$this->getJsUploadValidation().'

                e.preventDefault();

                var newCategory = $("#new-category").val();
                var category    = "";
                if ($("#existing-categories")) {
                    category = $("#existing-categories").val();
                }

                var uploader = $("#uploader").plupload("getUploader");
                uploader.settings.multipart_params = {
                    "plupload"      : "1",
                    "new-category"  : newCategory,
                    "category"      : category,
                };

                $("#uploader").plupload("start");
            });
            </script>';
    }
}
