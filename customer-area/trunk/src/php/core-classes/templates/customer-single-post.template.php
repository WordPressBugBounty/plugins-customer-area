<?php
/** Template version: 1.3.0
 *
 * -= 1.3.0 =-
 * - Enhanced compatibility with components from Elementor included into private contents
 *
 * -= 1.2.0 =-
 * - Compatibility with new option Show single private posts titles
 *
 * -= 1.1.0 =-
 * - Hide single posts bordered box if content description is empty
 *
 * -= 1.0.0 =-
 * - Add masonry layout to single-posts
 * - Add new filter cuar/private-content/view/max-width for private-contents
 * - Add new filter cuar/private-content/view/show-post-title to be able to display post titles
 *
 */
?>

<?php
/** @var $before */
/** @var $content */
/** @var $after */

global $content_width;
wp_enqueue_script('jquery-masonry');

$cols = '';
$after_check = trim(preg_replace('/<!--(.|\s)*?-->/', '', $after));

if (!empty($after_check)) {
    $cols_content = ' col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xl-8';
    $cols_sizer = ' col-xs-1';
} else {
    $cols_content = ' col-xs-12';
    $cols_sizer = ' col-xs-1';
}

$cuar_content_max_width = $this->get_max_width_markup();
$cuar_print_content_title = $this->is_show_post_titles_enabled();

$content_styles = ' cuar-content-styles';
if(class_exists('Elementor\Plugin'))
{
	$document = Elementor\Plugin::$instance->documents->get( get_the_ID() );
	if ($document && $document->is_built_with_elementor())
	{
		$content_styles = ' cuar-elementor-content-styles';
	}
}
?>

<?php echo $before; ?>

<div id="cuar-js-content-cols-sizer" class="cuar-single-post-content-wrapper cuar-js-msnry clearfix"<?php echo
$cuar_content_max_width; ?>>
    <div class="cuar-js-msnry-sizer<?php echo $cols_sizer; ?>"></div>
    <div class="cuar-single-post-content cuar-js-msnry-item<?php echo $cols_content; ?> clearfix">
        <div class="cuar-single-entry clearfix<?php echo $content_styles; ?>"><?php
            //Note: Do not add linebreaks into this div in order to let .cuar-content-styles:empty selector work
            if ($cuar_print_content_title) { ?>
                <h2 class="cuar-single-entry-title br-b">
                    <?php the_title(); ?>
                </h2>
            <?php } ?><?php echo $content; ?></div>
    </div>
    <?php echo $after; ?>
</div>
