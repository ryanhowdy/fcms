
            <div class="info-alert">
                <form action="<?php echo $TMPL['formUrl']; ?>" method="post">
                    <h2><?php echo T_('Are you sure you want to DELETE this?'); ?></h2>
                    <p><b><i><?php echo T_('This can NOT be undone.'); ?></i></b></p>
                    <div>
                        <input type="hidden" name="id" value="<?php echo $TMPL['id']; ?>"/>
                        <input type="hidden" name="confirmed" value="1"/>
                        <input style="float:left;" type="submit" id="delconfirm" class="sub1" name="delete" value="<?php echo T_('Yes'); ?>"/>
                        <a style="float:right;" href="<?php echo $TMPL['cancelUrl']; ?>"><?php echo T_('Cancel'); ?></a>
                    </div>
                </form>
            </div>
