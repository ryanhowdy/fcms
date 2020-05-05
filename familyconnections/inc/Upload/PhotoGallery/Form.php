<?php
/**
 * Basic Form
 * 
 * @package Upload
 * @subpackage UploadPhotoGallery
 * @copyright 2014 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class UploadPhotoGalleryForm
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
        $_SESSION['fcms_uploader_type'] = 'basic';

        // Setup the list of active members for possible tags
        $sql = "SELECT `id` 
                FROM `fcms_users` 
                WHERE `activated` > 0
                ORDER BY `fname`, `lname`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $autocompleteList = '';
        foreach ($rows as $r)
        {
            $autocompleteList .= '{ data: "'.$r['id'].'", value: "'.cleanOutput(getUserDisplayName($r['id'], 2)).'" }, ';
        }
        $autocompleteList = substr($autocompleteList, 0, -2); // remove the extra comma space at the end

        // Display the form
        echo '
            <form id="autocomplete_form" enctype="multipart/form-data" action="?action=upload" method="post" class="photo-uploader">
                <div class="header">
                    <label>'.T_('Category').'</label>
                    '.$this->getCategoryInputs().'
                </div>
                <ul class="upload-types">
                    '.$this->getUploadTypesNavigation('upload').'
                </ul>
                <div class="upload-area">
                    <div class="basic">
                        <p style="float:right">
                            <a class="help" href="../help.php?topic=photo#gallery-howworks">'.T_('Help').'</a>
                        </p>
                        <p>
                            <label><b>'.T_('Photo').'</b></label><br/>
                            <input name="photo_filename" type="file" accept="image/*" size="50"/>
                        </p>
                        <p>
                            <label><b>'.T_('Caption').'</b></label><br/>
                            <input class="frm_text" type="text" name="photo_caption" size="50"/>
                        </p>
                        <div id="tag-options">
                            <label><b>'.T_('Who is in this Photo?').'</b></label><br/>
                            <input type="text" id="autocomplete_input" class="frm_text autocomplete_input" 
                                autocomplete="off" size="50" tabindex="3"/>
                            <div id="autocomplete_instructions" class="autocomplete_instructions">
                                '.T_('Type name of person...').'
                            </div>
                            <ul id="autocomplete_selected" class="autocomplete_selected"></ul>
                            <div id="autocomplete_search" class="autocomplete_search" style="display:none"></div>
                            <script type="text/javascript">
                            $(document).ready(function() {
                                var users = [ '.$autocompleteList.' ];
                                $("#autocomplete_input").autocomplete({
                                    lookup: users,
                                    showNoSuggestionNotice: true,
                                    noSuggestionNotice: "'.T_('No users found').'",
                                    tabDisabled: true,
                                    onSelect: function (suggestion) {
                                        $("#autocomplete_instructions").hide();
                                        $("#autocomplete_form").append(
                                            "<input type=\"hidden\" name=\"tagged[]\" class=\"tagged\" value=\"" + suggestion.data + "\">"
                                        );
                                        $("#autocomplete_input").val("").focus();
                                        $("#autocomplete_selected").append(
                                            "<li>" + suggestion.value + "<a href=\"#\" alt=\"" + suggestion.data + "\" "
                                                + "onclick=\"removeTagged(this);\">x</a></li>"
                                        );
                                    }
                                });
                            });
                            </script>
                        </div>
                        <p class="rotate-options">
                            <label><b>'.T_('Rotate').'</b></label><br/>
                            <input type="radio" id="left" name="rotate" value="left"/>
                            <label for="left" class="radio_label left">'.T_('Left').'</label>&nbsp;&nbsp; 
                            <input type="radio" id="right" name="rotate" value="right"/>
                            <label for="right" class="radio_label right">'.T_('Right').'</label>
                        </p>
                    </div><!--/basic-->
                </div>
                <div class="footer">
                    <input class="sub1" type="submit" id="submit-photos" name="addphoto" value="'.T_('Submit').'"/>
                </div>
            </form>
            <script type="text/javascript">
            $("#submit-photos").click(function(e) {
            '.$this->getJsUploadValidation().'
            });
            </script>';
    }

    /**
     * getCategoryInputs 
     * 
     * @return string
     */
    protected function getCategoryInputs ()
    {
        $categories = $this->getUserCategories();

        // We have existing categories
        if (count($categories) > 0)
        {
            return '
                    <input class="frm_text" type="text" id="new-category" name="new-category" size="35"/>
                    <select id="existing-categories" name="category">
                        <option value="0">&nbsp;</option>
                        '.buildHtmlSelectOptions($categories, '').'
                    </select>';
        }
        // No Categories (force creation of new one)
        else
        {
            return '
                    <input class="frm_text" type="text" id="new-category" name="new-category" size="50"/>';
        }
    }

    /**
     * getUploadTypesNavigation 
     * 
     * @param string $currentType 
     * 
     * @return string
     */
    protected function getUploadTypesNavigation ($currentType)
    {
        $nav = '';

        $types = array(
            'upload',
            'facebook',
            'picasa',
            'instagram'
        );

        foreach ($types as $type)
        {
            $url   = '';
            $class = $currentType == $type ? 'current' : '';
            $text  = '';

            if ($type == 'upload')
            {
                $type  = getUploaderType($this->fcmsUser->id);
                $url   = '?action=upload&amp;type='.$type;
                $text  = T_('Computer');
            }
            elseif ($type == 'instagram')
            {
                $config  = getInstagramConfigData();
                if (empty($config['instagram_client_id']) || empty($config['instagram_client_secret']))
                {
                    continue;
                }

                $url   = '?action=upload&amp;type=instagram';
                $text  = 'Instagram';
            }
            elseif ($type == 'picasa')
            {
                $url   = '?action=upload&amp;type=picasa';
                $text  = 'Picasa';
            }
            elseif ($type == 'facebook')
            {
                $config = getFacebookConfigData();
                if (empty($config['fb_app_id']) && empty($config['fb_secret']))
                {
                    continue;
                }

                $url   = '?action=upload&amp;type=facebook';
                $text  = 'Facebook';
            }
            else
            {
                die('Invalid upload type.');
            }

            $nav .= '
                    <li class="'.$class.'"><a href="'.$url.'">'.$text.'</a></li>';
        }

        return $nav;
    }

    /**
     * getJsUploadValidation 
     * 
     * @return string
     */
    protected function getJsUploadValidation ()
    {
        return '
                if ($("#new-category").is(":visible") && !($("#new-category").val())) {
                    e.preventDefault();
                    $("#new-category").addClass("LV_invalid_field");
                    $("#new-category").focus();
                    return;
                }
                else if ($("#existing-categories").is(":visible") && $("#existing-categories").val() == "0")
                {
                    e.preventDefault();
                    $("#existing-categories").addClass("LV_invalid_field");
                    $("#existing-categories").focus();
                    return;
                }';
    }

    /**
     * getUserCategories 
     * 
     * Returns an array of the categories for the given user.
     *
     * @param int $userid 
     *
     * @return  array
     */
    protected function getUserCategories ($userid = 0)
    {
        if ($userid == 0)
        {
            $userid = $this->fcmsUser->id;
        }

        $sql = "SELECT `id`, `name` FROM `fcms_category` 
                WHERE `user` = ?
                AND `type` = 'gallery'
                ORDER BY `id` DESC";

        $rows = $this->fcmsDatabase->getRows($sql, $userid);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        $categories = array();

        foreach ($rows as $row)
        {
            $categories[$row['id']] = $row['name'];
        }

        return $categories;
    }
}
