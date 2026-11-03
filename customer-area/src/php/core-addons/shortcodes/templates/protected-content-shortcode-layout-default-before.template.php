<?php
/** Template version: 3.0.1
 *
 * -= 3.0.1 =-
 * - Escape class attributes and title (security fix)
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

<div class="panel top <?php echo esc_attr($type); ?>">
    <?php if ( !empty($title)): ?>
        <div class="panel-heading">
            <span class="panel-title">
                <?php echo wp_kses_post($title); ?>
            </span>
        </div>
    <?php endif; ?>
    <div class="panel-body pn">
        <table class="table table-hover table-striped">
            <tbody>