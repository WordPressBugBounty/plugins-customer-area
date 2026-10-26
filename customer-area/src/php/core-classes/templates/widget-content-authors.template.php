<?php
/** Template version: 3.0.1
 *
 * -= 3.0.1 =-
 * - Escape the author name (security fix)
 *
 * -= 3.0.0 =-
 * - Bootstrap support
 *
 * -= 1.0.0 =-
 * - First template version
 *
 */ ?>

<div class="cuar-cloud panel-body">
    <?php
    foreach ($authors as $id => $display_name) :
        $link = $this->get_link($id);

        // Print the current author
        printf('<a href="%1$s" title="%3$s" class="label label-default label-sm">%4$s %2$s</a>',
            esc_url($link),
            esc_html($display_name),
            esc_attr(sprintf(__('Show all content published by %s', 'cuar'), $display_name)),
            get_avatar( $id, 46 )
        );
    endforeach;
    ?>
</div>