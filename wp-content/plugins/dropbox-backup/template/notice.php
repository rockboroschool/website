<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="clear"></div>
<div class="update-nag" style="width: 95%;">
    <?php _e('Professional version of','dropbox-backup'); ?>
    "<a href="javascript:showProWPADMDescription();" title="<?php _e('Dropbox backup and restore"','dropbox-backup')?>" alt="<?php _e('Dropbox backup and restore','dropbox-backup')?>"><?php _e('Dropbox backup and restore','dropbox-backup')?></a>"
    <?php  _e(' plugin is now available!','dropbox-backup'); ?>
    <a href="javascript:showProWPADMDescription();" title="<?php _e('Read more...','dropbox-backup')?>" alt="<?php _e('Read more...','dropbox-backup')?>"><?php _e('Read more...','dropbox-backup')?></a>
    <a href="<?php echo admin_url( 'admin-post.php?action=hide_notice&type=preview' ); ?>" style="float: right; font-size: 12px;">[<?php _e('hide this message','dropbox-backup')?>]</a>
    <div id="pro-plugin-description" style="margin-top:15px;display: none;">
        <?php include 'advantage-plugin.php'; ?> 
    </div>
    <script>
        function showProWPADMDescription()
        {
            var disp = jQuery('#pro-plugin-description').css('display');
            if (disp == 'none') {
                jQuery('#pro-plugin-description').show(700);
            } else {
                jQuery('#pro-plugin-description').hide(700);
            }
        }
    </script>
</div>
