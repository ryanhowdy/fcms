
            <div class="pagination pages">
                <ul>

        <?php foreach ($TMPL['pages'] as $p): ?>
                    <li class="<?php echo $p['liClass']; ?>">
                        <a title="<?php echo $p['linkTitle']; ?>" 
                            class="<?php echo $p['linkClass']; ?>" 
                            href="<?php echo $p['linkUrl']; ?>"><?php echo $p['linkText']; ?></a>
                    </li>
        <?php endforeach; ?>

                </ul>
            </div>
