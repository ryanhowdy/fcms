                <form class="<?php echo $TMPL['pollFormClass']; ?>" method="post" action="polls.php">
                    <h3><?php echo $TMPL['pollQuestion']; ?></h3>
                <?php foreach ($TMPL['pollOptions'] as $option): ?>
                    <p>
                        <label class="radio_label">
                            <input type="radio" name="option" value="<?php echo $option['id']; ?>"/>
                            <?php echo $option['text']; ?>
                        </label>
                    </p>
                <?php endforeach; ?>
                <?php if (isset($TMPL['textCommentsCount'])): ?>
                        <a href="#comments"><?php echo $TMPL['textCommentsCount']; ?></a><br/>
                <?php endif; ?>
                    <p class="actions">
                        <input type="hidden" id="id" name="id" value="<?php echo $TMPL['pollId']; ?>"/>
                        <input type="submit" id="vote" name="vote" value="<?php echo $TMPL['textPollVote']; ?>"/>
                    </p>
                <?php if (isset($TMPL['textPollResults'])): ?>
                    <a href="polls.php?id=<?php echo $TMPL['pollId']; ?>&amp;results=1"><?php echo $TMPL['textPollResults']; ?></a> |
                    <a href="polls.php?action=pastpolls"><?php echo $TMPL['textPastPolls']; ?></a>
                <?php endif; ?>
                </form>
