
                <b><?php echo $TMPL['viewText']; ?></b>
                <ul class="address-categories">

                <?php foreach ($TMPL['categories'] as $c) { ?>
                    <li class="<?php echo $c['liClass']; ?>"><a title="<?php echo $c['title']; ?>" href="<?php echo $c['url']; ?>"><?php echo $c['text']; ?></a></li>
                <?php } ?>

                </ul>

                <b><?php echo $TMPL['optionText']; ?></b>
                <ul class="address-options">

                <?php foreach ($TMPL['options'] as $o) { ?>
                    <li><a href="<?php echo $o['url']; ?>"><?php echo $o['text']; ?></a></li>
                <?php } ?>

                </ul>
