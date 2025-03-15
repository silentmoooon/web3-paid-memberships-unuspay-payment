<?php

/**
 * Plugin Name: Aurpay Crypto payment for Paid Memberships Pro
 * Plugin URI: https://
 * Description: Pay with Crypto For Paid Memberships Pro, Let your customer pay with ETH, USDC, USDT, DAI, lowest fees, non-custodail & no fraud/chargeback, 50+ cryptos. Invoice, payment link, payment button.
 * Version: 1.0.17
 * Author: Aurpay
 * Author URI: https://aurpay.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: Expand customer base with crypto payment, non-custodail & no fraud/chargeback, low fees, 50+ cryptos. Invoice, payment link, payment button.
 * Tags: paid memberships pro, pmp, bitcoincash, bitcoin cash, bitcoins, gourl, cryptocurrency, btc, coinbase, bitpay, ecommerce, paypal, accept bitcoin, payment, payment gateway, digital downloads, download, downloads, e-commerce, e-downloads, e-store, wp ecommerce, litecoin, dogecoin, dash，Crypto, cryptocurrency, crypto payment, erc20, cryptocurrency, bitcoin, bitcoin lighting network, ethereum, crypto pay, smooth withdrawals, cryptocurrency payments, low commission, pay with meta mask, payment button, invoice, crypto paid memberships pro，bitcoin paid memberships pro，ethereum，pay crypto，virtual currency，bitcoin wordpress plugin，free crypto plugin,
 * Requires at least: 5.8
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('aurpay_pmp_gateway_load'))
{

    add_action('plugins_loaded', 'aurpay_pmp_gateway_load', 20);

    DEFINE("AURPAY_PMP_GATEWAY_NAME", "pmp_aurpay_gateway");

    add_action('pmpro_invoice_bullets_top', function (){error_reporting(E_ERROR);});

    function aurpay_pmp_gateway_load()
    {
        if (!class_exists('PMProGateway')) return;

        add_action('init', array('PMProGateway_aurpay', 'init'));

        add_filter('pmpro_pages_shortcode_confirmation', array('PMProGateway_aurpay', 'pmpro_pages_shortcode_confirmation'), 20, 1);

        add_filter('plugin_action_links', array('PMProGateway_aurpay', 'plugin_action_links'), 10, 2);

        add_filter('pmpro_get_gateway', array('PMProGateway_aurpay', 'select_gateway'), 10, 1);

        add_filter('pmpro_valid_gateways', array('PMProGateway_aurpay', 'valid_gateway'), 10, 1);

        add_action('pmpro_checkout_boxes', array('PMProGateway_aurpay', 'checkout_boxes'));

        add_action('parse_request', array('PMProGateway_aurpay', 'callback_parse_request'));

		add_filter( 'plugin_row_meta', array('PMProGateway_aurpay', 'plugin_row_meta'), 10, 2 );

        class PMProGateway_aurpay extends PMProGateway
        {
            function __construct($gateway = NULL)
            {
                $this->gateway = $gateway;
                return $this->gateway;
            }

            public static function init()
            {
                add_filter('pmpro_gateways', array('PMProGateway_aurpay', 'pmpro_gateways'));

                add_filter('pmpro_payment_options', array('PMProGateway_aurpay', 'pmpro_payment_options'));
                add_filter('pmpro_payment_option_fields', array('PMProGateway_aurpay', 'pmpro_payment_option_fields'), 10, 2);

                $gateway = pmpro_getGateway();
                if ($gateway == "aurpay")
                {
                    add_filter('pmpro_include_billing_address_fields', '__return_false');
                    add_filter('pmpro_include_payment_information_fields', '__return_false');
                    add_filter('pmpro_required_billing_fields', array('PMProGateway_aurpay', 'pmpro_required_billing_fields'));
					add_filter('pmpro_checkout_before_change_membership_level', array('PMProGateway_aurpay', 'pmpro_checkout_before_change_membership_level'), 1, 2);
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
                    $aurpay_link = '<a href="https://dashboard.aurpay.net/#/login?cur_url=/integration&platform=PAIDMEMBERSHIPSPRO" target="_blank" style="color: #39b54a; font-weight: bold;">' . __('Get Aurpay', AURPAY_PMP_GATEWAY_NAME) . '</a>';
                    $settings_link = '<a href="' . admin_url('admin.php?page=pmpro-paymentsettings') . '">' . __('Settings', AURPAY_PMP_GATEWAY_NAME) . '</a>';
                    array_unshift($links, $aurpay_link, $settings_link);
                }

                return $links;
            }

            public static function select_gateway($gateway)
            {
                if (!session_id()) session_start();

                if (isset($_POST['gateway']))
                {
                    $gateway = $_SESSION['aurpay_pmp_gateway'] = sanitize_text_field($_POST['gateway']);
                }
                else
                {
                    if (isset($_SESSION['aurpay_pmp_gateway']) && $_SESSION['aurpay_pmp_gateway'] == 'aurpay')
                    {
                        $gateway = sanitize_text_field($_SESSION['aurpay_pmp_gateway']);
                    }
                }

                return $gateway;
            }

            public static function valid_gateway($gateways)
            {
                if (array_search('aurpay', $gateways) === false)
                {
                    $gateways[] = 'aurpay';
                }

                return $gateways;
            }

            public static function admin_aurpay_notice()
            {
                if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'pmpro-paymentsettings')
                {
                    $tmp = '<div class="notice notice-info is-dismissible" style="margin:20px">';
                    $tmp .= '<img style="float:left;width: 140px;height: 55px;" alt="img" src="' . plugins_url("/images/aurpay.png", __FILE__) . '" border="0" vspace="12" hspace="10">';
                    $tmp .= '<p>' . sprintf(__("You can provide your customers with multiple payment options at the checkout page of PaidMembershipsPro. To enable this feature, you need to set up your Aurpay Crypto Payment Gateway settings on this page, then click the 'Save Settings' button. Afterward, you can switch to another payment gateway (such as Paypal or Stripe) and keep it as your primary gateway. The Aurpay settings will still be remembered in the background, and both payment gateways will be displayed on the checkout page. If you only want to use the Aurpay Crypto Payment Gateway on the checkout page, you should keep it as your primary gateway. For more information, please visit ", AURPAY_PMP_GATEWAY_NAME));
                    $tmp .= '<a href="www.aurpay.net" target="_blank">'. "www.aurpay.net" . '</a>.</p>';
                    $tmp .= '</div>';
                    echo wp_kses_post($tmp);
                }

                return true;
            }

            public static function getGatewayOptions()
            {
                global $wpdb;

                $options = array(
                    'currency',
                    'aurpay_merchant_id',
                    'aurpay_merchant_public_key',
                );

                $levels = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT name FROM {$wpdb->pmpro_membership_levels}"));

                foreach ($levels as $level) {
                    $options[] = 'aurpay_level_' . esc_attr(str_replace(' ', '', $level));
                }

                return $options;
            }

            public static function pmpro_payment_options($options)
            {
                $aurpay_options = PMProGateway_aurpay::getGatewayOptions();

                $options = array_merge($aurpay_options, $options);

                return $options;
            }

            public static function pmpro_gateways($gateways)
            {
                if (empty($gateways['aurpay']))
                {
                    $gateways = array_slice($gateways, 0, 1) + array("aurpay" => __('Aurpay Crypto Payment Gateway', AURPAY_PMP_GATEWAY_NAME)) + array_slice($gateways, 1);
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
				        'dome' => '<a style="color: #39b54a;" href="https://example-wp.aurpay.net/membership-account/membership-checkout/" aria-label="' . esc_attr( __( 'View Aurpay Demo', AURPAY_PMP_GATEWAY_NAME ) ) . '" target="_blank">' . __( 'Demo', AURPAY_PMP_GATEWAY_NAME ) . '</a>',
                        'video' => '<a style="color: #39b54a;" href="https://youtu.be/OCaz-_dbTGA" aria-label="' . esc_attr( __( 'View Aurpay Video Tutorials', AURPAY_PMP_GATEWAY_NAME ) ) . '" target="_blank">' . __( 'Video Tutorials', AURPAY_PMP_GATEWAY_NAME ) . '</a>',
                    ];

                    $plugin_meta = array_merge( $plugin_meta, $row_meta );
                }

                return $plugin_meta;
            }

            public static function pmpro_payment_option_fields($options, $gateway)
            {
                global $aurpay, $wpdb;

                $description  = "<a target='_blank' href='https://aurpay.net/' ><img border='0' src='" . plugins_url('/images/aurpay.png', __FILE__) . "'></a>";
                $description .= '<p style="margin-top: 10px;"><b>AURPAY official <a href="https://aurpay.net/" target="_blank">website.</a></b></p>';
                $description .= '<p style="margin-top: 10px;">Aurpay does not charge any setup fees, subscription fees, hidden costs, or fees for chargebacks. It is a pure non-custodial platform and there are no third-party charges. All transactions are peer-to-peer. Merchants can send crypto payment links directly to customers without the need for any middleman or code, making the payment process easy and hassle-free.</p>';
                $description .= '<p style="margin-top: 20px;"><b>PARTNER INCENTIVE REWARD PROGRAM!</b></p>';
                $description .= '<p style="margin-top: 10px;">Join the hundreds of popular WordPress and Paid Memberships Pro sellers who are already benefiting from using Aurpay as their global growth partner. With our managed platform, you can start accepting crypto payments in just 1 minute and see the immediate impact on your business. Our platform is designed to help you reach a global audience and make the payment process easy and seamless for your customers. Start using Aurpay today and experience the benefits of accepting crypto payments.</p>';
                $description .= '<p style="margin-top: 20px;"><b>Learn more about<a href="https://aurpay.net/partner/" target="_blank"> Partner </a>program!</b></p>';
                $description .= '<p style="margin-top: 10px;">Join our partner program and start earning a percentage of the transaction-based profit of each merchant you bring to Aurpay. Our partner dashboard makes it easy to manage your merchants and track your earnings.</p>';
                $description .= '<p>With our easy sign-up referral link and lifetime reward program, you can earn money for the lifetime of your merchant’s transactions. The more merchants you bring, the more rewards you earn.</p>';
                $description .= '<p style="margin-top: 20px;"><a href="https://dashboard.aurpay.net/#/login?cur_url=/integration&platform=PAIDMEMBERSHIPSPRO" target="_blank">Get Started</a></p>';
                $tr = '<tr class="gateway gateway_aurpay"' . ($gateway != "aurpay" ? ' style="display: none;"' : '') . '>';
                $tmp  = '<tr class="pmpro_settings_divider gateway gateway_aurpay"' . ($gateway != "aurpay" ? ' style="display: none;"' : '') . '>';
                $tmp .= '<td colspan="2"><hr/><h2>Aurpay Crypto Payment Gateway Settings</h2></td>';
                $tmp .= "</tr>";
                $tmp .= $tr;
                $tmp .= '<td colspan="2"><div style="font-size:13px;line-height:22px">' . $description . '</div></td></tr>';

                $tmp .= $tr . '<th scope="row" valign="top" style="padding-left:10px"><label for="aurpay_merchant_id">Aurpay Merchant Id</label></th><td><input type="text" value="' . $options["aurpay_merchant_id"] . '" name="aurpay_merchant_id" id="aurpay_merchant_id"></td></tr>';
                $tmp .= $tr . '<th scope="row" valign="top" style="padding-left:10px"><label for="aurpay_merchant_public_key">Aurpay Public Key</label></th><td><input type="text" value="' . $options["aurpay_merchant_public_key"] . '" name="aurpay_merchant_public_key" id="aurpay_merchant_public_key"></td></tr>';

                $tmp .= $tr . '<td colspan="2"><h4>Set Up Membership Level Display</h4>';
                $tmp .= "</tr>";
                $tmp .= $tr . '<td colspan="12"><p>Note:</p><ul><li>1. Aurpay Crypto Payment Gateway will always be available, but you can configure which membership levels to show below.</li><li>2. Check to show which level will show aurpay payment.</li><li>3. If it doesn\'t work, make sure there are no strange characters in the level name.</li></ul></td>';
                $tmp .= "</tr>";
                $tmp .= $tr . '<td colspan="12">';

                $levels = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT name FROM {$wpdb->pmpro_membership_levels}"));
                foreach ($levels as $level) {
                    $level_key = "aurpay_level_" . esc_attr(str_replace(' ', '', $level));
                    $level_value = $options[$level_key];
        
                    $tmp .= '<label style="margin-right: 10px;"><input type="checkbox" name="' . esc_attr($level_key) . '" ' . (!empty($level_value) ? 'checked="checked"' : '') . ' value="' . 1 . '"> ' . esc_html($level) . '</label>';
                }
                $tmp .="</td></tr>";
                
                echo $tmp;

                if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'pmpro-paymentsettings')
                {
                    try {
                        self::verify_aurpay_key($options["aurpay_merchant_id"], $options['aurpay_merchant_public_key']);
                    } catch (Exception $e) {
                        return;
                    }
                }

                return;
            }

            public static function verify_aurpay_key($merchant_id, $merchant_public_key)
            {

                $key_result = wp_remote_get( 'https://dashboard.aurpay.net/api/plugin/key/verify?id=' . $merchant_id . '&key=' .$merchant_public_key .'&name=PAIDMEMBERSHIPSPRO&url=' . parse_url(site_url(), PHP_URL_HOST) );
                $response_data = json_decode($key_result['body'], true);


                if (!($response_data['data']))
                {
                    self::admin_notice_for_key();
                    self::admin_aurpay_notice();
                }
            }

            public static function admin_notice_for_key()
            {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e( '[Aurpay PMP] The aurpay merchant id and public key you entered is incorrect. Please check the video link for more information', AURPAY_PMP_GATEWAY_NAME ); ?>
                    (<a href="https://youtu.be/OCaz-_dbTGA" target="blank">https://youtu.be/OCaz-_dbTGA</a>)</p>
                </div>
                <?php
            }

            public static function checkout_boxes()
            {
                global $pmpro_requirebilling, $gateway, $pmpro_review, $wpdb;

                $setting_gateway = get_option("pmpro_gateway");
                if ($setting_gateway == "aurpay")
                {
                    echo '<h2>' . esc_html(__('Payment method', AURPAY_PMP_GATEWAY_NAME)) . '</h2>';
                    echo esc_html(__('Aurpay Crypto Payment Gateway', AURPAY_PMP_GATEWAY_NAME)) . '<img style="vertical-align:middle" src="' . plugins_url("/images/aurpay.png", __FILE__) . '" border="0" vspace="10" hspace="10" height="43" width="143"><br><br>';
                    return true;
                }

                $arr = pmpro_gateways();

                $setting_gateway_name = (isset($arr["$setting_gateway"]) && $arr["$setting_gateway"]) ? $arr["$setting_gateway"] : ucwords($setting_gateway);

                $image = $setting_gateway;
                if (in_array($image, array("paypalexpress", "paypal", "payflowpro", "aurpay"))) $image = "paypal";
                if (!in_array($image, array("authorizenet", "braintree", "check", "cybersource", "aurpay", "paypal", "stripe", "twocheckout"))) $image = "creditcards";

                $selected_level = isset($_REQUEST['pmpro_level']) ? intval($_REQUEST['pmpro_level']) : null;
                $selected_level_name = "";

                if ($selected_level)
                {
                    $level_obj = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->pmpro_membership_levels} WHERE id = %d", $selected_level));
                    $selected_level_name = str_replace(' ', '', $level_obj->name);
                }

                if (empty($pmpro_review))
                {
?>
                    <div id="pmpro_payment_method" class="pmpro_checkout" <?php if (!$pmpro_requirebilling) { ?>style="display: none;" <?php } ?>>
                        <br>
                        <h2><?php _e('Choose your payment method', AURPAY_PMP_GATEWAY_NAME) ?> -</h2>
                        <div class="pmpro_checkout-fields">

                            <span class="gateway_aurpay" id="aurpay_radio" <?php echo !get_option('pmpro_aurpay_level_' . esc_attr($selected_level_name)) ? 'style="display:none"' : ''; ?>>
                                <input type="radio" name="gateway" value="aurpay" <?php if ($gateway == "aurpay") { ?>checked="checked" <?php } ?> />
                                <a href="javascript:void(0);" class="pmpro_radio" style="box-shadow:none"><?php _e('Aurpay Crypto Payment Gateway', AURPAY_PMP_GATEWAY_NAME) ?></a>
                                <img style="vertical-align:middle" src="<?php echo plugins_url("/images/aurpay.png", __FILE__); ?>" border="0" vspace="10" hspace="10" height="43" width="143">
                            </span>

                            <br>
                            <span class="gateway_<?php echo esc_attr($setting_gateway); ?>" id="other_radio">
                                <input type="radio" name="gateway" value="<?php echo esc_attr($setting_gateway); ?>" <?php if (!$gateway || $gateway == $setting_gateway) { ?>checked="checked" <?php } ?> />
                                <a href="javascript:void(0);" class="pmpro_radio" style="box-shadow:none"><?php _e($setting_gateway_name, AURPAY_PMP_GATEWAY_NAME) ?></a>
                                <img style="vertical-align:middle" src="<?php echo plugins_url("/images/" . $image . ".png", __FILE__); ?>" border="0" vspace="10" hspace="10" height="43">
                            </span>
                            <br><br><br>

                        </div>
                    </div>

                    <script>
                        var  pmpro_require_billing = <?php if ($pmpro_requirebilling) echo "true";
                                                    else echo "false"; ?>;

                        jQuery(document).ready(function() {
                            jQuery('#pmpro_aurpay_checkout').appendTo('div.pmpro_submit');

                            function showLiteCheckout() {
                                jQuery('#pmpro_billing_address_fields').hide();
                                jQuery('#pmpro_payment_information_fields').hide();
                                jQuery('#pmpro_paypalexpress_checkout, #pmpro_aurpay_checkout, #pmpro_payflowpro_checkout, #pmpro_paypal_checkout').hide();
                                jQuery('#pmpro_submit_span').show();

                                pmpro_require_billing = false;
                            }

                            function showFullCheckout() {
                                jQuery('#pmpro_billing_address_fields').show();
                                jQuery('#pmpro_payment_information_fields').show();

                                pmpro_require_billing = true;
                            }


                            jQuery('input[name=gateway]').click(function() {
                                if (jQuery(this).val() != 'aurpay')
                                {
                                    showFullCheckout();
                                }
                                else
                                {
                                    showLiteCheckout();
                                }
                            });

                            if (jQuery('input[name=gateway]:checked').val() != 'aurpay' && pmpro_require_billing == true)
                            {
                                showFullCheckout();
                            }
                            else
                            {
                                showLiteCheckout();
                            }

                            jQuery('a.pmpro_radio').click(function() {
                                jQuery(this).prev().click();
                            });

                            jQuery('#aurpay_radio').click(function() {
                                jQuery('div[class=pmpro_check_instructions]').hide();
                            });

                            jQuery('#other_radio').click(function() {
                                jQuery('div[class=pmpro_check_instructions]').show();
                            });
                        });
                    </script>
                <?php
                } else {
                ?>
                    <script>
                        jQuery(document).ready(function() {
                            jQuery('#pmpro_billing_address_fields').hide();
                            jQuery('#pmpro_payment_information_fields').hide();
                        });
                    </script>
<?php
                }
            }

            public static function pmpro_pages_shortcode_confirmation($content)
            {
                global $wpdb;

                if (!session_id()) session_start();

                if (!isset($_SESSION['aurpay_pmp_orderid'])) return $content;

                $order = new MemberOrder();
                $order->getMemberOrderByID($_SESSION['aurpay_pmp_orderid']);

                if (!empty($order) && $order->gateway == "aurpay" && isset($order->total) && $order->total > 0 && $order->user_id == get_current_user_id())
                {
                    unset($_SESSION['aurpay_pmp_orderid']);
                    self::pmpro_aurpay_cryptocoin_payment($order);
                }

                return $content;
            }

            public function process(&$order)
            {
                if (!empty($order) && $order->gateway == "aurpay")
                {
                    $order->payment_type = "Aurpay Crypto Payment Gateway";
                    $order->cardtype = "";
                    $order->ProfileStartDate = pmpro_calculate_profile_start_date( $order, 'Y-m-d\TH:i:s\Z' );
                    $order->status = "pending";
                    if(empty($order->code)) $order->code = $order->getRandomCode();
                    $order->saveOrder();
                    do_action('pmpro_before_commit_express_checkout', $order);
                    $_SESSION['aurpay_pmp_orderid'] = $order->id;
                }

                return true;
            }

            public static function pmpro_aurpay_cryptocoin_payment(&$order)
            {
                global $aurpay, $pmpro_currency, $current_user, $wpdb;

                if (!$order)
                {
                    echo "<div class='pmpro_message pmpro_error'>" . esc_html('The Aurpay payment gateway plugin was invoked to process a payment, but it was unable to fetch the order details. Therefore, the process cannot be carried forward. Please check the errors through the following steps: 1. Check your backend configuration to ensure it is correct. 2. Check your network environment. 3. Contact the Aurpay(contact@aurpay.net) service provider for further assistance.', 'aurpay-pmp') . "</div>";
                }


                $merchant_id = pmpro_getOption("aurpay_merchant_id");
                $merchant_key = pmpro_getOption("aurpay_merchant_public_key");

                $order_id = $order->id;
                $order_total = $order->total;
                $order_currency = $pmpro_currency;
                $order_user_id = $order->user_id;

                if (!$order_id)
                {
                    echo "<div class='pmpro_message pmpro_error'>" . esc_html(__('The Aurpay payment gateway plugin was triggered to process a payment, but it failed to retrieve the order details. As a result, the process cannot be continued. Please check the errors through the following steps: 1. Check your backend configuration to ensure it is correct. 2. Check your network environment. 3. Contact the Aurpay(contact@aurpay.net) service provider for further assistance.', 'aurpay-pmp')) . "</div>";
                }
                elseif (!$merchant_id || !$merchant_key || !$order_total || !$order_currency || !$order_user_id)
                {
                    echo "<div class='pmpro_message pmpro_error'>" . esc_html(__('Currently, there are some issues with the aurpay crypto payment. Please check the errors through the following steps: 1. Check your backend configuration to ensure it is correct. 2. Check your network environment. 3. Contact the Aurpay(contact@aurpay.net) service provider for further assistance.', 'aurpay-pmp')) . "</div>";
                }
                else
                {
                    $plugin = "aurpaypmpro";
                    $amount = $order_total;
                    $currency = $order_currency;
                    $orderID = $order_id;
                    $userID = $order_user_id;
                    $platform = "PAIDMEMBERSHIPPRO";

                    if (!$userID) $userID = "guest";

                    if (!$userID)
                    {
                        echo "<div align='center'><a href='" . wp_login_url(get_permalink()) . "'>
                        <span>" . esc_html(__('Before making payment, you must login or register on website.', 'aurpay-pmp')) . "</span></a></div>";
                    }
                    elseif ($amount <= 0)
                    {
                        echo "<div class='pmpro_message pmpro_error'>" . esc_html(sprintf(__("The amount for this order is '%s' and cannot be paid through Aurpay Crypto Payment. Please contact us(contact@aurpay.net) if you need assistance.", 'aurpay-pmp')), $amount . " " . $currency) . "</div>";
                    }
                    else
                    {
                        if ($amount > 0)
                        {
                            self::aurpay_generate_checkout_token($merchant_id, $merchant_key, $orderID, $amount, $currency);
                        }
                    }
                }

                return false;
            }

            public static function aurpay_generate_checkout_token($merchant_id, $merchant_key, $orderID, $amount, $currency_code)
            {
                global $wpdb;

                $aurpay_generate_checkout_token_url = "https://dashboard.aurpay.net/api/order/pay/token";
                $aurpay_checkout_url = "https://dashboard.aurpay.net/#/cashier/choose?token=";

                $platform = "PAIDMEMBERSHIPSPRO";
                $callback_url = trim(get_site_url(), "/ ") . "/aurpay.pmp.callback.php?status=completed&type=AURPAYPMP&platform=AURPAY&order_id=" . $orderID;
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

                $token_result = self::httpPost($aurpay_generate_checkout_token_url, json_encode($data), $merchant_key);
                $response_data = json_decode($token_result['body'], true);
                if (isset($response_data['data']) && $response_data['code'] == 0 && isset($response_data['data']['token']) && $response_data['data']['token'] != "")
                {
                    $token = $response_data['data']['token'];
                    $redirect_url = $aurpay_checkout_url . $token;
                    wp_redirect($redirect_url);
                    exit;
                }

                echo '<div>' . esc_html(__('Please make sure you use the correct Merchant ID and Merchant Public Key.', 'aurpay-pmp')) . '</div>';

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

                include_once(plugin_dir_path(__FILE__) . "includes/aurpay.pmp.callback.php");

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

add_action('plugins_loaded', 'load_appmp', 99);

function load_appmp()
{
    $active_plugins = get_option('active_plugins');
    if (!in_array('paid-memberships-pro/paid-memberships-pro.php', $active_plugins))
    {
        return false;
    }

    $merchant_id = pmpro_getOption("aurpay_merchant_id");
    $merchant_key = pmpro_getOption("aurpay_merchant_public_key");

    if (isset($merchant_id) && $merchant_id != "" && isset($merchant_key) && $merchant_key != "")
    {
        return false;
    }
    else
    {
        wp_enqueue_style('aurpay-pmp-notice-banner-style' , plugin_dir_url( __FILE__ ) . 'assets/css/aurpay-usage-notice.css');
        add_action('admin_notices', 'appmp_render_usage_notice');
    }
}

if (!function_exists('appmp_render_usage_notice'))
{
    function appmp_render_usage_notice()
    {
        global $pagenow;
        $admin_pages = [ 'index.php', 'plugins.php' ];
        if ( in_array( $pagenow, $admin_pages ) )
        {
            ?>
            <div class="ap-connection-banner aurpay-usage-notice">

                <div class="ap-connection-banner__container-top-text">
                    <span class="notice-dismiss aurpay-usage-notice__dismiss" title="Dismiss this notice"></span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="0" fill="none" width="24" height="24" /><g><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 15h-2v-2h2v2zm0-4h-2l-.5-6h3l-.5 6z" /></g></svg>
                    <span>You're almost done. Setup Aurpay to enable Crypto Payment for you Paid Memberships Pro site.</span>
                </div>
                <div class="ap-connection-banner__inner">
                    <div class="ap-connection-banner__content">
                        <div class="ap-connection-banner__logo">
                            <img src="<?php echo esc_url( plugins_url( 'assets/images/logo_aurpay.svg', __FILE__ ) ); ?>" alt="logo">
                        </div>
                        <h2 class="ap-connection-banner__title">Empower Your Business with Aurpay Crypto Payment</h2>
                        <div class="ap-connection-banner__columns">
                            <div class="ap-connection-banner__text">⭐ Get listed on our online directory to attract <span style="color: #007AFF">300 millions</span> of crypto owners. </div>
                            <div class="ap-connection-banner__text">⭐ Earn up to <span style="color: #007AFF">150,000 satoshi</span> rewards for merchants who finished all settings and more. </div>
                        </div>
                        <div class="ap-connection-banner__rows">
                            <div class="ap-connection-banner__text ap-connection-banner__step">By setting up Aurpay, get a merchant account and save your "<span style="color: #007AFF">Merchant ID</span>" & "<span style="color: #007AFF">Public Key</span>" in Paid Memberships Pro payment settings. </div>
                            <a id="ap-connect-button--alt" rel="external" target="_blank" href="https://dashboard.aurpay.net/#/login?cur_url=/integration&platform=PAIDMEMBERSHIPSPRO" class="ap-banner-cta-button ap_step_pmp_1">Setup Aurpay</a>
                        </div>
                        <div class="ap-connection-banner__rows" style="display: none;">
                            <div class="ap-connection-banner__text ap-connection-banner__step">Save your PublicKey in PAIDMEMBERSHIPSPRO Payment settings.</div>
                            <a id="ap-connect-button--alt" target="_self" href="<?php echo admin_url('admin.php?page=pmpro-paymentsettings') ?>" class="ap-banner-cta-button ap_step_pmp_2">Settings</a>
                        </div>
                    </div>
                    <div class="ap-connection-banner__image-container">
                        <picture>
                            <source type="image/webp" srcset="<?php echo esc_url( plugins_url( 'assets/images/img_aurpay.webp', __FILE__ ) ); ?> 1x, <?php echo esc_url( plugins_url( 'assets/images/img_aurpay-2x.webp', __FILE__ ) ); ?> 2x">
                            <img class="ap-connection-banner__image" srcset="<?php echo esc_url( plugins_url( 'assets/images/img_aurpay.png', __FILE__ ) ); ?> 1x, <?php echo esc_url( plugins_url( 'assets/images/img_aurpay-2x.png', __FILE__ ) ); ?> 2x" src="<?php echo esc_url( plugins_url( 'assets/images/img_aurpay.png', __FILE__ ) ); ?>" alt="img">
                        </picture>
                        <img class="ap-connection-banner__image-background" src="<?php echo esc_url( plugins_url( 'assets/images/background.svg', __FILE__ ) ); ?>" />
                    </div>
                </div>
            </div>

            <?php

            wp_enqueue_script(
                'aurpay-notice-banner-js' ,
                plugin_dir_url(__FILE__) . 'assets/js/aurpay-usage-notice.js',
                array( 'jquery' )
            );
        }
    }
}