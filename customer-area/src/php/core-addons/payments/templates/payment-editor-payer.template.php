<?php
/**
 * Template version: 3.2.0
 *
 * -= 3.2.0 =-
 * - Update address fields
 *
 * -= 3.1.0 =-
 * - Prevent Select2 assignment boxes to fail while initializing if the theme or a plugin is already loading Select2
 *
 * -= 3.0.0 =-
 * - Initial version
 */ ?>

<?php /** @var CUAR_Payment $payment */ ?>
<?php /** @var array $gateways  */ ?>

<?php
$user_id = $payment->get_user_id();
$selectable_users = get_users();
?>
<div class="cuar-payment-payer">
    <div class="row">
        <div class="form-group cuar-payer-user-id">
            <label for="payer_user_id" class="control-label"><?php _e('User', 'cuar'); ?></label>
            <select name="payer[user_id]" id="payer_user_id" data-placeholder="<?php esc_attr_e('Select user', 'cuar'); ?>">
                <?php foreach ($selectable_users as $u) : ?>
                    <option value="<?php echo $u->ID; ?>" <?php selected($user_id, $u->ID); ?>><?php echo $u->display_name; ?>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="form-group cuar-payer-user-ip">
            <label for="payer_user_ip" class="control-label"><?php _e('User IP', 'cuar'); ?></label>
            <div class="control-container">
                <input type="text" name="payer[user_ip]" id="payer_user_ip" value="<?php echo esc_attr($payment->get_user_ip()); ?>" class="form-control"/>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group cuar-payer-address">
            <label for="cuar_address" class="control-label"><?php _e('Address', 'cuar'); ?></label>
        </div>
    </div>
    <?php
    /** @var CUAR_AddressesAddOn $am */
    $am = cuar_addon('address-manager');
    $am->print_address_editor($payment->get_address(),
        'cuar_address', '',
        array(), '',
        'metabox',
        'payment-' . $payment->ID
    );
    ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $('#payer_user_id').cuarSelect2({
            width: "100%"
        });
    });
</script>
