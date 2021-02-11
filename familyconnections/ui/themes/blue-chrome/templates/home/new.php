        <h2><?php echo $TMPL['textWhatsNew']; ?></h2>

<?php foreach ($TMPL['new'] as $new) { ?>
    <?php if (isset($new['textDateHeading'])) { ?>
        <p><b><?php echo $new['textDateHeading']; ?></b></p>
    <?php } else { ?>
        <div id="<?php echo $new['position']; ?>" class="new <?php echo $new['class']; ?>">
            <div class="avatar">
                <img src="<?php echo $new['avatar']; ?>" alt="<?php echo $new['displayname']; ?>"/>
            </div>
            <div class="info">
                <a class="u" href="profile.php?member=<?php echo $new['userId']; ?>"><?php echo $new['displayname']; ?></a> &nbsp;- &nbsp;
                <small><i><?php echo $new['timeSince']; ?></i></small>
                <p><?php echo $new['textInfo']; ?></p>

        <?php if (isset($new['title']) && !empty($new['title'])) { ?>
                <div class="object">
                    <h5><?php echo $new['title']; ?></h5>
                    <?php echo $new['details']; ?>
                </div>
        <?php } ?>

        <?php if (isset($new['children'])) { ?>
            <?php foreach ($new['children'] as $child) { ?>
                <div class="child <?php echo $child['class']; ?>">
                    <div class="avatar">
                        <img src="<?php echo $child['avatar']; ?>" alt="<?php echo $child['displayname']; ?>"/>
                    </div>
                    <div class="info">
                        <a class="u" href="profile.php?member=<?php echo $child['userId']; ?>"><?php echo $child['displayname']; ?></a> &nbsp;- &nbsp;
                        <small><i><?php echo $child['timeSince']; ?></i></small>
                        <p><?php echo $child['textInfo']; ?></p>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>

        <?php if (isset($new['textReply'])) { ?>
                <div id="status_reply">
                    <form method="post" action="home.php">
                        <textarea id="status" name="status" placeholder="<?php echo $new['textReply']; ?>" title="<?php echo $new['textReply']; ?>"></textarea>
                        <input type="hidden" id="parent" name="parent" value="<?php echo $new['replyParentId']; ?>"/>
                        <input type="submit" id="status_submit" name="status_submit" value="<?php echo $new['textReply']; ?>"/>
                    </form>
                </div>
        <?php } ?>

            </div>
        </div>
    <?php } ?>
<?php } ?>

<?php if (empty($TMPL['new'])) { ?>
        <div class="blank-state">
            <h2><?php echo $TMPL['textBlankHeader']; ?></h2>
            <h3><?php echo $TMPL['textBlankDescription']; ?></h3>
        </div>
<?php } ?>

        <p class="alignright">
            <a class="rss" href="rss.php?feed=all"><?php echo $TMPL['textRssFeed']; ?></a>
        </p>

<?php if (isset($TMPL['page'])) { ?>
        <p class="more-whats-new">
            <a href="?page=<?php echo $TMPL['page']; ?>"><?php echo $TMPL['txtMore']; ?></a>
        </p>
<?php } ?>
