<?php /**
 * Template version: 3.1.0
 * Template zone: frontend
 *
 * -= 3.1.0 =-
 * - Allow files upload dialog box to select multiple files if Enhanced Files add-on is active
 *
 * -= 3.0.0 =-
 * - Initial version
 */ ?>

<?php
/** @var int $post_id */
/** @var bool $ef_addon_exists */
?>

<?php
$multiple_input = $ef_addon_exists ? ' multiple="multiple"' : '';
?>

<div class="cuar-classic-uploader cuar-js-classic-uploader" data-post-id="<?php echo esc_attr($post_id); ?>">
    <?php wp_nonce_field('cuar-attach-classic-upload-' . $post_id, 'cuar_classic-upload_' . $post_id); ?>

    <div class="cuar-dropzone cuar-js-dropzone" id="cuar_dropzone" data-post-id="<?php echo esc_attr($post_id); ?>">
        <div class="cuar-dropzone-message">
            <span class="fa fa-upload"></span><br />
            <span><?php _e('Drop your files here or click me!', 'cuar'); ?></span>
        </div>
        <input type="file" name="cuar_file" class="cuar-file-input cuar-js-file-input"<?php echo $multiple_input; ?>/>
    </div>
</div>

<script type="text/javascript">
    <!--
    (function ($) {
        "use strict";
        $(document).ready(function ($) {
            $('#cuar-js-content-container').on('cuar:wizard:initialized', function(){
                $('.cuar-js-classic-uploader').classicUploader();
            });
        });
    })(jQuery);
    //-->
</script>
