
            <div class="news-post">
                <h2>
                    <a href="<?php echo $TMPL['url']; ?>"><?php echo $TMPL['title']; ?></a>
                </h2>
                <span class="date">
                    <?php echo $TMPL['updated']; ?> - <?php echo $TMPL['username']; ?>

                    <?php if (isset($TMPL['edit'])): ?>
                         &nbsp;
                        <form method="post" action="familynews.php">
                            <div>
                                <input type="hidden" name="user" value="<?php echo $TMPL['edit']['user']; ?>"/>
                                <input type="hidden" name="id" value="<?php echo $TMPL['edit']['id']; ?>"/>
                                <input type="hidden" name="title" value="<?php echo $TMPL['edit']['title']; ?>"/>
                                <input type="hidden" name="news" value="<?php echo $TMPL['edit']['news']; ?>"/>
                                <input type="submit" name="editnews" value="<?php echo $TMPL['edit']['editText']; ?>" 
                                    class="editbtn" title="<?php echo $TMPL['edit']['editThisFamilyNewsText']; ?>"/>
                            </div>
                        </form>
                    <?php endif; ?>
                    <?php if (isset($TMPL['delete'])): ?>
                         &nbsp;
                        <form class="delnews" method="post" action="familynews.php?getnews=<?php echo $TMPL['delete']['user']; ?>">
                            <div>
                                <input type="hidden" name="user" value="<?php echo $TMPL['delete']['user']; ?>"/>
                                <input type="hidden" name="id" value="<?php echo $TMPL['delete']['id']; ?>"/>
                                <input type="submit" name="delnews" value="<?php echo $TMPL['delete']['deleteText']; ?>" 
                                    class="delbtn" title="<?php echo $TMPL['delete']['deleteThisFamilyNewsText']; ?>"/>
                            </div>
                        </form>
                    <?php endif; ?>
                </span>
                <p>
                    <?php if (isset($TMPL['external'])): ?>
                        <span style="background-color:#eee; color:#999; font-size:13px;">
                            <?php echo $TMPL['external']; ?>
                        </span><br/>
                    <?php endif; ?>
                    <?php echo $TMPL['news']; ?>
                </p>
                <p class="news-comments">
                    <a href="<?php echo $TMPL['commentUrl']; ?>"><?php echo $TMPL['commentsText']; ?></a> - 
                    <?php echo $TMPL['commentCount']; ?>
                </p>
            </div>

            <h3 id="comments"><?php echo $TMPL['commentsText']; ?></h3>
            <p class="center">
                <form action="<?php echo $TMPL['addCommentUrl']; ?>" method="post">
                    <?php echo $TMPL['addCommentText']; ?><br/>
                    <input type="text" name="comment" id="comment" size="50" title="<?php echo $TMPL['addACommentText']; ?>"/> 
                    <input type="submit" name="addcom" id="addcom" value="<?php echo $TMPL['addText']; ?>" class="sub1"/>
                </form>
            </p>

            <p>&nbsp;</p>

            <?php if (count($TMPL['comments']) > 0): ?>

                <?php foreach ($TMPL['comments'] as $c): ?>
                    <div class="comment_block">
                        <?php if (isset($c['delete'])): ?>
                        <form class="delcom" action="<?php echo $c['delete']['url']; ?>" method="post">
                            <input type="submit" name="delcom" id="delcom" value="<?php echo $c['delete']['deleteText']; ?>" 
                                class="gal_delcombtn" title="<?php echo $c['delete']['deleteThisCommentText']; ?>"/>
                            <input type="hidden" name="id" value="<?php echo $c['delete']['id']; ?>">
                        </form>
                        <?php endif; ?>
                        <img class="avatar" alt="avatar" src="<?php echo $c['avatar']; ?>"/>
                        <b><?php echo $c['username']; ?></b>
                        <span><?php echo $c['date']; ?></span>
                        <p><?php echo $c['comment']; ?></p>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <p class="center"><?php echo $TMPL['noCommentsText']; ?></p>
            <?php endif; ?>
