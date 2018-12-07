
            <div id="leftcolumn">
                <?php require_once TEMPLATES.'addressbook/menu.php'; ?>
            </div>

            <div id="maincolumn">

                <form action="addressbook.php" id="check_all_form" name="check_all_form" method="post">

                    <table id="address-table" cellspacing="0" cellpadding="0">
                        <thead>
                            <tr>
                                <th colspan="2"><div id="check-all"></div></th>
                                <th style="text-align:right" colspan="2"><a href="?add=yes"><?php echo $TMPL['addNewAddressText']; ?></a></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="header">
                                <td class="chk"></td> 
                                <td><?php echo $TMPL['nameText']; ?></td> 
                                <td><?php echo $TMPL['addressText']; ?></td> 
                                <td><?php echo $TMPL['phoneText']; ?></td> 
                            </tr>

                    <?php if (isset($TMPL['addresses'])): ?>
                        <?php foreach ($TMPL['addresses'] as $a): ?>
                            <tr>
                                <td class="chk"><?php echo $a['checkbox']; ?></td>
                                <td><a href="<?php echo $a['addressUrl']; ?>"><?php echo $a['name']; ?></a></td>
                                <td><?php echo $a['address']; ?></td>
                                <td><?php echo $a['phone']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                        </tbody>
                    </table>

                <?php if (isset($TMPL['allowedToEmail'])): ?>
                    <p class="alignright"><input class="sub1" type="submit" name="emailsubmit" value="<?php echo $TMPL['emailSelectedText']; ?>"/></p>
                <?php endif; ?>

                </form>
