
                <div class="<?php echo $TMPL['type']; ?>-alert">
                    <?php if (isset($TMPL['title'])) { ?><h3><?php echo $TMPL['title']; ?></h3><?php } ?>
                    <?php if (isset($TMPL['messages'])) { ?>
                        <?php foreach ($TMPL['messages'] as $msg) { ?>
                        <p><?php echo $msg; ?></p>
                        <?php } ?>
                    <?php } ?>
                </div>
