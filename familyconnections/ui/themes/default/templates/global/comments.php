        <div id="comments">
        <?php if (isset($TMPL['comments'])): ?>
        <?php foreach ($TMPL['comments'] as $comment): ?>
            <div class="comment">
                <form class="<?php echo $comment['formClass']; ?>" action="<?php echo $comment['formUrl']; ?>" method="post">
                <?php if (isset($comment['textDelete'])): ?>
                    <input type="submit" name="delcom" id="delcom" value="<?php echo $comment['textDelete']; ?>" 
                        class="<?php echo $comment['deleteClass']; ?>" title="<?php echo $comment['deleteTitle']; ?>"/>
                <?php endif; ?>
                    <img class="avatar" alt="avatar" src="<?php echo $comment['avatar']; ?>"/>
                    <b><?php echo $comment['displayname']; ?></b>
                    <span><?php echo $comment['date']; ?></span>
                    <p><?php echo $comment['comment']; ?></p>
                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                </form>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>

        <div id="addcomments">
            <form action="<?php echo $TMPL['addCommentUrl']; ?>" method="post">
                <h2><?php echo $TMPL['textAddCommentLabel']; ?></h2>
                <textarea class="frm_textarea" name="comments" rows="3" cols="63"></textarea>
            <?php if (isset($TMPL['addCommentHiddenInputs'])): ?>
            <?php foreach ($TMPL['addCommentHiddenInputs'] as $name => $value): ?>
                <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>">
            <?php endforeach; ?>
            <?php endif; ?>
                <p>
                    <input class="<?php echo $TMPL['addCommentSubmitClass']; ?>" type="submit" name="addcomment" id="addcomment" 
                        value="<?php echo $TMPL['addCommentSubmitValue']; ?>" title="<?php echo $TMPL['addCommentSubmitTitle']; ?>"/>
                </p>
            </form>
        </div>
