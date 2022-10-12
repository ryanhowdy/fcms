
            <table id="big-calendar" cellpadding="0" cellspacing="0">
                <tr>
                    <th colspan="2">
                        <a class="prev" href="<?php echo $TMPL['prevUrl']; ?>"><?php echo $TMPL['previousText']; ?></a> 
                        <a class="today" href="<?php echo $TMPL['todayUrl']; ?>"><?php echo $TMPL['todayText']; ?></a> 
                        <a class="next" href="<?php echo $TMPL['nextUrl']; ?>"><?php echo $TMPL['nextText']; ?></a>
                    </th>
                    <th colspan="3"><h3><?php echo $TMPL['monthYear']; ?></h3></th>
                    <th class="views" colspan="2">
                        <a class="day" href="<?php echo $TMPL['dayViewUrl']; ?>"><?php echo $TMPL['dayText']; ?></a> | 
                        <a class="month" href="<?php echo $TMPL['monthViewUrl']; ?>"><?php echo $TMPL['monthText']; ?></a>
                    </th>
                </tr>

                <tr>
        <?php foreach ($TMPL['weekDays'] as $name): ?>
                    <td class="weekDays"><?php echo $name; ?></td>
        <?php endforeach; ?>
                </tr>

        <?php foreach ($TMPL['weeks'] as $w): ?>
                <tr>
            <?php foreach ($w as $d): ?>
                    <td class="<?php echo $d['class']; ?>">
                        <?php if (isset($d['addUrl'])): ?><a class="add" href="<?php echo $d['addUrl']; ?>"><?php echo $d['addText']; ?></a><?php endif; ?>
                        <?php if (isset($d['dayUrl'])): ?><a href="<?php echo $d['dayUrl']; ?>"><?php echo $d['day']; ?></a><?php endif; ?>

                <?php if (isset($d['events'])): ?>
                <?php foreach ($d['events'] as $e): ?>
                        <div class="event">
                            <a class="<?php echo $e['class']; ?> tooltip" 
                                title="<?php echo $e['start']; ?><?php echo $e['end']; ?> <?php echo $e['title']; ?>" 
                                href="<?php echo $e['url']; ?>" 
                                onmouseover="showTooltip(this)" 
                                onmouseout="hideTooltip(this)"><i><?php echo $e['start']; ?></i> <?php echo $e['title']; ?></a>
                            <div class="tooltip" style="display:none"><?php echo $e['details']; ?></div>
                        </div>
                <?php endforeach; ?>
                <?php endif; ?>

                    </td>
            <?php endforeach; ?>
                </tr>
        <?php endforeach; ?>

                <tr class="actions">
                    <td style="text-align:left;" colspan="3">
                        <b><?php echo $TMPL['categoriesText']; ?></b><br/>
                        <ul id="category_menu">
                    <?php foreach ($TMPL['categories'] as $c): ?>
                            <li class="cat <?php echo $c['class']; ?>">
                                <a title="<?php echo $TMPL['editCategoryText']; ?>" href="<?php echo $c['url']; ?>"><?php echo $c['name']; ?></a>
                            </li>
                    <?php endforeach; ?>
                            <li><a href="?category=add"><?php echo $TMPL['addCategoryText']; ?></a></li>
                        </ul>
                    </td>
                    <td colspan="4">
                        <?php echo $TMPL['actionsText']; ?>: 
                        <a class="print" href="#" 
                            onclick="window.open('<?php echo $TMPL['printUrl']; ?>',
                            'name','width=700,height=400,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no'); 
                            return false;"><?php echo $TMPL['printText']; ?></a> | 
                        <a href="?import=true"><?php echo $TMPL['importText']; ?></a> | 
                        <a href="?export=true"><?php echo $TMPL['exportText']; ?></a>
                    </td>
                </tr>
            </table>

