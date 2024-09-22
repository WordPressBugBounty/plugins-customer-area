<?php
/** Template version: 4.0.0
 *
 * -= 4.1.0 =-
 * - Reduced width for customer-dashboard page based on cuar/private-content/view/max-width-for-inner-pages
 *
 * -= 4.0.0 =-
 * - Updated markup for masonry compatibility
 * - Fixed main content height not properly calculated
 *
 * -= 3.3.0 =-
 * - Add cuar-content-styles class
 *
 * -= 3.2.0 =-
 * - Replace clearfix CSS classes with cuar-clearfix
 *
 * -= 3.1.0 =-
 * - Improve sidebar javascript UI - added some selectors
 *
 * -= 3.0.0 =-
 * - Improve UI for new master-skin
 *
 * -= 1.0.0 =-
 * - Initial version
 *
 */ ?>

<?php
$page_classes = array('cuar-page-' . $this->page_description['slug']);
if ($this->has_page_sidebar()) $page_classes[] = "cuar-page-with-sidebar";
else $page_classes[] = "cuar-page-without-sidebar";

$content_class = $this->page_description['slug'];
$content_class = $this->has_page_sidebar() ? $content_class . ' table-layout' : $content_class;

$cuar_content_max_width = $this->get_max_width_markup('page', $page_classes);
$cuar_content_max_width_dashboard = $this->page_description['slug'] == 'customer-dashboard'
    ? $this->get_max_width_markup('inner_page', ['cuar-page-' . $this->page_description['slug']]) : '';
?>

<div class="cuar-page cuar-clearfix <?php echo implode(' ', $page_classes); ?>">
    <div class="cuar-page-header"><?php
        $this->print_page_header($args, $shortcode_content);
        ?></div>

    <div id="cuar-js-content-cols-sizer" class="cuar-page-content <?php echo $content_class; ?>"<?php echo
    $cuar_content_max_width; ?>>
        <?php if ($this->has_page_sidebar()) { ?>
            <div id="cuar-js-page-content" class="cuar-page-content-main cuar-content-styles tray tray-center tray-center-on-left va-t
            cuar-clearfix">
                <div id="cuar-js-page-content-wrapper"<?php echo $cuar_content_max_width_dashboard; ?>>
                    <?php
                    $this->print_page_content($args, $shortcode_content);
                    ?>
                </div>
            </div>
            <aside id="cuar-js-tray" class="cuar-page-sidebar tray tray-right va-t cuar-clearfix">
                <div id="cuar-js-tray-scroller" class="tray-scroller">
                    <div id="cuar-js-tray-scroller-wrapper">
                        <?php $this->print_page_sidebar($args, $shortcode_content); ?>
                    </div>
                </div>
            </aside>
        <?php } else { ?>
            <div class="cuar-page-content-main cuar-content-styles cuar-clearfix"<?php echo $cuar_content_max_width_dashboard; ?>><?php
                $this->print_page_content($args, $shortcode_content);
                ?></div>
        <?php } ?>
    </div>

    <div id="cuar-js-mobile-sidebar"></div>

    <div class="cuar-page-footer cuar-clearfix"><?php
        $this->print_page_footer($args, $shortcode_content);
        ?></div>
</div>