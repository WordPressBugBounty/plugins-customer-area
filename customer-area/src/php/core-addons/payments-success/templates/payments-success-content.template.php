<?php /** Template version: 3.0.1
 *
 * -= 3.0.1 =-
 * - Filter the gateway message (security hardening)
 *
 * -= 3.0.0 =-
 * - Initial version
 */ ?>

<?php
$message = cuar_get_gateway_message();
?>

<div class="alert alert-success alert-border-left mb-lg">
    <h3 class="mt-xs mb-lg"><?php _e('Thank you', 'cuar') ?></h3>
    <p class="mb-lg"><?php _e('Your payment has been recorded.', 'cuar') ?></p>
    <?php if (!empty($message)): ?>
        <p><?php echo wp_kses_post($message); ?></p>
    <?php endif; ?>
</div>
