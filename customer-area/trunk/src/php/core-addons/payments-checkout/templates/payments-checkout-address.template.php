<?php
/**
 * Template version: 3.1.0
 *
 * -= 3.1.0 =-
 * - Update address fields
 *
 * -= 3.0.0 =-
 * - Initial version
 *
 */ ?>

<?php /** @var CUAR_AddressesAddOn $ad_addon */ ?>
<?php /** @var array $address */ ?>
<?php /** @var bool $is_address_editable */ ?>

<?php do_action('cuar/core/payments/templates/checkout/before-address'); ?>

<div class="panel">
    <div class="panel-heading">
        <span class="panel-title"><?php _e('Billing information', 'cuar'); ?></span>
    </div>
    <div class="panel-body">
        <?php if ($is_address_editable) : ?>
            <!-- Not supported currently -->
            <?php /* $ad_addon->print_address_editor($address, 'cuar_address', '', array(), '', 'checkout', 'payment-' . $payment->ID); */ ?>
       <?php else: ?>
            <?php echo CUAR_AddressHelper::get_address_as_hidden_input($address, 'cuar_address'); ?>
            <?php $ad_addon->print_address($address, 'cuar_address', '', 'checkout'); ?>
        <?php endif; ?>
    </div>
</div>

<?php do_action('cuar/core/payments/templates/checkout/after-address'); ?>
