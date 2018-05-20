<div class="cfTabsContainer wpadm-setting-box" style="">
    <div class="stat-wpadm-info-title" id="title-setting" style="padding :20px 0px; margin-top:11px; line-height: 50px;">
        <?php _e('Settings','dropbox-backup'); ?>
    </div>
    <div id="setting_active" class="cfContentContainer" style="display: none;">
        <form method="post" action="" autocomplete="off">
            <div class="stat-wpadm-registr-info" style="width: 100%; margin-bottom: 9px;">
                <div  style="margin-bottom: 12px; margin-top: 20px; font-size: 15px; text-align: center;">
                    <div class="code-auth-dropbox" style="display: none;">
                        <input type="text" value="" name="dropbox_code_auth" id="dropbox_code_auth" placeholder="<?php _e('Enter code', 'dropbox-backup')?>">
                        <input class="btn-orange" type="button" value="<?php _e('Get access token', 'dropbox-backup')?>" onclick=" showLoading('.code-auth-loading');oauth2=true;connectDropbox(this,'<?php echo admin_url( 'admin-post.php?action=dropboxConnect' )?>')" />
                        <div class="code-auth-loading" style="display: none;"></div>
                    </div>
                    <input class="btn-orange" type="button" style="padding: 5px 10px; font-size: 15px; font-weight: 500" onclick="connectDropbox(this,'<?php echo admin_url( 'admin-post.php?action=dropboxConnect' )?>')" value="<?php _e('Connect to Dropbox','dropbox-backup'); ?>" name="submit">
                    <div class="desc-wpadm"><span id="dropbox_uid_text"><?php echo ( isset($dropbox_options['oauth_token']) || (isset( $dropbox_options['token_type'] ) ) ) && isset($dropbox_options['uid']) ? __('Dropbox successfully connected:','dropbox-backup') . " UID " . esc_html( $dropbox_options['uid'] ) : '';  ?></span></div>
                </div>
                <?php $show_fields =  isset($dropbox_options['app_key']) && !empty($dropbox_options['app_key']) && isset($dropbox_options['app_secret']) && !empty($dropbox_options['app_secret']) && $dropbox_options['app_key'] != WPADM_APP_KEY && $dropbox_options['app_secret'] != WPADM_APP_SECRET ; ?>
                <div class="setting-checkbox">
                    <input type="checkbox" onclick="showApp();" <?php echo $show_fields ? 'checked="checked"' : ''?> id="dbconnection-to-app" /><label for="dbconnection-to-app"><?php _e('Connect using my Dropbox App','dropbox-backup');?></label>
                </div>

                <table class="form-table stat-table-registr" style="margin-top:2px; <?php echo $show_fields  ? 'margin-bottom:25px;' : ''?>">
                    <tbody>
                        <tr valign="top" id="dropbox-app-key" style="display: <?php echo $show_fields  ? 'table-row' : 'none'?>;">
                            <th scope="row">
                                <label for="app_key"><?php _e('App key','dropbox-backup'); ?>*</label>
                            </th>
                            <td>
                                <input id="app_key" class="" type="text" name="app_key" value="<?php echo isset($dropbox_options['app_key']) && $dropbox_options['app_key'] != WPADM_APP_KEY ? esc_attr( $dropbox_options['app_key'] ) : ''?>">
                            </td>
                        </tr>
                        <tr valign="top" id="dropbox-app-secret" style="display: <?php echo $show_fields  ? 'table-row' : 'none'?>;">
                            <th scope="row">
                                <label for="app_secret"><?php _e('App secret','dropbox-backup'); ?>*</label>
                            </th>
                            <td>
                                <input id="app_secret" class="" type="text" name="app_secret" value="<?php echo isset($dropbox_options['app_secret']) && $dropbox_options['app_secret'] != WPADM_APP_SECRET ? esc_attr( $dropbox_options['app_secret'] ) : ''?>">
                            </td>
                        </tr>        

                        <tr valign="top" id="help-key-pass" style="display: <?php echo $show_fields  ? 'table-row' : 'none'?>;">
                            <td colspan="2" align="center">
                                <a class="help-key-secret" href="javascript:getHelperDropbox();" ><?php _e('Where to get App key & App secret?','dropbox-backup'); ?></a><br />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="setting-checkbox">
                    <span class="dashicons dashicons-editor-help wpadm-tooltip" title="<?php _e('The average waiting time before error message will be displayed. During this time the plugin will try to perform failed operation attempts. If this time was expired and operation still fails, the backup process will be interrupted and the message about problem will be shown on the screen.', 'dropbox-backup')?>"></span>
                    <label for="time_error" style="font-size: 13px;"><?php _e('Waiting time (minutes)', 'dropbox-backup'); ?></label>
                    <select name="time_error" id="time_error" onchange="saveSetting('time_error')">
                        <?php 
                            for($i = 1; $i < 16; $i++) {
                            ?>
                            <option value="<?php echo $i?>" <?php echo isset($dropbox_options['time_error']) && $dropbox_options['time_error'] == $i ? 'selected="selected"' : (!isset($dropbox_options['time_error']) && $i == $default ) ? 'selected="selected"' : ''  ?> ><?php echo $i?>:00</option>
                            <?php 
                            }
                        ?>
                    </select>

                    <span></span>
                </div>
                <?php if ( is_super_admin() ) { ?>
                    <div class="setting-checkbox">
                        <input type="checkbox" <?php echo isset($dropbox_options['is_admin']) && (int)$dropbox_options['is_admin'] == 1 ? 'checked="checked"' : ''; ?> name="is_admin" value="1" id="is_admin" onclick="saveSetting('is_admin')" />
                        <label for="is_admin" style="font-size: 13px;"><?php _e('Appear in menu for admins only','dropbox-backup'); ?></label>
                    </div>
                    <?php } ?>
                <div class="setting-checkbox">
                    <input type="checkbox" <?php echo (isset($dropbox_options['is_optimization']) && (int)$dropbox_options['is_optimization'] == 1) || (!isset($dropbox_options['is_optimization'])) ? 'checked="checked"' : ''; ?> name="is_optimization" value="1" id="is_optimization" onclick="saveSetting('is_optimization')" />
                    <label for="is_optimization" style="font-size: 13px;"><?php _e('Database Optimization','dropbox-backup'); ?></label>
                </div>  
                <div class="setting-checkbox">
                    <input type="checkbox" <?php echo (isset($dropbox_options['is_local_backup_delete']) && (int)$dropbox_options['is_local_backup_delete'] == 1) ? 'checked="checked"' : ''; ?> name="is_local_backup_delete" value="1" id="is_local_backup_delete" onclick="saveSetting('is_local_backup_delete')" />
                    <label for="is_local_backup_delete" style="font-size: 13px; width: 90%"><?php _e('Don\'t delete a local backup copy after uploading to dropbox','dropbox-backup'); ?></label>
                </div>
                <div class="setting-checkbox">
                    <input type="checkbox" <?php echo (isset($dropbox_options['is_repair']) && (int)$dropbox_options['is_repair'] == 1) ? 'checked="checked"' : ''; ?> name="is_repair" value="1" id="is_repair" onclick="saveSetting('is_repair')" />
                    <label for="is_repair" style="font-size: 13px;"><?php _e('Try database repair','dropbox-backup'); ?></label>
                </div> 
                <div class="setting-checkbox">
                    <input type="checkbox" <?php echo (isset($dropbox_options['is_show_admin_bar']) && (int)$dropbox_options['is_show_admin_bar'] == 1) ? 'checked="checked"' : ( !isset($dropbox_options['is_show_admin_bar']) ? 'checked="checked"' : '' ); ?> name="is_show_admin_bar" value="1" id="is_show_admin_bar" onclick="saveSetting('is_show_admin_bar')" />
                    <label for="is_show_admin_bar" style="font-size: 13px;"><?php _e('Show in a admin bar','dropbox-backup'); ?></label>
                </div>

                <!-- type archive -->
                <div class="setting-checkbox" style="margin-top:40px;">
                    <label for="" style="font-size: 13px; font-weight: 600;"><?php _e('Create backup using following backup methods:','dropbox-backup'); ?></label>
                    <span style="font-size: 11px; margin-top: -7px;">(<?php _e('ZIP method will be used as \'default\'')?>)</span>
                </div>
                <div class="setting-checkbox">
                    <input autocomplete="off" type="checkbox" <?php echo ( isset($dropbox_options['type_archive']['zip_archive']) && $dropbox_options['type_archive']['zip_archive'] == 1 ) ? 'checked="checked"' : ''; ?>  value="1" id="zip_archive" onclick="saveSetting('zip_archive')" />
                    <label for="zip_archive" style="font-size: 13px;"><?php _e('Zip shell','dropbox-backup'); ?></label>
                </div>
                <div class="setting-checkbox">
                    <input autocomplete="off" type="checkbox" <?php echo ( isset($dropbox_options['type_archive']['targz_archive']) && $dropbox_options['type_archive']['targz_archive'] == 1 ) ? 'checked="checked"' : ''; ?>  value="1" id="targz_archive" onclick="saveSetting('targz_archive')" />
                    <label for="targz_archive" style="font-size: 13px;"><?php _e('TarGz archive','dropbox-backup'); ?></label>
                </div>
                <!--   <div class="setting-checkbox">
                <input autocomplete="off" type="checkbox" <?php echo ( isset($dropbox_options['type_archive']['tar_archive']) && $dropbox_options['type_archive']['tar_archive'] == 1) ? 'checked="checked"' : ''; ?> name="type_archive" value="tar_archive" id="tar_archive" data-name="tar_archive" onclick="saveSetting('tar_archive')" />
                <label for="tar_archive" style="font-size: 13px;"><?php _e('Tar archive','dropbox-backup'); ?></label>
                </div> -->
                <div class="clear" style="margin-top: 15px;"></div>
                <!-- end type archive -->
                <div class="setting-checkbox">
                    <?php _e('Include/Exclude','dropbox-backup'); ?>
                    <a onclick="InludesSetting();"  href="javascript:void(0);" style="color: #fff"><?php _e('Folders and files','dropbox-backup'); ?></a>
                </div>
                <div style="border-bottom:1px solid #fff; margin:10px 0px;"></div>
                <div class="setting-checkbox">
                    <label for="backup_folder" style="font-size: 13px;"><?php _e('Backup folder location','dropbox-backup'); ?>:</label>  
                    <input type="text" style="width: 100%;" name="backup_folder" onkeypress="setDefaultFolderBackup(this)" value="<?php echo ( isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder']) ) ? esc_attr( $dropbox_options['backup_folder'] ) : esc_attr( DROPBOX_BACKUP_DIR_BACKUP ); ?>" id="backup_folder" onclick="" />
                    <span>
                        <input type="hidden" value="1" id="clear_backup_folder">
                        <a href="javascript:void(0);" onclick="setDefaultFolderBackup('<?php echo urlencode(DROPBOX_BACKUP_DIR_BACKUP);?>');" style="color:#fff;"><?php _e('Set to default backup folder','dropbox-backup');?></a>
                    </span>
                    <div class="clear"></div>
                </div>
                <div class="setting-checkbox" style="text-align: center;margin-top:15px;">
                    <input class="btn-orange" type="button" id="button-save-folder-backup" value="<?php _e('Save', 'dropbox-backup'); ?>" >
                </div>
                <script >
                    jQuery(document).ready(function() {
                        jQuery('#button-save-folder-backup').click(function() {
                            saveSetting('backup_folder');
                        })
                    })
                </script>
            </div>
        </form>
    </div>
    <div class="clear"></div> 
    <div class="block-button-show" style="color: #fff;">
        <div class="block-click" onclick="showSetting(true);">
            <span id="setting-show" style="color: #fff;"><?php _e('Show','dropbox-backup'); ?></span>
            <div id="setting-choice-icon" class="dashicons dashicons-arrow-down" style=""></div>
        </div>
    </div>
</div>