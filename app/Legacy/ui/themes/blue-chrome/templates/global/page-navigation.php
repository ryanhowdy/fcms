        <?php if (isset($TMPL['navigation'])): ?>
            <div id="sections_menu">
                <ul>
            <?php foreach ($TMPL['navigation'] as $nav): ?>
                    <li><a href="<?php echo $nav['url']; ?>"><?php echo $nav['textLink']; ?></a></li>
            <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (isset($TMPL['actions'])): ?>
            <div id="actions_menu">
                <ul>
            <?php foreach ($TMPL['actions'] as $action): ?>
                    <li><a href="<?php echo $action['url']; ?>"><?php echo $action['textLink']; ?></a></li>
            <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
