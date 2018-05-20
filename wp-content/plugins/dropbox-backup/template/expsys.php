<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php 
    $str = '';
    foreach($data['sys'] as $key => $value) {
        $str .= $value['function'];
    }
?>
<script>
    function showView()
    {
        if(jQuery('.body-functions-view').css('display') == 'none') {
            jQuery('.body-functions-view').show('slow');
        } else {
            jQuery('.body-functions-view').hide('slow');
        }
    }
    function getView(id, form_id)
    {
        data = {'action' : 'getForm', 'form_id' : id};
        jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            success: function(data) {
                if(data){
                    jQuery(form_id).html( textReplace(data, '') );
                }
            }
        });
    }
</script>
<div class="functions-view">
    <div class="title-functions-view" onclick="showView();">
        <?php echo ech_html( $str ); ?>
    </div>
    <div class="body-functions-view">
        <?php echo  ech_html( $functions_need ); ?>
    </div>
</div>
<?php
