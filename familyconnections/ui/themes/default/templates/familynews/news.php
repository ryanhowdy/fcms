
    <?php if (isset($TMPL['news']) && count($TMPL['news']) > 0) { ?>
        <?php foreach ($TMPL['news'] as $n) { ?>
            <div class="news-post">
                <h2>
                    <a href="<?php echo $n['url']; ?>"><?php echo $n['title']; ?></a>
                </h2>
                <span class="date">
                    <?php echo $n['updated']; ?> - <?php echo $n['displayname']; ?>
                </span>
                <p>
                    <?php if (isset($n['external'])) { ?>
                        <span style="background-color:#eee; color:#999; font-size:13px;">
                            <?php echo $n['external']; ?>
                        </span><br/>
                    <?php } ?>
                    <?php echo $n['news']; ?>
                </p>
                <p class="news-comments">
                    <a href="<?php echo $n['url']; ?>#comments"><?php echo $n['commentsText']; ?></a> - <?php echo $n['commentCount']; ?>
                </p>
            </div>
        <?php } ?>
    <?php } else { ?>
            <div class="blank-state">
                <h2><?php echo $TMPL['nothingToSeeHereText']; ?></h2>
                <h3><?php echo $TMPL['noOneAddedNewsText']; ?></h3>
                <h3><?php echo $TMPL['beFirstAddNewsText']; ?></h3>
                <ol>
                    <li><a href="?addnews=yes"><?php echo $TMPL['addFamilyNewsText']; ?></a></li>
                    <li><a href="settings.php?view=familynews"><?php echo $TMPL['importExistinBlogText']; ?></a></li>
                </ol>
            </div>
    <?php } ?>
