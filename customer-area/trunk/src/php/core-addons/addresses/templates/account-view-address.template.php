<?php /**
 * Template version: 5.0.0
 * Template zone: frontend
 *
 * -= 5.0.0 =-
 * - Code cleanup
 *
 * -= 4.0.0 =-
 * - Updated markup for masonry compatibility
 *
 * -= 3.0.0 =-
 * - Updated markup for new master-skin
 *
 * -= 1.0.0 =-
 * - Initial version
 */ ?>

<?php
/*  Copyright 2013 Foobar Studio (contact@foobar.studio)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/
?>

<?php /** @var $address array */ ?>
<?php /** @var $address_id string */ ?>
<?php /** @var $address_class string */ ?>
<?php /** @var $address_label string */ ?>
<?php /** @var $excluded_fields array */ ?>

<?php
/** @var CUAR_AddressesAddOn $ad_addon */
$ad_addon = cuar_addon('address-manager');
$user_addresses = $ad_addon->get_registered_user_addresses();
?>


<div class="cuar-js-msnry-item col-xs-12 col-sm-12 col-md-6 col-lg-3 col-xl-2 panel clearfix masonry-brick panel">
	<div class="panel-heading">
		<?php if (!empty($address_label)) : ?>
			<span class="panel-title"><?php echo esc_html($address_label); ?></span>
		<?php endif; ?>
	</div>
	<div class="panel-body">
		<?php if (CUAR_AddressHelper::compare_addresses(CUAR_AddressHelper::sanitize_address([]), $address)) : ?>
			<div class="cuar-address cuar-<?php echo esc_attr($address_class); ?>">
				<p><?php esc_html_e('No address yet', 'cuar'); ?></p>
			</div>
		<?php else: ?>
			<div class="cuar-address cuar-<?php echo esc_attr($address_class); ?>">
				<?php if (!empty($address['name']) || !empty($address['company'])) : ?>
					<p>
						<?php if (!empty($address['company'])) : ?>
							<strong><?php echo esc_html($address['company']); ?></strong><br>
						<?php endif; ?>

						<?php if (!empty($address['name'])) : ?>
							<strong><?php echo esc_html($address['name']); ?></strong>
						<?php endif; ?>

						<?php if (!empty($address['vat_number'])) : ?>
							<br><?php printf(esc_html__('VAT ID - %s', 'cuar'), esc_html($address['vat_number'])); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<p>
					<?php if (!empty($address['line1'])) : ?>
						<?php echo esc_html($address['line1']); ?><br>
					<?php endif; ?>
					<?php if (!empty($address['line2'])) : ?>
						<?php echo esc_html($address['line2']); ?><br>
					<?php endif; ?>
					<?php if (!empty($address['zip']) || !empty($address['city'])) : ?>
						<?php echo esc_html($address['zip']); ?>&nbsp;<?php echo esc_html($address['city']); ?><br>
					<?php endif; ?>
					<?php if (!empty($address['state']) || !empty($address['country'])) : ?>
						<?php if (!empty($address['state'])) : ?>
							<?php echo esc_html(CUAR_CountryHelper::getStateName($address['country'], $address['state'])); ?>,&nbsp;<?php endif; ?>
						<?php echo esc_html(CUAR_CountryHelper::getCountryName($address['country'])); ?>
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>
	</div>
</div>
