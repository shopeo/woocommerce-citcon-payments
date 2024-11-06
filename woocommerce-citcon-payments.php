<?php
/**
 * Plugin Name: WooCommerce Citcon Payments
 * Plugin URI:  https://woocommerce.com/products/woocommerce-citcon-payments/
 * Description: Citcon latest complete payments processing solution.
 * Version:     0.0.1
 * Author:      SHOPEO
 * Author URI:  https://shopeo.cn/
 * License:     GPL-2.0
 * Requires PHP: 7.1
 * WC requires at least: 3.9
 * WC tested up to: 6.7
 * Text Domain: woocommerce-citcon-payments
 *
 */
require_once 'vendor/autoload.php';

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Shopeo\WoocommerceCitconPayments\CitconPayApi;
use Shopeo\WoocommerceCitconPayments\PaymentChannel;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!defined('WOOCOMMERCE_CITCON_PAYMENTS_PLUGIN_FILE')) {
    define('WOOCOMMERCE_CITCON_PAYMENTS_PLUGIN_FILE', __FILE__);
}

if (!defined('WOOCOMMERCE_CITCON_PAYMENTS_PLUGIN_BASE')) {
    define('WOOCOMMERCE_CITCON_PAYMENTS_PLUGIN_BASE', plugin_basename(WOOCOMMERCE_CITCON_PAYMENTS_PLUGIN_FILE));
}

if (!defined('WOOCOMMERCE_CITCON_PAYMENTS_PATH')) {
    define('WOOCOMMERCE_CITCON_PAYMENTS_PATH', plugin_dir_path(WOOCOMMERCE_CITCON_PAYMENTS_PLUGIN_FILE));
}

if (!function_exists('woocommerce_citcon_payments_activate')) {
    function woocommerce_citcon_payments_activate()
    {

    }
}

register_activation_hook(__FILE__, 'woocommerce_citcon_payments_activate');


if (!function_exists('woocommerce_citcon_payments_deactivate')) {
    function woocommerce_citcon_payments_deactivate()
    {

    }
}

register_deactivation_hook(__FILE__, 'woocommerce_citcon_payments_deactivate');

if (!function_exists('woocommerce_citcon_payments_load_textdomain')) {
    function woocommerce_citcon_payments_load_textdomain()
    {
        load_plugin_textdomain('woocommerce-citcon-payments', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

add_action('init', 'woocommerce_citcon_payments_load_textdomain');

$payment_channels = [
    new PaymentChannel([
        'method' => 'card',
        'title' => 'Card',
        'currency' => ['USD'],
        'country' => '',
        'enabled' => 'no',
        'checked' => 'yes',
        'hide_form_title' => 'no',
        'icon' => 'assets/images/card.jpg',
        'icon_height' => '22',
    ]),
    new PaymentChannel([
        'title' => 'Alipay',
        'currency' => ['USD', 'CAD'],
        'country' => '',
        'enabled' => 'no',
        'method' => 'alipay',
        'checked' => 'no',
        'icon' => 'assets/images/alipay-logo.png',
    ]),
    new PaymentChannel([
        'method' => 'wechatpay',
        'title' => 'WeChat Pay',
        'currency' => ['USD', 'CAD'],
        'country' => '',
        'enabled' => 'no',
        'checked' => 'no',
        'icon' => 'assets/images/wechatpay-logo.png',
    ]),
    new PaymentChannel([
        'method' => 'upop',
        'title' => 'Union Pay',
        'currency' => ['USD', 'CAD'],
        'country' => '',
        'enabled' => 'no',
        'checked' => 'no',
        'icon' => 'assets/images/unionpay2-logo.png',
    ]),
    new PaymentChannel([
        'method' => 'paypal',
        'title' => 'Paypal',
        'currency' => ['USD'],
        'country' => '',
        'enabled' => 'no',
        'checked' => 'no',
        'icon' => 'assets/images/paypal-logo.png',
        'processPaymentBody' => function ($params, $order) {
            $params['country'] = 'US';
            $params['auto_capture'] = 'true';
            return $params;
        }
    ]),
    new PaymentChannel([
        'method' => 'venmo',
        'title' => 'Venmo',
        'currency' => ['USD'],
        'country' => '',
        'enabled' => 'no',
        'checked' => 'no',
        'icon' => 'assets/images/venmo-logo.png',
        'icon_height' => '20',
        'processPaymentBody' => function ($params, $order) {
            $params['country'] = 'US';
            $params['auto_capture'] = 'true';
            return $params;
        },
    ]),
];

if (!function_exists('get_payment_channels')) {
    function get_payment_channels()
    {
        global $payment_channels;
        return $payment_channels;
    }
}

if (!function_exists('get_form_payment_channels')) {
    function get_form_payment_channels()
    {
        $list = [];
        $payment_channels = get_payment_channels();
        if (!empty($payment_channels)) {
            foreach ($payment_channels as $channel) {
                $list[$channel->method] = $channel->get_form_fields();
            }
        }
        return $list;
    }
}
if (!function_exists('get_payment_channel_titles')) {
    function get_payment_channel_titles()
    {
        $list = [];
        $payment_channels = get_payment_channels();
        if (!empty($payment_channels)) {
            foreach ($payment_channels as $channel) {
                $list[$channel->method] = $channel->title;
            }
        }
        return $list;
    }
}

if (!function_exists('woocommerce_gateway_citcon_init')) {
    function woocommerce_gateway_citcon_init()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }
        if (!class_exists('WC_Citcon_Pay_Gateway')) {
            class WC_Citcon_Pay_Gateway extends WC_Payment_Gateway
            {
                private $debug;
                private $merchant_key;

                public function __construct()
                {
                    $this->id = 'citcon';
                    $this->icon = plugins_url('/assets/images/citcon-pay-logo.svg', WOOCOMMERCE_CITCON_PAYMENTS_PLUGIN_FILE);
                    $this->method_title = __('Citcon Payment', 'woocommerce-citcon-payments');
                    $this->method_description = __('Citcon Payment Gateway', 'woocommerce-citcon-payments');

                    $this->supports = array(
                        'products'
                    );

                    $this->init_form_fields();

                    $this->init_settings();

                    $this->enabled = $this->get_option('enabled');
                    $this->debug = $this->get_option('debug') == 'yes' ? true : false;
                    $this->title = $this->get_option('title');
                    $this->description = $this->get_option('description');
                    $this->merchant_key = $this->get_option('merchant_key');


                    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                        $this,
                        'process_admin_options'
                    ));
                    add_action('woocommerce_api_' . $this->id, array($this, 'webhook'));
                }

                public function init_form_fields()
                {
                    $titles = get_payment_channel_titles();
                    $this->form_fields = array(
                        'enabled' => array(
                            'title' => __('Enabled/Disable', 'woocommerce-citcon-payments'),
                            'label' => __('Enable Citcon Gateway', 'woocommerce-citcon-payments'),
                            'type' => 'checkbox',
                            'description' => '',
                            'default' => 'no'
                        ),
                        'debug' => array(
                            'title' => __('Debug', 'woocommerce-citcon-payments'),
                            'label' => __('Debug', 'woocommerce-citcon-payments'),
                            'type' => 'checkbox',
                            'description' => '',
                            'default' => 'no'
                        ),
                        'title' => array(
                            'title' => __('Title', 'woocommerce-citcon-payments'),
                            'type' => 'text',
                            'default' => __('Citcon Payment', 'woocommerce-citcon-payments'),
                            'desc_tip' => true,
                        ),
                        'description' => array(
                            'title' => __('Description', 'woocommerce-citcon-payments'),
                            'type' => 'textarea',
                            'default' => __('Please select your payment method', 'woocommerce-citcon-payments'),
                            'desc_tip' => true,
                        ),
                        'merchant_key' => array(
                            'title' => __('Merchant Key', 'woocommerce-citcon-payments'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => true,
                        ),
                        'default_channel' => array(
                            'title' => __('Default Payment Method', 'woocommerce-citcon-payments'),
                            'type' => 'select',
                            'options' => $titles,
                            'default' => array_keys($titles)[0],
                            'description' => __('Select a default payment method', 'woocommerce-citcon-payments')
                        )
                    );
                    $channels = get_form_payment_channels();
                    foreach ($channels as $key => $value) {
                        $this->form_fields[$key] = $value;
                    }
                }

                public function payment_fields()
                {
                    if ($this->description) {
                        ?>
                        <p><?php esc_html_e($this->description); ?></p>
                        <?php
                    }
                    ?>
                    <fieldset>
                        <legend><label><?php esc_html_e('Method of payment', 'woocommerce-citcon-payments'); ?><span
                                        class="required">*</span></label></legend>
                        <ul class="wc_payment_methods payment_methods methods">
                            <?php
                            $plugin_dir = plugin_dir_url(__FILE__);
                            foreach (get_payment_channels() as $channel) {
                                $method = $channel->method;
                                $title = $channel->title;
                                $currency = get_option('woocommerce_currency');
                                $icon = $channel->icon;
                                $icon_height = $channel->icon_height;
                                if (strcmp($this->settings[$method], 'yes') == 0 && in_array($currency, $channel->currency)) { ?>
                                    <li class="wc_payment_method">
                                        <div style="display: flex; align-items: center;">
                                            <input id="citcon_pay_method_<?php echo $method; ?>"
                                                   class="input-radio"
                                                   name="payment_channel"
                                                   value="<?php echo $method; ?>"
                                                   data-order_button_text=""
                                                   type="radio" required
                                                <?php
                                                if (strcmp($this->settings['default_channel'], $method) == 0) { ?>
                                                    checked="checked"
                                                <?php } ?>
                                            >
                                            <label for="citcon_pay_method_<?php echo $method; ?>">
                                                <img src="<?php echo $plugin_dir . $icon; ?>"
                                                     style="height: <?php echo $icon_height; ?>px; margin-left: -2px;"
                                                     alt="Citcon Pay"
                                                     title="<?php esc_html_e($title); ?>"
                                                />
                                                <!-- <?php esc_html_e($title); ?>  -->
                                            </label>
                                        </div>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                    </fieldset>
                    <?php
                }

                public
                function process_payment($order_id)
                {
                    global $woocommerce;
                    $order = new WC_Order($order_id);
                    if (!wp_verify_nonce('', 'woocommerce-process_checkout')
                        && !empty($_POST['payment_channel'])
                        && sanitize_key($_POST['payment_channel'])) {
                        $payment_channel = sanitize_key($_POST['payment_channel']);
                    }
                    $citconApi = new CitconPayApi($this->merchant_key, $this->debug);
                    $reference = $order_id;
                    $amount = intval($order->get_total() * 100);
                    $currency = $order->get_currency();
                    $country = $order->get_shipping_country();
                    $method = $payment_channel;
                    $ipn_url = home_url() . '/wc-api/' . $this->id . '?id=' . $order->get_id();
                    $success_url = $this->get_return_url($order);
                    $fail_url = $success_url;
                    $goods = [
                        'shipping' => [
                            'city' => $order->get_shipping_city(),
                            'zip' => $order->get_shipping_postcode(),
                            'country' => $order->get_shipping_country()
                        ]
                    ];
                    foreach ($order->get_items(['line_item', 'fee', 'shipping']) as $item) {
                        $total_amount = $item->get_total();
                        if ($total_amount > 0) {
                            $goods['data'][] = [
                                'name' => $item->get_name(),
                                'quantity' => 1,
                                'unit_amount' => intval($total_amount * 100),
                                "product_type" => "physical"
                            ];
                        }
                    }
                    $data = $citconApi->charge($reference, $amount, $currency, $country, $method, $ipn_url, $success_url, $fail_url, $goods);
                    if ($data['status'] === 'success') {
                        $order->set_transaction_id($data['data']['id']);
                        $order->save();
                        $woocommerce->cart->empty_cart();
                        foreach ($data['data']['payment']['client'] as $item) {
                            if ($item['format'] === 'redirect' && $item['method'] === 'GET') {
                                $redirect_url = $item['content'];
                            }
                        }
                        return array(
                            'result' => 'success',
                            'redirect' => $redirect_url
                        );
                    }
                    return null;
                }

                public function webhook()
                {
                    $order = new WC_Order($_GET['id']);
                    error_log('WebHook:' . $_GET['id']);
                    $params = file_get_contents("php://input");
                    error_log($params);
                    $params = json_decode($params, true);
                    error_log(print_r($params, true));
                    $sign_str = "amount={$params['amount']}&amount_captured={$params['amount_captured']}&amount_refunded={$params['amount_refunded']}&currency={$params['currency']}&fields={$params['fields']}&id={$params['id']}&payment={$params['payment']}&payment_method={$params['payment_method']}&reference={$params['reference']}&status={$params['status']}&time_completed={$params['time_completed']}&time_created={$params['time_created']}&transaction_type={$params['transaction_type']}&secret={$this->merchant_key}";
                    error_log($sign_str);
                    $sign = hash('sha256', $sign_str);
                    error_log($sign);
                    if ($order->get_id() === intval($params['reference']) && $sign === $params['sign'] && $params['transaction_type'] === 'charge' && $params['status'] === 'succeeded') {
                        error_log('Payment completed');
                        $order->payment_complete();
                        wc_reduce_stock_levels($order->get_id());
                        $order->add_order_note(
                            __('Payment completed', 'woocommerce-citcon-payments')
                        );
                    }
                }
            }
        }
    }
}

add_action('plugins_loaded', 'woocommerce_gateway_citcon_init', 0);

if (!function_exists('woocommerce_add_gateway_citcon_gateway')) {
    function woocommerce_add_gateway_citcon_gateway($methods)
    {
        $methods[] = 'WC_Citcon_Pay_Gateway';

        return $methods;
    }
}

add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_citcon_gateway');

if (!function_exists('citcon_declare_cart_checkout_blocks_compatibility')) {
    function citcon_declare_cart_checkout_blocks_compatibility()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
            FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }
}

add_action('before_woocommerce_init', 'citcon_declare_cart_checkout_blocks_compatibility');

if (!function_exists('citcon_register_order_approval_payment_method_type')) {
    function citcon_register_order_approval_payment_method_type()
    {
        if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }
        if (!class_exists('WC_Citcon_Pay_Gateway_Blocks')) {
            final class WC_Citcon_Pay_Gateway_Blocks extends AbstractPaymentMethodType
            {
                private $gateway;
                protected $name = 'citcon';

                public function initialize()
                {
                    $this->settings = get_option('woocommerce_citcon_settings', []);
                    $gateways = WC()->payment_gateways->payment_gateways();
                    $this->gateway = $gateways[$this->name];
                }

                public function is_active()
                {
                    return $this->gateway->is_available();
                }

                public function get_payment_method_script_handles()
                {
                    wp_register_script('citcon-blocks-integration', plugin_dir_url(__FILE__) . 'assets/js/checkout.js', [
                        'wc-blocks-registry',
                        'wc-settings',
                        'wp-element',
                        'wp-html-entities',
                        'wp-i18n',
                    ], null, true);
                    if (function_exists('wp_set_script_translations')) {
                        wp_set_script_translations('citcon-blocks-integration', 'woocommerce-citcon-payments', plugin_dir_path(__FILE__) . 'languages');
                    }

                    return ['citcon-blocks-integration'];
                }

                public function get_payment_method_data()
                {
                    return [
                        'title' => $this->get_setting('title'),
                        'description' => $this->get_setting('description'),
                        'supports' => $this->gateway->supports,
                    ];
                }
            }
        }

        add_action('woocommerce_blocks_payment_method_type_registration', function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new WC_Citcon_Pay_Gateway_Blocks);
        });
    }
}

add_action('woocommerce_blocks_loaded', 'citcon_register_order_approval_payment_method_type');