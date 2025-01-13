<?php
/**
 * Plugin Name: CareHQ NinjaForms Integration
 * Description: Integrates NinjaForms submissions with CareHQ CRM
 * Version: 1.0.0
 * Author: Andy Place
 * Author URI: https://www.andyplace.co.uk
 * Plugin URI: https://github.com/andyplace/carehq-ninja-forms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if Composer autoload exists
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}


class CareHQ_NinjaForms_Integration {
    private $options;
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_filter('ninja_forms_register_actions', array($this, 'ninja_forms_register_actions'));

        // Load options
        $this->options = get_option('carehq_integration_options');
    }

    public function ninja_forms_register_actions($actions) {
        require_once plugin_dir_path(__FILE__) . 'includes/Actions/CareHQ.php';
        $actions['carehq'] = new NF_Actions_CareHQ();
        return $actions;
    }

    public function add_plugin_page() {
        add_options_page(
            'CareHQ Integration Settings',
            'CareHQ Integration',
            'manage_options',
            'carehq-integration',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h2>CareHQ Integration Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('carehq_integration_group');
                do_settings_sections('carehq-integration');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {

        // Check if Ninja Forms is active
        if (!class_exists('Ninja_Forms')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . __('Ninja Forms must be installed and activated for the CareHQ Integration to work.', 'carehq-ninja-forms') . '</p></div>';
            });
            return;
        }

        register_setting(
            'carehq_integration_group',
            'carehq_integration_options',
            array($this, 'sanitize')
        );

        add_settings_section(
            'carehq_integration_section',
            'API Settings',
            array($this, 'section_info'),
            'carehq-integration'
        );

        add_settings_field(
            'account_id',
            'Account ID',
            array($this, 'account_id_callback'),
            'carehq-integration',
            'carehq_integration_section'
        );

        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'carehq-integration',
            'carehq_integration_section'
        );

        add_settings_field(
            'api_secret',
            'API Secret',
            array($this, 'api_secret_callback'),
            'carehq-integration',
            'carehq_integration_section'
        );
    }

    public function sanitize($input) {
        $new_input = array();

        if(isset($input['account_id']))
            $new_input['account_id'] = sanitize_text_field($input['account_id']);

        if(isset($input['api_key']))
            $new_input['api_key'] = sanitize_text_field($input['api_key']);

        if(isset($input['api_secret']))
            $new_input['api_secret'] = sanitize_text_field($input['api_secret']);

        return $new_input;
    }

    public function section_info() {
        print 'Enter your CareHQ API credentials below:';
    }

    public function account_id_callback() {
        printf(
            '<input type="text" id="account_id" name="carehq_integration_options[account_id]" value="%s" class="regular-text" />',
            isset($this->options['account_id']) ? esc_attr($this->options['account_id']) : ''
        );
    }

    public function api_key_callback() {
        printf(
            '<input type="text" id="api_key" name="carehq_integration_options[api_key]" value="%s" class="regular-text" />',
            isset($this->options['api_key']) ? esc_attr($this->options['api_key']) : ''
        );
    }

    public function api_secret_callback() {
        printf(
            '<input type="password" id="api_secret" name="carehq_integration_options[api_secret]" value="%s" class="regular-text" />',
            isset($this->options['api_secret']) ? esc_attr($this->options['api_secret']) : ''
        );
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('CareHQ_NinjaForms_Integration', 'get_instance'));
