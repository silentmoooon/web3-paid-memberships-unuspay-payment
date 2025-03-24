<?php

/**
 * Plugin Name: Unuspay Crypto payment for Paid Memberships Pro
 * Plugin URI: https://
 * Description: Pay with Crypto For Paid Memberships Pro
 * Version: 1.0.17
 * Author: Unuspay
 * Author URI: https://unuspay.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: Expand customer base with crypto payment, non-custodail & no fraud/chargeback, low fees, 50+ cryptos. Invoice, payment link, payment button.
 * Tags: paid memberships pro, pmp, bitcoincash, bitcoin cash, bitcoins, gourl, cryptocurrency, btc, coinbase, bitpay, ecommerce, paypal, accept bitcoin, payment, payment gateway, digital downloads, download, downloads, e-commerce, e-downloads, e-store, wp ecommerce, litecoin, dogecoin, dash，Crypto, cryptocurrency, crypto payment, erc20, cryptocurrency, bitcoin, bitcoin lighting network, ethereum, crypto pay, smooth withdrawals, cryptocurrency payments, low commission, pay with meta mask, payment button, invoice, crypto paid memberships pro，bitcoin paid memberships pro，ethereum，pay crypto，virtual currency，bitcoin wordpress plugin，free crypto plugin,
 * Requires at least: 5.8
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('unuspay_pmp_gateway_load'))
{

    add_action('plugins_loaded', 'unuspay_pmp_gateway_load', 20);

    DEFINE("UNUSPAY_PMP_GATEWAY_NAME", "pmp_unuspay_gateway");


    register_activation_hook(__FILE__, 'setup_plugin');
    function setup_plugin()
    {
        global $wpdb;
        $latestDbVersion = 5;
        $currentDbVersion = get_option('unuspay_pmp_db_version');

        if (!empty($currentDbVersion) && $currentDbVersion >= $latestDbVersion) {
            return;
        }
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("
		CREATE TABLE  IF NOT EXISTS {$wpdb->prefix}pmp_unuspay_checkouts (
			id VARCHAR(36) NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			accept LONGTEXT NOT NULL,
			created_at datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
			PRIMARY KEY  (id)
		);"
        );
        dbDelta("
        CREATE TABLE  IF NOT EXISTS {$wpdb->prefix}pmp_unuspay_transactions (
        			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        			order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        			checkout_id VARCHAR(36) NOT NULL,
        			tracking_uuid VARCHAR(64) NOT NULL,
        			blockchain TINYTEXT NOT NULL,
        			transaction_id TINYTEXT NOT NULL,
        			sender_id TINYTEXT NOT NULL,
        			receiver_id TINYTEXT NOT NULL,
        			token_id TINYTEXT NOT NULL,
        			amount TINYTEXT NOT NULL,
        			status TINYTEXT NOT NULL,
        			failed_reason TINYTEXT NOT NULL,
        			confirmed_by TINYTEXT NOT NULL,
        			confirmed_at datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
        			created_at datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
        			PRIMARY KEY  (id),
        			KEY tracking_uuid_index (tracking_uuid)
        		);
	");
        update_option('unuspay_pmp_db_version', $latestDbVersion);
    }


    //add_action('pmpro_invoice_bullets_top', function (){error_reporting(E_ERROR);});

    function unuspay_pmp_gateway_load()
    {
        if (!class_exists('PMProGateway')) return;

        add_action('init', array('PMProGateway_unuspay', 'init'));

        add_filter('pmpro_pages_shortcode_confirmation', array('PMProGateway_unuspay', 'pmpro_pages_shortcode_confirmation'), 20, 1);

        add_filter('plugin_action_links', array('PMProGateway_unuspay', 'plugin_action_links'), 10, 2);

        add_filter('pmpro_get_gateway', array('PMProGateway_unuspay', 'select_gateway'), 10, 1);

        add_filter('pmpro_valid_gateways', array('PMProGateway_unuspay', 'valid_gateway'), 10, 1);

        add_action('pmpro_checkout_boxes', array('PMProGateway_unuspay', 'checkout_boxes'));

        //add_action('parse_request', array('PMProGateway_unuspay', 'callback_parse_request'));

		add_filter( 'plugin_row_meta', array('PMProGateway_unuspay', 'plugin_row_meta'), 10, 2 );

        class PMProGateway_unuspay extends PMProGateway
        {
            function __construct($gateway = NULL)
            {
                $this->gateway = $gateway;
                return $this->gateway;
            }

            public static function init()
            {
                add_filter('pmpro_gateways', array('PMProGateway_unuspay', 'pmpro_gateways'));

                add_filter('pmpro_payment_options', array('PMProGateway_unuspay', 'pmpro_payment_options'));
                add_filter('pmpro_payment_option_fields', array('PMProGateway_unuspay', 'pmpro_payment_option_fields'), 10, 2);

                $gateway = pmpro_getGateway();
                if ($gateway == "unuspay")
                {
                    add_filter('pmpro_include_billing_address_fields', '__return_false');
                    add_filter('pmpro_include_payment_information_fields', '__return_false');
                    add_filter('pmpro_required_billing_fields', array('PMProGateway_unuspay', 'pmpro_required_billing_fields'));
					add_filter('pmpro_checkout_before_change_membership_level', array('PMProGateway_unuspay', 'pmpro_checkout_before_change_membership_level'), 1, 2);
                }
            }

            public static function plugin_action_links($links, $file)
            {
                static $this_plugin;

                if (isset($this_plugin) === false || empty($this_plugin) === true)
                {
                    $this_plugin = plugin_basename(__FILE__);
                }

                if ($file == $this_plugin)
                {
                    //$unuspay_link = '<a href="https://dashboard.unuspay.com/#/login?cur_url=/integration&platform=PAIDMEMBERSHIPSPRO" target="_blank" style="color: #39b54a; font-weight: bold;">' . __('Get Unuspay', UNUSPAY_PMP_GATEWAY_NAME) . '</a>';
                    $settings_link = '<a href="' . admin_url('admin.php?page=pmpro-paymentsettings') . '">' . __('Settings', UNUSPAY_PMP_GATEWAY_NAME) . '</a>';
                    array_unshift($links,  $settings_link);
                }

                return $links;
            }

            public static function select_gateway($gateway)
            {
                if (!session_id()) session_start();

                if (isset($_POST['gateway']))
                {
                    $gateway = $_SESSION['unuspay_pmp_gateway'] = sanitize_text_field($_POST['gateway']);
                }
                else
                {
                    if (isset($_SESSION['unuspay_pmp_gateway']) && $_SESSION['unuspay_pmp_gateway'] == 'unuspay')
                    {
                        $gateway = sanitize_text_field($_SESSION['unuspay_pmp_gateway']);
                    }
                }

                return $gateway;
            }

            public static function valid_gateway($gateways)
            {
                if (array_search('unuspay', $gateways) === false)
                {
                    $gateways[] = 'unuspay';
                }

                return $gateways;
            }

            public static function admin_unuspay_notice()
            {
                if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'pmpro-paymentsettings')
                {
                    $tmp = '<div class="notice notice-info is-dismissible" style="margin:20px">';
                    $tmp .= '<img style="float:left;width: 140px;height: 55px;" alt="img" src="' . plugins_url("/images/unuspay.png", __FILE__) . '" border="0" vspace="12" hspace="10">';
                    $tmp .= '<p>' . sprintf(__("You can provide your customers with multiple payment options at the checkout page of PaidMembershipsPro. To enable this feature, you need to set up your Unuspay Crypto Payment Gateway settings on this page, then click the 'Save Settings' button. Afterward, you can switch to another payment gateway (such as Paypal or Stripe) and keep it as your primary gateway. The Unuspay settings will still be remembered in the background, and both payment gateways will be displayed on the checkout page. If you only want to use the Unuspay Crypto Payment Gateway on the checkout page, you should keep it as your primary gateway. For more information, please visit ", UNUSPAY_PMP_GATEWAY_NAME));
                    $tmp .= '<a href="www.unuspay.com" target="_blank">'. "www.unuspay.com" . '</a>.</p>';
                    $tmp .= '</div>';
                    echo wp_kses_post($tmp);
                }

                return true;
            }

            public static function getGatewayOptions()
            {
                global $wpdb;

                $options = array(
                    'unuspay_payment_key',
                );

              /*  $levels = $wpdb->get_col("SELECT DISTINCT name FROM {$wpdb->pmpro_membership_levels}");

                foreach ($levels as $level) {
                    $options[] = 'unuspay_level_' . esc_attr(str_replace(' ', '', $level));
                }*/

                return $options;
            }

            public static function pmpro_payment_options($options)
            {
                $unuspay_options = PMProGateway_unuspay::getGatewayOptions();

                $options = array_merge($unuspay_options, $options);

                return $options;
            }

            public static function pmpro_gateways($gateways)
            {
                if (empty($gateways['unuspay']))
                {
                    $gateways = array_slice($gateways, 0, 1) + array("unuspay" => __('Unuspay', UNUSPAY_PMP_GATEWAY_NAME)) + array_slice($gateways, 1);
                }

                return $gateways;
            }

            public static function pmpro_required_billing_fields($fields)
            {
                unset($fields['bfirstname']);
                unset($fields['blastname']);
                unset($fields['baddress1']);
                unset($fields['bcity']);
                unset($fields['bstate']);
                unset($fields['bzipcode']);
                unset($fields['bphone']);
                unset($fields['bemail']);
                unset($fields['bcountry']);
                unset($fields['CardType']);
                unset($fields['AccountNumber']);
                unset($fields['ExpirationMonth']);
                unset($fields['ExpirationYear']);
                unset($fields['CVV']);

                return $fields;
            }

            public static function plugin_row_meta($plugin_meta, $plugin_file)
            {

                static $this_plugin;

                if (isset($this_plugin) === false || empty($this_plugin) === true)
                {
                    $this_plugin = plugin_basename(__FILE__);
                }

                if ( $this_plugin === $plugin_file )
                {
                    $row_meta = [
				        'dome' => '<a style="color: #39b54a;" href="https://example-wp.unuspay.com/membership-account/membership-checkout/" aria-label="' . esc_attr( __( 'View Unuspay Demo', UNUSPAY_PMP_GATEWAY_NAME ) ) . '" target="_blank">' . __( 'Demo', UNUSPAY_PMP_GATEWAY_NAME ) . '</a>',
                        'video' => '<a style="color: #39b54a;" href="https://youtu.be/OCaz-_dbTGA" aria-label="' . esc_attr( __( 'View Unuspay Video Tutorials', UNUSPAY_PMP_GATEWAY_NAME ) ) . '" target="_blank">' . __( 'Video Tutorials', UNUSPAY_PMP_GATEWAY_NAME ) . '</a>',
                    ];

                    $plugin_meta = array_merge( $plugin_meta, $row_meta );
                }

                return $plugin_meta;
            }

            public static function pmpro_payment_option_fields($options, $gateway)
            {
                global $unuspay, $wpdb;

                $description  = "<a target='_blank' href='https://unuspay.com/' ><img border='0' src='" . plugins_url('/images/unuspay.png', __FILE__) . "'></a>";
                $description .= '<p style="margin-top: 10px;"><b>Unuspay official <a href="https://unuspay.com/" target="_blank">website.</a></b></p>';
                 $tr = '<tr class="gateway gateway_unuspay"' . ($gateway != "unuspay" ? ' style="display: none;"' : '') . '>';
                $tmp  = '<tr class="pmpro_settings_divider gateway gateway_unuspay"' . ($gateway != "unuspay" ? ' style="display: none;"' : '') . '>';
                $tmp .= '<td colspan="2"><hr/><h2>Unuspay Crypto Payment Gateway Settings</h2></td>';
                $tmp .= "</tr>";
                $tmp .= $tr;
                $tmp .=  '<td colspan="2"><div style="font-size:13px;line-height:22px">' . $description . '</div></td></tr>';

                $tmp .= $tr .  '<th scope="row" valign="top" style="padding-left:10px"><label for="unuspay_payment_key">Unuspay payment Key:</label></th><td><input  style="width: 320px" type="text" value="' . $options["unuspay_payment_key"] . '" name="unuspay_payment_key" id="unuspay_payment_key"></td></tr>';

               /* $tmp .= $tr . '<td colspan="2"><h4>Set Up Membership Level Display</h4>';
                $tmp .= "</tr>";
                $tmp .= $tr . '<td colspan="12"><p>Note:</p><ul><li>1. Unuspay Crypto Payment Gateway will always be available, but you can configure which membership levels to show below.</li><li>2. Check to show which level will show unuspay payment.</li><li>3. If it doesn\'t work, make sure there are no strange characters in the level name.</li></ul></td>';
                $tmp .= "</tr>";
                $tmp .= $tr . '<td colspan="12">';

                $levels = $wpdb->get_col("SELECT DISTINCT name FROM {$wpdb->pmpro_membership_levels}");
                foreach ($levels as $level) {
                    $level_key = "unuspay_level_" . esc_attr(str_replace(' ', '', $level));
                    $level_value = $options[$level_key];
        
                    $tmp .= '<label style="margin-right: 10px;"><input type="checkbox" name="' . esc_attr($level_key) . '" ' . (!empty($level_value) ? 'checked="checked"' : '') . ' value="' . 1 . '"> ' . esc_html($level) . '</label>';
                }
                $tmp .="</td></tr>";*/
                
                echo $tmp;

             /*   if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'pmpro-paymentsettings')
                {
                    try {
                        self::verify_unuspay_key($options['unuspay_payment_key']);
                    } catch (Exception $e) {
                        return;
                    }
                }*/

                return;
            }

            public static function verify_unuspay_key($merchant_public_key)
            {

                /*$key_result = wp_remote_get( 'https://dashboard.unuspay.com/api/plugin/key/verify?id=' . $merchant_id . '&key=' .$merchant_public_key .'&name=PAIDMEMBERSHIPSPRO&url=' . parse_url(site_url(), PHP_URL_HOST) );
                $response_data = json_decode($key_result['body'], true);


                if (!($response_data['data']))
                {
                    self::admin_notice_for_key();
                    self::admin_unuspay_notice();
                }*/
            }

            public static function admin_notice_for_key()
            {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e( '[Unuspay PMP] The unuspay merchant id and public key you entered is incorrect. Please check the video link for more information', UNUSPAY_PMP_GATEWAY_NAME ); ?>
                    (<a href="https://youtu.be/OCaz-_dbTGA" target="blank">https://youtu.be/OCaz-_dbTGA</a>)</p>
                </div>
                <?php
            }
            public static function checkout_boxes()
            {
                global $pmpro_requirebilling, $gateway, $pmpro_review, $wpdb;

                $setting_gateway = get_option("pmpro_gateway");
                if ($setting_gateway == "unuspay")
                {
                    echo '<h2>' . esc_html(__('Payment method', UNUSPAY_PMP_GATEWAY_NAME)) . '</h2>';
                    //echo '<input type="hidden" id="pmpro_checkout_gateway" name="pmpro_checkout_gateway" value="pmp_unuspay_gateway">';
                    echo esc_html(__('Unuspay', UNUSPAY_PMP_GATEWAY_NAME)) . '<img style="vertical-align:middle" src="' . plugins_url("/images/unuspay.png", __FILE__) . '" border="0" vspace="10" hspace="10" height="43" width="143"><br><br>';
                    return true;
                }


            }


            public static function pmpro_pages_shortcode_confirmation($content)
            {
                global $wpdb;

                if (!session_id()) session_start();

                if (!isset($_SESSION['unuspay_pmp_orderid'])) return $content;

                $order = new MemberOrder();
                $order->getMemberOrderByID($_SESSION['unuspay_pmp_orderid']);

                if (!empty($order) && $order->gateway == "unuspay" && isset($order->total) && $order->total > 0 && $order->user_id == get_current_user_id())
                {
                    unset($_SESSION['unuspay_pmp_orderid']);
                    self::pmpro_unuspay_cryptocoin_payment($order);
                }

                return $content;
            }

            public function process(&$order)
            {
                if (!empty($order) && $order->gateway == "unuspay")
                {
                    $order->payment_type = "Unuspay Crypto Payment Gateway";
                    $order->cardtype = "";
                    $order->ProfileStartDate = pmpro_calculate_profile_start_date( $order, 'Y-m-d\TH:i:s\Z' );
                    $order->status = "pending";
                    if(empty($order->code)) $order->code = $order->getRandomCode();
                    $order->saveOrder();
                    do_action('pmpro_before_commit_express_checkout', $order);
                    $_SESSION['unuspay_pmp_orderid'] = $order->id;
                }

                return true;
            }

            public static function pmpro_unuspay_cryptocoin_payment(&$order)
            {
                global $unuspay, $pmpro_currency, $current_user, $wpdb;

                if (!$order)
                {
                    echo "<div class='pmpro_message pmpro_error'>" . esc_html('The Unuspay payment gateway plugin was invoked to process a payment, but it was unable to fetch the order details. Therefore, the process cannot be carried forward. Please check the errors through the following steps: 1. Check your backend configuration to ensure it is correct. 2. Check your network environment. 3. Contact the Unuspay(contact@unuspay.com) service provider for further assistance.', 'unuspay-pmp') . "</div>";
                    return false;
                }
                if ( $order->total == 0)
                {
                    return true;
                }
                elseif ( $order->total < 0)
                {
                    echo "<div class='pmpro_message pmpro_error'>" . esc_html('The Unuspay payment gateway plugin was invoked to process a payment, but it was unable to fetch the order details. Therefore, the process cannot be carried forward. Please check the errors through the following steps: 1. Check your backend configuration to ensure it is correct. 2. Check your network environment. 3. Contact the Unuspay(contact@unuspay.com) service provider for further assistance.', 'unuspay-pmp') . "</div>";
                    return false;
                }
                $checkout_id = wp_generate_uuid4();
                $accept = self::getUnusPayOrder($order,$pmpro_currency, $checkout_id);
                /*$accept= array(
                    'name' => 'John',
                    'age' => 30,
                    'city' => 'New York'
                );*/
                $result = $wpdb->insert("{$wpdb->prefix}pmp_unuspay_checkouts", array(
                    'id' => $checkout_id,
                    'order_id' => $order->id,
                    'accept' => json_encode($accept),
                    'created_at' => current_time('mysql')
                ));
                if (false === $result) {
                    $error_message = $wpdb->last_error;

                    throw new Exception('Storing checkout failed: ' . $error_message);
                }
                $confirmation_page_id = pmpro_getOption("confirmation_page_id");
                $checkout_url = get_permalink($confirmation_page_id);
                $redirect_url = "Location: " . $checkout_url . '#pmp-unuspay-checkout-' . $checkout_id . '@' . time();
                header($redirect_url);
                die();
                return true;


                /*$payment_key = pmpro_getOption("unuspay_payment_key");

                $order_id = $order->id;
                $order_total = $order->total;
                $order_currency = $pmpro_currency;
                $order_user_id = $order->user_id;

                if (!$order_id)
                {
                    echo "<div class='pmpro_message pmpro_error'>" . esc_html(__('The Unuspay payment gateway plugin was triggered to process a payment, but it failed to retrieve the order details. As a result, the process cannot be continued. Please check the errors through the following steps: 1. Check your backend configuration to ensure it is correct. 2. Check your network environment. 3. Contact the Unuspay(contact@unuspay.com) service provider for further assistance.', 'unuspay-pmp')) . "</div>";
                }
                elseif (!$payment_key || !$order_total || !$order_currency || !$order_user_id)
                {
                    echo "<div class='pmpro_message pmpro_error'>" . esc_html(__('Currently, there are some issues with the unuspay crypto payment. Please check the errors through the following steps: 1. Check your backend configuration to ensure it is correct. 2. Check your network environment. 3. Contact the Unuspay(contact@unuspay.com) service provider for further assistance.', 'unuspay-pmp')) . "</div>";
                }
                else
                {
                    $plugin = "unuspaypmpro";
                    $amount = $order_total;
                    $currency = $order_currency;
                    $orderID = $order_id;
                    $userID = $order_user_id;
                    $platform = "PAIDMEMBERSHIPPRO";

                    if (!$userID) $userID = "guest";

                    if (!$userID)
                    {
                        echo "<div align='center'><a href='" . wp_login_url(get_permalink()) . "'>
                        <span>" . esc_html(__('Before making payment, you must login or register on website.', 'unuspay-pmp')) . "</span></a></div>";
                    }
                    elseif ($amount == 0)
                    {
                        echo "<div class='pmpro_message pmpro_error'>" . esc_html(sprintf(__("The amount for this order is '%s' and cannot be paid through Unuspay Crypto Payment. Please contact us(contact@unuspay.com) if you need assistance.", 'unuspay-pmp')), $amount . " " . $currency) . "</div>";
                    }
                    elseif ($amount < 0)
                    {
                        return true;
                    }
                    else
                    {
                        if ($amount > 0)
                        {
                            self::unuspay_generate_checkout_token( $payment_key, $orderID, $amount, $currency);
                        }
                    }
                }*/


            }

            public static function getUnusPayOrder($order,$pmpro_currency, $checkout_id)
            {
                $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
                $headers = array(
                    'accept-language' => $lang,
                    'Content-Type' => 'application/json; charset=utf-8',
                );
                $website = get_option("siteurl");

                $total = $order->total;

                $payment_key = pmpro_getOption("unuspay_payment_key");
                if (empty($payment_key)) {
                    unuspay_edd_log_error('No payment key found!');
                    throw new Exception('No payment key found!');
                }

                $post_response = wp_remote_post("http://110.41.71.103:9080/payment/ecommerce/order",
                    array(
                        'headers' => $headers,
                        'body' => json_encode([
                            'checkout_id' => $checkout_id,
                            'website' => $website,
                            'lang' => $lang,
                            'orderNo' => $order->id,
                            'email' => $order->email,
                            'payLinkId' => $payment_key,
                            'currency' => $pmpro_currency,
                            'amount' => $total
                        ]),
                        'method' => 'POST',
                        'data_format' => 'body'
                    )
                );
                $post_response_code = $post_response['response']['code'];
                $post_response_successful = !is_wp_error($post_response_code) && $post_response_code >= 200 && $post_response_code < 300;
                if (!$post_response_successful) {
                    unuspay_edd_log_error('ecommerce order failed!' . $post_response->get_error_message());
                    throw new Exception('request failed!');
                }
                $post_response_json = json_decode($post_response['body']);
                if ($post_response_json->code != 200) {
                    unuspay_edd_log_error('ecommerce order failed!' . $post_response->get_error_message());
                    throw new Exception('request failed!');
                }

                return $post_response_json->data;
            }
            public static function unuspay_generate_checkout_token($merchant_id, $merchant_key, $orderID, $amount, $currency_code)
            {
                global $wpdb;

                $unuspay_generate_checkout_token_url = "https://dashboard.unuspay.com/api/order/pay/token";
                $unuspay_checkout_url = "https://dashboard.unuspay.com/#/cashier/choose?token=";

                $platform = "PAIDMEMBERSHIPSPRO";
                $callback_url = trim(get_site_url(), "/ ") . "/unuspay.pmp.callback.php?status=completed&type=AURPAYPMP&platform=AURPAY&order_id=" . $orderID;
                $current_url = home_url(add_query_arg(array(), $wpdb->request));

                $origin = array(
                    'id' => $orderID,
                    'price' => $amount,
                    'currency' => $currency_code,
                    'callback_url' => $callback_url,
                    'succeed_url' => $current_url,
                    'url' => trim(get_site_url(), "/ "),
                );

                $data = array(
                    'platform' => $platform,
                    'origin' => $origin,
                    'user_id' => $merchant_id,
                    'key' => $merchant_key
                );

                $token_result = self::httpPost($unuspay_generate_checkout_token_url, json_encode($data), $merchant_key);
                $response_data = json_decode($token_result['body'], true);
                if (isset($response_data['data']) && $response_data['code'] == 0 && isset($response_data['data']['token']) && $response_data['data']['token'] != "")
                {
                    $token = $response_data['data']['token'];
                    $redirect_url = $unuspay_checkout_url . $token;
                    wp_redirect($redirect_url);
                    exit;
                }

                echo '<div>' . esc_html(__('Please make sure you use the correct Merchant ID and Merchant Public Key.', 'unuspay-pmp')) . '</div>';

                return false;
            }

            public static function httpPost($url, $data, $API_KEY)
            {
                $body = $data;
                $headers = array(
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Content-Length' => strlen($data),
                    'API-KEY' => $API_KEY,
                );
                $args = array(
                    'body' => $body,
                    'timeout'     => '5',
                    'redirection' => '5',
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => $headers,
                );

                $response = wp_remote_post($url, $args);

                if ($response)
                {
                    return $response;
                }
                return [];
            }

            public static function callback_parse_request()
            {
                if (!session_id())
                {
                    session_start();
                }

                ob_start();

                include_once(plugin_dir_path(__FILE__) . "includes/unuspay.pmp.callback.php");

                if (ob_get_level() > 0)
                {
                    ob_flush();
                }

                return true;
            }

			public static function pmpro_checkout_before_change_membership_level($user_id, $order)
            {
                if ($order->total == 0)
                {
                    return true;
                }

                wp_redirect(pmpro_url("confirmation"));
                
                exit;
            }
        }
    }
}


add_action(
    'rest_api_init', 'init_rest_api'
);

function init_rest_api()
{
    register_rest_route(
        'unuspay/pmp',
        '/checkouts/(?P<id>[\w-]+)',
        [
            'methods' => 'POST',
            'callback' => 'get_checkout_accept',
            'permission_callback' => '__return_true'
        ]
    );
    register_rest_route(
        'unuspay/pmp',
        '/checkouts/(?P<id>[\w-]+)/track',
        [
            'methods' => 'POST',
            'callback' => 'track_payment',
            'permission_callback' => '__return_true'
        ]
    );
    register_rest_route(
        'unuspay/pmp',
        '/validate',
        array(
            'methods' => 'POST,GET',
            'callback' => 'process_notify',
            'permission_callback' => '__return_true'
        )
    );
    register_rest_route(
        'unuspay/pmp',
        '/release',
        [
            'methods' => 'POST',
            'callback' => 'check_release',
            'permission_callback' => '__return_true'
        ]
    );

}

function get_checkout_accept($request)
{

    global $wpdb;
    $id = $request->get_param('id');
    $accept = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT accept FROM {$wpdb->prefix}pmp_unuspay_checkouts WHERE id = %s LIMIT 1",
            $id
        )
    );
    $order_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}pmp_unuspay_checkouts WHERE id = %s LIMIT 1",
            $id
        )
    );
    $checkout_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pmp_unuspay_checkouts WHERE id = %s LIMIT 1",
            $id
        )
    );
    $order = new MemberOrder();
    $order->getMemberOrderByID($order_id);
    //$order = pmp_get_payment($order_id);

    if ($order->status === 'success') {
        $response = rest_ensure_response(
            json_encode([
                'redirect' => pmp_get_success_page_uri()
            ])
        );
    } else {
        $response = rest_ensure_response($accept);
    }

    $response->header('X-Checkout', json_encode([
        'request_id' => $id,
        'checkout_id' => $checkout_id,
        'order_id' => $order_id,
        'total' => $order->total,
        'currency' => $order->currency
    ]));
    return $response;
}

function track_payment($request)
{

    global $wpdb;
    $jsonBody = $request->get_json_params();
    $id = $jsonBody->id;
    $accept = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT accept FROM {$wpdb->prefix}pmp_unuspay_checkouts WHERE id = %s LIMIT 1",
            $id
        )
    );
    $order_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}pmp_unuspay_checkouts WHERE id = %s LIMIT 1",
            $id
        )
    );
    $payment = pmp_get_payment($order_id);

    $tracking_uuid = wp_generate_uuid4();

    $total = $payment->total;

    $transaction_id = $jsonBody->transaction;

    if (empty($transaction_id)) { // PAYMENT TRACE

        if ($payment->status('success') || $payment->status('pending')) {
            unuspay_pmp_log_error('Order has been completed already!');
            throw new Exception('Order has been completed already!');
        }


    } else { // PAYMENT TRACKING

        $result = $wpdb->insert("{$wpdb->prefix}pmp_unuspay_transactions", array(
            'order_id' => $order_id,
            'checkout_id' => $id,
            'tracking_uuid' => $tracking_uuid,
            'blockchain' => $jsonBody->blockchain,
            'transaction_id' => $transaction_id,
            'sender_id' => $jsonBody->sender,
            'receiver_id' => '',
            'token_id' => '',
            'amount' => 0.00,
            'status' => 'VALIDATING',

            'created_at' => current_time('mysql')
        ));
        if (false === $result) {
            unuspay_pmp_log_error('Storing tracking failed!');
            throw new Exception('Storing tracking failed!!');
        }

    }

    $endpoint = 'http://110.41.71.103:8080/payment/pay';

    $jsonBody->callback = get_site_url(null, 'index.php?rest_route=/unuspay/pmp/validate');
    $jsonBody->trackingId = $tracking_uuid;
    $post = wp_remote_post($endpoint,
        array(
            'body' => json_encode($jsonBody),
            'method' => 'POST',
            'data_format' => 'body'
        )
    );

    $response = rest_ensure_response('{}');

    if (!is_wp_error($post) && (wp_remote_retrieve_response_code($post) == 200 || wp_remote_retrieve_response_code($post) == 201) && wp_remote_retrieve_body($post)->code == 200) {
        $response->set_status(200);
    } else {
        if (is_wp_error($post)) {
            UnusPay_WC_Payments::log($post->get_error_message());
        } else {
            error_log(wp_remote_retrieve_body($post));
        }
        $response->set_status(500);
    }

    return $response;
}

function check_release($request)
{

    global $wpdb;
    $jsonBody = $request->get_json_params();

    $checkout_id = $jsonBody->id;
    $existing_transaction_status = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE checkout_id = %s ORDER BY created_at DESC LIMIT 1",
            $checkout_id
        )
    );

    if ('VALIDATING' === $existing_transaction_status) {
        $tracking_uuid = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT tracking_uuid FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE checkout_id = %s ORDER BY created_at DESC LIMIT 1",
                $checkout_id
            )
        );

        $endpoint = 'http://110.41.71.103:8080/payment/release';

        $response = wp_remote_post($endpoint,
            array(
                'body' => json_encode($jsonBody),
                'method' => 'POST',
                'data_format' => 'body'
            )
        );
        $rspBody = wp_remote_retrieve_body($response);
        if (!is_wp_error($response) && (wp_remote_retrieve_response_code($response) == 200 || wp_remote_retrieve_response_code($response) == 201) && $rspBody->code == 200) {


            $order_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT order_id FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
                    $tracking_uuid
                )
            );

            $expected_blockchain = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT blockchain FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
                    $tracking_uuid
                )
            );
            $expected_transaction = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT transaction_id FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
                    $tracking_uuid
                )
            );
            $order = wc_get_order($order_id);
            //$responseBody = json_decode( $response['body'] );
            $status = $rspBody->data->status;
            $transaction = $rspBody->data->transaction;

            if ($expected_transaction != $transaction) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}pmp_unuspay_transactions SET transaction_id = %s WHERE tracking_uuid = %s",
                        $transaction,
                        $tracking_uuid
                    )
                );
            }

            if (
                'success' === $status &&
                $rspBody->data->blockchain === $expected_blockchain
            ) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}pmp_unuspay_transactions SET status = %s, confirmed_at = %s, confirmed_by = %s, failed_reason = NULL WHERE tracking_uuid = %s",
                        'SUCCESS',
                        current_time('mysql'),
                        'API',
                        $tracking_uuid
                    )
                );
                $order->status = 'success';
                $order->saveOrder();

            } else if ('failed' === $status) {
                $failed_reason = 'fail';
                if (empty($failed_reason)) {
                    $failed_reason = 'MISMATCH';
                }
                UnusPay_WC_Payments::log('Validation failed: ' . $failed_reason);
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}pmp_unuspay_transactions SET failed_reason = %s, status = %s, confirmed_by = %s WHERE tracking_uuid = %s",
                        $failed_reason,
                        'FAILED',
                        'API',
                        $tracking_uuid
                    )
                );
                pmp_update_order_status($order_id, 'faild');
            }
        }
    }

    $existing_transaction_status = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE checkout_id = %s ORDER BY created_at DESC LIMIT 1",
            $checkout_id
        )
    );

    if (empty($existing_transaction_status) || 'VALIDATING' === $existing_transaction_status) {
        $response = new WP_REST_Response();
        $response->set_status(200);
        return $response;
    }

    $order_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE checkout_id = %s ORDER BY id DESC LIMIT 1",
            $checkout_id
        )
    );
    $order = wc_get_order($order_id);


    if ('SUCCESS' === $existing_transaction_status) {
        $response = rest_ensure_response([
            'code' => 200,
            'data' => [
                'status' => 'success',
                'forward_to' => pmp_get_success_page_uri()
            ]
        ]);
        $response->set_status(200);
        return $response;
    } else {
        $failed_reason = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT failed_reason FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE checkout_id = %s ORDER BY id DESC LIMIT 1",
                $checkout_id
            )
        );
        $response = rest_ensure_response([
            'code' => 200,
            'data' => [
                'status' => 'failed'
            ]
        ]);

        $response->set_status(200);
        return $response;
    }
}

function process_notify(WP_REST_Request $request)
{
    global $wpdb;
    $response = new WP_REST_Response();


    $tracking_uuid = $request->get_param('trackingId');
    $existing_transaction_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
            $tracking_uuid
        )
    );

    if (empty($existing_transaction_id)) {
        UnusPay_WC_Payments::log('Transaction not found for tracking_uuid');
        $response->set_status(404);
        return $response;
    }

    $order_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
            $tracking_uuid
        )
    );

    $expected_blockchain = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT blockchain FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
            $tracking_uuid
        )
    );
    $expected_transaction = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT transaction_id FROM {$wpdb->prefix}pmp_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
            $tracking_uuid
        )
    );

    $status = $request->get_param('status');
    $transaction = $request->get_param('transaction');

    if ($expected_transaction != $transaction) {
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}pmp_unuspay_transactions SET transaction_id = %s WHERE tracking_uuid = %s",
                $transaction,
                $tracking_uuid
            )
        );
    }

    if (
        'success' === $status &&
        $request->get_param('blockchain') === $expected_blockchain
    ) {
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}pmp_unuspay_transactions SET status = %s, confirmed_at = %s, confirmed_by = %s, failed_reason = NULL WHERE tracking_uuid = %s",
                'SUCCESS',
                current_time('mysql'),
                'API',
                $tracking_uuid
            )
        );
        $order = new MemberOrder();
        $order->getMemberOrderByID($order_id);
        $order->status = 'success';
        $order->saveOrder();

    } else {
        $failed_reason = $request->get_param('failed_reason');
        if (empty($failed_reason)) {
            $failed_reason = 'MISMATCH';
        }
        UnusPay_WC_Payments::log('Validation failed: ' . $failed_reason);
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}pmp_unuspay_transactions SET failed_reason = %s, status = %s, confirmed_by = %s WHERE tracking_uuid = %s",
                $failed_reason,
                'FAILED',
                'API',
                $tracking_uuid
            )
        );
        pmp_update_order_status($order_id, 'failed');
    }

    $response->set_status(200);
    return $response;
}

add_action('wp_enqueue_scripts', 'pmp_custom_scripts');

function pmp_custom_scripts()
{
    // 仅在 EDD 结账页面加载
    //if (pmp_is_checkout()) {
    wp_register_script( 'UNUSPAY_WC_WIDGETS',plugin_dir_url(__FILE__) .'dist/widgets.bundle.js', array(), '1.0', true);
    wp_enqueue_script( 'UNUSPAY_WC_WIDGETS' );

    // 注册脚本（依赖 jQuery）
    wp_register_script(
        'pmp-unuspay-check',
        plugin_dir_url(__FILE__) . 'dist/checkout.js', // 脚本路径
        array('wp-api-request', 'jquery'), // 依赖
        '1.0', // 版本号
        true // 在页脚加载
    );


    // 加载脚本
    wp_enqueue_script('pmp-unuspay-check');
    //}
}
