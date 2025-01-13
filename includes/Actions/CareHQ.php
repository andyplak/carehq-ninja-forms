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
            'location' => [
                'name' => 'location',
                'type' => 'textbox',
                'label' => esc_html__('Location ID', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => esc_html__('Enter your CareHQ Location ID', 'ninja-forms')
            ],
            'sales_channel' => [
                'name' => 'sales_channel',
                'type' => 'textbox',
                'label' => esc_html__('Sales Channel ID', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => esc_html__('Enter your CareHQ Sales Channel ID', 'ninja-forms')
            ],
                'service' => [
                'name' => 'service',
                'type' => 'select',
                'label' => esc_html__('Service Type', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'options' => [
                    ['label' => esc_html__('- Select Service -', 'ninja-forms'), 'value' => ''],
                    ['label' => esc_html__('Assisted Living', 'ninja-forms'), 'value' => 'assisted_living'],
                    ['label' => esc_html__('Residential Home', 'ninja-forms'), 'value' => 'residential_home'],
                    ['label' => esc_html__('Nursing Home', 'ninja-forms'), 'value' => 'nursing_home'],
                    ['label' => esc_html__('Learning Disability Care', 'ninja-forms'), 'value' => 'learning_disability_care'],
                    ['label' => esc_html__('Bariatric Care', 'ninja-forms'), 'value' => 'bariatric_care'],
                    ['label' => esc_html__('Specialist Care', 'ninja-forms'), 'value' => 'specialist_care'],
                    ['label' => esc_html__('Home Care', 'ninja-forms'), 'value' => 'home_care'],
                    ['label' => esc_html__('Live-in Care', 'ninja-forms'), 'value' => 'live_in_care']
                ]
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
            ]
        ];
    }

    public function process(array $action_settings, int $form_id, array $data): array
    {
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
