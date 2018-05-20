<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="clear"></div>
<div class="updated notice" style="width: 95%;">
    <p>
        <?php echo str_replace("%s", $time, __('You use Dropbox backup and restore plugin successfully for more than %s. Please, leave a 5 star review for our development team, because it inspires us to develop this plugin for you.','dropbox-backup')) ; ?><br />
        <?php _e('Thank you!','dropbox-backup')?>
        <br />
        <a href="https://wordpress.org/support/view/plugin-reviews/dropbox-backup?filter=5"  ><?php _e('Leave review','dropbox-backup'); ?></a><br />
        <a href="<?php echo admin_url( 'admin-post.php?action=hide_notice&type=star' );?>"><?php _e('I already left a review','dropbox-backup'); ?></a><br />
        <a href="<?php echo admin_url( 'admin-post.php?action=hide_notice&type=star&hide=' . $hide );?>"><?php _e('Hide this message','dropbox-backup'); ?></a><br />
    </p>
</div>
