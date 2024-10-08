<?php
/*
 * Copyright 2013 Foobar Studio (contact@foobar.studio) This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
require_once(CUAR_INCLUDES_DIR . '/helpers/wordpress-helper.class.php');

if (!class_exists('CUAR_Settings')) :

    /**
     * Creates the UI to change the plugin settings in the admin area.
     * Also used to access the plugin settings
     * stored in the DB (@see CUAR_Plugin::get_option)
     */
    class CUAR_Settings
    {
        public function __construct($plugin)
        {
            $this->plugin = $plugin;
            $this->setup();
            $this->reload_options();
        }

        /**
         * Get the value of a particular plugin option
         *
         * @param string $option_id
         *            the ID of the option to get
         *
         * @return mixed the value
         */
        public function get_option($option_id)
        {
            return isset($this->options [$option_id]) ? $this->options [$option_id] : null;
        }

        /**
         * Setup the WordPress hooks we need
         */
        public function setup()
        {
            add_action('cuar/core/admin/submenu-items?group=tools', [&$this, 'add_menu_items'], 10);

            if (is_admin())
            {
                add_action('admin_init', [&$this, 'page_init']);
                add_action('cuar/core/admin/print-admin-page?page=settings', [&$this, 'print_settings_page'], 99);

                // Links under the plugin name
                $plugin_file = 'customer-area/customer-area.php';
                add_filter("plugin_action_links_{$plugin_file}", [&$this, 'print_plugin_action_links'], 10, 2);

                // We have some core settings to take care of too
                add_filter('cuar/core/settings/settings-tabs', [&$this, 'add_core_settings_tab'], 200, 1);
                add_filter('cuar/core/settings/settings-tabs', [&$this, 'add_licensing_settings_tab'], 900, 1);

                add_action('cuar/core/settings/print-settings?tab=cuar_core',
                    [&$this, 'print_core_settings'], 10, 2);
                add_filter('cuar/core/settings/validate-settings?tab=cuar_core',
                    [&$this, 'validate_core_settings'], 10, 3);

                add_action('cuar/core/settings/print-settings?tab=cuar_frontend',
                    [&$this, 'print_frontend_settings'], 10, 2);
                add_filter('cuar/core/settings/validate-settings?tab=cuar_frontend',
                    [&$this, 'validate_frontend_settings'], 10, 3);

                add_action('cuar/core/settings/print-settings?tab=cuar_licenses',
                    [&$this, 'print_license_settings'], 10, 2);
                add_filter('cuar/core/settings/validate-settings?tab=cuar_licenses',
                    [&$this, 'validate_license_options'], 10, 3);

                add_action('wp_ajax_cuar_validate_license', ['CUAR_Settings', 'ajax_validate_license']);
            }
        }

        public function flush_rewrite_rules()
        {
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
        }

        /**
         * Add the menu item
         */
        public function add_menu_items($submenus)
        {
            if (is_admin())
            {
                add_options_page(__('WP Customer Area Settings', 'cuar'),
                    __('WP Customer Area', 'cuar'),
                    'manage_options',
                    self::$OPTIONS_PAGE_SLUG,
                    [&$this, 'print_settings_page']
                );
            }

            $item = [
                'page_title' => __('WP Customer Area Settings', 'cuar'),
                'title' => __('Settings', 'cuar'),
                'slug' => self::$OPTIONS_PAGE_SLUG,
                'href' => admin_url('options-general.php?page=' . self::$OPTIONS_PAGE_SLUG),
                'capability' => 'manage_options',
                'adminbar-only' => true,
                'children' => [],
            ];

            $tabs = apply_filters('cuar/core/settings/settings-tabs', []);
            $tabs_to_skip = ['cuar_addons', 'cuar_troubleshooting'];
            foreach ($tabs as $tab_id => $tab_label)
            {
                if (in_array($tab_id, $tabs_to_skip))
                {
                    continue;
                }

                $item['children'][] = [
                    'slug' => 'customer-area-admin-settings-' . $tab_id,
                    'title' => $tab_label,
                    'href' => admin_url('options-general.php?page=' . self::$OPTIONS_PAGE_SLUG . '&tab=' . $tab_id),
                ];
            }

            $submenus[] = $item;

            return $submenus;
        }

        public function print_plugin_action_links($links, $file)
        {
            $link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=' . self::$OPTIONS_PAGE_SLUG . '">'
                    . __('Settings', 'cuar') . '</a>';
            array_unshift($links, $link);

            return $links;
        }

        /**
         * Output the settings page
         */
        public function print_settings_page()
        {
            if (isset($_GET['run-setup-wizard']))
            {
                $success = false;

                if (isset($_POST['submit']))
                {
                    $errors = [];
                    if (!isset($_POST["cuar_page_title"]) || empty($_POST["cuar_page_title"]))
                    {
                        $errors[] = __('The page title cannot be empty', 'cuar');
                    }

                    if (empty($errors))
                    {
                        $post_data = [
                            'post_content' => '[customer-area /]',
                            'post_title' => $_POST["cuar_page_title"],
                            'post_status' => 'publish',
                            'post_type' => 'page',
                            'comment_status' => 'closed',
                            'ping_status' => 'closed',
                        ];
                        $page_id = wp_insert_post($post_data);
                        if (is_wp_error($page_id))
                        {
                            $errors[] = $page_id->get_error_message();
                        }
                        else
                        {
                            $this->plugin->get_addon('customer-pages')->set_customer_page_id($page_id);
                        }

                        if (empty($errors))
                        {
                            $success = true;
                        }
                    }
                }

                if ($_GET['run-setup-wizard'] == 1664)
                {
                    $success = true;
                }

                if ($success)
                {
                    include(CUAR_INCLUDES_DIR . '/setup-wizard-done.view.php');
                }
                else
                {
                    include(CUAR_INCLUDES_DIR . '/setup-wizard.view.php');
                }
            }
            else
            {
                include($this->plugin->get_template_file_path(
                    CUAR_INCLUDES_DIR . '/core-classes',
                    'settings.template.php',
                    'templates'));
            }
        }

        /**
         * Register the settings
         */
        public function page_init()
        {
            $this->setup_tabs();

            // Register the main settings and for the current tab too
            register_setting(self::$OPTIONS_GROUP, self::$OPTIONS_GROUP, [&$this, 'validate_options']);
            register_setting(self::$OPTIONS_GROUP . '_' . $this->current_tab, self::$OPTIONS_GROUP, [&$this,
                                                                                                     'validate_options']);

            // Let the current tab add its own settings to the page
            do_action("cuar/core/settings/print-settings?tab=" . $this->current_tab, $this, self::$OPTIONS_GROUP . '_' . $this->current_tab);
        }

        /**
         * Create the tabs to show
         */
        public function setup_tabs()
        {
            $this->tabs = apply_filters('cuar/core/settings/settings-tabs', []);

            // Get current tab from GET or POST params or default to first in list
            $this->current_tab = isset($_GET ['tab']) ? sanitize_text_field($_GET['tab']) : '';
            if (!isset($this->tabs [$this->current_tab]))
            {
                $this->current_tab = isset($_POST ['tab']) ? sanitize_text_field($_POST['tab']) : '';
            }
            if (!isset($this->tabs [$this->current_tab]))
            {
                reset($this->tabs);
                $this->current_tab = key($this->tabs);
            }
        }

        /**
         * Save the plugin settings
         *
         * @param array $input
         *            The new option values
         *
         * @return
         *
         */
        public function validate_options($input)
        {
            $validated = [];

            // Allow addons to validate their settings here
            $validated = apply_filters('cuar/core/settings/validate-settings?tab=' . $this->current_tab, $validated,
                $this, $input);

            $this->options = array_merge($this->options, $validated);

            // Also flush rewrite rules
            global $wp_rewrite;
            $wp_rewrite->flush_rules();

            return $this->options;
        }

        /* ------------ CORE SETTINGS ----------------------------------------------------------------------------------- */

        /**
         * Add a tab
         *
         * @param array $tabs
         *
         * @return array
         */
        public function add_core_settings_tab($tabs)
        {
            $tabs['cuar_core'] = __('General', 'cuar');
            $tabs['cuar_frontend'] = __('Frontend', 'cuar');

            return $tabs;
        }

        /**
         * Add a tab
         *
         * @param array $tabs
         *
         * @return array
         */
        public function add_licensing_settings_tab($tabs)
        {
            if ($this->plugin->has_commercial_addons())
            {
                $tabs['cuar_licenses'] = __('License Keys', 'cuar');
            }

            return $tabs;
        }

        /**
         * Add our fields to the settings page
         *
         * @param CUAR_Settings $cuar_settings
         *            The settings class
         */
        public function print_core_settings($cuar_settings, $options_group)
        {
            // General settings
            add_settings_section('cuar_general_settings',
                __('General Settings', 'cuar'),
                [&$cuar_settings, 'print_empty_section_info'],
                self::$OPTIONS_PAGE_SLUG);

            add_settings_field(self::$OPTION_ADMIN_SKIN,
                __('Admin theme', 'cuar'),
                [&$cuar_settings, 'print_theme_select_field'],
                self::$OPTIONS_PAGE_SLUG,
                'cuar_general_settings',
                [
                    'option_id' => self::$OPTION_ADMIN_SKIN,
                    'theme_type' => 'admin',
                ]
            );
        }

        /**
         * Validate core options
         *
         * @param CUAR_Settings $cuar_settings
         * @param array         $input
         * @param array         $validated
         *
         * @return array
         */
        public function validate_core_settings($validated, $cuar_settings, $input)
        {
            $cuar_settings->validate_not_empty($input, $validated, self::$OPTION_ADMIN_SKIN);

            return $validated;
        }

        /**
         * Add our fields to the settings page
         *
         * @param CUAR_Settings $cuar_settings
         *            The settings class
         */
        public function print_frontend_settings($cuar_settings, $options_group)
        {
            // General settings
            add_settings_section('cuar_general_settings',
                __('General Settings', 'cuar'),
                [&$cuar_settings, 'print_frontend_section_info'],
                self::$OPTIONS_PAGE_SLUG);

            if (!current_theme_supports('customer-area.stylesheet'))
            {
                add_settings_field(self::$OPTION_INCLUDE_CSS,
                    __('Use skin', 'cuar'),
                    [&$cuar_settings, 'print_input_field'],
                    self::$OPTIONS_PAGE_SLUG,
                    'cuar_general_settings',
                    [
                        'option_id' => self::$OPTION_INCLUDE_CSS,
                        'type' => 'checkbox',
                        'after' => __('Includes the WP Customer Area skin to provide a stylesheet for the plugin.', 'cuar') . '<p class="description">'
                                   . __('You can uncheck this if your theme includes support for WP Customer Area.', 'cuar') . '</p>',
                    ]
                );

                add_settings_field(self::$OPTION_FRONTEND_SKIN,
                    __('Skin to use', 'cuar'),
                    [&$cuar_settings, 'print_theme_select_field'],
                    self::$OPTIONS_PAGE_SLUG,
                    'cuar_general_settings',
                    [
                        'option_id' => self::$OPTION_FRONTEND_SKIN,
                        'theme_type' => 'frontend',
						'after' => '<p class="description">'
								   . sprintf(__('You can make your own skin, please refer to <a href="%1$s">our documentation about skins</a>.', 'cuar'),
								cuar_site_url('/documentation/developer-guides/the-skin-system'))
								   . '</p>',
                    ]
                );
            }

            add_settings_field(self::$OPTION_DEBUG_TEMPLATES,
                __('Debug templates', 'cuar'),
                [&$cuar_settings, 'print_input_field'],
                self::$OPTIONS_PAGE_SLUG,
                'cuar_general_settings',
                [
                    'option_id' => self::$OPTION_DEBUG_TEMPLATES,
                    'type' => 'checkbox',
                    'after' => __('Print debug information about the templates used by WP Customer Area.', 'cuar')
                               . '<p class="description">'
                               . __('If checked, the plugin will print HTML comments in the page source code to show which template files '
                                    . 'are used. This is very helpful if you are a developer and want to customize the plugin layout.',
                            'cuar') . '</p>',
                ]
            );
        }

        /**
         * Validate frontend options
         *
         * @param CUAR_Settings $cuar_settings
         * @param array         $input
         * @param array         $validated
         *
         * @return array
         */
        public function validate_frontend_settings($validated, $cuar_settings, $input)
        {
            $cuar_settings->validate_boolean($input, $validated, self::$OPTION_DEBUG_TEMPLATES);

            if (!current_theme_supports('customer-area-css'))
            {
                $cuar_settings->validate_boolean($input, $validated, self::$OPTION_INCLUDE_CSS);
                $cuar_settings->validate_not_empty($input, $validated, self::$OPTION_FRONTEND_SKIN);
            }

            return $validated;
        }


        /**
         * Add our fields to the settings page
         *
         * @param CUAR_Settings $cuar_settings The settings class
         */
        public function print_license_settings($cuar_settings, $options_group)
        {
            add_settings_section(
                'cuar_license_keys_section',
                __('License keys for commercial add-ons', 'cuar'),
                [&$this, 'print_license_section_info'],
                CUAR_Settings::$OPTIONS_PAGE_SLUG
            );

            $commercial_addons = $this->plugin->get_commercial_addons();
            foreach ($commercial_addons as $id => $addon)
            {
                $licensing_client = $addon->get_licensing_client();
                $api_key_option_id = $licensing_client->get_option_key('api_key');

                add_settings_field(
                    $api_key_option_id,
                    $addon->get_addon_name(),
                    [&$cuar_settings, 'print_license_key_field'],
                    CUAR_Settings::$OPTIONS_PAGE_SLUG,
                    'cuar_license_keys_section',
                    [
                        'addon_id' => $id,
                        'addon' => $addon,
                        'licensing_client' => $licensing_client,
                        'after' => '',
                    ]
                );
            }
        }

        /**
         * Validate our options
         *
         * @param CUAR_Settings $cuar_settings
         * @param array         $input
         * @param array         $validated
         *
         * @return array
         */
        public function validate_license_options($validated, $cuar_settings, $input)
        {
            return $validated;
        }

        public function print_empty_section_info()
        {
        }

        public function print_license_section_info()
        {
            echo '<p>';
            _e('This page allows you to enter license key you have received when you purchased commercial addons.', 'cuar');
            echo ' ';
            _e('You must set the proper product ID (personal, professional or developer) or else activation will fail.',
                'cuar');
            echo '</p>';
            echo '<p>';
            echo sprintf(
                __('You should have received those keys in your purchase confirmation email. You can however also find them in the %smy account page%s on the WP Customer Area website.',
                    'cuar'),
				'<a href="' . cuar_site_url('/my-account/api-keys') . '" target="_blank">',
                '</a>'
            );
            echo '</p>';
            echo '<p>';
            echo sprintf(
                __('You can also manage license activations from the %smy account page%s on the WP Customer Area website. For example, when you want to move a license from one website to another.',
                    'cuar'),
                '<a href="' . cuar_site_url('/my-account/api-keys') . '" target="_blank">',
                '</a>'
            );
            echo '</p>';
        }

        public function print_frontend_section_info()
        {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function ($)
                {
                    $(document).on('change', '#cuar_include_css', function ()
                    {
                        $('#cuar_frontend_theme_url').parents('tr').slideToggle();
                    });
                    if ($('#cuar_include_css').is(':checked')) {
                        $('#cuar_frontend_theme_url').parents('tr').show();
                    } else {
                        $('#cuar_frontend_theme_url').parents('tr').hide();
                    }
                });
            </script>
            <?php
        }

        /**
         * Set the default values for the core options
         *
         * @param array $defaults
         *
         * @return array
         */
        public static function set_default_core_options($defaults)
        {
            $defaults[self::$OPTION_INCLUDE_CSS] = true;
            $defaults[self::$OPTION_DEBUG_TEMPLATES] = false;
            $defaults[self::$OPTION_ADMIN_SKIN] = CUAR_ADMIN_SKIN;
            $defaults[self::$OPTION_FRONTEND_SKIN] = CUAR_FRONTEND_SKIN;
            $defaults[self::$OPTION_GET_BETA_VERSION_NOTIFICATIONS] = false;
            $defaults[self::$OPTION_BYPASS_SSL] = false;

            return $defaults;
        }

        /* ------------ VALIDATION HELPERS ------------------------------------------------------------------------------ */

        /**
         * Validate an address
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         */
        public function validate_address($input, &$validated, $option_id)
        {
            $validated[$option_id] = CUAR_AddressHelper::sanitize_address($input[$option_id]);
        }

        /**
         * Validate a value in any case
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         */
        public function validate_always($input, &$validated, $option_id)
        {
            $validated[$option_id] = trim($input[$option_id]);
        }

        /**
         * Validate a page
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         */
        public function validate_post_id($input, &$validated, $option_id)
        {
            if ($input[$option_id] == -1 || get_post($input[$option_id]))
            {
                $validated[$option_id] = $input[$option_id];
            }
            else
            {
                add_settings_error($option_id, 'settings-errors',
                    $option_id . ': ' . $input[$option_id] . __(' is not a valid post', 'cuar'), 'error');
            }
        }

        /**
         * Validate an hexadecimal color value within an array
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         */
        public function validate_hex_color($input, &$validated, $option_id)
        {
            if (!isset($input[$option_id]))
            {
                return;
            }

            if (preg_match('/^#[a-f0-9]{6}$/i', $input[$option_id]))
            { // if user insert a HEX color with #123456
                $validated[$option_id] = $input[$option_id];
            }

            if (preg_match('/^#[a-f0-9]{3}$/i', $input[$option_id]))
            { // if user insert a HEX color with #123
                $validated[$option_id] = $input[$option_id];
            }
        }

        /**
         * Validate a boolean value within an array
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         */
        public function validate_boolean($input, &$validated, $option_id)
        {
            $validated[$option_id] = isset($input[$option_id]) ? true : false;
        }

        /**
         * Validate a role
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         */
        public function validate_role($input, &$validated, $option_id)
        {
            $role = $input[$option_id];
            if (isset($role) && ($role === "cuar_any" || null !== get_role($input[$option_id])))
            {
                $validated[$option_id] = $input[$option_id];
            }
            else
            {
                add_settings_error($option_id, 'settings-errors',
                    $option_id . ': ' . $input[$option_id] . __(' is not a valid role', 'cuar'), 'error');
            }
        }

        /**
         * Validate a value in any case
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         */
        public function validate_license_key($input, &$validated, $option_id)
        {
            $validated[$option_id] = trim($input[$option_id]);
        }

        /**
         * Validate a value which should simply be not empty
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         */
        public function validate_not_empty($input, &$validated, $option_id)
        {
            if (isset($input[$option_id]) && !empty ($input[$option_id]))
            {
                $validated[$option_id] = $input[$option_id];
            }
            else
            {
                add_settings_error($option_id, 'settings-errors',
                    $option_id . ': ' . $input[$option_id] . __(' cannot be empty', 'cuar'), 'error');

                $validated[$option_id] = $this->default_options [$option_id];
            }
        }

        /**
         * Validate an email address
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         */
        public function validate_email($input, &$validated, $option_id)
        {
            if (isset($input[$option_id]) && is_email($input[$option_id]))
            {
                $validated[$option_id] = $input[$option_id];
            }
            else
            {
                add_settings_error($option_id, 'settings-errors',
                    $option_id . ': ' . $input[$option_id] . __(' is not a valid email', 'cuar'), 'error');

                $validated[$option_id] = $this->default_options [$option_id];
            }
        }

        /**
         * Validate an enum value within an array
         *
         * @param array  $input          Input array
         * @param array  $validated      Output array
         * @param string $option_id      Key of the value to check in the input array
         * @param array  $select_options List of possible id => values
         * @param bool   $multiple       Is multiple selection allowed
         */
        public function validate_select($input, &$validated, $option_id, $select_options, $multiple)
        {
            if ($multiple)
            {
                if (empty($input[$option_id]))
                {
                    $input[$option_id] = [];
                }

                if (!is_array($input[$option_id]))
                {
                    add_settings_error($option_id, 'settings-errors',
                        $option_id . ': ' . $input[$option_id] . __(' is not a valid value', 'cuar'), 'error');
                    $validated[$option_id] = $this->default_options [$option_id];

                    return;
                }

                $validated[$option_id] = [];
                foreach ($input[$option_id] as $item)
                {
                    if (isset($select_options[$item]))
                    {
                        $validated[$option_id][] = $item;
                    }
                }
            }
            else
            {
                if (is_array($input[$option_id]) || !isset($select_options[$input[$option_id]]))
                {
                    add_settings_error($option_id, 'settings-errors',
                        $option_id . ': ' . $input[$option_id] . __(' is not a valid value', 'cuar'), 'error');
                    $validated[$option_id] = $this->default_options [$option_id];

                    return;
                }

                $validated[$option_id] = $input[$option_id];
            }
        }

        /**
         * Validate an enum value within an array
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         * @param array  $enum_values
         *            Array of possible values
         */
        public function validate_enum($input, &$validated, $option_id, $enum_values)
        {
            if (!in_array($input[$option_id], $enum_values))
            {
                add_settings_error($option_id, 'settings-errors',
                    $option_id . ': ' . $input[$option_id] . __(' is not a valid value', 'cuar'), 'error');

                $validated[$option_id] = $this->default_options [$option_id];

                return;
            }

            $validated[$option_id] = $input[$option_id];
        }

        /**
         * Validate an integer value within an array
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         * @param int    $min
         *            Min value for the int (set to null to ignore check)
         * @param int    $max
         *            Max value for the int (set to null to ignore check)
         */
        public function validate_int($input, &$validated, $option_id, $min = null, $max = null)
        {
            // Must be an int
            if (!is_int(intval($input[$option_id])))
            {
                add_settings_error($option_id, 'settings-errors', $option_id . ': ' . __('must be an integer', 'cuar'),
                    'error');

                $validated[$option_id] = $this->default_options [$option_id];

                return;
            }

            // Must be > min
            if ($min !== null && $input[$option_id] < $min)
            {
                add_settings_error($option_id, 'settings-errors',
                    $option_id . ': ' . sprintf(__('must be greater than %s', 'cuar'), $min), 'error');

                $validated[$option_id] = $this->default_options [$option_id];

                return;
            }

            // Must be < max
            if ($max !== null && $input[$option_id] > $max)
            {
                add_settings_error($option_id, 'settings-errors',
                    $option_id . ': ' . sprintf(__('must be lower than %s', 'cuar'), $max), 'error');

                $validated[$option_id] = $this->default_options [$option_id];

                return;
            }

            // All good
            $validated[$option_id] = intval($input[$option_id]);
        }

        /**
         * Validate a value which should be an owner
         *
         * @param array  $input
         *            Input array
         * @param array  $validated
         *            Output array
         * @param string $option_id
         *            Key of the value to check in the input array
         */
        public function validate_owners($input, &$validated, $option_id, $owner_type_option_id)
        {
            if (empty($input[$option_id]))
            {
                $validated[$option_id] = [];
                $validated[$owner_type_option_id] = '';
            }
            else if (is_array($input[$option_id]))
            {
                $owners = [];
                foreach ($input[$option_id] as $type => $ids)
                {
                    if (!is_array($ids))
                    {
                        $ids = [$ids];
                    }

                    // Check if $ids is not really empty
                    $isNotEmpty = false;
                    foreach ($ids as $id)
                    {
                        if (!empty($id))
                        {
                            $isNotEmpty = true;
                            break;
                        }
                    }

                    if ($isNotEmpty)
                    {
                        $owners[$type] = $ids;
                    }
                }
                $validated[$option_id] = $owners;
                $validated[$owner_type_option_id] = '';
            }
            else
            {
                add_settings_error($option_id, 'settings-errors', $option_id . ': ' . __('Invalid owner', 'cuar'), 'error');
                $validated[$option_id] = $this->default_options [$option_id];
            }
        }

        /**
         * Validate a term id (for now, we just check it is not empty and strictly positive)
         *
         * @param array  $input     Input array
         * @param array  $validated Output array
         * @param string $option_id Key of the value to check in the input array
         */
        public function validate_term($input, &$validated, $option_id, $taxonomy, $allow_multiple = false)
        {
            $term_ids = isset($input[$option_id]) ? $input[$option_id] : '';

            if (!$allow_multiple && is_array($term_ids))
            {
                add_settings_error($option_id, 'settings-errors',
                    $option_id . ': ' . __('you cannot select multiple terms.', 'cuar'), 'error');
                $validated[$option_id] = -1;

                return;
            }

            if ($allow_multiple)
            {
                if (!is_array($term_ids))
                {
                    $term_ids = empty($term_ids) ? [] : [$term_ids];
                }

                $validated[$option_id] = $term_ids;
            }
            else
            {
                $validated[$option_id] = empty($term_ids) ? -1 : $term_ids;
            }
        }

        /**
         * Validate a post type
         *
         * @param array  $input     Input array
         * @param array  $validated Output array
         * @param string $option_id Key of the value to check in the input array
         */
        public function validate_post_type($input, &$validated, $option_id, $allow_multiple = false)
        {
            $term_ids = isset($input[$option_id]) ? $input[$option_id] : '';

            if (!$allow_multiple && is_array($term_ids))
            {
                add_settings_error($option_id, 'settings-errors',
                    $option_id . ': ' . __('you cannot select multiple terms.', 'cuar'), 'error');
                $validated[$option_id] = -1;

                return;
            }

            if ($allow_multiple)
            {
                if (!is_array($term_ids))
                {
                    $term_ids = empty($term_ids) ? [] : [$term_ids];
                }

                $validated[$option_id] = $term_ids;
            }
            else
            {
                $validated[$option_id] = empty($term_ids) ? -1 : $term_ids;
            }
        }

        /**
         * Handles remote requests to validate a license
         */
        public static function ajax_validate_license()
        {
            $cuar_plugin = cuar();

            $addon_id = $_POST["addon_id"];
            $api_key = $_POST["api_key"];
            $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';

            /** @var CUAR_AddOn $addon */
            $addon = $cuar_plugin->get_addon($addon_id);

            $licensing_client = $addon->get_licensing_client();
            if ($licensing_client === null)
            {
                return "";
            }

            $license_types = $addon->get_license_types();
            if (count($license_types) === 1)
            {
                $product_id = $license_types[0];
            }

            $result = $licensing_client->activate_license($product_id, $api_key);

            // Tell WordPress to look for updates
            set_site_transient('update_plugins', null);

            echo json_encode($result);
            exit;
        }

        /* ------------ FIELDS OUTPUT ----------------------------------------------------------------------------------- */

        /**
         * Output a text field for a license key
         *
         * @param string $option_id
         */
        public function print_license_key_field($args)
        {
            extract($args);

            $api_key_option_id = $licensing_client->get_option_key('api_key');
            $api_key = isset($this->options[$api_key_option_id]) ? $this->options[$api_key_option_id] : null;

            $product_id_option_id = $licensing_client->get_option_key('product_id');
            $product_id = isset($this->options[$product_id_option_id]) ? $this->options[$product_id_option_id] : null;

            $status = $licensing_client->get_option('status');
            $last_result = $licensing_client->get_option('last_validation_result');

            if (!empty($last_result) && !empty($api_key))
            {
                $status_class = $last_result->success ? 'cuar-ajax-success' : 'cuar-ajax-failure';
                if (isset($last_result->status_check) && !empty($last_result->data))
                {
                    if ($last_result->data->activated)
                    {
                        if ($last_result->data->unlimited_activations)
                        {
                            $status_class = 'cuar-ajax-success';
                            $status_message = __('License is active and you are allowed to activate it on any number of website.', 'cuar');
                        }
                        else if ($last_result->data->activations_remaining > 0)
                        {
                            $status_class = 'cuar-ajax-success';
                            $status_message = sprintf(
                                __('License is active on this website. You are still allowed to activate it on %d other website(s).', 'cuar'),
                                $last_result->data->activations_remaining
                            );
                        }
                        else
                        {
                            $status_class = 'cuar-ajax-success';
                            $status_message = sprintf(
                                __('License is active on this website. You have used your %d activation(s) included in that license type.',
                                    'cuar'),
                                $last_result->data->activations_remaining
                            );
                        }
                    }
                    else
                    {
                        $status_class = 'cuar-ajax-failure';
                        $status_message = __('License is not active.', 'cuar');
                    }
                }
                else
                {
                    $status_message = $last_result->success ? $last_result->message : $last_result->error;
                }
            }
            else if (empty($api_key))
            {
                $status_class = 'cuar-ajax-failure';
                $status_message = __('Please enter your license key, the right product ID and press the « activate » button.', 'cuar');
                $licensing_client->update_option('status', 'Deactivated');
                $licensing_client->update_option('last_validation_result', []);
            }
            else
            {
                $status_class = '';
                $status_message = '';
            }

            if (isset($before))
            {
                echo $before;
            }

            $extra_class = !empty($license_types) && count($license_types) > 1 ? 'without-type-select' : 'with-type-select';

            echo '<div class="license-control cuar-js-license-field ' . $extra_class . '">';

            echo sprintf(
                '<input type="text" id="%s" name="%s[%s]" value="%s" class="regular-text cuar-js-api-key" data-addon="%s" />',
                esc_attr($api_key_option_id . "_key"),
                self::$OPTIONS_GROUP,
                esc_attr($api_key_option_id),
                esc_attr($api_key),
                esc_attr($addon_id)
            );

            $license_types = $addon->get_license_types();
            if (count($license_types) > 1)
            {
                echo sprintf(
                    '<select id="%s" name="%s[%s]" class="cuar-js-product-id" data-addon="%s" />',
                    esc_attr($api_key_option_id . "_type"),
                    self::$OPTIONS_GROUP,
                    esc_attr($api_key_option_id),
                    esc_attr($addon_id)
                );

                foreach ($license_types as $id => $name)
                {
                    echo sprintf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($id),
                        selected($product_id, $id),
                        $name
                    );
                }

                echo '</select>';
            }

            echo sprintf('<a href="#" class="button cuar-js-activate-button">%s</a>', __('Activate', 'cuar'));

            echo sprintf(
                '<span class="cuar-ajax-container cuar-js-result"><span id="%s_check_result" class="%s">%s</span></span>',
                esc_attr($api_key_option_id),
                $status_class,
                $status_message
            );

            echo '</div>';

            if (isset($after))
            {
                echo $after;
            }

//            echo '<pre><code>';
//            echo json_encode($last_result, JSON_PRETTY_PRINT);
//            echo '</code></pre>';

            wp_enqueue_script('cuar.admin');
        }

        /**
         * Output a text field for a setting
         *
         * @param string $option_id
         * @param string $type
         * @param string $caption
         */
        public function print_input_field($args)
        {
            extract($args);

            if ($type == 'checkbox')
            {
                if (isset($before))
                {
                    echo $before;
                }

                echo sprintf('<input type="%s" id="%s" name="%s[%s]" value="open" %s />&nbsp;', esc_attr($type),
                    esc_attr($option_id), self::$OPTIONS_GROUP, esc_attr($option_id),
                    ($this->options [$option_id] != 0) ? 'checked="checked" ' : '');

                if (isset($after))
                {
                    echo $after;
                }
            }
            else if ($type == 'textarea')
            {
                if (isset($before))
                {
                    echo $before;
                }

                echo sprintf('<textarea id="%s" name="%s[%s]" class="large-text">%s</textarea>', esc_attr($option_id),
                    self::$OPTIONS_GROUP, esc_attr($option_id), $content);

                if (isset($after))
                {
                    echo $after;
                }
            }
            else if ($type == 'editor')
            {
                if (!isset($editor_settings))
                {
                    /** @noinspection PhpDeprecationInspection Fine since we are on the admin side */
                    $editor_settings = cuar_wp_editor_settings();
                }
                $editor_settings ['textarea_name'] = self::$OPTIONS_GROUP . "[" . $option_id . "]";

                wp_editor($this->options [$option_id], $option_id, $editor_settings);
            }
            else if ($type == 'upload')
            {
                wp_enqueue_script('cuar.admin');
                wp_enqueue_media();

                $extra_class = 'cuar-upload-input regular-text';

                if (isset($before))
                {
                    echo $before;
                }

                echo '<div id="cuar-upload-control-' . $option_id . '">';
                echo sprintf('<input type="%s" id="%s" name="%s[%s]" value="%s" class="%s" />', esc_attr($type),
                    esc_attr($option_id), self::$OPTIONS_GROUP, esc_attr($option_id),
                    esc_attr(stripslashes($this->options [$option_id])), esc_attr($extra_class));

                echo '<span>&nbsp;<input type="button" class="cuar-upload-button button-secondary" value="' . __('Upload File', 'cuar') . '"/></span>';

                echo '<script type="text/javascript">';
                echo '    jQuery(document).ready(function($) { $("#cuar-upload-control-' . $option_id . '").mediaInputControl(); });';
                echo '</script>';
                echo '</div>';

                if (isset($after))
                {
                    echo $after;
                }
            }
            else if ($type == 'color')
            {
                wp_enqueue_script('cuar.admin');

                $extra_class = 'cuar-color-input color-field';

                if (isset($before))
                {
                    echo $before;
                }

                echo '<div id="cuar-color-control-' . $option_id . '">';
                echo sprintf('<input type="%s" id="%s" name="%s[%s]" value="%s" class="%s" />', 'text',
                    esc_attr($option_id), self::$OPTIONS_GROUP, esc_attr($option_id),
                    esc_attr(stripslashes($this->options [$option_id])), esc_attr($extra_class));
                echo '<script type="text/javascript">';
                echo '    jQuery(document).ready(function($) { $("#cuar-color-control-' . $option_id . ' input").wpColorPicker(); });';
                echo '</script>';
                echo '</div>';

                if (isset($after))
                {
                    echo $after;
                }
            }
            else
            {
                $extra_class = isset($is_large) && $is_large == true ? 'large-text' : 'regular-text';

                if (isset($before))
                {
                    echo $before;
                }

                echo sprintf('<input type="%s" id="%s" name="%s[%s]" value="%s" class="%s" />', esc_attr($type),
                    esc_attr($option_id), self::$OPTIONS_GROUP, esc_attr($option_id),
                    esc_attr($this->options [$option_id]), esc_attr($extra_class));

                if (isset($after))
                {
                    echo $after;
                }
            }
        }

        /**
         * Output a submit button
         *
         * @param string $option_id
         * @param array  $options
         * @param string $caption
         */
        public function print_submit_button($args)
        {
            extract($args);

            if (isset($before))
            {
                echo $before;
            }

            echo sprintf('<p><input type="submit" name="%s" id="%s[%s]" value="%s" class="button %s" /></p>',
                esc_attr($option_id),
                self::$OPTIONS_GROUP, esc_attr($option_id),
                $label,
                esc_attr($option_id)
            );

            wp_nonce_field($nonce_action, $nonce_name);

            if (isset($after))
            {
                echo $after;
            }

            if (isset($confirm_message) && !empty($confirm_message))
            {
                ?>
                <script type="text/javascript">
                    <!--
                    jQuery(document).ready(function ($)
                    {
                        $('input.<?php echo esc_attr($option_id); ?>').click('click', function ()
                        {
                            var answer = confirm("<?php echo str_replace('"', '\\"', $confirm_message); ?>");
                            return answer;
                        });
                    });
                    //-->
                </script>
                <?php
            }
        }

        /**
         * Output a select field for a setting
         *
         * @param string $option_id
         * @param array  $options
         * @param string $caption
         */
        public function print_select_field($args)
        {
            extract($args);

            if (isset($before))
            {
                echo $before;
            }

            $multiple = isset($multiple) ? $multiple : false;
            $multiple = $multiple ? ' multiple="multiple" ' : '';

            $option_name = sprintf('%s[%s]', self::$OPTIONS_GROUP, esc_attr($option_id));
            $option_name = $multiple ? $option_name . '[]' : $option_name;

            echo sprintf('<select id="%s" name="%s" %s>', esc_attr($option_id), $option_name, $multiple);

            $option_value = isset($this->options[$option_id]) ? $this->options[$option_id] : null;
            foreach ($options as $value => $label)
            {
                if (is_array($option_value))
                {
                    $selected = in_array($value, $option_value) ? 'selected="selected"' : '';
                }
                else
                {
                    $selected = ($option_value == $value) ? 'selected="selected"' : '';
                }

                echo sprintf('<option value="%s" %s>%s</option>', esc_attr($value), $selected, $label);
            }

            echo '</select>';

            if (isset($after))
            {
                echo $after;
            }

            $this->plugin->enable_library('jquery.select2');
            echo '<script type="text/javascript">
                <!--
                jQuery("document").ready(function ($) {
                    $("#' . esc_attr($option_id) . '").cuarSelect2({
                        ' . (!is_admin() ? 'dropdownParent: $("#' . esc_attr($option_id) . '.parent()"),' : '') . '
                        width: "100%"
                    });
                });
                //-->
            </script>';
        }

        /**
         * Output a select field for a setting
         *
         * @param string $option_id
         * @param array  $options
         * @param string $caption
         */
        public function print_post_select_field($args)
        {
            extract($args);

            $query_args = [
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
            ];
            $pages_query = new WP_Query($query_args);

            if (isset($before))
            {
                echo $before;
            }

            echo sprintf('<select id="%s" name="%s[%s]" class="cuar-post-select">', esc_attr($option_id),
                self::$OPTIONS_GROUP, esc_attr($option_id));

            $value = -1;
            $label = __('None', 'cuar');
            $selected = (isset($this->options[$option_id]) && $this->options[$option_id] == $value)
                ? 'selected="selected"' : '';
            echo sprintf('<option value="%s" %s>%s</option>', esc_attr($value), $selected, $label);

            while ($pages_query->have_posts())
            {
                $pages_query->the_post();
                $value = get_the_ID();
                $label = get_the_title();

                $selected = (isset($this->options[$option_id]) && $this->options[$option_id] == $value)
                    ? 'selected="selected"' : '';

                echo sprintf('<option value="%s" %s>%s</option>', esc_attr($value), $selected, $label);
            }

            echo '</select>';

            if (isset($this->options[$option_id]) && $this->options[$option_id] > 0)
            {
                if ($show_create_button)
                {
                    printf('<input type="submit" value="%1$s" id="%2$s" name="%2$s" class="cuar-submit-create-post"/>',
                        esc_attr__('Delete existing &amp; create new &raquo;', 'cuar'),
                        esc_attr($this->get_submit_create_post_button_name($option_id))
                    );
                }

                edit_post_link(__('Edit it &raquo;', 'cuar'), '<span class="cuar-edit-page-link">', '</span>',
                    $this->options[$option_id]);
            }
            else
            {
                if ($show_create_button)
                {
                    printf('<input type="submit" value="%1$s" id="%2$s" name="%2$s" class="cuar-submit-create-post"/>',
                        esc_attr__('Create it &raquo;', 'cuar'),
                        esc_attr($this->get_submit_create_post_button_name($option_id))
                    );
                }
            }

            wp_reset_postdata();

            if (isset($after))
            {
                echo $after;
            }
        }

        public function get_submit_create_post_button_name($option_id)
        {
            return 'submit_' . $option_id . '_create';
        }

        /**
         * Output a set of fields for an address
         *
         * @param string $option_id
         * @param array  $options
         * @param string $caption
         */
        public function print_address_fields($args)
        {
            extract($args);

            if (isset($before))
            {
                echo $before;
            }

            $address = CUAR_AddressHelper::sanitize_address($this->options[$option_id]);

            /** @var CUAR_AddressesAddOn $am_addon */
            $am_addon = $this->plugin->get_addon("address-manager");
            $am_addon->print_address_editor($address, $option_id, '', [], '', 'settings', $context);

            if (isset($after))
            {
                echo $after;
            }
        }

        /**
         * Output a select field for a setting
         *
         * @param string $option_id
         * @param array  $options
         * @param string $caption
         */
        public function print_owner_select_field($args)
        {
            extract($args);

            if (isset($before))
            {
                echo $before;
            }

            // Handle legacy owner option
            if (!empty($this->options[$owner_type_option_id]))
            {
                $owner_type = $this->options[$owner_type_option_id];
                $owner_ids = $this->options [$option_id];

                $owners = [$owner_type => $owner_ids];
            }
            else
            {
                $owners = $this->options [$option_id];
            }

            /** @var CUAR_PostOwnerAddOn $po_addon */
            $po_addon = $this->plugin->get_addon("post-owner");
            $po_addon->print_owner_fields($owners, $option_id, self::$OPTIONS_GROUP);

            if (isset($after))
            {
                echo $after;
            }
        }

        /**
         * Output a select field for a setting
         *
         * @param string $option_id
         * @param array  $options
         * @param string $caption
         */
        public function print_role_select_field($args)
        {
            extract($args);

            if (isset($before))
            {
                echo $before;
            }

            $this->plugin->enable_library('jquery.select2');

            echo sprintf('<select id="%s" name="%s[%s]">', esc_attr($option_id), self::$OPTIONS_GROUP,
                esc_attr($option_id));

            global $wp_roles;
            if (!isset($wp_roles))
            {
                $wp_roles = new WP_Roles();
            }
            $all_roles = $wp_roles->role_objects;

            if ($show_any_option)
            {
                $value = 'cuar_any';
                $label = __('Any Role', 'cuar');
                $selected = ($this->options [$option_id] == $value) ? 'selected="selected"' : '';

                echo sprintf('<option value="%s" %s>%s</option>', esc_attr($value), $selected, $label);
            }

            foreach ($all_roles as $role)
            {
                $value = $role->name;
                $label = CUAR_WordPressHelper::getRoleDisplayName($role->name);
                $selected = ($this->options [$option_id] == $value) ? 'selected="selected"' : '';

                echo sprintf('<option value="%s" %s>%s</option>', esc_attr($value), $selected, $label);
            }

            echo '</select>';

            echo '<script type="text/javascript">
                <!--
                jQuery("document").ready(function ($) {
                    $("#' . esc_attr($option_id) . '").cuarSelect2({
                        ' . (!is_admin() ? 'dropdownParent: $("#' . esc_attr($option_id) . '.parent()"),' : '') . '
                        width: "100%"
                    });
                });
                //-->
            </script>';

            if (isset($after))
            {
                echo $after;
            }
        }

        /**
         * Output a select field for a term
         *
         * @param string $option_id
         * @param array  $options
         * @param string $caption
         */
        public function print_term_select_field($args)
        {
            extract($args);

            if (isset($before))
            {
                echo $before;
            }

            $this->plugin->enable_library('jquery.select2');
            $terms = get_terms($taxonomy, [
                'hide_empty' => 0,
                'orderby' => 'name',
            ]);

            $current_option_value = $this->options[$option_id];

            $field_name = esc_attr(self::$OPTIONS_GROUP) . '[' . esc_attr($option_id) . ']';
            if (isset($multiple) && $multiple)
            {
                $field_name .= '[]';
            }

            $field_id = esc_attr($option_id);

            $multiple = isset($multiple) && $multiple ? 'multiple="multiple" ' : '';
            ?>

            <select id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" <?php echo $multiple; ?>>

                <?php foreach ($terms as $term) :
                    $value = $term->term_id;
                    $label = $term->name;

                    if (is_array($current_option_value))
                    {
                        $selected = in_array($value, $current_option_value) ? 'selected="selected"' : '';
                    }
                    else
                    {
                        $selected = ($current_option_value == $value) ? 'selected="selected"' : '';
                    }
                    ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php echo $selected; ?>><?php echo $label; ?></option>

                <?php endforeach; ?>

            </select>

            <script type="text/javascript">
                <!--
                jQuery("document").ready(function ($)
                {
                    $("#<?php echo esc_attr($option_id); ?>").cuarSelect2({
                        <?php if (!is_admin())
                        {
                            echo "dropdownParent: $('#" . esc_attr($option_id) . "').parent(),";
                        } ?>
                        width: "100%"
                    });
                });
                //-->
            </script>
            <?php
            if (isset($after))
            {
                echo $after;
            }
        }

        /**
         * Output a select field for a post type
         *
         * @param string $option_id
         * @param array  $options
         * @param string $caption
         */
        public function print_post_type_select_field($args)
        {
            extract($args);

            if (isset($before))
            {
                echo $before;
            }

            $this->plugin->enable_library('jquery.select2');

            $post_types = get_post_types('', 'objects');
            $current_option_value = $this->options[$option_id];

            $field_name = esc_attr(self::$OPTIONS_GROUP) . '[' . esc_attr($option_id) . ']';
            if (isset($multiple) && $multiple)
            {
                $field_name .= '[]';
            }

            $field_id = esc_attr($option_id);

            $multiple = isset($multiple) && $multiple ? 'multiple="multiple" ' : '';
            ?>

            <select id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" <?php echo $multiple; ?>>

                <?php foreach ($post_types as $type => $obj) :
                    if (isset($exclude) && in_array($type, $exclude))
                    {
                        continue;
                    }

                    $value = $type;
                    $label = isset($obj->labels->singular_name) ? $obj->labels->singular_name : $type;

                    if (is_array($current_option_value))
                    {
                        $selected = in_array($value, $current_option_value) ? 'selected="selected"' : '';
                    }
                    else
                    {
                        $selected = ($current_option_value == $value) ? 'selected="selected"' : '';
                    }
                    ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php echo $selected; ?>><?php echo $label; ?></option>

                <?php endforeach; ?>

            </select>

            <script type="text/javascript">
                <!--
                jQuery("document").ready(function ($)
                {
                    $("#<?php echo esc_attr($option_id); ?>").cuarSelect2({
                        <?php if (!is_admin())
                        {
                            echo "dropdownParent: $('#" . esc_attr($option_id) . "').parent(),";
                        } ?>
                        width: "100%"
                    });
                });
                //-->
            </script>
            <?php
            if (isset($after))
            {
                echo $after;
            }
        }

        /**
         * Output a select field for a theme
         *
         * @param string $option_id
         * @param array  $options
         * @param string $caption
         */
        public function print_theme_select_field($args)
        {
            extract($args);

            if (isset($before))
            {
                echo $before;
            }

            $this->plugin->enable_library('jquery.select2');

            echo sprintf('<select id="%s" name="%s[%s]">', esc_attr($option_id), self::$OPTIONS_GROUP,
                esc_attr($option_id));

            $theme_locations = apply_filters('cuar/core/settings/theme-root-directories', [
                [
                    'base' => 'plugin',
                    'type' => $theme_type,
                    'dir' => CUAR_PLUGIN_DIR . '/skins/' . $theme_type,
                    'label' => __('Main plugin folder', 'cuar'),
                ],
                [
                    'base' => 'user-theme',
                    'type' => $theme_type,
                    'dir' => untrailingslashit(get_stylesheet_directory()) . '/customer-area/skins/' . $theme_type,
                    'label' => __('Current theme folder', 'cuar'),
                ],
                [
                    'base' => 'wp-content',
                    'type' => $theme_type,
                    'dir' => untrailingslashit(WP_CONTENT_DIR) . '/customer-area/skins/' . $theme_type,
                    'label' => __('WordPress content folder', 'cuar'),
                ],
            ]);

            foreach ($theme_locations as $theme_location)
            {
                if ($theme_location['type'] != $theme_type)
                {
                    continue;
                }

                $dir_content = glob($theme_location['dir'] . '/*');
                if (false === $dir_content)
                {
                    continue;
                }

                $subfolders = array_filter($dir_content, 'is_dir');

                foreach ($subfolders as $s)
                {
                    $theme_name = basename($s);
                    $label = $theme_location['label'] . ' - ' . $theme_name;
                    if ($theme_location['base'] == 'addon')
                    {
                        $value = esc_attr($theme_location['base'] . '%%' . $theme_location['addon-name'] . '%%' . $theme_name);
                    }
                    else
                    {
                        $value = esc_attr($theme_location['base'] . '%%' . $theme_name);
                    }
                    $selected = ($this->options[$option_id] == $value) ? 'selected="selected"' : '';

                    echo sprintf('<option value="%s" %s>%s</option>', esc_attr($value), $selected, $label);
                }
            }

            echo '</select>';

            echo '<script type="text/javascript">
                <!--
                jQuery("document").ready(function ($) {
                    $("#' . esc_attr($option_id) . '").cuarSelect2({
                        ' . (!is_admin() ? 'dropdownParent: $("#' . esc_attr($option_id) . '.parent()"),' : '') . '
                        width: "100%"
                    });
                });
                //-->
            </script>';

            if (isset($after))
            {
                echo $after;
            }
        }

        /* ------------ OTHER FUNCTIONS --------------------------------------------------------------------------------- */

        /**
         * Prints a sidebox on the side of the settings screen
         *
         * @param string $title
         * @param string $content
         */
        public function print_sidebox($title, $content)
        {
            echo '<div class="cuar-sidebox">';
            echo '<h2 class="cuar-sidebox-title">' . $title . '</h2>';
            echo '<div class="cuar-sidebox-content">' . $content . '</div>';
            echo '</div>';
        }

        /**
         * Update an option and persist to DB if asked to
         *
         * @param string  $option_id
         * @param mixed   $new_value
         * @param boolean $commit
         */
        public function update_option($option_id, $new_value, $commit = true)
        {
            $this->options [$option_id] = $new_value;
            if ($commit)
            {
                $this->save_options();
            }
        }

        /**
         * Persist the current plugin options to DB
         */
        public function save_options()
        {
            update_option(CUAR_Settings::$OPTIONS_GROUP, $this->options);
        }

        public function reset_defaults()
        {
            $this->options = [];
            $this->save_options();
            $this->reload_options();
        }

        public function set_options($opt)
        {
            $this->options = $opt;
            $this->save_options();
            $this->reload_options();
        }

        public function update_option_default($option_id, $new_value)
        {
            $this->default_options[$option_id] = $new_value;
        }

        /**
         * Persist the current plugin options to DB
         */
        public function get_options()
        {
            return $this->options;
        }

        /**
         * Persist the current plugin options to DB
         */
        public function get_default_options()
        {
            return $this->default_options;
        }

        /**
         * Load the options (and defaults if the options do not exist yet
         */
        private function reload_options()
        {
            $current_options = get_option(CUAR_Settings::$OPTIONS_GROUP);

            $this->default_options = apply_filters('cuar/core/settings/default-options', []);

            if (!is_array($current_options))
            {
                $current_options = [];
            }
            $this->options = array_merge($this->default_options, $current_options);

            do_action('cuar/core/settings/on-options-loaded', $this->options);
        }

        public static $OPTIONS_PAGE_SLUG = 'wpca-settings';
        public static $OPTIONS_GROUP = 'cuar_options';

        // Core options
        public static $OPTION_DEBUG_TEMPLATES = 'cuar_debug_templates';
        public static $OPTION_CURRENT_VERSION = 'cuar_current_version';
        public static $OPTION_INCLUDE_CSS = 'cuar_include_css';
        public static $OPTION_ADMIN_SKIN = 'cuar_admin_theme_url';
        public static $OPTION_FRONTEND_SKIN = 'cuar_frontend_theme_url';
        public static $OPTION_GET_BETA_VERSION_NOTIFICATIONS = 'cuar_license_is_beta_tester';
        public static $OPTION_BYPASS_SSL = 'cuar_licensing_bypass_ssl';

        /**
         * @var CUAR_Plugin The plugin instance
         */
        private $plugin;

		/**
		 * @var array
		 */
		private $default_options;

		/**
		 * @var array|mixed
		 */
		private $options;

        /**
         * @var array
         */
        private $tabs;

        /**
         * @var string
         */
        private $current_tab;
    }

    // This filter needs to be executed too early to be registered in the constructor
    add_filter('cuar/core/settings/default-options', [
        'CUAR_Settings',
        'set_default_core_options',
    ]);


endif; // if (!class_exists('CUAR_Settings')) :
