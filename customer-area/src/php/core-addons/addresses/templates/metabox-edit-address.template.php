<?php /**
 * Template version: 4.0.0
 * Template zone: admin
 *
 * -= 4.0.0 =-
 * - Code cleanup
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
<?php /** @var $address_actions array */ ?>
<?php /** @var $extra_scripts string */ ?>
<?php /** @var $context string */ ?>
<?php /** @var $excluded_fields array */ ?>

<div class="cuar-address cuar-js-address cuar-<?php echo esc_attr($address_class); ?>"
	 data-address-id="<?php echo esc_attr($address_id); ?>" data-context="<?php echo esc_attr($context); ?>">
	<?php wp_nonce_field('cuar_' . $address_id . '_' . $context, 'cuar_nonce'); ?>

	<div class="cuar-progress cuar-js-progress" style="display: none;">
		<span class="indeterminate"></span>
	</div>

	<?php if (!empty($address_actions)) : ?>
		<div class="row">
			<div class="form-group cuar-js-address-field-container cuar-js-address-actions cuar-address-actions">
				<?php foreach ($address_actions as $address_action => $desc) : ?>
					<a href="#"
					   class="button cuar-action cuar-js-action cuar-js-<?php echo esc_attr($address_action); ?>"
					   title="<?php echo esc_attr($desc['tooltip']); ?>">
						<?php echo esc_html($desc['label']); ?>
					</a>&nbsp;
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="row">
		<?php if (!in_array('name', $excluded_fields, true)) : ?>
			<div class="form-group cuar-js-address-field-container cuar-js-address-name">
				<label for="<?php echo esc_attr($address_id); ?>_name" class="control-label">
					<?php esc_html_e('Name', 'cuar'); ?>
				</label>

				<div class="control-container">
					<input type="text"
						   name="<?php echo esc_attr($address_id); ?>[name]"
						   id="<?php echo esc_attr($address_id); ?>_name"
						   value="<?php echo esc_attr($address['name']); ?>"
						   placeholder="<?php esc_attr_e('Name', 'cuar'); ?>"
						   class="form-control cuar-js-address-field" />
				</div>
			</div>
		<?php endif; ?>
		<?php if (!in_array('company', $excluded_fields, true)) : ?>
			<div class="form-group cuar-js-address-field-container cuar-js-address-company">
				<label for="<?php echo esc_attr($address_id); ?>_company" class="control-label">
					<?php esc_html_e('Company', 'cuar'); ?>
				</label>

				<div class="control-container">
					<input type="text"
						   name="<?php echo esc_attr($address_id); ?>[company]"
						   id="<?php echo esc_attr($address_id); ?>_company"
						   value="<?php echo esc_attr($address['company']); ?>"
						   placeholder="<?php esc_attr_e('Company', 'cuar'); ?>"
						   class="form-control cuar-js-address-field" />
				</div>
			</div>
		<?php endif; ?>
	</div>
	<div class="row">
		<?php if (!in_array('vat_number', $excluded_fields, true)) : ?>
			<div class="form-group cuar-js-address-field-container cuar-js-address-vat-number">
				<label for="<?php echo esc_attr($address_id); ?>_vat_id" class="control-label">
					<?php esc_html_e('VAT Number', 'cuar'); ?>
				</label>

				<input type="text"
					   name="<?php echo esc_attr($address_id); ?>[vat_number]"
					   id="<?php echo esc_attr($address_id); ?>_vat_number"
					   value="<?php echo esc_attr($address['vat_number']); ?>"
					   placeholder="<?php esc_attr_e('VAT Number', 'cuar'); ?>"
					   class="form-control cuar-js-address-field" />
			</div>
		<?php endif; ?>
	</div>
	<div class="row">
		<?php if (!in_array('logo_url', $excluded_fields, true)) : ?>
			<div class="form-group cuar-js-address-field-container cuar-js-address-logo-url">
				<label for="<?php echo esc_attr($address_id); ?>_logo_url" class="control-label">
					<?php esc_html_e('Logo', 'cuar'); ?>
				</label>

				<div class="cuar-js-upload-control">
					<input type="text"
						   name="<?php echo esc_attr($address_id); ?>[logo_url]"
						   id="<?php echo esc_attr($address_id); ?>_logo_url"
						   value="<?php echo esc_attr($address['logo_url']); ?>"
						   placeholder="<?php esc_attr_e('Logo URL', 'cuar'); ?>"
						   class="regular-text cuar-js-address-field cuar-upload-input" />
					<span>
					&nbsp;
					<input type="button"
						   class="cuar-upload-button cuar-js-upload-button button-secondary"
						   value="<?php esc_attr_e('Upload Logo', 'cuar'); ?>" />
				</span>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<div class="row">
		<?php if (!in_array('line1', $excluded_fields, true)) : ?>
			<div class="form-group cuar-js-address-field-container cuar-js-address-line1">
				<label for="<?php echo esc_attr($address_id); ?>_line1" class="control-label">
					<?php esc_html_e('Street address', 'cuar'); ?>
				</label>

				<div class="control-container">
					<input type="text"
						   name="<?php echo esc_attr($address_id); ?>[line1]"
						   id="<?php echo esc_attr($address_id); ?>_line1"
						   value="<?php echo esc_attr($address['line1']); ?>"
						   placeholder="<?php esc_attr_e('Street address, line 1', 'cuar'); ?>"
						   class="form-control cuar-js-address-field" />
				</div>
			</div>
		<?php endif; ?>
	</div>
	<div class="row">
		<?php if (!in_array('line2', $excluded_fields, true)) : ?>
			<div class="form-group cuar-js-address-field-container cuar-js-address-line2">
				<label for="<?php echo esc_attr($address_id); ?>_line2" class="control-label"></label>
				<div class="control-container">
					<input type="text"
						   name="<?php echo esc_attr($address_id); ?>[line2]"
						   id="<?php echo esc_attr($address_id); ?>_line2"
						   value="<?php echo esc_attr($address['line2']); ?>"
						   placeholder="<?php esc_attr_e('Street address, line 2', 'cuar'); ?>"
						   class="form-control cuar-js-address-field" />
				</div>
			</div>
		<?php endif; ?>
	</div>
	<div class="row">
		<?php if (!in_array('zip', $excluded_fields, true)) : ?>
			<div class="form-group cuar-js-address-field-container cuar-js-address-zip">
				<label for="<?php echo esc_attr($address_id); ?>_zip" class="control-label">
					<?php esc_html_e('Zip/Postal code', 'cuar'); ?>
				</label>

				<div class="control-container">
					<input type="text"
						   name="<?php echo esc_attr($address_id); ?>[zip]"
						   id="<?php echo esc_attr($address_id); ?>_zip"
						   value="<?php echo esc_attr($address['zip']); ?>"
						   placeholder="<?php esc_attr_e('Zip/Postal code', 'cuar'); ?>"
						   class="form-control cuar-js-address-field" />
				</div>
			</div>
		<?php endif; ?>

		<?php if (!in_array('city', $excluded_fields, true)) : ?>
			<div class="form-group cuar-js-address-field-container cuar-js-address-city">
				<label for="<?php echo esc_attr($address_id); ?>_city" class="control-label">
					<?php esc_html_e('City', 'cuar'); ?>
				</label>

				<div class="control-container">
					<input type="text"
						   name="<?php echo esc_attr($address_id); ?>[city]"
						   id="<?php echo esc_attr($address_id); ?>_city"
						   value="<?php echo esc_attr($address['city']); ?>"
						   placeholder="<?php esc_attr_e('City', 'cuar'); ?>"
						   class="form-control cuar-js-address-field" />
				</div>
			</div>
		<?php endif; ?>
	</div>
	<div class="row cuar-js-country-state-inputs">
		<?php if (!in_array('country', $excluded_fields, true)) : ?>
			<div class="form-group cuar-js-address-field-container cuar-js-address-country">
				<label for="<?php echo esc_attr($address_id); ?>_country" class="control-label">
					<?php esc_html_e('Country', 'cuar'); ?>
				</label>

				<div class="control-container">
					<select name="<?php echo esc_attr($address_id); ?>[country]"
							id="<?php echo esc_attr($address_id); ?>_country"
							class="form-control cuar-js-address-field">
						<?php foreach (CUAR_CountryHelper::getCountries() as $code => $label) : ?>
							<option
								value="<?php echo esc_attr($code); ?>" <?php selected($address['country'], $code); ?>>
								<?php echo esc_html($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		<?php endif; ?>

		<?php if (!in_array('state', $excluded_fields, true)) : ?>
			<?php $country_states = CUAR_CountryHelper::getStates($address['country']); ?>
			<div
				class="form-group cuar-js-address-field-container cuar-js-address-state" <?php if (empty($country_states)) {
				echo 'style="display: none;"';
			} ?>>
				<label for="<?php echo esc_attr($address_id); ?>_state" class="control-label">
					<?php esc_html_e('State/Province', 'cuar'); ?>
				</label>

				<div class="control-container">
					<select name="<?php echo esc_attr($address_id); ?>[state]"
							id="<?php echo esc_attr($address_id); ?>_state"
							class="form-control cuar-js-address-field">
						<?php foreach ($country_states as $code => $label) : ?>
							<option value="<?php echo esc_attr($code); ?>" <?php selected($address['state'], $code); ?>>
								<?php echo esc_html($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function ($)
	{
		$('.cuar-<?php echo esc_js($address_class); ?>').addressManager();
		$(".cuar-<?php echo esc_js($address_class); ?> .cuar-js-upload-control").mediaInputControl();
		$('.cuar-<?php echo esc_js($address_class); ?> .cuar-js-country-state-inputs').bindCountryStateInputs();
		<?php echo $extra_scripts; ?>
	});
</script>
