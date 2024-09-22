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

if (!class_exists('CUAR_Plugin')) :

    /**
     * The main plugin class
     *
     * @author Vincent Prat @ Foobar Studio
     */
    class CUAR_Plugin
    {

        /** @var CUAR_MessageCenter */
        private $message_center;

        /** @var CUAR_Settings */
        private $settings;

        /** @var CUAR_PluginActivationManager */
        private $activation_manager;

        /** @var CUAR_TemplateEngine */
        private $template_engine;

        /** @var CUAR_Logger */
        private $logger;

        /** @var CUAR_AddonManager */
        private $addon_manager;

        /** @var CUAR_Cron */
        private $cron;

        public function __construct()
        {
            $this->message_center = new CUAR_MessageCenter(['wpca-status', 'wpca-setup', 'wpca']);
            $this->activation_manager = new CUAR_PluginActivationManager();
            $this->template_engine = new CUAR_TemplateEngine('customer-area', false);
            $this->logger = new CUAR_Logger();
            $this->addon_manager = new CUAR_AddonManager($this->message_center);
            $this->cron = new CUAR_Cron();
        }

        public function run()
        {
            $this->message_center->register_hooks();
            $this->activation_manager->register_hooks();
            $this->addon_manager->register_hooks();
            $this->cron->register_hooks();

            add_action('plugins_loaded', [&$this, 'load_textdomain'], 3);
            add_action('plugins_loaded', [&$this, 'load_settings'], 5);
            add_action('plugins_loaded', [&$this, 'check_version'], 6);
            add_action('plugins_loaded', [&$this, 'load_addons'], 10);

            add_action('admin_enqueue_scripts', [&$this, 'load_admin_scripts'], 7);
            add_action('wp_enqueue_scripts', [&$this, 'load_frontend_scripts'], 7);
            add_action('admin_enqueue_scripts', [&$this, 'load_admin_styles'], 8);
            add_action('wp_enqueue_scripts', [&$this, 'load_frontend_styles'], 8);

            add_action('admin_init', [&$this, 'this_is_for_your_own_safety_really'], 1000);

            add_filter('single_template_hierarchy', [&$this, 'single_template_hierarchy'], 10);
            add_filter('page_template_hierarchy', [&$this, 'page_template_hierarchy'], 10);

            add_action('plugins_loaded', [&$this, 'load_theme_functions'], 7);

            if (is_admin())
            {
                add_action('admin_notices', [&$this, 'print_admin_notices']);

                add_action('permalink_structure_changed', [&$this, 'check_permalinks_enabled']);

                add_action('cuar/core/activation/run-deferred-action?action_id=check-template-files',
                    [&$this, 'check_templates']);
                add_action('cuar/core/activation/run-deferred-action?action_id=check-permalink-settings',
                    [&$this, 'check_permalinks_enabled']);
            }
        }

        /**
         * @return CUAR_Plugin
         */
        public static function get_instance()
        {
            global $cuar_plugin;

            return $cuar_plugin;
        }

        /**
         * @return CUAR_MessageCenter
         */
        public function get_message_center()
        {
            return $this->message_center;
        }

        /**
         * @return CUAR_TemplateEngine
         */
        public function get_template_engine()
        {
            return $this->template_engine;
        }

        /**
         * @return CUAR_Logger
         */
        public function get_logger()
        {
            return $this->logger;
        }

        /**
         * @return CUAR_Settings
         */
        public function get_settings()
        {
            return $this->settings;
        }

        /*------- MAIN HOOKS INTO WP ------------------------------------------------------------------------------------*/

        public function load_settings()
        {
            $this->settings = new CUAR_Settings($this);

            // Configure some components
            $this->template_engine->enable_debug($this->get_option(CUAR_Settings::$OPTION_DEBUG_TEMPLATES));
        }

        /**
         * Compare the version currently in database to the real plugin version. If not matching, then we should simulate an activation
         */
        public function check_version()
        {
            if (!is_admin())
            {
                return;
            }

            $active_version = CUAR_PLUGIN_VERSION;
            $current_version = $this->get_version();

            if ($active_version != $current_version)
            {
                CUAR_PluginActivationManager::on_activate();
            }
        }

        /**
         * Load the translation file for current language. Checks in wp-content/languages first
         * and then the customer-area/languages.
         *
         * Edits to translation files inside customer-area/languages will be lost with an update
         * **If you're creating custom translation files, please use the global language folder.**
         *
         * @param string $domain      The text domain
         * @param string $plugin_name The plugin folder name
         */
        public function load_textdomain($domain = 'cuar', $plugin_name = 'customer-area')
        {
            if (empty($domain))
            {
                $domain = 'cuar';
            }
            if (empty($plugin_name))
            {
                $plugin_name = 'customer-area';
            }

            $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();

            // Traditional WordPress plugin locale filter
            $locale = apply_filters('plugin_locale', $locale, $domain);
            $mo_file = sprintf('%1$s-%2$s.mo', $domain, $locale);
            $alt_mo_file = sprintf('%1$s-%2$s.mo', $plugin_name, $locale);

            $locations = [
                WP_CONTENT_DIR . '/customer-area/languages/' . $mo_file,
                WP_CONTENT_DIR . '/customer-area/languages/' . $alt_mo_file,
                WP_LANG_DIR . '/plugins/' . $mo_file,
                WP_LANG_DIR . '/plugins/' . $alt_mo_file,
                WP_LANG_DIR . '/' . $mo_file,
                WP_LANG_DIR . '/' . $alt_mo_file,
            ];

            // Try the user locations
            foreach ($locations as $path)
            {
                if (file_exists($path))
                {
                    load_textdomain($domain, $path);

                    return;
                }
            }

            // Not found above, load the default plugin file if it exists
            load_plugin_textdomain($domain, false, $plugin_name . '/languages');
        }

        /**
         * Loads the required javascript files (only when in admin area)
         */
        public function load_admin_scripts()
        {
            global $wp_locale;

            $lang = 'en';
            $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
            if ($locale && !empty($locale))
            {
                $locale = str_replace("_", "-", $locale);
                $locale_parts = explode("-", $locale);
                if (count($locale_parts) > 0)
                {
                    $lang = $locale_parts[0];
                }
            }

            // TODO Move those messages to their respective add-ons
            $messages = apply_filters('cuar/core/js-messages?zone=admin', [
                'isAdmin'                                  => true,
                'locale'                                   => $locale,
                'lang'                                     => $lang,
                'ajaxUrl'                                  => admin_url('admin-ajax.php'),
                'checkingLicense'                          => __('Checking license...', 'cuar'),
                'unreachableLicenseServerError'            => __('Failed to contact server', 'cuar'),
                'jeditableIndicator'                       => esc_attr__('Saving...', 'cuar'),
                'jeditableTooltip'                         => esc_attr__('Click to edit...', 'cuar'),
                'jeditableSubmit'                          => esc_attr__('OK', 'cuar'),
                'jeditableCancel'                          => esc_attr__('Cancel', 'cuar'),
                'datepickerDateFormat'                     => _x('MM d, yy', 'Date picker JS date format', 'cuar'),
                'datepickerCloseText'                      => _x('Clear', 'Date picker text', 'cuar'),
                'datepickerCurrentText'                    => _x('Today', 'Date picker text', 'cuar'),
                'datepickerMonthNames'                     => array_values($wp_locale->month),
                'datepickerMonthNamesShort'                => array_values($wp_locale->month_abbrev),
                'datepickerMonthStatus'                    => _x('Show a different month', 'Date picker text', 'cuar'),
                'datepickerDayNames'                       => array_values($wp_locale->weekday),
                'datepickerDayNamesShort'                  => array_values($wp_locale->weekday_abbrev),
                'datepickerDayNamesMin'                    => array_values($wp_locale->weekday_initial),
                'datepickerFirstDay'                       => get_option('start_of_week'),
                'datepickerIsRTL'                          => $wp_locale->is_rtl() ? true : false,
                'addressActionsCannotHandleMultipleOwners' => __('You must select only a single owner, multiple owners are not handled for this action.',
                    'cuar'),
                'addressActionsNeedAtLeastOneOwner'        => __('No owner is currently selected, the action cannot be executed.',
                    'cuar'),
            ]);
            wp_register_script(
                'cuar.admin',
                CUAR_PLUGIN_URL . 'assets/admin/js/customer-area.min.js',
                ['jquery', 'wp-color-picker', 'jquery-ui-datepicker'],
                $this->get_version());

            wp_localize_script('cuar.admin', 'cuar', $messages);
        }

        /**
         * Loads the required javascript files (only when not in admin area)
         */
        public function load_frontend_scripts()
        {
            global $wp_locale;

            $lang = 'en';
            $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
            if ($locale && !empty($locale))
            {
                $locale = str_replace("_", "-", $locale);
                $locale_parts = explode("-", $locale);
                if (count($locale_parts) > 0)
                {
                    $lang = $locale_parts[0];
                }
            }

            // TODO Move those messages to their respective add-ons
            $messages = apply_filters('cuar/core/js-messages?zone=frontend', [
                'isAdmin'                                  => false,
                'locale'                                   => $locale,
                'lang'                                     => $lang,
                'ajaxUrl'                                  => admin_url('admin-ajax.php'),
                'jeditableIndicator'                       => esc_attr__('Saving...', 'cuar'),
                'jeditableTooltip'                         => esc_attr__('Click to edit...', 'cuar'),
                'jeditableSubmit'                          => esc_attr__('OK', 'cuar'),
                'jeditableCancel'                          => esc_attr__('Cancel', 'cuar'),
                'datepickerDateFormat'                     => _x('MM d, yy', 'Date picker JS date format', 'cuar'),
                'datepickerCloseText'                      => _x('Clear', 'Date picker text', 'cuar'),
                'datepickerCurrentText'                    => _x('Today', 'Date picker text', 'cuar'),
                'datepickerMonthNames'                     => array_values($wp_locale->month),
                'datepickerMonthNamesShort'                => array_values($wp_locale->month_abbrev),
                'datepickerMonthStatus'                    => _x('Show a different month', 'Date picker text', 'cuar'),
                'datepickerDayNames'                       => array_values($wp_locale->weekday),
                'datepickerDayNamesShort'                  => array_values($wp_locale->weekday_abbrev),
                'datepickerDayNamesMin'                    => array_values($wp_locale->weekday_initial),
                'datepickerFirstDay'                       => get_option('start_of_week'),
                'datepickerIsRTL'                          => $wp_locale->is_rtl() ? true : false,
                'addressActionsCannotHandleMultipleOwners' => __('You must select only a single owner, multiple owners are not handled for this action.',
                    'cuar'),
                'addressActionsNeedAtLeastOneOwner'        => __('No owner is currently selected, the action cannot be executed.',
                    'cuar'),
            ]);
            wp_register_script('cuar.frontend', CUAR_PLUGIN_URL . 'assets/frontend/js/customer-area.min.js',
                ['jquery',
                 'jquery-ui-core',
                 'jquery-ui-draggable',
                 'jquery-ui-droppable',
                 'jquery-ui-sortable',
                 'jquery-ui-mouse',
                 'jquery-ui-widget'], $this->get_version());
            wp_localize_script('cuar.frontend', 'cuar', $messages);
        }

        /**
         * Loads the required css (only when in admin area)
         */
        public function load_admin_styles()
        {

            $screen = get_current_screen();

			if ($this->is_admin_area_page()
				|| (isset($screen) && isset($screen->id) && (
						$screen->id === 'user-edit' || $screen->id === 'profile')))
			{
                wp_enqueue_style('wp-color-picker');
                wp_enqueue_style(
                    'cuar.admin',
                    $this->get_admin_theme_url() . '/assets/css/styles.min.css',
                    [],
                    $this->get_version());
            }
            else
            {
                // When not on a page, we still need a little CSS for the menu separators
                add_action('admin_head', [$this, 'print_inline_admin_area_styles']);
            }
        }

        /**
         * Loads the required css (only when NOT in admin area)
         */
        public function load_frontend_styles()
        {
            if (!current_theme_supports('customer-area.stylesheet')
                && $this->get_option(CUAR_Settings::$OPTION_INCLUDE_CSS)
            )
            {
                wp_enqueue_style(
                    'cuar.frontend',
                    $this->get_frontend_theme_url() . '/assets/css/styles.min.css',
                    [],
                    $this->get_version());
            }
        }

        private function open_session()
        {
            $session = get_user_meta(get_current_user_id(), '__cuar_session', true);
            if (empty($session))
            {
                $session = [];
                $session['timestamp'] = time();
            }

            $opened_at = isset($session['timestamp']) ? $session['timestamp'] : 0;
            if (time()  - $opened_at > 24 * 60 * 60) {
                $session = [];
                $session['timestamp'] = time();
            }

            return $session;
        }

        public function get_session_var($key, $default = null)
        {
            $session = $this->open_session();
            return isset($session[$key]) ? $session[$key] : $default;
        }

        /**
         * @param string|array $key
         * @param null|mixed   $value
         */
        public function set_session_var($key, $value = null)
        {
            $session = $this->open_session();

            if (is_array($key))
            {
                foreach ($key as $k => $v)
                {
                    $session[$k] = $v;
                }
            }
            else
            {
                $session[$key] = $value;
            }

            update_user_meta(get_current_user_id(), '__cuar_session', $session);
        }

        public function unset_session_var($key)
        {
            $session = $this->open_session();
            unset($session[$key]);
            update_user_meta(get_current_user_id(), '__cuar_session', $session);
        }

        /**
         * Start a session when we save a post in order to store error logs
         */
        public function start_session()
        {
            if (version_compare(PHP_VERSION, '7.0.0') >= 0)
            {
                if (function_exists('session_status') && session_status() === PHP_SESSION_NONE)
                {
                    session_start([
                        'cache_limiter' => 'private_no_expire',
                    ]);
                }
            }
            else if (version_compare(PHP_VERSION, '5.4.0') >= 0)
            {
                if (function_exists('session_status') && session_status() === PHP_SESSION_NONE)
                {
                    session_cache_limiter('private_no_expire');
                    session_start();
                }
            }
            else
            {
                if (session_id() === '')
                {
                    if (version_compare(PHP_VERSION, '4.0.0') >= 0)
                    {
                        session_cache_limiter('private_no_expire');
                    }
                    session_start();
                }
            }
        }

        /**
         * @return bool True if we are on one of the admin area WP Customer Area pages
         */
        private function is_admin_area_page()
        {
            // About page
            if (isset($_GET['page']) && $_GET['page'] == 'wpca')
            {
                return true;
            }

            // Status, logs, settings and content listing pages
            if (isset($_GET['page']) && false !== strpos($_GET['page'], 'wpca-'))
            {
                return true;
            }

            // Post edition pages
            $managed_types = $this->get_managed_types();
            if (isset($_GET['post_type']) && array_key_exists($_GET['post_type'], $managed_types))
            {
                return true;
            }
            if (isset($_GET['post'])
                && get_post_type($_GET['post']) !== false
                && array_key_exists(get_post_type($_GET['post']), $managed_types))
            {
                return true;
            }

            return false;
        }

        public function print_inline_admin_area_styles()
        {
            ?>
            <style type="text/css" media="screen">
                #adminmenu #toplevel_page_wpca div.wp-menu-image:before,
                #wp-admin-bar-wpca > a:before {
                    content : "\f332";
                }

                #adminmenu span.cuar-menu-divider {
                    display     : block;
                    margin      : 0px 5px 12px 0px;
                    padding     : 0;
                    height      : 1px;
                    line-height : 1px;
                    background  : #666;
                    opacity     : 0.5;
                }
            </style>
            <?php
        }

        /*------- TEMPLATING & THEMING ----------------------------------------------------------------------------------*/

        public function page_template_hierarchy($templates)
        {
            /** @var CUAR_CustomerPagesAddOn $cp_addon */
            $cp_addon = $this->get_addon('customer-pages');
            $is_cuar_template = $cp_addon->is_customer_area_page(get_the_ID());

            if ($is_cuar_template)
            {
                array_splice($templates, count($templates) - 1, 0, 'cuar-page.php');
                array_splice($templates, count($templates) - 1, 0, 'cuar.php');
            }

            return $templates;
        }

        public function single_template_hierarchy($templates)
        {
            $is_cuar_template = false;
            for ($i = 0; $i < count($templates) - 2; ++$i)
            {
                if (strstr($templates[$i], 'cuar_'))
                {
                    $is_cuar_template = true;
                    break;
                };
            }

            if ($is_cuar_template)
            {
                array_splice($templates, count($templates) - 1, 0, 'cuar-single.php');
                array_splice($templates, count($templates) - 1, 0, 'cuar.php');
            }

            return $templates;
        }

        public function get_theme($theme_type)
        {
            return explode('%%', $this->get_option($theme_type == 'admin' ? CUAR_Settings::$OPTION_ADMIN_SKIN
                : CUAR_Settings::$OPTION_FRONTEND_SKIN));
        }

        public function get_theme_url($theme_type)
        {
            $theme = $this->get_theme($theme_type);

            if (count($theme) == 1)
            {
                // Still not on CUAR 4.0? Option value is already the URL
                return $theme[0];
            }
            else if (count($theme) == 2)
            {
                $base = '';
                switch ($theme[0])
                {
                    case 'plugin':
                        $base = untrailingslashit(CUAR_PLUGIN_URL) . '/skins/';
                        break;
                    case 'user-theme':
                        $base = untrailingslashit(get_stylesheet_directory_uri()) . '/customer-area/skins/';
                        break;
                    case 'wp-content':
                        $base = untrailingslashit(content_url()) . '/customer-area/skins/';
                        break;
                }

                return $base . $theme_type . '/' . $theme[1];
            }
            else if (count($theme) == 3)
            {
                // For addons
                // 0 = 'addon'
                // 1 = addon folder name
                // 2 = skin folder name
                switch ($theme[0])
                {
                    case 'addon':
                        return untrailingslashit(plugins_url()) . '/' . $theme[1] . '/skins/' . $theme_type . '/' . $theme[2];
                }
            }

            return '';
        }

        public function get_theme_path($theme_type)
        {
            $theme = $this->get_theme($theme_type);

            if (count($theme) === 1)
            {
                // Still not on CUAR 4.0? then we have a problem
                return '';
            }

            if (count($theme) === 2)
            {
                $base = '';
                switch ($theme[0])
                {
                    case 'plugin':
                        $base = untrailingslashit(CUAR_PLUGIN_DIR) . '/skins';
                        break;
                    case 'user-theme':
                        $base = untrailingslashit(get_stylesheet_directory()) . '/customer-area/skins';
                        break;
                    case 'wp-content':
                        $base = untrailingslashit(WP_CONTENT_DIR) . '/customer-area/skins';
                        break;
                }

                return $base . '/' . $theme_type . '/' . $theme[1];
            }

            if (count($theme) === 3)
            {
                // For addons
                // 0 = 'addon'
                // 1 = addon folder name
                // 2 = skin folder name
                switch ($theme[0])
                {
                    case 'addon':
                        return untrailingslashit(WP_PLUGIN_DIR) . '/' . $theme[1] . '/skins/' . $theme_type . '/' . $theme[2];
                }
            }

            return '';
        }

        public function get_admin_theme_url()
        {
            return $this->get_theme_url('admin');
        }

        /**
         * This function offers a way for addons to do their stuff after this plugin is loaded
         */
        public function get_frontend_theme_url()
        {
            return $this->get_theme_url('frontend');
        }

        /**
         * This function offers a way for addons to do their stuff after this plugin is loaded
         */
        public function get_frontend_theme_path()
        {
            return $this->get_theme_path('frontend');
        }

        public function load_theme_functions()
        {
            if (current_theme_supports('customer-area.stylesheet')
                || !$this->get_option(CUAR_Settings::$OPTION_INCLUDE_CSS)
            )
            {
                return;
            }

            $theme_path = trailingslashit($this->get_frontend_theme_path());
            if (empty($theme_path))
            {
                return;
            }

            $functions_path = $theme_path . 'cuar-functions.php';
            if (file_exists($functions_path))
            {
                include_once($functions_path);
            }
        }

        public function is_customer_area_page()
        {
            $cp_addon = $this->get_addon('customer-pages');

            return $cp_addon->is_customer_area_page();
        }

        public function get_customer_page_id($slug)
        {
            $cp_addon = $this->get_addon('customer-pages');

            return $cp_addon->get_page_id($slug);
        }

        public function login_then_redirect_to_page($page_slug)
        {
            $cp_addon = $this->get_addon('customer-pages');
            $redirect_page_id = $cp_addon->get_page_id($page_slug);
            if ($redirect_page_id > 0)
            {
                $redirect_url = get_permalink($redirect_page_id);
            }
            else
            {
                $redirect_url = '';
            }

            $this->login_then_redirect_to_url($redirect_url);
        }

        public function login_then_redirect_to_url($redirect_to = '')
        {
            $login_url = apply_filters('cuar/routing/login-url', null, $redirect_to);
            if ($login_url == null)
            {
                $login_url = wp_login_url($redirect_to);
            }

            wp_redirect($login_url);
            exit;
        }

        /*------- GENERAL MAINTENANCE -----------------------------------------------------------------------------------*/

        public function set_attention_needed($message_id, $message, $priority)
        {
            $this->message_center->add_warning($message_id, $message, $priority);
        }

        public function clear_attention_needed($message_id)
        {
            $this->message_center->remove_warning($message_id);
        }

        public function is_attention_needed($message_id)
        {
            return $this->message_center->is_warning_registered($message_id);
        }

        public function is_warning_ignored($warning_id)
        {
            return $this->message_center->is_warning_ignored($warning_id);
        }

        public function ignore_warning($warning_id)
        {
            $this->message_center->ignore_warning($warning_id);
        }

        public function get_attention_needed_messages()
        {
            return $this->message_center->get_warnings();
        }

        /*------- SETTINGS ----------------------------------------------------------------------------------------------*/

        /**
         * Access to the settings (delegated to our settings class instance)
         *
         * @param string $option_id The ID of the option to retrieve
         *
         * @return mixed The option value
         */
        public function get_option($option_id)
        {
            return $this->settings->get_option($option_id);
        }

        public function update_option($option_id, $new_value, $commit = true)
        {
            $this->settings->update_option($option_id, $new_value, $commit);
        }

        public function save_options()
        {
            $this->settings->save_options();
        }

        public function reset_defaults()
        {
            $this->settings->reset_defaults();
        }

        public function get_default_options()
        {
            return $this->settings->get_default_options();
        }

        public function get_version()
        {
            return $this->get_option(CUAR_Settings::$OPTION_CURRENT_VERSION);
        }

        public function get_major_version()
        {
            $tokens = explode('.', $this->get_version());

            return $tokens[0] . '.' . $tokens[1];
        }

        public function get_options()
        {
            return $this->settings->get_options();
        }

        public function set_options($opt)
        {
            $this->settings->set_options($opt);
        }

        /*------- ADD-ONS -----------------------------------------------------------------------------------------------*/

        /**
         * This function offers a way for addons to do their stuff after this plugin is loaded
         */
        public function load_addons()
        {
            do_action('cuar/core/addons/before-init', $this);
            do_action('cuar/core/addons/init', $this);
            do_action('cuar/core/addons/after-init', $this);
        }

        public function addon_manager()
        {
            return $this->addon_manager;
        }

        public function register_addon($addon)
        {
            $this->addon_manager->register_addon($addon);
        }

        public function get_registered_addons()
        {
            return $this->addon_manager->get_registered_addons();
        }

        public function tag_addon_as_commercial($addon_id)
        {
            $this->addon_manager->tag_addon_as_commercial($addon_id);
        }

        public function get_commercial_addons()
        {
            return $this->addon_manager->get_commercial_addons();
        }

        public function has_commercial_addons()
        {
            return $this->addon_manager->has_commercial_addons();
        }

        public function get_addon($id)
        {
            return $this->addon_manager->get_addon($id);
        }

        /*------- ADMIN NOTICES -----------------------------------------------------------------------------------------*/

        /**
         * Shows a compatibity warning
         */
        public function check_permalinks_enabled()
        {
            if (!get_option('permalink_structure'))
            {
                $this->set_attention_needed('permalinks-disabled',
                    sprintf(__('Permalinks are disabled, Customer Area will not work properly. Please <a href="%1$s">enable them in the WordPress settings</a>.',
                        'cuar'),
                        admin_url('options-permalink.php')),
                    10);
            }
            else
            {
                $this->clear_attention_needed('permalinks-disabled');
            }
        }

        /**
         * Print the eventual errors that occured during a post save/update
         */
        public function print_admin_notices()
        {
            $notices = $this->get_admin_notices();

            if ($notices)
            {
                foreach ($notices as $n)
                {
                    echo sprintf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($n['type']), $n['msg']);
                }
            }
            $this->clear_admin_notices();
        }

        /**
         * Remove the notices stored in the session for save posts
         */
        public function clear_admin_notices()
        {
            $this->unset_session_var('cuar_admin_notices');
        }

        /**
         * Remove the stored notices
         *
         * @return bool|array
         */
        private function get_admin_notices()
        {
            return $this->get_session_var('cuar_admin_notices', false);
        }

        /**
         * Add an admin notice (useful when in a save post function for example)
         *
         * @param string $msg
         * @param string $type error or updated
         * @param null   $key
         */
        public function add_admin_notice($msg, $type = 'error', $key = null)
        {
            $key = $key !== null ? $key : md5($msg);
            $notices = $this->get_session_var('cuar_admin_notices', []);
            $notices[$key] = [
                'type' => $type,
                'msg'  => $msg,
            ];
            $this->set_session_var('cuar_admin_notices', $notices);
        }

        /*------- EXTERNAL LIBRARIES ------------------------------------------------------------------------------------*/

        /**
         * Allow the use of an external library provided by Customer Area
         *
         * @param string $library_id The ID for the external library
         */
        public function enable_library($library_id)
        {
            // Only if the theme does not already support this and we are viewing the frontend
            if (!is_admin())
            {
                $theme_support = get_theme_support('customer-area.library.' . $library_id);
                if ($theme_support === true || (is_array($theme_support) && in_array('files',
                            $theme_support[0])))
                {
                    return;
                }
            }

            do_action('cuar/core/libraries/before-enable?id=' . $library_id);

            $cuar_version = $this->get_version();

            switch ($library_id)
            {
                case 'jquery.select2':
                {
                    wp_enqueue_script('jquery.select2', CUAR_PLUGIN_URL . 'libs/js/bower/select2/select2.min.js',
                        ['jquery'], $cuar_version);

                    $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
                    if ($locale && !empty($locale))
                    {
                        $locale = str_replace("_", "-", $locale);
                        $locale_parts = explode("-", $locale);

                        $loc_files = [$locale . '.js'];

                        if (count($locale_parts) > 0)
                        {
                            $loc_files[] = $locale_parts[0] . '.js';
                        }

                        foreach ($loc_files as $lf)
                        {
                            if (file_exists(CUAR_PLUGIN_DIR . '/libs/js/bower/select2/i18n/' . $lf))
                            {
                                wp_enqueue_script('jquery.select2.locale',
                                    CUAR_PLUGIN_URL . 'libs/js/bower/select2/i18n/' . $lf, ['jquery.select2'],
                                    $cuar_version);
                                break;
                            }
                        }
                    }

                    break;
                }

                case 'bootstrap.affix':
                {
                    wp_enqueue_script('bootstrap.affix', CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/affix.min.js',
                        ['jquery'], $cuar_version);
                    break;
                }

                case 'bootstrap.alert':
                {
                    wp_enqueue_script('bootstrap.alert', CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/alert.min.js',
                        ['jquery'], $cuar_version);
                    break;
                }

                case 'bootstrap.button':
                {
                    wp_enqueue_script('bootstrap.button', CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/button.min.js',
                        ['jquery'], $cuar_version);
                    break;
                }

                case 'bootstrap.carousel':
                {
                    wp_enqueue_script('bootstrap.carousel',
                        CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/carousel.min.js', ['jquery'], $cuar_version);
                    break;
                }

                case 'bootstrap.collapse':
                {
                    wp_enqueue_script('bootstrap.collapse',
                        CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/collapse.min.js', ['jquery'], $cuar_version);
                    break;
                }

                case 'bootstrap.dropdown':
                {
                    wp_enqueue_script('bootstrap.transition',
                        CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/transition.min.js', ['jquery'],
                        $cuar_version);
                    wp_enqueue_script('bootstrap.dropdown',
                        CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/dropdown.min.js',
                        ['jquery', 'bootstrap.transition'],
                        $cuar_version);
                    break;
                }

                case 'bootstrap.modal':
                {
                    wp_enqueue_script('bootstrap.modal', CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/modal.min.js',
                        ['jquery'], $cuar_version);
                    break;
                }

                case 'bootstrap.popover':
                {
                    wp_enqueue_script('bootstrap.tooltip',
                        CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/tooltip.min.js', ['jquery'], $cuar_version);
                    wp_enqueue_script('bootstrap.popover',
                        CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/popover.min.js', ['jquery', 'bootstrap.tooltip'],
                        $cuar_version);
                    break;
                }

                case 'bootstrap.scrollspy':
                {
                    wp_enqueue_script('bootstrap.scrollspy',
                        CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/scrollspy.min.js', ['jquery'], $cuar_version);
                    break;
                }

                case 'bootstrap.tab':
                {
                    wp_enqueue_script('bootstrap.tab', CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/tab.min.js',
                        ['jquery'], $cuar_version);
                    break;
                }

                case 'bootstrap.tooltip':
                {
                    wp_enqueue_script('bootstrap.tooltip',
                        CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/tooltip.min.js', ['jquery'], $cuar_version);
                    break;
                }

                case 'bootstrap.transition':
                {
                    wp_enqueue_script('bootstrap.transition',
                        CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/transition.min.js', ['jquery'],
                        $cuar_version);
                    break;
                }

                case 'bootstrap.slider':
                {
                    wp_enqueue_script('bootstrap.slider',
                        CUAR_PLUGIN_URL . 'libs/js/bower/bootstrap-slider/bootstrap-slider.min.js', ['jquery'],
                        $cuar_version, true);
                    break;
                }

                case 'summernote':
				{
					wp_register_script('summernote', CUAR_PLUGIN_URL . 'libs/js/bower/summernote/summernote.min.js',
						['jquery', 'bootstrap.tooltip', 'bootstrap.popover', 'bootstrap.modal'], $cuar_version);

					$options = apply_filters('cuar/core/js-richEditor', [
						"options" => [
							"container" => '#cuar-js-rich-editor-wrapper>.note-editor',

							/*
							 * Toolbar buttons
							 */
							"toolbar" => [
								['block', ['style']],
								['style', ['bold', 'italic', 'underline', 'clear']],
								['cleaner', ['cleaner']],
								['font', ['strikethrough', 'superscript', 'subscript']],
								['para', ['ul', 'ol', 'listStyles', 'paragraph']],
								['insert', ['table', 'hr', 'picture', 'link', 'embed']],
								['view', ['codeview', 'fullscreen']],
								['tools', ['undo', 'redo', 'help']],
							],

							/*
							 * Available popovers
							 */
							"popover" => [
								"image" => [
									['custom', ['imageAttributes']],
									['imagesize', ['imageSize100', 'imageSize50', 'imageSize25']],
									['float', ['floatLeft', 'floatRight', 'floatNone']],
									['custom', ['deleteImage']],
								],
							],

							/*
							 * Image Attributes popover options
							 */
							"imageAttributes" => [
								"icon" => '<i class="note-icon-pencil"/>',
								"removeEmpty" => false,
								"disableUpload" => true,
							],

							/*
							 * Autolink: convert links automatically
							 */
							"autolink" => false,

							/*
							 * Embed button
							 */
							"embed" => [
								"title" => __("Embed", "cuar"),
								"label" => __("Content URL", "cuar"),
								"button" => esc_attr__("Insert media", "cuar"),
								"description" => sprintf(__('%1sList of sites you can embed from%2s', "cuar"),
									'<a href="https://wordpress.org/documentation/article/embeds/#list-of-sites-you-can-embed-from" target="_BLANK">',
									"</a>"),
							],

							/*
							 * Clean HTML when pasting and clicking the clean button
							 */
							"cleaner" => [
								// both|button|paste 'button' only cleans via toolbar button, 'paste' only clean when pasting content, both does both options.
								"action" => 'paste',
								// Summernote's default is to use '<p><br></p>', should use '<div>\n</div>' for WP
								"newline" => "<div>\n</div>",
								"notStyle" => 'position:absolute;top:0;left:0;right:0',
								"icon" => '<i class="fa fa-pencil"/>',
								// Remove all Html formats if true
								"keepHtml" => true,
								// If keepHtml is true, remove all tags except these
								"keepOnlyTags" => [
									'<br>',
									/*
									'<h1>',
									'<h2>',
									'<h3>',
									'<h4>',
									'<h5>',
									'<h6>',
									'<a>',
									'<b>',
									'<blockquote>',
									'<abbr>',
									'<pre>',
									'<code>',
									'<button>',
									'<caption>',
									'<cite>',
									'<head>',
									'<header>',
									'<hgroup>',
									'<hr>',
									'<i>',
									'<label>',
									'<legend>',
									'<li>',
									'<ul>',
									'<ol>',
									'<p>',
									'<small>',
									'<span>',
									'<strong>',
									'<sub>',
									'<sup>',
									'<table>',
									'<tbody>',
									'<td>',
									'<textarea>',
									'<tfoot>',
									'<th>',
									'<thead>',
									'<time>',
									'<tr>',
									'<div>',
									'<ins>',
									*/
								],
								// Remove Classes if false
								"keepClasses" => false,
								// Remove full tags with contents
								"badTags" => [
									'iframe',
									'frame',
									'script',
									'link',
									'meta',
									'applet',
									'bgsound',
									'embed',
									'noframes',
									'noscript',
								],
								// Remove attributes from remaining tags
								"badAttributes" => ['style', 'start', 'dir', 'class'],
								// 0|# 0 disables option
								"limitChars" => 0,
								// none|text|html|both
								"limitDisplay" => 'none',
								// true/false
								"limitStop" => false,
							],
						],
						"translations" => [
							"font" => [
								"bold" => __("Bold", "cuar"),
								"italic" => __("Italic", "cuar"),
								"underline" => __("Underline", "cuar"),
								"clear" => __("Remove Font Style", "cuar"),
								"height" => __("Line Height", "cuar"),
								"name" => __("Font Family", "cuar"),
								"strikethrough" => __("Strikethrough", "cuar"),
								"subscript" => __("Subscript", "cuar"),
								"superscript" => __("Superscript", "cuar"),
								"size" => __("Font Size", "cuar"),
								"sizeunit" => __("Font Size Unit", "cuar"),
							],
							"image" => [
								"image" => __("Picture", "cuar"),
								"insert" => __("Insert Image", "cuar"),
								"resizeFull" => __("Resize full", "cuar"),
								"resizeHalf" => __("Resize half", "cuar"),
								"resizeQuarter" => __("Resize quarter", "cuar"),
								"resizeNone" => __("Original size", "cuar"),
								"floatLeft" => __("Float Left", "cuar"),
								"floatRight" => __("Float Right", "cuar"),
								"floatNone" => __("Remove float", "cuar"),
								"shapeRounded" => __("Shape: Rounded", "cuar"),
								"shapeCircle" => __("Shape: Circle", "cuar"),
								"shapeThumbnail" => __("Shape: Thumbnail", "cuar"),
								"shapeNone" => __("Shape: None", "cuar"),
								"dragImageHere" => __("Drag image or text here", "cuar"),
								"dropImage" => __("Drop image or Text", "cuar"),
								"selectFromFiles" => __("Select from files", "cuar"),
								"maximumFileSize" => __("Maximum file size", "cuar"),
								"maximumFileSizeError" => __("Maximum file size exceeded.", "cuar"),
								"url" => __("Image URL", "cuar"),
								"remove" => __("Remove Image", "cuar"),
								"original" => __("Original", "cuar"),
							],
							"video" => [
								"video" => __("Video", "cuar"),
								"videoLink" => __("Video Link", "cuar"),
								"insert" => __("Insert Video", "cuar"),
								"url" => __("Video URL", "cuar"),
								"providers" => __("(YouTube, Google Drive, Vimeo, Vine, Instagram, DailyMotion, Youku, Peertube)", "cuar"),
							],
							"link" => [
								"link" => __("Link", "cuar"),
								"insert" => __("Insert Link", "cuar"),
								"unlink" => __("Unlink", "cuar"),
								"edit" => __("Edit", "cuar"),
								"textToDisplay" => __("Text to display", "cuar"),
								"url" => __("To what URL should this link go?", "cuar"),
								"openInNewWindow" => __("Open in new window", "cuar"),
								"useProtocol" => __("Use default protocol", "cuar"),
							],
							"table" => [
								"table" => __("Table", "cuar"),
								"addRowAbove" => __("Add row above", "cuar"),
								"addRowBelow" => __("Add row below", "cuar"),
								"addColLeft" => __("Add column left", "cuar"),
								"addColRight" => __("Add column right", "cuar"),
								"delRow" => __("Delete row", "cuar"),
								"delCol" => __("Delete column", "cuar"),
								"delTable" => __("Delete table", "cuar"),
							],
							"hr" => [
								"insert" => __("Insert Horizontal Rule", "cuar"),
							],
							"style" => [
								"style" => __("Style", "cuar"),
								"p" => __("Normal", "cuar"),
								"blockquote" => __("Quote", "cuar"),
								"pre" => __("Code", "cuar"),
								"h1" => __("Header 1", "cuar"),
								"h2" => __("Header 2", "cuar"),
								"h3" => __("Header 3", "cuar"),
								"h4" => __("Header 4", "cuar"),
								"h5" => __("Header 5", "cuar"),
								"h6" => __("Header 6", "cuar"),
							],
							"lists" => [
								"unordered" => __("Unordered list", "cuar"),
								"ordered" => __("Ordered list", "cuar"),
							],
							"options" => [
								"help" => __("Help", "cuar"),
								"fullscreen" => __("Full Screen", "cuar"),
								"codeview" => __("Code View", "cuar"),
							],
							"paragraph" => [
								"paragraph" => __("Paragraph", "cuar"),
								"outdent" => __("Outdent", "cuar"),
								"indent" => __("Indent", "cuar"),
								"left" => __("Align left", "cuar"),
								"center" => __("Align center", "cuar"),
								"right" => __("Align right", "cuar"),
								"justify" => __("Justify full", "cuar"),
							],
							"color" => [
								"recent" => __("Recent Color", "cuar"),
								"more" => __("More Color", "cuar"),
								"background" => __("Background Color", "cuar"),
								"foreground" => __("Text Color", "cuar"),
								"transparent" => __("Transparent", "cuar"),
								"setTransparent" => __("Set transparent", "cuar"),
								"reset" => __("Reset", "cuar"),
								"resetToDefault" => __("Reset to default", "cuar"),
								"cpSelect" => __("Select", "cuar"),
							],
							"shortcut" => [
								"shortcuts" => __("Keyboard shortcuts", "cuar"),
								"close" => __("Close", "cuar"),
								"textFormatting" => __("Text formatting", "cuar"),
								"action" => __("Action", "cuar"),
								"paragraphFormatting" => __("Paragraph formatting", "cuar"),
								"documentStyle" => __("Document Style", "cuar"),
								"extraKeys" => __("Extra keys", "cuar"),
							],
							"help" => [
								"escape" => __("Escape", "cuar"),
								"insertParagraph" => __("Insert Paragraph", "cuar"),
								"undo" => __("Undo the last command", "cuar"),
								"redo" => __("Redo the last command", "cuar"),
								"tab" => __("Tab", "cuar"),
								"untab" => __("Untab", "cuar"),
								"bold" => __("Set a bold style", "cuar"),
								"italic" => __("Set a italic style", "cuar"),
								"underline" => __("Set a underline style", "cuar"),
								"strikethrough" => __("Set a strikethrough style", "cuar"),
								"removeFormat" => __("Clean a style", "cuar"),
								"justifyLeft" => __("Set left align", "cuar"),
								"justifyCenter" => __("Set center align", "cuar"),
								"justifyRight" => __("Set right align", "cuar"),
								"justifyFull" => __("Set full align", "cuar"),
								"insertUnorderedList" => __("Toggle unordered list", "cuar"),
								"insertOrderedList" => __("Toggle ordered list", "cuar"),
								"outdent" => __("Outdent on current paragraph", "cuar"),
								"indent" => __("Indent on current paragraph", "cuar"),
								"formatPara" => __("Change current block's format as a paragraph(P tag)", "cuar"),
								"formatH1" => __("Change current block's format as H1", "cuar"),
								"formatH2" => __("Change current block's format as H2", "cuar"),
								"formatH3" => __("Change current block's format as H3", "cuar"),
								"formatH4" => __("Change current block's format as H4", "cuar"),
								"formatH5" => __("Change current block's format as H5", "cuar"),
								"formatH6" => __("Change current block's format as H6", "cuar"),
								"insertHorizontalRule" => __("Insert horizontal rule", "cuar"),
								"linkDialog.show" => __("Show Link Dialog", "cuar"),
							],
							"history" => [
								"undo" => __("Undo", "cuar"),
								"redo" => __("Redo", "cuar"),
							],
							"specialChar" => [
								"specialChar" => __("SPECIAL CHARACTERS", "cuar"),
								"select" => __("Select Special characters", "cuar"),
							],
							"output" => [
								"noSelection" => __("No Selection Made!", "cuar"),
							],
							"listStyleTypes" => [
								"tooltip" => esc_attr__("List Styles", "cuar"),
								"labelsListStyleTypes" => [
									__("Numbered", "cuar"),
									__("Lower Alpha", "cuar"),
									__("Upper Alpha", "cuar"),
									__("Lower Roman", "cuar"),
									__("Upper Roman", "cuar"),
									__("Disc", "cuar"),
									__("Circle", "cuar"),
									__("Square", "cuar"),
								],
							],
							"imageUpload" => [
								"serverUnreachable" => __("We could not get a proper answer from the server, please contact site administrator.", "cuar"),
								"imageIsNotImg" => __("The type of file you tried to upload is not an image.", "cuar"),
							],
							"deleteImage" => [
								"tooltip" => __("Delete image", "cuar"),
							],
							"imageAttributes" => [
								"dialogTitle" => esc_attr__("Image Attributes", "cuar"),
								"tooltip" => __("Image Attributes", "cuar"),
								"tabImage" => __("Image", "cuar"),
								"src" => __("Source", "cuar"),
								"browse" => __("Browse", "cuar"),
								"title" => __("Title", "cuar"),
								"alt" => __("Alt Text", "cuar"),
								"dimensions" => __("Dimensions", "cuar"),
								"tabAttributes" => __("Attributes", "cuar"),
								"class" => __("Class", "cuar"),
								"style" => __("Style", "cuar"),
								"role" => __("Role", "cuar"),
								"tabLink" => __("Link", "cuar"),
								"linkHref" => __("URL", "cuar"),
								"linkTarget" => __("Target", "cuar"),
								"linkTargetInfo" => __("Options: _self, _blank, _top, _parent", "cuar"),
								"linkClass" => __("Class", "cuar"),
								"linkStyle" => __("Style", "cuar"),
								"linkRel" => __("Rel", "cuar"),
								"linkRelInfo" => __("Options: alternate, author, bookmark, help, license, next, nofollow, noreferrer, prefetch, prev, search, tag", "cuar"),
								"linkRole" => __("Role", "cuar"),
								"tabUpload" => __("Upload", "cuar"),
								"upload" => __("Upload", "cuar"),
								"tabBrowse" => __("Browse", "cuar"),
								"editBtn" => __("OK", "cuar"),
							],
							"cleaner" => [
								"tooltip" => esc_attr__("Cleaner", "cuar"),
								"not" => __("Text has been Cleaned!!!", "cuar"),
								"limitText" => __("Text", "cuar"),
								"limitHTML" => __("HTML", "cuar"),
							],
						],
					]);
					wp_localize_script('summernote', 'cuarSummernoteOptions', $options);

                    wp_enqueue_script('bootstrap.tooltip',
                        CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/tooltip.min.js', ['jquery'], $cuar_version);
                    wp_enqueue_script('bootstrap.popover',
                        CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/popover.min.js', ['jquery', 'bootstrap.tooltip'],
                        $cuar_version);
                    wp_enqueue_script('bootstrap.modal', CUAR_PLUGIN_URL . 'libs/js/framework/bootstrap/modal.min.js',
                        ['jquery'], $cuar_version);
                    wp_enqueue_script('summernote');
                    wp_enqueue_script('summernote-image-attributes',
                        CUAR_PLUGIN_URL . 'libs/js/bower/summernote-image-attributes/summernote-image-attributes.min.js',
                        ['jquery', 'bootstrap.tooltip', 'bootstrap.popover', 'bootstrap.modal', 'summernote'],
                        $cuar_version);

                    break;
                }

                case 'jquery.datepicker':
                {
                    wp_enqueue_script('jquery-ui-datepicker');
                    break;
                }

                case 'jquery.cookie':
                {
                    wp_enqueue_script('jquery.cookie',
                        CUAR_PLUGIN_URL . 'libs/js/bower/jquery-cookie/jquery.cookie.min.js', ['jquery'],
                        $cuar_version);
                    break;
                }

                case 'jquery.repeatable-fields':
                {
                    wp_enqueue_script('jquery.repeatable-fields',
                        CUAR_PLUGIN_URL . 'libs/js/other/repeatable-fields-master/repeatable-fields.min.js',
                        ['jquery', 'jquery-ui-sortable'], $cuar_version);
                    break;
                }

                case 'jquery.mixitup':
                {
                    wp_enqueue_script('jquery.magnificpopups',
                        CUAR_PLUGIN_URL . 'libs/js/framework/magnific/jquery.magnific-popup.min.js', ['jquery'],
                        $cuar_version);
                    wp_enqueue_script('jquery.mixitup',
                        CUAR_PLUGIN_URL . 'libs/js/framework/mixitup/jquery.mixitup.min.js',
                        ['jquery', 'jquery.magnificpopups'], $cuar_version);
                    break;
                }

                case 'jquery.masonry': {
                    wp_enqueue_script('jquery-masonry');
                    break;
                }

                case 'jquery.autogrow': {
                    wp_enqueue_script('jquery.autogrow', CUAR_PLUGIN_URL . 'libs/js/other/autogrow/autogrow.min.js', array('jquery'), $cuar_version);
                    break;
                }

                case 'jquery.jeditable':
                {
                    wp_enqueue_script('jquery.autogrow', CUAR_PLUGIN_URL . 'libs/js/other/autogrow/autogrow.min.js',
                        ['jquery'], $cuar_version);
                    wp_enqueue_script('jquery.jeditable',
                        CUAR_PLUGIN_URL . 'libs/js/other/jeditable/jquery.jeditable.min.js', ['jquery'], $cuar_version);
                    wp_enqueue_script('jquery.jeditable.autogrow',
                        CUAR_PLUGIN_URL . 'libs/js/other/jeditable/jquery.jeditable.autogrow.min.js',
                        ['jquery', 'jquery.jeditable', 'jquery.autogrow'], $cuar_version);
                    wp_enqueue_script('jquery.jeditable.datepicker',
                        CUAR_PLUGIN_URL . 'libs/js/other/jeditable/jquery.jeditable.datepicker.min.js',
                        ['jquery', 'jquery.jeditable', 'jquery-ui-datepicker'], $cuar_version);
                    break;
                }

                case 'jquery.fileupload':
                {
                    wp_enqueue_script('jquery.ui.widget',
                        CUAR_PLUGIN_URL . 'libs/js/bower/file-upload/vendor/jquery.ui.widget.min.js', ['jquery'],
                        $cuar_version);
                    wp_enqueue_script('jquery.iframe-transport',
                        CUAR_PLUGIN_URL . 'libs/js/bower/file-upload/jquery.iframe-transport.min.js', ['jquery'],
                        $cuar_version);
                    wp_enqueue_script('jquery.fileupload',
                        CUAR_PLUGIN_URL . 'libs/js/bower/file-upload/jquery.fileupload.min.js', ['jquery'],
                        $cuar_version);
                    break;
                }

                case 'jquery.steps':
                {
                    wp_enqueue_script('jquery.steps',
                        CUAR_PLUGIN_URL . 'libs/js/bower/jquery-steps/jquery.steps.min.js', ['jquery'],
                        $cuar_version);
                    break;
                }

                case 'jquery.slick':
                {
                    wp_enqueue_script('jquery.slick',
                        CUAR_PLUGIN_URL . 'libs/js/bower/slick-carousel/jquery.slick.min.js',
                        ['jquery', 'jquery-migrate'],
                        $cuar_version);
                    break;
                }

                case 'jquery.fancytree':
                {
                    wp_enqueue_script('jquery.fancytree',
                        CUAR_PLUGIN_URL . 'libs/js/bower/fancytree/jquery.fancytree.min.js',
                        ['jquery', 'jquery-ui-core', 'jquery-ui-widget'],
                        $cuar_version);
                    break;
                }

                case 'html2pdf':
                {
                    include_once(trailingslashit(CUAR_PLUGIN_DIR) . 'libs/php/vendor/autoload.php');
                    break;
                }

                default:
                    do_action('cuar/core/libraries/enable?id=' . $library_id);
            }

            do_action('cuar/core/libraries/after-enable?id=' . $library_id);
        }

        /*------- TEMPLATES ---------------------------------------------------------------------------------------------*/

        /**
         * Check all template files and log a warning if there are any outdated templates
         */
        public function check_templates()
        {
            $dirs_to_scan = apply_filters('cuar/core/status/directories-to-scan',
                [CUAR_PLUGIN_DIR => __('WP Customer Area', 'cuar')]);

            $outdated_templates = $this->template_engine->check_templates($dirs_to_scan);

            if (!empty($outdated_templates))
            {
                $this->set_attention_needed('outdated-templates',
                    __('Some template files you have overridden seem to be outdated.', 'cuar'), 100);
            }
            else
            {
                $this->clear_attention_needed('outdated-templates');
            }
        }

        /**
         * Delegate function for the template engine
         */
        public function get_template_file_path($default_root, $filenames, $relative_path = 'templates',
                                               $fallback_filename = '')
        {
            if (!is_array($filenames))
            {
                $filenames = [$filenames];
            }
            if (!empty($fallback_filename))
            {
                $filenames[] = $fallback_filename;
            }

            return $this->template_engine->get_template_file_path($default_root, $filenames, $relative_path);
        }

        /*------- OTHER FUNCTIONS ---------------------------------------------------------------------------------------*/

        public function this_is_for_your_own_safety_really()
        {
            if (function_exists('file_get_contents_ccode') || function_exists('ccode_page'))
            {
                echo '<div class="error"><p>';
                echo sprintf(
                    __('The WP Customer Area failed to initialize. Please contact us at <a href="mailto://%1$s">%1$s</a>.',
                        'cuar'),
                    'shop@wp-customerarea.com'
                );
                echo '</p></div>';
            }
        }

        /**
         * Tell if the post type is managed by the plugin or not (used to build the menu, etc.)
         *
         * @param string $post_type     The post type to check
         * @param array  $private_types The private types of the plugin (null if you simply want the plugin to fetch them
         *                              dynamically
         *
         * @return bool
         */
        public function is_type_managed($post_type, $private_types = null)
        {
            if ($private_types == null)
            {
                $private_types = $this->get_managed_types();
            }

            return apply_filters('cuar/core/types/is-type-managed',
                isset($private_types[$post_type]), $post_type, $private_types);
        }

        /**
         * Get both private content and container types
         *
         * @return array
         */
        public function get_managed_types()
        {
            $other_types = apply_filters('cuar/core/post-types/other', []);

            return array_merge(
                $this->get_content_types(),
                $this->get_container_types(),
                $other_types
            );
        }

        /**
         * Get both private content and container types
         *
         * @return array
         */
        public function get_private_types()
        {
            return array_merge(
                $this->get_content_types(),
                $this->get_container_types()
            );
        }

        /**
         * Get both private content and container types
         *
         * @return array
         */
        public function get_private_post_types()
        {
            return array_merge(
                $this->get_content_post_types(),
                $this->get_container_post_types()
            );
        }

        /**
         * Tells which post types are private (shown on the customer area page)
         *
         * @return array
         */
        public function get_content_post_types()
        {
            return apply_filters('cuar/core/post-types/content', []);
        }

        /**
         * Get the content types descriptors. Each descriptor is an array with:
         * - 'label-plural'                - plural label
         * - 'label-singular'            - singular label
         * - 'content-page-addon'        - content page addon associated to this type
         * - 'type'                     - 'content'
         *
         * @return array keys are post_type and values are arrays as described above
         */
        public function get_content_types()
        {
            return apply_filters('cuar/core/types/content', []);
        }

        /**
         * Tells which container post types are available
         *
         * @return array
         */
        public function get_container_post_types()
        {
            return apply_filters('cuar/core/post-types/container', []);
        }

        /**
         * Get the post type descriptors. Each descriptor is an array with:
         * - 'label-plural'                - plural label
         * - 'label-singular'            - singular label
         * - 'container-page-slug'        - main page slug associated to this type
         * - 'type'                     - 'container'
         *
         * @return array
         */
        public function get_container_types()
        {
            return apply_filters('cuar/core/types/container', []);
        }

        /**
         * @deprecated Since we use summernote now
         */
        public function get_default_wp_editor_settings()
        {
            return apply_filters('cuar/ui/default-wp-editor-settings', [
                'textarea_rows' => 5,
                'editor_class'  => 'form-control',
                'quicktags'     => false,
                'media_buttons' => false,
                'teeny'         => true,
                'dfw'           => true,
            ]);
        }
    }

endif; // if (!class_exists('CUAR_Plugin')) :
