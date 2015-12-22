    <?php if (isset($TMPL['pageNavigation'])): ?>

        <?php if (isset($TMPL['pageNavigation']['section'])): ?>

            <div id="sections_menu">
                <ul>
            <?php foreach ($TMPL['pageNavigation']['section'] as $nav): ?>
                    <li><a href="<?php echo $nav['url']; ?>"><?php echo $nav['text']; ?></a></li>
            <?php endforeach; ?>
                </ul>
            </div>

        <?php endif; ?>

        <?php if (isset($TMPL['pageNavigation']['action'])): ?>

            <div id="actions_menu">
                <ul>
            <?php foreach ($TMPL['pageNavigation']['action'] as $action): ?>
                    <li><a href="<?php echo $action['url']; ?>"><?php echo $action['text']; ?></a></li>
            <?php endforeach; ?>
                </ul>
            </div>

        <?php endif; ?>

    <?php endif; ?>
