    <nav class="navbar navbar-default">
        <div class="container-fluid">

            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="<?php echo $TMPL['path'].'index.php'; ?>"><?php echo T_pgettext('The beginning or starting place.', 'Home'); ?></a>
                    </li>
                    <li class=dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                            href="<?php echo $TMPL['path'].$TMPL['nav-link'][2][0]['url']; ?>"><?php echo $TMPL['nav-link']['my-stuff']; ?></a>
                        <ul class="dropdown-menu">
            <?php foreach($TMPL['nav-link'][2] as $nav): ?>
                            <li><a href="<?php echo $TMPL['path'].$nav['url']; ?>"><?php echo $nav['text']; ?></a></li>
            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                            href="<?php echo $TMPL['path'].$TMPL['nav-link'][3][0]['url']; ?>"><?php echo T_('Communicate'); ?></a>
                        <ul class="dropdown-menu">
            <?php foreach($TMPL['nav-link'][3] as $nav): ?>
                            <li><a href="<?php echo $TMPL['path'].$nav['url']; ?>"><?php echo $nav['text']; ?></a></li>
            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                            href="<?php echo $TMPL['path'].$TMPL['nav-link'][4][0]['url']; ?>"><?php echo T_('Share'); ?></a>
                        <ul class="dropdown-menu">
            <?php foreach($TMPL['nav-link'][4] as $nav):?>
                            <li><a href="<?php echo $TMPL['path'].$nav['url']; ?>"><?php echo $nav['text']; ?></a></li>
            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                            href="<?php echo $TMPL['path'].'members.php'; ?>"><?php echo T_('Misc.'); ?></a>
                        <ul class="dropdown-menu">
            <?php foreach($TMPL['nav-link'][5] as $nav):?>
                            <li><a href="<?php echo $TMPL['path'].$nav['url']; ?>"><?php echo $nav['text']; ?></a></li>
            <?php endforeach; ?>
                        </ul>
                    </li>
            <?php if (isset($TMPL['nav-link'][6])): ?>
                    <li>
                        <a href="<?php echo $TMPL['path'].'admin/index.php';?>"><?php echo T_('Administration');?></a>
                    </li>
            <?php endif; ?>
                </ul>
            </div><!--/navbar-collapse-->
        </div><!--/container-fluid-->
    </nav>
