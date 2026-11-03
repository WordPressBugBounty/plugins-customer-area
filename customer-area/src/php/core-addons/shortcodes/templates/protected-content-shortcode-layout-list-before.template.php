<?php
/** Template version: 3.0.1
 *
 * -= 3.0.1 =-
 * - Escape the title (security fix)
 *
 * -= 3.0.0 =-
 * - Improve UI for new master-skin
 *
 * -= 1.0.0 =-
 * - Initial version
 *
 */ ?>

<?php /** @var string $title */ ?>
<?php /** @var string $type */ ?>

<div class="cuar-sc-list-container">
<?php if ( !empty($title)): ?>
    <div class="cuar-title">
        <?php echo wp_kses_post($title); ?>
    </div>
<?php endif; ?>
    <ul>
