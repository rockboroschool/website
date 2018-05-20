<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>   
<div class="inline block-advantage" >
    <?php if (!isset($repeat_advantage)) {?>
        <span style="font-size:16px;">
            <?php _e('Use Professional version of "Dropbox backup and restore" plugin and get:','dropbox-backup') ; ?>
        </span>
        <?php } ?>
    <ul class="list-dropbox-backup-pro <?php echo !isset($repeat_advantage) ? '' : 'repeat_advantage'?>">
        <li>
            <div class="inline">
                <img src="<?php echo ( !isset($repeat_advantage) ? plugins_url('/template/ok-icon.png', dirname(__FILE__)) : plugins_url('/template/ico_ok.png', dirname(__FILE__) ) );?>" title="" alt="" />
            </div>
            <div class="inline">
                <span class="text">
                    <?php _e('Automated Dropbox backup','dropbox-backup') ; ?> <?php echo ( !isset($repeat_advantage) ? '' : '<br />' ) ?>
                    <?php _e('(Scheduled backup tasks)','dropbox-backup')?>
                </span>
            </div>
        </li>
        <li>
            <div class="inline">
                <img src="<?php echo ( !isset($repeat_advantage) ? plugins_url('/template/ok-icon.png', dirname(__FILE__)) : plugins_url('/template/ico_ok.png', dirname(__FILE__) ) );?>" title="" alt="" />
            </div>
            <div class="inline">
                <span class="text">
                    <?php _e('Automated Local backup','dropbox-backup') ; ?> <?php echo ( !isset($repeat_advantage) ? '' : '<br />' ) ?>
                    <?php _e('(Scheduled backup tasks)','dropbox-backup')?>
                    
                </span>
            </div>
        </li>
        <li>
            <div class="inline">
                <img src="<?php echo ( !isset($repeat_advantage) ? plugins_url('/template/ok-icon.png', dirname(__FILE__)) : plugins_url('/template/ico_ok.png', dirname(__FILE__) ) );?>" title="" alt="" />
            </div>
            <div class="inline">
                <span class="text">
                    <?php _e('Backup Status E-Mail Reporting','dropbox-backup') ; ?>
                </span>
            </div>
        </li>
        <li>
            <div class="inline">
                <img src="<?php echo ( !isset($repeat_advantage) ? plugins_url('/template/ok-icon.png', dirname(__FILE__)) : plugins_url('/template/ico_ok.png', dirname(__FILE__) ) );?>" title="" alt="" />
            </div>
            <div class="inline">
                <span class="text">
                    <?php _e('Online Service "Backup Website Manager"','dropbox-backup') ; ?> <?php echo ( !isset($repeat_advantage) ? '' : '<br />' ) ?>
                     <?php _e('(Copy, Clone or Migrate of websites)','dropbox-backup')?>
                </span>
            </div>
        </li>
        <li>
            <div class="inline">
                <img src="<?php echo ( !isset($repeat_advantage) ? plugins_url('/template/ok-icon.png', dirname(__FILE__)) : plugins_url('/template/ico_ok.png', dirname(__FILE__) ) );?>" title="" alt="" />
            </div>
            <div class="inline">
                <span class="text">
                    <?php _e('One Year Free Updates for PRO version','dropbox-backup') ; ?>
                </span>
            </div>
        </li>
        <li>
            <div class="inline">
                <img src="<?php echo ( !isset($repeat_advantage) ? plugins_url('/template/ok-icon.png', dirname(__FILE__)) : plugins_url('/template/ico_ok.png', dirname(__FILE__) ) );?>" title="" alt="" />
            </div>
            <div class="inline">
                <span class="text">
                    <?php _e('One Year Priority support','dropbox-backup') ; ?>
                </span>
            </div>
        </li>
    </ul>
</div>
<div class="<?php echo ( !isset($repeat_advantage) ) ? 'inline-right' : 'inline repeat_advantage'; ?>" style="">
    <?php if (!isset($repeat_advantage)) {?>
        <div class="image-dropbox-pro" onclick="document.dropbox_pro_form.submit();">
            <img src="<?php echo plugins_url('/template/dropbox_pro_logo_box1.png', dirname(__FILE__));?>" title="<?php _e('Get PRO version','dropbox-backup');?>" alt="<?php _e('Get PRO version','dropbox-backup'); ?>">
        </div>
        <?php } ?>
    <style>
          
          .block-pay.repeat_advantage {
              margin-top: 50%; 
          }
          
          
    </style>
    <div class="block-pay <?php echo (!isset($repeat_advantage) ? '' : 'repeat_advantage' ); ?>" style="">
        <?php if (!isset($repeat_advantage)) {?>
            <form action="<?php echo esc_url( WPADM_URL_PRO_VERSION ); ?>api/" method="post" id="dropbox_pro_form" name="dropbox_pro_form" >

                <input type="hidden" value="<?php echo home_url();?>" name="site">
                <input type="hidden" value="<?php echo 'proBackupPay'?>" name="actApi">
                <input type="hidden" value="<?php echo get_option('admin_email');?>" name="email">
                <input type="hidden" value="<?php echo 'dropbox-backup';?>" name="plugin">
                <input type="hidden" value="<?php echo (DRBBACKUP_MULTI) ? network_admin_url("admin.php?page=wpadm_wp_full_backup_dropbox&pay=success") : admin_url("admin.php?page=wpadm_wp_full_backup_dropbox&pay=success"); ?>" name="success_url">
                <input type="hidden" value="<?php echo (DRBBACKUP_MULTI) ? network_admin_url("admin.php?page=wpadm_wp_full_backup_dropbox&pay=cancel") : admin_url("admin.php?page=wpadm_wp_full_backup_dropbox&pay=cancel"); ?>" name="cancel_url">
                <input type="submit" class="backup_button" value="<?php _e('Get PRO','dropbox-backup');?>">
            </form>
            <?php } else {
            ?>
            <input type="button" class="backup_button" onclick="document.dropbox_pro_form.submit();" value="<?php _e('Get PRO','dropbox-backup');?>">
            <?php 
        }?>
    </div>
</div>

<div class="clear"></div>