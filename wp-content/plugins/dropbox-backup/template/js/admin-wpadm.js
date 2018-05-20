function showTab (elem, type) 
{
    jQuery('.cfTab').removeClass('selected');
    jQuery('.cfContentContainer').hide();
    jQuery(elem).addClass('selected');
    jQuery('#cf_' + type).fadeIn();
}

function textReplace(text, addStr)
{
    return text.replace(/([\w])(\'|’)([\w])/gm, "$1$3") + addStr;
}
var set_click = '';
function setDefaultFolderBackup(dir)
{
    if ( typeof( dir ) == 'object') {
        id = 'backup_folder';
    } else {
        jQuery('#backup_folder').val( decodeURIComponent( dir ) );
        id = 'clear_backup_folder';
    }
    if (set_click != id ) {
        jQuery('#button-save-folder-backup').off("click");
        jQuery('#button-save-folder-backup').click(function() {
            saveSetting(id);
        });
        set_click = id;
        changes_setting = true;
    }
}

var shows_id = ""
var shows_t = ""
function shows(id, t)
{
    if(document.getElementById(id).style.display == "none") {
        document.getElementById(id).style.display = "table-row";
        jQuery(t).parent("tr").addClass('border-shadow-bottom');
        if (shows_id == "") {
            shows_id = id;
            shows_t = t;
        } else {
            if(shows_id != id) {
                document.getElementById(shows_id).style.display = "none";
                jQuery(shows_t).parent("tr").removeClass('border-shadow-bottom');
            }
            shows_id = id;
            shows_t  = t;
        }
    } else if(document.getElementById(id).style.display == "table-row") {
        document.getElementById(id).style.display = "none";
        jQuery(t).parent("tr").removeClass('border-shadow-bottom');
    }
}
var bl = false;
function show_form_auth(file_val)
{
    if (file_val == 'registr') {
        showRegistInfo(false);
        if (bl === false) {
            blick('container-user');
            bl = true;
        }
    } else {
        html = '<input type="hidden" value="' + file_val +'" name="internal_identifier">';
        jQuery('#form_auth_backup').html(html);
        document.form_auth_backup.submit();
    }
}
var blick_form = true;
function blick(id, border_)
{
    if (border_ == 'undefined') {
        border_ = 10;
    }
    jQuery('#' + id).css({
        outline: "0px solid #cd433d",
        border: "0px"
    }).animate({
        outlineWidth: border_ + 'px',
        outlineColor: '#cd433d'
    }, 400).animate({outlineWidth: '0px',outlineColor: '#cd433d' } , 400);
    if (blick_form) {
        setTimeout('blick("' + id + '", ' + border_ + ')', 800);
    }
}

var send_checked = [];
function connectFolder(t)
{
    folder = jQuery(t).val();
    send_checked = unique(send_checked);
    k = jQuery.inArray( folder, send_checked );
    if ( k >= 0) {
        if (!t.checked) {
            send_checked.splice(k,1);
        }
    } else {
        if (t.checked) {
            send_checked[send_checked.length] = folder;
        } 
    }
    divs = jQuery(t).parents('div[id^="inc_"]');
    set = true;
    if (divs.length > 0) {
        for(i = 0; i < divs.length; i ++) {
            if(i == 1) {
                check = jQuery(divs[i]).find('.checkbox-send:checked');
                if(check.length > 1) {
                    set = false;
                }
            }
            id = jQuery(divs[i]).attr('data-value');
            if (set) {
                send_checked = unique(send_checked);
                if (t.checked) {
                    jQuery("#send-to-" + id).attr('checked', true);   
                    send_checked[send_checked.length] = jQuery("#send-to-" + id).val();  
                } else {
                    k = jQuery.inArray( jQuery("#send-to-" + id).val(), send_checked );
                    if (k >= 0) {
                        send_checked.splice(k,1);
                    }
                    jQuery("#send-to-" + id).attr('checked', false);
                }  
            }
        }
    }
    t_id = jQuery(t).attr('data-value-cache');
    if (jQuery("#include_" + t_id).length > 0) {
        checkboxes = jQuery("#include_" + t_id).find('.checkbox-send');
        for(i = 0; i < checkboxes.length; i++) {
            send_checked = unique(send_checked);
            if (t.checked) {
                jQuery(checkboxes[i]).attr('checked', true);
                send_checked[send_checked.length] = jQuery(checkboxes[i]).val();  
            } else {
                k = jQuery.inArray( jQuery(checkboxes[i]).val(), send_checked );
                if (k >= 0) {
                    send_checked.splice(k,1);
                }
                jQuery(checkboxes[i]).attr('checked', false);
            }
        }
    }

}
function showLoadingImg(show)
{
    img = jQuery('.loading-img').find('img');
    dips = jQuery(img).css('display');
    if (dips == 'none') {
        if (show) {
            jQuery(img).css('display', 'block');
        }
    } else {
        if (!show) {
            jQuery(img).css('display', 'none');
        }
    }
}

function showLoading(id)
{
    if (jQuery(id).length > 0) {
        if (jQuery(id).find('.img-loading').length == 0) {
            jQuery(id).append('<img class="img-loading" src="'+ image_loading + '">')
        }
        if ( jQuery(id).css('display') == 'none' ) {
            jQuery(id).show('slow');
        } else {
            jQuery(id).hide('slow');
        }
    }
}

function unique(arr)
{
    arr = arr.filter(function (e, i, arr) {
        return arr.lastIndexOf(e) === i;
    });
    return arr;
}

var rt = {};

rt['%E0']='%D0%B0';rt['%E1']='%D0%B1';rt['%E2']='%D0%B2';rt['%E3']='%D0%B3';rt['%E4']='%D0%B4';
rt['%E5']='%D0%B5';rt['%B8']='%D1%91';rt['%E6']='%D0%B6';rt['%E7']='%D0%B7';rt['%E8']='%D0%B8';
rt['%E9']='%D0%B9';rt['%EA']='%D0%BA';rt['%EB']='%D0%BB';rt['%EC']='%D0%BC';rt['%ED']='%D0%BD';
rt['%EE']='%D0%BE';rt['%EF']='%D0%BF';rt['%F0']='%D1%80';rt['%F1']='%D1%81';rt['%F2']='%D1%82';
rt['%F3']='%D1%83';rt['%F4']='%D1%84';rt['%F5']='%D1%85';rt['%F6']='%D1%86';rt['%F7']='%D1%87';
rt['%F8']='%D1%88';rt['%F9']='%D1%89';rt['%FC']='%D1%8C';rt['%FB']='%D1%8B';rt['%FA']='%D1%8A';
rt['%FD']='%D1%8D';rt['%FE']='%D1%8E';rt['%FF']='%D1%8F';rt['%C0']='%D0%90';rt['%C1']='%D0%91';
rt['%C2']='%D0%92';rt['%C3']='%D0%93';rt['%C4']='%D0%94';rt['%C5']='%D0%95';rt['%A8']='%D0%81';
rt['%C6']='%D0%96';rt['%C7']='%D0%97';rt['%C8']='%D0%98';rt['%C9']='%D0%99';rt['%CA']='%D0%9A';
rt['%CB']='%D0%9B';rt['%CC']='%D0%9C';rt['%CD']='%D0%9D';rt['%CE']='%D0%9E';rt['%CF']='%D0%9F';
rt['%D0']='%D0%A0';rt['%D1']='%D0%A1';rt['%D2']='%D0%A2';rt['%D3']='%D0%A3';rt['%D4']='%D0%A4';
rt['%D5']='%D0%A5';rt['%D6']='%D0%A6';rt['%D7']='%D0%A7';rt['%D8']='%D0%A8';rt['%D9']='%D0%A9';
rt['%DC']='%D0%AC';rt['%DB']='%D0%AB';rt['%DA']='%D0%AA';rt['%DD']='%D0%AD';rt['%DE']='%D0%AE';
rt['%DF']='%D0%AF';

function convert_to_cp1251(str) {
    var ret='';

    var l=str.length;
    var i=0;
    while (i<l) {

        var f=0;
        for (keyVar in t) {
            if (str.substring(i,i+6)==keyVar) {ret+=t[keyVar];i+=6;f=1;}
        }

        if (!f) {ret+=str.substring(i,i+1);i++;}
    }

    return ret;
}

function convert_from_cp1251(str) {
    var ret='';

    var l=str.length;
    var i=0;
    while (i<l) {

        var f=0;
        for (keyVar in rt) {
            if (str.substring(i,i+3)==keyVar) {ret+=rt[keyVar];i+=3;f=1;}
        }

        if (!f) {ret+=str.substring(i,i+1);i++;}
    }

    return ret;
}

function urldecode (str) {
    try {ret=decodeURIComponent((str + '').replace(/\+/g, '%20'));}
    catch (e) {}
    return ret;
}
