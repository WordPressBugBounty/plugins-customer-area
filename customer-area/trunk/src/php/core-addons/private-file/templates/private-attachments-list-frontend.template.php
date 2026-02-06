<?php /**
 * Template version: 3.2.0
 * Template zone: frontend
 *
 * -= 3.2.0 =-
 * - Fix some jQuery deprecated functions
 *
 * -= 3.1.0 =-
 * - Prevent Select2 assignment boxes to fail while initializing if the theme or a plugin is already loading Select2
 *
 * -= 3.0.0 =-
 * - Initial version
 */ ?>


<?php /** @var int $post_id */ ?><?php /** @var array $attached_files */ ?><?php /** @var string $attachment_item_template */ ?>


<?php do_action('cuar/templates/attachment-manager/before-file-attachment-list'); ?>

<div class="panel">
    <?php $attachments_count = count($attached_files); ?>
    <div class="panel-heading">
        <div class="panel-title">
            <?php _e('Attached files', 'cuar'); ?>
        </div>
    </div>
    <div class="panel-body pn">
        <table class="table cuar-file-attachments cuar-js-file-attachments">
            <?php wp_nonce_field('cuar-remove-attachment-' . $post_id, 'cuar_remove_attachment_nonce'); ?>
            <?php wp_nonce_field('cuar-update-attachment-' . $post_id, 'cuar_update_attachment_nonce'); ?>

            <?php
            foreach ($attached_files as $file_id => $attached_file) {
                include ($attachment_item_template);
            }
            ?>
            <tr class="cuar-js-empty-message" <?php if (!empty($attached_files)) echo 'style="display: none;"'; ?>>
                <td>
                    <p class="alert alert-default pastel"><?php _e('This content currently does not have any file attachment', 'cuar'); ?></p>
                </td>
            </tr>
        </table>

        <table class="cuar-js-file-attachment-template" style="display: none;">
            <?php
            $file_id = null;
            $attached_file = null;
            include ($attachment_item_template);
            ?>
        </table>
    </div>

</div>

<script type="text/javascript">
    <!--
    (function ($) {
        "use strict";
        $(document).ready(function ($) {

            // Init Select2
            if (typeof $.fn.cuarSelect2 === 'function') {
                $('#cuar-js-content-container').on('cuar:wizard:initialized', function(){
                    $('#cuar_file_selector_input').addClass('select2-single').cuarSelect2({
                        dropdownParent: $('#cuar_file_selector_input').parent(),
                        width: "100%",
                        minimumResultsForSearch: -1
                    });
                });
            }

        });
    })(jQuery);
    //-->
</script>

<?php do_action('cuar/templates/attachment-manager/after-file-attachment-list'); ?>