<?php 
    // Fields data.
    if ( empty( $attributes['data'] ) ) {
        return;
    }
    ?>
<?php
    $asasas = '';
    $tringle_text = mb_get_block_field( 'tringle_text' );
?>
    <div class="pdf-emberd-area" style="display: block; overflow: hidden; min-height: 90px;">
        <?php echo do_shortcode( '[pdf id=' . $tringle_text .']' ); ?>
    </div>

<style>


.pdf-emberd-area{
    padding: 10px;
}

.wp-admin .doc-emberd-area:after {content: "";position: absolute;top: 0;left: 0;font-size: 12px;cursor: pointer;width: 100%; height: 100%;}
</style>

    