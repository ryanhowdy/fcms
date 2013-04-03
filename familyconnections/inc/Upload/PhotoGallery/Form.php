<?php
/**
 * FormUpload 
 * 
 * @package Family Connections
 * @copyright 2013 Haudenschilt LLC
 * @author Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class FormUpload
{
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
    }

    public function display ()
    {
        $_SESSION['fcms_uploader_type'] = 'basic';

        // Setup the list of active members for possible tags
        $sql = "SELECT `id` 
                FROM `fcms_users` 
                WHERE `activated` > 0";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            return;
        }

        foreach ($rows as $r)
        {
            $members[$r['id']] = getUserDisplayName($r['id'], 2);
        }
        asort($members);

        $usersList = '';
        $usersLkup = '';

        foreach ($members as $key => $value)
        {
            $usersList .= '"'.$key.': '.cleanOutput($value).'", ';
            $usersLkup .= 'usersLkup["'.$key.'"] = "'.cleanOutput($value).'"; ';
        }

        $usersList = substr($usersList, 0, -2); // remove the extra comma space at the end

        // Display the form
        echo '
            <script type="text/javascript" src="../ui/js/scriptaculous.js"></script>
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
                            <input name="photo_filename" type="file" size="50"/>
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
                            Event.observe(window, "load", function() {
                                var usersList = [ '.$usersList.' ];
                                var usersLkup = new Array();
                                '.$usersLkup.'
                                new Autocompleter.Local(
                                    "autocomplete_input", "autocomplete_search", usersList, {
                                        fullSearch: true,
                                        partialChars: 1,
                                        updateElement: newUpdateElement
                                    }
                                );
                            });
                            </script>
                        </div>
                        <p class="rotate-options">
                            <label><b>'.T_('Rotate').'</b></label><br/>
                            <input type="radio" id="left" name="rotate" value="left"/>
                            <label for="left" class="radio_label">'.T_('Left').'</label>&nbsp;&nbsp; 
                            <input type="radio" id="right" name="rotate" value="right"/>
                            <label for="right" class="radio_label">'.T_('Right').'</label>
                        </p>
                    </div><!--/basic-->
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
     * @return void
     */
    protected function getUploadTypesNavigation ($currentType)
    {
        $nav = '';

        $types = array('upload', 'instagram', 'picasa');
        foreach ($types as $type)
        {
            $url   = '';
            $class = $currentType == $type ? 'current' : '';
            $text  = '';

            if ($type == 'upload')
            {
                $url   = '?action=upload&amp;type=basic';
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
                if ($("new-category").visible() && $F("new-category").empty()) {
                    Event.stop(e);
                    $("new-category").addClassName("LV_invalid_field");
                    $("new-category").focus();
                    return;
                }
                else if ($("existing-categories") != undefined && $("existing-categories").visible() && $F("existing-categories") <= 0)
                {
                    Event.stop(e);
                    $("existing-categories").addClassName("LV_invalid_field");
                    $("existing-categories").focus();
                    return;
                }';
    }

    /**
     * getUserCategories 
     * 
     * Returns an array of the categories for the given user.
     *
     * @param   int     $userid 
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
