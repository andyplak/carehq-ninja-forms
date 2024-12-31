<?php
/*
Plugin Name: NinjaForms CareHQ Integration
Description: Integrates NinjaForms submissions with CareHQ CRM
Version: 1.0.0
Author: Andy Place
Author URI: https://www.andyplace.co.uk
Plugin URI: https://github.com/andyplace/ninjaforms-carehq-integration
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if Ninja Forms is active
if (!class_exists('Ninja_Forms')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>' . __('Ninja Forms must be installed and activated for the CareHQ Integration to work.', 'ninja-forms-carehq') . '</p></div>';
    });
    return;
}

// Check if Composer autoload exists
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

use CareHQ\CareHQ\CareHQ;

class NinjaForms_CareHQ_Integration {
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
        add_filter('ninja_forms_submit_data', array($this, 'handle_form_submission'), 10, 1);

        // Load options
        $this->options = get_option('carehq_integration_options');
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

    public function handle_form_submission($form_data) {
        try {
            // Initialize CareHQ client
            $client = new CareHQ(
                $this->options['api_key'],
                $this->options['api_secret'],
                $this->options['account_id']
            );

            // Get form settings (you'll need to implement this based on your needs)
            $form_id = $form_data['form_id'];
            $form_settings = get_option('carehq_form_settings_' . $form_id);

            if (!$form_settings) {
                return $form_data;
            }

            // Map form fields to CareHQ fields
            $contact_data = array();
            foreach ($form_data['fields'] as $field) {
                // Map fields based on form settings
                // This is a basic example - you'll need to implement proper field mapping
                switch ($field['key']) {
                    case 'email':
                        $contact_data['email'] = $field['value'];
                        break;
                    case 'name':
                        $contact_data['name'] = $field['value'];
                        break;
                    // Add more field mappings as needed
                }
            }

            // Create contact in CareHQ
            $response = $client->contacts()->create($contact_data);

            // Log success
            error_log('Contact created in CareHQ: ' . print_r($response, true));

        } catch (Exception $e) {
            // Log error
            error_log('CareHQ API Error: ' . $e->getMessage());
        }

        return $form_data;
    }
}

// Add form-specific settings to Ninja Forms
add_filter('ninja_forms_register_fields', 'add_carehq_form_settings');

function add_carehq_form_settings($fields) {
    $fields['carehq_settings'] = array(
        'name' => 'CareHQ Settings',
        'type' => 'fieldset',
        'label' => 'CareHQ Integration Settings',
        'settings' => array(
            'location' => array(
                'name' => 'location',
                'type' => 'textbox',
                'label' => 'CareHQ Location ID',
                'width' => 'full',
                'group' => 'primary',
                'value' => ''
            ),
            'group' => array(
                'name' => 'group',
                'type' => 'textbox',
                'label' => 'CareHQ Group ID',
                'width' => 'full',
                'group' => 'primary',
                'value' => ''
            )
        )
    );

    return $fields;
}

// Initialize the plugin
add_action('plugins_loaded', array('NinjaForms_CareHQ_Integration', 'get_instance'));
