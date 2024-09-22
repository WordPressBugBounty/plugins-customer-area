<?php
/** Template version: 3.1.0
 *
 * -= 3.1.0 =-
 * - Use new format for term data
 *
 * -= 3.0.0 =-
 * - Initial template
 *
 */ ?>

<?php
/** @var $hide_empty */
/** @var $terms */
/** @var $depth */
?>

<?php if ($depth == 0)
{
    echo '<div id="cuar-terms-tree-' . $this->id_base . '" class="cuar-tree cuar-widget-terms-tree panel-body br-n pn">';
} ?>

    <ul class="cuar-tree-depth-<?php echo $depth; ?>">
        <?php
        foreach ($terms as $termDesc)
        {
            $term = $termDesc['term'];
            $children = $termDesc['children'];

            // Get term link
            $link = $this->get_link($term);
            $extra_class = (!empty($children) && !is_wp_error($terms)) ? ' class="folder"' : '';

            echo '<li' . $extra_class . '>';

            // Print the current term
            printf('<a href="%1$s" title="%3$s" target="_self">%2$s</a>',
                $link,
                $term->name,
                sprintf(esc_attr__('Show all content categorized under %s', 'cuar'), $term->name)
            );

            // Print all child terms in a sublist
            if (!empty($children) && !is_wp_error($terms))
            {
                $next_depth = $depth + 1;
                $this->print_term_list($children, $hide_empty, true, $next_depth);
            }

            echo '</li>';

        }
        ?>
    </ul>

<?php if ($depth == 0)
{
    echo '</div>';
}
