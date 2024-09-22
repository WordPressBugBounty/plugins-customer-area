<?php

// Based on WC_AM_Client_2_8
class CUAR_LicensingClient
{
    private static $inactive_notice_license_messages_shown = [];

    /** @var CUAR_Plugin */
    protected $plugin;

    /** @var CUAR_AddOn */
    protected $addon;

    protected $data_key = '';
    protected $file = '';
    protected $plugin_name = '';
    protected $product_id = '';
    protected $slug = '';
    protected $software_title = '';
    protected $software_version = '';
    protected $domain = '';
    protected $identifier = '';

    public function __construct($cuar_plugin, $addon, $file, $software_version, $software_title)
    {
        $this->plugin = $cuar_plugin;
        $this->addon = $addon;

        $this->identifier = dirname(untrailingslashit(plugin_basename($file)));
        $this->product_id = $this->get_option('product_id');

        $this->file = $file;
        $this->software_title = esc_attr($software_title);
        $this->software_version = esc_attr($software_version);
        $this->plugin_name = untrailingslashit(plugin_basename($this->file));

        // Slug should be the same as the plugin/theme directory name
        $this->slug = strpos($this->plugin_name, '.php') !== 0
            ? dirname($this->plugin_name)
            : $this->plugin_name;

        if (is_admin())
        {
            register_activation_hook($this->file, [&$this, 'activation']);

            // Check for external connection blocking
            add_action('admin_notices', [$this, 'check_external_blocking']);

            // Check for software updates
            $this->setup_update_check_hooks();
            if ($this->get_option('status') !== 'Activated' && current_user_can('manage_options'))
            {
                $this->add_admin_inactive_notice();
            }

            // Makes auto updates available if WP >= 5.5.
            $this->setup_automatic_update_hooks();
            add_filter('plugin_auto_update_setting_html', [$this, 'auto_update_message'], 10, 3);
        }
    }

    protected function get_api_url()
    {
        return cuar_site_url('/');
    }

    //region Settings accessors

    public function get_domain()
    {
        return str_ireplace(['http://', 'https://'], '', home_url());
    }

    public function instance_exists()
    {
        $instance = $this->get_option('instance');
        return $instance !== null && !empty($instance);
    }

    public function get_instance()
    {
        $instance = $this->get_option('instance');
        if ($instance === null || empty($instance))
        {
            $instance = wp_generate_password(18, false);
            $this->update_option('instance', $instance);
        }

        return $instance;
    }

    public function get_option_key($option_id)
    {
        $product_id = strtolower(str_ireplace([' ', '_', '&', '?', '-'], '_', $this->identifier));
        return "licensing_{$product_id}_{$option_id}";
    }

    public function get_option($option_id)
    {
        return $this->plugin->get_option($this->get_option_key($option_id));
    }

    public function update_option($option_id, $value)
    {
        $this->plugin->update_option($this->get_option_key($option_id), $value);
    }

    public function get_api_key_status($live = false)
    {
        if ($live)
        {
            $license_status = $this->status();
            return !empty($license_status)
                   && !empty($license_status->data->activated)
                   && $license_status->data->activated;
        }

        return $this->get_option('status') === 'Activated';
    }

    //endregion

    //region WP Hooks

    /**
     *  Tries auto updates.
     */
    public function setup_automatic_update_hooks()
    {
        global $wp_version;

        if (version_compare($wp_version, '5.5', '>='))
        {
            add_filter('auto_update_plugin', [&$this, 'maybe_auto_update'], 10, 2);
        }
    }

    /**
     * Check for software updates.
     */
    public function setup_update_check_hooks()
    {
        add_filter('pre_set_site_transient_update_plugins', [&$this, 'update_check']);

        // Check For Plugin Information to display on the update details page
        add_filter('plugins_api', [&$this, 'information_request'], 10, 3);
    }

    /**
     * Check for external blocking constant.
     */
    public function check_external_blocking()
    {
        // show notice if external requests are blocked through the WP_HTTP_BLOCK_EXTERNAL constant
        if (defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL === true)
        {
            // check if our API endpoint is in the allowed hosts
            $host = parse_url($this->get_api_url(), PHP_URL_HOST);

            if (!defined('WP_ACCESSIBLE_HOSTS') || stripos(WP_ACCESSIBLE_HOSTS, $host) === false)
            {
                $this->plugin->add_admin_notice(
                    sprintf(__(
                        "<b>Warning!</b> You're blocking external requests which means you won't be able to get %s updates. Please add %s to %s.", 'cuar'),
                        $this->software_title,
                        "<strong>{$host}</strong>",
                        '<code>WP_ACCESSIBLE_HOSTS</code>'
                    )
                );
            }
        }
    }

    /**
     * Generate the default data on plugin activation
     */
    public function activation()
    {
        if (!$this->instance_exists())
        {
            $this->get_instance();
        }

        $this->update_option('status', 'Deactivated');
    }

    //endregion

    //region Admin notices

    public function add_admin_inactive_notice()
    {
        if (in_array($this->plugin, self::$inactive_notice_license_messages_shown, true))
        {
            return;
        }

        self::$inactive_notice_license_messages_shown[] = $this->plugin;

        $this->plugin->add_admin_notice(
            sprintf(
                __("The <strong>WP Customer Area - %s</strong> add-on licence has not been activated! Visit the %slicenses settings page%s to activate it.", 'cuar'),
                esc_attr($this->software_title),
                '<a href="' . esc_url(admin_url('options-general.php?page=wpca-settings&tab=cuar_licenses')) . '">',
                '</a>',
                esc_attr($this->software_title)
            )
        );
    }

    //endregion

    //region API access

    protected function build_error_response($message)
    {
        return (object)[
            'success' => false,
            'error' => $message,
            'data' => ['error' => $message,],
        ];
    }

    protected function send_query($args)
    {
        $target_url = esc_url_raw(add_query_arg('wc-api', 'wc-am-api', $this->get_api_url()) . '&' . http_build_query($args));
        $request = wp_safe_remote_post($target_url, ['timeout' => 15]);

        if (is_wp_error($request) || wp_remote_retrieve_response_code($request) != 200)
        {
            return false;
        }

        $response = wp_remote_retrieve_body($request);

        return !empty($response) ? $response : false;
    }

    public function update_check($transient)
    {
        if (empty($transient->checked))
        {
            return $transient;
        }

        $apiKey = $this->get_option('api_key');
        $args = [
            'wc_am_action' => 'update',
            'slug' => $this->slug,
            'plugin_name' => $this->plugin_name,
            'version' => $this->software_version,
            'product_id' => $this->product_id,
            'api_key' => !empty($apiKey) ? $apiKey : '',
            'instance' => $this->get_option('instance'),
        ];

        if (empty($args['product_id']) || empty($args['api_key']))
        {
            return $transient;
        }

        // Check for a plugin update
        $response = json_decode($this->send_query($args), true);

        // Displays an admin error message in the WordPress dashboard
        //$this->check_response_for_errors( $response );
        if (isset($response['data']['error_code']))
        {
            $this->plugin->add_admin_notice($response['data']['error']);
//            add_settings_error('wc_am_client_error_text', 'wc_am_client_error', "{$response['data']['error']}", 'error');
        }

        if ($response && is_array($response) && $response['success'] === true)
        {
            // New plugin version from the API
            $new_version = (string)$response['data']['package']['new_version'];

            // Current installed plugin version
            $current_version = (string)$this->software_version;

            $package = [
                'id' => $response['data']['package']['id'],
                'slug' => $response['data']['package']['slug'],
                'plugin' => $response['data']['package']['plugin'],
                'new_version' => $response['data']['package']['new_version'],
                'url' => $response['data']['package']['url'],
                'tested' => $response['data']['package']['tested'],
                'package' => $response['data']['package']['package'],
                'upgrade_notice' => $response['data']['package']['upgrade_notice'],
            ];

            if (isset($new_version, $current_version) && version_compare($new_version, $current_version, '>'))
            {
                $transient->response[$this->plugin_name] = (object)$package;
                unset($transient->no_update[$this->plugin_name]);
            }
        }

        return $transient;
    }

    public function information_request($result, $action, $args)
    {
        // Check if this plugins API is about this plugin
        if (
            (isset($args->slug) && $args->slug != $this->slug)
            || !isset($args->slug)
        )
        {
            return $result;
        }

        $apiKey = $this->get_option('api_key');
        $args = [
            'wc_am_action' => 'plugininformation',
            'plugin_name' => $this->plugin_name,
            'version' => $this->software_version,
            'product_id' => $this->product_id,
            'api_key' => !empty($apiKey) ? $apiKey : '',
            'instance' => $this->get_instance(),
            'object' => $this->get_domain(),
        ];

        if (empty($args['product_id']) || empty($args['api_key']))
        {
            return $result;
        }

        $response = json_decode($this->send_query($args), false);
        if (isset($response) && is_object($response) && $response !== false)
        {
            return $response;
        }

        return $result;
    }

    /**
     * Sends the status check request to the API Manager.
     */
    public function status($product_id = '', $api_key = '')
    {
        $api_key = empty($api_key) ? $this->get_option('api_key') : $api_key;
        $product_id = empty($product_id) ? $this->product_id : $product_id;

        if (empty($product_id) || empty($api_key))
        {
            return $this->build_error_response(
                'The product ID or the API key are empty. You must supply both in order to check the license status'
            );
        }

        $args = [
            'wc_am_action' => 'status',
            'api_key' => $api_key,
            'product_id' => $product_id,
            'instance' => $this->get_instance(),
            'object' => $this->get_domain(),
        ];

        $response = json_decode($this->send_query($args), false);
        if (isset($response) && is_object($response) && $response !== false)
        {
            return $response;
        }

        return $this->build_error_response('Unknown error while checking the license status');
    }

    /**
     * Sends the status check request to the API Manager.
     */
    public function activate($product_id, $api_key)
    {
        if (empty($product_id) || empty($api_key))
        {
            return $this->build_error_response(
                'The product ID or the API key are empty. You must supply both in order to activate the license'
            );
        }

        $args = [
            'wc_am_action' => 'activate',
            'api_key' => $api_key,
            'product_id' => $product_id,
            'instance' => $this->get_instance(),
            'object' => $this->get_domain(),
            'software_version' => $this->software_version,
        ];

        $response = json_decode($this->send_query($args), false);
        if (isset($response) && is_object($response) && $response !== false)
        {
            if ($response->code === "100"
                && strpos($response->error, 'The API Key has already been activated') !== false)
            {
                $deactivation = $this->deactivate($product_id, $api_key);
                if (isset($deactivation) && is_object($deactivation) && $deactivation !== false && $deactivation->success)
                {
                    $response = json_decode($this->send_query($args), false);
                    if (isset($response) && is_object($response) && $response !== false)
                    {
                        return $response;
                    }
                }
            }
            return $response;
        }

        return $this->build_error_response('Unknown error while activating the license');
    }

    /**
     * Sends the status check request to the API Manager.
     */
    public function deactivate($product_id, $api_key)
    {
        if (empty($product_id) || empty($api_key))
        {
            return $this->build_error_response(
                'The product ID or the API key are empty. You must supply both in order to deactivate the license'
            );
        }

        $args = [
            'wc_am_action' => 'deactivate',
            'api_key' => $api_key,
            'product_id' => $product_id,
            'instance' => $this->get_instance(),
            'object' => $this->get_domain(),
            'software_version' => $this->software_version,
        ];

        $response = json_decode($this->send_query($args), false);
        if (isset($response) && is_object($response) && $response !== false)
        {
            return $response;
        }

        return $this->build_error_response('Unknown error while deactivating the license');
    }

    //endregion

    //region Actions that can be called to activate or deactivate licenses

    public function check_license()
    {
        $status = $this->status();

        $this->update_option('last_validation_result', $status);
        $this->update_option('last_check', (new DateTime())->format('Y-m-d'));

        if (isset($status->success) && $status->success === true)
        {
            $this->update_option('status', 'Activated');
        }
        else
        {
            $this->update_option('status', 'Deactivated');
        }

        return $status;
    }

    public function activate_license($product_id, $api_key)
    {
        $current_product_id = $this->get_option('product_id');
        $current_api_key = $this->get_option('api_key');

        if ($current_product_id
            && $current_api_key
            && $product_id !== $current_product_id
            && $api_key !== $current_api_key)
        {
            $this->deactivate($current_product_id, $current_api_key);
        }

        $status = $this->activate($product_id, $api_key);

        $this->update_option('product_id', $product_id);
        $this->update_option('api_key', $api_key);
        $this->update_option('last_validation_result', $status);
        $this->update_option('last_check', (new DateTime())->format('Y-m-d'));

        if (isset($status->success) && $status->success === true && !empty($api_key))
        {
            $status->message = __('Your license is active !', 'cuar') . ' ' . $status->message;
            $this->update_option('status', 'Activated');
        }
        else
        {
            $this->update_option('status', 'Deactivated');
        }

        return $status;
    }

    //endregion

    //region Autoupdate

    public function maybe_auto_update($update, $item)
    {
        if (isset($item->slug) && $item->slug == $this->slug)
        {
            if ($this->is_auto_update_disabled())
            {
                return false;
            }

            if (!$this->get_api_key_status() || !$this->get_api_key_status(true))
            {
                return false;
            }

            return true;
        }

        return $update;
    }

    public function auto_update_message($html, $plugin_file, $plugin_data)
    {
        if ($this->plugin_name == $plugin_file)
        {
            global $status, $page;

			$keyStatus = $this->get_option('status');
            if ($keyStatus !== 'Activated')
            {
				if ($keyStatus === 'Deactivated')
				{
					return esc_html__( 'Update your license.', 'cuar' );
				}

				return esc_html__('Auto-updates unavailable.', 'cuar');
            }

            $auto_updates = (array)get_site_option('auto_update_plugins', []);
            $html = [];

            if (isset($plugin_data['auto-update-forced']))
            {
                if ($plugin_data['auto-update-forced'])
                {
                    // Forced on.
                    $text = __('Auto-updates enabled', 'cuar');
                }
                else
                {
                    $text = __('Auto-updates disabled', 'cuar');
                }

                $action = 'unavailable';
                $time_class = ' hidden';
            }
            else if (in_array($plugin_file, $auto_updates, true))
            {
                $text = __('Disable auto-updates', 'cuar');
                $action = 'disable';
                $time_class = '';
            }
            else
            {
                $text = __('Enable auto-updates', 'cuar');
                $action = 'enable';
                $time_class = ' hidden';
            }

            $query_args = [
                'action' => "{$action}-auto-update",
                'plugin' => $plugin_file,
                'paged' => $page,
                'plugin_status' => $status,
            ];

            $url = add_query_arg($query_args, 'plugins.php');

            if ('unavailable' === $action)
            {
                $html[] = '<span class="label">' . $text . '</span>';
            }
            else
            {
                $html[] = sprintf('<a href="%s" class="toggle-auto-update aria-button-if-js" data-wp-action="%s">', wp_nonce_url($url, 'updates'), $action);
                $html[] = '<span class="dashicons dashicons-update spin hidden" aria-hidden="true"></span>';
                $html[] = '<span class="label">' . $text . '</span>';
                $html[] = '</a>';
            }

            if (!empty($plugin_data['update']))
            {
                $html[] = sprintf('<div class="auto-update-time%s">%s</div>', $time_class, wp_get_auto_update_message());
            }

            $html = implode('', $html);
        }

        return $html;
    }

    public function is_auto_update_disabled()
    {
        /*
         * WordPress will not offer to update if background updates are disabled.
         * WordPress background updates are disabled if file changes are not allowed.
         */
        if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)
        {
            return true;
        }

        if (defined('WP_INSTALLING'))
        {
            return true;
        }

        $wp_updates_disabled = defined('AUTOMATIC_UPDATER_DISABLED') && AUTOMATIC_UPDATER_DISABLED;
        $wp_updates_disabled = apply_filters('automatic_updater_disabled', $wp_updates_disabled);
        if ($wp_updates_disabled)
        {
            return true;
        }

        return false;
    }

    //endregion
}
