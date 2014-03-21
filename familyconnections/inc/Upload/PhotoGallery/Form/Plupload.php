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
            <script type="text/javascript" src="../ui/js/scriptaculous.js"></script>
            <script type="text/javascript" src="../inc/thirdparty/plupload/js/plupload.full.min.js"></script>
<script>
var filesPerPhotoCount = '.$filesPerPhotoCount.';
Event.observe(window, "load", function() {
    var uploader = new plupload.Uploader({
        runtimes            : "gears,html5,flash,silverlight,browserplus",
        browse_button       : "choose_photos",
        container           : "container",
        url                 : "index.php",
        flash_swf_url       : "../inc/thirdparty/plupload/js/plupload.flash.swf",
        silverlight_xap_url : "../inc/thirdparty/plupload/js/plupload.silverlight.xap",
        filters             : [
            {title : "Image files", extensions : "jpg,jpeg,gif,png"},
        ],
    });

    uploader.bind("Init", function(up, params) {
        $("filelist").update("Current runtime: " + params.runtime);

        uploader.real_total_files    = 0;
        uploader.real_files_uploaded = 0;
    });

    $("submit-photos").observe("click", function(e) {
        e.preventDefault();

        var newCategory = $F("new-category");
        var category    = "";
        if ($("existing-categories")) {
            category = $F("existing-categories");
        }

        uploader.settings.multipart_params = {
            "plupload"      : "1",
            "new-category"  : newCategory,
            "category"      : category,
        };

        uploader.start();
    });
 
    $("choose_photos").observe("click", function() {
        $("filelist").insert({ top: "<div id=\"dim\"><h1>Loading. Please wait.</h1></div>"});
    });

    uploader.init();

    uploader.bind("BeforeUpload", function(up, file) {
        if ("thumb" in file) {
            up.settings.resize         = { width: 150, height: 150, quality: 80, crop: true };
            up.settings.file_data_name = "thumb";

            if ("full" in file) {
                up.settings.resize         = { quality: 100 };
                up.settings.file_data_name = "full";
            }

        }
        else {
            up.settings.resize         = { width: 600, height: 600, quality: 90 };
            up.settings.file_data_name = "main";
        }
    });
 
    uploader.bind("FilesAdded", function(up, files) {
        var total = files.length;
        var i     = 1;

        files.each(function(file) {
            var img = new o.Image();

            var li = document.createElement("li");
            $("preview").appendChild(li);

            $(li).insert({
                bottom:   "<div id=\"progress_" + file.name + "\" class=\"progress\"><div class=\"bar\" style=\"width:0%\"></div></div>"
            });

            var el = new Element("div", {"class": "remove"});
            el.update("X");
            el.onclick = function() { removePhoto(el); };
            $(li).insert({ bottom: el});

            setTimeout(function() {
                console.log("-- onload, embed");
                img.onload = function() {
                    li.id = this.uid;

                    this.embed(li.id, {
                        width: 150,
                        height: 90,
                        crop: true
                    });
                    console.log("embed photo");
                    if (i >= total) {
                        console.log("i [" + i + "]");
                        $("dim").remove();
                        console.log("hiding overlay");
                    }
                };
            }, 4);

            setTimeout(function() {
                console.log("-- load");
                img.load(file.getSource());
                console.log("photo load");
            }, 4);

            i++;
            uploader.real_total_files += filesPerPhotoCount;
        });
 
        up.refresh(); // Reposition Flash/Silverlight
    });

    function insertPhotoPreview (file, li) {
        var img = new o.Image();

        img.onload = function() {
            li.id = this.uid;

            this.embed(li.id, {
                width: 150,
                height: 90,
                crop: true
            });
        };

        img.load(file.getSource());
    }

    uploader.bind("UploadProgress", function(up, file) {
        var totalPercent = file.percent;

        if ("thumb" in file) {
            if ("full" in file) {
                totalPercent = (file.percent + 200) / 300;
                totalPercent = totalPercent * 100;
            }
            else {
                totalPercent = (file.percent + 100) / (filesPerPhotoCount * 100);
                totalPercent = totalPercent * 100;
            }
        }
        else {
            totalPercent = file.percent / filesPerPhotoCount;
        }

        $("progress_" + file.name).down().writeAttribute("style", "width:" + totalPercent + "%");
    });
 
    uploader.bind("Error", function(up, err) {
        $("filelist").insert({
            after : "<div style=\"color:red\">Error: " + err.code + ", Message: " + err.message + (err.file ? ", File: " + err.file.name : "") + "</div>"
        });
 
        up.refresh(); // Reposition Flash/Silverlight
    });
 
    uploader.bind("FileUploaded", function(up, file) {
        if (!("thumb" in file)) {
            file.thumb   = true;
            file.loaded  = 0;
            file.percent = 0;
            file.status  = plupload.QUEUED;

            up.trigger("QueuedChanged");
            up.refresh();
        }
        '.$fullFileUploaded.'
        else {
            $("progress_" + file.name).down().writeAttribute("style", "width:100%");
        }

        uploader.real_files_uploaded++;

        if (uploader.real_total_files == uploader.real_files_uploaded) {
            window.location.href = "index.php?action=advanced";
        }
    });

    function removePhoto (el) {
        el.up().remove();
    }
});
</script>
<style>
#dim {
    height: 100%;
    width: 100%;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 9;
    background-color: black;
    opacity: 0.85;
}
#dim h1 {
    background-color: black;
    text-align: center;
    margin-top: 20%;
}
#preview {
    margin: 20px 0 0 0;
    padding: 0;
}
#preview li {
    border: 1px solid #ddd;
    float: left;
    height: 100px;
    list-style: none;
    margin: 0 10px 10px 0;
    overflow: hidden;
    position: relative;
    width: 150px;
}
#preview li canvas {
    left: 0;
    position: absolute;
    top: 0;
}
#preview li > div.progress {
    height: 10px;
    left: 0;
    position: absolute;
    top: 90px;
}
#preview li > div.remove {
    background-color: #fff;
    line-height: 13px;
    opacity: .8;
    padding: 2px;
    position: absolute;
    right: 0;
    top: 0;
    z-index: 2;
}
#preview li > div.remove:hover {
    color: red;
    cursor: pointer;
    cursor: hand;
}
</style>
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
                        <div id="container">
                            <div id="filelist">No runtime found.</div>
                            <br />
                            <a id="choose_photos" class="sub1" href="#">'.T_('Choose Photos').'</a>
                            <ul id="preview"></ul>
                        </div>
                    </div><!--/plupload-->
                </div>
                <div class="footer">
                    <input class="sub1" type="submit" id="submit-photos" name="addphoto" value="'.T_('Submit').'"/>
                </div>
            </form>
            <script type="text/javascript">
            Event.observe("submit-photos","click",function(e) {
            '.$this->getJsUploadValidation().'
            });
            </script>';
    }
}
