<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <script src="<?php echo plugin_dir_url(__FILE__) . 'js/jquery.arcticmodal-0.3.min.js'?>" type="text/javascript"></script>
    <link rel='stylesheet'  href='<?php echo plugin_dir_url(__FILE__) . 'js/jquery.arcticmodal-0.3.css'?>' type='text/css' media='all' />
    <style>
        .pointer {
            cursor: pointer;
        }
    </style>
    <script>
        var image_loading = '<?php echo plugins_url('/img/wpadmload.gif', dirname(__FILE__) ); ?>'

        function showProcessesData(data)
        {
            if (type_backup == 'send-to-dropbox') {
                jQuery('#dropbox-process').css('display', 'block');
            }
            for(i in data) {
                if (data[i]['all']) {
                    if (data[i]['all'] > 0) {
                        jQuery('#' + i + '-process').css('display', 'block');
                        jQuery('.stats-' + i ).css('display', 'inline');
                    }
                    jQuery('#all_' + i).html(data[i]['all']);
                }
                if (data[i]['count']) { 
                    jQuery('#count_' + i).html(data[i]['count']);
                }
                if (data[i]['procent']) { 
                    jQuery('#procent_' + i).html(data[i]['procent'] + '%');
                }
                jQuery('.procent-' + i).css('width', data[i]['procent'] + '%');
            }

        }

        function disable_buttons()
        {
            jQuery('.disable-button').each(function() {
                jQuery(this).prop('disabled', true);
                jQuery(this).html('<?php _e('Backup in progress...', 'dropbox-backup')?>' );
            });
        }

        function enable_buttons()
        {
            jQuery('.disable-button').each(function() {
                jQuery(this).prop('disabled', false);
                jQuery(this).html(jQuery(this).attr('text-button'));
            });
        }
        jQuery(document).ready(function() {
            enable_buttons();
            jQuery('.disable-button').click(function() {
                disable_buttons();
            })
        })
        var home_url = '<?php echo SITE_HOME; ?>';
        var type_backup = '';
        function blickForm(id, t)
        {
            if(t.checked == true) {
                t.checked = false;
            }
            l = jQuery('#' + id).length;
            showRegistInfo(false);
            if (l > 0) {
                blick(id);
            } 
        }
        function showRegistInfo(show)
        {
            display = jQuery('#cf_activate').css('display');
            if (display == 'none') {
                jQuery('#cf_activate').show('slow');
                jQuery('#registr-show').html("<?php _e('Hide','dropbox-backup'); ?>");
                jQuery('#title-regisr').css("padding" , "0px 0px");
                jQuery('#registr-choice-icon').removeClass("dashicons-arrow-down").addClass('dashicons-arrow-up');
                jQuery(".hover-title").hide(200);
            } else {
                if (show) {
                    jQuery('#cf_activate').hide('slow');
                    jQuery('#registr-show').html("<?php _e('Show','dropbox-backup'); ?>");
                    jQuery('#title-regisr').css("padding" , "9px 0px");
                    jQuery('#registr-choice-icon').removeClass("dashicons-arrow-up").addClass('dashicons-arrow-down');
                    jQuery(".hover-title").show(200);
                }
            }
        }
        function changesSetting()
        {
            return confirm('<?php _e('The configuration was changed. Would you like to save it?', 'dropbox-backup')?>');
        }
        var changes_setting = false;
        function showSetting(show)
        {
            display = jQuery('#setting_active').css('display');
            if (display == 'none') {
                jQuery('#setting_active').show(1000);
                jQuery('#setting-show').html("<?php _e('Hide','dropbox-backup'); ?>");
                jQuery('#title-setting').css("padding" , "0px 0px");
                jQuery('#setting-choice-icon').removeClass("dashicons-arrow-down").addClass('dashicons-arrow-up');
            } else {
                if (show) {
                    if (changes_setting) {
                        if ( changesSetting() ) {
                            jQuery('#button-save-folder-backup').click();
                            return;
                        } 
                        location.reload();
                        return;
                    }
                    jQuery('#setting_active').hide('slow');
                    jQuery('#setting-show').html("<?php _e('Show','dropbox-backup'); ?>");
                    jQuery('#title-setting').css("padding" , "20px 0px");
                    jQuery('#setting-choice-icon').removeClass("dashicons-arrow-up").addClass('dashicons-arrow-down');

                }
            }
        }
        var process_flag = 0;
        function start_local_backup()
        {
            d = new Date();
            var data_backup = {
                'action': 'wpadm_local_backup',
                'nonce' : '<?php echo esc_attr( $nonce ); ?>',
                'time': Math.ceil(  (d.getTime() + (-d.getTimezoneOffset() * 60000 ) ) / 1000 )
            };  
            jQuery("#logs-form").show("slow");
            jQuery("#action-buttons").css('margin-top', '8px');
            <?php if(!$stars5) { ?>
                jQuery("#support-button").css('margin-top', '9px');
                <?php } else { ?>
                jQuery("#support-button").css('margin-top', '8px');
                <?php  } ?>
            jQuery("#log-backup").html('');
            jQuery(".title-logs").css('display', 'block');
            jQuery(".title-status").css('display', 'none');
            type_backup = 'local_backup';
            jQuery('#process-box').show('slow');
            jQuery('#reviews-dropbox').hide('slow');
            jQuery('#stop-button').css('display', 'block');
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: data_backup,
                success: function(data){
                    if (data.result != 'work') {
                        process_flag = 0;
                        showData(data);
                    } else {
                        process_flag = 1
                        processBar(type_backup);
                        showTime();
                    }

                },
                error: function(jqXHR, textStatus, errorThrown){
                    processStop();
                    AjaxAlert(jqXHR, textStatus, errorThrown);
                },
                dataType: 'json'
            });
        }
        function AjaxAlert(jqXHR, textStatus, errorThrown)
        {
            if (stop_backup_flag === false) {
                <?php $command_running = get_transient('running_command');
                    if (isset($is_runnig) && $is_runnig && $command_running) {  
                        echo 'var running_command = true;';
                    } else {
                        echo 'var running_command = false;';
                    }
                ?>
                if (running_command === false ) {
                    var msg = 'Website "' + home_url + '" returned an error during operation with return:<br /><br /> <span style="font-size:13px; font-style: italic;">code: ' + jqXHR.status + ', text status: ' + textStatus + ', text: ' + errorThrown + "</span>";
                    jQuery("#ajax-message").html(msg);
                    jQuery("#msg_ajax").val(msg);
                    jQuery('#ajax-alert').arcticmodal({
                        beforeOpen: function(data, el) {
                            jQuery('#ajax-alert').css('display','block');
                        },
                        afterClose: function(data, el) {
                            jQuery('#ajax-alert').css('display','none');
                        }
                    });
                    enable_buttons();
                }
            }
        }

        var stop_backup_flag = false;

        function stopBackup() 
        {
            // type_backup != '' &&
            if ( confirm("<?php _e('Interrupted progress in backup? All data is lost.', 'dropbox-backup')?>")) { 
                stop_backup_flag = true;
                document.stop_backups.type_backup.value = type_backup;
                document.stop_backups.submit();
            }

        }

        var auth_param = <?php echo isset($dropbox_options['app_key']) && isset($dropbox_options['app_secret']) && isset($dropbox_options['uid']) && $dropbox_options['uid'] != '' ? 'false' : 'true'; ?>;
        function start_dropbox_backup(t)
        {
            if (auth_param === false) {
                d = new Date();
                process_flag = 0;

                var data_backup = {
                    'action': 'wpadm_dropbox_create',
                    'nonce' : '<?php echo esc_attr( $nonce ); ?>',
                    'time': Math.ceil(  (d.getTime() + (-d.getTimezoneOffset() * 60000 ) ) / 1000 ),
                };  
                jQuery("#logs-form").show("slow");
                jQuery("#action-buttons").css('margin-top', '8px');

                <?php if(!$stars5) { ?>
                    jQuery("#support-button").css('margin-top', '9px');
                    <?php } else { ?>
                    jQuery("#support-button").css('margin-top', '8px');
                    <?php  } ?>
                jQuery("#log-backup").html('');
                jQuery(".title-logs").css('display', 'block');
                jQuery(".title-status").css('display', 'none');
                jQuery('#process-box').show('slow');
                jQuery('#reviews-dropbox').hide('slow');
                jQuery('#stop-button').css('display', 'block');
                jQuery('#dropbox-process').css('display', 'block');
                type_backup = 'send-to-dropbox';
                jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: data_backup,
                    success: function(data){
                        if (data.result !== 'work') {
                            process_flag = 0;
                            if (data.result == 'success') {
                                jQuery('.title-logs').css('display', 'none');
                                jQuery('.title-status').css({'display':'block', 'color':'green'});
                                jQuery('.title-status').html('<?php _e('Dropbox Backup was created successfully','dropbox-backup'); ?>');
                            } else {
                                var error_log = '';
                                if (data.error && data.error != 'undefined') {
                                    error_log = ': ' + data.error;
                                }
                                jQuery('.title-logs').css('display', 'none');
                                jQuery('.title-status').css({'display':'block', 'color':'red'});
                                jQuery('.title-status').html("<?php _e('Dropbox Backup wasn\'t created. ','dropbox-backup'); ?>" + error_log);
                            }
                            showData(data);
                            jQuery('.table').css('display', 'table');
                        } else {
                            process_flag = 1
                            processBar('send-to-dropbox');
                            showTime();
                        }

                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        processStop();
                        AjaxAlert(jqXHR, textStatus, errorThrown);
                    },
                    dataType: 'json'
                });
            } else {
                jQuery('#is-dropbox-auth').arcticmodal({
                    beforeOpen: function(data, el) {
                        jQuery('#is-dropbox-auth').css('display','block');

                    },
                    afterClose: function(data, el) {
                        jQuery('#is-dropbox-auth').css('display','none');
                        showSetting(false);
                        blick('app_key', 4);
                        blick('app_secret', 4);
                    }
                });
            }
        }
        function showData(data)
        {
            <?php $command_running = get_transient('running_command');
                if (isset($is_runnig) && $is_runnig && $command_running ) { 
                    echo 'var command_running = true;';
                } else {
                    echo 'var command_running = false;';
                }
            ?>

            jQuery('.table').css('display', 'table');
            if (type_backup == 'local_backup') {
                if (data.result == 'success') {
                    jQuery('.title-logs').css('display', 'none');
                    jQuery('.title-status').css({'display':'block', 'color':'green'});
                    jQuery('.title-status').html('<?php _e('Local Backup was created successfully','dropbox-backup'); ?>');
                } else {
                    var error_log = '';
                    if (data.error && data.error != 'undefined') {
                        error_log = ': ' + data.error;
                    }
                    jQuery('.title-logs').css('display', 'none');
                    jQuery('.title-status').css({'display':'block', 'color':'red'});
                    jQuery('.title-status').html("<?php _e('Local Backup wasn\'t created', 'dropbox-backup'); ?>" + error_log) ;
                    return false;
                }
            } else {
                if (data.result == 'success') {
                    jQuery('.title-logs').css('display', 'none');
                    jQuery('.title-status').css({'display':'block', 'color':'green'});
                    jQuery('.title-status').html('<?php _e('Dropbox Backup was created successfully','dropbox-backup'); ?>');
                    <?php echo 'var is_reload= ' . ($dropbox_options['is_local_backup_delete'] && $dropbox_options['is_local_backup_delete'] == 1 ? 'true' : 'false') . ';'?>
                    if (is_reload) {
                        location.reload();
                    }
                } else {
                    var error_log = '';
                    if (data.error && data.error != 'undefined') {
                        error_log = data.error;
                    }
                    jQuery('.title-logs').css('display', 'none');
                    jQuery('.title-status').css({'display':'block', 'color':'red'});
                    jQuery('.title-status').html("<?php _e('Dropbox Backup wasn\'t created. ','dropbox-backup'); ?>" + error_log);
                    return false;
                }
            }
            if (command_running === false) { 
                enable_buttons()
                size_backup = data.size / 1024 / 1024;
                if (data.size != 0 || data.result != 'error') {
                    var img_table = 
                    '<img src="<?php echo plugin_dir_url(__FILE__) . "ok.png" ;?>" title="Successful" alt="Successful" style="float: left; width: 20px; height: 20px;margin-left: 21px;" />' +
                    '<div style="margin-top :1px;float: left;"><?php _e('Successful','dropbox-backup');?></div>';
                    name_backup = data.name;
                } else {
                    var img_table =
                    '<img src="<?php echo plugin_dir_url(__FILE__) . "not-ok.png" ;?>" title="fail" alt="fail" style="float: left; width: 20px; height: 20px;margin-left: 21px;" />' +
                    '<div style="margin-top :1px;float: left;"><?php _e('Fail','dropbox-backup');?>&nbsp;&nbsp;(<a style="text-decoration:underline;"><?php _e('Show Details','dropbox-backup');?></a>)</div>';
                    name_backup = '<?php _e('Not available','dropbox-backup');?>';
                }
                info = "";
                if (data.data) {
                    for(i = 0; i < data.data.length; i++) {
                        e = data.data[i].split('/');
                        info += '<tr style="border: 0;">' +
                        '<td style="border: 0;padding: 0px;"><a href="<?php echo content_url(DROPBOX_BACKUP_DIR_NAME) . '/'; ?>' + data.name + '/' + e[e.length - 1] + '">' + e[e.length - 1] + '</td>' +
                        '</tr>' ;
                    }

                    co = jQuery('.number-backup').length + 1;
                    jQuery('.table > tbody:last').after(
                    '<tr>'+
                    '<td class="number-backup" onclick="shows(\'' + data.md5_data + '\', this)">' +
                    co + 
                    '</td>' +
                    '<td class="pointer" onclick="shows(\'' + data.md5_data + '\', this)" style="text-align: left; padding-left: 7px;" >' +
                    data.time + 
                    '</td>' +
                    '<td class="pointer" onclick="shows(\'' + data.md5_data + '\', this)">' +
                    name_backup +
                    '</td>' +
                    '<td class="pointer" onclick="shows(\'' + data.md5_data + '\',this)">' +
                    data.counts +
                    '</td>' +
                    '<td class="pointer" onclick="shows(\'' + data.md5_data + '\', this)">' +
                    img_table +
                    '</td>' +
                    '<td class="pointer" onclick="shows(\'' + data.md5_data + '\', this)">' +
                    data.type + ' <?php _e('backup','dropbox-backup')?>' +
                    '</td>' +
                    '<td class="pointer" onclick="shows(\'' + data.md5_data + '\', this)">' +
                    size_backup.toFixed(2) + "<?php _e('Mb','dropbox-backup')?>" +
                    '</td>' +
                    '<td>' + 
                    '<a href="javascript:void(0)" class="button-wpadm" title="<?php _e('Restore','dropbox-backup')?>" onclick="show_recovery_form(\'' + data.type + '\', \'' + data.name + '\')"><span class="pointer dashicons dashicons-backup"></span><?php _e('Restore','dropbox-backup')?></a> &nbsp;' +
                    '<a href="javascript:void(0)" class="button-wpadm" title="<?php _e('Delete','dropbox-backup')?>" onclick="delete_backup(\'' + data.name + '\', \'' + data.type + '\')"><span class="pointer dashicons dashicons-trash"></span><?php _e('Delete','dropbox-backup')?></a> &nbsp;' +
                    '</td>' +
                    '</tr>'+
                    '<tr id="' + data.md5_data + '" style="display: none;">'+
                    '<td colspan="2">' +
                    '</td>' +
                    '<td align="center" style="padding: 0px; width: 350px;">' +
                    '<div style="overflow: auto; max-height: 150px;">' +
                    '<table border="0" align="center" style="width: 100%;" class="info-path">' +
                    info +
                    '</table>' +
                    '</div>' +
                    '</td>' +
                    '<td colspan="6"></td>' +
                    '</tr>')
                }
            } else {
                if (data.result == 'success') {
                    location.reload();
                }
            }
        }

        function initial_restore(method)
        {
            url_ajax = '<?php echo plugins_url('/modules/restore-class.php', dirname(__FILE__) ); ?>';
            data_send = {};
            data_send['key'] = '<?php echo esc_attr( $key ); ?>';
            data_send['method'] = 'restore';
            jQuery.ajax({
                type: "POST",
                url: url_ajax,
                data: data_send,
                error: function(jqXHR, textStatus, errorThrown){
                    //setTimeout('processBar("' + method + '")', 10000);
                    if (jqXHR.status != 0) {
                        //processStop();              
                        //AjaxAlert(jqXHR, textStatus, errorThrown);
                    }
                },
            });
        }
        var logs = [];
        function processBar(method)
        {      
            if (method == 'undefined') {
                method = type_backup;
            }
            var data_log = {
                'action': 'wpadm_logs',
                'nonce' : '<?php echo esc_attr( $nonce ); ?>',
                'type-backup' : method,
            }; 
            if (method == 'send-to-dropbox') {
                data_log['type-backup2'] = 'local_backup';
            }

            var url_ajax = ajaxurl;
            if (method == 'restore') {
                url_ajax = '<?php echo plugins_url('/modules/restore-class.php', dirname(__FILE__) ); ?>';
                data_log['key'] = '<?php echo esc_attr( $key ); ?>';
                data_log['method'] = 'wpadm_logs';
            }
            jQuery.ajax({
                type: "POST",
                url: url_ajax,
                data: data_log,
                success: function(response){
                    if (response != '') {
                        eval("var data=" + response);
                        for(s in data.log) {
                            if (jQuery.inArray(s , logs) == -1) {
                                l = jQuery("#log-backup").html();
                                l = "<div>" + data.log[s] + "</div>" + l;
                                jQuery("#log-backup").html(l);
                            }
                        }

                        if (data.processes) {
                            console.log(data.processes);
                            showProcessesData(data.processes);
                        }



                        if (process_flag == 1) {
                            if (data.data) {
                                if (method == 'restore') {
                                    if (type_restore == 'local') {
                                        showRestoreInfo(data.data.result, 'local', data.data.name)
                                    } else if (type_restore == 'dropbox') {
                                        showRestoreInfo(data.data.result, 'dropbox', data.data.name)
                                    }
                                } else {
                                    showData(data.data);
                                }
                                processStop();
                                jQuery('#stop-button').css('display', 'none');  
                            } else { 
                                setTimeout('processBar("' + method + '")', 10000);
                            }
                        }

                        if (data.mod_secur) {  // initial restore ajax
                            initial_restore(method);
                        }
                    } else {
                        setTimeout('processBar("' + method + '")', 10000);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown){
                    setTimeout('processBar("' + method + '")', 10000);
                    if (jqXHR.status != 0) {
                        //processStop();              
                        //AjaxAlert(jqXHR, textStatus, errorThrown);
                    }
                },
            });
        }

        function showTime(t)
        {

            if (process_flag == 1) {
                if ( (typeof t) == 'undefined') {
                    t = 1;
                } else {
                    t = t + 1;
                }
                time = t + " <?php _e('sec.','dropbox-backup'); ?>";
                jQuery("#time_backup").html(time);
                setTimeout(function() { showTime(t) }, 1000); 
            }
        }
        function processStop()
        {
            process_flag = 0;
        }
        function delete_backup(backup, type)
        {
            document.delete_backups.backup_name.value = backup;
            document.delete_backups.backup_type.value = type;
            document.delete_backups.submit();
        }
        function create_backup (type) {
            if (type == 'auth') {
                document.form_auth_backup_create.submit();
            }
        }

        function showRestoreInfo(result, type, name)
        {
            if (result == 'success') {
                jQuery('.title-logs').css('display', 'none');
                jQuery('.title-status').css({'display':'block', 'color':'green'});
                if (type == 'local') {
                    str = '<?php _e('Local Backup (%s) was restored successfully','dropbox-backup'); ?>';
                    str = str.replace('%s', name);
                    jQuery('.title-status').html(str);
                } else {
                    str = '<?php _e('Dropbox Backup (%s) was restored successfully','dropbox-backup'); ?>';
                    str = str.replace('%s', name);
                    jQuery('.title-status').html(str);
                }
            } else {
                jQuery('.title-logs').css('display', 'none');
                jQuery('.title-status').css({'display':'block', 'color':'red'});
                if (type == 'local') {
                    str = "<?php _e('Local Backup (%s) wasn\'t restored','dropbox-backup'); ?>";
                    str = str.replace("%s", name) ;
                    jQuery('.title-status').html(str);
                } else {
                    str = "<?php _e('Dropbox Backup (%s) wasn\'t restored','dropbox-backup'); ?>";
                    str = str.replace('%s', name);
                    jQuery('.title-status').html(str);
                }
            }
        }

        var type_restore = '';
        function show_recovery_form(type, name)
        {
            var act = '';
            if (confirm("<?php _e('Are you sure you want to start the recovery process?','dropbox-backup')?>")) {
                if (type == 'local') {
                    act = 'wpadm_local_restore';
                    type_restore = 'local';
                } else {
                    act = 'wpadm_restore_dropbox';
                    type_restore = 'dropbox';
                }
                var data_backup = {
                    'action': act,
                    'nonce' : '<?php echo esc_attr( $nonce ); ?>',
                    'key' : '<?php echo esc_attr( $key ); ?>',
                    'name': name,
                };  
                jQuery("#log-backup").html('');
                jQuery("#action-buttons").css('margin-top', '8px');
                <?php if(!$stars5) { ?>
                    jQuery("#support-button").css('margin-top', '9px');
                    <?php } else { ?>
                    jQuery("#support-button").css('margin-top', '8px');
                    <?php  } ?>
                jQuery(".title-logs").css('display', 'block');
                jQuery(".title-status").css('display', 'none');
                jQuery("#logs-form").show("slow");
                jQuery("#action-buttons").css('margin-top', '8px');
                process_flag = 1
                jQuery.ajax({
                    type: "POST",
                    // url: ajaxurl,
                    url: '<?php echo plugins_url('/modules/restore-class.php', dirname(__FILE__) ); ?>',
                    data: data_backup,
                    success: function(data){
                        if (data.result == 'success') {
                            process_flag = 0;
                            jQuery('.title-logs').css('display', 'none');
                            jQuery('.title-status').css({'display':'block', 'color':'green'});
                            if (type == 'local') {
                                str = '<?php _e('Local Backup (%s) was restored successfully','dropbox-backup'); ?>';
                                str = str.replace('%s', name);
                                jQuery('.title-status').html(str);
                            } else {
                                str = '<?php _e('Dropbox Backup (%s) was restored successfully','dropbox-backup'); ?>';
                                str = str.replace('%s', name);
                                jQuery('.title-status').html(str);
                            }
                        } else if (data.result == 'work'){
                            processBar('restore');
                            showTime();
                        } else {
                            process_flag = 0;
                            jQuery('.title-logs').css('display', 'none');
                            jQuery('.title-status').css({'display':'block', 'color':'red'});
                            if (type == 'local') {
                                str = "<?php _e('Local Backup (%s) wasn\'t restored','dropbox-backup'); ?>";
                                str = str.replace("%s", name) ;
                                jQuery('.title-status').html(str);
                            } else {
                                str = "<?php _e('Dropbox Backup (%s) wasn\'t restored','dropbox-backup'); ?>";
                                str = str.replace('%s', name);
                                jQuery('.title-status').html(str);
                            }
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        processStop();
                        AjaxAlert(jqXHR, textStatus, errorThrown);
                    },
                    dataType: 'json'
                });
            }

        }
        function auth_form(t)
        {
            var button = jQuery(t);
            var form = button.closest('form');
            var data = {};

            var reg = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,6})+$/;
            mail = document.auth.username.value;
            send = false;
            if (!reg.test(mail)) {
                document.auth.username.style.border = "2px solid red";
            } else {
                document.auth.username.style.border = "1px solid #5b9dd9";
                if(document.auth.password.value.length == 0) {
                    document.auth.password.style.border = "2px solid red";
                } else {
                    send = true;
                    document.auth.password.style.border = "1px solid #5b9dd9";
                }
            }
            if(send) {
                data['password'] = document.auth.password.value; 
                data['username'] = document.auth.username.value;
                data['plugin'] = document.auth.plugin.value;
                backup = jQuery("#name_backup_restore").val();  
                jQuery.ajax({
                    url: form.attr('action'),
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    success: function(data_res) {
                        if( !data_res){
                            alert('error');
                        } else if(data_res.error) {
                            if(form.find('#message-form').length) {
                                form.find('#message-form').html("");
                                form.find('#message-form').css('display', 'block');
                                form.find('#message-form').css('margin', '0');
                                form.find('#message-form').css('margin-top', '6px');
                                form.find('#message-form').html(data_res.error);
                            }
                        } else if(data_res.url) {

                            jQuery.ajax({
                                url: ajaxurl,
                                data: {'action' : 'set_user_mail', 'email' : document.auth.username.value},
                                type: 'POST',
                                dataType: 'json',
                                success: function(res) {

                                } 
                            });    
                            form.attr('action', data_res.url);
                            document.auth.submit();  
                            //location.reload();  
                        }
                    }, 
                    error: function ( jqXHR, textStatus, errorThrown ) {
                        AjaxAlert(jqXHR, textStatus, errorThrown);
                    }

                });
            }
        } 
        function disconnectDropbox()
        {
            var form = jQuery('form#dropbox_form');
            form.find('#oauth_token_secret').val('');
            form.find('#oauth_token').val('');
            form.find('#uid').val('');
            form.find('#dropbox_uid_text').text('');
            form.find('.disconnect_btn').parents('.form_block_input').removeClass('connected');
        }

        var winParams = "left=0,top=0,height=600,width=1000,menubar=no,location=no,resizable=yes,scrollbars=yes,status=yes,toolbar=no,directories=no"
        var dropboxBut, dropboxWin;
        var oauth2 = false;
        function connectDropbox(button, href, oauth_token_secret, oauth_token, uid){

            if (oauth2) {
                var data = {};
                data['dropbox_code_auth'] = jQuery('#dropbox_code_auth').val();
                jQuery('#dropbox_code_auth').val('');
                jQuery.ajax({
                    url: href,
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    success: function(data_res) {
                        if (data_res.uid && data_res.uid != '')  {
                            jQuery('.code-auth-dropbox').hide(600);
                            jQuery('#dropbox_uid_text').html('<?php _e('Dropbox successfully connected:','dropbox-backup')?> UID ' + data_res.uid);
                            auth_param = false; 
                            if( dropboxWin ){
                                dropboxWin.close();
                            }
                        }
                        showLoading('.code-auth-loading');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('error' + textStatus);
                    }
                });
                return true;
            }

            if( button && href ){
                dropboxBut = jQuery(button);
                var form = dropboxBut.parents('form');
                var url = href;
                jQuery('.code-auth-dropbox').show(600);
                // if (jQuery.trim(jQuery('#app_key').val()) != '' || jQuery.trim(jQuery('#app_secret').val()) != '') {
                url += '&app_key='+jQuery('#app_key').val();
                url += '&app_secret='+jQuery('#app_secret').val();
                // }

                dropboxWin = window.open(url, "Dropbox", winParams);
                if( dropboxWin ){
                    dropboxWin.focus();
                }else{
                    alert('<?php _e('Please, permit the pop-up windows.','dropbox-backup'); ?>');
                }
            }else{
                var form = dropboxBut.parents('form');
                if( dropboxWin ){
                    dropboxWin.close();
                }
                form.find('#oauth_token_secret').val(oauth_token_secret);
                form.find('#oauth_token').val(oauth_token);
                form.find('#uid').val(uid);
                auth_param = false;
                form.find('#dropbox_uid_text').html('<?php _e('Dropbox successfully connected:','dropbox-backup')?> UID ' + uid);
                blick_form = false;
                dropboxBut.parents('.form_block_input').addClass('connected');
            }
        }
        function getHelperDropbox()
        {
            jQuery('#helper-keys').arcticmodal({
                beforeOpen: function(data, el) {
                    jQuery('#helper-keys').css('display','block');
                },
                afterClose: function(data, el) {
                    jQuery('#helper-keys').css('display','none');
                }
            });
        }

        function setReadOnly(id)
        {
            r = jQuery('#' + id).attr('readonly');
            if (r == 'readonly') {
                jQuery('#' + id).prop('readonly', false);
            } else {
                jQuery('#' + id).prop('readonly', true);

            }
        }
        function InludesSetting()
        {  
            if (jQuery('#inludes-setting').css('display') == 'block') {
                jQuery('#inludes-setting').arcticmodal('close');
            } else {
                jQuery('#inludes-setting').arcticmodal({
                    beforeOpen: function(data, el) {
                        showLoadingImg(true);
                        getIncludesData();
                        jQuery('#inludes-setting').css('display','block');
                    },
                    afterClose: function(data, el) {
                        jQuery('.show-includes').html("");
                        jQuery('#inludes-setting').css('display','none');
                    }
                });
            }
            /*disp = jQuery('#inludes-setting').css('display');
            if (disp == 'none') {
            showLoadingImg(true);
            getIncludesData();
            jQuery('#inludes-setting').show("slow");
            } else {
            jQuery('.show-includes').html("");
            jQuery('#inludes-setting').hide("slow");
            }   */
        }
        var level_tree = {};
        function getIncludesData(type, dir_)
        {
            data = {'action' : 'getDirsIncludes'};
            if (type != 'undefined') {
                data['files'] = type
            }
            if ( ( typeof dir_ ) != 'undefined') {
                data['path'] = dir_.path;
            }
            jQuery.ajax({
                url: ajaxurl,
                data: data,
                type: 'POST',
                dataType: 'json',
                success: function(data_res) {
                    showLoadingImg(false);
                    if ((typeof dir_) != 'undefined') {
                        jQuery('#img_load_' + dir_.cache).css('display', 'none');
                    }
                    if (data.path) {
                        showIncludesData(data_res.dir, dir_.id);
                    } else { 
                        if (data_res.dir) {
                            showIncludesData(data_res.dir);
                        }
                    }
                    level_tree[level_tree.length] = data_res.dir;
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    //AjaxAlert(jqXHR, textStatus, errorThrown);
                }
            });
        }
        function loadInludes(path, cache, t, lvl)
        {
            if (t.checked) {
                showLoadingImg(true);
                jQuery('#include_' + cache).html('');
                jQuery('#img_load_' + cache).css({'display':'inline'});
                getIncludesData('undefined', {'path' : path, 'id' : 'include_' + cache, 'cache' : cache });
                jQuery('#include_' + cache).show('slow');
            } else {
                in_id = jQuery(t).attr('id')
                if (jQuery('#include_' + in_id).length > 0) {
                    jQuery('#include_' + in_id).hide('slow');
                }
            }
        }
        function showIncludesData(data, id)
        {
            html = "";
            if ( ( typeof data ) != 'undefined' ) {
                if (data.length > 0) {
                    for(i = 0; i < data.length; i++) {
                        if (data[i].check) {
                            check = 'checked="checked"' ;
                            send_checked[send_checked.length] = urldecode( convert_from_cp1251( data[i].folder ) ) ;
                        } else {
                            check = '';
                        }
                        label_class = '';
                        var onclick_ = 'onclick="loadInludes(\'/' + data[i].folder + '\', \'' + data[i].cache +'\', this, \'' + level_tree.length + '\')"';
                        if (data[i].is_file) {
                            label_class = 'class="is-file"';
                            onclick_ = '';
                        }
                        html += '<div class="field-value-folder-or-file" id="inc_' + data[i].cache + '" data-value="' + data[i].cache + '">' +
                        '<input type="checkbox" ' + check + ' class="checkbox-send" value="' + urldecode( convert_from_cp1251( data[i].folder ) ) + '" name="folder-include" id="send-to-' + data[i].cache + '" data-value-cache="' + data[i].cache + '" onclick="connectFolder(this)" />' +
                        '<input type="checkbox" class="input-folder" value="/' + urldecode( convert_from_cp1251( data[i].dir ) ) + '" id="' + data[i].cache + '" ' + onclick_ + ' />' +
                        '<label ' + label_class + ' for="' + data[i].cache  + '">' + urldecode( convert_from_cp1251( data[i].dir ) ) + ' <span >(' + data[i].perm + ')</span>' + '</label>' +
                        '<div id="img_load_' + data[i].cache + '" style="display:none; margin-left:10px;position:relative;">' +
                        '<img style="position:absolute;bottom:0;" src="<?php echo plugins_url('/img/folder-loader.gif', dirname(__FILE__) ); ?>" alt="load" title="load" >' +
                        '</div>'+
                        '<div class="tree-includes" id="include_' + data[i].cache + '">' +
                        '</div>' +
                        '</div>';
                    }
                    if (jQuery("#" + id).length > 0) {
                        jQuery("#" + id).html(html);
                    } else {
                        jQuery('.show-includes').html(html);
                    }
                }
            }
        }

        function saveIncludes()
        {
            data = {'action' : 'saveDirsIncludes', 'save' : 1, 'data' : send_checked}
            if (send_checked.length > 0) {
                showLoadingImg(true);
                jQuery.ajax({
                    url: ajaxurl,
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    success: function(data_res) {
                        showLoadingImg(false);
                        if (data_res == 1) {
                            InludesSetting();
                        }
                    }, 
                    error: function (jqXHR, textStatus, errorThrown) {
                        //AjaxAlert(jqXHR, textStatus, errorThrown);
                    }
                });
            }
        }
        function saveSetting(id)
        {
            if (jQuery('#' + id).length > 0) {
                if ( jQuery('#' + id).is('select') || jQuery('#' + id).is( ":text" ) || jQuery('#' + id).is( ":hidden" ) ) {
                    is_value = jQuery('#' + id).val();
                } else {
                    is_value = 0;
                    if(document.getElementById(id).checked) {
                        is_value = 1;
                    } 
                }

                data = {'action' : 'saveSetting'}
                data[id] = is_value; 
                jQuery.ajax({
                    url: ajaxurl,
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    success: function(data_res) {
                        location.reload();
                    }, 
                    error: function( jqXHR, textStatus, errorThrown ){ 
                        //AjaxAlert(jqXHR, textStatus, errorThrown);
                    }
                });
            }
        }
        var app_key = app_secret = ''; 
        function showApp()
        {
            disp = jQuery('#dropbox-app-key').css('display');
            if (disp == 'none') {
                jQuery('#dropbox-app-key').show('slow');
                jQuery('#help-key-pass').show('slow');
                jQuery('#dropbox-app-secret').show('slow');
                jQuery('#app_secret').val(app_secret);
                jQuery('#app_key').val(app_key);
                jQuery('.stat-table-registr').css('margin-bottom', '25px');
            } else {
                jQuery('#dropbox-app-key').hide('slow');
                jQuery('#help-key-pass').hide('slow');
                jQuery('#dropbox-app-secret').hide('slow');
                app_key = jQuery('#app_key').val();
                app_secret = jQuery('#app_secret').val()
                jQuery('#app_secret').val('');
                jQuery('#app_key').val('');
                jQuery('.stat-table-registr').css('margin-bottom', '0px');
            }
        }
        function showFormAjax()
        {
            //form-ajax-ftp-email
            disp = jQuery('#form-ajax-ftp-email').css('display');
            if (disp == 'none') {
                jQuery('#form-ajax-ftp-email').show('slow');
            } else {
                jQuery('#form-ajax-ftp-email').hide('slow');
            }
        }

        function showEnableCron()
        {
            jQuery('#wp-cron-enable').arcticmodal({
                beforeOpen: function(data, el) {
                    jQuery('#wp-cron-enable').css('display','block');
                },
                afterClose: function(data, el) {
                    jQuery('#wp-cron-enable').css('display','none');
                }
            });
        }
        <?php 
            $command_running = get_transient('running_command');
            if (isset($is_runnig) && $is_runnig && $command_running  ) {  
                if ( file_exists(WPAdm_Core::getTmpDir() . "/logs2" ) ) {
                    @unlink(WPAdm_Core::getTmpDir() . "/logs2");
                }  
            ?>
            jQuery(document).ready(function() {
                jQuery("#logs-form").show("slow");
                jQuery("#action-buttons").css('margin-top', '8px');

                <?php if(!$stars5) { ?>
                    jQuery("#support-button").css('margin-top', '9px');
                    <?php } else { ?>
                    jQuery("#support-button").css('margin-top', '8px');
                    <?php  } ?>
                jQuery("#log-backup").html('');
                jQuery(".title-logs").css('display', 'block');
                jQuery(".title-status").css('display', 'none');
                type_backup = '<?php echo esc_attr( $command_running ); ?>';

                setTimeout(function() {
                    disable_buttons() ;
                }, 10) 
                process_flag = 1;
                jQuery('#process-box').show('slow');
                jQuery('#reviews-dropbox').hide('slow');
                jQuery('#stop-button').css('display', 'block');
                processBar(type_backup);
                showTime(1);
            });
            <?php }?>
        jQuery(document).ready(function() { 
            jQuery('.wpadm-tooltip').tooltip();
        });  
    </script>
    <?php if (!empty($error)) {
            echo '<div class="error" style="text-align: left; color: red; font-weight:bold;background:#ffcece;">
            <p style="font-size: 16px;">
            ' . $error . '
            </p></div>'; 
    }?>
    <?php if (!empty($msg)) {
            echo '<div class="updated" style="text-align: center; font-weight:bold;">
            <p style="font-size: 16px;">
            ' . $msg . '
            </p></div>'; 
    }?>

    <div id="wp-cron-enable" style="display: none;">
        <div class="close-button"><a href="javascript:void(0)" onclick="jQuery('#wp-cron-enable').arcticmodal('close');"><img src="<?php echo plugins_url('/img/closebox.png', dirname(__FILE__));?>" alt=""></a></div>
        <div class="image-enable">
            <img src="<?php echo plugins_url('/img/how-to-enable-cron.png', dirname(__FILE__));?>" alt="">
        </div>
    </div>
    <div id="is-dropbox-auth" style="display: none; width: 380px; text-align: center; background: #fff; border: 2px solid #dde4ff; border-radius: 5px;">
        <div class="title-description" style="font-size: 20px; text-align: center;padding-top:45px; line-height: 30px;">
            <?php _e('Your Dropbox account must be connected before backup to Dropbox.', 'dropbox-backup'); ?> <br />
            <?php _e('To do this, please, click the button "Connect to Dropbox" in the settings', 'dropbox-backup'); ?> <br />
            <?php _e('or add your own Dropbox credentials:', 'dropbox-backup'); ?> <br />
            <strong><?php _e('"App key"','dropbox-backup'); ?></strong> & <strong><?php _e('"App secret"','dropbox-backup'); ?></strong> <br />
            <?php _e('in the Setting Form','dropbox-backup'); ?>
        </div>
        <div class="button-description" style="padding:20px 0;padding-top:45px">
            <input type="button" value="<?php _e('OK','dropbox-backup'); ?>" onclick="jQuery('#is-dropbox-auth').arcticmodal('close');" style="text-align: center; width: 100px;" class="button-wpadm">
        </div>
    </div>
    <?php 
        if (isset($sent_response)) {
        ?>
        <script>
            jQuery(document).ready(function() {
                jQuery('#sent-error-report').arcticmodal({
                    beforeOpen: function(data, el) {
                        jQuery('#sent-error-report').css('display','block');
                    },
                    afterClose: function(data, el) {
                        jQuery('#sent-error-report').css('display','none');
                    }
                });
            })
        </script>
        <div id="sent-error-report" style="display: none;" >
            <div class="text-view">
                <?php echo esc_html( $sent_response ) ;?>
            </div>
            <div class="button-sent-report">
                <input type="button" class="button-wpadm" value="<?php _e('OK','dropbox-backup'); ?>" onclick="jQuery('#sent-error-report').arcticmodal('close')" />
            </div>
        </div>
        <?php
        }
    ?>
    <div id="ajax-alert" style="display: none;width: 800px; text-align: center; background: #fff; border: 2px solid #dde4ff; border-radius: 5px;">
        <div id="ajax-message" style="font-size: 15px; margin-top: 10px; margin-bottom: 30px;"></div>
        <div style="font-size: 15px; margin-bottom: 30px;"><?php _e('To solve this problem, we need to access the system logs of your hosting/server and/or from your backup, <br />that you tried to create or simply send to us your FTP access data.','dropbox-backup');?></div>

        <form action="<?php echo admin_url( 'admin-post.php?action=error_logs_check' )?>" method="post" style=" text-align: left; margin-left:110px;margin-bottom:20px;">
            <div style="margin-top: 10px; font-size: 16px; font-weight: bold; margin-bottom: 10px;">
                <input type="checkbox" onclick="showFormAjax();" style="margin: 0;" id="show-form-ajax" /> <label for="show-form-ajax"><?php _e('I want to provide your FTP access to resolve this issue quickly:','dropbox-backup');?></label>
            </div>
            <div id="form-ajax-ftp-email" style="display: none;">
                <div class="form-help-send-error" >
                    <div style="margin-top: 3px;">
                        <div class="label-help" style="">
                            <label for="ftp-host"><?php _e('FTP Host','dropbox-backup'); ?></label>
                        </div>  
                        <div style="float:left; ">
                            <input type="text" id="ftp-host" value="<?php echo esc_url( str_ireplace( array('http://', 'https://'), '', home_url() ) ) ;?>" name="ftp_host" >
                        </div>
                    </div>
                    <div class="clear"></div>
                    <div style="margin-top: 3px;">
                        <div class="label-help" > 
                            <label for="ftp-user"><?php _e('FTP User','dropbox-backup'); ?></label>
                        </div>
                        <div style="float:left; ">
                            <input type="text" id="ftp-user" value="" name="ftp_user">
                        </div>
                    </div>
                    <div class="clear"></div>
                    <div style="margin-top: 3px;">
                        <div class="label-help" > 
                            <label for="ftp-pass"><?php _e('FTP Password','dropbox-backup'); ?></label>
                        </div>
                        <div style="float:left; ">
                            <input type="text" id="ftp-pass" value="" name="ftp_pass">
                        </div>
                    </div>
                    <div class="clear"></div>

                </div>
                <div class="form-help-mail-response">
                    <div style="padding: 20px; border:1px solid #fff; margin-top: 3px;">
                        <div class="label-help" > 
                            <label for="email-resp"><?php _e('Response Email:','dropbox-backup'); ?></label>
                        </div>
                        <div style=" ">
                            <input type="text" id="email-resp" value="<?php echo esc_attr( get_option('admin_email') );?>" style="padding-left:3px;" name="mail_response">
                        </div>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
            <div style="text-align: left; margin-left: 100px; margin-top: 10px;">
                <input value="<?php echo esc_attr($time_log); ?>" type="hidden" name="time_pars">
                <input value="" type="hidden" name="msg_ajax" id="msg_ajax">
            </div>
            <div class="ajax-button" style="margin-bottom: 10px;">
                <input type="submit" class="button-wpadm"  value="<?php _e('SEND TO SUPPORT and close this window','dropbox-backup'); ?>" />&nbsp;&nbsp;&nbsp;
                <input type="button" class="button-wpadm" onclick="jQuery('#ajax-message').arcticmodal('close')" value="<?php _e('Close this window WITHOUT SENDING TO SUPPORT','dropbox-backup'); ?>" /> 
            </div>
        </form> 
    </div>
    <div id="helper-keys" style="display: none;width: 400px; text-align: center; background: #fff; border: 2px solid #dde4ff; border-radius: 5px;">
        <div class="title-description" style="font-size: 20px; text-align: center;padding-top:20px; line-height: 30px;">
            <?php _e('Where can I find my app key and secret?','dropbox-backup'); ?>
        </div>
        <div class="button-description" style="padding:20px 10px;padding-top:20px; text-align: left;">
            <?php _e('You can get an API app key and secret by creating an app on the','dropbox-backup'); ?> 
            <a href="https://www.dropbox.com/developers/apps/create?app_type_checked=api" target="_blank"><?php _e('app creation page','dropbox-backup'); ?></a>.
            <?php _e('Once you have an app created, the app key and secret will be available on the app\'s page on the','dropbox-backup'); ?>
            <a href="https://www.dropbox.com/developers/apps" target="_blank"><?php _e('App Console','dropbox-backup'); ?></a>
            . <?php _e('Note that Drop-ins have app keys but no app secrets.','dropbox-backup'); ?>
        </div>
        <div class="button-description" style="padding:20px 0;padding-top:10px">
            <input type="button" value="<?php _e('OK','dropbox-backup'); ?>" onclick="jQuery('#helper-keys').arcticmodal('close');" style="text-align: center; width: 100px;" class="button-wpadm">
        </div>
    </div>
    <div class="block-content" style="margin-top:20px;">
        <?php include 'advantage-plugin.php'; ?>
    </div>
    <div class="block-content" style="margin-top:2px;">
        <div style="min-height : 215px; padding: 5px; padding-top: 10px;">
            <div class="log-dropbox" style="background-image: url(<?php echo plugins_url('/img/dropbox.png', dirname(__FILE__));?>);">
            </div>
            <div style="float: bottom; font-size: 40px; font-weight: bold; text-shadow: 1px 2px 2px #666; margin-left: 189px; line-height: 42px; margin-top: -12px;">
                <?php _e('Dropbox Full Backup','dropbox-backup');?> 
                <span style="font-size: 20px;"><?php _e('(files+database)','dropbox-backup');?></span>

                <span style="font-size: 11px;">
                    <?php echo (isset($plugin_data[0]['Version']) ? 'Version ' . esc_html( $plugin_data[0]['Version'] ) : '')?>
                </span>

            </div>
            <?php if ($show) {?>
                <div id="container-user" class="cfTabsContainer" onclick="showRegistInfo(false)" style="width: 48%; padding-bottom: 0px; padding-top: 0px; float: left; margin-left: 20px;">
                    <div class="stat-wpadm-info-title" id="title-regisr" style="padding :9px 0px; margin-top:11px; line-height: 24px;">
                        <?php if (false) {?>
                            <?php _e('Free Sign Up','dropbox-backup'); ?> <br /><?php _e('to backup more than one website...','dropbox-backup'); ?>
                            <?php } else {
                                _e('Professional version<br /> "Dropbox backup and restore"<br /><span class="hover-title">get more...</span>','dropbox-backup');
                        } ?>
                    </div>
                    <div id="cf_activate" class="cfContentContainer" style="display: none;">
                        <?php if (false) {?> 
                            <form method="post" id="dropbox_form" action="<?php echo admin_url( 'admin-post.php?action=wpadm_activate_plugin' )?>" >
                                <div class="stat-wpadm-registr-info" style="margin-bottom: 40px; margin-top: 17px;">
                                    <table class="form-table stat-table-registr" style="">
                                        <tbody>
                                            <tr valign="top">
                                                <th scope="row">
                                                    <label for="email"><?php _e('E-mail','dropbox-backup'); ?></label>
                                                </th>
                                                <td>
                                                    <input id="email" class="" type="text" name="email" value="">
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row">
                                                    <label for="password"><?php _e('Password','dropbox-backup'); ?></label>
                                                </th>
                                                <td>
                                                    <input id="password" class="" type="password" name="password" value="">
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row">
                                                    <label for="password-confirm"><?php _e('Password confirm','dropbox-backup'); ?></label>
                                                </th>
                                                <td>
                                                    <input id="password-confirm" class="" type="password" name="password-confirm" value="">
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row">
                                                </th>
                                                <td>
                                                    <input class="button-wpadm" type="submit" value="<?php _e('Register & Activate','dropbox-backup'); ?>" name="send">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="stat-wpadm-info" id="registr-info" style="margin-bottom: 2px; margin-top: 30px;">
                                    <span style="font-weight:bold; font-size: 14px;"><?php _e('If you are NOT registered at','dropbox-backup'); ?> <a target="_blank" style="color: #fff" href="<?php echo esc_url( SERVER_URL_INDEX ); ?>"><?php _e('WPAdm','dropbox-backup'); ?></a>,</span> <?php _e('enter your email and password to use as your Account Data for authorization on WPAdm.','dropbox-backup'); ?> <br /><span style="font-weight: bold;font-size: 14px;"><?php _e('If you already have an account at','dropbox-backup'); ?> <a target="_blank" style="color: #fff" href="<?php echo esc_url( SERVER_URL_INDEX ); ?>"><?php _e('WPAdm','dropbox-backup'); ?></a></span> <?php _e('and you want to Sign-In, so please, enter your registered credential data (email and password twice).','dropbox-backup'); ?>
                                </div>
                            </form>
                            <?php } else { 
                                $repeat_advantage = false;
                                include 'advantage-plugin.php';
                            }
                        ?>
                    </div>
                    <div class="clear"></div> 
                    <div class="block-button-show" style="color: #fff;">
                        <div class="block-click" onclick="showRegistInfo(true, false);">
                            <span id="registr-show" style="color: #fff;"><?php _e('Show','dropbox-backup'); ?></span>
                            <div id="registr-choice-icon" class="dashicons dashicons-arrow-down" style=""></div>
                        </div>
                    </div>

                </div>
                <?php } else { ?>
                <div id="container-user" class="cfTabsContainer" style="width: 48%; padding-bottom: 0px; padding-top: 0px; float: left; margin-left: 20px;">
                    <div class="stat-wpadm-info-title" id="title-regisr" style="padding :10px 0px; margin-top:11px; line-height: 25px; text-align: left; margin-left: 10px;">
                        <?php _e('Sign In to backup more than one website...','dropbox-backup'); ?>
                    </div>
                    <div>
                        <form method="post" id="auth" name="auth" action="<?php echo esc_url( SERVER_URL_INDEX . "login-process" ) ; ?>">
                            <div>
                                <div id="message-form" style="color: red; float: left;margin: 10px;margin-top: 14px;"></div>
                            </div>
                            <div style="padding: 5px; clear: both;">
                                <div class="form-field">
                                    <input class="input-small" type="text" id="username" value="<?php echo esc_attr( get_option(PREFIX_BACKUP_ . "email") );?>" readonly="readonly" required="required" name="username" placeholder="<?php _e('Email','dropbox-backup'); ?>" /> 
                                </div>
                                <div class="form-field">
                                    <input class="input-small" type="password" required="required" name="password" placeholder="<?php _e('Password','dropbox-backup'); ?>" />
                                </div>
                                <div class="form-field">
                                    <input class="button-wpadm" type="button" value="Sign In" onclick="auth_form(this);" />
                                    <input type="hidden" value="<?php echo 'dropbox-backup'?>" name="plugin" />
                                </div>
                            </div>
                            <div style="clear:both; padding: 5px; font-size: 11px; color: #fff;">
                                <div class="form-field" style="margin-bottom: 10px;">
                                    <input type="checkbox" onclick="setReadOnly('username')" style="margin: 0px;"> <?php _e('set new mail','dropbox-backup'); ?> 
                                </div>
                            </div>
                            <div style="clear:both;"></div>
                        </form>
                    </div>
                </div>
                <?php } ?>
            <?php include_once 'settings_form.php'?>
        </div> 
    </div>     
    <div style="clear: both;"></div>
    <div class="block-content">
        <div class="" style="margin-top:10px;">
            <div id="logs-form" style="display: none; float:left; width: 60%;">
                <div class="title-logs progress-box">      
                    <span style="font-size:16px;"><?php _e('Please wait...','dropbox-backup'); ?> 
                        <span id="time_backup">0 <?php _e('sec.','dropbox-backup'); ?></span>
                    </span>     
                </div>
                <div class="title-status" style="font-size:16px; display: none;"></div>
                <div style="border: 1px solid #ddd; text-align: left; background: #fff; padding: 2px;">
                    <div id="log-backup" style="overflow: auto; height: 60px; border: 5px solid #fff; "></div>
                </div>
            </div>
            <?php if ($stars5) {?>
                <div id="reviews-dropbox" class="pointer" onclick="window.open('https://wordpress.org/support/view/plugin-reviews/dropbox-backup?filter=5');">
                    <div class="title-reviews"><?php _e('++ Review ++','dropbox-backup'); ?></div>
                    <div class="desc-reviews"><?php _e('Your review is important for us','dropbox-backup'); ?></div>
                    <img src="<?php echo plugins_url('/img/stars-5.png', dirname(__FILE__));?>" alt=""></a>
                </div>
                <?php }?>
            <div id="process-box" style="display: none;">
                <div class="main-title"><?php _e('Backup processing status', 'dropbox-backup')?></div>
                <?php if (isset($dropbox_options['is_repair']) && $dropbox_options['is_repair'] == 1) {?>
                    <div class="inline progress-box" id="repair-process" data-width="0%" style="display: block;">
                        <div class="progress-text">
                            <?php _e('Repairing of database tables', 'dropbox-backup')?> 
                            <span class="stats-repair" style="display: none;">(<span id="count_repair">0</span> <?php _e('of', 'dropbox-backup'); ?> <span id="all_repair">0</span>)</span> <span class="procent-progress" id="procent_repair">0%</span>
                        </div>
                        <div class="progress-bar procent-repair" style="width: 0%;">
                        </div>
                    </div>
                    <?php } ?>
                <?php if ( !isset($dropbox_options['is_optimization']) || ( isset($dropbox_options['is_optimization']) && $dropbox_options['is_optimization'] == 1 ) ) { ?>
                    <div class="inline progress-box" id="optimization-process" style="display: block;">
                        <div class="progress-text"> 
                            <?php _e('Optimization of database tables', 'dropbox-backup')?>
                            <span class="stats-optimization" style="display: none;">(<span id="count_optimization">0</span> <?php _e('of', 'dropbox-backup'); ?> <span id="all_optimization">0</span>)</span> <span class="procent-progress" id="procent_optimization">0%</span> 
                        </div>
                        <div class="progress-bar procent-optimization" style="width: 0%;">
                        </div>
                    </div>
                    <?php } ?> 

                <div class="inline progress-box" id="mysqldump-process" style="display: block;">
                    <div class="progress-text"> 
                        <?php _e('Creation of database dump', 'dropbox-backup')?>
                        <span class="stats-mysqldump" style="display: none;">(<span id="count_mysqldump">0</span> <?php _e('of', 'dropbox-backup'); ?> <span id="all_mysqldump">0</span>)</span> <span class="procent-progress" id="procent_mysqldump">0%</span>
                    </div>
                    <div class="progress-bar procent-mysqldump" style="width: 0%;">
                    </div>
                </div>


                <div class="inline progress-box" id="archiving-process" style="display: block;">
                    <div class="progress-text">
                        <?php _e('Archiving of files', 'dropbox-backup')?>
                        <span class="stats-archiving" style="display: none;">(<span id="count_archiving">0</span> <?php _e('of', 'dropbox-backup'); ?> <span id="all_archiving">0</span>)</span> <span class="procent-progress" id="procent_archiving">0%</span>
                    </div>
                    <div class="progress-bar procent-archiving" style="width: 0%;">
                    </div>
                </div> 

                <div class="inline progress-box" id="dropbox-process" style="display: none;">
                    <div class="progress-text"> 
                        <?php _e('Sending of backup to Dropbox', 'dropbox-backup')?>
                        <span class="stats-dropbox" style="display: none;">(<span id="count_dropbox">0</span> <?php _e('of', 'dropbox-backup'); ?> <span id="all_dropbox">0</span>)</span> <span class="procent-progress" id="procent_dropbox">0%</span>
                    </div>
                    <div class="progress-bar procent-dropbox" style="width: 0%;">
                    </div>
                </div>
            </div>    
            <div id="support-button" style="float: right; margin-top: 130px; margin-right: 20px;">  
                <button onclick="window.open('<?php echo esc_url( SERVER_URL_INDEX . 'support/?pl=dbp' ); ?>')" class="backup_button" style="padding: 5px 10px; margin-top: 10px; font-size: 15px;bottom: 0px;"><?php _e('Help','dropbox-backup'); ?></button> <br />
            </div>
            <div id="action-buttons" style="">
                <div style="float: left;">
                    <button onclick="start_dropbox_backup();" text-button="<?php _e('Create Dropbox Backup','dropbox-backup');?>" class="backup_button disable-button" style=""><?php _e('Create Dropbox Backup','dropbox-backup'); ?></button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </div>
                <div style="float: left; margin-top: 2px;">
                    <button onclick="start_local_backup()" text-button="<?php _e('Create Local Backup','dropbox-backup');?>" class="backup_button disable-button" style="padding: 5px 10px; margin-top: 10px; font-size: 15px;bottom: 0px;"><?php _e('Create Local Backup','dropbox-backup'); ?></button> <br />
                </div>
                <div id="stop-button" style="float: left; margin-top: 2px; margin-left:20px; display: none;">
                    <button onclick="stopBackup();" text-button="<?php _e('Stop Backup','dropbox-backup');?>" class="backup_button" style="padding: 5px 10px; margin-top: 10px; font-size: 15px;bottom: 0px;"><?php _e('Stop Backup','dropbox-backup'); ?></button> <br />
                </div>
                <!-- <div style="float: left; margin-top: 2px;margin-left: 20px;">
                <button onclick="InludesSetting();" class="backup_button" style="padding: 5px 10px; margin-top: 10px; font-size: 15px;bottom: 0px;"><?php _e('Folders & files','dropbox-backup'); ?></button> <br />
                </div> -->
                <div style="clear: both;"></div>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>
        <div>
            <form action="<?php echo esc_url( WPADM_URL_BASE );?>wpsite/recovery-backup" method="post" target="_blank" id="form_auth_backup" name="form_auth_backup">
            </form>
            <form action="<?php echo esc_url( WPADM_URL_BASE ) ;?>backup/tasks" method="post" target="_blank" id="form_auth_backup_create" name="form_auth_backup_create">
                <input type="hidden" name="url_task_create" value="<?php echo esc_url( get_option('siteurl') );?>">
            </form>
            <form action="" method="post" id="form_auth_backup" name="form_auth_backup">
            </form>
            <form action="<?php echo admin_url( 'admin-post.php?action=wpadm_delete_backup' )?>" method="post" id="delete_backups" name="delete_backups">
                <input type="hidden" name="backup-name" id="backup_name" value="" />
                <input type="hidden" name="backup-type" id="backup_type" value="" />
                <input type="hidden" name="nonce" id="id_nonce_delete" value="<?php echo esc_attr( $nonce );?>" />
            </form>
            <form action="<?php echo admin_url( 'admin-post.php?action=stop_backup' )?>" method="post" id="stop_backups" name="stop_backups">
                <input type="hidden" name="type_backup" id="type_backup" value="" />
                <input type="hidden" name="nonce" id="id_nonce" value="<?php echo esc_attr( $nonce ); ?>" />
            </form>
            <div id="inludes-setting" class="" style="display: none; width: 550px;  position: relative; text-align: center; background: #e5e5e5; border: 2px solid #dde4ff; border-radius: 5px;">
                <div>
                    <div class="title-description" style="font-size: 20px; text-align: center;padding-top:20px; line-height: 30px;">
                        <?php _e('Selected files and folders will be included to backup','dropbox-backup'); ?>
                        <div style="font-size: 14px;">
                            <?php _e('Database of website will be included automatically','dropbox-backup'); ?>
                        </div>
                    </div>
                    <div class="loading-img">
                        <img style="display: none; margin: 0 auto;" src="<?php echo plugins_url('/img/wpadmload.gif', dirname(__FILE__) ); ?>"> 
                    </div>
                    <div class="button-description">
                        <!--  <input type="radio" value="folder" id="inc-folder" checked="checked" name="includes-files-folder"><label for="inc-folder"><?php _e('View folders','dropbox-backup'); ?></label> 
                        &nbsp;&nbsp;&nbsp; <input type="radio" value="files" id="inc-files" name="includes-files-folder"><label for="inc-files"><?php _e('View Folders & Files','dropbox-backup'); ?></label>   -->
                        <div class="show-includes">

                        </div>
                    </div>
                    <div class="clear"></div>
                    <div class="button-description" style="padding:20px 0; width: 100%;">
                        <input type="button" value="<?php _e('OK','dropbox-backup'); ?>" onclick="saveIncludes();" style="text-align: center; width: 100px;" class="button-wpadm">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <input type="button" value="<?php _e('Cancel','dropbox-backup'); ?>" onclick="InludesSetting();" style="text-align: center; width: 100px;" class="button-wpadm">
                    </div>
                </div>

            </div>   

            <table class="table" style="margin-top: 5px; display: <?php echo isset($data['md5']) && ($n = count($data['data'])) && is_array($data['data'][0]) ? 'table' : 'none'?>;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th align="left"><?php _e('Create, Date/Time','dropbox-backup'); ?></th>
                        <th><?php _e('Name of Backup','dropbox-backup'); ?></th>
                        <th><?php _e('Archive Parts','dropbox-backup'); ?></th>
                        <th><?php _e('Status','dropbox-backup'); ?></th>
                        <th><?php _e('Type of Backup','dropbox-backup'); ?></th>
                        <th><?php _e('Size','dropbox-backup'); ?></th>
                        <?php if(is_admin() || is_super_admin()) {?>
                            <th><?php _e('Action','dropbox-backup'); ?></th>
                            <?php
                            }
                        ?> 
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($data['md5']) && ($n = count($data['data'])) && is_array($data['data'][0])) { 
                            for($i = 0; $i < $n; $i++) {
                                $size = WPADM_getSize($data['data'][$i]['size']); /// MByte
                                //$size = round($size, 2);
                                $files = explode(",", str_replace(array('"', "[", "]"), "", $data['data'][$i]['files'] ) );
                                $f = count($files);
                            ?>
                            <tr>
                                <td class="number-backup"><?php echo ($i + 1);?></td>
                                <td onclick="shows('<?php echo esc_attr( md5( print_r($data['data'][$i], 1) ) );?>', this)" class="pointer" style="text-align: left; padding-left: 7px;"><?php echo esc_html( $data['data'][$i]['dt'] ); ?></td>
                                <td onclick="shows('<?php echo esc_attr( md5( print_r($data['data'][$i], 1) ) );?>', this)" class="pointer" <?php echo (isset($is_runnig) && $is_runnig  && isset($name_running_backup) && $name_running_backup == $data['data'][$i]['name']  ? 'style="text-align:left;"' : '')?>>
                                    <?php 
                                        $e = '';
                                        if ( isset($is_runnig) && $is_runnig && isset($name_running_backup) && $name_running_backup == $data['data'][$i]['name']) {
                                            $e = "<strong style=\"color:#ff8104; margin-left:6px;\">". __('Please wait, backup will be created...','dropbox-backup') . "</strong>";
                                        } elseif (  $data['data'][$i]['size'] != 0  ) { 
                                            if (isset($data['data'][$i]['not_all_upload']) && $data['data'][$i]['not_all_upload'] === false) {
                                                $e = "<strong style=\"color:red;\">" . __( 'Not all files were uploaded/created!', 'dropbox-backup') . "</strong>";
                                            } else {
                                                $e = $data['data'][$i]['name'];
                                            }
                                        } else {
                                            $e = "<strong style=\"color:red;\">". __('Not available','dropbox-backup') . "</strong>&nbsp;&nbsp;(<a style=\"text-decoration:underline;\">".__('Why?','dropbox-backup')."</a>)"; 
                                        } 
                                        echo ($e);
                                    ?>
                                    <script type="text/javascript">
                                        backup_name = '<?php echo esc_attr( $data['data'][$i]['name'] ); ?>';
                                    </script>
                                </td>
                                <td onclick="shows('<?php echo esc_attr( md5( print_r($data['data'][$i], 1) ) ) ;?>', this)" class="pointer"><?php echo isset($data['data'][$i]['count']) ? esc_html($data['data'][$i]['count']) : esc_html( $f ) ;?></td>
                                <td onclick="shows('<?php echo esc_attr( md5( print_r($data['data'][$i], 1) ) );?>', this)" class="pointer" style="padding: 0px;">
                                    <?php if ( isset($is_runnig) && $is_runnig && isset($name_running_backup) && $name_running_backup == $data['data'][$i]['name']  ) { ?> 
                                        <img src="<?php echo plugin_dir_url(__FILE__) . "attention.png" ;?>" title="Attention" alt="Attention" style="float: left; width: 20px; height: 20px;margin-left: 21px;" />
                                        <div style="margin-top :1px;float: left;"><?php _e('Backup in progress','dropbox-backup');?></div>
                                        <?php
                                        } elseif($data['data'][$i]['size'] != 0) { ?>
                                        <?php
                                            if (isset($data['data'][$i]['not_all_upload']) && $data['data'][$i]['not_all_upload'] === false) {
                                            ?>
                                            <img src="<?php echo plugin_dir_url(__FILE__) . "not-ok.png" ;?>" title="Fail" alt="Fail" style="float: left; width: 20px; height: 20px;margin-left:  61px;;" />
                                            <div style="float: left;"><?php _e('Fail','dropbox-backup');?></div><div style="clear: both;">(<a style="text-decoration:underline;"><?php _e('Show uploaded/created files','dropbox-backup');?></a>)</div>
                                            <?php
                                            } else {
                                            ?>
                                            <img src="<?php echo plugin_dir_url(__FILE__) . "ok.png" ;?>" title="Successful" alt="Successful" style="float: left; width: 20px; height: 20px;margin-left: 21px;" />
                                            <div style="margin-top :1px;float: left;"><?php _e('Successful','dropbox-backup');?></div>
                                            <?php }
                                        } else {
                                        ?>
                                        <img src="<?php echo plugin_dir_url(__FILE__) . "not-ok.png" ;?>" title="Fail" alt="Fail" style="float: left; width: 20px; height: 20px;margin-left: 21px;" />
                                        <div style="margin-top :1px;float: left;"><?php _e('Fail','dropbox-backup');?>&nbsp;&nbsp;(<a style="text-decoration:underline;"><?php _e('Show Details','dropbox-backup');?></a>)</div>
                                        <?php 
                                    }?>
                                </td>
                                <td onclick="shows('<?php echo esc_attr( md5( print_r($data['data'][$i], 1) ) );?>', this)" class="pointer"><?php echo esc_html( $data['data'][$i]['type'] );?> <?php _e('backup','dropbox-backup'); ?></td>
                                <td onclick="shows('<?php echo esc_attr( md5( print_r($data['data'][$i], 1) ) );?>', this)" class="pointer"><?php echo esc_html( $size )?></td>
                                <td> 
                                    <?php if(is_admin() || is_super_admin()) {?>
                                        <?php if ($data['data'][$i]['size'] > 0) { 
                                                if (isset($data['data'][$i]['not_all_upload']) && $data['data'][$i]['not_all_upload'] === false) {
                                                ?>
                                                <div style="width: 94px;float:left;">&nbsp;</div>
                                                <?php
                                                } else {
                                                ?>
                                                <a class="button-wpadm" href="javascript:void(0)" title="<?php _e('Restore','dropbox-backup'); ?>" onclick="show_recovery_form('<?php echo isset($data['data'][$i]['name']) && $data['data'][$i]['type'] != 'local' ? $data['data'][$i]['name'] : 'local' ?>', '<?php echo esc_attr( $data['data'][$i]['name'] );?>')" style="color: #fff;"><span class="pointer dashicons dashicons-backup" style="margin-top:3px;"></span><?php _e('Restore','dropbox-backup'); ?></a>&nbsp;
                                                <?php }
                                        } ?>
                                        <a class="button-wpadm" href="javascript:void(0)" title="<?php _e('Delete','dropbox-backup'); ?>" onclick="delete_backup('<?php echo esc_attr( $data['data'][$i]['name'] ); ?>', '<?php echo esc_attr( $data['data'][$i]['type'] );?>')" style="color: #fff;"><span class="pointer dashicons dashicons-trash" style="margin-top:3px;"></span><?php _e('Delete','dropbox-backup'); ?></a>&nbsp;
                                        <?php
                                        }
                                    ?>
                                </td> 
                            </tr>
                            <tr id="<?php echo esc_attr( md5( print_r($data['data'][$i], 1) ) );?>" style="display:none; ">
                                <?php if ($data['data'][$i]['size'] != 0) {?>
                                    <td colspan="2">
                                    </td>
                                    <td align="center" style="padding: 0px; width: 350px;">
                                        <div style="overflow: auto; max-height: 150px;">
                                            <?php 
                                                if ($f > 0) {  ?>
                                                <table border="0" align="center" class="info-path"> <?php
                                                        for($j = 0; $j < $f; $j++) {
                                                            if (!empty($files[$j])) {
                                                            ?>
                                                            <tr style="border: 0;">
                                                                <td style="border: 0;">
                                                                    <?php if ($data['data'][$i]['type'] == 'local') {?>
                                                                        <a href="<?php echo esc_url( content_url(DROPBOX_BACKUP_DIR_NAME) . "/{$data['data'][$i]['name']}/{$files[$j]}" ) ;?>">
                                                                            <?php echo esc_html( $files[$j] ); ?>
                                                                        </a>
                                                                        <?php 
                                                                        } else { 
                                                                            echo esc_html( $files[$j] ); 
                                                                        } 
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                            }
                                                        }
                                                    ?>
                                                </table>
                                                <?php
                                                } 
                                            ?>
                                        </div>
                                    </td>
                                    <td colspan="6"></td> 
                                    <?php 
                                    } elseif (isset($is_runnig) && $is_runnig && isset($name_running_backup) && $name_running_backup == $data['data'][$i]['name'] ) {
                                    ?>
                                    <td colspan="8">
                                        <?php _e('Backup is created. If you are sure that the backup down(crashed), please ','dropbox-backup');?> 
                                        <a href="javascript:void(0)" onclick="window.open('<?php echo esc_url(SERVER_URL_INDEX . 'support/?pl=dbp') ?>')"><?php _e('contact us','dropbox-backup'); ?></a>.
                                    </td>
                                    <?php
                                    } else { ?>
                                    <td colspan="2">
                                    </td>
                                    <td colspan="4" style="background: #ddecf9;">
                                        <div style="padding-left: 10px; padding-right: 10px;">
                                            <div style="font-size: 12px; text-align: left;">
                                                <?php 
                                                    $time_log = str_replace(array(':', '-', " "), "_", $data['data'][$i]['dt']); 
                                                    if ( file_exists( $base_path . "/tmp/logs_error_" . $time_log ) ) {
                                                        $log_ = file_get_contents( $base_path . "/tmp/logs_error_" . $time_log );
                                                        $pos = stripos($log_, "error");
                                                        if ($pos !== false) {
                                                            for($p = $pos; ; $p--) {
                                                                if ($log_{$p} == "\n") {
                                                                    $pos_new = $p + 1;
                                                                    break;
                                                                }
                                                            }
                                                            $error =substr($log_, $pos_new);
                                                            echo str_replace("\n", "<br />", $error);
                                                        }
                                                    } else {
                                                        _e('Error log wasn\'t Found','dropbox-backup');
                                                }?> 
                                            </div>

                                            <form action="<?php echo admin_url( 'admin-post.php?action=error_logs_check' )?>" method="post" style=" text-align: left;">
                                                <div style="margin-top: 10px; font-size: 16px; font-weight: bold; margin-bottom: 10px;">
                                                    <?php _e('Please, provide your FTP access to resolve this issue quickly:','dropbox-backup');?>
                                                </div>
                                                <div class="form-help-send-error" >
                                                    <div style="margin-top: 3px;">
                                                        <div class="label-help" style="">
                                                            <label for="ftp-host"><?php _e('FTP Host','dropbox-backup'); ?></label>
                                                        </div>  
                                                        <div style="float:left; ">
                                                            <input type="text" id="ftp-host" value="<?php echo esc_url( str_ireplace(array('http://', 'https://'), '', home_url()) ) ;?>" name="ftp_host" >
                                                        </div>
                                                    </div>
                                                    <div class="clear"></div>
                                                    <div style="margin-top: 3px;">
                                                        <div class="label-help" > 
                                                            <label for="ftp-user"><?php _e('FTP User','dropbox-backup'); ?></label>
                                                        </div>
                                                        <div style="float:left; ">
                                                            <input type="text" id="ftp-user" value="" name="ftp_user">
                                                        </div>
                                                    </div>
                                                    <div class="clear"></div>
                                                    <div style="margin-top: 3px;">
                                                        <div class="label-help" > 
                                                            <label for="ftp-pass"><?php _e('FTP Password','dropbox-backup'); ?></label>
                                                        </div>
                                                        <div style="float:left; ">
                                                            <input type="text" id="ftp-pass" value="" name="ftp_pass">
                                                        </div>
                                                    </div>
                                                    <div class="clear"></div>

                                                </div>
                                                <div class="form-help-mail-response">
                                                    <div style="padding: 20px; border:1px solid #fff; margin-top: 3px;">
                                                        <div class="label-help" > 
                                                            <label for="email-resp"><?php _e('Response Email:','dropbox-backup'); ?></label>
                                                        </div>
                                                        <div style=" ">
                                                            <input type="text" id="email-resp" value="<?php echo esc_attr( get_option('admin_email') );?>" style="padding-left:3px;" name="mail_response">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="clear"></div>
                                                <div style="text-align: left; margin-left: 100px; margin-top: 10px;">
                                                    <input value="<?php echo esc_attr( $time_log ); ?>" type="hidden" name="time_pars">
                                                    <input class="backup_button" style="font-size: 14px;font-weight: normal;padding: 3px;text-shadow: 0px;" type="submit" value="<?php _e('Send request to support','dropbox-backup'); ?>">
                                                </div>
                                            </form>

                                        </div>
                                    </td>
                                    <td colspan="3">
                                    </td>
                                    <?php 
                                    }
                                ?>
                            </tr>
                            <?php 
                        } ?>
                        <?php } ?>
                </tbody>
            </table>

        </div>
    </div>

</div>
