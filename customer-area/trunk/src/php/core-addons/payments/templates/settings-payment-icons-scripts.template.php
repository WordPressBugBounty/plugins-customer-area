<?php /** Template version: 3.1.0
 *
 * -= 3.1.0 =-
 * - Fix some jQuery deprecated functions
 *
 * -= 3.0.0 =-
 * - Initial version
 *
 */
?>

<?php /** @var array $gateways */ ?>
<?php /** @var CUAR_Settings $settings */ ?>
<?php /** @var CUAR_PaymentGateway $gateway */ ?>

<script>
    function refreshDisplayedIconPack($, iconPackSelector) {
        var packId = iconPackSelector.val();

        // Hide all settings related to PDF templates
        $('.cuar-payment-icon-setting').hide();

        // Show settings related to the selected PDF template
        $('.cuar-payment-icon-setting-' + packId).show();

        refreshSelectedIcons($, $('.cuar-payment-icon-setting-' + packId + ' select'));
    }

    function refreshSelectedIcons($, iconSelector) {
        var imgContainer = iconSelector.siblings('.description');

        iconSelector.children('option').each(function () {
            var img = imgContainer.children('img[data-id=' + $(this).val() + ']');

            if ($(this).is(':selected')) img.css('opacity', '1');
            else img.css('opacity', '0.25');
        });
    }

    jQuery(document).ready(function ($) {
        var iconPackID = '#cuar_enabled_payment_icon_pack;
        var iconPackSelector = $(iconPackID);
        $(document).on('change', iconPackID, function () {
            refreshDisplayedIconPack($, $(this));
        });

        refreshDisplayedIconPack($, iconPackSelector);

        $(document).on('change', '.cuar-payment-icon-setting select', function () {
            refreshSelectedIcons($, $(this));
        });
    });
</script>