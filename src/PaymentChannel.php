<?php

namespace Shopeo\WoocommerceCitconPayments;

class PaymentChannel
{
    public $title;
    public $currency;
    public $country;
    public $enabled = 'no';
    public $method;

    public $checked;

    public $icon;
    public $icon_height = '30';

    public $hide_form_title = 'yes';

    public $processPaymentBody;

    public function __construct($data)
    {
        $this->title = $data['title'];
        $this->currency = $data['currency'];
        $this->country = $data['country'];
        $this->enabled = $data['enabled'];
        $this->method = $data['method'];
        $this->checked = $data['checked'];
        $this->icon = $data['icon'];
        if (isset($data['hide_form_title'])) {
            $this->hide_form_title = $data['hide_form_title'];
        }

        if (isset($data['icon_height'])) {
            $this->icon_height = $data['icon_height'];
        }

        if (isset($data['processPaymentBody'])) {
            $this->processPaymentBody = $data['processPaymentBody'];
        }
    }
    public function get_form_fields () {
        $forms = [
            'type' => 'checkbox',
            'label' => __($this -> title, 'woocommerce'),
            'default' => 'yes'
        ];

        if ($this -> hide_form_title ==='no') {
            $forms['title'] = __('Enable/Disable', 'woocommerce');
        }

        return $forms;
    }
}