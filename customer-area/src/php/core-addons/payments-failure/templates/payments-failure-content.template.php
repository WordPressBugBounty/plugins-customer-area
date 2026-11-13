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

<div class="alert alert-danger alert-border-left mb-lg">
    <h3 class="mt-xs mb-lg"><?php _e('Sorry!', 'cuar') ?></h3>
    <p><?php _e('There has been a problem when recording your payment.', 'cuar') ?></p>
    <?php if (!empty($message)): ?>
        <p><?php echo wp_kses_post($message); ?></p>
    <?php endif; ?>
</div>
