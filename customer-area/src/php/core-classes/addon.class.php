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


if (!class_exists('CUAR_AddOn')) :

    /**
     * The base class for addons
     *
     * @author Vincent Prat @ Foobar Studio
     */
    abstract class CUAR_AddOn
    {
        private static $MISSING_LICENSE_MESSAGES_SHOWN = [];
        private static $HAS_NOTIFIED_INVALID_LICENSES = false;

        private static $OPTION_LICENSE_KEY = 'cuar_license_key_';
        private static $OPTION_LICENSE_CHECK = 'cuar_license_check_';
        private static $OPTION_LICENSE_STATUS = 'cuar_license_status_';

        /** @var string Id of the add-on */
        public $addon_id;

        /** @var array ID of the add-on on the new wpca store */
        public $store_item_id;

        /** @var string Name of the add-on on the legacy marvinlabs store */
        public $store_item_name;

        /** @var string the plugin file path relative to wp-plugins */
        public $plugin_file;

        /** @var string current version number for the addon */
        public $add_on_version;

        /** @var string min version of Customer Area */
        public $min_cuar_version;

        /** @var CUAR_Plugin The plugin instance */
        protected $plugin;

        /** @var CUAR_LicensingClient The licensing client */
        protected $licensing_client = null;

        /** @var boolean Does this addon have licensing? */
        public $is_licensing_enabled = false;

        public function __construct($addon_id = null)
        {
            $this->addon_id = $addon_id;

            add_action('cuar/core/settings/default-options', [&$this, 'set_default_options']);
            add_action('cuar/core/addons/before-init', [&$this, 'before_run'], 10);
            add_action('cuar/core/addons/init', [&$this, 'run'], 10);

            if (is_admin())
            {
                add_action('admin_init', [&$this, 'check_main_plugin_enabled'], 10);
                add_action('cuar/core/addons/after-init', [&$this, 'check_attention_needed'], 10);
            }
        }

        public abstract function get_addon_name();

        public function get_id()
        {
            return $this->addon_id;
        }

        /**
         * Function that starts the add-on
         *
         * @param CUAR_Plugin $cuar_plugin
         */
        public function before_run($cuar_plugin)
        {
            $this->plugin = $cuar_plugin;
            $cuar_plugin->register_addon($this);
        }

        /**
         * Function that starts the add-on
         *
         * @param CUAR_Plugin $cuar_plugin
         */
        public function run($cuar_plugin)
        {
            $this->run_addon($cuar_plugin);
        }

        /**
         * Addons should implement this method to do their initialisation
         *
         * @param CUAR_Plugin $cuar_plugin The plugin instance
         */
        public abstract function run_addon($cuar_plugin);

        /**
         * Add-ons can check if something needs to be fixed at this point of time
         */
        public function check_attention_needed()
        {
        }

        /**
         * Check that the main plugin is properly enabled. Else output a notice
         */
        public function check_main_plugin_enabled()
        {
            global $cuar_main_plugin_checked;
            if ($cuar_main_plugin_checked !== true && !is_plugin_active('customer-area/customer-area.php'))
            {
                $cuar_main_plugin_checked = true;
                add_action('admin_notices', [&$this, 'add_main_plugin_disabled_notice']);
            }
        }

        /**
         * Show a message to warn that the main plugin is either not installed or not activated
         */
        public function add_main_plugin_disabled_notice()
        {
            echo '<div class="error"><p>';
            echo __('<strong>Error: </strong>WP Customer Area add-ons are active but the main plugin is not installed!', 'cuar');
            echo '</p></div>';
        }

        public function set_default_options($defaults)
        {
            return $defaults;
        }

        /*------- LICENSING FUNCTIONS ---------------------------------------------------------------------------------*/

        public function enable_licensing($store_item_id, $store_item_name, $plugin_file, $add_on_version)
        {
            if (is_int($store_item_id) && is_admin())
            {
                $is_wpca_page = isset($_GET['page']) && substr($_GET['page'], 0, 4) === 'wpca';
                $is_wpca_posttype = isset($_GET['post_type']) && substr($_GET['post_type'], 0, 5) === 'cuar_';
                if ($is_wpca_page || $is_wpca_posttype)
                {
                    wp_die(
                        "The WP Customer Area add-on « {$this->get_addon_name()} » is outdated and not compatible with WP Customer Area 8 or greater. "
                        . 'You must either downgrade to <a href="' . cuar_site_url('/uploads/customer-area.7.10.6.zip', true) . '">WP Customer Area 7.10.6</a> or update your addons to the latest version.'
                        . '<br>'
                        . '<br>'
                        . 'If you plan to update, in order to be able to download the latest addons, please contact the WP Customer Area '
                        . 'support team via <a href="mailto:support@wp-customerarea.com">support@wp-customerarea.com</a> '
                        . 'providing the following information:'
                        . '<ul>'
                        . '<li>The email linked to your customer account</li>'
                        . '<li>The date of purchase</li>'
                        . '<li>The payment method used (Paypal or Credit card via Stripe)</li>'
                        . '</ul>'
                    );
                }
            }

            $this->plugin->tag_addon_as_commercial($this->addon_id);

            $this->is_licensing_enabled = true;
            $this->store_item_id = is_array($store_item_id) ? $store_item_id : [$store_item_id];
            $this->store_item_name = $store_item_name;
            $this->plugin_file = $plugin_file;
            $this->add_on_version = $add_on_version;

            $this->licensing_client = new CUAR_LicensingClient(
                $this->plugin,
                $this,
                $this->plugin_file,
                $this->add_on_version,
                $this->store_item_name
            );

            add_action('admin_init', [$this, 'show_invalid_license_admin_notice'], 10);
            add_action('admin_init', [$this, 'show_invalid_openssl_version_notice'], 10);
            add_filter('plugin_row_meta', [$this, 'plugin_row_links'], 10, 4);

            $plugin_name = plugin_basename($plugin_file);
            add_filter("after_plugin_row_$plugin_name", [$this, 'plugin_row_addon_needs_activation'], 10, 3);

            // Check that license is valid once per week
            add_action('cuar/cron/events?schedule=weekly', [$this, 'do_periodical_license_check']);

            // For testing scheduled license checks, uncomment this line to force checks on every page load
            // add_action('admin_init', array($this, 'do_periodical_license_check'), 5);
        }

        public function get_license_types()
        {
            $out = [0 => __('Select product ID', 'cuar')];

            foreach ($this->store_item_id as $key => $product_id)
            {
                $label = '';
                switch ($key)
                {
                    case 'per-en' :
                        $label = __('Personal', 'cuar');
                        break;
                    case 'per-fr' :
                        $label = __('Personal (FR)', 'cuar');
                        break;
                    case 'pro-en' :
                        $label = __('Professional', 'cuar');
                        break;
                    case 'pro-fr' :
                        $label = __('Professional (FR)', 'cuar');
                        break;
                    case 'dev-en' :
                        $label = __('Developer', 'cuar');
                        break;
                    case 'dev-fr' :
                        $label = __('Developer (FR)', 'cuar');
                        break;
                    case 'uni-en' :
                        $label = __('Universal', 'cuar');
                        break;
                    case 'uni-fr' :
                        $label = __('Universal (FR)', 'cuar');
                        break;
                }

                if (!empty($label))
                {
                    $out[$product_id] = "$product_id - $label";
                }
            }

            return $out;
        }

        public function get_licensing_client()
        {
            return $this->licensing_client;
        }

        /**
         * Displays message inline on plugin row that the license key is missing
         */
        public function plugin_row_links($links_array, $plugin_file_name, $plugin_data, $status)
        {
            if (strpos($this->plugin_file, $plugin_file_name))
            {
                if ($this->licensing_client === null)
                {
                    return;
                }

                // You can still use `array_unshift()` to add links at the beginning.
                $links_array[] = sprintf(
                    __('<a href="%s">Documentation</a>', 'cuar'),
					cuar_site_url('/documentation/introduction')
                );
                $links_array[] = sprintf(
                    __('<a href="%s">Support</a>', 'cuar'),
					cuar_site_url('/support')
                );

                $status = $this->licensing_client->get_api_key_status();
                if ($status !== true)
                {
                    $links_array[] = sprintf(
                        __('<a href="%s">Activate license</a>', 'cuar'),
                        admin_url('options-general.php?page=wpca-settings&tab=cuar_licenses')
                    );
                }
            }

            return $links_array;
        }

        /**
         * Displays message inline on plugin row that the license key is missing
         */
        public function plugin_row_addon_needs_activation($plugin_file_name, $plugin_data, $status)
        {
            if (strpos($this->plugin_file, $plugin_file_name))
            {
                if ($this->licensing_client === null)
                {
                    return;
                }

                $status = $this->licensing_client->get_api_key_status();
                if ($status !== true)
                {
                    echo '<tr class="plugin-update-tr active" data-plugin="' . $plugin_file_name . '">';
                    echo '<td colspan="4" class="plugin-update colspanchange">';
                    echo '<div class="notice inline notice-warning notice-alt"><p>';
                    echo sprintf(
                        __('You have not activated your license or the one entered is not valid. Please <a href="%s">activate your license</a>.',
                            'cuar'),
                        admin_url('options-general.php?page=wpca-settings&tab=cuar_licenses')
                    );
                    echo '</p></div></td></tr>';
                }
            }
        }

        public function show_invalid_openssl_version_notice()
        {
            // Bail if doing ajax
            if (defined('DOING_AJAX') && DOING_AJAX)
            {
                return;
            }

            // Do not show on anything but the licenses settings page
            if (!(isset($_GET['page'])
                  && strcmp($_GET['page'], 'wpca-settings') === 0
                  && isset($_GET['tab'])
                  && strcmp($_GET['tab'], 'cuar_licenses') === 0)
            )
            {
                return;
            }

            $version = $this->get_openssl_version();
            if (version_compare($version, '1.1.0') < 0)
            {
                add_action('admin_notices', [&$this, 'print_invalid_openssl_version_notice'], 10);
            }
        }

        public function print_invalid_openssl_version_notice()
        {
            $version = $this->get_openssl_version();

            echo '<div class="error"><p>';
            if ($version === '0.0.0')
            {
                echo __(
                    'The version of the cURL PHP extension could not be fetched, you may not be able to validate your licenses. Please contact your server administrator.',
                    'cuar'
                );
            }
            else
            {
                echo sprintf(
                    __(
                        'Your cURL PHP extension is using an outdated OpenSSL version (%1$s). That could prevent our licenses from being validated. Please contact your server administrator to update the cURL PHP extension.',
                        'cuar'
                    ),
                    $version
                );
            }
            echo '</p></div>';
        }

        private function get_openssl_version()
        {
            if (!function_exists('curl_version') || false === curl_version())
            {
                return '0.0.0';
            }

            $version = curl_version();
            return preg_replace('/[^\d.]/', '', $version['ssl_version']);
        }

        /**
         * Admin notices for errors
         *
         * @access  public
         * @return  void
         */
        public function show_invalid_license_admin_notice()
        {
            // Do not show notification twice
            if (self::$HAS_NOTIFIED_INVALID_LICENSES)
            {
                return;
            }

            // Bail if doing ajax
            if (defined('DOING_AJAX') && DOING_AJAX)
            {
                return;
            }

            // Do not show on licenses settings page
            if (isset($_GET['page'])
                && strcmp($_GET['page'], 'wpca-settings') == 0
                && isset($_GET['tab'])
                && strcmp($_GET['tab'], 'cuar_licenses') == 0
            )
            {
                return;
            }

            // Do not show on setup wizard settings page
            if (isset($_GET['page'])
                && (strcmp($_GET['page'], 'wpca-setup') == 0
                    || strcmp($_GET['page'], 'wpca') == 0)
            )
            {
                return;
            }

            // Only show to the site administrator
            if (!current_user_can('manage_options'))
            {
                return;
            }

            // Ignore non-commercial addons
            if (!$this->is_licensing_enabled)
            {
                return;
            }

            $status = $this->licensing_client->get_api_key_status();
            if ($status !== true)
            {
                add_action('admin_notices', [&$this, 'print_invalid_license_admin_notice'], 10);
                self::$HAS_NOTIFIED_INVALID_LICENSES = true;
            }
        }

        /**
         * Print admin notices for errors
         *
         * @access  public
         *
         * @return void
         */
        public function print_invalid_license_admin_notice()
        {
            echo '<div class="error"><p>';
            echo sprintf(
                __('You have invalid or expired license keys for WP Customer Area. Please go to the <a href="%s">Licenses page</a> to correct this issue.', 'cuar'),
                admin_url('options-general.php?page=wpca-settings&tab=cuar_licenses')
            );
            echo '</p></div>';
        }

        /**
         * Check if license key is valid
         */
        public function do_periodical_license_check()
        {
            // Don't fire when saving settings
            if (!empty($_POST['cuar_do_save_settings']))
            {
                return;
            }

            // Bail if doing ajax
            if (defined('DOING_AJAX') && DOING_AJAX)
            {
                return;
            }

            // Bail if not commercial
            if ($this->licensing_client === null)
            {
                return;
            }

            $this->licensing_client->check_license();
        }
    }

    /**
     * @param CUAR_AddOn $a
     * @param CUAR_AddOn $b
     *
     * @return int
     */
    function cuar_sort_addons_by_name_callback($a, $b)
    {
        return strcmp($a->get_addon_name(), $b->get_addon_name());
    }

endif; // CUAR_AddOn
