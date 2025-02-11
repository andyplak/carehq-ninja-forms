<?php

use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH')) exit;

/**
 * Class NF_Actions_CareHQ
 */
class NF_Actions_CareHQ  extends SotAction implements InterfacesSotAction
{
    use SotGetActionProperties;

    /**
     * @var array
     */
    protected $_tags = array();


    public function __construct()
    {
        parent::__construct();

        $this->_name  = 'carehq';
        $this->_timing = 'late';
        $this->_priority = 30;
        $this->_documentation_url = '';
        $this->_group = 'core';

        add_action('init', [$this, 'initHook']);
    }

    public function initHook()
    {
        $this->_nicename = esc_html__('CareHQ CRM', 'ninja-forms');

        $this->_settings = [
            'label' => [
                'name' => 'label',
                'type' => 'textbox',
                'label' => esc_html__('Label', 'ninja-forms'),
                'width' => 'full',
                'value' => $this->_nicename,
                'use_merge_tags' => false
            ],
            'sales_channel' => [
                'name' => 'sales_channel',
                'type' => 'textbox',
                'label' => esc_html__('Sales Channel ID', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => esc_html__('Get the ID from the url in Care HQ under Account Settings > Groups and select the correct group. A dedicated channel for the website would be a logical choice.', 'ninja-forms')
            ],
            'first_name' => [
                'name' => 'first_name',
                'type' => 'textbox',
                'label' => esc_html__('First Name Field', 'ninja-forms'),
                'width' => 'one-half',
                'group' => 'primary',
                'use_merge_tags' => ['include' => ['fields']]
            ],
            'last_name' => [
                'name' => 'last_name',
                'type' => 'textbox',
                'label' => esc_html__('Last Name Field', 'ninja-forms'),
                'width' => 'one-half',
                'group' => 'primary',
                'use_merge_tags' => ['include' => ['fields']]
            ],
            'email' => [
                'name' => 'email',
                'type' => 'textbox',
                'label' => esc_html__('Email Field', 'ninja-forms'),
                'width' => 'one-half',
                'group' => 'primary',
                'use_merge_tags' => ['include' => ['fields']]
            ],
            'phone' => [
                'name' => 'phone',
                'type' => 'textbox',
                'label' => esc_html__('Phone Field', 'ninja-forms'),
                'width' => 'one-half',
                'group' => 'primary',
                'use_merge_tags' => ['include' => ['fields']]
            ],
            'care_requirements' => [
                'name' => 'care_requirements',
                'type' => 'textbox',
                'label' => esc_html__('Care Requirements', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'use_merge_tags' => ['include' => ['fields']]
            ],
            'location' => [
                'name' => 'location',
                'type' => 'textbox',
                'label' => esc_html__('Location ID', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => esc_html__('Enter your CareHQ Location ID', 'ninja-forms'),
                'use_merge_tags' => ['include' => ['fields']]
            ],
            'funding_type' => [
                'name' => 'funding_type',
                'type' => 'textbox',
                'label' => esc_html__('Funding Type', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => esc_html__('Enter your CareHQ Funding Type. Valid options are "Private", "NHS", "Local Authority", "Charity", "Other"', 'ninja-forms'),
                'use_merge_tags' => ['include' => ['fields']]
            ],
            'service' => [
                'name' => 'service',
                'type' => 'textbox',
                'label' => esc_html__('Service Type', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => esc_html__('Enter your CareHQ Service Type. Valid options are "Assisted Living", "Residential Home", "Nursing Home", "Learning Disability Care", "Bariatric Care", "Specialist Care", "Home Care", "Live-in Care"', 'ninja-forms'),
                'use_merge_tags' => ['include' => ['fields']]
            ]
        ];
    }

    public function process(array $action_settings, int $form_id, array $data): array
    {

        // For the location we need to get the ID which is stored in the calc value.
        // Not convinced this is the best method to do this. Future refactor...
        #foreach ($data['fields'] as $field) {
        #    if (strpos($field['key'], 'location_') === 0) {
        #        foreach ($field['options'] as $option) {
        #            if($option['value'] === $action_settings['location']) {
        #                    $action_settings['location'] = $option['calc'];
        #                break;
        #            }
        #        }
        #    }
        #}

        $options = get_option('carehq_integration_options');

        // guard against empty api options
        if (empty($options['account_id']) || empty($options['api_key']) || empty($options['api_secret'])) {
            return $data;
        }

        try {
            $client = new \CareHQ\APIClient(
                $options['account_id'],
                $options['api_key'],
                $options['api_secret'],
                $options['api_base_url']
            );

            // Get locations from form data.
            // Need to handle multiple locations.
            // CareHQ doesn't support this, so send multiple requests for each location.
            $locations = explode(',', $action_settings['location']);

            foreach ($locations as $location) {
                $care_enquiry_data = [
                    'location'          => trim($location), // Ensure no whitespace
                    'sales_channel'     => $action_settings['sales_channel'],
                    'first_name'        => $action_settings['first_name'],
                    'last_name'         => $action_settings['last_name'],
                    'email'             => $action_settings['email'],
                    'phone'             => $action_settings['phone'],
                    'funding_type'      => $action_settings['funding_type'] ? sanitize_title($action_settings['funding_type']) : 'not_sure',
                    'service'           => $action_settings['service'] ? sanitize_title($action_settings['service']) : 'residential_home',
                    'care_requirements' => $action_settings['care_requirements']
                ];
                $response = $client->request('PUT', 'care-enquiries', null, $care_enquiry_data);
                error_log('Care enquiry created in CareHQ for location ' . $location . ': ' . print_r($response, true));
            }

        } catch (Exception $e) {
            error_log('CareHQ API Error: ' . $e->getMessage());
            error_log(print_r($e, true));
        }

        return $data;
    }

    private function get_field_value($fields, $field_key)
    {
        foreach ($fields as $field) {
            if ($field['key'] === $field_key) {
                return $field['value'];
            }
        }
        return '';
    }
}
