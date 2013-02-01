<?php

/**
 * displayComments 
 * 
 * Valid params:
 *
 *  currentUserId - The current user's id.
 *  id            - The id of the thing we are commenting on.
 *  header        - The header for the entire comments block. Defaults to ''.
 *  label         - The label for the text area. Defaults to 'Add Comment'.
 *  submit        - The value for the submit button. Defaults to 'Comment'.
 *  submitClass   - The class for the style of the submit button.  Defaults to 'sub1'.
 *  hidden        - An array of hidden inputs for the add form. Key is name, value is value.
 * 
 * @param string $url 
 * @param string $type
 * @param array  $params 
 * 
 * @return void
 */
function displayComments ($url, $type, $params = null)
{
    $addForm  = getAddCommentsForm($url, $params);
    $comments = getComments($url, $type, $params);

    $header = '';
    if (isset($params['header']))
    {
        $header = '<h2>'.$params['header'].'</h2>';
    }

    echo '
        <div id="comments">
            '.$header.'
            '.$comments.'
            '.$addForm.'
        </div>';
}

/**
 * getComments 
 * 
 * @param string $url
 * @param string $type
 * @param string $params 
 * 
 * @return void
 */
function getComments ($url, $type, $params)
{
    $comments = '';

    switch ($type)
    {
        case 'video':

            $comments = getVideoComments($url, $params);
            break;

        default:

            printr(debug_backtrace());
            die("Invalid Type for getComments");
            break;
    }

    return $comments;
}

/**
 * getAddCommentsForm 
 * 
 * See params for displayComments
 * 
 * @param string $url 
 * @param array  $params 
 * 
 * @return string
 */
function getAddCommentsForm ($url, $params = null)
{
    // Defaults
    $label       = '<h3>'.T_('Add Comment').'</h2>';
    $submit      = T_('Comment');
    $submitClass = 'sub1';
    $hidden      = '';

    // Handle any params
    if (is_array($params))
    {
        if (isset($params['label']))
        {
            $label = '<h2>'.$params['label'].'</h2>';
        }

        if (isset($params['submit']))
        {
            $submit = '<h2>'.$params['submit'].'</h2>';
        }

        if (isset($params['submitClass']))
        {
            $submitClass = '<h2>'.$params['submitClass'].'</h2>';
        }

        if (isset($params['hidden']) && is_array($params['hidden']))
        {
            foreach ($params['hidden'] as $key => $val)
            {
                $hidden .= '<input type="hidden" name="'.$key.'" value="'.$val.'">';
            }
        }

    }

    return '
            <div id="addcomments">
                <form action="'.$url.'" method="post">
                    '.$label.'
                    <textarea class="frm_textarea" name="comments" rows="3" cols="63"></textarea>
                    '.$hidden.'
                    <p><input class="'.$submitClass.'" type="submit" name="addcomment" id="addcomment" value="'.$submit.'" title="'.$label.'"/></p>
                </form>
            </div>';
}

/**
 * getVideoComments 
 * 
 * Valid params:
 * 
 *  currentUserId - The current user's id.
 *  id            - The id of the video.
 * 
 * @param string $url
 * @param string $params 
 * 
 * @return void
 */
function getVideoComments ($url, $params)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = new User($fcmsError, $fcmsDatabase);

    $comments = '';

    if (!isset($params['id']))
    {
        die("Missing Video ID or User ID for getVideoComments");
    }

    $id = $params['id'];

    $sql = "SELECT c.`id`, c.`comment`, c.`created`, c.`updated`, u.`fname`, u.`lname`, c.`created_id`, u.`avatar`, u.`gravatar`, s.`timezone`
            FROM `fcms_video_comment` AS c
            LEFT JOIN `fcms_users` AS u ON c.`created_id` = u.`id`
            LEFT JOIN `fcms_user_settings` AS s ON u.`id` = s.`user`
            WHERE `video_id` = '$id' 
            ORDER BY `updated`";

    $rows = $fcmsDatabase->getRows($sql, $id);
    if ($rows === false)
    {
        $fcmsError->displayError();
        return;
    }

    foreach ($rows as $row)
    {
        $del_comment = '';
        $date        = fixDate(T_('F j, Y g:i a'), $row['timezone'], $row['updated']);
        $displayname = $row['fname'].' '.$row['lname'];
        $comment     = $row['comment'];
        $avatarPath  = getAvatarPath($row['avatar'], $row['gravatar']);

        if ($fcmsUser->id == $row['created'] || $fcmsUser->access < 2)
        {
            $del_comment .= '<input type="submit" name="delcom" id="delcom" '
                . 'value="'.T_('Delete').'" class="gal_delcombtn" title="'
                . T_('Delete this Comment') . '"/>';
        }

        $comments .= '
                <div class="comment">
                    <form class="delcom" action="'.$url.'" method="post">
                        '.$del_comment.'
                        <img class="avatar" alt="avatar" src="'.$avatarPath.'"/>
                        <b>'.$displayname.'</b>
                        <span>'.$date.'</span>
                        <p>
                            '.parse($comment).'
                        </p>
                        <input type="hidden" name="id" value="'.$row['id'].'">
                    </form>
                </div>';
    }

    return $comments;
}
