
            <table id="day-calendar">
                <tr>
                    <th class="header" colspan="2">
                        <div class="navigation">
                            <a class="prev" href="<?php echo $TMPL['prevUrl']; ?>"><?php echo $TMPL['previousText']; ?></a> 
                            <a class="today" href="<?php echo $TMPL['todayUrl']; ?>"><?php echo $TMPL['todayText']; ?></a> 
                            <a class="next" href="<?php echo $TMPL['nextUrl']; ?>"><?php echo $TMPL['nextText']; ?></a>
                        </div>
                        <h3><?php echo $TMPL['header']; ?></h3>
                        <div class="views">
                            <a class="day" href="<?php echo $TMPL['dayViewUrl']; ?>"><?php echo $TMPL['dayText']; ?></a> | 
                            <a class="month" href="<?php echo $TMPL['monthViewUrl']; ?>"><?php echo $TMPL['monthText']; ?></a>
                        </div>
                    </th>
                </tr>
                <tr>
                    <td class="all-day"></td>
                    <td class="time-event-data">
            <?php foreach ($TMPL['allDayEvents'] as $e): ?>
                        <div class="event">
                            <a class="<?php echo $e['class']; ?>" href="<?php echo $e['url']; ?>">
                                <?php echo $e['title']; ?>
                                <span><?php $e['desc']; ?></span>
                            </a>
                        </div>
            <?php endforeach; ?>
                    </td>
                </tr>
            <?php foreach ($TMPL['times'] as $t): ?>
                <tr>
                    <td class="time <?php echo $t['class']; ?>"><?php echo $t['time']; ?></td>
                    <td class="time-event-data">
                <?php foreach ($t['events'] as $e): ?>
                        <div class="event">
                            <a class="<?php echo $e['class']; ?>" href="<?php echo $e['url']; ?>">
                                <i><?php echo $e['start']; ?> - <?php echo $e['end']; ?></i>
                                <?php echo $e['title']; ?>
                                <span><?php echo $e['desc']; ?></span>
                            </a>
                        </div>
                <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
                <tr class="actions">
                    <td style="text-align:left;">
                        <b><?php echo $TMPL['categoriesText']; ?></b><br/>
                        <ul id="category_menu">
                    <?php foreach ($TMPL['categories'] as $c): ?>
                            <li class="cat <?php echo $c['class']; ?>">
                                <a title="<?php echo $TMPL['editCategoryText']; ?>" 
                                    href="<?php echo $c['url']; ?>"><?php echo $c['name']; ?></a>
                            </li>
                    <?php endforeach; ?>
                            <li><a href="?category=add"><?php echo $TMPL['addCategoryText']; ?></a></li>
                        </ul>
                    </td>
                    <td>
                        <?php echo $TMPL['actionsText']; ?>
                        <a class="print" href="#" 
                            onclick="window.open('<?php echo $TMPL['printUrl']; ?>',
                            'name','width=700,height=400,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no'); 
                            return false;"><?php echo $TMPL['printText']; ?></a> | 
                        <a href="?import=true"><?php echo $TMPL['importText']; ?></a> | 
                        <a href="?export=true"><?php echo $TMPL['exportText']; ?></a>
                    </td>
                </tr>
            </table>
