
            <div id="sections_menu">
                <ul>
                    <li><a href="familynews.php"><?php echo $TMPL['latestNewsText']; ?></a></li>
                <?php if (isset($TMPL['myUserId'])): ?>
                    <li><a href="?getnews=<?php echo $TMPL['myUserId']; ?>"><?php echo $TMPL['myNewsText']; ?></a></li>
                <?php endif; ?>
                </ul>
            </div>
            <div id="actions_menu">
                <ul>
                    <li><a href="?addnews=yes"><?php echo $TMPL['addNewsText']; ?></a></li>
                </ul>
            </div>

        <?php if (isset($TMPL['newsMenu'])): ?>
            <div id="news-list">
                <h2><?php echo $TMPL['familyNewsText']; ?></h2>
                <ul>
                <?php foreach ($TMPL['newsMenu'] as $n): ?>
                    <li>
                        <a href="familynews.php?getnews=<?php echo $n['id']; ?>"><?php echo $n['displayname']; ?></a>
                         &nbsp;<small><?php echo $n['date']; ?></small>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
