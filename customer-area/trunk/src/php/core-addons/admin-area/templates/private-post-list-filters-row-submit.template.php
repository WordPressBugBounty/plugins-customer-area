<?php /**
 * Template version: 1.1.0
 *
 * -= 1.1.0 =-
 * - Fix some WP Customer Area strings
 *
 * -= 1.0.0 =-
 * Initial version
 *
 */ ?>

<input type="submit" name="filter_action" id="post-query-submit" class="button cuar-filter-button"
       value="<?php echo esc_attr(sprintf(__('Search within %s', 'cuar'),
           $post_type_object->labels->name)); ?>">