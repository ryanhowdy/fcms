<?php if (isset($TMPL['events'])): ?>
        <div id="todaysevents">
            <h2><?php echo $TMPL['textTodaysEvents']; ?></h2>
<?php foreach ($TMPL['events'] as $e): ?>
            <div class="events">
                <b><?php echo $e['title']; ?></b>
                <?php if (isset($e['desc'])): ?><span><?php echo $e['desc']; ?></span><?php endif; ?>
            </div>
<?php endforeach; ?>
        </div>
<?php endif; ?>

        <div id="status_update">
            <form method="post" action="home.php">
                <textarea id="status" name="status" 
                    placeholder="<?php echo $TMPL['textSharePlaceholder']; ?>" title="<?php echo $TMPL['textShareTitle']; ?>"></textarea>
<?php if (isset($TMPL['textUpdateFacebook'])): ?>
                <small>
                    <input type="checkbox" id="update_fb" name="update_fb"/>
                    <label for="update_fb"><?php echo $TMPL['textUpdateFacebook']; ?></label>
                </small>
<?php endif; ?>
                <input type="submit" id="status_submit" name="status_submit" value="<?php echo $TMPL['textSubmit']; ?>"/>
            </form>
        </div>
